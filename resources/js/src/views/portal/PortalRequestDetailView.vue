<template>
  <div
    class="portal-request-detail mx-auto max-w-7xl px-3 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-4 xs:px-4 sm:px-6 sm:pt-6 supports-[padding:max(0px)]:pb-[max(1.5rem,env(safe-area-inset-bottom))]"
    :class="hasMobileActionBar ? 'max-sm:pb-[max(5rem,env(safe-area-inset-bottom))]' : ''"
  >
    <PortalSuccessCard
      v-if="welcomeOpen"
      class="mb-5 sm:mb-6"
      :title="t('portal.welcome_banner_title')"
      :badge="t('portal.status_pending')"
      :request-id="Number(route.params.id)"
    >
      <template #actions>
        <button
          type="button"
          class="min-h-[44px] rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 active:scale-[0.98]"
          @click="welcomeOpen = false"
        >
          {{ t('portal.welcome_banner_dismiss') }}
        </button>
      </template>
    </PortalSuccessCard>

    <PortalRequestDetailSkeleton
      v-if="loading"
      :aria-label="t('portal.detail_loading')"
    />

    <div
      v-else-if="detailError"
      class="rounded-xl border border-rose-200 bg-rose-50/60 px-4 py-5 text-sm text-rose-900 sm:px-6"
    >
      <p class="font-medium">{{ detailError }}</p>
      <RouterLink
        :to="{ name: portalRoutes.home }"
        class="mt-4 inline-flex min-h-[44px] items-center font-semibold text-va-800 underline-offset-2 hover:underline"
      >
        {{ t('portal.back_home') }}
      </RouterLink>
    </div>

    <template v-else-if="req">
      <PortalRequestHeroCard
        class="mb-4"
        :req="req"
        :back-route="portalRoutes.list"
        :priority-label="actionCenter.priorityLabel"
        :polling-refreshing="pollingRefreshing"
        :can-print="canPrintRequest"
        :can-withdraw="canWithdrawPending"
        :withdraw-busy="withdrawBusy"
        @print="onPrintRequest"
        @withdraw="onWithdrawPending"
      />

      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <nav
          class="portal-detail-tabs sticky top-[calc(env(safe-area-inset-top,0px)+var(--portal-header,3rem))] z-20 flex gap-1 overflow-x-auto overscroll-x-contain border-b border-slate-200 bg-slate-50 px-2 py-2 snap-x snap-mandatory sm:px-3 lg:static lg:top-auto lg:flex-wrap lg:overflow-visible lg:snap-none"
          role="tablist"
          :aria-label="t('portal.detail_tablist_aria')"
        >
          <button
            v-for="tab in detailTabs"
            :key="tab.id"
            type="button"
            role="tab"
            :aria-selected="activeTab === tab.id"
            :aria-controls="`portal-tab-panel-${tab.id}`"
            class="shrink-0 snap-start rounded-xl px-3.5 py-2.5 text-xs font-semibold transition min-h-[44px] sm:text-sm"
            :class="
              activeTab === tab.id
                ? 'bg-va-800 text-white shadow-sm shadow-va-900/15'
                : 'text-slate-600 hover:bg-white hover:text-slate-900'
            "
            @click="setActiveTab(tab.id)"
          >
            {{ tab.label }}
          </button>
        </nav>

        <div class="p-4 sm:p-6">
          <div
            v-if="activeTab === 'overview'"
            id="portal-tab-panel-overview"
            role="tabpanel"
            class="space-y-4"
          >
            <PortalRequestJourneyCard
              :origin="originText"
              :destination="destinationText"
              :depart-at="departFmt"
              :trip-type-label="tripTypeLabel"
            />

            <PortalRequestActionCenter
              :waiting-role-label="actionCenter.waitingRoleLabel"
              :waiting-person-name="actionCenter.waitingPersonName"
              :waiting-person-detail="actionCenter.waitingPersonDetail"
              :sla-label="actionCenter.slaLabel"
              :next-action-text="actionCenter.nextActionText"
              :hours-until-depart="actionCenter.hoursUntilDepart"
              :urgent-threshold-hours="req.threshold_hours ?? 24"
              :dispatch-strip="actionCenter.dispatchStrip"
            />

            <div
              v-if="req.status === 'rejected' && req.rejection_reason"
              class="rounded-xl border border-rose-200 bg-rose-50/50 p-4 sm:p-5"
            >
              <div class="flex items-start gap-3">
                <XCircleIcon class="h-7 w-7 shrink-0 text-rose-600" aria-hidden="true" />
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-bold text-rose-950">{{ t('portal.rejected_title') }}</p>
                  <p class="mt-2 max-h-40 overflow-y-auto whitespace-pre-wrap text-sm leading-relaxed text-rose-900/95">
                    {{ req.rejection_reason }}
                  </p>
                  <button
                    type="button"
                    class="mt-3 inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-900 transition hover:bg-rose-50 active:scale-[0.98]"
                    @click="copyRejectionReason"
                  >
                    <ClipboardDocumentIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                    {{ copyRejectionFeedback ? t('portal.copied') : t('portal.copy_rejection') }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div
            v-if="activeTab === 'progress'"
            id="portal-tab-panel-progress"
            role="tabpanel"
          >
            <div id="portal-request-timeline" class="w-full">
              <PortalStatusTimeline
                :title="t('portal.timeline_heading')"
                :steps="timelineSteps"
              />
            </div>
          </div>

          <div
            v-if="activeTab === 'details'"
            id="portal-tab-panel-details"
            role="tabpanel"
          >
            <PortalRequestInfoCards
              :req="req"
              :origin="originText"
              :destination="destinationText"
              :timeline-steps="timelineSteps"
              :purpose="purposeLine"
              :notes="notesLine"
            />
          </div>

          <div
            v-if="activeTab === 'form' && showExtracurricularBm03"
            id="portal-tab-panel-form"
            role="tabpanel"
          >
            <PortalExtracurricularBm03EditForm
              ref="bm03FormRef"
              :req="req"
              @saved="load"
            />
          </div>

          <div
            v-if="activeTab === 'manage' && showRecurringExtras"
            id="portal-tab-panel-manage"
            role="tabpanel"
            class="space-y-4"
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
                class="mt-3 inline-flex min-h-[40px] items-center rounded-xl bg-va-800 px-4 text-sm font-semibold text-white shadow-sm hover:bg-va-900 disabled:opacity-50"
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
          </div>

          <div
            v-if="activeTab === 'pdf' && req.status === 'approved'"
            id="portal-tab-panel-pdf"
            role="tabpanel"
            class="space-y-3"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="flex min-w-0 items-center gap-2">
                <PdfFileIcon class="shrink-0" size-class="h-8 w-6" />
                <h2 class="text-sm font-bold text-slate-900">{{ t('portal.pdf_preview_title') }}</h2>
              </div>
              <div class="flex shrink-0 flex-wrap items-center gap-2">
                <button
                  type="button"
                  class="inline-flex min-h-[44px] items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 active:scale-[0.98] disabled:opacity-50 sm:text-sm"
                  :disabled="!pdfBlobUrl"
                  @click="openPdfInNewTab"
                >
                  <ArrowTopRightOnSquareIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                  {{ t('portal.pdf_preview_open_tab') }}
                </button>
                <button
                  type="button"
                  class="inline-flex min-h-[44px] items-center gap-1.5 rounded-xl border border-va-200 bg-va-50 px-3.5 py-2 text-xs font-semibold text-va-900 transition hover:bg-va-100 active:scale-[0.98] disabled:opacity-50 sm:text-sm"
                  :disabled="pdfBusy || !pdfBlobUrl"
                  @click="downloadPdf"
                >
                  <DocumentArrowDownIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                  {{ t('portal.pdf_btn_signing_label') }}
                </button>
              </div>
            </div>
            <div class="relative rounded-xl bg-slate-100 p-2">
              <div
                v-if="pdfPreviewLoading"
                class="flex min-h-[min(50vh,420px)] flex-col items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white py-12 text-sm text-slate-600"
              >
                <span class="h-8 w-8 animate-spin rounded-full border-2 border-va-200 border-t-va-800" aria-hidden="true" />
                {{ t('portal.pdf_export_loading') }}
              </div>
              <div
                v-else-if="pdfPreviewError"
                class="flex min-h-[200px] flex-col items-center justify-center gap-2 rounded-lg border border-rose-200 bg-rose-50/80 px-4 py-8 text-center"
              >
                <p class="max-w-md text-sm text-rose-800">{{ pdfPreviewError }}</p>
                <button
                  type="button"
                  class="rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-semibold text-rose-900 hover:bg-rose-50"
                  @click="retryPdfPreview"
                >
                  {{ t('portal.pdf_preview_retry') }}
                </button>
              </div>
              <div v-else-if="pdfBlobUrl" class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <iframe
                  :src="pdfIframeSrc"
                  class="block h-[min(62vh,560px)] w-full border-0 bg-white sm:h-[min(55vh,560px)]"
                  :title="t('portal.pdf_preview_iframe_title')"
                />
              </div>
            </div>
          </div>

          <div
            v-if="activeTab === 'docs' && showSignedDocSection"
            id="portal-tab-panel-docs"
            role="tabpanel"
            class="space-y-4"
          >
            <PortalSignedDocUpload
              :attachments="signedPaperAttachments"
              upload-component-key="portal-signed"
              :upload-fn="uploadSignedFn"
              :error="signedUploadErr"
              @download="downloadSignedAttachment"
              @uploaded="onSignedUploaded"
            />
            <PortalSignedDocCompare
              :pdf-blob-url="pdfBlobUrl"
              :signed-blob-url="signedPreviewBlobUrl"
              :signed-mime="signedPreviewMime"
            />
          </div>
        </div>
      </div>

      <PortalRequestMobileActionBar
        v-if="hasMobileActionBar"
        :can-print="canPrintRequest"
        :can-withdraw="canWithdrawPending"
        :withdraw-busy="withdrawBusy"
        @print="onPrintRequest"
        @withdraw="onWithdrawPending"
      />
    </template>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowTopRightOnSquareIcon,
  ClipboardDocumentIcon,
  DocumentArrowDownIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import { saveAs } from 'file-saver'
import { confirmAndCloneDispatchRequest } from '../../composables/useDispatchRequestClone'
import {
  exportPortalDispatchRequestPdf,
  deletePortalDispatchRequest,
  getPortalDispatchRequest,
  patchPassengerCount,
  submitStudentCount,
  uploadPortalSignedPaper,
  downloadPortalAttachmentBlob,
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
import { usePortalRequestActionCenter } from '../../composables/usePortalRequestActionCenter.js'
import PortalSuccessCard from '../../components/portal/PortalSuccessCard.vue'
import PortalRequestDetailSkeleton from '../../components/portal/PortalRequestDetailSkeleton.vue'
import PortalRequestHeroCard from '../../components/portal/PortalRequestHeroCard.vue'
import PortalRequestJourneyCard from '../../components/portal/PortalRequestJourneyCard.vue'
import PortalRequestActionCenter from '../../components/portal/PortalRequestActionCenter.vue'
import PortalRequestInfoCards from '../../components/portal/PortalRequestInfoCards.vue'
import PortalRequestMobileActionBar from '../../components/portal/PortalRequestMobileActionBar.vue'
import PortalStatusTimeline from '../../components/portal/PortalStatusTimeline.vue'
import PdfFileIcon from '../../components/icons/PdfFileIcon.vue'
import {
  isExtracurricularDispatchRequest,
  usePortalExtracurricularModule,
} from '../../composables/usePortalExtracurricularModule'
import PortalExtracurricularBm03EditForm from '../../components/portal/extracurricular/PortalExtracurricularBm03EditForm.vue'
import PortalSignedDocUpload from '../../components/portal/PortalSignedDocUpload.vue'
import PortalSignedDocCompare from '../../components/portal/PortalSignedDocCompare.vue'

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
const withdrawBusy = ref(false)
const bm03FormRef = ref(null)

const pdfBusy = ref(false)
const pdfBlobUrl = ref('')
const pdfPreviewLoading = ref(false)
const pdfPreviewError = ref('')
const welcomeOpen = ref(false)
const pollingRefreshing = ref(false)
const copyRejectionFeedback = ref(false)
const signedUploadErr = ref('')
const signedPreviewBlobUrl = ref('')
const signedPreviewMime = ref('')
let signedPreviewObjectUrl = ''
let ocrPollTimer = null

let copyRejectionTimer = null
let portalDetailLoadSeq = 0

const timelineSteps = usePortalTimelineSteps(req, t)
const actionCenter = usePortalRequestActionCenter(req, t)

const canPrintRequest = computed(() => req.value?.status === 'approved')

const canWithdrawPending = computed(() => {
  if (!isCurrentUserRequester.value || !req.value) return false
  return ['pending', 'price_filled'].includes(String(req.value.status || ''))
})

const hasMobileActionBar = computed(() => canPrintRequest.value || canWithdrawPending.value)

const PORTAL_DETAIL_TAB_IDS = ['overview', 'progress', 'details', 'form', 'manage', 'pdf', 'docs']
const activeTab = ref('overview')

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
    if (s === 'approved' && (activeTab.value === 'pdf' || activeTab.value === 'docs')) loadPdfPreview()
  },
)

