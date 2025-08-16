<?php

// Include header
include_once('../includes/header.php');
// Set page title
$pageTitle = __('arafat_guide');

?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <h1><i class="fas fa-mountain me-2"></i> <?php echo __('arafat_guide'); ?></h1>
            <p class="lead"><?php echo __('arafat_description_full'); ?></p>
            
            <!-- Video Overview -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-video me-2"></i> <?php echo __('video_overview'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.youtube.com/embed/eBt8P-mF8Aw" title="Arafat Guide" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
            
            <!-- Arafat Significance -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-star me-2"></i> <?php echo __('significance_of_arafat'); ?></h5>
                </div>
                <div class="card-body">
                    <p><?php echo __('arafat_significance_text'); ?></p>
                    
                    <div class="alert alert-primary">
                        <i class="fas fa-quote-left me-2"></i> <?php echo __('prophet_saying_about_arafat'); ?>
                    </div>
                    
                    <h6><?php echo __('historical_significance'); ?></h6>
                    <ul>
                        <li><?php echo __('arafat_historical_point1'); ?></li>
                        <li><?php echo __('arafat_historical_point2'); ?></li>
                        <li><?php echo __('arafat_historical_point3'); ?></li>
                        <li><?php echo __('arafat_historical_point4'); ?></li>
                    </ul>
                </div>
            </div>
            
            <!-- Step by Step Guide -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list-ol me-2"></i> <?php echo __('step_by_step_guide'); ?></h5>
                </div>
                <div class="card-body">
                    <!-- Step 1 -->
                    <div class="ritual-step">
                        <div class="ritual-step-header" data-bs-toggle="collapse" data-bs-target="#step1Content">
                            <div class="d-flex align-items-center">
                                <span class="step-number">1</span>
                                <h5 class="mb-0"><?php echo __('before_arafat'); ?></h5>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="collapse show ritual-step-content" id="step1Content">
                            <p><?php echo __('arafat_step1_desc'); ?></p>
                            <ul>
                                <li><?php echo __('arafat_step1_item1'); ?></li>
                                <li><?php echo __('arafat_step1_item2'); ?></li>
                                <li><?php echo __('arafat_step1_item3'); ?></li>
                                <li><?php echo __('arafat_step1_item4'); ?></li>
                                <li><?php echo __('arafat_step1_item5'); ?></li>
                            </ul>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> <?php echo __('arafat_step1_note'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2 -->
                    <div class="ritual-step">
                        <div class="ritual-step-header" data-bs-toggle="collapse" data-bs-target="#step2Content">
                            <div class="d-flex align-items-center">
                                <span class="step-number">2</span>
                                <h5 class="mb-0"><?php echo __('arrival_at_arafat'); ?></h5>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="collapse ritual-step-content" id="step2Content">
                            <p><?php echo __('arafat_step2_desc'); ?></p>
                            <ul>
                                <li><?php echo __('arafat_step2_item1'); ?></li>
                                <li><?php echo __('arafat_step2_item2'); ?></li>
                                <li><?php echo __('arafat_step2_item3'); ?></li>
                                <li><?php echo __('arafat_step2_item4'); ?></li>
                            </ul>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo __('arafat_step2_note'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 3 -->
                    <div class="ritual-step">
                        <div class="ritual-step-header" data-bs-toggle="collapse" data-bs-target="#step3Content">
                            <div class="d-flex align-items-center">
                                <span class="step-number">3</span>
                                <h5 class="mb-0"><?php echo __('during_arafat'); ?></h5>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="collapse ritual-step-content" id="step3Content">
                            <p><?php echo __('arafat_step3_desc'); ?></p>
                            <ul>
                                <li><?php echo __('arafat_step3_item1'); ?></li>
                                <li><?php echo __('arafat_step3_item2'); ?></li>
                                <li><?php echo __('arafat_step3_item3'); ?></li>
                                <li><?php echo __('arafat_step3_item4'); ?></li>
                                <li><?php echo __('arafat_step3_item5'); ?></li>
                            </ul>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i> <?php echo __('arafat_step3_note'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 4 -->
                    <div class="ritual-step">
                        <div class="ritual-step-header" data-bs-toggle="collapse" data-bs-target="#step4Content">
                            <div class="d-flex align-items-center">
                                <span class="step-number">4</span>
                                <h5 class="mb-0"><?php echo __('departure_from_arafat'); ?></h5>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="collapse ritual-step-content" id="step4Content">
                            <p><?php echo __('arafat_step4_desc'); ?></p>
                            <ul>
                                <li><?php echo __('arafat_step4_item1'); ?></li>
                                <li><?php echo __('arafat_step4_item2'); ?></li>
                                <li><?php echo __('arafat_step4_item3'); ?></li>
                                <li><?php echo __('arafat_step4_item4'); ?></li>
                            </ul>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo __('arafat_step4_note'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Map of Arafat -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map me-2"></i> <?php echo __('arafat_map'); ?></h5>
                </div>
                <div class="card-body">
                    <img src="<?php echo SITE_URL; ?>/assets/images/arafat_map.jpg" alt="<?php echo __('arafat_map'); ?>" class="img-fluid rounded">
                    <div class="mt-3">
                        <p class="text-muted"><?php echo __('arafat_map_description'); ?></p>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6><i class="fas fa-map-pin text-danger me-2"></i> <?php echo __('key_locations'); ?></h6>
                                <ul>
                                    <li><strong><?php echo __('jabal_rahmah'); ?>:</strong> <?php echo __('jabal_rahmah_desc'); ?></li>
                                    <li><strong><?php echo __('namira_mosque'); ?>:</strong> <?php echo __('namira_mosque_desc'); ?></li>
                                    <li><strong><?php echo __('urna_valley'); ?>:</strong> <?php echo __('urna_valley_desc'); ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle text-primary me-2"></i> <?php echo __('boundaries'); ?></h6>
                                <p><?php echo __('arafat_boundaries_desc'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Essential Timings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i> <?php echo __('essential_timings'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><?php echo __('event'); ?></th>
                                    <th><?php echo __('timing'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo __('arrival_at_arafat'); ?></td>
                                    <td><?php echo __('after_fajr_9th_dhulhijjah'); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo __('zuhr_and_asr_prayers'); ?></td>
                                    <td><?php echo __('combined_at_zuhr_time'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo __('wuqoof_time'); ?></strong></td>
                                    <td><?php echo __('zuhr_to_maghrib'); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo __('departure_from_arafat'); ?></td>
                                    <td><?php echo __('after_sunset'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo __('timing_warning'); ?>
                    </div>
                </div>
            </div>
            
            <!-- Recommended Supplications -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-pray me-2"></i> <?php echo __('recommended_supplications'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="supplication mb-4">
                        <h6><?php echo __('best_dua_for_arafat'); ?></h6>
                        <div class="arabic-text text-center my-3">
                            لَا إِلَهَ إِلَّا اللَّهُ وَحْدَهُ لَا شَرِيكَ لَهُ، لَهُ الْمُلْكُ وَلَهُ الْحَمْدُ، وَهُوَ عَلَى كُلِّ شَيْءٍ قَدِيرٌ
                        </div>
                        <div class="translation">
                            <strong><?php echo __('translation'); ?>:</strong> <?php echo __('best_dua_translation'); ?>
                        </div>
                    </div>
                    
                    <div class="supplication mb-4">
                        <h6><?php echo __('seeking_forgiveness'); ?></h6>
                        <div class="arabic-text text-center my-3">
                            رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الْآخِرَةِ حَسَنَةً وَقِنَا عَذَابَ النَّارِ
                        </div>
                        <div class="translation">
                            <strong><?php echo __('translation'); ?>:</strong> <?php echo __('seeking_forgiveness_translation'); ?>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> <?php echo __('arafat_supplication_note'); ?>
                    </div>
                </div>
            </div>
            
            <!-- Common Mistakes -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i> <?php echo __('common_mistakes'); ?></h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-times-circle text-danger me-2"></i> <?php echo __('arafat_mistake_1'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-times-circle text-danger me-2"></i> <?php echo __('arafat_mistake_2'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-times-circle text-danger me-2"></i> <?php echo __('arafat_mistake_3'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-times-circle text-danger me-2"></i> <?php echo __('arafat_mistake_4'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-times-circle text-danger me-2"></i> <?php echo __('arafat_mistake_5'); ?>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Practical Tips -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i> <?php echo __('practical_tips'); ?></h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('arafat_tip_1'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('arafat_tip_2'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('arafat_tip_3'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('arafat_tip_4'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('arafat_tip_5'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check-circle text-success me-2"></i> <?php echo __('arafat_tip_6'); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle steps
    document.addEventListener('DOMContentLoaded', function() {
        const stepHeaders = document.querySelectorAll('.ritual-step-header');
        
        stepHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-chevron-down');
                icon.classList.toggle('fa-chevron-up');
            });
        });
    });
</script>

<style>
    .ritual-step {
        margin-bottom: 1rem;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    
    .ritual-step-header {
        padding: 1rem;
        background-color: #f8f9fa;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }
    
    .ritual-step-content {
        padding: 1.5rem;
    }
    
    .step-number {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        background-color: var(--primary);
        color: white;
        border-radius: 50%;
        margin-right: 1rem;
        font-weight: bold;
    }
    
    .arabic-text {
        font-family: 'Traditional Arabic', serif;
        font-size: 1.5rem;
        line-height: 2;
        direction: rtl;
    }
</style>

<?php
// Include footer
include_once('../includes/footer.php');
?>