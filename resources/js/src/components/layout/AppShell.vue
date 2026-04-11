<template>
  <div class="min-h-dvh bg-slate-50 text-slate-900">
    <div class="mx-auto flex min-h-dvh w-full max-w-7xl">
      <aside
        class="hidden w-72 shrink-0 border-r border-slate-200/80 bg-gradient-to-b from-white via-white to-slate-50/90 md:flex md:flex-col md:shadow-[inset_-1px_0_0_0_rgba(15,23,42,0.04)]"
      >
        <div class="shrink-0 border-b border-slate-100 bg-white/90 px-5 py-4 backdrop-blur-sm">
          <AppLogo size="md" />
          <div class="mt-3 text-xs font-semibold tracking-tight text-va-900">{{ t('app.title') }}</div>
          <div class="mt-0.5 text-[11px] leading-snug text-slate-500">Vehicle Dispatching · VA Schools</div>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-2 py-3">
          <nav class="space-y-0.5">
            <template v-for="(section, si) in desktopSections" :key="si">
              <div
                v-if="section.headingKey"
                class="mb-1 mt-4 flex items-center gap-2 px-3 first:mt-0"
              >
                <span
                  class="h-1 w-1 shrink-0 rounded-full bg-va-700/70"
                  aria-hidden="true"
                />
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                  {{ t(section.headingKey) }}
                </span>
              </div>
              <NavItem
                v-for="item in section.items"
                :key="item.to"
                :to="item.to"
                :label="t(item.labelKey)"
              />
            </template>
          </nav>
        </div>

        <div class="shrink-0 border-t border-slate-200/80 bg-white/95 p-4 backdrop-blur-sm">
          <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ t('app.account') }}</div>
          <div class="mt-3 flex items-start gap-3">
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-va-50 text-xs font-bold text-va-800 ring-1 ring-va-800/10"
              aria-hidden="true"
            >
              {{ userInitials }}
            </div>
            <div class="min-w-0 flex-1">
              <div class="truncate text-sm font-semibold text-slate-900">
                {{ auth.user?.name ?? '—' }}
              </div>
              <div class="truncate text-xs text-slate-500">
                {{ auth.user?.email ?? '' }}
              </div>
              <div v-if="auth.roleNames?.length" class="mt-1 line-clamp-2 text-[11px] text-slate-400">
                {{ auth.roleNames.join(', ') }}
              </div>
            </div>
          </div>
          <label class="mt-4 block text-[11px] font-medium text-slate-500">{{ t('app.lang') }}</label>
          <select
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-2 py-2 text-sm shadow-sm focus:border-va-300 focus:outline-none focus:ring-2 focus:ring-va-800/15"
            :value="locale"
            @change="onLocale($event.target.value)"
          >
            <option value="vi">Tiếng Việt</option>
            <option value="en">English</option>
          </select>
          <RouterLink
            class="mt-2 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-center text-sm font-medium text-slate-800 shadow-sm transition hover:border-va-200 hover:bg-va-50/50"
            to="/profile"
          >
            {{ t('app.profile') }}
          </RouterLink>
          <button
            type="button"
            class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
            @click="logout"
          >
            {{ t('app.logout') }}
          </button>
        </div>
      </aside>

      <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-10 border-b bg-white/80 backdrop-blur">
          <div class="flex items-center justify-between gap-3 px-4 py-3 md:px-6">
            <div class="flex items-center gap-3">
              <div class="md:hidden">
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-md border px-3 py-2 text-sm"
                  @click="mobileOpen = true"
                >
                  {{ t('app.menu') }}
                </button>
              </div>
              <AppLogo class="hidden shrink-0 sm:block md:hidden" size="sm" />
              <div class="min-w-0">
                <div class="truncate text-sm font-semibold">{{ title }}</div>
                <div class="truncate text-xs text-slate-500">{{ subtitle }}</div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <select
                class="rounded-md border bg-white px-2 py-1.5 text-xs md:hidden"
                :value="locale"
                @change="onLocale($event.target.value)"
              >
                <option value="vi">VI</option>
                <option value="en">EN</option>
              </select>
              <RouterLink
                v-if="auth.hasPermission('request.create')"
                class="rounded-md border px-3 py-2 text-sm hover:bg-slate-50"
                to="/dispatch-requests/new"
              >
                {{ t('app.create_request') }}
              </RouterLink>
              <button
                type="button"
                class="hidden rounded-md border px-3 py-2 text-sm hover:bg-slate-50 md:inline"
                @click="logout"
              >
                {{ t('app.logout') }}
              </button>
            </div>
          </div>
        </header>

        <main class="flex-1 px-4 py-5 pb-2 md:px-6 md:pb-5">
          <slot />
        </main>

        <nav
          class="sticky bottom-0 z-10 border-t bg-white pb-[max(0.25rem,env(safe-area-inset-bottom))] pt-0.5 md:hidden"
        >
          <div class="grid grid-cols-5">
            <BottomItem v-for="item in bottomItems" :key="item.to" :to="item.to" :label="t(item.labelKey)" />
          </div>
        </nav>
      </div>
    </div>

    <div v-if="mobileOpen" class="fixed inset-0 z-50 md:hidden" @click="mobileOpen = false">
      <div class="absolute inset-0 z-0 bg-black/40" />
      <div
        class="absolute inset-y-0 left-0 z-10 flex w-80 max-w-[85vw] flex-col border-r border-slate-200/80 bg-gradient-to-b from-white to-slate-50/90 shadow-xl"
        @click.stop
      >
        <div class="flex items-start justify-between gap-2 border-b border-slate-100 px-5 py-4">
          <div class="min-w-0">
            <AppLogo size="lg" />
            <div class="mt-2 text-xs font-semibold text-va-900">{{ t('app.title') }}</div>
            <div class="mt-0.5 text-[11px] text-slate-500">{{ t('app.subtitle') }}</div>
          </div>
          <button
            type="button"
            class="shrink-0 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50"
            @click="mobileOpen = false"
          >
            {{ t('app.close') }}
          </button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-2 py-3">
          <nav class="space-y-0.5">
            <template v-for="(section, si) in desktopSections" :key="'m'+si">
              <div
                v-if="section.headingKey"
                class="mb-1 mt-4 flex items-center gap-2 px-3 first:mt-0"
              >
                <span class="h-1 w-1 shrink-0 rounded-full bg-va-700/70" aria-hidden="true" />
                <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                  {{ t(section.headingKey) }}
                </span>
              </div>
              <NavItem
                v-for="item in section.items"
                :key="'m'+item.to"
                :to="item.to"
                :label="t(item.labelKey)"
                @click="mobileOpen = false"
              />
            </template>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, defineComponent, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../store'
