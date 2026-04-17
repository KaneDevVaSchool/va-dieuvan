<template>
  <div class="min-h-screen bg-[#F8F9FA]">
    <div v-if="loading && !trip" class="mx-auto max-w-6xl space-y-6 px-4 py-10">
      <div class="animate-pulse space-y-4">
        <div class="h-10 max-w-md rounded-xl bg-slate-200/90" />
        <div class="grid gap-6 lg:grid-cols-3">
          <div class="space-y-4 lg:col-span-2">
            <div class="h-64 rounded-2xl bg-slate-200/80" />
            <div class="h-48 rounded-2xl bg-slate-200/70" />
            <div class="h-56 rounded-2xl bg-slate-200/70" />
          </div>
          <div class="space-y-4">
            <div class="h-72 rounded-2xl bg-slate-200/80" />
            <div class="h-40 rounded-2xl bg-slate-200/70" />
          </div>
        </div>
      </div>
      <p class="text-center text-xs text-slate-500">{{ t('trip_detail.loading') }}</p>
    </div>

    <div v-else-if="loadError && !trip" class="mx-auto max-w-lg px-4 py-20 text-center">
      <p class="text-sm text-rose-700">{{ loadError }}</p>
      <Button class="mt-4" @click="load()">{{ t('trip_detail.retry') }}</Button>
    </div>

    <template v-else-if="trip">
      <div class="mx-auto max-w-6xl space-y-6 px-4 pb-12">
        <!-- Header -->
        <header class="flex flex-col gap-4 border-b border-slate-200/80 pb-6 sm:flex-row sm:items-start sm:justify-between">
          <div class="flex min-w-0 flex-1 items-start gap-3">
            <RouterLink
              to="/trips"
              class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
              :aria-label="t('trip_detail.header.back_trips')"
            >
              <ArrowLeftIcon class="h-5 w-5" />
            </RouterLink>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">
                  {{ t('trip_detail.header.page_title') }}
                </h1>
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                  :class="pillClassForStatus(trip.status)"
                >
                  {{ labelTripStatus(trip.status) }}
                </span>
              </div>
              <p class="mt-1 text-sm text-slate-600">
                {{ t('trip_detail.header.request_ref', { code: requestRefCode }) }}
                <span class="text-slate-300">·</span>
                {{ t('trip_detail.header.trip_ref', { code: tripCode }) }}
              </p>
              <p class="mt-0.5 text-xs text-slate-500">{{ t('trip_detail.created_at', { time: fmt(trip.created_at) }) }}</p>
            </div>
          </div>
          <div class="flex flex-wrap items-center justify-end gap-2">
            <button
              type="button"
              class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50 disabled:opacity-50"
              :disabled="refreshing"
              :title="t('trip_detail.actions.refresh')"
              @click="load({ silent: true })"
            >
              <ArrowPathIcon class="h-5 w-5" :class="refreshing ? 'animate-spin' : ''" />
            </button>
            <RouterLink
              to="/notifications"
              class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50"
              :title="t('trip_detail.header.notifications')"
            >
              <BellIcon class="h-5 w-5" />
            </RouterLink>
          </div>
          <p v-if="silentLoadError" class="w-full rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-left text-xs text-amber-900 sm:order-last">
            {{ silentLoadError }}
          </p>
        </header>

        <div
          v-if="trip.dispatch_request && trip.dispatch_request.status === 'pending'"
          class="rounded-xl border border-amber-200 bg-amber-50/90 px-4 py-3 text-sm text-amber-900"
        >
          {{ t('trip_detail.banner.request_pending') }}
          <RouterLink
            v-if="trip.dispatch_request?.id"
            :to="`/requests/${trip.dispatch_request.id}`"
            class="ml-1 font-semibold underline decoration-amber-700/40 underline-offset-2"
          >
            {{ t('trip_detail.banner.open_request') }}
          </RouterLink>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
          <!-- Main column -->
          <div class="space-y-6 lg:col-span-2">
            <!-- Overview -->
            <section class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
              <div class="absolute right-4 top-4">
                <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', pillClassForStatus(trip.status)]">
                  {{ labelTripStatus(trip.status) }}
                </span>
              </div>
              <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.overview.title') }}</h2>

              <div
                v-if="slaBanner"
                class="mt-3 rounded-xl border px-3 py-2 text-sm font-medium"
                :class="slaBanner.kind === 'overdue' ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-sky-200 bg-sky-50 text-sky-950'"
              >
                {{ slaBanner.text }}
              </div>

              <div class="mt-3 flex flex-wrap gap-2 print:hidden">
                <span
                  v-if="trip.dispatcher?.name"
                  class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-800"
                >
                  {{ t('trip_detail.meta.dispatcher', { name: trip.dispatcher.name }) }}
                </span>
                <span
                  v-if="trip.dispatch_request?.source_channel"
                  class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-900"
                >
                  {{ t('trip_detail.meta.source', { ch: trip.dispatch_request.source_channel }) }}
                </span>
                <span
                  v-if="trip.dispatch_request?.paper_status"
                  class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-900"
                >
                  {{ t('trip_detail.meta.paper', { st: trip.dispatch_request.paper_status }) }}
                </span>
                <span
                  v-if="trip.payment_status === 'paid'"
                  class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-900"
                >
                  {{ t('trip_detail.meta.paid') }}
                </span>
              </div>

              <div class="mt-5 flex flex-col gap-4 sm:flex-row sm:items-start">
                <div class="flex min-w-0 items-center gap-3">
                  <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-sky-100 to-indigo-100 text-sm font-bold text-slate-700 ring-2 ring-white"
                  >
                    <img
                      v-if="trip.dispatch_request?.requester?.avatar_url"
                      :src="trip.dispatch_request.requester.avatar_url"
                      alt=""
                      class="h-full w-full object-cover"
                    />
                    <span v-else>{{ requesterInitials }}</span>
                  </div>
                  <div class="min-w-0">
                    <div class="truncate font-semibold text-slate-900">{{ requesterName }}</div>
                    <div class="truncate text-sm text-slate-600">{{ requesterSubtitle }}</div>
                  </div>
                </div>
                <div class="flex flex-1 flex-wrap gap-2 sm:justify-end">
                  <span
                    class="inline-flex items-center rounded-full bg-violet-50 px-3 py-1 text-xs font-medium text-violet-800 ring-1 ring-violet-100"
                  >
                    {{ tripTypeLabel }}
                  </span>
                  <span
                    v-if="trip.dispatch_request?.is_urgent"
                    class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-medium text-rose-700 ring-1 ring-rose-100"
                  >
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500" />
                    {{ t('trip_detail.high_priority') }}
                  </span>
                </div>
              </div>

              <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
                  <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
                    <CalendarDaysIcon class="h-4 w-4 text-slate-400" />
                    {{ t('trip_detail.overview.schedule') }}
                  </div>
                  <div class="mt-2 text-sm font-semibold text-slate-900">{{ scheduleDateLong }}</div>
                  <div class="mt-1 text-sm text-slate-600">
                    {{ scheduleTimeRange }}
                    <span v-if="scheduleDuration" class="text-slate-500">({{ scheduleDuration }})</span>
                  </div>
                  <ul class="mt-2 space-y-1 text-xs text-slate-600">
                    <li v-if="trip.dispatch_request?.passenger_count != null && trip.dispatch_request.passenger_count > 0">
                      {{ t('trip_detail.overview.pax_count', { n: trip.dispatch_request.passenger_count }) }}
                    </li>
                    <li v-if="trip.arrive_by">{{ t('trip_detail.overview.arrive_deadline', { time: fmtTime(trip.arrive_by) }) }}</li>
                    <li v-for="(ln, i) in scheduleMismatchNotes" :key="i" class="text-amber-800/90">{{ ln }}</li>
                  </ul>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                    <div class="text-xs font-medium text-slate-500">{{ t('trip_detail.overview.est_distance') }}</div>
                    <div class="mt-1 text-lg font-semibold tabular-nums text-slate-900">{{ estimatedDistanceLabel }}</div>
                    <p class="mt-1 text-[11px] leading-snug text-slate-500">{{ estimatedDistanceSub }}</p>
                  </div>
                  <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                    <div class="text-xs font-medium text-slate-500">{{ t('trip_detail.overview.est_cost') }}</div>
                    <div class="mt-1 text-lg font-semibold tabular-nums text-slate-900">{{ estimatedCostLabel }}</div>
                    <p class="mt-1 text-[11px] leading-snug text-slate-500">{{ estimatedCostSub }}</p>
                  </div>
                </div>
              </div>

              <!-- Trip progress (pickup / en route / dropoff) -->
              <div class="mt-6 border-t border-slate-100 pt-5">
                <div class="grid gap-4 md:grid-cols-3">
                  <div class="flex items-start gap-3">
                    <div :class="dotClass(stepPickup.state)" />
                    <div class="min-w-0">
                      <div class="text-xs font-medium text-slate-500">{{ t('trip_detail.trip_status.pickup') }}</div>
                      <div class="mt-1 text-sm font-semibold text-slate-900">{{ originLabel }}</div>
                      <div class="mt-1 text-xs text-slate-500">
                        <span class="font-medium">{{ stepPickup.label }}</span>
                        <span v-if="trip.depart_at"> · {{ fmtTime(trip.depart_at) }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div :class="dotClass(stepCurrent.state)" />
                    <div class="min-w-0">
                      <div class="text-xs font-medium text-slate-500">{{ t('trip_detail.trip_status.current') }}</div>
                      <div class="mt-1 text-sm font-semibold text-slate-900">{{ currentLabel }}</div>
                      <div class="mt-1 text-xs text-slate-500">
                        <span class="font-medium">{{ stepCurrent.label }}</span>
                        <span v-if="trip.started_at"> · {{ fmtTime(trip.started_at) }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-start gap-3">
                    <div :class="dotClass(stepDropoff.state)" />
                    <div class="min-w-0">
                      <div class="text-xs font-medium text-slate-500">{{ t('trip_detail.trip_status.dropoff') }}</div>
                      <div class="mt-1 text-sm font-semibold text-slate-900">{{ destinationLabel }}</div>
                      <div class="mt-1 text-xs text-slate-500">
                        <span class="font-medium">{{ stepDropoff.label }}</span>
                        <span v-if="trip.arrive_by"> · {{ fmtTime(trip.arrive_by) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </section>

            <!-- Route + map -->
            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
              <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.route.section_title') }}</h2>
                <div v-if="routeStops.length" class="flex flex-wrap gap-2 text-xs text-slate-600">
                  <span class="rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-700">
                    {{ t('trip_detail.route.stop_count', { n: routeStops.length }) }}
                  </span>
                  <span v-if="trip.record?.distance_km != null && trip.record.distance_km !== ''" class="rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-800">
                    {{ t('trip_detail.route.recorded_km', { km: trip.record.distance_km }) }}
                  </span>
                  <span class="rounded-full bg-sky-50 px-2.5 py-1 font-medium text-sky-800">{{ tripTypeLabel }}</span>
                </div>
              </div>
              <div class="mt-4 flex flex-col gap-5 lg:flex-row">
                <div class="min-w-0 flex-1">
                  <ol class="relative space-y-0 border-l-2 border-slate-200 pl-6">
                    <li v-for="(stop, idx) in routeStops" :key="`${idx}-${stop.address}`" class="relative pb-8 last:pb-0">
                      <span
                        class="absolute -left-[25px] top-1 flex h-5 w-5 items-center justify-center rounded-full border-2 border-white text-[10px] font-bold"
                        :class="stopDotClass(stop.kind)"
                      >
                        {{ idx + 1 }}
                      </span>
                      <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ stopLabel(stop.kind) }}</div>
                      <div class="mt-1 text-sm font-medium text-slate-900">{{ stop.address }}</div>
                      <ul v-if="stop.detailLines?.length" class="mt-2 space-y-0.5 border-l-2 border-slate-100 pl-3">
                        <li v-for="(dl, j) in stop.detailLines" :key="j" class="text-xs leading-relaxed text-slate-600">{{ dl }}</li>
                      </ul>
                    </li>
                  </ol>
                  <div v-if="!routeStops.length" class="text-sm text-slate-500">{{ t('trip_detail.route.no_stops') }}</div>
                </div>
                <div class="relative w-full shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 lg:w-[320px]">
                  <div class="relative aspect-[4/3] w-full bg-gradient-to-br from-emerald-50 via-sky-50 to-indigo-50">
                    <iframe
                      v-if="embedMapSrc"
                      :src="embedMapSrc"
                      class="absolute inset-0 h-full w-full border-0"
                      loading="lazy"
                      referrerpolicy="no-referrer-when-downgrade"
                      :title="t('trip_detail.route.map_title')"
                    />
                    <div v-else class="flex h-full min-h-[200px] items-center justify-center p-4 text-center text-sm text-slate-500">
                      {{ t('trip_detail.route.map_placeholder') }}
                    </div>
                  </div>
                  <button
                    type="button"
                    class="absolute right-2 top-2 rounded-lg border border-slate-200/80 bg-white/95 px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:bg-white"
                    @click="mapExpanded = true"
                  >
                    {{ t('trip_detail.route.expand_map') }}
                  </button>
                </div>
              </div>
              <p class="mt-3 text-xs text-slate-500">{{ t('trip_detail.route_tracking.hint_no_gps') }}</p>
            </section>

            <!-- Passengers -->
            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
              <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">
                  {{ t('trip_detail.passengers.title', { n: passengerRowsDisplay.length }) }}
                </h2>
                <div v-if="canEditPassengerList" class="flex flex-wrap items-center gap-2">
                  <template v-if="!passengersEditMode">
                    <button
                      type="button"
                      class="rounded-lg border border-sky-200/80 bg-sky-50/80 px-3 py-1.5 text-sm font-semibold text-sky-800 transition hover:bg-sky-100 dark:border-sky-800/50 dark:bg-sky-950/40 dark:text-sky-200 dark:hover:bg-sky-950/70"
                      @click="startPassengersEdit"
                    >
                      {{ t('trip_detail.passengers.edit_inline') }}
                    </button>
                  </template>
                  <template v-else>
                    <button
                      type="button"
                      class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                      @click="addPassengerListRow"
                    >
                      {{ t('trip_detail.passengers.add_row') }}
                    </button>
                    <button
                      type="button"
                      class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                      @click="cancelPassengersEdit"
                    >
                      {{ t('trip_detail.passengers.cancel_edit') }}
                    </button>
                    <button
                      type="button"
                      class="rounded-lg bg-sky-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-700 disabled:opacity-50"
                      :disabled="passengersSaving"
                      @click="submitPassengersEdit"
                    >
                      {{ t('trip_detail.passengers.save') }}
                    </button>
                  </template>
                </div>
              </div>
              <p v-if="passengersEditMsg" class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ passengersEditMsg }}</p>
              <div class="mt-4 overflow-x-auto rounded-xl border border-slate-100">
                <template v-if="passengersEditMode && passengersEditDraft">
                  <!-- D2D / P2P -->
                  <table v-if="passengersEditDraft.kind === 'passenger'" class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/80">
                      <tr>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600">{{ t('trip_detail.passengers.col_name') }}</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600">{{ t('trip_detail.passengers.col_guests') }}</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600">{{ t('trip_detail.passengers.col_notes') }}</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600" />
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                      <tr v-for="(row, idx) in passengersEditDraft.passengerRows" :key="'pe-' + idx">
                        <td class="px-3 py-2 align-top">
                          <input v-model="row.person_in_charge" type="text" :class="paxEditInputClass" />
                        </td>
                        <td class="px-3 py-2 align-top">
                          <input v-model="row.guests" type="number" min="1" step="1" :class="paxEditInputClass" />
                        </td>
                        <td class="px-3 py-2 align-top">
                          <input v-model="row.notes" type="text" :class="paxEditInputClass" />
                        </td>
                        <td class="px-3 py-2 align-top">
                          <button
                            v-if="passengersEditDraft.passengerRows.length > 1"
                            type="button"
                            class="rounded p-1 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                            :aria-label="t('trip_detail.passengers.remove_row')"
                            @click="removePassengerListRow(idx)"
                          >
                            <TrashIcon class="h-4 w-4" />
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <!-- Business -->
                  <table v-else-if="passengersEditDraft.kind === 'business'" class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/80">
                      <tr>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600">{{ t('trip_detail.passengers.col_guests') }}</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600">{{ t('trip_detail.passengers.col_notes') }}</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600" />
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                      <tr v-for="(row, idx) in passengersEditDraft.businessRows" :key="'be-' + idx">
                        <td class="px-3 py-2 align-top">
                          <input v-model="row.guests" type="number" min="1" step="1" :class="paxEditInputClass" />
                        </td>
                        <td class="px-3 py-2 align-top">
                          <input v-model="row.notes" type="text" :class="paxEditInputClass" />
                        </td>
                        <td class="px-3 py-2 align-top">
                          <button
                            v-if="passengersEditDraft.businessRows.length > 1"
                            type="button"
                            class="rounded p-1 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                            :aria-label="t('trip_detail.passengers.remove_row')"
                            @click="removePassengerListRow(idx)"
                          >
                            <TrashIcon class="h-4 w-4" />
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <!-- Cargo -->
                  <table v-else class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/80">
                      <tr>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600">{{ t('trip_detail.passengers.col_name') }}</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600">{{ t('trip_detail.passengers.col_qty') }}</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600">{{ t('trip_detail.passengers.col_notes') }}</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600">{{ t('trip_detail.passengers.col_contact') }}</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600" />
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                      <tr v-for="(row, idx) in passengersEditDraft.cargoRows" :key="'ce-' + idx">
                        <td class="px-3 py-2 align-top">
                          <input v-model="row.name" type="text" :class="paxEditInputClass" />
                        </td>
                        <td class="px-3 py-2 align-top">
                          <input v-model="row.qty" type="number" min="1" step="1" :class="paxEditInputClass" />
                        </td>
                        <td class="px-3 py-2 align-top">
                          <input v-model="row.item_notes" type="text" :class="paxEditInputClass" />
                        </td>
                        <td class="px-3 py-2 align-top">
                          <div class="flex min-w-[10rem] flex-col gap-1">
                            <input v-model="row.pickup_contact" type="text" :placeholder="t('trip_detail.passengers.ph_pickup_contact')" :class="paxEditInputClass" />
                            <input v-model="row.delivery_contact" type="text" :placeholder="t('trip_detail.passengers.ph_delivery_contact')" :class="paxEditInputClass" />
                          </div>
                        </td>
                        <td class="px-3 py-2 align-top">
                          <button
                            v-if="passengersEditDraft.cargoRows.length > 1"
                            type="button"
                            class="rounded p-1 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40"
                            :aria-label="t('trip_detail.passengers.remove_row')"
                            @click="removePassengerListRow(idx)"
                          >
                            <TrashIcon class="h-4 w-4" />
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </template>
                <template v-else>
                  <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50/80">
                      <tr>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600">{{ t('trip_detail.passengers.col_name') }}</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600">{{ t('trip_detail.passengers.col_role') }}</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600">{{ t('trip_detail.passengers.col_contact') }}</th>
                        <th class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600">{{ t('trip_detail.passengers.col_notes') }}</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                      <tr v-for="(row, idx) in passengerRowsDisplay" :key="idx">
                        <td class="px-3 py-2.5 font-medium text-slate-900">{{ row.name }}</td>
                        <td class="px-3 py-2.5">
                          <span
                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="rolePillClass(row.roleKind)"
                          >
                            {{ row.roleLabel }}
                          </span>
                        </td>
                        <td class="max-w-[140px] truncate px-3 py-2.5 text-slate-600">{{ row.contact || '—' }}</td>
                        <td class="px-3 py-2.5">
                          <div class="flex flex-wrap items-center gap-1.5">
                            <span v-if="row.flagWheelchair" :title="t('trip_detail.passengers.flag_wheelchair')">
                              <WheelchairIcon class="h-5 w-5 text-rose-600" />
                            </span>
                            <span v-if="row.flagAllergy" :title="t('trip_detail.passengers.flag_allergy')">
                              <ExclamationTriangleIcon class="h-5 w-5 text-amber-500" />
                            </span>
                            <span class="text-slate-600">{{ row.notes || '—' }}</span>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <div v-if="!passengerRowsDisplay.length" class="px-3 py-6 text-center text-sm text-slate-500">
                    {{ t('trip_detail.passengers.empty') }}
                  </div>
                </template>
              </div>
              <div
                v-if="specialNeedsSummary"
                class="mt-4 rounded-xl border border-sky-100 bg-sky-50/80 px-4 py-3 text-sm text-sky-950"
              >
                <div class="text-xs font-bold uppercase tracking-wide text-sky-800/80">{{ t('trip_detail.passengers.special_summary_title') }}</div>
                <p class="mt-1 whitespace-pre-wrap">{{ specialNeedsSummary }}</p>
              </div>
            </section>

            <!-- Chi phí phát sinh -->
            <section
              class="overflow-hidden rounded-2xl border border-amber-200/85 bg-white shadow-lg shadow-amber-500/[0.06] ring-1 ring-amber-100/50 print:break-inside-avoid dark:border-amber-900/45 dark:bg-slate-900/45 dark:shadow-none dark:ring-slate-800/80"
              :aria-label="t('trip_detail.costs_block.title')"
            >
              <div
                class="border-b border-amber-100/90 bg-gradient-to-br from-amber-50/95 via-white to-orange-50/55 px-5 py-4 sm:px-6 dark:from-amber-950/35 dark:via-slate-900 dark:to-orange-950/25 dark:border-amber-900/40"
              >
                <div class="flex flex-wrap items-start gap-4">
                  <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-800 shadow-sm dark:bg-amber-950/70 dark:text-amber-200"
                  >
                    <BanknotesIcon class="h-5 w-5" aria-hidden="true" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <h2 class="text-base font-bold tracking-tight text-slate-900 dark:text-white">{{ t('trip_detail.costs_block.title') }}</h2>
                    <p class="mt-1 max-w-2xl text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                      {{ t('trip_detail.costs_block.subtitle') }}
                    </p>
                  </div>
                </div>
              </div>
              <div class="p-5 sm:p-6">
                <div
                  class="rounded-xl border border-slate-200/75 bg-gradient-to-b from-slate-50/70 to-white p-4 shadow-sm dark:border-slate-700/80 dark:from-slate-950/40 dark:to-slate-900/60"
                >
                  <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-400">{{ t('trip_detail.costs.title') }}</div>
                    <div class="flex flex-wrap items-center gap-2">
                      <div v-if="(trip.costs ?? []).length" class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">
                        {{ t('trip_detail.costs.total', { amount: costsTotalFormatted }) }}
                      </div>
                      <RouterLink
                        to="/costs"
                        class="rounded-lg px-2 py-1 text-xs font-semibold text-amber-800 underline decoration-amber-300/80 underline-offset-2 hover:bg-amber-50 hover:text-amber-950 dark:text-amber-300 dark:hover:bg-amber-950/40"
                      >
                        {{ t('trip_detail.costs.open_list') }}
                      </RouterLink>
                    </div>
                  </div>
                  <div
                    v-if="canSubmitQuickCost"
                    class="mt-4 rounded-xl border border-dashed border-amber-200/80 bg-amber-50/40 p-3 dark:border-amber-900/50 dark:bg-amber-950/25"
                  >
                    <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ t('trip_detail.costs.quick_title') }}</div>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                      <Select v-model="costQuickForm.type" :label="t('trip_detail.costs.quick_type')">
                        <option value="fuel">{{ t('trip_detail.costs.type_fuel') }}</option>
                        <option value="toll">{{ t('trip_detail.costs.type_toll') }}</option>
                        <option value="parking">{{ t('trip_detail.costs.type_parking') }}</option>
                        <option value="labor">{{ t('trip_detail.costs.type_labor') }}</option>
                        <option value="other">{{ t('trip_detail.costs.type_other') }}</option>
                      </Select>
                      <Input
                        v-model="costQuickForm.amount"
                        type="number"
                        min="0"
                        step="1"
                        :label="t('trip_detail.costs.quick_amount')"
                        :placeholder="t('trip_detail.costs.quick_amount_ph')"
                      />
                      <div class="sm:col-span-2 xl:col-span-2">
                        <Input v-model="costQuickForm.description" :label="t('trip_detail.costs.quick_desc')" :placeholder="t('trip_detail.costs.quick_desc_ph')" />
                      </div>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                      <Button type="button" variant="secondary" class="!py-1.5 !text-xs" :loading="costSubmitting" @click="submitQuickCost">{{ t('trip_detail.costs.quick_submit') }}</Button>
                      <span v-if="costFormMsg" class="text-xs text-slate-600 dark:text-slate-400">{{ costFormMsg }}</span>
                    </div>
                  </div>
                  <div class="mt-4 space-y-2">
                    <div
                      v-for="c in trip.costs ?? []"
                      :key="c.id"
                      class="flex flex-col gap-1 rounded-xl border border-slate-100/90 bg-white px-3 py-2.5 shadow-sm transition hover:border-amber-100 hover:shadow-md dark:border-slate-700/80 dark:bg-slate-950/40 sm:flex-row sm:items-center sm:justify-between"
                    >
                      <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                          <span class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ costTypeLabel(c.type) }}</span>
                          <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide" :class="costStatusClass(c.status)">
                            {{ c.status }}
                          </span>
                        </div>
                        <p v-if="c.description?.trim()" class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">{{ c.description.trim() }}</p>
                      </div>
                      <div class="shrink-0 text-sm font-semibold tabular-nums text-slate-900 dark:text-white">{{ formatCostAmount(c.amount, c.currency) }}</div>
                    </div>
                    <div
                      v-if="!(trip.costs ?? []).length"
                      class="rounded-xl border border-dashed border-slate-200/90 bg-slate-50/50 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/30 dark:text-slate-400"
                    >
                      {{ t('trip_detail.costs.empty') }}
                    </div>
                  </div>
                </div>
              </div>
            </section>

            <!-- Trạng thái chuyến -->
            <section
              class="overflow-hidden rounded-2xl border border-teal-200/85 bg-white shadow-lg shadow-teal-500/[0.06] ring-1 ring-teal-100/50 print:break-inside-avoid dark:border-teal-900/45 dark:bg-slate-900/45 dark:shadow-none dark:ring-slate-800/80"
              :aria-label="t('trip_detail.status_block.title')"
            >
              <div
                class="border-b border-teal-100/90 bg-gradient-to-br from-teal-50/95 via-white to-cyan-50/50 px-5 py-4 sm:px-6 dark:from-teal-950/35 dark:via-slate-900 dark:to-cyan-950/25 dark:border-teal-900/40"
              >
                <div class="flex flex-wrap items-start gap-4">
                  <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-teal-100 text-teal-800 shadow-sm dark:bg-teal-950/70 dark:text-teal-200"
                  >
                    <ArrowPathIcon class="h-5 w-5" aria-hidden="true" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <h2 class="text-base font-bold tracking-tight text-slate-900 dark:text-white">{{ t('trip_detail.status_block.title') }}</h2>
                    <p class="mt-1 max-w-2xl text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                      {{ t('trip_detail.status_block.subtitle') }}
                    </p>
                  </div>
                </div>
              </div>
              <div class="p-5 sm:p-6">
                <form
                  class="rounded-xl border border-teal-100/90 bg-gradient-to-br from-teal-50/35 via-white to-white p-5 shadow-sm ring-1 ring-teal-100/35 dark:border-teal-900/50 dark:from-teal-950/20 dark:via-slate-900/80 dark:to-slate-900/50 dark:ring-teal-900/30"
                  @submit.prevent="doStatus"
                >
                  <div class="grid gap-3 sm:grid-cols-3">
                    <Select v-model="statusForm.status" :label="t('trip_detail.status_update.status')" :placeholder="t('trip_detail.status_update.pick')">
                      <option value="driver_confirmed">{{ labelTripStatus('driver_confirmed') }}</option>
                      <option value="in_progress">{{ labelTripStatus('in_progress') }}</option>
                      <option value="completed">{{ labelTripStatus('completed') }}</option>
                      <option value="cancelled">{{ labelTripStatus('cancelled') }}</option>
                    </Select>
                    <div class="sm:col-span-2">
                      <Input v-model="statusForm.message" :label="t('trip_detail.status_update.note')" :placeholder="t('trip_detail.status_update.note_ph')" />
                    </div>
                    <div class="sm:col-span-3 flex flex-wrap items-center gap-3 border-t border-teal-100/80 pt-4 dark:border-teal-900/40">
                      <Button v-if="canUpdateStatus" :loading="statusing" type="submit">{{ t('trip_detail.status_update.update') }}</Button>
                      <span v-else class="text-xs text-slate-500 dark:text-slate-400">{{ t('trip_detail.coordination.no_permission_status') }}</span>
                    </div>
                  </div>
                </form>
              </div>
            </section>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <section
              class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-md ring-1 ring-slate-100/80 print:hidden"
              :aria-label="t('trip_detail.coordination.title')"
            >
              <div class="border-b border-slate-100/90 bg-gradient-to-r from-slate-50 via-white to-indigo-50/60 px-5 py-4 sm:px-6">
                <h2 class="text-sm font-bold tracking-tight text-slate-900">{{ t('trip_detail.coordination.title') }}</h2>
                <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ t('trip_detail.coordination.subtitle') }}</p>
              </div>

              <div class="space-y-5 p-5 sm:p-6">
                <div
                  v-if="canRescheduleTrip"
                  class="rounded-xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50/90 to-white p-4 shadow-sm"
                >
                  <div class="text-xs font-bold uppercase tracking-wide text-indigo-900">{{ t('trip_detail.reschedule.title') }}</div>
                  <p class="mt-1 text-xs text-slate-600">{{ t('trip_detail.reschedule.hint') }}</p>
                  <input
                    v-model="rescheduleDepartLocal"
                    type="datetime-local"
                    class="mt-3 w-full rounded-lg border border-indigo-100 bg-white px-3 py-2 text-sm outline-none ring-2 ring-transparent focus:border-indigo-300 focus:ring-indigo-100"
                  />
                  <Button class="mt-3 w-full sm:w-auto" type="button" :loading="rescheduling" @click="doReschedule">
                    {{ t('trip_detail.reschedule.save') }}
                  </Button>
                  <div
                    v-if="rescheduleMsg"
                    class="mt-3 rounded-lg border px-3 py-2 text-xs font-medium"
                    :class="rescheduleFeedbackIsError ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-emerald-200 bg-emerald-50 text-emerald-900'"
                    role="status"
                  >
                    {{ rescheduleMsg }}
                  </div>
                </div>

                <div class="rounded-xl border border-slate-200/80 bg-slate-50/40 p-4 shadow-sm">
                  <div class="text-xs font-bold uppercase tracking-wide text-slate-600">{{ t('trip_detail.coordination.assign_pair_title') }}</div>
                  <p class="mt-1 text-xs text-slate-600">{{ t('trip_detail.coordination.driver_default_vehicle_hint') }}</p>
                  <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <Select
                      v-model="vehicleChoice"
                      :label="t('trip_detail.coordination.assign_vehicle')"
                      :placeholder="t('trip_detail.ops.form.pick_vehicle')"
                    >
                      <option value="">{{ t('trip_detail.ops.form.keep_or_clear') }}</option>
                      <option v-for="v in vehicles" :key="v.id" :value="String(v.id)">
                        {{ v.license_plate }} · {{ v.type ?? '—' }} {{ v.seat_count ? `(${v.seat_count})` : '' }}
                      </option>
                    </Select>
                    <Select
                      v-model="driverChoice"
                      :label="t('trip_detail.coordination.assign_driver')"
                      :placeholder="t('trip_detail.ops.form.pick_driver')"
                    >
                      <option value="">{{ t('trip_detail.ops.form.keep_or_clear') }}</option>
                      <option v-for="d in drivers" :key="d.id" :value="String(d.id)">
                        {{ d.full_name }} {{ d.phone ? `· ${d.phone}` : '' }}
                      </option>
                    </Select>
                  </div>
                  <p v-if="suitableVehiclesHint" class="mt-3 text-xs font-medium text-emerald-800">{{ suitableVehiclesHint }}</p>
                </div>

                <label
                  class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm"
                >
                  <input v-model="hireExternal" type="checkbox" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                  {{ t('trip_detail.coordination.hire_external') }}
                </label>

                <div v-if="hireExternal" class="grid gap-3 rounded-xl border border-amber-200/80 bg-amber-50/60 p-4">
                  <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                      <Select
                        v-model="providerChoice"
                        :label="t('trip_detail.coordination.provider_select')"
                        :placeholder="t('trip_detail.coordination.provider_placeholder')"
                      >
                        <option value="">{{ t('trip_detail.coordination.provider_placeholder') }}</option>
                        <option v-for="p in transportProviders" :key="p.id" :value="String(p.id)">
                          {{ p.name }}<template v-if="p.type"> ({{ p.type }})</template>
                        </option>
                      </Select>
                    </div>
                    <Button
                      v-if="canQuickCreateProvider"
                      type="button"
                      variant="secondary"
                      class="shrink-0 !px-3"
                      @click="openProviderModal"
                    >
                      {{ t('trip_detail.coordination.provider_quick_add') }}
                    </Button>
                  </div>
                  <Input
                    v-model="externalVehicleRef"
                    :label="t('trip_detail.coordination.external_vehicle')"
                    :placeholder="t('trip_detail.coordination.external_vehicle_ph')"
                  />
                  <Input
                    v-model="externalDriverRef"
                    :label="t('trip_detail.coordination.external_driver')"
                    :placeholder="t('trip_detail.coordination.external_driver_ph')"
                  />
                </div>

                <div>
                  <label class="mb-1 block text-sm font-medium text-slate-800">{{ t('trip_detail.coordination.internal_notes') }}</label>
                  <textarea
                    v-model="coordinationNotes"
                    rows="3"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-sky-200 focus:ring"
                    :placeholder="t('trip_detail.coordination.internal_notes_ph')"
                  />
                </div>

                <div v-if="resourceHint" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-950">
                  {{ resourceHint }}
                </div>
                <div
                  v-if="assignMsg"
                  class="rounded-lg border px-3 py-2.5 text-sm font-medium"
                  :class="
                    assignFeedbackKind === 'success'
                      ? 'border-emerald-200 bg-emerald-50 text-emerald-950'
                      : assignFeedbackKind === 'error'
                        ? 'border-rose-200 bg-rose-50 text-rose-950'
                        : 'border-slate-200 bg-slate-50 text-slate-800'
                  "
                  role="alert"
                >
                  {{ assignMsg }}
                </div>

                <div v-if="canAssign || canUpdateStatus" class="flex flex-col gap-2 sm:flex-row sm:items-stretch">
                  <Button
                    v-if="canUpdateStatus"
                    variant="secondary"
                    class="min-h-[2.75rem] w-full justify-center !border-rose-200 !bg-white !text-rose-700 hover:!bg-rose-50"
                    :loading="rejecting"
                    type="button"
                    @click="onRejectTrip"
                  >
                    {{ t('trip_detail.coordination.reject') }}
                  </Button>
                  <Button
                    v-if="canAssign"
                    class="min-h-[2.75rem] w-full justify-center !bg-sky-600 !py-2.5 text-[15px] font-semibold hover:!bg-sky-700"
                    :loading="assigning"
                    type="button"
                    @click="onApproveTransfer"
                  >
                    {{ t('trip_detail.coordination.approve_transfer') }}
                  </Button>
                </div>
                <p v-else class="text-xs text-slate-500">{{ t('trip_detail.coordination.no_permission_assign') }}</p>
              </div>
            </section>

            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
              <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.attachments.title') }}</h2>
                <div v-if="canManageAttachments && trip.dispatch_request?.id" class="flex flex-wrap items-center gap-2">
                  <input ref="attachInputRef" type="file" class="hidden" @change="onAttachmentFile" />
                  <Button type="button" variant="secondary" class="!px-3" :loading="attachUploading" @click="attachInputRef?.click()">
                    {{ t('trip_detail.attachments.upload') }}
                  </Button>
                </div>
              </div>
              <p v-if="attachMsg" class="mt-2 text-xs" :class="attachMsgIsError ? 'text-rose-600' : 'text-slate-600'">{{ attachMsg }}</p>
              <ul class="mt-3 space-y-2">
                <li
                  v-for="a in attachmentsList"
                  :key="a.id"
                  class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50/50 px-3 py-2"
                >
                  <div class="min-w-0">
                    <div class="truncate text-sm font-medium text-slate-900">{{ a.original_name || t('trip_detail.attachments.unnamed') }}</div>
                    <div class="text-xs text-slate-500">{{ fmtFileSize(a.size_bytes) }}</div>
                  </div>
                  <div class="flex shrink-0 items-center gap-1">
                    <a
                      v-if="a.url"
                      :href="a.url"
                      target="_blank"
                      rel="noopener"
                      class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
                      :download="a.original_name || undefined"
                    >
                      <ArrowDownTrayIcon class="h-5 w-5" />
                    </a>
                    <button
                      v-if="canManageAttachments"
                      type="button"
                      class="flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 bg-white text-rose-600 hover:bg-rose-50 disabled:opacity-50"
                      :disabled="attachDeletingId === a.id"
                      :title="t('trip_detail.attachments.delete')"
                      @click="removeAttachment(a)"
                    >
                      <TrashIcon class="h-5 w-5" />
                    </button>
                  </div>
                </li>
              </ul>
              <p v-if="!attachmentsList.length" class="mt-2 text-sm text-slate-500">{{ t('trip_detail.attachments.empty') }}</p>
            </section>

            <section
              class="overflow-hidden rounded-2xl border border-slate-200/85 bg-white shadow-md shadow-slate-500/5 ring-1 ring-slate-100/90 dark:border-slate-700/80 dark:bg-slate-900/45 dark:shadow-none dark:ring-slate-800/80"
            >
              <div
                class="flex items-center gap-3 border-b border-slate-100/90 bg-gradient-to-r from-indigo-50/90 via-white to-violet-50/50 px-5 py-3.5 dark:from-indigo-950/40 dark:via-slate-900 dark:to-violet-950/30 dark:border-slate-700/80"
              >
                <div
                  class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 shadow-sm dark:bg-indigo-950/70 dark:text-indigo-200"
                >
                  <CalendarDaysIcon class="h-5 w-5" aria-hidden="true" />
                </div>
                <h2 class="text-sm font-bold tracking-tight text-slate-900 dark:text-white">{{ t('trip_detail.timeline.title') }}</h2>
              </div>
              <div class="p-5 sm:p-6">
                <div v-if="timeline.length" class="relative">
                  <div
                    v-if="timeline.length > 1"
                    class="pointer-events-none absolute left-[1.125rem] top-11 bottom-11 w-px bg-gradient-to-b from-indigo-200/90 via-slate-200 to-slate-100 dark:from-indigo-800/80 dark:via-slate-600 dark:to-slate-800"
                    aria-hidden="true"
                  />
                  <div v-for="e in timeline" :key="e.key" class="relative z-[1] flex gap-4 pb-6 last:pb-0">
                    <div
                      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border text-sm font-semibold shadow-sm ring-4 ring-white dark:ring-slate-900"
                      :class="timelineToneClass(e.tone)"
                    >
                      <span class="leading-none">{{ e.icon }}</span>
                    </div>
                    <div
                      class="min-w-0 flex-1 rounded-xl border border-slate-100/90 bg-gradient-to-br from-white to-slate-50/90 px-4 py-3 shadow-sm dark:border-slate-700/80 dark:from-slate-950/40 dark:to-slate-900/60"
                    >
                      <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between sm:gap-3">
                        <div class="min-w-0">
                          <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            {{ e.title }}
                            <span v-if="e.actor" class="text-xs font-normal text-slate-500 dark:text-slate-400"> · {{ e.actor }}</span>
                          </div>
                        </div>
                        <time
                          class="shrink-0 rounded-lg bg-slate-100/90 px-2 py-0.5 text-xs tabular-nums text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                          :datetime="e.at"
                        >
                          {{ fmt(e.at) }}
                        </time>
                      </div>
                      <p v-if="e.subtitle" class="mt-2 border-t border-slate-100/80 pt-2 text-sm leading-relaxed text-slate-600 dark:border-slate-700/80 dark:text-slate-400">
                        {{ e.subtitle }}
                      </p>
                    </div>
                  </div>
                </div>
                <div
                  v-else
                  class="rounded-xl border border-dashed border-slate-200/90 bg-slate-50/50 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/30 dark:text-slate-400"
                >
                  {{ t('trip_detail.timeline.empty') }}
                </div>
              </div>
            </section>

            <!-- Dispatcher notes -->
            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
              <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.notes.title') }}</h2>
              <div class="mt-3 space-y-3">
                <div v-if="tripRequestNotesFromUser" class="rounded-lg border border-amber-100 bg-amber-50 p-3 text-sm text-amber-950">
                  <div class="text-xs font-medium text-amber-800">{{ t('trip_detail.notes.from_request') }}</div>
                  <div class="mt-1 max-h-40 overflow-y-auto whitespace-pre-wrap">{{ tripRequestNotesFromUser }}</div>
                </div>
                <div v-if="tripBm03Display" class="rounded-lg border border-slate-100 bg-slate-50/80 p-3">
                  <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.notes.bm03_title') }}</div>
                  <p class="mt-1 text-xs text-slate-500">{{ t('trip_detail.notes.bm03_hint') }}</p>
                  <div class="mt-2 max-h-[min(28rem,55vh)] overflow-y-auto whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-700 [overflow-wrap:anywhere]">
                    {{ tripBm03Display }}
                  </div>
                </div>
                <div v-for="n in noteEvents" :key="n.id" class="rounded-lg border border-slate-100 bg-slate-50/50 p-3">
                  <div class="flex items-baseline justify-between gap-2">
                    <div class="text-xs font-medium text-slate-700">{{ n.creator?.name ?? t('trip_detail.timeline.system') }}</div>
                    <div class="text-xs text-slate-400">{{ fmt(n.created_at) }}</div>
                  </div>
                  <div class="mt-1 whitespace-pre-wrap text-sm text-slate-800">{{ n.message }}</div>
                </div>
                <div v-if="!noteEvents.length && !tripRequestNotesFromUser && !tripBm03Display" class="text-sm text-slate-500">{{ t('trip_detail.notes.empty') }}</div>
                <div class="pt-1">
                  <div class="text-sm font-semibold text-slate-900">{{ t('trip_detail.notes.add_title') }}</div>
                  <textarea
                    v-model="newNote"
                    rows="3"
                    class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-sky-200 focus:ring"
                    :placeholder="t('trip_detail.notes.placeholder')"
                  />
                  <div class="mt-2 flex flex-wrap items-center gap-2">
                    <Button :loading="noting" :disabled="!newNote.trim()" @click="addNote">{{ t('trip_detail.notes.add_action') }}</Button>
                    <span v-if="noteMsg" class="text-sm text-slate-600">{{ noteMsg }}</span>
                  </div>
                </div>
              </div>
            </section>

            <Card v-if="trip.vehicle || trip.driver || trip.transport_provider" :title="t('trip_detail.assigned.title')">
              <div class="space-y-2 text-sm">
                <div class="flex justify-between gap-2">
                  <span class="text-slate-500">{{ t('trip_detail.assigned.vehicle') }}</span>
                  <span class="font-medium text-slate-900">{{ trip.vehicle?.license_plate ?? '—' }}</span>
                </div>
                <div class="flex justify-between gap-2">
                  <span class="text-slate-500">{{ t('trip_detail.assigned.driver') }}</span>
                  <span class="min-w-0 truncate font-medium text-slate-900">{{ trip.driver?.full_name ?? '—' }}</span>
                </div>
                <div class="flex justify-between gap-2">
                  <span class="text-slate-500">{{ t('trip_detail.assigned.provider') }}</span>
                  <span class="min-w-0 truncate font-medium text-slate-900">{{ trip.transport_provider?.name ?? '—' }}</span>
                </div>
              </div>
            </Card>
          </div>
        </div>
      </div>

      <!-- Map modal -->
      <Teleport to="body">
        <div
          v-if="mapExpanded"
          class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
          role="dialog"
          aria-modal="true"
          @click.self="mapExpanded = false"
        >
          <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
              <span class="text-sm font-semibold text-slate-900">{{ t('trip_detail.route.map_modal_title') }}</span>
              <button
                type="button"
                class="rounded-lg px-2 py-1 text-sm font-medium text-slate-600 hover:bg-slate-100"
                @click="mapExpanded = false"
              >
                {{ t('trip_detail.route.close_map') }}
              </button>
            </div>
            <div class="relative min-h-[60vh] flex-1 bg-slate-100">
              <iframe
                v-if="embedMapSrc"
                :src="embedMapSrc"
                class="absolute inset-0 h-full w-full border-0"
                loading="lazy"
                :title="t('trip_detail.route.map_title')"
              />
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
              <a
                v-if="mapsHref"
                :href="mapsHref"
                target="_blank"
                rel="noopener"
                class="text-sm font-semibold text-sky-700 hover:underline"
              >
                {{ t('trip_detail.route.open_in_google_maps') }}
              </a>
            </div>
          </div>
        </div>
      </Teleport>

      <Teleport to="body">
        <div
          v-if="providerModalOpen"
          class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
          role="dialog"
          aria-modal="true"
          @click.self="providerModalOpen = false"
        >
          <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl">
            <h3 class="text-sm font-semibold text-slate-900">{{ t('trip_detail.coordination.provider_modal_title') }}</h3>
            <p class="mt-1 text-xs text-slate-500">{{ t('trip_detail.coordination.provider_modal_hint') }}</p>
            <div class="mt-4 space-y-3">
              <Input v-model="newProviderName" :label="t('trip_detail.coordination.provider_modal_name')" :placeholder="t('trip_detail.coordination.provider_modal_name_ph')" />
              <Select v-model="newProviderType" :label="t('trip_detail.coordination.provider_modal_type')">
                <option value="taxi">taxi</option>
                <option value="vendor">vendor</option>
              </Select>
              <p v-if="providerModalError" class="text-xs text-rose-600">{{ providerModalError }}</p>
            </div>
            <div class="mt-5 flex justify-end gap-2">
              <Button type="button" variant="secondary" @click="providerModalOpen = false">{{ t('trip_detail.coordination.provider_modal_cancel') }}</Button>
              <Button type="button" :loading="providerCreating" @click="submitQuickProvider">{{ t('trip_detail.coordination.provider_modal_save') }}</Button>
            </div>
          </div>
        </div>
      </Teleport>
    </template>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowPathIcon,
  BanknotesIcon,
  BellIcon,
  CalendarDaysIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/solid'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { addTripEvent, assignTrip, getTrip, rescheduleTrip, updateTripPassengerList, updateTripStatus } from '../../api/trips'
