<?php
// Disable error output for clean JSON response
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

session_start();

// Load environment variables
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';

// Security headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Authentication check
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Session timeout check (30 minutes)
$session_timeout = env('SESSION_TIMEOUT', 1800);
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $session_timeout) {
    if (isset($_SESSION['username'])) {
        logLogout($_SESSION['username'], $_SERVER['REMOTE_ADDR'] ?? 'unknown', 'timeout');
    }
    session_destroy();
    http_response_code(401);
    echo json_encode(['error' => 'Session expired']);
    exit;
}

// Permission check - only admin and manager can view logs
$username = $_SESSION['username'] ?? '';
$allowedUsers = ['admin', 'manager', 'crmanager', 'cremp01', 'cremp02', 'cremp03', 'crlemp04', 'cremp05', 'cremp06', 'cremp07', 'cremp08', 'cremp09', 'cremp10', 'cremp11', 'cremp12'];
if (!in_array($username, $allowedUsers)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Get parameters
$logType = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'auth';
$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?? 100;
$offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT) ?? 0;

// Validate parameters
if (!in_array($logType, ['auth', 'data_access'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid log type']);
    exit;
}

if ($limit > 1000) $limit = 1000; // Max 1000 records
if ($limit < 1) $limit = 100;
if ($offset < 0) $offset = 0;

// Log path
$logPath = env('LOG_PATH', '/var/www/html/logs');
$logFile = $logPath . '/' . $logType . '.log';

try {
    // Read log file
    if (!file_exists($logFile)) {
        echo json_encode(['data' => [], 'total' => 0]);
        exit;
    }

    // Read file content and split by JSON objects
    $content = file_get_contents($logFile);
    if ($content === false) {
        throw new Exception('Cannot read log file');
    }

    // Split by }{ pattern to separate JSON objects
    $lines = [];
    if (trim($content)) {
        // Add newlines between JSON objects if missing
        $content = preg_replace('/}\s*{/', "}\n{", $content);
        $lines = array_filter(explode("\n", $content), function($line) {
            return trim($line) !== '';
        });
    }

    // Reverse to get newest first
    $lines = array_reverse($lines);
    $total = count($lines);

    // Apply offset and limit
    $selectedLines = array_slice($lines, $offset, $limit);

    // Parse JSON lines and prepare for DataTable
    $data = [];
    foreach ($selectedLines as $line) {
        $logEntry = json_decode($line, true);
        if ($logEntry) {
            if ($logType === 'auth') {
                // Mask session ID for security
                $sessionId = $logEntry['session_id'] ?? '';
                $maskedSessionId = $sessionId ? substr($sessionId, 0, 8) . '***' : '';
                
                // Optionally mask IP address (show only first 3 octets for IPv4)
                $ip = $logEntry['ip'] ?? '';
                $maskedIp = $ip;
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $parts = explode('.', $ip);
                    if (count($parts) === 4) {
                        $maskedIp = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.***';
                    }
                }
                
                $data[] = [
                    htmlspecialchars($logEntry['timestamp'] ?? '', ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($logEntry['type'] ?? '', ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($logEntry['username'] ?? '', ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($maskedIp, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($logEntry['success'] ?? $logEntry['reason'] ?? '', ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($maskedSessionId, ENT_QUOTES, 'UTF-8')
                ];
            } else { // data_access
                // Mask session ID for security
                $sessionId = $logEntry['session_id'] ?? '';
                $maskedSessionId = $sessionId ? substr($sessionId, 0, 8) . '***' : '';
                
                // Mask IP address
                $ip = $logEntry['ip'] ?? '';
                $maskedIp = $ip;
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $parts = explode('.', $ip);
                    if (count($parts) === 4) {
                        $maskedIp = $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.***';
                    }
                }
                
                $data[] = [
                    htmlspecialchars($logEntry['timestamp'] ?? '', ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($logEntry['username'] ?? '', ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($maskedIp, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($logEntry['endpoint'] ?? '', ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($logEntry['date_range'] ?? '', ENT_QUOTES, 'UTF-8'),
                    intval($logEntry['record_count'] ?? 0),
                    htmlspecialchars($maskedSessionId, ENT_QUOTES, 'UTF-8')
                ];
            }
        }
    }

    // Log this access
    logDataAccess($username, $_SERVER['REMOTE_ADDR'] ?? 'unknown', 'logs.php', "type={$logType}, offset={$offset}, limit={$limit}", count($data));

    echo json_encode([
        'data' => $data,
        'total' => $total,
        'recordsFiltered' => count($data)
    ]);

} catch (Exception $e) {
    // Log error
    error_log("Log API Error: " . $e->getMessage() . " - User: {$username} - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 3, $logPath . '/api_errors.log');
    
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>