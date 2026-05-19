<template>
  <div class="flex min-h-dvh bg-[#f6f4f2] text-slate-900 antialiased">
    <aside
      class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-black/10 bg-[#6b1026] text-white shadow-lg"
      style="background: linear-gradient(180deg, #800020 0%, #5c0d1a 100%)"
    >
      <div class="border-b border-white/10 px-4 py-5">
        <RouterLink :to="{ name: 'deptDashboard' }" class="flex items-center gap-3">
          <img
            :src="logoUrl"
            alt=""
            width="44"
            height="44"
            class="h-11 w-11 shrink-0 rounded-full bg-white/90 object-contain p-0.5 ring-2 ring-white/30"
            decoding="async"
          />
          <div class="min-w-0">
            <p class="truncate text-[10px] font-semibold uppercase tracking-wide text-white/70">
              {{ t('app.title') }}
            </p>
            <p class="truncate text-sm font-bold leading-tight">{{ t('portal.nav_title') }}</p>
          </div>
        </RouterLink>
        <div class="mt-4 flex items-center gap-3 rounded-xl bg-white/10 px-3 py-2.5">
          <span
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/20 text-xs font-bold uppercase text-white ring-2 ring-white/25"
          >
            {{ userInitials }}
          </span>
          <div class="min-w-0 text-xs leading-snug">
            <p class="truncate font-semibold">{{ auth.user?.name || '—' }}</p>
            <p class="truncate text-white/75">{{ deptDisplayName }}</p>
          </div>
        </div>
      </div>

      <nav class="min-h-0 flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="Dept nav">
        <div>
          <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-wider text-white/45">
            {{ t('dept.nav_section_overview') }}
          </p>
          <RouterLink
            :to="{ name: 'deptDashboard' }"
            class="flex items-center gap-2 rounded-lg px-2 py-2.5 text-sm font-semibold transition"
            :class="navClass('deptDashboard')"
          >
            <HomeModernIcon class="h-5 w-5 shrink-0 opacity-90" aria-hidden="true" />
            {{ t('dept.nav_dashboard') }}
          </RouterLink>
        </div>

        <div>
          <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-wider text-white/45">
            {{ t('dept.nav_section_approval') }}
          </p>
          <RouterLink
            :to="{ name: 'deptDashboard' }"
            class="relative flex items-center gap-2 rounded-lg px-2 py-2.5 text-sm font-semibold transition"
            :class="navClass('deptDashboard')"
          >
            <span
              class="absolute left-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-full bg-white"
              :class="route.name === 'deptDashboard' ? 'opacity-100' : 'opacity-0'"
              aria-hidden="true"
            />
            <span class="pl-1">{{ t('dept.nav_pending') }}</span>
            <span
              v-if="summary.pending_count > 0"
              class="ml-auto flex min-h-[1.25rem] min-w-[1.25rem] items-center justify-center rounded-full bg-white px-1.5 text-xs font-bold text-[#800020]"
            >
              {{ summary.pending_count > 99 ? '99+' : summary.pending_count }}
            </span>
          </RouterLink>
          <RouterLink
            :to="{ name: 'deptApproved' }"
            class="mt-0.5 flex items-center gap-2 rounded-lg px-2 py-2.5 pl-3 text-sm font-semibold transition"
            :class="navClass('deptApproved')"
          >
            <CheckCircleIcon class="h-5 w-5 shrink-0 opacity-90" aria-hidden="true" />
            {{ t('dept.nav_approved') }}
          </RouterLink>
          <RouterLink
            :to="{ name: 'deptRejected' }"
            class="mt-0.5 flex items-center gap-2 rounded-lg px-2 py-2.5 pl-3 text-sm font-semibold transition"
            :class="navClass('deptRejected')"
          >
            <XCircleIcon class="h-5 w-5 shrink-0 opacity-90" aria-hidden="true" />
            {{ t('dept.nav_rejected') }}
          </RouterLink>
        </div>

        <div>
          <p class="mb-2 px-2 text-[10px] font-bold uppercase tracking-wider text-white/45">
            {{ t('dept.nav_section_track') }}
          </p>
          <RouterLink
            :to="{ name: 'deptAll' }"
            class="flex items-center gap-2 rounded-lg px-2 py-2.5 text-sm font-semibold transition"
            :class="navClass('deptAll')"
          >
            <ListBulletIcon class="h-5 w-5 shrink-0 opacity-90" aria-hidden="true" />
            {{ t('dept.nav_all') }}
          </RouterLink>
          <RouterLink
            :to="reportsHref"
            class="mt-0.5 flex items-center gap-2 rounded-lg px-2 py-2.5 text-sm font-semibold transition"
            :class="navReportsClass"
          >
            <ChartBarIcon class="h-5 w-5 shrink-0 opacity-90" aria-hidden="true" />
            {{ t('dept.nav_reports') }}
          </RouterLink>
        </div>
      </nav>

      <div class="border-t border-white/10 p-3">
        <p class="rounded-lg bg-black/15 px-3 py-2 text-center text-xs font-semibold text-white/90">
          {{ t('dept.role_badge') }}
        </p>
        <button
          type="button"
          class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15"
          :disabled="loggingOut"
          @click="onLogout"
        >
          <ArrowRightStartOnRectangleIcon class="h-4 w-4" aria-hidden="true" />
          {{ t('portal.logout') }}
        </button>
      </div>
    </aside>

    <div class="min-h-dvh flex-1 pl-64">
      <RouterView />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, provide, ref, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowRightStartOnRectangleIcon,
  ChartBarIcon,
  CheckCircleIcon,
  HomeIcon as HomeModernIcon,
  ListBulletIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../store'
import { getDeptSummary } from '../../api/requests'
import { DISPATCH_WEB_BASE } from '../../config/dispatchWebBase'

const logoUrl = '/images/logo/logo-2.png'

const { t } = useI18n()
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

const reportsHref = computed(() => `${DISPATCH_WEB_BASE}/reports`)

function navClass(name) {
  const on = route.name === name
  return on ? 'bg-white/15 text-white shadow-inner shadow-black/10' : 'text-white/85 hover:bg-white/10'
}

const navReportsClass = computed(() => {
  const p = route.path
  const on = p === `${DISPATCH_WEB_BASE}/reports` || p.startsWith(`${DISPATCH_WEB_BASE}/reports/`)
  return on ? 'bg-white/15 text-white shadow-inner shadow-black/10' : 'text-white/85 hover:bg-white/10'
})

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

onMounted(loadSummary)
watch(
  () => route.fullPath,
  () => {
    loadSummary()
  },
)

provide('deptLoadSummary', loadSummary)

async function onLogout() {
  loggingOut.value = true
  try {
    await auth.logout()
    await router.replace({ name: 'login' })
  } finally {
    loggingOut.value = false
  }
}
</script>
