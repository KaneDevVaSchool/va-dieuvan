<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ShieldCheckIcon,
  UserGroupIcon,
  SparklesIcon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  roles: { type: Array, default: () => [] },
  templateCount: { type: Number, default: 0 },
  loading: { type: Boolean, default: false },
  activeUsersFilter: { type: String, default: 'all' },
})

const emit = defineEmits(['quick-filter'])

const { t } = useI18n()

const stats = computed(() => {
  const list = props.roles ?? []
  const total = list.length
  const withUsers = list.filter((r) => (r.users_count ?? 0) > 0).length
  const empty = total - withUsers
  const totalUsers = list.reduce((s, r) => s + (r.users_count ?? 0), 0)
  const totalPerms = list.reduce((s, r) => s + (r.permissions_count ?? 0), 0)
  return { total, withUsers, empty, totalUsers, totalPerms }
})

const cards = computed(() => {
  const s = stats.value
  const loading = props.loading
  const pct = (n) => (s.total > 0 ? Math.round((n / s.total) * 100) : 0)

  return [
    {
      key: 'all',
      label: t('system_pages.roles.kpi_total'),
      tone: 'brand',
      icon: ShieldCheckIcon,
      display: loading ? '…' : String(s.total),
      sub: t('system_pages.roles.kpi_total_sub'),
      filter: { users: 'all' },
    },
    {
      key: 'with_users',
      label: t('system_pages.roles.kpi_with_users'),
      tone: 'emerald',
      icon: UserGroupIcon,
      display: loading ? '…' : String(s.withUsers),
      sub: t('system_pages.kpi_share', { pct: pct(s.withUsers) }),
      progress: pct(s.withUsers),
      filter: { users: 'with_users' },
    },
    {
      key: 'empty',
      label: t('system_pages.roles.kpi_empty'),
      tone: 'amber',
      icon: UserGroupIcon,
      display: loading ? '…' : String(s.empty),
      sub: t('system_pages.kpi_share', { pct: pct(s.empty) }),
      progress: pct(s.empty),
      filter: { users: 'empty' },
    },
    {
      key: 'staff',
      label: t('system_pages.roles.kpi_staff'),
      tone: 'sky',
      icon: UserGroupIcon,
      display: loading ? '…' : s.totalUsers.toLocaleString('vi-VN'),
      sub: t('system_pages.roles.kpi_staff_sub'),
    },
    {
      key: 'templates',
      label: t('system_pages.roles.kpi_templates'),
      tone: 'violet',
      icon: SparklesIcon,
      display: loading ? '…' : String(props.templateCount),
      sub: t('system_pages.roles.kpi_templates_sub'),
    },
  ]
})

const activeKey = computed(() => {
  if (props.activeUsersFilter === 'with_users') return 'with_users'
  if (props.activeUsersFilter === 'empty') return 'empty'
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
    :aria-label="t('system_pages.roles.kpi_strip_aria')"
    :eyebrow="t('system_pages.kpi_eyebrow')"
    :title="t('system_pages.roles.kpi_title')"
    :hint="t('system_pages.kpi_hint_quick_filter')"
    @card-action="onCardAction"
  />
</template>
