<template>
  <div class="space-y-4 pb-10">

    <!-- ── Header ─────────────────────────────────────────────────────────── -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-lg font-bold text-slate-900 dark:text-slate-50">Nhật ký hoạt động</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Theo dõi ai đã làm gì trong hệ thống</p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
        :disabled="loading"
        @click="reload(true)"
      >
        <ArrowPathIcon class="h-4 w-4" :class="loading ? 'animate-spin' : ''" aria-hidden="true" />
        Làm mới
      </button>
    </div>

    <AppFilterBar>
      <div class="flex w-full flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
        <AppFilterFunnelMenu ref="filterMenuRef" :badge-count="activeFilterCount">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Bộ lọc đang áp dụng</p>
          <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
            <li v-if="activeCategory !== 'all'" class="flex justify-between gap-2">
              <span class="text-slate-500">Nhóm</span>
              <span class="font-medium">{{ activeCategoryLabel }}</span>
            </li>
            <li v-if="searchInput.trim()" class="flex justify-between gap-2">
              <span class="text-slate-500">Tìm kiếm</span>
              <span class="max-w-[60%] truncate text-right font-medium">{{ searchInput }}</span>
            </li>
            <li v-if="filters.from || filters.to" class="flex justify-between gap-2">
              <span class="text-slate-500">Khoảng ngày</span>
              <span class="font-medium tabular-nums">{{ dateRangeLabel }}</span>
            </li>
            <li v-if="activeFilterCount === 0" class="text-slate-400">Chưa có điều kiện lọc</li>
          </ul>
          <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">Hiển thị bộ lọc trên thanh</p>
            <ul class="mt-2 space-y-2">
              <li class="flex gap-2">
                <input id="audit-vis-category" v-model="filterBarVisible.category" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600" />
                <label for="audit-vis-category" class="text-sm">Nhóm sự kiện</label>
              </li>
              <li class="flex gap-2">
                <input id="audit-vis-search" v-model="filterBarVisible.search" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600" />
                <label for="audit-vis-search" class="text-sm">Tìm trong trang</label>
              </li>
              <li class="flex gap-2">
                <input id="audit-vis-dates" v-model="filterBarVisible.dates" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600" />
                <label for="audit-vis-dates" class="text-sm">Khoảng ngày</label>
              </li>
            </ul>
          </div>
          <button type="button" class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-medium dark:border-slate-600" @click="resetFilters(); closeFilterMenu()">
            Xóa tất cả bộ lọc
          </button>
        </AppFilterFunnelMenu>
        <div class="hidden h-6 w-px bg-slate-200 sm:block dark:bg-slate-700" aria-hidden="true" />
        <button type="button" class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500" title="Xóa lọc" @click="resetFilters">
          <span class="relative inline-flex">
            <FunnelIcon class="h-5 w-5" />
            <XMarkIcon class="absolute -right-0.5 -top-0.5 h-3 w-3 text-rose-500" />
          </span>
        </button>
      </div>
      <div v-if="hasVisibleBarFilters" class="mt-2 space-y-3 border-t border-violet-100/80 pt-2 dark:border-violet-900/30">
        <div v-if="filterBarVisible.category" class="flex flex-wrap gap-1.5">
          <button
            v-for="cat in CATEGORIES"
            :key="cat.key"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium transition"
            :class="activeCategory === cat.key
              ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900'
              : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'"
            @click="setCategory(cat.key)"
          >
            <span>{{ cat.icon }}</span>
            {{ cat.label }}
          </button>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <div v-if="filterBarVisible.search" class="relative min-w-0 flex-1">
            <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
            <input
              v-model="searchInput"
              type="search"
              placeholder="Tìm theo tên người, hành động…"
              class="h-9 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
            />
          </div>
          <template v-if="filterBarVisible.dates">
            <label class="flex items-center gap-2 text-sm">
              <span class="w-14 shrink-0 text-slate-500">Từ ngày</span>
              <input
                v-model="filters.from"
                type="date"
                class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-sm text-slate-900 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
            </label>
            <label class="flex items-center gap-2 text-sm">
              <span class="w-14 shrink-0 text-slate-500">Đến ngày</span>
              <input
                v-model="filters.to"
                type="date"
                class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-sm text-slate-900 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              />
            </label>
          </template>
        </div>
      </div>
    </AppFilterBar>

    <!-- ── Loading ────────────────────────────────────────────────────────── -->
    <div v-if="loading" class="space-y-2">
      <div v-for="i in 5" :key="i" class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
        <div class="h-9 w-9 shrink-0 animate-pulse rounded-full bg-slate-200 dark:bg-slate-700" />
        <div class="flex-1 space-y-2 pt-0.5">
          <div class="h-4 w-3/5 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
          <div class="h-3 w-2/5 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
        </div>
      </div>
    </div>

    <template v-else>
      <!-- ── Empty ──────────────────────────────────────────────────────────── -->
      <div
        v-if="!groupedItems.length"
        class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 py-16 text-center dark:border-slate-700"
      >
        <ClockIcon class="mb-3 h-12 w-12 text-slate-300 dark:text-slate-600" />
        <p class="text-base font-semibold text-slate-600 dark:text-slate-400">Không có hoạt động nào</p>
        <p class="mt-1 text-sm text-slate-400 dark:text-slate-500">Thử điều chỉnh bộ lọc hoặc khoảng thời gian</p>
      </div>

      <!-- ── Activity feed ──────────────────────────────────────────────────── -->
      <div v-else class="space-y-5">
        <div v-for="group in groupedItems" :key="group.date">
          <!-- Date heading -->
          <div class="mb-2 flex items-center gap-3">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ group.label }}</span>
            <div class="h-px flex-1 bg-slate-100 dark:bg-slate-800" />
          </div>

          <!-- Items for this date -->
          <div class="space-y-1.5">
            <div
              v-for="log in group.items"
              :key="log.id"
              class="group flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200/60 bg-white px-4 py-3.5 transition hover:border-slate-300 hover:shadow-sm dark:border-slate-700/60 dark:bg-slate-900 dark:hover:border-slate-600"
              @click="goDetail(log)"
            >
              <!-- Avatar -->
              <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                :class="eventAvatarClass(log.event)"
              >
                {{ actorInitials(log.actor?.name) }}
              </div>

              <!-- Content -->
              <div class="min-w-0 flex-1">
                <p class="text-sm leading-snug text-slate-800 dark:text-slate-200">
                  <span class="font-semibold">{{ log.actor?.name ?? 'Hệ thống' }}</span>
                  <span class="text-slate-600 dark:text-slate-400"> {{ actionVerb(log) }}</span>
                  <span v-if="subjectText(log)" class="font-medium text-slate-700 dark:text-slate-300"> {{ subjectText(log) }}</span>
                </p>
                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                  <span class="text-xs text-slate-400 dark:text-slate-500">{{ timeAgo(log.created_at) }}</span>
                  <span
                    v-if="log.metadata?.status && log.metadata.status >= 400"
                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium"
                    :class="log.metadata.status >= 500 ? 'bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400'"
                  >
                    <ExclamationCircleIcon class="h-3 w-3" aria-hidden="true" />
                    Lỗi {{ log.metadata.status }}
                  </span>
                </div>
              </div>

              <!-- Event tag (subtle) -->
              <div class="shrink-0 text-right">
                <span
                  class="inline-block rounded-lg px-2 py-0.5 text-[11px] font-medium"
                  :class="eventTagClass(log.event)"
                >{{ eventLabel(log.event) }}</span>
                <ChevronRightIcon class="mx-auto mt-1 h-3.5 w-3.5 text-slate-300 transition group-hover:text-slate-400 dark:text-slate-600" aria-hidden="true" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Pagination ─────────────────────────────────────────────────────── -->
      <div
        v-if="meta.last_page > 1"
        class="flex items-center justify-between pt-2"
      >
        <p class="text-xs text-slate-400 dark:text-slate-500">
          Tổng <span class="font-medium text-slate-600 dark:text-slate-300">{{ (meta.total ?? 0).toLocaleString('vi-VN') }}</span> hoạt động
        </p>
        <div class="flex items-center gap-1">
          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
            :disabled="(meta.current_page ?? 1) <= 1"
            @click="goPage((meta.current_page ?? 1) - 1)"
          >
            <ChevronLeftIcon class="h-4 w-4" aria-hidden="true" />
          </button>
          <span class="min-w-[4.5rem] text-center text-xs font-medium text-slate-600 dark:text-slate-300">
            {{ meta.current_page ?? 1 }} / {{ meta.last_page ?? 1 }}
          </span>
          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
            :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1)"
            @click="goPage((meta.current_page ?? 1) + 1)"
          >
            <ChevronRightIcon class="h-4 w-4" aria-hidden="true" />
          </button>
        </div>
      </div>
    </template>

  </div>
