<template>
  <div class="space-y-4 md:space-y-5">
    <!-- Header -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Danh sách Học sinh</h1>
        <p class="mt-1 text-sm text-slate-500">Quản lý toàn bộ học sinh trong hệ thống đưa đón</p>
      </div>

      <div class="flex flex-col items-stretch gap-3 sm:items-end">
        <div class="flex flex-wrap gap-2">
          <Button variant="secondary" @click="exportList">
            <ArrowDownTrayIcon class="h-4 w-4" /> Xuất danh sách
          </Button>
          <Button variant="secondary" @click="goImport">
            <ArrowUpTrayIcon class="h-4 w-4" /> Import học sinh
          </Button>
          <Button @click="openCreate"><PlusIcon class="h-4 w-4" /> Thêm học sinh</Button>
        </div>

        <div class="flex flex-wrap gap-2">
          <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">
            <UsersIcon class="h-4 w-4 text-slate-400" />
            <span class="font-bold text-slate-900">{{ stats.total }}</span> Tổng học sinh
          </span>
          <span class="inline-flex items-center gap-1.5 rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700">
            <TruckIcon class="h-4 w-4 text-sky-500" />
            <span class="font-bold">{{ stats.transporting }}</span> Đang đưa đón
          </span>
          <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700">
            <ClockIcon class="h-4 w-4 text-amber-500" />
            <span class="font-bold">{{ stats.pending }}</span> Chờ duyệt
          </span>
          <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-500">
            <UserMinusIcon class="h-4 w-4 text-slate-400" />
            <span class="font-bold text-slate-700">{{ stats.unregistered }}</span> Chưa đăng ký
          </span>
        </div>
      </div>
    </div>

    <!-- Filter bar -->
    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5">
      <div class="relative min-w-[14rem] flex-1">
        <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
        <input
          v-model="filters.search"
          type="search"
          placeholder="Tìm theo tên, mã HS, SĐT..."
          class="h-9 w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-va-500 focus:outline-none focus:ring-2 focus:ring-va-500/20"
        />
      </div>

      <select v-model="filters.class_name" class="filter-select">
        <option value="">Tất cả lớp</option>
        <option v-for="c in filterOptions.classes" :key="c" :value="c">{{ c }}</option>
      </select>

      <select v-model="filters.transport_status" class="filter-select">
        <option value="">Tất cả trạng thái vận chuyển</option>
        <option value="transporting">Đang đưa đón</option>
        <option value="pending">Chờ duyệt</option>
        <option value="paused">Tạm dừng</option>
        <option value="unregistered">Chưa đăng ký</option>
      </select>

      <select v-model="filters.program_id" class="filter-select">
        <option value="">Tất cả chương trình</option>
        <option v-for="p in filterOptions.programs" :key="p.id" :value="p.id">{{ p.name }}</option>
      </select>

      <select v-model="filters.grade" class="filter-select">
        <option value="">Tất cả khối</option>
        <option v-for="g in filterOptions.grades" :key="g" :value="g">{{ g }}</option>
      </select>

      <button
        v-if="hasActiveFilters"
        type="button"
        class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700"
        @click="clearFilters"
      >
        <XMarkIcon class="h-4 w-4" /> Xóa lọc
      </button>

      <span class="ml-auto text-xs text-slate-400">
        Hiển thị <span class="font-semibold text-slate-600">{{ items.length }}</span> / {{ stats.total }} học sinh
      </span>
    </div>

    <!-- Table -->
    <div v-if="loading" class="flex items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> Đang tải…
    </div>
    <div v-else-if="!items.length" class="rounded-xl border border-slate-200 bg-white py-16 text-center text-sm text-slate-500">
      Không có học sinh phù hợp.
    </div>
    <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
      <table class="w-full min-w-[60rem] text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50/80 text-[11px] uppercase tracking-wide text-slate-500">
          <tr>
            <th class="w-10 px-3 py-3">
              <input type="checkbox" :checked="allSelected" class="h-4 w-4 rounded border-slate-300 accent-va-800" @change="toggleAll" />
            </th>
            <th class="w-12 px-3 py-3 font-semibold">#</th>
            <th class="px-3 py-3 font-semibold">Học sinh</th>
            <th class="px-3 py-3 font-semibold">Lớp</th>
            <th class="px-3 py-3 font-semibold">Chương trình</th>
            <th class="px-3 py-3 font-semibold">Liên hệ phụ huynh</th>
            <th class="px-3 py-3 font-semibold">Điểm đón</th>
            <th class="px-3 py-3 font-semibold">Trạng thái</th>
            <th class="w-16 px-3 py-3 text-right font-semibold">Hành động</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="(s, i) in items" :key="s.id" class="transition hover:bg-slate-50/70">
            <td class="px-3 py-3">
              <input type="checkbox" :value="s.id" v-model="selected" class="h-4 w-4 rounded border-slate-300 accent-va-800" />
            </td>
            <td class="px-3 py-3 text-xs font-medium text-slate-400">{{ rowNumber(i) }}</td>

            <td class="px-3 py-3">
              <div class="flex items-center gap-3">
                <span
                  class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold text-white"
                  :style="{ backgroundColor: avatarColor(s.full_name) }"
                >{{ initials(s.full_name) }}</span>
                <div class="min-w-0">
                  <div class="truncate font-semibold text-slate-900">{{ s.full_name }}</div>
                  <div class="truncate text-xs text-slate-400">{{ studentMeta(s) }}</div>
                </div>
              </div>
            </td>

            <td class="px-3 py-3 font-medium text-slate-700">{{ s.class_name || '—' }}</td>

            <td class="px-3 py-3">
              <template v-if="s.program">
                <div class="font-medium text-va-800">{{ s.program.name }}</div>
                <div class="mt-0.5 text-xs" :class="programSubClass(s.program.status)">
                  <span class="inline-flex items-center gap-1">
                    <component :is="programSubIcon(s.program.status)" class="h-3.5 w-3.5" />
                    {{ programSubLabel(s.program) }}
                  </span>
                </div>
              </template>
              <span v-else class="text-xs italic text-slate-400">Chưa gán chương trình</span>
            </td>

            <td class="px-3 py-3">
              <div class="font-medium text-slate-700">{{ s.parent_name || '—' }}</div>
              <a v-if="s.parent_phone" :href="`tel:${s.parent_phone}`" class="text-xs text-sky-600 hover:underline">{{ s.parent_phone }}</a>
            </td>

            <td class="px-3 py-3 text-slate-600">{{ s.pickup_point || '—' }}</td>

            <td class="px-3 py-3">
              <span :class="transportBadgeClass(s.transport_status)">
                <span class="h-1.5 w-1.5 rounded-full" :class="transportDotClass(s.transport_status)"></span>
                {{ transportLabel(s.transport_status) }}
              </span>
            </td>

            <td class="px-3 py-3 text-right">
              <AppRowActionsMenu :aria-label="`Hành động cho ${s.full_name}`">
                <button class="menu-item" @click="openEdit(s)"><PencilSquareIcon class="h-4 w-4" /> Chỉnh sửa</button>
                <button class="menu-item" @click="goEnroll(s)"><AcademicCapIcon class="h-4 w-4" /> Đăng ký tuyến</button>
                <button class="menu-item text-rose-600" @click="remove(s)"><TrashIcon class="h-4 w-4" /> Xóa</button>
              </AppRowActionsMenu>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-sm">
        <span class="text-xs text-slate-500">Trang {{ meta.current_page }} / {{ meta.last_page }}</span>
        <div class="flex gap-2">
          <Button variant="secondary" :disabled="meta.current_page <= 1" @click="changePage(meta.current_page - 1)">Trước</Button>
          <Button variant="secondary" :disabled="meta.current_page >= meta.last_page" @click="changePage(meta.current_page + 1)">Sau</Button>
        </div>
      </div>
    </div>

    <TpStudentFormModal v-if="showForm" :student="editing" @close="showForm = false" @saved="onSaved" />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  PlusIcon, ArrowPathIcon, ArrowUpTrayIcon, ArrowDownTrayIcon, MagnifyingGlassIcon,
  XMarkIcon, UsersIcon, TruckIcon, ClockIcon, UserMinusIcon, PencilSquareIcon,
  TrashIcon, AcademicCapIcon, CalendarDaysIcon, PauseCircleIcon, ExclamationCircleIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import AppRowActionsMenu from '../../components/ui/AppRowActionsMenu.vue'
