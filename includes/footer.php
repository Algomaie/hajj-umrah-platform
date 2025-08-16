<!-- // includes/footer.php -->
</main>
    
    <!-- Footer -->
    <footer class="py-3 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-kaaba me-2"></i>
                        <h5 class="mb-0"><?php echo __('site_name'); ?></h5>
                    </div>
                    <p class="small mb-0"><?php echo __('platform_description'); ?></p>
                </div>
                
                <div class="col-md-4 mb-3 mb-md-0">
                    <h5 class="h6 mb-3"><?php echo __('quick_links'); ?></h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-1"><a href="<?php echo SITE_URL; ?>" class="text-decoration-none"><?php echo __('home'); ?></a></li>
                        <li class="mb-1"><a href="<?php echo SITE_URL; ?>/maps/interactive_map.php" class="text-decoration-none"><?php echo __('interactive_map'); ?></a></li>
                        <li class="mb-1"><a href="<?php echo SITE_URL; ?>/rituals/tawaf_guide.php" class="text-decoration-none"><?php echo __('rituals'); ?></a></li>
                        <li class="mb-1"><a href="<?php echo SITE_URL; ?>/services/missing_report.php" class="text-decoration-none"><?php echo __('emergency'); ?></a></li>
                    </ul>
                </div>
                
                <div class="col-md-4">
                    <h5 class="h6 mb-3"><?php echo __('emergency_contact'); ?></h5>
                    <ul class="list-unstyled small mb-3">
                        <li class="mb-1"><i class="fas fa-phone-alt fa-fw me-2"></i> <a href="tel:911" class="emergency-number">911</a></li>
                        <li class="mb-1"><i class="fas fa-ambulance fa-fw me-2"></i> <a href="tel:937" class="emergency-number">937</a> (<?php echo __('medical'); ?>)</li>
                        <li class="mb-1"><i class="fas fa-info-circle fa-fw me-2"></i> <a href="tel:920002814" class="emergency-number">920002814</a> (<?php echo __('hajj_info'); ?>)</li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-3">
            
            <div class="row">
                <div class="col-md-6 mb-2 mb-md-0">
                    <p class="small mb-0">&copy; <?php echo date('Y'); ?> <?php echo __('site_name'); ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="small mb-0">
                        <?php echo __('designed_by'); ?> <a href="#" class="designer-link"><?php echo __('developer_name'); ?></a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($useLeaflet) && $useLeaflet): ?>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <?php endif; ?>
    
    <!-- App JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    
    <!-- Translations -->
    <script>
    const translations = <?php echo json_encode(array_merge(
        json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/hajj-umrah-platform/assets/lang/'.($lang === 'ar' ? 'ar' : 'en').'.json'), true)
    )); ?>;
    
    function __(key) {
        return translations[key] || key;
    }
    </script>
    
    <!-- Custom dark mode support for footer -->
    <style>
        /* Footer-specific dark mode styling */
        footer {
            background-color: var(--dark);
            color: white;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        footer p, footer .small {
            color: var(--dm-text-muted, rgba(255, 255, 255, 0.6));
        }
        
        footer a, footer a:visited {
            color: var(--dm-text-muted, rgba(255, 255, 255, 0.6));
            transition: color 0.3s ease;
        }
        
        footer a:hover {
            color: white;
            text-decoration: none;
        }
        
        footer hr {
            background-color: rgba(255, 255, 255, 0.1);
            opacity: 0.2;
        }
        
        .emergency-number {
            color: var(--danger, #EF4444);
            font-weight: 600;
        }
        
        .designer-link {
            color: var(--secondary, #26a69a);
        }
        
        /* Add a subtle border in dark mode */
        .dark footer {
            border-top: 1px solid var(--dm-border-color, #444);
        }
    </style>
    
    <?php if (isset($extraJS)) echo $extraJS; ?>
</body>
</html>