<template>
  <section class="space-y-3" :aria-label="title">
    <div>
      <p class="text-sm font-semibold text-driver-muted">{{ title }}</p>
      <p v-if="hint" class="mt-0.5 text-xs leading-snug text-driver-muted/80">{{ hint }}</p>
    </div>

    <input
      ref="multiRef"
      type="file"
      accept="image/*"
      multiple
      class="hidden"
      data-testid="driver-cost-create-receipt-input"
      @change="onNativePick"
    />

    <div class="grid gap-3 sm:grid-cols-2">
      <button
        type="button"
        class="flex min-h-[48px] items-center justify-center gap-2 rounded-xl bg-driver-surface px-3 text-sm font-semibold text-driver-accent ring-1 ring-driver-accent/30 transition hover:bg-driver-elevated active:scale-[0.99]"
        data-testid="driver-cost-create-receipt-gallery"
        @click="openPicker(false)"
      >
        <PhotoIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
        {{ addPhotos }}
      </button>
      <button
        type="button"
        class="flex min-h-[48px] items-center justify-center gap-2 rounded-xl bg-driver-accent/15 px-3 text-sm font-semibold text-driver-ink ring-1 ring-driver-accent/35 transition hover:bg-driver-accent/25 active:scale-[0.99]"
        data-testid="driver-cost-create-receipt-camera"
        @click="openPicker(true)"
      >
        <CameraIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
        {{ cameraCapture }}
      </button>
    </div>

    <p v-if="busyCount" class="text-sm text-amber-200/90">{{ preparingLabel }} ({{ busyCount }})</p>

    <div
      v-if="!staged.length"
      class="rounded-2xl bg-driver-surface/80 px-4 py-8 text-center ring-1 ring-white/[0.05]"
    >
      <PhotoIcon class="mx-auto h-10 w-10 text-driver-muted/50" aria-hidden="true" />
      <p class="mt-3 text-sm text-driver-muted">{{ emptyText }}</p>
    </div>

    <div v-else class="grid grid-cols-3 gap-2 sm:grid-cols-4">
      <div
        v-for="row in staged"
        :key="row.key"
        class="group relative overflow-hidden rounded-xl bg-driver-surface ring-1 ring-white/[0.06]"
      >
        <div class="relative aspect-square w-full">
          <img
            :src="row.previewUrl"
            :alt="previewAlt"
            loading="lazy"
            decoding="async"
            class="h-full w-full object-cover"
          />
          <span
            v-if="row.preparing"
            class="absolute inset-0 flex items-center justify-center bg-black/45 text-xs font-semibold text-white"
          >
            …
          </span>
        </div>
        <button
          type="button"
          class="absolute right-1 top-1 rounded-lg bg-rose-600/90 p-1.5 text-white shadow-lg backdrop-blur-sm transition hover:bg-rose-500 active:scale-95"
          :aria-label="removeAria"
          :disabled="row.preparing"
          @click="removeRow(row.key)"
        >
          <TrashIcon class="h-4 w-4" aria-hidden="true" />
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onBeforeUnmount, ref } from 'vue'
import { CameraIcon, PhotoIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { compressImageFile } from '../../../util/imageCompress'
import { showAppError } from '../../../composables/appMessage'

const props = defineProps({
  title: { type: String, required: true },
  hint: { type: String, default: '' },
  emptyText: { type: String, required: true },
  addPhotos: { type: String, required: true },
  cameraCapture: { type: String, required: true },
  previewAlt: { type: String, required: true },
  removeAria: { type: String, required: true },
  preparingLabel: { type: String, required: true },
  prepareFail: { type: String, required: true },
})

const multiRef = ref(null)
/** @type {import('vue').Ref<{ key: string, previewUrl: string, file: File|null, preparing: boolean }[]>} */
const staged = ref([])
const busyCount = ref(0)

function revokeRow(row) {
  if (row?.previewUrl) URL.revokeObjectURL(row.previewUrl)
}

function removeRow(key) {
  const row = staged.value.find((r) => r.key === key)
  if (row) revokeRow(row)
  staged.value = staged.value.filter((r) => r.key !== key)
}

function openPicker(cameraOnly) {
  const el = multiRef.value
  if (!el) return
  el.value = ''
  if (cameraOnly) el.setAttribute('capture', 'environment')
  else el.removeAttribute('capture')
  el.click()
}

async function onNativePick(e) {
  const input = e.target
  const files = [...(input.files || [])]
  input.value = ''
  for (const file of files) {
    if (!file.type.startsWith('image/')) continue
    void stageFile(file)
  }
}

async function stageFile(file) {
  const key = `local-${Date.now()}-${Math.random().toString(36).slice(2)}`
  const previewUrl = URL.createObjectURL(file)
  staged.value.push({ key, previewUrl, file: null, preparing: true })
  busyCount.value += 1
  try {
    const prepared = await compressImageFile(file)
    URL.revokeObjectURL(previewUrl)
    const nextUrl = URL.createObjectURL(prepared)
    const row = staged.value.find((r) => r.key === key)
    if (row) {
      row.previewUrl = nextUrl
      row.file = prepared
      row.preparing = false
    }
  } catch {
    removeRow(key)
    showAppError(props.prepareFail)
  } finally {
    busyCount.value = Math.max(0, busyCount.value - 1)
  }
}

/** @returns {File[]} */
function getReadyFiles() {
  return staged.value.filter((r) => r.file && !r.preparing).map((r) => r.file)
}

function hasPendingPrepare() {
  return busyCount.value > 0 || staged.value.some((r) => r.preparing)
}

function clearAll() {
  for (const row of staged.value) revokeRow(row)
  staged.value = []
}

onBeforeUnmount(() => {
  for (const row of staged.value) revokeRow(row)
})

defineExpose({ getReadyFiles, hasPendingPrepare, clearAll })
</script>
