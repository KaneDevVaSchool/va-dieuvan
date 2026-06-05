<template>
  <label class="block">
    <div v-if="label" class="mb-1 flex items-center gap-1 text-xs font-medium text-slate-600 dark:text-slate-400">
      <span>{{ label }}<span v-if="required" class="text-rose-600 dark:text-rose-400"> *</span></span>
      <span
        v-if="tooltip"
        :title="tooltip"
        class="inline-flex cursor-help text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-200"
      >
        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 012 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
        </svg>
      </span>
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
  tooltip: { type: String, default: '' },
  error: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])
</script>

