<template>
  <div class="flex min-h-0 w-full min-w-0 flex-1 flex-col overflow-hidden bg-gradient-to-b from-slate-50 via-white to-slate-50">
    <div v-if="loading" class="flex flex-1 items-center justify-center px-4 py-16 text-sm text-slate-500">
      {{ t('request_detail.page_loading') }}
    </div>

    <template v-else-if="req">
      <header
        class="sticky top-0 z-40 shrink-0 border-b border-slate-200/90 bg-white/95 shadow-sm backdrop-blur-md supports-[top:env(safe-area-inset-top)]:top-[env(safe-area-inset-top)]"
      >
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
          <div class="flex min-w-0 flex-1 items-center gap-3">
            <RouterLink
              :to="backTo"
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
              :aria-label="backAriaLabel"
            >
              <ArrowLeftIcon class="h-5 w-5" aria-hidden="true" />
            </RouterLink>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h1 class="truncate font-mono text-lg font-bold tracking-tight text-slate-900 sm:text-xl">
                  {{ requestRefCode }}
                </h1>
                <StatusBadge :status="req.status" />
                <span
                  v-if="showRecurringBadge"
                  class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-900 ring-1 ring-indigo-200/80"
                >
                  <ArrowPathIcon class="h-3 w-3" aria-hidden="true" />
                  {{ t('request_detail.badge_recurring') }}
                </span>
                <span
                  v-if="showUrgentBadge"
                  class="inline-flex items-center gap-1 rounded-lg bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-800 ring-1 ring-rose-200/80"
                >
                  <BoltIcon class="h-3 w-3" aria-hidden="true" />
                  {{ t('requests_page.filter_priority_urgent') }}
                </span>
              </div>
              <p class="mt-1 text-xs text-slate-600">
                {{ t('request_detail.meta_created', { dt: fmt(req.created_at) }) }}
                <template v-if="req.trip">
                  <span class="text-slate-300"> · </span>
                  <RouterLink
                    :to="`/trips/${req.trip.id}`"
                    class="font-semibold text-va-800 underline decoration-va-300 underline-offset-2 hover:decoration-va-600"
                  >
                    {{ t('request_detail.trip_link', { id: req.trip.id }) }}
                  </RouterLink>
                </template>
              </p>
            </div>
          </div>
          <button
            type="button"
            class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border px-4 text-sm font-medium transition"
            :class="
              pdfExportDisabled
                ? 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400'
                : 'border-slate-200 bg-white text-slate-800 shadow-sm hover:bg-slate-50'
            "
            :disabled="pdfBusy || pdfExportDisabled"
            :title="pdfExportDisabled ? t('request_detail.pdf_locked_tooltip') : t('request_detail.export_pdf')"
            @click="downloadRequestPdf"
          >
            <ArrowDownTrayIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
            {{ pdfBusy ? t('request_detail.pdf_export_loading') : t('request_detail.export_pdf') }}
          </button>
        </div>
      </header>

      <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain">
        <div class="mx-auto max-w-7xl space-y-5 px-4 py-5 pb-12 sm:px-6">
          <PortalRequestJourneyCard
            :origin="req.origin || '—'"
            :destination="req.destination || '—'"
            :depart-at="journeyDepartLine"
            :trip-type-label="labelTripType(req.trip_type)"
          />

          <div
            v-if="req.status === 'rejected'"
            class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 sm:p-5"
            role="alert"
          >
            <div class="flex items-start gap-3">
              <XCircleIcon class="h-8 w-8 shrink-0 text-rose-600" aria-hidden="true" />
              <div class="min-w-0 flex-1">
                <p class="text-base font-bold text-rose-950">{{ rejectionBannerTitle }}</p>
                <p
                  v-if="req.rejection_reason"
                  class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-rose-900/95"
                >
                  {{ req.rejection_reason }}
                </p>
                <p v-else class="mt-2 text-sm text-rose-800/90">{{ t('request_detail.rejected_no_reason') }}</p>
                <button
                  v-if="req.rejection_reason"
                  type="button"
                  class="mt-3 inline-flex min-h-[40px] items-center gap-2 rounded-xl border border-rose-200 bg-white px-3 text-xs font-semibold text-rose-900 shadow-sm transition hover:bg-rose-50"
                  @click="copyRejectionReason"
                >
                  <ClipboardDocumentIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                  {{ copyRejectionFeedback ? t('request_detail.copied') : t('request_detail.copy_rejection') }}
                </button>
              </div>
            </div>
          </div>

          <RequestCloneLineageBanner
            v-if="req.cloned_from_summary"
            :summary="req.cloned_from_summary"
            :context="cloneBannerContext"
          />

          <CostLimitAlert
            v-if="req.dispatch_package_cost_alert || req.dispatch_package_budget_alert"
            :alert="req.dispatch_package_cost_alert"
            :budget-alert="req.dispatch_package_budget_alert"
          />

          <RequestWorkflowBar
            v-if="workflowTodoItems.length"
            :todos="workflowTodoItems"
            @navigate="onWorkflowNavigate"
          />

          <div class="grid gap-5 lg:grid-cols-12 lg:items-start">
            <div class="space-y-5 lg:col-span-8">
              <PortalStatusTimeline :title="t('portal.timeline_heading')" :steps="timelineSteps" />
            </div>
            <aside class="space-y-4 lg:col-span-4">
              <DispatchD2dDecisionSection
                v-if="showD2dDecisionSection"
                :acting="d2dActing"
                :inline-message="d2dMsg"
                :reject-modal-open="d2dRejectOpen"
                @approve="onD2dApproveClick"
                @reject="openD2dReject"
              />
              <DeptApprovalSection
                v-if="showDeptDecisionSection"
                id="request-focus-dept-decision"
                class="scroll-mt-24"
                :class="focusHighlight === 'dept-decision' ? 'ring-2 ring-amber-400/80 ring-offset-2' : ''"
                :declared-total-label="costEstimate?.declaredTotalLabel ?? null"
                :service-price-display="req.service_price != null ? formatVndCurrency(req.service_price) : null"
                :acting="deptActing"
                :inline-message="deptMsg"
                :reject-modal-open="deptRejectOpen"
                @approve="onDeptApproveClick"
                @reject="openDeptReject"
              />
              <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                  {{ t('request_detail.aside_requester') }}
                </h2>
                <div class="mt-3 flex items-start gap-3">
                  <img
                    v-if="req.requester?.avatar_url"
                    :src="req.requester.avatar_url"
                    alt=""
                    class="h-11 w-11 shrink-0 rounded-full object-cover ring-2 ring-slate-100"
                  />
                  <div
                    v-else
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-700"
                  >
                    {{ requesterInitials }}
                  </div>
                  <div class="min-w-0">
                    <p class="font-semibold text-slate-900">{{ req.requester?.name ?? '—' }}</p>
                    <p v-if="requesterAsideSubtitle" class="mt-0.5 text-sm text-slate-600">{{ requesterAsideSubtitle }}</p>
                    <dl v-if="requesterAsideFields.length" class="mt-3 space-y-1.5 text-sm">
                      <div v-for="row in requesterAsideFields" :key="row.key" class="flex gap-2">
                        <dt class="shrink-0 text-slate-500">{{ row.label }}</dt>
                        <dd class="min-w-0 break-all text-slate-800">{{ row.value }}</dd>
                      </div>
                    </dl>
                  </div>
                </div>
                <div class="mt-4 border-t border-slate-100 pt-4">
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                    {{ t('request_detail.aside_vehicle_request') }}
                  </p>
                  <p class="mt-1 text-sm font-semibold text-slate-900">{{ labelTripType(req.trip_type) }}</p>
                  <p class="mt-1 text-sm text-slate-600">{{ passengerOrCargoLine }}</p>
                  <p v-if="overviewScheduleLine" class="mt-2 text-xs text-slate-500">{{ overviewScheduleLine }}</p>
                </div>
              </section>
            </aside>
          </div>

          <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <nav
              class="flex gap-1 overflow-x-auto border-b border-slate-100 bg-slate-50/80 px-2 py-2 sm:px-3"
              role="tablist"
              :aria-label="t('request_detail.tablist_aria')"
            >
              <button
                v-for="tab in sectionNavItems"
                :key="tab.id"
                type="button"
                role="tab"
                :aria-selected="activeTab === tab.id"
                class="relative shrink-0 rounded-xl px-3.5 py-2.5 text-xs font-semibold transition sm:text-sm"
                :class="
                  activeTab === tab.id
                    ? 'bg-va-800 text-white shadow-sm shadow-va-900/15'
                    : 'text-slate-600 hover:bg-white hover:text-slate-900'
                "
                @click="setActiveTab(tab.id)"
              >
                {{ tab.label }}
                <span
                  v-if="tab.badge"
                  class="ml-1.5 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"
                >
                  {{ tab.badge }}
                </span>
                <span
                  v-else-if="tab.dot"
                  class="absolute right-1 top-1 h-2 w-2 rounded-full"
                  :class="tab.dotTone === 'amber' ? 'bg-amber-500' : 'bg-teal-500'"
                  aria-hidden="true"
                />
              </button>
            </nav>

            <div class="p-4 sm:p-6">
              <div v-show="activeTab === 'form'" class="space-y-5">
                <RequestBm03FormTab
                  :req="req"
                  :show-fill-price-section="showFillPriceSection"
                  :fill-price-acting="fillPriceActing"
                  :fill-price-msg="fillPriceMsg"
                  :signed-paper-attachments="signedPaperAttachments"
                  :signed-upload-component-key="`signed-${route.params.id}-${signedPaperAttachments.length}`"
                  :upload-signed-fn="uploadSignedPaper"
                  :signed-upload-err="signedUploadErr"
                  :show-signed-paper-section="showSignedPaperSection"
                  :signed-document-current="signedDocumentCurrent"
                  :approval-tab-needs-focus="approvalTabNeedsFocus"
                  @save-row-prices="onSaveRowPrices"
                  @download-signed="downloadFile"
                  @signed-uploaded="onSignedUploaded"
                />
                <ResetCloneSection v-if="showResetCloneBtn" :busy="resetCloneBusy" @clone="onResetCloneRequest" />
              </div>

              <div v-show="activeTab === 'students' && showStudentCountTab">
                <RequestStudentCountTab
                  v-model:passenger-draft="passengerDraft"
                  :req="req"
                  :passenger-saving="passengerSaving"
                  :passenger-patch-err="passengerPatchErr"
                  :passenger-depart-locked="passengerDepartLocked"
                  :passenger-dispatcher-override="passengerDispatcherOverride"
                  :depart-at-formatted="req.depart_at ? fmtStepDetail(req.depart_at) : ''"
                  :show-passenger-adjust-section="false"
                  @save-passenger="savePassengerDraft"
                />
              </div>

              <div v-show="activeTab === 'docs'">
                <RequestDocsPanel
                  id="request-docs-panel"
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
                    <div
                      v-if="req.paper_status === 'pending'"
                      class="rounded-xl border border-slate-100 bg-slate-50/80 p-4"
                    >
                      <h3 class="text-xs font-bold uppercase tracking-wide text-slate-600">
                        {{ t('request_detail.paper_confirm_received_title') }}
                      </h3>
                      <form class="mt-3 grid gap-3 sm:grid-cols-2" @submit.prevent="doMarkPaper">
                        <div class="sm:col-span-2">
                          <Input
                            v-model="paperForm.paper_reference"
                            :label="t('request_detail.paper_ref_input_label')"
                            :placeholder="t('request_detail.paper_ref_placeholder')"
                          />
                        </div>
                        <div class="sm:col-span-2">
                          <Input
                            v-model="paperForm.paper_received_at"
                            :label="t('request_detail.paper_received_at_input_label')"
                            type="datetime-local"
                          />
                        </div>
                        <div class="flex flex-wrap items-center gap-2 sm:col-span-2">
                          <Button :loading="paperActing" type="submit" class="!bg-teal-600 hover:!bg-teal-700">
                            {{ t('request_detail.paper_mark_received_btn') }}
                          </Button>
                          <span v-if="paperMsg" class="text-xs text-slate-600">{{ paperMsg }}</span>
                        </div>
                      </form>
                    </div>
                    <div
                      v-else-if="req.paper_status === 'received'"
                      class="rounded-xl border border-slate-100 bg-slate-50/80 p-4"
                    >
                      <h3 class="text-xs font-bold uppercase tracking-wide text-slate-600">
                        {{ t('request_detail.paper_update_section_title') }}
                      </h3>
                      <form class="mt-3 grid gap-3 sm:grid-cols-2" @submit.prevent="doMarkPaper">
                        <div class="sm:col-span-2">
                          <Input
                            v-model="paperForm.paper_reference"
                            :label="t('request_detail.paper_ref_input_label')"
                            :placeholder="t('request_detail.paper_ref_placeholder')"
                          />
                        </div>
                        <div class="sm:col-span-2">
                          <Input
                            v-model="paperForm.paper_received_at"
                            :label="t('request_detail.paper_received_at_input_label')"
                            type="datetime-local"
                          />
                        </div>
                        <div class="flex flex-wrap items-center gap-2 sm:col-span-2">
                          <Button :loading="paperActing" type="submit" class="!bg-teal-600 hover:!bg-teal-700">
                            {{ t('request_detail.paper_save_changes_btn') }}
                          </Button>
                          <Button
                            variant="secondary"
                            type="button"
                            class="!border-amber-200 !text-amber-900 hover:!bg-amber-50"
                            :disabled="paperActing || paperRevertActing"
                            @click="doRevertPaper"
                          >
                            {{ t('request_detail.paper_revert_btn') }}
                          </Button>
                          <span v-if="paperMsg" class="text-xs text-slate-600">{{ paperMsg }}</span>
                        </div>
                      </form>
                    </div>
                  </template>
                </RequestDocsPanel>
              </div>
            </div>
          </div>
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

      <RejectReasonModal
        :open="d2dRejectOpen"
        :reason="d2dRejectReason"
        :acting="d2dActing"
        :error-message="d2dMsg"
        :modal-title="t('request_detail.d2d_reject_confirm_title')"
        :modal-lead="t('request_detail.d2d_reject_confirm_message')"
        @update:reason="d2dRejectReason = $event"
        @close="closeD2dReject"
        @confirm="submitD2dReject"
      />
    </template>
  </div>
