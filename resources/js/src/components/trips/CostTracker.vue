<template>
  <section
    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-4 print:break-inside-avoid dark:border-slate-700/80 dark:bg-slate-900/45"
    :aria-label="t('trip_detail.costs_block.title')"
  >
    <div class="flex flex-wrap items-center justify-between gap-2 gap-y-3">
      <div class="min-w-0">
        <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('trip_detail.costs_block.title') }}</h2>
        <p class="sr-only">{{ t('trip_detail.costs_block.subtitle') }}</p>
      </div>
      <div class="ml-auto flex flex-wrap items-center justify-end gap-2">
        <div v-if="(costs ?? []).length" class="text-xs text-slate-500 dark:text-slate-400">{{ t('trip_detail.costs.total', { amount: costsTotalFormatted }) }}</div>
        <RouterLink
          v-if="showCostsLink"
          to="/costs"
          class="rounded-lg px-3 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-200 transition hover:bg-blue-50 dark:text-blue-400 dark:ring-blue-900/70 dark:hover:bg-blue-950/50"
        >
          {{ t('trip_detail.costs.view_all') }}
        </RouterLink>
      </div>
    </div>

    <!-- Quick-add toolbar -->
    <div
      v-if="canSubmit"
      class="relative z-0 mt-3 flex flex-wrap items-end gap-2 rounded-xl border border-slate-200/90 bg-slate-50/80 p-3 dark:border-slate-700/80 dark:bg-slate-950/30"
      @dragover.prevent
      @drop.prevent="onDropPending"
    >
      <div class="flex min-w-0 flex-[1_1_100%] flex-wrap gap-2 sm:flex-[1_1_auto]">
        <span class="sr-only">{{ t('trip_detail.costs.quick_type') }}</span>
        <button
          v-for="opt in COST_TRACKER_TYPES"
          :key="opt.value"
          type="button"
          role="radio"
          :aria-checked="selectedType === opt.value"
          class="rounded-full px-3 py-1 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
          :class="chipClass(opt.value)"
          @click="selectedType = opt.value"
        >
          {{ t(opt.labelKey) }}
        </button>
      </div>
      <div class="flex min-w-[6.5rem] flex-1 shrink-0 flex-col gap-0.5 sm:max-w-[9rem]">
        <label for="trip-quick-cost-amt" class="text-xs text-slate-500 dark:text-slate-400">{{ t('trip_detail.costs.quick_amount') }}</label>
        <input
          id="trip-quick-cost-amt"
          ref="amountInputRef"
          v-model="amount"
          type="number"
          min="0"
          step="1"
          :placeholder="t('trip_detail.costs.quick_amount_ph')"
          autocomplete="transaction-amount"
          class="trip-cost-compact-input h-9 w-full rounded-md border border-slate-200 bg-white px-2.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
        />
      </div>
      <div class="min-w-[7rem] flex-[1_1_10rem] flex-col gap-0.5">
        <label for="trip-quick-cost-desc" class="text-xs text-slate-500 dark:text-slate-400">{{ t('trip_detail.costs.quick_desc') }}</label>
        <input
          id="trip-quick-cost-desc"
          v-model="description"
          type="text"
          :placeholder="t('trip_detail.costs.quick_desc_ph')"
          class="trip-cost-compact-input h-9 w-full rounded-md border border-slate-200 bg-white px-2.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
        />
      </div>
      <div class="flex items-end gap-2">
        <input ref="pendingFileRef" type="file" accept="image/*,application/pdf" class="hidden" @change="onPendingFile" />
        <button
          type="button"
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-600 dark:bg-slate-900 dark:hover:border-blue-700 dark:hover:bg-blue-950/40"
          :title="t('trip_detail.costs.attach_receipt_aria')"
          :aria-label="t('trip_detail.costs.attach_receipt_aria')"
          @click="pendingFileRef?.click()"
        >
          <PaperClipIcon class="h-5 w-5" />
        </button>
        <Button type="button" variant="secondary" class="!h-9 !min-w-[5rem] shrink-0 !py-2 !text-xs" :loading="submitting" @click="submit">
          {{ t('trip_detail.costs.quick_submit') }}
        </Button>
      </div>
      <div v-if="pendingFile" class="flex w-full basis-full flex-wrap items-center gap-2 border-t border-slate-200/80 pt-2 dark:border-slate-700/80">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-md border border-slate-200 bg-slate-100 text-[9px] dark:border-slate-600 dark:bg-slate-800">
          <img v-if="pendingPreviewUrl" :src="pendingPreviewUrl" alt="" class="h-full w-full object-cover" />
          <span v-else class="font-semibold text-slate-600 dark:text-slate-400">PDF</span>
        </div>
        <span class="min-w-0 flex-1 truncate text-xs font-medium text-slate-700 dark:text-slate-300">{{ pendingFile.name }}</span>
        <button
          type="button"
          class="rounded px-2 py-1 text-xs text-slate-500 hover:bg-slate-200/80 hover:text-rose-600 dark:hover:bg-slate-800 dark:hover:text-rose-400"
          @click="clearPendingFile"
        >
          ✕
        </button>
      </div>
      <span v-if="formMsg" class="w-full basis-full text-xs text-slate-600 dark:text-slate-400">{{ formMsg }}</span>
    </div>

    <!-- List -->
    <div class="mt-3 divide-y divide-slate-100 dark:divide-slate-700/80">
      <CostItem
        v-for="c in costs ?? []"
        :key="c.id"
        :cost="c"
        :can-submit="canSubmit"
        :uploading-receipt="receiptUploadingId === c.id"
        :cost-type-label="costTypeLabel(c.type)"
        @pick-receipt="onReceiptPick"
      />
      <div v-if="!(costs ?? []).length" class="border-0 py-8 text-center">
        <p class="text-sm text-slate-600 dark:text-slate-400">{{ t('trip_detail.costs.empty_title') }}</p>
        <button
          v-if="canSubmit"
          type="button"
          class="mt-3 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
          @click="focusQuickAdd"
        >
          {{ t('trip_detail.costs.empty_cta') }}
        </button>
      </div>
    </div>

    <div v-if="breakdown.grand > 0" class="mt-4 border-t border-slate-100 pt-3 dark:border-slate-700">
      <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('trip_detail.costs.breakdown_title') }}</div>
      <div class="mt-2 space-y-2">
        <div v-for="row in breakdownRows" :key="row.key" class="flex items-center gap-2 text-xs">
          <span class="w-24 shrink-0 font-medium text-slate-700 dark:text-slate-300">{{ row.label }}</span>
          <div class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
            <div class="h-full rounded-full bg-blue-500" :style="{ width: row.pct }" />
          </div>
          <span class="w-28 shrink-0 text-right tabular-nums text-slate-800 dark:text-slate-200">{{ row.amountFmt }}</span>
        </div>
        <div class="flex items-center justify-between border-t border-slate-100 pt-2 text-xs font-semibold dark:border-slate-700">
          <span>{{ t('trip_detail.costs.breakdown_total') }}</span>
          <span class="tabular-nums">{{ grandFmt }}</span>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { PaperClipIcon } from '@heroicons/vue/24/outline'
