<script setup>
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  hint: { type: String, default: '' },
})

const emit = defineEmits(['close'])

const rootRef = ref(null)
const panelRef = ref(null)
const panelStyle = ref({})

function updatePanelPosition() {
  const el = rootRef.value
  if (!el || !props.open) return
  const rect = el.getBoundingClientRect()
  const panelWidth = Math.min(300, window.innerWidth - 16)
  let left = rect.left
  if (left + panelWidth > window.innerWidth - 8) {
    left = Math.max(8, window.innerWidth - panelWidth - 8)
  } else {
    left = Math.max(8, left)
  }
  const top = rect.bottom + 6
  panelStyle.value = {
    top: `${top}px`,
    left: `${left}px`,
    width: `${panelWidth}px`,
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
}

function unbindPositionListeners() {
  window.removeEventListener('scroll', updatePanelPosition, true)
  window.removeEventListener('resize', updatePanelPosition)
}

watch(
  () => props.open,
  async (isOpen) => {
    if (isOpen) {
      await nextTick()
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
  <div ref="rootRef" class="relative" data-filter-visibility-panel>
    <slot name="trigger" />
    <Teleport to="body">
      <div
        v-if="open"
        ref="panelRef"
        class="fixed z-[9999] min-w-[260px] rounded-xl border border-slate-200/90 bg-white p-3 text-sm shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950"
        :style="panelStyle"
        role="dialog"
        :aria-label="title"
      >
        <p v-if="title" class="text-[11px] font-semibold uppercase tracking-wide text-va-800 dark:text-va-300">
          {{ title }}
        </p>
        <p v-if="hint" class="mt-1 text-[10px] leading-snug text-slate-500 dark:text-slate-400">
          {{ hint }}
        </p>
        <ul class="mt-2 max-h-[min(50vh,280px)] space-y-2 overflow-y-auto pr-0.5">
          <slot />
        </ul>
      </div>
    </Teleport>
  </div>
</template>
