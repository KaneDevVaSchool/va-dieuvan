<template>
  <nav
    class="flex w-full items-stretch border-t border-slate-200/90 bg-white/95 shadow-[0_-4px_20px_-4px_rgba(15,23,42,0.08)] backdrop-blur-md dark:border-slate-700 dark:bg-slate-900/95 dark:shadow-[0_-4px_24px_-4px_rgba(0,0,0,0.35)]"
    :aria-label="t('app.title')"
  >
    <SidebarNavItem
      v-for="item in bottomNavItems"
      :key="item.to"
      :to="item.to"
      :label="t(item.labelKey)"
      :icon="item.icon"
      :badge-count="badgeCount(item)"
      variant="bottom"
    />
  </nav>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import SidebarNavItem from './SidebarNavItem.vue'
import { useNavSections } from '../../composables/useNavSections'

const { t } = useI18n()
const { bottomNavItems, badgeCount } = useNavSections()
</script>
