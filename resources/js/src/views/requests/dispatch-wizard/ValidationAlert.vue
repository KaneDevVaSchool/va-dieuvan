<template>
  <div
    v-if="hasContent"
    :id="alertId || undefined"
    class="rounded-xl border border-amber-200/90 bg-amber-50/90 px-4 py-3 shadow-sm ring-1 ring-amber-900/[0.06] sm:px-5 sm:py-4"
    role="alert"
  >
    <p class="text-sm font-semibold text-amber-950">{{ title }}</p>
    <p v-if="lead" class="mt-1 text-xs leading-relaxed text-amber-900/90">{{ lead }}</p>

    <div v-if="groups?.length" class="mt-3 space-y-3">
      <section
        v-for="group in groups"
        :key="group.section"
        class="rounded-lg border border-amber-200/80 bg-white/60 px-3 py-2.5"
      >
        <p class="text-xs font-semibold uppercase tracking-wide text-amber-900">
          {{ group.title }}
        </p>
        <ul class="mt-1.5 list-inside list-disc space-y-1 text-xs font-medium text-amber-950/95">
          <li v-for="(msg, i) in group.messages" :key="i">{{ msg }}</li>
        </ul>
      </section>
    </div>

    <ul
      v-else-if="messages.length"
      class="mt-2 list-inside list-disc space-y-1 text-xs font-medium text-amber-900/95"
    >
      <li v-for="(msg, i) in messages" :key="i">{{ msg }}</li>
    </ul>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  lead: { type: String, default: '' },
  alertId: { type: String, default: '' },
  messages: { type: Array, default: () => [] },
  /** @type {{ section: string, title: string, messages: string[] }[]} */
  groups: { type: Array, default: () => [] },
})

const hasContent = computed(
  () => (props.groups?.length ?? 0) > 0 || (props.messages?.length ?? 0) > 0,
)
</script>
