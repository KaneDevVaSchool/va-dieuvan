<template>
  <article
    class="overflow-hidden rounded-[1.35rem] bg-driver-card shadow-[0_12px_40px_-16px_rgba(0,0,0,0.65)] ring-1 ring-white/[0.06] transition-transform duration-200 hover:ring-driver-accent/20"
  >
    <RouterLink
      :to="`/driver/costs/${cost.id}`"
      class="block px-4 pb-4 pt-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-driver-accent/60 sm:px-5 sm:pt-5"
    >
      <div class="flex flex-wrap items-start justify-between gap-2">
        <span
          class="inline-flex items-center rounded-full px-3.5 py-1.5 text-sm font-bold sm:text-base"
          :class="statusBadgeClass(cost.status)"
        >
          {{ statusLabel(cost.status) }}
        </span>
        <span
          v-if="cost.trip_id"
          class="font-mono text-sm font-bold tabular-nums text-[#7fdcc8] sm:text-base"
        >
          {{ tripCode }}
        </span>
        <span
          v-else
          class="rounded-full bg-driver-accent/12 px-3 py-1 text-sm font-bold text-driver-accent ring-1 ring-driver-accent/25"
        >
          {{ standaloneBadge }}
        </span>
      </div>

      <p
        v-if="flowLine.text"
        class="mt-3 flex items-start gap-1.5 text-sm font-medium leading-snug sm:text-base"
        :class="flowToneClass"
      >
        <component :is="flowIcon" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
        <span class="min-w-0">{{ flowLine.text }}</span>
      </p>

      <div class="mt-4 flex gap-4 sm:gap-5">
        <div
          class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-driver-surface ring-1 ring-white/[0.05] sm:h-[4.25rem] sm:w-[4.25rem]"
          aria-hidden="true"
        >
          <component :is="typeIcon" class="h-8 w-8 text-driver-accent sm:h-9 sm:w-9" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-driver-muted/80 sm:text-xs">
            {{ t('driver_costs.card_amount_label') }}
          </p>
          <p class="mt-0.5 text-2xl font-bold tabular-nums leading-tight text-driver-ink sm:text-[1.75rem]">
            {{ formatVnd(cost.amount) }}
          </p>
          <p class="mt-2 text-lg font-semibold leading-snug text-driver-ink/95 sm:text-xl">
            {{ typeLabel(cost.type) }}
          </p>
          <p v-if="cost.description" class="mt-2 line-clamp-2 text-base leading-snug text-driver-muted">
            {{ cost.description }}
          </p>
        </div>
        <ChevronRightIcon class="mt-2 h-7 w-7 shrink-0 text-driver-muted/40" aria-hidden="true" />
      </div>

      <div
        v-if="hasTripInfo"
        class="mt-4 space-y-2 rounded-xl border border-white/[0.06] bg-driver-surface/60 p-3.5 sm:p-4"
      >
        <div class="flex flex-wrap items-center gap-2">
          <span class="text-sm font-semibold text-driver-muted sm:text-base">
            {{ t('driver_costs.card_trip_label', { code: tripCode }) }}
          </span>
          <span
            v-if="tripTypeLabel"
            class="rounded-md px-2.5 py-0.5 text-xs font-semibold ring-1 sm:text-sm"
            :class="tripTypeChipClass"
          >
            {{ tripTypeLabel }}
          </span>
        </div>
        <p
          v-if="timeRange"
          class="flex items-center gap-2 text-base font-semibold tabular-nums text-[#7fdcc8] sm:text-lg"
        >
          <ClockIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('driver_costs.card_time', { range: timeRange }) }}
        </p>
        <div v-if="originLine" class="flex items-start gap-2 text-base leading-snug sm:text-lg">
          <span class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-emerald-400" aria-hidden="true" />
          <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-driver-muted/80">
              {{ t('driver_costs.card_pickup') }}
            </p>
            <p class="font-medium text-driver-ink">{{ originLine }}</p>
          </div>
        </div>
        <div v-if="destinationLine" class="flex items-start gap-2 text-base leading-snug sm:text-lg">
          <span class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-rose-400" aria-hidden="true" />
          <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-driver-muted/80">
              {{ t('driver_costs.card_dropoff') }}
            </p>
            <p class="font-medium text-driver-ink">{{ destinationLine }}</p>
          </div>
        </div>
      </div>

      <p class="mt-4 flex items-center gap-2 text-base text-driver-muted sm:text-lg">
        <CalendarIcon class="h-5 w-5 shrink-0 opacity-80" aria-hidden="true" />
        {{ submittedLabel }}
      </p>
    </RouterLink>

    <div v-if="cost.trip_id" class="border-t border-white/[0.06] px-4 pb-4 pt-3 sm:px-5">
      <RouterLink
        :to="`/driver/trips/${cost.trip_id}`"
        class="flex min-h-[52px] w-full items-center justify-center rounded-2xl bg-driver-surface text-base font-bold text-driver-accent ring-1 ring-driver-accent/25 transition hover:bg-driver-elevated active:scale-[0.99] sm:text-lg"
      >
        {{ tripCta }}
      </RouterLink>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import {
  BanknotesIcon,
  CalendarIcon,
  CheckCircleIcon,
  ChevronRightIcon,
  ClockIcon,
  FireIcon,
  MapIcon,
  MapPinIcon,
  TruckIcon,
  WrenchScrewdriverIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import {
  tripDestination,
  tripOrigin,
  tripOutboundInboundTimeRange,
  tripServiceTypeCalendarLabel,
  tripTypeBadgeClass,
} from '../../../composables/useDriverTripDisplay'
import { formatTripCode, formatVnd } from '../../../util/labels'

const props = defineProps({
  cost: { type: Object, required: true },
  statusLabel: { type: Function, required: true },
  typeLabel: { type: Function, required: true },
  tripCta: { type: String, required: true },
  standaloneBadge: { type: String, default: '' },
})

const { t, locale } = useI18n()

const localeTag = computed(() => (locale.value === 'vi' ? 'vi' : 'en'))

const trip = computed(() => props.cost?.trip ?? null)

const tripCode = computed(() => formatTripCode(props.cost?.trip_id ?? trip.value?.id))

const tripTypeLabel = computed(() =>
  trip.value ? tripServiceTypeCalendarLabel(trip.value, t) : '',
)
const tripTypeChipClass = computed(() => (trip.value ? tripTypeBadgeClass(trip.value) : ''))

const originLine = computed(() => {
  if (!trip.value) return ''
  const o = tripOrigin(trip.value)
  return o && o !== '—' ? o : ''
})

const destinationLine = computed(() => {
  if (!trip.value) return ''
  const d = tripDestination(trip.value)
  return d && d !== '—' ? d : ''
})

const timeRange = computed(() => {
  if (!trip.value) return ''
  return tripOutboundInboundTimeRange(trip.value, localeTag.value, t) || ''
})

const hasTripInfo = computed(
  () => props.cost?.trip_id && (originLine.value || destinationLine.value || timeRange.value),
)

function normType(type) {
  return String(type ?? '')
    .trim()
    .toLowerCase()
}

const typeIcon = computed(() => {
  const icons = {
    fuel: FireIcon,
    toll: MapIcon,
    parking: MapPinIcon,
    meal: BanknotesIcon,
    wash: TruckIcon,
    repair: WrenchScrewdriverIcon,
  }
  return icons[normType(props.cost?.type)] ?? BanknotesIcon
})

function statusBadgeClass(st) {
  if (st === 'confirmed')
    return 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-400/25'
  if (st === 'rejected') return 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-400/25'
  if (st === 'submitted') return 'bg-amber-500/15 text-amber-200 ring-1 ring-amber-400/25'
  if (st === 'draft') return 'bg-white/[0.08] text-driver-muted ring-1 ring-white/10'
  return 'bg-white/[0.06] text-driver-ink ring-1 ring-white/10'
}

function fmtDecidedAt(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  const loc = localeTag.value === 'vi' ? 'vi-VN' : 'en-US'
  return d.toLocaleString(loc, { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false })
}

// Dòng làm rõ luồng duyệt cho tài xế (Hình 5): đang chờ ai duyệt / đã duyệt / từ chối + lý do.
const flowLine = computed(() => {
  const st = props.cost?.status
  if (st === 'confirmed') {
    const who = String(props.cost?.confirmer?.name ?? '').trim()
    const when = fmtDecidedAt(props.cost?.confirmed_at)
    if (who && when) return { tone: 'ok', text: t('driver_costs.flow_approved_by_at', { name: who, time: when }) }
    if (when) return { tone: 'ok', text: t('driver_costs.flow_approved_at', { time: when }) }
    return { tone: 'ok', text: t('driver_costs.flow_approved') }
  }
  if (st === 'rejected') {
    const reason = String(props.cost?.rejection_reason ?? '').trim()
    return { tone: 'bad', text: reason ? t('driver_costs.flow_rejected_reason', { reason }) : t('driver_costs.flow_rejected') }
  }
  return { tone: 'pending', text: t('driver_costs.flow_pending') }
})

const flowIcon = computed(() => {
  if (flowLine.value.tone === 'ok') return CheckCircleIcon
  if (flowLine.value.tone === 'bad') return XCircleIcon
  return ClockIcon
})

const flowToneClass = computed(() => {
  if (flowLine.value.tone === 'ok') return 'text-emerald-300'
  if (flowLine.value.tone === 'bad') return 'text-rose-300'
  return 'text-amber-200'
})

const submittedLabel = computed(() => {
  const raw = props.cost?.created_at
  if (!raw) return t('driver_costs.card_submitted_unknown')
  const d = new Date(raw)
  if (Number.isNaN(d.getTime())) return t('driver_costs.card_submitted_unknown')
  const loc = localeTag.value === 'vi' ? 'vi-VN' : 'en-US'
  const formatted = d.toLocaleString(loc, {
    weekday: 'long',
    day: 'numeric',
    month: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  })
  return t('driver_costs.card_submitted', { time: formatted })
})
</script>
