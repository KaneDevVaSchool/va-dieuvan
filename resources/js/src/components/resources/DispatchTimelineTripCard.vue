<template>
  <button
    type="button"
    class="group/card absolute top-2 z-[5] flex min-h-[2.75rem] items-center gap-1 overflow-visible rounded-lg border px-2 py-1.5 pl-2.5 text-left shadow-md transition hover:brightness-[0.98] hover:shadow-lg dark:hover:brightness-110"
    :class="[
      visual.cardClass,
      selected
        ? 'ring-2 ring-teal-500 ring-offset-2 dark:ring-offset-slate-900'
        : '',
      draggable ? 'cursor-grab active:cursor-grabbing touch-none' : '',
    ]"
    :style="positionStyle"
    @pointerdown="(e) => emit('pointerdown', e)"
  >
    <span
      class="pointer-events-none absolute bottom-0 left-0 top-0 w-[3px] rounded-l-[inherit]"
      :style="{ backgroundColor: visual.barColor }"
      aria-hidden="true"
    />

    <span class="min-w-0 flex-1 select-none pl-0.5">
      <span class="flex items-baseline gap-x-1.5">
        <span class="font-medium tabular-nums">#{{ trip.id }}</span>
        <span class="text-[10px] opacity-70 tabular-nums">{{ timeRange }}</span>
      </span>
      <span
        class="mt-0.5 block truncate text-[10px] leading-snug opacity-85"
        >{{ routeLine }}</span
      >
    </span>

    <component
      :is="typeIcon"
      class="h-4 w-4 shrink-0 self-center opacity-80"
      aria-hidden="true"
    />

    <span
      v-if="conflict"
      class="absolute -right-1 -top-1 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-rose-600 text-[9px] font-bold text-white shadow-sm"
    >
      !
    </span>

    <div
      class="pointer-events-none absolute left-0 top-full z-[60] mt-1.5 hidden w-max max-w-[min(18rem,calc(100vw-2rem))] rounded-xl border border-slate-200/90 bg-white p-2.5 text-left text-xs font-normal normal-case text-slate-700 shadow-xl ring-1 ring-slate-900/5 group-hover/card:block dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-950/40"
      role="tooltip"
    >
      <span
        class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold"
        :class="statusPillClass"
        >{{ statusLabel }}</span
      >
      <dl class="mt-2 space-y-1.5 text-[11px] leading-snug">
        <div>
          <dt class="font-medium text-slate-500 dark:text-slate-400">
            {{ t('resources_dashboard.field_time') }}
          </dt>
          <dd class="text-slate-800 dark:text-slate-100">{{ timeRange }}</dd>
        </div>
        <div>
          <dt class="font-medium text-slate-500 dark:text-slate-400">
            {{ t('resources_dashboard.timeline_tooltip_pickup') }}
          </dt>
          <dd class="text-slate-800 dark:text-slate-100">{{ originText }}</dd>
        </div>
        <div>
          <dt class="font-medium text-slate-500 dark:text-slate-400">
            {{ t('resources_dashboard.timeline_tooltip_dropoff') }}
          </dt>
          <dd class="text-slate-800 dark:text-slate-100">
            {{ destinationText }}
          </dd>
        </div>
        <div>
          <dt class="font-medium text-slate-500 dark:text-slate-400">
            {{ t('resources_dashboard.field_trip_type') }}
          </dt>
          <dd class="text-slate-800 dark:text-slate-100">{{ tripTypeLabel }}</dd>
        </div>
        <div v-if="driverLabel">
          <dt class="font-medium text-slate-500 dark:text-slate-400">
            {{ t('resources_dashboard.field_driver') }}
          </dt>
          <dd class="text-slate-800 dark:text-slate-100">{{ driverLabel }}</dd>
        </div>
      </dl>
    </div>
  </button>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useDispatchTimelineTripCard } from '../../composables/useDispatchTimelineTripCard'

const props = defineProps({
  trip: { type: Object, required: true },
  conflict: { type: Boolean, default: false },
  selected: { type: Boolean, default: false },
  draggable: { type: Boolean, default: false },
  positionStyle: { type: Object, required: true },
})

const emit = defineEmits(['pointerdown'])

const { t } = useI18n()

const {
  visual,
  typeIcon,
  timeRange,
  routeLine,
  originText,
  destinationText,
  tripTypeLabel,
  statusLabel,
  statusPillClass,
  driverLabel,
} = useDispatchTimelineTripCard(() => props.trip)
</script>
