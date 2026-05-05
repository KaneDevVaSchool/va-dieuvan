<template>
  <div
    class="overflow-hidden rounded-[12px] border-[0.5px] border-slate-200/90 border-t-[2px] border-t-[#378ADD] bg-sky-50/35 p-3 dark:border-slate-600/80 dark:bg-sky-950/25"
  >
    <div class="flex items-start gap-3">
      <div
        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-[0.5px] border-slate-200/80 bg-white text-sm font-medium text-[#378ADD] dark:border-slate-700 dark:bg-slate-900 dark:text-[#6cb3f5]"
      >
        {{ initials }}
      </div>
      <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-2 flex-nowrap">
          <div class="min-w-0 truncate text-[13px] font-medium text-slate-900 dark:text-slate-100">
            {{ driver?.full_name ?? '—' }}
          </div>
          <button
            type="button"
            class="shrink-0 rounded-[12px] border-[0.5px] border-[#8B1A1A]/50 px-2 py-1 text-[11px] font-normal text-[#8B1A1A] hover:bg-[#8B1A1A]/5 dark:border-[#8B1A1A]/40 dark:text-[#e85c5c] dark:hover:bg-[#8B1A1A]/10"
            @click="$emit('change')"
          >
            {{ t('trip_detail.coordination.card_change') }} →
          </button>
        </div>
        <div class="mt-0.5 text-[11px] font-normal text-slate-600 dark:text-slate-400">
          {{ t('trip_detail.coordination.driver_internal_subtitle') }}
        </div>
        <div class="mt-2">
          <span
            class="inline-flex items-center gap-1.5 rounded-full border-[0.5px] px-2 py-0.5 text-[12px] font-normal"
            :class="
              busy
                ? 'border-rose-300/60 bg-rose-50/80 text-rose-800 dark:border-rose-800/50 dark:bg-rose-950/40 dark:text-rose-200'
                : 'border-[#1D9E75]/40 bg-emerald-50/50 text-[#1D9E75] dark:border-[#1D9E75]/35 dark:bg-emerald-950/30 dark:text-[#4dcf9a]'
            "
          >
            <span
              class="h-1.5 w-1.5 shrink-0 rounded-full"
              :class="busy ? 'bg-rose-500' : 'bg-[#1D9E75]'"
              aria-hidden="true"
            />
            {{ busy ? t('trip_detail.coordination.driver_busy') : t('trip_detail.coordination.driver_free') }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps<{
  driver: {
    full_name?: string | null
    phone?: string | null
    user?: { employee_code?: string | null } | null
  } | null
  busy?: boolean
}>()

defineEmits<{
  change: []
}>()

const { t } = useI18n()

const initials = computed(() => {
  const name = String(props.driver?.full_name ?? '').trim()
  if (!name || name === '—') return '?'
  const parts = name.split(/\s+/).filter(Boolean)
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})
</script>
