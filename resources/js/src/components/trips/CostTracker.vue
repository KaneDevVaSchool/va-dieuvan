<template>
  <section
    class="overflow-hidden rounded-2xl bg-white print:break-inside-avoid dark:bg-slate-900/45"
    :aria-label="t('trip_detail.costs_block.title')"
  >
    <div class="border-b border-amber-100/90 bg-amber-50/95 px-5 py-4 sm:px-6 dark:border-amber-900/40 dark:bg-amber-950/35">
      <div class="flex flex-wrap items-start gap-4">
        <div
          class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-800 shadow-sm dark:bg-amber-950/70 dark:text-amber-200"
        >
          <BanknotesIcon class="h-5 w-5" aria-hidden="true" />
        </div>
        <div class="min-w-0 flex-1">
          <h2 class="text-base font-bold tracking-tight text-slate-900 dark:text-white">{{ t('trip_detail.costs_block.title') }}</h2>
          <p class="mt-1 max-w-2xl text-xs leading-relaxed text-slate-600 dark:text-slate-400">
            {{ t('trip_detail.costs_block.subtitle') }}
          </p>
        </div>
      </div>
    </div>
    <div class="p-5 sm:p-6">
      <div class="rounded-xl bg-white p-4 dark:bg-slate-900/60">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          <div class="text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-400">{{ t('trip_detail.costs.title') }}</div>
          <div class="flex flex-wrap items-center gap-2">
            <div v-if="(costs ?? []).length" class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">
              {{ t('trip_detail.costs.total', { amount: costsTotalFormatted }) }}
            </div>
            <RouterLink
              v-if="showCostsLink"
              to="/costs"
              class="rounded-lg px-2 py-1 text-xs font-semibold text-amber-800 underline decoration-amber-300/80 underline-offset-2 hover:bg-amber-50 hover:text-amber-950 dark:text-amber-300 dark:hover:bg-amber-950/40"
            >
              {{ t('trip_detail.costs.open_list') }}
            </RouterLink>
          </div>
        </div>

        <div
          v-if="canSubmit"
          class="mt-4 rounded-xl border border-dashed border-amber-200/80 bg-amber-50/40 p-3 dark:border-amber-900/50 dark:bg-amber-950/25"
        >
          <div class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ t('trip_detail.costs.quick_title') }}</div>
          <div class="mt-2 flex flex-wrap gap-2">
            <button
              v-for="opt in COST_TRACKER_TYPES"
              :key="opt.value"
              type="button"
              class="rounded-full px-3 py-1 text-[11px] font-semibold transition"
              :class="chipClass(opt.value, opt.color)"
              @click="selectedType = opt.value"
            >
              {{ t(opt.labelKey) }}
            </button>
          </div>
          <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
            <Input
              v-model="amount"
              type="number"
              min="0"
              step="1"
              :label="t('trip_detail.costs.quick_amount')"
              :placeholder="t('trip_detail.costs.quick_amount_ph')"
            />
            <div class="sm:col-span-2 xl:col-span-2">
              <Input v-model="description" :label="t('trip_detail.costs.quick_desc')" :placeholder="t('trip_detail.costs.quick_desc_ph')" />
            </div>
          </div>
          <div
            class="mt-2 rounded-lg border border-dashed border-slate-200 bg-white/80 px-3 py-2 dark:border-slate-600 dark:bg-slate-950/40"
            @dragover.prevent
            @drop.prevent="onDropPending"
          >
            <input ref="pendingFileRef" type="file" accept="image/*,application/pdf" class="hidden" @change="onPendingFile" />
            <div v-if="!pendingFile" class="flex h-16 cursor-pointer items-center justify-center gap-2 text-xs text-slate-600" @click="pendingFileRef?.click()">
              <ArrowUpTrayIcon class="h-5 w-5 text-slate-400" />
              {{ t('trip_detail.costs.receipt_drop_hint') }}
            </div>
            <div v-else class="flex h-16 items-center gap-3">
              <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50 text-[10px] dark:border-slate-600">
                <img v-if="pendingPreviewUrl" :src="pendingPreviewUrl" alt="" class="h-full w-full object-cover" />
                <span v-else class="font-medium text-slate-500">PDF</span>
              </div>
              <div class="min-w-0 flex-1 text-xs">
                <div class="truncate font-medium text-slate-900 dark:text-slate-100">{{ pendingFile.name }}</div>
              </div>
              <button type="button" class="rounded p-1 text-slate-500 hover:bg-slate-100 hover:text-rose-600" @click="clearPendingFile">✕</button>
            </div>
          </div>
          <div class="mt-2 flex flex-wrap items-center gap-2">
            <Button type="button" variant="secondary" class="!py-1.5 !text-xs" :loading="submitting" @click="submit">{{ t('trip_detail.costs.quick_submit') }}</Button>
            <span v-if="formMsg" class="text-xs text-slate-600 dark:text-slate-400">{{ formMsg }}</span>
          </div>
        </div>

        <div class="mt-4 space-y-2">
          <div
            v-for="c in costs ?? []"
            :key="c.id"
            class="flex flex-col gap-1 rounded-xl border border-slate-100/90 bg-white px-3 py-2.5 shadow-sm transition hover:border-amber-100 hover:shadow-md dark:border-slate-700/80 dark:bg-slate-950/40 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ costTypeLabel(c.type) }}</span>
                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide" :class="costStatusClass(c.status)">
                  {{ c.status }}
                </span>
              </div>
              <p v-if="c.description?.trim()" class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">{{ c.description.trim() }}</p>
              <p v-if="c.receipt_url" class="mt-1">
                <a :href="c.receipt_url" target="_blank" rel="noopener" class="text-[11px] font-semibold text-sky-700 hover:underline">{{
                  t('trip_detail.costs.receipt_upload')
                }}</a>
              </p>
            </div>
            <div class="flex shrink-0 flex-col items-end gap-2">
              <div class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">{{ formatCostAmount(c.amount, c.currency) }}</div>
              <div v-if="canSubmit && c.id && !c.receipt_url" class="w-full sm:w-auto">
                <input :ref="(el) => setReceiptInputRef(c.id, el)" type="file" accept="image/*,application/pdf" class="hidden" @change="(e) => onReceiptFile(c.id, e)" />
                <Button type="button" variant="secondary" class="!py-1 !text-[10px]" :loading="receiptUploadingId === c.id" @click="openReceiptPicker(c.id)">
                  {{ t('trip_detail.costs.receipt_upload') }}
                </Button>
              </div>
            </div>
          </div>
          <div
            v-if="!(costs ?? []).length"
            class="rounded-xl border border-dashed border-slate-200/90 bg-slate-50/50 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950/30 dark:text-slate-400"
          >
            {{ t('trip_detail.costs.empty') }}
          </div>
        </div>

        <div v-if="breakdown.grand > 0" class="mt-5 border-t border-slate-100 pt-4 dark:border-slate-700">
          <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ t('trip_detail.costs.breakdown_title') }}</div>
          <div class="mt-3 space-y-2">
            <div v-for="row in breakdownRows" :key="row.key" class="flex items-center gap-2 text-xs">
              <span class="w-24 shrink-0 font-medium text-slate-700 dark:text-slate-300">{{ row.label }}</span>
              <div class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                <div class="h-full rounded-full" :class="row.barClass" :style="{ width: row.pct }" />
              </div>
              <span class="w-28 shrink-0 text-right tabular-nums text-slate-800 dark:text-slate-200">{{ row.amountFmt }}</span>
            </div>
            <div class="flex items-center justify-between border-t border-slate-100 pt-2 text-xs font-semibold dark:border-slate-700">
              <span>{{ t('trip_detail.costs.breakdown_total') }}</span>
              <span class="tabular-nums">{{ grandFmt }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowUpTrayIcon, BanknotesIcon } from '@heroicons/vue/24/outline'
