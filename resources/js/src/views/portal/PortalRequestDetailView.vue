<template>
  <div
    class="portal-request-detail mx-auto max-w-7xl px-3 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-4 xs:px-4 sm:px-6 sm:pt-6 lg:pb-24"
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
      class="rounded-2xl border border-rose-200 bg-gradient-to-br from-rose-50 to-white px-4 py-5 text-sm text-rose-900 shadow-sm sm:px-6"
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
      <!-- Hero -->
      <section
        class="overflow-hidden rounded-3xl border border-slate-200/80 bg-gradient-to-br from-va-50/50 via-white to-slate-50/80 shadow-sm ring-1 ring-slate-900/5"
      >
        <div class="p-4 sm:p-6">
          <div class="flex min-w-0 items-start gap-3">
            <RouterLink
              :to="{ name: portalRoutes.list }"
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-white/80 bg-white/90 text-slate-600 shadow-sm backdrop-blur-sm transition hover:border-va-200 hover:bg-white hover:text-va-800 active:scale-[0.97]"
              :aria-label="t('portal.back_list')"
            >
              <ArrowLeftIcon class="h-5 w-5" aria-hidden="true" />
            </RouterLink>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <h1 class="font-mono text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                  #{{ req.id }}
                </h1>
                <button
                  type="button"
                  class="inline-flex min-h-[32px] items-center gap-1 rounded-lg border border-slate-200/80 bg-white/80 px-2.5 text-xs font-semibold text-slate-600 transition hover:border-va-200 hover:text-va-800"
                  @click="copyRequestId"
                >
                  <ClipboardDocumentIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                  {{ copyIdFeedback ? t('portal.copied') : t('portal.copy_id') }}
                </button>
              </div>
              <div class="mt-2 flex flex-wrap items-center gap-2">
                <StatusBadge :status="req.status" />
                <span
                  v-if="req.dispatch_request_template_id"
                  class="inline-flex items-center rounded-full bg-sky-100/90 px-2.5 py-0.5 text-xs font-semibold text-sky-900"
                >
                  {{ t('request_detail.badge_recurring') }}
                </span>
                <span
                  v-if="req.is_urgent"
                  class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800"
                >
                  <BoltIcon class="h-3.5 w-3.5" aria-hidden="true" />
                  {{ t('portal.badge_urgent') }}
                </span>
              </div>
            </div>
          </div>

          <div class="mt-5 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] sm:items-stretch sm:gap-4">
            <div
              class="flex min-h-[4.5rem] flex-col justify-center rounded-2xl border border-white/90 bg-white/70 px-4 py-3 shadow-sm backdrop-blur-sm"
            >
              <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ t('portal.origin') }}</p>
              <p class="mt-1 text-sm font-semibold leading-snug text-slate-900">{{ originText }}</p>
            </div>
            <div class="hidden items-center justify-center sm:flex" aria-hidden="true">
              <ArrowLongRightIcon class="h-6 w-6 text-va-800/50" />
            </div>
            <div
              class="flex min-h-[4.5rem] flex-col justify-center rounded-2xl border border-white/90 bg-white/70 px-4 py-3 shadow-sm backdrop-blur-sm"
            >
              <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ t('portal.destination') }}</p>
              <p class="mt-1 text-sm font-semibold leading-snug text-slate-900">{{ destinationText }}</p>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-600">
            <span
              v-if="departFmt"
              class="inline-flex min-h-[32px] items-center gap-1.5 rounded-full bg-white/80 px-3 py-1 font-medium ring-1 ring-slate-200/80"
            >
              <CalendarDaysIcon class="h-4 w-4 shrink-0 text-va-800" aria-hidden="true" />
              {{ departFmt }}
            </span>
            <span
              v-if="tripTypeLabel"
              class="inline-flex min-h-[32px] items-center gap-1.5 rounded-full bg-white/80 px-3 py-1 font-medium ring-1 ring-slate-200/80"
            >
              <TruckIcon class="h-4 w-4 shrink-0 text-slate-500" aria-hidden="true" />
              {{ tripTypeLabel }}
            </span>
            <span
              v-if="pollingRefreshing"
              class="inline-flex min-h-[32px] items-center gap-1.5 rounded-full bg-teal-50 px-3 py-1 font-semibold text-teal-800"
            >
              <span class="h-2 w-2 animate-pulse rounded-full bg-teal-600" aria-hidden="true" />
              {{ t('portal.auto_refresh_indicator') }}
            </span>
          </div>
        </div>
      </section>

      <div
        class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_min(100%,19rem)] lg:items-start xl:grid-cols-[minmax(0,1fr)_22rem] xl:gap-8"
      >
        <div class="min-w-0 space-y-6 lg:order-1">
          <div
            v-if="req.status === 'rejected' && req.rejection_reason"
            class="rounded-2xl border border-rose-200 bg-gradient-to-br from-rose-50/90 to-white p-4 shadow-sm sm:p-5"
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

          <div
            v-if="detailTabs.length"
            class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/5"
          >
            <nav
              v-if="detailTabs.length > 1"
              class="portal-detail-tabs sticky top-[calc(env(safe-area-inset-top,0px)+4.5rem)] z-20 -mx-px flex gap-1 overflow-x-auto border-b border-slate-100 bg-slate-50/95 px-2 py-2 backdrop-blur-md sm:static sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-3 sm:py-2"
              role="tablist"
              :aria-label="t('portal.detail_tablist_aria')"
            >
              <button
                v-for="tab in detailTabs"
                :key="tab.id"
                type="button"
                role="tab"
                :aria-selected="activeTab === tab.id"
                class="shrink-0 snap-start rounded-xl px-3.5 py-2.5 text-xs font-semibold transition sm:text-sm"
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
          <div v-show="activeTab === 'form' && showExtracurricularBm03">
            <PortalExtracurricularBm03EditForm
              ref="bm03FormRef"
              :req="req"
              @saved="load"
            />
          </div>

          <div v-show="activeTab === 'manage' && showRecurringExtras" class="space-y-4">
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

          <div v-show="activeTab === 'pdf' && req.status === 'approved'" class="space-y-3">
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

          <div v-show="activeTab === 'docs' && showSignedDocSection" class="space-y-4">
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
        </div>

        <aside class="space-y-4 lg:order-2 lg:sticky lg:top-[calc(env(safe-area-inset-top,0px)+5rem)] lg:self-start">
          <PortalStatusHint :req="req" />
          <PortalStatusTimeline
            :title="t('portal.timeline_heading')"
            :steps="timelineSteps"
          />
          <section
            class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm ring-1 ring-slate-900/5 sm:p-5"
          >
            <h2 class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500">
              {{ t('portal.detail_facts') }}
            </h2>
            <dl class="mt-4 space-y-3 text-sm">
              <div v-if="requesterLine">
                <dt class="text-xs font-medium text-slate-500">{{ t('portal.requester') }}</dt>
                <dd class="mt-0.5 font-semibold text-slate-900">{{ requesterLine }}</dd>
              </div>
              <div v-if="passengerLine">
                <dt class="text-xs font-medium text-slate-500">{{ t('portal.passenger_count') }}</dt>
                <dd class="mt-0.5 font-semibold text-slate-900">{{ passengerLine }}</dd>
              </div>
              <div v-if="purposeLine">
                <dt class="text-xs font-medium text-slate-500">{{ t('portal.purpose') }}</dt>
                <dd class="mt-0.5 leading-relaxed text-slate-800">{{ purposeLine }}</dd>
              </div>
              <div v-if="notesLine">
                <dt class="text-xs font-medium text-slate-500">{{ t('portal.notes') }}</dt>
                <dd class="mt-0.5 line-clamp-4 leading-relaxed text-slate-700">{{ notesLine }}</dd>
              </div>
            </dl>
          </section>
          <p class="hidden text-center text-xs leading-relaxed text-slate-500 lg:block">
            {{ t('portal.detail_help_footer') }}
          </p>
        </aside>
      </div>

      <p class="mt-8 text-center text-xs leading-relaxed text-slate-500 lg:hidden">
        {{ t('portal.detail_help_footer') }}
      </p>
    </template>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowLeftIcon,
  ArrowLongRightIcon,
  ArrowTopRightOnSquareIcon,
  BoltIcon,
  CalendarDaysIcon,
  ClipboardDocumentIcon,
  DocumentArrowDownIcon,
  TruckIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import { saveAs } from 'file-saver'
