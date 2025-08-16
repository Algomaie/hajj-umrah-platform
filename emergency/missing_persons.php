<?php
// Start session and include required files
session_start();
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Set page title and include header
$pageTitle = __('missing_persons');
$useLeaflet = true; // For maps
include_once('../includes/header.php');

// Define actions and validate input
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleMissingPersonFormSubmission($currentUser);
}

// Main content based on action
switch ($action) {
    case 'view':
        displayMissingPersonDetails($id, $currentUser);
        break;
    case 'mark_found':
        displayMarkFoundForm($id, $currentUser);
        break;
    case 'new':
        displayNewReportForm($currentUser);
        break;
    case 'map':
        displayMissingPersonsMap();
        break;
    case 'search':
    case 'list':
    default:
        displayMissingPersonsList($action);
}

// Include footer
include_once('../includes/footer.php');

// ==================== FUNCTIONS ====================

/**
 * Handles form submissions for missing persons
 */
function handleMissingPersonFormSubmission($currentUser) {
    if (!isset($_POST['action'])) {
        return;
    }

    try {
        switch ($_POST['action']) {
            case 'create':
                createMissingPersonReport($currentUser);
                break;
            case 'mark_found':
                markPersonAsFound($currentUser);
                break;
            case 'delete':
                deleteMissingPersonReport($currentUser);
                break;
        }
    } catch (Exception $e) {
        setFlashMessage('danger', $e->getMessage());
    }
}

/**
 * Creates a new missing person report
 */
function createMissingPersonReport($currentUser) {
    // Validate required fields
    $required = ['name', 'last_seen_location', 'last_seen_time'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception(__('required_field_missing'));
        }
    }

    // Prepare data
    $emergency_id = isset($_POST['emergency_id']) ? (int)$_POST['emergency_id'] : 0;
    $name = sanitizeInput($_POST['name']);
    $age = !empty($_POST['age']) ? (int)$_POST['age'] : null;
    $gender = !empty($_POST['gender']) ? sanitizeInput($_POST['gender']) : null;
    $last_seen_location = sanitizeInput($_POST['last_seen_location']);
    $last_seen_time = sanitizeInput($_POST['last_seen_time']);
    $additional_info = !empty($_POST['additional_info']) ? sanitizeInput($_POST['additional_info']) : null;

    // Handle photo upload
    $photo = handlePhotoUpload();

    // Insert into database
    $sql = "INSERT INTO missing_persons SET
            emergency_id = ?,
            name = ?,
            age = ?,
            gender = ?,
            photo = ?,
            last_seen_location = ?,
            last_seen_time = ?,
            additional_info = ?,
            created_at = NOW()";

    $result = executeQuery($sql, [
        $emergency_id, $name, $age, $gender, $photo, 
        $last_seen_location, $last_seen_time, $additional_info
    ]);

    if (!$result) {
        throw new Exception(__('report_error'));
    }

    // Update emergency status if linked
    if ($emergency_id > 0) {
        updateEmergencyStatus($emergency_id, 'in_progress');
    }

    // Log activity
    logActivity($currentUser['id'], 'missing_person_report', "Reported missing person: $name");

    setFlashMessage('success', __('missing_person_reported'));
    redirect(SITE_URL . '/emergency/missing_persons.php');
}

/**
 * Marks a missing person as found
 */
function markPersonAsFound($currentUser) {
    $required = ['missing_id', 'found_location', 'found_time'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception(__('required_field_missing'));
        }
    }

    $missing_id = (int)$_POST['missing_id'];
    $found_location = sanitizeInput($_POST['found_location']);
    $found_time = sanitizeInput($_POST['found_time']);
    $additional_info = !empty($_POST['additional_info']) ? sanitizeInput($_POST['additional_info']) : null;

    // Get current info
    $person = getMissingPerson($missing_id);
    if (!$person) {
        throw new Exception(__('record_not_found'));
        echo "ssssssssssssssssss";
    }

    // Update record
    $sql = "UPDATE missing_persons SET
            found_location = ?,
            found_time = ?,
            additional_info = CONCAT(IFNULL(additional_info, ''), '\n', ?),
            updated_at = NOW()
            WHERE id = ?";

    $result = executeQuery($sql, [
        $found_location, $found_time, $additional_info, $missing_id
    ]);

    if (!$result) {
        throw new Exception(__('update_error'));
    }

    // Update emergency status if all persons found
    if ($person['emergency_id']) {
        checkAllPersonsFound($person['emergency_id']);
    }

    // Log activity
    logActivity($currentUser['id'], 'missing_person_found', "Marked {$person['name']} as found");

    setFlashMessage('success', __('person_marked_found'));
    redirect(SITE_URL . '/emergency/missing_persons.php?action=view&id='.$missing_id);
}

