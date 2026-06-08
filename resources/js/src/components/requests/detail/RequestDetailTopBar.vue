<template>
  <header
    class="bg-white"
    :class="embedded ? '' : 'sticky top-0 z-30 border-b border-slate-200'"
  >
    <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 px-4 py-3">
      <div class="flex min-w-0 flex-1 items-center gap-3">
        <RouterLink
          :to="backTo"
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:bg-slate-50"
          :aria-label="backAriaLabel"
        >
          <ArrowLeftIcon class="h-4 w-4" />
        </RouterLink>
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <h1 class="truncate text-base font-bold text-slate-900 sm:text-lg">{{ title }}</h1>
            <StatusBadge v-if="status" :status="status" />
            <span
              v-if="recurring"
              class="inline-flex items-center gap-1 rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-900"
            >
              <ArrowPathIcon class="h-3 w-3" aria-hidden="true" />
              {{ t('request_detail.badge_recurring') }}
            </span>
          </div>
          <p class="mt-0.5 text-xs text-slate-600">
            <slot name="meta" />
          </p>
        </div>
      </div>
      <div class="flex w-full shrink-0 flex-wrap items-center justify-end gap-2 sm:w-auto">
        <slot name="actions" />
      </div>
    </div>
    <p
      v-if="inlineAlert"
      class="border-t border-amber-100 bg-amber-50 px-4 py-2 text-xs font-medium text-amber-950 sm:text-sm"
    >
      {{ inlineAlert }}
    </p>
  </header>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowLeftIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'
import StatusBadge from '../../ui/StatusBadge.vue'

defineProps({
  backTo: { type: [String, Object], required: true },
  backAriaLabel: { type: String, required: true },
  title: { type: String, required: true },
  status: { type: String, default: '' },
  recurring: { type: Boolean, default: false },
  inlineAlert: { type: String, default: '' },
  /** Nằm trong khối sticky chung (không tự sticky riêng). */
  embedded: { type: Boolean, default: false },
})

const { t } = useI18n()
</script>
