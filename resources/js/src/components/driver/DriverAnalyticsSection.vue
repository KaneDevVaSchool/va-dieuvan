<template>
  <section class="space-y-4 transition-opacity duration-300 ease-out">
    <h2 class="text-base font-bold tracking-tight text-slate-900 dark:text-white sm:text-lg">
      {{ t('driver_home.analytics_title') }}
    </h2>

    <div
      v-if="loading"
      class="grid grid-cols-1 gap-4 md:grid-cols-2"
    >
      <div class="h-[240px] animate-pulse rounded-2xl bg-slate-200/90 dark:bg-slate-800/90 md:h-[260px]" />
      <div class="h-[280px] animate-pulse rounded-2xl bg-slate-200/90 dark:bg-slate-800/90 md:h-[300px]" />
    </div>

    <div
      v-else
      class="grid grid-cols-1 gap-4 md:grid-cols-2"
    >
      <!-- Donut -->
      <div
        class="rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50 md:min-h-0"
      >
        <h3 class="mb-3 text-sm font-semibold text-slate-800 dark:text-slate-100">
          {{ t('driver_home.chart_donut_title') }}
        </h3>
        <DashboardEChart
          :option="donutOption"
          :height="chartHeightSm"
          :aria-label="t('driver_home.chart_donut_title')"
        />
      </div>

      <!-- Trend: full width row on mobile; span 2 on md so filters + chart breathe -->
      <div
        class="rounded-2xl border border-slate-200/80 bg-white/80 p-4 shadow-sm backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/50"
      >
        <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">
            {{ t('driver_home.chart_trend_title') }}
          </h3>
          <div
            class="flex w-full max-w-full overflow-x-auto rounded-xl border border-slate-200/90 bg-slate-50/90 p-0.5 dark:border-slate-600/80 dark:bg-slate-800/80 sm:w-auto sm:shrink-0 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            role="tablist"
          >
            <button
              v-for="seg in segments"
              :key="seg.id"
              type="button"
              role="tab"
              class="min-h-[40px] min-w-0 flex-1 whitespace-nowrap rounded-lg px-3 py-2 text-center text-xs font-semibold transition duration-200 sm:flex-none sm:px-3.5 sm:text-sm"
              :class="
                trendPeriod === seg.id
                  ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white'
                  : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
              "
              :aria-selected="trendPeriod === seg.id"
              @click="setPeriod(seg.id)"
            >
              {{ seg.label }}
            </button>
          </div>
        </div>

        <div
          v-if="trendPeriod === 'custom'"
          class="mb-3 flex flex-wrap items-end gap-3"
        >
          <label class="min-w-0 flex flex-1 flex-col gap-1 sm:flex-none sm:w-40">
            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
              {{ t('driver_home.filter_from') }}
            </span>
            <input
              v-model="customFrom"
              type="date"
              class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
            >
          </label>
          <label class="min-w-0 flex flex-1 flex-col gap-1 sm:flex-none sm:w-40">
            <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
              {{ t('driver_home.filter_to') }}
            </span>
            <input
              v-model="customTo"
              type="date"
              class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
            >
          </label>
        </div>

        <DashboardEChart
          :option="trendOption"
          :height="chartHeightLg"
          :aria-label="t('driver_home.chart_trend_title')"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import DashboardEChart from '../dashboard/DashboardEChart.vue'
import {
  computeDriverTripCounts,
  driverStatusDonutOption,
  driverTrendOption,
} from '../../util/driverDashboardCharts'

const props = defineProps({
  rawTrips: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const { t } = useI18n()

const trendPeriod = ref('7d')
const customFrom = ref('')
const customTo = ref('')

const chartHeightSm = ref('200px')
const chartHeightLg = ref('220px')

function ymd(d) {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function syncChartHeights() {
  if (typeof window === 'undefined') return
  const smUp = window.matchMedia('(min-width: 640px)').matches
  chartHeightSm.value = smUp ? '240px' : '200px'
  chartHeightLg.value = smUp ? '260px' : '220px'
}

function seedCustomRange() {
  const now = new Date()
  const start = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  start.setDate(start.getDate() - 6)
  customFrom.value = ymd(start)
  customTo.value = ymd(now)
}

function setPeriod(id) {
  trendPeriod.value = id
  if (id === 'custom') seedCustomRange()
}

watch(
  trendPeriod,
  (id) => {
    if (id === 'custom' && (!customFrom.value || !customTo.value)) seedCustomRange()
  },
  { immediate: true },
)

onMounted(() => {
  syncChartHeights()
  window.addEventListener('resize', syncChartHeights, { passive: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', syncChartHeights)
})

const segments = computed(() => [
  { id: 'today', label: t('driver_home.filter_today') },
  { id: '7d', label: t('driver_home.filter_7d') },
  { id: '30d', label: t('driver_home.filter_30d') },
  { id: 'custom', label: t('driver_home.filter_custom') },
])

const countsByStatus = computed(() => computeDriverTripCounts(props.rawTrips))

const donutOption = computed(() =>
  driverStatusDonutOption({
    countsByStatus: countsByStatus.value,
    labelMap: {
      completed: t('driver_home.label_completed'),
      in_progress: t('driver_home.label_in_progress'),
      pending: t('driver_home.label_pending'),
      cancelled: t('driver_home.label_cancelled'),
    },
    emptyText: t('driver_home.chart_empty'),
  }),
)

const trendOption = computed(() =>
  driverTrendOption({
    rawTrips: props.rawTrips,
    period: trendPeriod.value,
    customFrom: customFrom.value,
    customTo: customTo.value,
    emptyText: t('driver_home.chart_empty'),
    yAxisName: t('driver_home.trend_y_label'),
    tripsSuffix: t('driver_home.trend_trips_suffix'),
  }),
)
</script>
