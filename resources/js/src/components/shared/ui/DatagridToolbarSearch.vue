<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid'

defineProps({
  modelValue: { type: String, default: '' },
  inputId: { type: String, default: 'datagrid-search' },
  placeholder: { type: String, default: '' },
  ariaLabel: { type: String, default: '' },
  hideLabel: { type: Boolean, default: false },
  stretch: { type: Boolean, default: false },
  inlineActions: { type: Boolean, default: false },
  inputHeight: { type: String, default: 'h-10' },
})

const emit = defineEmits(['update:modelValue', 'enter'])
</script>

<template>
  <div
    class="flex min-w-0 items-center gap-2"
    :class="[stretch ? 'w-full flex-1' : '', inlineActions ? 'flex-nowrap' : 'flex-wrap']"
  >
    <label v-if="!hideLabel" :for="inputId" class="shrink-0 text-sm font-medium text-slate-600">
      Tìm kiếm
    </label>
    <div class="relative min-w-0" :class="stretch ? 'w-full flex-1' : 'w-full max-w-xl'">
      <MagnifyingGlassIcon
        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-slate-500"
        aria-hidden="true"
      />
      <input
        :id="inputId"
        type="search"
        :value="modelValue"
        :placeholder="placeholder"
        :aria-label="ariaLabel || placeholder"
        data-testid="datagrid-toolbar-search"
        class="w-full truncate rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-base text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15 sm:text-sm dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100"
        :class="inputHeight"
        @input="emit('update:modelValue', $event.target.value)"
        @keydown.enter="emit('enter')"
      />
    </div>
  </div>
</template>
