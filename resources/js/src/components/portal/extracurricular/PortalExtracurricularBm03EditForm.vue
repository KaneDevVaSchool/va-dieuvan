<template>
  <section
    ref="rootEl"
    class="overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-md ring-1 ring-slate-900/5"
  >
    <div class="border-b-2 border-slate-800 bg-slate-50 px-4 py-5 sm:px-6">
      <div class="flex flex-wrap items-start gap-4">
        <div class="min-w-0 flex-1 text-center sm:text-left">
          <h2 class="text-lg font-bold uppercase tracking-tight text-slate-900">Đề Nghị Điều Vận</h2>
          <p class="mt-1 text-sm font-medium text-slate-600">{{ tripSubtitle }}</p>
        </div>
        <div
          class="grid w-full max-w-[14rem] shrink-0 gap-px overflow-hidden rounded border-2 border-slate-400 text-[11px] sm:mx-0"
        >
          <div class="grid grid-cols-2 bg-white">
            <span class="border-b border-r border-slate-300 px-2 py-1.5 font-bold text-slate-600">Ký hiệu</span>
            <span class="border-b border-slate-300 px-2 py-1.5 text-right font-medium">BM.03/MH.QT.04</span>
          </div>
          <div class="grid grid-cols-2 bg-white">
            <span class="border-r border-slate-300 px-2 py-1.5 font-bold text-slate-600">Mã yêu cầu</span>
            <span class="px-2 py-1.5 text-right font-bold text-slate-900">#{{ req.id }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="divide-y divide-slate-200">
      <!-- A -->
      <div class="bg-white">
        <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-100 px-4 py-2.5">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-slate-800 text-xs font-bold text-white">A</span>
          <span class="text-xs font-bold uppercase tracking-wide text-slate-800">{{ t('portal.recurring_edit.bm03_sec_a') }}</span>
        </div>
        <div class="grid gap-4 px-4 py-4 sm:grid-cols-2 sm:px-5">
          <Bm03Editable label="a.1 Họ và tên" v-model="draft.requesterName" :disabled="!canEditForm" />
          <Bm03Editable label="a.2 Email VA của nhân viên" v-model="draft.requesterEmail" :disabled="!canEditForm" />
          <Bm03Editable label="a.3 Số điện thoại" v-model="draft.requesterPhone" :disabled="!canEditForm" />
          <Bm03Editable label="a.4 Đơn vị" v-model="draft.requesterUnit" :disabled="!canEditForm" />
        </div>
      </div>

      <!-- B -->
      <div class="bg-white">
        <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-100 px-4 py-2.5">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-slate-800 text-xs font-bold text-white">B</span>
          <span class="text-xs font-bold uppercase tracking-wide text-slate-800">{{ t('portal.recurring_edit.bm03_sec_b') }}</span>
        </div>
        <div class="space-y-4 px-4 py-4 sm:px-5">
          <Bm03Editable label="b.1 Mục đích sử dụng" v-model="draft.purpose" multiline :disabled="!canEditForm" />
          <Bm03Editable label="b.2 Căn cứ đề xuất" v-model="draft.basisRef" multiline :disabled="!canEditForm" />
        </div>
      </div>

      <!-- C -->
      <div class="bg-white">
        <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-100 px-4 py-2.5">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-slate-800 text-xs font-bold text-white">C</span>
          <span class="text-xs font-bold uppercase tracking-wide text-slate-800">{{ t('portal.recurring_edit.bm03_sec_c') }}</span>
        </div>
        <div class="space-y-4 px-4 py-4 sm:px-5">
          <div class="grid gap-4 sm:grid-cols-2">
            <Bm03Editable label="c.1 Ngày đề xuất" v-model="draft.proposedDate" type="date" :disabled="!canEditForm" />
            <Bm03Editable label="c.2 Ngày cần sử dụng xe" v-model="draft.dateNeeded" type="date" :disabled="!canEditForm" />
          </div>
          <p class="rounded border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
            {{ t('portal.recurring_edit.bm03_time_note') }}
          </p>
          <div class="grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
            <Bm03Editable
              :label="t('portal.recurring_edit.depart_at')"
              v-model="draft.departAtLocal"
              type="datetime-local"
              :disabled="!canEditForm"
            />
            <Bm03Editable
              :label="t('portal.recurring_edit.arrive_by')"
              v-model="draft.arriveByLocal"
              type="datetime-local"
              :disabled="!canEditForm"
            />
          </div>
          <p class="text-sm text-slate-800">
            <span class="text-xs font-bold uppercase text-slate-600">{{ t('portal.recurring_edit.bm03_trip_type') }}:</span>
            {{ tripTypeDisplay }}
          </p>
        </div>
      </div>

      <!-- D -->
      <div class="bg-white">
        <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-100 px-4 py-2.5">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-slate-800 text-xs font-bold text-white">D</span>
          <span class="text-xs font-bold uppercase tracking-wide text-slate-800">{{ t('portal.recurring_edit.bm03_sec_d') }}</span>
        </div>
        <div class="px-4 py-4 sm:px-5">
          <p class="mb-3 text-[11px] font-bold uppercase text-slate-700">d.1 Đối tượng sử dụng</p>
          <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <label
              v-for="opt in TARGET_OPTIONS"
              :key="opt"
              class="flex cursor-pointer items-start gap-2 rounded border border-transparent px-1 py-1 text-xs hover:bg-slate-50"
            >
              <input
                v-model="draft.targets"
                type="checkbox"
                class="mt-0.5 h-4 w-4 rounded border-slate-400 text-slate-800"
                :value="opt"
                :disabled="!canEditForm"
              />
              <span>{{ opt }}</span>
            </label>
          </div>
          <div class="mt-4 grid gap-4 border-t border-slate-200 pt-4 sm:grid-cols-3">
            <Bm03Editable label="d.2 Họ tên" v-model="draft.coordinatorName" :disabled="!canEditForm" />
            <Bm03Editable label="Email" v-model="draft.coordinatorEmail" :disabled="!canEditForm" />
            <Bm03Editable label="SĐT" v-model="draft.coordinatorPhone" :disabled="!canEditForm" />
          </div>
        </div>
      </div>

      <!-- E -->
      <div class="bg-white">
        <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-100 px-4 py-2.5">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-slate-800 text-xs font-bold text-white">E</span>
          <span class="text-xs font-bold uppercase tracking-wide text-slate-800">{{ t('portal.recurring_edit.bm03_sec_e') }}</span>
        </div>
        <div class="space-y-4 px-4 py-4 sm:px-5">
          <Bm03Editable :label="t('dispatch_wizard.s3.pickup_ph')" v-model="draft.origin" :disabled="!canEditForm" />
          <Bm03Editable :label="t('dispatch_wizard.s3.dropoff_ph')" v-model="draft.destination" :disabled="!canEditForm" />
        </div>
      </div>
    </div>

    <p v-if="formError" class="border-t border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 sm:px-6">{{ formError }}</p>

    <p
      v-if="!canEditForm"
      class="border-t border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600 sm:px-6"
    >
      {{
        req.student_count_submitted_at
          ? t('portal.recurring_edit.bm03_submitted_readonly')
          : t('portal.recurring_edit.bm03_view_only')
      }}
    </p>

    <div
      v-else
      class="sticky bottom-0 flex flex-wrap gap-3 border-t-2 border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:px-6"
    >
      <button
        type="button"
        class="rounded-xl border-2 border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-800 hover:bg-slate-50 disabled:opacity-50"
        :disabled="saving"
        @click="save"
      >
        {{ saving ? t('portal.recurring_edit.save_draft_busy') : t('portal.recurring_edit.save_draft') }}
      </button>
      <button
        type="button"
        class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-800 disabled:opacity-50"
        :disabled="saving || submitting || !canSubmit"
        @click="submitToDispatch"
      >
        {{
          submitting
            ? t('portal.extracurricular_table.submit_dispatch_busy')
            : t('portal.extracurricular_table.submit_dispatch')
        }}
      </button>
      <p v-if="submitBlockedHint" class="self-center text-xs text-amber-800">{{ submitBlockedHint }}</p>
    </div>
  </section>
