<template>
  <div class="overflow-hidden rounded-2xl bg-driver-card ring-1 ring-white/[0.06]">
    <button
      type="button"
      class="flex min-h-[48px] w-full items-center justify-between gap-2 border-b border-white/[0.06] px-4 py-3 text-left active:bg-driver-surface/40"
      :class="canAddCost ? '' : 'cursor-default opacity-95'"
      @click="canAddCost && $emit('open-modal')"
    >
      <div class="flex min-w-0 flex-1 items-center gap-2.5">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-orange-50">
          <BanknotesIcon class="h-4 w-4 text-orange-500" aria-hidden="true" />
        </div>
        <span class="text-base font-semibold text-driver-ink sm:text-lg">{{ t('driver_trip_detail.costs_section') }}</span>
        <span v-if="tripCosts.length" class="ml-auto mr-2 max-w-[55%] shrink-0 text-right text-sm font-semibold text-driver-muted sm:mr-3 sm:text-base">
          <span class="block truncate sm:whitespace-normal">{{ formatVnd(costsApprovedTotal) }}</span>
          <span v-if="costsPendingTotal > 0" class="mt-0.5 block text-xs font-medium text-amber-400/90 sm:text-sm">
            {{ t('driver_trip_detail.costs_pending_line', { amount: formatVnd(costsPendingTotal) }) }}
          </span>
        </span>
      </div>
      <ChevronRightIcon class="h-5 w-5 shrink-0 text-driver-muted/60" :class="canAddCost ? '' : 'opacity-0'" aria-hidden="true" />
    </button>

    <div class="px-4 pb-4 pt-3">
      <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <span class="text-sm text-driver-muted sm:text-base">
          <template v-if="tripCosts.length">
            {{ t('driver_trip_detail.costs_approved_line', { amount: formatVnd(costsApprovedTotal) }) }}
            <template v-if="costsPendingTotal > 0">
              <span class="text-driver-muted/80"> · </span>
              {{ t('driver_trip_detail.costs_pending_line', { amount: formatVnd(costsPendingTotal) }) }}
            </template>
          </template>
          <template v-else>{{ t('driver_trip_detail.costs_empty') }}</template>
        </span>
        <button
          v-if="canAddCost"
          type="button"
          class="flex min-h-[44px] items-center gap-1 rounded-full bg-driver-bg px-4 py-2 text-sm font-bold text-white active:opacity-90 sm:text-base"
          @click="$emit('open-modal')"
        >
          <PlusIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ t('driver_trip_detail.costs_add') }}
        </button>
      </div>
      <ul v-if="tripCosts.length" class="space-y-2">
        <li v-for="c in tripCosts" :key="c.id">
          <RouterLink
            :to="`/driver/costs/${c.id}`"
            class="flex min-h-[52px] items-center gap-2.5 rounded-xl border border-white/[0.06] bg-driver-surface/60 px-3 py-2.5 transition active:bg-driver-elevated"
          >
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-driver-elevated text-driver-muted ring-1 ring-white/10">
              <FireIcon v-if="c.type === 'fuel'" class="h-4 w-4 text-orange-500" />
              <WrenchScrewdriverIcon v-else-if="c.type === 'repair'" class="h-4 w-4 text-blue-500" />
              <SparklesIcon v-else-if="c.type === 'wash'" class="h-4 w-4 text-sky-500" />
              <BanknotesIcon v-else class="h-4 w-4 text-driver-muted" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-base font-semibold leading-snug text-driver-ink sm:text-lg">
                {{ costTypeLabel(c.type) }}
                <span v-if="c.description" class="font-normal text-driver-muted"> · {{ c.description }}</span>
              </p>
              <div class="mt-0.5 flex flex-wrap items-center gap-2">
                <p class="text-xs text-driver-muted/85 sm:text-sm">{{ formatCostTime(c.created_at) }}</p>
                <TripCostApprovalBadge :status="c.status" :label="costStatusLabel(c.status)" />
              </div>
            </div>
            <div class="flex shrink-0 items-center gap-0.5 text-base font-bold tabular-nums text-driver-ink sm:text-lg">
              {{ formatVnd(c.amount) }}
              <ChevronRightIcon class="h-5 w-5 text-driver-muted/60" aria-hidden="true" />
            </div>
          </RouterLink>
        </li>
      </ul>
      <p v-else class="py-2 text-center text-sm text-driver-muted/80 sm:text-base">{{ t('driver_trip_detail.costs_empty') }}</p>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import {
  BanknotesIcon,
  ChevronRightIcon,
  FireIcon,
  PlusIcon,
  SparklesIcon,
  WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'
import TripCostApprovalBadge from './TripCostApprovalBadge.vue'

defineProps({
  tripCosts: { type: Array, default: () => [] },
  costsApprovedTotal: { type: Number, default: 0 },
  costsPendingTotal: { type: Number, default: 0 },
  canAddCost: { type: Boolean, default: false },
  formatVnd: { type: Function, required: true },
  costTypeLabel: { type: Function, required: true },
  costStatusLabel: { type: Function, required: true },
  formatCostTime: { type: Function, required: true },
})

defineEmits(['open-modal'])

const { t } = useI18n()
</script>