</template>

<script setup>
import { computed, onActivated, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import {
  ArrowPathIcon,
  MagnifyingGlassIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClockIcon,
  FunnelIcon,
  XMarkIcon,
  ExclamationCircleIcon,
} from '@heroicons/vue/24/outline'
import { useFilterBarVisibility } from '../../composables/useFilterBarVisibility.js'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterFunnelMenu from '../../components/filters/AppFilterFunnelMenu.vue'
import { listAuditLogs } from '../../api/audit'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { formatApiError } from '../../api/http'
import { debounceTrailing } from '../../composables/useDebounce'

const { locale } = useI18n()
const router = useRouter()

// ─── Categories ───────────────────────────────────────────────────────────────

const CATEGORIES = [
  { key: 'all',        label: 'Tất cả',     icon: '📋', events: null },
  { key: 'request',   label: 'Yêu cầu',    icon: '📝', events: ['request.create', 'request.paper_received', 'request.reject', 'request.approve'] },
  { key: 'file',      label: 'Tài liệu',   icon: '📎', events: ['attachment.upload', 'attachment.ocr_stub'] },
  { key: 'alert',     label: 'Cảnh báo',   icon: '⚠️',  events: ['cargo.sla_breached'] },
  { key: 'system',    label: 'Hệ thống',   icon: '🔧', events: ['api.request'] },
]

// ─── Event labels (human-readable) ───────────────────────────────────────────

const EVENT_LABELS = {
  'api.request':            'Truy cập hệ thống',
  'request.create':         'Tạo yêu cầu',
  'request.paper_received': 'Nhận hồ sơ giấy',
  'request.reject':         'Từ chối yêu cầu',
  'request.approve':        'Duyệt yêu cầu',
  'attachment.upload':      'Tải lên tài liệu',
  'attachment.ocr_stub':    'Nhận dạng tài liệu',
  'cargo.sla_breached':     'Vi phạm SLA',
}

const EVENT_VERBS = {
  'api.request':            'truy cập hệ thống',
  'request.create':         'đã tạo',
  'request.paper_received': 'đã xác nhận nhận hồ sơ giấy',
  'request.reject':         'đã từ chối',
  'request.approve':        'đã duyệt',
  'attachment.upload':      'đã tải lên tài liệu',
  'attachment.ocr_stub':    'đã nhận dạng tài liệu',
  'cargo.sla_breached':     '— cảnh báo vi phạm SLA',
}

const SUBJECT_LABELS = {
  'DispatchRequest': 'yêu cầu',
  'Trip':            'chuyến xe',
  'Attachment':      'tài liệu',
  'User':            'tài khoản',
  'PolicyTrip':      'chuyến chính sách',
  'Vehicle':         'phương tiện',
  'Driver':          'tài xế',
}

const EVENT_AVATAR = {
  'request.approve':        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
  'request.reject':         'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300',
  'request.create':         'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
  'request.paper_received': 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300',
  'attachment.upload':      'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
  'attachment.ocr_stub':    'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300',
  'cargo.sla_breached':     'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
  'api.request':            'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
}

const EVENT_TAG = {
  'request.approve':        'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400',
  'request.reject':         'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400',
  'request.create':         'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400',
  'request.paper_received': 'bg-teal-50 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400',
  'attachment.upload':      'bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400',
  'attachment.ocr_stub':    'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400',
  'cargo.sla_breached':     'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400',
}

function eventLabel(event)      { return EVENT_LABELS[event] ?? event }
function eventAvatarClass(event){ return EVENT_AVATAR[event] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }
function eventTagClass(event)   { return EVENT_TAG[event]   ?? 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }

function actionVerb(log) {
  return EVENT_VERBS[log.event] ?? log.event
}

function subjectText(log) {
  if (!log.auditable_type && !log.auditable_id) return null
  const typeName = log.auditable_type?.split('\\').pop()
  const label = SUBJECT_LABELS[typeName] ?? typeName
  return log.auditable_id ? `${label} #${log.auditable_id}` : label
}

// ─── State ────────────────────────────────────────────────────────────────────

const loading        = ref(false)
const listReady      = ref(false)
const items          = ref([])
const meta           = ref({})
const activeCategory = ref('all')
const searchInput    = ref('')
const searchQ        = ref('')

const filters = reactive({ from: '', to: '', page: 1, per_page: 50 })

const AUDIT_FILTER_VIS_IDS = ['category', 'search', 'dates']
const AUDIT_FILTER_VIS_DEFAULTS = Object.fromEntries(AUDIT_FILTER_VIS_IDS.map((id) => [id, false]))
const {
  visible: filterBarVisible,
  resetVisibility: resetFilterBarVisibility,
  hasVisibleOnBar: hasVisibleBarFilters,
} = useFilterBarVisibility(AUDIT_FILTER_VIS_IDS, AUDIT_FILTER_VIS_DEFAULTS)

const filterMenuRef = ref(null)

function onAuditFilterBarEnter() {
  resetFilterBarVisibility()
}

function closeFilterMenu() {
  filterMenuRef.value?.close?.()
}

// ─── Computed ─────────────────────────────────────────────────────────────────

const activeFilterCount = computed(() => {
  let n = 0
  if (activeCategory.value !== 'all') n++
  if (searchInput.value.trim()) n++
  if (filters.from || filters.to) n++
  return n
})

const activeCategoryLabel = computed(() => CATEGORIES.find((c) => c.key === activeCategory.value)?.label ?? activeCategory.value)

const hasActiveFilters = computed(() => activeFilterCount.value > 0)

const dateRangeLabel = computed(() => {
  if (!filters.from && !filters.to) return 'Khoảng ngày'
  const a = filters.from || '…'
  const b = filters.to   || '…'
  return `${a} – ${b}`
})

const bumpSearch = debounceTrailing(() => { searchQ.value = searchInput.value }, 300)
watch(searchInput, () => bumpSearch())

function itemMatchesSearch(log, q) {
  if (!q) return true
  const blob = [
    log.actor?.name ?? '',
    log.actor?.employee_code ?? '',
    log.actor_id ?? '',
    eventLabel(log.event),
    EVENT_VERBS[log.event] ?? '',
    subjectText(log) ?? '',
    log.auditable_type ?? '',
    String(log.auditable_id ?? ''),
  ].join(' ').toLowerCase()
  return blob.includes(q)
}

const filteredItems = computed(() => {
  const q = searchQ.value.trim().toLowerCase()
  if (!q) return items.value
  return items.value.filter((l) => itemMatchesSearch(l, q))
})

const groupedItems = computed(() => {
  const groups = []
  const seen = new Map()
  for (const log of filteredItems.value) {
    const key = dateGroupKey(log.created_at)
    const label = dateGroupLabel(log.created_at)
    if (!seen.has(key)) {
      seen.set(key, groups.length)
      groups.push({ date: key, label, items: [] })
    }
    groups[seen.get(key)].items.push(log)
  }
  return groups
})

// ─── Date helpers ─────────────────────────────────────────────────────────────

function dateGroupKey(v) {
  if (!v) return 'unknown'
  try { return new Date(v).toISOString().slice(0, 10) } catch { return 'unknown' }
}

function dateGroupLabel(v) {
  if (!v) return 'Không rõ'
  try {
    const d    = new Date(v)
    const now  = new Date()
    const diff = Math.floor((now - d) / 86400000)
    if (diff === 0) return 'Hôm nay'
    if (diff === 1) return 'Hôm qua'
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return d.toLocaleDateString(loc, { weekday: 'long', day: 'numeric', month: 'long' })
  } catch { return String(v) }
}

function timeAgo(v) {
  if (!v) return ''
  try {
    const diffMs   = Date.now() - new Date(v)
    const diffMins = Math.floor(diffMs / 60000)
    const diffH    = Math.floor(diffMins / 60)
    const diffD    = Math.floor(diffH / 24)
    if (diffMins < 1)  return 'vừa xong'
    if (diffMins < 60) return `${diffMins} phút trước`
    if (diffH < 24)    return `${diffH} giờ trước`
    if (diffD <= 1)    return 'hôm qua'
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return new Date(v).toLocaleTimeString(loc, { hour: '2-digit', minute: '2-digit' })
  } catch { return '' }
}

function actorInitials(name) {
  if (!name) return '?'
  return name.split(' ').filter(Boolean).slice(-2).map((n) => n[0]).join('').toUpperCase().slice(0, 2)
}

// ─── Actions ──────────────────────────────────────────────────────────────────

const scheduleReload = debounceTrailing(() => {
  if (!listReady.value) return
  filters.page = 1
  reload(false)
}, 350)

watch(() => [filters.from, filters.to], () => scheduleReload(), { deep: true })

function setCategory(key) {
  activeCategory.value = key
  filters.page = 1
  if (listReady.value) reload(false)
}

function resetFilters() {
  activeCategory.value = 'all'
  searchInput.value    = ''
  searchQ.value        = ''
  filters.from         = ''
  filters.to           = ''
  filters.page         = 1
  if (listReady.value) reload(false)
}

function goDetail(log) {
  router.push({ name: 'systemAuditDetail', params: { id: log.id } })
}

async function reload(notify = false) {
  loading.value = true
  try {
    const cat    = CATEGORIES.find((c) => c.key === activeCategory.value)
    const params = { page: filters.page, per_page: filters.per_page }
    if (filters.from) params.from = filters.from
    if (filters.to)   params.to   = filters.to
    if (cat?.events)  params.events = cat.events

    const res = await listAuditLogs(params)
    items.value = res.items ?? []
    meta.value  = res.meta  ?? {}
    if (notify) showAppSuccess('Đã tải lại danh sách.', 'Thành công')
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  filters.page = p
  reload(false)
}

onMounted(async () => {
  onAuditFilterBarEnter()
  await reload(false)
  listReady.value = true
})

onActivated(() => {
  onAuditFilterBarEnter()
})
</script>
