<template>
  <div
    :class="[
      'flex flex-col overflow-hidden bg-slate-50',
      isDeptRequestDetailRoute ? 'min-h-0 min-w-0 flex-1' : 'h-screen',
    ]"
  >
    <div v-if="loading" class="flex flex-1 items-center justify-center px-4 py-12 text-sm text-slate-500">
      {{ t('request_detail.page_loading') }}
    </div>

    <template v-else-if="req">
      <header
        class="shrink-0 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur-md supports-[backdrop-filter]:bg-white/85"
      >
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-2.5">
          <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
            <RouterLink
              :to="isDeptRequestDetailRoute ? { name: 'deptDashboard' } : '/requests'"
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
              :aria-label="isDeptRequestDetailRoute ? t('dept.aria_back_pending') : t('request_detail.aria_back_list')"
            >
              <ArrowLeftIcon class="h-4 w-4" />
            </RouterLink>
            <div
              class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-600 ring-1 ring-teal-600/15 sm:flex"
            >
              <TruckIcon class="h-5 w-5" aria-hidden="true" />
            </div>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-1.5">
                <h1 class="text-sm font-bold tracking-tight text-slate-900 sm:text-base">
                  {{ requestRefCode }}
                </h1>
                <StatusBadge :status="req.status" />
                <span
                  v-if="showRecurringBadge"
                  class="inline-flex items-center gap-0.5 rounded-full bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-900 ring-1 ring-indigo-600/15"
                >
                  <ArrowPathIcon class="h-3 w-3 shrink-0 text-indigo-700" aria-hidden="true" />
                  {{ t('request_detail.badge_recurring') }}
                </span>
              </div>
              <p class="mt-0.5 text-[11px] text-slate-600 sm:text-xs">
                {{ t('request_detail.meta_created', { dt: fmt(req.created_at) }) }}
                <template v-if="req.trip">
                  <span class="text-slate-300"> · </span>
                  <RouterLink
                    :to="`/trips/${req.trip.id}`"
                    class="font-medium text-teal-700 underline decoration-teal-600/30 underline-offset-2 hover:decoration-teal-800"
                  >
                    {{ t('request_detail.trip_link', { id: req.trip.id }) }}
                  </RouterLink>
                </template>
              </p>
            </div>
          </div>

          <div class="flex w-full shrink-0 flex-wrap items-center justify-end gap-2 sm:w-auto">
            <button
              type="button"
              class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border px-3 text-xs font-medium shadow-sm transition sm:text-sm"
              :class="
                pdfExportDisabled
                  ? 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400'
                  : 'border-teal-200 bg-white text-teal-800 hover:bg-teal-50'
              "
              :disabled="pdfBusy || pdfExportDisabled"
              :title="pdfExportDisabled ? t('request_detail.pdf_locked_tooltip') : t('request_detail.export_pdf')"
              @click="downloadRequestPdf"
            >
              <span
                v-if="pdfBusy"
                class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-teal-500/30 border-t-teal-600"
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
                class="min-h-9 !border-rose-200 !bg-white !px-3 !py-2 text-xs font-semibold !text-rose-700 shadow-sm hover:!bg-rose-50 sm:text-sm"
                @click="onDecideClick('reject')"
              >
                {{ t('request_detail.dept_reject') }}
              </Button>
              <Button
                :loading="acting"
                class="min-h-9 !bg-teal-600 !px-4 !py-2 text-xs font-semibold text-white shadow-sm hover:!bg-teal-700 sm:text-sm"
                @click="onDecideClick('approve')"
              >
                {{ t('request_detail.header_approve') }}
              </Button>
            </div>
          </div>
        </div>

        <p
          v-if="msg && req.status === 'pending' && canApprove && req.trip_type === 'door_to_door'"
          class="border-t border-amber-100 bg-amber-50/80 px-4 py-1.5 text-xs font-medium text-amber-950 sm:text-sm"
        >
          {{ msg }}
        </p>
      </header>

      <div
        v-if="req.status === 'rejected'"
        class="shrink-0 border-b-2 border-rose-400 bg-gradient-to-r from-rose-100 via-rose-50 to-white px-4 py-4 shadow-sm ring-1 ring-rose-500/15 sm:px-6"
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

      <div class="flex min-h-0 min-w-0 flex-1 flex-col lg:flex-row">
        <aside
          class="w-full shrink-0 overflow-y-auto border-b border-slate-200 bg-slate-50/90 lg:w-72 lg:max-h-none lg:border-b-0 lg:border-r xl:w-80"
          :class="'max-h-[min(40vh,22rem)] lg:max-h-full'"
        >
          <div class="space-y-4 p-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
              <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ t('request_detail.aside_requester') }}</p>
              <div class="mt-3 flex items-center gap-2.5">
                <img
                  v-if="req.requester?.avatar_url"
                  :src="req.requester.avatar_url"
                  alt=""
                  class="h-10 w-10 rounded-full object-cover ring-2 ring-slate-100"
                />
                <div
                  v-else
                  class="flex h-10 w-10 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800 ring-2 ring-teal-50"
                >
                  {{ requesterInitials }}
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-slate-900">{{ req.requester?.name ?? '—' }}</p>
                  <p class="text-xs text-slate-600">{{ requesterSubtitle }}</p>
                </div>
              </div>
              <ul class="mt-3 space-y-1.5 border-t border-slate-100 pt-3 text-xs text-slate-700">
                <li v-if="req.requester?.email" class="flex gap-2">
                  <span class="shrink-0 font-medium text-slate-500">{{ t('request_detail.lbl_email') }}</span>
                  <span class="min-w-0 break-all text-slate-800">{{ req.requester.email }}</span>
                </li>
                <li v-if="req.requester?.phone" class="flex gap-2">
                  <span class="shrink-0 font-medium text-slate-500">{{ t('request_detail.lbl_phone') }}</span>
                  <span class="text-slate-800">{{ req.requester.phone }}</span>
                </li>
                <li v-if="req.requester?.employee_code" class="flex gap-2">
                  <span class="shrink-0 font-medium text-slate-500">{{ t('request_detail.lbl_employee_code') }}</span>
                  <span class="text-slate-800">{{ req.requester.employee_code }}</span>
                </li>
                <li v-if="req.wizard_snapshot?.form?.requester_unit" class="flex gap-2">
                  <span class="shrink-0 font-medium text-slate-500">{{ t('request_detail.lbl_requester_unit') }}</span>
                  <span class="text-slate-800">{{ req.wizard_snapshot.form.requester_unit }}</span>
                </li>
              </ul>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
              <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ t('request_detail.aside_vehicle_request') }}</p>
              <div
                class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-700"
              >
                <TruckIcon class="h-3.5 w-3.5 text-teal-600" aria-hidden="true" />
                {{ labelTripType(req.trip_type) }}
              </div>
              <dl class="mt-3 space-y-2 border-t border-slate-100 pt-3 text-xs">
                <div>
                  <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ t('request_detail.lbl_passenger_load') }}</dt>
                  <dd class="mt-1 flex items-center gap-1.5 text-slate-900">
                    <CubeIcon class="h-4 w-4 shrink-0 text-teal-600" />
                    <span>{{ passengerOrCargoLine }}</span>
                  </dd>
                </div>
              </dl>
            </div>
          </div>
        </aside>

        <div class="flex min-h-0 min-w-0 flex-1 flex-col bg-slate-50">
          <nav
            class="flex shrink-0 flex-wrap gap-1 border-b border-slate-200 bg-white px-2 py-1.5 sm:px-3"
            role="tablist"
            :aria-label="t('request_detail.tablist_aria')"
          >
            <button
              type="button"
              role="tab"
              :aria-selected="activeTab === 'route'"
              class="rounded-lg px-3 py-2 text-xs font-semibold transition sm:text-sm"
              :class="
                activeTab === 'route'
                  ? 'bg-teal-50 text-teal-900 ring-1 ring-teal-600/20'
                  : 'text-slate-600 hover:bg-slate-50'
              "
              @click="activeTab = 'route'"
            >
              {{ t('request_detail.tab_route') }}
            </button>
            <button
              type="button"
              role="tab"
              :aria-selected="activeTab === 'form'"
              class="relative inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold transition sm:text-sm"
              :class="
                activeTab === 'form'
                  ? 'bg-teal-50 text-teal-900 ring-1 ring-teal-600/20'
                  : 'text-slate-600 hover:bg-slate-50'
              "
              @click="activeTab = 'form'"
            >
              {{ t('request_detail.tab_form') }}
              <span
                v-if="approvalTabNeedsFocus"
                class="ml-1.5 inline-flex h-2 w-2 shrink-0 rounded-full bg-teal-500"
                aria-hidden="true"
              />
            </button>
            <button
              type="button"
              role="tab"
              :aria-selected="activeTab === 'docs'"
              class="rounded-lg px-3 py-2 text-xs font-semibold transition sm:text-sm"
              :class="
                activeTab === 'docs'
                  ? 'bg-teal-50 text-teal-900 ring-1 ring-teal-600/20'
                  : 'text-slate-600 hover:bg-slate-50'
              "
              @click="activeTab = 'docs'"
            >
              {{ t('request_detail.tab_docs') }}
            </button>
          </nav>

          <div class="min-h-0 flex-1 overflow-y-auto p-3 sm:p-4">
            <div v-show="activeTab === 'route'" class="space-y-4">
              <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ t('request_detail.section_progress') }}</p>
                <div class="mt-3 overflow-x-auto pb-1 [-webkit-overflow-scrolling:touch]">
                  <div class="flex items-start" :class="stepperTrackMinClass">
                    <template v-for="(step, idx) in stepperSteps" :key="step.key">
                      <div class="flex min-w-0 flex-1 flex-col items-center text-center">
                        <div
                          class="flex h-7 w-7 items-center justify-center rounded-full border-2 text-[10px] font-semibold transition-colors"
                          :class="stepCircleClass(step.state)"
                        >
                          <CheckIcon v-if="step.state === 'done'" class="h-3.5 w-3.5" />
                          <HandThumbUpIcon
                            v-else-if="
                              step.key === 'approved' && (step.state === 'upcoming' || step.state === 'current')
                            "
                            class="h-3.5 w-3.5"
                            :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                          />
                          <CurrencyDollarIcon
                            v-else-if="step.key === 'price_pending' && step.state !== 'done'"
                            class="h-3.5 w-3.5"
                            :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                          />
                          <BuildingOffice2Icon
                            v-else-if="step.key === 'dept_pending' && step.state !== 'done'"
                            class="h-3.5 w-3.5"
                            :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                          />
                          <Cog6ToothIcon
                            v-else-if="step.key === 'dispatch' && step.state !== 'done'"
                            class="h-3.5 w-3.5"
                            :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                          />
                          <TruckIcon
                            v-else-if="step.key === 'running' && step.state !== 'done'"
                            class="h-3.5 w-3.5"
                            :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                          />
                          <FlagIcon
                            v-else-if="step.key === 'done' && step.state !== 'done'"
                            class="h-3.5 w-3.5"
                            :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                          />
                          <span
                            v-else-if="step.state === 'current'"
                            class="h-1.5 w-1.5 rounded-full bg-teal-600"
                          />
                          <span v-else-if="step.state === 'rejected'" class="text-[10px] font-bold">!</span>
                          <span v-else class="text-slate-300">·</span>
                        </div>
                        <p
                          class="mt-1 text-[9px] font-semibold leading-tight text-slate-800 sm:text-[10px]"
                        >
                          {{ step.label }}
                        </p>
                      </div>
                      <div
                        v-if="idx < stepperSteps.length - 1"
                        class="mx-0.5 mt-3 h-0.5 w-2 shrink-0 sm:mt-3.5 sm:w-4"
                        :class="step.state === 'done' ? 'bg-teal-500' : 'bg-slate-200'"
                        aria-hidden="true"
                      />
                    </template>
                  </div>
                </div>
              </div>

              <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
                  <div class="flex items-center gap-2 text-teal-600">
                    <InformationCircleIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
                    <h2 class="text-sm font-semibold text-slate-900 sm:text-base">{{ t('request_detail.route_map_heading') }}</h2>
                  </div>
                  <span
                    class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-[10px] font-bold tabular-nums text-teal-800 ring-1 ring-teal-600/20 sm:text-xs"
                  >
                    {{
                      costEstimate?.distanceLabel != null
                        ? t('request_detail.distance_badge_approx', { label: costEstimate.distanceLabel })
                        : t('request_detail.distance_badge_empty')
                    }}
                  </span>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-700 sm:text-sm">
                  <span
                    class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-2 py-1 font-medium ring-1 ring-slate-200/80"
                  >
                    <CalendarDaysIcon class="h-3.5 w-3.5 text-slate-400" />
                    {{ fmtDateVi(req.depart_at) }}
                  </span>
                  <span
                    class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-2 py-1 font-medium ring-1 ring-slate-200/80"
                  >
                    <ClockIcon class="h-3.5 w-3.5 text-slate-400" />
                    {{ fmtTimeWindow(req.depart_at, req.arrive_by) }}
                  </span>
                </div>
                <div class="mt-5 flex gap-3">
                  <div class="flex flex-col items-center pt-0.5">
                    <span class="h-2.5 w-2.5 rounded-full border-2 border-teal-500 bg-white shadow-sm" />
                    <span class="mt-0.5 min-h-[2.5rem] w-px flex-1 bg-gradient-to-b from-teal-400 to-teal-200" />
                    <span class="h-2.5 w-2.5 rounded-full border-2 border-teal-500 bg-white shadow-sm" />
                  </div>
                  <div class="min-w-0 flex-1 space-y-5">
                    <div>
                      <p class="text-[10px] font-bold uppercase tracking-wide text-teal-700/90">{{ t('request_detail.lbl_origin') }}</p>
                      <p class="mt-0.5 text-sm font-semibold text-slate-900">{{ req.origin || '—' }}</p>
                      <p v-if="routeSubFrom" class="mt-0.5 text-xs text-slate-500">{{ routeSubFrom }}</p>
                    </div>
                    <div>
                      <p class="text-[10px] font-bold uppercase tracking-wide text-teal-700/90">{{ t('request_detail.lbl_destination') }}</p>
                      <p class="mt-0.5 text-sm font-semibold text-slate-900">{{ req.destination || '—' }}</p>
                      <p v-if="routeSubTo" class="mt-0.5 text-xs text-slate-500">{{ routeSubTo }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                  <div class="flex items-center gap-2 text-teal-600">
                    <CalculatorIcon class="h-5 w-5 shrink-0" />
                    <h2 class="text-sm font-semibold text-slate-900 sm:text-base">{{ t('request_detail.cost_estimate_heading') }}</h2>
                  </div>
                  <Button
                    type="button"
                    variant="secondary"
                    class="shrink-0 !border-teal-200 !text-teal-900 hover:!bg-teal-50"
                    @click="pricingModalOpen = true"
                  >
                    {{ t('request_detail.reference_pricing_link') }}
                  </Button>
                </div>
                <dl class="mt-4 space-y-2 text-xs sm:text-sm">
                  <div class="flex justify-between gap-3 border-b border-slate-50 pb-2">
                    <dt class="text-slate-500">{{ t('request_detail.lbl_est_distance') }}</dt>
                    <dd class="text-right font-medium text-slate-900">{{ costEstimate?.distanceLabel ?? '—' }}</dd>
                  </div>
                  <div class="flex justify-between gap-3 border-b border-slate-50 pb-2">
                    <dt class="text-slate-500">{{ t('request_detail.lbl_suggested_vehicle_type') }}</dt>
                    <dd class="text-right font-medium text-slate-900">{{ costEstimate?.vehicleHint ?? '—' }}</dd>
                  </div>
                  <div class="flex justify-between gap-3 border-b border-slate-50 pb-2">
                    <dt class="text-slate-500">{{ t('request_detail.lbl_ref_unit_price_estimate') }}</dt>
                    <dd class="text-right font-medium text-slate-900">{{ costEstimate?.refUnitLabel ?? '—' }}</dd>
                  </div>
                  <div class="flex justify-between gap-3 border-b border-slate-50 pb-2">
                    <dt class="text-slate-500">{{ t('request_detail.lbl_toll_estimate') }}</dt>
                    <dd class="text-right font-medium text-slate-900">{{ costEstimate?.tollLabel ?? '—' }}</dd>
                  </div>
                </dl>
                <div class="mt-4 rounded-xl bg-teal-50/90 px-3 py-3 ring-1 ring-teal-600/10 sm:px-4 sm:py-4">
                  <p class="text-[11px] font-medium text-teal-900/90">{{ t('request_detail.total_per_declaration') }}</p>
                  <p class="mt-0.5 text-lg font-bold tabular-nums text-teal-600 sm:text-xl">
                    {{ costEstimate ? formatVndCurrency(costEstimate.total) : '—' }}
                  </p>
                </div>
              </div>

              <section
                v-if="showApprovalDecisionPanel"
                class="rounded-xl border-2 border-slate-200 bg-white p-4 shadow-sm"
              >
                <h2 class="border-b border-slate-200 pb-2 text-sm font-bold text-slate-900 sm:text-base">
                  {{ t('request_detail.approval_basis_heading') }}
                </h2>
                <dl class="mt-3 grid gap-3 text-xs sm:grid-cols-2 sm:text-sm lg:grid-cols-3">
                  <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ t('request_detail.lbl_status_short') }}</dt>
                    <dd class="mt-1">
                      <StatusBadge :status="req.status" />
                    </dd>
                  </div>
                  <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ t('request_detail.lbl_trip_type_short') }}</dt>
                    <dd class="mt-1 font-semibold text-slate-900">
                      {{ labelTripType(req.trip_type) }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ t('request_detail.lbl_requester_name') }}</dt>
                    <dd class="mt-1 font-semibold text-slate-900">
                      {{ req.requester?.name ?? '—' }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ t('request_detail.lbl_requester_unit') }}</dt>
                    <dd class="mt-1 text-slate-900">
                      {{ req.wizard_snapshot?.form?.requester_unit || requesterSubtitle || '—' }}
                    </dd>
                  </div>
                  <div class="sm:col-span-2">
                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ t('request_detail.lbl_usage_window') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                      {{ fmtDateVi(req.depart_at) }} · {{ fmtTimeWindow(req.depart_at, req.arrive_by) }}
                    </dd>
                  </div>
                  <div class="lg:col-span-3">
                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ t('request_detail.lbl_route_line') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                      <span>{{ req.origin || '—' }}</span>
                      <span class="mx-2 text-slate-400">→</span>
                      <span>{{ req.destination || '—' }}</span>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ t('request_detail.lbl_people_load_compact') }}</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                      {{ passengerOrCargoLine }}
                    </dd>
                  </div>
                  <div v-if="wizardPurpose" class="sm:col-span-2">
                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ t('request_detail.lbl_purpose') }}</dt>
                    <dd class="mt-1 text-slate-900">
                      {{ wizardPurpose }}
                    </dd>
                  </div>
                  <div v-if="costEstimate">
                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                      {{ t('request_detail.lbl_total_cost_declared') }}
                    </dt>
                    <dd class="mt-1 text-base font-bold tabular-nums text-teal-700 sm:text-lg">
                      {{ formatVndCurrency(costEstimate.total) }}
                    </dd>
                  </div>
                  <div v-if="showDeptDecisionSection && req.service_price != null">
                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ t('request_detail.lbl_dispatcher_unit_price') }}</dt>
                    <dd class="mt-1 text-base font-bold tabular-nums text-violet-800 sm:text-lg">
                      {{ formatVndCurrency(req.service_price) }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ t('request_detail.lbl_attachment_count') }}</dt>
                    <dd class="mt-1 font-semibold text-slate-900">
                      {{ generalAttachments.length }}
                    </dd>
                  </div>
                </dl>
              </section>
            </div>

            <div v-show="activeTab === 'form'" class="space-y-5">
              <DeptApprovalSection
                v-if="showDeptDecisionSection"
                :service-price-display="req.service_price != null ? formatVndCurrency(req.service_price) : null"
                :acting="deptActing"
                :inline-message="deptMsg"
                :reject-modal-open="deptRejectOpen"
                @approve="onDeptApproveClick"
                @reject="openDeptReject"
              />
              <RequestBm03FormTab
                v-if="req"
                v-model:passenger-draft="passengerDraft"
                :req="req"
                :fill-price-acting="fillPriceActing"
                :fill-price-msg="fillPriceMsg"
                :signed-paper-attachments="signedPaperAttachments"
                :signed-upload-component-key="`signed-${route.params.id}-${signedPaperAttachments.length}`"
                :upload-signed-fn="uploadSignedPaper"
                :signed-upload-err="signedUploadErr"
                :passenger-saving="passengerSaving"
                :passenger-patch-err="passengerPatchErr"
                :passenger-depart-locked="passengerDepartLocked"
                :passenger-dispatcher-override="passengerDispatcherOverride"
                :depart-at-formatted="req.depart_at ? fmtStepDetail(req.depart_at) : ''"
                :show-fill-price-section="showFillPriceSection"
                :show-signed-paper-section="showSignedPaperSection"
                :show-reset-clone-btn="showResetCloneBtn"
                :show-passenger-adjust-section="showPassengerAdjustSection"
                :approval-tab-needs-focus="approvalTabNeedsFocus"
                :reset-clone-busy="resetCloneBusy"
                @save-row-prices="onSaveRowPrices"
                @open-reference-pricing="pricingModalOpen = true"
                @download-signed="downloadFile"
                @signed-uploaded="onSignedUploaded"
                @save-passenger="savePassengerDraft"
                @reset-clone="onResetCloneRequest"
              />
            </div>
            <div v-show="activeTab === 'docs'" class="space-y-5">
              <!-- Tài liệu đính kèm -->
              <section class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.03]">
                <header class="border-b border-slate-100 bg-gradient-to-r from-teal-50/90 via-white to-white px-4 py-3.5 sm:px-5">
                  <div class="flex items-start gap-3">
                    <div
                      class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-600/10 text-teal-700 ring-1 ring-teal-600/15"
                    >
                      <PaperClipIcon class="h-5 w-5" aria-hidden="true" />
                    </div>
                    <div class="min-w-0 flex-1">
                      <h2 class="text-sm font-semibold text-slate-900 sm:text-base">
                        {{ t('request_detail.docs_section_heading') }}
                      </h2>
                      <p class="mt-0.5 text-xs leading-relaxed text-slate-600">
                        {{ t('request_detail.docs_section_lead') }}
                      </p>
                    </div>
                    <span
                      class="shrink-0 rounded-full bg-white px-2.5 py-0.5 text-[10px] font-bold tabular-nums text-slate-700 ring-1 ring-slate-200"
                    >
                      {{ generalAttachments.length }}
                    </span>
                  </div>
                </header>
                <div class="p-4 sm:p-5">
                  <ul v-if="generalAttachments.length" class="space-y-2">
                    <li
                      v-for="a in generalAttachments"
                      :key="a.id"
                      class="group flex items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50/50 px-3 py-2.5 transition hover:border-teal-200/70 hover:bg-white hover:shadow-sm"
                    >
                      <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200/80 group-hover:text-teal-600"
                      >
                        <DocumentIcon class="h-4 w-4" aria-hidden="true" />
                      </div>
                      <div class="min-w-0 flex-1">
                        <p
                          class="truncate text-sm font-medium text-slate-900"
                          :title="a.original_name || t('request_detail.file_fallback_name', { id: a.id })"
                        >
                          {{ a.original_name || t('request_detail.file_fallback_name', { id: a.id }) }}
                        </p>
                        <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-slate-500">
                          <span>{{ fmtFileSize(a.size_bytes) }}</span>
                          <span v-if="a.kind" class="rounded-md bg-slate-200/60 px-1.5 py-px font-medium text-slate-600">
                            {{ attachmentKindLabel(a.kind) }}
                          </span>
                        </p>
                      </div>
                      <div class="flex shrink-0 items-center gap-1">
                        <button
                          type="button"
                          class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-teal-700 transition hover:border-teal-200 hover:bg-teal-50"
                          :title="t('request_detail.download_action')"
                          @click="downloadFile(a)"
                        >
                          <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />
                        </button>
                        <button
                          v-if="canDeleteAttachment"
                          type="button"
                          class="flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200/80 bg-white text-rose-600 transition hover:bg-rose-50 disabled:opacity-50"
                          :disabled="deletingId === a.id"
                          :title="t('request_detail.delete_action')"
                          @click="removeAttachment(a)"
                        >
                          <TrashIcon class="h-4 w-4" aria-hidden="true" />
                        </button>
                      </div>
                    </li>
                  </ul>
                  <p
                    v-else
                    class="rounded-xl border border-dashed border-slate-200 bg-slate-50/40 px-4 py-6 text-center text-xs text-slate-500 sm:text-sm"
                  >
                    {{ t('request_detail.docs_empty_attachments') }}
                  </p>

                  <div v-if="attachErr" class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-800 ring-1 ring-rose-200/80">
                    {{ attachErr }}
                  </div>

                  <div class="mt-4 border-t border-slate-100 pt-4">
                    <FileUpload
                      v-if="canUploadAttachment"
                      :key="`doc-${route.params.id}-${generalAttachments.length}`"
                      :label="t('request_detail.add_attachment_label')"
                      drag-drop
                      compact
                      :upload-fn="uploadRequestDocument"
                      @uploaded="onDocUploaded"
                    />
                    <p
                      v-else
                      class="rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-3 text-center text-xs text-slate-500"
                    >
                      {{ t('request_detail.no_attachment_download_perm') }}
                    </p>
                  </div>
                </div>
              </section>

              <!-- Phiếu đã ký (người đề xuất) -->
              <section
                v-if="signedPaperAttachments.length || req.status === 'approved'"
                class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.03]"
              >
                <header class="border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-4 py-3.5 sm:px-5">
                  <div class="flex items-start gap-3">
                    <div
                      class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-800/5 text-slate-700 ring-1 ring-slate-300/50"
                    >
                      <ClipboardDocumentIcon class="h-5 w-5" aria-hidden="true" />
                    </div>
                    <div class="min-w-0 flex-1">
                      <h2 class="text-sm font-semibold text-slate-900 sm:text-base">
                        {{ t('request_detail.docs_signed_paper_heading') }}
                      </h2>
                      <p class="mt-0.5 text-xs leading-relaxed text-slate-600">
                        {{ t('request_detail.docs_signed_paper_lead') }}
                      </p>
                    </div>
                  </div>
                </header>
                <div class="p-4 sm:p-5">
                  <ul v-if="signedPaperAttachments.length" class="space-y-2">
                    <li
                      v-for="a in signedPaperAttachments"
                      :key="a.id"
                      class="flex items-center gap-3 rounded-xl border border-teal-100 bg-teal-50/30 px-3 py-2.5"
                    >
                      <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-slate-900">
                          {{ a.original_name || t('request_detail.file_fallback_name', { id: a.id }) }}
                        </p>
                        <p class="mt-0.5 text-[11px] text-slate-600">{{ fmtFileSize(a.size_bytes) }}</p>
                      </div>
                      <button
                        type="button"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-teal-700"
                        @click="downloadFile(a)"
                      >
                        <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />
                        {{ t('request_detail.download_action') }}
                      </button>
                    </li>
                  </ul>
                  <p
                    v-else
                    class="rounded-xl border border-dashed border-amber-200/80 bg-amber-50/40 px-4 py-4 text-center text-xs leading-relaxed text-amber-950/90 sm:text-sm"
                  >
                    {{ t('request_detail.docs_signed_paper_empty') }}
                  </p>
                </div>
              </section>

              <!-- Phiếu giấy & OCR -->
              <section
                v-if="paperScans.length || canUploadAttachment || req.paper_status === 'pending' || req.paper_status === 'received'"
                class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.03]"
              >
                <header class="border-b border-slate-100 bg-gradient-to-r from-violet-50/50 via-white to-white px-4 py-3.5 sm:px-5">
                  <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                      <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-600/10 text-violet-800 ring-1 ring-violet-500/15"
                      >
                        <DocumentTextIcon class="h-5 w-5" aria-hidden="true" />
                      </div>
                      <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-slate-900 sm:text-base">
                          {{ t('request_detail.paper_ocr_heading') }}
                        </h2>
                        <p class="mt-0.5 max-w-prose text-xs leading-relaxed text-slate-600">
                          {{ t('request_detail.paper_ocr_lead') }}
                        </p>
                      </div>
                    </div>
                    <span
                      v-if="req.paper_status === 'received'"
                      class="shrink-0 rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-teal-800 ring-1 ring-teal-600/25"
                    >
                      {{ t('request_detail.paper_received_badge') }}
                    </span>
                    <span
                      v-else-if="req.paper_status === 'pending'"
                      class="shrink-0 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-900 ring-1 ring-amber-500/25"
                    >
                      {{ t('request_detail.paper_pending_badge') }}
                    </span>
                  </div>
                </header>

                <div class="space-y-4 p-4 sm:p-5">
                  <div
                    v-if="req.paper_status === 'received' && (req.paper_reference || req.paper_received_at)"
                    class="grid gap-3 rounded-xl bg-slate-50/80 p-3 ring-1 ring-slate-200/70 sm:grid-cols-2"
                  >
                    <div v-if="req.paper_reference">
                      <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">
                        {{ t('request_detail.paper_ref_label_short') }}
                      </p>
                      <p class="mt-1 text-sm font-semibold text-slate-900">{{ req.paper_reference }}</p>
                    </div>
                    <div v-if="req.paper_received_at">
                      <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">
                        {{ t('request_detail.paper_received_at_short') }}
                      </p>
                      <p class="mt-1 text-sm font-semibold text-slate-900">{{ fmt(req.paper_received_at) }}</p>
                    </div>
                  </div>

                  <ul v-if="paperScans.length" class="space-y-3">
                    <li
                      v-for="a in paperScans"
                      :key="a.id"
                      class="overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.02]"
                    >
                      <div
                        class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/40 px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-4"
                      >
                        <div class="min-w-0 flex-1">
                          <button
                            type="button"
                            class="max-w-full truncate text-left text-sm font-semibold text-teal-800 hover:underline"
                            :title="a.original_name || t('request_detail.download_file_fallback')"
                            @click="downloadFile(a)"
                          >
                            {{ a.original_name || t('request_detail.download_file_fallback') }}
                          </button>
                          <p class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                            <span>{{ fmtFileSize(a.size_bytes) }}</span>
                            <span
                              v-if="a.mime_type"
                              class="rounded-md bg-white px-1.5 py-px font-medium text-slate-600 ring-1 ring-slate-200/80"
                            >
                              {{ a.mime_type }}
                            </span>
                            <span
                              v-if="a.ocr_processed_at"
                              class="inline-flex items-center gap-1 rounded-md bg-teal-50 px-1.5 py-px font-medium text-teal-800"
                            >
                              <SparklesIcon class="h-3 w-3" aria-hidden="true" />
                              {{ t('request_detail.ocr_result_prefix') }} {{ fmt(a.ocr_processed_at) }}
                            </span>
                          </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                          <Button
                            variant="secondary"
                            type="button"
                            class="!inline-flex !items-center !gap-1.5 !border-violet-200 !text-violet-900 hover:!bg-violet-50"
                            :loading="ocrBusy === a.id"
                            @click="runOcr(a.id)"
                          >
                            <SparklesIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                            {{ a.ocr_processed_at ? t('request_detail.ocr_rerun') : t('request_detail.ocr_run') }}
                          </Button>
                          <button
                            v-if="canDeleteAttachment"
                            type="button"
                            class="inline-flex h-9 items-center gap-1 rounded-lg border border-rose-200/80 px-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 disabled:opacity-50"
                            :disabled="deletingId === a.id"
                            @click="removeAttachment(a)"
                          >
                            <TrashIcon class="h-4 w-4" aria-hidden="true" />
                            {{ deletingId === a.id ? '…' : t('request_detail.delete_scan_action') }}
                          </button>
                        </div>
                      </div>
                      <div v-if="a.ocr_text" class="px-3 py-3 sm:px-4">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">
                          {{ t('request_detail.ocr_text_label') }}
                        </p>
                        <pre
                          class="mt-2 max-h-44 overflow-auto whitespace-pre-wrap rounded-lg border border-slate-200/80 bg-slate-50/80 p-3 font-mono text-[11px] leading-relaxed text-slate-800 sm:text-xs"
                        >{{ a.ocr_text }}</pre>
                      </div>
                    </li>
                  </ul>
                  <p
                    v-else-if="canUploadAttachment"
                    class="rounded-xl border border-dashed border-slate-200 bg-slate-50/30 px-4 py-5 text-center text-xs text-slate-500"
                  >
                    {{ t('request_detail.docs_empty_paper_scan') }}
                  </p>

                  <div v-if="ocrErr" class="rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-800 ring-1 ring-rose-200/80">
                    {{ ocrErr }}
                  </div>

                  <div v-if="canUploadAttachment" class="rounded-xl border border-dashed border-violet-200/60 bg-violet-50/20 p-3">
                    <FileUpload
                      :key="`paper-${route.params.id}-${paperScans.length}`"
                      :label="t('request_detail.attach_paper_scan_label')"
                      drag-drop
                      compact
                      :upload-fn="uploadPaperScan"
                      @uploaded="load"
                    />
                  </div>

                  <div
                    v-if="req.paper_status === 'pending' && canManagePaper"
                    class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4"
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
                    v-else-if="req.paper_status === 'received' && canManagePaper"
                    class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4"
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
                </div>
              </section>
            </div>
          </div>
        </div>
      </div>

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
            class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-teal-200 bg-white shadow-2xl ring-1 ring-slate-900/5"
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
            <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-4 pt-3 sm:px-5 sm:pt-4">
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
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowLeftIcon,
  ArrowPathIcon,
  BuildingOffice2Icon,
  CalculatorIcon,
  CalendarDaysIcon,
  ClipboardDocumentIcon,
  ClockIcon,
  Cog6ToothIcon,
  CubeIcon,
  CurrencyDollarIcon,
  DocumentTextIcon,
  DocumentIcon,
  ArrowDownTrayIcon,
  TrashIcon,
  SparklesIcon,
  FlagIcon,
  HandThumbUpIcon,
  InformationCircleIcon,
  PaperClipIcon,
  TruckIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import { CheckIcon } from '@heroicons/vue/24/solid'
