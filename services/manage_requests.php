<?php
require_once '../includes/auth_check.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check role
if (!in_array($currentUser['user_type'], ['admin', 'provider'])) {
    setFlashMessage('error', __('access_denied'));
    redirect(SITE_URL . '/index.php');
    exit;
}

// Handle form submissions and actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception(__('invalid_csrf_token'));
        }

        $action = $_POST['action'] ?? '';
        beginTransaction();

        if ($action === 'create_provider') {
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $serviceType = $_POST['service_type'] ?? '';
            $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
            $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;

            if (empty($name) || strlen($name) > 100) {
                throw new Exception(__('provider_name_required'));
            }
            if (empty($phone) || !preg_match('/^\+?\d{8,15}$/', $phone)) {
                throw new Exception(__('invalid_phone'));
            }
            if (!in_array($serviceType, ['cart', 'wheelchair'])) {
                throw new Exception(__('invalid_service_type'));
            }

            $sql = "INSERT INTO service_providers (name, phone, service_type, latitude, longitude, available, created_at)
                    VALUES (?, ?, ?, ?, ?, '1', NOW())";
            insertData($sql, [$name, $phone, $serviceType, $latitude, $longitude]);

            logActivity($currentUser['id'], 'create_provider', ['name' => $name]);
            setFlashMessage('success', __('provider_added_success'));
            redirect(SITE_URL . '/index.php');
        } elseif ($action === 'update_provider') {
            $providerId = intval($_POST['provider_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $serviceType = $_POST['service_type'] ?? '';
            $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
            $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;

            if (empty($name) || strlen($name) > 100) {
                throw new Exception(__('provider_name_required'));
            }
            if (empty($phone) || !preg_match('/^\+?\d{8,15}$/', $phone)) {
                throw new Exception(__('invalid_phone'));
            }
            if (!in_array($serviceType, ['cart', 'wheelchair'])) {
                throw new Exception(__('invalid_service_type'));
            }

            $sql = "UPDATE service_providers SET name = ?, phone = ?, service_type = ?, latitude = ?, longitude = ? WHERE id = ?";
            executeQuery($sql, [$name, $phone, $serviceType, $latitude, $longitude, $providerId]);

            logActivity($currentUser['id'], 'update_provider', ['provider_id' => $providerId]);
            setFlashMessage('success', __('provider_updated_success'));
        } elseif ($action === 'assign_provider') {
            $requestId = intval($_POST['request_id'] ?? 0);
            $providerId = intval($_POST['provider_id'] ?? 0);

            $request = fetchRow("SELECT id, status, service_type FROM service_requests WHERE id = ?", [$requestId]);
            $provider = fetchRow("SELECT id, service_type, available FROM service_providers WHERE id = ?", [$providerId]);

            if (!$request || $request['status'] !== 'requested') {
                throw new Exception(__('request_not_found_or_invalid'));
            }
            if (!$provider || $provider['status'] !== '1' || $provider['service_type'] !== $request['service_type']) {
                throw new Exception(__('provider_not_available'));
            }

            $sql = "UPDATE service_requests SET provider_id = ?, status = 'assigned', assigned_at = NOW() WHERE id = ?";
            executeQuery($sql, [$providerId, $requestId]);

            executeQuery("UPDATE service_providers SET available = '0' WHERE id = ?", [$providerId]);

            logActivity($currentUser['id'], 'assign_provider', ['request_id' => $requestId, 'provider_id' => $providerId]);
            setFlashMessage('success', __('provider_assigned_success'));
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

// Handle delete provider
if (isset($_GET['delete_provider'])) {
    try {
        $providerId = intval($_GET['delete_provider']);
        $provider = fetchRow("SELECT id FROM service_providers WHERE id = ?", [$providerId]);
        if (!$provider) {
            throw new Exception(__('provider_not_found'));
        }

        beginTransaction();
        executeQuery("DELETE FROM service_providers WHERE id = ?", [$providerId]);
        logActivity($currentUser['id'], 'delete_provider', ['provider_id' => $providerId]);
        commitTransaction();
        setFlashMessage('success', __('provider_deleted_success'));
        redirect($_SERVER['PHP_SELF']);
        exit;

    } catch (Exception $e) {
        rollbackTransaction();
        error_log("Error in delete_provider: " . $e->getMessage());
        setFlashMessage('error', $e->getMessage());
    }
}

// Handle cancel request
if (isset($_GET['cancel_request'])) {
    try {
        $requestId = intval($_GET['cancel_request']);
        $request = fetchRow("SELECT id, provider_id, status FROM service_requests WHERE id = ?", [$requestId]);
        if (!$request) {
            throw new Exception(__('request_not_found'));
        }
        if (!in_array($request['status'], ['requested', 'assigned'])) {
            throw new Exception(__('cancel_failed_status'));
        }

        beginTransaction();
        executeQuery("DELETE FROM service_requests WHERE id = ?", [$requestId]);
        if ($request['provider_id']) {
            executeQuery("UPDATE service_providers SET available = '0' WHERE id = ?", [$request['provider_id']]);
        }
        logActivity($currentUser['id'], 'cancel_request', ['request_id' => $requestId]);
        commitTransaction();
        setFlashMessage('success', __('request_cancelled'));
        redirect($_SERVER['PHP_SELF']);
        exit;

    } catch (Exception $e) {
        rollbackTransaction();
        error_log("Error in cancel_request: " . $e->getMessage());
        setFlashMessage('error', $e->getMessage());
    }
}

$pageTitle = __('manage_requests');
require_once '../includes/header.php';
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
        <i class="fas fa-tasks me-2" aria-hidden="true"></i>
        <?php echo __('manage_requests'); ?>
    </h1>

    <!-- Provider Form -->
    <?php include '../partials/provider_form.php'; ?>

    <!-- Provider List -->
    <?php include '../partials/provider_list.php'; ?>

    <!-- Request List -->
    <?php include '../partials/request_list.php'; ?>
</div>

<?php require_once '../includes/footer.php'; ?>