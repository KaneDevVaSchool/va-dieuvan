<template>
  <tr class="border-b border-slate-100 dark:border-slate-800">
    <td
      class="sticky left-0 z-10 w-10 bg-white py-2 pr-1 align-top dark:bg-slate-950"
      :class="rowSaving ? 'opacity-70' : ''"
    >
      <input
        type="checkbox"
        class="mt-1 rounded border-slate-300 dark:border-slate-600"
        :checked="selected"
        :disabled="disabled"
        :aria-label="t('user_roles_page.select_user')"
        @change="emit('toggle-select', user.id)"
      />
    </td>
    <td
      class="sticky left-10 z-10 min-w-[12rem] max-w-[16rem] bg-white py-2 pr-3 align-top shadow-[4px_0_12px_-4px_rgba(0,0,0,0.08)] dark:bg-slate-950 dark:shadow-[4px_0_12px_-4px_rgba(0,0,0,0.4)]"
    >
      <div class="font-semibold text-slate-900 dark:text-slate-100">{{ user.name }}</div>
      <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ user.email }}</div>
      <div v-if="user.employee_code" class="mt-1.5">
        <span
          class="inline-flex rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-700 dark:bg-slate-800 dark:text-slate-300"
        >
          {{ user.employee_code }}
        </span>
      </div>
      <div v-if="rowSaving" class="mt-2 text-[10px] text-violet-600 dark:text-violet-400">
        {{ t('user_roles_page.row_saving') }}
      </div>
    </td>
    <td class="py-2 pr-2 align-top">
      <RoleSelector
        :categories="categories"
        :active-ids="roleIds"
        :disabled="disabled"
        @toggle="(rid) => emit('toggle-role', user.id, rid)"
      />
    </td>
  </tr>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { RoleDto, UserDto } from '../../services/userRoleService'
import RoleSelector from './RoleSelector.vue'

const { t } = useI18n()

defineProps<{
  user: UserDto
  roleIds: number[]
  selected: boolean
  rowSaving: boolean
  disabled: boolean
  categories: readonly (readonly [string, RoleDto[]])[]
}>()

const emit = defineEmits<{
  'toggle-select': [userId: number]
  'toggle-role': [userId: number, roleId: number]
}>()
</script>
