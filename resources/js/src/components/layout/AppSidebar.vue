<template>
  <!-- Sidebar dọc -->
  <aside
    v-if="axis === 'vertical'"
    :class="verticalAsideClass"
  >
    <div class="shrink-0 border-b border-slate-100 bg-white/90 px-2 py-2.5 backdrop-blur-sm md:px-4 md:py-3 dark:border-slate-700 dark:bg-slate-900/90">
      <div
        class="flex items-center gap-2.5"
        :class="ui.sidebarCollapsed ? 'justify-center' : 'justify-start'"
      >
        <AppLogo class="shrink-0" :class="ui.sidebarCollapsed ? 'scale-90' : 'scale-100'" size="sm" />
        <div v-if="!ui.sidebarCollapsed" class="min-w-0 flex-1">
          <div class="truncate text-xs font-semibold tracking-tight text-va-900 dark:text-va-100">
            {{ t('app.title') }}
          </div>
          <div class="mt-0.5 hidden text-[11px] leading-snug text-slate-500 lg:block dark:text-slate-400">
            Vehicle Dispatching · VA Schools
          </div>
        </div>
      </div>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-1 py-2 md:px-2 md:py-2.5">
      <nav class="space-y-0.5" :aria-label="t('app.title')">
        <template v-for="(section, si) in sections" :key="'v' + si">
          <div
            v-if="section.headingKey && !ui.sidebarCollapsed"
            class="mb-1 mt-3 flex items-center gap-2 px-2 first:mt-0"
          >
            <span class="h-1 w-1 shrink-0 rounded-full bg-va-700/70" aria-hidden="true" />
            <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 lg:text-[11px] dark:text-slate-400">
              {{ t(section.headingKey) }}
            </span>
          </div>
          <SidebarNavItem
            v-for="item in section.items"
            :key="'v' + item.to"
            :to="item.to"
            :label="t(item.labelKey)"
            :icon="item.icon"
            :badge-count="badgeCount(item)"
            :variant="navVariant"
          />
        </template>
      </nav>
    </div>

    <SidebarAccountBlock layout="vertical" :compact="ui.sidebarCollapsed" />

    <div
      class="flex shrink-0 items-center justify-center gap-1 border-t border-slate-200/80 bg-white/90 px-1 py-2 dark:border-slate-700 dark:bg-slate-900/90"
    >
      <button
        type="button"
        class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
        :title="t('app.sidebar_cycle_layout')"
        @click="cyclePreference"
      >
        <ArrowsRightLeftIcon class="h-5 w-5" aria-hidden="true" />
        <span class="sr-only">{{ t('app.sidebar_cycle_layout') }}</span>
      </button>
      <button
        type="button"
        class="inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
        :title="ui.sidebarCollapsed ? t('app.sidebar_expand') : t('app.sidebar_collapse')"
        @click="ui.toggleSidebarCollapsed()"
      >
        <ChevronDoubleLeftIcon v-if="!ui.sidebarCollapsed" class="h-5 w-5" aria-hidden="true" />
        <ChevronDoubleRightIcon v-else class="h-5 w-5" aria-hidden="true" />
        <span class="sr-only">
          {{ ui.sidebarCollapsed ? t('app.sidebar_expand') : t('app.sidebar_collapse') }}
        </span>
      </button>
    </div>
    <p class="sr-only" aria-live="polite">{{ t(preferenceLabelKey) }}</p>
  </aside>

  <!-- Thanh ngang -->
  <header
    v-else
    class="flex shrink-0 flex-col border-b border-slate-200/80 bg-white/95 shadow-sm backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/95"
  >
    <div class="flex min-h-[3.5rem] flex-wrap items-stretch gap-x-2 gap-y-2 px-2 py-2 sm:min-h-[4rem] sm:gap-3 sm:px-4">
      <div class="flex shrink-0 items-center">
        <AppLogo size="sm" />
      </div>

      <nav
        class="order-last flex min-h-[2.75rem] w-full min-w-0 flex-[1_1_100%] items-center gap-0.5 overflow-x-auto overflow-y-hidden overscroll-x-contain sm:order-none sm:flex-[1_1_auto] sm:px-0"
        :aria-label="t('app.title')"
      >
        <template v-for="(section, si) in sections" :key="'h' + si">
          <span
            v-if="si > 0"
            class="mx-0.5 h-7 w-px shrink-0 self-center bg-slate-200 dark:bg-slate-600"
            aria-hidden="true"
          />
          <span
            v-if="section.headingKey"
            class="hidden shrink-0 self-center px-1 text-[9px] font-bold uppercase leading-none tracking-wide text-slate-400 sm:inline md:text-[10px] dark:text-slate-500"
          >
            {{ t(section.headingKey) }}
          </span>
          <SidebarNavItem
            v-for="item in section.items"
            :key="'h' + item.to"
            :to="item.to"
            :label="t(item.labelKey)"
            :icon="item.icon"
            :badge-count="badgeCount(item)"
            variant="horizontal"
          />
        </template>
      </nav>

      <SidebarAccountBlock layout="horizontal" class="ml-auto shrink-0" />
    </div>

    <div
      class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 px-2 py-1.5 sm:px-4 dark:border-slate-700"
    >
      <span class="max-w-[70%] truncate text-[10px] text-slate-500 dark:text-slate-400">
        {{ t(preferenceLabelKey) }}
      </span>
      <button
        type="button"
        class="inline-flex shrink-0 items-center gap-1 rounded-md border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
        @click="cyclePreference"
      >
        <ArrowsRightLeftIcon class="h-4 w-4" aria-hidden="true" />
        {{ t('app.sidebar_cycle_layout') }}
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowsRightLeftIcon,
  ChevronDoubleLeftIcon,
  ChevronDoubleRightIcon,
} from '@heroicons/vue/24/outline'
import AppLogo from '../branding/AppLogo.vue'
import SidebarNavItem from '../nav/SidebarNavItem.vue'
import SidebarAccountBlock from './SidebarAccountBlock.vue'
import { useNavSections } from '../../composables/useNavSections'
import { useSidebarLayout } from '../../composables/useSidebarLayout'
import { useUiStore } from '../../store/ui'

const { t } = useI18n()
const { sections, badgeCount } = useNavSections()
const { axis, preferenceLabelKey, cyclePreference } = useSidebarLayout()
const ui = useUiStore()

const navVariant = computed(() =>
  ui.sidebarCollapsed ? 'vertical-compact' : 'vertical-full',
)

const verticalAsideClass = computed(() => {
  const base = [
    'flex flex-col border-slate-200/80 bg-gradient-to-b from-white via-white to-slate-50/90',
    'border-r shadow-[inset_-1px_0_0_0_rgba(15,23,42,0.04)] dark:border-slate-700 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950/90',
  ]
  if (ui.sidebarCollapsed) {
    base.push('w-[4.25rem] sm:w-14')
  } else {
    base.push('w-[min(17.5rem,calc(100vw-3rem))] min-w-[13rem] sm:w-56 md:w-60 lg:w-64')
  }
  return base.join(' ')
})
</script>
