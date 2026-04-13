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
        class="max-h-[min(24rem,85vh)] w-full max-w-lg overflow-hidden rounded-lg border shadow-lg"
        :class="panelClass"
      >
        <div class="flex items-start justify-between gap-3 border-b px-4 py-3" :class="headerBorderClass">
          <div :id="titleId" class="text-sm font-semibold" :class="titleClass">{{ state.title }}</div>
          <button
            type="button"
            class="shrink-0 rounded px-2 py-1 text-xs transition"
            :class="closeBtnClass"
            @click="closeAppMessage"
          >
            Đóng
          </button>
        </div>
        <div class="max-h-[min(18rem,70vh)] overflow-y-auto px-4 py-3">
          <p class="whitespace-pre-wrap break-words text-sm leading-relaxed" :class="bodyClass">{{ state.body }}</p>
        </div>
        <div class="flex justify-end border-t px-4 py-3" :class="footerBorderClass">
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
