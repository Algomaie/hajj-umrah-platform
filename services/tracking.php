<?php
require_once '../includes/auth_check.php';

// Set page title and Leaflet flag
$pageTitle = __('real_time_tracking');
$useLeaflet = true;

require_once '../includes/header.php';
// // Constants
// const KAABA_LAT = 21.4225;
// const KAABA_LNG = 39.8262;

// Fetch user's active groups
$groups = fetchAll("
    SELECT g.id, g.name, g.invite_code
    FROM groups g
    JOIN group_members gm ON g.id = gm.group_id
    WHERE gm.user_id = ? AND g.active = 1
", [$currentUser['id']]);

// Fetch group members with latest locations
$groupMembers = [];
foreach ($groups as $group) {
    $members = fetchAll("
        SELECT 
            u.id, u.full_name, u.phone, u.profile_image,
            l.latitude, l.longitude, l.timestamp,
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
        WHERE gm.group_id = ? AND u.id != ?
        ORDER BY u.full_name
    ", [$group['id'], $currentUser['id']]);
    $groupMembers = array_merge($groupMembers, $members);
}

// Fetch user's last location
$lastLocation = fetchRow("
    SELECT latitude, longitude, accuracy, timestamp, 
           TIMESTAMPDIFF(MINUTE, timestamp, NOW()) as minutes_ago
    FROM locations 
    WHERE user_id = ? 
    ORDER BY timestamp DESC 
    LIMIT 1
", [$currentUser['id']]);

?>

<div class="container py-4">
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('close'); ?>"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2"><i class="fas fa-map-marked-alt me-2" aria-hidden="true"></i> <?php echo __('group_tracking'); ?></h1>
                <button id="refreshData" class="btn btn-outline-secondary btn-sm" aria-label="<?php echo __('refresh_data'); ?>">
                    <i class="fas fa-sync-alt" aria-hidden="true"></i>
                </button>
            </div>

            <!-- Map Container -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo __('live_location_map'); ?></h5>
                    <div class="btn-group">
                        <button id="centerMap" class="btn btn-outline-primary btn-sm" aria-label="<?php echo __('center_on_me'); ?>">
                            <i class="fas fa-location-arrow" aria-hidden="true"></i> <?php echo __('center_on_me'); ?>
                        </button>
                        <button id="toggleSatellite" class="btn btn-outline-primary btn-sm" aria-label="<?php echo __('toggle_satellite'); ?>">
                            <i class="fas fa-satellite" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="trackingMap" class="map-container" role="region" aria-label="<?php echo __('interactive_map'); ?>"></div>
                </div>
            </div>

            <!-- Location Info -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2" aria-hidden="true"></i> <?php echo __('your_location'); ?></h5>
                </div>
                <div class="card-body">
                    <div id="locationStatus" class="alert alert-info <?php echo $lastLocation ? 'd-none' : ''; ?>">
                        <i class="fas fa-info-circle me-2" aria-hidden="true"></i> <?php echo __('location_unknown'); ?>
                    </div>
                    <div id="locationDetails" class="<?php echo $lastLocation ? '' : 'd-none'; ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <h6><?php echo __('coordinates'); ?></h6>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-map-pin me-2 text-primary" aria-hidden="true"></i>
                                    <span id="coordinates" class="font-monospace">
                                        <?php echo $lastLocation ? number_format($lastLocation['latitude'], 6) . ', ' . number_format($lastLocation['longitude'], 6) : ''; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h6><?php echo __('last_updated'); ?></h6>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock me-2 text-primary" aria-hidden="true"></i>
                                    <span id="lastUpdated">
                                        <?php echo $lastLocation ? formatDate($lastLocation['timestamp']) : ''; ?>
                                        <?php if ($lastLocation && $lastLocation['minutes_ago'] > 5): ?>
                                            <span class="badge bg-warning ms-2"><?php echo __('minutes_ago', ['count' => $lastLocation['minutes_ago']]); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h6><?php echo __('distance_to_kaaba'); ?></h6>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-kaaba me-2 text-primary" aria-hidden="true"></i>
                                    <span id="distanceToKaaba"><?php echo __('calculating'); ?>...</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h6><?php echo __('current_address'); ?></h6>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-map-marker-alt me-2 text-primary" aria-hidden="true"></i>
                                    <span id="currentAddress"><?php echo __('fetching_address'); ?>...</span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button id="updateLocation" class="btn btn-primary">
                                <i class="fas fa-location-arrow me-1" aria-hidden="true"></i> <?php echo __('update_location'); ?>
                            </button>
                            <button id="shareLocation" class="btn btn-outline-primary">
                                <i class="fas fa-share-alt me-1" aria-hidden="true"></i> <?php echo __('share_with_group'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Group Members -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users me-2" aria-hidden="true"></i> <?php echo __('group_members'); ?></h5>
                    <span class="badge bg-primary rounded-pill"><?php echo count($groupMembers); ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($groupMembers)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-friends text-muted mb-3" style="font-size: 3rem;" aria-hidden="true"></i>
                            <p class="text-muted"><?php echo __('no_group_members_found'); ?></p>
                            <a href="<?php echo htmlspecialchars(SITE_URL); ?>/services/manage.php" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2" aria-hidden="true"></i> <?php echo __('add_members'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($groupMembers as $member): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <?php if ($member['profile_image']): ?>
                                                <img src="<?php echo htmlspecialchars(SITE_URL . '/uploads/profiles/' . $member['profile_image']); ?>" 
                                                     class="rounded-circle" width="40" height="40" 
                                                     alt="<?php echo htmlspecialchars($member['full_name']); ?>" 
                                                     loading="lazy">
                                            <?php else: ?>
                                                <div class="avatar-circle bg-<?php echo getAvatarColor($member['user_type'] ?? 'pilgrim'); ?> text-white" 
                                                     style="width: 40px; height: 40px; line-height: 40px;">
                                                    <?php echo getInitials($member['full_name']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($member['full_name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($member['group_name']); ?></small>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <small class="text-muted">
                                                    <?php if ($member['latitude'] && $member['longitude']): ?>
                                                        <i class="fas fa-clock me-1" aria-hidden="true"></i>
                                                        <?php echo $member['minutes_ago'] <= 5 ? __('just_now') : __('minutes_ago', ['count' => $member['minutes_ago']]); ?>
                                                    <?php else: ?>
                                                        <?php echo __('location_unknown'); ?>
                                                    <?php endif; ?>
                                                </small>
                                                <div class="btn-group">
                                                    <?php if ($member['latitude'] && $member['longitude']): ?>
                                                        <button class="btn btn-sm btn-outline-primary show-on-map" 
                                                                data-lat="<?php echo $member['latitude']; ?>" 
                                                                data-lng="<?php echo $member['longitude']; ?>" 
                                                                data-name="<?php echo htmlspecialchars($member['full_name']); ?>" 
                                                                aria-label="<?php echo __('show_on_map'); ?>">
                                                            <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($member['phone']): ?>
                                                        <a href="tel:<?php echo htmlspecialchars($member['phone']); ?>" 
                                                           class="btn btn-sm btn-outline-success" 
                                                           aria-label="<?php echo __('call') . ' ' . htmlspecialchars($member['full_name']); ?>">
                                                            <i class="fas fa-phone-alt" aria-hidden="true"></i>
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

            <!-- Tracking Controls -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-sliders-h me-2" aria-hidden="true"></i> <?php echo __('tracking_settings'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="trackingMode" class="form-label"><?php echo __('tracking_mode'); ?></label>
                        <select id="trackingMode" class="form-select" aria-describedby="trackingModeHelp">
                            <option value="manual"><?php echo __('manual_updates'); ?></option>
                            <option value="interval"><?php echo __('periodic_updates'); ?></option>
                            <option value="realtime"><?php echo __('real_time'); ?></option>
                        </select>
                        <small id="trackingModeHelp" class="form-text"><?php echo __('tracking_mode_help'); ?></small>
                    </div>
                    <div id="intervalSettings" class="mb-3 d-none">
                        <label for="updateInterval" class="form-label"><?php echo __('update_interval'); ?></label>
                        <select id="updateInterval" class="form-select">
                            <option value="30"><?php echo __('every_30_seconds'); ?></option>
                            <option value="60" selected><?php echo __('every_minute'); ?></option>
                            <option value="300"><?php echo __('every_5_minutes'); ?></option>
                            <option value="600"><?php echo __('every_10_minutes'); ?></option>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="enableBatterySaver" aria-label="<?php echo __('battery_saver'); ?>">
                        <label class="form-check-label" for="enableBatterySaver"><?php echo __('battery_saver'); ?></label>
                    </div>
                    <div class="d-grid gap-2">
                        <button id="startTracking" class="btn btn-success">
                            <i class="fas fa-play me-2" aria-hidden="true"></i> <?php echo __('start_tracking'); ?>
                        </button>
                        <button id="stopTracking" class="btn btn-outline-danger" disabled>
                            <i class="fas fa-stop me-2" aria-hidden="true"></i> <?php echo __('stop_tracking'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Device Status -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-battery-three-quarters me-2" aria-hidden="true"></i> <?php echo __('device_status'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <div class="h4 mb-1" id="batteryLevel">--%</div>
                            <small class="text-muted"><?php echo __('battery'); ?></small>
                        </div>
                        <div class="col-6">
                            <div class="h4 mb-1" id="signalStrength">--</div>
                            <small class="text-muted"><?php echo __('signal'); ?></small>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress mb-2" style="height: 6px;">
                            <div id="batteryBar" class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div id="signalBar" class="progress-bar bg-info" role="progressbar" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/jsab32nVPoN8/5P3M=" crossorigin="anonymous">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qijJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>

<script>
// Tracking state
let trackingMap, userMarker, kaabaMarker, accuracyCircle;
let groupMarkers = {};
let locationWatchId = null, updateIntervalId = null;
let isTracking = false, currentTrackingMode = 'manual', batterySaverEnabled = false;

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    initTrackingMap();
    setupEventListeners();
    checkBatteryStatus();
    updateSignalStrength();
});

// Initialize Leaflet map
function initTrackingMap() {
    const defaultPos = [<?php echo $lastLocation ? $lastLocation['latitude'] . ',' . $lastLocation['longitude'] : 'KAABA_LAT,KAABA_LNG'; ?>];
    const defaultZoom = <?php echo $lastLocation ? '17' : '15'; ?>;

    trackingMap = L.map('trackingMap', {
        zoomControl: false,
        gestureHandling: true
    }).setView(defaultPos, defaultZoom);

    // Map layers
    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(trackingMap);

    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: '© <a href="https://www.esri.com/">Esri</a>',
        maxZoom: 19
    });

    // Kaaba marker
    kaabaMarker = L.marker([KAABA_LAT, KAABA_LNG], {
        icon: L.divIcon({
            html: '<i class="fas fa-kaaba" style="color: #8B4513; font-size: 24px;"></i>',
            className: 'kaaba-marker',
            iconSize: [30, 30],
            iconAnchor: [15, 30]
        }),
        zIndexOffset: 1000
    }).addTo(trackingMap).bindPopup("<b><?php echo __('kaaba'); ?></b>");

    // User marker
    <?php if ($lastLocation): ?>
        addUserMarker(<?php echo $lastLocation['latitude']; ?>, <?php echo $lastLocation['longitude']; ?>);
        updateLocationInfo(<?php echo $lastLocation['latitude']; ?>, <?php echo $lastLocation['longitude']; ?>, <?php echo $lastLocation['accuracy'] ?? 'null'; ?>);
    <?php endif; ?>

    // Group member markers
    <?php foreach ($groupMembers as $member): ?>
        <?php if ($member['latitude'] && $member['longitude']): ?>
            addGroupMemberMarker(
                <?php echo $member['latitude']; ?>, 
                <?php echo $member['longitude']; ?>, 
                "<?php echo htmlspecialchars($member['full_name']); ?>",
                "<?php echo htmlspecialchars($member['group_name']); ?>",
                <?php echo $member['minutes_ago']; ?>
            );
        <?php endif; ?>
    <?php endforeach; ?>

    // Layer control
    L.control.layers({
        "<?php echo __('street_map'); ?>": osmLayer,
        "<?php echo __('satellite'); ?>": satelliteLayer
    }, null, { position: 'bottomright' }).addTo(trackingMap);

    L.control.zoom({ position: 'bottomright' }).addTo(trackingMap);
}

// Event listeners
function setupEventListeners() {
    document.getElementById('updateLocation').addEventListener('click', updateLocation);
    document.getElementById('shareLocation').addEventListener('click', shareLocation);
    document.getElementById('centerMap').addEventListener('click', centerMapOnUser);
    document.getElementById('toggleSatellite').addEventListener('click', toggleSatelliteView);
    document.getElementById('refreshData').addEventListener('click', refreshGroupData);
    document.getElementById('trackingMode').addEventListener('change', function() {
        currentTrackingMode = this.value;
        document.getElementById('intervalSettings').classList.toggle('d-none', this.value !== 'interval');
        if (isTracking) { stopTracking(); startTracking(); }
    });
    document.getElementById('enableBatterySaver').addEventListener('change', function() {
        batterySaverEnabled = this.checked;
    });
    document.getElementById('startTracking').addEventListener('click', startTracking);
    document.getElementById('stopTracking').addEventListener('click', stopTracking);
    document.querySelectorAll('.show-on-map').forEach(btn => {
        btn.addEventListener('click', () => {
            const lat = parseFloat(btn.dataset.lat);
            const lng = parseFloat(btn.dataset.lng);
            trackingMap.setView([lat, lng], 17);
            if (groupMarkers[btn.dataset.name]) groupMarkers[btn.dataset.name].openPopup();
        });
    });
}

// Add user marker
function addUserMarker(lat, lng) {
    const icon = L.divIcon({
        html: '<i class="fas fa-user" style="font-size: 24px;"></i>',
        className: 'user-marker',
        iconSize: [30, 30],
        iconAnchor: [15, 30]
    });
    if (userMarker) {
        userMarker.setLatLng([lat, lng]);
    } else {
        userMarker = L.marker([lat, lng], { icon, zIndexOffset: 1000 })
            .addTo(trackingMap)
            .bindPopup("<b><?php echo __('your_location'); ?></b>");
    }
}

// Add group member marker
function addGroupMemberMarker(lat, lng, name, group, minutesAgo) {
    const icon = L.divIcon({
        html: '<i class="fas fa-user-friends" style="font-size: 24px;"></i>',
        className: 'group-marker',
        iconSize: [30, 30],
        iconAnchor: [15, 30]
    });
    if (groupMarkers[name]) {
        groupMarkers[name].setLatLng([lat, lng]);
    } else {
        groupMarkers[name] = L.marker([lat, lng], { icon })
            .addTo(trackingMap)
            .bindPopup(`<b>${name}</b><br>${group}<br><small>${minutesAgo} <?php echo __('minutes_ago'); ?></small>`);
    }
}

// Update location
function updateLocation() {
    showLoadingStatus('<?php echo __('getting_location'); ?>...');
    getCurrentLocation()
        .then(position => {
            const { latitude, longitude, accuracy } = position.coords;
            updateLocationInfo(latitude, longitude, accuracy);
            addUserMarker(latitude, longitude);
            if (!isTracking) trackingMap.setView([latitude, longitude], 17);
            saveLocation(latitude, longitude, accuracy);
        })
        .catch(error => {
            showErrorStatus(getLocationError(error));
            console.error('Location error:', error);
        });
}

// Update location info
async function updateLocationInfo(lat, lng, accuracy) {
    document.getElementById('locationStatus').classList.add('d-none');
    document.getElementById('locationDetails').classList.remove('d-none');

    document.getElementById('coordinates').textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    document.getElementById('lastUpdated').textContent = new Date().toLocaleString();

    const distance = calculateDistance(lat, lng, KAABA_LAT, KAABA_LNG);
    document.getElementById('distanceToKaaba').textContent = `${formatDistance(distance)} <?php echo __('from_kaaba'); ?>`;

    try {
        const address = await reverseGeocode(lat, lng);
        document.getElementById('currentAddress').textContent = address;
    } catch {
        document.getElementById('currentAddress').textContent = '<?php echo __('address_unavailable'); ?>';
    }

    if (accuracy) {
        if (accuracyCircle) {
            accuracyCircle.setLatLng([lat, lng]).setRadius(accuracy);
        } else {
            accuracyCircle = L.circle([lat, lng], {
                radius: accuracy,
                fillColor: '#3388ff',
                color: '#3388ff',
                fillOpacity: 0.2
            }).addTo(trackingMap);
        }
    }
}

// Start tracking
function startTracking() {
    if (isTracking) return;
    showLoadingStatus('<?php echo __('starting_tracking'); ?>...');
    getCurrentLocation()
        .then(position => {
            const { latitude, longitude, accuracy } = position.coords;
            updateLocationInfo(latitude, longitude, accuracy);
            addUserMarker(latitude, longitude);
            saveLocation(latitude, longitude, accuracy);

            isTracking = true;
            document.getElementById('startTracking').disabled = true;
            document.getElementById('stopTracking').disabled = false;

            switch (currentTrackingMode) {
                case 'interval':
                    const interval = parseInt(document.getElementById('updateInterval').value) * 1000;
                    updateIntervalId = setInterval(updateLocation, interval);
                    showSuccessStatus(`<?php echo __('updating_every'); ?> ${interval / 1000} <?php echo __('seconds'); ?>`);
                    break;
                case 'realtime':
                    locationWatchId = navigator.geolocation.watchPosition(
                        position => {
                            const { latitude, longitude, accuracy } = position.coords;
                            updateLocationInfo(latitude, longitude, accuracy);
                            addUserMarker(latitude, longitude);
                            if (!batterySaverEnabled || accuracy < 50) saveLocation(latitude, longitude, accuracy);
                        },
                        error => {
                            showErrorStatus(getLocationError(error));
                            stopTracking();
                        },
                        {
                            enableHighAccuracy: !batterySaverEnabled,
                            maximumAge: 0,
                            timeout: 15000
                        }
                    );
                    showSuccessStatus('<?php echo __('real_time_tracking_active'); ?>');
                    break;
                default:
                    showSuccessStatus('<?php echo __('manual_tracking_ready'); ?>');
            }
        })
        .catch(error => {
            showErrorStatus(getLocationError(error));
            console.error('Initial tracking error:', error);
        });
}

// Stop tracking
function stopTracking() {
    if (!isTracking) return;
    if (locationWatchId) {
        navigator.geolocation.clearWatch(locationWatchId);
        locationWatchId = null;
    }
    if (updateIntervalId) {
        clearInterval(updateIntervalId);
        updateIntervalId = null;
    }
    isTracking = false;
    document.getElementById('startTracking').disabled = false;
    document.getElementById('stopTracking').disabled = true;
    showInfoStatus('<?php echo __('tracking_stopped'); ?>');
}

// Save location to server
async function saveLocation(lat, lng, accuracy) {
    try {
        const response = await fetch('<?php echo htmlspecialchars(SITE_URL); ?>/api/locations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
            },
            body: JSON.stringify({ action: 'update', latitude: lat, longitude: lng, accuracy })
        });
        const data = await response.json();
        if (!data.success) console.error('Error saving location:', data.message);
    } catch (error) {
        console.error('Network error:', error);
    }
}

// Share location
async function shareLocation() {
    const coords = document.getElementById('coordinates').textContent.split(',');
    if (coords.length !== 2) {
        showAlert('<?php echo __('no_location_to_share'); ?>', 'danger');
        return;
    }
    try {
        const response = await fetch('<?php echo htmlspecialchars(SITE_URL); ?>/api/locations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
            },
            body: JSON.stringify({
                action: 'share',
                latitude: parseFloat(coords[0]),
                longitude: parseFloat(coords[1]),
                share_with_groups: true
            })
        });
        const data = await response.json();
        showAlert(data.success ? '<?php echo __('location_shared_success'); ?>' : data.message || '<?php echo __('share_failed'); ?>', data.success ? 'success' : 'danger');
    } catch (error) {
        showAlert('<?php echo __('network_error'); ?>', 'danger');
        console.error('Share error:', error);
    }
}

