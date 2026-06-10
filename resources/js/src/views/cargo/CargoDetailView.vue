<template>
  <div class="space-y-5 pb-10 print:pb-0">
    <div
      v-if="loadError"
      role="alert"
      aria-live="assertive"
      class="flex flex-col gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200 sm:flex-row sm:items-center sm:justify-between"
    >
      <span>{{ loadError }}</span>
      <button
        type="button"
        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-rose-300 bg-white px-3 py-2 text-sm font-medium text-rose-900 shadow-sm transition hover:bg-rose-50 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-100 dark:hover:bg-rose-900"
        @click="load"
      >
        <ArrowPathIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
        {{ t('cargo_detail.retry') }}
      </button>
    </div>

    <div v-else-if="loading" class="space-y-5 animate-pulse" aria-busy="true">
      <div class="h-10 w-48 rounded-lg bg-slate-200 dark:bg-slate-700" />
      <div class="h-36 rounded-2xl bg-slate-200 dark:bg-slate-700" />
      <div class="grid gap-5 lg:grid-cols-5">
        <div class="space-y-5 lg:col-span-3">
          <div class="h-64 rounded-2xl bg-slate-200 dark:bg-slate-700" />
          <div class="h-56 rounded-2xl bg-slate-200 dark:bg-slate-700" />
          <div class="h-48 rounded-2xl bg-slate-200 dark:bg-slate-700" />
        </div>
        <div class="space-y-5 lg:col-span-2">
          <div class="h-40 rounded-2xl bg-slate-200 dark:bg-slate-700" />
          <div class="h-32 rounded-2xl bg-slate-200 dark:bg-slate-700" />
          <div class="h-44 rounded-2xl bg-slate-200 dark:bg-slate-700" />
        </div>
      </div>
    </div>

    <template v-else-if="shipment">
      <!-- Header -->
      <div
        class="sticky top-0 z-30 -mx-1 flex flex-col gap-3 border-b border-slate-200/80 bg-slate-50/95 px-1 py-3 backdrop-blur-md dark:border-slate-700/80 dark:bg-slate-950/90 sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0 sm:backdrop-blur-none print:static print:border-0 print:bg-transparent"
      >
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
          <div class="min-w-0">
            <RouterLink
              to="/cargo"
              class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:text-teal-900 dark:text-teal-400 dark:hover:text-teal-200 print:hidden"
            >
              <ChevronLeftIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ t('cargo_detail.back_list') }}
            </RouterLink>
            <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
              {{ t('cargo_detail.hero_title', { code: displayCode }) }}
            </h1>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
              <span class="font-medium text-slate-800 dark:text-slate-200">{{ routeSummary }}</span>
              <span v-if="departHint" class="text-slate-500"> · {{ departHint }}</span>
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2 print:hidden">
            <RouterLink
              v-if="requestId"
              :to="'/requests/' + requestId"
              class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
            >
              <DocumentTextIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" aria-hidden="true" />
              {{ t('cargo_detail.btn_request') }}
            </RouterLink>
            <RouterLink
              v-if="shipment.trip_id"
              :to="'/trips/' + shipment.trip_id"
              class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
            >
              <TruckIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" aria-hidden="true" />
              {{ t('cargo_detail.btn_trip') }}
            </RouterLink>
            <a
              v-if="mapsHref"
              :href="mapsHref"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700"
            >
              <MapPinIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
              {{ t('cargo_detail.btn_maps') }}
            </a>
          </div>
        </div>
      </div>

      <!-- Status summary -->
      <div
        class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
      >
        <div class="flex flex-col gap-4 px-4 py-5 sm:px-6 sm:py-6 lg:flex-row lg:items-center lg:justify-between">
          <div class="flex items-start gap-3">
            <div
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-100 dark:bg-teal-950/80"
            >
              <TruckIcon class="h-7 w-7 text-teal-700 dark:text-teal-300" aria-hidden="true" />
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ t('cargo_detail.status_label') }}
              </p>
              <p class="mt-0.5 text-xl font-bold text-slate-900 dark:text-white sm:text-2xl">
                {{ labelCargoStatus(shipment.status) }}
              </p>
              <p v-if="slaLine" class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ slaLine }}</p>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-3 sm:gap-4 lg:min-w-[300px]">
            <div class="rounded-xl border border-slate-100 bg-slate-50/90 px-3 py-2 text-center dark:border-slate-700 dark:bg-slate-800/50 sm:px-4">
              <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ t('cargo_detail.hero_qty') }}
              </p>
              <p class="mt-0.5 text-lg font-bold tabular-nums text-slate-900 dark:text-white sm:text-xl">
                {{ shipment.quantity ?? '—' }}
              </p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50/90 px-3 py-2 text-center dark:border-slate-700 dark:bg-slate-800/50 sm:px-4">
              <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ t('cargo_detail.hero_weight') }}
              </p>
              <p class="mt-0.5 text-lg font-bold tabular-nums text-slate-900 dark:text-white sm:text-xl">
                {{ weightLabel }}
              </p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50/90 px-3 py-2 text-center dark:border-slate-700 dark:bg-slate-800/50 sm:px-4">
              <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ t('cargo_detail.hero_progress') }}
              </p>
              <p class="mt-0.5 text-lg font-bold tabular-nums text-slate-900 dark:text-white sm:text-xl">{{ progressPct }}%</p>
            </div>
          </div>
        </div>
        <div class="border-t border-slate-100 px-4 pb-4 dark:border-slate-800 sm:px-6 sm:pb-5">
          <div class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
            <div
              class="h-full rounded-full bg-teal-500 transition-all dark:bg-teal-600"
              :style="{ width: progressPct + '%' }"
            />
          </div>
          <p v-if="slaBreached" class="mt-3 flex items-center gap-2 rounded-lg bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900 dark:bg-amber-950/50 dark:text-amber-100">
            <ExclamationTriangleIcon class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" aria-hidden="true" />
            {{ t('cargo_detail.sla_breached') }}
          </p>
        </div>
      </div>

      <!-- Quick status: one horizontal row -->
      <div
        v-if="quickStatusActions.length"
        class="print:hidden overflow-hidden rounded-2xl border border-violet-200/80 bg-gradient-to-r from-violet-50/90 via-white to-slate-50/80 shadow-sm dark:border-violet-900/40 dark:from-violet-950/30 dark:via-slate-900/50 dark:to-slate-900/40"
      >
        <div class="flex flex-col gap-2 border-b border-violet-100/80 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-violet-900/30">
          <div class="min-w-0 shrink-0">
            <p class="text-[11px] font-bold uppercase tracking-wide text-violet-800 dark:text-violet-300">{{ t('cargo_detail.quick_status_title') }}</p>
            <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">{{ t('cargo_detail.quick_status_hint') }}</p>
          </div>
          <div
            class="flex min-w-0 flex-1 items-stretch justify-start gap-2 sm:justify-end overflow-x-auto pb-0.5 [-ms-overflow-style:none] [scrollbar-width:none] sm:max-w-[70%] [&::-webkit-scrollbar]:hidden"
          >
            <button
              v-for="act in quickStatusActions"
              :key="act.status"
              type="button"
              :disabled="statusSaving"
              :class="[
                'shrink-0 rounded-full px-4 py-2.5 text-sm font-semibold shadow-sm transition disabled:opacity-50',
                act.danger
                  ? 'border border-rose-200/90 bg-white text-rose-700 hover:bg-rose-50 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-200 dark:hover:bg-rose-950/80'
                  : 'border border-teal-200/90 bg-teal-600 text-white hover:bg-teal-700 dark:border-teal-700 dark:bg-teal-600 dark:hover:bg-teal-500',
              ]"
              @click="applyQuickStatus(act.status, act.danger)"
            >
              {{ quickStatusLabel(act.status) }}
            </button>
          </div>
        </div>
        <p v-if="statusError" class="border-t border-violet-100/60 px-4 py-2.5 text-sm font-medium text-rose-600 dark:border-violet-900/25 dark:text-rose-400">
          {{ statusError }}
        </p>
      </div>

      <div class="grid gap-5 lg:grid-cols-5">
        <div class="min-w-0 space-y-5 lg:col-span-3">
          <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
            <div class="border-b border-slate-200/90 px-4 py-3 dark:border-slate-700 sm:px-5">
              <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                <MapPinIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-400" aria-hidden="true" />
                {{ t('cargo_detail.card_route') }}
              </h2>
            </div>
            <div class="px-4 py-5 sm:px-5">
              <div class="relative space-y-6 pl-2">
                <div class="absolute bottom-3 left-[15px] top-3 w-0.5 bg-slate-200 dark:bg-slate-600" aria-hidden="true" />
                <div class="relative flex gap-4">
                  <div
                    class="relative z-[1] flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-white bg-emerald-500 text-white shadow dark:border-slate-900"
                  >
                    <CheckCircleIcon v-if="pickupDone" class="h-5 w-5" aria-hidden="true" />
                    <span v-else class="h-2.5 w-2.5 rounded-full bg-white/90" />
                  </div>
                  <div class="min-w-0 flex-1 pt-0.5">
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">{{
                        t('cargo_detail.route_pickup')
                      }}</span>
                      <span
                        v-if="pickupDone"
                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-100"
                      >
                        {{ t('cargo_detail.route_done') }}
                      </span>
                      <span
                        v-else
                        class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                      >
                        {{ t('cargo_detail.route_pending') }}
                      </span>
                    </div>
                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ shipment.pickup_address || '—' }}</p>
                    <p v-if="shipment.sender_name" class="text-xs text-slate-600 dark:text-slate-400">{{ shipment.sender_name }}</p>
                    <p v-if="shipment.picked_up_at" class="mt-1 text-xs tabular-nums text-slate-500">{{ fmt(shipment.picked_up_at) }}</p>
                  </div>
                </div>
                <div class="relative flex gap-4">
                  <div
                    class="relative z-[1] flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-white bg-rose-500 text-white shadow dark:border-slate-900"
                  >
                    <TruckIcon v-if="inMotion" class="h-4 w-4" aria-hidden="true" />
                    <CheckCircleIcon v-else-if="deliveredDone" class="h-5 w-5" aria-hidden="true" />
                    <span v-else class="h-2.5 w-2.5 rounded-full bg-white/90" />
                  </div>
                  <div class="min-w-0 flex-1 pt-0.5">
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="text-xs font-bold uppercase tracking-wide text-rose-700 dark:text-rose-400">{{
                        t('cargo_detail.route_delivery')
                      }}</span>
                      <span
                        v-if="deliveredDone"
                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-100"
                      >
                        {{ t('cargo_detail.route_delivered') }}
                      </span>
                      <span
                        v-else-if="inMotion"
                        class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-900 dark:bg-sky-950/50 dark:text-sky-100"
                      >
                        {{ t('cargo_detail.route_moving') }}
                      </span>
                      <span
                        v-else
                        class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                      >
                        {{ t('cargo_detail.route_awaiting') }}
                      </span>
                    </div>
                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ shipment.delivery_address || '—' }}</p>
                    <p v-if="shipment.receiver_name" class="text-xs text-slate-600 dark:text-slate-400">{{ shipment.receiver_name }}</p>
                    <p v-if="shipment.delivered_at" class="mt-1 text-xs tabular-nums text-slate-500">{{ fmt(shipment.delivered_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
            <div class="border-b border-slate-200/90 px-4 py-3 dark:border-slate-700 sm:px-5">
              <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                <MapIcon class="h-5 w-5 text-sky-600 dark:text-sky-400" aria-hidden="true" />
                {{ t('cargo_detail.map_section_title') }}
              </h2>
            </div>
            <div class="relative w-full overflow-hidden bg-slate-50 dark:bg-slate-800/40">
              <div class="relative aspect-[16/10] w-full min-h-[200px] bg-gradient-to-br from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-900 sm:aspect-[21/9]">
                <iframe
                  v-if="embedMapSrc"
                  :src="embedMapSrc"
                  class="absolute inset-0 h-full w-full border-0"
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                  :title="t('cargo_detail.map_iframe_title')"
                />
                <div
                  v-else
                  class="flex h-full min-h-[200px] flex-col items-center justify-center gap-2 px-4 text-center text-sm text-slate-500 dark:text-slate-400"
                >
                  <MapPinIcon class="h-10 w-10 opacity-50" aria-hidden="true" />
                  {{ t('cargo_detail.map_no_embed') }}
                </div>
              </div>
              <button
                v-if="embedMapSrc"
                type="button"
                class="absolute right-2 top-2 rounded-lg border border-slate-200/80 bg-white/95 px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:bg-white print:hidden dark:border-slate-600 dark:bg-slate-900/95 dark:text-slate-200 dark:hover:bg-slate-800"
                @click="mapExpanded = true"
              >
                {{ t('cargo_detail.map_expand') }}
              </button>
            </div>
            <p class="border-t border-slate-100 px-4 py-2 text-[11px] text-slate-500 dark:border-slate-800 dark:text-slate-400">
              {{ t('cargo_detail.map_hint') }}
            </p>
          </div>

          <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
            <div class="flex flex-col gap-3 border-b border-slate-200/90 px-4 py-3 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between sm:px-5">
              <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                <ClockIcon class="h-5 w-5 text-violet-600 dark:text-violet-400" aria-hidden="true" />
                {{ t('cargo_detail.card_timeline') }}
              </h2>
              <select
                v-model="timelineFilter"
                class="max-w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs font-medium text-slate-800 shadow-sm outline-none ring-teal-500/30 focus:ring-2 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
              >
                <option value="all">{{ t('cargo_detail.timeline_filter_all') }}</option>
                <option value="milestone">{{ t('cargo_detail.timeline_filter_milestone') }}</option>
                <option value="audit">{{ t('cargo_detail.timeline_filter_audit') }}</option>
              </select>
            </div>
            <div class="px-4 py-4 sm:px-5">
              <div v-if="timelineLoading" class="text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_page.timeline_loading') }}</div>
              <ul v-else-if="timelineFiltered.length" class="relative ml-1 space-y-4 border-l-2 border-slate-200 pl-5 dark:border-slate-600">
                <li v-for="(ev, idx) in timelineFiltered" :key="idx + '-' + (ev.at || '') + '-' + (ev.kind || '') + '-' + (ev.code || '')" class="relative">
                  <span
                    class="absolute -left-[calc(1.25rem+5px)] top-1.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-teal-600 dark:border-slate-900"
                  />
                  <div class="flex flex-wrap justify-between gap-2 text-xs">
                    <span class="font-medium text-slate-800 dark:text-slate-200">{{ timelineTitle(ev) }}</span>
                    <span class="tabular-nums text-slate-500 dark:text-slate-400">{{ fmt(ev.at) }}</span>
                  </div>
                  <p v-if="timelineSubtitle(ev)" class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">{{ timelineSubtitle(ev) }}</p>
                  <p v-if="ev.actor?.name" class="mt-0.5 text-[11px] text-slate-500">{{ ev.actor.name }}</p>
                </li>
              </ul>
              <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_page.timeline_empty') }}</p>
            </div>
          </div>
        </div>

        <aside class="min-w-0 space-y-5 lg:col-span-2">
          <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
            <div class="border-b border-slate-200/90 px-4 py-3 dark:border-slate-700">
              <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                <UserIcon class="h-5 w-5 text-teal-600 dark:text-teal-400" aria-hidden="true" />
                {{ t('cargo_detail.card_fleet') }}
              </h2>
            </div>
            <div class="space-y-3 px-4 py-4">
              <template v-if="tripLite?.driver">
                <div class="flex gap-3">
                  <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-bold text-teal-800 dark:bg-teal-950 dark:text-teal-200"
                  >
                    {{ driverInitials(tripLite.driver.full_name) }}
                  </div>
                  <div class="min-w-0">
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ tripLite.driver.full_name }}</p>
                    <p v-if="tripLite.driver.phone" class="text-xs tabular-nums text-slate-600 dark:text-slate-400">{{ tripLite.driver.phone }}</p>
                  </div>
                </div>
              </template>
              <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_detail.fleet_unassigned') }}</p>
              <ul class="space-y-2 border-t border-slate-100 pt-3 text-sm dark:border-slate-800">
                <li v-if="tripLite?.vehicle" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('cargo_detail.fleet_plate') }}</span>
                  <span class="font-mono font-medium text-slate-900 dark:text-slate-100">{{ tripLite.vehicle.license_plate }}</span>
                </li>
                <li v-if="tripLite?.transport_provider" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('cargo_detail.fleet_provider') }}</span>
                  <span class="text-right font-medium text-slate-900 dark:text-slate-100">{{ tripLite.transport_provider.name }}</span>
                </li>
                <li v-if="tripLite?.dispatcher" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('cargo_detail.fleet_dispatcher') }}</span>
                  <span class="text-right text-slate-800 dark:text-slate-200">{{ tripLite.dispatcher.name }}</span>
                </li>
              </ul>
            </div>
          </div>

          <div
            v-if="canAssignResources"
            class="overflow-hidden rounded-2xl border border-sky-200/80 bg-gradient-to-b from-sky-50/40 to-white shadow-sm dark:border-sky-900/40 dark:from-sky-950/20 dark:to-slate-900/40"
          >
            <div class="border-b border-sky-100/90 px-4 py-3 dark:border-sky-900/40">
              <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ t('cargo_detail.assign_title') }}</h2>
              <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">{{ t('cargo_detail.assign_hint') }}</p>
              <p v-if="assignScheduleHint" class="mt-2 rounded-lg border border-slate-200/80 bg-white/80 px-2.5 py-1 text-[11px] font-medium text-slate-700 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-200">
                {{ assignScheduleHint }}
              </p>
              <p v-if="assignReadySummary" class="mt-1 text-[11px] text-slate-600 dark:text-slate-400">{{ assignReadySummary }}</p>
              <p v-if="assignBusyHint && !hireExternal" class="mt-1 text-[11px] text-amber-800 dark:text-amber-200/90">{{ assignBusyHint }}</p>
              <p v-if="sameDayTripsLoading" class="mt-1 text-[11px] text-slate-500">{{ t('trip_detail.coordination.schedule_loading') }}</p>
              <p v-if="sameDayTripsError" class="mt-1 text-[11px] text-rose-600">{{ sameDayTripsError }}</p>
            </div>
            <div class="space-y-4 px-4 py-4">
              <div
                class="rounded-xl border border-slate-200/80 bg-slate-50/40 p-3 dark:border-slate-600 dark:bg-slate-800/30"
                :class="hireExternal ? 'pointer-events-none opacity-45' : ''"
              >
                <div class="grid gap-3">
                  <Select
                    v-model="vehicleChoice"
                    :disabled="hireExternal"
                    :label="t('trip_detail.coordination.assign_vehicle')"
                    :placeholder="t('trip_detail.ops.form.pick_vehicle')"
                  >
                    <option value="">{{ t('trip_detail.ops.form.keep_or_clear') }}</option>
                    <option
                      v-for="v in sortedAssignVehicles"
                      :key="v.id"
                      :value="String(v.id)"
                      :disabled="isAssignVehicleBusy(v.id)"
                      :title="assignVehicleOptionTitle(v.id)"
                    >
                      {{ assignVehicleOptionLabel(v) }}
                    </option>
                  </Select>
                  <Select
                    v-model="driverChoice"
                    :disabled="hireExternal"
                    :label="t('trip_detail.coordination.assign_driver')"
                    :placeholder="t('trip_detail.ops.form.pick_driver')"
                  >
                    <option value="">{{ t('trip_detail.ops.form.keep_or_clear') }}</option>
                    <option
                      v-for="d in sortedAssignDrivers"
                      :key="d.id"
                      :value="String(d.id)"
                      :disabled="isAssignDriverBusy(d.id)"
                      :title="assignDriverOptionTitle(d.id)"
                    >
                      {{ assignDriverOptionLabel(d) }}
                    </option>
                  </Select>
                </div>
                <p v-if="assignSuitableHint" class="mt-2 text-xs font-medium text-emerald-800 dark:text-emerald-300">{{ assignSuitableHint }}</p>
                <p v-if="assignVehicleSeatsWarning" class="mt-2 text-xs font-medium text-rose-700 dark:text-rose-400">{{ assignVehicleSeatsWarning }}</p>
              </div>

              <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900">
                <input v-model="hireExternal" type="checkbox" class="rounded border-slate-300 text-sky-600" />
                {{ t('trip_detail.coordination.hire_external') }}
              </label>
              <p v-if="hireExternal" class="-mt-2 text-[11px] text-slate-500 dark:text-slate-400">{{ t('trip_detail.coordination.external_mode_hint') }}</p>

              <div v-if="hireExternal" class="grid gap-3 rounded-xl border border-amber-200/80 bg-amber-50/50 p-3 dark:border-amber-900/40 dark:bg-amber-950/20">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                  <div class="min-w-0 flex-1">
                    <Select v-model="providerChoice" :label="t('trip_detail.coordination.provider_select')" :placeholder="t('trip_detail.coordination.provider_placeholder')">
                      <option value="">{{ t('trip_detail.coordination.provider_placeholder') }}</option>
                      <option v-for="p in transportProviders" :key="p.id" :value="String(p.id)">
                        {{ p.name }}<template v-if="p.type"> · {{ assignProviderTypeLabel(p.type) }}</template>
                      </option>
                    </Select>
                  </div>
                  <Button
                    v-if="canQuickCreateProvider"
                    type="button"
                    variant="secondary"
                    class="h-10 w-full shrink-0 sm:h-auto sm:w-auto sm:self-end"
                    @click="openAssignProviderModal"
                  >
                    {{ t('trip_detail.coordination.provider_quick_add') }}
                  </Button>
                </div>
                <Input v-model="externalVehicleRef" :label="t('trip_detail.coordination.external_vehicle')" :placeholder="t('trip_detail.coordination.external_vehicle_ph')" />
                <Input v-model="externalDriverRef" :label="t('trip_detail.coordination.external_driver')" :placeholder="t('trip_detail.coordination.external_driver_ph')" />
              </div>

              <div>
                <label class="mb-1 block text-xs font-medium text-slate-700 dark:text-slate-300">{{ t('trip_detail.coordination.internal_notes') }}</label>
                <textarea
                  v-model="assignCoordinationNotes"
                  rows="2"
                  class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                  :placeholder="t('trip_detail.coordination.internal_notes_ph')"
                />
              </div>

              <p v-if="assignResourceHint" class="text-xs text-amber-900 dark:text-amber-200/90">{{ assignResourceHint }}</p>
              <div
                v-if="assignMsg"
                class="rounded-lg border px-3 py-2 text-sm font-medium"
                :class="
                  assignFeedbackKind === 'success'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-100'
                    : 'border-rose-200 bg-rose-50 text-rose-950 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-100'
                "
              >
                {{ assignMsg }}
              </div>

              <Button
                type="button"
                class="w-full !bg-sky-600 font-semibold hover:!bg-sky-700 disabled:opacity-60"
                :loading="assignSaving"
                :disabled="assignSaving || !assignFormReady"
                :title="!assignFormReady ? t('trip_detail.coordination.assign_disabled_hint') : ''"
                @click="submitCargoTripAssign"
              >
                {{ t('cargo_detail.assign_submit') }}
              </Button>
            </div>
          </div>
          <p v-else-if="shipment.trip_id && !tripForAssign && assignLoadError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-200">
            {{ assignLoadError }}
          </p>
          <p v-else-if="shipment.trip_id && !canAssignTrip" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-400">
            {{ t('cargo_detail.assign_no_permission') }}
          </p>
          <p v-else-if="!shipment.trip_id" class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
            {{ t('cargo_detail.assign_no_trip') }}
          </p>

          <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
            <div class="border-b border-slate-200/90 px-4 py-3 dark:border-slate-700">
              <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                <CurrencyDollarIcon class="h-5 w-5 text-teal-600 dark:text-teal-400" aria-hidden="true" />
                {{ t('cargo_detail.card_costs') }}
              </h2>
            </div>
            <div class="px-4 py-4">
              <p v-if="costsForbidden" class="text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_detail.costs_no_permission') }}</p>
              <p v-else-if="costsLoading" class="text-sm text-slate-500">{{ t('cargo_detail.costs_loading') }}</p>
              <template v-else-if="costItems.length">
                <ul class="space-y-2">
                  <li v-for="c in costItems" :key="c.id" class="flex justify-between gap-3 text-sm">
                    <span class="min-w-0 text-slate-600 dark:text-slate-400">
                      {{ c.description?.trim() || c.type || t('cargo_detail.cost_line') }}
                      <span v-if="c.status" class="ml-1 text-[11px] text-slate-400">({{ c.status }})</span>
                    </span>
                    <span class="shrink-0 tabular-nums font-medium text-slate-900 dark:text-slate-100">{{ formatMoney(c.amount, c.currency) }}</span>
                  </li>
                </ul>
                <div class="mt-4 border-t border-slate-200 pt-3 dark:border-slate-700">
                  <div class="flex justify-between text-sm font-semibold text-slate-900 dark:text-slate-100">
                    <span>{{ t('cargo_detail.costs_total') }}</span>
                    <span class="tabular-nums text-teal-700 dark:text-teal-400">{{ formatMoney(costsTotal) }}</span>
                  </div>
                </div>
              </template>
              <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_detail.costs_empty') }}</p>
            </div>
          </div>

          <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
            <div class="border-b border-slate-200/90 px-4 py-3 dark:border-slate-700">
              <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                <FolderIcon class="h-5 w-5 text-violet-600 dark:text-violet-400" aria-hidden="true" />
                {{ t('cargo_detail.card_docs') }}
              </h2>
            </div>
            <div class="px-4 py-4">
              <ul v-if="(shipment.attachments ?? []).length" class="space-y-2">
                <li
                  v-for="att in shipment.attachments"
                  :key="att.id"
                  class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/40"
                >
                  <DocumentIcon class="h-8 w-8 shrink-0 text-slate-500" aria-hidden="true" />
                  <div class="min-w-0 flex-1">
                    <a
                      v-if="att.url"
                      :href="att.url"
                      target="_blank"
                      rel="noopener"
                      class="block truncate text-sm font-medium text-teal-700 underline hover:text-teal-900 dark:text-teal-400"
                    >
                      {{ att.original_name || t('cargo_page.open_pod') }}
                    </a>
                    <span v-else class="block truncate text-sm text-slate-700 dark:text-slate-300">{{ att.original_name }}</span>
                    <span class="text-[11px] text-slate-500">{{ formatBytes(att.size_bytes) }}</span>
                  </div>
                  <a
                    v-if="att.url"
                    :href="att.url"
                    target="_blank"
                    rel="noopener"
                    class="shrink-0 rounded-lg p-2 text-slate-500 hover:bg-white hover:text-teal-700 dark:hover:bg-slate-700 dark:hover:text-teal-300"
                    :aria-label="t('cargo_detail.doc_download')"
                  >
                    <ArrowDownTrayIcon class="h-5 w-5" aria-hidden="true" />
                  </a>
                </li>
              </ul>
              <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_page.no_pod') }}</p>
              <FileUpload
                v-if="canUploadPod"
                :key="'pod-' + shipment.id"
                class="mt-4"
                :label="t('cargo_page.upload_pod')"
                :hint="t('cargo_page.upload_pod_hint')"
                :upload-fn="(file, onProgress) => uploadCargoPod(shipment.id, file, onProgress)"
                @uploaded="reloadShipment"
              />
            </div>
          </div>
        </aside>
      </div>
    </template>

    <Teleport to="body">
      <div
        v-if="mapExpanded"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm print:hidden"
        role="dialog"
        aria-modal="true"
        @click.self="mapExpanded = false"
      >
        <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900">
          <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-700">
            <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ t('cargo_detail.map_modal_title') }}</span>
            <button
              type="button"
              class="rounded-lg px-2 py-1 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
              @click="mapExpanded = false"
            >
              {{ t('cargo_detail.map_modal_close') }}
            </button>
          </div>
          <div class="relative min-h-[60vh] flex-1 bg-slate-100 dark:bg-slate-800">
            <iframe
              v-if="embedMapSrc"
              :src="embedMapSrc"
              class="absolute inset-0 h-full w-full border-0"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              :title="t('cargo_detail.map_iframe_title')"
            />
          </div>
          <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-700">
            <a
              v-if="mapsHref"
              :href="mapsHref"
              target="_blank"
              rel="noopener"
              class="text-sm font-semibold text-teal-700 hover:underline dark:text-teal-400"
            >
              {{ t('cargo_detail.open_in_google_maps') }}
            </a>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="assignProviderModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        @click.self="assignProviderModalOpen = false"
      >
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-600 dark:bg-slate-900">
          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ t('trip_detail.coordination.provider_modal_title') }}</h3>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('trip_detail.coordination.provider_modal_hint') }}</p>
          <div class="mt-4 space-y-3">
            <Input v-model="newAssignProviderName" :label="t('trip_detail.coordination.provider_modal_name')" :placeholder="t('trip_detail.coordination.provider_modal_name_ph')" />
            <Select v-model="newAssignProviderType" :label="t('trip_detail.coordination.provider_modal_type')">
              <option value="taxi">{{ t('resources.provider_form_type_taxi') }}</option>
              <option value="vendor">{{ t('resources.provider_form_type_vendor') }}</option>
            </Select>
            <p v-if="assignProviderModalError" class="text-xs text-rose-600">{{ assignProviderModalError }}</p>
          </div>
          <div class="mt-5 flex justify-end gap-2">
            <Button type="button" variant="secondary" @click="assignProviderModalOpen = false">{{ t('trip_detail.coordination.provider_modal_cancel') }}</Button>
            <Button type="button" :loading="assignProviderCreating" @click="submitQuickAssignProvider">{{ t('trip_detail.coordination.provider_modal_save') }}</Button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  ChevronLeftIcon,
  ClockIcon,
  CurrencyDollarIcon,
  DocumentIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  FolderIcon,
  MapIcon,
  MapPinIcon,
  TruckIcon,
  UserIcon,
} from '@heroicons/vue/24/outline'
import FileUpload from '../../components/ui/FileUpload.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { uploadCargoPod } from '../../api/attachments'
import { listCostsForTrip } from '../../api/costs'
import { getCargoShipment, getCargoShipmentTimeline, updateCargoStatus } from '../../api/cargo'
import { addTripEvent, assignTrip, getTrip, listTrips } from '../../api/trips'
import { listVehicles, listDrivers, listTransportProviders, createTransportProvider } from '../../api/operational'
import { newIdempotencyKey } from '../../util/idempotency'
import {
  withTripLockVersion,
  runWithOptimisticLockRetry,
  isOptimisticLockConflict,
  patchTripFromApi,
} from '../../util/tripLock'
import { labelCargoStatus } from '../../util/labels'
import { useAuthStore } from '../../store'
import { useNotificationStore } from '../../store/notificationCenter'
import { i18n } from '../../i18n'
import {
  collectBusyDriverIds,
  collectBusyVehicleIds,
  tripPlannedEndMs,
} from '../../util/tripScheduleConflict'

