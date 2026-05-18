<template>
  <div
    class="rounded-2xl border border-slate-200/90 bg-white px-5 py-7 shadow-sm ring-1 ring-slate-950/[0.04] sm:px-8 sm:py-9"
  >
    <div class="flex flex-col gap-1 border-b border-slate-100 pb-5 sm:pb-6">
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
    <div v-if="created" class="mt-6 space-y-6">
      <div
        class="rounded-xl border border-slate-200 bg-slate-50/80 px-5 py-5 shadow-sm ring-1 ring-slate-950/[0.04] sm:flex sm:items-start sm:justify-between sm:gap-6"
      >
        <div class="min-w-0 space-y-2">
          <p class="text-sm font-semibold text-slate-900">
            {{ t('dispatch_wizard.confirm.success_meta', { id: created.id }) }}
          </p>
          <span
            class="inline-flex w-fit items-center rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-800"
          >
            {{ requestStatusLabel }}
          </span>
          <p v-if="error" class="text-xs font-medium text-rose-700">{{ error }}</p>
        </div>
          <div class="mt-4 flex min-w-0 flex-1 flex-col gap-2 sm:mt-0 sm:items-end">
          <div class="flex shrink-0 flex-wrap gap-2">
            <RouterLink
              v-if="!isPortal"
              :to="staffPath('/requests')"
              class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50"
            >
              {{ t('dispatch_wizard.confirm.view_list') }}
            </RouterLink>
            <RouterLink
              v-else
              :to="{ name: 'portalRequestList' }"
              class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50"
            >
              {{ t('portal.confirm.view_my_requests') }}
            </RouterLink>
            <button
              type="button"
              class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-xs font-semibold shadow-sm transition hover:bg-neutral-900 disabled:opacity-50"
              :class="
                pdfLockedAfterCreate
                  ? 'cursor-not-allowed border-slate-300 bg-slate-100 text-slate-500'
                  : 'border-slate-900 bg-black text-white'
              "
              :disabled="pdfLoading || pdfLockedAfterCreate"
              :title="pdfLockedAfterCreate ? t('dispatch_wizard.confirm.pdf_locked_tooltip') : ''"
              @click="downloadCreatedPdf"
            >
              <span v-if="pdfLoading" class="mr-2 h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white" />
              {{ t('dispatch_wizard.create.download_pdf') }}
            </button>
          </div>
          <p v-if="pdfLockedAfterCreate" class="max-w-[min(100%,20rem)] text-xs leading-relaxed text-slate-600 sm:text-right">
            {{ t('dispatch_wizard.confirm.pdf_locked_visible_hint') }}
          </p>
        </div>
      </div>

      <PdfPreview
        class="w-full"
        :title="t('dispatch_wizard.confirm.pdf_title')"
        :placeholder="t('dispatch_wizard.confirm.pdf_hint')"
        :pdf-url="pdfPreviewUrl"
        :pdf-loading="pdfLoading"
        :pdf-error="pdfError"
        :has-created-record="!!created?.id"
        :download-busy="pdfLoading"
        :download-disabled="pdfLoading || !created?.id || pdfLockedAfterCreate"
        :fallback-download-label="t('dispatch_wizard.confirm.pdf_download_fallback')"
        :loading-label="t('dispatch_wizard.create.exporting_pdf')"
        :zoom-in-label="t('dispatch_wizard.confirm.zoom_in')"
        :zoom-out-label="t('dispatch_wizard.confirm.zoom_out')"
        :fullscreen-label="t('dispatch_wizard.confirm.fullscreen')"
        :fullscreen-close-label="t('dispatch_wizard.confirm.fullscreen_close')"
        :show-close="!!pdfPreviewUrl"
        :close-label="t('dispatch_wizard.create.preview_close')"
        @download="downloadCreatedPdf"
        @close="closePdfPreview"
      />
    </div>

    <!-- Review (pre-submit) -->
    <template v-else>
      <ValidationAlert
        class="mt-6"
        :title="t('dispatch_wizard.confirm.validation_title')"
        :messages="confirmReviewIssues"
      />

      <div v-if="error" class="mt-4 rounded-xl border border-rose-200/90 bg-rose-50 px-4 py-3 text-xs font-medium text-rose-900">
        {{ error }}
      </div>

      <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-start lg:gap-8">
        <div class="min-w-0 flex-1 space-y-4 lg:max-w-[38%]">
          <SummarySection :title="t('dispatch_wizard.confirm.sec_general')" :default-open="true">
            <dl class="space-y-2">
              <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2">
                <dt class="shrink-0 font-medium text-slate-500">{{ t('dispatch_wizard.confirm.svc_type') }}</dt>
                <dd class="min-w-0 text-slate-900">{{ tripTypeLabel }}</dd>
              </div>
              <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2">
                <dt class="shrink-0 font-medium text-slate-500">{{ t('dispatch_wizard.confirm.requester') }}</dt>
                <dd class="min-w-0 text-slate-900">{{ form.requester_name?.trim() || '—' }}</dd>
              </div>
              <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2">
                <dt class="shrink-0 font-medium text-slate-500">{{ t('dispatch_wizard.confirm.email_phone') }}</dt>
                <dd class="min-w-0 break-words text-slate-900">
                  {{ form.requester_email?.trim() || '—' }}
                  <span v-if="form.requester_phone?.trim()" class="text-slate-600">
                    · {{ form.requester_phone }}</span>
                </dd>
              </div>
              <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2">
                <dt class="shrink-0 font-medium text-slate-500">{{ t('dispatch_wizard.confirm.usage_dates') }}</dt>
                <dd class="min-w-0 text-slate-900">{{ usageDatesDisplay }}</dd>
              </div>
              <div class="flex flex-col gap-0.5 sm:flex-row sm:gap-2">
                <dt class="shrink-0 font-medium text-slate-500">{{ t('dispatch_wizard.confirm.urgency') }}</dt>
                <dd class="min-w-0 text-slate-900">{{ urgencyLabel }}</dd>
              </div>
              <div v-if="form.requester_unit?.trim()" class="flex flex-col gap-0.5 sm:flex-row sm:gap-2">
                <dt class="shrink-0 font-medium text-slate-500">{{ t('dispatch_wizard.create.unit') }}</dt>
                <dd class="min-w-0 text-slate-900">{{ form.requester_unit }}</dd>
              </div>
              <div
                v-if="form.coordinator_name?.trim() || form.coordinator_email?.trim() || form.coordinator_phone?.trim()"
                class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"
              >
                <dt class="shrink-0 font-medium text-slate-500">{{ t('dispatch_wizard.confirm.coord_block') }}</dt>
                <dd class="min-w-0 text-slate-900">
                  {{ form.coordinator_name?.trim() || '—' }}
                  <span v-if="form.coordinator_email?.trim()" class="block text-slate-600">{{ form.coordinator_email }}</span>
                  <span v-if="form.coordinator_phone?.trim()" class="text-slate-600">{{ form.coordinator_phone }}</span>
                </dd>
              </div>
              <div v-if="form.trip_type === 'point_to_point' && form.targets?.length" class="flex flex-col gap-0.5">
                <dt class="font-medium text-slate-500">{{ t('dispatch_wizard.confirm.targets_block') }}</dt>
                <dd class="text-slate-900">{{ form.targets.join(', ') }}</dd>
              </div>
            </dl>
          </SummarySection>

          <SummarySection :title="t('dispatch_wizard.confirm.sec_purpose_attach')" :default-open="true">
            <p class="whitespace-pre-wrap text-slate-900">{{ form.purpose?.trim() || '—' }}</p>
            <div v-if="form.trip_type === 'point_to_point'" class="mt-3 border-t border-slate-100 pt-3">
              <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                {{ t('dispatch_wizard.create.purpose_tab_aria') }}
              </p>
              <p class="mt-1 text-slate-900">
                {{
                  form.point_purpose_kind === 'extracurricular'
                    ? t('dispatch_wizard.create.purpose_extra')
                    : t('dispatch_wizard.create.purpose_point')
                }}
              </p>
            </div>
            <div class="mt-4 border-t border-slate-100 pt-3">
              <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                {{ t('dispatch_wizard.confirm.attachments') }}
              </p>
              <p v-if="basisFile?.name" class="mt-1 font-medium text-slate-900">{{ basisFile.name }}</p>
              <p v-else class="mt-1 text-slate-500">{{ t('dispatch_wizard.confirm.no_attachment') }}</p>
              <p v-if="basisFile?.size != null" class="mt-0.5 text-slate-500">{{ formatFileSize(basisFile.size) }}</p>
            </div>
          </SummarySection>

          <SummarySection :title="t('dispatch_wizard.confirm.sec_schedule')" :default-open="true">
            <div v-if="!scheduleCards.length" class="text-slate-500">{{ t('dispatch_wizard.confirm.schedule_empty') }}</div>
            <div v-else class="space-y-3">
              <TripItemCard v-for="card in scheduleCards" :key="card.key" :heading="card.heading" :lines="card.lines" />
            </div>
          </SummarySection>
        </div>

        <div class="min-w-0 flex-1 lg:min-w-[60%] lg:flex-[1.2]">
          <PdfPreview
            :title="t('dispatch_wizard.confirm.pdf_title')"
            :placeholder="t('dispatch_wizard.confirm.pdf_hint')"
            :pdf-url="null"
            :pdf-loading="false"
            :pdf-error="''"
            :has-created-record="false"
            :show-download="false"
            :fallback-download-label="t('dispatch_wizard.confirm.pdf_download_fallback')"
            :loading-label="t('dispatch_wizard.create.exporting_pdf')"
            :zoom-in-label="t('dispatch_wizard.confirm.zoom_in')"
            :zoom-out-label="t('dispatch_wizard.confirm.zoom_out')"
            :fullscreen-label="t('dispatch_wizard.confirm.fullscreen')"
            :fullscreen-close-label="t('dispatch_wizard.confirm.fullscreen_close')"
          />
        </div>
      </div>
    </template>
  </div>

  <!-- Sticky actions (chỉ trước khi gửi thành công) -->
  <Teleport to="body">
    <div
      v-if="showStickyBar"
      class="fixed inset-x-0 bottom-0 z-[120] border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-8px_30px_rgba(15,23,42,0.08)] backdrop-blur-md supports-[padding:max(0px)]:pb-[max(0.75rem,env(safe-area-inset-bottom))]"
    >
      <div class="mx-auto flex max-w-6xl flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
        <button
          type="button"
          class="order-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-center text-xs font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 sm:order-1 sm:text-left"
          @click="goPrevStep"
        >
          {{ t('dispatch_wizard.confirm.back_edit') }}
        </button>
        <div class="order-1 flex flex-col gap-2 sm:order-2 sm:flex-row sm:justify-end">
          <button
            type="button"
            class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50"
            @click="saveDraft"
          >
            {{ t('dispatch_wizard.create.save_draft') }}
          </button>
          <button
            type="button"
            class="rounded-lg border border-black bg-black px-5 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-neutral-900 disabled:cursor-not-allowed disabled:opacity-50"
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
import PdfPreview from './PdfPreview.vue'
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
  pdfPreviewUrl,
  pdfLoading,
  pdfError,
  confirmReviewIssues,
  headerPrimaryDisabled,
  primaryAction,
  saveDraft,
  downloadCreatedPdf,
  closePdfPreview,
  step,
  formattedRequestedDateTime,
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

const pdfLockedAfterCreate = computed(
  () => !!(created.value?.id && created.value?.status !== 'approved'),
)

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
