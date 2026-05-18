<template>
  <section
    class="flex h-full flex-col overflow-hidden rounded-xl border bg-slate-50/70 shadow-sm shadow-slate-900/[0.03]"
    :class="
      hasIssues
        ? 'border-amber-300/90 ring-2 ring-amber-400/35'
        : 'border-slate-200/60'
    "
  >
    <button
      type="button"
      class="flex w-full shrink-0 items-center justify-between gap-3 border-b border-slate-200/50 px-4 py-3.5 text-left transition hover:bg-slate-100/60 sm:px-5 sm:py-4"
      :aria-expanded="open"
      @click="open = !open"
    >
      <h3 class="text-sm font-semibold" :class="hasIssues ? 'text-amber-950' : 'text-slate-900'">
        {{ title }}
        <span v-if="hasIssues" class="ml-1.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700">
          {{ issueHint }}
        </span>
      </h3>
      <ChevronDownIcon
        class="h-4 w-4 shrink-0 text-slate-500 transition-transform duration-200"
        :class="open ? 'rotate-180' : ''"
        aria-hidden="true"
      />
    </button>
    <div v-show="open" class="min-h-0 flex-1 px-4 pb-4 pt-3 sm:px-5 sm:pb-5">
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
  hasIssues: { type: Boolean, default: false },
  issueHint: { type: String, default: '' },
})

const open = ref(props.defaultOpen)
</script>
