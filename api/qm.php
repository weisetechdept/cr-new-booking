<?php
session_start();

// Load environment variables
require_once __DIR__ . '/../config/env.php';

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
    session_destroy();
    http_response_code(401);
    echo json_encode(['error' => 'Session expired']);
    exit;
}

// Input validation
function validateDate($date) {
    if (!$date) return false;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
    
    $parts = explode('-', $date);
    if (count($parts) !== 3) return false;
    
    return checkdate($parts[1], $parts[2], $parts[0]);
}

$fmdate = filter_input(INPUT_GET, 'fmdate', FILTER_SANITIZE_STRING);
$todate = filter_input(INPUT_GET, 'todate', FILTER_SANITIZE_STRING);

if (!validateDate($fmdate) || !validateDate($todate)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
    exit;
}

// Check date range
$fmdate_timestamp = strtotime($fmdate);
$todate_timestamp = strtotime($todate);

if ($fmdate_timestamp > $todate_timestamp) {
    http_response_code(400);
    echo json_encode(['error' => 'From date must be before to date']);
    exit;
}

// Log API access
$log_entry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'user' => $_SESSION['username'] ?? 'unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'endpoint' => 'qm',
    'from_date' => $fmdate,
    'to_date' => $todate
];
error_log(json_encode($log_entry), 3, '../logs/api_access.log');

try {
    // Secure API call - endpoint from environment only
    $url = env('API_URL');
    
    if (!$url) {
        throw new Exception('API_URL not configured in environment');
    }
    
    $curl = curl_init($url);
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT => env('API_TIMEOUT', 30),
        CURLOPT_CONNECTTIMEOUT => env('API_CONNECT_TIMEOUT', 10),
        CURLOPT_USERAGENT => 'SecureApp/1.0',
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Content-Type: application/json",
        ]
    ]);

    $data = json_encode([
        'date_from' => $fmdate,
        'date_to' => $todate
    ]);
    
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    $resp = curl_exec($curl);
    
    // Check for cURL errors
    if (curl_error($curl)) {
        throw new Exception('API connection error: ' . curl_error($curl));
    }
    
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($http_code !== 200) {
        throw new Exception("API returned HTTP {$http_code}");
    }
    
    $results = json_decode($resp);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON response from API');
    }
    
    $api = [];
    if ($results) {
        foreach($results as $value){
            // Validate required fields
            if (!isset($value->receiptdate, $value->customername, $value->mobilephone)) {
                continue; // Skip invalid records
            }
            
            $date = date('d/m/Y', strtotime($value->receiptdate) + 25200);
            
            $api['data'][] = [
                htmlspecialchars($date, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($value->customername ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($value->mobilephone ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($value->sale ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($value->manager ?? '', ENT_QUOTES, 'UTF-8'),
                number_format($value->price ?? 0),
                number_format($value->downpayment ?? 0),
                number_format($value->advancepay ?? 0),
                htmlspecialchars($value->model ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($value->cartype ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($value->color ?? '', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($value->jobstatus ?? '', ENT_QUOTES, 'UTF-8')
            ];
        }
    } else {
        $api = ['data' => []];
    }
    
    echo json_encode($api);
    
} catch (Exception $e) {
    // Log error
    error_log("API Error: " . $e->getMessage() . " - User: {$_SESSION['username']} - IP: {$_SERVER['REMOTE_ADDR']}", 3, '../logs/api_errors.log');
    
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>