<template>
  <section
    class="relative overflow-hidden rounded-2xl border border-va-900/20 bg-gradient-to-br from-va-800 via-va-900 to-slate-900 px-4 py-4 text-white shadow-sm sm:px-6 sm:py-6"
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

    <div class="relative flex flex-col gap-3 sm:gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div class="min-w-0 flex-1">
        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/70 sm:text-xs">
          {{ t('portal.dashboard_today', { date: todayLabel }) }}
        </p>
        <h1 class="mt-1 font-display text-lg font-bold tracking-tight sm:text-2xl">
          {{ t('portal.dashboard_greeting', { name: displayName }) }}
        </h1>
        <p class="mt-1.5 max-w-2xl text-xs leading-relaxed text-white/85 sm:mt-2 sm:text-sm">
          {{ t('portal.dashboard_lead') }}
        </p>
      </div>

      <RouterLink
        :to="{ name: 'portalCreate' }"
        class="inline-flex min-h-[40px] shrink-0 items-center justify-center rounded-xl bg-white px-4 text-xs font-semibold text-va-900 shadow-sm transition hover:bg-va-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:min-h-[44px] sm:px-5 sm:text-sm"
        data-testid="portal-home-hero-create"
      >
        {{ t('portal.cta_primary') }}
      </RouterLink>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../store'

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
</script>