const route = useRoute()
const { t, te, locale } = useI18n()
const auth = useAuthStore()
const notifStore = useNotificationStore()

const loading = ref(true)
const loadError = ref('')
const shipment = ref(null)
const timelineItems = ref([])
const timelineLoading = ref(true)
const timelineFilter = ref('all')
const costItems = ref([])
const costsLoading = ref(false)
const costsForbidden = ref(false)
const mapExpanded = ref(false)
const statusSaving = ref(false)
const statusError = ref('')

const TRIP_ASSIGN_CONFLICT_STATUSES = ['assigned', 'driver_confirmed', 'in_progress']
const canAssignTrip = computed(() => auth.hasPermission('trip.assign'))
const canQuickCreateProvider = computed(() => auth.hasPermission('resource.provider.manage'))

const tripForAssign = ref(null)
const assignLoadError = ref('')
const assignVehicles = ref([])
const assignDrivers = ref([])
const transportProviders = ref([])
const sameDayTrips = ref([])
const sameDayTripsLoading = ref(false)
const sameDayTripsError = ref('')
const assignResourceHint = ref('')
const vehicleChoice = ref('')
const driverChoice = ref('')
const hireExternal = ref(false)
const providerChoice = ref('')
const externalVehicleRef = ref('')
const externalDriverRef = ref('')
const assignCoordinationNotes = ref('')
const suppressAssignSync = ref(false)
const needsAssignVehicleResync = ref(false)
const assignSaving = ref(false)
const assignMsg = ref('')
const assignFeedbackKind = ref('')
const assignProviderModalOpen = ref(false)
const newAssignProviderName = ref('')
const newAssignProviderType = ref('vendor')
const assignProviderModalError = ref('')
const assignProviderCreating = ref(false)

