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
        :passenger-line="passengerOrCargoLine !== '—' ? passengerOrCargoLine : ''"
        :requester-name="req.requester?.name || ''"
        :requester-unit="heroRequesterUnit"
        :requester-initials="requesterInitials"
        :requester-avatar="req.requester?.avatar_url || ''"
        :created-date="heroCreatedDate"
        :urgent-accent="showUrgentBadge"
        :show-recurring="showRecurringBadge"
        :pdf-busy="pdfBusy"
        :pdf-export-disabled="pdfExportDisabled"
        :show-approve-actions="showD2dDecisionSection"
        :d2d-acting="d2dActing"
        @export-pdf="downloadRequestPdf"
        @approve="onD2dApproveClick"
        @reject="openD2dReject"
      />

      <StaffRequestDetailTabNav
        :tabs="workspaceTabs"
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
            class="flex items-start gap-3 border-y border-rose-200 bg-rose-50 px-4 py-4 dark:border-rose-900/60 dark:bg-rose-950/40 sm:px-5"
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
            class="flex items-center gap-2.5 border-y border-indigo-200 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-900/50 dark:bg-indigo-950/30 dark:text-indigo-200 sm:px-5"
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
            class="flex items-start gap-2.5 border-y border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200 sm:px-5"
            role="alert"
          >
            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0 text-amber-500 dark:text-amber-400" aria-hidden="true" />
            <span class="min-w-0 flex-1 whitespace-pre-line">{{ costAlertText }}</span>
          </div>

          <!-- ─── Tab: Phê duyệt ─── -->
          <div v-show="activeTab === 'approval'" class="space-y-4">
            <FillPricePanel
              v-if="showFillPriceSection"
              ref="fillPricePanelRef"
              :req="req"
              :acting="fillPriceActing"
              :message="fillPriceMsg"
              actions-in-sidebar
              @save="onSaveRowPrices"
              @summary-change="fillPriceSummary = $event"
              @open-reference-pricing="referencePricingModalOpen = true"
            />

            <div
              v-if="hasAnyAction"
              class="overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900"
            >
            <div class="flex items-center gap-2.5 border-b border-slate-100 px-4 py-3 dark:border-slate-800 sm:px-5">
              <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                <BoltIcon class="h-4 w-4" aria-hidden="true" />
              </span>
              <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                {{ t('request_detail.ops_action_center') }}
              </h2>
            </div>
            <div class="flex flex-wrap items-start gap-3 p-4 sm:p-5">
              <!-- D2D approve/reject (secondary when not in header) -->
              <div v-if="showD2dDecisionSection && d2dMsg && !d2dRejectOpen" class="w-full text-sm text-rose-600 dark:text-rose-400">{{ d2dMsg }}</div>

              <!-- Fill price -->
              <div
                v-if="showFillPriceSection"
                class="shrink-0 space-y-3 rounded-xl border border-sky-200/80 bg-sky-50/60 px-3.5 py-3.5 dark:border-sky-900/50 dark:bg-sky-950/25"
              >
                <div class="flex items-start gap-2.5">
                  <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-600 text-white">
                    <CurrencyDollarIcon class="h-5 w-5" aria-hidden="true" />
                  </span>
                  <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ t('request_detail.fill_price_title') }}</p>
                  </div>
                </div>
                <div class="flex items-center justify-between gap-2 rounded-lg border border-sky-200/60 bg-white/80 px-3 py-2.5 dark:border-sky-900/40 dark:bg-slate-900/60">
                  <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('request_detail.fill_price_total') }}</span>
                  <span class="text-lg font-bold tabular-nums" :class="fillPriceSummary.total > 0 ? 'text-teal-600 dark:text-teal-400' : 'text-slate-400 dark:text-slate-500'">{{ fillPriceSummary.totalFmt }}</span>
                </div>
                <div v-if="!fillPriceAutoApproves">
                  <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.assign_dept_head_preset_label') }}</p>
                  <div v-if="fillPriceSummary.deptHeadPresetLocked" class="mt-1.5 rounded-lg border border-sky-200/60 bg-white/80 px-3 py-2 dark:border-sky-900/40 dark:bg-slate-900/60">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ fillPriceSummary.deptHeadDisplayLine }}</p>
                    <p v-if="fillPriceSummary.deptHeadLoadErr" class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ fillPriceSummary.deptHeadLoadErr }}</p>
                  </div>
                  <div v-else class="mt-1.5 rounded-lg border border-amber-200/80 bg-amber-50/80 px-3 py-2 dark:border-amber-900/40 dark:bg-amber-950/20" role="alert">
                    <p class="text-xs font-semibold text-amber-900 dark:text-amber-100">{{ t('request_detail.assign_dept_head_missing_staff_title') }}</p>
                  </div>
                </div>
                <Button class="w-full !bg-sky-600 hover:!bg-sky-700" :loading="fillPriceActing" :disabled="!fillPriceSummary.canSubmitFillPrice" @click="onFillPriceSubmitFromSidebar">
                  {{ fillPriceAutoApproves ? t('request_detail.fill_price_submit_approve') : t('request_detail.fill_price_submit_preset_dept') }}
                </Button>
                <Button type="button" variant="secondary" class="w-full" data-testid="fill-price-sidebar-open-reference-pricing" :disabled="fillPriceActing" @click="referencePricingModalOpen = true">
                  {{ t('request_detail.reference_pricing_link') }}
                </Button>
                <p v-if="fillPriceMsg" class="text-xs text-slate-500 dark:text-slate-400">{{ fillPriceMsg }}</p>
              </div>

              <!-- Reset / clone -->
              <button
                v-if="showResetCloneBtn"
                type="button"
                class="inline-flex shrink-0 items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-60 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                :disabled="resetCloneBusy"
                @click="onResetCloneRequest"
              >
                <ArrowPathIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                {{ resetCloneBusy ? t('request_detail.reset_clone_busy') : t('request_detail.reset_clone') }}
              </button>

              <!-- Workflow todos -->
              <div v-if="workflowTodoItems.length" class="shrink-0 space-y-1.5">
                <p class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-400">{{ t('request_detail.aside_todos_heading') }}</p>
                <button
                  v-for="item in workflowTodoItems"
                  :key="item.key"
                  type="button"
                  class="flex w-full items-center justify-between gap-2 rounded-lg border border-amber-200/70 bg-white/80 px-3.5 py-2.5 text-left text-sm font-semibold text-amber-900 transition hover:bg-white dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200 dark:hover:bg-amber-950/40"
                  @click="onWorkflowNavigate({ tab: mapTodoTab(item), focus: item.focus })"
                >
                  <span class="min-w-0 truncate">{{ item.label }}</span>
                  <ArrowRightIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                </button>
              </div>
            </div>
            </div>

            <p
              v-if="!hasAnyAction && !showFillPriceSection"
              class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400 sm:px-5"
            >
              {{ t('request_detail.ops_no_actions') }}
            </p>
          </div>

          <!-- ─── 1-col tab panels ─── -->
          <div :class="cardClass">
                <div>
                  <!-- ===== Tab: Tổng quan ===== -->
                  <!-- ════ Tab: Tổng quan ════ -->
                  <div v-show="activeTab === 'form'" class="space-y-0">

                    <!-- Dispatch + cost summary (driver, vehicle, cost) — only when trip exists -->
                    <div v-if="req.trip" class="overflow-hidden border-y border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                      <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-2.5 dark:border-slate-800">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                          {{ t('request_detail.overview_group_dispatch') }}
                        </p>
                        <RouterLink
                          :to="`/trips/${req.trip.id}`"
                          class="inline-flex items-center gap-1 text-xs font-semibold text-va-700 transition hover:text-va-900 dark:text-va-400 dark:hover:text-va-200"
                          data-testid="staff-request-linked-trip"
                        >
                          <span>{{ t('request_detail.ops_linked_trip') }} {{ linkedTripCode }}</span>
                          <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                        </RouterLink>
                      </div>
                      <dl class="grid grid-cols-2 divide-x divide-y divide-slate-100 dark:divide-slate-800 sm:grid-cols-4">
                        <div class="px-4 py-3">
                          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.overview_lbl_driver') }}</dt>
                          <dd class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ req.trip.driver?.full_name || req.trip.driver?.name || friendlyEmpty }}</dd>
                        </div>
                        <div class="px-4 py-3">
                          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.overview_lbl_vehicle') }}</dt>
                          <dd class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ req.trip.vehicle?.type || friendlyEmpty }}</dd>
                        </div>
                        <div class="px-4 py-3">
                          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.overview_lbl_plate') }}</dt>
                          <dd class="mt-0.5 text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ req.trip.vehicle?.license_plate || friendlyEmpty }}</dd>
                        </div>
                        <div class="px-4 py-3">
                          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.overview_lbl_cost') }}</dt>
                          <dd class="mt-0.5 text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ asideDeclaredTotalDisplay }}</dd>
                        </div>
                      </dl>
                    </div>

                    <!-- Single unified request-info card -->
                    <div class="overflow-hidden border-y border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                      <div class="border-b border-slate-100 px-4 py-2.5 dark:border-slate-800">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                          {{ t('request_detail.hero_card_request_info') }}
                        </p>
                      </div>

                      <dl class="grid grid-cols-1 divide-y divide-slate-100 dark:divide-slate-800 sm:grid-cols-2 sm:divide-x lg:grid-cols-3">
                        <!-- Mục đích -->
                        <div class="px-4 py-3 sm:col-span-2 lg:col-span-1">
                          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.ops_lbl_purpose') }}</dt>
                          <dd class="mt-1 text-sm leading-relaxed text-slate-800 dark:text-slate-200" :class="nz(formData.purpose) ? '' : 'italic text-slate-400 dark:text-slate-500'">
                            {{ nz(formData.purpose) || friendlyEmpty }}
                          </dd>
                        </div>
                        <!-- Căn cứ -->
                        <div class="px-4 py-3 sm:col-span-2 lg:col-span-2">
                          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.ops_lbl_basis') }}</dt>
                          <dd class="mt-1 text-sm leading-relaxed text-slate-800 dark:text-slate-200" :class="basisText ? '' : 'italic text-slate-400 dark:text-slate-500'">
                            {{ basisText || friendlyEmpty }}
                          </dd>
                        </div>
                      </dl>

                      <dl class="grid grid-cols-2 divide-x divide-y divide-slate-100 dark:divide-slate-800 sm:grid-cols-4">
                        <div class="px-4 py-3">
                          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.ops_lbl_proposed_date') }}</dt>
                          <dd class="mt-0.5 text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ fmtDateOnly(formData.proposed_date) || friendlyEmpty }}</dd>
                        </div>
                        <div class="px-4 py-3">
                          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.ops_lbl_date_needed') }}</dt>
                          <dd class="mt-0.5 text-sm font-semibold tabular-nums" :class="formData.date_needed ? 'text-slate-900 dark:text-slate-100' : 'italic text-slate-400 dark:text-slate-500'">
                            {{ fmtDateOnly(formData.date_needed) || friendlyEmpty }}
                          </dd>
                        </div>
                        <div v-if="coordinatorName" class="px-4 py-3">
                          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.ops_lbl_coordinator') }}</dt>
                          <dd class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ coordinatorName }}</dd>
                        </div>
                        <div v-if="targets.length" class="px-4 py-3" :class="coordinatorName ? '' : 'col-span-2'">
                          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.ops_lbl_target_audience') }}</dt>
                          <dd class="mt-1 flex flex-wrap gap-1">
                            <span
                              v-for="(tg, i) in targets"
                              :key="i"
                              class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-xs font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            >{{ tg }}</span>
                          </dd>
                        </div>
                      </dl>

                      <!-- Urgent reason banner (inline, no extra card) -->
                      <div
                        v-if="urgentReasonText"
                        class="border-t border-rose-100 bg-rose-50/70 px-4 py-3 dark:border-rose-900/40 dark:bg-rose-950/20"
                      >
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400">{{ t('request_detail.ops_lbl_urgent_reason') }}</p>
                        <p class="mt-0.5 text-sm text-rose-900 dark:text-rose-200">{{ urgentReasonText }}</p>
                      </div>
                    </div>

                    <!-- Assigned dept head (retained, important for workflow) -->
                    <AssignedDeptHeadFormCard v-if="showDeptHeadPanel" :req="req" variant="staff" />
                  </div>

                  <!-- ===== Tab: Chi tiết chuyến ===== -->
                  <div v-show="activeTab === 'route'" class="space-y-4 p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                      <div class="flex min-w-0 items-center gap-2.5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-va-50 text-va-700 dark:bg-va-950/50 dark:text-va-300">
                          <MapIcon class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                          <h2 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ t('request_detail.ops_itinerary_heading') }}
                          </h2>
                          <p v-if="itineraryCards.length" class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                            {{ t('request_detail.ops_itinerary_count', { n: itineraryCards.length }) }}
                          </p>
                        </div>
                      </div>
                    </div>

                    <p
                      v-if="!itineraryCards.length"
                      class="rounded-2xl border border-dashed border-slate-200 px-4 py-12 text-center text-base text-slate-400 dark:border-slate-700 dark:text-slate-500"
                    >{{ t('request_detail.ops_no_itinerary') }}</p>

                    <div
                      v-for="card in itineraryCards"
                      :key="card.key"
                      class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
                      :data-testid="`staff-request-itinerary-row-${card.idx}`"
                    >
                      <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/50">
                        <span
                          class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-va-100 text-sm font-bold text-va-800 dark:bg-va-950/50 dark:text-va-300"
                          :aria-label="t('request_detail.ops_row_badge_aria', { n: card.idx })"
                        >{{ card.idx }}</span>
                        <div class="min-w-0 flex-1">
                          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            {{ t('request_detail.ops_itinerary_row_label') }}
                          </p>
                          <p class="mt-0.5 truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ card.title }}</p>
                        </div>
                      </div>

                      <div
                        v-if="card.from || card.to"
                        class="grid grid-cols-[1fr_auto_1fr] border-b border-slate-100 dark:border-slate-800"
                      >
                        <div class="bg-emerald-50/50 px-4 py-3 dark:bg-emerald-950/15">
                          <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600/80 dark:text-emerald-400/80">{{ t('request_detail.lbl_origin') }}</p>
                          <p class="mt-0.5 text-sm font-semibold leading-snug text-slate-800 dark:text-slate-100">{{ card.from || friendlyEmpty }}</p>
                        </div>
                        <div class="flex items-center justify-center bg-slate-50/50 px-2 dark:bg-slate-800/30">
                          <ArrowRightIcon class="h-4 w-4 text-slate-300 dark:text-slate-600" aria-hidden="true" />
                        </div>
                        <div class="bg-rose-50/50 px-4 py-3 dark:bg-rose-950/15">
                          <p class="text-[10px] font-bold uppercase tracking-wider text-rose-600/80 dark:text-rose-400/80">{{ t('request_detail.lbl_destination') }}</p>
                          <p class="mt-0.5 text-sm font-semibold leading-snug text-slate-800 dark:text-slate-100">{{ card.to || friendlyEmpty }}</p>
                        </div>
                      </div>

                      <ItineraryRowInfoGrid :row="card.sourceRow" :trip-type="itineraryTripType" />

                      <div
                        v-if="card.timeline.length"
                        class="border-b border-slate-100 px-4 py-3 dark:border-slate-800"
                      >
                        <RequestItineraryTimelineTabs :legs="card.timeline" />
                      </div>

                      <div
                        v-if="card.priceTotal || card.unitPrice || card.extraFee"
                        class="border-b border-slate-100 bg-slate-50/50 px-4 py-4 dark:border-slate-800 dark:bg-slate-800/25"
                      >
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                          {{ t('request_detail.ops_route_cost_breakdown') }}
                        </p>
                        <dl class="mt-2.5 grid gap-2 sm:grid-cols-3">
                          <FieldRow
                            v-if="card.unitPrice"
                            boxed
                            :label="t('request_detail.ops_lbl_unit_price')"
                            :value="card.unitPrice.display"
                          />
                          <FieldRow
                            v-if="card.extraFee"
                            boxed
                            :label="t('request_detail.ops_lbl_extra_fee')"
                            :value="card.extraFee.display"
                          />
                          <FieldRow
                            v-if="card.priceTotal"
                            boxed
                            :label="t('request_detail.ops_lbl_row_cost')"
                            :value="card.priceTotal.display"
                            highlight
                          />
                        </dl>
                        <p v-if="card.priceTotal?.words" class="mt-2 text-xs italic leading-relaxed text-slate-500 dark:text-slate-400">
                          <span class="font-semibold not-italic text-slate-400 dark:text-slate-500">{{ t('request_detail.ops_lbl_amount_in_words') }}:</span>
                          {{ card.priceTotal.words }}
                        </p>
                      </div>

                      <dl v-if="card.fields.length" class="grid gap-2.5 px-4 py-4 sm:grid-cols-2">
                        <FieldRow v-for="(f, fi) in card.fields" :key="fi" boxed :label="f.label" :value="f.value" :multiline="f.multiline" />
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

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white px-4 py-4 dark:border-slate-800 dark:bg-slate-900 sm:px-5">
                      <PortalStatusTimeline
                        :title="t('portal.timeline_heading')"
                        :steps="timelineSteps"
                        variant="staff"
                      />
                    </div>
                  </div>

                  <!-- ===== Tab: Hồ sơ ===== -->
                  <div
                    v-show="activeTab === 'docs'"
                    class="space-y-4 text-[90%] leading-snug"
                    data-testid="request-tab-docs"
                  >
                    <p v-if="uploadErrLocal || attachErr || ocrErr" class="rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">
                      {{ uploadErrLocal || attachErr || ocrErr }}
                    </p>

                    <div class="grid gap-4 lg:grid-cols-3 lg:items-stretch">
                      <DocGroup
                        column
                        accent="sky"
                        :header-icon="PaperClipIcon"
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
                        column
                        accent="amber"
                        :header-icon="DocumentCheckIcon"
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
                        <div
                          v-if="signedDocumentCurrent"
                          class="shrink-0 space-y-2.5 rounded-xl border border-amber-200/80 bg-amber-50/60 p-3 dark:border-amber-900/50 dark:bg-amber-950/25"
                        >
                          <p class="text-[10px] font-medium uppercase tracking-wider text-amber-700/90 dark:text-amber-400/90">
                            {{ t('request_detail.ops_signed_doc_status') }}
                          </p>
                          <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold" :class="signedVerifyBadgeClass">{{ signedVerifyLabel }}</span>
                          <div v-if="canManagePaper" class="flex flex-col gap-2">
                            <button type="button" class="w-full" :class="btnGhostClass" :disabled="signedOcrBusy" @click="onSignedRerunOcr">{{ signedOcrBusy ? t('request_detail.docs_ocr_running') : t('request_detail.ops_rerun_ocr') }}</button>
                            <button type="button" class="w-full" :class="btnTealClass" :disabled="signedVerifyBusy" @click="onSignedVerify('approve')">{{ t('request_detail.ops_verify_pass') }}</button>
                            <button type="button" class="w-full" :class="btnDangerGhostClass" :disabled="signedVerifyBusy" @click="onSignedVerify('reject')">{{ t('request_detail.ops_verify_fail') }}</button>
                          </div>
                        </div>
                      </DocGroup>

                      <DocGroup
                        column
                        accent="teal"
                        :header-icon="DocumentDuplicateIcon"
                        :title="t('request_detail.ops_docs_scan')"
                        :files="paperScans"
                        :deleting-id="deletingId"
                        :can-delete="canDeleteAttachment"
                        :can-upload="canUploadAttachment"
                        :uploading="uploadingScan"
                        :fmt-size="fmtSize"
                        :fmt-date="fmt"
                        :previewable="isPreviewable"
                        empty-text-key="docs_empty_paper_scan"
                        @preview="openAttachmentPreview"
                        @download="downloadFile"
                        @delete="removeAttachment"
                        @pick="onPickScan"
                      >
                        <form
                          v-if="canManagePaper && (req.paper_status === 'pending' || req.paper_status === 'received')"
                          class="shrink-0 space-y-3 rounded-xl border border-teal-200/80 bg-teal-50/50 p-3 dark:border-teal-900/50 dark:bg-teal-950/25"
                          @submit.prevent="doMarkPaper"
                        >
                          <p class="text-[10px] font-medium uppercase tracking-wider text-teal-700/90 dark:text-teal-400/90">
                            {{ req.paper_status === 'received' ? t('request_detail.paper_update_section_title') : t('request_detail.paper_confirm_received_title') }}
                          </p>
                          <Input v-model="paperForm.paper_reference" :label="t('request_detail.paper_ref_input_label')" :placeholder="t('request_detail.paper_ref_placeholder')" />
                          <Input v-model="paperForm.paper_received_at" :label="t('request_detail.paper_received_at_input_label')" type="datetime-local" />
                          <div class="flex flex-col gap-2">
                            <Button :loading="paperActing" type="submit" class="w-full !bg-teal-600 hover:!bg-teal-700">
                              {{ req.paper_status === 'received' ? t('request_detail.paper_save_changes_btn') : t('request_detail.paper_mark_received_btn') }}
                            </Button>
                            <Button v-if="req.paper_status === 'received'" variant="secondary" type="button" class="w-full !border-amber-200 !text-amber-900 hover:!bg-amber-50" :disabled="paperActing || paperRevertActing" @click="doRevertPaper">
                              {{ t('request_detail.paper_revert_btn') }}
                            </Button>
                            <span v-if="paperMsg" class="text-center text-sm text-slate-500 dark:text-slate-400">{{ paperMsg }}</span>
                          </div>
                        </form>
                      </DocGroup>
                    </div>
                  </div>

                  <!-- ===== Tab: Học sinh ===== -->
                  <div v-show="activeTab === 'students' && showStudentCountTab" class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 p-5 dark:border-slate-800">
                      <p :class="sectionTitleClass">{{ t('request_detail.bm03_student_count_plan_short') }}</p>
                      <p class="mt-2 text-4xl font-bold tabular-nums text-slate-900 dark:text-white">{{ dispatchRequestDisplayPassengerCount(req) || friendlyEmpty }}</p>
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
                    <template v-else>
                      <div class="mb-3 flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm tabular-nums text-slate-500 dark:text-slate-400">
                          {{ t('request_detail.audit_pagination_showing', { from: auditShowFrom, to: auditShowTo, total: auditTotal }) }}
                        </p>
                        <label class="inline-flex items-center gap-2">
                          <span class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ t('request_detail.audit_per_page_label') }}</span>
                          <select
                            v-model.number="auditPageSize"
                            class="h-9 rounded-lg border border-slate-200 bg-white px-2.5 text-sm font-medium text-slate-800 shadow-sm outline-none focus:ring-2 focus:ring-va-500/30 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                            :aria-label="t('request_detail.audit_per_page_aria')"
                          >
                            <option v-for="opt in auditPageSizeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                          </select>
                        </label>
                      </div>

                      <ol class="space-y-1">
                        <li
                          v-for="row in paginatedAuditItems"
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

                      <div
                        v-if="auditPaginationVisible"
                        class="mt-3 flex flex-col gap-2 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800"
                      >
                        <p class="text-xs tabular-nums text-slate-500 dark:text-slate-400">
                          {{ t('request_detail.audit_pagination_page', { page: auditPage, total: auditTotalPages }) }}
                        </p>
                        <div class="flex items-center gap-2">
                          <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                            :disabled="auditPage <= 1"
                            @click="auditPage--"
                          >
                            <ChevronLeftIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                            {{ t('request_detail.audit_pagination_prev') }}
                          </button>
                          <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                            :disabled="auditPage >= auditTotalPages"
                            @click="auditPage++"
                          >
                            {{ t('request_detail.audit_pagination_next') }}
                            <ChevronRightIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                          </button>
                        </div>
                      </div>
                    </template>
                  </div>

                </div>
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
      <ReferencePricingModal :open="referencePricingModalOpen" @close="referencePricingModalOpen = false" />
    </template>
  </div>
