<template>
  <div v-if="current" class="space-y-2 rounded-xl border border-slate-200 bg-slate-50/80 px-4 py-3">
    <div class="flex flex-wrap items-center gap-2">
      <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('portal.signed_status_heading') }}</span>
      <span v-if="workflowLabel" class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-[11px] font-semibold text-indigo-800">
        {{ workflowLabel }}
      </span>
    </div>
    <div class="flex flex-wrap gap-2">
      <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1" :class="ocrBadgeClass">
        {{ ocrLabel }}
      </span>
      <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1" :class="sigBadgeClass">
        {{ sigLabel }}
      </span>
    </div>
    <p v-if="current.ocr_error" class="text-xs text-rose-700">{{ current.ocr_error }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  current: { type: Object, default: null },
  signingWorkflowStatus: { type: String, default: '' },
})

const { t } = useI18n()

const workflowLabel = computed(() => {
  const s = props.signingWorkflowStatus
  if (!s) return ''
  const key = `portal.signing_workflow.${s}`
  const tr = t(key)
  return tr !== key ? tr : s
})

const ocrLabel = computed(() => {
  const st = props.current?.ocr_status || 'pending'
  const key = `portal.signed_ocr.${st}`
  const tr = t(key)
  return tr !== key ? tr : st
})

const ocrBadgeClass = computed(() => {
  const st = props.current?.ocr_status
  if (st === 'completed') return 'bg-teal-50 text-teal-900 ring-teal-200/80'
  if (st === 'failed') return 'bg-rose-50 text-rose-900 ring-rose-200/80'
  if (st === 'processing' || st === 'queued') return 'bg-amber-50 text-amber-900 ring-amber-200/80'
  return 'bg-slate-100 text-slate-700 ring-slate-200/80'
})

const sigLabel = computed(() => {
  const st = props.current?.verification_status || 'pending'
  const key = `portal.signed_verification.${st}`
  const tr = t(key)
  return tr !== key ? tr : st
})

const sigBadgeClass = computed(() => {
  const st = props.current?.verification_status
  if (st === 'auto_pass' || st === 'verified') return 'bg-teal-50 text-teal-900 ring-teal-200/80'
  if (st === 'no_signature' || st === 'rejected') return 'bg-rose-50 text-rose-900 ring-rose-200/80'
  if (st === 'manual_review' || st === 'processing') return 'bg-amber-50 text-amber-900 ring-amber-200/80'
  return 'bg-slate-100 text-slate-700 ring-slate-200/80'
})
</script>
