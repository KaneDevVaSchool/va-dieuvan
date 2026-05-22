<template>
  <div>
    <header
      v-if="compact"
      class="flex items-center gap-2 border-b border-slate-100 bg-gradient-to-r from-amber-50/80 via-white to-white px-3 py-2"
    >
      <ArrowPathIcon class="h-4 w-4 shrink-0 text-amber-700" aria-hidden="true" />
      <h2 class="text-[10px] font-bold uppercase tracking-wide text-slate-600">
        {{ t('request_detail.reset_clone_section_title') }}
      </h2>
    </header>
    <div :class="compact ? 'p-3' : ''">
      <h2 v-if="!compact" class="text-base font-semibold text-slate-900">
        {{ t('request_detail.reset_clone_section_title') }}
      </h2>
      <p v-if="compact" class="mb-3 text-xs leading-relaxed text-slate-600">
        {{ t('request_detail.reset_clone_section_hint') }}
      </p>
      <button
        type="button"
        class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-teal-200 bg-teal-50 px-4 text-sm font-semibold text-teal-900 shadow-sm transition hover:bg-teal-100 disabled:cursor-not-allowed disabled:opacity-50"
        :class="compact ? 'h-10' : 'mt-3 h-10'"
        :disabled="busy"
        @click="$emit('clone')"
      >
        <span
          v-if="busy"
          class="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-teal-600/30 border-t-teal-700"
        />
        {{ busy ? t('request_detail.reset_clone_busy') : t('request_detail.reset_clone') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { ArrowPathIcon } from '@heroicons/vue/24/outline'

defineProps({
  busy: { type: Boolean, default: false },
  compact: { type: Boolean, default: false },
})

defineEmits(['clone'])

const { t } = useI18n()
</script>
