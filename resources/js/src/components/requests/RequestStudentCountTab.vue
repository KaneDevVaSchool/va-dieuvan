<template>
  <div id="request-focus-passenger" class="scroll-mt-24">
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-200 bg-slate-50/80 px-4 py-3.5 sm:px-5">
        <h2 class="text-sm font-bold text-slate-900 sm:text-base">
          {{ t('request_detail.bm03_student_count_header') }}
        </h2>
        <p class="mt-0.5 text-xs leading-relaxed text-slate-600">
          {{ t('request_detail.passenger_section_lead') }}
        </p>
      </div>

      <dl
        class="grid grid-cols-1 divide-y divide-slate-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0"
      >
        <div class="px-4 py-3.5 sm:px-5">
          <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
            {{ t('request_detail.bm03_student_count_plan_short') }}
          </dt>
          <dd class="mt-1 text-2xl font-semibold tabular-nums leading-none text-slate-900">
            {{ studentCountPlanDisplay ?? '—' }}
          </dd>
        </div>
        <div class="px-4 py-3.5 sm:px-5">
          <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
            {{ t('request_detail.bm03_student_count_actual_short') }}
          </dt>
          <dd
            class="mt-1 text-2xl font-semibold tabular-nums leading-none"
            :class="studentCountActualHighlight ? 'text-teal-700' : 'text-slate-900'"
          >
            {{ studentCountActualDisplay ?? '—' }}
          </dd>
        </div>
        <div class="flex flex-col justify-center px-4 py-3.5 sm:px-5">
          <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
            {{ t('request_detail.bm03_student_count_status') }}
          </dt>
          <dd class="mt-1.5">
            <StudentCountTrackingBadge
              :tracking-key="studentCountTrackingKey"
              i18n-prefix="requests_page.extracurricular_table"
            />
          </dd>
        </div>
      </dl>

      <div
        v-if="showPassengerAdjustSection"
        class="border-t border-slate-200 bg-slate-50/40 px-4 py-4 sm:px-5"
      >
        <StudentCountField
          v-model:passenger-count="passengerDraftModel"
          embedded
          :student-count-plan="req?.passenger_count != null ? Number(req.passenger_count) : null"
          :locked="passengerDepartLocked"
          :is-dispatcher-override="passengerDispatcherOverride"
          :depart-at-formatted="departAtFormatted"
          :saving="passengerSaving"
          :error="passengerPatchErr"
          @save="$emit('savePassenger')"
        />
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import StudentCountField from '../recurring/StudentCountField.vue'
import StudentCountTrackingBadge from './extracurricular/StudentCountTrackingBadge.vue'
import { extracurricularStudentCountTrackingKey } from '../../composables/useExtracurricularStudentCountTracking'

const { t } = useI18n()

const props = defineProps({
  req: { type: Object, default: null },
  passengerSaving: { type: Boolean, default: false },
  passengerPatchErr: { type: String, default: '' },
  passengerDepartLocked: { type: Boolean, default: true },
  passengerDispatcherOverride: { type: Boolean, default: false },
  departAtFormatted: { type: String, default: '' },
  showPassengerAdjustSection: { type: Boolean, default: false },
})

const passengerDraftModel = defineModel('passengerDraft', {
  type: Number,
  default: 1,
})

defineEmits(['savePassenger'])

const studentCountPlanDisplay = computed(() => {
  const n = props.req?.passenger_count
  if (n == null || n === '') return null
  return Number(n)
})

const studentCountActualDisplay = computed(() => {
  const saved = props.req?.student_count_actual
  if (saved != null && saved !== '') return Number(saved)
  const draft = passengerDraftModel.value
  if (draft != null && draft > 0) return Number(draft)
  return null
})

const studentCountActualHighlight = computed(() => {
  const plan = studentCountPlanDisplay.value
  const actual = studentCountActualDisplay.value
  if (plan == null || actual == null) return actual != null
  return actual !== plan
})

const studentCountTrackingKey = computed(() => extracurricularStudentCountTrackingKey(props.req))
</script>
