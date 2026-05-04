<template>
  <div class="flex flex-wrap items-start gap-x-5 gap-y-3" role="group">
    <div
      v-for="[category, list] in categories"
      :key="category"
      class="flex min-w-[min(100%,11rem)] max-w-[22rem] flex-none flex-col gap-1.5"
    >
      <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ category }}
      </div>
      <div class="flex flex-wrap gap-x-1.5 gap-y-1.5">
        <button
          v-for="r in list"
          :key="r.id"
          type="button"
          :disabled="disabled"
          class="inline-flex max-w-full shrink-0 items-center rounded-full border px-2.5 py-1 text-[11px] font-medium transition focus:outline-none focus:ring-2 focus:ring-violet-400/60 disabled:cursor-not-allowed disabled:opacity-50"
          :class="
            activeIds.includes(r.id)
              ? 'border-violet-500 bg-violet-600 text-white shadow-sm dark:bg-violet-500 dark:text-white'
              : 'border-slate-200 bg-white text-slate-700 hover:border-violet-300 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-violet-700 dark:hover:bg-violet-950/40'
          "
          @click="emit('toggle', r.id)"
        >
          <span class="truncate font-mono">{{ r.name }}</span>
          <span v-if="r.display_name" class="ml-1 shrink-0 text-[10px] font-normal opacity-80">
            ({{ r.display_name }})
          </span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { RoleDto } from '../../services/userRoleService'

defineProps<{
  categories: readonly (readonly [string, RoleDto[]])[]
  activeIds: number[]
  disabled?: boolean
}>()

const emit = defineEmits<{
  toggle: [roleId: number]
}>()
</script>
