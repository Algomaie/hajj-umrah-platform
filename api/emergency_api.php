 
<?php
/**
 * Emergency API
 * 
 * Handles emergency reports and services
 */

// Include required files
require_once('../includes/functions.php');

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // For POST requests, return JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => __('login_required')
        ]);
    } else {
        // For GET requests, redirect to login
        redirect(SITE_URL . '/auth/login.php');
    }
    exit;
}

// Get current user
$currentUser = getCurrentUser();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Determine the emergency type from form
    $emergencyType = isset($_POST['emergency_type']) ? sanitizeInput($_POST['emergency_type']) : '';
    
    if (empty($emergencyType) || !in_array($emergencyType, ['medical', 'missing_person', 'security', 'other'])) {
        echo json_encode([
            'success' => false,
            'message' => __('invalid_emergency_type')
        ]);
        exit;
    }
    
    // General emergency report information
    $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';
    $includeLocation = isset($_POST['include_location']) && $_POST['include_location'] === '1';
    $latitude = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    $contactPhone = isset($_POST['contact_phone']) ? sanitizeInput($_POST['contact_phone']) : $currentUser['phone'];
    
    if (empty($description)) {
        echo json_encode([
            'success' => false,
            'message' => __('description_required')
        ]);
        exit;
    }
    
    // If location should be included but is missing
    if ($includeLocation && (is_null($latitude) || is_null($longitude))) {
        echo json_encode([
            'success' => false,
            'message' => __('location_required')
        ]);
        exit;
    }
    
    // Start transaction
    beginTransaction();
    
    try {
        // Insert emergency record
        $sql = "INSERT INTO emergencies (
                    reporter_id, type, description, latitude, longitude, 
                    contact_phone, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'requested', NOW(), NOW())";
        
        $emergencyId = insertData($sql, [
            $currentUser['id'],
            $emergencyType,
            $description,
            $includeLocation ? $latitude : null,
            $includeLocation ? $longitude : null,
            $contactPhone
        ]);
        
        if (!$emergencyId) {
            throw new Exception(__('emergency_creation_failed'));
        }
        
        // For missing person reports, add additional information
        if ($emergencyType === 'missing_person') {
            $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
            $age = isset($_POST['age']) ? (int)$_POST['age'] : null;
            $gender = isset($_POST['gender']) ? sanitizeInput($_POST['gender']) : null;
            $lastSeenLocation = isset($_POST['last_seen_location']) ? sanitizeInput($_POST['last_seen_location']) : '';
            $lastSeenTime = isset($_POST['last_seen_time']) ? sanitizeInput($_POST['last_seen_time']) : null;
            
            if (empty($name)) {
                throw new Exception(__('person_name_required'));
            }
            
            // Handle photo upload if provided
            $photoPath = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $photoPath = uploadFile($_FILES['photo'], 'missing_persons');
                
                if (!$photoPath) {
                    throw new Exception(__('photo_upload_failed'));
                }
            }
            
            // Insert missing person record
            $sql = "INSERT INTO missing_persons (
                        emergency_id, name, age, gender, photo, 
                        last_seen_location, last_seen_time
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $result = insertData($sql, [
                $emergencyId,
                $name,
                $age,
                $gender,
                $photoPath,
                $lastSeenLocation,
                $lastSeenTime
            ]);
            
            if (!$result) {
                throw new Exception(__('missing_person_creation_failed'));
            }
        }
        
        // Commit transaction
        commitTransaction();
        
        // Log activity
        logActivity($currentUser['id'], 'report_emergency', [
            'emergency_id' => $emergencyId,
            'type' => $emergencyType
        ]);
        
        // Create notification for authorities
        createEmergencyNotification($emergencyId, $emergencyType);
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => __('emergency_reported'),
            'emergency_id' => $emergencyId
        ]);
    } catch (Exception $e) {
        // Rollback transaction
        rollbackTransaction();
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
// Handle GET requests
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Get emergency details
    if ($action === 'get_emergency') {
        // Check if emergency ID is provided
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setFlashMessage('danger', __('emergency_id_required'));
            redirect(SITE_URL . '/dashboard/' . $currentUser['user_type'] . '.php');
            exit;
        }
        
        $emergencyId = (int)$_GET['id'];
        
        // Get emergency details
        $emergency = fetchRow("
            SELECT e.*, u.full_name as reporter_name, u.phone as reporter_phone
            FROM emergencies e
            JOIN users u ON e.reporter_id = u.id
            WHERE e.id = ?
        ", [$emergencyId]);
        
        if (!$emergency) {
            setFlashMessage('danger', __('emergency_not_found'));
            redirect(SITE_URL . '/dashboard/' . $currentUser['user_type'] . '.php');
            exit;
        }
        
        // Check permission
        if ($emergency['reporter_id'] !== $currentUser['id'] && 
            $currentUser['user_type'] !== 'authority' && 
            $currentUser['user_type'] !== 'admin') {
            setFlashMessage('danger', __('permission_denied'));
            redirect(SITE_URL . '/dashboard/' . $currentUser['user_type'] . '.php');
            exit;
        }
        
        // If missing person, get additional details
        if ($emergency['type'] === 'missing_person') {
            $missingPerson = fetchRow("
                SELECT * FROM missing_persons
                WHERE emergency_id = ?
            ", [$emergencyId]);
            
            $emergency['missing_person'] = $missingPerson;
        }
        
        // Set page title and include header
        $pageTitle = __('emergency_details');
        include_once('../includes/header.php');
        
        // Display emergency details
        include_once('../templates/emergency_details.php');
        
        // Include footer
        include_once('../includes/footer.php');
    }
    // List emergencies
    elseif ($action === 'list') {
        // Set page title and include header
        $pageTitle = __('emergencies');
        include_once('../includes/header.php');
        
        // Get filter parameters
        $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
        $type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // Build query based on user role
        $params = [];
        $whereConditions = [];
        
        if ($status) {
            $whereConditions[] = "e.status = ?";
            $params[] = $status;
        }
        
        if ($type) {
            $whereConditions[] = "e.type = ?";
            $params[] = $type;
        }
        
        if ($currentUser['user_type'] === 'pilgrim' || $currentUser['user_type'] === 'guardian') {
            // Regular users can only see their own reports
            $whereConditions[] = "e.reporter_id = ?";
            $params[] = $currentUser['id'];
        }
        
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = "WHERE " . implode(" AND ", $whereConditions);
        }
        
        // Count total emergencies
        $countSql = "SELECT COUNT(*) as total FROM emergencies e $whereClause";
        $totalResult = fetchRow($countSql, $params);
        $total = $totalResult ? $totalResult['total'] : 0;
        
        // Get emergencies
        $sql = "
            SELECT e.*, u.full_name as reporter_name
            FROM emergencies e
            JOIN users u ON e.reporter_id = u.id
            $whereClause
            ORDER BY e.created_at DESC
            LIMIT $offset, $perPage
        ";
        
        $emergencies = fetchAll($sql, $params);
        
        // Calculate pagination
        $totalPages = ceil($total / $perPage);
        
        // Display emergencies
        include_once('../templates/emergency_list.php');
        
        // Include footer
        include_once('../includes/footer.php');
    }
    // Update emergency status
    elseif ($action === 'update_status') {
        // Must be authority or admin
        if ($currentUser['user_type'] !== 'authority' && $currentUser['user_type'] !== 'admin') {
            setFlashMessage('danger', __('permission_denied'));
            redirect(SITE_URL . '/dashboard/' . $currentUser['user_type'] . '.php');
            exit;
        }
        
        // Check if emergency ID and status are provided
        if (!isset($_GET['id']) || empty($_GET['id']) || !isset($_GET['status']) || empty($_GET['status'])) {
            setFlashMessage('danger', __('missing_required_params'));
            redirect(SITE_URL . '../api/emergency_api.php?action=list');
            exit;
        }
        
        $emergencyId = (int)$_GET['id'];
        $status = sanitizeInput($_GET['status']);
        
        // Verify status is valid
        if (!in_array($status, ['requested', 'in_progress', 'resolved', 'cancelled'])) {
            setFlashMessage('danger', __('invalid_status'));
            redirect(SITE_URL . '/api/emergency_api.php?action=get_emergency&id=' . $emergencyId);
            exit;
        }
        
        // Update emergency status
        $sql = "UPDATE emergencies SET status = ?, handled_by = ?, updated_at = NOW() ";
        
        if ($status === 'resolved') {
            $sql .= ", resolved_at = NOW() ";
        }
        
        $sql .= "WHERE id = ?";
        
        $result = updateData($sql, [
            $status,
            $currentUser['id'],
            $emergencyId
        ]);
        
        if ($result) {
            // Log activity
            logActivity($currentUser['id'], 'update_emergency_status', [
                'emergency_id' => $emergencyId,
                'status' => $status
            ]);
            
            // Create notification for reporter
            $emergency = fetchRow("SELECT reporter_id FROM emergencies WHERE id = ?", [$emergencyId]);
            if ($emergency) {
                createNotification(
                    $emergency['reporter_id'],
                    'emergency_update',
                    __('emergency_status_updated'),
                    sprintf(__('emergency_status_updated_message'), __($status)),
                    ['emergency_id' => $emergencyId]
                );
            }
            
            setFlashMessage('success', __('status_updated'));
        } else {
            setFlashMessage('danger', __('status_update_failed'));
        }
        
        redirect(SITE_URL . '/api/emergency_api.php?action=get_emergency&id=' . $emergencyId);
    } else {
        // Invalid action, redirect to dashboard
        redirect(SITE_URL . '/dashboard/' . $currentUser['user_type'] . '.php');
    }
} else {
    // Unsupported method
    if (expectsJsonResponse()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => __('method_not_supported')
        ]);
    } else {
        setFlashMessage('danger', __('method_not_supported'));
        redirect(SITE_URL . '/dashboard/' . $currentUser['user_type'] . '.php');
    }
}

