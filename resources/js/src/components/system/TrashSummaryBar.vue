<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  TrashIcon,
  ClipboardDocumentListIcon,
  TruckIcon,
  RectangleStackIcon,
  BuildingOfficeIcon,
  AcademicCapIcon,
  CalendarDaysIcon,
  UserGroupIcon,
  Bars3Icon,
} from '@heroicons/vue/24/outline'
import KpiSummaryStrip from '../shared/ui/KpiSummaryStrip.vue'

const props = defineProps({
  summary: {
    type: Object,
    default: () => ({}),
  },
  loading: { type: Boolean, default: false },
  activeType: { type: String, default: null },
})

const emit = defineEmits(['quick-filter'])

const { t } = useI18n()

function displayCount(n) {
  return props.loading ? '…' : new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(n ?? 0)
}

const cards = computed(() => {
  const s = props.summary?.counts ?? {}
  const total = s.total ?? 0

  return [
    {
      key: 'all',
      label: t('trash_page.type_all'),
      tone: 'brand',
      icon: TrashIcon,
      display: displayCount(total),
      sub: t('trash_page.kpi_all_sub'),
      filter: { type: null },
    },
    {
      key: 'dispatch_request',
      label: t('trash_page.type_dispatch_request'),
      tone: 'sky',
      icon: ClipboardDocumentListIcon,
      display: displayCount(s.dispatch_request),
      sub: t('trash_page.kpi_share', { pct: pct(s.dispatch_request, total) }),
      filter: { type: 'dispatch_request' },
    },
    {
      key: 'driver',
      label: t('trash_page.type_driver'),
      tone: 'violet',
      icon: TruckIcon,
      display: displayCount(s.driver),
      sub: t('trash_page.kpi_share', { pct: pct(s.driver, total) }),
      filter: { type: 'driver' },
    },
    {
      key: 'vehicle',
      label: t('trash_page.type_vehicle'),
      tone: 'amber',
      icon: RectangleStackIcon,
      display: displayCount(s.vehicle),
      sub: t('trash_page.kpi_share', { pct: pct(s.vehicle, total) }),
      filter: { type: 'vehicle' },
    },
    {
      key: 'transport_provider',
      label: t('trash_page.type_transport_provider'),
      tone: 'emerald',
      icon: BuildingOfficeIcon,
      display: displayCount(s.transport_provider),
      sub: t('trash_page.kpi_share', { pct: pct(s.transport_provider, total) }),
      filter: { type: 'transport_provider' },
    },
    {
      key: 'tp_student',
      label: t('trash_page.type_tp_student'),
      tone: 'rose',
      icon: AcademicCapIcon,
      display: displayCount(s.tp_student),
      sub: t('trash_page.kpi_share', { pct: pct(s.tp_student, total) }),
      filter: { type: 'tp_student' },
    },
    {
      key: 'tp_program',
      label: t('trash_page.type_tp_program'),
      tone: 'brand',
      icon: CalendarDaysIcon,
      display: displayCount(s.tp_program),
      sub: t('trash_page.kpi_share', { pct: pct(s.tp_program, total) }),
      filter: { type: 'tp_program' },
    },
    {
      key: 'role',
      label: t('trash_page.type_role'),
      tone: 'slate',
      icon: UserGroupIcon,
      display: displayCount(s.role),
      sub: t('trash_page.kpi_share', { pct: pct(s.role, total) }),
      filter: { type: 'role' },
    },
    {
      key: 'menu_item',
      label: t('trash_page.type_menu_item'),
      tone: 'slate',
      icon: Bars3Icon,
      display: displayCount(s.menu_item),
      sub: t('trash_page.kpi_share', { pct: pct(s.menu_item, total) }),
      filter: { type: 'menu_item' },
    },
  ]
})

function pct(n, total) {
  return total > 0 ? Math.round(((n ?? 0) / total) * 100) : 0
}

function onCardAction(card) {
  if (!card.filter) return
  emit('quick-filter', card.filter)
}
</script>

<template>
  <KpiSummaryStrip
    :cards="cards"
    :aria-label="t('trash_page.kpi_strip_aria')"
    :eyebrow="t('trash_page.kpi_eyebrow')"
    :title="t('trash_page.kpi_strip_title')"
    :hint="t('trash_page.kpi_strip_hint')"
    :active-card-key="activeType ?? 'all'"
    grid-class="grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-9"
    compact
    @card-action="onCardAction"
  />
</template>
