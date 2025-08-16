<?php
require_once '../includes/auth_check.php';

// Set page title and Leaflet flag
$pageTitle = __('medical_service');
$useLeaflet = true;

// Fetch user's medical requests
$medicalRequests = fetchAll("
    SELECT e.*, u.full_name
    FROM emergencies e
    JOIN users u ON e.reporter_id = u.id
    WHERE e.reporter_id = ? AND e.type = 'medical'
    ORDER BY e.created_at DESC
    LIMIT 5
", [$currentUser['id']]);

// Configuration for emergency contacts and medical facilities
$emergencyContacts = [
    ['label' => __('ambulance'), 'number' => '937'],
    ['label' => __('general_emergency'), 'number' => '911'],
];

$medicalFacilities = [
    [
        'name' => __('makkah_medical_center'),
        'address' => __('makkah_center_address'),
        'phone' => '+966125403000',
        'availability' => '24/7',
        'badge_class' => 'bg-success'
    ],
    [
        'name' => __('haram_medical_center'),
        'address' => __('haram_center_address'),
        'phone' => '+966125376666',
        'availability' => '24/7',
        'badge_class' => 'bg-success'
    ],
    [
        'name' => __('jabal_rahma_center'),
        'address' => __('jabal_center_address'),
        'phone' => '+966125423333',
        'availability' => __('hajj_only'),
        'badge_class' => 'bg-warning text-dark'
    ]
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception(__('invalid_csrf_token'));
        }

        // Validate required fields
        $requiredFields = [
            'medical_type' => __('medical_emergency_type_required'),
            'patient_name' => __('patient_name_required'),
            'description' => __('description_required'),
            'urgency' => __('urgency_level_required'),
            'contact_phone' => __('contact_phone_required')
        ];

        foreach ($requiredFields as $field => $error) {
            if (empty($_POST[$field])) {
                throw new Exception($error);
            }
        }

        // Validate urgency and medical type
        $validUrgencies = ['low', 'medium', 'high'];
        $validMedicalTypes = ['illness', 'injury', 'exhaustion', 'other'];
        if (!in_array($_POST['urgency'], $validUrgencies)) {
            throw new Exception(__('invalid_urgency_level'));
        }
        if (!in_array($_POST['medical_type'], $validMedicalTypes)) {
            throw new Exception(__('invalid_medical_type'));
        }

        // Start transaction
        beginTransaction();

        // Handle location
        $includeLocation = isset($_POST['include_location']) && $_POST['include_location'] === '1';
        $latitude = $includeLocation && isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
        $longitude = $includeLocation && isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;

        // Combine description
        $description = sprintf(
            "[%s: %s] [%s: %s] %s",
            __('urgency'), __($_POST['urgency']),
            __('type'), __($_POST['medical_type']),
            sanitizeInput($_POST['description'])
        );

        // Insert emergency record
        $sql = "INSERT INTO emergencies (
                    emergency_id, reporter_id, type, description, latitude, longitude, 
                    contact_phone, status, created_at, updated_at
                ) VALUES (UUID(), ?, ?, ?, ?, ?, ?, 'requested', NOW(), NOW())";

        $emergencyId = insertData($sql, [
            $currentUser['id'],
            'medical',
            $description,
            $latitude,
            $longitude,
            sanitizeInput($_POST['contact_phone'])
        ]);

        if (!$emergencyId) {
            throw new Exception(__('emergency_creation_failed'));
        }

        // Commit transaction
        commitTransaction();

        // Log activity
        logActivity($currentUser['id'], 'report_emergency', [
            'emergency_id' => $emergencyId,
            'type' => 'medical'
        ]);

        // Create notification
        createEmergencyNotification($emergencyId, 'medical');

        // Set success message and redirect
        setFlashMessage('success', __('medical_request_submitted'));
        redirect($_SERVER['PHP_SELF']);
        exit;

    } catch (Exception $e) {
        rollbackTransaction();
        $errorMessage = $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<div class="container py-4">
    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('close'); ?>"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <h1 class="h2 mb-3"><i class="fas fa-medkit me-2" aria-hidden="true"></i> <?php echo __('medical_service'); ?></h1>
            <p class="lead mb-4"><?php echo __('medical_description'); ?></p>

            <!-- Request Form -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-file-medical me-2" aria-hidden="true"></i> <?php echo __('medical_request_form'); ?></h5>
                </div>
                <div class="card-body">
                    <form id="medicalForm" method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="alert alert-warning mb-4" role="alert">
                            <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i> <?php echo __('medical_warning'); ?>
                        </div>

                        <div class="mb-3">
                            <label for="emergencyType" class="form-label"><?php echo __('medical_emergency_type'); ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="emergencyType" name="medical_type" required
                                    aria-describedby="emergencyTypeHelp">
                                <option value=""><?php echo __('select_medical_type'); ?></option>
                                <option value="illness"><?php echo __('illness'); ?></option>
                                <option value="injury"><?php echo __('injury'); ?></option>
                                <option value="exhaustion"><?php echo __('exhaustion'); ?></option>
                                <option value="other"><?php echo __('other'); ?></option>
                            </select>
                            <div class="invalid-feedback"><?php echo __('medical_emergency_type_required'); ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="patientName" class="form-label"><?php echo __('patient_name'); ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="patientName" name="patient_name" required
                                   value="<?php echo htmlspecialchars($currentUser['full_name']); ?>"
                                   aria-describedby="patientNameHelp">
                            <div class="invalid-feedback"><?php echo __('patient_name_required'); ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label"><?php echo __('description'); ?> <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="3" required
                                      aria-describedby="descriptionHelp"></textarea>
                            <div class="invalid-feedback"><?php echo __('description_required'); ?></div>
                            <small id="descriptionHelp" class="form-text"><?php echo __('medical_description_help'); ?></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><?php echo __('urgency_level'); ?> <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group" aria-label="<?php echo __('urgency_level'); ?>">
                                <input type="radio" class="btn-check" name="urgency" id="urgencyLow" value="low" required>
                                <label class="btn btn-outline-success" for="urgencyLow"><?php echo __('low'); ?></label>
                                <input type="radio" class="btn-check" name="urgency" id="urgencyMedium" value="medium" checked>
                                <label class="btn btn-outline-warning" for="urgencyMedium"><?php echo __('medium'); ?></label>
                                <input type="radio" class="btn-check" name="urgency" id="urgencyHigh" value="high">
                                <label class="btn btn-outline-danger" for="urgencyHigh"><?php echo __('high'); ?></label>
                            </div>
                            <div class="invalid-feedback d-block"><?php echo __('urgency_level_required'); ?></div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="includeLocation" name="include_location" value="1" checked
                                       aria-label="<?php echo __('include_my_location'); ?>">
                                <label class="form-check-label" for="includeLocation"><?php echo __('include_my_location'); ?></label>
                            </div>
                        </div>

                        <div id="locationMapContainer" class="mb-4">
                            <div id="medicalMap" class="map-container map-container-small" role="region" aria-label="<?php echo __('location_map'); ?>"></div>
                            <div id="locationStatus" class="alert alert-info mt-2"><?php echo __('getting_location'); ?>...</div>
                            <input type="hidden" id="latitude" name="latitude">
                            <input type="hidden" id="longitude" name="longitude">
                        </div>

                        <div class="mb-3">
                            <label for="contactPhone" class="form-label"><?php echo __('contact_phone'); ?> <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="contactPhone" name="contact_phone" required
                                   value="<?php echo htmlspecialchars($currentUser['phone']); ?>">
                            <div class="invalid-feedback"><?php echo __('contact_phone_required'); ?></div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirmCheck" required
                                       aria-label="<?php echo __('medical_confirm'); ?>">
                                <label class="form-check-label" for="confirmCheck"><?php echo __('medical_confirm'); ?></label>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-paper-plane me-2" aria-hidden="true"></i> <?php echo __('submit_request'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Emergency Contacts -->
            <div class="card mb-4 emergency-card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-phone-alt me-2" aria-hidden="true"></i> <?php echo __('emergency_numbers'); ?></h5>
                </div>
                <div class="card-body">
                    <?php foreach ($emergencyContacts as $contact): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <p class="mb-0 fw-bold"><?php echo $contact['label']; ?></p>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="emergency-number me-3"><?php echo $contact['number']; ?></span>
                                <a href="tel:<?php echo $contact['number']; ?>" class="btn btn-sm btn-danger"
                                   aria-label="<?php echo __('call') . ' ' . $contact['label']; ?>">
                                    <i class="fas fa-phone" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="alert alert-info mt-3 mb-0" role="alert">
                        <i class="fas fa-info-circle me-2" aria-hidden="true"></i> <?php echo __('medical_emergency_note'); ?>
                    </div>
                </div>
            </div>

            <!-- Medical Facilities -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-hospital me-2" aria-hidden="true"></i> <?php echo __('nearby_facilities'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($medicalFacilities as $facility): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo $facility['name']; ?></h6>
                                    <small><span class="badge <?php echo $facility['badge_class']; ?>"><?php echo $facility['availability']; ?></span></small>
                                </div>
                                <p class="mb-1"><?php echo $facility['address']; ?></p>
                                <small>
                                    <a href="tel:<?php echo $facility['phone']; ?>" class="text-primary">
                                        <i class="fas fa-phone me-1" aria-hidden="true"></i> <?php echo $facility['phone']; ?>
                                    </a>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Previous Requests -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2" aria-hidden="true"></i> <?php echo __('my_requests'); ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($medicalRequests)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-clipboard-list text-muted mb-3" style="font-size: 2rem;" aria-hidden="true"></i>
                            <p class="text-muted"><?php echo __('no_medical_requests'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($medicalRequests as $request): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo __('medical_request'); ?> #<?php echo htmlspecialchars($request['emergency_id']); ?></h6>
                                        <small class="text-<?php echo $request['status'] === 'resolved' ? 'success' : ($request['status'] === 'in_progress' ? 'primary' : 'warning'); ?>">
                                            <?php echo __($request['status']); ?>
                                        </small>
                                    </div>
                                    <p class="mb-1 text-truncate"><?php echo htmlspecialchars($request['description']); ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1" aria-hidden="true"></i> <?php echo formatDate($request['created_at']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS and JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/jsab32nVPoN8/5P3M=" crossorigin="anonymous">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qijJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>

<script>
// Map and marker variables
let medicalMap, userMarker;

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    initMedicalMap();
    setupEventListeners();
    setupFormValidation();
});

