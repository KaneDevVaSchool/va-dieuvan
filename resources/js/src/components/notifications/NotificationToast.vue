<template>
  <Teleport to="body">
    <div
      class="pointer-events-none fixed z-[300] flex flex-col gap-2 print:hidden
             bottom-[5.5rem] left-3 right-3
             md:bottom-auto md:left-auto md:right-4 md:top-4 md:w-[min(22rem,calc(100vw-2rem))]"
      aria-live="polite"
      aria-label="Thông báo mới"
    >
      <TransitionGroup
        tag="div"
        name="va-toast"
        class="flex flex-col gap-2"
      >
        <div
          v-for="toast in notifStore.toastQueue"
          :key="toast.id"
          class="pointer-events-auto relative overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-[0_8px_32px_-8px_rgba(15,23,42,0.18)] ring-1 ring-slate-900/5 transition-shadow dark:border-slate-700 dark:bg-slate-900 dark:ring-white/10"
          :class="toast.url ? 'cursor-pointer hover:shadow-lg' : ''"
          role="alert"
          @click="onToastClick(toast)"
        >
          <!-- Nội dung toast -->
          <div class="flex items-start gap-3 px-4 py-3 pr-10">
            <div
              class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300"
              aria-hidden="true"
            >
              <BellIcon class="h-4 w-4" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold leading-snug text-slate-900 dark:text-slate-50">
                {{ toast.title }}
              </p>
              <p
                v-if="toast.body"
                class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-slate-600 dark:text-slate-400"
              >
                {{ toast.body }}
              </p>
            </div>
          </div>

          <!-- Nút đóng -->
          <button
            type="button"
            class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300"
            :aria-label="t('notify.close')"
            @click.stop="notifStore.dismissToast(toast.id)"
          >
            <XMarkIcon class="h-3.5 w-3.5" />
          </button>

          <!-- Progress bar auto-dismiss -->
          <div class="absolute bottom-0 left-0 h-[3px] w-full overflow-hidden rounded-b-xl bg-slate-100 dark:bg-slate-800">
            <div
              class="h-full origin-left bg-sky-500 dark:bg-sky-400"
              :style="{ animationDuration: `${DISMISS_MS}ms` }"
              :class="progressClass"
            />
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { onUnmounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { BellIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useNotificationStore } from '../../store/notificationCenter'

const { t } = useI18n()
const router = useRouter()
const notifStore = useNotificationStore()

const DISMISS_MS = 5000

/** Kích hoạt animation progress bar khi có toast */
const progressClass = ref('va-toast-progress')

/** Map id → timer để tự dismiss */
const timers = new Map()

function scheduleAutoDismiss(id) {
  if (timers.has(id)) return
  const handle = window.setTimeout(() => {
    notifStore.dismissToast(id)
    timers.delete(id)
  }, DISMISS_MS)
  timers.set(id, handle)
}

function onToastClick(toast) {
  notifStore.dismissToast(toast.id)
  if (toast.url && typeof toast.url === 'string' && toast.url.startsWith('/')) {
    void router.push(toast.url)
  }
}

watch(
  () => notifStore.toastQueue.map((t) => t.id),
  (ids) => {
    for (const id of ids) {
      scheduleAutoDismiss(id)
    }
  },
  { immediate: true },
)

onUnmounted(() => {
  for (const handle of timers.values()) {
    clearTimeout(handle)
  }
  timers.clear()
})
</script>

<style scoped>
/* Slide-in từ phải trên desktop, fade-up trên mobile */
.va-toast-enter-active {
  transition: opacity 0.2s ease, transform 0.25s cubic-bezier(0.34, 1.4, 0.64, 1);
}
.va-toast-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease, max-height 0.2s ease;
}
.va-toast-enter-from {
  opacity: 0;
  transform: translateX(1.5rem);
}
.va-toast-leave-to {
  opacity: 0;
  transform: translateX(1.5rem);
}
.va-toast-move {
  transition: transform 0.2s ease;
}

/* Progress bar đếm ngược */
.va-toast-progress {
  animation: va-progress-shrink linear forwards;
}
@keyframes va-progress-shrink {
  from { transform: scaleX(1); }
  to   { transform: scaleX(0); }
}
</style>
