<template>
  <div
    class="flex items-start gap-3 rounded-xl border border-emerald-200/90 bg-emerald-50/80 p-3 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/30"
  >
    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-emerald-700 shadow-sm dark:bg-emerald-950 dark:text-emerald-300">
      <TruckIcon class="h-5 w-5" />
    </div>
    <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
          <div class="text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">
            {{ vehicle?.license_plate ?? '—' }}
          </div>
          <div class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">
            {{ typeLabel }} · {{ seatsLabel }}
          </div>
        </div>
        <button
          type="button"
          class="shrink-0 text-xs font-semibold text-[#8B1A1A] underline-offset-2 hover:underline"
          @click="$emit('change')"
        >
          {{ t('trip_detail.coordination.card_change') }} →
        </button>
      </div>
      <div class="mt-2 flex flex-wrap gap-1.5">
        <span class="rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-medium text-sky-800 dark:bg-sky-950 dark:text-sky-200">GPS</span>
        <span
          v-if="vehicle?.status === 'ready'"
          class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200"
        >
          {{ t('trip_detail.coordination.vehicle_ready_chip') }}
        </span>
      </div>
      <p v-if="busy" class="mt-2 text-[11px] font-medium text-rose-700 dark:text-rose-400">
        {{ t('trip_detail.coordination.vehicle_busy_hint') }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { TruckIcon } from '@heroicons/vue/24/outline'

const props = defineProps<{
  vehicle: {
    license_plate?: string | null
    type?: string | null
    seat_count?: number | null
    status?: string | null
  } | null
  busy?: boolean
}>()

defineEmits<{
  change: []
}>()

const { t } = useI18n()

const typeLabel = computed(() => {
  const x = props.vehicle?.type
  return x && String(x).trim() ? String(x) : '—'
})

const seatsLabel = computed(() => {
  const n = props.vehicle?.seat_count
  if (n == null || !Number.isFinite(Number(n))) return t('trip_detail.coordination.seats_unknown')
  return t('trip_detail.coordination.seats_n', { n })
})
</script>
