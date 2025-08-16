<?php
// Require admin authentication
require_once('../includes/auth_check.php');
// Set page title
$pageTitle = __('service_management');

// Check admin/authority access
if (!in_array($currentUser['user_type'], ['admin', 'authority'])) {
    setFlashMessage('danger', __('admin_authority_access_required'));
    redirect(SITE_URL . '/dashboard/' . $currentUser['user_type'] . '.php');
}

// Service types configuration
$serviceTypes = [
    'cart' => ['icon' => 'fa-shopping-cart', 'color' => 'primary'],
    'wheelchair' => ['icon' => 'fa-wheelchair', 'color' => 'success'],
    'guide' => ['icon' => 'fa-user-tie', 'color' => 'info'],
    'medical' => ['icon' => 'fa-medkit', 'color' => 'danger'],
    'transport' => ['icon' => 'fa-car', 'color' => 'warning'],
    'other' => ['icon' => 'fa-cogs', 'color' => 'secondary']
];

// Status configuration
$statusTypes = [
    'requested' => ['color' => 'warning'],
    'accepted' => ['color' => 'primary'],
    'in_progress' => ['color' => 'info'],
    'completed' => ['color' => 'success'],
    'cancelled' => ['color' => 'danger']
];

// Get action
$action = $_GET['action'] ?? 'list';

// Process actions
switch ($action) {
    case 'edit_provider':
    case 'view_provider':
        handleProviderViewEdit();
        break;
    case 'create_provider':
        handleProviderCreate();
        break;
    case 'delete_provider':
        handleProviderDelete();
        break;
    case 'request_details':
        handleRequestDetails();
        break;
    default:
        handleDefaultList();
}

// Include header
include_once('../includes/header.php');

// Display appropriate view based on action
switch ($action) {
    case 'view_provider':
        displayProviderView();
        break;
    case 'edit_provider':
        displayProviderEdit();
        break;
    case 'create_provider':
        displayProviderCreate();
        break;
    case 'delete_provider':
        displayProviderDelete();
        break;
    case 'request_details':
        displayRequestDetails();
        break;
    default:
        displayDefaultView();
}

// Include footer
include_once('../includes/footer.php');

// ==================== FUNCTIONS ====================

function handleProviderViewEdit() {
    global $provider, $serviceRequests, $action;
    
    $providerId = (int)($_GET['id'] ?? 0);
    if (!$providerId) {
        setFlashMessage('danger', __('invalid_provider_id'));
        redirectBack();
    }
    
    $provider = getProvider($providerId);
    $serviceRequests = getProviderRequests($providerId);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit_provider') {
        updateProvider($providerId);
    }
}

function handleProviderCreate() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        createProvider();
    }
}

function handleProviderDelete() {
    global $provider;
    
    $providerId = (int)($_GET['id'] ?? 0);
    if (!$providerId) {
        setFlashMessage('danger', __('invalid_provider_id'));
        redirectBack();
    }
    
    $provider = getProvider($providerId);
    
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        deleteProvider($providerId);
    }
}

function handleRequestDetails() {
    global $request, $notes, $rating, $availableProviders;
    
    $requestId = (int)($_GET['id'] ?? 0);
    if (!$requestId) {
        setFlashMessage('danger', __('invalid_request_id'));
        redirectBack();
    }
    
    $request = getRequestDetails($requestId);
    $notes = getRequestNotes($requestId);
    $rating = getRequestRating($requestId);
    $availableProviders = getAvailableProviders($request['service_type']);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
        updateRequestStatus($requestId);
    }
}

function handleDefaultList() {
    global $providers, $requests, $providerTotal, $requestTotal, $page, $perPage;
    
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 10;
    
    // Get providers with filters
    $providerFilters = [
        'search' => $_GET['provider_search'] ?? '',
        'type' => $_GET['provider_type'] ?? '',
        'available' => $_GET['provider_available'] ?? ''
    ];
    
    $providers = getFilteredProviders($providerFilters, $page, $perPage);
    $providerTotal = getProvidersCount($providerFilters);
    
    // Get requests with filters
    $requestFilters = [
        'search' => $_GET['request_search'] ?? '',
        'type' => $_GET['request_type'] ?? '',
        'status' => $_GET['request_status'] ?? ''
    ];
    
    $requests = getFilteredRequests($requestFilters, $page, $perPage);
    $requestTotal = getRequestsCount($requestFilters);
}

