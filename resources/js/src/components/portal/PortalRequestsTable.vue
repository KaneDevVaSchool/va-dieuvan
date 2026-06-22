<template>
  <div>
    <!-- Tablet / desktop -->
    <div class="portal-requests-table-wrap hidden overflow-hidden sm:block">
      <div class="portal-requests-table-scroll overflow-x-auto">
        <table class="portal-requests-table min-w-full text-left text-sm">
          <thead class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur-sm">
            <tr>
              <th class="portal-requests-table__sticky-col whitespace-nowrap px-4 py-3">{{ t('portal.table_code') }}</th>
              <th class="whitespace-nowrap px-4 py-3">{{ t('portal.table_trip_type') }}</th>
              <th class="hidden whitespace-nowrap px-4 py-3 lg:table-cell">{{ t('portal.table_created') }}</th>
              <th class="hidden min-w-[9rem] px-4 py-3 lg:table-cell">{{ t('portal.table_origin') }}</th>
              <th class="hidden min-w-[9rem] px-4 py-3 lg:table-cell">{{ t('portal.table_destination') }}</th>
              <th class="min-w-[10rem] px-4 py-3 lg:hidden">{{ t('portal.table_route') }}</th>
              <th class="whitespace-nowrap px-4 py-3">{{ t('portal.table_time') }}</th>
              <th class="whitespace-nowrap px-4 py-3">{{ t('portal.table_status') }}</th>
              <th class="hidden whitespace-nowrap px-4 py-3 xl:table-cell">{{ t('portal.shell.table_dispatcher') }}</th>
              <th class="whitespace-nowrap px-4 py-3">{{ t('portal.shell.table_sla') }}</th>
              <th class="w-12 px-4 py-3 text-right" :aria-label="t('portal.table_action')">
                <span class="sr-only">{{ t('portal.table_action') }}</span>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="req in requests"
              :key="req.id"
              class="group/row transition-colors"
              :class="rowHighlightClass(req)"
            >
              <td class="portal-requests-table__sticky-col whitespace-nowrap bg-white px-4 py-3">
                <div class="inline-flex items-center gap-1.5 font-mono text-sm font-semibold tracking-tight text-slate-900">
                  <BoltIcon
                    v-if="req.is_urgent"
                    class="h-4 w-4 shrink-0 text-amber-500"
                    :title="t('portal.badge_urgent')"
                    aria-hidden="true"
                  />
                  <span>{{ refCode(req) }}</span>
                </div>
              </td>
              <td class="px-4 py-3">
                <span
                  v-if="tripTypeLabel(req)"
                  class="inline-flex max-w-[10rem] rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700"
                >
                  {{ tripTypeLabel(req) }}
                </span>
                <span v-else class="italic text-slate-400">{{ emptyText.tripType('') }}</span>
              </td>
              <td class="hidden whitespace-nowrap px-4 py-3 lg:table-cell">
                <p :class="req.created_at ? 'text-slate-700' : 'italic text-slate-400'">{{ createdFmt(req) }}</p>
              </td>
              <td class="hidden max-w-[12rem] px-4 py-3 lg:table-cell">
                <p
                  class="truncate text-base"
                  :class="placeCellClass(req.origin)"
                  :title="emptyText.origin(req.origin)"
                >
                  {{ emptyText.origin(req.origin) }}
                </p>
              </td>
              <td class="hidden max-w-[12rem] px-4 py-3.5 lg:table-cell">
                <p
                  class="truncate text-base"
                  :class="placeCellClass(req.destination)"
                  :title="emptyText.destination(req.destination)"
                >
                  {{ emptyText.destination(req.destination) }}
                </p>
              </td>
              <td class="min-w-[10rem] px-4 py-3.5 lg:hidden">
                <p class="text-base font-medium text-slate-800">{{ routeLine(req) }}</p>
              </td>
              <td class="px-4 py-3">
                <p v-if="departFmt(req)" class="whitespace-nowrap font-medium text-slate-800">
                  {{ departFmt(req) }}
                </p>
                <p v-else class="italic text-slate-400">{{ emptyText.departAt('') }}</p>
              </td>
              <td class="px-4 py-3">
                <StatusBadge :status="req.status" size="sm" />
              </td>
              <td class="hidden px-4 py-3 xl:table-cell">
                <span
                  class="truncate"
                  :class="
                    emptyText.portalFieldHasValue(req.trip?.dispatcher?.name)
                      ? 'text-slate-700'
                      : 'italic text-slate-400'
                  "
                >
                  {{ dispatcherName(req) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center gap-1.5 text-xs font-semibold"
                  :class="slaToneClass(req)"
                >
                  <span class="h-2 w-2 rounded-full" :class="slaDotClass(req)" aria-hidden="true" />
                  {{ slaLabel(req) }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <RouterLink
                  :to="portalDetailRouteForRequest(req)"
                  class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-xl text-slate-400 transition group-hover/row:bg-va-50 group-hover/row:text-va-700 hover:text-va-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800"
                  :aria-label="t('portal.open_request', { code: refCode(req) })"
                >
                  <ChevronRightIcon class="h-5 w-5" />
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Mobile -->
    <div class="space-y-3 sm:hidden">
      <RouterLink
        v-for="req in requests"
        :key="req.id"
        :to="portalDetailRouteForRequest(req)"
        class="group/card block rounded-2xl bg-white p-4 shadow-sm transition active:scale-[0.99] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800"
        :class="cardHighlightClass(req)"
      >
        <div class="flex items-start gap-3">
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex items-center gap-1.5 font-mono text-base font-semibold text-slate-900">
                <BoltIcon
                  v-if="req.is_urgent"
                  class="h-4 w-4 shrink-0 text-amber-500"
                  aria-hidden="true"
                />
                {{ refCode(req) }}
              </span>
              <StatusBadge :status="req.status" size="sm" />
            </div>
            <p
              v-if="tripTypeLabel(req)"
              class="mt-2 inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"
            >
              {{ tripTypeLabel(req) }}
            </p>
            <dl class="mt-3 space-y-2 text-base">
              <div class="grid grid-cols-[4.5rem_1fr] gap-x-2 gap-y-0.5">
                <dt class="text-sm font-medium text-slate-500">{{ t('portal.table_origin') }}</dt>
                <dd class="font-medium" :class="placeCellClass(req.origin)">
                  {{ emptyText.origin(req.origin) }}
                </dd>
              </div>
              <div class="grid grid-cols-[4.5rem_1fr] gap-x-2 gap-y-0.5">
                <dt class="text-sm font-medium text-slate-500">{{ t('portal.table_destination') }}</dt>
                <dd class="font-medium" :class="placeCellClass(req.destination)">
                  {{ emptyText.destination(req.destination) }}
                </dd>
              </div>
              <div class="grid grid-cols-[4.5rem_1fr] gap-x-2 gap-y-0.5">
                <dt class="text-sm font-medium text-slate-500">{{ t('portal.table_time') }}</dt>
                <dd class="font-medium text-slate-800">
                  <span v-if="departFmt(req)">{{ departFmt(req) }}</span>
                  <span v-else class="italic text-slate-400">{{ emptyText.departAt('') }}</span>
                  <span v-if="req.arrive_by" class="mt-0.5 block text-sm font-normal text-slate-500">
                    {{ arriveFmt(req) }}
                  </span>
                </dd>
              </div>
            </dl>
          </div>
          <ChevronRightIcon
            class="mt-1 h-6 w-6 shrink-0 text-slate-300 transition group-hover/card:text-va-700"
            aria-hidden="true"
          />
        </div>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { BoltIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import StatusBadge from '../ui/StatusBadge.vue'
import { portalDetailRouteForRequest } from '../../composables/usePortalExtracurricularModule'
import { formatPortalDepartLine, formatPortalTimeHm } from '../../util/portalDatetime.js'
import { formatDispatchRequestRefCode } from '../../util/portalRequestFormat.js'
import { usePortalRequestEmptyText } from '../../composables/usePortalRequestEmptyText.js'

const props = defineProps({
  requests: { type: Array, required: true },
  highlightRequestId: { type: [Number, String, null], default: null },
})

const { t, locale } = useI18n()
const emptyText = usePortalRequestEmptyText()

const localeKey = computed(() => (locale.value === 'en' ? 'en' : 'vi'))

function placeCellClass(value) {
  return emptyText.portalFieldHasValue(value) ? 'text-slate-800' : 'italic text-slate-400'
}

function refCode(req) {
  return formatDispatchRequestRefCode(req)
}

function routeLine(req) {
  const o = emptyText.portalFieldHasValue(req.origin)
  const d = emptyText.portalFieldHasValue(req.destination)
  if (o || d) {
    return `${emptyText.origin(req.origin)} → ${emptyText.destination(req.destination)}`
  }
  return t('portal.card_no_route')
}

function tripTypeLabel(req) {
  const tt = req.trip_type
  if (!tt) return ''
  return t(`dispatch_wizard.trip_short.${tt}`)
}

function isPendingApproval(req) {
  const s = req?.status
  return s === 'pending' || s === 'price_filled'
}

function isHighlighted(req) {
  const hid = props.highlightRequestId
  if (hid == null || hid === '') return false
  return Number(req?.id) === Number(hid)
}

function rowHighlightClass(req) {
  if (isHighlighted(req)) return 'bg-va-50/80'
  return isPendingApproval(req) ? 'bg-amber-50/50' : ''
}

function cardHighlightClass(req) {
  if (isHighlighted(req)) return 'bg-va-50/70 shadow-md shadow-va-900/5'
  return isPendingApproval(req) ? 'bg-amber-50/40' : ''
}

function departFmt(req) {
  return formatPortalDepartLine(req.depart_at, localeKey.value)
}

function arriveFmt(req) {
  const hm = formatPortalTimeHm(req.arrive_by)
  if (!hm) return ''
  return t('portal.time_arrive_by', { time: hm })
}

function createdFmt(req) {
  if (!req.created_at) return emptyText.createdAt('')
  return formatPortalDepartLine(req.created_at, localeKey.value)
}

function dispatcherName(req) {
  const n = req.trip?.dispatcher?.name
  return n && String(n).trim() ? String(n).trim() : emptyText.dispatcher('')
}

function slaIsRisk(req) {
  if (req.status !== 'pending' && req.status !== 'price_filled') return false
  if (req.is_urgent) return true
  if (!req.depart_at) return false
  const depart = new Date(req.depart_at)
  if (Number.isNaN(depart.getTime())) return false
  const hours = (depart.getTime() - Date.now()) / (1000 * 60 * 60)
  return hours <= 48
}

function slaLabel(req) {
  if (req.status === 'approved' && req.trip?.status === 'completed') return t('portal.shell.sla_ok')
  if (slaIsRisk(req)) return t('portal.shell.sla_risk')
  if (req.status === 'rejected') return t('portal.shell.sla_na')
  return t('portal.shell.sla_ok')
}

function slaToneClass(req) {
  if (slaIsRisk(req)) return 'text-amber-700'
  return 'text-emerald-700'
}

function slaDotClass(req) {
  if (slaIsRisk(req)) return 'bg-amber-500'
  return 'bg-emerald-500'
}
</script>

<style scoped>
.portal-requests-table-scroll {
  -webkit-overflow-scrolling: touch;
  max-height: min(70vh, 42rem);
  overflow-y: auto;
}

.portal-requests-table {
  border-collapse: separate;
  border-spacing: 0;
}

.portal-requests-table thead {
  position: sticky;
  top: 0;
  z-index: 2;
  background: rgb(248 250 252);
}

.portal-requests-table__sticky-col {
  position: sticky;
  left: 0;
  z-index: 1;
  box-shadow: 1px 0 0 rgb(226 232 240);
}

.portal-requests-table tbody tr:nth-child(even) .portal-requests-table__sticky-col {
  background-color: rgb(248 250 252 / 0.5);
}

.portal-requests-table tbody tr:hover .portal-requests-table__sticky-col {
  background-color: rgb(240 253 250 / 0.45);
}

.portal-requests-table thead th {
  font-size: 0.8125rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  color: rgb(71 85 105);
}

.portal-requests-table tbody tr:nth-child(even) {
  background-color: rgb(248 250 252 / 0.5);
}

.portal-requests-table tbody tr:hover {
  background-color: rgb(240 253 250 / 0.45);
}

.portal-requests-table tbody td {
  border-bottom: 1px solid rgb(241 245 249 / 0.9);
  vertical-align: middle;
}

.portal-requests-table tbody tr:last-child td {
  border-bottom: none;
}
</style>
