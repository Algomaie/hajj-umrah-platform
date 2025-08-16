<?php

// Include header
include_once('../includes/header.php');
// Set page title
$pageTitle = __('qibla_direction');

?>

<div class="qibla-container">
    <div class="qibla-header">
        <h1><i class="fas fa-compass me-2"></i> <?php echo __('qibla_direction'); ?></h1>
        <p class="lead"><?php echo __('qibla_description'); ?></p>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="qibla-card">
                <div class="qibla-display">
                    <div id="qibla-compass">
                        <div class="compass-circle">
                            <div class="compass-arrow" id="compass-arrow"></div>
                            <div class="compass-degree" id="compass-degree">0°</div>
                            <div class="compass-direction" id="compass-direction"><?php echo __('north'); ?></div>
                        </div>
                    </div>
                    <div class="qibla-info">
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span id="current-location"><?php echo __('locating'); ?>...</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-kaaba"></i>
                            <span id="qibla-angle"><?php echo __('calculating'); ?>...</span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-street-view"></i>
                            <span id="distance-to-makkah"><?php echo __('calculating'); ?>...</span>
                        </div>
                    </div>
                </div>
                
                <div class="qibla-map-container">
                    <div id="qibla-map" style="height: 300px;"></div>
                </div>
            </div>
            
            <div class="qibla-instructions mt-4">
                <h3><i class="fas fa-info-circle me-2"></i> <?php echo __('how_to_use'); ?></h3>
                <ol>
                    <li><?php echo __('qibla_instruction_1'); ?></li>
                    <li><?php echo __('qibla_instruction_2'); ?></li>
                    <li><?php echo __('qibla_instruction_3'); ?></li>
                    <li><?php echo __('qibla_instruction_4'); ?></li>
                </ol>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="qibla-sidebar">
                <div class="sidebar-card">
                    <h4><i class="fas fa-clock me-2"></i> <?php echo __('prayer_times'); ?></h4>
                    <div id="sidebar-prayer-times">
                        <div class="loading-text"><?php echo __('loading'); ?>...</div>
                    </div>
                </div>
                
                <div class="sidebar-card">
                    <h4><i class="fas fa-question-circle me-2"></i> <?php echo __('qibla_facts'); ?></h4>
                    <ul class="qibla-facts">
                        <li><?php echo __('qibla_fact_1'); ?></li>
                        <li><?php echo __('qibla_fact_2'); ?></li>
                        <li><?php echo __('qibla_fact_3'); ?></li>
                    </ul>
                </div>
                <button id="locateButton" class="btn btn-primary w-100">
                        <i class="fas fa-location-arrow me-2"></i> <?php echo __('locate_me'); ?>
                    </button>
                <div class="sidebar-card">
                    <h4><i class="fas fa-lightbulb me-2"></i> <?php echo __('tips'); ?></h4>
                    <div class="alert alert-info">
                        <?php echo __('qibla_tip'); ?>
                    </div>
                    <button class="btn btn-primary w-100" onclick="calibrateCompass()">
                        <i class="fas fa-sync-alt me-2"></i> <?php echo __('recalibrate'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Leaflet.js for maps -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<script>
// Coordinates of the Kaaba in Makkah
const KAABA_COORDS = {
    lat: 21.422487,
    lng: 39.826206
};

let map;
let userMarker;
let qiblaLine;
let compassPermissionGranted = false;

document.addEventListener('DOMContentLoaded', function() {
    initializeCompass();
    initializeMap();
    loadPrayerTimes();
    
    // Check if device has compass capability
    if (!window.DeviceOrientationEvent || !'ontouchstart' in window) {
        document.getElementById('compass-direction').textContent = '<?php echo __('compass_not_supported'); ?>';
    }
});

function initializeMap() {
    // Create map centered on a default location (will be updated with user's location)
    map = L.map('qibla-map').setView([0, 0], 2);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Add Kaaba marker
    L.marker(KAABA_COORDS, {
        icon: L.divIcon({
            className: 'kaaba-marker',
            html: '<i class="fas fa-kaaba"></i>',
            iconSize: [30, 30]
        })
    }).addTo(map).bindPopup('<?php echo __('kaaba_location'); ?>');
    
    // Get user's location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            const userCoords = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            
            updateLocationInfo(userCoords);
            updateMap(userCoords);
            calculateQiblaDirection(userCoords);
            
            // Get address information
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${userCoords.lat}&lon=${userCoords.lng}`)
                .then(response => response.json())
                .then(data => {
                    const address = data.address || {};
                    const locationText = [
                        address.city || address.town || address.village || '',
                        address.country || ''
                    ].filter(Boolean).join(', ');
                    
                    if (locationText) {
                        document.getElementById('current-location').textContent = locationText;
                    }
                });
        }, error => {
            console.error('Error getting location:', error);
            document.getElementById('current-location').textContent = '<?php echo __('location_error'); ?>';
            
            // Default to Riyadh coordinates if location access is denied
            const defaultCoords = { lat: 24.7136, lng: 46.6753 };
            updateLocationInfo(defaultCoords);
            updateMap(defaultCoords);
            calculateQiblaDirection(defaultCoords);
        }, {
            enableHighAccuracy: true,
            timeout: 10000
        });
    } else {
        document.getElementById('current-location').textContent = '<?php echo __('geolocation_not_supported'); ?>';
    }
}

function initializeCompass() {
    // Request permission for device orientation on iOS 13+
    if (typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission === 'function') {
        document.getElementById('compass-direction').textContent = '<?php echo __('tap_to_enable_compass'); ?>';
        
        document.getElementById('qibla-compass').addEventListener('click', function() {
            DeviceOrientationEvent.requestPermission()
                .then(response => {
                    if (response === 'granted') {
                        compassPermissionGranted = true;
                        window.addEventListener('deviceorientation', handleOrientation);
                    }
                })
                .catch(console.error);
        });
    } else {
        // For non-iOS devices
        window.addEventListener('deviceorientation', handleOrientation);
    }
}

function handleOrientation(event) {
    if (!compassPermissionGranted && typeof DeviceOrientationEvent.requestPermission === 'function') {
        return;
    }
    
    const arrow = document.getElementById('compass-arrow');
    const degreeDisplay = document.getElementById('compass-degree');
    const directionDisplay = document.getElementById('compass-direction');
    
    // Check if absolute orientation is available
    if (event.absolute && event.alpha !== null) {
        const alpha = event.alpha;  // Compass heading (0-360)
        const beta = event.beta;    // Front-to-back tilt
        const gamma = event.gamma;  // Left-to-right tilt
        
        // Rotate the compass arrow
        arrow.style.transform = `rotate(${alpha}deg)`;
        degreeDisplay.textContent = `${Math.round(alpha)}°`;
        
        // Determine cardinal direction
        const directions = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
        const index = Math.round(alpha / 45) % 8;
        directionDisplay.textContent = __('' + directions[index].toLowerCase());
    }
}

function calculateQiblaDirection(userCoords) {
    // Calculate the Qibla direction using spherical trigonometry
    const phi1 = userCoords.lat * Math.PI / 180;
    const lambda1 = userCoords.lng * Math.PI / 180;
    const phi2 = KAABA_COORDS.lat * Math.PI / 180;
    const lambda2 = KAABA_COORDS.lng * Math.PI / 180;
    
    const y = Math.sin(lambda2 - lambda1) * Math.cos(phi2);
    const x = Math.cos(phi1) * Math.sin(phi2) - 
              Math.sin(phi1) * Math.cos(phi2) * Math.cos(lambda2 - lambda1);
    const theta = Math.atan2(y, x);
    const qiblaAngle = (theta * 180 / Math.PI + 360) % 360;
    
    // Display the Qibla angle
    document.getElementById('qibla-angle').innerHTML = `
        ${Math.round(qiblaAngle)}° <?php echo __('from_north'); ?><br>
        <small>${getQiblaDirectionText(qiblaAngle)}</small>
    `;
    
    // Calculate distance to Makkah
    const distance = calculateDistance(userCoords, KAABA_COORDS);
    document.getElementById('distance-to-makkah').textContent = 
        `${distance.toLocaleString()} km ${__('to_makkah')}`;
    
    // Rotate compass arrow to Qibla direction (for non-device orientation)
    if (!compassPermissionGranted) {
        document.getElementById('compass-arrow').style.transform = `rotate(${qiblaAngle}deg)`;
        document.getElementById('compass-degree').textContent = `${Math.round(qiblaAngle)}°`;
    }
    
    return qiblaAngle;
}

function calculateDistance(coords1, coords2) {
    // Haversine formula to calculate distance between two coordinates
    const R = 6371; // Earth radius in km
    const dLat = (coords2.lat - coords1.lat) * Math.PI / 180;
    const dLon = (coords2.lng - coords1.lng) * Math.PI / 180;
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(coords1.lat * Math.PI / 180) * 
        Math.cos(coords2.lat * Math.PI / 180) *
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return Math.round(R * c);
}

function updateMap(userCoords) {
    // Set map view to user's location
    map.setView([userCoords.lat, userCoords.lng], 13);
    
    // Add or update user marker
    if (userMarker) {
        userMarker.setLatLng([userCoords.lat, userCoords.lng]);
    } else {
        userMarker = L.marker([userCoords.lat, userCoords.lng], {
            icon: L.divIcon({
                className: 'user-marker',
                html: '<i class="fas fa-user"></i>',
                iconSize: [25, 25]
            })
        }).addTo(map).bindPopup('<?php echo __('your_location'); ?>');
    }
    
    // Draw Qibla line
    if (qiblaLine) {
        map.removeLayer(qiblaLine);
    }
    qiblaLine = L.polyline([userCoords, KAABA_COORDS], {
        color: '#d63031',
        dashArray: '5, 5',
        weight: 2
    }).addTo(map);
}

function updateLocationInfo(coords) {
    document.getElementById('current-location').textContent = 
        `${coords.lat.toFixed(4)}, ${coords.lng.toFixed(4)}`;
}

function getQiblaDirectionText(angle) {
    const directions = [
        { min: 0, max: 22.5, name: 'north' },
        { min: 22.5, max: 67.5, name: 'northeast' },
        { min: 67.5, max: 112.5, name: 'east' },
        { min: 112.5, max: 157.5, name: 'southeast' },
        { min: 157.5, max: 202.5, name: 'south' },
        { min: 202.5, max: 247.5, name: 'southwest' },
        { min: 247.5, max: 292.5, name: 'west' },
        { min: 292.5, max: 337.5, name: 'northwest' },
        { min: 337.5, max: 360, name: 'north' }
    ];
    
    const direction = directions.find(d => angle >= d.min && angle < d.max);
    return direction ? __('' + direction.name) : '';
}

function calibrateCompass() {
    if (window.DeviceOrientationEvent) {
        alert('<?php echo __('calibration_instructions'); ?>');
    } else {
        alert('<?php echo __('compass_not_available'); ?>');
    }
}

function loadPrayerTimes() {
    const container = document.getElementById('sidebar-prayer-times');
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            const { latitude, longitude } = position.coords;
            
            fetch(`https://api.aladhan.com/v1/timings?latitude=${latitude}&longitude=${longitude}&method=2`)
                .then(response => response.json())
                .then(data => {
                    const timings = data.data.timings;
                    container.innerHTML = `
                        <div class="prayer-time-item">
                            <span>${__('fajr')}</span>
                            <span>${formatTime(timings.Fajr)}</span>
                        </div>
                        <div class="prayer-time-item">
                            <span>${__('dhuhr')}</span>
                            <span>${formatTime(timings.Dhuhr)}</span>
                        </div>
                        <div class="prayer-time-item">
                            <span>${__('asr')}</span>
                            <span>${formatTime(timings.Asr)}</span>
                        </div>
                        <div class="prayer-time-item">
                            <span>${__('maghrib')}</span>
                            <span>${formatTime(timings.Maghrib)}</span>
                        </div>
                        <div class="prayer-time-item">
                            <span>${__('isha')}</span>
                            <span>${formatTime(timings.Isha)}</span>
                        </div>
                    `;
                })
                .catch(() => {
                    container.innerHTML = '<div class="error-text"><?php echo __('prayer_times_error'); ?></div>';
                });
        }, () => {
            // Fallback to Makkah prayer times
            fetch('https://api.aladhan.com/v1/timingsByCity?city=Makkah&country=SaudiArabia&method=2')
                .then(response => response.json())
                .then(data => {
                    const timings = data.data.timings;
                    container.innerHTML = `
                        <div class="prayer-time-item">
                            <span>${__('fajr')}</span>
                            <span>${formatTime(timings.Fajr)}</span>
                        </div>
                        <div class="prayer-time-item">
                            <span>${__('dhuhr')}</span>
                            <span>${formatTime(timings.Dhuhr)}</span>
                        </div>
                        <div class="prayer-time-item">
                            <span>${__('asr')}</span>
                            <span>${formatTime(timings.Asr)}</span>
                        </div>
                        <div class="prayer-time-item">
                            <span>${__('maghrib')}</span>
                            <span>${formatTime(timings.Maghrib)}</span>
                        </div>
                        <div class="prayer-time-item">
                            <span>${__('isha')}</span>
                            <span>${formatTime(timings.Isha)}</span>
                        </div>
                    `;
                });
        });
    }
}

