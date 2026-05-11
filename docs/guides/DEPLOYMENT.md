# SiroPHP Deployment Guide

**Deploy your API to production with confidence**

---

## 🚀 Quick Deploy (One Command)

```bash
php siro deploy
```

This command:
1. Runs tests
2. Optimizes for production
3. Deploys via Git/rsync/custom strategy
4. Restarts services

---

## 📋 Pre-Deployment Checklist

### 1. Environment Validation

```bash
php siro env:check
```

**Checks:**
- ✅ `.env` file exists
- ✅ Required variables set
- ✅ JWT_SECRET strength (min 32 chars)
- ✅ APP_DEBUG is false in production
- ✅ PHP extensions loaded
- ✅ Storage directories writable

### 2. Run Tests

```bash
php siro test
```

**All tests must pass before deployment!**

### 3. Optimize for Production

```bash
php siro optimize
```

**Runs:**
- `php siro config:cache` - Cache configuration
- `composer dump-autoload --optimize` - Optimize autoloader

### 4. Generate Documentation

```bash
php siro make:openapi --with-swagger
```

---

## 🌐 Deployment Strategies

### Strategy 1: Git Deployment (Recommended)

**Setup on server:**
```bash
# SSH into server
ssh user@your-server.com

# Create deployment directory
mkdir -p /var/www/myapp
cd /var/www/myapp

# Initialize git repo
git init --bare
```

**Configure in project:**
```json
// deploy.json
{
    "strategy": "git",
    "remote": "user@your-server.com:/var/www/myapp",
    "branch": "main",
    "commands": [
        "composer install --no-dev --optimize-autoloader",
        "php siro migrate --force",
        "php siro config:cache",
        "sudo systemctl restart php8.2-fpm"
    ]
}
```

**Deploy:**
```bash
php siro deploy
```

### Strategy 2: Rsync Deployment

**Configure:**
```json
// deploy.json
{
    "strategy": "rsync",
    "host": "your-server.com",
    "user": "deploy",
    "path": "/var/www/myapp",
    "exclude": [
        ".git",
        "node_modules",
        "storage/logs/*.log",
        "vendor"
    ],
    "commands": [
        "composer install --no-dev",
        "php siro migrate --force",
        "php siro optimize"
    ]
}
```

**Deploy:**
```bash
php siro deploy
```

### Strategy 3: Custom Script

**Configure:**
```json
// deploy.json
{
    "strategy": "custom",
    "script": "deploy.sh"
}
```

**Create script:**
```bash
#!/bin/bash
# deploy.sh

echo "Starting deployment..."

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php siro migrate --force

# Optimize
php siro config:cache

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx

echo "Deployment complete!"
```

**Deploy:**
```bash
chmod +x deploy.sh
php siro deploy
```

---

## 🔧 Server Configuration

### Ubuntu VPS Setup

#### 1. Install Prerequisites

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-pgsql \
    php8.2-sqlite3 php8.2-mbstring php8.2-xml php8.2-curl \
    php8.2-zip php8.2-gd php8.2-intl

# Install Nginx
sudo apt install -y nginx

# Install MySQL (optional)
sudo apt install -y mysql-server

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### 2. Configure PHP-FPM

```bash
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

**Settings:**
```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35

; Increase memory limit if needed
php_admin_value[memory_limit] = 256M
```

**Restart:**
```bash
sudo systemctl restart php8.2-fpm
```

#### 3. Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/myapp
```

**Configuration:**
```nginx
server {
    listen 80;
    server_name api.example.com;
    root /var/www/myapp/public;
    index index.php;

    # Security headers
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";

    # Gzip compression
    gzip on;
    gzip_types application/json text/xml application/xml;
    gzip_min_length 1000;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # Timeout settings
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }

    # Deny access to .env file
    location ~ /\.env {
        deny all;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
}
```

**Enable site:**
```bash
sudo ln -s /etc/nginx/sites-available/myapp /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 4. Setup SSL (Let's Encrypt)

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d api.example.com

# Auto-renewal (already configured by certbot)
sudo systemctl enable certbot.timer
```

---

## 🗄️ Database Setup

### MySQL

```bash
# Login to MySQL
mysql -u root -p

# Create database and user
CREATE DATABASE myapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'myapp_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON myapp.* TO 'myapp_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**.env configuration:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapp
DB_USERNAME=myapp_user
DB_PASSWORD=strong_password
```

### PostgreSQL

```bash
# Login to PostgreSQL
sudo -u postgres psql

-- Create database and user
CREATE DATABASE myapp;
CREATE USER myapp_user WITH PASSWORD 'strong_password';
GRANT ALL PRIVILEGES ON DATABASE myapp TO myapp_user;
\q
```

**.env configuration:**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=myapp
DB_USERNAME=myapp_user
DB_PASSWORD=strong_password
```

### SQLite (Simple deployments)

**.env configuration:**
```env
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/myapp/storage/database.sqlite
```

**Create database file:**
```bash
touch storage/database.sqlite
chmod 664 storage/database.sqlite
chown www-data:www-data storage/database.sqlite
```

---

## 🔐 Security Hardening

### 1. File Permissions

```bash
cd /var/www/myapp

# Set ownership
sudo chown -R www-data:www-data .

# Set permissions
sudo find . -type f -exec chmod 644 {} \;
sudo find . -type d -exec chmod 755 {} \;

# Storage and cache directories
sudo chmod -R 775 storage bootstrap/cache
```

### 2. Disable Dangerous PHP Functions

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

**Add:**
```ini
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

