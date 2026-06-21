<script setup>
import { computed } from 'vue'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import { vi } from 'date-fns/locale'

const props = defineProps({
  modelValue: { type: [String, Date, null], default: '' },
  placeholder: { type: String, default: '' },
  minDate: { type: [String, Date, null], default: null },
  maxDate: { type: [String, Date, null], default: null },
  inputId: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const inner = computed({
  get: () => props.modelValue || '',
  set: (v) => emit('update:modelValue', v || ''),
})
</script>

<template>
  <VueDatePicker
    :id="inputId"
    v-model="inner"
    model-type="yyyy-MM-dd"
    format="dd/MM/yyyy"
    :enable-time-picker="false"
    auto-apply
    :teleport="true"
    :placeholder="placeholder"
    :min-date="minDate || undefined"
    :max-date="maxDate || undefined"
    :locale="vi"
    class="filter-date-picker w-full"
    :aria-label="placeholder"
    data-testid="filter-date-picker"
  />
</template>

<style scoped>
.filter-date-picker :deep(.dp__input) {
  @apply h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-va-700 focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100;
}

.filter-date-picker :deep(.dp__active_date) {
  background: #9a0036;
}
</style>