const displayCode = computed(() => shipment.value?.tracking_code || '#' + shipment.value?.id)
const requestId = computed(() => shipment.value?.dispatch_request_id ?? shipment.value?.dispatch_request?.id ?? null)
const tripLite = computed(() => shipment.value?.trip ?? null)

const routeSummary = computed(() => {
  const s = shipment.value
  if (!s) return ''
  const a = s.pickup_address || s.dispatch_request?.origin || '—'
  const b = s.delivery_address || s.dispatch_request?.destination || '—'
  return `${a} → ${b}`
})

const departHint = computed(() => {
  const d = tripLite.value?.depart_at || shipment.value?.dispatch_request?.depart_at
  if (!d) return ''
  return t('cargo_detail.depart_prefix') + ' ' + fmt(d)
})

const mapsHref = computed(() => {
  const s = shipment.value
  if (!s) return ''
  const o = s.pickup_address || s.dispatch_request?.origin
  const dest = s.delivery_address || s.dispatch_request?.destination
  if (!o || !dest) return ''
  const u = new URL('https://www.google.com/maps/dir/')
  u.searchParams.set('api', '1')
  u.searchParams.set('origin', o)
  u.searchParams.set('destination', dest)
  return u.toString()
})

const embedMapSrc = computed(() => {
  const s = shipment.value
  if (!s) return ''
  const o = (s.pickup_address || s.dispatch_request?.origin || '').trim()
  const d = (s.delivery_address || s.dispatch_request?.destination || '').trim()
  if (o && d) {
    return `https://maps.google.com/maps?q=${encodeURIComponent(`${o} → ${d}`)}&output=embed`
  }
  if (o) {
    return `https://maps.google.com/maps?q=${encodeURIComponent(o)}&output=embed`
  }
  if (d) {
    return `https://maps.google.com/maps?q=${encodeURIComponent(d)}&output=embed`
  }
  return ''
})

