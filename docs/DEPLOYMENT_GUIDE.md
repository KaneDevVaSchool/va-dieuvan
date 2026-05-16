# DEPLOYMENT_GUIDE — VA Điều Vận

## 📑 Mục lục

- [Yêu cầu server](#yêu-cầu-server)
- [Biến môi trường production](#biến-môi-trường-production)
- [Build & release](#build--release)
- [Nginx (ví dụ)](#nginx-ví-dụ)
- [PHP-FPM](#php-fpm)
- [Supervisor — queue workers](#supervisor--queue-workers)
- [Cron — scheduler](#cron--scheduler)
- [SSL / domain](#ssl--domain)
- [Docker / Sail](#docker--sail)
- [Backup](#backup)
- [Rollback](#rollback)
- [Zero-downtime (thực tiễn)](#zero-downtime-thực-tiễn)

---

## Yêu cầu server

| Thành phần | Khuyến nghị |
|------------|-------------|
| OS | Linux x86_64 (Ubuntu 22.04 LTS hoặc tương đương) |
| PHP | 8.1+ với extensions Laravel |
| Web server | Nginx + PHP-FPM |
| Database | MySQL 8 |
| Queue | Redis **hoặc** MySQL `jobs` table |
| Process manager | Supervisor |
| Node | 18+ **trên build machine** (CI hoặc runner deploy) |

---

## Biến môi trường production

Tối thiểu:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dispatch.example.com

DB_*=...

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

CACHE_DRIVER=redis
SESSION_DRIVER=redis

DISPATCH_NOTIFICATIONS_QUEUE_DEFAULT=default
DISPATCH_NOTIFICATIONS_QUEUE_URGENT=urgent-notifications

GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=https://dispatch.example.com/auth/google/callback
GOOGLE_ALLOWED_EMAIL_DOMAINS=your-school.edu.vn

VAPID_PUBLIC_KEY=...
VAPID_PRIVATE_KEY=...
VAPID_SUBJECT=mailto:ops@example.com
```

---

## Build & release

Trên server hoặc CI:

```bash
cd /var/www/va-dieuvan
git pull origin main

composer install --no-dev --optimize-autoloader

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

npm ci
npm run build

php artisan storage:link || true
```

Quyền thư mục:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache
```

---

## Nginx (ví dụ)

```nginx
server {
    listen 443 ssl http2;
    server_name dispatch.example.com;
    root /var/www/va-dieuvan/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    # Vite `/public/build/` — luôn trả static hoặc 404; không fallback `index.php` (tránh HTML `text/html` cho `*.js` → lỗi MIME module script).
    location ^~ /build/ {
        expires 7d;
        access_log off;
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ^~ /api/ {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2)$ {
        expires 7d;
        access_log off;
        try_files $uri =404;
    }

    ssl_certificate     /etc/letsencrypt/live/dispatch.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/dispatch.example.com/privkey.pem;
}
```

**Lưu ý SPA:** route cuối Laravel `Route::view('/{any?}', 'welcome')` phục vụ Vue — `try_files` fallback `index.php` là đủ.

**MIME / Vite (`Failed to load module script … text/html`):** Nếu request tới `/build/assets/*.js` nhưng file không có trên disk (quên `npm run build`), Nginx không được đẩy request đó sang Laravel dưới dạng HTML. Khối `location ^~ /build/` và pattern `\.(js|css|…)` với `try_files $uri =404` là bắt buộc. Trên app, regex catch-all không khớp `build/` / `storage/` (`routes/web.php`) để không trả SPA HTML cho các URL build.

---

## PHP-FPM

- `memory_limit` ≥ `256M` (tuỳ báo cáo/PDF).
- `upload_max_filesize` / `post_max_size` đủ cho upload chứng từ.

---

## Supervisor — queue workers

`/etc/supervisor/conf.d/va-dieuvan-worker.conf`:

```ini
[program:va-dieuvan-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/va-dieuvan/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --queue=default,urgent-notifications
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/va-dieuvan/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start va-dieuvan-worker:*
```

Nếu `QUEUE_CONNECTION=database`, đổi `redis` → `database` và đảm bảo đã `migrate` bảng `jobs`.

---

## Cron — scheduler

Crontab user chạy PHP:

```cron
* * * * * cd /var/www/va-dieuvan && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler đã đăng ký: `cargo:sla-check` mỗi 5 phút (`app/Console/Kernel.php`).

---

## SSL / domain

- DNS A/AAAA trỏ server.
- Certbot Let’s Encrypt hoặc chứng chỉ nội bộ.

---

## Docker / Sail

Repo có `laravel/sail` dev dependency — có thể dùng Sail cho môi trường dev đồng nhất. Production thường triển khai VM/K8s riêng; **không** có file `docker-compose.yml` production cố định trong phạm vi tài liệu này (tuỳ team).

---

## Backup

| Đối tượng | Gợi ý |
|-----------|--------|
| MySQL | `mysqldump` hàng ngày + retention |
| Files | `storage/app` nếu lưu upload local; S3 thì backup bucket policy/versioning |
| Code | Git tags/release |

---

## Rollback

1. `git checkout <previous_tag>`
2. `composer install --no-dev`
3. **Cẩn trọng:** `migrate:rollback` chỉ khi migration backward an toàn — thường **restore DB snapshot** + deploy code cũ.
4. `php artisan config:cache && php artisan route:cache`
5. `npm run build` (commit build artifact hoặc rebuild)

---

## Zero-downtime (thực tiễn)

| Bước | Hành động |
|------|-----------|
| 1 | Bật maintenance nhẹ (tuỳ chính sách) hoặc deploy slot |
| 2 | Pull code mới trên thư mục release |
| 3 | `composer install`, `migrate --force`, build asset |
| 4 | Symlink `current` → release mới (Capistrano-style) hoặc reload PHP-FPM |
| 5 | `supervisorctl restart` workers sau deploy |

Worker đang xử lý job: dùng `--max-time` và graceful restart Supervisor để tránh cắt ngang không cần thiết.

---

## Liên quan

- [ENVIRONMENT_SETUP.md](./ENVIRONMENT_SETUP.md)
- [QUEUE_EVENT_CRON.md](./QUEUE_EVENT_CRON.md)
