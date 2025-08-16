<?php
// partials\provider_list.php
require_once '../includes/auth_check.php';
// Fetch all providers
$providers = fetchAll("
    SELECT id, name, phone, service_type, status, latitude, longitude
    FROM service_providers
    ORDER BY name ASC
");
?>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="fas fa-users me-2" aria-hidden="true"></i>
            <?php echo __('service_providers'); ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($providers)): ?>
            <div class="text-center py-4">
                <i class="fas fa-users text-muted mb-3" style="font-size: 3rem;" aria-hidden="true"></i>
                <p class="text-muted"><?php echo __('no_providers_found'); ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th scope="col"><?php echo __('name'); ?></th>
                            <th scope="col"><?php echo __('phone'); ?></th>
                            <th scope="col"><?php echo __('service_type'); ?></th>
                            <th scope="col"><?php echo __('status'); ?></th>
                            <th scope="col"><?php echo __('location'); ?></th>
                            <th scope="col"><?php echo __('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($providers as $provider): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($provider['name']); ?></td>
                                <td>
                                    <?php if ($provider['phone']): ?>
                                        <a href="tel:<?php echo htmlspecialchars($provider['phone']); ?>" 
                                           aria-label="<?php echo __('call_provider'); ?>">
                                            <?php echo htmlspecialchars($provider['phone']); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo __('not_provided'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo __($provider['service_type']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $provider['status'] === 'available' ? 'success' : 'warning'; ?>">
                                        <?php echo __($provider['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($provider['latitude'] && $provider['longitude']): ?>
                                        <?php echo htmlspecialchars($provider['latitude'] . ', ' . $provider['longitude']); ?>
                                    <?php else: ?>
                                        <?php echo __('not_set'); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="manage_requests.php?edit_provider=<?php echo htmlspecialchars($provider['id']); ?>" 
                                       class="btn btn-sm btn-outline-primary me-1" 
                                       aria-label="<?php echo __('edit_provider'); ?>">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </a>
                                    <a href="manage_requests.php?delete_provider=<?php echo htmlspecialchars($provider['id']); ?>" 
                                       class="btn btn-sm btn-outline-danger" 
                                       onclick="return confirm('<?php echo __('confirm_delete_provider'); ?>')"
                                       aria-label="<?php echo __('delete_provider'); ?>">
                                        <i class="fas fa-trash" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>