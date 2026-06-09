<template>
  <div
    class="flex min-h-0 w-full min-w-0 flex-1 flex-col overflow-hidden bg-gradient-to-b from-slate-50 via-white to-slate-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950"
  >
    <div
      v-if="loading"
      class="flex flex-1 items-center justify-center px-4 py-16 text-sm text-slate-500 dark:text-slate-400"
    >
      {{ t('request_detail.page_loading') }}
    </div>

    <template v-else-if="req">
      <!-- ───────────────── Sticky header ───────────────── -->
      <header
        class="sticky top-0 z-40 shrink-0 border-b border-slate-200/90 bg-white/95 shadow-sm backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/95 supports-[top:env(safe-area-inset-top)]:top-[env(safe-area-inset-top)]"
      >
        <div class="mx-auto flex max-w-[88rem] flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
          <div class="flex min-w-0 flex-1 items-center gap-3">
            <RouterLink
              :to="backTo"
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-600 dark:hover:bg-slate-800"
              :aria-label="backAriaLabel"
            >
              <ArrowLeftIcon class="h-5 w-5" aria-hidden="true" />
            </RouterLink>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h1 class="truncate font-mono text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl">
                  {{ requestRefCode }}
                </h1>
                <StatusBadge :status="req.status" />
                <span
                  v-if="showUrgentBadge"
                  class="inline-flex items-center gap-1 rounded-lg bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-800 ring-1 ring-rose-200/80 dark:bg-rose-950/60 dark:text-rose-300 dark:ring-rose-800/60"
                >
                  <BoltIcon class="h-3 w-3" aria-hidden="true" />
                  {{ t('requests_page.filter_priority_urgent') }}
                </span>
                <span
                  v-if="showRecurringBadge"
                  class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-900 ring-1 ring-indigo-200/80 dark:bg-indigo-950/60 dark:text-indigo-300 dark:ring-indigo-800/60"
                >
                  <ArrowPathIcon class="h-3 w-3" aria-hidden="true" />
                  {{ t('request_detail.badge_recurring') }}
                </span>
              </div>
              <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                {{ t('request_detail.meta_created', { dt: fmt(req.created_at) }) }}
                <template v-if="req.trip">
                  <span class="text-slate-300 dark:text-slate-600"> · </span>
                  <RouterLink
                    :to="`/trips/${req.trip.id}`"
                    class="font-semibold text-va-800 underline decoration-va-300 underline-offset-2 hover:decoration-va-600 dark:text-va-300 dark:decoration-va-500"
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
                ? 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-600'
                : 'border-slate-200 bg-white text-slate-800 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700'
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

      <!-- ───────────────── Scroll body ───────────────── -->
      <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain">
        <div class="mx-auto max-w-[88rem] space-y-5 px-4 py-5 pb-16 sm:px-6">
          <!-- Alerts -->
          <div
            v-if="req.status === 'rejected'"
            class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 dark:border-rose-900/60 dark:bg-rose-950/40 sm:p-5"
            role="alert"
          >
            <div class="flex items-start gap-3">
              <XCircleIcon class="h-8 w-8 shrink-0 text-rose-600 dark:text-rose-400" aria-hidden="true" />
              <div class="min-w-0 flex-1">
                <p class="text-base font-bold text-rose-950 dark:text-rose-200">{{ rejectionBannerTitle }}</p>
                <p
                  v-if="req.rejection_reason"
                  class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-rose-900/95 dark:text-rose-200/90"
                >
                  {{ req.rejection_reason }}
                </p>
                <p v-else class="mt-2 text-sm text-rose-800/90 dark:text-rose-300/80">
                  {{ t('request_detail.rejected_no_reason') }}
                </p>
                <button
                  v-if="req.rejection_reason"
                  type="button"
                  class="mt-3 inline-flex min-h-[40px] items-center gap-2 rounded-xl border border-rose-200 bg-white px-3 text-xs font-semibold text-rose-900 shadow-sm transition hover:bg-rose-50 dark:border-rose-800 dark:bg-slate-900 dark:text-rose-200 dark:hover:bg-slate-800"
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

          <!-- Workspace grid -->
          <div class="grid gap-5 lg:grid-cols-12 lg:items-start">
            <!-- ─────────── MAIN ─────────── -->
            <div class="space-y-5 lg:col-span-8">
              <section
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5"
              >
                <PortalStatusTimeline :title="t('portal.timeline_heading')" :steps="timelineSteps" />
              </section>

              <!-- Tabbed workspace -->
              <div
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
              >
                <nav
                  class="flex gap-1 overflow-x-auto border-b border-slate-100 bg-slate-50/80 px-2 py-2 dark:border-slate-800 dark:bg-slate-900/60 sm:px-3"
                  role="tablist"
                  :aria-label="t('request_detail.tablist_aria')"
                >
                  <button
                    v-for="tab in workspaceTabs"
                    :key="tab.id"
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === tab.id"
                    class="relative shrink-0 rounded-xl px-3.5 py-2.5 text-xs font-semibold transition sm:text-sm"
                    :class="
                      activeTab === tab.id
                        ? 'bg-va-800 text-white shadow-sm shadow-va-900/15'
                        : 'text-slate-600 hover:bg-white hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100'
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
                  <!-- Overview / BM.03 form -->
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
                  </div>

                  <!-- Documents -->
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

                  <!-- Students (recurring) -->
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

                  <!-- Activity / audit log -->
                  <div v-show="activeTab === 'activity'">
                    <RequestAuditTimeline
                      :items="auditItems"
                      :loading="auditLoading"
                      :error="auditError"
                      :format-date-time="fmt"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- ─────────── SIDEBAR ─────────── -->
            <aside class="space-y-4 lg:col-span-4">
              <!-- Action center -->
              <section
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900"
              >
                <h2
                  class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                >
                  {{ t('request_detail.ops_action_center') }}
                </h2>

                <div class="mt-3 space-y-3">
                  <DispatchD2dDecisionSection
                    v-if="showD2dDecisionSection"
                    :acting="d2dActing"
                    :inline-message="d2dMsg"
                    :reject-modal-open="d2dRejectOpen"
                    @approve="onD2dApproveClick"
                    @reject="openD2dReject"
                  />

                  <button
                    v-if="showFillPriceSection"
                    type="button"
                    class="flex w-full items-center gap-3 rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white px-4 py-3 text-left shadow-sm transition hover:from-sky-100 dark:border-sky-900/60 dark:from-sky-950/40 dark:to-slate-900 dark:hover:from-sky-950/70"
                    @click="onWorkflowNavigate({ tab: 'form', focus: 'fill-price' })"
                  >
                    <span
                      class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-white shadow"
                    >
                      <CurrencyDollarIcon class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <span class="min-w-0">
                      <span class="block text-sm font-semibold text-slate-900 dark:text-slate-100">
                        {{ t('request_detail.fill_price_title') }}
                      </span>
                      <span class="mt-0.5 block text-xs text-slate-600 dark:text-slate-400">
                        {{ t('request_detail.todo_fill_price') }}
                      </span>
                    </span>
                  </button>

                  <ResetCloneSection
                    v-if="showResetCloneBtn"
                    compact
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900"
                    :busy="resetCloneBusy"
                    @clone="onResetCloneRequest"
                  />

                  <!-- Next steps quick-nav -->
                  <div v-if="workflowTodoItems.length" class="space-y-2">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:text-amber-400">
                      {{ t('request_detail.aside_todos_heading') }}
                    </p>
                    <button
                      v-for="item in workflowTodoItems"
                      :key="item.key"
                      type="button"
                      class="flex w-full items-center justify-between gap-2 rounded-xl border border-amber-200/80 bg-amber-50/60 px-3 py-2 text-left text-xs font-semibold text-amber-950 transition hover:bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200 dark:hover:bg-amber-950/50"
                      @click="onWorkflowNavigate({ tab: item.tab, focus: item.focus })"
                    >
                      <span class="min-w-0 truncate">{{ item.label }}</span>
                      <ArrowRightIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                    </button>
                  </div>

                  <p
                    v-if="!hasAnyAction"
                    class="rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-3 py-4 text-center text-xs text-slate-500 dark:border-slate-700 dark:bg-slate-800/40 dark:text-slate-400"
                  >
                    {{ t('request_detail.ops_no_actions') }}
                  </p>
                </div>
              </section>

              <!-- Properties -->
              <section
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900"
              >
                <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('request_detail.ops_properties') }}
                </h2>

                <!-- Requester -->
                <div class="mt-3 flex items-start gap-3">
                  <img
                    v-if="req.requester?.avatar_url"
                    :src="req.requester.avatar_url"
                    alt=""
                    class="h-11 w-11 shrink-0 rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-700"
                  />
                  <div
                    v-else
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200"
                  >
                    {{ requesterInitials }}
                  </div>
                  <div class="min-w-0">
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ req.requester?.name ?? '—' }}</p>
                    <p v-if="requesterAsideSubtitle" class="mt-0.5 text-sm text-slate-600 dark:text-slate-400">
                      {{ requesterAsideSubtitle }}
                    </p>
                    <dl v-if="requesterAsideFields.length" class="mt-2 space-y-1.5 text-sm">
                      <div v-for="row in requesterAsideFields" :key="row.key" class="flex gap-2">
                        <dt class="shrink-0 text-slate-500 dark:text-slate-500">{{ row.label }}</dt>
                        <dd class="min-w-0 break-all text-slate-800 dark:text-slate-300">{{ row.value }}</dd>
                      </div>
                    </dl>
                  </div>
                </div>

                <!-- Route -->
                <div class="mt-4 border-t border-slate-100 pt-4 dark:border-slate-800">
                  <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-500">
                    {{ t('request_detail.route_map_heading') }}
                  </p>
                  <div class="mt-2 flex items-start gap-2">
                    <MapPinIcon class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-400" aria-hidden="true" />
                    <p class="min-w-0 text-sm font-medium text-slate-900 dark:text-slate-100">{{ req.origin || '—' }}</p>
                  </div>
                  <div class="ml-[7px] my-0.5 h-3 w-px bg-slate-200 dark:bg-slate-700" aria-hidden="true" />
                  <div class="flex items-start gap-2">
                    <MapPinIcon class="mt-0.5 h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400" aria-hidden="true" />
                    <p class="min-w-0 text-sm font-medium text-slate-900 dark:text-slate-100">{{ req.destination || '—' }}</p>
                  </div>
                  <p v-if="journeyDepartLine" class="mt-3 flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                    <ClockIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                    {{ journeyDepartLine }}
                  </p>
                </div>

                <!-- Vehicle / load -->
                <div class="mt-4 border-t border-slate-100 pt-4 dark:border-slate-800">
                  <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-500">
                    {{ t('request_detail.aside_vehicle_request') }}
                  </p>
                  <p class="mt-2 flex items-center gap-1.5 text-sm font-semibold text-slate-900 dark:text-slate-100">
                    <TruckIcon class="h-4 w-4 shrink-0 text-slate-400 dark:text-slate-500" aria-hidden="true" />
                    {{ labelTripType(req.trip_type) }}
                  </p>
                  <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ passengerOrCargoLine }}</p>
                </div>
              </section>

              <!-- Cost estimate -->
              <section
                v-if="costEstimate"
                class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900"
              >
                <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  <CalculatorIcon class="h-4 w-4 shrink-0 text-teal-600 dark:text-teal-400" aria-hidden="true" />
                  {{ t('request_detail.cost_estimate_heading') }}
                </h2>
                <dl class="mt-3 space-y-2 text-sm">
                  <div class="flex justify-between gap-3">
                    <dt class="text-slate-500 dark:text-slate-400">{{ t('request_detail.lbl_est_distance') }}</dt>
                    <dd class="text-right font-medium text-slate-900 dark:text-slate-100">
                      {{ costEstimate.distanceLabel ?? '—' }}
                    </dd>
                  </div>
                  <div
                    v-if="req.service_price != null"
                    class="flex justify-between gap-3"
                  >
                    <dt class="text-slate-500 dark:text-slate-400">{{ t('request_detail.lbl_dispatcher_unit_price') }}</dt>
                    <dd class="text-right font-medium text-violet-900 dark:text-violet-300">
                      {{ formatVndCurrency(req.service_price) }}
                    </dd>
                  </div>
                </dl>
                <div class="mt-3 rounded-xl bg-teal-50/90 px-3 py-3 ring-1 ring-teal-600/10 dark:bg-teal-950/40 dark:ring-teal-800/40">
                  <p class="text-[11px] font-medium text-teal-900/90 dark:text-teal-300/90">
                    {{ t('request_detail.total_per_declaration') }}
                  </p>
                  <p class="mt-0.5 text-lg font-bold tabular-nums text-teal-600 dark:text-teal-300">
                    {{ costEstimate.declaredTotalLabel ?? '—' }}
                  </p>
                </div>
              </section>

              <!-- Trip link -->
              <RouterLink
                v-if="req.trip"
                :to="`/trips/${req.trip.id}`"
                class="flex items-center justify-between gap-3 rounded-2xl border border-va-200 bg-va-50/60 px-4 py-3 shadow-sm transition hover:bg-va-50 dark:border-va-800/60 dark:bg-va-950/30 dark:hover:bg-va-950/50"
              >
                <span class="min-w-0">
                  <span class="block text-[11px] font-semibold uppercase tracking-wide text-va-700 dark:text-va-300">
                    {{ t('request_detail.ops_linked_trip') }}
                  </span>
                  <span class="mt-0.5 block text-sm font-bold text-va-900 dark:text-va-200">
                    {{ t('request_detail.trip_link', { id: req.trip.id }) }}
                  </span>
                </span>
                <ArrowTopRightOnSquareIcon class="h-5 w-5 shrink-0 text-va-700 dark:text-va-300" aria-hidden="true" />
              </RouterLink>
            </aside>
          </div>
        </div>
      </div>

      <!-- ───────────────── Modals ───────────────── -->
      <AttachmentPreviewModal
        :open="previewOpen"
        :attachment="previewAttachment"
        @close="closeAttachmentPreview"
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
import { RouterLink } from 'vue-router'
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowPathIcon,
  ArrowRightIcon,
  ArrowTopRightOnSquareIcon,
  BoltIcon,
  CalculatorIcon,
  ClipboardDocumentIcon,
  ClockIcon,
  CurrencyDollarIcon,
  MapPinIcon,
  TruckIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import CostLimitAlert from '../../components/requests/CostLimitAlert.vue'