function syncPortalDetailRouteName(data) {
  if (!data?.id) return
  const target = isExtracurricularDispatchRequest(data)
    ? 'portalExtracurricularDetail'
    : 'portalRequestDetail'
  if (route.name === target) return
  router.replace({
    name: target,
    params: { id: String(data.id) },
    query: route.query,
  })
}

async function load(opts = {}) {
  const silent = opts.silent === true
  const seq = ++portalDetailLoadSeq
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
    if (seq !== portalDetailLoadSeq) return
    syncPortalDetailRouteName(data)
    req.value = data
    const actual = data.student_count_actual ?? data.passenger_count
    passengerDraft.value = Math.max(1, Math.min(999, Math.round(Number(actual) || 1)))
    passengerPatchErr.value = ''
    if (!silent) detailError.value = ''
    if (!silent) {
      const qTab = tabFromRouteQuery()
      if (qTab) activeTab.value = qTab
      else if (!portalTabVisible(activeTab.value)) activeTab.value = resolveDefaultPortalTab()
    }
  } catch (e) {
    if (seq !== portalDetailLoadSeq) return
    if (!silent) {
      detailError.value = formatApiError(e, t('portal.detail_load_fail'))
      req.value = null
    }
  } finally {
    if (seq !== portalDetailLoadSeq) return
    if (!silent) loading.value = false
    else pollingRefreshing.value = false
  }
}

