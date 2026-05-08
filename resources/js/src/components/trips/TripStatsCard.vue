<template>
  <div
    class="rounded-2xl border border-[rgba(255,255,255,0.06)] bg-driver-card px-4 py-3 shadow-lg shadow-black/20"
    style="border-radius: var(--radius-card, 16px)"
  >
    <p class="mb-2.5 text-[10px] font-bold uppercase tracking-wider text-[#7fdcc8]">
      {{ t('trip_history_page.stats_month_title') }}
    </p>

    <!-- Skeleton -->
    <div v-if="loading" class="flex items-center gap-0">
      <div v-for="i in 3" :key="i" class="flex flex-1 flex-col items-center gap-1.5 py-1">
        <div class="h-6 w-10 animate-pulse rounded-md bg-white/[0.07]" />
        <div class="h-2.5 w-8 animate-pulse rounded bg-white/[0.04]" />
      </div>
    </div>

    <!-- Stats row -->
    <div v-else class="flex items-stretch">
      <div
        v-for="(stat, idx) in statItems"
        :key="stat.key"
        class="flex flex-1 flex-col items-center gap-0.5 px-1 py-1"
        :class="idx < statItems.length - 1 ? 'border-r border-white/[0.08]' : ''"
      >
        <span
          class="text-[22px] font-bold tabular-nums leading-tight"
          :style="{ color: stat.color }"
        >
          {{ stat.value }}
        </span>
        <span class="text-[10px] font-medium tracking-wide text-driver-muted/70">
          {{ stat.label }}
        </span>
      </div>
    </div>

    <!-- Overdue reminder -->
    <div
      v-if="!loading && overdueCount > 0"
      class="mt-3 flex gap-2.5 rounded-xl border border-amber-500/35 bg-amber-500/10 px-3 py-2.5"
      role="status"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 20 20"
        fill="currentColor"
        class="h-4 w-4 shrink-0 text-amber-400"
        aria-hidden="true"
      >
        <path
          fill-rule="evenodd"
          d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 0-.75.75v3.5a.75.75 0 0 0 1.5 0v-3.5A.75.75 0 0 0 10 5Zm1 8a1 1 0 1 0-2 0 1 1 0 0 0 2 0Z"
          clip-rule="evenodd"
        />
      </svg>
      <p class="min-w-0 text-[11px] font-medium leading-snug text-amber-100">
        {{ t('trip_history_page.stats_overdue_notice', { n: overdueCount }) }}
      </p>
    </div>
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

const overdueCount = computed(() => {
  const n = Number(props.stats?.overdue_count)
  return Number.isFinite(n) && n > 0 ? Math.floor(n) : 0
})

const statItems = computed(() => {
  const s = props.stats
  return [
    {
      key: 'total',
      value: s?.total ?? 0,
      label: t('trip_history_page.stats_total'),
      color: '#7fdcc8',
    },
    {
      key: 'completed',
      value: s?.completed ?? 0,
      label: t('trip_history_page.filter_completed'),
      color: '#34d399',
    },
    {
      key: 'cancelled',
      value: s?.cancelled ?? 0,
      label: t('trip_history_page.filter_cancelled'),
      color: '#f43f5e',
    },
  ]
})
</script>