import {
  cloneDispatchRequest,
  exportPortalDispatchRequestPdf,
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
import StatusBadge from '../../components/ui/StatusBadge.vue'
import PortalSuccessCard from '../../components/portal/PortalSuccessCard.vue'
import PortalRequestDetailSkeleton from '../../components/portal/PortalRequestDetailSkeleton.vue'
import PortalStatusHint from '../../components/portal/PortalStatusHint.vue'
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
const bm03FormRef = ref(null)

const pdfBusy = ref(false)
const pdfBlobUrl = ref('')
const pdfPreviewLoading = ref(false)
const pdfPreviewError = ref('')
const welcomeOpen = ref(false)
const pollingRefreshing = ref(false)
const copyRejectionFeedback = ref(false)
const copyIdFeedback = ref(false)
let copyIdTimer = null

const signedUploadErr = ref('')
const signedPreviewBlobUrl = ref('')
const signedPreviewMime = ref('')
let signedPreviewObjectUrl = ''
let ocrPollTimer = null

let copyRejectionTimer = null

const timelineSteps = usePortalTimelineSteps(req, t)

const PORTAL_DETAIL_TAB_IDS = ['form', 'manage', 'pdf', 'docs']
const activeTab = ref('form')

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
  if (copyIdTimer) clearTimeout(copyIdTimer)
  if (ocrPollTimer) clearInterval(ocrPollTimer)
  revokePdfPreviewUrl()
  revokeSignedPreviewUrl()
})

