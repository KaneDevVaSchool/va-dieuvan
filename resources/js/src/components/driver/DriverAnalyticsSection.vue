<template>
  <section class="space-y-4 transition-opacity duration-300 ease-out">
    <h2 class="text-base font-bold tracking-tight text-[#9A0036] sm:text-lg">
      {{ t('driver_home.analytics_title') }}
    </h2>

    <div v-if="loading" class="grid grid-cols-1 gap-4">
      <div class="h-[240px] animate-pulse rounded-3xl border border-slate-700/50 bg-[#1E293B]/90 md:h-[260px]" />
    </div>

    <div v-else class="grid grid-cols-1 gap-4">
      <div
        class="rounded-3xl border border-slate-700/60 bg-[#1E293B] p-4 shadow-lg shadow-black/20 md:min-h-0"
      >
        <h3 class="mb-3 text-sm font-semibold text-slate-100">
          {{ t('driver_home.chart_donut_title') }}
        </h3>
        <DashboardEChart
          :option="donutOption"
          :height="chartHeightSm"
          :aria-label="t('driver_home.chart_donut_title')"
        />
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import DashboardEChart from '../dashboard/DashboardEChart.vue'
import { computeDriverTripCounts, driverStatusDonutOption } from '../../util/driverDashboardCharts'

const props = defineProps({
  rawTrips: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const { t } = useI18n()

const chartHeightSm = ref('200px')

function syncChartHeights() {
  if (typeof window === 'undefined') return
  const smUp = window.matchMedia('(min-width: 640px)').matches
  chartHeightSm.value = smUp ? '240px' : '200px'
}

onMounted(() => {
  syncChartHeights()
  window.addEventListener('resize', syncChartHeights, { passive: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', syncChartHeights)
})

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
</script>
