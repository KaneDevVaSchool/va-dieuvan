<template>
  <section class="rounded-3xl border border-slate-700/60 bg-[#1E293B] p-4 shadow-lg shadow-black/20 ring-1 ring-white/5">
    <h2 class="mb-3 text-base font-bold text-[#E04676]">
      {{ t('driver_home.calendar_title') }}
    </h2>

    <div v-if="loading" class="grid grid-cols-7 gap-1">
      <div v-for="n in 7" :key="n" class="flex flex-col items-center gap-1.5">
        <div class="h-3.5 w-5 animate-pulse rounded bg-slate-700/60" />
        <div class="h-9 w-9 animate-pulse rounded-full bg-slate-700/60" />
        <div class="h-1.5 w-1.5 animate-pulse rounded-full bg-slate-600/60" />
      </div>
    </div>

    <div v-else class="grid grid-cols-7 gap-1">
      <div
        v-for="day in weekDays"
        :key="day.iso"
        class="flex flex-col items-center gap-1"
      >
        <span
          class="text-[10px] font-semibold uppercase leading-none"
          :class="day.isToday ? 'text-white' : 'text-slate-500'"
        >
          {{ day.abbr }}
        </span>

        <div
          class="flex h-9 min-h-[36px] w-9 min-w-[36px] items-center justify-center rounded-full text-sm font-bold tabular-nums leading-none transition-colors"
          :class="day.isToday ? 'bg-[#9A0036] text-white' : 'bg-slate-800 text-slate-200'"
        >
          {{ day.date }}
        </div>

        <div class="flex h-2 items-center gap-0.5">
          <span
            v-for="i in Math.min(day.tripCount, 3)"
            :key="i"
            class="block h-1.5 w-1.5 rounded-full"
            :class="day.isToday ? 'bg-white/90' : 'bg-slate-500'"
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
  const dow = today.getDay()
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
