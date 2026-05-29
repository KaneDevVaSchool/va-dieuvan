<script setup>
import { computed, ref } from 'vue'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  /** Module descriptor: { id, label, icon } */
  module: { type: Object, required: true },
  /** All permissions belonging to this module */
  perms: { type: Array, required: true },
  /** Array of currently selected permission IDs */
  modelValue: { type: Array, required: true },
  /** When true the section is read-only (no checkboxes can be toggled) */
  readonly: { type: Boolean, default: false },
  /** Start expanded by default */
  defaultOpen: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(props.defaultOpen)

const selectedInModule = computed(() =>
  props.perms.filter((p) => props.modelValue.includes(p.id)),
)

const selectedCount = computed(() => selectedInModule.value.length)
const totalCount    = computed(() => props.perms.length)
const pct           = computed(() => (totalCount.value === 0 ? 0 : (selectedCount.value / totalCount.value) * 100))

const allSelected  = computed(() => selectedCount.value === totalCount.value && totalCount.value > 0)
const someSelected = computed(() => selectedCount.value > 0 && !allSelected.value)

function toggleAll() {
  if (props.readonly) return
  const ids = props.perms.map((p) => p.id)
  if (allSelected.value) {
    emit('update:modelValue', props.modelValue.filter((id) => !ids.includes(id)))
  } else {
    const merged = [...new Set([...props.modelValue, ...ids])]
    emit('update:modelValue', merged)
  }
}

function toggle(permId) {
  if (props.readonly) return
  if (props.modelValue.includes(permId)) {
    emit('update:modelValue', props.modelValue.filter((id) => id !== permId))
  } else {
    emit('update:modelValue', [...props.modelValue, permId])
  }
}

function displayName(perm) {
  const dn = perm.display_name?.trim()
  return dn && dn !== perm.name ? dn : perm.name
}
</script>

<template>
  <div class="overflow-hidden rounded-xl border border-slate-200/90 dark:border-slate-700">
    <!-- Accordion header -->
    <button
      type="button"
      class="flex w-full items-center justify-between px-4 py-3 text-left transition hover:bg-slate-50/80 dark:hover:bg-slate-800/50"
      :class="isOpen ? 'bg-slate-50/90 dark:bg-slate-800/60' : 'bg-white dark:bg-slate-900'"
      :aria-expanded="isOpen"
      @click="isOpen = !isOpen"
    >
      <!-- Left: checkbox + label -->
      <span class="flex items-center gap-3 min-w-0">
        <!-- Module select-all checkbox (stop propagation so it doesn't toggle accordion) -->
        <span @click.stop>
          <input
            :id="`mod-all-${module.id}`"
            type="checkbox"
            :checked="allSelected"
            :indeterminate="someSelected"
            :disabled="readonly || perms.length === 0"
            class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800"
            @change="toggleAll"
          />
        </span>
        <span class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ module.label }}
        </span>
      </span>

      <!-- Right: progress + count + chevron -->
      <span class="ml-4 flex shrink-0 items-center gap-3">
        <span class="hidden sm:flex items-center gap-2">
          <span class="h-1.5 w-16 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
            <span
              class="block h-full rounded-full transition-all duration-300"
              :class="pct === 100 ? 'bg-teal-500' : pct > 0 ? 'bg-teal-400' : 'bg-transparent'"
              :style="{ width: pct + '%' }"
            />
          </span>
          <span class="min-w-[2.5rem] text-right text-xs tabular-nums text-slate-500 dark:text-slate-400">
            {{ selectedCount }}/{{ totalCount }}
          </span>
        </span>
        <ChevronDownIcon
          class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
          :class="{ '-rotate-180': isOpen }"
          aria-hidden="true"
        />
      </span>
    </button>

    <!-- Accordion body -->
    <div v-show="isOpen" class="border-t border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900/60">
      <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2 lg:grid-cols-3">
        <label
          v-for="perm in perms"
          :key="perm.id"
          :for="`perm-${perm.id}`"
          class="flex cursor-pointer items-start gap-2.5 rounded-lg border px-3 py-2 text-sm transition select-none"
          :class="
            modelValue.includes(perm.id)
              ? 'border-teal-300 bg-teal-50/70 text-teal-900 dark:border-teal-600/50 dark:bg-teal-950/30 dark:text-teal-100'
              : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
          "
          :title="perm.name"
        >
          <input
            :id="`perm-${perm.id}`"
            type="checkbox"
            :checked="modelValue.includes(perm.id)"
            :disabled="readonly"
            class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 disabled:cursor-not-allowed dark:border-slate-600 dark:bg-slate-800"
            @change="toggle(perm.id)"
          />
          <span class="min-w-0 leading-snug">
            <span class="block font-medium">{{ displayName(perm) }}</span>
            <span class="block font-mono text-[10px] text-slate-400 dark:text-slate-500 truncate">{{ perm.name }}</span>
          </span>
        </label>
      </div>
    </div>
  </div>
</template>
