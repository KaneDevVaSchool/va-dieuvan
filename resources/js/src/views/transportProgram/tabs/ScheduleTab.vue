<template>
  <div class="space-y-4">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <button
          class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50"
          aria-label="Tháng trước"
          @click="shiftMonth(-1)"
        >
          <ChevronLeftIcon class="h-5 w-5" />
        </button>
        <div class="min-w-[10rem] text-center text-lg font-bold text-slate-900">{{ monthLabel }}</div>
        <button
          class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50"
          aria-label="Tháng sau"
          @click="shiftMonth(1)"
        >
          <ChevronRightIcon class="h-5 w-5" />
        </button>
        <button
          class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
          @click="goToday"
        >
          Hôm nay
        </button>
      </div>

      <!-- Legend -->
      <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> Vận hành</span>
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span> Học bù</span>
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span> Đã hủy</span>
      </div>
    </div>

    <!-- Summary chips -->
    <div class="flex flex-wrap gap-2 text-sm">
      <span class="rounded-full bg-emerald-50 px-3 py-1 font-medium text-emerald-700">{{ counts.operating }} ngày vận hành</span>
      <span v-if="counts.makeup" class="rounded-full bg-amber-50 px-3 py-1 font-medium text-amber-700">{{ counts.makeup }} ngày học bù</span>
      <span v-if="counts.cancelled" class="rounded-full bg-rose-50 px-3 py-1 font-medium text-rose-700">{{ counts.cancelled }} ngày hủy</span>
      <span class="rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-600">{{ counts.expected }} lượt học sinh dự kiến</span>
    </div>

    <div v-if="loading" class="flex items-center justify-center rounded-2xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> Đang tải lịch…
    </div>

    <!-- Calendar -->
    <div v-else class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="grid grid-cols-7 border-b border-slate-100 bg-slate-50 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
        <div v-for="w in weekHeaders" :key="w" class="py-2.5">{{ w }}</div>
      </div>
      <div class="grid grid-cols-7">
        <div
          v-for="(cell, i) in cells"
          :key="i"
          :class="[
            'min-h-[6.5rem] border-b border-r border-slate-100 p-1.5 transition',
            cell.date ? '' : 'bg-slate-50/40',
            cell.day ? 'cursor-pointer hover:bg-va-800/[0.04]' : '',
          ]"
          @click="cell.day && openDay(cell.day)"
        >
          <template v-if="cell.date">
            <div class="flex items-center justify-between">
              <span
                :class="[
                  'grid h-6 w-6 place-items-center rounded-full text-xs font-semibold',
                  cell.isToday ? 'bg-va-800 text-white' : 'text-slate-500',
                ]"
              >{{ cell.dayNum }}</span>
              <span
                v-if="cell.day"
                class="h-2 w-2 rounded-full"
                :class="dotColor(cell.day.day_type)"
              ></span>
            </div>

            <div v-if="cell.day" class="mt-1 space-y-1">
              <span
                v-if="cell.day.day_type === 'cancelled'"
                class="block truncate rounded bg-rose-50 px-1.5 py-0.5 text-[11px] font-medium text-rose-600"
              >Đã hủy</span>
              <template v-else>
                <span
                  v-for="trip in tripsFor(cell.day)"
                  :key="trip.key"
                  class="flex items-center gap-1 truncate rounded px-1.5 py-0.5 text-[11px] font-medium"
                  :class="trip.cls"
                >
                  <component :is="trip.icon" class="h-3 w-3 shrink-0" />
                  {{ trip.label }}
                </span>
                <span class="block text-[11px] text-slate-400">SS {{ cell.day.expected_count }}</span>
              </template>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Day detail drawer -->
    <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-150"
      leave-to-class="opacity-0"
    >
      <div v-if="selected" class="fixed inset-0 z-50 bg-slate-900/40" @click="selected = null">
        <Transition
          enter-active-class="transition-transform duration-200 ease-out"
          enter-from-class="translate-x-full"
          leave-active-class="transition-transform duration-150 ease-in"
          leave-to-class="translate-x-full"
          appear
        >
          <aside
            v-if="selected"
            class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-2xl"
            @click.stop
          >
            <header class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
              <div>
                <div class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ weekdayLong(selected.scheduled_date) }}</div>
                <h3 class="text-xl font-bold text-slate-900">{{ formatLong(selected.scheduled_date) }}</h3>
                <span :class="dayBadge(selected.day_type)">{{ dayTypeLabel(selected.day_type) }}</span>
              </div>
              <button class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100" @click="selected = null">
                <XMarkIcon class="h-5 w-5" />
              </button>
            </header>

            <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
              <div class="grid grid-cols-2 gap-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                  <div class="text-xs text-slate-400">Sĩ số dự kiến</div>
                  <div class="mt-0.5 text-2xl font-bold text-slate-900">{{ selected.expected_count }}</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                  <div class="text-xs text-slate-400">Trạng thái chạy</div>
                  <div class="mt-0.5 text-base font-semibold" :class="execTextClass(selected)">{{ execLabel(selected) }}</div>
                </div>
              </div>

              <!-- Trips -->
              <div v-if="selected.day_type !== 'cancelled'">
                <div class="mb-1.5 text-sm font-semibold text-slate-700">Lịch chạy trong ngày</div>
                <div class="space-y-2">
                  <div
                    v-for="trip in tripsFor(selected)"
                    :key="trip.key"
                    class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"
                  >
                    <span class="grid h-9 w-9 place-items-center rounded-lg" :class="trip.iconWrap">
                      <component :is="trip.icon" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                      <div class="text-sm font-semibold text-slate-800">{{ trip.title }}</div>
                      <div class="text-xs text-slate-500">{{ trip.time }}</div>
                    </div>
                  </div>
                  <p v-if="!tripsFor(selected).length" class="rounded-xl border border-dashed border-slate-200 p-3 text-center text-xs text-slate-400">
                    Chưa cấu hình chuyến cho ngày này.
                  </p>
                </div>
              </div>

              <!-- Driver -->
              <div class="rounded-xl border border-slate-200 p-3">
                <div class="text-xs text-slate-400">Tài xế hiệu lực</div>
                <div class="mt-0.5 flex items-center gap-2">
                  <UserCircleIcon class="h-5 w-5 text-slate-400" />
                  <span class="text-sm font-medium text-slate-800">{{ selected.effective_driver?.full_name || '— chưa gán —' }}</span>
                </div>
              </div>

              <div v-if="selected.notes" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                {{ selected.notes }}
              </div>
            </div>

            <footer class="border-t border-slate-100 px-5 py-4 space-y-2">
              <template v-if="tripsFor(selected).length > 1">
                <Button
                  v-for="trip in tripsFor(selected)"
                  :key="trip.key"
                  variant="secondary"
                  class="w-full justify-center"
                  @click="goAttendance(selected, trip.key)"
                >
                  <ClipboardDocumentCheckIcon class="h-4 w-4" /> Điểm danh {{ trip.label }}
                </Button>
              </template>
              <Button v-else class="w-full justify-center" @click="goAttendance(selected)">
                <ClipboardDocumentCheckIcon class="h-4 w-4" /> Điểm danh chuyến này
              </Button>
            </footer>
          </aside>
        </Transition>
      </div>
    </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  ArrowPathIcon,
  XMarkIcon,
  SunIcon,
  MoonIcon,
  UserCircleIcon,
  ClipboardDocumentCheckIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../../components/ui/Button.vue'
