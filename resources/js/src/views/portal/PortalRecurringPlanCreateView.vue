<template>
  <div
    class="dispatch-wizard mx-auto max-w-6xl space-y-6 px-4 py-6 text-slate-900 supports-[padding:max(0px)]:pl-[max(1rem,env(safe-area-inset-left))] supports-[padding:max(0px)]:pr-[max(1rem,env(safe-area-inset-right))] sm:px-6 lg:py-8"
    :class="step === 1 ? 'pb-28 sm:pb-24' : 'pb-10'"
  >
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">
          {{ t('portal.extracurricular_module.badge') }}
        </p>
        <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">
          {{ t('portal.extracurricular_module.create_heading') }}
        </h1>
      </div>
      <div class="flex flex-col items-stretch gap-2 sm:items-end">
        <div class="flex flex-wrap items-center justify-end gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
            @click="handleCancel"
          >
            {{ t('dispatch_wizard.create.cancel') }}
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50"
            @click="saveDraft"
          >
            {{ t('dispatch_wizard.create.save_draft') }}
          </button>
          <button
            v-if="step === 0"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="headerPrimaryDisabled"
            @click="primaryAction"
          >
            <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white" />
            {{ headerPrimaryLabel }}
            <ArrowRightIcon v-if="!loading" class="h-4 w-4" />
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

    <PortalStepper
      :steps="stepperSteps"
      :current="step"
      :steps-nav-label="t('portal.steps_nav')"
      interactive
      :max-reached-step="maxReachedStep"
      @select="goStepFromStepper"
    />

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8 lg:p-10">
      <!-- Step 0: form -->
      <div v-show="step === 0" class="space-y-8">
        <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
          <div class="dw-fieldset">
            <h3 class="dw-section-title">{{ t('portal.extracurricular_create.sec_schedule') }}</h3>
            <RecurringSchedulePanel
              v-model:form="form"
              :weekday-options="e1WeekdayOptions"
              :preview-dates="occurrencePreview.dates"
              :format-date="formatIsoDateDisplay"
              @toggle-weekday="toggleWeekday"
              @preset="setWeekdayPreset"
            />
          </div>

          <div class="dw-fieldset space-y-4">
            <h3 class="dw-section-title">{{ t('portal.extracurricular_create.sec_route') }}</h3>
            <p class="text-sm text-slate-600">{{ t('portal.extracurricular_create.route_lead') }}</p>
            <label class="block">
              <span class="dw-label-text">
                {{ t('dispatch_wizard.s3.place') }} ({{ t('dispatch_wizard.s3.trip_out') }})
                <span class="dw-req" aria-hidden="true">*</span>
              </span>
              <input v-model="form.pickup" type="text" class="dw-input mt-1" :placeholder="t('dispatch_wizard.s3.pickup_ph')" />
            </label>
            <label class="block">
              <span class="dw-label-text">
                {{ t('dispatch_wizard.s3.place') }} ({{ t('dispatch_wizard.s3.trip_back') }})
                <span class="dw-req" aria-hidden="true">*</span>
              </span>
              <input v-model="form.dropoff" type="text" class="dw-input mt-1" :placeholder="t('dispatch_wizard.s3.dropoff_ph')" />
            </label>
            <label class="block">
              <span class="dw-label-text">
                {{ t('dispatch_wizard.s3.guests') }}
                <span class="dw-req" aria-hidden="true">*</span>
              </span>
              <input
                v-model="form.planned_guests"
                type="number"
                min="1"
                inputmode="numeric"
                class="dw-input mt-1"
                :placeholder="t('dispatch_wizard.s3.guests_ph')"
              />
            </label>
          </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
          <div class="dw-fieldset space-y-4">
            <h3 class="dw-section-title">{{ t('dispatch_wizard.create.sec_requester') }}</h3>
            <div class="relative">
              <label class="dw-label">
                <span>{{ t('dispatch_wizard.create.search_by_name') }}</span>
              </label>
              <input
                v-model="requesterSearchQ"
                type="search"
                autocomplete="off"
                class="dw-input"
                :placeholder="t('dispatch_wizard.create.search_ph')"
                @input="scheduleRequesterSearch"
                @focus="requesterDropdownOpen = true"
              />
              <ul
                v-if="requesterDropdownOpen && requesterSearchQ.trim().length >= 2"
                class="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg"
              >
                <li v-for="u in requesterSearchResults" :key="u.id">
                  <button
                    type="button"
                    class="flex w-full flex-col px-3 py-2.5 text-left hover:bg-indigo-50"
                    @mousedown.prevent="pickRequester(u)"
                  >
                    <span class="font-medium">{{ u.name }}</span>
                    <span class="text-xs text-slate-500">{{ u.email }}</span>
                  </button>
                </li>
              </ul>
              <p v-if="requesterSearchError" class="mt-1 text-xs text-rose-600">{{ requesterSearchError }}</p>
            </div>
            <label class="block">
              <span class="dw-label-text">{{ t('dispatch_wizard.create.full_name') }} <span class="dw-req">*</span></span>
              <input v-model="form.requester_name" type="text" class="dw-input mt-1" />
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="block">
                  <span class="dw-label-text">{{ t('dispatch_wizard.create.requester_email_label') }} <span class="dw-req">*</span></span>
                  <input
                    v-model="form.requester_email"
                    type="email"
                    :class="['dw-input mt-1', step2RequesterEmailInvalid ? 'ring-1 ring-rose-300' : '']"
                    @blur="onRequesterEmailBlur"
                  />
                </label>
                <p v-if="step2RequesterEmailInvalid" class="mt-1 text-xs text-rose-600">
                  {{ t('dispatch_wizard.create.email_invalid') }}
                </p>
              </div>
              <label class="block">
                <span class="dw-label-text">{{ t('dispatch_wizard.create.phone') }}</span>
                <input
                  v-model="form.requester_phone"
                  type="text"
                  class="dw-input mt-1"
                  maxlength="11"
                  @input="onRequesterPhoneInput"
                />
              </label>
            </div>
            <label class="block">
              <span class="dw-label-text">{{ t('dispatch_wizard.create.unit') }}</span>
              <input v-model="form.requester_unit" type="text" class="dw-input mt-1" />
            </label>
          </div>

          <div class="dw-fieldset space-y-4">
            <h3 class="dw-section-title">{{ t('dispatch_wizard.create.sec_purpose') }}</h3>
            <label class="block">
              <span class="dw-label-text">{{ t('dispatch_wizard.create.purpose_label') }} <span class="dw-req">*</span></span>
              <textarea v-model="form.purpose" rows="4" class="dw-input mt-1 min-h-[5rem] resize-y" />
            </label>
            <div>
              <span class="dw-label-text">{{ t('dispatch_wizard.create.basis_label') }}</span>
              <div
                class="mt-2 flex min-h-[6rem] cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/80 px-4 py-5"
                @click="basisInput?.click()"
              >
                <p class="text-sm text-slate-600">{{ t('dispatch_wizard.create.basis_drop') }}</p>
                <input
                  ref="basisInput"
                  type="file"
                  class="sr-only"
                  accept=".pdf,.jpg,.jpeg,.png,image/*,application/pdf"
                  @change="onBasisChange"
                />
              </div>
              <p v-if="basisFileError" class="mt-1 text-xs text-rose-600">{{ basisFileError }}</p>
              <div v-if="basisFile" class="mt-2 flex items-center justify-between rounded-lg border px-3 py-2 text-sm">
                <span class="truncate">{{ basisFile.name }}</span>
                <button type="button" class="text-xs text-rose-700" @click="clearBasisFile">{{ t('dispatch_wizard.create.remove_file') }}</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 1: confirm -->
      <div v-show="step === 1" class="space-y-6">
        <h2 class="text-lg font-semibold text-slate-900">{{ t('dispatch_wizard.steps.confirm') }}</h2>

        <dl class="grid gap-4 sm:grid-cols-2">
          <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">
              {{ t('portal.extracurricular_create.sec_schedule') }}
            </dt>
            <dd class="mt-2 text-sm text-slate-900">
              {{
                form.recurrence_freq_mode === 'daily'
                  ? t('portal.recurring_plan.mode_daily')
                  : t('portal.recurring_plan.mode_weekly')
              }}
              ·
              {{ t('portal.recurring_plan.preview_count', { n: occurrencePreview.count }) }}
            </dd>
            <dd class="mt-1 text-sm text-slate-700">
              {{ formatIsoDateDisplay(form.recurrence_start_date) }}
              · {{ form.recurrence_depart_time?.slice(0, 5) }} – {{ form.recurrence_return_time?.slice(0, 5) }}
            </dd>
            <RecurringOccurrencePreview
              class="mt-3"
              :dates="occurrencePreview.dates"
              :format-date="formatIsoDateDisplay"
              :max-visible="12"
            />
          </div>

          <div class="rounded-xl border border-slate-200 p-4">
            <dt class="text-xs font-semibold uppercase text-slate-500">{{ t('portal.extracurricular_create.sec_route') }}</dt>
            <dd class="mt-2 text-sm text-slate-900">{{ form.pickup }} → {{ form.dropoff }}</dd>
            <dd class="mt-1 text-sm text-slate-600">
              {{ t('dispatch_wizard.s3.guests') }}: {{ form.planned_guests }}
            </dd>
          </div>

          <div class="rounded-xl border border-slate-200 p-4">
            <dt class="text-xs font-semibold uppercase text-slate-500">{{ t('dispatch_wizard.create.sec_requester') }}</dt>
            <dd class="mt-2 text-sm font-medium text-slate-900">{{ form.requester_name }}</dd>
            <dd class="text-sm text-slate-600">{{ form.requester_email }}</dd>
          </div>

          <div class="rounded-xl border border-slate-200 p-4 sm:col-span-2">
            <dt class="text-xs font-semibold uppercase text-slate-500">{{ t('dispatch_wizard.create.purpose_label') }}</dt>
            <dd class="mt-2 whitespace-pre-wrap text-sm text-slate-800">{{ form.purpose }}</dd>
          </div>
        </dl>

        <div
          v-if="step === 1"
          class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:static sm:border-0 sm:bg-transparent sm:p-0"
        >
          <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3">
            <button type="button" class="text-sm text-slate-600 hover:text-slate-900" @click="prevStep">
              {{ t('dispatch_wizard.create.back') }}
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-lg bg-va-800 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-va-900 disabled:opacity-50"
              :disabled="headerPrimaryDisabled"
              @click="primaryAction"
            >
              <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white" />
              {{ t('dispatch_wizard.header.submit') }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="step === 0" class="mt-8 flex justify-between border-t border-slate-200 pt-6">
        <span />
        <button
          type="button"
          class="rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40"
          :disabled="!canGoNext"
          @click="nextStep"
        >
          {{ t('dispatch_wizard.create.next') }}
        </button>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowRightIcon } from '@heroicons/vue/24/outline'