import TpStudentFormModal from '../../components/transportProgram/TpStudentFormModal.vue'
import { listStudents, deleteStudent } from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'

const router = useRouter()
const loading = ref(false)
const items = ref([])
const selected = ref([])
const showForm = ref(false)
const editing = ref(null)
const stats = reactive({ total: 0, transporting: 0, pending: 0, unregistered: 0 })
const filterOptions = reactive({ grades: [], classes: [], programs: [] })
const meta = reactive({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const filters = reactive({ search: '', class_name: '', transport_status: '', program_id: '', grade: '', page: 1 })
let timer = null

const hasActiveFilters = computed(
  () => !!(filters.search || filters.class_name || filters.transport_status || filters.program_id || filters.grade),
)
const allSelected = computed(() => items.value.length > 0 && selected.value.length === items.value.length)

async function load() {
  loading.value = true
  try {
    const res = await listStudents({
      search: filters.search || undefined,
      class_name: filters.class_name || undefined,
      transport_status: filters.transport_status || undefined,
      program_id: filters.program_id || undefined,
      grade: filters.grade || undefined,
      page: filters.page,
    })
    items.value = res?.items ?? []
    Object.assign(stats, res?.stats ?? {})
    Object.assign(filterOptions, res?.filter_options ?? {})
    Object.assign(meta, res?.meta ?? {})
    selected.value = []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

watch(() => [filters.class_name, filters.transport_status, filters.program_id, filters.grade], () => {
  filters.page = 1
  load()
})
watch(() => filters.search, () => {
  clearTimeout(timer)
  filters.page = 1
  timer = setTimeout(load, 300)
})

function changePage(p) {
  filters.page = p
  load()
}
function clearFilters() {
  filters.search = ''
  filters.class_name = ''
  filters.transport_status = ''
  filters.program_id = ''
  filters.grade = ''
  filters.page = 1
  load()
}
function toggleAll(e) {
  selected.value = e.target.checked ? items.value.map((s) => s.id) : []
}
function rowNumber(i) {
  return String((meta.current_page - 1) * meta.per_page + i + 1).padStart(3, '0')
}

function openCreate() {
  editing.value = null
  showForm.value = true
}
function openEdit(s) {
  editing.value = { ...s }
  showForm.value = true
}
function onSaved() {
  showForm.value = false
  load()
}
function goImport() {
  router.push({ name: 'tpImportWizard' })
}
function goEnroll(s) {
  if (s.program?.id) router.push({ name: 'tpEnrollStudents', params: { id: s.program.id } })
  else router.push({ name: 'tpPrograms' })
}
function exportList() {
  showAppSuccess('Đang chuẩn bị tệp xuất danh sách…')
}

async function remove(s) {
  const ok = await confirmAction({ title: 'Xóa học sinh', message: `Xóa ${s.full_name}?`, danger: true, confirmLabel: 'Xóa' })
  if (!ok) return
  try {
    await deleteStudent(s.id)
    showAppSuccess('Đã xóa.')
    load()
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

// ── Presentation helpers ──────────────────────────────────────────────
function initials(name) {
  if (!name) return '?'
  const parts = String(name).trim().split(/\s+/)
  const last = parts[parts.length - 1]?.[0] ?? ''
  const first = parts.length > 1 ? parts[parts.length - 2]?.[0] ?? '' : parts[0]?.[1] ?? ''
  return (last + first).toUpperCase() || name[0].toUpperCase()
}
const AVATAR_COLORS = ['#2563eb', '#0891b2', '#7c3aed', '#db2777', '#ea580c', '#16a34a', '#0d9488', '#4f46e5']
function avatarColor(name) {
  let h = 0
  for (const ch of String(name || '')) h = (h * 31 + ch.charCodeAt(0)) >>> 0
  return AVATAR_COLORS[h % AVATAR_COLORS.length]
}
function studentMeta(s) {
  const parts = [s.code, genderLabel(s.gender), s.age ? `${s.age} tuổi` : null].filter(Boolean)
  return parts.join(' · ')
}
function genderLabel(g) {
  return { male: 'Nam', female: 'Nữ', nam: 'Nam', nu: 'Nữ' }[String(g || '').toLowerCase()] || g || null
}
function formatDate(d) {
  if (!d) return ''
  const [y, m, day] = String(d).slice(0, 10).split('-')
  return `${day}/${m}/${y}`
}
function programSubLabel(p) {
  if (p.status === 'paused') return 'Tạm dừng'
  if (p.status === 'draft') return 'Chờ xác nhận'
  return p.start_date ? `Từ ${formatDate(p.start_date)}` : 'Đang hoạt động'
}
function programSubClass(status) {
  return { paused: 'text-rose-500', draft: 'text-amber-600' }[status] || 'text-slate-400'
}
function programSubIcon(status) {
  return { paused: PauseCircleIcon, draft: ExclamationCircleIcon }[status] || CalendarDaysIcon
}

const TRANSPORT_LABELS = { transporting: 'Đang đưa đón', pending: 'Chờ duyệt', paused: 'Tạm dừng', unregistered: 'Chưa đăng ký' }
function transportLabel(s) {
  return TRANSPORT_LABELS[s] || s
}
function transportBadgeClass(s) {
  const base = 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium '
  return base + ({
    transporting: 'bg-emerald-50 text-emerald-700',
    pending: 'bg-amber-50 text-amber-700',
    paused: 'bg-rose-50 text-rose-700',
    unregistered: 'bg-slate-100 text-slate-500',
  }[s] || 'bg-slate-100 text-slate-500')
}
function transportDotClass(s) {
  return {
    transporting: 'bg-emerald-500',
    pending: 'bg-amber-500',
    paused: 'bg-rose-500',
    unregistered: 'bg-slate-400',
  }[s] || 'bg-slate-400'
}

onMounted(load)
</script>

<style scoped>
.filter-select {
  @apply h-9 cursor-pointer rounded-lg border border-slate-200 bg-white px-3 pr-8 text-sm font-medium text-slate-700 focus:border-va-500 focus:outline-none focus:ring-2 focus:ring-va-500/20;
}
.menu-item {
  @apply flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50;
}
</style>
