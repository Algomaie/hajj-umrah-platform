<?php

require_once('../includes/functions.php');
// Set page title
$pageTitle = __('tawaf_guide');

// Include header
include_once('../includes/header.php');
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <h1><i class="fas fa-sync-alt me-2"></i> <?php echo __('tawaf_guide'); ?></h1>
            <p class="lead"><?php echo __('tawaf_description_full'); ?></p>
            
            <!-- Video Overview -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-video me-2"></i> <?php echo __('video_overview'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.youtube.com/embed/dZFcXxhsRuo" title="Tawaf Guide" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
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
                                <h5 class="mb-0"><?php echo __('preparation'); ?></h5>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="collapse show ritual-step-content" id="step1Content">
                            <p><?php echo __('tawaf_step1_desc'); ?></p>
                            <ul>
                                <li><?php echo __('tawaf_step1_item1'); ?></li>
                                <li><?php echo __('tawaf_step1_item2'); ?></li>
                                <li><?php echo __('tawaf_step1_item3'); ?></li>
                                <li><?php echo __('tawaf_step1_item4'); ?></li>
                            </ul>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> <?php echo __('tawaf_step1_note'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2 -->
                    <div class="ritual-step">
                        <div class="ritual-step-header" data-bs-toggle="collapse" data-bs-target="#step2Content">
                            <div class="d-flex align-items-center">
                                <span class="step-number">2</span>
                                <h5 class="mb-0"><?php echo __('starting_position'); ?></h5>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="collapse ritual-step-content" id="step2Content">
                            <p><?php echo __('tawaf_step2_desc'); ?></p>
                            <ul>
                                <li><?php echo __('tawaf_step2_item1'); ?></li>
                                <li><?php echo __('tawaf_step2_item2'); ?></li>
                                <li><?php echo __('tawaf_step2_item3'); ?></li>
                            </ul>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo __('tawaf_step2_note'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 3 -->
                    <div class="ritual-step">
                        <div class="ritual-step-header" data-bs-toggle="collapse" data-bs-target="#step3Content">
                            <div class="d-flex align-items-center">
                                <span class="step-number">3</span>
                                <h5 class="mb-0"><?php echo __('performing_rounds'); ?></h5>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="collapse ritual-step-content" id="step3Content">
                            <p><?php echo __('tawaf_step3_desc'); ?></p>
                            <ol>
                                <li><?php echo __('tawaf_step3_item1'); ?></li>
                                <li><?php echo __('tawaf_step3_item2'); ?></li>
                                <li><?php echo __('tawaf_step3_item3'); ?></li>
                                <li><?php echo __('tawaf_step3_item4'); ?></li>
                                <li><?php echo __('tawaf_step3_item5'); ?></li>
                                <li><?php echo __('tawaf_step3_item6'); ?></li>
                                <li><?php echo __('tawaf_step3_item7'); ?></li>
                            </ol>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> <?php echo __('tawaf_step3_note'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 4 -->
                    <div class="ritual-step">
                        <div class="ritual-step-header" data-bs-toggle="collapse" data-bs-target="#step4Content">
                            <div class="d-flex align-items-center">
                                <span class="step-number">4</span>
                                <h5 class="mb-0"><?php echo __('prayer_at_maqam'); ?></h5>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="collapse ritual-step-content" id="step4Content">
                            <p><?php echo __('tawaf_step4_desc'); ?></p>
                            <ul>
                                <li><?php echo __('tawaf_step4_item1'); ?></li>
                                <li><?php echo __('tawaf_step4_item2'); ?></li>
                                <li><?php echo __('tawaf_step4_item3'); ?></li>
                            </ul>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo __('tawaf_step4_note'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 5 -->
                    <div class="ritual-step">
                        <div class="ritual-step-header" data-bs-toggle="collapse" data-bs-target="#step5Content">
                            <div class="d-flex align-items-center">
                                <span class="step-number">5</span>
                                <h5 class="mb-0"><?php echo __('zamzam_water'); ?></h5>
                            </div>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="collapse ritual-step-content" id="step5Content">
                            <p><?php echo __('tawaf_step5_desc'); ?></p>
                            <ul>
                                <li><?php echo __('tawaf_step5_item1'); ?></li>
                                <li><?php echo __('tawaf_step5_item2'); ?></li>
                                <li><?php echo __('tawaf_step5_item3'); ?></li>
                            </ul>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> <?php echo __('tawaf_step5_note'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Map of Tawaf -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map me-2"></i> <?php echo __('tawaf_map'); ?></h5>
                </div>
                <div class="card-body">
                    <img src="<?php echo SITE_URL; ?>../assets/images/tawaf_map.png" alt="<?php echo __('tawaf_map'); ?>" class="img-fluid rounded">
                    <div class="mt-3">
                        <p class="text-muted"><?php echo __('tawaf_map_description'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Supplications -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-pray me-2"></i> <?php echo __('recommended_supplications'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="supplication mb-4">
                        <h6><?php echo __('general_tawaf_dua'); ?></h6>
                        <div class="arabic-text text-center my-3">
                            سُبْحَانَ اللهِ وَالْحَمْدُ لِلهِ وَلاَ إِلَهَ إِلاَّ اللهُ وَاللهُ أَكْبَرُ وَلاَ حَوْلَ وَلاَ قُوَّةَ إِلاَّ بِاللهِ الْعَلِيِّ الْعَظِيمِ
                        </div>
                        <div class="translation">
                            <strong><?php echo __('translation'); ?>:</strong> <?php echo __('general_tawaf_dua_translation'); ?>
                        </div>
                    </div>
                    
                    <div class="supplication mb-4">
                        <h6><?php echo __('between_rukn_yamani_and_black_stone'); ?></h6>
                        <div class="arabic-text text-center my-3">
                            رَبَّنَا آتِنَا فِي الدُّنْيَا حَسَنَةً وَفِي الْآخِرَةِ حَسَنَةً وَقِنَا عَذَابَ النَّارِ
                        </div>
                        <div class="translation">
                            <strong><?php echo __('translation'); ?>:</strong> <?php echo __('rabbana_dua_translation'); ?>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> <?php echo __('supplication_note'); ?>
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
                            <i class="fas fa-times-circle text-danger me-2"></i> <?php echo __('tawaf_mistake_1'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-times-circle text-danger me-2"></i> <?php echo __('tawaf_mistake_2'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-times-circle text-danger me-2"></i> <?php echo __('tawaf_mistake_3'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-times-circle text-danger me-2"></i> <?php echo __('tawaf_mistake_4'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-times-circle text-danger me-2"></i> <?php echo __('tawaf_mistake_5'); ?>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- FAQs -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i> <?php echo __('faqs'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="tawafFaqs">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqOne">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                    <?php echo __('tawaf_faq_1_q'); ?>
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="faqOne" data-bs-parent="#tawafFaqs">
                                <div class="accordion-body">
                                    <?php echo __('tawaf_faq_1_a'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    <?php echo __('tawaf_faq_2_q'); ?>
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#tawafFaqs">
                                <div class="accordion-body">
                                    <?php echo __('tawaf_faq_2_a'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    <?php echo __('tawaf_faq_3_q'); ?>
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#tawafFaqs">
                                <div class="accordion-body">
                                    <?php echo __('tawaf_faq_3_a'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    <?php echo __('tawaf_faq_4_q'); ?>
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="faqFour" data-bs-parent="#tawafFaqs">
                                <div class="accordion-body">
                                    <?php echo __('tawaf_faq_4_a'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
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