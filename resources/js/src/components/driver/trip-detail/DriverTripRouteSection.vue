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
          :key="legKey(leg, idx)"
          :class="idx > 0 ? 'mt-3 border-t border-white/10 pt-3' : ''"
        >
          <button
            type="button"
            class="flex w-full min-w-0 items-center gap-2 rounded-xl px-1 py-2 text-left transition hover:bg-white/[0.04] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#7fdcc8]/40"
            :aria-expanded="isLegExpanded(leg, idx) ? 'true' : 'false'"
            :aria-controls="`driver-route-leg-panel-${legKey(leg, idx)}`"
            :data-testid="`driver-route-leg-toggle-${idx}`"
            :aria-label="isLegExpanded(leg, idx) ? t('driver_trip_detail.schedule_leg_collapse') : t('driver_trip_detail.schedule_leg_expand')"
            @click="toggleLeg(leg, idx)"
          >
            <div class="min-w-0 flex-1">
              <p
                v-if="leg.label"
                class="text-xs font-semibold uppercase tracking-wider text-[#7fdcc8]/90"
              >
                {{ leg.label }}
              </p>
              <p
                v-if="!isLegExpanded(leg, idx)"
                class="mt-0.5 truncate text-sm text-driver-muted"
              >
                {{ legRoutePreview(leg) }}
              </p>
            </div>
            <ChevronDownIcon
              class="h-5 w-5 shrink-0 text-driver-muted transition-transform duration-200"
              :class="isLegExpanded(leg, idx) ? 'rotate-180' : ''"
              aria-hidden="true"
            />
          </button>
          <div
            v-show="isLegExpanded(leg, idx)"
            :id="`driver-route-leg-panel-${legKey(leg, idx)}`"
            class="pt-1"
          >
            <DriverRouteTimeline
              :origin-main="leg.originMain"
              :origin-sub="leg.originSub"
              :waypoint-main="leg.waypointMain"
              :waypoint-sub="leg.waypointSub"
              :dest-main="leg.destMain"
              :dest-sub="leg.destSub"
              :map-url="leg.mapUrl"
            />
          </div>
        </div>
      </template>
      <div v-else>
        <button
          type="button"
          class="mb-2 flex w-full min-w-0 items-center justify-between gap-2 rounded-xl px-1 py-2 text-left transition hover:bg-white/[0.04] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#7fdcc8]/40"
          :aria-expanded="singleRouteOpen ? 'true' : 'false'"
          aria-controls="driver-route-single-panel"
          :aria-label="singleRouteOpen ? t('driver_trip_detail.schedule_leg_collapse') : t('driver_trip_detail.schedule_leg_expand')"
          data-testid="driver-route-leg-toggle-single"
          @click="singleRouteOpen = !singleRouteOpen"
        >
          <span class="truncate text-sm font-medium text-driver-muted">
            {{ singleRouteOpen ? t('driver_trip_detail.schedule_leg_collapse') : legRoutePreview(fallbackLegPreview) }}
          </span>
          <ChevronDownIcon
            class="h-5 w-5 shrink-0 text-driver-muted transition-transform duration-200"
            :class="singleRouteOpen ? 'rotate-180' : ''"
            aria-hidden="true"
          />
        </button>
        <div v-show="singleRouteOpen" id="driver-route-single-panel">
          <DriverRouteTimeline
            :origin-main="originMain"
            :origin-sub="originSub"
            :waypoint-main="waypointMain"
            :waypoint-sub="waypointSub"
            :dest-main="destMain"
            :dest-sub="destSub"
            :map-url="mapUrl"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronDownIcon, MapIcon } from '@heroicons/vue/24/outline'
import DriverRouteTimeline from './DriverRouteTimeline.vue'

const props = defineProps({
  originMain: { type: String, default: '' },
  originSub: { type: String, default: '' },
  destMain: { type: String, default: '' },
  destSub: { type: String, default: '' },
  waypointMain: { type: String, default: '' },
  waypointSub: { type: String, default: '' },
  mapUrl: { type: String, default: '' },
  legs: { type: Array, default: () => [] },
  scheduleSummary: { type: String, default: '' },
  distanceLabel: { type: String, default: '' },
  passengerCount: { type: Number, default: 0 },
  tripTypeLabel: { type: String, default: '' },
  notesPreview: { type: String, default: '' },
})

const { t } = useI18n()

/** @type {import('vue').Ref<Record<string, boolean>>} */
const expandedByKey = ref({})
const singleRouteOpen = ref(true)

const fallbackLegPreview = computed(() => ({
  originMain: props.originMain,
  destMain: props.destMain,
  waypointMain: props.waypointMain,
}))

function legKey(leg, idx) {
  return String(leg?.key ?? leg?.label ?? `leg-${idx}`)
}

function defaultExpandedForIndex(idx) {
  return idx === 0
}

function isLegExpanded(leg, idx) {
  const key = legKey(leg, idx)
  if (Object.prototype.hasOwnProperty.call(expandedByKey.value, key)) {
    return expandedByKey.value[key]
  }
  return defaultExpandedForIndex(idx)
}

function toggleLeg(leg, idx) {
  const key = legKey(leg, idx)
  expandedByKey.value = {
    ...expandedByKey.value,
    [key]: !isLegExpanded(leg, idx),
  }
}

function legRoutePreview(leg) {
  const o = String(leg?.originMain ?? '').trim() || '—'
  const d = String(leg?.destMain ?? '').trim() || '—'
  const w = String(leg?.waypointMain ?? '').trim()
  if (w) return `${o} → ${w} → ${d}`
  return `${o} → ${d}`
}

watch(
  () => props.legs,
  (legs) => {
    if (!Array.isArray(legs) || !legs.length) return
    const next = { ...expandedByKey.value }
    legs.forEach((leg, idx) => {
      const key = legKey(leg, idx)
      if (!Object.prototype.hasOwnProperty.call(next, key)) {
        next[key] = defaultExpandedForIndex(idx)
      }
    })
    expandedByKey.value = next
  },
  { immediate: true, deep: true },
)
</script>
