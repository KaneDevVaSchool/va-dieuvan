<template>
  <section
    class="overflow-hidden rounded-2xl border border-emerald-200/90 bg-white p-5 shadow-sm ring-1 ring-emerald-600/10"
  >
    <h2 class="text-base font-semibold text-slate-900">{{ t('request_detail.signed_upload_title') }}</h2>
    <p class="mt-1 text-sm text-slate-600">{{ t('request_detail.signed_upload_lead') }}</p>
    <ul v-if="attachments.length" class="mt-3 space-y-1.5">
      <li
        v-for="a in attachments"
        :key="a.id"
        class="flex items-center justify-between gap-2 rounded-lg border border-slate-100 bg-slate-50/80 px-2.5 py-2 text-sm"
      >
        <span class="min-w-0 truncate font-medium text-slate-800">{{ a.original_name || `File #${a.id}` }}</span>
        <button type="button" class="shrink-0 text-xs font-semibold text-teal-700 hover:underline" @click="$emit('download', a)">
          Tải
        </button>
      </li>
    </ul>
    <div class="mt-3">
      <FileUpload
        :key="uploadComponentKey"
        label="Thêm file đã ký"
        hint="PDF, JPG, PNG"
        drag-drop
        compact
        :upload-fn="uploadFn"
        @uploaded="$emit('uploaded')"
      />
    </div>
    <p v-if="error" class="mt-2 text-xs font-medium text-rose-600">{{ error }}</p>
  </section>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import FileUpload from '../ui/FileUpload.vue'

defineProps({
  attachments: { type: Array, default: () => [] },
  /** Key để reset FileUpload khi danh sách đổi */
  uploadComponentKey: { type: String, default: 'signed' },
  uploadFn: { type: Function, required: true },
  error: { type: String, default: '' },
})

defineEmits(['download', 'uploaded'])

const { t } = useI18n()
</script>