// Helper functions
function centerMapOnUser() {
    if (userMarker) {
        trackingMap.setView(userMarker.getLatLng(), 17);
    } else {
        showAlert('<?php echo __('no_location_available'); ?>', 'warning');
    }
}

function toggleSatelliteView() {
    const currentLayer = trackingMap.hasLayer(trackingMap._layers[Object.keys(trackingMap._layers)[0]]);
    trackingMap.eachLayer(layer => trackingMap.removeLayer(layer));
    trackingMap.addLayer(currentLayer ? satelliteLayer : osmLayer);
}

async function refreshGroupData() {
    try {
        const response = await fetch('<?php echo htmlspecialchars(SITE_URL); ?>/api/groups.php?action=get_members', {
            headers: {
                'X-CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
            }
        });
        const data = await response.json();
        if (data.success) {
            location.reload(); // Reload to update group members
            showAlert('<?php echo __('group_data_updated'); ?>', 'success');
        } else {
            showAlert(data.message || '<?php echo __('refresh_failed'); ?>', 'danger');
        }
    } catch (error) {
        showAlert('<?php echo __('network_error'); ?>', 'danger');
        console.error('Refresh error:', error);
    }
}

function checkBatteryStatus() {
    if ('getBattery' in navigator) {
        navigator.getBattery().then(battery => {
            updateBatteryStatus(battery);
            battery.addEventListener('levelchange', () => updateBatteryStatus(battery));
        });
    } else {
        document.getElementById('batteryLevel').textContent = '<?php echo __('not_available'); ?>';
    }
}

