<script setup>
import { computed, onActivated, onMounted, reactive, ref, watch } from 'vue'
import {
  ChevronDownIcon,
  FunnelIcon,
  LockClosedIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  PlusIcon,
  TrashIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { useFilterBarVisibility } from '../../composables/useFilterBarVisibility.js'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterFunnelMenu from '../../components/filters/AppFilterFunnelMenu.vue'
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

// ─── Màu badge role ───────────────────────────────────────────────────────────

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

const PERM_FILTER_VIS_IDS = ['module', 'role', 'unassigned', 'assigned', 'system']
const PERM_FILTER_VIS_DEFAULTS = Object.fromEntries(PERM_FILTER_VIS_IDS.map((id) => [id, false]))
const {
  visible: filterBarVisible,
  resetVisibility: resetFilterBarVisibility,
  hasVisibleOnBar: hasVisibleBarFilters,
} = useFilterBarVisibility(PERM_FILTER_VIS_IDS, PERM_FILTER_VIS_DEFAULTS)

const filterMenuRef = ref(null)

const permFilterVisibilityOptions = [
  { id: 'module', label: 'Module' },
  { id: 'role', label: 'Vai trò' },
  { id: 'unassigned', label: 'Chưa gán vai trò' },
  { id: 'assigned', label: 'Đã gán vai trò' },
  { id: 'system', label: 'Quyền hệ thống (system.*)' },
]

function onPermissionsFilterBarEnter() {
  resetFilterBarVisibility()
}

function closeFilterMenu() {
  filterMenuRef.value?.close?.()
}

function moduleFilterSummary() {
  if (filterModule.value === 'all') return 'Tất cả module'
  return MODULE_OPTS.value.find((o) => o.value === filterModule.value)?.label ?? filterModule.value
}

function roleFilterSummary() {
  if (!filterRoleId.value) return 'Tất cả vai trò'
  const r = roles.value.find((x) => String(x.id) === filterRoleId.value)
  return r?.display_name || r?.name || filterRoleId.value
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

onMounted(() => {
  onPermissionsFilterBarEnter()
  load()
})

onActivated(() => {
  onPermissionsFilterBarEnter()
})
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

    <AppFilterBar>
      <div class="flex w-full flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
        <AppFilterFunnelMenu ref="filterMenuRef" :badge-count="activeFilters">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Bộ lọc đang áp dụng</p>
          <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
            <li v-if="searchRaw.trim()" class="flex justify-between gap-2">
              <span class="text-slate-500">Tìm kiếm</span>
              <span class="max-w-[60%] truncate text-right font-medium">{{ searchRaw }}</span>
            </li>
            <li v-if="filterModule !== 'all'" class="flex justify-between gap-2">
              <span class="text-slate-500">Module</span>
              <span class="font-medium">{{ moduleFilterSummary() }}</span>
            </li>
            <li v-if="filterRoleId" class="flex justify-between gap-2">
              <span class="text-slate-500">Vai trò</span>
              <span class="font-medium">{{ roleFilterSummary() }}</span>
            </li>
            <li v-if="filterUnassigned" class="flex justify-between gap-2">
              <span class="text-slate-500">Gán vai trò</span>
              <span class="font-medium">Chưa gán</span>
            </li>
            <li v-if="filterAssignedOnly" class="flex justify-between gap-2">
              <span class="text-slate-500">Gán vai trò</span>
              <span class="font-medium">Đã gán</span>
            </li>
            <li v-if="filterSystemOnly" class="flex justify-between gap-2">
              <span class="text-slate-500">Loại quyền</span>
              <span class="font-medium">system.*</span>
            </li>
            <li v-if="activeFilters === 0" class="text-slate-400">Chưa có điều kiện lọc</li>
          </ul>
          <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">Hiển thị bộ lọc trên thanh</p>
            <ul class="mt-2 space-y-2">
              <li v-for="opt in permFilterVisibilityOptions" :key="'perm-vis-' + opt.id" class="flex gap-2">
                <input :id="'perm-filter-vis-' + opt.id" v-model="filterBarVisible[opt.id]" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600" />
                <label :for="'perm-filter-vis-' + opt.id" class="text-sm">{{ opt.label }}</label>
              </li>
            </ul>
          </div>
          <button type="button" class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-medium dark:border-slate-600" @click="resetFilters(); closeFilterMenu()">
            Xóa tất cả bộ lọc
          </button>
        </AppFilterFunnelMenu>
        <div class="hidden h-6 w-px bg-slate-200 sm:block dark:bg-slate-700" aria-hidden="true" />
        <button type="button" class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500" title="Xóa bộ lọc" @click="resetFilters">
          <span class="relative inline-flex">
            <FunnelIcon class="h-5 w-5" />
            <XMarkIcon class="absolute -right-0.5 -top-0.5 h-3 w-3 text-rose-500" />
          </span>
        </button>
        <div class="relative min-w-0 flex-1 basis-[10rem] sm:min-w-[12rem] sm:max-w-md">
          <MagnifyingGlassIcon class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
          <input
            v-model="searchRaw"
            type="search"
            placeholder="Tìm quyền…"
            aria-label="Tìm quyền"
            class="h-9 w-full rounded-md border-0 bg-white/90 py-0 pl-9 pr-3 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600 dark:placeholder:text-slate-500"
          />
        </div>
      </div>
      <div v-if="hasVisibleBarFilters" class="mt-2 flex flex-wrap items-center gap-2 border-t border-violet-100/80 pt-2 dark:border-violet-900/30">
        <select
          v-if="filterBarVisible.module"
          v-model="filterModule"
          aria-label="Lọc theo module"
          class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-600 dark:bg-slate-900"
          :class="filterModule === 'all' ? 'text-slate-500' : 'text-slate-900 dark:text-slate-100'"
        >
          <option v-for="opt in MODULE_OPTS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <select
          v-if="filterBarVisible.role"
          v-model="filterRoleId"
          aria-label="Lọc theo vai trò"
          class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-600 dark:bg-slate-900"
          :class="!filterRoleId ? 'text-slate-500' : 'text-slate-900 dark:text-slate-100'"
        >
          <option value="">Vai trò</option>
          <option v-for="r in roles" :key="r.id" :value="String(r.id)">
            {{ r.display_name || r.name }}
          </option>
        </select>
        <label
          v-if="filterBarVisible.unassigned"
          class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
        >
          <input v-model="filterUnassigned" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900" />
          Chưa gán vai trò
        </label>
        <label
          v-if="filterBarVisible.assigned"
          class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
        >
          <input v-model="filterAssignedOnly" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900" />
          Đã gán vai trò
        </label>
        <label
          v-if="filterBarVisible.system"
          class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
        >
          <input v-model="filterSystemOnly" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900" />
          Quyền system.*
        </label>
      </div>
    </AppFilterBar>

    <!-- ── Loading ────────────────────────────────────────────────────────── -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="h-32 animate-pulse rounded-xl border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800" />
    </div>

    <!-- ── Empty ─────────────────────────────────────────────────────────── -->
    <template v-else-if="!items.length">
      <Card>
        <div class="py-12 text-center">
          <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Chưa có quyền nào trong hệ thống.</p>
          <Button class="mt-4" @click="openCreate">Thêm quyền đầu tiên</Button>
        </div>
      </Card>
    </template>

    <template v-else>
      <!-- Không khớp filter -->
      <div
        v-if="grouped.length === 0"
        class="rounded-xl border border-dashed border-amber-200/80 bg-amber-50/40 py-10 text-center text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200"
      >
        Không tìm thấy quyền nào khớp bộ lọc.
      </div>

      <!-- Module groups -->
      <div v-else class="space-y-3">
        <div
          v-for="group in grouped"
          :key="group.module.id"
          class="overflow-hidden rounded-xl border border-slate-200/90 shadow-sm dark:border-slate-700"
        >
          <!-- Module header -->
          <button
            type="button"
            class="flex w-full items-center justify-between px-4 py-3 text-left transition"
            :class="openModules.has(group.module.id)
              ? 'bg-slate-50 dark:bg-slate-800/60'
              : 'bg-white hover:bg-slate-50/60 dark:bg-slate-900 dark:hover:bg-slate-800/40'"
            @click="toggleModule(group.module.id)"
          >
            <span class="flex items-center gap-2.5">
              <span class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ group.module.label }}</span>
              <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                {{ group.perms.length }}
              </span>
            </span>
            <ChevronDownIcon
              class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
              :class="{ '-rotate-180': openModules.has(group.module.id) }"
              aria-hidden="true"
            />
          </button>

          <!-- Perm rows -->
          <div v-show="openModules.has(group.module.id)" class="divide-y divide-slate-100 dark:divide-slate-800">
            <div
              v-for="perm in group.perms"
              :key="perm.id"
              class="flex items-start gap-3 bg-white px-4 py-3 transition hover:bg-slate-50/50 dark:bg-slate-900/60 dark:hover:bg-slate-800/30"
            >
              <!-- Info -->
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-1.5">
                  <span class="font-semibold text-slate-900 dark:text-slate-100">{{ permDisplayName(perm) }}</span>
                  <!-- System badge -->
                  <span
                    v-if="isSystemPerm(perm.name)"
                    class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                  >
                    <LockClosedIcon class="h-2.5 w-2.5" aria-hidden="true" />
                    Hệ thống
                  </span>
                </div>
                <p class="mt-0.5 font-mono text-[11px] text-slate-400 dark:text-slate-500">{{ perm.name }}</p>
                <p v-if="perm.plain_summary" class="mt-1 text-xs leading-snug text-slate-500 dark:text-slate-400">{{ perm.plain_summary }}</p>
              </div>

              <!-- Role badges -->
              <div class="flex shrink-0 flex-wrap justify-end gap-1 pt-0.5">
                <template v-if="rolesOfPerm(perm).length">
                  <span
                    v-for="r in rolesOfPerm(perm)"
                    :key="r.id"
                    :title="r.name"
                    class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                    :class="roleColorMap.get(r.id) ?? ROLE_COLORS[0]"
                  >{{ r.display_name || r.name }}</span>
                </template>
                <span
                  v-else
                  class="rounded-full border border-dashed border-slate-200 px-2 py-0.5 text-[10px] text-slate-400 dark:border-slate-700 dark:text-slate-500"
                >Chưa gán</span>
              </div>

              <!-- Actions -->
              <div class="flex shrink-0 items-center gap-1 self-start">
                <button
                  type="button"
                  title="Chỉnh sửa"
                  class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                  :disabled="saving"
                  @click="openEdit(perm)"
                >
                  <PencilSquareIcon class="h-4 w-4" aria-hidden="true" />
                </button>
                <button
                  v-if="!isSystemPerm(perm.name)"
                  type="button"
                  :title="(perm.role_ids?.length ?? 0) > 0 ? `${perm.role_ids.length} vai trò đang dùng` : 'Xóa quyền'"
                  class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                  :disabled="saving"
                  @click="removePerm(perm)"
                >
                  <TrashIcon class="h-4 w-4" aria-hidden="true" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

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
