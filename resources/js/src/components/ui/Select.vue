<template>
  <label class="block">
    <div v-if="label" class="mb-1 text-xs font-medium text-slate-600 dark:text-slate-400">
      {{ label }}<span v-if="required" class="text-rose-600 dark:text-rose-400"> *</span>
    </div>
    <select
      :value="modelValue"
      :disabled="disabled"
      class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option v-if="placeholder" value="">{{ placeholder }}</option>
      <slot />
    </select>
    <div v-if="error" class="mt-1 text-xs text-rose-600">{{ error }}</div>
  </label>
</template>

<script setup>
defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  hint: { type: String, default: '' },
  error: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>

