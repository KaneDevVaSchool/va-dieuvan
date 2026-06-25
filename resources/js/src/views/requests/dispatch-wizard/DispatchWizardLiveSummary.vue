<template>
  <aside
    class="dw-live-summary"
    :aria-label="t('dispatch_wizard.create.summary_sidebar_aria')"
    data-testid="dispatch-wizard-live-summary"
  >
    <h2 class="dw-live-summary__title">{{ t('dispatch_wizard.create.summary_sidebar_title') }}</h2>
    <dl class="dw-live-summary__list">
      <div class="dw-live-summary__row">
        <dt>{{ t('dispatch_wizard.confirm.svc_type') }}</dt>
        <dd>{{ tripTypeLabel }}</dd>
      </div>
      <div class="dw-live-summary__row">
        <dt>{{ t('dispatch_wizard.create.summary_requester') }}</dt>
        <dd>{{ requesterDisplay }}</dd>
      </div>
      <div class="dw-live-summary__row">
        <dt>{{ t('dispatch_wizard.create.summary_depart_date') }}</dt>
        <dd>{{ departDateDisplay }}</dd>
      </div>
      <div class="dw-live-summary__row">
        <dt>{{ t('dispatch_wizard.create.summary_routes') }}</dt>
        <dd>{{ routeCount }}</dd>
      </div>
      <div v-if="showCost" class="dw-live-summary__row dw-live-summary__row--emphasis">
        <dt>{{ t('dispatch_wizard.create.summary_cost') }}</dt>
        <dd>{{ costDisplay }}</dd>
      </div>
    </dl>
  </aside>
</template>

<script setup>
import { computed, inject, unref } from 'vue'
import { useI18n } from 'vue-i18n'
import { DISPATCH_WIZARD_KEY } from './injectionKeys'

const { t } = useI18n()
const w = inject(DISPATCH_WIZARD_KEY)
if (!w) throw new Error('DispatchWizardLiveSummary: missing DISPATCH_WIZARD_KEY')

const { form, passengerRows, businessRows, cargoRows, isCargo, formatCurrency, passengerTotal, cargoTotal, extraCosts, isPortal } =
  w

const tripTypeLabel = computed(() => {
  const tt = form.value.trip_type
  if (!tt) return '—'
  return t(`dispatch_wizard.trip_type.${tt}.label`)
})

const requesterDisplay = computed(() => form.value.requester_name?.trim() || '—')

const departDateDisplay = computed(() => {
  const d = form.value.proposed_date || form.value.date_needed
  if (!d) return '—'
  const raw = String(d).slice(0, 10)
  const [y, m, day] = raw.split('-')
  if (!y || !m || !day) return raw
  return `${day}/${m}/${y}`
})

const routeCount = computed(() => {
  if (unref(isCargo)) return cargoRows.value.length
  if (form.value.trip_type === 'business') return businessRows.value.length
  return passengerRows.value.length
})

const showCost = computed(() => !unref(isPortal))

const costDisplay = computed(() => {
  const base = unref(isCargo) ? cargoTotal.value : passengerTotal.value
  const total = base + extraCosts.value
  if (!total) return '—'
  return formatCurrency(total)
})
</script>