/**
 * Deletes a missing person report
 */
function deleteMissingPersonReport($currentUser) {
    if ($currentUser['user_type'] !== 'admin') {
        throw new Exception(__('permission_denied'));
    }

    $missing_id = (int)$_POST['missing_id'];
    $person = getMissingPerson($missing_id);

    if (!$person) {
        throw new Exception(__('record_not_found'));
    }

    // Delete photo if exists
    if ($person['photo']) {
        $photo_path = SITE_URL . '/uploads/missing_persons/' . $person['photo'];
        if (file_exists($photo_path)) {
            unlink($photo_path);
        }
    }

    // Delete record
    $sql = "DELETE FROM missing_persons WHERE id = ?";
    $result = executeQuery($sql, [$missing_id]);

    if (!$result) {
        throw new Exception(__('delete_error'));
    }

    // Log activity
    logActivity($currentUser['id'], 'missing_person_deleted', "Deleted report #$missing_id");

    setFlashMessage('success', __('report_deleted'));
    redirect(SITE_URL . '/emergency/missing_persons.php');
}

/**
 * Handles photo upload and returns filename
 */
function handlePhotoUpload() {
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $upload_dir = SITE_URL . '/uploads/missing_persons/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected_type = finfo_file($finfo, $_FILES['photo']['tmp_name']);
    finfo_close($finfo);

    if (!in_array($detected_type, $allowed_types)) {
        throw new Exception(__('invalid_file_type'));
    }

    // Generate unique filename
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $destination = $upload_dir . $filename;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
        throw new Exception(__('photo_upload_failed'));
    }

    return $filename;
}

/**
 * Displays the main missing persons list
 */
/**
 * عرض قائمة الأشخاص المفقودين مع إمكانية البحث
 * 
 * @param string $action نوع الإجراء (list/search)
 */
