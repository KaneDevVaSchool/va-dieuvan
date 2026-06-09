<template>
  <div :class="compact ? 'space-y-1' : 'space-y-1.5'">
    <div class="flex flex-wrap items-center gap-1.5">
      <span class="font-mono text-sm font-bold tabular-nums text-driver-ink sm:text-base">
        {{ tripCode }}
      </span>
      <span
        class="rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1"
        :class="typeBadgeClass"
      >
        {{ typeLabel }}
      </span>
      <span
        v-if="statusLabel"
        class="rounded-md bg-white/5 px-1.5 py-0.5 text-[10px] font-semibold text-driver-muted ring-1 ring-white/10"
      >
        {{ statusLabel }}
      </span>
    </div>

    <p
      class="inline-flex flex-wrap items-center gap-1.5 text-sm font-semibold tabular-nums text-[#7fdcc8] sm:text-base"
    >
      <ClockIcon class="h-4 w-4 shrink-0 text-[#7fdcc8]/80" aria-hidden="true" />
      <span>{{ timeRange || '—' }}</span>
    </p>

    <div class="flex min-w-0 items-start gap-2 text-sm leading-snug sm:text-base">
      <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-emerald-400" aria-hidden="true" />
      <p class="min-w-0 flex-1 font-medium text-driver-ink/95">{{ origin }}</p>
    </div>
    <div class="flex min-w-0 items-start gap-2 text-sm leading-snug sm:text-base">
      <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-rose-400" aria-hidden="true" />
      <p class="min-w-0 flex-1 font-medium text-driver-ink/95">{{ destination }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ClockIcon } from '@heroicons/vue/24/outline'
import {
  formatDepartForTrip,
  tripDestination,
  tripOrigin,
  tripOutboundInboundTimeRange,
  tripTypeBadgeClass,
  tripTypeBadgeText,
} from '../../../composables/useDriverTripDisplay'
import { formatTripCode } from '../../../util/labels'
import { driverCostTripStatusLabel } from '../../../util/driverCostTripPicker'

const props = defineProps({
  trip: { type: Object, required: true },
  compact: { type: Boolean, default: true },
})

const { t, locale } = useI18n()

const localeTag = computed(() => (locale.value === 'vi' ? 'vi' : 'en'))

const tripCode = computed(() => formatTripCode(props.trip?.id))

const typeLabel = computed(() => tripTypeBadgeText(props.trip))
const typeBadgeClass = computed(() => tripTypeBadgeClass(props.trip))
const origin = computed(() => tripOrigin(props.trip))
const destination = computed(() => tripDestination(props.trip))
const statusLabel = computed(() => driverCostTripStatusLabel(props.trip, t))

const timeRange = computed(() => {
  const tr = props.trip
  const range = tripOutboundInboundTimeRange(tr, localeTag.value, t)
  if (range) return range
  const { time } = formatDepartForTrip(tr, localeTag.value)
  return time && time !== '—' ? time : ''
})
</script>
