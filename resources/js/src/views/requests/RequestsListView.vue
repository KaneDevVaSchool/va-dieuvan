<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 border-b border-slate-200/80 pb-6 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <div class="flex flex-wrap items-center gap-2">
          <h1 class="text-xl font-semibold tracking-tight text-slate-900">
            {{ t('requests_page.title') }}
          </h1>
          <span
            class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-800 ring-1 ring-inset ring-teal-600/20"
          >
            {{ t('requests_page.workspace_badge') }}
          </span>
        </div>
        <p class="mt-1 text-sm text-slate-500">{{ t('requests_page.subtitle') }}</p>
      </div>
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <div class="relative min-w-[220px] flex-1 sm:max-w-xs">
          <MagnifyingGlassIcon
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            aria-hidden="true"
          />
          <input
            v-model="searchInput"
            type="search"
            :placeholder="t('requests_page.search_placeholder')"
            class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
            @input="onSearchInput"
            @keydown.enter="applySearchNow"
          />
        </div>
        <RouterLink
          to="/notifications"
          class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
          :title="t('requests_page.notifications')"
        >
          <BellIcon class="h-5 w-5" aria-hidden="true" />
        </RouterLink>
        <RouterLink
          to="/dispatch-requests/new"
          class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-teal-700"
        >
          <PlusIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('requests_page.create') }}
        </RouterLink>
      </div>
    </div>

    <!-- KPI cards -->
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
              {{ t('requests_page.kpi_total') }}
            </p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">
              {{ formatInt(stats.total) }}
            </p>
            <p v-if="stats.month_trend_pct != null" class="mt-1 text-xs text-teal-700">
              {{ trendLabel(stats.month_trend_pct) }}
            </p>
            <p v-else class="mt-1 text-xs text-slate-400">{{ t('requests_page.kpi_no_trend') }}</p>
          </div>
          <div class="rounded-lg bg-slate-100 p-2 text-slate-600">
            <RectangleStackIcon class="h-6 w-6" aria-hidden="true" />
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0 flex-1">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
              {{ t('requests_page.kpi_in_progress') }}
            </p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">
              {{ formatInt(stats.trips_in_progress) }}
            </p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
              <div
                class="h-full rounded-full bg-teal-500 transition-all"
                :style="{ width: progressBarPct + '%' }"
              />
            </div>
          </div>
          <div class="rounded-lg bg-teal-50 p-2 text-teal-700">
            <TruckIcon class="h-6 w-6" aria-hidden="true" />
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-2">
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
              {{ t('requests_page.kpi_sla_risk') }}
            </p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">
              {{ formatInt(stats.sla_risk) }}
            </p>
            <span
              v-if="stats.sla_risk > 0"
              class="mt-2 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-800 ring-1 ring-amber-200"
            >
              {{ t('requests_page.kpi_action_required') }}
            </span>
            <span v-else class="mt-2 inline-flex text-xs text-emerald-700">{{ t('requests_page.kpi_sla_ok') }}</span>
          </div>
          <div class="rounded-lg bg-amber-50 p-2 text-amber-700">
            <ExclamationTriangleIcon class="h-6 w-6" aria-hidden="true" />
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-sm">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">
          {{ t('requests_page.kpi_volume') }}
        </p>
        <div class="mt-3 h-16 w-full">
          <svg
            class="h-full w-full text-teal-600"
            viewBox="0 0 120 48"
            preserveAspectRatio="none"
            aria-hidden="true"
          >
            <polyline
              :points="sparklinePoints"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </div>
        <p class="mt-1 text-[11px] text-slate-400">{{ t('requests_page.kpi_volume_hint') }}</p>
      </div>
    </div>

    <!-- Yêu cầu mới -->
    <section class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm">
      <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
        <div>
          <h2 class="text-base font-semibold text-slate-900">{{ t('requests_page.section_new_requests') }}</h2>
          <p class="text-xs text-slate-500">{{ t('requests_page.section_new_requests_hint') }}</p>
        </div>
      </div>
      <div v-if="insightsLoading" class="py-6 text-center text-sm text-slate-400">{{ t('requests_page.loading') }}</div>
      <div v-else-if="!pendingSpotlight.length" class="py-6 text-center text-sm text-slate-500">
        {{ t('requests_page.new_requests_empty') }}
      </div>
      <ul v-else class="space-y-3">
        <li
          v-for="r in pendingSpotlight"
          :key="r.id"
          class="flex flex-col gap-3 rounded-xl border border-slate-100 bg-slate-50/50 p-3 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="flex min-w-0 flex-1 gap-3">
            <span class="w-1 shrink-0 rounded-full" :class="requestCardAccentClass(r)" aria-hidden="true" />
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <span
                  v-if="r.is_urgent"
                  class="inline-flex rounded-md bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-800"
                >
                  {{ t('requests_page.filter_priority_urgent') }}
                </span>
                <span
                  v-else
                  class="inline-flex rounded-md px-2 py-0.5 text-[11px] font-medium"
                  :class="tripTypeTagClass(r.trip_type)"
                >
                  {{ labelTripType(r.trip_type) }}
                </span>
              </div>
              <p class="mt-1 font-medium leading-snug text-slate-900">
                {{ requestCardTitle(r) }}
              </p>
              <p class="mt-0.5 text-xs text-slate-500">
                {{ requestCardSub(r) }}
              </p>
            </div>
          </div>
          <RouterLink
            :to="`/requests/${r.id}`"
            class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg border border-teal-200 bg-teal-50 px-4 py-2 text-sm font-medium text-teal-900 transition hover:bg-teal-100"
          >
            {{ canApproveRequests ? t('requests_page.cta_review') : t('requests_page.cta_detail') }}
            <ArrowTopRightOnSquareIcon class="h-4 w-4" />
          </RouterLink>
        </li>
      </ul>
    </section>

    <!-- Phân công tài nguyên -->
    <section
      v-if="canShowAssignPanel"
      class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm"
    >
      <h2 class="text-base font-semibold text-slate-900">
        {{ t('requests_page.section_assign') }}
        <span v-if="assignTripRef" class="font-mono text-sm font-normal text-slate-500">
          — Trip #{{ assignTripRef.id }}
        </span>
      </h2>
      <div v-if="insightsLoading" class="py-8 text-center text-sm text-slate-400">{{ t('requests_page.loading') }}</div>
      <div v-else-if="!assignTripRef" class="py-6 text-center text-sm text-slate-500">
        {{ t('requests_page.assign_no_trip') }}
      </div>
      <div v-else class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 md:items-stretch">
        <div class="flex min-h-0 flex-col space-y-3 rounded-xl border border-slate-100 bg-slate-50/40 p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            {{ t('requests_page.assign_plan_title') }}
          </p>
          <p class="text-sm text-slate-700">
            <span class="font-medium text-slate-900">{{ labelTripType(assignTripRef.dispatch_request?.trip_type) }}</span>
            <span v-if="assignTripRef.depart_at" class="text-slate-600">
              · {{ formatTripWhen(assignTripRef.depart_at) }}
            </span>
          </p>
          <p class="text-sm text-slate-800">
            {{ (assignTripRef.dispatch_request?.origin ?? '—') + ' → ' + (assignTripRef.dispatch_request?.destination ?? '—') }}
          </p>
          <p v-if="assignTripRef.dispatch_request?.passenger_count" class="text-xs text-slate-500">
            {{ t('requests_page.passengers', { n: assignTripRef.dispatch_request.passenger_count }) }}
          </p>
          <div class="border-t border-slate-200/80 pt-3">
            <ol class="space-y-2">
              <li>
                <span
                  class="block rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-900"
                >
                  1. {{ t('requests_page.assign_plan_1') }}
                </span>
              </li>
              <li>
                <span class="block rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                  2. {{ t('requests_page.assign_plan_2') }}
                </span>
              </li>
              <li>
                <span class="block rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                  3. {{ t('requests_page.assign_plan_3') }}
                </span>
              </li>
            </ol>
          </div>
        </div>
        <div class="flex min-h-0 flex-col rounded-xl border border-slate-100 bg-slate-50/40 p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
            {{ t('requests_page.assign_resources_title') }}
          </p>
          <div class="mt-3 flex-1 space-y-4 text-sm">
            <div>
              <p class="text-xs text-slate-500">{{ t('requests_page.assign_internal_vehicles') }}</p>
              <p v-if="assignTripRef.vehicle?.license_plate" class="mt-1 font-mono text-slate-900">
                {{ assignTripRef.vehicle.license_plate }}
                <span v-if="assignTripRef.vehicle.status" class="text-xs font-sans text-slate-500">
                  · {{ assignTripRef.vehicle.status }}
                </span>
              </p>
              <p v-else class="mt-1 text-slate-400">—</p>
            </div>
            <div>
              <p class="text-xs text-slate-500">{{ t('requests_page.assign_drivers_free') }}</p>
              <div v-if="assignTripRef.driver?.full_name" class="mt-1 flex flex-wrap gap-2">
                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-900">
                  {{ assignTripRef.driver.full_name }}
                </span>
              </div>
              <p v-else class="mt-1 text-slate-400">—</p>
            </div>
          </div>
          <RouterLink
            :to="`/trips/${assignTripRef.id}`"
            class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-teal-600 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700"
          >
            {{ t('requests_page.cta_confirm_assign') }}
            <ArrowTopRightOnSquareIcon class="h-5 w-5" />
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- Theo dõi trip đang chạy -->
    <section class="rounded-2xl border border-slate-200/90 bg-white p-4 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">{{ t('requests_page.section_track') }}</h2>
      <div v-if="insightsLoading" class="py-8 text-center text-sm text-slate-400">{{ t('requests_page.loading') }}</div>
      <div v-else-if="!activeTripsTrack.length" class="py-6 text-center text-sm text-slate-500">
        {{ t('requests_page.track_empty') }}
      </div>
      <div v-else class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <RouterLink
          v-for="(row, idx) in activeTripsTrackWithSteps"
          :key="row.tr.id"
          :to="`/trips/${row.tr.id}`"
          class="block rounded-xl border p-3 transition hover:shadow-md sm:p-4"
          :class="tripTrackCardClass(idx)"
        >
          <div class="flex items-start justify-between gap-2">
            <span class="font-mono text-xs font-semibold text-slate-500">#{{ row.tr.id }}</span>
            <span class="text-[11px] font-medium" :class="tripTrackStatusClass(row.tr)">
              {{ tripTrackStatusLabel(row.tr) }}
            </span>
          </div>
          <p class="mt-2 text-sm font-medium text-slate-900">
            {{ labelTripType(row.tr.dispatch_request?.trip_type) }}
          </p>
          <p class="mt-1 line-clamp-2 text-xs text-slate-600">
            {{ (row.tr.dispatch_request?.origin ?? '—') + ' → ' + (row.tr.dispatch_request?.destination ?? '—') }}
          </p>
          <p class="mt-2 text-xs text-slate-500">
            <span v-if="row.tr.driver?.full_name">TX: {{ row.tr.driver.full_name }}</span>
            <span v-if="row.tr.vehicle?.license_plate"> · {{ row.tr.vehicle.license_plate }}</span>
            <span v-if="row.tr.transport_provider?.name"> · {{ row.tr.transport_provider.name }}</span>
          </p>
          <div v-if="row.steps.length" class="mt-3 border-t border-slate-200/80 pt-3">
            <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-400">
              {{ t('requests_page.section_timeline') }}
            </p>
            <div class="-mx-0.5 overflow-x-auto pb-0.5 [scrollbar-width:thin]">
              <div class="flex min-w-[280px] items-start gap-0 px-0.5 sm:min-w-0">
                <div
                  v-for="(step, si) in row.steps"
                  :key="step.id"
                  class="flex min-w-0 flex-1 flex-col items-stretch"
                >
                  <div class="flex items-center">
                    <div
                      v-if="si > 0"
                      class="h-px min-w-[4px] flex-1"
                      :class="row.steps[si - 1].done ? 'bg-teal-300' : 'bg-slate-200'"
                    />
                    <div
                      class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-[9px] font-bold leading-none"
                      :class="
                        step.current
                          ? 'border-teal-600 bg-teal-50 text-teal-800'
                          : step.done
                            ? 'border-emerald-400 bg-emerald-50 text-emerald-800'
                            : 'border-slate-200 bg-white text-slate-300'
                      "
                      :title="step.label + (step.time ? ' · ' + formatShortDate(step.time) : '')"
                    >
                      <CheckCircleIcon
                        v-if="step.done && !step.current"
                        class="h-3 w-3 text-emerald-600"
                        aria-hidden="true"
                      />
                      <PlayCircleIcon v-else-if="step.current" class="h-3 w-3 text-teal-600" aria-hidden="true" />
                      <span v-else class="tabular-nums">{{ si + 1 }}</span>
                    </div>
                    <div
                      v-if="si < row.steps.length - 1"
                      class="h-px min-w-[4px] flex-1"
                      :class="step.done ? 'bg-teal-300' : 'bg-slate-200'"
                    />
                  </div>
                  <p
                    class="mt-1 max-w-[100%] truncate text-center text-[9px] font-medium leading-tight text-slate-500"
                  >
                    {{ step.label }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </RouterLink>
      </div>
    </section>

    <!-- Filters: horizontal bar -->
    <div
      class="rounded-2xl border border-violet-100/90 bg-gradient-to-r from-slate-50 via-violet-50/40 to-indigo-50/25 px-2 py-2 shadow-sm sm:px-3 sm:py-2.5"
    >
      <div class="flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
        <!-- Main filter menu -->
        <details ref="filterMenuRef" class="group relative">
          <summary
            class="flex cursor-pointer list-none items-center gap-1 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white [&::-webkit-details-marker]:hidden"
          >
            <span class="relative inline-flex">
              <FunnelIcon class="h-5 w-5 text-slate-600" aria-hidden="true" />
              <span
                v-if="activeFilterCount > 0"
                class="absolute -right-1.5 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-600 px-1 text-[10px] font-semibold leading-none text-white"
              >
                {{ activeFilterCount > 9 ? '9+' : activeFilterCount }}
              </span>
            </span>
            <ChevronDownIcon class="h-4 w-4 text-slate-400" aria-hidden="true" />
          </summary>
          <div
            class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[260px] rounded-xl border border-slate-200/90 bg-white p-3 shadow-lg ring-1 ring-slate-900/5"
          >
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
              {{ t('requests_page.filter_menu_title') }}
            </p>
            <ul class="mt-2 space-y-2 text-sm text-slate-700">
              <li v-if="filters.trip_type" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('requests_page.filter_trip_type') }}</span>
                <span class="font-medium">{{ labelTripType(filters.trip_type) }}</span>
              </li>
              <li v-if="filters.from || filters.to" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('requests_page.filter_depart_range') }}</span>
                <span class="text-right font-medium">{{ filters.from || '…' }} → {{ filters.to || '…' }}</span>
              </li>
              <li v-if="filters.source_channel" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('requests_page.filter_channel') }}</span>
                <span class="font-medium">{{ labelSourceChannel(filters.source_channel) }}</span>
              </li>
              <li v-if="filters.paper_status" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('requests_page.filter_paper') }}</span>
                <span class="font-medium">{{ labelPaperStatus(filters.paper_status) }}</span>
              </li>
              <li v-if="filters.priority === 'urgent'" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('requests_page.filter_priority') }}</span>
                <span class="font-medium">{{ t('requests_page.filter_priority_urgent') }}</span>
              </li>
              <li v-if="filters.sla_risk_only" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('requests_page.sla_toggle') }}</span>
                <span class="font-medium">{{ t('requests_page.filter_on') }}</span>
              </li>
              <li v-if="filters.per_page !== 10" class="flex justify-between gap-2">
                <span class="text-slate-500">{{ t('requests_page.filter_per_page') }}</span>
                <span class="font-medium">{{ filters.per_page }}</span>
              </li>
              <li v-if="activeFilterCount === 0" class="text-slate-400">{{ t('requests_page.filter_menu_empty') }}</li>
            </ul>
            <button
              type="button"
              class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
              @click="resetFilters(); closeFilterMenu()"
            >
              {{ t('requests_page.filter_clear_all') }}
            </button>
          </div>
        </details>

        <!-- Inline dropdowns -->
        <div class="hidden h-6 w-px bg-slate-200/90 sm:block" aria-hidden="true" />

        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
          <!-- Trip type: title opens panel -->
          <details class="group relative min-w-0">
            <summary
              class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white [&::-webkit-details-marker]:hidden"
            >
              <span class="whitespace-nowrap text-sm text-slate-600">{{ t('requests_page.filter_trip_type') }}</span>
              <span class="min-w-0 max-w-[10rem] truncate text-sm font-medium text-slate-900">{{
                filters.trip_type ? labelTripType(filters.trip_type) : t('requests_page.all')
              }}</span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white p-2 shadow-lg ring-1 ring-slate-900/5"
            >
              <div class="relative">
                <select
                  v-model="filters.trip_type"
                  class="h-9 w-full min-w-[12rem] cursor-pointer appearance-none rounded-md border-0 bg-white py-1.5 pl-2 pr-8 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 transition hover:ring-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
                  @change="onFilterDropdownChange"
                >
                  <option value="">{{ t('requests_page.all') }}</option>
                  <option value="door_to_door">{{ labelTripType('door_to_door') }}</option>
                  <option value="point_to_point">{{ labelTripType('point_to_point') }}</option>
                  <option value="business">{{ labelTripType('business') }}</option>
                  <option value="cargo">{{ labelTripType('cargo') }}</option>
                </select>
                <ChevronDownIcon
                  class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                />
              </div>
            </div>
          </details>

          <!-- Depart date range -->
          <details class="group relative min-w-0">
            <summary
              class="flex max-w-full cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white [&::-webkit-details-marker]:hidden"
            >
              <span class="whitespace-nowrap text-sm text-slate-600">{{ t('requests_page.filter_depart_range') }}</span>
              <span class="min-w-0 truncate text-sm font-medium text-slate-900">{{ filterDepartSummary }}</span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+6px)] z-40 w-[min(100vw-1.5rem,320px)] rounded-xl border border-slate-200/90 bg-white p-3 shadow-lg ring-1 ring-slate-900/5 sm:w-max"
            >
              <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input
                  v-model="filters.from"
                  type="date"
                  class="h-9 w-full rounded-md border-0 bg-white px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-auto"
                  @change="onFilterDropdownChange"
                />
                <span class="hidden text-slate-300 sm:inline">—</span>
                <input
                  v-model="filters.to"
                  type="date"
                  class="h-9 w-full rounded-md border-0 bg-white px-2 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 sm:w-auto"
                  @change="onFilterDropdownChange"
                />
              </div>
            </div>
          </details>

          <details class="group relative min-w-0">
            <summary
              class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white [&::-webkit-details-marker]:hidden"
            >
              <span class="whitespace-nowrap text-sm text-slate-600">{{ t('requests_page.filter_channel') }}</span>
              <span class="min-w-0 max-w-[8rem] truncate text-sm font-medium text-slate-900">{{
                filters.source_channel ? labelSourceChannel(filters.source_channel) : t('requests_page.all')
              }}</span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[200px] rounded-xl border border-slate-200/90 bg-white p-2 shadow-lg ring-1 ring-slate-900/5"
            >
              <div class="relative">
                <select
                  v-model="filters.source_channel"
                  class="h-9 w-full cursor-pointer appearance-none rounded-md border-0 bg-white py-1.5 pl-2 pr-8 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 transition hover:ring-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
                  @change="onFilterDropdownChange"
                >
                  <option value="">{{ t('requests_page.all') }}</option>
                  <option value="portal">{{ labelSourceChannel('portal') }}</option>
                  <option value="zalo">{{ labelSourceChannel('zalo') }}</option>
                  <option value="paper">{{ labelSourceChannel('paper') }}</option>
                </select>
                <ChevronDownIcon
                  class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                />
              </div>
            </div>
          </details>

          <details class="group relative min-w-0">
            <summary
              class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white [&::-webkit-details-marker]:hidden"
            >
              <span class="whitespace-nowrap text-sm text-slate-600">{{ t('requests_page.filter_paper') }}</span>
              <span class="min-w-0 max-w-[9rem] truncate text-sm font-medium text-slate-900">{{
                filters.paper_status ? labelPaperStatus(filters.paper_status) : t('requests_page.all')
              }}</span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[220px] rounded-xl border border-slate-200/90 bg-white p-2 shadow-lg ring-1 ring-slate-900/5"
            >
              <div class="relative">
                <select
                  v-model="filters.paper_status"
                  class="h-9 w-full cursor-pointer appearance-none rounded-md border-0 bg-white py-1.5 pl-2 pr-8 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 transition hover:ring-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
                  @change="onFilterDropdownChange"
                >
                  <option value="">{{ t('requests_page.all') }}</option>
                  <option value="pending">{{ labelPaperStatus('pending') }}</option>
                  <option value="received">{{ labelPaperStatus('received') }}</option>
                  <option value="digitally_signed">{{ labelPaperStatus('digitally_signed') }}</option>
                </select>
                <ChevronDownIcon
                  class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                />
              </div>
            </div>
          </details>

          <details class="group relative min-w-0">
            <summary
              class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white [&::-webkit-details-marker]:hidden"
            >
              <span class="whitespace-nowrap text-sm text-slate-600">{{ t('requests_page.filter_priority') }}</span>
              <span class="min-w-0 max-w-[9rem] truncate text-sm font-medium text-slate-900">{{
                filters.priority === 'urgent'
                  ? t('requests_page.filter_priority_urgent')
                  : t('requests_page.filter_priority_all')
              }}</span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div
              class="absolute left-0 top-[calc(100%+6px)] z-40 min-w-[200px] rounded-xl border border-slate-200/90 bg-white p-2 shadow-lg ring-1 ring-slate-900/5"
            >
              <div class="relative">
                <select
                  v-model="filters.priority"
                  class="h-9 w-full cursor-pointer appearance-none rounded-md border-0 bg-white py-1.5 pl-2 pr-8 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 transition hover:ring-slate-300 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
                  @change="onFilterDropdownChange"
                >
                  <option value="">{{ t('requests_page.filter_priority_all') }}</option>
                  <option value="urgent">{{ t('requests_page.filter_priority_urgent') }}</option>
                </select>
                <ChevronDownIcon
                  class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                />
              </div>
            </div>
          </details>
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800"
            :title="t('requests_page.filter_clear')"
            @click="resetFilters"
          >
            <span class="relative inline-flex">
              <FunnelIcon class="h-5 w-5" />
              <XMarkIcon class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100" />
            </span>
          </button>

          <div class="hidden h-6 w-px bg-slate-200/90 sm:block" aria-hidden="true" />

          <button
            type="button"
            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-2 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-white/70 hover:text-slate-900"
            :aria-expanded="extraFiltersOpen"
            @click="extraFiltersOpen = !extraFiltersOpen"
          >
            {{ t('requests_page.filter_extra') }}
            <PlusCircleIcon class="h-5 w-5 text-teal-600" aria-hidden="true" />
          </button>
        </div>
      </div>

      <!-- Extra attributes -->
      <div
        v-show="extraFiltersOpen"
        class="mt-3 flex flex-wrap items-center gap-4 border-t border-violet-100/80 pt-3"
      >
        <label class="inline-flex cursor-pointer items-center gap-2">
          <button
            type="button"
            role="switch"
            :aria-checked="filters.sla_risk_only"
            class="relative inline-flex h-6 w-11 shrink-0 rounded-full border border-slate-200/80 bg-white transition focus:outline-none focus:ring-2 focus:ring-teal-500/30"
            :class="filters.sla_risk_only ? 'bg-teal-600' : 'bg-slate-200'"
            @click="toggleSla"
          >
            <span
              class="pointer-events-none inline-block h-5 w-5 translate-x-0.5 translate-y-0.5 rounded-full bg-white shadow transition"
              :class="filters.sla_risk_only ? 'translate-x-5' : ''"
            />
          </button>
          <span class="text-sm text-slate-700">{{ t('requests_page.sla_toggle') }}</span>
        </label>
        <label class="inline-flex items-center gap-2">
          <span class="text-sm text-slate-600">{{ t('requests_page.filter_per_page') }}</span>
          <select
            v-model.number="filters.per_page"
            class="h-9 rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
            @change="onFilterChange"
          >
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
        </label>
      </div>
    </div>

    <!-- Status tabs -->
    <div class="-mx-1 overflow-x-auto pb-1">
      <nav class="flex min-w-max gap-1 border-b border-slate-200 px-1" aria-label="Tabs">
        <button
          v-for="tab in tabDefs"
          :key="tab.id"
          type="button"
          class="whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition"
          :class="
            activeTab === tab.id
              ? 'border-teal-600 text-teal-800'
              : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800'
          "
          @click="setTab(tab.id)"
        >
          {{ tab.label }}
          <span class="ml-1.5 tabular-nums text-slate-400">({{ formatInt(tabCount(tab.id)) }})</span>
        </button>
      </nav>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
      <div v-if="loading" class="p-8 text-center text-sm text-slate-500">{{ t('requests_page.loading') }}</div>
      <div v-else-if="!items.length" class="p-8 text-center text-sm text-slate-500">
        {{ t('requests_page.empty') }}
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
          <thead class="bg-slate-50/80">
            <tr>
              <th class="w-10 px-3 py-3">
                <span class="sr-only">{{ t('requests_page.col_select') }}</span>
              </th>
              <th class="px-3 py-3 font-semibold text-slate-700">{{ t('requests_page.col_id') }}</th>
              <th class="px-3 py-3 font-semibold text-slate-700">{{ t('requests_page.col_trip') }}</th>
              <th class="px-3 py-3 font-semibold text-slate-700">{{ t('requests_page.col_type_channel') }}</th>
              <th class="px-3 py-3 font-semibold text-slate-700">{{ t('requests_page.col_timeline') }}</th>
              <th class="px-3 py-3 font-semibold text-slate-700">{{ t('requests_page.col_sla') }}</th>
              <th class="w-28 px-3 py-3 text-right font-semibold text-slate-700">{{ t('requests_page.col_actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="r in items" :key="r.id" class="transition hover:bg-slate-50/80">
              <td class="px-3 py-3 align-top">
                <input type="checkbox" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500" disabled />
              </td>
              <td class="px-3 py-3 align-top">
                <div class="font-semibold text-slate-900">REQ-{{ r.id }}</div>
                <div class="text-xs text-slate-500">{{ formatShortDate(r.created_at) }}</div>
              </td>
              <td class="max-w-xs px-3 py-3 align-top">
                <div class="flex gap-2">
                  <component :is="tripTypeIcon(r.trip_type)" class="mt-0.5 h-5 w-5 shrink-0 text-teal-600" />
                  <div class="min-w-0">
                    <div class="truncate font-medium text-slate-900">
                      {{ (r.origin ?? '—') + ' → ' + (r.destination ?? '—') }}
                    </div>
                    <div class="text-xs text-slate-500">
                      <span v-if="r.passenger_count">{{ t('requests_page.passengers', { n: r.passenger_count }) }}</span>
                      <span v-else-if="r.trip_type === 'cargo'">{{ t('requests_page.cargo') }}</span>
                      <span v-else>{{ t('requests_page.no_passenger_info') }}</span>
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-3 py-3 align-top">
                <div>{{ labelTripType(r.trip_type) }}</div>
                <div class="text-xs text-slate-500">{{ labelSourceChannel(r.source_channel) }}</div>
              </td>
              <td class="px-3 py-3 align-top">
                <span
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                  :class="badgeClass(r.status)"
                >
                  {{ labelRequestStatus(r.status) }}
                </span>
                <div class="mt-1 text-xs text-slate-500">{{ tripTimelineHint(r) }}</div>
              </td>
              <td class="px-3 py-3 align-top text-xs">
                <span v-if="slaCell(r).kind === 'ok'" class="inline-flex items-center gap-1 text-emerald-700">
                  <CheckCircleIcon class="h-4 w-4" />
                  {{ t('requests_page.sla_on_track') }}
                </span>
                <span v-else-if="slaCell(r).kind === 'warn'" class="inline-flex items-center gap-1 text-amber-800">
                  <ExclamationTriangleIcon class="h-4 w-4 shrink-0" />
                  {{ slaCell(r).text }}
                </span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="px-3 py-3 align-top">
                <div class="flex items-center justify-end gap-1">
                  <RouterLink
                    :to="`/requests/${r.id}`"
                    class="inline-flex rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                    :title="t('requests_page.view')"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </RouterLink>
                  <RouterLink
                    v-if="r.status === 'draft'"
                    :to="`/requests/${r.id}`"
                    class="inline-flex rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                    :title="t('requests_page.edit')"
                  >
                    <PencilSquareIcon class="h-5 w-5" />
                  </RouterLink>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-500">
          {{
            t('requests_page.pagination_summary', {
              from: pageFrom,
              to: pageTo,
              total: meta.total ?? 0,
            })
          }}
        </p>
        <div class="flex flex-wrap items-center gap-2">
          <Button variant="secondary" :disabled="(meta.current_page ?? 1) <= 1 || loading" @click="goPage((meta.current_page ?? 1) - 1)">
            {{ t('requests_page.prev') }}
          </Button>
          <div class="flex items-center gap-1">
            <button
              v-for="p in pageNumbers"
              :key="p"
              type="button"
              class="min-w-[2.25rem] rounded-md px-2 py-1.5 text-sm"
              :class="
                p === meta.current_page
                  ? 'bg-teal-600 font-medium text-white'
                  : 'text-slate-600 hover:bg-slate-100'
              "
              @click="goPage(p)"
            >
              {{ p }}
            </button>
          </div>
          <Button
            variant="secondary"
            :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1) || loading"
            @click="goPage((meta.current_page ?? 1) + 1)"
          >
            {{ t('requests_page.next') }}
          </Button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowTopRightOnSquareIcon,
  BellIcon,
  CheckCircleIcon,
  ChevronDownIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  MapPinIcon,
  PencilSquareIcon,
  PlayCircleIcon,
  PlusCircleIcon,
  PlusIcon,
  RectangleStackIcon,
  TruckIcon,
  XMarkIcon,
  AcademicCapIcon,
  CubeIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import { listRequests } from '../../api/requests'
import { listTrips } from '../../api/trips'
import { useAuthStore } from '../../store'
import {
  labelPaperStatus,
  labelRequestStatus,
  labelSourceChannel,
  labelTripStatus,
  labelTripType,
} from '../../util/labels'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const loading = ref(false)
const items = ref([])
const meta = ref({})
const stats = ref({
  total: 0,
  by_status: {},
  trips_in_progress: 0,
  trips_completed: 0,
  sla_risk: 0,
  month_trend_pct: null,
  volume_trend: [],
})

const activeTab = ref('all')
const searchInput = ref('')
let searchDebounce = null
const filterMenuRef = ref(null)
const extraFiltersOpen = ref(false)

const insightsLoading = ref(true)
const pendingSpotlight = ref([])
const assignTripRef = ref(null)
const activeTripsTrack = ref([])

const canApproveRequests = computed(() => auth.hasPermission('request.approve'))
const canShowAssignPanel = computed(() => auth.hasPermission('trip.assign'))

const filters = reactive({
  q: '',
  trip_type: '',
  source_channel: '',
  paper_status: '',
  from: '',
  to: '',
  priority: '',
  sla_risk_only: false,
  per_page: 10,
  page: 1,
})

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.trip_type) n++
  if (filters.from || filters.to) n++
  if (filters.source_channel) n++
  if (filters.paper_status) n++
  if (filters.priority === 'urgent') n++
  if (filters.sla_risk_only) n++
  if (filters.per_page !== 10) n++
  return n
})

