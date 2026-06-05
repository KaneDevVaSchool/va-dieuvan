<template>
  <div class="w-full pb-2">
    <!-- Sticky breadcrumb / header -->
    <div class="sticky top-0 z-30 -mx-3 mb-5 border-b border-slate-200 bg-white/90 px-3 py-3 backdrop-blur sm:-mx-4 sm:px-4 md:-mx-6 md:px-6 lg:-mx-8 lg:px-8">
      <div class="flex items-center justify-between gap-3">
        <nav class="flex min-w-0 items-center gap-1.5 text-sm">
          <button type="button" class="shrink-0 font-medium text-slate-500 transition hover:text-va-800" @click="goBack">
            Chương trình Đưa đón
          </button>
          <ChevronRightIcon class="h-4 w-4 shrink-0 text-slate-300" />
          <button v-if="program" type="button" class="max-w-[14rem] truncate font-medium text-slate-500 transition hover:text-va-800" @click="goBack">
            {{ program.name }}
          </button>
          <ChevronRightIcon class="h-4 w-4 shrink-0 text-slate-300" />
          <span class="truncate font-semibold text-slate-900">Đăng ký học sinh</span>
        </nav>
        <button
          type="button"
          class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
          @click="goBack"
        >
          <XMarkIcon class="h-4 w-4" /> Đóng
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
      <!-- LEFT: search + results -->
      <div class="space-y-4 xl:col-span-8">
        <!-- Search bar -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="relative">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
            <input
              ref="searchInput"
              v-model="search"
              type="search"
              placeholder="Gõ tên, mã học sinh hoặc lớp để tìm…"
              class="w-full rounded-xl border border-slate-200 bg-white py-3.5 pl-12 pr-11 text-base outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring"
              @keydown.esc="search = ''"
            />
            <ArrowPathIcon v-if="loading" class="absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 animate-spin text-slate-400" />
            <button
              v-else-if="search"
              type="button"
              class="absolute right-3 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
              @click="search = ''"
            >
              <XMarkIcon class="h-4 w-4" />
            </button>
          </div>

          <!-- Quick filters -->
          <div class="mt-3 flex flex-wrap items-center gap-2">
            <select v-model="gradeFilter" :class="filterSelectClass">
              <option value="">Tất cả khối</option>
              <option v-for="g in filterOptions.grades" :key="g" :value="g">Khối {{ g }}</option>
            </select>
            <select v-model="classFilter" :class="filterSelectClass">
              <option value="">Tất cả lớp</option>
              <option v-for="c in filterOptions.classes" :key="c" :value="c">{{ c }}</option>
            </select>
            <label class="ml-auto inline-flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-slate-600 transition hover:bg-slate-50">
              <input type="checkbox" v-model="hideEnrolled" class="h-4 w-4 rounded border-slate-300 text-va-800 focus:ring-va-800/30" />
              Ẩn HS đã trong chương trình
            </label>
          </div>
        </div>

        <!-- Results -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <div class="text-sm text-slate-500">
              <span class="font-semibold text-slate-800">{{ total }}</span> kết quả
            </div>
            <button
              v-if="selectableVisible.length"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium text-va-800 transition hover:bg-va-800/5"
              @click="toggleSelectAllVisible"
            >
              <component :is="allVisibleSelected ? MinusIcon : PlusIcon" class="h-4 w-4" />
              {{ allVisibleSelected ? 'Bỏ chọn trang này' : `Chọn ${selectableVisible.length} HS trang này` }}
            </button>
          </div>

          <!-- Loading skeleton -->
          <div v-if="loading && !students.length" class="divide-y divide-slate-100">
            <div v-for="i in 6" :key="i" class="flex items-center gap-3 px-4 py-3">
              <div class="h-10 w-10 shrink-0 animate-pulse rounded-full bg-slate-100"></div>
              <div class="flex-1 space-y-2">
                <div class="h-3 w-1/3 animate-pulse rounded bg-slate-100"></div>
                <div class="h-2.5 w-1/4 animate-pulse rounded bg-slate-100"></div>
              </div>
            </div>
          </div>

          <!-- Empty -->
          <div v-else-if="!visibleStudents.length" class="px-4 py-16 text-center">
            <UsersIcon class="mx-auto mb-3 h-12 w-12 text-slate-300" />
            <p class="text-base font-medium text-slate-600">{{ search ? 'Không tìm thấy học sinh phù hợp' : 'Không có học sinh để hiển thị' }}</p>
            <p class="mt-1 text-sm text-slate-400">Thử đổi từ khóa hoặc bộ lọc khối / lớp.</p>
          </div>

          <!-- List -->
          <ul v-else class="divide-y divide-slate-100">
            <li
              v-for="s in visibleStudents"
              :key="s.id"
              :class="[
                'flex items-center gap-3 px-4 py-2.5 transition',
                s.enrolled ? 'opacity-60' : 'cursor-pointer hover:bg-slate-50',
                isSelected(s.id) && 'bg-va-800/[0.04]',
              ]"
              @click="!s.enrolled && toggle(s)"
            >
              <input
                type="checkbox"
                :checked="isSelected(s.id)"
                :disabled="s.enrolled"
                class="h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-800/30 disabled:opacity-40"
                @click.stop="!s.enrolled && toggle(s)"
              />
              <span
                class="grid h-10 w-10 shrink-0 place-items-center rounded-full text-sm font-semibold"
                :class="isSelected(s.id) ? 'bg-va-800 text-white' : 'bg-slate-100 text-slate-500'"
              >
                {{ initials(s.full_name) }}
              </span>
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <span class="truncate font-medium text-slate-900">{{ s.full_name }}</span>
                  <span v-if="s.enrolled" class="shrink-0 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-600">Đã trong CT</span>
                </div>
                <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-slate-400">
                  <span class="font-mono">{{ s.code }}</span>
                  <span v-if="s.class_name">· {{ s.class_name }}</span>
                  <span v-if="s.grade">· Khối {{ s.grade }}</span>
                  <span v-if="s.parent_phone" class="hidden sm:inline">· {{ s.parent_phone }}</span>
                </div>
              </div>
              <span v-if="!s.enrolled" :class="transportBadgeClass(s.transport_status)">{{ transportLabel(s.transport_status) }}</span>
            </li>
          </ul>

          <!-- Load more -->
          <div v-if="canLoadMore" class="border-t border-slate-100 p-3 text-center">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 disabled:opacity-50"
              :disabled="loadingMore"
              @click="loadMore"
            >
              <ArrowPathIcon v-if="loadingMore" class="h-4 w-4 animate-spin" />
              <ChevronDownIcon v-else class="h-4 w-4" />
              Tải thêm học sinh
            </button>
          </div>
        </div>
      </div>

      <!-- RIGHT: selected summary -->
      <div class="xl:col-span-4">
        <div class="sticky top-20 space-y-4">
          <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-4 py-3">
              <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-va-800/10 text-va-800">
                  <CheckIcon class="h-5 w-5" />
                </span>
                <div>
                  <div class="text-sm font-semibold text-slate-800">Đã chọn</div>
                  <div class="text-xs text-slate-400">{{ selectedList.length }} học sinh</div>
                </div>
              </div>
              <button
                v-if="selectedList.length"
                type="button"
                class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50"
                @click="clearSelection"
              >
                Xóa hết
              </button>
            </div>

            <!-- Capacity warning -->
            <div v-if="capacity && selectedList.length + enrolledIds.size > capacity" class="flex items-start gap-2 border-b border-amber-100 bg-amber-50 px-4 py-2.5 text-xs text-amber-700">
              <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 shrink-0" />
              <span>Vượt sức chứa: {{ selectedList.length + enrolledIds.size }}/{{ capacity }} chỗ.</span>
            </div>

            <div v-if="!selectedList.length" class="px-4 py-12 text-center">
              <UserPlusIcon class="mx-auto mb-2 h-10 w-10 text-slate-200" />
              <p class="text-sm text-slate-400">Chọn học sinh từ danh sách bên trái.</p>
            </div>
            <ul v-else class="max-h-[24rem] divide-y divide-slate-100 overflow-y-auto">
              <li v-for="s in selectedList" :key="s.id" class="flex items-center gap-2.5 px-4 py-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">{{ initials(s.full_name) }}</span>
                <div class="min-w-0 flex-1">
                  <div class="truncate text-sm font-medium text-slate-800">{{ s.full_name }}</div>
                  <div class="truncate text-xs text-slate-400"><span class="font-mono">{{ s.code }}</span><span v-if="s.class_name"> · {{ s.class_name }}</span></div>
                </div>
                <button
                  type="button"
                  class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                  @click="remove(s.id)"
                >
                  <XMarkIcon class="h-4 w-4" />
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Sticky footer (stays within content, never overlaps sidebar) -->
    <div class="sticky bottom-0 z-30 -mx-3 mt-5 border-t border-slate-200 bg-white/95 px-3 py-3 backdrop-blur sm:-mx-4 sm:px-4 md:-mx-6 md:px-6 lg:-mx-8 lg:px-8">
      <div class="flex items-center justify-between gap-3">
        <p class="text-sm text-slate-600">
          <span class="font-semibold text-slate-900">{{ selectedList.length }}</span> học sinh sẽ được đăng ký
        </p>
        <div class="flex items-center gap-2">
          <Button variant="secondary" @click="goBack">Hủy</Button>
          <Button :loading="saving" :disabled="!selectedList.length" @click="submit">
            <UserPlusIcon class="h-4 w-4" /> Đăng ký {{ selectedList.length || '' }} học sinh
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  MagnifyingGlassIcon,
  XMarkIcon,
  ChevronRightIcon,
  ChevronDownIcon,
  ArrowPathIcon,
  UsersIcon,
  UserPlusIcon,
  CheckIcon,
  PlusIcon,
  MinusIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import { listStudents, enrollStudents, listEnrollments, getProgram } from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const route = useRoute()
