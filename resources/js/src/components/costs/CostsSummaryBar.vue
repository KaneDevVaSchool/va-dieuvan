<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  BanknotesIcon,
  CheckCircleIcon,
  ClockIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'
import { formatVnd } from '../../util/labels'

const props = defineProps({
  total: { type: Number, default: 0 },
  confirmed: { type: Object, default: () => ({ count: 0, amount: 0 }) },
  pending: { type: Object, default: () => ({ count: 0, amount: 0 }) },
  rejected: { type: Object, default: () => ({ count: 0, amount: 0 }) },
  loading: { type: Boolean, default: false },
  activeStatus: { type: String, default: '' },
})

const emit = defineEmits(['quick-filter'])

const { t } = useI18n()

function formatInt(n) {
  return new Intl.NumberFormat('vi-VN').format(n ?? 0)
}

function displayCount(n) {
  return props.loading ? '…' : formatInt(n)
}

const activeCardKey = computed(() => {
  if (props.activeStatus === 'confirmed') return 'confirmed'
  if (props.activeStatus === 'submitted') return 'pending'
  if (props.activeStatus === 'rejected') return 'rejected'
  if (!props.activeStatus) return 'total'
  return ''
})

const cards = computed(() => [
  {
    key: 'total',
    label: t('costs_page.kpi_total'),
    tone: 'brand',
    icon: BanknotesIcon,
    display: displayCount(props.total),
    sub: t('costs_page.kpi_total_hint'),
    filter: { status: '' },
  },
  {
    key: 'confirmed',
    label: t('costs_page.kpi_confirmed'),
    tone: 'emerald',
    icon: CheckCircleIcon,
    display: displayCount(props.confirmed.count),
    sub: formatVnd(props.confirmed.amount),
    filter: { status: 'confirmed' },
  },
  {
    key: 'pending',
    label: t('costs_page.kpi_submitted'),
    tone: 'sky',
    icon: ClockIcon,
    display: displayCount(props.pending.count),
    sub: formatVnd(props.pending.amount),
    filter: { status: 'submitted' },
  },
  {
    key: 'rejected',
    label: t('costs_page.kpi_rejected'),
    tone: 'rose',
    icon: XCircleIcon,
    display: displayCount(props.rejected.count),
    sub: formatVnd(props.rejected.amount),
    filter: { status: 'rejected' },
  },
])

function onCardAction(card) {
  if (!card.filter) return
  emit('quick-filter', card.filter)
}
</script>

<template>
  <KpiSummaryStrip
    :cards="cards"
    grid-class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-4"
    :aria-label="t('costs_page.kpi_strip_aria')"
    :eyebrow="t('costs_page.kpi_stats_eyebrow')"
    :title="t('costs_page.kpi_strip_overview')"
    :hint="t('costs_page.kpi_strip_hint')"
    :active-card-key="activeCardKey"
    @card-action="onCardAction"
  />
</template>
