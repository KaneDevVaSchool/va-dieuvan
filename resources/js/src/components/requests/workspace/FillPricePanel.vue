<template>
  <section
    id="request-focus-fill-price"
    class="scroll-mt-24"
    :class="workspaceMode ? '' : 'space-y-3'"
  >
    <!-- Workspace: grid 4 cột — không lặp hành trình -->
    <div v-if="workspaceMode" class="space-y-2">
      <div
        class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-slate-200/80 bg-slate-200/80 dark:border-slate-800 dark:bg-slate-800 sm:grid-cols-4"
      >
        <div
          v-for="cell in workspaceCells"
          :key="cell.key"
          class="bg-white px-3 py-3 dark:bg-slate-900 sm:px-4"
        >
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ cell.label }}
          </p>
          <div v-if="cell.editable" class="relative mt-1">
            <input
              :value="cell.value"
              type="text"
              inputmode="numeric"
              class="w-full rounded border border-slate-200 bg-slate-50/80 py-1.5 pl-2 pr-10 text-right text-sm font-semibold tabular-nums text-slate-900 outline-none focus:border-va-500 focus:ring-1 focus:ring-va-500/30 dark:border-slate-700 dark:bg-slate-950/50 dark:text-slate-100"
              :data-testid="cell.testId"
              :disabled="acting"
              @input="cell.onInput($event)"
              @blur="cell.onBlur?.()"
            />
            <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-[10px] text-slate-400">{{ vndSuffix }}</span>
          </div>
          <p v-else class="mt-1 text-base font-semibold tabular-nums text-slate-900 dark:text-slate-100">
            {{ cell.display }}
          </p>
        </div>
      </div>
      <button
        v-if="rows.length > 1"
        type="button"
        class="text-xs font-semibold text-va-700 underline-offset-2 hover:underline dark:text-va-400"
        data-testid="fill-price-workspace-multi-row"
        @click="emit('open-multi-row')"
      >
        {{ t('request_detail.approval_ws_edit_multi_row', { n: rows.length }) }}
      </button>
    </div>

    <template v-if="!workspaceMode">
    <!-- Intro banner -->
    <div class="flex flex-wrap items-start justify-between gap-3 rounded-2xl border border-sky-200/80 bg-gradient-to-r from-sky-50 to-slate-50 px-5 py-4 dark:border-sky-900/40 dark:from-sky-950/30 dark:to-slate-900">
      <div class="flex min-w-0 items-center gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-white shadow-sm">
          <CurrencyDollarIcon class="h-5 w-5" aria-hidden="true" />
        </span>
        <div class="min-w-0">
          <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ t('request_detail.fill_price_title') }}</h2>
        </div>
      </div>
      <Button
        type="button"
        variant="secondary"
        class="shrink-0 !border-teal-200 !text-teal-900 hover:!bg-teal-50 dark:!border-teal-900/50 dark:!text-teal-200 dark:hover:!bg-teal-950/40"
        data-testid="fill-price-open-reference-pricing"
        :disabled="acting"
        @click="emit('open-reference-pricing')"
      >
        {{ t('request_detail.reference_pricing_link') }}
      </Button>
    </div>

    <!-- Row cards -->
    <div
      v-for="(row, idx) in rows"
      :key="idx"
      class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
    >
      <!-- Card header: route badge + heading -->
      <div class="flex items-center gap-3 border-b border-slate-100 bg-slate-50/80 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/50">
        <span
          class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sm font-bold text-sky-700 dark:bg-sky-950/50 dark:text-sky-300"
          :aria-label="t('request_detail.ops_row_badge_aria', { n: idx + 1 })"
        >{{ idx + 1 }}</span>
        <div class="min-w-0 flex-1">
          <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
            {{ t('request_detail.ops_itinerary_row_label') }}
          </p>
          <p class="mt-0.5 truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ rowHeading(row, idx) }}</p>
        </div>
      </div>

      <!-- Route mini-map -->
      <div
        v-if="rowFrom(row) || rowTo(row)"
        class="grid grid-cols-[1fr_auto_1fr] border-b border-slate-100 dark:border-slate-800"
      >
        <div class="bg-emerald-50/50 px-4 py-3 dark:bg-emerald-950/15">
          <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600/80 dark:text-emerald-400/80">{{ t('request_detail.lbl_origin') }}</p>
          <p class="mt-0.5 text-sm font-semibold leading-snug text-slate-800 dark:text-slate-100">{{ rowFrom(row) || '—' }}</p>
        </div>
        <div class="flex items-center justify-center bg-slate-50/50 px-2 dark:bg-slate-800/30">
          <svg class="h-4 w-4 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
        </div>
        <div class="bg-rose-50/50 px-4 py-3 dark:bg-rose-950/15">
          <p class="text-[10px] font-bold uppercase tracking-wider text-rose-600/80 dark:text-rose-400/80">{{ t('request_detail.lbl_destination') }}</p>
          <p class="mt-0.5 text-sm font-semibold leading-snug text-slate-800 dark:text-slate-100">{{ rowTo(row) || '—' }}</p>
        </div>
      </div>

      <ItineraryRowInfoGrid :row="row" :trip-type="itineraryTripType" />

      <!-- Cargo inputs -->
      <div v-if="isCargo" class="grid gap-4 px-4 py-4 sm:grid-cols-2">
        <label class="block">
          <FillPriceFieldLabel
            :label="t('request_detail.ops_lbl_transport_type')"
            :tooltip="t('request_detail.ops_transport_type_tooltip')"
          />
          <input
            :value="cargoTransport[idx]"
            type="text"
            maxlength="500"
            class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            :placeholder="t('request_detail.ops_transport_type_ph')"
            :title="t('request_detail.ops_transport_type_tooltip')"
            @input="cargoTransport[idx] = String($event.target.value).slice(0, 500)"
          />
        </label>
        <label class="block">
          <FillPriceFieldLabel
            :label="t('request_detail.ops_lbl_cost')"
            :tooltip="t('request_detail.ops_cost_tooltip')"
          />
          <div class="relative mt-1.5">
            <input
              :value="cargoCost[idx]"
              type="text"
              inputmode="numeric"
              class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-3 pr-14 text-right text-sm tabular-nums text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              :placeholder="t('request_detail.ops_money_ph')"
              :title="t('request_detail.ops_cost_tooltip')"
              @input="cargoCost[idx] = fmtTyping($event.target.value)"
              @blur="cargoCost[idx] = fmtBlur(cargoCost[idx])"
            />
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-medium text-slate-400 dark:text-slate-500">{{ vndSuffix }}</span>
          </div>
        </label>
      </div>

      <!-- Passenger / business pricing inputs -->
      <div v-else class="space-y-3 px-4 py-4">
        <div class="grid gap-3 sm:grid-cols-2">
          <label class="block">
            <FillPriceFieldLabel
              :label="t('request_detail.ops_lbl_unit_price')"
              :tooltip="t('request_detail.ops_unit_price_tooltip')"
            />
            <div class="relative mt-1.5">
              <input
                :value="unitDraft[idx]"
                type="text"
                inputmode="numeric"
                class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-3 pr-14 text-right text-sm tabular-nums text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('request_detail.ops_money_ph')"
                :title="t('request_detail.ops_unit_price_tooltip')"
                @input="unitDraft[idx] = fmtTyping($event.target.value)"
                @blur="unitDraft[idx] = fmtBlur(unitDraft[idx])"
              />
              <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-medium text-slate-400 dark:text-slate-500">{{ vndSuffix }}</span>
            </div>
          </label>
          <label class="block">
            <FillPriceFieldLabel
              :label="t('request_detail.ops_lbl_extra_fee')"
              :tooltip="t('request_detail.ops_extra_fee_tooltip')"
            />
            <div class="relative mt-1.5">
              <input
                :value="extraDraft[idx]"
                type="text"
                inputmode="numeric"
                class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-3 pr-14 text-right text-sm tabular-nums text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('request_detail.ops_money_zero_ph')"
                :title="t('request_detail.ops_extra_fee_tooltip')"
                @input="extraDraft[idx] = fmtTyping($event.target.value)"
                @blur="extraDraft[idx] = fmtBlur(extraDraft[idx])"
              />
              <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs font-medium text-slate-400 dark:text-slate-500">{{ vndSuffix }}</span>
            </div>
          </label>
        </div>
        <label class="block">
          <FillPriceFieldLabel
            :label="t('request_detail.ops_lbl_notes')"
            :tooltip="t('request_detail.ops_notes_tooltip')"
          />
          <input
            :value="notesDraft[idx]"
            type="text"
            maxlength="2000"
            class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            :placeholder="t('request_detail.ops_notes_ph')"
            :title="t('request_detail.ops_notes_tooltip')"
            @input="notesDraft[idx] = String($event.target.value).slice(0, 2000)"
          />
        </label>
      </div>
    </div>

    <!-- Summary: total + dept head + save (inline unless actions live in sidebar) -->
    <div
      v-if="!actionsInSidebar"
      class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
    >
      <!-- Total row -->
      <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
        <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">
          {{ t('request_detail.fill_price_total') }}
        </span>
        <span
          class="text-2xl font-bold tabular-nums"
          :class="total > 0 ? 'text-teal-600 dark:text-teal-400' : 'text-slate-400 dark:text-slate-500'"
        >{{ totalFmt }}</span>
      </div>

      <!-- Dept head -->
      <div class="px-5 py-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
          {{ t('request_detail.assign_dept_head_preset_label') }}
        </p>
        <div
          v-if="deptHeadPresetLocked"
          class="mt-2 rounded-xl border border-sky-200/80 bg-sky-50/60 px-3.5 py-3 dark:border-sky-900/40 dark:bg-sky-950/25"
        >
          <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ deptHeadDisplayLine }}</p>
          <p v-if="deptHeadLoadErr" class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ deptHeadLoadErr }}</p>
        </div>
        <div
          v-else
          class="mt-2 rounded-xl border border-amber-200/80 bg-amber-50/60 px-3.5 py-3 dark:border-amber-900/40 dark:bg-amber-950/20"
          role="alert"
        >
          <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">{{ t('request_detail.assign_dept_head_missing_staff_title') }}</p>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
          <Button
            class="!bg-sky-600 hover:!bg-sky-700"
            :loading="acting"
            :disabled="!canSubmitFillPrice"
            @click="onSave"
          >
            {{ t('request_detail.fill_price_submit_preset_dept') }}
          </Button>
          <Button
            type="button"
            variant="secondary"
            :disabled="acting"
            data-testid="fill-price-open-reference-pricing-footer"
            @click="emit('open-reference-pricing')"
          >
            {{ t('request_detail.reference_pricing_link') }}
          </Button>
          <span v-if="message" class="text-sm text-slate-500 dark:text-slate-400">{{ message }}</span>
        </div>
      </div>
    </div>
    </template>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { dispatchRequestDisplayPassengerCount } from '../../../util/dispatchRequestPassengers'
