<template>
  <header
    class="shrink-0 border-b bg-white dark:bg-slate-900"
    :class="urgentAccent
      ? 'border-rose-200/70 dark:border-rose-900/40'
      : 'border-slate-200 dark:border-slate-800'"
    data-testid="staff-request-hero-header"
  >
    <!-- ── Row 0: Back + Actions ── -->
    <div
      class="flex items-center justify-between gap-4 border-b px-4 py-2 sm:px-6 lg:px-8"
      :class="urgentAccent
        ? 'border-rose-100/80 dark:border-rose-900/30'
        : 'border-slate-100 dark:border-slate-800'"
    >
      <RouterLink
        :to="backTo"
        class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 transition hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200"
        :aria-label="backAriaLabel"
        data-testid="staff-request-back-link"
      >
        <ArrowLeftIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
        {{ t('request_detail.hero_back_list') }}
      </RouterLink>

      <div class="flex shrink-0 items-center gap-2">
        <button
          type="button"
          class="inline-flex h-8 items-center gap-1.5 rounded-md border px-3 text-xs font-semibold transition"
          :class="pdfExportDisabled
            ? 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-600'
            : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300 dark:hover:bg-slate-700'"
          :disabled="pdfBusy || pdfExportDisabled"
          :title="pdfExportDisabled ? t('request_detail.pdf_locked_tooltip') : t('request_detail.export_pdf')"
          data-testid="staff-request-export-pdf"
          @click="$emit('export-pdf')"
        >
          <ArrowDownTrayIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          {{ pdfBusy ? t('request_detail.pdf_export_loading') : t('request_detail.export_pdf') }}
        </button>
        <template v-if="showApproveActions">
          <button
            type="button"
            class="inline-flex h-8 items-center gap-1.5 rounded-md bg-emerald-600 px-3 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60"
            :disabled="d2dActing"
            data-testid="staff-request-approve"
            @click="$emit('approve')"
          >
            <CheckBadgeIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            {{ t('request_detail.d2d_approve_confirm_btn') }}
          </button>
          <button
            type="button"
            class="inline-flex h-8 items-center rounded-md border border-rose-200 px-3 text-xs font-semibold text-rose-700 transition hover:bg-rose-50 disabled:opacity-60 dark:border-rose-800/60 dark:text-rose-300 dark:hover:bg-rose-950/40"
            :disabled="d2dActing"
            data-testid="staff-request-reject"
            @click="$emit('reject')"
          >
            {{ t('request_detail.d2d_reject_confirm_btn') }}
          </button>
        </template>
      </div>
    </div>

    <div class="px-4 sm:px-6 lg:px-8">
      <!-- ── Row 1: ID + Status + Priority ── -->
      <div
        class="flex flex-wrap items-center gap-x-6 gap-y-2 border-b py-3"
        :class="urgentAccent ? 'border-rose-100/80 dark:border-rose-900/30' : 'border-slate-100 dark:border-slate-800'"
      >
        <h1 class="font-mono text-xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">
          {{ requestRefCode }}
        </h1>
        <div class="h-4 w-px bg-slate-200 dark:bg-slate-700 max-sm:hidden" aria-hidden="true" />
        <div class="flex items-center gap-1.5">
          <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.hero_lbl_status') }}</span>
          <StatusBadge :status="status" />
        </div>
        <div class="h-4 w-px bg-slate-200 dark:bg-slate-700 max-sm:hidden" aria-hidden="true" />
        <div class="flex items-center gap-1.5">
          <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.ops_header_priority') }}</span>
          <span
            class="inline-flex items-center gap-1 text-sm font-semibold"
            :class="urgentAccent ? 'text-rose-700 dark:text-rose-300' : 'text-slate-700 dark:text-slate-200'"
          >
            <BoltIcon v-if="urgentAccent" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            {{ priorityLabel }}
          </span>
        </div>
        <span v-if="showRecurring" class="ml-auto inline-flex items-center gap-1 text-[11px] font-medium text-indigo-600 dark:text-indigo-400">
          <ArrowPathIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          {{ t('request_detail.badge_recurring') }}
        </span>
      </div>

      <!-- ── Row 2: Route ── -->
      <div
        class="grid grid-cols-[1fr_auto_1fr] items-stretch border-b"
        :class="urgentAccent ? 'border-rose-100/80 dark:border-rose-900/30' : 'border-slate-100 dark:border-slate-800'"
      >
        <div class="py-3 pr-4">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ t('request_detail.lbl_origin') }}
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
            {{ t('request_detail.lbl_destination') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-white sm:text-[15px]">
            {{ destination || empty }}
          </p>
        </div>
      </div>

      <!-- ── Row 3: Depart / Trip type / Passengers ── -->
      <div
        class="grid grid-cols-2 gap-x-4 border-b py-3 sm:grid-cols-3"
        :class="urgentAccent ? 'border-rose-100/80 dark:border-rose-900/30' : 'border-slate-100 dark:border-slate-800'"
      >
        <div>
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ t('request_detail.hero_depart_label') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold tabular-nums text-slate-900 dark:text-white">
            {{ departSummary || empty }}
          </p>
        </div>
        <div>
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ t('request_detail.hero_lbl_trip_type') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-white">
            {{ tripType || empty }}
          </p>
        </div>
        <div v-if="passengerLine" class="col-span-2 mt-2 sm:col-span-1 sm:mt-0">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ t('request_detail.hero_lbl_passengers') }}
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
            {{ t('request_detail.hero_lbl_requester') }}
          </p>
          <div class="mt-0.5 flex items-center gap-1.5">
            <div
              v-if="!requesterAvatar"
              class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-va-100 text-[9px] font-bold text-va-700 dark:bg-va-900/50 dark:text-va-300"
              aria-hidden="true"
            >{{ requesterInitials }}</div>
            <img v-else :src="requesterAvatar" alt="" class="h-5 w-5 shrink-0 rounded-full object-cover" />
            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ requesterName || empty }}</p>
          </div>
        </div>
        <div>
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ t('request_detail.hero_lbl_unit') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-white">{{ requesterUnit || empty }}</p>
        </div>
        <div class="col-span-2 mt-2 sm:col-span-1 sm:mt-0">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ t('request_detail.hero_lbl_created') }}
          </p>
          <p class="mt-0.5 text-sm tabular-nums text-slate-700 dark:text-slate-300">{{ createdDate || empty }}</p>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowPathIcon,
  ArrowRightIcon,
  BoltIcon,
  CheckBadgeIcon,
} from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import StatusBadge from '../../ui/StatusBadge.vue'

defineProps({
  backTo: { type: [String, Object], required: true },
  backAriaLabel: { type: String, required: true },
  requestRefCode: { type: String, required: true },
  status: { type: String, required: true },
  priorityLabel: { type: String, required: true },
  origin: { type: String, default: '' },
  destination: { type: String, default: '' },
  departSummary: { type: String, default: '' },
  tripType: { type: String, default: '' },
  passengerLine: { type: String, default: '' },
  requesterName: { type: String, default: '' },
  requesterUnit: { type: String, default: '' },
  requesterInitials: { type: String, default: '?' },
  requesterAvatar: { type: String, default: '' },
  createdDate: { type: String, default: '' },
  urgentAccent: { type: Boolean, default: false },
  showRecurring: { type: Boolean, default: false },
  pdfBusy: { type: Boolean, default: false },
  pdfExportDisabled: { type: Boolean, default: false },
  showApproveActions: { type: Boolean, default: false },
  d2dActing: { type: Boolean, default: false },
})

defineEmits(['export-pdf', 'approve', 'reject'])

const { t } = useI18n()
const empty = computed(() => t('request_detail.ops_no_data'))
</script>
