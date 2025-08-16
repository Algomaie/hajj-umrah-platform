<?php

// Require admin authentication
require_once('../includes/auth_check.php');

// Set page title
$pageTitle = __('admin_reports');

// Check if user is an admin or authority
if ($currentUser['user_type'] !== 'admin' && $currentUser['user_type'] !== 'authority') {
    // Redirect to dashboard
    redirect(SITE_URL . '/dashboard/' . $currentUser['user_type'] . '.php');
}

// Get date range parameters
$startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : date('Y-m-d');
$reportType = isset($_GET['report_type']) ? sanitizeInput($_GET['report_type']) : 'summary';

// Include header
include_once('../includes/header.php');

// Fetch summary data
$totalUsers = fetchRow("SELECT COUNT(*) as count FROM users WHERE created_at BETWEEN ? AND ?", [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])['count'];
$newUsers = fetchRow("SELECT COUNT(*) as count FROM users WHERE created_at BETWEEN ? AND ?", [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])['count'];
$totalEmergencies = fetchRow("SELECT COUNT(*) as count FROM emergencies WHERE created_at BETWEEN ? AND ?", [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])['count'];
$resolvedEmergencies = fetchRow("SELECT COUNT(*) as count FROM emergencies WHERE status = 'resolved' AND created_at BETWEEN ? AND ?", [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])['count'];
$totalServiceRequests = fetchRow("SELECT COUNT(*) as count FROM service_requests WHERE created_at BETWEEN ? AND ?", [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])['count'];
$completedServiceRequests = fetchRow("SELECT COUNT(*) as count FROM service_requests WHERE status = 'completed' AND created_at BETWEEN ? AND ?", [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])['count'];
$activeGroups = fetchRow("SELECT COUNT(DISTINCT group_id) as count FROM group_members JOIN locations ON group_members.user_id = locations.user_id WHERE locations.timestamp BETWEEN ? AND ?", [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])['count'];