const filterDepartSummary = computed(() => {
  if (!filters.from && !filters.to) return t('requests_page.all')
  return `${filters.from || '…'} → ${filters.to || '…'}`
})

const tabDefs = computed(() => [
  { id: 'all', label: t('requests_page.tab_all') },
  { id: 'draft', label: t('requests_page.tab_draft') },
  { id: 'pending', label: t('requests_page.tab_pending') },
  { id: 'approved', label: t('requests_page.tab_approved') },
  { id: 'rejected', label: t('requests_page.tab_rejected') },
  { id: 'cancelled', label: t('requests_page.tab_cancelled') },
  { id: 'trip_in_progress', label: t('requests_page.tab_trip_running') },
  { id: 'trip_completed', label: t('requests_page.tab_trip_done') },
])

const progressBarPct = computed(() => {
  const t = stats.value.total || 0
  const x = stats.value.trips_in_progress || 0
  if (t <= 0) return 0
  return Math.min(100, Math.round((x / t) * 100))
})

const sparklinePoints = computed(() => {
  const pts = stats.value.volume_trend
  if (!pts?.length) return '0,40 120,40'
  const max = Math.max(...pts, 1)
  const w = 120
  const h = 40
  return pts
    .map((v, i) => {
      const x = (i / Math.max(pts.length - 1, 1)) * w
      const y = h - (v / max) * (h - 4) - 2
      return `${x.toFixed(1)},${y.toFixed(1)}`
    })
    .join(' ')
})

