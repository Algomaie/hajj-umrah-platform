 
<?php

// Include header
include_once('../includes/header.php');
// Set page title
$pageTitle = __('other_rituals');

?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <h1><i class="fas fa-book me-2"></i> <?php echo __('other_rituals'); ?></h1>
            <p class="lead"><?php echo __('other_rituals_description'); ?></p>
            
            <!-- Ritual Quick Links -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-link me-2"></i> <?php echo __('quick_links'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="#ihram" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="fas fa-robe mb-2" style="font-size: 1.5rem;"></i>
                                <span><?php echo __('ihram'); ?></span>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="#muzdalifah" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="fas fa-moon mb-2" style="font-size: 1.5rem;"></i>
                                <span><?php echo __('muzdalifah'); ?></span>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="#mina" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="fas fa-campground mb-2" style="font-size: 1.5rem;"></i>
                                <span><?php echo __('mina'); ?></span>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="#jamarat" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="fas fa-place-of-worship mb-2" style="font-size: 1.5rem;"></i>
                                <span><?php echo __('jamarat'); ?></span>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="#sacrifice" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="fas fa-sheep mb-2" style="font-size: 1.5rem;"></i>
                                <span><?php echo __('sacrifice'); ?></span>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="#halq" class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-3">
                                <i class="fas fa-cut mb-2" style="font-size: 1.5rem;"></i>
                                <span><?php echo __('halq_taqsir'); ?></span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Ihram -->
            <div id="ihram" class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-robe me-2"></i> <?php echo __('ihram'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-8">
                            <p><?php echo __('ihram_description'); ?></p>
                            
                            <h6><?php echo __('how_to_wear_ihram'); ?></h6>
                            <ul>
                                <li><?php echo __('ihram_wear_item1'); ?></li>
                                <li><?php echo __('ihram_wear_item2'); ?></li>
                                <li><?php echo __('ihram_wear_item3'); ?></li>
                                <li><?php echo __('ihram_wear_item4'); ?></li>
                            </ul>
                            
                            <h6 class="mt-3"><?php echo __('ihram_prohibitions'); ?></h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul>
                                        <li><?php echo __('ihram_prohibition1'); ?></li>
                                        <li><?php echo __('ihram_prohibition2'); ?></li>
                                        <li><?php echo __('ihram_prohibition3'); ?></li>
                                        <li><?php echo __('ihram_prohibition4'); ?></li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul>
                                        <li><?php echo __('ihram_prohibition5'); ?></li>
                                        <li><?php echo __('ihram_prohibition6'); ?></li>
                                        <li><?php echo __('ihram_prohibition7'); ?></li>
                                        <li><?php echo __('ihram_prohibition8'); ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <img src="<?php echo SITE_URL; ?>/assets/images/ihram.jpg" alt="<?php echo __('ihram'); ?>" class="img-fluid rounded">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> <?php echo __('ihram_note'); ?>
                    </div>
                    
                    <div class="supplication-box p-3 bg-light rounded">
                        <h6 class="text-center"><?php echo __('talbiyah'); ?></h6>
                        <div class="arabic-text text-center my-3">
                            لَبَّيْكَ اللَّهُمَّ لَبَّيْكَ، لَبَّيْكَ لَا شَرِيكَ لَكَ لَبَّيْكَ، إِنَّ الْحَمْدَ وَالنِّعْمَةَ لَكَ وَالْمُلْكَ، لَا شَرِيكَ لَكَ
                        </div>
                        <div class="translation text-center">
                            <strong><?php echo __('translation'); ?>:</strong> <?php echo __('talbiyah_translation'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Muzdalifah -->
            <div id="muzdalifah" class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-moon me-2"></i> <?php echo __('muzdalifah'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-8">
                            <p><?php echo __('muzdalifah_description'); ?></p>
                            
                            <h6><?php echo __('when_to_go'); ?></h6>
                            <p><?php echo __('muzdalifah_when'); ?></p>
                            
                            <h6><?php echo __('what_to_do'); ?></h6>
                            <ol>
                                <li><?php echo __('muzdalifah_todo1'); ?></li>
                                <li><?php echo __('muzdalifah_todo2'); ?></li>
                                <li><?php echo __('muzdalifah_todo3'); ?></li>
                                <li><?php echo __('muzdalifah_todo4'); ?></li>
                                <li><?php echo __('muzdalifah_todo5'); ?></li>
                            </ol>
                        </div>
                        <div class="col-md-4">
                            <img src="<?php echo SITE_URL; ?>/assets/images/muzdalifah.jpg" alt="<?php echo __('muzdalifah'); ?>" class="img-fluid rounded">
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo __('muzdalifah_warning'); ?>
                    </div>
                    
                    <div class="bg-light p-3 rounded">
                        <h6><i class="fas fa-lightbulb me-2"></i> <?php echo __('practical_tips'); ?></h6>
                        <ul class="mb-0">
                            <li><?php echo __('muzdalifah_tip1'); ?></li>
                            <li><?php echo __('muzdalifah_tip2'); ?></li>
                            <li><?php echo __('muzdalifah_tip3'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Mina -->
            <div id="mina" class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-campground me-2"></i> <?php echo __('mina'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-8">
                            <p><?php echo __('mina_description'); ?></p>
                            
                            <h6><?php echo __('days_of_mina'); ?></h6>
                            <ul>
                                <li><strong><?php echo __('mina_day8'); ?></strong>: <?php echo __('mina_day8_desc'); ?></li>
                                <li><strong><?php echo __('mina_day10'); ?></strong>: <?php echo __('mina_day10_desc'); ?></li>
                                <li><strong><?php echo __('mina_day11_12_13'); ?></strong>: <?php echo __('mina_day11_12_13_desc'); ?></li>
                            </ul>
                            
                            <h6><?php echo __('essential_activities'); ?></h6>
                            <ul>
                                <li><?php echo __('mina_activity1'); ?></li>
                                <li><?php echo __('mina_activity2'); ?></li>
                                <li><?php echo __('mina_activity3'); ?></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <img src="<?php echo SITE_URL; ?>/assets/images/mina.jpg" alt="<?php echo __('mina'); ?>" class="img-fluid rounded">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> <?php echo __('mina_note'); ?>
                    </div>
                    
                    <div class="bg-light p-3 rounded">
                        <h6><i class="fas fa-lightbulb me-2"></i> <?php echo __('practical_tips'); ?></h6>
                        <ul class="mb-0">
                            <li><?php echo __('mina_tip1'); ?></li>
                            <li><?php echo __('mina_tip2'); ?></li>
                            <li><?php echo __('mina_tip3'); ?></li>
                            <li><?php echo __('mina_tip4'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Jamarat -->
            <div id="jamarat" class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-place-of-worship me-2"></i> <?php echo __('jamarat'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-8">
                            <p><?php echo __('jamarat_description'); ?></p>
                            
                            <h6><?php echo __('schedule'); ?></h6>
                            <ul>
                                <li><strong><?php echo __('jamarat_day10'); ?></strong>: <?php echo __('jamarat_day10_desc'); ?></li>
                                <li><strong><?php echo __('jamarat_day11_12'); ?></strong>: <?php echo __('jamarat_day11_12_desc'); ?></li>
                                <li><strong><?php echo __('jamarat_day13'); ?></strong>: <?php echo __('jamarat_day13_desc'); ?></li>
                            </ul>
                            
                            <h6><?php echo __('how_to_perform'); ?></h6>
                            <ol>
                                <li><?php echo __('jamarat_how1'); ?></li>
                                <li><?php echo __('jamarat_how2'); ?></li>
                                <li><?php echo __('jamarat_how3'); ?></li>
                                <li><?php echo __('jamarat_how4'); ?></li>
                                <li><?php echo __('jamarat_how5'); ?></li>
                            </ol>
                        </div>
                        <div class="col-md-4">
                            <img src="<?php echo SITE_URL; ?>/assets/images/jamarat.jpg" alt="<?php echo __('jamarat'); ?>" class="img-fluid rounded">
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo __('jamarat_warning'); ?>
                    </div>
                    
                    <div class="bg-light p-3 rounded">
                        <h6><i class="fas fa-lightbulb me-2"></i> <?php echo __('practical_tips'); ?></h6>
                        <ul class="mb-0">
                            <li><?php echo __('jamarat_tip1'); ?></li>
                            <li><?php echo __('jamarat_tip2'); ?></li>
                            <li><?php echo __('jamarat_tip3'); ?></li>
                            <li><?php echo __('jamarat_tip4'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Sacrifice -->
            <div id="sacrifice" class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-sheep me-2"></i> <?php echo __('sacrifice'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <p><?php echo __('sacrifice_description'); ?></p>
                            
                            <h6><?php echo __('who_must_sacrifice'); ?></h6>
                            <p><?php echo __('who_must_sacrifice_desc'); ?></p>
                            
                            <h6><?php echo __('sacrifice_options'); ?></h6>
                            <ul>
                                <li><?php echo __('sacrifice_option1'); ?></li>
                                <li><?php echo __('sacrifice_option2'); ?></li>
                                <li><?php echo __('sacrifice_option3'); ?></li>
                            </ul>
                            
                            <h6><?php echo __('when_to_sacrifice'); ?></h6>
                            <p><?php echo __('when_to_sacrifice_desc'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body bg-light">
                                    <h6 class="card-title text-center"><?php echo __('authorized_services'); ?></h6>
                                    <p class="card-text"><?php echo __('authorized_services_desc'); ?></p>
                                    <ul>
                                        <li><?php echo __('authorized_service1'); ?></li>
                                        <li><?php echo __('authorized_service2'); ?></li>
                                        <li><?php echo __('authorized_service3'); ?></li>
                                    </ul>
                                    <a href="#" class="btn btn-primary btn-sm d-block"><?php echo __('book_sacrifice_service'); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> <?php echo __('sacrifice_note'); ?>
                    </div>
                </div>
            </div>
            
            <!-- Halq & Taqsir -->
            <div id="halq" class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cut me-2"></i> <?php echo __('halq_taqsir'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <p><?php echo __('halq_description'); ?></p>
                            
                            <h6><?php echo __('difference'); ?></h6>
                            <ul>
                                <li><strong><?php echo __('halq'); ?>:</strong> <?php echo __('halq_meaning'); ?></li>
                                <li><strong><?php echo __('taqsir'); ?>:</strong> <?php echo __('taqsir_meaning'); ?></li>
                            </ul>
                            
                            <h6><?php echo __('rules_for_men'); ?></h6>
                            <p><?php echo __('rules_for_men_desc'); ?></p>
                            
                            <h6><?php echo __('rules_for_women'); ?></h6>
                            <p><?php echo __('rules_for_women_desc'); ?></p>
                            
                            <h6><?php echo __('when_to_perform'); ?></h6>
                            <p><?php echo __('when_to_perform_desc'); ?></p>
                        </div>
                        <div class="col-md-4">
                            <img src="<?php echo SITE_URL; ?>/assets/images/halq.jpg" alt="<?php echo __('halq_taqsir'); ?>" class="img-fluid rounded">
                        </div>
                    </div>
                    
                    <div class="supplication-box p-3 bg-light rounded">
                        <h6 class="text-center"><?php echo __('recommended_dua'); ?></h6>
                        <div class="arabic-text text-center my-3">
                            اللَّهُمَّ اغْفِرْ لِلْمُحَلِّقِينَ
                        </div>
                        <div class="translation text-center">
                            <strong><?php echo __('translation'); ?>:</strong> <?php echo __('halq_dua_translation'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Table of Contents -->
            <div class="card mb-4 sticky-top  text-white" style="top: 2rem; z-index: 10;color:white ">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> <?php echo __('table_of_contents'); ?></h5>
                </div>
                <div class="card-body ">
                    <nav id="toc" class="toc">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="#ihram">
                                    <i class="fas fa-robe me-2"></i> <?php echo __('ihram'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#muzdalifah">
                                    <i class="fas fa-moon me-2"></i> <?php echo __('muzdalifah'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#mina">
                                    <i class="fas fa-campground me-2"></i> <?php echo __('mina'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#jamarat">
                                    <i class="fas fa-place-of-worship me-2"></i> <?php echo __('jamarat'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#sacrifice">
                                    <i class="fas fa-sheep me-2"></i> <?php echo __('sacrifice'); ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#halq">
                                    <i class="fas fa-cut me-2"></i> <?php echo __('halq_taqsir'); ?>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            
            <!-- Hajj Types -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tags me-2"></i> <?php echo __('hajj_types'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="hajjTypes">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="hajjIfrad">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ifradContent" aria-expanded="false">
                                    <?php echo __('hajj_ifrad'); ?>
                                </button>
                            </h2>
                            <div id="ifradContent" class="accordion-collapse collapse" aria-labelledby="hajjIfrad" data-bs-parent="#hajjTypes">
                                <div class="accordion-body">
                                    <p><?php echo __('hajj_ifrad_description'); ?></p>
                                    <ul>
                                        <li><?php echo __('hajj_ifrad_point1'); ?></li>
                                        <li><?php echo __('hajj_ifrad_point2'); ?></li>
                                        <li><?php echo __('hajj_ifrad_point3'); ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="hajjTamattu">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tamattuContent" aria-expanded="false">
                                    <?php echo __('hajj_tamattu'); ?>
                                </button>
                            </h2>
                            <div id="tamattuContent" class="accordion-collapse collapse" aria-labelledby="hajjTamattu" data-bs-parent="#hajjTypes">
                                <div class="accordion-body">
                                    <p><?php echo __('hajj_tamattu_description'); ?></p>
                                    <ul>
                                        <li><?php echo __('hajj_tamattu_point1'); ?></li>
                                        <li><?php echo __('hajj_tamattu_point2'); ?></li>
                                        <li><?php echo __('hajj_tamattu_point3'); ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="hajjQiran">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#qiranContent" aria-expanded="false">
                                    <?php echo __('hajj_qiran'); ?>
                                </button>
                            </h2>
                            <div id="qiranContent" class="accordion-collapse collapse" aria-labelledby="hajjQiran" data-bs-parent="#hajjTypes">
                                <div class="accordion-body">
                                    <p><?php echo __('hajj_qiran_description'); ?></p>
                                    <ul>
                                        <li><?php echo __('hajj_qiran_point1'); ?></li>
                                        <li><?php echo __('hajj_qiran_point2'); ?></li>
                                        <li><?php echo __('hajj_qiran_point3'); ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Additional Resources -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-external-link-alt me-2"></i> <?php echo __('additional_resources'); ?></h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="<?php echo SITE_URL; ?>/rituals/tawaf_guide.php" class="text-decoration-none">
                                <i class="fas fa-circle-notch me-2 text-primary"></i> <?php echo __('tawaf_guide'); ?>
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="<?php echo SITE_URL; ?>/rituals/sai_guide.php" class="text-decoration-none">
                                <i class="fas fa-walking me-2 text-primary"></i> <?php echo __('sai_guide'); ?>
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="<?php echo SITE_URL; ?>/rituals/arafat_guide.php" class="text-decoration-none">
                                <i class="fas fa-mountain me-2 text-primary"></i> <?php echo __('arafat_guide'); ?>
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="<?php echo SITE_URL; ?>/education/prayers.php" class="text-decoration-none">
                                <i class="fas fa-hands me-2 text-primary"></i> <?php echo __('prayers_supplications'); ?>
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="<?php echo SITE_URL; ?>/education/faqs.php" class="text-decoration-none">
                                <i class="fas fa-question-circle me-2 text-primary"></i> <?php echo __('faqs'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Download Guide -->
            <div class="card mb-4 bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-download mb-3" style="font-size: 2rem;"></i>
                    <h5 class="card-title"><?php echo __('download_complete_guide'); ?></h5>
                    <p class="card-text"><?php echo __('download_guide_description'); ?></p>
                    <a href="#" class="btn btn-light">
                        <i class="fas fa-file-pdf me-2"></i> <?php echo __('pdf_guide'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Highlight active TOC link based on scroll position
        const sections = document.querySelectorAll('div[id]');
        const navLinks = document.querySelectorAll('.toc .nav-link');
        
        function highlightNavLink() {
            const scrollPosition = window.scrollY + 100;
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.offsetHeight;
                const sectionId = section.getAttribute('id');
                
                if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                        
                        if (link.getAttribute('href') === '#' + sectionId) {
                            link.classList.add('active');
                        }
                    });
                }
            });
        }
        
        window.addEventListener('scroll', highlightNavLink);
        
        // Smooth scroll for TOC links
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 20,
                        behavior: 'smooth'
                    });
                }
            });
        });
    });
</script>

<style>
    .toc .nav-link {
        color: var(--dark);
        padding: 0.5rem 1rem;
        border-left: 2px solid transparent;
        transition: all 0.2s ease;
    }
    
    .toc .nav-link:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.1);
        border-left-color: rgba(var(--bs-primary-rgb), 0.4);
    }
    
    .toc .nav-link.active {
        color: var(--primary);
        background-color: rgba(var(--bs-primary-rgb), 0.1);
        border-left-color: var(--primary);
        font-weight: 500;
    }
    
    .arabic-text {
        font-family: 'Traditional Arabic', serif;
        font-size: 1.5rem;
        line-height: 2;
        direction: rtl;
    }
    
    @media (max-width: 991.98px) {
        .sticky-top {
            position: static !important;
            color: white;
        }
    }
</style>

<?php
// Include footer
include_once('../includes/footer.php');
?>