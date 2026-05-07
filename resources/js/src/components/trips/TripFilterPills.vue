<template>
  <div class="sticky top-[3.25rem] z-20 -mx-3 border-b border-[rgba(255,255,255,0.06)] bg-[#09180f]/92 px-3 py-2 backdrop-blur-md sm:-mx-4">
    <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-[#7fdcc8]">
      {{ title }}
    </p>
    <div class="flex gap-2 overflow-x-auto pb-1">
      <button
        v-for="opt in options"
        :key="opt.id"
        type="button"
        class="shrink-0 rounded-full px-4 py-2 text-xs font-semibold transition active:scale-[0.98]"
        :class="
          modelValue === opt.id
            ? 'bg-[#7fdcc8] text-[#09180f]'
            : 'border border-[rgba(255,255,255,0.08)] bg-[#0f2318] text-[#94a3b8]'
        "
        @click="$emit('update:modelValue', opt.id)"
      >
        {{ opt.label }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

defineProps({
  modelValue: { type: String, required: true },
})

defineEmits(['update:modelValue'])

const { t } = useI18n()

const title = computed(() => t('trip_history_page.filters_title'))

const options = computed(() => [
  { id: 'all', label: t('trip_history_page.filter_all') },
  { id: 'completed', label: t('trip_history_page.filter_completed') },
  { id: 'pending', label: t('trip_history_page.filter_pending') },
  { id: 'cancelled', label: t('trip_history_page.filter_cancelled') },
])
</script>
