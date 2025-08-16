 
<?php
// Include required files
require_once('../includes/functions.php');

// Start session
startSession();

// Check if user is logged in
if (isLoggedIn()) {
    // Get user ID for logging
    $userId = $_SESSION['user_id'];
    
    // Remove remember me token if exists
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        // Delete token from database
        $sql = "DELETE FROM user_tokens WHERE user_id = ? AND token = ?";
        executeQuery($sql, [$userId, $token]);
        
        // Expire cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Log activity
    logActivity($userId, 'logout');
    
    // Destroy session
    session_unset();
    session_destroy();
}

// Redirect to homepage
redirect(SITE_URL);
?>