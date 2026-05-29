<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import {
  FunnelIcon,
  ChevronDownIcon,
  PencilSquareIcon,
  TrashIcon,
  XMarkIcon,
  UserGroupIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import { useDetailsAutoClose } from '../../composables/useDetailsAutoClose.js'
import AppRowActionsMenu from '../../components/ui/AppRowActionsMenu.vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import RoleSlideOver from '../../components/system/RoleSlideOver.vue'
import { SEED_ROLE_PRESETS } from '../../config/systemSeedOptions'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import { debounceTrailing } from '../../composables/useDebounce'

const ROLES_FILTER_VIS_KEY = 'va.roles.filter_control_visibility_v2'
const FILTER_CONTROL_IDS   = ['search', 'origin', 'perm', 'users']

function defaultFilterControlVisibility() {
  return FILTER_CONTROL_IDS.reduce((acc, id) => { acc[id] = true; return acc }, {})
}

// ─── State ───────────────────────────────────────────────────────────────────

const loading    = ref(true)
const saving     = ref(false)
const items      = ref([])
const allPerms   = ref([])

/** null = closed; { role: null } = create mode; { role: <object> } = edit mode */
const slideOver  = ref(null)

const seedRoles       = SEED_ROLE_PRESETS
const seedRoleNameSet = new Set(seedRoles.map((x) => x.name))

const funnelDetailsRef = ref(null)
useDetailsAutoClose(funnelDetailsRef)
const filterControlVisible = reactive(defaultFilterControlVisibility())

const searchInput     = ref('')
const filterQ         = ref('')
const filterOrigin    = ref('all')
const filterPermScope = ref('all')
const filterUserScope = ref('all')

const filterControlDefs = Object.freeze([
  { id: 'search', label: 'Tìm trong danh sách' },
  { id: 'origin', label: 'Kiểu vai trò' },
  { id: 'perm',   label: 'Số quyền đã gán' },
  { id: 'users',  label: 'Số người dùng' },
])

const ORIGIN_OPTS = Object.freeze([
  { value: 'all',    label: 'Tất cả' },
  { value: 'preset', label: 'Mẫu có sẵn' },
  { value: 'custom', label: 'Tuỳ chỉnh' },
])

const PERM_SCOPE_OPTS = Object.freeze([
  { value: 'all',      label: 'Tất cả' },
  { value: 'has_perm', label: 'Đã có quyền' },
  { value: 'no_perm',  label: 'Chưa gán quyền' },
])

const USER_SCOPE_OPTS = Object.freeze([
  { value: 'all',       label: 'Tất cả' },
  { value: 'has_users', label: 'Đang có người dùng' },
  { value: 'no_users',  label: 'Chưa có người dùng' },
])

// ─── Computed ─────────────────────────────────────────────────────────────────

const bumpSearchDebounced = debounceTrailing(() => { filterQ.value = searchInput.value }, 300)
watch(searchInput, () => bumpSearchDebounced())

const originChipLabel = computed(
  () => ORIGIN_OPTS.find((o) => o.value === filterOrigin.value)?.label ?? 'Tất cả',
)
const permScopeChipLabel = computed(
  () => PERM_SCOPE_OPTS.find((o) => o.value === filterPermScope.value)?.label ?? 'Tất cả',
)
const userScopeChipLabel = computed(
  () => USER_SCOPE_OPTS.find((o) => o.value === filterUserScope.value)?.label ?? 'Tất cả',
)

const filteredItems = computed(() => {
  let list = items.value

  if (filterOrigin.value === 'preset') list = list.filter((r) => seedRoleNameSet.has(r.name))
  else if (filterOrigin.value === 'custom') list = list.filter((r) => !seedRoleNameSet.has(r.name))

  if (filterPermScope.value === 'has_perm') list = list.filter((r) => (r.permissions_count ?? 0) > 0)
  else if (filterPermScope.value === 'no_perm') list = list.filter((r) => (r.permissions_count ?? 0) === 0)

  if (filterUserScope.value === 'has_users') list = list.filter((r) => (r.users_count ?? 0) > 0)
  else if (filterUserScope.value === 'no_users') list = list.filter((r) => (r.users_count ?? 0) === 0)

  const q = filterQ.value.trim().toLowerCase()
  if (!q) return list
  return list.filter((r) => {
    return (r.name ?? '').toLowerCase().includes(q) ||
           (r.display_name ?? '').toLowerCase().includes(q) ||
           (r.description ?? '').toLowerCase().includes(q)
  })
})

const activeFilterCount = computed(() => {
  let n = 0
  if (searchInput.value.trim())    n++
  if (filterOrigin.value !== 'all') n++
  if (filterPermScope.value !== 'all') n++
  if (filterUserScope.value !== 'all') n++
  return n
})

// ─── Helpers ─────────────────────────────────────────────────────────────────

function isPreset(role) {
  return seedRoleNameSet.has(role.name)
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: '2-digit' })
}

