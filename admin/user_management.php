<?php
// admin/user_management.php
require_once('../includes/auth_check.php');
// Set page title
$pageTitle = __('user_management');

// Make sure currentUser is available
if (!isset($currentUser) || $currentUser['user_type'] !== 'admin') {
    setFlashMessage('danger', __('admin_access_required'));
    redirect(SITE_URL . '/dashboard/' . ($currentUser['user_type'] ?? 'guest') . '.php');
    exit;
}

// Define allowed actions and get current action
$allowedActions = ['list', 'view', 'edit', 'create', 'delete'];
$action = isset($_GET['action']) && in_array($_GET['action'], $allowedActions) ? $_GET['action'] : 'list';

// Process actions
switch ($action) {
    case 'view':
    case 'edit':
        handleViewEditAction();
        break;
        
    case 'create':
        handleCreateAction();
        break;
        
    case 'delete':
        handleDeleteAction();
        break;
        
    default:
        handleListAction();
}

// Include header
include_once('../includes/header.php');

// Display appropriate view based on action
switch ($action) {
    case 'view':
        displayUserView();
        break;
        
    case 'edit':
        displayEditForm();
        break;
        
    case 'create':
        displayCreateForm();
        break;
        
    case 'delete':
        displayDeleteConfirmation();
        break;
        
    default:
        displayUserList();
}

// Include footer
include_once('../includes/footer.php');

/**
 * Handle view/edit user actions
 */
function handleViewEditAction() {
    global $user, $userActivity, $loginHistory, $currentUser, $action; // Add $action to globals
    
    $userId = validateUserId();
    $user = getUserById($userId);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
        $errors = validateUserData($_POST);
        
        if (empty($errors)) {
            if (updateUser($userId, $_POST)) {
                logActivity($currentUser['id'], 'update_user', ['user_id' => $userId]);
                setFlashMessage('success', __('user_updated'));
                redirect(SITE_URL . '/admin/user_management.php?action=view&id=' . $userId);
            } else {
                setFlashMessage('danger', __('user_update_failed'));
            }
        } else {
            setFlashMessage('danger', implode('<br>', $errors));
        }
    }
    
    $userActivity = getUserActivity($userId, 50);
    $loginHistory = getLoginHistory($userId, 10);
}

/**
 * Handle create user action
 */
function handleCreateAction() {
    global $currentUser;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $errors = validateUserData($_POST, true);
        
        if (empty($errors)) {
            $userId = createUser($_POST);
            
            if ($userId) {
                logActivity($currentUser['id'], 'create_user', ['user_id' => $userId]);
                setFlashMessage('success', __('user_created'));
                redirect(SITE_URL . '/admin/user_management.php?action=view&id=' . $userId);
            } else {
                setFlashMessage('danger', __('user_creation_failed'));
            }
        } else {
            setFlashMessage('danger', implode('<br>', $errors));
        }
    }
}

/**
 * Handle delete user action
 */
function handleDeleteAction() {
    global $currentUser;
    
    $userId = validateUserId();
    $user = getUserById($userId);
    
    // Prevent self-deletion
    if ($userId === $currentUser['id']) {
        setFlashMessage('danger', __('cannot_delete_self'));
        redirect(SITE_URL . '/admin/user_management.php');
       // exit;
    }
    
    // Confirm deletion
    // if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        if (deleteUser($userId)) {
            redirect(SITE_URL . '/admin/user_management.php');
            logActivity($currentUser['id'], 'delete_user', ['user_id' => $userId]);
            setFlashMessage('success', __('user_deleted'));
        } else {
            setFlashMessage('danger', __('user_deletion_failed'));
        }
        redirect(SITE_URL . '/admin/user_management.php');
    
}

/**
 * Handle list users action
 */
function handleListAction() {
    global $users, $total, $page, $totalPages;
    
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $role = isset($_GET['user_type']) ? sanitizeInput($_GET['user_type']) : '';
    $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 20;
    
    $result = getUsersWithPagination($search, $role, $status, $page, $perPage);
    $users = $result['users'];
    $total = $result['total'];
    $totalPages = $result['totalPages'];
}

/**
 * Display user list view
 */
