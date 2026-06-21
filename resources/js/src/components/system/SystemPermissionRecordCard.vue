<script setup>
import { LockClosedIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  perm: { type: Object, required: true },
  displayName: { type: String, required: true },
  isSystem: { type: Boolean, default: false },
  roles: { type: Array, default: () => [] },
  roleColorMap: { type: Object, required: true },
  roleColorsFallback: { type: String, required: true },
  saving: { type: Boolean, default: false },
})

const emit = defineEmits(['edit', 'remove'])
</script>

<template>
  <article
    class="rounded-2xl bg-slate-50/90 px-3 py-3.5 dark:bg-slate-800/35 sm:px-4"
    :data-testid="`permission-card-${perm.id}`"
  >
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:gap-4">
      <div class="min-w-0 flex-1 lg:max-w-[38%]">
        <div class="flex flex-wrap items-center gap-1.5">
          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ displayName }}</h3>
          <span
            v-if="isSystem"
            class="inline-flex items-center gap-1 rounded-full bg-slate-200/80 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300"
          >
            <LockClosedIcon class="h-2.5 w-2.5" aria-hidden="true" />
            Hệ thống
          </span>
        </div>
        <p class="mt-0.5 font-mono text-[11px] text-slate-400 dark:text-slate-500">{{ perm.name }}</p>
        <p v-if="perm.plain_summary" class="mt-1.5 text-xs leading-relaxed text-slate-600 dark:text-slate-400">
          {{ perm.plain_summary }}
        </p>
      </div>

      <div class="min-w-0 flex-1">
        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Vai trò được gán</p>
        <div class="mt-1.5 flex flex-wrap gap-1">
          <template v-if="roles.length">
            <span
              v-for="r in roles"
              :key="r.id"
              :title="r.name"
              class="rounded-full px-2 py-0.5 text-[10px] font-medium"
              :class="roleColorMap.get(r.id) ?? roleColorsFallback"
            >
              {{ r.display_name || r.name }}
            </span>
          </template>
          <span v-else class="rounded-full bg-white/60 px-2 py-0.5 text-[10px] text-slate-400 dark:bg-slate-900/40">
            Chưa gán vai trò
          </span>
        </div>
      </div>

      <div class="flex shrink-0 items-center gap-1 self-start">
        <button
          type="button"
          title="Chỉnh sửa"
          class="rounded-lg p-2 text-slate-400 hover:bg-white hover:text-slate-700 dark:hover:bg-slate-900 dark:hover:text-slate-200"
          :disabled="saving"
          data-testid="permission-card-edit"
          @click="emit('edit')"
        >
          <PencilSquareIcon class="h-4 w-4" aria-hidden="true" />
        </button>
        <button
          v-if="!isSystem"
          type="button"
          title="Xóa quyền"
          class="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600 disabled:opacity-40 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
          :disabled="saving"
          data-testid="permission-card-delete"
          @click="emit('remove')"
        >
          <TrashIcon class="h-4 w-4" aria-hidden="true" />
        </button>
      </div>
    </div>
  </article>
</template>
