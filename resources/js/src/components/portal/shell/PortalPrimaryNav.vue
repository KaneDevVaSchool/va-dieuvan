<script setup>
import { useI18n } from 'vue-i18n'
import { usePortalPrimaryNav } from '../../../composables/usePortalPrimaryNav'

const { t } = useI18n()
const { items } = usePortalPrimaryNav()
</script>

<template>
  <nav
    class="hidden shrink-0 items-center gap-0.5 md:flex lg:gap-1"
    :aria-label="t('portal.shell.primary_nav_aria')"
    data-testid="portal-primary-nav"
  >
    <RouterLink
      v-for="item in items"
      :key="item.key"
      :to="item.to"
      class="inline-flex min-h-9 max-w-[11rem] items-center justify-center gap-1.5 rounded-lg px-2.5 text-xs font-semibold transition
             focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800
             lg:min-h-10 lg:max-w-none lg:px-3 lg:text-sm"
      :class="
        item.isActive()
          ? item.emphasize
            ? 'bg-va-800 text-white shadow-sm'
            : 'bg-va-50 text-va-900 ring-1 ring-inset ring-va-200'
          : item.emphasize
            ? 'border border-va-600/25 text-va-800 hover:bg-va-50'
            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
      "
      :aria-current="item.isActive() ? 'page' : undefined"
      :data-testid="`portal-primary-nav-${item.key}`"
      :title="item.label"
    >
      <component :is="item.icon" class="h-4 w-4 shrink-0 lg:h-[1.125rem] lg:w-[1.125rem]" aria-hidden="true" />
      <span class="hidden truncate lg:inline">{{ item.label }}</span>
      <span class="truncate lg:hidden">{{ item.shortLabel || item.label }}</span>
    </RouterLink>
  </nav>
</template>
