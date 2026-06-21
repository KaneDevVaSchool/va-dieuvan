<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowTopRightOnSquareIcon,
  ClockIcon,
  CubeIcon,
  DocumentTextIcon,
  EyeIcon,
  MapPinIcon,
  ScaleIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'
import { labelCargoStatus } from '../../util/labels'
import { formatListDateTime } from '../../util/datetime'
import { formatDispatchRequestRefCode } from '../../util/portalRequestFormat'

const props = defineProps({
  shipment: { type: Object, required: true },
})

const { t, locale } = useI18n()

function dateKey() {
  return locale.value === 'en' ? 'en' : 'vi'
}

function fmt(v) {
  if (!v) return t('cargo_page.empty_datetime')
  return formatListDateTime(v, dateKey()) || t('cargo_page.empty_datetime')
}

const trackingCode = computed(() => {
  const s = props.shipment
  return s.tracking_code || `CGO-${String(s.id).padStart(4, '0')}`
})

const statusPillClass = computed(() => {
  const map = {
    pending: 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-100',
    picked_up: 'bg-sky-100 text-sky-900 dark:bg-sky-950/50 dark:text-sky-100',
    in_transit: 'bg-teal-100 text-teal-900 dark:bg-teal-950/50 dark:text-teal-100',
    delivered: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100',
    failed: 'bg-rose-100 text-rose-900 dark:bg-rose-950/50 dark:text-rose-100',
    cancelled: 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
  }
  return map[props.shipment.status] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200'
})

const iconWrapClass = computed(() => {
  const map = {
    pending: 'bg-amber-100 dark:bg-amber-950/40',
    picked_up: 'bg-sky-100 dark:bg-sky-950/40',
    in_transit: 'bg-teal-100 dark:bg-teal-950/40',
    delivered: 'bg-emerald-100 dark:bg-emerald-950/40',
    failed: 'bg-rose-100 dark:bg-rose-950/40',
    cancelled: 'bg-slate-100 dark:bg-slate-800/60',
  }
  return map[props.shipment.status] ?? 'bg-slate-100 dark:bg-slate-800/60'
})

const iconColorClass = computed(() => {
  const map = {
    pending: 'text-amber-600 dark:text-amber-400',
    picked_up: 'text-sky-600 dark:text-sky-400',
    in_transit: 'text-teal-600 dark:text-teal-400',
    delivered: 'text-emerald-600 dark:text-emerald-400',
    failed: 'text-rose-600 dark:text-rose-400',
    cancelled: 'text-slate-500 dark:text-slate-400',
  }
  return map[props.shipment.status] ?? 'text-slate-600 dark:text-slate-400'
})

function dispatchRequestId(s) {
  return s.dispatch_request_id ?? s.dispatch_request?.id ?? null
}

function dispatchRequestEntity(s) {
  const id = dispatchRequestId(s)
  if (id == null) return null
  return s.dispatch_request ?? s.dispatchRequest ?? { id }
}

const dispatchRequestRefCode = computed(() => {
  const dr = dispatchRequestEntity(props.shipment)
  if (!dr) return ''
  return formatDispatchRequestRefCode(dr) || `REQ-${String(dr.id).padStart(3, '0')}`
})

