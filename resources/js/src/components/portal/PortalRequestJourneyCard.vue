<template>
  <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div class="flex items-center gap-2.5 border-b border-va-100 bg-va-50/80 px-4 py-3 sm:px-5">
      <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-va-100 text-va-700">
        <MapIcon class="h-4 w-4" aria-hidden="true" />
      </span>
      <h2 class="text-sm font-bold text-va-900">{{ t('portal.detail_journey.title') }}</h2>
    </div>

    <div class="grid gap-0 lg:grid-cols-[1fr_auto_1fr] lg:items-stretch">
      <div class="border-b border-slate-100 bg-emerald-50/60 px-4 py-4 sm:px-5 lg:border-b-0 lg:border-r">
        <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600/90">
          {{ t('portal.origin') }}
        </p>
        <p
          class="mt-1.5 flex items-start gap-2 text-lg font-bold leading-snug sm:text-xl"
          :class="originIsEmpty ? 'italic text-slate-400' : 'text-slate-900'"
        >
          <MapPinIcon
            class="mt-0.5 h-5 w-5 shrink-0"
            :class="originIsEmpty ? 'text-slate-300' : 'text-emerald-600'"
            aria-hidden="true"
          />
          <span class="min-w-0">{{ originDisplay }}</span>
        </p>
      </div>

      <div
        class="flex items-center justify-center border-b border-slate-100 bg-slate-50/50 px-3 py-2 lg:border-b-0"
        aria-hidden="true"
      >
        <ArrowRightIcon class="hidden h-6 w-6 shrink-0 text-slate-300 lg:block" />
        <ArrowDownIcon class="h-5 w-5 shrink-0 text-slate-300 lg:hidden" />
      </div>

      <div class="border-t border-slate-100 bg-rose-50/60 px-4 py-4 sm:px-5 lg:border-l lg:border-t-0">
        <p class="text-[10px] font-bold uppercase tracking-wider text-rose-600/90">
          {{ t('portal.destination') }}
        </p>
        <p
          class="mt-1.5 flex items-start gap-2 text-lg font-bold leading-snug sm:text-xl"
          :class="destinationIsEmpty ? 'italic text-slate-400' : 'text-slate-900'"
        >
          <MapPinIcon
            class="mt-0.5 h-5 w-5 shrink-0"
            :class="destinationIsEmpty ? 'text-slate-300' : 'text-rose-600'"
            aria-hidden="true"
          />
          <span class="min-w-0">{{ destinationDisplay }}</span>
        </p>
      </div>
    </div>

    <div
      class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/80 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5"
    >
      <div class="flex flex-wrap gap-2">
        <div class="rounded-xl border border-va-200/80 bg-va-50/70 px-3 py-2">
          <p class="text-[10px] font-bold uppercase tracking-wider text-va-600/80">
            {{ t('portal.detail_journey.depart_label') }}
          </p>
          <p
            class="mt-0.5 text-sm font-semibold tabular-nums"
            :class="departAt ? 'text-va-900' : 'italic text-slate-400'"
          >
            {{ departAt || emptyText.departAt('') }}
          </p>
        </div>
        <div
          v-if="tripTypeLabel"
          class="rounded-xl border border-slate-200/80 bg-white px-3 py-2"
        >
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
            {{ t('portal.detail_journey.trip_type_label') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ tripTypeLabel }}</p>
        </div>
        <div v-else class="rounded-xl border border-slate-200/80 bg-white px-3 py-2">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
            {{ t('portal.detail_journey.trip_type_label') }}
          </p>
          <p class="mt-0.5 text-sm italic text-slate-400">{{ emptyText.tripType('') }}</p>
        </div>
      </div>
      <a
        v-if="mapsHref"
        :href="mapsHref"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-va-800 hover:underline"
      >
        {{ t('portal.detail_journey.open_maps') }}
        <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5" aria-hidden="true" />
      </a>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowDownIcon,
  ArrowRightIcon,
  ArrowTopRightOnSquareIcon,
  MapIcon,
  MapPinIcon,
} from '@heroicons/vue/24/outline'

import { usePortalRequestEmptyText } from '../../composables/usePortalRequestEmptyText.js'

const props = defineProps({
  origin: { type: String, default: '' },
  destination: { type: String, default: '' },
  departAt: { type: String, default: '' },
  tripTypeLabel: { type: String, default: '' },
})

const { t } = useI18n()
const emptyText = usePortalRequestEmptyText()

const originDisplay = computed(() => emptyText.routeJourneyOrigin(props.origin))
const destinationDisplay = computed(() => emptyText.routeJourneyDestination(props.destination))
const originIsEmpty = computed(() => !emptyText.portalFieldHasValue(props.origin))
const destinationIsEmpty = computed(() => !emptyText.portalFieldHasValue(props.destination))

const mapsHref = computed(() => {
  const o = (props.origin || '').trim()
  const d = (props.destination || '').trim()
  if (!emptyText.portalFieldHasValue(o) || !emptyText.portalFieldHasValue(d)) return ''
  const u = new URL('https://www.google.com/maps/dir/')
  u.searchParams.set('api', '1')
  u.searchParams.set('origin', o)
  u.searchParams.set('destination', d)
  return u.toString()
})

</script>
