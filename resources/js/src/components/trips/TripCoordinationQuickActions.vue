<template>
  <div class="flex flex-wrap gap-2 border-b border-slate-100 pb-3 dark:border-slate-700">
    <button
      v-if="canDuplicate"
      type="button"
      class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
      :disabled="duplicating"
      @click="$emit('duplicate')"
    >
      <DocumentDuplicateIcon class="h-4 w-4" />
      {{ t('trip_detail.quick.duplicate') }}
    </button>
    <button
      v-if="canExportPdf"
      type="button"
      class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
      :disabled="pdfLoading"
      @click="$emit('export-pdf')"
    >
      <PrinterIcon class="h-4 w-4" />
      {{ t('trip_detail.quick.print') }}
    </button>
    <button
      type="button"
      class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
      @click="$emit('copy-link')"
    >
      <LinkIcon class="h-4 w-4" />
      {{ t('trip_detail.quick.copy_link') }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { DocumentDuplicateIcon, LinkIcon, PrinterIcon } from '@heroicons/vue/24/outline'

defineProps<{
  canDuplicate?: boolean
  canExportPdf?: boolean
  duplicating?: boolean
  pdfLoading?: boolean
}>()

defineEmits<{
  duplicate: []
  'export-pdf': []
  'copy-link': []
}>()

const { t } = useI18n()
</script>