import PortalStepper from '../../components/portal/PortalStepper.vue'
import RecurringSchedulePanel from '../../components/portal/recurring/RecurringSchedulePanel.vue'
import RecurringOccurrencePreview from '../../components/portal/recurring/RecurringOccurrencePreview.vue'
import { usePortalRecurringPlanCreate } from '../../composables/usePortalRecurringPlanCreate.js'

const { t } = useI18n()
const router = useRouter()
const basisInput = ref(null)

const w = usePortalRecurringPlanCreate()

const {
  step,
  maxReachedStep,
  form,
  loading,
  error,
  created,
  basisFile,
  basisFileError,
  requesterSearchQ,
  requesterSearchResults,
  requesterSearchError,
  requesterDropdownOpen,
  draftSaveError,
  draftSaveFlash,
  e1WeekdayOptions,
  occurrencePreview,
  stepBlockers,
  canGoNext,
  headerPrimaryDisabled,
  stepperSteps,
  toggleWeekday,
  setWeekdayPreset,
  nextStep,
  prevStep,
  goStepFromStepper,
  primaryAction,
  saveDraft,
  scheduleRequesterSearch,
  pickRequester,
  onRequesterPhoneInput,
  onRequesterEmailBlur,
  step2RequesterEmailInvalid,
  setBasisFile,
  clearBasisFile,
  formatIsoDateDisplay,
} = w

const headerPrimaryLabel = computed(() => {
  if (loading.value) return t('dispatch_wizard.header.sending')
  if (step.value === 0) return t('dispatch_wizard.header.to_confirm')
  return t('dispatch_wizard.header.submit')
})

function handleCancel() {
  router.push({ name: 'portalExtracurricularList' })
}

function onBasisChange(e) {
  const file = e.target?.files?.[0]
  setBasisFile(file || null)
  if (e.target) e.target.value = ''
}
</script>

<style src="../requests/dispatch-wizard/dispatchWizard.styles.css"></style>
