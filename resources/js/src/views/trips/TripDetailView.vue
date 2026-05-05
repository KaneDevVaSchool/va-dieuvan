<template>
  <div class="min-h-screen bg-[#F8F9FA]">
    <div v-if="loading && !trip" class="mx-auto max-w-7xl space-y-6 px-4 py-10">
      <div class="animate-pulse space-y-4">
        <div class="h-10 max-w-md rounded-xl bg-slate-200/90" />
        <div class="grid gap-6 xl:grid-cols-12">
          <div class="min-w-0 space-y-4 xl:col-span-7">
            <div class="h-64 rounded-2xl bg-slate-200/80" />
            <div class="h-48 rounded-2xl bg-slate-200/70" />
            <div class="h-56 rounded-2xl bg-slate-200/70" />
          </div>
          <div class="min-w-0 space-y-4 xl:col-span-5">
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
      <StickyTripHeader
        :trip="trip"
        :can-approve="canAssign"
        :can-reject="canUpdateStatus"
        :refreshing="refreshing"
        :assign-disabled="assigning || !assignReady"
        @back="router.push(tripsListPath)"
        @refresh="load({ silent: true })"
        @approve="onApproveTransfer"
        @reject="onRejectTrip"
      />
      <p class="mx-auto max-w-7xl px-4 pt-1 text-xs text-slate-500">{{ t('trip_detail.created_at', { time: fmt(trip.created_at) }) }}</p>
      <p
        v-if="silentLoadError"
        class="mx-auto max-w-7xl px-4 pt-2 text-xs text-amber-900"
      >
        {{ silentLoadError }}
      </p>
      <div class="mx-auto max-w-7xl space-y-6 px-4 pb-12 pt-3">
        <div
          v-if="trip.dispatch_request && trip.dispatch_request.status === 'pending'"
          class="rounded-xl border border-amber-200 bg-amber-50/90 px-4 py-3 text-sm text-amber-900"
        >
          {{ t('trip_detail.banner.request_pending') }}
          <RouterLink
            v-if="trip.dispatch_request?.id && auth.canAccessDispatchWebApp()"
            :to="`/requests/${trip.dispatch_request.id}`"
            class="ml-1 font-semibold underline decoration-amber-700/40 underline-offset-2"
          >
            {{ t('trip_detail.banner.open_request') }}
          </RouterLink>
        </div>

        <div class="grid gap-6 xl:grid-cols-12">
          <!-- Main column (7/12) -->
          <div class="min-w-0 space-y-6 xl:col-span-7">
            <TripInfoCard
              :trip="trip"
              :countdown="countdown"
              :passenger-count="Number(trip.dispatch_request?.passenger_count ?? 0)"
              :schedule-date-long="scheduleDateLong"
              :schedule-time-range="scheduleTimeRange"
              :schedule-duration="scheduleDuration"
              :schedule-mismatch-notes="scheduleMismatchNotes"
              :estimated-distance-label="estimatedDistanceLabel"
              :estimated-distance-sub="estimatedDistanceSub"
              :estimated-cost-label="estimatedCostLabel"
              :estimated-cost-sub="estimatedCostSub"
              :requester-initials="requesterInitials"
              :requester-name="requesterName"
              :requester-subtitle="requesterSubtitle"
              :trip-type-label="tripTypeLabel"
              :sla-banner="slaBanner"
              :step-pickup="stepPickup"
              :step-current="stepCurrent"
              :step-dropoff="stepDropoff"
              :origin-label="originLabel"
              :destination-label="destinationLabel"
              :current-label="currentLabel"
              :route-stops="routeStops"
              :embed-map-src="embedMapSrc"
              :expand-map="expandTripMap"
            />

            <PassengerCheckIn
              :trip-id="trip.id"
              :trip="trip"
              :rows="passengerRowsDisplay"
              :can-check-in="canPassengerCheckIn"
              :can-edit-list="canEditPassengerList"
              :edit-mode="passengersEditMode"
              :edit-message="passengersEditMsg"
              :special-summary="specialNeedsSummary"
              @start-edit="startPassengersEdit"
              @trip-updated="applyTripPayload"
            >
              <template #editor>
                <template v-if="passengersEditMode && passengersEditDraft">
                  <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/80 px-3 py-2">
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
                  </div>
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
              </template>
            </PassengerCheckIn>

            <TripTimeline :current-status="timelineWorkflowStatus" :logs="activityLogs" />

            <div class="grid gap-6 lg:grid-cols-12">
              <div class="min-w-0 lg:col-span-7">
                <CostTracker
                  :trip-id="trip.id"
                  :costs="trip.costs ?? []"
                  :can-submit="canSubmitQuickCost"
                  :show-costs-link="auth.canAccessDispatchWebApp()"
                  @updated="load({ silent: true })"
                />
              </div>

              <!-- Trạng thái chuyến -->
              <div class="min-w-0 lg:col-span-5">
                <section
                  class="overflow-hidden rounded-2xl bg-white print:break-inside-avoid dark:bg-slate-900/45"
                  :aria-label="t('trip_detail.status_block.title')"
                >
                  <div
                    class="border-b border-teal-100/90 bg-teal-50/95 px-5 py-4 sm:px-6 dark:border-teal-900/40 dark:bg-teal-950/35"
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
                    <form class="rounded-xl bg-white p-5 dark:bg-slate-900/80" @submit.prevent="doStatus">
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
            </div>
          </div>

          <!-- Sidebar / coordination (5/12) -->
          <div class="min-w-0 space-y-4 xl:col-span-5">
            <section
              class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-md ring-1 ring-slate-100/80 print:hidden"
              :aria-label="t('trip_detail.coordination.title')"
            >
              <div class="border-b border-slate-100/90 bg-slate-50 px-4 py-3 sm:px-4 dark:border-slate-700/80 dark:bg-slate-900/80">
                <div class="flex flex-wrap items-start justify-between gap-2">
                  <div class="min-w-0">
                    <h2 class="text-sm font-bold tracking-tight text-slate-900">{{ t('trip_detail.coordination.title') }}</h2>
                    <p class="mt-1 text-xs leading-relaxed text-slate-600">{{ t('trip_detail.coordination.subtitle') }}</p>
                  </div>
                  <Button
                    v-if="canAssign"
                    type="button"
                    variant="secondary"
                    class="!shrink-0 !px-2.5 !py-1.5 !text-xs"
                    :loading="sameDayTripsLoading || refreshing"
                    :disabled="sameDayTripsLoading"
                    @click="refreshCoordinationData"
                  >
                    {{ t('trip_detail.coordination.refresh_schedule') }}
                  </Button>
                </div>
              </div>

              <div class="space-y-4 p-4 sm:p-4">
                <TripCoordinationQuickActions
                  :can-duplicate="canAssign && !!trip?.dispatch_request?.id"
                  :can-export-pdf="!!trip?.dispatch_request?.id && auth.canAccessDispatchWebApp()"
                  :duplicating="duplicatingTrip"
                  :pdf-loading="pdfExportLoading"
                  @duplicate="onDuplicateTrip"
                  @export-pdf="onExportTripPdf"
                  @copy-link="onCopyTripLink"
                />
                <div
                  v-if="canRescheduleTrip"
                  class="rounded-xl border border-indigo-200/80 bg-indigo-50/90 p-3 shadow-sm dark:border-indigo-800/50 dark:bg-indigo-950/40"
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

                <div class="rounded-xl border border-slate-200/80 bg-slate-50/40 p-3 shadow-sm">
                  <p class="mt-1 text-xs text-slate-600">{{ t('trip_detail.coordination.driver_default_vehicle_hint') }}</p>
                  <p v-if="coordinationScheduleHint" class="mt-2 rounded-lg border border-slate-200/80 bg-white/80 px-2.5 py-1.5 text-[11px] font-medium text-slate-700">
                    {{ coordinationScheduleHint }}
                  </p>
                  <p
                    v-if="schedulePreviewDirty"
                    class="mt-2 rounded-lg border border-sky-200/90 bg-sky-50/90 px-2.5 py-1.5 text-[11px] font-medium text-sky-900"
                  >
                    {{ t('trip_detail.coordination.preview_window_hint') }}
                  </p>
                  <p v-if="sameDayTripsLoading" class="mt-2 text-[11px] text-slate-500">{{ t('trip_detail.coordination.schedule_loading') }}</p>
                  <p v-if="sameDayTripsError" class="mt-2 text-[11px] text-rose-700">{{ sameDayTripsError }}</p>
                  <p v-if="busyResourcesHint && dispatchResources?.mode !== 'external'" class="mt-2 text-[11px] text-amber-800">{{ busyResourcesHint }}</p>
                  <VehicleCard
                    v-if="showInternalVehicleCard && selectedVehicleForCard"
                    :vehicle="selectedVehicleForCard"
                    :busy="vehicleCardBusy"
                    @change="onVehicleCardChange"
                  />
                  <ConflictBanner :conflict="vehicleConflictBanner" @pick-again="onVehicleConflictPickAgain" @keep-anyway="onVehicleConflictKeep" />
                  <DriverCard
                    v-if="showInternalDriverCard && selectedDriverForCard"
                    :driver="selectedDriverForCard"
                    :busy="driverCardBusy"
                    @change="onDriverCardChange"
                  />
                  <ResourcePanel
                    v-if="trip?.id"
                    ref="resourcePanelRef"
                    :trip-id="trip.id"
                    :available-count="suitableVehiclesCount"
                    :busy-vehicle-ids="busyVehicleIdList"
                    :busy-driver-ids="busyDriverIdList"
                    :trip-snapshot="coordinationTripSnapshot"
                    :can-quick-create-vendor="canQuickCreateProvider"
                    :hide-internal-vehicle-section="showInternalVehicleCard"
                    :hide-internal-driver-section="showInternalDriverCard"
                    @update:resources="onDispatchResourcesUpdate"
                    @create-vendor="openProviderModal"
                  />
                  <p v-if="suitableVehiclesHint" class="mt-2 text-xs font-medium text-emerald-800">{{ suitableVehiclesHint }}</p>
                  <p v-if="selectedVehicleSeatsWarning" class="mt-2 text-xs font-medium text-rose-700">{{ selectedVehicleSeatsWarning }}</p>
                </div>

                <div
                  v-if="canAssign && overlappingOtherTrips.length"
                  class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-sm"
                >
                  <div class="text-[10px] font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.coordination.overlap_section_title') }}</div>
                  <ul class="mt-2 max-h-40 space-y-1.5 overflow-y-auto text-[11px]">
                    <li v-for="row in overlappingOtherTrips" :key="row.id" class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                      <RouterLink :to="tripDetailPathFor(row.id)" class="font-semibold text-sky-700 underline-offset-2 hover:underline">
                        #{{ row.id }}
                      </RouterLink>
                      <span class="tabular-nums text-slate-600">{{ fmtTime(row.depart_at) }}</span>
                      <span class="min-w-0 text-slate-700">{{ row.label }}</span>
                      <span v-if="row.driverName || row.vehiclePlate" class="text-slate-500">
                        <template v-if="row.driverName">{{ row.driverName }}</template>
                        <template v-if="row.driverName && row.vehiclePlate"> · </template>
                        <template v-if="row.vehiclePlate">{{ row.vehiclePlate }}</template>
                      </span>
                    </li>
                  </ul>
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

                <p v-if="!canAssign && !canUpdateStatus" class="text-xs text-slate-500">{{ t('trip_detail.coordination.no_permission_assign') }}</p>
              </div>
            </section>

            <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
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

            <!-- Dispatcher notes -->
            <section class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
              <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.notes.title') }}</h2>
              <div class="mt-3 space-y-3">
                <div v-if="tripRequestNotesFromUser" class="rounded-lg border border-amber-100 bg-amber-50 p-3 text-sm text-amber-950">
                  <div class="text-xs font-medium text-amber-800">{{ t('trip_detail.notes.from_request') }}</div>
                  <div class="mt-1 max-h-40 overflow-y-auto whitespace-pre-wrap">{{ tripRequestNotesFromUser }}</div>
                </div>
                <div v-for="n in noteEvents" :key="n.id" class="rounded-lg border border-slate-100 bg-slate-50/50 p-3">
                  <div class="flex items-baseline justify-between gap-2">
                    <div class="text-xs font-medium text-slate-700">{{ n.creator?.name ?? t('trip_detail.timeline.system') }}</div>
                    <div class="text-xs text-slate-400">{{ fmt(n.created_at) }}</div>
                  </div>
                  <div class="mt-1 whitespace-pre-wrap text-sm text-slate-800">{{ n.message }}</div>
                </div>
                <div v-if="!noteEvents.length && !tripRequestNotesFromUser" class="text-sm text-slate-500">{{ t('trip_detail.notes.empty') }}</div>
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
                <option value="taxi">{{ t('resources.provider_form_type_taxi') }}</option>
                <option value="vendor">{{ t('resources.provider_form_type_vendor') }}</option>
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
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import ResourcePanel from '../../components/dispatch/ResourcePanel.vue'
import ConflictBanner from '../../components/trips/ConflictBanner.vue'
import CostTracker from '../../components/trips/CostTracker.vue'
import DriverCard from '../../components/trips/DriverCard.vue'
import PassengerCheckIn from '../../components/trips/PassengerCheckIn.vue'
import StickyTripHeader from '../../components/trips/StickyTripHeader.vue'
import TripCoordinationQuickActions from '../../components/trips/TripCoordinationQuickActions.vue'
import TripTimeline from '../../components/trips/TripTimeline.vue'
import TripInfoCard from '../../components/trips/TripInfoCard.vue'
import { addTripEvent, assignTrip, duplicateTrip, getTrip, listTrips, rescheduleTrip, updateTripPassengerList, updateTripStatus } from '../../api/trips'
import { exportDispatchRequestPdf } from '../../api/requests'
import { listVehicles, createTransportProvider, listDrivers, getVehicleScheduleConflicts } from '../../api/operational'
import { uploadAttachment, deleteAttachment } from '../../api/attachments'
import { newIdempotencyKey } from '../../util/idempotency'
import { labelTripStatus } from '../../util/labels'
import { formatDispatchRequestNotesForDisplay, isLegacyBm03NotesBlock } from '../../util/formatDispatchNotes'
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
import { buildStaffPrefixedPath as staffPath } from '../../config/dispatchWebBase'
import { confirmAction } from '../../composables/useConfirm'
import { showAppSuccess, showAppError } from '../../composables/appMessage'
import { useTripDetail } from '../../composables/useTripDetail'