import { listProgramDays } from '../../../api/transportProgram'
import { showAppErrorFromApi } from '../../../composables/appMessage'

const props = defineProps({ program: { type: Object, required: true } })
const router = useRouter()

const loading = ref(false)
const days = ref([])
const month = ref(new Date().toISOString().slice(0, 7))
const selected = ref(null)

const weekHeaders = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN']
const WD_LONG = ['Chủ nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy']

const dayByDate = computed(() => {
  const m = {}
  for (const d of days.value) m[d.scheduled_date] = d
  return m
})

const monthLabel = computed(() => {
  const [y, m] = month.value.split('-')
  return `Tháng ${Number(m)} / ${y}`
})

const counts = computed(() => {
  const c = { operating: 0, makeup: 0, cancelled: 0, expected: 0 }
  for (const d of days.value) {
    if (d.day_type === 'operating') c.operating++
    else if (d.day_type === 'makeup') c.makeup++
    else if (d.day_type === 'cancelled') c.cancelled++
    if (d.day_type !== 'cancelled') c.expected += Number(d.expected_count || 0)
  }
  return c
})

const cells = computed(() => {
  const [y, m] = month.value.split('-').map(Number)
  const first = new Date(y, m - 1, 1)
  const daysInMonth = new Date(y, m, 0).getDate()
  // Monday-first offset
  const offset = (first.getDay() + 6) % 7
  const todayStr = new Date().toISOString().slice(0, 10)
  const out = []
  for (let i = 0; i < offset; i++) out.push({ date: null })
  for (let d = 1; d <= daysInMonth; d++) {
    const dateStr = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    out.push({
      date: dateStr,
      dayNum: d,
      isToday: dateStr === todayStr,
      day: dayByDate.value[dateStr] || null,
    })
  }
  while (out.length % 7 !== 0) out.push({ date: null })
  return out
})