const router = useRouter()

const program = ref(null)
const loading = ref(false)
const loadingMore = ref(false)
const saving = ref(false)
const students = ref([])
const total = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const filterOptions = ref({ grades: [], classes: [] })

const search = ref('')
const gradeFilter = ref('')
const classFilter = ref('')
const hideEnrolled = ref(true)

const searchInput = ref(null)
const enrolledIds = ref(new Set())
/** id -> student object (giữ được dù không còn trong kết quả tìm hiện tại) */
const selected = ref(new Map())

let timer = null

const filterSelectClass =
  'rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 outline-none ring-va-800/20 transition focus:border-va-800/40 focus:ring'

const capacity = computed(() => Number(program.value?.settings?.vehicle?.max_capacity || 0))

const visibleStudents = computed(() => {
  const rows = students.value.map((s) => ({ ...s, enrolled: enrolledIds.value.has(s.id) }))
  return hideEnrolled.value ? rows.filter((s) => !s.enrolled) : rows
})

const selectableVisible = computed(() => visibleStudents.value.filter((s) => !s.enrolled))
const allVisibleSelected = computed(
  () => selectableVisible.value.length > 0 && selectableVisible.value.every((s) => selected.value.has(s.id)),
)
const selectedList = computed(() => Array.from(selected.value.values()))
const canLoadMore = computed(() => currentPage.value < lastPage.value)