function updateBatteryStatus(battery) {
    const percent = Math.round(battery.level * 100);
    const batteryBar = document.getElementById('batteryBar');
    document.getElementById('batteryLevel').textContent = `${percent}%`;
    batteryBar.style.width = `${percent}%`;
    batteryBar.className = `progress-bar ${percent < 20 ? 'bg-danger' : percent < 50 ? 'bg-warning' : 'bg-success'}`;
}

function updateSignalStrength() {
    const strength = Math.min(100, Math.floor(Math.random() * 120));
    const signalBar = document.getElementById('signalBar');
    document.getElementById('signalStrength').textContent = strength >= 70 ? '<?php echo __('strong'); ?>' : strength >= 30 ? '<?php echo __('fair'); ?>' : '<?php echo __('weak'); ?>';
    signalBar.style.width = `${strength}%`;
    signalBar.className = `progress-bar ${strength < 30 ? 'bg-danger' : strength < 70 ? 'bg-warning' : 'bg-info'}`;
    setTimeout(updateSignalStrength, 30000);
}

function showLoadingStatus(message) {
    const status = document.getElementById('locationStatus');
    status.classList.remove('d-none', 'alert-success', 'alert-danger');
    status.classList.add('alert-info');
    status.innerHTML = `<i class="fas fa-circle-notch fa-spin me-2" aria-hidden="true"></i> ${message}`;
}

