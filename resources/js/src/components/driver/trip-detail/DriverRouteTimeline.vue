<template>
  <div>
    <div class="flex gap-3">
      <div class="flex w-11 shrink-0 flex-col items-center">
        <div
          class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-sm shadow-emerald-900/30"
        >
          <MapPinIcon class="h-5 w-5" aria-hidden="true" />
        </div>
        <div
          class="mt-1 min-h-[2rem] w-0.5 flex-1 rounded-full bg-gradient-to-b from-emerald-500/70 to-sky-500/70"
          aria-hidden="true"
        />
      </div>
      <div class="min-w-0 flex-1 pb-4">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-300/90 sm:text-xs">
          {{ t('driver_trip_detail.point_start') }}
        </p>
        <p class="mt-1 text-base font-semibold leading-snug text-driver-ink sm:text-lg">
          {{ originMain || '—' }}
        </p>
        <p v-if="originSub" class="mt-1 text-sm leading-snug text-driver-muted sm:text-base">
          {{ originSub }}
        </p>
      </div>
    </div>

    <div v-if="waypointMain" class="flex gap-3">
      <div class="flex w-11 shrink-0 flex-col items-center">
        <div
          class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-500 text-white shadow-sm shadow-sky-900/30"
        >
          <MapPinIcon class="h-5 w-5" aria-hidden="true" />
        </div>
        <div
          class="mt-1 min-h-[2rem] w-0.5 flex-1 rounded-full bg-gradient-to-b from-sky-500/70 to-rose-500/70"
          aria-hidden="true"
        />
      </div>
      <div class="min-w-0 flex-1 pb-4">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-sky-300/90 sm:text-xs">
          {{ t('driver_trip_detail.point_waypoint') }}
        </p>
        <p class="mt-1 text-base font-semibold leading-snug text-driver-ink sm:text-lg">
          {{ waypointMain }}
        </p>
        <p v-if="waypointSub" class="mt-1 text-sm leading-snug text-driver-muted sm:text-base">
          {{ waypointSub }}
        </p>
      </div>
    </div>

    <div class="flex gap-3">
      <div class="flex w-11 shrink-0 justify-center">
        <div
          class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-500 text-white shadow-sm shadow-rose-900/30"
        >
          <FlagIcon class="h-5 w-5" aria-hidden="true" />
        </div>
      </div>
      <div class="min-w-0 flex-1">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-rose-300/90 sm:text-xs">
          {{ t('driver_trip_detail.point_end') }}
        </p>
        <p class="mt-1 text-base font-semibold leading-snug text-driver-ink sm:text-lg">
          {{ destMain || '—' }}
        </p>
        <p v-if="destSub" class="mt-1 text-sm leading-snug text-driver-muted sm:text-base">
          {{ destSub }}
        </p>
      </div>
    </div>

    <a
      v-if="mapUrl"
      :href="mapUrl"
      target="_blank"
      rel="noopener noreferrer"
      class="mt-4 flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl border-2 border-sky-600 bg-sky-600 px-4 py-3 text-base font-bold text-white transition active:opacity-90 sm:text-lg"
    >
      <MapIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
      {{ t('driver_trip_detail.route_map_btn') }}
    </a>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { FlagIcon, MapIcon, MapPinIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  originMain: { type: String, default: '' },
  originSub: { type: String, default: '' },
  destMain: { type: String, default: '' },
  destSub: { type: String, default: '' },
  waypointMain: { type: String, default: '' },
  waypointSub: { type: String, default: '' },
  mapUrl: { type: String, default: '' },
})

const { t } = useI18n()
</script>