import RequestCloneLineageBanner from '../../components/requests/RequestCloneLineageBanner.vue'
import ResetCloneSection from '../../components/requests/ResetCloneSection.vue'
import RequestDocsPanel from '../../components/requests/RequestDocsPanel.vue'
import RequestAuditTimeline from '../../components/requests/RequestAuditTimeline.vue'
import AttachmentPreviewModal from '../../components/requests/AttachmentPreviewModal.vue'
import DispatchD2dDecisionSection from '../../components/requests/DispatchD2dDecisionSection.vue'
import RejectReasonModal from '../../components/requests/RejectReasonModal.vue'
import PortalStatusTimeline from '../../components/portal/PortalStatusTimeline.vue'
import { useRequestDetailPage } from '../../composables/useRequestDetailPage'

const RequestBm03FormTab = defineAsyncComponent(() =>
  import('../../components/requests/RequestBm03FormTab.vue'),
)
const RequestStudentCountTab = defineAsyncComponent(() =>
  import('../../components/requests/RequestStudentCountTab.vue'),
)

const { t } = useI18n()

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
  journeyDepartLine,
  requesterInitials,
  requesterAsideSubtitle,
  requesterAsideFields,
  passengerOrCargoLine,
  showD2dDecisionSection,
  showFillPriceSection,
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
  auditItems,
  auditLoading,
  auditError,
} = page

/** Relabel composable nav items into workspace tabs + append activity tab. */
const TAB_LABEL_KEY = {
  form: 'request_detail.ops_tab_overview',
  students: 'request_detail.tab_students',
  docs: 'request_detail.tab_docs',
}

const workspaceTabs = computed(() => {
  const tabs = sectionNavItems.value.map((item) => ({
    ...item,
    label: t(TAB_LABEL_KEY[item.id] ?? '') || item.label,
  }))
  tabs.push({ id: 'activity', label: t('request_detail.audit_timeline_heading') })
  return tabs
})

const hasAnyAction = computed(
  () =>
    showD2dDecisionSection.value ||
    showFillPriceSection.value ||
    showResetCloneBtn.value ||
    workflowTodoItems.value.length > 0,
)
</script>
