<?php
// Include header
require_once 'includes/header.php';

// Set page title
$pageTitle = __('home');

// Define restricted paths for access control
$restrictedPaths = ['/services/', '/rituals/', '/maps/', '/education/', '/emergency/', '/dashboard/'];
?>

<!-- Hero Section -->
<section class="hero position-relative text-white bg-dark py-5">
    <div class="hero-bg-overlay"></div>
    <div class="container py-5 position-relative">
        <div class="row">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4"><?php echo __('welcome_message'); ?></h1>
                <p class="lead mb-5"><?php echo __('platform_description'); ?></p>
                <div class="d-flex flex-column flex-sm-row gap-3">
                    <?php if (!isLoggedIn()): ?>
                        <a href="<?php echo htmlspecialchars(SITE_URL); ?>/auth/login.php" class="btn btn-primary btn-lg px-4"><?php echo __('login'); ?></a>
                        <a href="<?php echo htmlspecialchars(SITE_URL); ?>/auth/register.php" class="btn btn-outline-light btn-lg px-4"><?php echo __('register'); ?></a>
                    <?php else: ?>
                        <a href="<?php echo htmlspecialchars(SITE_URL); ?>/dashboard/pilgrim.php" class="btn btn-primary btn-lg px-4"><?php echo __('dashboard'); ?></a>
                        <a href="<?php echo htmlspecialchars(SITE_URL); ?>/maps/interactive_map.php" class="btn btn-outline-light btn-lg px-4"><?php echo __('explore_map'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="py-5">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3"><?php echo __('our_services'); ?></h2>
            <p class="text-muted lead mx-auto" style="max-width: 800px"><?php echo __('services_description'); ?></p>
        </div>
        <div class="row g-4">
            <?php
            $services = [
                ['icon' => 'map-marker-alt', 'title' => __('tracking_service'), 'desc' => __('tracking_description'), 'url' => '/services/tracking.php'],
                ['icon' => 'user-minus', 'title' => __('missing_person'), 'desc' => __('missing_description'), 'url' => '/services/missing_report.php'],
                ['icon' => 'medkit', 'title' => __('medical_service'), 'desc' => __('medical_description'), 'url' => '/services/medical_help.php'],
                ['icon' => 'wheelchair', 'title' => __('cart_service'), 'desc' => __('cart_description'), 'url' => '/services/cart_service.php'],
            ];
            foreach ($services as $service):
            ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm hover-card">
                        <div class="card-body text-center p-4">
                            <div class="rounded-circle bg-primary-subtle mx-auto mb-4 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-<?php echo $service['icon']; ?> fa-2x text-primary" aria-hidden="true"></i>
                            </div>
                            <h3 class="h5 fw-bold mb-3"><?php echo $service['title']; ?></h3>
                            <p class="text-muted mb-4"><?php echo $service['desc']; ?></p>
                            <a href="<?php echo isLoggedIn() ? htmlspecialchars(SITE_URL . $service['url']) : htmlspecialchars(SITE_URL . '/auth/login.php'); ?>" 
                               class="btn btn-outline-primary stretched-link"
                               <?php if (!isLoggedIn()): ?>onclick="setFlashMessage('warning', '<?php echo __('please_login'); ?>')"<?php endif; ?>>
                                <?php echo __('learn_more'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Ritual Guides Section -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3"><?php echo __('ritual_guides'); ?></h2>
            <p class="text-muted lead mx-auto" style="max-width: 800px"><?php echo __('rituals_description'); ?></p>
        </div>
        <div class="row g-4">
            <?php
            $rituals = [
                ['img' => 'tawaf.jpg', 'title' => __('tawaf_guide'), 'desc' => __('tawaf_description'), 'url' => '/rituals/tawaf_guide.php'],
                ['img' => 'sai.jpg', 'title' => __('sai_guide'), 'desc' => __('sai_description'), 'url' => '/rituals/sai_guide.php'],
                ['img' => 'arafat.jpg', 'title' => __('arafat_guide'), 'desc' => __('arafat_description'), 'url' => '/rituals/arafat_guide.php'],
                ['img' => 'other_rituals.jpg', 'title' => __('other_rituals'), 'desc' => __('other_rituals_description'), 'url' => '/rituals/other_rituals.php'],
            ];
            foreach ($rituals as $ritual):
            ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <img src="<?php echo htmlspecialchars(SITE_URL . '/assets/images/' . $ritual['img']); ?>" 
                             class="card-img-top object-fit-cover" 
                             style="height: 180px;" 
                             alt="<?php echo $ritual['title']; ?>" 
                             loading="lazy">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3"><?php echo $ritual['title']; ?></h5>
                            <p class="text-muted mb-4"><?php echo $ritual['desc']; ?></p>
                            <a href="<?php echo isLoggedIn() ? htmlspecialchars(SITE_URL . $ritual['url']) : htmlspecialchars(SITE_URL . '/auth/login.php'); ?>" 
                               class="btn btn-primary stretched-link"
                               <?php if (!isLoggedIn()): ?>onclick="setFlashMessage('warning', '<?php echo __('please_login'); ?>')"<?php endif; ?>>
                                <?php echo __('view_guide'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Interactive Map Section -->
<section class="py-5">
    <div class="container py-4">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <h2 class="fw-bold mb-4"><?php echo __('interactive_map'); ?></h2>
                <p class="lead mb-4"><?php echo __('map_description'); ?></p>
                <ul class="list-unstyled mb-5">
                    <?php
                    $map_features = [__('map_feature_1'), __('map_feature_2'), __('map_feature_3'), __('map_feature_4')];
                    foreach ($map_features as $feature):
                    ?>
                        <li class="d-flex align-items-center mb-3">
                            <span class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" style="width: 28px; height: 28px;">
                                <i class="fas fa-check" aria-hidden="true"></i>
                            </span>
                            <span><?php echo $feature; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?php echo isLoggedIn() ? htmlspecialchars(SITE_URL . '/maps/interactive_map.php') : htmlspecialchars(SITE_URL . '/auth/login.php'); ?>" 
                   class="btn btn-primary btn-lg px-4"
                   <?php if (!isLoggedIn()): ?>onclick="setFlashMessage('warning', '<?php echo __('please_login'); ?>')"<?php endif; ?>>
                    <?php echo __('explore_map'); ?>
                </a>
            </div>
            <div class="col-lg-6">
                <img src="<?php echo htmlspecialchars(SITE_URL); ?>/assets/images/map_preview.jpg" 
                     class="img-fluid rounded shadow-lg w-100" 
                     alt="<?php echo __('interactive_map'); ?>" 
                     loading="lazy">
            </div>
        </div>
    </div>
</section>

<!-- Emergency Services Section -->
<section class="py-5 bg-danger text-white">
    <div class="container py-4">
        <div class="row align-items-center">
            <div class="col-lg-8 mb-4 mb-lg-0">
                <h2 class="fw-bold mb-4"><?php echo __('emergency_services'); ?></h2>
                <p class="lead mb-5"><?php echo __('emergency_description'); ?></p>
                <div class="d-flex flex-wrap gap-3">
                    <?php
                    $emergency_contacts = [
                        ['number' => '911', 'label' => __('general_emergency')],
                        ['number' => '937', 'label' => __('medical_emergency')],
                        ['number' => '999', 'label' => __('police')],
                    ];
                    foreach ($emergency_contacts as $contact):
                    ?>
                        <div class="p-3 bg-white bg-opacity-10 rounded-3">
                            <h4 class="fw-bold"><i class="fas fa-phone-alt me-2" aria-hidden="true"></i> <?php echo $contact['number']; ?></h4>
                            <p class="mb-0"><?php echo $contact['label']; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <a href="<?php echo isLoggedIn() ? htmlspecialchars(SITE_URL . '/services/missing_report.php') : htmlspecialchars(SITE_URL . '/auth/login.php'); ?>" 
                   class="btn btn-light btn-lg px-4 py-3 shadow-sm"
                   <?php if (!isLoggedIn()): ?>onclick="setFlashMessage('warning', '<?php echo __('please_login'); ?>')"<?php endif; ?>>
                    <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i> <?php echo __('report_emergency'); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container py-4">
        <h2 class="display-5 fw-bold mb-4"><?php echo __('ready_to_start'); ?></h2>
        <p class="lead mx-auto mb-5" style="max-width: 700px"><?php echo __('cta_description'); ?></p>
        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
            <?php if (!isLoggedIn()): ?>
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/auth/register.php" class="btn btn-light btn-lg px-5 py-3">
                    <i class="fas fa-user-plus me-2" aria-hidden="true"></i> <?php echo __('register_now'); ?>
                </a>
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/about.php" class="btn btn-outline-light btn-lg px-5 py-3"><?php echo __('learn_more'); ?></a>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/dashboard/pilgrim.php" class="btn btn-light btn-lg px-5 py-3">
                    <i class="fas fa-tachometer-alt me-2" aria-hidden="true"></i> <?php echo __('go_to_dashboard'); ?>
                </a>
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/maps/interactive_map.php" class="btn btn-outline-light btn-lg px-5 py-3">
                    <i class="fas fa-map-marked-alt me-2" aria-hidden="true"></i> <?php echo __('explore_map'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Custom Styles -->
<style>
    .hero {
        background-image: url('<?php echo htmlspecialchars(SITE_URL); ?>/assets/images/hero-bg.jpg');
        background-size: cover;
        background-position: center;
    }

    .hero-bg-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
    }

    .hover-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    }

    .bg-primary-subtle {
        background-color: rgba(93, 92, 222, 0.1);
    }

    [data-bs-theme="dark"] .bg-light {
        background-color: #2a2a2a;
    }

    [data-bs-theme="dark"] .text-muted {
        color: rgba(255, 255, 255, 0.6);
    }

    [data-bs-theme="dark"] .card {
        background-color: #333;
    }

    @media (max-width: 576px) {
        .hero {
            text-align: center;
        }

        .hero .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }
</style>

<!-- Flash Message Script -->
<script>
    function setFlashMessage(type, message) {
        sessionStorage.setItem('flashMessage', JSON.stringify({ type, message }));
    }
</script>

<?php
// Include footer
require_once 'includes/footer.php';
?>