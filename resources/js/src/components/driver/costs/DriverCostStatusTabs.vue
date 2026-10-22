<template>
  <div
    class="grid grid-cols-4 gap-1 px-3.5 pb-2 sm:px-5"
    role="tablist"
    :aria-label="ariaLabel"
  >
    <button
      v-for="tab in tabs"
      :key="tab.value || 'all'"
      type="button"
      role="tab"
      class="min-h-[34px] min-w-0 whitespace-nowrap rounded-lg px-1 text-xs font-semibold transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-driver-accent/70 active:scale-[0.98] min-[375px]:text-[13px] sm:text-sm"
      :class="
        modelValue === tab.value
          ? 'bg-driver-accent/15 text-driver-accent'
          : 'bg-transparent text-driver-muted hover:bg-white/[0.04] hover:text-driver-ink'
      "
      :aria-selected="modelValue === tab.value"
      @click="emit('update:modelValue', tab.value)"
    >
      {{ tab.label }}
    </button>
  </div>
</template>

<script setup>
defineProps({
  tabs: { type: Array, required: true },
  modelValue: { type: String, default: '' },
  ariaLabel: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])
</script>
