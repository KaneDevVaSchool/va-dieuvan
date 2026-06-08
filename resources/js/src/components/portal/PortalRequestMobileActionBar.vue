<template>
  <div
    class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-3 py-2 shadow-[0_-4px_24px_rgba(15,23,42,0.08)] backdrop-blur-sm lg:hidden supports-[padding:max(0px)]:pb-[max(0.5rem,env(safe-area-inset-bottom))]"
  >
    <div class="mx-auto flex max-w-7xl gap-2">
      <button
        type="button"
        class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-200 text-sm font-semibold text-slate-800"
        @click="$emit('copy-id')"
      >
        <ClipboardDocumentIcon class="h-4 w-4" aria-hidden="true" />
        {{ copyIdFeedback ? t('portal.copied') : t('portal.copy_id') }}
      </button>
      <button
        type="button"
        class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-200 text-sm font-semibold text-slate-800 disabled:opacity-40"
        :disabled="!canPrint"
        @click="$emit('print')"
      >
        <PrinterIcon class="h-4 w-4" aria-hidden="true" />
        {{ t('portal.detail_hero.print') }}
      </button>
      <a
        :href="contactHref"
        class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-1.5 rounded-lg bg-va-800 text-sm font-semibold text-white"
      >
        <PhoneIcon class="h-4 w-4" aria-hidden="true" />
        {{ t('portal.detail_hero.contact_short') }}
      </a>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { ClipboardDocumentIcon, PhoneIcon, PrinterIcon } from '@heroicons/vue/24/outline'

defineProps({
  copyIdFeedback: { type: Boolean, default: false },
  canPrint: { type: Boolean, default: false },
  contactHref: { type: String, default: 'mailto:dieuhanh@vaschools.edu.vn' },
})

defineEmits(['copy-id', 'print'])

const { t } = useI18n()
</script>
