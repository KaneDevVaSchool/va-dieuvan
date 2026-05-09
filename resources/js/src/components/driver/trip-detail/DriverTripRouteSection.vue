<template>
  <div class="overflow-hidden rounded-2xl bg-driver-card ring-1 ring-white/[0.06]">
    <p class="sr-only">{{ t('driver_trip_detail.route_section') }}</p>
    <div class="px-4 pb-4 pt-4">
      <div class="flex items-start gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-sm">
          <MapPinIcon class="h-5 w-5" aria-hidden="true" />
        </div>
        <div class="min-w-0 flex-1 pt-0.5">
          <p class="text-xs font-semibold uppercase tracking-wider text-driver-muted/80">{{ t('driver_trip_detail.point_start') }}</p>
          <p class="mt-1 text-base font-semibold leading-snug text-driver-ink sm:text-lg">{{ originMain }}</p>
          <p v-if="originSub" class="mt-1 text-sm leading-snug text-driver-muted sm:text-base">{{ originSub }}</p>
        </div>
      </div>

      <div class="mt-4 flex items-start gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-500 text-white shadow-sm">
          <FlagIcon class="h-5 w-5" aria-hidden="true" />
        </div>
        <div class="min-w-0 flex-1 pt-0.5">
          <p class="text-xs font-semibold uppercase tracking-wider text-driver-muted/80">{{ t('driver_trip_detail.point_end') }}</p>
          <p class="mt-1 text-base font-semibold leading-snug text-driver-ink sm:text-lg">{{ destMain }}</p>
          <p v-if="destSub" class="mt-1 text-sm leading-snug text-driver-muted sm:text-base">{{ destSub }}</p>
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
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { FlagIcon, MapIcon, MapPinIcon } from '@heroicons/vue/24/outline'

defineProps({
  originMain: { type: String, default: '' },
  originSub: { type: String, default: '' },
  destMain: { type: String, default: '' },
  destSub: { type: String, default: '' },
  mapUrl: { type: String, default: '' },
})

const { t } = useI18n()
</script>
