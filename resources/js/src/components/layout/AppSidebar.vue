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
          <template v-for="(item, ii) in section.items" :key="'v' + (item.to || item.labelKey)">
            <template v-if="item.children?.length">
              <div
                v-if="!ui.sidebarCollapsed"
                class="mb-1 flex items-center gap-2 px-2"
                :class="ii > 0 || si > 0 ? 'mt-3' : 'mt-0'"
              >
                <span class="h-1 w-1 shrink-0 rounded-full bg-va-700/70" aria-hidden="true" />
                <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 lg:text-[11px] dark:text-slate-400">
                  {{ t(item.labelKey) }}
                </span>
              </div>
              <SidebarNavItem
                v-for="c in item.children"
                :key="'vc' + c.to"
                :to="c.to"
                :label="t(c.labelKey)"
                :icon="c.icon"
                :badge-count="badgeCount(c)"
                :variant="navVariant"
              />
            </template>
            <SidebarNavItem
              v-else
              :to="item.to"
              :label="t(item.labelKey)"
              :icon="item.icon"
              :badge-count="badgeCount(item)"
              :variant="navVariant"
            />
          </template>
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
        @click="onCycleLayoutClick"
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

  <!-- Thanh ngang (navbar) -->
  <header
    v-else
    class="sticky top-0 z-30 flex w-full max-w-full shrink-0 flex-col border-b border-slate-200/90 bg-white/95 shadow-sm backdrop-blur-md dark:border-slate-700 dark:bg-slate-900/95"
  >
    <div
      class="flex w-full min-w-0 items-center gap-2 px-3 py-2.5 sm:min-h-14 sm:gap-3 sm:px-4 sm:py-2"
    >
      <div
        class="flex min-w-0 flex-1 items-center gap-2.5 sm:gap-3 md:max-w-[min(18rem,36vw)] md:flex-none md:shrink-0"
      >
        <AppLogo class="shrink-0" size="sm" />
        <span
          class="min-w-0 max-w-[min(12rem,28vw)] truncate text-xs font-semibold leading-tight tracking-tight text-slate-800 dark:text-slate-100 sm:max-w-[14rem] sm:text-sm"
          :title="t('app.title')"
        >
          {{ t('app.title_bar') }}
        </span>
      </div>

      <nav
        class="hidden min-h-9 min-w-0 flex-1 items-center gap-0 overflow-x-auto overflow-y-hidden overscroll-x-contain [-ms-overflow-style:none] [scrollbar-width:none] md:flex [&::-webkit-scrollbar]:hidden"
        :aria-label="t('app.title')"
      >
        <template v-for="(section, si) in sections" :key="'h' + si">
          <span
            v-if="si > 0"
            class="mx-0.5 h-5 w-px shrink-0 self-center bg-slate-200 dark:bg-slate-600"
            aria-hidden="true"
          />
          <!-- Cấp 1: không có heading section — mục phẳng hoặc nhóm con (dropdown) -->
          <template v-if="!section.headingKey">
            <template v-for="item in section.items" :key="item.to || item.labelKey">
              <HorizontalNavGroup
                v-if="item.children?.length"
                :label="navBarLabel(item.labelKey)"
                :full-label="t(item.labelKey)"
                :items="item.children"
                :badge-count="badgeCount"
                :t="t"
                :icon-key="item.icon"
              />
              <SidebarNavItem
                v-else
                :to="item.to"
                :label="navBarLabel(item.labelKey)"
                :full-label="t(item.labelKey)"
                :icon="item.icon"
                :badge-count="badgeCount(item)"
                variant="horizontal"
              />
            </template>
          </template>
          <!-- Cấp 2+: nhóm theo section — dropdown -->
          <template v-else>
            <HorizontalNavGroup
              v-if="section.items.length > 1"
              :label="navBarLabel(section.headingKey)"
              :full-label="t(section.headingKey)"
              :items="section.items"
              :badge-count="badgeCount"
              :t="t"
            />
            <HorizontalNavGroup
              v-else-if="section.items.length === 1 && section.items[0].children?.length"
              :label="navBarLabel(section.headingKey)"
              :full-label="t(section.headingKey)"
              :items="section.items[0].children"
              :badge-count="badgeCount"
              :t="t"
              :icon-key="section.items[0].icon"
            />
            <SidebarNavItem
              v-else-if="section.items.length === 1"
              :key="'h1' + section.items[0].to"
              :to="section.items[0].to"
              :label="navBarLabel(section.items[0].labelKey)"
              :full-label="t(section.items[0].labelKey)"
              :icon="section.items[0].icon"
              :badge-count="badgeCount(section.items[0])"
              variant="horizontal"
            />
          </template>
        </template>
      </nav>

      <div class="flex shrink-0 items-center">
        <SidebarAccountBlock layout="horizontal" />
      </div>
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
import HorizontalNavGroup from '../nav/HorizontalNavGroup.vue'
import SidebarNavItem from '../nav/SidebarNavItem.vue'
import SidebarAccountBlock from './SidebarAccountBlock.vue'
import { useNavSections } from '../../composables/useNavSections'
import { useNavBarLabel } from '../../composables/useNavBarLabel'
import { useSidebarLayout } from '../../composables/useSidebarLayout'
import { useUiStore } from '../../store/ui'

const { t } = useI18n()
const navBarLabel = useNavBarLabel()
const { sections, badgeCount } = useNavSections()
const { axis, preferenceLabelKey } = useSidebarLayout()
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

/** Nút xoay bố cục (sidebar dọc + header mobile); trước đây thiếu handler nên bấm không có tác dụng */
function onCycleLayoutClick() {
  ui.cycleSidebarAxisPreference()
}
</script>
