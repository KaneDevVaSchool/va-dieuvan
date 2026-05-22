<template>
  <div
    v-if="hasAny"
    class="flex flex-wrap gap-2 rounded-lg border border-amber-200/80 bg-amber-50/40 px-3 py-2"
    role="region"
    :aria-label="t('requests_page.ops_alerts_aria')"
  >
    <RouterLink
      v-if="ops.pending_fill_price > 0"
      to="/requests?status=pending"
      class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-amber-950 ring-1 ring-amber-200/90 hover:bg-amber-50"
    >
      {{ t('requests_page.ops_fill_price', { n: ops.pending_fill_price }) }}
    </RouterLink>
    <RouterLink
      v-if="ops.approved_missing_signed > 0"
      to="/requests?status=approved"
      class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-teal-900 ring-1 ring-teal-200/90 hover:bg-teal-50"
    >
      {{ t('requests_page.ops_missing_signed', { n: ops.approved_missing_signed }) }}
    </RouterLink>
    <RouterLink
      v-if="ops.approved_missing_paper_scan > 0"
      to="/requests?status=approved"
      class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-violet-900 ring-1 ring-violet-200/90 hover:bg-violet-50"
    >
      {{ t('requests_page.ops_missing_scan', { n: ops.approved_missing_paper_scan }) }}
    </RouterLink>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  ops: { type: Object, default: () => ({}) },
})

const { t } = useI18n()

const hasAny = computed(() => {
  const o = props.ops || {}
  return (o.pending_fill_price ?? 0) > 0 || (o.approved_missing_signed ?? 0) > 0 || (o.approved_missing_paper_scan ?? 0) > 0
})
</script>
