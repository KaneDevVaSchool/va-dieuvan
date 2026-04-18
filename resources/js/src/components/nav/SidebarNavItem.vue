<template>
  <RouterLink
    :to="to"
    :class="linkClass"
    :title="linkTitle"
    @click="$emit('navigate')"
  >
    <component
      v-if="showIcon"
      :is="IconComp"
      :class="iconClass"
      aria-hidden="true"
    />
    <span v-if="showLabel" :class="labelClass">{{ label }}</span>
    <span
      v-if="statusPill"
      :class="statusPillClass"
      :title="statusPill"
    >
      {{ statusPill }}
    </span>
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
  /** Mục đang bảo trì / đang phát triển (sidebar) */
  statusPill: { type: String, default: '' },
  /** vertical-full | vertical-compact | horizontal | bottom */
  variant: {
    type: String,
    default: 'vertical-full',
    validator: (v) => ['vertical-full', 'vertical-compact', 'horizontal', 'bottom'].includes(v),
  },
  /** default | brand — sidebar màu thương hiệu (#9A0036) */
  tone: {
    type: String,
    default: 'default',
    validator: (v) => ['default', 'brand'].includes(v),
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
const showIcon = computed(() => props.variant !== 'horizontal')
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

const statusPillClass = computed(() => {
  const base =
    'max-w-[6.5rem] shrink-0 truncate rounded border px-1 py-px text-[8px] font-semibold leading-tight sm:max-w-[7rem] sm:text-[9px]'
  if (props.tone === 'brand') {
    return `${base} border-amber-400/50 bg-amber-400/15 text-amber-100 dark:border-amber-300/40 dark:bg-amber-500/20 dark:text-amber-50`
  }
  return `${base} border-amber-300/70 bg-amber-50 text-amber-900 dark:border-amber-600/50 dark:bg-amber-950/50 dark:text-amber-100`
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
    return 'min-w-0 truncate whitespace-nowrap text-center text-[10px] font-medium leading-tight sm:text-[11px]'
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
  if (props.tone === 'brand') {
    if (isActive.value) {
      base.push('bg-white text-[color:var(--va-brand)]')
    } else {
      base.push('bg-amber-100 text-amber-900')
    }
    return base.join(' ')
  }
  if (isActive.value) {
    if (props.variant === 'vertical-full' || props.variant === 'vertical-compact') {
      base.push('bg-slate-700 text-white dark:bg-slate-500')
    } else {
      base.push('bg-va-800 text-white dark:bg-va-500')
    }
  } else {
    base.push('bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-100')
  }
  return base.join(' ')
})

const linkClass = computed(() => {
  const v = props.variant
  const brand = props.tone === 'brand'

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
      'relative flex min-w-0 flex-1 snap-start items-center justify-center rounded-md border-b-2 px-1.5 py-1.5 text-center transition-colors sm:px-2 sm:py-2',
      'border-transparent text-slate-600 dark:text-slate-300',
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
    'relative flex items-center gap-3 rounded-lg text-sm transition-colors',
  ]
  if (v === 'vertical-compact') {
    base.push('justify-center px-2 py-2')
  } else {
    base.push('py-2.5 pl-3 pr-3')
  }

  if (brand) {
    if (isActive.value) {
      base.push(
        'bg-white/15 font-semibold text-white before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:rounded-r-full before:bg-white before:content-[\'\']',
      )
    } else {
      base.push('text-white/90 hover:bg-white/10')
    }
    return base.join(' ')
  }

  if (isActive.value) {
    base.push(
      'bg-slate-100 font-semibold text-slate-900 before:absolute before:left-0 before:top-2 before:bottom-2 before:w-1 before:rounded-r-full before:bg-slate-800 before:content-[\'\'] dark:bg-slate-800/70 dark:text-slate-100 dark:before:bg-slate-300',
    )
  } else {
    base.push(
      'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800/80',
    )
  }
  return base.join(' ')
})
</script>