const pageFrom = computed(() => {
  const cur = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? 10
  const total = meta.value.total ?? 0
  if (total === 0) return 0
  return (cur - 1) * per + 1
})

const pageTo = computed(() => {
  const cur = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? 10
  const total = meta.value.total ?? 0
  return Math.min(cur * per, total)
})

const pageNumbers = computed(() => {
  const last = meta.value.last_page ?? 1
  const cur = meta.value.current_page ?? 1
  const window = 3
  const start = Math.max(1, cur - 1)
  const end = Math.min(last, start + window - 1)
  const list = []
  for (let p = start; p <= end; p++) list.push(p)
  return list
})

function formatInt(n) {
  return new Intl.NumberFormat('vi-VN').format(n ?? 0)
}

function trendLabel(pct) {
  if (pct > 0) return t('requests_page.trend_up', { pct })
  if (pct < 0) return t('requests_page.trend_down', { pct: Math.abs(pct) })
  return t('requests_page.trend_flat')
}

function formatShortDate(v) {
  if (!v) return '—'
  try {
    return new Date(v).toLocaleString('vi-VN', { dateStyle: 'medium', timeStyle: 'short' })
  } catch {
    return String(v)
  }
}

function badgeClass(status) {
  if (status === 'approved') return 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200'
  if (status === 'pending') return 'bg-amber-50 text-amber-800 ring-1 ring-amber-200'
  if (status === 'draft') return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200'
  if (status === 'rejected') return 'bg-rose-50 text-rose-800 ring-1 ring-rose-200'
  if (status === 'cancelled') return 'bg-slate-100 text-slate-500 ring-1 ring-slate-200'
  return 'bg-slate-100 text-slate-700'
}

