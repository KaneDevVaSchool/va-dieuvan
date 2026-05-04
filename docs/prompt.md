You are a Senior Fullstack Developer & System Architect.

Apply strictly:

* @.cursorrules
* @.cursor/rules/core.mdc
* @.cursor/rules/vue.mdc
* @.cursor/rules/filter-toolbar-vue-tailwind.mdc

---

# 🎯 OBJECTIVE

Build a reusable internal Design System and refactor admin UI to achieve:

* Consistent UI/UX across all admin pages
* Reusable components (Button, Modal, Table, FilterToolbar)
* Clean architecture (separation of concerns)
* Easy for non-technical users
* Scalable for future features

---

# 🧱 PHASE 1 — DESIGN SYSTEM (FOUNDATION)

Create reusable components under:

resources/js/src/components/ui/

---

## 1. BaseButton.vue

Support:

* Variants:

  * primary
  * secondary
  * danger
  * ghost

* States:

  * loading (spinner)
  * disabled

* Props:

  * variant
  * size (sm, md, lg)
  * loading
  * disabled

* Behavior:

  * Disable when loading
  * Consistent Tailwind styling

---

## 2. BaseModal.vue

Features:

* Controlled via v-model

* Slots:

  * header
  * body
  * footer

* Behavior:

  * ESC to close
  * click outside to close
  * smooth transition

* Must support:

  * confirm dialog
  * form modal

---

## 3. BaseTable.vue

Core responsibilities:

* Render table layout

* Accept props:

  columns: [
  { key, label, width?, align? }
  ]

  data: array

* Slots:

  * cell-{column}
  * actions

* Features:

  * loading state
  * empty state
  * consistent spacing
  * responsive

---

## 4. FilterToolbar.vue

Follow:
@.cursor/rules/filter-toolbar-vue-tailwind.mdc

Features:

* Search input (debounced)
* Select filters
* Status filter
* Emit:

  * onSearch
  * onFilterChange

---

# 🧱 PHASE 2 — REUSABLE LOGIC

## 1. useTable.ts (composable)

Handle:

* loading
* pagination (if exists)
* filter state
* search debounce

---

# 🧱 PHASE 3 — ADMIN UI REFACTOR

Refactor pages:

* Roles
* Permissions
* Feature Toggles
* Audit Logs

---

## REQUIREMENTS

### 1. Replace UI with Design System

* Buttons → BaseButton
* Modals → BaseModal
* Tables → BaseTable
* Filters → FilterToolbar

---

### 2. ADD CREATE FLOW VIA MODAL

* Replace inline forms with:
  [ + Add New ] button → open modal

---

### 3. REMOVE NOISE

* Remove:

  * tooltips
  * helper text
  * redundant labels

---

### 4. STANDARDIZE TABLE

* Bold primary text
* Muted secondary text
* Align columns
* Add action column

---

### 5. FEEDBACK SYSTEM

* Success / Error:

  * toast or inline

* Confirm dialog:

  * delete
  * destructive actions

---

### 6. LOADING UX

* Disable buttons when loading
* Show spinner
* No flicker

---

### 7. CONSISTENCY (STRICT)

All pages must share:

* Same spacing
* Same button style
* Same modal style
* Same table layout
* Same filter toolbar

---

# ⚠️ SCOPE (VERY IMPORTANT)

* DO NOT change:

  * API logic
  * store logic
  * backend

* Only refactor UI layer

* Keep existing behavior

---

# 🧱 CODE RULES

* Vue 3 `<script setup>`
* Composition API only
* Clean, readable code
* Extract reusable components only when needed

---

# 🎯 OUTPUT FORMAT

Return in order:

## 1. Design System Components

* BaseButton.vue
* BaseModal.vue
* BaseTable.vue
* FilterToolbar.vue

## 2. Composable

* useTable.ts

## 3. Refactored Pages

* Each page full updated code

---

# 🧾 EXPLANATION

Explain briefly in Vietnamese:

* Design decisions
* How components improve scalability
* How consistency is achieved

---

# 🚫 STRICT RULES

* Do NOT over-engineer
* Do NOT introduce new architecture
* Do NOT break existing behavior
* Do NOT refactor unrelated logic

---

# 🎯 FINAL GOAL

The admin UI must feel:

* Clean
* Consistent
* Fast
* Professional
* Scalable for large systems
🔥 CÁCH DÙNG ĐÚNG (RẤT QUAN TRỌNG)
❌ Sai (tốn request + dễ lỗi)
Chạy toàn bộ prompt 1 lần
✅ Đúng (chuẩn team)
Step 1 — Generate Design System
Build Design System only (Phase 1)
Step 2 — Generate BaseTable + FilterToolbar
Build Phase 2 (composable + table logic)
Step 3 — Refactor từng page
Refactor @SystemPermissionsView.vue using new components
🎯 KẾT QUẢ

Sau khi áp dụng:

🔥 UI đồng bộ toàn hệ thống
🔥 Dev mới vào code rất nhanh
🔥 Giảm 60–70% effort cho UI mới
🔥 Cursor chạy ổn định, ít lỗi
Nếu muốn nâng cấp tiếp (rất đáng)

Tôi có thể giúp bạn:

⚡ Thêm Dark mode + theme system
⚡ Build Design Token (spacing, color, typography)
⚡ Setup Storybook cho component

👉 Chỉ cần nói:
"build design token + storybook"
