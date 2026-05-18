<template>
  <section
    class="overflow-hidden rounded-2xl border border-sky-200/90 bg-gradient-to-br from-sky-50/80 via-white to-white p-5 shadow-md ring-1 ring-sky-600/10"
  >
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
      <div
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-600 text-white shadow-lg shadow-sky-900/15"
      >
        <CurrencyDollarIcon class="h-6 w-6" aria-hidden="true" />
      </div>
      <div class="min-w-0 flex-1">
        <h2 class="text-base font-semibold tracking-tight text-slate-900">{{ t('request_detail.fill_price_title') }}</h2>
        <p class="mt-1 text-sm leading-snug text-slate-600">{{ t('request_detail.fill_price_lead') }}</p>
        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2">
          <a
            v-if="referencePricingUrl"
            :href="referencePricingUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 text-sm font-semibold text-teal-700 underline decoration-teal-500/30 underline-offset-2 hover:text-teal-900"
          >
            {{ t('request_detail.reference_pricing_link') }}
            <ArrowTopRightOnSquareIcon class="h-4 w-4 shrink-0 text-teal-600" aria-hidden="true" />
          </a>
        </div>
        <form class="mt-4 grid gap-3 sm:max-w-md" @submit.prevent="$emit('submit')">
          <Input
            :model-value="servicePrice"
            type="text"
            inputmode="decimal"
            :label="t('request_detail.service_price_label')"
            :placeholder="t('request_detail.service_price_placeholder')"
            @update:model-value="$emit('update:servicePrice', $event)"
          />
          <div class="flex flex-wrap items-center gap-2">
            <Button type="submit" class="!bg-sky-600 hover:!bg-sky-700" :loading="submitting">
              {{ t('request_detail.fill_price_submit') }}
            </Button>
            <span v-if="message" class="text-xs text-slate-600">{{ message }}</span>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { CurrencyDollarIcon, ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline'
import Button from '../ui/Button.vue'
import Input from '../ui/Input.vue'

defineProps({
  referencePricingUrl: { type: String, default: '' },
  servicePrice: { type: String, default: '' },
  submitting: { type: Boolean, default: false },
  message: { type: String, default: '' },
})

defineEmits(['update:servicePrice', 'submit'])

const { t } = useI18n()
</script>
