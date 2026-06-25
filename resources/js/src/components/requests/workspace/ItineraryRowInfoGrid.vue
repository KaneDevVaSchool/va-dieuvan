<template>
  <div
    v-if="items.length"
    class="grid gap-2 border-b border-slate-100 dark:border-slate-800"
    :class="[gridClass, dense ? 'px-3 py-2' : 'px-4 py-3']"
  >
    <div
      v-for="item in items"
      :key="item.key"
      class="flex items-start rounded-xl border border-slate-200/80 bg-slate-50/50 dark:border-slate-700/80 dark:bg-slate-800/40"
      :class="dense ? 'gap-2 px-2.5 py-2' : 'gap-2.5 px-3 py-2.5'"
    >
      <span
        v-if="!dense"
        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
        :class="toneClass[item.tone]"
      >
        <component :is="iconFor(item.iconKey)" class="h-4 w-4" aria-hidden="true" />
      </span>
      <div class="min-w-0 flex-1">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
          {{ item.label }}
        </p>
        <p
          class="mt-0.5 font-semibold tabular-nums text-slate-800 dark:text-slate-100"
          :class="dense ? 'text-xs' : 'text-sm'"
        >
          {{ item.primary }}
        </p>
        <p
          v-if="item.secondary"
          class="mt-0.5 break-words text-xs font-medium text-slate-500 dark:text-slate-400"
        >
          {{ item.secondary }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowRightIcon,
  ArrowUturnLeftIcon,
  ClockIcon,
  MapPinIcon,
  ScaleIcon,
  UserGroupIcon,
  UserIcon,
} from '@heroicons/vue/24/outline'
import { itineraryRowInfoItems } from '../../../util/itineraryRowInfoItems'

const props = defineProps({
  row: { type: Object, required: true },
  tripType: { type: String, required: true },
  /** Gọn hơn khi nhiều chặng (modal rộng). */
  compact: { type: Boolean, default: false },
  /** Modal nhúng — bỏ icon, giảm padding. */
  dense: { type: Boolean, default: false },
})

const { t } = useI18n()

const toneClass = {
  emerald: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
  rose: 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300',
  sky: 'bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300',
  violet: 'bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300',
  amber: 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
}

const ICONS = {
  clock: ClockIcon,
  'arrow-right': ArrowRightIcon,
  'arrow-uturn': ArrowUturnLeftIcon,
  'map-pin': MapPinIcon,
  'user-group': UserGroupIcon,
  user: UserIcon,
  scale: ScaleIcon,
}

function iconFor(key) {
  return ICONS[key] || ClockIcon
}

const items = computed(() => itineraryRowInfoItems(props.row, { tripType: props.tripType, t }))

const gridClass = computed(() => {
  const n = items.value.length
  if (props.compact && n >= 3) return 'grid-cols-1 sm:grid-cols-3'
  if (n >= 3) return 'sm:grid-cols-2 lg:grid-cols-3'
  return 'sm:grid-cols-2'
})
</script>
