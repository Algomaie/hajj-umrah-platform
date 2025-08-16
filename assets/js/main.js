// assets/js/main.js

/**
 * Main JavaScript for Hajj & Umrah Smart Platform
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize ritual step accordions
    initializeRitualSteps();
    
    // Handle language switch
    handleLanguageSwitch();
    
    // Handle dark mode toggle
    handleDarkMode();
});

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    
    if (tooltipTriggerList.length > 0) {
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

/**
 * Initialize ritual step accordions
 */
function initializeRitualSteps() {
    const ritualStepHeaders = document.querySelectorAll('.ritual-step-header');
    
    if (ritualStepHeaders.length > 0) {
        ritualStepHeaders.forEach(header => {
            header.addEventListener('click', function() {
                // Toggle active class on content
                const content = this.nextElementSibling;
                content.classList.toggle('active');
                
                // Toggle icon
                const icon = this.querySelector('.fa-chevron-down, .fa-chevron-up');
                
                if (icon) {
                    icon.classList.toggle('fa-chevron-down');
                    icon.classList.toggle('fa-chevron-up');
                }
            });
        });
    }
}

/**
 * Handle language switch
 */
function handleLanguageSwitch() {
    const languageLinks = document.querySelectorAll('a[href*="lang="]');
    
    if (languageLinks.length > 0) {
        languageLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Store current scroll position
                localStorage.setItem('scrollPosition', window.scrollY);
            });
        });
    }
    
    // Restore scroll position after page load if coming from language switch
    const scrollPosition = localStorage.getItem('scrollPosition');
    
    if (scrollPosition) {
        window.scrollTo(0, parseInt(scrollPosition));
        localStorage.removeItem('scrollPosition');
    }
}

/**
 * Handle dark mode toggle
 */
function handleDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    
    if (darkModeToggle) {
        // Check for saved dark mode preference
        const darkMode = localStorage.getItem('darkMode') === 'enabled';
        
        // Apply dark mode if enabled
        if (darkMode) {
            document.body.classList.add('dark-mode');
            darkModeToggle.checked = true;
        }
        
        // Toggle dark mode on change
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('dark-mode');
                localStorage.setItem('darkMode', 'enabled');
            } else {
                document.body.classList.remove('dark-mode');
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    }
}

/**
 * Get user's current location
 * @param {Function} callback - Callback function to handle location
 */
function getCurrentLocation(callback) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            // Success callback
            function(position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                
                callback({
                    success: true,
                    latitude: latitude,
                    longitude: longitude,
                    accuracy: position.coords.accuracy
                });
            },
            // Error callback
            function(error) {
                callback({
                    success: false,
                    error: error.message
                });
            },
            // Options
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        callback({
            success: false,
            error: 'Geolocation is not supported by this browser.'
        });
    }
}

/**
 * Calculate distance between two points
 * @param {number} lat1 - Latitude of first point
 * @param {number} lon1 - Longitude of first point
 * @param {number} lat2 - Latitude of second point
 * @param {number} lon2 - Longitude of second point
 * @returns {number} - Distance in meters
 */
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371e3; // Earth radius in meters
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;
    
    const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ/2) * Math.sin(Δλ/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    
    return R * c; // Distance in meters
}

/**
 * Format distance in a human-readable format
 * @param {number} meters - Distance in meters
 * @returns {string} - Formatted distance
 */
function formatDistance(meters) {
    if (meters < 1000) {
        return Math.round(meters) + ' ' + __('meters');
    } else {
        return (meters / 1000).toFixed(1) + ' ' + __('kilometers');
    }
}

/**
 * Calculate Qibla direction
 * @param {number} latitude - User's latitude
 * @param {number} longitude - User's longitude
 * @returns {number} - Qibla direction in degrees
 */
function calculateQiblaDirection(latitude, longitude) {
    // Kaaba coordinates
    const kaabaLat = 21.4225;
    const kaabaLon = 39.8262;
    
    // Convert to radians
    const φ1 = latitude * Math.PI / 180;
    const φ2 = kaabaLat * Math.PI / 180;
    const Δλ = (kaabaLon - longitude) * Math.PI / 180;
    
    // Calculate direction
    const y = Math.sin(Δλ);
    const x = Math.cos(φ1) * Math.tan(φ2) - Math.sin(φ1) * Math.cos(Δλ);
    
    // Convert to degrees and normalize
    let qiblaDirection = Math.atan2(y, x) * 180 / Math.PI;
    if (qiblaDirection < 0) {
        qiblaDirection += 360;
    }
    
    return qiblaDirection;
}

/**
 * Format date in local format
 * @param {string} dateString - Date string
 * @returns {string} - Formatted date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    
    // Get current language
    const lang = document.documentElement.lang || 'en';
    
    // Format options
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    
    return date.toLocaleDateString(lang === 'ar' ? 'ar-SA' : 'en-US', options);
}

/**
 * Format time in local format
 * @param {string} timeString - Time string
 * @returns {string} - Formatted time
 */
function formatTime(timeString) {
    const date = new Date(`2000-01-01T${timeString}`);
    
    // Get current language
    const lang = document.documentElement.lang || 'en';
    
    // Format options
    const options = {
        hour: '2-digit',
        minute: '2-digit'
    };
    
    return date.toLocaleTimeString(lang === 'ar' ? 'ar-SA' : 'en-US', options);
}

  /* Dark Mode Javascript */
  
  document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    
    // Check system preference
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      document.documentElement.classList.add('dark');
    }
    
    // Listen for changes in system preference
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
      if (event.matches) {
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.classList.remove('dark');
      }
    });
    
    // Toggle between light/dark mode when button is clicked
    themeToggle.addEventListener('click', function() {
      document.documentElement.classList.toggle('dark');
    });
  });
  