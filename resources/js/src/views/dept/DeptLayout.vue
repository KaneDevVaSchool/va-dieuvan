<template>
  <div
    class="flex h-dvh min-h-0 flex-row overflow-hidden bg-slate-50 text-slate-900 antialiased supports-[padding:max(0px)]:pt-[env(safe-area-inset-top)] supports-[padding:max(0px)]:pb-[env(safe-area-inset-bottom)] dark:bg-slate-950 dark:text-slate-100"
  >
    <!-- Mobile drawer backdrop -->
    <button
      v-if="mobileOpen"
      type="button"
      class="fixed inset-0 z-40 bg-black/50 backdrop-blur-[2px] md:hidden"
      :aria-label="t('dept.aria_close_menu')"
      @click="mobileOpen = false"
    />

    <aside
      id="dept-sidebar"
      :class="[
        'flex shrink-0 flex-col border-r border-white/10 bg-[color:var(--va-brand)] text-white shadow-[inset_-1px_0_0_0_rgba(0,0,0,0.08)]',
        'fixed inset-y-0 left-0 z-50 transition-transform duration-300 ease-in-out md:relative md:inset-auto md:left-auto md:z-auto md:translate-x-0 md:transition-none',
        mobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
        verticalAsideWidthClass,
      ]"
    >
      <div
        class="shrink-0 border-b border-white/15 bg-[color:var(--va-brand)]/95 backdrop-blur-sm"
        :class="compactNav ? 'px-1 py-1.5 md:px-1.5 md:py-2' : 'px-2 py-2 md:px-3 md:py-2.5'"
      >
        <div
          class="flex w-full min-w-0 items-center gap-2"
          :class="compactNav ? 'flex-col md:flex-col' : 'flex-row justify-between'"
        >
          <RouterLink
            :to="{ name: 'deptDashboard' }"
            class="flex min-w-0 flex-1 items-center gap-2 rounded-lg outline-none ring-white/30 transition hover:bg-white/10 focus-visible:ring-2"
            :class="compactNav ? 'justify-center p-1 md:justify-center' : 'gap-3 p-0.5'"
            @click="closeMobileDrawer"
          >
            <img
              :src="logoUrl"
              alt=""
              width="36"
              height="36"
              class="shrink-0 rounded-full object-contain p-0.5 ring-2 ring-white/30"
              :class="compactNav ? 'h-8 w-8' : 'h-9 w-9 md:h-10 md:w-10'"
              decoding="async"
            />
            <div v-if="!compactNav" class="min-w-0 flex-1">
              <p class="truncate text-[10px] font-semibold uppercase tracking-wide text-white/70">
                {{ t('app.title') }}
              </p>
              <p class="truncate text-xs font-bold leading-tight md:text-sm">{{ t('portal.nav_title') }}</p>
            </div>
          </RouterLink>

          <div class="flex shrink-0 items-center gap-1">
            <button
              type="button"
              class="hidden shrink-0 items-center justify-center rounded-md text-white/85 transition hover:bg-white/10 md:inline-flex"
              :class="compactNav ? 'h-7 w-7' : 'h-8 w-8'"
              :title="sidebarCollapsed ? t('app.sidebar_expand') : t('app.sidebar_collapse')"
              @click="sidebarCollapsed = !sidebarCollapsed"
            >
              <ChevronLeftIcon v-if="!sidebarCollapsed" class="h-4 w-4 sm:h-[1.125rem] sm:w-[1.125rem]" aria-hidden="true" />
              <ChevronRightIcon v-else class="h-4 w-4" aria-hidden="true" />
              <span class="sr-only">
                {{ sidebarCollapsed ? t('app.sidebar_expand') : t('app.sidebar_collapse') }}
              </span>
            </button>

            <button
              type="button"
              class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-white/90 transition hover:bg-white/10 md:hidden"
              :title="t('app.cancel')"
              @click="mobileOpen = false"
            >
              <XMarkIcon class="h-6 w-6" aria-hidden="true" />
              <span class="sr-only">{{ t('app.cancel') }}</span>
            </button>
          </div>
        </div>

        <div
          v-if="!compactNav"
          class="mt-3 flex items-center gap-3 rounded-xl bg-white/10 px-3 py-2.5 md:mt-3"
        >
          <span
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/20 text-xs font-bold uppercase text-white ring-2 ring-white/25"
          >
            {{ userInitials }}
          </span>
          <div class="min-w-0 flex-1 text-xs leading-snug">
            <p class="truncate font-semibold">{{ auth.user?.name || '—' }}</p>
            <p class="truncate text-white/75">{{ deptDisplayName }}</p>
          </div>
        </div>
        <div v-else class="mt-2 hidden justify-center md:flex">
          <span
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/20 text-[10px] font-bold uppercase text-white ring-2 ring-white/25"
            :title="auth.user?.name || ''"
          >
            {{ userInitials }}
          </span>
        </div>
      </div>

      <nav
        class="min-h-0 flex-1 space-y-1 overflow-y-auto overscroll-y-contain scrollbar-hidden px-1 py-2 md:px-2 md:py-2.5"
        :aria-label="t('dept.aria_nav')"
      >
        <p
          v-if="!compactNav"
          class="mb-1 px-2 text-[10px] font-bold uppercase tracking-wider text-white/45"
        >
          {{ t('dept.nav_section_requests') }}
        </p>
        <p v-else class="sr-only">{{ t('dept.nav_section_requests') }}</p>

        <RouterLink
          :to="{ name: 'deptDashboard' }"
          :class="navLinkClass('deptDashboard')"
          :title="compactNav ? t('dept.nav_pending') : undefined"
          @click="closeMobileDrawer"
        >
          <ClipboardDocumentListIcon class="h-5 w-5 shrink-0 opacity-90" aria-hidden="true" />
          <span v-if="!compactNav" class="min-w-0 flex-1 truncate pl-0">{{ t('dept.nav_pending') }}</span>
          <span
            v-if="!compactNav && summary.pending_count > 0"
            class="inline-flex min-h-[1.25rem] min-w-[1.25rem] shrink-0 items-center justify-center rounded-full bg-white px-1.5 text-[10px] font-bold leading-none text-[color:var(--va-brand)]"
          >
            {{ summary.pending_count > 99 ? '99+' : summary.pending_count }}
          </span>
          <span
            v-if="compactNav && summary.pending_count > 0"
            class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-amber-100 px-0.5 text-[9px] font-bold text-amber-900"
          >
            {{ summary.pending_count > 99 ? '99+' : summary.pending_count }}
          </span>
        </RouterLink>

        <RouterLink
          :to="{ name: 'deptApproved' }"
          :class="navLinkClass('deptApproved')"
          :title="compactNav ? t('dept.nav_approved') : undefined"
          @click="closeMobileDrawer"
        >
          <CheckCircleIcon class="h-5 w-5 shrink-0 opacity-90" aria-hidden="true" />
          <span v-if="!compactNav" class="min-w-0 flex-1 truncate">{{ t('dept.nav_approved') }}</span>
        </RouterLink>

        <RouterLink
          :to="{ name: 'deptRejected' }"
          :class="navLinkClass('deptRejected')"
          :title="compactNav ? t('dept.nav_rejected') : undefined"
          @click="closeMobileDrawer"
        >
          <XCircleIcon class="h-5 w-5 shrink-0 opacity-90" aria-hidden="true" />
          <span v-if="!compactNav" class="min-w-0 flex-1 truncate">{{ t('dept.nav_rejected') }}</span>
        </RouterLink>

        <RouterLink
          :to="{ name: 'deptAll' }"
          :class="navLinkClass('deptAll')"
          :title="compactNav ? t('dept.nav_all') : undefined"
          @click="closeMobileDrawer"
        >
          <QueueListIcon class="h-5 w-5 shrink-0 opacity-90" aria-hidden="true" />
          <span v-if="!compactNav" class="min-w-0 flex-1 truncate">{{ t('dept.nav_all') }}</span>
        </RouterLink>
      </nav>

      <div
        class="shrink-0 border-t border-white/15 py-1.5 md:py-2"
        :class="compactNav ? 'flex justify-center px-1 md:px-1.5' : 'flex items-center px-2 md:px-3'"
      >
        <NotificationBell tone="brand" />
      </div>

      <div class="shrink-0 border-t border-white/15 p-2 md:p-3">
        <p
          v-if="!compactNav"
          class="rounded-lg bg-black/15 px-3 py-2 text-center text-xs font-semibold text-white/90"
        >
          {{ t('dept.role_badge') }}
        </p>
        <p
          v-else
          class="hidden rounded-lg bg-black/15 py-2 text-center text-[10px] font-semibold leading-tight text-white/90 md:block"
          :title="t('dept.role_badge')"
        >
          <span class="sr-only">{{ t('dept.role_badge') }}</span>
          <span aria-hidden="true">★</span>
        </p>
        <button
          type="button"
          class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15"
          :class="compactNav ? 'md:px-1 md:py-2.5' : ''"
          :title="compactNav ? t('portal.logout') : undefined"
          :disabled="loggingOut"
          @click="onLogout"
        >
          <ArrowRightStartOnRectangleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
          <span v-if="!compactNav">{{ t('portal.logout') }}</span>
        </button>
      </div>
    </aside>

    <div class="flex min-h-0 min-w-0 flex-1 flex-col">
      <header
        v-if="route.name !== 'deptRequestDetail'"
        class="sticky top-0 z-30 hidden shrink-0 border-b border-slate-200/90 bg-white/95 px-4 py-4 shadow-sm backdrop-blur-md dark:border-slate-700 dark:bg-slate-900/95 md:flex md:px-6 lg:px-8"
      >
        <div class="min-w-0 flex-1">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ t('portal.nav_title') }}
            <span class="text-slate-300 dark:text-slate-600"> · </span>
            <span class="text-slate-600 dark:text-slate-300">{{ deptDisplayName }}</span>
          </p>
          <h1 class="mt-0.5 truncate text-xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
            {{ pageTitle }}
          </h1>
          <p v-if="pageSubtitle" class="mt-0.5 text-sm text-slate-600 dark:text-slate-400">
            {{ pageSubtitle }}
          </p>
        </div>
        <span
          v-if="summary.pending_count > 0 && route.name === 'deptDashboard'"
          class="ml-4 inline-flex shrink-0 items-center gap-1.5 self-start rounded-full bg-[color:var(--va-brand)]/10 px-3 py-1.5 text-xs font-semibold text-[color:var(--va-brand)] ring-1 ring-[color:var(--va-brand)]/20"
        >
          {{ t('dept.kpi_pending') }}
          <span class="tabular-nums">{{ summary.pending_count }}</span>
        </span>
      </header>

      <header
        class="sticky top-0 z-30 flex shrink-0 items-center gap-2 border-b border-slate-200/90 bg-white/95 px-3 py-2.5 shadow-sm backdrop-blur-md dark:border-slate-700 dark:bg-slate-900/95 md:hidden"
      >
        <button
          type="button"
          class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200/90 bg-white text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700"
          :aria-expanded="mobileOpen"
          aria-controls="dept-sidebar"
          @click="mobileOpen = true"
        >
          <Bars3Icon class="h-6 w-6" aria-hidden="true" />
          <span class="sr-only">{{ t('app.sidebar_expand') }}</span>
        </button>

        <RouterLink :to="{ name: 'deptDashboard' }" class="flex min-w-0 flex-1 items-center gap-2" @click="closeMobileDrawer">
          <img
            :src="logoUrl"
            alt=""
            width="32"
            height="32"
            class="h-8 w-8 shrink-0 rounded-full bg-white object-contain p-0.5 ring-2 ring-slate-200 dark:ring-slate-600"
            decoding="async"
          />
          <div class="min-w-0">
            <p class="truncate text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('portal.nav_title') }}
            </p>
          </div>
        </RouterLink>

        <span
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-200 text-[11px] font-bold uppercase text-slate-800 dark:bg-slate-700 dark:text-slate-100"
        >
          {{ userInitials }}
        </span>

        <span
          v-if="summary.pending_count > 0"
          class="inline-flex min-h-[1.25rem] min-w-[1.25rem] shrink-0 items-center justify-center rounded-full bg-[color:var(--va-brand)] px-1.5 text-[11px] font-bold text-white"
        >
          {{ summary.pending_count > 99 ? '99+' : summary.pending_count }}
        </span>
      </header>

      <main
        id="dept-main-scroll"
        :class="[
          'min-h-0 min-w-0 flex-1 overflow-y-auto overflow-x-hidden overscroll-y-contain scrollbar-hidden',
          route.name === 'deptRequestDetail'
            ? 'flex flex-col p-0'
            : 'px-3 py-3 sm:px-4 sm:py-4 md:px-6 md:py-5 lg:px-8 lg:py-6',
        ]"
      >
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, provide, ref, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowRightStartOnRectangleIcon,
  Bars3Icon,
  CheckCircleIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClipboardDocumentListIcon,
  QueueListIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import NotificationBell from '../../components/notifications/NotificationBell.vue'
