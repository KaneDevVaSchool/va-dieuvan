# Scripts phát triển (VA Điều vận)

Chạy trong PowerShell từ **thư mục gốc repo** hoặc từ đây; các script tự `cd` về root.

## `dev-bootstrap.ps1`

Cài dependency và chuẩn bị môi trường local (máy mới hoặc sau khi clone).

```powershell
.\scripts\dev-bootstrap.ps1
.\scripts\dev-bootstrap.ps1 -Migrate
.\scripts\dev-bootstrap.ps1 -SkipComposer -SkipNpm
```

## `New-ApiModule.ps1`

Scaffold **backend** (controller API, form request mẫu) + **checklist** việc còn lại (route spa, policy, Vue, test).  
Không gọi mạng; chỉ `php artisan` + ghi file.

```powershell
# Ví dụ: module "Phiếu taxi" trong namespace Api\Taxi
.\scripts\New-ApiModule.ps1 -Name "TaxiVoucher" -Subdir "Taxi" -IncludeVue

# Chỉ controller + request, có model + migration
.\scripts\New-ApiModule.ps1 -Name "BudgetAlert" -Subdir "Costs" -Model -Migration
```

Tham số:

| Tham số       | Mô tả |
|---------------|--------|
| `-Name`       | PascalCase, ví dụ `TaxiVoucher` |
| `-Subdir`     | Thư mục con dưới `App\Http\Controllers\Api\`, ví dụ `Taxi`, `D2D` |
| `-IncludeVue` | Tạo thêm `resources/js/src/views/<subdir>/<Name>View.vue` (khung rỗng) |
| `-Model`      | `artisan make:model` |
| `-Migration`  | `artisan make:migration` (bảng snake_case của Name) |
| `-DryRun`     | Chỉ in lệnh, không chạy |

Checklist được ghi vào `docs/scaffold/<name>-CHECKLIST.md` (git có thể ignore hoặc commit tùy team).

## `module-gap-report.php`

In ra **Markdown**: route đã khai báo trong `routes/api/spa/*.php` và path Vue trong `router/index.js`.  
Dùng để đối chiếu “đã có endpoint / đã có màn” trước khi mở module mới — **không** gọi API.

```powershell
php scripts\module-gap-report.php
php scripts\module-gap-report.php --out=docs\scaffold\SURFACE_REPORT.md
```

## Gợi ý backlog module (SRS vs lộ trình trong app)

Trong SPA: `RoadmapSuggestionsView.vue` (P1: RBAC UI, OCR, chọn xe/TX, thông báo; P2: map; P3: GPS, ký số, BI).  
SRS: taxi voucher/NCC riêng, duyệt 2 cấp mobile, GPS giai đoạn 2 — bổ sung dần bằng `New-ApiModule` + checklist.

Xem thêm: [docs/TIEP_TUC_PHAT_TRIEN.md](../docs/TIEP_TUC_PHAT_TRIEN.md).
