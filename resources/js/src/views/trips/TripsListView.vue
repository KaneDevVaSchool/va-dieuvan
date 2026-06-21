<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-3 border-b border-slate-200/80 pb-6 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">
          {{ pageTitle }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('trips_page.hero_subtitle') }}</p>
      </div>
    </div>

    <TripsSummaryBar
      :stats="stats"
      :loading="statsLoading"
      :trip-type="filters.trip_type"
      :run-bucket="filters.run_bucket"
      @quick-filter="onKpiQuickFilter"
    />

    <div
      ref="tripsDatagridRef"
      class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
    >
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
          <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
            <DatagridToolbarSearch
              v-model="searchInput"
              input-id="trips-list-search"
              :placeholder="t('trips_page.search_placeholder')"
              stretch
              inline-actions
              hide-label
              input-height="h-10"
              @enter="flushSearch"
            />
          </div>

          <div class="flex shrink-0 items-center gap-2">
            <FilterVisibilityDropdown
              :open="showFilterPanelDd"
              :title="t('trips_page.filter_show_controls_title')"
              :hint="t('trips_page.filter_show_controls_hint')"
              @close="closeFilterPanel"
            >
              <template #trigger>
                <DatagridToolbarActionButton
                  icon="filter"
                  :active="showFilterPanelDd"
                  test-id="trips-toolbar-filter"
                  @click="openFilterPanel"
                >
                  {{ t('trips_page.toolbar_filter') }}
                </DatagridToolbarActionButton>
              </template>
              <li v-for="fd in filterControlDefs" :key="'trips-vis-' + fd.key" class="flex items-start gap-2">
                <input
                  :id="`trips-filter-vis-${fd.key}`"
                  v-model="visibleFilters[fd.key]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                  :data-testid="`trips-filter-vis-${fd.key}`"
                />
                <label
                  :for="`trips-filter-vis-${fd.key}`"
                  class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                >
                  {{ fd.label }}
                </label>
              </li>
            </FilterVisibilityDropdown>
          </div>

          <div class="ml-auto flex shrink-0 items-center">
            <button
              type="button"
              class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 transition hover:bg-slate-50 hover:text-slate-800 dark:hover:bg-slate-800"
              :title="t('trips_page.filter_clear_all')"
              data-testid="trips-reset-filters"
              @click="resetFilters"
            >
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              <XMarkIcon class="h-3 w-3 text-rose-500" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="hasFilterRow"
        class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
      >
        <DatagridFilterField v-if="visibleFilters.trip_type">
          <select
            v-model="filters.trip_type"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('requests_page.filter_trip_type')"
            data-testid="trips-filter-trip-type"
            @change="onTripTypeFilterChange"
          >
            <option v-for="opt in tripTypeFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.date_range">
          <FilterDatePicker
            v-model="filters.from"
            :placeholder="t('dashboard_analytics.range_from')"
            :max-date="filters.to || null"
            input-id="trips-filter-from"
            @update:model-value="onFilterChange"
          />
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.date_range">
          <FilterDatePicker
            v-model="filters.to"
            :placeholder="t('dashboard_analytics.range_to')"
            :min-date="filters.from || null"
            input-id="trips-filter-to"
            @update:model-value="onFilterChange"
          />
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.run">
          <select
            :value="filters.run_bucket"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('dashboard_analytics.filter_trip_run_status')"
            data-testid="trips-filter-run"
            @change="onRunBucketSelect($event.target.value)"
          >
            <option v-for="opt in runBucketFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.channel">
          <select
            v-model="filters.source_channel"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('dashboard_analytics.filter_channel')"
            data-testid="trips-filter-channel"
            @change="onFilterChange"
          >
            <option v-for="opt in channelFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.paper">
          <select
            v-model="filters.paper_status"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('dashboard_analytics.filter_paper')"
            data-testid="trips-filter-paper"
            @change="onFilterChange"
          >
            <option v-for="opt in paperFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.fleet">
          <select
            v-model="filters.fleet_mode"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('dashboard_analytics.filter_fleet')"
            data-testid="trips-filter-fleet"
            @change="onFilterChange"
          >
            <option v-for="opt in fleetFilterOptions" :key="opt.value === '' ? '_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.urgent">
          <select
            :value="filters.is_urgent ? '1' : ''"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('dashboard_analytics.filter_urgent')"
            data-testid="trips-filter-urgent"
            @change="onUrgentSelect($event.target.value)"
          >
            <option value="">{{ t('dashboard_analytics.filter_urgent') }}</option>
            <option value="1">{{ t('dashboard_analytics.filter_urgent_only') }}</option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.per_page">
          <select
            v-model.number="filters.per_page"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('filter_bar.per_page')"
            data-testid="trips-filter-per-page"
            @change="onFilterChange"
          >
            <option v-for="opt in perPageFilterOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>
      </div>
    </div>

    <!-- List -->
    <div v-if="loading" class="text-sm text-slate-500 dark:text-slate-400">{{ t('trips_page.loading') }}</div>
    <div v-else class="space-y-3 md:space-y-4">
      <article
        v-for="trip in items"
        :key="trip.id"
        class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/50"
      >
        <div class="border-b border-slate-100 px-3 py-4 sm:px-5 dark:border-slate-800">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex min-w-0 gap-3 sm:gap-4">
              <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl sm:h-12 sm:w-12"
                :class="tripTypeIconWrap(trip.dispatch_request?.trip_type)"
              >
                <TruckIcon v-if="trip.dispatch_request?.trip_type === 'door_to_door'" class="h-6 w-6 text-sky-600 dark:text-sky-400" />
                <MapPinIcon v-else-if="trip.dispatch_request?.trip_type === 'point_to_point'" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                <BriefcaseIcon v-else-if="trip.dispatch_request?.trip_type === 'business'" class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                <QueueListIcon v-else class="h-6 w-6 text-slate-600 dark:text-slate-400" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                      {{ t('trips_page.card_trip_code') }}
                    </p>
                    <RouterLink
                      :to="`${tripDetailPrefix}/${trip.id}`"
                      class="mt-0.5 inline-block font-mono text-lg font-bold tracking-tight text-slate-900 underline decoration-slate-300 underline-offset-2 hover:text-va-800 hover:decoration-va-400 dark:text-slate-100"
                      :data-testid="`trips-card-link-${trip.id}`"
                    >
                      {{ tripCode(trip.id) }}
                    </RouterLink>
                  </div>
                  <span
                    class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                    :class="tripTypeBadgeClass(trip.dispatch_request?.trip_type)"
                  >
                    {{ tripTypeLabel(trip.dispatch_request?.trip_type) }}
                  </span>
                  <span
                    v-if="trip.dispatch_request?.is_urgent"
                    class="rounded-md bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-800 dark:bg-rose-950/60 dark:text-rose-200"
                  >
                    {{ t('trips_page.badge_urgent') }}
                  </span>
                  <span
                    v-if="trip.dispatch_request?.dispatch_request_template_id"
                    class="rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-900 dark:bg-indigo-950/50 dark:text-indigo-100"
                  >
                    {{ t('trips_page.badge_recurring') }}
                  </span>
                </div>
                <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2 xl:grid-cols-3">
                  <div class="min-w-0">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                      {{ t('trips_page.created_at_label') }}
                    </dt>
                    <dd class="mt-0.5 font-medium tabular-nums text-slate-800 dark:text-slate-200">
                      {{ fmtTripDateTime(trip.created_at) }}
                    </dd>
                  </div>
                  <div class="min-w-0">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                      {{ t('trips_page.meta_channel') }}
                    </dt>
                    <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200">
                      {{
                        trip.dispatch_request?.source_channel
                          ? labelSourceChannel(trip.dispatch_request.source_channel)
                          : t('trips_page.empty_channel')
                      }}
                    </dd>
                  </div>
                  <div class="min-w-0">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                      {{ t('trips_page.meta_paper') }}
                    </dt>
                    <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-200">
                      {{
                        trip.dispatch_request?.paper_status
                          ? labelPaperStatus(trip.dispatch_request.paper_status)
                          : t('trips_page.empty_paper')
                      }}
                    </dd>
                  </div>
                </dl>
              </div>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2 lg:justify-end">
              <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold sm:text-sm', statusPillClass(trip.status)]">
                {{ labelTripStatus(trip.status) }}
              </span>
              <span
                class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300"
              >
                {{ t('trips_page.meta_payment') }}: {{ paymentLabel(trip.payment_status) }}
              </span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 px-3 py-4 sm:px-4 md:grid-cols-2 md:gap-5 xl:grid-cols-4">
          <div class="min-w-0">
            <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">
              <MapPinIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ t('trips_page.col_origin') }}
            </div>
            <p class="mt-1 text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100 sm:text-base">
              {{ displayPlace(trip.dispatch_request?.origin, 'empty_origin') }}
            </p>
            <div class="mt-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ t('trips_page.depart_at_label') }}
              </p>
              <p class="mt-0.5 text-sm font-semibold tabular-nums text-teal-800 dark:text-teal-300">
                {{ fmtDepartAt(trip.depart_at) }}
              </p>
            </div>
          </div>
          <div class="min-w-0">
            <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-400">
              <MapPinIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ t('trips_page.col_destination') }}
            </div>
            <p class="mt-1 text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100 sm:text-base">
              {{ displayPlace(trip.dispatch_request?.destination, 'empty_destination') }}
            </p>
            <div class="mt-2">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ t('trips_page.arrive_by_label') }}
              </p>
              <p class="mt-0.5 text-sm font-semibold tabular-nums text-rose-800 dark:text-rose-300">
                {{ fmtArriveBy(trip.arrive_by || trip.dispatch_request?.arrive_by) }}
              </p>
            </div>
          </div>
          <div class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-800/40">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('trips_page.col_driver') }} · {{ t('trips_page.col_vehicle') }}
            </p>
            <div v-if="trip.driver" class="mt-2 flex items-start gap-2">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800 dark:bg-teal-950 dark:text-teal-200">
                {{ driverInitials(trip.driver.full_name) }}
              </div>
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
                  {{ trip.driver.full_name }}
                </p>
                <p v-if="trip.driver.phone" class="mt-0.5 text-xs tabular-nums text-slate-600 dark:text-slate-400">
                  {{ trip.driver.phone }}
                </p>
                <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                  <span v-if="trip.vehicle" class="font-medium">{{ trip.vehicle.license_plate }}</span>
                  <span v-else class="text-slate-500">{{ t('trips_page.empty_vehicle') }}</span>
                  <span v-if="trip.external_vehicle_ref" class="block truncate">{{ trip.external_vehicle_ref }}</span>
                  <span v-if="trip.external_driver_ref" class="block truncate">{{ trip.external_driver_ref }}</span>
                </p>
              </div>
            </div>
            <div v-else class="mt-2 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                ?
              </div>
              <span>{{ t('trips_page.unassigned') }}</span>
            </div>
            <div v-if="trip.transport_provider" class="mt-3 border-t border-slate-200/80 pt-2 text-xs dark:border-slate-700">
              <span class="font-semibold text-slate-600 dark:text-slate-300">{{ t('trips_page.col_provider') }}:</span>
              <span class="ml-1 text-slate-800 dark:text-slate-200">{{ trip.transport_provider.name }}</span>
            </div>
          </div>
          <div class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-800/40">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('trips_page.col_ops_meta') }}
            </p>
            <ul class="mt-2 space-y-2 text-xs text-slate-700 dark:text-slate-300 sm:text-sm">
              <li>
                <span class="font-semibold text-slate-600 dark:text-slate-400">{{ t('trips_page.col_dispatcher') }}</span>
                {{ trip.dispatcher?.name || t('trips_page.empty_dispatcher') }}
              </li>
              <li>
                <span class="font-semibold text-slate-600 dark:text-slate-400">{{ t('trips_page.started_at_label') }}</span>
                {{ fmtTripDateTime(trip.started_at) }}
              </li>
              <li>
                <span class="font-semibold text-slate-600 dark:text-slate-400">{{ t('trips_page.completed_at_label') }}</span>
                {{ fmtTripDateTime(trip.completed_at) }}
              </li>
            </ul>
            <p
              v-if="trip.dispatch_request?.notes && String(trip.dispatch_request.notes).trim()"
              class="mt-3 line-clamp-3 border-t border-slate-200/80 pt-2 text-xs leading-relaxed text-slate-600 dark:border-slate-700 dark:text-slate-400"
              :title="String(trip.dispatch_request.notes).trim()"
            >
              <span class="font-semibold text-slate-700 dark:text-slate-300">{{ t('trips_page.notes_label') }}</span>
              {{ trip.dispatch_request.notes }}
            </p>
          </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-100 px-3 py-3 dark:border-slate-800 sm:px-4 sm:py-3.5 md:flex-row md:items-center md:justify-between">
          <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-600 dark:text-slate-400 sm:text-sm">
            <span class="inline-flex items-center gap-1.5">
              <UserPlusIcon class="h-4 w-4 shrink-0 text-slate-400" />
              {{ passengerMetaSummary(trip) }}
            </span>
            <span class="inline-flex items-center gap-1.5">
              <ArrowsRightLeftIcon class="h-4 w-4 shrink-0 text-slate-400" />
              {{ distanceSummary(trip) }}
            </span>
            <span class="inline-flex items-center gap-1.5">
              <ClockIcon class="h-4 w-4 shrink-0 text-slate-400" />
              {{ durationSummary(trip) }}
            </span>
          </div>
          <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:justify-end">
            <RouterLink
              :to="`${tripDetailPrefix}/${trip.id}`"
              class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 sm:min-h-0 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
            >
              <EyeIcon class="h-4 w-4 shrink-0" />
              {{ t('trips_page.action_detail') }}
            </RouterLink>
            <a
              v-if="mapsHref(trip)"
              :href="mapsHref(trip)"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-teal-200 bg-teal-50 px-3 py-2.5 text-sm font-medium text-teal-900 hover:bg-teal-100 sm:min-h-0 dark:border-teal-900 dark:bg-teal-950/60 dark:text-teal-100"
            >
              <ArrowTopRightOnSquareIcon class="h-4 w-4 shrink-0" />
              {{ t('trips_page.action_track') }}
            </a>
            <RouterLink
              v-if="canAssignTrip && needsAssign(trip)"
              :to="`${tripDetailPrefix}/${trip.id}`"
              class="col-span-2 inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl bg-amber-500 px-3 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-600 sm:col-span-1 sm:min-h-0"
            >
              <UserPlusIcon class="h-4 w-4 shrink-0" />
              {{ t('trips_page.action_assign') }}
            </RouterLink>
            <a
              v-else-if="trip.driver?.phone"
              :href="`tel:${trip.driver.phone}`"
              class="col-span-2 inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl bg-violet-600 px-3 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-violet-700 sm:col-span-1 sm:min-h-0"
            >
              <PhoneIcon class="h-4 w-4 shrink-0" />
              {{ t('trips_page.action_contact') }}
            </a>
          </div>
        </div>
      </article>
      <div v-if="!items.length" class="rounded-2xl border border-dashed border-slate-200 py-12 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
        {{ t('trips_page.empty') }}
      </div>
    </div>

    <!-- Pagination -->
    <div
      v-if="(meta.total ?? 0) > 0"
      class="flex flex-col gap-3 rounded-2xl border border-slate-200/90 bg-white px-4 py-3 text-sm shadow-sm dark:border-slate-700 dark:bg-slate-900/50 sm:flex-row sm:items-center sm:justify-between"
    >
      <p class="text-slate-600 dark:text-slate-400">
        {{ t('trips_page.page_range', { from: pageFrom, to: pageTo, total: meta.total ?? 0 }) }}
      </p>
      <div class="flex flex-wrap items-center justify-end gap-1">
        <button
          type="button"
          class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 disabled:opacity-40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          :disabled="(meta.current_page ?? 1) <= 1"
          :aria-label="t('trips_page.prev')"
          @click="goPage((meta.current_page ?? 1) - 1)"
        >
          <ChevronLeftIcon class="h-5 w-5" />
        </button>
        <button
          v-for="n in pageNumbers"
          :key="'p-' + n"
          type="button"
          :class="[
            'h-9 min-w-[2.25rem] rounded-lg px-2 text-sm font-medium tabular-nums',
            n === (meta.current_page ?? 1)
              ? 'bg-teal-600 text-white shadow-sm'
              : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800',
          ]"
          @click="goPage(n)"
        >
          {{ n }}
        </button>
        <button
          type="button"
          class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 disabled:opacity-40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1)"
          :aria-label="t('trips_page.next')"
          @click="goPage((meta.current_page ?? 1) + 1)"
        >
          <ChevronRightIcon class="h-5 w-5" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowTopRightOnSquareIcon,
  ArrowsRightLeftIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClockIcon,
  EyeIcon,
  FunnelIcon,
  XMarkIcon,
  BriefcaseIcon,
  MapPinIcon,
  PhoneIcon,
  QueueListIcon,
  TruckIcon,
  UserPlusIcon,
} from '@heroicons/vue/24/outline'
import TripsSummaryBar from '../../components/trips/TripsSummaryBar.vue'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../../components/shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'
import FilterDatePicker from '../../components/shared/ui/FilterDatePicker.vue'
import { getTripStats, listTrips } from '../../api/trips'
import {
  labelPaperStatus,
  labelSourceChannel,
  labelTripStatus,
  labelTripType,
} from '../../util/labels'
import { dispatchRequestEffectivePassengerCount } from '../../util/dispatchRequestPassengers'
import { tripStatusAdminPillClass } from '../../constants/tripStatus'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import { useVisiblePoll } from '../../composables/useDriverVisiblePoll'
import { useAuthStore } from '../../store'
import { buildStaffPrefixedPath as staffPath } from '../../config/dispatchWebBase'
import { formatListDateTime } from '../../util/datetime'

