<template>
  <span class="relative mt-1 block min-w-0 w-full">
    <input
      v-bind="$attrs"
      type="date"
      :value="modelValue"
      :class="!modelValue ? 'text-transparent [&::-webkit-datetime-edit]:invisible' : 'text-driver-ink'"
      class="block min-h-[48px] min-w-0 w-full rounded-xl border border-white/10 bg-driver-surface px-3 py-2 text-base [color-scheme:dark] focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
      @input="$emit('update:modelValue', $event.target.value)"
    />
    <span v-if="!modelValue" class="pointer-events-none absolute inset-y-0 left-3 right-10 flex items-center truncate text-base text-driver-muted/60" aria-hidden="true">{{ placeholder }}</span>
  </span>
</template>

<script setup>
defineOptions({ inheritAttrs: false })
defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, required: true },
})
defineEmits(['update:modelValue'])
</script>