usePortalDetailPoll(load, req)

function revokeSignedPreviewUrl() {
  if (signedPreviewObjectUrl) {
    URL.revokeObjectURL(signedPreviewObjectUrl)
    signedPreviewObjectUrl = ''
  }
  signedPreviewBlobUrl.value = ''
  signedPreviewMime.value = ''
}

const signedPaperAttachments = computed(() => {
  const list = req.value?.attachments ?? []
  return list.filter((a) => a.kind === 'signed_paper')
})

const signedDocumentCurrent = computed(() => req.value?.signed_document?.current ?? null)

async function loadSignedPreview() {
  revokeSignedPreviewUrl()
  const att = signedDocumentCurrent.value?.attachment
  if (!att?.id || !req.value?.id) return
  try {
    const blob = await downloadPortalAttachmentBlob(Number(req.value.id), Number(att.id))
    signedPreviewMime.value = att.mime_type || blob.type || ''
    signedPreviewObjectUrl = URL.createObjectURL(blob)
    signedPreviewBlobUrl.value = signedPreviewObjectUrl
  } catch {
    /* preview optional */
  }
}

watch(signedDocumentCurrent, () => {
  loadSignedPreview()
})

function syncOcrPoll() {
  if (ocrPollTimer) {
    clearInterval(ocrPollTimer)
    ocrPollTimer = null
  }
  const st = signedDocumentCurrent.value?.ocr_status
  if (st === 'queued' || st === 'processing') {
    ocrPollTimer = setInterval(() => load({ silent: true }), 8000)
  }
}

