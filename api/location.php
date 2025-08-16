<?php
// Set headers for JSON response
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Include required files
require_once '../includes/auth_check.php';

// Initialize PDO
try {
    $pdo = getPDOConnection();
} catch (Exception $e) {
    sendResponse(false, __('database_error'), 500);
    error_log("Database connection error: " . $e->getMessage());
    exit;
}

// Get current user
$currentUser = getCurrentUser();
if (!$currentUser) {
    sendResponse(false, __('session_invalid'), 401);
    exit;
}
$currentUserId = $currentUser['id'];
$userType = $currentUser['user_type'];

// Read input data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['action'])) {
    sendResponse(false, __('invalid_request'), 400);
    exit;
}
$action = filter_var($input['action'], FILTER_SANITIZE_STRING);
$data = $input;

// Process request
try {
    switch ($action) {
        case 'share_location':
            handleShareLocation($pdo, $currentUserId, $data);
            break;
        case 'get_locations':
            handleGetLocations($pdo, $currentUserId, $data, $userType);
            break;
        case 'create_emergency':
            handleCreateEmergency($pdo, $currentUserId, $data, $userType);
            break;
        case 'get_emergency':
            handleGetEmergency($pdo, $currentUserId, $data, $userType);
            break;
        case 'cancel_emergency':
            handleCancelEmergency($pdo, $currentUserId, $data, $userType);
            break;
        case 'get_active_emergencies':
            handleGetActiveEmergencies($pdo, $currentUserId, $userType);
            break;
        case 'create_service_request':
            handleCreateServiceRequest($pdo, $currentUserId, $data, $userType);
            break;
        case 'get_service_request':
            handleGetServiceRequest($pdo, $currentUserId, $data, $userType);
            break;
        case 'update_service_request_status':
            handleUpdateServiceRequestStatus($pdo, $currentUserId, $data, $userType);
            break;
        case 'get_active_service_requests':
            handleGetActiveServiceRequests($pdo, $currentUserId, $userType);
            break;
        default:
            sendResponse(false, __('invalid_action'), 400);
    }
} catch (Exception $e) {
    sendResponse(false, $e->getMessage(), $e->getCode() ?: 500);
    error_log("API Error [$action]: " . $e->getMessage());
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $code = 200, $data = []) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Handle share_location action
 */
function handleShareLocation($pdo, $userId, $data) {
    // Validate input
    $requiredFields = ['latitude', 'longitude'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception(sprintf(__('missing_field'), $field), 400);
        }
    }

    $latitude = filter_var($data['latitude'], FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($data['longitude'], FILTER_VALIDATE_FLOAT);

    if ($latitude === false || $longitude === false) {
        throw new Exception(__('invalid_coordinates'), 400);
    }

    // Insert or update location
    $stmt = $pdo->prepare("
        INSERT INTO locations (user_id, latitude, longitude, timestamp)
        VALUES (:user_id, :latitude, :longitude, NOW())
        ON DUPLICATE KEY UPDATE
            latitude = :latitude,
            longitude = :longitude,
            timestamp = NOW()
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':latitude' => $latitude,
        ':longitude' => $longitude
    ]);

    sendResponse(true, __('location_shared'), 200);
}

/**
 * Handle get_locations action
 */
function handleGetLocations($pdo, $userId, $data, $userType) {
    $groupId = isset($data['group_id']) ? filter_var($data['group_id'], FILTER_VALIDATE_INT) : null;

    // Build query based on user type
    $query = "
        SELECT 
            u.id, 
            u.full_name, 
            u.phone, 
            l.latitude, 
            l.longitude, 
            l.timestamp
        FROM users u
        LEFT JOIN (
            SELECT user_id, latitude, longitude, timestamp
            FROM locations
            WHERE (user_id, timestamp) IN (
                SELECT user_id, MAX(timestamp)
                FROM locations
                GROUP BY user_id
            )
        ) l ON u.id = l.user_id
        JOIN group_members gm ON u.id = gm.user_id
        WHERE gm.group_id IN (
            SELECT group_id FROM group_members WHERE user_id = :user_id
        )
    ";
    $params = [':user_id' => $userId];

    if ($groupId) {
        $query .= " AND gm.group_id = :group_id";
        $params[':group_id'] = $groupId;
    }

    if ($userType === 'pilgrim') {
        $query .= " AND u.id = :user_id";
    }

    $query .= " ORDER BY u.full_name";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, __('locations_fetched'), 200, ['locations' => $locations]);
}

