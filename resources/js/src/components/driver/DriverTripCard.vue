<template>
  <article
    class="rounded-2xl border border-slate-700/60 bg-[#1E293B] p-4 shadow-lg shadow-black/20"
  >
    <!-- Priority 1: pickup time + type -->
    <div class="flex flex-wrap items-start gap-2 gap-y-2">
      <div class="min-w-0 flex-1">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
          {{ t('driver_home.pending_pickup_time_label') }}
        </p>
        <p class="mt-0.5 text-2xl font-bold tabular-nums tracking-tight text-white">
          {{ depart.time }}
        </p>
        <p v-if="depart.dateLine" class="mt-0.5 text-sm text-slate-400">
          {{ depart.dateLine }}
        </p>
      </div>
      <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
        <!-- Priority 2: GẤP -->
        <span
          v-if="isTripUrgent(trip)"
          class="inline-flex items-center gap-1.5 rounded-lg bg-rose-950/80 px-2.5 py-1 text-xs font-extrabold uppercase tracking-wide text-rose-100 ring-1 ring-rose-600/50"
        >
          <span class="h-2 w-2 animate-pulse rounded-full bg-rose-400/90" aria-hidden="true" />
          {{ t('driver_home.urgent_badge') }}
        </span>
        <span class="rounded-lg bg-slate-700/80 px-2 py-1 text-xs font-bold tabular-nums text-slate-200">
          {{ tripTypeBadgeText(trip) }}
        </span>
      </div>
    </div>

    <!-- Pickup / destination -->
    <div class="mt-4 space-y-3">
      <div class="flex gap-3">
        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-500/20">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-emerald-400" aria-hidden="true">
            <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .757.433c.12.065.227.115.315.142.162.04.343.04.506 0a1.16 1.16 0 0 0 .315-.142c.088-.027.195-.077.315-.142z" clip-rule="evenodd" />
          </svg>
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
            {{ t('driver_home.pending_pickup') }}
          </p>
          <p class="mt-0.5 line-clamp-2 text-base font-semibold leading-snug text-white">
            {{ tripOrigin(trip) }}
          </p>
        </div>
      </div>
      <div class="flex gap-3">
        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-500/20">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-sky-400" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 1a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 1ZM5.05 3.05a.75.75 0 0 1 1.06 0l1.062 1.06A.75.75 0 1 1 6.11 5.173L5.05 4.11a.75.75 0 0 1 0-1.06Zm9.9 0a.75.75 0 0 1 0 1.06l-1.06 1.062a.75.75 0 0 1-1.062-1.061l1.061-1.061a.75.75 0 0 1 1.06 0ZM10 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4Zm-7.75 2a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5h-1.5Zm14.5 0a.75.75 0 0 0 0 1.5h1.5a.75.75 0 0 0 0-1.5h-1.5ZM5.05 16.95a.75.75 0 0 1 1.06-1.06l-1.06-1.062a.75.75 0 0 0-1.061 1.06l1.06 1.062Zm9.9-1.06a.75.75 0 0 0-1.061-1.061l-1.062 1.06a.75.75 0 0 1 1.06 1.062l1.062-1.061ZM10 16a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 16Z" clip-rule="evenodd" />
          </svg>
        </span>
        <div class="min-w-0 flex-1">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
            {{ t('driver_home.pending_dropoff') }}
          </p>
          <p class="mt-0.5 line-clamp-2 text-base font-semibold leading-snug text-white">
            {{ tripDestination(trip) }}
          </p>
        </div>
      </div>
    </div>

    <p v-if="tripRequesterLine(trip)" class="mt-3 text-sm text-slate-400">
      {{ t('driver_home.pending_requester', { name: tripRequesterLine(trip) }) }}
    </p>
    <p v-if="passengerLine" class="text-sm text-slate-500">
      {{ passengerLine }}
    </p>

    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-stretch">
      <button
        type="button"
        :disabled="busy"
        class="flex min-h-[52px] flex-1 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-base font-bold text-white shadow-md shadow-emerald-950/40 transition-colors hover:bg-emerald-500 active:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
        @click="$emit('confirm', trip)"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 shrink-0" aria-hidden="true">
          <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143z" clip-rule="evenodd" />
        </svg>
        {{ t('driver_home.btn_confirm') }}
      </button>
      <button
        type="button"
        :disabled="busy"
        class="flex min-h-[52px] flex-1 items-center justify-center gap-2 rounded-xl border border-slate-600 bg-transparent px-4 text-base font-bold text-slate-200 transition-colors hover:bg-slate-700/50 active:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
        @click="$emit('decline', trip)"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 shrink-0" aria-hidden="true">
          <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22z" />
        </svg>
        {{ t('driver_home.btn_decline') }}
      </button>
    </div>

    <RouterLink
      :to="`/driver/trips/${trip.id}`"
      class="mt-3 flex w-full min-h-[44px] items-center justify-center rounded-xl py-2 text-sm font-semibold text-amber-400/90 underline-offset-2 hover:text-amber-300 active:opacity-80"
    >
      {{ t('driver_home.pending_view_detail') }}
    </RouterLink>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import {
  formatDepartForTrip,
  isTripUrgent,
  tripDestination,
  tripOrigin,
  tripPassengerLine,
  tripRequesterLine,
  tripTypeBadgeText,
} from '../../composables/useDriverTripDisplay'

const props = defineProps({
  trip: { type: Object, required: true },
  busy: { type: Boolean, default: false },
})

defineEmits(['confirm', 'decline'])

const { t, locale } = useI18n()

const depart = computed(() =>
  formatDepartForTrip(props.trip, locale.value === 'vi' ? 'vi' : 'en'),
)

const passengerLine = computed(() => tripPassengerLine(props.trip, t))
</script>
