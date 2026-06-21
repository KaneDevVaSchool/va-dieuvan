<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ClipboardDocumentCheckIcon,
  ShieldCheckIcon,
  TruckIcon,
  WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  boxes: { type: Array, default: () => [] },
})

const { t } = useI18n()

const intFmt = new Intl.NumberFormat('vi-VN')

function formatInt(n) {
  return intFmt.format(Number(n ?? 0))
}

const toneByKey = {
  insp: 'emerald',
  insu: 'sky',
  road: 'amber',
  maint: 'violet',
}

const iconByKey = {
  insp: ClipboardDocumentCheckIcon,
  insu: ShieldCheckIcon,
  road: TruckIcon,
  maint: WrenchScrewdriverIcon,
}

const cards = computed(() =>
  (props.boxes ?? []).map((box) => {
    const overdue = Number(box.overdue ?? 0)
    const soon = box.soon != null ? Number(box.soon) : null
    const stale = box.stale != null ? Number(box.stale) : null
    const parts = []
    if (overdue > 0) {
      parts.push(`${t('dashboard_analytics.compliance_overdue')}: ${formatInt(overdue)}`)
    }
    if (soon != null && soon > 0) {
      parts.push(`${t('dashboard_analytics.compliance_due_30d')}: ${formatInt(soon)}`)
    }
    if (stale != null && stale > 0 && box.staleLabel) {
      parts.push(`${box.staleLabel}: ${formatInt(stale)}`)
    }
    const sub =
      parts.length > 0 ? parts.join(' · ') : t('dashboard_analytics.compliance_kpi_ok')
    return {
      key: box.key,
      label: box.title,
      tone: toneByKey[box.key] ?? 'slate',
      icon: iconByKey[box.key] ?? ClipboardDocumentCheckIcon,
      display: formatInt(overdue + (soon ?? 0) + (stale ?? 0)),
      sub,
    }
  }),
)
</script>

<template>
  <KpiSummaryStrip
    :cards="cards"
    grid-class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4"
    :aria-label="t('dashboard_analytics.compliance_strip_aria')"
    :eyebrow="t('dashboard_analytics.kpi_strip_eyebrow')"
    :title="t('dashboard_analytics.section_compliance')"
    :hint="t('dashboard_analytics.compliance_strip_hint')"
  />
</template>
