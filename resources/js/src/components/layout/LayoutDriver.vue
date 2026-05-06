<template>
  <div
    class="h-dvh min-h-0 w-full overflow-hidden bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 flex flex-col supports-[padding:max(0px)]:pb-[env(safe-area-inset-bottom)]"
  >
    <main
      id="app-main-scroll"
      class="min-h-0 min-w-0 flex-1 overflow-y-auto overflow-x-hidden overscroll-y-contain scrollbar-hidden px-3 pb-[calc(3.5rem+env(safe-area-inset-bottom))] sm:px-4"
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
