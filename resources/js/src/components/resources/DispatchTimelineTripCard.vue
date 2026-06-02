<template>
  <button
    type="button"
    class="group/card absolute top-2 z-[5] flex min-h-[2.75rem] items-center gap-1 overflow-visible rounded-lg border px-2 py-1.5 pl-2 text-left shadow-md"
    :class="[
      visual.cardClass,
      cardHoverClass,
      selected ? cardSelectedClass : '',
      draggable ? 'cursor-grab active:cursor-grabbing touch-none' : '',
    ]"
    :style="positionStyle"
    @pointerdown="(e) => emit('pointerdown', e)"
  >
    <span
      class="pointer-events-none absolute bottom-0 left-0 top-0 w-1 rounded-l-lg shadow-[2px_0_8px_-2px_rgba(0,0,0,0.12)] dark:shadow-[2px_0_8px_-2px_rgba(0,0,0,0.4)]"
      :style="{ backgroundColor: visual.barColor }"
      aria-hidden="true"
    />

    <span class="min-w-0 flex-1 select-none pl-1">
      <span class="flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5">
        <span
          class="text-[11px] font-bold tabular-nums tracking-tight text-slate-900 dark:text-white"
          >#{{ trip.id }}</span
        >
        <span
          class="text-[10px] font-medium tabular-nums opacity-80 dark:opacity-90"
          >{{ timeRange }}</span
        >
      </span>
      <span
        class="mt-0.5 line-clamp-2 text-[10px] font-medium leading-snug opacity-90 dark:opacity-95"
        >{{ routeLine }}</span
      >
    </span>

    <component
      :is="typeIcon"
      class="h-4 w-4 shrink-0 self-center opacity-80 transition-opacity group-hover/card:opacity-100"
      aria-hidden="true"
    />

    <span
      v-if="conflict"
      class="absolute -right-1 -top-1 z-[1] inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-rose-600 text-[9px] font-bold text-white shadow-sm"
    >
      !
    </span>

    <div
      class="pointer-events-none absolute z-[60] hidden w-max max-w-[min(18rem,calc(100vw-2rem))] rounded-2xl border border-slate-200/90 bg-white p-3 text-left text-xs font-normal normal-case text-slate-700 shadow-xl ring-1 ring-slate-900/5 group-hover/card:block dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-950/40"
      :class="tooltipPlacementClass"
      role="tooltip"
    >
      <div class="flex flex-wrap items-center gap-2">
        <span
          class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold"
          :class="statusPillClass"
          >{{ statusLabel }}</span
        >
        <span class="font-mono text-[10px] font-medium text-slate-400 dark:text-slate-500"
          >#{{ trip.id }}</span
        >
      </div>
      <dl class="mt-2.5 divide-y divide-slate-100 text-[11px] leading-snug dark:divide-slate-700/80">
        <div class="space-y-0.5 pb-2 first:pt-0">
          <dt class="font-medium text-slate-500 dark:text-slate-400">
            {{ t('resources_dashboard.field_time') }}
          </dt>
          <dd class="text-slate-800 dark:text-slate-100">{{ timeRange }}</dd>
        </div>
        <div class="space-y-0.5 py-2">
          <dt class="font-medium text-slate-500 dark:text-slate-400">
            {{ t('resources_dashboard.timeline_tooltip_pickup') }}
          </dt>
          <dd class="text-slate-800 dark:text-slate-100">{{ originText }}</dd>
        </div>
        <div class="space-y-0.5 py-2">
          <dt class="font-medium text-slate-500 dark:text-slate-400">
            {{ t('resources_dashboard.timeline_tooltip_dropoff') }}
          </dt>
          <dd class="text-slate-800 dark:text-slate-100">
            {{ destinationText }}
          </dd>
        </div>
        <div class="space-y-0.5 py-2">
          <dt class="font-medium text-slate-500 dark:text-slate-400">
            {{ t('resources_dashboard.field_trip_type') }}
          </dt>
          <dd class="text-slate-800 dark:text-slate-100">{{ tripTypeLabel }}</dd>
        </div>
        <div v-if="driverLabel" class="space-y-0.5 pt-2">
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
import { computed } from 'vue'
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
  cardHoverClass,
  cardSelectedClass,
} = useDispatchTimelineTripCard(() => props.trip)

/** Tooltip above card by default; below when bar starts near top of track (low %). */
const tooltipPlacementClass = computed(() => {
  const left = props.positionStyle?.left
  const pct =
    typeof left === 'string'
      ? parseFloat(left)
      : typeof left === 'number'
        ? left
        : 50
  const nearStart = !Number.isNaN(pct) && pct < 12
  if (nearStart) {
    return 'left-1/2 top-full mt-1.5 -translate-x-1/2'
  }
  const nearEnd = !Number.isNaN(pct) && pct > 72
  const horizontal = nearEnd ? 'right-0 left-auto' : 'left-1/2 -translate-x-1/2'
  return `${horizontal} bottom-full mb-1.5`
})
</script>
