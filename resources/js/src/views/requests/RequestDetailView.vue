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
          class="w-full shrink-0 overflow-y-auto border-b border-slate-200/90 bg-gradient-to-b from-slate-50 to-slate-100/80 lg:sticky lg:top-0 lg:z-[1] lg:w-72 lg:max-h-[calc(100dvh-8rem)] lg:self-start lg:border-b-0 lg:border-r xl:w-80"
          :class="'max-h-[min(40vh,22rem)] lg:max-h-[calc(100dvh-8rem)]'"
        >
          <div class="space-y-3 p-3 sm:p-4">
            <section
              class="overflow-hidden rounded-lg border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.03]"
            >
              <header
                class="flex items-center gap-2 border-b border-slate-100 bg-gradient-to-r from-teal-50/90 via-white to-white px-3 py-2"
              >
                <UserCircleIcon class="h-4 w-4 shrink-0 text-teal-700" aria-hidden="true" />
                <h2 class="text-[10px] font-bold uppercase tracking-wide text-slate-600">
                  {{ t('request_detail.aside_requester') }}
                </h2>
              </header>
              <div class="p-3">
                <div class="flex items-center gap-3">
                  <img
                    v-if="req.requester?.avatar_url"
                    :src="req.requester.avatar_url"
                    alt=""
                    class="h-11 w-11 shrink-0 rounded-full object-cover ring-2 ring-white shadow-sm"
                  />
                  <div
                    v-else
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-teal-100 to-teal-50 text-xs font-bold text-teal-900 ring-2 ring-teal-100/80"
                  >
                    {{ requesterInitials }}
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold leading-snug text-slate-900">
                      {{ req.requester?.name ?? '—' }}
                    </p>
                    <p
                      v-if="requesterAsideSubtitle"
                      class="mt-0.5 truncate text-xs text-slate-600"
                    >
                      {{ requesterAsideSubtitle }}
                    </p>
                  </div>
                </div>
                <dl
                  v-if="requesterAsideFields.length"
                  class="mt-3 grid gap-2 border-t border-slate-100 pt-3"
                >
                  <div
                    v-for="row in requesterAsideFields"
                    :key="row.key"
                    class="grid grid-cols-[4.5rem_1fr] items-start gap-x-2 gap-y-0.5 text-xs sm:grid-cols-[5rem_1fr]"
                  >
                    <dt class="font-medium text-slate-500">{{ row.label }}</dt>
                    <dd class="min-w-0 break-words text-slate-800">{{ row.value }}</dd>
                  </div>
                </dl>
              </div>
            </section>

            <section
              class="overflow-hidden rounded-lg border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.03]"
            >
              <header
                class="flex items-center gap-2 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-3 py-2"
              >
                <TruckIcon class="h-4 w-4 shrink-0 text-teal-700" aria-hidden="true" />
                <h2 class="text-[10px] font-bold uppercase tracking-wide text-slate-600">
                  {{ t('request_detail.aside_vehicle_request') }}
                </h2>
              </header>
              <div class="space-y-3 p-3">
                <div
                  class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-teal-100 bg-teal-50/80 px-2.5 py-1 text-[11px] font-semibold text-teal-900"
                >
                  <TruckIcon class="h-3.5 w-3.5 shrink-0 text-teal-600" aria-hidden="true" />
                  <span class="truncate">{{ labelTripType(req.trip_type) }}</span>
                </div>
                <dl class="rounded-md border border-slate-100 bg-slate-50/60 px-2.5 py-2 text-xs">
                  <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                    {{ t('request_detail.lbl_passenger_load') }}
                  </dt>
                  <dd class="mt-1 flex items-center gap-1.5 font-medium text-slate-900">
                    <CubeIcon class="h-4 w-4 shrink-0 text-teal-600" aria-hidden="true" />
                    <span>{{ passengerOrCargoLine }}</span>
                  </dd>
                </dl>
              </div>
            </section>

            <section
              v-if="workflowTodoItems.length"
              class="overflow-hidden rounded-lg border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.03]"
            >
              <header class="border-b border-slate-100 bg-slate-50/90 px-3 py-2">
                <h2 class="text-[10px] font-bold uppercase tracking-wide text-slate-600">
                  {{ t('request_detail.aside_todos_heading') }}
                </h2>
              </header>
              <ul class="space-y-1 p-2">
                <li v-for="item in workflowTodoItems.slice(0, 4)" :key="item.key">
                  <button
                    type="button"
                    class="w-full rounded-md px-2 py-1.5 text-left text-xs font-semibold text-teal-900 hover:bg-teal-50"
                    @click="onWorkflowNavigate({ tab: item.tab, focus: item.focus })"
                  >
                    {{ item.label }}
                  </button>
                </li>
              </ul>
            </section>

            <section
              v-if="showResetCloneBtn"
              class="overflow-hidden rounded-lg border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/[0.03]"
            >
              <ResetCloneSection compact :busy="resetCloneBusy" @clone="onResetCloneRequest" />
            </section>
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
              @click="setActiveTab('route')"
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
              @click="setActiveTab('form')"
            >
              {{ t('request_detail.tab_form') }}
              <span
                v-if="formTabActionCount > 0"
                class="ml-1.5 inline-flex min-w-[1.125rem] items-center justify-center rounded-full bg-teal-600 px-1 py-px text-[10px] font-bold text-white"
              >
                {{ formTabActionCount }}
              </span>
              <span
                v-else-if="approvalTabNeedsFocus"
                class="ml-1.5 inline-flex h-2 w-2 shrink-0 rounded-full bg-teal-500"
                aria-hidden="true"
              />
            </button>
            <button
              v-if="showStudentCountTab"
              type="button"
              role="tab"
              :aria-selected="activeTab === 'students'"
              class="relative inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold transition sm:text-sm"
              :class="
                activeTab === 'students'
                  ? 'bg-teal-50 text-teal-900 ring-1 ring-teal-600/20'
                  : 'text-slate-600 hover:bg-slate-50'
              "
              @click="setActiveTab('students')"
            >
              {{ t('request_detail.tab_students') }}
              <span
                v-if="studentsTabActionCount > 0"
                class="ml-1.5 inline-flex min-w-[1.125rem] items-center justify-center rounded-full bg-teal-600 px-1 py-px text-[10px] font-bold text-white"
              >
                {{ studentsTabActionCount }}
              </span>
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
              @click="setActiveTab('docs')"
            >
              {{ t('request_detail.tab_docs') }}
              <span
                v-if="docsTabActionCount > 0"
                class="ml-1.5 inline-flex min-w-[1.125rem] items-center justify-center rounded-full bg-amber-500 px-1 py-px text-[10px] font-bold text-white"
              >
                {{ docsTabActionCount }}
              </span>
              <span
                v-else-if="docsTabNeedsFocus"
                class="ml-1.5 inline-flex h-2 w-2 shrink-0 rounded-full bg-amber-500"
                aria-hidden="true"
              />
            </button>
          </nav>

          <div class="min-h-0 flex-1 overflow-y-auto p-3 sm:p-4">
            <RequestWorkflowBar
              v-if="workflowTodoItems.length"
              class="mb-4"
              :todos="workflowTodoItems"
              @navigate="onWorkflowNavigate"
            />
            <RequestCloneLineageBanner
              v-if="req?.cloned_from_summary"
              :summary="req.cloned_from_summary"
              :context="isDeptRequestDetailRoute ? 'dept' : 'staff'"
            />

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
            </div>
            <div v-show="activeTab === 'students' && showStudentCountTab" class="space-y-5">
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
            </div>
            <div v-show="activeTab === 'docs'">
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
import { computed, defineAsyncComponent, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
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
  UserCircleIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import { CheckIcon } from '@heroicons/vue/24/solid'
import Button from '../../components/ui/Button.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
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
import AttachmentPreviewModal from '../../components/requests/AttachmentPreviewModal.vue'
import DeptApprovalSection from '../../components/requests/DeptApprovalSection.vue'
import RejectReasonModal from '../../components/requests/RejectReasonModal.vue'
import { useDispatchRequestDocs } from '../../composables/useDispatchRequestDocs'
import { useRequestCostEstimate } from '../../composables/useRequestCostEstimate'
import { useRequestWorkflowSteps } from '../../composables/useRequestWorkflowSteps'
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

/** Chốt/cập nhật số HS định kỳ: người đề nghị trên portal; điều vận chỉnh trên tab Học sinh chuyến. */
const showStudentCountTab = computed(() => req.value?.dispatch_request_template_id != null)

const showPassengerAdjustSection = computed(
  () => showStudentCountTab.value && auth.hasPermission('trip.view_all'),
)

const showResetCloneBtn = computed(() => {
  if (!isCurrentUserRequester.value || !auth.hasPermission('request.create')) return false
  return ['approved', 'rejected'].includes(String(req.value?.status || ''))
})

const DETAIL_TABS = ['route', 'form', 'students', 'docs']

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

function setActiveTab(tab) {
  if (tab === 'students' && !showStudentCountTab.value) return
  activeTab.value = tab
  const nextQuery = { ...route.query, tab }
  if (route.query.tab !== tab) {
    router.replace({ query: nextQuery })
  }
}

watch(
  () => route.query.tab,
  (tab) => {
    if (typeof tab === 'string' && DETAIL_TABS.includes(tab) && activeTab.value !== tab) {
      if (tab === 'students' && !showStudentCountTab.value) return
      activeTab.value = tab
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
    if (need && was !== true && req.value) activeTab.value = 'form'
  },
  { immediate: true },
)

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
    const tabQ = tabFromRouteQuery()
    if (tabQ) activeTab.value = tabQ
  } finally {
    loading.value = false
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
