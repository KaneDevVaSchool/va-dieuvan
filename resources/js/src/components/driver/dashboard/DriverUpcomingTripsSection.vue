<template>
  <section class="space-y-2 pb-2 pt-1">
    <div class="flex items-end justify-between gap-2">
      <h2 class="text-sm font-bold uppercase tracking-wide text-[#64748b]">
        {{ t(titleKey) }}
      </h2>
      <RouterLink
        v-if="showScheduleLink"
        to="/driver/schedule"
        class="shrink-0 text-base font-bold text-[#4ade80] transition hover:text-[#7fdcc8]"
      >
        {{ t('driver_home.view_schedule') }} →
      </RouterLink>
    </div>

    <div v-if="listLoading && trips.length === 0" class="flex gap-3 overflow-hidden">
      <div
        v-for="n in 3"
        :key="n"
        class="h-[280px] w-[min(88vw,20rem)] shrink-0 animate-pulse rounded-2xl border border-white/5 bg-[#0f1816] p-5"
      >
        <div class="h-5 w-40 rounded bg-white/10" />
        <div class="mt-4 h-8 w-24 rounded bg-white/10" />
        <div class="mt-3 h-4 w-full rounded bg-white/[0.06]" />
        <div class="mt-2 h-4 w-4/5 rounded bg-white/[0.05]" />
      </div>
    </div>

    <DriverTodayEmptyState v-else-if="trips.length === 0 && !compactEmpty" />

    <p
      v-else-if="trips.length === 0 && compactEmpty"
      class="rounded-xl border border-white/5 bg-[#0a1512]/60 px-4 py-3 text-sm text-[#64748b]"
    >
      {{ t('driver_home.empty_today_short') }}
    </p>

    <TransitionGroup
      v-else-if="isSingleTrip"
      name="dash-trip-carousel"
      tag="div"
      class="flex w-full min-w-0 flex-col gap-3 pb-1"
    >
      <DriverTripCard
        v-for="trip in trips"
        :key="trip.id"
        class="w-full min-w-0"
        layout="stacked"
        :trip="trip"
        :busy="startBusyTripId != null && String(startBusyTripId) === String(trip.id)"
        data-testid="driver-dash-trip-card"
        @start="emit('start-trip', $event)"
      />
    </TransitionGroup>

    <div
      v-else
      class="-mx-3 snap-x snap-mandatory overflow-x-auto px-3 pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
      style="-webkit-overflow-scrolling: touch"
      data-testid="driver-dash-trip-carousel"
    >
      <TransitionGroup
        name="dash-trip-carousel"
        tag="div"
        class="flex w-max max-w-none snap-x snap-mandatory gap-3 pb-2 pl-0.5 pr-3"
      >
        <div
          v-for="trip in trips"
          :key="trip.id"
          class="dash-carousel-card snap-center first:snap-start last:pr-[max(0.75rem,env(safe-area-inset-right))]"
        >
          <DriverTripCard
            class="w-[min(82vw,20rem)] shrink-0 will-change-transform"
            layout="carousel"
            :trip="trip"
            :busy="startBusyTripId != null && String(startBusyTripId) === String(trip.id)"
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

const { t } = useI18n()

const isSingleTrip = computed(() => props.trips.length === 1)
</script>

<style scoped>
.dash-carousel-card {
  scroll-snap-align: center;
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
