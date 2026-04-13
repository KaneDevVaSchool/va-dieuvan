<template>
  <!-- Dọc -->
  <div
    v-if="layout === 'vertical'"
    class="shrink-0 border-t border-slate-200/80 bg-white/95 p-2 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/95 md:p-3"
  >
    <div v-if="!compact" class="hidden text-[10px] font-medium uppercase tracking-wide text-slate-400 md:block">
      {{ t('app.account') }}
    </div>
    <div :class="compact ? 'flex flex-col items-center gap-2' : 'mt-0 flex items-center gap-2 md:mt-2 md:gap-3'">
      <div
        class="flex shrink-0 items-center justify-center rounded-lg bg-va-50 text-[11px] font-bold text-va-800 ring-1 ring-va-800/10 dark:bg-va-950/50 dark:text-va-200 dark:ring-va-500/30"
        :class="compact ? 'mx-auto h-9 w-9 text-[10px]' : 'h-9 w-9 md:h-10 md:w-10 md:rounded-xl md:text-xs'"
        :title="auth.user?.name ?? ''"
        aria-hidden="true"
      >
        {{ userInitials }}
      </div>
      <div v-if="!compact" class="hidden min-w-0 flex-1 md:block">
        <div class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ auth.user?.name ?? '—' }}
        </div>
        <div class="truncate text-xs text-slate-500 dark:text-slate-400">
          {{ auth.user?.email ?? '' }}
        </div>
        <div
          v-if="auth.roleNames?.length"
          class="mt-0.5 line-clamp-2 text-[10px] text-slate-400 dark:text-slate-500 lg:text-[11px]"
        >
          {{ auth.roleNames.join(', ') }}
        </div>
      </div>
    </div>

    <label v-if="!compact" class="mt-2 hidden text-[11px] font-medium text-slate-500 md:block dark:text-slate-400">
      {{ t('app.lang') }}
    </label>
    <select
      v-if="!compact"
      class="mt-1.5 hidden w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs shadow-sm focus:border-va-300 focus:outline-none focus:ring-2 focus:ring-va-800/15 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 md:block"
      :value="locale"
      @change="onLocale($event.target.value)"
    >
      <option value="vi">Tiếng Việt</option>
      <option value="en">English</option>
    </select>
    <div v-if="compact" class="w-full">
      <select
        class="w-full rounded-md border border-slate-200 bg-white px-1 py-1.5 text-[10px] shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
        :value="locale"
        :aria-label="t('app.lang')"
        @change="onLocale($event.target.value)"
      >
        <option value="vi">VI</option>
        <option value="en">EN</option>
      </select>
    </div>

    <RouterLink
      class="mt-2 flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-2 py-2 text-[11px] font-medium text-slate-800 shadow-sm transition hover:border-va-200 hover:bg-va-50/50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-va-500/40 dark:hover:bg-va-950/30 md:py-2.5 md:text-sm"
      to="/profile"
      :aria-label="t('app.profile')"
    >
      <UserCircleIcon class="h-5 w-5 shrink-0 md:hidden" aria-hidden="true" />
      <span class="sr-only md:not-sr-only md:inline">{{ t('app.profile') }}</span>
    </RouterLink>
    <button
      type="button"
      class="mt-1.5 w-full rounded-lg border border-slate-200 px-2 py-2 text-[11px] font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800 md:mt-2 md:py-2.5 md:text-sm"
      @click="logout"
    >
      {{ t('app.logout') }}
    </button>
  </div>

  <!-- Ngang -->
  <div
    v-else
    class="flex shrink-0 flex-wrap items-center justify-end gap-1.5 sm:gap-2"
  >
    <div
      class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-va-50 text-[11px] font-bold text-va-800 ring-1 ring-va-800/10 dark:bg-va-950/50 dark:text-va-200"
      :title="auth.user?.name ?? ''"
      aria-hidden="true"
    >
      {{ userInitials }}
    </div>
    <div class="hidden max-w-[10rem] min-w-0 sm:block">
      <div class="truncate text-xs font-semibold text-slate-900 dark:text-slate-100">
        {{ auth.user?.name ?? '—' }}
      </div>
    </div>
    <select
      class="rounded-md border border-slate-200 bg-white px-1.5 py-1 text-[10px] shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 sm:text-xs"
      :value="locale"
      :aria-label="t('app.lang')"
      @change="onLocale($event.target.value)"
    >
      <option value="vi">VI</option>
      <option value="en">EN</option>
    </select>
    <RouterLink
      class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white p-1.5 text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
      to="/profile"
      :aria-label="t('app.profile')"
    >
      <UserCircleIcon class="h-5 w-5" aria-hidden="true" />
    </RouterLink>
    <button
      type="button"
      class="rounded-md border border-slate-200 px-2 py-1 text-[10px] font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800 sm:text-xs"
      @click="logout"
    >
      {{ t('app.logout') }}
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { UserCircleIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../store'
import { setLocale } from '../../i18n'

defineProps({
  layout: { type: String, required: true, validator: (v) => v === 'vertical' || v === 'horizontal' },
  compact: { type: Boolean, default: false },
})

const { t, locale } = useI18n()
const auth = useAuthStore()
const router = useRouter()

function onLocale(v) {
  setLocale(v)
}

async function logout() {
  await auth.logout()
  await router.push({ name: 'login' })
}

const userInitials = computed(() => {
  const name = (auth.user?.name || '').trim()
  if (name) {
    const parts = name.split(/\s+/).filter(Boolean)
    if (parts.length >= 2) {
      return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    }
    return name.slice(0, 2).toUpperCase()
  }
  const local = (auth.user?.email || '').split('@')[0] || ''
  return (local.slice(0, 2) || '?').toUpperCase()
})
</script>
