<?php
// Load configuration
require_once($_SERVER['DOCUMENT_ROOT'] . '/hajj-umrah-platform/config/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/hajj-umrah-platform/config/db_connect.php');

// Start session if not already started
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}



// Get current language
function getCurrentLanguage() {
    startSession();
    
    if (isset($_SESSION['lang'])) {
        return $_SESSION['lang'];
    }
    
    return DEFAULT_LANGUAGE;
}

// Set language
function setLanguage($lang) {
    startSession();
    
    if ($lang === 'ar' || $lang === 'en') {
        $_SESSION['lang'] = $lang;
        return true;
    }
    
    return false;
}

// Get translation
function getTranslation($key) {
    $lang = getCurrentLanguage();
    $translations = [];
    
    // Load language file
    $langFile = $_SERVER['DOCUMENT_ROOT'] . '/hajj-umrah-platform/assets/js/lang_' . $lang . '.json';
    
    if (file_exists($langFile)) {
        $translations = json_decode(file_get_contents($langFile), true);
    }
    
    return isset($translations[$key]) ? $translations[$key] : $key;
}

// Translate text
function __($key) {
    return getTranslation($key);
}


// Check user role
function hasRole($role) {
    $user = getCurrentUser();
    
    if (!$user) {
        return false;
    }
    
    return $user['user_type'] === $role;
}

// Redirect to URL
/**
 * Redirect to a URL
 * 
 * @param string $url The URL to redirect to
 * @return void
 */
// function redirect($url) {
//     // Check if headers have been sent
//     if (!headers_sent()) {
//         // Normal redirect - this is the preferred method
//         header("Location: $url");
//         exit; // Stop script execution after redirect
//     } else {
//         // If headers have been sent, use JavaScript as fallback
//         echo '<script>window.location.href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '";</script>';
//         echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></noscript>';
//         echo 'If you are not redirected, please <a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">click here</a>.';
//         exit;
//     }
// }
// // Set flash message
// function setFlashMessage($type, $message) {
//     startSession();
//     $_SESSION['flash'] = [
//         'type' => $type,
//         'message' => $message
//     ];
// }

// // Get flash message
// function getFlashMessage() {
//     startSession();
    
//     if (isset($_SESSION['flash'])) {
//         $flash = $_SESSION['flash'];
//         unset($_SESSION['flash']);
//         return $flash;
//     }
    
//     return null;
// }

// Format date
function formatDate($date, $format = 'Y-m-d H:i:s') {
    $timestamp = strtotime($date);
    
    if ($timestamp === false) {
        return $date;
    }
    
    return date($format, $timestamp);
}

// Calculate distance between two coordinates
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // in meters
    
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);
    
    $dLat = $lat2 - $lat1;
    $dLon = $lon2 - $lon1;
    
    $a = sin($dLat/2) * sin($dLat/2) + cos($lat1) * cos($lat2) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c; // in meters
}

// Format distance
function formatDistance($meters) {
    if ($meters < 1000) {
        return round($meters) . ' ' . __('meters');
    }
    
    return round($meters / 1000, 1) . ' ' . __('kilometers');
}
/**
 * Get icon class for emergency type
 * 
 * @param string $emergencyType The emergency type
 * @return string The icon class
 */
function getEmergencyTypeIcon($emergencyType) {
    switch ($emergencyType) {
        case 'medical':
            return 'fas fa-ambulance';
        case 'missing_person':
            return 'fas fa-user-slash';
        case 'security':
            return 'fas fa-shield-alt';
        default:
            return 'fas fa-exclamation-triangle';
    }
}

/**
 * Format relative time
 * 
 * @param string $dateTime The date/time string
 * @return string The relative time
 */
function relativeTime($dateTime) {
    if (!$dateTime) {
        return '-';
    }
    
    $timestamp = strtotime($dateTime);
    
    if ($timestamp === false) {
        return $dateTime;
    }
    
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return __('just_now');
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' ' . __('minutes_ago');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' ' . __('hours_ago');
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' ' . __('days_ago');
    } else {
        return formatDate($dateTime);
    }
}
// Generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    
    return $data;
}

