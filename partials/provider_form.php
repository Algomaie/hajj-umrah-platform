<?php
// partials\provider_form.php
require_once '../includes/auth_check.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Determine if editing an existing provider
$provider = null;
$action = 'create';
if (isset($_GET['edit_provider'])) {
    $providerId = intval($_GET['edit_provider']);
    $provider = fetchRow("SELECT * FROM service_providers WHERE id = ?", [$providerId]);
    if ($provider) {
        $action = 'update';
    } else {
        setFlashMessage('error', __('provider_not_found'));
        redirect('manage_requests.php');
        exit;
    }
}
?>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="fas fa-user-plus me-2" aria-hidden="true"></i>
            <?php echo $action === 'create' ? __('add_provider') : __('edit_provider'); ?>
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="manage_requests.php" id="providerForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="<?php echo $action; ?>_provider">
            <?php if ($action === 'update'): ?>
                <input type="hidden" name="provider_id" value="<?php echo htmlspecialchars($provider['id']); ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="providerName" class="form-label"><?php echo __('provider_name'); ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="providerName" name="name" 
                       value="<?php echo $provider ? htmlspecialchars($provider['name']) : ''; ?>" 
                       required maxlength="100">
                <div class="invalid-feedback"><?php echo __('provider_name_required'); ?></div>
            </div>

            <div class="mb-3">
                <label for="providerPhone" class="form-label"><?php echo __('provider_phone'); ?> <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" id="providerPhone" name="phone" 
                       value="<?php echo $provider ? htmlspecialchars($provider['phone']) : ''; ?>" 
                       required pattern="\+?\d{8,15}" maxlength="20">
                <div class="invalid-feedback"><?php echo __('invalid_phone'); ?></div>
            </div>

            <div class="mb-3">
                <label for="serviceType" class="form-label"><?php echo __('service_type'); ?> <span class="text-danger">*</span></label>
                <select class="form-select" id="serviceType" name="service_type" required>
                    <option value="cart" <?php echo $provider && $provider['service_type'] === 'cart' ? 'selected' : ''; ?>>
                        <?php echo __('cart'); ?>
                    </option>
                    <option value="wheelchair" <?php echo $provider && $provider['service_type'] === 'wheelchair' ? 'selected' : ''; ?>>
                        <?php echo __('wheelchair'); ?>
                    </option>
                </select>
                <div class="invalid-feedback"><?php echo __('service_type_required'); ?></div>
            </div>

            <div class="mb-3">
                <label for="latitude" class="form-label"><?php echo __('latitude'); ?></label>
                <input type="number" step="any" class="form-control" id="latitude" name="latitude" 
                       value="<?php echo $provider && $provider['latitude'] !== null ? htmlspecialchars($provider['latitude']) : ''; ?>">
            </div>

            <div class="mb-3">
                <label for="longitude" class="form-label"><?php echo __('longitude'); ?></label>
                <input type="number" step="any" class="form-control" id="longitude" name="longitude" 
                       value="<?php echo $provider && $provider['longitude'] !== null ? htmlspecialchars($provider['longitude']) : ''; ?>">
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2" aria-hidden="true"></i>
                    <?php echo $action === 'create' ? __('add_provider') : __('update_provider'); ?>
                </button>
                <a href="manage_requests.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2" aria-hidden="true"></i>
                    <?php echo __('cancel'); ?>
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('providerForm');
    form.addEventListener('submit', event => {
        console.log('Provider Form submitted:', new FormData(form));
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});
</script>