const route = useRoute()
const router = useRouter()
const { t, locale } = useI18n()
const auth = useAuthStore()

const tripsListPath = computed(() =>
  route.path.startsWith('/driver') ? '/driver/schedule' : staffPath('/trips'),
)
function tripDetailPathFor(id) {
  return route.path.startsWith('/driver') ? `/driver/trips/${id}` : staffPath(`/trips/${id}`)
}

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

const trip = ref(null)

const {
  countdown,
  tripTypeLabel,
  slaBanner,
  requesterName,
  requesterSubtitle,
  requesterInitials,
  scheduleDateLong,
  scheduleTimeRange,
  scheduleDuration,
  scheduleMismatchNotes,
  originLabel,
  destinationLabel,
  currentLabel,
  stepPickup,
  stepCurrent,
  stepDropoff,
} = useTripDetail(trip)
const loading = ref(true)
const loadError = ref('')
const refreshing = ref(false)
const silentLoadError = ref('')
const rescheduleDepartLocal = ref('')
const rescheduling = ref(false)
const rescheduleMsg = ref('')
const rescheduleFeedbackIsError = ref(false)
const assigning = ref(false)
const rejecting = ref(false)
const assignMsg = ref('')
const assignFeedbackKind = ref('')
const statusing = ref(false)
const vehicles = ref([])
const driversList = ref([])
const vehicleScheduleConflict = ref(null)
const suppressVehicleScheduleConflict = ref(false)
const duplicatingTrip = ref(false)
const pdfExportLoading = ref(false)
const sameDayTrips = ref([])
const sameDayTripsLoading = ref(false)
const sameDayTripsError = ref('')

