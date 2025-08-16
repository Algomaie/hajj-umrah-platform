<?php
// Set page title
$pageTitle = __('guardian_dashboard');

// Use map
$useLeaflet = true;

// Require authentication
require_once('../includes/auth_check.php');

// Check if user is a guardian
if ($currentUser['role'] !== 'guardian' && $currentUser['role'] !== 'admin') {
    // Redirect to appropriate dashboard
    redirect(SITE_URL . '/dashboard/index.php');
}

// Get guardian's groups
$guardianGroups = fetchAll("
    SELECT g.*, 
           (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
    FROM groups g
    WHERE g.created_by = ?
    ORDER BY g.created_at DESC
", [$currentUser['id']]);

// Get pilgrims under guardian's supervision
$supervisedPilgrims = fetchAll("
    SELECT u.id, u.full_name, u.email, u.phone, u.profile_image, 
           g.id as group_id, g.name as group_name,
           (SELECT MAX(timestamp) FROM locations WHERE user_id = u.id) as last_location_time
    FROM users u
    JOIN group_members gm ON u.id = gm.user_id
    JOIN groups g ON gm.group_id = g.id
    WHERE g.created_by = ? AND u.id != ? AND u.role = 'pilgrim'
    ORDER BY u.full_name
", [$currentUser['id'], $currentUser['id']]);

// Get recent emergencies reported by supervised pilgrims
$recentEmergencies = fetchAll("
    SELECT e.id, e.type, e.description, e.status, e.created_at, u.full_name, u.id as user_id
    FROM emergencies e
    JOIN users u ON e.reporter_id = u.id
    WHERE e.reporter_id IN (
        SELECT u.id 
        FROM users u
        JOIN group_members gm ON u.id = gm.user_id
        JOIN groups g ON gm.group_id = g.id
        WHERE g.created_by = ? AND u.id != ?
    )
    ORDER BY e.created_at DESC
    LIMIT 5
", [$currentUser['id'], $currentUser['id']]);

// Get recent service requests by supervised pilgrims
$recentServiceRequests = fetchAll("
    SELECT sr.id, sr.service_type, sr.status, sr.created_at, u.full_name, u.id as user_id
    FROM service_requests sr
    JOIN users u ON sr.user_id = u.id
    WHERE sr.user_id IN (
        SELECT u.id 
        FROM users u
        JOIN group_members gm ON u.id = gm.user_id
        JOIN groups g ON gm.group_id = g.id
        WHERE g.created_by = ? AND u.id != ?
    )
    ORDER BY sr.created_at DESC
    LIMIT 5
", [$currentUser['id'], $currentUser['id']]);

// Get ritual progress for supervised pilgrims
$ritualProgress = fetchAll("
    SELECT rp.id, rp.ritual_type, rp.tawaf_rounds, rp.sai_rounds, 
           rp.ihram_completed, rp.halq_completed, 
           rp.started_at, rp.completed_at, u.id as user_id, u.full_name
    FROM ritual_progress rp
    JOIN users u ON rp.user_id = u.id
    WHERE rp.user_id IN (
        SELECT u.id 
        FROM users u
        JOIN group_members gm ON u.id = gm.user_id
        JOIN groups g ON gm.group_id = g.id
        WHERE g.created_by = ? AND u.id != ?
    )
    AND rp.completed_at IS NULL
    ORDER BY rp.started_at DESC
", [$currentUser['id'], $currentUser['id']]);

// Include header
include_once('../includes/header.php');
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-8">
            <h1><i class="fas fa-user-shield me-2"></i> <?php echo __('guardian_dashboard'); ?></h1>
            <p class="lead"><?php echo __('guardian_dashboard_description'); ?></p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <button type="button" class="btn btn-outline-primary me-2" id="refreshLocation">
                <i class="fas fa-sync-alt me-1"></i> <?php echo __('refresh'); ?>
            </button>
            <a href="<?php echo SITE_URL; ?>/maps/location_sharing.php" class="btn btn-primary">
                <i class="fas fa-map-marked-alt me-1"></i> <?php echo __('location_map'); ?>
            </a>
        </div>
    </div>
    
    <!-- Status Overview -->
    <div class="row mb-4">
        <div class="col-md-3 mb-4 mb-md-0">
            <div class="card h-100 border-left-primary shadow-sm">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                <?php echo __('supervised_pilgrims'); ?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo count($supervisedPilgrims); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4 mb-md-0">
            <div class="card h-100 border-left-success shadow-sm">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                <?php echo __('groups_managed'); ?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo count($guardianGroups); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-friends fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4 mb-md-0">
            <div class="card h-100 border-left-info shadow-sm">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                <?php echo __('ritual_progress'); ?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold">
                                <?php echo count($ritualProgress); ?> <?php echo __('active'); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card h-100 border-left-warning shadow-sm">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                <?php echo __('pending_assistance'); ?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold">
                                <?php echo count($recentEmergencies) + count($recentServiceRequests); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Pilgrims under Supervision -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i> <?php echo __('supervised_pilgrims'); ?></h5>
                    <a href="<?php echo SITE_URL; ?>/groups/manage.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-user-plus me-1"></i> <?php echo __('manage_groups'); ?>
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($supervisedPilgrims)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-users text-muted mb-3" style="font-size: 2rem;"></i>
                        <p><?php echo __('no_supervised_pilgrims'); ?></p>
                        <a href="<?php echo SITE_URL; ?>/groups/create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> <?php echo __('create_group'); ?>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover" id="pilgrimsTable">
                            <thead>
                                <tr>
                                    <th><?php echo __('name'); ?></th>
                                    <th><?php echo __('group'); ?></th>
                                    <th><?php echo __('contact'); ?></th>
                                    <th><?php echo __('last_active'); ?></th>
                                    <th><?php echo __('actions'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($supervisedPilgrims as $pilgrim): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo !empty($pilgrim['profile_image']) ? SITE_URL . '/uploads/' . $pilgrim['profile_image'] : SITE_URL . '/assets/images/default_avatar.png'; ?>" 
                                                 alt="Profile" class="rounded-circle me-2" width="32" height="32">
                                            <?php echo htmlspecialchars($pilgrim['full_name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($pilgrim['group_name']); ?></td>
                                    <td>
                                        <?php if ($pilgrim['phone']): ?>
                                        <a href="tel:<?php echo $pilgrim['phone']; ?>" class="text-decoration-none">
                                            <i class="fas fa-phone-alt me-1"></i> <?php echo htmlspecialchars($pilgrim['phone']); ?>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted"><?php echo __('no_phone'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($pilgrim['last_location_time']): ?>
                                        <span data-bs-toggle="tooltip" title="<?php echo formatDate($pilgrim['last_location_time']); ?>">
                                            <?php echo timeSince($pilgrim['last_location_time']); ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted"><?php echo __('never'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo SITE_URL; ?>/maps/location_sharing.php?user_id=<?php echo $pilgrim['id']; ?>" 
                                               class="btn btn-outline-primary" data-bs-toggle="tooltip" title="<?php echo __('view_location'); ?>">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/rituals/track.php?user_id=<?php echo $pilgrim['id']; ?>" 
                                               class="btn btn-outline-info" data-bs-toggle="tooltip" title="<?php echo __('view_rituals'); ?>">
                                                <i class="fas fa-tasks"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-success send-message" 
                                                   data-user-id="<?php echo $pilgrim['id']; ?>" data-name="<?php echo htmlspecialchars($pilgrim['full_name']); ?>"
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
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Group Location Map -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i> <?php echo __('group_location_map'); ?></h5>
                </div>
                <div class="card-body">
                    <div id="groupMap" class="map-container mb-3"></div>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="centerMap">
                            <i class="fas fa-crosshairs me-1"></i> <?php echo __('center_map'); ?>
                        </button>
                        <span class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i> <?php echo __('last_updated'); ?>: <span id="lastUpdated"><?php echo __('just_now'); ?></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Ritual Progress -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i> <?php echo __('ritual_progress'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($ritualProgress)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-pray text-muted mb-3" style="font-size: 2rem;"></i>
                        <p><?php echo __('no_active_rituals'); ?></p>
                    </div>
                    <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($ritualProgress as $ritual): ?>
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">
                                    <i class="<?php echo $ritual['ritual_type'] === 'hajj' ? 'fas fa-kaaba' : 'fas fa-mosque'; ?> me-1"></i>
                                    <?php echo htmlspecialchars($ritual['full_name']); ?>
                                </h6>
                                <small class="text-<?php echo $ritual['ritual_type'] === 'hajj' ? 'success' : 'primary'; ?>">
                                    <?php echo __($ritual['ritual_type']); ?>
                                </small>
                            </div>
                            
                            <?php if ($ritual['ritual_type'] === 'umrah' || $ritual['ritual_type'] === 'hajj'): ?>
                            <div class="mt-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <small><?php echo __('tawaf'); ?></small>
                                    <small><?php echo $ritual['tawaf_rounds']; ?>/7</small>
                                </div>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?php echo ($ritual['tawaf_rounds'] / 7) * 100; ?>%;" 
                                         aria-valuenow="<?php echo $ritual['tawaf_rounds']; ?>" aria-valuemin="0" aria-valuemax="7"></div>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-1">
                                    <small><?php echo __('sai'); ?></small>
                                    <small><?php echo $ritual['sai_rounds']; ?>/7</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-info" role="progressbar" 
                                         style="width: <?php echo ($ritual['sai_rounds'] / 7) * 100; ?>%;" 
                                         aria-valuenow="<?php echo $ritual['sai_rounds']; ?>" aria-valuemin="0" aria-valuemax="7"></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mt-2">
                                <a href="<?php echo SITE_URL; ?>/rituals/track.php?user_id=<?php echo $ritual['user_id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i> <?php echo __('view_details'); ?>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Emergencies and Assistance -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo __('pending_assistance'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php 
                        $pendingItems = array_merge($recentEmergencies, $recentServiceRequests);
                        usort($pendingItems, function($a, $b) {
                            return strtotime($b['created_at']) - strtotime($a['created_at']);
                        });
                        
                        if (empty($pendingItems)): 
                        ?>
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success mb-3" style="font-size: 2rem;"></i>
                            <p><?php echo __('no_pending_assistance'); ?></p>
                        </div>
                        <?php else: ?>
                            <?php foreach (array_slice($pendingItems, 0, 5) as $item): ?>
                                <?php 
                                $isEmergency = isset($item['type']); 
                                $itemType = $isEmergency ? $item['type'] : $item['service_type'];
                                $statusClass = getStatusBadgeClass($item['status']);
                                $iconClass = $isEmergency ?  getEmergency($itemType) : getServiceTypeIcon($itemType);
                                ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <i class="<?php echo $iconClass; ?> me-1"></i>
                                            <?php echo htmlspecialchars($item['full_name']); ?>
                                        </h6>
                                        <small class="badge <?php echo $statusClass; ?>">
                                            <?php echo __($item['status']); ?>
                                        </small>
                                    </div>
                                    <p class="mb-1 small">
                                        <?php echo $isEmergency ? __($itemType) . ' ' . __('emergency') : __($itemType) . ' ' . __('request'); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i> <?php echo relativeTime($item['created_at']); ?>
                                        </small>
                                        <a href="<?php echo SITE_URL; ?>/<?php echo $isEmergency ? 'emergencies/view.php?id=' : 'services/view_request.php?id='; ?><?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> <?php echo __('view'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i> <?php echo __('quick_actions'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="<?php echo SITE_URL; ?>/groups/create.php" class="btn btn-outline-primary d-block">
                                <i class="fas fa-users me-1"></i> <?php echo __('create_group'); ?>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo SITE_URL; ?>/emergencies/report.php" class="btn btn-outline-danger d-block">
                                <i class="fas fa-ambulance me-1"></i> <?php echo __('report_emergency'); ?>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo SITE_URL; ?>/services/cart_service.php" class="btn btn-outline-success d-block">
                                <i class="fas fa-wheelchair me-1"></i> <?php echo __('request_service'); ?>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo SITE_URL; ?>/messages/inbox.php" class="btn btn-outline-info d-block">
                                <i class="fas fa-envelope me-1"></i> <?php echo __('messages'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Emergency Contacts -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-phone-alt me-2"></i> <?php echo __('emergency_contacts'); ?></h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo __('general_emergency'); ?></strong>
                                <div class="text-danger">911</div>
                            </div>
                            <a href="tel:911" class="btn btn-sm btn-danger">
                                <i class="fas fa-phone-alt"></i>
                            </a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo __('medical'); ?></strong>
                                <div class="text-danger">937</div>
                            </div>
                            <a href="tel:937" class="btn btn-sm btn-danger">
                                <i class="fas fa-phone-alt"></i>
                            </a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?php echo __('hajj_information'); ?></strong>
                                <div class="text-primary">1966</div>
                            </div>
                            <a href="tel:1966" class="btn btn-sm btn-primary">
                                <i class="fas fa-phone-alt"></i>
                            </a>
                        </li>
                    </ul>
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
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="sendAsNotification" name="send_as_notification" checked>
                        <label class="form-check-label" for="sendAsNotification">
                            <?php echo __('send_as_notification'); ?>
                        </label>
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
    let groupMap;
    let markers = {};
    let userInfoWindows = {};
    let userMarkers = [];
    let lastUpdateTime = new Date();
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize map
        initGroupMap();
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize pilgrims table
        if (document.getElementById('pilgrimsTable')) {
            new DataTable('#pilgrimsTable', {
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
        document.getElementById('refreshLocation').addEventListener('click', updateGroupLocations);
        document.getElementById('centerMap').addEventListener('click', centerMapOnGroup);
        
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
        
        // Update locations every 60 seconds
        setInterval(updateGroupLocations, 60000);
    });
    
    // Initialize Group Map
    function initGroupMap() {
        // Create map centered on Kaaba
        groupMap = L.map('groupMap').setView([21.4225, 39.8262], 15);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(groupMap);
        
        // Add Kaaba marker
        L.marker([21.4225, 39.8262], {
            icon: L.divIcon({
                html: '<i class="fas fa-kaaba"></i>',
                className: 'kaaba-marker-icon',
                iconSize: [30, 30],
                iconAnchor: [15, 15],
                popupAnchor: [0, -15]
            })
        }).addTo(groupMap).bindPopup("<?php echo __('kaaba'); ?>");
        
        // Get location updates
        updateGroupLocations();
    }
    
    // Update Group Locations
    function updateGroupLocations() {
        // Show loading state
        document.getElementById('refreshLocation').innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> <?php echo __("loading"); ?>';
        
        // Get current timestamp
        lastUpdateTime = new Date();
        
        // Make API call to get group locations
        fetch('<?php echo SITE_URL; ?>/api/location_api.php?action=get_group_locations')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear existing markers
                    userMarkers.forEach(marker => groupMap.removeLayer(marker));
                    userMarkers = [];
                    
                    // Add markers for group members
                    data.locations.forEach(location => {
                        addUserMarker(location);
                    });
                    
                    // Center map if needed
                    if (data.locations.length > 0) {
                        centerMapOnGroup();
                    }
                    
                    // Update last updated time
                    document.getElementById('lastUpdated').textContent = '<?php echo __("just_now"); ?>';
                    
                    // Reset button
                    document.getElementById('refreshLocation').innerHTML = '<i class="fas fa-sync-alt me-1"></i> <?php echo __("refresh"); ?>';
                } else {
                    console.error('Error fetching locations:', data.message);
                    // Reset button
                    document.getElementById('refreshLocation').innerHTML = '<i class="fas fa-sync-alt me-1"></i> <?php echo __("refresh"); ?>';
                }
            })
            .catch(error => {
                console.error('API error:', error);
                // Reset button
                document.getElementById('refreshLocation').innerHTML = '<i class="fas fa-sync-alt me-1"></i> <?php echo __("refresh"); ?>';
            });
    }
    
    // Add User Marker to Map
    function addUserMarker(location) {
        if (!location.latitude || !location.longitude) return;
        
        // Create marker icon based on user type
        const markerIcon = L.divIcon({
            html: `<div class="user-marker-inner">${location.full_name.charAt(0)}</div>`,
            className: `user-marker ${location.user_id === '<?php echo $currentUser['id']; ?>' ? 'current-user' : ''}`,
            iconSize: [40, 40],
            iconAnchor: [20, 20],
            popupAnchor: [0, -20]
        });
        
        // Create marker
        const marker = L.marker([location.latitude, location.longitude], {
            icon: markerIcon,
            title: location.full_name
        }).addTo(groupMap);
        
        // Create popup content
        const popupContent = `
            <div class="user-popup">
                <div class="user-popup-header">
                    <img src="${location.profile_image ? '<?php echo SITE_URL; ?>/uploads/' + location.profile_image : '<?php echo SITE_URL; ?>/assets/images/default_avatar.png'}" alt="Profile" class="user-popup-image">
                    <div class="user-popup-name">${location.full_name}</div>
                </div>
                <div class="user-popup-info">
                    <div><i class="fas fa-clock me-1"></i> ${location.timestamp ? formatTimestamp(location.timestamp) : '<?php echo __("unknown"); ?>'}</div>
                    <div><i class="fas fa-map-marker-alt me-1"></i> ${location.latitude.toFixed(6)}, ${location.longitude.toFixed(6)}</div>
                </div>
                <div class="user-popup-actions">
                    <a href="<?php echo SITE_URL; ?>/maps/location_sharing.php?user_id=${location.user_id}" class="btn btn-sm btn-primary">
                        <i class="fas fa-map me-1"></i> <?php echo __('view_on_map'); ?>
                    </a>
                    <a href="tel:${location.phone}" class="btn btn-sm btn-success ${!location.phone ? 'disabled' : ''}">
                        <i class="fas fa-phone-alt me-1"></i> <?php echo __('call'); ?>
                    </a>
                </div>
            </div>
        `;
        
        // Bind popup to marker
        marker.bindPopup(popupContent);
        
        // Add marker to array
        userMarkers.push(marker);
    }
    
    // Center Map on Group
    function centerMapOnGroup() {
        if (userMarkers.length === 0) return;
        
        // Create bounds object
        const bounds = L.latLngBounds();
        
        // Add all user markers to bounds
        userMarkers.forEach(marker => {
            bounds.extend(marker.getLatLng());
        });
        
        // Extend bounds to include Kaaba
        bounds.extend([21.4225, 39.8262]);
        
        // Fit map to bounds with padding
        groupMap.fitBounds(bounds, {
            padding: [50, 50],
            maxZoom: 16
        });
    }
    
    // Format Timestamp
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleString();
    }
    
    // Send Message
    function sendMessage() {
        const recipientId = document.getElementById('recipientId').value;
        const subject = document.getElementById('messageSubject').value;
        const content = document.getElementById('messageContent').value;
        const sendAsNotification = document.getElementById('sendAsNotification').checked;
        
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
                send_notification: sendAsNotification
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
.map-container {
    height: 400px;
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

.user-marker.current-user {
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

/* Popup Styles */
.user-popup {
    min-width: 200px;
}

.user-popup-header {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.user-popup-image {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 10px;
    object-fit: cover;
}

.user-popup-name {
    font-weight: bold;
}

.user-popup-info {
    margin-bottom: 10px;
    font-size: 0.875rem;
}

.user-popup-actions {
    display: flex;
    justify-content: space-between;
}
</style>

<?php
// Include footer
include_once('../includes/footer.php');
?>