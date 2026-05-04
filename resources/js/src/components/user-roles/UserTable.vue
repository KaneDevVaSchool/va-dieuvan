<template>
  <div class="overflow-x-auto overscroll-x-contain [-webkit-overflow-scrolling:touch]">
    <table class="w-full min-w-[42rem] border-separate border-spacing-0 text-left text-sm">
      <thead>
        <tr
          class="sticky top-0 z-20 border-b border-slate-200 bg-slate-50/95 text-slate-600 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-400"
        >
          <th
            class="sticky left-0 z-30 w-10 bg-slate-50/95 py-2 pr-1 dark:bg-slate-900/95"
          >
            <input
              type="checkbox"
              class="mt-0.5 rounded border-slate-300 dark:border-slate-600"
              :checked="allSelected"
              :indeterminate="someSelected"
              :disabled="disabled || !items.length"
              :aria-label="t('user_roles_page.select_all_page')"
              @change="emit('toggle-select-all')"
            />
          </th>
          <th
            class="sticky left-10 z-30 min-w-[12rem] bg-slate-50/95 py-2 pr-3 font-medium shadow-[4px_0_12px_-4px_rgba(0,0,0,0.06)] dark:bg-slate-900/95 dark:shadow-[4px_0_12px_-4px_rgba(0,0,0,0.35)]"
          >
            {{ t('user_roles_page.col_user') }}
          </th>
          <th class="py-2 pr-2 font-medium">{{ t('user_roles_page.col_roles') }}</th>
        </tr>
      </thead>
      <tbody>
        <UserRow
          v-for="u in items"
          :key="u.id"
          :user="u"
          :role-ids="rowState[u.id] ?? []"
          :selected="selectedSet.has(u.id)"
          :row-saving="savingSet.has(u.id)"
          :disabled="disabled"
          :categories="categories"
          @toggle-select="(id) => emit('toggle-select', id)"
          @toggle-role="(uid, rid) => emit('toggle-role', uid, rid)"
        />
        <tr v-if="!items.length">
          <td colspan="3" class="py-8 text-center text-slate-500">
            {{ emptyLabel }}
          </td>
        </tr>
      </tbody>
      <tfoot v-if="items.length">
        <tr
          class="border-t border-slate-200 bg-slate-50/90 text-slate-600 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400"
        >
          <td colspan="3" class="py-2.5 text-xs leading-relaxed">
            <slot name="footer" />
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import type { RoleDto, UserDto } from '../../services/userRoleService'
import UserRow from './UserRow.vue'

const { t } = useI18n()

const props = defineProps<{
  items: UserDto[]
  rowState: Record<number, number[]>
  selectedIds: number[]
  savingUserIds: number[]
  allSelected: boolean
  someSelected: boolean
  disabled: boolean
  emptyLabel: string
  categories: readonly (readonly [string, RoleDto[]])[]
}>()

const emit = defineEmits<{
  'toggle-select': [userId: number]
  'toggle-select-all': []
  'toggle-role': [userId: number, roleId: number]
}>()

const selectedSet = computed(() => new Set(props.selectedIds))
const savingSet = computed(() => new Set(props.savingUserIds))
</script>
