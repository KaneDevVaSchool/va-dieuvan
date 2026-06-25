<template>
  <div class="flex shrink-0 flex-nowrap items-center gap-0.5" role="group" :aria-label="t('request_detail.docs_actions_aria')">
    <button
      v-if="canPreview"
      type="button"
      class="flex h-7 w-7 items-center justify-center rounded-md text-slate-600 hover:bg-slate-100"
      :title="t('request_detail.preview_action')"
      data-testid="attachment-preview-btn"
      @click="$emit('preview')"
    >
      <EyeIcon class="h-3.5 w-3.5" aria-hidden="true" />
    </button>
    <button
      type="button"
      class="flex h-7 items-center justify-center rounded-md text-teal-700 hover:bg-teal-50"
      :class="downloadEmphasis ? 'gap-1 bg-teal-600 px-2 text-[10px] font-semibold text-white hover:bg-teal-700' : 'w-7'"
      :title="t('request_detail.download_action')"
      @click="$emit('download')"
    >
      <ArrowDownTrayIcon class="h-3.5 w-3.5" aria-hidden="true" />
      <span v-if="downloadEmphasis" class="hidden sm:inline">{{ t('request_detail.download_action') }}</span>
    </button>
    <button
      v-if="canDelete"
      type="button"
      class="flex h-7 w-7 items-center justify-center rounded-md text-rose-600 hover:bg-rose-50 disabled:opacity-50"
      :disabled="deleting"
      :title="t('request_detail.delete_action')"
      @click="$emit('delete')"
    >
      <TrashIcon class="h-3.5 w-3.5" aria-hidden="true" />
    </button>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { ArrowDownTrayIcon, EyeIcon, TrashIcon } from '@heroicons/vue/24/outline'

defineProps({
  canPreview: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
  deleting: { type: Boolean, default: false },
  downloadEmphasis: { type: Boolean, default: false },
})

defineEmits(['preview', 'download', 'delete'])

const { t } = useI18n()
</script>
