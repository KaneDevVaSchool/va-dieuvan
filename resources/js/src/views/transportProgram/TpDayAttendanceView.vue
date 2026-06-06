<template>
  <div class="mx-auto max-w-6xl space-y-4 pb-28">

    <!-- ── Header ───────────────────────────────────────────────────────────── -->
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div class="min-w-0 space-y-0.5">
        <button
          type="button"
          class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600"
          @click="goBack"
        >
          <ArrowLeftIcon class="h-3.5 w-3.5" />
          Quay lại
        </button>
        <nav class="text-xs text-slate-500" aria-label="breadcrumb">
          <span>Chương trình Đưa đón</span>
          <span v-if="data?.day?.program_name"> › {{ data.day.program_name }}</span>
          <span> › Điểm danh theo Ngày</span>
        </nav>
        <div class="flex flex-wrap items-center gap-2 pt-0.5">
          <h1 class="text-xl font-bold tracking-tight text-slate-900">Điểm danh theo Ngày</h1>
          <span class="rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-800">
            {{ shiftLabel }}
          </span>
          <span
            v-if="data?.attendance_status === 'confirmed'"
            class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800"
          >
            <CheckCircleIcon class="h-3.5 w-3.5" />
            Đã xác nhận
          </span>
          <span
            v-else-if="data?.attendance_status === 'draft'"
            class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800"
          >
            <ClockIcon class="h-3.5 w-3.5" />
            Bản nháp
          </span>
        </div>
        <p v-if="data?.day" class="flex flex-wrap items-center gap-1.5 text-sm text-slate-500">
          <CalendarDaysIcon class="h-4 w-4 shrink-0" />
          {{ formatLongDate(data.day.scheduled_date) }}
          <span v-if="shiftDepartureDisplay" class="before:mr-1 before:content-['·']">{{ shiftDepartureDisplay }}</span>
          <span v-if="data.day.driver_name" class="before:mr-1 before:content-['·']">
            <TruckIcon class="mb-0.5 mr-0.5 inline h-3.5 w-3.5" />{{ data.day.driver_name }}
          </span>
        </p>
      </div>

      <div class="flex shrink-0 flex-wrap items-center gap-2">
        <Button
          variant="secondary"
          :disabled="loading || !data"
          :loading="exporting"
          @click="onExport"
        >
          <ArrowDownTrayIcon class="h-4 w-4" />
          Xuất Excel
        </Button>
        <Button
          variant="secondary"
          :disabled="loading || !data"
          :loading="notifying"
          @click="onNotifyParents"
        >
          <BellIcon class="h-4 w-4" />
          Thông báo PH
        </Button>
      </div>
    </div>

    <!-- ── Date nav + shift toggle ─────────────────────────────────────────── -->
    <div
      v-if="data"
      class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm"
    >
      <div class="flex items-center gap-1">
        <button
          type="button"
          :disabled="!hasPrevDay"
          class="rounded-lg border border-slate-200 p-1.5 text-slate-500 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
          aria-label="Ngày trước"
          @click="shiftDay(-1)"
        >
          <ChevronLeftIcon class="h-4 w-4" />
        </button>
        <span class="min-w-[96px] text-center text-sm font-semibold text-slate-800">
          {{ formatShortDate(data.day.scheduled_date) }}
        </span>
        <button
          type="button"
          :disabled="!hasNextDay"
          class="rounded-lg border border-slate-200 p-1.5 text-slate-500 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
          aria-label="Ngày sau"
          @click="shiftDay(1)"
        >
          <ChevronRightIcon class="h-4 w-4" />
        </button>
      </div>

      <div
        class="flex items-center rounded-lg border border-slate-200 p-0.5 text-sm"
        role="group"
        aria-label="Chọn ca"
      >
        <button
          type="button"
          class="rounded-md px-4 py-1.5 font-medium transition disabled:cursor-not-allowed disabled:opacity-40"
          :class="isMorningShift ? 'bg-va-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
          :disabled="!hasMorningShift"
          @click="trySwitchShift('morning')"
        >Sáng</button>
        <button
          type="button"
          class="rounded-md px-4 py-1.5 font-medium transition disabled:cursor-not-allowed disabled:opacity-40"
          :class="!isMorningShift ? 'bg-va-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
          :disabled="!hasAfternoonShift"
          @click="trySwitchShift('afternoon')"
        >Chiều</button>
      </div>
    </div>

    <!-- ── Loading ──────────────────────────────────────────────────────────── -->
    <template v-if="loading">
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div v-for="i in 4" :key="i" class="h-24 animate-pulse rounded-2xl bg-slate-100" />
      </div>
      <div class="h-12 animate-pulse rounded-2xl bg-slate-100" />
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th v-for="i in 6" :key="i" class="h-9 px-3 py-2.5">
                <div class="h-3 w-full rounded bg-slate-200" />
              </th>
            </tr>
          </thead>
          <tbody>
            <AttendanceSkeletonRow v-for="i in 8" :key="i" />
          </tbody>
        </table>
      </div>
    </template>

    <template v-else-if="data">
      <!-- Stats ── -->
      <AttendanceStatsBar :summary="data.summary" />

      <!-- Confirmed banner ── -->
      <div
        v-if="data.attendance_status === 'confirmed'"
        class="flex items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
      >
        <div class="flex items-center gap-2 text-sm font-medium text-emerald-800">
          <LockClosedIcon class="h-4 w-4 shrink-0" />
          Điểm danh đã xác nhận. Dữ liệu ở chế độ chỉ đọc.
        </div>
        <Button
          v-if="canReopen"
          variant="secondary"
          class="shrink-0 text-xs"
          :loading="saving"
          @click="onReopen"
        >Mở lại chỉnh sửa</Button>
      </div>

      <!-- Filter bar ── -->
      <AppFilterBar>
        <div class="flex flex-wrap items-center gap-x-2 gap-y-2">
          <div class="relative min-w-[180px] flex-1">
            <MagnifyingGlassIcon class="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 pointer-events-none" />
            <input
              v-model="filters.q"
              type="search"
              placeholder="Tìm học sinh, mã, SĐT…"
              class="h-9 w-full rounded-lg border-0 bg-white/90 pl-8 pr-3 text-sm ring-1 ring-slate-200/80 focus:ring-2 focus:ring-teal-500/30"
              aria-label="Tìm học sinh"
            />
          </div>

          <select
            v-model="filters.className"
            class="h-9 rounded-lg border-0 bg-white/90 pl-2 pr-6 text-sm ring-1 ring-slate-200/80 focus:ring-2 focus:ring-teal-500/30"
            aria-label="Lọc lớp"
          >
            <option value="">Tất cả lớp</option>
            <option v-for="c in classOptions" :key="c" :value="c">{{ c }}</option>
          </select>

          <select
            v-model="filters.status"
            class="h-9 rounded-lg border-0 bg-white/90 pl-2 pr-6 text-sm ring-1 ring-slate-200/80 focus:ring-2 focus:ring-teal-500/30"
            aria-label="Lọc trạng thái"
          >
            <option value="">Tất cả trạng thái</option>
            <option value="present">Có mặt</option>
            <option value="excused">Vắng có phép</option>
            <option value="unexcused">Vắng không phép</option>
          </select>

          <select
            v-if="pickupOptions.length"
            v-model="filters.pickup"
            class="h-9 rounded-lg border-0 bg-white/90 pl-2 pr-6 text-sm ring-1 ring-slate-200/80 focus:ring-2 focus:ring-teal-500/30"
            aria-label="Lọc điểm đón"
          >
            <option value="">Tất cả điểm đón</option>
            <option v-for="p in pickupOptions" :key="p" :value="p">{{ p }}</option>
          </select>

          <button
            v-if="hasActiveFilter"
            type="button"
            class="inline-flex items-center gap-1 text-xs text-slate-500 hover:text-rose-600"
            @click="clearFilters"
          >
            <XMarkIcon class="h-3.5 w-3.5" />
            Xóa lọc
          </button>

          <Button
            class="ml-auto shrink-0"
            variant="secondary"
            :disabled="saving || isConfirmed"
            :loading="markingAllPresent"
            @click="onMarkAllPresent"
          >
            <CheckIcon class="h-4 w-4" />
            Đánh dấu tất cả có mặt
          </Button>
        </div>
      </AppFilterBar>

      <!-- Table ── -->
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-100 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
              <tr>
                <th scope="col" class="px-4 py-3">Học sinh</th>
                <th scope="col" class="px-3 py-3">Lớp</th>
                <th scope="col" class="px-3 py-3">Điểm đón</th>
                <th scope="col" class="px-3 py-3">Trạng thái</th>
                <th scope="col" class="px-3 py-3">Lý do vắng</th>
                <th scope="col" class="px-3 py-3 text-center">Điểm danh</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="s in filteredItems"
                :key="s.student_id"
                class="border-b border-slate-100 transition-colors last:border-0"
                :class="[rowBg(s), pendingRows.has(s.student_id) ? 'opacity-60' : '']"
              >
                <!-- Student ── -->
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <div
                      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                      :class="avatarClass(s)"
                    >
                      {{ initials(s.full_name) }}
                    </div>
                    <div class="min-w-0">
                      <div class="truncate font-semibold text-slate-900">{{ s.full_name }}</div>
                      <div class="text-xs text-slate-400">
                        {{ s.code }}
                        <span v-if="s.parent_phone" class="before:mx-1 before:content-['·']">PH: {{ s.parent_phone }}</span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Class ── -->
                <td class="px-3 py-3">
                  <span v-if="s.class_name" class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                    {{ s.class_name }}
                  </span>
                  <span v-else class="text-slate-400">—</span>
                </td>

                <!-- Pickup ── -->
                <td class="px-3 py-3 text-sm text-slate-600">{{ s.pickup_point || '—' }}</td>

                <!-- Status badge ── -->
                <td class="px-3 py-3">
                  <span :class="statusBadgeClass(s)">{{ statusLabel(s) }}</span>
                </td>

                <!-- Reason ── -->
                <td class="px-3 py-3">
                  <div
                    v-if="s.status === 'absent'"
                    class="flex w-full min-w-[200px] max-w-[260px] flex-col gap-1.5"
                  >
                    <select
                      v-if="reasons.length"
                      :value="s.reason_code || ''"
                      :disabled="isConfirmed || pendingRows.has(s.student_id)"
                      class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-700 focus:border-teal-400 focus:outline-none focus:ring-1 focus:ring-teal-400 disabled:cursor-not-allowed disabled:opacity-60"
                      @change="onReasonChange(s, $event.target.value)"
                    >
                      <option value="">Chọn lý do…</option>
                      <option v-for="r in reasons" :key="r.code" :value="r.code">{{ r.label_vi }}</option>
                    </select>
                    <input
                      type="text"
                      :value="s.absence_reason || ''"
                      :disabled="isConfirmed || pendingRows.has(s.student_id)"
                      :placeholder="reasons.length ? 'Ghi chú thêm (tuỳ chọn)…' : 'Nhập lý do vắng…'"
                      maxlength="500"
                      class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-700 placeholder:text-slate-400 focus:border-teal-400 focus:outline-none focus:ring-1 focus:ring-teal-400 disabled:cursor-not-allowed disabled:opacity-60"
                      @blur="onAbsenceNoteBlur(s, $event.target.value)"
                      @keydown.enter.prevent="blurNoteInput($event)"
                    />
                  </div>
                  <span v-else class="text-slate-300">—</span>
                </td>

                <!-- Toggle ── -->
                <td class="px-3 py-3 text-center">
                  <div class="flex justify-center">
                    <template v-if="pendingRows.has(s.student_id)">
                      <span class="inline-flex h-6 w-11 items-center justify-center">
                        <span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-slate-600" />
                      </span>
                    </template>
                    <Toggle
                      v-else
                      :model-value="s.status === 'attending'"
                      :disabled="isConfirmed || saving"
                      @update:model-value="togglePresent(s)"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty state ── -->
        <div v-if="!filteredItems.length && !loading" class="flex flex-col items-center gap-2 py-14">
          <UsersIcon class="h-10 w-10 text-slate-300" />
          <p class="text-sm text-slate-500">
            <span v-if="hasActiveFilter">Không có học sinh phù hợp bộ lọc.</span>
            <span v-else>Chưa có học sinh nào trong ngày này.</span>
          </p>
          <button
            v-if="hasActiveFilter"
            type="button"
            class="text-xs text-teal-600 hover:underline"
            @click="clearFilters"
          >Xóa bộ lọc</button>
        </div>
      </div>
    </template>

    <!-- Sticky footer (trong vùng main — không đè sidebar) -->
    <div
      v-if="data && !loading"
      class="sticky bottom-0 z-30 pt-2 supports-[padding:max(0px)]:pb-[max(0.75rem,env(safe-area-inset-bottom))]"
    >
      <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white/95 shadow-2xl shadow-slate-900/15 backdrop-blur-md"
      >
          <!-- Warning ── -->
          <div
            v-if="data.missing_reason_count > 0"
            class="flex items-center gap-2 border-b border-amber-100 bg-amber-50 px-4 py-2.5 text-sm"
          >
            <ExclamationTriangleIcon class="h-4 w-4 shrink-0 text-amber-600" />
            <span class="font-medium text-amber-900">
              {{ data.missing_reason_count }} học sinh chưa có lý do vắng (chọn danh mục hoặc nhập ghi chú)
            </span>
          </div>

          <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-3">
            <!-- Summary ── -->
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-600">
              <span>
                <span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-emerald-500" />
                Có mặt: <strong class="text-slate-800">{{ data.summary.present }}</strong>
              </span>
              <span>
                <span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-amber-400" />
                Có phép: <strong class="text-slate-800">{{ data.summary.excused }}</strong>
              </span>
              <span>
                <span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-rose-500" />
                Không phép: <strong class="text-slate-800">{{ data.summary.unexcused }}</strong>
              </span>
            </div>

            <!-- Actions ── -->
            <div v-if="!isConfirmed" class="flex items-center gap-2">
              <Button
                variant="secondary"
                :disabled="saving"
                :loading="savingDraft"
                @click="onSaveDraft"
              >Lưu nháp</Button>
              <Button
                :disabled="saving || data.missing_reason_count > 0"
                :loading="confirming"
                @click="onConfirm"
              >
                <LockClosedIcon class="h-4 w-4" />
                Xác nhận điểm danh
              </Button>
            </div>
            <div v-else class="text-sm font-medium text-emerald-700">
              <CheckCircleIcon class="mr-1 inline h-4 w-4" />
              Đã xác nhận lúc {{ formatTime(data.attendance_confirmed_at) }}
            </div>
          </div>
        </div>
    </div>

  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  BellIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  CheckIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  LockClosedIcon,
  MagnifyingGlassIcon,
  TruckIcon,
  UsersIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

