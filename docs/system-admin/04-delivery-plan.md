# 04 — Delivery Plan

> Kế hoạch triển khai từ tài liệu → code. Phân theo phase, có WBS, ước lượng (người-ngày, 1 BE + 1 FE song song), rủi ro, DoD.

## 1. Chiến lược tổng thể

- **Greenfield**: build module mới song song, bật bằng cờ `feature.module.system_v2`, cutover khi smoke test pass, rồi gỡ màn cũ.
- **Backend-first per epic**: schema + API + test trước, FE bám API stub/contract.
- **Vertical slices**: hoàn thiện trọn vẹn từng epic (DB→API→UI→test) thay vì làm ngang.

## 2. Roadmap theo Phase

### Phase 0 — Nền móng (Foundation)
| WBS | Hạng mục | Ước lượng |
|---|---|---|
| 0.1 | Migrations greenfield (7 file, mục 9.1 technical) | 2 BE |
| 0.2 | Seeders + `system:migrate-rbac` command | 2 BE |
| 0.3 | Service nền: `AuditLogger`, `EffectivePermissionResolver`, `RoleAssignmentSyncService` | 3 BE |
| 0.4 | Middleware `CaptureRequestContext` + UA parser | 1 BE |
| 0.5 | FE core: `SystemLayout`, `DataTable`, `AppDrawer`, design tokens/dark mode | 4 FE |
| 0.6 | Pinia stores skeleton + axios services + router `/system/*` | 2 FE |

**Output:** schema chạy được, data cũ migrate được, FE shell + DataTable dùng chung sẵn sàng.

### Phase 1 — Roles
| WBS | Hạng mục | Est |
|---|---|---|
| 1.1 | API Roles (CRUD, clone, copy-perms, lock, restore, trashed, history) + FormRequest + Resource + Policy | 3 BE |
| 1.2 | RolesView + Drawer (tabs Thông tin/Quyền/User/Lịch sử) + RoleChip + ColorPalette | 4 FE |
| 1.3 | Test: Feature test API + AC Roles | 1.5 BE/QA |

### Phase 2 — Permissions Matrix
| WBS | Hạng mục | Est |
|---|---|---|
| 2.1 | API matrix + sync (atomic, audit before/after) | 2.5 BE |
| 2.2 | PermissionsMatrixView (sticky head/col, bulk tick module/group/column, staged save) — nâng cấp `PermissionMatrix.vue` | 4 FE |
| 2.3 | Test sync atomicity + sensitive guard | 1.5 BE/QA |

### Phase 3 — Role Assignment
| WBS | Hạng mục | Est |
|---|---|---|
| 3.1 | API assignments (single/bulk/revoke/effective) + queue cho bulk + `ExpireAssignments` cron | 3 BE |
| 3.2 | AssignmentsView (master list + right panel effective perms) + bulk bar + Drawer gán (temporary) | 4 FE |
| 3.3 | Test: effective-permission resolver, temporal logic, bulk job | 2 BE/QA |

### Phase 4 — Menu Management
| WBS | Hạng mục | Est |
|---|---|---|
| 4.1 | API menu (CRUD, reorder batch, /menu/me, preview) + validate depth/cycle | 2.5 BE |
| 4.2 | MenuSeeder từ `nav.js` | 1 BE |
| 4.3 | MenuManagementView (MenuTree drag-drop, detail panel, preview-by-role) | 4 FE |
| 4.4 | Đấu nối sidebar app thật vào `/menu/me` (thay `useNavSections` tĩnh) | 2 FE |
| 4.5 | Test reorder/cycle/visibility/permission filter | 1.5 BE/QA |

### Phase 5 — Audit Logs
| WBS | Hạng mục | Est |
|---|---|---|
| 5.1 | API audit (list/detail/export/filters) + Observer gắn vào model module | 2.5 BE |
| 5.2 | AuditLogsView + Drawer + DiffViewer + export | 3.5 FE |
| 5.3 | `audit:purge` cron + retention config | 1 BE |
| 5.4 | Test diff + filter + export + append-only | 1.5 BE/QA |

### Phase 6 — Dashboard
| WBS | Hạng mục | Est |
|---|---|---|
| 6.1 | API dashboard (aggregations, cache 60s) | 2 BE |
| 6.2 | SystemDashboardView (StatWidget, TopUsers, AnomalyList, SecurityAlerts, ECharts) + deep-links | 3 FE |
| 6.3 | Test số liệu + cache | 1 BE/QA |

