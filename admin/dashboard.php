<?php

require_once('../includes/functions.php');
// Set page title
$pageTitle = __('admin_dashboard');

// Require admin authentication
require_once('../includes/auth_check.php');

// Check if user is admin
if ($currentUser['user_type'] !== 'admin') {
    setFlashMessage('danger', __('admin_access_required'));
    redirect(SITE_URL . '/dashboard/' . $currentUser['user_type'] . '.php');
    exit;
}

// Get stats for dashboard
$totalUsers = fetchRow("SELECT COUNT(*) as count FROM users")['count'];
$totalPilgrims = fetchRow("SELECT COUNT(*) as count FROM users WHERE user_type = 'pilgrim'")['count'];
$totalGuardians = fetchRow("SELECT COUNT(*) as count FROM users WHERE user_type = 'guardian'")['count'];
$totalAuthorities = fetchRow("SELECT COUNT(*) as count FROM users WHERE user_type = 'authority'")['count'];

$activeUsers = fetchRow("SELECT COUNT(*) as count FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 24 HOUR)")['count'];
$userPercentage = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100) : 0;

// Emergency stats
$totalEmergencies = fetchRow("SELECT COUNT(*) as count FROM emergencies")['count'];
$pendingEmergencies = fetchRow("SELECT COUNT(*) as count FROM emergencies WHERE status = 'requested'")['count'];
$resolvedEmergencies = fetchRow("SELECT COUNT(*) as count FROM emergencies WHERE status = 'resolved'")['count'];

// Service stats
$totalServices = fetchRow("SELECT COUNT(*) as count FROM service_requests")['count'];
$pendingServices = fetchRow("SELECT COUNT(*) as count FROM service_requests WHERE status = 'requested'")['count'];
$completedServices = fetchRow("SELECT COUNT(*) as count FROM service_requests WHERE status = 'completed'")['count'];

