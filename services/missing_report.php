<?php
require_once('../includes/auth_check.php');

// Set page title
$pageTitle = __('report_missing_person');

// Use Leaflet for maps
$useLeaflet = true;

// Require authentication
// Include header
include_once('../includes/header.php');

// Get user's previous reports
$missingReports = fetchAll("
    SELECT e.*, mp.name, mp.age, mp.gender, mp.photo
    FROM emergencies e
    JOIN missing_persons mp ON e.emergency_id = mp.emergency_id
    WHERE e.reporter_id = ? AND e.type = 'missing_person'
    ORDER BY e.created_at DESC
    LIMIT 5
", [$currentUser['id']]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields
        $requiredFields = [
            'name' => __('person_name_required'),
            'description' => __('description_required'),
            'last_seen_location' => __('last_seen_location_required'),
            'last_seen_time' => __('last_seen_time_required'),
            'contact_phone' => __('contact_phone_required')
        ];
        
        foreach ($requiredFields as $field => $error) {
            if (empty($_POST[$field])) {
                throw new Exception($error);
            }
        }
        
        // Start transaction
        beginTransaction();
        
        // Insert emergency record
        $includeLocation = isset($_POST['include_location']) && $_POST['include_location'] === 'on';
        $latitude = $includeLocation && isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
        $longitude = $includeLocation && isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;
        
        $sql = "INSERT INTO emergencies (
                    reporter_id, type, description, latitude, longitude, 
                    contact_phone, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'requested', NOW(), NOW())";
        
        $emergencyId = insertData($sql, [
            $currentUser['id'],
            'missing_person',
            sanitizeInput($_POST['description']),
            $latitude,
            $longitude,
            sanitizeInput($_POST['contact_phone'])
        ]);
        
        if (!$emergencyId) {
            throw new Exception(__('emergency_creation_failed'));
        }
        
        $uploadResult = uploadFile($_FILES['photo'], 
        ['image/jpeg', 'image/png', 'image/gif'],
        5 * 1024 * 1024, // 5MB
        'missing_persons'
    );
    
    if ($uploadResult['success']) {
        // سيحتوي file_path على مسار نسبي مثل:
        // /hajj-umrah-platform/uploads/missing_persons/680159940a51f_1744918932.jpg
        $photoPath = $uploadResult['file_path'];
        
        // حفظ المسار النسبي فقط في قاعدة البيانات
        $sql = "UPDATE missing_persons SET photo = ? WHERE id = ?";
        executeQuery($sql, [$photoPath, $missing_id]);
    } else {
        throw new Exception($uploadResult['message']);
    }
        
        // Insert missing person record
        $sql = "INSERT INTO missing_persons (
                    emergency_id, name, age, gender, photo, 
                    last_seen_location, last_seen_time
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $result = insertData($sql, [
            $emergencyId,
            sanitizeInput($_POST['name']),
            !empty($_POST['age']) ? (int)$_POST['age'] : null,
            !empty($_POST['gender']) ? sanitizeInput($_POST['gender']) : null,
            $photoPath,
            sanitizeInput($_POST['last_seen_location']),
            sanitizeInput($_POST['last_seen_time'])
        ]);
        
        if (!$result) {
            throw new Exception(__('missing_person_creation_failed'));
        }
        
        // Commit transaction
        commitTransaction();
        
        // Log activity
        logActivity($currentUser['id'], 'report_emergency', [
            'emergency_id' => $emergencyId,
            'type' => 'missing_person'
        ]);
        
        // Create notification for authorities
        createEmergencyNotification($emergencyId, 'missing_person');
        
        // Set success message and redirect to avoid form resubmission
        setFlashMessage('success', __('report_submitted_successfully'));
        redirect($_SERVER['PHP_SELF']);
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        rollbackTransaction();
        $errorMessage = $e->getMessage();
    }
}
?>

