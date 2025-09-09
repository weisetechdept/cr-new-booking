<?php
session_start();

// Load environment variables
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Redirect if already logged in
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header("Location: /dashboard");
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Validate credentials using new auth system
    if (validateUser($username, $password)) {
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['login_time'] = time();
        
        // Log successful login
        logAuthAttempt($username, $ip, true, $userAgent);
        
        header("Location: /dashboard");
        exit;
    } else {
        $error_message = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        
        // Log failed login attempt
        logAuthAttempt($username ?: 'unknown', $ip, false, $userAgent);
        
        // Add delay to prevent brute force
        sleep(2);
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>เข้าสู่ระบบ - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- App favicon -->
    <link rel="shortcut icon" href="../../assets/images/favicon.ico">
    
    <!-- App css -->
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="../../assets/css/icons.min.css" rel="stylesheet" type="text/css">
    <link href="../../assets/css/theme.min.css" rel="stylesheet" type="text/css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Chakra Petch', sans-serif;
        }
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 6px;
        }
        .alert {
            border-radius: 6px;
        }
    </style>
</head>

<body>
    <div class="auth-page">
        <div class="auth-box">
            <div class="text-center mb-4">
                <h2 class="text-primary">Alpha X</h2>
                <p class="text-muted">เข้าสู่ระบบจัดการข้อมูล</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">ชื่อผู้ใช้</label>
                    <input type="text" class="form-control" id="username" name="username" required 
                           maxlength="50" autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input type="password" class="form-control" id="password" name="password" required 
                           maxlength="100" autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">
                    เข้าสู่ระบบ
                </button>
            </form>
            
            <div class="text-center mt-3">
                <small class="text-muted">© 2023 Weise Tech - Secure Login</small>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="../../assets/js/jquery.min.js"></script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Clear form on page load to prevent auto-fill issues
        window.addEventListener('load', function() {
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
        });
        
        // Prevent multiple form submissions
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitBtn = document.querySelector('.btn-login');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'กำลังเข้าสู่ระบบ...';
        });
    </script>
</body>
</html>