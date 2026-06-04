<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import {
  ChevronDownIcon,
  MagnifyingGlassIcon,
  PlusIcon,
  TrashIcon,
  XMarkIcon,
  LinkIcon,
} from '@heroicons/vue/24/outline'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { getFeatureToggleNavClusters } from '../../config/nav'
import { SEED_FEATURE_TOGGLE_PRESETS } from '../../config/systemSeedOptions'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import { debounceTrailing } from '../../composables/useDebounce'
import { useAuthStore } from '../../store'

// ─── Nav clusters: featureKey → links ─────────────────────────────────────────
// Tính một lần, dùng per-row khi expand
const navKeyMap = computed(() => {
  const m = new Map()
  for (const cluster of getFeatureToggleNavClusters()) {
    for (const feat of cluster.features) {
      if (!m.has(feat.featureKey)) m.set(feat.featureKey, [])
      for (const link of feat.links) {
        m.get(feat.featureKey).push(link)
      }
    }
  }
  return m
})

function navLinksForKey(key) {
  return navKeyMap.value.get(key) ?? []
}

// ─── State ────────────────────────────────────────────────────────────────────

const auth = useAuthStore()

const loading    = ref(true)
const saving     = ref(false)
const items      = ref([])
const expandedId = ref(null)  // id của row đang expand nav panel

const searchRaw  = ref('')
const searchQ    = ref('')
const filterStatus = ref('all')

const addModalOpen = ref(false)
const presetIdx    = ref('')
const form = reactive({ key: '', name: '', module: '', is_enabled: true, maintenance_mode: false, upgrade_notice: false })

const STATUS_OPTS = [
  { value: 'all',         label: 'Tất cả' },
  { value: 'on',          label: 'Đang bật' },
  { value: 'off',         label: 'Đang tắt' },
  { value: 'maintenance', label: 'Đang bảo trì' },
  { value: 'upgrade',     label: 'Có thông báo nâng cấp' },
]

// ─── Computed ─────────────────────────────────────────────────────────────────

const bumpSearch = debounceTrailing(() => { searchQ.value = searchRaw.value }, 300)
watch(searchRaw, () => bumpSearch())

const filteredItems = computed(() => {
  let list = items.value

  switch (filterStatus.value) {
    case 'on':          list = list.filter((r) => r.is_enabled); break
    case 'off':         list = list.filter((r) => !r.is_enabled); break
    case 'maintenance': list = list.filter((r) => r.maintenance_mode); break
    case 'upgrade':     list = list.filter((r) => r.upgrade_notice); break
  }

  const q = searchQ.value.trim().toLowerCase()
  if (q) {
    list = list.filter((r) =>
      (r.name ?? '').toLowerCase().includes(q) ||
      (r.key ?? '').toLowerCase().includes(q) ||
      (r.module ?? '').toLowerCase().includes(q),
    )
  }

  return list
})

const activeFilters = computed(() => {
  let n = 0
  if (searchRaw.value.trim()) n++
  if (filterStatus.value !== 'all') n++
  return n
})

// ─── Sync session sau mutation ────────────────────────────────────────────────

async function syncSession() {
  if (!auth.isLoggedIn) return
  try { await auth.fetchMe() } catch { /* ignore */ }
}

// ─── API ──────────────────────────────────────────────────────────────────────

