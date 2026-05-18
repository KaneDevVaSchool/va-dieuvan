<template>
  <div
    v-if="tripType === 'point_to_point' && pointPurposeKind === 'extracurricular' && !replaceDraftRequestId"
    class="mt-4 space-y-3 rounded-xl border border-sky-100 bg-sky-50/60 p-3 text-sm text-slate-800"
  >
    <label class="flex cursor-pointer items-start gap-2">
      <input
        :checked="recurringEnabled"
        type="checkbox"
        class="mt-1 h-4 w-4 rounded border-slate-300 text-va-800"
        @change="$emit('update:recurringEnabled', $event.target.checked)"
      />
      <span>
        <span class="font-semibold">{{ t('dispatch_wizard.create.recurring_toggle') }}</span>
        <span class="mt-0.5 block text-xs text-slate-600">{{ t('dispatch_wizard.create.recurring_hint') }}</span>
      </span>
    </label>
    <label v-if="recurringEnabled" class="block text-xs font-medium text-slate-700">
      {{ t('dispatch_wizard.create.recurrence_end_label') }}
      <input
        :value="recurrenceEndDate"
        type="date"
        lang="vi"
        class="dw-input mt-1 max-w-xs"
        @input="$emit('update:recurrenceEndDate', $event.target.value)"
      />
    </label>
    <div
      v-if="recurringEnabled"
      class="mt-3 rounded-xl border border-sky-200 bg-white/90 px-3 py-2.5 text-xs text-slate-700 shadow-sm"
    >
      <p class="font-semibold text-slate-900">{{ t('dispatch_wizard.create.recurring_weekdays_preview_label') }}</p>
      <p v-if="!weekdayLabels.length" class="mt-1 text-slate-600">
        {{ t('dispatch_wizard.create.recurring_weekdays_empty') }}
      </p>
      <p v-else class="mt-1 font-medium text-slate-800">{{ weekdayLabels.join(', ') }}</p>
      <button
        type="button"
        class="mt-2 text-left text-sm font-semibold text-teal-700 underline decoration-teal-600/35 underline-offset-2 hover:text-teal-900"
        @click="$emit('goScheduleStep')"
      >
        {{ t('dispatch_wizard.create.recurring_go_schedule_step') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  tripType: { type: String, default: '' },
  pointPurposeKind: { type: String, default: '' },
  replaceDraftRequestId: { type: [Number, String], default: null },
  recurringEnabled: { type: Boolean, default: false },
  recurrenceEndDate: { type: String, default: '' },
  weekdayLabels: { type: Array, default: () => [] },
})

defineEmits(['update:recurringEnabled', 'update:recurrenceEndDate', 'goScheduleStep'])

const { t } = useI18n()
</script>
