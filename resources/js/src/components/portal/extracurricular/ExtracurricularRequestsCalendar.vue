<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <button
        type="button"
        class="inline-flex h-9 min-w-[2.5rem] items-center justify-center rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
        :aria-label="t('portal.recurring_plan.calendar_prev_month')"
        @click="shiftMonth(-1)"
      >
        ‹
      </button>
      <h3 class="text-base font-bold capitalize text-slate-900">{{ monthLabel }}</h3>
      <button
        type="button"
        class="inline-flex h-9 min-w-[2.5rem] items-center justify-center rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
        :aria-label="t('portal.recurring_plan.calendar_next_month')"
        @click="shiftMonth(1)"
      >
        ›
      </button>
    </div>

    <div class="grid grid-cols-7 gap-1.5 sm:gap-2">
      <div
        v-for="wd in weekdayHeaders"
        :key="wd"
        class="py-1 text-center text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:text-xs"
      >
        {{ wd }}
      </div>
    </div>

    <div class="mt-1.5 grid grid-cols-7 gap-1.5 sm:gap-2">
      <div
        v-for="cell in cells"
        :key="cell.key"
        class="flex min-h-[5.5rem] flex-col rounded-xl border text-left transition sm:min-h-[7.5rem]"
        :class="cellShellClass(cell)"
      >
        <div
          class="flex items-center justify-between gap-1 border-b px-1.5 py-1 sm:px-2"
          :class="cell.inMonth ? 'border-indigo-100/80' : 'border-transparent'"
        >
          <span
            class="inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-md text-xs font-bold tabular-nums sm:text-sm"
            :class="
              cell.isToday && cell.inMonth
                ? 'bg-indigo-600 text-white shadow-sm'
                : cell.inMonth
                  ? 'text-slate-800'
                  : 'text-slate-400'
            "
          >
            {{ cell.dayNum }}
          </span>
          <span
            v-if="cell.inMonth && cell.items.length"
            class="rounded-full bg-indigo-600/90 px-1.5 py-0.5 text-[9px] font-bold tabular-nums text-white sm:text-[10px]"
          >
            {{ cell.items.length }}
          </span>
        </div>

        <div class="flex flex-1 flex-col gap-1 p-1 sm:p-1.5">
          <template v-if="cell.inMonth && cell.items.length">
            <RouterLink
              v-for="req in cell.items.slice(0, maxCardsPerDay)"
              :key="req.id"
              :to="{ name: detailRouteName, params: { id: req.id } }"
              class="group block rounded-lg border border-white/80 bg-white p-1.5 shadow-sm ring-1 ring-indigo-200/60 transition hover:border-indigo-300 hover:shadow-md hover:ring-indigo-300/80 sm:p-2"
            >
              <div class="flex items-start justify-between gap-1">
                <span class="font-mono text-[11px] font-bold text-indigo-900 sm:text-xs">#{{ req.id }}</span>
                <span
                  class="shrink-0 rounded px-1 py-0.5 text-[10px] font-bold tabular-nums text-indigo-800 bg-indigo-50"
                >
                  {{ departTime(req) }}
                </span>
              </div>
              <p class="mt-0.5 line-clamp-2 text-[10px] leading-snug text-slate-600 group-hover:text-slate-800 sm:text-[11px]">
                {{ routeLabel(req) }}
              </p>
              <div class="mt-1 flex flex-wrap items-center gap-1">
                <span class="inline-flex items-center gap-0.5 rounded bg-slate-100 px-1 py-0.5 text-[9px] font-semibold text-slate-700 sm:text-[10px]">
                  <span class="text-slate-500">{{ t('portal.extracurricular_table.col_actual') }}</span>
                  <span class="tabular-nums">{{ hsActual(req) }}</span>
                </span>
                <span
                  v-if="hsPlan(req) != null"
                  class="text-[9px] text-slate-500 sm:text-[10px]"
                >
                  / {{ t('portal.extracurricular_table.col_plan') }} {{ hsPlan(req) }}
                </span>
              </div>
              <span
                class="mt-1 inline-flex max-w-full truncate rounded-full px-1.5 py-0.5 text-[8px] font-bold uppercase tracking-wide ring-1 ring-inset sm:text-[9px]"
                :class="trackingTone(req)"
              >
                {{ trackingLabel(req) }}
              </span>
            </RouterLink>
            <p
              v-if="cell.items.length > maxCardsPerDay"
              class="px-0.5 text-center text-[10px] font-semibold text-indigo-700"
            >
              {{ t('portal.recurring_plan.calendar_more', { n: cell.items.length - maxCardsPerDay }) }}
            </p>
          </template>
          <p
            v-else-if="cell.inMonth"
            class="flex flex-1 items-center justify-center px-1 text-center text-[10px] text-slate-400"
          >
            —
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { extracurricularStudentCountTrackingKey } from '../../../composables/useExtracurricularStudentCountTracking'