const { t, locale } = useI18n()
const route = useRoute()
const auth = useAuthStore()

const pageTitle = computed(() => t('trips_page.hero_title'))

const tripDetailPrefix = computed(() =>
  route.path.startsWith('/driver') ? '/driver/trips' : staffPath('/trips'),
)

const loading = ref(false)
const statsLoading = ref(false)
const items = ref([])
const meta = ref({})
const stats = ref({
  total: 0,
  by_trip_type: {},
  incident: 0,
  by_run: { awaiting_dispatch: 0, in_progress: 0, completed: 0, incident: 0 },
})

const searchInput = ref('')
const searchDebounce = ref(null)
const tripsDatagridRef = ref(null)
useDetailsAutoCloseWithin(tripsDatagridRef)

const FILTER_CONTROL_CLASS =
  'h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100'

const TRIPS_FILTER_CONTROLS = [
  { key: 'trip_type', label: '', default: false },
  { key: 'date_range', label: '', default: false },
  { key: 'run', label: '', default: false },
  { key: 'channel', label: '', default: false },
  { key: 'paper', label: '', default: false },
  { key: 'fleet', label: '', default: false },
  { key: 'urgent', label: '', default: false },
  { key: 'per_page', label: '', default: false },
]

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
} = useVisibleFilterControls(TRIPS_FILTER_CONTROLS, 'va-dieuvan.trips.visible-filters.v1')

