<template>
  <div
    v-if="conflict"
    class="rounded-[12px] border-[0.5px] border-[#EF9F27]/50 bg-[#EF9F27]/12 p-3 text-sm dark:border-amber-800/50 dark:bg-amber-950/35"
  >
    <div class="font-medium text-[#8A4A0A] dark:text-[#F2C98A]">
      ⚠ {{ t('trip_detail.coordination.conflict_title', { code: conflict.trip_code }) }}
    </div>
    <div class="mt-1 text-xs font-normal text-[#B9720D] dark:text-amber-200/90">
      {{ conflict.time_range }} · {{ conflict.requester }}
    </div>
    <div class="mt-2 flex flex-wrap gap-2">
      <button
        type="button"
        class="rounded-[12px] border-[0.5px] border-[#EF9F27]/55 bg-white px-3 py-1.5 text-xs font-medium text-[#8A4A0A] hover:bg-[#EF9F27]/10 dark:border-amber-700/60 dark:bg-slate-900 dark:text-amber-100 dark:hover:bg-amber-950/50"
        @click="$emit('pick-again')"
      >
        {{ t('trip_detail.coordination.conflict_pick_again') }}
      </button>
      <button
        type="button"
        class="rounded-[12px] border-[0.5px] border-transparent bg-[#EF9F27] px-3 py-1.5 text-xs font-medium text-white hover:bg-[#d98a1f] dark:bg-[#EF9F27] dark:hover:bg-[#f0a84a]"
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
