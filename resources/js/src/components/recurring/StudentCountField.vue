<template>
  <div>
    <h2 class="text-base font-semibold text-slate-900">{{ t('request_detail.passenger_follow_section_title') }}</h2>
    <p class="mt-1 text-xs text-slate-600">{{ t('request_detail.passenger_section_lead') }}</p>
    <div
      v-if="locked"
      class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-3 text-sm text-amber-950"
    >
      <div class="flex gap-2">
        <LockClosedIcon class="h-5 w-5 shrink-0 text-amber-800" aria-hidden="true" />
        <div class="min-w-0">
          <p class="font-semibold">{{ t('request_detail.passenger_locked_banner_title') }}</p>
          <p v-if="departAtFormatted" class="mt-1 text-xs leading-snug">
            {{ t('request_detail.passenger_locked_banner_depart', { dt: departAtFormatted }) }}
          </p>
          <p class="mt-1 text-xs leading-snug">{{ t('request_detail.passenger_locked') }}</p>
          <p class="mt-2 text-xs font-medium text-slate-800">
            {{ t('request_detail.passenger_count_label') }}:
            <span class="tabular-nums">{{ passengerCount }}</span>
          </p>
        </div>
      </div>
    </div>
    <div v-else class="mt-3 flex max-w-md flex-wrap items-end gap-2">
      <label class="block text-xs font-medium text-slate-700">
        {{ t('request_detail.passenger_count_label') }}
        <input
          :value="passengerCount"
          type="number"
          min="1"
          max="999"
          class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
          :disabled="saving"
          @input="$emit('update:passengerCount', Math.round(Number($event.target.value) || 0))"
        />
      </label>
      <button
        type="button"
        class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500 disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="saving"
        @click="$emit('save')"
      >
        {{ saving ? t('request_detail.passenger_save_busy') : t('request_detail.passenger_save') }}
      </button>
    </div>
    <p v-if="error" class="mt-2 text-xs font-medium text-rose-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { LockClosedIcon } from '@heroicons/vue/24/outline'

defineProps({
  passengerCount: { type: Number, default: 1 },
  locked: { type: Boolean, default: true },
  departAtFormatted: { type: String, default: '' },
  saving: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

defineEmits(['update:passengerCount', 'save'])

const { t } = useI18n()
</script>
