<template>
  <div>
    <h2 v-if="hint" class="text-lg font-semibold text-slate-900">{{ hint }}</h2>
    <p v-if="doubleTapHint" class="mt-1 text-sm text-slate-600">{{ doubleTapHint }}</p>
    <div
      class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-2 xl:grid-cols-4"
      role="listbox"
      :aria-label="hint"
      aria-orientation="horizontal"
    >
      <button
        v-for="tt in tripTypes"
        :key="tt"
        type="button"
        role="option"
        class="relative flex min-h-[120px] flex-col items-center rounded-xl border-2 p-4 text-center outline-none focus-visible:ring-2 focus-visible:ring-va-700 focus-visible:ring-offset-2 xl:min-h-[124px]"
        :class="tripType === tt ? styleFor(tt).selectedCard : styleFor(tt).idleCard"
        :aria-selected="tripType === tt"
        @click="$emit('select', tt)"
      >
        <component
          :is="tripTypeIcon(tt)"
          class="mb-3 h-10 w-10 shrink-0 opacity-90 stroke-[1.5]"
          :class="styleFor(tt).iconClass"
          aria-hidden="true"
        />
        <span class="font-semibold text-slate-900">{{ t(`dispatch_wizard.trip_type.${tt}.label`) }}</span>
        <span class="mt-1 text-xs leading-snug text-slate-600">{{ t(`dispatch_wizard.trip_type.${tt}.hint`) }}</span>
        <span
          v-if="tt === 'cargo' && cargoBadge"
          class="mt-2 rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-orange-800"
        >
          {{ cargoBadge }}
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
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

const cargoBadge = computed(() =>
  te('dispatch_wizard.trip_type.cargo.badge') ? t('dispatch_wizard.trip_type.cargo.badge') : '',
)

/** Khớp admin DispatchRequestCreateView — không hover idle */
const TRIP_STYLE = {
  door_to_door: {
    iconClass: 'text-va-800',
    selectedCard: 'border-va-600 bg-va-50/90 shadow-sm ring-2 ring-va-700',
    idleCard: 'border-slate-200 bg-slate-50',
  },
  point_to_point: {
    iconClass: 'text-sky-600',
    selectedCard: 'border-va-600 bg-sky-50/90 shadow-sm ring-2 ring-va-700',
    idleCard: 'border-slate-200 bg-slate-50',
  },
  business: {
    iconClass: 'text-emerald-600',
    selectedCard: 'border-va-600 bg-emerald-50/90 shadow-sm ring-2 ring-va-700',
    idleCard: 'border-slate-200 bg-slate-50',
  },
  cargo: {
    iconClass: 'text-orange-600',
    selectedCard: 'border-va-600 bg-orange-50/90 shadow-sm ring-2 ring-va-700',
    idleCard: 'border-slate-200 bg-slate-50',
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
