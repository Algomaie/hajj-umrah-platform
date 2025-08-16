<?php
// Use Leaflet for maps
$useLeaflet = true;
// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $serviceType = isset($_POST['service_type']) ? sanitizeInput($_POST['service_type']) : '';
    $numPassengers = isset($_POST['num_passengers']) ? intval($_POST['num_passengers']) : 1;
    $specialNeeds = isset($_POST['special_needs']) ? sanitizeInput($_POST['special_needs']) : '';
    $pickupLocation = isset($_POST['pickup_location']) ? sanitizeInput($_POST['pickup_location']) : '';
    $destination = isset($_POST['destination']) ? sanitizeInput($_POST['destination']) : '';
    $contactPhone = isset($_POST['contact_phone']) ? sanitizeInput($_POST['contact_phone']) : '';
    $includeLocation = isset($_POST['include_location']) ? 1 : 0;
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    
    // Validate inputs
    $errors = [];
    
    if (!in_array($serviceType, ['cart', 'wheelchair'])) {
        $errors[] = __('invalid_service_type');
    }
    
    if ($numPassengers < 1 || $numPassengers > 4) {
        $errors[] = __('invalid_passenger_count');
    }
    
    if (empty($pickupLocation)) {
        $errors[] = __('pickup_location_required');
    }
    
    if (empty($destination)) {
        $errors[] = __('destination_required');
    }
    
    if (empty($contactPhone)) {
        $errors[] = __('contact_phone_required');
    }
    
    if ($includeLocation && (empty($latitude) || empty($longitude))) {
        $errors[] = __('location_required');
    }
    
    if (empty($errors)) {
        // Insert service request
        $sql = "INSERT INTO service_requests (
            user_id, 
            service_type, 
            num_passengers, 
            special_needs, 
            pickup_location, 
            destination, 
            contact_phone, 
            latitude, 
            longitude, 
            status, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'requested', NOW())";
        
        $params = [
            $currentUser['id'],
            $serviceType,
            $numPassengers,
            $specialNeeds,
            $pickupLocation,
            $destination,
            $contactPhone,
            $includeLocation ? $latitude : null,
            $includeLocation ? $longitude : null
        ];
        
        $requestId = insertData($sql, $params);
        
        if ($requestId) {
            // Find available service providers
            $providerSql = "SELECT id FROM service_providers 
                          WHERE service_type = ? AND status = 'available'
                          ORDER BY 
                            (ABS(latitude - ?) + ABS(longitude - ?)) ASC
                          LIMIT 1";
            
            $provider = fetchRow($providerSql, [$serviceType, $latitude, $longitude]);
            
            if ($provider) {
                // Assign provider to request
                $updateSql = "UPDATE service_requests 
                             SET provider_id = ?, status = 'assigned', assigned_at = NOW() 
                             WHERE id = ?";
                executeQuery($updateSql, [$provider['id'], $requestId]);
                
                // Update provider status
                executeQuery("UPDATE service_providers SET status = 'busy' WHERE id = ?", [$provider['id']]);
            }
            
            setFlashMessage('success', __('service_request_submitted'));
            redirect($_SERVER['PHP_SELF']);
        } else {
            setFlashMessage('danger', __('request_submission_failed'));
        }
    } else {
        setFlashMessage('danger', implode('<br>', $errors));
    }
}

// Process cancel request
if (isset($_GET['cancel_request'])) {
    $requestId = intval($_GET['cancel_request']);
    
    // Verify request belongs to user
    $request = fetchRow("SELECT id, provider_id, status FROM service_requests WHERE id = ? AND user_id = ?", [$requestId, $currentUser['id']]);
    
    if ($request && $request['status'] === 'requested') {
        // Delete the request
        executeQuery("DELETE FROM service_requests WHERE id = ?", [$requestId]);
        
        if ($request['provider_id']) {
            // Mark provider as available again
            executeQuery("UPDATE service_providers SET status = 'available' WHERE id = ?", [$request['provider_id']]);
        }
        
        setFlashMessage('success', __('request_cancelled'));
        redirect($_SERVER['PHP_SELF']);
    } else {
        setFlashMessage('danger', __('cancel_failed'));
    }
}

