<template>
  <div :class="block ? 'flex flex-col gap-2' : 'flex flex-col items-end gap-1.5 sm:flex-row sm:justify-end'">
    <button
      v-if="showClone"
      type="button"
      class="inline-flex min-h-[36px] items-center justify-center gap-1 rounded-xl border px-3 py-1.5 text-xs font-semibold shadow-sm transition disabled:cursor-not-allowed disabled:opacity-55"
      :class="
        canClone
          ? 'border-violet-300 bg-violet-50 text-violet-950 hover:bg-violet-100'
          : 'border-slate-200 bg-slate-50 text-slate-500'
      "
      :disabled="!canClone || cloneBusy"
      :title="!canClone ? cloneDisabledHint : undefined"
      @click="canClone && $emit('clone')"
    >
      <DocumentDuplicateIcon v-if="!cloneBusy" class="h-4 w-4 shrink-0 opacity-80" aria-hidden="true" />
      <span
        v-else
        class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-violet-600/30 border-t-violet-700"
        aria-hidden="true"
      />
      {{ cloneBusy ? cloneBusyLabel : cloneLabel }}
    </button>
    <RouterLink
      v-if="showCompleteSlip && detailRouteName"
      :to="{
        name: detailRouteName,
        params: { id: String(req.id) },
        query: { operate: '1' },
      }"
      class="inline-flex min-h-[36px] items-center justify-center rounded-xl border border-violet-300 bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-violet-500"
    >
      {{ t('portal.extracurricular_list.complete_slip') }}
    </RouterLink>
    <button
      type="button"
      class="inline-flex min-h-[36px] items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50/60 hover:text-indigo-800"
      @click="$emit('open-detail')"
    >
      {{ variant === 'portal' ? t('portal.open_request', { id: req.id }) : t('requests_page.cta_detail') }}
    </button>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { DocumentDuplicateIcon } from '@heroicons/vue/24/outline'

defineProps({
  req: { type: Object, required: true },
  variant: { type: String, default: 'portal' },
  detailRouteName: { type: String, default: '' },
  showCompleteSlip: { type: Boolean, default: false },
  showClone: { type: Boolean, default: true },
  canClone: { type: Boolean, default: false },
  cloneBusy: { type: Boolean, default: false },
  cloneLabel: { type: String, required: true },
  cloneBusyLabel: { type: String, required: true },
  cloneDisabledHint: { type: String, required: true },
  block: { type: Boolean, default: false },
})

defineEmits(['clone', 'open-detail'])

const { t } = useI18n()
</script>
