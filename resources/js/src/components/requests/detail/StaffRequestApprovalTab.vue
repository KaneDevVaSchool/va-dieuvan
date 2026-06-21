<template>
  <div class="w-full min-w-0 space-y-5 py-1" data-testid="staff-request-approval-tab">
    <!-- Khối 1 — Tóm tắt duyệt -->
    <section
      class="rounded-lg border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900 sm:px-5"
      :aria-label="t('request_detail.approval_ws_summary_aria')"
    >
      <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 flex-1 space-y-1">
          <p class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
            <span class="h-2 w-2 shrink-0 rounded-full bg-va-600 dark:bg-va-400" aria-hidden="true" />
            <span class="min-w-0 truncate">{{ statusTitle }}</span>
          </p>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            <span class="tabular-nums">{{ t('request_detail.approval_ws_step_line', stepFraction) }}</span>
            <span aria-hidden="true"> · </span>
            <span>{{ t('request_detail.approval_ws_next_label') }}: {{ nextActorLine }}</span>
          </p>
        </div>
        <dl class="flex shrink-0 flex-wrap gap-x-4 gap-y-0.5 text-xs text-slate-600 dark:text-slate-300 lg:text-right">
          <div>
            <dt class="inline text-slate-400 dark:text-slate-500">{{ t('request_detail.approval_ws_handler_label') }}:</dt>
            <dd class="inline font-medium text-slate-800 dark:text-slate-200">{{ handlerName }}</dd>
          </div>
        </dl>
      </div>
      <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-800">
        <ApprovalMiniStepper
          :steps="miniSteps"
          :aria-label="t('request_detail.approval_ws_stepper_aria')"
        />
      </div>
    </section>

    <!-- Khối 2 — Thông tin cần xử lý -->
    <section :aria-label="t('request_detail.approval_ws_decision_aria')">
      <FillPricePanel
        v-if="showFillPriceSection"
        ref="fillPricePanelRef"
        workspace-mode
        :req="req"
        :acting="fillPriceActing"
        :allow-auto-approve="fillPriceAutoApproves"
        @save="$emit('save-fill-price', $event)"
        @summary-change="$emit('fill-price-summary-change', $event)"
        @workspace-metrics-change="fillPriceWorkspace = $event"
        @open-reference-pricing="$emit('open-reference-pricing')"
        @open-multi-row="multiRowModalOpen = true"
      />
      <div
        v-else
        class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-slate-200/80 bg-slate-200/80 dark:border-slate-800 dark:bg-slate-800 sm:grid-cols-4"
      >
        <div
          v-for="cell in decisionCells"
          :key="cell.key"
          class="bg-white px-3 py-3 dark:bg-slate-900 sm:px-4"
        >
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ cell.label }}
          </p>
          <p class="mt-1 text-base font-semibold tabular-nums text-slate-900 dark:text-slate-100">
            {{ cell.value }}
          </p>
        </div>
      </div>
    </section>

    <!-- Khối 3 — Hành động -->
    <section
      v-if="showActionCard"
      class="overflow-hidden rounded-lg border border-slate-300 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
      :aria-label="t('request_detail.approval_ws_actions_aria')"
    >
      <label class="block border-b border-slate-100 px-4 py-3 dark:border-slate-800 sm:px-5">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
          {{ t('request_detail.approval_ws_processing_note') }}
        </span>
        <textarea
          v-model="processingNote"
          rows="3"
          class="mt-2 w-full resize-y rounded-md border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm text-slate-900 outline-none focus:border-va-500 focus:ring-1 focus:ring-va-500/30 dark:border-slate-700 dark:bg-slate-950/50 dark:text-slate-100"
          :placeholder="t('request_detail.approval_ws_processing_note_ph')"
          :disabled="actionBusy"
          data-testid="approval-processing-note"
        />
      </label>

      <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 dark:border-slate-800 sm:px-5">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
          {{ t('request_detail.approval_ws_action_total') }}
        </span>
        <span class="text-lg font-bold tabular-nums text-slate-900 dark:text-slate-100">{{ actionTotalDisplay }}</span>
      </div>

      <p v-if="actionMessage" class="px-4 pt-3 text-sm text-rose-600 dark:text-rose-400 sm:px-5">{{ actionMessage }}</p>
      <p v-else-if="fillPriceMsg && showFillPriceSection" class="px-4 pt-3 text-sm text-slate-500 dark:text-slate-400 sm:px-5">{{ fillPriceMsg }}</p>

      <div class="flex flex-col gap-2 px-4 py-4 sm:flex-row sm:flex-wrap sm:items-center sm:px-5">
        <Button
          v-if="showReject"
          type="button"
          variant="danger"
          class="w-full sm:w-auto"
          :loading="d2dActing"
          data-testid="approval-action-reject"
          @click="$emit('reject')"
        >
          {{ t('request_detail.approval_ws_btn_reject') }}
        </Button>
        <Button
          v-if="showSupplement"
          type="button"
          variant="secondary"
          class="w-full sm:w-auto"
          :disabled="actionBusy"
          data-testid="approval-action-supplement"
          @click="onSupplement"
        >
          {{ t('request_detail.approval_ws_btn_supplement') }}
        </Button>
        <Button
          v-if="showComplete"
          type="button"
          class="w-full sm:ml-auto sm:w-auto"
          :loading="completeLoading"
          :disabled="completeDisabled"
          data-testid="approval-action-complete"
          @click="onComplete"
        >
          {{ completeLabel }}
        </Button>
      </div>

      <div
        v-if="showFillPriceSection && !fillPriceSummary.deptHeadPresetLocked && !fillPriceAutoApproves"
        class="border-t border-amber-200/80 bg-amber-50/60 px-4 py-2.5 text-xs text-amber-900 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-100 sm:px-5"
        role="alert"
      >
        {{ t('request_detail.assign_dept_head_missing_staff_title') }}
      </div>
    </section>

    <div v-else-if="showResetClone" class="flex justify-center">
      <button
        type="button"
        class="text-sm font-semibold text-slate-600 underline-offset-2 hover:underline dark:text-slate-300"
        :disabled="resetCloneBusy"
        data-testid="approval-reset-clone"
        @click="$emit('reset-clone')"
      >
        {{ resetCloneBusy ? t('request_detail.reset_clone_busy') : t('request_detail.reset_clone') }}
      </button>
    </div>

    <p
      v-else-if="!showFillPriceSection"
      class="py-6 text-center text-sm text-slate-500 dark:text-slate-400"
    >
      {{ t('request_detail.ops_no_actions') }}
    </p>

    <!-- Lịch sử — modal -->
    <div class="flex justify-center pt-1">
      <button
        type="button"
        class="text-sm font-medium text-slate-500 transition hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200"
        data-testid="approval-view-history"
        @click="historyOpen = true"
      >
        {{ t('request_detail.approval_ws_view_history') }}
      </button>
    </div>

    <Modal
      :open="historyOpen"
      wide
      :title="t('request_detail.approval_ws_history_title')"
      :description="t('request_detail.approval_ws_history_lead')"
      @close="historyOpen = false"
    >
      <p v-if="auditLoading" class="py-8 text-center text-sm text-slate-400">{{ t('request_detail.audit_timeline_loading') }}</p>
      <p v-else-if="auditError" class="text-sm text-rose-600">{{ auditError }}</p>
      <p v-else-if="!auditItems.length" class="py-8 text-center text-sm text-slate-400">{{ t('request_detail.audit_timeline_empty') }}</p>
      <ol v-else class="space-y-2">
        <li
          v-for="row in auditItems"
          :key="row.id"
          class="flex gap-3 border-b border-slate-100 py-2 last:border-0 dark:border-slate-800"
        >
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ auditEventLabel(row.event) }}</p>
            <p v-if="row.user?.name" class="text-xs text-slate-500 dark:text-slate-400">{{ row.user.name }}</p>
          </div>
          <time class="shrink-0 text-xs tabular-nums text-slate-400">{{ fmtAudit(row.created_at) }}</time>
        </li>
      </ol>
    </Modal>

    <Modal
      v-if="showFillPriceSection && multiRowModalOpen"
      :open="multiRowModalOpen"
      wide
      :title="t('request_detail.approval_ws_multi_row_title')"
      @close="multiRowModalOpen = false"
    >
      <FillPricePanel
        :req="req"
        :acting="fillPriceActing"
        :message="fillPriceMsg"
        @save="onMultiRowSave"
        @open-reference-pricing="$emit('open-reference-pricing')"
      />
    </Modal>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Button from '../../ui/Button.vue'
