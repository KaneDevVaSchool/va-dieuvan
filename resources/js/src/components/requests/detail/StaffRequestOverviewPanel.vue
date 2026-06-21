<template>
  <div class="space-y-0">
    <!-- Workflow progress — first in overview -->
    <div
      class="overflow-hidden border-b border-slate-200 bg-white px-4 py-2.5 dark:border-slate-800 dark:bg-slate-900 sm:px-5"
      data-testid="staff-request-overview-timeline"
    >
      <PortalStatusTimeline
        :title="t('portal.timeline_heading')"
        :steps="timelineSteps"
        variant="staff"
        embedded
      />
    </div>

    <!-- Dispatch + cost summary (driver, vehicle, cost) — only when trip exists -->
    <div v-if="req.trip" class="overflow-hidden border-y border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
      <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-4 py-2.5 dark:border-slate-800">
        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
          {{ t('request_detail.overview_group_dispatch') }}
        </p>
        <RouterLink
          :to="`/trips/${req.trip.id}`"
          class="inline-flex items-center gap-1 text-xs font-semibold text-va-700 transition hover:text-va-900 dark:text-va-400 dark:hover:text-va-200"
          data-testid="staff-request-linked-trip"
        >
          <span>{{ t('request_detail.ops_linked_trip') }} {{ linkedTripCode }}</span>
          <ArrowTopRightOnSquareIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
        </RouterLink>
      </div>
      <dl class="grid grid-cols-2 divide-x divide-y divide-slate-100 dark:divide-slate-800 sm:grid-cols-4">
        <div class="px-4 py-3">
          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.overview_lbl_driver') }}</dt>
          <dd class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ req.trip.driver?.full_name || req.trip.driver?.name || rdEmptyLabel(t, 'driver') }}</dd>
        </div>
        <div class="px-4 py-3">
          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.overview_lbl_vehicle') }}</dt>
          <dd class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ req.trip.vehicle?.type || rdEmptyLabel(t, 'vehicle') }}</dd>
        </div>
        <div class="px-4 py-3">
          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.overview_lbl_plate') }}</dt>
          <dd class="mt-0.5 text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ req.trip.vehicle?.license_plate || rdEmptyLabel(t, 'plate') }}</dd>
        </div>
        <div class="px-4 py-3">
          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.overview_lbl_cost') }}</dt>
          <dd class="mt-0.5 text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ declaredTotalDisplay }}</dd>
        </div>
      </dl>
    </div>

    <!-- Single unified request-info card -->
    <div class="overflow-hidden border-y border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
      <div class="border-b border-slate-100 px-4 py-2.5 dark:border-slate-800">
        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
          {{ t('request_detail.hero_card_request_info') }}
        </p>
      </div>

      <dl class="grid grid-cols-1 divide-y divide-slate-100 dark:divide-slate-800 sm:grid-cols-2 sm:divide-x lg:grid-cols-3">
        <!-- Mục đích -->
        <div class="px-4 py-3 sm:col-span-2 lg:col-span-1">
          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.ops_lbl_purpose') }}</dt>
          <dd class="mt-1 text-sm leading-relaxed text-slate-800 dark:text-slate-200" :class="nz(formData.purpose) ? '' : 'italic text-slate-400 dark:text-slate-500'">
            {{ nz(formData.purpose) || rdEmptyLabel(t, 'purpose') }}
          </dd>
        </div>
        <!-- Căn cứ -->
        <div class="px-4 py-3 sm:col-span-2 lg:col-span-2">
          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.ops_lbl_basis') }}</dt>
          <dd class="mt-1 text-sm leading-relaxed text-slate-800 dark:text-slate-200" :class="basisText ? '' : 'italic text-slate-400 dark:text-slate-500'">
            {{ basisText || rdEmptyLabel(t, 'basis') }}
          </dd>
        </div>
      </dl>

      <dl class="grid grid-cols-2 divide-x divide-y divide-slate-100 dark:divide-slate-800 sm:grid-cols-4">
        <div class="px-4 py-3">
          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.ops_lbl_proposed_date') }}</dt>
          <dd class="mt-0.5 text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ fmtDateOnly(formData.proposed_date) || rdEmptyLabel(t, 'date') }}</dd>
        </div>
        <div class="px-4 py-3">
          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.ops_lbl_date_needed') }}</dt>
          <dd class="mt-0.5 text-sm font-semibold tabular-nums" :class="formData.date_needed ? 'text-slate-900 dark:text-slate-100' : 'italic text-slate-400 dark:text-slate-500'">
            {{ fmtDateOnly(formData.date_needed) || rdEmptyLabel(t, 'date') }}
          </dd>
        </div>
        <div v-if="coordinatorName" class="px-4 py-3">
          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.ops_lbl_coordinator') }}</dt>
          <dd class="mt-0.5 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ coordinatorName }}</dd>
        </div>
        <div v-if="targets.length" class="px-4 py-3" :class="coordinatorName ? '' : 'col-span-2'">
          <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ t('request_detail.ops_lbl_target_audience') }}</dt>
          <dd class="mt-1 flex flex-wrap gap-1">
            <span
              v-for="(tg, i) in targets"
              :key="i"
              class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-xs font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
            >{{ tg }}</span>
          </dd>
        </div>
      </dl>

      <!-- Urgent reason banner (inline, no extra card) -->
      <div
        v-if="urgentReasonText"
        class="border-t border-rose-100 bg-rose-50/70 px-4 py-3 dark:border-rose-900/40 dark:bg-rose-950/20"
      >
        <p class="text-[10px] font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400">{{ t('request_detail.ops_lbl_urgent_reason') }}</p>
        <p class="mt-0.5 text-sm text-rose-900 dark:text-rose-200">{{ urgentReasonText }}</p>
      </div>
    </div>

    <!-- Assigned dept head (retained, important for workflow) -->
    <AssignedDeptHeadFormCard :req="req" variant="staff" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import PortalStatusTimeline from '../../portal/PortalStatusTimeline.vue'