import { submitTripCost } from '../../api/costs'
import { listVehicles, listDrivers, listTransportProviders, createTransportProvider } from '../../api/operational'
import { uploadAttachment, deleteAttachment } from '../../api/attachments'
import { newIdempotencyKey } from '../../util/idempotency'
import { labelTripStatus, labelTripType } from '../../util/labels'
import { formatDispatchRequestNotesForDisplay, isLegacyBm03NotesBlock } from '../../util/formatDispatchNotes'
import { buildBm03BodyFromWizardSnapshot } from '../../util/buildBm03BodyFromSnapshot'
import { parseMoneyVnd } from '../../util/money'
import {
  emptyPassengerRow,
  emptyBusinessRow,
  emptyCargoRow,
  isPassengerRowFilled,
  isBusinessRowFilled,
  isCargoRowFilled,
} from '../../composables/dispatchWizardConstants'
import { useAuthStore } from '../../store'
import { confirmAction } from '../../composables/useConfirm'

/** Simple wheelchair glyph for special-needs hint (Hero lacks a dedicated wheelchair icon in outline set). */
const WheelchairIcon = {
  name: 'WheelchairIcon',
  template:
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm7.94 14.13-1.39-3.47A2 2 0 0 0 16.67 16H13v-2.34c1.81.34 3.72-.37 4.92-2.02l1.14-1.59a1 1 0 0 0-1.62-1.16l-1.15 1.6c-.72 1-1.86 1.51-3.03 1.51h-.61a1 1 0 0 0-.98.8l-2.2 11a1 1 0 1 0 1.96.39l2.03-10.19H16a4 4 0 0 1 3.89 3.05l1.39 3.47a1 1 0 1 0 1.86-.73ZM7 12a5 5 0 1 0 5 5 5 5 0 0 0-5-5Zm0 8a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg>',
}

