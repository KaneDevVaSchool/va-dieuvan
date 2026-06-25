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
              <div class="flex items-center gap-2">
                <p
                  v-if="leg.label"
                  class="text-xs font-semibold uppercase tracking-wider text-[#7fdcc8]/90"
                >
                  {{ leg.label }}
                </p>
                <span
                  v-if="multiLeg && leg.hasActions && leg.statusLabel"
                  class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                  :class="leg.statusBadgeClass"
                  :data-testid="`driver-route-leg-status-${idx}`"
                >
                  {{ leg.statusLabel }}
                </span>
              </div>
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

          <div
            v-if="multiLeg && leg.hasActions"
            class="mt-2"
            :data-testid="`driver-route-leg-actions-${idx}`"
          >
            <div v-if="leg.canConfirm" class="grid grid-cols-2 gap-2">
              <button
                type="button"
                :disabled="legActionBusyKey === leg.key"
                class="inline-flex min-h-[44px] items-center justify-center rounded-xl bg-emerald-500 px-3 text-sm font-bold text-white shadow-sm transition-transform disabled:cursor-not-allowed disabled:opacity-50 active:scale-[0.98]"
                :data-testid="`driver-route-leg-confirm-${idx}`"
                @click="emitLeg('confirm-leg', leg)"
              >
                {{ t('driver_home.btn_confirm') }}
              </button>
              <button
                type="button"
                :disabled="legActionBusyKey === leg.key"
                class="inline-flex min-h-[44px] items-center justify-center rounded-xl border-2 border-rose-500/70 bg-transparent px-3 text-sm font-bold text-rose-400 transition active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                :data-testid="`driver-route-leg-decline-${idx}`"
                @click="emitLeg('decline-leg', leg)"
              >
                {{ t('driver_home.btn_decline') }}
              </button>
            </div>
            <button
              v-else-if="leg.canStart"
              type="button"
              :disabled="legActionBusyKey === leg.key"
              class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-emerald-500 px-4 text-sm font-bold text-white shadow-sm transition-transform disabled:opacity-50 active:scale-[0.98]"
              :data-testid="`driver-route-leg-start-${idx}`"
              @click="emitLeg('start-leg', leg)"
            >
              <PlayIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
              {{ t('driver_trip_detail.btn_start_trip') }}
            </button>
            <button
              v-else-if="leg.canEnd"
              type="button"
              :disabled="legActionBusyKey === leg.key"
              class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-driver-bg px-4 text-sm font-bold text-white shadow-sm transition-transform disabled:opacity-50 active:scale-[0.98]"
              :data-testid="`driver-route-leg-end-${idx}`"
              @click="emitLeg('end-leg', leg)"
            >
              <FlagIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
              {{ t('driver_trip_detail.btn_end_trip') }}
            </button>
            <p
              v-else-if="leg.isTerminal"
              class="rounded-xl border px-3 py-2 text-center text-sm font-medium"
              :class="leg.status === 'completed'
                ? 'border-emerald-500/35 bg-emerald-950/35 text-emerald-100'
                : 'border-rose-500/35 bg-rose-950/30 text-rose-100'"
            >
              {{ leg.statusLabel }}
            </p>
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
import { ChevronDownIcon, FlagIcon, MapIcon, PlayIcon } from '@heroicons/vue/24/outline'
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
  multiLeg: { type: Boolean, default: false },
  legActionBusyKey: { type: String, default: '' },
})

const emit = defineEmits(['confirm-leg', 'decline-leg', 'start-leg', 'end-leg'])

const { t } = useI18n()

function emitLeg(event, leg) {
  if (!leg?.key || props.legActionBusyKey) return
  emit(event, leg.key)
}

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