import AssignedDeptHeadFormCard from '../AssignedDeptHeadFormCard.vue'
import { formatTripCode } from '../../../util/labels'
import { rdEmptyLabel } from '../../../util/requestDetailEmpty'
import {
  formatVndCurrency as formatVndMoney,
  parseMoneyVnd,
  VND_CURRENCY_SUFFIX,
} from '../../../util/money'

const props = defineProps({
  req: { type: Object, required: true },
  timelineSteps: { type: Array, default: () => [] },
  costEstimate: { type: Object, default: null },
})

const { t } = useI18n()

const linkedTripCode = computed(() => formatTripCode(props.req?.trip?.id))

const formData = computed(() => props.req?.wizard_snapshot?.form ?? {})

function nz(v) {
  return v == null ? '' : String(v).trim()
}
function fmtDateOnly(v) {
  if (!v) return ''
  const s = String(v).trim()
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
    const [y, m, d] = s.slice(0, 10).split('-')
    return `${d}/${m}/${y}`
  }
  const d = new Date(s)
  return Number.isNaN(d.getTime()) ? s : d.toLocaleDateString('vi-VN')
}

const urgentReasonText = computed(() => (formData.value.is_urgent ? nz(formData.value.urgent_reason) : ''))
const basisText = computed(() => {
  const r = nz(formData.value.basis_ref)
  if (r) return r
  const bf = nz(formData.value.basisFileName)
  return bf ? t('request_detail.ops_basis_file', { name: bf }) : ''
})
const targets = computed(() => (Array.isArray(formData.value.targets) ? formData.value.targets : []).map(nz).filter(Boolean))
const coordinatorName = computed(() => nz(formData.value.coordinator_name))

function formatVndSidebar(n) {
  const amount = parseMoneyVnd(n)
  if (!amount) return rdEmptyLabel(t, 'money_total')
  return formatVndMoney(amount, VND_CURRENCY_SUFFIX)
}
const declaredTotalDisplay = computed(() => {
  const total = props.costEstimate?.total
  if (total != null && total > 0) return formatVndSidebar(total)
  return rdEmptyLabel(t, 'money_total')
})
</script>