const route = useRoute()
const { t, te, locale } = useI18n()
const auth = useAuthStore()

function formatApiMessage(e) {
  const d = e?.response?.data
  if (typeof d?.message === 'string' && d.message.trim()) return d.message.trim()
  if (d?.errors && typeof d.errors === 'object') {
    const vals = Object.values(d.errors)
      .flat()
      .filter(Boolean)
    if (vals.length) return String(vals[0])
  }
  const status = e?.response?.status
  if (status === 429) return t('trip_detail.messages.rate_limited')
  return t('trip_detail.messages.error')
}

function defaultVehicleIdForDriver(driverId) {
  if (driverId == null || String(driverId).trim() === '') return null
  const v = vehicles.value.find((x) => x.default_driver && String(x.default_driver.id) === String(driverId))
  return v ? String(v.id) : null
}

const trip = ref(null)
const loading = ref(true)
const loadError = ref('')
const refreshing = ref(false)
const silentLoadError = ref('')
const rescheduleDepartLocal = ref('')
const rescheduling = ref(false)
const rescheduleMsg = ref('')
const rescheduleFeedbackIsError = ref(false)
const costQuickForm = ref({ type: 'fuel', amount: '', description: '' })
const costSubmitting = ref(false)
const costFormMsg = ref('')
const assigning = ref(false)
const rejecting = ref(false)
const assignMsg = ref('')
const assignFeedbackKind = ref('')
const suppressDriverVehicleSync = ref(false)
const needsVehicleResync = ref(false)
const statusing = ref(false)
const vehicles = ref([])
const drivers = ref([])
const resourceHint = ref('')
const vehicleChoice = ref('')
const driverChoice = ref('')
const attachInputRef = ref(null)
const mapExpanded = ref(false)
const hireExternal = ref(false)
const externalVehicleRef = ref('')
const externalDriverRef = ref('')
const coordinationNotes = ref('')
const transportProviders = ref([])
const providerChoice = ref('')
const providerModalOpen = ref(false)
const newProviderName = ref('')
const newProviderType = ref('vendor')
const providerModalError = ref('')
const providerCreating = ref(false)
const attachUploading = ref(false)
const attachDeletingId = ref(null)
const attachMsg = ref('')
const attachMsgIsError = ref(false)

