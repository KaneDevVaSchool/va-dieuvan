<template>
  <header
    class="sticky top-0 z-30 border-b border-slate-200/90 bg-white/98 shadow-sm backdrop-blur-md
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
          <p class="truncate text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ t('app.title') }}</p>
          <h1 class="truncate text-sm font-bold text-slate-900 sm:text-lg">{{ t('portal.nav_title') }}</h1>
        </div>
      </RouterLink>

      <nav class="flex shrink-0 items-center gap-1.5 sm:gap-2 lg:gap-3" aria-label="Portal">
        <RouterLink
          :to="{ name: 'portalHome' }"
          class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full px-3 text-sm font-semibold transition sm:min-w-0 sm:px-4 lg:min-w-[8rem]"
          :class="
            homeNavActive
              ? 'bg-va-50 text-va-800'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          "
          :aria-current="homeNavActive ? 'page' : undefined"
          :title="t('portal.nav_home')"
        >
          <HomeModernIcon class="h-5 w-5 shrink-0 sm:mr-1.5" aria-hidden="true" />
          <span class="hidden sm:inline">{{ t('portal.nav_home') }}</span>
        </RouterLink>
        <RouterLink
          :to="{ name: 'portalCreate' }"
          class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full px-3 text-sm font-semibold transition sm:min-w-0 sm:px-4 lg:min-w-[8rem]"
          :class="
            isCreate
              ? 'border border-va-800 bg-va-800 text-white shadow-sm hover:bg-va-900'
              : 'border border-va-800/25 text-va-800 hover:bg-va-50'
          "
          :aria-current="isCreate ? 'page' : undefined"
          :title="t('portal.nav_create')"
        >
          <PlusCircleIcon class="h-5 w-5 shrink-0 sm:mr-1.5" aria-hidden="true" />
          <span class="hidden sm:inline">{{ t('portal.nav_create') }}</span>
        </RouterLink>
      </nav>

      <div ref="menuRootRef" class="relative shrink-0">
        <button
          type="button"
          class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full border border-slate-200/80 bg-white px-2 py-1 font-semibold text-va-900 outline-none ring-va-800/30 transition hover:bg-slate-50 focus-visible:ring-2 disabled:opacity-50 sm:min-w-0 sm:gap-2 sm:px-3"
          :aria-expanded="menuOpen"
          aria-haspopup="menu"
          :disabled="loggingOut"
          @click="menuOpen = !menuOpen"
        >
          <span
            class="flex h-9 w-9 items-center justify-center rounded-full bg-va-800/10 text-xs font-bold uppercase tracking-wide text-va-900 sm:h-10 sm:w-10 sm:text-sm"
            aria-hidden="true"
          >
            {{ userInitials }}
          </span>
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
            class="absolute right-0 z-40 mt-2 w-[min(100vw-2rem,18rem)] origin-top-right rounded-2xl border border-slate-200 bg-white py-3 shadow-2xl shadow-slate-900/10 ring-1 ring-slate-900/5"
          >
            <div class="flex items-start gap-3 px-4">
              <span
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-va-800/10 text-sm font-bold uppercase tracking-wide text-va-900"
                aria-hidden="true"
              >
                {{ userInitials }}
              </span>
              <div class="min-w-0 flex-1 pt-0.5">
                <p v-if="auth.user?.name" class="truncate text-sm font-semibold text-slate-900">{{ auth.user.name }}</p>
                <p
                  v-if="auth.user?.email"
                  class="truncate text-slate-500"
                  :class="auth.user?.name ? 'text-xs' : 'text-sm font-semibold text-slate-900'"
                >
                  {{ auth.user.email }}
                </p>
              </div>
            </div>
            <div class="mx-3 mt-3 border-t border-slate-100" />
            <div class="px-2 pt-2">
              <button
                type="button"
                role="menuitem"
                class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-rose-700 hover:bg-rose-50 disabled:opacity-50"
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
  </header>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowRightStartOnRectangleIcon,
  ChevronDownIcon,
  HomeIcon as HomeModernIcon,
  PlusCircleIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../store'
import { confirmAction } from '../../composables/useConfirm'

const logoUrl = '/images/logo/logo-2.png'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const loggingOut = ref(false)
const menuOpen = ref(false)
const menuRootRef = ref(null)

const isHome = computed(() => route.name === 'portalHome')
const isCreate = computed(() => route.name === 'portalCreate')
const isDetail = computed(() => route.name === 'portalRequestDetail')

/** Detail is treated as sub-view of list → highlight Home nav */
const homeNavActive = computed(() => isHome.value || isDetail.value)

const userInitials = computed(() => {
  const name = auth.user?.name?.trim()
  const email = auth.user?.email?.trim()
  const src = name || email || '?'
  const parts = src.split(/\s+/).filter(Boolean)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase().slice(0, 2)
  }
  return src.slice(0, 2).toUpperCase()
})

function onDocPointerDown(e) {
  const root = menuRootRef.value
  if (!root || !menuOpen.value) return
  if (!root.contains(e.target)) menuOpen.value = false
}

onMounted(() => {
  document.addEventListener('pointerdown', onDocPointerDown, true)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocPointerDown, true)
})

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
    await auth.logout()
    await router.replace({ name: 'login' })
  } finally {
    loggingOut.value = false
  }
}
</script>
