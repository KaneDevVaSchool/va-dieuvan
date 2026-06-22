<template>
  <div class="space-y-4">
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div class="border-b border-va-100 bg-va-50/90 px-4 py-3 sm:px-5">
        <h2 class="text-sm font-bold text-va-900">{{ t('portal.info_cards.trip_title') }}</h2>
      </div>
      <table class="portal-info-table w-full text-sm">
        <tbody>
          <tr>
            <th scope="row" class="portal-info-th">{{ t('portal.info_cards.depart_date') }}</th>
            <td class="portal-info-td">
              <span v-if="departDate" class="font-semibold text-slate-900">{{ departDate }}</span>
              <span v-else class="italic text-slate-400">{{ t('portal.info_cards.empty_date') }}</span>
            </td>
          </tr>
          <tr>
            <th scope="row" class="portal-info-th">{{ t('portal.info_cards.depart_time') }}</th>
            <td class="portal-info-td">
              <span v-if="departTime" class="font-semibold text-slate-900">{{ departTime }}</span>
              <span v-else class="italic text-slate-400">{{ t('portal.info_cards.empty_time') }}</span>
            </td>
          </tr>
          <tr>
            <th scope="row" class="portal-info-th">{{ t('portal.origin') }}</th>
            <td class="portal-info-td">
              <span v-if="originDisplay" class="font-medium text-slate-900">{{ originDisplay }}</span>
              <span v-else class="italic text-slate-400">{{ t('portal.info_cards.empty_origin') }}</span>
            </td>
          </tr>
          <tr>
            <th scope="row" class="portal-info-th">{{ t('portal.destination') }}</th>
            <td class="portal-info-td">
              <span v-if="destinationDisplay" class="font-medium text-slate-900">{{ destinationDisplay }}</span>
              <span v-else class="italic text-slate-400">{{ t('portal.info_cards.empty_destination') }}</span>
            </td>
          </tr>
          <tr class="bg-amber-50/50">
            <th scope="row" class="portal-info-th font-bold text-amber-900">{{ t('portal.passenger_count') }}</th>
            <td class="portal-info-td">
              <span v-if="passengerCount" class="text-lg font-bold tabular-nums text-amber-950">{{ passengerCount }}</span>
              <span v-else class="italic text-slate-400">{{ t('portal.info_cards.empty_passengers') }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 bg-slate-50 px-4 py-3 sm:px-5">
          <h2 class="text-sm font-bold text-slate-800">{{ t('portal.info_cards.requester_title') }}</h2>
        </div>
        <table class="portal-info-table w-full text-sm">
          <tbody>
            <tr>
              <th scope="row" class="portal-info-th">{{ t('portal.info_cards.full_name') }}</th>
              <td class="portal-info-td">
                <span v-if="requesterName" class="font-semibold text-slate-900">{{ requesterName }}</span>
                <span v-else class="italic text-slate-400">{{ t('portal.info_cards.empty_requester') }}</span>
              </td>
            </tr>
            <tr>
              <th scope="row" class="portal-info-th">{{ t('portal.info_cards.unit') }}</th>
              <td class="portal-info-td">
                <span v-if="requesterUnit" class="font-medium text-slate-800">{{ requesterUnit }}</span>
                <span v-else class="italic text-slate-400">{{ t('portal.info_cards.empty_unit') }}</span>
              </td>
            </tr>
            <tr>
              <th scope="row" class="portal-info-th">{{ t('portal.info_cards.contact') }}</th>
              <td class="portal-info-td">
                <span v-if="requesterContact" class="font-medium text-slate-800">{{ requesterContact }}</span>
                <span v-else class="italic text-slate-400">{{ t('portal.info_cards.empty_contact') }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="border-b border-slate-100 bg-slate-50 px-4 py-3 sm:px-5">
          <h2 class="text-sm font-bold text-slate-800">{{ t('portal.info_cards.dispatch_title') }}</h2>
        </div>
        <table class="portal-info-table w-full text-sm">
          <tbody>
            <tr>
              <th scope="row" class="portal-info-th">{{ t('portal.info_cards.driver') }}</th>
              <td class="portal-info-td">
                <span v-if="driverName" class="font-medium text-slate-900">{{ driverName }}</span>
                <span v-else class="italic text-slate-400">{{ dispatchEmptyHint }}</span>
              </td>
            </tr>
            <tr>
              <th scope="row" class="portal-info-th">{{ t('portal.info_cards.vehicle') }}</th>
              <td class="portal-info-td">
                <span v-if="vehicleName" class="font-medium text-slate-900">{{ vehicleName }}</span>
                <span v-else class="italic text-slate-400">{{ dispatchEmptyHint }}</span>
              </td>
            </tr>
            <tr>
              <th scope="row" class="portal-info-th">{{ t('portal.info_cards.plate') }}</th>
              <td class="portal-info-td">
                <span v-if="plateNumber" class="font-mono font-medium text-slate-900">{{ plateNumber }}</span>
                <span v-else class="italic text-slate-400">{{ dispatchEmptyHint }}</span>
              </td>
            </tr>
            <tr>
              <th scope="row" class="portal-info-th">{{ t('portal.info_cards.trip_status') }}</th>
              <td class="portal-info-td">
                <span
                  v-if="tripStatus"
                  class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold"
                  :class="tripStatusBadgeClass(tripStatus)"
                >
                  {{ labelTripStatus(tripStatus) }}
                </span>
                <span v-else class="italic text-slate-400">{{ t('portal.info_cards.empty_trip_status') }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div
      v-if="purpose || notes"
      class="overflow-hidden rounded-xl border border-slate-200 bg-white"
    >
      <div class="border-b border-slate-100 bg-slate-50 px-4 py-3 sm:px-5">
        <h2 class="text-sm font-bold text-slate-800">{{ t('portal.info_cards.notes_title') }}</h2>
      </div>
      <table class="portal-info-table w-full text-sm">
        <tbody>
          <tr v-if="purpose">
            <th scope="row" class="portal-info-th align-top">{{ t('portal.purpose') }}</th>
            <td class="portal-info-td leading-relaxed text-slate-800">{{ purpose }}</td>
          </tr>
          <tr v-if="notes">
            <th scope="row" class="portal-info-th align-top">{{ t('portal.notes') }}</th>
            <td class="portal-info-td leading-relaxed text-slate-700">{{ notes }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div class="border-b border-slate-100 bg-slate-50 px-4 py-3 sm:px-5">
        <h2 class="text-sm font-bold text-slate-800">{{ t('portal.info_cards.history_title') }}</h2>
      </div>
      <ul v-if="historyEntries.length" class="divide-y divide-slate-100">
        <li
          v-for="(entry, idx) in historyEntries"
          :key="`${entry.at}-${idx}`"
          class="flex flex-col gap-0.5 px-4 py-3 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4 sm:px-5"
        >
          <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-900">{{ entry.title }}</p>
            <p v-if="entry.actor" class="mt-0.5 text-sm text-slate-600">{{ entry.actor }}</p>
          </div>
          <time class="shrink-0 text-xs tabular-nums font-medium text-slate-500">{{ entry.at }}</time>
        </li>
      </ul>
      <p v-else class="px-4 py-4 text-sm italic text-slate-400 sm:px-5">{{ t('portal.info_cards.history_empty') }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { labelTripStatus } from '../../util/labels'
import { tripStatusBadgeClass } from '../../composables/useTripStatusWorkflow'
import { portalRequestPassengerCount } from '../../util/portalRequestFormat.js'

const props = defineProps({
  req: { type: Object, required: true },
  origin: { type: String, default: '' },
  destination: { type: String, default: '' },
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
  const n = portalRequestPassengerCount(props.req)
  return n > 0 ? String(n) : ''
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
  const name = tr.vehicle?.type || tr.external_vehicle_ref
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
      at: formatPart(r.updated_at, { full: true }) || t('portal.info_cards.empty_time'),
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

<style scoped>
.portal-info-th {
  @apply w-[38%] min-w-[7.5rem] border-t border-slate-100 bg-slate-50/60 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 sm:w-40 sm:px-5;
}
.portal-info-td {
  @apply border-t border-slate-100 px-4 py-3 text-slate-800 sm:px-5;
}
.portal-info-table tr:first-child .portal-info-th,
.portal-info-table tr:first-child .portal-info-td {
  @apply border-t-0;
}
</style>
