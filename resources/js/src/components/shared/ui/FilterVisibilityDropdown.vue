<script setup>
import { onMounted, onUnmounted, ref } from 'vue'

defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  hint: { type: String, default: '' },
})

const emit = defineEmits(['close'])

const rootRef = ref(null)

function onDocMouseDown(ev) {
  if (!rootRef.value) return
  if (rootRef.value.contains(ev.target)) return
  emit('close')
}

onMounted(() => {
  document.addEventListener('mousedown', onDocMouseDown)
})

onUnmounted(() => {
  document.removeEventListener('mousedown', onDocMouseDown)
})
</script>

<template>
  <div ref="rootRef" class="relative" data-filter-visibility-panel>
    <slot name="trigger" />
    <div
      v-if="open"
      class="absolute left-0 top-[calc(100%+6px)] z-50 min-w-[260px] rounded-xl border border-slate-200/90 bg-white p-3 text-sm shadow-lg ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-slate-950"
      role="dialog"
      :aria-label="title"
    >
      <p v-if="title" class="text-[11px] font-semibold uppercase tracking-wide text-va-800 dark:text-va-300">
        {{ title }}
      </p>
      <p v-if="hint" class="mt-1 text-[10px] leading-snug text-slate-500 dark:text-slate-400">
        {{ hint }}
      </p>
      <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
        <slot />
      </ul>
    </div>
  </div>
</template>
