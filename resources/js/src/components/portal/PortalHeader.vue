<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowRightStartOnRectangleIcon,
  BellIcon,
  ChevronDownIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline'
import Button from '../ui/Button.vue'
import UserAvatar from '../branding/UserAvatar.vue'
import PortalGlobalSearch from './shell/PortalGlobalSearch.vue'
import PortalPrimaryNav from './shell/PortalPrimaryNav.vue'
import { usePortalBreadcrumb } from '../../composables/usePortalBreadcrumb'
import { usePortalPrimaryNav } from '../../composables/usePortalPrimaryNav'
import { useAuthStore } from '../../store'
import { useAuthLogout } from '../../composables/useAuthLogout'
import { fetchPortalNotifications } from '../../api/notifications'
import { confirmAction } from '../../composables/useConfirm'
import { setLocale } from '../../i18n'

const { t, locale } = useI18n()
const route = useRoute()
const auth = useAuthStore()
const { performLogout } = useAuthLogout()
const { segments, pageTitle } = usePortalBreadcrumb()
const { createTo } = usePortalPrimaryNav()

const menuOpen = ref(false)
const userMenuRef = ref(null)
const loggingOut = ref(false)
const unreadBadge = ref(0)
let unreadPollTimer = null

function onLocale(lang) {
  setLocale(lang)
}

async function refreshUnreadBadge() {
  try {
    const data = await fetchPortalNotifications({ per_page: 1, page: 1 })
    unreadBadge.value = data.meta?.unread_total ?? 0
  } catch {
    unreadBadge.value = 0
  }
}

function onDocPointerDown(e) {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
    menuOpen.value = false
  }
}

watch(
  () => route.fullPath,
  () => {
    refreshUnreadBadge()
  },
)

onMounted(() => {
  document.addEventListener('pointerdown', onDocPointerDown, true)
  refreshUnreadBadge()
  unreadPollTimer = window.setInterval(refreshUnreadBadge, 60_000)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocPointerDown, true)
  if (unreadPollTimer) window.clearInterval(unreadPollTimer)
})

async function onLogout() {
  const dirty = !!sessionStorage.getItem('portal_form_dirty')
  if (dirty) {
    const ok = await confirmAction({
      title: t('portal.logout_confirm_title'),
      message: t('portal.logout_confirm_msg'),
      confirmLabel: t('portal.logout'),
      danger: true,
    })
    if (!ok) return
  }
  loggingOut.value = true
  menuOpen.value = false
  try {
    sessionStorage.removeItem('portal_form_dirty')
    await performLogout()
  } finally {
    loggingOut.value = false
  }
}

</script>

