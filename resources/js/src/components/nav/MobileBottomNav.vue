<template>
  <nav
    :class="
      isDriverShell
        ? 'flex w-full items-stretch border-t border-[#7fdcc8]/15 bg-[#080f0d] shadow-[0_-8px_32px_rgba(0,0,0,0.45)]'
        : 'flex w-full items-stretch border-t border-slate-200/90 bg-white/95 shadow-[0_-4px_20px_-4px_rgba(15,23,42,0.08)] backdrop-blur-md dark:border-slate-700 dark:bg-slate-900/95 dark:shadow-[0_-4px_24px_-4px_rgba(0,0,0,0.35)]'
    "
    :aria-label="t('app.title')"
  >
    <SidebarNavItem
      v-for="item in bottomNavItems"
      :key="item.to"
      :to="item.to"
      :label="t(item.labelKey)"
      :full-label="item.fullLabelKey ? t(item.fullLabelKey) : undefined"
      :icon="item.icon"
      :badge-count="badgeCount(item)"
      variant="bottom"
      :driver-bottom-nav="isDriverShell"
    />
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import SidebarNavItem from './SidebarNavItem.vue'
import { useNavSections } from '../../composables/useNavSections'

const { t } = useI18n()
const route = useRoute()
const { bottomNavItems, badgeCount } = useNavSections()

const isDriverShell = computed(() => !!route.meta?.driverApp)
</script>
