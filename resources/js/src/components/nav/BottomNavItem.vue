<template>
  <RouterLink :to="to" :class="linkClass">
    <component :is="IconComp" class="h-5 w-5 shrink-0 opacity-80" aria-hidden="true" />
    <span class="max-w-[4.5rem] truncate text-center font-medium leading-tight">{{ label }}</span>
  </RouterLink>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { NAV_ICON_MAP } from '../../config/navIconMap'
import { isNavRouteActive } from '../../util/isNavRouteActive'

const props = defineProps({
  to: { type: String, required: true },
  label: { type: String, required: true },
  icon: { type: String, default: 'home' },
})

const route = useRoute()
const IconComp = computed(() => NAV_ICON_MAP[props.icon] ?? NAV_ICON_MAP.home)

const isActive = computed(() => isNavRouteActive(props.to, route.path))

const linkClass = computed(() => [
  'flex flex-col items-center justify-center gap-0.5 px-0.5 py-2 text-[10px] leading-tight sm:text-[11px]',
  isActive.value
    ? 'rounded-lg bg-va-50 font-semibold text-va-900 dark:bg-va-950/50 dark:text-va-100'
    : 'text-slate-500 dark:text-slate-400',
])
</script>