const TRIP_ASSIGN_CONFLICT_STATUSES = ['assigned', 'driver_confirmed', 'in_progress']
const resourceHint = ref('')
const attachInputRef = ref(null)
const mapExpanded = ref(false)
function expandTripMap() {
  mapExpanded.value = true
}
const coordinationNotes = ref('')
const resourcePanelRef = ref(null)
const dispatchResources = ref(null)
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

const canPassengerCheckIn = computed(() => {
  if (!trip.value) return false
  const s = trip.value.status
  if (s === 'cancelled' || s === 'completed') return false
  return canAssign.value || canUpdateStatus.value
})

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

function fmt(v) {
  const l = locale.value === 'en' ? 'en-US' : 'vi-VN'
  return v ? new Date(v).toLocaleString(l) : '-'
}

function fmtTime(v) {
  const l = locale.value === 'en' ? 'en-US' : 'vi-VN'
  return v ? new Date(v).toLocaleTimeString(l, { hour: '2-digit', minute: '2-digit' }) : '-'
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
      const pickupAddress = [r.pickup_place, r.pickup_contact].filter(Boolean).join(' · ') || ''
      rows.push({
        name: r.name?.trim() || t('trip_detail.passengers.cargo_item', { n: i }),
        roleKind: 'cargo',
        roleLabel: t('trip_detail.passengers.role_cargo'),
        contact: r.pickup_contact || r.delivery_contact || '',
        notes,
        pickupAddress,
        flagWheelchair: /xe lăn|wheelchair/i.test(notes),
        flagAllergy: /dị ứng|allergy/i.test(notes),
      })
    }
    return rows.map((r, i) => ({
      ...r,
      passengerKey: `row_${i}`,
    }))
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
      pickupAddress: (r.pickup ?? '').trim(),
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
      pickupAddress: '',
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
      pickupAddress: '',
      flagWheelchair: false,
      flagAllergy: false,
    })
  }

  return rows.map((r, i) => ({
    ...r,
    passengerKey: `row_${i}`,
  }))
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
  const n = suitableVehiclesCount.value
  if (!vehicles.value.length || n < 1) return ''
  return t('trip_detail.coordination.suitable_vehicles', { n })
})

