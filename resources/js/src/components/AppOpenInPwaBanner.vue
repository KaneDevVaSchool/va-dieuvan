<template>
  <Transition name="banner-slide">
    <div
      v-if="visible"
      class="fixed inset-x-0 top-0 z-50 flex items-center gap-3 bg-[#0f172a] px-4 py-2.5 text-sm text-white shadow-lg"
      style="padding-top: max(0.625rem, env(safe-area-inset-top))"
    >
      <span class="flex-1 leading-snug">
        Mở trong ứng dụng <strong>VA Dispatch</strong> để dùng đầy đủ tính năng
      </span>
      <a
        :href="currentUrl"
        class="shrink-0 rounded-md bg-[#8B1A3A] px-3 py-1.5 text-xs font-semibold text-white"
        @click.prevent="openInApp"
      >
        Mở App
      </a>
      <button
        class="shrink-0 p-1 text-slate-400 hover:text-white"
        aria-label="Đóng"
        @click="dismiss"
      >
        ✕
      </button>
    </div>
  </Transition>
</template>

<script setup>
import { onMounted, ref } from 'vue'

const STORAGE_KEY = 'va_pwa_banner_dismissed'
const visible = ref(false)
const currentUrl = ref(window.location.href)

onMounted(() => {
  if (sessionStorage.getItem(STORAGE_KEY)) return
  const isStandalone =
    window.matchMedia('(display-mode: standalone)').matches ||
    window.navigator.standalone === true
  if (isStandalone) return
  // Only show when navigated from outside (email, external link)
  const isInternal = document.referrer && new URL(document.referrer).origin === window.location.origin
  if (isInternal) return
  visible.value = true
})

function openInApp() {
  // On Android Chrome, this navigation will be intercepted by the installed PWA
  // due to handle_links: "preferred" in the manifest
  window.location.href = currentUrl.value
}

function dismiss() {
  sessionStorage.setItem(STORAGE_KEY, '1')
  visible.value = false
}
</script>

<style scoped>
.banner-slide-enter-active,
.banner-slide-leave-active {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.banner-slide-enter-from,
.banner-slide-leave-to {
  transform: translateY(-100%);
  opacity: 0;
}
</style>