async function load() {
  loading.value = true
  try {
    const list = (await admin.listFeatureToggles()) ?? []
    items.value = list.map((r) => ({
      ...r,
      maintenance_mode: !!r.maintenance_mode,
      upgrade_notice:   !!r.upgrade_notice,
    }))
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

async function patchToggle(row, partial) {
  if (saving.value) return
  saving.value = true
  try {
    await admin.updateFeatureToggle(row.id, partial)
    Object.assign(row, partial)
    await syncSession()
  } catch (e) {
    showAppError(formatApiError(e))
    await load()
  } finally {
    saving.value = false
  }
}

async function confirmDelete(row) {
  const ok = await confirmAction({
    title:        'Xóa tính năng?',
    message:      `Xóa tính năng «${row.name}» (${row.key})?\nCác menu liên quan sẽ bị ảnh hưởng ngay lập tức.`,
    confirmLabel: 'Xóa',
    danger:       true,
  })
  if (!ok) return
  saving.value = true
  try {
    await admin.deleteFeatureToggle(row.id)
    await load()
    await syncSession()
    showAppSuccess('Đã xóa tính năng.')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

// ─── Create ───────────────────────────────────────────────────────────────────

watch(presetIdx, (v) => {
  if (v === '' || v == null) return
  const row = SEED_FEATURE_TOGGLE_PRESETS[Number(v)]
  if (row) {
    form.key    = row.key
    form.name   = row.name
    form.module = row.module ?? ''
  }
})

function openAdd() {
  presetIdx.value          = ''
  form.key                 = ''
  form.name                = ''
  form.module              = ''
  form.is_enabled          = true
  form.maintenance_mode    = false
  form.upgrade_notice      = false
  addModalOpen.value       = true
}

async function submitAdd() {
  if (!form.key.trim() || !form.name.trim()) return
  saving.value = true
  try {
    await admin.createFeatureToggle({
      key:              form.key.trim(),
      name:             form.name.trim(),
      module:           form.module.trim() || null,
      is_enabled:       !!form.is_enabled,
      maintenance_mode: !!form.maintenance_mode,
      upgrade_notice:   !!form.upgrade_notice,
    })
    addModalOpen.value = false
    await load()
    await syncSession()
    showAppSuccess('Đã thêm tính năng mới.')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    saving.value = false
  }
}

function resetFilters() {
  searchRaw.value    = ''
  searchQ.value      = ''
  filterStatus.value = 'all'
}

function toggleExpand(id) {
  expandedId.value = expandedId.value === id ? null : id
}

onMounted(load)
</script>

<template>
  <div class="mx-auto max-w-6xl space-y-5 pb-8">

    <!-- ── Header ─────────────────────────────────────────────────────────── -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-lg font-bold text-slate-900 dark:text-slate-50">Tính năng hệ thống</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
          Bật / tắt các tính năng và quản lý chế độ bảo trì, thông báo nâng cấp.
        </p>
      </div>
      <Button type="button" class="shrink-0 gap-1.5" :disabled="loading || saving" @click="openAdd">
        <PlusIcon class="h-4 w-4" aria-hidden="true" />
        Thêm tính năng
      </Button>
    </div>

    <!-- ── Filter bar ─────────────────────────────────────────────────────── -->
    <div class="flex flex-wrap items-center gap-2">
      <div class="relative">
        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
        <input
          v-model="searchRaw"
          type="search"
          placeholder="Tìm tính năng…"
          aria-label="Tìm tính năng"
          class="h-9 w-44 rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 sm:w-52"
        />
      </div>

      <select
        v-model="filterStatus"
        aria-label="Lọc theo trạng thái"
        class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
      >
        <option v-for="opt in STATUS_OPTS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>

      <button
        v-if="activeFilters > 0"
        type="button"
        class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-500 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
        @click="resetFilters"
      >
        <XMarkIcon class="h-4 w-4" aria-hidden="true" />
        Xóa bộ lọc
      </button>
    </div>

    <!-- ── Loading ────────────────────────────────────────────────────────── -->
    <div v-if="loading" class="space-y-2">
      <div v-for="i in 6" :key="i" class="h-14 animate-pulse rounded-xl border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-800" />
    </div>

    <!-- ── Empty ─────────────────────────────────────────────────────────── -->
    <template v-else-if="!items.length">
      <Card>
        <div class="py-12 text-center">
          <p class="text-sm font-medium text-slate-600 dark:text-slate-400">Chưa có tính năng nào.</p>
          <Button class="mt-4" @click="openAdd">Thêm tính năng đầu tiên</Button>
        </div>
      </Card>
    </template>

    <template v-else>
      <div
        v-if="filteredItems.length === 0"
        class="rounded-xl border border-dashed border-amber-200/80 bg-amber-50/40 py-10 text-center text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200"
      >
        Không tìm thấy tính năng nào khớp bộ lọc.
      </div>

      <!-- Desktop table -->
      <div v-else class="overflow-hidden rounded-xl border border-slate-200/90 shadow-sm dark:border-slate-700">
        <div class="hidden md:block">
          <table class="w-full border-collapse text-left text-sm">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50/95 text-slate-500 dark:border-slate-700 dark:bg-slate-800/80">
                <th class="py-2.5 pl-4 pr-3 text-xs font-semibold uppercase tracking-wide">Tên tính năng</th>
                <th class="py-2.5 pr-3 text-xs font-semibold uppercase tracking-wide">Khoá (Key)</th>
                <th class="py-2.5 pr-3 text-xs font-semibold uppercase tracking-wide">Nhóm</th>
                <th class="w-24 py-2.5 px-2 text-center text-xs font-semibold uppercase tracking-wide text-teal-600 dark:text-teal-400">Bật</th>
                <th class="w-28 py-2.5 px-2 text-center text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">Bảo trì</th>
                <th class="w-28 py-2.5 px-2 text-center text-xs font-semibold uppercase tracking-wide text-violet-600 dark:text-violet-400">Nâng cấp</th>
                <th class="w-10 py-2.5 px-2 text-right text-xs font-semibold uppercase tracking-wide">Nav</th>
                <th class="py-2.5 pl-2 pr-4 text-right text-xs font-semibold uppercase tracking-wide">Xoá</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="row in filteredItems" :key="row.id">
                <tr
                  class="border-b border-slate-100 transition-colors hover:bg-slate-50/60 dark:border-slate-800 dark:hover:bg-slate-800/30"
                  :class="{ 'bg-slate-50/30 dark:bg-slate-900/30': !row.is_enabled }"
                >
                  <!-- Tên -->
                  <td class="py-3 pl-4 pr-3 align-middle">
                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ row.name }}</span>
                    <div class="mt-0.5 flex gap-1">
                      <span v-if="row.maintenance_mode" class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">Bảo trì</span>
                      <span v-if="row.upgrade_notice" class="rounded-full bg-violet-100 px-1.5 py-0.5 text-[10px] font-semibold text-violet-700 dark:bg-violet-950/60 dark:text-violet-300">Nâng cấp</span>
                    </div>
                  </td>

                  <!-- Key -->
                  <td class="max-w-[14rem] py-3 pr-3 align-middle">
                    <span class="break-all font-mono text-xs text-slate-500 dark:text-slate-400">{{ row.key }}</span>
                  </td>

                  <!-- Module -->
                  <td class="py-3 pr-3 align-middle text-sm text-slate-500 dark:text-slate-400">{{ row.module ?? '—' }}</td>

                  <!-- Toggle: Bật -->
                  <td class="py-3 px-2 text-center align-middle">
                    <button
                      type="button"
                      role="switch"
                      :aria-checked="row.is_enabled"
                      :disabled="saving"
                      class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-slate-900"
                      :class="row.is_enabled ? 'bg-teal-500' : 'bg-slate-200 dark:bg-slate-700'"
                      @click="patchToggle(row, { is_enabled: !row.is_enabled })"
                    >
                      <span
                        class="inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                        :class="row.is_enabled ? 'translate-x-5' : 'translate-x-0'"
                      />
                    </button>
                  </td>

                  <!-- Toggle: Bảo trì -->
                  <td class="py-3 px-2 text-center align-middle">
                    <button
                      type="button"
                      role="switch"
                      :aria-checked="row.maintenance_mode"
                      :disabled="saving"
                      class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-slate-900"
                      :class="row.maintenance_mode ? 'bg-amber-500' : 'bg-slate-200 dark:bg-slate-700'"
                      @click="patchToggle(row, { maintenance_mode: !row.maintenance_mode })"
                    >
                      <span
                        class="inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                        :class="row.maintenance_mode ? 'translate-x-5' : 'translate-x-0'"
                      />
                    </button>
                  </td>

                  <!-- Toggle: Nâng cấp -->
                  <td class="py-3 px-2 text-center align-middle">
                    <button
                      type="button"
                      role="switch"
                      :aria-checked="row.upgrade_notice"
                      :disabled="saving"
                      class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-slate-900"
                      :class="row.upgrade_notice ? 'bg-violet-500' : 'bg-slate-200 dark:bg-slate-700'"
                      @click="patchToggle(row, { upgrade_notice: !row.upgrade_notice })"
                    >
                      <span
                        class="inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                        :class="row.upgrade_notice ? 'translate-x-5' : 'translate-x-0'"
                      />
                    </button>
                  </td>

                  <!-- Nav expand -->
                  <td class="py-3 px-2 text-right align-middle">
                    <button
                      v-if="navLinksForKey(row.key).length > 0"
                      type="button"
                      :title="expandedId === row.id ? 'Ẩn nav' : 'Xem nav bị ảnh hưởng'"
                      class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-teal-600 dark:hover:bg-slate-800 dark:hover:text-teal-400"
                      @click="toggleExpand(row.id)"
                    >
                      <ChevronDownIcon
                        class="h-4 w-4 transition-transform duration-200"
                        :class="{ '-rotate-180': expandedId === row.id }"
                        aria-hidden="true"
                      />
                    </button>
                  </td>

                  <!-- Delete -->
                  <td class="py-3 pl-2 pr-4 text-right align-middle">
                    <button
                      type="button"
                      title="Xóa"
                      class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600 disabled:opacity-40 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                      :disabled="saving"
                      @click="confirmDelete(row)"
                    >
                      <TrashIcon class="h-4 w-4" aria-hidden="true" />
                    </button>
                  </td>
                </tr>

                <!-- Nav affected panel (expand) -->
                <tr v-if="expandedId === row.id" :key="'nav-' + row.id">
                  <td colspan="8" class="border-b border-slate-100 bg-slate-50/70 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/40">
                    <div class="flex items-start gap-2">
                      <LinkIcon class="mt-0.5 h-4 w-4 shrink-0 text-teal-500" aria-hidden="true" />
                      <div>
                        <p class="mb-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400">MỤC MENU BỊ ẢNH HƯỞNG KHI TẮT TÍNH NĂNG NÀY</p>
                        <div class="flex flex-wrap gap-1.5">
                          <span
                            v-for="link in navLinksForKey(row.key)"
                            :key="link.to"
                            class="rounded-lg border border-teal-200/60 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 dark:border-teal-800/40 dark:bg-slate-900 dark:text-slate-300"
                          >
                            {{ link.labelKey.replace(/^nav\./, '') }} <span class="ml-1 font-mono text-[10px] text-slate-400">{{ link.to }}</span>
                          </span>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Mobile cards -->
        <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
          <div v-for="row in filteredItems" :key="'m' + row.id" class="bg-white p-4 dark:bg-slate-900/60">
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ row.name }}</p>
                <p class="mt-0.5 break-all font-mono text-xs text-slate-400 dark:text-slate-500">{{ row.key }}</p>
                <p v-if="row.module" class="mt-0.5 text-xs text-slate-500">{{ row.module }}</p>
                <div class="mt-1 flex gap-1">
                  <span v-if="row.maintenance_mode" class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/60 dark:text-amber-300">Bảo trì</span>
                  <span v-if="row.upgrade_notice" class="rounded-full bg-violet-100 px-1.5 py-0.5 text-[10px] font-semibold text-violet-700 dark:bg-violet-950/60 dark:text-violet-300">Nâng cấp</span>
                </div>
              </div>
              <button
                type="button"
                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600 disabled:opacity-40"
                :disabled="saving"
                @click="confirmDelete(row)"
              >
                <TrashIcon class="h-4 w-4" aria-hidden="true" />
              </button>
            </div>

            <div class="mt-4 grid grid-cols-3 gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
              <div class="flex flex-col items-center gap-1.5">
                <span class="text-xs font-medium text-teal-600 dark:text-teal-400">Bật</span>
                <button
                  type="button"
                  role="switch"
                  :aria-checked="row.is_enabled"
                  :disabled="saving"
                  class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors disabled:opacity-50"
                  :class="row.is_enabled ? 'bg-teal-500' : 'bg-slate-200 dark:bg-slate-700'"
                  @click="patchToggle(row, { is_enabled: !row.is_enabled })"
                >
                  <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="row.is_enabled ? 'translate-x-5' : 'translate-x-0'" />
                </button>
              </div>
              <div class="flex flex-col items-center gap-1.5">
                <span class="text-xs font-medium text-amber-600 dark:text-amber-400">Bảo trì</span>
                <button
                  type="button"
                  role="switch"
                  :aria-checked="row.maintenance_mode"
                  :disabled="saving"
                  class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors disabled:opacity-50"
                  :class="row.maintenance_mode ? 'bg-amber-500' : 'bg-slate-200 dark:bg-slate-700'"
                  @click="patchToggle(row, { maintenance_mode: !row.maintenance_mode })"
                >
                  <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="row.maintenance_mode ? 'translate-x-5' : 'translate-x-0'" />
                </button>
              </div>
              <div class="flex flex-col items-center gap-1.5">
                <span class="text-xs font-medium text-violet-600 dark:text-violet-400">Nâng cấp</span>
                <button
                  type="button"
                  role="switch"
                  :aria-checked="row.upgrade_notice"
                  :disabled="saving"
                  class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors disabled:opacity-50"
                  :class="row.upgrade_notice ? 'bg-violet-500' : 'bg-slate-200 dark:bg-slate-700'"
                  @click="patchToggle(row, { upgrade_notice: !row.upgrade_notice })"
                >
                  <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="row.upgrade_notice ? 'translate-x-5' : 'translate-x-0'" />
                </button>
              </div>
            </div>

            <!-- Nav affected (mobile) -->
            <div v-if="navLinksForKey(row.key).length" class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-800">
              <button type="button" class="flex items-center gap-1 text-xs font-medium text-teal-600 dark:text-teal-400" @click="toggleExpand(row.id)">
                <LinkIcon class="h-3.5 w-3.5" aria-hidden="true" />
                {{ expandedId === row.id ? 'Ẩn nav' : `${navLinksForKey(row.key).length} mục menu bị ảnh hưởng` }}
              </button>
              <div v-if="expandedId === row.id" class="mt-2 flex flex-wrap gap-1.5">
                <span
                  v-for="link in navLinksForKey(row.key)"
                  :key="link.to"
                  class="rounded-lg border border-teal-200/60 bg-slate-50 px-2 py-1 text-xs text-slate-600 dark:border-teal-800/40 dark:bg-slate-800 dark:text-slate-300"
                >
                  {{ link.labelKey.replace(/^nav\./, '') }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ── Add modal ─────────────────────────────────────────────────────── -->
    <div
      v-if="addModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="feature-add-title"
      @click.self="addModalOpen = false"
    >
      <Card class="max-h-[90vh] w-full max-w-lg overflow-y-auto shadow-xl">
        <div class="mb-4 flex items-center justify-between">
          <h2 id="feature-add-title" class="text-sm font-semibold text-slate-900 dark:text-slate-100">Thêm tính năng mới</h2>
          <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800" @click="addModalOpen = false">
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <form class="space-y-4" @submit.prevent="submitAdd">
          <Select v-model="presetIdx" label="Chọn từ mẫu có sẵn">
            <option value="">— Không dùng mẫu —</option>
            <option v-for="(row, i) in SEED_FEATURE_TOGGLE_PRESETS" :key="row.key" :value="String(i)">
              {{ row.name }} ({{ row.key }})
            </option>
          </Select>

          <div class="grid gap-4 sm:grid-cols-2">
            <Input v-model="form.key" label="Khoá (Key) *" placeholder="vd. module.reports" required />
            <Input v-model="form.name" label="Tên hiển thị *" placeholder="vd. Báo cáo" required />
          </div>

          <Input v-model="form.module" label="Nhóm (tùy chọn)" placeholder="vd. reports" />

          <!-- Toggle defaults -->
          <div class="space-y-2 rounded-lg border border-slate-200 p-3 dark:border-slate-700">
            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">TRẠNG THÁI MẶC ĐỊNH</p>
            <label class="flex cursor-pointer items-center justify-between gap-2 text-sm text-slate-700 dark:text-slate-300">
              <span>Bật tính năng</span>
              <button
                type="button"
                role="switch"
                :aria-checked="form.is_enabled"
                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full border-2 border-transparent transition-colors"
                :class="form.is_enabled ? 'bg-teal-500' : 'bg-slate-200 dark:bg-slate-700'"
                @click="form.is_enabled = !form.is_enabled"
              >
                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="form.is_enabled ? 'translate-x-5' : 'translate-x-0'" />
              </button>
            </label>
            <label class="flex cursor-pointer items-center justify-between gap-2 text-sm text-slate-700 dark:text-slate-300">
              <span>Chế độ bảo trì</span>
              <button
                type="button"
                role="switch"
                :aria-checked="form.maintenance_mode"
                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full border-2 border-transparent transition-colors"
                :class="form.maintenance_mode ? 'bg-amber-500' : 'bg-slate-200 dark:bg-slate-700'"
                @click="form.maintenance_mode = !form.maintenance_mode"
              >
                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="form.maintenance_mode ? 'translate-x-5' : 'translate-x-0'" />
              </button>
            </label>
            <label class="flex cursor-pointer items-center justify-between gap-2 text-sm text-slate-700 dark:text-slate-300">
              <span>Thông báo nâng cấp</span>
              <button
                type="button"
                role="switch"
                :aria-checked="form.upgrade_notice"
                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full border-2 border-transparent transition-colors"
                :class="form.upgrade_notice ? 'bg-violet-500' : 'bg-slate-200 dark:bg-slate-700'"
                @click="form.upgrade_notice = !form.upgrade_notice"
              >
                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="form.upgrade_notice ? 'translate-x-5' : 'translate-x-0'" />
              </button>
            </label>
          </div>

          <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 dark:border-slate-800 sm:flex-row sm:justify-end">
            <Button variant="secondary" type="button" :disabled="saving" @click="addModalOpen = false">Huỷ</Button>
            <Button type="submit" :loading="saving" :disabled="saving || !form.key.trim() || !form.name.trim()">Thêm tính năng</Button>
          </div>
        </form>
      </Card>
    </div>

  </div>
</template>
