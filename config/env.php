<?php
/**
 * Simple Environment Variables Loader
 * Loads .env file variables into $_ENV and getenv()
 */

function loadEnv($path) {
    if (!file_exists($path) || is_dir($path)) {
        return false; // Don't throw exception, just return false
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return false;
    }
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Set environment variable
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    return true;
}

// Load .env file
$envLoaded = loadEnv(__DIR__ . '/../.env');
if (!$envLoaded) {
    // Set default values if .env not found or failed to load
    $_ENV['ADMIN_PASSWORD'] = 'crl2024!@#';
    $_ENV['MANAGER_PASSWORD'] = 'mgr2024!@#';
    $_ENV['SESSION_TIMEOUT'] = '1800';
    $_ENV['API_TIMEOUT'] = '30';
    $_ENV['API_CONNECT_TIMEOUT'] = '10';
    $_ENV['RATE_LIMIT_REQUESTS'] = '10';
    $_ENV['RATE_LIMIT_WINDOW'] = '60';
    $_ENV['MAX_DATE_RANGE_DAYS'] = '365';
}

/**
 * Get environment variable with default value
 */
function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}
?>