const FILTER_CONTROL_LABEL_KEYS = {
  trip_type: 'requests_page.filter_trip_type',
  date_range: 'trips_page.filter_vis_dates',
  run: 'dashboard_analytics.filter_trip_run_status',
  channel: 'dashboard_analytics.filter_channel',
  paper: 'dashboard_analytics.filter_paper',
  fleet: 'dashboard_analytics.filter_fleet',
  urgent: 'dashboard_analytics.filter_urgent',
  per_page: 'filter_bar.per_page',
}

const filterControlDefs = computed(() =>
  TRIPS_FILTER_CONTROLS.map((fd) => ({
    key: fd.key,
    label: t(FILTER_CONTROL_LABEL_KEYS[fd.key] ?? fd.key),
  })),
)

const perPageFilterOptions = computed(() => [
  { value: 10, label: '10' },
  { value: 20, label: t('filter_bar.per_page') },
  { value: 50, label: '50' },
  { value: 100, label: '100' },
])

const filters = reactive({
  trip_type: '',
  run_bucket: '',
  status: '',
  source_channel: '',
  paper_status: '',
  is_urgent: false,
  fleet_mode: '',
  from: '',
  to: '',
  q: '',
  page: 1,
  per_page: 20,
})

const rangeValid = computed(() => {
  const from = filters.from
  const to = filters.to
  if (!from && !to) return true
  if (!from || !to) return false
  return from <= to
})

