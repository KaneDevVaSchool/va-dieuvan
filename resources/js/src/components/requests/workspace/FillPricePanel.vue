<template>
  <section id="request-focus-fill-price" class="scroll-mt-24 space-y-4">
    <!-- Intro -->
    <div class="rounded-2xl border border-sky-200 bg-sky-50/60 p-4 dark:border-sky-900/50 dark:bg-sky-950/20 sm:p-5">
      <div class="flex items-start gap-3">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-white shadow-sm">
          <CurrencyDollarIcon class="h-6 w-6" aria-hidden="true" />
        </span>
        <div class="min-w-0">
          <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ t('request_detail.fill_price_title') }}</h2>
          <p class="mt-0.5 text-sm text-slate-600 dark:text-slate-400">{{ t('request_detail.fill_price_lead') }}</p>
        </div>
      </div>
    </div>

    <!-- Rows -->
    <div
      v-for="(row, idx) in rows"
      :key="idx"
      class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800 sm:p-5"
    >
      <div class="flex items-start gap-3">
        <span
          class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300"
          :aria-label="t('request_detail.ops_row_badge_aria', { n: idx + 1 })"
        >
          {{ idx + 1 }}
        </span>
        <div class="min-w-0 flex-1 space-y-1">
          <p class="text-base font-semibold leading-snug text-slate-900 dark:text-white">
            {{ rowHeading(row, idx) }}
          </p>
          <ul
            v-if="rowSummary(row, idx).length"
            class="space-y-0.5 text-sm leading-snug text-slate-600 dark:text-slate-400"
          >
            <li v-for="(line, li) in rowSummary(row, idx)" :key="li" class="break-words">{{ line }}</li>
          </ul>
        </div>
      </div>

      <!-- Cargo inputs -->
      <div v-if="isCargo" class="mt-4 grid gap-4 sm:grid-cols-2">
        <label class="block">
          <FillPriceFieldLabel
            :label="t('request_detail.ops_lbl_transport_type')"
            :tooltip="t('request_detail.ops_transport_type_tooltip')"
          />
          <input
            :value="cargoTransport[idx]"
            type="text"
            maxlength="500"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-base text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
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
          <div class="relative">
            <input
              :value="cargoCost[idx]"
              type="text"
              inputmode="numeric"
              class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-3 pr-14 text-right text-base tabular-nums text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              :placeholder="t('request_detail.ops_money_ph')"
              :title="t('request_detail.ops_cost_tooltip')"
              @input="cargoCost[idx] = fmtTyping($event.target.value)"
              @blur="cargoCost[idx] = fmtBlur(cargoCost[idx])"
            />
            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm font-medium text-slate-500 dark:text-slate-400">{{ vndSuffix }}</span>
          </div>
        </label>
      </div>

      <!-- Passenger / business inputs -->
      <div v-else class="mt-4 space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
          <label class="block">
            <FillPriceFieldLabel
              :label="t('request_detail.ops_lbl_unit_price')"
              :tooltip="t('request_detail.ops_unit_price_tooltip')"
            />
            <div class="relative">
              <input
                :value="unitDraft[idx]"
                type="text"
                inputmode="numeric"
                class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-3 pr-14 text-right text-base tabular-nums text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('request_detail.ops_money_ph')"
                :title="t('request_detail.ops_unit_price_tooltip')"
                @input="unitDraft[idx] = fmtTyping($event.target.value)"
                @blur="unitDraft[idx] = fmtBlur(unitDraft[idx])"
              />
              <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm font-medium text-slate-500 dark:text-slate-400">{{ vndSuffix }}</span>
            </div>
          </label>
          <label class="block">
            <FillPriceFieldLabel
              :label="t('request_detail.ops_lbl_extra_fee')"
              :tooltip="t('request_detail.ops_extra_fee_tooltip')"
            />
            <div class="relative">
              <input
                :value="extraDraft[idx]"
                type="text"
                inputmode="numeric"
                class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-3 pr-14 text-right text-base tabular-nums text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                :placeholder="t('request_detail.ops_money_zero_ph')"
                :title="t('request_detail.ops_extra_fee_tooltip')"
                @input="extraDraft[idx] = fmtTyping($event.target.value)"
                @blur="extraDraft[idx] = fmtBlur(extraDraft[idx])"
              />
              <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm font-medium text-slate-500 dark:text-slate-400">{{ vndSuffix }}</span>
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
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-base text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            :placeholder="t('request_detail.ops_notes_ph')"
            :title="t('request_detail.ops_notes_tooltip')"
            @input="notesDraft[idx] = String($event.target.value).slice(0, 2000)"
          />
        </label>
      </div>
    </div>

    <!-- Total + dept head + save -->
    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-800/30 sm:p-5">
      <div
        class="flex items-center justify-between gap-3 border-b border-slate-200 pb-3 dark:border-slate-700"
        :title="t('request_detail.fill_price_total_tooltip')"
      >
        <span class="flex items-center gap-1 text-sm font-semibold text-slate-600 dark:text-slate-300">
          {{ t('request_detail.fill_price_total') }}
          <span
            :title="t('request_detail.fill_price_total_tooltip')"
            class="inline-flex cursor-help text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-200"
            @click.prevent
          >
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path
                fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 012 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z"
                clip-rule="evenodd"
              />
            </svg>
          </span>
        </span>
        <span class="text-xl font-bold tabular-nums text-teal-600 dark:text-teal-400">{{ totalFmt }}</span>
      </div>

      <div
        v-if="deptHeadPresetLocked"
        class="mt-4 rounded-lg border border-sky-200 bg-sky-50/50 px-3 py-3 dark:border-sky-900/40 dark:bg-sky-950/20"
      >
        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">
          {{ t('request_detail.assign_dept_head_preset_label') }}
        </p>
        <p class="mt-1 text-base font-semibold text-slate-900 dark:text-white">{{ deptHeadDisplayLine }}</p>
        <p v-if="deptHeadLoadErr" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ deptHeadLoadErr }}</p>
        <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('request_detail.assign_dept_head_preset_hint') }}</p>
      </div>

      <div
        v-else
        class="mt-4 rounded-lg border border-amber-200 bg-amber-50/80 px-3 py-3 dark:border-amber-900/40 dark:bg-amber-950/20"
        role="alert"
      >
        <p class="text-sm font-medium text-amber-900 dark:text-amber-100">
          {{ t('request_detail.assign_dept_head_missing_staff_title') }}
        </p>
        <p class="mt-1 text-sm text-amber-800 dark:text-amber-200/90">
          {{ t('request_detail.assign_dept_head_missing_staff_body') }}
        </p>
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
        <span v-if="message" class="text-sm text-slate-600 dark:text-slate-400">{{ message }}</span>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { CurrencyDollarIcon } from '@heroicons/vue/24/outline'
import Button from '../../ui/Button.vue'
import FillPriceFieldLabel from './FillPriceFieldLabel.vue'
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
  itineraryRowSummaryLines,
  nz,
  resolveItineraryTripType,
} from '../../../util/requestItineraryRowDisplay'

const { t } = useI18n()

const props = defineProps({
  req: { type: Object, default: null },
  acting: { type: Boolean, default: false },
  message: { type: String, default: '' },
})

const emit = defineEmits(['save'])

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

function rowSummary(row, idx) {
  return itineraryRowSummaryLines(row, { tripType: itineraryTripType.value, t, index: idx })
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

const canSubmitFillPrice = computed(() => deptHeadPresetLocked.value && !props.acting)

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

function onSave() {
  if (!deptHeadPresetLocked.value) return
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
</script>
