<script setup>
import {
  ChevronDownIcon,
  ChevronRightIcon,
  DocumentDuplicateIcon,
  PencilSquareIcon,
  ShieldCheckIcon,
  TrashIcon,
  UserGroupIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  role: { type: Object, required: true },
  icon: { type: String, default: '🎭' },
  colorBarClass: { type: String, default: 'bg-slate-300' },
  expanded: { type: Boolean, default: false },
  permLoading: { type: Boolean, default: false },
  permissions: { type: Array, default: () => [] },
  permTitleFn: { type: Function, required: true },
  saving: { type: Boolean, default: false },
})

const emit = defineEmits(['detail', 'assign-users', 'edit', 'clone', 'remove', 'toggle-perms'])
</script>

<template>
  <article
    class="relative overflow-hidden rounded-2xl bg-slate-50/90 dark:bg-slate-800/35"
    :data-testid="`system-role-card-${role.id}`"
  >
    <div class="absolute inset-y-0 left-0 w-1" :class="colorBarClass" aria-hidden="true" />

    <div class="flex flex-col gap-3 py-3.5 pl-4 pr-3 sm:px-5 lg:flex-row lg:items-center lg:gap-4">
      <div class="flex min-w-0 flex-1 items-start gap-3 lg:max-w-[32%]">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/80 text-2xl dark:bg-slate-900/60">
          {{ icon }}
        </span>
        <div class="min-w-0 flex-1">
          <h3 class="truncate text-sm font-semibold text-slate-900 dark:text-slate-50">
            {{ role.display_name || role.name }}
          </h3>
          <p v-if="role.description" class="mt-0.5 line-clamp-2 text-xs leading-snug text-slate-500 dark:text-slate-400">
            {{ role.description }}
          </p>
          <p v-else class="mt-0.5 text-xs italic text-slate-400 dark:text-slate-600">Chưa có mô tả</p>
          <p class="mt-1 font-mono text-[10px] text-slate-400">{{ role.name }}</p>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-4 text-xs text-slate-600 dark:text-slate-400 lg:gap-6">
        <span class="inline-flex items-center gap-1.5 rounded-lg bg-white/70 px-2.5 py-1.5 dark:bg-slate-900/50">
          <UserGroupIcon class="h-4 w-4 text-sky-600 dark:text-sky-400" aria-hidden="true" />
          <span class="font-display text-lg tabular-nums font-semibold text-slate-800 dark:text-slate-100">{{ role.users_count ?? 0 }}</span>
          nhân viên
        </span>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-lg bg-white/70 px-2.5 py-1.5 transition hover:bg-white dark:bg-slate-900/50 dark:hover:bg-slate-900"
          data-testid="system-role-toggle-perms"
          @click="emit('toggle-perms')"
        >
          <ShieldCheckIcon class="h-4 w-4 text-violet-600 dark:text-violet-400" aria-hidden="true" />
          <span class="font-display text-lg tabular-nums font-semibold text-slate-800 dark:text-slate-100">{{ role.permissions_count ?? 0 }}</span>
          quyền
          <ChevronDownIcon class="h-3.5 w-3.5 transition-transform" :class="{ '-rotate-180': expanded }" aria-hidden="true" />
        </button>
      </div>

      <div class="flex flex-wrap items-center gap-2 lg:ml-auto lg:shrink-0">
        <button
          type="button"
          class="inline-flex h-9 items-center gap-1 rounded-lg px-2.5 text-xs font-medium text-teal-700 hover:bg-teal-50 dark:text-teal-400 dark:hover:bg-teal-950/40"
          data-testid="system-role-detail"
          @click="emit('detail')"
        >
          Chi tiết
          <ChevronRightIcon class="h-3.5 w-3.5" aria-hidden="true" />
        </button>
        <button
          type="button"
          class="inline-flex h-9 items-center gap-1 rounded-lg px-2.5 text-xs font-medium text-violet-700 hover:bg-violet-50 dark:text-violet-400 dark:hover:bg-violet-950/40"
          data-testid="system-role-assign"
          @click="emit('assign-users')"
        >
          <UsersIcon class="h-3.5 w-3.5" aria-hidden="true" />
          Gán NV
        </button>
        <button
          type="button"
          title="Chỉnh sửa"
          class="rounded-lg p-2 text-slate-400 hover:bg-white hover:text-slate-700 dark:hover:bg-slate-900 dark:hover:text-slate-200"
          :disabled="saving"
          data-testid="system-role-edit"
          @click="emit('edit')"
        >
          <PencilSquareIcon class="h-4 w-4" aria-hidden="true" />
        </button>
        <button
          type="button"
          title="Nhân bản"
          class="rounded-lg p-2 text-slate-400 hover:bg-white hover:text-slate-700 dark:hover:bg-slate-900 dark:hover:text-slate-200"
          :disabled="saving"
          data-testid="system-role-clone"
          @click="emit('clone')"
        >
          <DocumentDuplicateIcon class="h-4 w-4" aria-hidden="true" />
        </button>
        <button
          type="button"
          title="Xóa vai trò"
          class="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30 dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
          :disabled="saving || role.name === 'superadmin'"
          data-testid="system-role-delete"
          @click="emit('remove')"
        >
          <TrashIcon class="h-4 w-4" aria-hidden="true" />
        </button>
      </div>
    </div>

    <div v-if="expanded" class="border-t border-slate-200/60 px-4 py-3 dark:border-slate-700/60 sm:px-5">
      <div v-if="permLoading" class="text-xs text-slate-400">Đang tải…</div>
      <div v-else-if="!permissions.length" class="text-xs text-slate-400">Chưa có quyền nào.</div>
      <div v-else class="flex flex-wrap gap-1">
        <span
          v-for="p in permissions"
          :key="p.id"
          :title="`${p.name}${p.plain_summary ? ' — ' + p.plain_summary : ''}`"
          class="rounded-md bg-white/80 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-900/80 dark:text-slate-300"
        >
          {{ permTitleFn(p) }}
        </span>
      </div>
    </div>
  </article>
</template>
