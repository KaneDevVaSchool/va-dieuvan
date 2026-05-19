<template>
  <div class="min-h-screen bg-slate-50">
    <div v-if="loading" class="px-4 py-12 text-center text-sm text-slate-500">Đang tải…</div>

    <template v-else-if="req">
      <header
        class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur-md supports-[backdrop-filter]:bg-white/85"
      >
        <div class="mx-auto max-w-[1600px] px-4 py-3">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between lg:gap-6">
            <div class="flex min-w-0 flex-1 items-start gap-3">
              <RouterLink
                to="/requests"
                class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                aria-label="Quay lại danh sách"
              >
                <ArrowLeftIcon class="h-5 w-5" />
              </RouterLink>
              <div class="flex min-w-0 flex-1 items-start gap-3">
                <div
                  class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 ring-1 ring-teal-600/15"
                >
                  <TruckIcon class="h-6 w-6" aria-hidden="true" />
                </div>
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-base font-bold tracking-tight text-slate-900 sm:text-lg">
                      {{ requestRefCode }}
                    </h1>
                    <StatusBadge :status="req.status" />
                    <span
                      v-if="showRecurringBadge"
                      class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-900 ring-1 ring-indigo-600/15"
                    >
                      <ArrowPathIcon class="h-3.5 w-3.5 shrink-0 text-indigo-700" aria-hidden="true" />
                      {{ t('request_detail.badge_recurring') }}
                    </span>
                  </div>
                  <p class="mt-0.5 text-xs text-slate-600 sm:text-sm">
                    Tạo lúc: {{ fmt(req.created_at) }}
                  </p>
                  <p v-if="req.trip" class="mt-1 text-sm font-medium text-teal-700">
                    <RouterLink
                      :to="`/trips/${req.trip.id}`"
                      class="underline decoration-teal-600/30 underline-offset-2 hover:decoration-teal-800"
                    >
                      Mở chuyến #{{ req.trip.id }}
                    </RouterLink>
                  </p>
                </div>
              </div>
            </div>

            <div class="min-w-0 w-full lg:max-w-2xl lg:flex-1 xl:max-w-3xl">
              <div class="overflow-x-auto pb-1 [-webkit-overflow-scrolling:touch]">
                <div class="flex items-start" :class="stepperTrackMinClass">
                  <template v-for="(step, idx) in stepperSteps" :key="step.key">
                    <div class="flex min-w-0 flex-1 flex-col items-center text-center">
                      <div
                        class="flex h-9 w-9 items-center justify-center rounded-full border-2 text-sm font-semibold transition-colors sm:h-10 sm:w-10"
                        :class="stepCircleClass(step.state)"
                      >
                        <CheckIcon v-if="step.state === 'done'" class="h-4 w-4 sm:h-5 sm:w-5" />
                        <HandThumbUpIcon
                          v-else-if="step.key === 'approved' && (step.state === 'upcoming' || step.state === 'current')"
                          class="h-4 w-4 sm:h-5 sm:w-5"
                          :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                        />
                        <CurrencyDollarIcon
                          v-else-if="step.key === 'price_pending' && step.state !== 'done'"
                          class="h-4 w-4 sm:h-5 sm:w-5"
                          :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                        />
                        <BuildingOffice2Icon
                          v-else-if="step.key === 'dept_pending' && step.state !== 'done'"
                          class="h-4 w-4 sm:h-5 sm:w-5"
                          :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                        />
                        <Cog6ToothIcon
                          v-else-if="step.key === 'dispatch' && step.state !== 'done'"
                          class="h-4 w-4 sm:h-5 sm:w-5"
                          :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                        />
                        <TruckIcon
                          v-else-if="step.key === 'running' && step.state !== 'done'"
                          class="h-4 w-4 sm:h-5 sm:w-5"
                          :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                        />
                        <FlagIcon
                          v-else-if="step.key === 'done' && step.state !== 'done'"
                          class="h-4 w-4 sm:h-5 sm:w-5"
                          :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                        />
                        <span v-else-if="step.state === 'current'" class="h-2 w-2 rounded-full bg-teal-600 sm:h-2.5 sm:w-2.5" />
                        <span v-else-if="step.state === 'rejected'" class="text-xs font-bold">!</span>
                        <span v-else class="text-slate-300">·</span>
                      </div>
                      <p class="mt-1.5 text-[10px] font-semibold leading-tight text-slate-800 sm:mt-2 sm:text-xs">
                        {{ step.label }}
                      </p>
                      <p v-if="step.sub" class="mt-0.5 hidden max-w-[6.5rem] truncate text-[10px] text-slate-500 sm:block sm:max-w-none">
                        {{ step.sub }}
                      </p>
                    </div>
                    <div
                      v-if="idx < stepperSteps.length - 1"
                      class="mx-0.5 mt-4 h-0.5 w-4 shrink-0 sm:mx-1 sm:mt-5 sm:w-6 md:w-8"
                      :class="step.state === 'done' ? 'bg-teal-500' : 'bg-slate-200'"
                      aria-hidden="true"
                    />
                  </template>
                </div>
              </div>
            </div>

            <div class="flex w-full shrink-0 flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center lg:w-auto lg:flex-col lg:items-stretch">
              <div class="flex w-full flex-col gap-1 sm:w-auto sm:min-w-[11rem]">
                <button
                  type="button"
                  class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border px-3 text-sm font-medium shadow-sm transition"
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
                    class="h-4 w-4 animate-spin rounded-full border-2 border-teal-500/30 border-t-teal-600"
                  />
                  {{ pdfBusy ? t('request_detail.pdf_export_loading') : t('request_detail.export_pdf') }}
                </button>
              </div>

              <div
                v-if="req.status === 'pending' && canApprove && req.trip_type === 'door_to_door'"
                class="flex w-full gap-2 sm:w-auto"
              >
                <Button
                  variant="danger"
                  :loading="acting"
                  class="min-h-[2.75rem] flex-1 justify-center !border-rose-200 !bg-white !py-2.5 text-sm font-semibold !text-rose-700 shadow-sm hover:!bg-rose-50 sm:flex-initial sm:px-4"
                  @click="onDecideClick('reject')"
                >
                  Từ chối
                </Button>
                <Button
                  :loading="acting"
                  class="min-h-[2.75rem] flex-1 justify-center !bg-teal-600 !py-2.5 text-sm font-semibold text-white shadow-sm hover:!bg-teal-700 sm:flex-initial sm:px-5"
                  @click="onDecideClick('approve')"
                >
                  Phê duyệt
                </Button>
              </div>
            </div>
          </div>

          <p
            v-if="msg && req.status === 'pending' && canApprove && req.trip_type === 'door_to_door'"
            class="mt-4 rounded-lg border border-amber-200/90 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-950"
          >
            {{ msg }}
          </p>
        </div>
      </header>

      <div class="mx-auto max-w-[1600px] space-y-6 px-4 pb-12 pt-6">
        <section
          v-if="showResetCloneBtn || showPassengerAdjustSection"
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
        >
          <ResetCloneSection v-if="showResetCloneBtn" :busy="resetCloneBusy" @clone="onResetCloneRequest" />
          <StudentCountField
            v-if="showPassengerAdjustSection"
            v-model:passenger-count="passengerDraft"
            :locked="passengerDepartLocked"
            :depart-at-formatted="req.depart_at ? fmtStepDetail(req.depart_at) : ''"
            :saving="passengerSaving"
            :error="passengerPatchErr"
            :class="showResetCloneBtn ? 'mt-5 border-t border-slate-100 pt-5' : ''"
            @save="savePassengerDraft"
          />
        </section>

        <CostLimitAlert v-if="req.dispatch_package_cost_alert" :alert="req.dispatch_package_cost_alert" />

        <section v-if="showApprovalDecisionPanel" class="rounded-2xl border-2 border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="border-b border-slate-200 pb-3 text-base font-bold text-slate-900">Cơ sở phê duyệt</h2>
          <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Trạng thái</dt>
              <dd class="mt-1">
                <StatusBadge :status="req.status" />
              </dd>
            </div>
            <div>
              <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Loại chuyến</dt>
              <dd class="mt-1 font-semibold text-slate-900">
                {{ labelTripType(req.trip_type) }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Người yêu cầu</dt>
              <dd class="mt-1 font-semibold text-slate-900">
                {{ req.requester?.name ?? '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Đơn vị</dt>
              <dd class="mt-1 text-slate-900">
                {{ req.wizard_snapshot?.form?.requester_unit || requesterSubtitle || '—' }}
              </dd>
            </div>
            <div class="sm:col-span-2">
              <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Thời gian sử dụng</dt>
              <dd class="mt-1 font-medium text-slate-900">
                {{ fmtDateVi(req.depart_at) }} · {{ fmtTimeWindow(req.depart_at, req.arrive_by) }}
              </dd>
            </div>
            <div class="lg:col-span-3">
              <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lộ trình</dt>
              <dd class="mt-1 font-medium text-slate-900">
                <span>{{ req.origin || '—' }}</span>
                <span class="mx-2 text-slate-400">→</span>
                <span>{{ req.destination || '—' }}</span>
              </dd>
            </div>
            <div>
              <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Số người / tải</dt>
              <dd class="mt-1 font-medium text-slate-900">
                {{ passengerOrCargoLine }}
              </dd>
            </div>
            <div v-if="wizardPurpose" class="sm:col-span-2">
              <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mục đích</dt>
              <dd class="mt-1 text-slate-900">
                {{ wizardPurpose }}
              </dd>
            </div>
            <div v-if="costEstimate">
              <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tổng chi phí (khai báo)</dt>
              <dd class="mt-1 text-lg font-bold tabular-nums text-teal-700">
                {{ formatVndCurrency(costEstimate.total) }}
              </dd>
            </div>
            <div v-if="showDeptDecisionSection && req.service_price != null">
              <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Đơn giá (Điều vận)</dt>
              <dd class="mt-1 text-lg font-bold tabular-nums text-violet-800">
                {{ formatVndCurrency(req.service_price) }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Số tệp đính kèm</dt>
              <dd class="mt-1 font-semibold text-slate-900">
                {{ generalAttachments.length }}
              </dd>
            </div>
          </dl>
        </section>

        <PriceFillSection
          v-if="showFillPriceSection"
          :reference-pricing-url="referencePricingUrl"
          :service-price="fillPriceForm.service_price"
          :submitting="fillPriceActing"
          :message="fillPriceMsg"
          @update:service-price="onFillPriceServicePriceInput"
          @submit="submitFillPrice"
        />

        <SignedPaperUpload
          v-if="showSignedPaperSection"
          :attachments="signedPaperAttachments"
          :upload-component-key="`signed-${route.params.id}-${signedPaperAttachments.length}`"
          :upload-fn="uploadSignedPaper"
          :error="signedUploadErr"
          @download="downloadFile"
          @uploaded="onSignedUploaded"
        />

        <div class="flex flex-col gap-6 lg:flex-row lg:items-start">
          <aside class="w-full shrink-0 space-y-6 lg:w-80 xl:w-72">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Người yêu cầu</p>
              <div class="mt-4 flex items-center gap-3">
                <img
                  v-if="req.requester?.avatar_url"
                  :src="req.requester.avatar_url"
                  alt=""
                  class="h-12 w-12 rounded-full object-cover ring-2 ring-slate-100"
                />
                <div
                  v-else
                  class="flex h-12 w-12 items-center justify-center rounded-full bg-teal-100 text-sm font-bold text-teal-800 ring-2 ring-teal-50"
                >
                  {{ requesterInitials }}
                </div>
                <div class="min-w-0">
                  <p class="font-semibold text-slate-900">{{ req.requester?.name ?? '—' }}</p>
                  <p class="text-sm text-slate-600">{{ requesterSubtitle }}</p>
                </div>
              </div>
              <ul class="mt-4 space-y-2.5 border-t border-slate-100 pt-4 text-sm text-slate-700">
                <li v-if="req.requester?.email" class="flex gap-2">
                  <span class="shrink-0 font-medium text-slate-500">Email</span>
                  <span class="min-w-0 break-all text-slate-800">{{ req.requester.email }}</span>
                </li>
                <li v-if="req.requester?.phone" class="flex gap-2">
                  <span class="shrink-0 font-medium text-slate-500">Điện thoại</span>
                  <span class="text-slate-800">{{ req.requester.phone }}</span>
                </li>
                <li v-if="req.requester?.employee_code" class="flex gap-2">
                  <span class="shrink-0 font-medium text-slate-500">Mã NV</span>
                  <span class="text-slate-800">{{ req.requester.employee_code }}</span>
                </li>
                <li v-if="req.wizard_snapshot?.form?.requester_unit" class="flex gap-2">
                  <span class="shrink-0 font-medium text-slate-500">Đơn vị</span>
                  <span class="text-slate-800">{{ req.wizard_snapshot.form.requester_unit }}</span>
                </li>
              </ul>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Yêu cầu phương tiện</p>
              <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                <TruckIcon class="h-4 w-4 text-teal-600" aria-hidden="true" />
                {{ labelTripType(req.trip_type) }}
              </div>
              <dl class="mt-4 space-y-3 border-t border-slate-100 pt-4 text-sm">
                <div>
                  <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Số người / Khối lượng</dt>
                  <dd class="mt-1 flex items-center gap-2 text-slate-900">
                    <CubeIcon class="h-5 w-5 shrink-0 text-teal-600" />
                    <span>{{ passengerOrCargoLine }}</span>
                  </dd>
                </div>
                <div v-if="hasRequestNotesBlock">
                  <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ requestNotesLabel }}</dt>
                  <dd
                    class="mt-1 max-h-[min(36rem,70vh)] overflow-y-auto whitespace-pre-wrap break-words rounded-lg bg-slate-50 px-3 py-2.5 text-sm leading-relaxed text-slate-800 [overflow-wrap:anywhere]"
                  >
                    {{ requestNotesCombined }}
                  </dd>
                </div>
              </dl>
            </div>
          </aside>

          <div class="min-w-0 flex-1 space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2 text-teal-600">
                  <InformationCircleIcon class="h-6 w-6 shrink-0" aria-hidden="true" />
                  <h2 class="text-base font-semibold text-slate-900">Lộ trình di chuyển</h2>
                </div>
                <span
                  class="inline-flex items-center rounded-full bg-teal-50 px-3 py-1 text-xs font-bold tabular-nums text-teal-800 ring-1 ring-teal-600/20"
                >
                  {{ costEstimate?.distanceLabel != null ? `~ ${costEstimate.distanceLabel}` : 'Khoảng cách: —' }}
                </span>
              </div>
              <div class="mt-5 flex flex-wrap items-center gap-3 text-sm text-slate-700">
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-2.5 py-1 font-medium ring-1 ring-slate-200/80">
                  <CalendarDaysIcon class="h-4 w-4 text-slate-400" />
                  {{ fmtDateVi(req.depart_at) }}
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-2.5 py-1 font-medium ring-1 ring-slate-200/80">
                  <ClockIcon class="h-4 w-4 text-slate-400" />
                  {{ fmtTimeWindow(req.depart_at, req.arrive_by) }}
                </span>
              </div>
              <div class="mt-6 flex gap-4">
                <div class="flex flex-col items-center pt-1">
                  <span class="h-3 w-3 rounded-full border-2 border-teal-500 bg-white shadow-sm" />
                  <span class="mt-1 min-h-[3rem] w-px flex-1 bg-gradient-to-b from-teal-400 to-teal-200" />
                  <span class="h-3 w-3 rounded-full border-2 border-teal-500 bg-white shadow-sm" />
                </div>
                <div class="min-w-0 flex-1 space-y-6">
                  <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-teal-700/90">Điểm đi</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ req.origin || '—' }}</p>
                    <p v-if="routeSubFrom" class="mt-0.5 text-sm text-slate-500">{{ routeSubFrom }}</p>
                  </div>
                  <div>
                    <p class="text-[11px] font-bold uppercase tracking-wide text-teal-700/90">Điểm đến</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ req.destination || '—' }}</p>
                    <p v-if="routeSubTo" class="mt-0.5 text-sm text-slate-500">{{ routeSubTo }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2 text-teal-600">
                  <CalculatorIcon class="h-6 w-6 shrink-0" />
                  <h2 class="text-base font-semibold text-slate-900">Dự toán chi phí</h2>
                </div>
              </div>
              <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-3 border-b border-slate-50 pb-3">
                  <dt class="text-slate-500">Quãng đường ước tính</dt>
                  <dd class="text-right font-medium text-slate-900">{{ costEstimate?.distanceLabel ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-slate-50 pb-3">
                  <dt class="text-slate-500">Loại xe đề xuất</dt>
                  <dd class="text-right font-medium text-slate-900">{{ costEstimate?.vehicleHint ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-slate-50 pb-3">
                  <dt class="text-slate-500">Đơn giá tham chiếu</dt>
                  <dd class="text-right font-medium text-slate-900">{{ costEstimate?.refUnitLabel ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-slate-50 pb-3">
                  <dt class="text-slate-500">Phí cầu đường (dự kiến)</dt>
                  <dd class="text-right font-medium text-slate-900">{{ costEstimate?.tollLabel ?? '—' }}</dd>
                </div>
              </dl>
              <div class="mt-5 rounded-xl bg-teal-50/90 px-4 py-4 ring-1 ring-teal-600/10">
                <p class="text-xs font-medium text-teal-900/90">Tổng (theo khai báo)</p>
                <p class="mt-1 text-xl font-bold tabular-nums text-teal-600 sm:text-2xl">
                  {{ costEstimate ? formatVndCurrency(costEstimate.total) : '—' }}
                </p>
              </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <div class="flex items-start gap-3 border-b border-slate-100 pb-4">
                <div
                  class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 ring-1 ring-teal-600/10"
                >
                  <PaperClipIcon class="h-5 w-5" aria-hidden="true" />
                </div>
                <div class="min-w-0 flex-1">
                  <h2 class="text-base font-semibold text-slate-900">Tài liệu đính kèm</h2>
                </div>
              </div>

              <ul v-if="generalAttachments.length" class="mt-4 space-y-1.5">
                <li
                  v-for="a in generalAttachments"
                  :key="a.id"
                  class="flex items-center justify-between gap-2 rounded-xl border border-slate-100 bg-slate-50/40 px-3 py-2.5 text-sm shadow-sm transition hover:border-teal-200/70 hover:bg-white"
                >
                  <span class="flex min-w-0 flex-1 items-center gap-2">
                    <DocumentIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
                    <span class="truncate font-medium text-slate-800" :title="a.original_name || `File #${a.id}`">
                      {{ a.original_name || `File #${a.id}` }}
                    </span>
                  </span>
                  <div class="flex shrink-0 items-center gap-0.5">
                    <button
                      type="button"
                      class="rounded-md px-2 py-1 text-[11px] font-semibold text-teal-700 transition hover:bg-teal-100/80 hover:text-teal-900"
                      @click="downloadFile(a)"
                    >
                      Tải
                    </button>
                    <button
                      v-if="canDeleteAttachment"
                      type="button"
                      class="rounded-md px-2 py-1 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-50 disabled:opacity-50"
                      :disabled="deletingId === a.id"
                      @click="removeAttachment(a)"
                    >
                      {{ deletingId === a.id ? '…' : 'Xóa' }}
                    </button>
                  </div>
                </li>
              </ul>

              <div v-if="attachErr" class="mt-3 rounded-md bg-rose-50 px-2 py-1.5 text-xs text-rose-700">
                {{ attachErr }}
              </div>

              <div class="mt-4">
                  <FileUpload
                    v-if="canUploadAttachment"
                    :key="`doc-${route.params.id}-${generalAttachments.length}`"
                    label="Thêm tài liệu"
                    drag-drop
                    compact
                    :upload-fn="uploadRequestDocument"
                    @uploaded="onDocUploaded"
                  />
                <p
                  v-else
                  class="rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-3 py-3 text-center text-xs text-slate-500"
                >
                  Bạn không có quyền tải file đính kèm.
                </p>
              </div>
            </div>

            <DeptApprovalSection
              v-if="showDeptDecisionSection"
              :service-price-display="req.service_price != null ? formatVndCurrency(req.service_price) : null"
              :acting="deptActing"
              :inline-message="deptMsg"
              :reject-modal-open="deptRejectOpen"
              @approve="onDeptApproveClick"
              @reject="openDeptReject"
            />
          </div>

          <aside class="w-full shrink-0 space-y-6 lg:w-96 xl:w-80">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
              <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Tổng hợp chi phí</p>
              <dl class="mt-4 space-y-2.5 text-sm">
                <div class="flex justify-between gap-2 border-b border-slate-50 pb-2.5">
                  <dt class="text-slate-500">Đơn giá tham chiếu</dt>
                  <dd class="text-right font-medium text-slate-900">{{ costEstimate?.refUnitLabel ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2 border-b border-slate-50 pb-2.5">
                  <dt class="text-slate-500">Phí cầu đường</dt>
                  <dd class="text-right font-medium text-slate-900">{{ costEstimate?.tollLabel ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-2 border-b border-slate-50 pb-2.5">
                  <dt class="text-slate-500">Loại xe đề xuất</dt>
                  <dd class="text-right font-medium text-slate-900">{{ costEstimate?.vehicleHint ?? '—' }}</dd>
                </div>
              </dl>
              <div class="mt-5 rounded-xl bg-teal-50 px-4 py-4 ring-1 ring-teal-600/15">
                <p class="text-xs font-semibold text-teal-900/80">Tổng chi phí ước tính</p>
                <p class="mt-1 text-3xl font-bold tabular-nums tracking-tight text-teal-600">
                  {{ costEstimate ? formatVndCurrency(costEstimate.total) : '—' }}
                </p>
              </div>
            </div>

            <div
              v-if="paperScans.length || canUploadAttachment || req.paper_status === 'pending' || req.paper_status === 'received'"
              class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
            >
              <div class="flex flex-wrap items-start justify-between gap-2 border-b border-slate-100 pb-3">
                <div class="flex items-start gap-3">
                  <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 ring-1 ring-slate-200"
                  >
                    <DocumentTextIcon class="h-5 w-5" aria-hidden="true" />
                  </div>
                  <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-slate-900">Phiếu giấy &amp; OCR</h2>
                  </div>
                </div>
                <span
                  v-if="req.paper_status === 'received'"
                  class="shrink-0 rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-teal-800 ring-1 ring-teal-600/20"
                >
                  Đã nhận phiếu
                </span>
                <span
                  v-else-if="req.paper_status === 'pending'"
                  class="shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-900 ring-1 ring-amber-600/20"
                >
                  Chưa nhận phiếu
                </span>
              </div>

              <ul v-if="paperScans.length" class="mt-4 space-y-2 text-sm">
                <li
                  v-for="a in paperScans"
                  :key="a.id"
                  class="rounded-xl border border-slate-100 bg-slate-50/50 p-3 shadow-sm"
                >
                  <div class="flex flex-wrap items-center gap-2">
                    <button
                      type="button"
                      class="max-w-full truncate text-left text-xs font-semibold text-teal-700 hover:underline"
                      :title="a.original_name || 'Tải file'"
                      @click="downloadFile(a)"
                    >
                      {{ a.original_name || 'Tải file' }}
                    </button>
                    <span v-if="a.mime_type" class="rounded bg-white px-1.5 py-0.5 text-[10px] text-slate-500 ring-1 ring-slate-200">
                      {{ a.mime_type }}
                    </span>
                    <Button
                      variant="secondary"
                      type="button"
                      class="!border-slate-200 !px-2 !py-1 !text-[11px] !font-semibold"
                      :loading="ocrBusy === a.id"
                      @click="runOcr(a.id)"
                    >
                      {{ a.ocr_processed_at ? 'OCR lại' : 'Chạy OCR' }}
                    </Button>
                    <button
                      v-if="canDeleteAttachment"
                      type="button"
                      class="ml-auto text-[11px] font-semibold text-rose-600 hover:text-rose-700 disabled:opacity-50"
                      :disabled="deletingId === a.id"
                      @click="removeAttachment(a)"
                    >
                      {{ deletingId === a.id ? '…' : 'Xóa scan' }}
                    </button>
                  </div>
                  <p v-if="a.ocr_processed_at" class="mt-1.5 text-[11px] text-slate-500">
                    OCR: {{ fmt(a.ocr_processed_at) }}
                  </p>
                  <pre
                    v-if="a.ocr_text"
                    class="mt-2 max-h-36 overflow-auto whitespace-pre-wrap rounded-lg border border-slate-100 bg-white p-2 text-[11px] leading-relaxed text-slate-800"
                  >{{ a.ocr_text }}</pre>
                </li>
              </ul>

              <div v-if="ocrErr" class="mt-3 rounded-md bg-rose-50 px-2 py-1.5 text-xs text-rose-700">{{ ocrErr }}</div>

              <div v-if="canUploadAttachment" class="mt-4">
                <FileUpload
                  :key="`paper-${route.params.id}-${paperScans.length}`"
                  label="Đính kèm phiếu / scan"
                  drag-drop
                  compact
                  :upload-fn="uploadPaperScan"
                  @uploaded="load"
                />
              </div>

              <div
                v-if="req.paper_status === 'received' && !canManagePaper"
                class="mt-4 border-t border-slate-100 pt-3 text-[11px] text-slate-600"
              >
                <p v-if="req.paper_reference">
                  <span class="font-medium text-slate-500">Số phiếu / tham chiếu:</span>
                  {{ req.paper_reference }}
                </p>
                <p v-if="req.paper_received_at" class="mt-1">
                  <span class="font-medium text-slate-500">Thời điểm nhận:</span>
                  {{ fmt(req.paper_received_at) }}
                </p>
              </div>

              <div v-if="req.paper_status === 'pending' && canManagePaper" class="mt-4 border-t border-slate-100 pt-3">
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Xác nhận đã nhận phiếu giấy</h3>
                <form class="mt-3 grid gap-2.5" @submit.prevent="doMarkPaper">
                  <Input
                    v-model="paperForm.paper_reference"
                    label="Số phiếu / mã tham chiếu"
                    placeholder="Ví dụ: PG-2026-00123"
                  />
                  <Input
                    v-model="paperForm.paper_received_at"
                    label="Thời điểm nhận phiếu"
                    type="datetime-local"
                  />
                  <div class="flex flex-wrap items-center gap-2 pt-0.5">
                    <Button :loading="paperActing" type="submit" class="!bg-teal-600 hover:!bg-teal-700">Đánh dấu đã nhận</Button>
                    <span v-if="paperMsg" class="text-xs text-slate-600">{{ paperMsg }}</span>
                  </div>
                </form>
              </div>

              <div v-else-if="req.paper_status === 'received' && canManagePaper" class="mt-4 border-t border-slate-100 pt-3">
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Cập nhật / hoàn tác phiếu giấy</h3>
                <form class="mt-3 grid gap-2.5" @submit.prevent="doMarkPaper">
                  <Input
                    v-model="paperForm.paper_reference"
                    label="Số phiếu / mã tham chiếu"
                    placeholder="Ví dụ: PG-2026-00123"
                  />
                  <Input
                    v-model="paperForm.paper_received_at"
                    label="Thời điểm nhận phiếu"
                    type="datetime-local"
                  />
                  <div class="flex flex-wrap items-center gap-2 pt-0.5">
                    <Button :loading="paperActing" type="submit" class="!bg-teal-600 hover:!bg-teal-700">Lưu thay đổi</Button>
                    <Button
                      variant="secondary"
                      type="button"
                      class="!border-amber-200 !text-amber-900 hover:!bg-amber-50"
                      :disabled="paperActing || paperRevertActing"
                      @click="doRevertPaper"
                    >
                      Hoàn tác (chưa nhận phiếu)
                    </Button>
                    <span v-if="paperMsg" class="text-xs text-slate-600">{{ paperMsg }}</span>
                  </div>
                </form>
              </div>
            </div>
          </aside>
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
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowLeftIcon,
  ArrowPathIcon,
  BuildingOffice2Icon,
  CalculatorIcon,
  CalendarDaysIcon,
  ClockIcon,
  Cog6ToothIcon,
  CubeIcon,
  CurrencyDollarIcon,
  DocumentTextIcon,
  DocumentIcon,
  FlagIcon,
  HandThumbUpIcon,
  InformationCircleIcon,
  PaperClipIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'
import { CheckIcon } from '@heroicons/vue/24/solid'
import Button from '../../components/ui/Button.vue'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import Input from '../../components/ui/Input.vue'
import FileUpload from '../../components/ui/FileUpload.vue'
import PriceFillSection from '../../components/requests/PriceFillSection.vue'
import DeptApprovalSection from '../../components/requests/DeptApprovalSection.vue'
import RejectReasonModal from '../../components/requests/RejectReasonModal.vue'
import SignedPaperUpload from '../../components/requests/SignedPaperUpload.vue'
import CostLimitAlert from '../../components/requests/CostLimitAlert.vue'
import ResetCloneSection from '../../components/requests/ResetCloneSection.vue'
import StudentCountField from '../../components/recurring/StudentCountField.vue'
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
import { formatApiError } from '../../api/http'
import { saveAs } from 'file-saver'
import { newIdempotencyKey } from '../../util/idempotency'
import { labelTripType } from '../../util/labels'
import { formatDispatchRequestNotesForDisplay, isLegacyBm03NotesBlock } from '../../util/formatDispatchNotes'
import { buildBm03BodyFromWizardSnapshot } from '../../util/buildBm03BodyFromSnapshot'
import { parseMoneyVnd, formatVndWhileTyping } from '../../util/money'
import { downloadBinaryAttachmentFromApi } from '../../util/downloadPdfAttachment'
import { toDatetimeLocalValue } from '../../util/datetime'
import { useAuthStore } from '../../store'
import { confirmAction } from '../../composables/useConfirm'
import { showAppSuccess, showAppError } from '../../composables/appMessage'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { t } = useI18n()

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

const fillPriceForm = ref({ service_price: '' })
const fillPriceActing = ref(false)
const fillPriceMsg = ref('')

const deptActing = ref(false)
const deptMsg = ref('')
const deptRejectOpen = ref(false)
const deptRejectReason = ref('')

const signedUploadErr = ref('')

const passengerDraft = ref(1)
const passengerSaving = ref(false)
const passengerPatchErr = ref('')
const resetCloneBusy = ref(false)

const bm03FromSnapshotRaw = computed(() => {
  const s = buildBm03BodyFromWizardSnapshot(req.value?.wizard_snapshot)
  return s && String(s).trim() ? String(s).trim() : ''
})

/** BM.03 trong notes (bản cũ) + nội dung tái tạo từ wizard_snapshot + ghi chú tự do. */
const requestNotesCombined = computed(() => {
  const n = req.value?.notes?.trim()
  const snap = bm03FromSnapshotRaw.value

  if (n && isLegacyBm03NotesBlock(n)) {
    return formatDispatchRequestNotesForDisplay(n)
  }

  const userPart = n && !isLegacyBm03NotesBlock(n) ? formatDispatchRequestNotesForDisplay(n) : ''
  const snapPart = snap ? formatDispatchRequestNotesForDisplay(snap) : ''

  if (snapPart && userPart) {
    return `${snapPart}\n\n———\n\nGhi chú thêm:\n\n${userPart}`
  }
  if (snapPart) return snapPart
  if (userPart) return userPart
  return ''
})

const hasRequestNotesBlock = computed(() => !!requestNotesCombined.value.trim())

/** Một nhãn cố định — tránh nhiều dòng chú thích. */
const requestNotesLabel = computed(() => 'Chi tiết đề nghị')

const requestRefCode = computed(() => {
  const r = req.value
  if (!r?.id) return ''
  const d = r.created_at ? new Date(r.created_at) : new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  return `REQ-${y}${m}-${String(r.id).padStart(3, '0')}`
})

const canApprove = computed(
  () => auth.hasPermission('request.approve') || auth.hasPermission('trip.view_all'),
)

const canUploadAttachment = computed(() => auth.hasPermission('attachment.upload'))
const canDeleteAttachment = computed(() => auth.hasPermission('attachment.upload'))
const canManagePaper = computed(() => auth.hasPermission('request.paper.manage'))

const pdfExportDisabled = computed(() => req.value?.status !== 'approved')

const stepperTrackMinClass = computed(() => {
  if (!req.value) return 'min-w-[700px]'
  return req.value.trip_type === 'door_to_door' ? 'min-w-[700px]' : 'min-w-[860px]'
})

const referencePricingUrl = computed(() => {
  const u = formSettings.value?.reference_pricing_url
  return u && String(u).trim() !== '' ? String(u).trim() : ''
})

const showFillPriceSection = computed(
  () =>
    req.value?.status === 'pending' &&
    req.value?.trip_type !== 'door_to_door' &&
    auth.hasPermission('request.fill_price'),
)

const showDeptDecisionSection = computed(
  () => req.value?.status === 'price_filled' && auth.hasPermission('request.approve_dept'),
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

const passengerDepartLocked = computed(() => {
  const st = req.value?.status
  if (!isCurrentUserRequester.value || !req.value?.dispatch_request_template_id) return true
  if (st !== 'pending' && st !== 'price_filled') return true
  const h = hoursUntilDepartIso(req.value?.depart_at)
  return h == null || h < 24
})

const showPassengerAdjustSection = computed(
  () =>
    isCurrentUserRequester.value &&
    !!req.value?.dispatch_request_template_id &&
    ['pending', 'price_filled'].includes(String(req.value?.status || '')),
)

const showResetCloneBtn = computed(
  () =>
    isCurrentUserRequester.value &&
    auth.hasPermission('request.create') &&
    ['approved', 'rejected'].includes(String(req.value?.status || '')),
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
  return list.filter((a) => a.kind !== 'paper_scan')
})

const requesterSubtitle = computed(() => {
  const u = req.value?.wizard_snapshot?.form?.requester_unit
  if (u?.trim()) return u.trim()
  const code = req.value?.requester?.employee_code
  if (code) return `Mã NV: ${code}`
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
      return `${joined}${/tấn|kg|ton/i.test(joined) ? '' : ' (khối lượng theo khai báo)'}`
    }
  }
  if (r.passenger_count != null && r.passenger_count > 0) {
    return `${r.passenger_count} hành khách`
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
    refRaw != null && String(refRaw).trim() !== ''
      ? `${new Intl.NumberFormat('vi-VN').format(cargoExtra(refRaw))} VNĐ`
      : null

  const toll = f.interprovincial ? cargoExtra(f.interprovincial_cost) : 0
  const tollLabel = f.interprovincial && toll > 0 ? `${new Intl.NumberFormat('vi-VN').format(toll)} VNĐ` : null

  const wRaw = (snap.cargoRows ?? []).map((c) => c.weight).find((x) => String(x ?? '').trim())
  let vehicleHint = null
  if (wRaw) {
    const n = parseFloat(String(wRaw).replace(',', '.'))
    if (Number.isFinite(n)) vehicleHint = `Tải ~${n} tấn (tham khảo)`
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

function fmtStepDetail(v) {
  if (!v) return '—'
  try {
    return new Date(v).toLocaleString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
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
      { key: 'created', label: 'Tạo', sub: fmtStepDetail(r.created_at), state: 'upcoming' },
      { key: 'pending', label: 'CHỜ DUYỆT', sub: '', state: 'upcoming' },
      { key: 'approved', label: 'Đã Duyệt', sub: '', state: 'upcoming' },
      { key: 'dispatch', label: 'Điều Phối', sub: '', state: 'upcoming' },
      { key: 'running', label: 'Đang Chạy', sub: '', state: 'upcoming' },
      { key: 'done', label: 'Hoàn Tất', sub: '', state: 'upcoming' },
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
    { key: 'created', label: 'Tạo', sub: fmtStepDetail(r.created_at), state: 'upcoming' },
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
    { key: 'approved', label: 'Đã Duyệt', sub: '', state: 'upcoming' },
    { key: 'dispatch', label: 'Điều Phối', sub: '', state: 'upcoming' },
    { key: 'running', label: 'Đang Chạy', sub: '', state: 'upcoming' },
    { key: 'done', label: 'Hoàn Tất', sub: '', state: 'upcoming' },
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
  return v ? new Date(v).toLocaleString('vi-VN') : '-'
}

function fmtShort(v) {
  if (!v) return ''
  const d = new Date(v)
  return d.toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

function fmtDateVi(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('vi-VN')
}

function fmtTimeWindow(depart, arrive) {
  if (!depart) return '—'
  const opt = { hour: '2-digit', minute: '2-digit' }
  const a = new Date(depart).toLocaleTimeString('vi-VN', opt)
  if (!arrive) return a
  const b = new Date(arrive).toLocaleTimeString('vi-VN', opt)
  return `${a} - ${b}`
}

function formatVndCurrency(n) {
  return `${new Intl.NumberFormat('vi-VN').format(Number(n))} VNĐ`
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
    fillPriceForm.value.service_price =
      dr?.service_price != null && dr.service_price !== ''
        ? formatVndWhileTyping(String(dr.service_price))
        : ''
    paperForm.value.paper_reference = req.value?.paper_reference ?? ''
    paperForm.value.paper_received_at = req.value?.paper_received_at
      ? toDatetimeLocalValue(new Date(req.value.paper_received_at))
      : ''
    passengerDraft.value = Math.max(1, Math.min(999, Math.round(Number(dr.passenger_count) || 1)))
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
    ocrErr.value = e?.response?.data?.message ?? 'OCR thất bại.'
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
    attachErr.value = e?.response?.data?.message ?? 'Không tải được file.'
  }
}

async function removeAttachment(a) {
  if (!canDeleteAttachment.value) return
  const ok = await confirmAction({
    title: 'Xóa tệp đính kèm?',
    message: `Bạn có chắc muốn xóa «${a.original_name || 'tệp này'}»? Thao tác không thể hoàn tác.`,
    confirmLabel: 'Xóa tệp',
    danger: true,
  })
  if (!ok) return
  attachErr.value = ''
  deletingId.value = a.id
  try {
    await deleteAttachment(a.id)
    await load()
    showAppSuccess('Đã xóa tệp đính kèm.', 'Đã xử lý')
  } catch (e) {
    attachErr.value = e?.response?.data?.message ?? 'Không xóa được file.'
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
      showAppSuccess('Đã lưu thay đổi thông tin phiếu.', 'Đã xử lý')
    } else {
      showAppSuccess('Đã đánh dấu đã nhận phiếu.', 'Đã xử lý')
    }
    await load()
  } catch (e) {
    paperMsg.value = e?.response?.data?.message ?? 'Lỗi'
  } finally {
    paperActing.value = false
  }
}

async function doRevertPaper() {
  const ok = await confirmAction({
    title: 'Hoàn tác trạng thái phiếu?',
    message: 'Yêu cầu sẽ chuyển về chưa nhận phiếu giấy. Bạn có chắc?',
    confirmLabel: 'Hoàn tác',
    danger: true,
  })
  if (!ok) return
  paperMsg.value = ''
  paperRevertActing.value = true
  try {
    await revertPaperReceived(route.params.id)
    showAppSuccess('Đã hoàn tác trạng thái phiếu.', 'Đã xử lý')
    await load()
  } catch (e) {
    paperMsg.value = e?.response?.data?.message ?? 'Lỗi'
  } finally {
    paperRevertActing.value = false
  }
}

async function onDecideClick(d) {
  if (d === 'approve') {
    const ok = await confirmAction({
      title: 'Duyệt yêu cầu?',
      message:
        'Sau khi duyệt, yêu cầu có thể được phân công chuyến. Bạn có chắc muốn duyệt yêu cầu này?',
      confirmLabel: 'Duyệt',
    })
    if (!ok) return
  } else {
    const ok = await confirmAction({
      title: 'Từ chối yêu cầu?',
      message: 'Yêu cầu sẽ chuyển sang trạng thái từ chối. Bạn có chắc?',
      confirmLabel: 'Từ chối',
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
      if (tripId) {
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
    pdfErr.value = e?.response?.data?.message ?? 'Không xuất được PDF.'
    window.alert(pdfErr.value)
  } finally {
    pdfBusy.value = false
  }
}

function onFillPriceServicePriceInput(v) {
  fillPriceForm.value.service_price = formatVndWhileTyping(v)
}

async function submitFillPrice() {
  fillPriceMsg.value = ''
  const n = parseMoneyVnd(fillPriceForm.value.service_price)
  if (!Number.isFinite(n) || n < 0) {
    fillPriceMsg.value = 'Nhập đơn giá hợp lệ.'
    return
  }
  fillPriceActing.value = true
  try {
    await fillPriceDispatchRequest(Number(route.params.id), { service_price: n })
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
    if (tripId) {
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

onMounted(load)
watch(() => route.params.id, load)
</script>
