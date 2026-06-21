<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  BanknotesIcon,
  BuildingOffice2Icon,
  QueueListIcon,
  TagIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'
import { formatVnd } from '../../util/labels'
import { useCostReportPresentation } from '../../composables/useCostReportPresentation'

const props = defineProps({
  stats: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  topCategory: { type: String, default: null },
  topProvider: { type: String, default: null },
})

const { t } = useI18n()
const { labelProviderDisplay } = useCostReportPresentation()

const cards = computed(() => {
  const stats = props.stats
  return [
    {
      key: 'total_amount',
      label: t('cost_report.kpi_total_amount'),
      tone: 'brand',
      icon: BanknotesIcon,
      display: props.loading ? '…' : stats ? formatVnd(stats.total_amount) : '—',
      sub: t('cost_report.kpi_total_amount_sub'),
    },
    {
      key: 'count',
      label: t('cost_report.kpi_count'),
      tone: 'sky',
      icon: QueueListIcon,
      display: props.loading ? '…' : stats ? stats.count.toLocaleString('vi-VN') : '—',
      sub: t('cost_report.kpi_count_sub'),
    },
    {
      key: 'top_category',
      label: t('cost_report.kpi_top_category'),
      tone: 'amber',
      icon: TagIcon,
      display: props.loading ? '…' : props.topCategory || '—',
      sub: t('cost_report.kpi_top_category_sub'),
    },
    {
      key: 'top_provider',
      label: t('cost_report.kpi_top_provider'),
      tone: 'emerald',
      icon: BuildingOffice2Icon,
      display: props.loading ? '…' : (props.topProvider ? labelProviderDisplay(props.topProvider) : '—'),
      sub: t('cost_report.kpi_top_provider_sub'),
    },
  ]
})
</script>

<template>
  <KpiSummaryStrip
    :cards="cards"
    grid-class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4"
    :aria-label="t('cost_report.kpi_strip_aria')"
    :eyebrow="t('cost_report.kpi_stats_eyebrow')"
    :title="t('cost_report.kpi_strip_overview')"
    :hint="t('cost_report.kpi_strip_hint')"
  />
</template>
