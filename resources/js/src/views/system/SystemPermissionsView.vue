<script setup>
import { computed, onActivated, onMounted, reactive, ref, watch } from 'vue'
import { PlusIcon, XMarkIcon, FunnelIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import { PERMISSION_MODULES, groupPermissions, getModuleId } from '../../config/permissionModules.js'
import { SEED_PERMISSION_PRESETS } from '../../config/systemSeedOptions'
import permissionPlainVi from '../../data/permission_plain_vi.json'
import { getPermissionFriendlyTitle } from '../../config/businessCapabilities.js'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import { debounceTrailing } from '../../composables/useDebounce'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import SystemPermissionsSummaryBar from '../../components/system/SystemPermissionsSummaryBar.vue'
import SystemPermissionRecordCard from '../../components/system/SystemPermissionRecordCard.vue'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../../components/shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'
import { ChevronDownIcon, LockClosedIcon } from '@heroicons/vue/24/outline'

const { t } = useI18n()

const PERM_FILTER_CONTROLS = [
  { key: 'module', label: 'Module', default: false },
  { key: 'role', label: 'Vai trò', default: false },
  { key: 'unassigned', label: 'Chưa gán', default: false },
  { key: 'assigned', label: 'Đã gán', default: false },
  { key: 'system', label: 'system.*', default: false },
]

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
  filterControlDefs,
} = useVisibleFilterControls(PERM_FILTER_CONTROLS, 'va-dieuvan.system.permissions.filters.v1')

const datagridRef = ref(null)
useDetailsAutoCloseWithin(datagridRef)

function toggleFilterPanel() {
  openFilterPanel()
}

const FILTER_CONTROL_CLASS =
  'input h-10 w-full text-sm rounded-lg border border-slate-200 bg-white px-3 text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'

const ROLE_COLORS = [
  'bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300',
  'bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300',
  'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
  'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
  'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300',
  'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300',
  'bg-teal-100 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300',
]

// ─── State ───────────────────────────────────────────────────────────────────

const loading  = ref(true)
const saving   = ref(false)
const items    = ref([])   // permissions với role_ids + role_names
const roles    = ref([])   // danh sách roles (cho filter dropdown)

// Filter
const searchRaw    = ref('')
const searchQ      = ref('')
const filterRoleId = ref('')
const filterModule = ref('all')
const filterUnassigned = ref(false)
const filterSystemOnly = ref(false)
const filterAssignedOnly = ref(false)

const permActiveFilter = computed(() => {
  if (filterUnassigned.value) return 'unassigned'
  if (filterAssignedOnly.value) return 'assigned'
  if (filterSystemOnly.value) return 'system'
  return 'all'
})

function onKpiQuickFilter(payload) {
  if (!payload?.kind) return
  filterUnassigned.value = payload.kind === 'unassigned'
  filterAssignedOnly.value = payload.kind === 'assigned'
  filterSystemOnly.value = payload.kind === 'system'
  if (payload.kind === 'all') {
    filterUnassigned.value = false
    filterAssignedOnly.value = false
    filterSystemOnly.value = false
  }
}

// Module accordions — mở mặc định tất cả
const openModules = ref(new Set())

// Create modal
const createOpen   = ref(false)
const presetVal    = ref('')
const createForm   = reactive({ name: '', display_name: '', plain_description: '' })

// Edit panel
const editingPerm  = ref(null)
const editForm     = reactive({ name: '', display_name: '', plain_description: '' })

// ─── Computed ─────────────────────────────────────────────────────────────────

const roleColorMap = computed(() => {
  const m = new Map()
  roles.value.forEach((r, i) => m.set(r.id, ROLE_COLORS[i % ROLE_COLORS.length]))
  return m
})

const MODULE_OPTS = computed(() => [
  { value: 'all', label: 'Module' },
  ...PERMISSION_MODULES.map((m) => ({ value: m.id, label: m.label })),
])

const bumpSearch = debounceTrailing(() => { searchQ.value = searchRaw.value }, 300)
watch(searchRaw, () => bumpSearch())

