---
name: reports-and-mobile-upgrade
description: Scope/decisions for the June 2026 batch — driver cost date, target picker, portal responsive, reports filters/export, mobile UX
metadata:
  type: project
---

Batch of 5 tasks from user (2026-06-25). Branch `dev`. Build via `node node_modules/vite/bin/vite.js build` (npm/npx native-spawn is broken in this shell).

**Done & build-verified:**
- Task 1 — driver/costs/new: added `reported_on` date field for standalone (no-trip) cost. Migration `2026_06_25_120000_add_reported_on_to_trip_costs`, TripCost fillable+cast, SubmitTripCostRequest rule `nullable,date,before_or_equal:today`, shown in DriverCostDetailView. **Needs `php artisan migrate`.**
- Task 4 — allocation-target picker now single-select (radio, click again clears) + removed "Thêm đối tượng" custom block. Applied to BOTH DispatchRequestCreateView.vue and portal/PortalCreateView.vue (shared feature). `selectSingleTarget()`.
- Task 5 — portal/new responsive: portal-scoped `.dw-input` got `min-h-[2.75rem]` + `text-base sm:text-sm` (stop iOS zoom) + `.dw-form-grid-2` `items-start`. Affects both portal-create views.

**Task 2 (reports) — user decisions:** add filters to BOTH reports + my own upgrade ideas. Cost report (mng/reports/costs): cost type, provider, amount range (min/max), unit/dept. Driver-freq (mng/reports/driver-frequency): vehicle, month. Export = support BOTH "xuất theo filter" AND "xuất tất cả" (ignore filters), plus include a filter-summary header in the exported file. Backend already validates `type`/`provider` (TripCostReportRequest) and `vehicle_id` (DriverFrequencyReportRequest) but UI doesn't surface them yet.

**Task 3 (mobile):** DONE (2026-06-25) — TripHeroHeader + StaffRequestHeroHeader: 44px touch actions, route stacks vertical on mobile; tab nav snap scroll + min-h-11; TripDetailView tabs; list filter grids px-4; DatagridToolbarSearch text-base on mobile (iOS zoom); StaffRequest clone banner stacks; PortalTripTypeGrid taller tiles.

**Also:** Trip cost export max_amount validation fixed; driver_freq i18n; targets_selected_one copy.

Reports use toggleable filter-visibility (localStorage), xlsx/pdf export via api/reports.js, ECharts. Filter UI lives in components/reports/CostReportFilters.vue & DriverFrequencyFilters.vue.
