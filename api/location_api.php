<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connect.php';
header('Content-Type: application/json');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Get current user
$currentUser = getCurrentUser();
if (!$currentUser) {
    sendResponse(false, __('session_invalid'), 401);
    exit;
}
$currentUserId = $currentUser['id'];
$userType = $currentUser['user_type'];

// Check base permission for maps
global $permissions;
if (!isset($permissions[$userType]['maps']) || !$permissions[$userType]['maps']) {
    sendResponse(false, __('access_denied'), 403);
    exit;
}

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception(__('method_not_allowed'), 405);
    }

    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception(__('invalid_json'), 400);
    }

    // Ensure action is provided
    if (!isset($data['action']) || empty($data['action'])) {
        throw new Exception(__('missing_action'), 400);
    }

    $action = $data['action'];
    $pdo->beginTransaction();

    switch ($action) {
        case 'share_location':
            handleShareLocation($pdo, $currentUserId, $data, $userType);
            break;
        case 'create_group':
            handleCreateGroup($pdo, $currentUserId, $data, $userType);
            break;
        case 'join_group':
            handleJoinGroup($pdo, $currentUserId, $data);
            break;
        case 'get_group':
            handleGetGroup($pdo, $currentUserId, $data);
            break;
        case 'delete_group':
            handleDeleteGroup($pdo, $currentUserId, $data, $userType);
            break;
        case 'leave_group':
            handleLeaveGroup($pdo, $currentUserId, $data);
            break;
        case 'remove_member':
            handleRemoveMember($pdo, $currentUserId, $data, $userType);
            break;
        case 'get_all_members':
            handleGetAllMembers($pdo, $currentUserId);
            break;
        default:
            throw new Exception(__('invalid_action'), 400);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error in locations.php: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
    sendResponse(false, $e->getMessage(), $e->getCode() ?: 500);
}

/**
 * Send JSON response
 */
function sendResponse($success, $message, $code = 200, $data = []) {
    http_response_code($code);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
        'error_code' => $code
    ], $data));
    exit;
}

/**
 * Handle share_location action
 */
function handleShareLocation($pdo, $userId, $data, $userType) {
    // Check permissions
    if (!in_array($userType, ['pilgrim', 'guardian', 'admin'])) {
        throw new Exception(__('access_denied'), 403);
    }

    // Validate coordinates
    $requiredFields = ['latitude', 'longitude'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception(sprintf(__('missing_field'), $field), 400);
        }
    }

    $latitude = filter_var($data['latitude'], FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($data['longitude'], FILTER_VALIDATE_FLOAT);
    $accuracy = isset($data['accuracy']) ? filter_var($data['accuracy'], FILTER_VALIDATE_FLOAT) : 0;

    if ($latitude === false || $longitude === false) {
        throw new Exception(__('invalid_coordinates'), 400);
    }

    // Insert or update location
    $stmt = $pdo->prepare("
        INSERT INTO locations (user_id, latitude, longitude, accuracy, timestamp)
        VALUES (:user_id, :latitude, :longitude, :accuracy, NOW())
        ON DUPLICATE KEY UPDATE
        latitude = :latitude,
        longitude = :longitude,
        accuracy = :accuracy,
        timestamp = NOW()
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':latitude' => $latitude,
        ':longitude' => $longitude,
        ':accuracy' => $accuracy
    ]);

    sendResponse(true, __('location_updated'));
}

/**
 * Handle create_group action
 */