const filteredItems = computed(() => {
  let list = items.value

  if (filterUnassigned.value) {
    list = list.filter((p) => !p.role_ids?.length)
  }
  if (filterAssignedOnly.value) {
    list = list.filter((p) => (p.role_ids?.length ?? 0) > 0)
  }
  if (filterSystemOnly.value) {
    list = list.filter((p) => isSystemPerm(p.name))
  }

  if (filterRoleId.value) {
    const id = Number(filterRoleId.value)
    list = list.filter((p) => p.role_ids?.includes(id))
  }

  if (filterModule.value !== 'all') {
    list = list.filter((p) => getModuleId(p.name) === filterModule.value)
  }

  const q = searchQ.value.trim().toLowerCase()
  if (q) {
    list = list.filter((p) =>
      (p.name ?? '').toLowerCase().includes(q) ||
      (p.display_name ?? '').toLowerCase().includes(q) ||
      (p.plain_summary ?? '').toLowerCase().includes(q),
    )
  }

  return list
})

const grouped = computed(() => groupPermissions(filteredItems.value))

const activeFilters = computed(() => {
  let n = 0
  if (searchRaw.value.trim()) n++
  if (filterRoleId.value) n++
  if (filterModule.value !== 'all') n++
  if (filterUnassigned.value) n++
  if (filterAssignedOnly.value) n++
  if (filterSystemOnly.value) n++
  return n
})

function isSystemPerm(name) {
  return name?.startsWith('system.')
}

function rolesOfPerm(perm) {
  return perm.role_names ?? []
}

function permDisplayName(perm) {
  return getPermissionFriendlyTitle(perm)
}

// ─── Accordions ──────────────────────────────────────────────────────────────

function toggleModule(id) {
  if (openModules.value.has(id)) openModules.value.delete(id)
  else openModules.value.add(id)
}

// Mở accordion của các module có kết quả filter
watch(filteredItems, () => {
  if (activeFilters.value > 0) {
    grouped.value.forEach((g) => openModules.value.add(g.module.id))
  }
})

// ─── API load ─────────────────────────────────────────────────────────────────

