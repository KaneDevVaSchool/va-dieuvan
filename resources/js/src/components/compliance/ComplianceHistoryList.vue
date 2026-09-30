<template>
  <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50/60 p-3 dark:border-slate-600 dark:bg-slate-800/30">
    <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
      {{ t('compliance.history_title') }}
    </p>
    <ul class="space-y-2">
      <li
        v-for="h in history"
        :key="h.id"
        class="grid gap-2 rounded-lg bg-white p-2 text-xs dark:bg-slate-900/60 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]"
      >
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-300">
              {{ t('compliance.superseded') }}
            </span>
            <span class="truncate text-slate-700 dark:text-slate-300">{{ h.title || '—' }}</span>
          </div>
          <div class="mt-1 text-slate-500 dark:text-slate-400">
            <span v-if="h.document_no">{{ h.document_no }} · </span>
            {{ formatIsoDate(h.issued_at, locale) }} → {{ formatIsoDate(h.expires_at, locale) }}
          </div>
        </div>
        <ComplianceFileActions :attachments="h.attachments" />
      </li>
    </ul>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { formatIsoDate } from '../../util/datetime'
import ComplianceFileActions from './ComplianceFileActions.vue'

/** Các bản chứng từ đã được gia hạn thay thế (mới → cũ). */
defineProps({
  history: { type: Array, default: () => [] },
})

const { t, locale } = useI18n()
</script>