function showSuccessStatus(message) {
    const status = document.getElementById('locationStatus');
    status.classList.remove('d-none', 'alert-info', 'alert-danger');
    status.classList.add('alert-success');
    status.innerHTML = `<i class="fas fa-check-circle me-2" aria-hidden="true"></i> ${message}`;
    setTimeout(() => status.classList.add('d-none'), 3000);
}

function showErrorStatus(message) {
    const status = document.getElementById('locationStatus');
    status.classList.remove('d-none', 'alert-info', 'alert-success');
    status.classList.add('alert-danger');
    status.innerHTML = `<i class="fas fa-exclamation-circle me-2" aria-hidden="true"></i> ${message}`;
}

function showInfoStatus(message) {
    const status = document.getElementById('locationStatus');
    status.classList.remove('d-none', 'alert-success', 'alert-danger');
    status.classList.add('alert-info');
    status.innerHTML = `<i class="fas fa-info-circle me-2" aria-hidden="true"></i> ${message}`;
}

function showAlert(message, type) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.role = 'alert';
    alert.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('close'); ?>"></button>`;
    document.querySelector('.container.py-4').prepend(alert);
    setTimeout(() => alert.classList.remove('show'), 5000);
    setTimeout(() => alert.remove(), 5150);
}

function getCurrentLocation() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) return reject(new Error('<?php echo __('geolocation_unsupported'); ?>'));
        navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: !batterySaverEnabled,
            timeout: 15000,
            maximumAge: 0
        });
    });
}