### Phase 7 — Hardening & Cutover
| WBS | Hạng mục | Est |
|---|---|---|
| 7.1 | Data Table nâng cao: Filter Builder, Saved Filter, Column prefs (`/me/table-prefs`) | 4 FE + 1 BE |
| 7.2 | Responsive Desktop/Tablet pass | 2 FE |
| 7.3 | Security review (mục 10 technical) + perf (index, N+1, lazy matrix) | 2 BE |
| 7.4 | E2E happy paths + regression | 2 QA |
| 7.5 | Cutover: bật cờ, gỡ màn cũ (`SystemRolesView` cũ…), dọn `nav.js` | 1 |

> **Ước lượng thô tổng:** ~38 BE-day + ~43 FE-day + ~11 QA-day (song song ~6–8 tuần với 1 BE + 1 FE + 1 QA part-time). Con số để lập kế hoạch, không cam kết.

## 3. Sơ đồ phụ thuộc

```
Phase 0 ──► Phase 1 (Roles) ──► Phase 2 (Matrix) ──► Phase 3 (Assignment)
   │                                                       │
   ├──────────────────────► Phase 4 (Menu) ◄──────────────┘ (cần effective perms)
   ├──────────────────────► Phase 5 (Audit) (cần AuditLogger từ P0)
   └──────────────────────► Phase 6 (Dashboard) (cần data từ P1–P5)
Phase 7 sau cùng.
```
- Phase 4/5 có thể chạy song song sau Phase 1.
- Phase 6 phụ thuộc dữ liệu các phase trước (làm cuối).

## 4. Rủi ro & giảm thiểu

| Rủi ro | Mức | Giảm thiểu |
|---|---|---|
| Migrate RBAC sai → mất quyền user | Cao | Script idempotent + dry-run + backup + smoke test trên staging trước |
| Recreate `audit_logs` mất lịch sử | TB | Archive bảng cũ (`audit_logs_archive`) thay vì drop thẳng |
| Menu động sai filter → user mất menu | Cao | Fallback seed từ `nav.js`; preview-by-role; cờ rollback về menu tĩnh |
| Matrix lớn → chậm | TB | Lazy theo module; sync gửi delta; index `(module,action_type)` |
| Bulk assign quá tải | TB | Queue + rate-limit + báo tiến trình |
| Spatie cache lệch sau sync | TB | Gọi `forgetCachedPermissions()` sau mỗi sync/assign |
| Scope creep (Data Scope quay lại) | TB | Đã chốt bỏ; schema chừa `parent_id` đủ linh hoạt, không mở thêm |

## 5. Definition of Done (mỗi epic)

- [ ] Migration + seeder chạy sạch (`migrate:fresh --seed`).
- [ ] API có FormRequest + Policy + Resource + Feature test (happy + lỗi chính).
- [ ] Mọi mutation ghi Audit Log (kiểm chứng bằng test).
- [ ] UI khớp wireframe; Drawer-first; sticky bar; dark mode OK; responsive Desktop/Tablet.
- [ ] DataTable: ẩn/hiện cột, saved filter, export, bulk action hoạt động.
- [ ] Tất cả Acceptance Criteria của epic pass (QA ký).
- [ ] Không N+1 (kiểm bằng query log); không lỗi console.
- [ ] Tài liệu API cập nhật nếu lệch so với mục 8 technical.

## 6. Việc cần stakeholder xác nhận trước khi build

1. **Danh mục module nghiệp vụ thật** (HR/Kho/Mua sắm…): spec đưa ví dụ — cần chốt resource/action thực tế để seed permission (mục 5.2 technical chỉ là đề xuất).
2. **Role "Auditor"** (chỉ đọc audit) có tạo không?
3. **Retention audit** bao nhiêu tháng?
4. **Vai trò kế thừa (`parent_id`)**: bật hay tạm ẩn ở v1? (schema đã chừa sẵn).
5. **Mobile**: chấp nhận read-only tối thiểu như đề xuất?

> Các điểm 1–5 không chặn việc bắt đầu Phase 0 (foundation). Nên chốt trước Phase 1.

## 7. Checklist cutover (Phase 7.5)

- [ ] Bật cờ `feature.module.system_v2` trên staging → UAT pass.
- [ ] Backup DB production (RBAC + audit).
- [ ] Chạy `system:migrate-rbac` (dry-run → thật).
- [ ] Bật cờ production; theo dõi 24h.
- [ ] Gỡ view cũ: `SystemRolesView/SystemPermissionsView/SystemUserRolesView/SystemFeatureTogglesView` + routes cũ + `nav.js` phần menu (giữ icon map).
- [ ] Đóng tài liệu: cập nhật README module.
