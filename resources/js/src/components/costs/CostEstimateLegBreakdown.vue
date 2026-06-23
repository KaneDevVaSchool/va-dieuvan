<template>
  <section
    v-if="rows.length"
    :class="shellClass"
    :aria-labelledby="headingId"
    :data-testid="testId"
  >
    <div :class="headerClass">
      <h3 :id="headingId" class="text-sm font-bold text-slate-900 dark:text-slate-100">
        {{ t('costs_page.detail_section_leg_costs') }}
      </h3>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
        {{ t('costs_page.detail_leg_costs_hint') }}
      </p>
    </div>
    <div :class="bodyClass">
      <table class="w-full min-w-[640px] border-collapse text-sm">
        <thead>
          <tr class="border-b border-slate-200 text-left dark:border-slate-700">
            <th scope="col" class="pb-2 pr-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('costs_page.detail_leg_col_leg') }}
            </th>
            <th scope="col" class="pb-2 pr-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('costs_page.col_description') }}
            </th>
            <th scope="col" class="pb-2 pr-3 text-right text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('costs_page.col_unit_price') }}
            </th>
            <th scope="col" class="pb-2 pr-3 text-right text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('costs_page.col_extra_fee') }}
            </th>
            <th scope="col" class="pb-2 text-right text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('costs_page.col_payment') }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <tr
            v-for="row in rows"
            :key="row.key"
            class="align-top"
            :data-testid="`${rowTestIdPrefix}-${row.key}`"
          >
            <td class="whitespace-nowrap py-3 pr-3">
              <span
                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                :class="row.leg_seq ? 'bg-amber-50 text-amber-900 ring-1 ring-amber-200/80 dark:bg-amber-950/40 dark:text-amber-100 dark:ring-amber-800/50' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'"
              >
                {{ row.leg_label }}
              </span>
            </td>
            <td class="min-w-[12rem] py-3 pr-3">
              <p class="font-medium text-slate-900 dark:text-slate-100">{{ row.description }}</p>
              <p v-if="row.leg_route && row.leg_route !== row.description" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                {{ row.leg_route }}
              </p>
            </td>
            <td class="py-3 pr-3 text-right tabular-nums text-slate-800 dark:text-slate-200">
              {{ row.unit_price_display }}
            </td>
            <td class="py-3 pr-3 text-right tabular-nums text-slate-800 dark:text-slate-200">
              {{ row.extra_fee_display }}
            </td>
            <td class="py-3 text-right font-semibold tabular-nums text-slate-900 dark:text-slate-100">
              {{ formatVnd(row.amount) }}
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr class="border-t border-slate-200 dark:border-slate-700">
            <td colspan="4" class="pt-3 pr-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('costs_page.detail_leg_total_label') }}
            </td>
            <td class="pt-3 text-right text-base font-bold tabular-nums text-teal-800 dark:text-teal-300">
              {{ formatVnd(total) }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCostEstimateLineRows } from '../../composables/useCostEstimateLineRows'

const props = defineProps({
  /** @type {Array<Record<string, unknown>>} */
  lines: { type: Array, default: () => [] },
  /** section = modal card; embedded = list card inset */
  variant: { type: String, default: 'section' },
  testId: { type: String, default: 'cost-estimate-leg-breakdown' },
  rowTestIdPrefix: { type: String, default: 'cost-leg-row' },
})

const { t } = useI18n()
const linesRef = computed(() => props.lines)
const { rows, total, formatVnd } = useCostEstimateLineRows(linesRef)

const headingId = computed(() => `${props.testId}-heading`)

const shellClass = computed(() => {
  if (props.variant === 'embedded') {
    return 'border-t border-slate-100 dark:border-slate-800'
  }
  return 'rounded-2xl border border-slate-200/90 bg-white dark:border-slate-700 dark:bg-slate-900/30'
})

const headerClass = computed(() => {
  if (props.variant === 'embedded') {
    return 'px-3 pt-4 sm:px-5'
  }
  return 'border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5'
})

const bodyClass = computed(() => {
  const base = 'overflow-x-auto'
  if (props.variant === 'embedded') {
    return `${base} px-3 pb-4 sm:px-5`
  }
  return `${base} px-4 py-4 sm:px-5`
})
</script>