/**
 * Create emergency notification for authorities
 * 
 * @param int $emergencyId Emergency ID
 * @param string $emergencyType Emergency type
 * @return void
 */

/**
 * Create a notification for a user
 * 
 * @param int $userId User ID
 * @param string $type Notification type
 * @param string $title Notification title
 * @param string $message Notification message
 * @param array $data Additional data
 * @return bool True if successful, false otherwise
 */
function createNotification($userId, $type, $title, $message, $data = []) {
    $sql = "INSERT INTO notifications (user_id, type, title, message, data, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())";
    
    return insertData($sql, [
        $userId,
        $type,
        $title,
        $message,
        json_encode($data)
    ]) ? true : false;
}

/**
 * Check if request expects JSON response
 * 
 * @return bool True if JSON is expected, false otherwise
 */
function expectsJsonResponse() {
    return isset($_SERVER['HTTP_ACCEPT']) && 
           (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false ||
            strpos($_SERVER['HTTP_ACCEPT'], 'text/javascript') !== false);
}

/**
 * Upload a file
 * 
 * @param array $file File data ($_FILES element)
 * @param string $folder Destination folder
 * @return string|false Path to uploaded file or false on failure
 */
// function uploadFile($file, $folder = 'uploads') {
//     // Create directory if it doesn't exist
//     $uploadDir = '../uploads/' . $folder;
//     if (!file_exists($uploadDir)) {
//         mkdir($uploadDir, 0755, true);
//     }
    
//     // Generate unique filename
//     $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
//     $filename = uniqid() . '_' . time() . '.' . $extension;
//     $destination = $uploadDir . '/' . $filename;
    
//     // Move uploaded file
//     if (move_uploaded_file($file['tmp_name'], $destination)) {
//         return 'uploads/' . $folder . '/' . $filename;
//     }
    
//     return false;
// }
?>