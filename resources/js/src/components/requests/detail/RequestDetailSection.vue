<template>
  <section
    :id="sectionId"
    class="scroll-mt-36 rounded-lg border border-slate-200 bg-white"
    :aria-labelledby="headingId"
  >
    <header class="border-b border-slate-100 px-4 py-3 sm:px-5">
      <h2 :id="headingId" class="text-sm font-semibold text-slate-900 sm:text-base">{{ title }}</h2>
      <p v-if="lead" class="mt-1 text-xs text-slate-600 sm:text-sm">{{ lead }}</p>
    </header>
    <div class="p-4 sm:p-5">
      <slot />
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  sectionId: { type: String, required: true },
  title: { type: String, required: true },
  lead: { type: String, default: '' },
})

const headingId = computed(() => `${props.sectionId}-heading`)
</script>
