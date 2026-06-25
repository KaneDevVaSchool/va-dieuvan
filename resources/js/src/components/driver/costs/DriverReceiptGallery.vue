<template>
  <section class="space-y-4" :aria-label="title">
    <div class="flex items-center justify-between gap-3">
      <h2
        class="text-driver-ink"
        :class="compactSection ? 'text-base font-semibold' : 'text-lg font-bold sm:text-xl'"
      >
        {{ title }}
      </h2>
      <span
        v-if="!readonly && canMutate"
        class="text-driver-muted"
        :class="compactSection ? 'text-xs' : 'text-xs font-medium sm:text-sm'"
      >
        {{ hintMutate }}
      </span>
    </div>

    <input
      ref="multiRef"
      type="file"
      accept="image/*"
      multiple
      class="hidden"
      @change="onNativePick"
    />

    <div
      v-if="!readonly && canMutate"
      class="grid gap-3 sm:grid-cols-2"
    >
      <button
        type="button"
        class="flex min-h-[52px] items-center justify-center gap-2 rounded-2xl bg-driver-surface px-4 text-base font-semibold text-driver-accent ring-1 ring-driver-accent/30 transition hover:bg-driver-elevated active:scale-[0.99]"
        @click="openPicker(false)"
      >
        <PhotoIcon class="h-6 w-6 shrink-0" aria-hidden="true" />
        {{ addPhotos }}
      </button>
      <button
        type="button"
        class="flex min-h-[52px] items-center justify-center gap-2 rounded-2xl bg-driver-accent/15 px-4 text-base font-semibold text-driver-ink ring-1 ring-driver-accent/35 transition hover:bg-driver-accent/25 active:scale-[0.99]"
        @click="openPicker(true)"
      >
        <CameraIcon class="h-6 w-6 shrink-0" aria-hidden="true" />
        {{ cameraCapture }}
      </button>
    </div>

    <p v-if="queueLen && !readonly" class="text-sm text-amber-200/90">
      {{ uploadingLabel }} ({{ queueLen }})
    </p>

    <div v-if="!displayRows.length" class="rounded-3xl bg-driver-surface/80 px-6 py-12 text-center ring-1 ring-white/[0.05]">
      <PhotoIcon class="mx-auto h-12 w-12 text-driver-muted/50" aria-hidden="true" />
      <p class="mt-4 text-base text-driver-muted">{{ emptyText }}</p>
    </div>

    <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3">
      <div
        v-for="(row, idx) in displayRows"
        :key="row.key"
        class="group relative overflow-hidden rounded-2xl bg-driver-surface ring-1 ring-white/[0.06]"
      >
        <button
          type="button"
          class="relative flex aspect-square w-full overflow-hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-driver-accent"
          @click="openPreview(idx)"
        >
          <img
            :src="rowImageSrc(row)"
            :alt="previewAlt"
            loading="lazy"
            decoding="async"
            class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
            @error="onImgErr"
          />
          <span
            class="pointer-events-none absolute inset-x-0 bottom-0 flex items-center justify-center gap-1 bg-gradient-to-t from-black/75 via-black/35 to-transparent py-3 text-xs font-semibold text-white"
          >
            <MagnifyingGlassPlusIcon class="h-4 w-4" aria-hidden="true" />
            {{ previewVerb }}
          </span>
          <span
            v-if="row.progress != null && row.progress < 1"
            class="absolute inset-x-0 bottom-0 h-1 bg-black/40"
            aria-hidden="true"
          >
            <span class="block h-full bg-driver-accent transition-all" :style="{ width: pct(row.progress) }" />
          </span>
        </button>

        <div
          v-if="canReorder && displayRows.length > 1 && row.progress == null && !String(row.key).startsWith('tmp')"
          class="absolute left-1 top-1 flex flex-col gap-1 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100"
        >
          <button
            type="button"
            class="rounded-lg bg-black/55 p-1.5 text-white backdrop-blur-sm disabled:opacity-30"
            :disabled="idx === 0"
            :aria-label="moveUp"
            @click.stop="moveItem(idx, -1)"
          >
            <ChevronUpIcon class="h-5 w-5" />
          </button>
          <button
            type="button"
            class="rounded-lg bg-black/55 p-1.5 text-white backdrop-blur-sm disabled:opacity-30"
            :disabled="idx === displayRows.length - 1"
            :aria-label="moveDown"
            @click.stop="moveItem(idx, 1)"
          >
            <ChevronDownIcon class="h-5 w-5" />
          </button>
        </div>

        <button
          v-if="canRemoveRow(row) && !readonly"
          type="button"
          class="absolute right-1 top-1 rounded-xl bg-rose-600/90 p-2 text-white shadow-lg backdrop-blur-sm transition hover:bg-rose-500 active:scale-95"
          :aria-label="removeAria"
          @click.stop="confirmRemove(row)"
        >
          <TrashIcon class="h-5 w-5" aria-hidden="true" />
        </button>
      </div>
    </div>

    <div v-if="failedJobs.length" class="rounded-2xl bg-rose-950/40 px-4 py-3 ring-1 ring-rose-500/25">
      <p class="text-sm font-semibold text-rose-200">{{ uploadFail }}</p>
      <ul class="mt-2 space-y-2">
        <li v-for="(fj, i) in failedJobs.filter((x) => x.file)" :key="i" class="flex flex-wrap items-center gap-2">
          <span class="min-w-0 flex-1 truncate text-sm text-rose-100/90">{{ fj.name }}</span>
          <button
            type="button"
            class="rounded-xl bg-white/10 px-3 py-2 text-sm font-semibold text-white ring-1 ring-white/15"
            @click="retryJob(fj)"
          >
            {{ retryLabel }}
          </button>
        </li>
      </ul>
    </div>

    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="previewOpen"
          class="fixed inset-0 z-[100] flex flex-col bg-black/95"
          role="dialog"
          aria-modal="true"
          :aria-label="previewAlt"
        >
          <div class="flex items-center justify-between gap-2 px-3 pt-[max(0.75rem,env(safe-area-inset-top))] pb-2">
            <button
              type="button"
              class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full bg-white/10 text-white"
              @click="closePreview"
            >
              <XMarkIcon class="h-7 w-7" />
            </button>
            <div class="flex flex-1 items-center justify-center gap-2">
              <button type="button" class="min-h-[44px] rounded-xl bg-white/10 px-4 text-sm font-semibold text-white" @click="zoomBy(-0.25)">
                −
              </button>
              <button type="button" class="min-h-[44px] rounded-xl bg-white/10 px-4 text-sm font-semibold text-white" @click="zoomBy(0.25)">
                +
              </button>
            </div>
            <span class="min-w-[3rem] text-center text-sm tabular-nums text-white/80">
              {{ previewIndex + 1 }}/{{ displayRows.length }}
            </span>
          </div>
          <div
            ref="previewStageRef"
            class="relative flex flex-1 touch-none items-center justify-center overflow-hidden px-2 pb-[max(1rem,env(safe-area-inset-bottom))]"
            @wheel.prevent="onWheelZoom"
          >
            <img
              v-if="previewSrc"
              :src="previewSrc"
              alt=""
              loading="eager"
              decoding="async"
              class="max-h-full max-w-full select-none transition-transform duration-150 ease-out"
              :style="{ transform: `scale(${previewZoom})` }"
              draggable="false"
            />
          </div>
          <div class="flex items-center justify-between gap-3 px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-2">
            <button
              type="button"
              class="min-h-[48px] flex-1 rounded-2xl bg-white/10 py-3 text-base font-semibold text-white disabled:opacity-30"
              :disabled="previewIndex <= 0"
              @click="stepPreview(-1)"
            >
              ←
            </button>
            <button
              type="button"
              class="min-h-[48px] flex-1 rounded-2xl bg-white/10 py-3 text-base font-semibold text-white disabled:opacity-30"
              :disabled="previewIndex >= displayRows.length - 1"
              @click="stepPreview(1)"
            >
              →
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </section>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import {
  CameraIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  MagnifyingGlassPlusIcon,
  PhotoIcon,
  TrashIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { uploadAttachment, deleteAttachment } from '../../../api/attachments'
import { compressImageFile } from '../../../util/imageCompress'
import { confirmAction } from '../../../composables/useConfirm'
import { showAppError, showAppErrorFromApi } from '../../../composables/appMessage'
import { fetchAttachmentBlob, resolveAttachmentAbsoluteUrl } from '../../../util/downloadPdfAttachment'

const props = defineProps({
  tripId: { type: Number, default: null },
  costId: { type: Number, required: true },
  /** @type {{ id?: number|null, url: string }[]} */
  attachments: { type: Array, default: () => [] },
  legacyReceiptUrl: { type: String, default: '' },
  readonly: { type: Boolean, default: false },
  canMutate: { type: Boolean, default: false },
  title: { type: String, required: true },
  emptyText: { type: String, required: true },
  hintMutate: { type: String, default: '' },
  addPhotos: { type: String, required: true },
  cameraCapture: { type: String, required: true },
  previewVerb: { type: String, required: true },
  previewAlt: { type: String, required: true },
  removeAria: { type: String, required: true },
  confirmRemoveTitle: { type: String, required: true },
  confirmRemoveMsg: { type: String, required: true },
  uploadFail: { type: String, required: true },
  retryLabel: { type: String, required: true },
  uploadingLabel: { type: String, required: true },
  deleteConfirmLabel: { type: String, required: true },
  deleteCancelLabel: { type: String, required: true },
  deleteFailMsg: { type: String, required: true },
  moveUp: { type: String, default: '' },
  moveDown: { type: String, default: '' },
  compactSection: { type: Boolean, default: false },
})

const emit = defineEmits(['updated'])

const multiRef = ref(null)
const previewOpen = ref(false)
const previewIndex = ref(0)
const previewZoom = ref(1)
const previewStageRef = ref(null)
const uploadingKeys = ref(new Set())
/** @type {import('vue').Ref<{ key: string, url: string, attachmentId: number|null, progress: number|null }[]>} */
const optimisticRows = ref([])
/** @type {import('vue').Ref<{ file: File, name: string }[]>} */
const failedJobs = ref([])
/** @type {import('vue').Ref<Map<number, string>>} */
const blobUrlByAttachmentId = ref(new Map())
const hydratingAttachmentIds = ref(new Set())

const STORAGE_ORDER_KEY = computed(() => `driver-cost-gallery-order:${props.costId}`)

function revokeAllBlobUrls() {
  for (const u of blobUrlByAttachmentId.value.values()) {
    URL.revokeObjectURL(u)
  }
  blobUrlByAttachmentId.value = new Map()
}

async function hydrateAttachmentBlob(attachmentId) {
  const id = Number(attachmentId)
  if (!Number.isFinite(id) || id < 1) return
  if (blobUrlByAttachmentId.value.has(id) || hydratingAttachmentIds.value.has(id)) return
  hydratingAttachmentIds.value.add(id)
  try {
    const blob = await fetchAttachmentBlob(id)
    const objectUrl = URL.createObjectURL(blob)
    const next = new Map(blobUrlByAttachmentId.value)
    next.set(id, objectUrl)
    blobUrlByAttachmentId.value = next
  } catch {
    /* fallback: resolveAttachmentAbsoluteUrl(row.url) */
  } finally {
    hydratingAttachmentIds.value.delete(id)
  }
}

function rowImageSrc(row) {
  if (!row) return ''
  if (row.progress != null || String(row.key).startsWith('tmp')) return row.url
  const id = row.attachmentId
  if (id != null) {
    const cached = blobUrlByAttachmentId.value.get(id)
    if (cached) return cached
    void hydrateAttachmentBlob(id)
  }
  return resolveAttachmentAbsoluteUrl(row.url)
}

function syncAttachmentBlobCache() {
  const ids = new Set(
    (props.attachments || []).map((a) => a?.id).filter((x) => x != null && Number(x) > 0),
  )
  const next = new Map()
  for (const [id, url] of blobUrlByAttachmentId.value) {
    if (ids.has(id)) next.set(id, url)
    else URL.revokeObjectURL(url)
  }
  blobUrlByAttachmentId.value = next
  for (const id of ids) {
    void hydrateAttachmentBlob(id)
  }
}

function normAttachmentList() {
  const rows = []
  const seen = new Set()
  for (const a of props.attachments || []) {
    const url = a?.url
    if (!url || seen.has(url)) continue
    seen.add(url)
    rows.push({
      key: `att-${a.id ?? url}`,
      url,
      attachmentId: a.id ?? null,
      progress: null,
    })
  }
  const leg = (props.legacyReceiptUrl || '').trim()
  if (leg && /^https?:/i.test(leg) && !seen.has(leg)) {
    rows.push({ key: 'legacy-receipt', url: leg, attachmentId: null, progress: null })
  }
  return mergeStoredOrder(rows)
}

function mergeStoredOrder(rows) {
  try {
    const raw = sessionStorage.getItem(STORAGE_ORDER_KEY.value)
    if (!raw) return rows
    const order = JSON.parse(raw)
    if (!Array.isArray(order)) return rows
    const map = new Map(rows.map((r) => [r.key, r]))
    const next = []
    for (const k of order) {
      const x = map.get(k)
      if (x) next.push(x)
    }
    for (const r of rows) {
      if (!next.includes(r)) next.push(r)
    }
    return next
  } catch {
    return rows
  }
}

function persistOrder(keys) {
  try {
    sessionStorage.setItem(STORAGE_ORDER_KEY.value, JSON.stringify(keys))
  } catch {
    /* ignore */
  }
}

const serverRows = ref(normAttachmentList())

watch(
  () => [props.attachments, props.legacyReceiptUrl],
  () => {
    serverRows.value = normAttachmentList()
    syncAttachmentBlobCache()
  },
  { deep: true, immediate: true },
)

onBeforeUnmount(revokeAllBlobUrls)

const displayRows = computed(() => {
  const opt = optimisticRows.value.filter((o) => uploadingKeys.value.has(o.key))
  const base = serverRows.value.map((r) => ({ ...r }))
  return [...base, ...opt]
})

const queueLen = computed(() => uploadingKeys.value.size)

const previewSrc = computed(() => rowImageSrc(displayRows.value[previewIndex.value]))

const canReorder = computed(() => props.canMutate && !props.readonly)

watch(previewOpen, (open) => {
  if (typeof document === 'undefined') return
  document.body.style.overflow = open ? 'hidden' : ''
})

function pct(p) {
  return `${Math.round((p || 0) * 100)}%`
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
  if (!files.length || !props.canMutate || props.readonly) return
  for (const file of files) {
    if (!file.type.startsWith('image/')) continue
    void pipeUpload(file)
  }
}

async function pipeUpload(file) {
  let prepared
  try {
    prepared = await compressImageFile(file)
  } catch {
    failedJobs.value.push({ file, name: file.name })
    showAppError(props.uploadFail)
    return
  }

  const key = `tmp-${Date.now()}-${Math.random().toString(36).slice(2)}`
  const url = URL.createObjectURL(prepared)
  optimisticRows.value.push({
    key,
    url,
    attachmentId: null,
    progress: 0,
  })
  uploadingKeys.value.add(key)
  failedJobs.value = failedJobs.value.filter((x) => x.name !== prepared.name)
  try {
    await uploadAttachment({
      attachable_type: 'trip_cost',
      attachable_id: props.costId,
      file: prepared,
      onProgress: (p) => {
        const row = optimisticRows.value.find((r) => r.key === key)
        if (row) row.progress = p
      },
    })
    URL.revokeObjectURL(url)
    optimisticRows.value = optimisticRows.value.filter((r) => r.key !== key)
    uploadingKeys.value.delete(key)
    emit('updated')
  } catch (err) {
    URL.revokeObjectURL(url)
    optimisticRows.value = optimisticRows.value.filter((r) => r.key !== key)
    uploadingKeys.value.delete(key)
    failedJobs.value.push({ file: prepared, name: prepared.name })
    showAppErrorFromApi(err, props.uploadFail)
  }
}

async function retryJob(job) {
  if (!job?.file) return
  failedJobs.value = failedJobs.value.filter((x) => x !== job)
  await pipeUpload(job.file)
}

function canRemoveRow(row) {
  if (!props.canMutate || props.readonly) return false
  return row.attachmentId != null
}

async function confirmRemove(row) {
  const ok = await confirmAction({
    title: props.confirmRemoveTitle,
    message: props.confirmRemoveMsg,
    danger: true,
    confirmLabel: props.deleteConfirmLabel,
    cancelLabel: props.deleteCancelLabel,
  })
  if (!ok || !row.attachmentId) return
  try {
    await deleteAttachment(row.attachmentId)
    emit('updated')
  } catch {
    showAppError(props.deleteFailMsg)
  }
}

function moveItem(idx, delta) {
  const row = displayRows.value[idx]
  if (!row || row.progress != null || String(row.key).startsWith('tmp')) return
  const list = serverRows.value.slice()
  const j = list.findIndex((r) => r.key === row.key)
  if (j < 0) return
  const nj = j + delta
  if (nj < 0 || nj >= list.length) return
  const t = list[j]
  list[j] = list[nj]
  list[nj] = t
  serverRows.value = list
  persistOrder(list.map((r) => r.key))
}

function openPreview(idx) {
  previewIndex.value = idx
  previewZoom.value = 1
  previewOpen.value = true
}

function closePreview() {
  previewOpen.value = false
}

function stepPreview(d) {
  previewIndex.value = Math.max(0, Math.min(displayRows.value.length - 1, previewIndex.value + d))
  previewZoom.value = 1
}

function zoomBy(dz) {
  previewZoom.value = Math.min(2.5, Math.max(1, previewZoom.value + dz))
}

function onWheelZoom(ev) {
  zoomBy(ev.deltaY > 0 ? -0.08 : 0.08)
}

function onImgErr(e) {
  const el = e?.target
  if (el) el.style.opacity = '0.35'
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.18s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
