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
    <template v-if="recurringEnabled">
      <div class="grid gap-3 sm:grid-cols-2">
        <label class="block text-xs font-medium text-slate-700">
          {{ t('dispatch_wizard.create.recurrence_start_label') }}
          <input
            :value="startDate"
            type="date"
            lang="vi"
            required
            :min="todayStr"
            class="dw-input mt-1 w-full"
            @input="$emit('update:startDate', $event.target.value)"
          />
        </label>
        <label class="block text-xs font-medium text-slate-700">
          {{ t('dispatch_wizard.create.recurrence_return_time_label') }}
          <input
            :value="returnTime"
            type="time"
            required
            class="dw-input mt-1 w-full"
            @input="$emit('update:returnTime', $event.target.value)"
          />
        </label>
      </div>
      <div
        class="rounded-xl border border-sky-200 bg-white/90 px-3 py-2.5 text-xs text-slate-700 shadow-sm"
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
      <fieldset class="space-y-2">
        <legend class="text-xs font-medium text-slate-700">{{ t('dispatch_wizard.create.recurrence_stop_legend') }}</legend>
        <label class="flex cursor-pointer items-center gap-2 text-xs">
          <input
            type="radio"
            name="recurrence_end_mode"
            value="date"
            :checked="recurrenceEndMode === 'date'"
            class="h-4 w-4 border-slate-300 text-va-800"
            @change="$emit('update:recurrenceEndMode', 'date')"
          />
          {{ t('dispatch_wizard.create.recurrence_stop_by_date') }}
        </label>
        <label v-if="recurrenceEndMode === 'date'" class="block text-xs font-medium text-slate-700">
          {{ t('dispatch_wizard.create.recurrence_end_label') }}
          <input
            :value="recurrenceEndDate"
            type="date"
            lang="vi"
            :min="endDateMin"
            class="dw-input mt-1 max-w-xs"
            @input="$emit('update:recurrenceEndDate', $event.target.value)"
          />
        </label>
        <label class="flex cursor-pointer items-center gap-2 text-xs">
          <input
            type="radio"
            name="recurrence_end_mode"
            value="weeks"
            :checked="recurrenceEndMode === 'weeks'"
            class="h-4 w-4 border-slate-300 text-va-800"
            @change="$emit('update:recurrenceEndMode', 'weeks')"
          />
          {{ t('dispatch_wizard.create.recurrence_stop_by_weeks') }}
        </label>
        <label v-if="recurrenceEndMode === 'weeks'" class="block text-xs font-medium text-slate-700">
          {{ t('dispatch_wizard.create.recurrence_repeat_count_label') }}
          <input
            :value="repeatCount"
            type="number"
            min="1"
            max="520"
            class="dw-input mt-1 max-w-[8rem]"
            @input="$emit('update:repeatCount', $event.target.value)"
          />
        </label>
      </fieldset>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  tripType: { type: String, default: '' },
  pointPurposeKind: { type: String, default: '' },
  replaceDraftRequestId: { type: [Number, String], default: null },
  recurringEnabled: { type: Boolean, default: false },
  startDate: { type: String, default: '' },
  returnTime: { type: String, default: '' },
  recurrenceEndDate: { type: String, default: '' },
  recurrenceEndMode: { type: String, default: 'date' },
  repeatCount: { type: [String, Number], default: '' },
  weekdayLabels: { type: Array, default: () => [] },
})

defineEmits([
  'update:recurringEnabled',
  'update:startDate',
  'update:returnTime',
  'update:recurrenceEndDate',
  'update:recurrenceEndMode',
  'update:repeatCount',
  'goScheduleStep',
])

const { t } = useI18n()

const todayStr = new Date().toISOString().slice(0, 10)

const endDateMin = computed(() => {
  const s = props.startDate?.trim()
  return s || todayStr
})
</script>
