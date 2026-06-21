<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  AdjustmentsHorizontalIcon,
  CheckCircleIcon,
  XCircleIcon,
  WrenchScrewdriverIcon,
  SparklesIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  activeStatus: { type: String, default: 'all' },
})

const emit = defineEmits(['quick-filter'])

const { t } = useI18n()

const stats = computed(() => {
  const list = props.items ?? []
  const total = list.length
  const on = list.filter((r) => r.is_enabled).length
  const maintenance = list.filter((r) => r.maintenance_mode).length
  const upgrade = list.filter((r) => r.upgrade_notice).length
  const off = total - on
  return { total, on, off, maintenance, upgrade }
})

const cards = computed(() => {
  const s = stats.value
  const loading = props.loading
  const pct = (n) => (s.total > 0 ? Math.round((n / s.total) * 100) : 0)

  return [
    {
      key: 'all',
      label: t('system_pages.feature_toggles.kpi_total'),
      tone: 'brand',
      icon: AdjustmentsHorizontalIcon,
      display: loading ? '…' : String(s.total),
      sub: t('system_pages.feature_toggles.kpi_total_sub'),
      filter: { status: 'all' },
    },
    {
      key: 'on',
      label: t('system_pages.feature_toggles.kpi_on'),
      tone: 'emerald',
      icon: CheckCircleIcon,
      display: loading ? '…' : String(s.on),
      sub: t('system_pages.kpi_share', { pct: pct(s.on) }),
      progress: pct(s.on),
      filter: { status: 'on' },
    },
    {
      key: 'off',
      label: t('system_pages.feature_toggles.kpi_off'),
      tone: 'slate',
      icon: XCircleIcon,
      display: loading ? '…' : String(s.off),
      sub: t('system_pages.kpi_share', { pct: pct(s.off) }),
      progress: pct(s.off),
      filter: { status: 'off' },
    },
    {
      key: 'maintenance',
      label: t('system_pages.feature_toggles.kpi_maintenance'),
      tone: 'amber',
      icon: WrenchScrewdriverIcon,
      display: loading ? '…' : String(s.maintenance),
      sub: t('system_pages.kpi_share', { pct: pct(s.maintenance) }),
      progress: pct(s.maintenance),
      filter: { status: 'maintenance' },
    },
    {
      key: 'upgrade',
      label: t('system_pages.feature_toggles.kpi_upgrade'),
      tone: 'violet',
      icon: SparklesIcon,
      display: loading ? '…' : String(s.upgrade),
      sub: t('system_pages.kpi_share', { pct: pct(s.upgrade) }),
      progress: pct(s.upgrade),
      filter: { status: 'upgrade' },
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
    :active-card-key="activeStatus"
    :aria-label="t('system_pages.feature_toggles.kpi_strip_aria')"
    :eyebrow="t('system_pages.kpi_eyebrow')"
    :title="t('system_pages.feature_toggles.kpi_title')"
    :hint="t('system_pages.kpi_hint_quick_filter')"
    @card-action="onCardAction"
  />
</template>
