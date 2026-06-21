<template>
  <div class="space-y-3 pb-2" data-testid="staff-request-trip-detail-tab">
    <StaffRequestTripCollapseSection
      section-key="plan"
      :title="t('request_detail.trip_tab_section_plan')"
      tone="brand"
    >
      <dl class="grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-4">
        <div v-for="stat in planStats" :key="stat.key" class="min-w-0">
          <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
            {{ stat.label }}
          </dt>
          <dd class="mt-0.5 truncate text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">
            {{ stat.value }}
          </dd>
        </div>
      </dl>
    </StaffRequestTripCollapseSection>

    <StaffRequestTripCollapseSection
      section-key="assignment"
      :title="t('request_detail.trip_tab_section_assignment')"
      :badge="hasAssignment && trip?.status ? assignmentStatus : ''"
      tone="sky"
    >
      <template #header-actions>
        <RouterLink
          v-if="trip?.id"
          :to="`/trips/${trip.id}`"
          class="shrink-0 text-xs font-semibold text-va-700 hover:underline dark:text-va-400"
          data-testid="staff-request-trip-tab-open-trip"
          @click.stop
        >
          {{ t('request_detail.ops_open_trip_detail') }}
        </RouterLink>
      </template>

      <p
        v-if="!hasAssignment"
        class="py-3 text-center text-sm text-slate-500 dark:text-slate-400"
        data-testid="staff-request-trip-tab-assignment-empty"
      >
        <ExclamationTriangleIcon class="mx-auto mb-1.5 h-5 w-5 text-amber-500" aria-hidden="true" />
        {{ t('request_detail.trip_tab_assignment_empty_title') }}
      </p>

      <dl
        v-else
        class="grid grid-cols-2 gap-x-3 gap-y-2 sm:grid-cols-3 lg:grid-cols-5"
        data-testid="staff-request-trip-tab-assignment-grid"
      >
        <div v-for="cell in assignmentCells" :key="cell.key" class="min-w-0">
          <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ cell.label }}</dt>
          <dd
            class="truncate text-sm font-semibold leading-snug"
            :class="cell.key === 'status' ? statusValueClass : 'text-slate-900 dark:text-slate-100'"
          >
            {{ cell.value }}
          </dd>
        </div>
      </dl>
    </StaffRequestTripCollapseSection>

    <StaffRequestTripCollapseSection
      section-key="passengers"
      :title="passengerSectionTitle"
      :badge="passengerRows.length ? String(passengerRows.length) : ''"
      tone="violet"
      collapsible
      :default-open="passengerRows.length > 0 && passengerRows.length <= 8"
      :expand-label="t('request_detail.trip_tab_expand_section')"
      :collapse-label="t('request_detail.trip_tab_collapse_section')"
    >
      <p
        v-if="!passengerRows.length"
        class="text-sm italic text-slate-400"
        data-testid="staff-request-trip-tab-passengers-empty"
      >
        {{ t('request_detail.trip_tab_passengers_empty') }}
      </p>

      <table v-else class="w-full min-w-0 text-left text-sm" data-testid="staff-request-trip-tab-passengers-table">
        <thead>
          <tr class="border-b border-slate-100 text-[10px] font-semibold uppercase tracking-wide text-slate-400 dark:border-slate-800">
            <th scope="col" class="py-1.5 pr-2 font-semibold">{{ t('request_detail.trip_tab_col_name') }}</th>
            <th scope="col" class="hidden py-1.5 pr-2 font-semibold md:table-cell">{{ t('request_detail.trip_tab_col_department') }}</th>
            <th scope="col" class="py-1.5 pr-2 font-semibold">{{ t('request_detail.trip_tab_col_role') }}</th>
            <th scope="col" class="py-1.5 font-semibold">{{ t('request_detail.trip_tab_col_phone') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
          <tr v-for="row in passengerRows" :key="row.key">
            <td class="max-w-[9rem] truncate py-1.5 pr-2 font-medium text-slate-900 dark:text-slate-100">{{ row.name }}</td>
            <td class="hidden max-w-[8rem] truncate py-1.5 pr-2 text-slate-600 dark:text-slate-400 md:table-cell">{{ row.department }}</td>
            <td class="max-w-[6rem] truncate py-1.5 pr-2 text-slate-600 dark:text-slate-400">{{ row.role }}</td>
            <td class="py-1.5 tabular-nums text-slate-700 dark:text-slate-300">{{ row.phone }}</td>
          </tr>
        </tbody>
      </table>
    </StaffRequestTripCollapseSection>

    <StaffRequestTripCollapseSection
      section-key="execution"
      :title="t('request_detail.trip_tab_section_execution')"
      tone="emerald"
    >
      <StaffRequestTripExecutionTimeline
        :steps="executionSteps"
        :planned-label="t('request_detail.trip_tab_planned')"
        :actual-label="t('request_detail.trip_tab_actual')"
        :planned-short="t('request_detail.trip_tab_planned_short')"
        :actual-short="t('request_detail.trip_tab_actual_short')"
      />
    </StaffRequestTripCollapseSection>

    <StaffRequestTripCollapseSection
      section-key="business"
      :title="t('request_detail.trip_tab_section_business')"
      tone="slate"
    >
      <div class="grid gap-3 sm:grid-cols-2">
        <div class="min-w-0">
          <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ t('request_detail.ops_lbl_purpose') }}</p>
          <p v-if="purposeText" class="mt-1 text-sm leading-snug text-slate-800 dark:text-slate-200">{{ purposeText }}</p>
          <p v-else class="mt-1 text-sm italic text-slate-400">{{ rdEmptyLabel(t, 'purpose') }}</p>
          <div v-if="purposeTargets.length" class="mt-1.5 flex flex-wrap gap-1.5">
            <span
              v-for="(tg, i) in purposeTargets"
              :key="i"
              class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-300"
            >{{ tg }}</span>
          </div>
        </div>
        <div class="min-w-0">
          <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ t('request_detail.ops_lbl_basis') }}</p>
          <p
            class="mt-1 whitespace-pre-wrap text-sm leading-snug text-slate-800 dark:text-slate-200"
            data-testid="staff-request-trip-tab-basis-block"
          >
            <template v-if="basisText">{{ basisText }}</template>
            <template v-else><span class="italic text-slate-400">{{ rdEmptyLabel(t, 'basis') }}</span></template>
          </p>
        </div>
      </div>
    </StaffRequestTripCollapseSection>

    <StaffRequestTripCollapseSection
      section-key="finance"
      :title="t('request_detail.trip_tab_section_finance')"
      tone="amber"
    >
      <dl class="grid grid-cols-2 gap-x-4 gap-y-2 sm:grid-cols-4">
        <div v-for="line in financeLines" :key="line.key" class="min-w-0">
          <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ line.label }}</dt>
          <dd
            class="mt-0.5 truncate tabular-nums font-semibold text-slate-900 dark:text-slate-100"
            :class="line.key === 'total' ? 'text-base' : 'text-sm'"
          >
            {{ line.value }}
          </dd>
        </div>
      </dl>
    </StaffRequestTripCollapseSection>

    <StaffRequestTripCollapseSection
      section-key="notes"
      :title="t('request_detail.trip_tab_section_notes')"
      tone="slate"
    >
      <dl class="grid gap-3 sm:grid-cols-3">
        <div v-for="note in noteBlocks" :key="note.key" class="min-w-0">
          <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ note.label }}</dt>
          <dd
            class="mt-1 whitespace-pre-wrap text-sm leading-snug"
            :class="note.text ? 'text-slate-800 dark:text-slate-200' : 'italic text-slate-400'"
          >
            {{ note.text || rdEmptyLabel(t, 'note') }}
          </dd>
        </div>
      </dl>
    </StaffRequestTripCollapseSection>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import StaffRequestTripCollapseSection from './StaffRequestTripCollapseSection.vue'
