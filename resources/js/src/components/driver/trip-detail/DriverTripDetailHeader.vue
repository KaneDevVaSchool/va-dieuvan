<template>
  <div
    class="relative isolate shrink-0 bg-gradient-to-b from-[#020B0B] to-[#031818] pb-5 text-driver-ink shadow-[0_10px_40px_-8px_rgba(34,211,238,0.12)] ring-1 ring-[#7fdcc8]/10 sm:rounded-b-3xl"
  >
    <div
      class="pointer-events-none absolute inset-x-0 top-0 z-0 h-[env(safe-area-inset-top,0px)] bg-[#020B0B]"
      aria-hidden="true"
    />
    <div
      class="relative z-[1] pl-[max(theme(spacing.4),env(safe-area-inset-left))] pr-[max(theme(spacing.4),env(safe-area-inset-right))] pt-[max(theme(spacing.3),env(safe-area-inset-top))] sm:pt-[max(theme(spacing.4),env(safe-area-inset-top))]"
    >
      <div class="flex items-center justify-between gap-2">
        <RouterLink
          :to="{ name: 'driverSchedule' }"
          class="flex min-h-[44px] min-w-[44px] shrink-0 items-center justify-center rounded-full bg-white/10 text-driver-ink ring-1 ring-white/20 active:bg-white/20"
          :aria-label="t('driver_trip_detail.back')"
        >
          <ArrowLeftIcon class="h-5 w-5" />
        </RouterLink>
        <h1 class="flex-1 text-center text-lg font-bold leading-snug tracking-tight sm:text-xl">
          {{ trip ? t('driver_trip_detail.header_title', { id: trip.id }) : t('driver_trip_detail.title') }}
        </h1>
        <div ref="overflowWrap" class="relative">
          <button
            type="button"
            :id="overflowBtnId"
            class="flex min-h-[44px] min-w-[44px] shrink-0 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20 active:bg-white/20"
            :aria-label="t('driver_trip_detail.more')"
            :aria-expanded="moreOpen ? 'true' : 'false'"
            aria-haspopup="true"
            :aria-controls="overflowMenuId"
            @click.stop="$emit('toggle-menu')"
          >
            <EllipsisVerticalIcon class="h-5 w-5" aria-hidden="true" />
          </button>
          <div
            :id="overflowMenuId"
            v-show="moreOpen"
            role="menu"
            class="absolute right-0 top-12 z-20 min-w-[11rem] overflow-hidden rounded-xl border border-white/15 bg-driver-card py-1 text-left text-base shadow-lg ring-1 ring-white/10"
            :aria-labelledby="overflowBtnId"
          >
            <button
              type="button"
              role="menuitem"
              class="flex min-h-[44px] w-full items-center px-4 py-2.5 text-left text-driver-ink/90 hover:bg-white/10 active:bg-white/15"
              @click="$emit('refresh')"
            >
              {{ t('driver_trip_detail.action_refresh') }}
            </button>
            <a
              v-if="dispatcherPhone"
              role="menuitem"
              :href="`tel:${dispatcherPhone}`"
              class="flex min-h-[44px] items-center px-4 py-2.5 text-driver-ink/90 hover:bg-white/10 active:bg-white/15"
              @click="$emit('close-menu')"
            >
              {{ t('driver_trip_detail.call_dispatcher') }}
            </a>
          </div>
        </div>
      </div>

      <template v-if="trip">
        <div class="mt-3 flex justify-center">
          <span class="rounded-full px-3 py-1 text-base font-semibold sm:text-lg" :class="statusBadgeClass">{{
            headerStatusText
          }}</span>
        </div>
        <TripScheduleCard :schedule-date-line="scheduleDateLine" :schedule-time-line="scheduleTimeLine" />
        <div class="mt-3 grid grid-cols-3 divide-x divide-white/15 rounded-2xl bg-white/10 py-3">
          <div class="flex flex-col items-center gap-0.5 px-2">
            <UserGroupIcon class="h-5 w-5 text-[#7fdcc8] sm:h-6 sm:w-6" aria-hidden="true" />
            <span class="text-xs text-driver-muted/80 sm:text-sm">{{ t('driver_trip_detail.stats_students') }}</span>
            <span class="text-lg font-bold tabular-nums text-driver-ink sm:text-xl">{{ statsStudentCount }}</span>
          </div>
          <div class="flex flex-col items-center gap-0.5 px-2">
            <MapPinIcon class="h-5 w-5 text-[#7fdcc8] sm:h-6 sm:w-6" aria-hidden="true" />
            <span class="text-xs text-driver-muted/80 sm:text-sm">{{ t('driver_trip_detail.stats_distance') }}</span>
            <span class="text-lg font-bold tabular-nums text-driver-ink sm:text-xl">{{ statsDistance }}</span>
          </div>
          <div class="flex flex-col items-center gap-0.5 px-2">
            <ClockIcon class="h-5 w-5 text-[#7fdcc8] sm:h-6 sm:w-6" aria-hidden="true" />
            <span class="text-xs text-driver-muted/80 sm:text-sm">{{ t('driver_trip_detail.stats_duration') }}</span>
            <span class="text-lg font-bold tabular-nums text-driver-ink sm:text-xl">{{ statsDuration }}</span>
          </div>
        </div>
      </template>

      <div
        v-if="warningBanner"
        class="mt-3 flex gap-3 rounded-2xl border border-amber-400/50 bg-gradient-to-r from-amber-500/95 to-amber-600/90 px-3 py-3 text-amber-950 shadow-md"
        role="status"
      >
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/30">
          <ClockIcon class="h-5 w-5 text-amber-950" aria-hidden="true" />
        </div>
        <div class="min-w-0">
          <p class="text-base font-bold leading-snug sm:text-lg">{{ t('driver_trip_detail.warn_title', { n: warningBanner.minutes }) }}</p>
          <p class="mt-0.5 text-sm leading-snug text-amber-950/90 sm:text-base">{{ warningBanner.body }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { ArrowLeftIcon, ClockIcon, EllipsisVerticalIcon, MapPinIcon, UserGroupIcon } from '@heroicons/vue/24/outline'
import TripScheduleCard from './TripScheduleCard.vue'

const props = defineProps({
  trip: { type: Object, default: null },
  moreOpen: { type: Boolean, default: false },
  statusBadgeClass: { type: String, default: '' },
  headerStatusText: { type: String, default: '' },
  scheduleDateLine: { type: String, default: '' },
  scheduleTimeLine: { type: String, default: '' },
  statsStudentCount: { type: [Number, String], default: 0 },
  statsDistance: { type: String, default: '' },
  statsDuration: { type: String, default: '' },
  warningBanner: { type: Object, default: null },
  dispatcherPhone: { type: String, default: null },
})

const emit = defineEmits(['toggle-menu', 'close-menu', 'refresh'])

const { t } = useI18n()

const overflowBtnId = 'driver-trip-detail-overflow-btn'
const overflowMenuId = 'driver-trip-detail-overflow-menu'

const overflowWrap = ref(null)

function onDocClick(e) {
  if (!props.moreOpen) return
  const el = overflowWrap.value
  if (el && !el.contains(e.target)) emit('close-menu')
}

onMounted(() => document.addEventListener('click', onDocClick))
onUnmounted(() => document.removeEventListener('click', onDocClick))
</script>
