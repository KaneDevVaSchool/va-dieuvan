<template>
  <!-- Dọc -->
  <div
    v-if="layout === 'vertical'"
    class="shrink-0 border-t border-slate-200/80 bg-white/95 p-2 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/95 md:p-3"
    :class="compact ? 'min-w-0 overflow-hidden' : ''"
  >
    <div v-if="!compact" class="hidden text-[10px] font-medium uppercase tracking-wide text-slate-400 md:block">
      {{ t('app.account') }}
    </div>
    <div :class="compact ? 'flex flex-col items-center gap-2' : 'mt-0 flex items-center gap-2 md:mt-2 md:gap-3'">
      <UserAvatar
        class="shrink-0"
        :class="compact ? 'mx-auto' : ''"
        :name="auth.user?.name"
        :email="auth.user?.email"
        :avatar-url="auth.user?.avatar_url"
        :title="auth.user?.name ?? ''"
        :size="compact ? 'sm' : 'md'"
      />
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
    <div v-if="compact" class="w-full min-w-0 max-w-full">
      <select
        class="w-full min-w-0 max-w-full rounded-md border border-slate-200 bg-white px-1 py-1.5 text-center text-[10px] shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
        :value="locale"
        :aria-label="t('app.lang')"
        @change="onLocale($event.target.value)"
      >
        <option value="vi">VI</option>
        <option value="en">EN</option>
      </select>
    </div>

    <RouterLink
      class="mt-2 flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white text-[11px] font-medium text-slate-800 shadow-sm transition hover:border-va-200 hover:bg-va-50/50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-va-500/40 dark:hover:bg-va-950/30 md:text-sm"
      :class="
        compact
          ? 'aspect-square min-h-0 w-full max-w-full shrink-0 px-0 py-2'
          : 'px-2 py-2 md:py-2.5'
      "
      to="/profile"
      :aria-label="t('app.profile')"
    >
      <UserCircleIcon
        class="h-5 w-5 shrink-0"
        :class="compact ? '' : 'md:hidden'"
        aria-hidden="true"
      />
      <span :class="compact ? 'sr-only' : 'sr-only md:not-sr-only md:inline'">{{ t('app.profile') }}</span>
    </RouterLink>
    <button
      type="button"
      class="mt-1.5 flex w-full items-center justify-center rounded-lg border border-slate-200 text-[11px] font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800 md:mt-2 md:text-sm"
      :class="compact ? 'aspect-square max-w-full px-0 py-2' : 'px-2 py-2 md:py-2.5'"
      @click="logout"
    >
      <ArrowRightOnRectangleIcon v-if="compact" class="h-5 w-5 shrink-0" aria-hidden="true" />
      <span v-if="compact" class="sr-only">{{ t('app.logout') }}</span>
      <template v-else>{{ t('app.logout') }}</template>
    </button>
  </div>

  <!-- Ngang -->
  <div
    v-else
    class="flex min-w-0 max-w-full shrink-0 items-center gap-1.5 sm:gap-2 md:gap-2.5 md:border-l md:border-slate-200/90 md:pl-3 dark:md:border-slate-600/90"
  >
    <RouterLink
      to="/profile"
      class="shrink-0 touch-manipulation rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-va-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
      :aria-label="t('app.profile')"
    >
      <UserAvatar
        :name="auth.user?.name"
        :email="auth.user?.email"
        :avatar-url="auth.user?.avatar_url"
        :title="auth.user?.name ?? ''"
        size="lg"
        ring-prominent
      />
    </RouterLink>
    <div class="hidden min-w-0 max-w-[9rem] md:block">
      <div class="truncate text-xs font-semibold leading-tight text-slate-900 dark:text-slate-100">
        {{ auth.user?.name ?? '—' }}
      </div>
      <div class="truncate text-[10px] text-slate-500 dark:text-slate-400">
        {{ auth.user?.email ?? '' }}
      </div>
    </div>
    <select
      class="shrink-0 rounded-lg border border-slate-200/90 bg-white px-1.5 py-1.5 text-[10px] font-medium shadow-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 sm:px-2 sm:text-xs"
      :value="locale"
      :aria-label="t('app.lang')"
      @change="onLocale($event.target.value)"
    >
      <option value="vi">VI</option>
      <option value="en">EN</option>
    </select>
    <button
      type="button"
      class="inline-flex shrink-0 items-center justify-center rounded-lg border border-slate-200/90 bg-white p-2 text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 md:px-2.5 md:py-1.5 md:text-xs md:font-medium"
      :title="t('app.logout')"
      @click="logout"
    >
      <ArrowRightOnRectangleIcon class="h-5 w-5 md:hidden" aria-hidden="true" />
      <span class="hidden md:inline">{{ t('app.logout') }}</span>
    </button>
  </div>
</template>

<script setup>
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowRightOnRectangleIcon, UserCircleIcon } from '@heroicons/vue/24/outline'
import UserAvatar from '../branding/UserAvatar.vue'
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

</script>
