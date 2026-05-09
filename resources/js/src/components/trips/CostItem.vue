<template>
  <div
    class="flex flex-wrap items-center gap-2 gap-y-2 border-b border-slate-100 py-3 last:border-b-0 dark:border-slate-700/80 sm:flex-nowrap"
  >
    <div class="flex min-w-0 flex-1 items-start gap-2">
      <div
        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300"
        aria-hidden="true"
      >
        <component :is="typeIcon" class="h-4 w-4" />
      </div>
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
          <span class="text-sm text-slate-800 dark:text-slate-100">{{ costTypeLabel }}</span>
          <span
            v-if="cost.status != null && String(cost.status).trim()"
            class="inline-flex shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
            :class="costStatusClass"
          >
            {{ costStatusLabel }}
          </span>
        </div>
        <p v-if="cost.description?.trim()" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ cost.description.trim() }}</p>
        <div class="mt-1 flex flex-wrap items-center gap-2">
          <a
            v-if="cost.receipt_url"
            :href="cost.receipt_url"
            target="_blank"
            rel="noopener noreferrer"
            class="text-[11px] font-medium text-sky-600 hover:underline dark:text-sky-400"
          >
            {{ receiptLabel }}
          </a>
        </div>
      </div>
    </div>
    <div class="flex shrink-0 flex-col items-end gap-2 sm:flex-row sm:items-center">
      <div class="flex flex-col items-end gap-0.5">
        <span class="text-sm font-bold tabular-nums text-slate-900 dark:text-white">{{ formattedAmount }}</span>
        <time v-if="timeLabel" class="text-[11px] text-slate-400 dark:text-slate-500">{{ timeLabel }}</time>
      </div>
      <div v-if="canSubmit && cost.id != null && !cost.receipt_url" class="flex items-center gap-1">
        <input
          ref="hiddenInputRef"
          type="file"
          accept="image/*,application/pdf"
          class="hidden"
          @change="onReceiptInputChange"
        />
        <button
          type="button"
          :disabled="uploadingReceipt"
          class="rounded-lg border border-slate-200 bg-white p-2 text-slate-500 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900 dark:hover:border-blue-700 dark:hover:bg-blue-950/40 dark:hover:text-blue-300"
          :aria-label="receiptAria"
          @click="triggerPick"
        >
          <PaperClipIcon v-if="!uploadingReceipt" class="h-4 w-4" />
          <span v-else class="flex h-4 w-4 items-center justify-center text-[10px]">…</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, shallowRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { BanknotesIcon, PaperClipIcon, TruckIcon, MapPinIcon, Square3Stack3DIcon } from '@heroicons/vue/24/outline'
import type { CostRow } from '../../composables/useCostTracker'
import { mapCostTypeForUi } from '../../composables/useCostTracker'

const props = defineProps<{
  cost: CostRow
  canSubmit: boolean
  uploadingReceipt: boolean
  costTypeLabel: string
}>()

const emit = defineEmits<{
  'pick-receipt': [costId: number, ev: Event]
}>()

const { t, te, locale } = useI18n()
const hiddenInputRef = shallowRef<HTMLInputElement | null>(null)

function triggerPick() {
  hiddenInputRef.value?.click()
}

function onReceiptInputChange(ev: Event) {
  const id = props.cost.id
  if (id == null) return
  emit('pick-receipt', id, ev)
}

const costStatusLabel = computed(() => {
  const raw = String(props.cost.status ?? '').trim().toLowerCase()
  if (!raw) return ''
  const keyMap: Record<string, string> = {
    draft: 'trip_detail.costs.status_draft',
    submitted: 'trip_detail.costs.status_submitted',
    confirmed: 'trip_detail.costs.status_confirmed',
    rejected: 'trip_detail.costs.status_rejected',
    pending: 'trip_detail.costs.status_pending',
    approved: 'trip_detail.costs.status_approved',
  }
  const key = keyMap[raw]
  if (key && te(key)) return t(key)
  return props.cost.status ?? ''
})

const receiptLabel = computed(() => t('trip_detail.costs.receipt_upload'))
const receiptAria = receiptLabel

const typeIcon = computed(() => {
  const k = mapCostTypeForUi(props.cost.type)
  if (k === 'fuel') return BanknotesIcon
  if (k === 'toll') return TruckIcon
  if (k === 'parking') return MapPinIcon
  return Square3Stack3DIcon
})

const costStatusClass = computed(() => {
  const x = String(props.cost.status ?? '').toLowerCase()
  if (x === 'confirmed' || x === 'approved') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
  if (x === 'rejected') return 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300'
  if (x === 'draft') return 'bg-slate-200 text-slate-800 dark:bg-slate-800 dark:text-slate-200'
  if (x === 'submitted') return 'bg-sky-100 text-sky-900 dark:bg-sky-950/40 dark:text-sky-300'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
})

const formattedAmount = computed(() => {
  const n = Number(props.cost.amount)
  const c = props.cost.currency || 'VND'
  if (!Number.isFinite(n)) return `— ${c}`
  return `${new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'vi-VN').format(n)} ${c}`
})

const timeLabel = computed(() => {
  const raw = props.cost.created_at
  if (!raw || !String(raw).trim()) return ''
  try {
    const d = new Date(raw)
    if (Number.isNaN(d.getTime())) return ''
    return new Intl.DateTimeFormat(locale.value === 'en' ? 'en-GB' : 'vi-VN', {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }).format(d)
  } catch {
    return ''
  }
})
</script>