</template>

<script setup>
import { computed, defineAsyncComponent, h, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ArrowPathIcon,
  ArrowRightIcon,
  ArrowTopRightOnSquareIcon,
  ArrowUturnLeftIcon,
  BoltIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClipboardDocumentIcon,
  ClockIcon,
  CurrencyDollarIcon,
  DocumentCheckIcon,
  DocumentDuplicateIcon,
  DocumentTextIcon,
  PaperClipIcon,
  ExclamationTriangleIcon,
  MapIcon,
  MapPinIcon,
  PencilSquareIcon,
  PlusCircleIcon,
  ScaleIcon,
  TruckIcon,
  UserGroupIcon,
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
import AttachmentPreviewModal from '../../components/requests/AttachmentPreviewModal.vue'
import AssignedDeptHeadFormCard from '../../components/requests/AssignedDeptHeadFormCard.vue'
import StaffRequestHeroHeader from '../../components/requests/detail/StaffRequestHeroHeader.vue'
import StaffRequestDetailTabNav from '../../components/requests/detail/StaffRequestDetailTabNav.vue'
import StaffRequestOverviewInfoGrid from '../../components/requests/detail/StaffRequestOverviewInfoGrid.vue'
import RejectReasonModal from '../../components/requests/RejectReasonModal.vue'
import ReferencePricingModal from '../../components/pricing/ReferencePricingModal.vue'
import PortalStatusTimeline from '../../components/portal/PortalStatusTimeline.vue'
import RequestItineraryTimelineTabs from '../../components/requests/RequestItineraryTimelineTabs.vue'
import ItineraryRowInfoGrid from '../../components/requests/workspace/ItineraryRowInfoGrid.vue'
import { useRequestDetailPage } from '../../composables/useRequestDetailPage'
import { useAssignedDeptHeadDisplay } from '../../composables/useAssignedDeptHeadDisplay'
import { formatVndCurrency as formatVndMoney, parseMoneyVnd, VND_CURRENCY_SUFFIX, vndAmountInWords } from '../../util/money'
import {
  itineraryRowEndpoints,
  itineraryRowHeading,
  resolveItineraryTripType,
} from '../../util/requestItineraryRowDisplay'
import { isPassengerRowFilled, isBusinessRowFilled } from '../../composables/dispatchWizardConstants'
import { formatTripCode, labelTripStatus } from '../../util/labels'
import { dispatchRequestDisplayPassengerCount } from '../../util/dispatchRequestPassengers'

const FillPricePanel = defineAsyncComponent(() =>
  import('../../components/requests/workspace/FillPricePanel.vue'),
)
const { t, locale } = useI18n()

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
  fillPriceAutoApproves,
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

const { showDeptHeadPanel } = useAssignedDeptHeadDisplay(req)

// ── Style tokens ──
const cardClass = 'overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900'
const sectionTitleClass = 'text-sm font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400'
const btnGhostClass = 'inline-flex items-center rounded-md border border-slate-200 px-2.5 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800'
const btnTealClass = 'inline-flex items-center rounded-md bg-teal-600 px-2.5 py-1.5 text-sm font-semibold text-white transition hover:bg-teal-700 disabled:opacity-50'
const btnDangerGhostClass = 'inline-flex items-center rounded-md border border-rose-200 px-2.5 py-1.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 disabled:opacity-50 dark:border-rose-800/60 dark:text-rose-300 dark:hover:bg-rose-950/40'

const friendlyEmpty = computed(() => t('request_detail.ops_no_data'))
const linkedTripCode = computed(() => formatTripCode(req.value?.trip?.id))

const referencePricingModalOpen = ref(false)

const fillPricePanelRef = ref(null)
const fillPriceSummary = ref({
  totalFmt: '—',
  total: 0,
  canSubmitFillPrice: false,
  deptHeadDisplayLine: '—',
  deptHeadPresetLocked: false,
  deptHeadLoadErr: '',
})

function onFillPriceSubmitFromSidebar() {
  fillPricePanelRef.value?.submit?.()
}

const asidePanelTab = ref('info')
const asidePanelTabs = computed(() => [
  { id: 'info', label: t('request_detail.ops_aside_tab_info') },
  { id: 'cost', label: t('request_detail.ops_aside_tab_cost') },
  { id: 'trip', label: t('request_detail.ops_aside_tab_trip') },
])

function formatVndSidebar(n) {
  const amount = parseMoneyVnd(n)
  if (!amount) return friendlyEmpty.value
  return formatVndMoney(amount, VND_CURRENCY_SUFFIX)
}

const asideServicePriceDisplay = computed(() =>
  req.value?.service_price != null ? formatVndSidebar(req.value.service_price) : '',
)
const asideServicePriceWords = computed(() => {
  if (req.value?.service_price == null) return ''
  const words = vndAmountInWords(req.value.service_price)
  return words && words !== 'Không đồng' ? words : ''
})
const asideDeclaredTotalDisplay = computed(() => {
  const total = costEstimate.value?.total
  if (total != null && total > 0) return formatVndSidebar(total)
  return friendlyEmpty.value
})
const asideDeclaredTotalWords = computed(() => {
  const total = costEstimate.value?.total
  if (total == null || total <= 0) return ''
  return vndAmountInWords(total)
})

// ── BM.03 data ──
const formData = computed(() => req.value?.wizard_snapshot?.form ?? {})
const snap = computed(() => req.value?.wizard_snapshot ?? {})
const isCargo = computed(() => req.value?.trip_type === 'cargo')
const metaLoadLabel = computed(() =>
  isCargo.value ? t('request_detail.ops_lbl_weight') : t('request_detail.ops_lbl_passenger_count'),
)
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
  return fmt(v)
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

const itineraryTripType = computed(() => resolveItineraryTripType(isCargo.value, isBusiness.value))

function buildRowMoney(n) {
  const amount = parseMoneyVnd(n)
  if (!amount) return null
  const words = vndAmountInWords(amount)
  return {
    display: formatVndMoney(amount, VND_CURRENCY_SUFFIX),
    words: words && words !== 'Không đồng' ? words : '',
    amount,
  }
}


function buildItineraryTimeline(r, tripType) {
  const { from, to } = itineraryRowEndpoints(r)
  const legs = []

  if (tripType === 'cargo') {
    if (from || fmtRowDt(r.pickup_at)) {
      legs.push({
        tone: 'pickup',
        label: t('request_detail.ops_route_timeline_pickup'),
        time: fmtRowDt(r.pickup_at),
        place: from,
      })
    }
    if (to || fmtRowDt(r.delivery_at)) {
      legs.push({
        tone: 'back',
        label: t('request_detail.ops_route_timeline_delivery'),
        time: fmtRowDt(r.delivery_at),
        place: to,
      })
    }
    return legs
  }

  if (from || fmtRowDt(r.depart_at)) {
    legs.push({
      tone: 'out',
      label: t('request_detail.ops_route_timeline_out'),
      time: fmtRowDt(r.depart_at),
      place: from,
    })
  }
  if (tripType === 'business' && nz(r.waypoint)) {
    legs.push({
      tone: 'waypoint',
      label: t('request_detail.ops_route_timeline_waypoint'),
      time: '',
      place: nz(r.waypoint),
    })
  }
  if (to || fmtRowDt(r.return_at)) {
    legs.push({
      tone: 'back',
      label: t('request_detail.ops_route_timeline_back'),
      time: fmtRowDt(r.return_at),
      place: to,
    })
  }
  return legs
}

function buildItineraryCard(r, i, keyPrefix) {
  const tripType = itineraryTripType.value
  const { from, to } = itineraryRowEndpoints(r)
  const routeLabel = from || to ? `${from || '—'} → ${to || '—'}` : ''
  const title = itineraryRowHeading(r, i, { tripType, t })
  return {
    key: `${keyPrefix}-${i}`,
    idx: i + 1,
    title,
    subtitle: routeLabel || title,
    routeLabel,
    from,
    to,
    sourceRow: r,
    timeline: buildItineraryTimeline(r, tripType),
  }
}

const itineraryCards = computed(() => {
  const s = snap.value
  if (isCargo.value) {
    return (Array.isArray(s.cargoRows) ? s.cargoRows : []).filter((r) => nz(r?.name)).map((r, i) => ({
      ...buildItineraryCard(r, i, 'c'),
      unitPrice: null,
      extraFee: null,
      priceTotal: buildRowMoney(r.cost),
      fields: [
        { label: t('request_detail.ops_lbl_dimensions'), value: nz(r.dimensions) },
        { label: t('request_detail.ops_lbl_pickup_contact'), value: nz(r.pickup_contact) },
        { label: t('request_detail.ops_lbl_delivery_contact'), value: nz(r.delivery_contact) },
        { label: t('request_detail.ops_lbl_notes'), value: nz(r.item_notes) || nz(r.transport_note), multiline: true },
      ].filter((f) => f.value),
    }))
  }
  if (isBusiness.value) {
    return (Array.isArray(s.businessRows) ? s.businessRows : []).filter(isBusinessRowFilled).map((r, i) => {
      const unitPrice = buildRowMoney(r.unit_price)
      const extraFee = buildRowMoney(r.extra_fee)
      const totalAmount = parseMoneyVnd(r.unit_price) + parseMoneyVnd(r.extra_fee)
      return {
        ...buildItineraryCard(r, i, 'b'),
        unitPrice,
        extraFee,
        priceTotal: buildRowMoney(totalAmount),
        fields: [
          { label: t('request_detail.ops_lbl_person_in_charge'), value: nz(r.person_in_charge) },
          { label: t('request_detail.ops_lbl_notes'), value: nz(r.notes), multiline: true },
        ].filter((f) => f.value),
      }
    })
  }
  return (Array.isArray(s.passengerRows) ? s.passengerRows : []).filter(isPassengerRowFilled).map((r, i) => {
    const unitPrice = buildRowMoney(r.unit_price)
    const extraFee = buildRowMoney(r.extra_fee)
    const totalAmount = parseMoneyVnd(r.unit_price) + parseMoneyVnd(r.extra_fee)
    return {
      ...buildItineraryCard(r, i, 'p'),
      unitPrice,
      extraFee,
      priceTotal: buildRowMoney(totalAmount),
      fields: [
        { label: t('request_detail.ops_lbl_notes'), value: nz(r.notes), multiline: true },
      ].filter((f) => f.value),
    }
  })
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
  out.push({
    id: 'approval',
    label: t('request_detail.ops_tab_approval'),
    dot: hasAnyAction.value,
    dotTone: 'amber',
  })
  const docItem = nav.find((x) => x.id === 'docs')
  out.push({ id: 'docs', label: t('request_detail.tab_docs'), badge: docItem?.badge, dot: docItem?.dot, dotTone: docItem?.dotTone })
  if (showStudentCountTab.value) {
    const stItem = nav.find((x) => x.id === 'students')
    out.push({ id: 'students', label: t('request_detail.tab_students'), badge: stItem?.badge })
  }
  out.push({ id: 'activity', label: t('request_detail.ops_tab_audit') })
  return out
})

function mapTodoTab(item) {
  if (item?.focus === 'fill-price') return 'approval'
  if (item?.tab === 'approval') return 'approval'
  return item?.tab || 'form'
}

const heroPriorityLabel = computed(() =>
  showUrgentBadge.value ? t('request_detail.hero_priority_high') : t('request_detail.hero_priority_normal'),
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

const heroRequesterUnit = computed(() =>
  req.value?.wizard_snapshot?.form?.requester_unit?.trim() ||
  requesterAsideSubtitle.value ||
  '',
)

const overviewInfoGroups = computed(() => {
  const r = req.value
  if (!r) return []
  const emailRow = r.requester?.email?.trim() || requesterAsideFields.value.find((x) => x.key === 'email')?.value || ''
  const dept =
    r.wizard_snapshot?.form?.requester_unit?.trim() ||
    requesterAsideSubtitle.value ||
    ''
  const trip = r.trip
  const driverName = trip?.driver?.full_name || trip?.driver?.name || ''
  const vehicleLabel = trip?.vehicle?.type || ''
  const plate = trip?.vehicle?.license_plate || ''
  const dispatchStatus = trip?.status ? labelTripStatus(trip.status) : ''

  return [
    {
      key: 'requester',
      title: t('request_detail.overview_group_requester'),
      fields: [
        { key: 'name', label: t('request_detail.overview_lbl_requester'), value: r.requester?.name ?? '' },
        { key: 'dept', label: t('request_detail.overview_lbl_department'), value: dept },
        { key: 'created', label: t('request_detail.ops_lbl_created_at'), value: fmt(r.created_at) },
        { key: 'email', label: t('request_detail.lbl_email'), value: emailRow },
      ],
    },
    {
      key: 'trip',
      title: t('request_detail.overview_group_trip'),
      fields: [
        { key: 'type', label: t('request_detail.lbl_trip_type_short'), value: labelTripType(r.trip_type) },
        {
          key: 'pax',
          label: metaLoadLabel.value,
          value: passengerOrCargoLine.value !== '—' ? passengerOrCargoLine.value : '',
        },
        { key: 'driver', label: t('request_detail.overview_lbl_driver'), value: driverName },
        { key: 'vehicle', label: t('request_detail.overview_lbl_vehicle'), value: vehicleLabel },
      ],
    },
    {
      key: 'dispatch',
      title: t('request_detail.overview_group_dispatch'),
      fields: [
        { key: 'unit', label: t('request_detail.overview_lbl_vehicle_unit'), value: vehicleLabel },
        { key: 'plate', label: t('request_detail.overview_lbl_plate'), value: plate },
        { key: 'cost', label: t('request_detail.overview_lbl_cost'), value: asideDeclaredTotalDisplay.value },
        { key: 'status', label: t('request_detail.overview_lbl_dispatch_status'), value: dispatchStatus },
      ],
    },
  ]
})

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

const AUDIT_PAGE_SIZE_ALL = 0
const auditPageSize = ref(10)
const auditPage = ref(1)

const auditPageSizeOptions = computed(() => [
  { value: 5, label: '5' },
  { value: 10, label: '10' },
  { value: 15, label: '15' },
  { value: 20, label: '20' },
  { value: AUDIT_PAGE_SIZE_ALL, label: t('request_detail.audit_per_page_all') },
])

const auditTotal = computed(() => auditItems.value.length)

const auditTotalPages = computed(() => {
  if (auditPageSize.value === AUDIT_PAGE_SIZE_ALL || auditTotal.value === 0) return 1
  return Math.max(1, Math.ceil(auditTotal.value / auditPageSize.value))
})

const paginatedAuditItems = computed(() => {
  if (auditPageSize.value === AUDIT_PAGE_SIZE_ALL) return auditItems.value
  const start = (auditPage.value - 1) * auditPageSize.value
  return auditItems.value.slice(start, start + auditPageSize.value)
})

const auditShowFrom = computed(() => {
  if (auditTotal.value === 0) return 0
  if (auditPageSize.value === AUDIT_PAGE_SIZE_ALL) return 1
  return (auditPage.value - 1) * auditPageSize.value + 1
})

const auditShowTo = computed(() => {
  if (auditTotal.value === 0) return 0
  if (auditPageSize.value === AUDIT_PAGE_SIZE_ALL) return auditTotal.value
  return Math.min(auditPage.value * auditPageSize.value, auditTotal.value)
})

const auditPaginationVisible = computed(
  () => auditPageSize.value !== AUDIT_PAGE_SIZE_ALL && auditTotalPages.value > 1,
)

watch(auditPageSize, () => {
  auditPage.value = 1
})

watch(auditItems, () => {
  if (auditPage.value > auditTotalPages.value) auditPage.value = auditTotalPages.value
})

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
  props: {
    label: { type: String, default: '' },
    value: { type: [String, Number], default: '' },
    multiline: { type: Boolean, default: false },
    highlight: { type: Boolean, default: false },
    emphasize: { type: Boolean, default: false },
    boxed: { type: Boolean, default: false },
  },
  setup(props) {
    const empty = computed(() => props.value === '' || props.value == null)
    const valueClass = computed(() => {
      if (empty.value) return 'mt-1 text-sm italic text-slate-400 dark:text-slate-500'
      const base = ['mt-1 break-words', props.multiline ? 'whitespace-pre-wrap text-sm leading-relaxed' : 'text-sm']
      if (props.emphasize) base.push('font-semibold text-rose-800 dark:text-rose-200')
      else if (props.highlight) base.push('font-semibold text-va-800 dark:text-va-200')
      else base.push('font-medium text-slate-800 dark:text-slate-200')
      return base
    })
    const labelClass = props.boxed
      ? 'text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500'
      : 'text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500'
    const wrapClass = props.boxed
      ? 'min-w-0 rounded-xl border border-slate-100 bg-slate-50/70 px-3.5 py-2.5 dark:border-slate-800 dark:bg-slate-800/40'
      : 'min-w-0'
    return () =>
      h('div', { class: wrapClass }, [
        h('dt', { class: labelClass }, props.label),
        empty.value
          ? h('dd', { class: 'mt-1 text-sm italic text-slate-400 dark:text-slate-500' }, t('request_detail.ops_no_data'))
          : h('dd', { class: valueClass.value }, String(props.value)),
      ])
  },
}

const DOC_GROUP_ACCENT = {
  sky: {
    icon: 'bg-sky-50 text-sky-600 dark:bg-sky-950/40 dark:text-sky-400',
    top: 'border-t-sky-200/80 dark:border-t-sky-900/50',
    badge: 'bg-sky-100 text-sky-800 dark:bg-sky-950/50 dark:text-sky-300',
  },
  amber: {
    icon: 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400',
    top: 'border-t-amber-200/80 dark:border-t-amber-900/50',
    badge: 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
  },
  teal: {
    icon: 'bg-teal-50 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400',
    top: 'border-t-teal-200/80 dark:border-t-teal-900/50',
    badge: 'bg-teal-100 text-teal-800 dark:bg-teal-950/50 dark:text-teal-300',
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
    column: { type: Boolean, default: false },
    accent: { type: String, default: 'sky' },
    headerIcon: { type: [Object, Function], default: null },
    emptyTextKey: { type: String, default: 'docs_empty_attachments' },
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

    const accentStyle = computed(() => DOC_GROUP_ACCENT[props.accent] || DOC_GROUP_ACCENT.sky)

    const emptyMessage = computed(() => {
      const key = `request_detail.${props.emptyTextKey}`
      const tr = t(key)
      return tr !== key ? tr : t('request_detail.docs_empty_attachments')
    })

    const fileList = () =>
      props.files.length === 0
        ? h('p', {
            class: props.column
              ? 'flex flex-1 items-center justify-center rounded-xl border border-dashed border-slate-200 px-3 py-8 text-center text-sm leading-relaxed text-slate-400 dark:border-slate-700 dark:text-slate-500'
              : 'rounded-lg border border-dashed border-slate-200 px-3 py-5 text-center text-sm text-slate-400 dark:border-slate-700 dark:text-slate-500',
          }, emptyMessage.value)
        : props.files.map((a) =>
            h('div', {
              key: a.id,
              class: ['flex items-center gap-2 rounded-lg border px-2.5 py-2 transition',
                props.column ? 'flex-col items-stretch sm:flex-row sm:items-center' : 'gap-3 px-3 py-2.5',
                String(props.highlightId) === String(a.id) ? 'border-teal-300 bg-teal-50/60 dark:border-teal-700 dark:bg-teal-950/30' : 'border-slate-200 dark:border-slate-800'],
            }, [
              h('div', { class: 'min-w-0 flex-1' }, [
                h('p', { class: ['font-medium text-slate-800 dark:text-slate-200', props.column ? 'line-clamp-2 text-sm leading-snug' : 'truncate text-sm'] }, a.original_name || '—'),
                h('p', { class: 'mt-0.5 text-xs tabular-nums text-slate-500 dark:text-slate-400' }, [
                  props.fmtSize(a.size) ? `${props.fmtSize(a.size)} · ` : '',
                  props.fmtDate(a.created_at),
                  a.ocr_status === 'completed' ? ' · OCR ✓' : (a.ocr_status === 'queued' || a.ocr_status === 'processing') ? ' · OCR…' : '',
                ].join('')),
              ]),
              h('div', { class: ['flex shrink-0 items-center gap-1', props.column ? 'justify-end' : ''] }, [
                props.previewable(a) ? actionBtn(EyeIcon, t('request_detail.ops_preview'), () => emit('preview', a)) : null,
                actionBtn(ArrowDownTraySolid, t('request_detail.download_action'), () => emit('download', a)),
                props.canOcr ? actionBtn(SparklesIcon, t('request_detail.ops_run_ocr'), () => emit('ocr', a.id)) : null,
                props.canDelete ? actionBtn(TrashIcon, t('request_detail.delete_action'), () => emit('delete', a), 'danger') : null,
              ]),
            ]),
          )

    return () => {
      if (!props.column) {
        return h('section', { class: 'rounded-2xl border border-slate-200 p-4 dark:border-slate-800 sm:p-5' }, [
          h('div', { class: 'flex items-center justify-between gap-2' }, [
            h('h3', { class: 'text-sm font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400' }, props.title),
            props.canUpload
              ? h('label', { class: 'inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800' }, [
                  h(ArrowDownTraySolid, { class: 'h-4 w-4 rotate-180' }),
                  props.uploading ? t('request_detail.docs_ocr_running') : t('request_detail.ops_upload_file'),
                  h('input', { type: 'file', class: 'hidden', disabled: props.uploading, onChange }),
                ])
              : null,
          ]),
          h('div', { class: 'mt-3 space-y-2' }, [fileList()]),
          slots.default ? slots.default() : null,
        ])
      }

      return h('section', {
        class: ['flex h-full min-h-[22rem] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900'],
      }, [
        h('div', { class: ['flex shrink-0 items-start justify-between gap-2 border-b border-slate-100 px-4 py-3 dark:border-slate-800', accentStyle.value.top] }, [
          h('div', { class: 'flex min-w-0 items-center gap-2.5' }, [
            props.headerIcon
              ? h('span', { class: ['flex h-8 w-8 shrink-0 items-center justify-center rounded-lg', accentStyle.value.icon] }, [
                  h(props.headerIcon, { class: 'h-4 w-4', 'aria-hidden': 'true' }),
                ])
              : null,
            h('div', { class: 'min-w-0' }, [
              h('h3', { class: 'text-sm font-semibold uppercase tracking-wide text-slate-600 dark:text-slate-300' }, props.title),
              h('p', { class: 'mt-0.5 text-xs text-slate-500 dark:text-slate-400' }, t('request_detail.ops_docs_file_count', { n: props.files.length })),
            ]),
          ]),
          props.canUpload
              ? h('label', { class: 'inline-flex shrink-0 cursor-pointer items-center gap-1 rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800' }, [
                h(ArrowDownTraySolid, { class: 'h-3.5 w-3.5 rotate-180' }),
                props.uploading ? '…' : t('request_detail.ops_upload_file'),
                h('input', { type: 'file', class: 'hidden', disabled: props.uploading, onChange }),
              ])
            : h('span', { class: ['inline-flex h-6 min-w-[1.5rem] items-center justify-center rounded-full px-1.5 text-xs font-semibold tabular-nums', accentStyle.value.badge] }, String(props.files.length)),
        ]),
        h('div', { class: 'flex min-h-0 flex-1 flex-col gap-3 p-3' }, [
          h('div', { class: 'flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto overscroll-contain' }, [fileList()]),
          slots.default ? h('div', { class: 'shrink-0 space-y-2 border-t border-slate-100 pt-3 dark:border-slate-800' }, [slots.default()]) : null,
        ]),
      ])
    }
  },
}
</script>
