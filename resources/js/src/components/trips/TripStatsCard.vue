<template>
  <div
    class="rounded-2xl border border-[rgba(255,255,255,0.06)] bg-driver-card px-4 py-3 shadow-lg shadow-black/20"
    style="border-radius: var(--radius-card, 16px)"
  >
    <p class="mb-3 text-sm font-bold uppercase tracking-wider text-[#7fdcc8] sm:text-base">
      {{ t('trip_history_page.stats_month_title') }}
    </p>

    <!-- Skeleton -->
    <div v-if="loading" class="flex items-center gap-0">
      <div v-for="i in 3" :key="i" class="flex flex-1 flex-col items-center gap-2 py-1">
        <div class="h-8 w-12 animate-pulse rounded-md bg-white/[0.07]" />
        <div class="h-3 w-10 animate-pulse rounded bg-white/[0.04]" />
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
          class="text-3xl font-bold tabular-nums leading-tight sm:text-4xl"
          :style="{ color: stat.color }"
        >
          {{ stat.value }}
        </span>
        <span class="text-xs font-medium tracking-wide text-driver-muted/80 sm:text-sm">
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
        class="mt-0.5 h-5 w-5 shrink-0 text-amber-400"
        aria-hidden="true"
      >
        <path
          fill-rule="evenodd"
          d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 0-.75.75v3.5a.75.75 0 0 0 1.5 0v-3.5A.75.75 0 0 0 10 5Zm1 8a1 1 0 1 0-2 0 1 1 0 0 0 2 0Z"
          clip-rule="evenodd"
        />
      </svg>
      <p class="min-w-0 text-sm font-medium leading-snug text-amber-100 sm:text-base">
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
      key: 'overdue',
      value: Number.isFinite(Number(s?.overdue_count)) ? Math.max(0, Math.floor(Number(s.overdue_count))) : 0,
      label: t('trip_history_page.status_overdue'),
      color: '#fb923c',
    },
  ]
})
</script>
