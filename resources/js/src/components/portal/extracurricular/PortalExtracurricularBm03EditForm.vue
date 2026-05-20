<template>
  <section ref="rootEl" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-start gap-4 border-b border-slate-200 bg-slate-50/80 px-4 py-4 sm:px-6">
      <div class="min-w-0 flex-1 text-center sm:text-left">
        <h2 class="text-base font-bold uppercase tracking-tight text-slate-900 sm:text-lg">
          {{ t('portal.recurring_edit.bm03_title') }}
        </h2>
        <p class="mt-1 text-xs font-medium text-slate-600 sm:text-sm">{{ bm.tripSubtitle }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ t('portal.recurring_edit.lead') }}</p>
        <p
          class="mt-2 rounded-lg border border-violet-200 bg-violet-50/80 px-3 py-2 text-xs font-medium text-violet-950"
        >
          {{ t('portal.recurring_edit.workflow_steps') }}
        </p>
      </div>
      <div
        class="mx-auto grid w-full max-w-[13rem] shrink-0 gap-px overflow-hidden rounded border border-slate-300 text-[11px] sm:mx-0"
      >
        <div class="grid grid-cols-2 bg-white">
          <span class="border-b border-r border-slate-300 px-2 py-1 font-semibold text-slate-600">Ký hiệu</span>
          <span class="border-b border-slate-300 px-2 py-1 text-right text-slate-900">BM.03/MH.QT.04</span>
        </div>
        <div class="grid grid-cols-2 bg-white">
          <span class="border-r border-slate-300 px-2 py-1 font-semibold text-slate-600">Mã yêu cầu</span>
          <span class="px-2 py-1 text-right font-medium text-slate-900">#{{ req.id }}</span>
        </div>
        <div class="grid grid-cols-2 bg-white">
          <span class="border-t border-r border-slate-300 px-2 py-1 font-semibold text-slate-600">Trạng thái</span>
          <span class="border-t border-slate-300 px-2 py-1 text-right">
            <StatusBadge :status="req.status" size="sm" />
          </span>
        </div>
      </div>
    </div>

    <div class="divide-y divide-slate-100 px-4 py-4 sm:px-6">
      <details class="group/section">
        <summary
          class="flex cursor-pointer list-none items-center gap-2 rounded-lg bg-slate-50 px-3 py-2.5 [&::-webkit-details-marker]:hidden"
        >
          <span class="inline-flex h-5 w-5 items-center justify-center rounded bg-slate-800 text-xs font-bold text-white">A</span>
          <span class="text-xs font-bold uppercase tracking-wide text-slate-700">{{ t('portal.recurring_edit.bm03_sec_a') }}</span>
        </summary>
        <div class="mt-2 grid gap-4 rounded-lg border border-slate-200 px-3 py-4 sm:grid-cols-2">
          <BmRoField label="a.1 Họ và tên" :model-value="bm.aName" />
          <BmRoField label="a.2 Email VA của nhân viên" :model-value="bm.aEmail" />
          <BmRoField label="a.3 Số điện thoại" :model-value="bm.aPhone" />
          <BmRoField label="a.4 Đơn vị" :model-value="bm.aUnit" />
        </div>
      </details>

      <details class="group/section">
        <summary
          class="flex cursor-pointer list-none items-center gap-2 rounded-lg bg-slate-50 px-3 py-2.5 [&::-webkit-details-marker]:hidden"
        >
          <span class="inline-flex h-5 w-5 items-center justify-center rounded bg-slate-800 text-xs font-bold text-white">B</span>
          <span class="text-xs font-bold uppercase tracking-wide text-slate-700">{{ t('portal.recurring_edit.bm03_sec_b') }}</span>
        </summary>
        <div class="mt-2 space-y-4 rounded-lg border border-slate-200 px-3 py-4">
          <BmRoField label="b.1 Mục đích sử dụng" :model-value="bm.purposeDisplay" multiline />
          <BmRoField label="b.2 Căn cứ đề xuất" :model-value="bm.basisDisplay" multiline />
        </div>
      </details>

      <details class="group/section" open>
        <summary
          class="flex cursor-pointer list-none items-center gap-2 rounded-lg bg-slate-50 px-3 py-2.5 [&::-webkit-details-marker]:hidden"
        >
          <span class="inline-flex h-5 w-5 items-center justify-center rounded bg-slate-800 text-xs font-bold text-white">C</span>
          <span class="text-xs font-bold uppercase tracking-wide text-slate-700">{{ t('portal.recurring_edit.bm03_sec_c') }}</span>
        </summary>
        <div class="mt-2 space-y-4 rounded-lg border border-slate-200 px-3 py-4">
          <div class="grid gap-4 sm:grid-cols-2">
            <BmRoField label="c.1 Ngày đề xuất" :model-value="fmtDateVi(bm.proposedDateRaw)" />
            <BmRoField label="c.2 Ngày cần sử dụng xe" :model-value="fmtDateVi(bm.dateNeededRaw)" />
          </div>
          <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
            {{ t('portal.recurring_edit.bm03_time_note') }}
          </p>
          <div class="grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
            <label class="block">
              <span class="text-xs font-semibold text-slate-700">{{ t('portal.recurring_edit.depart_at') }}</span>
              <input
                v-model="draft.departAtLocal"
                type="datetime-local"
                class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                :disabled="locked"
              />
            </label>
            <label class="block">
              <span class="text-xs font-semibold text-slate-700">{{ t('portal.recurring_edit.arrive_by') }}</span>
              <input
                v-model="draft.arriveByLocal"
                type="datetime-local"
                class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
                :disabled="locked"
              />
            </label>
          </div>
          <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="font-semibold text-slate-700">{{ t('portal.recurring_edit.bm03_trip_type') }}:</span>
            <span class="rounded border border-slate-300 bg-white px-2 py-1 font-medium">{{ bm.tripTypeLabel }}</span>
          </div>
        </div>
      </details>

      <details class="group/section">
        <summary
          class="flex cursor-pointer list-none items-center gap-2 rounded-lg bg-slate-50 px-3 py-2.5 [&::-webkit-details-marker]:hidden"
        >
          <span class="inline-flex h-5 w-5 items-center justify-center rounded bg-slate-800 text-xs font-bold text-white">D</span>
          <span class="text-xs font-bold uppercase tracking-wide text-slate-700">{{ t('portal.recurring_edit.bm03_sec_d') }}</span>
        </summary>
        <div class="mt-2 rounded-lg border border-slate-200 px-3 py-4">
          <p class="mb-3 text-[11px] font-bold uppercase tracking-wide text-slate-700">d.1 Đối tượng sử dụng</p>
          <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2 lg:grid-cols-4">
            <label
              v-for="opt in bm.TARGET_OPTIONS"
              :key="opt"
              class="flex items-start gap-2 text-xs text-slate-800"
            >
              <input
                type="checkbox"
                class="mt-0.5 h-4 w-4 rounded border-slate-300"
                :checked="bm.targetsSet.has(opt)"
                disabled
              />
              <span class="leading-snug">{{ opt }}</span>
            </label>
          </div>
          <div class="mt-4 grid gap-4 border-t border-slate-200 pt-4 sm:grid-cols-3">
            <BmRoField label="d.2 Họ tên" :model-value="bm.coordinatorName" />
            <BmRoField label="Email" :model-value="bm.coordinatorEmail" />
            <BmRoField label="SĐT" :model-value="bm.coordinatorPhone" />
          </div>
        </div>
      </details>

      <details class="group/section" open>
        <summary
          class="flex cursor-pointer list-none items-center gap-2 rounded-lg bg-slate-50 px-3 py-2.5 [&::-webkit-details-marker]:hidden"
        >
          <span class="inline-flex h-5 w-5 items-center justify-center rounded bg-slate-800 text-xs font-bold text-white">E</span>
          <span class="text-xs font-bold uppercase tracking-wide text-slate-700">{{ t('portal.recurring_edit.bm03_sec_e') }}</span>
        </summary>
        <div class="mt-2 space-y-4 rounded-lg border border-slate-200 px-3 py-4">
          <label class="block">
            <span class="text-xs font-semibold text-slate-700">{{ t('dispatch_wizard.s3.pickup_ph') }}</span>
            <input
              v-model="draft.origin"
              type="text"
              class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
              :disabled="locked"
            />
          </label>
          <label class="block">
            <span class="text-xs font-semibold text-slate-700">{{ t('dispatch_wizard.s3.dropoff_ph') }}</span>
            <input
              v-model="draft.destination"
              type="text"
              class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
              :disabled="locked"
            />
          </label>
        </div>
      </details>

      <details class="group/section" open>
        <summary
          class="flex cursor-pointer list-none items-center gap-2 rounded-lg bg-violet-50 px-3 py-2.5 [&::-webkit-details-marker]:hidden"
        >
          <span class="inline-flex h-5 w-5 items-center justify-center rounded bg-violet-700 text-xs font-bold text-white">G</span>
          <span class="text-xs font-bold uppercase tracking-wide text-violet-900">{{ t('portal.recurring_edit.bm03_sec_g') }}</span>
          <StudentCountTrackingBadge
            class="ml-auto"
            :tracking-key="row.studentCountTrackingKey(req)"
            i18n-prefix="portal.extracurricular_table"
          />
        </summary>
        <div class="mt-2 space-y-4 rounded-lg border border-violet-200 bg-violet-50/30 px-3 py-4">
          <div class="flex flex-wrap items-end gap-4">
            <BmRoField
              compact
              :label="t('portal.extracurricular_table.col_plan')"
              :model-value="String(row.planStudentCount(req) ?? '—')"
            />
            <label class="block min-w-[8rem]">
              <span class="text-xs font-bold text-violet-900">{{ t('portal.extracurricular_table.col_actual') }}</span>
              <input
                v-model.number="draft.studentCount"
                type="number"
                min="1"
                max="999"
                class="mt-1 w-full rounded-md border border-violet-300 bg-white px-3 py-2 text-lg font-bold tabular-nums text-slate-900"
                :disabled="locked"
              />
            </label>
          </div>
          <label class="block">
            <span class="text-xs font-semibold text-slate-700">{{ t('portal.recurring_edit.sec_notes') }}</span>
            <textarea
              v-model="draft.notes"
              rows="4"
              class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
              :disabled="locked"
            />
          </label>
        </div>
      </details>
    </div>

    <p v-if="formError" class="px-4 pb-2 text-sm text-rose-600 sm:px-6">{{ formError }}</p>

    <div
      class="sticky bottom-0 flex flex-wrap gap-3 border-t border-slate-100 bg-white/95 px-4 py-4 backdrop-blur sm:px-6"
    >
      <button
        type="button"
        class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50 disabled:opacity-50"
        :disabled="locked || saving"
        @click="save"
      >
        {{ saving ? t('portal.recurring_edit.save_draft_busy') : t('portal.recurring_edit.save_draft') }}
      </button>
      <button
        type="button"
        class="rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-violet-500 disabled:opacity-50"
        :disabled="locked || saving || submitting || !canSubmit"
        @click="submitToDispatch"
      >
        {{
          submitting
            ? t('portal.extracurricular_table.submit_dispatch_busy')
            : t('portal.extracurricular_table.submit_dispatch')
        }}
      </button>
      <p v-if="locked" class="self-center text-xs text-amber-800">{{ t('portal.extracurricular_table.depart_soon') }}</p>
    </div>
  </section>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import StatusBadge from '../../ui/StatusBadge.vue'
