<template>
  <div
    class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50/90 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
  >
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
      <span class="text-sm text-slate-600">
        {{ t('dispatch_wizard.s3.toolbar_schedules', { n: itemCount }) }}
      </span>
      <span v-if="total > 0" class="text-sm font-semibold text-slate-800">
        {{ t('dispatch_wizard.s3.toolbar_estimated') }}
        <span class="text-va-800">{{ formattedTotal }}</span>
      </span>
    </div>
    <button
      type="button"
      class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
      @click="$emit('add')"
    >
      <span class="text-base leading-none">+</span>
      {{ t('dispatch_wizard.s3.toolbar_add') }}
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  total: { type: Number, default: 0 },
  itemCount: { type: Number, default: 0 },
  localeTag: { type: String, default: 'vi-VN' },
})

defineEmits(['add'])

const { t } = useI18n()

const formattedTotal = computed(() => {
  try {
    return `${props.total.toLocaleString(props.localeTag)} ₫`
  } catch {
    return `${props.total} ₫`
  }
})
</script>