import Button from '../../components/ui/Button.vue'
import Toggle from '../../components/ui/Toggle.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AttendanceStatsBar from './attendance/AttendanceStatsBar.vue'
import AttendanceSkeletonRow from './attendance/AttendanceSkeletonRow.vue'

import {
  confirmDayAttendance,
  downloadDayAttendanceExport,
  getDayAttendance,
  listAbsenceReasons,
  listProgramDays,
  markDayAbsence,
  markDayPresent,
  notifyDayParents,
  reopenDayAttendance,
  saveAttendanceDraft,
} from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { slotsForProgram } from '../../composables/tpProgramSlots'
import { confirmAction } from '../../composables/useConfirm'
import { useAuthStore } from '../../store'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const loading = ref(false)
const saving = ref(false)
const savingDraft = ref(false)
const confirming = ref(false)
const markingAllPresent = ref(false)
const exporting = ref(false)
const notifying = ref(false)
const pendingRows = ref(new Set())

const data = ref(null)
const reasons = ref([])
const siblingDays = ref([])

const filters = reactive({ q: '', className: '', status: '', pickup: '' })

const isConfirmed = computed(() => data.value?.attendance_status === 'confirmed')

const canReopen = computed(() =>
  auth.user?.is_superadmin || auth.hasPermission('data.override_confirmed'),
)