const tripTypeFilterOptions = computed(() => [
  { value: '', label: t('requests_page.filter_trip_type') },
  { value: 'door_to_door', label: labelTripType('door_to_door') },
  { value: 'point_to_point', label: labelTripType('point_to_point') },
  { value: 'business', label: labelTripType('business') },
])

const runBucketFilterOptions = computed(() => [
  { value: '', label: t('dashboard_analytics.filter_trip_run_status') },
  { value: 'awaiting_dispatch', label: t('trips_page.kpi_awaiting_dispatch') },
  { value: 'in_progress', label: t('trips_page.kpi_running') },
  { value: 'completed', label: t('trips_page.kpi_completed') },
  { value: 'incident', label: t('trips_page.kpi_issues') },
])

const channelFilterOptions = computed(() => [
  { value: '', label: t('dashboard_analytics.filter_channel') },
  { value: 'portal', label: t('labels.source_channel.portal') },
  { value: 'zalo', label: t('labels.source_channel.zalo') },
  { value: 'paper', label: t('labels.source_channel.paper') },
])

const paperFilterOptions = computed(() => [
  { value: '', label: t('dashboard_analytics.filter_paper') },
  { value: 'pending', label: t('labels.paper_status.pending') },
  { value: 'received', label: t('labels.paper_status.received') },
  { value: 'digitally_signed', label: t('labels.paper_status.digitally_signed') },
])