function handleCreateGroup($pdo, $userId, $data, $userType) {
    // Check permissions
    if (!in_array($userType, ['guardian', 'admin'])) {
        throw new Exception(__('access_denied'), 403);
    }

    // Validate input
    if (!isset($data['name']) || empty(trim($data['name']))) {
        throw new Exception(__('enter_group_name'), 400);
    }

    $name = filter_var(trim($data['name']), FILTER_SANITIZE_STRING);
    $description = isset($data['description']) ? filter_var(trim($data['description']), FILTER_SANITIZE_STRING) : '';
    $inviteCode = generateInviteCode();

    // Insert group
    $stmt = $pdo->prepare("
        INSERT INTO groups (name, description, invite_code, created_by, created_at, active)
        VALUES (:name, :description, :invite_code, :created_by, NOW(), 1)
    ");
    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':invite_code' => $inviteCode,
        ':created_by' => $userId
    ]);

    $groupId = $pdo->lastInsertId();

    // Add creator to group members
    $stmt = $pdo->prepare("
        INSERT INTO group_members (group_id, user_id)
        VALUES (:group_id, :user_id)
    ");
    $stmt->execute([
        ':group_id' => $groupId,
        ':user_id' => $userId
    ]);

    sendResponse(true, __('group_created'), 200, ['group_id' => $groupId, 'invite_code' => $inviteCode]);
}

/**
 * Handle join_group action
 */
