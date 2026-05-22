<template>
  <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
      <div class="flex items-center gap-2 text-teal-600">
        <CalculatorIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
        <h2 class="text-sm font-semibold text-slate-900 sm:text-base">{{ t('request_detail.cost_estimate_heading') }}</h2>
      </div>
      <div class="flex flex-wrap gap-2">
        <Button
          v-if="suggestions.length && canApplyPricing"
          type="button"
          variant="secondary"
          class="shrink-0 !border-violet-200 !text-violet-900 hover:!bg-violet-50"
          :disabled="applyingPricing"
          @click="$emit('apply-suggestion', suggestions[0])"
        >
          {{ applyingPricing ? t('request_detail.pricing_apply_busy') : t('request_detail.pricing_apply_top') }}
        </Button>
        <Button
          type="button"
          variant="secondary"
          class="shrink-0 !border-teal-200 !text-teal-900 hover:!bg-teal-50"
          @click="$emit('open-pricing')"
        >
          {{ t('request_detail.reference_pricing_link') }}
        </Button>
        <Button
          v-if="mapsUrl"
          type="button"
          variant="secondary"
          class="shrink-0 !border-slate-200 !text-slate-800"
          @click="openMaps"
        >
          {{ t('request_detail.open_maps_route') }}
        </Button>
        <Button
          type="button"
          variant="secondary"
          class="shrink-0 !text-slate-700"
          @click="$emit('go-form')"
        >
          {{ t('request_detail.cost_edit_in_form') }}
        </Button>
      </div>
    </div>

    <p
      v-if="estimate?.declaredMismatch"
      class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-950"
    >
      {{ t('request_detail.cost_mismatch_banner', {
        declared: estimate.declaredTotalLabel,
        dispatcher: estimate.dispatcherPriceLabel,
      }) }}
    </p>

    <p
      v-if="estimate?.priceFilledByUser"
      class="mt-2 text-[11px] text-slate-600"
    >
      {{ t('request_detail.price_filled_meta', {
        name: estimate.priceFilledByUser.name,
        at: priceFilledAtLabel,
      }) }}
    </p>

    <dl class="mt-4 space-y-2 text-xs sm:text-sm">
      <div class="flex justify-between gap-3 border-b border-slate-50 pb-2">
        <dt class="text-slate-500">{{ t('request_detail.lbl_est_distance') }}</dt>
        <dd class="text-right font-medium text-slate-900">{{ estimate?.distanceLabel ?? '—' }}</dd>
      </div>
      <div class="flex justify-between gap-3 border-b border-slate-50 pb-2">
        <dt class="text-slate-500">{{ t('request_detail.lbl_suggested_vehicle_type') }}</dt>
        <dd class="text-right font-medium text-slate-900">{{ estimate?.vehicleHint ?? '—' }}</dd>
      </div>
      <div class="flex justify-between gap-3 border-b border-slate-50 pb-2">
        <dt class="text-slate-500">{{ t('request_detail.lbl_ref_unit_price_estimate') }}</dt>
        <dd class="text-right font-medium text-slate-900">{{ estimate?.refUnitLabel ?? '—' }}</dd>
      </div>
      <div class="flex justify-between gap-3 border-b border-slate-50 pb-2">
        <dt class="text-slate-500">{{ t('request_detail.lbl_toll_estimate') }}</dt>
        <dd class="text-right font-medium text-slate-900">{{ estimate?.tollLabel ?? '—' }}</dd>
      </div>
      <div
        v-if="estimate?.dispatcherPriceLabel"
        class="flex justify-between gap-3 border-b border-slate-50 pb-2"
      >
        <dt class="text-slate-500">{{ t('request_detail.lbl_dispatcher_unit_price') }}</dt>
        <dd class="text-right font-semibold text-violet-900">{{ estimate.dispatcherPriceLabel }}</dd>
      </div>
    </dl>

    <details v-if="estimate?.breakdown?.length" class="mt-3 rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2">
      <summary class="cursor-pointer text-xs font-semibold text-slate-700">
        {{ t('request_detail.cost_breakdown_toggle') }}
      </summary>
      <ul class="mt-2 space-y-1 text-xs">
        <li
          v-for="line in estimate.breakdown"
          :key="line.key"
          class="flex justify-between gap-2 text-slate-800"
        >
          <span class="text-slate-600">{{ line.label }}</span>
          <span class="font-medium tabular-nums">{{ formatVnd(line.amount) }}</span>
        </li>
      </ul>
    </details>

    <p
      v-if="estimate && !estimate.hasDeclaredAmount"
      class="mt-2 text-[11px] leading-snug text-slate-500"
    >
      {{ t('request_detail.cost_estimate_empty_hint') }}
    </p>

    <div class="mt-4 rounded-xl bg-teal-50/90 px-3 py-3 ring-1 ring-teal-600/10 sm:px-4 sm:py-4">
      <p class="text-[11px] font-medium text-teal-900/90">{{ t('request_detail.total_per_declaration') }}</p>
      <p class="mt-0.5 text-lg font-bold tabular-nums text-teal-600 sm:text-xl">
        {{ estimate?.declaredTotalLabel ?? '—' }}
      </p>
    </div>

    <ul v-if="suggestions.length > 1" class="mt-3 space-y-1 border-t border-slate-100 pt-3 text-xs">
      <li v-for="(s, i) in suggestions.slice(1, 4)" :key="i" class="flex items-center justify-between gap-2">
        <span class="min-w-0 truncate text-slate-700">{{ s.label }}</span>
        <button
          v-if="canApplyPricing"
          type="button"
          class="shrink-0 font-semibold text-teal-700 hover:underline"
          @click="$emit('apply-suggestion', s)"
        >
          {{ t('request_detail.pricing_apply_short') }}
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { CalculatorIcon } from '@heroicons/vue/24/outline'
import Button from '../ui/Button.vue'
import { formatVndCurrency } from '../../util/money'

const props = defineProps({
  estimate: { type: Object, default: null },
  suggestions: { type: Array, default: () => [] },
  canApplyPricing: { type: Boolean, default: false },
  applyingPricing: { type: Boolean, default: false },
  formatDateTime: { type: Function, required: true },
  mapsUrl: { type: String, default: '' },
})

function openMaps() {
  if (props.mapsUrl) window.open(props.mapsUrl, '_blank', 'noopener,noreferrer')
}

defineEmits(['open-pricing', 'go-form', 'apply-suggestion'])

const { t } = useI18n()

function formatVnd(n) {
  return formatVndCurrency(n)
}

const priceFilledAtLabel = computed(() => {
  const at = props.estimate?.priceFilledAt
  if (!at) return '—'
  return props.formatDateTime(at)
})
</script>