import Modal from '../../ui/Modal.vue'
import FillPricePanel from '../workspace/FillPricePanel.vue'
import ApprovalMiniStepper from './ApprovalMiniStepper.vue'
import { useStaffRequestApprovalWorkspace } from '../../../composables/useStaffRequestApprovalWorkspace'

const props = defineProps({
  req: { type: Object, default: null },
  showFillPriceSection: { type: Boolean, default: false },
  fillPriceAutoApproves: { type: Boolean, default: false },
  fillPriceActing: { type: Boolean, default: false },
  fillPriceMsg: { type: String, default: '' },
  fillPriceSummary: { type: Object, default: () => ({}) },
  showD2dDecisionSection: { type: Boolean, default: false },
  d2dActing: { type: Boolean, default: false },
  d2dMsg: { type: String, default: '' },
  showResetClone: { type: Boolean, default: false },
  resetCloneBusy: { type: Boolean, default: false },
  declaredTotalDisplay: { type: String, default: '' },
  auditItems: { type: Array, default: () => [] },
  auditLoading: { type: Boolean, default: false },
  auditError: { type: String, default: '' },
  auditEventLabel: { type: Function, required: true },
  fmtAudit: { type: Function, required: true },
})

const emit = defineEmits([
  'save-fill-price',
  'fill-price-summary-change',
  'open-reference-pricing',
  'complete-fill-price',
  'approve',
  'reject',
  'supplement',
  'reset-clone',
])

