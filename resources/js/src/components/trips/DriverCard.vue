<template>
  <div
    class="flex items-start gap-3 rounded-xl border border-sky-200/90 bg-sky-50/80 p-3 shadow-sm dark:border-sky-900/50 dark:bg-sky-950/30"
  >
    <div
      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white text-sm font-bold text-sky-800 shadow-sm dark:bg-slate-900 dark:text-sky-200"
    >
      {{ initials }}
    </div>
    <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0">
          <div class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
            {{ driver?.full_name ?? '—' }}
          </div>
          <div v-if="employeeCode" class="mt-0.5 text-[11px] text-slate-600 dark:text-slate-400">
            {{ t('trip_detail.coordination.driver_code', { code: employeeCode }) }}
          </div>
          <div class="mt-1 text-[11px] text-slate-600 dark:text-slate-400">
            <span v-if="ratingText">{{ ratingText }}</span>
            <span v-if="ratingText && tripsLine"> · </span>
            <span v-if="tripsLine">{{ tripsLine }}</span>
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
      <div class="mt-2">
        <span
          class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold"
          :class="busy ? 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-200' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200'"
        >
          {{ busy ? t('trip_detail.coordination.driver_busy') : t('trip_detail.coordination.driver_free') }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  driver: {
    full_name?: string | null
    phone?: string | null
    user?: { employee_code?: string | null } | null
  } | null
  /** Điểm sao (tuỳ backend), ví dụ 4.8 */
  rating?: number | null
  tripsThisMonth?: number | null
  busy?: boolean
}>()

defineEmits<{
  change: []
}>()

const { t } = useI18n()

const initials = computed(() => {
  const name = String(props.driver?.full_name ?? '').trim()
  if (!name || name === '—') return '?'
  const parts = name.split(/\s+/).filter(Boolean)
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const employeeCode = computed(() => props.driver?.user?.employee_code ?? '')

const ratingText = computed(() => {
  const r = props.rating
  if (r == null || !Number.isFinite(Number(r))) return ''
  return `★ ${Number(r).toFixed(1)}`
})

const tripsLine = computed(() => {
  const n = props.tripsThisMonth
  if (n == null || !Number.isFinite(Number(n))) return ''
  return t('trip_detail.coordination.driver_trips_month', { n })
})
</script>
