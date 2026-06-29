<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import TripTimeline from './TripTimeline.vue'

const props = defineProps({
  /**
   * @type {Array<{
   *   key: string, seq: number,
   *   status: string, statusLabel: string, tone: string,
   *   origin: string, destination: string, waypoint: string,
   *   scheduleDateShort?: string, arriveDateShort?: string,
   *   startTime: string, endTime: string, durationLabel?: string,
   *   detailLines?: Array<{ key: string, label: string, value: string }>,
   *   passengers: number|null, vehicle: string, driver: string,
   *   transportProvider?: string,
   * }>}
   */
  segments: { type: Array, default: () => [] },
  selectedKey: { type: String, default: '' },
  showPassengers: { type: Boolean, default: true },
})

const emit = defineEmits(['select-segment'])

const { t } = useI18n()

const showPanel = computed(() => (props.segments?.length ?? 0) > 0)

/** Lớp màu cho chấm trạng thái trên timeline. */
function dotClass(tone) {
  switch (tone) {
    case 'completed':
      return 'border-emerald-500 bg-emerald-500'
    case 'in_progress':
      return 'border-blue-500 bg-blue-500 ring-4 ring-blue-100'
    case 'cancelled':
      return 'border-rose-400 bg-rose-400'
    case 'incident':
      return 'border-orange-400 bg-orange-400'
    default:
      return 'border-slate-300 bg-white'
  }
}

/** Lớp màu cho badge trạng thái. */
function badgeClass(tone) {
  switch (tone) {
    case 'completed':
      return 'bg-emerald-50 text-emerald-700 ring-emerald-200'
    case 'in_progress':
      return 'bg-blue-50 text-blue-700 ring-blue-200'
    case 'cancelled':
      return 'bg-rose-50 text-rose-700 ring-rose-200'
    case 'incident':
      return 'bg-orange-50 text-orange-700 ring-orange-200'
    default:
      return 'bg-slate-100 text-slate-600 ring-slate-200'
  }
}

function isActive(key) {
  return props.selectedKey === key
}

function timeRange(seg) {
  if (seg.startTime && seg.endTime) return `${seg.startTime} → ${seg.endTime}`
  return seg.startTime || seg.endTime || ''
}

function arriveTimeDisplay(seg) {
  const time = seg.endTime || ''
  const extra = seg.arriveDateShort?.trim()
  if (time && extra) return `${time} (${extra})`
  return time || extra || ''
}
</script>

