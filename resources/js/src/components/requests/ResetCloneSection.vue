<template>
  <div>
    <h2 class="text-base font-semibold text-slate-900">{{ t('request_detail.reset_clone_section_title') }}</h2>
    <div class="mt-3">
      <button
        type="button"
        class="inline-flex h-10 items-center gap-2 rounded-lg border border-teal-200 bg-teal-50 px-4 text-sm font-semibold text-teal-900 shadow-sm transition hover:bg-teal-100 disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="busy"
        @click="$emit('clone')"
      >
        <span
          v-if="busy"
          class="h-4 w-4 animate-spin rounded-full border-2 border-teal-600/30 border-t-teal-700"
        />
        {{ busy ? t('request_detail.reset_clone_busy') : t('request_detail.reset_clone') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  busy: { type: Boolean, default: false },
})

defineEmits(['clone'])

const { t } = useI18n()
</script>
