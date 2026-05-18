<template>
  <div>
    <p v-if="hint" class="mb-4 text-sm font-semibold tracking-tight text-slate-700">{{ hint }}</p>
    <div
      class="grid grid-cols-2 gap-4 xl:grid-cols-4"
      role="listbox"
      :aria-label="hint"
      aria-orientation="horizontal"
    >
      <button
        v-for="tt in tripTypes"
        :key="tt"
        type="button"
        role="option"
        class="group relative flex min-h-[120px] flex-col items-start gap-3 rounded-2xl border px-4 py-4 text-left shadow-sm transition-all duration-200 ease-out focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800 active:scale-[0.98] xl:min-h-[124px]"
        :class="tripType === tt ? styleFor(tt).selectedCard : styleFor(tt).idleCard"
        :aria-selected="tripType === tt"
        @click="$emit('select', tt)"
      >
        <span
          class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-inner ring-1 ring-black/[0.04] transition-colors duration-200"
          :class="tripType === tt ? styleFor(tt).iconSelected : styleFor(tt).iconIdle"
        >
          <component :is="tripTypeIcon(tt)" class="h-10 w-10 shrink-0 stroke-[1.5]" aria-hidden="true" />
        </span>
        <span class="min-w-0">
          <span class="block text-base font-bold tracking-tight text-slate-900">{{ t(`dispatch_wizard.trip_type.${tt}.label`) }}</span>
          <span class="mt-1 block text-xs leading-snug text-slate-600">{{ t(`dispatch_wizard.trip_type.${tt}.hint`) }}</span>
        </span>
      </button>
    </div>
    <p v-if="doubleTapHint" class="mt-4 text-xs text-slate-500">{{ doubleTapHint }}</p>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { BriefcaseIcon, CubeIcon, HomeIcon, MapPinIcon } from '@heroicons/vue/24/outline'

defineProps({
  tripTypes: { type: Array, required: true },
  tripType: { type: String, required: true },
  hint: { type: String, default: '' },
  doubleTapHint: { type: String, default: '' },
})

defineEmits(['select'])

const { t } = useI18n()

const TRIP_TYPE_ICONS = {
  door_to_door: HomeIcon,
  point_to_point: MapPinIcon,
  business: BriefcaseIcon,
  cargo: CubeIcon,
}

/** Full Tailwind strings so JIT keeps classes */
const TRIP_STYLE = {
  door_to_door: {
    iconIdle: 'bg-sky-100 text-sky-700 group-hover:bg-sky-200/90',
    iconSelected: 'bg-sky-200 text-sky-800',
    selectedCard:
      'border-sky-400 bg-gradient-to-br from-sky-50 via-white to-white shadow-lg shadow-sky-900/10 ring-2 ring-sky-400/40 hover:border-sky-400',
    idleCard:
      'border-slate-200/90 bg-white hover:-translate-y-px hover:border-slate-300 hover:bg-slate-50 hover:shadow-md hover:shadow-slate-900/10',
  },
  point_to_point: {
    iconIdle: 'bg-indigo-100 text-indigo-700 group-hover:bg-indigo-200/90',
    iconSelected: 'bg-indigo-200 text-indigo-900',
    selectedCard:
      'border-indigo-400 bg-gradient-to-br from-indigo-50 via-white to-white shadow-lg shadow-indigo-900/10 ring-2 ring-indigo-400/35 hover:border-indigo-400',
    idleCard:
      'border-slate-200/90 bg-white hover:-translate-y-px hover:border-slate-300 hover:bg-slate-50 hover:shadow-md hover:shadow-slate-900/10',
  },
  business: {
    iconIdle: 'bg-amber-100 text-amber-700 group-hover:bg-amber-200/90',
    iconSelected: 'bg-amber-200 text-amber-900',
    selectedCard:
      'border-amber-400 bg-gradient-to-br from-amber-50 via-white to-white shadow-lg shadow-amber-900/10 ring-2 ring-amber-400/35 hover:border-amber-400',
    idleCard:
      'border-slate-200/90 bg-white hover:-translate-y-px hover:border-slate-300 hover:bg-slate-50 hover:shadow-md hover:shadow-slate-900/10',
  },
  cargo: {
    iconIdle: 'bg-teal-100 text-teal-700 group-hover:bg-teal-200/90',
    iconSelected: 'bg-teal-200 text-teal-900',
    selectedCard:
      'border-teal-400 bg-gradient-to-br from-teal-50 via-white to-white shadow-lg shadow-teal-900/10 ring-2 ring-teal-400/35 hover:border-teal-400',
    idleCard:
      'border-slate-200/90 bg-white hover:-translate-y-px hover:border-slate-300 hover:bg-slate-50 hover:shadow-md hover:shadow-slate-900/10',
  },
}

function styleFor(tt) {
  return TRIP_STYLE[tt] ?? TRIP_STYLE.point_to_point
}

function tripTypeIcon(tt) {
  return TRIP_TYPE_ICONS[tt] ?? MapPinIcon
}
</script>