function tripTypeIcon(type) {
  if (type === 'cargo') return CubeIcon
  if (type === 'business') return AcademicCapIcon
  return MapPinIcon
}

function tripTimelineHint(r) {
  if (r.trip?.status) {
    return labelTripStatus(r.trip.status)
  }
  if (r.status === 'pending') return t('requests_page.hint_await_assign')
  if (r.status === 'approved' && !r.trip) return t('requests_page.hint_no_trip')
  return labelPaperStatus(r.paper_status) || '—'
}

/** @param {Record<string, unknown>} r */
function slaCell(r) {
  if (r.status !== 'pending') {
    return { kind: 'neutral' }
  }
  const depart = r.depart_at ? new Date(r.depart_at) : null
  if (r.is_urgent) {
    return { kind: 'warn', text: t('requests_page.sla_urgent') }
  }
  if (depart) {
    const hours = (depart.getTime() - Date.now()) / 36e5
    if (hours > 0 && hours <= 48) {
      return {
        kind: 'warn',
        text: t('requests_page.sla_depart_hours', { h: Math.max(1, Math.round(hours)) }),
      }
    }
  }
  return { kind: 'ok' }
}

function tabCount(tabId) {
  const b = stats.value.by_status || {}
  switch (tabId) {
    case 'all':
      return stats.value.total ?? 0
    case 'draft':
      return b.draft ?? 0
    case 'pending':
      return b.pending ?? 0
    case 'approved':
      return b.approved ?? 0
    case 'rejected':
      return b.rejected ?? 0
    case 'cancelled':
      return b.cancelled ?? 0
    case 'trip_in_progress':
      return stats.value.trips_in_progress ?? 0
    case 'trip_completed':
      return stats.value.trips_completed ?? 0
    default:
      return 0
  }
}