</template>

<script setup>
import { computed, defineComponent, h, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { patchPortalRecurringInstance, submitPortalRecurringInstance } from '../../../api/requests'
import { formatApiError } from '../../../api/http'
import { useAuthStore } from '../../../store'
import { useExtracurricularRequestRow } from '../../../composables/useExtracurricularRequestRow'
import { TARGET_OPTIONS } from '../../../composables/dispatchWizardConstants'
import { labelTripType } from '../../../util/labels'
import { confirmAction } from '../../../composables/useConfirm'

const Bm03Editable = defineComponent({
  name: 'Bm03Editable',
  props: {
    label: { type: String, required: true },
    modelValue: { type: [String, Number], default: '' },
    multiline: { type: Boolean, default: false },
    type: { type: String, default: 'text' },
    disabled: { type: Boolean, default: false },
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    return () =>
      h('label', { class: 'block min-w-0' }, [
        h(
          'span',
          { class: 'block text-[11px] font-bold uppercase tracking-wide text-slate-600' },
          props.label,
        ),
        props.multiline
          ? h('textarea', {
              rows: 3,
              class:
                'mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500/30 disabled:cursor-not-allowed disabled:bg-slate-100',
              value: props.modelValue,
              disabled: props.disabled,
              onInput: (e) => emit('update:modelValue', e.target.value),
            })
          : h('input', {
              type: props.type,
              class:
                'mt-1 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500/30 disabled:cursor-not-allowed disabled:bg-slate-100',
              value: props.modelValue,
              disabled: props.disabled,
              onInput: (e) => emit('update:modelValue', e.target.value),
            }),
      ])
  },
})

const props = defineProps({
  req: { type: Object, required: true },
})

const emit = defineEmits(['saved'])

const rootEl = ref(null)
defineExpose({ rootEl })

const { t } = useI18n()
const auth = useAuthStore()
const row = useExtracurricularRequestRow(auth, computed(() => auth.user))

const saving = ref(false)
const submitting = ref(false)
const formError = ref('')

const draft = reactive({
  requesterName: '',
  requesterEmail: '',
  requesterPhone: '',
  requesterUnit: '',
  purpose: '',
  basisRef: '',
  proposedDate: '',
  dateNeeded: '',
  departAtLocal: '',
  arriveAtLocal: '',
  targets: [],
  coordinatorName: '',
  coordinatorEmail: '',
  coordinatorPhone: '',
  origin: '',
  destination: '',
})

const tripTypeDisplay = computed(() => labelTripType(props.req?.trip_type) || '—')

const tripSubtitle = computed(() => {
  const tt = props.req?.trip_type
  if (tt === 'cargo') return '(Điều chuyển Hàng hóa)'
  if (tt === 'business') return '(Công tác)'
  if (tt === 'point_to_point') return '(Vận chuyển Điểm — Điểm)'
  return '(Đưa đón tận nơi)'
})

const canEditForm = computed(() => {
  const r = props.req
  if (!r || r.student_count_submitted_at) return false
  if (!row.isRequester(r)) return false
  return ['pending', 'price_filled'].includes(String(r.status || ''))
})

const canSubmit = computed(() => row.canSubmitStudentCount(props.req))

const submitBlockedHint = computed(() => {
  if (canSubmit.value || !canEditForm.value) return ''
  if (props.req?.student_count_actual == null || props.req?.student_count_actual === '') {
    return t('portal.extracurricular_table.submit_save_first_hint')
  }
  const k = row.lockHintKey(props.req)
  return k ? t(`portal.extracurricular_table.${k}`) : ''
})

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

function toDateInput(v) {
  if (!v) return ''
  const s = String(v).trim()
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) return s.slice(0, 10)
  try {
    const d = new Date(s)
    if (!Number.isNaN(d.getTime())) {
      const pad = (n) => String(n).padStart(2, '0')
      return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
    }
  } catch {
    /* ignore */
  }
  return ''
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
  const form = r?.wizard_snapshot?.form ?? {}
  draft.requesterName = form.requester_name || r?.requester?.name || ''
  draft.requesterEmail = form.requester_email || r?.requester?.email || ''
  draft.requesterPhone = form.requester_phone || r?.requester?.phone || ''
  draft.requesterUnit = form.requester_unit || ''
  draft.purpose = form.purpose || ''
  draft.basisRef = form.basis_ref || form.basisRef || ''
  draft.proposedDate = toDateInput(form.proposed_date)
  draft.dateNeeded = toDateInput(form.date_needed || r.depart_at)
  draft.departAtLocal = toLocalInput(r.depart_at)
  draft.arriveByLocal = toLocalInput(r.arrive_by)
  const raw = form.targets
  draft.targets = Array.isArray(raw) ? [...raw] : raw && typeof raw === 'object' ? Object.values(raw) : []
  draft.coordinatorName = form.coordinator_name || ''
  draft.coordinatorEmail = form.coordinator_email || ''
  draft.coordinatorPhone = form.coordinator_phone || ''
  draft.origin = r.origin || ''
  draft.destination = r.destination || ''
}