const { t } = useI18n()

const fillPricePanelRef = ref(null)
const fillPriceWorkspace = ref({ unitSumFmt: '', extraSumFmt: '', rowCount: 0 })
const processingNote = ref('')
const historyOpen = ref(false)
const multiRowModalOpen = ref(false)

const workspaceCtx = computed(() => ({
  showFillPriceSection: props.showFillPriceSection,
  showD2dDecisionSection: props.showD2dDecisionSection,
  fillPriceSummary: props.fillPriceSummary,
  fillPriceWorkspace: fillPriceWorkspace.value,
  declaredTotalDisplay: props.declaredTotalDisplay,
}))

const reqRef = computed(() => props.req)
const {
  handlerName,
  statusTitle,
  nextActorLine,
  miniSteps,
  stepFraction,
  readOnlyMetrics,
} = useStaffRequestApprovalWorkspace(reqRef, workspaceCtx)

const decisionCells = computed(() => [
  { key: 'unit', label: t('request_detail.ops_lbl_unit_price'), value: readOnlyMetrics.value.unitPrice },
  { key: 'extra', label: t('request_detail.ops_lbl_extra_fee'), value: readOnlyMetrics.value.extraFee },
  { key: 'pax', label: t('request_detail.hero_lbl_passengers'), value: readOnlyMetrics.value.passengers },
  { key: 'total', label: t('request_detail.approval_ws_metric_total'), value: readOnlyMetrics.value.total },
])

const showActionCard = computed(
  () => props.showFillPriceSection || props.showD2dDecisionSection,
)

const showReject = computed(() => props.showD2dDecisionSection)
const showSupplement = computed(() => props.showFillPriceSection || props.showD2dDecisionSection)
const showComplete = computed(() => props.showFillPriceSection || props.showD2dDecisionSection)

const actionBusy = computed(() => props.fillPriceActing || props.d2dActing)
const actionMessage = computed(() => (props.showD2dDecisionSection ? props.d2dMsg : ''))

const actionTotalDisplay = computed(() => {
  if (props.showFillPriceSection && props.fillPriceSummary?.totalFmt) {
    return props.fillPriceSummary.totalFmt
  }
  return props.declaredTotalDisplay
})

const completeLabel = computed(() => {
  if (props.showD2dDecisionSection) return t('request_detail.approval_ws_btn_complete')
  if (props.fillPriceAutoApproves) return t('request_detail.fill_price_submit_approve')
  return t('request_detail.approval_ws_btn_complete')
})

const completeLoading = computed(() =>
  props.showD2dDecisionSection ? props.d2dActing : props.fillPriceActing,
)

const completeDisabled = computed(() => {
  if (props.showD2dDecisionSection) return props.d2dActing
  const s = props.fillPriceSummary
  if (props.fillPriceAutoApproves) {
    return !s?.total || props.fillPriceActing
  }
  return !s?.canSubmitFillPrice
})

watch(processingNote, (note) => {
  fillPricePanelRef.value?.setProcessingNote?.(note)
})

function onComplete() {
  if (props.showD2dDecisionSection) {
    emit('approve')
    return
  }
  fillPricePanelRef.value?.setProcessingNote?.(processingNote.value)
  fillPricePanelRef.value?.submit?.()
  emit('complete-fill-price')
}

function onSupplement() {
  if (props.showFillPriceSection) {
    emit('open-reference-pricing')
    return
  }
  emit('supplement')
}

function onMultiRowSave(payload) {
  emit('save-fill-price', payload)
  multiRowModalOpen.value = false
}

defineExpose({
  openMultiRowEditor: () => {
    multiRowModalOpen.value = true
  },
})
</script>
