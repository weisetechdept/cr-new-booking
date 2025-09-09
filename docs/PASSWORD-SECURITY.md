# 🔐 Password Security Guide

## Overview
ระบบ CRL Booking System รองรับการจัดการรหัสผ่านแบบปลอดภัย 2 รูปแบบ:

1. **Plaintext** (Development) - รหัสผ่านแบบข้อความธรรมดาใน .env
2. **Hashed** (Production) - รหัสผ่านที่เข้ารหัสแล้วใน .env

## 🚨 Security Levels

### Level 1: Plaintext Passwords (Not Recommended)
```env
USE_HASHED_PASSWORDS=false
APP_SECRET=your_secret_key_here
USERS=admin:admin123,manager:manager123
```

**ความเสี่ยง:**
- รหัสผ่านเก็บเป็น plaintext ใน .env
- หาก .env รั่วไหล รหัสผ่านจะถูกเปิดเผย

### Level 2: Hashed Passwords (Recommended)
```env
USE_HASHED_PASSWORDS=true
APP_SECRET=Kf8#mN2$pL9!qR3@vT6*wX4&zA7%cE5^
USERS=admin:$argon2id$v=19$m=65536$t=4$p=3$...,manager:$argon2id$v=19$...
```

**ข้อดี:**
- รหัสผ่านเข้ารหัสด้วย Argon2ID (อัลกอริทึมที่แข็งแกร่งที่สุด)
- ใช้ APP_SECRET เป็น salt เพิ่มความปลอดภัย
- แม้ .env รั่วไหลก็ไม่สามารถถอดรหัสได้

## 🛠️ วิธีการตั้งค่า Hashed Passwords

### 1. ใช้ Password Generator (Development)
```bash
# เข้าไปที่ (development environment เท่านั้น)
http://localhost:8090/src/pages/password-generator.php
```

**ขั้นตอน:**
1. ใส่ username และ password ที่ต้องการ
2. ใส่ APP_SECRET (32 ตัวอักษรขึ้นไป)
3. กด "Generate Password Hash"
4. Copy hash ที่ได้ไปใส่ใน .env

### 2. Generate APP_SECRET
```bash
# สร้าง random secret 32 characters
openssl rand -base64 32

# หรือใช้ PHP
php -r "echo bin2hex(random_bytes(16));"
```

### 3. ปรับแต่ง .env
```env
# เปลี่ยนเป็น hashed mode
USE_HASHED_PASSWORDS=true

# ใส่ secret key ที่สร้างไว้
APP_SECRET=Kf8#mN2$pL9!qR3@vT6*wX4&zA7%cE5^

# ใส่ hashed passwords
USERS=admin:$argon2id$v=19$m=65536$t=4$p=3$...,manager:$argon2id$v=19$...
```

## 🔧 Algorithm Details

### Argon2ID Configuration
```php
password_hash($password . $app_secret, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,  // 64 MB memory usage
    'time_cost' => 4,        // 4 iterations
    'threads' => 3           // 3 parallel threads
]);
```

**ความหมาย:**
- **memory_cost**: ใช้หน่วยความจำ 64 MB ในการเข้ารหัส
- **time_cost**: ทำซ้า 4 รอบ (balance ระหว่างความปลอดภัยและความเร็ว)
- **threads**: ใช้ 3 threads แบบขนาน

### Salt Strategy
```
Final Password = User_Password + APP_SECRET
```

**ตัวอย่าง:**
- User types: `admin123`
- APP_SECRET: `mySecret123`
- Salted password: `admin123mySecret123`
- Hash: `$argon2id$v=19$m=65536$t=4$p=3$...`

## 📋 Production Checklist

### ✅ Before Deployment
- [ ] Generate strong APP_SECRET (32+ characters)
- [ ] Use password generator to hash all passwords
- [ ] Set USE_HASHED_PASSWORDS=true
- [ ] Test login with hashed passwords
- [ ] Remove/disable password generator page

### ✅ Security Verification
- [ ] .env contains no plaintext passwords
- [ ] APP_SECRET is unique and strong
- [ ] All password hashes start with `$argon2id$`
- [ ] Password generator page returns 404 in production

## 🚫 Common Mistakes

### ❌ Wrong: Mixing plaintext and hashed
```env
USE_HASHED_PASSWORDS=true
USERS=admin:admin123,manager:$argon2id$...  # DON'T DO THIS
```

### ❌ Wrong: Same APP_SECRET everywhere
```env
APP_SECRET=secret123  # Too simple, don't reuse
```

### ❌ Wrong: No APP_SECRET
```env
APP_SECRET=  # Empty secret reduces security
```

### ✅ Correct: All hashed with strong secret
```env
USE_HASHED_PASSWORDS=true
APP_SECRET=Kf8#mN2$pL9!qR3@vT6*wX4&zA7%cE5^xY9&bC2@eF5!
USERS=admin:$argon2id$v=19$...,manager:$argon2id$v=19$...
```

## 🔄 Migration Guide

### From Plaintext to Hashed

1. **Backup current .env**
   ```bash
   cp .env .env.backup
   ```

2. **Generate APP_SECRET**
   ```bash
   openssl rand -base64 32
   ```

3. **Use Password Generator**
   - Visit password generator page
   - Generate hash for each user
   - Copy hashes

4. **Update .env**
   ```env
   USE_HASHED_PASSWORDS=true
   APP_SECRET=your_new_secret_here
   USERS=admin:$argon2id$...,manager:$argon2id$...
   ```

5. **Test & Deploy**
   ```bash
   # Test login functionality
   # Deploy to production
   ```

## 🛡️ Security Benefits

### Argon2ID Advantages
- **Memory-hard**: Requires significant RAM (resists GPU attacks)
- **Time-hard**: Requires significant time (resists parallel attacks)  
- **Side-channel resistant**: Protects against timing attacks
- **Winner of PHC**: Password Hashing Competition winner

### APP_SECRET Benefits
- **Unique salt**: Different hash for same password across systems
- **Secret ingredient**: Even if hash leaks, original password harder to find
- **Peppered approach**: Modern security best practice

## 📞 Support

หากมีปัญหาเกี่ยวกับการตั้งค่ารหัสผ่าน:

1. ตรวจสอบ logs ใน `logs/auth.log`
2. ทดสอบ login ใน development environment ก่อน
3. ตรวจสอบ .env configuration ให้ถูกต้อง