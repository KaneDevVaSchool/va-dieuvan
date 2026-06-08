<template>
  <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:gap-6">
      <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
        <MapPinIcon class="h-5 w-5" aria-hidden="true" />
      </div>

      <div class="min-w-0 flex-1 md:grid md:grid-cols-[1fr_auto_1fr] md:items-center md:gap-4">
        <div class="min-w-0">
          <p class="text-xs font-medium text-slate-500">{{ t('portal.origin') }}</p>
          <p class="mt-1 text-base font-semibold leading-snug text-slate-900">{{ origin }}</p>
        </div>

        <div class="hidden items-center justify-center md:flex" aria-hidden="true">
          <ArrowLongRightIcon class="h-6 w-6 text-slate-400" />
        </div>
        <div class="flex items-center gap-2 py-1 md:hidden" aria-hidden="true">
          <div class="h-px min-w-[1rem] flex-1 bg-slate-200" />
          <ArrowDownIcon class="h-4 w-4 text-slate-400" />
          <div class="h-px min-w-[1rem] flex-1 bg-slate-200" />
        </div>

        <div class="min-w-0">
          <p class="text-xs font-medium text-slate-500">{{ t('portal.destination') }}</p>
          <p class="mt-1 text-base font-semibold leading-snug text-slate-900">{{ destination }}</p>
        </div>
      </div>

      <div
        v-if="metaLine || mapsHref"
        class="flex flex-col gap-2 border-t border-slate-100 pt-3 md:w-48 md:border-l md:border-t-0 md:pl-5 md:pt-0"
      >
        <p v-if="metaLine" class="text-sm text-slate-600">{{ metaLine }}</p>
        <a
          v-if="mapsHref"
          :href="mapsHref"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex items-center gap-1 text-sm font-semibold text-va-800 hover:underline"
        >
          {{ t('portal.detail_journey.open_maps') }}
          <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5" aria-hidden="true" />
        </a>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowDownIcon,
  ArrowLongRightIcon,
  ArrowTopRightOnSquareIcon,
  MapPinIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  origin: { type: String, default: '—' },
  destination: { type: String, default: '—' },
  departAt: { type: String, default: '' },
  tripTypeLabel: { type: String, default: '' },
})

const { t } = useI18n()

const mapsHref = computed(() => {
  const o = (props.origin || '').trim()
  const d = (props.destination || '').trim()
  if (!o || !d || o === '—' || d === '—') return ''
  const u = new URL('https://www.google.com/maps/dir/')
  u.searchParams.set('api', '1')
  u.searchParams.set('origin', o)
  u.searchParams.set('destination', d)
  return u.toString()
})

const metaLine = computed(() => {
  const parts = []
  if (props.tripTypeLabel) parts.push(props.tripTypeLabel)
  if (props.departAt) parts.push(props.departAt)
  return parts.join(' · ')
})
</script>