// Upload file
/**
 * Uploads a file with comprehensive error handling and validation
 * 
 * @param array $file The $_FILES array element
 * @param array $options Configuration options:
 *              - 'allowed_types' => array of MIME types (default: image types)
 *              - 'max_size' => max file size in bytes (default: 5MB)
 *              - 'subfolder' => subdirectory to store in (default: '')
 *              - 'rename' => whether to generate unique filename (default: true)
 * @return array Result array with success status and file info
 */

 function createEmergencyNotification($emergencyId, $emergencyType) {
    // Get authorities
    $authorities = fetchAll("SELECT id FROM users WHERE role = 'authority'");
    
    if (!$authorities) {
        return;
    }
    
    // Create notification for each authority
    foreach ($authorities as $authority) {
        createNotification(
            $authority['id'],
            'emergency',
            __('new_emergency_report'),
            sprintf(__('new_emergency_of_type'), __($emergencyType)),
            ['emergency_id' => $emergencyId]
        );
    }
}

/*

/**
 * Helper function to get an emergency record
 */
function getEmergency($id) {
    $sql = "SELECT * FROM emergencies WHERE id = ? AND type = 'missing_person'";
    return fetchRow($sql, [$id]);
}



/**
 * Helper function to get a missing person with emergency details
 */
function uploadFile($file, $allowedTypes, $maxSize, $subfolder = '') {
    $baseDir = $_SERVER['DOCUMENT_ROOT'] . '/hajj-umrah-platform/uploads/';
    $relativeDir = '/hajj-umrah-platform/uploads/';
    
    // إنشاء المجلد إذا لم يكن موجوداً
    $uploadDir = $baseDir . $subfolder;
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // التحقق من نوع الملف وحجمه
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($detectedType, $allowedTypes)) {
        return ['success' => false, 'message' => 'نوع الملف غير مسموح به'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'حجم الملف يتجاوز الحد المسموح'];
    }

    // إنشاء اسم فريد للملف
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $destination = $uploadDir . '/' . $filename;

    // نقل الملف
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'file_name' => $filename,
            'file_path' => $relativeDir . $subfolder . '/' . $filename // استخدام المسار النسبي فقط
        ];
    }

    return ['success' => false, 'message' => 'فشل في رفع الملف'];
}
// Log activity
function logActivity($userId, $action, $details = null) {
    $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $detailsJson = $details ? json_encode($details) : null;
    
    return insertData($sql, [$userId, $action, $detailsJson, $ip]);
}

function setFlashMessage($type, $message) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message,
        'timestamp' => time()
    ];
}

/**
 * Check if a flash message exists
 * 
 * @return bool True if a flash message exists, false otherwise
 */
function hasFlashMessage() {
    return isset($_SESSION['flash_message']);
}

/**
 * Get the type of the flash message
 * 
 * @return string The message type (success, danger, warning, info)
 */
function getFlashMessageType() {
    if (hasFlashMessage()) {
        return $_SESSION['flash_message']['type'];
    }
    
    return '';
}

/**
 * Get the flash message text
 * 
 * @return string The message text
 */
function getFlashMessage() {
    if (hasFlashMessage()) {
        $message = $_SESSION['flash_message']['message'];
        // Clear the message after retrieving it (unless we're redirecting)
        if (!isset($_SESSION['_flash_keep'])) {
            unset($_SESSION['flash_message']);
        }
        
        return $message;
    }
    
    return '';
}

/**
 * Keep flash messages for one more request (useful for redirects)
 * 
 * @return void
 */
function keepFlashMessage() {
    if (hasFlashMessage()) {
        $_SESSION['_flash_keep'] = true;
    }
}

/**
 * Clear flash message keep flag
 * 
 * @return void
 */
function clearFlashKeep() {
    if (isset($_SESSION['_flash_keep'])) {
        unset($_SESSION['_flash_keep']);
    }
}


/**
 * Core Functions File
 * 
 * Contains all essential functions for the Hajj and Umrah Smart Platform
 * Using MySQLi instead of PDO
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// // Define constants
// define('SITE_URL', 'http://localhost/hajj_umrah_platform'); // Change to your actual URL in production
// define('DEFAULT_LANGUAGE', 'en');
// define('UPLOAD_DIR', __DIR__ . '/../uploads/');
// define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB


/**
 * Delete rows from a table
 * 
 * @param string $table The table name
 * @param string $where The WHERE clause
 * @param array $params Parameters for the WHERE clause
 * @return bool True if successful, false otherwise
 */