import Button from '../ui/Button.vue'
import CostItem from './CostItem.vue'
import { submitTripCost, uploadTripCostReceipt } from '../../api/costs'
import { newIdempotencyKey } from '../../util/idempotency'
import { COST_TRACKER_TYPES, useCostTypeBreakdown, type CostRow } from '../../composables/useCostTracker'

const props = defineProps<{
  tripId: number
  costs?: CostRow[] | null
  canSubmit: boolean
  showCostsLink?: boolean
}>()

const emit = defineEmits<{
  updated: []
}>()

const { t, te, locale } = useI18n()

const selectedType = ref<string>('fuel')
const amount = ref('')
const description = ref('')
const submitting = ref(false)
const formMsg = ref('')
const pendingFile = ref<File | null>(null)
const pendingPreviewUrl = ref<string | null>(null)
const pendingFileRef = ref<HTMLInputElement | null>(null)
const amountInputRef = ref<HTMLInputElement | null>(null)
const receiptUploadingId = ref<number | null>(null)

const breakdownSource = computed(() => props.costs ?? [])
const breakdown = useCostTypeBreakdown(breakdownSource)

watch(pendingFile, (f) => {
  if (pendingPreviewUrl.value) {
    URL.revokeObjectURL(pendingPreviewUrl.value)
    pendingPreviewUrl.value = null
  }
  if (f && f.type.startsWith('image/')) pendingPreviewUrl.value = URL.createObjectURL(f)
})

