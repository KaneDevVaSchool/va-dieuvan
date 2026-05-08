<template>
  <div class="grid grid-cols-4 gap-1.5" role="group" :aria-label="t('driver_maintenance.filter_all')">
    <button
      v-for="chip in chips"
      :key="chip.value"
      type="button"
      :class="[
        'flex flex-col items-center justify-center gap-1 rounded-2xl py-2.5 text-center transition-all',
        modelValue === chip.value ? chip.activeClass : 'bg-white/5 border border-white/10 text-white/50',
      ]"
      @click="$emit('update:modelValue', chip.value)"
    >
      <span :class="['h-2 w-2 rounded-full', chip.dotClass]" aria-hidden="true" />
      <span class="text-[11px] font-semibold leading-tight">{{ chip.label }}</span>
      <span :class="['rounded-full px-1.5 py-0 text-[10px] tabular-nums font-bold', modelValue === chip.value ? chip.countClass : 'bg-white/10 text-white/50']">{{ chip.count }}</span>
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
    label: t('driver_maintenance.filter_all_short'),
    count: props.total,
    dotClass: 'bg-white/40',
    activeClass: 'bg-white/15 text-white border border-white/25',
    countClass: 'bg-white/20 text-white',
  },
  {
    value: 'urgent',
    label: t('driver_maintenance.filter_urgent_short'),
    count: props.counts.urgent,
    dotClass: 'bg-[#ff6b6b]',
    activeClass: 'bg-[#ff6b6b]/15 text-[#ff6b6b] border border-[#ff6b6b]/40',
    countClass: 'bg-[#ff6b6b]/25 text-[#ff6b6b]',
  },
  {
    value: 'warning',
    label: t('driver_maintenance.filter_upcoming_short'),
    count: props.counts.upcoming,
    dotClass: 'bg-amber-400',
    activeClass: 'bg-amber-400/15 text-amber-300 border border-amber-400/40',
    countClass: 'bg-amber-400/25 text-amber-300',
  },
  {
    value: 'safe',
    label: t('driver_maintenance.filter_safe_short'),
    count: props.counts.safe,
    dotClass: 'bg-[#7fdcc8]',
    activeClass: 'bg-[#7fdcc8]/12 text-[#7fdcc8] border border-[#7fdcc8]/35',
    countClass: 'bg-[#7fdcc8]/20 text-[#7fdcc8]',
  },
])
</script>