/**
 * @param {Record<string, unknown> | null} trip
 */
function buildTimelineSteps(trip, tr) {
  if (!trip) return []
  const dr = trip.dispatch_request
  if (!dr) return []
  const hasAssign = !!(trip.vehicle_id || trip.driver_id || trip.transport_provider_id)
  const st = trip.status
  const isRunning = st === 'in_progress' || st === 'driver_confirmed'

  return [
    {
      id: 'req',
      label: tr('requests_page.tl_request'),
      time: dr.created_at,
      done: true,
      current: false,
    },
    {
      id: 'appr',
      label: tr('requests_page.tl_approve'),
      time: dr.status === 'approved' ? trip.created_at : null,
      done: dr.status === 'approved',
      current: false,
    },
    {
      id: 'trip',
      label: tr('requests_page.tl_trip'),
      time: trip.created_at,
      done: !!trip.created_at,
      current: false,
    },
    {
      id: 'as',
      label: tr('requests_page.tl_assigned'),
      time: hasAssign ? trip.updated_at : null,
      done: hasAssign,
      current: false,
    },
    {
      id: 'run',
      label: tr('requests_page.tl_in_progress'),
      time: trip.started_at,
      done: !!trip.started_at && !isRunning,
      current: isRunning,
    },
    {
      id: 'done',
      label: tr('requests_page.tl_completed'),
      time: trip.completed_at,
      done: !!trip.completed_at,
      current: false,
    },
    {
      id: 'paid',
      label: tr('requests_page.tl_paid'),
      time: trip.paid_at,
      done: trip.payment_status === 'paid' && !!trip.paid_at,
      current: false,
    },
  ]
}