import Button from '../../components/ui/Button.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import Input from '../../components/ui/Input.vue'
import FileUpload from '../../components/ui/FileUpload.vue'
import RequestBm03FormTab from '../../components/requests/RequestBm03FormTab.vue'
import DeptApprovalSection from '../../components/requests/DeptApprovalSection.vue'
import RejectReasonModal from '../../components/requests/RejectReasonModal.vue'
import ReferencePricingReadOnlyBody from '../../components/pricing/ReferencePricingReadOnlyBody.vue'
import { deleteAttachment, runAttachmentOcr, uploadAttachment } from '../../api/attachments'
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

const stepperTrackMinClass = computed(() => {
  if (!req.value) return 'min-w-[560px]'
  return req.value.trip_type === 'door_to_door' ? 'min-w-[560px]' : 'min-w-[720px]'
})




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

const passengerDispatcherOverride = computed(
  () => auth.hasPermission('trip.view_all') && !!req.value?.locked_at,
)

const passengerDepartLocked = computed(() => {
  if (auth.hasPermission('trip.view_all')) return false
  const st = req.value?.status
  if (!isCurrentUserRequester.value || !req.value?.dispatch_request_template_id) return true
  if (req.value?.locked_at) return true
  if (st !== 'pending' && st !== 'price_filled') return true
  const h = hoursUntilDepartIso(req.value?.depart_at)
  return h == null || h < 24
})

