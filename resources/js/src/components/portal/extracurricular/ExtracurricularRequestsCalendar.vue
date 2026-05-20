<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-4 flex items-center justify-between gap-2">
      <button
        type="button"
        class="rounded-lg border border-slate-200 px-2 py-1 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        @click="shiftMonth(-1)"
      >
        ‹
      </button>
      <h3 class="text-sm font-bold text-slate-900">{{ monthLabel }}</h3>
      <button
        type="button"
        class="rounded-lg border border-slate-200 px-2 py-1 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        @click="shiftMonth(1)"
      >
        ›
      </button>
    </div>
    <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-semibold uppercase text-slate-500">
      <span v-for="wd in weekdayHeaders" :key="wd">{{ wd }}</span>
    </div>
    <div class="mt-1 grid grid-cols-7 gap-1">
      <div
        v-for="cell in cells"
        :key="cell.key"
        class="min-h-[4.5rem] rounded-lg border p-1 text-left"
        :class="
          cell.inMonth
            ? cell.items.length
              ? 'border-indigo-200 bg-indigo-50/40'
              : 'border-slate-100 bg-slate-50/50'
            : 'border-transparent bg-transparent opacity-40'
        "
      >
        <span class="text-xs font-semibold text-slate-700">{{ cell.dayNum }}</span>
        <ul v-if="cell.items.length" class="mt-0.5 space-y-0.5">
          <li v-for="req in cell.items.slice(0, 3)" :key="req.id">
            <RouterLink
              :to="{ name: detailRouteName, params: { id: req.id } }"
              class="block truncate rounded px-0.5 text-[10px] font-medium text-indigo-800 hover:underline"
            >
              #{{ req.id }} {{ departTime(req) }}
            </RouterLink>
          </li>
          <li v-if="cell.items.length > 3" class="text-[10px] text-slate-500">+{{ cell.items.length - 3 }}</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  requests: { type: Array, default: () => [] },
  detailRouteName: { type: String, required: true },
})

const { locale } = useI18n()

const viewMonth = ref(startOfMonth(new Date()))

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
      items: byDay.value.get(key) || [],
    })
  }
  return out
})

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
</script>
