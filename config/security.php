<?php
/**
 * Security Configuration
 * Contains all security-related settings and functions
 */

// Prevent direct access
if (!defined('SECURE_ACCESS')) {
    die('Direct access not permitted');
}

// Environment configuration
define('ENVIRONMENT', 'production'); // development, staging, production
define('DEBUG_MODE', ENVIRONMENT !== 'production');

// Security settings
ini_set('display_errors', DEBUG_MODE ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Session security
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.regenerate_id', 1);
ini_set('session.gc_maxlifetime', 1800); // 30 minutes

// Hide server information
header_remove('X-Powered-By');
header('Server: SecureServer');

// Rate limiting configuration
define('RATE_LIMIT_REQUESTS', 10);
define('RATE_LIMIT_WINDOW', 60); // seconds
define('RATE_LIMIT_DIR', __DIR__ . '/../logs/rate_limits/');

// API configuration
define('API_TIMEOUT', 30);
define('API_CONNECT_TIMEOUT', 10);
define('MAX_DATE_RANGE_DAYS', 365);

// File upload security (if needed)
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);

// Password requirements
define('MIN_PASSWORD_LENGTH', 8);
define('REQUIRE_SPECIAL_CHARS', true);
define('REQUIRE_NUMBERS', true);
define('REQUIRE_UPPERCASE', true);

/**
 * Security helper functions
 */
class SecurityHelper {
    
    /**
     * Apply security headers
     */
    public static function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    }
    
    /**
     * Set CSP header for different page types
     */
    public static function setCSPHeader($type = 'default') {
        $policies = [
            'default' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self';",
            'admin' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://code.highcharts.com https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self';",
            'api' => "default-src 'none'; connect-src 'self';"
        ];
        
        $policy = $policies[$type] ?? $policies['default'];
        header("Content-Security-Policy: {$policy}");
    }
    
    /**
     * Validate and sanitize input
     */
    public static function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validate date format and range
     */
    public static function validateDate($date, $format = 'Y-m-d') {
        if (!$date) return false;
        
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Check password strength
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < MIN_PASSWORD_LENGTH) {
            $errors[] = "Password must be at least " . MIN_PASSWORD_LENGTH . " characters long";
        }
        
        if (REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if (REQUIRE_SPECIAL_CHARS && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit($identifier, $limit = RATE_LIMIT_REQUESTS, $window = RATE_LIMIT_WINDOW) {
        if (!is_dir(RATE_LIMIT_DIR)) {
            mkdir(RATE_LIMIT_DIR, 0755, true);
        }
        
        $file = RATE_LIMIT_DIR . 'rate_' . md5($identifier) . '.json';
        $current_time = time();
        
        if (file_exists($file)) {
            $requests = json_decode(file_get_contents($file), true) ?: [];
            
            // Remove old requests outside the window
            $requests = array_filter($requests, function($timestamp) use ($current_time, $window) {
                return ($current_time - $timestamp) < $window;
            });
            
            if (count($requests) >= $limit) {
                return false; // Rate limit exceeded
            }
            
            $requests[] = $current_time;
        } else {
            $requests = [$current_time];
        }
        
        file_put_contents($file, json_encode($requests));
        return true; // Rate limit OK
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $details = [], $level = 'INFO') {
        $log_dir = __DIR__ . '/../logs/';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'session_id' => session_id(),
            'details' => $details
        ];
        
        $log_file = $log_dir . 'security_' . date('Y-m-d') . '.log';
        error_log(json_encode($log_entry) . "\n", 3, $log_file);
    }
    
    /**
     * Generate secure random token
     */
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Secure file upload validation
     */
    public static function validateFileUpload($file) {
        $errors = [];
        
        // Check file size
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            $errors[] = 'File size exceeds maximum allowed size';
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, ALLOWED_MIME_TYPES)) {
            $errors[] = 'File type not allowed';
        }
        
        // Check file extension
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $errors[] = 'File extension not allowed';
        }
        
        return empty($errors) ? true : $errors;
    }
}

/**
 * Initialize security settings
 */
function initializeSecurity() {
    // Set security headers
    SecurityHelper::setSecurityHeaders();
    
    // Start secure session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// Auto-initialize if included
if (!defined('NO_AUTO_INIT')) {
    define('SECURE_ACCESS', true);
    initializeSecurity();
}
?>