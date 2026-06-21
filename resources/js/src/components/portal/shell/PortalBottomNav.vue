<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  AcademicCapIcon,
  HomeIcon,
  ListBulletIcon,
  PlusCircleIcon,
} from '@heroicons/vue/24/outline'
import { usePortalExtracurricularModule } from '../../../composables/usePortalExtracurricularModule'

const { t } = useI18n()
const route = useRoute()
const { routes: portalRoutes } = usePortalExtracurricularModule()

const items = computed(() => [
  {
    key: 'home',
    label: t('portal.nav_home'),
    icon: HomeIcon,
    to: { name: portalRoutes.value.home },
    match: (n) => n === 'portalHome' || n === 'portalExtracurricularHome',
  },
  {
    key: 'list',
    label: t('portal.nav_list'),
    icon: ListBulletIcon,
    to: { name: portalRoutes.value.list },
    match: (n) => n === 'portalRequestList' || n === 'portalExtracurricularList',
  },
  {
    key: 'recurring',
    label: t('portal.shell.bottom_recurring'),
    icon: AcademicCapIcon,
    to: { name: 'portalExtracurricularList' },
    match: (n) =>
      n === 'portalExtracurricularHome' ||
      n === 'portalExtracurricularList' ||
      n === 'portalExtracurricularCreate',
  },
  {
    key: 'create',
    label: t('portal.shell.bottom_create'),
    icon: PlusCircleIcon,
    to: { name: portalRoutes.value.create },
    match: (n) => n === 'portalCreate' || n === 'portalExtracurricularCreate',
    primary: true,
  },
])

function isActive(item) {
  return item.match(route.name)
}
</script>

<template>
  <nav
    class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200/90 bg-white/95 backdrop-blur-md md:hidden
           pb-[env(safe-area-inset-bottom)]"
    :aria-label="t('portal.shell.bottom_nav_aria')"
    data-testid="portal-bottom-nav"
  >
    <ul class="mx-auto flex max-w-lg items-stretch justify-around gap-0 px-1 pt-1">
      <li v-for="item in items" :key="item.key" class="min-w-0 flex-1">
        <RouterLink
          :to="item.to"
          class="flex min-h-[52px] flex-col items-center justify-center gap-0.5 rounded-lg px-1 text-[10px] font-semibold transition
                 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800"
          :class="
            isActive(item)
              ? item.primary
                ? 'text-va-800'
                : 'text-va-800'
              : 'text-slate-500 hover:text-slate-800'
          "
          :data-testid="`portal-bottom-nav-${item.key}`"
          :aria-current="isActive(item) ? 'page' : undefined"
        >
          <span
            class="flex h-8 w-8 items-center justify-center rounded-full transition"
            :class="
              item.primary
                ? isActive(item)
                  ? 'bg-va-800 text-white shadow-sm'
                  : 'bg-va-800 text-white shadow-sm ring-2 ring-va-100'
                : isActive(item)
                  ? 'bg-va-50 text-va-800'
                  : ''
            "
          >
            <component :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
          </span>
          <span class="max-w-full truncate leading-tight">{{ item.label }}</span>
        </RouterLink>
      </li>
    </ul>
  </nav>
</template>
