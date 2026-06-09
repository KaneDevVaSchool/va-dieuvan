<template>
  <section id="request-focus-fill-price" class="scroll-mt-24 space-y-3">
    <!-- Intro banner -->
    <div class="flex items-center gap-3 rounded-2xl border border-sky-200/80 bg-gradient-to-r from-sky-50 to-slate-50 px-5 py-4 dark:border-sky-900/40 dark:from-sky-950/30 dark:to-slate-900">
      <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-white shadow-sm">
        <CurrencyDollarIcon class="h-5 w-5" aria-hidden="true" />
      </span>
      <div class="min-w-0">
        <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ t('request_detail.fill_price_title') }}</h2>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ t('request_detail.fill_price_lead') }}</p>
      </div>
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

      <!-- Trip info: labeled fields (time, guests, PIC…) -->
      <div
        v-if="rowInfoItems(row).length"
        class="grid gap-2 border-b border-slate-100 px-4 py-3 sm:grid-cols-2 dark:border-slate-800"
      >
        <div
          v-for="item in rowInfoItems(row)"
          :key="item.key"
          class="flex items-start gap-2.5 rounded-xl border border-slate-200/80 bg-slate-50/50 px-3 py-2.5 dark:border-slate-700/80 dark:bg-slate-800/40"
        >
          <span
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
            :class="infoItemTone[item.tone].icon"
          >
            <component :is="item.icon" class="h-4 w-4" aria-hidden="true" />
          </span>
          <div class="min-w-0 flex-1">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
              {{ item.label }}
            </p>
            <p class="mt-0.5 text-sm font-semibold tabular-nums text-slate-800 dark:text-slate-100">
              {{ item.primary }}
            </p>
            <p
              v-if="item.secondary"
              class="mt-0.5 truncate text-xs font-medium text-slate-500 dark:text-slate-400"
            >
              {{ item.secondary }}
            </p>
          </div>
        </div>
      </div>

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

    <!-- Summary: total + dept head + save -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
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
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('request_detail.assign_dept_head_preset_hint') }}</p>
        </div>
        <div
          v-else
          class="mt-2 rounded-xl border border-amber-200/80 bg-amber-50/60 px-3.5 py-3 dark:border-amber-900/40 dark:bg-amber-950/20"
          role="alert"
        >
          <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">{{ t('request_detail.assign_dept_head_missing_staff_title') }}</p>
          <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-300/80">{{ t('request_detail.assign_dept_head_missing_staff_body') }}</p>
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
          <span v-if="message" class="text-sm text-slate-500 dark:text-slate-400">{{ message }}</span>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowRightIcon,
  ArrowUturnLeftIcon,
  ClockIcon,
  CurrencyDollarIcon,
  MapPinIcon,
  ScaleIcon,
  UserGroupIcon,
  UserIcon,
} from '@heroicons/vue/24/outline'
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
  itineraryRowEndpoints,
  formatItineraryRowDt,
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

function rowFrom(row) {
  return itineraryRowEndpoints(row).from
}

function rowTo(row) {
  return itineraryRowEndpoints(row).to
}

const infoItemTone = {
  emerald: { icon: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' },
  rose: { icon: 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300' },
  sky: { icon: 'bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300' },
  violet: { icon: 'bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300' },
  amber: { icon: 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300' },
}

function rowInfoItems(row) {
  const items = []
  const tripType = itineraryTripType.value
  const { from, to } = itineraryRowEndpoints(row)

  if (tripType === 'cargo') {
    const pickupAt = formatItineraryRowDt(row.pickup_at)
    const deliveryAt = formatItineraryRowDt(row.delivery_at)
    if (pickupAt || from) {
      items.push({
        key: 'pickup',
        label: t('request_detail.ops_lbl_pickup_time'),
        primary: pickupAt || '—',
        secondary: from || '',
        icon: ClockIcon,
        tone: 'emerald',
      })
    }
    if (deliveryAt || to) {
      items.push({
        key: 'delivery',
        label: t('request_detail.ops_lbl_delivery_time'),
        primary: deliveryAt || '—',
        secondary: to || '',
        icon: ArrowRightIcon,
        tone: 'rose',
      })
    }
    const qty = nz(row.qty)
    const weight = nz(row.weight)
    if (qty || weight) {
      items.push({
        key: 'load',
        label: t('request_detail.ops_lbl_qty'),
        primary: qty || '—',
        secondary: weight ? `${t('request_detail.ops_lbl_weight')}: ${weight}` : '',
        icon: ScaleIcon,
        tone: 'amber',
      })
    }
    return items
  }

  const outTime = formatItineraryRowDt(row.depart_at)
  const backTime = formatItineraryRowDt(row.return_at)
  if (outTime || from) {
    items.push({
      key: 'out',
      label: t('request_detail.ops_lbl_depart_time'),
      primary: outTime || '—',
      secondary: from || '',
      icon: ArrowRightIcon,
      tone: 'emerald',
    })
  }
  if (backTime || to) {
    items.push({
      key: 'back',
      label: t('request_detail.ops_lbl_return_time'),
      primary: backTime || '—',
      secondary: to || '',
      icon: ArrowUturnLeftIcon,
      tone: 'rose',
    })
  }
  const waypoint = nz(row.waypoint)
  if (waypoint && tripType === 'business') {
    items.push({
      key: 'waypoint',
      label: t('request_detail.ops_lbl_waypoint'),
      primary: waypoint,
      secondary: '',
      icon: MapPinIcon,
      tone: 'amber',
    })
  }
  const guests = nz(row.guests)
  if (guests) {
    items.push({
      key: 'guests',
      label: t('request_detail.ops_lbl_guests'),
      primary: t('request_detail.ops_row_heading_guests', { n: guests }),
      secondary: '',
      icon: UserGroupIcon,
      tone: 'sky',
    })
  }
  const pic = nz(row.person_in_charge)
  if (pic && tripType === 'passenger') {
    items.push({
      key: 'pic',
      label: t('request_detail.ops_lbl_person_in_charge'),
      primary: pic,
      secondary: '',
      icon: UserIcon,
      tone: 'violet',
    })
  }
  return items
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
