<script setup>
import { computed, onActivated, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  FunnelIcon,
  XMarkIcon,
  PlusIcon,
  ShieldCheckIcon,
  SparklesIcon,
} from '@heroicons/vue/24/outline'
import { JOB_ROLE_TEMPLATES } from '../../config/jobRoleTemplates.js'
import { getPermissionFriendlyTitle } from '../../config/businessCapabilities.js'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import { debounceTrailing } from '../../composables/useDebounce'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import SystemRolesSummaryBar from '../../components/system/SystemRolesSummaryBar.vue'
import SystemRoleRecordCard from '../../components/system/SystemRoleRecordCard.vue'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../../components/shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'

const router = useRouter()
const { t } = useI18n()

const ROLE_FILTER_CONTROLS = [
  { key: 'users', label: 'Nhân viên được gán', default: false },
  { key: 'template', label: 'Loại vai trò', default: false },
]

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
  filterControlDefs,
} = useVisibleFilterControls(ROLE_FILTER_CONTROLS, 'va-dieuvan.system.roles.filters.v1')

const datagridRef = ref(null)
useDetailsAutoCloseWithin(datagridRef)

function toggleFilterPanel() {
  openFilterPanel()
}

const FILTER_CONTROL_CLASS =
  'input h-10 w-full text-sm rounded-lg border border-slate-200 bg-white px-3 text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'

const filterUsers = ref('all')
const filterTemplate = ref('all')

function clearRoleSearch() {
  query.value = ''
  debouncedQuery.value = ''
  filterUsers.value = 'all'
  filterTemplate.value = 'all'
}

function onKpiQuickFilter(payload) {
  if (payload?.users) filterUsers.value = payload.users
}

// ─── State ───────────────────────────────────────────────────────────────────

const loading = ref(true)
const saving  = ref(false)
const roles   = ref([])
const query   = ref('')
const debouncedQuery = ref('')
const bumpQ = debounceTrailing(() => { debouncedQuery.value = query.value }, 250)

// Expand permission list per role card
const expandedPermRoleId = ref(null)
const permLoading        = ref(false)
const permsByRoleId      = ref({})

async function togglePermissions(role) {
  if (expandedPermRoleId.value === role.id) {
    expandedPermRoleId.value = null
    return
  }
  expandedPermRoleId.value = role.id
  if (permsByRoleId.value[role.id]) return  // đã cache
  permLoading.value = true
  try {
    const detail = await admin.getRole(role.id)
    permsByRoleId.value[role.id] = detail.permissions ?? []
  } catch {
    permsByRoleId.value[role.id] = []
  } finally {
    permLoading.value = false
  }
}

function goToUserRoles(role) {
  router.push({ name: 'systemUserRoles', query: { roles: role.name } })
}

// ─── Derived ─────────────────────────────────────────────────────────────────

/** Map template id → template object for quick lookup */
const templateMap = new Map(JOB_ROLE_TEMPLATES.map((t) => [t.id, t]))

/** Try to find matching template for a role by name */
function matchTemplate(role) {
  return JOB_ROLE_TEMPLATES.find((t) => t.id === role.name || role.name?.includes(t.id)) ?? null
}

function getRoleIcon(role) {
  const tpl = matchTemplate(role)
  return tpl?.icon ?? '🎭'
}

function getRoleColorKey(role) {
  const tpl = matchTemplate(role)
  return tpl?.colorKey ?? 'slate'
}

const ROLE_COLOR_BAR = {
  blue: 'bg-blue-400',
  teal: 'bg-teal-400',
  amber: 'bg-amber-400',
  green: 'bg-green-400',
  indigo: 'bg-indigo-400',
  purple: 'bg-purple-400',
  violet: 'bg-violet-400',
  rose: 'bg-rose-400',
  cyan: 'bg-cyan-400',
  slate: 'bg-slate-300',
}

function roleColorBarClass(role) {
  return ROLE_COLOR_BAR[getRoleColorKey(role)] ?? ROLE_COLOR_BAR.slate
}