// Fetch data for selected report type
$reportData = [];
switch ($reportType) {
    case 'users':
        $reportData = fetchAll("
            SELECT u.id, u.full_name, u.email, u.user_type, u.nationality, u.created_at, 
                   (SELECT COUNT(*) FROM group_members WHERE user_id = u.id) as group_count,
                   (SELECT MAX(timestamp) FROM locations WHERE user_id = u.id) as last_active
            FROM users u
            WHERE u.created_at BETWEEN ? AND ?
            ORDER BY u.created_at DESC
            LIMIT 1000
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Get useruser_types breakdown
        $userRoles = fetchAll("
            SELECT user_type, COUNT(*) as count 
            FROM users 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY user_type
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Get user registrations by day
        $userRegistrationsByDay = fetchAll("
            SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM users 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        break;
        
    case 'emergencies':
        $reportData = fetchAll("
            SELECT e.id, e.type, e.status, e.description, e.created_at, e.resolved_at,
                   u.full_name as reporter_name,
                   (SELECT full_name FROM users WHERE id = e.handled_by) as handler_name
            FROM emergencies e
            JOIN users u ON e.reporter_id = u.id
            WHERE e.created_at BETWEEN ? AND ?
            ORDER BY e.created_at DESC
            LIMIT 1000
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Get emergency types breakdown
        $emergencyTypes = fetchAll("
            SELECT type, COUNT(*) as count 
            FROM emergencies 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY type
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Get average resolution time
        $avgResolutionTime = fetchRow("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_minutes
            FROM emergencies
            WHERE status = 'resolved'
            AND created_at BETWEEN ? AND ?
            AND resolved_at IS NOT NULL
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        break;
        
    case 'services':
        $reportData = fetchAll("
            SELECT sr.id, sr.service_type, sr.status, sr.created_at, sr.completed_at,
                   u.full_name as requester_name,
                   sp.name as provider_name
            FROM service_requests sr
            JOIN users u ON sr.user_id = u.id
            LEFT JOIN service_providers sp ON sr.provider_id = sp.id
            WHERE sr.created_at BETWEEN ? AND ?
            ORDER BY sr.created_at DESC
            LIMIT 1000
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Get service types breakdown
        $serviceTypes = fetchAll("
            SELECT service_type, COUNT(*) as count 
            FROM service_requests 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY service_type
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Get service requests by day
        $serviceRequestsByDay = fetchAll("
            SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM service_requests 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        break;
        
    case 'locations':
        // Get heat map data (aggregated for performance)
        $heatMapData = fetchAll("
            SELECT 
                ROUND(latitude, 3) as lat,
                ROUND(longitude, 3) as lng,
                COUNT(*) as weight
            FROM locations
            WHERE timestamp BETWEEN ? AND ?
            GROUP BY ROUND(latitude, 3), ROUND(longitude, 3)
            HAVING COUNT(*) > 5
            LIMIT 5000
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Get most active areas
        $activeAreas = fetchAll("
            SELECT 
                ROUND(latitude, 2) as lat,
                ROUND(longitude, 2) as lng,
                COUNT(*) as count
            FROM locations
            WHERE timestamp BETWEEN ? AND ?
            GROUP BY ROUND(latitude, 2), ROUND(longitude, 2)
            ORDER BY count DESC
            LIMIT 10
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Get location updates by hour
        $locationUpdatesByHour = fetchAll("
            SELECT HOUR(timestamp) as hour, COUNT(*) as count 
            FROM locations 
            WHERE timestamp BETWEEN ? AND ?
            GROUP BY HOUR(timestamp)
            ORDER BY hour
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        break;
        
    case 'groups':
        $reportData = fetchAll("
            SELECT g.id, g.name, g.created_at, u.full_name as creator_name,
                   (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
                   (SELECT MAX(l.timestamp) 
                    FROM locations l 
                    JOIN group_members gm ON l.user_id = gm.user_id 
                    WHERE gm.group_id = g.id) as last_active
            FROM groups g
            JOIN users u ON g.created_by = u.id
            WHERE g.created_at BETWEEN ? AND ?
            ORDER BY g.created_at DESC
            LIMIT 1000
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Get groups created by day
        $groupsByDay = fetchAll("
            SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM groups 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Get group size distribution
        $groupSizes = fetchAll("
            SELECT size_category, COUNT(*) as count FROM (
                SELECT 
                    CASE 
                        WHEN member_count < 3 THEN 'Small (1-2)'
                        WHEN member_count < 6 THEN 'Medium (3-5)'
                        WHEN member_count < 11 THEN 'Large (6-10)'
                        ELSE 'Very Large (11+)'
                    END as size_category
                FROM (
                    SELECT g.id, COUNT(gm.user_id) as member_count
                    FROM groups g
                    JOIN group_members gm ON g.id = gm.group_id
                    WHERE g.created_at BETWEEN ? AND ?
                    GROUP BY g.id
                ) as group_counts
            ) as categorized
            GROUP BY size_category
            ORDER BY 
                CASE 
                    WHEN size_category = 'Small (1-2)' THEN 1
                    WHEN size_category = 'Medium (3-5)' THEN 2
                    WHEN size_category = 'Large (6-10)' THEN 3
                    ELSE 4
                END
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        break;
        
    case 'rituals':
        $reportData = fetchAll("
            SELECT rp.id, rp.ritual_type, u.full_name, 
                   rp.tawaf_rounds, rp.sai_rounds, 
                   rp.halq_completed, rp.arafat_completed, 
                   rp.muzdalifah_completed, rp.mina_completed, 
                   rp.jamarat_completed, rp.started_at, rp.completed_at
            FROM ritual_progress rp
            JOIN users u ON rp.user_id = u.id
            WHERE rp.started_at BETWEEN ? AND ?
            ORDER BY rp.started_at DESC
            LIMIT 1000
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Get ritual types breakdown
        $ritualTypes = fetchAll("
            SELECT ritual_type, COUNT(*) as count 
            FROM ritual_progress 
            WHERE started_at BETWEEN ? AND ?
            GROUP BY ritual_type
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Get completion rates
        $completionRates = [
            'tawaf' => fetchRow("
                SELECT 
                    COUNT(CASE WHEN tawaf_rounds = 7 THEN 1 END) as completed,
                    COUNT(*) as total
                FROM ritual_progress
                WHERE started_at BETWEEN ? AND ?
            ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']),
            'sai' => fetchRow("
                SELECT 
                    COUNT(CASE WHEN sai_rounds = 7 THEN 1 END) as completed,
                    COUNT(*) as total
                FROM ritual_progress
                WHERE started_at BETWEEN ? AND ?
            ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']),
            'halq' => fetchRow("
                SELECT 
                    COUNT(CASE WHEN halq_completed = 1 THEN 1 END) as completed,
                    COUNT(*) as total
                FROM ritual_progress
                WHERE started_at BETWEEN ? AND ?
            ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
        ];
        break;
        
    case 'summary':
    default:
        // Calculate percentage changes
        $previousStartDate = date('Y-m-d', strtotime('-60 days'));
        $previousEndDate = date('Y-m-d', strtotime('-31 days'));
        
        $previousUserCount = fetchRow("SELECT COUNT(*) as count FROM users WHERE created_at BETWEEN ? AND ?", [$previousStartDate . ' 00:00:00', $previousEndDate . ' 23:59:59'])['count'];
        $userChangePercent = $previousUserCount > 0 ? round(($newUsers - $previousUserCount) / $previousUserCount * 100, 2) : 100;
        
        $previousEmergencyCount = fetchRow("SELECT COUNT(*) as count FROM emergencies WHERE created_at BETWEEN ? AND ?", [$previousStartDate . ' 00:00:00', $previousEndDate . ' 23:59:59'])['count'];
        $emergencyChangePercent = $previousEmergencyCount > 0 ? round(($totalEmergencies - $previousEmergencyCount) / $previousEmergencyCount * 100, 2) : 100;
        
        $previousServiceCount = fetchRow("SELECT COUNT(*) as count FROM service_requests WHERE created_at BETWEEN ? AND ?", [$previousStartDate . ' 00:00:00', $previousEndDate . ' 23:59:59'])['count'];
        $serviceChangePercent = $previousServiceCount > 0 ? round(($totalServiceRequests - $previousServiceCount) / $previousServiceCount * 100, 2) : 100;
        
        // Get activity by day
        $activityByDay = fetchAll("
            SELECT date, 
                   SUM(user_count) as user_count, 
                   SUM(emergency_count) as emergency_count, 
                   SUM(service_count) as service_count,
                   SUM(location_count) as location_count
            FROM (
                SELECT DATE(created_at) as date, COUNT(*) as user_count, 0 as emergency_count, 0 as service_count, 0 as location_count
                FROM users
                WHERE created_at BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                
                UNION ALL
                
                SELECT DATE(created_at) as date, 0 as user_count, COUNT(*) as emergency_count, 0 as service_count, 0 as location_count
                FROM emergencies
                WHERE created_at BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                
                UNION ALL
                
                SELECT DATE(created_at) as date, 0 as user_count, 0 as emergency_count, COUNT(*) as service_count, 0 as location_count
                FROM service_requests
                WHERE created_at BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                
                UNION ALL
                
                SELECT DATE(timestamp) as date, 0 as user_count, 0 as emergency_count, 0 as service_count, COUNT(*) as location_count
                FROM locations
                WHERE timestamp BETWEEN ? AND ?
                GROUP BY DATE(timestamp)
            ) as combined
            GROUP BY date
            ORDER BY date
        ", [
            $startDate . ' 00:00:00', $endDate . ' 23:59:59',
            $startDate . ' 00:00:00', $endDate . ' 23:59:59',
            $startDate . ' 00:00:00', $endDate . ' 23:59:59',
            $startDate . ' 00:00:00', $endDate . ' 23:59:59'
        ]);
        
        // Get user nationalities
        $userNationalities = fetchAll("
            SELECT nationality, COUNT(*) as count 
            FROM users 
            WHERE created_at BETWEEN ? AND ?
            AND nationality IS NOT NULL AND nationality != ''
            GROUP BY nationality
            ORDER BY count DESC
            LIMIT 10
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        // Get emergency response times
        $emergencyResponseTimes = fetchAll("
            SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(MINUTE, created_at, resolved_at) < 30 THEN 'Less than 30 min'
                    WHEN TIMESTAMPDIFF(MINUTE, created_at, resolved_at) < 60 THEN '30-60 min'
                    WHEN TIMESTAMPDIFF(MINUTE, created_at, resolved_at) < 120 THEN '1-2 hours'
                    WHEN TIMESTAMPDIFF(MINUTE, created_at, resolved_at) < 240 THEN '2-4 hours'
                    ELSE 'More than 4 hours'
                END as time_category,
                COUNT(*) as count
            FROM emergencies
            WHERE status = 'resolved'
            AND created_at BETWEEN ? AND ?
            AND resolved_at IS NOT NULL
            GROUP BY time_category
            ORDER BY 
                CASE 
                    WHEN time_category = 'Less than 30 min' THEN 1
                    WHEN time_category = '30-60 min' THEN 2
                    WHEN time_category = '1-2 hours' THEN 3
                    WHEN time_category = '2-4 hours' THEN 4
                    ELSE 5
                END
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        break;
}
?>

<div class="container-fluid py-4">
    <!-- Page Header with Filters -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-chart-bar me-2"></i> <?php echo __('admin_reports'); ?></h1>
            <p class="lead"><?php echo __('reports_description'); ?></p>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="start_date" class="form-label"><?php echo __('start_date'); ?></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                        </div>
                        <div class="col-md-5">
                            <label for="end_date" class="form-label"><?php echo __('end_date'); ?></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                        </div>
                        <input type="hidden" name="report_type" value="<?php echo $reportType; ?>">
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Report Type Navigation -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $reportType === 'summary' ? 'active' : ''; ?>" href="?report_type=summary&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                <i class="fas fa-tachometer-alt me-1"></i> <?php echo __('summary'); ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $reportType === 'users' ? 'active' : ''; ?>" href="?report_type=users&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                <i class="fas fa-users me-1"></i> <?php echo __('users_report'); ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $reportType === 'emergencies' ? 'active' : ''; ?>" href="?report_type=emergencies&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                <i class="fas fa-ambulance me-1"></i> <?php echo __('emergencies_report'); ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $reportType === 'services' ? 'active' : ''; ?>" href="?report_type=services&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                <i class="fas fa-concierge-bell me-1"></i> <?php echo __('services_report'); ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $reportType === 'locations' ? 'active' : ''; ?>" href="?report_type=locations&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                <i class="fas fa-map-marker-alt me-1"></i> <?php echo __('locations_report'); ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $reportType === 'groups' ? 'active' : ''; ?>" href="?report_type=groups&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                <i class="fas fa-users-cog me-1"></i> <?php echo __('groups_report'); ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $reportType === 'rituals' ? 'active' : ''; ?>" href="?report_type=rituals&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                <i class="fas fa-kaaba me-1"></i> <?php echo __('rituals_report'); ?>
            </a>
        </li>
    </ul>
    
    <?php if ($reportType === 'summary'): ?>
    <!-- Summary Dashboard -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?php echo __('new_users'); ?></div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo number_format($newUsers); ?></div>
                            <div class="mt-2 small">
                                <?php if ($userChangePercent > 0): ?>
                                <span class="text-success"><i class="fas fa-arrow-up me-1"></i><?php echo $userChangePercent; ?>%</span>
                                <?php elseif ($userChangePercent < 0): ?>
                                <span class="text-danger"><i class="fas fa-arrow-down me-1"></i><?php echo abs($userChangePercent); ?>%</span>
                                <?php else: ?>
                                <span class="text-muted"><i class="fas fa-equals me-1"></i>0%</span>
                                <?php endif; ?>
                                <span class="text-muted"><?php echo __('from_previous_period'); ?></span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1"><?php echo __('emergency_reports'); ?></div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo number_format($totalEmergencies); ?></div>
                            <div class="mt-2 small">
                                <?php if ($emergencyChangePercent > 0): ?>
                                <span class="text-danger"><i class="fas fa-arrow-up me-1"></i><?php echo $emergencyChangePercent; ?>%</span>
                                <?php elseif ($emergencyChangePercent < 0): ?>
                                <span class="text-success"><i class="fas fa-arrow-down me-1"></i><?php echo abs($emergencyChangePercent); ?>%</span>
                                <?php else: ?>
                                <span class="text-muted"><i class="fas fa-equals me-1"></i>0%</span>
                                <?php endif; ?>
                                <span class="text-muted"><?php echo __('from_previous_period'); ?></span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ambulance fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1"><?php echo __('service_requests'); ?></div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo number_format($totalServiceRequests); ?></div>
                            <div class="mt-2 small">
                                <?php if ($serviceChangePercent > 0): ?>
                                <span class="text-success"><i class="fas fa-arrow-up me-1"></i><?php echo $serviceChangePercent; ?>%</span>
                                <?php elseif ($serviceChangePercent < 0): ?>
                                <span class="text-danger"><i class="fas fa-arrow-down me-1"></i><?php echo abs($serviceChangePercent); ?>%</span>
                                <?php else: ?>
                                <span class="text-muted"><i class="fas fa-equals me-1"></i>0%</span>
                                <?php endif; ?>
                                <span class="text-muted"><?php echo __('from_previous_period'); ?></span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-concierge-bell fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1"><?php echo __('active_groups'); ?></div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo number_format($activeGroups); ?></div>
                            <div class="mt-2 small">
                                <span class="text-success"><?php echo $resolvedEmergencies; ?> <?php echo __('resolved_emergencies'); ?></span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users-cog fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Charts -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold"><?php echo __('activity_over_time'); ?></h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height:50vh;">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('user_nationalities'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:25vh;">
                                <canvas id="nationalitiesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('emergency_response_times'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:25vh;">
                                <canvas id="responseTimesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Export Buttons -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="mb-3"><?php echo __('export_reports'); ?></h5>
                    <div class="btn-group"user_type="group">
                        <a href="<?php echo SITE_URL; ?>/exports/export_users.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-primary">
                            <i class="fas fa-users me-1"></i> <?php echo __('export_users'); ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/exports/export_emergencies.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-danger">
                            <i class="fas fa-ambulance me-1"></i> <?php echo __('export_emergencies'); ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/exports/export_services.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-success">
                            <i class="fas fa-concierge-bell me-1"></i> <?php echo __('export_services'); ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/exports/export_groups.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-info">
                            <i class="fas fa-users-cog me-1"></i> <?php echo __('export_groups'); ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/exports/export_all.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-secondary">
                            <i class="fas fa-file-export me-1"></i> <?php echo __('export_all'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($reportType === 'users'): ?>
    <!-- Users Report -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><?php echo __('users_list'); ?></h6>
                    <a href="<?php echo SITE_URL; ?>/exports/export_users.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-download me-1"></i> <?php echo __('export'); ?>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th><?php echo __('id'); ?></th>
                                    <th><?php echo __('name'); ?></th>
                                    <th><?php echo __('email'); ?></th>
                                    <th><?php echo __('user_type'); ?></th>
                                    <th><?php echo __('nationality'); ?></th>
                                    <th><?php echo __('registration_date'); ?></th>
                                    <th><?php echo __('last_active'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="badge bg-<?php echo $user['user_type']  === 'admin' ? 'danger' : ($user['user_type']  === 'authority' ? 'warning' : 'primary'); ?>"><?php echo $user['user_type'] ; ?></span></td>
                                    <td><?php echo htmlspecialchars($user['nationality'] ?: '-'); ?></td>
                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                    <td><?php echo $user['last_active'] ? formatDate($user['last_active']) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('user_roles'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:25vh;">
                                <canvas id="userRolesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('registrations_by_day'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:25vh;">
                                <canvas id="registrationsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('user_statistics'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('total_users'); ?></div>
                                    <div class="h5"><?php echo number_format($totalUsers); ?></div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('new_users'); ?></div>
                                    <div class="h5"><?php echo number_format($newUsers); ?></div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('pilgrims'); ?></div>
                                    <div class="h5">
                                        <?php 
                                        $pilgrimCount = 0;
                                        foreach ($userRoles as $role) {
                                            if ($role['user_type']  === 'pilgrim') {
                                                $pilgrimCount = $role['count'];
                                                break;
                                            }
                                        }
                                        echo number_format($pilgrimCount); 
                                        ?>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('guardians'); ?></div>
                                    <div class="h5">
                                        <?php 
                                        $guardianCount = 0;
                                        foreach ($userRoles as $role) {
                                            if ($role['user_type']  === 'guardian') {
                                                $guardianCount = $role['count'];
                                                break;
                                            }
                                        }
                                        echo number_format($guardianCount); 
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($reportType === 'emergencies'): ?>
    <!-- Emergencies Report -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><?php echo __('emergencies_list'); ?></h6>
                    <a href="<?php echo SITE_URL; ?>/exports/export_emergencies.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-download me-1"></i> <?php echo __('export'); ?>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="emergenciesTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th><?php echo __('id'); ?></th>
                                    <th><?php echo __('type'); ?></th>
                                    <th><?php echo __('reporter'); ?></th>
                                    <th><?php echo __('status'); ?></th>
                                    <th><?php echo __('handler'); ?></th>
                                    <th><?php echo __('created_at'); ?></th>
                                    <th><?php echo __('resolved_at'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $emergency): ?>
                                <tr>
                                    <td><?php echo $emergency['id']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $emergency['type'] === 'medical' ? 'danger' : 
                                                ($emergency['type'] === 'missing_person' ? 'warning' : 
                                                    ($emergency['type'] === 'security' ? 'dark' : 'info')); 
                                        ?>">
                                            <?php echo $emergency['type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($emergency['reporter_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $emergency['status'] === 'resolved' ? 'success' : 
                                                ($emergency['status'] === 'in_progress' ? 'primary' : 
                                                    ($emergency['status'] === 'cancelled' ? 'secondary' : 'warning')); 
                                        ?>">
                                            <?php echo $emergency['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($emergency['handler_name'] ?: '-'); ?></td>
                                    <td><?php echo formatDate($emergency['created_at']); ?></td>
                                    <td><?php echo $emergency['resolved_at'] ? formatDate($emergency['resolved_at']) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('emergency_types'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:25vh;">
                                <canvas id="emergencyTypesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('emergencies_statistics'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('total_emergencies'); ?></div>
                                    <div class="h5"><?php echo number_format($totalEmergencies); ?></div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('resolved_emergencies'); ?></div>
                                    <div class="h5"><?php echo number_format($resolvedEmergencies); ?></div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('resolution_rate'); ?></div>
                                    <div class="h5">
                                        <?php echo $totalEmergencies > 0 ? round(($resolvedEmergencies / $totalEmergencies) * 100, 2) : 0; ?>%
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('avg_resolution_time'); ?></div>
                                    <div class="h5">
                                        <?php 
                                        if (isset($avgResolutionTime['avg_minutes']) && $avgResolutionTime['avg_minutes']) {
                                            $minutes = round($avgResolutionTime['avg_minutes']);
                                            if ($minutes < 60) {
                                                echo $minutes . ' ' . __('minutes');
                                            } else {
                                                $hours = floor($minutes / 60);
                                                $remainingMinutes = $minutes % 60;
                                                echo $hours . 'h ' . $remainingMinutes . 'm';
                                            }
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold"><?php echo __('emergency_locations'); ?></h6>
                            <span class="small text-muted"><?php echo __('click_to_view'); ?></span>
                        </div>
                        <div class="card-body">
                            <div id="emergenciesMap" style="height: 200px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($reportType === 'services'): ?>
    <!-- Services Report -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><?php echo __('services_list'); ?></h6>
                    <a href="<?php echo SITE_URL; ?>/exports/export_services.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-download me-1"></i> <?php echo __('export'); ?>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="servicesTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th><?php echo __('id'); ?></th>
                                    <th><?php echo __('type'); ?></th>
                                    <th><?php echo __('requester'); ?></th>
                                    <th><?php echo __('provider'); ?></th>
                                    <th><?php echo __('status'); ?></th>
                                    <th><?php echo __('created_at'); ?></th>
                                    <th><?php echo __('completed_at'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $service): ?>
                                <tr>
                                    <td><?php echo $service['id']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $service['service_type'] === 'cart' ? 'info' : 
                                                ($service['service_type'] === 'wheelchair' ? 'primary' : 
                                                    ($service['service_type'] === 'guide' ? 'success' : 
                                                        ($service['service_type'] === 'medical' ? 'danger' : 'secondary'))); 
                                        ?>">
                                            <?php echo $service['service_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($service['requester_name']); ?></td>
                                    <td><?php echo htmlspecialchars($service['provider_name'] ?: '-'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $service['status'] === 'completed' ? 'success' : 
                                                ($service['status'] === 'in_progress' || $service['status'] === 'accepted' ? 'primary' : 
                                                    ($service['status'] === 'cancelled' ? 'secondary' : 'warning')); 
                                        ?>">
                                            <?php echo $service['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($service['created_at']); ?></td>
                                    <td><?php echo $service['completed_at'] ? formatDate($service['completed_at']) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('service_types'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:25vh;">
                                <canvas id="serviceTypesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('service_requests_by_day'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:25vh;">
                                <canvas id="serviceRequestsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('services_statistics'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('total_services'); ?></div>
                                    <div class="h5"><?php echo number_format($totalServiceRequests); ?></div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('completed_services'); ?></div>
                                    <div class="h5"><?php echo number_format($completedServiceRequests); ?></div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('completion_rate'); ?></div>
                                    <div class="h5">
                                        <?php echo $totalServiceRequests > 0 ? round(($completedServiceRequests / $totalServiceRequests) * 100, 2) : 0; ?>%
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('avg_per_day'); ?></div>
                                    <div class="h5">
                                        <?php
                                        $days = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24) + 1;
                                        echo $days > 0 ? round($totalServiceRequests / $days, 1) : 0;
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($reportType === 'locations'): ?>
    <!-- Locations Report -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold"><?php echo __('location_heat_map'); ?></h6>
                </div>
                <div class="card-body">
                    <div id="heatMap" style="height: 600px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('location_updates_by_hour'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:25vh;">
                                <canvas id="locationUpdatesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('most_active_areas'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th><?php echo __('area'); ?></th>
                                            <th><?php echo __('location'); ?></th>
                                            <th><?php echo __('count'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeAreas as $index => $area): ?>
                                        <tr>
                                            <td><?php echo __('area') . ' ' . ($index + 1); ?></td>
                                            <td><small><?php echo $area['lat'] . ', ' . $area['lng']; ?></small></td>
                                            <td><span class="badge bg-primary"><?php echo number_format($area['count']); ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('location_statistics'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('total_updates'); ?></div>
                                    <div class="h5">
                                        <?php
                                        $totalUpdates = 0;
                                        foreach ($locationUpdatesByHour as $hourData) {
                                            $totalUpdates += $hourData['count'];
                                        }
                                        echo number_format($totalUpdates);
                                        ?>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('unique_users'); ?></div>
                                    <div class="h5">
                                        <?php
                                        $uniqueUsers = fetchRow("
                                            SELECT COUNT(DISTINCT user_id) as count
                                            FROM locations
                                            WHERE timestamp BETWEEN ? AND ?
                                        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])['count'];
                                        echo number_format($uniqueUsers);
                                        ?>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('avg_updates_per_user'); ?></div>
                                    <div class="h5">
                                        <?php echo $uniqueUsers > 0 ? round($totalUpdates / $uniqueUsers, 1) : 0; ?>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('peak_hour'); ?></div>
                                    <div class="h5">
                                        <?php
                                        $peakHour = 0;
                                        $peakCount = 0;
                                        foreach ($locationUpdatesByHour as $hourData) {
                                            if ($hourData['count'] > $peakCount) {
                                                $peakCount = $hourData['count'];
                                                $peakHour = $hourData['hour'];
                                            }
                                        }
                                        echo sprintf('%02d:00', $peakHour);
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($reportType === 'groups'): ?>
    <!-- Groups Report -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><?php echo __('groups_list'); ?></h6>
                    <a href="<?php echo SITE_URL; ?>/exports/export_groups.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-download me-1"></i> <?php echo __('export'); ?>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="groupsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th><?php echo __('id'); ?></th>
                                    <th><?php echo __('name'); ?></th>
                                    <th><?php echo __('creator'); ?></th>
                                    <th><?php echo __('members'); ?></th>
                                    <th><?php echo __('created_at'); ?></th>
                                    <th><?php echo __('last_active'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $group): ?>
                                <tr>
                                    <td><?php echo $group['id']; ?></td>
                                    <td><?php echo htmlspecialchars($group['name']); ?></td>
                                    <td><?php echo htmlspecialchars($group['creator_name']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $group['member_count']; ?></span></td>
                                    <td><?php echo formatDate($group['created_at']); ?></td>
                                    <td><?php echo $group['last_active'] ? formatDate($group['last_active']) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('groups_by_day'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:25vh;">
                                <canvas id="groupsByDayChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('group_size_distribution'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:25vh;">
                                <canvas id="groupSizeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('group_statistics'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('total_groups'); ?></div>
                                    <div class="h5"><?php echo number_format(count($reportData)); ?></div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('active_groups'); ?></div>
                                    <div class="h5"><?php echo number_format($activeGroups); ?></div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('avg_group_size'); ?></div>
                                    <div class="h5">
                                        <?php
                                        $totalMembers = 0;
                                        $totalGroups = count($reportData);
                                        foreach ($reportData as $group) {
                                            $totalMembers += $group['member_count'];
                                        }
                                        echo $totalGroups > 0 ? round($totalMembers / $totalGroups, 1) : 0;
                                        ?>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('largest_group'); ?></div>
                                    <div class="h5">
                                        <?php
                                        $largestGroup = 0;
                                        foreach ($reportData as $group) {
                                            $largestGroup = max($largestGroup, $group['member_count']);
                                        }
                                        echo number_format($largestGroup);
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php elseif ($reportType === 'rituals'): ?>
    <!-- Rituals Report -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><?php echo __('rituals_list'); ?></h6>
                    <a href="<?php echo SITE_URL; ?>/exports/export_rituals.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-download me-1"></i> <?php echo __('export'); ?>
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="ritualsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th><?php echo __('id'); ?></th>
                                    <th><?php echo __('pilgrim'); ?></th>
                                    <th><?php echo __('type'); ?></th>
                                    <th><?php echo __('tawaf'); ?></th>
                                    <th><?php echo __('sai'); ?></th>
                                    <th><?php echo __('other_rituals'); ?></th>
                                    <th><?php echo __('started_at'); ?></th>
                                    <th><?php echo __('completed_at'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $ritual): ?>
                                <tr>
                                    <td><?php echo $ritual['id']; ?></td>
                                    <td><?php echo htmlspecialchars($ritual['full_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $ritual['ritual_type'] === 'hajj' ? 'success' : 'primary'; ?>">
                                            <?php echo $ritual['ritual_type']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?php echo $ritual['tawaf_rounds'] === 7 ? 'bg-success' : ''; ?>"user_type="progressbar" 
                                                style="width: <?php echo ($ritual['tawaf_rounds'] / 7) * 100; ?>%;" 
                                                aria-valuenow="<?php echo $ritual['tawaf_rounds']; ?>" aria-valuemin="0" aria-valuemax="7">
                                                <?php echo $ritual['tawaf_rounds']; ?>/7
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?php echo $ritual['sai_rounds'] === 7 ? 'bg-success' : ''; ?>"user_type="progressbar" 
                                                style="width: <?php echo ($ritual['sai_rounds'] / 7) * 100; ?>%;" 
                                                aria-valuenow="<?php echo $ritual['sai_rounds']; ?>" aria-valuemin="0" aria-valuemax="7">
                                                <?php echo $ritual['sai_rounds']; ?>/7
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($ritual['ritual_type'] === 'hajj'): ?>
                                        <div class="text-nowrap">
                                            <span class="badge bg-<?php echo $ritual['arafat_completed'] ? 'success' : 'secondary'; ?>">Arafat</span>
                                            <span class="badge bg-<?php echo $ritual['muzdalifah_completed'] ? 'success' : 'secondary'; ?>">Muzdalifah</span>
                                            <span class="badge bg-<?php echo $ritual['mina_completed'] ? 'success' : 'secondary'; ?>">Mina</span>
                                            <span class="badge bg-<?php echo $ritual['jamarat_completed'] ? 'success' : 'secondary'; ?>">Jamarat</span>
                                        </div>
                                        <?php elseif ($ritual['ritual_type'] === 'umrah'): ?>
                                        <span class="badge bg-<?php echo $ritual['halq_completed'] ? 'success' : 'secondary'; ?>">Halq</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($ritual['started_at']); ?></td>
                                    <td><?php echo $ritual['completed_at'] ? formatDate($ritual['completed_at']) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('ritual_types'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="position: relative; height:25vh;">
                                <canvas id="ritualTypesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('completion_rates'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo __('tawaf'); ?></span>
                                    <span>
                                        <?php 
                                        $tawafRate = $completionRates['tawaf']['total'] > 0 
                                            ? round(($completionRates['tawaf']['completed'] / $completionRates['tawaf']['total']) * 100, 2) 
                                            : 0;
                                        echo $tawafRate . '%'; 
                                        ?>
                                    </span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success"user_type="progressbar" style="width: <?php echo $tawafRate; ?>%;" 
                                         aria-valuenow="<?php echo $tawafRate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo __('sai'); ?></span>
                                    <span>
                                        <?php 
                                        $saiRate = $completionRates['sai']['total'] > 0 
                                            ? round(($completionRates['sai']['completed'] / $completionRates['sai']['total']) * 100, 2) 
                                            : 0;
                                        echo $saiRate . '%'; 
                                        ?>
                                    </span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success"user_type="progressbar" style="width: <?php echo $saiRate; ?>%;" 
                                         aria-valuenow="<?php echo $saiRate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><?php echo __('halq'); ?></span>
                                    <span>
                                        <?php 
                                        $halqRate = $completionRates['halq']['total'] > 0 
                                            ? round(($completionRates['halq']['completed'] / $completionRates['halq']['total']) * 100, 2) 
                                            : 0;
                                        echo $halqRate . '%'; 
                                        ?>
                                    </span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success"user_type="progressbar" style="width: <?php echo $halqRate; ?>%;" 
                                         aria-valuenow="<?php echo $halqRate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold"><?php echo __('ritual_statistics'); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('total_rituals'); ?></div>
                                    <div class="h5"><?php echo number_format(count($reportData)); ?></div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('completed_rituals'); ?></div>
                                    <div class="h5">
                                        <?php
                                        $completed = 0;
                                        foreach ($reportData as $ritual) {
                                            if ($ritual['completed_at']) {
                                                $completed++;
                                            }
                                        }
                                        echo number_format($completed);
                                        ?>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('hajj_rituals'); ?></div>
                                    <div class="h5">
                                        <?php
                                        $hajjCount = 0;
                                        foreach ($ritualTypes as $type) {
                                            if ($type['ritual_type'] === 'hajj') {
                                                $hajjCount = $type['count'];
                                                break;
                                            }
                                        }
                                        echo number_format($hajjCount);
                                        ?>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted"><?php echo __('umrah_rituals'); ?></div>
                                    <div class="h5">
                                        <?php
                                        $umrahCount = 0;
                                        foreach ($ritualTypes as $type) {
                                            if ($type['ritual_type'] === 'umrah') {
                                                $umrahCount = $type['count'];
                                                break;
                                            }
                                        }
                                        echo number_format($umrahCount);
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- JavaScript for Data Tables and Charts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables
    if (document.getElementById('usersTable')) {
        new DataTable('#usersTable', {
            order: [[5, 'desc']],
            pageLength: 10,
            language: {
                searchPlaceholder: "<?php echo __('search_users'); ?>"
            }
        });
    }
    
    if (document.getElementById('emergenciesTable')) {
        new DataTable('#emergenciesTable', {
            order: [[5, 'desc']],
            pageLength: 10,
            language: {
                searchPlaceholder: "<?php echo __('search_emergencies'); ?>"
            }
        });
    }
    
    if (document.getElementById('servicesTable')) {
        new DataTable('#servicesTable', {
            order: [[5, 'desc']],
            pageLength: 10,
            language: {
                searchPlaceholder: "<?php echo __('search_services'); ?>"
            }
        });
    }
    
    if (document.getElementById('groupsTable')) {
        new DataTable('#groupsTable', {
            order: [[4, 'desc']],
            pageLength: 10,
            language: {
                searchPlaceholder: "<?php echo __('search_groups'); ?>"
            }
        });
    }
    
    if (document.getElementById('ritualsTable')) {
        new DataTable('#ritualsTable', {
            order: [[6, 'desc']],
            pageLength: 10,
            language: {
                searchPlaceholder: "<?php echo __('search_rituals'); ?>"
            }
        });
    }
    
    // Chart.js - Initialize charts based on report type
    <?php if ($reportType === 'summary'): ?>
    // Activity Chart
    if (document.getElementById('activityChart')) {
        const activityData = <?php echo json_encode($activityByDay); ?>;
        
        new Chart(document.getElementById('activityChart'), {
            type: 'line',
            data: {
                labels: activityData.map(item => item.date),
                datasets: [
                    {
                        label: '<?php echo __('users'); ?>',
                        data: activityData.map(item => item.user_count),
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: '<?php echo __('emergencies'); ?>',
                        data: activityData.map(item => item.emergency_count),
                        borderColor: '#e74a3b',
                        backgroundColor: 'rgba(231, 74, 59, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: '<?php echo __('services'); ?>',
                        data: activityData.map(item => item.service_count),
                        borderColor: '#1cc88a',
                        backgroundColor: 'rgba(28, 200, 138, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: '<?php echo __('locations'); ?>',
                        data: activityData.map(item => item.location_count > 1000 ? 1000 : item.location_count), // Cap for scale
                        borderColor: '#36b9cc',
                        backgroundColor: 'rgba(54, 185, 204, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: '<?php echo __('activity_over_time'); ?>'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Nationalities Chart
    if (document.getElementById('nationalitiesChart')) {
        const nationalitiesData = <?php echo json_encode($userNationalities); ?>;
        
        new Chart(document.getElementById('nationalitiesChart'), {
            type: 'doughnut',
            data: {
                labels: nationalitiesData.map(item => item.nationality),
                datasets: [
                    {
                        data: nationalitiesData.map(item => item.count),
                        backgroundColor: [
                            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                            '#5a5c69', '#858796', '#8BC34A', '#FF9800', '#9C27B0'
                        ]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12
                        }
                    }
                }
            }
        });
    }
    
    // Response Times Chart
    if (document.getElementById('responseTimesChart')) {
        const responseTimesData = <?php echo json_encode($emergencyResponseTimes); ?>;
        
        new Chart(document.getElementById('responseTimesChart'), {
            type: 'bar',
            data: {
                labels: responseTimesData.map(item => item.time_category),
                datasets: [
                    {
                        label: '<?php echo __('emergencies'); ?>',
                        data: responseTimesData.map(item => item.count),
                        backgroundColor: [
                            '#1cc88a', '#4e73df', '#f6c23e', '#e74a3b', '#5a5c69'
                        ]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    <?php if ($reportType === 'users'): ?>
    // Useruser_types Chart
    if (document.getElementById('userRolesChart')) {
        const userRolesData = <?php echo json_encode($userRoles); ?>;
        
        new Chart(document.getElementById('userRolesChart'), {
            type: 'pie',
            data: {
                labels: userRolesData.map(item => item.user_type),
                datasets: [
                    {
                        data: userRolesData.map(item => item.count),
                        backgroundColor: [
                            '#4e73df', '#1cc88a', '#e74a3b', '#f6c23e'
                        ]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12
                        }
                    }
                }
            }
        });
    }
    
    // Registrations Chart
    if (document.getElementById('registrationsChart')) {
        const registrationsData = <?php echo json_encode($userRegistrationsByDay); ?>;
        
        new Chart(document.getElementById('registrationsChart'), {
            type: 'bar',
            data: {
                labels: registrationsData.map(item => item.date),
                datasets: [
                    {
                        label: '<?php echo __('new_users'); ?>',
                        data: registrationsData.map(item => item.count),
                        backgroundColor: '#4e73df'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    <?php if ($reportType === 'emergencies'): ?>
    // Emergency Types Chart
    if (document.getElementById('emergencyTypesChart')) {
        const emergencyTypesData = <?php echo json_encode($emergencyTypes); ?>;
        
        new Chart(document.getElementById('emergencyTypesChart'), {
            type: 'doughnut',
            data: {
                labels: emergencyTypesData.map(item => item.type),
                datasets: [
                    {
                        data: emergencyTypesData.map(item => item.count),
                        backgroundColor: [
                            '#e74a3b', '#f6c23e', '#5a5c69', '#4e73df'
                        ]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12
                        }
                    }
                }
            }
        });
    }
    
    // Emergencies Map (you need to fetch coordinates for this)
    if (document.getElementById('emergenciesMap')) {
        // Initialize map centered on Mecca
        const map = L.map('emergenciesMap').setView([21.4225, 39.8262], 13);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Add Kaaba marker
        L.marker([21.4225, 39.8262], {
            icon: L.divIcon({
                html: '<i class="fas fa-kaaba"></i>',
                className: 'kaaba-marker-icon',
                iconSize: [36, 36],
                iconAnchor: [18, 36],
                popupAnchor: [0, -36]
            })
        }).addTo(map).bindPopup("<?php echo __('kaaba'); ?>");
        
        // Load emergency coordinates from API (in real app)
        // For now, add sample markers
        <?php
        // Fetch a few emergency locations for the map
        $emergencyLocations = fetchAll("
            SELECT e.id, e.latitude, e.longitude, e.type, u.full_name
            FROM emergencies e
            JOIN users u ON e.reporter_id = u.id
            WHERE e.latitude IS NOT NULL AND e.longitude IS NOT NULL
            AND e.created_at BETWEEN ? AND ?
            LIMIT 10
        ", [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        
        foreach ($emergencyLocations as $location):
        if (!empty($location['latitude']) && !empty($location['longitude'])):
        ?>
        L.marker([<?php echo $location['latitude']; ?>, <?php echo $location['longitude']; ?>], {
            icon: L.divIcon({
                html: '<i class="fas fa-<?php echo $location['type'] === 'medical' ? 'ambulance' : ($location['type'] === 'missing_person' ? 'user-slash' : 'exclamation-triangle'); ?>"></i>',
                className: 'emergency-marker-icon emergency-<?php echo $location['type']; ?>',
                iconSize: [36, 36],
                iconAnchor: [18, 36],
                popupAnchor: [0, -36]
            })
        }).addTo(map).bindPopup("ID: <?php echo $location['id']; ?><br>Type: <?php echo $location['type']; ?><br>Reporter: <?php echo htmlspecialchars($location['full_name']); ?>");
        <?php
        endif;
        endforeach;
        ?>
    }
    <?php endif; ?>
    
    <?php if ($reportType === 'services'): ?>
    // Service Types Chart
    if (document.getElementById('serviceTypesChart')) {
        const serviceTypesData = <?php echo json_encode($serviceTypes); ?>;
        
        new Chart(document.getElementById('serviceTypesChart'), {
            type: 'doughnut',
            data: {
                labels: serviceTypesData.map(item => item.service_type),
                datasets: [
                    {
                        data: serviceTypesData.map(item => item.count),
                        backgroundColor: [
                            '#4e73df', '#1cc88a', '#f6c23e', '#e74a3b', '#5a5c69'
                        ]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12
                        }
                    }
                }
            }
        });
    }
    
    // Service Requests Chart
    if (document.getElementById('serviceRequestsChart')) {
        const serviceRequestsData = <?php echo json_encode($serviceRequestsByDay); ?>;
        
        new Chart(document.getElementById('serviceRequestsChart'), {
            type: 'bar',
            data: {
                labels: serviceRequestsData.map(item => item.date),
                datasets: [
                    {
                        label: '<?php echo __('service_requests'); ?>',
                        data: serviceRequestsData.map(item => item.count),
                        backgroundColor: '#1cc88a'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    <?php if ($reportType === 'locations'): ?>
    // Heat Map
    if (document.getElementById('heatMap')) {
        // Initialize map centered on Mecca
        const map = L.map('heatMap').setView([21.4225, 39.8262], 13);
        
        // Add tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Add Kaaba marker
        L.marker([21.4225, 39.8262], {
            icon: L.divIcon({
                html: '<i class="fas fa-kaaba"></i>',
                className: 'kaaba-marker-icon',
                iconSize: [36, 36],
                iconAnchor: [18, 36],
                popupAnchor: [0, -36]
            })
        }).addTo(map).bindPopup("<?php echo __('kaaba'); ?>");
        
        // Add heat map layer
        const heatMapData = <?php echo json_encode($heatMapData); ?>;
        const points = heatMapData.map(item => [item.lat, item.lng, item.weight / 10]); // Scale weight
        
        L.heatLayer(points, {
            radius: 25,
            blur: 15,
            maxZoom: 17,
            gradient: {
                0.4: 'blue',
                0.6: 'cyan',
                0.7: 'lime',
                0.8: 'yellow',
                1.0: 'red'
            }
        }).addTo(map);
        
        // Add markers for most active areas
        const activeAreas = <?php echo json_encode($activeAreas); ?>;
        
        activeAreas.forEach((area, index) => {
            L.marker([area.lat, area.lng], {
                icon: L.divIcon({
                    html: `<span>${index + 1}</span>`,
                    className: 'hotspot-marker',
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                })
            }).addTo(map).bindPopup(`Hotspot #${index + 1}<br>Count: ${area.count}<br>Coordinates: ${area.lat}, ${area.lng}`);
        });
    }
    
    // Location Updates by Hour Chart
    if (document.getElementById('locationUpdatesChart')) {
        const locationUpdatesData = <?php echo json_encode($locationUpdatesByHour); ?>;
        
        new Chart(document.getElementById('locationUpdatesChart'), {
            type: 'bar',
            data: {
                labels: locationUpdatesData.map(item => `${item.hour}:00`),
                datasets: [
                    {
                        label: '<?php echo __('location_updates'); ?>',
                        data: locationUpdatesData.map(item => item.count),
                        backgroundColor: '#36b9cc'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    <?php if ($reportType === 'groups'): ?>
    // Groups by Day Chart
    if (document.getElementById('groupsByDayChart')) {
        const groupsByDayData = <?php echo json_encode($groupsByDay); ?>;
        
        new Chart(document.getElementById('groupsByDayChart'), {
            type: 'bar',
            data: {
                labels: groupsByDayData.map(item => item.date),
                datasets: [
                    {
                        label: '<?php echo __('new_groups'); ?>',
                        data: groupsByDayData.map(item => item.count),
                        backgroundColor: '#4e73df'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Group Size Chart
    if (document.getElementById('groupSizeChart')) {
        const groupSizesData = <?php echo json_encode($groupSizes); ?>;
        
        new Chart(document.getElementById('groupSizeChart'), {
            type: 'pie',
            data: {
                labels: groupSizesData.map(item => item.size_category),
                datasets: [
                    {
                        data: groupSizesData.map(item => item.count),
                        backgroundColor: [
                            '#1cc88a', '#4e73df', '#f6c23e', '#e74a3b'
                        ]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
    
    <?php if ($reportType === 'rituals'): ?>
    // Ritual Types Chart
    if (document.getElementById('ritualTypesChart')) {
        const ritualTypesData = <?php echo json_encode($ritualTypes); ?>;
        
        new Chart(document.getElementById('ritualTypesChart'), {
            type: 'pie',
            data: {
                labels: ritualTypesData.map(item => item.ritual_type),
                datasets: [
                    {
                        data: ritualTypesData.map(item => item.count),
                        backgroundColor: [
                            '#1cc88a', '#4e73df'
                        ]
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>

<!-- Custom CSS for this page -->
<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}

.kaaba-marker-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border-radius: 50%;
    border: 2px solid #21618C;
    width: 36px !important;
    height: 36px !important;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.kaaba-marker-icon i {
    color: #21618C;
    font-size: 18px;
}

.emergency-marker-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border-radius: 50%;
    width: 36px !important;
    height: 36px !important;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.emergency-medical {
    border: 2px solid #e74a3b;
}

.emergency-medical i {
    color: #e74a3b;
    font-size: 18px;
}

.emergency-missing_person {
    border: 2px solid #f6c23e;
}

.emergency-missing_person i {
    color: #f6c23e;
    font-size: 18px;
}

.emergency-security {
    border: 2px solid #5a5c69;
}

.emergency-security i {
    color: #5a5c69;
    font-size: 18px;
}

.hotspot-marker {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #e74a3b;
    color: white;
    border-radius: 50%;
    width: 30px !important;
    height: 30px !important;
    font-weight: bold;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}
</style>

<?php
// Include footer
include_once('../includes/footer.php');
?>