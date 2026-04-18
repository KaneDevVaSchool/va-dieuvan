<template>
  <div class="space-y-4">
    <Card :title="t('user_roles_page.card_title')">
      <p class="mb-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
        {{ t('user_roles_page.intro_lead') }}<b>{{ t('user_roles_page.intro_save') }}</b
        >{{ t('user_roles_page.intro_tail') }}
      </p>

      <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-end">
        <div class="w-full max-w-md lg:w-auto lg:min-w-[14rem]">
          <Input
            v-model="filters.q"
            :label="t('user_roles_page.filter_user_label')"
            :placeholder="t('user_roles_page.filter_user_ph')"
            :hint="t('user_roles_page.filter_user_hint')"
            @keydown.enter="reload(1)"
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
        <Button variant="secondary" class="w-full shrink-0 lg:w-auto" :loading="loading" @click="reload(1)">{{
          t('user_roles_page.apply_filter')
        }}</Button>
      </div>

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
                      @change="toggleRole(u.id, r.id, $event.target.checked)"
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
          <Button variant="secondary" :disabled="meta.current_page <= 1" @click="goPage(meta.current_page - 1)">{{
            t('user_roles_page.prev')
          }}</Button>
          <span class="text-xs text-slate-600 dark:text-slate-400">{{
            t('user_roles_page.page_of', { cur: meta.current_page, last: meta.last_page })
          }}</span>
          <Button variant="secondary" :disabled="meta.current_page >= meta.last_page" @click="goPage(meta.current_page + 1)">
            {{ t('user_roles_page.next') }}
          </Button>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import * as admin from '../../api/admin'
import { formatApiError } from '../../api/http'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'

const { t } = useI18n()

const loading = ref(true)
const savingId = ref(null)
const allRoles = ref([])
const items = ref([])
const meta = ref({
  total: 0,
  current_page: 1,
  last_page: 1,
  per_page: 10,
  per_page_mode: 'paged',
  truncated: false,
  cap: null,
})

const filters = reactive({
  q: '',
  assignment: 'all',
  per_page: '10',
})

/** @type {Record<number, number[]>} */
const rowState = reactive({})

function toggleRole(userId, roleId, on) {
  const cur = [...(rowState[userId] ?? [])]
  if (on) {
    if (!cur.includes(roleId)) cur.push(roleId)
  } else {
    const i = cur.indexOf(roleId)
    if (i >= 0) cur.splice(i, 1)
  }
  rowState[userId] = cur
}

function hydrateRowState() {
  const keep = new Set(items.value.map((u) => u.id))
  for (const key of Object.keys(rowState)) {
    if (!keep.has(Number(key))) delete rowState[key]
  }
  for (const u of items.value) {
    rowState[u.id] = (u.roles ?? []).map((r) => r.id)
  }
}

const displayFrom = computed(() => {
  const tot = meta.value.total ?? 0
  if (tot <= 0) return 0
  if (meta.value.per_page_mode !== 'paged') return tot ? 1 : 0
  const per = Number(meta.value.per_page) || 10
  return (meta.value.current_page - 1) * per + 1
})

const displayTo = computed(() => {
  const tot = meta.value.total ?? 0
  if (tot <= 0) return 0
  if (meta.value.per_page_mode !== 'paged') return items.value.length
  const per = Number(meta.value.per_page) || 10
  return Math.min(meta.value.current_page * per, tot)
})

async function reload(page = 1) {
  loading.value = true
  try {
    const res = await admin.listUsers({
      q: filters.q.trim() || undefined,
      assignment: filters.assignment,
      per_page: filters.per_page,
      page: filters.per_page === 'all' ? 1 : page,
    })
    items.value = res.items ?? []
    meta.value = { ...meta.value, ...(res.meta ?? {}) }
    hydrateRowState()
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  if (p < 1 || p > (meta.value.last_page ?? 1)) return
  reload(p)
}

async function saveRow(userId) {
  const u = items.value.find((x) => x.id === userId)
  const ok = await confirmAction({
    title: t('user_roles_page.confirm_title'),
    message: u
      ? t('user_roles_page.confirm_body_named', { name: u.name, email: u.email })
      : t('user_roles_page.confirm_body_generic'),
    confirmLabel: t('user_roles_page.confirm_save'),
  })
  if (!ok) return
  savingId.value = userId
  try {
    await admin.syncUserRoles(userId, rowState[userId] ?? [])
    showAppSuccess(t('user_roles_page.success'), t('user_roles_page.success_title'))
    await reload(meta.value.current_page ?? 1)
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    savingId.value = null
  }
}

onMounted(async () => {
  try {
    allRoles.value = (await admin.listRoles()) ?? []
  } catch (e) {
    showAppError(formatApiError(e))
  }
  await reload(1)
})
</script>
