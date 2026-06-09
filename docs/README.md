# VA Điều Vận — Tài Liệu Dự Án

**Phiên bản**: Laravel 10 + Vue 3 SPA  
**Mục đích**: Hệ thống quản lý điều vận xe cho trường học VA  
**Kiểm tra lần cuối**: 2026-06-10

---

## Mục lục

### Kiến trúc
- [Tổng quan hệ thống](architecture/system-overview.md)
- [Luồng xử lý ứng dụng](architecture/application-flow.md)
- [Tích hợp bên ngoài](architecture/integration-flow.md)
- [Triển khai & hạ tầng](architecture/deployment.md)

### Cơ sở dữ liệu
- [ERD tổng quan](database/erd.md)
- [Schema chi tiết](database/schema.md)
- [Indexes & hiệu năng](database/indexes.md)
- [Quan hệ giữa các bảng](database/relationships.md)

### Modules nghiệp vụ
- [Dashboard](modules/dashboard.md)
- [Người dùng & phân quyền](modules/users.md)
- [Yêu cầu điều vận](modules/requests.md)
- [Quản lý chuyến đi](modules/trips.md)
- [Chương trình vận chuyển (TP)](modules/transport-program.md)
- [Tài xế](modules/drivers.md)
- [Hàng hóa (Cargo)](modules/cargo.md)
- [Chi phí & thanh toán](modules/costs.md)
- [Thông báo](modules/notifications.md)
- [Báo cáo](modules/reports.md)

### API
- [Danh sách endpoints](api/endpoints.md)
- [Xác thực & phân quyền](api/authentication.md)
- [Ví dụ request/response](api/examples.md)

### Nghiệp vụ
- [Quy trình điều vận](business/workflows.md)
- [Business rules](business/rules.md)
- [Use cases chính](business/use-cases.md)

### Audit & Báo cáo
- [Technical Debt](audit/technical-debt.md)
- [Báo cáo bảo mật](audit/security-report.md)
- [Báo cáo hiệu năng](audit/performance-report.md)
- [Kế hoạch refactor](audit/refactor-plan.md)

---

## Tổng quan nhanh

| Mục | Chi tiết |
|-----|----------|
| Framework | Laravel 10, PHP 8.1+ |
| Frontend | Vue 3, Pinia, Vue Router, Tailwind CSS |
| Database | MySQL (với Soft Delete, optimistic locking) |
| Auth | Laravel Sanctum + Google OAuth |
| Queue | Laravel Queue (database driver) |
| Push | Web Push API (VAPID) |
| PWA | Vite PWA Plugin, Workbox |
| PDF | DomPDF |
| Excel | OpenSpout, PhpSpreadsheet |
| Permission | Spatie Laravel Permission |
| Tests | PHPUnit (45+ feature, 5+ unit) |
