<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { FunnelIcon, ChevronDownIcon, PencilSquareIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import AppRowActionsMenu from '../../components/ui/AppRowActionsMenu.vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { SEED_PERMISSION_PRESETS } from '../../config/systemSeedOptions'
import permissionPlainVi from '../../data/permission_plain_vi.json'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import { debounceTrailing } from '../../composables/useDebounce'

const PERMISSIONS_FILTER_VIS_KEY = 'va.permissions.filter_control_visibility_v1'
const FILTER_CONTROL_IDS = ['search', 'role', 'status']

function defaultFilterControlVisibility() {
  return FILTER_CONTROL_IDS.reduce((acc, id) => {
    acc[id] = true
    return acc
  }, {})
}

const loading = ref(true)
const saving = ref(false)
const items = ref([])
const editing = ref(null)
const form = reactive({ name: '', display_name: '', plain_description: '' })
const editForm = reactive({ name: '', display_name: '', plain_description: '' })
const seedPermissions = SEED_PERMISSION_PRESETS
const permissionPreset = ref('')

const rolesBrief = ref([])
const permissionIdsByRoleId = ref(new Map())
const roleFacetsReady = ref(false)

const funnelDetailsRef = ref(null)
const filterControlVisible = reactive(defaultFilterControlVisibility())
const createModalOpen = ref(false)

const searchInput = ref('')
const filterQ = ref('')
const filterRoleId = ref('')
const filterStatus = ref('all')

const STATUS_OPTIONS = Object.freeze([
  { value: 'all', label: 'Tất cả' },
  { value: 'labeled', label: 'Có nhãn hiển thị' },
  { value: 'unlabeled', label: 'Chưa có nhãn' },
  { value: 'note_own', label: 'Có mô tả riêng' },
  { value: 'note_default', label: 'Chưa mô tả riêng' },
])

const filterControlDefs = Object.freeze([
  { id: 'search', label: 'Tìm trong danh sách' },
  { id: 'role', label: 'Theo vai trò' },
  { id: 'status', label: 'Trạng thái' },
])

const bumpSearchDebounced = debounceTrailing(() => {
  filterQ.value = searchInput.value
}, 300)

watch(searchInput, () => bumpSearchDebounced())

const statusFilterLabel = computed(
  () => STATUS_OPTIONS.find((x) => x.value === filterStatus.value)?.label ?? 'Tất cả',
)

const roleChipSummaryText = computed(() => {
  if (!roleFacetsReady.value) return 'Đang tải…'
  if (!filterRoleId.value) return 'Tất cả'
  const r = rolesBrief.value.find((x) => String(x.id) === String(filterRoleId.value))
  if (!r) return '—'
  const dn = r.display_name ?? ''
  return dn ? `${r.name} — ${dn}` : r.name
})

const filteredItems = computed(() => {
  const qRaw = filterQ.value.trim().toLowerCase()

  let list = items.value
  const roleIdSel = filterRoleId.value

  if (roleIdSel && roleFacetsReady.value) {
    const idNum = Number(roleIdSel)
    const set = permissionIdsByRoleId.value.get(idNum)
    list = list.filter((p) => set && set.has(p.id))
  }

  list = list.filter((p) => {
    switch (filterStatus.value) {
      case 'labeled':
        return !!(p.display_name && String(p.display_name).trim())
      case 'unlabeled':
        return !(p.display_name && String(p.display_name).trim())
      case 'note_own':
        return !!(p.plain_description && String(p.plain_description).trim())
      case 'note_default':
        return !(p.plain_description && String(p.plain_description).trim())
      default:
        return true
    }
  })

  if (!qRaw) return list
  return list.filter((p) => {
    const name = (p.name ?? '').toLowerCase()
    const dn = (p.display_name ?? '').toLowerCase()
    const sum = (p.plain_summary ?? '').toLowerCase()
    return name.includes(qRaw) || dn.includes(qRaw) || sum.includes(qRaw)
  })
})

const activeFilterCount = computed(() => {
  let n = 0
  if (searchInput.value.trim()) n++
  if (filterRoleId.value) n++
  if (filterStatus.value !== 'all') n++
  return n
})

function loadFilterControlVisibility() {
  try {
    const raw = localStorage.getItem(PERMISSIONS_FILTER_VIS_KEY)
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
      localStorage.setItem(PERMISSIONS_FILTER_VIS_KEY, JSON.stringify({ ...filterControlVisible }))
    } catch {
      /* ignore */
    }
  },
  { deep: true },
)

async function hydrateRoleFacets() {
  roleFacetsReady.value = false
  permissionIdsByRoleId.value = new Map()
  try {
    const brief = (await admin.listRoles()) ?? []
    rolesBrief.value = brief
    const pairs = await Promise.all(
      brief.map(async (r) => {
        try {
          const d = await admin.getRole(r.id)
          const ids = new Set((d.permissions ?? []).map((p) => p.id))
          return [r.id, ids]
        } catch {
          return [r.id, new Set()]
        }
      }),
    )
    permissionIdsByRoleId.value = new Map(pairs)
  } catch {
    rolesBrief.value = []
  } finally {
    roleFacetsReady.value = true
  }
}

