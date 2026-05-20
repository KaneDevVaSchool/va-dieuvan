<template>
  <div class="mx-auto max-w-7xl px-4 pb-16 pt-6 sm:px-6 lg:pb-24">
    <PortalSuccessCard
      v-if="welcomeOpen"
      class="mb-6"
      :title="t('portal.welcome_banner_title')"
      :badge="t('portal.status_pending')"
      :request-id="Number(route.params.id)"
      :hint="t('portal.welcome_banner_hint')"
    >
      <template #actions>
        <button
          type="button"
          class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow hover:bg-slate-50"
          @click="welcomeOpen = false"
        >
          {{ t('portal.welcome_banner_dismiss') }}
        </button>
      </template>
    </PortalSuccessCard>

    <div v-if="loading" class="py-14 text-center text-sm text-slate-500">{{ t('portal.detail_loading') }}</div>

    <div v-else-if="detailError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
      {{ detailError }}
      <RouterLink :to="{ name: portalRoutes.home }" class="mt-3 block font-semibold text-indigo-700 underline">{{ t('portal.back_home') }}</RouterLink>
    </div>

    <template v-else-if="req">
      <div class="border-b border-slate-200/80 pb-6">
        <div class="flex min-w-0 items-start gap-3">
          <RouterLink
            :to="{ name: portalRoutes.list }"
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50/70 hover:text-indigo-700"
            :aria-label="t('portal.back_list')"
          >
            <ArrowLeftIcon class="h-5 w-5" aria-hidden="true" />
          </RouterLink>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <h1 class="font-mono text-lg font-bold text-slate-900 sm:text-xl">#{{ req.id }}</h1>
              <StatusBadge :status="req.status" />
              <span
                v-if="req.dispatch_request_template_id"
                class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-800"
              >
                {{ t('request_detail.badge_recurring') }}
              </span>
              <span
                v-if="req.is_urgent"
                class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700"
              >
                {{ t('portal.badge_urgent') }}
              </span>
            </div>
            <p class="mt-2 text-sm leading-relaxed text-slate-700">
              {{ routeSummary }}
            </p>
            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
              <span v-if="departFmt">{{ departFmt }}</span>
              <span v-if="tripTypeLabel">{{ tripTypeLabel }}</span>
              <span v-if="pollingRefreshing" class="font-medium text-teal-700">{{ t('portal.auto_refresh_indicator') }}</span>
            </div>
          </div>
        </div>
      </div>

      <PortalExtracurricularBm03EditForm
        v-if="showExtracurricularInstanceEdit"
        ref="bm03FormRef"
        class="mt-6"
        :req="req"
        @saved="load"
      />

      <PortalStatusTimeline
        v-if="!showExtracurricularInstanceEdit"
        class="mt-8"
        :title="t('portal.timeline_heading')"
        :steps="timelineSteps"
      />

      <section
        v-else-if="showRecurringExtras"
        class="mt-8 space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
      >
        <CostLimitAlert
          v-if="req.dispatch_package_cost_alert || req.dispatch_package_budget_alert"
          :alert="req.dispatch_package_cost_alert"
          :budget-alert="req.dispatch_package_budget_alert"
        />
        <ResetCloneSection v-if="showResetCloneBtn" :busy="resetCloneBusy" @clone="onResetCloneRequest" />
        <div
          v-if="showPassengerAdjustSection"
          :class="showResetCloneBtn ? 'border-t border-slate-100 pt-4' : ''"
        >
          <div class="mb-3 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-slate-500">{{ t('portal.extracurricular_table.col_tracking') }}</span>
            <StudentCountTrackingBadge
              v-if="req"
              :tracking-key="extracurricularRow.studentCountTrackingKey(req)"
              i18n-prefix="portal.extracurricular_table"
            />
          </div>
          <StudentCountField
            v-model:passenger-count="passengerDraft"
            :student-count-plan="req.passenger_count != null ? Number(req.passenger_count) : null"
            :locked="passengerDepartLocked"
            :is-dispatcher-override="false"
            :depart-at-formatted="departAtFormattedShort"
            :saving="passengerSaving"
            :error="passengerPatchErr"
            @save="savePassengerDraft"
          />
          <button
            v-if="canSubmitPassengerCount"
            type="button"
            class="mt-3 inline-flex min-h-[40px] items-center rounded-xl bg-violet-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-violet-500 disabled:opacity-50"
            :disabled="passengerSubmitting || passengerSaving"
            @click="submitPassengerCount"
          >
            {{
              passengerSubmitting
                ? t('portal.extracurricular_table.submit_dispatch_busy')
                : t('portal.extracurricular_table.submit_dispatch')
            }}
          </button>
        </div>
      </section>

      <section
        v-if="req.status === 'approved'"
        class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
      >
        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/80 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
          <div class="flex min-w-0 items-start gap-3">
            <PdfFileIcon class="mt-0.5 shrink-0" size-class="h-10 w-8" />
            <div class="min-w-0">
              <h2 class="text-sm font-bold text-slate-900 sm:text-base">{{ t('portal.pdf_preview_title') }}</h2>
              <p class="mt-1 text-xs leading-relaxed text-slate-600 sm:text-sm">{{ t('portal.pdf_preview_lead') }}</p>
            </div>
          </div>
          <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
            <button
              type="button"
              class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="!pdfBlobUrl"
              @click="openPdfInNewTab"
            >
              <ArrowTopRightOnSquareIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ t('portal.pdf_preview_open_tab') }}
            </button>
            <button
              type="button"
              class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-semibold text-teal-900 shadow-sm transition hover:bg-teal-100 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="pdfBusy || !pdfBlobUrl"
              @click="downloadPdf"
            >
              <DocumentArrowDownIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ t('portal.pdf_btn_label') }}
            </button>
          </div>
        </div>

        <div class="relative bg-slate-100 p-3 sm:p-4">
          <div
            v-if="pdfPreviewLoading"
            class="flex min-h-[min(75vh,640px)] flex-col items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white py-16 text-sm text-slate-600"
          >
            <span
              class="h-9 w-9 animate-spin rounded-full border-2 border-teal-500/30 border-t-teal-700"
              aria-hidden="true"
            />
            {{ t('portal.pdf_export_loading') }}
          </div>
          <div
            v-else-if="pdfPreviewError"
            class="flex min-h-[min(40vh,320px)] flex-col items-center justify-center gap-3 rounded-xl border border-rose-200 bg-rose-50/80 px-4 py-10 text-center"
          >
            <p class="max-w-md text-sm text-rose-800">{{ pdfPreviewError }}</p>
            <button
              type="button"
              class="rounded-xl border border-rose-200 bg-white px-4 py-2 text-xs font-semibold text-rose-900 shadow-sm hover:bg-rose-50"
              @click="retryPdfPreview"
            >
              {{ t('portal.pdf_preview_retry') }}
            </button>
          </div>
          <div
            v-else-if="pdfBlobUrl"
            class="overflow-hidden rounded-xl border border-slate-300/90 bg-white shadow-inner ring-1 ring-slate-900/5"
          >
            <iframe
              :src="pdfIframeSrc"
              class="block h-[min(75vh,900px)] w-full border-0 bg-white"
              :title="t('portal.pdf_preview_iframe_title')"
            />
          </div>
        </div>
      </section>

      <div
        v-if="req.status === 'rejected' && req.rejection_reason"
        class="mt-8 rounded-2xl border-2 border-rose-300 bg-rose-50 px-5 py-4 shadow-sm"
      >
        <div class="flex items-start gap-3">
          <XCircleIcon class="h-8 w-8 shrink-0 text-rose-600" aria-hidden="true" />
          <div class="min-w-0 flex-1">
            <p class="font-semibold text-rose-950">{{ t('portal.rejected_title') }}</p>
            <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-rose-900/95">{{ req.rejection_reason }}</p>
            <button
              type="button"
              class="mt-4 inline-flex min-h-[40px] items-center gap-2 rounded-xl border border-rose-200 bg-white px-4 text-xs font-semibold text-rose-900 shadow-sm hover:bg-rose-50"
              @click="copyRejectionReason"
            >
              <ClipboardDocumentIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ copyRejectionFeedback ? t('portal.copied') : t('portal.copy_rejection') }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="!showExtracurricularInstanceEdit" class="mt-8 w-full space-y-6">
        <PortalSignedDocUpload
          v-if="showSignedSection"
          :attachments="signedPaperAttachments"
          :upload-component-key="`portal-signed-${req.id}-${uploadKeySeed}`"
          :upload-fn="uploadSignedPaper"
          :error="signedUploadErr"
          @download="downloadAttachment"
          @uploaded="onSignedUploaded"
        />
        <template v-else>
          <PortalStatusHint :req="req" />
          <p class="text-sm leading-relaxed text-slate-600 sm:text-base">{{ t('portal.signed_gate_hint') }}</p>
        </template>

        <p class="text-sm font-medium text-slate-500 sm:text-base">
          {{ t('portal.detail_help_footer') }}
        </p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowLeftIcon, ArrowTopRightOnSquareIcon, ClipboardDocumentIcon, DocumentArrowDownIcon, XCircleIcon } from '@heroicons/vue/24/outline'