function driverInitials(name) {
  if (!name) return '?'
  const p = String(name).trim().split(/\s+/)
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

const weightDisplay = computed(() => {
  const w = props.shipment.weight_kg
  if (!w) return null
  return `${w} kg`
})

const quantityDisplay = computed(() => {
  const q = props.shipment.quantity
  if (!q) return null
  return t('cargo_page.card_qty', { n: q })
})
</script>

<template>
  <article
    class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/50"
    :data-testid="`cargo-card-${shipment.id}`"
  >
    <!-- Header -->
    <div class="border-b border-slate-100 px-3 py-4 sm:px-5 dark:border-slate-800">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 gap-3 sm:gap-4">
          <div
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl sm:h-12 sm:w-12"
            :class="iconWrapClass"
          >
            <CubeIcon class="h-6 w-6" :class="iconColorClass" aria-hidden="true" />
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('cargo_page.card_code') }}
            </p>
            <RouterLink
              :to="`/cargo/${shipment.id}`"
              class="mt-0.5 inline-block font-mono text-lg font-bold tracking-tight text-slate-900 underline decoration-slate-300 underline-offset-2 hover:text-va-800 hover:decoration-va-400 dark:text-slate-100"
              :data-testid="`cargo-card-link-${shipment.id}`"
            >
              {{ trackingCode }}
            </RouterLink>
            <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2 xl:grid-cols-3">
              <div class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('cargo_page.card_created') }}
                </dt>
                <dd class="mt-0.5 font-medium tabular-nums text-slate-800 dark:text-slate-200">
                  {{ fmt(shipment.created_at) }}
                </dd>
              </div>
              <div v-if="shipment.sender_name" class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('cargo_page.card_sender') }}
                </dt>
                <dd class="mt-0.5 truncate font-medium text-slate-800 dark:text-slate-200">
                  {{ shipment.sender_name }}
                </dd>
              </div>
              <div v-if="shipment.receiver_name" class="min-w-0">
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('cargo_page.card_receiver') }}
                </dt>
                <dd class="mt-0.5 truncate font-medium text-slate-800 dark:text-slate-200">
                  {{ shipment.receiver_name }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2 lg:justify-end">
          <span :class="['rounded-full px-2.5 py-1 text-xs font-semibold sm:text-sm', statusPillClass]">
            {{ labelCargoStatus(shipment.status) }}
          </span>
          <span
            v-if="weightDisplay || quantityDisplay"
            class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300"
          >
            {{ [quantityDisplay, weightDisplay].filter(Boolean).join(' · ') }}
          </span>
        </div>
      </div>
    </div>

    <!-- Body: route, driver, ops -->
    <div class="grid grid-cols-1 gap-4 px-3 py-4 sm:px-4 md:grid-cols-2 md:gap-5 xl:grid-cols-4">
      <!-- Pickup -->
      <div class="min-w-0">
        <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">
          <MapPinIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ t('cargo_page.card_pickup') }}
        </div>
        <p class="mt-1 text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100 sm:text-base">
          {{ shipment.pickup_address || t('cargo_page.empty_address') }}
        </p>
        <div v-if="shipment.pickup_at || shipment.expected_pickup_at" class="mt-2">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ shipment.pickup_at ? t('cargo_page.card_picked_at') : t('cargo_page.card_expected_pickup') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold tabular-nums text-teal-800 dark:text-teal-300">
            {{ fmt(shipment.pickup_at || shipment.expected_pickup_at) }}
          </p>
        </div>
      </div>

      <!-- Delivery -->
      <div class="min-w-0">
        <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-rose-700 dark:text-rose-400">
          <MapPinIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ t('cargo_page.card_delivery') }}
        </div>
        <p class="mt-1 text-sm font-semibold leading-snug text-slate-900 dark:text-slate-100 sm:text-base">
          {{ shipment.delivery_address || t('cargo_page.empty_address') }}
        </p>
        <div v-if="shipment.delivered_at || shipment.expected_delivery_at" class="mt-2">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ shipment.delivered_at ? t('cargo_page.card_delivered_at') : t('cargo_page.card_expected_delivery') }}
          </p>
          <p class="mt-0.5 text-sm font-semibold tabular-nums text-rose-800 dark:text-rose-300">
            {{ fmt(shipment.delivered_at || shipment.expected_delivery_at) }}
          </p>
        </div>
      </div>

      <!-- Driver / vehicle -->
      <div class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-800/40">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          {{ t('cargo_page.card_driver_vehicle') }}
        </p>
        <div v-if="shipment.trip?.driver" class="mt-2 flex items-start gap-2">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800 dark:bg-teal-950 dark:text-teal-200">
            {{ driverInitials(shipment.trip.driver.full_name) }}
          </div>
          <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
              {{ shipment.trip.driver.full_name }}
            </p>
            <p v-if="shipment.trip.driver.phone" class="mt-0.5 text-xs tabular-nums text-slate-600 dark:text-slate-400">
              {{ shipment.trip.driver.phone }}
            </p>
            <p v-if="shipment.trip?.vehicle" class="mt-1 text-xs text-slate-600 dark:text-slate-400">
              <span class="font-medium">{{ shipment.trip.vehicle.license_plate }}</span>
            </p>
          </div>
        </div>
        <div v-else class="mt-2 flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-300">
            <TruckIcon class="h-5 w-5" aria-hidden="true" />
          </div>
          <span>{{ t('cargo_page.card_no_driver') }}</span>
        </div>
      </div>

      <!-- Ops meta -->
      <div class="min-w-0 rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-800 dark:bg-slate-800/40">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          {{ t('cargo_page.card_ops_meta') }}
        </p>
        <ul class="mt-2 space-y-2 text-xs text-slate-700 dark:text-slate-300 sm:text-sm">
          <li v-if="shipment.trip_id">
            <span class="font-semibold text-slate-600 dark:text-slate-400">{{ t('cargo_page.card_trip') }}:</span>
            <RouterLink
              :to="`/trips/${shipment.trip_id}`"
              class="ml-1 font-medium text-sky-700 hover:underline dark:text-sky-400"
            >
              TRP-{{ String(shipment.trip_id).padStart(4, '0') }}
            </RouterLink>
          </li>
          <li v-if="dispatchRequestId(shipment)">
            <span class="font-semibold text-slate-600 dark:text-slate-400">{{ t('cargo_page.card_request') }}:</span>
            <RouterLink
              :to="`/requests/${dispatchRequestId(shipment)}`"
              class="ml-1 font-medium text-violet-700 hover:underline dark:text-violet-400"
            >
              {{ dispatchRequestRefCode }}
            </RouterLink>
          </li>
          <li v-if="shipment.notes && String(shipment.notes).trim()">
            <span class="font-semibold text-slate-600 dark:text-slate-400">{{ t('cargo_page.card_notes') }}:</span>
            <span class="ml-1 line-clamp-2 text-slate-700 dark:text-slate-300">{{ shipment.notes }}</span>
          </li>
          <li v-if="!shipment.trip_id && !dispatchRequestId(shipment)" class="text-slate-400 dark:text-slate-500">
            {{ t('cargo_page.card_no_links') }}
          </li>
        </ul>
      </div>
    </div>

    <!-- Footer -->
    <div class="flex flex-col gap-3 border-t border-slate-100 px-3 py-3 dark:border-slate-800 sm:px-4 sm:py-3.5 md:flex-row md:items-center md:justify-between">
      <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-600 dark:text-slate-400 sm:text-sm">
        <span v-if="weightDisplay" class="inline-flex items-center gap-1.5">
          <ScaleIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
          {{ weightDisplay }}
        </span>
        <span v-if="shipment.sla_due_at" class="inline-flex items-center gap-1.5">
          <ClockIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
          {{ t('cargo_page.sla_prefix') }} {{ fmt(shipment.sla_due_at) }}
        </span>
        <span v-if="shipment.cod_amount" class="inline-flex items-center gap-1.5">
          <span class="h-4 w-4 shrink-0 text-center text-slate-400 text-[11px] font-bold leading-none">₫</span>
          COD: {{ new Intl.NumberFormat('vi-VN').format(shipment.cod_amount) }}
        </span>
      </div>
      <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:justify-end">
        <RouterLink
          :to="`/cargo/${shipment.id}`"
          class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 sm:min-h-0 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          :data-testid="`cargo-action-detail-${shipment.id}`"
        >
          <EyeIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ t('cargo_page.action_detail') }}
        </RouterLink>
        <a
          v-if="shipment.pickup_address && shipment.delivery_address"
          :href="`https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(shipment.pickup_address)}&destination=${encodeURIComponent(shipment.delivery_address)}`"
          target="_blank"
          rel="noopener noreferrer"
          class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-teal-200 bg-teal-50 px-3 py-2.5 text-sm font-medium text-teal-900 hover:bg-teal-100 sm:min-h-0 dark:border-teal-900 dark:bg-teal-950/60 dark:text-teal-100"
          :data-testid="`cargo-action-map-${shipment.id}`"
        >
          <ArrowTopRightOnSquareIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ t('cargo_page.action_track') }}
        </a>
        <RouterLink
          v-if="dispatchRequestId(shipment)"
          :to="`/requests/${dispatchRequestId(shipment)}`"
          class="inline-flex min-h-[44px] items-center justify-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 px-3 py-2.5 text-sm font-medium text-violet-900 hover:bg-violet-100 sm:min-h-0 dark:border-violet-900 dark:bg-violet-950/60 dark:text-violet-100"
          :data-testid="`cargo-action-request-${shipment.id}`"
        >
          <DocumentTextIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ t('cargo_page.card_request') }}
        </RouterLink>
      </div>
    </div>
  </article>
</template>