const classOptions = computed(() => {
  const s = new Set((data.value?.items || []).map((i) => i.class_name).filter(Boolean))
  return [...s].sort()
})

const pickupOptions = computed(() => {
  const s = new Set((data.value?.items || []).map((i) => i.pickup_point).filter(Boolean))
  return [...s].sort()
})

const hasActiveFilter = computed(
  () => !!(filters.q || filters.className || filters.status || filters.pickup),
)

const filteredItems = computed(() => {
  let rows = data.value?.items || []
  const q = filters.q.trim().toLowerCase()
  if (q) {
    rows = rows.filter(
      (r) =>
        r.full_name?.toLowerCase().includes(q) ||
        r.code?.toLowerCase().includes(q) ||
        r.parent_phone?.includes(q),
    )
  }
  if (filters.className) rows = rows.filter((r) => r.class_name === filters.className)
  if (filters.pickup) rows = rows.filter((r) => r.pickup_point === filters.pickup)
  if (filters.status) rows = rows.filter((r) => r.display_status === filters.status)
  return rows
})

const programForSlots = computed(() => {
  const day = data.value?.day
  if (!day) return null
  return {
    settings: day.settings,
    departure_time: day.departure_time,
    return_time: day.return_time,
  }
})

const programSlots = computed(() => slotsForProgram(programForSlots.value))

