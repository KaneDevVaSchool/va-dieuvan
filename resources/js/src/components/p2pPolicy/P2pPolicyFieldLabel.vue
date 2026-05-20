<script setup>
import { InformationCircleIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'

defineProps({
  label: { type: String, required: true },
  hint: { type: String, default: '' },
  required: { type: Boolean, default: false },
})
const { t } = useI18n()
</script>

<template>
  <div class="flex flex-wrap items-center gap-2 text-base font-semibold text-slate-800 dark:text-slate-200">
    <span>{{ label }}</span>
    <span
      v-if="required"
      class="cursor-help text-rose-600 dark:text-rose-400"
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
      <InformationCircleIcon class="h-5 w-5" aria-hidden="true" />
    </span>
  </div>
</template>