const fleetFilterOptions = computed(() => [
  { value: '', label: t('dashboard_analytics.filter_fleet') },
  { value: 'internal', label: t('dashboard_analytics.fleet_internal') },
  { value: 'vendor_hire', label: t('dashboard_analytics.fleet_vendor_hire') },
  { value: 'taxi', label: t('dashboard_analytics.fleet_taxi') },
  { value: 'unspecified', label: t('dashboard_analytics.fleet_unspecified') },
])

function onKpiQuickFilter(payload) {
  const { kind, value } = payload
  if (kind === 'reset') {
    filters.trip_type = ''
    filters.run_bucket = ''
    filters.status = ''
  } else if (kind === 'trip_type') {
    filters.run_bucket = ''
    filters.status = ''
    filters.trip_type = filters.trip_type === value ? '' : value
  } else if (kind === 'run') {
    filters.trip_type = ''
    filters.status = ''
    filters.run_bucket = filters.run_bucket === value ? '' : value
  }
  onFilterChange()
}

function onTripTypeFilterChange() {
  filters.run_bucket = ''
  onFilterChange()
}

function onRunBucketSelect(raw) {
  filters.run_bucket = raw || ''
  filters.status = ''
  onFilterChange()
}

function onUrgentSelect(raw) {
  filters.is_urgent = raw === '1'
  onFilterChange()
}