function getLocationError(error) {
    switch (error.code) {
        case error.PERMISSION_DENIED: return '<?php echo __('location_permission_denied'); ?>';
        case error.POSITION_UNAVAILABLE: return '<?php echo __('location_unavailable'); ?>';
        case error.TIMEOUT: return '<?php echo __('location_timeout'); ?>';
        default: return '<?php echo __('location_error'); ?>';
    }
}

function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
              Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function formatDistance(km) {
    return km < 1 ? `${Math.round(km * 1000)} <?php echo __('meters'); ?>` : `${km.toFixed(1)} <?php echo __('kilometers'); ?>`;
}

async function reverseGeocode(lat, lng) {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=<?php echo $_SESSION['lang'] ?? 'en'; ?>`);
        const data = await response.json();
        return data.display_name || '<?php echo __('address_unavailable'); ?>';
    } catch (error) {
        console.error('Geocoding error:', error);
        return '<?php echo __('address_error'); ?>';
    }
}
</script>

<style>
.map-container {
    height: 500px;
    border-radius: 0.25rem;
    overflow: hidden;
}
.user-marker, .group-marker, .kaaba-marker {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border-radius: 50%;
    border: 2px solid;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
.user-marker { border-color: var(--bs-primary); color: var(--bs-primary); }
.group-marker { border-color: var(--bs-success); color: var(--bs-success); }
.kaaba-marker { border-color: #8B4513; color: #8B4513; }
.avatar-circle {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-weight: bold;
}
.list-group-item {
    transition: all 0.2s ease;
}
.list-group-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.alert-dismissible .btn-close {
    padding: 0.75rem;
}
</style>

<?php require_once '../includes/footer.php'; ?>