function formatTime(timeStr) {
    const [hours, minutes] = timeStr.split(':');
    const period = hours >= 12 ? 'م' : 'ص';
    const adjustedHours = hours % 12 || 12;
    return `${adjustedHours}:${minutes} ${period}`;
}
</script>

<style>
.qibla-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.qibla-header {
    text-align: center;
    margin-bottom: 30px;
}

.qibla-header h1 {
    color: #2c3e50;
}

.qibla-card {
    background-color: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.qibla-display {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 20px;
    align-items: center;
    justify-content: center;
}

#qibla-compass {
    width: 200px;
    height: 200px;
    position: relative;
}

.compass-circle {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background-color: #f8f9fa;
    border: 3px solid #3498db;
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.compass-arrow {
    width: 0;
    height: 0;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-bottom: 80px solid #e74c3c;
    position: absolute;
    top: 10px;
    transform-origin: 50% 80px;
    transition: transform 0.1s ease-out;
}

.compass-degree {
    font-size: 1.5em;
    font-weight: bold;
    color: #2c3e50;
    margin-top: 10px;
}

.compass-direction {
    font-size: 1.2em;
    color: #7f8c8d;
}

.qibla-info {
    flex: 1;
    min-width: 250px;
}

.info-item {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
    padding: 10px;
    background-color: #f8f9fa;
    border-radius: 5px;
}

.info-item i {
    font-size: 1.5em;
    margin-right: 15px;
    color: #3498db;
    width: 30px;
    text-align: center;
}

.qibla-map-container {
    height: 300px;
    border-radius: 8px;
    overflow: hidden;
    margin-top: 20px;
}

.qibla-instructions {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
}

.qibla-instructions ol {
    padding-left: 20px;
}

.qibla-instructions li {
    margin-bottom: 10px;
}

.qibla-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.sidebar-card {
    background-color: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.sidebar-card h4 {
    color: #2c3e50;
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.prayer-time-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px dashed #eee;
}

.prayer-time-item:last-child {
    border-bottom: none;
}

.qibla-facts li {
    margin-bottom: 10px;
    padding-left: 5px;
}

.kaaba-marker i {
    color: #d63031;
    font-size: 20px;
}

.user-marker i {
    color: #3498db;
    font-size: 18px;
}

.loading-text, .error-text {
    text-align: center;
    padding: 10px;
    font-style: italic;
    color: #7f8c8d;
}

.error-text {
    color: #e74c3c;
}

/* RTL support */
[dir="rtl"] .info-item i,
[dir="rtl"] .prayer-time-item {
    margin-right: 0;
    margin-left: 15px;
}

[dir="rtl"] .qibla-instructions ol {
    padding-left: 0;
    padding-right: 20px;
}

[dir="rtl"] .qibla-facts li {
    padding-left: 0;
    padding-right: 5px;
}
</style>

<?php include_once('../includes/footer.php'); ?>