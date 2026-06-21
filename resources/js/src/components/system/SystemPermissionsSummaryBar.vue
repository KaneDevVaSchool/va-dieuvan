<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  KeyIcon,
  ShieldCheckIcon,
  UserMinusIcon,
  LockClosedIcon,
  Squares2X2Icon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  activeFilter: { type: String, default: 'all' },
})

const emit = defineEmits(['quick-filter'])

const { t } = useI18n()

const stats = computed(() => {
  const list = props.items ?? []
  const total = list.length
  const assigned = list.filter((p) => (p.role_ids?.length ?? 0) > 0).length
  const unassigned = total - assigned
  const system = list.filter((p) => (p.name ?? '').startsWith('system.')).length
  const modules = new Set(list.map((p) => (p.name ?? '').split('.')[0]).filter(Boolean)).size
  return { total, assigned, unassigned, system, modules }
})

const cards = computed(() => {
  const s = stats.value
  const loading = props.loading
  const pct = (n) => (s.total > 0 ? Math.round((n / s.total) * 100) : 0)

  return [
    {
      key: 'all',
      label: t('system_pages.permissions.kpi_total'),
      tone: 'brand',
      icon: KeyIcon,
      display: loading ? '…' : String(s.total),
      sub: t('system_pages.permissions.kpi_total_sub'),
      filter: { kind: 'all' },
    },
    {
      key: 'assigned',
      label: t('system_pages.permissions.kpi_assigned'),
      tone: 'emerald',
      icon: ShieldCheckIcon,
      display: loading ? '…' : String(s.assigned),
      sub: t('system_pages.kpi_share', { pct: pct(s.assigned) }),
      progress: pct(s.assigned),
      filter: { kind: 'assigned' },
    },
    {
      key: 'unassigned',
      label: t('system_pages.permissions.kpi_unassigned'),
      tone: 'amber',
      icon: UserMinusIcon,
      display: loading ? '…' : String(s.unassigned),
      sub: t('system_pages.kpi_share', { pct: pct(s.unassigned) }),
      progress: pct(s.unassigned),
      filter: { kind: 'unassigned' },
    },
    {
      key: 'system',
      label: t('system_pages.permissions.kpi_system'),
      tone: 'slate',
      icon: LockClosedIcon,
      display: loading ? '…' : String(s.system),
      sub: t('system_pages.kpi_share', { pct: pct(s.system) }),
      progress: pct(s.system),
      filter: { kind: 'system' },
    },
    {
      key: 'modules',
      label: t('system_pages.permissions.kpi_modules'),
      tone: 'violet',
      icon: Squares2X2Icon,
      display: loading ? '…' : String(s.modules),
      sub: t('system_pages.permissions.kpi_modules_sub'),
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
    :active-card-key="activeFilter"
    :aria-label="t('system_pages.permissions.kpi_strip_aria')"
    :eyebrow="t('system_pages.kpi_eyebrow')"
    :title="t('system_pages.permissions.kpi_title')"
    :hint="t('system_pages.kpi_hint_quick_filter')"
    @card-action="onCardAction"
  />
</template>
