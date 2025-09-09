# CRL Booking System - Docker Setup

## การรันระบบ Production สำหรับ 5-10 คน

### Quick Start (Development)
```bash
# 1. Copy environment file
cp .env.example .env

# 2. Edit .env file with your settings
nano .env

# 3. Run the application
docker-compose up -d
```

### Production Setup (with Nginx)
```bash
# 1. Copy environment file
cp .env.example .env

# 2. Edit .env with production values
nano .env

# 3. Run with production profile
docker-compose --profile production up -d
```

### การตั้งค่า

1. **แก้ไข .env file:**
   - `ADMIN_PASSWORD` และ `MANAGER_PASSWORD` ใส่รหัสผ่านที่ปลอดภัย
   - `API_URL` ใส่ URL ของ API ที่ใช้จริง

2. **การเข้าถึง:**
   - Development: `http://localhost`
   - Production with Nginx: `http://localhost:8080` หรือ `https://localhost` (ถ้าตั้ง SSL)

### คำสั่งที่มีประโยชน์

```bash
# ดู logs
docker-compose logs -f

# Restart services
docker-compose restart

# Stop services  
docker-compose down

# Update and rebuild
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Monitor resource usage
docker stats
```

### โครงสร้างไฟล์
- `Dockerfile` - PHP + Apache container
- `docker-compose.yml` - Service orchestration
- `nginx.conf` - Nginx reverse proxy (สำหรับ production)
- `apache-config.conf` - Apache security configuration
- `.dockerignore` - Optimize build process

### Security Features
- Rate limiting (10 requests/minute for API, 5 requests/minute for login)
- Security headers
- Session management
- Input validation
- Log monitoring

### สำหรับ Production 5-10 คน:
- Memory usage: ~512MB
- CPU: 1 core เพียงพอ
- Storage: ~2GB (รวม logs)
- Network: 100Mbps เพียงพอ