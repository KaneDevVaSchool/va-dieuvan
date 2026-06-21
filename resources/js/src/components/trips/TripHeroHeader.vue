<template>
  <header
    class="shrink-0 border-b border-slate-200 bg-white print:hidden dark:border-slate-800 dark:bg-slate-900"
    data-testid="trip-detail-hero-header"
  >
    <!-- ── Row 0: Back + Actions ── -->
    <div
      class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-2 dark:border-slate-800 sm:px-6 lg:px-8"
    >
      <button
        type="button"
        class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 transition hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200"
        :aria-label="$t('trip_detail.header.back_trips')"
        data-testid="trip-detail-back"
        @click="$emit('back')"
      >
        <ArrowLeftIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
        {{ $t('trip_detail.header.back_trips') }}
      </button>

      <div class="flex shrink-0 items-center gap-2">
        <button
          type="button"
          class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 text-slate-500 transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800"
          :disabled="refreshing"
          :aria-label="$t('trip_detail.actions.refresh')"
          data-testid="trip-detail-refresh"
          @click="$emit('refresh')"
        >
          <ArrowPathIcon class="h-4 w-4" :class="refreshing ? 'animate-spin' : ''" aria-hidden="true" />
        </button>
        <button
          v-if="canReject"
          type="button"
          class="inline-flex h-8 items-center rounded-md border border-rose-200 px-3 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 disabled:opacity-60 dark:border-rose-800/60 dark:text-rose-300 dark:hover:bg-rose-950/40"
          data-testid="trip-detail-reject"
          @click="$emit('reject')"
        >
          {{ $t('trip_detail.coordination.reject') }}
        </button>
        <button
          v-if="canApprove"
          type="button"
          class="inline-flex h-8 items-center gap-1.5 rounded-md bg-blue-600 px-3 text-xs font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="assignDisabled"
          :aria-label="$t('trip_detail.coordination.approve_transfer')"
          data-testid="trip-detail-approve"
          @click="$emit('approve')"
        >
          <CheckBadgeIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          {{ $t('trip_detail.coordination.approve_transfer') }}
        </button>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8">
      <!-- ── Row 1: Code + Status + Countdown ── -->
      <div class="flex flex-wrap items-center gap-x-6 gap-y-2 border-b border-slate-100 py-3 dark:border-slate-800">
        <div class="min-w-0">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ $t('trip_detail.header.trip_code_label') }}
          </p>
          <h1
            class="font-mono text-xl font-bold tracking-tight tabular-nums text-slate-900 dark:text-white sm:text-2xl"
            data-testid="trip-detail-display-code"
          >
            {{ displayCode || '…' }}
          </h1>
        </div>
        <div class="h-4 w-px bg-slate-200 dark:bg-slate-700 max-sm:hidden" aria-hidden="true" />
        <div class="flex items-center gap-1.5">
          <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ $t('trip_detail.header.status_label') }}</span>
          <span
            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium"
            :class="badgeClass"
            data-testid="trip-detail-status-badge"
          >
            {{ resolvedStatusLabel }}
          </span>
        </div>
        <span
          v-if="countdownLabel"
          class="ml-auto inline-flex items-center rounded-full bg-sky-100 px-2.5 py-0.5 text-[11px] font-semibold text-sky-900 dark:bg-sky-900/40 dark:text-sky-200"
        >
          {{ countdownLabel }}
        </span>
      </div>

      <!-- ── Row 2: Route ── -->
      <div class="grid grid-cols-[1fr_auto_1fr] items-stretch border-b border-slate-100 dark:border-slate-800">
        <div class="py-3 pr-4">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ $t('trip_detail.header.lbl_origin') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-white sm:text-[15px]">
            {{ origin || empty }}
          </p>
        </div>
        <div class="flex items-center px-3 py-3" aria-hidden="true">
          <ArrowRightIcon class="h-4 w-4 shrink-0 text-slate-300 dark:text-slate-600" />
        </div>
        <div class="py-3 pl-4">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ $t('trip_detail.header.lbl_destination') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-white sm:text-[15px]">
            {{ destination || empty }}
          </p>
        </div>
      </div>

      <!-- ── Row 3: Depart / Trip type / Passengers ── -->
      <div class="grid grid-cols-2 gap-x-4 border-b border-slate-100 py-3 dark:border-slate-800 sm:grid-cols-3">
        <div>
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ $t('trip_detail.header.depart_label') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold tabular-nums text-slate-900 dark:text-white">
            {{ departSummary || empty }}
          </p>
        </div>
        <div>
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ $t('trip_detail.header.lbl_trip_type') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-white">
            {{ tripType || empty }}
          </p>
        </div>
        <div v-if="passengerLine" class="col-span-2 mt-2 sm:col-span-1 sm:mt-0">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ $t('trip_detail.header.lbl_passengers') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-white">
            {{ passengerLine }}
          </p>
        </div>
      </div>

      <!-- ── Row 4: Requester / Unit / Created ── -->
      <div class="grid grid-cols-2 gap-x-4 py-3 sm:grid-cols-3">
        <div>
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ $t('trip_detail.header.lbl_requester') }}
          </p>
          <div class="mt-0.5 flex items-center gap-1.5">
            <div
              class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-va-100 text-[9px] font-bold text-va-700 dark:bg-va-900/50 dark:text-va-300"
              aria-hidden="true"
            >{{ requesterInitials }}</div>
            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ requesterName || empty }}</p>
          </div>
        </div>
        <div>
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ $t('trip_detail.header.lbl_unit') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-white">{{ requesterUnit || empty }}</p>
        </div>
        <div class="col-span-2 mt-2 sm:col-span-1 sm:mt-0">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ $t('trip_detail.header.lbl_created') }}
          </p>
          <p class="mt-0.5 text-sm tabular-nums text-slate-700 dark:text-slate-300">{{ createdDate || empty }}</p>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  ArrowLeftIcon,
  ArrowPathIcon,
  ArrowRightIcon,
  CheckBadgeIcon,
} from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import { labelTripStatus } from '../../util/labels'

const props = defineProps<{
  displayCode?: string | null
  status?: string
  statusLabelOverride?: string | null
  countdownLabel?: string | null
  origin?: string
  destination?: string
  departSummary?: string
  tripType?: string
  passengerLine?: string
  requesterName?: string
  requesterUnit?: string
  requesterInitials?: string
  createdDate?: string
  canApprove?: boolean
  canReject?: boolean
  refreshing?: boolean
  assignDisabled?: boolean
}>()

defineEmits<{
  approve: []
  reject: []
  back: []
  refresh: []
}>()

const { t } = useI18n()
const empty = computed(() => t('trip_detail.empty.not_available'))

const countdownLabel = computed(() => props.countdownLabel?.trim() || '')

const resolvedStatusLabel = computed(() =>
  props.statusLabelOverride?.trim()
    ? props.statusLabelOverride
    : labelTripStatus(props.status),
)

const badgeClass = computed(() => {
  const s = String(props.status ?? '')
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
