<template>
  <button
    type="button"
    role="switch"
    :aria-checked="modelValue"
    :disabled="disabled"
    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition focus:outline-none focus:ring-2 focus:ring-va-500/30 disabled:cursor-not-allowed disabled:opacity-50"
    :class="modelValue ? 'bg-va-700' : 'bg-slate-300'"
    @click="toggle"
  >
    <span
      class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition"
      :class="modelValue ? 'translate-x-5' : 'translate-x-0.5'"
    ></span>
  </button>
</template>

<script setup>
const props = defineProps({
  modelValue: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])

function toggle() {
  if (props.disabled) return
  emit('update:modelValue', !props.modelValue)
}
</script>