const newNote = ref('')
const noting = ref(false)
const noteMsg = ref('')

const assign = ref({ lock_version: 0, vehicle_id: null, driver_id: null })

const canAssign = computed(() => auth.hasPermission('trip.assign'))
const canUpdateStatus = computed(() => auth.hasPermission('trip.update_status'))
const canManageAttachments = computed(() => auth.hasPermission('attachment.upload'))
const canQuickCreateProvider = computed(() => auth.hasPermission('resource.provider.manage'))
const canSubmitQuickCost = computed(
  () => auth.hasPermission('trip.record.create') || auth.hasPermission('trip.update_status'),
)
const canRescheduleTrip = computed(() => {
  if (!canAssign.value || !trip.value) return false
  if ((trip.value.payment_status ?? 'unpaid') === 'paid') return false
  const s = trip.value.status
  if (s === 'cancelled' || s === 'completed') return false
  return true
})

const passengersEditMode = ref(false)
const passengersEditDraft = ref(null)
const passengersSaving = ref(false)
const passengersEditMsg = ref('')

const canEditPassengerList = computed(
  () => canAssign.value && trip.value?.dispatch_request?.id != null && (trip.value?.payment_status ?? 'unpaid') !== 'paid',
)

const paxEditInputClass =
  'w-full min-w-[6rem] rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm text-slate-900 shadow-sm focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400/30 dark:border-slate-600 dark:bg-slate-900 dark:text-white'

