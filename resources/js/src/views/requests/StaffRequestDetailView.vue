<template>
  <div class="flex min-h-0 w-full min-w-0 flex-1 flex-col overflow-hidden bg-slate-50 dark:bg-slate-950">
    <div
      v-if="loading"
      class="flex flex-1 items-center justify-center px-4 py-16 text-base text-slate-500 dark:text-slate-400"
    >
      {{ t('request_detail.page_loading') }}
    </div>

    <template v-else-if="req">
      <!-- ═══════════ Header ═══════════ -->
      <header
        class="sticky top-0 z-40 shrink-0 border-b border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95 supports-[top:env(safe-area-inset-top)]:top-[env(safe-area-inset-top)]"
      >
        <div class="mx-auto flex max-w-[84rem] flex-wrap items-center gap-3 px-4 py-3 sm:px-6">
          <RouterLink
            :to="backTo"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
            :aria-label="backAriaLabel"
          >
            <ArrowLeftIcon class="h-5 w-5" aria-hidden="true" />
          </RouterLink>

          <div class="flex min-w-0 flex-1 items-center gap-2.5">
            <h1 class="truncate font-mono text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl">
              {{ requestRefCode }}
            </h1>
            <StatusBadge :status="req.status" />
            <span
              v-if="showUrgentBadge"
              class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:ring-rose-800/60"
            >
              <BoltIcon class="h-3.5 w-3.5" aria-hidden="true" />
              {{ t('requests_page.filter_priority_urgent') }}
            </span>
            <span
              v-if="showRecurringBadge"
              class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-300 dark:ring-indigo-800/60"
            >
              <ArrowPathIcon class="h-3.5 w-3.5" aria-hidden="true" />
              {{ t('request_detail.badge_recurring') }}
            </span>
          </div>

          <button
            type="button"
            class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg border px-3.5 text-sm font-semibold transition"
            :class="
              pdfExportDisabled
                ? 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-600'
                : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'
            "
            :disabled="pdfBusy || pdfExportDisabled"
            :title="pdfExportDisabled ? t('request_detail.pdf_locked_tooltip') : t('request_detail.export_pdf')"
            @click="downloadRequestPdf"
          >
            <ArrowDownTrayIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
            <span class="hidden sm:inline">{{ pdfBusy ? t('request_detail.pdf_export_loading') : t('request_detail.export_pdf') }}</span>
          </button>
        </div>
      </header>

      <!-- ═══════════ Body ═══════════ -->
      <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain">
        <div class="mx-auto max-w-[84rem] space-y-4 px-4 py-5 pb-16 sm:px-6">
          <!-- Alerts -->
          <div
            v-if="req.status === 'rejected'"
            class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/60 dark:bg-rose-950/40"
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

          <div
            v-if="req.cloned_from_summary"
            class="flex items-center gap-2.5 rounded-xl border border-indigo-200 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-900/50 dark:bg-indigo-950/30 dark:text-indigo-200"
          >
            <ArrowPathIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
            <span class="min-w-0 flex-1">{{ cloneLineageText }}</span>
            <RouterLink
              v-if="req.cloned_from_summary.id"
              :to="`/requests/${req.cloned_from_summary.id}`"
              class="shrink-0 font-semibold underline decoration-indigo-300 underline-offset-2 hover:decoration-indigo-500"
            >{{ t('request_detail.clone_lineage_open_source') }}</RouterLink>
          </div>

          <div
            v-if="costAlertText"
            class="flex items-start gap-2.5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200"
            role="alert"
          >
            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0 text-amber-500 dark:text-amber-400" aria-hidden="true" />
            <span class="min-w-0 flex-1 whitespace-pre-line">{{ costAlertText }}</span>
          </div>

          <!-- Grid -->
          <div class="grid gap-4 lg:grid-cols-12 lg:items-start">
            <!-- ─── MAIN ─── -->
            <div class="space-y-4 lg:col-span-8">
              <div :class="cardClass">
                <nav
                  class="flex gap-1 overflow-x-auto border-b border-slate-100 px-2 pt-2 dark:border-slate-800"
                  role="tablist"
                >
                  <button
                    v-for="tab in workspaceTabs"
                    :key="tab.id"
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === tab.id"
                    class="relative -mb-px shrink-0 border-b-2 px-4 py-3 text-base font-semibold transition"
                    :class="
                      activeTab === tab.id
                        ? 'border-va-700 text-va-800 dark:border-va-400 dark:text-va-300'
                        : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'
                    "
                    @click="setActiveTab(tab.id)"
                  >
                    {{ tab.label }}
                    <span
                      v-if="tab.badge"
                      class="ml-1.5 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[11px] font-bold text-white"
                    >{{ tab.badge }}</span>
                    <span
                      v-else-if="tab.dot"
                      class="ml-1 inline-block h-2 w-2 rounded-full align-middle"
                      :class="tab.dotTone === 'amber' ? 'bg-amber-500' : 'bg-teal-500'"
                      aria-hidden="true"
                    />
                  </button>
                </nav>

                <div class="p-4 sm:p-6">
                  <!-- ===== Tab: Tổng quan ===== -->
                  <div v-show="activeTab === 'form'" class="space-y-5">
                    <!-- Journey -->
                    <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-5 dark:border-slate-800 dark:from-slate-800/40 dark:to-slate-900">
                      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="min-w-0 flex-1">
                          <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400">
                            <MapPinIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                            <span class="text-xs font-semibold uppercase tracking-wide">{{ t('request_detail.lbl_origin') }}</span>
                          </div>
                          <p class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ req.origin || friendlyEmpty }}</p>
                        </div>
                        <ArrowRightIcon class="hidden h-6 w-6 shrink-0 text-slate-300 dark:text-slate-600 sm:block" aria-hidden="true" />
                        <ArrowDownIcon class="h-5 w-5 shrink-0 text-slate-300 dark:text-slate-600 sm:hidden" aria-hidden="true" />
                        <div class="min-w-0 flex-1">
                          <div class="flex items-center gap-2 text-rose-600 dark:text-rose-400">
                            <MapPinIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                            <span class="text-xs font-semibold uppercase tracking-wide">{{ t('request_detail.lbl_destination') }}</span>
                          </div>
                          <p class="mt-1 text-lg font-bold text-slate-900 dark:text-white">{{ req.destination || friendlyEmpty }}</p>
                        </div>
                      </div>
                      <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-slate-200/70 pt-3 text-sm text-slate-600 dark:border-slate-700/70 dark:text-slate-400">
                        <span class="inline-flex items-center gap-1.5"><ClockIcon class="h-4 w-4 shrink-0" aria-hidden="true" />{{ journeyDepartLine || friendlyEmpty }}</span>
                        <span class="inline-flex items-center gap-1.5"><TruckIcon class="h-4 w-4 shrink-0" aria-hidden="true" />{{ labelTripType(req.trip_type) }}</span>
                        <span v-if="distanceText" class="inline-flex items-center gap-1.5"><MapIcon class="h-4 w-4 shrink-0" aria-hidden="true" />{{ distanceText }}</span>
                      </div>
                    </div>

                    <PortalStatusTimeline
                      :title="t('portal.timeline_heading')"
                      :steps="timelineSteps"
                      variant="staff"
                    />

                    <!-- Quick facts -->
                    <div class="rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                      <h2 :class="sectionTitleClass">{{ t('request_detail.ops_quick_facts') }}</h2>
                      <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                        <FieldRow v-for="f in quickFacts" :key="f.label" :label="f.label" :value="f.value" />
                      </dl>
                    </div>

                    <!-- Schedule + purpose -->
                    <div class="grid gap-4 sm:grid-cols-2">
                      <div class="rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                        <h2 :class="sectionTitleClass">{{ t('request_detail.ops_schedule_heading') }}</h2>
                        <dl class="mt-4 space-y-3.5">
                          <FieldRow :label="t('request_detail.ops_lbl_proposed_date')" :value="fmtDateOnly(formData.proposed_date)" />
                          <FieldRow :label="t('request_detail.ops_lbl_date_needed')" :value="fmtDateOnly(formData.date_needed)" />
                          <FieldRow :label="t('request_detail.ops_lbl_dispatch_window')" :value="journeyDepartLine" />
                          <FieldRow v-if="urgentReasonText" :label="t('request_detail.ops_lbl_urgent_reason')" :value="urgentReasonText" multiline />
                        </dl>
                      </div>
                      <div class="rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                        <h2 :class="sectionTitleClass">{{ t('request_detail.ops_purpose_heading') }}</h2>
                        <dl class="mt-4 space-y-3.5">
                          <FieldRow :label="t('request_detail.ops_lbl_purpose')" :value="nz(formData.purpose)" multiline />
                          <FieldRow :label="t('request_detail.ops_lbl_basis')" :value="basisText" multiline />
                        </dl>
                      </div>

                      <div v-if="targets.length || coordinatorName" class="rounded-2xl border border-slate-200 p-5 dark:border-slate-800 sm:col-span-2">
                        <h2 :class="sectionTitleClass">{{ t('request_detail.ops_targets_heading') }}</h2>
                        <div v-if="targets.length" class="mt-4 flex flex-wrap gap-2">
                          <span v-for="(tg, i) in targets" :key="i" class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-sm font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ tg }}</span>
                        </div>
                        <dl v-if="coordinatorName" class="mt-4 grid gap-x-6 gap-y-3.5 sm:grid-cols-3">
                          <FieldRow :label="t('request_detail.ops_lbl_coordinator')" :value="coordinatorName" />
                          <FieldRow :label="t('request_detail.lbl_email')" :value="nz(formData.coordinator_email)" />
                          <FieldRow :label="t('request_detail.lbl_phone')" :value="nz(formData.coordinator_phone)" />
                        </dl>
                      </div>
                    </div>
                  </div>

                  <!-- ===== Tab: Chi tiết chuyến ===== -->
                  <div v-show="activeTab === 'route'" class="space-y-4">
                    <FillPricePanel
                      v-if="showFillPriceSection"
                      :req="req"
                      :acting="fillPriceActing"
                      :message="fillPriceMsg"
                      @save="onSaveRowPrices"
                    />

                    <template v-else>
                      <h2 :class="sectionTitleClass">{{ t('request_detail.ops_itinerary_heading') }}</h2>
                      <p
                        v-if="!itineraryCards.length"
                        class="rounded-2xl border border-dashed border-slate-200 px-4 py-12 text-center text-base text-slate-400 dark:border-slate-700 dark:text-slate-500"
                      >{{ t('request_detail.ops_no_itinerary') }}</p>
                      <div
                        v-for="card in itineraryCards"
                        :key="card.key"
                        class="rounded-2xl border border-slate-200 p-5 dark:border-slate-800"
                      >
                        <div class="flex flex-wrap items-start justify-between gap-2">
                          <div class="flex min-w-0 flex-1 items-start gap-3">
                            <span
                              class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-va-50 text-sm font-bold text-va-800 dark:bg-va-950/50 dark:text-va-300"
                              :aria-label="t('request_detail.ops_row_badge_aria', { n: card.idx })"
                            >
                              {{ card.idx }}
                            </span>
                            <div class="min-w-0 flex-1 space-y-1">
                              <p class="text-base font-semibold leading-snug text-slate-900 dark:text-white">
                                {{ card.title }}
                              </p>
                              <ul
                                v-if="card.summaryLines.length"
                                class="space-y-0.5 text-sm leading-snug text-slate-600 dark:text-slate-400"
                              >
                                <li v-for="(line, li) in card.summaryLines" :key="li" class="break-words">{{ line }}</li>
                              </ul>
                            </div>
                          </div>
                          <span v-if="card.price" class="shrink-0 text-base font-bold tabular-nums text-teal-600 dark:text-teal-400">{{ card.price }}</span>
                        </div>
                        <dl v-if="card.fields.length" class="mt-4 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                          <FieldRow v-for="(f, fi) in card.fields" :key="fi" :label="f.label" :value="f.value" :multiline="f.multiline" />
                        </dl>
                      </div>

                      <div v-if="extraNotes.length" class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5 dark:border-slate-800 dark:bg-slate-800/30">
                        <h3 :class="sectionTitleClass">{{ t('request_detail.ops_extra_notes_heading') }}</h3>
                        <ul class="mt-3 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                          <li v-for="(n, ni) in extraNotes" :key="ni" class="flex gap-2">
                            <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400" aria-hidden="true" />
                            <span class="min-w-0">{{ n }}</span>
                          </li>
                        </ul>
                      </div>
                    </template>
                  </div>

                  <!-- ===== Tab: Hồ sơ ===== -->
                  <div v-show="activeTab === 'docs'" class="space-y-4">
                    <p v-if="uploadErrLocal || attachErr || ocrErr" class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                      {{ uploadErrLocal || attachErr || ocrErr }}
                    </p>

                    <DocGroup
                      :title="t('request_detail.ops_docs_general')"
                      :files="generalAttachments"
                      :highlight-id="docsHighlightAttachmentId"
                      :deleting-id="deletingId"
                      :can-delete="canDeleteAttachment"
                      :can-ocr="canUploadAttachment"
                      :can-upload="canUploadAttachment"
                      :uploading="uploadingGeneral"
                      :fmt-size="fmtSize"
                      :fmt-date="fmt"
                      :previewable="isPreviewable"
                      @preview="openAttachmentPreview"
                      @download="downloadFile"
                      @delete="removeAttachment"
                      @ocr="runOcr"
                      @pick="onPickGeneral"
                    />

                    <DocGroup
                      :title="t('request_detail.ops_docs_signed')"
                      :files="signedPaperAttachments"
                      :deleting-id="deletingId"
                      :can-delete="canDeleteAttachment"
                      :can-upload="false"
                      :fmt-size="fmtSize"
                      :fmt-date="fmt"
                      :previewable="isPreviewable"
                      @preview="openAttachmentPreview"
                      @download="downloadFile"
                      @delete="removeAttachment"
                    >
                      <div v-if="signedDocumentCurrent" class="mt-3 flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-slate-50/70 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/40">
                        <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">{{ t('request_detail.ops_signed_doc_status') }}</span>
                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold" :class="signedVerifyBadgeClass">{{ signedVerifyLabel }}</span>
                        <div v-if="canManagePaper" class="ml-auto flex flex-wrap gap-2">
                          <button type="button" :class="btnGhostClass" :disabled="signedOcrBusy" @click="onSignedRerunOcr">{{ signedOcrBusy ? t('request_detail.docs_ocr_running') : t('request_detail.ops_rerun_ocr') }}</button>
                          <button type="button" :class="btnTealClass" :disabled="signedVerifyBusy" @click="onSignedVerify('approve')">{{ t('request_detail.ops_verify_pass') }}</button>
                          <button type="button" :class="btnDangerGhostClass" :disabled="signedVerifyBusy" @click="onSignedVerify('reject')">{{ t('request_detail.ops_verify_fail') }}</button>
                        </div>
                      </div>
                    </DocGroup>

                    <DocGroup
                      :title="t('request_detail.ops_docs_scan')"
                      :files="paperScans"
                      :deleting-id="deletingId"
                      :can-delete="canDeleteAttachment"
                      :can-upload="canUploadAttachment"
                      :uploading="uploadingScan"
                      :fmt-size="fmtSize"
                      :fmt-date="fmt"
                      :previewable="isPreviewable"
                      @preview="openAttachmentPreview"
                      @download="downloadFile"
                      @delete="removeAttachment"
                      @pick="onPickScan"
                    >
                      <form
                        v-if="canManagePaper && (req.paper_status === 'pending' || req.paper_status === 'received')"
                        class="mt-3 space-y-3 rounded-lg border border-slate-200 bg-slate-50/70 p-3 dark:border-slate-700 dark:bg-slate-800/40"
                        @submit.prevent="doMarkPaper"
                      >
                        <p class="text-sm font-bold text-slate-600 dark:text-slate-300">
                          {{ req.paper_status === 'received' ? t('request_detail.paper_update_section_title') : t('request_detail.paper_confirm_received_title') }}
                        </p>
                        <Input v-model="paperForm.paper_reference" :label="t('request_detail.paper_ref_input_label')" :placeholder="t('request_detail.paper_ref_placeholder')" />
                        <Input v-model="paperForm.paper_received_at" :label="t('request_detail.paper_received_at_input_label')" type="datetime-local" />
                        <div class="flex flex-wrap items-center gap-2">
                          <Button :loading="paperActing" type="submit" class="!bg-teal-600 hover:!bg-teal-700">
                            {{ req.paper_status === 'received' ? t('request_detail.paper_save_changes_btn') : t('request_detail.paper_mark_received_btn') }}
                          </Button>
                          <Button v-if="req.paper_status === 'received'" variant="secondary" type="button" class="!border-amber-200 !text-amber-900 hover:!bg-amber-50" :disabled="paperActing || paperRevertActing" @click="doRevertPaper">
                            {{ t('request_detail.paper_revert_btn') }}
                          </Button>
                          <span v-if="paperMsg" class="text-sm text-slate-500 dark:text-slate-400">{{ paperMsg }}</span>
                        </div>
                      </form>
                    </DocGroup>
                  </div>

                  <!-- ===== Tab: Học sinh ===== -->
                  <div v-show="activeTab === 'students' && showStudentCountTab" class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                      <p :class="sectionTitleClass">{{ t('request_detail.bm03_student_count_plan_short') }}</p>
                      <p class="mt-2 text-4xl font-bold tabular-nums text-slate-900 dark:text-white">{{ req.passenger_count ?? friendlyEmpty }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                      <p :class="sectionTitleClass">{{ t('request_detail.bm03_student_count_actual_short') }}</p>
                      <p class="mt-2 text-4xl font-bold tabular-nums text-teal-600 dark:text-teal-400">{{ req.student_count_actual ?? friendlyEmpty }}</p>
                    </div>
                  </div>

                  <!-- ===== Tab: Nhật ký ===== -->
                  <div v-show="activeTab === 'activity'">
                    <p v-if="auditLoading" class="py-10 text-center text-base text-slate-400 dark:text-slate-500">{{ t('request_detail.audit_timeline_loading') }}</p>
                    <p v-else-if="auditError" class="py-4 text-sm text-rose-600 dark:text-rose-400">{{ auditError }}</p>
                    <p v-else-if="!auditItems.length" class="py-10 text-center text-base text-slate-400 dark:text-slate-500">{{ t('request_detail.audit_timeline_empty') }}</p>
                    <ol v-else class="space-y-1">
                      <li
                        v-for="row in auditItems"
                        :key="row.id"
                        class="flex items-start gap-3 rounded-xl border border-slate-100 px-3.5 py-3 dark:border-slate-800"
                      >
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full" :class="auditMeta(row.event).cls">
                          <component :is="auditMeta(row.event).icon" class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0 flex-1">
                          <p class="text-base font-semibold text-slate-800 dark:text-slate-100">{{ auditEventLabel(row.event) }}</p>
                          <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                            <span class="font-medium text-slate-600 dark:text-slate-300">{{ row.actor?.name ?? t('request_detail.audit_actor_system') }}</span>
                            <span class="text-slate-300 dark:text-slate-600"> · </span>{{ fmt(row.created_at) }}
                          </p>
                        </div>
                      </li>
                    </ol>
                  </div>
                </div>
              </div>
            </div>

            <!-- ─── SIDEBAR ─── -->
            <aside class="space-y-4 lg:col-span-4">
              <section :class="cardClass + ' p-5'">
                <h2 :class="sectionTitleClass">{{ t('request_detail.ops_action_center') }}</h2>
                <div class="mt-4 space-y-2.5">
                  <div v-if="showD2dDecisionSection" class="space-y-2">
                    <button type="button" class="flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-3 text-base font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-60" :disabled="d2dActing" @click="onD2dApproveClick">
                      <CheckBadgeIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
                      {{ t('request_detail.d2d_approve_confirm_btn') }}
                    </button>
                    <button type="button" class="flex w-full items-center justify-center gap-2 rounded-lg border border-rose-200 px-4 py-3 text-base font-semibold text-rose-700 transition hover:bg-rose-50 disabled:opacity-60 dark:border-rose-800/60 dark:text-rose-300 dark:hover:bg-rose-950/40" :disabled="d2dActing" @click="openD2dReject">
                      {{ t('request_detail.d2d_reject_confirm_btn') }}
                    </button>
                    <p v-if="d2dMsg && !d2dRejectOpen" class="text-sm text-rose-600 dark:text-rose-400">{{ d2dMsg }}</p>
                  </div>

                  <button
                    v-if="showFillPriceSection"
                    type="button"
                    class="flex w-full items-center gap-3 rounded-lg border border-sky-200 bg-sky-50/70 px-3.5 py-3 text-left transition hover:bg-sky-100/70 dark:border-sky-900/50 dark:bg-sky-950/30 dark:hover:bg-sky-950/50"
                    @click="onWorkflowNavigate({ tab: 'route', focus: 'fill-price' })"
                  >
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-600 text-white"><CurrencyDollarIcon class="h-5 w-5" aria-hidden="true" /></span>
                    <span class="min-w-0">
                      <span class="block text-base font-semibold text-slate-900 dark:text-slate-100">{{ t('request_detail.fill_price_title') }}</span>
                      <span class="block truncate text-sm text-slate-500 dark:text-slate-400">{{ t('request_detail.todo_fill_price') }}</span>
                    </span>
                  </button>

                  <button
                    v-if="showResetCloneBtn"
                    type="button"
                    class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-3 text-base font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-60 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                    :disabled="resetCloneBusy"
                    @click="onResetCloneRequest"
                  >
                    <ArrowPathIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
                    {{ resetCloneBusy ? t('request_detail.reset_clone_busy') : t('request_detail.reset_clone') }}
                  </button>

                  <div v-if="workflowTodoItems.length" class="space-y-1.5 pt-1">
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-600 dark:text-amber-400">{{ t('request_detail.aside_todos_heading') }}</p>
                    <button
                      v-for="item in workflowTodoItems"
                      :key="item.key"
                      type="button"
                      class="flex w-full items-center justify-between gap-2 rounded-lg border border-amber-200/70 bg-amber-50/50 px-3.5 py-2.5 text-left text-sm font-semibold text-amber-900 transition hover:bg-amber-50 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200 dark:hover:bg-amber-950/40"
                      @click="onWorkflowNavigate({ tab: mapTodoTab(item.tab), focus: item.focus })"
                    >
                      <span class="min-w-0 truncate">{{ item.label }}</span>
                      <ArrowRightIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                    </button>
                  </div>

                  <p v-if="!hasAnyAction" class="rounded-lg border border-dashed border-slate-200 px-3 py-6 text-center text-sm text-slate-400 dark:border-slate-700 dark:text-slate-500">{{ t('request_detail.ops_no_actions') }}</p>
                </div>
              </section>

              <section :class="cardClass + ' p-5'">
                <h2 :class="sectionTitleClass">{{ t('request_detail.ops_properties') }}</h2>
                <div class="mt-4 flex items-start gap-3">
                  <img v-if="req.requester?.avatar_url" :src="req.requester.avatar_url" alt="" class="h-11 w-11 shrink-0 rounded-full object-cover ring-2 ring-slate-100 dark:ring-slate-700" />
                  <div v-else class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-va-50 text-base font-bold text-va-800 dark:bg-va-950/50 dark:text-va-300">{{ requesterInitials }}</div>
                  <div class="min-w-0">
                    <p class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ req.requester?.name ?? friendlyEmpty }}</p>
                    <p v-if="requesterAsideSubtitle" class="text-sm text-slate-500 dark:text-slate-400">{{ requesterAsideSubtitle }}</p>
                  </div>
                </div>
                <dl v-if="requesterAsideFields.length" class="mt-4 space-y-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                  <FieldRow v-for="row in requesterAsideFields" :key="row.key" :label="row.label" :value="row.value" />
                </dl>
              </section>

              <section v-if="costEstimate" :class="cardClass + ' p-5'">
                <h2 class="flex items-center gap-2" :class="sectionTitleClass">
                  <CalculatorIcon class="h-4 w-4 shrink-0 text-teal-500 dark:text-teal-400" aria-hidden="true" />
                  {{ t('request_detail.cost_estimate_heading') }}
                </h2>
                <dl class="mt-4 space-y-3">
                  <FieldRow :label="t('request_detail.lbl_est_distance')" :value="costEstimate.distanceLabel" />
                  <FieldRow v-if="req.service_price != null" :label="t('request_detail.lbl_dispatcher_unit_price')" :value="formatVndCurrency(req.service_price)" />
                </dl>
                <div class="mt-4 rounded-xl bg-teal-50 px-4 py-3 dark:bg-teal-950/30">
                  <p class="text-xs font-medium text-teal-700/90 dark:text-teal-300/90">{{ t('request_detail.total_per_declaration') }}</p>
                  <p class="mt-0.5 text-xl font-bold tabular-nums text-teal-600 dark:text-teal-300">{{ costEstimate.declaredTotalLabel ?? friendlyEmpty }}</p>
                </div>
              </section>

              <RouterLink
                v-if="req.trip"
                :to="`/trips/${req.trip.id}`"
                class="flex items-center justify-between gap-3 rounded-2xl border border-va-200 bg-va-50/60 px-4 py-3.5 transition hover:bg-va-50 dark:border-va-800/50 dark:bg-va-950/30 dark:hover:bg-va-950/50"
              >
                <span class="min-w-0">
                  <span class="block text-xs font-semibold uppercase tracking-wide text-va-600 dark:text-va-400">{{ t('request_detail.ops_linked_trip') }}</span>
                  <span class="block text-base font-bold text-va-900 dark:text-va-200">{{ t('request_detail.trip_link', { id: req.trip.id }) }}</span>
                </span>
                <ArrowTopRightOnSquareIcon class="h-5 w-5 shrink-0 text-va-600 dark:text-va-400" aria-hidden="true" />
              </RouterLink>
            </aside>
          </div>
        </div>
      </div>

      <AttachmentPreviewModal :open="previewOpen" :attachment="previewAttachment" @close="closeAttachmentPreview" />
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
import { computed, defineAsyncComponent, h, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ArrowDownIcon,
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowPathIcon,
  ArrowRightIcon,
  ArrowTopRightOnSquareIcon,
  ArrowUturnLeftIcon,
  BoltIcon,
  CalculatorIcon,
  CheckBadgeIcon,
  CheckCircleIcon,
  ClipboardDocumentIcon,
  ClockIcon,
  CurrencyDollarIcon,
  DocumentCheckIcon,
  ExclamationTriangleIcon,
  MapIcon,
  MapPinIcon,
  PencilSquareIcon,
  PlusCircleIcon,
  TruckIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import {
  ArrowDownTrayIcon as ArrowDownTraySolid,
  EyeIcon,
  SparklesIcon,
  TrashIcon,
} from '@heroicons/vue/24/solid'
import { useI18n } from 'vue-i18n'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import AttachmentPreviewModal from '../../components/requests/AttachmentPreviewModal.vue'
import RejectReasonModal from '../../components/requests/RejectReasonModal.vue'
import PortalStatusTimeline from '../../components/portal/PortalStatusTimeline.vue'
import { useRequestDetailPage } from '../../composables/useRequestDetailPage'
import { parseMoneyVnd } from '../../util/money'
import {
  itineraryRowEndpoints,
  itineraryRowHeading,
  itineraryRowSummaryLines,
  resolveItineraryTripType,
} from '../../util/requestItineraryRowDisplay'
import { isPassengerRowFilled, isBusinessRowFilled } from '../../composables/dispatchWizardConstants'

const FillPricePanel = defineAsyncComponent(() =>
  import('../../components/requests/workspace/FillPricePanel.vue'),
)

const { t } = useI18n()

const page = useRequestDetailPage()
const {
  labelTripType,
  req,
  loading,
  backTo,
  backAriaLabel,
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
  downloadFile,
  fmt,
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

// ── Style tokens ──
const cardClass = 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900'
const sectionTitleClass = 'text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400'
const btnGhostClass = 'inline-flex items-center rounded-md border border-slate-200 px-2.5 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800'
const btnTealClass = 'inline-flex items-center rounded-md bg-teal-600 px-2.5 py-1.5 text-sm font-semibold text-white transition hover:bg-teal-700 disabled:opacity-50'
const btnDangerGhostClass = 'inline-flex items-center rounded-md border border-rose-200 px-2.5 py-1.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 disabled:opacity-50 dark:border-rose-800/60 dark:text-rose-300 dark:hover:bg-rose-950/40'

const friendlyEmpty = computed(() => t('request_detail.ops_no_data'))

// ── BM.03 data ──
const formData = computed(() => req.value?.wizard_snapshot?.form ?? {})
const snap = computed(() => req.value?.wizard_snapshot ?? {})
const isCargo = computed(() => req.value?.trip_type === 'cargo')
const isBusiness = computed(() => req.value?.trip_type === 'business')

function nz(v) {
  return v == null ? '' : String(v).trim()
}
function fmtDateOnly(v) {
  if (!v) return ''
  const s = String(v).trim()
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
    const [y, m, d] = s.slice(0, 10).split('-')
    return `${d}/${m}/${y}`
  }
  const d = new Date(s)
  return Number.isNaN(d.getTime()) ? s : d.toLocaleDateString('vi-VN')
}
function fmtRowDt(v) {
  if (!v) return ''
  const d = new Date(String(v).trim())
  if (Number.isNaN(d.getTime())) return String(v)
  return d.toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}
function money(v) {
  const n = parseMoneyVnd(v)
  return n > 0 ? formatVndCurrency(n) : ''
}

const urgentReasonText = computed(() => (formData.value.is_urgent ? nz(formData.value.urgent_reason) : ''))
const basisText = computed(() => {
  const r = nz(formData.value.basis_ref)
  if (r) return r
  const bf = nz(formData.value.basisFileName)
  return bf ? t('request_detail.ops_basis_file', { name: bf }) : ''
})
const targets = computed(() => (Array.isArray(formData.value.targets) ? formData.value.targets : []).map(nz).filter(Boolean))
const coordinatorName = computed(() => nz(formData.value.coordinator_name))
const distanceText = computed(() => {
  const label = costEstimate.value?.distanceLabel
  return label ? t('request_detail.distance_badge_approx', { label }) : ''
})
const cloneLineageText = computed(() => {
  const s = req.value?.cloned_from_summary
  return s ? t('request_detail.clone_lineage_body', { id: s.id ?? '' }) : ''
})
const costAlertText = computed(() => {
  const a = req.value?.dispatch_package_cost_alert
  const b = req.value?.dispatch_package_budget_alert
  const parts = []
  if (typeof a === 'string') parts.push(a)
  else if (a?.message) parts.push(a.message)
  if (typeof b === 'string') parts.push(b)
  else if (b?.message) parts.push(b.message)
  return parts.join('\n')
})

const paperStatusText = computed(() => {
  const s = req.value?.paper_status
  if (s === 'received') return t('request_detail.ops_paper_received')
  if (s === 'pending') return t('request_detail.ops_paper_pending')
  return ''
})

const quickFacts = computed(() => {
  const r = req.value
  if (!r) return []
  const facts = [
    { label: t('request_detail.ops_lbl_created_at'), value: fmt(r.created_at) },
    { label: t('request_detail.ops_lbl_date_needed'), value: fmtDateOnly(formData.value.date_needed) },
    { label: t('request_detail.ops_lbl_load'), value: passengerOrCargoLine.value },
    { label: t('request_detail.lbl_est_distance'), value: costEstimate.value?.distanceLabel ?? '' },
    { label: t('request_detail.ops_lbl_service_price'), value: r.service_price != null ? formatVndCurrency(r.service_price) : '' },
    { label: t('request_detail.ops_lbl_approver'), value: r.approver?.name ?? '' },
  ]
  if (r.status === 'approved') facts.push({ label: t('request_detail.ops_lbl_paper_status'), value: paperStatusText.value })
  return facts
})

const itineraryTripType = computed(() => resolveItineraryTripType(isCargo.value, isBusiness.value))

function buildItineraryCard(r, i, keyPrefix) {
  const { from, to } = itineraryRowEndpoints(r)
  return {
    key: `${keyPrefix}-${i}`,
    idx: i + 1,
    title: itineraryRowHeading(r, i, { tripType: itineraryTripType.value, t }),
    summaryLines: itineraryRowSummaryLines(r, { tripType: itineraryTripType.value, t, index: i }),
    route: from || to ? { from, to } : null,
  }
}

const itineraryCards = computed(() => {
  const s = snap.value
  if (isCargo.value) {
    return (Array.isArray(s.cargoRows) ? s.cargoRows : []).filter((r) => nz(r?.name)).map((r, i) => ({
      ...buildItineraryCard(r, i, 'c'),
      price: money(r.cost),
      fields: [
        { label: t('request_detail.ops_lbl_qty'), value: nz(r.qty) },
        { label: t('request_detail.ops_lbl_weight'), value: nz(r.weight) },
        { label: t('request_detail.ops_lbl_dimensions'), value: nz(r.dimensions) },
        { label: t('request_detail.ops_lbl_pickup_time'), value: fmtRowDt(r.pickup_at) },
        { label: t('request_detail.ops_lbl_delivery_time'), value: fmtRowDt(r.delivery_at) },
        { label: t('request_detail.ops_lbl_notes'), value: nz(r.item_notes) || nz(r.transport_note), multiline: true },
      ].filter((f) => f.value),
    }))
  }
  if (isBusiness.value) {
    return (Array.isArray(s.businessRows) ? s.businessRows : []).filter(isBusinessRowFilled).map((r, i) => ({
      ...buildItineraryCard(r, i, 'b'),
      price: money(parseMoneyVnd(r.unit_price) + parseMoneyVnd(r.extra_fee)),
      fields: [
        { label: t('request_detail.ops_lbl_guests'), value: nz(r.guests) },
        { label: t('request_detail.ops_lbl_waypoint'), value: nz(r.waypoint) },
        { label: t('request_detail.ops_lbl_depart_time'), value: fmtRowDt(r.depart_at) },
        { label: t('request_detail.ops_lbl_return_time'), value: fmtRowDt(r.return_at) },
        { label: t('request_detail.ops_lbl_notes'), value: nz(r.notes), multiline: true },
      ].filter((f) => f.value),
    }))
  }
  return (Array.isArray(s.passengerRows) ? s.passengerRows : []).filter(isPassengerRowFilled).map((r, i) => ({
    ...buildItineraryCard(r, i, 'p'),
    price: money(parseMoneyVnd(r.unit_price) + parseMoneyVnd(r.extra_fee)),
    fields: [
      { label: t('request_detail.ops_lbl_guests'), value: nz(r.guests) },
      { label: t('request_detail.ops_lbl_depart_time'), value: fmtRowDt(r.depart_at) },
      { label: t('request_detail.ops_lbl_return_time'), value: fmtRowDt(r.return_at) },
      { label: t('request_detail.ops_lbl_person_in_charge'), value: nz(r.person_in_charge) },
      { label: t('request_detail.ops_lbl_notes'), value: nz(r.notes), multiline: true },
    ].filter((f) => f.value),
  }))
})

const extraNotes = computed(() => {
  const f = formData.value
  const out = []
  if (f.need_porters) out.push([t('request_detail.ops_porter_label'), nz(f.porter_qty) && `× ${nz(f.porter_qty)}`, money(f.porter_cost)].filter(Boolean).join(' '))
  if (f.interprovincial) out.push([t('request_detail.ops_interprovincial_label'), money(f.interprovincial_cost)].filter(Boolean).join(' — '))
  if (nz(f.cargo_extra_notes)) out.push(nz(f.cargo_extra_notes))
  return out
})

// ── Signed doc badge ──
const signedVerifyLabel = computed(() => {
  const v = signedDocumentCurrent.value?.verification_status
  const key = `request_detail.signed_verify_${String(v || 'pending')}`
  const tr = t(key)
  return tr !== key ? tr : (v || '—')
})
const signedVerifyBadgeClass = computed(() => {
  const v = signedDocumentCurrent.value?.verification_status
  if (v === 'verified' || v === 'auto_pass') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300'
  if (v === 'rejected') return 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300'
  return 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300'
})

// ── Tabs ──
const workspaceTabs = computed(() => {
  const out = []
  const nav = sectionNavItems.value
  const formItem = nav.find((x) => x.id === 'form')
  out.push({ id: 'form', label: t('request_detail.ops_tab_overview'), badge: formItem?.badge, dot: formItem?.dot, dotTone: formItem?.dotTone })
  out.push({ id: 'route', label: t('request_detail.ops_tab_route') })
  const docItem = nav.find((x) => x.id === 'docs')
  out.push({ id: 'docs', label: t('request_detail.tab_docs'), badge: docItem?.badge, dot: docItem?.dot, dotTone: docItem?.dotTone })
  if (showStudentCountTab.value) {
    const stItem = nav.find((x) => x.id === 'students')
    out.push({ id: 'students', label: t('request_detail.tab_students'), badge: stItem?.badge })
  }
  out.push({ id: 'activity', label: t('request_detail.audit_timeline_heading') })
  return out
})

function mapTodoTab(tab) {
  return tab === 'form' ? 'route' : tab
}

const hasAnyAction = computed(
  () => showD2dDecisionSection.value || showFillPriceSection.value || showResetCloneBtn.value || workflowTodoItems.value.length > 0,
)

// ── Audit ──
function auditEventLabel(event) {
  const key = `request_detail.audit_event_${String(event || '').replace(/\./g, '_')}`
  const tr = t(key)
  if (tr !== key) return tr
  // Friendly fallback: last segment, underscores→spaces, capitalized.
  const seg = String(event || '').split('.').pop().replace(/_/g, ' ').trim()
  return seg ? seg.charAt(0).toUpperCase() + seg.slice(1) : t('request_detail.audit_event_unknown')
}

const AUDIT_ICON = {
  create: { icon: PlusCircleIcon, cls: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300' },
  created: { icon: PlusCircleIcon, cls: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300' },
  approved: { icon: CheckCircleIcon, cls: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300' },
  dept_approved: { icon: CheckCircleIcon, cls: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300' },
  rejected: { icon: XCircleIcon, cls: 'bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-300' },
  dept_rejected: { icon: XCircleIcon, cls: 'bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-300' },
  cancelled: { icon: XCircleIcon, cls: 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' },
  urgent_marked: { icon: BoltIcon, cls: 'bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-300' },
  fill_price: { icon: CurrencyDollarIcon, cls: 'bg-sky-100 text-sky-600 dark:bg-sky-950/50 dark:text-sky-300' },
  price_filled: { icon: CurrencyDollarIcon, cls: 'bg-sky-100 text-sky-600 dark:bg-sky-950/50 dark:text-sky-300' },
  updated: { icon: PencilSquareIcon, cls: 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' },
  cloned: { icon: ArrowPathIcon, cls: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300' },
  paper_received: { icon: DocumentCheckIcon, cls: 'bg-teal-100 text-teal-600 dark:bg-teal-950/50 dark:text-teal-300' },
  paper_reverted: { icon: ArrowUturnLeftIcon, cls: 'bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-300' },
}
function auditMeta(event) {
  const seg = String(event || '').split('.').pop()
  return AUDIT_ICON[seg] || { icon: ClockIcon, cls: 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }
}

// ── Docs ──
const uploadingGeneral = ref(false)
const uploadingScan = ref(false)
const uploadErrLocal = ref('')

function fmtSize(bytes) {
  const n = Number(bytes)
  if (!Number.isFinite(n) || n <= 0) return ''
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  return `${(n / (1024 * 1024)).toFixed(1)} MB`
}
function isPreviewable(a) {
  return /image\/|pdf/i.test(String(a?.mime || ''))
}
async function onPickGeneral(file) {
  if (!file) return
  uploadErrLocal.value = ''
  uploadingGeneral.value = true
  try {
    const r = await uploadRequestDocument(file)
    await onDocUploaded(r)
  } catch (e) {
    uploadErrLocal.value = e?.response?.data?.message ?? t('request_detail.attachment_delete_failed_fallback')
  } finally {
    uploadingGeneral.value = false
  }
}
async function onPickScan(file) {
  if (!file) return
  uploadErrLocal.value = ''
  uploadingScan.value = true
  try {
    const r = await uploadPaperScan(file)
    await onPaperScanUploaded(r)
  } catch (e) {
    uploadErrLocal.value = e?.response?.data?.message ?? t('request_detail.attachment_delete_failed_fallback')
  } finally {
    uploadingScan.value = false
  }
}

// ── Presentational helpers ──
const FieldRow = {
  props: { label: { type: String, default: '' }, value: { type: [String, Number], default: '' }, multiline: { type: Boolean, default: false } },
  setup(props) {
    const empty = computed(() => props.value === '' || props.value == null)
    return () =>
      h('div', { class: 'min-w-0' }, [
        h('dt', { class: 'text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500' }, props.label),
        empty.value
          ? h('dd', { class: 'mt-0.5 text-sm italic text-slate-300 dark:text-slate-600' }, t('request_detail.ops_no_data'))
          : h('dd', { class: ['mt-0.5 text-base text-slate-800 dark:text-slate-200', props.multiline ? 'whitespace-pre-wrap break-words' : 'break-words'] }, String(props.value)),
      ])
  },
}

const DocGroup = {
  props: {
    title: { type: String, default: '' },
    files: { type: Array, default: () => [] },
    highlightId: { type: [Number, String], default: null },
    deletingId: { type: [Number, String], default: null },
    canDelete: { type: Boolean, default: false },
    canOcr: { type: Boolean, default: false },
    canUpload: { type: Boolean, default: false },
    uploading: { type: Boolean, default: false },
    fmtSize: { type: Function, required: true },
    fmtDate: { type: Function, required: true },
    previewable: { type: Function, required: true },
  },
  emits: ['preview', 'download', 'delete', 'ocr', 'pick'],
  setup(props, { emit, slots }) {
    const onChange = (e) => {
      const f = e.target.files?.[0]
      e.target.value = ''
      if (f) emit('pick', f)
    }
    const actionBtn = (icon, label, handler, tone) =>
      h('button', {
        type: 'button', title: label, 'aria-label': label,
        class: ['inline-flex h-8 w-8 items-center justify-center rounded-md border transition disabled:opacity-40',
          tone === 'danger'
            ? 'border-rose-200 text-rose-600 hover:bg-rose-50 dark:border-rose-800/60 dark:text-rose-300 dark:hover:bg-rose-950/40'
            : 'border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800'],
        onClick: handler,
      }, [h(icon, { class: 'h-4 w-4' })])

    return () =>
      h('section', { class: 'rounded-2xl border border-slate-200 p-4 dark:border-slate-800 sm:p-5' }, [
        h('div', { class: 'flex items-center justify-between gap-2' }, [
          h('h3', { class: 'text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400' }, props.title),
          props.canUpload
            ? h('label', { class: 'inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800' }, [
                h(ArrowDownTraySolid, { class: 'h-4 w-4 rotate-180' }),
                props.uploading ? t('request_detail.docs_ocr_running') : t('request_detail.ops_upload_file'),
                h('input', { type: 'file', class: 'hidden', disabled: props.uploading, onChange }),
              ])
            : null,
        ]),
        h('div', { class: 'mt-3 space-y-2' }, [
          props.files.length === 0
            ? h('p', { class: 'rounded-lg border border-dashed border-slate-200 px-3 py-5 text-center text-sm text-slate-400 dark:border-slate-700 dark:text-slate-500' }, t('request_detail.docs_empty_attachments'))
            : props.files.map((a) =>
                h('div', {
                  key: a.id,
                  class: ['flex items-center gap-3 rounded-lg border px-3 py-2.5 transition',
                    String(props.highlightId) === String(a.id) ? 'border-teal-300 bg-teal-50/60 dark:border-teal-700 dark:bg-teal-950/30' : 'border-slate-200 dark:border-slate-800'],
                }, [
                  h('div', { class: 'min-w-0 flex-1' }, [
                    h('p', { class: 'truncate text-base font-medium text-slate-800 dark:text-slate-200' }, a.original_name || '—'),
                    h('p', { class: 'mt-0.5 text-xs text-slate-400 dark:text-slate-500' }, [
                      props.fmtSize(a.size) ? `${props.fmtSize(a.size)} · ` : '',
                      props.fmtDate(a.created_at),
                      a.ocr_status === 'completed' ? ' · OCR ✓' : (a.ocr_status === 'queued' || a.ocr_status === 'processing') ? ' · OCR…' : '',
                    ].join('')),
                  ]),
                  h('div', { class: 'flex shrink-0 items-center gap-1' }, [
                    props.previewable(a) ? actionBtn(EyeIcon, t('request_detail.ops_preview'), () => emit('preview', a)) : null,
                    actionBtn(ArrowDownTraySolid, t('request_detail.download_action'), () => emit('download', a)),
                    props.canOcr ? actionBtn(SparklesIcon, t('request_detail.ops_run_ocr'), () => emit('ocr', a.id)) : null,
                    props.canDelete ? actionBtn(TrashIcon, t('request_detail.delete_action'), () => emit('delete', a), 'danger') : null,
                  ]),
                ]),
              ),
        ]),
        slots.default ? slots.default() : null,
      ])
  },
}
</script>
