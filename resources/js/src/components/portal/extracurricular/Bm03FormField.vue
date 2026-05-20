<template>
  <label class="block min-w-0">
    <span class="block text-[11px] font-bold uppercase tracking-wide text-slate-600">{{ label }}</span>
    <p v-if="hint" :id="hintId" class="mt-0.5 text-xs font-normal normal-case text-slate-500">{{ hint }}</p>
    <textarea
      v-if="multiline"
      :id="inputId"
      :rows="rows"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :autocomplete="autocomplete"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="describedBy"
      class="mt-1 w-full rounded-md border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:bg-slate-100"
      :class="
        error
          ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/30'
          : 'border-slate-300 focus:border-slate-500 focus:ring-slate-500/30'
      "
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <input
      v-else
      :id="inputId"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :autocomplete="autocomplete"
      :inputmode="inputmode"
      :aria-invalid="error ? 'true' : undefined"
      :aria-describedby="describedBy"
      class="mt-1 w-full rounded-md border bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-1 disabled:cursor-not-allowed disabled:bg-slate-100"
      :class="
        error
          ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/30'
          : 'border-slate-300 focus:border-slate-500 focus:ring-slate-500/30'
      "
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <p v-if="error" :id="errorId" class="mt-1.5 text-xs font-medium text-rose-700" role="alert">{{ error }}</p>
  </label>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  modelValue: { type: [String, Number], default: '' },
  placeholder: { type: String, default: '' },
  hint: { type: String, default: '' },
  error: { type: String, default: '' },
  multiline: { type: Boolean, default: false },
  rows: { type: Number, default: 3 },
  type: { type: String, default: 'text' },
  disabled: { type: Boolean, default: false },
  autocomplete: { type: String, default: 'off' },
  inputmode: { type: String, default: undefined },
  fieldId: { type: String, default: '' },
})

defineEmits(['update:modelValue'])

const inputId = computed(() => props.fieldId || undefined)
const hintId = computed(() => (props.fieldId ? `${props.fieldId}-hint` : undefined))
const errorId = computed(() => (props.fieldId ? `${props.fieldId}-err` : undefined))
const describedBy = computed(() => {
  const ids = []
  if (props.hint && hintId.value) ids.push(hintId.value)
  if (props.error && errorId.value) ids.push(errorId.value)
  return ids.length ? ids.join(' ') : undefined
})
</script>
