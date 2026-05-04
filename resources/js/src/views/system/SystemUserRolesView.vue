<template>
  <div class="space-y-4">
    <Card :title="t('user_roles_page.card_title')">
      <p class="mb-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
        {{ t('user_roles_page.intro_lead') }}
        <span class="font-medium text-slate-800 dark:text-slate-200">{{ t('user_roles_page.intro_autosave') }}</span>
        {{ t('user_roles_page.intro_tail_autosave') }}
      </p>

      <AppFilterBar class="mb-4">
        <div
          class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
        >
          {{ t('user_roles_page.filter_bar_title') }}
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
          <Input
            v-model="filters.q"
            class="min-w-0 xl:col-span-2"
            :label="t('user_roles_page.filter_user_label')"
            :placeholder="t('user_roles_page.filter_user_ph')"
            :hint="t('user_roles_page.filter_user_hint')"
            @keydown.enter="store.applyFilters()"
          />
          <Select
            v-model="filters.assignment"
            class="min-w-0"
            :label="t('user_roles_page.filter_assignment_label')"
            :hint="t('user_roles_page.filter_assignment_hint')"
          >
            <option value="all">{{ t('user_roles_page.assignment_all') }}</option>
            <option value="assigned">{{ t('user_roles_page.assignment_assigned') }}</option>
            <option value="unassigned">{{ t('user_roles_page.assignment_unassigned') }}</option>
          </Select>
          <label class="block min-w-0">
            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">
              {{ t('user_roles_page.filter_roles_label') }}
            </span>
            <select
              v-model="filters.roles"
              multiple
              class="min-h-[4.75rem] w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-800 outline-none ring-slate-200 focus:ring dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
              :disabled="interactionLocked"
            >
              <option v-for="r in allRoles" :key="r.id" :value="r.name">
                {{ r.name }} — {{ r.display_name || '—' }}
              </option>
            </select>
            <span class="mt-1 block text-[11px] text-slate-500 dark:text-slate-400">{{
              t('user_roles_page.filter_roles_hint')
            }}</span>
          </label>
          <Select v-model="filters.per_page" class="min-w-0" :label="t('user_roles_page.per_page_label')" :hint="t('user_roles_page.per_page_hint')">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="all">{{ t('user_roles_page.per_page_all') }}</option>
          </Select>
          <div class="flex items-end">
            <Button variant="secondary" class="w-full" :loading="loading" :disabled="interactionLocked" @click="store.applyFilters()">
              {{ t('user_roles_page.apply_filter') }}
            </Button>
          </div>
        </div>
      </AppFilterBar>

      <BulkActionBar
        v-if="selectedIds.length > 0"
        v-model="bulkRoleNames"
        class="mb-4"
        :selected-count="selectedIds.length"
        :roles="allRoles"
        :action="bulkAction"
        :disabled="interactionLocked"
        :apply-loading="bulkApplying"
        :apply-disabled="interactionLocked"
        @update:action="store.setBulkAction"
        @apply="store.applyBulkRoles()"
        @clear-selection="store.clearSelection()"
      />

      <div
        v-if="meta.truncated"
        class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
      >
        {{ t('user_roles_page.truncated', { cap: meta.cap }) }}
      </div>

      <div v-if="loading" class="flex items-center gap-2 py-6 text-sm text-slate-500">
        <span
          class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-slate-300 border-t-violet-600 dark:border-slate-600 dark:border-t-violet-400"
        />
        {{ t('user_roles_page.loading') }}
      </div>
      <UserTable
        v-else
        :items="items"
        :row-state="rowState"
        :selected-ids="selectedIds"
        :saving-user-ids="savingUserIds"
        :all-selected="allPageSelected"
        :some-selected="somePageSelected"
        :disabled="interactionLocked"
        :categories="rolesByCategory"
        :empty-label="t('user_roles_page.empty')"
        @toggle-select="store.toggleSelect"
        @toggle-select-all="store.toggleSelectAllOnPage"
        @toggle-role="(uid, rid) => store.toggleUserRole(uid, rid)"
      >
        <template #footer>
          {{ t('user_roles_page.footer_total', { total: meta.total }) }}
          <template v-if="meta.per_page_mode === 'paged'">
            {{ t('user_roles_page.footer_page', { cur: meta.current_page, last: meta.last_page }) }}
            {{ t('user_roles_page.footer_rows', { from: displayFrom, to: displayTo }) }}
          </template>
          <template v-else>
            {{
              t('user_roles_page.footer_showing', {
                n: items.length,
                total: meta.total,
              })
            }}
          </template>
        </template>
      </UserTable>

      <div
        v-if="!loading && meta.per_page_mode === 'paged' && (meta.last_page ?? 1) > 1"
        class="mt-4 flex flex-col gap-3 border-t border-slate-200 pt-4 dark:border-slate-700 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
      >
        <div class="text-xs text-slate-500">{{ t('user_roles_page.per_page_summary', { n: meta.per_page }) }}</div>
        <div class="flex flex-wrap items-center gap-2">
          <Button variant="secondary" :disabled="meta.current_page <= 1 || interactionLocked" @click="store.goPage(meta.current_page - 1)">
            {{ t('user_roles_page.prev') }}
          </Button>
          <span class="text-xs text-slate-600 dark:text-slate-400">
            {{ t('user_roles_page.page_of', { cur: meta.current_page, last: meta.last_page }) }}
          </span>
          <Button variant="secondary" :disabled="meta.current_page >= meta.last_page || interactionLocked" @click="store.goPage(meta.current_page + 1)">
            {{ t('user_roles_page.next') }}
          </Button>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import BulkActionBar from '../../components/user-roles/BulkActionBar.vue'
import UserTable from '../../components/user-roles/UserTable.vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { useUserRoleAssignmentStore } from '../../stores/userRoleAssignmentStore'

const { t } = useI18n()
const store = useUserRoleAssignmentStore()

const {
  loading,
  savingUserIds,
  bulkApplying,
  allRoles,
  items,
  meta,
  filters,
  rowState,
  rolesByCategory,
  selectedIds,
  bulkRoleNames,
  bulkAction,
  interactionLocked,
  allPageSelected,
  somePageSelected,
  displayFrom,
  displayTo,
} = storeToRefs(store)

onMounted(() => {
  void store.bootstrap()
})
</script>
