<template>
  <div class="driver-workload-badge">
    <div class="load-bar-wrap">
      <div
        class="load-bar-fill"
        :style="{ width: `${Math.min(100, w.load_score)}%`, background: color.bar }"
      />
    </div>
    <span class="load-text" :style="{ color: color.text }">
      {{ w.trips_today }} {{ t('trip_detail.coordination.workload_today_suffix') }} · {{ w.load_score }}%
    </span>
    <span class="load-level-badge" :style="{ background: color.bg, color: color.text }">
      {{ levelLabel }}
    </span>
    <span v-if="w.is_resting" class="rest-badge">
      {{ t('trip_detail.coordination.workload_resting_badge') }}
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  workload: { type: Object, required: true },
  colorFn: { type: Function, required: true },
})

const { t } = useI18n()

const w = computed(() => props.workload)
const color = computed(() => props.colorFn(w.value.load_level))
const levelLabel = computed(() => {
  const m = {
    low: t('trip_detail.coordination.workload_level_low'),
    medium: t('trip_detail.coordination.workload_level_medium'),
    high: t('trip_detail.coordination.workload_level_high'),
  }
  return m[w.value.load_level] ?? ''
})
</script>

<style scoped>
.driver-workload-badge {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  margin-top: 3px;
}
.load-bar-wrap {
  width: 40px;
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
  overflow: hidden;
}
.load-bar-fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.3s ease;
}
.load-text {
  font-size: 11px;
}
.load-level-badge {
  font-size: 10px;
  font-weight: 500;
  padding: 1px 6px;
  border-radius: 4px;
}
.rest-badge {
  font-size: 10px;
  background: #faeeda;
  color: #633806;
  padding: 1px 6px;
  border-radius: 4px;
}
</style>
