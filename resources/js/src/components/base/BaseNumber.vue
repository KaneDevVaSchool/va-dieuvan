<template>
  <div class="flex flex-col gap-1">
    <label v-if="label" class="text-xs font-medium text-slate-600">{{ label }}</label>
    <input
      type="number"
      class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 focus:border-va-800 focus:outline-none focus:ring-2 focus:ring-va-800/25"
      :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-200/40': error }"
      :min="min"
      :step="formatCurrency ? 1000 : 1"
      :value="modelValue ?? ''"
      @input="onInput"
    />
    <p v-if="error" class="text-xs font-medium text-rose-600">{{ error }}</p>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: [Number, null], default: null },
  label: { type: String, default: '' },
  min: { type: Number, default: undefined },
  error: { type: String, default: '' },
  formatCurrency: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

function onInput(e) {
  const val = e.target.valueAsNumber
  emit('update:modelValue', Number.isNaN(val) ? null : val)
}
</script>