import BmRoField from '../../requests/RequestBm03RoField.vue'
import StudentCountTrackingBadge from '../../requests/extracurricular/StudentCountTrackingBadge.vue'
import { patchPortalRecurringInstance, submitPortalRecurringInstance } from '../../../api/requests'
import { formatApiError } from '../../../api/http'
import { useAuthStore } from '../../../store'
import { useExtracurricularRequestRow } from '../../../composables/useExtracurricularRequestRow'
import { useBm03PresentFromRequest, fmtDateVi } from '../../../composables/useBm03PresentFromRequest'
import { confirmAction } from '../../../composables/useConfirm'

const props = defineProps({
  req: { type: Object, required: true },
})

const emit = defineEmits(['saved'])

const rootEl = ref(null)
defineExpose({ rootEl })

const { t } = useI18n()
const auth = useAuthStore()
const row = useExtracurricularRequestRow(auth, computed(() => auth.user))
const reqRef = computed(() => props.req)
const bm = useBm03PresentFromRequest(reqRef)

const saving = ref(false)
const submitting = ref(false)
const formError = ref('')

const draft = reactive({
  departAtLocal: '',
  arriveByLocal: '',
  origin: '',
  destination: '',
  studentCount: null,
  notes: '',
})

const locked = computed(() => row.passengerDepartLocked(props.req))
const canSubmit = computed(() => row.canSubmitStudentCount(props.req))

