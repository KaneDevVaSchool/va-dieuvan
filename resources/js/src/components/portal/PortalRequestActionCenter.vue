<template>
  <section
    class="overflow-hidden rounded-xl border border-slate-200 bg-white"
    :aria-label="t('portal.action_center.aria')"
    data-testid="portal-request-action-center"
  >
    <div class="border-b border-va-100 bg-va-50/80 px-4 py-3 sm:px-5">
      <h2 class="text-sm font-bold text-va-900">{{ t('portal.action_center.heading') }}</h2>
    </div>

    <div class="grid gap-0 md:grid-cols-3 md:items-stretch">
      <div
        class="border-b border-slate-100 px-4 py-4 md:border-b-0 md:border-r"
        data-testid="portal-action-waiting"
      >
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
          {{ t('portal.action_center.waiting_label') }}
        </p>
        <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-va-700">
          {{ waitingRoleLabel }}
        </p>
        <p
          v-if="waitingPersonName"
          class="mt-1 text-base font-bold leading-snug text-slate-900 sm:text-lg"
        >
          {{ waitingPersonName }}
        </p>
        <p
          v-else-if="waitingPersonDetail"
          class="mt-1.5 text-sm font-medium leading-snug text-slate-700"
        >
          {{ waitingPersonDetail }}
        </p>
        <p
          v-if="waitingPersonName && waitingPersonDetail && waitingPersonDetail !== waitingPersonName"
          class="mt-1 text-sm tabular-nums text-slate-600"
        >
          {{ waitingPersonDetail }}
        </p>
      </div>

      <div
        v-if="slaLabel"
        class="border-b border-slate-100 px-4 py-4 md:border-b-0 md:border-r"
        data-testid="portal-action-sla"
      >
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
          {{ t('portal.action_center.sla_label') }}
        </p>
        <p
          class="mt-2 text-base font-bold leading-snug sm:text-lg"
          :class="slaToneClass"
        >
          {{ slaLabel }}
        </p>
        <p class="mt-1.5 text-xs leading-snug text-slate-500">
          {{ t('portal.action_center.sla_journey_hint') }}
        </p>
      </div>

      <div class="px-4 py-4" data-testid="portal-action-next">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
          {{ t('portal.action_center.next_label') }}
        </p>
        <p class="mt-2 text-sm font-medium leading-relaxed text-slate-800">
          {{ nextActionText }}
        </p>
      </div>
    </div>

    <div
      v-if="dispatchStrip"
      class="border-t border-slate-100 bg-slate-50/90 px-4 py-3 sm:px-5"
      data-testid="portal-action-dispatch-strip"
    >
      <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
        {{ t('portal.action_center.dispatch_strip_title') }}
      </p>
      <dl class="mt-2.5 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:gap-4">
        <div v-if="dispatchStrip.driver" class="min-w-0">
          <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
            {{ t('portal.info_cards.driver') }}
          </dt>
          <dd class="mt-0.5 truncate text-sm font-bold text-slate-900">
            {{ dispatchStrip.driver }}
          </dd>
          <dd
            v-if="dispatchStrip.driverPhone"
            class="mt-0.5 text-xs tabular-nums text-slate-600"
          >
            {{ dispatchStrip.driverPhone }}
          </dd>
        </div>
        <div v-if="dispatchStrip.vehicle || dispatchStrip.plate" class="min-w-0">
          <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
            {{ t('portal.info_cards.vehicle') }}
          </dt>
          <dd class="mt-0.5 truncate text-sm font-bold text-slate-900">
            {{ dispatchStrip.vehicle || '—' }}
          </dd>
          <dd v-if="dispatchStrip.plate" class="mt-0.5 font-mono text-xs font-medium text-slate-600">
            {{ dispatchStrip.plate }}
          </dd>
        </div>
        <div v-if="dispatchStrip.passengers != null" class="min-w-0">
          <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
            {{ t('portal.passenger_count') }}
          </dt>
          <dd class="mt-0.5 text-lg font-bold tabular-nums text-amber-950">
            {{ dispatchStrip.passengers }}
          </dd>
        </div>
        <div v-if="dispatchStrip.tripStatusLabel" class="min-w-0 lg:flex lg:flex-col lg:justify-end">
          <dt class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
            {{ t('portal.info_cards.trip_status') }}
          </dt>
          <dd class="mt-1">
            <span
              class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold"
              :class="tripStatusBadgeClass(dispatchStrip.tripStatus)"
            >
              {{ dispatchStrip.tripStatusLabel }}
            </span>
          </dd>
        </div>
      </dl>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { tripStatusBadgeClass } from '../../composables/useTripStatusWorkflow'

const props = defineProps({
  waitingRoleLabel: { type: String, required: true },
  waitingPersonName: { type: String, default: '' },
  waitingPersonDetail: { type: String, default: '' },
  slaLabel: { type: String, default: '' },
  nextActionText: { type: String, required: true },
  hoursUntilDepart: { type: Number, default: null },
  urgentThresholdHours: { type: Number, default: 24 },
  dispatchStrip: {
    type: Object,
    default: null,
  },
})

const { t } = useI18n()

const slaUrgent = computed(() => {
  const h = props.hoursUntilDepart
  if (h == null) return false
  return h >= 0 && h <= (props.urgentThresholdHours ?? 24)
})

const slaPast = computed(() => {
  const h = props.hoursUntilDepart
  return h != null && h < 0
})

const slaToneClass = computed(() => {
  if (slaPast.value) return 'text-slate-700'
  if (slaUrgent.value) return 'text-rose-700'
  return 'text-slate-900'
})
</script>
