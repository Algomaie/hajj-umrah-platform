<?php
// dashboard\pilgrim.php
require_once '../includes/auth_check.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/header.php';

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check user role
if ($currentUser['user_type'] !== 'pilgrim') {
    setFlashMessage('error', __('access_denied'));
    redirect(SITE_URL . '/splash.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception(__('invalid_csrf_token'));
        }

        $action = $_POST['action'] ?? '';
        beginTransaction();

        if ($action === 'join_group') {
            $inviteCode = trim($_POST['invite_code'] ?? '');
            if (empty($inviteCode)) {
                throw new Exception(__('invite_code_required'));
            }

            $group = fetchRow("SELECT id FROM groups WHERE invite_code = ? AND active = 1", [$inviteCode]);
            if (!$group) {
                throw new Exception(__('invalid_invite_code'));
            }

            // Check if already a member
            $isMember = fetchRow("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?", [$group['id'], $currentUser['id']]);
            if ($isMember) {
                throw new Exception(__('already_in_group'));
            }

            $sql = "INSERT INTO group_members (group_id, user_id, joined_at) VALUES (?, ?, NOW())";
            insertData($sql, [$group['id'], $currentUser['id']]);

            logActivity($currentUser['id'], 'join_group', ['group_id' => $group['id']]);
            setFlashMessage('success', __('joined_group_success'));
        } elseif ($action === 'request_service') {
            $serviceType = $_POST['service_type'] ?? '';
            $numPassengers = intval($_POST['num_passengers'] ?? 1);
            $pickupLocation = trim($_POST['pickup_location'] ?? '');
            $destination = trim($_POST['destination'] ?? '');
            $contactPhone = trim($_POST['contact_phone'] ?? '');
            $specialNeeds = trim($_POST['special_needs'] ?? '');
            $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
            $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;

            if (!in_array($serviceType, ['cart', 'wheelchair'])) {
                throw new Exception(__('invalid_service_type'));
            }
            if ($numPassengers < 1 || $numPassengers > 10) {
                throw new Exception(__('invalid_num_passengers'));
            }
            if (empty($pickupLocation) || empty($destination)) {
                throw new Exception(__('location_required'));
            }
            if (empty($contactPhone) || !preg_match('/^\+?\d{8,15}$/', $contactPhone)) {
                throw new Exception(__('invalid_phone'));
            }

            $sql = "INSERT INTO service_requests (
                user_id, service_type, num_passengers, special_needs, 
                pickup_location, destination, contact_phone, latitude, longitude, 
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'requested', NOW())";
            insertData($sql, [
                $currentUser['id'], $serviceType, $numPassengers, $specialNeeds,
                $pickupLocation, $destination, $contactPhone, $latitude, $longitude
            ]);

            logActivity($currentUser['id'], 'request_service', ['service_type' => $serviceType]);
            setFlashMessage('success', __('service_request_submitted'));
        } elseif ($action === 'update_profile') {
            $fullName = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $specialNeeds = trim($_POST['special_needs'] ?? '');

            if (empty($fullName) || strlen($fullName) > 100) {
                throw new Exception(__('full_name_required'));
            }
            if (empty($phone) || !preg_match('/^\+?\d{8,15}$/', $phone)) {
                throw new Exception(__('invalid_phone'));
            }

            $sql = "UPDATE users SET full_name = ?, phone = ?, special_needs = ? WHERE id = ?";
            executeQuery($sql, [$fullName, $phone, $specialNeeds, $currentUser['id']]);

            logActivity($currentUser['id'], 'update_profile', []);
            setFlashMessage('success', __('profile_updated_success'));
        }

        commitTransaction();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Refresh CSRF token
        redirect($_SERVER['PHP_SELF']);
        exit;

    } catch (Exception $e) {
        rollbackTransaction();
        error_log("Error in $action: " . $e->getMessage());
        setFlashMessage('error', $e->getMessage());
    }
}