function startPassengersEdit() {
  passengersEditMsg.value = ''
  const dr = trip.value?.dispatch_request
  if (!dr) return
  const tt = dr.trip_type
  const s = dr.wizard_snapshot
  if (tt === 'cargo') {
    const src = Array.isArray(s?.cargoRows) && s.cargoRows.length ? s.cargoRows : [emptyCargoRow()]
    passengersEditDraft.value = { kind: 'cargo', cargoRows: src.map((r) => ({ ...emptyCargoRow(), ...r })) }
  } else if (tt === 'business') {
    const src = Array.isArray(s?.businessRows) && s.businessRows.length ? s.businessRows : [emptyBusinessRow()]
    passengersEditDraft.value = { kind: 'business', businessRows: src.map((r) => ({ ...emptyBusinessRow(), ...r })) }
  } else {
    const src = Array.isArray(s?.passengerRows) && s.passengerRows.length ? s.passengerRows : [emptyPassengerRow()]
    passengersEditDraft.value = { kind: 'passenger', passengerRows: src.map((r) => ({ ...emptyPassengerRow(), ...r })) }
  }
  passengersEditMode.value = true
}

function cancelPassengersEdit() {
  passengersEditMode.value = false
  passengersEditDraft.value = null
  passengersEditMsg.value = ''
}

function addPassengerListRow() {
  const d = passengersEditDraft.value
  if (!d) return
  if (d.kind === 'passenger') d.passengerRows.push(emptyPassengerRow())
  else if (d.kind === 'business') d.businessRows.push(emptyBusinessRow())
  else d.cargoRows.push(emptyCargoRow())
}

function removePassengerListRow(idx) {
  const d = passengersEditDraft.value
  if (!d) return
  if (d.kind === 'passenger') {
    d.passengerRows.splice(idx, 1)
    if (!d.passengerRows.length) d.passengerRows.push(emptyPassengerRow())
  } else if (d.kind === 'business') {
    d.businessRows.splice(idx, 1)
    if (!d.businessRows.length) d.businessRows.push(emptyBusinessRow())
  } else {
    d.cargoRows.splice(idx, 1)
    if (!d.cargoRows.length) d.cargoRows.push(emptyCargoRow())
  }
}

async function submitPassengersEdit() {
  passengersEditMsg.value = ''
  const d = passengersEditDraft.value
  const tid = trip.value?.id
  if (!d || tid == null) return

  let payload = {}
  if (d.kind === 'passenger') {
    const filled = d.passengerRows.filter(isPassengerRowFilled)
    if (!filled.length) {
      passengersEditMsg.value = t('trip_detail.passengers.validation_need_one')
      return
    }
    payload = { passenger_rows: filled }
  } else if (d.kind === 'business') {
    const filled = d.businessRows.filter(isBusinessRowFilled)
    if (!filled.length) {
      passengersEditMsg.value = t('trip_detail.passengers.validation_need_one')
      return
    }
    payload = { business_rows: filled }
  } else {
    const filled = d.cargoRows.filter(isCargoRowFilled)
    if (!filled.length) {
      passengersEditMsg.value = t('trip_detail.passengers.validation_need_one')
      return
    }
    payload = { cargo_rows: filled }
  }

  const ok = await confirmAction({
    title: t('trip_detail.passengers.save_confirm_title'),
    message: t('trip_detail.passengers.save_confirm_body'),
    confirmLabel: t('trip_detail.passengers.save'),
    cancelLabel: t('trip_detail.passengers.cancel_edit'),
  })
  if (!ok) return

  passengersSaving.value = true
  try {
    await updateTripPassengerList(tid, payload)
    await load({ silent: true })
    passengersEditMode.value = false
    passengersEditDraft.value = null
  } catch (e) {
    passengersEditMsg.value = formatApiMessage(e)
  } finally {
    passengersSaving.value = false
  }
}

