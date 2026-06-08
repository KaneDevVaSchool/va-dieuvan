<template>
  <nav
    :class="
      nested
        ? 'border-t border-slate-200 bg-white px-0 py-2'
        : 'sticky top-[calc(env(safe-area-inset-top,0px)+3.25rem)] z-20 -mx-4 border-y border-slate-200 bg-white/95 px-4 py-2 backdrop-blur-sm sm:top-14 sm:mx-0 sm:rounded-lg sm:border sm:shadow-sm'
    "
    role="tablist"
    :aria-label="t('request_detail.tablist_aria')"
  >
    <div class="flex gap-1 overflow-x-auto pb-0.5 [-webkit-overflow-scrolling:touch]">
      <button
        v-for="item in items"
        :key="item.id"
        type="button"
        role="tab"
        :aria-selected="activeId === item.id"
        class="relative shrink-0 snap-start rounded-lg px-3 py-2 text-xs font-semibold transition sm:text-sm"
        :class="
          activeId === item.id
            ? 'bg-slate-900 text-white'
            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
        "
        @click="$emit('select', item.id)"
      >
        {{ item.label }}
        <span
          v-if="item.badge"
          class="ml-1.5 inline-flex min-w-[1.125rem] items-center justify-center rounded-full px-1 py-px text-[10px] font-bold"
          :class="activeId === item.id ? 'bg-white/20 text-white' : 'bg-teal-600 text-white'"
        >
          {{ item.badge }}
        </span>
        <span
          v-else-if="item.dot"
          class="ml-1.5 inline-flex h-2 w-2 rounded-full"
          :class="item.dotTone === 'amber' ? 'bg-amber-500' : 'bg-teal-500'"
          aria-hidden="true"
        />
      </button>
    </div>
  </nav>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

defineProps({
  items: { type: Array, required: true },
  activeId: { type: String, required: true },
  /** Gắn dưới top bar trong một khối sticky. */
  nested: { type: Boolean, default: false },
})

defineEmits(['select'])

const { t } = useI18n()
</script>
