<template>
  <div class="space-y-4">
    <Card title="Lịch tháng (chuyến)">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
          <Button variant="secondary" type="button" @click="shiftMonth(-1)">←</Button>
          <span class="min-w-[10rem] text-center text-sm font-semibold">{{ monthTitle }}</span>
          <Button variant="secondary" type="button" @click="shiftMonth(1)">→</Button>
          <Button variant="secondary" type="button" :loading="loading" @click="load">Tải lại</Button>
        </div>
        <RouterLink class="text-sm text-slate-600 underline" to="/schedule">Lịch 7 ngày</RouterLink>
      </div>
      <p v-if="error" class="mt-2 text-sm text-rose-600">{{ error }}</p>

      <div class="mt-4 overflow-x-auto">
        <div class="grid min-w-[720px] grid-cols-7 gap-1 text-center text-[11px] font-medium text-slate-500">
          <div v-for="h in weekHead" :key="h" class="py-2">{{ h }}</div>
        </div>
        <div class="grid min-w-[720px] grid-cols-7 gap-1">
          <div v-for="(c, idx) in paddedCells" :key="idx" class="min-h-[5.5rem] rounded-md border border-slate-100 bg-slate-50/50 p-1 text-left">
            <template v-if="c.day">
              <div class="text-xs font-semibold text-slate-800">{{ c.day }}</div>
              <ul class="mt-1 space-y-0.5">
                <li v-for="t in c.trips" :key="t.id" class="truncate">
                  <RouterLink :to="`/trips/${t.id}`" class="text-[10px] text-slate-700 underline hover:text-slate-900">
                    #{{ t.id }}
                  </RouterLink>
                </li>
              </ul>
            </template>
          </div>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import { listTrips } from '../../api/trips'
import { daysInMonth, monthRangeKeys, toLocalDateKey, weekdayMon0 } from '../../util/dates'

const cursor = ref(new Date())
const loading = ref(false)
const error = ref('')
const trips = ref([])

const weekHead = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN']

const monthTitle = computed(() => {
  const d = cursor.value
  return d.toLocaleString('vi-VN', { month: 'long', year: 'numeric' })
})

const tripsByDay = computed(() => {
  const m = {}
  for (const t of trips.value) {
    const k = toLocalDateKey(t.depart_at)
    if (!m[k]) m[k] = []
    m[k].push(t)
  }
  Object.keys(m).forEach((k) => m[k].sort((a, b) => new Date(a.depart_at) - new Date(b.depart_at)))
  return m
})

const paddedCells = computed(() => {
  const y = cursor.value.getFullYear()
  const mo = cursor.value.getMonth()
  const first = new Date(y, mo, 1)
  const pad = weekdayMon0(first)
  const totalDays = daysInMonth(y, mo)
  const cells = []
  for (let i = 0; i < pad; i++) {
    cells.push({ day: null, trips: [] })
  }
  for (let day = 1; day <= totalDays; day++) {
    const key = toLocalDateKey(new Date(y, mo, day))
    cells.push({ day, trips: tripsByDay.value[key] ?? [] })
  }
  while (cells.length % 7 !== 0) {
    cells.push({ day: null, trips: [] })
  }
  return cells
})

function shiftMonth(delta) {
  const d = new Date(cursor.value.getFullYear(), cursor.value.getMonth() + delta, 1)
  cursor.value = d
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const y = cursor.value.getFullYear()
    const mo = cursor.value.getMonth()
    const keys = monthRangeKeys(y, mo)
    const from = keys[0]
    const to = keys[keys.length - 1]
    const res = await listTrips({ from, to, per_page: 200, page: 1 })
    trips.value = res.items ?? []
  } catch (e) {
    error.value = e?.response?.data?.message ?? 'Không tải được lịch.'
    trips.value = []
  } finally {
    loading.value = false
  }
}

watch(cursor, load)
onMounted(load)
</script>
