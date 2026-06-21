<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ClipboardDocumentListIcon,
  DocumentTextIcon,
  PaperClipIcon,
  ExclamationTriangleIcon,
  Cog6ToothIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  summary: {
    type: Object,
    default: () => ({
      total: 0,
      request: 0,
      file: 0,
      alert: 0,
      system: 0,
    }),
  },
  loading: { type: Boolean, default: false },
  activeCategory: { type: String, default: 'all' },
})

const emit = defineEmits(['quick-filter'])

const { t } = useI18n()

function formatInt(n) {
  return new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(n ?? 0)
}

function displayCount(n) {
  return props.loading ? '…' : formatInt(n)
}

const total = computed(() => props.summary?.total ?? 0)

const cards = computed(() => {
  const s = props.summary ?? {}
  const pct = (n) => (total.value > 0 ? Math.round(((n ?? 0) / total.value) * 100) : 0)
  return [
    {
      key: 'all',
      label: t('audit_logs_page.kpi_total'),
      tone: 'brand',
      icon: ClipboardDocumentListIcon,
      display: displayCount(s.total),
      sub: t('audit_logs_page.kpi_total_sub'),
      progress: 100,
      filter: { category: 'all' },
    },
    {
      key: 'request',
      label: t('audit_logs_page.kpi_request'),
      tone: 'emerald',
      icon: DocumentTextIcon,
      display: displayCount(s.request),
      sub: t('audit_logs_page.kpi_share', { pct: pct(s.request) }),
      progress: pct(s.request),
      filter: { category: 'request' },
    },
    {
      key: 'file',
      label: t('audit_logs_page.kpi_file'),
      tone: 'violet',
      icon: PaperClipIcon,
      display: displayCount(s.file),
      sub: t('audit_logs_page.kpi_share', { pct: pct(s.file) }),
      progress: pct(s.file),
      filter: { category: 'file' },
    },
    {
      key: 'alert',
      label: t('audit_logs_page.kpi_alert'),
      tone: 'amber',
      icon: ExclamationTriangleIcon,
      display: displayCount(s.alert),
      sub: t('audit_logs_page.kpi_share', { pct: pct(s.alert) }),
      progress: pct(s.alert),
      filter: { category: 'alert' },
    },
    {
      key: 'system',
      label: t('audit_logs_page.kpi_system'),
      tone: 'slate',
      icon: Cog6ToothIcon,
      display: displayCount(s.system),
      sub: t('audit_logs_page.kpi_share', { pct: pct(s.system) }),
      progress: pct(s.system),
      filter: { category: 'system' },
    },
  ]
})

function onCardAction(card) {
  if (!card.filter) return
  emit('quick-filter', card.filter)
}
</script>

<template>
  <KpiSummaryStrip
    :cards="cards"
    :aria-label="t('audit_logs_page.kpi_strip_aria')"
    :eyebrow="t('audit_logs_page.kpi_stats_eyebrow')"
    :title="t('audit_logs_page.kpi_strip_title')"
    :hint="t('audit_logs_page.kpi_strip_hint')"
    :active-card-key="activeCategory"
    @card-action="onCardAction"
  />
</template>