/**
 * Handle create_emergency action
 */
function handleCreateEmergency($pdo, $userId, $data, $userType) {
    // Check permissions
    if (!in_array($userType, ['guardian', 'admin'])) {
        throw new Exception(__('access_denied'), 403);
    }

    // Validate input
    $requiredFields = ['title', 'group_id', 'latitude', 'longitude'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception(sprintf(__('missing_field'), $field), 400);
        }
    }

    $title = filter_var(trim($data['title']), FILTER_SANITIZE_STRING);
    $description = isset($data['description']) ? filter_var(trim($data['description']), FILTER_SANITIZE_STRING) : '';
    $groupId = filter_var($data['group_id'], FILTER_VALIDATE_INT);
    $latitude = filter_var($data['latitude'], FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($data['longitude'], FILTER_VALIDATE_FLOAT);

    if (!$title || !$groupId || $latitude === false || $longitude === false) {
        throw new Exception(__('invalid_input'), 400);
    }

    // Check if user is a member of the group
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM group_members
        WHERE group_id = :group_id AND user_id = :user_id
    ");
    $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);
    if ($stmt->fetchColumn() == 0) {
        throw new Exception(__('not_in_group'), 403);
    }

    // Insert emergency
    $stmt = $pdo->prepare("
        INSERT INTO emergencies (group_id, created_by, title, description, latitude, longitude, created_at, status)
        VALUES (:group_id, :created_by, :title, :description, :latitude, :longitude, NOW(), 'active')
    ");
    $stmt->execute([
        ':group_id' => $groupId,
        ':created_by' => $userId,
        ':title' => $title,
        ':description' => $description,
        ':latitude' => $latitude,
        ':longitude' => $longitude
    ]);

    $emergencyId = $pdo->lastInsertId();

    // Notify group members
    $stmt = $pdo->prepare("
        SELECT u.id FROM users u
        JOIN group_members gm ON u.id = gm.user_id
        WHERE gm.group_id = :group_id AND u.id != :user_id
    ");
    $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);
    $memberIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($memberIds as $memberId) {
        sendNotification($memberId, "Emergency Alert: $title", $description);
    }

    sendResponse(true, __('emergency_created'), 200, ['emergency_id' => $emergencyId]);
}

/**
 * Handle get_emergency action
 */