import { CurrencyDollarIcon } from '@heroicons/vue/24/outline'
import Button from '../../ui/Button.vue'
import FillPriceFieldLabel from './FillPriceFieldLabel.vue'
import ItineraryRowInfoGrid from './ItineraryRowInfoGrid.vue'
import { getAvailableDeptHeads } from '../../../api/requests'
import {
  parseMoneyVnd,
  formatVndCurrency,
  formatVndWhileTyping,
  formatMoneyDraftDisplay,
  VND_CURRENCY_SUFFIX,
} from '../../../util/money'
import {
  itineraryRowHeading,
  itineraryRowEndpoints,
  nz,
  resolveItineraryTripType,
} from '../../../util/requestItineraryRowDisplay'

const { t } = useI18n()

const props = defineProps({
  req: { type: Object, default: null },
  acting: { type: Boolean, default: false },
  message: { type: String, default: '' },
  /** Khi true: tổng / trưởng BP / nút Lưu do sidebar «Trung tâm xử lý» đảm nhiệm */
  actionsInSidebar: { type: Boolean, default: false },
  /** Tab Phê duyệt — chỉ lưới giá, không card hành trình */
  workspaceMode: { type: Boolean, default: false },
  /** Điền giá xong duyệt luôn (không bắt buộc Trưởng BP trên phiếu) */
  allowAutoApprove: { type: Boolean, default: false },
})

