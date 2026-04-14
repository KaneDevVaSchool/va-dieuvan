<template>
  <Teleport to="body">
    <div
      v-if="state.open"
      class="fixed inset-0 z-[200] flex items-center justify-center bg-black/40 p-4"
      role="alertdialog"
      aria-modal="true"
      :aria-labelledby="titleId"
      @click.self="closeAppMessage"
    >
      <div
        class="max-h-[min(32rem,90vh)] w-full overflow-hidden rounded-xl border shadow-xl"
        :class="[panelClass, state.apiDetails ? 'max-w-2xl' : 'max-w-lg']"
      >
        <div class="flex items-start justify-between gap-3 border-b px-4 py-3 sm:px-5" :class="headerBorderClass">
          <div :id="titleId" class="pr-2 text-sm font-semibold leading-snug" :class="titleClass">{{ state.title }}</div>
          <button
            type="button"
            class="shrink-0 rounded px-2 py-1 text-xs transition"
            :class="closeBtnClass"
            @click="closeAppMessage"
          >
            Đóng
          </button>
        </div>
        <div class="max-h-[min(22rem,75vh)] overflow-y-auto px-4 py-3 sm:px-5">
          <p class="whitespace-pre-wrap break-words text-sm leading-relaxed" :class="bodyClass">{{ state.body }}</p>
          <div
            v-if="state.variant === 'error' && state.apiDetails"
            class="mt-4 rounded-lg border border-slate-200/90 bg-slate-50 p-3 text-left dark:border-slate-600 dark:bg-slate-800/80"
          >
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Chi tiết kỹ thuật</p>
            <dl class="mt-2 space-y-1.5 font-mono text-[11px] text-slate-700 dark:text-slate-300">
              <div v-if="state.apiDetails.status != null" class="flex flex-wrap gap-x-2 gap-y-0.5">
                <dt class="shrink-0 text-slate-500 dark:text-slate-500">HTTP</dt>
                <dd>{{ state.apiDetails.status }}</dd>
              </div>
              <div class="flex flex-wrap gap-x-2 gap-y-0.5 break-all">
                <dt class="shrink-0 text-slate-500 dark:text-slate-500">Yêu cầu</dt>
                <dd>{{ state.apiDetails.method }} {{ state.apiDetails.path }}</dd>
              </div>
              <div v-if="state.apiDetails.retryAfter" class="flex flex-wrap gap-x-2 gap-y-0.5">
                <dt class="shrink-0 text-slate-500 dark:text-slate-500">Retry-After</dt>
                <dd>{{ state.apiDetails.retryAfter }} (giây hoặc ngày theo máy chủ)</dd>
              </div>
              <div v-if="state.apiDetails.serverRaw" class="pt-1">
                <dt class="text-slate-500 dark:text-slate-500">Phản hồi máy chủ</dt>
                <dd class="mt-0.5 whitespace-pre-wrap break-words text-slate-800 dark:text-slate-200">{{ state.apiDetails.serverRaw }}</dd>
              </div>
              <div v-if="state.apiDetails.networkHint" class="text-slate-600 dark:text-slate-400">
                {{ state.apiDetails.networkHint }}
              </div>
            </dl>
            <p
              v-if="state.apiDetails?.status === 429"
              class="mt-2 text-[10px] leading-relaxed text-slate-500 dark:text-slate-500"
            >
              Mã 429: máy chủ giới hạn số yêu cầu trong một phút. Đợi vài giây rồi thử lại; tránh bấm lặp liên tục.
            </p>
          </div>
        </div>
        <div class="flex justify-end border-t px-4 py-3 sm:px-5" :class="footerBorderClass">
          <button
            type="button"
            class="rounded-md px-4 py-2 text-sm font-medium text-white transition focus:outline-none focus:ring-2 focus:ring-offset-1 dark:focus:ring-offset-slate-900"
            :class="primaryBtnClass"
            @click="closeAppMessage"
          >
            OK
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onUnmounted, watch, watchEffect } from 'vue'
import { appMessageState as state, closeAppMessage } from '../../composables/appMessage'

const titleId = 'app-message-modal-title'

const panelClass = computed(() => {
  if (state.variant === 'success') {
    return 'border-emerald-200 bg-white dark:border-emerald-900/60 dark:bg-slate-900'
  }
  if (state.variant === 'info') {
    return 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900'
  }
  return 'border-rose-200 bg-white dark:border-rose-900/50 dark:bg-slate-900'
})

const headerBorderClass = computed(() =>
  state.variant === 'error'
    ? 'border-rose-200/80 dark:border-rose-900/50'
    : state.variant === 'success'
      ? 'border-emerald-200/80 dark:border-emerald-900/50'
      : 'border-slate-200 dark:border-slate-700',
)

const footerBorderClass = headerBorderClass

const titleClass = computed(() =>
  state.variant === 'error'
    ? 'text-rose-900 dark:text-rose-100'
    : state.variant === 'success'
      ? 'text-emerald-900 dark:text-emerald-100'
      : 'text-slate-900 dark:text-slate-100',
)

const bodyClass = computed(() =>
  state.variant === 'error'
    ? 'text-slate-800 dark:text-slate-200'
    : 'text-slate-700 dark:text-slate-300',
)

const closeBtnClass = computed(() =>
  state.variant === 'error'
    ? 'text-rose-700 hover:bg-rose-50 dark:text-rose-200 dark:hover:bg-rose-950/40'
    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800',
)

const primaryBtnClass = computed(() =>
  state.variant === 'error'
    ? 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500'
    : state.variant === 'success'
      ? 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500'
      : 'bg-slate-700 hover:bg-slate-800 focus:ring-slate-500 dark:bg-slate-600',
)

watch(
  () => state.open,
  (open) => {
    if (typeof document === 'undefined') return
    document.body.style.overflow = open ? 'hidden' : ''
  },
)

watchEffect((onCleanup) => {
  if (!state.open || typeof window === 'undefined') return
  const onKey = (e) => {
    if (e.key === 'Escape') closeAppMessage()
  }
  window.addEventListener('keydown', onKey)
  onCleanup(() => window.removeEventListener('keydown', onKey))
})

onUnmounted(() => {
  if (typeof document !== 'undefined') document.body.style.overflow = ''
})
</script>
