<template>
  <li class="rounded-xl bg-driver-surface p-3 ring-1" :class="error ? 'ring-rose-400/60' : 'ring-white/[0.06]'">
    <div class="flex items-center justify-between gap-2">
      <p class="text-sm font-bold">{{ t('expense.line_n', { n: index + 1 }) }}</p>
      <span v-if="linked" class="rounded bg-sky-500/15 px-2 py-0.5 text-[11px] font-semibold text-sky-200">
        {{ t('expense.line_linked', { id: line.trip_cost_id }) }}
      </span>
      <button type="button" class="flex h-9 w-9 items-center justify-center rounded-lg text-driver-muted ring-1 ring-white/10" :aria-label="t('expense.line_remove')" @click="$emit('remove')">
        <TrashIcon class="h-5 w-5" />
      </button>
    </div>

    <div class="mt-2 grid gap-2">
      <div class="grid grid-cols-2 gap-2">
        <label class="flex min-w-0 flex-col justify-between text-xs text-driver-muted">
          {{ t('expense.line_vehicle') }}
          <select v-model="model.vehicle_id" :disabled="linked" class="mt-1 min-h-[44px] min-w-0 w-full rounded-lg border border-white/10 bg-driver-bg/60 px-2 text-sm text-driver-ink disabled:opacity-60">
            <option value="">{{ t('driver_form.select_vehicle') }}</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.license_plate }}</option>
          </select>
        </label>
        <label v-if="usesTripCosts" class="flex min-w-0 flex-col justify-between text-xs text-driver-muted">
          {{ t('expense.line_cost_type') }}
          <select v-model="model.cost_type" :disabled="linked" class="mt-1 min-h-[44px] min-w-0 w-full rounded-lg border border-white/10 bg-driver-bg/60 px-2 text-sm text-driver-ink disabled:opacity-60">
            <option v-for="ct in EXPENSE_COST_TYPES" :key="ct" :value="ct">{{ t(`expense.cost_type_${ct}`) }}</option>
          </select>
        </label>
        <label v-else class="flex min-w-0 flex-col justify-between text-xs text-driver-muted">
          {{ t('expense.line_unit') }}
          <input v-model="model.unit" type="text" maxlength="32" :placeholder="t('expense.line_unit_ph')" class="mt-1 min-h-[44px] min-w-0 w-full rounded-lg border border-white/10 bg-driver-bg/60 px-2 text-sm text-driver-ink" />
        </label>
      </div>

      <label class="flex min-w-0 flex-col justify-between text-xs text-driver-muted">
        {{ docType === 'no_invoice_statement' ? t('expense.line_item_name') : t('expense.line_category') }}
        <input v-model="model.category" type="text" maxlength="255" class="mt-1 min-h-[44px] min-w-0 w-full rounded-lg border border-white/10 bg-driver-bg/60 px-3 text-sm text-driver-ink" :placeholder="t('driver_form.category')" />
      </label>
      <label class="flex min-w-0 flex-col justify-between text-xs text-driver-muted">
        {{ t('expense.line_description') }}
        <textarea v-model="model.description" rows="2" maxlength="1000" class="mt-1 min-w-0 w-full rounded-lg border border-white/10 bg-driver-bg/60 px-3 py-2 text-sm text-driver-ink" :placeholder="t('driver_form.description')" />
      </label>

      <div class="grid grid-cols-3 gap-2">
        <label class="flex min-w-0 flex-col justify-between text-xs text-driver-muted">
          {{ t('expense.line_qty') }}
          <input v-model="model.quantity" type="number" min="0.01" step="0.01" inputmode="decimal" :disabled="linked" class="mt-1 min-h-[44px] min-w-0 w-full rounded-lg border border-white/10 bg-driver-bg/60 px-2 text-sm tabular-nums text-driver-ink disabled:opacity-60" :placeholder="t('driver_form.quantity')" />
        </label>
        <label class="col-span-2 text-xs text-driver-muted">
          {{ t('expense.line_unit_price') }}
          <input :value="unitPriceDisplay" type="text" inputmode="numeric" :disabled="linked" class="mt-1 min-h-[44px] min-w-0 w-full rounded-lg border border-white/10 bg-driver-bg/60 px-2 text-sm font-semibold tabular-nums text-driver-ink disabled:opacity-60" @input="onPriceInput" :placeholder="t('driver_form.unit_price')" />
        </label>
      </div>

      <template v-if="usesTripCosts">
        <div class="grid grid-cols-2 gap-2">
          <label class="flex min-w-0 flex-col justify-between text-xs text-driver-muted">
            {{ t('expense.line_date') }}
            <DriverDateInput v-model="model.purchased_on" :aria-label="t('expense.line_date')" :placeholder="t('driver_form.select_date')" />
          </label>
          <label v-if="docType !== 'no_invoice_statement'" class="flex items-end gap-2 pb-2 text-sm">
            <input v-model="model.has_invoice" type="checkbox" class="h-5 w-5 accent-[#7fdcc8]" />
            {{ t('expense.line_has_invoice') }}
          </label>
        </div>
        <div v-if="!model.has_invoice || docType === 'no_invoice_statement'" class="grid gap-2">
          <p class="text-[11px] text-amber-200/90">{{ t('expense.line_no_invoice_hint') }}</p>
          <label class="flex min-w-0 flex-col justify-between text-xs text-driver-muted">
            {{ t('expense.line_seller') }}
            <input v-model="model.seller_name" type="text" maxlength="255" class="mt-1 min-h-[44px] min-w-0 w-full rounded-lg border border-white/10 bg-driver-bg/60 px-3 text-sm text-driver-ink" :placeholder="t('driver_form.seller')" />
          </label>
          <label class="flex min-w-0 flex-col justify-between text-xs text-driver-muted">
            {{ t('expense.line_seller_address') }}
            <input v-model="model.seller_address" type="text" maxlength="255" class="mt-1 min-h-[44px] min-w-0 w-full rounded-lg border border-white/10 bg-driver-bg/60 px-3 text-sm text-driver-ink" :placeholder="t('driver_form.seller_address')" />
          </label>
        </div>
      </template>

      <label class="flex min-w-0 flex-col justify-between text-xs text-driver-muted">
        {{ t('expense.line_note') }}
        <input v-model="model.note" type="text" maxlength="255" class="mt-1 min-h-[44px] min-w-0 w-full rounded-lg border border-white/10 bg-driver-bg/60 px-3 text-sm text-driver-ink" :placeholder="t('driver_form.note')" />
      </label>

      <p class="text-right text-sm">
        {{ t('expense.line_amount') }}: <span class="font-bold tabular-nums">{{ formatVndCurrency(lineAmount) }}</span>
      </p>
      <p v-if="error" class="text-xs text-rose-300">{{ error }}</p>
    </div>
  </li>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { TrashIcon } from '@heroicons/vue/24/outline'
