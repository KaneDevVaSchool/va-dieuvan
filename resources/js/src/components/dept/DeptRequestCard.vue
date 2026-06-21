<template>
  <article
    class="overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:shadow-md dark:bg-slate-900/50"
    :class="cardClass"
  >
    <!-- Header -->
    <div class="border-b border-slate-100 px-3 py-4 sm:px-5 dark:border-slate-800">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 gap-3 sm:gap-4">
          <div
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl sm:h-12 sm:w-12"
            :class="requestTypeIconWrap(req.trip_type)"
          >
            <component
              :is="requestTypeIcon(req.trip_type)"
              class="h-6 w-6"
              :class="requestTypeIconColor(req.trip_type)"
              aria-hidden="true"
            />
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <div class="min-w-0">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('requests_page.col_id') }}
                </p>
                <button
                  type="button"
                  class="mt-0.5 inline-block font-mono text-lg font-bold tracking-tight text-slate-900 underline decoration-slate-300 underline-offset-2 transition hover:text-va-800 hover:decoration-va-400 dark:text-slate-100"
                  @click="$emit('detail', req.id)"
                >
                  {{ t('dept.request_code_short', { id: req.id }) }}
                </button>
              </div>
              <span
                class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                :class="requestTypeBadgeClass(req.trip_type)"
              >
                {{ tripTypeLabel }}
              </span>
              <span
                v-if="req.is_urgent"
                class="inline-flex items-center gap-0.5 rounded-md bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-800 dark:bg-rose-950/60 dark:text-rose-200"
              >
                <BoltIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                {{ t('requests_page.filter_priority_urgent') }}
              </span>
              <span
                v-if="req.dispatch_request_template_id"
                class="inline-flex items-center gap-0.5 rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-900 dark:bg-indigo-950/50 dark:text-indigo-100"
              >
                <ArrowPathIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                {{ t('requests_page.badge_recurring') }}
              </span>
            </div>
            <p class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('requests_page.meta_created') }}
            </p>
            <p class="mt-0.5 text-sm font-medium tabular-nums text-slate-800 dark:text-slate-200">
              {{ formatDateTime(req.created_at) }}
            </p>
          </div>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2 lg:justify-end">
          <span
            class="rounded-full px-2.5 py-1 text-xs font-semibold"
            :class="statusBadgeClass"
          >
            {{ statusLabel }}
          </span>
          <span
            v-if="priceLabel"
            class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold tabular-nums text-slate-800 dark:bg-slate-800 dark:text-slate-200"
          >
            <BanknotesIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            {{ priceLabel }}
          </span>
        </div>
      </div>
    </div>

    <!-- Body grid: origin · destination · requester -->
    <div class="grid grid-cols-1 gap-4 px-3 py-4 sm:px-4 md:grid-cols-2 md:gap-5 xl:grid-cols-3">
      <div class="min-w-0">
        <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">
          <MapPinIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ t('trips_page.col_origin') }}
        </div>
        <p class="mt-1 text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100 sm:text-base">
          {{ displayPlaceLabel(req.origin, 'empty_origin') }}
        </p>
        <div class="mt-2">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ t('requests_page.col_depart_at') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold tabular-nums text-teal-800 dark:text-teal-300">
            {{ formatDepartDate(req.depart_at) }}
          </p>
        </div>
      </div>
      <div class="min-w-0">
        <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-400">
          <MapPinIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ t('trips_page.col_destination') }}
        </div>
        <p class="mt-1 text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100 sm:text-base">
          {{ displayPlaceLabel(req.destination, 'empty_destination') }}
        </p>
        <div v-if="req.arrive_by" class="mt-2">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ t('requests_page.col_arrive_by') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold tabular-nums text-rose-800 dark:text-rose-300">
            {{ formatDepartDate(req.arrive_by) }}
          </p>
        </div>
      </div>
      <div class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-800/40">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          {{ t('requests_page.col_requester') }}
        </p>
        <div class="mt-2 flex items-start gap-2">
          <UserAvatar
            :name="req.requester?.name || ''"
            :email="req.requester?.email || ''"
            :avatar-url="req.requester?.avatar_url"
            :title="req.requester?.name || ''"
            size="md"
          />
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
              {{ req.requester?.name || t('requests_page.empty_requester') }}
            </p>
            <p class="mt-1 inline-flex items-center gap-1 text-xs text-slate-600 dark:text-slate-400">
              <UserPlusIcon class="h-3.5 w-3.5 shrink-0 text-slate-400" aria-hidden="true" />
              {{ passengerSummary }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Rejection reason -->
    <div
      v-if="req.rejection_reason && showRejection"
      class="border-t border-rose-100 bg-rose-50/60 px-3 py-3 text-sm leading-relaxed text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/20 dark:text-rose-200 sm:px-5"
    >
      <span class="font-semibold">{{ t('dept.reject_btn') }}:</span>
      {{ req.rejection_reason }}
    </div>

    <!-- Footer: actions -->
    <div class="flex flex-col gap-3 border-t border-slate-100 px-3 py-3 dark:border-slate-800 sm:px-4 sm:py-3.5 md:flex-row md:items-center md:justify-between">
      <span class="inline-flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 sm:text-sm">
        <ArrowsRightLeftIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
        {{ tripTypeLabel }}
      </span>
      <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:justify-end">
        <button
          type="button"
          class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 sm:min-h-0 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          @click="$emit('detail', req.id)"
        >
          <EyeIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ t('dept.view_detail') }}
        </button>
        <template v-if="showActions">
          <Button
            class="min-h-[44px] justify-center !bg-[#800020] hover:!bg-[#6b0a1f] sm:min-h-0"
            :loading="acting"
            :disabled="acting"
            @click="$emit('approve', req.id)"
          >
            <CheckCircleIcon class="mr-1.5 h-5 w-5" aria-hidden="true" />
            {{ t('dept.approve_btn') }}
          </Button>
          <Button
            variant="danger"
            class="min-h-[44px] justify-center sm:min-h-0"
            :loading="acting"
            :disabled="acting"
            @click="$emit('reject', req.id)"
          >
            {{ t('dept.reject_btn') }}
          </Button>
        </template>
      </div>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowPathIcon,
  ArrowsRightLeftIcon,
  BanknotesIcon,
  BoltIcon,
  BriefcaseIcon,
  CheckCircleIcon,
  CubeIcon,
  EyeIcon,
  MapPinIcon,
  TruckIcon,
  UserPlusIcon,
} from '@heroicons/vue/24/outline'
import Button from '../ui/Button.vue'
import UserAvatar from '../branding/UserAvatar.vue'
import { labelTripType, labelRequestStatus } from '../../util/labels'
import { formatListDateTime } from '../../util/datetime'
import { dispatchRequestDisplayPassengerCount } from '../../util/dispatchRequestPassengers'