const hasMorningShift = computed(() => programSlots.value.some((s) => s.shift === 'morning'))
const hasAfternoonShift = computed(() => programSlots.value.some((s) => s.shift === 'afternoon'))

const activeShift = computed(() => {
  const q = route.query.shift
  const shifts = programSlots.value.map((s) => s.shift)
  if (q === 'morning' || q === 'afternoon') {
    if (shifts.length && !shifts.includes(q)) {
      return shifts[0] || 'morning'
    }
    return q
  }
  return shifts[0] || 'morning'
})

const isMorningShift = computed(() => activeShift.value === 'morning')

const usesPerShiftAttendance = computed(() => {
  if (data.value && typeof data.value.multi_slot === 'boolean') {
    return data.value.multi_slot
  }
  return programSlots.value.length > 1
})

function shiftQueryOpts() {
  const shift = activeShift.value
  if (usesPerShiftAttendance.value) {
    return { shift }
  }
  if (!programForSlots.value && (route.query.shift === 'morning' || route.query.shift === 'afternoon')) {
    return { shift: route.query.shift }
  }
  return {}
}

const shiftLabel = computed(() => (isMorningShift.value ? 'Chuyến Sáng' : 'Chuyến Chiều'))

function formatClockTime(value) {
  if (!value) return ''
  return String(value).slice(0, 5)
}

