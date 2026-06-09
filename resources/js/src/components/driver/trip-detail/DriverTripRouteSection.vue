<template>
  <div class="overflow-hidden rounded-2xl bg-driver-card ring-1 ring-white/[0.06]">
    <div class="flex items-center justify-between gap-3 border-b border-white/[0.06] px-4 py-3">
      <div class="flex min-w-0 items-center gap-2">
        <div
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#7fdcc8]/15 text-[#7fdcc8] ring-1 ring-[#7fdcc8]/25"
        >
          <MapIcon class="h-5 w-5" aria-hidden="true" />
        </div>
        <h2 class="text-base font-semibold text-driver-ink sm:text-lg">
          {{ t('driver_trip_detail.route_section') }}
        </h2>
      </div>
      <span
        v-if="tripTypeLabel"
        class="shrink-0 rounded-full bg-white/10 px-2.5 py-1 text-xs font-semibold text-driver-muted ring-1 ring-white/10"
      >
        {{ tripTypeLabel }}
      </span>
    </div>

    <div v-if="hasMeta" class="flex flex-wrap gap-2 border-b border-white/[0.06] px-4 py-3">
      <span
        v-if="scheduleSummary && scheduleSummary !== '—'"
        class="inline-flex items-center gap-1.5 rounded-lg bg-white/5 px-2.5 py-1.5 text-xs font-medium text-driver-ink/90 ring-1 ring-white/10 sm:text-sm"
      >
        <ClockIcon class="h-4 w-4 shrink-0 text-[#7fdcc8]" aria-hidden="true" />
        {{ scheduleSummary }}
      </span>
      <span
        v-if="distanceLabel && distanceLabel !== '— km'"
        class="inline-flex items-center gap-1.5 rounded-lg bg-white/5 px-2.5 py-1.5 text-xs font-medium text-driver-ink/90 ring-1 ring-white/10 sm:text-sm"
      >
        <ArrowsRightLeftIcon class="h-4 w-4 shrink-0 text-[#7fdcc8]" aria-hidden="true" />
        {{ distanceLabel }}
      </span>
      <span
        v-if="passengerCount > 0"
        class="inline-flex items-center gap-1.5 rounded-lg bg-white/5 px-2.5 py-1.5 text-xs font-medium text-driver-ink/90 ring-1 ring-white/10 sm:text-sm"
      >
        <UserGroupIcon class="h-4 w-4 shrink-0 text-[#7fdcc8]" aria-hidden="true" />
        {{ t('driver_trip_detail.route_chip_passengers', { n: passengerCount }) }}
      </span>
    </div>

    <div v-if="notesPreview" class="mx-4 mt-3 rounded-xl border border-amber-400/25 bg-amber-500/10 px-3 py-2.5">
      <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-200/90">
        {{ t('driver_trip_detail.notes_important') }}
      </p>
      <p class="mt-1 whitespace-pre-wrap text-sm leading-snug text-amber-50/95">{{ notesPreview }}</p>
    </div>

    <div class="px-4 py-4">
      <template v-if="legs && legs.length > 0">
        <div
          v-for="(leg, idx) in legs"
          :key="leg.key || idx"
          :class="idx > 0 ? 'mt-6 border-t border-white/10 pt-6' : ''"
        >
          <p
            v-if="leg.label"
            class="mb-3 text-xs font-semibold uppercase tracking-wider text-[#7fdcc8]/90"
          >
            {{ leg.label }}
          </p>
          <DriverRouteTimeline
            :origin-main="leg.originMain"
            :origin-sub="leg.originSub"
            :dest-main="leg.destMain"
            :dest-sub="leg.destSub"
            :map-url="leg.mapUrl"
          />
        </div>
      </template>
      <DriverRouteTimeline
        v-else
        :origin-main="originMain"
        :origin-sub="originSub"
        :dest-main="destMain"
        :dest-sub="destSub"
        :map-url="mapUrl"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowsRightLeftIcon,
  ClockIcon,
  MapIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'
import DriverRouteTimeline from './DriverRouteTimeline.vue'

const props = defineProps({
  originMain: { type: String, default: '' },
  originSub: { type: String, default: '' },
  destMain: { type: String, default: '' },
  destSub: { type: String, default: '' },
  mapUrl: { type: String, default: '' },
  legs: { type: Array, default: () => [] },
  scheduleSummary: { type: String, default: '' },
  distanceLabel: { type: String, default: '' },
  passengerCount: { type: Number, default: 0 },
  tripTypeLabel: { type: String, default: '' },
  notesPreview: { type: String, default: '' },
})

const { t } = useI18n()

const hasMeta = computed(
  () =>
    (props.scheduleSummary && props.scheduleSummary !== '—') ||
    (props.distanceLabel && props.distanceLabel !== '— km') ||
    props.passengerCount > 0,
)
</script>