import DriverDateInput from '../DriverDateInput.vue'
import { EXPENSE_COST_TYPES } from '../../../constants/checklistExpense'
import { formatVndCurrency, formatVndWhileTyping, parseMoneyVnd } from '../../../util/money'

const props = defineProps({
  line: { type: Object, required: true },
  index: { type: Number, required: true },
  vehicles: { type: Array, default: () => [] },
  docType: { type: String, required: true },
  usesTripCosts: { type: Boolean, default: false },
  error: { type: String, default: '' },
})
defineEmits(['remove'])
const { t } = useI18n()

/** Dòng là object reactive của cha — sửa trực tiếp. */
const model = computed(() => props.line)
const linked = computed(() => !!props.line.trip_cost_id && !props.line.owns_trip_cost)

const unitPriceDisplay = computed(() => formatVndWhileTyping(String(Math.round(Number(props.line.unit_price) || 0) || '')))
const lineAmount = computed(() => {
  if (linked.value) return Number(props.line.amount) || 0
  return Math.round((Number(props.line.quantity) || 1) * (Number(props.line.unit_price) || 0))
})

function onPriceInput(ev) {
  const n = parseMoneyVnd(String(ev.target?.value ?? '').replace(/\D/g, ''))
  model.value.unit_price = Number.isFinite(n) ? n : 0
}
</script>
