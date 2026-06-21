<script setup>
defineProps({
  modelValue: { type: String, default: '' },
  options: {
    type: Array,
    default: () => [],
    /** @type {Array<{ value: string, label: string }>} */
  },
  ariaLabel: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div
    class="inline-flex max-w-full items-center gap-0.5 overflow-x-auto rounded-lg bg-slate-100/90 p-0.5 ring-1 ring-slate-200/80
           [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
    role="group"
    :aria-label="ariaLabel"
    data-testid="datagrid-segmented-control"
  >
    <button
      v-for="opt in options"
      :key="opt.value"
      type="button"
      class="shrink-0 rounded-md px-3 py-2 text-xs font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-va-800 sm:text-sm"
      :class="
        modelValue === opt.value
          ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200/80'
          : 'text-slate-600 hover:text-slate-900'
      "
      :aria-pressed="modelValue === opt.value"
      :data-testid="`datagrid-segment-${opt.value}`"
      @click="emit('update:modelValue', opt.value)"
    >
      {{ opt.label }}
    </button>
  </div>
</template>
