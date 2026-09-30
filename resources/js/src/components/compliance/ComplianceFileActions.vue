<template>
  <div>
    <span v-if="!attachments?.length" class="text-xs text-slate-400">{{ t('compliance.no_files') }}</span>
    <ul v-else class="flex flex-col gap-1.5">
      <li
        v-for="a in attachments"
        :key="a.id"
        class="flex min-w-0 items-center gap-2 rounded-lg border border-slate-200/90 bg-slate-50/80 px-2 py-1.5 text-xs dark:border-slate-600/80 dark:bg-slate-800/40"
      >
        <span class="min-w-0 flex-1 truncate text-slate-700 dark:text-slate-300" :title="a.original_name">
          {{ a.original_name || `#${a.id}` }}
        </span>
        <button
          type="button"
          class="shrink-0 font-medium text-teal-700 hover:underline dark:text-teal-400"
          data-testid="compliance-file-preview"
          @click.stop="previewAttachment = a"
        >
          {{ t('compliance.preview') }}
        </button>
        <button
          type="button"
          class="shrink-0 font-medium text-slate-600 hover:underline disabled:opacity-50 dark:text-slate-300"
          :disabled="downloadingId === a.id"
          data-testid="compliance-file-download"
          @click.stop="download(a)"
        >
          {{ downloadingId === a.id ? t('resources.loading') : t('compliance.download') }}
        </button>
      </li>
    </ul>

    <AttachmentPreviewModal
      :open="!!previewAttachment"
      :attachment="previewAttachment"
      @close="previewAttachment = null"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import AttachmentPreviewModal from '../requests/AttachmentPreviewModal.vue'
import { downloadBinaryAttachmentFromApi } from '../../util/downloadPdfAttachment'
import { showAppErrorFromApi } from '../../composables/appMessage'

/**
 * Xem trước / tải file chứng từ qua GET /attachments/{id}/download (đã xác thực, đọc từ DB).
 */
defineProps({
  attachments: { type: Array, default: () => [] },
})

const { t } = useI18n()

const previewAttachment = ref(null)
const downloadingId = ref(null)

async function download(a) {
  downloadingId.value = a.id
  try {
    await downloadBinaryAttachmentFromApi(a.id, a.original_name || `chung-tu-${a.id}`)
  } catch (e) {
    showAppErrorFromApi(e, t('compliance.download_error'))
  } finally {
    downloadingId.value = null
  }
}
</script>
