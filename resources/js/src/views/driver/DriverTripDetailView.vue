<template>
  <div
    class="driver-trip-detail mx-auto flex min-h-full w-full max-w-lg flex-col bg-driver-bg text-driver-ink sm:pb-0"
  >
    <DriverTripDetailHeader
      :trip="trip"
      :more-open="moreOpen"
      :status-badge-class="statusBadgeClass"
      :header-status-text="headerStatusText"
      :schedule-date-line="scheduleDateLine"
      :schedule-time-line="scheduleTimeLine"
      :stats-student-count="statsStudentCount"
      :stats-distance="statsDistance"
      :stats-duration="statsDuration"
      :warning-banner="warningBanner"
      :dispatcher-phone="dispatcherPhone"
      @toggle-menu="moreOpen = !moreOpen"
      @close-menu="moreOpen = false"
      @refresh="onRefreshMenu"
    />

    <div
      class="relative z-10 -mt-3 flex-1 space-y-3 pb-44 pl-[max(theme(spacing.3),env(safe-area-inset-left))] pr-[max(theme(spacing.3),env(safe-area-inset-right))] sm:pb-12 sm:pl-[max(theme(spacing.4),env(safe-area-inset-left))] sm:pr-[max(theme(spacing.4),env(safe-area-inset-right))]"
    >
      <p
        v-if="loadError"
        class="mt-3 rounded-xl border border-amber-700/50 bg-amber-950/40 px-3 py-2 text-sm leading-snug text-amber-100 ring-1 ring-amber-600/30 sm:text-base"
      >
        {{ loadError }}
      </p>

      <DriverTripDetailSkeleton v-if="loading && !trip" />

      <div v-else-if="trip" class="driver-stagger space-y-3">
        <DriverTripRouteSection
          :origin-main="originMain"
          :origin-sub="originSub"
          :dest-main="destMain"
          :dest-sub="destSub"
          :map-url="mapUrl"
          :legs="driverRouteLegs"
          :schedule-summary="scheduleTimeLine"
          :distance-label="statsDistance"
          :passenger-count="paxDisplayTotal"
          :trip-type-label="routeTripTypeLabel"
          :notes-preview="routeNotesPreview"
        />

        <DriverTripPaxSection
          :pax-kind="paxKind"
          :pax-list="paxList"
          :displayed-pax-list="displayedPaxList"
          :pax-section-total="paxDisplayTotal"
          :expanded-student-idx="expandedStudentIdx"
          :is-paused="isPaused"
          :can-mark-pickup="canMarkPickup"
          :event-posting="eventPosting"
          :row-state="rowState"
          :is-next-index="isNextIndex"
          @toggle-student="toggleStudent"
          @set-row-state="(i, st) => setRowState(i, st)"
        />

        <button
          v-if="DRIVER_KM_SECTION_ENABLED"
          type="button"
          class="flex min-h-[52px] w-full items-center justify-between gap-2 rounded-2xl bg-driver-card px-4 py-3.5 text-left ring-1 ring-white/[0.06] active:bg-driver-surface/30"
          @click="openKmModal()"
        >
          <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-driver-elevated text-driver-muted">
              <ChartBarIcon class="h-4 w-4" aria-hidden="true" />
            </div>
            <div class="min-w-0">
              <p class="text-base font-semibold text-driver-ink sm:text-lg">{{ t('driver_trip_detail.km_row_title') }}</p>
              <p v-if="!hasEndOdometer" class="text-sm text-amber-500 sm:text-base">{{ t('driver_trip_detail.km_end_missing') }}</p>
              <p v-else class="text-sm text-driver-muted sm:text-base">{{ t('driver_trip_detail.km_end_value', { km: formatKm(trip.record.end_odometer_km) }) }}</p>
            </div>
          </div>
          <ChevronRightIcon class="h-5 w-5 shrink-0 text-driver-muted/60" aria-hidden="true" />
        </button>

        <DriverTripCostsSection
          :trip-costs="tripCosts"
          :costs-approved-total="costsApprovedTotal"
          :costs-pending-total="costsPendingTotal"
          :can-add-cost="canAddCost"
          :show-post-trip-cost-link="canAddPostTripCost"
          :trip-id="tripId"
          :format-vnd="formatVnd"
          :cost-type-label="costTypeLabel"
          :cost-status-label="costStatusLabel"
          :format-cost-time="formatCostTime"
          @open-modal="openCostModal"
        />
      </div>
    </div>

    <DriverTripBottomBar
      :trip-status="trip?.status"
      :show-pickup-bar="showPickupBar"
      :picked-count="pickedCount"
      :pax-total="paxDisplayTotal"
      :is-paused="isPaused"
      :can-start="canStart"
      :can-end-trip="canEndTrip"
      :action-busy="actionBusy"
      @toggle-pause="isPaused = !isPaused"
      @start-trip="startTrip"
      @end-trip="onEndTrip"
    />

    <DriverTripDetailModals
      :km-modal-open="kmModalOpen"
      :cost-modal-open="costModalOpen"
      :start-km-display="formatKmInput(startKmModel)"
      :end-km="endKmModel"
      :km-note="kmNoteModel"
      :distance-preview="distancePreview"
      :can-submit-km="canSubmitKm"
      :km-saving="kmSaving"
      :cost-form="costForm"
      :cost-types="costTypes"
      :cost-error="costError"
      :cost-saving="costSaving"
      @close-km="kmModalOpen = false"
      @close-cost="costModalOpen = false"
      @submit-km="submitKmModal"
      @submit-cost="submitCost"
      @update:end-km="onModalEndKm"
      @update:km-note="setKmNote"
      @update:cost-form="setCostForm"
    />
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { ChartBarIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import { DRIVER_KM_SECTION_ENABLED, useDriverTripDetailPage } from '../../composables/useDriverTripDetailPage'
import { formatVnd } from '../../util/labels'
import DriverTripBottomBar from '../../components/driver/trip-detail/DriverTripBottomBar.vue'
import DriverTripCostsSection from '../../components/driver/trip-detail/DriverTripCostsSection.vue'
import DriverTripDetailHeader from '../../components/driver/trip-detail/DriverTripDetailHeader.vue'
import DriverTripDetailModals from '../../components/driver/trip-detail/DriverTripDetailModals.vue'
import DriverTripDetailSkeleton from '../../components/driver/trip-detail/DriverTripDetailSkeleton.vue'
import DriverTripPaxSection from '../../components/driver/trip-detail/DriverTripPaxSection.vue'
import DriverTripRouteSection from '../../components/driver/trip-detail/DriverTripRouteSection.vue'

const { t } = useI18n()

const {
  trip,
  loading,
  loadError,
  moreOpen,
  kmModalOpen,
  kmSaving,
  endKmModel,
  kmNoteModel,
  costModalOpen,
  costSaving,
  costError,
  costForm,
  expandedStudentIdx,
  isPaused,
  costTypes,
  statusBadgeClass,
  headerStatusText,
  scheduleDateLine,
  scheduleTimeLine,
  dispatcherPhone,
  originMain,
  originSub,
  destMain,
  destSub,
  mapUrl,
  driverRouteLegs,
  routeTripTypeLabel,
  routeNotesPreview,
  tripCosts,
  costsApprovedTotal,
  costsPendingTotal,
  canAddCost,
  canAddPostTripCost,
  startKmModel,
  hasEndOdometer,
  distancePreview,
  canSubmitKm,
  warningBanner,
  rowState,
  paxKind,
  paxList,
  displayedPaxList,
  paxDisplayTotal,
  statsStudentCount,
  statsDistance,
  statsDuration,
  showPickupBar,
  pickedCount,
  canMarkPickup,
  canStart,
  canEndTrip,
  actionBusy,
  eventPosting,
  costTypeLabel,
  costStatusLabel,
  formatKm,
  formatKmInput,
  formatCostTime,
  onEndKmInput,
  openKmModal,
  submitKmModal,
  openCostModal,
  submitCost,
  toggleStudent,
  isNextIndex,
  onEndTrip,
  startTrip,
  setRowState,
  onRefreshMenu,
} = useDriverTripDetailPage()

function onModalEndKm(v) {
  endKmModel.value = v
  onEndKmInput()
}

function setKmNote(v) {
  kmNoteModel.value = v
}

function setCostForm(v) {
  costForm.value = v
}
</script>
