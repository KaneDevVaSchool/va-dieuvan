<template>
  <div class="flex flex-col gap-1">
    <label v-if="label" class="text-xs font-medium text-slate-600">{{ label }}</label>
    <input
      type="datetime-local"
      class="dw-date-input rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm text-slate-900 focus:border-va-800 focus:outline-none focus:ring-2 focus:ring-va-800/25"
      :class="{ 'border-rose-400 focus:border-rose-500 focus:ring-rose-200/40': error }"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="hint && !error" class="text-xs text-slate-400">{{ hint }}</p>
    <p v-if="error" class="text-xs font-medium text-rose-600">{{ error }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, default: '' },
  hint: { type: String, default: '' },
  error: { type: String, default: '' },
})
defineEmits(['update:modelValue'])
</script>