const activeTripsTrackWithSteps = computed(() =>
  activeTripsTrack.value.map((tr) => ({
    tr,
    steps: buildTimelineSteps(tr, t),
  })),
)

function requestCardAccentClass(r) {
  if (r.is_urgent) return 'bg-rose-500'
  if (r.trip_type === 'cargo') return 'bg-emerald-500'
  if (r.trip_type === 'business') return 'bg-slate-400'
  return 'bg-teal-500'
}

function tripTypeTagClass(tripType) {
  if (tripType === 'cargo') return 'bg-emerald-100 text-emerald-800'
  if (tripType === 'business') return 'bg-slate-100 text-slate-700'
  return 'bg-teal-100 text-teal-800'
}

function requestCardTitle(r) {
  const route = `${r.origin ?? '—'} → ${r.destination ?? '—'}`
  if (!r.depart_at) return route
  try {
    const d = new Date(r.depart_at)
    return `${route} · ${d.toLocaleDateString('vi-VN')}`
  } catch {
    return route
  }
}

function requestCardSub(r) {
  const parts = []
  if (r.passenger_count) parts.push(`${r.passenger_count} khách`)
  if (r.notes) parts.push(String(r.notes).slice(0, 96))
  return parts.join(' · ') || '—'
}

function formatTripWhen(v) {
  if (!v) return ''
  try {
    return new Date(v).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' })
  } catch {
    return String(v)
  }
}

