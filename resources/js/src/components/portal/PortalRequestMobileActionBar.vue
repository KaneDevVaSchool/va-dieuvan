<template>
  <div
    class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white px-3 py-2 shadow-[0_-4px_16px_rgba(15,23,42,0.06)] lg:hidden supports-[padding:max(0px)]:pb-[max(0.5rem,env(safe-area-inset-bottom))]"
  >
    <div class="mx-auto max-w-7xl">
      <button
        type="button"
        class="inline-flex min-h-[44px] w-full items-center justify-center gap-1.5 rounded-lg bg-va-800 text-sm font-semibold text-white"
        @click="$emit('print')"
      >
        <PrinterIcon class="h-4 w-4" aria-hidden="true" />
        {{ t('portal.detail_hero.print') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { PrinterIcon } from '@heroicons/vue/24/outline'

defineEmits(['print'])

const { t } = useI18n()
</script>
