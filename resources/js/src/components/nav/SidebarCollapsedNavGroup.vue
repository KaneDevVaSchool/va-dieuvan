<template>
  <div ref="rootRef" class="relative flex w-full justify-center">
    <button
      type="button"
      :class="buttonClass"
      :title="label"
      :aria-expanded="open"
      aria-haspopup="menu"
      @click="$emit('toggle')"
    >
      <component :is="IconComp" :class="iconClass" aria-hidden="true" />
      <span
        v-if="badgeTotal > 0"
        :class="badgeClass"
      >
        {{ badgeTotal > 99 ? '99+' : badgeTotal }}
      </span>
    </button>
    <Teleport to="body">
      <div
        v-show="open"
        ref="panelRef"
        class="fixed z-[200] min-w-[13rem] overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-600 dark:bg-slate-900"
        :style="panelStyle"
        role="menu"
        :aria-label="label"
      >
        <div
          class="border-b border-slate-100 px-3 py-2 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:text-slate-300"
        >
          {{ label }}
        </div>
        <RouterLink
          v-for="c in children"
          :key="c.to"
          :to="c.to"
          class="flex items-center gap-2.5 px-3 py-2 text-sm transition-colors"
          :class="
            isChildActive(c.to)
              ? 'bg-slate-100 font-semibold text-slate-900 dark:bg-slate-800/70 dark:text-slate-100'
              : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800/80'
          "
          role="menuitem"
          @click="$emit('close')"
        >
          <component
            :is="childIcon(c)"
            class="h-5 w-5 shrink-0 text-current opacity-90"
            aria-hidden="true"
          />
          <span class="min-w-0 flex-1 truncate">{{ t(c.labelKey) }}</span>
          <span
            v-if="badgeCount(c) > 0"
            class="inline-flex min-w-[1.25rem] shrink-0 items-center justify-center rounded-full bg-rose-100 px-1 text-[10px] font-bold leading-none text-rose-800 dark:bg-rose-900/50 dark:text-rose-100"
          >
            {{ badgeCount(c) > 99 ? '99+' : badgeCount(c) }}
          </span>
        </RouterLink>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, onUnmounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { NAV_ICON_MAP } from '../../config/navIconMap'

const props = defineProps({
  label: { type: String, required: true },
  icon: { type: String, default: 'home' },
  children: { type: Array, required: true },
  badgeCount: { type: Function, required: true },
  open: { type: Boolean, default: false },
})

const emit = defineEmits(['toggle', 'close'])

const { t } = useI18n()
const route = useRoute()
const rootRef = ref(null)
const panelRef = ref(null)
const panelStyle = ref({})

const IconComp = computed(() => NAV_ICON_MAP[props.icon] ?? NAV_ICON_MAP.home)

function childIcon(c) {
  return NAV_ICON_MAP[c.icon] ?? NAV_ICON_MAP.home
}

const badgeTotal = computed(() =>
  props.children.reduce((sum, c) => sum + props.badgeCount(c), 0),
)

function pathMatches(to, path) {
  if (!to) return false
  if (to === '/') return path === '/' || path === ''
  return path === to || path.startsWith(`${to}/`)
}

const isChildActive = (to) => pathMatches(to, route.path)

const anyChildActive = computed(() =>
  props.children.some((c) => pathMatches(c.to, route.path)),
)

const iconClass = computed(() => {
  return 'h-[1.15rem] w-[1.15rem] shrink-0 text-current opacity-90 sm:h-5 sm:w-5'
})

const badgeClass = computed(() => {
  const base =
    'absolute -right-0.5 -top-0.5 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full px-0.5 text-[10px] font-bold leading-none'
  if (anyChildActive.value) {
    return `${base} bg-slate-700 text-white dark:bg-slate-500`
  }
  return `${base} bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-100`
})

const buttonClass = computed(() => {
  const base = [
    'relative flex w-full items-center justify-center rounded-lg px-2 py-2 text-sm transition-colors',
  ]
  if (anyChildActive.value) {
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

function updatePosition() {
  if (!rootRef.value || !props.open) return
  const rect = rootRef.value.getBoundingClientRect()
  const panelW = 208
  let left = rect.right + 8
  if (left + panelW > window.innerWidth - 8) {
    left = Math.max(8, rect.left - panelW - 8)
  }
  let top = rect.top
  const maxH = window.innerHeight - 16
  if (top + 240 > maxH) {
    top = Math.max(8, maxH - 240)
  }
  panelStyle.value = {
    top: `${top}px`,
    left: `${left}px`,
  }
}

function onDocPointerDown(e) {
  if (!props.open) return
  const el = e.target
  if (rootRef.value?.contains(el)) return
  if (panelRef.value?.contains(el)) return
  emit('close')
}

watch(
  () => props.open,
  async (open) => {
    if (open) {
      await nextTick()
      updatePosition()
      document.addEventListener('pointerdown', onDocPointerDown, true)
      window.addEventListener('resize', updatePosition, { passive: true })
      window.addEventListener('scroll', updatePosition, true)
    } else {
      document.removeEventListener('pointerdown', onDocPointerDown, true)
      window.removeEventListener('resize', updatePosition)
      window.removeEventListener('scroll', updatePosition, true)
    }
  },
)

watch(
  () => route.path,
  () => {
    if (props.open) emit('close')
  },
)

onUnmounted(() => {
  document.removeEventListener('pointerdown', onDocPointerDown, true)
  window.removeEventListener('resize', updatePosition)
  window.removeEventListener('scroll', updatePosition, true)
})
</script>
