<template>
  <div
    class="dispatch-wizard mx-auto max-w-4xl space-y-6 px-4 py-6 text-slate-900 sm:px-6 lg:py-8"
  >
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">
          {{ t('portal.extracurricular_module.badge') }}
        </p>
        <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
          {{ t('portal.extracurricular_module.create_heading') }}
        </h1>
        <p class="mt-2 max-w-prose text-sm text-slate-600">
          {{ t('portal.recurring_plan.create_lead') }}
        </p>
      </div>
      <div class="flex flex-col items-stretch gap-2 sm:items-end">
        <div class="flex flex-wrap items-center justify-end gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50"
            @click="handleCancel"
          >
            {{ t('dispatch_wizard.create.cancel') }}
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm hover:bg-slate-50"
            @click="saveDraft"
          >
            {{ t('dispatch_wizard.create.save_draft') }}
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="headerPrimaryDisabled"
            @click="primaryAction"
          >
            <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white" />
            {{ loading ? t('dispatch_wizard.header.sending') : t('portal.recurring_plan.submit_create') }}
          </button>
        </div>
        <span v-if="draftSaveFlash" class="text-xs font-semibold text-emerald-700" role="status">
          {{ t('dispatch_wizard.create.draft_saved_flash') }}
        </span>
        <p v-if="draftSaveError" class="text-xs font-medium text-rose-600 sm:text-right">{{ draftSaveError }}</p>
        <ul
          v-if="stepBlockers.length && headerPrimaryDisabled"
          class="mt-1 max-w-md list-inside list-disc text-right text-xs font-medium text-amber-800 sm:ml-auto"
          role="status"
        >
          <li v-for="(msg, i) in stepBlockers" :key="i">{{ msg }}</li>
        </ul>
        <p v-if="error" class="text-xs font-medium text-rose-600 sm:text-right">{{ error }}</p>
      </div>
    </header>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
      <div class="space-y-8">
        <div>
          <h3 class="dw-section-title">{{ t('portal.extracurricular_create.sec_schedule') }}</h3>
          <RecurringSchedulePanel
            v-model:form="form"
            :weekday-options="e1WeekdayOptions"
            :preview-dates="occurrencePreview.dates"
            :preview-hint="previewHint"
            :format-date="formatIsoDateDisplay"
            @toggle-weekday="toggleWeekday"
            @preset="setWeekdayPreset"
          />
        </div>

        <div class="border-t border-slate-100 pt-8">
          <h3 class="dw-section-title">{{ t('portal.extracurricular_create.sec_route') }}</h3>
          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <label class="block sm:col-span-2">
              <span class="dw-label-text">
                {{ t('dispatch_wizard.s3.place') }} ({{ t('dispatch_wizard.s3.trip_out') }})
                <span class="dw-req" aria-hidden="true">*</span>
              </span>
              <input v-model="form.pickup" type="text" class="dw-input mt-1" :placeholder="t('dispatch_wizard.s3.pickup_ph')" />
            </label>
            <label class="block sm:col-span-2">
              <span class="dw-label-text">
                {{ t('dispatch_wizard.s3.place') }} ({{ t('dispatch_wizard.s3.trip_back') }})
                <span class="dw-req" aria-hidden="true">*</span>
              </span>
              <input v-model="form.dropoff" type="text" class="dw-input mt-1" :placeholder="t('dispatch_wizard.s3.dropoff_ph')" />
            </label>
            <label class="block sm:col-span-2">
              <span class="dw-label-text">{{ t('portal.recurring_plan.notes_optional') }}</span>
              <textarea v-model="form.notes" rows="3" class="dw-input mt-1 resize-y" />
            </label>
          </div>
        </div>
      </div>

      <div
        v-if="stepBlockers.length"
        class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950"
        role="status"
      >
        <p class="font-semibold">{{ t('portal.recurring_plan.blockers_title') }}</p>
        <ul class="mt-2 list-inside list-disc space-y-0.5 text-xs font-medium">
          <li v-for="(msg, i) in stepBlockers" :key="i">{{ msg }}</li>
        </ul>
      </div>

      <div class="mt-8 flex flex-col gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">
          {{
            formComplete
              ? t('portal.recurring_plan.ready_to_create')
              : t('portal.recurring_plan.complete_form_hint')
          }}
        </p>
        <button
          type="button"
          class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-va-800 px-6 text-sm font-bold text-white shadow-md hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-45 sm:w-auto"
          :disabled="headerPrimaryDisabled"
          @click="primaryAction"
        >
          <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white" />
          {{ loading ? t('dispatch_wizard.header.sending') : t('portal.recurring_plan.submit_create') }}
        </button>
      </div>
    </section>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import RecurringSchedulePanel from '../../components/portal/recurring/RecurringSchedulePanel.vue'
import { usePortalRecurringPlanCreate } from '../../composables/usePortalRecurringPlanCreate.js'

const { t } = useI18n()
const router = useRouter()

const {
  form,
  loading,
  error,
  draftSaveError,
  draftSaveFlash,
  e1WeekdayOptions,
  occurrencePreview,
  previewHint,
  formComplete,
  stepBlockers,
  headerPrimaryDisabled,
  toggleWeekday,
  setWeekdayPreset,
  primaryAction,
  saveDraft,
  formatIsoDateDisplay,
} = usePortalRecurringPlanCreate()

function handleCancel() {
  router.push({ name: 'portalExtracurricularList' })
}
</script>

<style src="../requests/dispatch-wizard/dispatchWizard.styles.css"></style>