const weightLabel = computed(() => {
  const g = shipment.value?.weight_grams
  if (g == null || g === '') return '—'
  const kg = Number(g) / 1000
  if (!Number.isFinite(kg)) return '—'
  return kg < 1 ? `${g} g` : `${kg.toLocaleString(locale.value === 'en' ? 'en-US' : 'vi-VN', { maximumFractionDigits: 2 })} kg`
})

const progressPct = computed(() => {
  const st = shipment.value?.status
  if (st === 'delivered') return 100
  if (st === 'in_transit') return 72
  if (st === 'picked_up') return 40
  if (st === 'pending') return 15
  if (st === 'failed' || st === 'cancelled') return 0
  return 12
})

const pickupDone = computed(() => ['picked_up', 'in_transit', 'delivered'].includes(shipment.value?.status))
const deliveredDone = computed(() => shipment.value?.status === 'delivered')
const inMotion = computed(() => shipment.value?.status === 'in_transit')

const slaLine = computed(() => {
  if (!shipment.value?.sla_due_at) return ''
  return t('cargo_detail.sla_due_line', { at: fmt(shipment.value.sla_due_at) })
})

const slaBreached = computed(() => {
  if (!shipment.value?.sla_due_at || shipment.value?.status === 'delivered') return false
  return new Date(shipment.value.sla_due_at).getTime() < Date.now()
})

