<script setup>
import { computed, onActivated, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  MagnifyingGlassIcon,
  FunnelIcon,
  XMarkIcon,
  PlusIcon,
  PencilSquareIcon,
  TrashIcon,
  DocumentDuplicateIcon,
  UserGroupIcon,
  ShieldCheckIcon,
  ChevronRightIcon,
  SparklesIcon,
  ChevronDownIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'
import { JOB_ROLE_TEMPLATES } from '../../config/jobRoleTemplates.js'
import { getPermissionFriendlyTitle } from '../../config/businessCapabilities.js'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import { debounceTrailing } from '../../composables/useDebounce'
import { useFilterBarVisibility } from '../../composables/useFilterBarVisibility.js'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterFunnelMenu from '../../components/filters/AppFilterFunnelMenu.vue'

const router = useRouter()

const ROLE_FILTER_VIS_IDS = ['users', 'template']
const ROLE_FILTER_VIS_DEFAULTS = Object.fromEntries(ROLE_FILTER_VIS_IDS.map((id) => [id, false]))
const {
  visible: filterBarVisible,
  resetVisibility: resetFilterBarVisibility,
  hasVisibleOnBar: hasVisibleBarFilters,
} = useFilterBarVisibility(ROLE_FILTER_VIS_IDS, ROLE_FILTER_VIS_DEFAULTS)

const filterMenuRef = ref(null)
const filterUsers = ref('all')
const filterTemplate = ref('all')

const roleFilterVisibilityOptions = [
  { id: 'users', label: 'Nhân viên được gán' },
  { id: 'template', label: 'Loại vai trò (mẫu)' },
]

const activeRoleSearchFilter = computed(() => {
  let n = 0
  if (debouncedQuery.value.trim()) n++
  if (filterUsers.value !== 'all') n++
  if (filterTemplate.value !== 'all') n++
  return n
})

function onSystemRolesFilterBarEnter() {
  resetFilterBarVisibility()
}

function closeFilterMenu() {
  filterMenuRef.value?.close?.()
}

function clearRoleSearch() {
  query.value = ''
  debouncedQuery.value = ''
  filterUsers.value = 'all'
  filterTemplate.value = 'all'
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

onMounted(() => {
  onSystemRolesFilterBarEnter()
  load()
})

onActivated(() => {
  onSystemRolesFilterBarEnter()
})
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

    <!-- ── Stats strip ─────────────────────────────────────────────────────── -->
    <div v-if="!loading && roles.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Vai trò đang dùng</p>
        <p class="mt-1 text-2xl font-bold text-slate-900 tabular-nums dark:text-slate-50">{{ roles.length }}</p>
      </div>
      <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Nhân viên được gán</p>
        <p class="mt-1 text-2xl font-bold text-slate-900 tabular-nums dark:text-slate-50">{{ totalUsers }}</p>
      </div>
      <div class="col-span-2 rounded-2xl border border-slate-200/80 bg-white px-4 py-3 sm:col-span-1 dark:border-slate-700 dark:bg-slate-900">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Mẫu có sẵn</p>
        <p class="mt-1 text-2xl font-bold text-slate-900 tabular-nums dark:text-slate-50">{{ JOB_ROLE_TEMPLATES.length - 1 }}</p>
      </div>
    </div>

    <AppFilterBar>
      <div class="flex w-full flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
        <AppFilterFunnelMenu ref="filterMenuRef" :badge-count="activeRoleSearchFilter">
          <p class="text-xs font-semibold uppercase text-slate-500">Bộ lọc đang áp dụng</p>
          <ul class="mt-2 space-y-2 text-sm text-slate-700">
            <li v-if="query.trim()" class="flex justify-between gap-2"><span class="text-slate-500">Tìm kiếm</span><span class="truncate font-medium">{{ query }}</span></li>
            <li v-if="filterUsers !== 'all'" class="flex justify-between gap-2">
              <span class="text-slate-500">Nhân viên</span>
              <span class="font-medium">{{ filterUsers === 'with_users' ? 'Có người dùng' : 'Chưa gán ai' }}</span>
            </li>
            <li v-if="filterTemplate !== 'all'" class="flex justify-between gap-2">
              <span class="text-slate-500">Loại vai trò</span>
              <span class="font-medium">{{ filterTemplate === 'template' ? 'Theo mẫu' : 'Tùy chỉnh' }}</span>
            </li>
            <li v-if="activeRoleSearchFilter === 0" class="text-slate-400">Chưa có điều kiện lọc</li>
          </ul>
          <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
            <p class="text-[11px] font-semibold uppercase text-violet-700 dark:text-violet-300">Hiển thị bộ lọc trên thanh</p>
            <ul class="mt-2 space-y-2">
              <li v-for="opt in roleFilterVisibilityOptions" :key="'role-vis-' + opt.id" class="flex gap-2">
                <input :id="'role-filter-vis-' + opt.id" v-model="filterBarVisible[opt.id]" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600" />
                <label :for="'role-filter-vis-' + opt.id" class="text-sm">{{ opt.label }}</label>
              </li>
            </ul>
          </div>
          <button type="button" class="mt-3 w-full rounded-lg border py-2 text-sm" @click="clearRoleSearch(); closeFilterMenu()">Xóa bộ lọc</button>
        </AppFilterFunnelMenu>
        <div class="hidden h-6 w-px bg-slate-200 sm:block dark:bg-slate-700" />
        <button type="button" class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500" @click="clearRoleSearch">
          <span class="relative inline-flex">
            <FunnelIcon class="h-5 w-5" aria-hidden="true" />
            <XMarkIcon class="absolute -right-0.5 -top-0.5 h-3 w-3 text-rose-500" aria-hidden="true" />
          </span>
        </button>
        <div class="relative min-w-0 flex-1 basis-[10rem] sm:min-w-[12rem] sm:max-w-md">
          <MagnifyingGlassIcon class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
          <input
            v-model="query"
            type="search"
            placeholder="Tìm theo tên vai trò hoặc mô tả…"
            aria-label="Tìm vai trò"
            class="h-9 w-full rounded-md border-0 bg-white/90 py-0 pl-9 pr-3 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600 dark:placeholder:text-slate-500"
            @input="bumpQ"
          />
        </div>
      </div>
      <div v-if="hasVisibleBarFilters" class="mt-2 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-2 dark:border-slate-700">
        <select
          v-if="filterBarVisible.users"
          v-model="filterUsers"
          class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm dark:border-slate-700 dark:bg-slate-900"
          :class="filterUsers === 'all' ? 'text-slate-500' : 'text-slate-900 dark:text-slate-100'"
        >
          <option value="all">Nhân viên được gán</option>
          <option value="with_users">Có người dùng</option>
          <option value="empty">Chưa gán ai</option>
        </select>
        <select
          v-if="filterBarVisible.template"
          v-model="filterTemplate"
          class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm dark:border-slate-700 dark:bg-slate-900"
          :class="filterTemplate === 'all' ? 'text-slate-500' : 'text-slate-900 dark:text-slate-100'"
        >
          <option value="all">Loại vai trò</option>
          <option value="template">Theo mẫu công việc</option>
          <option value="custom">Tùy chỉnh</option>
        </select>
      </div>
    </AppFilterBar>

    <!-- ── Loading ─────────────────────────────────────────────────────────── -->
    <div v-if="loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="i in 6"
        :key="i"
        class="h-40 animate-pulse rounded-2xl border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800"
      />
    </div>

    <!-- ── Empty state ────────────────────────────────────────────────────── -->
    <template v-else-if="!roles.length">
      <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 py-16 text-center dark:border-slate-700 dark:bg-slate-900/30">
        <ShieldCheckIcon class="mb-4 h-14 w-14 text-slate-300 dark:text-slate-600" />
        <p class="text-base font-semibold text-slate-700 dark:text-slate-300">Chưa có vai trò nào</p>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Tạo vai trò đầu tiên bằng cách chọn một mẫu công việc.</p>
        <button
          type="button"
          class="mt-5 inline-flex items-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700"
          @click="openNew"
        >
          <PlusIcon class="h-4 w-4" />
          Tạo vai trò đầu tiên
        </button>
      </div>
    </template>

    <template v-else>
      <!-- ── No search match ──────────────────────────────────────────────── -->
      <div
        v-if="filteredRoles.length === 0"
        class="rounded-2xl border border-amber-200/80 bg-amber-50/60 py-10 text-center text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200"
      >
        Không có vai trò nào khớp với "{{ debouncedQuery }}".
      </div>

      <!-- ── Role cards grid ─────────────────────────────────────────────── -->
      <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <article
          v-for="role in filteredRoles"
          :key="role.id"
          class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm transition hover:border-slate-300 hover:shadow-md dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600"
        >
          <!-- Card top color accent -->
          <div
            class="h-1 w-full"
            :class="{
              'bg-blue-400':   getRoleColorKey(role) === 'blue',
              'bg-teal-400':   getRoleColorKey(role) === 'teal',
              'bg-amber-400':  getRoleColorKey(role) === 'amber',
              'bg-green-400':  getRoleColorKey(role) === 'green',
              'bg-indigo-400': getRoleColorKey(role) === 'indigo',
              'bg-purple-400': getRoleColorKey(role) === 'purple',
              'bg-violet-400': getRoleColorKey(role) === 'violet',
              'bg-rose-400':   getRoleColorKey(role) === 'rose',
              'bg-cyan-400':   getRoleColorKey(role) === 'cyan',
              'bg-slate-300':  getRoleColorKey(role) === 'slate',
            }"
          />

          <!-- Card body -->
          <div class="flex flex-1 flex-col p-5">
            <!-- Icon + name -->
            <div class="mb-3 flex items-start gap-3">
              <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-2xl dark:bg-slate-800">
                {{ getRoleIcon(role) }}
              </span>
              <div class="min-w-0 flex-1">
                <h3 class="truncate text-base font-semibold text-slate-900 dark:text-slate-50">
                  {{ role.display_name || role.name }}
                </h3>
                <p v-if="role.description" class="mt-0.5 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">
                  {{ role.description }}
                </p>
                <p v-else class="mt-0.5 text-xs italic text-slate-400 dark:text-slate-600">Chưa có mô tả</p>
              </div>
            </div>

            <!-- Stats row -->
            <div class="mt-auto flex items-center gap-4 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
              <span class="flex items-center gap-1.5">
                <UserGroupIcon class="h-3.5 w-3.5" />
                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ role.users_count ?? 0 }}</span>
                nhân viên
              </span>
              <!-- Badge permissions — click để expand -->
              <button
                type="button"
                class="flex items-center gap-1.5 transition hover:text-slate-800 dark:hover:text-slate-200"
                :title="expandedPermRoleId === role.id ? 'Ẩn danh sách quyền' : 'Xem danh sách quyền'"
                @click="togglePermissions(role)"
              >
                <ShieldCheckIcon class="h-3.5 w-3.5" />
                <span class="font-semibold text-slate-700 dark:text-slate-300">{{ role.permissions_count ?? 0 }}</span>
                quyền
                <ChevronDownIcon
                  class="h-3 w-3 transition-transform duration-200"
                  :class="{ '-rotate-180': expandedPermRoleId === role.id }"
                  aria-hidden="true"
                />
              </button>
            </div>

            <!-- Expand: danh sách permissions -->
            <div v-if="expandedPermRoleId === role.id" class="border-t border-slate-100 px-4 py-3 dark:border-slate-800">
              <div v-if="permLoading" class="text-xs text-slate-400">Đang tải…</div>
              <div v-else-if="!permsByRoleId[role.id]?.length" class="text-xs text-slate-400">Chưa có quyền nào.</div>
              <div v-else class="flex flex-wrap gap-1">
                <span
                  v-for="p in permsByRoleId[role.id]"
                  :key="p.id"
                  :title="`${p.name}${p.plain_summary ? ' — ' + p.plain_summary : ''}`"
                  class="rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                >{{ getPermissionFriendlyTitle(p) }}</span>
              </div>
            </div>
          </div>

          <!-- Action bar -->
          <div class="flex items-center justify-between border-t border-slate-100 px-4 py-2.5 dark:border-slate-800">
            <!-- View detail + Gán nhân viên -->
            <div class="flex items-center gap-3">
              <button
                type="button"
                class="flex items-center gap-1 text-xs font-medium text-teal-600 transition hover:text-teal-800 dark:text-teal-400 dark:hover:text-teal-200"
                @click="openDetail(role)"
              >
                Xem chi tiết
                <ChevronRightIcon class="h-3.5 w-3.5" />
              </button>
              <button
                type="button"
                class="flex items-center gap-1 text-xs font-medium text-violet-600 transition hover:text-violet-800 dark:text-violet-400 dark:hover:text-violet-200"
                :title="`Gán nhân viên vào vai trò ${role.display_name || role.name}`"
                @click="goToUserRoles(role)"
              >
                <UsersIcon class="h-3.5 w-3.5" />
                Gán nhân viên
              </button>
            </div>

            <!-- Quick actions -->
            <div class="flex items-center gap-0.5">
              <button
                type="button"
                title="Chỉnh sửa"
                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                :disabled="saving"
                @click="openEdit(role)"
              >
                <PencilSquareIcon class="h-4 w-4" />
              </button>
              <button
                type="button"
                title="Nhân bản"
                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                :disabled="saving"
                @click="cloneRole(role)"
              >
                <DocumentDuplicateIcon class="h-4 w-4" />
              </button>
              <button
                type="button"
                title="Xóa vai trò"
                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600 disabled:opacity-30 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                :disabled="saving || role.name === 'superadmin'"
                @click="remove(role)"
              >
                <TrashIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </article>

        <!-- New role CTA card -->
        <button
          type="button"
          class="flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-slate-200 bg-transparent py-10 text-slate-400 transition hover:border-teal-300 hover:bg-teal-50/40 hover:text-teal-600 dark:border-slate-700 dark:hover:border-teal-700 dark:hover:bg-teal-950/20 dark:hover:text-teal-400"
          @click="openNew"
        >
          <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800">
            <SparklesIcon class="h-6 w-6" />
          </span>
          <span class="text-sm font-medium">Tạo vai trò mới</span>
        </button>
      </div>
    </template>

  </div>
</template>
