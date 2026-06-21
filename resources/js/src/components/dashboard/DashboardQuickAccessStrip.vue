<script setup>
import { computed, markRaw, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  BanknotesIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  DocumentMagnifyingGlassIcon,
  PlusCircleIcon,
  TableCellsIcon,
  TruckIcon,
  UserGroupIcon,
} from '@heroicons/vue/24/outline'
import { buildStaffPrefixedPath as staffPath } from '../../config/dispatchWebBase'

const { t } = useI18n()

const quickScrollRef = ref(null)
const quickCanScrollLeft = ref(false)
const quickCanScrollRight = ref(false)

const quickLinks = computed(() => [
  {
    key: 'trips',
    to: staffPath('/trips'),
    title: t('dashboard_analytics.quick_trips'),
    hint: t('dashboard_analytics.quick_trips_tooltip'),
    icon: markRaw(TruckIcon),
    tone: 'sky',
  },
  {
    key: 'new_request',
    to: staffPath('/dispatch-requests/new'),
    title: t('dashboard_analytics.quick_new_request'),
    hint: t('dashboard_analytics.quick_new_request_tooltip'),
    icon: markRaw(PlusCircleIcon),
    tone: 'emerald',
  },
  {
    key: 'requests',
    to: staffPath('/requests'),
    title: t('dashboard_analytics.quick_requests'),
    hint: t('dashboard_analytics.quick_requests_tooltip'),
    icon: markRaw(ClipboardDocumentListIcon),
    tone: 'violet',
  },
  {
    key: 'resources',
    to: staffPath('/resources/list'),
    title: t('dashboard_analytics.quick_resources'),
    hint: t('dashboard_analytics.quick_resources_tooltip'),
    icon: markRaw(UserGroupIcon),
    tone: 'brand',
  },
  {
    key: 'cargo',
    to: staffPath('/cargo'),
    title: t('dashboard_analytics.quick_cargo'),
    hint: t('dashboard_analytics.quick_cargo_tooltip'),
    icon: markRaw(CubeIcon),
    tone: 'amber',
  },
  {
    key: 'costs',
    to: staffPath('/costs'),
    title: t('dashboard_analytics.quick_costs'),
    hint: t('dashboard_analytics.quick_costs_tooltip'),
    icon: markRaw(BanknotesIcon),
    tone: 'rose',
  },
  {
    key: 'pricing',
    to: staffPath('/pricing'),
    title: t('dashboard_analytics.quick_pricing'),
    hint: t('dashboard_analytics.quick_pricing_tooltip'),
    icon: markRaw(TableCellsIcon),
    tone: 'sky',
  },
  {
    key: 'audit',
    to: staffPath('/audit-logs'),
    title: t('dashboard_analytics.quick_audit'),
    hint: t('dashboard_analytics.quick_audit_tooltip'),
    icon: markRaw(DocumentMagnifyingGlassIcon),
    tone: 'slate',
  },
])

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

function updateQuickScrollState() {
  const el = quickScrollRef.value
  if (!el) {
    quickCanScrollLeft.value = false
    quickCanScrollRight.value = false
    return
  }
  const { scrollLeft, scrollWidth, clientWidth } = el
  quickCanScrollLeft.value = scrollLeft > 2
  quickCanScrollRight.value = scrollLeft + clientWidth < scrollWidth - 2
}

function scrollQuickLinks(direction) {
  const el = quickScrollRef.value
  if (!el) return
  const step = Math.max(160, Math.floor(el.clientWidth * 0.82))
  el.scrollBy({ left: direction * step, behavior: 'smooth' })
}

function onResize() {
  updateQuickScrollState()
}

onMounted(() => {
  window.addEventListener('resize', onResize)
  nextTick(() => updateQuickScrollState())
})

onUnmounted(() => {
  window.removeEventListener('resize', onResize)
})
</script>

<template>
  <section
    class="kpi-strip relative overflow-x-hidden rounded-xl border border-slate-200/80 bg-gradient-to-b from-slate-50/90 to-white px-4 py-4 shadow-sm sm:px-5 sm:py-5 dark:border-slate-700 dark:from-slate-900/80 dark:to-slate-900/40"
    :aria-label="t('dashboard_analytics.quick_strip_aria')"
  >
    <div class="kpi-strip__bg-outer" aria-hidden="true">
      <div class="kpi-strip__bg-inner" />
    </div>

    <header class="relative mb-3 flex flex-wrap items-end justify-between gap-2">
      <div>
        <p class="text-[10px] font-semibold uppercase tracking-[0.14em] text-va-800/80">
          {{ t('dashboard_analytics.kpi_strip_eyebrow') }}
        </p>
        <h2 class="text-sm font-semibold tracking-tight text-slate-800 dark:text-slate-100">
          {{ t('dashboard_analytics.quick_title') }}
        </h2>
      </div>
      <p class="max-w-md text-right text-[11px] text-slate-500 dark:text-slate-400">
        {{ t('dashboard_analytics.quick_strip_hint') }}
      </p>
    </header>

    <div class="relative flex items-stretch gap-1 sm:gap-2">
      <button
        type="button"
        class="flex w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200/90 bg-white/90 text-slate-600 shadow-sm transition hover:border-va-200/70 hover:text-va-800 disabled:pointer-events-none disabled:opacity-25 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300 sm:w-9"
        :disabled="!quickCanScrollLeft"
        :aria-label="t('dashboard_analytics.quick_scroll_prev')"
        data-testid="dash-quick-scroll-prev"
        @click="scrollQuickLinks(-1)"
      >
        <ChevronLeftIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
      </button>

      <div
        ref="quickScrollRef"
        class="dash-quick-scroll min-w-0 flex-1 overflow-x-auto overflow-y-hidden scroll-smooth pb-1"
        @scroll.passive="updateQuickScrollState"
      >
        <div class="flex flex-nowrap gap-2 sm:gap-3">
          <RouterLink
            v-for="item in quickLinks"
            :key="item.key"
            :to="item.to"
            :title="item.hint"
            :class="[
              'kpi-card kpi-card--interactive flex w-[7.25rem] shrink-0 flex-col items-center justify-center gap-2 px-2 py-3 text-center sm:w-32',
              toneClass[item.tone] ?? toneClass.brand,
            ]"
            :data-testid="`dash-quick-${item.key}`"
          >
            <span
              :class="[
                'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ring-1',
                iconToneClass[item.tone] ?? iconToneClass.brand,
              ]"
              aria-hidden="true"
            >
              <component :is="item.icon" class="h-5 w-5" />
            </span>
            <span class="w-full line-clamp-2 text-[11px] font-semibold uppercase leading-tight tracking-wide text-slate-700 dark:text-slate-200 sm:text-xs">
              {{ item.title }}
            </span>
          </RouterLink>
        </div>
      </div>

      <button
        type="button"
        class="flex w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200/90 bg-white/90 text-slate-600 shadow-sm transition hover:border-va-200/70 hover:text-va-800 disabled:pointer-events-none disabled:opacity-25 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300 sm:w-9"
        :disabled="!quickCanScrollRight"
        :aria-label="t('dashboard_analytics.quick_scroll_next')"
        data-testid="dash-quick-scroll-next"
        @click="scrollQuickLinks(1)"
      >
        <ChevronRightIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
      </button>
    </div>
  </section>
</template>

<style scoped>
.dash-quick-scroll {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.dash-quick-scroll::-webkit-scrollbar {
  display: none;
}
</style>
