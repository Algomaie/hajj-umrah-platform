<?php
// Include required files
require_once $_SERVER['DOCUMENT_ROOT'] . '/hajj-umrah-platform/includes/functions.php';

// Start session
// ../includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Clear flash message persistence
if (function_exists('clearFlashKeep')) {
    clearFlashKeep();
}

// Define restricted paths that require login
$restrictedPaths = [
    '/services/',
    '/rituals/',
    '/maps/',
    '/education/',
    '/emergency/',
    '/dashboard/'
];

// Check if the current request is for a restricted path
$currentPath = $_SERVER['PHP_SELF'];
$isRestricted = false;
foreach ($restrictedPaths as $path) {
    if (strpos($currentPath, $path) !== false) {
        $isRestricted = true;
        break;
    }
}

// Redirect to homepage if user is not logged in and trying to access restricted path
if ($isRestricted && !isLoggedIn()) {
    header('Location: ' . SITE_URL);
    exit;
}

// Handle language change with input sanitization
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ar', 'en'])) {
    setLanguage($_GET['lang']);
}

// Get current language and direction
$lang = getCurrentLanguage();
$dir = $lang === 'ar' ? 'rtl' : 'ltr';
$otherLang = $lang === 'ar' ? 'en' : 'ar';
$otherLangText = $lang === 'ar' ? 'English' : 'العربية';

// Get current user if logged in
$currentUser = isLoggedIn() ? getCurrentUser() : null;

// Define user permissions based on user_type
$permissions = [
    'admin' => [
        'dashboard' => true,
        'services' => true, // Full management
        'rituals' => true,
        'maps' => true,
        'education' => true,
        'emergency' => true, // Full management
        'profile' => true,
        'logout' => true
    ],
    'pilgrim' => [
        'dashboard' => true,
        'services' => true, // Can request services
        'rituals' => true,
        'maps' => true,
        'education' => true,
        'emergency' => true, // Can report emergencies
        'profile' => true,
        'logout' => true
    ],
    'guardian' => [
        'dashboard' => true,
        'services' => true, // Can request services for group
        'rituals' => true,
        'maps' => true,
        'education' => true,
        'emergency' => true, // Can report emergencies
        'profile' => true,
        'logout' => true
    ],
    'authority' => [
        'dashboard' => true,
        'services' => true, // Can view and manage service requests
        'rituals' => false,
        'maps' => true,
        'education' => false,
        'emergency' => true, // Can manage emergencies
        'profile' => true,
        'logout' => true
    ],
    'guest' => [
        'dashboard' => false,
        'services' => false,
        'rituals' => false,
        'maps' => false,
        'education' => false,
        'emergency' => false,
        'profile' => false,
        'logout' => false
    ]
];

// Get user permissions
$userType = $currentUser['user_type'] ?? 'guest';
$userPermissions = $permissions[$userType];
error_log("User type: $userType, Permissions: " . json_encode($userPermissions));

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>" dir="<?php echo $dir; ?>" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php
        echo isset($pageTitle)
            ? htmlspecialchars($pageTitle) . ' - ' . __('site_name')
            : __('site_name');
        ?>
    </title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap RTL for Arabic -->
    <?php if ($lang === 'ar'): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" integrity="sha384-PJsj/BTMn3J6eC1W0P1CVAf/GnvsX8iQfB0nVwez1HMcBIdxh3NfxTyOQH4SrZ3O" crossorigin="anonymous">
    <?php endif; ?>

    <!-- Leaflet CSS for maps (if needed) -->
    <?php if (isset($useLeaflet) && $useLeaflet): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
    <?php endif; ?>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">

    <style>
        :root {
            --primary-color: #005F73;
            --secondary-color: #0A9396;
            --accent-color: #EE9B00;
            --danger-color: #AE2012;
            --success-color: #2B9348;
            --background-color: #f8f9fa;
            --card-bg: #ffffff;
            --text-color: #212529;
            --border-color: #dee2e6;
            --input-bg: #ffffff;
            --nav-bg: #0E4064;
            --nav-text: #ffffff;
            --shadow-color: rgba(0, 0, 0, 0.1);
        }

        [data-bs-theme="dark"] {
            --background-color: #121212;
            --card-bg: #1e1e1e;
            --text-color: #f8f9fa;
            --border-color: #343a40;
            --input-bg: #2b3035;
            --nav-bg: #1a2533;
            --nav-text: #f8f9fa;
            --shadow-color: rgba(0, 0, 0, 0.5);
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            transition: background-color 0.3s ease, color 0.3s ease;
            padding-top: 70px;
            font-family: <?php echo $lang === 'ar' ? "'Cairo', sans-serif" : "'Roboto', sans-serif"; ?>;
        }

        .card {
            background-color: var(--card-bg);
            border-color: var(--border-color);
            box-shadow: 0 4px 6px var(--shadow-color);
        }

        .navbar-dark {
            background-color: var(--nav-bg);
        }

        .navbar-dark .navbar-nav .nav-link {
            color: var(--nav-text);
        }

        .form-control, .form-select {
            background-color: var(--input-bg);
            border-color: var(--border-color);
            color: var(--text-color);
        }

        .theme-toggle-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: transparent;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--nav-text);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .theme-toggle-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .theme-toggle-btn:active {
            transform: scale(0.95);
        }
    </style>

    <!-- Additional CSS (if provided) -->
    <?php if (isset($extraCSS)): ?>
        <?php echo $extraCSS; ?>
    <?php endif; ?>
