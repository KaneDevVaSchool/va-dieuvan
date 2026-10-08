<template>
  <button
    type="button"
    class="group relative flex shrink-0 items-center justify-center overflow-hidden rounded-lg ring-1 transition active:scale-[0.97]"
    :class="[sizeClass, dark ? 'bg-white/5 ring-white/10' : 'bg-slate-50 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700']"
    :title="attachment.original_name || ''"
    @click="open"
  >
    <img v-if="objectUrl && isImage" :src="objectUrl" :alt="attachment.original_name || ''" class="h-full w-full object-cover" />
    <DocumentIcon v-else class="h-6 w-6" :class="dark ? 'text-driver-muted' : 'text-slate-400'" />
    <span v-if="loading" class="absolute inset-0 animate-pulse bg-black/10" aria-hidden="true" />
    <span
      v-if="removable"
      role="button"
      tabindex="0"
      class="absolute right-0.5 top-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-black/60 text-white"
      :aria-label="removeLabel"
      @click.stop="$emit('remove', attachment)"
      @keydown.enter.stop="$emit('remove', attachment)"
    >
      <XMarkIcon class="h-4 w-4" />
    </span>
  </button>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { DocumentIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { fetchAttachmentBlob } from '../../util/downloadPdfAttachment'

/**
 * Ảnh / file đính kèm tải qua Sanctum (GET /attachments/{id}/download) — không phụ thuộc URL /storage công khai.
 */
const props = defineProps({
  attachment: { type: Object, required: true },
  size: { type: String, default: 'md' },
  dark: { type: Boolean, default: false },
  removable: { type: Boolean, default: false },
  removeLabel: { type: String, default: 'Xóa' },
})
defineEmits(['remove'])

const objectUrl = ref('')
const loading = ref(false)

const isImage = computed(() => String(props.attachment?.mime_type || '').startsWith('image/'))
const sizeClass = computed(() => (props.size === 'sm' ? 'h-12 w-12' : props.size === 'lg' ? 'h-24 w-24' : 'h-16 w-16'))

async function ensureBlob() {
  if (objectUrl.value || !props.attachment?.id) return objectUrl.value
  loading.value = true
  try {
    const blob = await fetchAttachmentBlob(props.attachment.id)
    objectUrl.value = URL.createObjectURL(blob)
  } catch {
    objectUrl.value = ''
  } finally {
    loading.value = false
  }
  return objectUrl.value
}

async function open() {
  const url = await ensureBlob()
  if (url) window.open(url, '_blank', 'noopener')
}

onMounted(() => {
  if (isImage.value) void ensureBlob()
})

onBeforeUnmount(() => {
  if (objectUrl.value) URL.revokeObjectURL(objectUrl.value)
})
</script>
