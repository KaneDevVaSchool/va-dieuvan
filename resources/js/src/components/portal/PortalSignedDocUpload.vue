<template>
  <section
    class="w-full overflow-hidden rounded-2xl border border-emerald-200/90 bg-white p-6 shadow-sm ring-1 ring-emerald-600/10 sm:p-8"
  >
    <h2 class="text-lg font-bold text-slate-900 sm:text-xl">{{ t('portal.signed_upload_title') }}</h2>
    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-600 sm:text-base">{{ t('portal.signed_upload_lead') }}</p>
    <ul v-if="attachments.length" class="mt-5 space-y-2">
      <li
        v-for="a in attachments"
        :key="a.id"
        class="flex flex-col gap-2 rounded-xl border border-slate-200/90 bg-slate-50/80 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4"
      >
        <span class="min-w-0 text-sm font-semibold text-slate-800 sm:text-base">{{ a.original_name || `File #${a.id}` }}</span>
        <button
          type="button"
          class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-xl border border-teal-200 bg-white px-4 text-sm font-semibold text-teal-800 shadow-sm transition hover:bg-teal-50 sm:min-h-[40px]"
          @click="$emit('download', a)"
        >
          {{ t('portal.signed_download') }}
        </button>
      </li>
    </ul>
    <div class="mt-5">
      <FileUpload
        :key="uploadComponentKey"
        :label="t('portal.signed_add')"
        :hint="t('portal.signed_hints')"
        drag-drop
        :upload-fn="uploadFn"
        @uploaded="$emit('uploaded')"
      />
    </div>
    <p v-if="error" class="mt-3 text-sm font-medium text-rose-600 sm:text-base">{{ error }}</p>
  </section>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import FileUpload from '../ui/FileUpload.vue'

defineProps({
  attachments: { type: Array, default: () => [] },
  uploadComponentKey: { type: String, default: 'signed' },
  uploadFn: { type: Function, required: true },
  error: { type: String, default: '' },
})

defineEmits(['download', 'uploaded'])

const { t } = useI18n()
</script>
