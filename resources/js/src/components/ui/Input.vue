<template>
  <label class="block">
    <div v-if="label" class="mb-1 text-xs font-medium text-slate-600">{{ label }}</div>
    <input
      :value="modelValue"
      :type="type"
      :placeholder="placeholder"
      :disabled="disabled"
      :min="min || undefined"
      :max="max || undefined"
      class="w-full rounded-md border bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring"
      @input="$emit('update:modelValue', $event.target.value)"
    />
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
  type: { type: String, default: 'text' },
  disabled: { type: Boolean, default: false },
  min: { type: String, default: '' },
  max: { type: String, default: '' },
})

defineEmits(['update:modelValue'])
</script>

