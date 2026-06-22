<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { BellAlertIcon, CheckCircleIcon, InboxStackIcon } from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  loading: { type: Boolean, default: false },
  total: { type: Number, default: 0 },
  unread: { type: Number, default: 0 },
  activeTab: { type: String, default: 'all' },
})

const emit = defineEmits(['quick-filter'])

const { t } = useI18n()

function fmt(n) {
  if (props.loading) return '…'
  const v = typeof n === 'number' && Number.isFinite(n) ? n : 0
  return new Intl.NumberFormat('vi-VN').format(v)
}

const readCount = computed(() => Math.max(0, props.total - props.unread))

const cards = computed(() => [
  {
    key: 'all',
    label: t('portal.notifications_kpi.total'),
    tone: 'brand',
    icon: InboxStackIcon,
    display: fmt(props.total),
    sub: t('portal.notifications_kpi.total_sub'),
    filter: { tab: 'all' },
  },
  {
    key: 'unread',
    label: t('portal.notifications_kpi.unread'),
    tone: 'violet',
    icon: BellAlertIcon,
    display: fmt(props.unread),
    sub: t('portal.notifications_kpi.unread_sub'),
    filter: { tab: 'unread' },
  },
  {
    key: 'read',
    label: t('portal.notifications_kpi.read'),
    tone: 'emerald',
    icon: CheckCircleIcon,
    display: fmt(readCount.value),
    sub: t('portal.notifications_kpi.read_sub'),
    filter: null,
  },
])

const activeKey = computed(() => {
  if (props.activeTab === 'unread') return 'unread'
  return 'all'
})

function onCardAction(card) {
  if (!card.filter?.tab) return
  emit('quick-filter', card.filter.tab)
}
</script>

<template>
  <KpiSummaryStrip
    :cards="cards"
    grid-class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-3"
    :aria-label="t('portal.notifications_kpi.aria')"
    :eyebrow="t('portal.kpi_strip.eyebrow')"
    :title="t('portal.notifications_kpi.title')"
    :hint="t('portal.notifications_kpi.hint')"
    :active-card-key="activeKey"
    @card-action="onCardAction"
  />
</template>
