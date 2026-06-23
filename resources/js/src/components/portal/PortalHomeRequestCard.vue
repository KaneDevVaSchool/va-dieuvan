<template>
  <RouterLink
    :to="detailRoute"
    class="group/card block rounded-2xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800"
    :data-testid="`portal-home-request-card-${req.id}`"
  >
    <article
      class="overflow-hidden rounded-2xl border bg-white shadow-sm transition group-hover/card:border-va-200/90 group-hover/card:shadow-md"
      :class="cardClass"
    >
      <div class="border-b border-slate-100 px-3 py-4 sm:px-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div class="flex min-w-0 gap-3">
            <div
              v-if="rank != null"
              class="relative flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl ring-1 ring-inset ring-black/5"
              :class="requestTypeIconWrap(req.trip_type)"
              :aria-label="t('portal.home_card_rank', { n: rank })"
            >
              <component
                :is="requestTypeIcon(req.trip_type)"
                class="absolute right-1 top-1 h-3.5 w-3.5 opacity-60"
                :class="requestTypeIconColor(req.trip_type)"
                aria-hidden="true"
              />
              <span class="font-display text-xl font-bold leading-none text-slate-900">{{ rank }}</span>
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <p class="font-mono text-lg font-bold tracking-tight text-slate-900 group-hover/card:text-va-900">
                  {{ refCode }}
                </p>
                <StatusBadge :status="req.status" size="sm" />
                <span
                  v-if="req.trip?.status"
                  class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700"
                >
                  {{ labelTripStatus(req.trip.status) }}
                </span>
              </div>
              <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                <span
                  v-if="tripTypeLabel"
                  class="rounded-full px-2 py-0.5 text-xs font-semibold"
                  :class="requestTypeBadgeClass(req.trip_type)"
                >
                  {{ tripTypeLabel }}
                </span>
                <span
                  v-if="req.is_urgent"
                  class="rounded-md bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-800"
                >
                  {{ t('portal.badge_urgent') }}
                </span>
                <span
                  v-if="req.dispatch_request_template_id"
                  class="inline-flex items-center gap-0.5 rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-900"
                >
                  <ArrowPathIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                  {{ t('portal.home_card_recurring') }}
                </span>
              </div>
              <p class="mt-2 text-xs text-slate-500">
                <span class="font-medium text-slate-600">{{ t('portal.table_created') }}:</span>
                {{ createdFmt }}
                <template v-if="recurringPlanLabel">
                  <span class="mx-1 text-slate-300" aria-hidden="true">·</span>
                  <span class="font-medium text-slate-600">{{ t('portal.home_card_plan') }}:</span>
                  {{ recurringPlanLabel }}
                </template>
              </p>
            </div>
          </div>
          <ChevronRightIcon
            class="hidden h-6 w-6 shrink-0 text-slate-300 transition group-hover/card:text-va-700 sm:block"
            aria-hidden="true"
          />
        </div>
      </div>

      <div class="grid grid-cols-1 gap-3 px-3 py-4 sm:px-4 md:grid-cols-2 xl:grid-cols-4 xl:gap-4">
        <div class="min-w-0 rounded-xl border border-slate-100/90 bg-slate-50/50 p-3">
          <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-700">
            <MapPinIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            {{ t('portal.table_origin') }}
          </div>
          <p class="mt-1 line-clamp-2 text-sm font-semibold leading-snug text-slate-900">
            {{ emptyText.origin(req.origin) }}
          </p>
          <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
            {{ t('portal.table_time') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold tabular-nums text-teal-800">
            {{ departFmt || emptyText.departAt('') }}
          </p>
        </div>
        <div class="min-w-0 rounded-xl border border-slate-100/90 bg-slate-50/50 p-3">
          <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-rose-700">
            <MapPinIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            {{ t('portal.table_destination') }}
          </div>
          <p class="mt-1 line-clamp-2 text-sm font-semibold leading-snug text-slate-900">
            {{ emptyText.destination(req.destination) }}
          </p>
          <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
            {{ t('requests_page.col_arrive_by') }}
          </p>
          <p
            class="mt-0.5 text-sm font-semibold tabular-nums"
            :class="arriveFmt ? 'text-rose-800' : 'font-normal italic text-slate-400'"
          >
            {{ arriveFmt || t('requests_page.empty_arrive_by') }}
          </p>
        </div>
        <div class="min-w-0 rounded-xl border border-slate-100 bg-white p-3">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
            {{ t('portal.shell.table_dispatcher') }}
          </p>
          <p
            class="mt-1.5 text-sm font-semibold"
            :class="emptyText.portalFieldHasValue(req.trip?.dispatcher?.name) ? 'text-slate-900' : 'italic text-slate-400'"
          >
            {{ dispatcherName }}
          </p>
          <p v-if="dispatcherHint" class="mt-1.5 text-xs leading-snug text-slate-600">
            {{ dispatcherHint }}
          </p>
        </div>
        <div class="min-w-0 rounded-xl border border-slate-100 bg-white p-3">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
            {{ t('portal.shell.table_sla') }}
          </p>
          <div class="mt-1.5 text-sm">
            <span
              v-if="slaKind === 'ok'"
              class="inline-flex items-center gap-1.5 font-medium text-emerald-700"
            >
              <CheckCircleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ t('portal.shell.sla_ok') }}
            </span>
            <span
              v-else-if="slaKind === 'risk'"
              class="inline-flex items-center gap-1.5 font-medium text-amber-800"
            >
              <ExclamationTriangleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ t('portal.shell.sla_risk') }}
            </span>
            <span v-else class="italic text-slate-400">{{ t('portal.shell.sla_na') }}</span>
          </div>
        </div>
      </div>

      <div
        class="flex items-center justify-between gap-3 border-t border-slate-100 px-3 py-3 text-xs text-slate-600 sm:px-4 sm:text-sm"
      >
        <span class="inline-flex min-w-0 items-center gap-1.5">
          <UserPlusIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
          <span class="truncate">{{ passengerSummary }}</span>
        </span>
        <span class="inline-flex shrink-0 items-center gap-1 font-semibold text-va-800">
          {{ t('portal.home_card_view_detail') }}
          <ChevronRightIcon class="h-4 w-4 sm:hidden" aria-hidden="true" />
        </span>
      </div>
    </article>
  </RouterLink>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowPathIcon,
  CheckCircleIcon,
  ChevronRightIcon,
  ExclamationTriangleIcon,
  MapPinIcon,
  UserPlusIcon,
} from '@heroicons/vue/24/outline'
import StatusBadge from '../ui/StatusBadge.vue'
import { portalDetailRouteForRequest } from '../../composables/usePortalExtracurricularModule'
import { usePortalRequestEmptyText } from '../../composables/usePortalRequestEmptyText.js'
import {
  dispatchRequestTypeBadgeClass,
  dispatchRequestTypeIcon,
  dispatchRequestTypeIconColor,
  dispatchRequestTypeIconWrap,
} from '../../composables/useDispatchRequestTypeVisuals.js'
import { formatPortalDepartLine } from '../../util/portalDatetime.js'
import { formatDispatchRequestRefCode, portalRequestPassengerCount } from '../../util/portalRequestFormat.js'
import { labelTripStatus } from '../../util/labels'

