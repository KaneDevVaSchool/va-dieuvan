<template>
  <div
    class="driver-pwa-shell flex h-dvh min-h-0 w-full flex-col overflow-hidden bg-[#020B0B] text-[#eaf8f5] [--driver-bottom-nav-height:3.5rem] supports-[padding:max(0px)]:pb-[env(safe-area-inset-bottom)]"
  >
    <main
      id="app-main-scroll"
      class="min-h-0 min-w-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-y-contain pb-[calc(var(--driver-bottom-nav-height,3.5rem)+env(safe-area-inset-bottom))] scrollbar-hidden"
    >
      <slot />
    </main>

    <!-- Bottom nav: always visible on driver layout -->
    <div class="pointer-events-none fixed inset-x-0 bottom-0 z-40 print:hidden">
      <div class="pointer-events-auto pb-[env(safe-area-inset-bottom)]">
        <MobileBottomNav />
      </div>
    </div>
  </div>
</template>

<script setup>
import MobileBottomNav from '../nav/MobileBottomNav.vue'
</script>