const canAssignTrip = computed(() => auth.hasPermission('trip.assign'))

const pageFrom = computed(() => {
  const total = meta.value.total ?? 0
  if (total <= 0) return 0
  const cur = meta.value.current_page ?? 1
  const pp = meta.value.per_page ?? filters.per_page
  return (cur - 1) * pp + 1
})

const pageTo = computed(() => {
  const total = meta.value.total ?? 0
  if (total <= 0) return 0
  const cur = meta.value.current_page ?? 1
  const pp = meta.value.per_page ?? filters.per_page
  return Math.min(cur * pp, total)
})

const pageNumbers = computed(() => {
  const last = meta.value.last_page ?? 1
  const cur = meta.value.current_page ?? 1
  const delta = 2
  const start = Math.max(1, cur - delta)
  const end = Math.min(last, cur + delta)
  const pages = []
  for (let i = start; i <= end; i++) pages.push(i)
  return pages
})

function tripCode(id) {
  return `TRP-${String(id).padStart(4, '0')}`
}

function dateLocaleKey() {
  return locale.value === 'en' ? 'en' : 'vi'
}

function fmtTripDateTime(v) {
  if (v == null || v === '') return t('trips_page.empty_datetime')
  const out = formatListDateTime(v, dateLocaleKey())
  return out || t('trips_page.empty_datetime')
}