// Fetch user's group
$group = fetchRow("
    SELECT g.id, g.name, g.invite_code
    FROM groups g
    JOIN group_members gm ON g.id = gm.group_id
    WHERE gm.user_id = ? AND g.active = 1
", [$currentUser['id']]);

// Fetch user's service requests
$requests = fetchAll("
    SELECT sr.*, sp.name as provider_name
    FROM service_requests sr
    LEFT JOIN service_providers sp ON sr.provider_id = sp.id
    WHERE sr.user_id = ?
    ORDER BY sr.created_at DESC
", [$currentUser['id']]);

$pageTitle = __('pilgrim_dashboard');
$useLeaflet = true;
?>

<div class="container py-4">
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type'] ?? 'info'); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('close'); ?>"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <h1 class="h2 mb-4">
        <i class="fas fa-user me-2" aria-hidden="true"></i>
        <?php echo __('pilgrim_dashboard'); ?>
    </h1>

    <!-- Group Membership -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="fas fa-users me-2" aria-hidden="true"></i>
                <?php echo __('group_membership'); ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if ($group): ?>
                <p><strong><?php echo __('group_name'); ?>:</strong> <?php echo htmlspecialchars($group['name']); ?></p>
                <p><strong><?php echo __('invite_code'); ?>:</strong> <?php echo htmlspecialchars($group['invite_code']); ?></p>
            <?php else: ?>
                <p class="text-muted"><?php echo __('not_in_group'); ?></p>
                <form method="POST" id="joinGroupForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="join_group">
                    <div class="mb-3">
                        <label for="inviteCode" class="form-label"><?php echo __('invite_code'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="inviteCode" name="invite_code" required maxlength="10">
                        <div class="invalid-feedback"><?php echo __('invite_code_required'); ?></div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2" aria-hidden="true"></i>
                        <?php echo __('join_group'); ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Service Request Form -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="fas fa-clipboard-list me-2" aria-hidden="true"></i>
                <?php echo __('request_service'); ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" id="serviceRequestForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="request_service">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">

                <div class="mb-3">
                    <label for="serviceType" class="form-label"><?php echo __('service_type'); ?> <span class="text-danger">*</span></label>
                    <select class="form-select" id="serviceType" name="service_type" required>
                        <option value="cart"><?php echo __('cart'); ?></option>
                        <option value="wheelchair"><?php echo __('wheelchair'); ?></option>
                    </select>
                    <div class="invalid-feedback"><?php echo __('service_type_required'); ?></div>
                </div>

                <div class="mb-3">
                    <label for="numPassengers" class="form-label"><?php echo __('num_passengers'); ?> <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="numPassengers" name="num_passengers" value="1" min="1" max="10" required>
                    <div class="invalid-feedback"><?php echo __('invalid_num_passengers'); ?></div>
                </div>

                <div class="mb-3">
                    <label for="pickupLocation" class="form-label"><?php echo __('pickup_location'); ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="pickupLocation" name="pickup_location" required>
                    <div class="invalid-feedback"><?php echo __('location_required'); ?></div>
                </div>

                <div class="mb-3">
                    <label for="destination" class="form-label"><?php echo __('destination'); ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="destination" name="destination" required>
                    <div class="invalid-feedback"><?php echo __('location_required'); ?></div>
                </div>

                <div class="mb-3">
                    <label for="contactPhone" class="form-label"><?php echo __('contact_phone'); ?> <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" id="contactPhone" name="contact_phone" 
                           value="<?php echo htmlspecialchars($currentUser['phone']); ?>" 
                           required pattern="\+?\d{8,15}" maxlength="20">
                    <div class="invalid-feedback"><?php echo __('invalid_phone'); ?></div>
                </div>

                <div class="mb-3">
                    <label for="specialNeeds" class="form-label"><?php echo __('special_needs'); ?></label>
                    <textarea class="form-control" id="specialNeeds" name="special_needs" rows="3"></textarea>
                </div>

                <div id="map" style="height: 300px;" class="mb-3"></div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2" aria-hidden="true"></i>
                    <?php echo __('submit_request'); ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Request History -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="fas fa-history me-2" aria-hidden="true"></i>
                <?php echo __('request_history'); ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($requests)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-clipboard-list text-muted mb-3" style="font-size: 3rem;" aria-hidden="true"></i>
                    <p class="text-muted"><?php echo __('no_requests_found'); ?></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col"><?php echo __('request_id'); ?></th>
                                <th scope="col"><?php echo __('service_type'); ?></th>
                                <th scope="col"><?php echo __('pickup_location'); ?></th>
                                <th scope="col"><?php echo __('destination'); ?></th>
                                <th scope="col"><?php echo __('provider'); ?></th>
                                <th scope="col"><?php echo __('status'); ?></th>
                                <th scope="col"><?php echo __('created_at'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['id']); ?></td>
                                    <td><?php echo __($request['service_type']); ?></td>
                                    <td><?php echo htmlspecialchars($request['pickup_location']); ?></td>
                                    <td><?php echo htmlspecialchars($request['destination']); ?></td>
                                    <td>
                                        <?php echo $request['provider_name'] ? htmlspecialchars($request['provider_name']) : __('unassigned'); ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $request['status'] === 'completed' ? 'success' : 
                                                ($request['status'] === 'assigned' ? 'primary' : 'warning');
                                        ?>">
                                            <?php echo __($request['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($request['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Update -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="fas fa-user-edit me-2" aria-hidden="true"></i>
                <?php echo __('update_profile'); ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" id="profileForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="update_profile">

                <div class="mb-3">
                    <label for="fullName" class="form-label"><?php echo __('full_name'); ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="fullName" name="full_name" 
                           value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" 
                           required maxlength="100">
                    <div class="invalid-feedback"><?php echo __('full_name_required'); ?></div>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label"><?php echo __('phone'); ?> <span class="text-danger">*</span></label>
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($currentUser['phone']); ?>" 
                           required pattern="\+?\d{8,15}" maxlength="20">
                    <div class="invalid-feedback"><?php echo __('invalid_phone'); ?></div>
                </div>

                <div class="mb-3">
                    <label for="profileSpecialNeeds" class="form-label"><?php echo __('special_needs'); ?></label>
                    <textarea class="form-control" id="profileSpecialNeeds" name="special_needs" rows="3"><?php echo htmlspecialchars($currentUser['special_needs'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2" aria-hidden="true"></i>
                    <?php echo __('update_profile'); ?>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Form validation
    ['joinGroupForm', 'serviceRequestForm', 'profileForm'].forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', event => {
                console.log(`${formId} submitted:`, new FormData(form));
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        }
    });

    // Initialize Leaflet map
    const map = L.map('map').setView([21.4225, 39.8262], 13); // Default to Mecca
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    let marker;
    map.on('click', e => {
        if (marker) {
            map.removeLayer(marker);
        }
        marker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(map);
        document.getElementById('latitude').value = e.latlng.lat.toFixed(6);
        document.getElementById('longitude').value = e.latlng.lng.toFixed(6);
    });

    // Get user's location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            const { latitude, longitude } = position.coords;
            map.setView([latitude, longitude], 13);
            marker = L.marker([latitude, longitude]).addTo(map);
            document.getElementById('latitude').value = latitude.toFixed(6);
            document.getElementById('longitude').value = longitude.toFixed(6);
        }, () => {
            console.log('Geolocation access denied');
        });
    }
});
</script>

<style>
.card {
    transition: all 0.2s ease;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}
.alert-dismissible .btn-close {
    padding: 0.75rem;
}
.was-validated .form-control:invalid {
    border-color: var(--bs-danger);
}
</style>

<?php require_once '../includes/footer.php'; ?>