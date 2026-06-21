<template>
  <div
    class="divide-y divide-slate-100 dark:divide-slate-800"
    data-testid="staff-request-trip-detail-tab"
  >
    <!-- Section 1 — Kế hoạch điều vận -->
    <section class="px-4 py-6 sm:px-5" :aria-label="t('request_detail.trip_tab_section_plan')">
      <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">
        {{ t('request_detail.trip_tab_section_plan') }}
      </p>
      <div class="mt-4 grid grid-cols-2 gap-x-6 gap-y-5 lg:grid-cols-4">
        <div v-for="stat in planStats" :key="stat.key" class="min-w-0">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ stat.label }}
          </p>
          <p class="mt-1 font-display text-xl font-semibold tabular-nums leading-tight text-slate-900 dark:text-white sm:text-2xl">
            {{ stat.value }}
          </p>
        </div>
      </div>
    </section>

    <!-- Section 2 — Điều phối phương tiện -->
    <section class="px-4 py-6 sm:px-5" :aria-label="t('request_detail.trip_tab_section_assignment')">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">
          {{ t('request_detail.trip_tab_section_assignment') }}
        </p>
        <RouterLink
          v-if="trip?.id"
          :to="`/trips/${trip.id}`"
          class="text-xs font-semibold text-va-700 hover:text-va-900 dark:text-va-400 dark:hover:text-va-200"
          data-testid="staff-request-trip-tab-open-trip"
        >
          {{ t('request_detail.ops_open_trip_detail') }}
        </RouterLink>
      </div>

      <div
        v-if="!hasAssignment"
        class="mt-4 flex flex-col items-center rounded-xl bg-slate-50/80 px-6 py-10 text-center dark:bg-slate-800/40"
        data-testid="staff-request-trip-tab-assignment-empty"
      >
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400">
          <ExclamationTriangleIcon class="h-6 w-6" aria-hidden="true" />
        </span>
        <p class="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-100">
          {{ t('request_detail.trip_tab_assignment_empty_title') }}
        </p>
        <p class="mt-1 max-w-sm text-sm text-slate-500 dark:text-slate-400">
          {{ t('request_detail.trip_tab_assignment_empty_hint') }}
        </p>
      </div>

      <dl
        v-else
        class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5"
        data-testid="staff-request-trip-tab-assignment-grid"
      >
        <div v-for="cell in assignmentCells" :key="cell.key" class="min-w-0">
          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ cell.label }}
          </dt>
          <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
            {{ cell.value }}
          </dd>
        </div>
      </dl>
    </section>

    <!-- Section 3 — Hành khách -->
    <section class="px-4 py-6 sm:px-5" :aria-label="t('request_detail.trip_tab_section_passengers')">
      <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">
        {{ passengerSectionTitle }}
      </p>

      <p
        v-if="!passengerRows.length"
        class="mt-4 text-sm italic text-slate-400 dark:text-slate-500"
        data-testid="staff-request-trip-tab-passengers-empty"
      >
        {{ t('request_detail.trip_tab_passengers_empty') }}
      </p>

      <div v-else class="mt-4 -mx-4 overflow-x-auto sm:mx-0">
        <table class="min-w-full text-left text-sm" data-testid="staff-request-trip-tab-passengers-table">
          <thead>
            <tr class="border-b border-slate-100 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:border-slate-800 dark:text-slate-500">
              <th scope="col" class="px-4 py-2 font-semibold sm:px-0">{{ t('request_detail.trip_tab_col_name') }}</th>
              <th scope="col" class="hidden px-3 py-2 font-semibold sm:table-cell">{{ t('request_detail.trip_tab_col_department') }}</th>
              <th scope="col" class="px-3 py-2 font-semibold">{{ t('request_detail.trip_tab_col_role') }}</th>
              <th scope="col" class="px-3 py-2 font-semibold sm:px-0">{{ t('request_detail.trip_tab_col_phone') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50 dark:divide-slate-800/80">
            <tr v-for="row in passengerRows" :key="row.key">
              <td class="px-4 py-3 sm:px-0">
                <div class="flex min-w-0 items-center gap-2.5">
                  <span
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                    aria-hidden="true"
                  >{{ row.initials }}</span>
                  <span class="min-w-0 font-medium text-slate-900 dark:text-slate-100">{{ row.name }}</span>
                </div>
              </td>
              <td class="hidden px-3 py-3 text-slate-600 dark:text-slate-300 sm:table-cell">{{ row.department }}</td>
              <td class="px-3 py-3 text-slate-600 dark:text-slate-300">{{ row.role }}</td>
              <td class="px-3 py-3 tabular-nums text-slate-700 dark:text-slate-200 sm:px-0">{{ row.phone }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Section 4 — Lịch trình thực hiện -->
    <section class="px-4 py-6 sm:px-5" :aria-label="t('request_detail.trip_tab_section_execution')">
      <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">
        {{ t('request_detail.trip_tab_section_execution') }}
      </p>
      <ol class="relative mt-5 space-y-0" data-testid="staff-request-trip-tab-execution-timeline">
        <li
          v-for="(step, idx) in executionSteps"
          :key="step.key"
          class="relative flex gap-4 pb-8 last:pb-0"
        >
          <div class="flex shrink-0 flex-col items-center">
            <span
              class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold"
              :class="stepCircleClass(step.state)"
              :aria-current="step.state === 'current' ? 'step' : undefined"
            >
              <CheckIcon v-if="step.state === 'done'" class="h-4 w-4" />
              <span v-else class="tabular-nums">{{ idx + 1 }}</span>
            </span>
            <span
              v-if="idx < executionSteps.length - 1"
              class="mt-1 w-px flex-1 min-h-[2rem] bg-slate-200 dark:bg-slate-700"
              :class="step.state === 'done' ? 'bg-va-500 dark:bg-va-400' : ''"
              aria-hidden="true"
            />
          </div>
          <div class="min-w-0 flex-1 pt-0.5">
            <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ step.label }}</p>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
              <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ t('request_detail.trip_tab_planned') }}</p>
                <p class="mt-0.5 text-sm tabular-nums text-slate-600 dark:text-slate-300">{{ step.planned }}</p>
              </div>
              <div>
                <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">{{ t('request_detail.trip_tab_actual') }}</p>
                <p class="mt-0.5 text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ step.actual }}</p>
              </div>
            </div>
          </div>
        </li>
      </ol>
    </section>

    <!-- Section 5 — Thông tin nghiệp vụ -->
    <section class="px-4 py-6 sm:px-5" :aria-label="t('request_detail.trip_tab_section_business')">
      <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">
        {{ t('request_detail.trip_tab_section_business') }}
      </p>
      <div class="mt-4 grid gap-6 lg:grid-cols-2">
        <div class="min-w-0">
          <h3 class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ t('request_detail.ops_lbl_purpose') }}</h3>
          <p
            v-if="purposeText"
            class="mt-2 text-sm leading-relaxed text-slate-800 dark:text-slate-200"
          >{{ purposeText }}</p>
          <p v-else class="mt-2 text-sm italic text-slate-400">{{ emptyLabel }}</p>
          <div v-if="purposeTargets.length" class="mt-3 flex flex-wrap gap-1.5">
            <span
              v-for="(tg, i) in purposeTargets"
              :key="i"
              class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300"
            >{{ tg }}</span>
          </div>
        </div>
        <div class="min-w-0">
          <h3 class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ t('request_detail.ops_lbl_basis') }}</h3>
          <div
            class="mt-2 rounded-lg bg-slate-50/90 px-4 py-3 text-sm leading-relaxed text-slate-800 dark:bg-slate-800/50 dark:text-slate-200"
            data-testid="staff-request-trip-tab-basis-block"
          >
            <p v-if="basisText" class="whitespace-pre-wrap">{{ basisText }}</p>
            <p v-else class="italic text-slate-400">{{ emptyLabel }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Section 6 — Chi phí -->
    <section class="px-4 py-6 sm:px-5" :aria-label="t('request_detail.trip_tab_section_finance')">
      <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">
        {{ t('request_detail.trip_tab_section_finance') }}
      </p>
      <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="line in financeLines" :key="line.key" class="min-w-0" :class="line.key === 'total' ? 'sm:col-span-2 lg:col-span-1' : ''">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ line.label }}
          </p>
          <p
            class="mt-1 tabular-nums text-slate-900 dark:text-white"
            :class="line.key === 'total' ? 'font-display text-2xl font-bold sm:text-3xl' : 'text-lg font-semibold'"
          >
            {{ line.value }}
          </p>
        </div>
      </div>
    </section>

    <!-- Section 7 — Ghi chú -->
    <section class="px-4 py-6 sm:px-5" :aria-label="t('request_detail.trip_tab_section_notes')">
      <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 dark:text-slate-500">
        {{ t('request_detail.trip_tab_section_notes') }}
      </p>
      <div class="mt-4 grid gap-5 lg:grid-cols-3">
        <div v-for="note in noteBlocks" :key="note.key" class="min-w-0">
          <h3 class="text-xs font-semibold text-slate-600 dark:text-slate-400">{{ note.label }}</h3>
          <p
            class="mt-2 whitespace-pre-wrap text-sm leading-relaxed"
            :class="note.text ? 'text-slate-800 dark:text-slate-200' : 'italic text-slate-400'"
          >{{ note.text || emptyLabel }}</p>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { CheckIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import { useStaffRequestTripDetailTab } from '../../composables/useStaffRequestTripDetailTab'

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

function stepCircleClass(state) {
  if (state === 'done') return 'bg-va-600 text-white dark:bg-va-500'
  if (state === 'current') return 'bg-sky-100 text-sky-800 ring-2 ring-sky-500/40 dark:bg-sky-950/50 dark:text-sky-200'
  return 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
}
</script>