import { saveAs } from 'file-saver'
import {
  cloneDispatchRequest,
  downloadPortalAttachmentBlob,
  exportPortalDispatchRequestPdf,
  getPortalDispatchRequest,
  patchPassengerCount,
  submitStudentCount,
  uploadPortalSignedPaper,
} from '../../api/requests'
import { formatApiError } from '../../api/http'
import { useAuthStore } from '../../store'
import CostLimitAlert from '../../components/requests/CostLimitAlert.vue'
import ResetCloneSection from '../../components/requests/ResetCloneSection.vue'
import StudentCountField from '../../components/recurring/StudentCountField.vue'
import StudentCountTrackingBadge from '../../components/requests/extracurricular/StudentCountTrackingBadge.vue'
import { useExtracurricularRequestRow } from '../../composables/useExtracurricularRequestRow'
import { confirmAction } from '../../composables/useConfirm'
import { usePortalTimelineSteps } from '../../composables/usePortalTimelineSteps.js'
import { usePortalDetailPoll } from '../../composables/usePortalDetailPoll.js'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import PortalSuccessCard from '../../components/portal/PortalSuccessCard.vue'
import PortalStatusTimeline from '../../components/portal/PortalStatusTimeline.vue'
import PortalSignedDocUpload from '../../components/portal/PortalSignedDocUpload.vue'
import PortalStatusHint from '../../components/portal/PortalStatusHint.vue'
import PdfFileIcon from '../../components/icons/PdfFileIcon.vue'
import { usePortalExtracurricularModule } from '../../composables/usePortalExtracurricularModule'
import PortalExtracurricularBm03EditForm from '../../components/portal/extracurricular/PortalExtracurricularBm03EditForm.vue'