</head>
<body class="<?php echo $lang === 'ar' ? 'arabic-font' : ''; ?> <?php echo isset($bodyClass) ? htmlspecialchars($bodyClass) : ''; ?>">
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
       <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <img src="<?php echo SITE_URL; ?>/assets/images/logo.ico" alt="<?php echo __('site_name'); ?>" class="me-2" style="width: 44px; height: 44px;">
                <?php echo __('site_name'); ?>
            </a>


            <!-- Theme Toggle Button -->
            <div class="theme-toggle-container ms-auto ms-lg-3 me-2 order-lg-3">
                <button id="themeToggle" class="theme-toggle-btn" aria-label="<?php echo __('toggle_theme'); ?>">
                    <i class="fas fa-moon"></i>
                </button>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="<?php echo __('toggle_navigation'); ?>">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Main Navigation Links -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>">
                            <i class="fas fa-home"></i> <?php echo __('home'); ?>
                        </a>
                    </li>

                    <!-- Dashboard -->
                    <?php if ($userPermissions['dashboard']): ?>
                        <li class="nav-item">
                            <?php
                            $dashboardUrl = match ($userType) {
                                'admin' => SITE_URL . '/admin/dashboard.php',
                                'pilgrim' => SITE_URL . '/dashboard/pilgrim.php',
                                'guardian' => SITE_URL . '/dashboard/guardian.php',
                                'authority' => SITE_URL . '/dashboard/authority.php',
                                default => SITE_URL . '/dashboard/pilgrim.php',
                            };
                            $isDashboardActive = strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false || strpos($_SERVER['PHP_SELF'], '/admin/dashboard.php') !== false;
                            ?>
                            <a class="nav-link <?php echo $isDashboardActive ? 'active' : ''; ?>" 
                               href="<?php echo htmlspecialchars($dashboardUrl); ?>?t=<?php echo time(); ?>" 
                               onclick="console.log('Navigating to: <?php echo $dashboardUrl; ?>')">
                                <i class="fas fa-tachometer-alt"></i> <?php echo __('dashboard'); ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Services Dropdown -->
                    <?php if ($userPermissions['services']): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['PHP_SELF'], '/services/') !== false ? 'active' : ''; ?>" 
                               href="#" id="servicesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-hands-helping"></i> <?php echo __('services'); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="servicesDropdown">
                                <?php if (in_array($userType, ['pilgrim', 'guardian'])): ?>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/services/tracking.php"><i class="fas fa-map-marker-alt"></i> <?php echo __('tracking_service'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/services/missing_report.php"><i class="fas fa-user-minus"></i> <?php echo __('missing_person'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/services/medical_help.php"><i class="fas fa-medkit"></i> <?php echo __('medical_service'); ?></a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/services/cart_service.php"><i class="fas fa-wheelchair"></i> <?php echo __('cart_service'); ?></a></li>
                                <?php endif; ?>
                                <?php if (in_array($userType, ['authority', 'admin'])): ?>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/services/manage_requests.php"><i class="fas fa-tasks"></i> <?php echo __('manage_service_requests'); ?></a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Rituals Dropdown -->
                    <?php if ($userPermissions['rituals']): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['PHP_SELF'], '/rituals/') !== false ? 'active' : ''; ?>" 
                               href="#" id="ritualsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-pray"></i> <?php echo __('rituals'); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="ritualsDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/rituals/tawaf_guide.php"><i class="fas fa-circle-notch"></i> <?php echo __('tawaf_guide'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/rituals/sai_guide.php"><i class="fas fa-walking"></i> <?php echo __('sai_guide'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/rituals/arafat_guide.php"><i class="fas fa-mountain"></i> <?php echo __('arafat_guide'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/rituals/other_rituals.php"><i class="fas fa-book"></i> <?php echo __('other_rituals'); ?></a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Maps Dropdown -->
                    <?php if ($userPermissions['maps']): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['PHP_SELF'], '/maps/') !== false ? 'active' : ''; ?>" 
                               href="#" id="mapsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-map"></i> <?php echo __('maps'); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="mapsDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/maps/interactive_map.php"><i class="fas fa-map-marked-alt"></i> <?php echo __('interactive_map'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/maps/qibla_direction.php"><i class="fas fa-compass"></i> <?php echo __('qibla_direction'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/maps/location_share.php"><i class="fas fa-share-alt"></i> <?php echo __('location_sharing'); ?></a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Education Dropdown -->
                    <?php if ($userPermissions['education']): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['PHP_SELF'], '/education/') !== false ? 'active' : ''; ?>" 
                               href="#" id="educationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-book-open"></i> <?php echo __('education'); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="educationDropdown">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/education/ritual_guides.php"><i class="fas fa-scroll"></i> <?php echo __('ritual_guides'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/education/prayers.php"><i class="fas fa-hands"></i> <?php echo __('prayers'); ?></a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/education/faqs.php"><i class="fas fa-question-circle"></i> <?php echo __('faqs'); ?></a></li>
                            </ul>
                        </li>
                    <?php endif; ?>

                    <!-- Emergency Link -->
                    <?php if ($userPermissions['emergency']): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/emergency/') !== false ? 'active' : ''; ?>" 
                               href="<?php echo in_array($userType, ['authority', 'admin']) ? SITE_URL . '/emergency/missing_persons.php' : SITE_URL . '/emergency/missing_persons.php'; ?>">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo __('emergency'); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <!-- User and Language Controls -->
                <ul class="navbar-nav ms-auto">
                    <!-- Language Switcher -->
                    <li class="nav-item mx-2">
                        <div class="btn-group" role="group" aria-label="<?php echo __('language_switcher'); ?>">
                            <a href="?lang=ar" class="btn btn-sm <?php echo $lang === 'ar' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                العربية
                            </a>
                            <a href="?lang=en" class="btn btn-sm <?php echo $lang === 'en' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                English
                            </a>
                        </div>
                    </li>

                    <!-- User Menu -->
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['full_name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <?php if ($userPermissions['profile']): ?>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/auth/profile.php"><i class="fas fa-user"></i> <?php echo __('profile'); ?></a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <?php if ($userPermissions['logout']): ?>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?></a></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'login.php' ? 'active' : ''; ?>" 
                               href="<?php echo SITE_URL; ?>/auth/login.php">
                                <i class="fas fa-sign-in-alt"></i> <?php echo __('login'); ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'register.php' ? 'active' : ''; ?>" 
                               href="<?php echo SITE_URL; ?>/auth/register.php">
                                <i class="fas fa-user-plus"></i> <?php echo __('register'); ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if ($flash = getFlashMessage()): ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo htmlspecialchars($flash['type']); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?php echo __('close'); ?>"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="py-4">

    <!-- Theme Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon = themeToggle.querySelector('i');
            const htmlElement = document.documentElement;
            const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');

            // Get saved theme or default to 'auto'
            let currentTheme = localStorage.getItem('theme') || 'auto';
            applyTheme(currentTheme);

            // Toggle theme on button click
            themeToggle.addEventListener('click', () => {
                currentTheme = currentTheme === 'auto' ? 'light' : currentTheme === 'light' ? 'dark' : 'auto';
                localStorage.setItem('theme', currentTheme);
                applyTheme(currentTheme);
            });

            // Apply theme
            function applyTheme(theme) {
                if (theme === 'auto') {
                    htmlElement.setAttribute('data-bs-theme', prefersDarkScheme.matches ? 'dark' : 'light');
                } else {
                    htmlElement.setAttribute('data-bs-theme', theme);
                }
                updateIcon(theme);
            }

            // Update theme icon
            function updateIcon(theme) {
                themeIcon.className = 'fas ' + (
                    theme === 'auto' ? 'fa-adjust' :
                    htmlElement.getAttribute('data-bs-theme') === 'dark' ? 'fa-sun' : 'fa-moon'
                );
            }

            // Listen for system theme changes
            prefersDarkScheme.addEventListener('change', () => {
                if (localStorage.getItem('theme') === 'auto') {
                    applyTheme('auto');
                }
            });
        });
    </script>