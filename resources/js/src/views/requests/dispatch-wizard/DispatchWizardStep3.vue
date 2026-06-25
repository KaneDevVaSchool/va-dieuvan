<template>
  <div class="dw-step3-root">
    <div class="dw-step3-hero">
      <h2 class="text-lg font-semibold text-slate-900">
        {{ t('dispatch_wizard.s3.title') }}
      </h2>
    </div>

    <!-- Hành khách / door / p2p: e.1 -->
    <div v-if="!isCargo" class="dw-step3-stack">
      <section v-if="showE1Schedule" class="dw-step3-section" aria-labelledby="dw-e1-heading">
        <header class="dw-sec-intro">
          <h3 id="dw-e1-heading" class="dw-sec-intro__title">
            {{ t('dispatch_wizard.s3.e1_title') }}
          </h3>
          <p v-if="showRecurringClbDetail" class="mt-2 max-w-prose text-sm leading-relaxed text-slate-600">
            {{ t('portal.extracurricular_create.step_detail_lead') }}
          </p>
        </header>
        <DispatchStepDetails
          :model-value="passengerRows"
          variant="passenger"
          @update:valid="setSchedulePassengerValid"
        />
      </section>

      <section
        v-if="showPassengerTripExtras && !isBusinessTrip && !showRecurringClbDetail"
        class="dw-step3-section dw-e-panel dw-e-panel--e11"
        aria-labelledby="dw-e11-heading"
      >
        <header class="dw-e-panel__head">
          <div>
            <h3 id="dw-e11-heading" class="dw-e-panel__title">
              {{ t('dispatch_wizard.s3.e11_notes_title') }}
            </h3>
          </div>
        </header>

        <div class="dw-e11-flag">
          <label class="dw-e11-flag__row">
            <input v-model="form.e1_use_3plus_days" type="checkbox" class="dw-e11-flag__check" />
            <span class="dw-e11-flag__label">{{ t('dispatch_wizard.s3.e11_flag') }}</span>
          </label>
        </div>

        <div class="dw-e11-fields">
          <div class="dw-e11-field">
            <span class="dw-e11-field__label">
              {{ t('dispatch_wizard.s3.from_date') }}
              <span class="font-normal text-slate-400">{{ t('dispatch_wizard.s3.optional') }}</span>
            </span>
            <input
              v-model="form.e1_from_date"
              type="date"
              lang="vi"
              class="dw-input dw-input--e11 dw-date-input"
              :title="t('dispatch_wizard.s3.e1_period_from_title')"
              @click="openDatePickerFromInput($event)"
            />
          </div>
          <div class="dw-e11-field">
            <span class="dw-e11-field__label">
              {{ t('dispatch_wizard.s3.to_date') }}
              <span class="font-normal text-slate-400">{{ t('dispatch_wizard.s3.optional') }}</span>
            </span>
            <input
              v-model="form.e1_to_date"
              type="date"
              lang="vi"
              class="dw-input dw-input--e11 dw-date-input"
              :title="t('dispatch_wizard.s3.e1_period_to_title')"
              @click="openDatePickerFromInput($event)"
            />
          </div>
          <div class="dw-e11-field">
            <span class="dw-e11-field__label">{{ t('dispatch_wizard.s3.days_total') }}</span>
            <input
              v-model="form.e1_days_total"
              type="text"
              inputmode="numeric"
              class="dw-input dw-input--e11"
              :placeholder="t('dispatch_wizard.s3.days_ph')"
              :title="t('dispatch_wizard.s3.days_title')"
            />
          </div>
          <div class="dw-e11-field">
            <span class="dw-e11-field__label">{{ t('dispatch_wizard.s3.extra_cost') }}</span>
            <input
              :value="form.e1_extra_cost"
              type="text"
              inputmode="numeric"
              autocomplete="off"
              class="dw-input dw-input--e11 dw-cell--vnd"
              :placeholder="t('dispatch_wizard.s3.extra_cost_ph')"
              :title="t('dispatch_wizard.s3.extra_cost_title')"
              @input="vndForm('e1_extra_cost', $event)"
            />
          </div>
        </div>

        <div class="dw-e11-weekwrap">
          <p id="dw-e11-weekdays-label" class="dw-e11-weekwrap__title">
            {{ t('dispatch_wizard.s3.weekdays_title') }}
          </p>
          <div class="dw-weekday-strip" role="group" aria-labelledby="dw-e11-weekdays-label">
            <button
              v-for="wd in e1WeekdayOptions"
              :key="wd.k"
              type="button"
              class="dw-weekday-chip"
              :class="{ 'dw-weekday-chip--on': form.e1_weekdays[wd.k] }"
              role="checkbox"
              :aria-checked="!!form.e1_weekdays[wd.k]"
              @click="toggleE1Weekday(wd.k)"
            >
              {{ wd.label }}
            </button>
          </div>
        </div>
      </section>

      <!-- e.2 -->
      <section v-if="showE2Schedule" class="dw-step3-section" aria-labelledby="dw-e2-heading">
        <header class="dw-sec-intro">
          <h3 id="dw-e2-heading" class="dw-sec-intro__title">
            {{ t('dispatch_wizard.s3.e2_title') }}
          </h3>
        </header>
        <DispatchStepDetails
          :model-value="businessRows"
          variant="business"
          @update:valid="setScheduleBusinessValid"
        />
      </section>

      <section
        v-if="showPassengerTripExtras"
        class="dw-step3-section dw-e-panel dw-e-panel--e21"
        aria-labelledby="dw-e21-heading"
      >
        <header class="dw-e-panel__head">
          <div>
            <h3 id="dw-e21-heading" class="dw-e-panel__title">
              {{ t('dispatch_wizard.s3.e21_title') }}
            </h3>
          </div>
        </header>
        <div class="dw-e21-rows">
          <div class="dw-e21-row">
            <label class="dw-e21-row__opt">
              <input v-model="form.e2_door_pickup" type="checkbox" class="dw-e21-row__check" />
              <span class="dw-e21-row__label">{{ t('dispatch_wizard.s3.door_pickup') }}</span>
            </label>
            <div class="dw-e21-row__cost">
              <span class="dw-e21-row__cost-label">{{ t('dispatch_wizard.s3.door_cost_lbl') }}</span>
              <input
                :value="form.e2_door_cost"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                :placeholder="t('dispatch_wizard.s3.vnd_ph_small')"
                class="dw-e21-row__input dw-cell--vnd"
                :title="t('dispatch_wizard.s3.door_cost_title')"
                @input="vndForm('e2_door_cost', $event)"
              />
              <span class="dw-e21-row__unit">{{ t('dispatch_wizard.s3.vnd') }}</span>
            </div>
          </div>
          <div class="dw-e21-row">
            <label class="dw-e21-row__opt">
              <input v-model="form.e2_driver_self" type="checkbox" class="dw-e21-row__check" />
              <span class="dw-e21-row__label">{{ t('dispatch_wizard.s3.driver_self') }}</span>
            </label>
            <div class="dw-e21-row__cost">
              <span class="dw-e21-row__cost-label">{{ t('dispatch_wizard.s3.door_cost_lbl') }}</span>
              <input
                :value="form.e2_driver_self_cost"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                :placeholder="t('dispatch_wizard.s3.vnd_ph')"
                class="dw-e21-row__input dw-cell--vnd"
                :title="t('dispatch_wizard.s3.driver_cost_title')"
                @input="vndForm('e2_driver_self_cost', $event)"
              />
              <span class="dw-e21-row__unit">{{ t('dispatch_wizard.s3.vnd') }}</span>
            </div>
          </div>
          <div class="dw-e21-row">
            <label class="dw-e21-row__opt">
              <input v-model="form.e2_after_21h" type="checkbox" class="dw-e21-row__check" />
              <span class="dw-e21-row__label">{{ t('dispatch_wizard.s3.after21') }}</span>
            </label>
            <div class="dw-e21-row__cost">
              <span class="dw-e21-row__cost-label">{{ t('dispatch_wizard.s3.door_cost_lbl') }}</span>
              <input
                :value="form.e2_after_21h_cost"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                :placeholder="t('dispatch_wizard.s3.vnd_ph_large')"
                class="dw-e21-row__input dw-cell--vnd"
                :title="t('dispatch_wizard.s3.after21_title')"
                @input="vndForm('e2_after_21h_cost', $event)"
              />
              <span class="dw-e21-row__unit">{{ t('dispatch_wizard.s3.vnd') }}</span>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- Cargo: phát sinh compact -->
    <div v-else class="dw-step3-stack">
      <article class="dw-step3-section">
        <header class="dw-sec-intro">
          <h3 class="dw-sec-intro__title">{{ t('dispatch_wizard.s3.cargo_title') }}</h3>
        </header>
        <DispatchStepDetails
          :model-value="cargoRows"
          variant="cargo"
          @update:valid="setScheduleCargoValid"
        />
      </article>

      <section class="dw-step3-section px-4 py-3 sm:px-4 sm:py-4" aria-labelledby="dw-cargo-surcharge-heading">
        <h3 id="dw-cargo-surcharge-heading" class="dw-sec-intro__title mb-3">
          {{ t('dispatch_wizard.s3.cargo_panel') }}
        </h3>
        <label class="mb-3 block">
          <span class="mb-1 block text-xs font-medium text-slate-600">
            {{ t('dispatch_wizard.s3.cargo_notes_lbl') }}
            <span class="font-normal text-slate-400">{{ t('dispatch_wizard.s3.optional') }}</span>
          </span>
          <textarea
            v-model="form.cargo_extra_notes"
            rows="2"
            class="dw-input min-h-[2.5rem] resize-y"
            :placeholder="t('dispatch_wizard.s3.cargo_notes_ph')"
          />
        </label>
        <table class="dw-surcharge-table">
          <thead>
            <tr>
              <th class="w-[40%]">{{ t('dispatch_wizard.s3.surcharge_col') }}</th>
              <th>{{ t('dispatch_wizard.s3.surcharge_detail') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <label class="flex cursor-pointer items-center gap-2">
                  <input
                    v-model="form.need_porters"
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-300 text-va-800"
                  />
                  <span class="text-sm text-slate-800">{{ t('dispatch_wizard.s3.need_porters') }}</span>
                </label>
              </td>
              <td>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="text-xs text-slate-500">{{ t('dispatch_wizard.s3.qty_lbl') }}</span>
                  <input
                    v-model="form.porter_qty"
                    type="text"
                    :placeholder="t('dispatch_wizard.s3.porter_qty_ph')"
                    class="dw-cell dw-cell--e21 min-w-[5rem] max-w-[7rem]"
                    :disabled="!form.need_porters"
                  />
                  <span class="text-xs text-slate-500">{{ t('dispatch_wizard.s3.cost_vnd') }}</span>
                  <input
                    :value="form.porter_cost"
                    type="text"
                    inputmode="numeric"
                    autocomplete="off"
                    :placeholder="t('dispatch_wizard.s3.vnd_ph')"
                    class="dw-cell dw-cell--e21 dw-cell--vnd min-w-[8rem] flex-1 sm:max-w-[12rem]"
                    :disabled="!form.need_porters"
                    @input="vndForm('porter_cost', $event)"
                  />
                </div>
              </td>
            </tr>
            <tr>
              <td>
                <label class="flex cursor-pointer items-center gap-2">
                  <input
                    v-model="form.interprovincial"
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-300 text-va-800"
                  />
                  <span class="text-sm text-slate-800">{{ t('dispatch_wizard.s3.interprov') }}</span>
                </label>
              </td>
              <td>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="text-xs text-slate-500">{{ t('dispatch_wizard.s3.interprov_cost') }}</span>
                  <input
                    :value="form.interprovincial_cost"
                    type="text"
                    inputmode="numeric"
                    autocomplete="off"
                    :placeholder="t('dispatch_wizard.s3.vnd_ph_small')"
                    class="dw-cell dw-cell--e21 dw-cell--vnd min-w-[8rem] flex-1 sm:max-w-[14rem]"
                    :disabled="!form.interprovincial"
                    @input="vndForm('interprovincial_cost', $event)"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, inject, ref, unref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatVndWhileTyping } from '../../../util/money'
import DispatchStepDetails from './DispatchStepDetails.vue'
import { DISPATCH_WIZARD_KEY } from './injectionKeys'

const { t } = useI18n()
const w = inject(DISPATCH_WIZARD_KEY)
if (!w) throw new Error('DispatchWizardStep3: missing DISPATCH_WIZARD_KEY provider')

const {
  passengerRows,
  businessRows,
  cargoRows,
  form,
  e1WeekdayOptions,
  openDatePickerFromInput,
  toggleE1Weekday,
  detailStepSchedulesValid,
} = w

const schedulePassengerValid = ref(true)
const scheduleBusinessValid = ref(true)
const scheduleCargoValid = ref(true)

function setSchedulePassengerValid(v) {
  schedulePassengerValid.value = v
}
function setScheduleBusinessValid(v) {
  scheduleBusinessValid.value = v
}
function setScheduleCargoValid(v) {
  scheduleCargoValid.value = v
}

const isCargo = computed(() => unref(w.isCargo))
const isPointToPointTrip = computed(() => unref(w.isPointToPointTrip))
const isBusinessTrip = computed(() => form.value.trip_type === 'business')

const showE1Schedule = computed(() => !isBusinessTrip.value)
const showE2Schedule = computed(() => !isPointToPointTrip.value)

const isP2PExtracurricular = computed(
  () => isPointToPointTrip.value && form.value.point_purpose_kind === 'extracurricular',
)

const showPassengerTripExtras = computed(() => !isPointToPointTrip.value || isP2PExtracurricular.value)

const showRecurringClbDetail = computed(() => !!unref(w.wantsRecurringTemplate))

const combinedScheduleValid = computed(() => {
  if (isCargo.value) return scheduleCargoValid.value
  if (isBusinessTrip.value) return scheduleBusinessValid.value
  if (isPointToPointTrip.value) return schedulePassengerValid.value
  return schedulePassengerValid.value && scheduleBusinessValid.value
})

watch(
  combinedScheduleValid,
  (v) => {
    detailStepSchedulesValid.value = v
  },
  { immediate: true },
)

function vndForm(key, e) {
  form.value[key] = formatVndWhileTyping(e.target.value)
}
</script>
