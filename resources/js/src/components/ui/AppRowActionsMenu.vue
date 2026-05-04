<template>
  <div class="inline-block" :class="rootClass">
    <button
      ref="triggerRef"
      type="button"
      class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-slate-200/90 bg-white p-1.5 text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:bg-slate-700 dark:hover:text-slate-100"
      :class="[triggerClass, disabled ? 'pointer-events-none opacity-40' : '']"
      :disabled="disabled"
      :aria-expanded="open"
      aria-haspopup="menu"
      :aria-label="ariaLabel"
      @click.stop="toggle"
    >
      <slot name="trigger">
        <EllipsisVerticalIcon class="h-5 w-5" aria-hidden="true" />
        <span v-if="triggerSrOnly" class="sr-only">{{ triggerSrOnly }}</span>
      </slot>
    </button>

    <Teleport to="body">
      <div
        v-if="open"
        ref="menuRef"
        role="menu"
        class="fixed z-[200] rounded-xl border border-slate-200/90 bg-white py-1 text-left text-sm shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950/50"
        :class="[panelMinWidthClass, menuClass]"
        :style="menuStyle"
        @click="close"
      >
        <slot />
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { EllipsisVerticalIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  disabled: { type: Boolean, default: false },
  /** Panel horizontal alignment relative to trigger (`fixed` coordinates). */
  align: {
    type: String,
    default: 'end',
    validator: (v) => ['start', 'end', 'stretch'].includes(v),
  },
  ariaLabel: { type: String, required: true },
  triggerSrOnly: { type: String, default: '' },
  rootClass: { type: String, default: '' },
  triggerClass: { type: String, default: '' },
  menuClass: { type: String, default: '' },
})

const open = ref(false)
const triggerRef = ref(null)
const menuRef = ref(null)
const menuStyle = ref({})

const panelMinWidthClass = computed(() =>
  props.align === 'stretch' ? '' : 'min-w-[12.5rem]',
)

const GAP = 6

function computeMenuStyle() {
  const el = triggerRef.value
  if (!el) return
  const rect = el.getBoundingClientRect()
  const top = `${Math.round(rect.bottom + GAP)}px`

  if (props.align === 'end') {
    menuStyle.value = {
      position: 'fixed',
      top,
      left: 'auto',
      right: `${Math.round(window.innerWidth - rect.right)}px`,
    }
  } else if (props.align === 'stretch') {
    menuStyle.value = {
      position: 'fixed',
      top,
      left: `${Math.round(rect.left)}px`,
      width: `${Math.round(rect.width)}px`,
    }
  } else {
    menuStyle.value = {
      position: 'fixed',
      top,
      left: `${Math.round(rect.left)}px`,
    }
  }
}

function toggle() {
  if (props.disabled) return
  open.value = !open.value
}

function close() {
  open.value = false
}

function reposition() {
  if (!open.value) return
  computeMenuStyle()
}

function onDocPointerDown(ev) {
  if (!open.value) return
  const t = ev.target
  if (triggerRef.value?.contains(t)) return
  if (menuRef.value?.contains(t)) return
  close()
}

function onKeydown(ev) {
  if (!open.value) return
  if (ev.key === 'Escape') {
    ev.preventDefault()
    close()
    triggerRef.value?.focus?.()
  }
}

watch(open, (isOpen) => {
  if (isOpen) {
    nextTick(() => computeMenuStyle())
    window.addEventListener('resize', reposition)
    window.addEventListener('scroll', reposition, true)
  } else {
    window.removeEventListener('resize', reposition)
    window.removeEventListener('scroll', reposition, true)
  }
})

onMounted(() => {
  document.addEventListener('pointerdown', onDocPointerDown, true)
  document.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocPointerDown, true)
  document.removeEventListener('keydown', onKeydown)
  window.removeEventListener('resize', reposition)
  window.removeEventListener('scroll', reposition, true)
})
</script>
