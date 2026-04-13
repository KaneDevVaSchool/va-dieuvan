<template>
  <div
    class="flex h-dvh min-h-0 w-full overflow-hidden bg-slate-50 text-slate-900 supports-[padding:max(0px)]:pt-[env(safe-area-inset-top)] supports-[padding:max(0px)]:pb-[env(safe-area-inset-bottom)]"
  >
    <aside
      class="flex w-[4.25rem] shrink-0 flex-col border-r border-slate-200/80 bg-gradient-to-b from-white via-white to-slate-50/90 shadow-[inset_-1px_0_0_0_rgba(15,23,42,0.04)] sm:w-14 md:w-[15.5rem] lg:w-60"
    >
      <div
        class="shrink-0 border-b border-slate-100 bg-white/90 px-2 py-2.5 backdrop-blur-sm md:px-4 md:py-3"
      >
        <div class="flex items-center justify-center md:justify-start md:gap-2.5">
          <AppLogo class="shrink-0 scale-90 md:scale-100" size="sm" />
          <div class="hidden min-w-0 md:block">
            <div class="truncate text-xs font-semibold tracking-tight text-va-900">{{ t('app.title') }}</div>
            <div class="mt-0.5 hidden text-[11px] leading-snug text-slate-500 lg:block">
              Vehicle Dispatching · VA Schools
            </div>
          </div>
        </div>
      </div>

      <div class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-1 py-2 md:px-2 md:py-2.5">
        <nav class="space-y-0.5">
          <template v-for="(section, si) in desktopSections" :key="si">
            <div
              v-if="section.headingKey"
              class="mb-1 mt-3 hidden items-center gap-2 px-2 first:mt-0 md:flex"
            >
              <span class="h-1 w-1 shrink-0 rounded-full bg-va-700/70" aria-hidden="true" />
              <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-500 lg:text-[11px]">
                {{ t(section.headingKey) }}
              </span>
            </div>
            <NavItem
              v-for="item in section.items"
              :key="item.to"
              :to="item.to"
              :label="t(item.labelKey)"
              :icon-key="item.icon"
            />
          </template>
        </nav>
      </div>

      <div
        class="shrink-0 border-t border-slate-200/80 bg-white/95 p-2 backdrop-blur-sm md:p-3"
      >
        <div class="hidden text-[10px] font-medium uppercase tracking-wide text-slate-400 md:block">
          {{ t('app.account') }}
        </div>
        <div class="mt-0 flex items-center gap-2 md:mt-2 md:gap-3">
          <div
            class="mx-auto flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-va-50 text-[11px] font-bold text-va-800 ring-1 ring-va-800/10 md:h-10 md:w-10 md:rounded-xl md:text-xs"
            :title="auth.user?.name ?? ''"
            aria-hidden="true"
          >
            {{ userInitials }}
          </div>
          <div class="hidden min-w-0 flex-1 md:block">
            <div class="truncate text-sm font-semibold text-slate-900">
              {{ auth.user?.name ?? '—' }}
            </div>
            <div class="truncate text-xs text-slate-500">
              {{ auth.user?.email ?? '' }}
            </div>
            <div v-if="auth.roleNames?.length" class="mt-0.5 line-clamp-2 text-[10px] text-slate-400 lg:text-[11px]">
              {{ auth.roleNames.join(', ') }}
            </div>
          </div>
        </div>
        <label class="mt-2 hidden text-[11px] font-medium text-slate-500 md:block">{{ t('app.lang') }}</label>
        <select
          class="mt-1.5 hidden w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs shadow-sm focus:border-va-300 focus:outline-none focus:ring-2 focus:ring-va-800/15 md:block"
          :value="locale"
          @change="onLocale($event.target.value)"
        >
          <option value="vi">Tiếng Việt</option>
          <option value="en">English</option>
        </select>
        <div class="mt-2 flex gap-1 md:hidden">
          <select
            class="min-w-0 flex-1 rounded-md border border-slate-200 bg-white px-1 py-1.5 text-[10px] shadow-sm"
            :value="locale"
            :aria-label="t('app.lang')"
            @change="onLocale($event.target.value)"
          >
            <option value="vi">VI</option>
            <option value="en">EN</option>
          </select>
        </div>
        <RouterLink
          class="mt-2 flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-2 py-2 text-center text-[11px] font-medium text-slate-800 shadow-sm transition hover:border-va-200 hover:bg-va-50/50 md:block md:py-2.5 md:text-sm"
          to="/profile"
          :aria-label="t('app.profile')"
        >
          <UserCircleIcon class="h-5 w-5 shrink-0 md:hidden" aria-hidden="true" />
          <span class="sr-only md:not-sr-only md:inline">{{ t('app.profile') }}</span>
        </RouterLink>
        <button
          type="button"
          class="mt-1.5 w-full rounded-lg border border-slate-200 px-2 py-2 text-[11px] font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 md:mt-2 md:py-2.5 md:text-sm"
          @click="logout"
        >
          {{ t('app.logout') }}
        </button>
      </div>
    </aside>

    <main
      class="min-h-0 min-w-0 flex-1 overflow-y-auto overflow-x-hidden overscroll-y-contain px-3 py-3 sm:px-4 sm:py-4 md:px-6 md:py-5"
    >
      <slot />
    </main>
  </div>
</template>

<script setup>
import { computed, defineComponent, onMounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '../../store'
import { NAV_SECTIONS } from '../../config/nav'
import { NAV_ICON_MAP } from '../../config/navIconMap'
import { setLocale } from '../../i18n'
import AppLogo from '../branding/AppLogo.vue'
import { UserCircleIcon } from '@heroicons/vue/24/outline'

const { t, locale } = useI18n()
const auth = useAuthStore()
const router = useRouter()

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

function filterNavItems(items) {
  return items.filter((i) => auth.hasAnyPermission(i.perms))
}

const desktopSections = computed(() => {
  return NAV_SECTIONS.map((sec) => ({
    headingKey: sec.headingKey,
    items: filterNavItems(sec.items),
  })).filter((sec) => sec.items.length > 0)
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
    iconKey: { type: String, default: '' },
  },
  setup(props) {
    const r = useRoute()
    const Icon = computed(() => {
      const k = props.iconKey
      return k && NAV_ICON_MAP[k] ? NAV_ICON_MAP[k] : null
    })
    const isActive = computed(() => {
      if (props.to === '/') {
        return r.path === '/' || r.path === ''
      }
      return r.path === props.to || r.path.startsWith(`${props.to}/`)
    })
    const klass = computed(() => [
      'group flex items-center justify-center gap-0 rounded-lg border-l-[3px] py-2 md:justify-start md:gap-2 md:py-2 md:pl-2.5 md:pr-2 text-sm transition-colors',
      isActive.value
        ? 'border-va-800 bg-va-50 font-semibold text-va-900 shadow-sm shadow-va-900/5'
        : 'border-transparent text-slate-700 hover:border-slate-200 hover:bg-slate-50/90',
    ])
    return { Icon, klass }
  },
  template: `
    <RouterLink
      :to="to"
      :class="klass"
      :title="label"
    >
      <component
        v-if="Icon"
        :is="Icon"
        class="h-[1.15rem] w-[1.15rem] shrink-0 text-current md:h-5 md:w-5"
        aria-hidden="true"
      />
      <span class="hidden min-w-0 flex-1 truncate md:inline">{{ label }}</span>
    </RouterLink>
  `,
})
</script>
