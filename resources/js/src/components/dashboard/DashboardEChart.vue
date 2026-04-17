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
import * as echarts from 'echarts'

const props = defineProps({
  option: { type: Object, default: null },
  height: { type: String, default: '260px' },
  ariaLabel: { type: String, default: '' },
})

const emit = defineEmits(['chartClick', 'chartDblclick'])

const hostRef = ref(null)
const chartRef = shallowRef(null)
let resizeObs = null

function applyOption() {
  if (!chartRef.value) return
  if (props.option) {
    chartRef.value.setOption(props.option, { notMerge: true })
  }
}

onMounted(() => {
  if (!hostRef.value) return
  const chart = echarts.init(hostRef.value, null, { renderer: 'canvas' })
  chartRef.value = chart
  chart.on('click', (p) => emit('chartClick', p))
  chart.on('dblclick', () => emit('chartDblclick'))
  applyOption()
  resizeObs = new ResizeObserver(() => chart.resize())
  resizeObs.observe(hostRef.value)
})

watch(
  () => props.option,
  () => applyOption(),
  { deep: true },
)

onBeforeUnmount(() => {
  resizeObs?.disconnect()
  resizeObs = null
  chartRef.value?.dispose()
  chartRef.value = null
})
</script>
