<template>
  <div class="w-full space-y-6 pb-16 text-slate-900 dark:text-slate-100">
    <header class="flex flex-col gap-4 border-b border-slate-200/80 pb-5 dark:border-slate-700/80 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0 space-y-2">
        <RouterLink
          :to="p2pStepTo('p2pPolicyHub', workflowTermId)"
          class="inline-flex items-center gap-2 rounded-lg px-2 py-1.5 text-base font-medium text-teal-800 transition hover:bg-teal-50 dark:text-teal-300 dark:hover:bg-teal-950/40"
        >
          <ArrowLeftIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
          {{ t('p2p_policy_page.back_to_hub') }}
        </RouterLink>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white md:text-3xl">
          {{ t('p2p_policy_page.students_title') }}
        </h1>
      </div>
    </header>

    <P2pPolicyWorkflowBar current-step="students" :term-id="workflowTermId" />

    <p
      v-if="focusedRouteLabel"
      class="rounded-xl border border-teal-200/80 bg-teal-50/80 px-4 py-3 text-sm text-teal-950 dark:border-teal-900/50 dark:bg-teal-950/25 dark:text-teal-100"
    >
      {{ t('p2p_policy_page.students_focus_route', { route: focusedRouteLabel }) }}
      <RouterLink
        :to="p2pStepTo('p2pPolicyRoutes', workflowTermId)"
        class="ml-2 font-semibold text-teal-800 underline dark:text-teal-200"
      >
        {{ t('p2p_policy_page.students_back_routes') }}
      </RouterLink>
    </p>

    <Card v-if="canImportExport" :hint="t('p2p_policy_page.tip_section_import_export')">
      <h2 class="mb-4 text-xl font-bold text-slate-900 dark:text-white">{{ t('p2p_policy_page.section_import_export') }}</h2>
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          :disabled="exporting"
          @click="onExport"
        >
          {{ t('p2p_policy_page.export') }}
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          :disabled="exporting"
          @click="onDownloadTemplate"
        >
          {{ t('p2p_policy_page.download_template') }}
        </button>
        <button
          type="button"
          class="rounded-lg bg-va-800 px-3 py-2 text-sm font-medium text-white hover:bg-va-900 disabled:opacity-50"
          :disabled="!filters.p2p_policy_term_id"
          @click="openImport"
        >
          {{ t('p2p_policy_page.import') }}
        </button>
      </div>
    </Card>

    <div class="relative z-40">
      <AppFilterBar>
        <div class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
          <details class="group relative">
            <summary
              class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 [&::-webkit-details-marker]:hidden dark:border-slate-700 dark:bg-slate-900/95"
            >
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              <ChevronDownIcon class="h-4 w-4 text-slate-400" aria-hidden="true" />
            </summary>
            <div class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[260px] rounded-2xl border bg-white p-3 shadow-xl dark:border-slate-700 dark:bg-slate-900">
              <p class="text-xs font-semibold uppercase text-violet-700 dark:text-violet-300">{{ t('dashboard_analytics.filter_applied_title') }}</p>
              <ul class="mt-2 space-y-1 text-sm">
                <li v-if="!activeFilterCount">{{ t('p2p_policy_page.no_filters') }}</li>
              </ul>
              <button type="button" class="mt-3 w-full rounded-xl border px-3 py-2 text-sm" @click="onClearFilters">
                {{ t('p2p_policy_page.clear_filters') }}
              </button>
            </div>
          </details>

          <template v-for="fd in filterDefs" :key="fd.id">
            <label
              v-if="fd.id === 'per_page' && visibility.per_page"
              class="inline-flex shrink-0 items-center gap-1.5"
            >
              <span class="sr-only">{{ t('filter_bar.per_page') }}</span>
              <select
                v-model.number="filters.per_page"
                class="h-9 rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                :aria-label="t('filter_bar.per_page')"
                @change="onPerPageChange"
              >
                <option v-for="n in P2P_STUDENTS_PER_PAGE_OPTIONS" :key="n" :value="n">{{ n }}</option>
              </select>
              <span class="hidden text-xs text-slate-500 sm:inline dark:text-slate-400">{{ t('p2p_policy_page.rows') }}</span>
            </label>
            <details v-else-if="visibility[fd.id]" class="group relative min-w-0 shrink-0">
              <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-white/90 bg-white/95 px-2 py-1.5 text-sm [&::-webkit-details-marker]:hidden dark:border-slate-700 dark:bg-slate-900/95">
                <span class="whitespace-nowrap text-slate-600 dark:text-slate-400">{{ t(fd.labelKey) }}</span>
                <span class="max-w-[10rem] truncate font-medium text-slate-900 dark:text-white">{{ filterLabel(fd.id) }}</span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" />
              </summary>
              <div class="absolute left-0 top-[calc(100%+6px)] z-50 min-w-[220px] rounded-xl border bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                <select
                  v-if="fd.id === 'policy_type'"
                  v-model="filters.policy_type"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onFilterChange"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option v-for="opt in policyTypeOptions" :key="opt.value" :value="opt.value">
                    {{ t(opt.labelKey) }}
                  </option>
                </select>
                <input
                  v-else-if="fd.id === 'class_name'"
                  v-model="filters[fd.id]"
                  type="search"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onFilterChange"
                />
                <select
                  v-else-if="fd.id === 'is_active'"
                  v-model="filters.is_active"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onFilterChange"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option value="true">{{ t('p2p_policy_page.active_yes') }}</option>
                  <option value="false">{{ t('p2p_policy_page.active_no') }}</option>
                </select>
                <select
                  v-else-if="fd.id === 'weekday_iso'"
                  v-model="filters.weekday_iso"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onFilterChange"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option v-for="d in weekdays" :key="d.v" :value="d.v">{{ d.l }}</option>
                </select>
                <select
                  v-else-if="fd.id === 'p2p_policy_term_id'"
                  v-model="filters.p2p_policy_term_id"
                  class="w-full max-h-48 overflow-y-auto rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onTermFilterChange"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option v-for="pt in p2pTerms" :key="pt.id" :value="pt.id">{{ p2pTermLabel(pt) }}</option>
                </select>
                <select
                  v-else-if="fd.id === 'policy_route_id'"
                  v-model="filters.policy_route_id"
                  class="w-full max-h-48 overflow-y-auto rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onFilterChange"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option v-for="r in routes" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
                <select
                  v-else-if="fd.id === 'campus_id'"
                  v-model="filters.campus_id"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onFilterChange"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <select
                  v-else-if="fd.id === 'academic_term_id'"
                  v-model="filters.academic_term_id"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="onFilterChange"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option v-for="at in academicTerms" :key="at.id" :value="at.id">{{ at.name }}</option>
                </select>
              </div>
            </details>
          </template>

          <input
            v-model="filters.q"
            type="search"
            class="min-w-[10rem] flex-1 rounded-lg border-0 bg-white/90 px-3 py-2 text-sm ring-1 ring-slate-200/80 focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-900/90 dark:ring-slate-700"
            :placeholder="t('p2p_policy_page.search_placeholder')"
            :aria-label="t('p2p_policy_page.search_placeholder')"
            @keydown.enter="onSearch"
          />
        </div>
      </AppFilterBar>
    </div>

    <div
      v-if="canManage && selectedIds.length"
      class="flex flex-wrap items-center gap-3 rounded-xl border border-teal-200/80 bg-teal-50/90 px-4 py-3 dark:border-teal-900/50 dark:bg-teal-950/30"
    >
      <span class="text-sm font-medium text-teal-950 dark:text-teal-100">
        {{ t('p2p_policy_page.students_bulk_selected', { count: selectedIds.length }) }}
      </span>
      <button
        type="button"
        class="rounded-lg bg-va-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-va-900"
        @click="openBulkAssign"
      >
        {{ t('p2p_policy_page.students_bulk_assign') }}
      </button>
      <button
        type="button"
        class="rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-sm font-medium text-rose-800 hover:bg-rose-50 dark:border-rose-900 dark:bg-slate-900 dark:text-rose-200"
        @click="onBulkDelete"
      >
        {{ t('p2p_policy_page.students_bulk_delete') }}
      </button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/[0.04] dark:border-slate-700 dark:bg-slate-900/80">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/80">
            <tr>
              <th v-if="canManage" class="w-10 px-3 py-2.5">
                <input
                  type="checkbox"
                  class="rounded border-slate-300 text-teal-600"
                  :checked="allPageSelected"
                  :aria-label="t('p2p_policy_page.students_select_page')"
                  @change="toggleSelectPage"
                />
              </th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_code') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_name') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_class') }}</th>
              <th v-if="showRouteColumn" class="px-3 py-2.5">{{ t('p2p_policy_page.col_route') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_direction') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_policy') }}</th>
              <th class="px-3 py-2.5">{{ t('p2p_policy_page.col_active') }}</th>
              <th v-if="canManage" class="w-[5rem] px-3 py-2.5 text-right">{{ t('p2p_policy_page.col_actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(row, idx) in items"
              :key="row.id"
              class="border-t border-slate-100 transition-colors hover:bg-violet-50/40 dark:border-slate-800 dark:hover:bg-violet-950/20"
              :class="idx % 2 === 1 ? 'bg-slate-50/40 dark:bg-slate-900/20' : ''"
            >
              <td v-if="canManage" class="px-3 py-2.5">
                <input
                  type="checkbox"
                  class="rounded border-slate-300 text-teal-600"
                  :checked="isSelected(row.id)"
                  @change="toggleSelect(row.id)"
                />
              </td>
              <td class="px-3 py-2.5 font-mono text-xs font-medium text-slate-800 dark:text-slate-200">{{ row.student_code }}</td>
              <td class="px-3 py-2.5 font-medium text-slate-900 dark:text-white">{{ row.student_name }}</td>
              <td class="px-3 py-2.5 text-slate-700 dark:text-slate-300">{{ row.class_name || '—' }}</td>
              <td v-if="showRouteColumn" class="px-3 py-2.5 text-slate-700 dark:text-slate-300">
                {{ row.policy_route?.name ?? '—' }}
              </td>
              <td class="px-3 py-2.5">
                <span
                  class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                  :class="directionBadgeClass(row.direction)"
                >
                  {{ directionLabel(t, row.direction) }}
                </span>
              </td>
              <td class="px-3 py-2.5">
                <span
                  class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                  :class="policyTypeBadgeClass(row.policy_type)"
                >
                  {{ policyTypeLabel(t, row.policy_type) }}
                </span>
              </td>
              <td class="px-3 py-2.5">
                <span
                  class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                  :class="row.is_active
                    ? 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100'
                    : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
                >
                  {{ row.is_active ? t('p2p_policy_page.active_yes') : t('p2p_policy_page.active_no') }}
                </span>
              </td>
              <td v-if="canManage" class="px-3 py-2.5 text-right">
                <button
                  type="button"
                  class="rounded-lg border border-teal-200 bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-900 hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-950/50 dark:text-teal-100"
                  @click="openEdit(row)"
                >
                  {{ t('p2p_policy_page.students_edit') }}
                </button>
              </td>
            </tr>
            <tr v-if="!loading && !items.length">
              <td :colspan="tableColspan" class="px-3 py-10 text-center text-slate-500">{{ t('p2p_policy_page.empty') }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="loading" class="flex items-center justify-center gap-2 border-t border-slate-100 py-8 text-sm text-slate-500 dark:border-slate-800">
          <span class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-va-700" aria-hidden="true" />
          {{ t('common.processing') }}
        </div>
      </div>

      <div
        v-if="meta.total > 0"
        class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/90 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:bg-slate-800/50"
      >
        <p class="text-sm text-slate-600 dark:text-slate-400">
          {{
            t('p2p_policy_page.students_pagination_summary', {
              from: pageFrom,
              to: pageTo,
              total: meta.total,
            })
          }}
        </p>
        <div v-if="(meta.last_page ?? 1) > 1" class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900"
            :disabled="loading || (meta.current_page ?? 1) <= 1"
            @click="goPage((meta.current_page ?? 1) - 1)"
          >
            {{ t('p2p_policy_page.routes_page_prev') }}
          </button>
          <button
            v-for="p in pageNumbers"
            :key="p"
            type="button"
            class="min-w-[2rem] rounded-lg px-2 py-1.5 text-sm"
            :class="
              p === meta.current_page
                ? 'bg-teal-600 font-semibold text-white'
                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
            "
            :disabled="loading"
            @click="goPage(p)"
          >
            {{ p }}
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900"
            :disabled="loading || (meta.current_page ?? 1) >= (meta.last_page ?? 1)"
            @click="goPage((meta.current_page ?? 1) + 1)"
          >
            {{ t('p2p_policy_page.routes_page_next') }}
          </button>
        </div>
      </div>
    </div>

    <P2pPolicyStudentImportDialog
      ref="importDialog"
      :p2p-policy-term-id="filters.p2p_policy_term_id"
      @committed="reload"
    />

    <P2pPolicyStudentEditDialog
      ref="editDialog"
      :routes="routes"
      @saved="onStudentSaved"
    />

    <P2pPolicyStudentBulkAssignDialog
      ref="bulkAssignDialog"
      :routes="routes"
      :count="selectedIds.length"
      @done="onBulkAssignDone"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { ArrowLeftIcon, ChevronDownIcon, FunnelIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import P2pPolicyStudentBulkAssignDialog from '../../components/p2pPolicy/P2pPolicyStudentBulkAssignDialog.vue'
import P2pPolicyStudentEditDialog from '../../components/p2pPolicy/P2pPolicyStudentEditDialog.vue'
import P2pPolicyStudentImportDialog from '../../components/p2pPolicy/P2pPolicyStudentImportDialog.vue'
import P2pPolicyWorkflowBar from '../../components/p2pPolicy/P2pPolicyWorkflowBar.vue'
import Card from '../../components/ui/Card.vue'
import {
  P2P_STUDENTS_DEFAULT_PER_PAGE,
  P2P_STUDENTS_PER_PAGE_OPTIONS,
  useP2pPolicyStudentFilters,
} from '../../composables/useP2pPolicyStudentFilters'
import {
  p2pStepTo,
  p2pWorkflowQuery,
  resolveP2pTermIdFromRoute,
} from '../../composables/useP2pPolicyWorkflow'
import { confirmAction } from '../../composables/useConfirm'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { formatApiError } from '../../api/http'
import { useAuthStore } from '../../store'
import {
  directionBadgeClass,
  directionLabel,
  P2P_POLICY_TYPE_VALUES,
  policyTypeBadgeClass,
  policyTypeLabel,
  p2pTermStatusLabel,
} from '../../utils/p2pPolicyStudentLabels'
import {
  bulkDeletePolicyStudents,
  downloadPolicyStudentsExport,
  downloadPolicyStudentsImportTemplate,
  listAcademicTerms,
  listCampuses,
  listP2pPolicyTerms,
  listPolicyRoutes,
  listPolicyStudents,
} from '../../api/p2pPolicy'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { filters, visibility, apiParams, activeFilterCount, clearFilters, resetPage, filterDefs } =
  useP2pPolicyStudentFilters()

const workflowTermId = computed(() => {
  const fromFilter = filters.p2p_policy_term_id
  if (fromFilter !== '' && fromFilter != null) return Number(fromFilter)
  return resolveP2pTermIdFromRoute(route)
})

const focusedRouteLabel = computed(() => {
  if (!filters.policy_route_id) return ''
  const r = routes.value.find((x) => String(x.id) === String(filters.policy_route_id))
  return r?.name ?? ''
})

const showRouteColumn = computed(() => !filters.policy_route_id)

const canImportExport = computed(() => auth.hasPermission('p2p_policy.import_export'))
const canManage = computed(() => auth.hasPermission('p2p_policy.manage'))

const items = ref([])
const meta = ref({ total: 0, current_page: 1, per_page: P2P_STUDENTS_DEFAULT_PER_PAGE, last_page: 1 })
const loading = ref(false)
const exporting = ref(false)
const routes = ref([])
const campuses = ref([])
const academicTerms = ref([])
const p2pTerms = ref([])
const importDialog = ref(null)
const editDialog = ref(null)
const bulkAssignDialog = ref(null)
const selectedIds = ref([])
const listReady = ref(false)

const weekdays = [
  { v: 1, l: 'T2' },
  { v: 2, l: 'T3' },
  { v: 3, l: 'T4' },
  { v: 4, l: 'T5' },
  { v: 5, l: 'T6' },
  { v: 6, l: 'T7' },
  { v: 7, l: 'CN' },
]

const policyTypeOptions = P2P_POLICY_TYPE_VALUES.map((value) => ({
  value,
  labelKey: `p2p_policy_page.policy_type_${value}`,
}))

const tableColspan = computed(() => {
  let n = 6
  if (showRouteColumn.value) n++
  if (canManage.value) n += 2
  return n
})

const pageFrom = computed(() => {
  if (!meta.value.total) return 0
  const cur = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? filters.per_page
  return (cur - 1) * per + 1
})

const pageTo = computed(() => {
  const cur = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? filters.per_page
  const total = meta.value.total ?? 0
  return Math.min(cur * per, total)
})

const pageNumbers = computed(() => {
  const last = meta.value.last_page ?? 1
  const cur = meta.value.current_page ?? 1
  const span = 5
  let start = Math.max(1, cur - Math.floor(span / 2))
  let end = Math.min(last, start + span - 1)
  start = Math.max(1, end - span + 1)
  const nums = []
  for (let p = start; p <= end; p++) nums.push(p)
  return nums
})

const allPageSelected = computed(() => {
  if (!items.value.length) return false
  return items.value.every((row) => selectedIds.value.includes(row.id))
})

function isSelected(id) {
  return selectedIds.value.includes(id)
}

function toggleSelect(id) {
  const set = new Set(selectedIds.value)
  if (set.has(id)) set.delete(id)
  else set.add(id)
  selectedIds.value = [...set]
}

function toggleSelectPage(ev) {
  const checked = ev.target.checked
  const pageIds = items.value.map((r) => r.id)
  if (checked) {
    const set = new Set([...selectedIds.value, ...pageIds])
    selectedIds.value = [...set]
  } else {
    selectedIds.value = selectedIds.value.filter((id) => !pageIds.includes(id))
  }
}

function clearSelection() {
  selectedIds.value = []
}

function openEdit(row) {
  editDialog.value?.open(row)
}

function openBulkAssign() {
  if (!selectedIds.value.length) return
  bulkAssignDialog.value?.open(selectedIds.value)
}

async function onBulkAssignDone() {
  const n = selectedIds.value.length
  showAppSuccess(t('p2p_policy_page.students_bulk_assign_done', { count: n }))
  clearSelection()
  await reload()
}

async function onBulkDelete() {
  const n = selectedIds.value.length
  if (!n) return
  const ok = await confirmAction({
    title: t('p2p_policy_page.students_bulk_delete'),
    message: t('p2p_policy_page.students_bulk_delete_confirm', { count: n }),
    confirmLabel: t('p2p_policy_page.students_bulk_delete'),
    danger: true,
  })
  if (!ok) return
  try {
    await bulkDeletePolicyStudents({ ids: selectedIds.value })
    showAppSuccess(t('p2p_policy_page.students_bulk_delete_done', { count: n }))
    clearSelection()
    await reload()
  } catch (e) {
    showAppError(formatApiError(e))
  }
}

async function onStudentSaved() {
  showAppSuccess(t('p2p_policy_page.students_edit_saved'))
  await reload()
}

function p2pTermLabel(pt) {
  const year = pt.academic_term?.academic_year ?? pt.academic_year ?? ''
  const status = p2pTermStatusLabel(t, pt.status ?? '')
  return year ? `${year} — ${status}` : `#${pt.id} — ${status}`
}

const selectedP2pTermLabel = computed(() => {
  if (!filters.p2p_policy_term_id) return t('p2p_policy_page.filter_any')
  const pt = p2pTerms.value.find((x) => String(x.id) === String(filters.p2p_policy_term_id))
  return pt ? p2pTermLabel(pt) : '—'
})

function filterLabel(id) {
  if (id === 'per_page') return String(filters.per_page)
  if (id === 'p2p_policy_term_id' && filters.p2p_policy_term_id) {
    const pt = p2pTerms.value.find((x) => String(x.id) === String(filters.p2p_policy_term_id))
    return pt ? p2pTermLabel(pt) : '—'
  }
  if (id === 'policy_route_id' && filters.policy_route_id) {
    return routes.value.find((r) => String(r.id) === String(filters.policy_route_id))?.name ?? '—'
  }
  if (id === 'campus_id' && filters.campus_id) {
    return campuses.value.find((c) => String(c.id) === String(filters.campus_id))?.name ?? '—'
  }
  if (id === 'is_active' && filters.is_active !== '') {
    return filters.is_active === 'true' || filters.is_active === true
      ? t('p2p_policy_page.active_yes')
      : t('p2p_policy_page.active_no')
  }
  if (id === 'policy_type' && filters.policy_type) {
    return policyTypeLabel(t, filters.policy_type)
  }
  return filters[id] || t('p2p_policy_page.filter_any')
}

async function reload() {
  loading.value = true
  try {
    const res = await listPolicyStudents(apiParams.value)
    items.value = res.items ?? []
    meta.value = res.meta ?? {
      total: 0,
      current_page: 1,
      per_page: filters.per_page,
      last_page: 1,
    }
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  const last = meta.value.last_page ?? 1
  filters.page = Math.min(Math.max(1, p), last)
}

function onPerPageChange() {
  resetPage()
}

function onFilterChange() {
  resetPage()
  syncWorkflowQuery()
}

function onTermFilterChange() {
  resetPage()
  loadRouteOptions().then(() => {
    syncWorkflowQuery()
  })
}

function onSearch() {
  resetPage()
}

function onClearFilters() {
  clearFilters()
  clearSelection()
  syncWorkflowQuery()
}

async function onExport() {
  exporting.value = true
  try {
    await downloadPolicyStudentsExport(apiParams.value)
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    exporting.value = false
  }
}

async function onDownloadTemplate() {
  exporting.value = true
  try {
    await downloadPolicyStudentsImportTemplate()
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    exporting.value = false
  }
}

function openImport() {
  importDialog.value?.open()
}

function syncWorkflowQuery() {
  const extra = {}
  if (filters.policy_route_id) {
    extra.policy_route_id = String(filters.policy_route_id)
  }
  router.replace({
    query: p2pWorkflowQuery(filters.p2p_policy_term_id || resolveP2pTermIdFromRoute(route), extra),
  })
}

async function loadRouteOptions() {
  const params = { per_page: 100 }
  if (filters.p2p_policy_term_id) {
    params.p2p_policy_term_id = filters.p2p_policy_term_id
  }
  const r = await listPolicyRoutes(params)
  routes.value = r.items ?? []
}

onMounted(async () => {
  const [c, at, pt] = await Promise.all([
    listCampuses({ per_page: 100 }),
    listAcademicTerms({ per_page: 100 }),
    listP2pPolicyTerms({ per_page: 50 }),
  ])
  campuses.value = c.items ?? []
  academicTerms.value = at.items ?? []
  p2pTerms.value = pt.items ?? []

  const qTerm = resolveP2pTermIdFromRoute(route)
  if (qTerm) {
    filters.p2p_policy_term_id = qTerm
  } else if (p2pTerms.value[0] && !filters.p2p_policy_term_id) {
    filters.p2p_policy_term_id = p2pTerms.value[0].id
  }
  if (route.query.policy_route_id) {
    filters.policy_route_id = Number(route.query.policy_route_id)
  }

  await loadRouteOptions()
  syncWorkflowQuery()
  listReady.value = true
  await reload()
})

watch(
  () => filters.p2p_policy_term_id,
  async () => {
    await loadRouteOptions()
    syncWorkflowQuery()
  },
)

watch(
  () => filters.policy_route_id,
  () => {
    resetPage()
    syncWorkflowQuery()
  },
)

watch(
  apiParams,
  () => {
    if (!listReady.value) return
    clearSelection()
    reload()
  },
  { deep: true },
)
</script>