function tripTrackCardClass(idx) {
  const rings = [
    'border-teal-200 ring-1 ring-teal-100',
    'border-amber-200 ring-1 ring-amber-100',
    'border-sky-200 ring-1 ring-sky-100',
  ]
  return rings[idx % 3]
}

function tripTrackStatusLabel(tr) {
  if (tr.status === 'assigned' && tr.depart_at && new Date(tr.depart_at) < new Date()) {
    return t('requests_page.status_delay')
  }
  return labelTripStatus(tr.status)
}

function tripTrackStatusClass(tr) {
  if (tr.status === 'assigned' && tr.depart_at && new Date(tr.depart_at) < new Date()) {
    return 'text-amber-700'
  }
  if (tr.status === 'in_progress') return 'text-teal-700'
  return 'text-slate-600'
}

async function loadInsights() {
  insightsLoading.value = true
  try {
    const [pendingRes, inProgRes, approvedRes] = await Promise.all([
      listRequests({ status: 'pending', per_page: 5, page: 1 }),
      listTrips({ status: 'in_progress', per_page: 3, page: 1 }),
      listTrips({ status: 'approved', per_page: 1, page: 1 }),
    ])
    pendingSpotlight.value = pendingRes.items ?? []
    activeTripsTrack.value = inProgRes.items ?? []
    let assign = approvedRes.items?.[0] ?? null
    if (!assign) {
      const pendT = await listTrips({ status: 'pending', per_page: 1, page: 1 })
      assign = pendT.items?.[0] ?? null
    }
    assignTripRef.value = assign
  } catch {
    pendingSpotlight.value = []
    activeTripsTrack.value = []
    assignTripRef.value = null
  } finally {
    insightsLoading.value = false
  }
}