function toLocalDateKey(iso) {
  if (!iso) return ''
  const x = new Date(iso)
  if (Number.isNaN(x.getTime())) return ''
  const y = x.getFullYear()
  const m = String(x.getMonth() + 1).padStart(2, '0')
  const day = String(x.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

/** Cùng logic backend DispatchingService: kết thúc kế hoạch = arrive_by hoặc depart + 2h */
function tripPlannedEnd(t) {
  const dr = t?.dispatch_request
  const endIso = t?.arrive_by ?? dr?.arrive_by
  if (endIso) return new Date(endIso)
  const s = new Date(t.depart_at)
  return new Date(s.getTime() + 2 * 60 * 60 * 1000)
}

function tripIntervalsOverlap(aStart, aEnd, bStart, bEnd) {
  return aStart < bEnd && bStart < aEnd
}

function getActiveScheduleDateKey() {
  const raw = rescheduleDepartLocal.value?.trim()
  if (raw) {
    const d = new Date(raw)
    if (!Number.isNaN(d.getTime())) return toLocalDateKey(d)
  }
  return toLocalDateKey(trip.value?.depart_at)
}

const scheduleDateKeyForList = computed(() => {
  if (!trip.value?.depart_at) return ''
  return getActiveScheduleDateKey()
})

const scheduleWindowForConflicts = computed(() => {
  const tr = trip.value
  if (!tr?.depart_at) return null
  const origStart = new Date(tr.depart_at).getTime()
  const origEnd = tripPlannedEnd(tr).getTime()
  const duration = Math.max(30 * 60 * 1000, origEnd - origStart)
  let startMs = origStart
  const raw = rescheduleDepartLocal.value?.trim()
  if (raw) {
    const d = new Date(raw)
    if (!Number.isNaN(d.getTime())) startMs = d.getTime()
  }
  const endMs = startMs + duration
  if (!Number.isFinite(startMs) || !Number.isFinite(endMs)) return null
  return { start: startMs, end: endMs }
})

const schedulePreviewDirty = computed(() => {
  const tr = trip.value
  if (!tr?.depart_at || !rescheduleDepartLocal.value?.trim()) return false
  return toDatetimeLocalValue(tr.depart_at) !== rescheduleDepartLocal.value.trim()
})

const busyDriverIds = computed(() => {
  const w = scheduleWindowForConflicts.value
  const cur = trip.value
  if (!w || !cur?.id) return new Set()
  const busy = new Set()
  for (const o of sameDayTrips.value) {
    if (!o?.id || o.id === cur.id) continue
    if (!o.driver_id) continue
    if (!TRIP_ASSIGN_CONFLICT_STATUSES.includes(o.status)) continue
    const oStart = new Date(o.depart_at).getTime()
    const oEnd = tripPlannedEnd(o).getTime()
    if (tripIntervalsOverlap(w.start, w.end, oStart, oEnd)) busy.add(o.driver_id)
  }
  return busy
})

const busyVehicleIds = computed(() => {
  const w = scheduleWindowForConflicts.value
  const cur = trip.value
  if (!w || !cur?.id) return new Set()
  const busy = new Set()
  for (const o of sameDayTrips.value) {
    if (!o?.id || o.id === cur.id) continue
    if (!o.vehicle_id) continue
    if (!TRIP_ASSIGN_CONFLICT_STATUSES.includes(o.status)) continue
    const oStart = new Date(o.depart_at).getTime()
    const oEnd = tripPlannedEnd(o).getTime()
    if (tripIntervalsOverlap(w.start, w.end, oStart, oEnd)) busy.add(o.vehicle_id)
  }
  return busy
})

const busyVehicleIdList = computed(() => [...busyVehicleIds.value])
const busyDriverIdList = computed(() => [...busyDriverIds.value])

const coordinationTripSnapshot = computed(() => {
  if (!trip.value?.id) return null
  return {
    tripId: trip.value.id,
    vehicleId: trip.value.vehicle_id ?? null,
    driverId: trip.value.driver_id ?? null,
    transportProviderId: trip.value.transport_provider_id ?? null,
    externalVehicleRef: trip.value.external_vehicle_ref ?? '',
    externalDriverRef: trip.value.external_driver_ref ?? '',
    lockVersion: trip.value.lock_version ?? 0,
  }
})

function onDispatchResourcesUpdate(p) {
  dispatchResources.value = p
}

const overlappingOtherTrips = computed(() => {
  const w = scheduleWindowForConflicts.value
  const cur = trip.value
  if (!w || !cur?.id) return []
  const out = []
  for (const o of sameDayTrips.value) {
    if (!o?.id || o.id === cur.id) continue
    if (!TRIP_ASSIGN_CONFLICT_STATUSES.includes(o.status)) continue
    const oStart = new Date(o.depart_at).getTime()
    const oEnd = tripPlannedEnd(o).getTime()
    if (!tripIntervalsOverlap(w.start, w.end, oStart, oEnd)) continue
    const dr = o.dispatch_request
    const label = dr ? `${dr.origin || '—'} → ${dr.destination || '—'}` : '—'
    out.push({
      id: o.id,
      depart_at: o.depart_at,
      label,
      driverName: o.driver?.full_name ?? '',
      vehiclePlate: o.vehicle?.license_plate ?? '',
    })
  }
  out.sort((a, b) => new Date(a.depart_at) - new Date(b.depart_at))
  return out
})

const selectedVehicleSeatsWarning = computed(() => {
  const p = dispatchResources.value
  if (!p || p.mode !== 'internal' || !p.primaryVehicleId) return ''
  const v = vehicles.value.find((x) => String(x.id) === String(p.primaryVehicleId))
  if (!v) return ''
  const n = v.seat_count ?? 0
  if (n >= neededSeats.value) return ''
  return t('trip_detail.coordination.vehicle_seats_warning', { n, need: neededSeats.value })
})

const assignReady = computed(() => {
  if (!canAssign.value) return false
  const p = dispatchResources.value
  if (!p?.readyForSubmit) return false
  if (p.mode === 'internal') {
    if (p.driver_id && busyDriverIds.value.has(Number(p.driver_id))) return false
    if (p.vehicle_id && busyVehicleIds.value.has(Number(p.vehicle_id))) return false
    const v = vehicles.value.find((x) => Number(x.id) === Number(p.vehicle_id))
    if (v && (v.seat_count ?? 0) < neededSeats.value) return false
  }
  return true
})

const showInternalVehicleCard = computed(
  () => dispatchResources.value?.mode === 'internal' && dispatchResources.value?.vehicle_id != null,
)
const showInternalDriverCard = computed(
  () => dispatchResources.value?.mode === 'internal' && dispatchResources.value?.driver_id != null,
)

const selectedVehicleForCard = computed(() => {
  const id = dispatchResources.value?.vehicle_id
  if (id == null) return null
  const v = vehicles.value.find((x) => Number(x.id) === Number(id))
  return v ?? trip.value?.vehicle ?? null
})

const selectedDriverForCard = computed(() => {
  const id = dispatchResources.value?.driver_id
  if (id == null) return null
  const d = driversList.value.find((x) => Number(x.id) === Number(id))
  return d ?? trip.value?.driver ?? null
})

const vehicleCardBusy = computed(() => {
  const id = dispatchResources.value?.vehicle_id
  return id != null && busyVehicleIds.value.has(Number(id))
})

const driverCardBusy = computed(() => {
  const id = dispatchResources.value?.driver_id
  return id != null && busyDriverIds.value.has(Number(id))
})

const vehicleConflictBanner = computed(() => {
  if (suppressVehicleScheduleConflict.value) return null
  return vehicleScheduleConflict.value
})

watch(
  () => [trip.value?.id, dispatchResources.value?.primaryVehicleId],
  async ([tid, vid]) => {
    vehicleScheduleConflict.value = null
    suppressVehicleScheduleConflict.value = false
    if (!tid || !vid || !canAssign.value) return
    try {
      const data = await getVehicleScheduleConflicts(Number(vid), { trip_id: tid, exclude_trip: tid })
      vehicleScheduleConflict.value = data?.conflict ?? null
    } catch {
      vehicleScheduleConflict.value = null
    }
  },
)

const coordinationScheduleHint = computed(() => {
  if (!trip.value?.depart_at) return ''
  const r = scheduleTimeRange.value
  const d = scheduleDateLong.value
  if (!r || r === '—') return d ? `${d}` : ''
  return t('trip_detail.coordination.schedule_window_hint', { date: d, range: r })
})

const busyResourcesHint = computed(() => {
  if (!busyDriverIds.value.size && !busyVehicleIds.value.size) return ''
  return t('trip_detail.coordination.busy_resources_hint')
})

async function loadSameDayTrips() {
  const key = scheduleDateKeyForList.value
  if (!trip.value?.id || !key) {
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

async function refreshCoordinationData() {
  await Promise.all([loadResources(), loadSameDayTrips()])
}

function applyTripPayload(data) {
  if (!data) return
  trip.value = data
  assign.value.lock_version = trip.value.lock_version ?? 0
}

async function onDuplicateTrip() {
  const tid = trip.value?.id
  if (tid == null || !canAssign.value) return
  duplicatingTrip.value = true
  try {
    const res = await duplicateTrip(tid)
    showAppSuccess(t('trip_detail.quick.duplicate_ok'), '')
    const rid = res?.dispatch_request_id
    if (rid != null) router.push(staffPath(`/requests/${rid}`))
  } catch (e) {
    showAppError(formatApiMessage(e))
  } finally {
    duplicatingTrip.value = false
  }
}

async function onExportTripPdf() {
  const id = trip.value?.dispatch_request?.id
  if (id == null) return
  pdfExportLoading.value = true
  try {
    const blob = await exportDispatchRequestPdf(id)
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank', 'noopener')
    setTimeout(() => URL.revokeObjectURL(url), 60_000)
  } catch (e) {
    showAppError(formatApiMessage(e) || t('trip_detail.quick.pdf_error'))
  } finally {
    pdfExportLoading.value = false
  }
}

async function onCopyTripLink() {
  const url = typeof window !== 'undefined' ? window.location.href : ''
  if (!url) return
  try {
    await navigator.clipboard.writeText(url)
    showAppSuccess(t('trip_detail.quick.copied'), '')
  } catch {
    showAppError(t('trip_detail.messages.error'))
  }
}

function onVehicleCardChange() {
  vehicleScheduleConflict.value = null
  suppressVehicleScheduleConflict.value = false
  resourcePanelRef.value?.clearInternalVehicle?.()
}

function onDriverCardChange() {
  resourcePanelRef.value?.clearInternalDriver?.()
}

function onVehicleConflictPickAgain() {
  suppressVehicleScheduleConflict.value = false
  vehicleScheduleConflict.value = null
  resourcePanelRef.value?.clearInternalVehicle?.()
}

function onVehicleConflictKeep() {
  suppressVehicleScheduleConflict.value = true
  vehicleScheduleConflict.value = null
}

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

const timelineWorkflowStatus = computed(() => {
  const s = trip.value?.status
  const m = {
    pending: 'created',
    approved: 'approved',
    assigned: 'assigned',
    driver_confirmed: 'assigned',
    in_progress: 'running',
    completed: 'completed',
    cancelled: 'created',
    incident: 'created',
  }
  return m[s] ?? 'created'
})

const activityLogs = computed(() => {
  const logs = []
  if (trip.value?.created_at) {
    logs.push({ status: 'created', created_at: trip.value.created_at, actor_name: '' })
  }
  const ev = trip.value?.events ?? []
  for (const e of ev) {
    if (e?.type === 'status_change' && e?.data?.to) {
      const st = e.data.to
      const keyMap = {
        pending: 'created',
        approved: 'approved',
        assigned: 'assigned',
        driver_confirmed: 'assigned',
        in_progress: 'running',
        completed: 'completed',
        cancelled: 'created',
        incident: 'created',
      }
      logs.push({
        status: keyMap[st] ?? 'created',
        created_at: e.created_at,
        actor_name: e.creator?.name ?? '',
      })
    }
  }
  return logs
})

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
    await resourcePanelRef.value?.refreshOptions?.()
    if (created?.id != null) {
      resourcePanelRef.value?.pickProvider?.(created.id, newProviderType.value)
    }
    providerModalOpen.value = false
  } catch (e) {
    providerModalError.value = e?.response?.data?.message ?? t('trip_detail.messages.error')
  } finally {
    providerCreating.value = false
  }
}

function openProviderModal() {
  providerModalError.value = ''
  newProviderName.value = ''
  newProviderType.value = 'vendor'
  providerModalOpen.value = true
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
    const [vr, dr] = await Promise.all([
      listVehicles({ status: 'ready', per_page: 150 }),
      listDrivers({ employment_status: 'active', availability_status: 'available', per_page: 150 }),
    ])
    vehicles.value = vr.items ?? []
    driversList.value = dr.items ?? []
  } catch {
    resourceHint.value = t('trip_detail.ops.messages.vehicles_load_failed')
    vehicles.value = []
    driversList.value = []
  }
}

async function load(opts = {}) {
  const silent = opts.silent === true
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
  const ok = resourcePanelRef.value?.validate?.()
  if (!ok) {
    assignFeedbackKind.value = 'error'
    assignMsg.value = t('trip_detail.coordination.validation_assign')
    return
  }

  const p = dispatchResources.value
  if (!p?.readyForSubmit) {
    assignFeedbackKind.value = 'error'
    assignMsg.value = t('trip_detail.coordination.validation_assign')
    return
  }

  if (p.mode === 'internal') {
    if (p.driver_id && busyDriverIds.value.has(Number(p.driver_id))) {
      assignFeedbackKind.value = 'error'
      assignMsg.value = t('trip_detail.coordination.validation_busy_driver')
      return
    }
    if (p.vehicle_id && busyVehicleIds.value.has(Number(p.vehicle_id))) {
      assignFeedbackKind.value = 'error'
      assignMsg.value = t('trip_detail.coordination.validation_busy_vehicle')
      return
    }
  }

  assigning.value = true
  try {
    const payload = {
      lock_version: assign.value.lock_version,
      vehicle_id: p.vehicle_id,
      driver_id: p.driver_id,
      transport_provider_id: p.transport_provider_id,
      external_vehicle_ref: p.external_vehicle_ref,
      external_driver_ref: p.external_driver_ref,
    }

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

watch(
  () => [trip.value?.id, scheduleDateKeyForList.value],
  async ([id, key]) => {
    if (!id || !key) {
      sameDayTrips.value = []
      sameDayTripsError.value = ''
      return
    }
    await loadSameDayTrips()
  },
  { flush: 'post' },
)

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
