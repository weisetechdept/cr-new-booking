<?php
/**
 * Password Hash Generator for CRL Booking System
 * This page helps generate password hashes for .env configuration
 */
session_start();

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Load environment variables
require_once __DIR__ . '/../../config/env.php';

// Check if this is production environment
if (env('APP_ENV') === 'production') {
    http_response_code(404);
    exit('Page not found');
}

$generated_hash = '';
$username = '';
$password = '';
$app_secret = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST['password'] ?? '';
    $app_secret = filter_input(INPUT_POST, 'app_secret', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    if ($password && $app_secret) {
        // Use app secret as salt for more security
        $salted_password = $password . $app_secret;
        $generated_hash = password_hash($salted_password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>Password Generator - CRL Booking System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Bootstrap CSS -->
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="../../assets/css/icons.min.css" rel="stylesheet" type="text/css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 20px 0;
        }
        .generator-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .form-container {
            padding: 40px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-generate {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 8px;
            color: white;
            padding: 12px 30px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-generate:hover {
            transform: translateY(-2px);
            color: white;
        }
        .result-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        .hash-output {
            background: #343a40;
            color: #00ff41;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            word-break: break-all;
            border: none;
            resize: none;
        }
        .alert-warning {
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }
        .copy-btn {
            background: #28a745;
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .copy-btn:hover {
            background: #218838;
            color: white;
        }
    </style>
</head>
<body>
    <div class="generator-container">
        <div class="header">
            <h2><i class="mdi mdi-shield-key"></i> Password Hash Generator</h2>
            <p class="mb-0">สร้างรหัสผ่านที่เข้ารหัสแล้วสำหรับ .env configuration</p>
        </div>
        
        <div class="form-container">
            <div class="alert alert-warning">
                <strong>⚠️ คำเตือน:</strong> หน้านี้ใช้สำหรับ development เท่านั้น<br>
                ใน production environment หน้านี้จะถูกปิดการใช้งาน
            </div>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="username">ชื่อผู้ใช้ (Username)</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= htmlspecialchars($username) ?>" 
                                   placeholder="admin, manager, sales...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password">รหัสผ่าน (Password)</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   value="<?= htmlspecialchars($password) ?>"
                                   placeholder="รหัสผ่านที่ต้องการเข้ารหัส" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="app_secret">App Secret Key</label>
                    <input type="password" class="form-control" id="app_secret" name="app_secret" 
                           value="<?= htmlspecialchars($app_secret) ?>"
                           placeholder="ใส่ APP_SECRET จาก .env ของคุณ" required>
                    <small class="text-muted">
                        ใช้เป็น salt เพื่อความปลอดภัยเพิ่มเติม (ควรเก็บไว้ใน APP_SECRET ใน .env)
                    </small>
                </div>
                
                <button type="submit" class="btn btn-generate">
                    <i class="mdi mdi-key-variant"></i> Generate Password Hash
                </button>
            </form>
            
            <?php if ($generated_hash): ?>
            <div class="result-container">
                <h5><i class="mdi mdi-check-circle text-success"></i> Generated Hash</h5>
                
                <div class="form-group">
                    <label>Hashed Password:</label>
                    <textarea class="hash-output" rows="4" readonly id="hashedPassword"><?= htmlspecialchars($generated_hash) ?></textarea>
                    <button type="button" class="copy-btn" onclick="copyToClipboard('hashedPassword')">
                        <i class="mdi mdi-content-copy"></i> Copy Hash
                    </button>
                </div>
                
                <?php if ($username): ?>
                <div class="form-group">
                    <label>สำหรับใส่ใน .env (USERS format):</label>
                    <textarea class="hash-output" rows="2" readonly id="envFormat"><?= $username ?>:<?= htmlspecialchars($generated_hash) ?></textarea>
                    <button type="button" class="copy-btn" onclick="copyToClipboard('envFormat')">
                        <i class="mdi mdi-content-copy"></i> Copy ENV Format
                    </button>
                </div>
                <?php endif; ?>
                
                <div class="alert alert-info mt-3">
                    <strong>วิธีใช้:</strong><br>
                    1. Copy hash ที่ได้<br>
                    2. เปิดไฟล์ .env<br>
                    3. แทนที่รหัสผ่าน plaintext ด้วย hash ที่ได้<br>
                    4. เซต USE_HASHED_PASSWORDS=true ใน .env<br>
                    5. เซต APP_SECRET=your_secret_key ใน .env
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="../../assets/js/jquery.min.js"></script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            element.select();
            element.setSelectionRange(0, 99999);
            
            try {
                document.execCommand('copy');
                
                // Show success feedback
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="mdi mdi-check"></i> Copied!';
                btn.style.background = '#28a745';
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.style.background = '#28a745';
                }, 2000);
            } catch (err) {
                console.error('Failed to copy text: ', err);
            }
        }
        
        // Generate random app secret
        function generateAppSecret() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
            let result = '';
            for (let i = 0; i < 32; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('app_secret').value = result;
        }
    </script>
</body>
</html>