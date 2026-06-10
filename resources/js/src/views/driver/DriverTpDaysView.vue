<template>
  <div class="mx-auto min-h-full w-full max-w-[430px] overflow-x-hidden bg-driver-bg pb-28 text-driver-ink sm:max-w-2xl">
    <!-- Header -->
    <header
      class="sticky top-0 z-[25] flex items-center gap-3 border-b border-white/5 bg-driver-bg/90 px-4 py-3 backdrop-blur-md [-webkit-backdrop-filter:blur(12px)]"
      style="padding-top: max(0.75rem, env(safe-area-inset-top))"
    >
      <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-driver-accent/15">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-driver-accent" aria-hidden="true">
          <path fill-rule="evenodd" d="M4 4a2 2 0 0 1 2-2h4.586A2 2 0 0 1 12 2.586L15.414 6A2 2 0 0 1 16 7.414V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4Zm2 6a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1Zm1 3a1 1 0 1 0 0 2h4a1 1 0 1 0 0-2H7Z" clip-rule="evenodd" />
        </svg>
      </div>
      <div class="min-w-0 flex-1">
        <h1 class="truncate text-base font-bold leading-tight tracking-tight">{{ t('nav.driver_tp_days') }}</h1>
        <p class="truncate text-[11px] leading-tight text-driver-muted">{{ t('driver_tp_days.subtitle') }}</p>
      </div>
      <span
        v-if="!online"
        class="shrink-0 rounded-full bg-amber-500/20 px-2.5 py-1 text-[11px] font-semibold text-amber-300"
      >
        Ngoại tuyến
      </span>
    </header>

    <div class="px-3 pt-4">
      <!-- Week calendar -->
      <WeekCalendar v-model="selectedDate" :trip-dates="weekTripDateKeys" />

      <!-- Day title bar -->
      <div class="mt-5 mb-4 flex items-center justify-between gap-3 px-1">
        <p class="min-w-0 truncate text-base font-bold tracking-tight text-driver-ink">
          {{ dayTitle }}
        </p>
        <span
          v-if="visibleDays.length"
          class="shrink-0 rounded-full bg-driver-elevated px-3 py-1 text-xs font-bold tabular-nums text-driver-accent ring-1 ring-driver-accent/20"
        >
          {{ visibleDays.length }}
        </span>
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading" class="flex flex-col gap-0">
        <div v-for="n in 3" :key="n" class="flex items-stretch gap-3">
          <div class="flex w-14 shrink-0 flex-col items-center pt-4">
            <div class="h-4 w-10 animate-pulse rounded-md bg-driver-accent/15" />
            <div class="mt-2 h-3 w-3 animate-pulse rounded-full bg-driver-accent/10" />
            <div v-if="n < 3" class="mt-1 h-full w-px animate-pulse bg-driver-accent/8" style="min-height: 80px" />
          </div>
          <div
            class="mb-3 flex-1 animate-pulse rounded-2xl bg-driver-surface ring-1 ring-white/5"
            :style="{ minHeight: n === 2 ? '100px' : '120px' }"
          />
        </div>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="!visibleDays.length"
        class="mt-2 flex flex-col items-center gap-4 rounded-2xl border border-dashed border-driver-accent/18 bg-driver-surface/40 px-4 py-14 text-center"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.25"
          stroke="currentColor"
          class="h-14 w-14 text-driver-accent/25"
          aria-hidden="true"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
        </svg>
        <div class="space-y-1">
          <p class="text-sm font-semibold text-driver-ink/70">Không có chuyến nào</p>
          <p class="text-xs text-driver-muted">Ngày này không có lịch đưa đón</p>
        </div>
      </div>

      <!-- Timeline list -->
      <div v-else class="flex flex-col">
        <div
          v-for="(d, idx) in visibleDays"
          :key="d.list_key || d.day_id"
          class="flex items-stretch gap-3"
        >
          <!-- Left column: time + dot + connector -->
          <div class="flex w-14 shrink-0 flex-col items-center pt-4">
            <span class="shrink-0 text-sm font-extrabold tabular-nums leading-none" :class="shiftTimeClass(d)">
              {{ d.departure_time || '—' }}
            </span>
            <div
              class="mt-2 h-3 w-3 shrink-0 rounded-full ring-2 ring-driver-bg"
              :class="shiftDotClass(d)"
              aria-hidden="true"
            />
            <div
              v-if="idx < visibleDays.length - 1"
              class="mt-1 flex-1 self-stretch"
              style="min-height: 24px"
            >
              <div class="mx-auto h-full w-px bg-white/8" />
            </div>
          </div>

          <!-- Card -->
          <button
            type="button"
            class="mb-3 min-w-0 flex-1 overflow-hidden rounded-2xl bg-driver-card text-left ring-1 transition active:scale-[0.99]"
            :class="shiftCardClass(d)"
            @click="open(d)"
          >
            <!-- Shift accent strip -->
            <div class="h-0.5 w-full" :class="shiftStripClass(d)" />

            <!-- Card body -->
            <div class="px-4 pb-0 pt-3.5">
              <!-- Row 1: shift badge + status chip -->
              <div class="mb-2.5 flex items-center justify-between gap-2">
                <!-- Shift badge with icon -->
                <span
                  class="flex shrink-0 items-center gap-1.5 rounded-lg px-2 py-1 text-[11px] font-bold uppercase tracking-wide"
                  :class="shiftBadgeClass(d)"
                >
                  <!-- Morning: sun -->
                  <svg
                    v-if="d.shift === 'morning'"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    class="h-3.5 w-3.5"
                    aria-hidden="true"
                  >
                    <path d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2ZM10 15a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 15ZM10 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM15.657 5.404a.75.75 0 1 0-1.06-1.06l-1.061 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM6.464 14.596a.75.75 0 1 0-1.06-1.06l-1.06 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM18 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 18 10ZM5 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 5 10ZM14.596 15.657a.75.75 0 0 0 1.06-1.06l-1.06-1.061a.75.75 0 1 0-1.06 1.06l1.06 1.06ZM5.404 6.464a.75.75 0 0 0 1.06-1.06l-1.06-1.06a.75.75 0 1 0-1.06 1.06l1.06 1.06Z" />
                  </svg>
                  <!-- Afternoon: moon -->
                  <svg
                    v-else-if="d.shift === 'afternoon'"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    class="h-3.5 w-3.5"
                    aria-hidden="true"
                  >
                    <path fill-rule="evenodd" d="M7.455 2.004a.75.75 0 0 1 .26.77 7 7 0 0 0 9.958 7.967.75.75 0 0 1 1.067.853A8.5 8.5 0 1 1 6.647 1.921a.75.75 0 0 1 .808.083Z" clip-rule="evenodd" />
                  </svg>
                  {{ shiftLabel(d.shift) }}
                </span>

                <!-- Status chip -->
                <span
                  class="shrink-0 rounded-lg px-2.5 py-1 text-[11px] font-bold"
                  :class="statusChipClass(d)"
                >
                  {{ statusLabel(d) }}
                </span>
              </div>

              <!-- Program name -->
              <p class="truncate text-base font-bold leading-snug text-white">
                {{ d.program_name }}
              </p>

              <!-- Meta row: arrival time + student count -->
              <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1.5">
                <span v-if="d.arrival_time" class="flex items-center gap-1.5 text-[13px] text-driver-muted">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3.5 w-3.5 shrink-0 text-driver-accent/50" aria-hidden="true">
                    <path fill-rule="evenodd" d="M1 8a7 7 0 1 1 14 0A7 7 0 0 1 1 8Zm7.75-4.25a.75.75 0 0 0-1.5 0V8c0 .414.336.75.75.75h3.25a.75.75 0 0 0 0-1.5h-2.5v-3.5Z" clip-rule="evenodd" />
                  </svg>
                  Đến {{ d.arrival_time }}
                </span>

                <span class="flex items-center gap-1.5 text-[13px] text-driver-muted">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3.5 w-3.5 shrink-0 text-driver-accent/50" aria-hidden="true">
                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM12.735 14c.618 0 1.093-.561.872-1.139a6.002 6.002 0 0 0-11.215 0c-.22.578.254 1.139.872 1.139h9.47Z" />
                  </svg>
                  {{ d.expected_count }} học sinh
                </span>
              </div>
            </div>

            <!-- Footer tap indicator -->
            <div class="mt-3 flex items-center justify-end gap-1.5 border-t border-white/[0.05] px-4 py-2.5">
              <span class="text-[11px] text-driver-muted/70">Xem điểm danh</span>
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3.5 w-3.5 shrink-0 text-driver-accent/35" aria-hidden="true">
                <path fill-rule="evenodd" d="M6.22 4.22a.75.75 0 0 1 1.06 0l3.25 3.25a.75.75 0 0 1 0 1.06l-3.25 3.25a.75.75 0 0 1-1.06-1.06L9.19 8 6.22 5.03a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
              </svg>
            </div>
          </button>
        </div>
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

