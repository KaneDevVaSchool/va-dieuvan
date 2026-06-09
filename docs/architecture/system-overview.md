# Tổng quan hệ thống — VA Điều Vận

## Mô hình kiến trúc

```
┌─────────────────────────────────────────────────────────────┐
│                      FRONTEND (Vue 3 SPA)                    │
│                                                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │ Dispatch │  │  Driver  │  │  Portal  │  │  Admin   │  │
│  │ Web App  │  │ Web App  │  │  (Dept)  │  │  System  │  │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘  │
│       └─────────────┴──────────────┴──────────────┘        │
│                            │                                 │
│                     Vue Router + Pinia                       │
└────────────────────────────┬────────────────────────────────┘
                             │ REST API (JSON)
                             │ Laravel Sanctum (token auth)
┌────────────────────────────┴────────────────────────────────┐
│                      BACKEND (Laravel 10)                    │
│                                                              │
│  HTTP Layer:                                                 │
│  ┌────────────────────────────────────────────────┐         │
│  │  Middleware → FormRequest → Controller         │         │
│  │  (Auth, Role, Feature, Idempotency, Throttle)  │         │
│  └──────────────────┬─────────────────────────────┘         │
│                     │                                         │
│  Business Layer:                                             │
│  ┌────────────────────────────────────────────────┐         │
│  │  Services (50+)  │  Actions (4)  │  Support    │         │
│  └──────────────────┬─────────────────────────────┘         │
│                     │                                         │
│  Data Layer:                                                 │
│  ┌────────────────────────────────────────────────┐         │
│  │  Models (Eloquent ORM)  │  Repositories (1)    │         │
│  └──────────────────┬─────────────────────────────┘         │
│                     │                                         │
│  Background:                                                 │
│  ┌─────────────────────────────────────────────────┐        │
│  │  Jobs │ Events/Listeners │ Commands (scheduled) │        │
│  └─────────────────────────────────────────────────┘        │
└─────────────────────────────────────────────────────────────┘
                             │
           ┌─────────────────┼──────────────────┐
           │                 │                  │
      ┌────┴────┐     ┌──────┴─────┐    ┌──────┴────┐
      │  MySQL  │     │  File/S3   │    │  Google   │
      │   DB    │     │  Storage   │    │  OAuth    │
      └─────────┘     └────────────┘    └───────────┘
```

## Các domain chính

### 1. Dispatch (Điều vận)
Lõi hệ thống. Quản lý yêu cầu điều vận từ khi tạo đến khi hoàn thành.

- **DispatchRequest**: Yêu cầu từ người dùng/portal
- **Trip**: Chuyến đi được điều phối từ yêu cầu
- **TripRecord**: Kết quả thực tế của chuyến
- **TripEvent**: Log sự kiện trong chuyến

### 2. Transport Program (TP)
Module chuyên biệt cho vận chuyển học sinh định kỳ.

- **TpProgram**: Chương trình vận chuyển
- **TpProgramDay**: Ngày thực hiện
- **TpStudent/TpEnrollment**: Học sinh đăng ký
- **TpTripExecution**: Thực thi chuyến đi
- **TpDayAbsence**: Điểm danh vắng

### 3. Portal
Giao diện dành cho trưởng đơn vị/nhân viên gửi yêu cầu.

### 4. Driver
Giao diện mobile-first cho tài xế.

### 5. Admin/System
Quản lý người dùng, phân quyền, cấu hình hệ thống.

## Stack công nghệ chi tiết

### Backend
| Package | Version | Mục đích |
|---------|---------|----------|
| laravel/framework | ^10.10 | Core framework |
| spatie/laravel-permission | ^6.25 | RBAC |
| laravel/sanctum | ^3.3 | API token auth |
| laravel/socialite | ^5.26 | Google OAuth |
| barryvdh/laravel-dompdf | ^3.1 | PDF generation |
| openspout/openspout | ^4.25 | Excel streaming |
| phpoffice/phpspreadsheet | ^5.7 | Excel phức tạp |
| minishlink/web-push | ^9.0 | Web Push notifications |

### Frontend
| Package | Version | Mục đích |
|---------|---------|----------|
| vue | ^3.5.0 | UI framework |
| pinia | ^2.3.0 | State management |
| vue-router | ^4.5.0 | SPA routing |
| tailwindcss | ^3.4.17 | CSS utility |
| echarts | ^6.0.0 | Charts |
| leaflet | ^1.9.4 | Maps |
| vue-i18n | ^9.14.5 | Internationalization |
| exceljs | ^4.4.0 | Excel client-side |
| vite-plugin-pwa | ^0.21.0 | PWA support |

## Phân vùng quyền truy cập

```
/api/...          → Sanctum auth required
  /portal/...     → Tất cả user đã đăng nhập
  /driver/...     → middleware: driver.spa (role: driver)
  /spa/...        → middleware: dispatch.web hoặc dispatch.staff
  /admin/...      → role: admin hoặc superadmin
```

## Mô hình phân quyền

Dùng Spatie Laravel Permission với các role:
- `superadmin`: Toàn quyền
- `admin`: Quản trị hệ thống  
- `dispatcher`: Điều phối viên
- `department_head`: Trưởng đơn vị
- `internal_user`: Nhân viên nội bộ (gửi yêu cầu)
- `driver`: Tài xế
- `accountant`: Kế toán
