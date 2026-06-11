<template>
  <div
    class="overflow-hidden rounded-[12px] border-[0.5px] border-slate-200/90 border-t-[2px] border-t-[#1D9E75] bg-emerald-50/35 p-3 dark:border-slate-600/80 dark:bg-emerald-950/20"
  >
    <div class="flex items-start gap-3">
      <div
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] border-[0.5px] border-slate-200/80 bg-white text-[#1D9E75] dark:border-slate-700 dark:bg-emerald-950/40 dark:text-[#4dcf9a]"
      >
        <TruckIcon class="h-5 w-5" aria-hidden="true" />
      </div>
      <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0 flex-1 truncate text-[13px] font-medium tabular-nums text-slate-900 dark:text-slate-100">
            <EmptyValue :value="vehicle?.license_plate" empty-key="trip_detail.empty.plate" />
          </div>
          <button
            v-if="props.allowChange"
            type="button"
            class="shrink-0 rounded-[12px] border-[0.5px] border-[#8B1A1A]/50 px-2 py-1 text-[11px] font-normal text-[#8B1A1A] hover:bg-[#8B1A1A]/5 dark:border-[#8B1A1A]/40 dark:text-[#e85c5c] dark:hover:bg-[#8B1A1A]/10"
            @click="$emit('change')"
          >
            {{ t('trip_detail.coordination.card_change') }} →
          </button>
        </div>
        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
          <span
            class="rounded-full border-[0.5px] border-[#378ADD]/45 px-2 py-0.5 text-[12px] font-normal text-[#378ADD] dark:border-[#378ADD]/40 dark:text-[#6cb3f5]"
          >
            GPS
          </span>
          <span
            v-if="seatChipText"
            class="rounded-full border-[0.5px] border-[#1D9E75]/45 px-2 py-0.5 text-[12px] font-normal text-[#1D9E75] dark:border-[#1D9E75]/40 dark:text-[#4dcf9a]"
          >
            {{ seatChipText }}
          </span>
          <span
            v-if="vehicle?.status === 'ready'"
            class="rounded-full border-[0.5px] border-[#1D9E75]/45 px-2 py-0.5 text-[12px] font-normal text-[#1D9E75] dark:border-[#1D9E75]/40 dark:text-[#4dcf9a]"
          >
            {{ t('trip_detail.coordination.vehicle_ready_chip') }}
          </span>
        </div>
        <div class="mt-0.5 text-[13px] font-normal text-slate-600 dark:text-slate-400">
          <EmptyValue :value="typeLabelRaw" empty-key="trip_detail.empty.vehicle_type" />
        </div>
        <p v-if="busy" class="mt-2 text-[11px] font-normal text-rose-700 dark:text-rose-400">
          {{ t('trip_detail.coordination.vehicle_busy_hint') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { TruckIcon } from '@heroicons/vue/24/outline'
import EmptyValue from '../ui/EmptyValue.vue'

const props = withDefaults(
  defineProps<{
    vehicle: {
      license_plate?: string | null
      type?: string | null
      seat_count?: number | null
      status?: string | null
    } | null
    busy?: boolean
    /** false = ẩn nút Đổi (vd. sau khi đã gán trên timeline) */
    allowChange?: boolean
  }>(),
  { allowChange: true },
)

defineEmits<{
  change: []
}>()

const { t } = useI18n()

const typeLabelRaw = computed(() => props.vehicle?.type ?? '')

const seatChipText = computed(() => {
  const n = props.vehicle?.seat_count
  if (n == null || !Number.isFinite(Number(n))) return ''
  return t('trip_detail.coordination.seats_n', { n })
})
</script>
