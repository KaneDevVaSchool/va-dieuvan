<template>
  <div
    class="sticky top-0 z-30 border-b border-white/[0.06] bg-driver-bg/90 backdrop-blur-md supports-[backdrop-filter]:bg-driver-bg/80"
    :style="{ paddingTop: 'env(safe-area-inset-top)' }"
  >
    <div
      ref="trackRef"
      class="flex min-h-[52px] gap-1 px-3 pb-3 pt-2 sm:min-h-[56px]"
      role="tablist"
      :aria-label="ariaLabel"
    >
      <button
        v-for="tab in tabs"
        :key="tab.value || 'all'"
        type="button"
        role="tab"
        class="min-h-[44px] flex-1 rounded-2xl px-2 text-sm font-semibold transition-all duration-200 ease-out focus:outline-none focus-visible:ring-2 focus-visible:ring-driver-accent/70 active:scale-[0.98] sm:text-base"
        :class="
          modelValue === tab.value
            ? 'bg-driver-elevated text-driver-ink shadow-[0_8px_24px_-8px_rgba(127,220,200,0.35)] ring-1 ring-driver-accent/35'
            : 'bg-transparent text-driver-muted hover:bg-white/[0.04] hover:text-driver-ink'
        "
        :aria-selected="modelValue === tab.value"
        @click="emit('update:modelValue', tab.value)"
      >
        {{ tab.label }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  tabs: { type: Array, required: true },
  modelValue: { type: String, default: '' },
  ariaLabel: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const trackRef = ref(null)
</script>
