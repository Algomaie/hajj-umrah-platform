<?php
// Include required files
include_once '../includes/auth_check.php';
// Set page title
$pageTitle = __('location_sharing');

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
if (!isset($permissions[$userType]['maps']) || !$permissions[$userType]['maps']) {
    setFlashMessage('danger', __('access_denied'));
    redirect(SITE_URL . '/index.php');
    exit;
}

// Fetch user's groups and member count
$groups = fetchAll("
    SELECT g.id, g.name, g.invite_code, g.created_by, COUNT(gm.user_id) - 1 as member_count
    FROM groups g
    JOIN group_members gm ON g.id = gm.group_id
    WHERE g.active = 1 AND (g.created_by = ? OR gm.user_id = ?)
    GROUP BY g.id
    ORDER BY g.created_at DESC
", [$currentUserId, $currentUserId]);

// Fetch group members with their latest location
$groupMembers = [];
if (!empty($groups)) {
    $groupIds = array_column($groups, 'id');
    $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
    $groupMembers = fetchAll("
        SELECT 
            u.id, 
            u.full_name, 
            u.phone, 
            u.profile_image,
            l.latitude, 
            l.longitude, 
            l.timestamp,
            TIMESTAMPDIFF(MINUTE, l.timestamp, NOW()) as minutes_ago,
            g.name as group_name
        FROM users u
        JOIN group_members gm ON u.id = gm.user_id
        JOIN groups g ON gm.group_id = g.id
        LEFT JOIN (
            SELECT user_id, MAX(timestamp) as latest
            FROM locations
            GROUP BY user_id
        ) latest_loc ON u.id = latest_loc.user_id
        LEFT JOIN locations l ON latest_loc.user_id = l.user_id AND latest_loc.latest = l.timestamp
        WHERE gm.group_id IN ($placeholders) AND u.id != ?
        ORDER BY g.name, u.full_name
    ", array_merge($groupIds, [$currentUserId]));
}

// Log for debugging
error_log("User ID: $currentUserId, User Type: $userType, Groups: " . count($groups) . ", Members: " . count($groupMembers));
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <h1><i class="fas fa-share-alt me-2"></i> <?php echo __('location_sharing'); ?></h1>
            <p class="lead"><?php echo __('location_sharing_description'); ?></p>

            <!-- Map Container -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-white">
                    <h5 class="mb-0"><?php echo __('group_location_map'); ?></h5>
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
                    <div id="sharingMap" style="height: 400px;"></div>
                </div>
            </div>

            <!-- Create Group (only for guardian and admin) -->
            <?php if (in_array($userType, ['guardian', 'admin'])): ?>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-users-cog me-2"></i> <?php echo __('create_group'); ?></h5>
                    </div>
                    <div class="card-body">
                        <form id="createGroupForm">
                            <div class="mb-3">
                                <label for="groupName" class="form-label"><?php echo __('group_name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="groupName" name="group_name" required maxlength="100">
                                <small class="form-text text-muted"><?php echo __('group_name_help'); ?></small>
                            </div>
                            <div class="mb-3">
                                <label for="groupDescription" class="form-label"><?php echo __('group_description'); ?></label>
                                <textarea class="form-control" id="groupDescription" name="group_description" rows="3" maxlength="500"></textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-users me-2"></i> <?php echo __('create_group'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Join Group -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i> <?php echo __('join_group'); ?></h5>
                </div>
                <div class="card-body">
                    <form id="joinGroupForm">
                        <div class="mb-3">
                            <label for="inviteCode" class="form-label"><?php echo __('invite_code'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="inviteCode" name="invite_code" required maxlength="50">
                            <small class="form-text text-muted"><?php echo __('invite_code_help'); ?></small>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i> <?php echo __('join_group'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- My Groups -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i> <?php echo __('my_groups'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($groups)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users text-muted mb-3" style="font-size: 2rem;"></i>
                            <p class="text-muted"><?php echo __('no_groups'); ?></p>
                            <?php if (in_array($userType, ['guardian', 'admin'])): ?>
                                <button class="btn btn-primary mt-2" onclick="document.getElementById('groupName').focus();">
                                    <i class="fas fa-users-cog me-2"></i> <?php echo __('create_group'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($groups as $group): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($group['name']); ?></h6>
                                            <small class="text-muted">
                                                <i class="fas fa-users me-1"></i> <?php echo $group['member_count']; ?> <?php echo __('members'); ?>
                                            </small>
                                        </div>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary view-group" data-id="<?php echo $group['id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($group['created_by'] == $currentUserId && in_array($userType, ['guardian', 'admin'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-group" data-id="<?php echo $group['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger leave-group" data-id="<?php echo $group['id']; ?>">
                                                    <i class="fas fa-sign-out-alt"></i>
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

            <!-- Group Details (hidden by default) -->
            <div class="card mb-4 shadow-sm" id="groupDetails" style="display: none;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" id="groupDetailsName"></h5>
                    <button type="button" class="btn-close" id="closeGroupDetails" aria-label="<?php echo __('close'); ?>"></button>
                </div>
                <div class="card-body">
                    <div id="groupMembersList" class="mb-3"></div>
                    <?php if (in_array($userType, ['guardian', 'admin'])): ?>
                        <div id="inviteSection">
                            <h6><?php echo __('invite_others'); ?></h6>
                            <div class="input-group">
                                <input type="text" class="form-control" id="inviteLink" readonly>
                                <button class="btn btn-outline-primary" type="button" id="copyInviteLink">
                                    <i class="fas fa-copy"></i> <?php echo __('copy'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Group Members List -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i> <?php echo __('group_members'); ?></h5>
                    <span class="badge bg-primary rounded-pill"><?php echo count($groupMembers); ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($groupMembers)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-friends text-muted mb-3" style="font-size: 3rem;"></i>
                            <p class="text-muted"><?php echo __('no_group_members_found'); ?></p>
                            <a href="<?php echo SITE_URL; ?>/services/manage.php" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i> <?php echo __('add_members'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($groupMembers as $member): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-center">
                                        <!-- Profile Image -->
                                        <div class="flex-shrink-0 me-3">
                                            <?php if ($member['profile_image']): ?>
                                                <img src="<?php echo SITE_URL; ?>/Uploads/profiles/<?php echo htmlspecialchars($member['profile_image']); ?>" 
                                                     class="rounded-circle" width="40" height="40" alt="<?php echo htmlspecialchars($member['full_name']); ?>">
                                            <?php else: ?>
                                                <div class="avatar-circle bg-<?php echo getAvatarColor($member['user_type'] ?? 'pilgrim'); ?> text-white" 
                                                     style="width: 40px; height: 40px; line-height: 40px; text-align: center;">
                                                    <?php echo getInitials($member['full_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Member Info -->
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($member['full_name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($member['group_name']); ?></small>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <?php if ($member['latitude'] && $member['longitude']): ?>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo __('updated'); ?> 
                                                        <?php echo $member['minutes_ago'] <= 5 ? __('just_now') : sprintf(__('%d minutes ago'), $member['minutes_ago']); ?>
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted"><?php echo __('location_unknown'); ?></small>
                                                <?php endif; ?>
                                                <div class="btn-group">
                                                    <?php if ($member['latitude'] && $member['longitude']): ?>
                                                        <button class="btn btn-sm btn-outline-primary show-on-map" 
                                                                data-lat="<?php echo $member['latitude']; ?>" 
                                                                data-lng="<?php echo $member['longitude']; ?>"
                                                                data-name="<?php echo htmlspecialchars($member['full_name']); ?>">
                                                            <i class="fas fa-map-marker-alt"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($member['phone']): ?>
                                                        <a href="tel:<?php echo htmlspecialchars($member['phone']); ?>" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-phone-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Share Status -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-broadcast-tower me-2"></i> <?php echo __('sharing_status'); ?></h5>
                </div>
                <div class="card-body">
                    <div id="sharingStatus" class="alert alert-info">
                        <?php echo __('location_not_shared'); ?>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="enableSharing">
                        <label class="form-check-label" for="enableSharing"><?php echo __('enable_sharing'); ?></label>
                    </div>
                    <div id="lastShared" class="mb-3" style="display: none;">
                        <small class="text-muted"><?php echo __('last_shared'); ?>: <span id="lastSharedTime">--</span></small>
                    </div>
                    <div class="d-grid">
                        <button id="shareLocation" class="btn btn-primary" disabled>
                            <i class="fas fa-share-alt me-2"></i> <?php echo __('share_now'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tips -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i> <?php echo __('sharing_tips'); ?></h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('sharing_tip_1'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('sharing_tip_2'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('sharing_tip_3'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('sharing_tip_4'); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Viewing Member Details -->
<div class="modal fade" id="viewMemberModal" tabindex="-1" aria-labelledby="viewMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="memberName"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo __('close'); ?>"></button>
            </div>
            <div class="modal-body">
                <p><strong><?php echo __('contact'); ?>:</strong> <span id="memberContact"></span></p>
                <p><strong><?php echo __('last_updated'); ?>:</strong> <span id="memberLastUpdate"></span></p>
                <div id="memberMapContainer" style="height: 300px;"></div>
            </div>
            <div class="modal-footer">
                <a id="memberContactBtn" class="btn btn-success" style="display: none;">
                    <i class="fas fa-phone-alt me-2"></i> <?php echo __('call'); ?>
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Location Sharing -->
<script>
// Global variables
let sharingMap;
let userMarker;
let groupMarkers = {};
let currentGroupId = null;
let sharingEnabled = false;
let sharingInterval = null;
let viewMemberModal;
let memberMap;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initSharingMap();
    setupEventListeners();
    viewMemberModal = new bootstrap.Modal(document.getElementById('viewMemberModal'));
});

// Initialize the sharing map
function initSharingMap() {
    sharingMap = L.map('sharingMap').setView([21.4225, 39.8262], 15); // Center on Kaaba
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(sharingMap);

    getCurrentLocation((result) => {
        if (result.success) {
            userMarker = L.marker([result.latitude, result.longitude], {
                icon: L.divIcon({
                    html: '<i class="fas fa-user-circle"></i>',
                    className: 'user-marker-icon',
                    iconSize: [36, 36],
                    iconAnchor: [18, 36],
                    popupAnchor: [0, -36]
                })
            }).addTo(sharingMap);
            userMarker.bindPopup(`<b><?php echo __('your_location'); ?></b>`);
            sharingMap.setView([result.latitude, result.longitude], 15);
            loadGroupMembers();
        } else {
            console.error('Error getting location:', result.error);
            document.getElementById('sharingStatus').textContent = `<?php echo __('location_error'); ?>: ${result.error}`;
            document.getElementById('sharingStatus').className = 'alert alert-danger';
        }
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

// Setup event listeners
function setupEventListeners() {
    // Create group form
    document.getElementById('createGroupForm')?.addEventListener('submit', (e) => {
        e.preventDefault();
        createGroup();
    });

    // Join group form
    document.getElementById('joinGroupForm').addEventListener('submit', (e) => {
        e.preventDefault();
        joinGroup();
    });

    // View group buttons
    document.querySelectorAll('.view-group').forEach(button => {
        button.addEventListener('click', () => viewGroup(button.getAttribute('data-id')));
    });

    // Delete group buttons
    document.querySelectorAll('.delete-group').forEach(button => {
        button.addEventListener('click', () => {
            if (confirm("<?php echo __('confirm_delete_group'); ?>")) {
                deleteGroup(button.getAttribute('data-id'));
            }
        });
    });

    // Leave group buttons
    document.querySelectorAll('.leave-group').forEach(button => {
        button.addEventListener('click', () => {
            if (confirm("<?php echo __('confirm_leave_group'); ?>")) {
                leaveGroup(button.getAttribute('data-id'));
            }
        });
    });

    // Close group details
    document.getElementById('closeGroupDetails').addEventListener('click', () => {
        document.getElementById('groupDetails').style.display = 'none';
        currentGroupId = null;
    });

    // Copy invite link
    document.getElementById('copyInviteLink')?.addEventListener('click', () => {
        const inviteLink = document.getElementById('inviteLink');
        inviteLink.select();
        document.execCommand('copy');
        alert("<?php echo __('link_copied'); ?>");
    });

    // Enable sharing toggle
    document.getElementById('enableSharing').addEventListener('change', function() {
        sharingEnabled = this.checked;
        const shareButton = document.getElementById('shareLocation');
        shareButton.disabled = !sharingEnabled;

        if (sharingEnabled) {
            document.getElementById('sharingStatus').textContent = "<?php echo __('sharing_enabled'); ?>";
            document.getElementById('sharingStatus').className = 'alert alert-success';
            sharingInterval = setInterval(shareUserLocation, 60000);
        } else {
            document.getElementById('sharingStatus').textContent = "<?php echo __('sharing_disabled'); ?>";
            document.getElementById('sharingStatus').className = 'alert alert-warning';
            if (sharingInterval) {
                clearInterval(sharingInterval);
                sharingInterval = null;
            }
        }
    });

    // Share location button
    document.getElementById('shareLocation').addEventListener('click', shareUserLocation);

    // Refresh map
    document.getElementById('refreshMap').addEventListener('click', loadGroupMembers);

    // Center map
    document.getElementById('centerMap').addEventListener('click', () => {
        if (userMarker) {
            sharingMap.setView(userMarker.getLatLng(), 15);
        } else {
            getCurrentLocation((result) => {
                if (result.success) {
                    sharingMap.setView([result.latitude, result.longitude], 15);
                }
            });
        }
    });

    // Show on map buttons
    document.querySelectorAll('.show-on-map').forEach(button => {
        button.addEventListener('click', () => {
            const lat = parseFloat(button.getAttribute('data-lat'));
            const lng = parseFloat(button.getAttribute('data-lng'));
            const name = button.getAttribute('data-name');
            sharingMap.setView([lat, lng], 15);
            L.popup()
                .setLatLng([lat, lng])
                .setContent(`<b>${name}</b>`)
                .openOn(sharingMap);
        });
    });
}

// Create a new group
function createGroup() {
    const form = document.getElementById('createGroupForm');
    const groupName = form.querySelector('#groupName').value.trim();
    const groupDescription = form.querySelector('#groupDescription').value.trim();

    if (!groupName) {
        alert("<?php echo __('enter_group_name'); ?>");
        return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> <?php echo __("creating"); ?>';

    fetch('<?php echo SITE_URL; ?>/api/location_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'create_group',
            name: groupName,
            description: groupDescription
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("<?php echo __('group_created'); ?>");
            window.location.reload();
        } else {
            alert(data.message || "<?php echo __('group_creation_failed'); ?>");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("<?php echo __('group_creation_failed'); ?>");
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-users me-2"></i> <?php echo __("create_group"); ?>';
    });
}

// Join an existing group
function joinGroup() {
    const form = document.getElementById('joinGroupForm');
    const inviteCode = form.querySelector('#inviteCode').value.trim();

    if (!inviteCode) {
        alert("<?php echo __('enter_invite_code'); ?>");
        return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> <?php echo __("joining"); ?>';

    fetch('<?php echo SITE_URL; ?>/api/location_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'join_group',
            invite_code: inviteCode
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("<?php echo __('group_joined'); ?>");
            window.location.reload();
        } else {
            alert(data.message || "<?php echo __('group_join_failed'); ?>");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("<?php echo __('group_join_failed'); ?>");
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> <?php echo __("join_group"); ?>';
    });
}

// View group details
function viewGroup(groupId) {
    currentGroupId = groupId;
    document.getElementById('groupDetailsName').textContent = "<?php echo __('loading'); ?>";
    document.getElementById('groupMembersList').innerHTML = '<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div></div>';
    document.getElementById('groupDetails').style.display = 'block';

    fetch(`<?php echo SITE_URL; ?>/api/location_api.php?action=get_group&group_id=${groupId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const group = data.group;
                const members = data.members;
                document.getElementById('groupDetailsName').textContent = group.name;

                <?php if (in_array($userType, ['guardian', 'admin'])): ?>
                    const inviteSection = document.getElementById('inviteSection');
                    if (group.created_by == <?php echo $currentUserId; ?>) {
                        inviteSection.style.display = 'block';
                        document.getElementById('inviteLink').value = `<?php echo SITE_URL; ?>/join-group.php?code=${group.invite_code}`;
                    } else {
                        inviteSection.style.display = 'none';
                    }
                <?php endif; ?>

                const membersList = document.getElementById('groupMembersList');
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
                            viewButton.innerHTML = '<i class="fas fa-eye"></i>';
                            viewButton.addEventListener('click', () => viewMember(member));
                            actionButtons.appendChild(viewButton);
                        }

                        if (member.phone) {
                            const callButton = document.createElement('a');
                            callButton.href = `tel:${member.phone}`;
                            callButton.className = 'btn btn-sm btn-outline-success';
                            callButton.innerHTML = '<i class="fas fa-phone-alt"></i>';
                            actionButtons.appendChild(callButton);
                        }

                        <?php if (in_array($userType, ['guardian', 'admin'])): ?>
                            if (group.created_by == <?php echo $currentUserId; ?> && member.id != <?php echo $currentUserId; ?>) {
                                const removeButton = document.createElement('button');
                                removeButton.type = 'button';
                                removeButton.className = 'btn btn-sm btn-outline-danger';
                                removeButton.innerHTML = '<i class="fas fa-user-minus"></i>';
                                removeButton.addEventListener('click', () => {
                                    if (confirm(`<?php echo __('confirm_remove_member'); ?> ${member.full_name}?`)) {
                                        removeMember(groupId, member.id);
                                    }
                                });
                                actionButtons.appendChild(removeButton);
                            }
                        <?php endif; ?>

                        item.appendChild(memberInfo);
                        item.appendChild(actionButtons);
                        list.appendChild(item);
                    });
                    membersList.appendChild(list);
                } else {
                    membersList.innerHTML = '<p class="text-center text-muted"><?php echo __('no_members'); ?></p>';
                }
            } else {
                alert(data.message || "<?php echo __('failed_to_get_group'); ?>");
                document.getElementById('groupDetails').style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("<?php echo __('failed_to_get_group'); ?>");
            document.getElementById('groupDetails').style.display = 'none';
        });
}

// Delete a group
function deleteGroup(groupId) {
    fetch('<?php echo SITE_URL; ?>/api/location_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete_group', group_id: groupId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("<?php echo __('group_deleted'); ?>");
            window.location.reload();
        } else {
            alert(data.message || "<?php echo __('group_deletion_failed'); ?>");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("<?php echo __('group_deletion_failed'); ?>");
    });
}

// Leave a group
function leaveGroup(groupId) {
    fetch('<?php echo SITE_URL; ?>/api/location_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'leave_group', group_id: groupId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("<?php echo __('group_left'); ?>");
            window.location.reload();
        } else {
            alert(data.message || "<?php echo __('group_leave_failed'); ?>");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("<?php echo __('group_leave_failed'); ?>");
    });
}

// Remove a member from group
function removeMember(groupId, memberId) {
    fetch('<?php echo SITE_URL; ?>/api/location_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'remove_member', group_id: groupId, member_id: memberId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("<?php echo __('member_removed'); ?>");
            viewGroup(groupId);
        } else {
            alert(data.message || "<?php echo __('member_removal_failed'); ?>");
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("<?php echo __('member_removal_failed'); ?>");
    });
}

// View member details in modal
function viewMember(member) {
    document.getElementById('memberName').textContent = member.full_name;
    document.getElementById('memberContact').textContent = member.phone || '<?php echo __('not_available'); ?>';
    document.getElementById('memberLastUpdate').textContent = member.timestamp ? formatTimeAgo(member.timestamp) : '<?php echo __('never'); ?>';

    const contactBtn = document.getElementById('memberContactBtn');
    if (member.phone) {
        contactBtn.href = `tel:${member.phone}`;
        contactBtn.style.display = 'block';
    } else {
        contactBtn.style.display = 'none';
    }

    viewMemberModal.show();

    if (member.latitude && member.longitude) {
        setTimeout(() => {
            if (!memberMap) {
                memberMap = L.map('memberMapContainer').setView([member.latitude, member.longitude], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(memberMap);
            } else {
                memberMap.setView([member.latitude, member.longitude], 15);
            }

            L.marker([member.latitude, member.longitude], {
                icon: L.divIcon({
                    html: '<i class="fas fa-user-friends"></i>',
                    className: 'group-marker-icon',
                    iconSize: [36, 36],
                    iconAnchor: [18, 36],
                    popupAnchor: [0, -36]
                })
            }).addTo(memberMap).bindPopup(`<b>${member.full_name}</b>`);

            memberMap.invalidateSize();
        }, 300);
    }
}

// Load group members on the map
function loadGroupMembers() {
    Object.values(groupMarkers).forEach(marker => sharingMap.removeLayer(marker));
    groupMarkers = {};

    fetch('<?php echo SITE_URL; ?>/api/location_api.php?action=get_all_members')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.members.forEach(member => {
                    if (member.latitude && member.longitude) {
                        const marker = L.marker([member.latitude, member.longitude], {
                            icon: L.divIcon({
                                html: '<i class="fas fa-user-friends"></i>',
                                className: 'group-marker-icon',
                                iconSize: [36, 36],
                                iconAnchor: [18, 36],
                                popupAnchor: [0, -36]
                            })
                        }).addTo(sharingMap);

                        marker.bindPopup(`
                            <b>${member.full_name}</b><br>
                            <small>${formatTimeAgo(member.timestamp)}</small>
                            ${member.phone ? `<br><a href="tel:${member.phone}">${member.phone}</a>` : ''}
                        `);

                        groupMarkers[member.id] = marker;
                    }
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

// Share user location
function shareUserLocation() {
    getCurrentLocation((result) => {
        if (result.success) {
            const lat = result.latitude;
            const lng = result.longitude;

            if (userMarker) {
                userMarker.setLatLng([lat, lng]);
            } else {
                userMarker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        html: '<i class="fas fa-user-circle"></i>',
                        className: 'user-marker-icon',
                        iconSize: [36, 36],
                        iconAnchor: [18, 36],
                        popupAnchor: [0, -36]
                    })
                }).addTo(sharingMap);
                userMarker.bindPopup(`<b><?php echo __('your_location'); ?></b>`);
            }

            fetch('<?php echo SITE_URL; ?>/api/location_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'share_location',
                    latitude: lat,
                    longitude: lng,
                    share_with_group: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('lastShared').style.display = 'block';
                    document.getElementById('lastSharedTime').textContent = new Date().toLocaleTimeString();
                    document.getElementById('sharingStatus').textContent = "<?php echo __('location_shared'); ?>";
                    document.getElementById('sharingStatus').className = 'alert alert-success';
                } else {
                    console.error('Error sharing location:', data.message);
                    document.getElementById('sharingStatus').textContent = "<?php echo __('location_share_failed'); ?>: " + data.message;
                    document.getElementById('sharingStatus').className = 'alert alert-danger';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('sharingStatus').textContent = "<?php echo __('location_share_failed'); ?>: " + error.message;
                document.getElementById('sharingStatus').className = 'alert alert-danger';
            });
        } else {
            document.getElementById('sharingStatus').textContent = "<?php echo __('location_error'); ?>: " + result.error;
            document.getElementById('sharingStatus').className = 'alert alert-danger';
        }
    });
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
.user-marker-icon, .group-marker-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border-radius: 50%;
    border: 2px solid var(--primary-color);
    width: 36px !important;
    height: 36px !important;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}
.user-marker-icon { background-color: var(--primary-color); border-color: white; }
.user-marker-icon i { color: white; font-size: 18px; }
.group-marker-icon i { color: var(--success-color); font-size: 18px; }
.avatar-circle {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 16px;
    font-weight: bold;
}
.bg-pilgrim { background-color: #0A9396; }
.bg-guardian { background-color: #EE9B00; }
.bg-authority { background-color: #AE2012; }
.bg-admin { background-color: #2B9348; }
</style>

<?php
// Include footer
include_once '../includes/footer.php';
?>