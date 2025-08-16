<?php
// Set page title

require_once('../includes/auth_check.php');

$pageTitle = __('authority_dashboard');

// Use map
$useLeaflet = true;
// Use charts
$useCharts = true;

// Include header
include_once('../includes/header.php');

// Check if user is an authority
if ($currentUser['user_type'] !== 'authority' && $currentUser['user_type'] !== 'admin') {
    // Redirect to appropriate dashboard
    redirect(SITE_URL . '/index.php');
}

// Get dashboard statistics
$stats = [
    'active_users' => fetchRow("SELECT COUNT(*) as count FROM users WHERE active = 1")['count'],
    'emergencies' => fetchRow("SELECT COUNT(*) as count FROM emergencies WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'],
    'unresolved_emergencies' => fetchRow("SELECT COUNT(*) as count FROM emergencies WHERE status != 'resolved' AND status != 'cancelled'")['count'],
    'service_requests' => fetchRow("SELECT COUNT(*) as count FROM service_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'],
    'pending_services' => fetchRow("SELECT COUNT(*) as count FROM service_requests WHERE status = 'requested'")['count'],
    'locations_24h' => fetchRow("SELECT COUNT(*) as count FROM locations WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count']
];

// Get recent emergency reports
$recentEmergencies = fetchAll("
    SELECT e.*, u.full_name as reporter_name, u.id as reporter_id, u.phone as reporter_phone,
           (SELECT full_name FROM users WHERE id = e.handled_by) as handler_name
    FROM emergencies e
    JOIN users u ON e.reporter_id = u.id
    ORDER BY e.created_at DESC
    LIMIT 10
");

// Get unresolved emergency reports
$unresolvedEmergencies = fetchAll("
    SELECT e.*, u.full_name as reporter_name, u.id as reporter_id, u.phone as reporter_phone,
           (SELECT full_name FROM users WHERE id = e.handled_by) as handler_name
    FROM emergencies e
    JOIN users u ON e.reporter_id = u.id
    WHERE e.status != 'resolved' AND e.status != 'cancelled'
    ORDER BY 
        CASE 
            WHEN e.status = 'requested' THEN 1
            WHEN e.status = 'in_progress' THEN 2
            ELSE 3
        END,
        e.created_at DESC
    LIMIT 15
");

// Get pending service requests
$pendingServices = fetchAll("
    SELECT sr.*, u.full_name as requester_name, u.id as requester_id, u.phone as requester_phone,
           (SELECT name FROM service_providers WHERE id = sr.provider_id) as provider_name
    FROM service_requests sr
    JOIN users u ON sr.user_id = u.id
    WHERE sr.status = 'requested'
    ORDER BY sr.created_at ASC
    LIMIT 10
");

// Get active service providers
$activeProviders = fetchAll("
    SELECT * FROM service_providers 
    WHERE available = 1
    ORDER BY service_type, name
");

// Get emergency location data for heat map
$emergencyLocations = fetchAll("
    SELECT latitude, longitude, type 
    FROM emergencies 
    WHERE latitude IS NOT NULL AND longitude IS NOT NULL
    AND created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
");

// Get users with recent location updates
$activeUsers = fetchAll("
    SELECT u.id, u.full_name, u.role, u.phone, u.profile_image,
           l.latitude, l.longitude, l.timestamp
    FROM users u
    JOIN (
        SELECT user_id, MAX(timestamp) as max_time
        FROM locations
        GROUP BY user_id
    ) latest_loc ON u.id = latest_loc.user_id
    JOIN locations l ON latest_loc.user_id = l.user_id AND latest_loc.max_time = l.timestamp
    WHERE l.timestamp >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
    ORDER BY l.timestamp DESC
    LIMIT 100
");

// Get emergency statistics by type
$emergencyStats = fetchAll("
    SELECT type, COUNT(*) as count 
    FROM emergencies 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY type
");

// Get service statistics by type
$serviceStats = fetchAll("
    SELECT service_type, COUNT(*) as count 
    FROM service_requests 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY service_type
");

// Get hourly emergency distribution
$hourlyEmergencies = fetchAll("
    SELECT HOUR(created_at) as hour, COUNT(*) as count 
    FROM emergencies 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY HOUR(created_at)
    ORDER BY hour
");

?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-8">
            <h1><i class="fas fa-user-tie me-2"></i> <?php echo __('authority_dashboard'); ?></h1>
            <p class="lead"><?php echo __('authority_dashboard_description'); ?></p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <button type="button" class="btn btn-outline-primary me-2" id="refreshData">
                <i class="fas fa-sync-alt me-1"></i> <?php echo __('refresh'); ?>
            </button>
            <a href="<?php echo SITE_URL; ?>/reports/index.php" class="btn btn-primary">
                <i class="fas fa-chart-bar me-1"></i> <?php echo __('detailed_reports'); ?>
            </a>
        </div>
    </div>
    
    <!-- Status Cards -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card h-100 border-left-primary shadow-sm">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                <?php echo __('active_users'); ?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['active_users']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card h-100 border-left-danger shadow-sm">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                <?php echo __('unresolved_emergencies'); ?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['unresolved_emergencies']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card h-100 border-left-warning shadow-sm">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                <?php echo __('pending_services'); ?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['pending_services']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-concierge-bell fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card h-100 border-left-info shadow-sm">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                <?php echo __('emergencies_24h'); ?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['emergencies']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ambulance fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card h-100 border-left-success shadow-sm">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                <?php echo __('services_24h'); ?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['service_requests']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wheelchair fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card h-100 border-left-secondary shadow-sm">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                <?php echo __('locations_24h'); ?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo number_format($stats['locations_24h']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-map-marker-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Left Column -->
        <div class="col-xl-8 col-lg-7">
            <!-- Emergency Locations Map -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i> <?php echo __('emergency_locations'); ?></h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="showAllUsers">
                            <i class="fas fa-users me-1"></i> <?php echo __('show_all_users'); ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="showEmergenciesOnly">
                            <i class="fas fa-exclamation-triangle me-1"></i> <?php echo __('show_emergencies'); ?>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="emergencyMap" class="large-map-container mb-3"></div>
                </div>
            </div>
            
            <!-- Active System Users -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i> <?php echo __('active_users'); ?></h5>
                    <a href="<?php echo SITE_URL; ?>/admin/user_management.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-user-cog me-1"></i> <?php echo __('manage_users'); ?>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="activeUsersTable">
                            <thead>
                                <tr>
                                    <th><?php echo __('name'); ?></th>
                                    <th><?php echo __('role'); ?></th>
                                    <th><?php echo __('contact'); ?></th>
                                    <th><?php echo __('last_active'); ?></th>
                                    <th><?php echo __('location'); ?></th>
                                    <th><?php echo __('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeUsers as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo !empty($user['profile_image']) ? SITE_URL . '/uploads/' . $user['profile_image'] : SITE_URL . '/assets/images/default_avatar.png'; ?>" 
                                                 alt="Profile" class="rounded-circle me-2" width="32" height="32">
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getRoleBadgeClass($user['role']); ?>">
                                            <?php echo $user['role']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['phone']): ?>
                                        <a href="tel:<?php echo $user['phone']; ?>" class="text-decoration-none">
                                            <i class="fas fa-phone-alt me-1"></i> <?php echo htmlspecialchars($user['phone']); ?>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted"><?php echo __('no_phone'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span data-bs-toggle="tooltip" title="<?php echo formatDate($user['timestamp']); ?>">
                                            <?php echo relativeTime($user['timestamp']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['latitude'] && $user['longitude']): ?>
                                        <a href="https://www.google.com/maps?q=<?php echo $user['latitude']; ?>,<?php echo $user['longitude']; ?>" target="_blank" class="text-decoration-none">
                                            <i class="fas fa-map-marker-alt me-1"></i> <?php echo round($user['latitude'], 5); ?>, <?php echo round($user['longitude'], 5); ?>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted"><?php echo __('no_location'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo SITE_URL; ?>/maps/location_share.php?user_id=<?php echo $user['id']; ?>" 
                                               class="btn btn-outline-primary" data-bs-toggle="tooltip" title="<?php echo __('view_location'); ?>">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/profile/view.php?user_id=<?php echo $user['id']; ?>" 
                                               class="btn btn-outline-info" data-bs-toggle="tooltip" title="<?php echo __('view_profile'); ?>">
                                                <i class="fas fa-user"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-success send-message" 
                                                   data-user-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                   data-bs-toggle="tooltip" title="<?php echo __('send_message'); ?>">
                                                <i class="fas fa-comment"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-xl-4 col-lg-5">
            <!-- Emergency Statistics -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> <?php echo __('emergency_statistics'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h6 class="text-muted mb-3"><?php echo __('emergency_types'); ?></h6>
                            <canvas id="emergencyTypesChart" height="180"></canvas>
                        </div>
                        <div class="col-md-6 mb-4">
                            <h6 class="text-muted mb-3"><?php echo __('service_types'); ?></h6>
                            <canvas id="serviceTypesChart" height="180"></canvas>
                        </div>
                    </div>
                    
                    <h6 class="text-muted mb-3"><?php echo __('emergency_hourly_distribution'); ?></h6>
                    <canvas id="emergencyHourlyChart" height="200"></canvas>
                </div>
            </div>
            
            <!-- Unresolved Emergencies -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo __('unresolved_emergencies'); ?></h5>
                    <a href="<?php echo SITE_URL; ?>/emergencies/manage.php" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-list me-1"></i> <?php echo __('view_all'); ?>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($unresolvedEmergencies)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success mb-3" style="font-size: 2rem;"></i>
                            <p><?php echo __('no_unresolved_emergencies'); ?></p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($unresolvedEmergencies as $emergency): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <i class="<?php echo getEmergencyTypeIcon($emergency['type']); ?> me-1"></i>
                                        <?php echo __($emergency['type']); ?> <?php echo __('emergency'); ?>
                                    </h6>
                                    <small class="badge <?php echo getStatusBadgeClass($emergency['status']); ?>">
                                        <?php echo __($emergency['status']); ?>
                                    </small>
                                </div>
                                <p class="mb-1 small text-truncate">
                                    <?php echo htmlspecialchars(substr($emergency['description'], 0, 100)); ?>
                                    <?php echo strlen($emergency['description']) > 100 ? '...' : ''; ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($emergency['reporter_name']); ?>
                                        <i class="fas fa-clock ms-2 me-1"></i> <?php echo relativeTime($emergency['created_at']); ?>
                                    </small>
                                    <div>
                                        <?php if ($emergency['reporter_phone']): ?>
                                        <a href="tel:<?php echo $emergency['reporter_phone']; ?>" class="btn btn-sm btn-outline-success me-1" data-bs-toggle="tooltip" title="<?php echo __('call_reporter'); ?>">
                                            <i class="fas fa-phone-alt"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="<?php echo SITE_URL; ?>/emergencies/manage_emergency.php?id=<?php echo $emergency['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="<?php echo __('manage'); ?>">
                                            <i class="fas fa-clipboard-list"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Pending Service Requests -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-concierge-bell me-2"></i> <?php echo __('pending_services'); ?></h5>
                    <a href="<?php echo SITE_URL; ?>/services/manage.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-list me-1"></i> <?php echo __('view_all'); ?>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if (empty($pendingServices)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success mb-3" style="font-size: 2rem;"></i>
                            <p><?php echo __('no_pending_services'); ?></p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($pendingServices as $service): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <i class="<?php echo getServiceTypeIcon($service['service_type']); ?> me-1"></i>
                                        <?php echo __($service['service_type']); ?> <?php echo __('request'); ?>
                                    </h6>
                                    <small class="badge <?php echo getStatusBadgeClass($service['status']); ?>">
                                        <?php echo __($service['status']); ?>
                                    </small>
                                </div>
                                <div class="mb-1 small">
                                    <span class="text-muted"><?php echo __('pickup'); ?>:</span> <?php echo htmlspecialchars($service['pickup_location']); ?><br>
                                    <span class="text-muted"><?php echo __('destination'); ?>:</span> <?php echo htmlspecialchars($service['destination']); ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($service['requester_name']); ?>
                                        <i class="fas fa-clock ms-2 me-1"></i> <?php echo relativeTime($service['created_at']); ?>
                                    </small>
                                    <div>
                                        <?php if ($service['requester_phone']): ?>
                                        <a href="tel:<?php echo $service['requester_phone']; ?>" class="btn btn-sm btn-outline-success me-1" data-bs-toggle="tooltip" title="<?php echo __('call_requester'); ?>">
                                            <i class="fas fa-phone-alt"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="<?php echo SITE_URL; ?>/services/manage_request.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="<?php echo __('manage'); ?>">
                                            <i class="fas fa-clipboard-list"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i> <?php echo __('quick_links'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <a href="<?php echo SITE_URL; ?>/admin/user_management.php" class="btn btn-outline-primary d-block">
                                <i class="fas fa-users me-1"></i> <?php echo __('manage_users'); ?>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?php echo SITE_URL; ?>/admin/service_management.php" class="btn btn-outline-info d-block">
                                <i class="fas fa-concierge-bell me-1"></i> <?php echo __('manage_services'); ?>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?php echo SITE_URL; ?>/reports/index.php" class="btn btn-outline-success d-block">
                                <i class="fas fa-chart-bar me-1"></i> <?php echo __('reports'); ?>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="<?php echo SITE_URL; ?>/admin/announcements.php" class="btn btn-outline-warning d-block">
                                <i class="fas fa-bullhorn me-1"></i> <?php echo __('announcements'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Send Message Modal -->
<div class="modal fade" id="sendMessageModal" tabindex="-1" aria-labelledby="sendMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sendMessageModalLabel"><?php echo __('send_message_to'); ?> <span id="recipientName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="messageForm">
                    <input type="hidden" id="recipientId" name="recipient_id">
                    <div class="mb-3">
                        <label for="messageSubject" class="form-label"><?php echo __('subject'); ?></label>
                        <input type="text" class="form-control" id="messageSubject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="messageContent" class="form-label"><?php echo __('message'); ?></label>
                        <textarea class="form-control" id="messageContent" name="content" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sendAsNotification" name="send_as_notification" checked>
                            <label class="form-check-label" for="sendAsNotification">
                                <?php echo __('send_as_notification'); ?>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="sendAsUrgent" name="send_as_urgent">
                            <label class="form-check-label" for="sendAsUrgent">
                                <?php echo __('send_as_urgent'); ?>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="sendMessageBtn">
                    <i class="fas fa-paper-plane me-1"></i> <?php echo __('send'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let emergencyMap;
    let userMarkers = [];
    let emergencyMarkers = [];
    let heatmapLayer;
    let userClusterGroup;
    let emergencyClusterGroup;
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize map
        initEmergencyMap();
        
        // Initialize charts
        initEmergencyCharts();
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize active users table
        if (document.getElementById('activeUsersTable')) {
            new DataTable('#activeUsersTable', {
                responsive: true,
                language: {
                    search: "<?php echo __('search'); ?>",
                    lengthMenu: "<?php echo __('show'); ?> _MENU_ <?php echo __('entries'); ?>",
                    info: "<?php echo __('showing'); ?> _START_ <?php echo __('to'); ?> _END_ <?php echo __('of'); ?> _TOTAL_ <?php echo __('entries'); ?>",
                    paginate: {
                        first: "<?php echo __('first'); ?>",
                        last: "<?php echo __('last'); ?>",
                        next: "<?php echo __('next'); ?>",
                        previous: "<?php echo __('previous'); ?>"
                    }
                }
            });
        }
        
        // Setup event listeners
        document.getElementById('refreshData').addEventListener('click', refreshDashboardData);
        document.getElementById('showAllUsers').addEventListener('click', showAllUsers);
        document.getElementById('showEmergenciesOnly').addEventListener('click', showEmergenciesOnly);
        
        // Send Message Modal
        const sendMessageButtons = document.querySelectorAll('.send-message');
        sendMessageButtons.forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const name = this.getAttribute('data-name');
                
                document.getElementById('recipientId').value = userId;
                document.getElementById('recipientName').textContent = name;
                
                const modal = new bootstrap.Modal(document.getElementById('sendMessageModal'));
                modal.show();
            });
        });
        
        document.getElementById('sendMessageBtn').addEventListener('click', sendMessage);
    });
    
    // Initialize Emergency Map
    function initEmergencyMap() {
        // Create map centered on Kaaba
        emergencyMap = L.map('emergencyMap').setView([21.4225, 39.8262], 13);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(emergencyMap);
        
        // Create cluster groups
        userClusterGroup = L.markerClusterGroup({
            maxClusterRadius: 50,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true,
            disableClusteringAtZoom: 18
        });
        
        emergencyClusterGroup = L.markerClusterGroup({
            maxClusterRadius: 30,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true,
            iconCreateFunction: function(cluster) {
                return L.divIcon({
                    html: '<div class="cluster-icon cluster-icon-emergency">' + cluster.getChildCount() + '</div>',
                    className: 'marker-cluster-emergency',
                    iconSize: L.point(40, 40)
                });
            }
        });
        
        // Add Kaaba marker
        L.marker([21.4225, 39.8262], {
            icon: L.divIcon({
                html: '<i class="fas fa-kaaba"></i>',
                className: 'kaaba-marker-icon',
                iconSize: [30, 30],
                iconAnchor: [15, 15],
                popupAnchor: [0, -15]
            })
        }).addTo(emergencyMap).bindPopup("<?php echo __('kaaba'); ?>");
        
        // Add emergency locations and heat map
        addEmergencyLocations();
        
        // Add active users
        addActiveUsers();
        
        // Add clusters to map
        emergencyMap.addLayer(userClusterGroup);
        emergencyMap.addLayer(emergencyClusterGroup);
    }
    
    // Add Emergency Locations
    function addEmergencyLocations() {
        // Clear existing markers
        emergencyMarkers = [];
        emergencyClusterGroup.clearLayers();
        
        // Add each emergency location marker
        <?php foreach ($recentEmergencies as $emergency): ?>
        <?php if (!empty($emergency['latitude']) && !empty($emergency['longitude'])): ?>
        const emergencyIcon = L.divIcon({
            html: '<i class="<?php echo getEmergencyTypeIcon($emergency['type']); ?>"></i>',
            className: 'emergency-marker-icon emergency-type-<?php echo $emergency['type']; ?>',
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            popupAnchor: [0, -15]
        });
        
        const emergencyMarker = L.marker([<?php echo $emergency['latitude']; ?>, <?php echo $emergency['longitude']; ?>], {
            icon: emergencyIcon,
            title: '<?php echo addslashes(__($emergency['type']) . ' ' . __('emergency')); ?>'
        });
        
        // Create popup content
        const emergencyPopupContent = `
            <div class="emergency-popup">
                <div class="emergency-popup-header">
                    <div class="emergency-popup-type"><?php echo __($emergency['type']); ?> <?php echo __('emergency'); ?></div>
                    <span class="badge <?php echo getStatusBadgeClass($emergency['status']); ?>"><?php echo __($emergency['status']); ?></span>
                </div>
                <div class="emergency-popup-info">
                    <div class="emergency-popup-name"><i class="fas fa-user me-1"></i> <?php echo addslashes(htmlspecialchars($emergency['reporter_name'])); ?></div>
                    <div><i class="fas fa-clock me-1"></i> <?php echo formatDate($emergency['created_at']); ?></div>
                    <div class="emergency-popup-desc"><?php echo addslashes(htmlspecialchars(substr($emergency['description'], 0, 100))); ?><?php echo strlen($emergency['description']) > 100 ? '...' : ''; ?></div>
                </div>
                <div class="emergency-popup-actions">
                    <a href="<?php echo SITE_URL; ?>/emergencies/manage_emergency.php?id=<?php echo $emergency['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-clipboard-list me-1"></i> <?php echo __('manage'); ?>
                    </a>
                    <?php if ($emergency['reporter_phone']): ?>
                    <a href="tel:<?php echo $emergency['reporter_phone']; ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-phone-alt me-1"></i> <?php echo __('call'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        `;
        
        // Bind popup to marker
        emergencyMarker.bindPopup(emergencyPopupContent);
        
        // Add marker to cluster group
        emergencyClusterGroup.addLayer(emergencyMarker);
        
        // Add marker to array
        emergencyMarkers.push(emergencyMarker);
        <?php endif; ?>
        <?php endforeach; ?>
        
        // Create heat map data
        const heatData = [
            <?php foreach ($emergencyLocations as $location): ?>
            <?php if (!empty($location['latitude']) && !empty($location['longitude'])): ?>
            [<?php echo $location['latitude']; ?>, <?php echo $location['longitude']; ?>, 0.5],
            <?php endif; ?>
            <?php endforeach; ?>
        ];
        
        // Add heat map layer if data exists
        if (heatData.length > 0) {
            if (heatmapLayer) {
                emergencyMap.removeLayer(heatmapLayer);
            }
            
            heatmapLayer = L.heatLayer(heatData, {
                radius: 25,
                blur: 15,
                maxZoom: 17
            }).addTo(emergencyMap);
        }
    }
    
    // Add Active Users
    function addActiveUsers() {
        // Clear existing markers
        userMarkers = [];
        userClusterGroup.clearLayers();
        
        // Add each user marker
        <?php foreach ($activeUsers as $user): ?>
        <?php if (!empty($user['latitude']) && !empty($user['longitude'])): ?>
        const userIcon = L.divIcon({
            html: `<div class="user-marker-inner"><?php echo substr(htmlspecialchars($user['full_name']), 0, 1); ?></div>`,
            className: 'user-marker user-role-<?php echo $user['role']; ?>',
            iconSize: [36, 36],
            iconAnchor: [18, 18],
            popupAnchor: [0, -18]
        });
        
        const userMarker = L.marker([<?php echo $user['latitude']; ?>, <?php echo $user['longitude']; ?>], {
            icon: userIcon,
            title: '<?php echo addslashes(htmlspecialchars($user['full_name'])); ?>'
        });
        
        // Create popup content
        const userPopupContent = `
            <div class="user-popup">
                <div class="user-popup-header">
                    <img src="<?php echo !empty($user['profile_image']) ? SITE_URL . '/uploads/' . $user['profile_image'] : SITE_URL . '/assets/images/default_avatar.png'; ?>" alt="Profile" class="user-popup-image">
                    <div>
                        <div class="user-popup-name"><?php echo addslashes(htmlspecialchars($user['full_name'])); ?></div>
                        <span class="badge <?php echo getRoleBadgeClass($user['role']); ?>"><?php echo $user['role']; ?></span>
                    </div>
                </div>
                <div class="user-popup-info">
                    <div><i class="fas fa-clock me-1"></i> <?php echo formatDate($user['timestamp']); ?></div>
                    <div><i class="fas fa-map-marker-alt me-1"></i> <?php echo round($user['latitude'], 6); ?>, <?php echo round($user['longitude'], 6); ?></div>
                </div>
                <div class="user-popup-actions">
                    <a href="<?php echo SITE_URL; ?>/profile/view.php?user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-user me-1"></i> <?php echo __('profile'); ?>
                    </a>
                    <?php if ($user['phone']): ?>
                    <a href="tel:<?php echo $user['phone']; ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-phone-alt me-1"></i> <?php echo __('call'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        `;
        
        // Bind popup to marker
        userMarker.bindPopup(userPopupContent);
        
        // Add marker to cluster group
        userClusterGroup.addLayer(userMarker);
        
        // Add marker to array
        userMarkers.push(userMarker);
        <?php endif; ?>
        <?php endforeach; ?>
    }
    
    // Show All Users
    function showAllUsers() {
        document.getElementById('showAllUsers').classList.add('active');
        document.getElementById('showEmergenciesOnly').classList.remove('active');
        
        // Show user markers
        emergencyMap.addLayer(userClusterGroup);
        
        // Show emergency markers
        emergencyMap.addLayer(emergencyClusterGroup);
        
        // Show heat map
        if (heatmapLayer) {
            emergencyMap.addLayer(heatmapLayer);
        }
    }
    
    // Show Emergencies Only
    function showEmergenciesOnly() {
        document.getElementById('showEmergenciesOnly').classList.add('active');
        document.getElementById('showAllUsers').classList.remove('active');
        
        // Hide user markers
        emergencyMap.removeLayer(userClusterGroup);
        
        // Ensure emergency markers are visible
        emergencyMap.addLayer(emergencyClusterGroup);
        
        // Hide heat map
        if (heatmapLayer) {
            emergencyMap.removeLayer(heatmapLayer);
        }
    }
    
    // Initialize Emergency Charts
    function initEmergencyCharts() {
        // Emergency Types Chart
        const emergencyTypesCtx = document.getElementById('emergencyTypesChart').getContext('2d');
        new Chart(emergencyTypesCtx, {
            type: 'pie',
            data: {
                labels: [
                    <?php foreach ($emergencyStats as $stat): ?>
                    '<?php echo __($stat['type']); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($emergencyStats as $stat): ?>
                        <?php echo $stat['count']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        '#e74a3b', // medical (red)
                        '#f6c23e', // missing_person (yellow)
                        '#4e73df', // security (blue)
                        '#1cc88a'  // other (green)
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                return label + ': ' + value;
                            }
                        }
                    }
                }
            }
        });
        
        // Service Types Chart
        const serviceTypesCtx = document.getElementById('serviceTypesChart').getContext('2d');
        new Chart(serviceTypesCtx, {
            type: 'pie',
            data: {
                labels: [
                    <?php foreach ($serviceStats as $stat): ?>
                    '<?php echo __($stat['service_type']); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    data: [
                        <?php foreach ($serviceStats as $stat): ?>
                        <?php echo $stat['count']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: [
                        '#4e73df', // cart (blue)
                        '#36b9cc', // wheelchair (cyan)
                        '#1cc88a', // guide (green)
                        '#f6c23e', // medical (yellow)
                        '#5a5c69'  // other (gray)
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 10
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                let value = context.parsed || 0;
                                return label + ': ' + value;
                            }
                        }
                    }
                }
            }
        });
        
        // Emergency Hourly Distribution Chart
        const hourlyCtx = document.getElementById('emergencyHourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: Array.from({length: 24}, (_, i) => `${i}:00`),
                datasets: [{
                    label: '<?php echo __("emergencies"); ?>',
                    data: Array.from({length: 24}, (_, i) => {
                        const hour = <?php 
                            echo json_encode(array_column($hourlyEmergencies, 'count', 'hour')); 
                        ?>[i] || 0;
                        return hour;
                    }),
                    backgroundColor: 'rgba(231, 74, 59, 0.7)',
                    borderColor: '#e74a3b',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // Refresh Dashboard Data
    function refreshDashboardData() {
        // Show loading state
        document.getElementById('refreshData').innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> <?php echo __("loading"); ?>';
        
        // Reload the page
        window.location.reload();
    }
    
    // Send Message
    function sendMessage() {
        const recipientId = document.getElementById('recipientId').value;
        const subject = document.getElementById('messageSubject').value;
        const content = document.getElementById('messageContent').value;
        const sendAsNotification = document.getElementById('sendAsNotification').checked;
        const sendAsUrgent = document.getElementById('sendAsUrgent').checked;
        
        if (!subject || !content) {
            alert("<?php echo __('please_complete_all_fields'); ?>");
            return;
        }
        
        // Show loading state
        const sendButton = document.getElementById('sendMessageBtn');
        sendButton.disabled = true;
        sendButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> <?php echo __("sending"); ?>';
        
        // Make API call to send message
        fetch('<?php echo SITE_URL; ?>/api/message_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'send_message',
                recipient_id: recipientId,
                subject: subject,
                content: content,
                send_notification: sendAsNotification,
                is_urgent: sendAsUrgent
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                bootstrap.Modal.getInstance(document.getElementById('sendMessageModal')).hide();
                
                // Show success message
                alert("<?php echo __('message_sent_successfully'); ?>");
                
                // Reset form
                document.getElementById('messageForm').reset();
            } else {
                alert(data.message || "<?php echo __('message_send_failed'); ?>");
            }
            
            // Reset button
            sendButton.disabled = false;
            sendButton.innerHTML = '<i class="fas fa-paper-plane me-1"></i> <?php echo __("send"); ?>';
        })
        .catch(error => {
            console.error('API error:', error);
            alert("<?php echo __('message_send_failed'); ?>");
            
            // Reset button
            sendButton.disabled = false;
            sendButton.innerHTML = '<i class="fas fa-paper-plane me-1"></i> <?php echo __("send"); ?>';
        });
    }
</script>

<style>
/* Map Styles */
.large-map-container {
    height: 500px;
    width: 100%;
    border-radius: 0.25rem;
}

.kaaba-marker-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border-radius: 50%;
    border: 2px solid #21618C;
    color: #21618C;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.user-marker {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #4e73df;
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    border: 2px solid white;
    color: white;
    font-weight: bold;
}

.user-marker.user-role-admin {
    background-color: #e74a3b;
}

.user-marker.user-role-authority {
    background-color: #f6c23e;
}

.user-marker.user-role-guardian {
    background-color: #1cc88a;
}

.user-marker-inner {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    font-size: 16px;
}

.emergency-marker-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    font-size: 16px;
}

.emergency-type-medical {
    border: 3px solid #e74a3b;
    color: #e74a3b;
}

.emergency-type-missing_person {
    border: 3px solid #f6c23e;
    color: #f6c23e;
}

.emergency-type-security {
    border: 3px solid #4e73df;
    color: #4e73df;
}

.emergency-type-other {
    border: 3px solid #5a5c69;
    color: #5a5c69;
}

.cluster-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    font-weight: bold;
    color: white;
}

.marker-cluster-emergency div {
    background-color: rgba(231, 74, 59, 0.8);
}

/* Card Styles */
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}

.border-left-secondary {
    border-left: 0.25rem solid #858796 !important;
}

/* Popup Styles */
.user-popup, .emergency-popup {
    min-width: 200px;
}

.user-popup-header, .emergency-popup-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.user-popup-header {
    align-items: flex-start;
}

.user-popup-image {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
    object-fit: cover;
}

.user-popup-name, .emergency-popup-type {
    font-weight: bold;
}

.emergency-popup-name {
    margin-top: 5px;
}

.emergency-popup-desc {
    margin-top: 5px;
    font-style: italic;
}

.user-popup-info, .emergency-popup-info {
    margin-bottom: 10px;
    font-size: 0.875rem;
}

.user-popup-actions, .emergency-popup-actions {
    display: flex;
    justify-content: space-between;
}
</style>

<?php
// Include footer
include_once('../includes/footer.php');
?>