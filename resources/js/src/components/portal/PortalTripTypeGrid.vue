<template>
  <div>
    <h2 v-if="hint" class="text-base font-semibold text-slate-900 sm:text-lg">{{ hint }}</h2>
    <p v-if="doubleTapHint" class="mt-0.5 text-xs text-slate-500 sm:text-sm">{{ doubleTapHint }}</p>
    <div
      class="mt-4 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4"
      role="listbox"
      :aria-label="hint"
    >
      <button
        v-for="tt in tripTypes"
        :key="tt"
        type="button"
        role="option"
        class="group relative flex aspect-square w-full flex-col items-center justify-center gap-2.5 rounded-2xl border p-4 text-center outline-none transition focus-visible:ring-2 focus-visible:ring-va-800/30 sm:gap-3 sm:p-5"
        :class="tripType === tt ? styleFor(tt).selectedCard : styleFor(tt).idleCard"
        :aria-selected="tripType === tt"
        :data-testid="`trip-type-${tt}`"
        @click="$emit('select', tt)"
      >
        <CheckCircleIcon
          v-if="tripType === tt"
          class="absolute right-2.5 top-2.5 h-5 w-5 shrink-0 text-va-700 sm:right-3 sm:top-3"
          aria-hidden="true"
        />
        <span
          class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl ring-1 sm:h-14 sm:w-14"
          :class="styleFor(tt).iconWrap"
        >
          <component
            :is="tripTypeIcon(tt)"
            class="h-7 w-7 shrink-0 stroke-[1.75] sm:h-8 sm:w-8"
            :class="styleFor(tt).iconClass"
            aria-hidden="true"
          />
        </span>
        <span class="flex min-h-0 w-full flex-col items-center gap-1 px-0.5">
          <span class="flex flex-wrap items-center justify-center gap-1.5">
            <span class="text-sm font-semibold leading-tight text-slate-900 sm:text-base">{{
              t(`dispatch_wizard.trip_type.${tt}.label`)
            }}</span>
            <span
              v-if="slaBadge(tt)"
              class="rounded-md bg-orange-50 px-1.5 py-px text-[10px] font-bold uppercase tracking-wide text-orange-700 ring-1 ring-orange-200/80"
            >
              {{ slaBadge(tt) }}
            </span>
          </span>
          <span
            class="line-clamp-4 text-[11px] leading-snug text-slate-500 sm:line-clamp-5 sm:text-xs sm:leading-relaxed"
            >{{ t(`dispatch_wizard.trip_type.${tt}.hint`) }}</span
          >
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import {
  AcademicCapIcon,
  BriefcaseIcon,
  BuildingOffice2Icon,
  CheckCircleIcon,
  CubeIcon,
} from '@heroicons/vue/24/outline'

defineProps({
  tripTypes: { type: Array, required: true },
  tripType: { type: String, required: true },
  hint: { type: String, default: '' },
  doubleTapHint: { type: String, default: '' },
})

defineEmits(['select'])

const { t, te } = useI18n()

function slaBadge(tt) {
  if (tt !== 'cargo') return ''
  return te('dispatch_wizard.trip_type.cargo.badge') ? t('dispatch_wizard.trip_type.cargo.badge') : ''
}

const TRIP_STYLE = {
  door_to_door: {
    iconClass: 'text-va-800',
    iconWrap: 'bg-va-50 ring-va-100',
    selectedCard: 'border-va-600 bg-va-50/90 shadow-sm ring-1 ring-va-700/25',
    idleCard: 'border-slate-200/90 bg-white hover:border-slate-300 hover:bg-slate-50/80',
  },
  point_to_point: {
    iconClass: 'text-sky-600',
    iconWrap: 'bg-sky-50 ring-sky-100',
    selectedCard: 'border-va-600 bg-sky-50/90 shadow-sm ring-1 ring-va-700/25',
    idleCard: 'border-slate-200/90 bg-white hover:border-slate-300 hover:bg-slate-50/80',
  },
  business: {
    iconClass: 'text-emerald-600',
    iconWrap: 'bg-emerald-50 ring-emerald-100',
    selectedCard: 'border-va-600 bg-emerald-50/90 shadow-sm ring-1 ring-va-700/25',
    idleCard: 'border-slate-200/90 bg-white hover:border-slate-300 hover:bg-slate-50/80',
  },
  cargo: {
    iconClass: 'text-orange-600',
    iconWrap: 'bg-orange-50 ring-orange-100',
    selectedCard: 'border-va-600 bg-orange-50/90 shadow-sm ring-1 ring-va-700/25',
    idleCard: 'border-slate-200/90 bg-white hover:border-slate-300 hover:bg-slate-50/80',
  },
}

const TRIP_TYPE_ICONS = {
  door_to_door: AcademicCapIcon,
  point_to_point: BuildingOffice2Icon,
  business: BriefcaseIcon,
  cargo: CubeIcon,
}

function styleFor(tt) {
  return TRIP_STYLE[tt] ?? TRIP_STYLE.point_to_point
}

function tripTypeIcon(tt) {
  return TRIP_TYPE_ICONS[tt] ?? BuildingOffice2Icon
}
</script>