// Get user's service requests
$serviceRequests = fetchAll("
    SELECT sr.*, sp.name as provider_name, sp.phone as provider_phone
    FROM service_requests sr
    LEFT JOIN service_providers sp ON sr.provider_id = sp.id
    WHERE sr.user_id = ? AND sr.service_type IN ('cart', 'wheelchair')
    ORDER BY sr.created_at DESC
    LIMIT 5
", [$currentUser['id']]);

// Include header
include_once('../includes/header.php');

// Set page title
$pageTitle = __('cart_service');
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <h1><i class="fas fa-wheelchair me-2"></i> <?php echo __('cart_service'); ?></h1>
            <p class="lead"><?php echo __('cart_service_description'); ?></p>
            
            <?php if (hasFlashMessage('success')): ?>
                <div class="alert alert-success">
                    <?php echo getFlashMessage('success'); ?>
                </div>
            <?php endif; ?>
            
            <?php if (hasFlashMessage('danger')): ?>
                <div class="alert alert-danger">
                    <?php echo getFlashMessage('danger'); ?>
                </div>
            <?php endif; ?>
            
            <!-- Request Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i> <?php echo __('service_request_form'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="cartServiceForm">
                        <div class="mb-3">
                            <label for="serviceType" class="form-label"><?php echo __('service_type'); ?> *</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="service_type" id="typeCart" value="cart" checked>
                                <label class="btn btn-outline-primary" for="typeCart">
                                    <i class="fas fa-shopping-cart me-2"></i> <?php echo __('cart'); ?>
                                </label>
                                
                                <input type="radio" class="btn-check" name="service_type" id="typeWheelchair" value="wheelchair">
                                <label class="btn btn-outline-primary" for="typeWheelchair">
                                    <i class="fas fa-wheelchair me-2"></i> <?php echo __('wheelchair'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="numPassengers" class="form-label"><?php echo __('num_passengers'); ?> *</label>
                            <select class="form-select" id="numPassengers" name="num_passengers" required>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="specialNeeds" class="form-label"><?php echo __('special_needs'); ?></label>
                            <textarea class="form-control" id="specialNeeds" name="special_needs" rows="2"></textarea>
                            <small class="form-text"><?php echo __('special_needs_help'); ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="pickupLocation" class="form-label"><?php echo __('pickup_location'); ?> *</label>
                            <input type="text" class="form-control" id="pickupLocation" name="pickup_location" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="destination" class="form-label"><?php echo __('destination'); ?> *</label>
                            <input type="text" class="form-control" id="destination" name="destination" required>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeLocation" name="include_location" checked>
                                <label class="form-check-label" for="includeLocation">
                                    <?php echo __('include_my_location'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <div id="locationMapContainer" class="mb-4">
                            <div id="serviceMap" class="map-container map-container-small"></div>
                            <div id="locationStatus" class="alert alert-info mt-2"><?php echo __('getting_location'); ?></div>
                            
                            <input type="hidden" id="latitude" name="latitude">
                            <input type="hidden" id="longitude" name="longitude">
                        </div>
                        
                        <div class="mb-3">
                            <label for="contactPhone" class="form-label"><?php echo __('contact_phone'); ?> *</label>
                            <input type="tel" class="form-control" id="contactPhone" name="contact_phone" value="<?php echo htmlspecialchars($currentUser['phone']); ?>" required>
                        </div>
                        
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i> <?php echo __('service_info'); ?>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> <?php echo __('request_service'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Service Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> <?php echo __('service_information'); ?></h5>
                </div>
                <div class="card-body">
                    <h6><?php echo __('cart_service'); ?></h6>
                    <p><?php echo __('cart_details'); ?></p>
                    <ul>
                        <li><?php echo __('cart_detail_1'); ?></li>
                        <li><?php echo __('cart_detail_2'); ?></li>
                        <li><?php echo __('cart_detail_3'); ?></li>
                    </ul>
                    
                    <hr>
                    
                    <h6><?php echo __('wheelchair_service'); ?></h6>
                    <p><?php echo __('wheelchair_details'); ?></p>
                    <ul>
                        <li><?php echo __('wheelchair_detail_1'); ?></li>
                        <li><?php echo __('wheelchair_detail_2'); ?></li>
                        <li><?php echo __('wheelchair_detail_3'); ?></li>
                    </ul>
                </div>
            </div>
            
            <!-- My Previous Requests -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> <?php echo __('my_requests'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($serviceRequests)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-clipboard-list text-muted mb-3" style="font-size: 2rem;"></i>
                        <p><?php echo __('no_service_requests'); ?></p>
                    </div>
                    <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($serviceRequests as $request): ?>
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">
                                    <i class="<?php echo $request['service_type'] === 'wheelchair' ? 'fas fa-wheelchair' : 'fas fa-shopping-cart'; ?> me-1"></i>
                                    <?php echo __($request['service_type']); ?>
                                </h6>
                                <small class="badge <?php 
                                    echo $request['status'] === 'completed' ? 'bg-success' : 
                                        ($request['status'] === 'assigned' ? 'bg-primary' : 'bg-secondary'); 
                                ?>">
                                    <?php echo __($request['status']); ?>
                                </small>
                            </div>
                            
                            <?php if ($request['provider_name']): ?>
                            <p class="mb-1">
                                <small><?php echo __('provider'); ?>: <?php echo htmlspecialchars($request['provider_name']); ?></small>
                                <?php if ($request['provider_phone']): ?>
                                <a href="tel:<?php echo $request['provider_phone']; ?>" class="ms-2">
                                    <i class="fas fa-phone-alt"></i>
                                </a>
                                <?php endif; ?>
                            </p>
                            <?php endif; ?>
                            
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i> <?php echo date('M j, Y g:i a', strtotime($request['created_at'])); ?>
                            </small>
                            
                            <?php if ($request['status'] === 'requested'): ?>
                            <div class="mt-2">
                                <a href="?cancel_request=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('<?php echo __('confirm_cancel_request'); ?>')">
                                    <?php echo __('cancel'); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tips -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i> <?php echo __('tips'); ?></h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('service_tip_1'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('service_tip_2'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('service_tip_3'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('service_tip_4'); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Cart Service -->
<script>
    let serviceMap;
    let userMarker;
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        initServiceMap();
        setupEventListeners();
    });
    
    // Initialize Service Map
    function initServiceMap() {
        // Create map centered on Kaaba
        serviceMap = L.map('serviceMap').setView([21.4225, 39.8262], 15);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(serviceMap);
        
        // Get user's current location
        getCurrentLocation(function(result) {
            if (result.success) {
                const lat = result.latitude;
                const lng = result.longitude;
                
                // Update hidden inputs
                document.getElementById('latitude').value = lat;
                document.getElementById('longitude').value = lng;
                
                // Add marker
                userMarker = L.marker([lat, lng], {
                    draggable: true // Allow marker to be dragged
                }).addTo(serviceMap);
                
                // Center map on user
                serviceMap.setView([lat, lng], 15);
                
                // Update status
                document.getElementById('locationStatus').className = 'alert alert-success mt-2';
                document.getElementById('locationStatus').textContent = "<?php echo __('location_found'); ?>";
                
                // Update coordinates when marker is dragged
                userMarker.on('dragend', function() {
                    const position = userMarker.getLatLng();
                    document.getElementById('latitude').value = position.lat;
                    document.getElementById('longitude').value = position.lng;
                });
                
                // Try to get address
                getAddressFromCoordinates(lat, lng, function(address) {
                    if (address) {
                        document.getElementById('pickupLocation').value = address;
                    }
                });
            } else {
                // Show error
                document.getElementById('locationStatus').className = 'alert alert-danger mt-2';
                document.getElementById('locationStatus').textContent = result.error;
            }
        });
    }
    
    // Setup event listeners
    function setupEventListeners() {
        // Include location checkbox
        document.getElementById('includeLocation').addEventListener('change', function() {
            const mapContainer = document.getElementById('locationMapContainer');
            
            if (this.checked) {
                mapContainer.style.display = 'block';
                
                // Re-get location
                getCurrentLocation(function(result) {
                    if (result.success) {
                        const lat = result.latitude;
                        const lng = result.longitude;
                        
                        // Update hidden inputs
                        document.getElementById('latitude').value = lat;
                        document.getElementById('longitude').value = lng;
                        
                        // Add or update marker
                        if (userMarker) {
                            userMarker.setLatLng([lat, lng]);
                        } else {
                            userMarker = L.marker([lat, lng], {
                                draggable: true
                            }).addTo(serviceMap);
                            
                            // Update coordinates when marker is dragged
                            userMarker.on('dragend', function() {
                                const position = userMarker.getLatLng();
                                document.getElementById('latitude').value = position.lat;
                                document.getElementById('longitude').value = position.lng;
                            });
                        }
                        
                        // Center map on user
                        serviceMap.setView([lat, lng], 15);
                        
                        // Update status
                        document.getElementById('locationStatus').className = 'alert alert-success mt-2';
                        document.getElementById('locationStatus').textContent = "<?php echo __('location_found'); ?>";
                        
                        // Try to get address
                        getAddressFromCoordinates(lat, lng, function(address) {
                            if (address) {
                                document.getElementById('pickupLocation').value = address;
                            }
                        });
                    } else {
                        // Show error
                        document.getElementById('locationStatus').className = 'alert alert-danger mt-2';
                        document.getElementById('locationStatus').textContent = result.error;
                    }
                });
            } else {
                mapContainer.style.display = 'none';
                document.getElementById('latitude').value = '';
                document.getElementById('longitude').value = '';
            }
        });
        
        // Service type radio buttons
        document.querySelectorAll('input[name="service_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Update form based on service type
                if (this.value === 'wheelchair') {
                    document.getElementById('numPassengers').value = '1';
                    document.getElementById('numPassengers').disabled = true;
                } else {
                    document.getElementById('numPassengers').disabled = false;
                }
            });
        });
    }
    
    // Get address from coordinates using Nominatim
    function getAddressFromCoordinates(lat, lng, callback) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
            .then(response => response.json())
            .then(data => {
                if (data && data.display_name) {
                    callback(data.display_name);
                } else {
                    callback(null);
                }
            })
            .catch(error => {
                console.error('Error getting address:', error);
                callback(null);
            });
    }
    
    // Helper function to get current location
    function getCurrentLocation(callback) {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    callback({
                        success: true,
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    });
                },
                function(error) {
                    let errorMessage = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = "<?php echo __('permission_denied'); ?>";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = "<?php echo __('position_unavailable'); ?>";
                            break;
                        case error.TIMEOUT:
                            errorMessage = "<?php echo __('timeout'); ?>";
                            break;
                        default:
                            errorMessage = "<?php echo __('unknown_error'); ?>";
                            break;
                    }
                    callback({
                        success: false,
                        error: errorMessage
                    });
                }, 
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            callback({
                success: false,
                error: "<?php echo __('geolocation_not_supported'); ?>"
            });
        }
    }
</script>

<style>
.map-container {
    width: 100%;
    height: 300px;
    border-radius: 0.25rem;
}

.map-container-small {
    height: 250px;
}
</style>

<?php
// Include footer
include_once('../includes/footer.php');
?>