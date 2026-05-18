<template>
  <div class="space-y-3 text-sm">
    <p class="font-medium text-slate-700">{{ hint }}</p>
    <dl class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-100">
      <div class="flex items-center justify-between gap-4 px-3 py-2.5">
        <dt class="text-slate-500">{{ t('dispatch_wizard.steps.type') }}</dt>
        <dd class="flex items-center gap-1.5 font-medium text-slate-900">
          <component :is="tripTypeIcon(summary.tripType)" class="h-4 w-4 text-slate-400" />
          {{ t(`dispatch_wizard.trip_short.${summary.tripType}`) }}
        </dd>
      </div>
      <div class="flex justify-between gap-4 px-3 py-2.5">
        <dt class="text-slate-500">{{ t('portal.origin') }}</dt>
        <dd class="text-right font-medium text-slate-900">{{ summary.origin || '—' }}</dd>
      </div>
      <div class="flex justify-between gap-4 px-3 py-2.5">
        <dt class="text-slate-500">{{ t('portal.destination') }}</dt>
        <dd class="text-right font-medium text-slate-900">{{ summary.destination || '—' }}</dd>
      </div>
      <div class="flex justify-between gap-4 px-3 py-2.5">
        <dt class="text-slate-500">{{ t('portal.depart_at') }}</dt>
        <dd class="text-right font-medium text-slate-900">{{ summary.departPreview || summary.departAtLocal || '—' }}</dd>
      </div>
      <div v-if="summary.arriveByLocal" class="flex justify-between gap-4 px-3 py-2.5">
        <dt class="text-slate-500">{{ t('portal.arrive_by') }}</dt>
        <dd class="text-right font-medium text-slate-900">{{ summary.arrivePreview || summary.arriveByLocal }}</dd>
      </div>
      <div
        v-if="summary.showPassengerCount && summary.passengerCount != null && summary.passengerCount >= 1"
        class="flex justify-between gap-4 px-3 py-2.5"
      >
        <dt class="text-slate-500">{{ t('portal.passenger_count') }}</dt>
        <dd class="font-medium text-slate-900">{{ summary.passengerCount }}</dd>
      </div>
      <div v-if="summary.notes" class="flex justify-between gap-4 px-3 py-2.5">
        <dt class="text-slate-500">{{ t('portal.notes') }}</dt>
        <dd class="max-w-[58%] text-right font-medium text-slate-900">{{ summary.notes }}</dd>
      </div>
      <div v-if="summary.isUrgent" class="flex justify-between gap-4 px-3 py-2.5">
        <dt class="text-slate-500">{{ t('portal.urgent') }}</dt>
        <dd class="text-right font-medium text-rose-700">{{ summary.urgentReason || '—' }}</dd>
      </div>
    </dl>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { BriefcaseIcon, CubeIcon, HomeIcon, MapPinIcon } from '@heroicons/vue/24/outline'

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

function tripTypeIcon(tt) {
  return TRIP_TYPE_ICONS[tt] ?? MapPinIcon
}
</script>
