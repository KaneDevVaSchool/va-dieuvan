<template>
  <section>
    <h2 class="mb-3 text-base font-bold" style="color: #9A0036">
      {{ t('driver_home.calendar_title') }}
    </h2>

    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-7 gap-1">
      <div v-for="n in 7" :key="n" class="flex flex-col items-center gap-1.5">
        <div class="h-3.5 w-5 animate-pulse rounded bg-slate-200 dark:bg-slate-800" />
        <div class="h-9 w-9 animate-pulse rounded-full bg-slate-200 dark:bg-slate-800" />
        <div class="h-1.5 w-1.5 animate-pulse rounded-full bg-slate-100 dark:bg-slate-700" />
      </div>
    </div>

    <!-- Week row -->
    <div v-else class="grid grid-cols-7 gap-1">
      <div
        v-for="day in weekDays"
        :key="day.iso"
        class="flex flex-col items-center gap-1"
      >
        <!-- Day abbreviation -->
        <span
          class="text-[10px] font-semibold uppercase leading-none"
          :class="day.isToday ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-500'"
        >
          {{ day.abbr }}
        </span>

        <!-- Date circle -->
        <div
          class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold leading-none transition-colors"
          :class="day.isToday ? 'text-white' : 'text-slate-700 dark:text-slate-200'"
          :style="day.isToday ? 'background-color: #9A0036' : ''"
        >
          {{ day.date }}
        </div>

        <!-- Trip dots (max 3) -->
        <div class="flex items-center gap-0.5 h-2">
          <span
            v-for="i in Math.min(day.tripCount, 3)"
            :key="i"
            class="block h-1.5 w-1.5 rounded-full"
            :style="day.isToday ? 'background-color: #9A0036' : ''"
            :class="!day.isToday ? 'bg-slate-400 dark:bg-slate-500' : ''"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  rawTrips: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const { t, locale } = useI18n()

function ymd(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

/** Map từ 'YYYY-MM-DD' → số chuyến */
const tripCountByDate = computed(() => {
  const map = {}
  for (const trip of props.rawTrips) {
    const iso = trip.depart_at
    if (!iso) continue
    const key = iso.slice(0, 10)
    map[key] = (map[key] ?? 0) + 1
  }
  return map
})

const DAY_ABBR_VI = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
const DAY_ABBR_EN = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']

const weekDays = computed(() => {
  const today = new Date()
  const todayIso = ymd(today)
  // Monday-start: find Monday of current week
  const dow = today.getDay() // 0=Sun
  const monday = new Date(today)
  monday.setDate(today.getDate() - ((dow + 6) % 7))

  const isVi = locale.value === 'vi'

  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(monday)
    d.setDate(monday.getDate() + i)
    const iso = ymd(d)
    return {
      iso,
      date: d.getDate(),
      abbr: isVi ? DAY_ABBR_VI[d.getDay()] : DAY_ABBR_EN[d.getDay()],
      isToday: iso === todayIso,
      tripCount: tripCountByDate.value[iso] ?? 0,
    }
  })
})
</script>
