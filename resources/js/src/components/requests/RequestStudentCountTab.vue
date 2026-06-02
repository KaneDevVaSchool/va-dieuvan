<template>
  <div id="request-focus-passenger" class="scroll-mt-24 space-y-5">
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-200 bg-slate-50/80 px-4 py-4 sm:px-5">
        <h2 class="text-sm font-bold uppercase tracking-wide text-slate-900 sm:text-base">
          {{ t('request_detail.bm03_student_count_header') }}
        </h2>
        <p class="mt-1 text-xs text-slate-600">{{ t('request_detail.passenger_section_lead') }}</p>
      </div>
      <div class="flex flex-col gap-5 p-4 sm:flex-row sm:items-start sm:p-5">
        <div
          class="mx-auto grid w-full max-w-[15rem] shrink-0 gap-px overflow-hidden rounded border border-teal-300/80 bg-teal-50/30 text-[11px] sm:mx-0"
        >
          <div
            class="col-span-2 border-b border-teal-200 bg-teal-50 px-2 py-1 text-center text-[10px] font-bold uppercase tracking-wide text-teal-900"
          >
            {{ t('request_detail.bm03_student_count_header') }}
          </div>
          <div class="grid grid-cols-2 bg-white">
            <span class="border-b border-r border-slate-300 px-2 py-1 font-semibold text-slate-600">
              {{ t('request_detail.bm03_student_count_plan_short') }}
            </span>
            <span class="border-b border-slate-300 px-2 py-1 text-right tabular-nums text-slate-900">
              {{ studentCountPlanDisplay ?? '—' }}
            </span>
          </div>
          <div class="grid grid-cols-2 bg-white">
            <span class="border-b border-r border-slate-300 px-2 py-1 font-semibold text-slate-600">
              {{ t('request_detail.bm03_student_count_actual_short') }}
            </span>
            <span
              class="border-b border-slate-300 px-2 py-1 text-right tabular-nums font-semibold"
              :class="studentCountActualHighlight ? 'text-teal-800' : 'text-slate-900'"
            >
              {{ studentCountActualDisplay ?? '—' }}
            </span>
          </div>
          <div class="grid grid-cols-2 bg-white">
            <span class="border-r border-slate-300 px-2 py-1 font-semibold text-slate-600">
              {{ t('request_detail.bm03_student_count_status') }}
            </span>
            <span class="flex justify-end px-1 py-0.5">
              <StudentCountTrackingBadge
                :tracking-key="studentCountTrackingKey"
                i18n-prefix="requests_page.extracurricular_table"
              />
            </span>
          </div>
        </div>

        <div v-if="showPassengerAdjustSection" class="min-w-0 flex-1">
          <StudentCountField
            v-model:passenger-count="passengerDraftModel"
            :student-count-plan="req?.passenger_count != null ? Number(req.passenger_count) : null"
            :locked="passengerDepartLocked"
            :is-dispatcher-override="passengerDispatcherOverride"
            :depart-at-formatted="departAtFormatted"
            :saving="passengerSaving"
            :error="passengerPatchErr"
            @save="$emit('savePassenger')"
          />
        </div>
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
