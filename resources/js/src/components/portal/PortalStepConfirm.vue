<template>
  <div class="space-y-5">
    <header class="border-b border-slate-100 pb-4">
      <h2 class="text-lg font-semibold text-slate-900">{{ t('portal.confirm_review_title') }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ hint }}</p>
      <div
        class="mt-3 inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-wide"
        :class="tripBadgeClass(summary.tripType)"
      >
        <component :is="tripTypeIcon(summary.tripType)" class="h-5 w-5 shrink-0 stroke-[1.75]" aria-hidden="true" />
        {{ t(`dispatch_wizard.trip_short.${summary.tripType}`) }}
      </div>
    </header>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/[0.04]">
      <dl class="divide-y divide-slate-100">
        <div v-if="summary.requesterDisplay" class="flex items-start justify-between gap-4 bg-slate-50/70 px-4 py-4 sm:px-5">
          <dt class="flex shrink-0 items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
            <UserCircleIcon class="h-5 w-5 text-slate-500" aria-hidden="true" />
            {{ t('portal.requester') }}
          </dt>
          <dd class="text-right text-sm font-medium text-slate-900">{{ summary.requesterDisplay }}</dd>
        </div>

        <div v-if="summary.purpose" class="flex items-start justify-between gap-4 px-4 py-3.5 sm:px-5">
          <dt class="flex min-w-0 items-center gap-2 text-sm font-medium text-slate-600">
            <DocumentTextIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
            {{ t('portal.purpose') }}
          </dt>
          <dd class="max-w-[58%] text-right text-sm font-medium text-slate-900">{{ summary.purpose }}</dd>
        </div>

        <div class="flex items-start justify-between gap-4 px-4 py-3.5 sm:px-5">
          <dt class="flex min-w-0 items-center gap-2 text-sm font-medium text-slate-600">
            <MapPinIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
            {{ t('portal.origin') }}
          </dt>
          <dd class="max-w-[58%] text-right text-sm font-medium text-slate-900">{{ summary.origin || '—' }}</dd>
        </div>

        <div class="flex items-start justify-between gap-4 px-4 py-3.5 sm:px-5">
          <dt class="flex min-w-0 items-center gap-2 text-sm font-medium text-slate-600">
            <FlagIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
            {{ t('portal.destination') }}
          </dt>
          <dd class="max-w-[58%] text-right text-sm font-medium text-slate-900">{{ summary.destination || '—' }}</dd>
        </div>

        <div class="flex items-start justify-between gap-4 px-4 py-3.5 sm:px-5">
          <dt class="flex min-w-0 items-center gap-2 text-sm font-medium text-slate-600">
            <ClockIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
            {{ t('portal.depart_at') }}
          </dt>
          <dd class="max-w-[58%] text-right text-sm font-medium text-slate-900">
            {{ summary.departPreview || summary.departAtLocal || '—' }}
          </dd>
        </div>

        <div v-if="summary.arriveByLocal" class="flex items-start justify-between gap-4 px-4 py-3.5 sm:px-5">
          <dt class="flex min-w-0 items-center gap-2 text-sm font-medium text-slate-600">
            <CalendarDaysIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
            {{ t('portal.arrive_by') }}
          </dt>
          <dd class="max-w-[58%] text-right text-sm font-medium text-slate-900">{{ summary.arrivePreview || summary.arriveByLocal }}</dd>
        </div>

        <div
          v-if="summary.showPassengerCount && summary.passengerCount != null && summary.passengerCount >= 1"
          class="flex items-start justify-between gap-4 px-4 py-3.5 sm:px-5"
        >
          <dt class="flex min-w-0 items-center gap-2 text-sm font-medium text-slate-600">
            <UsersIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
            {{ t('portal.passenger_count') }}
          </dt>
          <dd class="text-sm font-medium text-slate-900">{{ summary.passengerCount }}</dd>
        </div>

        <div v-if="summary.notes" class="flex items-start justify-between gap-4 px-4 py-3.5 sm:px-5">
          <dt class="flex min-w-0 shrink-0 items-center gap-2 text-sm font-medium text-slate-600">
            <ChatBubbleBottomCenterTextIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
            {{ t('portal.notes') }}
          </dt>
          <dd class="max-w-[65%] text-right text-sm font-medium leading-relaxed text-slate-900">{{ summary.notes }}</dd>
        </div>

        <div v-if="summary.isUrgent" class="px-4 py-4 sm:px-5">
          <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3.5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
              <div class="flex items-center gap-2 text-sm font-semibold text-rose-900">
                <ExclamationTriangleIcon class="h-5 w-5 shrink-0 text-rose-600" aria-hidden="true" />
                {{ t('portal.urgent') }}
              </div>
              <p class="text-sm font-medium leading-snug text-rose-900 sm:max-w-[65%] sm:text-right">{{ summary.urgentReason || '—' }}</p>
            </div>
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
  ClockIcon,
  CubeIcon,
  DocumentTextIcon,
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
  door_to_door: 'border-violet-200 bg-violet-50 text-violet-900',
  point_to_point: 'border-sky-200 bg-sky-50 text-sky-900',
  business: 'border-emerald-200 bg-emerald-50 text-emerald-900',
  cargo: 'border-orange-200 bg-orange-50 text-orange-900',
}

function tripTypeIcon(tt) {
  return TRIP_TYPE_ICONS[tt] ?? MapPinIcon
}

function tripBadgeClass(tt) {
  return TRIP_BADGE[tt] ?? TRIP_BADGE.point_to_point
}
</script>
