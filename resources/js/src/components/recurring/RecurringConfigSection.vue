<template>
  <div
    v-if="tripType === 'point_to_point' && pointPurposeKind === 'extracurricular' && !replaceDraftRequestId"
    :class="
      fixedEnabled
        ? 'text-sm text-slate-800'
        : 'mt-4 rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-800 shadow-sm sm:p-5'
    "
  >
    <p v-if="fixedEnabled" class="sr-only">
      {{ t('dispatch_wizard.create.recurring_schedule_heading') }}
    </p>
    <label
      v-else
      class="flex min-h-[44px] w-full cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-3 py-2.5 transition has-[:checked]:border-sky-500 has-[:checked]:bg-sky-50 has-[:checked]:ring-1 has-[:checked]:ring-sky-200"
    >
      <input
        :checked="recurringEnabled"
        type="checkbox"
        class="h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-800"
        @change="$emit('update:recurringEnabled', $event.target.checked)"
      />
      <span class="text-sm font-semibold text-slate-900">{{ t('dispatch_wizard.create.recurring_toggle') }}</span>
    </label>

    <div v-if="recurringPanelOpen" :class="fixedEnabled ? 'mt-0 space-y-5' : 'mt-5 space-y-5'">
      <section aria-labelledby="recurring-times-heading">
        <h4 id="recurring-times-heading" class="text-sm font-semibold text-slate-900">
          {{ t('dispatch_wizard.create.recurrence_times_legend') }}
        </h4>
        <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <label class="block min-w-0">
            <span class="dw-label-text">{{ t('dispatch_wizard.create.recurrence_start_label') }}</span>
            <input
              :value="startDate"
              type="date"
              lang="vi"
              required
              :min="todayStr"
              class="dw-input mt-1 min-h-[2.75rem] w-full"
              @input="$emit('update:startDate', $event.target.value)"
            />
          </label>
          <label class="block min-w-0">
            <span class="dw-label-text">{{ t('dispatch_wizard.create.recurrence_depart_time_label') }}</span>
            <input
              :value="departTime"
              type="time"
              required
              class="dw-input mt-1 min-h-[2.75rem] w-full"
              @input="$emit('update:departTime', $event.target.value)"
            />
          </label>
          <label class="block min-w-0">
            <span class="dw-label-text">{{ t('dispatch_wizard.create.recurrence_return_time_label') }}</span>
            <input
              :value="returnTime"
              type="time"
              required
              class="dw-input mt-1 min-h-[2.75rem] w-full"
              @input="$emit('update:returnTime', $event.target.value)"
            />
          </label>
        </div>
      </section>

      <section aria-labelledby="recurring-weekdays-heading">
        <div class="flex flex-wrap items-end justify-between gap-2">
          <div>
            <h4 id="recurring-weekdays-heading" class="text-sm font-semibold text-slate-900">
              {{ t('dispatch_wizard.create.recurring_weekdays_preview_label') }}
            </h4>
            <p v-if="fixedEnabled" class="mt-1 text-xs text-slate-600">
              {{ t('portal.extracurricular_create.weekdays_required_hint') }}
            </p>
          </div>
          <button
            v-if="!fixedEnabled"
            type="button"
            class="inline-flex items-center gap-1 text-sm font-medium text-teal-800 hover:text-teal-950"
            @click="$emit('goScheduleStep')"
          >
            {{ t('dispatch_wizard.create.recurring_edit_schedule') }}
            <span aria-hidden="true">→</span>
          </button>
        </div>
        <div
          class="dw-weekday-strip mt-3"
          role="group"
          :aria-labelledby="'recurring-weekdays-heading'"
        >
          <button
            v-for="wd in weekdayOptions"
            :key="wd.k"
            type="button"
            class="dw-weekday-chip"
            :class="{ 'dw-weekday-chip--on': weekdays?.[wd.k] }"
            role="checkbox"
            :aria-checked="!!weekdays?.[wd.k]"
            @click="$emit('toggleWeekday', wd.k)"
          >
            {{ wd.label }}
          </button>
        </div>
      </section>

      <fieldset class="space-y-3">
        <legend class="text-sm font-semibold text-slate-900">
          {{ t('dispatch_wizard.create.recurrence_stop_legend') }}
        </legend>
        <div class="space-y-2">
          <label
            class="flex cursor-pointer flex-col gap-2 rounded-xl border px-3 py-3 transition sm:flex-row sm:items-start sm:gap-3"
            :class="
              recurrenceEndMode === 'date'
                ? 'border-sky-400 bg-sky-50/80 ring-1 ring-sky-200'
                : 'border-slate-200 bg-slate-50/50 hover:border-slate-300'
            "
          >
            <span class="flex items-center gap-2 text-sm font-medium text-slate-800">
              <input
                type="radio"
                :name="endModeRadioName"
                value="date"
                :checked="recurrenceEndMode === 'date'"
                class="h-4 w-4 border-slate-300 text-va-800"
                @change="$emit('update:recurrenceEndMode', 'date')"
              />
              {{ t('dispatch_wizard.create.recurrence_stop_by_date') }}
            </span>
            <label
              v-if="recurrenceEndMode === 'date'"
              class="block min-w-0 flex-1 pl-6 sm:pl-0"
            >
              <span class="dw-label-text">{{ t('dispatch_wizard.create.recurrence_end_label') }}</span>
              <input
                :value="recurrenceEndDate"
                type="date"
                lang="vi"
                :min="endDateMin"
                class="dw-input mt-1 min-h-[2.75rem] w-full max-w-md"
                @input="$emit('update:recurrenceEndDate', $event.target.value)"
              />
            </label>
          </label>

          <label
            class="flex cursor-pointer flex-col gap-2 rounded-xl border px-3 py-3 transition sm:flex-row sm:items-start sm:gap-3"
            :class="
              recurrenceEndMode === 'weeks'
                ? 'border-sky-400 bg-sky-50/80 ring-1 ring-sky-200'
                : 'border-slate-200 bg-slate-50/50 hover:border-slate-300'
            "
          >
            <span class="flex items-center gap-2 text-sm font-medium text-slate-800">
              <input
                type="radio"
                :name="endModeRadioName"
                value="weeks"
                :checked="recurrenceEndMode === 'weeks'"
                class="h-4 w-4 border-slate-300 text-va-800"
                @change="$emit('update:recurrenceEndMode', 'weeks')"
              />
              {{ t('dispatch_wizard.create.recurrence_stop_by_weeks') }}
            </span>
            <label
              v-if="recurrenceEndMode === 'weeks'"
              class="block min-w-0 flex-1 pl-6 sm:pl-0"
            >
              <span class="dw-label-text">{{ t('dispatch_wizard.create.recurrence_repeat_count_label') }}</span>
              <input
                :value="repeatCount"
                type="number"
                min="1"
                max="520"
                class="dw-input mt-1 min-h-[2.75rem] w-full max-w-[10rem]"
                @input="$emit('update:repeatCount', $event.target.value)"
              />
            </label>
          </label>
        </div>
      </fieldset>
    </div>
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
  departTime: { type: String, default: '' },
  returnTime: { type: String, default: '' },
  recurrenceEndDate: { type: String, default: '' },
  recurrenceEndMode: { type: String, default: 'date' },
  repeatCount: { type: [String, Number], default: '' },
  weekdayOptions: { type: Array, default: () => [] },
  weekdays: { type: Object, default: () => ({}) },
  endModeRadioName: { type: String, default: 'recurrence_end_mode' },
  fixedEnabled: { type: Boolean, default: false },
})

const recurringPanelOpen = computed(() => props.fixedEnabled || props.recurringEnabled)

defineEmits([
  'update:recurringEnabled',
  'update:startDate',
  'update:departTime',
  'update:returnTime',
  'update:recurrenceEndDate',
  'update:recurrenceEndMode',
  'update:repeatCount',
  'goScheduleStep',
  'toggleWeekday',
])

const { t } = useI18n()

const todayStr = new Date().toISOString().slice(0, 10)

const endDateMin = computed(() => {
  const s = props.startDate?.trim()
  return s || todayStr
})
</script>
