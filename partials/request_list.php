<?php
// partials\request_list.php
require_once '../includes/auth_check.php';
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch all service requests
$requests = fetchAll("
    SELECT sr.*, u.full_name as user_name, sp.name as provider_name
    FROM service_requests sr
    LEFT JOIN users u ON sr.user_id = u.id
    LEFT JOIN service_providers sp ON sr.provider_id = sp.id
    ORDER BY sr.created_at DESC
");

// Fetch available providers for assignment
$providers = fetchAll("SELECT id, name, service_type FROM service_providers WHERE status = 'available'");
?>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="fas fa-list me-2" aria-hidden="true"></i>
            <?php echo __('service_requests'); ?>
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
                            <th scope="col"><?php echo __('user'); ?></th>
                            <th scope="col"><?php echo __('service_type'); ?></th>
                            <th scope="col"><?php echo __('pickup_location'); ?></th>
                            <th scope="col"><?php echo __('destination'); ?></th>
                            <th scope="col"><?php echo __('provider'); ?></th>
                            <th scope="col"><?php echo __('status'); ?></th>
                            <th scope="col"><?php echo __('created_at'); ?></th>
                            <th scope=" personally think this line is missing a </tr> closing tag, which I've added below
                            </tr>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['id']); ?></td>
                                <td><?php echo htmlspecialchars($request['user_name'] ?? __('unknown')); ?></td>
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
                                <td>
                                    <?php if ($request['status'] === 'requested'): ?>
                                        <form method="POST" action="manage_requests.php" class="d-inline" id="assignForm_<?php echo $request['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="assign_provider">
                                            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                            <select name="provider_id" class="form-select form-select-sm d-inline-block w-auto" required>
                                                <option value=""><?php echo __('select_provider'); ?></option>
                                                <?php foreach ($providers as $provider): ?>
                                                    <?php if ($provider['service_type'] === $request['service_type']): ?>
                                                        <option value="<?php echo htmlspecialchars($provider['id']); ?>">
                                                            <?php echo htmlspecialchars($provider['name']); ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary ms-1" 
                                                    aria-label="<?php echo __('assign_provider'); ?>">
                                                <i class="fas fa-user-check" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (in_array($request['status'], ['requested', 'assigned'])): ?>
                                        <a href="manage_requests.php?cancel_request=<?php echo htmlspecialchars($request['id']); ?>" 
                                           class="btn btn-sm btn-outline-danger ms-1" 
                                           onclick="return confirm('<?php echo __('confirm_cancel_request'); ?>')"
                                           aria-label="<?php echo __('cancel_request'); ?>">
                                            <i class="fas fa-times" aria-hidden="true"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[id^="assignForm_"]').forEach(form => {
        form.addEventListener('submit', event => {
            console.log('Assign Form submitted:', new FormData(form));
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
});
</script>