const showPassengerAdjustSection = computed(() => {
  if (!req.value?.dispatch_request_template_id) return false
  if (auth.hasPermission('trip.view_all')) {
    return ['pending', 'price_filled', 'approved'].includes(String(req.value?.status || ''))
  }
  return (
    isCurrentUserRequester.value &&
    ['pending', 'price_filled'].includes(String(req.value?.status || ''))
  )
})

const showResetCloneBtn = computed(() => {
  if (!isCurrentUserRequester.value || !auth.hasPermission('request.create')) return false
  return ['approved', 'rejected'].includes(String(req.value?.status || ''))
})

const activeTab = ref('route')

/** Tab Biểu mẫu BM.03 — badge khi có hành động / cảnh báo cần xem (empty state trong tab). */
const approvalTabNeedsFocus = computed(() => {
  const r = req.value
  if (!r) return false
  return !!(
    r.dispatch_package_cost_alert ||
    r.dispatch_package_budget_alert ||
    showResetCloneBtn.value ||
    showPassengerAdjustSection.value ||
    showFillPriceSection.value ||
    showDeptDecisionSection.value ||
    showSignedPaperSection.value
  )
})

watch(
  approvalTabNeedsFocus,
  (need, was) => {
    if (need && was !== true && req.value) activeTab.value = 'form'
  },
  { immediate: true },
)

