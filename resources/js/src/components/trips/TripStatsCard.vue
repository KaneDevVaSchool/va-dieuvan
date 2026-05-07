<template>
  <div
    class="rounded-2xl border border-[rgba(255,255,255,0.06)] bg-[#0f2318] p-4 shadow-lg shadow-black/20"
    style="border-radius: var(--radius-card, 16px)"
  >
    <p class="text-[10px] font-bold uppercase tracking-wider text-[#7fdcc8]">
      {{ t('trip_history_page.stats_month_title') }}
    </p>

    <div v-if="loading" class="mt-3 flex flex-wrap gap-2">
      <div class="h-9 w-36 animate-pulse rounded-lg bg-white/5" />
      <div class="h-9 w-36 animate-pulse rounded-lg bg-white/5" />
    </div>

    <template v-else>
      <div class="mt-3 flex flex-wrap gap-2">
        <div
          class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold"
          style="background: rgb(127 220 200 / 0.12); color: #7fdcc8"
        >
        <span class="tabular-nums">{{ t('trip_history_page.stats_completed_line', { n: stats?.completed_this_month ?? 0 }) }}</span>
        </div>
        <div
          class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold"
          style="background: rgb(244 63 94 / 0.12); color: #f43f5e"
        >
          <span class="tabular-nums">{{ t('trip_history_page.stats_cancelled_line', { n: stats?.cancelled_this_month ?? 0 }) }}</span>
        </div>
      </div>
      <p
        v-if="growthText"
        class="mt-2 text-xs text-[#94a3b8]"
      >
        {{ growthText }}
      </p>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  stats: { type: Object, default: null },
  loading: { type: Boolean, default: false },
})

const { t } = useI18n()

const growthText = computed(() => {
  const p = props.stats?.completed_growth_pct
  if (p == null) return ''
  if (p === 0) {
    return t('trip_history_page.stats_growth_same')
  }
  if (p > 0) {
    return t('trip_history_page.stats_growth_up', { n: Math.abs(p) })
  }
  return t('trip_history_page.stats_growth_down', { n: Math.abs(p) })
})
</script>
