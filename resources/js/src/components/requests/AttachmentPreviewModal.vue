<template>
  <Teleport to="body">
    <div
      v-if="open && attachment"
      class="fixed inset-0 z-[110] flex items-end justify-center bg-black/55 p-2 sm:items-center sm:p-4"
      role="dialog"
      aria-modal="true"
      :aria-label="t('request_detail.preview_modal_title')"
      @click.self="$emit('close')"
    >
      <div
        class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl"
        @click.stop
      >
        <header class="flex shrink-0 items-center gap-2 border-b border-slate-100 px-3 py-2.5">
          <p class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-900">
            {{ attachment.original_name || t('request_detail.file_fallback_name', { id: attachment.id }) }}
          </p>
          <button
            type="button"
            class="rounded-lg px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100"
            @click="$emit('close')"
          >
            {{ t('request_detail.preview_modal_close') }}
          </button>
        </header>
        <div class="relative min-h-[200px] flex-1 bg-slate-100 p-2">
          <div
            v-if="loading"
            class="flex min-h-[min(50vh,400px)] items-center justify-center text-sm text-slate-600"
          >
            <span
              class="mr-2 h-6 w-6 animate-spin rounded-full border-2 border-teal-500/30 border-t-teal-700"
              aria-hidden="true"
            />
            {{ t('request_detail.preview_modal_loading') }}
          </div>
          <p v-else-if="error" class="px-3 py-8 text-center text-sm text-rose-700">{{ error }}</p>
          <iframe
            v-else-if="isPdf && blobUrl"
            :src="blobUrl"
            class="block h-[min(70vh,720px)] w-full rounded-lg border border-slate-200 bg-white"
            :title="t('request_detail.preview_modal_iframe_title')"
          />
          <img
            v-else-if="isImage && blobUrl"
            :src="blobUrl"
            alt=""
            class="mx-auto max-h-[min(70vh,720px)] max-w-full rounded-lg border border-slate-200 bg-white object-contain"
          />
          <p v-else class="px-3 py-8 text-center text-sm text-slate-600">
            {{ t('request_detail.preview_modal_unsupported') }}
          </p>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { http } from '../../api/http'
import { normalizeAxiosBlobError } from '../../util/downloadPdfAttachment'

const props = defineProps({
  open: { type: Boolean, default: false },
  attachment: { type: Object, default: null },
  /** @param {number} attachmentId */
  downloadPath: { type: Function, default: null },
})

defineEmits(['close'])

const { t } = useI18n()

const loading = ref(false)
const error = ref('')
const blobUrl = ref('')

const mime = computed(() => String(props.attachment?.mime_type || '').toLowerCase())
const isPdf = computed(() => mime.value.includes('pdf') || /\.pdf$/i.test(props.attachment?.original_name || ''))
const isImage = computed(() => mime.value.startsWith('image/'))

function revokeUrl() {
  if (blobUrl.value) {
    URL.revokeObjectURL(blobUrl.value)
    blobUrl.value = ''
  }
}

async function loadPreview() {
  revokeUrl()
  error.value = ''
  const id = props.attachment?.id
  if (!id) return
  loading.value = true
  try {
    const path = props.downloadPath
      ? props.downloadPath(id)
      : `/attachments/${id}/download`
    const res = await http.get(path, {
      responseType: 'blob',
      headers: { Accept: '*/*' },
    })
    const blob = res.data
    if (!(blob instanceof Blob)) {
      error.value = t('request_detail.preview_modal_unsupported')
      return
    }
    blobUrl.value = URL.createObjectURL(blob)
  } catch (e) {
    await normalizeAxiosBlobError(e)
    error.value = e?.response?.data?.message ?? t('request_detail.preview_modal_error')
  } finally {
    loading.value = false
  }
}

watch(
  () => [props.open, props.attachment?.id],
  ([open]) => {
    if (open && props.attachment?.id) {
      loadPreview()
    } else {
      revokeUrl()
      error.value = ''
    }
  },
)

onBeforeUnmount(revokeUrl)
</script>