import StaffRequestTripExecutionTimeline from './StaffRequestTripExecutionTimeline.vue'
import { useStaffRequestTripDetailTab } from '../../../composables/useStaffRequestTripDetailTab'
import { rdEmptyLabel } from '../../../util/requestDetailEmpty'

const props = defineProps({
  req: { type: Object, required: true },
  costEstimate: { type: Object, default: null },
})

const { t } = useI18n()

const {
  planDateNeeded,
  planDuration,
  planVehicleType,
  planBudget,
  hasAssignment,
  assignmentVehicle,
  assignmentDriver,
  assignmentPlate,
  assignmentOperator,
  assignmentStatus,
  passengerRows,
  executionSteps,
  purposeText,
  purposeTargets,
  basisText,
  financeVehicle,
  financeExtras,
  financeAdvance,
  financeTotal,
  noteDispatcher,
  noteDriver,
  noteExtra,
  trip,
  isCargo,
} = useStaffRequestTripDetailTab(
  () => props.req,
  () => props.costEstimate,
)

const planStats = computed(() => [
  { key: 'date', label: t('request_detail.ops_lbl_date_needed'), value: planDateNeeded.value },
  { key: 'duration', label: t('request_detail.trip_tab_stat_duration'), value: planDuration.value },
  { key: 'vehicle', label: t('request_detail.trip_tab_stat_vehicle'), value: planVehicleType.value },
  { key: 'budget', label: t('request_detail.trip_tab_stat_budget'), value: planBudget.value },
])