const route = useRoute()
const { routes: portalRoutes, isExtracurricularModule } = usePortalExtracurricularModule()
const router = useRouter()
const { t } = useI18n()
const auth = useAuthStore()

const loading = ref(true)
const detailError = ref('')
const req = ref(null)

const passengerDraft = ref(1)
const passengerSaving = ref(false)
const passengerSubmitting = ref(false)
const extracurricularRow = useExtracurricularRequestRow(auth, computed(() => auth.user))
const passengerPatchErr = ref('')
const resetCloneBusy = ref(false)
const bm03FormRef = ref(null)

const pdfBusy = ref(false)
const pdfBlobUrl = ref('')
const pdfPreviewLoading = ref(false)
const pdfPreviewError = ref('')
const signedUploadErr = ref('')
const uploadKeySeed = ref(0)
const welcomeOpen = ref(false)
const pollingRefreshing = ref(false)
const copyRejectionFeedback = ref(false)

let copyRejectionTimer = null

const timelineSteps = usePortalTimelineSteps(req, t)

function revokePdfPreviewUrl() {
  if (pdfBlobUrl.value && pdfBlobUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(pdfBlobUrl.value)
  }
  pdfBlobUrl.value = ''
  pdfPreviewLoading.value = false
  pdfPreviewError.value = ''
}

watch(
  () => route.params.id,
  () => {
    if (route.name === 'portalRequestDetail' || route.name === 'portalExtracurricularDetail') {
      welcomeOpen.value = false
      revokePdfPreviewUrl()
      load()
    }
  },
)

watch(
  () => req.value?.status,
  (s) => {
    if (s === 'approved') loadPdfPreview()
  },
)

async function load(opts = {}) {
  const silent = opts.silent === true
  if (!silent) {
    loading.value = true
    detailError.value = ''
  } else {
    pollingRefreshing.value = true
  }
  try {
    const id = Number(route.params.id)
    if (!Number.isFinite(id)) {
      if (!silent) {
        detailError.value = t('portal.detail_invalid_id')
        req.value = null
      }
      return
    }
    const data = await getPortalDispatchRequest(id)
    req.value = data
    const actual = data.student_count_actual ?? data.passenger_count
    passengerDraft.value = Math.max(1, Math.min(999, Math.round(Number(actual) || 1)))
    passengerPatchErr.value = ''
    if (!silent) detailError.value = ''
  } catch (e) {
    if (!silent) {
      detailError.value = formatApiError(e, t('portal.detail_load_fail'))
      req.value = null
    }
  } finally {
    if (!silent) loading.value = false
    else pollingRefreshing.value = false
  }
}

