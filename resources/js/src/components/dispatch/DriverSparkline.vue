<template>
  <div class="sparkline" aria-hidden="true">
    <div v-for="day in data" :key="day.date" class="spark-col">
      <div class="spark-bar-wrap">
        <div
          class="spark-bar"
          :style="{
            height: `${barPct(day.trip_count)}%`,
            background: day.date === highlightToday ? '#8B1A1A' : '#D3D1C7',
          }"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  data: { type: Array, required: true },
  highlightToday: { type: String, required: true },
})

const max = computed(() => Math.max(1, ...props.data.map((d) => d.trip_count)))

function barPct(count) {
  return Math.max(8, Math.round((count / max.value) * 100))
}
</script>

<style scoped>
.sparkline {
  display: flex;
  align-items: flex-end;
  gap: 2px;
  width: 56px;
  height: 24px;
}
.spark-col {
  flex: 1;
  display: flex;
  align-items: flex-end;
  height: 100%;
}
.spark-bar-wrap {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: flex-end;
}
.spark-bar {
  width: 100%;
  border-radius: 1px;
  transition: height 0.2s ease;
}
</style>
