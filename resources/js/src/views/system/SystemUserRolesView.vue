<template>
  <div class="space-y-4">
    <Card :title="t('user_roles_page.card_title')">
      <p class="mb-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
        {{ t('user_roles_page.intro_lead') }}<b>{{ t('user_roles_page.intro_save') }}</b
        >{{ t('user_roles_page.intro_tail') }}
      </p>

      <AppFilterBar class="mb-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-end">
          <div class="w-full max-w-md lg:w-auto lg:min-w-[14rem]">
            <Input
              v-model="filters.q"
              :label="t('user_roles_page.filter_user_label')"
              :placeholder="t('user_roles_page.filter_user_ph')"
              :hint="t('user_roles_page.filter_user_hint')"
              @keydown.enter="applyFilters"
            />
          </div>
          <Select
            v-model="filters.assignment"
            :label="t('user_roles_page.filter_assignment_label')"
            :hint="t('user_roles_page.filter_assignment_hint')"
            class="w-full min-w-0 lg:min-w-[14rem]"
          >
            <option value="all">{{ t('user_roles_page.assignment_all') }}</option>
            <option value="assigned">{{ t('user_roles_page.assignment_assigned') }}</option>
            <option value="unassigned">{{ t('user_roles_page.assignment_unassigned') }}</option>
          </Select>
          <Select
            v-model="filters.per_page"
            :label="t('user_roles_page.per_page_label')"
            class="w-full min-w-0 lg:min-w-[11rem]"
            :hint="t('user_roles_page.per_page_hint')"
          >
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="20">20</option>
            <option value="25">25</option>
            <option value="all">{{ t('user_roles_page.per_page_all') }}</option>
          </Select>
          <Button variant="secondary" class="w-full shrink-0 lg:w-auto" :loading="loading" @click="applyFilters">
            {{ t('user_roles_page.apply_filter') }}
          </Button>
        </div>
      </AppFilterBar>

      <div
        v-if="meta.truncated"
        class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
      >
        {{ t('user_roles_page.truncated', { cap: meta.cap }) }}
      </div>

      <div v-if="loading" class="text-sm text-slate-500">{{ t('user_roles_page.loading') }}</div>
      <div v-else class="overflow-x-auto overscroll-x-contain [-webkit-overflow-scrolling:touch]">
        <table class="w-full min-w-[56rem] text-left text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-400">
              <th class="py-2 pr-3 font-medium">{{ t('user_roles_page.col_name') }}</th>
              <th class="py-2 pr-3 font-medium">{{ t('user_roles_page.col_email') }}</th>
              <th class="py-2 pr-3 font-medium">{{ t('user_roles_page.col_code') }}</th>
              <th class="py-2 pr-3 font-medium">{{ t('user_roles_page.col_roles') }}</th>
              <th class="py-2 text-right font-medium">{{ t('user_roles_page.col_actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in items" :key="u.id" class="border-b border-slate-100 dark:border-slate-800">
              <td class="max-w-[12rem] py-2 pr-3 align-top font-medium">{{ u.name }}</td>
              <td class="py-2 pr-3 align-top text-slate-700 dark:text-slate-300">{{ u.email }}</td>
              <td class="py-2 pr-3 align-top font-mono text-xs text-slate-600 dark:text-slate-400">{{ u.employee_code ?? '—' }}</td>
              <td class="py-2 pr-3 align-top">
                <div class="flex max-w-xl flex-wrap gap-x-3 gap-y-1.5">
                  <label v-for="r in allRoles" :key="r.id" class="inline-flex cursor-pointer items-center gap-1.5 text-xs">
                    <input
                      type="checkbox"
                      class="rounded border-slate-300 dark:border-slate-600"
                      :checked="(rowState[u.id] ?? []).includes(r.id)"
                      @change="onRoleCheckboxChange(u.id, r.id, $event)"
                    />
                    <span class="font-mono text-[11px] text-slate-800 dark:text-slate-200">{{ r.name }}</span>
                    <span v-if="r.display_name" class="text-slate-500">({{ r.display_name }})</span>
                  </label>
                </div>
              </td>
              <td class="py-2 text-right align-top">
                <Button class="whitespace-nowrap px-2.5 py-1.5 text-xs" :loading="savingId === u.id" @click="saveRow(u.id)">
                  {{ t('user_roles_page.save_row') }}
                </Button>
              </td>
            </tr>
            <tr v-if="!items.length">
              <td colspan="5" class="py-8 text-center text-slate-500">{{ t('user_roles_page.empty') }}</td>
            </tr>
          </tbody>
          <tfoot v-if="items.length">
            <tr class="border-t border-slate-200 bg-slate-50/90 text-slate-600 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400">
              <td colspan="5" class="py-2.5 text-xs leading-relaxed">
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
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      <div
        v-if="!loading && meta.per_page_mode === 'paged' && (meta.last_page ?? 1) > 1"
        class="mt-4 flex flex-col gap-3 border-t border-slate-200 pt-4 dark:border-slate-700 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
      >
        <div class="text-xs text-slate-500">{{ t('user_roles_page.per_page_summary', { n: meta.per_page }) }}</div>
        <div class="flex flex-wrap items-center gap-2">
          <Button variant="secondary" :disabled="meta.current_page <= 1" @click="goPage(meta.current_page - 1)">
            {{ t('user_roles_page.prev') }}
          </Button>
          <span class="text-xs text-slate-600 dark:text-slate-400">
            {{ t('user_roles_page.page_of', { cur: meta.current_page, last: meta.last_page }) }}
          </span>
          <Button variant="secondary" :disabled="meta.current_page >= meta.last_page" @click="goPage(meta.current_page + 1)">
            {{ t('user_roles_page.next') }}
          </Button>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { useUserRoles } from '../../composables/useUserRoles'

const { t } = useI18n()

const {
  loading,
  savingId,
  allRoles,
  items,
  meta,
  filters,
  rowState,
  displayFrom,
  displayTo,
  goPage,
  applyFilters,
  saveRow,
  onRoleCheckboxChange,
} = useUserRoles()
</script>
