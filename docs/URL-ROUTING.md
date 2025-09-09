# 🔗 URL Routing & Clean URLs

## Overview
ระบบใช้ Apache mod_rewrite เพื่อสร้าง clean URLs ที่ดูสะอาดและใช้งานง่าย

## 📍 Available Routes

### Public Routes
| Clean URL | Original Path | Description |
|-----------|---------------|-------------|
| `/login` | `src/pages/login.php` | หน้า login |
| `/logout` | `src/pages/logout.php` | Logout handler |
| `/dashboard` | `src/pages/detail.php?p=crl1` | หน้า dashboard หลัก |
| `/logs` | `src/pages/logs.php?p=logs` | หน้าดู system logs |
| `/password-generator` | `src/pages/password-generator.php` | Password hash generator |

### API Routes  
| Clean URL | Original Path | Description |
|-----------|---------------|-------------|
| `/api/secure` | `api/secure.php` | Main data API |
| `/api/logs` | `api/logs.php` | Logs API |

## 🛡️ Security Features

### File Protection
```apache
# Prevent access to sensitive files
<Files ~ "\.(env|log|md|txt)$">
    Order allow,deny  
    Deny from all
</Files>

# Prevent access to config directories
RedirectMatch 403 ^/?(config|docs|docker|logs)/.*$
```

### Production Security
```apache
# Hide password generator in production
RewriteCond %{ENV:APP_ENV} ^production$
RewriteRule ^password-generator/?$ - [F,L]
```

### Security Headers
```apache
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

## 🔄 URL Redirects

### Automatic Redirects
- `/` → `/login` (Main entry point)
- Old URLs automatically redirect to new clean URLs
- HTTP → HTTPS (in production)

### Legacy URL Support
```apache
# Handle old URLs with redirect
RewriteRule ^src/pages/login\.php$ login [R=301,L]
RewriteRule ^src/pages/detail\.php\?p=crl1$ dashboard [R=301,L]
```

## 🛠️ Development vs Production

### Development
- All routes accessible
- Password generator available
- Debug information visible

### Production  
- Password generator blocked (404)
- Enhanced security headers
- HTTPS enforcement (optional)

## 📝 Usage Examples

### In PHP Code
```php
// Old way
header("Location: src/pages/login.php");

// New way  
header("Location: /login");
```

### In HTML/Navigation
```html
<!-- Old way -->
<a href="detail.php?p=crl1">Dashboard</a>

<!-- New way -->
<a href="/dashboard">Dashboard</a>
```

### Using Route Helpers
```php
require_once 'config/routes.php';

// Generate URLs
$loginUrl = route('login');
$dashboardUrl = route('dashboard', ['msg' => 'welcome']);

// Redirect  
redirect('dashboard');

// Check current route
if (isRoute('login')) {
    // On login page
}
```

## 🔧 Configuration

### .htaccess Structure
```apache
RewriteEngine On

# Security headers
Header always set X-Content-Type-Options nosniff

# File protection
<Files ~ "\.(env|log)$">
    Deny from all
</Files>

# Clean URL rewrites
RewriteRule ^login/?$ src/pages/login.php [L]
RewriteRule ^dashboard/?$ src/pages/detail.php?p=crl1 [L]

# Default redirect
RewriteRule ^$ login [R=301,L]
```

### Custom Error Pages
```apache
ErrorDocument 404 /src/pages/error.php?code=404
ErrorDocument 403 /src/pages/error.php?code=403
ErrorDocument 500 /src/pages/error.php?code=500
```

## ✅ Benefits

### User Experience
- **Clean URLs**: `/dashboard` แทน `/src/pages/detail.php?p=crl1`
- **Memorizable**: ง่ายต่อการจำและพิมพ์
- **Professional**: ดูเป็นมืออาชีพ

### Security
- **Hide Structure**: ซ่อนโครงสร้างไฟล์จริง
- **File Protection**: ป้องกันการเข้าถึงไฟล์สำคัญ
- **Access Control**: ควบคุมการเข้าถึงตาม environment

### SEO & Maintenance
- **Search Friendly**: URL ที่เป็นมิตรกับ search engines
- **Flexible**: เปลี่ยนโครงสร้างไฟล์ได้โดยไม่กระทบ URL
- **Consistent**: รูปแบบ URL ที่สม่ำเสมอ

## 🚨 Troubleshooting

### Common Issues

1. **404 Errors**
   ```bash
   # Check if mod_rewrite is enabled
   apache2ctl -M | grep rewrite
   ```

2. **Permission Errors**
   ```bash
   # Check .htaccess permissions
   ls -la .htaccess
   ```

3. **Rules Not Working**
   ```apache
   # Enable rewrite logging (development only)
   RewriteLog /tmp/rewrite.log
   RewriteLogLevel 3
   ```

### Testing URLs
```bash
# Test clean URLs
curl -I http://localhost:8090/login
curl -I http://localhost:8090/dashboard  
curl -I http://localhost:8090/logs
```

## 📋 Best Practices

1. **Always use clean URLs** in navigation
2. **Test both development and production** configurations  
3. **Keep route helpers updated** when adding new pages
4. **Use HTTPS redirects** in production
5. **Monitor error logs** for routing issues