function toLocalInput(iso) {
  if (!iso) return ''
  try {
    const d = new Date(iso)
    const pad = (n) => String(n).padStart(2, '0')
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
  } catch {
    return ''
  }
}

function fromLocalInput(local) {
  if (!local) return null
  try {
    return new Date(local).toISOString()
  } catch {
    return null
  }
}

function syncFromReq(r) {
  draft.departAtLocal = toLocalInput(r.depart_at)
  draft.arriveByLocal = toLocalInput(r.arrive_by)
  draft.origin = r.origin || ''
  draft.destination = r.destination || ''
  draft.studentCount =
    r.student_count_actual != null
      ? Number(r.student_count_actual)
      : r.passenger_count != null
        ? Number(r.passenger_count)
        : null
  draft.notes = r.notes || ''
}

watch(
  () => props.req,
  (r) => {
    if (r) syncFromReq(r)
  },
  { immediate: true, deep: true },
)

function buildPayload() {
  const payload = {
    origin: draft.origin.trim(),
    destination: draft.destination.trim(),
    notes: draft.notes,
  }
  const dep = fromLocalInput(draft.departAtLocal)
  const arr = fromLocalInput(draft.arriveByLocal)
  if (dep) payload.depart_at = dep
  if (arr) payload.arrive_by = arr
  if (draft.studentCount != null && draft.studentCount !== '') {
    payload.student_count_actual = Math.round(Number(draft.studentCount))
  }
  return payload
}

async function save() {
  if (locked.value || saving.value) return
  saving.value = true
  formError.value = ''
  try {
    await patchPortalRecurringInstance(props.req.id, buildPayload())
    emit('saved')
  } catch (e) {
    formError.value = formatApiError(e, t('portal.extracurricular_table.save_fail'))
  } finally {
    saving.value = false
  }
}

async function submitToDispatch() {
  if (!canSubmit.value || locked.value || submitting.value) return
  const n = Math.round(Number(draft.studentCount) || 0)
  const ok = await confirmAction({
    title: t('portal.extracurricular_table.submit_confirm_title'),
    message: t('portal.extracurricular_table.submit_confirm_message', { count: n }),
    confirmLabel: t('portal.extracurricular_table.submit_dispatch'),
  })
  if (!ok) return
  submitting.value = true
  formError.value = ''
  try {
    await patchPortalRecurringInstance(props.req.id, buildPayload())
    await submitPortalRecurringInstance(props.req.id)
    emit('saved')
  } catch (e) {
    formError.value = formatApiError(e, t('portal.extracurricular_table.submit_fail'))
  } finally {
    submitting.value = false
  }
}
</script>
