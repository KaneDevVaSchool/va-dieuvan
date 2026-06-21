<template>
  <nav
    class="sticky top-0 z-30 shrink-0 border-b border-slate-200/80 bg-white/95 backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/95 supports-[top:env(safe-area-inset-top)]:top-[env(safe-area-inset-top)]"
    role="tablist"
    :aria-label="ariaLabel"
    data-testid="staff-request-tab-nav"
  >
    <div class="flex gap-0 overflow-x-auto px-4 sm:px-5 lg:px-6">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        role="tab"
        :aria-selected="activeTab === tab.id"
        class="relative shrink-0 border-b-2 px-4 py-3 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-va-600"
        :class="
          activeTab === tab.id
            ? 'border-va-700 text-va-800 dark:border-va-400 dark:text-va-300'
            : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'
        "
        :data-testid="`staff-request-tab-${tab.id}`"
        @click="$emit('select', tab.id)"
      >
        {{ tab.label }}
        <span
          v-if="tab.badge"
          class="ml-1.5 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"
        >{{ tab.badge }}</span>
        <span
          v-else-if="tab.dot"
          class="ml-1 inline-block h-2 w-2 rounded-full align-middle"
          :class="tab.dotTone === 'amber' ? 'bg-amber-500' : 'bg-teal-500'"
          aria-hidden="true"
        />
      </button>
    </div>
  </nav>
</template>

<script setup>
defineProps({
  tabs: { type: Array, required: true },
  activeTab: { type: String, required: true },
  ariaLabel: { type: String, required: true },
})

defineEmits(['select'])
</script>
