<template>
  <div class="min-h-screen bg-[#F8F9FA]">
    <div v-if="loading" class="px-4 py-12 text-center text-sm text-slate-500">{{ t('trip_detail.loading') }}</div>

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
            <RouterLink
              to="/notifications"
              class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50"
              :title="t('trip_detail.header.notifications')"
            >
              <BellIcon class="h-5 w-5" />
            </RouterLink>
            <Button variant="secondary" class="!px-3" @click="copyLink">{{ t('trip_detail.actions.copy_link') }}</Button>
            <Button variant="secondary" class="!px-3" @click="exportJson">{{ t('trip_detail.actions.export') }}</Button>
          </div>
          <p v-if="linkMsg" class="w-full text-right text-xs text-slate-600 sm:order-last">{{ linkMsg }}</p>
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
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                    <div class="text-xs font-medium text-slate-500">{{ t('trip_detail.overview.est_distance') }}</div>
                    <div class="mt-1 text-lg font-semibold tabular-nums text-slate-900">{{ estimatedDistanceLabel }}</div>
                  </div>
                  <div class="rounded-xl border border-slate-100 bg-white p-4 shadow-sm">
                    <div class="text-xs font-medium text-slate-500">{{ t('trip_detail.overview.est_cost') }}</div>
                    <div class="mt-1 text-lg font-semibold tabular-nums text-slate-900">{{ estimatedCostLabel }}</div>
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
              <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.route.section_title') }}</h2>
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
                <RouterLink
                  v-if="trip.dispatch_request?.id"
                  :to="`/requests/${trip.dispatch_request.id}`"
                  class="text-sm font-semibold text-sky-700 hover:text-sky-800 hover:underline"
                >
                  {{ t('trip_detail.passengers.edit_hint') }}
                </RouterLink>
              </div>
              <div class="mt-4 overflow-x-auto rounded-xl border border-slate-100">
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
              </div>
              <div
                v-if="specialNeedsSummary"
                class="mt-4 rounded-xl border border-sky-100 bg-sky-50/80 px-4 py-3 text-sm text-sky-950"
              >
                <div class="text-xs font-bold uppercase tracking-wide text-sky-800/80">{{ t('trip_detail.passengers.special_summary_title') }}</div>
                <p class="mt-1 whitespace-pre-wrap">{{ specialNeedsSummary }}</p>
              </div>
            </section>

            <!-- Costs & advanced status -->
            <details class="group rounded-2xl border border-slate-200/80 bg-white shadow-sm open:shadow-md">
              <summary
                class="cursor-pointer list-none px-5 py-4 text-sm font-semibold text-slate-900 marker:hidden [&::-webkit-details-marker]:hidden"
              >
                <span class="flex items-center justify-between gap-2">
                  {{ t('trip_detail.more_section.title') }}
                  <ChevronDownIcon class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" />
                </span>
              </summary>
              <div class="space-y-5 border-t border-slate-100 px-5 pb-5 pt-4">
                <div>
                  <div class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.costs.title') }}</div>
                  <div class="mt-2 space-y-2 text-sm">
                    <div v-for="c in trip.costs ?? []" :key="c.id" class="flex justify-between border-b border-slate-50 py-2">
                      <span class="min-w-0 truncate">{{ c.type }} · {{ c.status }}</span>
                      <span class="font-medium tabular-nums">{{ c.amount }} {{ c.currency }}</span>
                    </div>
                    <div v-if="!(trip.costs ?? []).length" class="text-slate-500">{{ t('trip_detail.costs.empty') }}</div>
                  </div>
                </div>
                <form class="grid gap-3 sm:grid-cols-3" @submit.prevent="doStatus">
                  <Select v-model="statusForm.status" :label="t('trip_detail.status_update.status')" :placeholder="t('trip_detail.status_update.pick')">
                    <option value="driver_confirmed">{{ labelTripStatus('driver_confirmed') }}</option>
                    <option value="in_progress">{{ labelTripStatus('in_progress') }}</option>
                    <option value="completed">{{ labelTripStatus('completed') }}</option>
                    <option value="cancelled">{{ labelTripStatus('cancelled') }}</option>
                  </Select>
                  <div class="sm:col-span-2">
                    <Input v-model="statusForm.message" :label="t('trip_detail.status_update.note')" />
                  </div>
                  <div class="sm:col-span-3 flex items-center gap-3">
                    <Button v-if="canUpdateStatus" :loading="statusing" type="submit">{{ t('trip_detail.status_update.update') }}</Button>
                    <span v-else class="text-xs text-slate-500">{{ t('trip_detail.coordination.no_permission_status') }}</span>
                  </div>
                </form>
              </div>
            </details>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <section ref="coordinationEl" class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
              <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.coordination.title') }}</h2>
              <p class="mt-1 text-sm text-slate-600">{{ t('trip_detail.coordination.subtitle') }}</p>

              <div class="mt-4 space-y-4">
                <div>
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
                  <p v-if="suitableVehiclesHint" class="mt-1.5 text-xs font-medium text-emerald-700">{{ suitableVehiclesHint }}</p>
                </div>
                <Select v-model="driverChoice" :label="t('trip_detail.coordination.assign_driver')" :placeholder="t('trip_detail.ops.form.pick_driver')">
                  <option value="">{{ t('trip_detail.ops.form.keep_or_clear') }}</option>
                  <option v-for="d in drivers" :key="d.id" :value="String(d.id)">
                    {{ d.full_name }} {{ d.phone ? `· ${d.phone}` : '' }}
                  </option>
                </Select>

                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-800">
                  <input v-model="hireExternal" type="checkbox" class="rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                  {{ t('trip_detail.coordination.hire_external') }}
                </label>

                <div v-if="hireExternal" class="grid gap-3 rounded-xl border border-amber-100 bg-amber-50/50 p-3">
                  <Input v-model.number="assign.transport_provider_id" :label="t('trip_detail.coordination.provider_id')" type="number" />
                  <Input v-model="externalVehicleRef" :label="t('trip_detail.coordination.external_vehicle')" />
                  <Input v-model="externalDriverRef" :label="t('trip_detail.coordination.external_driver')" />
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

                <div v-if="resourceHint" class="text-xs text-amber-800">{{ resourceHint }}</div>
                <div v-if="assignMsg" class="text-sm text-slate-600">{{ assignMsg }}</div>

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
              <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.attachments.title') }}</h2>
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
                  <a
                    v-if="a.url"
                    :href="a.url"
                    target="_blank"
                    rel="noopener"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
                    :download="a.original_name || undefined"
                  >
                    <ArrowDownTrayIcon class="h-5 w-5" />
                  </a>
                </li>
              </ul>
              <p v-if="!attachmentsList.length" class="mt-2 text-sm text-slate-500">{{ t('trip_detail.attachments.empty') }}</p>
            </section>

            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
              <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.timeline.title') }}</h2>
              <div class="mt-4 space-y-4">
                <div v-for="e in timeline" :key="e.key" class="flex gap-3">
                  <div
                    class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-600"
                  >
                    {{ e.icon }}
                  </div>
                  <div class="min-w-0 flex-1 border-b border-slate-50 pb-4 last:border-0">
                    <div class="flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:justify-between">
                      <div class="text-sm font-medium text-slate-900">
                        {{ e.title }}
                        <span v-if="e.actor" class="text-xs font-normal text-slate-500">· {{ e.actor }}</span>
                      </div>
                      <div class="text-xs text-slate-500">{{ fmt(e.at) }}</div>
                    </div>
                    <div v-if="e.subtitle" class="mt-1 text-sm text-slate-600">{{ e.subtitle }}</div>
                  </div>
                </div>
                <div v-if="!timeline.length" class="text-sm text-slate-500">{{ t('trip_detail.timeline.empty') }}</div>
              </div>
            </section>

            <!-- Dispatcher notes -->
            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
              <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.notes.title') }}</h2>
              <div class="mt-3 space-y-3">
                <div v-if="trip.dispatch_request?.notes" class="rounded-lg border border-amber-100 bg-amber-50 p-3 text-sm text-amber-950">
                  <div class="text-xs font-medium text-amber-800">{{ t('trip_detail.notes.from_request') }}</div>
                  <div class="mt-1 max-h-40 overflow-y-auto whitespace-pre-wrap">{{ trip.dispatch_request.notes }}</div>
                </div>
                <div v-for="n in noteEvents" :key="n.id" class="rounded-lg border border-slate-100 bg-slate-50/50 p-3">
                  <div class="flex items-baseline justify-between gap-2">
                    <div class="text-xs font-medium text-slate-700">{{ n.creator?.name ?? t('trip_detail.timeline.system') }}</div>
                    <div class="text-xs text-slate-400">{{ fmt(n.created_at) }}</div>
                  </div>
                  <div class="mt-1 whitespace-pre-wrap text-sm text-slate-800">{{ n.message }}</div>
                </div>
                <div v-if="!noteEvents.length && !trip.dispatch_request?.notes" class="text-sm text-slate-500">{{ t('trip_detail.notes.empty') }}</div>
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

            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
              <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.quick.title') }}</h2>
              <div class="mt-3 grid gap-2">
                <a
                  v-if="requesterPhone"
                  class="inline-flex items-center justify-center rounded-lg bg-sky-600 px-3 py-2.5 text-sm font-medium text-white hover:bg-sky-700"
                  :href="`tel:${requesterPhone}`"
                >
                  {{ t('trip_detail.quick.call_requester') }}
                </a>
                <a
                  v-if="trip.driver?.phone"
                  class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50"
                  :href="`tel:${trip.driver.phone}`"
                >
                  {{ t('trip_detail.quick.call_driver') }}
                </a>
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50"
                  @click="scrollToCoordination"
                >
                  {{ t('trip_detail.quick.reassign') }}
                </button>
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
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  BellIcon,
  CalendarDaysIcon,
  ChevronDownIcon,
} from '@heroicons/vue/24/outline'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/solid'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { addTripEvent, assignTrip, getTrip, updateTripStatus } from '../../api/trips'
import { listVehicles, listDrivers } from '../../api/operational'
import { newIdempotencyKey } from '../../util/idempotency'
import { labelTripStatus, labelTripType } from '../../util/labels'
import { parseMoneyVnd } from '../../util/money'
import {
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
const { t, locale } = useI18n()
const auth = useAuthStore()

const trip = ref(null)
const loading = ref(true)
const assigning = ref(false)
const rejecting = ref(false)
const assignMsg = ref('')
const statusing = ref(false)
const vehicles = ref([])
const drivers = ref([])
const resourceHint = ref('')
const vehicleChoice = ref('')
const driverChoice = ref('')
const coordinationEl = ref(null)
const linkMsg = ref('')
const mapExpanded = ref(false)
const hireExternal = ref(false)
const externalVehicleRef = ref('')
const externalDriverRef = ref('')
const coordinationNotes = ref('')

const newNote = ref('')
const noting = ref(false)
const noteMsg = ref('')

const assign = ref({ lock_version: 0, vehicle_id: null, driver_id: null, transport_provider_id: null })

const canAssign = computed(() => auth.hasPermission('trip.assign'))
const canUpdateStatus = computed(() => auth.hasPermission('trip.update_status'))

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

const originLabel = computed(() => trip.value?.dispatch_request?.origin ?? '—')
const destinationLabel = computed(() => trip.value?.dispatch_request?.destination ?? '—')
const currentLabel = computed(() => {
  if (trip.value?.status === 'in_progress') return t('trip_detail.current_location.en_route')
  return '—'
})

const tripTypeLabel = computed(() => labelTripType(trip.value?.dispatch_request?.trip_type))

const requesterName = computed(() => trip.value?.dispatch_request?.requester?.name ?? '—')
const requesterPhone = computed(() => trip.value?.dispatch_request?.requester?.phone ?? '')

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

const estimatedDistanceLabel = computed(() => {
  const km = trip.value?.record?.distance_km
  if (km != null && km !== '') return `${km} km`
  return '—'
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

  const pushAddr = (addr, kind) => {
    const tAddr = (addr ?? '').trim()
    if (!tAddr) return
    const last = out[out.length - 1]
    if (last && last.address === tAddr) return
    out.push({ kind, address: tAddr })
  }

  if (!dr) return out

  if (dr.trip_type === 'cargo' && s?.cargoRows?.length) {
    for (const r of s.cargoRows) {
      if (!isCargoRowFilled(r)) continue
      pushAddr(r.pickup_place || r.pickup_contact, 'pickup')
      pushAddr(r.delivery_place || r.delivery_contact, 'dropoff')
    }
    if (!out.length) {
      pushAddr(dr.origin, 'pickup')
      pushAddr(dr.destination, 'dropoff')
    }
  } else {
    const rows = [...(s?.passengerRows ?? []), ...(s?.businessRows ?? [])]
    for (const r of rows) {
      const filled = 'waypoint' in r ? isBusinessRowFilled(r) : isPassengerRowFilled(r)
      if (!filled) continue
      pushAddr(r.pickup, 'pickup')
      if (r.waypoint?.trim()) pushAddr(r.waypoint, 'waypoint')
      pushAddr(r.dropoff, 'dropoff')
    }
    if (!out.length) {
      pushAddr(dr.origin, 'pickup')
      pushAddr(dr.destination, 'dropoff')
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
  const free = trip.value?.dispatch_request?.notes?.trim()
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

function scrollToCoordination() {
  coordinationEl.value?.scrollIntoView?.({ behavior: 'smooth', block: 'start' })
}

async function copyLink() {
  linkMsg.value = ''
  try {
    await navigator.clipboard.writeText(window.location.href)
    linkMsg.value = t('trip_detail.messages.copied')
  } catch {
    linkMsg.value = t('trip_detail.messages.copy_failed')
  }
}

function exportJson() {
  const blob = new Blob([JSON.stringify(trip.value, null, 2)], { type: 'application/json;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `${tripCode.value}.json`
  a.click()
  URL.revokeObjectURL(url)
}

async function loadResources() {
  resourceHint.value = ''
  try {
    const [vr, dr] = await Promise.allSettled([
      listVehicles({ status: 'ready', per_page: 150 }),
      listDrivers({ employment_status: 'active', availability_status: 'available', per_page: 150 }),
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
  } catch {
    resourceHint.value = t('trip_detail.ops.messages.resources_load_failed')
  }
}

async function load() {
  loading.value = true
  try {
    trip.value = await getTrip(route.params.id)
    assign.value.lock_version = trip.value.lock_version ?? 0
    vehicleChoice.value = trip.value.vehicle_id ? String(trip.value.vehicle_id) : ''
    driverChoice.value = trip.value.driver_id ? String(trip.value.driver_id) : ''
    hireExternal.value = !!trip.value.transport_provider_id
    externalVehicleRef.value = trip.value.external_vehicle_ref ?? ''
    externalDriverRef.value = trip.value.external_driver_ref ?? ''
    assign.value.transport_provider_id = trip.value.transport_provider_id ?? null
    coordinationNotes.value = ''
    await loadResources()
  } finally {
    loading.value = false
  }
}

async function onApproveTransfer() {
  assignMsg.value = ''
  const hasInternal = vehicleChoice.value && driverChoice.value
  const hasExternal =
    hireExternal.value && assign.value.transport_provider_id != null && String(assign.value.transport_provider_id).trim() !== ''

  if (!hasInternal && !hasExternal) {
    assignMsg.value = t('trip_detail.coordination.validation_assign')
    return
  }
  if (hireExternal.value && !hasExternal) {
    assignMsg.value = t('trip_detail.coordination.validation_provider')
    return
  }
  if (!hireExternal.value && !hasInternal) {
    assignMsg.value = t('trip_detail.coordination.validation_assign')
    return
  }

  assigning.value = true
  try {
    const payload = { lock_version: assign.value.lock_version }
    if (vehicleChoice.value) payload.vehicle_id = Number(vehicleChoice.value)
    if (driverChoice.value) payload.driver_id = Number(driverChoice.value)
    if (hireExternal.value && assign.value.transport_provider_id != null && assign.value.transport_provider_id !== '') {
      payload.transport_provider_id = Number(assign.value.transport_provider_id)
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

    assignMsg.value = t('trip_detail.messages.ok')
    await load()
  } catch (e) {
    assignMsg.value = e?.response?.data?.message ?? t('trip_detail.messages.error')
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

  rejecting.value = true
  try {
    const msg = coordinationNotes.value.trim() || undefined
    await updateTripStatus(route.params.id, { status: 'cancelled', message: msg })
    await load()
  } catch (e) {
    assignMsg.value = e?.response?.data?.message ?? t('trip_detail.messages.error')
  } finally {
    rejecting.value = false
  }
}

async function doStatus() {
  if (!canUpdateStatus.value) return
  statusing.value = true
  try {
    await updateTripStatus(route.params.id, statusForm.value)
    await load()
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
    await load()
  } catch (e) {
    noteMsg.value = e?.response?.data?.message ?? t('trip_detail.messages.error')
  } finally {
    noting.value = false
  }
}

const statusForm = ref({ status: 'in_progress', message: '' })

onMounted(load)
watch(() => route.params.id, load)
</script>
