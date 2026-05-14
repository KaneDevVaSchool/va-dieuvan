# TROUBLESHOOTING — VA Điều Vận

## 📑 Mục lục

- [Queue / notification](#queue--notification)
- [Permission / 403](#permission--403)
- [Storage / upload](#storage--upload)
- [Redis / cache](#redis--cache)
- [Session / OAuth](#session--oauth)
- [Deploy / migrate](#deploy--migrate)
- [CORS](#cors)
- [Nginx](#nginx)
- [Docker / Sail](#docker--sail)
- [Conflict 409 optimistic lock](#conflict-409-optimistic-lock)
- [HTTP 429 throttle](#http-429-throttle)

---

## Queue / notification

| Triệu chứng | Không thấy notification hoặc worker “đứng” |
| Nguyên nhân | `QUEUE_CONNECTION=sync` chạy trong request; production quên `queue:work`; Redis/database chưa migrate `jobs` |
| Giải pháp | Đặt `QUEUE_CONNECTION=redis` hoặc `database`; `php artisan migrate`; Supervisor chạy `queue:work`; kiểm tra `DISPATCH_NOTIFICATIONS_QUEUE_*` khớp `--queue=` |
| Phòng ngừa | Monitor worker uptime; log `dispatch.debug_notification_log` khi cần |

---

## Permission / 403

| Triệu chứng | `403` trên API staff hoặc “permission denied” |
| Nguyên nhân | User thiếu permission Spatie; cache permission; sai guard |
| Giải pháp | Chạy `php artisan permission:cache-reset`; kiểm tra role trong DB; chạy `db:seed --class=RbacSeeder` trên môi trường dev |
| Phòng ngừa | Quy trình onboarding user luôn gán role qua admin UI/API |

---

## Storage / upload

| Triệu chứng | 404 ảnh/file hoặc lỗi ghi upload |
| Nguyên nhân | Thiếu `storage:link`; quyền OS `storage/`; disk sai |
| Giải pháp | `php artisan storage:link`; `chmod` www-data; kiểm tra `FILESYSTEM_DISK` |
| Phòng ngừa | Deploy checklist luôn có bước link + quyền |

---

## Redis / cache

| Triệu chứng | Queue/cache lỗi connection |
| Nguyên nhân | Redis down; sai `REDIS_PASSWORD`; firewall |
| Giải pháp | `redis-cli ping`; chỉnh `.env`; mở port nội bộ |
| Phòng ngừa | Healthcheck Redis |

---

## Session / OAuth

| Triệu chứng | Google login redirect lỗi state hoặc không có session |
| Nguyên nhân | `SESSION_DOMAIN` sai HTTPS/mixed content; cookie blocked |
| Giải pháp | HTTPS đồng nhất; cookie `SameSite` tuỳ browser |
| Phòng ngừa | Test OAuth trên URL production thật |

**Đăng nhập Google báo domain không hợp lệ:** kiểm tra `GOOGLE_ALLOWED_EMAIL_DOMAINS`.

---

## Deploy / migrate

| Triệu chứng | 500 sau deploy hoặc migrate fail |
| Nguyên nhân | Thiếu `APP_KEY`; migrate conflict; config cache cũ |
| Giải pháp | `php artisan key:generate`; `migrate --force`; `config:clear` rồi `config:cache` |
| Phòng ngừa | CI chạy migrate trên staging trước |

---

## CORS

| Triệu chứng | Browser chặn request API |
| Nguyên nhân | Frontend origin khác `APP_URL`; cấu hình `config/cors.php` |
| Giải pháp | Cho phép origin SPA trong CORS config Laravel |
| Phòng ngừa | Host API và SPA rõ ràng trong env |

> Sanctum stateful middleware **không** bật trong `api` group của `Kernel` — client phải dùng **Bearer token**, không kỳ vọng cookie session API.

---

## Nginx

| Triệu chứng | 404 mọi route SPA hoặc chỉ trắng |
| Nguyên nhân | `root` không trỏ `public/`; thiếu `try_files` |
| Giải pháp | Sửa server block như [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md) |
| Phòng ngừa | Dùng snippet chuẩn Laravel |

---

## Docker / Sail

| Triệu chứng | Container không resolve DB host |
| Nguyên nhân | Sai service name trong `.env` Sail |
| Giải pháp | Dùng hostname `mysql`, `redis` theo compose Sail |

---

## Conflict 409 optimistic lock

| Triệu chứng | “Dữ liệu đã thay đổi, vui lòng tải lại” khi assign/reschedule |
| Nguyên nhân | `lock_version` không khớp — hai người sửa cùng lúc |
| Giải pháp | Refresh UI và submit lại |
| Phòng ngừa | UX disable nút khi đang submit |

---

## HTTP 429 throttle

| Triệu chứng | `Too Many Requests` |
| Nguyên nhân | Vượt `throttle` trên route (đặc biệt export PDF, OCR, push subscription) |
| Giải pháp | Giảm tần suất client; điều chỉnh rate limit nếu NAT chung IP |
| Phòng ngừa | Debounce / backoff phía SPA |

---

## Feature toggle

| Triệu chứng | Route SPA báo không khả dụng |
| Nguyên nhân | `feature_toggles.is_enabled = false` hoặc middleware `feature` |
| Giải pháp | Bật trong admin API hoặc DB |

---

## Payment API không hoạt động

| Triệu chứng | Không có endpoint đối soát |
| Nguyên nhân | `ReconciliationController` **chưa được đăng ký route** |
| Giải pháp | Thêm route vào `routes/api/spa/dispatch-staff-*.php` và kiểm tra permission |

---

## Liên quan

- [ENVIRONMENT_SETUP.md](./ENVIRONMENT_SETUP.md)
- [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md)
- [QUEUE_EVENT_CRON.md](./QUEUE_EVENT_CRON.md)
