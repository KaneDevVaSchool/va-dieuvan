<template>
  <div class="space-y-5">
    <header class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
      <div class="flex items-start gap-3">
        <span
          class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-va-800 to-va-900 text-white shadow-lg shadow-va-900/25 ring-1 ring-white/10"
        >
          <ClipboardDocumentCheckIcon class="h-7 w-7" aria-hidden="true" />
        </span>
        <div class="min-w-0">
          <h2 class="text-lg font-bold tracking-tight text-slate-900">{{ t('portal.confirm_review_title') }}</h2>
          <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ hint }}</p>
        </div>
      </div>
      <div
        class="inline-flex items-center gap-2 self-start rounded-full border px-3 py-1.5 text-xs font-bold uppercase tracking-wide shadow-sm sm:self-center"
        :class="tripBadgeClass(summary.tripType)"
      >
        <component :is="tripTypeIcon(summary.tripType)" class="h-5 w-5 shrink-0 stroke-[1.75]" aria-hidden="true" />
        {{ t(`dispatch_wizard.trip_short.${summary.tripType}`) }}
      </div>
    </header>

    <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-md shadow-slate-900/[0.06] ring-1 ring-slate-900/[0.04]">
      <dl class="divide-y divide-slate-100">
        <div
          v-if="summary.requesterDisplay"
          class="flex items-start justify-between gap-4 bg-gradient-to-r from-va-50/95 via-white to-slate-50/90 px-4 py-4 sm:px-5"
        >
          <dt class="flex shrink-0 items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
            <UserCircleIcon class="h-5 w-5 text-va-800" aria-hidden="true" />
            {{ t('portal.requester') }}
          </dt>
          <dd class="text-right text-sm font-semibold text-slate-900">{{ summary.requesterDisplay }}</dd>
        </div>

        <div class="flex items-start justify-between gap-4 px-4 py-3.5 sm:px-5">
          <dt class="flex min-w-0 items-center gap-2 text-sm font-medium text-slate-500">
            <MapPinIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
            {{ t('portal.origin') }}
          </dt>
          <dd class="max-w-[58%] text-right text-sm font-semibold text-slate-900">{{ summary.origin || '—' }}</dd>
        </div>

        <div class="flex items-start justify-between gap-4 px-4 py-3.5 sm:px-5">
          <dt class="flex min-w-0 items-center gap-2 text-sm font-medium text-slate-500">
            <FlagIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
            {{ t('portal.destination') }}
          </dt>
          <dd class="max-w-[58%] text-right text-sm font-semibold text-slate-900">{{ summary.destination || '—' }}</dd>
        </div>

        <div class="flex items-start justify-between gap-4 bg-violet-50/40 px-4 py-3.5 sm:px-5">
          <dt class="flex min-w-0 items-center gap-2 text-sm font-semibold text-violet-900">
            <ClockIcon class="h-5 w-5 shrink-0 text-violet-600" aria-hidden="true" />
            {{ t('portal.depart_at') }}
          </dt>
          <dd class="max-w-[58%] text-right text-sm font-bold text-slate-900">
            {{ summary.departPreview || summary.departAtLocal || '—' }}
          </dd>
        </div>

        <div v-if="summary.arriveByLocal" class="flex items-start justify-between gap-4 px-4 py-3.5 sm:px-5">
          <dt class="flex min-w-0 items-center gap-2 text-sm font-medium text-slate-500">
            <CalendarDaysIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
            {{ t('portal.arrive_by') }}
          </dt>
          <dd class="max-w-[58%] text-right text-sm font-semibold text-slate-900">{{ summary.arrivePreview || summary.arriveByLocal }}</dd>
        </div>

        <div
          v-if="summary.showPassengerCount && summary.passengerCount != null && summary.passengerCount >= 1"
          class="flex items-start justify-between gap-4 px-4 py-3.5 sm:px-5"
        >
          <dt class="flex min-w-0 items-center gap-2 text-sm font-medium text-slate-500">
            <UsersIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
            {{ t('portal.passenger_count') }}
          </dt>
          <dd class="text-sm font-semibold text-slate-900">{{ summary.passengerCount }}</dd>
        </div>

        <div v-if="summary.notes" class="flex items-start justify-between gap-4 px-4 py-3.5 sm:px-5">
          <dt class="flex min-w-0 shrink-0 items-center gap-2 text-sm font-medium text-slate-500">
            <ChatBubbleBottomCenterTextIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
            {{ t('portal.notes') }}
          </dt>
          <dd class="max-w-[65%] text-right text-sm font-semibold leading-relaxed text-slate-900">{{ summary.notes }}</dd>
        </div>

        <div v-if="summary.isUrgent" class="px-4 py-4 sm:px-5">
          <div
            class="flex flex-col gap-2 rounded-xl border border-rose-200/80 bg-gradient-to-br from-rose-50 via-white to-rose-50/50 px-4 py-3.5 shadow-inner ring-1 ring-rose-100/70 sm:flex-row sm:items-start sm:justify-between sm:gap-4"
          >
            <div class="flex items-center gap-2 text-sm font-bold text-rose-900">
              <ExclamationTriangleIcon class="h-5 w-5 shrink-0 text-rose-600" aria-hidden="true" />
              {{ t('portal.urgent') }}
            </div>
            <p class="text-sm font-semibold leading-snug text-rose-900 sm:max-w-[65%] sm:text-right">{{ summary.urgentReason || '—' }}</p>
          </div>
        </div>
      </dl>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import {
  BriefcaseIcon,
  CalendarDaysIcon,
  ChatBubbleBottomCenterTextIcon,
  ClipboardDocumentCheckIcon,
  ClockIcon,
  CubeIcon,
  ExclamationTriangleIcon,
  FlagIcon,
  HomeIcon,
  MapPinIcon,
  UserCircleIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'

defineProps({
  hint: { type: String, default: '' },
  summary: { type: Object, required: true },
})

const { t } = useI18n()

const TRIP_TYPE_ICONS = {
  door_to_door: HomeIcon,
  point_to_point: MapPinIcon,
  business: BriefcaseIcon,
  cargo: CubeIcon,
}

const TRIP_BADGE = {
  door_to_door: 'border-sky-200 bg-sky-50 text-sky-900 ring-sky-100',
  point_to_point: 'border-indigo-200 bg-indigo-50 text-indigo-900 ring-indigo-100',
  business: 'border-amber-200 bg-amber-50 text-amber-900 ring-amber-100',
  cargo: 'border-teal-200 bg-teal-50 text-teal-900 ring-teal-100',
}

function tripTypeIcon(tt) {
  return TRIP_TYPE_ICONS[tt] ?? MapPinIcon
}

function tripBadgeClass(tt) {
  return TRIP_BADGE[tt] ?? TRIP_BADGE.point_to_point
}
</script>
