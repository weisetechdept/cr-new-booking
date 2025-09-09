<?php
// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

$error_code = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_NUMBER_INT);
$error_code = $error_code ?: 404;

$error_messages = [
    400 => 'คำขอไม่ถูกต้อง',
    401 => 'ไม่ได้รับอนุญาต',
    403 => 'ถูกปฏิเสธการเข้าถึง',
    404 => 'ไม่พบหน้าที่ต้องการ',
    500 => 'เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์'
];

$error_title = $error_messages[$error_code] ?? 'เกิดข้อผิดพลาด';

// Log error access
$log_entry = [
    'timestamp' => date('Y-m-d H:i:s'),
    'event' => 'error_page_access',
    'error_code' => $error_code,
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    'referer' => $_SERVER['HTTP_REFERER'] ?? ''
];
error_log(json_encode($log_entry), 3, 'error_access.log');

http_response_code($error_code);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>Error <?= $error_code ?> - Alpha X</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Arial', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 40px;
            text-align: center;
            max-width: 500px;
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }
        .error-message {
            color: #666;
            margin-bottom: 30px;
        }
        .btn-home {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
        }
        .btn-home:hover {
            background: #5a67d8;
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-code"><?= $error_code ?></div>
        <div class="error-title"><?= $error_title ?></div>
        <div class="error-message">
            ขออภัย เกิดข้อผิดพลาดในการเข้าถึงหน้าที่คุณต้องการ
        </div>
        <a href="/login" class="btn-home">กลับสู่หน้าหลัก</a>
    </div>
</body>
</html>