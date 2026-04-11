<template>
  <div class="space-y-4">
    <Card title="Lịch chuyến — 7 ngày tới">
      <p class="mb-4 text-sm text-slate-600">
        Gom chuyến theo ngày xuất phát (giờ địa phương). Nhấn chuyến để mở chi tiết.
      </p>
      <div class="mb-4 flex flex-wrap items-center gap-2">
        <Button variant="secondary" type="button" :loading="loading" @click="load">Làm mới</Button>
        <span v-if="error" class="text-sm text-rose-600">{{ error }}</span>
      </div>

      <div v-if="loading && !trips.length" class="text-sm text-slate-500">Đang tải…</div>

      <div class="space-y-4 lg:hidden">
        <details
          v-for="day in dayColumns"
          :key="day.key"
          class="rounded-lg border border-slate-200 bg-white open:bg-slate-50/50"
          :open="day.trips.length > 0"
        >
          <summary class="cursor-pointer list-none px-3 py-2 text-sm font-medium marker:content-none [&::-webkit-details-marker]:hidden">
            <span class="text-slate-900">{{ dayLabel(day.key) }}</span>
            <span class="ml-2 text-xs font-normal text-slate-500">({{ day.trips.length }} chuyến)</span>
          </summary>
          <ul class="space-y-2 border-t border-slate-100 px-3 py-2">
            <li v-for="t in day.trips" :key="t.id">
              <RouterLink :to="`/trips/${t.id}`" class="block rounded-md border border-slate-100 bg-white p-2 text-sm hover:border-slate-300">
                <div class="font-medium text-slate-900">#{{ t.id }} · {{ labelTripStatus(t.status) }}</div>
                <div class="mt-0.5 text-xs text-slate-600">{{ fmtTime(t.depart_at) }}</div>
                <div v-if="tripRoute(t)" class="mt-1 truncate text-xs text-slate-500">{{ tripRoute(t) }}</div>
              </RouterLink>
            </li>
            <li v-if="!day.trips.length" class="text-xs text-slate-400">Không có chuyến.</li>
          </ul>
        </details>
      </div>

      <div class="hidden lg:block overflow-x-auto">
        <div class="grid min-w-[900px] grid-cols-7 gap-2">
          <div v-for="day in dayColumns" :key="day.key" class="flex min-h-[280px] flex-col rounded-lg border border-slate-200 bg-slate-50/50 p-2">
            <div class="border-b border-slate-200 pb-2 text-center text-xs font-semibold text-slate-800">
              {{ dayLabel(day.key) }}
            </div>
            <div class="mt-2 flex flex-1 flex-col gap-2 overflow-y-auto">
              <RouterLink
                v-for="t in day.trips"
                :key="t.id"
                :to="`/trips/${t.id}`"
                class="rounded-md border border-slate-200 bg-white p-2 text-left text-xs shadow-sm hover:border-slate-400"
              >
                <div class="font-semibold text-slate-900">#{{ t.id }}</div>
                <div class="text-[11px] text-slate-600">{{ labelTripStatus(t.status) }}</div>
                <div class="mt-1 text-[11px] text-slate-500">{{ fmtTime(t.depart_at) }}</div>
              </RouterLink>
              <div v-if="!day.trips.length" class="flex flex-1 items-center justify-center text-[11px] text-slate-400">Trống</div>
            </div>
          </div>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import { listTrips } from '../../api/trips'
import { eachLocalDayKey, shortViDayLabel, toLocalDateKey } from '../../util/dates'
import { labelTripStatus } from '../../util/labels'

const loading = ref(false)
const error = ref('')
const trips = ref([])

const rangeKeys = computed(() => eachLocalDayKey(new Date(), 7))

const dayColumns = computed(() => {
  const keys = rangeKeys.value
  const map = Object.fromEntries(keys.map((k) => [k, []]))
  for (const t of trips.value) {
    const k = toLocalDateKey(t.depart_at)
    if (map[k]) map[k].push(t)
  }
  for (const k of keys) {
    map[k].sort((a, b) => new Date(a.depart_at) - new Date(b.depart_at))
  }
  return keys.map((key) => ({ key, trips: map[key] }))
})

function dayLabel(key) {
  const d = new Date()
  const todayKey = toLocalDateKey(d)
  if (key === todayKey) return `Hôm nay ${shortViDayLabel(key)}`
  return shortViDayLabel(key)
}

function fmtTime(v) {
  if (!v) return ''
  return new Date(v).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

function tripRoute(t) {
  const r = t.dispatch_request ?? t.dispatchRequest
  if (!r) return ''
  const a = r.origin || ''
  const b = r.destination || ''
  if (a && b) return `${a} → ${b}`
  return a || b || ''
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const keys = rangeKeys.value
    const res = await listTrips({
      from: keys[0],
      to: keys[keys.length - 1],
      per_page: 100,
      page: 1,
    })
    trips.value = res.items ?? []
  } catch (e) {
    error.value = e?.response?.data?.message ?? 'Không tải được lịch.'
    trips.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
