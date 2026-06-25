<script setup>
import { computed, onActivated, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  UserGroupIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { useUserRoleManager } from '../../composables/useUserRoleManager'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import SystemUserRolesSummaryBar from '../../components/system/SystemUserRolesSummaryBar.vue'
import SystemUserRoleRecordCard from '../../components/system/SystemUserRoleRecordCard.vue'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../../components/shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'
import Button from '../../components/ui/Button.vue'

const NEEDS_SUPPLEMENT = 'Cần bổ sung'
const COL_STORAGE_KEY = 'user-roles-table-cols-v2'

const { t } = useI18n()

const COLUMN_DEFS = [
  { id: 'employee_code', label: 'Mã nhân viên', defaultOn: true },
  { id: 'department', label: 'Phòng ban', defaultOn: true },
  { id: 'position', label: 'Chức vụ', defaultOn: true },
  { id: 'email', label: 'Email', defaultOn: true },
  { id: 'role', label: 'Vai trò chính', defaultOn: true },
  { id: 'status', label: 'Trạng thái', defaultOn: true },
]

function defaultColumnVisible() {
  return Object.fromEntries(COLUMN_DEFS.map((c) => [c.id, c.defaultOn]))
}

function loadColumnVisible() {
  try {
    const raw = localStorage.getItem(COL_STORAGE_KEY)
    if (!raw) return defaultColumnVisible()
    const parsed = JSON.parse(raw)
    const base = defaultColumnVisible()
    for (const c of COLUMN_DEFS) {
      if (typeof parsed[c.id] === 'boolean') base[c.id] = parsed[c.id]
    }
    return base
  } catch {
    return defaultColumnVisible()
  }
}

const columnVisible = ref(loadColumnVisible())

watch(columnVisible, () => {
  try {
    localStorage.setItem(COL_STORAGE_KEY, JSON.stringify(columnVisible.value))
  } catch {
    /* ignore */
  }
}, { deep: true })

const USER_ROLES_FILTER_CONTROLS = [
  { key: 'assignment', label: 'Tình trạng gán', default: false },
  { key: 'per_page', label: 'Số dòng/trang', default: false },
]

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
  filterControlDefs,
} = useVisibleFilterControls(USER_ROLES_FILTER_CONTROLS, 'va-dieuvan.system.user-roles.filters.v1')

const showColPanelDd = ref(false)
const datagridRef = ref(null)
useDetailsAutoCloseWithin(datagridRef)

function toggleColPanel() {
  closeFilterPanel()
  showColPanelDd.value = !showColPanelDd.value
}

function closeColPanel() {
  showColPanelDd.value = false
}

function toggleFilterPanel() {
  showColPanelDd.value = false
  openFilterPanel()
}

const FILTER_CONTROL_CLASS =
  'input h-10 w-full text-sm rounded-lg border border-slate-200 bg-white px-3 text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'

function displayOrSupplement(val) {
  const s = val != null ? String(val).trim() : ''
  return s === '' ? NEEDS_SUPPLEMENT : s
}

function isSupplementValue(val) {
  const s = val != null ? String(val).trim() : ''
  return s === ''
}

const {
  loading, savingIds, savedIds, bulkApplying,
  users, roles, meta, rowRoleId,
  selectedIds, bulkRoleId, bulkAction,
  filters, isLocked,
  allSelected, someSelected, displayFrom, displayTo,
  bootstrap, applyFilters, goPage, onRoleChange,
  toggleSelect, toggleSelectAll, clearSelection, applyBulk,
  bumpSearch,
} = useUserRoleManager()

watch(() => filters.q, () => bumpSearch())

const pageAssigned = computed(() =>
  users.value.filter((u) => {
    const rid = rowRoleId[u.id]
    return rid != null || (u.roles?.length ?? 0) > 0
  }).length,
)

const pageUnassigned = computed(() => users.value.length - pageAssigned.value)

function onKpiQuickFilter(payload) {
  if (payload?.assignment) {
    filters.assignment = payload.assignment
    applyFilters()
  }
}

function resetFilters() {
  filters.q = ''
  filters.assignment = 'all'
  filters.per_page = '25'
  filters.roles = []
  applyFilters()
}

function userCurrentRoleName(user) {
  const rid = rowRoleId[user.id]
  if (!rid) return null
  return roles.value.find((r) => r.id === rid)?.display_name
    || roles.value.find((r) => r.id === rid)?.name
    || null
}

function onRoleSelect(user, val) {
  rowRoleId[user.id] = val
  onRoleChange(user)
}

onMounted(() => bootstrap())
onActivated(() => bootstrap())
</script>