function closeParentDetails(ev) {
  const d = ev.currentTarget?.closest?.('details')
  if (d) d.open = false
}

function resetFilters() {
  searchInput.value    = ''
  filterQ.value        = ''
  filterOrigin.value   = 'all'
  filterPermScope.value = 'all'
  filterUserScope.value = 'all'
  const el = funnelDetailsRef.value
  if (el) el.open = false
}

// ─── localStorage ─────────────────────────────────────────────────────────────

function loadFilterControlVisibility() {
  try {
    const raw = localStorage.getItem(ROLES_FILTER_VIS_KEY)
    if (!raw) return
    const o = JSON.parse(raw)
    const base = defaultFilterControlVisibility()
    FILTER_CONTROL_IDS.forEach((id) => { if (typeof o[id] === 'boolean') base[id] = o[id] })
    Object.assign(filterControlVisible, base)
  } catch { /* ignore */ }
}

watch(filterControlVisible, () => {
  try { localStorage.setItem(ROLES_FILTER_VIS_KEY, JSON.stringify({ ...filterControlVisible })) } catch { /* ignore */ }
}, { deep: true })

// ─── API ─────────────────────────────────────────────────────────────────────

async function load() {
  loading.value = true
  try {
    const [roles, perms] = await Promise.all([admin.listRoles(), admin.listPermissions()])
    items.value    = roles ?? []
    allPerms.value = perms ?? []
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

async function onSaved() {
  slideOver.value = null
  await load()
}

async function remove(r) {
  const ok = await confirmAction({
    title: 'Xóa vai trò?',
    message: `Xóa vai trò «${r.display_name || r.name}»${(r.users_count ?? 0) > 0 ? ` (đang được gán cho ${r.users_count} người dùng — quyền của họ sẽ bị thu hồi)` : ''}?`,
    confirmLabel: 'Xóa',
    danger: true,
  })
  if (!ok) return
  saving.value = true
  try {
    await admin.deleteRole(r.id)
    await load()
    showAppSuccess('Đã xóa vai trò.', 'Thành công')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

function setOriginFilter(ev, v)    { filterOrigin.value = v;    closeParentDetails(ev) }
function setPermScopeFilter(ev, v) { filterPermScope.value = v; closeParentDetails(ev) }
function setUserScopeFilter(ev, v) { filterUserScope.value = v; closeParentDetails(ev) }

onMounted(() => {
  loadFilterControlVisibility()
  load()
})
</script>

<template>
  <div class="mx-auto max-w-6xl space-y-5 pb-6 text-slate-900 dark:text-slate-100 sm:space-y-6 sm:pb-8">
    <Card>
      <!-- Page header -->
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Danh sách vai trò</h2>
        <Button type="button" class="w-full sm:w-auto" :disabled="saving || loading" @click="slideOver = { role: null }">
          + Thêm vai trò
        </Button>
      </div>

      <!-- Filter bar -->
      <div class="relative z-40 mb-4">
        <AppFilterBar>
          <div class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">

            <!-- Funnel menu -->
            <details ref="funnelDetailsRef" class="group relative">
              <summary
                class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
              >
                <span class="relative inline-flex">
                  <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" aria-hidden="true" />
                  <span
                    v-if="activeFilterCount > 0"
                    class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-500 px-1 text-[10px] font-bold leading-none text-white"
                  >{{ activeFilterCount }}</span>
                </span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
              </summary>

              <div class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[260px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900 dark:shadow-black/30 dark:ring-slate-950/50">
                <p class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300">
                  Đang áp dụng
                </p>
                <div class="p-3 pt-2">
                  <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                    <li v-if="searchInput.trim()" class="flex justify-between gap-2">
                      <span class="text-slate-500 dark:text-slate-400">Tìm trong trang</span>
                      <span class="max-w-[10rem] truncate text-right font-medium" :title="searchInput">{{ searchInput }}</span>
                    </li>
                    <li v-if="filterOrigin !== 'all'" class="flex justify-between gap-2">
                      <span class="text-slate-500 dark:text-slate-400">Kiểu vai trò</span>
                      <span class="font-medium">{{ originChipLabel }}</span>
                    </li>
                    <li v-if="filterPermScope !== 'all'" class="flex justify-between gap-2">
                      <span class="text-slate-500 dark:text-slate-400">Quyền đã gán</span>
                      <span class="font-medium">{{ permScopeChipLabel }}</span>
                    </li>
                    <li v-if="filterUserScope !== 'all'" class="flex justify-between gap-2">
                      <span class="text-slate-500 dark:text-slate-400">Người dùng</span>
                      <span class="font-medium">{{ userScopeChipLabel }}</span>
                    </li>
                    <li v-if="activeFilterCount === 0" class="text-slate-400 dark:text-slate-500">Chưa chọn điều kiện lọc.</li>
                  </ul>

                  <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">Hiển thị trên thanh</p>
                    <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
                      <li v-for="fd in filterControlDefs" :key="fd.id" class="flex items-start gap-2">
                        <input
                          :id="`roles-filter-vis-${fd.id}`"
                          v-model="filterControlVisible[fd.id]"
                          type="checkbox"
                          class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                        />
                        <label :for="`roles-filter-vis-${fd.id}`" class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300">{{ fd.label }}</label>
                      </li>
                    </ul>
                  </div>

                  <button
                    type="button"
                    class="mt-3 w-full rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                    @click="resetFilters"
                  >
                    Xóa tất cả bộ lọc
                  </button>
                </div>
              </div>
            </details>

            <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
              <!-- Origin filter -->
              <AppFilterDropdown
                v-if="filterControlVisible.origin"
                root-class="shrink-0"
                label="Kiểu"
                :summary-text="originChipLabel"
                summary-text-class="max-w-[9rem]"
                panel-class="min-w-[220px] py-1"
              >
                <ul class="space-y-0.5 px-1 py-1">
                  <li v-for="opt in ORIGIN_OPTS" :key="opt.value">
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="filterOrigin === opt.value ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'"
                      @click="setOriginFilter($event, opt.value)"
                    >{{ opt.label }}</button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <!-- Perm scope filter -->
              <AppFilterDropdown
                v-if="filterControlVisible.perm"
                root-class="shrink-0"
                label="Quyền"
                :summary-text="permScopeChipLabel"
                summary-text-class="max-w-[9rem]"
                panel-class="min-w-[220px] py-1"
              >
                <ul class="space-y-0.5 px-1 py-1">
                  <li v-for="opt in PERM_SCOPE_OPTS" :key="opt.value">
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="filterPermScope === opt.value ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'"
                      @click="setPermScopeFilter($event, opt.value)"
                    >{{ opt.label }}</button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <!-- User scope filter -->
              <AppFilterDropdown
                v-if="filterControlVisible.users"
                root-class="shrink-0"
                label="Users"
                :summary-text="userScopeChipLabel"
                summary-text-class="max-w-[9rem]"
                panel-class="min-w-[220px] py-1"
              >
                <ul class="space-y-0.5 px-1 py-1">
                  <li v-for="opt in USER_SCOPE_OPTS" :key="opt.value">
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="filterUserScope === opt.value ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'"
                      @click="setUserScopeFilter($event, opt.value)"
                    >{{ opt.label }}</button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <!-- Search -->
              <input
                v-if="filterControlVisible.search"
                v-model="searchInput"
                type="search"
                aria-label="Tìm theo mã, tên hoặc mô tả vai trò"
                placeholder="Tìm mã, tên, mô tả…"
                title="Tìm theo mã, tên hoặc mô tả vai trò"
                class="h-9 w-[10rem] shrink-0 rounded-md border-0 bg-white/90 px-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600 dark:placeholder:text-slate-500 sm:w-52"
              />
            </div>

            <!-- Clear filters -->
            <div class="ml-auto flex shrink-0 items-center gap-1 pl-2 sm:gap-2 sm:pl-3">
              <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
                aria-label="Xóa bộ lọc"
                @click="resetFilters"
              >
                <span class="relative inline-flex">
                  <FunnelIcon class="h-5 w-5" aria-hidden="true" />
                  <XMarkIcon class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40" />
                </span>
              </button>
            </div>
          </div>
        </AppFilterBar>
      </div>

      <!-- Loading state -->
      <div v-if="loading" class="flex items-center gap-3 py-10 text-sm text-slate-500 dark:text-slate-400">
        <span class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-teal-600 dark:border-slate-600 dark:border-t-teal-400" aria-hidden="true" />
        Đang tải…
      </div>

      <!-- Empty state (no roles at all) -->
      <template v-else-if="!items.length">
        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/50 py-14 text-center dark:border-slate-700 dark:bg-slate-900/30">
          <ShieldCheckIcon class="mb-3 h-12 w-12 text-slate-300 dark:text-slate-600" aria-hidden="true" />
          <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Chưa có vai trò nào.</p>
          <Button type="button" class="mt-4" @click="slideOver = { role: null }">+ Thêm vai trò đầu tiên</Button>
        </div>
      </template>

      <template v-else>
        <!-- No match state -->
        <div
          v-if="filteredItems.length === 0"
          class="rounded-xl border border-dashed border-amber-200/80 bg-amber-50/40 py-10 text-center text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200/90"
        >
          Không có vai trò khớp bộ lọc.
        </div>

        <!-- Table -->
        <div v-else class="overflow-hidden rounded-xl border border-slate-200/90 shadow-sm dark:border-slate-700">
          <div class="overflow-x-auto">
            <table class="w-full min-w-[44rem] border-collapse text-left text-sm">
              <thead>
                <tr class="border-b border-slate-200 bg-slate-50/95 text-slate-600 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-300">
                  <th scope="col" class="whitespace-nowrap py-2.5 pl-4 pr-3 text-xs font-semibold uppercase tracking-wide">Vai trò</th>
                  <th scope="col" class="hidden whitespace-nowrap py-2.5 pr-4 text-xs font-semibold uppercase tracking-wide md:table-cell">Mô tả</th>
                  <th scope="col" class="whitespace-nowrap py-2.5 pr-3 text-right text-xs font-semibold uppercase tracking-wide tabular-nums">Quyền</th>
                  <th scope="col" class="hidden whitespace-nowrap py-2.5 pr-3 text-right text-xs font-semibold uppercase tracking-wide tabular-nums sm:table-cell">Users</th>
                  <th scope="col" class="hidden whitespace-nowrap py-2.5 pr-3 text-right text-xs font-semibold uppercase tracking-wide lg:table-cell">Cập nhật</th>
                  <th scope="col" class="whitespace-nowrap py-2.5 pr-3 text-center text-xs font-semibold uppercase tracking-wide">Loại</th>
                  <th scope="col" class="whitespace-nowrap py-2.5 pl-2 pr-4 text-right text-xs font-semibold uppercase tracking-wide">Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(r, ri) in filteredItems"
                  :key="r.id"
                  class="border-b border-slate-100 transition-colors hover:bg-slate-50/80 dark:border-slate-800 dark:hover:bg-slate-800/40"
                  :class="ri % 2 === 1 ? 'bg-white dark:bg-transparent' : 'bg-slate-50/30 dark:bg-slate-900/40'"
                >
                  <!-- Role name -->
                  <td class="max-w-[14rem] py-3 pl-4 pr-3 align-middle">
                    <p class="font-semibold text-slate-900 dark:text-slate-50 truncate">{{ r.display_name ?? r.name ?? '—' }}</p>
                    <p class="font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ r.name }}</p>
                  </td>

                  <!-- Description -->
                  <td class="hidden max-w-[16rem] py-3 pr-4 align-middle md:table-cell">
                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ r.description || '—' }}</p>
                  </td>

                  <!-- Permissions count -->
                  <td class="py-3 pr-3 text-right align-middle tabular-nums">
                    <span
                      class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                      :class="(r.permissions_count ?? 0) > 0 ? 'bg-teal-50 text-teal-800 dark:bg-teal-950/40 dark:text-teal-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'"
                    >
                      {{ r.permissions_count ?? 0 }}
                    </span>
                  </td>

                  <!-- Users count -->
                  <td class="hidden py-3 pr-3 text-right align-middle tabular-nums sm:table-cell">
                    <span class="inline-flex items-center gap-1 text-xs text-slate-600 dark:text-slate-400">
                      <UserGroupIcon class="h-3.5 w-3.5 text-slate-400" aria-hidden="true" />
                      {{ r.users_count ?? 0 }}
                    </span>
                  </td>

                  <!-- Updated at -->
                  <td class="hidden py-3 pr-3 text-right align-middle text-xs text-slate-500 dark:text-slate-400 lg:table-cell">
                    {{ formatDate(r.updated_at) }}
                  </td>

                  <!-- Type badge -->
                  <td class="py-3 pr-3 text-center align-middle">
                    <span
                      class="rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                      :class="isPreset(r) ? 'bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'"
                    >
                      {{ isPreset(r) ? 'Mẫu' : 'Tuỳ chỉnh' }}
                    </span>
                  </td>

                  <!-- Actions -->
                  <td class="relative py-3 pl-2 pr-4 text-right align-middle">
                    <AppRowActionsMenu
                      align="end"
                      aria-label="Thao tác"
                      trigger-sr-only="Thao tác"
                      root-class="text-right"
                      :disabled="saving"
                    >
                      <button
                        type="button"
                        role="menuitem"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-slate-700 transition hover:bg-slate-50 disabled:opacity-40 dark:text-slate-200 dark:hover:bg-slate-800"
                        :disabled="saving"
                        @click="slideOver = { role: r }"
                      >
                        <PencilSquareIcon class="h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" aria-hidden="true" />
                        Sửa
                      </button>
                      <button
                        type="button"
                        role="menuitem"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-red-700 transition hover:bg-red-50 disabled:opacity-40 dark:text-red-400 dark:hover:bg-red-950/40"
                        :disabled="r.name === 'superadmin' || saving"
                        @click="remove(r)"
                      >
                        <TrashIcon class="h-4 w-4 shrink-0 text-red-600 dark:text-red-400" aria-hidden="true" />
                        Xóa
                      </button>
                    </AppRowActionsMenu>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </Card>

    <!-- Role slide-over (create or edit) -->
    <RoleSlideOver
      v-if="slideOver !== null"
      :role="slideOver.role"
      :all-perms="allPerms"
      :saving="saving"
      @close="slideOver = null"
      @saved="onSaved"
    />
  </div>
</template>
