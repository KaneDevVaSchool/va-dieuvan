<template>
  <div :class="block ? 'flex flex-col gap-2' : 'flex flex-col items-end gap-1.5 sm:flex-row sm:justify-end'">
    <button
      v-if="canClone"
      type="button"
      class="inline-flex min-h-[36px] items-center justify-center gap-1 rounded-xl border border-teal-200 bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-900 shadow-sm transition hover:bg-teal-100 disabled:opacity-50"
      :disabled="cloneBusy"
      @click="$emit('clone')"
    >
      <span
        v-if="cloneBusy"
        class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-teal-600/30 border-t-teal-700"
        aria-hidden="true"
      />
      {{ cloneBusy ? t('request_detail.reset_clone_busy') : t('request_detail.reset_clone') }}
    </button>
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

defineProps({
  req: { type: Object, required: true },
  variant: { type: String, default: 'portal' },
  canClone: { type: Boolean, default: false },
  cloneBusy: { type: Boolean, default: false },
  block: { type: Boolean, default: false },
})

defineEmits(['clone', 'open-detail'])

const { t } = useI18n()
</script>