import { useAuthStore } from '../../store'
import { useAuthLogout } from '../../composables/useAuthLogout'
import { getDeptSummary } from '../../api/requests'

const logoUrl = '/images/logo/logo-2.png'

const { t, te } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const summary = ref({
  pending_count: 0,
  pending_today: 0,
  approved_this_month: 0,
  approval_rate_30d: null,
})

const loggingOut = ref(false)
const sidebarCollapsed = ref(false)
const mobileOpen = ref(false)

/** Desktop rail collapse only — mobile drawer always shows full labels */
const mqMd = ref(false)

function syncMqMd() {
  if (typeof window === 'undefined') return
  mqMd.value = window.matchMedia('(min-width: 768px)').matches
}

const compactNav = computed(() => mqMd.value && sidebarCollapsed.value)

const verticalAsideWidthClass = computed(() => {
  if (sidebarCollapsed.value) {
    return 'w-[min(17.5rem,calc(100vw-3rem))] min-w-[13rem] max-w-[min(17.5rem,calc(100vw-3rem))] sm:w-56 md:w-[4.25rem] md:min-w-[4.25rem] md:max-w-[4.25rem]'
  }
  return 'w-[min(17.5rem,calc(100vw-3rem))] min-w-[13rem] max-w-[min(17.5rem,calc(100vw-3rem))] sm:w-56 md:w-60 lg:w-64'
})