### 3. Configure Firewall

```bash
# Enable UFW
sudo ufw enable

# Allow SSH, HTTP, HTTPS
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Block direct database access from outside
sudo ufw deny 3306/tcp  # MySQL
sudo ufw deny 5432/tcp  # PostgreSQL
```

### 4. Setup Log Rotation

```bash
sudo nano /etc/logrotate.d/myapp
```

**Configuration:**
```
/var/www/myapp/storage/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0644 www-data www-data
}
```

---

## 📊 Monitoring & Maintenance

### 1. Setup Cron Jobs

```bash
crontab -e
```

**Add:**
```cron
# Run scheduler every minute
* * * * * cd /var/www/myapp && php siro schedule:run >> /dev/null 2>&1

# Process queue every minute
* * * * * cd /var/www/myapp && php siro queue:work >> /dev/null 2>&1

# Rotate logs weekly
0 0 * * 0 find /var/www/myapp/storage/logs -name "*.log" -mtime +30 -delete
```

### 2. Monitor Performance

```bash
# Check slow requests
php siro slow

# View trace logs
php siro log:trace --status=500

# Check rate limits
php siro rate:status
```

### 3. Backup Database

```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/myapp"
mkdir -p $BACKUP_DIR

# MySQL backup
mysqldump -u myapp_user -p'strong_password' myapp > $BACKUP_DIR/db_$DATE.sql

# Compress
gzip $BACKUP_DIR/db_$DATE.sql

# Delete old backups (keep 30 days)
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete

echo "Backup completed: db_$DATE.sql.gz"
```

**Schedule:**
```cron
0 2 * * * /var/www/myapp/backup.sh >> /var/log/backup.log 2>&1
```

### 4. Health Check Endpoint

```php
// routes/api.php
Route::get('/health', function () {
    $checks = [
        'database' => false,
        'cache' => false,
    ];
    
    // Check database
    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (\Exception $e) {
        // Database connection failed
    }
    
    // Check cache
    try {
        Cache::put('health_check', true, 10);
        $checks['cache'] = Cache::get('health_check');
    } catch (\Exception $e) {
        // Cache failed
    }
    
    $status = collect($checks)->every(fn($v) => $v) ? 200 : 503;
    
    return Response::json([
        'status' => $status === 200 ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $status);
});
```

**Monitor with uptime checker:**
```bash
curl https://api.example.com/api/health
```

---

## 🔄 Zero-Downtime Deployment

### Using Maintenance Mode

```bash
# 1. Enable maintenance mode (allows your IP)
php siro down --allow=YOUR_IP_ADDRESS

# 2. Deploy code
git pull origin main
composer install --no-dev
php siro migrate --force
php siro optimize

# 3. Test health endpoint
curl http://localhost/api/health

# 4. Disable maintenance mode
php siro up
```

### Blue-Green Deployment (Advanced)

**Setup two directories:**
```
/var/www/myapp-blue   (active)
/var/www/myapp-green  (staging)
```

**Nginx configuration:**
```nginx
# Point to blue
root /var/www/myapp-blue/public;

# Switch to green when ready
# root /var/www/myapp-green/public;
```

**Deploy process:**
1. Deploy to inactive environment (green)
2. Run tests on green
3. Switch Nginx to point to green
4. Old blue becomes staging for next deployment

---

## 🐳 Docker Deployment

### Using Docker Compose

```yaml
# docker-compose.prod.yml
version: '3.8'

services:
  app:
    build: .
    volumes:
      - ./storage:/var/www/html/storage
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    depends_on:
      - db
      - redis

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: myapp
      MYSQL_USER: myapp_user
      MYSQL_PASSWORD: strong_password
      MYSQL_ROOT_PASSWORD: root_password
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
      - ./public:/var/www/html/public

volumes:
  mysql_data:
```

**Deploy:**
```bash
docker-compose -f docker-compose.prod.yml up -d
docker-compose -f docker-compose.prod.yml exec app php siro migrate --force
docker-compose -f docker-compose.prod.yml exec app php siro optimize
```

---

## ❓ Troubleshooting

### Problem: 502 Bad Gateway

**Check:**
```bash
# PHP-FPM status
sudo systemctl status php8.2-fpm

# Nginx error log
sudo tail -f /var/log/nginx/error.log

# PHP-FPM error log
sudo tail -f /var/log/php8.2-fpm.log
```

### Problem: Permission Denied

**Fix:**
```bash
sudo chown -R www-data:www-data /var/www/myapp
sudo chmod -R 775 storage bootstrap/cache
```

### Problem: Database Connection Failed

**Check:**
```bash
# Test connection
php -r "require 'vendor/autoload.php'; var_dump(DB::connection()->getPdo());"

# Check credentials
cat .env | grep DB_

# Check MySQL status
sudo systemctl status mysql
```

### Problem: High Memory Usage

**Optimize:**
```bash
# Check memory usage
php siro benchmark

# Reduce PHP-FPM children
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
# pm.max_children = 20 (reduce from 50)

sudo systemctl restart php8.2-fpm
```

---

## 📚 Additional Resources

- **[Performance Guide](PERFORMANCE.md)** - Optimization tips
- **[Security Guide](SECURITY.md)** - Security best practices
- **[Nginx Documentation](https://nginx.org/en/docs/)**
- **[PHP-FPM Tuning](https://www.php.net/manual/en/install.fpm.configuration.php)**

---

**Happy deploying! 🚀**
