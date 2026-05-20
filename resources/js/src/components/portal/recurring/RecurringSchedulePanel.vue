<template>
  <div class="space-y-5">
    <div
      class="flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-slate-50/80 p-1"
      role="tablist"
      :aria-label="t('portal.recurring_plan.mode_aria')"
    >
      <button
        type="button"
        role="tab"
        class="min-h-[44px] flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition sm:flex-none sm:px-4"
        :class="
          form.recurrence_freq_mode === 'daily'
            ? 'bg-white text-indigo-800 shadow-sm ring-1 ring-indigo-200'
            : 'text-slate-600 hover:text-slate-900'
        "
        :aria-selected="form.recurrence_freq_mode === 'daily'"
        @click="form.recurrence_freq_mode = 'daily'"
      >
        {{ t('portal.recurring_plan.mode_daily') }}
      </button>
      <button
        type="button"
        role="tab"
        class="min-h-[44px] flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition sm:flex-none sm:px-4"
        :class="
          form.recurrence_freq_mode === 'weekly'
            ? 'bg-white text-indigo-800 shadow-sm ring-1 ring-indigo-200'
            : 'text-slate-600 hover:text-slate-900'
        "
        :aria-selected="form.recurrence_freq_mode === 'weekly'"
        @click="form.recurrence_freq_mode = 'weekly'"
      >
        {{ t('portal.recurring_plan.mode_weekly') }}
      </button>
    </div>

    <p v-if="form.recurrence_freq_mode === 'daily'" class="text-xs text-slate-600">
      {{ t('portal.recurring_plan.mode_daily_hint') }}
    </p>

    <div class="grid gap-3 sm:grid-cols-2">
      <label class="block min-w-0 sm:col-span-2">
        <span class="dw-label-text">{{ t('dispatch_wizard.create.recurrence_start_label') }}</span>
        <input
          v-model="form.recurrence_start_date"
          type="date"
          lang="vi"
          required
          :min="todayStr"
          class="dw-input mt-1 min-h-[2.75rem] w-full"
        />
      </label>
      <label class="block min-w-0">
        <span class="dw-label-text">{{ t('dispatch_wizard.create.recurrence_depart_time_label') }}</span>
        <input v-model="form.recurrence_depart_time" type="time" required class="dw-input mt-1 min-h-[2.75rem] w-full" />
      </label>
      <label class="block min-w-0">
        <span class="dw-label-text">{{ t('dispatch_wizard.create.recurrence_return_time_label') }}</span>
        <input v-model="form.recurrence_return_time" type="time" required class="dw-input mt-1 min-h-[2.75rem] w-full" />
      </label>
    </div>

    <section v-if="form.recurrence_freq_mode === 'weekly'" aria-labelledby="rp-weekdays-heading">
      <div class="flex flex-wrap items-end justify-between gap-2">
        <h4 id="rp-weekdays-heading" class="text-sm font-semibold text-slate-900">
          {{ t('dispatch_wizard.create.recurring_weekdays_preview_label') }}
        </h4>
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            class="text-xs font-medium text-teal-800 hover:text-teal-950"
            @click="$emit('preset', 'weekdays')"
          >
            {{ t('portal.recurring_plan.preset_weekdays') }}
          </button>
          <button
            type="button"
            class="text-xs font-medium text-teal-800 hover:text-teal-950"
            @click="$emit('preset', 'all')"
          >
            {{ t('portal.recurring_plan.preset_all') }}
          </button>
        </div>
      </div>
      <div class="dw-weekday-strip mt-3" role="group" :aria-labelledby="'rp-weekdays-heading'">
        <button
          v-for="wd in weekdayOptions"
          :key="wd.k"
          type="button"
          class="dw-weekday-chip"
          :class="{ 'dw-weekday-chip--on': form.e1_weekdays?.[wd.k] }"
          role="checkbox"
          :aria-checked="!!form.e1_weekdays?.[wd.k]"
          @click="$emit('toggle-weekday', wd.k)"
        >
          {{ wd.label }}
        </button>
      </div>
    </section>

    <fieldset class="space-y-3">
      <legend class="text-sm font-semibold text-slate-900">
        {{ t('dispatch_wizard.create.recurrence_stop_legend') }}
      </legend>
      <label
        class="flex cursor-pointer flex-col gap-2 rounded-xl border px-3 py-3 transition sm:flex-row sm:items-start sm:gap-3"
        :class="
          form.recurrence_end_mode === 'date'
            ? 'border-sky-400 bg-sky-50/80 ring-1 ring-sky-200'
            : 'border-slate-200 bg-slate-50/50 hover:border-slate-300'
        "
      >
        <span class="flex items-center gap-2 text-sm font-medium text-slate-800">
          <input
            v-model="form.recurrence_end_mode"
            type="radio"
            name="portal_rp_end_mode"
            value="date"
            class="h-4 w-4 border-slate-300 text-va-800"
          />
          {{ t('dispatch_wizard.create.recurrence_stop_by_date') }}
        </span>
        <label v-if="form.recurrence_end_mode === 'date'" class="block min-w-0 flex-1 pl-6 sm:pl-0">
          <span class="dw-label-text">{{ t('dispatch_wizard.create.recurrence_end_label') }}</span>
          <input
            v-model="form.recurrence_end_date"
            type="date"
            lang="vi"
            :min="endDateMin"
            class="dw-input mt-1 min-h-[2.75rem] w-full max-w-md"
          />
        </label>
      </label>

      <label
        class="flex cursor-pointer flex-col gap-2 rounded-xl border px-3 py-3 transition sm:flex-row sm:items-start sm:gap-3"
        :class="
          form.recurrence_end_mode === 'weeks'
            ? 'border-sky-400 bg-sky-50/80 ring-1 ring-sky-200'
            : 'border-slate-200 bg-slate-50/50 hover:border-slate-300'
        "
      >
        <span class="flex items-center gap-2 text-sm font-medium text-slate-800">
          <input
            v-model="form.recurrence_end_mode"
            type="radio"
            name="portal_rp_end_mode"
            value="weeks"
            class="h-4 w-4 border-slate-300 text-va-800"
          />
          {{ t('dispatch_wizard.create.recurrence_stop_by_weeks') }}
        </span>
        <label v-if="form.recurrence_end_mode === 'weeks'" class="block min-w-0 flex-1 pl-6 sm:pl-0">
          <span class="dw-label-text">{{ t('dispatch_wizard.create.recurrence_repeat_count_label') }}</span>
          <input
            v-model="form.recurrence_repeat_count"
            type="number"
            min="1"
            max="520"
            class="dw-input mt-1 min-h-[2.75rem] w-full max-w-[10rem]"
          />
        </label>
      </label>
    </fieldset>

    <RecurringOccurrencePreview :dates="previewDates" :format-date="formatDate" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import RecurringOccurrencePreview from './RecurringOccurrencePreview.vue'

const form = defineModel('form', { type: Object, required: true })

defineProps({
  weekdayOptions: { type: Array, default: () => [] },
  previewDates: { type: Array, default: () => [] },
  formatDate: { type: Function, required: true },
})

defineEmits(['toggle-weekday', 'preset'])

const { t } = useI18n()

const todayStr = new Date().toISOString().slice(0, 10)

const endDateMin = computed(() => form.value.recurrence_start_date?.trim() || todayStr)
</script>
