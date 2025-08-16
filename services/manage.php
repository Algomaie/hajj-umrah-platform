<?php
require_once '../includes/auth_check.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check role
if (!in_array($currentUser['user_type'], ['group_leader', 'admin'])) {
    setFlashMessage('error', __('access_denied'));
    redirect(SITE_URL . '/index.php');
    exit;
}

// Set page title
$pageTitle = __('manage_groups');
$useLeaflet = true;

require_once '../includes/header.php';
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug: Log POST data
        error_log(print_r($_POST, true));

        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception(__('invalid_csrf_token'));
        }

        $action = $_POST['action'] ?? '';
        error_log("Action: $action");

        beginTransaction();

        if ($action === 'create_group') {
            $groupName = trim($_POST['group_name'] ?? '');
            error_log("Group Name: $groupName");

            if (empty($groupName)) {
                throw new Exception(__('group_name_required'));
            }
            if (strlen($groupName) > 100) {
                throw new Exception(__('group_name_too_long'));
            }

            $inviteCode = generateInviteCode();
            $groupId = generateUUID();
            error_log("Invite Code: $inviteCode, Group ID: $groupId");

            $sql = "INSERT INTO groups (id, name, invite_code, creator_id, active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 1, NOW(), NOW())";
            $params = [$groupId, sanitizeInput($groupName), $inviteCode, $currentUser['id']];
            error_log("SQL: $sql, Params: " . print_r($params, true));

            $result = insertData($sql, $params);
            error_log("Insert Result: $result");

            logActivity($currentUser['id'], 'create_group', ['group_id' => $groupId]);
            setFlashMessage('success', __('group_created_success'));

            // Refresh CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            throw new Exception(__('invalid_action'));
        }

        commitTransaction();
        redirect($_SERVER['PHP_SELF']);
        exit;

    } catch (Exception $e) {
        rollbackTransaction();
        error_log("Error: " . $e->getMessage());
        setFlashMessage('error', $e->getMessage());
    }
}

// Fetch user's groups
$groups = fetchAll("
    SELECT g.*, COUNT(gm.user_id) as member_count
    FROM groups g
    LEFT JOIN group_members gm ON g.id = gm.group_id
    WHERE g.creator_id = ? AND g.active = 1
    GROUP BY g.id
    ORDER BY g.created_at DESC
", [$currentUser['id']]);

?>

<div class="container py-4">
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('close'); ?>"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <h1 class="h2 mb-4"><i class="fas fa-users me-2" aria-hidden="true"></i> <?php echo __('manage_groups'); ?></h1>

            <!-- Create Group Form -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-users me-2" aria-hidden="true"></i> <?php echo __('create_group'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="createGroupForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="action" value="create_group">
                        <div class="mb-3">
                            <label for="groupName" class="form-label"><?php echo __('group_name'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="groupName" name="group_name" required maxlength="100">
                            <div class="invalid-feedback"><?php echo __('group_name_required'); ?></div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-2" aria-hidden="true"></i> <?php echo __('create_group'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Groups List -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2" aria-hidden="true"></i> <?php echo __('your_groups'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($groups)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users text-muted mb-3" style="font-size: 3rem;" aria-hidden="true"></i>
                            <p class="text-muted"><?php echo __('no_groups_found'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($groups as $group): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <i class="fas fa-users me-1" aria-hidden="true"></i>
                                            <?php echo htmlspecialchars($group['name']); ?>
                                        </h6>
                                        <small class="badge bg-primary"><?php echo $group['member_count']; ?> <?php echo __('members'); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo __('invite_code'); ?>: <?php echo htmlspecialchars($group['invite_code']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1" aria-hidden="true"></i>
                                        <?php echo formatDate($group['created_at']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('createGroupForm');
    form.addEventListener('submit', event => {
        console.log('Form submitted:', new FormData(form));
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>

<style>
.list-group-item {
    transition: all 0.2s ease;
}
.list-group-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.alert-dismissible .btn-close {
    padding: 0.75rem;
}
.was-validated .form-control:invalid {
    border-color: var(--bs-danger);
}
</style>

<?php require_once '../includes/footer.php'; ?>