import Button from '../ui/Button.vue'
import Input from '../ui/Input.vue'
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
const receiptUploadingId = ref<number | null>(null)
const receiptInputRefs = ref<Record<number, HTMLInputElement | null>>({})

const breakdownSource = computed(() => props.costs ?? [])
const breakdown = useCostTypeBreakdown(breakdownSource)

watch(pendingFile, (f) => {
  if (pendingPreviewUrl.value) {
    URL.revokeObjectURL(pendingPreviewUrl.value)
    pendingPreviewUrl.value = null
  }
  if (f && f.type.startsWith('image/')) pendingPreviewUrl.value = URL.createObjectURL(f)
})

function chipClass(value: string, color: 'amber' | 'blue' | 'purple' | 'gray') {
  const on = selectedType.value === value
  const map = {
    amber: on ? 'bg-amber-500 text-white ring-2 ring-amber-300' : 'bg-amber-50 text-amber-900 ring-1 ring-amber-200 hover:bg-amber-100',
    blue: on ? 'bg-blue-600 text-white ring-2 ring-blue-300' : 'bg-blue-50 text-blue-900 ring-1 ring-blue-200 hover:bg-blue-100',
    purple: on ? 'bg-purple-600 text-white ring-2 ring-purple-300' : 'bg-purple-50 text-purple-900 ring-1 ring-purple-200 hover:bg-purple-100',
    gray: on ? 'bg-slate-600 text-white ring-2 ring-slate-400' : 'bg-slate-50 text-slate-800 ring-1 ring-slate-200 hover:bg-slate-100',
  }
  return map[color]
}