watch(
  () => signedDocumentCurrent.value?.ocr_status,
  () => syncOcrPoll(),
  { immediate: true },
)

async function uploadSignedFn(file, onProgress) {
  signedUploadErr.value = ''
  await uploadPortalSignedPaper(Number(req.value.id), file, onProgress)
}

async function onSignedUploaded() {
  await load()
}

async function downloadSignedAttachment(a) {
  if (!req.value?.id || !a?.id) return
  try {
    const blob = await downloadPortalAttachmentBlob(Number(req.value.id), Number(a.id))
    const { saveAs } = await import('file-saver')
    saveAs(blob, a.original_name || `signed-${a.id}`)
  } catch (e) {
    window.alert(formatApiError(e, t('portal.signed_download_fail')))
  }
}

onMounted(async () => {
  await load()
  activeTab.value = resolveDefaultPortalTab()
  if ((activeTab.value === 'pdf' || activeTab.value === 'docs') && req.value?.status === 'approved') {
    loadPdfPreview()
  }
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
  if (ocrPollTimer) clearInterval(ocrPollTimer)
  revokePdfPreviewUrl()
  revokeSignedPreviewUrl()
})

const originText = computed(() => (req.value?.origin || '').trim())

const destinationText = computed(() => (req.value?.destination || '').trim())

