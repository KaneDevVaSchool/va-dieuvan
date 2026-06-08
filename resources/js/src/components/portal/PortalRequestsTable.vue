<template>
  <div>
    <!-- Tablet / desktop table -->
    <div
      class="portal-requests-table-wrap hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm sm:block"
    >
      <div class="portal-requests-table-scroll overflow-x-auto">
      <table class="portal-requests-table min-w-full text-left text-sm">
        <thead>
          <tr>
            <th class="whitespace-nowrap px-4 py-3">{{ t('portal.table_code') }}</th>
            <th class="min-w-[12rem] px-4 py-3">{{ t('portal.table_route') }}</th>
            <th class="whitespace-nowrap px-4 py-3">{{ t('portal.table_time') }}</th>
            <th class="whitespace-nowrap px-4 py-3">{{ t('portal.table_status') }}</th>
            <th class="w-14 px-4 py-3 text-right" :aria-label="t('portal.table_action')"><span class="sr-only">{{ t('portal.table_action') }}</span></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="req in requests"
            :key="req.id"
            class="group/row transition"
            :class="rowHighlightClass(req)"
          >
            <td class="whitespace-nowrap px-4 py-3">
              <div class="group/id relative inline-flex items-center gap-1.5 font-mono text-sm font-semibold text-slate-900">
                <BoltIcon
                  v-if="req.is_urgent"
                  class="h-4 w-4 shrink-0 text-amber-500"
                  :title="t('portal.badge_urgent')"
                  aria-hidden="true"
                />
                <span>#{{ req.id }}</span>
                <div
                  class="pointer-events-none absolute left-0 top-full z-20 mt-1 hidden w-64 rounded-xl border border-slate-200 bg-white p-3 text-left text-xs font-normal normal-case text-slate-600 shadow-lg group-hover/id:block group-focus-within/id:block"
                  role="tooltip"
                >
                  <p class="font-medium text-slate-800">{{ routeLine(req) }}</p>
                  <p class="mt-1">
                    <StatusBadge :status="req.status" size="sm" />
                  </p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <p class="font-medium text-slate-800">{{ routeLine(req) }}</p>
              <p v-if="tripTypeLabel(req)" class="mt-0.5 text-xs text-slate-500">{{ tripTypeLabel(req) }}</p>
            </td>
            <td class="px-4 py-3">
              <p v-if="departFmt(req)" class="whitespace-nowrap text-xs font-medium text-slate-700">{{ departFmt(req) }}</p>
              <p v-else class="text-xs text-slate-400">—</p>
              <p v-if="req.arrive_by" class="mt-0.5 whitespace-nowrap text-[11px] text-slate-400">
                {{ arriveFmt(req) }}
              </p>
            </td>
            <td class="px-4 py-3">
              <StatusBadge :status="req.status" size="sm" />
            </td>
            <td class="px-4 py-3 text-right">
              <RouterLink
                :to="portalDetailRouteForRequest(req)"
                class="inline-flex min-h-[40px] min-w-[40px] items-center justify-center rounded-xl text-slate-400 transition group-hover/row:bg-va-50 group-hover/row:text-va-700 hover:text-va-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800"
                :aria-label="t('portal.open_request', { id: req.id })"
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
        class="group/card flex gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-va-200/80 hover:bg-va-50/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800"
        :class="cardHighlightClass(req)"
      >
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1 font-mono text-sm font-semibold text-slate-900">
              <BoltIcon
                v-if="req.is_urgent"
                class="h-4 w-4 shrink-0 text-amber-500"
                aria-hidden="true"
              />
              #{{ req.id }}
            </span>
            <span
              v-if="tripTypeLabel(req)"
              class="inline-flex max-w-[10rem] truncate rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600"
            >
              {{ tripTypeLabel(req) }}
            </span>
            <StatusBadge :status="req.status" size="sm" />
          </div>
          <p class="mt-2 text-sm font-medium text-slate-800">{{ routeLine(req) }}</p>
          <div class="mt-2">
            <p v-if="departFmt(req)" class="text-xs font-medium text-slate-700">{{ departFmt(req) }}</p>
            <p v-else class="text-xs text-slate-400">—</p>
            <p v-if="req.arrive_by" class="mt-0.5 text-[11px] text-slate-400">{{ arriveFmt(req) }}</p>
          </div>
        </div>
        <ChevronRightIcon class="h-5 w-5 shrink-0 text-slate-400 transition group-hover/card:text-va-700" aria-hidden="true" />
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

const props = defineProps({
  requests: { type: Array, required: true },
  highlightRequestId: { type: [Number, String, null], default: null },
})

const { t, locale } = useI18n()

const localeKey = computed(() => (locale.value === 'en' ? 'en' : 'vi'))

function routeLine(req) {
  const o = (req.origin || '').trim()
  const d = (req.destination || '').trim()
  if (o || d) return `${o || '…'} → ${d || '…'}`.trim()
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
  if (isHighlighted(req)) {
    return 'bg-va-50/70 ring-2 ring-inset ring-va-600/70'
  }
  return isPendingApproval(req) ? 'bg-amber-50/40' : ''
}

function cardHighlightClass(req) {
  if (isHighlighted(req)) {
    return 'border-va-300/90 bg-va-50/50 ring-2 ring-va-600/50'
  }
  return isPendingApproval(req) ? 'border-amber-200/80 bg-amber-50/30' : ''
}

function departFmt(req) {
  return formatPortalDepartLine(req.depart_at, localeKey.value)
}

function arriveFmt(req) {
  const hm = formatPortalTimeHm(req.arrive_by)
  if (!hm) return ''
  return t('portal.time_arrive_by', { time: hm })
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
  background: linear-gradient(180deg, rgb(248 250 252) 0%, rgb(241 245 249 / 0.98) 100%);
  box-shadow: 0 1px 0 rgb(226 232 240);
}

.portal-requests-table thead th {
  font-size: 0.6875rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: rgb(71 85 105);
}

.portal-requests-table tbody tr:nth-child(even) {
  background-color: rgb(248 250 252 / 0.65);
}

.portal-requests-table tbody tr:hover {
  background-color: rgb(240 253 250 / 0.55);
}

.portal-requests-table tbody td {
  border-bottom: 1px solid rgb(241 245 249);
  vertical-align: middle;
}

.portal-requests-table tbody tr:last-child td {
  border-bottom: none;
}
</style>