function isSelected(id) {
  return selected.value.has(id)
}

function toggle(s) {
  const next = new Map(selected.value)
  if (next.has(s.id)) next.delete(s.id)
  else next.set(s.id, s)
  selected.value = next
}

function remove(id) {
  const next = new Map(selected.value)
  next.delete(id)
  selected.value = next
}

function clearSelection() {
  selected.value = new Map()
}

function toggleSelectAllVisible() {
  const next = new Map(selected.value)
  if (allVisibleSelected.value) {
    selectableVisible.value.forEach((s) => next.delete(s.id))
  } else {
    selectableVisible.value.forEach((s) => next.set(s.id, s))
  }
  selected.value = next
}

function initials(name) {
  if (!name) return '?'
  return name.trim().split(/\s+/).slice(-2).map((w) => w[0]).join('').toUpperCase()
}

function transportLabel(s) {
  return { transporting: 'Đang đi tuyến khác', pending: 'Chờ tuyến khác', paused: 'Tuyến tạm dừng', unregistered: 'Chưa đăng ký' }[s] || ''
}
function transportBadgeClass(s) {
  const base = 'hidden shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium sm:inline '
  return base + ({
    transporting: 'bg-sky-50 text-sky-600',
    pending: 'bg-amber-50 text-amber-600',
    paused: 'bg-slate-100 text-slate-500',
    unregistered: 'bg-emerald-50 text-emerald-600',
  }[s] || 'bg-slate-100 text-slate-500')
}

async function load({ append = false } = {}) {
  if (append) loadingMore.value = true
  else loading.value = true
  try {
    const res = await listStudents({
      search: search.value || undefined,
      grade: gradeFilter.value || undefined,
      class_name: classFilter.value || undefined,
      status: 'active',
      per_page: 25,
      page: append ? currentPage.value + 1 : 1,
    })
    const items = res?.items ?? []
    students.value = append ? [...students.value, ...items] : items
    total.value = res?.meta?.total ?? items.length
    currentPage.value = res?.meta?.current_page ?? 1
    lastPage.value = res?.meta?.last_page ?? 1
    if (res?.filter_options) filterOptions.value = res.filter_options
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

function loadMore() {
  if (canLoadMore.value && !loadingMore.value) load({ append: true })
}

watch([search, gradeFilter, classFilter], () => {
  clearTimeout(timer)
  timer = setTimeout(() => load(), 300)
})

function goBack() {
  router.push({ name: 'tpProgramWorkspace', params: { id: route.params.id } })
}

async function submit() {
  if (!selectedList.value.length) return
  saving.value = true
  try {
    const ids = selectedList.value.map((s) => s.id)
    const res = await enrollStudents(route.params.id, ids)
    const enrolled = res?.enrolled ?? ids.length
    const skipped = res?.skipped ?? 0
    showAppSuccess(`Đã đăng ký ${enrolled} học sinh${skipped ? ` · bỏ qua ${skipped} đã có` : ''}.`)
    goBack()
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  load()
  searchInput.value?.focus()
  try {
    program.value = await getProgram(route.params.id)
  } catch { /* header context optional */ }
  try {
    const res = await listEnrollments(route.params.id)
    enrolledIds.value = new Set((res?.items ?? []).map((e) => e.student_id))
  } catch { /* non-blocking */ }
})
</script>
