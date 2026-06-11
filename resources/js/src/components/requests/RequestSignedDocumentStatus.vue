<template>
  <div
    v-if="current"
    class="mb-4 rounded-xl border border-slate-200 bg-slate-50/90 px-3 py-3 sm:px-4"
  >
    <div class="flex flex-wrap items-center gap-2">
      <span class="text-[11px] font-medium uppercase tracking-wide text-slate-600">{{ t('request_detail.signed_doc_status_heading') }}</span>
      <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold ring-1 ring-slate-200">
        v{{ current.version_no }}
      </span>
      <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1" :class="ocrClass">{{ ocrLabel }}</span>
      <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1" :class="sigClass">{{ sigLabel }}</span>
    </div>
    <div v-if="canManage" class="mt-3 flex flex-wrap gap-2">
      <button
        type="button"
        class="inline-flex h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-800 hover:bg-slate-50 disabled:opacity-50"
        :disabled="ocrBusy"
        @click="$emit('rerun-ocr')"
      >
        {{ t('request_detail.signed_doc_rerun_ocr') }}
      </button>
      <button
        v-if="needsManualReview"
        type="button"
        class="inline-flex h-8 items-center rounded-lg bg-teal-600 px-3 text-xs font-semibold text-white hover:bg-teal-700 disabled:opacity-50"
        :disabled="verifyBusy"
        @click="$emit('verify', 'approve')"
      >
        {{ t('request_detail.signed_doc_verify_approve') }}
      </button>
      <button
        v-if="needsManualReview"
        type="button"
        class="inline-flex h-8 items-center rounded-lg border border-rose-200 bg-white px-3 text-xs font-semibold text-rose-800 hover:bg-rose-50 disabled:opacity-50"
        :disabled="verifyBusy"
        @click="$emit('verify', 'reject')"
      >
        {{ t('request_detail.signed_doc_verify_reject') }}
      </button>
    </div>
    <p v-if="current.ocr_error" class="mt-2 text-xs text-rose-700">{{ current.ocr_error }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  current: { type: Object, default: null },
  canManage: { type: Boolean, default: false },
  ocrBusy: { type: Boolean, default: false },
  verifyBusy: { type: Boolean, default: false },
})

defineEmits(['rerun-ocr', 'verify'])

const { t } = useI18n()

const ocrLabel = computed(() => {
  const st = props.current?.ocr_status || 'pending'
  const key = `request_detail.signed_ocr.${st}`
  const tr = t(key)
  return tr !== key ? tr : st
})

const sigLabel = computed(() => {
  const st = props.current?.verification_status || 'pending'
  const key = `request_detail.signed_verification.${st}`
  const tr = t(key)
  return tr !== key ? tr : st
})

const ocrClass = computed(() => {
  const st = props.current?.ocr_status
  if (st === 'completed') return 'bg-teal-50 text-teal-900 ring-teal-200/80'
  if (st === 'failed') return 'bg-rose-50 text-rose-900 ring-rose-200/80'
  return 'bg-amber-50 text-amber-900 ring-amber-200/80'
})

const sigClass = computed(() => {
  const st = props.current?.verification_status
  if (st === 'auto_pass' || st === 'verified') return 'bg-teal-50 text-teal-900 ring-teal-200/80'
  if (st === 'no_signature' || st === 'rejected') return 'bg-rose-50 text-rose-900 ring-rose-200/80'
  return 'bg-amber-50 text-amber-900 ring-amber-200/80'
})

const needsManualReview = computed(() =>
  ['manual_review', 'no_signature', 'processing', 'pending'].includes(String(props.current?.verification_status || '')),
)
</script>