function handleJoinGroup($pdo, $userId, $data) {
    if (!isset($data['invite_code']) || empty(trim($data['invite_code']))) {
        throw new Exception(__('enter_invite_code'), 400);
    }

    $inviteCode = filter_var(trim($data['invite_code']), FILTER_SANITIZE_STRING);

    // Check if group exists
    $stmt = $pdo->prepare("
        SELECT id FROM groups
        WHERE invite_code = :invite_code AND active = 1
    ");
    $stmt->execute([':invite_code' => $inviteCode]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {
        throw new Exception(__('invalid_invite_code'), 404);
    }

    $groupId = $group['id'];

    // Check if user is already a member
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM group_members
        WHERE group_id = :group_id AND user_id = :user_id
    ");
    $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception(__('already_in_group'), 400);
    }

    // Add user to group
    $stmt = $pdo->prepare("
        INSERT INTO group_members (group_id, user_id)
        VALUES (:group_id, :user_id)
    ");
    $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);

    sendResponse(true, __('group_joined'), 200, ['group_id' => $groupId]);
}

/**
 * Handle get_group action
 */
function handleGetGroup($pdo, $userId, $data) {
    if (!isset($data['group_id']) || !filter_var($data['group_id'], FILTER_VALIDATE_INT)) {
        throw new Exception(__('invalid_group_id'), 400);
    }

    $groupId = $data['group_id'];

    // Check if user is a member of the group
    $stmt = $pdo->prepare("
        SELECT g.id, g.name, g.invite_code, g.created_by
        FROM groups g
        JOIN group_members gm ON g.id = gm.group_id
        WHERE g.id = :group_id AND gm.user_id = :user_id AND g.active = 1
    ");
    $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$group) {
        throw new Exception(__('group_not_found_or_access_denied'), 404);
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
    $stmt->execute([':group_id' => $groupId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, __('group_details_fetched'), 200, [
        'group' => $group,
        'members' => $members
    ]);
}

/**
 * Handle delete_group action
 */
function handleDeleteGroup($pdo, $userId, $data, $userType) {
    if (!in_array($userType, ['guardian', 'admin'])) {
        throw new Exception(__('access_denied'), 403);
    }

    if (!isset($data['group_id']) || !filter_var($data['group_id'], FILTER_VALIDATE_INT)) {
        throw new Exception(__('invalid_group_id'), 400);
    }

    $groupId = $data['group_id'];

    // Check if user is the creator
    $stmt = $pdo->prepare("
        SELECT id FROM groups
        WHERE id = :group_id AND created_by = :user_id AND active = 1
    ");
    $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception(__('group_not_found_or_access_denied'), 404);
    }

    // Mark group as inactive
    $stmt = $pdo->prepare("
        UPDATE groups
        SET active = 0
        WHERE id = :group_id
    ");
    $stmt->execute([':group_id' => $groupId]);

    // Remove all members
    $stmt = $pdo->prepare("
        DELETE FROM group_members
        WHERE group_id = :group_id
    ");
    $stmt->execute([':group_id' => $groupId]);

    sendResponse(true, __('group_deleted'));
}

/**
 * Handle leave_group action
 */
function handleLeaveGroup($pdo, $userId, $data) {
    if (!isset($data['group_id']) || !filter_var($data['group_id'], FILTER_VALIDATE_INT)) {
        throw new Exception(__('invalid_group_id'), 400);
    }

    $groupId = $data['group_id'];

    // Check if user is a member
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM group_members
        WHERE group_id = :group_id AND user_id = :user_id
    ");
    $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);
    if ($stmt->fetchColumn() == 0) {
        throw new Exception(__('not_in_group'), 400);
    }

    // Check if user is the creator
    $stmt = $pdo->prepare("
        SELECT created_by FROM groups
        WHERE id = :group_id AND active = 1
    ");
    $stmt->execute([':group_id' => $groupId]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($group && $group['created_by'] == $userId) {
        throw new Exception(__('creator_cannot_leave'), 400);
    }

    // Remove user from group
    $stmt = $pdo->prepare("
        DELETE FROM group_members
        WHERE group_id = :group_id AND user_id = :user_id
    ");
    $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);

    sendResponse(true, __('group_left'));
}

/**
 * Handle remove_member action
 */
function handleRemoveMember($pdo, $userId, $data, $userType) {
    if (!in_array($userType, ['guardian', 'admin'])) {
        throw new Exception(__('access_denied'), 403);
    }

    if (!isset($data['group_id']) || !filter_var($data['group_id'], FILTER_VALIDATE_INT)) {
        throw new Exception(__('invalid_group_id'), 400);
    }
    if (!isset($data['member_id']) || !filter_var($data['member_id'], FILTER_VALIDATE_INT)) {
        throw new Exception(__('invalid_member_id'), 400);
    }

    $groupId = $data['group_id'];
    $memberId = $data['member_id'];

    // Check if user is the creator
    $stmt = $pdo->prepare("
        SELECT id FROM groups
        WHERE id = :group_id AND created_by = :user_id AND active = 1
    ");
    $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception(__('group_not_found_or_access_denied'), 404);
    }

    // Check if member is in group
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM group_members
        WHERE group_id = :group_id AND user_id = :member_id
    ");
    $stmt->execute([':group_id' => $groupId, ':member_id' => $memberId]);
    if ($stmt->fetchColumn() == 0) {
        throw new Exception(__('member_not_in_group'), 400);
    }

    // Remove member
    $stmt = $pdo->prepare("
        DELETE FROM group_members
        WHERE group_id = :group_id AND user_id = :member_id
    ");
    $stmt->execute([':group_id' => $groupId, ':member_id' => $memberId]);

    sendResponse(true, __('member_removed'));
}

/**
 * Handle get_all_members action
 */
function handleGetAllMembers($pdo, $userId) {
    // Get all groups the user is part of
    $stmt = $pdo->prepare("
        SELECT group_id FROM group_members
        WHERE user_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    $groupIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($groupIds)) {
        sendResponse(true, __('no_groups'), 200, ['members' => []]);
        return;
    }

    $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
    $params = array_merge($groupIds, [$userId]);

    // Get all members except the current user
    $stmt = $pdo->prepare("
        SELECT 
            u.id, 
            u.full_name, 
            u.phone, 
            l.latitude, 
            l.longitude, 
            l.timestamp,
            g.name as group_name
        FROM users u
        JOIN group_members gm ON u.id = gm.user_id
        JOIN groups g ON gm.group_id = g.id
        LEFT JOIN (
            SELECT user_id, latitude, longitude, timestamp
            FROM locations
            WHERE (user_id, timestamp) IN (
                SELECT user_id, MAX(timestamp)
                FROM locations
                GROUP BY user_id
            )
        ) l ON u.id = l.user_id
        WHERE gm.group_id IN ($placeholders) AND u.id != ?
        ORDER BY g.name, u.full_name
    ");
    $stmt->execute($params);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(true, __('members_fetched'), 200, ['members' => $members]);
}

/**
 * Generate unique invite code
 */
function generateInviteCode() {
    return bin2hex(random_bytes(4)); // Generates an 8-character code
}
?>