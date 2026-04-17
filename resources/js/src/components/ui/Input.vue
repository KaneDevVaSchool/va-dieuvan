<template>
  <label class="block" @click="onLabelClick">
    <div v-if="label" class="mb-1 text-xs font-medium text-slate-600 dark:text-slate-400">
      {{ label }}<span v-if="required" class="text-rose-600 dark:text-rose-400"> *</span>
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
    <div v-if="hint" class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ hint }}</div>
    <div v-if="error" class="mt-1 text-xs text-rose-600">{{ error }}</div>
  </label>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  hint: { type: String, default: '' },
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
