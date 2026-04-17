<template>
  <div
    class="rounded-xl border border-dashed transition-all duration-150"
    :class="rootSurfaceClass"
    @dragover.prevent="onDragOver"
    @dragleave.prevent="onDragLeave"
    @drop.prevent="onDrop"
  >
    <div :class="compact ? 'flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between sm:gap-4' : ''">
      <div class="min-w-0 flex-1">
        <div :class="compact ? 'text-xs font-semibold text-slate-800' : 'text-sm font-semibold text-slate-800'">
          {{ label }}
        </div>
        <p v-if="hint" class="mt-1 text-[11px] leading-snug text-slate-500 sm:text-xs">{{ hint }}</p>
        <p v-else-if="dragDrop" class="mt-1 text-[11px] leading-snug text-slate-400 sm:text-xs">
          Kéo thả vào khung hoặc chọn tệp · tối đa 10&nbsp;MB
        </p>
      </div>

      <div class="flex flex-col gap-2 sm:shrink-0 sm:flex-row sm:items-center sm:gap-2">
        <label
          class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-slate-200/90 bg-white px-3 py-1.5 text-center text-xs font-semibold text-slate-700 shadow-sm transition hover:border-teal-400 hover:bg-teal-50/50 hover:text-teal-900"
          :class="compact ? '' : 'sm:px-4 sm:py-2 sm:text-sm'"
        >
          Chọn file
          <input type="file" class="hidden" :accept="accept" @change="onPick" />
        </label>
        <span v-if="fileName" class="max-w-full truncate text-[11px] text-slate-600 sm:max-w-[12rem] sm:text-xs" :title="fileName">
          {{ fileName }}
        </span>
      </div>
    </div>

    <div v-if="previewUrl" class="mt-3">
      <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Xem trước</div>
      <img v-if="isImage" :src="previewUrl" alt="preview" class="mt-1.5 max-h-36 rounded-lg border border-slate-200 object-contain sm:max-h-40" />
      <div v-else class="mt-1.5 rounded-lg border border-slate-200 bg-white px-2 py-2 text-xs text-slate-600">
        Không phải ảnh — không xem trước.
      </div>
    </div>

    <div v-if="progress > 0 && progress < 1" class="mt-3">
      <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200/90">
        <div class="h-full rounded-full bg-teal-500 transition-all" :style="{ width: `${Math.round(progress * 100)}%` }" />
      </div>
      <div class="mt-1 text-[11px] tabular-nums text-slate-500">{{ Math.round(progress * 100) }}%</div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2">
      <Button
        :disabled="!file"
        :loading="uploading"
        class="!bg-teal-600 !text-white hover:!bg-teal-700 disabled:!bg-slate-300"
        :class="compact ? '!px-3 !py-1.5 !text-xs' : ''"
        @click="doUpload"
      >
        Tải lên
      </Button>
      <button
        v-if="file"
        type="button"
        class="text-xs font-medium text-slate-500 hover:text-slate-800"
        @click="clear"
      >
        Bỏ chọn
      </button>
    </div>

    <div v-if="error" class="mt-2 rounded-md bg-rose-50 px-2 py-1.5 text-xs text-rose-700">{{ error }}</div>
    <div v-if="resultUrl" class="mt-2 truncate text-[11px] text-emerald-700" :title="resultUrl">
      <span class="font-medium">Đã tải:</span>
      <a :href="resultUrl" target="_blank" rel="noopener" class="ml-1 font-medium underline decoration-emerald-600/40 underline-offset-2 hover:text-emerald-900">
        Mở liên kết
      </a>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import Button from './Button.vue'

const props = defineProps({
  label: { type: String, default: 'Upload file' },
  hint: { type: String, default: '' },
  accept: { type: String, default: 'image/*,.pdf,.doc,.docx' },
  uploadFn: { type: Function, required: true },
  /** Bật kéo-thả file vào vùng viền nét đứt */
  dragDrop: { type: Boolean, default: false },
  /** Padding và chữ nhỏ hơn — dùng trong thẻ chi tiết */
  compact: { type: Boolean, default: false },
})

const emit = defineEmits(['uploaded'])

const file = ref(null)
const fileName = ref('')
const previewUrl = ref('')
const progress = ref(0)
const uploading = ref(false)
const error = ref('')
const resultUrl = ref('')
const dragOver = ref(false)

const rootSurfaceClass = computed(() => {
  const pad = props.compact ? 'p-3' : 'p-4'
  if (props.dragDrop) {
    return [
      pad,
      'border-teal-200/90 bg-gradient-to-b from-teal-50/40 to-white',
      dragOver.value
        ? 'border-teal-500 bg-teal-50/70 shadow-sm ring-2 ring-teal-500/20'
        : 'hover:border-teal-300/80',
    ]
  }
  return [pad, 'border-slate-300/80 bg-slate-50/40', dragOver.value ? 'border-teal-500 bg-teal-50/50' : '']
})

const isImage = computed(() => {
  const f = file.value
  return f && f.type.startsWith('image/')
})

function revoke() {
  if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(previewUrl.value)
  }
}

watch(file, (f) => {
  revoke()
  previewUrl.value = f && f.type.startsWith('image/') ? URL.createObjectURL(f) : ''
})

function setFileFromList(list) {
  error.value = ''
  resultUrl.value = ''
  const f = list?.[0]
  file.value = f || null
  fileName.value = f?.name ?? ''
}

function onPick(e) {
  setFileFromList(e.target.files)
  e.target.value = ''
}

function onDragOver(e) {
  if (!props.dragDrop) return
  e.preventDefault()
  dragOver.value = true
}

function onDragLeave(e) {
  if (!props.dragDrop) return
  e.preventDefault()
  dragOver.value = false
}

function onDrop(e) {
  if (!props.dragDrop) return
  dragOver.value = false
  const files = e.dataTransfer?.files
  if (files?.length) setFileFromList(files)
}

function clear() {
  revoke()
  file.value = null
  fileName.value = ''
  previewUrl.value = ''
  progress.value = 0
  error.value = ''
  resultUrl.value = ''
}

async function doUpload() {
  if (!file.value) return
  error.value = ''
  resultUrl.value = ''
  uploading.value = true
  progress.value = 0
  try {
    const res = await props.uploadFn(file.value, (p) => {
      progress.value = p
    })
    resultUrl.value = res?.url ?? res?.path ?? ''
    progress.value = 1
    emit('uploaded', res)
  } catch (e) {
    error.value = e?.response?.data?.message ?? 'Upload thất bại.'
  } finally {
    uploading.value = false
  }
}
</script>
