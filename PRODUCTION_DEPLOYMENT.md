# 🚀 HƯỚNG DẪN TRIỂN KHAI PRODUCTION
## Hệ thống quản lý shop thời trang The Trendy Station

### 📋 CHECKLIST TRIỂN KHAI

#### 1. Chuẩn bị môi trường Production
```bash
# Yêu cầu hệ thống:
- PHP 7.4+ (khuyến nghị PHP 8.0+)
- MySQL 5.7+ hoặc MariaDB 10.3+
- Apache hoặc Nginx
- SSL Certificate (khuyến nghị)
- Domain name đã setup

# Kiểm tra PHP extensions:
php -m | grep -E "(pdo_mysql|json|mbstring|fileinfo)"
```

#### 2. Cấu hình Database Production
```sql
-- Tạo database và user riêng cho production
CREATE DATABASE trendy_station_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'trendy_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON trendy_station_prod.* TO 'trendy_user'@'localhost';
FLUSH PRIVILEGES;

-- Import database structure và data
mysql -u trendy_user -p trendy_station_prod < database.sql
mysql -u trendy_user -p trendy_station_prod < database_customers.sql
mysql -u trendy_user -p trendy_station_prod < database_imports.sql
mysql -u trendy_user -p trendy_station_prod < database_returns.sql
```

#### 3. Cấu hình bảo mật Production

**3.1. Cập nhật config/database.php cho Production:**
```php
<?php
// Production Database Configuration
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'trendy_station_prod';
$username = $_ENV['DB_USER'] ?? 'trendy_user';
$password = $_ENV['DB_PASS'] ?? 'YOUR_STRONG_PASSWORD';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Lỗi kết nối database. Vui lòng thử lại sau.");
}
?>
```

**3.2. Tạo file .env cho production:**
```env
# Database
DB_HOST=localhost
DB_NAME=trendy_station_prod
DB_USER=trendy_user
DB_PASS=your_strong_password_here

# Security
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE=true
SESSION_HTTPONLY=true
```

**3.3. Tạo .htaccess bảo mật:**
```apache
# Security Headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' cdnjs.cloudflare.com cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' cdnjs.cloudflare.com; img-src 'self' data:; font-src 'self' cdnjs.cloudflare.com;"

# Hide sensitive files
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

<Files "*.sql">
    Order allow,deny
    Deny from all
</Files>

<FilesMatch "\.(md|txt|log)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Pretty URLs
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# HTTPS Redirect (nếu có SSL)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Gzip compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Cache static files
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
</IfModule>
```

#### 4. Tối ưu hóa Performance

**4.1. Minify CSS/JS (tạo script build.php):**
```php
<?php
// build.php - Script tối ưu assets
function minifyCSS($css) {
    // Remove comments
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    // Remove unnecessary whitespace
    $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
    return $css;
}

function minifyJS($js) {
    // Basic minification - for production use proper tools
    $js = preg_replace('/\s+/', ' ', $js);
    $js = str_replace(['; ', ' {', '{ ', ' }', '} ', ' (', '( ', ' )', ') '], [';', '{', '{', '}', '}', '(', '(', ')', ')'], $js);
    return $js;
}

// Minify CSS
$css = file_get_contents('assets/css/style.css');
$minifiedCSS = minifyCSS($css);
file_put_contents('assets/css/style.min.css', $minifiedCSS);

// Minify JS
$js = file_get_contents('assets/js/script.js');
$minifiedJS = minifyJS($js);
file_put_contents('assets/js/script.min.js', $minifiedJS);

echo "Assets minified successfully!\n";
?>
```

**4.2. Cấu hình OpCache trong php.ini:**
```ini
; OpCache settings
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=0
opcache.fast_shutdown=1
opcache.enable_cli=1
```

#### 5. Backup và Monitoring

**5.1. Script backup database:**
```bash
#!/bin/bash
# backup_db.sh
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/path/to/backups"
DB_NAME="trendy_station_prod"
DB_USER="trendy_user"

# Create backup
mysqldump -u $DB_USER -p $DB_NAME > $BACKUP_DIR/trendy_backup_$DATE.sql
gzip $BACKUP_DIR/trendy_backup_$DATE.sql

# Keep only last 7 days
find $BACKUP_DIR -name "trendy_backup_*.sql.gz" -mtime +7 -delete

echo "Backup completed: trendy_backup_$DATE.sql.gz"
```

