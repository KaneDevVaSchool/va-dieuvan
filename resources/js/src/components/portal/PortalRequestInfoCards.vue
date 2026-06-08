<template>
  <section class="rounded-xl border border-slate-200 bg-white p-4 sm:p-6">
    <h2 class="text-base font-semibold text-slate-900">{{ t('portal.info_cards.section_title') }}</h2>

    <div class="mt-5 grid gap-8 md:grid-cols-2 xl:grid-cols-3">
      <div class="min-w-0">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
          {{ t('portal.info_cards.trip_title') }}
        </h3>
        <dl class="mt-3 space-y-3">
          <PortalDetailField
            :label="t('portal.info_cards.depart_date')"
            :value="departDate"
            :empty-text="t('portal.info_cards.empty_date')"
          />
          <PortalDetailField
            :label="t('portal.info_cards.depart_time')"
            :value="departTime"
            :empty-text="t('portal.info_cards.empty_time')"
          />
          <PortalDetailField
            :label="t('portal.origin')"
            :value="originDisplay"
            :empty-text="t('portal.info_cards.empty_route')"
          />
          <PortalDetailField
            :label="t('portal.destination')"
            :value="destinationDisplay"
            :empty-text="t('portal.info_cards.empty_route')"
          />
          <PortalDetailField
            :label="t('portal.passenger_count')"
            :value="passengerCount"
            :empty-text="t('portal.info_cards.empty_passengers')"
          />
        </dl>
      </div>

      <div class="min-w-0">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
          {{ t('portal.info_cards.requester_title') }}
        </h3>
        <dl class="mt-3 space-y-3">
          <PortalDetailField
            :label="t('portal.info_cards.full_name')"
            :value="requesterName"
            :empty-text="t('portal.info_cards.empty_requester')"
          />
          <PortalDetailField
            :label="t('portal.info_cards.unit')"
            :value="requesterUnit"
            :empty-text="t('portal.info_cards.empty_unit')"
          />
          <PortalDetailField
            :label="t('portal.info_cards.contact')"
            :value="requesterContact"
            :empty-text="t('portal.info_cards.empty_contact')"
          />
        </dl>
      </div>

      <div class="min-w-0 md:col-span-2 xl:col-span-1">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
          {{ t('portal.info_cards.dispatch_title') }}
        </h3>
        <dl class="mt-3 space-y-3">
          <PortalDetailField
            :label="t('portal.info_cards.driver')"
            :value="driverName"
            :empty-text="dispatchEmptyHint"
          />
          <PortalDetailField
            :label="t('portal.info_cards.vehicle')"
            :value="vehicleName"
            :empty-text="dispatchEmptyHint"
          />
          <PortalDetailField
            :label="t('portal.info_cards.plate')"
            :value="plateNumber"
            :empty-text="dispatchEmptyHint"
          />
          <div class="grid grid-cols-1 gap-1 sm:grid-cols-[minmax(0,7rem)_1fr] sm:items-start sm:gap-x-4">
            <dt class="text-sm text-slate-500">{{ t('portal.info_cards.trip_status') }}</dt>
            <dd class="text-sm">
              <span
                v-if="tripStatus"
                class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold"
                :class="tripStatusBadgeClass(tripStatus)"
              >
                {{ labelTripStatus(tripStatus) }}
              </span>
              <span v-else class="italic text-slate-400">{{ t('portal.info_cards.empty_trip_status') }}</span>
            </dd>
          </div>
        </dl>
      </div>
    </div>

    <div
      v-if="purpose || notes"
      class="mt-6 border-t border-slate-100 pt-6"
    >
      <dl class="grid gap-4 sm:grid-cols-2">
        <div v-if="purpose" class="min-w-0">
          <dt class="text-xs font-medium text-slate-500">{{ t('portal.purpose') }}</dt>
          <dd class="mt-1 text-sm leading-relaxed text-slate-800">{{ purpose }}</dd>
        </div>
        <div v-if="notes" class="min-w-0">
          <dt class="text-xs font-medium text-slate-500">{{ t('portal.notes') }}</dt>
          <dd class="mt-1 text-sm leading-relaxed text-slate-700">{{ notes }}</dd>
        </div>
      </dl>
    </div>

    <div class="mt-6 border-t border-slate-100 pt-6">
      <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
        {{ t('portal.info_cards.history_title') }}
      </h3>
      <ul v-if="historyEntries.length" class="mt-4 divide-y divide-slate-100">
        <li
          v-for="(entry, idx) in historyEntries"
          :key="`${entry.at}-${idx}`"
          class="flex flex-col gap-0.5 py-3 first:pt-0 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4"
        >
          <div class="min-w-0">
            <p class="text-sm font-medium text-slate-900">{{ entry.title }}</p>
            <p v-if="entry.actor" class="mt-0.5 text-sm text-slate-600">{{ entry.actor }}</p>
          </div>
          <time class="shrink-0 text-xs tabular-nums text-slate-500">{{ entry.at }}</time>
        </li>
      </ul>
      <p v-else class="mt-3 text-sm italic text-slate-400">{{ t('portal.info_cards.history_empty') }}</p>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { labelTripStatus } from '../../util/labels'