const emit = defineEmits([
  'save',
  'summary-change',
  'open-reference-pricing',
  'workspace-metrics-change',
  'open-multi-row',
])

const snap = computed(() => props.req?.wizard_snapshot ?? {})
const isCargo = computed(() => props.req?.trip_type === 'cargo')
const isBusiness = computed(() => props.req?.trip_type === 'business')

const rows = computed(() => {
  if (isCargo.value) return Array.isArray(snap.value.cargoRows) ? snap.value.cargoRows : []
  if (isBusiness.value) return Array.isArray(snap.value.businessRows) ? snap.value.businessRows : []
  return Array.isArray(snap.value.passengerRows) ? snap.value.passengerRows : []
})

const itineraryTripType = computed(() => resolveItineraryTripType(isCargo.value, isBusiness.value))

const vndSuffix = VND_CURRENCY_SUFFIX

function fmtTyping(v) {
  return formatVndWhileTyping(v)
}

function fmtBlur(v) {
  const n = parseMoneyVnd(v)
  return n > 0 ? formatVndWhileTyping(String(n)) : ''
}

function rowHeading(row, idx) {
  return itineraryRowHeading(row, idx, { tripType: itineraryTripType.value, t })
}

function rowFrom(row) {
  return itineraryRowEndpoints(row).from
}

function rowTo(row) {
  return itineraryRowEndpoints(row).to
}

