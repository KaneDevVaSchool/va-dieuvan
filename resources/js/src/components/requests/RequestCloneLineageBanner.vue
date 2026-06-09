<template>
  <div
    v-if="summary"
    class="rounded-lg border border-indigo-200/80 bg-indigo-50/50 px-3 py-2.5 text-xs text-indigo-950"
  >
    <p class="font-semibold">{{ t('request_detail.clone_lineage_heading') }}</p>
    <p class="mt-1 text-indigo-900/90">
      {{ t('request_detail.clone_lineage_body', { id: summary.id }) }}
      <span v-if="summary.origin || summary.destination" class="block mt-0.5 text-indigo-800/80">
        {{ summary.origin || '—' }} → {{ summary.destination || '—' }}
      </span>
    </p>
    <RouterLink
      :to="detailTo"
      class="mt-2 inline-flex font-semibold text-indigo-800 underline decoration-indigo-400/60 hover:text-indigo-950"
    >
      {{ t('request_detail.clone_lineage_open_source') }}
    </RouterLink>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
const props = defineProps({
  summary: { type: Object, default: null },
  /** 'staff' | 'dept' */
  context: { type: String, default: 'staff' },
})

const { t } = useI18n()

const detailTo = computed(() => {
  if (!props.summary?.id) return '/requests'
  if (props.context === 'dept') {
    return { name: 'deptRequestDetail', params: { id: String(props.summary.id) } }
  }
  return { name: 'requestDetail', params: { id: String(props.summary.id) } }
})
</script>
