
<?php
// Include required files
require_once($_SERVER['DOCUMENT_ROOT'] . '/hajj-umrah-platform/includes/functions.php');

// Start session
startSession();

// Check if language is being changed
if (isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
}

// Get current language
$lang = getCurrentLanguage();
$dir = $lang === 'ar' ? 'rtl' : 'ltr';
$otherLang = $lang === 'ar' ? 'en' : 'ar';
$otherLangText = $lang === 'ar' ? 'English' : 'العربية';

// Get current user
$currentUser = isLoggedIn() ? getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . __('site_name') : __('site_name'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <?php if ($lang === 'ar'): ?>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <?php endif; ?>
    
    <?php if (isset($useLeaflet) && $useLeaflet): ?>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <?php endif; ?>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <?php if (isset($extraCSS)): ?>
    <!-- Extra CSS -->
    <?php echo $extraCSS; ?>
    <?php endif; ?>
</head>
<body class="<?php echo $lang === 'ar' ? 'arabic-font' : ''; ?> <?php echo isset($bodyClass) ? $bodyClass : ''; ?>">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
            <img src="../assets/images/" alt="Site Icon" class="me-2" style="width: 44px; height: 44px;"> 
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>">
                            <i class="fas fa-home"></i> <?php echo __('home'); ?>
                        </a>
                    </li>
                    
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/dashboard/pilgrim.php">
                            <i class="fas fa-tachometer-alt"></i> <?php echo __('dashboard'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['PHP_SELF'], '/services/') !== false ? 'active' : ''; ?>" href="#" id="servicesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-hands-helping"></i> <?php echo __('services'); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="servicesDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/services/tracking.php"><i class="fas fa-map-marker-alt"></i> <?php echo __('tracking_service'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/services/missing_report.php"><i class="fas fa-user-minus"></i> <?php echo __('missing_person'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/services/medical_help.php"><i class="fas fa-medkit"></i> <?php echo __('medical_service'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/services/cart_service.php"><i class="fas fa-wheelchair"></i> <?php echo __('cart_service'); ?></a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['PHP_SELF'], '/rituals/') !== false ? 'active' : ''; ?>" href="#" id="ritualsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-pray"></i> <?php echo __('rituals'); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="ritualsDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/rituals/tawaf_guide.php"><i class="fas fa-circle-notch"></i> <?php echo __('tawaf_guide'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/rituals/sai_guide.php"><i class="fas fa-walking"></i> <?php echo __('sai_guide'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/rituals/arafat_guide.php"><i class="fas fa-mountain"></i> <?php echo __('arafat_guide'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/rituals/other_rituals.php"><i class="fas fa-book"></i> <?php echo __('other_rituals'); ?></a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['PHP_SELF'], '/maps/') !== false ? 'active' : ''; ?>" href="#" id="mapsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-map"></i> <?php echo __('maps'); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="mapsDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/maps/interactive_map.php"><i class="fas fa-map-marked-alt"></i> <?php echo __('interactive_map'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/maps/qibla_direction.php"><i class="fas fa-compass"></i> <?php echo __('qibla_direction'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/maps/location_share.php"><i class="fas fa-share-alt"></i> <?php echo __('location_sharing'); ?></a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo strpos($_SERVER['PHP_SELF'], '/education/') !== false ? 'active' : ''; ?>" href="#" id="educationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-book-open"></i> <?php echo __('education'); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="educationDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/education/ritual_guides.php"><i class="fas fa-scroll"></i> <?php echo __('ritual_guides'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/education/prayers.php"><i class="fas fa-hands"></i> <?php echo __('prayers'); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/education/faqs.php"><i class="fas fa-question-circle"></i> <?php echo __('faqs'); ?></a></li>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/emergency') !== false ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/services/missing_report.php">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo __('emergency'); ?>
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav ms-auto">
                <li class="nav-item mx-2">
    <div class="btn-group" role="group" aria-label="Language switcher">
        <a href="?lang=ar" class="btn btn-sm <?php echo $lang === 'ar' ? 'btn-primary' : 'btn-outline-primary'; ?>">
            العربية
        </a>
        <a href="?lang=en" class="btn btn-sm <?php echo $lang === 'en' ? 'btn-primary' : 'btn-outline-primary'; ?>">
        English
        </a>
    </div>
</li>

                    
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?php echo $currentUser['full_name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/auth/profile.php"><i class="fas fa-user"></i> <?php echo __('profile'); ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?></a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'login.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/auth/login.php"><i class="fas fa-sign-in-alt"></i> <?php echo __('login'); ?></a>
                    </li>
                    <li class="nav-item">
    <a class="nav-link " href="<?php echo SITE_URL; ?>/auth/logout.php">
        <i class="fas fa-sign-out-alt"></i> <?php echo __('logout'); ?>
    </a>
</li>


                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Flash Messages -->
    <?php $flash = getFlashMessage(); ?>
    <?php if ($flash): ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $flash['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="py-4">