function closeParentDetails(ev) {
  const el = ev.currentTarget
  if (!el?.closest) return
  const d = el.closest('details')
  if (d) d.open = false
}

function closeFunnelMenu() {
  const el = funnelDetailsRef.value
  if (el) el.open = false
}

function resetFilters() {
  searchInput.value = ''
  filterQ.value = ''
  filterRoleId.value = ''
  filterStatus.value = 'all'
  closeFunnelMenu()
}

watch(permissionPreset, (v) => {
  if (!v) return
  const p = seedPermissions.find((x) => x.name === v)
  if (p) {
    form.name = p.name
    form.display_name = p.display_name
    form.plain_description = permissionPlainVi[v] ?? ''
  }
})

async function load() {
  loading.value = true
  try {
    items.value = (await admin.listPermissions()) ?? []
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
  void hydrateRoleFacets()
}

async function create() {
  if (!form.name.trim()) return
  saving.value = true
  try {
    await admin.createPermission({
      name: form.name.trim(),
      display_name: form.display_name?.trim() || null,
      plain_description: form.plain_description?.trim() || null,
    })
    form.name = ''
    form.display_name = ''
    form.plain_description = ''
    permissionPreset.value = ''
    await load()
    showAppSuccess('Đã thêm quyền mới.', 'Thành công')
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

function openEdit(p) {
  editing.value = p
  editForm.name = p.name
  editForm.display_name = p.display_name ?? ''
  editForm.plain_description = p.plain_description ?? ''
}

async function saveEdit() {
  if (!editing.value) return
  saving.value = true
  try {
    await admin.updatePermission(editing.value.id, {
      name: editForm.name.trim(),
      display_name: editForm.display_name?.trim() || null,
      plain_description: editForm.plain_description?.trim() || null,
    })
    editing.value = null
    await load()
    showAppSuccess('Đã lưu thay đổi quyền.', 'Thành công')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

async function remove(p) {
  const ok = await confirmAction({
    title: 'Xóa quyền?',
    message: `Xóa quyền «${p.name}»? Các vai trò đang dùng quyền này có thể cần chỉnh lại.`,
    confirmLabel: 'Xóa',
    danger: true,
  })
  if (!ok) return
  try {
    await admin.deletePermission(p.id)
    await load()
    showAppSuccess('Đã xóa quyền.', 'Thành công')
  } catch (e) {
    showAppError(formatApiError(e))
  }
}

function setRoleFilter(ev, idVal) {
  filterRoleId.value = idVal
  closeParentDetails(ev)
}

function setStatusFilter(ev, val) {
  filterStatus.value = val
  closeParentDetails(ev)
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
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Danh sách quyền</h2>
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
                    <li v-if="filterRoleId" class="flex justify-between gap-2">
                      <span class="text-slate-500 dark:text-slate-400">Vai trò</span>
                      <span class="max-w-[12rem] truncate text-right font-medium">
                        {{ roleChipSummaryText }}
                      </span>
                    </li>
                    <li v-if="filterStatus !== 'all'" class="flex justify-between gap-2">
                      <span class="text-slate-500 dark:text-slate-400">Trạng thái</span>
                      <span class="font-medium">{{ statusFilterLabel }}</span>
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
                          :id="`permissions-filter-vis-${fd.id}`"
                          v-model="filterControlVisible[fd.id]"
                          type="checkbox"
                          class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                        />
                        <label
                          :for="`permissions-filter-vis-${fd.id}`"
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
                v-if="filterControlVisible.role"
                root-class="shrink-0"
                label="Vai trò"
                :summary-text="roleChipSummaryText"
                summary-text-class="max-w-[10rem]"
                panel-class="max-h-[min(60vh,320px)] min-w-[220px] overflow-hidden py-1"
              >
                <ul class="max-h-[min(50vh,280px)] space-y-0.5 overflow-y-auto px-1 py-1">
                  <li>
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition disabled:opacity-50"
                      :disabled="!roleFacetsReady"
                      :class="
                        !filterRoleId
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                      "
                      @click="setRoleFilter($event, '')"
                    >
                      Tất cả
                    </button>
                  </li>
                  <li v-for="r in rolesBrief" :key="r.id">
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition disabled:opacity-50"
                      :disabled="!roleFacetsReady"
                      :class="
                        String(filterRoleId) === String(r.id)
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                      "
                      @click="setRoleFilter($event, String(r.id))"
                    >
                      {{ r.name }} — {{ r.display_name ?? '—' }}
                    </button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <AppFilterDropdown
                v-if="filterControlVisible.status"
                root-class="shrink-0"
                label="Trạng thái"
                :summary-text="statusFilterLabel"
                summary-text-class="max-w-[10rem]"
                panel-class="min-w-[220px] py-1"
              >
                <ul class="space-y-0.5 px-1 py-1">
                  <li v-for="opt in STATUS_OPTIONS" :key="opt.value">
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="
                        filterStatus === opt.value
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                      "
                      @click="setStatusFilter($event, opt.value)"
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
                aria-label="Tìm trong trang danh sách quyền"
                placeholder="Tìm mã, nhãn hoặc mô tả…"
                title="Tìm trong trang danh sách quyền"
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
          <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Chưa có quyền nào.</p>
        </div>
      </template>

      <template v-else>
        <div
          v-if="filteredItems.length === 0"
          class="rounded-xl border border-dashed border-amber-200/80 bg-amber-50/40 py-10 text-center text-sm text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200/90"
        >
          Không có quyền khớp bộ lọc.
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-slate-200/90 shadow-sm dark:border-slate-700">
          <div class="overflow-x-auto">
            <table class="w-full min-w-[40rem] border-collapse text-left text-sm">
              <thead>
                <tr
                  class="border-b border-slate-200 bg-slate-50/95 text-slate-600 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-300"
                >
                  <th class="whitespace-nowrap py-2.5 pl-4 pr-3 text-xs font-semibold uppercase tracking-wide">Quyền</th>
                  <th class="whitespace-nowrap py-2.5 pl-2 pr-4 text-right text-xs font-semibold uppercase tracking-wide tabular-nums">
                    Thao tác
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(p, pi) in filteredItems"
                  :key="p.id"
                  class="border-b border-slate-100 transition-colors hover:bg-slate-50/80 dark:border-slate-800 dark:hover:bg-slate-800/40"
                  :class="pi % 2 === 1 ? 'bg-white dark:bg-transparent' : 'bg-slate-50/30 dark:bg-slate-900/40'"
                >
                  <td class="max-w-xl py-2.5 pl-4 pr-3 align-middle">
                    <p class="font-semibold leading-snug text-slate-900 dark:text-slate-100">
                      {{ p.display_name?.trim() || p.name }}
                    </p>
                    <p class="mt-0.5 font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ p.name }}</p>
                    <p class="mt-1 text-xs leading-snug text-slate-500 dark:text-slate-400">
                      {{ p.plain_summary ?? '—' }}
                    </p>
                  </td>
                  <td class="relative py-2.5 pl-2 pr-4 text-right align-middle">
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
                        @click="openEdit(p)"
                      >
                        <PencilSquareIcon class="h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" aria-hidden="true" />
                        Sửa
                      </button>
                      <button
                        type="button"
                        role="menuitem"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-red-700 transition hover:bg-red-50 disabled:opacity-40 dark:text-red-400 dark:hover:bg-red-950/40"
                        :disabled="saving"
                        @click="remove(p)"
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

    <div
      v-if="createModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="system-permissions-create-title"
      @click.self="closeCreateModal"
    >
      <Card class="max-h-[90vh] w-full max-w-lg overflow-y-auto shadow-xl">
        <h2 id="system-permissions-create-title" class="mb-4 text-sm font-semibold text-slate-900 dark:text-slate-100">
          Thêm quyền
        </h2>
        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="create">
          <Select v-model="permissionPreset" label="Mẫu có sẵn" placeholder="— Không dùng mẫu —" class="sm:col-span-2">
            <option value="">— Không dùng mẫu —</option>
            <option v-for="p in seedPermissions" :key="p.name" :value="p.name">{{ p.name }}</option>
          </Select>
          <Input v-model="form.name" label="Mã quyền" placeholder="vd. request.create" required />
          <Input v-model="form.display_name" label="Nhãn hiển thị" placeholder="vd. Tạo yêu cầu điều xe" />
          <label class="block sm:col-span-2">
            <span class="mb-1 block text-sm font-medium text-slate-800 dark:text-slate-200">Mô tả</span>
            <textarea
              v-model="form.plain_description"
              rows="3"
              class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
            />
          </label>
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
      <Card class="max-h-[90vh] w-full max-w-lg overflow-y-auto" :title="`Sửa quyền: ${editing.name}`">
        <div class="space-y-4">
          <Input v-model="editForm.name" label="Mã quyền" />
          <Input v-model="editForm.display_name" label="Nhãn hiển thị" />
          <label class="block">
            <span class="mb-1 block text-sm font-medium text-slate-800 dark:text-slate-200">Mô tả</span>
            <textarea
              v-model="editForm.plain_description"
              rows="3"
              class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring-2 focus:ring-teal-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
            />
          </label>
          <div class="flex justify-end gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
            <Button variant="secondary" type="button" :disabled="saving" @click="editing = null">
              Đóng
            </Button>
            <Button :loading="saving" :disabled="saving" @click="saveEdit">Lưu thay đổi</Button>
          </div>
        </div>
      </Card>
    </div>
  </div>
</template>