const shiftDepartureDisplay = computed(() => {
  const slot = programSlots.value.find((s) => s.shift === activeShift.value)
  return slot?.departure ? formatClockTime(slot.departure) : ''
})

const sortedSiblings = computed(() =>
  [...siblingDays.value].sort((a, b) => a.scheduled_date.localeCompare(b.scheduled_date)),
)

const hasPrevDay = computed(() => {
  const idx = sortedSiblings.value.findIndex((d) => d.id === Number(route.params.dayId))
  return idx > 0
})
const hasNextDay = computed(() => {
  const idx = sortedSiblings.value.findIndex((d) => d.id === Number(route.params.dayId))
  return idx >= 0 && idx < sortedSiblings.value.length - 1
})

async function load() {
  loading.value = true
  data.value = null
  try {
    const [att, reasonList] = await Promise.all([
      getDayAttendance(route.params.dayId, shiftQueryOpts()),
      listAbsenceReasons().catch(() => ({ items: [] })),
    ])
    data.value = att
    reasons.value = reasonList.items || []

    if (att?.day?.program_id) {
      const month = att.day.scheduled_date?.slice(0, 7)
      const daysPayload = await listProgramDays(att.day.program_id, { month })
      siblingDays.value = daysPayload?.items || daysPayload || []
    }
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

watch(() => route.params.dayId, () => { if (route.params.dayId) load() })

watch(activeShift, (next, prev) => {
  if (prev && next !== prev && route.params.dayId) load()
})

watch(
  () => [data.value?.day?.id, route.query.shift, programSlots.value.length],
  () => {
    if (!data.value?.day?.id) return
    const q = route.query.shift
    const shifts = programSlots.value.map((s) => s.shift)
    if ((q === 'morning' || q === 'afternoon') && shifts.includes(q)) return
    const fallback = shifts[0] || 'morning'
    if (q !== fallback) {
      router.replace({
        name: 'tpDayAttendance',
        params: { dayId: route.params.dayId },
        query: usesPerShiftAttendance.value ? { shift: fallback } : {},
      })
    }
  },
)

function categoryForReason(code) {
  const r = reasons.value.find((x) => x.code === code)
  return r?.default_category || 'excused'
}

function absenceTypeForCategory(category) {
  return category === 'excused' ? 'parent_notified' : 'no_notice'
}

async function togglePresent(s) {
  if (isConfirmed.value) return
  pendingRows.value = new Set([...pendingRows.value, s.student_id])
  saving.value = true
  try {
    if (s.status === 'attending') {
      data.value = await markDayAbsence(route.params.dayId, {
        student_ids: [s.student_id],
        absence_type: 'no_notice',
        category: 'unexcused',
        ...shiftQueryOpts(),
      })
    } else {
      data.value = await markDayPresent(route.params.dayId, { student_ids: [s.student_id], ...shiftQueryOpts() })
    }
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    const next = new Set(pendingRows.value)
    next.delete(s.student_id)
    pendingRows.value = next
    saving.value = false
  }
}

async function onReasonChange(s, code) {
  if (!code || isConfirmed.value) return
  const category = categoryForReason(code)
  pendingRows.value = new Set([...pendingRows.value, s.student_id])
  saving.value = true
  try {
    data.value = await markDayAbsence(route.params.dayId, {
      student_ids: [s.student_id],
      absence_type: absenceTypeForCategory(category),
      category,
      reason_code: code,
      absence_reason: s.absence_reason || undefined,
      ...shiftQueryOpts(),
    })
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    const next = new Set(pendingRows.value)
    next.delete(s.student_id)
    pendingRows.value = next
    saving.value = false
  }
}

function blurNoteInput(ev) {
  ev?.target?.blur?.()
}

async function onAbsenceNoteBlur(s, rawNote) {
  if (isConfirmed.value) return
  const note = (rawNote || '').trim()
  const prev = (s.absence_reason || '').trim()
  if (note === prev) return
  if (!note && !s.reason_code) return

  pendingRows.value = new Set([...pendingRows.value, s.student_id])
  saving.value = true
  try {
    const category = s.category || (s.reason_code ? categoryForReason(s.reason_code) : 'unexcused')
    const payload = {
      student_ids: [s.student_id],
      absence_type: s.absence_type || absenceTypeForCategory(category),
      category,
      absence_reason: note || undefined,
      ...shiftQueryOpts(),
    }
    if (s.reason_code) {
      payload.reason_code = s.reason_code
    }
    data.value = await markDayAbsence(route.params.dayId, payload)
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    const next = new Set(pendingRows.value)
    next.delete(s.student_id)
    pendingRows.value = next
    saving.value = false
  }
}

async function onMarkAllPresent() {
  const ok = await confirmAction({
    title: 'Đánh dấu tất cả có mặt',
    message: 'Hành động này sẽ xóa toàn bộ vắng trong ngày và đặt tất cả học sinh thành Có mặt. Tiếp tục?',
    confirmLabel: 'Đánh dấu tất cả có mặt',
  })
  if (!ok) return

  markingAllPresent.value = true
  saving.value = true
  try {
    data.value = await markDayPresent(route.params.dayId, { mark_all: true, ...shiftQueryOpts() })
    showAppSuccess('Đã đánh dấu tất cả học sinh có mặt.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    markingAllPresent.value = false
    saving.value = false
  }
}

async function onSaveDraft() {
  savingDraft.value = true
  saving.value = true
  try {
    data.value = await saveAttendanceDraft(route.params.dayId, shiftQueryOpts())
    showAppSuccess('Đã lưu nháp điểm danh.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    savingDraft.value = false
    saving.value = false
  }
}

async function onConfirm() {
  if (data.value?.missing_reason_count > 0) return

  const ok = await confirmAction({
    title: 'Xác nhận điểm danh',
    message: `Xác nhận điểm danh cho ngày ${formatShortDate(data.value?.day?.scheduled_date)}?\nSau khi xác nhận, dữ liệu sẽ ở chế độ chỉ đọc.`,
    confirmLabel: 'Xác nhận',
  })
  if (!ok) return

  confirming.value = true
  saving.value = true
  try {
    data.value = await confirmDayAttendance(route.params.dayId, data.value.attendance_lock_version, shiftQueryOpts())
    showAppSuccess('Điểm danh đã được xác nhận thành công.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    confirming.value = false
    saving.value = false
  }
}

async function onReopen() {
  const ok = await confirmAction({
    title: 'Mở lại điểm danh',
    message: 'Mở lại điểm danh đã xác nhận để chỉnh sửa?',
    confirmLabel: 'Mở lại',
  })
  if (!ok) return

  saving.value = true
  try {
    data.value = await reopenDayAttendance(route.params.dayId, shiftQueryOpts())
    showAppSuccess('Đã mở lại điểm danh.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    saving.value = false
  }
}

async function onExport() {
  exporting.value = true
  try {
    await downloadDayAttendanceExport(route.params.dayId, shiftQueryOpts())
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    exporting.value = false
  }
}

async function onNotifyParents() {
  if (!data.value?.summary?.absent_total) {
    showAppSuccess('Không có học sinh vắng để thông báo.')
    return
  }
  const ok = await confirmAction({
    title: 'Thông báo phụ huynh',
    message: `Gửi thông báo vắng đến ${data.value.summary.absent_total} phụ huynh học sinh vắng hôm nay?`,
    confirmLabel: 'Gửi thông báo',
  })
  if (!ok) return

  notifying.value = true
  try {
    const res = await notifyDayParents(route.params.dayId)
    showAppSuccess(`Đã xếp hàng thông báo cho ${res.queued} phụ huynh${res.skipped_no_phone ? ` (${res.skipped_no_phone} không có SĐT)` : ''}.`)
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    notifying.value = false
  }
}

function clearFilters() {
  filters.q = ''
  filters.className = ''
  filters.status = ''
  filters.pickup = ''
}

async function shiftDay(delta) {
  const idx = sortedSiblings.value.findIndex((d) => d.id === Number(route.params.dayId))
  const next = sortedSiblings.value[idx + delta]
  if (next) {
    const query = usesPerShiftAttendance.value ? { shift: activeShift.value } : {}
    await router.push({ name: 'tpDayAttendance', params: { dayId: next.id }, query })
  }
}

function trySwitchShift(which) {
  if (which === activeShift.value) return
  if (which === 'morning' && !hasMorningShift.value) return
  if (which === 'afternoon' && !hasAfternoonShift.value) return
  router.replace({
    name: 'tpDayAttendance',
    params: { dayId: route.params.dayId },
    query: { shift: which },
  })
}

function goBack() {
  if (data.value?.day?.program_id) {
    router.push({ name: 'tpProgramWorkspace', params: { id: data.value.day.program_id } })
  } else {
    router.back()
  }
}

function initials(name) {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  return (parts.length >= 2 ? parts[parts.length - 2][0] + parts[parts.length - 1][0] : parts[0][0])
    .toUpperCase()
}

const AVATAR_COLORS = [
  'bg-sky-100 text-sky-700',
  'bg-violet-100 text-violet-700',
  'bg-teal-100 text-teal-700',
  'bg-orange-100 text-orange-700',
  'bg-pink-100 text-pink-700',
  'bg-indigo-100 text-indigo-700',
  'bg-lime-100 text-lime-700',
]

function avatarClass(s) {
  if (s.status === 'absent') return 'bg-rose-100 text-rose-600'
  return AVATAR_COLORS[s.student_id % AVATAR_COLORS.length]
}

function statusLabel(s) {
  if (s.display_status === 'present') return 'Có mặt'
  if (s.display_status === 'excused') return 'Vắng có phép'
  return 'Vắng không phép'
}

function statusBadgeClass(s) {
  if (s.display_status === 'present')
    return 'inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800'
  if (s.display_status === 'excused')
    return 'inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-900'
  return 'inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-800'
}

function rowBg(s) {
  if (s.display_status === 'excused') return 'bg-amber-50/40 hover:bg-amber-50/70'
  if (s.display_status === 'unexcused') return 'bg-rose-50/40 hover:bg-rose-50/70'
  return 'hover:bg-slate-50/60'
}

function formatShortDate(iso) {
  if (!iso) return '—'
  const [y, m, d] = iso.split('-')
  return `${d}/${m}/${y}`
}

function formatLongDate(iso) {
  if (!iso) return ''
  const dt = new Date(`${iso}T12:00:00`)
  const wd = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'][dt.getDay()]
  return `${wd}, ${formatShortDate(iso)}`
}

function formatTime(isoOrDatetime) {
  if (!isoOrDatetime) return ''
  try {
    return new Date(isoOrDatetime).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
  } catch {
    return ''
  }
}

onMounted(load)
</script>
