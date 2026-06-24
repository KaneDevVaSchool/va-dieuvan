<template>
  <div
    class="flex min-h-0 w-full min-w-0 flex-1 flex-col overflow-y-auto overscroll-y-contain bg-slate-50 dark:bg-slate-950"
  >
    <div
      v-if="loading"
      class="flex flex-1 items-center justify-center px-4 py-16 text-base text-slate-500 dark:text-slate-400"
    >
      {{ t('request_detail.page_loading') }}
    </div>

    <template v-else-if="req">
      <StaffRequestHeroHeader
        :back-to="backTo"
        :back-aria-label="backAriaLabel"
        :request-ref-code="requestRefCode"
        :status="req.status"
        :priority-label="heroPriorityLabel"
        :origin="req.origin || ''"
        :destination="req.destination || ''"
        :depart-summary="heroDepartSummary"
        :trip-type="labelTripType(req.trip_type)"
        :passenger-line="passengerOrCargoLine || passengerOrCargoEmptyLabel"
        :requester-name="req.requester?.name || ''"
        :requester-unit="heroRequesterUnit"
        :requester-initials="requesterInitials"
        :requester-avatar="req.requester?.avatar_url || ''"
        :created-date="heroCreatedDate"
        :urgent-accent="showUrgentBadge"
        :show-recurring="showRecurringBadge"
        :pdf-busy="pdfBusy"
        :pdf-export-disabled="pdfExportDisabled"
        :show-approve-actions="showHeroApproveActions"
        :d2d-acting="heroActionBusy"
        :linked-trip="req.trip || null"
        @export-pdf="downloadRequestPdf"
        @approve="onHeroApprove"
        @reject="onHeroReject"
      />

      <StaffRequestDetailTabNav
        :tabs="sectionNavItems"
        :active-tab="activeTab"
        :aria-label="t('request_detail.tablist_aria')"
        @select="setActiveTab"
      />

      <!-- ═══════════ Body ═══════════ -->
      <div class="min-w-0 flex-1 px-4 sm:px-5 lg:px-6">
        <div class="w-full min-w-0 space-y-4 pb-16 pt-4">
          <!-- Alerts -->
          <div
            v-if="req.status === 'rejected'"
            class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-4 dark:border-rose-900/60 dark:bg-rose-950/40"
            role="alert"
          >
            <XCircleIcon class="h-7 w-7 shrink-0 text-rose-500 dark:text-rose-400" aria-hidden="true" />
            <div class="min-w-0 flex-1">
              <p class="text-base font-bold text-rose-900 dark:text-rose-200">{{ rejectionBannerTitle }}</p>
              <p
                v-if="req.rejection_reason"
                class="mt-1 whitespace-pre-wrap text-sm leading-relaxed text-rose-800/90 dark:text-rose-200/80"
              >
                {{ req.rejection_reason }}
              </p>
              <p v-else class="mt-1 text-sm text-rose-700/80 dark:text-rose-300/70">{{ t('request_detail.rejected_no_reason') }}</p>
              <button
                v-if="req.rejection_reason"
                type="button"
                class="mt-2.5 inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-sm font-semibold text-rose-800 transition hover:bg-rose-50 dark:border-rose-800 dark:bg-slate-900 dark:text-rose-200 dark:hover:bg-slate-800"
                @click="copyRejectionReason"
              >
                <ClipboardDocumentIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                {{ copyRejectionFeedback ? t('request_detail.copied') : t('request_detail.copy_rejection') }}
              </button>
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

          <!-- ─── Khối quyết định duyệt ─── -->
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

          <!-- ─── Tab panels ─── -->
          <div :class="cardClass">
            <!-- ===== Tab: Tổng quan ===== -->
            <div v-show="activeTab === 'form'" class="space-y-0">
              <StaffRequestOverviewPanel
                :req="req"
                :timeline-steps="timelineSteps"
                :cost-estimate="costEstimate"
              />

              <div v-if="showResetCloneBtn" class="p-4 sm:p-5">
                <ResetCloneSection :busy="resetCloneBusy" @clone="onResetCloneRequest" />
              </div>
            </div>

            <!-- ===== Tab: Học sinh ===== -->
            <div v-show="activeTab === 'students' && showStudentCountTab" class="p-4 sm:p-5">
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

            <!-- ===== Tab: Hồ sơ ===== -->
            <div v-show="activeTab === 'docs'" class="p-4 sm:p-5">
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
                    class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/40"
                  >
                    <h3 class="text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">
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
                        <span v-if="paperMsg" class="text-xs text-slate-600 dark:text-slate-400">{{ paperMsg }}</span>
                      </div>
                    </form>
                  </div>
                  <div
                    v-else-if="req.paper_status === 'received'"
                    class="rounded-xl border border-slate-100 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-800/40"
                  >
                    <h3 class="text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">
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
                        <span v-if="paperMsg" class="text-xs text-slate-600 dark:text-slate-400">{{ paperMsg }}</span>
                      </div>
                    </form>
                  </div>
                </template>
              </RequestDocsPanel>
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
import { computed, defineAsyncComponent } from 'vue'
import {
  ClipboardDocumentIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import CostLimitAlert from '../../components/requests/CostLimitAlert.vue'
import RequestCloneLineageBanner from '../../components/requests/RequestCloneLineageBanner.vue'
import ResetCloneSection from '../../components/requests/ResetCloneSection.vue'
import RequestDocsPanel from '../../components/requests/RequestDocsPanel.vue'
import RequestWorkflowBar from '../../components/requests/RequestWorkflowBar.vue'
import AttachmentPreviewModal from '../../components/requests/AttachmentPreviewModal.vue'
import DeptApprovalSection from '../../components/requests/DeptApprovalSection.vue'
import DispatchD2dDecisionSection from '../../components/requests/DispatchD2dDecisionSection.vue'
import RejectReasonModal from '../../components/requests/RejectReasonModal.vue'
import StaffRequestHeroHeader from '../../components/requests/detail/StaffRequestHeroHeader.vue'
import StaffRequestDetailTabNav from '../../components/requests/detail/StaffRequestDetailTabNav.vue'
import StaffRequestOverviewPanel from '../../components/requests/detail/StaffRequestOverviewPanel.vue'
import { useRequestDetailPage } from '../../composables/useRequestDetailPage'

const RequestStudentCountTab = defineAsyncComponent(() =>
  import('../../components/requests/RequestStudentCountTab.vue'),
)

const { t, locale } = useI18n()

const page = useRequestDetailPage()
const {
  route,
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
  requesterInitials,
  requesterAsideSubtitle,
  passengerOrCargoLine,
  passengerOrCargoEmptyLabel,
  showD2dDecisionSection,
  showDeptDecisionSection,
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
  showStudentCountTab,
  showResetCloneBtn,
  resetCloneBusy,
  onResetCloneRequest,
  signedPaperAttachments,
  downloadFile,
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

// ── Style tokens ──
const cardClass =
  'overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900'

// ── Hero header derived values ──
const heroPriorityLabel = computed(() =>
  showUrgentBadge.value
    ? t('request_detail.hero_priority_high')
    : t('request_detail.hero_priority_normal'),
)

const heroRequesterUnit = computed(
  () =>
    req.value?.wizard_snapshot?.form?.requester_unit?.trim() ||
    requesterAsideSubtitle.value ||
    '',
)

function _fmtHeroDate(isoStr) {
  if (!isoStr) return ''
  const d = new Date(isoStr)
  if (Number.isNaN(d.getTime())) return ''
  const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  const hour12 = locale.value === 'en'
  const time = d.toLocaleTimeString(loc, { hour: '2-digit', minute: '2-digit', hour12 })
  const date = d.toLocaleDateString(loc, { day: '2-digit', month: '2-digit', year: 'numeric' })
  return `${time} • ${date}`
}

const heroDepartSummary = computed(() => _fmtHeroDate(req.value?.depart_at))

const heroCreatedDate = computed(() => {
  const raw = req.value?.created_at
  if (!raw) return ''
  const d = new Date(raw)
  if (Number.isNaN(d.getTime())) return ''
  const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  return d.toLocaleDateString(loc, { day: '2-digit', month: '2-digit', year: 'numeric' })
})

// ── Hero approve/reject (ưu tiên quyết định của trưởng đơn vị) ──
const showHeroApproveActions = computed(
  () => showDeptDecisionSection.value || showD2dDecisionSection.value,
)
const heroActionBusy = computed(() => deptActing.value || d2dActing.value)

function onHeroApprove() {
  if (showDeptDecisionSection.value) onDeptApproveClick()
  else if (showD2dDecisionSection.value) onD2dApproveClick()
}

function onHeroReject() {
  if (showDeptDecisionSection.value) openDeptReject()
  else if (showD2dDecisionSection.value) openD2dReject()
}
</script>