const showRecurringBadge = computed(() => !!req.value?.dispatch_request_template_id)

const signedPaperAttachments = computed(() => {
  const list = req.value?.attachments ?? []
  return list.filter((a) => a.kind === 'signed_paper')
})

const paperScans = computed(() => {
  const list = req.value?.attachments ?? []
  return list.filter((a) => a.kind === 'paper_scan')
})

const generalAttachments = computed(() => {
  const list = req.value?.attachments ?? []
  return list.filter((a) => a.kind !== 'paper_scan' && a.kind !== 'signed_paper')
})

function fmtFileSize(bytes) {
  const n = Number(bytes)
  if (!Number.isFinite(n) || n < 0) return '—'
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  return `${(n / (1024 * 1024)).toFixed(1)} MB`
}

function attachmentKindLabel(kind) {
  const k = kind || 'file'
  const key = `request_detail.attachment_kind_${k}`
  const translated = t(key)
  return translated !== key ? translated : k
}

const requesterSubtitle = computed(() => {
  const u = req.value?.wizard_snapshot?.form?.requester_unit
  if (u?.trim()) return u.trim()
  const code = req.value?.requester?.employee_code
  if (code) return t('request_detail.requester_employee_line', { code })
  return req.value?.requester?.email ?? '—'
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

const costEstimate = computed(() => {
  const r = req.value
  const snap = r?.wizard_snapshot
  if (!snap?.form) return null

  const f = snap.form
  const cargoExtra = parseMoneyVnd
  let extras = 0
  if (f.need_porters) extras += cargoExtra(f.porter_cost)
  if (f.interprovincial) extras += cargoExtra(f.interprovincial_cost)
  if (f.e1_use_3plus_days) extras += cargoExtra(f.e1_extra_cost)
  if (f.e2_door_pickup) extras += cargoExtra(f.e2_door_cost)
  if (f.e2_driver_self) extras += cargoExtra(f.e2_driver_self_cost)
  if (f.e2_after_21h) extras += cargoExtra(f.e2_after_21h_cost)

  const rowTotal = (row) => cargoExtra(row.unit_price) + cargoExtra(row.extra_fee)
  const totalPass = (snap.passengerRows ?? []).reduce((s, row) => s + rowTotal(row), 0)
  const totalBus = (snap.businessRows ?? []).reduce((s, row) => s + rowTotal(row), 0)
  const cargoCosts = (snap.cargoRows ?? []).reduce((s, row) => s + cargoExtra(row.cost), 0)

  const total = extras + totalPass + totalBus + cargoCosts

  const passU = snap.passengerRows?.[0]?.unit_price
  const busU = snap.businessRows?.[0]?.unit_price
  const refRaw = passU || busU
  const refUnitLabel =
    refRaw != null && String(refRaw).trim() !== '' ? formatVndCurrency(cargoExtra(refRaw)) : null

  const toll = f.interprovincial ? cargoExtra(f.interprovincial_cost) : 0
  const tollLabel = f.interprovincial && toll > 0 ? formatVndCurrency(toll) : null

  const wRaw = (snap.cargoRows ?? []).map((c) => c.weight).find((x) => String(x ?? '').trim())
  let vehicleHint = null
  if (wRaw) {
    const n = parseFloat(String(wRaw).replace(',', '.'))
    if (Number.isFinite(n)) vehicleHint = t('request_detail.vehicle_load_estimate_tons', { n })
    else vehicleHint = String(wRaw)
  }

  return {
    distanceLabel: null,
    vehicleHint,
    refUnitLabel,
    tollLabel,
    total,
  }
})

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

const stepperSteps = computed(() => {
  const r = req.value
  if (!r) return []
  const trip = r.trip
  const tripSt = trip?.status
  const st = r.status

  const approvedAt = trip?.created_at
  const pendingEndAt = st === 'rejected' ? r.updated_at : approvedAt
  const isAssignedOrMore =
    trip &&
    ['assigned', 'driver_confirmed', 'in_progress', 'completed'].includes(tripSt)
  const dispatchDoneAt =
    tripSt === 'approved'
      ? null
      : isAssignedOrMore
        ? ['assigned', 'driver_confirmed'].includes(tripSt)
          ? trip.updated_at
          : trip.started_at || trip.updated_at
        : null
  const runningAt = trip?.started_at
  const completedAt = trip?.completed_at

  const deptFlow = r.trip_type !== 'door_to_door'

  if (!deptFlow) {
    const steps = [
      { key: 'created', label: t('request_detail.step_label_created'), sub: fmtStepDetail(r.created_at), state: 'upcoming' },
      { key: 'pending', label: t('request_detail.step_label_pending'), sub: '', state: 'upcoming' },
      { key: 'approved', label: t('request_detail.step_label_approved_short'), sub: '', state: 'upcoming' },
      { key: 'dispatch', label: t('request_detail.step_label_dispatch'), sub: '', state: 'upcoming' },
      { key: 'running', label: t('request_detail.step_label_running'), sub: '', state: 'upcoming' },
      { key: 'done', label: t('request_detail.step_label_done'), sub: '', state: 'upcoming' },
    ]

    steps[1].sub =
      st === 'pending' || st === 'rejected'
        ? fmtStepDetail(r.created_at)
        : pendingEndAt
          ? fmtStepDetail(pendingEndAt)
          : '—'

    steps[2].sub = approvedAt ? fmtStepDetail(approvedAt) : '—'

    steps[3].sub =
      tripSt === 'approved'
        ? '—'
        : dispatchDoneAt
          ? fmtStepDetail(dispatchDoneAt)
          : '—'

    steps[4].sub =
      tripSt === 'in_progress' || tripSt === 'completed'
        ? fmtStepDetail(runningAt)
        : ['assigned', 'driver_confirmed'].includes(tripSt)
          ? fmtStepDetail(trip.updated_at)
          : '—'

    steps[5].sub = tripSt === 'completed' && completedAt ? fmtStepDetail(completedAt) : '—'

    let active = 1
    if (st === 'draft') active = 0
    else if (st === 'pending' || st === 'rejected') active = 1
    else if (st === 'cancelled') active = 1
    else if (st === 'approved') {
      if (!trip) active = 2
      else if (tripSt === 'approved') active = 3
      else if (['assigned', 'driver_confirmed'].includes(tripSt)) active = 4
      else if (tripSt === 'in_progress') active = 4
      else if (tripSt === 'completed') active = 5
      else active = 2
    }

    steps[0].state = st === 'draft' ? 'current' : 'done'
    for (let i = 1; i < steps.length; i++) {
      if (i < active) steps[i].state = 'done'
      else if (i === active) {
        if (st === 'rejected' && i === 1) steps[i].state = 'rejected'
        else if (st === 'cancelled' && i === 1) steps[i].state = 'current'
        else steps[i].state = 'current'
      } else steps[i].state = 'upcoming'
    }

    if (st === 'rejected') {
      for (let i = 2; i < steps.length; i++) steps[i].state = 'upcoming'
    }
    if (st === 'draft') {
      for (let i = 1; i < steps.length; i++) steps[i].state = 'upcoming'
    }
    if (st === 'cancelled') {
      for (let i = 2; i < steps.length; i++) steps[i].state = 'upcoming'
    }

    return steps
  }

  const steps = [
    { key: 'created', label: t('request_detail.step_label_created'), sub: fmtStepDetail(r.created_at), state: 'upcoming' },
    {
      key: 'price_pending',
      label: t('request_detail.step_price_pending'),
      sub: '',
      state: 'upcoming',
    },
    {
      key: 'dept_pending',
      label: t('request_detail.step_dept_pending'),
      sub: '',
      state: 'upcoming',
    },
    { key: 'approved', label: t('request_detail.step_label_approved_short'), sub: '', state: 'upcoming' },
    { key: 'dispatch', label: t('request_detail.step_label_dispatch'), sub: '', state: 'upcoming' },
    { key: 'running', label: t('request_detail.step_label_running'), sub: '', state: 'upcoming' },
    { key: 'done', label: t('request_detail.step_label_done'), sub: '', state: 'upcoming' },
  ]

  steps[1].sub = st === 'pending' ? fmtStepDetail(r.created_at) : '—'

  steps[2].sub =
    r.price_filled_at && (st === 'price_filled' || st === 'approved' || st === 'rejected')
      ? fmtStepDetail(r.price_filled_at)
      : st === 'price_filled'
        ? fmtStepDetail(r.updated_at)
        : '—'

  steps[3].sub = approvedAt ? fmtStepDetail(approvedAt) : '—'

  steps[4].sub =
    tripSt === 'approved'
      ? '—'
      : dispatchDoneAt
        ? fmtStepDetail(dispatchDoneAt)
        : '—'

  steps[5].sub =
    tripSt === 'in_progress' || tripSt === 'completed'
      ? fmtStepDetail(runningAt)
      : ['assigned', 'driver_confirmed'].includes(tripSt)
        ? fmtStepDetail(trip.updated_at)
        : '—'

  steps[6].sub = tripSt === 'completed' && completedAt ? fmtStepDetail(completedAt) : '—'

  let active = 1
  if (st === 'draft') active = 0
  else if (st === 'pending') active = 1
  else if (st === 'price_filled') active = 2
  else if (st === 'rejected') active = 2
  else if (st === 'cancelled') active = 2
  else if (st === 'approved') {
    if (!trip) active = 3
    else if (tripSt === 'approved') active = 4
    else if (['assigned', 'driver_confirmed'].includes(tripSt)) active = 5
    else if (tripSt === 'in_progress') active = 5
    else if (tripSt === 'completed') active = 6
    else active = 3
  }

  steps[0].state = st === 'draft' ? 'current' : 'done'
  for (let i = 1; i < steps.length; i++) {
    if (i < active) steps[i].state = 'done'
    else if (i === active) {
      if (st === 'rejected' && i === 2) steps[i].state = 'rejected'
      else if (st === 'cancelled' && i === 2) steps[i].state = 'current'
      else steps[i].state = 'current'
    } else steps[i].state = 'upcoming'
  }

  if (st === 'rejected') {
    for (let i = 3; i < steps.length; i++) steps[i].state = 'upcoming'
  }
  if (st === 'draft') {
    for (let i = 1; i < steps.length; i++) steps[i].state = 'upcoming'
  }
  if (st === 'cancelled') {
    for (let i = 3; i < steps.length; i++) steps[i].state = 'upcoming'
  }

  return steps
})

function stepCircleClass(state) {
  if (state === 'done') return 'border-teal-500 bg-teal-500 text-white'
  if (state === 'current') return 'border-teal-500 bg-white text-teal-600'
  if (state === 'rejected') return 'border-rose-400 bg-white text-rose-500'
  return 'border-slate-200 bg-white text-slate-300'
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
  } finally {
    loading.value = false
  }
}

async function runOcr(attachmentId) {
  ocrErr.value = ''
  ocrBusy.value = attachmentId
  try {
    await runAttachmentOcr(attachmentId)
    await load()
  } catch (e) {
    ocrErr.value = e?.response?.data?.message ?? t('request_detail.ocr_failed_fallback')
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

function onDocUploaded() {
  attachErr.value = ''
  load()
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
