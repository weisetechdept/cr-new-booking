# 📁 Project Structure

## Overview
โครงสร้างโปรเจคได้รับการจัดระเบียบเพื่อให้ง่ายต่อการดูแลรักษา และแยกส่วนต่างๆ ตามหน้าที่

```
crl-new-booking/
├── 📁 src/                     # Source code
│   ├── 📁 pages/               # หน้าเว็บหลัก
│   │   ├── index.php           # หน้าแรก (redirect)
│   │   ├── login.php           # หน้า login
│   │   ├── detail.php          # หน้า dashboard หลัก
│   │   ├── logs.php            # หน้าดู logs
│   │   ├── logout.php          # logout handler
│   │   └── error.php           # หน้า error
│   ├── 📁 includes/            # Components ที่ใช้ร่วมกัน
│   │   ├── nav.php             # Navigation bar
│   │   └── sidebar.php         # Sidebar menu
│   └── 📁 components/          # Components อื่นๆ (ว่าง)
├── 📁 api/                     # REST API endpoints
│   ├── secure.php              # API หลักสำหรับดึงข้อมูล
│   ├── logs.php                # API สำหรับ logs
│   └── qm.php                  # Quality Management API
├── 📁 config/                  # Configuration files
│   ├── auth.php                # Authentication functions
│   ├── env.php                 # Environment loader
│   └── security.php            # Security configurations
├── 📁 assets/                  # Static assets
│   ├── 📁 css/                 # Stylesheets
│   ├── 📁 js/                  # JavaScript files
│   ├── 📁 images/              # Images และ icons
│   ├── 📁 fonts/               # Font files
│   └── 📁 plugins/             # Third-party plugins
├── 📁 docker/                  # Docker configurations
│   ├── Dockerfile              # Development Docker
│   ├── Dockerfile.prod         # Production Docker
│   ├── docker-compose.yml      # Development compose
│   ├── docker-compose.prod.yml # Production compose
│   ├── apache-config.conf      # Apache configuration
│   └── nginx.prod.conf         # Nginx configuration
├── 📁 docs/                    # Documentation
│   ├── DEPLOYMENT.md           # Deployment guide
│   ├── USER-MANAGEMENT.md      # User management guide
│   ├── README-Docker.md        # Docker guide
│   └── PROJECT-STRUCTURE.md    # This file
├── 📁 logs/                    # Application logs
├── 📁 ssl/                     # SSL certificates (production)
├── index.php                   # Main entry point
├── .env.example                # Environment template
├── .gitignore                  # Git ignore rules
└── .dockerignore               # Docker ignore rules
```

## 🎯 Key Benefits

### 1. **Separation of Concerns**
- **src/pages/**: หน้าเว็บหลักที่ user เข้าถึง
- **src/includes/**: Components ที่ใช้ร่วมกัน
- **api/**: REST API endpoints แยกออกมา
- **config/**: Configuration และ utility functions
- **docker/**: Docker configurations แยกออกมา

### 2. **Easy Maintenance**
- แต่ละส่วนอยู่ในโฟลเดอร์ที่เหมาะสม
- Path references ได้รับการปรับปรุงแล้ว
- Documentation จัดอยู่ใน docs/

### 3. **Production Ready**
- Docker configurations อยู่ในโฟลเดอร์เดียว
- SSL และ logs แยกออกมา
- Environment configuration แยกชัดเจน

## 🚀 How to Use

### Development
```bash
# Run development environment
docker-compose -f docker/docker-compose.yml up -d
```

### Production
```bash
# Run production environment  
docker-compose -f docker/docker-compose.prod.yml up -d
```

### Access Points
- **Main Application**: `http://localhost:8090` (dev) or `https://your-domain` (prod)
- **Entry Point**: `index.php` → redirects to `src/pages/login.php`
- **API Endpoints**: `/api/secure.php`, `/api/logs.php`

## 📝 Development Notes

### Adding New Pages
1. สร้างไฟล์ใน `src/pages/`
2. ปรับ path references:
   - Config: `../../config/`
   - Assets: `../../assets/`
   - API: `../../api/`
   - Includes: `../includes/`

### Adding New APIs
1. สร้างไฟล์ใน `api/`
2. ใช้ relative path `../config/` สำหรับ config files

### Modifying Docker
1. แก้ไขใน `docker/` folder
2. ตรวจสอบ context paths ใน compose files

## 🔒 Security Features
- Config files แยกออกจาก public access
- API endpoints ใช้ authentication
- Docker configurations secured
- SSL support สำหรับ production