function fmtDepartAt(v) {
  if (v == null || v === '') return t('trips_page.empty_depart_at')
  const out = formatListDateTime(v, dateLocaleKey())
  return out || t('trips_page.empty_depart_at')
}

function fmtArriveBy(v) {
  if (v == null || v === '') return t('trips_page.no_eta')
  const out = formatListDateTime(v, dateLocaleKey())
  return out || t('trips_page.no_eta')
}

function displayPlace(value, emptyKey) {
  const s = value != null ? String(value).trim() : ''
  return s || t(`trips_page.${emptyKey}`)
}

function tripTypeLabel(tt) {
  if (!tt) return t('trips_page.empty_trip_type')
  return labelTripType(tt)
}

function tripTypeBadgeClass(tt) {
  if (tt === 'door_to_door') return 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-200'
  if (tt === 'point_to_point') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200'
  if (tt === 'business') return 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-100'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
}

function tripTypeIconWrap(tt) {
  if (tt === 'door_to_door') return 'bg-sky-100 dark:bg-sky-950/40'
  if (tt === 'point_to_point') return 'bg-emerald-100 dark:bg-emerald-950/40'
  if (tt === 'business') return 'bg-amber-100 dark:bg-amber-950/40'
  return 'bg-slate-100 dark:bg-slate-800/60'
}

function statusPillClass(s) {
  return tripStatusAdminPillClass(s)
}

function paymentLabel(ps) {
  const k = ps && ['unpaid', 'pending', 'paid'].includes(ps) ? ps : 'unpaid'
  return t(`trips_page.payment_${k}`)
}

