<template>
  <!-- Sidebar dọc -->
  <aside
    v-if="axis === 'vertical'"
    :class="verticalAsideClass"
  >
    <div
      class="shrink-0 border-b border-white/15 bg-[color:var(--va-brand)]/95 backdrop-blur-sm"
      :class="isCollapsed ? 'px-1 py-2 md:px-1.5 md:py-2.5' : 'px-2 py-3 md:px-3 md:py-3.5'"
    >
      <div
        class="relative flex w-full min-w-0 items-center justify-center"
        :class="isCollapsed ? 'flex-col gap-1.5' : mobileDrawer ? 'px-7' : 'px-9'"
      >
        <AppLogo
          align="center"
          class="w-full min-w-0 [&_img]:mx-auto [&_img]:object-contain"
          :class="isCollapsed ? 'max-w-full px-0.5' : 'max-w-full'"
          :variant="isCollapsed ? 'icon' : 'wordmark'"
          :size="isCollapsed ? 'sm' : mobileDrawer ? 'md' : 'lg'"
        />
        <button
          type="button"
          class="inline-flex shrink-0 items-center justify-center rounded-md text-white/85 transition hover:bg-white/10"
          :class="
            isCollapsed
              ? 'h-7 w-7'
              : 'absolute right-0 top-1/2 h-7 w-7 -translate-y-1/2 sm:h-8 sm:w-8'
          "
          :title="mobileDrawer ? t('common.close') : isCollapsed ? t('app.sidebar_expand') : t('app.sidebar_collapse')"
          @click="mobileDrawer ? emit('close-menu') : ui.toggleSidebarCollapsed()"
        >
          <XMarkIcon v-if="mobileDrawer" class="h-5 w-5" aria-hidden="true" />
          <ChevronLeftIcon v-else-if="!isCollapsed" class="h-4 w-4 sm:h-[1.125rem] sm:w-[1.125rem]" aria-hidden="true" />
          <ChevronRightIcon v-else class="h-4 w-4" aria-hidden="true" />
          <span class="sr-only">
            {{ mobileDrawer ? t('common.close') : isCollapsed ? t('app.sidebar_expand') : t('app.sidebar_collapse') }}
          </span>
        </button>
      </div>
    </div>

    <div
      class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain scrollbar-hidden border-white/5 px-1 py-2 md:px-2 md:py-2.5"
    >
      <nav class="space-y-0.5" :aria-label="t('app.title')">
        <template v-for="(section, si) in sections" :key="'vsec' + si">
          <!-- Khối không có tiêu đề section (trang đầu) -->
          <template v-if="!section.headingKey">
            <template v-for="(item, ii) in section.items" :key="'v' + (item.to || item.labelKey)">
              <template v-if="item.children?.length">
                <SidebarCollapsedNavGroup
                  v-if="isCollapsed"
                  :class="ii > 0 || si > 0 ? 'mt-3' : 'mt-0.5'"
                  :label="t(item.labelKey)"
                  :icon="item.icon"
                  :children="item.children"
                  :badge-count="badgeCount"
                  :developing-label-for="navStatusPill"
                  tone="brand"
                  :open="flyoutOpenKey === subGroupKey(si, ii)"
                  @toggle="toggleFlyout(subGroupKey(si, ii))"
                  @close="flyoutOpenKey = null"
                />
                <template v-else>
                  <div class="px-1">
                    <button
                      type="button"
                      class="flex w-full items-center justify-between gap-1 rounded-md px-2 py-1.5 text-left text-xs font-medium text-white/85 transition hover:bg-white/10"
                      :class="ii > 0 || si > 0 ? 'mt-3' : 'mt-0.5'"
                      :aria-expanded="isGroupOpen(subGroupKey(si, ii))"
                      @click="toggleGroup(subGroupKey(si, ii))"
                    >
                      <span class="truncate">{{ t(item.labelKey) }}</span>
                      <ChevronDownIcon
                        class="h-4 w-4 shrink-0 text-white/55 transition-transform"
                        :class="isGroupOpen(subGroupKey(si, ii)) ? 'rotate-0' : '-rotate-90'"
                        aria-hidden="true"
                      />
                    </button>
                  </div>
                  <div
                    v-show="isGroupOpen(subGroupKey(si, ii))"
                    class="space-y-0.5"
                  >
                    <SidebarNavItem
                      v-for="c in item.children"
                      :key="'vc' + c.to"
                      :to="c.to"
                      :label="t(c.labelKey)"
                      :icon="c.icon"
                      :badge-count="badgeCount(c)"
                      :status-pill="navStatusPill(c)"
                      :variant="navVariant"
                      tone="brand"
                    />
                  </div>
                </template>
              </template>
              <SidebarNavItem
                v-else
                :to="item.to"
                :label="t(item.labelKey)"
                :icon="item.icon"
                :badge-count="badgeCount(item)"
                :status-pill="navStatusPill(item)"
                :variant="navVariant"
                tone="brand"
              />
            </template>
          </template>

          <!-- Section có tiêu đề: collapse cả khối -->
          <div v-else :class="si >= 1 ? 'mt-3 border-t border-white/15 pt-2' : 'mt-2'">
            <div v-if="!isCollapsed" class="mb-1 px-1">
              <button
                type="button"
                class="flex w-full items-center justify-between gap-1 rounded-md px-2 py-1.5 text-left text-xs font-medium text-white/80 transition hover:bg-white/10"
                :aria-expanded="isGroupOpen(sectionGroupKey(si))"
                @click="toggleGroup(sectionGroupKey(si))"
              >
                <span class="truncate">{{ t(section.headingKey) }}</span>
                <ChevronDownIcon
                  class="h-4 w-4 shrink-0 text-white/50 transition-transform"
                  :class="isGroupOpen(sectionGroupKey(si)) ? 'rotate-0' : '-rotate-90'"
                  aria-hidden="true"
                />
              </button>
            </div>
            <div v-show="isCollapsed || isGroupOpen(sectionGroupKey(si))" class="space-y-0.5">
              <template v-for="(item, ii) in section.items" :key="'v' + (item.to || item.labelKey)">
                <template v-if="item.children?.length">
                  <SidebarCollapsedNavGroup
                    v-if="isCollapsed"
                    class="mt-2"
                    :label="t(item.labelKey)"
                    :icon="item.icon"
                    :children="item.children"
                    :badge-count="badgeCount"
                    :developing-label-for="navStatusPill"
                    tone="brand"
                    :open="flyoutOpenKey === subGroupKey(si, ii)"
                    @toggle="toggleFlyout(subGroupKey(si, ii))"
                    @close="flyoutOpenKey = null"
                  />
                  <!-- Một section = một nhóm con (vd. Hệ thống): bỏ accordion cấp 2, liệt kê link phẳng -->
                  <div
                    v-else-if="isSingleChildGroupSection(section)"
                    class="space-y-px"
                  >
                    <SidebarNavItem
                      v-for="c in item.children"
                      :key="'vc' + c.to"
                      :to="c.to"
                      :label="t(c.labelKey)"
                      :icon="c.icon"
                      :badge-count="badgeCount(c)"
                      :status-pill="navStatusPill(c)"
                      :variant="navVariant"
                      tone="brand"
                    />
                  </div>
                  <template v-else>
                    <div class="px-1">
                      <button
                        type="button"
                        class="mt-2 flex w-full items-center justify-between gap-1 rounded-md px-2 py-1.5 text-left text-xs font-medium text-white/85 transition hover:bg-white/10"
                        :aria-expanded="isGroupOpen(subGroupKey(si, ii))"
                        @click="toggleGroup(subGroupKey(si, ii))"
                      >
                        <span class="truncate">{{ t(item.labelKey) }}</span>
                        <ChevronDownIcon
                          class="h-4 w-4 shrink-0 text-white/55 transition-transform"
                          :class="isGroupOpen(subGroupKey(si, ii)) ? 'rotate-0' : '-rotate-90'"
                          aria-hidden="true"
                        />
                      </button>
                    </div>
                    <div
                      v-show="isGroupOpen(subGroupKey(si, ii))"
                      class="ml-2 space-y-0.5 border-l border-white/15 pl-2"
                    >
                      <SidebarNavItem
                        v-for="c in item.children"
                        :key="'vc' + c.to"
                        :to="c.to"
                        :label="t(c.labelKey)"
                        :icon="c.icon"
                        :badge-count="badgeCount(c)"
                        :status-pill="navStatusPill(c)"
                        :variant="navVariant"
                        tone="brand"
                      />
                    </div>
                  </template>
                </template>
                <SidebarNavItem
                  v-else
                  :to="item.to"
                  :label="t(item.labelKey)"
                  :icon="item.icon"
                  :badge-count="badgeCount(item)"
                  :status-pill="navStatusPill(item)"
                  :variant="navVariant"
                  tone="brand"
                />
              </template>
            </div>
          </div>
        </template>
      </nav>
    </div>

    <!-- Chuông thông báo: dọc theo sidebar, trên block tài khoản -->
    <div
      v-if="!mobileDrawer"
      class="shrink-0 border-t border-white/15 py-1.5 md:py-2"
      :class="isCollapsed ? 'flex justify-center px-1 md:px-1.5' : 'flex w-full items-center px-2 md:px-3'"
    >
      <NotificationBell tone="brand" :show-label="!isCollapsed" />
    </div>

    <SidebarAccountBlock v-if="!mobileDrawer" layout="vertical" brand :compact="isCollapsed" />

    <p class="sr-only" aria-live="polite">{{ t(preferenceLabelKey) }}</p>
  </aside>

  <!-- Thanh ngang (navbar) -->
  <header
    v-else
    class="print:hidden sticky top-0 z-30 flex w-full max-w-full shrink-0 flex-col border-b border-slate-200/90 bg-white/95 shadow-sm backdrop-blur-md dark:border-slate-700 dark:bg-slate-900/95"
  >
    <div
      class="flex w-full min-w-0 items-center gap-2 px-3 py-2.5 sm:min-h-14 sm:gap-3 sm:px-4 sm:py-2"
    >
      <div
        class="flex min-w-0 shrink-0 items-center gap-2 sm:gap-3 md:max-w-[min(18rem,36vw)]"
      >
        <button
          type="button"
          class="-ml-1.5 inline-flex h-10 w-9 shrink-0 items-center justify-center rounded-lg text-slate-700 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 dark:text-slate-100 dark:hover:bg-slate-800 md:hidden"
          :aria-label="t('app.menu')"
          aria-controls="mobile-sidebar-menu"
          aria-haspopup="dialog"
          :aria-expanded="mobileMenuOpen"
          @click="emit('open-menu')"
        >
          <Bars3Icon class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true" />
        </button>
        <AppLogo class="hidden shrink-0 md:block" size="sm" />
        <span
          class="hidden min-w-0 truncate text-sm font-bold leading-tight tracking-tight text-slate-900 dark:text-slate-100 sm:block"
          :title="t('app.title')"
        >
          {{ t('app.title_bar') }}
        </span>
      </div>

      <nav
        class="hidden min-h-9 min-w-0 flex-1 items-stretch divide-x divide-slate-200/90 overflow-x-auto overflow-y-hidden overscroll-x-contain [-ms-overflow-style:none] [scrollbar-width:none] dark:divide-slate-600 md:flex [&::-webkit-scrollbar]:hidden"
        :aria-label="t('app.title')"
      >
        <template v-for="(section, si) in sections" :key="'h' + si">
          <!-- Cấp 1: không có heading section — mục phẳng hoặc nhóm con (dropdown) -->
          <template v-if="!section.headingKey">
            <template v-for="item in section.items" :key="item.to || item.labelKey">
              <HorizontalNavGroup
                v-if="item.children?.length"
                :label="navBarLabel(item.labelKey)"
                :full-label="t(item.labelKey)"
                :items="item.children"
                :badge-count="badgeCount"
                :developing-label-for="navStatusPill"
                :t="t"
              />
              <SidebarNavItem
                v-else
                :to="item.to"
                :label="navBarLabel(item.labelKey)"
                :full-label="t(item.labelKey)"
                :icon="item.icon"
                :badge-count="badgeCount(item)"
                :status-pill="navStatusPill(item)"
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
              :developing-label-for="navStatusPill"
              :t="t"
            />
            <HorizontalNavGroup
              v-else-if="section.items.length === 1 && section.items[0].children?.length"
              :label="navBarLabel(section.headingKey)"
              :full-label="t(section.headingKey)"
              :items="section.items[0].children"
              :badge-count="badgeCount"
              :developing-label-for="navStatusPill"
              :t="t"
            />
            <SidebarNavItem
              v-else-if="section.items.length === 1"
              :key="'h1' + section.items[0].to"
              :to="section.items[0].to"
              :label="navBarLabel(section.items[0].labelKey)"
              :full-label="t(section.items[0].labelKey)"
              :icon="section.items[0].icon"
              :badge-count="badgeCount(section.items[0])"
              :status-pill="navStatusPill(section.items[0])"
              variant="horizontal"
            />
          </template>
        </template>
      </nav>

      <!-- Mobile: ô tìm kiếm chiếm phần giữa header; md+: pill gọn cạnh chuông. -->
      <div class="flex min-w-0 flex-1 md:flex-none">
        <GlobalSearch />
      </div>
      <div class="flex shrink-0 items-center gap-2">
        <NotificationBell tone="light" />
        <SidebarAccountBlock layout="horizontal" />
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { Bars3Icon, XMarkIcon, ChevronLeftIcon, ChevronRightIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'
import AppLogo from '../branding/AppLogo.vue'
import HorizontalNavGroup from '../nav/HorizontalNavGroup.vue'
import SidebarCollapsedNavGroup from '../nav/SidebarCollapsedNavGroup.vue'
import SidebarNavItem from '../nav/SidebarNavItem.vue'
import SidebarAccountBlock from './SidebarAccountBlock.vue'
import NotificationBell from '../notifications/NotificationBell.vue'
import GlobalSearch from './GlobalSearch.vue'
import { useNavSections } from '../../composables/useNavSections'
import { useNavBarLabel } from '../../composables/useNavBarLabel'
import { useSidebarLayout } from '../../composables/useSidebarLayout'
import { useUiStore } from '../../store/ui'
import { useAuthStore } from '../../store'

const { t } = useI18n()
const auth = useAuthStore()

function navStatusPill(navItem) {
  if (!navItem?.featureKey) return ''
  const st = auth.featureToggleRuntimeState(navItem.featureKey)
  return st?.maintenance_mode ? t('nav.badge_developing') : ''
}
const navBarLabel = useNavBarLabel()
const { sections, badgeCount } = useNavSections()
const { axis: layoutAxis, preferenceLabelKey } = useSidebarLayout()
const ui = useUiStore()
const props = defineProps({ mobileDrawer: Boolean, mobileMenuOpen: Boolean })
const emit = defineEmits(['open-menu', 'close-menu'])
const axis = computed(() => props.mobileDrawer ? 'vertical' : layoutAxis.value)
const isCollapsed = computed(() => !props.mobileDrawer && ui.sidebarCollapsed)

const navVariant = computed(() =>
  isCollapsed.value ? 'vertical-compact' : 'vertical-full',
)

/** Mở/đóng nhóm menu — mặc định mở hết để dễ cuộn xem nhiều mục */
const openGroups = reactive({})

/** Rail thu nhỏ: một flyout nhóm con mở tại một thời điểm */
const flyoutOpenKey = ref(null)

function toggleFlyout(key) {
  flyoutOpenKey.value = flyoutOpenKey.value === key ? null : key
}

function sectionGroupKey(si) {
  return `sec-${si}`
}

/** Section có tiêu đề và chỉ một nhóm con (không `to` ở mục cha) — sidebar mở: bỏ accordion cấp 2 */
function isSingleChildGroupSection(section) {
  if (!section.headingKey) return false
  const first = section.items?.[0]
  return first && section.items.length === 1 && first.children?.length && !first.to
}

function subGroupKey(si, ii) {
  return `sub-${si}-${ii}`
}

function expandAllNavGroups() {
  sections.value.forEach((section, si) => {
    if (section.headingKey) {
      openGroups[sectionGroupKey(si)] = true
    }
    section.items.forEach((item, ii) => {
      if (item.children?.length) {
        openGroups[subGroupKey(si, ii)] = true
      }
    })
  })
}

watch(sections, expandAllNavGroups, { immediate: true })

watch(
  isCollapsed,
  (collapsed) => {
    if (!collapsed) flyoutOpenKey.value = null
  },
)

function isGroupOpen(key) {
  return openGroups[key] === true
}

function toggleGroup(key) {
  openGroups[key] = !isGroupOpen(key)
}

const verticalAsideClass = computed(() => {
  const base = [
    'print:hidden flex flex-col border-r border-white/10 bg-[color:var(--va-brand)] text-white shadow-[inset_-1px_0_0_0_rgba(0,0,0,0.08)]',
  ]
  if (props.mobileDrawer) {
    base.push('h-full w-full pb-[env(safe-area-inset-bottom)] pt-[env(safe-area-inset-top)]')
  } else if (isCollapsed.value) {
    /** Rail cố định ~4.25rem — tránh sm:w-14 quá hẹp làm vỡ logo + nút */
    base.push('w-[4.25rem] min-w-[4.25rem] max-w-[4.25rem]')
  } else {
    base.push('w-[min(17.5rem,calc(100vw-3rem))] min-w-[13rem] sm:w-56 md:w-60 lg:w-64')
  }
  return base.join(' ')
})

</script>
