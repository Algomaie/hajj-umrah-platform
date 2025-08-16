<?php

// Include header
include_once('../includes/header.php');
// Set page title
$pageTitle = __('prayer_times');

?>

<section class="prayer-times-section">
    <div class="prayer-header">
        <h2><?php echo __('prayer_times'); ?></h2>
        <div class="location-info">
            <i class="fas fa-map-marker-alt"></i>
            <span id="location-name"><?php echo __('locating'); ?>...</span>
        </div>
    </div>
    
    <div class="date-display">
        <span id="date"><?php echo __('loading_date'); ?>...</span>
    </div>
    
    <div class="prayer-table-container">
        <table class="prayer-times-table">
            <thead>
                <tr>
                    <th><?php echo __('fajr'); ?></th>
                    <th><?php echo __('dhuhr'); ?></th>
                    <th><?php echo __('asr'); ?></th>
                    <th><?php echo __('maghrib'); ?></th>
                    <th><?php echo __('isha'); ?></th>
                </tr>
            </thead>
            <tbody id="prayer-times">
                <tr>
                    <td colspan="5" class="loading"><?php echo __('loading_prayer_times'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="prayer-actions">
        <button class="btn-qibla" onclick="window.location.href='<?php echo SITE_URL; ?>/maps/qibla_direction.php'">
            <i class="fas fa-compass"></i> <?php echo __('qibla_direction'); ?>
        </button>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateElement = document.getElementById('date');
    const prayerTimesElement = document.getElementById('prayer-times');
    const locationElement = document.getElementById('location-name');

    // Display current date in both Hijri and Gregorian
    const today = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDateAr = today.toLocaleDateString('ar-SA', options);
    const formattedDateEn = today.toLocaleDateString('en-US', options);
    dateElement.textContent = `${formattedDateAr} / ${formattedDateEn}`;

    // Get user's location and prayer times
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(position => {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;

            // Get location name
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`)
                .then(response => response.json())
                .then(locationData => {
                    const city = locationData.address.city || locationData.address.town || '';
                    const country = locationData.address.country || '';
                    locationElement.textContent = `${city}, ${country}`;
                })
                .catch(() => {
                    locationElement.textContent = '<?php echo __('current_location'); ?>';
                });

            // Get prayer times from API
            fetch(`https://api.aladhan.com/v1/timings?latitude=${latitude}&longitude=${longitude}&method=2`)
                .then(response => response.json())
                .then(data => {
                    const timings = data.data.timings;
                    prayerTimesElement.innerHTML = `
                        <tr>
                            <td>${formatPrayerTime(timings.Fajr)}</td>
                            <td>${formatPrayerTime(timings.Dhuhr)}</td>
                            <td>${formatPrayerTime(timings.Asr)}</td>
                            <td>${formatPrayerTime(timings.Maghrib)}</td>
                            <td>${formatPrayerTime(timings.Isha)}</td>
                        </tr>
                    `;
                    
                    // Highlight current prayer time
                    highlightCurrentPrayer(timings);
                })
                .catch(error => {
                    console.error('Error fetching prayer times:', error);
                    prayerTimesElement.innerHTML = `
                        <tr>
                            <td colspan="5" class="error"><?php echo __('prayer_times_error'); ?></td>
                        </tr>
                    `;
                });
        }, error => {
            console.error('Error getting location:', error);
            // Fallback to Makkah prayer times
            getMakkahPrayerTimes();
        });
    } else {
        // Browser doesn't support geolocation
        getMakkahPrayerTimes();
    }

    function formatPrayerTime(time) {
        const [hours, minutes] = time.split(':');
        const period = hours >= 12 ? '<?php echo __('pm'); ?>' : '<?php echo __('am'); ?>';
        const adjustedHours = hours % 12 || 12;
        
        // Bilingual time format
        return `
            <span class="time">${adjustedHours}:${minutes} ${period}</span>
            <span class="arabic-time">${adjustedHours}:${minutes} ${hours >= 12 ? 'م' : 'ص'}</span>
        `;
    }

    function highlightCurrentPrayer(timings) {
        const now = new Date();
        const currentTime = now.getHours() * 60 + now.getMinutes();
        
        const prayerTimes = [
            { name: 'Fajr', time: convertTimeToMinutes(timings.Fajr) },
            { name: 'Dhuhr', time: convertTimeToMinutes(timings.Dhuhr) },
            { name: 'Asr', time: convertTimeToMinutes(timings.Asr) },
            { name: 'Maghrib', time: convertTimeToMinutes(timings.Maghrib) },
            { name: 'Isha', time: convertTimeToMinutes(timings.Isha) }
        ];
        
        let currentPrayer = null;
        
        // Find current prayer
        for (let i = 0; i < prayerTimes.length; i++) {
            if (currentTime < prayerTimes[i].time) {
                break;
            }
            currentPrayer = prayerTimes[i];
        }
        
        // Highlight current prayer in table
        const cells = document.querySelectorAll('#prayer-times td');
        if (cells.length === 5 && currentPrayer) {
            const prayerIndex = prayerTimes.findIndex(p => p.name === currentPrayer.name);
            if (prayerIndex >= 0) {
                cells[prayerIndex].classList.add('current-prayer');
                cells[prayerIndex].innerHTML += '<div class="current-indicator"><?php echo __('current'); ?></div>';
            }
        }
    }

    function convertTimeToMinutes(timeStr) {
        const [hours, minutes] = timeStr.split(':').map(Number);
        return hours * 60 + minutes;
    }

    function getMakkahPrayerTimes() {
        locationElement.textContent = 'مكة المكرمة, المملكة العربية السعودية / Makkah, Saudi Arabia';
        
        fetch('https://api.aladhan.com/v1/timingsByCity?city=Makkah&country=SaudiArabia&method=2')
            .then(response => response.json())
            .then(data => {
                const timings = data.data.timings;
                prayerTimesElement.innerHTML = `
                    <tr>
                        <td>${formatPrayerTime(timings.Fajr)}</td>
                        <td>${formatPrayerTime(timings.Dhuhr)}</td>
                        <td>${formatPrayerTime(timings.Asr)}</td>
                        <td>${formatPrayerTime(timings.Maghrib)}</td>
                        <td>${formatPrayerTime(timings.Isha)}</td>
                    </tr>
                `;
                highlightCurrentPrayer(timings);
            })
            .catch(error => {
                console.error('Error fetching Makkah prayer times:', error);
                prayerTimesElement.innerHTML = `
                    <tr>
                        <td colspan="5" class="error"><?php echo __('prayer_times_error'); ?></td>
                    </tr>
                `;
            });
    }
});
</script>

<?php include_once('../includes/footer.php'); ?>