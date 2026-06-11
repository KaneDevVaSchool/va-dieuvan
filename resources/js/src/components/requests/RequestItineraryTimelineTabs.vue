<template>
  <div v-if="legs.length" class="min-w-0" :data-testid="compact ? 'itinerary-timeline-strip' : 'itinerary-timeline-tabs'">
    <div
      role="tablist"
      class="flex gap-1.5 overflow-x-auto"
      :class="compact ? 'pb-0.5' : 'gap-0.5 border-b border-slate-100 dark:border-slate-800'"
      :aria-label="t('request_detail.ops_itinerary_timeline_tablist_aria')"
    >
      <button
        v-for="(leg, i) in legs"
        :key="`${leg.label}-${i}`"
        type="button"
        role="tab"
        :aria-selected="!compact && activeIdx === i"
        :tabindex="compact ? -1 : activeIdx === i ? 0 : -1"
        class="relative -mb-px shrink-0 border-b-2 px-3 py-2.5 text-left transition"
        :class="[
          compact ? compactChipClass(leg.tone) : tabButtonClass(leg.tone, i),
          compact ? 'cursor-default rounded-t-lg border-b-0 mb-0 mr-1 max-w-[14rem]' : '',
        ]"
        :data-testid="`itinerary-leg-tab-${i}`"
        @click="compact ? undefined : (activeIdx = i)"
      >
        <span class="block text-[10px] font-medium uppercase tracking-wide opacity-90">{{ leg.label }}</span>
        <span
          v-if="leg.time"
          class="mt-0.5 block truncate text-xs tabular-nums font-normal"
          :class="compact ? '' : activeIdx === i ? 'text-slate-700 dark:text-slate-200' : 'text-slate-500 dark:text-slate-400'"
        >
          {{ leg.time }}
        </span>
        <span
          v-else-if="compact && leg.place"
          class="mt-0.5 block truncate text-xs font-normal opacity-90"
        >
          {{ legPlacePreview(leg.place) }}
        </span>
      </button>
    </div>

    <div
      v-if="!compact && activeLeg"
      role="tabpanel"
      class="px-1 py-3 sm:px-2"
      :data-testid="`itinerary-leg-panel-${activeIdx}`"
    >
      <div
        class="rounded-xl border px-4 py-3"
        :class="panelSurfaceClass(activeLeg.tone)"
      >
        <p v-if="activeLeg.time" class="text-xs tabular-nums text-slate-600 dark:text-slate-300">
          {{ activeLeg.time }}
        </p>
        <div v-if="activeLeg.place" class="mt-2 min-w-0">
          <p
            class="whitespace-pre-wrap text-sm leading-relaxed text-slate-800 dark:text-slate-100"
            :class="placeNeedsCollapse(activeLeg.place) && !placeOpen ? 'line-clamp-6' : ''"
          >
            {{ activeLeg.place }}
          </p>
          <button
            v-if="placeNeedsCollapse(activeLeg.place)"
            type="button"
            class="mt-2 text-xs font-medium text-va-800 underline decoration-va-300 underline-offset-2 hover:decoration-va-600 dark:text-va-300"
            :data-testid="`itinerary-leg-place-toggle-${activeIdx}`"
            @click="placeOpen = !placeOpen"
          >
            {{ placeOpen ? t('request_detail.ops_itinerary_place_show_less') : t('request_detail.ops_itinerary_place_show_more') }}
          </button>
        </div>
        <p
          v-if="!activeLeg.time && !activeLeg.place"
          class="text-sm italic text-slate-400 dark:text-slate-500"
        >
          {{ t('request_detail.ops_no_data') }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  legs: { type: Array, default: () => [] },
  /** Thu gọn: chỉ dải tab/chip (khi thẻ hành trình đang đóng) */
  compact: { type: Boolean, default: false },
})

const { t } = useI18n()

const activeIdx = ref(0)
const placeOpen = ref(false)

watch(
  () => props.legs,
  () => {
    activeIdx.value = 0
    placeOpen.value = false
  },
)

watch(activeIdx, () => {
  placeOpen.value = false
})

const activeLeg = computed(() => props.legs[activeIdx.value] ?? null)

const PANEL_SURFACE = {
  out: 'border-emerald-200/80 bg-emerald-50/40 text-emerald-950 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-100',
  pickup: 'border-emerald-200/80 bg-emerald-50/40 text-emerald-950 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-100',
  waypoint: 'border-amber-200/80 bg-amber-50/40 text-amber-950 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100',
  back: 'border-rose-200/80 bg-rose-50/40 text-rose-950 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-100',
}

const TAB_ACTIVE = {
  out: 'border-emerald-600 text-emerald-900 dark:border-emerald-400 dark:text-emerald-100',
  pickup: 'border-emerald-600 text-emerald-900 dark:border-emerald-400 dark:text-emerald-100',
  waypoint: 'border-amber-600 text-amber-950 dark:border-amber-400 dark:text-amber-100',
  back: 'border-rose-600 text-rose-900 dark:border-rose-400 dark:text-rose-100',
}

const COMPACT_CHIP = {
  out: 'border border-emerald-200/80 bg-emerald-50/70 text-emerald-900 dark:border-emerald-800/60 dark:bg-emerald-950/30 dark:text-emerald-100',
  pickup: 'border border-emerald-200/80 bg-emerald-50/70 text-emerald-900 dark:border-emerald-800/60 dark:bg-emerald-950/30 dark:text-emerald-100',
  waypoint: 'border border-amber-200/80 bg-amber-50/70 text-amber-950 dark:border-amber-800/60 dark:bg-amber-950/30 dark:text-amber-100',
  back: 'border border-rose-200/80 bg-rose-50/70 text-rose-900 dark:border-rose-800/60 dark:bg-rose-950/30 dark:text-rose-100',
}

function panelSurfaceClass(tone) {
  return PANEL_SURFACE[tone] || PANEL_SURFACE.back
}

function tabButtonClass(tone, i) {
  const active = activeIdx.value === i
  if (active) return TAB_ACTIVE[tone] || TAB_ACTIVE.back
  return 'border-transparent text-slate-500 hover:border-slate-200 hover:text-slate-800 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:text-slate-200'
}

function compactChipClass(tone) {
  return COMPACT_CHIP[tone] || COMPACT_CHIP.back
}

function placeNeedsCollapse(place) {
  const s = String(place || '')
  return s.length > 200 || s.split(/\n/).length > 5
}

function legPlacePreview(place) {
  const s = String(place || '').replace(/\s+/g, ' ').trim()
  if (s.length <= 48) return s
  return `${s.slice(0, 48)}…`
}
</script>
