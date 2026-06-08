<template>
  <div
    :class="[
      'flex min-h-0 flex-col bg-slate-50',
      isDeptRequestDetailRoute ? 'min-w-0 flex-1 overflow-hidden' : 'w-full',
    ]"
  >
    <div v-if="loading" class="flex flex-1 items-center justify-center px-4 py-12 text-sm text-slate-500">
      {{ t('request_detail.page_loading') }}
    </div>

    <template v-else-if="req">
      <RequestDetailTopBar
        :back-to="isDeptRequestDetailRoute ? { name: 'deptDashboard' } : '/requests'"
        :back-aria-label="isDeptRequestDetailRoute ? t('dept.aria_back_pending') : t('request_detail.aria_back_list')"
        :title="requestRefCode"
        :status="req.status"
        :recurring="showRecurringBadge"
        :inline-alert="
          msg && req.status === 'pending' && canApprove && req.trip_type === 'door_to_door' ? msg : ''
        "
      >
        <template #meta>
          {{ t('request_detail.meta_created', { dt: fmt(req.created_at) }) }}
          <template v-if="req.trip">
            <span class="text-slate-300"> · </span>
            <RouterLink
              :to="`/trips/${req.trip.id}`"
              class="font-medium text-slate-800 underline decoration-slate-400 underline-offset-2 hover:decoration-slate-700"
            >
              {{ t('request_detail.trip_link', { id: req.trip.id }) }}
            </RouterLink>
          </template>
        </template>
        <template #actions>
          <button
            type="button"
            class="inline-flex h-9 items-center justify-center rounded-lg border px-3 text-xs font-medium transition sm:text-sm"
            :class="
              pdfExportDisabled
                ? 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400'
                : 'border-slate-300 bg-white text-slate-800 hover:bg-slate-50'
            "
            :disabled="pdfBusy || pdfExportDisabled"
            :title="pdfExportDisabled ? t('request_detail.pdf_locked_tooltip') : t('request_detail.export_pdf')"
            @click="downloadRequestPdf"
          >
            <span
              v-if="pdfBusy"
              class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-slate-400/30 border-t-slate-700"
            />
            {{ pdfBusy ? t('request_detail.pdf_export_loading') : t('request_detail.export_pdf') }}
          </button>
          <div
            v-if="req.status === 'pending' && canApprove && req.trip_type === 'door_to_door'"
            class="flex gap-2"
          >
            <Button
              variant="danger"
              :loading="acting"
              class="min-h-9 !px-3 !py-2 text-xs font-semibold sm:text-sm"
              @click="onDecideClick('reject')"
            >
              {{ t('request_detail.dept_reject') }}
            </Button>
            <Button
              :loading="acting"
              class="min-h-9 !bg-slate-900 !px-4 !py-2 text-xs font-semibold text-white hover:!bg-slate-800 sm:text-sm"
              @click="onDecideClick('approve')"
            >
              {{ t('request_detail.header_approve') }}
            </Button>
          </div>
        </template>
      </RequestDetailTopBar>

      <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain">
        <div class="mx-auto max-w-5xl space-y-5 px-4 py-5 pb-10">
          <div
            v-if="req.status === 'rejected'"
            class="rounded-lg border border-rose-200 bg-rose-50/90 p-4 sm:p-5"
            role="alert"
          >
        <div class="flex items-start gap-3">
          <XCircleIcon class="h-9 w-9 shrink-0 text-rose-600" aria-hidden="true" />
          <div class="min-w-0 flex-1">
            <p class="text-base font-bold text-rose-950 sm:text-lg">{{ rejectionBannerTitle }}</p>
            <p
              v-if="req.rejection_reason"
              class="mt-2 text-xs font-bold uppercase tracking-wide text-rose-800/90 sm:text-sm"
            >
              {{ t('request_detail.dept_reject_reason_label') }}
            </p>
            <p
              v-if="req.rejection_reason"
              class="mt-2 whitespace-pre-wrap rounded-xl border-2 border-rose-300/80 bg-white px-4 py-3 text-sm font-semibold leading-relaxed text-rose-950 shadow-inner sm:text-base"
            >
              {{ req.rejection_reason }}
            </p>
            <p v-else class="mt-2 text-sm text-rose-800/90">{{ t('request_detail.rejected_no_reason') }}</p>
            <button
              v-if="req.rejection_reason"
              type="button"
              class="mt-3 inline-flex min-h-[40px] items-center gap-2 rounded-lg border border-rose-200 bg-white px-3 text-xs font-semibold text-rose-900 shadow-sm transition hover:bg-rose-50 sm:text-sm"
              @click="copyRejectionReason"
            >
              <ClipboardDocumentIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ copyRejectionFeedback ? t('request_detail.copied') : t('request_detail.copy_rejection') }}
            </button>
          </div>
        </div>
          </div>

          <RequestCloneLineageBanner
            v-if="req?.cloned_from_summary"
            :summary="req.cloned_from_summary"
            :context="isDeptRequestDetailRoute ? 'dept' : 'staff'"
          />

          <RequestWorkflowBar
            v-if="workflowTodoItems.length"
            :todos="workflowTodoItems"
            @navigate="onWorkflowNavigate"
          />

          <RequestDetailOverviewPanel
            :avatar-url="req.requester?.avatar_url ?? ''"
            :initials="requesterInitials"
            :requester-name="req.requester?.name ?? '—'"
            :requester-subtitle="requesterAsideSubtitle"
            :requester-fields="requesterAsideFields"
            :trip-type-label="labelTripType(req.trip_type)"
            :load-line="passengerOrCargoLine"
            :origin="req.origin || '—'"
            :destination="req.destination || '—'"
            :schedule-line="overviewScheduleLine"
          />

          <RequestDetailSectionNav
            :items="sectionNavItems"
            :active-id="activeTab"
            @select="setActiveTab"
          />

          <RequestDetailSection
            section-id="request-section-route"
            :title="t('request_detail.section_progress')"
            :lead="routeSectionLead"
          >
            <RequestRouteTab
              hide-route-summary
              :req="req"
              :timeline-steps="timelineSteps"
              :cost-estimate="costEstimate"
              :route-sub-from="routeSubFrom"
              :route-sub-to="routeSubTo"
              :passenger-or-cargo-line="passengerOrCargoLine"
              :purpose="wizardPurpose"
              :show-approval-panel="showApprovalDecisionPanel"
              :show-dispatcher-price="showDeptDecisionSection"
              :attachment-count="generalAttachments.length"
              :requester-unit-fallback="requesterSubtitle"
              :depart-date-label="fmtDateVi(req.depart_at)"
              :time-window-label="fmtTimeWindow(req.depart_at, req.arrive_by)"
              :trip-type-label="labelTripType(req.trip_type)"
              :declared-total-label="costEstimate ? formatVndCurrency(costEstimate.total) : '—'"
              :dispatcher-price-label="req.service_price != null ? formatVndCurrency(req.service_price) : '—'"
            />
          </RequestDetailSection>

          <RequestDetailSection
            section-id="request-section-form"
            :title="t('request_detail.tab_form')"
          >
            <div class="space-y-5">
              <DeptApprovalSection
                v-if="showDeptDecisionSection"
                id="request-focus-dept-decision"
                class="scroll-mt-24 ring-offset-2 transition-shadow"
                :class="focusHighlight === 'dept-decision' ? 'ring-2 ring-amber-400/80' : ''"
                :declared-total-label="costEstimate?.declaredTotalLabel ?? null"
                :service-price-display="req.service_price != null ? formatVndCurrency(req.service_price) : null"
                :acting="deptActing"
                :inline-message="deptMsg"
                :reject-modal-open="deptRejectOpen"
                @approve="onDeptApproveClick"
                @reject="openDeptReject"
              />
              <RequestBm03FormTab
                v-if="req"
                :req="req"
                :fill-price-acting="fillPriceActing"
                :fill-price-msg="fillPriceMsg"
                :signed-paper-attachments="signedPaperAttachments"
                :signed-upload-component-key="`signed-${route.params.id}-${signedPaperAttachments.length}`"
                :upload-signed-fn="uploadSignedPaper"
                :signed-upload-err="signedUploadErr"
                :show-fill-price-section="showFillPriceSection"
                :show-signed-paper-section="showSignedPaperSection"
                :signed-document-current="signedDocumentCurrent"
                :approval-tab-needs-focus="approvalTabNeedsFocus"
                @save-row-prices="onSaveRowPrices"
                @open-reference-pricing="pricingModalOpen = true"
                @download-signed="downloadFile"
                @signed-uploaded="onSignedUploaded"
              />
              <ResetCloneSection v-if="showResetCloneBtn" :busy="resetCloneBusy" @clone="onResetCloneRequest" />
            </div>
          </RequestDetailSection>

          <RequestDetailSection
            v-if="showStudentCountTab"
            section-id="request-section-students"
            :title="t('request_detail.tab_students')"
          >
            <RequestStudentCountTab
              v-if="req"
              v-model:passenger-draft="passengerDraft"
              :req="req"
              :passenger-saving="passengerSaving"
              :passenger-patch-err="passengerPatchErr"
              :passenger-depart-locked="passengerDepartLocked"
              :passenger-dispatcher-override="passengerDispatcherOverride"
              :depart-at-formatted="req.depart_at ? fmtStepDetail(req.depart_at) : ''"
              :show-passenger-adjust-section="showPassengerAdjustSection"
              @save-passenger="savePassengerDraft"
            />
          </RequestDetailSection>

          <RequestDetailSection
            section-id="request-section-docs"
            :title="t('request_detail.tab_docs')"
          >
              <RequestDocsPanel
                v-if="req"
                :req="req"
                :request-id="route.params.id"
                :docs-progress-steps="docsProgressSteps"
                :docs-checklist="docsChecklist"
                :highlight-attachment-id="docsHighlightAttachmentId"
                :general-attachments="generalAttachments"
                :signed-paper-attachments="signedPaperAttachments"
                :paper-scans="paperScans"
                :attach-err="attachErr"
                :ocr-err="ocrErr"
                :ocr-busy="ocrBusy"
                :deleting-id="deletingId"
                :can-upload-general="canUploadAttachment"
                :can-upload-paper-scan="canUploadAttachment"
                :can-delete-attachment="canDeleteAttachment"
                :can-run-ocr="canUploadAttachment"
                :signed-document-current="signedDocumentCurrent"
                :can-manage-signed-document="canManagePaper"
                :signed-ocr-busy="signedOcrBusy"
                :signed-verify-busy="signedVerifyBusy"
                :upload-general-fn="uploadRequestDocument"
                :upload-paper-scan-fn="uploadPaperScan"
                :format-date-time="fmt"
                @preview="openAttachmentPreview"
                @download="downloadFile"
                @delete="removeAttachment"
                @ocr="runOcr"
                @uploaded-general="onDocUploaded"
                @uploaded-paper-scan="onPaperScanUploaded"
                @signed-rerun-ocr="onSignedRerunOcr"
                @signed-verify="onSignedVerify"
              >
                <template v-if="canManagePaper" #paper-forms>
                  <div v-if="req.paper_status === 'pending'" class="rounded-md border border-slate-100 bg-slate-50/60 p-2.5">
                    <h3 class="text-[10px] font-bold uppercase tracking-wide text-slate-600">{{ t('request_detail.paper_confirm_received_title') }}</h3>
                    <form class="mt-2 grid gap-2 sm:grid-cols-2" @submit.prevent="doMarkPaper">
                      <div class="sm:col-span-2"><Input v-model="paperForm.paper_reference" :label="t('request_detail.paper_ref_input_label')" :placeholder="t('request_detail.paper_ref_placeholder')" /></div>
                      <div class="sm:col-span-2"><Input v-model="paperForm.paper_received_at" :label="t('request_detail.paper_received_at_input_label')" type="datetime-local" /></div>
                      <div class="flex flex-nowrap items-center gap-2 sm:col-span-2">
                        <Button :loading="paperActing" type="submit" class="!h-8 !px-3 !py-1 !text-xs !bg-teal-600 hover:!bg-teal-700">{{ t('request_detail.paper_mark_received_btn') }}</Button>
                        <span v-if="paperMsg" class="min-w-0 truncate text-[11px] text-slate-600">{{ paperMsg }}</span>
                      </div>
                    </form>
                  </div>
                  <div v-else-if="req.paper_status === 'received'" class="rounded-md border border-slate-100 bg-slate-50/60 p-2.5">
                    <h3 class="text-[10px] font-bold uppercase tracking-wide text-slate-600">{{ t('request_detail.paper_update_section_title') }}</h3>
                    <form class="mt-2 grid gap-2 sm:grid-cols-2" @submit.prevent="doMarkPaper">
                      <div class="sm:col-span-2"><Input v-model="paperForm.paper_reference" :label="t('request_detail.paper_ref_input_label')" :placeholder="t('request_detail.paper_ref_placeholder')" /></div>
                      <div class="sm:col-span-2"><Input v-model="paperForm.paper_received_at" :label="t('request_detail.paper_received_at_input_label')" type="datetime-local" /></div>
                      <div class="flex flex-nowrap items-center gap-2 overflow-x-auto sm:col-span-2">
                        <Button :loading="paperActing" type="submit" class="!h-8 !shrink-0 !px-3 !py-1 !text-xs !bg-teal-600 hover:!bg-teal-700">{{ t('request_detail.paper_save_changes_btn') }}</Button>
                        <Button variant="secondary" type="button" class="!h-8 !shrink-0 !px-3 !py-1 !text-xs !border-amber-200 !text-amber-900 hover:!bg-amber-50" :disabled="paperActing || paperRevertActing" @click="doRevertPaper">{{ t('request_detail.paper_revert_btn') }}</Button>
                        <span v-if="paperMsg" class="min-w-0 flex-1 truncate text-[11px] text-slate-600">{{ paperMsg }}</span>
                      </div>
                    </form>
                  </div>
                </template>
              </RequestDocsPanel>
          </RequestDetailSection>
        </div>
      </div>

      <AttachmentPreviewModal
        :open="previewOpen"
        :attachment="previewAttachment"
        @close="closeAttachmentPreview"
      />

      <RejectReasonModal
        :open="deptRejectOpen"
        :reason="deptRejectReason"
        :acting="deptActing"
        :error-message="deptMsg"
        @update:reason="deptRejectReason = $event"
        @close="closeDeptReject"
        @confirm="submitDeptReject"
      />

      <Teleport to="body">
        <div
          v-if="pricingModalOpen"
          class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 p-3 sm:items-center sm:p-4"
          role="dialog"
          aria-modal="true"
          aria-labelledby="pricing-modal-title"
          @click.self="pricingModalOpen = false"
        >
          <div
            class="flex max-h-[min(90dvh,calc(100dvh-2rem))] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-teal-200 bg-white shadow-2xl ring-1 ring-slate-900/5"
            @click.stop
          >
            <div class="shrink-0 border-b border-teal-100 bg-gradient-to-r from-teal-50 to-white px-4 py-3 sm:px-5">
              <h3 id="pricing-modal-title" class="text-base font-semibold text-teal-950">
                {{ t('request_detail.pricing_modal_title') }}
              </h3>
              <p class="mt-1 text-xs leading-relaxed text-teal-900/85">
                {{ t('request_detail.pricing_modal_lead') }}
              </p>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-4 pb-4 pt-3 sm:px-5 sm:pt-4">
              <ReferencePricingReadOnlyBody
                :loading="pricingModalLoading"
                :error="pricingModalError"
                :passenger-fares="pricingModalData.passenger_fares"
                :cargo-fares="pricingModalData.cargo_fares"
                :notes="pricingModalData.notes"
              />
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2 border-t border-slate-100 bg-slate-50/80 px-4 py-3 sm:flex-nowrap sm:px-5">
              <Button
                v-if="canOpenPricingManagePage"
                type="button"
                variant="secondary"
                class="w-full !border-teal-200 !text-teal-900 hover:!bg-teal-50 sm:me-auto sm:w-auto"
                @click="openPricingManagePage"
              >
                {{ t('request_detail.reference_pricing_manage_link') }}
              </Button>
              <Button type="button" class="ms-auto !bg-teal-600 hover:!bg-teal-700 sm:ms-0" @click="pricingModalOpen = false">
                {{ t('app.close') }}
              </Button>
            </div>
          </div>
        </div>
      </Teleport>
    </template>
  </div>
