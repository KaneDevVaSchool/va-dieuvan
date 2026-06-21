<template>
  <header
    class="shrink-0 border-b border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900"
    :class="urgentAccent ? 'border-b-rose-200/80 dark:border-rose-900/50' : ''"
    data-testid="staff-request-hero-header"
  >
    <div class="px-4 py-4 sm:px-6 lg:px-8">
      <RouterLink
        :to="backTo"
        class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200"
        :aria-label="backAriaLabel"
        data-testid="staff-request-back-link"
      >
        <ArrowLeftIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
        {{ t('request_detail.hero_back_list') }}
      </RouterLink>

      <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 flex-1 space-y-3">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                {{ t('request_detail.hero_lbl_request_id') }}
              </p>
              <p class="font-mono text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                {{ requestRefCode }}
              </p>
              <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                {{ t('request_detail.hero_lbl_module') }}:
                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ t('request_detail.ops_header_eyebrow') }}</span>
              </p>
            </div>

            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
              <button
                type="button"
                class="inline-flex h-10 items-center gap-1.5 rounded-lg border px-3.5 text-sm font-semibold transition"
                :class="
                  pdfExportDisabled
                    ? 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-600'
                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'
                "
                :disabled="pdfBusy || pdfExportDisabled"
                :title="pdfExportDisabled ? t('request_detail.pdf_locked_tooltip') : t('request_detail.export_pdf')"
                data-testid="staff-request-export-pdf"
                @click="$emit('export-pdf')"
              >
                <ArrowDownTrayIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                <span class="hidden sm:inline">{{ pdfBusy ? t('request_detail.pdf_export_loading') : t('request_detail.export_pdf') }}</span>
              </button>
              <template v-if="showApproveActions">
                <button
                  type="button"
                  class="inline-flex h-10 items-center gap-1.5 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-60"
                  :disabled="d2dActing"
                  data-testid="staff-request-approve"
                  @click="$emit('approve')"
                >
                  <CheckBadgeIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                  {{ t('request_detail.d2d_approve_confirm_btn') }}
                </button>
                <button
                  type="button"
                  class="inline-flex h-10 items-center rounded-lg border border-rose-200 px-4 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 disabled:opacity-60 dark:border-rose-800/60 dark:text-rose-300 dark:hover:bg-rose-950/40"
                  :disabled="d2dActing"
                  data-testid="staff-request-reject"
                  @click="$emit('reject')"
                >
                  {{ t('request_detail.d2d_reject_confirm_btn') }}
                </button>
              </template>
            </div>
          </div>

          <dl class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
            <div class="min-w-[8rem]">
              <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                {{ t('request_detail.hero_lbl_status') }}
              </dt>
              <dd class="mt-1">
                <StatusBadge :status="status" />
              </dd>
            </div>
            <div class="min-w-[8rem]">
              <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                {{ t('request_detail.ops_header_priority') }}
              </dt>
              <dd class="mt-1 font-semibold text-slate-900 dark:text-slate-100">{{ priorityLabel }}</dd>
            </div>
          </dl>

          <div class="flex flex-col gap-3 sm:flex-row sm:items-stretch sm:gap-4">
            <div
              class="min-w-0 flex-1 rounded-xl border border-slate-200/90 bg-slate-50/50 px-4 py-3 dark:border-slate-700/80 dark:bg-slate-800/40"
            >
              <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                {{ t('request_detail.lbl_origin') }}
              </p>
              <p class="mt-1 text-base font-semibold leading-snug text-slate-900 dark:text-white">{{ origin || empty }}</p>
            </div>
            <div class="hidden shrink-0 items-center justify-center sm:flex" aria-hidden="true">
              <ArrowRightIcon class="h-5 w-5 text-slate-300 dark:text-slate-600" />
            </div>
            <div
              class="min-w-0 flex-1 rounded-xl border border-slate-200/90 bg-slate-50/50 px-4 py-3 dark:border-slate-700/80 dark:bg-slate-800/40"
            >
              <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
                {{ t('request_detail.lbl_destination') }}
              </p>
              <p class="mt-1 text-base font-semibold leading-snug text-slate-900 dark:text-white">{{ destination || empty }}</p>
            </div>
          </div>

          <p v-if="departSummary" class="text-sm text-slate-600 dark:text-slate-400">
            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ t('request_detail.hero_depart_label') }}:</span>
            <span class="ml-1 tabular-nums text-slate-900 dark:text-slate-100">{{ departSummary }}</span>
          </p>
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
  ArrowRightIcon,
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
  urgentAccent: { type: Boolean, default: false },
  pdfBusy: { type: Boolean, default: false },
  pdfExportDisabled: { type: Boolean, default: false },
  showApproveActions: { type: Boolean, default: false },
  d2dActing: { type: Boolean, default: false },
})

defineEmits(['export-pdf', 'approve', 'reject'])

const { t } = useI18n()
const empty = computed(() => t('request_detail.ops_no_data'))
</script>
