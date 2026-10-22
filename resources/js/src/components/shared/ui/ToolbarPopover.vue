<script setup>
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  hint: { type: String, default: '' },
  width: { type: Number, default: 280 },
  panelMarker: { type: String, default: '' },
  pointerTestId: { type: String, default: 'toolbar-popover-pointer' },
})

const emit = defineEmits(['close'])

const rootRef = ref(null)
const panelRef = ref(null)
const panelStyle = ref({})
const pointerLeft = ref(20)
const opensAbove = ref(false)

function updatePanelPosition() {
  const el = rootRef.value
  if (!el || !props.open) return
  const rect = el.getBoundingClientRect()
  const panelWidth = Math.min(props.width, window.innerWidth - 16)
  let left = rect.left
  if (left + panelWidth > window.innerWidth - 8) {
    left = Math.max(8, window.innerWidth - panelWidth - 8)
  } else {
    left = Math.max(8, left)
  }
  const gap = 10
  if (panelRef.value) panelRef.value.style.width = `${panelWidth}px`
  const height = panelRef.value?.offsetHeight ?? 0
  const below = window.innerHeight - rect.bottom - gap - 8
  const above = rect.top - gap - 8
  opensAbove.value = below < height && above > below
  const top = opensAbove.value ? Math.max(8, rect.top - gap - height) : rect.bottom + gap
  pointerLeft.value = Math.max(20, Math.min(rect.left + rect.width / 2 - left, panelWidth - 20))
  panelStyle.value = {
    top: `${top}px`,
    left: `${left}px`,
    width: `${panelWidth}px`,
    transformOrigin: `${pointerLeft.value}px ${opensAbove.value ? 'bottom' : 'top'}`,
  }
}

function onDocMouseDown(ev) {
  const t = ev.target
  if (!t || typeof t.closest !== 'function') return
  if (rootRef.value?.contains(t)) return
  if (panelRef.value?.contains(t)) return
  emit('close')
}

function bindPositionListeners() {
  window.addEventListener('scroll', updatePanelPosition, true)
  window.addEventListener('resize', updatePanelPosition)
  document.addEventListener('keydown', onKeydown)
}

function onKeydown(event) {
  if (event.key !== 'Escape') return
  event.preventDefault()
  emit('close')
  rootRef.value?.querySelector('button')?.focus({ preventScroll: true })
}

function unbindPositionListeners() {
  window.removeEventListener('scroll', updatePanelPosition, true)
  window.removeEventListener('resize', updatePanelPosition)
  document.removeEventListener('keydown', onKeydown)
}

watch(
  () => props.open,
  async (isOpen) => {
    if (isOpen) {
      await nextTick()
      if (!props.open) return
      updatePanelPosition()
      bindPositionListeners()
    } else {
      unbindPositionListeners()
    }
  },
)

onMounted(() => {
  document.addEventListener('mousedown', onDocMouseDown)
})

onUnmounted(() => {
  document.removeEventListener('mousedown', onDocMouseDown)
  unbindPositionListeners()
})
</script>

<template>
  <div ref="rootRef" class="relative">
    <slot name="trigger" />
    <Teleport to="body">
      <Transition name="filter-popover">
      <div
        v-if="open"
        ref="panelRef"
        class="fixed z-[9999] min-w-0 rounded-2xl border border-slate-200/90 bg-white p-3 text-sm shadow-[0_16px_48px_-12px_rgba(15,23,42,0.28),0_4px_12px_rgba(15,23,42,0.06)] dark:border-slate-700/90 dark:bg-slate-900 dark:shadow-black/40"
        :style="panelStyle"
        v-bind="panelMarker ? { [panelMarker]: '' } : {}"
        role="dialog"
        :aria-label="title"
      >
        <span
          class="pointer-events-none absolute h-3.5 w-3.5 rotate-45 border-slate-200/90 bg-white dark:border-slate-700/90 dark:bg-slate-900"
          :class="opensAbove ? '-bottom-[7px] border-b border-r' : '-top-[7px] border-l border-t'"
          :style="{ left: `${pointerLeft - 7}px` }"
          :data-testid="pointerTestId"
          aria-hidden="true"
        />
        <div v-if="title || hint" class="border-b border-slate-100 pb-2.5 dark:border-slate-800">
        <p v-if="title" class="text-[11px] font-semibold uppercase tracking-wide text-va-800 dark:text-va-300">
          {{ title }}
        </p>
        <p v-if="hint" class="mt-1 text-[10px] leading-snug text-slate-500 dark:text-slate-400">
          {{ hint }}
        </p>
        </div>
        <div class="min-h-0 max-h-[min(60dvh,420px)] overflow-y-auto overscroll-y-contain rounded-xl [&_button]:rounded-lg">
          <slot />
        </div>
      </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.filter-popover-enter-active {
  transition: opacity 160ms ease, transform 160ms cubic-bezier(0.22, 1, 0.36, 1);
}
.filter-popover-leave-active {
  transition: opacity 100ms ease, transform 100ms ease;
}
.filter-popover-enter-from,
.filter-popover-leave-to {
  opacity: 0;
  transform: scale(0.97);
}
@media (prefers-reduced-motion: reduce) {
  .filter-popover-enter-active,
  .filter-popover-leave-active {
    transition: none;
  }
}
</style>
