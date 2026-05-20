<template>
  <section
    ref="rootEl"
    class="overflow-hidden rounded-2xl border border-slate-300 bg-white shadow-md ring-1 ring-slate-900/5"
  >
    <div class="border-b-2 border-slate-800 bg-slate-50 px-4 py-5 sm:px-6">
      <div class="flex flex-wrap items-start gap-4">
        <div class="min-w-0 flex-1 text-center sm:text-left">
          <h2 class="text-lg font-bold uppercase tracking-tight text-slate-900">{{ t('portal.recurring_edit.bm03_title') }}</h2>
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
          <Bm03FormField
            v-model="draft.requesterName"
            field-id="bm03-a1-name"
            :label="t('portal.recurring_edit.bm03_fields.a1_name')"
            :placeholder="t('portal.recurring_edit.bm03_fields.a1_name_ph')"
            :hint="t('portal.recurring_edit.bm03_fields.a1_name_hint')"
            :error="fieldErrors.requesterName"
            autocomplete="name"
            :disabled="!canEditForm"
          />
          <Bm03FormField
            v-model="draft.requesterEmail"
            field-id="bm03-a2-email"
            :label="t('portal.recurring_edit.bm03_fields.a2_email')"
            :placeholder="t('portal.recurring_edit.bm03_fields.a2_email_ph')"
            :hint="t('portal.recurring_edit.bm03_fields.a2_email_hint')"
            :error="fieldErrors.requesterEmail"
            type="email"
            autocomplete="email"
            :disabled="!canEditForm"
          />
          <Bm03FormField
            v-model="draft.requesterPhone"
            field-id="bm03-a3-phone"
            :label="t('portal.recurring_edit.bm03_fields.a3_phone')"
            :placeholder="t('portal.recurring_edit.bm03_fields.a3_phone_ph')"
            :hint="t('portal.recurring_edit.bm03_fields.a3_phone_hint')"
            :error="fieldErrors.requesterPhone"
            type="tel"
            autocomplete="tel"
            inputmode="tel"
            :disabled="!canEditForm"
          />
          <Bm03FormField
            v-model="draft.requesterUnit"
            field-id="bm03-a4-unit"
            :label="t('portal.recurring_edit.bm03_fields.a4_unit')"
            :placeholder="t('portal.recurring_edit.bm03_fields.a4_unit_ph')"
            :hint="t('portal.recurring_edit.bm03_fields.a4_unit_hint')"
            :error="fieldErrors.requesterUnit"
            autocomplete="organization"
            :disabled="!canEditForm"
          />
        </div>
      </div>

      <!-- B -->
      <div class="bg-white">
        <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-100 px-4 py-2.5">
          <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-slate-800 text-xs font-bold text-white">B</span>
          <span class="text-xs font-bold uppercase tracking-wide text-slate-800">{{ t('portal.recurring_edit.bm03_sec_b') }}</span>
        </div>
        <div class="space-y-4 px-4 py-4 sm:px-5">
          <Bm03FormField
            v-model="draft.purpose"
            field-id="bm03-b1-purpose"
            :label="t('portal.recurring_edit.bm03_fields.b1_purpose')"
            :placeholder="t('portal.recurring_edit.bm03_fields.b1_purpose_ph')"
            :hint="t('portal.recurring_edit.bm03_fields.b1_purpose_hint')"
            :error="fieldErrors.purpose"
            multiline
            :rows="4"
            autocomplete="off"
            :disabled="!canEditForm"
          />
          <div>
            <div class="flex flex-wrap items-center gap-1.5">
              <span class="text-[11px] font-bold uppercase tracking-wide text-slate-600">
                {{ t('portal.recurring_edit.bm03_fields.b2_basis') }}
              </span>
              <span
                class="inline-flex cursor-help text-slate-400 hover:text-slate-600"
                :title="t('dispatch_wizard.create.basis_title')"
              >
                <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
              </span>
            </div>
            <p id="bm03-b2-hint" class="mt-0.5 text-xs text-slate-500">{{ t('portal.recurring_edit.bm03_fields.b2_basis_hint') }}</p>
            <div
              class="mt-2 flex min-h-[7.5rem] flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-6 transition sm:min-h-[8rem]"
              :class="
                canEditForm
                  ? basisDragOver
                    ? 'cursor-pointer border-teal-500 bg-teal-50/50'
                    : 'cursor-pointer border-slate-200 bg-slate-50/80 hover:border-slate-300 hover:bg-slate-50'
                  : 'cursor-not-allowed border-slate-100 bg-slate-50 opacity-70'
              "
              role="button"
              tabindex="0"
              :aria-disabled="!canEditForm"
              :aria-describedby="'bm03-b2-hint' + (fieldErrors.basis ? ' bm03-b2-err' : '')"
              @keydown.enter.prevent="canEditForm && basisFileInput?.click()"
              @keydown.space.prevent="canEditForm && basisFileInput?.click()"
              @dragover.prevent="canEditForm && (basisDragOver = true)"
              @dragleave.prevent="basisDragOver = false"
              @drop.prevent="onBasisDrop"
              @click="canEditForm && basisFileInput?.click()"
            >
              <CloudArrowUpIcon class="h-9 w-9 text-slate-400" aria-hidden="true" />
              <p class="mt-2 text-center text-sm font-medium text-slate-800">{{ t('dispatch_wizard.create.basis_drop') }}</p>
              <p class="mt-1 text-center text-xs text-slate-500">{{ t('dispatch_wizard.create.basis_types') }}</p>
              <input
                ref="basisFileInput"
                type="file"
                class="sr-only"
                accept=".pdf,.jpg,.jpeg,.png,.webp,image/*,application/pdf"
                :disabled="!canEditForm"
                @change="onBasisFileChange"
              />
            </div>
            <p v-if="fieldErrors.basis" id="bm03-b2-err" class="mt-2 text-xs font-medium text-rose-700" role="alert">
              {{ fieldErrors.basis }}
            </p>
            <p v-if="basisFileError" class="mt-2 text-xs font-medium text-rose-600" role="alert">{{ basisFileError }}</p>
            <div
              v-if="basisDisplayName"
              class="mt-3 flex flex-wrap items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm"
            >
              <PaperClipIcon class="h-5 w-5 shrink-0 text-teal-800" aria-hidden="true" />
              <div class="min-w-0 flex-1">
                <div class="truncate font-medium text-slate-900">{{ basisDisplayName }}</div>
                <div v-if="basisDisplaySize" class="text-xs text-slate-500">{{ basisDisplaySize }}</div>
              </div>
              <button
                v-if="existingBasis && !basisPendingFile"
                type="button"
                class="shrink-0 rounded-lg border border-slate-200 px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                @click="downloadExistingBasis"
              >
                {{ t('portal.recurring_edit.bm03_fields.b2_download') }}
              </button>
              <button
                v-if="canEditForm && (basisPendingFile || existingBasis)"
                type="button"
                class="shrink-0 rounded-lg px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                @click="clearBasisSelection"
              >
                {{ t('dispatch_wizard.create.remove_file') }}
              </button>
            </div>
            <div
              v-if="basisPreviewUrl"
              class="mt-3 overflow-hidden rounded-lg border border-slate-200 bg-slate-50 p-2"
            >
              <p class="mb-2 text-[11px] font-semibold uppercase text-slate-500">{{ t('portal.recurring_edit.bm03_fields.b2_preview') }}</p>
              <img :src="basisPreviewUrl" alt="" class="mx-auto max-h-48 max-w-full rounded object-contain" />
            </div>
            <p v-else-if="existingBasis && isPdfBasis" class="mt-2 text-xs text-slate-600">
              {{ t('portal.recurring_edit.bm03_fields.b2_pdf_no_preview') }}
            </p>
          </div>
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
            <Bm03FormField
              v-model="draft.proposedDate"
              field-id="bm03-c1-proposed"
              :label="t('portal.recurring_edit.bm03_fields.c1_proposed')"
              :hint="t('portal.recurring_edit.bm03_fields.c1_proposed_hint')"
              :error="fieldErrors.proposedDate"
              type="date"
              :disabled="!canEditForm"
            />
            <Bm03FormField
              v-model="draft.dateNeeded"
              field-id="bm03-c2-needed"
              :label="t('portal.recurring_edit.bm03_fields.c2_needed')"
              :hint="t('portal.recurring_edit.bm03_fields.c2_needed_hint')"
              :error="fieldErrors.dateNeeded"
              type="date"
              :disabled="!canEditForm"
            />
          </div>
          <p class="rounded border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
            {{ t('portal.recurring_edit.bm03_time_note') }}
          </p>
          <div class="grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
            <Bm03FormField
              v-model="draft.departAtLocal"
              field-id="bm03-depart"
              :label="t('portal.recurring_edit.depart_at')"
              :hint="t('portal.recurring_edit.bm03_fields.depart_hint')"
              :error="fieldErrors.departAtLocal"
              type="datetime-local"
              :disabled="!canEditForm"
            />
            <Bm03FormField
              v-model="draft.arriveByLocal"
              field-id="bm03-arrive"
              :label="t('portal.recurring_edit.arrive_by')"
              :hint="t('portal.recurring_edit.bm03_fields.arrive_hint')"
              :error="fieldErrors.arriveByLocal"
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
          <p class="mb-1 text-[11px] font-bold uppercase text-slate-700">d.1 Đối tượng sử dụng</p>
          <p class="mb-3 text-xs text-slate-500">{{ t('portal.recurring_edit.bm03_fields.d1_targets_hint') }}</p>
          <p v-if="fieldErrors.targets" class="mb-2 text-xs font-medium text-rose-700" role="alert">{{ fieldErrors.targets }}</p>
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
            <Bm03FormField
              v-model="draft.coordinatorName"
              field-id="bm03-d2-name"
              :label="t('portal.recurring_edit.bm03_fields.d2_coord_name')"
              :placeholder="t('portal.recurring_edit.bm03_fields.d2_coord_name_ph')"
              :hint="t('portal.recurring_edit.bm03_fields.d2_coord_hint')"
              :error="fieldErrors.coordinatorName"
              autocomplete="name"
              :disabled="!canEditForm"
            />
            <Bm03FormField
              v-model="draft.coordinatorEmail"
              field-id="bm03-d2-email"
              :label="t('portal.recurring_edit.bm03_fields.d2_coord_email_label')"
              :placeholder="t('portal.recurring_edit.bm03_fields.d2_coord_email_ph')"
              :hint="t('portal.recurring_edit.bm03_fields.d2_coord_hint')"
              :error="fieldErrors.coordinatorEmail"
              type="email"
              autocomplete="email"
              :disabled="!canEditForm"
            />
            <Bm03FormField
              v-model="draft.coordinatorPhone"
              field-id="bm03-d2-phone"
              :label="t('portal.recurring_edit.bm03_fields.d2_coord_phone_label')"
              :placeholder="t('portal.recurring_edit.bm03_fields.d2_coord_phone_ph')"
              :error="fieldErrors.coordinatorPhone"
              type="tel"
              autocomplete="tel"
              inputmode="tel"
              :disabled="!canEditForm"
            />
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
          <Bm03FormField
            v-model="draft.origin"
            field-id="bm03-e-pickup"
            :label="t('portal.recurring_edit.bm03_fields.e_pickup')"
            :placeholder="t('portal.recurring_edit.bm03_fields.e_pickup_ph')"
            :hint="t('portal.recurring_edit.bm03_fields.e_route_hint')"
            :error="fieldErrors.origin"
            autocomplete="off"
            :disabled="!canEditForm"
          />
          <Bm03FormField
            v-model="draft.destination"
            field-id="bm03-e-dropoff"
            :label="t('portal.recurring_edit.bm03_fields.e_dropoff')"
            :placeholder="t('portal.recurring_edit.bm03_fields.e_dropoff_ph')"
            :error="fieldErrors.destination"
            autocomplete="off"
            :disabled="!canEditForm"
          />
        </div>
      </div>

    </div>

    <p v-if="formError" class="border-t border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-700 sm:px-6" role="alert">
      {{ formError }}
    </p>

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
import { computed, onUnmounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { CloudArrowUpIcon, InformationCircleIcon, PaperClipIcon } from '@heroicons/vue/24/outline'
import Bm03FormField from './Bm03FormField.vue'
import {
  patchPortalRecurringInstance,
  submitPortalRecurringInstance,
  uploadPortalProposalBasis,
  downloadPortalAttachmentBlob,
} from '../../../api/requests'
import { formatApiError } from '../../../api/http'
import { useAuthStore } from '../../../store'
import { useExtracurricularRequestRow } from '../../../composables/useExtracurricularRequestRow'
import { TARGET_OPTIONS } from '../../../composables/dispatchWizardConstants'
import { labelTripType } from '../../../util/labels'
import { confirmAction } from '../../../composables/useConfirm'
import { saveAs } from 'file-saver'

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
const BASIS_MAX = 10 * 1024 * 1024
const BASIS_MIME = new Set(['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
const BASIS_EXT = new Set(['.pdf', '.jpg', '.jpeg', '.png', '.webp'])

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
const fieldErrors = reactive({})
const basisFileInput = ref(null)
const basisPendingFile = ref(null)
const basisDragOver = ref(false)
const basisFileError = ref('')
const basisPreviewUrl = ref('')
let basisObjectUrl = ''

const draft = reactive({
  requesterName: '',
  requesterEmail: '',
  requesterPhone: '',
  requesterUnit: '',
  purpose: '',
  proposedDate: '',
  dateNeeded: '',
  departAtLocal: '',
  arriveByLocal: '',
  targets: [],
  coordinatorName: '',
  coordinatorEmail: '',
  coordinatorPhone: '',
  origin: '',
  destination: '',
})

const existingBasis = computed(() => {
  const list = props.req?.attachments ?? []
  return list.find((a) => a.kind === 'proposal_basis') || null
})

const basisDisplayName = computed(() => {
  if (basisPendingFile.value) return basisPendingFile.value.name
  if (existingBasis.value?.original_name) return existingBasis.value.original_name
  const snap = props.req?.wizard_snapshot?.form
  return snap?.basisFileName || ''
})

const basisDisplaySize = computed(() => {
  if (basisPendingFile.value?.size != null) return formatFileSize(basisPendingFile.value.size)
  if (existingBasis.value?.size_bytes != null) return formatFileSize(existingBasis.value.size_bytes)
  return ''
})

const isPdfBasis = computed(() => {
  const mime = basisPendingFile.value?.type || existingBasis.value?.mime_type || ''
  const name = (basisDisplayName.value || '').toLowerCase()
  return mime === 'application/pdf' || name.endsWith('.pdf')
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

function formatFileSize(n) {
  const b = Number(n)
  if (!Number.isFinite(b) || b < 0) return ''
  if (b < 1024) return `${b} B`
  if (b < 1024 * 1024) return `${(b / 1024).toFixed(1)} KB`
  return `${(b / (1024 * 1024)).toFixed(1)} MB`
}

function isAllowedBasisFile(f) {
  if (!f) return false
  const type = String(f.type || '').toLowerCase()
  if (type && BASIS_MIME.has(type)) return true
  const name = String(f.name || '')
  const dot = name.lastIndexOf('.')
  if (dot < 0) return false
  return BASIS_EXT.has(name.slice(dot).toLowerCase())
}

function revokeBasisPreview() {
  if (basisObjectUrl) {
    URL.revokeObjectURL(basisObjectUrl)
    basisObjectUrl = ''
  }
  basisPreviewUrl.value = ''
}

function updateBasisPreview(file) {
  revokeBasisPreview()
  if (!file || !String(file.type || '').startsWith('image/')) return
  basisObjectUrl = URL.createObjectURL(file)
  basisPreviewUrl.value = basisObjectUrl
}

function onBasisFileChange(e) {
  basisFileError.value = ''
  fieldErrors.basis = ''
  const f = e?.target?.files?.[0]
  if (!f) return
  if (f.size > BASIS_MAX) {
    basisPendingFile.value = null
    basisFileError.value = t('dispatch_wizard.file_too_large')
    if (basisFileInput.value) basisFileInput.value.value = ''
    revokeBasisPreview()
    return
  }
  if (!isAllowedBasisFile(f)) {
    basisPendingFile.value = null
    basisFileError.value = t('dispatch_wizard.file_type_not_allowed')
    if (basisFileInput.value) basisFileInput.value.value = ''
    revokeBasisPreview()
    return
  }
  basisPendingFile.value = f
  updateBasisPreview(f)
}

function onBasisDrop(e) {
  if (!canEditForm.value) return
  basisDragOver.value = false
  basisFileError.value = ''
  fieldErrors.basis = ''
  const f = e?.dataTransfer?.files?.[0]
  if (!f) return
  if (f.size > BASIS_MAX) {
    basisFileError.value = t('dispatch_wizard.file_too_large')
    return
  }
  if (!isAllowedBasisFile(f)) {
    basisFileError.value = t('dispatch_wizard.file_type_not_allowed')
    return
  }
  basisPendingFile.value = f
  updateBasisPreview(f)
}

function clearBasisSelection() {
  basisPendingFile.value = null
  basisFileError.value = ''
  fieldErrors.basis = ''
  if (basisFileInput.value) basisFileInput.value.value = ''
  revokeBasisPreview()
}

async function downloadExistingBasis() {
  const att = existingBasis.value
  if (!att?.id) return
  try {
    const blob = await downloadPortalAttachmentBlob(props.req.id, att.id)
    saveAs(blob, att.original_name || `basis-${att.id}`)
  } catch (e) {
    formError.value = formatApiError(e, t('portal.recurring_edit.bm03_fields.b2_download_fail'))
  }
}

function clearFieldErrors() {
  for (const k of Object.keys(fieldErrors)) delete fieldErrors[k]
}

function validateClient() {
  clearFieldErrors()
  let ok = true
  if (!draft.requesterName.trim()) {
    fieldErrors.requesterName = t('portal.recurring_edit.bm03_err.required')
    ok = false
  }
  const email = draft.requesterEmail.trim()
  if (!email) {
    fieldErrors.requesterEmail = t('portal.recurring_edit.bm03_err.required')
    ok = false
  } else if (!EMAIL_RE.test(email)) {
    fieldErrors.requesterEmail = t('portal.recurring_edit.bm03_err.email')
    ok = false
  }
  if (!draft.requesterPhone.trim()) {
    fieldErrors.requesterPhone = t('portal.recurring_edit.bm03_err.required')
    ok = false
  }
  if (!draft.requesterUnit.trim()) {
    fieldErrors.requesterUnit = t('portal.recurring_edit.bm03_err.required')
    ok = false
  }
  if (!draft.purpose.trim()) {
    fieldErrors.purpose = t('portal.recurring_edit.bm03_err.required')
    ok = false
  }
  if (!basisPendingFile.value && !existingBasis.value) {
    fieldErrors.basis = t('portal.recurring_edit.bm03_err.basis_required')
    ok = false
  }
  if (!draft.proposedDate) {
    fieldErrors.proposedDate = t('portal.recurring_edit.bm03_err.required')
    ok = false
  }
  if (!draft.dateNeeded) {
    fieldErrors.dateNeeded = t('portal.recurring_edit.bm03_err.required')
    ok = false
  }
  if (!draft.departAtLocal) {
    fieldErrors.departAtLocal = t('portal.recurring_edit.bm03_err.required')
    ok = false
  }
  if (!draft.arriveByLocal) {
    fieldErrors.arriveByLocal = t('portal.recurring_edit.bm03_err.required')
    ok = false
  } else if (draft.departAtLocal && draft.arriveByLocal) {
    try {
      if (new Date(draft.arriveByLocal) < new Date(draft.departAtLocal)) {
        fieldErrors.arriveByLocal = t('portal.recurring_edit.bm03_err.arrive_before_depart')
        ok = false
      }
    } catch {
      /* ignore */
    }
  }
  if (!draft.targets.length) {
    fieldErrors.targets = t('portal.recurring_edit.bm03_err.targets')
    ok = false
  }
  const coordEmail = draft.coordinatorEmail.trim()
  if (coordEmail && !EMAIL_RE.test(coordEmail)) {
    fieldErrors.coordinatorEmail = t('portal.recurring_edit.bm03_err.coord_email')
    ok = false
  }
  if (!draft.origin.trim()) {
    fieldErrors.origin = t('portal.recurring_edit.bm03_err.required')
    ok = false
  }
  if (!draft.destination.trim()) {
    fieldErrors.destination = t('portal.recurring_edit.bm03_err.required')
    ok = false
  }
  if (!ok) {
    formError.value = t('portal.recurring_edit.bm03_err.summary')
  }
  return ok
}

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
  basisPendingFile.value = null
  if (basisFileInput.value) basisFileInput.value.value = ''
  revokeBasisPreview()
}

watch(
  () => props.req,
  (r) => {
    if (r) syncFromReq(r)
  },
  { immediate: true, deep: true },
)

watch(existingBasis, async (att) => {
  if (basisPendingFile.value || !att?.id) return
  const mime = String(att.mime_type || '')
  if (!mime.startsWith('image/')) return
  try {
    const blob = await downloadPortalAttachmentBlob(props.req.id, att.id)
    revokeBasisPreview()
    basisObjectUrl = URL.createObjectURL(blob)
    basisPreviewUrl.value = basisObjectUrl
  } catch {
    /* preview optional */
  }
})

onUnmounted(() => revokeBasisPreview())

function buildPayload() {
  const dep = fromLocalInput(draft.departAtLocal)
  const arr = fromLocalInput(draft.arriveByLocal)
  const snapForm = {
    requester_name: draft.requesterName.trim(),
    requester_email: draft.requesterEmail.trim(),
    requester_phone: draft.requesterPhone.trim(),
    requester_unit: draft.requesterUnit.trim(),
    purpose: draft.purpose.trim(),
    basis_ref: '',
    proposed_date: draft.proposedDate || null,
    date_needed: draft.dateNeeded || null,
    targets: [...draft.targets],
    coordinator_name: draft.coordinatorName.trim(),
    coordinator_email: draft.coordinatorEmail.trim(),
    coordinator_phone: draft.coordinatorPhone.trim(),
    point_purpose_kind: 'extracurricular',
  }
  if (existingBasis.value?.original_name) {
    snapForm.basisFileName = existingBasis.value.original_name
  } else if (basisPendingFile.value?.name) {
    snapForm.basisFileName = basisPendingFile.value.name
  }
  const payload = {
    origin: draft.origin.trim(),
    destination: draft.destination.trim(),
    wizard_snapshot: { form: snapForm },
  }
  if (dep) payload.depart_at = dep
  if (arr) payload.arrive_by = arr
  return payload
}

async function uploadBasisIfNeeded() {
  if (!basisPendingFile.value) return
  await uploadPortalProposalBasis(props.req.id, basisPendingFile.value)
  basisPendingFile.value = null
  if (basisFileInput.value) basisFileInput.value.value = ''
}

async function save() {
  if (!canEditForm.value || saving.value) return
  if (!validateClient()) return
  saving.value = true
  formError.value = ''
  try {
    await uploadBasisIfNeeded()
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
  if (!validateClient()) return
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
    await uploadBasisIfNeeded()
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
