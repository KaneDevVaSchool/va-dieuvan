<template>
  <Teleport to="body">
    <div
      v-if="state.open"
      class="fixed inset-0 z-[210] flex items-center justify-center bg-black/40 p-4"
      role="dialog"
      aria-modal="true"
      :aria-labelledby="titleId"
      @click.self="onCancel"
    >
      <div
        class="max-h-[min(28rem,90vh)] w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
      >
        <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-700 sm:px-5">
          <h2 :id="titleId" class="text-sm font-semibold text-slate-900 dark:text-slate-100">
            {{ state.title }}
          </h2>
        </div>
        <div class="max-h-[min(18rem,70vh)] overflow-y-auto px-4 py-3 sm:px-5">
          <p class="whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-700 dark:text-slate-300">
            {{ state.message }}
          </p>
        </div>
        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-4 py-3 dark:border-slate-700 sm:px-5">
          <button
            type="button"
            class="rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
            @click="onCancel"
          >
            {{ state.cancelLabel }}
          </button>
          <button
            type="button"
            class="rounded-md px-4 py-2 text-sm font-medium text-white transition focus:outline-none focus:ring-2 focus:ring-offset-1 dark:focus:ring-offset-slate-900"
            :class="
              state.danger
                ? 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500'
                : 'bg-teal-700 hover:bg-teal-800 focus:ring-teal-600'
            "
            @click="onOk"
          >
            {{ state.confirmLabel }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { onUnmounted, watch, watchEffect } from 'vue'
import { confirmDialogState as state, resolveConfirm } from '../../composables/useConfirm'

const titleId = 'app-confirm-modal-title'

function onCancel() {
  resolveConfirm(false)
}

function onOk() {
  resolveConfirm(true)
}

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
    if (e.key === 'Escape') resolveConfirm(false)
  }
  window.addEventListener('keydown', onKey)
  onCleanup(() => window.removeEventListener('keydown', onKey))
})

onUnmounted(() => {
  if (typeof document !== 'undefined') document.body.style.overflow = ''
})
</script>
