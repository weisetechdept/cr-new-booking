# User Management & Logging System

## เพิ่ม Users ใหม่

### วิธี 1: แก้ไขในไฟล์ .env
```bash
# รองรับหลาย users ในรูปแบบ username:password
USERS=admin:123456,manager:123456,sales:sales123,viewer:viewer123,accountant:acc456
```

### วิธี 2: ใน Docker environment
```yaml
environment:
  - USERS=admin:123456,manager:123456,sales:sales123
```

## การ Login ที่รองรับ

ระบบตอนนี้รองรับ users ดังนี้:
- **admin**: รหัส `123456` 
- **manager**: รหัส `123456`
- **sales**: รหัส `sales123`
- **viewer**: รหัส `viewer123`

## ระบบ Logging

### 1. Authentication Logs (`/logs/auth.log`)
ข้อมูลที่บันทึก:
- เวลาที่ login/logout
- username ที่ใช้
- IP address
- User agent 
- สถานะ success/failed
- Session ID
- เหตุผลการ logout (manual, timeout)

### 2. Data Access Logs (`/logs/data_access.log`) 
ข้อมูลที่บันทึก:
- เวลาที่เข้าถึงข้อมูล
- username
- IP address  
- API endpoint ที่เรียก
- ช่วงวันที่ที่ค้นหา
- จำนวน records ที่ได้
- Session ID
- User agent

## ตัวอย่าง Log Files

### Auth Log
```json
{"timestamp":"2025-09-09 11:30:45","type":"auth_attempt","username":"sales","ip":"172.18.0.1","success":"SUCCESS","user_agent":"Mozilla/5.0...","session_id":"abc123"}
{"timestamp":"2025-09-09 12:00:15","type":"logout","username":"sales","ip":"172.18.0.1","reason":"manual","session_id":"abc123"}
```

### Data Access Log  
```json
{"timestamp":"2025-09-09 11:31:20","type":"data_access","username":"sales","ip":"172.18.0.1","endpoint":"secure.php","date_range":"2025-09-09 to 2025-09-09","record_count":25,"session_id":"abc123","user_agent":"Mozilla/5.0..."}
```

## การตั้งค่า Logging

ใน `.env` file:
```bash
# เปิด/ปิด logging
LOG_LOGIN_ATTEMPTS=true
LOG_DATA_ACCESS=true

# ที่เก็บ log files  
LOG_PATH=/var/www/html/logs

# ระดับ logging
LOG_LEVEL=info
```

## Security Features

1. **Password Hashing**: ใช้ `PASSWORD_DEFAULT` algorithm
2. **Session Management**: Auto-regenerate session ID หลัง login สำเร็จ
3. **Timeout Logging**: บันทึก session timeout
4. **Rate Limiting**: มีใน API level
5. **Input Validation**: Sanitize ทุก input
6. **Brute Force Protection**: Sleep 2 วินาทีหลัง login ผิด

## การ Monitor

ดู logs แบบ real-time:
```bash
# Login attempts
docker exec crl-booking-web tail -f /var/www/html/logs/auth.log

# Data access
docker exec crl-booking-web tail -f /var/www/html/logs/data_access.log
```