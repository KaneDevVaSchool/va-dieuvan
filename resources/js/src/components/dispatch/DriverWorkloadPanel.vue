<template>
  <div
    class="flex h-full flex-col border-l border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
    @click.stop
  >
    <div class="flex shrink-0 items-start justify-between gap-2 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
      <div class="min-w-0">
        <div class="text-[14px] font-semibold text-slate-900 dark:text-slate-100">
          {{ t('trip_detail.coordination.workload_panel_title') }}
        </div>
        <div class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ weekRange }}</div>
      </div>
      <button
        type="button"
        class="rounded-md p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:hover:bg-slate-800 dark:hover:text-slate-100"
        :aria-label="t('trip_detail.coordination.workload_panel_close')"
        @click="emit('close')"
      >
        ✕
      </button>
    </div>

    <div class="flex shrink-0 gap-1 border-b border-slate-100 px-2 py-2 dark:border-slate-800">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        type="button"
        class="rounded-md px-2 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
        :class="
          sortBy === tab.value ? 'bg-rose-50 text-[#8B1A1A] dark:bg-rose-950/40 dark:text-rose-200' : ''
        "
        @click="sortBy = tab.value"
      >
        {{ tab.label }}
      </button>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto px-2 py-2">
      <div
        v-for="driver in sortedDrivers"
        :key="driver.id"
        class="mb-1 flex cursor-pointer items-center gap-2 rounded-lg px-2 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/80"
        :class="selectedLocalId === driver.id ? 'bg-rose-50/80 ring-1 ring-[#8B1A1A]/25 dark:bg-rose-950/25' : ''"
        @mouseenter="prefetchDetail(driver.id)"
        @click="selectDriver(driver.id)"
      >
        <div
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-200 text-[11px] font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100"
        >
          {{ initials(driver.label) }}
        </div>
        <div class="min-w-0 flex-1">
          <div class="truncate text-[13px] font-medium text-slate-900 dark:text-slate-100">{{ driver.label }}</div>
          <div class="text-[11px] text-slate-500 dark:text-slate-400">
            {{ wl(driver.id)?.trips_this_week ?? 0 }} {{ t('trip_detail.coordination.workload_trips_week_suffix') }} ·
            {{ wl(driver.id)?.total_hours ?? 0 }}{{ t('trip_detail.coordination.workload_hours_week_suffix') }}
          </div>
        </div>
        <div class="flex w-[88px] shrink-0 flex-col items-end gap-0.5">
          <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-600">
            <div
              class="h-full rounded-full transition-[width] duration-300"
              :style="{
                width: `${wl(driver.id)?.load_score ?? 0}%`,
                background: loadColor(wl(driver.id)?.load_level ?? 'low').bar,
              }"
            />
          </div>
          <span
            class="text-[10px] font-medium tabular-nums"
            :style="{ color: loadColor(wl(driver.id)?.load_level ?? 'low').text }"
          >
            {{ wl(driver.id)?.load_score ?? 0 }}%
          </span>
        </div>
        <DriverSparkline
          v-if="detailCache[driver.id]?.chart?.length"
          :data="detailCache[driver.id].chart"
          :highlight-today="tripDate"
        />
        <div v-else class="h-6 w-14 shrink-0 rounded bg-slate-100 dark:bg-slate-800" />
      </div>
    </div>

    <Transition name="wp-slide">
      <div
        v-if="selectedDetail"
        class="shrink-0 border-t border-slate-200 bg-slate-50/90 px-3 py-3 dark:border-slate-700 dark:bg-slate-950/50"
      >
        <div class="text-[12px] font-semibold text-slate-900 dark:text-slate-100">
          {{ selectedDetail.name }} — {{ t('trip_detail.coordination.workload_detail_chart_title') }}
        </div>
        <div class="mt-3 flex h-28 items-end justify-between gap-1 px-1">
          <div v-for="day in selectedDetail.chart" :key="day.date" class="flex min-w-0 flex-1 flex-col items-center gap-1">
            <div class="flex h-20 w-full max-w-[28px] items-end justify-center">
              <div
                class="w-full min-h-[4px] rounded-t-sm transition-all"
                :class="day.date === tripDate ? 'bg-[#8B1A1A]' : 'bg-[#D3D1C7]'"
                :style="{ height: `${barHeight(day.trip_count)}%` }"
                :title="`${day.trip_count} · ${day.hours}h`"
              />
            </div>
            <div class="text-[9px] font-medium text-slate-500 dark:text-slate-400">{{ day.day_label }}</div>
            <div class="text-[10px] tabular-nums text-slate-600 dark:text-slate-300">{{ day.trip_count }}</div>
          </div>
        </div>

        <div v-if="selectedDetail.upcoming?.length" class="mt-3">
          <div class="text-[11px] font-semibold text-slate-700 dark:text-slate-200">
            {{ t('trip_detail.coordination.workload_upcoming_title') }}
          </div>
          <div
            v-for="row in selectedDetail.upcoming"
            :key="row.id"
            class="mt-1 flex items-baseline justify-between gap-2 text-[11px]"
          >
            <span class="font-mono font-medium text-slate-800 dark:text-slate-100">{{ row.trip_code }}</span>
            <span class="shrink-0 text-slate-500 dark:text-slate-400">{{ formatDateTime(row.scheduled_at) }}</span>
          </div>
        </div>

        <button
          type="button"
          class="mt-3 w-full rounded-md bg-[#8B1A1A] px-3 py-2 text-[12px] font-semibold text-white hover:bg-[#751515]"
          @click="emit('assign', selectedLocalId)"
        >
          {{ t('trip_detail.coordination.workload_assign_cta') }}
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  useDriverWorkload,
  type DriverWorkloadDetailPayload,
  type WorkloadChartDay,
} from '../../composables/useDriverWorkload'
import DriverSparkline from './DriverSparkline.vue'