const assignmentCells = computed(() => [
  { key: 'vehicle', label: t('request_detail.trip_tab_lbl_assigned_vehicle'), value: assignmentVehicle.value },
  { key: 'driver', label: t('request_detail.overview_lbl_driver'), value: assignmentDriver.value },
  { key: 'plate', label: t('request_detail.overview_lbl_plate'), value: assignmentPlate.value },
  { key: 'operator', label: t('request_detail.trip_tab_lbl_operator'), value: assignmentOperator.value },
  { key: 'status', label: t('request_detail.overview_lbl_dispatch_status'), value: assignmentStatus.value },
])

const passengerSectionTitle = computed(() =>
  isCargo.value
    ? t('request_detail.trip_tab_section_cargo')
    : t('request_detail.trip_tab_section_passengers'),
)

const financeLines = computed(() => [
  { key: 'vehicle', label: t('request_detail.trip_tab_fin_vehicle'), value: financeVehicle.value },
  { key: 'extras', label: t('request_detail.trip_tab_fin_extras'), value: financeExtras.value },
  { key: 'advance', label: t('request_detail.trip_tab_fin_advance'), value: financeAdvance.value },
  { key: 'total', label: t('request_detail.trip_tab_fin_total'), value: financeTotal.value },
])

const noteBlocks = computed(() => [
  { key: 'dispatcher', label: t('request_detail.trip_tab_note_dispatcher'), text: noteDispatcher.value },
  { key: 'driver', label: t('request_detail.trip_tab_note_driver'), text: noteDriver.value },
  { key: 'extra', label: t('request_detail.trip_tab_note_extra'), text: noteExtra.value },
])

const statusValueClass = computed(() => {
  const raw = trip.value?.status
  if (!raw) return 'text-slate-900 dark:text-slate-100'
  if (['completed', 'done'].includes(raw)) return 'text-emerald-700 dark:text-emerald-400'
  if (['in_progress', 'started', 'en_route'].includes(raw)) return 'text-sky-700 dark:text-sky-400'
  if (['cancelled', 'incident'].includes(raw)) return 'text-rose-700 dark:text-rose-400'
  return 'text-amber-800 dark:text-amber-300'
})
</script>