// ── Drafts ──
const unitDraft = ref([])
const extraDraft = ref([])
const notesDraft = ref([])
const cargoTransport = ref([])
const cargoCost = ref([])

function syncDrafts() {
  const r = rows.value
  if (isCargo.value) {
    cargoTransport.value = r.map((x) => nz(x?.transport_note))
    cargoCost.value = r.map((x) => formatMoneyDraftDisplay(x?.cost))
  } else {
    unitDraft.value = r.map((x) => formatMoneyDraftDisplay(x?.unit_price))
    extraDraft.value = r.map((x) => formatMoneyDraftDisplay(x?.extra_fee))
    notesDraft.value = r.map((x) => nz(x?.notes))
  }
}
watch(() => [props.req?.id, rows.value], syncDrafts, { immediate: true, deep: true })

const total = computed(() => {
  let s = 0
  if (isCargo.value) {
    cargoCost.value.forEach((c) => (s += parseMoneyVnd(c)))
  } else {
    rows.value.forEach((_, i) => {
      s += parseMoneyVnd(unitDraft.value[i] ?? '')
      s += parseMoneyVnd(extraDraft.value[i] ?? '')
    })
  }
  return s
})
const totalFmt = computed(() => formatVndCurrency(total.value, VND_CURRENCY_SUFFIX))

const unitSum = computed(() => {
  if (isCargo.value) return total.value
  let s = 0
  rows.value.forEach((_, i) => {
    s += parseMoneyVnd(unitDraft.value[i] ?? '')
  })
  return s
})

const extraSum = computed(() => {
  if (isCargo.value) return 0
  let s = 0
  rows.value.forEach((_, i) => {
    s += parseMoneyVnd(extraDraft.value[i] ?? '')
  })
  return s
})

const unitSumFmt = computed(() => formatVndCurrency(unitSum.value, VND_CURRENCY_SUFFIX))
const extraSumFmt = computed(() => formatVndCurrency(extraSum.value, VND_CURRENCY_SUFFIX))

const passengerDisplay = computed(() => {
  const pax = dispatchRequestDisplayPassengerCount(props.req)
  if (pax == null || pax === '') return '—'
  return t('request_detail.approval_ws_passengers_n', { n: pax })
})

function setDraftOnRow(field, idx, raw) {
  if (field === 'unit') unitDraft.value[idx] = fmtTyping(raw)
  else if (field === 'extra') extraDraft.value[idx] = fmtTyping(raw)
  else if (field === 'cargo') cargoCost.value[idx] = fmtTyping(raw)
}

function blurDraftOnRow(field, idx) {
  if (field === 'unit') unitDraft.value[idx] = fmtBlur(unitDraft.value[idx])
  else if (field === 'extra') extraDraft.value[idx] = fmtBlur(extraDraft.value[idx])
  else if (field === 'cargo') cargoCost.value[idx] = fmtBlur(cargoCost.value[idx])
}

function workspaceInputHandlers(field) {
  const multi = rows.value.length > 1
  return {
    onInput: (e) => {
      const v = String(e.target?.value ?? '')
      if (multi) {
        rows.value.forEach((_, i) => setDraftOnRow(field, i, i === 0 ? v : '0'))
      } else {
        setDraftOnRow(field, 0, v)
      }
    },
    onBlur: () => {
      if (multi) {
        rows.value.forEach((_, i) => blurDraftOnRow(field, i))
      } else {
        blurDraftOnRow(field, 0)
      }
    },
  }
}

const workspaceCells = computed(() => {
  if (isCargo.value) {
    const h = workspaceInputHandlers('cargo')
    return [
      {
        key: 'unit',
        label: t('request_detail.ops_lbl_cost'),
        editable: rows.value.length <= 1,
        value: rows.value.length <= 1 ? cargoCost.value[0] ?? '' : '',
        display: unitSumFmt.value,
        testId: 'approval-ws-unit-price',
        ...h,
      },
      {
        key: 'extra',
        label: t('request_detail.ops_lbl_extra_fee'),
        editable: false,
        display: '—',
      },
      {
        key: 'pax',
        label: t('request_detail.hero_lbl_passengers'),
        editable: false,
        display: passengerDisplay.value,
      },
      {
        key: 'total',
        label: t('request_detail.approval_ws_metric_total'),
        editable: false,
        display: totalFmt.value,
      },
    ]
  }
  const unitH = workspaceInputHandlers('unit')
  const extraH = workspaceInputHandlers('extra')
  const multi = rows.value.length > 1
  return [
    {
      key: 'unit',
      label: t('request_detail.ops_lbl_unit_price'),
      editable: !multi,
      value: multi ? '' : unitDraft.value[0] ?? '',
      display: unitSumFmt.value,
      testId: 'approval-ws-unit-price',
      ...unitH,
    },
    {
      key: 'extra',
      label: t('request_detail.ops_lbl_extra_fee'),
      editable: !multi,
      value: multi ? '' : extraDraft.value[0] ?? '',
      display: extraSumFmt.value,
      testId: 'approval-ws-extra-fee',
      ...extraH,
    },
    {
      key: 'pax',
      label: t('request_detail.hero_lbl_passengers'),
      editable: false,
      display: passengerDisplay.value,
    },
    {
      key: 'total',
      label: t('request_detail.approval_ws_metric_total'),
      editable: false,
      display: totalFmt.value,
    },
  ]
})

