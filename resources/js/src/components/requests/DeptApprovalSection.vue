<template>
  <section
    class="overflow-hidden rounded-2xl border border-violet-200/90 bg-gradient-to-br from-violet-50/80 via-white to-white p-5 shadow-md ring-1 ring-violet-600/10"
  >
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
      <div
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-white shadow-lg shadow-violet-900/15"
      >
        <BuildingOffice2Icon class="h-6 w-6" aria-hidden="true" />
      </div>
      <div class="min-w-0 flex-1">
        <h2 class="text-base font-semibold tracking-tight text-slate-900">{{ t('request_detail.dept_decision_title') }}</h2>
        <p v-if="servicePriceDisplay != null" class="mt-2 text-sm font-medium text-slate-800">
          {{ t('request_detail.service_price_label') }}:
          {{ servicePriceDisplay }}
        </p>
        <div class="mt-4 grid gap-2.5 sm:grid-cols-2 sm:gap-3">
          <Button
            class="min-h-[2.75rem] w-full justify-center !bg-violet-600 hover:!bg-violet-700"
            :loading="acting"
            @click="$emit('approve')"
          >
            {{ t('request_detail.dept_approve') }}
          </Button>
          <Button
            variant="danger"
            class="min-h-[2.75rem] w-full justify-center"
            :loading="acting"
            @click="$emit('reject')"
          >
            {{ t('request_detail.dept_reject') }}
          </Button>
        </div>
        <p v-if="inlineMessage && !rejectModalOpen" class="mt-3 text-sm text-slate-700">{{ inlineMessage }}</p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { BuildingOffice2Icon } from '@heroicons/vue/24/outline'
import Button from '../ui/Button.vue'

defineProps({
  /** Định dạng hiển thị (VNĐ) hoặc null nếu chưa có */
  servicePriceDisplay: { type: String, default: null },
  acting: { type: Boolean, default: false },
  inlineMessage: { type: String, default: '' },
  rejectModalOpen: { type: Boolean, default: false },
})

defineEmits(['approve', 'reject'])

const { t } = useI18n()
</script>
