<template>
  <header
    class="sticky top-0 z-50 w-full border-b border-slate-200 bg-white print:hidden dark:border-slate-700 dark:bg-slate-950"
  >
    <div class="flex h-14 items-center gap-2 px-2 sm:gap-3 sm:px-3 md:px-4 lg:px-5">
      <button
        type="button"
        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800"
        :aria-label="$t('trip_detail.header.back_trips')"
        data-testid="trip-detail-back"
        @click="$emit('back')"
      >
        <ArrowLeftIcon class="h-4 w-4" />
      </button>

      <div class="min-w-0 flex-1">
        <p
          class="text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400"
        >
          {{ $t('trip_detail.header.trip_code_label') }}
        </p>
        <p
          class="truncate font-mono text-[15px] font-bold leading-tight tabular-nums text-slate-900 dark:text-slate-50"
          data-testid="trip-detail-display-code"
        >
          {{ displayCode || '…' }}
        </p>
      </div>

      <div class="flex shrink-0 flex-wrap items-center justify-end gap-1.5">
        <span
          v-if="countdownLabel"
          class="hidden rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-900 sm:inline-block"
        >
          {{ countdownLabel }}
        </span>
        <span
          class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium"
          :class="badgeClass"
          data-testid="trip-detail-status-badge"
        >
          {{ statusLabelOverride?.trim() ? statusLabelOverride : labelTripStatus(trip.status) }}
        </span>
      </div>

      <div class="flex shrink-0 items-center gap-1.5 border-l border-slate-200 pl-2 dark:border-slate-700">
        <button
          type="button"
          class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800"
          :disabled="refreshing"
          :aria-label="$t('trip_detail.actions.refresh')"
          data-testid="trip-detail-refresh"
          @click="$emit('refresh')"
        >
          <ArrowPathIcon class="h-4 w-4" :class="refreshing ? 'animate-spin' : ''" />
        </button>

        <div v-if="canReject || canApprove" class="flex items-center gap-1.5">
          <button
            v-if="canReject"
            type="button"
            class="rounded-lg border border-rose-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50 dark:border-rose-800/60 dark:bg-transparent dark:text-rose-400 dark:hover:bg-rose-950/30"
            data-testid="trip-detail-reject"
            @click="$emit('reject')"
          >
            {{ $t('trip_detail.coordination.reject') }}
          </button>
          <button
            v-if="canApprove"
            type="button"
            class="rounded-lg bg-blue-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="assignDisabled"
            :aria-label="$t('trip_detail.coordination.approve_transfer')"
            data-testid="trip-detail-approve"
            @click="$emit('approve')"
          >
            {{ $t('trip_detail.coordination.approve_transfer') }}
          </button>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { ArrowLeftIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import { labelTripStatus } from '../../util/labels'
import { resolveTripDisplayCode } from '../../util/tripDisplayCode'

type TripLike = {
  id?: number
  status?: string
  request_code?: string
  trip_number?: string
  dispatch_request?: { id?: number; created_at?: string | null } | null
}

const props = defineProps<{
  trip: TripLike
  canApprove: boolean
  canReject: boolean
  refreshing?: boolean
  assignDisabled?: boolean
  statusLabelOverride?: string | null
  /** Mã hiển thị (REQ-…); nếu bỏ trống sẽ suy từ trip. */
  displayCode?: string | null
  /** Đếm ngược khởi hành (đã format i18n). */
  countdownLabel?: string | null
}>()

defineEmits<{
  approve: []
  reject: []
  back: []
  refresh: []
}>()

const displayCode = computed(() => {
  const ext = props.displayCode?.trim()
  if (ext) return ext
  return resolveTripDisplayCode(props.trip)
})

const countdownLabel = computed(() => props.countdownLabel?.trim() || '')

const badgeClass = computed(() => {
  const s = String(props.trip?.status ?? '')
  const map: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-800',
    approved: 'bg-green-100 text-green-800',
    assigned: 'bg-blue-100 text-blue-800',
    driver_confirmed: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-teal-100 text-teal-900',
    completed: 'bg-slate-100 text-slate-700',
    cancelled: 'bg-red-100 text-red-800',
    incident: 'bg-red-100 text-red-800',
  }
  return map[s] ?? 'bg-slate-100 text-slate-700'
})
</script>
