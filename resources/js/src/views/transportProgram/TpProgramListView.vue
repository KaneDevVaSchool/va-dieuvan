<template>
  <div class="space-y-5 pb-10">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">
          Chương trình Đưa đón
        </h1>
        <p class="mt-1.5 text-base text-slate-500">Quản lý tất cả chương trình vận chuyển học sinh</p>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-xl bg-va-800 px-5 py-3 text-base font-semibold text-white shadow-sm shadow-va-800/20 transition hover:bg-va-900"
          @click="goCreate"
        >
          <PlusIcon class="h-5 w-5" /> Tạo Chương trình
        </button>
      </div>
    </div>

    <!-- Stat tiles -->
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
      <div
        v-for="tile in statTiles"
        :key="tile.key"
        class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
      >
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl" :class="tile.iconBg">
          <component :is="tile.icon" class="h-6 w-6" :class="tile.iconText" />
        </span>
        <div class="min-w-0">
          <div class="text-3xl font-bold tracking-tight text-slate-900 md:text-4xl">{{ tile.value }}</div>
          <div class="mt-0.5 text-sm font-medium" :class="tile.hintClass">{{ tile.hint }}</div>
        </div>
      </div>
    </div>

    <!-- Filter / toolbar -->
    <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
      <AppFilterBar>
        <div ref="tpProgramFilterBarRef" class="flex w-full flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
          <AppFilterFunnelMenu ref="filterMenuRef" :badge-count="activeFilterCount">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bộ lọc đang áp dụng</p>
            <ul class="mt-2 space-y-2 text-sm text-slate-700">
              <li v-if="filters.search" class="flex justify-between gap-2">
                <span class="text-slate-500">Tìm kiếm</span>
                <span class="max-w-[10rem] truncate font-medium">{{ filters.search }}</span>
              </li>
              <li v-if="filters.status" class="flex justify-between gap-2">
                <span class="text-slate-500">Trạng thái</span>
                <span class="font-medium">{{ statusLabel(filters.status) }}</span>
              </li>
              <li v-if="filters.schoolYear" class="flex justify-between gap-2">
                <span class="text-slate-500">Năm học</span>
                <span class="font-medium">{{ filters.schoolYear }}</span>
              </li>
              <li v-if="filters.route" class="flex justify-between gap-2">
                <span class="text-slate-500">Tuyến</span>
                <span class="max-w-[10rem] truncate font-medium">{{ filters.route }}</span>
              </li>
              <li v-if="activeFilterCount === 0" class="text-slate-400">Chưa có điều kiện lọc</li>
            </ul>
            <div class="mt-3 border-t border-slate-100 pt-3">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700">Hiển thị bộ lọc trên thanh</p>
              <ul class="mt-2 space-y-2">
                <li v-for="opt in filterBarVisibilityOptions" :key="opt.id" class="flex items-start gap-2">
                  <input :id="'tp-prog-vis-' + opt.id" v-model="filterBarVisible[opt.id]" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal-600" />
                  <label :for="'tp-prog-vis-' + opt.id" class="cursor-pointer text-sm text-slate-700">{{ opt.label }}</label>
                </li>
              </ul>
            </div>
            <button type="button" class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="clearFilters(); closeFilterMenu()">
              Xóa tất cả bộ lọc
            </button>
          </AppFilterFunnelMenu>
          <div class="hidden h-6 w-px bg-slate-200 sm:block" aria-hidden="true" />
          <button
            type="button"
            class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
            aria-label="Xóa lọc"
            @click="clearFilters"
          >
            <span class="relative inline-flex">
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              <XMarkIcon
                class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40"
                aria-hidden="true"
              />
            </span>
          </button>
          <div class="ml-auto flex items-center gap-1 rounded-xl border border-slate-200 p-1">
            <button type="button" class="grid h-9 w-9 place-items-center rounded-lg transition" :class="view === 'grid' ? 'bg-va-800 text-white' : 'text-slate-400 hover:text-slate-600'" aria-label="Xem dạng lưới" @click="view = 'grid'">
              <Squares2X2Icon class="h-5 w-5" />
            </button>
            <button type="button" class="grid h-9 w-9 place-items-center rounded-lg transition" :class="view === 'list' ? 'bg-va-800 text-white' : 'text-slate-400 hover:text-slate-600'" aria-label="Xem dạng danh sách" @click="view = 'list'">
              <ListBulletIcon class="h-5 w-5" />
            </button>
          </div>
        </div>
        <div v-if="hasVisibleBarFilters" class="mt-2 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-2">
          <div v-if="filterBarVisible.search" class="relative min-w-[14rem] flex-1">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input v-model="filters.search" type="search" placeholder="Tìm kiếm…" aria-label="Tìm kiếm chương trình" class="h-9 w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:ring-2 focus:ring-teal-500/20" />
          </div>
          <select v-if="filterBarVisible.status" v-model="filters.status" aria-label="Trạng thái" class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm" :class="filters.status ? 'text-slate-900' : 'text-slate-500'">
            <option value="">Trạng thái</option>
            <option value="draft">Nháp</option>
            <option value="active">Hoạt động</option>
            <option value="paused">Tạm dừng</option>
            <option value="completed">Hoàn thành</option>
            <option value="cancelled">Đã hủy</option>
          </select>
          <select v-if="filterBarVisible.schoolYear" v-model="filters.schoolYear" aria-label="Năm học" class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm" :class="filters.schoolYear ? 'text-slate-900' : 'text-slate-500'">
            <option value="">Năm học</option>
            <option v-for="y in schoolYearOptions" :key="y" :value="y">{{ y }}</option>
          </select>
          <select v-if="filterBarVisible.route" v-model="filters.route" aria-label="Tuyến" class="h-9 max-w-[12rem] rounded-lg border border-slate-200 bg-white px-3 text-sm" :class="filters.route ? 'text-slate-900' : 'text-slate-500'">
            <option value="">Tuyến</option>
            <option v-for="r in routeOptions" :key="r" :value="r">{{ r }}</option>
          </select>
        </div>
      </AppFilterBar>

      <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
        <p class="text-sm text-slate-500">{{ listSummaryText }}</p>
        <label class="flex items-center gap-2 text-sm text-slate-500">
          Sắp xếp:
          <select
            v-model="sort"
            aria-label="Sắp xếp"
            class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm text-slate-700 outline-none ring-va-800/20 focus:ring"
          >
            <option value="newest">Mới nhất</option>
            <option value="oldest">Cũ nhất</option>
            <option value="name">Tên A → Z</option>
            <option value="students">Học sinh nhiều nhất</option>
          </select>
        </label>
      </div>
    </div>

    <!-- States -->
    <div v-if="loading" class="flex items-center justify-center rounded-2xl border border-slate-200 bg-white py-16 text-base text-slate-500">
      <ArrowPathIcon class="mr-2 h-6 w-6 animate-spin" /> Đang tải…
    </div>
    <div v-else-if="!visibleItems.length" class="flex flex-col items-center justify-center rounded-2xl border border-slate-200 bg-white py-16 text-center">
      <AcademicCapIcon class="mb-3 h-14 w-14 text-slate-300" />
      <p class="text-base font-medium text-slate-600">Không có chương trình phù hợp</p>
      <Button class="mt-4" @click="goCreate">Tạo chương trình đầu tiên</Button>
    </div>

    <!-- Card grid -->
    <div v-else-if="view === 'grid'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <article
        v-for="p in visibleItems"
        :key="p.id"
        class="group cursor-pointer rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-va-800/30 hover:shadow-md"
        @click="goWorkspace(p.id)"
      >
        <div class="flex items-start gap-3">
          <div class="grid h-12 w-12 shrink-0 place-items-center rounded-xl" :class="accent(p.status).iconBg">
            <TruckIcon class="h-7 w-7" :class="accent(p.status).iconText" />
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="truncate text-base font-semibold text-slate-900">{{ p.name }}</h3>
            <p class="mt-0.5 text-xs font-medium uppercase tracking-wide text-slate-400">
              Năm học {{ schoolYear(p) }}
            </p>
          </div>
          <span :class="statusClass(p.status)">
            <span class="h-1.5 w-1.5 rounded-full" :class="accent(p.status).dot"></span>
            {{ statusLabel(p.status) }}
          </span>
        </div>

        <div class="mt-4 grid grid-cols-3 gap-2 rounded-xl bg-slate-50/70 py-3 text-center">
          <div>
            <div class="text-2xl font-bold text-slate-900">{{ p.enrolled_count ?? 0 }}</div>
            <div class="mt-0.5 text-xs text-slate-500">Học sinh</div>
          </div>
          <div class="border-x border-slate-200/70">
            <div class="text-2xl font-bold text-slate-900">{{ p.day_count ?? 0 }}</div>
            <div class="mt-0.5 text-xs text-slate-500">Số ngày</div>
          </div>
          <div>
            <div class="text-2xl font-bold" :class="accent(p.status).iconText">{{ runsPerWeek(p) }}</div>
            <div class="mt-0.5 text-xs text-slate-500">Buổi/tuần</div>
          </div>
        </div>

        <div class="mt-4 space-y-2 text-sm text-slate-500">
          <div class="flex items-center gap-2">
            <MapPinIcon class="h-4 w-4 shrink-0 text-slate-400" />
            <span class="truncate">{{ p.origin_name || '—' }} → {{ p.destination_name || 'Trường' }}</span>
          </div>
          <div class="flex items-center gap-2">
            <ClockIcon class="h-4 w-4 shrink-0 text-slate-400" />
            <span>{{ timeRange(p) }}</span>
          </div>
        </div>

        <div class="mt-4">
          <div class="mb-1.5 flex items-center justify-between text-xs">
            <span class="text-slate-500">Tiến độ chương trình</span>
            <span class="font-semibold text-slate-700">{{ progress(p) }}%</span>
          </div>
          <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full transition-all" :class="accent(p.status).bar" :style="{ width: progress(p) + '%' }"></div>
          </div>
        </div>

        <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3.5">
          <div class="flex items-center gap-2 text-sm text-slate-600">
            <span class="grid h-7 w-7 place-items-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">
              {{ initials(p.responsible_user_name) }}
            </span>
            <span class="truncate">{{ p.responsible_user_name || 'Chưa phân công' }}</span>
          </div>
          <span class="inline-flex items-center gap-1 text-sm font-medium text-va-800 group-hover:underline">
            Xem chi tiết <ArrowRightIcon class="h-4 w-4" />
          </span>
        </div>
      </article>
    </div>

    <!-- List view -->
    <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
      <table class="w-full min-w-[56rem] text-left text-base">
        <thead class="bg-slate-50 text-sm uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3.5 font-medium">Chương trình</th>
            <th class="px-4 py-3.5 font-medium">Tuyến</th>
            <th class="px-4 py-3.5 font-medium">Giờ</th>
            <th class="px-4 py-3.5 font-medium">Học sinh</th>
            <th class="px-4 py-3.5 font-medium">Số ngày</th>
            <th class="px-4 py-3.5 font-medium">Phụ trách</th>
            <th class="px-4 py-3.5 font-medium">Trạng thái</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="p in pagedListItems"
            :key="p.id"
            class="cursor-pointer hover:bg-slate-50/60"
            @click="goWorkspace(p.id)"
          >
            <td class="px-4 py-3.5">
              <div class="font-medium text-slate-900">{{ p.name }}</div>
              <div class="font-mono text-xs text-slate-400">{{ p.code }}</div>
            </td>
            <td class="px-4 py-3.5 text-slate-600">{{ p.origin_name || '—' }} → {{ p.destination_name || 'Trường' }}</td>
            <td class="px-4 py-3.5 text-slate-600">{{ timeRange(p) }}</td>
            <td class="px-4 py-3.5 text-slate-600">{{ p.enrolled_count ?? 0 }}</td>
            <td class="px-4 py-3.5 text-slate-600">{{ p.day_count ?? 0 }}</td>
            <td class="px-4 py-3.5 text-slate-600">{{ p.responsible_user_name || '—' }}</td>
            <td class="px-4 py-3.5">
              <span :class="statusClass(p.status)">
                <span class="h-1.5 w-1.5 rounded-full" :class="accent(p.status).dot"></span>
                {{ statusLabel(p.status) }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm">
        <div class="flex flex-wrap items-center gap-3 text-slate-500">
          <span v-if="listTotal > 0">
            {{ listRangeText }}
          </span>
          <label class="inline-flex items-center gap-2">
            <span class="text-slate-500">Hiển thị</span>
            <select
              v-model.number="listPerPage"
              aria-label="Số dòng mỗi trang"
              class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-sm text-slate-700 outline-none ring-va-800/20 focus:ring"
            >
              <option v-for="n in LIST_PER_PAGE_OPTIONS" :key="n" :value="n">{{ n }}</option>
            </select>
            <span class="text-slate-500">dòng</span>
          </label>
        </div>
        <div v-if="listLastPage > 1" class="flex items-center gap-2">
          <span class="text-xs text-slate-500">Trang {{ listPage }} / {{ listLastPage }}</span>
          <Button variant="secondary" :disabled="listPage <= 1" @click="changeListPage(listPage - 1)">Trước</Button>
          <Button variant="secondary" :disabled="listPage >= listLastPage" @click="changeListPage(listPage + 1)">Sau</Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onActivated, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  PlusIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  Squares2X2Icon,
  ListBulletIcon,
  ArrowPathIcon,
  AcademicCapIcon,
  MapPinIcon,
  ClockIcon,
  TruckIcon,
  XMarkIcon,
  ArrowRightIcon,
  RectangleStackIcon,
  BoltIcon,
  CheckBadgeIcon,
  CalendarDaysIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterFunnelMenu from '../../components/filters/AppFilterFunnelMenu.vue'
import { useFilterBarVisibility } from '../../composables/useFilterBarVisibility.js'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { listPrograms } from '../../api/transportProgram'
import { showAppErrorFromApi } from '../../composables/appMessage'

const router = useRouter()
const loading = ref(false)
const items = ref([])
const view = ref('grid')
const sort = ref('newest')
const filters = reactive({ search: '', status: '', schoolYear: '', route: '' })

const TP_PROG_FILTER_VIS_IDS = ['search', 'status', 'schoolYear', 'route']
const TP_PROG_FILTER_VIS_DEFAULTS = Object.fromEntries(TP_PROG_FILTER_VIS_IDS.map((id) => [id, false]))
const {
  visible: filterBarVisible,
  resetVisibility: resetFilterBarVisibility,
  hasVisibleOnBar: hasVisibleBarFilters,
} = useFilterBarVisibility(TP_PROG_FILTER_VIS_IDS, TP_PROG_FILTER_VIS_DEFAULTS)

const filterMenuRef = ref(null)
const tpProgramFilterBarRef = ref(null)
useDetailsAutoCloseWithin(tpProgramFilterBarRef)

const filterBarVisibilityOptions = [
  { id: 'search', label: 'Tìm kiếm' },
  { id: 'status', label: 'Trạng thái' },
  { id: 'schoolYear', label: 'Năm học' },
  { id: 'route', label: 'Tuyến' },
]

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.search?.trim()) n++
  if (filters.status) n++
  if (filters.schoolYear) n++
  if (filters.route) n++
  return n
})

