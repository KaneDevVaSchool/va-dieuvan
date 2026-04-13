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
      :class="[
        'min-h-0 min-w-0 flex-1 overflow-y-auto overflow-x-hidden overscroll-y-contain px-3 py-3 sm:px-4 sm:py-4 md:px-6 md:py-5',
        isHorizontalMobilePad,
      ]"
    >
      <slot />
    </main>
    <!-- Menu dưới: chỉ layout ngang + viewport &lt; md -->
    <div
      v-if="isHorizontal"
      class="pointer-events-none fixed inset-x-0 bottom-0 z-40 md:hidden"
    >
      <div class="pointer-events-auto pb-[env(safe-area-inset-bottom)]">
        <MobileBottomNav />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import AppSidebar from './AppSidebar.vue'
import MobileBottomNav from '../nav/MobileBottomNav.vue'
import { useSidebarLayout } from '../../composables/useSidebarLayout'
import { useAuthStore } from '../../store'

const { isVertical, isHorizontal } = useSidebarLayout()

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