function displayMissingPersonsList($action) {
    global $currentUser;
    
    // إعداد معاملات البحث
    $where = '';
    $params = [];
    $query = '';
    
    // معالجة البحث إذا كان الطلب بحثاً
    if ($action === 'search' && !empty($_GET['q'])) {
        $query = trim($_GET['q']);
        $searchTerm = '%' . sanitizeInput($query) . '%';
        $where = "WHERE (mp.name LIKE ? OR mp.additional_info LIKE ?)";
        $params = [$searchTerm, $searchTerm];
    }
    
    // استعلام SQL مع الحماية من الحقن
    $sql = "SELECT mp.*, e.reporter_id, u.full_name as reporter_name,
    e.status as emergency_status,
                u.user_type as reporter_type,
                e.created_at
    
FROM missing_persons mp
LEFT JOIN emergencies e ON mp.emergency_id = e.emergency_id  
LEFT JOIN users u ON e.reporter_id = u.id"; // حد أقصى للنتائج
    
      
    
    // جلب البيانات
    $missingPersons = fetchAll($sql, $params);
    
    // تسجيل للأغراض الأمنية (في بيئة التطوير فقط)
    // if (ENVIRONMENT === 'development') {
    //     error_log("Missing Persons Query: " . $sql);
    //     error_log("Query Parameters: " . print_r($params, true));
    // }
    ?>
    

    <div class="container-fluid py-4">
        <!-- Page header with actions -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fas fa-user-slash me-2"></i> <?= __('missing_persons') ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?php if (hasPermission($currentUser, 'create_report')): ?>
                <a href="?action=new" class="btn btn-sm btn-outline-primary me-2">
                    <i class="fas fa-plus me-1"></i> <?= __('report_missing') ?>
                </a>
                <?php endif; ?>
                <a href="?action=map" class="btn btn-sm btn-outline-info me-2">
                    <i class="fas fa-map-marked-alt me-1"></i> <?= __('view_map') ?>
                </a>
                <form class="d-flex" method="GET">
                    <input type="hidden" name="action" value="search">
                    <input type="text" name="q" class="form-control form-control-sm" 
                           placeholder="<?= __('search_name') ?>" 
                           value="<?= !empty($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary ms-2">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
        
        <?php getFlashMessage(); ?>
        
        <!-- Main table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="60"><?= __('photo') ?></th>
                                <th><?= __('name') ?></th>
                                <th><?= __('age_gender') ?></th>
                                <th><?= __('last_seen') ?></th>
                                <th><?= __('status') ?></th>
                                <th><?= __('reported_by') ?></th>
                                <th width="120"><?= __('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($missingPersons)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4"><?= __('no_missing_reports') ?></td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($missingPersons as $person): ?>
                                <tr>
                                    <td><?= renderPersonPhoto($person) ?></td>
                                    <td>
                                        <a href="?action=view&id=<?= $person['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($person['name']) ?>
                                        </a>
                                    </td>
                                    <td><?= renderAgeGender($person) ?></td>
                                    <td><?= renderLastSeenInfo($person) ?></td>
                                    <td><?= renderStatusBadge($person) ?></td>
                                    <td><?= renderReporterInfo($person) ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?= renderActionButtons($person, $currentUser) ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete confirmation modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= __('confirm_delete') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><?= __('delete_report_confirm') ?> <strong id="deleteName"></strong>?</p>
                    <p class="text-danger"><?= __('action_irreversible') ?></p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="missing_id" id="deleteId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                        <button type="submit" class="btn btn-danger"><?= __('delete') ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function confirmDelete(id, name) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteName').textContent = name;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    </script>
    <?php
}

/**
 * Displays details for a single missing person
 */
function displayMissingPersonDetails($id, $currentUser) {
    $person = getMissingPersonWithEmergency($id);
    if (!$person) {
        setFlashMessage('danger', __('record_not_found'));
        redirect(SITE_URL . '/emergency/missing_persons.php');
        return;
    }
    ?>
    
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Left column - Person details -->
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><?= __('person_details') ?></h5>
                    </div>
                    <div class="card-body text-center">
                        <?= renderPersonPhoto($person, '200px') ?>
                        <h4 class="mt-3"><?= htmlspecialchars($person['name']) ?></h4>
                        <?= renderStatusBadge($person, true) ?>
                        
                        <ul class="list-group list-group-flush mt-3">
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted"><?= __('age') ?>:</span>
                                <span><?= $person['age'] ? $person['age'] . ' ' . __('years') : __('unknown') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted"><?= __('gender') ?>:</span>
                                <span><?= $person['gender'] ? __($person['gender']) : __('unknown') ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted"><?= __('reported_on') ?>:</span>
                                <span><?= formatDate($person['created_at']) ?></span>
                            </li>
                            <?php if ($person['emergency_id']): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted"><?= __('emergency_status') ?>:</span>
                                <span class="badge bg-<?= getStatusBadgeClass($person['emergency_status']) ?>">
                                    <?= __($person['emergency_status']) ?>
                                </span>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <div class="d-grid gap-2">
                            <a href="?action=list" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> <?= __('back_to_list') ?>
                            </a>
                            <?php if (!$person['found_time'] && hasPermission($currentUser, 'mark_found')): ?>
                            <a href="?action=mark_found&id=<?= $person['id'] ?>" class="btn btn-success">
                                <i class="fas fa-check me-1"></i> <?= __('mark_as_found') ?>
                            </a>
                            <?php endif; ?>
                            <?php if (hasPermission($currentUser, 'delete_report')): ?>
                            <button type="button" class="btn btn-danger" 
                                    onclick="confirmDelete(<?= $person['id'] ?>, '<?= htmlspecialchars($person['name']) ?>')">
                                <i class="fas fa-trash-alt me-1"></i> <?= __('delete_report') ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right column - Details -->
            <div class="col-md-8 mb-4">
                <div class="row">
                    <!-- Last Seen Information -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><?= __('last_seen_information') ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <h6><?= __('last_seen_time') ?></h6>
                                        <p><?= $person['last_seen_time'] ? formatDate($person['last_seen_time']) : __('information_not_available') ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <h6><?= __('last_seen_location') ?></h6>
                                        <p><?= $person['last_seen_location'] ? htmlspecialchars($person['last_seen_location']) : __('information_not_available') ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($person['last_seen_location']): ?>
                                <div id="lastSeenMap" style="height: 200px;"></div>
                                <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    initMap('lastSeenMap', '<?= htmlspecialchars($person['last_seen_location']) ?>');
                                });
                                </script>
                                <?php endif; ?>
                                
                                <?php if ($person['found_time']): ?>
                                <div class="alert alert-success mt-3">
                                    <div class="d-flex">
                                        <div class="me-3">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                        <div>
                                            <h6><?= __('person_found') ?></h6>
                                            <p class="mb-1"><?= __('found_at') ?>: <?= htmlspecialchars($person['found_location']) ?></p>
                                            <p class="mb-0"><?= __('found_time') ?>: <?= formatDate($person['found_time']) ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($person['found_location']): ?>
                                <div id="foundLocationMap" style="height: 200px;" class="mt-3"></div>
                                <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    initMap('foundLocationMap', '<?= htmlspecialchars($person['found_location']) ?>', true);
                                });
                                </script>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Information -->
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><?= __('additional_information') ?></h5>
                            </div>
                            <div class="card-body">
                                <?= $person['additional_info'] ? nl2br(htmlspecialchars($person['additional_info'])) : '<p class="text-muted">'.__('no_additional_info').'</p>' ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reporter Information -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><?= __('reporter_information') ?></h5>
                            </div>
                            <div class="card-body">
                                <?php if ($person['reporter_name']): ?>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <h6><?= __('reporter_name') ?></h6>
                                        <p><?= htmlspecialchars($person['reporter_name']) ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <h6><?= __('reporter_type') ?></h6>
                                        <span class="badge bg-<?= getRoleBadgeClass($person['reporter_type']) ?>">
                                            <?= __($person['reporter_type']) ?>
                                        </span>
                                    </div>
                                    <?php if ($person['reporter_phone'] && hasPermission($currentUser, 'view_contact')): ?>
                                    <div class="col-md-6 mb-3">
                                        <h6><?= __('contact_number') ?></h6>
                                        <p><a href="tel:<?= $person['reporter_phone'] ?>"><?= $person['reporter_phone'] ?></a></p>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-md-6 mb-3">
                                        <h6><?= __('reported_at') ?></h6>
                                        <p><?= formatDate($person['reported_at'] ?? $person['created_at']) ?></p>
                                    </div>
                                </div>
                                <?php else: ?>
                                <p class="text-muted"><?= __('reporter_info_not_available') ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Displays the form to mark a person as found
 */
