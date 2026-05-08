<template>
  <div
    class="rounded-2xl border border-[rgba(255,255,255,0.06)] bg-[#0f2318] p-3"
    @touchstart.passive="onTouchStart"
    @touchend.passive="onTouchEnd"
  >
    <p class="mb-3 px-1 text-sm font-bold uppercase tracking-wider text-[#7fdcc8] sm:text-base">
      {{ t('trip_history_page.week_calendar_title') }}
    </p>
    <div class="grid grid-cols-7 gap-0.5">
      <button
        v-for="day in weekDays"
        :key="day.iso"
        type="button"
        class="flex min-h-[5.25rem] w-full flex-col items-center gap-1.5 py-2 transition active:scale-[0.96]"
        :aria-pressed="isSelected(day.iso)"
        @click="selectDay(day)"
      >
        <span
          class="text-xs font-bold uppercase leading-none tracking-wide sm:text-sm"
          :class="dayAbbrevClass(day)"
        >
          {{ day.abbr }}
        </span>
        <div
          class="flex h-12 w-12 items-center justify-center rounded-full text-base font-bold tabular-nums transition-colors sm:h-14 sm:w-14 sm:text-lg"
          :class="dayCircleClass(day)"
        >
          {{ day.dayNum }}
        </div>
        <span
          v-if="hasTripDot(day.iso)"
          class="h-1 w-1 rounded-full bg-[#7fdcc8]"
          aria-hidden="true"
        />
        <span v-else class="h-1 w-1 shrink-0" aria-hidden="true" />
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
    return 'border-2 border-[#7fdcc8] bg-transparent text-[#7fdcc8]'
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
