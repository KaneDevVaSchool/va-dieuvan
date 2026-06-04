# System Admin Module — Redesign & Rebuild

> Bộ tài liệu thiết kế lại module **"Hệ Thống"** theo hướng **Enterprise SaaS Admin** (tham chiếu Jira / Notion / Atlassian Admin / Microsoft 365 Admin Center).
>
> Phạm vi: **xóa toàn bộ module Hệ Thống hiện tại** và xây lại từ đầu (greenfield schema, drop & recreate, kèm script migrate dữ liệu cũ).

---

## 📚 Mục lục tài liệu

| # | Tài liệu | Dành cho | Nội dung |
|---|----------|----------|----------|
| 00 | [Overview & Scope](./00-overview.md) | Tất cả | Bối cảnh, mục tiêu, phạm vi, quyết định kiến trúc, sitemap, glossary, IA |
| 01 | [Business Analysis (BA)](./01-ba-business-analysis.md) | BA | Business Flow, Use Case, User Story, Acceptance Criteria |
| 02 | [UX Design](./02-ux-design.md) | UI/UX | Sitemap, User Flow, Wireframe, Responsive, Design System, Component spec |
| 03 | [Technical Architecture](./03-technical-architecture.md) | Developer | Database, ERD, API Spec, Permission/Menu/Audit Architecture, Security |
| 04 | [Delivery Plan](./04-delivery-plan.md) | PM / Tech Lead | Kế hoạch triển khai theo phase, WBS, ước lượng, rủi ro, Definition of Done |

---

## 🎯 Tiêu chí hoàn thành tài liệu (Done = đủ để…)

- **BA** viết được BRD đầy đủ.
- **UI/UX** thiết kế được Figma ngay.
- **Developer** code được ngay (schema + API + permission rõ ràng).
- **QA** viết được Test Case ngay (Acceptance Criteria dạng Given/When/Then).

---

## 🧩 6 màn hình con của module

1. **Dashboard Hệ Thống** — Trang tổng quan quản trị.
2. **Vai Trò (Roles)** — Quản lý vai trò: clone, sao chép quyền, khóa, xóa mềm, lịch sử.
3. **Quyền Thao Tác (Permissions)** — Ma trận Role × Permission (CRUD + Workflow).
4. **Gán Vai Trò (Role Assignment)** — Gán/thu hồi hàng loạt, hiệu lực theo thời gian, panel quyền thực tế.
5. **Quản Lý Menu (Menu Management)** — Menu động DB-backed, kéo thả, phân quyền menu (thay "Bật Tắt Menu").
6. **Nhật Ký Hoạt Động (Audit Logs)** — Theo dõi thao tác, IP/thiết bị/trình duyệt, Diff Viewer.

---

## ⚖️ Quyết định nền tảng (chốt với stakeholder)

| Quyết định | Lựa chọn đã chốt | Tác động |
|-----------|------------------|----------|
| **Data Scope** (Toàn HT / Pháp nhân / Điểm trường / Phòng ban / Chính mình) | ❌ **Bỏ tính năng** — không làm phân quyền theo phạm vi dữ liệu | Permissions = `Module × Action`. Không cần bảng pháp nhân/điểm trường. Đơn giản hóa toàn bộ ERD & API. |
| **Chiến lược schema RBAC** | ✅ **Greenfield — drop & recreate** | Thiết kế schema mới sạch; kèm script migrate 7 roles + 41 permissions cũ. |
| **Nền RBAC** | ✅ Giữ `spatie/laravel-permission` làm lõi, mở rộng bằng bảng phụ | Tận dụng package đã cài; tránh viết lại engine phân quyền. |
| **Định dạng deliverable** | ✅ Nhiều file markdown trong `docs/system-admin/` | Giao việc theo từng vai trò. |

---

## 🛠 Tech stack hiện tại (giữ nguyên)

- **Backend:** Laravel 10, PHP 8.1+, `spatie/laravel-permission` v6, Sanctum, `openspout/openspout` (Excel), `barryvdh/laravel-dompdf`.
- **Frontend:** Vue 3 (`<script setup>`), Pinia, Vue Router 4, Tailwind 3, Vite 5, Heroicons, ECharts.
- **Auth:** SPA + Sanctum; route guard theo `meta.permission` + `meta.featureKey`.

---

## 📌 Trạng thái

Tài liệu PLAN/BRIEF — **chưa code**. Mọi schema/API/wireframe trong bộ này là đề xuất để review trước khi build.