</template>

<script setup>
import { defineAsyncComponent } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowPathIcon,
  BoltIcon,
  ClipboardDocumentIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import CostLimitAlert from '../../components/requests/CostLimitAlert.vue'
import RequestCloneLineageBanner from '../../components/requests/RequestCloneLineageBanner.vue'
import ResetCloneSection from '../../components/requests/ResetCloneSection.vue'
import RequestDocsPanel from '../../components/requests/RequestDocsPanel.vue'
import RequestWorkflowBar from '../../components/requests/RequestWorkflowBar.vue'
import AttachmentPreviewModal from '../../components/requests/AttachmentPreviewModal.vue'
import DeptApprovalSection from '../../components/requests/DeptApprovalSection.vue'
import DispatchD2dDecisionSection from '../../components/requests/DispatchD2dDecisionSection.vue'
import RejectReasonModal from '../../components/requests/RejectReasonModal.vue'
import PortalRequestJourneyCard from '../../components/portal/PortalRequestJourneyCard.vue'
import PortalStatusTimeline from '../../components/portal/PortalStatusTimeline.vue'
import { useRequestDetailPage } from '../../composables/useRequestDetailPage'

const RequestBm03FormTab = defineAsyncComponent(() =>
  import('../../components/requests/RequestBm03FormTab.vue'),
)
const RequestStudentCountTab = defineAsyncComponent(() =>
  import('../../components/requests/RequestStudentCountTab.vue'),
)