<template>
  <div class="space-y-5 pb-8">

    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold text-slate-900 dark:text-slate-50">Phân vai trò nhân viên</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
          Mỗi nhân viên được gán <strong class="font-semibold text-slate-700 dark:text-slate-300">1 vai trò chính</strong>.
          Thay đổi lưu tự động sau 0,5 giây.
        </p>
      </div>
    </div>

    <SystemUserRolesSummaryBar
      :meta="meta"
      :roles-count="roles.length"
      :page-assigned="pageAssigned"
      :page-unassigned="pageUnassigned"
      :loading="loading"
      :active-assignment="filters.assignment"
      @quick-filter="onKpiQuickFilter"
    />

    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="-translate-y-2 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="-translate-y-2 opacity-0"
    >
      <div
        v-if="selectedIds.length > 0"
        class="flex flex-wrap items-center gap-3 rounded-2xl bg-teal-50/90 px-4 py-3 dark:bg-teal-950/30"
      >
        <span class="text-sm font-semibold text-teal-800 dark:text-teal-300">
          Đã chọn {{ selectedIds.length }} nhân viên
        </span>
        <div class="flex flex-wrap items-center gap-2">
          <select
            v-model="bulkAction"
            aria-label="Thao tác bulk"
            class="input h-10 rounded-lg border border-teal-200 bg-white px-2.5 text-sm dark:border-teal-800/50 dark:bg-slate-900"
          >
            <option value="assign">Đặt vai trò</option>
            <option value="remove">Gỡ vai trò</option>
          </select>
          <select
            v-model="bulkRoleId"
            aria-label="Vai trò bulk"
            class="input h-10 rounded-lg border border-teal-200 bg-white px-2.5 text-sm dark:border-teal-800/50 dark:bg-slate-900"
          >
            <option value="">— Chọn vai trò —</option>
            <option v-for="r in roles" :key="r.id" :value="String(r.id)">
              {{ r.display_name || r.name }}
            </option>
          </select>
          <Button
            class="h-10 px-4 text-sm"
            :loading="bulkApplying"
            :disabled="bulkApplying || (bulkAction === 'assign' && !bulkRoleId)"
            data-testid="user-roles-bulk-apply"
            @click="applyBulk"
          >
            Áp dụng
          </Button>
        </div>
        <button type="button" class="ml-auto rounded-lg p-1 text-teal-500 hover:bg-teal-100 dark:hover:bg-teal-900/40" @click="clearSelection">
          <XMarkIcon class="h-4 w-4" aria-hidden="true" />
        </button>
      </div>
    </Transition>

    <div
      v-if="meta.truncated"
      class="rounded-2xl bg-amber-50/90 px-3 py-2.5 text-sm text-amber-800 dark:bg-amber-950/40 dark:text-amber-200"
    >
      Kết quả bị giới hạn ở {{ meta.cap }} nhân viên. Dùng bộ lọc để thu hẹp tìm kiếm.
    </div>

    <div
      ref="datagridRef"
      class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
    >
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="mb-2 flex flex-wrap items-center gap-2">
          <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">
            {{ t('system_pages.user_roles.list_title') }}
            <span class="ml-1 text-xs font-normal text-slate-400">({{ meta.total }})</span>
          </h2>
          <label class="ml-auto flex items-center gap-2 text-xs text-slate-500">
            <input
              type="checkbox"
              :checked="allSelected"
              :indeterminate="someSelected"
              class="h-4 w-4 rounded border-slate-300 text-teal-600"
              data-testid="user-roles-select-all"
              @change="toggleSelectAll"
            />
            Chọn trang
          </label>
        </div>
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
          <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
            <DatagridToolbarSearch
              v-model="filters.q"
              input-id="user-roles-search"
              :placeholder="t('system_pages.user_roles.search_ph')"
              :aria-label="t('system_pages.user_roles.search_aria')"
              stretch
              inline-actions
              hide-label
              input-height="h-10"
            />
          </div>
          <div class="flex shrink-0 items-center gap-2">
            <FilterVisibilityDropdown
              :open="showFilterPanelDd"
              :title="t('system_pages.filter_show_controls_title')"
              :hint="t('system_pages.filter_show_controls_hint')"
              @close="closeFilterPanel"
            >
              <template #trigger>
                <DatagridToolbarActionButton
                  icon="filter"
                  :active="showFilterPanelDd"
                  test-id="user-roles-toolbar-filter"
                  @click="toggleFilterPanel"
                >
                  {{ t('system_pages.toolbar_filter') }}
                </DatagridToolbarActionButton>
              </template>
              <li v-for="fd in filterControlDefs" :key="'ur-vis-' + fd.key" class="flex items-start gap-2">
                <input
                  :id="'ur-filter-vis-' + fd.key"
                  v-model="visibleFilters[fd.key]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                />
                <label :for="'ur-filter-vis-' + fd.key" class="cursor-pointer text-sm text-slate-700 dark:text-slate-300">
                  {{ fd.key === 'assignment' ? t('system_pages.user_roles.filter_assignment') : t('system_pages.user_roles.filter_per_page') }}
                </label>
              </li>
            </FilterVisibilityDropdown>

            <FilterVisibilityDropdown
              :open="showColPanelDd"
              :title="t('system_pages.toolbar_columns')"
              @close="closeColPanel"
            >
              <template #trigger>
                <DatagridToolbarActionButton
                  icon="columns"
                  :active="showColPanelDd"
                  test-id="user-roles-toolbar-columns"
                  @click="toggleColPanel"
                >
                  {{ t('system_pages.toolbar_columns') }}
                </DatagridToolbarActionButton>
              </template>
              <li v-for="col in COLUMN_DEFS" :key="'ur-col-' + col.id" class="flex items-start gap-2">
                <input
                  :id="'urcol-' + col.id"
                  v-model="columnVisible[col.id]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800"
                />
                <label :for="'urcol-' + col.id" class="cursor-pointer text-sm text-slate-700 dark:text-slate-300">{{ col.label }}</label>
              </li>
            </FilterVisibilityDropdown>
          </div>
        </div>
      </div>

      <div
        v-if="hasFilterRow"
        class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
      >
        <DatagridFilterField v-if="visibleFilters.assignment">
          <select
            v-model="filters.assignment"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('system_pages.user_roles.filter_assignment')"
            data-testid="user-roles-filter-assignment"
            :disabled="isLocked"
            @change="applyFilters"
          >
            <option value="all">{{ t('system_pages.user_roles.filter_assignment') }}</option>
            <option value="assigned">Đã gán vai trò</option>
            <option value="unassigned">Chưa gán vai trò</option>
          </select>
        </DatagridFilterField>
        <DatagridFilterField v-if="visibleFilters.per_page">
          <select
            v-model="filters.per_page"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('system_pages.user_roles.filter_per_page')"
            data-testid="user-roles-filter-per-page"
            :disabled="isLocked"
            @change="applyFilters"
          >
            <option value="10">10</option>
            <option value="25">{{ t('system_pages.user_roles.filter_per_page') }}</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="all">Tất cả</option>
          </select>
        </DatagridFilterField>
      </div>

      <div v-if="loading" class="space-y-2 p-4 sm:p-5">
        <div v-for="i in 6" :key="i" class="h-20 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800" />
      </div>

      <template v-else-if="!users.length">
        <div class="flex flex-col items-center py-14 text-center">
          <UserGroupIcon class="h-12 w-12 text-slate-300 dark:text-slate-600" />
          <p class="mt-3 text-sm font-medium text-slate-600 dark:text-slate-400">Không có nhân viên nào khớp.</p>
          <button type="button" class="mt-2 text-sm text-teal-600 underline dark:text-teal-400" @click="resetFilters">Xóa bộ lọc</button>
        </div>
      </template>

      <div v-else class="space-y-2 border-t border-slate-100 p-3 sm:space-y-2.5 sm:p-4 dark:border-slate-700">
        <SystemUserRoleRecordCard
          v-for="user in users"
          :key="user.id"
          :user="user"
          :roles="roles"
          :role-id="rowRoleId[user.id]"
          :selected="selectedIds.includes(user.id)"
          :saving="savingIds.has(user.id)"
          :saved="savedIds.has(user.id)"
          :locked="isLocked"
          :col-visible="columnVisible"
          :display-or-supplement="displayOrSupplement"
          :is-supplement-value="isSupplementValue"
          :current-role-name="userCurrentRoleName(user)"
          @toggle-select="toggleSelect(user.id)"
          @role-change="(val) => onRoleSelect(user, val)"
        />
      </div>

      <div
        v-if="!loading && users.length"
        class="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 sm:px-5"
      >
        <p class="text-xs text-slate-500 dark:text-slate-400">
          <template v-if="meta.per_page_mode === 'paged'">
            Hiển thị {{ displayFrom }}–{{ displayTo }} trong {{ meta.total }} nhân viên
          </template>
          <template v-else>
            Đang hiển thị {{ users.length }} / {{ meta.total }} nhân viên
          </template>
        </p>
        <div v-if="meta.per_page_mode === 'paged' && meta.last_page > 1" class="flex items-center gap-2">
          <Button variant="secondary" class="h-8 px-3 text-sm" :disabled="meta.current_page <= 1 || isLocked" data-testid="user-roles-prev" @click="goPage(meta.current_page - 1)">
            ← Trước
          </Button>
          <span class="text-sm text-slate-600 dark:text-slate-400">{{ meta.current_page }} / {{ meta.last_page }}</span>
          <Button variant="secondary" class="h-8 px-3 text-sm" :disabled="meta.current_page >= meta.last_page || isLocked" data-testid="user-roles-next" @click="goPage(meta.current_page + 1)">
            Sau →
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