const props = defineProps({
  requests: { type: Array, default: () => [] },
  detailRouteName: { type: String, required: true },
  maxCardsPerDay: { type: Number, default: 2 },
})

const { t, locale } = useI18n()

const viewMonth = ref(startOfMonth(new Date()))

const todayKey = computed(() => ymd(new Date()))

function startOfMonth(d) {
  const x = new Date(d)
  x.setDate(1)
  x.setHours(0, 0, 0, 0)
  return x
}

function ymd(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

const byDay = computed(() => {
  const map = new Map()
  for (const req of props.requests) {
    const iso = req?.depart_at
    if (!iso) continue
    const key = String(iso).slice(0, 10)
    if (!map.has(key)) map.set(key, [])
    map.get(key).push(req)
  }
  for (const list of map.values()) {
    list.sort((a, b) => String(a.depart_at).localeCompare(String(b.depart_at)))
  }
  return map
})

const monthLabel = computed(() => {
  const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
  return new Intl.DateTimeFormat(loc, { month: 'long', year: 'numeric' }).format(viewMonth.value)
})

const weekdayHeaders = computed(() => {
  const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
  const base = new Date(2024, 0, 1)
  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(base)
    d.setDate(base.getDate() + i)
    return new Intl.DateTimeFormat(loc, { weekday: 'short' }).format(d)
  })
})

const cells = computed(() => {
  const m = viewMonth.value
  const first = startOfMonth(m)
  const startPad = (first.getDay() + 6) % 7
  const gridStart = new Date(first)
  gridStart.setDate(first.getDate() - startPad)

  const out = []
  for (let i = 0; i < 42; i++) {
    const d = new Date(gridStart)
    d.setDate(gridStart.getDate() + i)
    const key = ymd(d)
    out.push({
      key,
      dayNum: d.getDate(),
      inMonth: d.getMonth() === m.getMonth(),
      isToday: key === todayKey.value,
      items: byDay.value.get(key) || [],
    })
  }
  return out
})

function cellShellClass(cell) {
  if (!cell.inMonth) return 'border-transparent bg-transparent opacity-35'
  if (cell.items.length) {
    return 'border-indigo-200/90 bg-gradient-to-b from-indigo-50/90 to-white shadow-sm'
  }
  return 'border-slate-100 bg-slate-50/60'
}

function shiftMonth(delta) {
  const n = new Date(viewMonth.value)
  n.setMonth(n.getMonth() + delta)
  viewMonth.value = startOfMonth(n)
}

function departTime(req) {
  try {
    const d = new Date(req.depart_at)
    return d.toLocaleTimeString(locale.value === 'en' ? 'en-US' : 'vi-VN', {
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return ''
  }
}

function routeLabel(req) {
  const o = String(req?.origin || '').trim()
  const d = String(req?.destination || '').trim()
  if (o && d) return `${o} → ${d}`
  if (o || d) return o || d
  return t('portal.extracurricular_table.no_route')
}

function hsActual(req) {
  const n = req?.student_count_actual
  if (n != null && n !== '') return String(n)
  return '—'
}

function hsPlan(req) {
  const n = req?.passenger_count
  if (n != null && n !== '') return Number(n)
  return null
}

function trackingKey(req) {
  return extracurricularStudentCountTrackingKey(req)
}

function trackingLabel(req) {
  const k = trackingKey(req)
  return t(`portal.extracurricular_table.tracking_${k}`)
}

function trackingTone(req) {
  switch (trackingKey(req)) {
    case 'coordinated':
      return 'bg-emerald-50 text-emerald-900 ring-emerald-200'
    case 'submitted':
      return 'bg-sky-50 text-sky-900 ring-sky-200'
    case 'updated':
      return 'bg-amber-50 text-amber-950 ring-amber-200'
    default:
      return 'bg-slate-100 text-slate-600 ring-slate-200'
  }
}
</script>