function deleteFrom($table, $where, $params = []) {
    try {
        $mysqli = connectDB();
        $query = "DELETE FROM $table WHERE $where";
        
        $stmt = $mysqli->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: {$mysqli->error}");
        }
        
        if (!empty($params)) {
            $types = '';
            $bindParams = [];
            
            // Get param types
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_double($param) || is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
                
                // Store param by reference
                $bindParams[] = &$params[array_search($param, $params)];
            }
            
            // Add types to beginning of the params array
            array_unshift($bindParams, $types);
            
            // Call bind_param with dynamic number of params
            call_user_func_array([$stmt, 'bind_param'], $bindParams);
        }
        
        // Execute the statement
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: {$stmt->error}");
        }
        
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $affectedRows > 0;
    } catch (Exception $e) {
        error_log('Database delete error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Begin a database transaction
 * 
 * @return bool True if successful, false otherwise
 */
function beginTransaction() {
    try {
        $mysqli = connectDB();
        return $mysqli->begin_transaction();
    } catch (Exception $e) {
        error_log('Begin transaction error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Commit a database transaction
 * 
 * @return bool True if successful, false otherwise
 */
function commitTransaction() {
    try {
        $mysqli = connectDB();
        return $mysqli->commit();
    } catch (Exception $e) {
        error_log('Commit transaction error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Rollback a database transaction
 * 
 * @return bool True if successful, false otherwise
 */
function rollbackTransaction() {
    try {
        $mysqli = connectDB();
        return $mysqli->rollback();
    } catch (Exception $e) {
        error_log('Rollback transaction error: ' . $e->getMessage());
        return false;
    }
}

/**
 * =====================================================================
 * AUTHENTICATION FUNCTIONS
 * =====================================================================
 */

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get the current user data
 * 
 * @return array|false The user data or false if not logged in
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = fetchRow("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    
    if (!$user) {
        // User no longer exists or was deleted, clear the session
        logout();
        return false;
    }
    
    return $user;
}

/**
 * Login a user with email and password
 * 
 * @param string $email The user email
 * @param string $password The user password
 * @param bool $remember Whether to remember the login
 * @return array|false The user data or false if login failed
 */
function login($email, $password, $remember = false) {
    $user = fetchRow("SELECT * FROM users WHERE email = ? AND active = 1", [$email]);
    
    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }
    
    // Set user session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['language'] = $user['language'] ?? DEFAULT_LANGUAGE;
    
    // Update last login time
    updateData('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
    
    // Create a remember me token if requested
    if ($remember) {
        createRememberToken($user['id']);
    }
    
    // Log activity
    logActivity($user['id'], 'login', ['ip' => getIpAddress()]);
    
    return $user;
}

/**
 * Create a remember me token
 * 
 * @param int $userId The user ID
 * @return bool True if successful, false otherwise
 */

/**
 * Check and validate remember me token
 * 
 * @return bool True if the token is valid and the user is logged in, false otherwise
 */
function checkRememberToken() {
    if (isLoggedIn() || !isset($_COOKIE['remember_token'])) {
        return false;
    }
    
    $cookieValue = $_COOKIE['remember_token'];
    $parts = explode(':', $cookieValue);
    
    if (count($parts) !== 2) {
        return false;
    }
    
    list($userId, $token) = $parts;
    
    $tokenData = fetchRow("
        SELECT * FROM user_tokens 
        WHERE user_id = ? AND token = ? AND expires > NOW()
    ", [$userId, $token]);
    
    if (!$tokenData) {
        return false;
    }
    
    $user = fetchRow("SELECT * FROM users WHERE id = ? AND active = 1", [$userId]);
    
    if (!$user) {
        return false;
    }
    
    // Set user session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['language'] = $user['language'] ?? DEFAULT_LANGUAGE;
    
    // Update last login time
    updateData('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
    
  
    // Log activity
    logActivity($user['id'], 'login_token', ['ip' => getIpAddress()]);
    
    return true;
}

/**
 * Register a new user
 * 
 * @param array $userData The user data
 * @return int|false The user ID or false if registration failed
 */
function registerUser($userData) {
    // Check if email already exists
    $existing = fetchRow("SELECT id FROM users WHERE email = ?", [$userData['email']]);
    
    if ($existing) {
        return false;
    }
    
    // Hash the password
    $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
    
    // Set default values
    $userData['created_at'] = date('Y-m-d H:i:s');
    $userData['updated_at'] = date('Y-m-d H:i:s');
    
    // Insert the user
    return insertData('users', $userData);
}

/**
 * Logout the current user
 * 
 * @return void
 */
function logout() {
    if (isLoggedIn()) {
        // Delete the remember token if it exists
        if (isset($_COOKIE['remember_token'])) {
            $cookieValue = $_COOKIE['remember_token'];
            $parts = explode(':', $cookieValue);
            
            if (count($parts) === 2) {
                list($userId, $token) = $parts;
                deleteFrom('user_tokens', 'user_id = ? AND token = ?', [$userId, $token]);
            }
            
            // Delete the cookie
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        // Log activity
        logActivity($_SESSION['user_id'], 'logout', ['ip' => getIpAddress()]);
        
        // Clear the session
        session_unset();
        session_destroy();
    }
}
if (!function_exists('bin2hex')) {
    function bin2hex($data) {
        $hex = '';
        for ($i = 0; $i < strlen($data); $i++) {
            $hex .= sprintf('%02x', ord($data[$i]));
        }
        return $hex;
    }
}

/**
 * Create a password reset token
 * 
 * @param string $email The user email
 * @return string|false The reset token or false if the email doesn't exist
 */
function createPasswordResetToken($email) {
    $user = fetchRow("SELECT id FROM users WHERE email = ? AND active = 1", [$email]);
    
    if (!$user) {
        return false;
    }
    
    // Delete any existing tokens for this user
    deleteFrom('password_resets', 'user_id = ?', [$user['id']]);
    
    // Generate a new token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Store the token in the database
    $result = insertData('password_resets', [
        'user_id' => $user['id'],
        'token' => $token,
        'expires' => $expires,
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($result) {
        return $token;
    }
    
    return false;
}

/**
 * Validate a password reset token
 * 
 * @param string $token The reset token
 * @return int|false The user ID or false if the token is invalid
 */
function validatePasswordResetToken($token) {
    $tokenData = fetchRow("
        SELECT user_id FROM password_resets 
        WHERE token = ? AND expires > NOW()
    ", [$token]);
    
    if (!$tokenData) {
        return false;
    }
    
    return $tokenData['user_id'];
}

/**
 * Reset a user's password
 * 
 * @param int $userId The user ID
 * @param string $newPassword The new password
 * @return bool True if successful, false otherwise
 */
function resetPassword($userId, $newPassword) {
    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update the user's password
    $result = updateData('users', [
        'password' => $hashedPassword,
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$userId]);
    
    if ($result) {
        // Delete all password reset tokens for this user
        deleteFrom('password_resets', 'user_id = ?', [$userId]);
        
        // Log activity
        logActivity($userId, 'password_reset', ['ip' => getIpAddress()]);
        
        return true;
    }
    
    return false;
}

// The rest of your code remains the same...

/**
 * =====================================================================
 * USER MANAGEMENT FUNCTIONS
 * =====================================================================
 */

// All functions after this point remain the same, they don't need changes
// since they rely on the database functions we've already converted.
// I've kept them in place but won't repeat them all to save space.

// Just for better closure:

/**
 * Redirect to a URL
 * 
 * @param string $url The URL to redirect to
 * @return void
 */
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    } else {
        echo '<script>window.location.href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        echo 'If you are not redirected, please <a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">click here</a>.';
        exit;
    }
}
/**
 * Get the client IP address
 * 
 * @return string The IP address
 */
function getIpAddress() {
    $ipAddress = '';
    
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipAddress = $_SERVER['HTTP_FORWARDED'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipAddress = 'UNKNOWN';
    }
    
    return $ipAddress;
}

/**
 * Get user language from database
 * 
 * @return string The user's language code
 */
function getUserLanguage() {
    global $mysqli; // Assuming $mysqli connection is available
    
    if (!isLoggedIn()) {
        return DEFAULT_LANGUAGE;
    }
    
    $userId = $_SESSION['user_id'];
    $query = "SELECT language FROM users WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['language'] ?? DEFAULT_LANGUAGE;
    }
    
    return DEFAULT_LANGUAGE;
}
/**
 * Generate avatar URL based on user data (MySQLi version)
 * 
 * @param int $userId The user ID
 * @param int $size The avatar size
 * @return string The avatar URL
 */
function getUserAvatar($userId, $size = 80) {
    global $mysqli;
    
    $query = "SELECT profile_image, full_name FROM users WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (!empty($user['profile_image'])) {
            return SITE_URL . '/uploads/' . $user['profile_image'];
        } else {
            $name = urlencode($user['full_name']);
            $bgColor = substr(md5($userId), 0, 6);
            return "https://ui-avatars.com/api/?name=$name&size=$size&background=$bgColor&color=fff";
        }
    }
    
    // Default avatar if user not found
    return "https://ui-avatars.com/api/?name=Unknown&size=$size&background=000000&color=fff";
}
/**
 * Get current user data (MySQLi version)
 * 
 * @return array|false The user data or false if not logged in
 */
function getServiceTypeIcon($type) {
    global $db;
    $stmt = $db->prepare("SELECT icon_class FROM service_types WHERE name = ?");
    $stmt->execute([$type]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ? $result['icon_class'] : 'fas fa-concierge-bell';
}
function getServiceTypeColor($type) {
    global $db;
    $stmt = $db->prepare("SELECT color_code FROM service_types WHERE name = ?");
    $stmt->execute([$type]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ? $result['color_code'] : '#5a5c69';
}
function getStatusColor($status) {
    global $db;
    $stmt = $db->prepare("SELECT color_code FROM statuses WHERE name = ?");
    $stmt->execute([$status]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ? $result['color_code'] : '#f8f9fc'; 
} 
// function getStatusBadgeClass($status) {
//     global $db;
//     $stmt = $db->prepare("SELECT badge_class FROM statuses WHERE name = ?");
//     $stmt->execute([$status]);
//     $result = $stmt->fetch(PDO::FETCH_ASSOC);

//     return $result ? $result['badge_class'] : 'bg-light text-dark';
// // }
// function getRoleBadgeClass($role) {
//     global $db;
//     $stmt = $db->prepare("SELECT badge_class FROM roles WHERE name = ?");
//     $stmt->execute([$role]);
//     $result = $stmt->fetch(PDO::FETCH_ASSOC);

//     return $result ? $result['badge_class'] : 'bg-secondary'; 
// } 
// function getRoleBadgeClass($role) {
//     switch($role) {
//         case 'admin':
//             return 'danger';
//         case 'authority':
//             return 'warning';
//         case 'guardian':
//             return 'success';
//         case 'pilgrim':
//             return 'primary';
//         default:
//             return 'secondary';
//     }
// }

function getAvatarColor($role) {
    switch($role) {
        case 'admin':
            return 'danger';
        case 'authority':
            return 'warning';
        case 'guardian':
            return 'success';
        case 'pilgrim':
            return 'primary';
        default:
            return 'secondary';
    }
}

function getActivityBadgeClass($action) {
    if (strpos($action, 'create') !== false || strpos($action, 'register') !== false) {
        return 'success';
    } elseif (strpos($action, 'update') !== false || strpos($action, 'edit') !== false) {
        return 'info';
    } elseif (strpos($action, 'delete') !== false) {
        return 'danger';
    } elseif (strpos($action, 'login') !== false) {
        return 'primary';
    } elseif (strpos($action, 'logout') !== false) {
        return 'warning';
    } else {
        return 'secondary';
    }
}
// // Initialize the application
// initializeApp();
function timeSince($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return __('just_now');
    }
    
    $intervals = [
        31536000 => ['year', 'years'],
        2592000 => ['month', 'months'],
        604800 => ['week', 'weeks'],
        86400 => ['day', 'days'],
        3600 => ['hour', 'hours'],
        60 => ['minute', 'minutes'],
    ];
    
    foreach ($intervals as $seconds => $labels) {
        $count = floor($diff / $seconds);
        
        if ($count > 0) {
            $label = $count == 1 ? $labels[0] : $labels[1];
            return $count . ' ' . __($label) . ' ' . __('ago');
        }
    }
    
    return __('just_now');
}

// includes/user_management_functions.php

/**
 * Validate user ID from GET parameter
 */
function validateUserId() {
    $userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$userId) {
        setFlashMessage('danger', __('invalid_user_id'));
        redirect(SITE_URL . '/admin/user_management.php');
        exit;
    }
    
    return $userId;
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    $user = fetchRow("SELECT * FROM users WHERE id = ?", [$userId]);
    
    if (!$user) {
        setFlashMessage('danger', __('user_not_found'));
        redirect(SITE_URL . '/admin/user_management.php');
        exit;
    }
    
    return $user;
}

/**
 * Validate user data from form submission
 */
function validateUserData($data, $isCreate = false) {
    $errors = [];
    
    // Required fields
    if (empty($data['full_name'])) {
        $errors[] = __('full_name_required');
    }
    
    if (empty($data['email'])) {
        $errors[] = __('email_required');
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('invalid_email');
    }
    
    // For create action, password is required
    if ($isCreate) {
        if (empty($data['password'])) {
            $errors[] = __('password_required');
        } elseif (strlen($data['password']) < 8) {
            $errors[] = __('password_min_length');
        }
    }
    
    // Validate role
    $allowedRoles = array_keys(getAllowedRoles());
    if (empty($data['user_type']) || !in_array($data['user_type'], $allowedRoles)) {
        $errors[] = __('invalid_role');
    }
    
    // Check email uniqueness
    if (!empty($data['email'])) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $params = [$data['email']];
        
        if (!$isCreate) {
            $sql .= " AND id != ?";
            $params[] = $data['id'];
        }
        
        $existingUser = fetchRow($sql, $params);
        
        if ($existingUser) {
            $errors[] = __('email_exists');
        }
    }
    
    return $errors;
}

/**
 * Update user in database
 */
function updateUser($userId, $data) {
    $sql = "UPDATE users SET 
            full_name = ?, 
            email = ?, 
            phone = ?, 
            user_type = ?, 
            active = ?,
            updated_at = NOW()";
    
    $params = [
        sanitizeInput($data['full_name']),
        sanitizeInput($data['email']),
        sanitizeInput($data['phone'] ?? ''),
        sanitizeInput($data['user_type']),
        isset($data['active']) && $data['active'] === '1' ? 1 : 0
    ];
    
    // Update password if provided
    if (!empty($data['password'])) {
        $sql .= ", password = ?";
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $userId;
    
    return updateData($sql, $params);
}

/**
 * Create new user in database
 */
function createUser($data) {
    $sql = "INSERT INTO users (
            full_name, 
            email, 
            password, 
            phone, 
            nationality, 
            user_type, 
            active,
            created_at, 
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";
    
    return insertData($sql, [
        sanitizeInput($data['full_name']),
        sanitizeInput($data['email']),
        password_hash($data['password'], PASSWORD_DEFAULT),
        sanitizeInput($data['phone'] ?? ''),
        sanitizeInput($data['nationality'] ?? ''),
        sanitizeInput($data['user_type'])
    ]);
}

/**
 * Delete user from database
 */
function deleteUser($userId) {
    // First delete related records to maintain referential integrity
    // Example: delete from other tables where user_id = ?
    // executeQuery("DELETE FROM user_permissions WHERE user_id = ?", [$userId]);
    
    // Then delete the user
    $sql = "DELETE FROM users WHERE id = ?";
    return executeQuery($sql, [$userId]);
}

/**
 * Get users with pagination
 */
function getUsersWithPagination($search, $role, $status, $page, $perPage) {
    $whereConditions = [];
    $params = [];
    
    if (!empty($search)) {
        $whereConditions[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($role)) {
        $whereConditions[] = "user_type = ?";
        $params[] = $role;
    }
    
    if ($status === 'active') {
        $whereConditions[] = "active = 1";
    } elseif ($status === 'inactive') {
        $whereConditions[] = "active = 0";
    }
    
    $whereClause = empty($whereConditions) ? '' : "WHERE " . implode(" AND ", $whereConditions);
    
    // Count total users
    $countSql = "SELECT COUNT(*) as total FROM users $whereClause";
    $totalResult = fetchRow($countSql, $params);
    $total = $totalResult ? $totalResult['total'] : 0;
    
    // Calculate pagination
    $offset = ($page - 1) * $perPage;
    $totalPages = ceil($total / $perPage);
    
    // Get users
    $sql = "SELECT * FROM users
            $whereClause
            ORDER BY created_at DESC
            LIMIT $offset, $perPage";
    
    $users = fetchAll($sql, $params);
    
    return [
        'users' => $users,
        'total' => $total,
        'totalPages' => $totalPages
    ];
}

/**
 * Get user activity logs
 */
function getUserActivity($userId, $limit = 50) {
    return fetchAll("
        SELECT * FROM activity_logs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ", [$userId, $limit]);
}

/**
 * Get user login history
 */
function getLoginHistory($userId, $limit = 10) {
    return fetchAll("
        SELECT * FROM activity_logs 
        WHERE user_id = ? AND action = 'login' 
        ORDER BY created_at DESC 
        LIMIT ?
    ", [$userId, $limit]);
}

/**
 * Get allowed user roles with their display labels
 */
function getAllowedRoles() {
    return [
        'pilgrim' => __('pilgrim'),
        'guardian' => __('guardian'),
        'authority' => __('authority'),
        'admin' => __('admin')
    ];
}

/**
 * Get list of nationalities for dropdown
 */
function getNationalities() {
    return [
        'Saudi Arabia',
        'Egypt',
        'Pakistan',
        'India',
        'Indonesia',
        'Malaysia',
        'Turkey',
        'United States',
        'United Kingdom',
        'Other'
    ];
}

/**
 * Get user information fields for display
 */
function getUserInfoFields() {
    return [
        'id' => __('id'),
        'full_name' => __('full_name'),
        'email' => __('email'),
        'phone' => __('phone'),
        'nationality' => __('nationality'),
        'language' => __('language'),
        'created_at' => __('registration_date'),
        'updated_at' => __('last_update')
    ];
}

/**
 * Format user field for display
 */
function formatUserField($user, $field) {
    switch ($field) {
        case 'phone':
            return htmlspecialchars($user[$field] ?: __('not_provided'));
        case 'nationality':
            return htmlspecialchars($user[$field] ?: __('not_provided'));
        case 'language':
            return strtoupper($user[$field] ?: 'EN');
        case 'created_at':
        case 'updated_at':
            return formatDate($user[$field]);
        default:
            return htmlspecialchars($user[$field] ?? '');
    }
}

/**
 * Format activity details for display
 */
function formatActivityDetails($detailsJson) {
    $details = json_decode($detailsJson, true);
    if (!$details) {
        return '-';
    }
    
    $output = '';
    foreach ($details as $key => $value) {
        $output .= '<small class="d-block">' . 
                  htmlspecialchars($key) . ': ' . 
                  htmlspecialchars(is_array($value) ? json_encode($value) : $value) . 
                  '</small>';
    }
    
    return $output;
}

/**
 * Fetch a single value from database
 * 
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters to bind
 * @return mixed|false Returns the value or false on failure
 */
function fetchValue($sql, $params = []) {
    global $pdo;
    
    try {
        $result = executeQuery($sql, $params);
    
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("DB Error in fetchValue(): " . $e->getMessage());
        return false;
    }
}

// Usage example:
/**
 * Get avatar color based on user role
 */
// function getAvatarColor($role) {
//     $colors = [
//         'pilgrim' => 'primary',
//         'guardian' => 'success',
//         'authority' => 'warning',
//         'admin' => 'danger'
//     ];
    
//     return $colors[$role] ?? 'secondary';
// }

/**
 * Get badge class based on user role
 */
function getRoleBadgeClass($role) {
    $classes = [
        'pilgrim' => 'primary',
        'guardian' => 'success',
        'authority' => 'warning',
        'admin' => 'danger'
    ];
    
    return $classes[$role] ?? 'secondary';
}

/**
 * Get badge class based on activity type
 */
// function getActivityBadgeClass($action) {
//     $classes = [
//         'login' => 'success',
//         'logout' => 'secondary',
//         'update_profile' => 'info',
//         'create_user' => 'primary',
//         'update_user' => 'warning',
//         'delete_user' => 'danger'
//     ];
    
//     return $classes[$action] ?? 'light';
// }

/**
 * Build pagination URL with current filters
 */
function buildPaginationUrl($page) {
    global $search, $role, $status;
    
    $params = [
        'page' => $page,
        'search' => $search,
        'user_type' => $role,
        'status' => $status
    ];
    
    return SITE_URL . '/admin/user_management.php?' . http_build_query($params);
}
/**
 * Generate a unique invite code
 */
function generateInviteCode($length = 8) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    $exists = fetchRow("SELECT 1 FROM groups WHERE invite_code = ?", [$code]);
    return $exists ? generateInviteCode($length) : $code;
}

/**
 * Generate a UUID
 */
function generateUUID() {
    return fetchRow("SELECT UUID() as uuid")['uuid'];
}
?>