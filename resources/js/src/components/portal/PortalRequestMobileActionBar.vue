<template>
  <div
    v-if="canPrint || canWithdraw"
    class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white px-3 py-2 shadow-[0_-4px_16px_rgba(15,23,42,0.06)] lg:hidden supports-[padding:max(0px)]:pb-[max(0.5rem,env(safe-area-inset-bottom))]"
  >
    <div class="flex w-full min-w-0 gap-2 px-4 sm:px-6 lg:px-8">
      <button
        v-if="canWithdraw"
        type="button"
        class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 text-sm font-semibold text-rose-900 disabled:opacity-50"
        :disabled="withdrawBusy"
        data-testid="portal-request-withdraw-mobile"
        @click="$emit('withdraw')"
      >
        {{ withdrawBusy ? t('portal.withdraw_pending_busy') : t('portal.withdraw_pending') }}
      </button>
      <button
        v-if="canPrint"
        type="button"
        class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-1.5 rounded-lg bg-va-800 text-sm font-semibold text-white"
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

defineProps({
  canPrint: { type: Boolean, default: false },
  canWithdraw: { type: Boolean, default: false },
  withdrawBusy: { type: Boolean, default: false },
})

defineEmits(['print', 'withdraw'])

const { t } = useI18n()
</script>
