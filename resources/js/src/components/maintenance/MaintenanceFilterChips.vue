<template>
  <div class="flex gap-2 overflow-x-auto pb-0.5 scrollbar-none" role="group" :aria-label="t('driver_maintenance.filter_all')">
    <button
      v-for="chip in chips"
      :key="chip.value"
      type="button"
      :class="[
        'flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-semibold transition-all',
        modelValue === chip.value ? chip.activeClass : 'border border-white/20 text-white/70 bg-white/5',
      ]"
      @click="$emit('update:modelValue', chip.value)"
    >
      <span :class="['h-2 w-2 rounded-full', chip.dotClass]" aria-hidden="true" />
      {{ chip.label }}
      <span class="ml-0.5 rounded-full bg-white/15 px-1.5 py-0 text-xs tabular-nums">{{ chip.count }}</span>
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  modelValue: { type: String, default: 'all' },
  counts: { type: Object, default: () => ({ urgent: 0, upcoming: 0, safe: 0 }) },
  total: { type: Number, default: 0 },
})

defineEmits(['update:modelValue'])

const chips = computed(() => [
  {
    value: 'all',
    label: t('driver_maintenance.filter_all'),
    count: props.total,
    dotClass: 'bg-white/40',
    activeClass: 'bg-white/20 text-white border border-white/30',
  },
  {
    value: 'urgent',
    label: t('driver_maintenance.filter_urgent'),
    count: props.counts.urgent,
    dotClass: 'bg-[#ff6b6b]',
    activeClass: 'bg-[#ff6b6b]/20 text-[#ff6b6b] border border-[#ff6b6b]/50',
  },
  {
    value: 'warning',
    label: t('driver_maintenance.filter_upcoming'),
    count: props.counts.upcoming,
    dotClass: 'bg-amber-400',
    activeClass: 'bg-amber-400/20 text-amber-300 border border-amber-400/50',
  },
  {
    value: 'safe',
    label: t('driver_maintenance.filter_safe'),
    count: props.counts.safe,
    dotClass: 'bg-[#7fdcc8]',
    activeClass: 'bg-[#7fdcc8]/15 text-[#7fdcc8] border border-[#7fdcc8]/40',
  },
])
</script>
