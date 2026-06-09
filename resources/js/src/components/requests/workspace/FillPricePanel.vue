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
          <input
            :value="cargoCost[idx]"
            type="text"
            inputmode="numeric"
            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-right text-base tabular-nums text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            :placeholder="t('request_detail.ops_money_ph')"
            :title="t('request_detail.ops_cost_tooltip')"
            @input="cargoCost[idx] = fmtTyping($event.target.value)"
          />
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
            <input
              :value="unitDraft[idx]"
              type="text"
              inputmode="numeric"
              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-right text-base tabular-nums text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              :placeholder="t('request_detail.ops_money_ph')"
              :title="t('request_detail.ops_unit_price_tooltip')"
              @input="unitDraft[idx] = fmtTyping($event.target.value)"
            />
          </label>
          <label class="block">
            <FillPriceFieldLabel
              :label="t('request_detail.ops_lbl_extra_fee')"
              :tooltip="t('request_detail.ops_extra_fee_tooltip')"
            />
            <input
              :value="extraDraft[idx]"
              type="text"
              inputmode="numeric"
              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-right text-base tabular-nums text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/30 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
              :placeholder="t('request_detail.ops_money_zero_ph')"
              :title="t('request_detail.ops_extra_fee_tooltip')"
              @input="extraDraft[idx] = fmtTyping($event.target.value)"
            />
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

      <!-- Dept head combobox -->
      <div class="relative mt-4">
        <FillPriceFieldLabel
          for-id="fp-dept-head"
          :label="t('request_detail.assign_dept_head_label')"
          :tooltip="t('request_detail.assign_dept_head_tooltip')"
        />
        <input
          id="fp-dept-head"
          v-model="deptHeadQ"
          type="search"
          role="combobox"
          autocomplete="off"
          :aria-expanded="dropdownOpen && deptHeadQ.trim().length >= 2"
          :disabled="acting"
          :placeholder="t('request_detail.assign_dept_head_combo_ph')"
          :title="t('request_detail.assign_dept_head_tooltip')"
          class="w-full rounded-lg border bg-white px-3 py-2.5 text-base text-slate-900 outline-none focus:ring-1 dark:bg-slate-800 dark:text-slate-100"
          :class="clientErr ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/30' : 'border-slate-300 focus:border-sky-500 focus:ring-sky-500/30 dark:border-slate-700'"
          @input="scheduleSearch"
          @focus="onFocus"
          @blur="onBlur"
        />
        <div v-if="searchLoading" class="absolute right-3 top-[2.35rem] h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-sky-600" />
        <ul
          v-if="dropdownOpen && deptHeadQ.trim().length >= 2"
          class="absolute z-40 mt-1 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-800"
          role="listbox"
        >
          <li v-if="searchLoading" class="px-3 py-2.5 text-slate-500 dark:text-slate-400">{{ t('request_detail.assign_dept_head_loading') }}</li>
          <template v-else-if="options.length">
            <li v-for="u in options" :key="u.id">
              <button type="button" class="flex w-full flex-col gap-0.5 px-3 py-2.5 text-left transition hover:bg-sky-50 dark:hover:bg-slate-700" @mousedown.prevent="pick(u)">
                <span class="font-medium text-slate-900 dark:text-slate-100">{{ u.name }}</span>
                <span class="truncate text-xs text-slate-500 dark:text-slate-400">{{ u.email }}</span>
              </button>
            </li>
          </template>
          <li v-else class="px-3 py-2.5 text-slate-500 dark:text-slate-400">{{ t('request_detail.assign_dept_head_no_match') }}</li>
        </ul>
        <p v-if="loadErr" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ loadErr }}</p>
        <p v-else-if="clientErr" class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ clientErr }}</p>
        <p v-else class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('request_detail.assign_dept_head_combo_hint') }}</p>
      </div>

      <div class="mt-4 flex flex-wrap items-center gap-3">
        <Button class="!bg-sky-600 hover:!bg-sky-700" :loading="acting" @click="onSave">
          {{ t('request_detail.fill_price_submit') }}
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
import { parseMoneyVnd, formatVndCurrency, formatVndWhileTyping } from '../../../util/money'
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

function fmtTyping(v) {
  return formatVndWhileTyping(v)
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
    cargoCost.value = r.map((x) => (x?.cost ? formatVndWhileTyping(String(x.cost)) : ''))
  } else {
    unitDraft.value = r.map((x) => (x?.unit_price ? formatVndWhileTyping(String(x.unit_price)) : ''))
    extraDraft.value = r.map((x) => (x?.extra_fee ? formatVndWhileTyping(String(x.extra_fee)) : ''))
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
const totalFmt = computed(() => formatVndCurrency(total.value))

// ── Dept head search ──
const deptHeadQ = ref('')
const options = ref([])
const dropdownOpen = ref(false)
const searchLoading = ref(false)
const selectedId = ref('')
const lockedLabel = ref('')
const loadErr = ref('')
const clientErr = ref('')
let timer = null

function chosenLabel(u) {
  const email = nz(u?.email)
  const name = nz(u?.name)
  return email ? `${name} — ${email}` : name
}

watch(
  () => [props.req?.id, props.req?.assigned_dept_head_id],
  async ([rid, hid]) => {
    if (rid == null) return
    if (hid != null) {
      searchLoading.value = true
      try {
        const list = await getAvailableDeptHeads(rid, { pick: hid })
        options.value = list ?? []
        const u = options.value.find((x) => Number(x.id) === Number(hid))
        selectedId.value = String(hid)
        if (u) {
          lockedLabel.value = chosenLabel(u)
          deptHeadQ.value = lockedLabel.value
        }
      } catch (e) {
        loadErr.value = e?.response?.data?.message ?? t('request_detail.assign_dept_head_load_err')
      } finally {
        searchLoading.value = false
      }
    }
  },
  { immediate: true },
)

watch(deptHeadQ, () => {
  if (lockedLabel.value && deptHeadQ.value.trim() !== lockedLabel.value.trim()) selectedId.value = ''
})

function scheduleSearch() {
  loadErr.value = ''
  clientErr.value = ''
  clearTimeout(timer)
  timer = setTimeout(runSearch, 350)
}
async function runSearch() {
  const rid = props.req?.id
  if (rid == null) return
  const q = deptHeadQ.value.trim()
  if (q.length < 2) {
    options.value = []
    dropdownOpen.value = false
    return
  }
  searchLoading.value = true
  dropdownOpen.value = true
  try {
    options.value = await getAvailableDeptHeads(rid, { q })
    loadErr.value = ''
  } catch (e) {
    options.value = []
    loadErr.value = e?.response?.data?.message ?? t('request_detail.assign_dept_head_load_err')
  } finally {
    searchLoading.value = false
  }
}
function pick(u) {
  selectedId.value = String(u.id)
  lockedLabel.value = chosenLabel(u)
  deptHeadQ.value = lockedLabel.value
  dropdownOpen.value = false
  clientErr.value = ''
}
function onFocus() {
  if (deptHeadQ.value.trim().length >= 2) {
    dropdownOpen.value = true
    runSearch()
  }
}
function onBlur() {
  setTimeout(() => (dropdownOpen.value = false), 180)
}

function onSave() {
  clientErr.value = ''
  if (selectedId.value === '' || selectedId.value == null) {
    clientErr.value = t('request_detail.assign_dept_head_required')
    return
  }
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
    dept_head_user_id: Number(selectedId.value),
  })
}
</script>
