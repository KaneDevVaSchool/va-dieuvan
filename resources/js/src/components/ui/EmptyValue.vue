<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { isEmptyDisplay } from '../../util/displayValue'

const props = withDefaults(
  defineProps<{
    value?: string | number | null
    emptyKey?: string
  }>(),
  {
    emptyKey: 'trip_detail.empty.not_available',
  },
)

const { t } = useI18n()

const isEmpty = computed(() => isEmptyDisplay(props.value))
const text = computed(() =>
  isEmpty.value ? t(props.emptyKey) : String(props.value).trim(),
)
</script>

<template>
  <span
    :class="
      isEmpty
        ? 'font-normal italic text-slate-400 dark:text-slate-500'
        : ''
    "
  >
    {{ text }}
  </span>
</template>