watch(
  () => props.req,
  (r) => {
    if (r) syncFromReq(r)
  },
  { immediate: true, deep: true },
)

function buildPayload() {
  const dep = fromLocalInput(draft.departAtLocal)
  const arr = fromLocalInput(draft.arriveByLocal)
  const payload = {
    origin: draft.origin.trim(),
    destination: draft.destination.trim(),
    wizard_snapshot: {
      form: {
        requester_name: draft.requesterName.trim(),
        requester_email: draft.requesterEmail.trim(),
        requester_phone: draft.requesterPhone.trim(),
        requester_unit: draft.requesterUnit.trim(),
        purpose: draft.purpose.trim(),
        basis_ref: draft.basisRef.trim(),
        proposed_date: draft.proposedDate || null,
        date_needed: draft.dateNeeded || null,
        targets: [...draft.targets],
        coordinator_name: draft.coordinatorName.trim(),
        coordinator_email: draft.coordinatorEmail.trim(),
        coordinator_phone: draft.coordinatorPhone.trim(),
        point_purpose_kind: 'extracurricular',
      },
    },
  }
  if (dep) payload.depart_at = dep
  if (arr) payload.arrive_by = arr
  return payload
}

async function save() {
  if (!canEditForm.value || saving.value) return
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
  if (!canEditForm.value || !canSubmit.value || submitting.value) return
  const n = row.actualStudentCount(props.req)
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
