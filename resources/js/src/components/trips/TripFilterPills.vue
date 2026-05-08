<template>
  <div class="sticky top-[3.25rem] z-20 -mx-3 border-b border-[rgba(255,255,255,0.06)] bg-driver-bg/92 px-3 pb-2 pt-2 backdrop-blur-md sm:-mx-4">
    <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-[#7fdcc8]">
      {{ t('trip_history_page.filters_title') }}
    </p>

    <!-- Status tabs + funnel button -->
    <div class="flex items-center gap-2">
      <div class="flex flex-1 gap-1.5 overflow-x-auto pb-1 scrollbar-none">
        <button
          v-for="opt in statusOptions"
          :key="opt.id"
          type="button"
          class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition active:scale-[0.98]"
          :class="
            modelValue === opt.id
              ? 'bg-[#7fdcc8] text-driver-bg'
              : 'border border-[rgba(255,255,255,0.08)] bg-driver-card text-driver-muted'
          "
          @click="$emit('update:modelValue', opt.id)"
        >
          {{ opt.label }}
        </button>
      </div>

      <!-- Funnel button -->
      <button
        type="button"
        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition active:scale-95"
        :class="hasAdvancedFilter
          ? 'bg-[#7fdcc8] text-driver-bg'
          : 'border border-[rgba(255,255,255,0.08)] bg-driver-card text-driver-muted'"
        :aria-label="t('trip_history_page.adv_filter_title')"
        @click="showPanel = true"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true">
          <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 0 1 .628.74v2.288a2.25 2.25 0 0 1-.659 1.59l-4.682 4.683a2.25 2.25 0 0 0-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 0 1 8 18.25v-5.757a2.25 2.25 0 0 0-.659-1.591L2.659 6.22A2.25 2.25 0 0 1 2 4.629V2.34a.75.75 0 0 1 .628-.74Z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>

    <!-- Active filter chips -->
    <div v-if="hasAdvancedFilter" class="mt-2 flex flex-wrap gap-1.5">
      <span
        v-if="localFilter.type"
        class="inline-flex items-center gap-1 rounded-full bg-[#7fdcc8]/15 px-2.5 py-1 text-[11px] font-semibold text-[#7fdcc8]"
      >
        {{ tripTypeLabel(localFilter.type) }}
        <button type="button" class="ml-0.5" @click="clearType">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3" aria-hidden="true">
            <path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z" />
          </svg>
        </button>
      </span>
      <span
        v-if="localFilter.date_from || localFilter.date_to"
        class="inline-flex items-center gap-1 rounded-full bg-[#7fdcc8]/15 px-2.5 py-1 text-[11px] font-semibold text-[#7fdcc8]"
      >
        {{ dateRangeChipLabel }}
        <button type="button" class="ml-0.5" @click="clearDates">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3" aria-hidden="true">
            <path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z" />
          </svg>
        </button>
      </span>
    </div>
  </div>

  <!-- Advanced filter bottom sheet -->
  <Teleport to="body">
    <Transition name="sheet">
      <div
        v-if="showPanel"
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 backdrop-blur-sm"
        @click.self="showPanel = false"
      >
        <div
          class="sheet-inner w-full max-w-lg max-h-[min(560px,85dvh)] overflow-y-auto overscroll-contain rounded-t-3xl bg-driver-card px-5 pb-[calc(2rem+env(safe-area-inset-bottom))] pt-5 ring-1 ring-white/10"
        >
          <!-- Handle -->
          <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-bold text-white">{{ t('trip_history_page.adv_filter_title') }}</h3>
            <button
              type="button"
              class="flex h-8 w-8 items-center justify-center rounded-full text-white/40 hover:text-white/70"
              @click="showPanel = false"
            >
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5" aria-hidden="true">
                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
              </svg>
            </button>
          </div>

          <!-- Trip type: single row, horizontal scroll — avoids wrapping -->
          <div class="mb-5">
            <p class="mb-2.5 text-xs font-semibold uppercase tracking-wider text-white/50 whitespace-nowrap">
              {{ t('trip_history_page.adv_filter_type_label') }}
            </p>
            <div class="-mx-1 flex flex-nowrap gap-2 overflow-x-auto px-1 pb-1 scrollbar-none [-webkit-overflow-scrolling:touch]">
              <button
                type="button"
                class="shrink-0 whitespace-nowrap rounded-full px-3.5 py-2 text-sm font-semibold transition active:scale-[0.97]"
                :class="draft.type === '' ? 'bg-[#7fdcc8] text-driver-bg' : 'border border-white/10 bg-white/5 text-white/60'"
                @click="draft.type = ''"
              >
                {{ t('trip_history_page.adv_filter_type_all') }}
              </button>
              <button
                v-for="tp in tripTypes"
                :key="tp.id"
                type="button"
                class="shrink-0 whitespace-nowrap rounded-full px-3.5 py-2 text-sm font-semibold transition active:scale-[0.97]"
                :class="draft.type === tp.id
                  ? 'bg-[#7fdcc8] text-driver-bg'
                  : 'border border-white/10 bg-white/5 text-white/60'"
                @click="draft.type = tp.id"
              >
                {{ tp.label }}
              </button>
            </div>
          </div>

          <!-- Date range: stacked on narrow screens; 2 cols sm+ -->
          <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="min-w-0">
              <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-white/50 whitespace-nowrap">
                {{ t('trip_history_page.adv_filter_date_from') }}
              </label>
              <input
                v-model="draft.date_from"
                type="date"
                class="box-border w-full min-h-[2.75rem] rounded-xl bg-white/[0.06] px-3 py-2.5 text-sm text-white ring-1 ring-white/10 focus:outline-none focus:ring-[#7fdcc8]/50 [color-scheme:dark]"
              />
            </div>
            <div class="min-w-0">
              <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-white/50 whitespace-nowrap">
                {{ t('trip_history_page.adv_filter_date_to') }}
              </label>
              <input
                v-model="draft.date_to"
                type="date"
                class="box-border w-full min-h-[2.75rem] rounded-xl bg-white/[0.06] px-3 py-2.5 text-sm text-white ring-1 ring-white/10 focus:outline-none focus:ring-[#7fdcc8]/50 [color-scheme:dark]"
              />
            </div>
          </div>

          <!-- Actions: equal columns, text stays one line -->
          <div class="grid grid-cols-2 gap-2 sm:gap-3">
            <button
              type="button"
              class="min-h-[48px] shrink-0 whitespace-nowrap rounded-xl px-2 py-2.5 text-center text-[13px] font-semibold leading-none text-white/50 ring-1 ring-white/15 transition active:scale-[0.98] sm:text-sm sm:py-3"
              @click="onReset"
            >
              {{ t('trip_history_page.adv_filter_reset') }}
            </button>
            <button
              type="button"
              class="min-h-[48px] shrink-0 whitespace-nowrap rounded-xl bg-driver-accent px-2 py-2.5 text-center text-[13px] font-bold leading-none text-driver-bg transition active:scale-[0.98] sm:text-sm sm:py-3"
              @click="onApply"
            >
              {{ t('trip_history_page.adv_filter_apply') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  modelValue: { type: String, required: true },
  advancedFilter: {
    type: Object,
    default: () => ({ type: '', date_from: '', date_to: '' }),
  },
})

const emit = defineEmits(['update:modelValue', 'update:advancedFilter'])

const { t } = useI18n()

const showPanel = ref(false)

const draft = reactive({
  type: props.advancedFilter?.type ?? '',
  date_from: props.advancedFilter?.date_from ?? '',
  date_to: props.advancedFilter?.date_to ?? '',
})

watch(() => props.advancedFilter, (v) => {
  if (v) {
    draft.type = v.type ?? ''
    draft.date_from = v.date_from ?? ''
    draft.date_to = v.date_to ?? ''
  }
}, { deep: true })

watch(showPanel, (open) => {
  if (open) {
    draft.type = props.advancedFilter?.type ?? ''
    draft.date_from = props.advancedFilter?.date_from ?? ''
    draft.date_to = props.advancedFilter?.date_to ?? ''
  }
})

const statusOptions = computed(() => [
  { id: 'all', label: t('trip_history_page.filter_all') },
  { id: 'in_progress', label: t('trip_history_page.filter_in_progress') },
  { id: 'completed', label: t('trip_history_page.filter_completed') },
  { id: 'pending', label: t('trip_history_page.filter_pending') },
  { id: 'cancelled', label: t('trip_history_page.filter_cancelled') },
])

const tripTypes = computed(() => [
  { id: 'p2p', label: t('trip_history_page.type_p2p') },
  { id: 'd2d', label: t('trip_history_page.type_d2d') },
  { id: 'ct', label: t('trip_history_page.type_ct') },
  { id: 'hh', label: t('trip_history_page.type_hh') },
])

const hasAdvancedFilter = computed(
  () => !!(props.advancedFilter?.type || props.advancedFilter?.date_from || props.advancedFilter?.date_to),
)

function tripTypeLabel(id) {
  return tripTypes.value.find((x) => x.id === id)?.label ?? id.toUpperCase()
}

const dateRangeChipLabel = computed(() => {
  const from = props.advancedFilter?.date_from
  const to = props.advancedFilter?.date_to
  if (from && to) return `${from} → ${to}`
  if (from) return `≥ ${from}`
  if (to) return `≤ ${to}`
  return ''
})

function clearType() {
  emit('update:advancedFilter', { ...props.advancedFilter, type: '' })
}

function clearDates() {
  emit('update:advancedFilter', { ...props.advancedFilter, date_from: '', date_to: '' })
}

function onApply() {
  emit('update:advancedFilter', { type: draft.type, date_from: draft.date_from, date_to: draft.date_to })
  showPanel.value = false
}

function onReset() {
  draft.type = ''
  draft.date_from = ''
  draft.date_to = ''
  emit('update:advancedFilter', { type: '', date_from: '', date_to: '' })
  showPanel.value = false
}
</script>

<style scoped>
.sheet-enter-active,
.sheet-leave-active {
  transition: opacity 0.2s;
}
.sheet-enter-active .sheet-inner,
.sheet-leave-active .sheet-inner {
  transition: transform 0.25s cubic-bezier(0.32, 0.72, 0, 1);
}
.sheet-enter-from,
.sheet-leave-to {
  opacity: 0;
}

.scrollbar-none {
  scrollbar-width: none;
}
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
</style>
