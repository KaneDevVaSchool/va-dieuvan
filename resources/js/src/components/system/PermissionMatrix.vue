<script setup>
import { computed } from 'vue'
import { groupPermissions } from '../../config/permissionModules.js'
import PermissionModuleSection from './PermissionModuleSection.vue'

const props = defineProps({
  /** All available permissions: { id, name, display_name?, plain_description? }[] */
  allPerms: { type: Array, required: true },
  /** Currently selected permission IDs */
  modelValue: { type: Array, required: true },
  /** When true no checkboxes are editable */
  readonly: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue'])

const grouped = computed(() => groupPermissions(props.allPerms))

const totalSelected = computed(() => props.modelValue.length)
const totalAvailable = computed(() => props.allPerms.length)

const allSelected = computed(
  () => totalSelected.value === totalAvailable.value && totalAvailable.value > 0,
)

function selectAll() {
  emit('update:modelValue', props.allPerms.map((p) => p.id))
}

function deselectAll() {
  emit('update:modelValue', [])
}
</script>

<template>
  <div class="space-y-1.5">
    <!-- Global header: count + bulk actions -->
    <div class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-slate-50/70 px-4 py-2.5 dark:border-slate-700 dark:bg-slate-800/40">
      <span class="text-sm font-semibold text-slate-800 dark:text-slate-200">
        <span class="text-teal-700 dark:text-teal-400 tabular-nums">{{ totalSelected }}</span>
        <span class="text-slate-500 dark:text-slate-400"> / {{ totalAvailable }} quyền đã chọn</span>
      </span>
      <div v-if="!readonly" class="flex items-center gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-200 px-3 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-100 disabled:opacity-40 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
          :disabled="allSelected"
          @click="selectAll"
        >
          Chọn tất cả
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-200 px-3 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-100 disabled:opacity-40 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700"
          :disabled="totalSelected === 0"
          @click="deselectAll"
        >
          Bỏ chọn
        </button>
      </div>
    </div>

    <!-- Module accordion sections -->
    <PermissionModuleSection
      v-for="group in grouped"
      :key="group.module.id"
      :module="group.module"
      :perms="group.perms"
      :model-value="modelValue"
      :readonly="readonly"
      :default-open="group.perms.some((p) => modelValue.includes(p.id))"
      @update:model-value="emit('update:modelValue', $event)"
    />

    <!-- Empty state -->
    <div
      v-if="grouped.length === 0"
      class="rounded-xl border border-dashed border-slate-200 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400"
    >
      Không có quyền nào.
    </div>
  </div>
</template>