function handleGetEmergency($pdo, $userId, $data, $userType) {
    if (!isset($data['emergency_id']) || !filter_var($data['emergency_id'], FILTER_VALIDATE_INT)) {
        throw new Exception(__('invalid_emergency_id'), 400);
    }

    $emergencyId = $data['emergency_id'];

    // Get emergency details
    $stmt = $pdo->prepare("
        SELECT 
            e.id,
            e.title,
            e.description,
            e.latitude,
            e.longitude,
            e.created_at,
            e.group_id,
            g.name as group_name,
            u.full_name as created_by_name
        FROM emergencies e
        JOIN groups g ON e.group_id = g.id
        JOIN users u ON e.created_by = u.id
        JOIN group_members gm ON g.id = gm.group_id
        WHERE e.id = :emergency_id AND e.status = 'active' AND gm.user_id = :user_id
    ");
    $stmt->execute([':emergency_id' => $emergencyId, ':user_id' => $userId]);
    $emergency = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emergency) {
        throw new Exception(__('emergency_not_found_or_access_denied'), 404);
    }

    // Get group members
    $stmt = $pdo->prepare("
        SELECT 
            u.id, 
            u.full_name, 
            u.phone, 
            l.latitude, 
            l.longitude, 
            l.timestamp
        FROM users u
        JOIN group_members gm ON u.id = gm.user_id
        LEFT JOIN (
            SELECT user_id, latitude, longitude, timestamp
            FROM locations
            WHERE (user_id, timestamp) IN (
                SELECT user_id, MAX(timestamp)
                FROM locations
                GROUP BY user_id
            )
        ) l ON u.id = l.user_id
        WHERE gm.group_id = :group_id
        ORDER BY u.full_name
    ");
    $stmt->execute([':group_id' => $emergency['group_id']]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, __('emergency_details_fetched'), 200, [
        'emergency' => $emergency,
        'members' => $members
    ]);
}

/**
 * Handle cancel_emergency action
 */
function handleCancelEmergency($pdo, $userId, $data, $userType) {
    if (!isset($data['emergency_id']) || !filter_var($data['emergency_id'], FILTER_VALIDATE_INT)) {
        throw new Exception(__('invalid_emergency_id'), 400);
    }

    $emergencyId = $data['emergency_id'];

    // Check if user can cancel (admin or creator)
    $stmt = $pdo->prepare("
        SELECT group_id, created_by
        FROM emergencies
        WHERE id = :emergency_id AND status = 'active'
    ");
    $stmt->execute([':emergency_id' => $emergencyId]);
    $emergency = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emergency) {
        throw new Exception(__('emergency_not_found'), 404);
    }

    if ($userType !== 'admin' && ($userType !== 'guardian' || $emergency['created_by'] != $userId)) {
        throw new Exception(__('access_denied'), 403);
    }

    // Cancel emergency
    $stmt = $pdo->prepare("
        UPDATE emergencies
        SET status = 'canceled'
        WHERE id = :emergency_id
    ");
    $stmt->execute([':emergency_id' => $emergencyId]);

    // Notify group members
    $stmt = $pdo->prepare("
        SELECT u.id FROM users u
        JOIN group_members gm ON u.id = gm.user_id
        WHERE gm.group_id = :group_id AND u.id != :user_id
    ");
    $stmt->execute([':group_id' => $emergency['group_id'], ':user_id' => $userId]);
    $memberIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($memberIds as $memberId) {
        sendNotification($memberId, "Emergency Canceled", "The emergency has been canceled.");
    }

    sendResponse(true, __('emergency_canceled'));
}

/**
 * Handle get_active_emergencies action
 */
function handleGetActiveEmergencies($pdo, $userId, $userType) {
    $query = "
        SELECT 
            e.id,
            e.title,
            e.latitude,
            e.longitude,
            e.created_at,
            g.name as group_name
        FROM emergencies e
        JOIN groups g ON e.group_id = g.id
        JOIN group_members gm ON g.id = gm.group_id
        WHERE e.status = 'active' AND gm.user_id = :user_id
    ";
    $params = [':user_id' => $userId];

    if ($userType === 'pilgrim') {
        $query .= " AND e.created_by = :user_id";
    }

    $query .= " ORDER BY e.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $emergencies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, __('emergencies_fetched'), 200, ['emergencies' => $emergencies]);
}

/**
 * Handle create_service_request action
 */
function handleCreateServiceRequest($pdo, $userId, $data, $userType) {
    // Check permissions
    if (!in_array($userType, ['pilgrim', 'guardian', 'admin'])) {
        throw new Exception(__('access_denied'), 403);
    }

    // Validate input
    $requiredFields = ['title', 'service_type', 'latitude', 'longitude'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception(sprintf(__('missing_field'), $field), 400);
        }
    }


// Initialize PDO
try {
    $pdo = getPDOConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    sendResponse(false, __('database_error'), 500);
    error_log("Database connection error: " . $e->getMessage());
    exit;
}

// Get current user
$currentUser = getCurrentUser();
if (!$currentUser) {
    sendResponse(false, __('session_invalid'), 401);
    exit;
}
$currentUserId = $currentUser['id'];
$userType = $currentUser['user_type'];

// Read input data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['action'])) {
    sendResponse(false, __('invalid_request'), 400);
    exit;
}
$action = filter_var($input['action'], FILTER_SANITIZE_STRING);
$data = $input;

// Process request
try {
    switch ($action) {
        case 'share_location':
            handleShareLocation($pdo, $currentUserId, $data);
            break;
        case 'get_locations':
            handleGetLocations($pdo, $currentUserId, $data, $userType);
            break;
        case 'create_emergency':
            handleCreateEmergency($pdo, $currentUserId, $data, $userType);
            break;
        case 'get_emergency':
            handleGetEmergency($pdo, $currentUserId, $data, $userType);
            break;
        case 'cancel_emergency':
            handleCancelEmergency($pdo, $currentUserId, $data, $userType);
            break;
        case 'get_active_emergencies':
            handleGetActiveEmergencies($pdo, $currentUserId, $userType);
            break;
        case 'create_service_request':
            handleCreateServiceRequest($pdo, $currentUserId, $data, $userType);
            break;
        case 'get_service_request':
            handleGetServiceRequest($pdo, $currentUserId, $data, $userType);
            break;
        case 'update_service_request_status':
            handleUpdateServiceRequestStatus($pdo, $currentUserId, $data, $userType);
            break;
        case 'get_active_service_requests':
            handleGetActiveServiceRequests($pdo, $currentUserId, $userType);
            break;
        default:
            sendResponse(false, __('invalid_action'), 400);
    }
} catch (Exception $e) {
    sendResponse(false, $e->getMessage(), $e->getCode() ?: 500);
    error_log("API Error [$action]: " . $e->getMessage());
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $code = 200, $data = []) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Handle share_location action
 */
function handleShareLocation($pdo, $userId, $data) {
    $requiredFields = ['latitude', 'longitude'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception(sprintf(__('missing_field'), $field), 400);
        }
    }

    $latitude = filter_var($data['latitude'], FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($data['longitude'], FILTER_VALIDATE_FLOAT);

    if ($latitude === false || $longitude === false) {
        throw new Exception(__('invalid_coordinates'), 400);
    }

    $stmt = $pdo->prepare("
        INSERT INTO locations (user_id, latitude, longitude, timestamp)
        VALUES (:user_id, :latitude, :longitude, NOW())
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':latitude' => $latitude,
        ':longitude' => $longitude
    ]);

    sendResponse(true, __('location_shared'), 200);
}

/**
 * Handle get_locations action
 */
function handleGetLocations($pdo, $userId, $data, $userType) {
    $groupId = isset($data['group_id']) ? filter_var($data['group_id'], FILTER_VALIDATE_INT) : null;

    $query = "
        SELECT 
            u.id, 
            u.full_name, 
            u.phone, 
            l.latitude, 
            l.longitude, 
            l.timestamp
        FROM users u
        LEFT JOIN (
            SELECT user_id, latitude, longitude, timestamp
            FROM locations
            WHERE (user_id, timestamp) IN (
                SELECT user_id, MAX(timestamp)
                FROM locations
                GROUP BY user_id
            )
        ) l ON u.id = l.user_id
        JOIN group_members gm ON u.id = gm.user_id
        WHERE gm.group_id IN (
            SELECT group_id FROM group_members WHERE user_id = :user_id
        )
    ";
    $params = [':user_id' => $userId];

    if ($groupId) {
        $query .= " AND gm.group_id = :group_id";
        $params[':group_id'] = $groupId;
    }

    if ($userType === 'pilgrim') {
        $query .= " AND u.id = :user_id";
    }

    $query .= " ORDER BY u.full_name";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, __('locations_fetched'), 200, ['locations' => $locations]);
}

