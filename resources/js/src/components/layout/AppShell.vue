<template>
  <div
    :class="[
      'h-dvh min-h-0 w-full overflow-hidden bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100',
      'supports-[padding:max(0px)]:pt-[env(safe-area-inset-top)] supports-[padding:max(0px)]:pb-[env(safe-area-inset-bottom)]',
      isVertical ? 'flex flex-row' : 'flex flex-col',
    ]"
  >
    <AppSidebar />
    <main
      id="app-main-scroll"
      :class="[
        'flex min-h-0 min-w-0 flex-1 flex-col overscroll-y-contain bg-slate-50 scrollbar-hidden dark:bg-slate-950',
        mainFlush
          ? 'overflow-hidden px-0 py-0'
          : 'overflow-y-auto overflow-x-hidden px-3 py-3 sm:px-4 sm:py-4 md:px-6 md:py-5 lg:px-8 lg:py-6',
        isHorizontalMobilePad,
      ]"
    >
      <OperationalStatusBanner class="print:hidden" :class="mainFlush ? 'shrink-0 px-3 pt-3 sm:px-4 md:px-6 lg:px-8' : ''" />
      <div class="flex min-h-0 min-w-0 flex-1 flex-col">
        <slot />
      </div>
    </main>
    <!-- Menu dưới: chỉ layout ngang + viewport &lt; md -->
    <div
      v-if="isHorizontal"
      class="pointer-events-none fixed inset-x-0 bottom-0 z-40 print:hidden md:hidden"
    >
      <div class="pointer-events-auto pb-[env(safe-area-inset-bottom)]">
        <MobileBottomNav />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAppViewportLock } from '../../composables/useAppViewportLock'
import AppSidebar from './AppSidebar.vue'
import OperationalStatusBanner from './OperationalStatusBanner.vue'
import MobileBottomNav from '../nav/MobileBottomNav.vue'
import { useSidebarLayout } from '../../composables/useSidebarLayout'
import { useAuthStore } from '../../store'

const route = useRoute()
const mainFlush = computed(() => route.matched.some((record) => record.meta.mainFlush === true))

const { isVertical, isHorizontal } = useSidebarLayout()

useAppViewportLock()

const isHorizontalMobilePad = computed(() =>
  isHorizontal.value
    ? 'pb-[calc(3.5rem+env(safe-area-inset-bottom))] md:pb-5'
    : '',
)
const auth = useAuthStore()

onMounted(async () => {
  try {
    if (auth.isLoggedIn && !auth.user) {
      await auth.fetchMe()
    }
  } catch {
    /* router guard handles */
  }
})
</script>
