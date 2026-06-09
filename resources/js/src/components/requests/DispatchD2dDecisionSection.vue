<template>
  <section
    class="overflow-hidden rounded-2xl border border-emerald-200/90 bg-gradient-to-br from-emerald-50/90 via-white to-white p-5 shadow-sm ring-1 ring-emerald-600/10"
  >
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
      <div
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-md shadow-emerald-900/15"
      >
        <CheckBadgeIcon class="h-6 w-6" aria-hidden="true" />
      </div>
      <div class="min-w-0 flex-1">
        <h2 class="text-base font-semibold tracking-tight text-slate-900">{{ t('request_detail.d2d_decision_title') }}</h2>
        <p class="mt-1 text-sm leading-snug text-slate-600">{{ t('request_detail.d2d_decision_lead') }}</p>
        <div class="mt-4 grid gap-2.5 sm:grid-cols-2">
          <Button class="min-h-[2.75rem] w-full justify-center" :loading="acting" @click="$emit('approve')">
            {{ t('request_detail.d2d_approve_confirm_btn') }}
          </Button>
          <Button
            variant="danger"
            class="min-h-[2.75rem] w-full justify-center"
            :loading="acting"
            @click="$emit('reject')"
          >
            {{ t('request_detail.d2d_reject_confirm_btn') }}
          </Button>
        </div>
        <p v-if="inlineMessage && !rejectModalOpen" class="mt-3 text-sm text-rose-800">{{ inlineMessage }}</p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { CheckBadgeIcon } from '@heroicons/vue/24/outline'
import Button from '../ui/Button.vue'

defineProps({
  acting: { type: Boolean, default: false },
  inlineMessage: { type: String, default: '' },
  rejectModalOpen: { type: Boolean, default: false },
})

defineEmits(['approve', 'reject'])

const { t } = useI18n()
</script>
