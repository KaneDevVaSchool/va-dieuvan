<template>
  <div
    class="rounded-xl border border-violet-200/80 bg-gradient-to-r from-violet-50/90 via-white to-indigo-50/50 p-3 shadow-sm dark:border-violet-900/50 dark:from-violet-950/40 dark:via-slate-950 dark:to-indigo-950/30"
  >
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
      <div class="min-w-0 flex-1">
        <p class="text-xs font-semibold text-violet-900 dark:text-violet-200">
          {{ t('user_roles_page.bulk_bar_title', { n: selectedCount }) }}
        </p>
        <p class="mt-1 text-[11px] text-slate-600 dark:text-slate-400">
          {{ t('user_roles_page.bulk_bar_hint') }}
        </p>
        <div class="mt-2 flex flex-wrap gap-2">
          <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs">
            <input v-model="localAction" type="radio" value="assign" class="rounded border-slate-300 dark:border-slate-600" />
            {{ t('user_roles_page.bulk_assign') }}
          </label>
          <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs">
            <input v-model="localAction" type="radio" value="remove" class="rounded border-slate-300 dark:border-slate-600" />
            {{ t('user_roles_page.bulk_remove') }}
          </label>
        </div>
        <div class="mt-3">
          <p class="mb-1 text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">
            {{ t('user_roles_page.bulk_pick_roles_label') }}
          </p>
          <div class="max-h-28 overflow-y-auto rounded-lg border border-slate-200/80 bg-white/80 p-2 dark:border-slate-700 dark:bg-slate-900/80">
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="r in flatRoles"
                :key="r.id"
                type="button"
                :disabled="disabled"
                class="rounded-full border px-2 py-0.5 text-[10px] font-medium transition disabled:opacity-50"
                :class="
                  pickedSet.has(r.name)
                    ? 'border-teal-500 bg-teal-600 text-white dark:bg-teal-500'
                    : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-teal-300 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200'
                "
                @click="toggleName(r.name)"
              >
                {{ r.name }}
              </button>
            </div>
          </div>
        </div>
      </div>
      <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
        <Button variant="secondary" type="button" :disabled="disabled || !selectedCount" @click="emit('clear-selection')">
          {{ t('user_roles_page.bulk_clear_selection') }}
        </Button>
        <Button
          type="button"
          :loading="applyLoading"
          :disabled="!selectedCount || !modelValue.length || applyDisabled"
          @click="emit('apply')"
        >
          {{ t('user_roles_page.bulk_apply') }}
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { RoleDto } from '../../services/userRoleService'
import Button from '../ui/Button.vue'

const { t } = useI18n()

const props = defineProps<{
  selectedCount: number
  roles: RoleDto[]
  modelValue: string[]
  action: 'assign' | 'remove'
  disabled: boolean
  applyLoading: boolean
  applyDisabled: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [names: string[]]
  'update:action': [v: 'assign' | 'remove']
  apply: []
  'clear-selection': []
}>()

const localAction = computed({
  get: () => props.action,
  set: (v: 'assign' | 'remove') => emit('update:action', v),
})

const flatRoles = computed(() => [...props.roles].sort((a, b) => a.name.localeCompare(b.name)))

const pickedSet = computed(() => new Set(props.modelValue))

watch(
  () => props.selectedCount,
  (n) => {
    if (n === 0) emit('update:modelValue', [])
  },
)

function toggleName(name: string) {
  if (props.disabled) return
  const next = new Set(props.modelValue)
  if (next.has(name)) next.delete(name)
  else next.add(name)
  emit('update:modelValue', [...next])
}
</script>