/**
 * Handle create_emergency action
 */
function handleCreateEmergency($pdo, $userId, $data, $userType) {
    if (!in_array($userType, ['guardian', 'admin'])) {
        throw new Exception(__('access_denied'), 403);
    }

    $requiredFields = ['title', 'group_id', 'latitude', 'longitude'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception(sprintf(__('missing_field'), $field), 400);
        }
    }

    $title = filter_var(trim($data['title']), FILTER_SANITIZE_STRING);
    $description = isset($data['description']) ? filter_var(trim($data['description']), FILTER_SANITIZE_STRING) : '';
    $groupId = filter_var($data['group_id'], FILTER_VALIDATE_INT);
    $latitude = filter_var($data['latitude'], FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($data['longitude'], FILTER_VALIDATE_FLOAT);

    if (!$title || !$groupId || $latitude === false || $longitude === false) {
        throw new Exception(__('invalid_input'), 400);
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM group_members
        WHERE group_id = :group_id AND user_id = :user_id
    ");
    $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);
    if ($stmt->fetchColumn() == 0) {
        throw new Exception(__('not_in_group'), 403);
    }

    $stmt = $pdo->prepare("
        INSERT INTO emergencies (group_id, created_by, title, description, latitude, longitude, created_at, status)
        VALUES (:group_id, :created_by, :title, :description, :latitude, :longitude, NOW(), 'active')
    ");
    $stmt->execute([
        ':group_id' => $groupId,
        ':created_by' => $userId,
        ':title' => $title,
        ':description' => $description,
        ':latitude' => $latitude,
        ':longitude' => $longitude
    ]);

    $emergencyId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        SELECT u.id FROM users u
        JOIN group_members gm ON u.id = gm.user_id
        WHERE gm.group_id = :group_id AND u.id != :user_id
    ");
    $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);
    $memberIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($memberIds as $memberId) {
        sendNotification($memberId, "Emergency Alert: $title", $description);
    }

    sendResponse(true, __('emergency_created'), 200, ['emergency_id' => $emergencyId]);
}

