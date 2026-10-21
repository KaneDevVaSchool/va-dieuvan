<template>
  <div
    class="rounded-xl border border-[rgba(255,255,255,0.06)] bg-[#0f2318] p-2.5"
    @touchstart.passive="onTouchStart"
    @touchend.passive="onTouchEnd"
  >
    <p class="mb-2 px-1 text-xs font-bold uppercase tracking-wider text-[#7fdcc8]">
      {{ t('trip_history_page.week_calendar_title') }}
    </p>
    <div class="grid grid-cols-7 gap-1">
      <button
        v-for="day in weekDays"
        :key="day.iso"
        type="button"
        class="flex min-h-[62px] min-w-0 w-full flex-col items-center gap-1 rounded-lg py-1.5 transition hover:bg-white/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#7fdcc8] active:scale-[0.96]"
        :aria-pressed="isSelected(day.iso)"
        :aria-current="day.isToday ? 'date' : undefined"
        @click="selectDay(day)"
      >
        <span
          class="text-[11px] font-bold uppercase leading-none tracking-wide"
          :class="dayAbbrevClass(day)"
        >
          {{ day.abbr }}
        </span>
        <div
          class="relative flex aspect-square w-full max-w-8.5 shrink-0 items-center justify-center rounded-full text-sm font-bold tabular-nums transition-colors sm:max-w-9.5 sm:text-base"
          :class="dayCircleClass(day)"
        >
          {{ day.dayNum }}
          <span
            v-if="hasTripDot(day.iso)"
            class="absolute bottom-1 h-1 w-1 rounded-full sm:bottom-1"
            :class="isSelected(day.iso) ? 'bg-[#09180f]' : 'bg-[#7fdcc8]'"
            aria-hidden="true"
          />
        </div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  modelValue: { type: Date, required: true },
  tripDates: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:modelValue', 'date-change'])

const { t } = useI18n()

/** @type {import('vue').Ref<number | null>} */
const touchStartX = ref(null)


const tripDateSet = computed(() => new Set((props.tripDates || []).filter(Boolean)))

/** Monday = start of ISO-like week */
function startOfWeekMonday(d) {
  const x = new Date(d)
  x.setHours(12, 0, 0, 0)
  const dow = x.getDay()
  const diff = dow === 0 ? -6 : 1 - dow
  x.setDate(x.getDate() + diff)
  x.setHours(0, 0, 0, 0)
  return x
}

function ymd(dt) {
  const y = dt.getFullYear()
  const m = String(dt.getMonth() + 1).padStart(2, '0')
  const day = String(dt.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

/** @returns {{ iso: string, abbr: string, dayNum: number, dateObj: Date, isToday: boolean }[]} */
const weekDays = computed(() => {
  const base = startOfWeekMonday(props.modelValue)
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const abbrKeys = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']
  const days = []
  for (let i = 0; i < 7; i++) {
    const dateObj = new Date(base)
    dateObj.setDate(base.getDate() + i)
    const iso = ymd(dateObj)
    const t0 = new Date(dateObj)
    t0.setHours(0, 0, 0, 0)
    days.push({
      iso,
      abbr: t(`trip_history_page.week_abbr_${abbrKeys[i]}`),
      dayNum: dateObj.getDate(),
      dateObj,
      isToday: t0.getTime() === today.getTime(),
    })
  }
  return days
})

function isSelected(iso) {
  return iso === ymd(props.modelValue)
}

function hasTripDot(iso) {
  return tripDateSet.value.has(iso)
}

function dayAbbrevClass(day) {
  if (day.isToday) return 'text-[#7fdcc8]'
  if (isSelected(day.iso)) return 'text-[#7fdcc8]/85'
  return 'text-[#64748b]'
}

function dayCircleClass(day) {
  if (isSelected(day.iso)) {
    return 'bg-[#7fdcc8] text-[#09180f]'
  }
  if (day.isToday) {
    return 'bg-transparent text-[#7fdcc8] ring-2 ring-inset ring-[#7fdcc8]'
  }
  return 'bg-transparent text-white'
}

function selectDay(day) {
  const d = new Date(day.dateObj)
  emit('update:modelValue', d)
  emit('date-change', d)
}

function shiftWeek(delta) {
  const d = new Date(props.modelValue)
  d.setDate(d.getDate() + delta * 7)
  emit('update:modelValue', d)
  emit('date-change', d)
}

function onTouchStart(ev) {
  const x = ev.changedTouches?.[0]?.clientX
  touchStartX.value = typeof x === 'number' ? x : null
}

function onTouchEnd(ev) {
  const start = touchStartX.value
  touchStartX.value = null
  if (start == null) return
  const end = ev.changedTouches?.[0]?.clientX
  if (typeof end !== 'number') return
  const dx = end - start
  if (Math.abs(dx) < 44) return
  if (dx > 0) shiftWeek(-1)
  else shiftWeek(1)
}
</script>
