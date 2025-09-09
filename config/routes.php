<?php
/**
 * URL Route Helpers
 * Helper functions for clean URL routing
 */

/**
 * Get clean URL for a route
 * @param string $route Route name (login, dashboard, logs, etc.)
 * @param array $params Optional URL parameters
 * @return string Clean URL
 */
function route($route, $params = []) {
    $routes = [
        'login' => '/login',
        'logout' => '/logout', 
        'dashboard' => '/dashboard',
        'logs' => '/logs',
        'password-generator' => '/password-generator'
    ];
    
    $url = $routes[$route] ?? '/';
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * Redirect to a clean route
 * @param string $route Route name
 * @param array $params Optional parameters
 */
function redirect($route, $params = []) {
    $url = route($route, $params);
    header("Location: $url");
    exit;
}

/**
 * Get current route name
 * @return string Current route name
 */
function currentRoute() {
    $path = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($path, PHP_URL_PATH);
    
    $routeMap = [
        '/login' => 'login',
        '/logout' => 'logout',
        '/dashboard' => 'dashboard', 
        '/logs' => 'logs',
        '/password-generator' => 'password-generator'
    ];
    
    return $routeMap[$path] ?? 'unknown';
}

/**
 * Check if current route matches
 * @param string $route Route name to check
 * @return bool True if matches
 */
function isRoute($route) {
    return currentRoute() === $route;
}

/**
 * Get base URL
 * @return string Base URL
 */
function baseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return "$protocol://$host";
}
?>