const purposeLine = computed(() => (req.value?.purpose || '').trim())

const notesLine = computed(() => (req.value?.notes || '').trim())

async function onPrintRequest() {
  if (req.value?.status === 'approved') {
    if (!pdfBlobUrl.value) await loadPdfPreview()
    if (pdfBlobUrl.value) {
      openPdfInNewTab()
      return
    }
  }
  window.print()
}

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

const showSignedDocSection = computed(
  () => req.value?.status === 'approved' && isCurrentUserRequester.value,
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

const showExtracurricularBm03 = computed(() => {
  if (!req.value || !isExtracurricularModule.value) return false
  return true
})

const showExtracurricularInstanceEdit = computed(() => showExtracurricularBm03.value)

function scrollToBm03Form() {
  if (portalTabVisible('form')) setActiveTab('form')
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
  if (showExtracurricularBm03.value) return false
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

const secondaryDetailTabs = computed(() => {
  const tabs = []
  if (showExtracurricularBm03.value) {
    tabs.push({ id: 'form', label: t('portal.detail_tab_form') })
  }
  if (showRecurringExtras.value) {
    tabs.push({ id: 'manage', label: t('portal.detail_tab_manage') })
  }
  if (req.value?.status === 'approved') {
    tabs.push({ id: 'pdf', label: t('portal.detail_tab_pdf') })
  }
  if (showSignedDocSection.value) {
    tabs.push({ id: 'docs', label: t('portal.detail_tab_docs') })
  }
  return tabs
})

const detailTabs = computed(() => [
  { id: 'overview', label: t('portal.detail_tab_overview') },
  { id: 'progress', label: t('portal.detail_tab_progress') },
  { id: 'details', label: t('portal.detail_tab_details') },
  ...secondaryDetailTabs.value,
])

function portalTabVisible(id) {
  if (id === 'overview' || id === 'progress' || id === 'details') return true
  return secondaryDetailTabs.value.some((x) => x.id === id)
}

function tabFromRouteQuery() {
  const q = route.query.tab
  if (typeof q !== 'string' || !PORTAL_DETAIL_TAB_IDS.includes(q)) return null
  return portalTabVisible(q) ? q : null
}

function resolveDefaultPortalTab() {
  const fromQuery = tabFromRouteQuery()
  if (fromQuery) return fromQuery
  return 'overview'
}

function setActiveTab(tab) {
  if (!portalTabVisible(tab)) return
  activeTab.value = tab
  if (route.query.tab !== tab) {
    router.replace({ query: { ...route.query, tab } })
  }
  if ((tab === 'pdf' || tab === 'docs') && req.value?.status === 'approved') {
    loadPdfPreview()
  }
}

watch(
  () => route.query.tab,
  (tab) => {
    if (typeof tab === 'string' && PORTAL_DETAIL_TAB_IDS.includes(tab) && portalTabVisible(tab) && activeTab.value !== tab) {
      activeTab.value = tab
      if ((tab === 'pdf' || tab === 'docs') && req.value?.status === 'approved') loadPdfPreview()
    }
  },
)

watch(detailTabs, () => {
  if (!portalTabVisible(activeTab.value)) {
    activeTab.value = resolveDefaultPortalTab()
  }
})

async function onWithdrawPending() {
  if (!req.value?.id || withdrawBusy.value || !canWithdrawPending.value) return
  const ok = await confirmAction({
    title: t('portal.withdraw_pending_confirm_title'),
    message: t('portal.withdraw_pending_confirm_message'),
    confirmLabel: t('portal.withdraw_pending'),
  })
  if (!ok) return
  withdrawBusy.value = true
  try {
    await deletePortalDispatchRequest(req.value.id)
    await router.push({ name: portalRoutes.value.list })
  } catch (e) {
    window.alert(formatApiError(e, t('portal.withdraw_pending_fail')))
  } finally {
    withdrawBusy.value = false
  }
}

async function onResetCloneRequest() {
  if (!req.value?.id || resetCloneBusy.value) return
  resetCloneBusy.value = true
  try {
    const dr = await confirmAndCloneDispatchRequest(req.value, t)
    if (!dr?.id) return
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

</script>

<style scoped>
.portal-detail-tabs {
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}
.portal-detail-tabs::-webkit-scrollbar {
  display: none;
}
</style>