const page = useRequestDetailPage()
const {
  route,
  t,
  labelTripType,
  req,
  loading,
  backTo,
  backAriaLabel,
  cloneBannerContext,
  requestRefCode,
  showRecurringBadge,
  showUrgentBadge,
  pdfExportDisabled,
  pdfBusy,
  downloadRequestPdf,
  sectionNavItems,
  activeTab,
  setActiveTab,
  rejectionBannerTitle,
  copyRejectionFeedback,
  copyRejectionReason,
  workflowTodoItems,
  onWorkflowNavigate,
  timelineSteps,
  journeyDepartLine,
  overviewScheduleLine,
  requesterInitials,
  requesterAsideSubtitle,
  requesterAsideFields,
  passengerOrCargoLine,
  showD2dDecisionSection,
  showDeptDecisionSection,
  showFillPriceSection,
  focusHighlight,
  costEstimate,
  formatVndCurrency,
  d2dActing,
  d2dMsg,
  d2dRejectOpen,
  d2dRejectReason,
  openD2dReject,
  closeD2dReject,
  submitD2dReject,
  onD2dApproveClick,
  deptActing,
  deptMsg,
  deptRejectOpen,
  deptRejectReason,
  openDeptReject,
  closeDeptReject,
  submitDeptReject,
  onDeptApproveClick,
  fillPriceActing,
  fillPriceMsg,
  onSaveRowPrices,
  showStudentCountTab,
  showResetCloneBtn,
  resetCloneBusy,
  onResetCloneRequest,
  signedPaperAttachments,
  showSignedPaperSection,
  signedUploadErr,
  uploadSignedPaper,
  onSignedUploaded,
  downloadFile,
  approvalTabNeedsFocus,
  passengerDraft,
  passengerSaving,
  passengerPatchErr,
  passengerDepartLocked,
  passengerDispatcherOverride,
  savePassengerDraft,
  fmt,
  fmtStepDetail,
  docsProgressSteps,
  docsChecklist,
  docsHighlightAttachmentId,
  generalAttachments,
  paperScans,
  attachErr,
  ocrErr,
  ocrBusy,
  deletingId,
  canUploadAttachment,
  canDeleteAttachment,
  canManagePaper,
  signedDocumentCurrent,
  signedOcrBusy,
  signedVerifyBusy,
  uploadRequestDocument,
  uploadPaperScan,
  openAttachmentPreview,
  removeAttachment,
  runOcr,
  onDocUploaded,
  onPaperScanUploaded,
  onSignedRerunOcr,
  onSignedVerify,
  paperForm,
  paperActing,
  paperRevertActing,
  paperMsg,
  doMarkPaper,
  doRevertPaper,
  previewOpen,
  previewAttachment,
  closeAttachmentPreview,
} = page
</script>
