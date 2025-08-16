 
<?php
/**
 * Service API
 * 
 * Handles service requests and provider operations
 */

// Set content type to JSON
header('Content-Type: application/json');

// Include required files
require_once('../includes/functions.php');

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => __('login_required')
    ]);
    exit;
}

// Get current user
$currentUser = getCurrentUser();

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // If no JSON data, check POST
    if (!$data && !empty($_POST)) {
        $data = $_POST;
    }
    
    // Get action
    $action = isset($data['action']) ? $data['action'] : 'request_service';
    
    // Request a service
    if ($action === 'request_service' || empty($action)) {
        // Validate inputs
        if (!isset($data['service_type']) || empty($data['service_type'])) {
            echo json_encode([
                'success' => false,
                'message' => __('service_type_required')
            ]);
            exit;
        }
        
        $serviceType = sanitizeInput($data['service_type']);
        $pickupLocation = isset($data['pickup_location']) ? sanitizeInput($data['pickup_location']) : '';
        $destination = isset($data['destination']) ? sanitizeInput($data['destination']) : '';
        $specialNeeds = isset($data['special_needs']) ? sanitizeInput($data['special_needs']) : '';
        $contactPhone = isset($data['contact_phone']) ? sanitizeInput($data['contact_phone']) : $currentUser['phone'];
        $numPassengers = isset($data['num_passengers']) ? (int)$data['num_passengers'] : 1;
        $latitude = isset($data['latitude']) ? (float)$data['latitude'] : null;
        $longitude = isset($data['longitude']) ? (float)$data['longitude'] : null;
        
        // Validate service type
        if (!in_array($serviceType, ['cart', 'wheelchair', 'guide', 'medical', 'transport', 'other'])) {
            echo json_encode([
                'success' => false,
                'message' => __('invalid_service_type')
            ]);
            exit;
        }
        
        // For wheelchair, enforce 1 passenger
        if ($serviceType === 'wheelchair') {
            $numPassengers = 1;
        }
        
        // Validate pickup location
        if (empty($pickupLocation)) {
            echo json_encode([
                'success' => false,
                'message' => __('pickup_location_required')
            ]);
            exit;
        }
        
        // Validate destination
        if (empty($destination)) {
            echo json_encode([
                'success' => false,
                'message' => __('destination_required')
            ]);
            exit;
        }
        
        // Insert service request
        $sql = "INSERT INTO service_requests (
                    user_id, service_type, status, pickup_location, destination, 
                    special_needs, latitude, longitude, contact_phone, num_passengers, 
                    created_at
                ) VALUES (?, ?, 'requested', ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $requestId = insertData($sql, [
            $currentUser['id'],
            $serviceType,
            $pickupLocation,
            $destination,
            $specialNeeds,
            $latitude,
            $longitude,
            $contactPhone,
            $numPassengers
        ]);
        
        if (!$requestId) {
            echo json_encode([
                'success' => false,
                'message' => __('service_request_failed')
            ]);
            exit;
        }
        
        // If special needs provided, add to notes
        if (!empty($specialNeeds)) {
            $sql = "INSERT INTO request_notes (request_id, notes, created_at)
                    VALUES (?, ?, NOW())";
            insertData($sql, [$requestId, $specialNeeds]);
        }
        
        // Log activity
        logActivity($currentUser['id'], 'service_request', [
            'request_id' => $requestId,
            'service_type' => $serviceType
        ]);
        
        // Try to find and assign a suitable provider
        $provider = findSuitableProvider($serviceType, $latitude, $longitude);
        
        if ($provider) {
            // Update request with provider
            $sql = "UPDATE service_requests SET provider_id = ? WHERE id = ?";
            updateData($sql, [$provider['id'], $requestId]);
            
            // Create notification for provider
            createProviderNotification($provider['id'], $requestId, $serviceType);
        } else {
            // Notify authorities about unassigned request
            notifyAuthoritiesAboutRequest($requestId, $serviceType);
        }
        
        echo json_encode([
            'success' => true,
            'message' => __('service_request_submitted'),
            'request_id' => $requestId
        ]);
    }
    // Cancel a service request
    elseif ($action === 'cancel') {
        // Validate inputs
        if (!isset($data['request_id']) || empty($data['request_id'])) {
            echo json_encode([
                'success' => false,
                'message' => __('request_id_required')
            ]);
            exit;
        }
        
        $requestId = (int)$data['request_id'];
        
        // Get request details
        $request = fetchRow("SELECT * FROM service_requests WHERE id = ?", [$requestId]);
        
        if (!$request) {
            echo json_encode([
                'success' => false,
                'message' => __('request_not_found')
            ]);
            exit;
        }
        
        // Check if user owns the request or is an admin
        if ($request['user_id'] !== $currentUser['id'] && $currentUser['user_type'] !== 'admin') {
            echo json_encode([
                'success' => false,
                'message' => __('not_request_owner')
            ]);
            exit;
        }
        
        // Check if request can be cancelled
        if ($request['status'] !== 'requested' && $request['status'] !== 'accepted') {
            echo json_encode([
                'success' => false,
                'message' => __('cannot_cancel_request_in_state') . ' ' . __($request['status'])
            ]);
            exit;
        }
        
        // Update request status
        $sql = "UPDATE service_requests SET status = 'cancelled', cancelled_at = NOW() WHERE id = ?";
        $result = updateData($sql, [$requestId]);
        
        if (!$result) {
            echo json_encode([
                'success' => false,
                'message' => __('cancel_failed')
            ]);
            exit;
        }
        
        // Log activity
        logActivity($currentUser['id'], 'cancel_service_request', [
            'request_id' => $requestId
        ]);
        
        // Notify provider if assigned
        if ($request['provider_id']) {
            createProviderCancellationNotification($request['provider_id'], $requestId);
        }
        
        echo json_encode([
            'success' => true,
            'message' => __('request_cancelled')
        ]);
    }
    // Provider actions (for authority and service provider roles)
    elseif (in_array($action, ['accept_request', 'complete_request', 'reject_request'])) {
        // Must be a provider or authority
        if ($currentUser['user_type'] !== 'authority' && $currentUser['user_type'] !== 'admin') {
            echo json_encode([
                'success' => false,
                'message' => __('permission_denied')
            ]);
            exit;
        }
        
        // Validate inputs
        if (!isset($data['request_id']) || empty($data['request_id'])) {
            echo json_encode([
                'success' => false,
                'message' => __('request_id_required')
            ]);
            exit;
        }
        
        $requestId = (int)$data['request_id'];
        
        // Get request details
        $request = fetchRow("SELECT * FROM service_requests WHERE id = ?", [$requestId]);
        
        if (!$request) {
            echo json_encode([
                'success' => false,
                'message' => __('request_not_found')
            ]);
            exit;
        }
        
        // Process based on action
        if ($action === 'accept_request') {
            // Only accept if in requested state
            if ($request['status'] !== 'requested') {
                echo json_encode([
                    'success' => false,
                    'message' => __('cannot_accept_request_in_state') . ' ' . __($request['status'])
                ]);
                exit;
            }
            
            // Get provider ID from data or use current user (authority acts as provider)
            $providerId = isset($data['provider_id']) ? (int)$data['provider_id'] : null;
            
            if (!$providerId) {
                // Create a temporary provider record for the authority
                $providerId = createTemporaryProvider($currentUser['id']);
                
                if (!$providerId) {
                    echo json_encode([
                        'success' => false,
                        'message' => __('provider_creation_failed')
                    ]);
                    exit;
                }
            }
            
            // Update request status
            $sql = "UPDATE service_requests SET status = 'accepted', provider_id = ?, accepted_at = NOW() WHERE id = ?";
            $result = updateData($sql, [$providerId, $requestId]);
            
            if (!$result) {
                echo json_encode([
                    'success' => false,
                    'message' => __('accept_failed')
                ]);
                exit;
            }
            
            // Notify user
            createRequestAcceptedNotification($request['user_id'], $requestId);
            
            // Log activity
            logActivity($currentUser['id'], 'accept_service_request', [
                'request_id' => $requestId,
                'provider_id' => $providerId
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => __('request_accepted')
            ]);
        }
        elseif ($action === 'complete_request') {
            // Only complete if in accepted or in_progress state
            if ($request['status'] !== 'accepted' && $request['status'] !== 'in_progress') {
                echo json_encode([
                    'success' => false,
                    'message' => __('cannot_complete_request_in_state') . ' ' . __($request['status'])
                ]);
                exit;
            }
            
            // Update request status
            $sql = "UPDATE service_requests SET status = 'completed', completed_at = NOW() WHERE id = ?";
            $result = updateData($sql, [$requestId]);
            
            if (!$result) {
                echo json_encode([
                    'success' => false,
                    'message' => __('complete_failed')
                ]);
                exit;
            }
            
            // Notify user
            createRequestCompletedNotification($request['user_id'], $requestId);
            
            // Log activity
            logActivity($currentUser['id'], 'complete_service_request', [
                'request_id' => $requestId
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => __('request_completed')
            ]);
        }
        elseif ($action === 'reject_request') {
            // Only reject if in requested state
            if ($request['status'] !== 'requested') {
                echo json_encode([
                    'success' => false,
                    'message' => __('cannot_reject_request_in_state') . ' ' . __($request['status'])
                ]);
                exit;
            }
            
            // Update request - keep status as requested but clear provider
            $sql = "UPDATE service_requests SET provider_id = NULL WHERE id = ?";
            $result = updateData($sql, [$requestId]);
            
            if (!$result) {
                echo json_encode([
                    'success' => false,
                    'message' => __('reject_failed')
                ]);
                exit;
            }
            
            // Log activity
            logActivity($currentUser['id'], 'reject_service_request', [
                'request_id' => $requestId
            ]);
            
            // Try to find a different provider
            findAndAssignNewProvider($requestId, $request['service_type']);
            
            echo json_encode([
                'success' => true,
                'message' => __('request_rejected')
            ]);
        }
    }
    // Submit rating for completed service
    elseif ($action === 'submit_rating') {
        // Validate inputs
        if (!isset($data['request_id']) || empty($data['request_id']) || 
            !isset($data['rating']) || !is_numeric($data['rating'])) {
            echo json_encode([
                'success' => false,
                'message' => __('missing_required_params')
            ]);
            exit;
        }
        
        $requestId = (int)$data['request_id'];
        $rating = (int)$data['rating'];
        $comments = isset($data['comments']) ? sanitizeInput($data['comments']) : '';
        
        // Validate rating value
        if ($rating < 1 || $rating > 5) {
            echo json_encode([
                'success' => false,
                'message' => __('invalid_rating')
            ]);
            exit;
        }
        
        // Get request details
        $request = fetchRow("SELECT * FROM service_requests WHERE id = ?", [$requestId]);
        
        if (!$request) {
            echo json_encode([
                'success' => false,
                'message' => __('request_not_found')
            ]);
            exit;
        }
        
        // Check if user owns the request
        if ($request['user_id'] !== $currentUser['id']) {
            echo json_encode([
                'success' => false,
                'message' => __('not_request_owner')
            ]);
            exit;
        }
        
        // Check if request is completed
        if ($request['status'] !== 'completed') {
            echo json_encode([
                'success' => false,
                'message' => __('cannot_rate_incomplete_request')
            ]);
            exit;
        }
        
        // Check if already rated
        $existingRating = fetchRow("
            SELECT * FROM service_ratings 
            WHERE user_id = ? AND service_request_id = ?
        ", [$currentUser['id'], $requestId]);
        
        if ($existingRating) {
            // Update existing rating
            $sql = "UPDATE service_ratings SET rating = ?, comments = ? WHERE id = ?";
            $result = updateData($sql, [$rating, $comments, $existingRating['id']]);
            
            if (!$result) {
                echo json_encode([
                    'success' => false,
                    'message' => __('rating_update_failed')
                ]);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'message' => __('rating_updated')
            ]);
        } else {
            // Create new rating
            $sql = "INSERT INTO service_ratings (
                        user_id, service_request_id, provider_id, 
                        rating, comments, created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW())";
            
            $result = insertData($sql, [
                $currentUser['id'],
                $requestId,
                $request['provider_id'],
                $rating,
                $comments
            ]);
            
            if (!$result) {
                echo json_encode([
                    'success' => false,
                    'message' => __('rating_failed')
                ]);
                exit;
            }
            
            // Log activity
            logActivity($currentUser['id'], 'rate_service', [
                'request_id' => $requestId,
                'rating' => $rating
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => __('rating_submitted')
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => __('invalid_action')
        ]);
    }
} 
// Handle GET requests
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Get service request details
    if ($action === 'get_request') {
        // Validate inputs
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            echo json_encode([
                'success' => false,
                'message' => __('request_id_required')
            ]);
            exit;
        }
        
        $requestId = (int)$_GET['id'];
        
        // Get request details
        $request = fetchRow("
            SELECT sr.*, 
                   u.full_name as user_name, 
                   u.phone as user_phone,
                   sp.name as provider_name, 
                   sp.phone as provider_phone
            FROM service_requests sr
            JOIN users u ON sr.user_id = u.id
            LEFT JOIN service_providers sp ON sr.provider_id = sp.id
            WHERE sr.id = ?
        ", [$requestId]);
        
        if (!$request) {
            echo json_encode([
                'success' => false,
                'message' => __('request_not_found')
            ]);
            exit;
        }
        
        // Check permission - only requester, provider, or admin/authority can view
        if ($request['user_id'] !== $currentUser['id'] && 
            $currentUser['user_type'] !== 'authority' && 
            $currentUser['user_type'] !== 'admin') {
            echo json_encode([
                'success' => false,
                'message' => __('permission_denied')
            ]);
            exit;
        }
        
        // Get request notes
        $notes = fetchAll("
            SELECT * FROM request_notes 
            WHERE request_id = ? 
            ORDER BY created_at DESC
        ", [$requestId]);
        
        $request['notes'] = $notes;
        
        // Get rating if exists
        $rating = fetchRow("
            SELECT * FROM service_ratings 
            WHERE service_request_id = ?
        ", [$requestId]);
        
        $request['rating'] = $rating;
        
        echo json_encode([
            'success' => true,
            'request' => $request
        ]);
    }
    // Get list of open service requests (for authorities)
    elseif ($action === 'list_open_requests') {
        // Must be an authority or admin
        if ($currentUser['user_type'] !== 'authority' && $currentUser['user_type'] !== 'admin') {
            echo json_encode([
                'success' => false,
                'message' => __('permission_denied')
            ]);
            exit;
        }
        
        // Get filter parameters
        $serviceType = isset($_GET['service_type']) ? sanitizeInput($_GET['service_type']) : '';
        
        // Build query
        $whereConditions = ["sr.status = 'requested'"];
        $params = [];
        
        if ($serviceType) {
            $whereConditions[] = "sr.service_type = ?";
            $params[] = $serviceType;
        }
        
        $whereClause = "WHERE " . implode(" AND ", $whereConditions);
        
        // Get open requests
        $requests = fetchAll("
            SELECT sr.*, 
                   u.full_name as user_name, 
                   u.phone as user_phone
            FROM service_requests sr
            JOIN users u ON sr.user_id = u.id
            $whereClause
            ORDER BY sr.created_at ASC
        ", $params);
        
        echo json_encode([
            'success' => true,
            'requests' => $requests
        ]);
    }
    // Get user's service requests
    elseif ($action === 'my_requests') {
        // Get filter parameters
        $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
        
        // Build query
        $whereConditions = ["sr.user_id = ?"];
        $params = [$currentUser['id']];
        
        if ($status) {
            $whereConditions[] = "sr.status = ?";
            $params[] = $status;
        }
        
        $whereClause = "WHERE " . implode(" AND ", $whereConditions);
        
        // Get user's requests
        $requests = fetchAll("
            SELECT sr.*, 
                   sp.name as provider_name, 
                   sp.phone as provider_phone
            FROM service_requests sr
            LEFT JOIN service_providers sp ON sr.provider_id = sp.id
            $whereClause
            ORDER BY sr.created_at DESC
        ", $params);
        
        echo json_encode([
            'success' => true,
            'requests' => $requests
        ]);
    }
    // Get provider's assigned requests
    elseif ($action === 'assigned_requests') {
        // Must be an authority or admin
        if ($currentUser['user_type'] !== 'authority' && $currentUser['user_type'] !== 'admin') {
            echo json_encode([
                'success' => false,
                'message' => __('permission_denied')
            ]);
            exit;
        }
        
        // Get filter parameters
        $providerId = isset($_GET['provider_id']) ? (int)$_GET['provider_id'] : null;
        $status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
        
        // Build query
        $whereConditions = [];
        $params = [];
        
        if ($providerId) {
            $whereConditions[] = "sr.provider_id = ?";
            $params[] = $providerId;
        }
        
        if ($status) {
            $whereConditions[] = "sr.status = ?";
            $params[] = $status;
        } else {
            // If no status specified, show active ones
            $whereConditions[] = "sr.status IN ('accepted', 'in_progress')";
        }
        
        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Get assigned requests
        $requests = fetchAll("
            SELECT sr.*, 
                   u.full_name as user_name, 
                   u.phone as user_phone,
                   sp.name as provider_name
            FROM service_requests sr
            JOIN users u ON sr.user_id = u.id
            LEFT JOIN service_providers sp ON sr.provider_id = sp.id
            $whereClause
            ORDER BY sr.created_at ASC
        ", $params);
        
        echo json_encode([
            'success' => true,
            'requests' => $requests
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => __('invalid_action')
        ]);
    }
} else {
    // Unsupported method
    echo json_encode([
        'success' => false,
        'message' => __('method_not_supported')
    ]);
}

/**
 * Find a suitable service provider
 * 
 * @param string $serviceType Type of service requested
 * @param float|null $latitude User latitude
 * @param float|null $longitude User longitude
 * @return array|null Provider data or null if none found
 */
function findSuitableProvider($serviceType, $latitude = null, $longitude = null) {
    // Basic query to find available providers of the requested type
    $sql = "SELECT * FROM service_providers 
            WHERE service_type = ? AND available = 1 ";
    $params = [$serviceType];
    
    // If location is provided, order by proximity
    if ($latitude && $longitude) {
        $sql .= "ORDER BY 
                (POW(latitude - ?, 2) + POW(longitude - ?, 2)) ASC, 
                last_location_update DESC ";
        $params[] = $latitude;
        $params[] = $longitude;
    } else {
        $sql .= "ORDER BY last_location_update DESC ";
    }
    
    $sql .= "LIMIT 1";
    
    return fetchRow($sql, $params);
}

/**
 * Find and assign a new provider for a request
 * 
 * @param int $requestId Request ID
 * @param string $serviceType Service type
 * @return bool True if provider assigned, false otherwise
 */
function findAndAssignNewProvider($requestId, $serviceType) {
    // Get request location
    $request = fetchRow("SELECT latitude, longitude FROM service_requests WHERE id = ?", [$requestId]);
    
    if (!$request) {
        return false;
    }
    
    // Find suitable provider
    $provider = findSuitableProvider($serviceType, $request['latitude'], $request['longitude']);
    
    if (!$provider) {
        return false;
    }
    
    // Assign provider to request
    $sql = "UPDATE service_requests SET provider_id = ? WHERE id = ?";
    $result = updateData($sql, [$provider['id'], $requestId]);
    
    if (!$result) {
        return false;
    }
    
    // Create notification for provider
    createProviderNotification($provider['id'], $requestId, $serviceType);
    
    return true;
}

/**
 * Create a temporary service provider for an authority
 * 
 * @param int $authorityId Authority user ID
 * @return int|false Provider ID or false on failure
 */
function createTemporaryProvider($authorityId) {
    // Get authority details
    $authority = fetchRow("SELECT * FROM users WHERE id = ?", [$authorityId]);
    
    if (!$authority) {
        return false;
    }
    
    // Check if provider already exists for this authority
    $provider = fetchRow("
        SELECT * FROM service_providers 
        WHERE name = ? AND email = ?
    ", ["Authority: " . $authority['full_name'], $authority['email']]);
    
    if ($provider) {
        return $provider['id'];
    }
    
    // Create new provider
    $sql = "INSERT INTO service_providers (
                name, service_type, phone, email, license_number, 
                available, created_at, updated_at
            ) VALUES (?, 'other', ?, ?, ?, 1, NOW(), NOW())";
    
    return insertData($sql, [
        "Authority: " . $authority['full_name'],
        $authority['phone'],
        $authority['email'],
        "AUTH-" . $authorityId
    ]);
}

/**
 * Create notification for service provider
 * 
 * @param int $providerId Provider ID
 * @param int $requestId Request ID
 * @param string $serviceType Service type
 * @return void
 */
function createProviderNotification($providerId, $requestId, $serviceType) {
    // Get provider details to find associated user
    $provider = fetchRow("SELECT * FROM service_providers WHERE id = ?", [$providerId]);
    
    if (!$provider) {
        return;
    }
    
    // For now, notify all authorities since there's no direct provider-user mapping
    $authorities = fetchAll("SELECT id FROM users WHERE role = 'authority'");
    
    if (!$authorities) {
        return;
    }
    
    foreach ($authorities as $authority) {
        $sql = "INSERT INTO notifications (
                    user_id, type, title, message, data, is_read, created_at
                ) VALUES (?, 'service_request', ?, ?, ?, 0, NOW())";
        
        insertData($sql, [
            $authority['id'],
            __('new_service_request'),
            sprintf(__('new_service_request_of_type'), __($serviceType)),
            json_encode(['request_id' => $requestId, 'provider_id' => $providerId])
        ]);
    }
}

/**
 * Create notification about request cancellation
 * 
 * @param int $providerId Provider ID
 * @param int $requestId Request ID
 * @return void
 */
function createProviderCancellationNotification($providerId, $requestId) {
    // Same approach as createProviderNotification - notify authorities
    $authorities = fetchAll("SELECT id FROM users WHERE role = 'authority'");
    
    if (!$authorities) {
        return;
    }
    
    foreach ($authorities as $authority) {
        $sql = "INSERT INTO notifications (
                    user_id, type, title, message, data, is_read, created_at
                ) VALUES (?, 'service_cancelled', ?, ?, ?, 0, NOW())";
        
        insertData($sql, [
            $authority['id'],
            __('service_request_cancelled'),
            __('service_request_cancelled_message'),
            json_encode(['request_id' => $requestId, 'provider_id' => $providerId])
        ]);
    }
}

/**
 * Notify authorities about a new service request
 * 
 * @param int $requestId Request ID
 * @param string $serviceType Service type
 * @return void
 */
function notifyAuthoritiesAboutRequest($requestId, $serviceType) {
    $authorities = fetchAll("SELECT id FROM users WHERE role = 'authority'");
    
    if (!$authorities) {
        return;
    }
    
    foreach ($authorities as $authority) {
        $sql = "INSERT INTO notifications (
                    user_id, type, title, message, data, is_read, created_at
                ) VALUES (?, 'unassigned_request', ?, ?, ?, 0, NOW())";
        
        insertData($sql, [
            $authority['id'],
            __('unassigned_service_request'),
            sprintf(__('unassigned_service_request_of_type'), __($serviceType)),
            json_encode(['request_id' => $requestId])
        ]);
    }
}

/**
 * Create notification about request acceptance
 * 
 * @param int $userId User ID
 * @param int $requestId Request ID
 * @return void
 */
function createRequestAcceptedNotification($userId, $requestId) {
    $sql = "INSERT INTO notifications (
                user_id, type, title, message, data, is_read, created_at
            ) VALUES (?, 'request_accepted', ?, ?, ?, 0, NOW())";
    
    insertData($sql, [
        $userId,
        __('service_request_accepted'),
        __('service_request_accepted_message'),
        json_encode(['request_id' => $requestId])
    ]);
}

/**
 * Create notification about request completion
 * 
 * @param int $userId User ID
 * @param int $requestId Request ID
 * @return void
 */
function createRequestCompletedNotification($userId, $requestId) {
    $sql = "INSERT INTO notifications (
                user_id, type, title, message, data, is_read, created_at
            ) VALUES (?, 'request_completed', ?, ?, ?, 0, NOW())";
    
    insertData($sql, [
        $userId,
        __('service_request_completed'),
        __('service_request_completed_message'),
        json_encode(['request_id' => $requestId])
    ]);
}
?>