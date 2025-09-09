<?php
/**
 * Authentication and User Management Functions
 */

/**
 * Parse users from environment variable
 * @return array Associative array of username => hashed_password
 */
function parseUsers() {
    $users = [];
    $useHashedPasswords = env('USE_HASHED_PASSWORDS', 'false') === 'true';
    $appSecret = env('APP_SECRET', '');
    
    // Try new USERS format first
    $usersString = env('USERS');
    if ($usersString) {
        // Smart parsing to handle different delimiters
        if (strpos($usersString, '|') !== false) {
            // Using pipe delimiter to avoid Argon2ID comma conflicts
            $userPairs = explode('|', $usersString);
        } elseif ($useHashedPasswords && strpos($usersString, '$argon2id$') !== false) {
            // For hashed passwords with comma delimiter, split more carefully
            $userPairs = preg_split('/(?<=\w),(?=\w+:)/', $usersString);
        } else {
            // For plaintext passwords, simple comma split
            $userPairs = explode(',', $usersString);
        }
        
        foreach ($userPairs as $pair) {
            $pair = trim($pair);
            if (strpos($pair, ':') !== false) {
                list($username, $password) = explode(':', $pair, 2);
                $username = trim($username);
                $password = trim($password);
                if ($username && $password) {
                    if ($useHashedPasswords) {
                        // Password is already hashed
                        $users[$username] = $password;
                    } else {
                        // Hash the plaintext password with app secret as salt
                        $saltedPassword = $password . $appSecret;
                        $users[$username] = password_hash($saltedPassword, PASSWORD_ARGON2ID, [
                            'memory_cost' => 65536,
                            'time_cost' => 4,
                            'threads' => 3
                        ]);
                    }
                }
            }
        }
    }
    
    // Fallback to legacy format if no users found
    if (empty($users)) {
        $adminPassword = env('ADMIN_PASSWORD');
        $managerPassword = env('MANAGER_PASSWORD');
        
        if ($adminPassword) {
            if ($useHashedPasswords) {
                $users['admin'] = $adminPassword;
            } else {
                $saltedPassword = $adminPassword . $appSecret;
                $users['admin'] = password_hash($saltedPassword, PASSWORD_ARGON2ID, [
                    'memory_cost' => 65536,
                    'time_cost' => 4,
                    'threads' => 3
                ]);
            }
        }
        if ($managerPassword) {
            if ($useHashedPasswords) {
                $users['manager'] = $managerPassword;
            } else {
                $saltedPassword = $managerPassword . $appSecret;
                $users['manager'] = password_hash($saltedPassword, PASSWORD_ARGON2ID, [
                    'memory_cost' => 65536,
                    'time_cost' => 4,
                    'threads' => 3
                ]);
            }
        }
    }
    
    return $users;
}

/**
 * Validate user credentials
 * @param string $username
 * @param string $password
 * @return bool
 */
function validateUser($username, $password) {
    if (!$username || !$password) {
        return false;
    }
    
    $users = parseUsers();
    $appSecret = env('APP_SECRET', '');
    $useHashedPasswords = env('USE_HASHED_PASSWORDS', 'false') === 'true';
    
    if (isset($users[$username])) {
        if ($useHashedPasswords) {
            // Password in env is already hashed - verify directly
            return password_verify($password . $appSecret, $users[$username]);
        } else {
            // Password in env was plaintext - verify against the hashed version
            return password_verify($password . $appSecret, $users[$username]);
        }
    }
    
    return false;
}

/**
 * Get list of all usernames
 * @return array
 */
function getAllUsernames() {
    return array_keys(parseUsers());
}

/**
 * Log authentication attempt
 * @param string $username
 * @param string $ip
 * @param bool $success
 * @param string $userAgent
 */
function logAuthAttempt($username, $ip, $success, $userAgent = '') {
    if (!env('LOG_LOGIN_ATTEMPTS', true)) {
        return;
    }
    
    // Set timezone to Bangkok
    date_default_timezone_set('Asia/Bangkok');
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => 'auth_attempt',
        'username' => $username,
        'ip' => $ip,
        'success' => $success ? 'SUCCESS' : 'FAILED',
        'user_agent' => $userAgent,
        'session_id' => session_id()
    ];
    
    $logMessage = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
    error_log($logMessage, 3, env('LOG_PATH', '/var/www/html/logs') . '/auth.log');
}

/**
 * Log user logout
 * @param string $username
 * @param string $ip
 * @param string $reason
 */
function logLogout($username, $ip, $reason = 'manual') {
    if (!env('LOG_LOGIN_ATTEMPTS', true)) {
        return;
    }
    
    // Set timezone to Bangkok
    date_default_timezone_set('Asia/Bangkok');
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => 'logout',
        'username' => $username,
        'ip' => $ip,
        'reason' => $reason,
        'session_id' => session_id()
    ];
    
    $logMessage = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
    error_log($logMessage, 3, env('LOG_PATH', '/var/www/html/logs') . '/auth.log');
}

/**
 * Log data access
 * @param string $username
 * @param string $ip
 * @param string $endpoint
 * @param string $dateRange
 * @param int $recordCount
 */
function logDataAccess($username, $ip, $endpoint, $dateRange = '', $recordCount = 0) {
    if (!env('LOG_DATA_ACCESS', true)) {
        return;
    }
    
    // Set timezone to Bangkok
    date_default_timezone_set('Asia/Bangkok');
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => 'data_access',
        'username' => $username,
        'ip' => $ip,
        'endpoint' => $endpoint,
        'date_range' => $dateRange,
        'record_count' => $recordCount,
        'session_id' => session_id(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ];
    
    $logMessage = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
    error_log($logMessage, 3, env('LOG_PATH', '/var/www/html/logs') . '/data_access.log');
}
?>