function getProvider($id) {
    $provider = fetchRow("SELECT * FROM service_providers WHERE id = ?", [$id]);
    if (!$provider) {
        setFlashMessage('danger', __('provider_not_found'));
        redirectBack();
    }
    return $provider;
}

function getProviderRequests($providerId) {
    return fetchAll("
        SELECT sr.*, u.full_name as user_name
        FROM service_requests sr
        JOIN users u ON sr.user_id = u.id
        WHERE sr.provider_id = ?
        ORDER BY sr.created_at DESC
        LIMIT 20
    ", [$providerId]);
}
function updateProvider($providerId) {
    $data = [
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'service_type' => sanitizeInput($_POST['service_type'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'license_number' => sanitizeInput($_POST['license_number'] ?? ''),
        'available' => isset($_POST['available']) ? 1 : 0
    ];
    
    // Validate inputs
    $errors = [];
    if (empty($data['name'])) $errors[] = __('provider_name_required');
    if (!array_key_exists($data['service_type'], $GLOBALS['serviceTypes'])) $errors[] = __('invalid_service_type');
    
    if (empty($errors)) {
        $sql = "UPDATE service_providers SET 
                name = ?, service_type = ?, phone = ?, 
                email = ?, license_number = ?, available = ?,
                updated_at = NOW()
                WHERE id = ?";
    if (updateData($sql, array_values(array_merge($data, [$providerId])))) {
        logActivity(
            $GLOBALS['currentUser']['id'], 
            'update_service_provider', 
            ['provider_id' => $providerId]
        );
        setFlashMessage('success', __('provider_updated'));
        redirect(SITE_URL . '/admin/service_management.php?action=view_provider&id=' . $providerId);
    } else {
            setFlashMessage('danger', __('provider_update_failed'));
        }
    } else {
        setFlashMessage('danger', implode('<br>', $errors));
    }
}
function createProvider() {
    $data = [
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'service_type' => sanitizeInput($_POST['service_type'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'license_number' => sanitizeInput($_POST['license_number'] ?? ''),
        'available' => isset($_POST['available']) ? 1 : 0
    ];
    
    // Validate inputs
    $errors = [];
    if (empty($data['name'])) $errors[] = __('provider_name_required');
    if (!array_key_exists($data['service_type'], $GLOBALS['serviceTypes'])) $errors[] = __('invalid_service_type');
    
    if (empty($errors)) {
        $sql = "INSERT INTO service_providers (
                name, service_type, phone, email, 
                license_number, available, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $providerId = insertData($sql, array_values($data));
        
        if ($providerId) {
            logActivity($GLOBALS['currentUser']['id'], 'create_service_provider', ['provider_id' => $providerId]);
            setFlashMessage('success', __('provider_created'));
            redirect(SITE_URL . '/admin/service_management.php?action=view_provider&id=' . $providerId);
        } else {
            setFlashMessage('danger', __('provider_creation_failed'));
        }
    } else {
        setFlashMessage('danger', implode('<br>', $errors));
    }
}

function deleteProvider($providerId) {
    // Check for active requests
    $activeRequests = fetchRow("
        SELECT COUNT(*) as count 
        FROM service_requests 
        WHERE provider_id = ? AND status IN ('requested', 'accepted', 'in_progress')
    ", [$providerId]);
    
    if ($activeRequests && $activeRequests['count'] > 0) {
        setFlashMessage('danger', __('cannot_delete_provider_with_active_requests'));
        redirectBack();
    }
    
    beginTransaction();
    try {
        // Delete provider
        executeQuery("DELETE FROM service_providers WHERE id = ?", [$providerId]);
        
        // Clear provider from requests
        executeQuery("UPDATE service_requests SET provider_id = NULL WHERE provider_id = ?", [$providerId]);
        
        commitTransaction();
        
        logActivity($GLOBALS['currentUser']['id'], 'delete_service_provider', ['provider_id' => $providerId]);
        setFlashMessage('success', __('provider_deleted'));
        redirect(SITE_URL . '/admin/service_management.php');
    } catch (Exception $e) {
        rollbackTransaction();
        setFlashMessage('danger', __('provider_deletion_failed'));
        redirectBack();
    }
}

function getRequestDetails($requestId) {
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
        setFlashMessage('danger', __('request_not_found'));
        redirectBack();
    }
    
    return $request;
}

function getRequestNotes($requestId) {
    return fetchAll("
        SELECT * FROM request_notes 
        WHERE request_id = ? 
        ORDER BY created_at DESC
    ", [$requestId]);
}

function getRequestRating($requestId) {
    return fetchRow("
        SELECT * FROM service_ratings 
        WHERE service_request_id = ?
    ", [$requestId]);
}

function getAvailableProviders($serviceType) {
    return fetchAll("
        SELECT * FROM service_providers 
        WHERE service_type = ? AND available = 1
        ORDER BY name
    ", [$serviceType]);
}

function updateRequestStatus($requestId) {
    $status = sanitizeInput($_POST['status'] ?? '');
    $providerId = isset($_POST['provider_id']) ? (int)$_POST['provider_id'] : null;
    $note = sanitizeInput($_POST['note'] ?? '');
    
    // Validate status
    if (!array_key_exists($status, $GLOBALS['statusTypes'])) {
        setFlashMessage('danger', __('invalid_status'));
        redirectBack();
    }
    
    beginTransaction();
    try {
        // Update fields based on status
        $fields = ['status = ?'];
        $params = [$status];
        
        if ($status === 'accepted') {
            if (!$providerId) {
                throw new Exception(__('provider_required_for_accepted_status'));
            }
            
            $fields[] = 'provider_id = ?';
            $fields[] = 'accepted_at = NOW()';
            $params[] = $providerId;
            
            // Mark provider as busy
            executeQuery("UPDATE service_providers SET available = 0 WHERE id = ?", [$providerId]);
        } elseif ($status === 'completed') {
            $fields[] = 'completed_at = NOW()';
            
            // Mark provider as available if assigned
            $request = getRequestDetails($requestId);
            if ($request['provider_id']) {
                executeQuery("UPDATE service_providers SET available = 1 WHERE id = ?", [$request['provider_id']]);
            }
        } elseif ($status === 'cancelled') {
            $fields[] = 'cancelled_at = NOW()';
            
            // Mark provider as available if assigned
            $request = getRequestDetails($requestId);
            if ($request['provider_id']) {
                executeQuery("UPDATE service_providers SET available = 1 WHERE id = ?", [$request['provider_id']]);
            }
        }
        
        // Build update query
        $sql = "UPDATE service_requests SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $requestId;
        
        // Update request
        if (!updateData($sql, $params)) {
            throw new Exception(__('status_update_failed'));
        }
        
        // Add note if provided
        if (!empty($note)) {
            $sql = "INSERT INTO request_notes (request_id, notes, created_at)
                    VALUES (?, ?, NOW())";
            
            if (!insertData($sql, [$requestId, $note])) {
                throw new Exception(__('note_addition_failed'));
            }
        }
        
        commitTransaction();
        
        logActivity($GLOBALS['currentUser']['id'], 'update_service_request', [
            'request_id' => $requestId, 
            'status' => $status
        ]);
        
        createRequestStatusNotification($GLOBALS['request']['user_id'], $requestId, $status);
        
        setFlashMessage('success', __('request_updated'));
        redirectBack();
    } catch (Exception $e) {
        rollbackTransaction();
        setFlashMessage('danger', $e->getMessage());
        redirectBack();
    }
}

function getFilteredProviders($filters, $page, $perPage) {
    $whereConditions = [];
    $params = [];
    
    if (!empty($filters['search'])) {
        $search = "%{$filters['search']}%";
        $whereConditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR license_number LIKE ?)";
        array_push($params, $search, $search, $search, $search);
    }
    
    if (!empty($filters['type'])) {
        $whereConditions[] = "service_type = ?";
        $params[] = $filters['type'];
    }
    
    if ($filters['available'] === '1') {
        $whereConditions[] = "available = 1";
    } elseif ($filters['available'] === '0') {
        $whereConditions[] = "available = 0";
    }
    
    $whereClause = empty($whereConditions) ? '' : "WHERE " . implode(" AND ", $whereConditions);
    $offset = ($page - 1) * $perPage;
    
    return fetchAll("
        SELECT * FROM service_providers
        $whereClause
        ORDER BY name
        LIMIT $offset, $perPage
    ", $params);
}

function getProvidersCount($filters) {
    $whereConditions = [];
    $params = [];
    
    if (!empty($filters['search'])) {
        $search = "%{$filters['search']}%";
        $whereConditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR license_number LIKE ?)";
        array_push($params, $search, $search, $search, $search);
    }
    
    if (!empty($filters['type'])) {
        $whereConditions[] = "service_type = ?";
        $params[] = $filters['type'];
    }
    
    if ($filters['available'] === '1') {
        $whereConditions[] = "available = 1";
    } elseif ($filters['available'] === '0') {
        $whereConditions[] = "available = 0";
    }
    
    $whereClause = empty($whereConditions) ? '' : "WHERE " . implode(" AND ", $whereConditions);
    
    $result = fetchRow("SELECT COUNT(*) as total FROM service_providers $whereClause", $params);
    return $result ? $result['total'] : 0;
}

function getFilteredRequests($filters, $page, $perPage) {
    $whereConditions = [];
    $params = [];
    
    if (!empty($filters['search'])) {
        $search = "%{$filters['search']}%";
        $whereConditions[] = "(u.full_name LIKE ? OR sr.pickup_location LIKE ? OR sr.destination LIKE ?)";
        array_push($params, $search, $search, $search);
    }
    
    if (!empty($filters['type'])) {
        $whereConditions[] = "sr.service_type = ?";
        $params[] = $filters['type'];
    }
    
    if (!empty($filters['status'])) {
        $whereConditions[] = "sr.status = ?";
        $params[] = $filters['status'];
    }
    
    $whereClause = empty($whereConditions) ? '' : "WHERE " . implode(" AND ", $whereConditions);
    $offset = ($page - 1) * $perPage;
    
    return fetchAll("
        SELECT sr.*, 
               u.full_name as user_name,
               sp.name as provider_name
        FROM service_requests sr
        JOIN users u ON sr.user_id = u.id
        LEFT JOIN service_providers sp ON sr.provider_id = sp.id
        $whereClause
        ORDER BY sr.created_at DESC
        LIMIT $offset, $perPage
    ", $params);
}

function getRequestsCount($filters) {
    $whereConditions = [];
    $params = [];
    
    if (!empty($filters['search'])) {
        $search = "%{$filters['search']}%";
        $whereConditions[] = "(u.full_name LIKE ? OR sr.pickup_location LIKE ? OR sr.destination LIKE ?)";
        array_push($params, $search, $search, $search);
    }
    
    if (!empty($filters['type'])) {
        $whereConditions[] = "sr.service_type = ?";
        $params[] = $filters['type'];
    }
    
    if (!empty($filters['status'])) {
        $whereConditions[] = "sr.status = ?";
        $params[] = $filters['status'];
    }
    
    $whereClause = empty($whereConditions) ? '' : "WHERE " . implode(" AND ", $whereConditions);
    
    $result = fetchRow("
        SELECT COUNT(*) as total 
        FROM service_requests sr
        JOIN users u ON sr.user_id = u.id
        $whereClause
    ", $params);
    
    return $result ? $result['total'] : 0;
}

function createRequestStatusNotification($userId, $requestId, $status) {
    $title = sprintf(__('service_request_status_updated'), $requestId);
    $message = sprintf(__('service_request_status_message'), __($status));
    
    $sql = "INSERT INTO notifications (
                user_id, type, title, message, data, is_read, created_at
            ) VALUES (?, ?, ?, ?, ?, 0, NOW())";
    
    insertData($sql, [
        $userId,
        'service_update',
        $title,
        $message,
        json_encode(['request_id' => $requestId, 'status' => $status])
    ]);
}

function redirectBack() {
    redirect($_SERVER['HTTP_REFERER'] ?? SITE_URL . '/admin/service_management.php');
}

// ==================== VIEWS ====================
function displayDefaultView() {
    global $providers, $requests, $providerTotal, $requestTotal, $page, $perPage, $serviceTypes, $statusTypes;
    ?>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fas fa-concierge-bell me-2"></i> <?= __('service_management') ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <a href="<?= SITE_URL ?>/admin/dashboard.php" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i> <?= __('back_to_dashboard') ?>
                </a>
                <a href="<?= SITE_URL ?>/admin/service_management.php?action=create_provider" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i> <?= __('add_provider') ?>
                </a>
            </div>
        </div>
        
        <!-- Tabs for Providers and Requests -->
        <ul class="nav nav-tabs mb-4" id="serviceTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="providers-tab" 
                        data-bs-toggle="tab" data-bs-target="#providers" 
                        type="button" role="tab" aria-controls="providers" aria-selected="true">
                    <i class="fas fa-user-tie me-1"></i> <?= __('service_providers') ?>
                    <span class="badge bg-primary ms-1"><?= $providerTotal ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="requests-tab" 
                        data-bs-toggle="tab" data-bs-target="#requests" 
                        type="button" role="tab" aria-controls="requests" aria-selected="false">
                    <i class="fas fa-tasks me-1"></i> <?= __('service_requests') ?>
                    <span class="badge bg-primary ms-1"><?= $requestTotal ?></span>
                </button>
            </li>
        </ul>
        
        <div class="tab-content" id="serviceTabContent">
            <!-- Service Providers Tab -->
            <div class="tab-pane fade show active" id="providers" role="tabpanel" aria-labelledby="providers-tab">
                <?php include('../partials/provider_list.php') ?>
            </div>
            
            <!-- Service Requests Tab -->
            <div class="tab-pane fade" id="requests" role="tabpanel" aria-labelledby="requests-tab">
                <?php include('../partials/request_list.php') ?>
            </div>
        </div>
    </div>

    <!-- Add this JavaScript right after the HTML -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap tabs
        const tabElms = document.querySelectorAll('button[data-bs-toggle="tab"]');
        
        tabElms.forEach(tabEl => {
            tabEl.addEventListener('click', function(e) {
                e.preventDefault();
                const tab = new bootstrap.Tab(this);
                tab.show();
                
                // Update URL with active tab
                const tabId = this.getAttribute('aria-controls');
                const url = new URL(window.location.href);
                url.searchParams.set('tab', tabId);
                window.history.pushState({}, '', url);
            });
        });
        
        // Activate tab from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab') || 'providers';
        
        if(activeTab === 'requests') {
            const requestsTab = document.querySelector('#requests-tab');
            if(requestsTab) {
                const tab = new bootstrap.Tab(requestsTab);
                tab.show();
            }
        }
    });
    </script>
    <?php
}

function displayProviderView() {
    global $provider, $serviceRequests, $serviceTypes;
    ?>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-4">
                <?php include('../partials/provider_card.php') ?>
            </div>
            
            <div class="col-md-8">
                <?php include('../partials/provider_requests.php') ?>
            </div>
        </div>
    </div>
    <?php
}

function displayProviderEdit() {
    global $provider, $serviceTypes;
    ?>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-lg-8 col-md-10 mx-auto">
                <?php include('../partials/provider_form.php') ?>
            </div>
        </div>
    </div>
    <?php
}

function displayProviderCreate() {
    global $serviceTypes;
    ?>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-lg-8 col-md-10 mx-auto">
                <?php include('../partials/provider_form.php') ?>
            </div>
        </div>
    </div>
    <?php
}

function displayProviderDelete() {
    global $provider;
    ?>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-6 mx-auto">
                <?php include('../partials/provider_delete.php') ?>
            </div>
        </div>
    </div>
    <?php
}

function displayRequestDetails() {
    global $request, $notes, $rating, $availableProviders, $serviceTypes, $statusTypes;
    ?>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-4">
                <?php include('../partials/request_card.php') ?>
            </div>
            
            <div class="col-md-8">
                <?php include('../partials/request_management.php') ?>
            </div>
        </div>
    </div>
    <?php
}