const canUploadPod = computed(() => auth.hasPermission('cargo.manage'))
const canManageCargo = computed(() => auth.hasPermission('cargo.manage'))

const neededSeatsAssign = computed(() => 1)

const canAssignResources = computed(() => {
  if (!canAssignTrip.value || !shipment.value?.trip_id || !tripForAssign.value) return false
  const tr = tripForAssign.value
  if (['cancelled', 'completed'].includes(tr.status)) return false
  if ((tr.payment_status ?? 'unpaid') === 'paid') return false
  return true
})

function formatApiMessage(e) {
  const d = e?.response?.data
  if (typeof d?.message === 'string' && d.message.trim()) return d.message.trim()
  if (d?.errors && typeof d.errors === 'object') {
    const vals = Object.values(d.errors)
      .flat()
      .filter(Boolean)
    if (vals.length) return String(vals[0])
  }
  return t('cargo_detail.quick_status_error')
}

function toLocalDateKey(iso) {
  if (!iso) return ''
  const x = new Date(iso)
  if (Number.isNaN(x.getTime())) return ''
  const y = x.getFullYear()
  const m = String(x.getMonth() + 1).padStart(2, '0')
  const day = String(x.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function tripPlannedEndForCargo(t) {
  return new Date(tripPlannedEndMs(t))
}

const scheduleDateKeyForAssign = computed(() => {
  if (!tripForAssign.value?.depart_at) return ''
  return toLocalDateKey(tripForAssign.value.depart_at)
})

const assignScheduleWindow = computed(() => {
  const tr = tripForAssign.value
  if (!tr?.depart_at) return null
  const start = new Date(tr.depart_at).getTime()
  const end = tripPlannedEndForCargo(tr).getTime()
  if (!Number.isFinite(start) || !Number.isFinite(end)) return null
  return { start, end }
})

const assignBusyDriverIds = computed(() =>
  collectBusyDriverIds(
    sameDayTrips.value,
    assignScheduleWindow.value,
    tripForAssign.value?.id,
    TRIP_ASSIGN_CONFLICT_STATUSES,
  ),
)

const assignBusyVehicleIds = computed(() =>
  collectBusyVehicleIds(
    sameDayTrips.value,
    assignScheduleWindow.value,
    tripForAssign.value?.id,
    TRIP_ASSIGN_CONFLICT_STATUSES,
  ),
)

function assignScheduleTimeRange(tr) {
  const a = tr?.depart_at
  const b = tr?.arrive_by ?? tr?.dispatch_request?.arrive_by
  if (!a) return '—'
  const l = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  const tStr = (iso) => (iso ? new Date(iso).toLocaleTimeString(l, { hour: '2-digit', minute: '2-digit' }) : '')
  if (!b) return tStr(a)
  return `${tStr(a)} – ${tStr(b)}`
}

const assignScheduleHint = computed(() => {
  const tr = tripForAssign.value
  if (!tr?.depart_at) return ''
  const l = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  const d = new Date(tr.depart_at).toLocaleDateString(l, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
  const r = assignScheduleTimeRange(tr)
  if (!r || r === '—') return d
  return t('trip_detail.coordination.schedule_window_hint', { date: d, range: r })
})

const assignSuitableCount = computed(() => assignVehicles.value.filter((v) => (v.seat_count ?? 0) >= neededSeatsAssign.value).length)

const assignSuitableHint = computed(() => {
  const n = assignSuitableCount.value
  if (!assignVehicles.value.length || n < 1) return ''
  return t('trip_detail.coordination.suitable_vehicles', { n })
})

const assignReadySummary = computed(() => {
  if (!tripForAssign.value) return ''
  return t('trip_detail.coordination.ready_vehicles_summary', {
    total: assignVehicles.value.length,
    fit: assignSuitableCount.value,
    need: neededSeatsAssign.value,
  })
})

const assignBusyHint = computed(() => {
  if (!assignBusyDriverIds.value.size && !assignBusyVehicleIds.value.size) return ''
  return t('trip_detail.coordination.busy_resources_hint')
})

function isAssignDriverBusy(id) {
  return assignBusyDriverIds.value.has(id)
}

function isAssignVehicleBusy(id) {
  return assignBusyVehicleIds.value.has(id)
}

function assignDriverOptionLabel(d) {
  const base = `${d.full_name}${d.phone ? ` · ${d.phone}` : ''}`
  return isAssignDriverBusy(d.id) ? `${base} — ${t('trip_detail.coordination.option_busy_suffix')}` : base
}

function assignDriverOptionTitle(id) {
  return isAssignDriverBusy(id) ? t('trip_detail.coordination.option_busy_title_driver') : ''
}

function assignVehicleOptionLabel(v) {
  const base = `${v.license_plate} · ${v.type ?? '—'}${v.seat_count ? ` (${v.seat_count})` : ''}`
  return isAssignVehicleBusy(v.id) ? `${base} — ${t('trip_detail.coordination.option_busy_suffix')}` : base
}

function assignVehicleOptionTitle(id) {
  return isAssignVehicleBusy(id) ? t('trip_detail.coordination.option_busy_title_vehicle') : ''
}

function assignProviderTypeLabel(type) {
  const t0 = String(type ?? '').toLowerCase()
  if (t0 === 'taxi') return t('resources.provider_form_type_taxi')
  if (t0 === 'vendor') return t('resources.provider_form_type_vendor')
  return type ?? '—'
}

const sortedAssignDrivers = computed(() => {
  const list = [...assignDrivers.value]
  const busy = assignBusyDriverIds.value
  const loc = locale.value === 'en' ? 'en' : 'vi'
  list.sort((a, b) => {
    const ab = busy.has(a.id) ? 1 : 0
    const bb = busy.has(b.id) ? 1 : 0
    if (ab !== bb) return ab - bb
    return (a.full_name || '').localeCompare(b.full_name || '', loc, { sensitivity: 'base' })
  })
  return list
})

const sortedAssignVehicles = computed(() => {
  const list = [...assignVehicles.value]
  const busy = assignBusyVehicleIds.value
  const need = neededSeatsAssign.value
  const score = (v) => {
    const seats = v.seat_count ?? 0
    const fit = seats >= need ? 2 : 0
    const free = busy.has(v.id) ? 0 : 1
    return fit + free
  }
  list.sort((a, b) => {
    const diff = score(b) - score(a)
    if (diff !== 0) return diff
    return (a.license_plate || '').localeCompare(b.license_plate || '', undefined, { numeric: true })
  })
  return list
})

const assignVehicleSeatsWarning = computed(() => {
  if (hireExternal.value || !vehicleChoice.value) return ''
  const v = assignVehicles.value.find((x) => String(x.id) === String(vehicleChoice.value))
  if (!v) return ''
  const n = v.seat_count ?? 0
  if (n >= neededSeatsAssign.value) return ''
  return t('trip_detail.coordination.vehicle_seats_warning', { n, need: neededSeatsAssign.value })
})

const assignFormReady = computed(() => {
  if (!canAssignResources.value) return false
  const hasExternal = hireExternal.value && providerChoice.value && String(providerChoice.value).trim() !== ''
  const hasInternal = vehicleChoice.value && driverChoice.value
  if (!hasInternal && !hasExternal) return false
  if (hireExternal.value && !hasExternal) return false
  if (!hireExternal.value && !hasInternal) return false
  if (!hireExternal.value) {
    if (driverChoice.value && assignBusyDriverIds.value.has(Number(driverChoice.value))) return false
    if (vehicleChoice.value && assignBusyVehicleIds.value.has(Number(vehicleChoice.value))) return false
    const v = assignVehicles.value.find((x) => String(x.id) === String(vehicleChoice.value))
    if (v && (v.seat_count ?? 0) < neededSeatsAssign.value) return false
  }
  return true
})

function defaultVehicleIdForAssign(driverId) {
  if (driverId == null || String(driverId).trim() === '') return null
  const v = assignVehicles.value.find((x) => x.default_driver && String(x.default_driver.id) === String(driverId))
  return v ? String(v.id) : null
}

function syncAssignVehicleToDriver() {
  if (suppressAssignSync.value || hireExternal.value) return
  const id = driverChoice.value
  if (!id) {
    needsAssignVehicleResync.value = false
    return
  }
  const vid = defaultVehicleIdForAssign(id)
  if (vid) {
    vehicleChoice.value = vid
    needsAssignVehicleResync.value = false
  } else {
    needsAssignVehicleResync.value = true
  }
}

async function loadAssignResources() {
  assignResourceHint.value = ''
  try {
    const [vr, dr, pr] = await Promise.allSettled([
      listVehicles({ status: 'ready', per_page: 150 }),
      listDrivers({ employment_status: 'active', availability_status: 'available', per_page: 150 }),
      listTransportProviders({ is_active: true, per_page: 200 }),
    ])
    if (vr.status === 'fulfilled') assignVehicles.value = vr.value.items ?? []
    else assignResourceHint.value = t('trip_detail.ops.messages.vehicles_load_failed')
    if (dr.status === 'fulfilled') assignDrivers.value = dr.value.items ?? []
    else assignResourceHint.value = assignResourceHint.value || t('trip_detail.ops.messages.drivers_load_failed')
    if (pr.status === 'fulfilled') transportProviders.value = pr.value.items ?? []
  } catch {
    assignResourceHint.value = t('trip_detail.ops.messages.resources_load_failed')
  }
}

async function loadSameDayTripsForCargo() {
  const key = scheduleDateKeyForAssign.value
  if (!tripForAssign.value?.id || !key) {
    sameDayTrips.value = []
    sameDayTripsError.value = ''
    return
  }
  sameDayTripsLoading.value = true
  sameDayTripsError.value = ''
  try {
    const merged = []
    const seen = new Set()
    let page = 1
    let lastPage = 1
    do {
      const res = await listTrips({ from: key, to: key, per_page: 100, page })
      const items = res.items ?? []
      for (const x of items) {
        if (x?.id != null && !seen.has(x.id)) {
          seen.add(x.id)
          merged.push(x)
        }
      }
      lastPage = Number(res.meta?.last_page ?? 1)
      page += 1
    } while (page <= lastPage && page <= 30)
    sameDayTrips.value = merged
  } catch (e) {
    sameDayTrips.value = []
    sameDayTripsError.value = formatApiMessage(e)
  } finally {
    sameDayTripsLoading.value = false
  }
}

async function loadTripForAssign() {
  tripForAssign.value = null
  assignLoadError.value = ''
  assignMsg.value = ''
  const tid = shipment.value?.trip_id
  if (!tid || !canAssignTrip.value) return
  try {
    const data = await getTrip(tid)
    tripForAssign.value = data
    suppressAssignSync.value = true
    try {
      vehicleChoice.value = data.vehicle_id ? String(data.vehicle_id) : ''
      driverChoice.value = data.driver_id ? String(data.driver_id) : ''
      hireExternal.value = !!data.transport_provider_id
      providerChoice.value = data.transport_provider_id ? String(data.transport_provider_id) : ''
      externalVehicleRef.value = data.external_vehicle_ref ?? ''
      externalDriverRef.value = data.external_driver_ref ?? ''
      assignCoordinationNotes.value = ''
      await nextTick()
    } finally {
      suppressAssignSync.value = false
    }
  } catch (e) {
    assignLoadError.value = formatApiMessage(e)
    tripForAssign.value = null
  }
}

function openAssignProviderModal() {
  assignProviderModalError.value = ''
  newAssignProviderName.value = ''
  newAssignProviderType.value = 'vendor'
  assignProviderModalOpen.value = true
}

async function submitQuickAssignProvider() {
  assignProviderModalError.value = ''
  const name = newAssignProviderName.value.trim()
  if (!name) {
    assignProviderModalError.value = t('trip_detail.coordination.provider_modal_name_required')
    return
  }
  assignProviderCreating.value = true
  try {
    const created = await createTransportProvider({ name, type: newAssignProviderType.value, is_active: true })
    const pr = await listTransportProviders({ is_active: true, per_page: 200 })
    transportProviders.value = pr.items ?? []
    if (created?.id != null) providerChoice.value = String(created.id)
    assignProviderModalOpen.value = false
  } catch (e) {
    assignProviderModalError.value = formatApiMessage(e)
  } finally {
    assignProviderCreating.value = false
  }
}

async function submitCargoTripAssign() {
  if (!tripForAssign.value || !shipment.value?.trip_id) return
  assignMsg.value = ''
  assignFeedbackKind.value = ''
  const hasInternal = vehicleChoice.value && driverChoice.value
  const hasExternal = hireExternal.value && providerChoice.value && String(providerChoice.value).trim() !== ''
  if (!hasInternal && !hasExternal) {
    assignFeedbackKind.value = 'error'
    assignMsg.value = t('trip_detail.coordination.validation_assign')
    return
  }
  if (hireExternal.value && !hasExternal) {
    assignFeedbackKind.value = 'error'
    assignMsg.value = t('trip_detail.coordination.validation_provider')
    return
  }
  if (!hireExternal.value && !hasInternal) {
    assignFeedbackKind.value = 'error'
    assignMsg.value = t('trip_detail.coordination.validation_assign')
    return
  }
  if (!hireExternal.value) {
    if (driverChoice.value && isAssignDriverBusy(Number(driverChoice.value))) {
      assignFeedbackKind.value = 'error'
      assignMsg.value = t('trip_detail.coordination.validation_busy_driver')
      return
    }
    if (vehicleChoice.value && isAssignVehicleBusy(Number(vehicleChoice.value))) {
      assignFeedbackKind.value = 'error'
      assignMsg.value = t('trip_detail.coordination.validation_busy_vehicle')
      return
    }
  }

  assignSaving.value = true
  try {
    const buildAssignBody = () => {
      const body = {}
      if (hireExternal.value && hasExternal) {
        body.transport_provider_id = Number(providerChoice.value)
        body.vehicle_id = null
        body.driver_id = null
      } else {
        body.transport_provider_id = null
        if (vehicleChoice.value) body.vehicle_id = Number(vehicleChoice.value)
        if (driverChoice.value) body.driver_id = Number(driverChoice.value)
      }
      if (externalVehicleRef.value?.trim()) body.external_vehicle_ref = externalVehicleRef.value.trim()
      else body.external_vehicle_ref = null
      if (externalDriverRef.value?.trim()) body.external_driver_ref = externalDriverRef.value.trim()
      else body.external_driver_ref = null
      return body
    }

    const tid = shipment.value.trip_id
    await runWithOptimisticLockRetry({
      getTrip: () => tripForAssign.value,
      refreshTrip: async () => {
        const data = await getTrip(tid)
        patchTripFromApi(tripForAssign, data)
        return tripForAssign.value
      },
      execute: async (snap) =>
        assignTrip(
          tid,
          withTripLockVersion(buildAssignBody(), snap ?? tripForAssign.value),
          { idempotencyKey: newIdempotencyKey() },
        ),
    })

    const note = assignCoordinationNotes.value.trim()
    if (note) {
      try {
        await addTripEvent(shipment.value.trip_id, { type: 'note', message: note })
      } catch {
        /* non-fatal */
      }
    }

    assignFeedbackKind.value = 'success'
    assignMsg.value = t('trip_detail.coordination.assign_success')
    await reloadShipment()
    await loadSameDayTripsForCargo()
  } catch (e) {
    assignFeedbackKind.value = 'error'
    if (isOptimisticLockConflict(e)) {
      try {
        const data = await getTrip(shipment.value.trip_id)
        patchTripFromApi(tripForAssign, data)
      } catch {
        /* keep message */
      }
      assignMsg.value = t('trip_detail.coordination.lock_refresh_hint')
    } else {
      assignMsg.value = formatApiMessage(e)
    }
  } finally {
    assignSaving.value = false
  }
}

const quickStatusActions = computed(() => {
  if (!canManageCargo.value || !shipment.value) return []
  const st = shipment.value.status
  if (st === 'delivered' || st === 'failed' || st === 'cancelled') return []
  const out = []
  if (st === 'pending') {
    out.push({ status: 'picked_up', danger: false })
    out.push({ status: 'in_transit', danger: false })
  }
  if (st === 'picked_up') {
    out.push({ status: 'in_transit', danger: false })
    out.push({ status: 'delivered', danger: false })
  }
  if (st === 'in_transit') {
    out.push({ status: 'delivered', danger: false })
  }
  out.push({ status: 'failed', danger: true })
  out.push({ status: 'cancelled', danger: true })
  return out
})

const timelineFiltered = computed(() => {
  const items = timelineItems.value
  const f = timelineFilter.value
  if (f === 'all') return items
  return items.filter((ev) => ev.kind === f)
})

function quickStatusLabel(status) {
  const key = `cargo_detail.quick_${status}`
  return te(key) ? t(key) : status
}

async function applyQuickStatus(status, danger) {
  if (!shipment.value) return
  if (danger) {
    const ok = window.confirm(t('cargo_detail.quick_confirm_danger'))
    if (!ok) return
  }
  statusError.value = ''
  statusSaving.value = true
  try {
    const res = await updateCargoStatus(shipment.value.id, { status })
    const next = res?.shipment
    if (next && typeof next === 'object') {
      shipment.value = { ...shipment.value, ...next }
      await loadTripForAssign()
      await loadAssignResources()
    } else {
      await reloadShipment()
    }
    getCargoShipmentTimeline(shipment.value.id)
      .then((d) => {
        timelineItems.value = d.items ?? []
      })
      .catch(() => {})
    void notifStore.refreshBadges()
  } catch (e) {
    statusError.value = e?.response?.data?.message || t('cargo_detail.quick_status_error')
  } finally {
    statusSaving.value = false
  }
}

function fmt(v) {
  return v ? new Date(v).toLocaleString(locale.value === 'en' ? 'en-GB' : 'vi-VN') : ''
}

function driverInitials(name) {
  if (!name) return '?'
  const p = String(name).trim().split(/\s+/)
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

function formatBytes(n) {
  if (n == null || n <= 0) return '—'
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  return `${(n / (1024 * 1024)).toFixed(1)} MB`
}

function formatMoney(amount, currency) {
  const a = Number(amount)
  if (!Number.isFinite(a)) return '—'
  const cur = (currency || 'VND').toUpperCase()
  try {
    return new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'vi-VN', {
      style: 'currency',
      currency: cur === 'VND' ? 'VND' : cur,
      maximumFractionDigits: cur === 'VND' ? 0 : 2,
    }).format(a)
  } catch {
    return `${a} ${cur}`
  }
}

const costsTotal = computed(() => costItems.value.reduce((s, c) => s + (Number(c.amount) || 0), 0))

function timelineTitle(ev) {
  if (ev.kind === 'milestone') {
    const key = `labels.cargo_timeline.${ev.code}`
    return te(key) ? t(key) : ev.code
  }
  const key = `labels.cargo_audit.${ev.code}`
  return te(key) ? t(key) : ev.code
}

function timelineSubtitle(ev) {
  if (ev.kind === 'milestone' && ev.detail) return ev.detail
  if (ev.kind === 'audit' && ev.code === 'cargo.status_change' && ev.after?.status) {
    const st = ev.after.status
    const key = `labels.cargo_status.${st}`
    return te(key) ? t(key) : st
  }
  return ''
}

function setDocumentTitle() {
  if (typeof document === 'undefined') return
  const code = displayCode.value
  const appTitle = i18n.global.t('app.title')
  if (code) {
    document.title = `${t('cargo_detail.document_title', { code })} · ${appTitle}`
  }
}

async function loadCosts(tripId) {
  costsLoading.value = true
  costsForbidden.value = false
  costItems.value = []
  try {
    const data = await listCostsForTrip(tripId, { per_page: 100, page: 1 })
    costItems.value = data.items ?? []
  } catch (e) {
    const status = e?.response?.status
    if (status === 403) {
      costsForbidden.value = true
    }
  } finally {
    costsLoading.value = false
  }
}

async function reloadShipment() {
  const id = route.params.id
  const data = await getCargoShipment(id)
  shipment.value = data
  const tid = shipment.value?.trip_id
  if (tid) await loadCosts(tid)
  await loadTripForAssign()
  await loadAssignResources()
}

async function load() {
  loading.value = true
  loadError.value = ''
  shipment.value = null
  timelineItems.value = []
  try {
    await reloadShipment()
    const id = route.params.id
    timelineLoading.value = true
    getCargoShipmentTimeline(id)
      .then((d) => {
        timelineItems.value = d.items ?? []
      })
      .catch(() => {
        timelineItems.value = []
      })
      .finally(() => {
        timelineLoading.value = false
      })
  } catch (e) {
    loadError.value = e?.response?.data?.message || t('cargo_detail.load_error')
  } finally {
    loading.value = false
  }
}

watch(
  () => [shipment.value?.tracking_code, shipment.value?.id],
  () => setDocumentTitle(),
  { flush: 'post' },
)

watch(
  () => [tripForAssign.value?.id, scheduleDateKeyForAssign.value],
  async ([id, key]) => {
    if (!id || !key) {
      sameDayTrips.value = []
      sameDayTripsError.value = ''
      return
    }
    await loadSameDayTripsForCargo()
  },
  { flush: 'post' },
)

watch(hireExternal, (on) => {
  if (!on) providerChoice.value = ''
  if (on) needsAssignVehicleResync.value = false
})

watch(driverChoice, () => syncAssignVehicleToDriver(), { flush: 'sync' })

watch(assignVehicles, () => {
  if (!needsAssignVehicleResync.value) return
  syncAssignVehicleToDriver()
})

onMounted(() => {
  load()
})

watch(
  () => route.params.id,
  () => load(),
)
</script>