import { NAV_SECTIONS, BOTTOM_NAV } from '../../config/nav'
import { setLocale } from '../../i18n'
import AppLogo from '../branding/AppLogo.vue'

const { t, locale } = useI18n()
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const mobileOpen = ref(false)

function onLocale(v) {
  setLocale(v)
}

onMounted(async () => {
  try {
    if (auth.isLoggedIn && !auth.user) {
      await auth.fetchMe()
    }
  } catch {
    /* router guard handles */
  }
})

async function logout() {
  await auth.logout()
  await router.push({ name: 'login' })
}

const title = computed(() => route.meta?.title ?? t('app.title'))
const subtitle = computed(() => route.meta?.subtitle ?? '')

function filterNavItems(items) {
  return items.filter((i) => auth.hasAnyPermission(i.perms))
}

const desktopSections = computed(() => {
  return NAV_SECTIONS.map((sec) => ({
    headingKey: sec.headingKey,
    items: filterNavItems(sec.items),
  })).filter((sec) => sec.items.length > 0)
})

const bottomItems = computed(() => {
  const x = BOTTOM_NAV.filter((i) => auth.hasAnyPermission(i.perms))
  if (x.length > 0) return x
  return BOTTOM_NAV.filter((i) => !i.perms?.length)
})

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

const NavItem = defineComponent({
  name: 'NavItem',
  components: { RouterLink },
  props: {
    to: { type: String, required: true },
    label: { type: String, required: true },
  },
  emits: ['click'],
  setup(props, { emit }) {
    const r = useRoute()
    const isActive = computed(() => {
      if (props.to === '/') {
        return r.path === '/' || r.path === ''
      }
      return r.path === props.to || r.path.startsWith(`${props.to}/`)
    })
    const klass = computed(() => [
      'flex items-center gap-2 rounded-lg border-l-[3px] py-2.5 pl-3 pr-3 text-sm transition-colors',
      isActive.value
        ? 'border-va-800 bg-va-50 font-semibold text-va-900 shadow-sm shadow-va-900/5'
        : 'border-transparent text-slate-700 hover:border-slate-200 hover:bg-slate-50/90',
    ])
    const onClick = () => emit('click')
    return { klass, onClick }
  },
  template: `
    <RouterLink :to="to" :class="klass" @click="onClick">
      <span>{{ label }}</span>
    </RouterLink>
  `,
})

const BottomItem = defineComponent({
  name: 'BottomItem',
  components: { RouterLink },
  props: {
    to: { type: String, required: true },
    label: { type: String, required: true },
  },
  setup(props) {
    const r = useRoute()
    const isActive = computed(() => {
      if (props.to === '/') {
        return r.path === '/' || r.path === ''
      }
      return r.path === props.to || r.path.startsWith(`${props.to}/`)
    })
    const klass = computed(() => [
      'flex flex-col items-center justify-center gap-0.5 px-1 py-2 text-[10px] leading-tight sm:text-[11px]',
      isActive.value ? 'rounded-lg bg-va-50 font-semibold text-va-900' : 'text-slate-500',
    ])
    return { klass }
  },
  template: `
    <RouterLink :to="to" :class="klass">
      <span class="font-medium">{{ label }}</span>
    </RouterLink>
  `,
})
</script>
