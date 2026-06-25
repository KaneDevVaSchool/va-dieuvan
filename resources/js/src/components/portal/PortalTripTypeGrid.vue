<template>
  <div>
    <h2 v-if="hint" class="text-base font-semibold text-slate-900 sm:text-lg">{{ hint }}</h2>
    <p v-if="doubleTapHint" class="mt-0.5 text-xs text-slate-500 sm:text-sm">{{ doubleTapHint }}</p>
    <div
      class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-2"
      role="listbox"
      :aria-label="hint"
      aria-orientation="horizontal"
    >
      <button
        v-for="tt in tripTypes"
        :key="tt"
        type="button"
        role="option"
        class="group relative flex max-h-[80px] min-h-[60px] items-center justify-center gap-2 rounded-xl border px-2 py-2.5 text-center outline-none transition focus-visible:ring-2 focus-visible:ring-va-800/30 sm:max-h-[72px] sm:min-h-[64px] sm:px-3"
        :class="tripType === tt ? styleFor(tt).selectedCard : styleFor(tt).idleCard"
        :aria-selected="tripType === tt"
        :title="t(`dispatch_wizard.trip_type.${tt}.hint`)"
        :data-testid="`trip-type-${tt}`"
        @click="$emit('select', tt)"
      >
        <span
          v-if="slaBadge(tt)"
          class="absolute right-1 top-1 rounded-md bg-white/90 px-1 py-px text-[9px] font-bold uppercase tracking-wide text-orange-700 ring-1 ring-orange-200/80"
        >
          {{ slaBadge(tt) }}
        </span>
        <component
          :is="tripTypeIcon(tt)"
          class="h-5 w-5 shrink-0 stroke-[1.75] sm:h-6 sm:w-6"
          :class="styleFor(tt).iconClass"
          aria-hidden="true"
        />
        <span class="text-xs font-semibold leading-tight text-slate-900 sm:text-sm">{{
          t(`dispatch_wizard.trip_type.${tt}.label`)
        }}</span>
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
    selectedCard: 'border-va-600 bg-va-50/90 shadow-sm ring-1 ring-va-700/25',
    idleCard: 'border-slate-200/90 bg-white hover:border-slate-300 hover:bg-slate-50/80',
  },
  point_to_point: {
    iconClass: 'text-sky-600',
    selectedCard: 'border-va-600 bg-sky-50/90 shadow-sm ring-1 ring-va-700/25',
    idleCard: 'border-slate-200/90 bg-white hover:border-slate-300 hover:bg-slate-50/80',
  },
  business: {
    iconClass: 'text-emerald-600',
    selectedCard: 'border-va-600 bg-emerald-50/90 shadow-sm ring-1 ring-va-700/25',
    idleCard: 'border-slate-200/90 bg-white hover:border-slate-300 hover:bg-slate-50/80',
  },
  cargo: {
    iconClass: 'text-orange-600',
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