const filteredRoles = computed(() => {
  let list = roles.value
  if (filterUsers.value === 'with_users') {
    list = list.filter((r) => (r.users_count ?? 0) > 0)
  } else if (filterUsers.value === 'empty') {
    list = list.filter((r) => (r.users_count ?? 0) === 0)
  }
  if (filterTemplate.value === 'template') {
    list = list.filter((r) => matchTemplate(r))
  } else if (filterTemplate.value === 'custom') {
    list = list.filter((r) => !matchTemplate(r))
  }
  const q = debouncedQuery.value.trim().toLowerCase()
  if (!q) return list
  return list.filter((r) =>
    (r.display_name ?? '').toLowerCase().includes(q) ||
    (r.description ?? '').toLowerCase().includes(q) ||
    (r.name ?? '').toLowerCase().includes(q),
  )
})

const totalUsers = computed(() => roles.value.reduce((s, r) => s + (r.users_count ?? 0), 0))

// ─── Load ─────────────────────────────────────────────────────────────────────

async function load() {
  loading.value = true
  try {
    roles.value = (await admin.listRoles()) ?? []
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

// ─── Actions ─────────────────────────────────────────────────────────────────

function openNew() {
  router.push({ name: 'systemRoleNew' })
}

function openEdit(role) {
  router.push({ name: 'systemRoleEdit', params: { id: role.id } })
}

function openDetail(role) {
  router.push({ name: 'systemRoleDetail', params: { id: role.id } })
}

async function cloneRole(role) {
  const ok = await confirmAction({
    title: 'Nhân bản vai trò?',
    message: `Tạo bản sao của «${role.display_name || role.name}»? Bạn sẽ được chuyển đến trang tạo vai trò mới với các quyền đã sao chép.`,
    confirmLabel: 'Nhân bản',
  })
  if (!ok) return
  router.push({ name: 'systemRoleNew', query: { cloneFrom: role.id } })
}

async function remove(role) {
  if (role.name === 'superadmin') {
    showAppError('Không thể xóa vai trò Quản trị hệ thống.')
    return
  }
  const usersMsg = (role.users_count ?? 0) > 0
    ? ` Có ${role.users_count} nhân viên đang sử dụng vai trò này — quyền của họ sẽ bị thu hồi.`
    : ''
  const ok = await confirmAction({
    title: 'Xóa vai trò?',
    message: `Xóa vai trò «${role.display_name || role.name}»?${usersMsg}`,
    confirmLabel: 'Xóa',
    danger: true,
  })
  if (!ok) return
  saving.value = true
  try {
    await admin.deleteRole(role.id)
    await load()
    showAppSuccess('Đã xóa vai trò.', 'Thành công')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

onMounted(() => load())

onActivated(() => load())
</script>

<template>
  <div class="space-y-6 pb-10">

    <!-- ── Page header ─────────────────────────────────────────────────────── -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-slate-50">Vai trò & Phân quyền</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          Xác định nhóm quyền cho từng loại công việc trong tổ chức.
        </p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600 disabled:opacity-50"
        :disabled="loading || saving"
        @click="openNew"
      >
        <PlusIcon class="h-4 w-4" aria-hidden="true" />
        Tạo vai trò mới
      </button>
    </div>

    <SystemRolesSummaryBar
      :roles="roles"
      :template-count="JOB_ROLE_TEMPLATES.length - 1"
      :loading="loading"
      :active-users-filter="filterUsers"
      @quick-filter="onKpiQuickFilter"
    />

    <div
      ref="datagridRef"
      class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
    >
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="mb-2">
          <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">
            {{ t('system_pages.roles.list_title') }}
            <span class="ml-1 text-xs font-normal text-slate-400">({{ filteredRoles.length }})</span>
          </h2>
        </div>
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
          <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
            <DatagridToolbarSearch
              v-model="query"
              input-id="system-roles-search"
              :placeholder="t('system_pages.roles.search_ph')"
              :aria-label="t('system_pages.roles.search_aria')"
              stretch
              inline-actions
              hide-label
              input-height="h-10"
              @update:model-value="bumpQ"
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
                  test-id="system-roles-toolbar-filter"
                  @click="toggleFilterPanel"
                >
                  {{ t('system_pages.toolbar_filter') }}
                </DatagridToolbarActionButton>
              </template>
              <li v-for="fd in filterControlDefs" :key="'role-vis-' + fd.key" class="flex items-start gap-2">
                <input :id="'role-filter-vis-' + fd.key" v-model="visibleFilters[fd.key]" type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800" />
                <label :for="'role-filter-vis-' + fd.key" class="cursor-pointer text-sm text-slate-700 dark:text-slate-300">{{ fd.label }}</label>
              </li>
            </FilterVisibilityDropdown>
            <button type="button" class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800" :title="t('system_pages.clear_filters')" data-testid="system-roles-reset-filters" @click="clearRoleSearch">
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              <XMarkIcon class="h-3 w-3 text-rose-500" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>

      <div v-if="hasFilterRow" class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700">
        <DatagridFilterField v-if="visibleFilters.users">
          <select v-model="filterUsers" :class="FILTER_CONTROL_CLASS" :aria-label="t('system_pages.roles.filter_users')" data-testid="system-roles-filter-users">
            <option value="all">{{ t('system_pages.roles.filter_users') }}</option>
            <option value="with_users">Có người dùng</option>
            <option value="empty">Chưa gán ai</option>
          </select>
        </DatagridFilterField>
        <DatagridFilterField v-if="visibleFilters.template">
          <select v-model="filterTemplate" :class="FILTER_CONTROL_CLASS" :aria-label="t('system_pages.roles.filter_template')" data-testid="system-roles-filter-template">
            <option value="all">{{ t('system_pages.roles.filter_template') }}</option>
            <option value="template">Theo mẫu công việc</option>
            <option value="custom">Tùy chỉnh</option>
          </select>
        </DatagridFilterField>
      </div>

      <div v-if="loading" class="space-y-2 p-4 sm:p-5">
        <div v-for="i in 6" :key="i" class="h-24 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800" />
      </div>

      <template v-else-if="!roles.length">
        <div class="flex flex-col items-center py-16 text-center">
          <ShieldCheckIcon class="mb-4 h-14 w-14 text-slate-300 dark:text-slate-600" />
          <p class="text-base font-semibold text-slate-700 dark:text-slate-300">Chưa có vai trò nào</p>
          <button type="button" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700" data-testid="system-roles-create-first" @click="openNew">
            <PlusIcon class="h-4 w-4" />
            Tạo vai trò đầu tiên
          </button>
        </div>
      </template>

      <template v-else>
        <div v-if="filteredRoles.length === 0" class="border-t border-slate-100 py-10 text-center text-sm text-amber-800 dark:border-slate-700 dark:text-amber-200">
          Không có vai trò nào khớp bộ lọc.
        </div>
        <div v-else class="space-y-2 border-t border-slate-100 p-3 sm:p-4 dark:border-slate-700">
          <SystemRoleRecordCard
            v-for="role in filteredRoles"
            :key="role.id"
            :role="role"
            :icon="getRoleIcon(role)"
            :color-bar-class="roleColorBarClass(role)"
            :expanded="expandedPermRoleId === role.id"
            :perm-loading="permLoading"
            :permissions="permsByRoleId[role.id] ?? []"
            :perm-title-fn="getPermissionFriendlyTitle"
            :saving="saving"
            @detail="openDetail(role)"
            @assign-users="goToUserRoles(role)"
            @edit="openEdit(role)"
            @clone="cloneRole(role)"
            @remove="remove(role)"
            @toggle-perms="togglePermissions(role)"
          />
          <button
            type="button"
            class="flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-50/90 py-8 text-slate-500 transition hover:bg-teal-50/80 hover:text-teal-700 dark:bg-slate-800/35 dark:hover:bg-teal-950/30 dark:hover:text-teal-400"
            data-testid="system-roles-create-inline"
            @click="openNew"
          >
            <SparklesIcon class="h-5 w-5" aria-hidden="true" />
            Tạo vai trò mới
          </button>
        </div>
      </template>
    </div>

  </div>
</template>