function driverInitials(name) {
  if (!name) return '?'
  const p = String(name).trim().split(/\s+/)
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

function passengerMetaSummary(trip) {
  const n = dispatchRequestEffectivePassengerCount(trip.dispatch_request)
  if (n > 0) {
    return t('trips_page.meta_passengers', { n })
  }
  return t('requests_page.no_passenger_info')
}

function distanceSummary(trip) {
  const km = trip.record?.distance_km
  if (km != null && Number(km) > 0) {
    return t('trips_page.meta_km', { n: Math.round(Number(km) * 10) / 10 })
  }
  return t('trips_page.empty_distance')
}

function durationSummary(trip) {
  const a = trip.depart_at ? new Date(trip.depart_at).getTime() : null
  const bRaw = trip.arrive_by || trip.dispatch_request?.arrive_by
  const b = bRaw ? new Date(bRaw).getTime() : null
  if (!a || !b || b <= a) return t('trips_page.empty_duration')
  const mins = Math.round((b - a) / 60000)
  const h = Math.floor(mins / 60)
  const m = mins % 60
  if (h && m) return t('trips_page.meta_duration_hm', { h, m })
  if (h) return t('trips_page.meta_duration_h', { h })
  return t('trips_page.meta_duration_m', { m })
}

function mapsHref(trip) {
  const o = trip.dispatch_request?.origin
  const d = trip.dispatch_request?.destination
  if (!o || !d) return ''
  const u = new URL('https://www.google.com/maps/dir/')
  u.searchParams.set('api', '1')
  u.searchParams.set('origin', o)
  u.searchParams.set('destination', d)
  return u.toString()
}

function needsAssign(trip) {
  return !trip.driver_id && !['completed', 'cancelled'].includes(trip.status)
}

function applyStatusFromRoute() {
  const s = route.query.status
  if (typeof s === 'string' && s) {
    filters.status = s
    filters.run_bucket = ''
  } else if (!route.query.status) {
    filters.status = ''
  }
}

function listParams() {
  const p = {
    exclude_trip_type: 'cargo',
    trip_type: filters.trip_type || undefined,
    run_bucket: filters.run_bucket || undefined,
    status: filters.run_bucket ? undefined : filters.status || undefined,
    source_channel: filters.source_channel || undefined,
    paper_status: filters.paper_status || undefined,
    fleet_mode: filters.fleet_mode || undefined,
    from: filters.from || undefined,
    to: filters.to || undefined,
    q: filters.q || undefined,
    page: filters.page,
    per_page: filters.per_page,
  }
  if (filters.is_urgent) p.is_urgent = 1
  Object.keys(p).forEach((k) => {
    if (p[k] === '' || p[k] === undefined || p[k] === null) delete p[k]
  })
  return p
}

function statsParams() {
  const p = listParams()
  delete p.trip_type
  delete p.run_bucket
  delete p.page
  delete p.per_page
  return p
}

async function reloadStats() {
  if (!rangeValid.value) return
  statsLoading.value = true
  try {
    stats.value = await getTripStats(statsParams())
  } catch {
    stats.value = {
      total: 0,
      by_trip_type: {},
      incident: 0,
      by_run: { awaiting_dispatch: 0, in_progress: 0, completed: 0, incident: 0 },
    }
  } finally {
    statsLoading.value = false
  }
}

async function reload() {
  if (!rangeValid.value) return
  loading.value = true
  try {
    const res = await listTrips(listParams())
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
  } finally {
    loading.value = false
  }
}

function onFilterChange() {
  filters.page = 1
  reloadStats()
  reload()
}

function resetFilters() {
  filters.status = ''
  filters.run_bucket = ''
  filters.source_channel = ''
  filters.paper_status = ''
  filters.is_urgent = false
  filters.fleet_mode = ''
  filters.trip_type = ''
  filters.from = ''
  filters.to = ''
  filters.q = ''
  searchInput.value = ''
  filters.per_page = 20
  filters.page = 1
  closeFilterPanel()
  applyStatusFromRoute()
  reloadStats()
  reload()
}

function goPage(n) {
  const last = meta.value.last_page ?? 1
  if (n < 1 || n > last) return
  filters.page = n
  reload()
}

function flushSearch() {
  filters.q = searchInput.value.trim()
  onFilterChange()
}

watch(searchInput, () => {
  clearTimeout(searchDebounce.value)
  searchDebounce.value = setTimeout(() => {
    const next = searchInput.value.trim()
    if (next !== filters.q) {
      filters.q = next
      onFilterChange()
    }
  }, 350)
})

const { start: startTripsListPoll } = useVisiblePoll(
  () => {
    void reload()
    void reloadStats()
  },
  { intervalMs: 55_000 },
)

watch(
  () => route.query.status,
  () => {
    applyStatusFromRoute()
    filters.page = 1
    reloadStats()
    reload()
  },
)

onMounted(() => {
  applyStatusFromRoute()
  reloadStats()
  reload()
  startTripsListPoll()
})
</script>
