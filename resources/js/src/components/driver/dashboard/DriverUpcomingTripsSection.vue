<template>
  <section class="space-y-2">
    <div class="flex items-end justify-between gap-2">
      <h2 class="text-xs font-bold uppercase tracking-wider text-[#64748b]">
        {{ t(titleKey) }}
      </h2>
      <RouterLink
        v-if="showScheduleLink"
        to="/driver/schedule"
        class="shrink-0 text-xs font-semibold text-[#4ade80] transition hover:text-[#7fdcc8]"
      >
        {{ t('driver_home.view_schedule') }} →
      </RouterLink>
    </div>

    <div v-if="listLoading && trips.length === 0" class="flex gap-3 overflow-hidden">
      <div
        v-for="n in 3"
        :key="n"
        class="h-[240px] w-[min(80vw,18.5rem)] shrink-0 animate-pulse rounded-xl border border-white/5 bg-[#0f1816] p-4"
      >
        <div class="h-4 w-32 rounded bg-white/10" />
        <div class="mt-3 h-7 w-20 rounded bg-white/10" />
        <div class="mt-2.5 h-3.5 w-full rounded bg-white/[0.06]" />
        <div class="mt-2 h-3.5 w-4/5 rounded bg-white/[0.05]" />
      </div>
    </div>

    <DriverTodayEmptyState v-else-if="trips.length === 0 && !compactEmpty" />

    <p
      v-else-if="trips.length === 0 && compactEmpty"
      class="rounded-xl border border-white/5 bg-[#0a1512]/60 px-4 py-3 text-sm text-[#64748b] w-full text-center"
    >
      {{ t('driver_home.empty_today_short') }}
    </p>

    <TransitionGroup
      v-else-if="isSingleTrip"
      name="dash-trip-carousel"
      tag="div"
      class="flex w-full min-w-0 flex-col gap-3"
    >
      <DriverTripCard
        v-for="trip in trips"
        :key="rowKey(trip)"
        class="w-full min-w-0"
        layout="stacked"
        :trip="trip"
        :busy="startBusyTripId != null && String(startBusyTripId) === rowKey(trip)"
        data-testid="driver-dash-trip-card"
        @start="emit('start-trip', $event)"
      />
    </TransitionGroup>

    <div
      v-else
      class="-mx-3 snap-x snap-mandatory scroll-px-3 overflow-x-auto px-3 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:-mx-4 sm:scroll-px-4 sm:px-4"
      style="-webkit-overflow-scrolling: touch"
      data-testid="driver-dash-trip-carousel"
    >
      <TransitionGroup
        name="dash-trip-carousel"
        tag="div"
        class="flex w-max max-w-none gap-3"
      >
        <div
          v-for="trip in trips"
          :key="rowKey(trip)"
          class="dash-carousel-card snap-start last:snap-end"
        >
          <DriverTripCard
            class="w-[min(80vw,18.5rem)] shrink-0 will-change-transform"
            layout="carousel"
            :trip="trip"
            :busy="startBusyTripId != null && String(startBusyTripId) === rowKey(trip)"
            data-testid="driver-dash-trip-card"
            @start="emit('start-trip', $event)"
          />
        </div>
      </TransitionGroup>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { TransitionGroup } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import DriverTripCard from '../DriverTripCard.vue'
import DriverTodayEmptyState from '../DriverTodayEmptyState.vue'
import { driverTripListRowKey } from '../../../util/driverScheduleLeg'

const props = defineProps({
  trips: { type: Array, required: true },
  /** Skeleton khi đang tải lần đầu và chưa có dữ liệu */
  listLoading: { type: Boolean, default: false },
  startBusyTripId: { type: [Number, String], default: null },
  titleKey: { type: String, default: 'driver_home.today_trips_section' },
  showScheduleLink: { type: Boolean, default: true },
  /** Không hiện empty lớn khi còn chuyến ở section khác */
  compactEmpty: { type: Boolean, default: false },
})

const emit = defineEmits(['start-trip'])

function rowKey(trip) {
  return driverTripListRowKey(trip) || String(trip.id)
}

const { t } = useI18n()

const isSingleTrip = computed(() => props.trips.length === 1)
</script>

<style scoped>
.dash-carousel-card {
  flex-shrink: 0;
}
.dash-trip-carousel-enter-active,
.dash-trip-carousel-leave-active {
  transition: opacity 0.2s ease-out, transform 0.2s ease-out;
}
.dash-trip-carousel-enter-from {
  opacity: 0;
  transform: translateX(12px);
}
.dash-trip-carousel-leave-to {
  opacity: 0;
  transform: translateY(6px);
}
.dash-trip-carousel-move {
  transition: transform 0.2s ease-out;
}
</style>
