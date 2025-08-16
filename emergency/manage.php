<?php
// Require authentication
require_once '../includes/auth_check.php';

// Set page title
$pageTitle = __('manage_emergencies');

// Use Leaflet for maps
$useLeaflet = true;

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Include header
include_once '../includes/header.php';
// Get current user
$currentUser = getCurrentUser();
if (!$currentUser) {
    setFlashMessage('error', __('session_invalid'));
    redirect(SITE_URL . '/auth/login.php');
    exit;
}
$currentUserId = $currentUser['id'];
$userType = $currentUser['user_type'];

// Check permissions
global $permissions;
if (!isset($permissions[$userType]['emergencies']) || !$permissions[$userType]['emergencies']) {
    setFlashMessage('danger', __('access_denied'));
    redirect(SITE_URL . '/index.php');
    exit;
}
if ($userType === 'pilgrim') {
    setFlashMessage('danger', __('access_denied'));
    redirect(SITE_URL . '/index.php');
    exit;
}


// Fetch user's groups (for creating emergencies)
$groups = fetchAll("
    SELECT g.id, g.name
    FROM groups g
    JOIN group_members gm ON g.id = gm.group_id
    WHERE g.active = 1 AND gm.user_id = ?
    ORDER BY g.name
", [$currentUserId]);

// Fetch active emergencies
$emergencies = fetchAll("
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
    WHERE e.status = 'active' AND gm.user_id = ? AND g.active = 1
    ORDER BY e.created_at DESC
", [$currentUserId]);

// Log for debugging
error_log("User ID: $currentUserId, User Type: $userType, Groups: " . count($groups) . ", Emergencies: " . count($emergencies));
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <h1><i class="fas fa-exclamation-triangle me-2"></i> <?php echo __('manage_emergencies'); ?></h1>
            <p class="lead"><?php echo __('emergencies_description'); ?></p>

            <!-- Map Container -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-white">
                    <h5 class="mb-0"><?php echo __('emergency_locations'); ?></h5>
                    <div>
                        <button id="refreshMap" class="btn btn-sm btn-outline-primary me-2">
                            <i class="fas fa-sync-alt me-1"></i> <?php echo __('refresh'); ?>
                        </button>
                        <button id="centerMap" class="btn btn-sm btn-primary">
                            <i class="fas fa-crosshairs me-1"></i> <?php echo __('center_map'); ?>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="emergencyMap" style="height: 400px;"></div>
                </div>
            </div>

            <!-- Create Emergency (for guardian and admin) -->
            <?php if (in_array($userType, ['guardian', 'admin'])): ?>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-bell me-2"></i> <?php echo __('create_emergency'); ?></h5>
                    </div>
                    <div class="card-body">
                        <form id="createEmergencyForm">
                            <div class="mb-3">
                                <label for="emergencyTitle" class="form-label"><?php echo __('emergency_title'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="emergencyTitle" name="title" required maxlength="100">
                                <small class="form-text text-muted"><?php echo __('emergency_title_help'); ?></small>
                            </div>
                            <div class="mb-3">
                                <label for="emergencyDescription" class="form-label"><?php echo __('emergency_description'); ?></label>
                                <textarea class="form-control" id="emergencyDescription" name="description" rows="3" maxlength="500"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="emergencyGroup" class="form-label"><?php echo __('select_group'); ?> <span class="text-danger">*</span></label>
                                <select class="form-select" id="emergencyGroup" name="group_id" required>
                                    <option value=""><?php echo __('select_group'); ?></option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted"><?php echo __('emergency_group_help'); ?></small>
                            </div>
                            <div class="mb-3">
                                <label for="emergencyLocation" class="form-label"><?php echo __('emergency_location'); ?> <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="emergencyLat" name="latitude" placeholder="<?php echo __('latitude'); ?>" required readonly>
                                    <input type="text" class="form-control" id="emergencyLng" name="longitude" placeholder="<?php echo __('longitude'); ?>" required readonly>
                                    <button type="button" class="btn btn-outline-primary" id="getLocation">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo __('use_current_location'); ?>
                                    </button>
                                </div>
                                <small class="form-text text-muted"><?php echo __('emergency_location_help'); ?></small>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo __('create_emergency'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Active Emergencies -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo __('active_emergencies'); ?></h5>
                    <span class="badge bg-danger rounded-pill"><?php echo count($emergencies); ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($emergencies)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-bell-slash text-muted mb-3" style="font-size: 2rem;"></i>
                            <p class="text-muted"><?php echo __('no_emergencies'); ?></p>
                            <?php if (in_array($userType, ['guardian', 'admin'])): ?>
                                <button class="btn btn-danger mt-2" onclick="document.getElementById('emergencyTitle').focus();">
                                    <i class="fas fa-exclamation-circle me-2"></i> <?php echo __('create_emergency'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($emergencies as $emergency): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($emergency['title']); ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-users me-1"></i> <?php echo htmlspecialchars($emergency['group_name']); ?>
                                            </small><br>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($emergency['created_by_name']); ?>
                                            </small><br>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i> <?php echo date('Y-m-d H:i', strtotime($emergency['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary view-emergency" 
                                                    data-id="<?php echo $emergency['id']; ?>" 
                                                    data-lat="<?php echo $emergency['latitude']; ?>" 
                                                    data-lng="<?php echo $emergency['longitude']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($userType === 'admin' || ($userType === 'guardian' && $emergency['created_by'] == $currentUserId)): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger cancel-emergency" 
                                                        data-id="<?php echo $emergency['id']; ?>">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Viewing Emergency Details -->
<div class="modal fade" id="viewEmergencyModal" tabindex="-1" aria-labelledby="viewEmergencyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emergencyTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo __('close'); ?>"></button>
            </div>
            <div class="modal-body">
                <p><strong><?php echo __('group'); ?>:</strong> <span id="emergencyGroup"></span></p>
                <p><strong><?php echo __('created_by'); ?>:</strong> <span id="emergencyCreator"></span></p>
                <p><strong><?php echo __('created_at'); ?>:</strong> <span id="emergencyTime"></span></p>
                <p><strong><?php echo __('description'); ?>:</strong> <span id="emergencyDescription"></span></p>
                <h6><?php echo __('group_members'); ?>:</h6>
                <div id="emergencyMembersList" class="mb-3"></div>
                <div id="emergencyMapContainer" style="height: 300px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Emergency Management -->
<script>
// Global variables
let emergencyMap;
let emergencyMarkers = {};
let viewEmergencyModal;
let modalMap;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initEmergencyMap();
    setupEventListeners();
    viewEmergencyModal = new bootstrap.Modal(document.getElementById('viewEmergencyModal'));
});

// Initialize the emergency map
function initEmergencyMap() {
    emergencyMap = L.map('emergencyMap').setView([21.4225, 39.8262], 15); // Center on Kaaba
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(emergencyMap);

    loadEmergencies();
}

// Setup event listeners
function setupEventListeners() {
    // Create emergency form
    document.getElementById('createEmergencyForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        createEmergency();
    });

    // Get current location for emergency
    document.getElementById('getLocation')?.addEventListener('click', () => {
        getCurrentLocation((result) => {
            if (result.success) {
                document.getElementById('emergencyLat').value = result.latitude;
                document.getElementById('emergencyLng').value = result.longitude;
            } else {
                alert(`<?php echo __('location_error'); ?>: ${result.error}`);
            }
        });
    });

    // View emergency buttons
    document.querySelectorAll('.view-emergency').forEach(button => {
        button.addEventListener('click', () => {
            const id = button.getAttribute('data-id');
            const lat = parseFloat(button.getAttribute('data-lat'));
            const lng = parseFloat(button.getAttribute('data-lng'));
            viewEmergency(id);
            emergencyMap.setView([lat, lng], 15);
        });
    });

    // Cancel emergency buttons
    document.querySelectorAll('.cancel-emergency').forEach(button => {
        button.addEventListener('click', () => {
            if (confirm("<?php echo __('confirm_cancel_emergency'); ?>")) {
                cancelEmergency(button.getAttribute('data-id'));
            }
        });
    });

    // Refresh map
    document.getElementById('refreshMap').addEventListener('click', loadEmergencies);

    // Center map
    document.getElementById('centerMap').addEventListener('click', () => {
        getCurrentLocation((result) => {
            if (result.success) {
                emergencyMap.setView([result.latitude, result.longitude], 15);
            } else {
                emergencyMap.setView([21.4225, 39.8262], 15);
            }
        });
    });
}

// Get current location
function getCurrentLocation(callback) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                callback({
                    success: true,
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                });
            },
            (error) => {
                callback({
                    success: false,
                    error: error.message
                });
            }
        );
    } else {
        callback({
            success: false,
            error: '<?php echo __('geolocation_not_supported'); ?>'
        });
    }
}

// Create a new emergency
function createEmergency() {
    const form = document.getElementById('createEmergencyForm');
    const title = form.querySelector('#emergencyTitle').value.trim();
    const description = form.querySelector('#emergencyDescription').value.trim();
    const groupId = form.querySelector('#emergencyGroup').value;
    const latitude = form.querySelector('#emergencyLat').value;
    const longitude = form.querySelector('#emergencyLng').value;

    if (!title || !groupId || !latitude || !longitude) {
        alert("<?php echo __('fill_required_fields'); ?>");
        return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> <?php echo __("creating"); ?>';

    fetch('<?php echo SITE_URL; ?>/api/locations.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'create_emergency',
            title: title,
            description: description,
            group_id: groupId,
            latitude: parseFloat(latitude),
            longitude: parseFloat(longitude)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("<?php echo __('emergency_created'); ?>");
            window.location.reload();
        } else {
            alert(data.message || "<?php echo __('emergency_creation_failed'); ?>");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("<?php echo __('emergency_creation_failed'); ?>");
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> <?php echo __("create_emergency"); ?>';
    });
}

// View emergency details
function viewEmergency(emergencyId) {
    document.getElementById('emergencyTitle').textContent = "<?php echo __('loading'); ?>";
    document.getElementById('emergencyMembersList').innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div></div>';
    viewEmergencyModal.show();

    fetch('<?php echo SITE_URL; ?>/api/locations.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_emergency', emergency_id: emergencyId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const emergency = data.emergency;
            const members = data.members;

            document.getElementById('emergencyTitle').textContent = emergency.title;
            document.getElementById('emergencyGroup').textContent = emergency.group_name;
            document.getElementById('emergencyCreator').textContent = emergency.created_by_name;
            document.getElementById('emergencyTime').textContent = new Date(emergency.created_at).toLocaleString();
            document.getElementById('emergencyDescription').textContent = emergency.description || '<?php echo __('no_description'); ?>';

            // Initialize modal map
            setTimeout(() => {
                if (!modalMap) {
                    modalMap = L.map('emergencyMapContainer').setView([emergency.latitude, emergency.longitude], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19
                    }).addTo(modalMap);
                } else {
                    modalMap.setView([emergency.latitude, emergency.longitude], 15);
                }

                L.marker([emergency.latitude, emergency.longitude], {
                    icon: L.divIcon({
                        html: '<i class="fas fa-exclamation-triangle"></i>',
                        className: 'emergency-marker-icon',
                        iconSize: [36, 36],
                        iconAnchor: [18, 36],
                        popupAnchor: [0, -36]
                    })
                }).addTo(modalMap).bindPopup(`<b>${emergency.title}</b>`);

                modalMap.invalidateSize();
            }, 300);

            // Populate members list
            const membersList = document.getElementById('emergencyMembersList');
            membersList.innerHTML = '';

            if (members.length > 0) {
                const list = document.createElement('div');
                list.className = 'list-group';
                members.forEach(member => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';

                    const memberInfo = document.createElement('div');
                    const memberName = document.createElement('h6');
                    memberName.className = 'mb-1';
                    memberName.textContent = member.full_name;

                    const memberStatus = document.createElement('small');
                    memberStatus.className = 'text-muted';
                    memberStatus.innerHTML = member.timestamp
                        ? `<i class="fas fa-clock me-1"></i> ${formatTimeAgo(member.timestamp)}`
                        : `<i class="fas fa-user-slash me-1"></i> <?php echo __('location_not_shared'); ?>`;

                    memberInfo.appendChild(memberName);
                    memberInfo.appendChild(memberStatus);

                    const actionButtons = document.createElement('div');
                    actionButtons.className = 'btn-group';

                    if (member.latitude && member.longitude) {
                        const viewButton = document.createElement('button');
                        viewButton.type = 'button';
                        viewButton.className = 'btn btn-sm btn-outline-primary';
                        viewButton.innerHTML = '<i class="fas fa-map-marker-alt"></i>';
                        viewButton.addEventListener('click', () => {
                            modalMap.setView([member.latitude, member.longitude], 15);
                            L.popup()
                                .setLatLng([member.latitude, member.longitude])
                                .setContent(`<b>${member.full_name}</b>`)
                                .openOn(modalMap);
                        });
                        actionButtons.appendChild(viewButton);
                    }

                    if (member.phone) {
                        const callButton = document.createElement('a');
                        callButton.href = `tel:${member.phone}`;
                        callButton.className = 'btn btn-sm btn-outline-success';
                        callButton.innerHTML = '<i class="fas fa-phone-alt"></i>';
                        actionButtons.appendChild(callButton);
                    }

                    item.appendChild(memberInfo);
                    item.appendChild(actionButtons);
                    list.appendChild(item);
                });
                membersList.appendChild(list);
            } else {
                membersList.innerHTML = '<p class="text-center text-muted"><?php echo __('no_members'); ?></p>';
            }
        } else {
            alert(data.message || "<?php echo __('failed_to_get_emergency'); ?>");
            viewEmergencyModal.hide();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("<?php echo __('failed_to_get_emergency'); ?>");
        viewEmergencyModal.hide();
    });
}

