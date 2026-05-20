<template>
  <div class="space-y-5">
    <div class="grid gap-3 sm:grid-cols-2">
      <label class="block min-w-0">
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
        <span class="dw-label-text">{{ t('dispatch_wizard.create.recurrence_end_label') }}</span>
        <input
          v-model="form.recurrence_end_date"
          type="date"
          lang="vi"
          required
          :min="endDateMin"
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

    <section aria-labelledby="rp-weekdays-heading">
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
