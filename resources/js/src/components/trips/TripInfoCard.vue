<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { CalendarDaysIcon, MapIcon, UserCircleIcon } from '@heroicons/vue/24/outline'
import { labelTripStatus } from '../../util/labels'

type Step = { state: string; label: string }

type RouteStop = { kind: string; address: string; detailLines?: string[] }

type SlaBanner = { kind: 'overdue' | 'ok'; text: string } | null

const props = defineProps<{
  trip: Record<string, any>
  countdown: string | null
  passengerCount: number
  scheduleDateLong: string
  scheduleTimeRange: string
  scheduleDuration: string
  scheduleMismatchNotes: string[]
  estimatedDistanceLabel: string
  estimatedCostLabel: string
  requesterInitials: string
  requesterName: string
  requesterSubtitle: string
  tripTypeLabel: string
  slaBanner: SlaBanner
  stepPickup: Step
  stepCurrent: Step
  stepDropoff: Step
  originLabel: string
  destinationLabel: string
  currentLabel: string
  routeStops: RouteStop[]
  embedMapSrc: string
  expandMap: () => void
}>()

const { t, locale } = useI18n()

function fmtTime(v: string | null | undefined) {
  const l = locale.value === 'en' ? 'en-US' : 'vi-VN'
  return v ? new Date(v).toLocaleTimeString(l, { hour: '2-digit', minute: '2-digit' }) : '-'
}

