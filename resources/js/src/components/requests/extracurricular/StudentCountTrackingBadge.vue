<template>
  <span
    class="inline-flex max-w-full items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ring-inset"
    :class="toneClass"
  >
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  trackingKey: { type: String, required: true },
  i18nPrefix: { type: String, required: true },
})

const { t } = useI18n()

const label = computed(() => t(`${props.i18nPrefix}.tracking_${props.trackingKey}`))

const toneClass = computed(() => {
  switch (props.trackingKey) {
    case 'coordinated':
      return 'bg-emerald-50 text-emerald-900 ring-emerald-200'
    case 'submitted':
      return 'bg-sky-50 text-sky-900 ring-sky-200'
    case 'updated':
      return 'bg-amber-50 text-amber-950 ring-amber-200'
    default:
      return 'bg-slate-100 text-slate-600 ring-slate-200'
  }
})
</script>
