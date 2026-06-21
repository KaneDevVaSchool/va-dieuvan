<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute } from 'vue-router'
import { buildStaffPrefixedPath as staffPath } from '../../config/dispatchWebBase'
import {
  BriefcaseIcon,
  MapPinIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'
import { formatVnd } from '../../util/labels'
import { useCostReportPresentation } from '../../composables/useCostReportPresentation'
const props = defineProps({
  row: { type: Object, required: true },
  rowNo: { type: Number, required: true },
  colVisible: { type: Object, required: true },
  statusLabelFn: { type: Function, required: true },
})

const { t } = useI18n()
const route = useRoute()
const tripDetailPrefix = computed(() =>
  route.path.startsWith('/driver') ? '/driver/trips' : staffPath('/trips'),
)
const { labelProviderDisplay, labelUnitDisplay, tripCodeForRow, categoryLabel } = useCostReportPresentation()

const tripCode = computed(() => tripCodeForRow(props.row))
const isEstimate = computed(() => props.row.source === 'estimate')

const tripTypeIconWrap = computed(() => {
  const cat = props.row.category
  if (cat === 'door_to_door') return 'bg-sky-100 dark:bg-sky-950/40'
  if (cat === 'point_to_point') return 'bg-emerald-100 dark:bg-emerald-950/40'
  if (cat === 'business') return 'bg-violet-100 dark:bg-violet-950/40'
  if (cat === 'cargo') return 'bg-amber-100 dark:bg-amber-950/40'
  return 'bg-slate-100 dark:bg-slate-800'
})

const categoryBadgeClass = computed(() => {
  const cat = props.row.category
  if (cat === 'door_to_door') return 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-200'
  if (cat === 'point_to_point') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200'
  if (cat === 'business') return 'bg-violet-100 text-violet-800 dark:bg-violet-950/60 dark:text-violet-200'
  if (cat === 'cargo') return 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-200'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
})

const statusPillClass = computed(() => {
  const s = props.row.status
  if (s === 'confirmed') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200'
  if (s === 'rejected') return 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-200'
  if (s === 'submitted') return 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-200'
  if (s === 'estimate') return 'bg-violet-100 text-violet-900 dark:bg-violet-950/60 dark:text-violet-200'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
})

const statusText = computed(() => {
  if (props.row.status === 'estimate') return t('cost_report.badge_estimate')
  return props.row.status_label || props.statusLabelFn(props.row.status) || '—'
})

const showMoneyGrid = computed(
  () =>
    props.colVisible.unit_price ||
    props.colVisible.extra_fee ||
    props.colVisible.payment,
)
</script>

<template>
  <article
    class="overflow-hidden rounded-2xl border bg-white shadow-sm dark:bg-slate-900/50"
    :class="
      isEstimate
        ? 'border-violet-200/90 dark:border-violet-800/60'
        : 'border-slate-200/90 dark:border-slate-700'
    "
    :data-testid="`cost-report-card-${row.id}`"
  >
    <div class="border-b border-slate-100 px-3 py-4 sm:px-5 dark:border-slate-800">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 gap-3 sm:gap-4">
          <div
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl sm:h-12 sm:w-12"
            :class="tripTypeIconWrap"
          >
            <TruckIcon v-if="row.category === 'door_to_door'" class="h-6 w-6 text-sky-600 dark:text-sky-400" aria-hidden="true" />
            <MapPinIcon v-else-if="row.category === 'point_to_point'" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" aria-hidden="true" />
            <BriefcaseIcon v-else-if="row.category === 'business'" class="h-6 w-6 text-violet-600 dark:text-violet-400" aria-hidden="true" />
            <MapPinIcon v-else class="h-6 w-6 text-slate-600 dark:text-slate-400" aria-hidden="true" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-[11px] font-semibold tabular-nums text-slate-400">#{{ rowNo }}</span>
              <RouterLink
                v-if="tripCode && row.trip_id"
                :to="`${tripDetailPrefix}/${row.trip_id}`"
                class="font-mono text-lg font-bold tracking-tight text-slate-900 underline decoration-slate-300 underline-offset-2 hover:text-va-800 hover:decoration-va-400 dark:text-slate-100"
                :data-testid="`cost-report-trip-link-${row.trip_id}`"
              >
                {{ tripCode }}
              </RouterLink>
              <span
                v-if="colVisible.category && row.category"
                class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                :class="categoryBadgeClass"
              >
                {{ categoryLabel(row.category) }}
              </span>
              <span
                v-if="isEstimate"
                class="rounded-md border border-violet-200/80 bg-violet-50/90 px-2 py-0.5 text-[11px] font-semibold text-violet-800 dark:border-violet-800/50 dark:bg-violet-950/40 dark:text-violet-200"
              >
                {{ t('cost_report.badge_estimate') }}
              </span>
            </div>
            <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
              <div v-if="colVisible.unit" class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('cost_report.col_unit') }}
                </dt>
                <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200">
                  {{ labelUnitDisplay(row.unit) }}
                </dd>
              </div>
              <div v-if="colVisible.submitter" class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('cost_report.col_submitter') }}
                </dt>
                <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200">
                  {{ row.submitter || '—' }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
        <div v-if="colVisible.status" class="flex shrink-0 flex-wrap items-center gap-2 lg:justify-end">
          <span class="rounded-full px-2.5 py-1 text-xs font-semibold sm:text-sm" :class="statusPillClass">
            {{ statusText }}
          </span>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 px-3 py-4 sm:px-4 md:grid-cols-2 md:gap-5 xl:grid-cols-3">
      <div v-if="colVisible.description" class="min-w-0 md:col-span-2 xl:col-span-1">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          {{ t('cost_report.col_description') }}
        </p>
        <p class="mt-1 text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100 sm:text-base">
          {{ row.description || '—' }}
        </p>
      </div>
      <div
        v-if="colVisible.fleet_source"
        class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-800/40"
      >
        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          {{ t('cost_report.col_fleet_source') }}
        </p>
        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ row.fleet_source || '—' }}
        </p>
      </div>
      <div
        v-if="colVisible.provider"
        class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-800/40"
      >
        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          {{ t('cost_report.col_provider') }}
        </p>
        <p class="mt-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ labelProviderDisplay(row.provider) }}
        </p>
      </div>
    </div>

    <div
      v-if="showMoneyGrid"
      class="flex flex-col gap-3 border-t border-slate-100 px-3 py-3 dark:border-slate-800 sm:px-4 sm:py-3.5 md:flex-row md:items-center md:justify-between"
    >
      <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-3">
        <div v-if="colVisible.unit_price">
          <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ t('cost_report.col_unit_price') }}
          </dt>
          <dd class="mt-0.5 tabular-nums font-semibold text-slate-800 dark:text-slate-200">
            {{ row.unit_price != null && row.unit_price > 0 ? formatVnd(row.unit_price) : '—' }}
          </dd>
        </div>
        <div v-if="colVisible.extra_fee">
          <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ t('cost_report.col_extra_fee') }}
          </dt>
          <dd class="mt-0.5 tabular-nums font-semibold text-slate-800 dark:text-slate-200">
            {{ row.extra_fee != null && row.extra_fee > 0 ? formatVnd(row.extra_fee) : '—' }}
          </dd>
        </div>
        <div v-if="colVisible.payment">
          <dt class="text-[11px] font-semibold uppercase tracking-wide text-teal-700 dark:text-teal-400">
            {{ t('cost_report.col_payment') }}
          </dt>
          <dd class="mt-0.5 tabular-nums text-base font-bold text-teal-900 dark:text-teal-200">
            {{ formatVnd(row.amount) }}
          </dd>
        </div>
      </dl>
    </div>
  </article>
</template>