function pillClassForStatus(s: string) {
  const map: Record<string, string> = {
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

function dotClass(state: string) {
  const base = 'mt-0.5 h-3 w-3 flex-none rounded-full'
  if (state === 'done') return `${base} bg-emerald-500`
  if (state === 'active') return `${base} bg-amber-400`
  if (state === 'blocked') return `${base} bg-rose-400`
  return `${base} bg-slate-300`
}

function stopDotClass(kind: string) {
  if (kind === 'pickup') return 'bg-emerald-500 text-white ring-2 ring-emerald-200'
  if (kind === 'dropoff') return 'bg-sky-600 text-white ring-2 ring-sky-200'
  return 'bg-slate-200 text-slate-700 ring-2 ring-slate-100'
}

function stopLabel(kind: string) {
  if (kind === 'pickup') return t('trip_detail.route.stop_pickup')
  if (kind === 'dropoff') return t('trip_detail.route.stop_dropoff')
  return t('trip_detail.route.stop_waypoint')
}

const currentDotClass = computed(() => {
  const base = dotClass(props.stepCurrent.state)
  if (props.stepCurrent.state === 'active') return `${base} location-dot-pulse`
  return base
})
</script>

<template>
  <div class="space-y-6">
    <section
      class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm"
    >
      <div class="absolute right-4 top-4 flex flex-wrap items-center justify-end gap-2">
        <span
          v-if="countdown"
          class="rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-900"
        >
          {{ countdown }}
        </span>
        <span :class="['rounded-full px-2.5 py-0.5 text-xs font-medium', pillClassForStatus(trip.status)]">
          {{ labelTripStatus(trip.status) }}
        </span>
      </div>
      <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">
        {{ t('trip_detail.overview.title') }}
      </h2>

      <div
        v-if="slaBanner"
        class="mt-3 rounded-xl border px-3 py-2 text-sm font-medium"
        :class="
          slaBanner.kind === 'overdue'
            ? 'border-rose-200 bg-rose-50 text-rose-900'
            : 'border-sky-200 bg-sky-50 text-sky-950'
        "
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
            class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-sm font-bold text-slate-700 ring-2 ring-white dark:bg-slate-800 dark:text-slate-200"
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

      <!-- Lịch trình: 3 metric -->
      <div class="mt-5 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
          <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
            <CalendarDaysIcon class="h-4 w-4 shrink-0 text-slate-400" />
            {{ t('trip_detail.overview.schedule') }}
          </div>
          <div class="mt-2 text-sm font-semibold text-slate-900">{{ scheduleDateLong }}</div>
          <div class="mt-1 text-sm text-slate-600">
            {{ scheduleTimeRange }}
            <span v-if="scheduleDuration" class="text-slate-500">({{ scheduleDuration }})</span>
          </div>
          <ul class="mt-2 space-y-1 text-xs text-slate-600">
            <li
              v-for="(ln, i) in scheduleMismatchNotes"
              :key="i"
              class="text-amber-800/90"
            >
              {{ ln }}
            </li>
          </ul>
        </div>
        <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
          <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
            <UserCircleIcon class="h-4 w-4 shrink-0 text-slate-400" />
            {{ t('trip_detail.passengers.title', { n: passengerCount }) }}
          </div>
          <div class="mt-2 text-sm font-semibold text-slate-900">
            <template v-if="passengerCount > 0">
              {{ t('trip_detail.overview.pax_count', { n: passengerCount }) }}
            </template>
            <template v-else>—</template>
          </div>
          <div v-if="trip.arrive_by" class="mt-1 text-xs text-slate-600">
            {{ t('trip_detail.overview.arrive_deadline', { time: fmtTime(trip.arrive_by) }) }}
          </div>
        </div>
        <div class="rounded-xl border border-slate-100 bg-slate-50/80 p-4">
          <div class="flex items-center gap-2 text-xs font-medium text-slate-500">
            <MapIcon class="h-4 w-4 shrink-0 text-slate-400" />
            {{ t('trip_detail.overview.est_distance') }} · {{ t('trip_detail.overview.est_cost') }}
          </div>
          <div class="mt-2 text-sm font-semibold tabular-nums text-slate-900">{{ estimatedDistanceLabel }}</div>
          <div class="mt-2 text-sm font-semibold tabular-nums text-slate-900">{{ estimatedCostLabel }}</div>
        </div>
      </div>

      <!-- Điểm đón / vị trí / điểm trả -->
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
            <div :class="currentDotClass" />
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
      <details class="group" open>
        <summary
          class="flex cursor-pointer list-none flex-col gap-2 marker:content-none sm:flex-row sm:items-start sm:justify-between [&::-webkit-details-marker]:hidden"
        >
          <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">
            {{ t('trip_detail.route.section_title') }}
          </h2>
          <div
            v-if="routeStops.length"
            class="flex flex-wrap gap-2 text-xs text-slate-600"
            @click.stop
          >
            <span class="rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-700">
              {{ t('trip_detail.route.stop_count', { n: routeStops.length }) }}
            </span>
            <span
              v-if="trip.record?.distance_km != null && trip.record.distance_km !== ''"
              class="rounded-full bg-emerald-50 px-2.5 py-1 font-medium text-emerald-800"
            >
              {{ t('trip_detail.route.recorded_km', { km: trip.record.distance_km }) }}
            </span>
            <span class="rounded-full bg-sky-50 px-2.5 py-1 font-medium text-sky-800">{{ tripTypeLabel }}</span>
          </div>
        </summary>
        <div class="mt-4 flex flex-col gap-5 lg:flex-row">
          <div class="min-w-0 flex-1">
            <ol class="relative space-y-0 border-l-2 border-slate-200 pl-6">
              <li
                v-for="(stop, idx) in routeStops"
                :key="`${idx}-${stop.address}`"
                class="relative pb-8 last:pb-0"
              >
                <span
                  class="absolute -left-[25px] top-1 flex h-5 w-5 items-center justify-center rounded-full border-2 border-white text-[10px] font-bold"
                  :class="stopDotClass(stop.kind)"
                >
                  {{ idx + 1 }}
                </span>
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                  {{ stopLabel(stop.kind) }}
                </div>
                <div class="mt-1 text-sm font-medium text-slate-900">{{ stop.address }}</div>
                <ul
                  v-if="stop.detailLines?.length"
                  class="mt-2 space-y-0.5 border-l-2 border-slate-100 pl-3"
                >
                  <li
                    v-for="(dl, j) in stop.detailLines"
                    :key="j"
                    class="text-xs leading-relaxed text-slate-600"
                  >
                    {{ dl }}
                  </li>
                </ul>
              </li>
            </ol>
            <div v-if="!routeStops.length" class="text-sm text-slate-500">
              {{ t('trip_detail.route.no_stops') }}
            </div>
          </div>
          <div class="relative w-full shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 lg:w-[320px]">
            <div class="relative aspect-[4/3] w-full bg-slate-100 dark:bg-slate-800/80">
              <iframe
                v-if="embedMapSrc"
                :src="embedMapSrc"
                class="absolute inset-0 h-full w-full border-0"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                :aria-label="t('trip_detail.route.map_title')"
              />
              <div
                v-else
                class="flex h-full min-h-[200px] items-center justify-center p-4 text-center text-sm text-slate-500"
              >
                {{ t('trip_detail.route.map_placeholder') }}
              </div>
            </div>
            <button
              type="button"
              class="absolute right-2 top-2 rounded-lg border border-slate-200/80 bg-white/95 px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:bg-white"
              @click="expandMap"
            >
              {{ t('trip_detail.route.expand_map') }}
            </button>
          </div>
        </div>
      </details>
    </section>
  </div>
</template>

<style scoped>
@keyframes location-pulse {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.5;
    transform: scale(1.4);
  }
}
.location-dot-pulse {
  animation: location-pulse 2s ease-in-out infinite;
}
</style>
