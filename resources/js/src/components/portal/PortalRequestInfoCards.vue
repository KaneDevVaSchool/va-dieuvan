<template>
  <div class="space-y-4">
    <article class="rounded-xl border border-slate-200 bg-white p-4">
      <h2 class="text-sm font-semibold text-slate-900">{{ t('portal.info_cards.trip_title') }}</h2>
      <dl class="mt-3 space-y-2.5 text-sm">
        <div v-if="departDate" class="flex justify-between gap-3">
          <dt class="text-slate-500">{{ t('portal.info_cards.depart_date') }}</dt>
          <dd class="text-right font-medium text-slate-900">{{ departDate }}</dd>
        </div>
        <div v-if="departTime" class="flex justify-between gap-3">
          <dt class="text-slate-500">{{ t('portal.info_cards.depart_time') }}</dt>
          <dd class="text-right font-medium text-slate-900">{{ departTime }}</dd>
        </div>
        <div class="flex justify-between gap-3">
          <dt class="shrink-0 text-slate-500">{{ t('portal.origin') }}</dt>
          <dd class="text-right font-medium text-slate-900">{{ origin }}</dd>
        </div>
        <div class="flex justify-between gap-3">
          <dt class="shrink-0 text-slate-500">{{ t('portal.destination') }}</dt>
          <dd class="text-right font-medium text-slate-900">{{ destination }}</dd>
        </div>
        <div v-if="passengerCount != null && passengerCount !== ''" class="flex justify-between gap-3">
          <dt class="text-slate-500">{{ t('portal.passenger_count') }}</dt>
          <dd class="font-medium text-slate-900">{{ passengerCount }}</dd>
        </div>
      </dl>
    </article>

    <article class="rounded-xl border border-slate-200 bg-white p-4">
      <h2 class="text-sm font-semibold text-slate-900">{{ t('portal.info_cards.requester_title') }}</h2>
      <dl class="mt-3 space-y-2.5 text-sm">
        <div v-if="requesterName" class="flex justify-between gap-3">
          <dt class="text-slate-500">{{ t('portal.info_cards.full_name') }}</dt>
          <dd class="text-right font-medium text-slate-900">{{ requesterName }}</dd>
        </div>
        <div v-if="requesterUnit" class="flex justify-between gap-3">
          <dt class="text-slate-500">{{ t('portal.info_cards.unit') }}</dt>
          <dd class="text-right font-medium text-slate-900">{{ requesterUnit }}</dd>
        </div>
        <div v-if="requesterContact" class="flex justify-between gap-3">
          <dt class="text-slate-500">{{ t('portal.info_cards.contact') }}</dt>
          <dd class="text-right font-medium text-slate-900">{{ requesterContact }}</dd>
        </div>
      </dl>
    </article>

    <article class="rounded-xl border border-slate-200 bg-white p-4">
      <h2 class="text-sm font-semibold text-slate-900">{{ t('portal.info_cards.dispatch_title') }}</h2>
      <dl class="mt-3 space-y-2.5 text-sm">
        <div class="flex justify-between gap-3">
          <dt class="text-slate-500">{{ t('portal.info_cards.driver') }}</dt>
          <dd class="text-right font-medium text-slate-900">{{ driverLine }}</dd>
        </div>
        <div class="flex justify-between gap-3">
          <dt class="text-slate-500">{{ t('portal.info_cards.vehicle') }}</dt>
          <dd class="text-right font-medium text-slate-900">{{ vehicleLine }}</dd>
        </div>
        <div class="flex justify-between gap-3">
          <dt class="text-slate-500">{{ t('portal.info_cards.plate') }}</dt>
          <dd class="text-right font-medium text-slate-900">{{ plateLine }}</dd>
        </div>
        <div class="flex justify-between gap-3">
          <dt class="text-slate-500">{{ t('portal.info_cards.trip_status') }}</dt>
          <dd class="text-right">
            <span
              v-if="tripStatus"
              class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold"
              :class="tripStatusBadgeClass(tripStatus)"
            >
              {{ labelTripStatus(tripStatus) }}
            </span>
            <span v-else class="font-medium text-slate-500">—</span>
          </dd>
        </div>
      </dl>
    </article>

    <article class="rounded-xl border border-slate-200 bg-white p-4">
      <h2 class="text-sm font-semibold text-slate-900">{{ t('portal.info_cards.history_title') }}</h2>
      <ul v-if="historyEntries.length" class="mt-3 space-y-3">
        <li
          v-for="(entry, idx) in historyEntries"
          :key="`${entry.at}-${idx}`"
          class="border-l-2 border-slate-200 pl-3"
        >
          <p class="text-xs text-slate-500">{{ entry.at }}</p>
          <p class="text-sm font-medium text-slate-900">{{ entry.title }}</p>
          <p v-if="entry.actor" class="text-xs text-slate-600">{{ entry.actor }}</p>
        </li>
      </ul>
      <p v-else class="mt-3 text-sm text-slate-500">{{ t('portal.info_cards.history_empty') }}</p>
    </article>

    <div v-if="purpose || notes" class="rounded-xl border border-slate-200 bg-white p-4">
      <dl class="space-y-3 text-sm">
        <div v-if="purpose">
          <dt class="text-xs font-medium text-slate-500">{{ t('portal.purpose') }}</dt>
          <dd class="mt-1 text-slate-800">{{ purpose }}</dd>
        </div>
        <div v-if="notes">
          <dt class="text-xs font-medium text-slate-500">{{ t('portal.notes') }}</dt>
          <dd class="mt-1 text-slate-700">{{ notes }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { labelTripStatus } from '../../util/labels'
import { tripStatusBadgeClass } from '../../composables/useTripStatusWorkflow'

const props = defineProps({
  req: { type: Object, required: true },
  origin: { type: String, default: '—' },
  destination: { type: String, default: '—' },
  timelineSteps: { type: Array, default: () => [] },
  purpose: { type: String, default: '' },
  notes: { type: String, default: '' },
})

const { t } = useI18n()

const passengerCount = computed(() => {
  const r = props.req
  const n = r?.student_count_actual ?? r?.passenger_count
  if (n == null || n === '') return null
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

const driverLine = computed(() => {
  const tr = trip.value
  if (!tr) return '—'
  const name = tr.driver?.full_name || tr.external_driver_ref
  return name ? String(name).trim() : t('portal.info_cards.unassigned')
})

const vehicleLine = computed(() => {
  const tr = trip.value
  if (!tr) return '—'
  const name = tr.vehicle?.name || tr.external_vehicle_ref
  return name ? String(name).trim() : t('portal.info_cards.unassigned')
})

const plateLine = computed(() => {
  const plate = trip.value?.vehicle?.license_plate
  return plate ? String(plate).trim() : '—'
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