**5.2. Script monitoring:**
```bash
#!/bin/bash
# health_check.sh
SITE_URL="https://yoursite.com"
LOG_FILE="/var/log/trendy_health.log"

# Check website availability
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" $SITE_URL)
if [ $HTTP_CODE -eq 200 ]; then
    echo "$(date): Site OK" >> $LOG_FILE
else
    echo "$(date): Site DOWN - HTTP $HTTP_CODE" >> $LOG_FILE
    # Send alert email
    echo "Site is down!" | mail -s "Trendy Station Alert" admin@yoursite.com
fi
```

#### 6. Cấu hình SSL và Domain

**6.1. Cấu hình Apache VirtualHost:**
```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/trendy_station
    
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    SSLCertificateChainFile /path/to/ca_bundle.crt
    
    <Directory /var/www/trendy_station>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/trendy_error.log
    CustomLog ${APACHE_LOG_DIR}/trendy_access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>
```

#### 7. Kiểm tra cuối cùng

**7.1. Security Checklist:**
- [ ] Database password mạnh
- [ ] Files .env, .sql được ẩn
- [ ] HTTPS được bật
- [ ] Security headers được set
- [ ] PHP error display tắt
- [ ] File permissions đúng (644 cho files, 755 cho folders)

**7.2. Performance Checklist:**
- [ ] OpCache enabled
- [ ] Gzip compression enabled
- [ ] Static files caching
- [ ] CSS/JS minified
- [ ] Database indexes tối ưu

**7.3. Backup Checklist:**
- [ ] Database backup script
- [ ] File backup script
- [ ] Automated backup schedule
- [ ] Backup restore testing

### 🎯 GO-LIVE PROCESS

#### Bước 1: Upload files
```bash
# Upload tất cả files trừ development configs
rsync -avz --exclude='.git' --exclude='*.md' --exclude='*.txt' \
    ./trendy_station/ user@server:/var/www/trendy_station/
```

#### Bước 2: Set permissions
```bash
# Set proper permissions
find /var/www/trendy_station -type d -exec chmod 755 {} \;
find /var/www/trendy_station -type f -exec chmod 644 {} \;
chmod 600 /var/www/trendy_station/config/database.php
chmod 600 /var/www/trendy_station/.env
```

#### Bước 3: Import database
```bash
mysql -u trendy_user -p trendy_station_prod < database.sql
mysql -u trendy_user -p trendy_station_prod < database_customers.sql
mysql -u trendy_user -p trendy_station_prod < database_imports.sql
mysql -u trendy_user -p trendy_station_prod < database_returns.sql
```

#### Bước 4: Test website
- [ ] Truy cập trang chủ
- [ ] Test đăng nhập (nếu có)
- [ ] Test các chức năng chính
- [ ] Test trên mobile
- [ ] Check SSL certificate
- [ ] Test tốc độ loading

#### Bước 5: Setup monitoring
```bash
# Add to crontab
crontab -e

# Backup database daily at 2 AM
0 2 * * * /path/to/backup_db.sh

# Health check every 5 minutes
*/5 * * * * /path/to/health_check.sh
```

### 🔧 TROUBLESHOOTING

#### Lỗi thường gặp:

**1. Database connection error:**
```bash
# Check MySQL service
systemctl status mysql

# Check database exists
mysql -u root -p -e "SHOW DATABASES;"

# Check user permissions
mysql -u root -p -e "SHOW GRANTS FOR 'trendy_user'@'localhost';"
```

**2. File permission errors:**
```bash
# Reset permissions
chown -R www-data:www-data /var/www/trendy_station
chmod -R 755 /var/www/trendy_station
chmod 644 /var/www/trendy_station/config/database.php
```

**3. SSL/HTTPS issues:**
```bash
# Test SSL certificate
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com

# Check Apache SSL module
a2enmod ssl
systemctl restart apache2
```

**4. Performance issues:**
```bash
# Check Apache status
systemctl status apache2

# Check PHP-FPM (if using)
systemctl status php7.4-fpm

# Monitor server resources
top
htop
```

### 📝 MAINTENANCE SCHEDULE
- **Daily:** Automated backups
- **Weekly:** Security updates check
- **Monthly:** Performance review
- **Quarterly:** Full system audit

---
**⚠️ LƯU Ý QUAN TRỌNG:**
1. **LUÔN backup trước khi update**
2. **Test trên staging trước khi deploy production**
3. **Monitor logs thường xuyên**
4. **Cập nhật security patches kịp thời**
5. **Thay đổi mật khẩu định kỳ**