<template>
  <header
    class="fixed inset-x-0 top-0 z-50 border-b border-slate-200/80 bg-white
           supports-[padding:max(0px)]:pt-[env(safe-area-inset-top)]
           supports-[padding:max(0px)]:pl-[env(safe-area-inset-left)]
           supports-[padding:max(0px)]:pr-[env(safe-area-inset-right)]"
    data-testid="portal-shell-header"
  >
    <div class="flex h-12 w-full min-w-0 items-center gap-2 px-3 sm:gap-3 sm:px-4 lg:px-8">
      <RouterLink
        :to="{ name: 'portalHome' }"
        class="inline-flex shrink-0 items-center md:gap-2"
        :aria-label="t('portal.shell.breadcrumb_root')"
        data-testid="portal-header-brand"
      >
        <span
          class="flex h-9 w-9 items-center justify-center rounded-lg bg-va-800 text-xs font-bold tracking-tight text-white ring-1 ring-va-900/20 sm:h-8 sm:w-8 sm:rounded-md"
        >
          VA
        </span>
        <span class="hidden text-sm font-semibold text-slate-800 md:inline">{{ t('portal.nav_title') }}</span>
      </RouterLink>

      <PortalPrimaryNav />

      <!-- Breadcrumb (context on lg+) / mobile title -->
      <nav
        class="min-w-0 flex-1"
        :aria-label="t('portal.shell.breadcrumb_aria')"
      >
        <ol class="hidden min-w-0 items-center gap-1 text-sm lg:flex">
          <li v-for="(seg, idx) in segments" :key="idx" class="flex min-w-0 items-center gap-1">
            <span v-if="idx > 0" class="shrink-0 text-slate-300" aria-hidden="true">/</span>
            <RouterLink
              v-if="seg.to && idx < segments.length - 1"
              :to="seg.to"
              class="truncate font-medium text-slate-500 hover:text-va-800"
            >
              {{ seg.label }}
            </RouterLink>
            <span
              v-else
              class="truncate font-semibold text-slate-900"
              :class="idx === segments.length - 1 ? '' : 'text-slate-500'"
            >
              {{ seg.label }}
            </span>
          </li>
        </ol>
        <p class="truncate text-sm font-semibold text-slate-900 lg:hidden">{{ pageTitle }}</p>
      </nav>

      <PortalGlobalSearch />

      <div class="flex shrink-0 items-center gap-0.5 sm:gap-2">
        <RouterLink
          :to="createTo"
          class="hidden sm:inline-flex"
          data-testid="portal-header-create"
        >
          <Button variant="primary" class="!min-h-10 gap-1.5 whitespace-nowrap">
            <PlusIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
            <span class="hidden lg:inline">{{ t('portal.shell.create_request') }}</span>
            <span class="lg:hidden">{{ t('portal.shell.create_short') }}</span>
          </Button>
        </RouterLink>

        <RouterLink
          :to="{ name: 'portalNotifications' }"
          class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100"
          :aria-label="t('portal.notifications_bell_aria')"
          data-testid="portal-header-notifications"
        >
          <BellIcon class="h-5 w-5" aria-hidden="true" />
          <span
            v-if="unreadBadge > 0"
            class="absolute right-0.5 top-0.5 flex min-h-[16px] min-w-[16px] items-center justify-center rounded-full bg-rose-600 px-0.5 text-[10px] font-bold text-white ring-2 ring-white"
          >
            {{ unreadBadge > 99 ? '99+' : unreadBadge }}
          </span>
        </RouterLink>

        <div ref="userMenuRef" class="relative">
          <button
            type="button"
            class="flex h-10 items-center gap-1.5 rounded-lg border border-transparent px-1 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-va-800"
            :aria-expanded="menuOpen"
            aria-haspopup="menu"
            :disabled="loggingOut"
            data-testid="portal-header-user"
            @click="menuOpen = !menuOpen"
          >
            <UserAvatar
              class="!h-8 !w-8 !text-xs"
              :name="auth.user?.name"
              :email="auth.user?.email"
              :avatar-url="auth.user?.avatar_url"
              size="md"
            />
            <ChevronDownIcon class="hidden h-4 w-4 text-slate-500 sm:block" aria-hidden="true" />
          </button>
          <div
            v-if="menuOpen"
            role="menu"
            class="absolute right-0 z-50 mt-1 w-[min(100vw-1.5rem,14rem)] overflow-hidden rounded-xl border border-slate-200 bg-white py-2 shadow-lg ring-1 ring-slate-900/5 sm:w-56"
          >
            <div class="border-b border-slate-100 px-3 pb-2 pt-1">
              <p v-if="auth.user?.name" class="truncate text-sm font-semibold text-slate-900">{{ auth.user.name }}</p>
              <p v-if="auth.user?.email" class="truncate text-xs text-slate-500">{{ auth.user.email }}</p>
            </div>
            <div class="px-3 py-2">
              <p class="mb-1 text-[10px] font-medium uppercase tracking-wide text-slate-400">{{ t('app.lang') }}</p>
              <div class="flex rounded-md bg-slate-100 p-0.5">
                <button
                  type="button"
                  class="flex-1 rounded px-2 py-1 text-[11px] font-semibold"
                  :class="locale === 'vi' ? 'bg-slate-800 text-white' : 'text-slate-600'"
                  @click="onLocale('vi')"
                >
                  VI
                </button>
                <button
                  type="button"
                  class="flex-1 rounded px-2 py-1 text-[11px] font-semibold"
                  :class="locale === 'en' ? 'bg-slate-800 text-white' : 'text-slate-600'"
                  @click="onLocale('en')"
                >
                  EN
                </button>
              </div>
            </div>
            <div class="border-t border-slate-100 px-2 pt-1">
              <button
                type="button"
                role="menuitem"
                class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-sm font-medium text-rose-700 hover:bg-rose-50"
                :disabled="loggingOut"
                @click="onLogout"
              >
                <ArrowRightStartOnRectangleIcon class="h-4 w-4" aria-hidden="true" />
                {{ t('portal.logout') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>