function costTypeLabel(type: string | null | undefined) {
  const raw = String(type ?? '').trim()
  if (!raw) return '—'
  const slug = raw.toLowerCase().replace(/[^a-z0-9_]/g, '_')
  const key = `trip_detail.costs.type_${slug}`
  if (te(key)) return t(key)
  return raw
}

function costStatusClass(s: string | null | undefined) {
  const x = String(s ?? '').toLowerCase()
  if (x === 'confirmed' || x === 'approved') return 'bg-emerald-100 text-emerald-800'
  if (x === 'rejected') return 'bg-rose-100 text-rose-800'
  if (x === 'pending') return 'bg-amber-100 text-amber-900'
  if (x === 'submitted') return 'bg-sky-100 text-sky-900'
  return 'bg-slate-100 text-slate-700'
}

function formatCostAmount(amountVal: number | string | null | undefined, currency: string | null | undefined) {
  const n = Number(amountVal)
  const c = currency || 'VND'
  if (!Number.isFinite(n)) return `— ${c}`
  return `${new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'vi-VN').format(n)} ${c}`
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
  const rows: { key: string; label: string; pct: string; amountFmt: string; barClass: string }[] = []
  const add = (key: 'fuel' | 'toll' | 'parking' | 'other', labelKey: string, barClass: string) => {
    const n = totals[key]
    if (n <= 0) return
    const pct = `${Math.max(4, Math.round((n / grand) * 100))}%`
    rows.push({
      key,
      label: t(labelKey),
      pct,
      amountFmt: `${fmtN(n)} đ`,
      barClass,
    })
  }
  add('fuel', 'trip_detail.costs.type_fuel', 'bg-amber-500')
  add('toll', 'trip_detail.costs.type_toll', 'bg-blue-500')
  add('parking', 'trip_detail.costs.type_parking', 'bg-purple-500')
  add('other', 'trip_detail.costs.type_other', 'bg-slate-400')
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
    const costId = created && typeof created === 'object' && created !== null && 'id' in created ? Number((created as { id?: number }).id) : NaN
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

function setReceiptInputRef(id: number | undefined, el: unknown) {
  if (id == null) return
  receiptInputRefs.value[id] = (el as HTMLInputElement) ?? null
}

function openReceiptPicker(costId: number) {
  receiptInputRefs.value[costId]?.click()
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
