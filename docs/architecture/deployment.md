# Triển khai & Hạ tầng

## Môi trường

| Env | Mô tả |
|-----|-------|
| local | Phát triển (ServBay) |
| staging | Kiểm thử trước release |
| production | Production server |

## Yêu cầu server

```
PHP >= 8.1
MySQL >= 8.0
Node.js >= 18 (để build assets)
Nginx hoặc Apache
Redis (optional, cho cache/queue tốt hơn)
Supervisor (để chạy queue workers)
```

## Build Process

```bash
# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# Build frontend assets
npm run build

# Laravel optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Database
php artisan migrate --force
php artisan db:seed --class=FeatureToggleSeeder

# Queue workers (via Supervisor)
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

## Biến môi trường quan trọng

```env
APP_ENV=production
APP_KEY=base64:...
APP_URL=https://dieuvan.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=va_dieuvan
DB_USERNAME=...
DB_PASSWORD=...

GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=...

# Web Push (VAPID)
VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
VAPID_SUBJECT=mailto:admin@example.com

# Queue
QUEUE_CONNECTION=database  # hoặc redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=dieuvan@example.com
```

## Scheduled Commands

Cần cấu hình cron `* * * * * php artisan schedule:run`:

| Command | Lịch | Mục đích |
|---------|------|---------|
| `MaterializeRecurringDispatchRequests` | Daily | Tạo yêu cầu định kỳ |
| `RemindPendingDeptApprovals` | Daily | Nhắc trưởng đơn vị duyệt |
| `RemindMissingSignedPaperUpload` | Daily | Nhắc upload biên bản |
| `RemindUpcomingTripDrivers` | Daily | Nhắc tài xế chuyến sắp đến |
| `RemindTpDriverMorningShifts` | Daily morning | Nhắc ca sáng TP |
| `RemindTpDriverAfternoonShifts` | Daily afternoon | Nhắc ca chiều TP |
| `CargoSlaCheck` | Hourly | Kiểm tra SLA hàng hóa |

## Queue Jobs

| Job | Queue | Mục đích |
|-----|-------|---------|
| `ProcessAttachmentOcrJob` | default | OCR tài liệu đính kèm |
| `ProcessSignedDocumentPipelineJob` | default | Xử lý biên bản ký |

## Nginx config mẫu

```nginx
server {
    listen 80;
    server_name dieuvan.example.com;
    root /var/www/va-dieuvan/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```
