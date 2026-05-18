<template>
  <div>
    <p v-if="hint" class="mb-3 text-sm font-medium text-slate-700">{{ hint }}</p>
    <div
      class="grid grid-cols-2 gap-3 xl:grid-cols-4"
      role="listbox"
      :aria-label="hint"
      aria-orientation="horizontal"
    >
      <button
        v-for="tt in tripTypes"
        :key="tt"
        type="button"
        role="option"
        class="flex min-h-[44px] items-start gap-3 rounded-2xl border px-4 py-3 text-left shadow-sm transition-all duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800"
        :class="
          tripType === tt
            ? 'border-va-800 bg-va-800/5 ring-2 ring-va-800/35'
            : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'
        "
        :aria-selected="tripType === tt"
        @click="$emit('select', tt)"
      >
        <component
          :is="tripTypeIcon(tt)"
          class="mt-0.5 h-6 w-6 shrink-0"
          :class="tripType === tt ? 'text-va-800' : 'text-slate-400'"
          aria-hidden="true"
        />
        <span>
          <span class="block font-semibold text-slate-900">{{ t(`dispatch_wizard.trip_type.${tt}.label`) }}</span>
          <span class="mt-0.5 block text-xs leading-snug text-slate-500">
            {{ t(`dispatch_wizard.trip_type.${tt}.hint`) }}
          </span>
        </span>
      </button>
    </div>
    <p v-if="doubleTapHint" class="mt-3 text-xs text-slate-400">{{ doubleTapHint }}</p>
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

function tripTypeIcon(tt) {
  return TRIP_TYPE_ICONS[tt] ?? MapPinIcon
}
</script>
