<?php
/**
 * Simple Environment Variables Loader
 * Loads .env file variables into $_ENV and getenv()
 */

function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception(".env file not found at: $path");
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
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
}

// Load .env file
try {
    loadEnv(__DIR__ . '/../.env');
} catch (Exception $e) {
    error_log("Environment loading error: " . $e->getMessage());
    // Set default values if .env not found
    $_ENV['ADMIN_PASSWORD'] = 'crl2024!@#';
    $_ENV['MANAGER_PASSWORD'] = 'mgr2024!@#';
}

/**
 * Get environment variable with default value
 */
function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}
?>