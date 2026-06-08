<template>
  <header
    class="fixed inset-x-0 top-0 z-50 border-b border-slate-200/60 bg-white/80 shadow-[0_1px_16px_0_rgb(0,0,0,0.04)] backdrop-blur-xl
           supports-[padding:max(0px)]:pt-[env(safe-area-inset-top)]
           supports-[padding:max(0px)]:pl-[env(safe-area-inset-left)]
           supports-[padding:max(0px)]:pr-[env(safe-area-inset-right)]"
  >
    <div class="mx-auto flex max-w-7xl flex-nowrap items-center justify-between gap-2 px-4 py-3.5 sm:gap-3 sm:px-6 lg:gap-6">
      <RouterLink :to="{ name: 'portalHome' }" class="flex min-w-0 shrink items-center gap-2 sm:gap-3">
        <img
          :src="logoUrl"
          alt="VA Dispatch"
          width="48"
          height="48"
          class="h-9 w-9 shrink-0 object-contain sm:h-11 sm:w-11"
          decoding="async"
        />
        <div class="min-w-0">
          <p class="truncate text-xs font-semibold uppercase tracking-wide text-slate-500">{{ t('app.title') }}</p>
          <h1 class="truncate text-sm font-bold text-slate-900 sm:text-lg">{{ t('portal.nav_title') }}</h1>
        </div>
      </RouterLink>

      <nav class="flex shrink-0 flex-1 items-center justify-center gap-1.5 sm:gap-2 lg:gap-3 xl:flex-none xl:justify-start" aria-label="Portal">
        <RouterLink
          :to="{ name: 'portalHome' }"
          class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full px-3 text-sm font-semibold transition sm:min-w-0 sm:px-4 lg:min-w-[8rem]"
          :class="
            homeNavActive
              ? 'bg-va-50 text-va-800 ring-1 ring-inset ring-va-100'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          "
          :aria-current="homeNavActive ? 'page' : undefined"
          :title="t('portal.nav_home')"
        >
          <HomeModernIcon class="h-5 w-5 shrink-0 sm:mr-1.5" aria-hidden="true" />
          <span class="hidden sm:inline">{{ t('portal.nav_home') }}</span>
        </RouterLink>
        <RouterLink
          :to="{ name: listRouteName }"
          class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full px-3 text-sm font-semibold transition sm:min-w-0 sm:px-4 lg:min-w-[8rem]"
          :class="
            isList
              ? 'bg-va-50 text-va-800 ring-1 ring-inset ring-va-100'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          "
          :aria-current="isList ? 'page' : undefined"
          :title="t('portal.nav_list')"
        >
          <ListBulletIcon class="h-5 w-5 shrink-0 sm:mr-1.5" aria-hidden="true" />
          <span class="hidden sm:inline">{{ t('portal.nav_list') }}</span>
        </RouterLink>
        <RouterLink
          :to="{ name: 'portalExtracurricularHome' }"
          class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full px-3 text-sm font-semibold transition sm:min-w-0 sm:px-4 lg:min-w-[8rem]"
          :class="
            isExtracurricularModule
              ? 'bg-va-50 text-va-900 ring-1 ring-inset ring-va-200'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          "
          :aria-current="isExtracurricularModule ? 'page' : undefined"
          :title="t('portal.nav_extracurricular')"
        >
          <AcademicCapIcon class="h-5 w-5 shrink-0 sm:mr-1.5" aria-hidden="true" />
          <span class="hidden lg:inline">{{ t('portal.nav_extracurricular') }}</span>
        </RouterLink>
        <RouterLink
          :to="{ name: createRouteName }"
          class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full px-3 text-sm font-semibold transition sm:min-w-0 sm:px-4 lg:min-w-[8rem]"
          :class="
            isCreate
              ? 'border border-va-800 bg-va-800 text-white shadow-sm hover:bg-va-900'
              : 'border border-va-600/30 text-va-800 hover:bg-va-50'
          "
          :aria-current="isCreate ? 'page' : undefined"
          :title="t('portal.nav_create')"
        >
          <PlusCircleIcon class="h-5 w-5 shrink-0 sm:mr-1.5" aria-hidden="true" />
          <span class="hidden sm:inline">{{ t('portal.nav_create') }}</span>
        </RouterLink>
      </nav>

      <div class="flex shrink-0 items-center gap-1 sm:gap-2">
        <RouterLink
          :to="{ name: 'portalNotifications' }"
          class="relative inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
          :aria-label="t('portal.notifications_bell_aria')"
        >
          <BellOutlineIcon class="h-6 w-6" aria-hidden="true" />
          <span
            v-if="unreadBadge > 0"
            class="absolute right-1 top-1 flex min-h-[18px] min-w-[18px] items-center justify-center rounded-full bg-rose-600 px-1 text-xs font-bold leading-none text-white ring-2 ring-white"
          >
            {{ unreadBadge > 99 ? '99+' : unreadBadge }}
          </span>
        </RouterLink>

        <div ref="menuRootRef" class="relative shrink-0">
        <button
          type="button"
          class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full border border-slate-200/80 bg-white px-2 py-1 font-semibold text-va-900 outline-none ring-va-700/30 transition hover:bg-slate-50 focus-visible:ring-2 disabled:opacity-50 sm:min-w-0 sm:gap-2 sm:px-3"
          :aria-expanded="menuOpen"
          aria-haspopup="menu"
          :disabled="loggingOut"
          @click="menuOpen = !menuOpen"
        >
          <UserAvatar
            class="!h-9 !w-9 !text-xs sm:!h-10 sm:!w-10 sm:!text-sm"
            :name="auth.user?.name"
            :email="auth.user?.email"
            :avatar-url="auth.user?.avatar_url"
            :title="auth.user?.name ?? ''"
            size="md"
            ring-prominent
          />
          <span class="hidden max-w-[10rem] truncate text-left text-sm font-medium text-slate-800 sm:block md:max-w-[14rem]">
            {{ auth.user?.name || auth.user?.email || '' }}
          </span>
          <ChevronDownIcon
            class="hidden h-5 w-5 shrink-0 text-slate-500 transition-transform duration-200 sm:block"
            :class="menuOpen ? 'rotate-180' : ''"
            aria-hidden="true"
          />
        </button>

        <Transition
          enter-active-class="transition ease-out duration-150"
          enter-from-class="translate-y-1 scale-95 opacity-0"
          enter-to-class="translate-y-0 scale-100 opacity-100"
          leave-active-class="transition ease-in duration-100"
          leave-from-class="translate-y-0 scale-100 opacity-100"
          leave-to-class="translate-y-1 scale-95 opacity-0"
        >
          <div
            v-if="menuOpen"
            role="menu"
            class="absolute right-0 z-40 mt-2 w-[min(100vw-2rem,18rem)] origin-top-right overflow-hidden rounded-2xl border border-slate-200 bg-white pb-2 pt-0 shadow-2xl shadow-slate-900/10 ring-1 ring-slate-900/5"
          >
            <div class="flex items-start gap-3 rounded-t-xl bg-gradient-to-br from-va-700/95 to-va-800 px-4 py-3 text-white shadow-inner">
              <UserAvatar
                class="!h-11 !w-11 shrink-0 !bg-white/20 !text-sm !text-white !ring-2 !ring-white/30"
                :name="auth.user?.name"
                :email="auth.user?.email"
                :avatar-url="auth.user?.avatar_url"
                :title="auth.user?.name ?? ''"
                size="lg"
                ring-prominent
              />
              <div class="min-w-0 flex-1 pt-0.5">
                <p v-if="auth.user?.name" class="truncate text-sm font-semibold text-white">{{ auth.user.name }}</p>
                <p
                  v-if="auth.user?.email"
                  class="truncate text-va-100"
                  :class="auth.user?.name ? 'text-xs' : 'text-sm font-semibold text-white'"
                >
                  {{ auth.user.email }}
                </p>
              </div>
            </div>
            <div class="mx-3 mt-3 border-t border-slate-100" />

            <!-- Preferences: language -->
            <div class="px-3 py-2.5">
              <!-- Language -->
              <div>
                <p class="mb-1 text-[10px] font-medium uppercase tracking-wide text-slate-400">
                  {{ t('app.lang') }}
                </p>
                <div class="flex rounded-md bg-slate-100/90 p-0.5">
                  <button
                    type="button"
                    class="flex-1 rounded px-1.5 py-1 text-center text-[11px] font-semibold transition sm:py-1.5"
                    :class="locale === 'vi' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    :aria-pressed="locale === 'vi'"
                    @click="onLocale('vi')"
                  >
                    VI
                  </button>
                  <button
                    type="button"
                    class="flex-1 rounded px-1.5 py-1 text-center text-[11px] font-semibold transition sm:py-1.5"
                    :class="locale === 'en' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                    :aria-pressed="locale === 'en'"
                    @click="onLocale('en')"
                  >
                    EN
                  </button>
                </div>
              </div>
            </div>

            <div class="mx-3 border-t border-slate-100" />
            <div class="px-2 pt-2">
              <button
                type="button"
                role="menuitem"
                class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-rose-700 hover:bg-va-50 disabled:opacity-50"
                :disabled="loggingOut"
                @click="closeMenuThenLogout"
              >
                <ArrowRightStartOnRectangleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                {{ t('portal.logout') }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  AcademicCapIcon,
  ArrowRightStartOnRectangleIcon,
  BellIcon as BellOutlineIcon,
  ChevronDownIcon,
  HomeIcon as HomeModernIcon,
  ListBulletIcon,
  PlusCircleIcon,
} from '@heroicons/vue/24/outline'
import UserAvatar from '../branding/UserAvatar.vue'
import { usePortalExtracurricularModule } from '../../composables/usePortalExtracurricularModule'
import { useAuthStore } from '../../store'
import { useAuthLogout } from '../../composables/useAuthLogout'
import { fetchPortalNotifications } from '../../api/notifications'
import { confirmAction } from '../../composables/useConfirm'
import { setLocale } from '../../i18n'

