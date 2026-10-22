<script setup>
import ToolbarPopover from './ToolbarPopover.vue'

defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  hint: { type: String, default: '' },
})
defineEmits(['close'])
</script>

<template>
  <ToolbarPopover
    :open="open"
    :title="title"
    :hint="hint"
    :width="220"
    pointer-test-id="filter-popover-pointer"
    data-filter-visibility-panel
    @close="$emit('close')"
  >
    <template #trigger>
      <slot name="trigger" />
    </template>
    <ul class="mt-2.5 max-h-[min(50vh,280px)] space-y-2 overflow-y-auto pr-0.5 [&_li]:rounded-lg [&_li]:px-1.5 [&_li]:py-0.5 [&_li]:transition-colors [&_li:hover]:bg-slate-50 dark:[&_li:hover]:bg-slate-800/60">
      <slot />
    </ul>
  </ToolbarPopover>
</template>
