<template>
  <div>
    <h2 v-if="hint" class="text-base font-semibold text-slate-900 sm:text-lg">{{ hint }}</h2>
    <p v-if="doubleTapHint" class="mt-0.5 text-xs text-slate-500 sm:text-sm">{{ doubleTapHint }}</p>
    <div
      class="mt-3 grid grid-cols-1 gap-2.5 sm:grid-cols-2"
      role="listbox"
      :aria-label="hint"
    >
      <button
        v-for="tt in tripTypes"
        :key="tt"
        type="button"
        role="option"
        class="group relative flex items-start gap-3 rounded-2xl border p-3 text-left outline-none transition focus-visible:ring-2 focus-visible:ring-va-800/30 sm:p-3.5"
        :class="tripType === tt ? styleFor(tt).selectedCard : styleFor(tt).idleCard"
        :aria-selected="tripType === tt"
        :data-testid="`trip-type-${tt}`"
        @click="$emit('select', tt)"
      >
        <span
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 sm:h-11 sm:w-11"
          :class="styleFor(tt).iconWrap"
        >
          <component
            :is="tripTypeIcon(tt)"
            class="h-5 w-5 shrink-0 stroke-[1.75] sm:h-6 sm:w-6"
            :class="styleFor(tt).iconClass"
            aria-hidden="true"
          />
        </span>
        <span class="min-w-0 flex-1">
          <span class="flex flex-wrap items-center gap-1.5">
            <span class="text-sm font-semibold leading-tight text-slate-900 sm:text-[15px]">{{
              t(`dispatch_wizard.trip_type.${tt}.label`)
            }}</span>
            <span
              v-if="slaBadge(tt)"
              class="rounded-md bg-orange-50 px-1.5 py-px text-[10px] font-bold uppercase tracking-wide text-orange-700 ring-1 ring-orange-200/80"
            >
              {{ slaBadge(tt) }}
            </span>
            <CheckCircleIcon
              v-if="tripType === tt"
              class="h-4 w-4 shrink-0 text-va-700"
              aria-hidden="true"
            />
          </span>
          <span class="mt-0.5 block text-xs leading-relaxed text-slate-500">{{
            t(`dispatch_wizard.trip_type.${tt}.hint`)
          }}</span>
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
