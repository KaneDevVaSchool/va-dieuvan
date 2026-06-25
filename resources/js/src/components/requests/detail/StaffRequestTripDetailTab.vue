<template>
  <div class="min-w-0" data-testid="staff-request-trip-detail-tab">
    <StaffRequestTripCollapseSection
      section-key="plan"
      :title="t('request_detail.trip_tab_section_plan')"
    >
      <dl class="grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-4 sm:gap-x-6">
        <div v-for="stat in planStats" :key="stat.key" class="min-w-0">
          <dt :class="RD_FIELD_LABEL">{{ stat.label }}</dt>
          <dd :class="[RD_FIELD_VALUE_TABULAR, 'truncate']">{{ stat.value }}</dd>
        </div>
      </dl>
    </StaffRequestTripCollapseSection>

    <StaffRequestTripCollapseSection
      section-key="assignment"
      :title="t('request_detail.trip_tab_section_assignment')"
      :badge="hasAssignment && trip?.status ? assignmentStatus : ''"
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
        class="flex flex-col items-center gap-1.5 py-3 text-center text-sm text-slate-500 dark:text-slate-400"
        data-testid="staff-request-trip-tab-assignment-empty"
      >
        <ExclamationTriangleIcon class="h-5 w-5 text-amber-500" aria-hidden="true" />
        {{ t('request_detail.trip_tab_assignment_empty_title') }}
      </p>

      <dl
        v-else
        class="grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-3 lg:grid-cols-5"
        data-testid="staff-request-trip-tab-assignment-grid"
      >
        <div v-for="cell in assignmentCells" :key="cell.key" class="min-w-0">
          <dt :class="RD_FIELD_LABEL">{{ cell.label }}</dt>
          <dd
            class="mt-0.5 truncate text-sm font-semibold leading-snug"
            :class="cell.key === 'status' ? statusValueClass : 'text-slate-900 dark:text-slate-100'"
          >
            {{ cell.value }}
          </dd>
        </div>
      </dl>
    </StaffRequestTripCollapseSection>

    <StaffRequestTripCollapseSection
      section-key="execution"
      :title="t('request_detail.trip_tab_section_execution')"
    >
      <StaffRequestTripExecutionTimeline
        :steps="executionSteps"
        :planned-label="t('request_detail.trip_tab_planned')"
        :actual-label="t('request_detail.trip_tab_actual')"
        :empty-value="emptyLabel"
      />
    </StaffRequestTripCollapseSection>

    <StaffRequestTripCollapseSection
      section-key="finance"
      :title="t('request_detail.trip_tab_section_finance')"
    >
      <dl class="grid grid-cols-2 gap-x-4 gap-y-3 sm:grid-cols-4 sm:gap-x-6">
        <div v-for="line in financeLines" :key="line.key" class="min-w-0">
          <dt :class="RD_FIELD_LABEL">{{ line.label }}</dt>
          <dd
            class="truncate"
            :class="line.key === 'total' ? RD_FIELD_VALUE_TOTAL : RD_FIELD_VALUE_TABULAR"
          >
            {{ line.value }}
          </dd>
        </div>
      </dl>
    </StaffRequestTripCollapseSection>

    <StaffRequestTripCollapseSection
      section-key="passengers"
      :title="passengerSectionTitle"
      :badge="passengerRows.length ? String(passengerRows.length) : ''"
      collapsible
      :default-open="passengerRows.length > 0 && passengerRows.length <= 8"
      :expand-label="t('request_detail.trip_tab_expand_section')"
      :collapse-label="t('request_detail.trip_tab_collapse_section')"
    >
      <p
        v-if="!passengerRows.length"
        class="text-sm"
        :class="RD_EMPTY"
        data-testid="staff-request-trip-tab-passengers-empty"
      >
        {{ t('request_detail.trip_tab_passengers_empty') }}
      </p>

      <div v-else class="-mx-1 overflow-x-auto sm:mx-0">
        <table class="w-full min-w-0 text-left text-sm" data-testid="staff-request-trip-tab-passengers-table">
          <thead>
            <tr class="border-b border-slate-100 dark:border-slate-800">
              <th scope="col" :class="[RD_FIELD_LABEL, 'py-2 pr-3 text-left']">{{ t('request_detail.trip_tab_col_name') }}</th>
              <th scope="col" :class="[RD_FIELD_LABEL, 'hidden py-2 pr-3 text-left md:table-cell']">{{ t('request_detail.trip_tab_col_department') }}</th>
              <th scope="col" :class="[RD_FIELD_LABEL, 'py-2 pr-3 text-left']">{{ t('request_detail.trip_tab_col_role') }}</th>
              <th scope="col" :class="[RD_FIELD_LABEL, 'py-2 text-left']">{{ t('request_detail.trip_tab_col_phone') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
            <tr v-for="row in passengerRows" :key="row.key">
              <td class="max-w-[9rem] truncate py-2 pr-3 font-medium text-slate-900 dark:text-slate-100">{{ row.name }}</td>
              <td class="hidden max-w-[8rem] truncate py-2 pr-3 text-slate-600 dark:text-slate-400 md:table-cell">{{ row.department }}</td>
              <td class="max-w-[6rem] truncate py-2 pr-3 text-slate-600 dark:text-slate-400">{{ row.role }}</td>
              <td class="py-2 tabular-nums text-slate-700 dark:text-slate-300">{{ row.phone }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </StaffRequestTripCollapseSection>

    <StaffRequestTripCollapseSection
      section-key="business"
      :title="t('request_detail.trip_tab_section_business')"
      collapsible
      :default-open="false"
      :expand-label="t('request_detail.trip_tab_expand_section')"
      :collapse-label="t('request_detail.trip_tab_collapse_section')"
    >
      <div class="grid gap-4 sm:grid-cols-2 sm:gap-6">
        <div class="min-w-0">
          <p :class="RD_FIELD_LABEL">{{ t('request_detail.ops_lbl_purpose') }}</p>
          <p v-if="purposeText" :class="RD_FIELD_VALUE_BODY">{{ purposeText }}</p>
          <p v-else :class="[RD_FIELD_VALUE_BODY, RD_EMPTY]">{{ rdEmptyLabel(t, 'purpose') }}</p>
          <div v-if="purposeTargets.length" class="mt-2 flex flex-wrap gap-1.5">
            <span
              v-for="(tg, i) in purposeTargets"
              :key="i"
              class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-xs font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
            >{{ tg }}</span>
          </div>
        </div>
        <div class="min-w-0">
          <p :class="RD_FIELD_LABEL">{{ t('request_detail.ops_lbl_basis') }}</p>
          <p
            class="whitespace-pre-wrap"
            :class="RD_FIELD_VALUE_BODY"
            data-testid="staff-request-trip-tab-basis-block"
          >
            <template v-if="basisText">{{ basisText }}</template>
            <template v-else><span :class="RD_EMPTY">{{ rdEmptyLabel(t, 'basis') }}</span></template>
          </p>
        </div>
      </div>
    </StaffRequestTripCollapseSection>

    <StaffRequestTripCollapseSection
      section-key="notes"
      :title="t('request_detail.trip_tab_section_notes')"
    >
      <dl class="grid gap-4 sm:grid-cols-3 sm:gap-5">
        <div v-for="note in noteBlocks" :key="note.key" class="min-w-0">
          <dt :class="RD_FIELD_LABEL">{{ note.label }}</dt>
          <dd
            class="mt-1 whitespace-pre-wrap text-sm leading-relaxed"
            :class="note.text ? 'text-slate-800 dark:text-slate-200' : RD_EMPTY"
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
import {
  RD_EMPTY,
  RD_FIELD_LABEL,
  RD_FIELD_VALUE_BODY,
  RD_FIELD_VALUE_TABULAR,
  RD_FIELD_VALUE_TOTAL,
} from '../../../util/requestDetailTypography'

const props = defineProps({
  req: { type: Object, required: true },
  costEstimate: { type: Object, default: null },
})

const { t } = useI18n()

const {
  emptyLabel,
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
