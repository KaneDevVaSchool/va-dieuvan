<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  MapPinIcon,
  ClockIcon,
  UsersIcon,
  TruckIcon,
  UserIcon,
  CalendarDaysIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  /**
   * @type {Array<{
   *   key: string, seq: number, status: string, statusLabel: string, tone: string,
   *   origin: string, destination: string, waypoint: string,
   *   startTime: string, endTime: string, passengers: number|null,
   *   vehicle: string, driver: string,
   * }>}
   */
  segments: { type: Array, default: () => [] },
  summary: { type: Object, default: null },
  selectedKey: { type: String, default: '' },
})

const emit = defineEmits(['select-segment'])

const { t } = useI18n()

const showPanel = computed(() => (props.segments?.length ?? 0) > 0)

/** Chip tóm tắt hành trình (số chặng, khách, khởi hành, thời lượng, xe, tài xế). */
const chips = computed(() => {
  const s = props.summary
  if (!s) return []
  const out = [
    {
      key: 'segments',
      icon: MapPinIcon,
      text: t('trip_detail.route_journey.chip_segments', { n: s.segmentCount ?? 0 }),
    },
  ]
  if (s.passengers > 0) {
    out.push({
      key: 'passengers',
      icon: UsersIcon,
      text: t('trip_detail.route_journey.chip_passengers', { n: s.passengers }),
    })
  }
  if (s.departure) {
    out.push({
      key: 'departure',
      icon: CalendarDaysIcon,
      text: t('trip_detail.route_journey.chip_departure', { time: s.departure }),
    })
  }
  if (s.duration) {
    out.push({ key: 'duration', icon: ClockIcon, text: s.duration })
  }
  if (s.vehicleCount > 0) {
    out.push({
      key: 'vehicles',
      icon: TruckIcon,
      text: t('trip_detail.route_journey.chip_vehicles', { n: s.vehicleCount }),
    })
  }
  if (s.driverCount > 0) {
    out.push({
      key: 'drivers',
      icon: UserIcon,
      text: t('trip_detail.route_journey.chip_drivers', { n: s.driverCount }),
    })
  }
  return out
})

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
</script>

<template>
  <section
    v-if="showPanel"
    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
    :aria-label="t('trip_detail.route_journey.title')"
  >
    <!-- HEADER + SUMMARY CHIPS -->
    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2">
      <h2 class="text-sm font-semibold text-slate-900">
        {{ t('trip_detail.route_journey.title') }}
      </h2>
    </div>
    <div v-if="chips.length" class="mt-3 flex flex-wrap gap-2">
      <span
        v-for="chip in chips"
        :key="chip.key"
        class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700"
      >
        <component :is="chip.icon" class="size-3.5 text-slate-400" aria-hidden="true" />
        {{ chip.text }}
      </span>
    </div>

    <!-- VERTICAL JOURNEY TIMELINE -->
    <ol class="mt-5 space-y-3">
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
        <button
          type="button"
          class="mb-1 w-full rounded-lg border bg-white px-3.5 py-3 text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400/40"
          :class="
            isActive(seg.key)
              ? 'border-blue-300 ring-1 ring-blue-200'
              : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60'
          "
          @click="emit('select-segment', seg.key)"
        >
          <!-- Row 1: segment no + status -->
          <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-bold uppercase tracking-wide text-slate-500">
              {{ t('trip_detail.route_journey.segment', { n: seg.seq }) }}
            </span>
            <span
              class="rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1"
              :class="badgeClass(seg.tone)"
            >
              {{ seg.statusLabel }}
            </span>
          </div>

          <!-- Row 2: origin → destination -->
          <div class="mt-2 space-y-1.5">
            <div class="flex items-start gap-2">
              <span
                class="mt-1.5 size-1.5 shrink-0 rounded-full bg-slate-400"
                aria-hidden="true"
              />
              <span class="text-sm font-semibold text-slate-900">
                {{ seg.origin || t('trip_detail.empty.place') }}
              </span>
            </div>
            <div
              v-if="seg.waypoint"
              class="flex items-start gap-2 pl-px text-slate-500"
            >
              <span
                class="ml-px mt-1.5 size-1 shrink-0 rounded-full bg-slate-300"
                aria-hidden="true"
              />
              <span class="text-xs">{{ seg.waypoint }}</span>
            </div>
            <div class="flex items-start gap-2">
              <MapPinIcon
                class="mt-0.5 size-3.5 shrink-0 text-blue-500"
                aria-hidden="true"
              />
              <span class="text-sm font-semibold text-slate-900">
                {{ seg.destination || t('trip_detail.empty.place') }}
              </span>
            </div>
          </div>

          <!-- Row 3: meta grid -->
          <dl
            class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 border-t border-slate-100 pt-2.5 sm:grid-cols-4"
          >
            <div>
              <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-400">
                {{ t('trip_detail.route_journey.time') }}
              </dt>
              <dd class="mt-0.5 text-xs font-medium tabular-nums text-slate-800">
                {{ timeRange(seg) || t('trip_detail.empty.time') }}
              </dd>
            </div>
            <div>
              <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-400">
                {{ t('trip_detail.route_journey.passengers') }}
              </dt>
              <dd class="mt-0.5 text-xs font-medium tabular-nums text-slate-800">
                {{ seg.passengers != null ? seg.passengers : '—' }}
              </dd>
            </div>
            <div>
              <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-400">
                {{ t('trip_detail.route_journey.vehicle') }}
              </dt>
              <dd class="mt-0.5 truncate text-xs font-medium text-slate-800">
                {{ seg.vehicle || t('trip_detail.empty.plate') }}
              </dd>
            </div>
            <div>
              <dt class="text-[10px] font-medium uppercase tracking-wide text-slate-400">
                {{ t('trip_detail.route_journey.driver') }}
              </dt>
              <dd class="mt-0.5 truncate text-xs font-medium text-slate-800">
                {{ seg.driver || t('trip_detail.empty.driver') }}
              </dd>
            </div>
          </dl>
        </button>
      </li>
    </ol>
  </section>
</template>