<template>
  <section
    v-if="showPanel"
    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
    :aria-label="t('trip_detail.route_journey.title')"
  >
    <h2 class="text-sm font-semibold text-slate-900">
      {{ t('trip_detail.route_journey.title') }}
    </h2>

    <!-- VERTICAL JOURNEY TIMELINE -->
    <ol class="mt-4 space-y-3">
      <li
        v-for="(seg, i) in segments"
        :key="seg.key"
        class="relative flex gap-3 sm:gap-4"
      >
        <!-- Timeline rail: dot + connector -->
        <div class="flex flex-col items-center">
          <span
            class="relative z-[1] mt-1 size-3.5 shrink-0 rounded-full border-2"
            :class="dotClass(seg.tone)"
            aria-hidden="true"
          />
          <span
            v-if="i < segments.length - 1"
            class="mt-1 w-px flex-1 bg-slate-200"
            aria-hidden="true"
          />
        </div>

        <!-- Segment card -->
        <div class="mb-1 min-w-0 flex-1 space-y-2">
          <TripTimeline
            v-if="seg.timelineLogs?.length"
            embedded
            :data-testid="`trip-timeline-${seg.key}`"
            :current-status="seg.timelineCurrentStatus || 'created'"
            :logs="seg.timelineLogs"
          />
          <button
          type="button"
          class="w-full rounded-lg border bg-white px-3 py-2.5 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400/40 sm:px-3.5 sm:py-3"
          :class="
            isActive(seg.key)
              ? 'border-blue-300 ring-1 ring-blue-200'
              : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60'
          "
          :data-testid="`trip-route-journey-segment-${seg.seq}`"
          @click="emit('select-segment', seg.key)"
        >
          <!-- Row 1: segment no + status -->
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <span class="text-xs font-bold uppercase tracking-wide text-slate-500">
                {{ t('trip_detail.route_journey.segment', { n: seg.seq }) }}
              </span>
            </div>
            <span
              class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1"
              :class="badgeClass(seg.tone)"
            >
              {{ seg.statusLabel }}
            </span>
          </div>

          <!-- Body: 2 cột — tuyến | vận hành -->
          <div class="mt-2 grid grid-cols-1 gap-2.5 sm:grid-cols-2 sm:gap-3">
            <!-- Cột trái: điểm đi / điểm đến -->
            <div
              class="rounded-lg border border-slate-100 bg-slate-50/70 p-2 sm:p-2.5"
            >
              <div class="grid grid-cols-1 gap-2 min-[480px]:grid-cols-2 min-[480px]:gap-2.5">
                <div class="min-w-0 min-[480px]:border-r min-[480px]:border-slate-200/80 min-[480px]:pr-2.5">
                  <p
                    class="text-[10px] font-bold uppercase tracking-wide text-emerald-800"
                  >
                    {{ t('trip_detail.route_journey.lbl_pickup') }}
                  </p>
                  <p class="mt-0.5 text-sm font-semibold leading-snug text-slate-900">
                    {{ seg.origin || t('trip_detail.empty.place') }}
                  </p>
                  <p
                    v-if="seg.startTime"
                    class="mt-0.5 text-[11px] font-medium tabular-nums text-slate-600"
                  >
                    {{ seg.startTime }}
                    <span
                      v-if="seg.scheduleDateShort"
                      class="text-slate-500"
                    >
                      · {{ seg.scheduleDateShort }}
                    </span>
                  </p>
                </div>

                <div class="min-w-0 min-[480px]:pl-0.5">
                  <p
                    class="text-[10px] font-bold uppercase tracking-wide text-indigo-800"
                  >
                    {{ t('trip_detail.route_journey.lbl_dropoff') }}
                  </p>
                  <p class="mt-0.5 text-sm font-semibold leading-snug text-slate-900">
                    {{ seg.destination || t('trip_detail.empty.place') }}
                  </p>
                  <p
                    v-if="seg.endTime || seg.arriveDateShort"
                    class="mt-0.5 text-[11px] font-medium tabular-nums text-slate-600"
                  >
                    {{ seg.endTime || arriveTimeDisplay(seg) || t('trip_detail.empty.time') }}
                  </p>
                </div>
              </div>

              <div
                v-if="seg.waypoint"
                class="mt-2 border-t border-slate-200/80 pt-2"
              >
                <p
                  class="text-[10px] font-bold uppercase tracking-wide text-amber-800"
                >
                  {{ t('trip_detail.route.stop_waypoint') }}
                </p>
                <p class="mt-0.5 text-xs font-medium text-slate-700">
                  {{ seg.waypoint }}
                </p>
              </div>

              <dl
                v-if="seg.detailLines?.length"
                class="mt-2 grid grid-cols-1 gap-1.5 border-t border-dashed border-slate-200/90 pt-2 min-[480px]:grid-cols-2 min-[480px]:gap-x-2"
              >
                <div
                  v-for="line in seg.detailLines"
                  :key="line.key"
                  class="min-w-0"
                >
                  <dt class="text-[10px] font-semibold text-slate-500">
                    {{ line.label }}
                  </dt>
                  <dd class="mt-0.5 text-[11px] font-medium leading-snug text-slate-800">
                    {{ line.value }}
                  </dd>
                </div>
              </dl>
            </div>

            <!-- Cột phải: meta vận hành -->
            <dl
              class="grid grid-cols-2 gap-x-2.5 gap-y-2 rounded-lg border border-slate-100 bg-white px-2 py-2 sm:px-2.5 sm:py-2.5"
            >
              <div v-if="seg.scheduleDateShort" class="col-span-2 sm:col-span-1">
                <dt
                  class="text-[10px] font-medium uppercase tracking-wide text-slate-400"
                >
                  {{ t('trip_detail.route_journey.schedule_date') }}
                </dt>
                <dd class="mt-0.5 text-xs font-medium text-slate-800">
                  {{ seg.scheduleDateShort }}
                </dd>
              </div>
              <div>
                <dt
                  class="text-[10px] font-medium uppercase tracking-wide text-slate-400"
                >
                  {{ t('trip_detail.route_journey.time') }}
                </dt>
                <dd class="mt-0.5 text-xs font-medium tabular-nums text-slate-800">
                  {{ timeRange(seg) || t('trip_detail.empty.time') }}
                </dd>
              </div>
              <div v-if="seg.durationLabel">
                <dt
                  class="text-[10px] font-medium uppercase tracking-wide text-slate-400"
                >
                  {{ t('trip_detail.overview.duration_label') }}
                </dt>
                <dd class="mt-0.5 text-xs font-medium tabular-nums text-slate-800">
                  {{ seg.durationLabel }}
                </dd>
              </div>
              <div v-if="showPassengers">
                <dt
                  class="text-[10px] font-medium uppercase tracking-wide text-slate-400"
                >
                  {{ t('trip_detail.route_journey.passengers') }}
                </dt>
                <dd class="mt-0.5 text-xs font-medium tabular-nums text-slate-800">
                  {{ seg.passengers != null ? seg.passengers : '—' }}
                </dd>
              </div>
              <div>
                <dt
                  class="text-[10px] font-medium uppercase tracking-wide text-slate-400"
                >
                  {{ t('trip_detail.route_journey.vehicle') }}
                </dt>
                <dd class="mt-0.5 truncate text-xs font-medium text-slate-800">
                  {{ seg.vehicle || t('trip_detail.empty.plate') }}
                </dd>
              </div>
              <div>
                <dt
                  class="text-[10px] font-medium uppercase tracking-wide text-slate-400"
                >
                  {{ t('trip_detail.route_journey.driver') }}
                </dt>
                <dd class="mt-0.5 truncate text-xs font-medium text-slate-800">
                  {{ seg.driver || t('trip_detail.empty.driver') }}
                </dd>
              </div>
              <div
                v-if="seg.transportProvider"
                class="col-span-2"
              >
                <dt
                  class="text-[10px] font-medium uppercase tracking-wide text-slate-400"
                >
                  {{ t('trip_detail.route_journey.transport_provider') }}
                </dt>
                <dd class="mt-0.5 text-xs font-medium text-slate-800">
                  {{ seg.transportProvider }}
                </dd>
              </div>
            </dl>
          </div>
        </button>
        </div>
      </li>
    </ol>
  </section>
</template>
