# CRL Booking System - Production Deployment Guide

## 🚀 Production Deployment

### Prerequisites

1. **Server Requirements:**
   - Docker Engine 20.10+
   - Docker Compose V2
   - 2GB+ RAM
   - 10GB+ storage
   - SSL certificate files

2. **Domain & SSL:**
   - Registered domain name
   - Valid SSL certificate (.crt)
   - SSL private key (.key)

### Production Setup

#### 1. Clone Repository
```bash
git clone <repository-url>
cd crl-new-booking
```

#### 2. Environment Configuration
```bash
# Copy environment template
cp .env.example .env

# Edit production environment
nano .env
```

**Required .env Configuration:**
```bash
# Application
APP_ENV=production
APP_DEBUG=false

# Users (add your production users)
USERS=admin:secure_admin_password,manager:secure_manager_password,sales:sales_password

# API Configuration
API_URL=https://www.qms-toyotaparagon.com/api/cusbookingpayment
API_TIMEOUT=30

# Security
RATE_LIMIT_REQUESTS=20
SESSION_TIMEOUT=1800

# Logging
LOG_LOGIN_ATTEMPTS=true
LOG_DATA_ACCESS=true
LOG_LEVEL=warning
```

#### 3. SSL Certificate Setup
```bash
# Create SSL directory
mkdir ssl

# Copy your SSL files
cp your-domain.crt ssl/server.crt
cp your-domain.key ssl/server.key

# Set proper permissions
chmod 600 ssl/server.key
chmod 644 ssl/server.crt
```

#### 4. Deploy Production Stack
```bash
# Build and start services
docker-compose -f docker/docker-compose.prod.yml up -d

# Check status
docker-compose -f docker/docker-compose.prod.yml ps

# View logs
docker-compose -f docker/docker-compose.prod.yml logs -f
```

#### 5. Enable Monitoring (Optional)
```bash
# Start with monitoring
docker-compose -f docker/docker-compose.prod.yml --profile monitoring up -d
```

### Production Architecture

```
Internet → Nginx (Port 443/80) → PHP-Apache Container
                ↓
            Log Rotation Service
                ↓
            Watchtower (Auto-updates)
```

### Security Features

#### Network Security
- **HTTPS Only**: HTTP redirects to HTTPS
- **Rate Limiting**: 
  - API: 20 requests/minute
  - Login: 10 requests/minute
  - General: 100 requests/minute
- **Connection Limits**: Max 10 per IP

#### Application Security
- **Session Security**: HTTPOnly, Secure cookies
- **Input Validation**: All inputs sanitized
- **SQL Injection**: Prevention built-in
- **XSS Protection**: Content Security Policy
- **CSRF Protection**: Token validation

#### Data Privacy
- **Session ID Masking**: Only first 8 chars shown
- **IP Masking**: Last octet hidden (192.168.1.***)
- **Secure Logging**: Sensitive data filtered

### Monitoring & Maintenance

#### Health Checks
```bash
# Application health
curl https://your-domain/health

# Container health
docker-compose -f docker/docker-compose.prod.yml ps
```

#### Log Management
```bash
# View application logs
docker exec crl-booking-web-prod tail -f /var/www/html/logs/auth.log

# View nginx logs
docker-compose -f docker/docker-compose.prod.yml logs nginx

# Log rotation runs daily automatically
```

#### Backup Strategy
```bash
# Backup logs
tar -czf logs-backup-$(date +%Y%m%d).tar.gz logs/

# Backup environment
cp .env .env.backup.$(date +%Y%m%d)
```

#### Updates
```bash
# Update application
git pull origin main

# Rebuild and restart
docker-compose -f docker/docker-compose.prod.yml down
docker-compose -f docker/docker-compose.prod.yml build --no-cache
docker-compose -f docker/docker-compose.prod.yml up -d

# Auto-updates (with Watchtower enabled)
# Updates happen automatically every hour
```

### Performance Optimization

#### Resource Limits (for 5-10 users)
- **Memory**: 512MB per container
- **CPU**: 0.5 cores per container
- **Connections**: Max 100 concurrent

#### Caching
- **Static Files**: 1 year cache
- **Gzip Compression**: Enabled
- **SSL Session**: 10 minutes cache

### Troubleshooting

#### Common Issues

1. **SSL Certificate Error**
```bash
# Check certificate
openssl x509 -in ssl/server.crt -text -noout

# Test SSL
openssl s_client -connect your-domain:443
```

2. **Permission Issues**
```bash
# Fix log permissions
docker exec crl-booking-web-prod chown -R www-data:www-data /var/www/html/logs
```

3. **High Memory Usage**
```bash
# Check container resources
docker stats

# Restart services
docker-compose -f docker/docker-compose.prod.yml restart
```

#### Log Locations
- **Application**: `logs/*.log`
- **Nginx**: `logs/nginx/*.log`
- **Docker**: `docker-compose logs`

### Scaling for Larger Deployments

For more than 10 users, consider:
- Load balancer (HAProxy/Nginx+)
- Database separation
- Redis session storage
- Horizontal scaling

## Support

For production support:
1. Check logs first
2. Review health checks
3. Verify SSL certificates
4. Monitor resource usage

Remember to regularly backup logs and update SSL certificates!