const weekTripDateKeys = computed(() => {
  const set = new Set()
  for (const d of days.value) {
    if (d.scheduled_date) set.add(String(d.scheduled_date).slice(0, 10))
  }
  return [...set]
})

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

function shiftTimeClass(d) {
  if (d.shift === 'morning') return 'text-amber-300'
  if (d.shift === 'afternoon') return 'text-violet-300'
  return 'text-white/90'
}

function shiftDotClass(d) {
  if (d.execution_status === 'in_progress') return 'bg-sky-400'
  if (d.execution_status === 'completed') return 'bg-emerald-400'
  if (d.execution_status === 'cancelled') return 'bg-slate-500'
  if (d.shift === 'morning') return 'bg-amber-400'
  if (d.shift === 'afternoon') return 'bg-violet-400'
  return 'bg-driver-accent'
}

function shiftCardClass(d) {
  if (d.shift === 'morning') return 'ring-amber-400/12 hover:ring-amber-400/30'
  if (d.shift === 'afternoon') return 'ring-violet-400/12 hover:ring-violet-400/30'
  return 'ring-driver-accent/12 hover:ring-driver-accent/30'
}

function shiftStripClass(d) {
  if (d.execution_status === 'cancelled') return 'bg-slate-600'
  if (d.execution_status === 'completed') return 'bg-emerald-500'
  if (d.shift === 'morning') return 'bg-gradient-to-r from-amber-500 via-amber-400 to-amber-300/40'
  if (d.shift === 'afternoon') return 'bg-gradient-to-r from-violet-500 via-violet-400 to-violet-300/40'
  return 'bg-gradient-to-r from-driver-accent via-driver-accent to-driver-accent/30'
}

