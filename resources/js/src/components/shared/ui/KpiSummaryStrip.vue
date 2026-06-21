<script setup>
import { FunnelIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  cards: {
    type: Array,
    default: () => [],
  },
  gridClass: {
    type: String,
    default: 'grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5',
  },
  ariaLabel: { type: String, required: true },
  eyebrow: { type: String, default: 'THỐNG KÊ' },
  title: { type: String, required: true },
  hint: { type: String, default: '' },
  activeCardKey: { type: String, default: '' },
  /** Mặc định cỡ số KPI (card.displayClass ghi đè). */
  valueDisplayClass: { type: String, default: 'text-2xl' },
  /** Thẻ gọn — số nhỏ hơn, icon nhỏ, chiều cao thấp (vd. 6 cột tài chính). */
  compact: { type: Boolean, default: false },
})

const emit = defineEmits(['card-action'])

const toneClass = {
  brand: 'kpi-card--brand',
  emerald: 'kpi-card--emerald',
  amber: 'kpi-card--amber',
  sky: 'kpi-card--sky',
  violet: 'kpi-card--violet',
  rose: 'kpi-card--rose',
  slate: 'kpi-card--slate',
}

const iconToneClass = {
  brand: 'text-va-800 bg-va-50 ring-va-200/80',
  emerald: 'text-emerald-700 bg-emerald-50 ring-emerald-200/80',
  amber: 'text-amber-700 bg-amber-50 ring-amber-200/80',
  sky: 'text-sky-700 bg-sky-50 ring-sky-200/80',
  violet: 'text-violet-700 bg-violet-50 ring-violet-200/80',
  rose: 'text-rose-700 bg-rose-50 ring-rose-200/80',
  slate: 'text-slate-600 bg-slate-100 ring-slate-200/80',
}

function isInteractive(card) {
  return card.filter != null
}

function isActive(card) {
  return props.activeCardKey === card.key
}

function onCard(card) {
  if (!isInteractive(card)) return
  emit('card-action', card)
}

function valueClass(card) {
  return card.tone === 'brand' ? 'text-va-800' : 'text-slate-900'
}
</script>

<template>
  <section
    class="kpi-strip relative mb-5 overflow-x-hidden rounded-xl border border-slate-200/80 bg-gradient-to-b from-slate-50/90 to-white px-4 py-4 shadow-sm sm:px-5 sm:py-5"
    :aria-label="ariaLabel"
  >
    <div class="kpi-strip__bg-outer" aria-hidden="true">
      <div class="kpi-strip__bg-inner" />
    </div>

    <header class="relative mb-3 flex flex-wrap items-end justify-between gap-2">
      <div>
        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-va-800/80">{{ eyebrow }}</p>
        <h2 class="text-sm font-semibold tracking-tight text-slate-800">{{ title }}</h2>
      </div>
      <p v-if="hint" class="max-w-md text-right text-[11px] text-slate-500">{{ hint }}</p>
    </header>

    <div class="relative pb-1" :class="gridClass">
      <component
        :is="isInteractive(card) ? 'button' : 'div'"
        v-for="card in cards"
        :key="card.key"
        :type="isInteractive(card) ? 'button' : undefined"
        class="kpi-card group relative w-full"
        :class="[
          compact ? 'min-h-[5rem]' : 'min-h-[6.75rem]',
          toneClass[card.tone] || toneClass.brand,
          isInteractive(card) ? 'kpi-card--interactive' : 'kpi-card--static',
          isActive(card) ? 'kpi-card--active' : '',
        ]"
        :aria-pressed="isInteractive(card) ? isActive(card) : undefined"
        :data-testid="`kpi-card-${card.key}`"
        @click="onCard(card)"
      >
        <span class="kpi-card__shell-outer" aria-hidden="true" />
        <span class="kpi-card__shell-inner" aria-hidden="true" />
        <span class="kpi-card__shell-accent" aria-hidden="true" />
        <span class="kpi-card__shine" aria-hidden="true" />

        <div class="relative flex items-start justify-between gap-2">
          <div class="min-w-0 flex-1">
            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">{{ card.label }}</p>
            <p
              class="mt-1 tabular-nums font-semibold"
              :class="[
                compact
                  ? 'truncate text-xs leading-snug sm:text-sm'
                  : card.displayClass || valueDisplayClass,
                valueClass(card),
              ]"
            >
              {{ card.display }}
            </p>
            <p v-if="card.sub" class="mt-0.5 text-[11px] leading-snug text-slate-500">{{ card.sub }}</p>
            <div
              v-if="card.progress != null && card.progressTotal > 0"
              class="mt-2"
            >
              <div class="mb-0.5 flex justify-between text-[10px] text-slate-500">
                <span>Tỷ lệ</span>
                <span class="tabular-nums">{{ card.progress }}%</span>
              </div>
              <div class="h-1.5 overflow-hidden rounded-full bg-slate-100">
                <div class="kpi-card__bar" :style="{ width: `${card.progress}%` }" />
              </div>
            </div>
          </div>
          <div
            v-if="card.icon"
            class="flex shrink-0 items-center justify-center rounded-lg ring-1"
            :class="[
              compact ? 'h-8 w-8' : 'h-10 w-10',
              iconToneClass[card.tone] || iconToneClass.brand,
            ]"
          >
            <component :is="card.icon" :class="compact ? 'h-4 w-4' : 'h-5 w-5'" aria-hidden="true" />
          </div>
        </div>

        <p
          v-if="isInteractive(card)"
          class="relative mt-2 flex items-center gap-1 text-[10px] font-medium text-va-800/0 transition group-hover:text-va-800/90"
          :class="isActive(card) ? '!text-va-800' : ''"
        >
          <FunnelIcon class="h-3 w-3" aria-hidden="true" />
          {{ isActive(card) ? 'Đang lọc' : 'Lọc nhanh' }}
        </p>
      </component>
    </div>
  </section>
</template>
