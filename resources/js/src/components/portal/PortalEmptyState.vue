<template>
  <div class="rounded-2xl bg-slate-50/60 px-6 py-12 text-center">
    <svg
      class="mx-auto h-24 w-40 text-va-300"
      :class="{ 'animate-pulse': pulseIcon }"
      viewBox="0 0 160 96"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      aria-hidden="true"
    >
      <path
        d="M8 72h144"
        stroke="currentColor"
        stroke-width="2"
        stroke-dasharray="6 6"
        stroke-linecap="round"
        opacity="0.5"
      />
      <rect x="24" y="36" width="88" height="32" rx="8" class="fill-va-50 stroke-va-200" stroke-width="2" />
      <path d="M112 44h28l12 16v8h-40V44z" class="fill-va-100 stroke-va-200" stroke-width="2" stroke-linejoin="round" />
      <circle cx="48" cy="72" r="8" class="fill-slate-200 stroke-va-300" stroke-width="2" />
      <circle cx="112" cy="72" r="8" class="fill-slate-200 stroke-va-300" stroke-width="2" />
      <rect x="36" y="44" width="20" height="12" rx="2" class="fill-va-200/80" />
      <rect x="60" y="44" width="20" height="12" rx="2" class="fill-va-200/80" />
    </svg>
    <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ title }}</h2>
    <p class="mx-auto mt-2 max-w-md text-base text-slate-600">{{ description }}</p>
    <div v-if="$slots.action" class="mt-6">
      <slot name="action" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, required: true },
  /** Pulse animation on icon (e.g. transitional loading affordance) */
  pulseIcon: { type: Boolean, default: false },
})
</script>
