<template>
  <section class="rounded-xl bg-slate-50/70 shadow-sm shadow-slate-900/[0.03]">
    <button
      type="button"
      class="flex w-full items-center justify-between gap-3 rounded-t-xl px-4 py-3.5 text-left transition hover:bg-slate-100/60 sm:px-5 sm:py-4"
      :aria-expanded="open"
      @click="open = !open"
    >
      <h3 class="text-sm font-semibold text-slate-900">{{ title }}</h3>
      <ChevronDownIcon
        class="h-4 w-4 shrink-0 text-slate-500 transition-transform duration-200"
        :class="open ? 'rotate-180' : ''"
        aria-hidden="true"
      />
    </button>
    <div v-show="open" class="px-4 pb-4 pt-1 sm:px-5 sm:pb-5">
      <div class="text-xs leading-relaxed text-slate-700">
        <slot />
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  title: { type: String, required: true },
  defaultOpen: { type: Boolean, default: true },
})

const open = ref(props.defaultOpen)
</script>