const settings = computed(() => props.program?.settings || {})

function tripsFor(day) {
  if (!day || day.day_type === 'cancelled') return []
  const s = settings.value
  const out = []
  const morning = s.morning || {}
  const afternoon = s.afternoon || {}
  const hasMorning = morning.enabled ?? !!props.program.departure_time
  const hasAfternoon = afternoon.enabled ?? !!props.program.return_time
  if (hasMorning) {
    const dep = (morning.departure || props.program.departure_time || '').slice(0, 5)
    const arr = (morning.arrival || '').slice(0, 5)
    out.push({
      key: 'morning',
      icon: SunIcon,
      label: dep ? `Sáng ${dep}` : 'Sáng',
      cls: 'bg-amber-50 text-amber-700',
      iconWrap: 'bg-amber-100 text-amber-600',
      title: 'Chuyến sáng — Đưa đến trường',
      time: dep ? `${dep}${arr ? ' → ' + arr : ''}` : '—',
    })
  }
  if (hasAfternoon) {
    const dep = (afternoon.departure || props.program.return_time || '').slice(0, 5)
    const arr = (afternoon.arrival || '').slice(0, 5)
    out.push({
      key: 'afternoon',
      icon: MoonIcon,
      label: dep ? `Chiều ${dep}` : 'Chiều',
      cls: 'bg-indigo-50 text-indigo-700',
      iconWrap: 'bg-indigo-100 text-indigo-600',
      title: 'Chuyến chiều — Đón về nhà',
      time: dep ? `${dep}${arr ? ' → ' + arr : ''}` : '—',
    })
  }
  return out
}

function dotColor(t) {
  return { operating: 'bg-emerald-500', makeup: 'bg-amber-500', cancelled: 'bg-rose-500' }[t] || 'bg-slate-300'
}
function dayTypeLabel(t) {
  return { operating: 'Vận hành', cancelled: 'Đã hủy', makeup: 'Học bù' }[t] || t
}
function dayBadge(t) {
  const base = 'mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold '
  return base + ({
    operating: 'bg-emerald-50 text-emerald-700',
    makeup: 'bg-amber-50 text-amber-700',
    cancelled: 'bg-rose-50 text-rose-700',
  }[t] || 'bg-slate-100 text-slate-600')
}
function execLabel(d) {
  if (!d.has_execution) return 'Chưa thực hiện'
  return { completed: 'Hoàn thành', in_progress: 'Đang chạy', started: 'Đang chạy' }[d.execution_status] || 'Đang chạy'
}
function execTextClass(d) {
  if (!d.has_execution) return 'text-slate-400'
  return d.execution_status === 'completed' ? 'text-emerald-600' : 'text-amber-600'
}
function weekdayLong(dateStr) {
  return WD_LONG[new Date(dateStr + 'T00:00:00').getDay()]
}
function formatLong(dateStr) {
  const d = new Date(dateStr + 'T00:00:00')
  return `${d.getDate()} tháng ${d.getMonth() + 1}, ${d.getFullYear()}`
}

function shiftMonth(delta) {
  const [y, m] = month.value.split('-').map(Number)
  const d = new Date(y, m - 1 + delta, 1)
  month.value = d.toISOString().slice(0, 7)
}
function goToday() {
  month.value = new Date().toISOString().slice(0, 7)
}

function openDay(day) {
  selected.value = day
}
function goAttendance(day, shift = null) {
  const slots = tripsFor(day)
  const query = shift || (slots.length > 1 ? { shift: slots[0].key } : {})
  const q = typeof query === 'string' ? { shift: query } : query
  router.push({ name: 'tpDayAttendance', params: { dayId: day.id }, query: q })
}

async function load() {
  loading.value = true
  try {
    days.value = (await listProgramDays(props.program.id, { month: month.value }))?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

watch(month, load)
onMounted(load)
</script>
