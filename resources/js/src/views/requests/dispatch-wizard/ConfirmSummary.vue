<template>
  <div class="mx-auto max-w-5xl rounded-2xl bg-white px-5 py-7 shadow-sm shadow-slate-900/[0.05] sm:px-8 sm:py-9">
    <div class="flex flex-col gap-1 pb-5 sm:pb-6">
      <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">
        {{ t('dispatch_wizard.steps.confirm') }}
      </p>
      <h2 class="text-xl font-semibold tracking-tight text-slate-900">
        {{ t('dispatch_wizard.create.step4_title') }}
      </h2>
      <p class="max-w-prose text-xs leading-relaxed text-slate-600">
        {{
          t('dispatch_wizard.create.step4_lead', {
            submit: t('dispatch_wizard.confirm.submit_primary'),
            draft: t('dispatch_wizard.create.save_draft'),
          })
        }}
      </p>
    </div>

    <!-- Success -->
    <div v-if="created" class="mt-6 space-y-5">
      <div class="rounded-xl bg-slate-50/90 px-5 py-5 sm:flex sm:items-start sm:justify-between sm:gap-6">
        <div class="min-w-0 space-y-2">
          <p class="text-sm font-semibold text-slate-900">
            {{ t('dispatch_wizard.confirm.success_meta', { id: created.id }) }}
          </p>
          <span
            class="inline-flex w-fit items-center rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-slate-800 shadow-sm shadow-slate-900/[0.04]"
          >
            {{ requestStatusLabel }}
          </span>
          <p v-if="error" class="text-xs font-medium text-rose-700">{{ error }}</p>
        </div>
        <div class="mt-4 shrink-0 sm:mt-0 sm:text-right">
          <RouterLink
            v-if="!isPortal"
            :to="staffPath('/requests')"
            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-neutral-900"
          >
            {{ t('dispatch_wizard.confirm.view_list') }}
          </RouterLink>
          <RouterLink
            v-else
            :to="{ name: 'portalRequestList' }"
            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-neutral-900"
          >
            {{ t('portal.confirm.view_my_requests') }}
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Review (pre-submit) -->
    <template v-else>
      <ValidationAlert
        class="mt-6"
        alert-id="confirm-validation-alert"
        :title="t('dispatch_wizard.confirm.validation_title')"
        :lead="confirmValidationGroups.length ? t('dispatch_wizard.confirm.validation_lead') : ''"
        :groups="confirmValidationGroups"
        :messages="confirmValidationGroups.length ? [] : confirmReviewIssues"
      />

      <div
        v-if="error && !confirmReviewIssues.length"
        class="mt-4 rounded-xl bg-rose-50 px-4 py-3 text-xs font-medium text-rose-900"
      >
        {{ error }}
      </div>

      <div class="mt-6 grid grid-cols-1 gap-4 sm:gap-5 lg:grid-cols-2 lg:items-stretch">
        <SummarySection
          :title="t('dispatch_wizard.confirm.sec_general')"
          :default-open="true"
          :has-issues="confirmSectionHasIssues('general')"
          :issue-hint="t('dispatch_wizard.confirm.section_issue_hint')"
        >
          <dl class="divide-y divide-slate-200/70">
            <div class="summary-field">
              <dt>{{ t('dispatch_wizard.confirm.svc_type') }}</dt>
              <dd>{{ tripTypeLabel }}</dd>
            </div>
            <div class="summary-field">
              <dt>{{ t('dispatch_wizard.confirm.requester') }}</dt>
              <dd>{{ form.requester_name?.trim() || '—' }}</dd>
            </div>
            <div class="summary-field">
              <dt>{{ t('dispatch_wizard.confirm.email_phone') }}</dt>
              <dd class="break-words">
                {{ form.requester_email?.trim() || '—' }}
                <span v-if="form.requester_phone?.trim()" class="text-slate-600">
                  · {{ form.requester_phone }}</span>
              </dd>
            </div>
            <div class="summary-field">
              <dt>{{ t('dispatch_wizard.confirm.usage_dates') }}</dt>
              <dd>{{ usageDatesDisplay }}</dd>
            </div>
            <div class="summary-field">
              <dt>{{ t('dispatch_wizard.confirm.urgency') }}</dt>
              <dd>{{ urgencyLabel }}</dd>
            </div>
            <div v-if="form.requester_unit?.trim()" class="summary-field">
              <dt>{{ t('dispatch_wizard.create.unit') }}</dt>
              <dd>{{ form.requester_unit }}</dd>
            </div>
            <div
              v-if="form.coordinator_name?.trim() || form.coordinator_email?.trim() || form.coordinator_phone?.trim()"
              class="summary-field"
            >
              <dt>{{ t('dispatch_wizard.confirm.coord_block') }}</dt>
              <dd>
                {{ form.coordinator_name?.trim() || '—' }}
                <span v-if="form.coordinator_email?.trim()" class="block text-slate-600">{{ form.coordinator_email }}</span>
                <span v-if="form.coordinator_phone?.trim()" class="text-slate-600">{{ form.coordinator_phone }}</span>
              </dd>
            </div>
            <div v-if="form.trip_type === 'point_to_point' && form.targets?.length" class="summary-field">
              <dt>{{ t('dispatch_wizard.confirm.targets_block') }}</dt>
              <dd>{{ form.targets.join(', ') }}</dd>
            </div>
          </dl>
        </SummarySection>

        <SummarySection
          :title="t('dispatch_wizard.confirm.sec_purpose')"
          :default-open="true"
          :has-issues="confirmSectionHasIssues('purpose')"
          :issue-hint="t('dispatch_wizard.confirm.section_issue_hint')"
        >
          <p class="summary-text-block whitespace-pre-wrap">{{ form.purpose?.trim() || '—' }}</p>
          <dl v-if="form.trip_type === 'point_to_point'" class="mt-3 divide-y divide-slate-200/70 border-t border-slate-200/70 pt-1">
            <div class="summary-field">
              <dt>{{ t('dispatch_wizard.create.purpose_tab_aria') }}</dt>
              <dd>
                {{
                  form.point_purpose_kind === 'extracurricular'
                    ? t('dispatch_wizard.create.purpose_extra')
                    : t('dispatch_wizard.create.purpose_point')
                }}
              </dd>
            </div>
          </dl>
        </SummarySection>

        <SummarySection
          :title="t('dispatch_wizard.confirm.sec_attach')"
          :default-open="true"
          :has-issues="confirmSectionHasIssues('attach')"
          :issue-hint="t('dispatch_wizard.confirm.section_issue_hint')"
        >
          <div v-if="basisFile?.name" class="summary-text-block">
            <p class="font-medium text-slate-900">{{ basisFile.name }}</p>
            <p v-if="basisFile?.size != null" class="mt-0.5 text-slate-500">{{ formatFileSize(basisFile.size) }}</p>
          </div>
          <p v-else class="text-slate-500">{{ t('dispatch_wizard.confirm.no_attachment') }}</p>
        </SummarySection>

        <SummarySection
          :title="t('dispatch_wizard.confirm.sec_schedule')"
          :default-open="true"
          :has-issues="confirmSectionHasIssues('schedule')"
          :issue-hint="t('dispatch_wizard.confirm.section_issue_hint')"
        >
          <div v-if="!scheduleCards.length" class="text-slate-500">{{ t('dispatch_wizard.confirm.schedule_empty') }}</div>
          <div v-else class="grid gap-3">
            <TripItemCard v-for="card in scheduleCards" :key="card.key" :heading="card.heading" :lines="card.lines" />
          </div>
        </SummarySection>
      </div>
    </template>
  </div>

  <!-- Sticky actions (chỉ trước khi gửi thành công) -->
  <Teleport to="body">
    <div
      v-if="showStickyBar"
      class="fixed inset-x-0 bottom-0 z-[120] bg-white/95 px-4 py-3 shadow-[0_-12px_40px_rgba(15,23,42,0.1)] backdrop-blur-md supports-[padding:max(0px)]:pb-[max(0.75rem,env(safe-area-inset-bottom))]"
    >
      <div class="mx-auto flex max-w-5xl flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
        <button
          type="button"
          class="order-2 rounded-lg bg-slate-100 px-4 py-2.5 text-center text-xs font-semibold text-slate-800 transition hover:bg-slate-200/90 sm:order-1 sm:text-left"
          @click="goPrevStep"
        >
          {{ t('dispatch_wizard.confirm.back_edit') }}
        </button>
        <div class="order-1 flex flex-col gap-2 sm:order-2 sm:flex-row sm:justify-end">
          <button
            type="button"
            class="rounded-lg bg-slate-100 px-4 py-2.5 text-xs font-semibold text-slate-800 transition hover:bg-slate-200/90"
            @click="saveDraft"
          >
            {{ t('dispatch_wizard.create.save_draft') }}
          </button>
          <button
            type="button"
            class="rounded-lg bg-slate-900 px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-neutral-900 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="headerPrimaryDisabled"
            @click="primaryAction"
          >
            <span v-if="loading" class="mr-2 inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white align-middle" />
            {{ loading ? t('dispatch_wizard.confirm.submit_loading') : t('dispatch_wizard.confirm.submit_primary') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, inject, unref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { DISPATCH_WIZARD_KEY } from './injectionKeys'
import SummarySection from './SummarySection.vue'
import TripItemCard from './TripItemCard.vue'
import ValidationAlert from './ValidationAlert.vue'
import {
  isPassengerRowFilled,
  isBusinessRowFilled,
  isCargoRowFilled,
} from '../../../composables/dispatchWizardConstants'
import { parseMoneyVnd } from '../../../util/money'
import { buildStaffPrefixedPath as staffPath } from '../../../config/dispatchWebBase'

const { t, locale } = useI18n()

const w = inject(DISPATCH_WIZARD_KEY)
if (!w) throw new Error('ConfirmSummary: missing DISPATCH_WIZARD_KEY')

const {
  isPortal,
  form,
  tripTypeOptions,
  basisFile,
  formatFileSize,
  formatCurrency,
  rowLineTotal,
  passengerRows,
  businessRows,
  cargoRows,
  isCargo,
  created,
  error,
  loading,
  confirmReviewIssues,
  confirmValidationGroups,
  confirmSectionHasIssues,
  headerPrimaryDisabled,
  primaryAction,
  saveDraft,
  step,
  formattedRequestedDateTime,
  wantsRecurringTemplate,
  e1WeekdayOptions,
} = w

const showStickyBar = computed(() => step.value === 3 && !created.value)

function goPrevStep() {
  if (step.value > 0) step.value--
}

const localeTag = computed(() => (locale.value === 'en' ? 'en-US' : 'vi-VN'))

const tripTypeLabel = computed(() => {
  const tt = form.value.trip_type
  const opt = tripTypeOptions.value?.find((o) => o.value === tt)
  return opt?.label ?? tt ?? '—'
})

const usageDatesDisplay = computed(() => {
  if (wantsRecurringTemplate.value) {
    const start = formatIsoDate(form.value.recurrence_start_date)
    const dep = String(form.value.recurrence_depart_time || '').trim().slice(0, 5)
    const ret = String(form.value.recurrence_return_time || '').trim().slice(0, 5)
    const times = dep && ret ? `${dep} – ${ret}` : dep || ret || '—'
    const days = (e1WeekdayOptions.value || [])
      .filter((wd) => form.value.e1_weekdays?.[wd.k])
      .map((wd) => wd.label)
      .join(', ')
    const endMode = form.value.recurrence_end_mode || 'date'
    let end = '—'
    if (endMode === 'date') {
      end = formatIsoDate(form.value.recurrence_end_date)
    } else if (form.value.recurrence_repeat_count) {
      end = t('portal.extracurricular_create.summary_end_weeks', {
        n: form.value.recurrence_repeat_count,
      })
    }
    return t('portal.extracurricular_create.summary_schedule', {
      start,
      times,
      days: days || '—',
      end,
    })
  }
  const a = formatIsoDate(form.value.proposed_date)
  const b =
    formattedRequestedDateTime.value?.trim() || formatIsoDate(form.value.date_needed)
  if (a === '—' && b === '—') return '—'
  return `${a} → ${b}`
})

const urgencyLabel = computed(() => {
  if (!form.value.is_urgent) return t('dispatch_wizard.confirm.urgency_normal')
  const r = form.value.urgent_reason?.trim()
  return r ? `${t('dispatch_wizard.confirm.urgency_fast')}: ${r}` : t('dispatch_wizard.confirm.urgency_fast')
})

const requestStatusLabel = computed(() => {
  const s = created.value?.status
  if (!s) return '—'
  const key = `dispatch_wizard.request_status.${s}`
  const translated = t(key)
  return translated !== key ? translated : s
})

function formatIsoDate(iso) {
  if (!iso) return '—'
  try {
    const [y, m, d] = String(iso).split('-').map(Number)
    const dt = new Date(y, (m || 1) - 1, d || 1)
    if (Number.isNaN(dt.getTime())) return '—'
    return new Intl.DateTimeFormat(localeTag.value, {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    }).format(dt)
  } catch {
    return '—'
  }
}

function formatShortDt(val) {
  if (!val) return '—'
  try {
    const d = new Date(val)
    if (Number.isNaN(d.getTime())) return '—'
    return new Intl.DateTimeFormat(localeTag.value, {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    }).format(d)
  } catch {
    return '—'
  }
}

const scheduleCards = computed(() => {
  const cards = []
  let seq = 0

  if (unref(isCargo)) {
    cargoRows.value.forEach((row, idx) => {
      if (!isCargoRowFilled(row)) return
      seq += 1
      const heading = t('dispatch_wizard.confirm.cargo_trip_heading', { n: seq })
      const lines = [
        {
          label: t('dispatch_wizard.s3.cargo_name'),
          value: row.name?.trim() || '—',
        },
        {
          label: t('dispatch_wizard.confirm.lbl_pickup'),
          value: `${formatShortDt(row.pickup_at)} — ${row.pickup_place?.trim() || '—'}`,
        },
        {
          label: t('dispatch_wizard.confirm.lbl_delivery'),
          value: `${formatShortDt(row.delivery_at)} — ${row.delivery_place?.trim() || '—'}`,
        },
        {
          label: t('dispatch_wizard.confirm.cost_line'),
          value: formatCurrency(parseMoneyVnd(row.cost)),
        },
      ]
      cards.push({ key: `c-${row.id ?? idx}`, heading, lines })
    })
    return cards
  }

  passengerRows.value.forEach((row, idx) => {
    if (!isPassengerRowFilled(row)) return
    seq += 1
    cards.push(passengerCard(row, seq, `p-${row.id ?? idx}`))
  })

  if (form.value.trip_type !== 'point_to_point') {
    businessRows.value.forEach((row, idx) => {
      if (!isBusinessRowFilled(row)) return
      seq += 1
      cards.push(businessCard(row, seq, `b-${row.id ?? idx}`))
    })
  }

  return cards
})

function passengerCard(row, n, key) {
  const heading = t('dispatch_wizard.confirm.trip_heading', { n })
  const lines = [
    {
      label: t('dispatch_wizard.confirm.lbl_out'),
      value: `${formatShortDt(row.depart_at)} — ${row.pickup?.trim() || '—'}`,
    },
    {
      label: t('dispatch_wizard.confirm.lbl_back'),
      value: `${formatShortDt(row.return_at)} — ${row.dropoff?.trim() || '—'}`,
    },
    { label: t('dispatch_wizard.confirm.guests_line'), value: String(row.guests ?? '').trim() || '—' },
    { label: t('dispatch_wizard.confirm.pic_line'), value: row.person_in_charge?.trim() || '—' },
    {
      label: t('dispatch_wizard.confirm.cost_line'),
      value: formatCurrency(rowLineTotal(row)),
    },
  ]
  return { key, heading, lines }
}

function businessCard(row, n, key) {
  const heading = t('dispatch_wizard.confirm.business_trip_heading', { n })
  const lines = [
    {
      label: t('dispatch_wizard.confirm.lbl_out'),
      value: `${formatShortDt(row.depart_at)} — ${row.pickup?.trim() || '—'}`,
    },
    {
      label: t('dispatch_wizard.confirm.lbl_back'),
      value: `${formatShortDt(row.return_at)} — ${row.dropoff?.trim() || '—'}`,
    },
  ]
  if (row.waypoint?.trim()) {
    lines.splice(2, 0, {
      label: t('dispatch_wizard.s3.waypoint_col'),
      value: row.waypoint.trim(),
    })
  }
  lines.push(
    { label: t('dispatch_wizard.confirm.guests_line'), value: String(row.guests ?? '').trim() || '—' },
    {
      label: t('dispatch_wizard.confirm.cost_line'),
      value: formatCurrency(rowLineTotal(row)),
    },
  )
  return { key, heading, lines }
}
</script>

<style scoped>
.summary-field {
  @apply grid grid-cols-1 gap-1 py-2.5 first:pt-0 last:pb-0 sm:grid-cols-[minmax(6.5rem,8.5rem)_1fr] sm:gap-x-3 sm:py-3;
}

.summary-field dt {
  @apply text-[11px] font-medium leading-snug text-slate-500 sm:text-xs;
}

.summary-field dd {
  @apply min-w-0 text-xs leading-snug text-slate-900 sm:text-[13px];
}

.summary-text-block {
  @apply rounded-lg bg-white/90 px-3 py-2.5 text-sm leading-relaxed text-slate-900 ring-1 ring-slate-200/60;
}
</style>