const logoUrl = '/images/logo/logo-2.png'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { performLogout } = useAuthLogout()

function onLocale(lang) {
  setLocale(lang)
}

const loggingOut = ref(false)
const menuOpen = ref(false)
const menuRootRef = ref(null)

const { isExtracurricularModule } = usePortalExtracurricularModule()

const isHome = computed(() => route.name === 'portalHome')
const listRouteName = computed(() =>
  isExtracurricularModule.value ? 'portalExtracurricularList' : 'portalRequestList',
)
const createRouteName = computed(() =>
  isExtracurricularModule.value ? 'portalExtracurricularCreate' : 'portalCreate',
)

const isCreate = computed(
  () => route.name === 'portalCreate' || route.name === 'portalExtracurricularCreate',
)
const isDetail = computed(
  () => route.name === 'portalRequestDetail' || route.name === 'portalExtracurricularDetail',
)
const isList = computed(
  () => route.name === 'portalRequestList' || route.name === 'portalExtracurricularList',
)

/** Detail là phần mở rộng của trang chủ portal → highlight Home nav */
const homeNavActive = computed(() => {
  if (isExtracurricularModule.value) {
    return route.name === 'portalExtracurricularHome'
  }
  return isHome.value || isDetail.value
})

const unreadBadge = ref(0)
let unreadPollTimer = null

async function refreshUnreadBadge() {
  try {
    const data = await fetchPortalNotifications({ per_page: 1, page: 1 })
    unreadBadge.value = data.meta?.unread_total ?? 0
  } catch {
    unreadBadge.value = 0
  }
}

function onDocPointerDown(e) {
  const root = menuRootRef.value
  if (!root || !menuOpen.value) return
  if (!root.contains(e.target)) menuOpen.value = false
}

onMounted(() => {
  document.addEventListener('pointerdown', onDocPointerDown, true)
  refreshUnreadBadge()
  unreadPollTimer = window.setInterval(refreshUnreadBadge, 60_000)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocPointerDown, true)
  if (unreadPollTimer) window.clearInterval(unreadPollTimer)
})

watch(
  () => route.fullPath,
  () => {
    refreshUnreadBadge()
  },
)

async function closeMenuThenLogout() {
  menuOpen.value = false
  await onLogout()
}

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
  try {
    sessionStorage.removeItem('portal_form_dirty')
    await performLogout()
  } finally {
    loggingOut.value = false
  }
}
</script>
