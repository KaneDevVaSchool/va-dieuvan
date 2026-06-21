<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  CheckCircleIcon,
  ClockIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'dashboard',
    validator: (v) => ['dashboard', 'list', 'extracurricular'].includes(v),
  },
  loading: { type: Boolean, default: false },
  summary: { type: Object, default: null },
  activeKey: { type: String, default: '' },
})

const emit = defineEmits(['quick-filter'])

const { t } = useI18n()

function fmt(n) {
  if (props.loading) return '…'
  const v = typeof n === 'number' && Number.isFinite(n) ? n : 0
  return new Intl.NumberFormat('vi-VN').format(v)
}

const meta = computed(() => {
  const s = props.summary || {}
  return {
    total: s.total ?? 0,
    processing: s.processing ?? 0,
    pending: s.pending ?? 0,
    completed: s.completed_this_month ?? 0,
    sla_risk: s.sla_risk ?? 0,
    plans: s.plans ?? 0,
    in_progress: s.in_progress ?? s.processing ?? 0,
    overdue: s.overdue ?? s.sla_risk ?? 0,
  }
})

const cards = computed(() => {
  if (props.variant === 'extracurricular') {
    return [
      {
        key: 'plans',
        label: t('portal.kpi_strip.plans'),
        tone: 'brand',
        icon: DocumentTextIcon,
        display: fmt(meta.value.plans),
        sub: t('portal.kpi_strip.plans_sub'),
        filter: { key: 'plans' },
      },
      {
        key: 'in_progress',
        label: t('portal.kpi_strip.in_progress'),
        tone: 'sky',
        icon: TruckIcon,
        display: fmt(meta.value.in_progress),
        sub: t('portal.kpi_strip.in_progress_sub'),
        filter: { key: 'in_progress' },
      },
      {
        key: 'completed',
        label: t('portal.kpi_strip.completed'),
        tone: 'emerald',
        icon: CheckCircleIcon,
        display: fmt(meta.value.completed),
        sub: t('portal.kpi_strip.completed_sub'),
        filter: { key: 'completed' },
      },
      {
        key: 'overdue',
        label: t('portal.kpi_strip.overdue'),
        tone: 'rose',
        icon: ExclamationTriangleIcon,
        display: fmt(meta.value.overdue),
        sub: t('portal.kpi_strip.overdue_sub'),
        filter: { key: 'overdue' },
      },
    ]
  }

  if (props.variant === 'list') {
    return [
      {
        key: 'total',
        label: t('portal.kpi_strip.total'),
        tone: 'brand',
        icon: DocumentTextIcon,
        display: fmt(meta.value.total),
        sub: t('portal.kpi_strip.total_sub'),
        filter: { key: 'total' },
      },
      {
        key: 'pending',
        label: t('portal.kpi_strip.pending'),
        tone: 'amber',
        icon: ClockIcon,
        display: fmt(meta.value.pending),
        sub: t('portal.kpi_strip.pending_sub'),
        filter: { key: 'pending' },
      },
      {
        key: 'processing',
        label: t('portal.kpi_strip.dispatching'),
        tone: 'sky',
        icon: TruckIcon,
        display: fmt(meta.value.processing),
        sub: t('portal.kpi_strip.dispatching_sub'),
        filter: { key: 'processing' },
      },
      {
        key: 'completed',
        label: t('portal.kpi_strip.completed'),
        tone: 'emerald',
        icon: CheckCircleIcon,
        display: fmt(meta.value.completed),
        sub: t('portal.kpi_strip.completed_sub'),
        filter: { key: 'completed' },
      },
    ]
  }

  return [
    {
      key: 'total',
      label: t('portal.kpi_strip.total'),
      tone: 'brand',
      icon: DocumentTextIcon,
      display: fmt(meta.value.total),
      sub: t('portal.kpi_strip.total_sub'),
      filter: { key: 'total' },
    },
    {
      key: 'processing',
      label: t('portal.kpi_strip.processing'),
      tone: 'sky',
      icon: TruckIcon,
      display: fmt(meta.value.processing),
      sub: t('portal.kpi_processing_sub'),
      filter: { key: 'processing' },
    },
    {
      key: 'completed',
      label: t('portal.kpi_strip.completed'),
      tone: 'emerald',
      icon: CheckCircleIcon,
      display: fmt(meta.value.completed),
      sub: t('portal.kpi_completed_sub'),
      filter: { key: 'completed' },
    },
    {
      key: 'sla_risk',
      label: t('portal.kpi_strip.sla_risk'),
      tone: 'rose',
      icon: ExclamationTriangleIcon,
      display: fmt(meta.value.sla_risk),
      sub: t('portal.kpi_strip.sla_risk_sub'),
      filter: { key: 'sla_risk' },
    },
  ]
})

const stripTitle = computed(() => {
  if (props.variant === 'extracurricular') return t('portal.kpi_strip.title_extracurricular')
  if (props.variant === 'list') return t('portal.kpi_strip.title_list')
  return t('portal.kpi_strip.title_dashboard')
})

function onCardAction(card) {
  if (!card.filter) return
  emit('quick-filter', card.filter)
}
</script>

<template>
  <div class="portal-kpi-scroll -mx-1 px-1 sm:mx-0 sm:px-0">
    <KpiSummaryStrip
      :cards="cards"
      grid-class="portal-kpi-grid grid grid-cols-2 gap-3 lg:grid-cols-4"
      :aria-label="stripTitle"
      :eyebrow="t('portal.kpi_strip.eyebrow')"
      :title="stripTitle"
      :hint="t('portal.kpi_strip.hint')"
      :active-card-key="activeKey"
      @card-action="onCardAction"
    />
  </div>
</template>

<style scoped>
@media (max-width: 767px) {
  .portal-kpi-scroll {
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
  }
  :deep(.portal-kpi-grid) {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.75rem;
    padding-bottom: 0.25rem;
  }
  :deep(.portal-kpi-grid .kpi-card) {
    min-width: 9rem;
    scroll-snap-align: start;
    flex: 0 0 auto;
  }
}
</style>