// Recent user registrations
$recentUsers = fetchAll("
    SELECT id, full_name, email, user_type, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 10
");

// Recent emergencies
$recentEmergencies = fetchAll("
    SELECT e.id, e.type, e.status, e.created_at, u.full_name as reporter
    FROM emergencies e
    JOIN users u ON e.reporter_id = u.id
    ORDER BY e.created_at DESC
    LIMIT 10
");

// Recent service requests
$recentServices = fetchAll("
    SELECT sr.id, sr.service_type, sr.status, sr.created_at, u.full_name as requester
    FROM service_requests sr
    JOIN users u ON sr.user_id = u.id
    ORDER BY sr.created_at DESC
    LIMIT 10
");

// Monthly registrations for chart
$monthlyRegistrations = fetchAll("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM users
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");

// Convert to chart data format
$chartLabels = [];
$chartData = [];
foreach ($monthlyRegistrations as $row) {
    $chartLabels[] = date('M Y', strtotime($row['month'] . '-01'));
    $chartData[] = $row['count'];
}

// Include header
include_once('../includes/header.php');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="fas fa-tachometer-alt me-2"></i> <?php echo __('admin_dashboard'); ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="<?php echo SITE_URL; ?>/admin/reports.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-file-alt me-1"></i> <?php echo __('reports'); ?>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/user_management.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-users me-1"></i> <?php echo __('manage_users'); ?>
                </a>
                <a href="<?php echo SITE_URL; ?>/admin/service_management.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-cogs me-1"></i> <?php echo __('manage_services'); ?>
                </a>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="refreshDashboard">
                <i class="fas fa-sync-alt me-1"></i> <?php echo __('refresh'); ?>
            </button>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted fw-normal mt-0"><?php echo __('total_users'); ?></h6>
                            <h3 class="mt-2 mb-1"><?php echo number_format($totalUsers); ?></h3>
                            <p class="mb-0 text-muted">
                                <span class="text-success me-2">
                                    <i class="fas fa-arrow-up"></i> 
                                    <?php echo number_format($activeUsers); ?>
                                </span>
                                <span class="text-nowrap"><?php echo __('active_users'); ?></span>
                            </p>
                        </div>
                        <div class="avatar-sm rounded-circle bg-primary text-white">
                            <i class="fas fa-users fa-lg pt-3 ps-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted fw-normal mt-0"><?php echo __('emergencies'); ?></h6>
                            <h3 class="mt-2 mb-1"><?php echo number_format($totalEmergencies); ?></h3>
                            <p class="mb-0 text-muted">
                                <span class="text-danger me-2">
                                    <i class="fas fa-exclamation-circle"></i> 
                                    <?php echo number_format($pendingEmergencies); ?>
                                </span>
                                <span class="text-nowrap"><?php echo __('pending'); ?></span>
                            </p>
                        </div>
                        <div class="avatar-sm rounded-circle bg-danger text-white">
                            <i class="fas fa-ambulance fa-lg pt-3 ps-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted fw-normal mt-0"><?php echo __('service_requests'); ?></h6>
                            <h3 class="mt-2 mb-1"><?php echo number_format($totalServices); ?></h3>
                            <p class="mb-0 text-muted">
                                <span class="text-warning me-2">
                                    <i class="fas fa-clock"></i> 
                                    <?php echo number_format($pendingServices); ?>
                                </span>
                                <span class="text-nowrap"><?php echo __('pending'); ?></span>
                            </p>
                        </div>
                        <div class="avatar-sm rounded-circle bg-success text-white">
                            <i class="fas fa-concierge-bell fa-lg pt-3 ps-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted fw-normal mt-0"><?php echo __('user_distribution'); ?></h6>
                            <div class="mt-2 d-flex align-items-center">
                                <span class="badge bg-primary me-1"><?php echo number_format($totalPilgrims); ?></span>
                                <small><?php echo __('pilgrims'); ?></small>
                            </div>
                            <div class="mt-1 d-flex align-items-center">
                                <span class="badge bg-success me-1"><?php echo number_format($totalGuardians); ?></span>
                                <small><?php echo __('guardians'); ?></small>
                            </div>
                            <div class="mt-1 d-flex align-items-center">
                                <span class="badge bg-info me-1"><?php echo number_format($totalAuthorities); ?></span>
                                <small><?php echo __('authorities'); ?></small>
                            </div>
                        </div>
                        <div class="avatar-sm rounded-circle bg-info text-white">
                            <i class="fas fa-user-tag fa-lg pt-3 ps-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- User Registration Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0"><?php echo __('user_registrations'); ?></h5>
                </div>
                <div class="card-body">
                    <canvas id="registrationsChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Statistics Overview -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h5 class="card-title mb-0"><?php echo __('statistics_overview'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6><?php echo __('users'); ?></h6>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"><?php echo number_format($totalUsers); ?></div>
                        </div>
                        <small class="text-muted"><?php echo sprintf(__('active_users_percent'), $userPercentage); ?></small>
                    </div>
                    
                    <div class="mb-4">
                        <h6><?php echo __('emergencies'); ?></h6>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $totalEmergencies > 0 ? ($resolvedEmergencies / $totalEmergencies) * 100 : 0; ?>%" aria-valuenow="<?php echo $resolvedEmergencies; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalEmergencies; ?>"><?php echo $resolvedEmergencies; ?></div>
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $totalEmergencies > 0 ? (($totalEmergencies - $resolvedEmergencies - $pendingEmergencies) / $totalEmergencies) * 100 : 0; ?>%" aria-valuenow="<?php echo $totalEmergencies - $resolvedEmergencies - $pendingEmergencies; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalEmergencies; ?>"><?php echo $totalEmergencies - $resolvedEmergencies - $pendingEmergencies; ?></div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $totalEmergencies > 0 ? ($pendingEmergencies / $totalEmergencies) * 100 : 0; ?>%" aria-valuenow="<?php echo $pendingEmergencies; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalEmergencies; ?>"><?php echo $pendingEmergencies; ?></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-success"><?php echo __('resolved'); ?>: <?php echo $resolvedEmergencies; ?></small>
                            <small class="text-warning"><?php echo __('in_progress'); ?>: <?php echo $totalEmergencies - $resolvedEmergencies - $pendingEmergencies; ?></small>
                            <small class="text-danger"><?php echo __('pending'); ?>: <?php echo $pendingEmergencies; ?></small>
                        </div>
                    </div>
                    
                    <div>
                        <h6><?php echo __('service_requests'); ?></h6>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $totalServices > 0 ? ($completedServices / $totalServices) * 100 : 0; ?>%" aria-valuenow="<?php echo $completedServices; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalServices; ?>"><?php echo $completedServices; ?></div>
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $totalServices > 0 ? (($totalServices - $completedServices - $pendingServices) / $totalServices) * 100 : 0; ?>%" aria-valuenow="<?php echo $totalServices - $completedServices - $pendingServices; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalServices; ?>"><?php echo $totalServices - $completedServices - $pendingServices; ?></div>
                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $totalServices > 0 ? ($pendingServices / $totalServices) * 100 : 0; ?>%" aria-valuenow="<?php echo $pendingServices; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalServices; ?>"><?php echo $pendingServices; ?></div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small class="text-success"><?php echo __('completed'); ?>: <?php echo $completedServices; ?></small>
                            <small class="text-warning"><?php echo __('in_progress'); ?>: <?php echo $totalServices - $completedServices - $pendingServices; ?></small>
                            <small class="text-danger"><?php echo __('pending'); ?>: <?php echo $pendingServices; ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Users -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><?php echo __('recent_users'); ?></h5>
                    <a href="<?php echo SITE_URL; ?>/admin/user_management.php" class="btn btn-sm btn-outline-primary">
                        <?php echo __('view_all'); ?>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('name'); ?></th>
                                    <th><?php echo __('user_type'); ?></th>
                                    <th><?php echo __('date'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentUsers)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4"><?php echo __('no_data'); ?></td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($recentUsers as $user): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/admin/user_management.php?action=edit&id=<?php echo $user['id']; ?>" class="text-reset fw-bold">
                                                <?php echo htmlspecialchars($user['full_name']); ?>
                                            </a>
                                            <div class="small text-muted"><?php echo htmlspecialchars($user['email']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getRoleBadgeClass($user['user_type']); ?>">
                                                <?php echo __(htmlspecialchars($user['user_type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Emergencies -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><?php echo __('recent_emergencies'); ?></h5>
                    <a href="<?php echo SITE_URL; ?>/api/emergency_api.php?action=list" class="btn btn-sm btn-outline-primary">
                        <?php echo __('view_all'); ?>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('type'); ?></th>
                                    <th><?php echo __('status'); ?></th>
                                    <th><?php echo __('date'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentEmergencies)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4"><?php echo __('no_data'); ?></td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($recentEmergencies as $emergency): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/api/emergency_api.php?action=get_emergency&id=<?php echo $emergency['id']; ?>" class="text-reset fw-bold">
                                                <?php echo __(htmlspecialchars($emergency['type'])); ?>
                                            </a>
                                            <div class="small text-muted"><?php echo htmlspecialchars($emergency['reporter']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusBadgeClass($emergency['status']); ?>">
                                                <?php echo __(htmlspecialchars($emergency['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($emergency['created_at']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Service Requests -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><?php echo __('recent_service_requests'); ?></h5>
                    <a href="<?php echo SITE_URL; ?>/admin/service_management.php" class="btn btn-sm btn-outline-primary">
                        <?php echo __('view_all'); ?>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo __('type'); ?></th>
                                    <th><?php echo __('status'); ?></th>
                                    <th><?php echo __('date'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentServices)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4"><?php echo __('no_data'); ?></td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($recentServices as $service): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/api/service_api.php?action=get_request&id=<?php echo $service['id']; ?>" class="text-reset fw-bold">
                                                <?php echo __(htmlspecialchars($service['service_type'])); ?>
                                            </a>
                                            <div class="small text-muted"><?php echo htmlspecialchars($service['requester']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusBadgeClass($service['status']); ?>">
                                                <?php echo __(htmlspecialchars($service['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($service['created_at']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Registration chart
        const ctx = document.getElementById('registrationsChart').getContext('2d');
        const registrationsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: '<?php echo __('user_registrations'); ?>',
                    data: <?php echo json_encode($chartData); ?>,
                    fill: true,
                    backgroundColor: 'rgba(93, 92, 222, 0.1)',
                    borderColor: 'rgba(93, 92, 222, 1)',
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(93, 92, 222, 1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Refresh dashboard
        document.getElementById('refreshDashboard').addEventListener('click', function() {
            window.location.reload();
        });
    });
    
    function getStatusBadgeClass(status) {
        switch(status) {
            case 'requested':
                return 'danger';
            case 'in_progress':
            case 'accepted':
                return 'warning';
            case 'resolved':
            case 'completed':
                return 'success';
            case 'cancelled':
                return 'secondary';
            default:
                return 'primary';
        }
    }
    
    function getRoleBadgeClass(role) {
        switch(role) {
            case 'admin':
                return 'danger';
            case 'authority':
                return 'warning';
            case 'guardian':
                return 'success';
            case 'pilgrim':
                return 'primary';
            default:
                return 'secondary';
        }
    }
</script>

<style>
    .avatar-sm {
        height: 3rem;
        width: 3rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<?php
// Include footer
include_once('../includes/footer.php');
?>