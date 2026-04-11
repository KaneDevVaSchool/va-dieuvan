<template>
  <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/50 p-4">
    <div class="text-sm font-medium text-slate-800">{{ label }}</div>
    <p v-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</p>

    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
      <label
        class="inline-flex cursor-pointer items-center justify-center rounded-md border bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50"
      >
        Chọn file
        <input type="file" class="hidden" :accept="accept" @change="onPick" />
      </label>
      <span v-if="fileName" class="truncate text-xs text-slate-600">{{ fileName }}</span>
    </div>

    <div v-if="previewUrl" class="mt-3">
      <div class="text-xs font-medium text-slate-500">Xem trước</div>
      <img v-if="isImage" :src="previewUrl" alt="preview" class="mt-2 max-h-48 rounded-lg border object-contain" />
      <div v-else class="mt-2 rounded-lg border bg-white p-3 text-sm text-slate-600">Không phải ảnh — không preview.</div>
    </div>

    <div v-if="progress > 0 && progress < 1" class="mt-3">
      <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
        <div class="h-full bg-slate-900 transition-all" :style="{ width: `${Math.round(progress * 100)}%` }" />
      </div>
      <div class="mt-1 text-xs text-slate-500">{{ Math.round(progress * 100) }}%</div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2">
      <Button :disabled="!file" :loading="uploading" @click="doUpload">Tải lên</Button>
      <button v-if="file" type="button" class="text-sm text-slate-500 underline" @click="clear">Xóa</button>
    </div>

    <div v-if="error" class="mt-2 text-sm text-rose-600">{{ error }}</div>
    <div v-if="resultUrl" class="mt-2 break-all text-xs text-emerald-700">
      Đã upload:
      <a :href="resultUrl" target="_blank" rel="noopener" class="underline">{{ resultUrl }}</a>
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
})

const emit = defineEmits(['uploaded'])

const file = ref(null)
const fileName = ref('')
const previewUrl = ref('')
const progress = ref(0)
const uploading = ref(false)
const error = ref('')
const resultUrl = ref('')

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

function onPick(e) {
  error.value = ''
  resultUrl.value = ''
  const f = e.target.files?.[0]
  file.value = f || null
  fileName.value = f?.name ?? ''
  e.target.value = ''
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
