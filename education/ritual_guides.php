<?php

// Include header
include_once('../includes/header.php');
// Set page title
$pageTitle = __('ritual_guides');

?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <h1><i class="fas fa-scroll me-2"></i> <?php echo __('ritual_guides'); ?></h1>
            <p class="lead"><?php echo __('ritual_guides_description'); ?></p>
            
            <!-- Umrah Rituals -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-kaaba me-2"></i> <?php echo __('umrah_rituals'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100 mb-3">
                                <img src="<?php echo SITE_URL; ?>/assets/images/umrah.jpg" class="card-img-top" alt="<?php echo __('umrah'); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo __('umrah_overview'); ?></h5>
                                    <p class="card-text"><?php echo __('umrah_overview_desc'); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary"><?php echo __('beginner_friendly'); ?></span>
                                        <a href="#umrahModal" data-bs-toggle="modal" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i> <?php echo __('view'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="ritual-steps-list">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="ritual-step-number">1</span>
                                    <div>
                                        <h6 class="mb-0"><?php echo __('ihram'); ?></h6>
                                        <p class="small text-muted mb-0"><?php echo __('ihram_short_desc'); ?></p>
                                        <a href="<?php echo SITE_URL; ?>/rituals/other_rituals.php#ihram" class="small">
                                            <?php echo __('read_more'); ?> <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <span class="ritual-step-number">2</span>
                                    <div>
                                        <h6 class="mb-0"><?php echo __('tawaf'); ?></h6>
                                        <p class="small text-muted mb-0"><?php echo __('tawaf_short_desc'); ?></p>
                                        <a href="<?php echo SITE_URL; ?>/rituals/tawaf_guide.php" class="small">
                                            <?php echo __('read_more'); ?> <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <span class="ritual-step-number">3</span>
                                    <div>
                                        <h6 class="mb-0"><?php echo __('sai'); ?></h6>
                                        <p class="small text-muted mb-0"><?php echo __('sai_short_desc'); ?></p>
                                        <a href="<?php echo SITE_URL; ?>/rituals/sai_guide.php" class="small">
                                            <?php echo __('read_more'); ?> <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    <span class="ritual-step-number">4</span>
                                    <div>
                                        <h6 class="mb-0"><?php echo __('halq_taqsir'); ?></h6>
                                        <p class="small text-muted mb-0"><?php echo __('halq_short_desc'); ?></p>
                                        <a href="<?php echo SITE_URL; ?>/rituals/other_rituals.php#halq" class="small">
                                            <?php echo __('read_more'); ?> <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hajj Rituals -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-mosque me-2"></i> <?php echo __('hajj_rituals'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="ritual-steps-list">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="ritual-step-number bg-success">1</span>
                                    <div>
                                        <h6 class="mb-0"><?php echo __('ihram_for_hajj'); ?></h6>
                                        <p class="small text-muted mb-0"><?php echo __('ihram_hajj_short_desc'); ?></p>
                                        <a href="<?php echo SITE_URL; ?>/rituals/other_rituals.php#ihram" class="small">
                                            <?php echo __('read_more'); ?> <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <span class="ritual-step-number bg-success">2</span>
                                    <div>
                                        <h6 class="mb-0"><?php echo __('mina'); ?> - <?php echo __('day_8'); ?></h6>
                                        <p class="small text-muted mb-0"><?php echo __('mina_day8_short_desc'); ?></p>
                                        <a href="<?php echo SITE_URL; ?>/rituals/other_rituals.php#mina" class="small">
                                            <?php echo __('read_more'); ?> <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <span class="ritual-step-number bg-success">3</span>
                                    <div>
                                        <h6 class="mb-0"><?php echo __('arafat'); ?> - <?php echo __('day_9'); ?></h6>
                                        <p class="small text-muted mb-0"><?php echo __('arafat_short_desc'); ?></p>
                                        <a href="<?php echo SITE_URL; ?>/rituals/arafat_guide.php" class="small">
                                            <?php echo __('read_more'); ?> <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <span class="ritual-step-number bg-success">4</span>
                                    <div>
                                        <h6 class="mb-0"><?php echo __('muzdalifah'); ?> - <?php echo __('night_9'); ?></h6>
                                        <p class="small text-muted mb-0"><?php echo __('muzdalifah_short_desc'); ?></p>
                                        <a href="<?php echo SITE_URL; ?>/rituals/other_rituals.php#muzdalifah" class="small">
                                            <?php echo __('read_more'); ?> <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="ritual-steps-list">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="ritual-step-number bg-success">5</span>
                                    <div>
                                        <h6 class="mb-0"><?php echo __('jamarat'); ?> - <?php echo __('day_10'); ?></h6>
                                        <p class="small text-muted mb-0"><?php echo __('jamarat_short_desc'); ?></p>
                                        <a href="<?php echo SITE_URL; ?>/rituals/other_rituals.php#jamarat" class="small">
                                            <?php echo __('read_more'); ?> <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <span class="ritual-step-number bg-success">6</span>
                                    <div>
                                        <h6 class="mb-0"><?php echo __('sacrifice'); ?> - <?php echo __('day_10'); ?></h6>
                                        <p class="small text-muted mb-0"><?php echo __('sacrifice_short_desc'); ?></p>
                                        <a href="<?php echo SITE_URL; ?>/rituals/other_rituals.php#sacrifice" class="small">
                                            <?php echo __('read_more'); ?> <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center mb-3">
                                    <span class="ritual-step-number bg-success">7</span>
                                    <div>
                                        <h6 class="mb-0"><?php echo __('halq_taqsir'); ?> - <?php echo __('day_10'); ?></h6>
                                        <p class="small text-muted mb-0"><?php echo __('halq_hajj_short_desc'); ?></p>
                                        <a href="<?php echo SITE_URL; ?>/rituals/other_rituals.php#halq" class="small">
                                            <?php echo __('read_more'); ?> <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    <span class="ritual-step-number bg-success">8</span>
                                    <div>
                                        <h6 class="mb-0"><?php echo __('tawaf_ifadah'); ?> & <?php echo __('sai'); ?></h6>
                                        <p class="small text-muted mb-0"><?php echo __('tawaf_ifadah_short_desc'); ?></p>
                                        <a href="<?php echo SITE_URL; ?>/rituals/tawaf_guide.php" class="small">
                                            <?php echo __('read_more'); ?> <i class="fas fa-chevron-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> <?php echo __('hajj_days_note'); ?>
                        </div>
                        
                        <div class="d-flex justify-content-center mt-3">
                            <a href="#hajjModal" data-bs-toggle="modal" class="btn btn-primary">
                                <i class="fas fa-calendar-alt me-2"></i> <?php echo __('view_hajj_calendar'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ritual Maps -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map me-2"></i> <?php echo __('ritual_maps'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <img src="<?php echo SITE_URL; ?>/assets/images/masjid_al_haram_map.jpg" class="card-img-top" alt="<?php echo __('masjid_al_haram'); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo __('masjid_al_haram_map'); ?></h5>
                                    <p class="card-text"><?php echo __('masjid_al_haram_map_desc'); ?></p>
                                    <a href="#mapModal1" data-bs-toggle="modal" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-search-plus me-1"></i> <?php echo __('enlarge'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card h-100">
                                <img src="<?php echo SITE_URL; ?>/assets/images/hajj_sites_map.jpg" class="card-img-top" alt="<?php echo __('hajj_sites'); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo __('hajj_sites_map'); ?></h5>
                                    <p class="card-text"><?php echo __('hajj_sites_map_desc'); ?></p>
                                    <a href="#mapModal2" data-bs-toggle="modal" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-search-plus me-1"></i> <?php echo __('enlarge'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Quick Links -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i> <?php echo __('quick_links'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo SITE_URL; ?>/rituals/tawaf_guide.php" class="btn btn-outline-primary">
                            <i class="fas fa-circle-notch me-2"></i> <?php echo __('tawaf_guide'); ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/rituals/sai_guide.php" class="btn btn-outline-primary">
                            <i class="fas fa-walking me-2"></i> <?php echo __('sai_guide'); ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/rituals/arafat_guide.php" class="btn btn-outline-primary">
                            <i class="fas fa-mountain me-2"></i> <?php echo __('arafat_guide'); ?>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/rituals/other_rituals.php" class="btn btn-outline-primary">
                            <i class="fas fa-book me-2"></i> <?php echo __('other_rituals'); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Educational Videos -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-video me-2"></i> <?php echo __('educational_videos'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="ratio ratio-16x9 mb-2">
                            <iframe src="https://www.youtube.com/embed/PjOb2lQmM5I" title="Hajj Guide" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                        <h6><?php echo __('comprehensive_hajj_guide'); ?></h6>
                    </div>
                    
                    <div class="mb-3">
                        <div class="ratio ratio-16x9 mb-2">
                            <iframe src="https://www.youtube.com/embed/MgMQwB4K4xE" title="Umrah Guide" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        </div>
                        <h6><?php echo __('step_by_step_umrah'); ?></h6>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="#videoModal" data-bs-toggle="modal" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-th-list me-1"></i> <?php echo __('view_all_videos'); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Downloadable Resources -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-download me-2"></i> <?php echo __('downloadable_resources'); ?></h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-pdf text-danger me-2"></i>
                                <?php echo __('hajj_guide_pdf'); ?>
                            </div>
                            <a href="#" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i>
                            </a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-pdf text-danger me-2"></i>
                                <?php echo __('umrah_guide_pdf'); ?>
                            </div>
                            <a href="#" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i>
                            </a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-pdf text-danger me-2"></i>
                                <?php echo __('supplications_pdf'); ?>
                            </div>
                            <a href="#" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i>
                            </a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-pdf text-danger me-2"></i>
                                <?php echo __('maps_pdf'); ?>
                            </div>
                            <a href="#" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Have a Question? -->
            <div class="card bg-light mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-question-circle text-primary mb-3" style="font-size: 3rem;"></i>
                    <h5 class="card-title"><?php echo __('have_question'); ?></h5>
                    <p class="card-text"><?php echo __('question_description'); ?></p>
                    <a href="<?php echo SITE_URL; ?>/education/faqs.php" class="btn btn-primary">
                        <i class="fas fa-question me-2"></i> <?php echo __('view_faqs'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Umrah Modal -->
<div class="modal fade" id="umrahModal" tabindex="-1" aria-labelledby="umrahModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="umrahModalLabel"><?php echo __('umrah_guide'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ratio ratio-16x9 mb-3">
                    <iframe src="https://www.youtube.com/embed/MgMQwB4K4xE" title="Umrah Guide" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
                
                <h5><?php echo __('umrah_steps'); ?></h5>
                <ol>
                    <li><strong><?php echo __('ihram'); ?>:</strong> <?php echo __('umrah_step1_desc'); ?></li>
                    <li><strong><?php echo __('tawaf'); ?>:</strong> <?php echo __('umrah_step2_desc'); ?></li>
                    <li><strong><?php echo __('sai'); ?>:</strong> <?php echo __('umrah_step3_desc'); ?></li>
                    <li><strong><?php echo __('halq_taqsir'); ?>:</strong> <?php echo __('umrah_step4_desc'); ?></li>
                </ol>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> <?php echo __('umrah_note'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('close'); ?></button>
                <a href="<?php echo SITE_URL; ?>/rituals/tawaf_guide.php" class="btn btn-primary">
                    <?php echo __('detailed_guides'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Hajj Calendar Modal -->
<div class="modal fade" id="hajjModal" tabindex="-1" aria-labelledby="hajjModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hajjModalLabel"><?php echo __('hajj_calendar'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-primary">
                            <tr>
                                <th><?php echo __('day'); ?></th>
                                <th><?php echo __('date'); ?></th>
                                <th><?php echo __('activities'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>8 <?php echo __('dhul_hijjah'); ?><br><small>(<?php echo __('yawm_al_tarwiyah'); ?>)</small></td>
                                <td id="hajjDay8"></td>
                                <td>
                                    <ul class="mb-0">
                                        <li><?php echo __('hajj_day8_activity1'); ?></li>
                                        <li><?php echo __('hajj_day8_activity2'); ?></li>
                                        <li><?php echo __('hajj_day8_activity3'); ?></li>
                                    </ul>
                                </td>
                            </tr>
                            <tr class="table-warning">
                                <td>9 <?php echo __('dhul_hijjah'); ?><br><small>(<?php echo __('yawm_al_arafah'); ?>)</small></td>
                                <td id="hajjDay9"></td>
                                <td>
                                    <ul class="mb-0">
                                        <li><?php echo __('hajj_day9_activity1'); ?></li>
                                        <li><?php echo __('hajj_day9_activity2'); ?></li>
                                        <li><?php echo __('hajj_day9_activity3'); ?></li>
                                        <li><?php echo __('hajj_day9_activity4'); ?></li>
                                    </ul>
                                </td>
                            </tr>
                            <tr class="table-success">
                                <td>10 <?php echo __('dhul_hijjah'); ?><br><small>(<?php echo __('yawm_al_nahr'); ?>)</small></td>
                                <td id="hajjDay10"></td>
                                <td>
                                    <ul class="mb-0">
                                        <li><?php echo __('hajj_day10_activity1'); ?></li>
                                        <li><?php echo __('hajj_day10_activity2'); ?></li>
                                        <li><?php echo __('hajj_day10_activity3'); ?></li>
                                        <li><?php echo __('hajj_day10_activity4'); ?></li>
                                        <li><?php echo __('hajj_day10_activity5'); ?></li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <td>11 <?php echo __('dhul_hijjah'); ?><br><small>(<?php echo __('yawm_al_tashriq'); ?>)</small></td>
                                <td id="hajjDay11"></td>
                                <td>
                                    <ul class="mb-0">
                                        <li><?php echo __('hajj_day11_activity1'); ?></li>
                                        <li><?php echo __('hajj_day11_activity2'); ?></li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <td>12 <?php echo __('dhul_hijjah'); ?><br><small>(<?php echo __('yawm_al_tashriq'); ?>)</small></td>
                                <td id="hajjDay12"></td>
                                <td>
                                    <ul class="mb-0">
                                        <li><?php echo __('hajj_day12_activity1'); ?></li>
                                        <li><?php echo __('hajj_day12_activity2'); ?></li>
                                        <li><?php echo __('hajj_day12_activity3'); ?></li>
                                    </ul>
                                </td>
                            </tr>
                            <tr>
                                <td>13 <?php echo __('dhul_hijjah'); ?><br><small>(<?php echo __('yawm_al_tashriq'); ?>)</small></td>
                                <td id="hajjDay13"></td>
                                <td>
                                    <ul class="mb-0">
                                        <li><?php echo __('hajj_day13_activity1'); ?></li>
                                        <li><?php echo __('hajj_day13_activity2'); ?></li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i> <?php echo __('hajj_calendar_note'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('close'); ?></button>
                <a href="#" class="btn btn-primary" id="downloadCalendarBtn">
                    <i class="fas fa-download me-2"></i> <?php echo __('download_calendar'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Map Modals -->
<div class="modal fade" id="mapModal1" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('masjid_al_haram_map'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <img src="<?php echo SITE_URL; ?>/assets/images/masjid_al_haram_map.jpg" class="img-fluid" alt="<?php echo __('masjid_al_haram'); ?>">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mapModal2" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo __('hajj_sites_map'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <img src="<?php echo SITE_URL; ?>/assets/images/hajj_sites_map.jpg" class="img-fluid" alt="<?php echo __('hajj_sites'); ?>">
            </div>
        </div>
    </div>
</div>

<!-- Videos Modal -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-labelledby="videoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoModalLabel"><?php echo __('educational_videos'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="ratio ratio-16x9">
                                <iframe src="https://www.youtube.com/embed/PjOb2lQmM5I" title="Hajj Guide" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title"><?php echo __('comprehensive_hajj_guide'); ?></h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="ratio ratio-16x9">
                                <iframe src="https://www.youtube.com/embed/MgMQwB4K4xE" title="Umrah Guide" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title"><?php echo __('step_by_step_umrah'); ?></h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="ratio ratio-16x9">
                                <iframe src="https://www.youtube.com/embed/QZxUL_L65CY" title="Sa'i Guide" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title"><?php echo __('sai_detailed_guide'); ?></h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="ratio ratio-16x9">
                                <iframe src="https://www.youtube.com/embed/dZFcXxhsRuo" title="Tawaf Guide" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title"><?php echo __('tawaf_detailed_guide'); ?></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<style>
    .ritual-step-number {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background-color: var(--primary);
        color: white;
        border-radius: 50%;
        margin-right: 1rem;
        font-weight: bold;
        flex-shrink: 0;
    }
    
    .ritual-steps-list {
        padding: 1rem;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set Hajj calendar dates
        // This is a simplified example - in a real application, you would calculate
        // the actual Hijri calendar dates based on the current year
        const currentYear = new Date().getFullYear();
        
        // Example dates - these would be calculated from the Hijri calendar
        document.getElementById('hajjDay8').textContent = `${currentYear}-06-26`;
        document.getElementById('hajjDay9').textContent = `${currentYear}-06-27`;
        document.getElementById('hajjDay10').textContent = `${currentYear}-06-28`;
        document.getElementById('hajjDay11').textContent = `${currentYear}-06-29`;
        document.getElementById('hajjDay12').textContent = `${currentYear}-06-30`;
        document.getElementById('hajjDay13').textContent = `${currentYear}-07-01`;
        
        // Handle download calendar button
        document.getElementById('downloadCalendarBtn').addEventListener('click', function(e) {
            e.preventDefault();
            alert('Calendar download would be initiated here.');
        });
    });
</script>

<?php
// Include footer
include_once('../includes/footer.php');
?>