const props = defineProps({
  req: { type: Object, required: true },
  /** Phiếu đã có giá — nhấn mạnh viền như mockup */
  emphasize: { type: Boolean, default: false },
  showActions: { type: Boolean, default: false },
  acting: { type: Boolean, default: false },
  showRejection: { type: Boolean, default: false },
})

defineEmits(['detail', 'approve', 'reject'])

const { t, locale } = useI18n()

const cardClass = computed(() => {
  if (props.req.is_urgent) {
    return 'border-l-4 border-l-amber-500 border-y-slate-200/90 border-r-slate-200/90 dark:border-y-slate-700 dark:border-r-slate-700'
  }
  if (props.emphasize) {
    return 'border-rose-200/80 ring-1 ring-rose-100/80 dark:border-rose-900/60'
  }
  return 'border-slate-200/90 dark:border-slate-700'
})

const tripTypeLabel = computed(() => {
  const k = props.req.trip_type
  if (!k) return t('requests_page.empty_trip_type')
  return labelTripType(k)
})

const statusLabel = computed(() => {
  const s = props.req.status
  if (s === 'price_filled') return t('dept.badge_new_pending')
  if (s === 'pending') return t('dept.badge_waiting_price')
  return labelRequestStatus(s)
})

const statusBadgeClass = computed(() => {
  const s = props.req.status
  if (s === 'price_filled') return 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-200'
  if (s === 'pending') return 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-100'
  if (s === 'approved') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200'
  if (s === 'rejected') return 'bg-rose-50 text-rose-800 dark:bg-rose-950/40 dark:text-rose-200'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
})

const passengerSummary = computed(() => {
  const n = dispatchRequestDisplayPassengerCount(props.req)
  if (n > 0) return t('requests_page.passengers', { n })
  if (props.req.trip_type === 'cargo') return t('requests_page.cargo')
  return t('requests_page.no_passenger_info')
})

const priceLabel = computed(() => {
  const p = props.req.service_price
  if (p == null || p === '') return null
  const n = Number(p)
  if (!Number.isFinite(n)) return null
  const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  const num = new Intl.NumberFormat(loc).format(n)
  return `${num} ${t('dept.currency_suffix')}`
})

function dateLocaleKey() {
  return locale.value === 'en' ? 'en' : 'vi'
}

function formatDateTime(v) {
  if (!v) return t('requests_page.empty_datetime')
  return formatListDateTime(v, dateLocaleKey()) || t('requests_page.empty_datetime')
}

function formatDepartDate(v) {
  if (!v) return t('requests_page.empty_depart_at')
  return formatListDateTime(v, dateLocaleKey()) || t('requests_page.empty_depart_at')
}

function displayPlaceLabel(value, emptyKey = 'empty_route') {
  const s = value != null ? String(value).trim() : ''
  return s || t(`requests_page.${emptyKey}`)
}

// ── Card visuals (mirror mng requests list cards) ──
function requestTypeIcon(tt) {
  if (tt === 'door_to_door') return TruckIcon
  if (tt === 'point_to_point') return MapPinIcon
  if (tt === 'business') return BriefcaseIcon
  if (tt === 'cargo') return CubeIcon
  return MapPinIcon
}

function requestTypeIconColor(tt) {
  if (tt === 'door_to_door') return 'text-sky-600 dark:text-sky-400'
  if (tt === 'point_to_point') return 'text-emerald-600 dark:text-emerald-400'
  if (tt === 'business') return 'text-amber-600 dark:text-amber-400'
  if (tt === 'cargo') return 'text-orange-600 dark:text-orange-400'
  return 'text-slate-600 dark:text-slate-400'
}

function requestTypeIconWrap(tt) {
  if (tt === 'door_to_door') return 'bg-sky-100 dark:bg-sky-950/40'
  if (tt === 'point_to_point') return 'bg-emerald-100 dark:bg-emerald-950/40'
  if (tt === 'business') return 'bg-amber-100 dark:bg-amber-950/40'
  if (tt === 'cargo') return 'bg-orange-100 dark:bg-orange-950/40'
  return 'bg-slate-100 dark:bg-slate-800/60'
}

function requestTypeBadgeClass(tt) {
  if (tt === 'door_to_door') return 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-200'
  if (tt === 'point_to_point') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-200'
  if (tt === 'business') return 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-100'
  if (tt === 'cargo') return 'bg-orange-100 text-orange-900 dark:bg-orange-950/50 dark:text-orange-100'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
}
</script>