const deptHeadLoadErr = ref('')
const lockedLabel = ref('')

function chosenLabel(u) {
  const email = nz(u?.email)
  const name = nz(u?.name)
  return email ? `${name} — ${email}` : name
}

const presetDeptHeadId = computed(() => {
  const raw = props.req?.assigned_dept_head_id
  if (raw == null || raw === '') return null
  const n = Number(raw)
  return Number.isFinite(n) && n > 0 ? n : null
})

const deptHeadPresetLocked = computed(() => presetDeptHeadId.value != null)

const canSubmitFillPrice = computed(() => {
  if (props.acting || total.value <= 0) return false
  if (props.allowAutoApprove) return true
  return deptHeadPresetLocked.value
})

const deptHeadDisplayLine = computed(() => {
  if (lockedLabel.value.trim()) return lockedLabel.value.trim()
  const h = props.req?.assigned_dept_head
  if (h) return chosenLabel(h)
  const snap = props.req?.wizard_snapshot?.form
  const fromSnap = nz(snap?.dept_head_label)
  if (fromSnap) return fromSnap
  return presetDeptHeadId.value != null ? `#${presetDeptHeadId.value}` : '—'
})

watch(
  () => [props.req?.id, props.req?.assigned_dept_head_id, props.req?.assigned_dept_head],
  async ([rid, hid]) => {
    deptHeadLoadErr.value = ''
    lockedLabel.value = ''
    if (rid == null || hid == null || hid === '') return
    const h = props.req?.assigned_dept_head
    if (h && Number(h.id) === Number(hid)) {
      lockedLabel.value = chosenLabel(h)
      return
    }
    try {
      const list = await getAvailableDeptHeads(rid, { pick: hid })
      const u = (list ?? []).find((x) => Number(x.id) === Number(hid))
      if (u) lockedLabel.value = chosenLabel(u)
    } catch (e) {
      deptHeadLoadErr.value = e?.response?.data?.message ?? t('request_detail.assign_dept_head_load_err')
    }
  },
  { immediate: true },
)

function setProcessingNote(note) {
  const text = String(note ?? '').slice(0, 2000)
  notesDraft.value = rows.value.map((_, i) => (i === 0 ? text : notesDraft.value[i] ?? ''))
}

function onSave() {
  if (!deptHeadPresetLocked.value && !props.allowAutoApprove) return
  const payloadRows = isCargo.value
    ? rows.value.map((_, i) => ({
        transport_note: String(cargoTransport.value[i] ?? '').slice(0, 500),
        cost: parseMoneyVnd(cargoCost.value[i] ?? ''),
      }))
    : rows.value.map((_, i) => ({
        unit_price: parseMoneyVnd(unitDraft.value[i] ?? ''),
        extra_fee: parseMoneyVnd(extraDraft.value[i] ?? ''),
        notes: String(notesDraft.value[i] ?? '').slice(0, 2000),
      }))
  emit('save', {
    rows: payloadRows,
    service_price: total.value,
  })
}

const summaryState = computed(() => ({
  totalFmt: totalFmt.value,
  total: total.value,
  canSubmitFillPrice: canSubmitFillPrice.value,
  deptHeadDisplayLine: deptHeadDisplayLine.value,
  deptHeadPresetLocked: deptHeadPresetLocked.value,
  deptHeadLoadErr: deptHeadLoadErr.value,
}))

const workspaceMetricsState = computed(() => ({
  unitSumFmt: unitSumFmt.value,
  extraSumFmt: extraSumFmt.value,
  rowCount: rows.value.length,
}))

watch(
  summaryState,
  (state) => emit('summary-change', { ...state }),
  { immediate: true, deep: true },
)

watch(
  workspaceMetricsState,
  (state) => emit('workspace-metrics-change', { ...state }),
  { immediate: true, deep: true },
)

defineExpose({ submit: onSave, setProcessingNote })
</script>