function onTpProgramFilterBarEnter() {
  resetFilterBarVisibility()
}

function closeFilterMenu() {
  filterMenuRef.value?.close?.()
}

const LIST_PER_PAGE_OPTIONS = [5, 10, 15, 20]
const listPage = ref(1)
const listPerPage = ref(10)
let searchTimer = null

async function load() {
  loading.value = true
  try {
    const res = await listPrograms({
      status: filters.status || undefined,
      search: filters.search || undefined,
      per_page: 60,
    })
    items.value = res?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

watch(() => filters.status, load)
watch(() => filters.search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(load, 300)
})

function schoolYear(p) {
  const s = p.start_date ? new Date(p.start_date).getFullYear() : null
  const e = p.end_date ? new Date(p.end_date).getFullYear() : null
  if (!s) return '—'
  return s === e || !e ? String(s) : `${s}–${e}`
}

function runsPerWeek(p) {
  return Array.isArray(p.runs_on) ? p.runs_on.length : 0
}

function timeRange(p) {
  const fmt = (t) => (t ? String(t).slice(0, 5) : null)
  const a = fmt(p.departure_time)
  const b = fmt(p.return_time)
  if (a && b) return `${a} – ${b}`
  return a || '—'
}

function progress(p) {
  if (p.status === 'completed') return 100
  if (p.status === 'draft' || p.status === 'cancelled') return 0
  if (!p.start_date || !p.end_date) return 0
  const start = new Date(p.start_date).getTime()
  const end = new Date(p.end_date).getTime()
  const now = Date.now()
  if (now <= start) return 0
  if (now >= end) return 100
  return Math.round(((now - start) / (end - start)) * 100)
}

function initials(name) {
  if (!name) return '?'
  return name
    .trim()
    .split(/\s+/)
    .slice(-2)
    .map((w) => w[0])
    .join('')
    .toUpperCase()
}

const schoolYearOptions = computed(() => {
  const set = new Set(items.value.map((p) => schoolYear(p)).filter((y) => y && y !== '—'))
  return [...set].sort().reverse()
})

const routeOptions = computed(() => {
  const set = new Set(items.value.map((p) => p.destination_name).filter(Boolean))
  return [...set].sort()
})

const hasActiveFilters = computed(
  () => !!(filters.search || filters.status || filters.schoolYear || filters.route),
)

const visibleItems = computed(() => {
  let rows = items.value.filter((p) => {
    if (filters.schoolYear && schoolYear(p) !== filters.schoolYear) return false
    if (filters.route && p.destination_name !== filters.route) return false
    return true
  })
  rows = [...rows]
  switch (sort.value) {
    case 'oldest':
      rows.sort((a, b) => a.id - b.id)
      break
    case 'name':
      rows.sort((a, b) => (a.name || '').localeCompare(b.name || '', 'vi'))
      break
    case 'students':
      rows.sort((a, b) => (b.enrolled_count ?? 0) - (a.enrolled_count ?? 0))
      break
    default:
      rows.sort((a, b) => b.id - a.id)
  }
  return rows
})

const listTotal = computed(() => visibleItems.value.length)

const listLastPage = computed(() => Math.max(1, Math.ceil(listTotal.value / listPerPage.value)))

const pagedListItems = computed(() => {
  const start = (listPage.value - 1) * listPerPage.value
  return visibleItems.value.slice(start, start + listPerPage.value)
})

const listRangeStart = computed(() => (listTotal.value ? (listPage.value - 1) * listPerPage.value + 1 : 0))

const listRangeEnd = computed(() =>
  listTotal.value ? Math.min(listPage.value * listPerPage.value, listTotal.value) : 0,
)

const listRangeText = computed(() =>
  listTotal.value ? `${listRangeStart.value}–${listRangeEnd.value} / ${listTotal.value} chương trình` : '',
)

const listSummaryText = computed(() => {
  const n = visibleItems.value.length
  if (view.value === 'list' && n > 0) {
    return listRangeText.value
  }
  return `Hiển thị ${n} chương trình`
})

function changeListPage(p) {
  listPage.value = Math.min(Math.max(1, p), listLastPage.value)
}

watch([visibleItems, listPerPage], () => {
  listPage.value = 1
})

watch(listLastPage, (last) => {
  if (listPage.value > last) listPage.value = last
})

const statTiles = computed(() => {
  const all = items.value
  const total = all.length
  const active = all.filter((p) => p.status === 'active').length
  const completed = all.filter((p) => p.status === 'completed').length
  const operatingDays = all.reduce((sum, p) => sum + (p.day_count ?? 0), 0)
  const activePct = total ? Math.round((active / total) * 100) : 0
  return [
    {
      key: 'total',
      value: total,
      hint: 'chương trình',
      hintClass: 'text-slate-500',
      icon: RectangleStackIcon,
      iconBg: 'bg-slate-100',
      iconText: 'text-slate-600',
    },
    {
      key: 'active',
      value: active,
      hint: `${activePct}% đang hoạt động`,
      hintClass: 'text-emerald-600',
      icon: BoltIcon,
      iconBg: 'bg-emerald-50',
      iconText: 'text-emerald-600',
    },
    {
      key: 'completed',
      value: completed,
      hint: 'đã hoàn thành',
      hintClass: 'text-sky-600',
      icon: CheckBadgeIcon,
      iconBg: 'bg-sky-50',
      iconText: 'text-sky-600',
    },
    {
      key: 'days',
      value: operatingDays,
      hint: 'ngày vận hành',
      hintClass: 'text-violet-600',
      icon: CalendarDaysIcon,
      iconBg: 'bg-violet-50',
      iconText: 'text-violet-600',
    },
  ]
})

function clearFilters() {
  filters.search = ''
  filters.status = ''
  filters.schoolYear = ''
  filters.route = ''
}

function goCreate() {
  router.push({ name: 'tpProgramCreate' })
}
function goWorkspace(id) {
  router.push({ name: 'tpProgramWorkspace', params: { id } })
}

function statusLabel(s) {
  return {
    draft: 'Nháp',
    active: 'Hoạt động',
    paused: 'Tạm dừng',
    completed: 'Hoàn thành',
    cancelled: 'Đã hủy',
  }[s] || s
}
function statusClass(s) {
  const base = 'inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium '
  return base + ({
    draft: 'bg-slate-100 text-slate-600',
    active: 'bg-emerald-50 text-emerald-700',
    paused: 'bg-amber-50 text-amber-700',
    completed: 'bg-sky-50 text-sky-700',
    cancelled: 'bg-rose-50 text-rose-700',
  }[s] || 'bg-slate-100 text-slate-600')
}
function accent(s) {
  return {
    draft: { iconBg: 'bg-slate-100', iconText: 'text-slate-500', dot: 'bg-slate-400', bar: 'bg-slate-400' },
    active: { iconBg: 'bg-emerald-50', iconText: 'text-emerald-600', dot: 'bg-emerald-500', bar: 'bg-emerald-500' },
    paused: { iconBg: 'bg-amber-50', iconText: 'text-amber-600', dot: 'bg-amber-500', bar: 'bg-amber-500' },
    completed: { iconBg: 'bg-sky-50', iconText: 'text-sky-600', dot: 'bg-sky-500', bar: 'bg-sky-500' },
    cancelled: { iconBg: 'bg-rose-50', iconText: 'text-rose-600', dot: 'bg-rose-500', bar: 'bg-rose-500' },
  }[s] || { iconBg: 'bg-slate-100', iconText: 'text-slate-500', dot: 'bg-slate-400', bar: 'bg-slate-400' }
}

onMounted(() => {
  onTpProgramFilterBarEnter()
  load()
})

onActivated(() => {
  onTpProgramFilterBarEnter()
})
</script>
