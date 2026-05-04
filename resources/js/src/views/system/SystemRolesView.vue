<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { EllipsisVerticalIcon, FunnelIcon, ChevronDownIcon, PencilSquareIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { SEED_ROLE_PRESETS } from '../../config/systemSeedOptions'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import { debounceTrailing } from '../../composables/useDebounce'

const ROLES_FILTER_VIS_KEY = 'va.roles.filter_control_visibility_v1'
const FILTER_CONTROL_IDS = ['search', 'origin', 'perm']

function defaultFilterControlVisibility() {
  return FILTER_CONTROL_IDS.reduce((acc, id) => {
    acc[id] = true
    return acc
  }, {})
}

const loading = ref(true)
const saving = ref(false)
const items = ref([])
const allPerms = ref([])
const editing = ref(null)
const form = reactive({ name: '', display_name: '' })
const editForm = reactive({ name: '', display_name: '', permission_ids: [] })
const seedRoles = SEED_ROLE_PRESETS
const seedRoleNameSet = new Set(seedRoles.map((x) => x.name))
const rolePreset = ref('')

const funnelDetailsRef = ref(null)
const filterControlVisible = reactive(defaultFilterControlVisibility())
const createModalOpen = ref(false)

const searchInput = ref('')
const filterQ = ref('')
/** all | preset | custom */
const filterOrigin = ref('all')
/** all | has_perm | no_perm */
const filterPermScope = ref('all')

const filterControlDefs = Object.freeze([
  { id: 'search', label: 'Tìm trong danh sách' },
  { id: 'origin', label: 'Kiểu vai trò' },
  { id: 'perm', label: 'Số quyền đã gán' },
])

const ORIGIN_OPTS = Object.freeze([
  { value: 'all', label: 'Tất cả' },
  { value: 'preset', label: 'Mẫu có sẵn' },
  { value: 'custom', label: 'Tuỳ chỉnh' },
])

const PERM_SCOPE_OPTS = Object.freeze([
  { value: 'all', label: 'Tất cả' },
  { value: 'has_perm', label: 'Đã có quyền' },
  { value: 'no_perm', label: 'Chưa gán quyền' },
])

const bumpSearchDebounced = debounceTrailing(() => {
  filterQ.value = searchInput.value
}, 300)

watch(searchInput, () => bumpSearchDebounced())

const originChipLabel = computed(
  () => ORIGIN_OPTS.find((o) => o.value === filterOrigin.value)?.label ?? 'Tất cả',
)

const permScopeChipLabel = computed(
  () => PERM_SCOPE_OPTS.find((o) => o.value === filterPermScope.value)?.label ?? 'Tất cả',
)

const sortedPermsForEdit = computed(() =>
  [...allPerms.value].sort((a, b) => String(a.name).localeCompare(String(b.name))),
)

const filteredItems = computed(() => {
  let list = items.value

  switch (filterOrigin.value) {
    case 'preset':
      list = list.filter((r) => seedRoleNameSet.has(r.name))
      break
    case 'custom':
      list = list.filter((r) => !seedRoleNameSet.has(r.name))
      break
    default:
      break
  }

  switch (filterPermScope.value) {
    case 'has_perm':
      list = list.filter((r) => (r.permissions_count ?? 0) > 0)
      break
    case 'no_perm':
      list = list.filter((r) => (r.permissions_count ?? 0) === 0)
      break
    default:
      break
  }

  const q = filterQ.value.trim().toLowerCase()
  if (!q) return list
  return list.filter((r) => {
    const name = (r.name ?? '').toLowerCase()
    const dn = (r.display_name ?? '').toLowerCase()
    return name.includes(q) || dn.includes(q)
  })
})

const activeFilterCount = computed(() => {
  let n = 0
  if (searchInput.value.trim()) n++
  if (filterOrigin.value !== 'all') n++
  if (filterPermScope.value !== 'all') n++
  return n
})

function loadFilterControlVisibility() {
  try {
    const raw = localStorage.getItem(ROLES_FILTER_VIS_KEY)
    if (!raw) return
    const o = JSON.parse(raw)
    const base = defaultFilterControlVisibility()
    FILTER_CONTROL_IDS.forEach((id) => {
      if (typeof o[id] === 'boolean') base[id] = o[id]
    })
    Object.assign(filterControlVisible, base)
  } catch {
    /* ignore */
  }
}

watch(
  filterControlVisible,
  () => {
    try {
      localStorage.setItem(ROLES_FILTER_VIS_KEY, JSON.stringify({ ...filterControlVisible }))
    } catch {
      /* ignore */
    }
  },
  { deep: true },
)

function closeParentDetails(ev) {
  const el = ev.currentTarget
  if (!el?.closest) return
  const d = el.closest('details')
  if (d) d.open = false
}

function resetFilters() {
  searchInput.value = ''
  filterQ.value = ''
  filterOrigin.value = 'all'
  filterPermScope.value = 'all'
  const el = funnelDetailsRef.value
  if (el) el.open = false
}

watch(rolePreset, (v) => {
  if (!v) return
  const r = seedRoles.find((x) => x.name === v)
  if (r) {
    form.name = r.name
    form.display_name = r.display_name
  }
})

async function load() {
  loading.value = true
  try {
    const [roles, perms] = await Promise.all([admin.listRoles(), admin.listPermissions()])
    items.value = roles ?? []
    allPerms.value = perms ?? []
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

async function create() {
  if (!form.name.trim()) return
  saving.value = true
  try {
    await admin.createRole({ name: form.name.trim(), display_name: form.display_name || null, permission_ids: [] })
    form.name = ''
    form.display_name = ''
    rolePreset.value = ''
    await load()
    showAppSuccess('Đã thêm vai trò mới.', 'Thành công')
    createModalOpen.value = false
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

function closeCreateModal() {
  if (saving.value) return
  createModalOpen.value = false
}

function toggleEditPermission(pid) {
  const arr = editForm.permission_ids
  const i = arr.indexOf(pid)
  if (i >= 0) arr.splice(i, 1)
  else arr.push(pid)
}

function isEditPermSelected(pid) {
  return editForm.permission_ids.includes(pid)
}

function openEdit(r) {
  editing.value = r
  editForm.name = r.name
  editForm.display_name = r.display_name ?? ''
  editForm.permission_ids = []
  admin
    .getRole(r.id)
    .then((detail) => {
      editForm.permission_ids = (detail.permissions ?? []).map((p) => p.id)
    })
    .catch(() => {})
}

async function saveEdit() {
  if (!editing.value) return
  saving.value = true
  try {
    await admin.updateRole(editing.value.id, {
      name: editForm.name.trim(),
      display_name: editForm.display_name || null,
      permission_ids: editForm.permission_ids,
    })
    editing.value = null
    await load()
    showAppSuccess('Đã lưu thay đổi vai trò.', 'Thành công')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

async function remove(r) {
  const ok = await confirmAction({
    title: 'Xóa vai trò?',
    message: `Xóa vai trò «${r.display_name || r.name}» (mã ${r.name})? Người đang dùng vai này có thể bị ảnh hưởng.`,
    confirmLabel: 'Xóa',
    danger: true,
  })
  if (!ok) return
  try {
    await admin.deleteRole(r.id)
    await load()
    showAppSuccess('Đã xóa vai trò.', 'Thành công')
  } catch (e) {
    showAppError(formatApiError(e))
  }
}

function setOriginFilter(ev, v) {
  filterOrigin.value = v
  closeParentDetails(ev)
}

function setPermScopeFilter(ev, v) {
  filterPermScope.value = v
  closeParentDetails(ev)
}

function closeRowActionMenuThen(fn) {
  return (ev) => {
    const d = ev?.currentTarget?.closest?.('details')
    if (d) d.open = false
    fn()
  }
}

onMounted(() => {
  loadFilterControlVisibility()
  load()
})
</script>

<template>
  <div class="mx-auto max-w-6xl space-y-5 pb-6 text-slate-900 dark:text-slate-100 sm:space-y-6 sm:pb-8">
    <Card>
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Danh sách vai trò</h2>
        <Button type="button" class="w-full sm:w-auto" :disabled="saving" @click="createModalOpen = true">
          + Thêm mới
        </Button>
      </div>
      <div class="relative z-40 mb-4">
        <AppFilterBar>
          <div class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
            <details ref="funnelDetailsRef" class="group relative">
              <summary
                class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
              >
                <span class="relative inline-flex">
                  <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" aria-hidden="true" />
                  <span
                    v-if="activeFilterCount > 0"
                    class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-500 px-1 text-[10px] font-bold leading-none text-white"
                  >
                    {{ activeFilterCount }}
                  </span>
                </span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
              </summary>
              <div
                class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[260px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900 dark:shadow-black/30 dark:ring-slate-950/50"
              >
                <p
                  class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300"
                >
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
                    <li v-if="activeFilterCount === 0" class="text-slate-400 dark:text-slate-500">
                      Chưa chọn điều kiện lọc.
                    </li>
                  </ul>
                  <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                      Hiển thị trên thanh
                    </p>
                    <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
                      <li v-for="fd in filterControlDefs" :key="fd.id" class="flex items-start gap-2">
                        <input
                          :id="`roles-filter-vis-${fd.id}`"
                          v-model="filterControlVisible[fd.id]"
                          type="checkbox"
                          class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                        />
                        <label
                          :for="`roles-filter-vis-${fd.id}`"
                          class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                        >
                          {{ fd.label }}
                        </label>
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
                      :class="
                        filterOrigin === opt.value
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                      "
                      @click="setOriginFilter($event, opt.value)"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </AppFilterDropdown>

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
                      :class="
                        filterPermScope === opt.value
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                      "
                      @click="setPermScopeFilter($event, opt.value)"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <input
                v-if="filterControlVisible.search"
                v-model="searchInput"
                type="search"
                aria-label="Tìm theo mã hoặc tên vai trò"
                placeholder="Tìm mã hoặc tên hiển thị…"
                title="Tìm theo mã hoặc tên vai trò"
                class="h-9 w-[10rem] shrink-0 rounded-md border-0 bg-white/90 px-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600 dark:placeholder:text-slate-500 sm:w-52"
              />
            </div>

            <div
              class="ml-auto flex shrink-0 items-center gap-1 border-l border-violet-200/70 pl-2 sm:gap-2 sm:pl-3 dark:border-violet-900/40"
            >
              <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
                aria-label="Xóa bộ lọc"
                @click="resetFilters"
              >
                <span class="relative inline-flex">
                  <FunnelIcon class="h-5 w-5" aria-hidden="true" />
                  <XMarkIcon
                    class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40"
                  />
                </span>
              </button>
            </div>
          </div>
        </AppFilterBar>
      </div>

      <div v-if="loading" class="flex items-center gap-3 py-10 text-sm text-slate-500 dark:text-slate-400">
        <span
          class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-teal-600 dark:border-slate-600 dark:border-t-teal-400"
          aria-hidden="true"
        />
        Đang tải…
      </div>

      <template v-else-if="!items.length">
        <div
          class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 py-12 text-center dark:border-slate-700 dark:bg-slate-900/30"
        >
          <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Chưa có vai trò nào.</p>
        </div>
      </template>

      <template v-else>
        <div
          v-if="filteredItems.length === 0"
          class="rounded-xl border border-dashed border-amber-200/80 bg-amber-50/40 py-10 text-center text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200/90"
        >
          Không có vai trò khớp bộ lọc.
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-slate-200/90 shadow-sm dark:border-slate-700">
          <div class="overflow-x-auto">
            <table class="w-full min-w-[32rem] border-collapse text-left text-sm">
              <thead>
                <tr
                  class="border-b border-slate-200 bg-slate-50/95 text-slate-600 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-300"
                >
                  <th class="whitespace-nowrap py-2.5 pl-4 pr-3 text-xs font-semibold uppercase tracking-wide">Vai trò</th>
                  <th class="whitespace-nowrap py-2.5 pr-3 text-right text-xs font-semibold uppercase tracking-wide tabular-nums">
                    Quyền
                  </th>
                  <th class="whitespace-nowrap py-2.5 pl-2 pr-4 text-right text-xs font-semibold uppercase tracking-wide">
                    Thao tác
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(r, ri) in filteredItems"
                  :key="r.id"
                  class="border-b border-slate-100 transition-colors hover:bg-slate-50/80 dark:border-slate-800 dark:hover:bg-slate-800/40"
                  :class="ri % 2 === 1 ? 'bg-white dark:bg-transparent' : 'bg-slate-50/30 dark:bg-slate-900/40'"
                >
                  <td class="max-w-[20rem] py-2 pl-4 pr-3 align-middle">
                    <p class="font-semibold text-slate-900 dark:text-slate-50">
                      {{ r.display_name ?? r.name ?? '—' }}
                    </p>
                    <p class="font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ r.name }}</p>
                  </td>
                  <td class="py-2 pr-3 text-right align-middle tabular-nums text-slate-600 dark:text-slate-400">
                    {{ r.permissions_count ?? 0 }}
                  </td>
                  <td class="relative py-2 pl-2 pr-4 text-right align-middle">
                    <details class="group/action-menu relative inline-block text-right">
                      <summary
                        class="inline-flex cursor-pointer list-none items-center justify-center rounded-lg border border-slate-200/90 bg-white p-1.5 text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 [&::-webkit-details-marker]:hidden dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:bg-slate-700 dark:hover:text-slate-100"
                        :class="saving ? 'pointer-events-none opacity-40' : ''"
                      >
                        <EllipsisVerticalIcon class="h-5 w-5" aria-hidden="true" />
                        <span class="sr-only">Thao tác</span>
                      </summary>
                      <div
                        class="absolute right-0 top-[calc(100%+6px)] z-[60] min-w-[12.5rem] rounded-xl border border-slate-200/90 bg-white py-1 text-left text-sm shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950/50"
                        @click.stop
                      >
                        <button
                          type="button"
                          class="flex w-full items-center gap-2 px-3 py-2 text-left text-slate-700 transition hover:bg-slate-50 disabled:opacity-40 dark:text-slate-200 dark:hover:bg-slate-800"
                          :disabled="saving"
                          @click="closeRowActionMenuThen(() => openEdit(r))"
                        >
                          <PencilSquareIcon class="h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" aria-hidden="true" />
                          Sửa
                        </button>
                        <button
                          type="button"
                          class="flex w-full items-center gap-2 px-3 py-2 text-left text-red-700 transition hover:bg-red-50 disabled:opacity-40 dark:text-red-400 dark:hover:bg-red-950/40"
                          :disabled="r.name === 'superadmin' || saving"
                          @click="closeRowActionMenuThen(() => remove(r))"
                        >
                          <TrashIcon class="h-4 w-4 shrink-0 text-red-600 dark:text-red-400" aria-hidden="true" />
                          Xóa
                        </button>
                      </div>
                    </details>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </Card>

    <div
      v-if="createModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="system-roles-create-title"
      @click.self="closeCreateModal"
    >
      <Card class="max-h-[90vh] w-full max-w-lg overflow-y-auto shadow-xl">
        <h2 id="system-roles-create-title" class="mb-4 text-sm font-semibold text-slate-900 dark:text-slate-100">Thêm vai trò</h2>
        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="create">
          <Select v-model="rolePreset" label="Mẫu có sẵn" placeholder="— Không dùng mẫu —" class="sm:col-span-2">
            <option value="">— Không dùng mẫu —</option>
            <option v-for="r in seedRoles" :key="r.name" :value="r.name">{{ r.name }} — {{ r.display_name }}</option>
          </Select>
          <Input v-model="form.name" label="Mã vai trò" placeholder="vd. dispatcher" required />
          <Input v-model="form.display_name" label="Tên hiển thị" placeholder="vd. Điều vận" />
          <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 dark:border-slate-800 sm:col-span-2 sm:flex-row sm:justify-end">
            <Button variant="secondary" type="button" :disabled="saving" @click="closeCreateModal">Huỷ</Button>
            <Button type="submit" :loading="saving" :disabled="saving">Thêm</Button>
          </div>
        </form>
      </Card>
    </div>

    <div
      v-if="editing"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      role="dialog"
      aria-modal="true"
      @click.self="editing = null"
    >
      <Card class="max-h-[90vh] w-full max-w-lg overflow-y-auto" :title="`Sửa vai trò: ${editing.name}`">
        <div class="space-y-4">
          <Input v-model="editForm.name" label="Mã vai trò" />
          <Input v-model="editForm.display_name" label="Tên hiển thị" />
          <div>
            <p class="mb-2 text-sm font-medium text-slate-800 dark:text-slate-200">Quyền</p>
            <div class="flex max-h-[min(50vh,18rem)] flex-wrap gap-2 overflow-y-auto rounded-xl border border-slate-200/90 bg-slate-50/70 p-2 dark:border-slate-700 dark:bg-slate-900/35">
              <button
                v-for="p in sortedPermsForEdit"
                :key="p.id"
                type="button"
                :aria-pressed="isEditPermSelected(p.id)"
                class="rounded-full border px-2.5 py-1 text-xs font-mono outline-none ring-teal-500/30 transition focus-visible:ring-2"
                :class="
                  isEditPermSelected(p.id)
                    ? 'border-teal-500 bg-teal-50 text-teal-900 shadow-sm shadow-teal-500/15 dark:border-teal-400/70 dark:bg-teal-950/45 dark:text-teal-50'
                    : 'border-slate-200 bg-white text-slate-500 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800/70 dark:text-slate-400 dark:hover:bg-slate-800'
                "
                @click="toggleEditPermission(p.id)"
              >
                {{ p.name }}
              </button>
            </div>
          </div>
          <div class="flex justify-end gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
            <Button variant="secondary" type="button" :disabled="saving" @click="editing = null">Đóng</Button>
            <Button :loading="saving" :disabled="saving" @click="saveEdit">Lưu thay đổi</Button>
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>
