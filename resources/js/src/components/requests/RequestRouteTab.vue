<template>
  <div class="space-y-4">
    <PortalStatusTimeline
      :title="t('request_detail.section_progress')"
      :steps="timelineSteps"
    />

    <section class="border border-slate-200 bg-white p-4">
      <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="text-sm font-semibold text-slate-900">{{ t('request_detail.route_map_heading') }}</h2>
        <p class="text-xs text-slate-600">
          {{
            costEstimate?.distanceLabel != null
              ? t('request_detail.distance_badge_approx', { label: costEstimate.distanceLabel })
              : t('request_detail.distance_badge_empty')
          }}
        </p>
      </div>

      <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
        <div>
          <dt class="text-xs font-medium text-slate-500">{{ t('request_detail.lbl_usage_window') }}</dt>
          <dd class="mt-0.5 text-slate-900">
            {{ departDateLabel }} · {{ timeWindowLabel }}
          </dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-slate-500">{{ t('request_detail.lbl_origin') }}</dt>
          <dd class="mt-0.5 font-medium text-slate-900">{{ req.origin || '—' }}</dd>
          <p v-if="routeSubFrom" class="mt-0.5 text-xs text-slate-500">{{ routeSubFrom }}</p>
        </div>
        <div class="sm:col-span-2">
          <dt class="text-xs font-medium text-slate-500">{{ t('request_detail.lbl_destination') }}</dt>
          <dd class="mt-0.5 font-medium text-slate-900">{{ req.destination || '—' }}</dd>
          <p v-if="routeSubTo" class="mt-0.5 text-xs text-slate-500">{{ routeSubTo }}</p>
        </div>
      </dl>
    </section>

    <section v-if="showApprovalPanel" class="border border-slate-200 bg-white p-4">
      <h2 class="text-sm font-semibold text-slate-900">{{ t('request_detail.approval_basis_heading') }}</h2>
      <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
        <div>
          <dt class="text-xs font-medium text-slate-500">{{ t('request_detail.lbl_status_short') }}</dt>
          <dd class="mt-1">
            <StatusBadge :status="req.status" />
          </dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-slate-500">{{ t('request_detail.lbl_trip_type_short') }}</dt>
          <dd class="mt-1 font-medium text-slate-900">{{ tripTypeLabel }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-slate-500">{{ t('request_detail.lbl_requester_name') }}</dt>
          <dd class="mt-1 font-medium text-slate-900">{{ req.requester?.name ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-slate-500">{{ t('request_detail.lbl_requester_unit') }}</dt>
          <dd class="mt-1 text-slate-900">{{ requesterUnit }}</dd>
        </div>
        <div class="sm:col-span-2">
          <dt class="text-xs font-medium text-slate-500">{{ t('request_detail.lbl_usage_window') }}</dt>
          <dd class="mt-1 text-slate-900">{{ departDateLabel }} · {{ timeWindowLabel }}</dd>
        </div>
        <div class="lg:col-span-3">
          <dt class="text-xs font-medium text-slate-500">{{ t('request_detail.lbl_route_line') }}</dt>
          <dd class="mt-1 text-slate-900">
            {{ req.origin || '—' }}
            <span class="text-slate-400"> → </span>
            {{ req.destination || '—' }}
          </dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-slate-500">{{ t('request_detail.lbl_people_load_compact') }}</dt>
          <dd class="mt-1 text-slate-900">{{ passengerOrCargoLine }}</dd>
        </div>
        <div v-if="purpose" class="sm:col-span-2">
          <dt class="text-xs font-medium text-slate-500">{{ t('request_detail.lbl_purpose') }}</dt>
          <dd class="mt-1 text-slate-900">{{ purpose }}</dd>
        </div>
        <div v-if="costEstimate">
          <dt class="text-xs font-medium text-slate-500">{{ t('request_detail.lbl_total_cost_declared') }}</dt>
          <dd class="mt-1 font-semibold tabular-nums text-slate-900">{{ declaredTotalLabel }}</dd>
        </div>
        <div v-if="showDispatcherPrice && req.service_price != null">
          <dt class="text-xs font-medium text-slate-500">{{ t('request_detail.lbl_dispatcher_unit_price') }}</dt>
          <dd class="mt-1 font-semibold tabular-nums text-slate-900">{{ dispatcherPriceLabel }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-slate-500">{{ t('request_detail.lbl_attachment_count') }}</dt>
          <dd class="mt-1 font-medium text-slate-900">{{ attachmentCount }}</dd>
        </div>
      </dl>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import PortalStatusTimeline from '../portal/PortalStatusTimeline.vue'
import StatusBadge from '../ui/StatusBadge.vue'

const props = defineProps({
  req: { type: Object, required: true },
  timelineSteps: { type: Array, required: true },
  costEstimate: { type: Object, default: null },
  routeSubFrom: { type: String, default: '' },
  routeSubTo: { type: String, default: '' },
  passengerOrCargoLine: { type: String, default: '—' },
  purpose: { type: String, default: '' },
  showApprovalPanel: { type: Boolean, default: false },
  showDispatcherPrice: { type: Boolean, default: false },
  attachmentCount: { type: Number, default: 0 },
  requesterUnitFallback: { type: String, default: '' },
  departDateLabel: { type: String, required: true },
  timeWindowLabel: { type: String, required: true },
  tripTypeLabel: { type: String, required: true },
  declaredTotalLabel: { type: String, default: '—' },
  dispatcherPriceLabel: { type: String, default: '—' },
})

const { t } = useI18n()

const requesterUnit = computed(
  () => props.req.wizard_snapshot?.form?.requester_unit || props.requesterUnitFallback || '—',
)
</script>
