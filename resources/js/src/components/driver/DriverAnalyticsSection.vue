<template>
  <section class="space-y-4 transition-opacity duration-300 ease-out">
    <h2 class="text-base font-bold tracking-tight text-[#86c2b5] sm:text-lg">
      {{ t('driver_home.analytics_title') }}
    </h2>

    <div v-if="loading" class="grid grid-cols-1 gap-4">
      <div class="h-[260px] animate-pulse rounded-3xl border border-[#86c2b5]/15 bg-[#142421]/90 md:h-[280px]" />
    </div>

    <div v-else class="grid grid-cols-1 gap-4">
      <div
        class="rounded-3xl border border-[#86c2b5]/20 bg-[#142421] p-4 shadow-lg shadow-black/15 md:min-h-0"
      >
        <h3 class="mb-3 text-sm font-semibold text-[#86c2b5]/90">
          {{ t('driver_home.chart_donut_title') }}
        </h3>
        <DashboardEChart
          :option="donutOption"
          :height="chartHeightSm"
          :aria-label="t('driver_home.chart_donut_title')"
        />

        <div
          v-if="pillRows.length"
          class="mt-4 flex flex-wrap gap-2"
          aria-label="legend"
        >
          <div
            v-for="row in pillRows"
            :key="row.key"
            class="flex items-center gap-2 rounded-full border border-[#86c2b5]/20 bg-[#0d1f1c]/70 px-3 py-1.5 text-xs font-semibold text-[#f1f5f9]"
          >
            <span class="h-2.5 w-2.5 shrink-0 rounded-full ring-1 ring-white/10" :style="{ backgroundColor: row.color }" />
            <span class="text-[#86c2b5]/90">{{ row.label }}</span>
            <span class="tabular-nums text-white">{{ row.value }}</span>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import DashboardEChart from '../dashboard/DashboardEChart.vue'
import {
  computeDriverTripCounts,
  DRIVER_DONUT_COLORS,
  DRIVER_DONUT_ORDER,
  driverStatusDonutOption,
} from '../../util/driverDashboardCharts'

const props = defineProps({
  rawTrips: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const { t } = useI18n()

const chartHeightSm = ref('220px')

function syncChartHeights() {
  if (typeof window === 'undefined') return
  const smUp = window.matchMedia('(min-width: 640px)').matches
  chartHeightSm.value = smUp ? '260px' : '220px'
}

onMounted(() => {
  syncChartHeights()
  window.addEventListener('resize', syncChartHeights, { passive: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', syncChartHeights)
})

const countsByStatus = computed(() => computeDriverTripCounts(props.rawTrips))

const labelMap = computed(() => ({
  completed: t('driver_home.label_completed'),
  in_progress: t('driver_home.label_in_progress'),
  pending: t('driver_home.label_pending'),
  cancelled: t('driver_home.label_cancelled'),
}))

const donutOption = computed(() =>
  driverStatusDonutOption({
    countsByStatus: countsByStatus.value,
    labelMap: labelMap.value,
    emptyText: t('driver_home.chart_empty'),
    centerSuffix: t('driver_home.trend_trips_suffix'),
  }),
)

const pillRows = computed(() =>
  DRIVER_DONUT_ORDER.map((key) => ({
    key,
    label: labelMap.value[key],
    value: Number(countsByStatus.value?.[key] ?? 0),
    color: DRIVER_DONUT_COLORS[key],
  })).filter((row) => row.value > 0),
)
</script>
