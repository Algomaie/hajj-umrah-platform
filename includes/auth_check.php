<?php
// Include functions
require_once($_SERVER['DOCUMENT_ROOT'] . '/hajj-umrah-platform/includes/functions.php');

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    setFlashMessage('warning', __('login_required'));
    redirect(SITE_URL . '/auth/login.php');
    exit;
}

// Get current user
$currentUser = getCurrentUser();
if (!$currentUser) {
    session_destroy();
    setFlashMessage('error', __('session_invalid'));
    redirect(SITE_URL . '/auth/login.php');
    exit;
}
error_log("Current user type in auth_check.php: " . ($currentUser['user_type'] ?? 'undefined'));

// Check if role is specified and user has that role
if (isset($requiredRole) && $currentUser['user_type'] !== $requiredRole && $currentUser['user_type'] !== 'admin') {
    setFlashMessage('danger', __('access_denied'));
    redirect(SITE_URL . '/index.php'); // إعادة توجيه إلى index.php
    exit;
}
?>