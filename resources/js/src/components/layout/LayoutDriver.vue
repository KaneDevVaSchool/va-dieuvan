<template>
  <div
    ref="shellRef"
    class="driver-pwa-shell flex h-dvh min-h-0 w-full flex-col overflow-hidden bg-[#020B0B] text-[#eaf8f5] [--driver-bottom-nav-height:3.875rem]"
  >
    <AppOpenInPwaBanner />
    <ImpersonationBanner class="mx-auto w-full max-w-2xl" />
    <main
      id="app-main-scroll"
      class="min-h-0 min-w-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-y-contain pb-[calc(var(--driver-bottom-nav-height,3.875rem)+env(safe-area-inset-bottom))] scrollbar-hidden"
    >
      <div class="mx-auto min-h-full w-full max-w-2xl">
        <slot />
      </div>
    </main>

    <!-- Bottom nav: always visible on driver layout -->
    <div class="pointer-events-none fixed inset-x-0 bottom-0 z-40 print:hidden">
      <!-- Solid fill under safe-area + during overscroll; nav alone does not paint the padded strip -->
      <div
        class="pointer-events-auto bg-[#080f0d] pb-[env(safe-area-inset-bottom)]"
      >
        <div ref="navRef">
          <MobileBottomNav class="mx-auto max-w-2xl" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import AppOpenInPwaBanner from '../AppOpenInPwaBanner.vue'
import ImpersonationBanner from './ImpersonationBanner.vue'
import MobileBottomNav from '../nav/MobileBottomNav.vue'

/**
 * Khóa cuộn document trong suốt phiên driver shell → chỉ #app-main-scroll cuộn.
 * Ngăn thanh địa chỉ mobile co/giãn theo document scroll, giữ viewport ổn định
 * và loại bỏ hiện tượng footer (bottom nav) giật lên lúc mới vào app.
 */
const SCROLL_LOCK_CLASS = 'driver-scroll-lock'
const shellRef = ref(null)
const navRef = ref(null)
let navObserver

onMounted(() => {
  if (typeof document !== 'undefined') {
    document.documentElement.classList.add(SCROLL_LOCK_CLASS)
  }
  const measureNav = () => {
    const height = navRef.value?.getBoundingClientRect().height
    if (height) shellRef.value?.style.setProperty('--driver-bottom-nav-height', `${height}px`)
  }
  measureNav()
  navObserver = new ResizeObserver(measureNav)
  if (navRef.value) navObserver.observe(navRef.value)
})

onBeforeUnmount(() => {
  navObserver?.disconnect()
  if (typeof document !== 'undefined') {
    document.documentElement.classList.remove(SCROLL_LOCK_CLASS)
  }
})
</script>
