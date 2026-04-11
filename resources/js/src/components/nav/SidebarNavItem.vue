<template>
  <RouterLink
    :to="to"
    :class="linkClass"
    :title="collapsed ? label : undefined"
    @click="$emit('navigate')"
  >
    <component
      :is="IconComp"
      v-if="IconComp"
      class="h-5 w-5 shrink-0 text-current opacity-90"
      aria-hidden="true"
    />
    <span v-if="!collapsed" class="min-w-0 flex-1 truncate">{{ label }}</span>
    <span
      v-if="badgeCount > 0"
      :class="[
        'inline-flex min-w-[1.25rem] shrink-0 items-center justify-center rounded-full px-1 text-[10px] font-bold leading-none',
        collapsed ? 'absolute -right-0.5 -top-0.5 h-4 min-w-[1rem] px-0.5' : '',
        isActive ? 'bg-va-800 text-white' : 'bg-rose-100 text-rose-800',
      ]"
    >
      {{ badgeCount > 99 ? '99+' : badgeCount }}
    </span>
  </RouterLink>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { NAV_ICON_MAP } from '../../config/navIconMap'

const props = defineProps({
  to: { type: String, required: true },
  label: { type: String, required: true },
  icon: { type: String, default: 'home' },
  badgeCount: { type: Number, default: 0 },
  collapsed: { type: Boolean, default: false },
})

defineEmits(['navigate'])

const route = useRoute()

const IconComp = computed(() => NAV_ICON_MAP[props.icon] ?? NAV_ICON_MAP.home)

const isActive = computed(() => {
  if (props.to === '/') {
    return route.path === '/' || route.path === ''
  }
  return route.path === props.to || route.path.startsWith(`${props.to}/`)
})

const linkClass = computed(() => {
  const base = [
    'relative flex items-center gap-3 rounded-lg border-l-[3px] py-2.5 text-sm transition-colors',
    props.collapsed ? 'justify-center px-2' : 'pl-3 pr-3',
  ]
  if (isActive.value) {
    base.push(
      'border-va-800 bg-va-50 font-semibold text-va-900 shadow-sm shadow-va-900/5 dark:border-va-500 dark:bg-va-950/40 dark:text-va-100',
    )
  } else {
    base.push(
      'border-transparent text-slate-700 hover:border-slate-200 hover:bg-slate-50/90 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-800/80',
    )
  }
  return base.join(' ')
})
</script>
