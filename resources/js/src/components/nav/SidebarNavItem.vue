<template>
  <RouterLink
    :to="to"
    :class="linkClass"
    :title="linkTitle"
    @click="$emit('navigate')"
  >
    <component
      :is="IconComp"
      :class="iconClass"
      aria-hidden="true"
    />
    <span v-if="showLabel" :class="labelClass">{{ label }}</span>
    <span
      v-if="badgeCount > 0"
      :class="badgeClass"
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
  /** Tooltip (vd. nhãn đầy đủ khi `label` rút gọn trên thanh ngang) */
  fullLabel: { type: String, default: null },
  icon: { type: String, default: 'home' },
  badgeCount: { type: Number, default: 0 },
  /** vertical-full | vertical-compact | horizontal | bottom */
  variant: {
    type: String,
    default: 'vertical-full',
    validator: (v) => ['vertical-full', 'vertical-compact', 'horizontal', 'bottom'].includes(v),
  },
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

const showLabel = computed(() => props.variant !== 'vertical-compact')
const showTitle = computed(
  () =>
    props.variant === 'vertical-compact' ||
    props.variant === 'horizontal' ||
    props.variant === 'bottom',
)

const linkTitle = computed(() => {
  if (!showTitle.value) return undefined
  return props.fullLabel || props.label
})

const iconClass = computed(() => {
  if (props.variant === 'bottom') {
    return 'h-6 w-6 shrink-0 text-current opacity-90'
  }
  if (props.variant === 'horizontal') {
    return 'h-4 w-4 shrink-0 text-current opacity-90 sm:h-[1.125rem] sm:w-[1.125rem]'
  }
  if (props.variant === 'vertical-compact') {
    return 'h-[1.15rem] w-[1.15rem] shrink-0 text-current opacity-90 sm:h-5 sm:w-5'
  }
  return 'h-5 w-5 shrink-0 text-current opacity-90'
})

const labelClass = computed(() => {
  if (props.variant === 'bottom') {
    return 'line-clamp-2 min-w-0 max-w-full text-center text-[10px] font-medium leading-tight'
  }
  if (props.variant === 'horizontal') {
    return 'min-w-0 max-w-[3.75rem] truncate whitespace-nowrap text-center text-[9px] font-medium leading-none sm:max-w-[4.25rem] sm:text-[10px]'
  }
  return 'min-w-0 flex-1 truncate'
})

const badgeClass = computed(() => {
  const base = [
    'inline-flex min-w-[1.25rem] shrink-0 items-center justify-center rounded-full px-1 text-[10px] font-bold leading-none',
  ]
  if (props.variant === 'vertical-compact') {
    base.push('absolute -right-0.5 -top-0.5 h-4 min-w-[1rem] px-0.5')
  } else if (props.variant === 'horizontal') {
    base.push('absolute -right-1 -top-1 h-3.5 min-w-[0.875rem] px-0.5 text-[9px]')
  } else if (props.variant === 'bottom') {
    base.push('absolute -right-0.5 top-0.5 h-4 min-w-[1rem] px-0.5 text-[9px]')
  }
  if (isActive.value) {
    base.push('bg-va-800 text-white dark:bg-va-500')
  } else {
    base.push('bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-100')
  }
  return base.join(' ')
})

const linkClass = computed(() => {
  const v = props.variant
  if (v === 'bottom') {
    const base = [
      'relative flex min-h-[3.25rem] min-w-0 flex-1 flex-col items-center justify-center gap-0.5 border-t-2 border-transparent px-0.5 py-1 transition-colors',
      'text-slate-600 dark:text-slate-300',
    ]
    if (isActive.value) {
      base.push(
        'border-va-800 bg-va-50/95 font-semibold text-va-900 dark:border-va-500 dark:bg-va-950/50 dark:text-va-100',
      )
    } else {
      base.push('active:bg-slate-100 dark:active:bg-slate-800/80')
    }
    return base.join(' ')
  }
  if (v === 'horizontal') {
    const base = [
      'relative flex shrink-0 snap-start flex-col items-center justify-center gap-0.5 rounded-md border-b-2 px-1 py-1 transition-colors sm:px-1.5 sm:py-1.5',
      'min-w-[2.5rem] max-w-[4.5rem] border-transparent text-slate-600 dark:text-slate-300',
    ]
    if (isActive.value) {
      base.push(
        'border-va-800 bg-va-50 font-semibold text-va-900 shadow-sm shadow-va-900/5 dark:border-va-500 dark:bg-va-950/40 dark:text-va-100',
      )
    } else {
      base.push(
        'hover:border-slate-200 hover:bg-slate-50/90 dark:hover:border-slate-600 dark:hover:bg-slate-800/80',
      )
    }
    return base.join(' ')
  }

  const base = [
    'relative flex items-center gap-3 rounded-lg border-l-[3px] text-sm transition-colors',
  ]
  if (v === 'vertical-compact') {
    base.push('justify-center px-2 py-2')
  } else {
    base.push('py-2.5 pl-3 pr-3')
  }

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