// Cancel an emergency
function cancelEmergency(emergencyId) {
    fetch('<?php echo SITE_URL; ?>/api/locations.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'cancel_emergency', emergency_id: emergencyId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("<?php echo __('emergency_canceled'); ?>");
            window.location.reload();
        } else {
            alert(data.message || "<?php echo __('emergency_cancel_failed'); ?>");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("<?php echo __('emergency_cancel_failed'); ?>");
    });
}

// Load emergencies on the map
function loadEmergencies() {
    Object.values(emergencyMarkers).forEach(marker => emergencyMap.removeLayer(marker));
    emergencyMarkers = {};

    fetch('<?php echo SITE_URL; ?>/api/locations.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_active_emergencies' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            data.emergencies.forEach(emergency => {
                if (emergency.latitude && emergency.longitude) {
                    const marker = L.marker([emergency.latitude, emergency.longitude], {
                        icon: L.divIcon({
                            html: '<i class="fas fa-exclamation-triangle"></i>',
                            className: 'emergency-marker-icon',
                            iconSize: [36, 36],
                            iconAnchor: [18, 36],
                            popupAnchor: [0, -36]
                        })
                    }).addTo(emergencyMap);

                    marker.bindPopup(`
                        <b>${emergency.title}</b><br>
                        <small>${emergency.group_name}</small><br>
                        <small>${new Date(emergency.created_at).toLocaleString()}</small>
                    `);

                    emergencyMarkers[emergency.id] = marker;
                }
            });
        }
    })
    .catch(error => console.error('Error:', error));
}

// Format time ago
function formatTimeAgo(timestamp) {
    const now = new Date();
    const date = new Date(timestamp);
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return "<?php echo __('just_now'); ?>";
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes} <?php echo __('minutes_ago'); ?>`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} <?php echo __('hours_ago'); ?>`;
    const days = Math.floor(hours / 24);
    return `${days} <?php echo __('days_ago'); ?>`;
}
</script>

<!-- Extra CSS -->
<style>
.map-container { height: 400px; }
.emergency-marker-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border-radius: 50%;
    border: 2px solid var(--danger-color);
    width: 36px !important;
    height: 36px !important;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}
.emergency-marker-icon i {
    color: var(--danger-color);
    font-size: 18px;
}
</style>

<?php
// Include footer
include_once '../includes/footer.php';
?>