const slaBanner = computed(() => {
  const tr = trip.value
  if (!tr?.arrive_by) return null
  if (['completed', 'cancelled'].includes(tr.status)) return null
  const end = new Date(tr.arrive_by).getTime()
  if (!Number.isFinite(end)) return null
  const min = Math.round((end - Date.now()) / 60000)
  if (min < 0) return { kind: 'overdue', text: t('trip_detail.sla.overdue_detail', { n: Math.abs(min) }) }
  return { kind: 'ok', text: t('trip_detail.sla.remaining_minutes', { n: min }) }
})

function costTypeLabel(type) {
  const raw = String(type ?? '').trim()
  if (!raw) return '—'
  const slug = raw.toLowerCase().replace(/[^a-z0-9_]/g, '_')
  const key = `trip_detail.costs.type_${slug}`
  if (te(key)) return t(key)
  return raw
}

function fmt(v) {
  const l = locale.value === 'en' ? 'en-US' : 'vi-VN'
  return v ? new Date(v).toLocaleString(l) : '-'
}

function fmtTime(v) {
  const l = locale.value === 'en' ? 'en-US' : 'vi-VN'
  return v ? new Date(v).toLocaleTimeString(l, { hour: '2-digit', minute: '2-digit' }) : '-'
}

function fmtDateLong(v) {
  const l = locale.value === 'en' ? 'en-US' : 'vi-VN'
  return v
    ? new Date(v).toLocaleDateString(l, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
    : '—'
}

function toDatetimeLocalValue(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function fmtFileSize(bytes) {
  if (bytes == null || bytes === '') return ''
  const n = Number(bytes)
  if (!Number.isFinite(n) || n < 0) return ''
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  return `${(n / (1024 * 1024)).toFixed(1)} MB`
}

const tripCode = computed(() => {
  const id = trip.value?.id
  if (!id) return 'TRP-—'
  return `TRP-${String(id).padStart(4, '0')}`
})

const requestRefCode = computed(() => {
  const r = trip.value?.dispatch_request
  if (!r?.id) return '—'
  const d = r.created_at ? new Date(r.created_at) : new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  return `REQ-${y}${m}-${String(r.id).padStart(3, '0')}`
})

const snap = computed(() => trip.value?.dispatch_request?.wizard_snapshot ?? null)

const drNotesRaw = computed(() => trip.value?.dispatch_request?.notes?.trim() ?? '')

const tripRequestNotesFromUser = computed(() => {
  const n = drNotesRaw.value
  if (!n) return ''
  if (isLegacyBm03NotesBlock(n)) return ''
  return formatDispatchRequestNotesForDisplay(n)
})

const tripBm03Display = computed(() => {
  const s = trip.value?.dispatch_request?.wizard_snapshot
  if (s?.form) {
    const b = buildBm03BodyFromWizardSnapshot(s)?.trim()
    if (b) return formatDispatchRequestNotesForDisplay(b)
  }
  const n = drNotesRaw.value
  if (n && isLegacyBm03NotesBlock(n)) return formatDispatchRequestNotesForDisplay(n)
  return ''
})

const originLabel = computed(() => trip.value?.dispatch_request?.origin ?? '—')
const destinationLabel = computed(() => trip.value?.dispatch_request?.destination ?? '—')
const currentLabel = computed(() => {
  if (trip.value?.status === 'in_progress') return t('trip_detail.current_location.en_route')
  return '—'
})

const tripTypeLabel = computed(() => labelTripType(trip.value?.dispatch_request?.trip_type))

const requesterName = computed(() => trip.value?.dispatch_request?.requester?.name ?? '—')

const requesterSubtitle = computed(() => {
  const u = snap.value?.form?.requester_unit
  if (u?.trim()) return u.trim()
  const code = trip.value?.dispatch_request?.requester?.employee_code
  if (code) return t('trip_detail.overview.employee_code', { code })
  return trip.value?.dispatch_request?.requester?.email ?? '—'
})

const requesterInitials = computed(() => {
  const name = requesterName.value.trim()
  if (!name || name === '—') return '?'
  const parts = name.split(/\s+/).filter(Boolean)
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const scheduleDateLong = computed(() => fmtDateLong(trip.value?.depart_at))
const scheduleTimeRange = computed(() => {
  const a = trip.value?.depart_at
  const b = trip.value?.arrive_by
  if (!a) return '—'
  if (!b) return fmtTime(a)
  return `${fmtTime(a)} – ${fmtTime(b)}`
})

const scheduleDuration = computed(() => {
  const a = trip.value?.depart_at
  const b = trip.value?.arrive_by
  if (!a || !b) return ''
  const ms = new Date(b) - new Date(a)
  if (!Number.isFinite(ms) || ms <= 0) return ''
  const h = Math.round((ms / 3600000) * 10) / 10
  return t('trip_detail.overview.duration_hours', { n: h })
})

function scheduleSame(isoA, isoB) {
  if (!isoA || !isoB) return true
  return new Date(isoA).getTime() === new Date(isoB).getTime()
}

const scheduleMismatchNotes = computed(() => {
  const dr = trip.value?.dispatch_request
  const tr = trip.value
  if (!dr || !tr) return []
  const out = []
  if (dr.depart_at && tr.depart_at && !scheduleSame(dr.depart_at, tr.depart_at)) {
    out.push(t('trip_detail.overview.depart_vs_request', { req: fmtTime(dr.depart_at), trip: fmtTime(tr.depart_at) }))
  }
  if (dr.arrive_by && tr.arrive_by && !scheduleSame(dr.arrive_by, tr.arrive_by)) {
    out.push(t('trip_detail.overview.arrive_vs_request', { req: fmtTime(dr.arrive_by), trip: fmtTime(tr.arrive_by) }))
  }
  return out
})

const estimatedDistanceLabel = computed(() => {
  const km = trip.value?.record?.distance_km
  if (km != null && km !== '') return `${km} km`
  return '—'
})

const estimatedDistanceSub = computed(() => {
  const km = trip.value?.record?.distance_km
  if (km != null && km !== '') return t('trip_detail.overview.distance_from_record')
  return t('trip_detail.overview.distance_not_recorded')
})

const estimatedCostSub = computed(() => {
  if (estimatedCostVnd.value != null) return t('trip_detail.overview.cost_from_wizard')
  return t('trip_detail.overview.cost_no_estimate')
})

const estimatedCostLabel = computed(() => {
  const n = estimatedCostVnd.value
  if (n == null || n <= 0) return '—'
  return `${new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'vi-VN').format(n)} VNĐ`
})

const estimatedCostVnd = computed(() => {
  const s = snap.value
  const dr = trip.value?.dispatch_request
  if (!s?.form || !dr) return null

  const f = s.form
  let extras = 0
  if (f.need_porters) extras += parseMoneyVnd(f.porter_cost)
  if (f.interprovincial) extras += parseMoneyVnd(f.interprovincial_cost)
  if (f.e1_use_3plus_days) extras += parseMoneyVnd(f.e1_extra_cost)
  if (f.e2_door_pickup) extras += parseMoneyVnd(f.e2_door_cost)
  if (f.e2_driver_self) extras += parseMoneyVnd(f.e2_driver_self_cost)
  if (f.e2_after_21h) extras += parseMoneyVnd(f.e2_after_21h_cost)

  const rowTotal = (row) => parseMoneyVnd(row.unit_price) + parseMoneyVnd(row.extra_fee)
  const totalPass = (s.passengerRows ?? []).reduce((sum, row) => sum + rowTotal(row), 0)
  const totalBus = (s.businessRows ?? []).reduce((sum, row) => sum + rowTotal(row), 0)
  const cargoCosts = (s.cargoRows ?? []).reduce((sum, row) => sum + parseMoneyVnd(row.cost), 0)

  const total = extras + totalPass + totalBus + cargoCosts
  return total > 0 ? total : null
})

const costsTotalFormatted = computed(() => {
  const list = trip.value?.costs ?? []
  if (!list.length) return '—'
  const cur = list[0]?.currency || 'VND'
  const sum = list.reduce((s, c) => s + (Number(c.amount) || 0), 0)
  return `${new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'vi-VN').format(sum)} ${cur}`
})

function formatCostAmount(amount, currency) {
  const n = Number(amount)
  const c = currency || 'VND'
  if (!Number.isFinite(n)) return `— ${c}`
  return `${new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'vi-VN').format(n)} ${c}`
}

function costStatusClass(s) {
  const x = String(s ?? '').toLowerCase()
  if (x === 'confirmed' || x === 'approved') return 'bg-emerald-100 text-emerald-800'
  if (x === 'rejected') return 'bg-rose-100 text-rose-800'
  if (x === 'pending') return 'bg-amber-100 text-amber-900'
  if (x === 'submitted') return 'bg-sky-100 text-sky-900'
  return 'bg-slate-100 text-slate-700'
}

function pillClassForStatus(s) {
  const map = {
    pending: 'bg-slate-100 text-slate-700',
    approved: 'bg-amber-50 text-amber-800',
    assigned: 'bg-indigo-50 text-indigo-700',
    driver_confirmed: 'bg-amber-50 text-amber-700',
    in_progress: 'bg-emerald-50 text-emerald-700',
    completed: 'bg-emerald-100 text-emerald-800',
    cancelled: 'bg-rose-50 text-rose-700',
    incident: 'bg-rose-50 text-rose-700',
  }
  return map[s] ?? 'bg-slate-100 text-slate-700'
}

function dotClass(state) {
  const base = 'mt-0.5 h-3 w-3 flex-none rounded-full'
  if (state === 'done') return `${base} bg-emerald-500`
  if (state === 'active') return `${base} bg-amber-400`
  if (state === 'blocked') return `${base} bg-rose-400`
  return `${base} bg-slate-300`
}

function stopDotClass(kind) {
  if (kind === 'pickup') return 'bg-emerald-500 text-white ring-2 ring-emerald-200'
  if (kind === 'dropoff') return 'bg-sky-600 text-white ring-2 ring-sky-200'
  return 'bg-slate-200 text-slate-700 ring-2 ring-slate-100'
}

function stopLabel(kind) {
  if (kind === 'pickup') return t('trip_detail.route.stop_pickup')
  if (kind === 'dropoff') return t('trip_detail.route.stop_dropoff')
  return t('trip_detail.route.stop_waypoint')
}

const stepPickup = computed(() => {
  const s = trip.value?.status
  if (s === 'completed') return { state: 'done', label: t('trip_detail.step.completed') }
  if (s === 'in_progress') return { state: 'done', label: t('trip_detail.step.completed') }
  if (s === 'cancelled') return { state: 'blocked', label: t('trip_detail.step.cancelled') }
  return { state: 'active', label: t('trip_detail.step.pending') }
})
const stepCurrent = computed(() => {
  const s = trip.value?.status
  if (s === 'in_progress') return { state: 'active', label: t('trip_detail.step.en_route') }
  if (s === 'completed') return { state: 'done', label: t('trip_detail.step.arrived') }
  if (s === 'cancelled') return { state: 'blocked', label: t('trip_detail.step.cancelled') }
  return { state: 'pending', label: t('trip_detail.step.waiting') }
})
const stepDropoff = computed(() => {
  const s = trip.value?.status
  if (s === 'completed') return { state: 'done', label: t('trip_detail.step.completed') }
  if (s === 'cancelled') return { state: 'blocked', label: t('trip_detail.step.cancelled') }
  if (s === 'in_progress') return { state: 'active', label: t('trip_detail.step.pending') }
  return { state: 'pending', label: t('trip_detail.step.pending') }
})

const mapsHref = computed(() => {
  const o = originLabel.value
  const d = destinationLabel.value
  if (!o || !d || o === '—' || d === '—') return ''
  const u = new URL('https://www.google.com/maps/dir/')
  u.searchParams.set('api', '1')
  u.searchParams.set('origin', o)
  u.searchParams.set('destination', d)
  return u.toString()
})

const embedMapSrc = computed(() => {
  const o = trip.value?.dispatch_request?.origin?.trim()
  const d = trip.value?.dispatch_request?.destination?.trim()
  if (o && d) {
    return `https://maps.google.com/maps?q=${encodeURIComponent(`${o} → ${d}`)}&output=embed`
  }
  const stops = routeStops.value
  if (stops.length >= 2) {
    const first = stops[0].address
    const last = stops[stops.length - 1].address
    return `https://maps.google.com/maps?q=${encodeURIComponent(`${first} → ${last}`)}&output=embed`
  }
  if (stops.length === 1) {
    return `https://maps.google.com/maps?q=${encodeURIComponent(stops[0].address)}&output=embed`
  }
  return ''
})

const routeStops = computed(() => {
  const dr = trip.value?.dispatch_request
  const s = snap.value
  const out = []

  const pushAddr = (addr, kind, lines = []) => {
    const tAddr = (addr ?? '').trim()
    if (!tAddr) return
    const norm = lines.map((x) => String(x).trim()).filter(Boolean)
    const last = out[out.length - 1]
    if (last && last.address === tAddr) {
      for (const ln of norm) {
        if (!last.detailLines.includes(ln)) last.detailLines.push(ln)
      }
      if (kind === 'pickup' || kind === 'dropoff') last.kind = kind
      return
    }
    out.push({ kind, address: tAddr, detailLines: [...norm] })
  }

  if (!dr) return out

  if (dr.trip_type === 'cargo' && s?.cargoRows?.length) {
    let i = 0
    for (const r of s.cargoRows) {
      if (!isCargoRowFilled(r)) continue
      i += 1
      const label = r.name?.trim() || t('trip_detail.passengers.cargo_item', { n: i })
      const pickLines = [label]
      if (r.pickup_contact?.trim()) pickLines.push(t('trip_detail.route.contact', { c: r.pickup_contact.trim() }))
      if (r.item_notes?.trim()) pickLines.push(r.item_notes.trim().slice(0, 100))
      pushAddr(r.pickup_place || r.pickup_contact, 'pickup', pickLines)
      const dropLines = [label]
      if (r.delivery_contact?.trim()) dropLines.push(t('trip_detail.route.contact', { c: r.delivery_contact.trim() }))
      if (r.transport_note?.trim()) dropLines.push(r.transport_note.trim().slice(0, 100))
      pushAddr(r.delivery_place || r.delivery_contact, 'dropoff', dropLines)
    }
    if (!out.length) {
      pushAddr(dr.origin, 'pickup', [])
      pushAddr(dr.destination, 'dropoff', [])
    }
  } else {
    let gidx = 0
    for (const r of s?.passengerRows ?? []) {
      if (!isPassengerRowFilled(r)) continue
      gidx += 1
      const who = r.person_in_charge?.trim() || t('trip_detail.passengers.guest', { n: gidx })
      const base = [t('trip_detail.route.ctx_passenger', { name: who })]
      if (r.notes?.trim()) base.push(r.notes.trim().slice(0, 120))
      pushAddr(r.pickup, 'pickup', base)
      if (r.waypoint?.trim()) pushAddr(r.waypoint, 'waypoint', [who])
      pushAddr(r.dropoff, 'dropoff', base)
    }
    let bidx = 0
    for (const r of s?.businessRows ?? []) {
      if (!isBusinessRowFilled(r)) continue
      bidx += 1
      const who = t('trip_detail.passengers.business_party', { n: bidx })
      const base = [who]
      if (r.notes?.trim()) base.push(r.notes.trim().slice(0, 120))
      pushAddr(r.pickup, 'pickup', base)
      if (r.waypoint?.trim()) pushAddr(r.waypoint, 'waypoint', [who])
      pushAddr(r.dropoff, 'dropoff', base)
    }
    if (!out.length) {
      pushAddr(dr.origin, 'pickup', [])
      pushAddr(dr.destination, 'dropoff', [])
    }
  }

  if (out.length >= 2) {
    out[0].kind = 'pickup'
    out[out.length - 1].kind = 'dropoff'
  }
  return out
})

function inferRoleKind(tripType) {
  if (tripType === 'door_to_door') return 'student'
  if (tripType === 'business') return 'staff'
  if (tripType === 'cargo') return 'cargo'
  return 'guest'
}

function rolePillClass(kind) {
  if (kind === 'staff') return 'bg-slate-100 text-slate-800'
  if (kind === 'student') return 'bg-sky-50 text-sky-800'
  if (kind === 'cargo') return 'bg-amber-50 text-amber-900'
  return 'bg-slate-50 text-slate-700'
}

const passengerRowsDisplay = computed(() => {
  const dr = trip.value?.dispatch_request
  const s = snap.value
  if (!dr) return []

  const tripType = dr.trip_type
  const rows = []

  if (tripType === 'cargo' && s?.cargoRows?.length) {
    let i = 0
    for (const r of s.cargoRows) {
      if (!isCargoRowFilled(r)) continue
      i += 1
      const notes = [r.item_notes, r.transport_note].filter(Boolean).join(' · ')
      rows.push({
        name: r.name?.trim() || t('trip_detail.passengers.cargo_item', { n: i }),
        roleKind: 'cargo',
        roleLabel: t('trip_detail.passengers.role_cargo'),
        contact: r.pickup_contact || r.delivery_contact || '',
        notes,
        flagWheelchair: /xe lăn|wheelchair/i.test(notes),
        flagAllergy: /dị ứng|allergy/i.test(notes),
      })
    }
    return rows
  }

  let idx = 0
  for (const r of s?.passengerRows ?? []) {
    if (!isPassengerRowFilled(r)) continue
    idx += 1
    const name = r.person_in_charge?.trim() || t('trip_detail.passengers.guest', { n: idx })
    const notes = r.notes?.trim() || ''
    const kind = inferRoleKind(tripType)
    rows.push({
      name,
      roleKind: kind,
      roleLabel:
        kind === 'student'
          ? t('trip_detail.passengers.role_student')
          : kind === 'staff'
            ? t('trip_detail.passengers.role_staff')
            : t('trip_detail.passengers.role_guest'),
      contact: '',
      notes,
      flagWheelchair: /xe lăn|wheelchair/i.test(notes),
      flagAllergy: /dị ứng|allergy|đậu phộng|peanut/i.test(notes),
    })
  }

  for (const r of s?.businessRows ?? []) {
    if (!isBusinessRowFilled(r)) continue
    idx += 1
    const notes = r.notes?.trim() || ''
    rows.push({
      name: t('trip_detail.passengers.business_party', { n: idx }),
      roleKind: 'staff',
      roleLabel: t('trip_detail.passengers.role_staff'),
      contact: '',
      notes,
      flagWheelchair: /xe lăn|wheelchair/i.test(notes),
      flagAllergy: /dị ứng|allergy|đậu phộng|peanut/i.test(notes),
    })
  }

  if (!rows.length && dr.passenger_count != null && dr.passenger_count > 0) {
    rows.push({
      name: t('trip_detail.passengers.unlisted', { n: dr.passenger_count }),
      roleKind: inferRoleKind(tripType),
      roleLabel:
        inferRoleKind(tripType) === 'student'
          ? t('trip_detail.passengers.role_student')
          : t('trip_detail.passengers.role_guest'),
      contact: '',
      notes: '',
      flagWheelchair: false,
      flagAllergy: false,
    })
  }

  return rows
})

const specialNeedsSummary = computed(() => {
  const parts = []
  for (const r of passengerRowsDisplay.value) {
    if (r.flagWheelchair) parts.push(t('trip_detail.passengers.summary_wheelchair'))
    if (r.flagAllergy) parts.push(t('trip_detail.passengers.summary_allergy'))
  }
  const uniq = [...new Set(parts)]
  if (uniq.length) return uniq.join(' ')
  const free = tripRequestNotesFromUser.value?.trim()
  if (free && free.length < 400) return free
  return ''
})

const neededSeats = computed(() => {
  const c = trip.value?.dispatch_request?.passenger_count
  const n = c != null ? Number(c) : 0
  if (Number.isFinite(n) && n > 0) return n
  const guests = passengerRowsDisplay.value.length
  return guests > 0 ? guests : 1
})

const suitableVehiclesCount = computed(() => {
  return vehicles.value.filter((v) => (v.seat_count ?? 0) >= neededSeats.value).length
})

const suitableVehiclesHint = computed(() => {
  if (!vehicles.value.length) return ''
  return t('trip_detail.coordination.suitable_vehicles', { n: suitableVehiclesCount.value })
})

const attachmentsList = computed(() => trip.value?.dispatch_request?.attachments ?? [])

const noteEvents = computed(() => {
  const list = trip.value?.events ?? []
  return list
    .filter((e) => (e?.type ?? '') === 'note' && (e?.message ?? '').trim())
    .slice()
    .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

function eventIcon(type) {
  if (type === 'status_change') return '↻'
  if (type === 'note') return '✎'
  if (type === 'assign') return '⛟'
  return '•'
}

function eventTimelineTone(type) {
  if (type === 'status_change') return 'status'
  if (type === 'note') return 'note'
  if (type === 'assign') return 'assign'
  return 'other'
}

function timelineToneClass(tone) {
  const map = {
    create:
      'border-indigo-200/90 bg-indigo-50 text-indigo-800 dark:border-indigo-800/80 dark:bg-indigo-950/60 dark:text-indigo-200',
    status:
      'border-teal-200/90 bg-teal-50 text-teal-900 dark:border-teal-800/80 dark:bg-teal-950/60 dark:text-teal-200',
    note: 'border-violet-200/90 bg-violet-50 text-violet-900 dark:border-violet-800/80 dark:bg-violet-950/60 dark:text-violet-200',
    assign:
      'border-amber-200/90 bg-amber-50 text-amber-950 dark:border-amber-800/80 dark:bg-amber-950/60 dark:text-amber-100',
    other: 'border-slate-200/90 bg-slate-50 text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200',
  }
  return map[tone] ?? map.other
}

function eventTitle(e) {
  if (e.type === 'status_change') {
    const from = e?.data?.from
    const to = e?.data?.to
    if (from && to) return t('trip_detail.timeline.status_change', { from: labelTripStatus(from), to: labelTripStatus(to) })
    return t('trip_detail.timeline.status_change_short')
  }
  if (e.type === 'note') return t('trip_detail.timeline.dispatcher_note')
  return e.type || t('trip_detail.timeline.event')
}

const timeline = computed(() => {
  const items = []
  if (trip.value?.created_at) {
    items.push({
      key: `trip_created_${trip.value.id}`,
      icon: '+',
      tone: 'create',
      title: t('trip_detail.timeline.trip_created'),
      subtitle: trip.value?.dispatch_request?.trip_type ? t('trip_detail.timeline.trip_created_subtitle', { type: tripTypeLabel.value }) : '',
      actor: t('trip_detail.timeline.system'),
      at: trip.value.created_at,
    })
  }
  const ev = trip.value?.events ?? []
  ev.forEach((e) => {
    items.push({
      key: `ev_${e.id}`,
      icon: eventIcon(e.type),
      tone: eventTimelineTone(e.type),
      title: eventTitle(e),
      subtitle: (e.message ?? '').trim(),
      actor: e.creator?.name ?? '',
      at: e.created_at,
    })
  })
  return items
    .filter((x) => x.at)
    .sort((a, b) => new Date(b.at) - new Date(a.at))
})

async function doReschedule() {
  rescheduleMsg.value = ''
  rescheduleFeedbackIsError.value = false
  const raw = rescheduleDepartLocal.value
  if (!raw || !trip.value) return
  const d = new Date(raw)
  if (Number.isNaN(d.getTime())) {
    rescheduleFeedbackIsError.value = true
    rescheduleMsg.value = t('trip_detail.reschedule.invalid')
    return
  }
  rescheduling.value = true
  try {
    await rescheduleTrip(route.params.id, {
      depart_at: d.toISOString(),
      lock_version: assign.value.lock_version ?? 0,
    })
    rescheduleFeedbackIsError.value = false
    rescheduleMsg.value = t('trip_detail.reschedule.success')
    await load({ silent: true })
    rescheduleDepartLocal.value = toDatetimeLocalValue(trip.value?.depart_at)
  } catch (e) {
    rescheduleFeedbackIsError.value = true
    rescheduleMsg.value = formatApiMessage(e)
  } finally {
    rescheduling.value = false
  }
}

async function submitQuickCost() {
  if (!canSubmitQuickCost.value) return
  costFormMsg.value = ''
  const type = String(costQuickForm.value.type ?? '')
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '_')
  const amount = Number(costQuickForm.value.amount)
  if (!type || !Number.isFinite(amount) || amount <= 0) {
    costFormMsg.value = t('trip_detail.costs.quick_invalid')
    return
  }
  costSubmitting.value = true
  try {
    await submitTripCost(
      route.params.id,
      {
        type,
        amount,
        currency: 'VND',
        description: costQuickForm.value.description?.trim() || undefined,
      },
      { idempotencyKey: newIdempotencyKey() },
    )
    costQuickForm.value = { type: costQuickForm.value.type, amount: '', description: '' }
    costFormMsg.value = t('trip_detail.messages.ok')
    await load({ silent: true })
  } catch (e) {
    costFormMsg.value = e?.response?.data?.message ?? t('trip_detail.messages.error')
  } finally {
    costSubmitting.value = false
  }
}

async function loadTransportProvidersList() {
  try {
    const res = await listTransportProviders({ is_active: true, per_page: 200 })
    transportProviders.value = res.items ?? []
  } catch {
    transportProviders.value = []
  }
}

function openProviderModal() {
  providerModalError.value = ''
  newProviderName.value = ''
  newProviderType.value = 'vendor'
  providerModalOpen.value = true
}

async function submitQuickProvider() {
  providerModalError.value = ''
  const name = newProviderName.value.trim()
  if (!name) {
    providerModalError.value = t('trip_detail.coordination.provider_modal_name_required')
    return
  }
  providerCreating.value = true
  try {
    const created = await createTransportProvider({ name, type: newProviderType.value, is_active: true })
    await loadTransportProvidersList()
    if (created?.id != null) providerChoice.value = String(created.id)
    providerModalOpen.value = false
  } catch (e) {
    providerModalError.value = e?.response?.data?.message ?? t('trip_detail.messages.error')
  } finally {
    providerCreating.value = false
  }
}

async function removeAttachment(a) {
  if (!canManageAttachments.value) return
  const ok = await confirmAction({
    title: t('trip_detail.attachments.delete_confirm_title'),
    message: t('trip_detail.attachments.delete_confirm_body', { name: a.original_name || t('trip_detail.attachments.unnamed') }),
    confirmLabel: t('trip_detail.attachments.delete'),
    danger: true,
  })
  if (!ok) return
  attachMsg.value = ''
  attachDeletingId.value = a.id
  try {
    await deleteAttachment(a.id)
    await load({ silent: true })
    attachMsg.value = t('trip_detail.attachments.deleted_ok')
    attachMsgIsError.value = false
  } catch (e) {
    attachMsg.value = e?.response?.data?.message ?? t('trip_detail.messages.error')
    attachMsgIsError.value = true
  } finally {
    attachDeletingId.value = null
  }
}

async function onAttachmentFile(ev) {
  const input = ev.target
  const file = input.files?.[0]
  if (input) input.value = ''
  if (!file || !trip.value?.dispatch_request?.id) return
  attachMsg.value = ''
  attachUploading.value = true
  try {
    await uploadAttachment({
      attachable_type: 'dispatch_request',
      attachable_id: trip.value.dispatch_request.id,
      kind: 'request_attachment',
      file,
    })
    await load({ silent: true })
    attachMsg.value = t('trip_detail.attachments.upload_ok')
    attachMsgIsError.value = false
  } catch (e) {
    attachMsg.value = e?.response?.data?.message ?? t('trip_detail.messages.error')
    attachMsgIsError.value = true
  } finally {
    attachUploading.value = false
  }
}

async function loadResources() {
  resourceHint.value = ''
  try {
    const [vr, dr, pr] = await Promise.allSettled([
      listVehicles({ status: 'ready', per_page: 150 }),
      listDrivers({ employment_status: 'active', availability_status: 'available', per_page: 150 }),
      listTransportProviders({ is_active: true, per_page: 200 }),
    ])
    if (vr.status === 'fulfilled') {
      vehicles.value = vr.value.items ?? []
    } else {
      resourceHint.value = t('trip_detail.ops.messages.vehicles_load_failed')
    }
    if (dr.status === 'fulfilled') {
      drivers.value = dr.value.items ?? []
    } else {
      resourceHint.value = resourceHint.value || t('trip_detail.ops.messages.drivers_load_failed')
    }
    if (pr.status === 'fulfilled') {
      transportProviders.value = pr.value.items ?? []
    }
  } catch {
    resourceHint.value = t('trip_detail.ops.messages.resources_load_failed')
  }
}

async function load(opts = {}) {
  const silent = opts.silent === true
  needsVehicleResync.value = false
  if (!silent) {
    loading.value = true
    loadError.value = ''
  } else {
    silentLoadError.value = ''
    refreshing.value = true
  }
  try {
    const data = await getTrip(route.params.id)
    trip.value = data
    assign.value.lock_version = trip.value.lock_version ?? 0
    suppressDriverVehicleSync.value = true
    try {
      vehicleChoice.value = trip.value.vehicle_id ? String(trip.value.vehicle_id) : ''
      driverChoice.value = trip.value.driver_id ? String(trip.value.driver_id) : ''
      await nextTick()
    } finally {
      suppressDriverVehicleSync.value = false
    }
    hireExternal.value = !!trip.value.transport_provider_id
    externalVehicleRef.value = trip.value.external_vehicle_ref ?? ''
    externalDriverRef.value = trip.value.external_driver_ref ?? ''
    providerChoice.value = trip.value.transport_provider_id ? String(trip.value.transport_provider_id) : ''
    rescheduleDepartLocal.value = toDatetimeLocalValue(trip.value.depart_at)
    coordinationNotes.value = ''
    await loadResources()
    if (!silent) loadError.value = ''
  } catch (e) {
    const msg = e?.response?.data?.message ?? t('trip_detail.load_error')
    if (!silent) {
      trip.value = null
      loadError.value = msg
    } else {
      silentLoadError.value = msg
    }
  } finally {
    if (!silent) loading.value = false
    refreshing.value = false
  }
}

async function onApproveTransfer() {
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

  assigning.value = true
  try {
    const payload = { lock_version: assign.value.lock_version }
    if (vehicleChoice.value) payload.vehicle_id = Number(vehicleChoice.value)
    if (driverChoice.value) payload.driver_id = Number(driverChoice.value)
    if (hireExternal.value && providerChoice.value) {
      payload.transport_provider_id = Number(providerChoice.value)
    } else {
      payload.transport_provider_id = null
    }
    if (externalVehicleRef.value?.trim()) payload.external_vehicle_ref = externalVehicleRef.value.trim()
    if (externalDriverRef.value?.trim()) payload.external_driver_ref = externalDriverRef.value.trim()

    await assignTrip(route.params.id, payload, { idempotencyKey: newIdempotencyKey() })

    const note = coordinationNotes.value.trim()
    if (note) {
      try {
        await addTripEvent(route.params.id, { type: 'note', message: note })
      } catch {
        /* non-fatal */
      }
    }

    assignFeedbackKind.value = 'success'
    assignMsg.value = t('trip_detail.coordination.assign_success')
    await load({ silent: true })
  } catch (e) {
    assignFeedbackKind.value = 'error'
    assignMsg.value = formatApiMessage(e)
  } finally {
    assigning.value = false
  }
}

async function onRejectTrip() {
  if (!canUpdateStatus.value) return
  const ok = await confirmAction({
    title: t('trip_detail.coordination.reject_confirm_title'),
    message: t('trip_detail.coordination.reject_confirm_body'),
    confirmLabel: t('trip_detail.coordination.reject'),
    cancelLabel: t('trip_detail.coordination.reject_cancel'),
    danger: true,
  })
  if (!ok) return

  assignMsg.value = ''
  assignFeedbackKind.value = ''
  rejecting.value = true
  try {
    const msg = coordinationNotes.value.trim() || undefined
    await updateTripStatus(route.params.id, { status: 'cancelled', message: msg })
    assignFeedbackKind.value = 'success'
    assignMsg.value = t('trip_detail.coordination.reject_success')
    await load({ silent: true })
  } catch (e) {
    assignFeedbackKind.value = 'error'
    assignMsg.value = formatApiMessage(e)
  } finally {
    rejecting.value = false
  }
}

async function doStatus() {
  if (!canUpdateStatus.value) return
  statusing.value = true
  try {
    await updateTripStatus(route.params.id, statusForm.value)
    await load({ silent: true })
  } finally {
    statusing.value = false
  }
}

async function addNote() {
  noteMsg.value = ''
  noting.value = true
  try {
    await addTripEvent(route.params.id, { type: 'note', message: newNote.value.trim() })
    newNote.value = ''
    noteMsg.value = t('trip_detail.messages.ok')
    await load({ silent: true })
  } catch (e) {
    noteMsg.value = e?.response?.data?.message ?? t('trip_detail.messages.error')
  } finally {
    noting.value = false
  }
}

const statusForm = ref({ status: 'in_progress', message: '' })

watch(hireExternal, (on) => {
  if (!on) providerChoice.value = ''
  if (on) needsVehicleResync.value = false
})

function syncVehicleToDriver(driverId) {
  if (suppressDriverVehicleSync.value || hireExternal.value) return
  const id = driverId ?? driverChoice.value
  if (!id) {
    needsVehicleResync.value = false
    return
  }
  const vid = defaultVehicleIdForDriver(id)
  if (vid) {
    vehicleChoice.value = vid
    needsVehicleResync.value = false
  } else {
    needsVehicleResync.value = true
  }
}

watch(driverChoice, (id) => syncVehicleToDriver(id), { flush: 'sync' })

watch(vehicles, () => {
  if (!needsVehicleResync.value) return
  syncVehicleToDriver()
})

onMounted(load)
watch(
  () => route.params.id,
  () => {
    passengersEditMode.value = false
    passengersEditDraft.value = null
    passengersEditMsg.value = ''
    load()
  },
)
</script>
