<template>
  <section
    class="relative overflow-hidden rounded-2xl border border-va-900/20 bg-gradient-to-br from-va-800 via-va-900 to-slate-900 px-5 py-5 text-white shadow-sm sm:px-6 sm:py-6"
    :aria-label="t('portal.dashboard_heading')"
    data-testid="portal-home-hero"
  >
    <div
      class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-white/5 blur-2xl"
      aria-hidden="true"
    />
    <div
      class="pointer-events-none absolute -bottom-20 left-1/3 h-40 w-40 rounded-full bg-cyan-400/10 blur-3xl"
      aria-hidden="true"
    />

    <div class="relative flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div class="min-w-0 flex-1">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-white/70">
          {{ t('portal.dashboard_today', { date: todayLabel }) }}
        </p>
        <h1 class="mt-1 font-display text-xl font-bold tracking-tight sm:text-2xl">
          {{ t('portal.dashboard_greeting', { name: displayName }) }}
        </h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-white/85 sm:text-base">
          {{ t('portal.dashboard_lead') }}
        </p>
      </div>

      <RouterLink
        :to="{ name: 'portalCreate' }"
        class="inline-flex min-h-[44px] shrink-0 items-center justify-center rounded-xl bg-white px-5 text-sm font-semibold text-va-900 shadow-sm transition hover:bg-va-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
        data-testid="portal-home-hero-create"
      >
        {{ t('portal.cta_primary') }}
      </RouterLink>
    </div>

    <div
      v-if="showSnapshots"
      class="relative mt-5 grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-3"
      role="list"
      :aria-label="t('portal.home_snapshot_aria')"
    >
      <div
        v-for="snap in snapshots"
        :key="snap.key"
        role="listitem"
        class="rounded-xl border border-white/10 bg-white/10 px-3 py-2.5 backdrop-blur-sm"
        :data-testid="`portal-home-snapshot-${snap.key}`"
      >
        <p class="text-[10px] font-semibold uppercase tracking-wide text-white/70">{{ snap.label }}</p>
        <p class="mt-0.5 font-display text-2xl font-bold tabular-nums tracking-tight">
          {{ snap.loading ? '…' : snap.value }}
        </p>
        <p v-if="snap.hint" class="mt-0.5 text-[11px] leading-snug text-white/75">{{ snap.hint }}</p>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../store'

const props = defineProps({
  loading: { type: Boolean, default: false },
  summary: { type: Object, default: null },
})

const { t, locale } = useI18n()
const auth = useAuthStore()

const displayName = computed(() => {
  const name = auth.user?.name?.trim()
  return name || t('portal.dashboard_guest_name')
})

const todayLabel = computed(() => {
  try {
    return new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-US', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(new Date())
  } catch {
    return ''
  }
})

function fmt(n) {
  const v = typeof n === 'number' && Number.isFinite(n) ? n : 0
  return new Intl.NumberFormat('vi-VN').format(v)
}

const showSnapshots = computed(() => props.loading || props.summary != null)

const completedTrendHint = computed(() => {
  if (props.loading || !props.summary) return ''
  const delta = props.summary?.trends?.completed_this_month
  if (typeof delta !== 'number' || !Number.isFinite(delta) || delta === 0) {
    return t('portal.home_completed_trend_flat')
  }
  if (delta > 0) return t('portal.home_completed_trend_up', { n: fmt(delta) })
  return t('portal.home_completed_trend_down', { n: fmt(Math.abs(delta)) })
})

const snapshots = computed(() => {
  const s = props.summary || {}
  const n = (v) => (typeof v === 'number' && Number.isFinite(v) ? v : 0)
  return [
    {
      key: 'total',
      label: t('portal.stat_total'),
      value: fmt(n(s.total)),
      hint: '',
    },
    {
      key: 'pending',
      label: t('portal.kpi_pending_title'),
      value: fmt(n(s.pending)),
      hint: t('portal.kpi_pending_sub'),
    },
    {
      key: 'processing',
      label: t('portal.kpi_processing_title'),
      value: fmt(n(s.processing)),
      hint: t('portal.kpi_processing_sub'),
    },
    {
      key: 'completed',
      label: t('portal.kpi_completed_title'),
      value: fmt(n(s.completed_this_month)),
      hint: completedTrendHint.value,
    },
  ].map((row) => ({
    ...row,
    loading: props.loading,
  }))
})
</script>