usePortalDetailPoll(load, req)

onMounted(async () => {
  await load()
  if (route.query.created === '1') {
    welcomeOpen.value = true
    const q = { ...route.query }
    delete q.created
    router.replace({ query: q })
  }
  if (String(route.query.operate) === '1' && showExtracurricularInstanceEdit.value) {
    scrollToBm03Form()
  }
})

onBeforeUnmount(() => {
  if (copyRejectionTimer) clearTimeout(copyRejectionTimer)
  revokePdfPreviewUrl()
})

const routeSummary = computed(() => {
  const r = req.value
  if (!r) return ''
  const o = (r.origin || '').trim()
  const d = (r.destination || '').trim()
  if (!o && !d) return t('portal.detail_no_locations')
  return `${o || '…'} → ${d || '…'}`
})

const departFmt = computed(() => {
  const r = req.value
  if (!r?.depart_at) return ''
  try {
    return new Date(r.depart_at).toLocaleString('vi-VN', {
      weekday: 'short',
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return ''
  }
})

const departAtFormattedShort = computed(() => {
  const r = req.value
  if (!r?.depart_at) return ''
  try {
    return new Date(r.depart_at).toLocaleString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return ''
  }
})

const isCurrentUserRequester = computed(
  () =>
    auth.user?.id != null &&
    req.value?.requester_id != null &&
    Number(auth.user.id) === Number(req.value.requester_id),
)

function hoursUntilDepartIso(iso) {
  if (!iso) return null
  try {
    const d = new Date(iso)
    if (Number.isNaN(d.getTime())) return null
    return (d.getTime() - Date.now()) / 3600000
  } catch {
    return null
  }
}

const passengerDepartLocked = computed(() => {
  if (!req.value) return true
  return extracurricularRow.passengerDepartLocked(req.value)
})

const canSubmitPassengerCount = computed(() => {
  if (!req.value) return false
  return extracurricularRow.canSubmitStudentCount(req.value)
})

function isExtracurricularReq(r) {
  if (!r) return false
  const snap = r?.wizard_snapshot
  const kind = snap?.form?.point_purpose_kind ?? snap?.point_purpose_kind
  if (kind === 'extracurricular') return true
  if (isExtracurricularModule.value && r.dispatch_request_template_id) return true
  return false
}

const showExtracurricularInstanceEdit = computed(() => {
  const r = req.value
  if (!r?.dispatch_request_template_id || !isExtracurricularReq(r)) return false
  if (!isCurrentUserRequester.value) return false
  if (r.student_count_submitted_at) return false
  return ['pending', 'price_filled'].includes(String(r?.status || ''))
})

function scrollToBm03Form() {
  nextTick(() => {
    const el = bm03FormRef.value?.rootEl
    if (el?.scrollIntoView) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  })
}

watch(
  () => route.query.operate,
  (v) => {
    if (String(v) === '1' && showExtracurricularInstanceEdit.value) {
      scrollToBm03Form()
    }
  },
)

watch(showExtracurricularInstanceEdit, (on) => {
  if (on && String(route.query.operate) === '1') {
    scrollToBm03Form()
  }
})

const showPassengerAdjustSection = computed(() => {
  if (showExtracurricularInstanceEdit.value) return false
  if (!req.value?.dispatch_request_template_id) return false
  return (
    isCurrentUserRequester.value &&
    ['pending', 'price_filled'].includes(String(req.value?.status || ''))
  )
})

const showResetCloneBtn = computed(() => {
  if (!isCurrentUserRequester.value || !auth.hasPermission('request.create')) return false
  return ['approved', 'rejected'].includes(String(req.value?.status || ''))
})

const showRecurringExtras = computed(() => {
  const r = req.value
  if (!r) return false
  return !!(
    r.dispatch_package_cost_alert ||
    r.dispatch_package_budget_alert ||
    showResetCloneBtn.value ||
    showPassengerAdjustSection.value
  )
})

async function onResetCloneRequest() {
  if (!req.value?.id || resetCloneBusy.value) return
  resetCloneBusy.value = true
  try {
    const dr = await cloneDispatchRequest(req.value.id)
    await router.push({ name: portalRoutes.value.create, query: { replace: String(dr.id) } })
  } catch (e) {
    window.alert(formatApiError(e, t('request_detail.reset_clone_fail')))
  } finally {
    resetCloneBusy.value = false
  }
}

async function savePassengerDraft() {
  if (!req.value?.id || passengerSaving.value || passengerDepartLocked.value) return
  passengerSaving.value = true
  passengerPatchErr.value = ''
  try {
    const n = Math.round(Number(passengerDraft.value))
    await patchPassengerCount(req.value.id, n)
    await load()
  } catch (e) {
    passengerPatchErr.value = formatApiError(e, t('request_detail.passenger_save_fail'))
  } finally {
    passengerSaving.value = false
  }
}

async function submitPassengerCount() {
  if (!req.value?.id || !canSubmitPassengerCount.value || passengerSubmitting.value) return
  const n = extracurricularRow.actualStudentCount(req.value)
  const ok = await confirmAction({
    title: t('portal.extracurricular_table.submit_confirm_title'),
    message: t('portal.extracurricular_table.submit_confirm_message', { count: n }),
    confirmLabel: t('portal.extracurricular_table.submit_dispatch'),
  })
  if (!ok) return
  passengerSubmitting.value = true
  passengerPatchErr.value = ''
  try {
    await submitStudentCount(req.value.id)
    await load()
  } catch (e) {
    passengerPatchErr.value = formatApiError(e, t('portal.extracurricular_table.submit_fail'))
  } finally {
    passengerSubmitting.value = false
  }
}

const tripTypeLabel = computed(() => {
  const tt = req.value?.trip_type
  if (!tt) return ''
  return t(`dispatch_wizard.trip_short.${tt}`)
})

const pdfExportDisabled = computed(() => req.value?.status !== 'approved')

const signedPaperAttachments = computed(() =>
  (req.value?.attachments ?? []).filter((a) => a.kind === 'signed_paper'),
)

const showSignedSection = computed(() => req.value?.status === 'approved')

const pdfIframeSrc = computed(() => {
  const url = pdfBlobUrl.value
  if (!url) return ''
  return `${url}#view=FitH`
})

async function copyRejectionReason() {
  const text = String(req.value?.rejection_reason ?? '').trim()
  if (!text) return
  try {
    await navigator.clipboard.writeText(text)
    copyRejectionFeedback.value = true
    if (copyRejectionTimer) clearTimeout(copyRejectionTimer)
    copyRejectionTimer = setTimeout(() => {
      copyRejectionFeedback.value = false
    }, 2000)
  } catch {
    window.alert(t('portal.copy_failed'))
  }
}

async function loadPdfPreview() {
  if (!req.value?.id || req.value.status !== 'approved') return
  if (pdfBlobUrl.value || pdfPreviewLoading.value) return
  pdfPreviewLoading.value = true
  pdfPreviewError.value = ''
  try {
    const blob = await exportPortalDispatchRequestPdf(Number(req.value.id))
    if (!blob || blob.size === 0) {
      throw new Error(t('portal.pdf_preview_failed'))
    }
    pdfBlobUrl.value = URL.createObjectURL(blob)
  } catch (e) {
    pdfPreviewError.value = formatApiError(e, t('portal.pdf_preview_failed'))
  } finally {
    pdfPreviewLoading.value = false
  }
}

async function retryPdfPreview() {
  revokePdfPreviewUrl()
  await loadPdfPreview()
}

function openPdfInNewTab() {
  if (!pdfBlobUrl.value) return
  window.open(pdfIframeSrc.value || pdfBlobUrl.value, '_blank', 'noopener,noreferrer')
}

async function downloadPdf() {
  if (!req.value?.id || pdfExportDisabled.value || pdfBusy.value) return
  pdfBusy.value = true
  try {
    const blob = await exportPortalDispatchRequestPdf(Number(req.value.id))
    saveAs(blob, `de-nghi-dieu-van-${req.value.id}.pdf`)
  } catch (e) {
    window.alert(formatApiError(e, t('portal.pdf_download_fail')))
  } finally {
    pdfBusy.value = false
  }
}

function uploadSignedPaper(file, onProgress) {
  return uploadPortalSignedPaper(Number(route.params.id), file, onProgress)
}

async function downloadAttachment(att) {
  signedUploadErr.value = ''
  try {
    const blob = await downloadPortalAttachmentBlob(Number(route.params.id), att.id)
    saveAs(blob, att.original_name || `attachment-${att.id}`)
  } catch (e) {
    signedUploadErr.value = formatApiError(e, t('portal.signed_download_fail'))
  }
}

async function onSignedUploaded() {
  signedUploadErr.value = ''
  uploadKeySeed.value += 1
  await load()
}
</script>
