<template>
  <div
    v-if="todos.length"
    class="rounded-lg border border-amber-200/80 bg-amber-50/50 px-3 py-2.5"
    role="region"
    :aria-label="t('request_detail.workflow_todos_aria')"
  >
    <p class="text-[10px] font-bold uppercase tracking-wide text-amber-900/90">
      {{ t('request_detail.workflow_todos_heading') }}
    </p>
    <ul class="mt-2 flex flex-col gap-1.5 sm:flex-row sm:flex-wrap">
      <li v-for="item in todos" :key="item.key">
        <button
          type="button"
          class="inline-flex w-full items-center justify-between gap-2 rounded-lg border border-amber-200/90 bg-white px-2.5 py-1.5 text-left text-xs font-semibold text-amber-950 shadow-sm transition hover:bg-amber-50/80 sm:w-auto"
          @click="$emit('navigate', { tab: item.tab, focus: item.focus })"
        >
          <span>{{ item.label }}</span>
          <span class="shrink-0 text-[10px] font-bold uppercase text-teal-700">{{ t('request_detail.workflow_go') }}</span>
        </button>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  todos: { type: Array, default: () => [] },
})

defineEmits(['navigate'])

const { t } = useI18n()
</script>