async function load() {
  loading.value = true
  try {
    const [perms, roleList] = await Promise.all([
      admin.listPermissionsWithRoles(),
      admin.listRoles(),
    ])
    items.value = perms ?? []
    roles.value = roleList ?? []
    // Mở tất cả module mặc định khi load lần đầu
    if (openModules.value.size === 0) {
      PERMISSION_MODULES.forEach((m) => openModules.value.add(m.id))
    }
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

// ─── Create ───────────────────────────────────────────────────────────────────

watch(presetVal, (v) => {
  if (!v) return
  const p = SEED_PERMISSION_PRESETS.find((x) => x.name === v)
  if (p) {
    createForm.name            = p.name
    createForm.display_name    = p.display_name ?? ''
    createForm.plain_description = permissionPlainVi[p.name] ?? ''
  }
})

function openCreate() {
  presetVal.value              = ''
  createForm.name              = ''
  createForm.display_name      = ''
  createForm.plain_description = ''
  createOpen.value             = true
}

async function submitCreate() {
  if (!createForm.name.trim()) return
  saving.value = true
  try {
    await admin.createPermission({
      name:              createForm.name.trim(),
      display_name:      createForm.display_name.trim() || null,
      plain_description: createForm.plain_description.trim() || null,
    })
    createOpen.value = false
    await load()
    showAppSuccess('Đã thêm quyền mới.')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

// ─── Edit ─────────────────────────────────────────────────────────────────────

function openEdit(perm) {
  editingPerm.value          = perm
  editForm.name              = perm.name
  editForm.display_name      = perm.display_name ?? ''
  editForm.plain_description = perm.plain_description ?? ''
}

function closeEdit() {
  if (saving.value) return
  editingPerm.value = null
}

async function submitEdit() {
  if (!editingPerm.value) return
  saving.value = true
  try {
    await admin.updatePermission(editingPerm.value.id, {
      name:              editForm.name.trim(),
      display_name:      editForm.display_name.trim() || null,
      plain_description: editForm.plain_description.trim() || null,
    })
    editingPerm.value = null
    await load()
    showAppSuccess('Đã lưu thay đổi.')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

// ─── Delete ───────────────────────────────────────────────────────────────────

async function removePerm(perm) {
  const roleCount = (perm.role_ids ?? []).length
  const roleNames = (perm.role_names ?? []).map((r) => r.display_name || r.name).join(', ')
  const detail    = roleCount > 0
    ? `\n\nCó ${roleCount} vai trò đang dùng quyền này: ${roleNames}.\nXóa sẽ thu hồi quyền khỏi các vai trò đó.`
    : ''

  const ok = await confirmAction({
    title:        'Xóa quyền?',
    message:      `Xóa quyền «${perm.name}»?${detail}`,
    confirmLabel: 'Xóa',
    danger:       true,
  })
  if (!ok) return
  try {
    await admin.deletePermission(perm.id)
    await load()
    showAppSuccess('Đã xóa quyền.')
  } catch (e) {
    showAppError(formatApiError(e))
  }
}

// ─── Reset filter ─────────────────────────────────────────────────────────────

function resetFilters() {
  searchRaw.value      = ''
  searchQ.value        = ''
  filterRoleId.value   = ''
  filterModule.value   = 'all'
  filterUnassigned.value = false
  filterAssignedOnly.value = false
  filterSystemOnly.value = false
}

onMounted(() => load())

onActivated(() => load())
</script>

<template>
  <div class="space-y-5 pb-8">

    <!-- ── Header ─────────────────────────────────────────────────────────── -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-lg font-bold text-slate-900 dark:text-slate-50">Quyền hệ thống</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Quản lý danh sách quyền và mapping với vai trò.</p>
      </div>
      <Button type="button" class="shrink-0 gap-1.5" :disabled="loading || saving" @click="openCreate">
        <PlusIcon class="h-4 w-4" aria-hidden="true" />
        Thêm quyền
      </Button>
    </div>

    <SystemPermissionsSummaryBar
      :items="items"
      :loading="loading"
      :active-filter="permActiveFilter"
      @quick-filter="onKpiQuickFilter"
    />

    <div
      ref="datagridRef"
      class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
    >
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="mb-2">
          <h2 class="text-sm font-semibold text-slate-800 dark:text-slate-100">
            {{ t('system_pages.permissions.list_title') }}
            <span class="ml-1 text-xs font-normal text-slate-400">({{ filteredItems.length }})</span>
          </h2>
        </div>
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
          <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
            <DatagridToolbarSearch
              v-model="searchRaw"
              input-id="system-permissions-search"
              :placeholder="t('system_pages.permissions.search_ph')"
              :aria-label="t('system_pages.permissions.search_aria')"
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
                  test-id="system-permissions-toolbar-filter"
                  @click="toggleFilterPanel"
                >
                  {{ t('system_pages.toolbar_filter') }}
                </DatagridToolbarActionButton>
              </template>
              <li v-for="fd in filterControlDefs" :key="'perm-vis-' + fd.key" class="flex items-start gap-2">
                <input :id="'perm-filter-vis-' + fd.key" v-model="visibleFilters[fd.key]" type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800" />
                <label :for="'perm-filter-vis-' + fd.key" class="cursor-pointer text-sm text-slate-700 dark:text-slate-300">{{ fd.label }}</label>
              </li>
            </FilterVisibilityDropdown>
            <button type="button" class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800" :title="t('system_pages.clear_filters')" data-testid="system-permissions-reset-filters" @click="resetFilters">
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              <XMarkIcon class="h-3 w-3 text-rose-500" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>

      <div v-if="hasFilterRow" class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700">
        <DatagridFilterField v-if="visibleFilters.module">
          <select v-model="filterModule" :class="FILTER_CONTROL_CLASS" :aria-label="t('system_pages.permissions.filter_module')" data-testid="system-permissions-filter-module">
            <option v-for="opt in MODULE_OPTS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </DatagridFilterField>
        <DatagridFilterField v-if="visibleFilters.role">
          <select v-model="filterRoleId" :class="FILTER_CONTROL_CLASS" :aria-label="t('system_pages.permissions.filter_role')" data-testid="system-permissions-filter-role">
            <option value="">{{ t('system_pages.permissions.filter_role') }}</option>
            <option v-for="r in roles" :key="r.id" :value="String(r.id)">{{ r.display_name || r.name }}</option>
          </select>
        </DatagridFilterField>
        <DatagridFilterField v-if="visibleFilters.unassigned" class="sm:col-span-2">
          <label class="flex h-10 w-full cursor-pointer items-center gap-2 rounded-lg bg-slate-50 px-3 text-sm dark:bg-slate-800/50">
            <input v-model="filterUnassigned" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600" data-testid="system-permissions-filter-unassigned" />
            {{ t('system_pages.permissions.filter_unassigned') }}
          </label>
        </DatagridFilterField>
        <DatagridFilterField v-if="visibleFilters.assigned" class="sm:col-span-2">
          <label class="flex h-10 w-full cursor-pointer items-center gap-2 rounded-lg bg-slate-50 px-3 text-sm dark:bg-slate-800/50">
            <input v-model="filterAssignedOnly" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600" data-testid="system-permissions-filter-assigned" />
            {{ t('system_pages.permissions.filter_assigned') }}
          </label>
        </DatagridFilterField>
        <DatagridFilterField v-if="visibleFilters.system" class="sm:col-span-2">
          <label class="flex h-10 w-full cursor-pointer items-center gap-2 rounded-lg bg-slate-50 px-3 text-sm dark:bg-slate-800/50">
            <input v-model="filterSystemOnly" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600" data-testid="system-permissions-filter-system" />
            {{ t('system_pages.permissions.filter_system') }}
          </label>
        </DatagridFilterField>
      </div>

      <div v-if="loading" class="space-y-2 p-4 sm:p-5">
        <div v-for="i in 4" :key="i" class="h-24 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800" />
      </div>

      <template v-else-if="!items.length">
        <div class="py-12 text-center">
          <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Chưa có quyền nào trong hệ thống.</p>
          <Button class="mt-4" data-testid="system-permissions-add-first" @click="openCreate">Thêm quyền đầu tiên</Button>
        </div>
      </template>

      <template v-else>
        <div v-if="grouped.length === 0" class="border-t border-slate-100 py-10 text-center text-sm text-amber-800 dark:border-slate-700 dark:text-amber-200">
          Không tìm thấy quyền nào khớp bộ lọc.
        </div>
        <div v-else class="space-y-4 border-t border-slate-100 p-3 sm:p-4 dark:border-slate-700">
          <section v-for="group in grouped" :key="group.module.id" class="space-y-2">
            <button
              type="button"
              class="flex w-full items-center justify-between rounded-xl bg-slate-100/80 px-3 py-2.5 text-left dark:bg-slate-800/50"
              :data-testid="`perm-module-${group.module.id}`"
              @click="toggleModule(group.module.id)"
            >
              <span class="flex items-center gap-2.5">
                <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ group.module.label }}</span>
                <span class="rounded-full bg-white/80 px-2 py-0.5 text-[11px] font-medium text-slate-500 dark:bg-slate-900/60 dark:text-slate-400">{{ group.perms.length }}</span>
              </span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400 transition-transform" :class="{ '-rotate-180': openModules.has(group.module.id) }" aria-hidden="true" />
            </button>
            <div v-show="openModules.has(group.module.id)" class="space-y-2 pl-1 sm:pl-2">
              <SystemPermissionRecordCard
                v-for="perm in group.perms"
                :key="perm.id"
                :perm="perm"
                :display-name="permDisplayName(perm)"
                :is-system="isSystemPerm(perm.name)"
                :roles="rolesOfPerm(perm)"
                :role-color-map="roleColorMap"
                :role-colors-fallback="ROLE_COLORS[0]"
                :saving="saving"
                @edit="openEdit(perm)"
                @remove="removePerm(perm)"
              />
            </div>
          </section>
        </div>
      </template>
    </div>

    <!-- ── Create modal ───────────────────────────────────────────────────── -->
    <!-- ── Create modal ───────────────────────────────────────────────────── -->
    <Teleport to="body">
    <div
      v-if="createOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="perm-create-title"
      @click.self="createOpen = false"
    >
      <Card class="max-h-[90vh] w-full max-w-lg overflow-y-auto shadow-xl">
        <div class="mb-4 flex items-center justify-between">
          <h2 id="perm-create-title" class="text-sm font-semibold text-slate-900 dark:text-slate-100">Thêm quyền mới</h2>
          <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" @click="createOpen = false">
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <form class="space-y-4" @submit.prevent="submitCreate">
          <Select v-model="presetVal" label="Chọn từ mẫu có sẵn">
            <option value="">— Không dùng mẫu —</option>
            <option v-for="p in SEED_PERMISSION_PRESETS" :key="p.name" :value="p.name">{{ p.name }}</option>
          </Select>

          <div class="grid gap-4 sm:grid-cols-2">
            <Input v-model="createForm.name" label="Mã quyền *" placeholder="vd. request.create" required class="sm:col-span-1" />
            <Input v-model="createForm.display_name" label="Tên hiển thị" placeholder="vd. Tạo yêu cầu điều xe" class="sm:col-span-1" />
          </div>

          <label class="block">
            <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Mô tả (tùy chọn)</span>
            <textarea
              v-model="createForm.plain_description"
              rows="3"
              placeholder="Giải thích ngắn về quyền này…"
              class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
            />
          </label>

          <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 dark:border-slate-800 sm:flex-row sm:justify-end">
            <Button variant="secondary" type="button" :disabled="saving" @click="createOpen = false">Huỷ</Button>
            <Button type="submit" :loading="saving" :disabled="saving || !createForm.name.trim()">Thêm quyền</Button>
          </div>
        </form>
      </Card>
    </div>
    </Teleport>

    <!-- ── Edit slide panel ───────────────────────────────────────────────── -->
    <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-x-full opacity-0"
      enter-to-class="translate-x-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="translate-x-0 opacity-100"
      leave-to-class="translate-x-full opacity-0"
    >
      <div v-if="editingPerm" class="fixed inset-0 z-50 flex justify-end">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/30" @click="closeEdit" />

        <!-- Panel -->
        <div class="relative z-10 flex h-full w-full max-w-md flex-col bg-white shadow-2xl dark:bg-slate-900">
          <!-- Panel header -->
          <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
            <div>
              <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Chỉnh sửa quyền</h2>
              <p class="mt-0.5 font-mono text-[11px] text-slate-400">{{ editingPerm.name }}</p>
            </div>
            <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" :disabled="saving" @click="closeEdit">
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>

          <!-- Panel body -->
          <div class="flex-1 space-y-4 overflow-y-auto p-5">
            <!-- System warning -->
            <div
              v-if="isSystemPerm(editingPerm.name)"
              class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200"
            >
              <LockClosedIcon class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
              Đây là quyền hệ thống. Chỉ chỉnh sửa tên hiển thị và mô tả.
            </div>

            <!-- Roles using this perm -->
            <div v-if="rolesOfPerm(editingPerm).length" class="rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/40">
              <p class="mb-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400">VAI TRÒ ĐANG DÙNG</p>
              <div class="flex flex-wrap gap-1">
                <span
                  v-for="r in rolesOfPerm(editingPerm)"
                  :key="r.id"
                  class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                  :class="roleColorMap.get(r.id) ?? ROLE_COLORS[0]"
                >{{ r.display_name || r.name }}</span>
              </div>
            </div>

            <Input v-model="editForm.name" label="Mã quyền" :disabled="isSystemPerm(editingPerm.name)" />
            <Input v-model="editForm.display_name" label="Tên hiển thị" placeholder="Tên thân thiện để hiển thị trong UI" />

            <label class="block">
              <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Mô tả</span>
              <textarea
                v-model="editForm.plain_description"
                rows="4"
                placeholder="Giải thích quyền này cho phép làm gì…"
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
              />
            </label>
          </div>

          <!-- Panel footer -->
          <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-700">
            <Button variant="secondary" :disabled="saving" @click="closeEdit">Huỷ</Button>
            <Button :loading="saving" :disabled="saving" @click="submitEdit">Lưu thay đổi</Button>
          </div>
        </div>
      </div>
    </Transition>
    </Teleport>

  </div>
</template>