function chipClass(value: string) {
  const on = selectedType.value === value
  return on
    ? 'bg-blue-600 text-white ring-2 ring-blue-300 shadow-sm dark:ring-blue-400/80'
    : 'bg-slate-100 text-slate-800 ring-1 ring-slate-200 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-600 dark:hover:bg-slate-700'
}

function focusQuickAdd() {
  amountInputRef.value?.focus()
}

function costTypeLabel(type: string | null | undefined) {
  const raw = String(type ?? '').trim()
  if (!raw) return '—'
  const slug = raw.toLowerCase().replace(/[^a-z0-9_]/g, '_')
  const key = `trip_detail.costs.type_${slug}`
  if (te(key)) return t(key)
  return raw
}

const costsTotalFormatted = computed(() => {
  const list = props.costs ?? []
  if (!list.length) return '—'
  const cur = list[0]?.currency || 'VND'
  const sum = list.reduce((s, c) => s + (Number(c.amount) || 0), 0)
  return `${new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'vi-VN').format(sum)} ${cur}`
})

const grandFmt = computed(() => {
  const cur = props.costs?.[0]?.currency || 'VND'
  return `${new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'vi-VN').format(breakdown.value.grand)} ${cur}`
})

const breakdownRows = computed(() => {
  const { totals, grand } = breakdown.value
  const curLocale = locale.value === 'en' ? 'en-US' : 'vi-VN'
  const fmtN = (n: number) => new Intl.NumberFormat(curLocale).format(n)
  const rows: { key: string; label: string; pct: string; amountFmt: string }[] = []
  const add = (key: 'fuel' | 'toll' | 'parking' | 'other', labelKey: string) => {
    const n = totals[key]
    if (n <= 0) return
    const pct = `${Math.max(4, Math.round((n / grand) * 100))}%`
    rows.push({
      key,
      label: t(labelKey),
      pct,
      amountFmt: `${fmtN(n)} đ`,
    })
  }
  add('fuel', 'trip_detail.costs.type_fuel')
  add('toll', 'trip_detail.costs.type_toll')
  add('parking', 'trip_detail.costs.type_parking')
  add('other', 'trip_detail.costs.type_other')
  return rows
})

function onDropPending(e: DragEvent) {
  const f = e.dataTransfer?.files?.[0]
  if (f) pendingFile.value = f
}

function onPendingFile(e: Event) {
  const input = e.target as HTMLInputElement
  const f = input.files?.[0]
  pendingFile.value = f ?? null
  input.value = ''
}

function clearPendingFile() {
  pendingFile.value = null
}

async function submit() {
  if (!props.canSubmit) return
  formMsg.value = ''
  const type = String(selectedType.value ?? '')
    .trim()
    .toLowerCase()
  const n = Number(amount.value)
  if (!type || !Number.isFinite(n) || n <= 0) {
    formMsg.value = t('trip_detail.costs.quick_invalid')
    return
  }
  submitting.value = true
  try {
    const created = await submitTripCost(
      props.tripId,
      {
        type,
        amount: n,
        currency: 'VND',
        description: description.value?.trim() || undefined,
      },
      { idempotencyKey: newIdempotencyKey() },
    )
    formMsg.value = t('trip_detail.messages.ok')
    amount.value = ''
    description.value = ''
    const file = pendingFile.value
    clearPendingFile()
    emit('updated')
    const costId =
      created && typeof created === 'object' && created !== null && 'id' in created
        ? Number((created as { id?: number }).id)
        : NaN
    if (file && Number.isFinite(costId)) {
      receiptUploadingId.value = costId
      try {
        await uploadTripCostReceipt(props.tripId, costId, file, { idempotencyKey: newIdempotencyKey() })
      } finally {
        receiptUploadingId.value = null
      }
      emit('updated')
    }
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } }
    formMsg.value = err?.response?.data?.message ?? t('trip_detail.messages.error')
  } finally {
    submitting.value = false
  }
}

function onReceiptPick(costId: number, e: Event) {
  onReceiptFile(costId, e)
}

async function onReceiptFile(costId: number, e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file || !props.canSubmit) return
  receiptUploadingId.value = costId
  try {
    await uploadTripCostReceipt(props.tripId, costId, file, { idempotencyKey: newIdempotencyKey() })
    emit('updated')
  } finally {
    receiptUploadingId.value = null
  }
}
</script>
