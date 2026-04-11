<template>
  <label class="block">
    <div v-if="label" class="mb-1 text-xs font-medium text-slate-600">{{ label }}</div>
    <select
      :value="modelValue"
      :disabled="disabled"
      class="w-full rounded-md border bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option v-if="placeholder" value="">{{ placeholder }}</option>
      <slot />
    </select>
    <div v-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</div>
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
})

defineEmits(['update:modelValue'])
</script>

