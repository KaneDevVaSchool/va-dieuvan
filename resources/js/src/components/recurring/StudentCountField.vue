<template>
  <div>
    <template v-if="!embedded">
      <h2 class="text-base font-semibold text-slate-900">
        {{ t('request_detail.passenger_follow_section_title') }}
      </h2>
      <p class="mt-1 text-xs text-slate-600">{{ t('request_detail.passenger_section_lead') }}</p>
      <p v-if="studentCountPlan != null" class="mt-2 text-xs text-slate-700">
        {{ t('request_detail.passenger_count_plan_label') }}:
        <span class="font-semibold tabular-nums">{{ studentCountPlan }}</span>
      </p>
    </template>
    <p v-else class="text-xs font-semibold uppercase tracking-wide text-slate-600">
      {{ t('request_detail.passenger_follow_section_title') }}
    </p>

    <div
      v-if="locked"
      class="mt-3 rounded-lg border border-amber-200/90 bg-amber-50 px-3 py-2.5 text-sm text-amber-950"
      :class="embedded ? '' : 'mt-4 rounded-xl py-3'"
    >
      <div class="flex gap-2.5">
        <LockClosedIcon class="h-5 w-5 shrink-0 text-amber-800" aria-hidden="true" />
        <div class="min-w-0 space-y-1 text-xs leading-snug">
          <p class="text-sm font-semibold">{{ t('request_detail.passenger_locked_banner_title') }}</p>
          <p v-if="departAtFormatted">
            {{ t('request_detail.passenger_locked_banner_depart', { dt: departAtFormatted }) }}
          </p>
          <p>{{ t('request_detail.passenger_locked') }}</p>
          <p class="pt-1 font-medium text-slate-800">
            {{ t('request_detail.passenger_count_actual_label') }}:
            <span class="tabular-nums">{{ passengerCount }}</span>
          </p>
        </div>
      </div>
    </div>

    <div v-else class="mt-3 space-y-2.5" :class="embedded ? 'max-w-lg' : 'max-w-md'">
      <p
        v-if="isDispatcherOverride"
        class="inline-flex items-center gap-1.5 rounded-md border border-amber-200 bg-amber-50 px-2 py-1 text-[11px] font-medium text-amber-950"
      >
        <span class="size-1.5 shrink-0 rounded-full bg-amber-500" aria-hidden="true" />
        {{ t('request_detail.passenger_dispatcher_override_hint') }}
      </p>
      <div
        class="flex flex-col gap-2 sm:flex-row sm:items-end sm:gap-3"
      >
        <label class="min-w-0 flex-1 text-xs font-medium text-slate-700">
          {{ t('request_detail.passenger_count_actual_label') }}
          <input
            :value="passengerCount"
            type="number"
            min="1"
            max="999"
            class="mt-1 block w-full max-w-[8rem] rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm tabular-nums shadow-sm focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
            :disabled="saving"
            @input="$emit('update:passengerCount', Math.round(Number($event.target.value) || 0))"
          />
        </label>
        <button
          type="button"
          class="shrink-0 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-500 disabled:cursor-not-allowed disabled:opacity-50 sm:mb-0.5"
          :disabled="saving"
          @click="$emit('save')"
        >
          {{ saving ? t('request_detail.passenger_save_busy') : t('request_detail.passenger_save') }}
        </button>
      </div>
    </div>
    <p v-if="error" class="mt-2 text-xs font-medium text-rose-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { LockClosedIcon } from '@heroicons/vue/24/outline'

defineProps({
  passengerCount: { type: Number, default: 1 },
  studentCountPlan: { type: Number, default: null },
  locked: { type: Boolean, default: true },
  isDispatcherOverride: { type: Boolean, default: false },
  departAtFormatted: { type: String, default: '' },
  saving: { type: Boolean, default: false },
  error: { type: String, default: '' },
  embedded: { type: Boolean, default: false },
})

defineEmits(['update:passengerCount', 'save'])

const { t } = useI18n()
</script>
