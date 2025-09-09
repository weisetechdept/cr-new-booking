<?php
session_start();

// Load environment variables
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';

// Log logout event using new logging system
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    logLogout($username, $ip, 'manual');
}

// Clear all session data
session_unset();
session_destroy();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Redirect to login
header("Location: /login?logout=1");
exit;
?>