type DriverRow = { id: number | string; label: string }

const props = defineProps<{
  drivers: DriverRow[]
  tripDate: string
  selectedDriverId?: number | null
}>()

const emit = defineEmits<{
  close: []
  assign: [driverId: number]
}>()

const { t } = useI18n()

const { workloadMap, fetchDetail, getWorkload, loadColor, detailCache, fetchWorkload } = useDriverWorkload(
  () => props.tripDate,
)

onMounted(() => {
  void fetchWorkload()
})

watch(
  () => props.tripDate,
  () => {
    void fetchWorkload()
  },
)

const sortBy = ref<'name' | 'load_asc' | 'load_desc' | 'trips'>('load_asc')
const selectedLocalId = ref<number | null>(null)
const selectedDetail = ref<(DriverWorkloadDetailPayload & { name?: string }) | null>(null)

const tabs = computed(() => [
  { value: 'load_asc' as const, label: t('trip_detail.coordination.workload_sort_load_asc') },
  { value: 'load_desc' as const, label: t('trip_detail.coordination.workload_sort_load_desc') },
  { value: 'trips' as const, label: t('trip_detail.coordination.workload_sort_trips') },
  { value: 'name' as const, label: t('trip_detail.coordination.workload_sort_name') },
])

watch(
  () => props.selectedDriverId,
  (v: number | null | undefined) => {
    selectedLocalId.value = v != null && Number.isFinite(Number(v)) ? Number(v) : null
  },
  { immediate: true },
)

function wl(id: number | string): ReturnType<typeof getWorkload> {
  const n = Number(id)
  if (!Number.isFinite(n)) return null
  return getWorkload(n)
}

const sortedDrivers = computed(() => {
  const list = props.drivers
    .filter((d: DriverRow) => Number.isFinite(Number(d.id)))
    .map((d: DriverRow) => ({ ...d, id: Number(d.id) }))
  return [...list].sort((a, b) => {
    const wa = getWorkload(a.id)
    const wb = getWorkload(b.id)
    if (sortBy.value === 'load_asc') return (wa?.load_score ?? 0) - (wb?.load_score ?? 0)
    if (sortBy.value === 'load_desc') return (wb?.load_score ?? 0) - (wa?.load_score ?? 0)
    if (sortBy.value === 'trips') return (wb?.trips_this_week ?? 0) - (wa?.trips_this_week ?? 0)
    return String(a.label).localeCompare(String(b.label), 'vi')
  })
})

const weekRange = computed(() => {
  const td = props.tripDate
  const d = new Date(`${td}T12:00:00`)
  if (Number.isNaN(d.getTime())) return ''
  const dow = d.getDay() || 7
  const mon = new Date(d)
  mon.setDate(d.getDate() - (dow - 1))
  const sun = new Date(mon)
  sun.setDate(mon.getDate() + 6)
  const fmt = (x: Date) =>
    `${String(x.getDate()).padStart(2, '0')}/${String(x.getMonth() + 1).padStart(2, '0')}`
  return `${fmt(mon)} – ${fmt(sun)}`
})

const maxTripsDetail = computed(() => {
  const ch = selectedDetail.value?.chart
  if (!ch?.length) return 1
  return Math.max(1, ...ch.map((c: WorkloadChartDay) => c.trip_count))
})

function barHeight(count: number) {
  return Math.max(6, Math.round((count / maxTripsDetail.value) * 100))
}

async function selectDriver(id: number) {
  selectedLocalId.value = id
  const detail = await fetchDetail(id)
  const driver = props.drivers.find((d: DriverRow) => Number(d.id) === id)
  selectedDetail.value = { ...detail, name: driver?.label }
}

function prefetchDetail(id: number | string) {
  const n = Number(id)
  if (!Number.isFinite(n)) return
  void fetchDetail(n)
}

function initials(name: string) {
  const parts = String(name || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean)
  const take = parts.slice(-2)
  return take.map((w) => w[0]).join('').toUpperCase() || '?'
}

function formatDateTime(iso: string) {
  try {
    const d = new Date(iso)
    if (Number.isNaN(d.getTime())) return iso
    return new Intl.DateTimeFormat('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }).format(d)
  } catch {
    return iso
  }
}
</script>

<style scoped>
.wp-slide-enter-active,
.wp-slide-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.wp-slide-enter-from,
.wp-slide-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
