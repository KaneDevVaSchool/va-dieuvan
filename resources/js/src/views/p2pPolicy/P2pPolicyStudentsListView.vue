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
        <p class="max-w-3xl text-base leading-relaxed text-slate-600 dark:text-slate-400">
          {{ t('p2p_policy_page.students_subtitle') }}
        </p>
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
      <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
        {{ t('p2p_policy_page.import_term_hint') }}
        <strong class="text-slate-900 dark:text-white">{{ selectedP2pTermLabel }}</strong>
      </p>
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
              <button type="button" class="mt-3 w-full rounded-xl border px-3 py-2 text-sm" @click="clearFilters">
                {{ t('p2p_policy_page.clear_filters') }}
              </button>
            </div>
          </details>

          <template v-for="fd in filterDefs" :key="fd.id">
            <details v-if="visibility[fd.id]" class="group relative min-w-0 shrink-0">
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
                  @change="reload"
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
                  @change="reload"
                />
                <select
                  v-else-if="fd.id === 'is_active'"
                  v-model="filters.is_active"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="reload"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option value="true">{{ t('p2p_policy_page.active_yes') }}</option>
                  <option value="false">{{ t('p2p_policy_page.active_no') }}</option>
                </select>
                <select
                  v-else-if="fd.id === 'weekday_iso'"
                  v-model="filters.weekday_iso"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="reload"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option v-for="d in weekdays" :key="d.v" :value="d.v">{{ d.l }}</option>
                </select>
                <select
                  v-else-if="fd.id === 'p2p_policy_term_id'"
                  v-model="filters.p2p_policy_term_id"
                  class="w-full max-h-48 overflow-y-auto rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="reload"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option v-for="pt in p2pTerms" :key="pt.id" :value="pt.id">{{ p2pTermLabel(pt) }}</option>
                </select>
                <select
                  v-else-if="fd.id === 'policy_route_id'"
                  v-model="filters.policy_route_id"
                  class="w-full max-h-48 w-full overflow-y-auto rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="reload"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option v-for="r in routes" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
                <select
                  v-else-if="fd.id === 'campus_id'"
                  v-model="filters.campus_id"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="reload"
                >
                  <option value="">{{ t('p2p_policy_page.filter_any') }}</option>
                  <option v-for="c in campuses" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <select
                  v-else-if="fd.id === 'academic_term_id'"
                  v-model="filters.academic_term_id"
                  class="w-full rounded-md border border-slate-200 px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-800"
                  @change="reload"
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
            @keydown.enter="reload"
          />
        </div>
      </AppFilterBar>
    </div>

    <div v-if="loading" class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/50">
      {{ t('common.processing') }}
    </div>

    <div v-else class="space-y-4">
      <div v-if="studentGroups.length" class="flex flex-wrap justify-end gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          @click="expandAllGroups"
        >
          {{ t('p2p_policy_page.students_expand_all') }}
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          @click="collapseAllGroups"
        >
          {{ t('p2p_policy_page.students_collapse_all') }}
        </button>
      </div>

      <div
        v-for="group in studentGroups"
        :key="group.key"
        class="overflow-hidden rounded-2xl border border-slate-200/90 shadow-sm dark:border-slate-700/80"
      >
        <button
          type="button"
          class="flex w-full flex-wrap items-center gap-3 border-b border-teal-200/60 bg-gradient-to-r from-teal-50 via-white to-sky-50/80 px-4 py-3 text-left transition hover:from-teal-100/80 dark:border-teal-900/40 dark:from-teal-950/30 dark:via-slate-900 dark:to-sky-950/20 dark:hover:from-teal-950/50"
          :aria-expanded="isGroupOpen(group.key)"
          @click="toggleGroup(group.key)"
        >
          <ChevronDownIcon
            class="h-5 w-5 shrink-0 text-teal-700 transition-transform dark:text-teal-300"
            :class="isGroupOpen(group.key) ? 'rotate-0' : '-rotate-90'"
            aria-hidden="true"
          />
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-600 text-white shadow-sm">
            <UserGroupIcon class="h-5 w-5" aria-hidden="true" />
          </span>
          <div class="min-w-0 flex-1">
            <p class="text-base font-bold text-slate-900 dark:text-white">{{ group.routeName }}</p>
            <p v-if="group.campusLine" class="text-xs text-slate-600 dark:text-slate-400">{{ group.campusLine }}</p>
          </div>
          <span class="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-teal-900 ring-1 ring-teal-200 dark:bg-slate-900/80 dark:text-teal-100 dark:ring-teal-800">
            {{ t('p2p_policy_page.students_group_count', { count: group.rows.length }) }}
          </span>
        </button>
        <div v-show="isGroupOpen(group.key)" class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50/90 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800/60">
              <tr>
                <th class="px-4 py-2.5">{{ t('p2p_policy_page.col_code') }}</th>
                <th class="px-4 py-2.5">{{ t('p2p_policy_page.col_name') }}</th>
                <th class="px-4 py-2.5">{{ t('p2p_policy_page.col_class') }}</th>
                <th class="px-4 py-2.5">{{ t('p2p_policy_page.col_direction') }}</th>
                <th class="px-4 py-2.5">{{ t('p2p_policy_page.col_policy') }}</th>
                <th class="px-4 py-2.5">{{ t('p2p_policy_page.col_active') }}</th>
                <th v-if="canManage" class="w-[5rem] px-4 py-2.5 text-right">{{ t('p2p_policy_page.col_actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in group.rows"
                :key="row.id"
                class="border-t border-slate-100 transition hover:bg-slate-50/80 dark:border-slate-800 dark:hover:bg-slate-800/40"
                :class="idx % 2 === 1 ? 'bg-slate-50/40 dark:bg-slate-900/20' : ''"
              >
                <td class="px-4 py-2.5 font-mono text-xs font-medium text-slate-800 dark:text-slate-200">{{ row.student_code }}</td>
                <td class="px-4 py-2.5 font-medium text-slate-900 dark:text-white">{{ row.student_name }}</td>
                <td class="px-4 py-2.5 text-slate-700 dark:text-slate-300">{{ row.class_name || '—' }}</td>
                <td class="px-4 py-2.5">
                  <span
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                    :class="directionBadgeClass(row.direction)"
                  >
                    {{ directionLabel(t, row.direction) }}
                  </span>
                </td>
                <td class="px-4 py-2.5">
                  <span
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                    :class="policyTypeBadgeClass(row.policy_type)"
                  >
                    {{ policyTypeLabel(t, row.policy_type) }}
                  </span>
                </td>
                <td class="px-4 py-2.5">
                  <span
                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="row.is_active
                      ? 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-100'
                      : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
                  >
                    {{ row.is_active ? t('p2p_policy_page.active_yes') : t('p2p_policy_page.active_no') }}
                  </span>
                </td>
                <td v-if="canManage" class="px-4 py-2.5 text-right">
                  <button
                    type="button"
                    class="rounded-lg border border-teal-200 bg-teal-50 px-2.5 py-1 text-xs font-semibold text-teal-900 hover:bg-teal-100 dark:border-teal-800 dark:bg-teal-950/50 dark:text-teal-100"
                    @click.stop="openEdit(row)"
                  >
                    {{ t('p2p_policy_page.students_edit') }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div
        v-if="!studentGroups.length"
        class="rounded-2xl border border-dashed border-slate-300 px-6 py-12 text-center text-slate-500 dark:border-slate-600"
      >
        {{ t('p2p_policy_page.empty') }}
      </div>
    </div>

    <p v-if="meta.total" class="text-xs text-slate-500">{{ meta.total }} {{ t('p2p_policy_page.rows') }}</p>

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
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { ArrowLeftIcon, ChevronDownIcon, FunnelIcon, UserGroupIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import P2pPolicyStudentEditDialog from '../../components/p2pPolicy/P2pPolicyStudentEditDialog.vue'
import P2pPolicyStudentImportDialog from '../../components/p2pPolicy/P2pPolicyStudentImportDialog.vue'
import P2pPolicyWorkflowBar from '../../components/p2pPolicy/P2pPolicyWorkflowBar.vue'
import Card from '../../components/ui/Card.vue'
import { useP2pPolicyStudentFilters } from '../../composables/useP2pPolicyStudentFilters'
import {
  p2pStepTo,
  p2pWorkflowQuery,
  resolveP2pTermIdFromRoute,
} from '../../composables/useP2pPolicyWorkflow'
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
const { filters, visibility, apiParams, activeFilterCount, clearFilters, filterDefs } = useP2pPolicyStudentFilters()

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

const canImportExport = computed(() => auth.hasPermission('p2p_policy.import_export'))
const canManage = computed(() => auth.hasPermission('p2p_policy.manage'))

const openGroups = reactive({})

const items = ref([])
const meta = ref({ total: 0 })
const loading = ref(false)
const exporting = ref(false)
const routes = ref([])
const campuses = ref([])
const academicTerms = ref([])
const p2pTerms = ref([])
const importDialog = ref(null)
const editDialog = ref(null)

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

const studentGroups = computed(() => {
  const map = new Map()
  for (const row of items.value) {
    const route = row.policy_route
    const key = route?.id ?? 'none'
    if (!map.has(key)) {
      const origin = route?.origin_campus?.name
      const dest = route?.dest_campus?.name
      const campusLine = origin && dest ? `${origin} → ${dest}` : ''
      map.set(key, {
        key,
        routeName: route?.name ?? t('p2p_policy_page.students_ungrouped'),
        campusLine,
        rows: [],
      })
    }
    map.get(key).rows.push(row)
  }
  return [...map.values()].sort((a, b) => a.routeName.localeCompare(b.routeName, 'vi'))
})

function isGroupOpen(key) {
  return openGroups[key] !== false
}

function toggleGroup(key) {
  openGroups[key] = !isGroupOpen(key)
}

function expandAllGroups() {
  for (const g of studentGroups.value) {
    openGroups[g.key] = true
  }
}

function collapseAllGroups() {
  for (const g of studentGroups.value) {
    openGroups[g.key] = false
  }
}

watch(
  studentGroups,
  (groups) => {
    for (const g of groups) {
      if (!(g.key in openGroups)) {
        openGroups[g.key] = true
      }
    }
  },
  { immediate: true },
)

function openEdit(row) {
  editDialog.value?.open(row)
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
    meta.value = res.meta ?? { total: 0 }
  } finally {
    loading.value = false
  }
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
    syncWorkflowQuery()
  },
)

watch(apiParams, reload, { deep: true })
</script>
