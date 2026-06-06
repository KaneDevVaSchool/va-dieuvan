<template>
  <article
    class="overflow-hidden rounded-[1.35rem] bg-driver-card shadow-[0_12px_40px_-16px_rgba(0,0,0,0.65)] ring-1 ring-white/[0.06] transition-transform duration-200 hover:ring-driver-accent/20"
  >
    <RouterLink
      :to="`/driver/costs/${cost.id}`"
      class="block px-4 pb-3 pt-4 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-driver-accent/60"
    >
      <div class="flex items-start justify-between gap-3">
        <span
          class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold sm:text-[13px]"
          :class="statusBadgeClass(cost.status)"
        >
          {{ statusLabel(cost.status) }}
        </span>
        <span v-if="cost.trip_id" class="shrink-0 text-sm tabular-nums text-driver-muted">#{{ cost.trip_id }}</span>
        <span
          v-else
          class="shrink-0 rounded-full bg-driver-accent/12 px-2.5 py-0.5 text-xs font-semibold text-driver-accent ring-1 ring-driver-accent/25"
        >
          {{ standaloneBadge }}
        </span>
      </div>
      <div class="mt-4 flex gap-4">
        <div
          class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-driver-surface ring-1 ring-white/[0.05]"
          aria-hidden="true"
        >
          <FuelIcon v-if="normType(cost.type) === 'fuel'" class="h-7 w-7 text-driver-accent" />
          <RoadIcon v-else-if="normType(cost.type) === 'toll'" class="h-7 w-7 text-driver-accent" />
          <ParkingIcon v-else-if="normType(cost.type) === 'parking'" class="h-7 w-7 text-driver-accent" />
          <MoneyIcon v-else class="h-7 w-7 text-driver-accent" />
        </div>
        <div class="min-w-0 flex-1">
          <p class="text-xl font-bold tabular-nums leading-tight text-driver-ink sm:text-2xl">
            {{ formatVnd(cost.amount) }}
            <span v-if="cost.currency && cost.currency !== 'VND'" class="ml-1 text-base font-semibold text-driver-muted">
              {{ cost.currency }}
            </span>
          </p>
          <p class="mt-2 text-base leading-snug text-driver-muted">
            {{ typeLabel(cost.type) }}
          </p>
          <p v-if="cost.description" class="mt-1 line-clamp-2 text-sm text-driver-muted/90">
            {{ cost.description }}
          </p>
          <p class="mt-3 flex items-center gap-2 text-sm text-driver-muted/85">
            <CalendarIcon class="h-4 w-4 shrink-0 opacity-80" aria-hidden="true" />
            {{ departLabel }}
          </p>
        </div>
        <ChevronRightIcon class="mt-1 h-6 w-6 shrink-0 text-driver-muted/40" aria-hidden="true" />
      </div>
    </RouterLink>
    <div v-if="cost.trip_id" class="border-t border-white/[0.06] px-4 pb-4 pt-3">
      <RouterLink
        :to="`/driver/trips/${cost.trip_id}`"
        class="flex min-h-[48px] w-full items-center justify-center rounded-2xl bg-driver-surface text-base font-semibold text-driver-accent ring-1 ring-driver-accent/25 transition hover:bg-driver-elevated active:scale-[0.99]"
      >
        {{ tripCta }}
      </RouterLink>
    </div>
  </article>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import {
  CalendarIcon,
  ChevronRightIcon,
  BanknotesIcon as MoneyIcon,
  MapIcon as RoadIcon,
} from '@heroicons/vue/24/outline'
import { FireIcon as FuelIcon } from '@heroicons/vue/24/outline'
import { MapPinIcon as ParkingIcon } from '@heroicons/vue/24/outline'
import { formatVnd } from '../../../util/labels'

const props = defineProps({
  cost: { type: Object, required: true },
  statusLabel: { type: Function, required: true },
  typeLabel: { type: Function, required: true },
  tripCta: { type: String, required: true },
  standaloneBadge: { type: String, default: '' },
})

function normType(t) {
  return String(t ?? '')
    .trim()
    .toLowerCase()
}

function statusBadgeClass(st) {
  if (st === 'confirmed')
    return 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-400/25'
  if (st === 'rejected') return 'bg-rose-500/15 text-rose-300 ring-1 ring-rose-400/25'
  if (st === 'submitted') return 'bg-amber-500/15 text-amber-200 ring-1 ring-amber-400/25'
  if (st === 'draft') return 'bg-white/[0.08] text-driver-muted ring-1 ring-white/10'
  return 'bg-white/[0.06] text-driver-ink ring-1 ring-white/10'
}

const departLabel = computed(() => {
  const trip = props.cost?.trip
  if (trip?.depart_at) {
    const d = new Date(trip.depart_at)
    if (!Number.isNaN(d.getTime())) {
      return d.toLocaleString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    }
  }
  const created = props.cost?.created_at
  if (created) {
    const d = new Date(created)
    if (!Number.isNaN(d.getTime())) {
      return d.toLocaleString('vi-VN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    }
  }
  return '—'
})
</script>