function displayUserList() {
    global $users, $total, $page, $totalPages, $search, $role, $status, $currentUser;
    ?>
    <div class="container-fluid py-4">
        <!-- Search and Filters -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <form method="GET" action="<?= SITE_URL ?>/admin/user_management.php" class="row g-3">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" name="search" placeholder="<?= __('search_users') ?>" value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <select name="user_type" class="form-select">
                            <option value=""><?= __('all_roles') ?></option>
                            <?php foreach (getAllowedRoles() as $roleValue => $roleLabel): ?>
                                <option value="<?= $roleValue ?>" <?= $role === $roleValue ? 'selected' : '' ?>>
                                    <?= $roleLabel ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value=""><?= __('all_status') ?></option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>><?= __('active') ?></option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>><?= __('inactive') ?></option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> <?= __('apply_filters') ?>
                        </button>
                        <a href="<?= SITE_URL ?>/admin/user_management.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i> <?= __('clear') ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Users List -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= __('users_list') ?></h5>
                    <span class="badge bg-primary"><?= $total ?> <?= __('users') ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($users)): ?>
                <div class="p-4 text-center">
                    <i class="fas fa-user-slash text-muted" style="font-size: 3rem;"></i>
                    <p class="mt-3"><?= __('no_users_found') ?></p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= __('id') ?></th>
                                <th><?= __('name') ?></th>
                                <th><?= __('email') ?></th>
                                <th><?= __('user_type') ?></th>
                                <th><?= __('status') ?></th>
                                <th><?= __('registration_date') ?></th>
                                <th><?= __('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-<?= getAvatarColor($user['user_type']) ?> text-white me-2">
                                            <?= getInitials($user['full_name']) ?>
                                        </div>
                                        <div>
                                            <?= htmlspecialchars($user['full_name']) ?>
                                            <?php if ($user['id'] === $currentUser['id']): ?>
                                            <span class="badge bg-info ms-1"><?= __('you') ?></span>
                                            <?php endif; ?>
                                            <div class="small text-muted"><?= htmlspecialchars($user['phone'] ?: __('no_phone')) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getRoleBadgeClass($user['user_type']) ?>">
                                        <?= __(htmlspecialchars($user['user_type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['active']): ?>
                                    <span class="badge bg-success"><?= __('active') ?></span>
                                    <?php else: ?>
                                    <span class="badge bg-danger"><?= __('inactive') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= formatDate($user['created_at']) ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?= SITE_URL ?>/admin/user_management.php?action=view&id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= SITE_URL ?>/admin/user_management.php?action=edit&id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] !== getCurrentUser()): ?>
                                        <a href="<?= SITE_URL ?>/admin/user_management.php?action=delete&id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?= __('confirm_delete_user') ?>')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= buildPaginationUrl($page - 1) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildPaginationUrl($i) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= buildPaginationUrl($page + 1) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Display user view
 */
function displayUserView() {
   global $user, $userActivity, $loginHistory, $currentUser;

    ?>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-4">
                <!-- User Profile Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="avatar-circle-large bg-<?= getAvatarColor($user['user_type']) ?> text-white mx-auto mb-3">
                            <?= getInitials($user['full_name']) ?>
                        </div>
                        <h4 class="mb-0"><?= htmlspecialchars($user['full_name']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                        
                        <div class="d-flex justify-content-center mb-3">
                            <span class="badge bg-<?= getRoleBadgeClass($user['user_type']) ?> me-2">
                                <?= __(htmlspecialchars($user['user_type'])) ?>
                            </span>
                            <?php if ($user['active']): ?>
                            <span class="badge bg-success"><?= __('active') ?></span>
                            <?php else: ?>
                            <span class="badge bg-danger"><?= __('inactive') ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid gap-2 mt-3">
                            <a href="<?= SITE_URL ?>/admin/user_management.php?action=edit&id=<?= $user['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-edit me-1"></i> <?= __('edit_user') ?>
                            </a>
                            <?php if ($user['id'] !== $currentUser['id']): ?>
                            <a href="<?= SITE_URL ?>/admin/user_management.php?action=delete&id=<?= $user['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('<?= __('confirm_delete_user') ?>')">
                                <i class="fas fa-trash me-1"></i> <?= __('delete_user') ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="row text-center">
                            <div class="col-4 border-end">
                                <div class="h5 mb-0"><?= formatDate($user['created_at'], 'd M Y') ?></div>
                                <small class="text-muted"><?= __('joined') ?></small>
                            </div>
                            <div class="col-4 border-end">
                                <div class="h5 mb-0"><?= $user['last_login'] ? formatDate($user['last_login'], 'd M Y') : '-' ?></div>
                                <small class="text-muted"><?= __('last_login') ?></small>
                            </div>
                            <div class="col-4">
                                <div class="h5 mb-0"><?= count($userActivity) ?></div>
                                <small class="text-muted"><?= __('activities') ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Information Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><?= __('user_information') ?></h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach (getUserInfoFields() as $field => $label): ?>
                            <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                                <span class="text-muted"><?= $label ?></span>
                                <span class="fw-bold"><?= formatUserField($user, $field) ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <!-- Login History Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><?= __('login_history') ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($loginHistory)): ?>
                        <div class="p-3 text-center">
                            <p class="text-muted mb-0"><?= __('no_login_history') ?></p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= __('date') ?></th>
                                        <th><?= __('ip_address') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loginHistory as $login): ?>
                                    <tr>
                                        <td><?= formatDate($login['created_at']) ?></td>
                                        <td><?= $login['ip_address'] ?: '-' ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- User Activity Card -->
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><?= __('user_activity') ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($userActivity)): ?>
                        <div class="p-4 text-center">
                            <i class="fas fa-history text-muted" style="font-size: 3rem;"></i>
                            <p class="mt-3"><?= __('no_activity_found') ?></p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= __('action') ?></th>
                                        <th><?= __('details') ?></th>
                                        <th><?= __('ip_address') ?></th>
                                        <th><?= __('date') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userActivity as $activity): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?= getActivityBadgeClass($activity['action']) ?>">
                                                <?= __(str_replace('_', ' ', $activity['action'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= formatActivityDetails($activity['details']) ?>
                                        </td>
                                        <td><?= $activity['ip_address'] ?: '-' ?></td>
                                        <td><?= formatDate($activity['created_at']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Display edit user form
 */
function displayEditForm() {
    global $user;
    ?>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-lg-8 col-md-10 mx-auto">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><?= __('edit_user') ?>: <?= htmlspecialchars($user['full_name']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= SITE_URL ?>/admin/user_management.php?action=edit&id=<?= $user['id'] ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label"><?= __('full_name') ?> *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label"><?= __('email') ?> *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label"><?= __('phone') ?></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="user_type" class="form-label"><?= __('role') ?> *</label>
                                    <select class="form-select" id="user_type" name="user_type" required>
                                        <?php foreach (getAllowedRoles() as $roleValue => $roleLabel): ?>
                                            <option value="<?= $roleValue ?>" <?= $user['user_type'] === $roleValue ? 'selected' : '' ?>>
                                                <?= $roleLabel ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label"><?= __('new_password') ?></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password">
                                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted"><?= __('leave_blank_to_keep_current') ?></small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="active" class="form-label"><?= __('status') ?></label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" <?= $user['active'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="active">
                                            <?= __('account_active') ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-end mt-3">
                                <a href="<?= SITE_URL ?>/admin/user_management.php?action=view&id=<?= $user['id'] ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> <?= __('cancel') ?>
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> <?= __('save_changes') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Display create user form
 */
function displayCreateForm() {
    ?>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-lg-8 col-md-10 mx-auto">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><?= __('add_new_user') ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?= SITE_URL ?>/admin/user_management.php?action=create">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label"><?= __('full_name') ?> *</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label"><?= __('email') ?> *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label"><?= __('password') ?> *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted"><?= __('password_requirements') ?></small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="user_type" class="form-label"><?= __('role') ?> *</label>
                                    <select class="form-select" id="user_type" name="user_type" required>
                                        <?php foreach (getAllowedRoles() as $roleValue => $roleLabel): ?>
                                            <option value="<?= $roleValue ?>"><?= $roleLabel ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label"><?= __('phone') ?></label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="nationality" class="form-label"><?= __('nationality') ?></label>
                                    <select class="form-select" id="nationality" name="nationality">
                                        <option value=""><?= __('select_nationality') ?></option>
                                        <?php foreach (getNationalities() as $nationality): ?>
                                            <option value="<?= $nationality ?>"><?= $nationality ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="text-end mt-3">
                                <a href="<?= SITE_URL ?>/admin/user_management.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> <?= __('cancel') ?>
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-1"></i> <?= __('create_user') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Display delete confirmation
 */
function displayDeleteConfirmation() {

    global $user;
    ?>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> <?= __('confirm_deletion') ?></h5>
                    </div>
                    <div class="card-body text-center">
                        <i class="fas fa-user-slash text-danger mb-3" style="font-size: 4rem;"></i>
                        <h4 class="mb-3"><?= __('delete_user_confirmation') ?></h4>
                        <p class="mb-1"><strong><?= __('full_name') ?>:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
                        <p class="mb-1"><strong><?= __('email') ?>:</strong> <?= htmlspecialchars($user['email']) ?></p>
                        <p class="mb-4"><strong><?= __('user_type') ?>:</strong> <?= __(htmlspecialchars($user['user_type'])) ?></p>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle me-2"></i> <?= __('delete_user_warning') ?>
                        </div>
                        
                        <div class="mt-4">
                            <a href="<?= SITE_URL ?>/admin/user_management.php?action=view&id=<?= $user['id'] ?>" class="btn btn-outline-secondary me-2">
                                <i class="fas fa-arrow-left me-1"></i> <?= __('cancel') ?>
                            </a>
                            <a href="<?= SITE_URL ?>/admin/user_management.php?action=delete&id=<?= $user['id'] ?>&confirm=yes" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i> <?= __('confirm_delete') ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>