<div class="container py-4">
    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['flash_message']; ?></div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <h1><i class="fas fa-user-slash me-2"></i> <?php echo __('report_missing_person'); ?></h1>
            <p class="lead"><?php echo __('missing_person_description'); ?></p>
            
            <!-- Report Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i> <?php echo __('report_form'); ?></h5>
                </div>
                <div class="card-body">
                    <form id="missingPersonForm" enctype="multipart/form-data" method="POST">
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo __('missing_person_warning'); ?>
                        </div>
                        
                        <h5 class="mb-3"><?php echo __('person_details'); ?></h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="personName" class="form-label"><?php echo __('person_name'); ?> *</label>
                                <input type="text" class="form-control" id="personName" name="name" required
                                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                            </div>
                            
                            <div class="col-md-3 mb-3 mb-md-0">
                                <label for="personAge" class="form-label"><?php echo __('age'); ?></label>
                                <input type="number" class="form-control" id="personAge" name="age" min="1" max="120"
                                    value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label for="personGender" class="form-label"><?php echo __('gender'); ?></label>
                                <select class="form-select" id="personGender" name="gender">
                                    <option value=""><?php echo __('select_gender'); ?></option>
                                    <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'male') ? 'selected' : ''; ?>>
                                        <?php echo __('male'); ?>
                                    </option>
                                    <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'female') ? 'selected' : ''; ?>>
                                        <?php echo __('female'); ?>
                                    </option>
                                    <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'other') ? 'selected' : ''; ?>>
                                        <?php echo __('other'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="personDescription" class="form-label"><?php echo __('description'); ?> *</label>
                            <textarea class="form-control" id="personDescription" name="description" rows="3" required><?php 
                                echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; 
                            ?></textarea>
                            <small class="form-text"><?php echo __('description_help'); ?></small>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="lastSeenLocation" class="form-label"><?php echo __('last_seen_location'); ?> *</label>
                                <input type="text" class="form-control" id="lastSeenLocation" name="last_seen_location" required
                                    value="<?php echo isset($_POST['last_seen_location']) ? htmlspecialchars($_POST['last_seen_location']) : ''; ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="lastSeenTime" class="form-label"><?php echo __('last_seen_time'); ?> *</label>
                                <input type="datetime-local" class="form-control" id="lastSeenTime" name="last_seen_time" required
                                    value="<?php echo isset($_POST['last_seen_time']) ? htmlspecialchars($_POST['last_seen_time']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="personPhoto" class="form-label"><?php echo __('photo'); ?></label>
                            <input type="file" class="form-control" id="personPhoto" name="photo" accept="image/*">
                            <small class="form-text"><?php echo __('photo_help'); ?></small>
                        </div>
                        
                        <h5 class="mb-3"><?php echo __('report_location'); ?></h5>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeLocation" name="include_location" checked>
                                <label class="form-check-label" for="includeLocation">
                                    <?php echo __('include_my_location'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <div id="locationMapContainer" class="mb-4">
                            <div id="reportMap" class="map-container map-container-small"></div>
                            <div id="locationStatus" class="alert alert-info mt-2"><?php echo __('getting_location'); ?></div>
                            
                            <input type="hidden" id="latitude" name="latitude">
                            <input type="hidden" id="longitude" name="longitude">
                        </div>
                        
                        <div class="mb-3">
                            <label for="contactPhone" class="form-label"><?php echo __('contact_phone'); ?> *</label>
                            <input type="tel" class="form-control" id="contactPhone" name="contact_phone" 
                                value="<?php echo isset($_POST['contact_phone']) ? htmlspecialchars($_POST['contact_phone']) : htmlspecialchars($currentUser['phone']); ?>" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-paper-plane me-2"></i> <?php echo __('submit_report'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Emergency Contacts -->
            <div class="card mb-4 emergency-card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-phone-alt me-2"></i> <?php echo __('emergency_contacts'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <p class="mb-0 fw-bold"><?php echo __('police'); ?></p>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="emergency-number me-3">999</span>
                            <a href="tel:999" class="btn btn-sm btn-danger">
                                <i class="fas fa-phone"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <p class="mb-0 fw-bold"><?php echo __('ambulance'); ?></p>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="emergency-number me-3">937</span>
                            <a href="tel:937" class="btn btn-sm btn-danger">
                                <i class="fas fa-phone"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 fw-bold"><?php echo __('general_emergency'); ?></p>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="emergency-number me-3">911</span>
                            <a href="tel:911" class="btn btn-sm btn-danger">
                                <i class="fas fa-phone"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- My Previous Reports -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> <?php echo __('my_reports'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($missingReports)): ?>
                    <div class="text-center py-3">
                        <i class="fas fa-clipboard-list text-muted mb-3" style="font-size: 2rem;"></i>
                        <p><?php echo __('no_reports'); ?></p>
                    </div>
                    <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($missingReports as $report): ?>
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($report['name']); ?></h6>
                                <small class="text-<?php echo $report['status'] === 'resolved' ? 'success' : ($report['status'] === 'in_progress' ? 'primary' : 'warning'); ?>">
                                    <?php echo __($report['status']); ?>
                                </small>
                            </div>
                            <p class="mb-1 text-truncate"><?php echo htmlspecialchars($report['description']); ?></p>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i> <?php echo formatDate($report['created_at']); ?>
                            </small>
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
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('missing_tip_1'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('missing_tip_2'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('missing_tip_3'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('missing_tip_4'); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Missing Person Report -->
<script>
    let reportMap;
    let userMarker;
    
    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        initReportMap();
        setupEventListeners();
        
        // Set default last seen time to now
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById('lastSeenTime').value = now.toISOString().slice(0, 16);
    });
    
    // Initialize Report Map
    function initReportMap() {
        // Create map centered on Kaaba
        reportMap = L.map('reportMap').setView([21.4225, 39.8262], 15);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(reportMap);
        
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
                }).addTo(reportMap);
                
                // Center map on user
                reportMap.setView([lat, lng], 15);
                
                // Update status
                document.getElementById('locationStatus').className = 'alert alert-success mt-2';
                document.getElementById('locationStatus').textContent = "<?php echo __('location_found'); ?>";
                
                // Update coordinates when marker is dragged
                userMarker.on('dragend', function() {
                    const position = userMarker.getLatLng();
                    document.getElementById('latitude').value = position.lat;
                    document.getElementById('longitude').value = position.lng;
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
                            }).addTo(reportMap);
                            
                            // Update coordinates when marker is dragged
                            userMarker.on('dragend', function() {
                                const position = userMarker.getLatLng();
                                document.getElementById('latitude').value = position.lat;
                                document.getElementById('longitude').value = position.lng;
                            });
                        }
                        
                        // Center map on user
                        reportMap.setView([lat, lng], 15);
                        
                        // Update status
                        document.getElementById('locationStatus').className = 'alert alert-success mt-2';
                        document.getElementById('locationStatus').textContent = "<?php echo __('location_found'); ?>";
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
    }
</script>

<?php
// Include footer
include_once('../includes/footer.php');
?>