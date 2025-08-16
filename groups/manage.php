<?php
// Require authentication
$requiredRole = 'pilgrim';
require_once '../includes/auth_check.php';

// Set page title
$pageTitle = __('manage_group');

// Enable Leaflet for maps
$useLeaflet = true;

// Include header
require_once '../includes/header.php';

// Validate group ID
$groupId = filter_input(INPUT_GET, 'group_id', FILTER_VALIDATE_INT);
if (!$groupId) {
    header('Location: ../dashboard/pilgrim.php');
    exit;
}

// Fetch group details
$group = fetchRow("SELECT id, name, creator_id FROM groups WHERE id = ?", [$groupId]);

if (!$group) {
    $error = __('group_not_found');
} else {
    // Check if user is a member
    $isMember = fetchRow(
        "SELECT id FROM group_members WHERE group_id = ? AND user_id = ?",
        [$groupId, $currentUser['id']]
    );

    if (!$isMember) {
        $error = __('not_group_member');
    } else {
        // Fetch group members
        $groupMembers = fetchAll("
            SELECT u.id, u.full_name, u.phone, l.latitude, l.longitude, l.timestamp
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
            WHERE gm.group_id = ? AND u.id != ?
        ", [$groupId, $currentUser['id']]);
    }
}

// Handle member removal (only for creator)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $group['creator_id'] === $currentUser['id']) {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $memberId = filter_input(INPUT_POST, 'member_id', FILTER_VALIDATE_INT);

    if ($csrfToken !== $_SESSION['csrf_token']) {
        $error = __('invalid_csrf_token');
    } elseif (!$memberId) {
        $error = __('invalid_member_id');
    } else {
        $result = executeQuery(
            "DELETE FROM group_members WHERE group_id = ? AND user_id = ?",
            [$groupId, $memberId]
        );

        if ($result) {
            $success = __('member_removed_successfully');
            header("Location: manage.php?group_id=$groupId");
            exit;
        } else {
            $error = __('member_removal_failed');
        }
    }
}
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i> <?php echo __('manage_group'); ?> - <?php echo htmlspecialchars($group['name'] ?? ''); ?></h5>
                    <?php if ($group && $group['creator_id'] === $currentUser['id']): ?>
                        <a href="invite.php?group_id=<?php echo $groupId; ?>" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i> <?php echo __('invite_members'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php elseif (isset($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    <?php if ($group && $isMember): ?>
                        <!-- Group Members Map -->
                        <div id="groupMap" class="map-container mb-4" style="height: 400px;"></div>
                        <!-- Group Members List -->
                        <h6><?php echo __('group_members'); ?></h6>
                        <?php if (empty($groupMembers)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-users text-muted mb-3" style="font-size: 2rem;"></i>
                                <p><?php echo __('no_group_members'); ?></p>
                            </div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($groupMembers as $member): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($member['full_name']); ?></h6>
                                            <?php if (isset($member['timestamp'])): ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i> <?php echo formatDate($member['timestamp']); ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted"><?php echo __('no_location_data'); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <?php if (isset($member['latitude']) && isset($member['longitude'])): ?>
                                                <button class="btn btn-sm btn-outline-primary show-on-map"
                                                        data-lat="<?php echo htmlspecialchars($member['latitude']); ?>"
                                                        data-lng="<?php echo htmlspecialchars($member['longitude']); ?>"
                                                        data-name="<?php echo htmlspecialchars($member['full_name']); ?>">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($group['creator_id'] === $currentUser['id']): ?>
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="member_id" value="<?php echo $member['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger ms-2" onclick="return confirm('<?php echo __('confirm_remove_member'); ?>');">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
    let groupMap;
    let groupMarkers = L.markerClusterGroup();

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize map
        groupMap = L.map('groupMap').setView([21.4225, 39.8262], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(groupMap);
        groupMap.addLayer(groupMarkers);

        // Add Kaaba marker
        const kaabaIcon = L.divIcon({
            html: '<i class="fas fa-kaaba"></i>',
            className: 'kaaba-marker-icon',
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -36]
        });
        L.marker([21.4225, 39.8262], { icon: kaabaIcon }).addTo(groupMap).bindPopup("<b><?php echo __('kaaba'); ?></b>");

        // Show members on map
        document.querySelectorAll('.show-on-map').forEach(button => {
            button.addEventListener('click', function() {
                const lat = parseFloat(this.getAttribute('data-lat'));
                const lng = parseFloat(this.getAttribute('data-lng'));
                const name = this.getAttribute('data-name');

                groupMap.setView([lat, lng], 16);

                const icon = L.divIcon({
                    html: '<i class="fas fa-user-friends"></i>',
                    className: 'group-marker-icon',
                    iconSize: [36, 36],
                    iconAnchor: [18, 36],
                    popupAnchor: [0, -36]
                });

                const marker = L.marker([lat, lng], { icon }).bindPopup(`<b>${name}</b>`);
                groupMarkers.addLayer(marker);
                marker.openPopup();
            });
        });
    });
</script>

<style>
    .map-container {
        width: 100%;
        border-radius: 0.25rem;
        overflow: hidden;
    }
    .group-marker-icon, .kaaba-marker-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: white;
        border-radius: 50%;
        border: 2px solid var(--primary);
        width: 36px !important;
        height: 36px !important;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
    .group-marker-icon i {
        color: var(--success);
        font-size: 18px;
    }
    .kaaba-marker-icon i {
        color: #21618C;
        font-size: 18px;
    }
</style>

<?php
// Include footer
require_once '../includes/footer.php';
?>