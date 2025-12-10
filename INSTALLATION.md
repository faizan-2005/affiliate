# Installation Guide

## System Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher  
- **Redis**: Optional (recommended for production)
- **Apache/Nginx**: For production deployment

## Step-by-Step Installation

### 1. Clone Repository

```bash
git clone <repository-url> affiliate
cd affiliate
```

### 2. Configure Environment

```bash
# Copy example environment file
cp .env.example .env

# Edit with your settings
nano .env

# Required settings:
# DB_HOST=localhost
# DB_PORT=3306
# DB_NAME=affiliate_db
# DB_USER=root
# DB_PASSWORD=your_password
# REDIS_HOST=localhost (optional)
```

### 3. Create Database

```bash
# Create MySQL database and user
mysql -u root -p <<EOF
CREATE DATABASE affiliate_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'affiliate'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON affiliate_db.* TO 'affiliate'@'localhost';
FLUSH PRIVILEGES;
EOF

# Import schema
mysql -u affiliate -p affiliate_db < database/schema.sql
```

### 4. Set Permissions

```bash
# Create storage directories if needed
mkdir -p storage/logs storage/cache storage/sessions

# Set write permissions
chmod 755 workers/*.php
chmod 777 storage/logs storage/cache storage/sessions
```

### 5. Start Development Server

```bash
# Option 1: PHP Built-in Server
php -S localhost:8000 -t public/

# Option 2: Apache (if DocumentRoot is set to public/)
sudo systemctl restart apache2

# Option 3: Nginx with PHP-FPM
sudo systemctl restart nginx php-fpm
```

### 6. Start Background Workers

In separate terminal windows:

```bash
# Terminal 1: Fraud detection worker
php workers/fraud_worker.php

# Terminal 2: Stats aggregation worker
php workers/stats_worker.php

# Terminal 3: Click archival worker
php workers/archive_worker.php
```

### 7. Create Initial Admin Account

```bash
# Using MySQL directly
mysql -u affiliate -p affiliate_db <<EOF
INSERT INTO users (name, email, password, role, status) VALUES (
    'Admin User',
    'admin@example.com',
    '\$2y\$10\$N9qo8uLOickgx2ZMRZoMyuIjZAgcg7b3XeKeUxWdeS86E36XQuvG2', -- password: 'password'
    'admin',
    'active'
);
EOF
```

Or create via the application once it's running.

## Testing Installation

1. **Visit Dashboard**
   ```
   http://localhost:8000
   ```

2. **Login with credentials**
   ```
   Email: admin@example.com
   Password: password
   ```

3. **Test Click Tracking**
   ```bash
   curl "http://localhost:8000/click?offer_id=1&aff_id=1&click_id=test123"
   ```

4. **Check Logs**
   ```bash
   tail -f storage/logs/app.log
   tail -f storage/logs/fraud.log
   ```

## Production Deployment

### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName affiliate.example.com
    ServerAdmin admin@example.com
    
    DocumentRoot /var/www/affiliate/public
    
    <Directory /var/www/affiliate/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^ index.php [QSA,L]
        </IfModule>
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/affiliate-error.log
    CustomLog ${APACHE_LOG_DIR}/affiliate-access.log combined
</VirtualHost>
```

### Nginx Configuration

```nginx
upstream php-handler {
    server 127.0.0.1:9000;
}

server {
    listen 80;
    server_name affiliate.example.com;
    
    root /var/www/affiliate/public;
    index index.php;
    
    client_max_body_size 100M;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ [^/]\.php(/|$) {
        fastcgi_pass php-handler;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    error_log /var/log/nginx/affiliate-error.log;
    access_log /var/log/nginx/affiliate-access.log;
}
```

### Systemd Service Files

Create `/etc/systemd/system/affiliate-fraud-worker.service`:

```ini
[Unit]
Description=Affiliate Fraud Detection Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/affiliate
ExecStart=/usr/bin/php workers/fraud_worker.php
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable affiliate-fraud-worker
sudo systemctl start affiliate-fraud-worker
```

Repeat for `affiliate-stats-worker.service` and `affiliate-archive-worker.service`.

## Performance Optimization

### Database Optimization

```bash
# Enable MySQL query log (development only)
mysql -u root -p -e "SET GLOBAL general_log = 'ON';"

# Check slow queries
mysql -u root -p -e "SELECT * FROM mysql.slow_log;"

# Optimize tables
mysql -u affiliate -p affiliate_db <<EOF
OPTIMIZE TABLE clicks;
OPTIMIZE TABLE conversions;
OPTIMIZE TABLE daily_stats;
EOF
```

### Redis Configuration

```bash
# Edit redis.conf
sudo nano /etc/redis/redis.conf

# Key settings:
maxmemory 2gb
maxmemory-policy allkeys-lru
tcp-backlog 511
timeout 0
```

### PHP Configuration

Edit `/etc/php/8.0/fpm/php.ini`:

```ini
max_execution_time = 300
memory_limit = 512M
upload_max_filesize = 100M
post_max_size = 100M

# Opcache (production)
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 10000
```

## Monitoring

### Health Check

```bash
# Create health check endpoint (add to public/index.php)
if ($_SERVER['REQUEST_URI'] === '/health') {
    $db = Database::getInstance();
    try {
        $db->selectOne("SELECT 1");
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['status' => 'error']);
    }
    exit;
}
```

### Log Monitoring

```bash
# Watch logs in real-time
tail -f storage/logs/app.log
tail -f storage/logs/fraud.log

# Search for errors
grep ERROR storage/logs/app.log
grep FRAUD storage/logs/fraud.log

# Analyze traffic
grep "Click tracked" storage/logs/app.log | wc -l
```

## Troubleshooting

### Database Connection Error

```bash
# Check credentials in .env
# Verify MySQL is running
sudo systemctl status mysql

# Test connection
mysql -u affiliate -p -h localhost affiliate_db
```

### Permission Denied

```bash
# Fix directory permissions
sudo chown -R www-data:www-data /var/www/affiliate
sudo chmod 755 -R /var/www/affiliate
sudo chmod 777 storage/logs storage/cache storage/sessions
```

### Workers Not Running

```bash
# Check if processes are running
ps aux | grep fraud_worker.php
ps aux | grep stats_worker.php

# Check logs
tail -f storage/logs/app.log

# Restart workers
pkill -f "fraud_worker.php"
php workers/fraud_worker.php &
```

### High Memory Usage

```bash
# Check memory limits in .env
# Reduce cache TTL
# Implement pagination in queries
# Archive old data more frequently
```

## Backup & Recovery

### Daily Backup Script

```bash
#!/bin/bash
# /home/user/backup-affiliate.sh

BACKUP_DIR="/backups/affiliate"
DB_NAME="affiliate_db"
DB_USER="affiliate"

mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p $DB_NAME > $BACKUP_DIR/db-$(date +%Y%m%d).sql

# Backup files
tar -czf $BACKUP_DIR/files-$(date +%Y%m%d).tar.gz /var/www/affiliate

# Keep last 7 days only
find $BACKUP_DIR -type f -mtime +7 -delete
```

Schedule with cron:
```bash
0 2 * * * /home/user/backup-affiliate.sh
```

## Support

For detailed documentation, visit:
- Main README: `README.md`
- Database Schema: `database/schema.sql`
- API Docs: See README.md
