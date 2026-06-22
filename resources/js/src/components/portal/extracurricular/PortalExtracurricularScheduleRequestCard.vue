<template>
  <article
    class="xc-portal-mobile-row flex flex-col overflow-hidden"
    :data-testid="`portal-ec-schedule-card-${req.id}`"
  >
    <div class="border-b border-violet-100/90 bg-gradient-to-r from-violet-50/80 to-white px-4 py-3">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="min-w-0 flex-1">
          <button
            type="button"
            class="font-mono text-sm font-bold text-slate-900 underline-offset-2 hover:text-va-800 hover:underline focus:outline-none focus:ring-2 focus:ring-va-500/30 rounded"
            :data-testid="`portal-ec-card-ref-${req.id}`"
            @click="$emit('open-detail', req)"
          >
            {{ refCode }}
          </button>
          <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
            <StatusBadge :status="req.status" size="sm" />
            <StudentCountTrackingBadge
              :tracking-key="trackingKey"
              i18n-prefix="portal.extracurricular_table"
            />
          </div>
        </div>
        <ExtracurricularRowActions
          :req="req"
          variant="portal"
          :detail-route-name="detailRouteName"
          :show-complete-bm03="showCompleteBm03"
          :can-complete-bm03="canCompleteBm03"
          :complete-disabled-hint="completeDisabledHint"
          :show-clone="false"
          @open-detail="$emit('open-detail', req)"
        />
      </div>
      <p class="mt-2 text-sm font-medium leading-snug text-slate-800">{{ routeLine }}</p>
      <p class="mt-1 text-xs text-slate-500">{{ departLabel }}</p>
    </div>

    <div class="flex flex-1 flex-col gap-3 px-4 py-3">
      <dl class="grid grid-cols-2 gap-2 text-xs">
        <div class="rounded-lg bg-slate-50/90 px-2.5 py-2 ring-1 ring-slate-100">
          <dt class="text-[10px] font-semibold uppercase tracking-wide text-violet-700">
            {{ t('portal.extracurricular_table.col_plan') }}
          </dt>
          <dd class="mt-0.5 tabular-nums text-sm font-semibold text-slate-900">
            {{ planCount ?? '—' }}
          </dd>
        </div>
        <div
          v-if="tripCostDisplay"
          class="rounded-lg bg-teal-50/80 px-2.5 py-2 ring-1 ring-teal-100/80"
        >
          <dt class="text-[10px] font-semibold uppercase tracking-wide text-teal-800">
            {{ t('portal.extracurricular_table.col_trip_cost_filled') }}
          </dt>
          <dd class="mt-0.5 tabular-nums text-sm font-semibold text-slate-900">
            {{ tripCostDisplay }}
          </dd>
        </div>
      </dl>

      <StudentCountCell
        :req="req"
        :draft="draft"
        :saving="saving"
        :error="error"
        :can-edit="canEdit"
        :lock-hint="lockHint"
        :show-submit="false"
        mobile
        :save-label-key="'portal.extracurricular_table.update_count'"
        :save-busy-label-key="'portal.extracurricular_table.update_count_busy'"
        @update:draft="$emit('update:draft', $event)"
        @save="$emit('save')"
      />
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import StatusBadge from '../../ui/StatusBadge.vue'
import StudentCountCell from '../../requests/extracurricular/StudentCountCell.vue'
import StudentCountTrackingBadge from '../../requests/extracurricular/StudentCountTrackingBadge.vue'
import ExtracurricularRowActions from '../../requests/extracurricular/ExtracurricularRowActions.vue'
import { formatDispatchRequestRefCode } from '../../../util/portalRequestFormat.js'

const props = defineProps({
  req: { type: Object, required: true },
  detailRouteName: { type: String, required: true },
  routeLine: { type: String, default: '—' },
  departLabel: { type: String, default: '—' },
  planCount: { type: [Number, String], default: null },
  tripCostDisplay: { type: String, default: '' },
  trackingKey: { type: String, default: '' },
  draft: { type: [Number, String], default: null },
  saving: { type: Boolean, default: false },
  error: { type: String, default: '' },
  canEdit: { type: Boolean, default: false },
  lockHint: { type: String, default: '' },
  showCompleteBm03: { type: Boolean, default: false },
  canCompleteBm03: { type: Boolean, default: false },
  completeDisabledHint: { type: String, default: '' },
})

defineEmits(['open-detail', 'update:draft', 'save'])

const { t } = useI18n()

const refCode = computed(() => formatDispatchRequestRefCode(props.req) || `#${props.req.id}`)
</script>
