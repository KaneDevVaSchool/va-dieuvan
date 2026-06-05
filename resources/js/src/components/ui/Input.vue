<template>
  <label class="block" @click="onLabelClick">
    <div v-if="label" class="mb-1 flex items-center gap-1 text-xs font-medium text-slate-600 dark:text-slate-400">
      <span>{{ label }}<span v-if="required" class="text-rose-600 dark:text-rose-400"> *</span></span>
      <span
        v-if="tooltip"
        :title="tooltip"
        class="inline-flex cursor-help text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-200"
        @click.prevent
      >
        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 012 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
        </svg>
      </span>
    </div>
    <input
      ref="inputRef"
      :value="modelValue"
      :type="type"
      :placeholder="placeholder"
      :disabled="disabled"
      :min="min || undefined"
      :max="max || undefined"
      :class="inputClass"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <div v-if="error" class="mt-1 text-xs text-rose-600">{{ error }}</div>
  </label>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  hint: { type: String, default: '' },
  tooltip: { type: String, default: '' },
  error: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  type: { type: String, default: 'text' },
  disabled: { type: Boolean, default: false },
  min: { type: String, default: '' },
  max: { type: String, default: '' },
  required: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])

const inputRef = ref(null)

const isDateLike = computed(() => props.type === 'date' || props.type === 'datetime-local')

const inputClass = computed(() => {
  const base = [
    'w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-600',
  ]
  if (isDateLike.value) {
    base.push(
      'cursor-pointer accent-teal-600 [color-scheme:light] dark:[color-scheme:dark]',
      '[&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:inset-0 [&::-webkit-calendar-picker-indicator]:h-full [&::-webkit-calendar-picker-indicator]:w-full [&::-webkit-calendar-picker-indicator]:cursor-pointer [&::-webkit-calendar-picker-indicator]:opacity-0',
      'relative',
    )
  }
  return base.join(' ')
})

function onLabelClick(e) {
  if (!isDateLike.value || props.disabled) return
  const el = inputRef.value
  if (!el) return
  if (e.target === el) return
  try {
    if (typeof el.showPicker === 'function') {
      el.showPicker()
    } else {
      el.focus()
      el.click()
    }
  } catch {
    el.focus()
  }
}
</script>