const originText = computed(() => {
  const o = (req.value?.origin || '').trim()
  return o || '—'
})

const destinationText = computed(() => {
  const d = (req.value?.destination || '').trim()
  return d || '—'
})

const requesterLine = computed(() => {
  const r = req.value
  if (!r) return ''
  return (r.requester_name || r.requester?.name || r.user?.name || '').trim()
})

const passengerLine = computed(() => {
  const r = req.value
  if (!r) return ''
  const n = r.student_count_actual ?? r.passenger_count
  if (n == null || n === '') return ''
  return String(n)
})

const purposeLine = computed(() => (req.value?.purpose || '').trim())

const notesLine = computed(() => (req.value?.notes || '').trim())

async function copyRequestId() {
  const id = req.value?.id
  if (id == null) return
  try {
    await navigator.clipboard.writeText(String(id))
    copyIdFeedback.value = true
    if (copyIdTimer) clearTimeout(copyIdTimer)
    copyIdTimer = setTimeout(() => {
      copyIdFeedback.value = false
    }, 2000)
  } catch {
    window.alert(t('portal.copy_failed'))
  }
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

const detailTabs = computed(() => {
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

function portalTabVisible(id) {
  return detailTabs.value.some((x) => x.id === id)
}

function tabFromRouteQuery() {
  const q = route.query.tab
  if (typeof q !== 'string' || !PORTAL_DETAIL_TAB_IDS.includes(q)) return null
  return portalTabVisible(q) ? q : null
}

function resolveDefaultPortalTab() {
  const fromQuery = tabFromRouteQuery()
  if (fromQuery) return fromQuery
  if (portalTabVisible('docs')) return 'docs'
  if (portalTabVisible('form')) return 'form'
  if (portalTabVisible('pdf')) return 'pdf'
  if (portalTabVisible('manage')) return 'manage'
  return detailTabs.value[0]?.id ?? 'form'
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