const pageTitle = computed(() => {
  const name = route.name
  if (name && te(`routes_meta.${String(name)}.title`)) return t(`routes_meta.${String(name)}.title`)
  return t('portal.nav_title')
})

const pageSubtitle = computed(() => {
  const name = route.name
  if (name && te(`routes_meta.${String(name)}.subtitle`)) return t(`routes_meta.${String(name)}.subtitle`)
  return ''
})

const deptDisplayName = computed(() => {
  const d = auth.user?.department?.name
  if (d) return d
  const cms = auth.user?.cms_user_info
  if (cms && typeof cms.department_name === 'string' && cms.department_name) return cms.department_name
  return t('dept.dept_fallback')
})

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

function navLinkClass(routeName) {
  const collapsed = compactNav.value
  const on = route.name === routeName
  const layout = collapsed
    ? 'relative justify-center px-2 py-2 gap-0'
    : 'justify-start gap-3 py-2.5 pl-3 pr-3'

  const active = on
    ? 'bg-white/15 font-semibold text-white before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:rounded-r-full before:bg-white before:content-[\'\']'
    : 'text-white/90 hover:bg-white/10'

  return ['relative flex items-center rounded-lg text-sm transition-colors', layout, active].join(' ')
}

function closeMobileDrawer() {
  mobileOpen.value = false
}

async function loadSummary() {
  try {
    const data = await getDeptSummary()
    summary.value = {
      pending_count: data.pending_count ?? 0,
      pending_today: data.pending_today ?? 0,
      approved_this_month: data.approved_this_month ?? 0,
      approval_rate_30d: data.approval_rate_30d ?? null,
    }
  } catch {
    summary.value = {
      pending_count: 0,
      pending_today: 0,
      approved_this_month: 0,
      approval_rate_30d: null,
    }
  }
}

let mqMdListener = null

onMounted(() => {
  syncMqMd()
  loadSummary()
  if (typeof window !== 'undefined') {
    mqMdListener = window.matchMedia('(min-width: 768px)')
    mqMdListener.addEventListener('change', syncMqMd)
  }
})

onUnmounted(() => {
  mqMdListener?.removeEventListener('change', syncMqMd)
})

watch(
  () => route.fullPath,
  () => {
    loadSummary()
    mobileOpen.value = false
  },
)

provide('deptLoadSummary', loadSummary)

const { performLogout } = useAuthLogout()

async function onLogout() {
  loggingOut.value = true
  try {
    await performLogout()
  } finally {
    loggingOut.value = false
  }
}
</script>