function displayMarkFoundForm($id, $currentUser) {
    $person = getMissingPerson($id);
    if (!$person || $person['found_time']) {
        setFlashMessage('danger', __('record_not_found_or_found'));
        redirect(SITE_URL . '/emergency/missing_persons.php');
        return;
    }
    ?>
    
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <?= __('mark_person_found') ?>: <?= htmlspecialchars($person['name']) ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> <?= __('mark_found_info') ?>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="mark_found">
                            <input type="hidden" name="missing_id" value="<?= $id ?>">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="found_location" class="form-label required"><?= __('found_location') ?></label>
                                    <input type="text" class="form-control" id="found_location" name="found_location" required>
                                    <div class="form-text"><?= __('found_location_help') ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="found_time" class="form-label required"><?= __('found_time') ?></label>
                                    <input type="datetime-local" class="form-control" id="found_time" name="found_time" 
                                           value="<?= date('Y-m-d\TH:i') ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="additional_info" class="form-label"><?= __('additional_info') ?></label>
                                <textarea class="form-control" id="additional_info" name="additional_info" rows="4"></textarea>
                                <div class="form-text"><?= __('found_additional_info_help') ?></div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="?action=view&id=<?= $id ?>" class="btn btn-secondary"><?= __('cancel') ?></a>
                                <button type="submit" class="btn btn-success"><?= __('confirm_found') ?></button>
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
 * Displays the form to create a new missing person report
 */
function displayNewReportForm($currentUser) {
    if (!hasPermission($currentUser, 'create_report')) {
        setFlashMessage('danger', __('permission_denied'));
        redirect(SITE_URL . '/emergency/missing_persons.php');
        return;
    }
    
    // Get emergency_id from URL if provided
    $emergency_id = isset($_GET['emergency_id']) ? (int)$_GET['emergency_id'] : 0;
    $emergency = null;
    
    if ($emergency_id > 0) {
        $emergency = getEmergency($emergency_id);
    }
    ?>
    
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><?= __('report_missing_person') ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="create">
                            
                            <?php if ($emergency): ?>
                            <input type="hidden" name="emergency_id" value="<?= $emergency_id ?>">
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-link me-2"></i> <?= __('linked_to_emergency') ?> #<?= $emergency_id ?>
                            </div>
                            <?php else: ?>
                            <div class="mb-3">
                                <label for="emergency_id" class="form-label"><?= __('link_to_emergency') ?></label>
                                <select class="form-select" id="emergency_id" name="emergency_id">
                                    <option value="0"><?= __('none') ?></option>
                                    <?php foreach (getRecentEmergencies() as $e): ?>
                                    <option value="<?= $e['id'] ?>">
                                        <?= __('emergency') ?> #<?= $e['id'] ?> (<?= formatDate($e['created_at']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text"><?= __('emergency_link_help') ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="name" class="form-label required"><?= __('full_name') ?></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="age" class="form-label"><?= __('age') ?></label>
                                    <input type="number" class="form-control" id="age" name="age" min="1" max="120">
                                </div>
                                <div class="col-md-6">
                                    <label for="gender" class="form-label"><?= __('gender') ?></label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value=""><?= __('not_specified') ?></option>
                                        <option value="male"><?= __('male') ?></option>
                                        <option value="female"><?= __('female') ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="photo" class="form-label"><?= __('photo') ?></label>
                                <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                                <div class="form-text"><?= __('photo_help') ?></div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="last_seen_location" class="form-label required"><?= __('last_seen_location') ?></label>
                                    <input type="text" class="form-control" id="last_seen_location" name="last_seen_location" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="last_seen_time" class="form-label required"><?= __('last_seen_time') ?></label>
                                    <input type="datetime-local" class="form-control" id="last_seen_time" name="last_seen_time" 
                                           value="<?= date('Y-m-d\TH:i') ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="additional_info" class="form-label"><?= __('additional_info') ?></label>
                                <textarea class="form-control" id="additional_info" name="additional_info" rows="4"></textarea>
                                <div class="form-text"><?= __('additional_info_help') ?></div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <a href="?action=list" class="btn btn-secondary"><?= __('cancel') ?></a>
                                <button type="submit" class="btn btn-primary"><?= __('report_missing') ?></button>
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
 * Displays a map of all missing persons
 */
function displayMissingPersonsMap() {
    $missingPersons = getAllMissingPersons();
    ?>
    
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= __('missing_persons_map') ?></h5>
            </div>
            <div class="card-body">
                <div id="missingPersonsMap" style="height: 600px;"></div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize map centered on Kaaba
        const map = L.map('missingPersonsMap').setView([21.4225, 39.8262], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        // Add Kaaba marker
        L.marker([21.4225, 39.8262], {
            icon: L.divIcon({
                html: '<i class="fas fa-kaaba fa-2x" style="color:#21618C;"></i>',
                iconSize: [30, 30],
                className: 'kaaba-marker'
            })
        }).addTo(map).bindPopup("<?= __('kaaba') ?>");
        
        // Add markers for missing persons
        <?php foreach ($missingPersons as $person): ?>
        <?php
        // Generate random coordinates around Mecca for demo purposes
        $lat = 21.4225 + (rand(-100, 100) / 1000);
        $lng = 39.8262 + (rand(-100, 100) / 1000);
        ?>
        
        L.marker([<?= $lat ?>, <?= $lng ?>], {
            icon: L.divIcon({
                html: '<i class="fas fa-<?= $person['found_time'] ? 'check-circle' : 'user-slash' ?>" style="color:<?= $person['found_time'] ? 'green' : 'red' ?>;"></i>',
                iconSize: [20, 20],
                className: 'person-marker'
            })
        }).addTo(map).bindPopup(`
            <div class="text-center">
                <?php if ($person['photo']): ?>
                <img src="<?= SITE_URL ?>/uploads/missing_persons/<?= $person['photo'] ?>" 
                     style="max-width:100px;max-height:100px;margin-bottom:10px;">
                <?php endif; ?>
                <h6><?= htmlspecialchars($person['name']) ?></h6>
                <p class="mb-1">
                    <span class="badge bg-<?= $person['found_time'] ? 'success' : 'danger' ?>">
                        <?= $person['found_time'] ? __('found') : __('missing') ?>
                    </span>
                </p>
                <a href="?action=view&id=<?= $person['id'] ?>" class="btn btn-sm btn-primary mt-2">
                    <?= __('view_details') ?>
                </a>
            </div>
        `);
        <?php endforeach; ?>
    });
    </script>
    
    <style>
    .kaaba-marker {
        background: transparent;
        border: none;
    }
    .person-marker {
        background: transparent;
        border: none;
    }
    </style>
    <?php
}

/**
 * Helper function to get a missing person record
 */
function getMissingPerson($id) {
    $sql = "SELECT * FROM missing_persons WHERE id = ?";
    return fetchRow($sql, [$id]);
}

/**
 * Helper function to get a missing person with emergency details
 */
function getMissingPersonWithEmergency($id) {
    $sql = "SELECT mp.*, e.reporter_id, e.type as emergency_type, e.status as emergency_status,
            e.created_at as reported_at, u.full_name as reporter_name, 
            u.phone as reporter_phone, u.user_type as reporter_type
            FROM missing_persons mp
            LEFT JOIN emergencies e ON mp.emergency_id = e.id
            LEFT JOIN users u ON e.reporter_id = u.id
            WHERE mp.id = ?";
    return fetchRow($sql, [$id]);
}

/**
 * Helper function to get recent emergencies
 */
function getRecentEmergencies() {
    $sql = "SELECT id, created_at FROM emergencies 
            WHERE type = 'missing_person' AND status != 'resolved' 
            ORDER BY created_at DESC LIMIT 20";
    return fetchAll($sql);
}

/**
 * Helper function to get all missing persons for map
 */
function getAllMissingPersons() {
    $sql = "SELECT id, name, photo, last_seen_location, last_seen_time, found_time, found_location 
            FROM missing_persons";
    return fetchAll($sql);
}

/**
 * Helper function to update emergency status
 */
function updateEmergencyStatus($emergency_id, $status) {
    $sql = "UPDATE emergencies SET status = ?, updated_at = NOW() WHERE id = ?";
    return executeQuery($sql, [$status, $emergency_id]);
}

/**
 * Checks if all missing persons in an emergency are found and updates status
 */
function checkAllPersonsFound($emergency_id) {
    $sql = "SELECT COUNT(*) as total, 
            SUM(CASE WHEN found_time IS NOT NULL THEN 1 ELSE 0 END) as found 
            FROM missing_persons 
            WHERE emergency_id = ?";
    $stats = fetchRow($sql, [$emergency_id]);
    
    if ($stats['total'] == $stats['found']) {
        updateEmergencyStatus($emergency_id, 'resolved');
    }
}

/**
 * Helper function to check user permissions
 */
function hasPermission($user, $action) {
    switch ($action) {
        case 'create_report':
        case 'mark_found':
            return in_array($user['user_type'], ['admin', 'authority']);
        case 'delete_report':
            return $user['user_type'] === 'admin';
        case 'view_contact':
            return in_array($user['user_type'], ['admin', 'authority']);
        default:
            return false;
    }
}

/**
 * Helper function to render person photo
 */
function renderPersonPhoto($person, $size = '50px') {
    // echo $person['photo'];
    if ($person['photo']) {
        return '<img src="' . $person['photo'] . '"
               alt="' . htmlspecialchars($person['name']) . '"
               class="img-thumbnail" style="width:' . $size . ';height:' . $size . ';object-fit:cover;">';
    }
    return '<div class="text-center bg-light rounded" style="width:'.$size.';height:'.$size.';line-height:'.$size.';">
            <i class="fas fa-user text-muted"></i>
        </div>';
}

/**
 * Helper function to render age and gender
 */
function renderAgeGender($person) {
    $age = $person['age'] ? $person['age'] . ' ' . __('years') : __('unknown');
    $gender = $person['gender'] ? __($person['gender']) : __('unknown');
    return $age . ' - ' . $gender;
}

/**
 * Helper function to render last seen info
 */
function renderLastSeenInfo($person) {
    $html = '';
    if ($person['last_seen_location']) {
        $html .= '<div>'.htmlspecialchars($person['last_seen_location']).'</div>';
    }
    $html .= '<div class="small text-muted">';
    $html .= $person['last_seen_time'] ? formatDate($person['last_seen_time']) : __('unknown');
    $html .= '</div>';
    return $html;
}

/**
 * Helper function to render status badge
 */
function renderStatusBadge($person, $large = false) {
    $class = $person['found_time'] ? 'success' : 'danger';
    $text = $person['found_time'] ? __('found') : __('missing');
    $size = $large ? 'p-2' : '';
    
    $html = '<span class="badge bg-'.$class.' '.$size.'">'.$text.'</span>';
    
    if ($large) {
        return $html;
    }
    
    $html .= '<div class="small text-muted">';
    if ($person['found_time']) {
        $html .= formatDate($person['found_time']);
    } else {
        $html .= timeSince($person['created_at']);
    }
    $html .= '</div>';
    
    return $html;
}

/**
 * Helper function to render reporter info
 */
function renderReporterInfo($person) {
    if (!$person['reporter_name']) {
        return '<span class="text-muted small">'.__('not_available').'</span>';
    }
    
    $html = '<span class="small">'.htmlspecialchars($person['reporter_name']).'</span>
            <div>
                <span class="badge bg-'.getRoleBadgeClass($person['reporter_type']).'">
                    '.__($person['reporter_type']).'
                </span>
            </div>';
    
    return $html;
}

/**
 * Helper function to render action buttons
 */
function renderActionButtons($person, $currentUser) {
    $html = '<a href="?action=view&id='.$person['id'].'" class="btn btn-outline-primary" title="'.__('view').'">
                <i class="fas fa-eye"></i>
            </a>';
    
    if (!$person['found_time'] && hasPermission($currentUser, 'mark_found')) {
        $html .= '<a href="?action=mark_found&id='.$person['id'].'" class="btn btn-outline-success" title="'.__('mark_found').'">
                    <i class="fas fa-check"></i>
                </a>';
    }
    
    if (hasPermission($currentUser, 'delete_report')) {
        $html .= '<button type="button" class="btn btn-outline-danger" 
                    onclick="confirmDelete('.$person['id'].', \''.htmlspecialchars($person['name']).'\')"
                    title="'.__('delete').'">
                    <i class="fas fa-trash-alt"></i>
                </button>';
    }
    
    return $html;
}

/**
 * Helper function to get badge class for roles
 */
// function getRoleBadgeClass($role) {
//     switch ($role) {
//         case 'admin': return 'danger';
//         case 'authority': return 'warning';
//         case 'guardian': return 'success';
//         case 'pilgrim': return 'primary';
//         default: return 'secondary';
//     }
// }

/**
 * Helper function to get badge class for statuses
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'requested': return 'danger';
        case 'in_progress': return 'warning';
        case 'resolved': return 'success';
        case 'cancelled': return 'secondary';
        default: return 'primary';
    }
}