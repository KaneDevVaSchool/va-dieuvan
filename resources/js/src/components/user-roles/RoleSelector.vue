<template>
  <div class="flex flex-col gap-3">
    <div v-for="[category, list] in categories" :key="category">
      <div class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ category }}
      </div>
      <div class="flex flex-wrap gap-1.5">
        <button
          v-for="r in list"
          :key="r.id"
          type="button"
          :disabled="disabled"
          class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-medium transition focus:outline-none focus:ring-2 focus:ring-violet-400/60 disabled:cursor-not-allowed disabled:opacity-50"
          :class="
            activeIds.includes(r.id)
              ? 'border-violet-500 bg-violet-600 text-white shadow-sm dark:bg-violet-500 dark:text-white'
              : 'border-slate-200 bg-white text-slate-700 hover:border-violet-300 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-violet-700 dark:hover:bg-violet-950/40'
          "
          @click="emit('toggle', r.id)"
        >
          <span class="font-mono">{{ r.name }}</span>
          <span v-if="r.display_name" class="ml-1 text-[10px] font-normal opacity-80">({{ r.display_name }})</span>
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
