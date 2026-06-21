<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  UserGroupIcon,
  CheckBadgeIcon,
  UserMinusIcon,
  ShieldCheckIcon,
  QueueListIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  meta: { type: Object, required: true },
  rolesCount: { type: Number, default: 0 },
  pageAssigned: { type: Number, default: 0 },
  pageUnassigned: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
  activeAssignment: { type: String, default: 'all' },
})

const emit = defineEmits(['quick-filter'])

const { t } = useI18n()

const total = computed(() => props.meta?.total ?? 0)

const cards = computed(() => {
  const loading = props.loading
  const pageTotal = props.pageAssigned + props.pageUnassigned
  const pctAssigned = pageTotal > 0 ? Math.round((props.pageAssigned / pageTotal) * 100) : 0

  return [
    {
      key: 'all',
      label: t('system_pages.user_roles.kpi_total'),
      tone: 'brand',
      icon: UserGroupIcon,
      display: loading ? '…' : total.value.toLocaleString('vi-VN'),
      sub: t('system_pages.user_roles.kpi_total_sub'),
      filter: { assignment: 'all' },
    },
    {
      key: 'assigned',
      label: t('system_pages.user_roles.kpi_assigned'),
      tone: 'emerald',
      icon: CheckBadgeIcon,
      display: loading ? '…' : String(props.pageAssigned),
      sub: t('system_pages.user_roles.kpi_page_share', { pct: pctAssigned }),
      progress: pctAssigned,
      filter: { assignment: 'assigned' },
    },
    {
      key: 'unassigned',
      label: t('system_pages.user_roles.kpi_unassigned'),
      tone: 'amber',
      icon: UserMinusIcon,
      display: loading ? '…' : String(props.pageUnassigned),
      sub: t('system_pages.user_roles.kpi_unassigned_sub'),
      progress: pageTotal > 0 ? 100 - pctAssigned : 0,
      filter: { assignment: 'unassigned' },
    },
    {
      key: 'roles',
      label: t('system_pages.user_roles.kpi_roles'),
      tone: 'violet',
      icon: ShieldCheckIcon,
      display: loading ? '…' : String(props.rolesCount),
      sub: t('system_pages.user_roles.kpi_roles_sub'),
    },
    {
      key: 'page',
      label: t('system_pages.user_roles.kpi_on_page'),
      tone: 'sky',
      icon: QueueListIcon,
      display: loading ? '…' : String(pageTotal),
      sub: t('system_pages.user_roles.kpi_on_page_sub'),
    },
  ]
})

const activeKey = computed(() => {
  if (props.activeAssignment === 'assigned') return 'assigned'
  if (props.activeAssignment === 'unassigned') return 'unassigned'
  return 'all'
})

function onCardAction(card) {
  if (!card.filter) return
  emit('quick-filter', card.filter)
}
</script>

<template>
  <KpiSummaryStrip
    :cards="cards"
    :active-card-key="activeKey"
    :aria-label="t('system_pages.user_roles.kpi_strip_aria')"
    :eyebrow="t('system_pages.kpi_eyebrow')"
    :title="t('system_pages.user_roles.kpi_title')"
    :hint="t('system_pages.kpi_hint_quick_filter')"
    @card-action="onCardAction"
  />
</template>