import { tripStatusBadgeClass } from '../../composables/useTripStatusWorkflow'
import PortalDetailField from './PortalDetailField.vue'

const props = defineProps({
  req: { type: Object, required: true },
  origin: { type: String, default: '—' },
  destination: { type: String, default: '—' },
  timelineSteps: { type: Array, default: () => [] },
  purpose: { type: String, default: '' },
  notes: { type: String, default: '' },
})

const { t } = useI18n()

function isDash(v) {
  const s = String(v ?? '').trim()
  return !s || s === '—'
}

const originDisplay = computed(() => (isDash(props.origin) ? '' : props.origin.trim()))
const destinationDisplay = computed(() => (isDash(props.destination) ? '' : props.destination.trim()))

const passengerCount = computed(() => {
  const r = props.req
  const n = r?.student_count_actual ?? r?.passenger_count
  if (n == null || n === '') return ''
  return String(n)
})

const requesterName = computed(() => {
  const r = props.req
  return (r?.requester_name || r?.requester?.name || '').trim()
})

const requesterUnit = computed(() => {
  const r = props.req
  const dept = r?.requester?.department?.name
  const unit = r?.wizard_snapshot?.form?.requester_unit
  return (dept || unit || '').trim()
})

const requesterContact = computed(() => {
  const r = props.req?.requester
  if (!r) return ''
  const parts = [r.phone, r.email].filter((x) => x && String(x).trim())
  return parts.join(' · ')
})

const trip = computed(() => props.req?.trip)

const dispatchEmptyHint = computed(() => {
  const st = props.req?.status
  if (st === 'approved' || trip.value) {
    return t('portal.info_cards.empty_dispatch_pending')
  }
  return t('portal.info_cards.empty_dispatch_after_approval')
})

const driverName = computed(() => {
  const tr = trip.value
  if (!tr) return ''
  const name = tr.driver?.full_name || tr.external_driver_ref
  return name ? String(name).trim() : ''
})

const vehicleName = computed(() => {
  const tr = trip.value
  if (!tr) return ''
  const name = tr.vehicle?.name || tr.external_vehicle_ref
  return name ? String(name).trim() : ''
})

const plateNumber = computed(() => {
  const plate = trip.value?.vehicle?.license_plate
  return plate ? String(plate).trim() : ''
})

const tripStatus = computed(() => trip.value?.status || '')

const departDate = computed(() => formatPart(props.req?.depart_at, { date: true }))
const departTime = computed(() => formatPart(props.req?.depart_at, { time: true }))

const historyEntries = computed(() => {
  const entries = []
  for (const step of props.timelineSteps) {
    if (step.state !== 'done' && step.state !== 'rejected') continue
    if (!step.sub || step.sub === '—') continue
    entries.push({
      at: step.sub,
      title: step.label,
      actor: step.actor || '',
    })
  }
  const r = props.req
  if (r?.status === 'rejected' && r.rejection_reason) {
    entries.push({
      at: formatPart(r.updated_at, { full: true }) || '—',
      title: t('portal.rejected_title'),
      actor: String(r.rejection_reason).slice(0, 120),
    })
  }
  return entries.reverse()
})

function formatPart(iso, opts = {}) {
  if (!iso) return ''
  try {
    const d = new Date(iso)
    if (opts.date) {
      return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
    }
    if (opts.time) {
      return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
    }
    return d.toLocaleString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return ''
  }
}
</script>
