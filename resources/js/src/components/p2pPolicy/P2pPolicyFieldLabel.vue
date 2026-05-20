<script setup>
import { InformationCircleIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'

defineProps({
  label: { type: String, required: true },
  hint: { type: String, default: '' },
  required: { type: Boolean, default: false },
  compact: { type: Boolean, default: false },
})
const { t } = useI18n()
</script>

<template>
  <div
    class="flex flex-wrap items-center gap-1.5"
    :class="compact
      ? 'text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400'
      : 'text-base font-semibold text-slate-800 dark:text-slate-200'"
  >
    <span :class="compact ? 'normal-case tracking-normal text-slate-700 dark:text-slate-300' : ''">{{ label }}</span>
    <span
      v-if="required"
      class="text-rose-500 dark:text-rose-400"
      :class="compact ? 'text-xs font-bold' : 'cursor-help'"
      :title="t('p2p_policy_page.tip_required_star')"
      :aria-label="t('p2p_policy_page.tip_required_star')"
    >
      *
    </span>
    <span
      v-if="hint"
      class="inline-flex cursor-help align-middle text-slate-400 hover:text-teal-600 dark:hover:text-teal-400"
      :title="hint"
      tabindex="0"
      role="img"
      :aria-label="hint"
    >
      <InformationCircleIcon class="h-4 w-4" aria-hidden="true" />
    </span>
  </div>
</template>