// Initialize map
function initMedicalMap() {
    medicalMap = L.map('medicalMap', {
        zoomControl: false,
        gestureHandling: true
    }).setView([21.4225, 39.8262], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(medicalMap);

    L.control.zoom({ position: 'bottomright' }).addTo(medicalMap);

    getCurrentLocation()
        .then(position => {
            const { latitude, longitude } = position.coords;
            document.getElementById('latitude').value = latitude;
            document.getElementById('longitude').value = longitude;

            userMarker = L.marker([latitude, longitude], { draggable: true })
                .addTo(medicalMap)
                .bindPopup('<?php echo __('your_location'); ?>');
            medicalMap.setView([latitude, longitude], 15);

            document.getElementById('locationStatus').className = 'alert alert-success mt-2';
            document.getElementById('locationStatus').textContent = '<?php echo __('location_found'); ?>';

            userMarker.on('dragend', () => {
                const { lat, lng } = userMarker.getLatLng();
                document.getElementById('latitude').value = lat;
                document.getElementById('longitude').value = lng;
            });
        })
        .catch(error => {
            document.getElementById('locationStatus').className = 'alert alert-danger mt-2';
            document.getElementById('locationStatus').textContent = getLocationError(error);
        });
}

// Event listeners
function setupEventListeners() {
    document.getElementById('includeLocation').addEventListener('change', function() {
        const mapContainer = document.getElementById('locationMapContainer');
        mapContainer.classList.toggle('d-none', !this.checked);

        if (!this.checked) {
            document.getElementById('latitude').value = '';
            document.getElementById('longitude').value = '';
            return;
        }

        getCurrentLocation()
            .then(position => {
                const { latitude, longitude } = position.coords;
                document.getElementById('latitude').value = latitude;
                document.getElementById('longitude').value = longitude;

                if (userMarker) {
                    userMarker.setLatLng([latitude, longitude]);
                } else {
                    userMarker = L.marker([latitude, longitude], { draggable: true })
                        .addTo(medicalMap)
                        .bindPopup('<?php echo __('your_location'); ?>');
                    userMarker.on('dragend', () => {
                        const { lat, lng } = userMarker.getLatLng();
                        document.getElementById('latitude').value = lat;
                        document.getElementById('longitude').value = lng;
                    });
                }

                medicalMap.setView([latitude, longitude], 15);
                document.getElementById('locationStatus').className = 'alert alert-success mt-2';
                document.getElementById('locationStatus').textContent = '<?php echo __('location_found'); ?>';
            })
            .catch(error => {
                document.getElementById('locationStatus').className = 'alert alert-danger mt-2';
                document.getElementById('locationStatus').textContent = getLocationError(error);
            });
    });
}

// Form validation
function setupFormValidation() {
    const form = document.getElementById('medicalForm');
    form.addEventListener('submit', (event) => {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');

        // Custom validation for urgency radio buttons
        const urgencyInputs = form.querySelectorAll('input[name="urgency"]');
        let urgencySelected = false;
        urgencyInputs.forEach(input => {
            if (input.checked) urgencySelected = true;
        });
        const urgencyGroup = form.querySelector('.btn-group');
        if (!urgencySelected) {
            urgencyGroup.classList.add('is-invalid');
        } else {
            urgencyGroup.classList.remove('is-invalid');
        }
    });

    // Remove invalid class when urgency is selected
    form.querySelectorAll('input[name="urgency"]').forEach(input => {
        input.addEventListener('change', () => {
            form.querySelector('.btn-group').classList.remove('is-invalid');
        });
    });
}

// Get current location
function getCurrentLocation() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('<?php echo __('geolocation_unsupported'); ?>'));
            return;
        }
        navigator.geolocation.getCurrentPosition(
            position => resolve(position),
            error => reject(error),
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    });
}

// Location error handling
function getLocationError(error) {
    switch (error.code) {
        case error.PERMISSION_DENIED: return '<?php echo __('location_permission_denied'); ?>';
        case error.POSITION_UNAVAILABLE: return '<?php echo __('location_unavailable'); ?>';
        case error.TIMEOUT: return '<?php echo __('location_timeout'); ?>';
        default: return '<?php echo __('location_error'); ?>';
    }
}
</script>

<style>
.map-container-small {
    height: 300px;
    border-radius: 0.25rem;
    overflow: hidden;
}
.emergency-card .emergency-number {
    font-size: 1.25rem;
    font-weight: bold;
}
.list-group-item:hover {
    background-color: var(--bs-light);
}
.alert-dismissible .btn-close {
    padding: 0.75rem;
}
.was-validated .form-control:invalid,
.was-validated .form-select:invalid {
    border-color: var(--bs-danger);
}
.btn-group.is-invalid .btn {
    border-color: var(--bs-danger);
}
</style>

<?php require_once '../includes/footer.php'; ?>