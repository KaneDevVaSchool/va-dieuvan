<script setup>
import { useI18n } from 'vue-i18n'
import { usePortalPrimaryNav } from '../../../composables/usePortalPrimaryNav'

const { t } = useI18n()
const { items } = usePortalPrimaryNav()
</script>

<template>
  <nav
    class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200/90 bg-white/95 backdrop-blur-md md:hidden
           pb-[env(safe-area-inset-bottom)]"
    :aria-label="t('portal.shell.bottom_nav_aria')"
    data-testid="portal-bottom-nav"
  >
    <ul class="mx-auto flex max-w-lg items-stretch justify-around gap-0 px-0.5 pt-0.5">
      <li v-for="item in items" :key="item.key" class="min-w-0 flex-1">
        <RouterLink
          :to="item.to"
          class="relative flex min-h-[56px] flex-col items-center justify-center gap-0.5 rounded-xl px-0.5 pb-1 pt-1.5 text-[10px] font-semibold transition
                 active:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800"
          :class="item.isActive() ? 'text-va-800' : 'text-slate-500'"
          :data-testid="`portal-bottom-nav-${item.key}`"
          :aria-current="item.isActive() ? 'page' : undefined"
          :title="item.label"
        >
          <span
            v-if="item.isActive()"
            class="absolute inset-x-2 top-0 h-0.5 rounded-full bg-va-800"
            aria-hidden="true"
          />
          <span
            class="flex h-9 w-9 items-center justify-center rounded-full transition"
            :class="
              item.emphasize
                ? item.isActive()
                  ? 'bg-va-800 text-white shadow-md shadow-va-900/20'
                  : 'bg-va-800 text-white shadow-sm ring-2 ring-va-100'
                : item.isActive()
                  ? 'bg-va-50 text-va-800'
                  : ''
            "
          >
            <component :is="item.icon" class="h-5 w-5 shrink-0" aria-hidden="true" />
          </span>
          <span class="max-w-full truncate leading-tight px-0.5">
            {{ item.shortLabel || item.label }}
          </span>
        </RouterLink>
      </li>
    </ul>
  </nav>
</template>
