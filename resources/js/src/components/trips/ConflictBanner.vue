<template>
  <div
    v-if="conflict"
    class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm dark:border-amber-700 dark:bg-amber-950/40"
  >
    <div class="font-medium text-amber-800 dark:text-amber-200">
      ⚠ {{ t('trip_detail.coordination.conflict_title', { code: conflict.trip_code }) }}
    </div>
    <div class="mt-1 text-xs text-amber-700 dark:text-amber-300">
      {{ conflict.time_range }} · {{ conflict.requester }}
    </div>
    <div class="mt-2 flex flex-wrap gap-2">
      <button
        type="button"
        class="rounded border border-amber-400 bg-white px-3 py-1 text-xs font-medium text-amber-900 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100 dark:hover:bg-amber-900"
        @click="$emit('pick-again')"
      >
        {{ t('trip_detail.coordination.conflict_pick_again') }}
      </button>
      <button
        type="button"
        class="rounded bg-amber-600 px-3 py-1 text-xs font-medium text-white hover:bg-amber-700"
        @click="$emit('keep-anyway')"
      >
        {{ t('trip_detail.coordination.conflict_keep') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

export type VehicleConflict = {
  trip_code: string
  time_range: string
  requester: string
}

defineProps<{
  conflict: VehicleConflict | null
}>()

defineEmits<{
  'pick-again': []
  'keep-anyway': []
}>()

const { t } = useI18n()
</script>