function shiftBadgeClass(d) {
  if (d.shift === 'morning') return 'bg-amber-500/15 text-amber-300'
  if (d.shift === 'afternoon') return 'bg-violet-500/15 text-violet-300'
  return 'bg-driver-accent/12 text-driver-accent'
}

function statusLabel(d) {
  if (d.execution_status) {
    return {
      in_progress: 'Đang chạy',
      completed: 'Hoàn thành',
      cancelled: 'Đã hủy',
    }[d.execution_status] || 'Chưa bắt đầu'
  }
  return d.confirmed_at ? 'Đã xác nhận' : 'Chưa xác nhận'
}

function statusChipClass(d) {
  if (d.execution_status === 'in_progress') return 'bg-sky-500/15 text-sky-200 ring-1 ring-sky-400/25'
  if (d.execution_status === 'completed') return 'bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-400/25'
  if (d.execution_status === 'cancelled') return 'bg-slate-600/25 text-slate-300 ring-1 ring-slate-500/30'
  return d.confirmed_at
    ? 'bg-emerald-500/12 text-emerald-300 ring-1 ring-emerald-400/20'
    : 'bg-amber-500/12 text-amber-300 ring-1 ring-amber-400/20'
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
  const query = d.multi_slot && d.shift ? { shift: d.shift } : {}
  router.push({ name: 'driverTpAttendance', params: { dayId: d.day_id }, query })
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
