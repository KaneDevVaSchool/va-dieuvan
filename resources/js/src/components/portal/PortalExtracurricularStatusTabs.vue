<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  modelValue: { type: String, default: 'all' },
})

const emit = defineEmits(['update:modelValue'])

const { t } = useI18n()

const tabs = [
  { value: 'all', labelKey: 'portal.shell.ec_tab_all' },
  { value: 'draft', labelKey: 'portal.shell.ec_tab_draft' },
  { value: 'pending', labelKey: 'portal.shell.ec_tab_pending' },
  { value: 'processing', labelKey: 'portal.shell.ec_tab_active' },
  { value: 'completed', labelKey: 'portal.shell.ec_tab_done' },
]
</script>

<template>
  <div
    class="sticky top-12 z-30 -mx-4 border-b border-slate-200/80 bg-slate-50/95 px-4 backdrop-blur-sm sm:-mx-6 sm:px-6"
    role="tablist"
    :aria-label="t('portal.extracurricular_module.list_heading')"
    data-testid="portal-ec-status-tabs"
  >
    <div class="flex gap-1 overflow-x-auto py-2 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        type="button"
        role="tab"
        class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold transition sm:text-sm"
        :class="
          modelValue === tab.value
            ? 'bg-white text-va-800 shadow-sm ring-1 ring-slate-200/80'
            : 'text-slate-600 hover:bg-white/60 hover:text-slate-900'
        "
        :aria-selected="modelValue === tab.value"
        :data-testid="`portal-ec-tab-${tab.value}`"
        @click="emit('update:modelValue', tab.value)"
      >
        {{ t(tab.labelKey) }}
      </button>
    </div>
  </div>
</template>