function buildListParams() {
  const params = { ...filters }
  delete params.priority
  params.q = searchInput.value.trim() || undefined
  if (filters.priority === 'urgent') {
    params.is_urgent = true
  }

  if (activeTab.value === 'trip_in_progress') {
    params.trip_status = 'in_progress'
    params.status = undefined
  } else if (activeTab.value === 'trip_completed') {
    params.trip_status = 'completed'
    params.status = undefined
  } else if (activeTab.value !== 'all') {
    params.status = activeTab.value
    params.trip_status = undefined
  } else {
    params.status = undefined
    params.trip_status = undefined
  }

  Object.keys(params).forEach((k) => {
    if (params[k] === '' || params[k] === null || params[k] === undefined) delete params[k]
  })
  if (params.sla_risk_only === false) delete params.sla_risk_only

  return params
}

function onFilterChange() {
  filters.page = 1
  reload()
}

function closeParentDetails(ev) {
  const el = ev?.target
  if (!el || typeof el.closest !== 'function') return
  const d = el.closest('details')
  if (d) d.open = false
}

function onFilterDropdownChange(ev) {
  closeParentDetails(ev)
  onFilterChange()
}

function closeFilterMenu() {
  const el = filterMenuRef.value
  if (el && 'open' in el) {
    el.open = false
  }
}

async function reload() {
  loading.value = true
  try {
    const params = buildListParams()
    const res = await listRequests(params)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
    if (res.stats) {
      stats.value = { ...stats.value, ...res.stats }
    }
  } finally {
    loading.value = false
  }
  void loadInsights()
}

function goPage(p) {
  filters.page = p
  reload()
}

function setTab(id) {
  activeTab.value = id
  filters.page = 1
  const q = { ...route.query }
  delete q.status
  delete q.trip_status
  if (id === 'trip_in_progress') q.trip_status = 'in_progress'
  else if (id === 'trip_completed') q.trip_status = 'completed'
  else if (id !== 'all') q.status = id
  router.replace({ query: q })
}

function toggleSla() {
  filters.sla_risk_only = !filters.sla_risk_only
  filters.page = 1
  reload()
}

function resetFilters() {
  activeTab.value = 'all'
  filters.trip_type = ''
  filters.source_channel = ''
  filters.paper_status = ''
  filters.from = ''
  filters.to = ''
  filters.priority = ''
  filters.sla_risk_only = false
  filters.per_page = 10
  filters.page = 1
  searchInput.value = ''
  const hadQuery = Object.keys(route.query).length > 0
  router.replace({ query: {} })
  if (!hadQuery) {
    reload()
  }
}

function applySearchNow() {
  if (searchDebounce) clearTimeout(searchDebounce)
  filters.page = 1
  reload()
}

function applyRouteQuery() {
  const q = route.query
  if (typeof q.trip_status === 'string') {
    if (q.trip_status === 'in_progress') activeTab.value = 'trip_in_progress'
    else if (q.trip_status === 'completed') activeTab.value = 'trip_completed'
  } else if (typeof q.status === 'string' && ['draft', 'pending', 'approved', 'rejected', 'cancelled'].includes(q.status)) {
    activeTab.value = q.status
  } else {
    activeTab.value = 'all'
  }
  if (typeof q.trip_type === 'string') filters.trip_type = q.trip_type
  if (typeof q.source_channel === 'string') filters.source_channel = q.source_channel
  if (typeof q.paper_status === 'string') filters.paper_status = q.paper_status
  if (typeof q.q === 'string') {
    searchInput.value = q.q
  }
}

function onSearchInput() {
  if (searchDebounce) clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => {
    filters.page = 1
    reload()
  }, 400)
}

watch(
  () => route.query,
  () => {
    applyRouteQuery()
    reload()
  },
  { deep: true },
)

onMounted(() => {
  applyRouteQuery()
  reload()
})

onUnmounted(() => {
  if (searchDebounce) clearTimeout(searchDebounce)
})
</script>
