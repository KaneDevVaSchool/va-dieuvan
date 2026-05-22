<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
      <div
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600"
      >
        <BuildingOffice2Icon class="h-5 w-5" aria-hidden="true" />
      </div>
      <div class="min-w-0 flex-1">
        <h2 class="text-base font-semibold tracking-tight text-slate-900">{{ t('request_detail.dept_decision_title') }}</h2>
        <p v-if="servicePriceDisplay != null" class="mt-2 text-sm text-slate-700">
          {{ t('request_detail.service_price_label') }}:
          <span class="font-semibold text-violet-900">{{ servicePriceDisplay }}</span>
        </p>
        <p
          v-if="declaredTotalLabel && servicePriceDisplay"
          class="mt-1 text-xs text-slate-600"
        >
          {{ t('request_detail.dept_declared_total_line') }}:
          <span class="font-medium">{{ declaredTotalLabel }}</span>
        </p>
        <div class="mt-4 grid gap-2.5 sm:grid-cols-2 sm:gap-3">
          <Button
            class="min-h-[2.75rem] w-full justify-center"
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
  declaredTotalLabel: { type: String, default: null },
  acting: { type: Boolean, default: false },
  inlineMessage: { type: String, default: '' },
  rejectModalOpen: { type: Boolean, default: false },
})

defineEmits(['approve', 'reject'])

const { t } = useI18n()
</script>
