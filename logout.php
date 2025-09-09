<?php
session_start();

// Log logout event
if (isset($_SESSION['username'])) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => 'user_logout',
        'user' => $_SESSION['username'],
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    error_log(json_encode($log_entry), 3, 'security.log');
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
header("Location: login.php?logout=1");
exit;
?>