<!-- // config/config.php -->

<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hajj_umrah_platform');
define('BASE_URL', 'http://localhost/hajj-umrah-platform');
define('UPLOAD_DIR', '/hajj-umrah-platform/uploads/missing_persons/');
// Site configuration
define('SITE_NAME', 'Hajj & Umrah Smart Platform');
define('SITE_NAME_AR', 'منصة الحج والعمرة الذكية');
define('SITE_URL', 'http://localhost/hajj-umrah-platform');
define('DEFAULT_LANGUAGE', 'en'); // en or ar

// Leaflet map configuration
define('LEAFLET_TILE_URL', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
define('KAABA_LAT', 21.4225);
define('KAABA_LNG', 39.8262);
//define('UPLOAD_PATH', __DIR__ . '/../uploads/');
//define('UPLOAD_URL', '/uploads/');
// File upload paths
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/hajj-umrah-platform/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Session lifetime
define('SESSION_LIFETIME', 3600); // 1 hour in seconds


?>