</template>

<script setup>
import { computed, defineAsyncComponent, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  CalculatorIcon,
  ClipboardDocumentIcon,
  DocumentTextIcon,
  DocumentIcon,
  ArrowDownTrayIcon,
  TrashIcon,
  SparklesIcon,
  PaperClipIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import FileUpload from '../../components/ui/FileUpload.vue'
const RequestBm03FormTab = defineAsyncComponent(() =>
  import('../../components/requests/RequestBm03FormTab.vue'),
)
const RequestStudentCountTab = defineAsyncComponent(() =>
  import('../../components/requests/RequestStudentCountTab.vue'),
)
import RequestCloneLineageBanner from '../../components/requests/RequestCloneLineageBanner.vue'
import ResetCloneSection from '../../components/requests/ResetCloneSection.vue'
import RequestDocsPanel from '../../components/requests/RequestDocsPanel.vue'
import RequestWorkflowBar from '../../components/requests/RequestWorkflowBar.vue'
import RequestRouteTab from '../../components/requests/RequestRouteTab.vue'
import RequestDetailTopBar from '../../components/requests/detail/RequestDetailTopBar.vue'
import RequestDetailOverviewPanel from '../../components/requests/detail/RequestDetailOverviewPanel.vue'
import RequestDetailSectionNav from '../../components/requests/detail/RequestDetailSectionNav.vue'
import RequestDetailSection from '../../components/requests/detail/RequestDetailSection.vue'
import AttachmentPreviewModal from '../../components/requests/AttachmentPreviewModal.vue'
import DeptApprovalSection from '../../components/requests/DeptApprovalSection.vue'
import RejectReasonModal from '../../components/requests/RejectReasonModal.vue'
import { useDispatchRequestDocs } from '../../composables/useDispatchRequestDocs'
import { useRequestCostEstimate } from '../../composables/useRequestCostEstimate'
import { useRequestWorkflowSteps } from '../../composables/useRequestWorkflowSteps'
import { useRequestTimelineSteps } from '../../composables/useRequestTimelineSteps'
import ReferencePricingReadOnlyBody from '../../components/pricing/ReferencePricingReadOnlyBody.vue'
import { deleteAttachment, runAttachmentOcr, uploadAttachment } from '../../api/attachments'
import { rerunSignedDocumentOcr, verifySignedDocument } from '../../api/signedDocuments'
import { getDispatchFormSettings } from '../../api/dispatchSettings'
import {
  decideDispatchRequest,
  deptDecideDispatchRequest,
  exportDispatchRequestPdf,
  fillPriceDispatchRequest,
  getDispatchRequest,
  markPaperReceived,
  revertPaperReceived,
  cloneDispatchRequest,
  patchPassengerCount,
} from '../../api/requests'
import { getReferencePricing } from '../../api/pricing'
import { formatApiError } from '../../api/http'
import { saveAs } from 'file-saver'
import { newIdempotencyKey } from '../../util/idempotency'
import { labelTripType } from '../../util/labels'
import { parseMoneyVnd } from '../../util/money'
import { downloadBinaryAttachmentFromApi } from '../../util/downloadPdfAttachment'
import { toDatetimeLocalValue } from '../../util/datetime'
import { useAuthStore } from '../../store'
import { confirmAction } from '../../composables/useConfirm'
import { showAppSuccess, showAppError } from '../../composables/appMessage'
import { buildStaffPrefixedPath } from '../../config/dispatchWebBase'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { t, locale } = useI18n()

const req = ref(null)
const loading = ref(true)
const acting = ref(false)
const msg = ref('')

const paperForm = ref({ paper_reference: '', paper_received_at: '' })
const paperActing = ref(false)
const paperRevertActing = ref(false)
const paperMsg = ref('')
const ocrBusy = ref(null)
const ocrErr = ref('')
const signedOcrBusy = ref(false)
const signedVerifyBusy = ref(false)

const attachErr = ref('')
const deletingId = ref(null)

const formSettings = ref(null)
const pdfBusy = ref(false)
const pdfErr = ref('')

const fillPriceActing = ref(false)
const fillPriceMsg = ref('')
const deptActing = ref(false)
const deptMsg = ref('')
const deptRejectOpen = ref(false)
const deptRejectReason = ref('')
const copyRejectionFeedback = ref(false)
let copyRejectionTimer = null

const signedUploadErr = ref('')

const passengerDraft = ref(1)
const passengerSaving = ref(false)
const passengerPatchErr = ref('')
const resetCloneBusy = ref(false)

/** Đường dẫn SPA tới bảng giá (vd. /mng/pricing). */
const pricingAppPath = buildStaffPrefixedPath('/pricing')
const pricingModalOpen = ref(false)
const pricingModalLoading = ref(false)
const pricingModalError = ref('')
const pricingModalData = ref({
  passenger_fares: [],
  cargo_fares: [],
  notes: [],
})

watch(pricingModalOpen, async (open) => {
  if (!open) return
  pricingModalLoading.value = true
  pricingModalError.value = ''
  try {
    const d = await getReferencePricing()
    pricingModalData.value = {
      passenger_fares: Array.isArray(d.passenger_fares) ? d.passenger_fares : [],
      cargo_fares: Array.isArray(d.cargo_fares) ? d.cargo_fares : [],
      notes: Array.isArray(d.notes) ? d.notes : [],
    }
  } catch (e) {
    pricingModalError.value =
      typeof e?.response?.data?.message === 'string'
        ? e.response.data.message
        : t('request_detail.reference_pricing_load_error')
    pricingModalData.value = { passenger_fares: [], cargo_fares: [], notes: [] }
  } finally {
    pricingModalLoading.value = false
  }
})

const requestRefCode = computed(() => {
  const r = req.value
  if (!r?.id) return ''
  const d = r.created_at ? new Date(r.created_at) : new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  return `REQ-${y}${m}-${String(r.id).padStart(3, '0')}`
})

const rejectionBannerTitle = computed(() => {
  const r = req.value
  if (!r || r.status !== 'rejected') return ''
  if (r.trip_type !== 'door_to_door') {
    return t('request_detail.dept_rejected_banner_title')
  }
  return t('request_detail.rejected_banner_title_generic')
})

const canApprove = computed(
  () => auth.hasPermission('request.approve') || auth.hasPermission('trip.view_all'),
)

const canUploadAttachment = computed(() => auth.hasPermission('attachment.upload'))
const canDeleteAttachment = computed(() => auth.hasPermission('attachment.upload'))
const canManagePaper = computed(() => auth.hasPermission('request.paper.manage'))

const canOpenPricingManagePage = computed(() => auth.hasPermission('reference_pricing.manage'))

const pdfExportDisabled = computed(() => req.value?.status !== 'approved')

const timelineSteps = useRequestTimelineSteps(req, t, locale)

const showFillPriceSection = computed(
  () =>
    req.value?.status === 'pending' &&
    req.value?.trip_type !== 'door_to_door' &&
    auth.hasPermission('request.fill_price'),
)

const isDeptRequestDetailRoute = computed(() => route.name === 'deptRequestDetail')

const showDeptDecisionSection = computed(
  () =>
    isDeptRequestDetailRoute.value &&
    req.value?.status === 'price_filled' &&
    auth.hasPermission('request.approve_dept'),
)

const wizardPurpose = computed(() => {
  const p = req.value?.wizard_snapshot?.form?.purpose
  return p && String(p).trim() ? String(p).trim() : ''
})

const showApprovalDecisionPanel = computed(() => {
  const r = req.value
  if (!r) return false
  if (showDeptDecisionSection.value) return true
  return r.status === 'pending' && canApprove.value && r.trip_type === 'door_to_door'
})

const isCurrentUserRequester = computed(
  () =>
    auth.user?.id != null &&
    req.value?.requester_id != null &&
    Number(auth.user.id) === Number(req.value.requester_id),
)

const showSignedPaperSection = computed(
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

const passengerDispatcherOverride = computed(() => false)

const passengerDepartLocked = computed(() => true)

/** Tab số HS định kỳ trên màn điều vận: chỉ xem; người đề xuất cập nhật trên cổng (BM.03 / đề xuất phiếu). */
const showStudentCountTab = computed(() => req.value?.dispatch_request_template_id != null)

const showPassengerAdjustSection = computed(() => false)

const showResetCloneBtn = computed(() => {
  if (!isCurrentUserRequester.value || !auth.hasPermission('request.create')) return false
  return ['approved', 'rejected'].includes(String(req.value?.status || ''))
})

const DETAIL_TABS = ['route', 'form', 'students', 'docs']

const REQUEST_SECTION_IDS = {
  route: 'request-section-route',
  form: 'request-section-form',
  students: 'request-section-students',
  docs: 'request-section-docs',
}

const activeTab = ref('route')
const previewOpen = ref(false)
const previewAttachment = ref(null)

const reqForDocs = computed(() => req.value)
const {
  signedPaperAttachments,
  paperScans,
  generalAttachments,
  docsTabNeedsFocus,
  docsChecklist,
  docsProgressSteps,
} = useDispatchRequestDocs(reqForDocs)

const signedDocumentCurrent = computed(() => req.value?.signed_document?.current ?? null)

const { costEstimate } = useRequestCostEstimate(reqForDocs)

const docsNeedsPaperScan = computed(
  () =>
    req.value?.status === 'approved' &&
    paperScans.value.length === 0 &&
    canUploadAttachment.value,
)

const workflowCtx = computed(() => ({
  showFillPriceSection: showFillPriceSection.value,
  showDeptDecisionSection: showDeptDecisionSection.value,
  showPassengerAdjustSection: showPassengerAdjustSection.value,
  docsTabNeedsFocus: docsTabNeedsFocus.value,
  docsNeedsPaperScan: docsNeedsPaperScan.value,
}))

const { formTabActionCount, studentsTabActionCount, docsTabActionCount, todoItems: workflowTodoItems } = useRequestWorkflowSteps(
  reqForDocs,
  workflowCtx,
)

const docsHighlightAttachmentId = ref(null)
const focusHighlight = ref(null)

const FOCUS_TARGETS = {
  'fill-price': 'request-focus-fill-price',
  'dept-decision': 'request-focus-dept-decision',
  docs: 'request-docs-panel',
  'docs-upload': 'request-docs-upload-row',
  'passenger-adjust': 'request-focus-passenger',
}

function tabFromRouteQuery() {
  const q = route.query.tab
  if (typeof q !== 'string') return null
  if (q === 'students' && !showStudentCountTab.value) return null
  return DETAIL_TABS.includes(q) ? q : null
}

async function scrollToSection(tab) {
  await nextTick()
  const id = REQUEST_SECTION_IDS[tab]
  if (id) document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function setActiveTab(tab) {
  if (tab === 'students' && !showStudentCountTab.value) return
  activeTab.value = tab
  const nextQuery = { ...route.query, tab }
  if (route.query.tab !== tab) {
    router.replace({ query: nextQuery })
  }
  void scrollToSection(tab)
}

watch(
  () => route.query.tab,
  (tab) => {
    if (typeof tab === 'string' && DETAIL_TABS.includes(tab) && activeTab.value !== tab) {
      if (tab === 'students' && !showStudentCountTab.value) return
      activeTab.value = tab
      void scrollToSection(tab)
    }
  },
)

watch(docsTabNeedsFocus, (need, was) => {
  if (need && was !== true && req.value && tabFromRouteQuery() !== 'docs') {
    setActiveTab('docs')
  }
})

function openAttachmentPreview(a) {
  previewAttachment.value = a
  previewOpen.value = true
}

function closeAttachmentPreview() {
  previewOpen.value = false
  previewAttachment.value = null
}

/** Tab Biểu mẫu BM.03 — badge khi có hành động / cảnh báo cần xem (empty state trong tab). */
const approvalTabNeedsFocus = computed(() => {
  const r = req.value
  if (!r) return false
  return !!(
    r.dispatch_package_cost_alert ||
    r.dispatch_package_budget_alert ||
    showResetCloneBtn.value ||
    showFillPriceSection.value ||
    showDeptDecisionSection.value ||
    showSignedPaperSection.value
  )
})

watch(
  approvalTabNeedsFocus,
  (need, was) => {
    if (need && was !== true && req.value) setActiveTab('form')
  },
  { immediate: true },
)

const overviewScheduleLine = computed(() => {
  const r = req.value
  if (!r) return ''
  const window = `${fmtDateVi(r.depart_at)} · ${fmtTimeWindow(r.depart_at, r.arrive_by)}`
  const dist =
    costEstimate.value?.distanceLabel != null
      ? t('request_detail.distance_badge_approx', { label: costEstimate.value.distanceLabel })
      : ''
  return dist ? `${window} · ${dist}` : window
})

const routeSectionLead = computed(() => overviewScheduleLine.value)

const sectionNavItems = computed(() => {
  const items = [
    { id: 'route', label: t('request_detail.tab_route') },
    {
      id: 'form',
      label: t('request_detail.tab_form'),
      badge: formTabActionCount.value > 0 ? formTabActionCount.value : null,
      dot: formTabActionCount.value === 0 && approvalTabNeedsFocus.value,
    },
  ]
  if (showStudentCountTab.value) {
    items.push({
      id: 'students',
      label: t('request_detail.tab_students'),
      badge: studentsTabActionCount.value > 0 ? studentsTabActionCount.value : null,
    })
  }
  items.push({
    id: 'docs',
    label: t('request_detail.tab_docs'),
    badge: docsTabActionCount.value > 0 ? docsTabActionCount.value : null,
    dot: docsTabActionCount.value === 0 && docsTabNeedsFocus.value,
    dotTone: 'amber',
  })
  return items
})

const showRecurringBadge = computed(() => !!req.value?.dispatch_request_template_id)

const requesterSubtitle = computed(() => {
  const u = req.value?.wizard_snapshot?.form?.requester_unit
  if (u?.trim()) return u.trim()
  const code = req.value?.requester?.employee_code
  if (code) return t('request_detail.requester_employee_line', { code })
  return req.value?.requester?.email ?? '—'
})

/** Dòng phụ dưới tên trong sidebar — không lặp email (email nằm trong bảng chi tiết). */
const requesterAsideSubtitle = computed(() => {
  const u = req.value?.wizard_snapshot?.form?.requester_unit
  if (u?.trim()) return u.trim()
  const code = req.value?.requester?.employee_code
  if (code) return t('request_detail.requester_employee_line', { code })
  return ''
})

const requesterAsideFields = computed(() => {
  const r = req.value
  if (!r) return []
  const unitInHero = !!r.wizard_snapshot?.form?.requester_unit?.trim()
  const codeInHero = !unitInHero && !!r.requester?.employee_code?.trim()
  const rows = []
  const email = r.requester?.email?.trim()
  if (email) {
    rows.push({ key: 'email', label: t('request_detail.lbl_email'), value: email })
  }
  const phone = r.requester?.phone?.trim()
  if (phone) {
    rows.push({ key: 'phone', label: t('request_detail.lbl_phone'), value: phone })
  }
  const code = r.requester?.employee_code?.trim()
  if (code && !codeInHero) {
    rows.push({ key: 'code', label: t('request_detail.lbl_employee_code'), value: code })
  }
  const unit = r.wizard_snapshot?.form?.requester_unit?.trim()
  if (unit && !unitInHero) {
    rows.push({ key: 'unit', label: t('request_detail.lbl_requester_unit'), value: unit })
  }
  return rows
})

const requesterInitials = computed(() => {
  const name = req.value?.requester?.name?.trim() || ''
  if (!name) return '?'
  const parts = name.split(/\s+/).filter(Boolean)
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const routeSubFrom = computed(() => {
  const snap = req.value?.wizard_snapshot
  const row = snap?.cargoRows?.[0]
  if (row?.pickup_place?.trim()) return row.pickup_place.trim()
  if (row?.pickup_contact?.trim()) return row.pickup_contact.trim()
  return ''
})

const routeSubTo = computed(() => {
  const snap = req.value?.wizard_snapshot
  const row = snap?.cargoRows?.[0]
  if (row?.delivery_place?.trim()) return row.delivery_place.trim()
  if (row?.delivery_contact?.trim()) return row.delivery_contact.trim()
  return ''
})

const passengerOrCargoLine = computed(() => {
  const r = req.value
  if (!r) return '—'
  if (r.trip_type === 'cargo' && r.wizard_snapshot?.cargoRows?.length) {
    const weights = r.wizard_snapshot.cargoRows.map((x) => x.weight).filter((w) => String(w).trim())
    if (weights.length) {
      const joined = weights.join(', ')
      const hint = /tấn|kg|ton/i.test(joined) ? '' : t('request_detail.cargo_weight_declared_hint')
      return `${joined}${hint}`
    }
  }
  if (r.passenger_count != null && r.passenger_count > 0) {
    return t('request_detail.passengers_count_line', { n: r.passenger_count })
  }
  return '—'
})

function onWorkflowNavigate({ tab, focus }) {
  if (tab) setActiveTab(tab)
  if (focus) {
    router.replace({ query: { ...route.query, tab: tab || activeTab.value, focus } })
    scrollToFocus(focus)
  }
}

async function scrollToFocus(focus) {
  focusHighlight.value = focus
  await nextTick()
  const id = FOCUS_TARGETS[focus]
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  window.setTimeout(() => {
    if (focusHighlight.value === focus) focusHighlight.value = null
  }, 3000)
}

watch(
  () => route.query.focus,
  (focus) => {
    if (typeof focus === 'string' && FOCUS_TARGETS[focus]) {
      const tabQ = tabFromRouteQuery()
      if (tabQ) activeTab.value = tabQ
      scrollToFocus(focus)
    }
  },
  { immediate: true },
)

function requestDetailLocaleTag() {
  return locale.value === 'en' ? 'en-GB' : 'vi-VN'
}

function fmtStepDetail(v) {
  if (!v) return '—'
  try {
    const loc = requestDetailLocaleTag()
    const hour12 = locale.value === 'en'
    return new Date(v).toLocaleString(loc, {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12,
    })
  } catch {
    return '—'
  }
}

function fmt(v) {
  if (!v) return '—'
  const d = new Date(v)
  if (Number.isNaN(d.getTime())) return '—'
  const loc = requestDetailLocaleTag()
  const hour12 = locale.value === 'en'
  return d.toLocaleString(loc, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12,
  })
}

function fmtShort(v) {
  if (!v) return ''
  const d = new Date(v)
  if (Number.isNaN(d.getTime())) return ''
  const loc = requestDetailLocaleTag()
  const hour12 = locale.value === 'en'
  return d.toLocaleString(loc, { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit', hour12 })
}

function fmtDateVi(iso) {
  if (!iso) return '—'
  try {
    const loc = requestDetailLocaleTag()
    return new Date(iso).toLocaleDateString(loc, { day: '2-digit', month: '2-digit', year: 'numeric' })
  } catch {
    return '—'
  }
}

function fmtTimeWindow(depart, arrive) {
  if (!depart) return '—'
  const loc = requestDetailLocaleTag()
  const hour12 = locale.value === 'en'
  const opt = { hour: '2-digit', minute: '2-digit', hour12 }
  const a = new Date(depart).toLocaleTimeString(loc, opt)
  if (!arrive) return a
  const b = new Date(arrive).toLocaleTimeString(loc, opt)
  return `${a} - ${b}`
}

function formatVndCurrency(n) {
  const loc = requestDetailLocaleTag()
  const num = new Intl.NumberFormat(loc).format(Number(n))
  return `${num} ${t('dept.currency_suffix')}`
}

async function onResetCloneRequest() {
  if (!req.value?.id || resetCloneBusy.value) return
  resetCloneBusy.value = true
  try {
    const dr = await cloneDispatchRequest(req.value.id)
    await router.push({ name: 'dispatchRequestNew', query: { replace: String(dr.id) } })
  } catch (e) {
    showAppError(formatApiError(e, t('request_detail.reset_clone_fail')))
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
    showAppSuccess(t('request_detail.passenger_saved'))
    await load()
  } catch (e) {
    passengerPatchErr.value = formatApiError(e, t('request_detail.passenger_save_fail'))
  } finally {
    passengerSaving.value = false
  }
}

async function load() {
  loading.value = true
  try {
    const [dr, fs] = await Promise.all([
      getDispatchRequest(route.params.id),
      getDispatchFormSettings().catch(() => null),
    ])
    req.value = dr
    formSettings.value = fs
    paperForm.value.paper_reference = req.value?.paper_reference ?? ''
    paperForm.value.paper_received_at = req.value?.paper_received_at
      ? toDatetimeLocalValue(new Date(req.value.paper_received_at))
      : ''
    const actual = dr.student_count_actual ?? dr.passenger_count
    passengerDraft.value = Math.max(1, Math.min(999, Math.round(Number(actual) || 1)))
    passengerPatchErr.value = ''
    const tabQ = tabFromRouteQuery()
    if (tabQ) activeTab.value = tabQ
  } finally {
    loading.value = false
    await nextTick()
    void scrollToSection(activeTab.value)
  }
}

async function runOcr(attachmentId) {
  if (ocrBusy.value != null) {
    return
  }
  ocrErr.value = ''
  ocrBusy.value = attachmentId
  try {
    const updated = await runAttachmentOcr(attachmentId)
    if (updated?.ocr_status === 'queued') {
      showAppSuccess(t('request_detail.ocr_queued_toast'), '')
      await load()
      return
    }
    const list = req.value?.attachments
    if (Array.isArray(list)) {
      const idx = list.findIndex((x) => x.id === attachmentId)
      if (idx >= 0) {
        list[idx] = { ...list[idx], ...updated }
      } else {
        list.unshift(updated)
      }
    } else {
      await load()
    }
    showAppSuccess(t('request_detail.ocr_success_toast'), t('request_detail.toast_attachment_removed_title'))
  } catch (e) {
    ocrErr.value = formatApiError(e, t('request_detail.ocr_failed_fallback'))
    showAppError(ocrErr.value)
  } finally {
    ocrBusy.value = null
  }
}

function uploadPaperScan(file, onProgress) {
  return uploadAttachment({
    attachable_type: 'dispatch_request',
    attachable_id: Number(route.params.id),
    kind: 'paper_scan',
    file,
    onProgress,
  })
}

function uploadRequestDocument(file, onProgress) {
  return uploadAttachment({
    attachable_type: 'dispatch_request',
    attachable_id: Number(route.params.id),
    kind: 'request_attachment',
    file,
    onProgress,
  })
}

function highlightUploadedAttachment(payload) {
  const id = payload?.id ?? payload?.attachment?.id
  if (!id) return
  docsHighlightAttachmentId.value = id
  window.setTimeout(() => {
    docsHighlightAttachmentId.value = null
  }, 2500)
}

async function onDocUploaded(payload) {
  attachErr.value = ''
  await load()
  highlightUploadedAttachment(payload)
}

async function onPaperScanUploaded(payload) {
  await load()
  highlightUploadedAttachment(payload)
}

async function downloadFile(a) {
  attachErr.value = ''
  try {
    await downloadBinaryAttachmentFromApi(a.id, a.original_name || 'download')
  } catch (e) {
    attachErr.value = e?.response?.data?.message ?? t('request_detail.download_failed_fallback')
  }
}

async function removeAttachment(a) {
  if (!canDeleteAttachment.value) return
  const ok = await confirmAction({
    title: t('request_detail.delete_attachment_confirm_title'),
    message: t('request_detail.delete_attachment_confirm_message', {
      name: a.original_name || t('request_detail.delete_attachment_this_file'),
    }),
    confirmLabel: t('request_detail.delete_attachment_confirm_btn'),
    danger: true,
  })
  if (!ok) return
  attachErr.value = ''
  deletingId.value = a.id
  try {
    await deleteAttachment(a.id)
    await load()
    showAppSuccess(t('request_detail.toast_attachment_removed_msg'), t('request_detail.toast_attachment_removed_title'))
  } catch (e) {
    attachErr.value = e?.response?.data?.message ?? t('request_detail.attachment_delete_failed_fallback')
  } finally {
    deletingId.value = null
  }
}

async function doMarkPaper() {
  paperMsg.value = ''
  paperActing.value = true
  const wasReceived = req.value?.paper_status === 'received'
  try {
    const payload = {}
    if (wasReceived) {
      const ref = paperForm.value.paper_reference?.trim() ?? ''
      payload.paper_reference = ref === '' ? null : ref
    } else if (paperForm.value.paper_reference?.trim()) {
      payload.paper_reference = paperForm.value.paper_reference.trim()
    }
    if (paperForm.value.paper_received_at) {
      payload.paper_received_at = new Date(paperForm.value.paper_received_at).toISOString()
    }
    await markPaperReceived(route.params.id, payload)
    if (wasReceived) {
      showAppSuccess(t('request_detail.paper_saved_toast_msg'), t('request_detail.toast_attachment_removed_title'))
    } else {
      showAppSuccess(t('request_detail.paper_marked_toast_msg'), t('request_detail.toast_attachment_removed_title'))
    }
    await load()
  } catch (e) {
    paperMsg.value = e?.response?.data?.message ?? t('request_detail.paper_error_generic')
  } finally {
    paperActing.value = false
  }
}

async function doRevertPaper() {
  const ok = await confirmAction({
    title: t('request_detail.paper_revert_confirm_title'),
    message: t('request_detail.paper_revert_confirm_message'),
    confirmLabel: t('request_detail.paper_revert_confirm_btn'),
    danger: true,
  })
  if (!ok) return
  paperMsg.value = ''
  paperRevertActing.value = true
  try {
    await revertPaperReceived(route.params.id)
    showAppSuccess(t('request_detail.paper_reverted_toast_msg'), t('request_detail.toast_attachment_removed_title'))
    await load()
  } catch (e) {
    paperMsg.value = e?.response?.data?.message ?? t('request_detail.paper_error_generic')
  } finally {
    paperRevertActing.value = false
  }
}

async function onDecideClick(d) {
  if (d === 'approve') {
    const ok = await confirmAction({
      title: t('request_detail.d2d_approve_confirm_title'),
      message: t('request_detail.d2d_approve_confirm_message'),
      confirmLabel: t('request_detail.d2d_approve_confirm_btn'),
    })
    if (!ok) return
  } else {
    const ok = await confirmAction({
      title: t('request_detail.d2d_reject_confirm_title'),
      message: t('request_detail.d2d_reject_confirm_message'),
      confirmLabel: t('request_detail.d2d_reject_confirm_btn'),
      danger: true,
    })
    if (!ok) return
  }
  await decide(d)
}

async function decide(d) {
  msg.value = ''
  acting.value = true
  try {
    const res = await decideDispatchRequest(
      route.params.id,
      { decision: d, reason: d === 'reject' ? 'reject' : null },
      { idempotencyKey: newIdempotencyKey() },
    )
    msg.value = ''
    if (d === 'approve') {
      const code = requestRefCode.value || `REQ-${route.params.id}`
      const tripId = res?.trip?.id
      if (auth.isDeptHeadOnly()) {
        if (tripId) {
          await router.push({ name: 'deptDashboard' })
          showAppSuccess(t('requests_page.approve_success_body_trip', { code }), t('requests_page.approve_success_title'))
        } else {
          showAppSuccess(t('requests_page.approve_success_body', { code }), t('requests_page.approve_success_title'), {
            navigateTo: '/dept',
            primaryLabel: t('requests_page.approve_success_go_trips'),
          })
        }
      } else if (tripId) {
        await router.push(`/trips/${tripId}`)
        showAppSuccess(t('requests_page.approve_success_body_trip', { code }), t('requests_page.approve_success_title'))
      } else {
        showAppSuccess(t('requests_page.approve_success_body', { code }), t('requests_page.approve_success_title'), {
          navigateTo: '/trips',
          primaryLabel: t('requests_page.approve_success_go_trips'),
        })
      }
    } else {
      await load()
      showAppSuccess(t('requests_page.reject_success_body'), t('requests_page.reject_success_title'))
    }
  } catch (e) {
    msg.value = e?.response?.data?.message ?? 'Lỗi'
  } finally {
    acting.value = false
  }
}

async function downloadRequestPdf() {
  if (pdfExportDisabled.value || pdfBusy.value) return
  pdfBusy.value = true
  try {
    const blob = await exportDispatchRequestPdf(Number(route.params.id))
    saveAs(blob, `de-nghi-dieu-van-${route.params.id}.pdf`)
  } catch (e) {
    pdfErr.value = e?.response?.data?.message ?? t('request_detail.pdf_export_failed_fallback')
    window.alert(pdfErr.value)
  } finally {
    pdfBusy.value = false
  }
}

async function onSaveRowPrices(payload) {
  fillPriceMsg.value = ''
  const total = payload?.service_price
  const n = typeof total === 'number' ? total : Number(total)
  if (!Number.isFinite(n) || n < 0) {
    fillPriceMsg.value = 'Tổng giá không hợp lệ.'
    return
  }
  fillPriceActing.value = true
  try {
    await fillPriceDispatchRequest(Number(route.params.id), {
      service_price: n,
      rows: payload?.rows ?? [],
      dept_head_user_id: payload?.dept_head_user_id ?? null,
    })
    showAppSuccess(t('request_detail.fill_price_success'), 'Đã xử lý')
    await load()
  } catch (e) {
    fillPriceMsg.value = e?.response?.data?.message ?? 'Lỗi'
  } finally {
    fillPriceActing.value = false
  }
}

function openDeptReject() {
  deptRejectOpen.value = true
  deptRejectReason.value = ''
  deptMsg.value = ''
}

function closeDeptReject() {
  deptRejectOpen.value = false
  deptRejectReason.value = ''
  deptMsg.value = ''
}

async function submitDeptReject() {
  const reason = deptRejectReason.value.trim()
  if (!reason) {
    deptMsg.value = 'Vui lòng nhập lý do từ chối.'
    return
  }
  deptMsg.value = ''
  deptActing.value = true
  try {
    await deptDecideDispatchRequest(Number(route.params.id), {
      decision: 'reject',
      rejection_reason: reason,
    })
    closeDeptReject()
    await load()
    showAppSuccess(t('requests_page.reject_success_body'), t('requests_page.reject_success_title'))
  } catch (e) {
    deptMsg.value = e?.response?.data?.message ?? 'Lỗi'
  } finally {
    deptActing.value = false
  }
}

async function onDeptApproveClick() {
  const ok = await confirmAction({
    title: t('request_detail.dept_approve_confirm_title'),
    message: t('request_detail.dept_approve_confirm_message'),
    confirmLabel: t('request_detail.dept_approve'),
  })
  if (!ok) return
  deptMsg.value = ''
  deptActing.value = true
  try {
    const res = await deptDecideDispatchRequest(Number(route.params.id), { decision: 'approve' })
    const code = requestRefCode.value || `REQ-${route.params.id}`
    const tripId = res?.trip?.id
    if (auth.isDeptHeadOnly()) {
      if (tripId) {
        await router.push({ name: 'deptDashboard' })
        showAppSuccess(t('requests_page.approve_success_body_trip', { code }), t('requests_page.approve_success_title'))
      } else {
        showAppSuccess(t('requests_page.approve_success_body', { code }), t('requests_page.approve_success_title'), {
          navigateTo: '/dept',
          primaryLabel: t('requests_page.approve_success_go_trips'),
        })
      }
    } else if (tripId) {
      await router.push(`/trips/${tripId}`)
      showAppSuccess(t('requests_page.approve_success_body_trip', { code }), t('requests_page.approve_success_title'))
    } else {
      showAppSuccess(t('requests_page.approve_success_body', { code }), t('requests_page.approve_success_title'), {
        navigateTo: '/trips',
        primaryLabel: t('requests_page.approve_success_go_trips'),
      })
    }
  } catch (e) {
    deptMsg.value = e?.response?.data?.message ?? 'Lỗi'
  } finally {
    deptActing.value = false
  }
}

function uploadSignedPaper(file, onProgress) {
  signedUploadErr.value = ''
  return uploadAttachment({
    attachable_type: 'dispatch_request',
    attachable_id: Number(route.params.id),
    kind: 'signed_paper',
    file,
    onProgress,
  })
}

function onSignedUploaded() {
  signedUploadErr.value = ''
  load()
}

async function onSignedRerunOcr() {
  const id = signedDocumentCurrent.value?.id
  if (!id || signedOcrBusy.value) return
  signedOcrBusy.value = true
  ocrErr.value = ''
  try {
    await rerunSignedDocumentOcr(Number(id))
    showAppSuccess(t('request_detail.ocr_queued_toast'), '')
    await load()
  } catch (e) {
    ocrErr.value = formatApiError(e, t('request_detail.ocr_failed_fallback'))
    showAppError(ocrErr.value)
  } finally {
    signedOcrBusy.value = false
  }
}

async function onSignedVerify(decision) {
  const id = signedDocumentCurrent.value?.id
  if (!id || signedVerifyBusy.value) return
  signedVerifyBusy.value = true
  try {
    await verifySignedDocument(Number(id), decision)
    showAppSuccess(t('request_detail.signed_doc_verify_approve'), '')
    await load()
  } catch (e) {
    showAppError(formatApiError(e, t('request_detail.ocr_failed_fallback')))
  } finally {
    signedVerifyBusy.value = false
  }
}

function openPricingManagePage() {
  pricingModalOpen.value = false
  router.push(pricingAppPath)
}

onMounted(load)

onBeforeUnmount(() => {
  if (copyRejectionTimer) clearTimeout(copyRejectionTimer)
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
    showAppError(t('request_detail.copy_rejection_failed'))
  }
}
watch(() => route.params.id, load)
</script>
