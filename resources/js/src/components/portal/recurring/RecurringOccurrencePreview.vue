<template>
  <div
    class="rounded-xl border border-slate-200 bg-slate-50/60 p-4"
    role="status"
    :aria-live="count > 0 ? 'polite' : 'off'"
  >
    <p class="text-sm font-semibold text-slate-900">
      {{
        count > 0
          ? t('portal.recurring_plan.preview_count', { n: count })
          : t('portal.recurring_plan.preview_empty')
      }}
    </p>
    <p v-if="count > 0 && compactLabel" class="mt-1 text-xs text-slate-600">{{ compactLabel }}</p>
    <ul v-if="count > 0 && showList" class="mt-2 max-h-32 space-y-1 overflow-y-auto text-xs text-slate-700">
      <li v-for="d in visibleDates" :key="d" class="font-medium">{{ formatDate(d) }}</li>
      <li v-if="hiddenCount > 0" class="text-slate-500">
        {{ t('portal.recurring_plan.preview_more', { n: hiddenCount }) }}
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  dates: { type: Array, default: () => [] },
  formatDate: { type: Function, required: true },
  showList: { type: Boolean, default: true },
  maxVisible: { type: Number, default: 8 },
})

const { t } = useI18n()

const count = computed(() => props.dates?.length ?? 0)

const visibleDates = computed(() => (props.dates || []).slice(0, props.maxVisible))

const hiddenCount = computed(() => Math.max(0, count.value - props.maxVisible))

const compactLabel = computed(() => {
  if (!count.value) return ''
  const first = props.dates[0]
  const last = props.dates[count.value - 1]
  if (!first || !last) return ''
  if (first === last) return props.formatDate(first)
  return `${props.formatDate(first)} → ${props.formatDate(last)}`
})
</script>