const props = defineProps({
  req: { type: Object, required: true },
  rank: { type: Number, default: null },
  highlighted: { type: Boolean, default: false },
})

const { t, locale } = useI18n()
const emptyText = usePortalRequestEmptyText()

const localeKey = computed(() => (locale.value === 'en' ? 'en' : 'vi'))

const detailRoute = computed(() => portalDetailRouteForRequest(props.req))

const refCode = computed(() => formatDispatchRequestRefCode(props.req))

const tripTypeLabel = computed(() => {
  const tt = props.req.trip_type
  if (!tt) return ''
  return t(`dispatch_wizard.trip_short.${tt}`)
})

const recurringPlanLabel = computed(() => {
  const raw = props.req.recurring_plan_label
  return raw && String(raw).trim() ? String(raw).trim() : ''
})

const createdFmt = computed(() => {
  if (!props.req.created_at) return emptyText.createdAt('')
  return formatPortalDepartLine(props.req.created_at, localeKey.value)
})

const departFmt = computed(() =>
  props.req.depart_at ? formatPortalDepartLine(props.req.depart_at, localeKey.value) : '',
)

const arriveFmt = computed(() =>
  props.req.arrive_by ? formatPortalDepartLine(props.req.arrive_by, localeKey.value) : '',
)

const dispatcherName = computed(() => {
  const n = props.req.trip?.dispatcher?.name
  return n && String(n).trim() ? String(n).trim() : emptyText.dispatcher('')
})

const dispatcherHint = computed(() => {
  if (props.req.trip?.status) return ''
  if (props.req.status === 'pending' || props.req.status === 'price_filled') {
    return t('portal.home_card_hint_pending')
  }
  if (props.req.status === 'approved' && !props.req.trip) {
    return t('portal.home_card_hint_no_trip')
  }
  return ''
})

const passengerSummary = computed(() => {
  const n = portalRequestPassengerCount(props.req)
  if (n > 0) return t('requests_page.passengers', { n })
  if (props.req.trip_type === 'cargo') return t('requests_page.cargo')
  return t('requests_page.no_passenger_info')
})

const slaKind = computed(() => {
  const req = props.req
  if (req.status === 'rejected') return 'na'
  if (req.status !== 'pending' && req.status !== 'price_filled') return 'ok'
  if (req.is_urgent) return 'risk'
  if (req.depart_at) {
    const depart = new Date(req.depart_at)
    if (!Number.isNaN(depart.getTime())) {
      const hours = (depart.getTime() - Date.now()) / (1000 * 60 * 60)
      if (hours <= 48) return 'risk'
    }
  }
  return 'ok'
})

const cardClass = computed(() => {
  if (props.highlighted) {
    return 'border-va-300 ring-2 ring-va-500/30 shadow-md shadow-va-900/5'
  }
  if (props.req.is_urgent || isPendingApproval(props.req)) {
    return 'border-l-4 border-l-amber-500 border-y-slate-200/90 border-r-slate-200/90'
  }
  return 'border-slate-200/90'
})

function isPendingApproval(req) {
  const s = req?.status
  return s === 'pending' || s === 'price_filled'
}

const requestTypeIcon = dispatchRequestTypeIcon
const requestTypeIconColor = dispatchRequestTypeIconColor
const requestTypeIconWrap = dispatchRequestTypeIconWrap
const requestTypeBadgeClass = dispatchRequestTypeBadgeClass
</script>
