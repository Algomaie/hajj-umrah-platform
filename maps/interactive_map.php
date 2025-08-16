<?php

require_once('../includes/functions.php');
// Set page title
$pageTitle = __('interactive_map');

// Use Leaflet for maps
$useLeaflet = true;

// Include header
include_once('../includes/header.php');

?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <h1><?php echo __('interactive_map'); ?></h1>
            <p class="lead"><?php echo __('interactive_map_description'); ?></p>
            
            <!-- Map Container -->
            <div class="card mb-4">
                <div class="card-body p-0">
                    <div id="map" class="map-container"></div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Location Search -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-search me-2"></i> <?php echo __('search_location'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="searchInput" placeholder="<?php echo __('search_placeholder'); ?>">
                        <button class="btn btn-primary" type="button" id="searchButton">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Important Places -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map-pin me-2"></i> <?php echo __('important_places'); ?></h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush" id="placesList">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-kaaba me-2"></i> <?php echo __('kaaba'); ?></span>
                            <button class="btn btn-sm btn-primary place-button" data-lat="21.4225" data-lng="39.8262">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-mosque me-2"></i> <?php echo __('masjid_al_haram'); ?></span>
                            <button class="btn btn-sm btn-primary place-button" data-lat="21.4225" data-lng="39.8262">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-mountain me-2"></i> <?php echo __('mount_arafat'); ?></span>
                            <button class="btn btn-sm btn-primary place-button" data-lat="21.3553" data-lng="39.9841">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-mountain me-2"></i> <?php echo __('muzdalifah'); ?></span>
                            <button class="btn btn-sm btn-primary place-button" data-lat="21.4041" data-lng="39.9362">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-archway me-2"></i> <?php echo __('mina'); ?></span>
                            <button class="btn btn-sm btn-primary place-button" data-lat="21.4133" data-lng="39.8933">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-mosque me-2"></i> <?php echo __('masjid_al_nabawi'); ?></span>
                            <button class="btn btn-sm btn-primary place-button" data-lat="24.4672" data-lng="39.6111">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Current Location -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-location-arrow me-2"></i> <?php echo __('your_location'); ?></h5>
                </div>
                <div class="card-body">
                    <div id="locationStatus" class="alert alert-info"><?php echo __('waiting_for_location'); ?></div>
                    <div id="locationDetails" style="display: none;">
                        <p><strong><?php echo __('coordinates'); ?>:</strong> <span id="coordinates"></span></p>
                        <p><strong><?php echo __('distance_to_kaaba'); ?>:</strong> <span id="distanceToKaaba"></span></p>
                    </div>
                    <button id="locateButton" class="btn btn-primary w-100">
                        <i class="fas fa-location-arrow me-2"></i> <?php echo __('locate_me'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Map -->
<script>
    let map;
    let userMarker;
    const importantPlaces = [
        { name: "<?php echo __('kaaba'); ?>", lat: 21.4225, lng: 39.8262, icon: 'kaaba' },
        { name: "<?php echo __('mount_arafat'); ?>", lat: 21.3553, lng: 39.9841, icon: 'mountain' },
        { name: "<?php echo __('muzdalifah'); ?>", lat: 21.4041, lng: 39.9362, icon: 'mountain' },
        { name: "<?php echo __('mina'); ?>", lat: 21.4133, lng: 39.8933, icon: 'archway' },
        { name: "<?php echo __('masjid_al_nabawi'); ?>", lat: 24.4672, lng: 39.6111, icon: 'mosque' }
    ];
    
    // Initialize map when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        
        // Add event listeners
        document.getElementById('locateButton').addEventListener('click', locateUser);
        document.getElementById('searchButton').addEventListener('click', searchLocation);
        
        // Add event listeners to place buttons
        const placeButtons = document.querySelectorAll('.place-button');
        placeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const lat = parseFloat(this.getAttribute('data-lat'));
                const lng = parseFloat(this.getAttribute('data-lng'));
                
                // Center map on place
                map.setView([lat, lng], 15);
                
                // Add a temporary bounce animation to the marker
                const targetMarker = findMarkerByLatLng(lat, lng);
                if (targetMarker) {
                    targetMarker.bounce();
                }
            });
        });
    });
    
    // Initialize Leaflet map
    function initMap() {
        // Create map centered on Kaaba
        map = L.map('map').setView([21.4225, 39.8262], 15);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Add markers for important places
        importantPlaces.forEach(place => {
            addMarker(place);
        });
        
        // Try to get user's location automatically
        locateUser();
    }
    
    // Add marker for a place
    function addMarker(place) {
        // Create custom icon
        const icon = L.divIcon({
            html: `<i class="fas fa-${place.icon}"></i>`,
            className: 'custom-marker-icon',
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -36]
        });
        
        // Create marker
        const marker = L.marker([place.lat, place.lng], { icon: icon }).addTo(map);
        
        // Add popup
        marker.bindPopup(`<b>${place.name}</b>`);
        
        // Add 'bounce' method to marker
        marker.bounce = function() {
            const markerElement = marker.getElement();
            markerElement.classList.add('marker-bounce');
            setTimeout(() => {
                markerElement.classList.remove('marker-bounce');
            }, 1500);
        };
        
        return marker;
    }
    
    // Find marker by lat/lng
    function findMarkerByLatLng(lat, lng) {
        let targetMarker = null;
        
        map.eachLayer(layer => {
            if (layer instanceof L.Marker) {
                const markerLatLng = layer.getLatLng();
                if (markerLatLng.lat === lat && markerLatLng.lng === lng) {
                    targetMarker = layer;
                }
            }
        });
        
        return targetMarker;
    }
    
    // Locate user
    function locateUser() {
        const locationStatus = document.getElementById('locationStatus');
        const locationDetails = document.getElementById('locationDetails');
        const coordinates = document.getElementById('coordinates');
        const distanceToKaaba = document.getElementById('distanceToKaaba');
        
        locationStatus.classList.remove('alert-success', 'alert-danger');
        locationStatus.classList.add('alert-info');
        locationStatus.textContent = "<?php echo __('getting_location'); ?>";
        locationDetails.style.display = 'none';
        
        // Get current location
        getCurrentLocation(function(result) {
            if (result.success) {
                const lat = result.latitude;
                const lng = result.longitude;
                
                // Update status
                locationStatus.classList.remove('alert-info', 'alert-danger');
                locationStatus.classList.add('alert-success');
                locationStatus.textContent = "<?php echo __('location_found'); ?>";
                
                // Show location details
                coordinates.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                
                // Calculate distance to Kaaba
                const distance = calculateDistance(lat, lng, 21.4225, 39.8262);
                distanceToKaaba.textContent = formatDistance(distance);
                
                // Show details
                locationDetails.style.display = 'block';
                
                // Update or create user marker
                if (userMarker) {
                    userMarker.setLatLng([lat, lng]);
                } else {
                    // Create custom icon
                    const icon = L.divIcon({
                        html: `<i class="fas fa-user-circle"></i>`,
                        className: 'user-marker-icon',
                        iconSize: [36, 36],
                        iconAnchor: [18, 36],
                        popupAnchor: [0, -36]
                    });
                    
                    userMarker = L.marker([lat, lng], { icon: icon }).addTo(map);
                    userMarker.bindPopup(`<b><?php echo __('your_location'); ?></b>`);
                }
                
                // Pan map to user location
                map.setView([lat, lng], 15);
                
                // Open popup
                userMarker.openPopup();
            } else {
                // Show error
                locationStatus.classList.remove('alert-info', 'alert-success');
                locationStatus.classList.add('alert-danger');
                locationStatus.textContent = result.error;
            }
        });
    }
    
    // Search location
    function searchLocation() {
        const searchInput = document.getElementById('searchInput');
        const query = searchInput.value.trim();
        
        if (query === '') {
            return;
        }
        
        // Use Nominatim for geocoding
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const result = data[0];
                    const lat = parseFloat(result.lat);
                    const lng = parseFloat(result.lon);
                    
                    // Center map on result
                    map.setView([lat, lng], 15);
                    
                    // Add marker
                    const marker = L.marker([lat, lng]).addTo(map);
                    marker.bindPopup(`<b>${result.display_name}</b>`).openPopup();
                    
                    // Remove marker after 10 seconds
                    setTimeout(() => {
                        map.removeLayer(marker);
                    }, 10000);
                } else {
                    alert("<?php echo __('location_not_found'); ?>");
                }
            })
            .catch(error => {
                console.error('Error searching location:', error);
                alert("<?php echo __('search_error'); ?>");
            });
    }
</script>

<!-- Extra CSS for map markers -->
<style>
    .custom-marker-icon,
    .user-marker-icon {
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
    
    .custom-marker-icon i {
        color: var(--primary);
        font-size: 18px;
    }
    
    .user-marker-icon {
        background-color: var(--primary);
        border-color: white;
    }
    
    .user-marker-icon i {
        color: white;
        font-size: 18px;
    }
    
    .marker-bounce {
        animation: marker-bounce 0.5s infinite alternate;
    }
    
    @keyframes marker-bounce {
        from { transform: translateY(0); }
        to { transform: translateY(-15px); }
    }
</style>

<?php
// Include footer
include_once('../includes/footer.php');
?>