/**
 * Handle get_emergency action
 */
function handleGetEmergency($pdo, $userId, $data, $userType) {
    if (!isset($data['emergency_id']) || !filter_var($data['emergency_id'], FILTER_VALIDATE_INT)) {
        throw new Exception(__('invalid_emergency_id'), 400);
    }

    $emergencyId = $data['emergency_id'];

    $stmt = $pdo->prepare("
        SELECT 
            e.id,
            e.title,
            e.description,
            e.latitude,
            e.longitude,
            e.created_at,
            e.group_id,
            g.name as group_name,
            u.full_name as created_by_name
        FROM emergencies e
        JOIN groups g ON e.group_id = g.id
        JOIN users u ON e.created_by = u.id
        JOIN group_members gm ON g.id = gm.group_id
        WHERE e.id = :emergency_id AND e.status = 'active' AND gm.user_id = :user_id
    ");
    $stmt->execute([':emergency_id' => $emergencyId, ':user_id' => $userId]);
    $emergency = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emergency) {
        throw new Exception(__('emergency_not_found_or_access_denied'), 404);
    }

    $stmt = $pdo->prepare("
        SELECT 
            u.id, 
            u.full_name, 
            u.phone, 
            l.latitude, 
            l.longitude, 
            l.timestamp
        FROM users u
        JOIN group_members gm ON u.id = gm.user_id
        LEFT JOIN (
            SELECT user_id, latitude, longitude, timestamp
            FROM locations
            WHERE (user_id, timestamp) IN (
                SELECT user_id, MAX(timestamp)
                FROM locations
                GROUP BY user_id
            )
        ) l ON u.id = l.user_id
        WHERE gm.group_id = :group_id
        ORDER BY u.full_name
    ");
    $stmt->execute([':group_id' => $emergency['group_id']]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, __('emergency_details_fetched'), 200, [
        'emergency' => $emergency,
        'members' => $members
    ]);
}

/**
 * Handle cancel_emergency action
 */
function handleCancelEmergency($pdo, $userId, $data, $userType) {
    if (!isset($data['emergency_id']) || !filter_var($data['emergency_id'], FILTER_VALIDATE_INT)) {
        throw new Exception(__('invalid_emergency_id'), 400);
    }

    $emergencyId = $data['emergency_id'];

    $stmt = $pdo->prepare("
        SELECT group_id, created_by
        FROM emergencies
        WHERE id = :emergency_id AND status = 'active'
    ");
    $stmt->execute([':emergency_id' => $emergencyId]);
    $emergency = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emergency) {
        throw new Exception(__('emergency_not_found'), 404);
    }

    if ($userType !== 'admin' && ($userType !== 'guardian' || $emergency['created_by'] != $userId)) {
        throw new Exception(__('access_denied'), 403);
    }

    $stmt = $pdo->prepare("
        UPDATE emergencies
        SET status = 'canceled'
        WHERE id = :emergency_id
    ");
    $stmt->execute([':emergency_id' => $emergencyId]);

    $stmt = $pdo->prepare("
        SELECT u.id FROM users u
        JOIN group_members gm ON u.id = gm.user_id
        WHERE gm.group_id = :group_id AND u.id != :user_id
    ");
    $stmt->execute([':group_id' => $emergency['group_id'], ':user_id' => $userId]);
    $memberIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($memberIds as $memberId) {
        sendNotification($memberId, "Emergency Canceled", "The emergency has been canceled.");
    }

    sendResponse(true, __('emergency_canceled'));
}

/**
 * Handle get_active_emergencies action
 */
function handleGetActiveEmergencies($pdo, $userId, $userType) {
    $query = "
        SELECT 
            e.id,
            e.title,
            e.latitude,
            e.longitude,
            e.created_at,
            g.name as group_name
        FROM emergencies e
        JOIN groups g ON e.group_id = g.id
        JOIN group_members gm ON g.id = gm.group_id
        WHERE e.status = 'active' AND gm.user_id = :user_id
    ";
    $params = [':user_id' => $userId];

    if ($userType === 'pilgrim') {
        $query .= " AND e.created_by = :user_id";
    }

    $query .= " ORDER BY e.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $emergencies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, __('emergencies_fetched'), 200, ['emergencies' => $emergencies]);
}

/**
 * Handle create_service_request action
 */
function handleCreateServiceRequest($pdo, $userId, $data, $userType) {
    if (!in_array($userType, ['pilgrim', 'guardian', 'admin'])) {
        throw new Exception(__('access_denied'), 403);
    }

    $requiredFields = ['title', 'service_type', 'latitude', 'longitude'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception(sprintf(__('missing_field'), $field), 400);
        }
    }

    $title = filter_var(trim($data['title']), FILTER_SANITIZE_STRING);
    $description = isset($data['description']) ? filter_var(trim($data['description']), FILTER_SANITIZE_STRING) : '';
    $serviceType = filter_var($data['service_type'], FILTER_SANITIZE_STRING);
    $groupId = isset($data['group_id']) && $data['group_id'] ? filter_var($data['group_id'], FILTER_VALIDATE_INT) : null;
    $latitude = filter_var($data['latitude'], FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($data['longitude'], FILTER_VALIDATE_FLOAT);

    if (!$title || !in_array($serviceType, ['medical', 'logistic', 'security']) || $latitude === false || $longitude === false) {
        throw new Exception(__('invalid_input'), 400);
    }

    if ($groupId) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM group_members
            WHERE group_id = :group_id AND user_id = :user_id
        ");
        $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception(__('not_in_group'), 403);
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO service_requests (group_id, created_by, title, description, service_type, latitude, longitude, status, created_at, updated_at)
        VALUES (:group_id, :created_by, :title, :description, :service_type, :latitude, :longitude, 'pending', NOW(), NOW())
    ");
    $stmt->execute([
        ':group_id' => $groupId,
        ':created_by' => $userId,
        ':title' => $title,
        ':description' => $description,
        ':service_type' => $serviceType,
        ':latitude' => $latitude,
        ':longitude' => $longitude
    ]);

    $requestId = $pdo->lastInsertId();

    if ($groupId) {
        $stmt = $pdo->prepare("
            SELECT u.id FROM users u
            JOIN group_members gm ON u.id = gm.user_id
            WHERE gm.group_id = :group_id AND u.user_type = 'authority' AND u.id != :user_id
        ");
        $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);
        $authorityIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($authorityIds as $authorityId) {
            sendNotification($authorityId, "New Service Request: $title", $description);
        }
    }

    sendResponse(true, __('request_created'), 200, ['request_id' => $requestId]);
}

/**
 * Handle get_service_request action
 */
function handleGetServiceRequest($pdo, $userId, $data, $userType) {
    if (!isset($data['request_id']) || !filter_var($data['request_id'], FILTER_VALIDATE_INT)) {
        throw new Exception(__('invalid_request_id'), 400);
    }

    $requestId = $data['request_id'];

    $query = "
        SELECT 
            sr.id,
            sr.title,
            sr.description,
            sr.service_type,
            sr.latitude,
            sr.longitude,
            sr.status,
            sr.created_at,
            sr.group_id,
            g.name as group_name,
            u.full_name as created_by_name
        FROM service_requests sr
        LEFT JOIN groups g ON sr.group_id = g.id
        JOIN users u ON sr.created_by = u.id
        WHERE sr.id = :request_id
    ";
    $params = [':request_id' => $requestId];

    if ($userType === 'pilgrim') {
        $query .= " AND sr.created_by = :user_id";
        $params[':user_id'] = $userId;
    } elseif ($userType === 'guardian' || $userType === 'authority') {
        $query .= " AND (sr.created_by = :user_id OR sr.group_id IN (
            SELECT group_id FROM group_members WHERE user_id = :user_id
        ))";
        $params[':user_id'] = $userId;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception(__('request_not_found_or_access_denied'), 404);
    }

    $members = [];
    if ($request['group_id']) {
        $stmt = $pdo->prepare("
            SELECT 
                u.id, 
                u.full_name, 
                u.phone, 
                l.latitude, 
                l.longitude, 
                l.timestamp
            FROM users u
            JOIN group_members gm ON u.id = gm.user_id
            LEFT JOIN (
                SELECT user_id, latitude, longitude, timestamp
                FROM locations
                WHERE (user_id, timestamp) IN (
                    SELECT user_id, MAX(timestamp)
                    FROM locations
                    GROUP BY user_id
                )
            ) l ON u.id = l.user_id
            WHERE gm.group_id = :group_id
            ORDER BY u.full_name
        ");
        $stmt->execute([':group_id' => $request['group_id']]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    sendResponse(true, __('request_details_fetched'), 200, [
        'request' => $request,
        'members' => $members
    ]);
}

/**
 * Handle update_service_request_status action
 */
function handleUpdateServiceRequestStatus($pdo, $userId, $data, $userType) {
    if (!in_array($userType, ['authority', 'admin'])) {
        throw new Exception(__('access_denied'), 403);
    }

    if (!isset($data['request_id']) || !filter_var($data['request_id'], FILTER_VALIDATE_INT)) {
        throw new Exception(__('invalid_request_id'), 400);
    }

    if (!isset($data['status']) || !in_array($data['status'], ['pending', 'in_progress', 'completed', 'canceled'])) {
        throw new Exception(__('invalid_status'), 400);
    }

    $requestId = $data['request_id'];
    $status = filter_var($data['status'], FILTER_SANITIZE_STRING);

    $stmt = $pdo->prepare("
        SELECT group_id, created_by
        FROM service_requests
        WHERE id = :request_id
    ");
    $stmt->execute([':request_id' => $requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception(__('request_not_found'), 404);
    }

    if ($userType === 'authority' && $request['group_id']) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM group_members
            WHERE group_id = :group_id AND user_id = :user_id
        ");
        $stmt->execute([':group_id' => $request['group_id'], ':user_id' => $userId]);
        if ($stmt->fetchColumn() == 0) {
            throw new Exception(__('not_in_group'), 403);
        }
    }

    $stmt = $pdo->prepare("
        UPDATE service_requests
        SET status = :status, updated_at = NOW()
        WHERE id = :request_id
    ");
    $stmt->execute([':status' => $status, ':request_id' => $requestId]);

    if ($request['group_id']) {
        $stmt = $pdo->prepare("
            SELECT u.id FROM users u
            JOIN group_members gm ON u.id = gm.user_id
            WHERE gm.group_id = :group_id AND u.id != :user_id
        ");
        $stmt->execute([':group_id' => $request['group_id'], ':user_id' => $userId]);
        $memberIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($memberIds as $memberId) {
            sendNotification($memberId, "Service Request Status Updated", "The request status has been updated to: $status.");
        }
    }

    sendResponse(true, __('request_status_updated'));
}

/**
 * Handle get_active_service_requests action
 */
function handleGetActiveServiceRequests($pdo, $userId, $userType) {
    $query = "
        SELECT 
            sr.id,
            sr.title,
            sr.description,
            sr.service_type,
            sr.latitude,
            sr.longitude,
            sr.status,
            sr.created_at,
            g.name as group_name
        FROM service_requests sr
        LEFT JOIN groups g ON sr.group_id = g.id
        WHERE sr.status IN ('pending', 'in_progress')
    ";
    $params = [];

    if ($userType === 'pilgrim') {
        $query .= " AND sr.created_by = :user_id";
        $params[':user_id'] = $userId;
    } elseif ($userType === 'guardian') {
        $query .= " AND (sr.created_by = :user_id OR sr.group_id IN (
            SELECT group_id FROM group_members WHERE user_id = :user_id
        ))";
        $params[':user_id'] = $userId;
    } elseif ($userType === 'authority') {
        $query .= " AND sr.group_id IN (
            SELECT group_id FROM group_members WHERE user_id = :user_id
        )";
        $params[':user_id'] = $userId;
    }

    $query .= " ORDER BY sr.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, __('requests_fetched'), 200, ['requests' => $requests]);
}
function calculateDistanceToKaaba($lat, $lon) {
    $kaabaLat = 21.4225;
    $kaabaLon = 39.8262;
    $earthRadius = 6371; // km
    $dLat = deg2rad($kaabaLat - $lat);
    $dLon = deg2rad($kaabaLon - $lon);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat)) * cos(deg2rad($kaabaLat)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}
/**
 * Placeholder for sendNotification
 */
function sendNotification($userId, $title, $message) {
    error_log("Notification sent to user $userId: $title - $message");
}

function calculateDistanceToKaaba($lat, $lon) {
    $kaabaLat = 21.4225;
    $kaabaLon = 39.8262;
    $earthRadius = 6371; // km
    $dLat = deg2rad($kaabaLat - $lat);
    $dLon = deg2rad($kaabaLon - $lon);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat)) * cos(deg2rad($kaabaLat)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earthRadius * $c;
}

?>