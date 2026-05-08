<template>
  <div
    ref="hostRef"
    class="w-full min-h-[160px] overflow-hidden rounded-lg"
    :style="{ height }"
    role="img"
    :aria-label="ariaLabel || undefined"
  />
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, shallowRef } from 'vue'
import * as echarts from 'echarts/core'
import { BarChart, LineChart, PieChart } from 'echarts/charts'
import {
  DatasetComponent,
  GraphicComponent,
  GridComponent,
  LegendComponent,
  TooltipComponent,
} from 'echarts/components'
import { LabelLayout, UniversalTransition } from 'echarts/features'
import { CanvasRenderer } from 'echarts/renderers'

echarts.use([
  DatasetComponent,
  GraphicComponent,
  GridComponent,
  LegendComponent,
  TooltipComponent,
  BarChart,
  LineChart,
  PieChart,
  LabelLayout,
  UniversalTransition,
  CanvasRenderer,
])

const props = defineProps({
  option: { type: Object, default: null },
  height: { type: String, default: '260px' },
  ariaLabel: { type: String, default: '' },
})

const emit = defineEmits(['chartClick', 'chartDblclick'])

const hostRef = ref(null)
const chartRef = shallowRef(null)
let resizeObs = null
let resizeRafId = null

function applyOption() {
  if (!chartRef.value) return
  if (props.option) {
    chartRef.value.setOption(props.option, { notMerge: true, lazyUpdate: true })
  }
}

function scheduleResize() {
  if (resizeRafId != null || !chartRef.value) return
  resizeRafId = requestAnimationFrame(() => {
    resizeRafId = null
    chartRef.value?.resize()
  })
}

onMounted(() => {
  if (!hostRef.value) return
  const dpr =
    typeof window !== 'undefined' ? Math.min(window.devicePixelRatio || 1, 2) : 1
  const chart = echarts.init(hostRef.value, null, {
    renderer: 'canvas',
    devicePixelRatio: dpr,
  })
  chartRef.value = chart
  chart.on('click', (p) => emit('chartClick', p))
  chart.on('dblclick', () => emit('chartDblclick'))
  applyOption()
  resizeObs = new ResizeObserver(scheduleResize)
  resizeObs.observe(hostRef.value)
})

watch(
  () => props.option,
  () => applyOption(),
  { deep: true },
)

onBeforeUnmount(() => {
  if (resizeRafId != null && typeof cancelAnimationFrame !== 'undefined') {
    cancelAnimationFrame(resizeRafId)
    resizeRafId = null
  }
  resizeObs?.disconnect()
  resizeObs = null
  chartRef.value?.dispose()
  chartRef.value = null
})
</script>
