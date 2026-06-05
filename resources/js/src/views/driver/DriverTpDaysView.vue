<template>
  <div class="min-h-full w-full max-w-[430px] bg-driver-bg pb-28 text-driver-ink sm:max-w-none" :style="{ '--accent': '#7fdcc8' }">
    <header
      class="sticky top-0 z-[25] flex items-center gap-2 border-b border-white/5 bg-driver-bg/90 px-3 py-3 backdrop-blur-md"
      style="padding-top: max(0.75rem, env(safe-area-inset-top))"
    >
      <div class="min-w-0 flex-1">
        <h1 class="truncate text-lg font-bold tracking-tight">{{ t('nav.driver_tp_days') }}</h1>
        <p class="truncate text-xs text-driver-ink/50">{{ t('driver_tp_days.subtitle') }}</p>
      </div>
      <span v-if="!online" class="rounded-full bg-amber-500/20 px-2 py-0.5 text-xs text-amber-300">Ngoại tuyến</span>
    </header>

    <div class="space-y-4 px-3 pt-4">
      <!-- Lịch tuần: lướt sang trái/phải để xem các ngày khác -->
      <WeekCalendar v-model="selectedDate" :trip-dates="weekTripDateKeys" />

      <p class="px-1 text-base font-semibold text-driver-ink">
        {{ dayTitle }}
        <span v-if="visibleDays.length" class="text-sm text-driver-ink/50"> ({{ visibleDays.length }})</span>
      </p>

      <div v-if="loading" class="py-16 text-center text-sm text-driver-ink/60">Đang tải…</div>
      <div v-else-if="!visibleDays.length" class="py-16 text-center text-sm text-driver-ink/60">Không có chuyến nào trong ngày này.</div>
      <div v-else class="space-y-2">
        <button
          v-for="d in visibleDays"
          :key="d.list_key || d.day_id"
          type="button"
          class="block w-full rounded-2xl border border-white/8 bg-white/[0.03] p-4 text-left transition active:scale-[0.99]"
          @click="open(d)"
        >
          <div class="flex items-center justify-between gap-2">
            <div class="min-w-0 font-semibold truncate">{{ d.program_name }}</div>
            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(d)">{{ statusLabel(d) }}</span>
          </div>
          <div class="mt-1 text-xs text-driver-ink/50">
            <span v-if="d.shift" class="font-semibold text-[#7fdcc8]">{{ shiftLabel(d.shift) }}</span>
            <span v-if="d.shift"> · </span>
            {{ d.scheduled_date }} · {{ d.departure_time }}
            <span v-if="d.arrival_time">–{{ d.arrival_time }}</span>
            · {{ d.expected_count }} HS
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { driverListDays } from '../../api/transportProgram'
import { showAppErrorFromApi } from '../../composables/appMessage'
import WeekCalendar from '../../components/trips/WeekCalendar.vue'
import { useDriverWebPushBoot } from '../../composables/useDriverWebPushBoot'

const { t } = useI18n()
const router = useRouter()
const { bootDriverOutboundNotifications } = useDriverWebPushBoot()
const loading = ref(false)
const days = ref([])
const selectedDate = ref(new Date())
const online = ref(typeof navigator !== 'undefined' ? navigator.onLine : true)

function setOnline() { online.value = navigator.onLine }

function ymd(d) {
  const x = d instanceof Date ? d : new Date(d)
  const y = x.getFullYear()
  const m = String(x.getMonth() + 1).padStart(2, '0')
  const day = String(x.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function startOfWeekMonday(d) {
  const x = new Date(d)
  x.setHours(12, 0, 0, 0)
  const dow = x.getDay()
  const diff = dow === 0 ? -6 : 1 - dow
  x.setDate(x.getDate() + diff)
  x.setHours(0, 0, 0, 0)
  return x
}

function endOfWeekSunday(d) {
  const start = startOfWeekMonday(d)
  const end = new Date(start)
  end.setDate(start.getDate() + 6)
  return end
}

const selectedYmd = computed(() => ymd(selectedDate.value))

// Chấm trên lịch cho mỗi ngày có chuyến trong tuần đang xem
const weekTripDateKeys = computed(() => {
  const set = new Set()
  for (const d of days.value) {
    if (d.scheduled_date) set.add(String(d.scheduled_date).slice(0, 10))
  }
  return [...set]
})

// Chỉ hiển thị chuyến của ngày đang chọn
const visibleDays = computed(() =>
  days.value
    .filter((d) => String(d.scheduled_date).slice(0, 10) === selectedYmd.value)
    .sort((a, b) => String(a.departure_time || '').localeCompare(String(b.departure_time || ''))),
)

const dayTitle = computed(() =>
  selectedDate.value.toLocaleDateString('vi-VN', { weekday: 'long', day: 'numeric', month: 'numeric' }),
)

function shiftLabel(shift) {
  if (shift === 'morning') return t('driver_home.shift_morning')
  if (shift === 'afternoon') return t('driver_home.shift_afternoon')
  return ''
}

async function load() {
  loading.value = true
  try {
    const from = ymd(startOfWeekMonday(selectedDate.value))
    const to = ymd(endOfWeekSunday(selectedDate.value))
    days.value = (await driverListDays({ date_from: from, date_to: to }))?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

function open(d) {
  router.push({ name: 'driverTpAttendance', params: { dayId: d.day_id } })
}

// Trạng thái hiển thị: ưu tiên trạng thái chạy, rồi tới xác nhận
function statusLabel(d) {
  if (d.execution_status) {
    return { in_progress: 'Đang chạy', completed: 'Hoàn thành', cancelled: 'Đã hủy' }[d.execution_status] || 'Chưa bắt đầu'
  }
  return d.confirmed_at ? 'Đã xác nhận' : 'Chưa xác nhận'
}
function statusClass(d) {
  if (d.execution_status) {
    return {
      in_progress: 'bg-[#7fdcc8]/20 text-[#7fdcc8]',
      completed: 'bg-sky-500/20 text-sky-300',
      cancelled: 'bg-rose-500/20 text-rose-300',
    }[d.execution_status] || 'bg-white/10 text-driver-ink/60'
  }
  return d.confirmed_at ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300'
}

watch(selectedDate, () => { void load() })

onMounted(() => {
  void bootDriverOutboundNotifications()
  load()
  window.addEventListener('online', setOnline)
  window.addEventListener('offline', setOnline)
})
onUnmounted(() => {
  window.removeEventListener('online', setOnline)
  window.removeEventListener('offline', setOnline)
})
</script>
