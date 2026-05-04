<template>
  <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm ring-1 ring-slate-950/[0.03] sm:p-5">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h3 class="text-sm font-semibold text-slate-900">{{ title }}</h3>
      <div v-if="pdfUrl && !pdfLoading" class="flex flex-wrap items-center gap-1.5">
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-40"
          :disabled="zoom <= ZOOM_MIN"
          @click="zoomOut"
        >
          {{ zoomOutLabel }}
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50 disabled:opacity-40"
          :disabled="zoom >= ZOOM_MAX"
          @click="zoomIn"
        >
          {{ zoomInLabel }}
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
          @click="fullscreenOpen = true"
        >
          {{ fullscreenLabel }}
        </button>
      </div>
    </div>

    <div
      v-if="pdfLoading"
      class="flex min-h-[320px] flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-slate-200 bg-slate-50/80 sm:min-h-[420px]"
    >
      <span class="h-8 w-8 animate-spin rounded-full border-2 border-slate-200 border-t-slate-700" />
      <span class="text-xs font-medium text-slate-600">{{ loadingLabel }}</span>
    </div>

    <div
      v-else-if="pdfError"
      class="flex min-h-[220px] flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-slate-200 bg-slate-50/80 px-4 text-center sm:min-h-[280px]"
    >
      <p class="text-xs font-medium text-rose-700">{{ pdfError }}</p>
      <button
        v-if="showDownload && hasCreatedRecord"
        type="button"
        class="rounded-lg border border-slate-900 bg-black px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-neutral-900 disabled:opacity-50"
        :disabled="downloadDisabled"
        @click="$emit('download')"
      >
        <span v-if="downloadBusy" class="mr-2 inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white align-middle" />
        {{ fallbackDownloadLabel }}
      </button>
    </div>

    <div
      v-else-if="pdfUrl"
      class="relative overflow-auto rounded-lg border border-slate-200 bg-slate-100"
      :style="{ maxHeight: 'min(78vh, 900px)' }"
    >
      <div
        class="origin-top-left"
        :style="{
          transform: `scale(${zoom})`,
          width: `${100 / zoom}%`,
        }"
      >
        <iframe
          :src="pdfUrl"
          class="block h-[min(72vh,840px)] w-full border-0 bg-white"
          :title="title"
        />
      </div>
    </div>

    <p v-else class="rounded-lg border border-dashed border-slate-200 bg-slate-50/80 px-4 py-8 text-center text-xs leading-relaxed text-slate-600">
      {{ placeholder }}
    </p>

    <button
      v-if="pdfUrl && showClose"
      type="button"
      class="self-start text-xs font-medium text-slate-500 underline-offset-2 hover:text-slate-800 hover:underline"
      @click="$emit('close')"
    >
      {{ closeLabel }}
    </button>
  </div>

  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="fullscreenOpen && pdfUrl"
        class="fixed inset-0 z-[210] flex flex-col bg-black/90 p-3 sm:p-6"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
      >
        <div class="mb-3 flex shrink-0 flex-wrap items-center justify-between gap-2">
          <p class="text-xs font-medium text-white/90">{{ title }}</p>
          <button
            type="button"
            class="rounded-lg border border-white/30 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur hover:bg-white/20"
            @click="fullscreenOpen = false"
          >
            {{ fullscreenCloseLabel }}
          </button>
        </div>
        <div class="min-h-0 flex-1 overflow-hidden rounded-lg bg-white">
          <iframe :src="pdfUrl" class="h-full w-full border-0" :title="title" />
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, watchEffect } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  placeholder: { type: String, default: '' },
  pdfUrl: { type: String, default: null },
  pdfLoading: { type: Boolean, default: false },
  pdfError: { type: String, default: '' },
  hasCreatedRecord: { type: Boolean, default: false },
  showDownload: { type: Boolean, default: true },
  downloadBusy: { type: Boolean, default: false },
  downloadDisabled: { type: Boolean, default: false },
  fallbackDownloadLabel: { type: String, required: true },
  loadingLabel: { type: String, required: true },
  zoomInLabel: { type: String, required: true },
  zoomOutLabel: { type: String, required: true },
  fullscreenLabel: { type: String, required: true },
  fullscreenCloseLabel: { type: String, required: true },
  showClose: { type: Boolean, default: false },
  closeLabel: { type: String, default: '' },
})

defineEmits(['download', 'close'])

const ZOOM_MIN = 0.65
const ZOOM_MAX = 1.35
const ZOOM_STEP = 0.1

const zoom = ref(1)
const fullscreenOpen = ref(false)

watch(fullscreenOpen, (open) => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = open ? 'hidden' : ''
})

watchEffect((onCleanup) => {
  if (typeof window === 'undefined' || !fullscreenOpen.value) return
  const onKey = (e) => {
    if (e.key === 'Escape') fullscreenOpen.value = false
  }
  window.addEventListener('keydown', onKey)
  onCleanup(() => window.removeEventListener('keydown', onKey))
})

function zoomIn() {
  zoom.value = Math.min(ZOOM_MAX, Math.round((zoom.value + ZOOM_STEP) * 100) / 100)
}

function zoomOut() {
  zoom.value = Math.max(ZOOM_MIN, Math.round((zoom.value - ZOOM_STEP) * 100) / 100)
}
</script>
