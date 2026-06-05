<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-[100] overflow-y-auto bg-black/50 p-4"
      role="dialog"
      aria-modal="true"
      @click.self="$emit('close')"
    >
      <div class="flex min-h-full items-end justify-center sm:items-center">
      <div
        class="flex max-h-[min(90dvh,calc(100dvh-2rem))] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-2xl"
        @click.stop
      >
        <div class="shrink-0 border-b border-violet-100 bg-violet-50/80 px-4 py-3">
          <h3 class="text-base font-semibold text-violet-950">{{ t('request_detail.dept_reject_modal_title') }}</h3>
          <p class="mt-0.5 text-xs text-violet-900/80">{{ t('request_detail.dept_reject_modal_lead') }}</p>
        </div>
        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-y-contain p-4">
          <label class="block">
            <span class="mb-1 block text-xs font-medium text-slate-600">{{ t('request_detail.dept_reject_reason_label') }}</span>
            <textarea
              :value="reason"
              rows="5"
              class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none ring-slate-200 focus:ring-2"
              :placeholder="t('request_detail.dept_reject_reason_placeholder')"
              @input="$emit('update:reason', $event.target.value)"
            />
          </label>
          <p v-if="errorMessage" class="text-sm text-rose-700">{{ errorMessage }}</p>
          <div class="flex flex-wrap gap-2">
            <Button variant="danger" class="min-h-[2.75rem]" :loading="acting" @click="$emit('confirm')">
              {{ t('request_detail.dept_confirm_reject') }}
            </Button>
            <Button variant="secondary" type="button" class="min-h-[2.75rem]" :disabled="acting" @click="$emit('close')">
              {{ t('request_detail.dept_cancel_reject') }}
            </Button>
          </div>
        </div>
      </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import Button from '../ui/Button.vue'

defineProps({
  open: { type: Boolean, default: false },
  reason: { type: String, default: '' },
  acting: { type: Boolean, default: false },
  errorMessage: { type: String, default: '' },
})

defineEmits(['update:reason', 'confirm', 'close'])

const { t } = useI18n()
</script>
