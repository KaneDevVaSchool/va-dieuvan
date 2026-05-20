<template>
  <div class="w-full space-y-6 pb-16 text-slate-900 dark:text-slate-100">
    <header class="flex flex-col gap-4 border-b border-slate-200/80 pb-5 dark:border-slate-700/80 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0 space-y-2">
        <RouterLink
          :to="{ name: 'p2pPolicyHub' }"
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
                <input
                  v-if="fd.id === 'class_name' || fd.id === 'policy_type'"
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

    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500 dark:bg-slate-800/80">
          <tr>
            <th class="px-3 py-2">{{ t('p2p_policy_page.col_code') }}</th>
            <th class="px-3 py-2">{{ t('p2p_policy_page.col_name') }}</th>
            <th class="px-3 py-2">{{ t('p2p_policy_page.col_class') }}</th>
            <th class="px-3 py-2">{{ t('p2p_policy_page.col_route') }}</th>
            <th class="px-3 py-2">{{ t('p2p_policy_page.col_policy') }}</th>
            <th class="px-3 py-2">{{ t('p2p_policy_page.col_active') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in items" :key="row.id" class="border-t border-slate-100 dark:border-slate-800">
            <td class="px-3 py-2 font-mono text-xs">{{ row.student_code }}</td>
            <td class="px-3 py-2">{{ row.student_name }}</td>
            <td class="px-3 py-2">{{ row.class_name }}</td>
            <td class="px-3 py-2">{{ row.policy_route?.name }}</td>
            <td class="px-3 py-2">{{ row.policy_type }}</td>
            <td class="px-3 py-2">{{ row.is_active ? '✓' : '—' }}</td>
          </tr>
          <tr v-if="!loading && items.length === 0">
            <td colspan="6" class="px-3 py-8 text-center text-slate-500">{{ t('p2p_policy_page.empty') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-if="meta.total" class="text-xs text-slate-500">{{ meta.total }} {{ t('p2p_policy_page.rows') }}</p>

    <P2pPolicyStudentImportDialog
      ref="importDialog"
      :p2p-policy-term-id="filters.p2p_policy_term_id"
      @committed="reload"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { ArrowLeftIcon, ChevronDownIcon, FunnelIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import P2pPolicyStudentImportDialog from '../../components/p2pPolicy/P2pPolicyStudentImportDialog.vue'
import Card from '../../components/ui/Card.vue'
import { useP2pPolicyStudentFilters } from '../../composables/useP2pPolicyStudentFilters'
import { showAppError } from '../../composables/appMessage'
import { formatApiError } from '../../api/http'
import { useAuthStore } from '../../store'
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
const auth = useAuthStore()
const { filters, visibility, apiParams, activeFilterCount, clearFilters, filterDefs } = useP2pPolicyStudentFilters()

const canImportExport = computed(() => auth.hasPermission('p2p_policy.import_export'))

const items = ref([])
const meta = ref({ total: 0 })
const loading = ref(false)
const exporting = ref(false)
const routes = ref([])
const campuses = ref([])
const academicTerms = ref([])
const p2pTerms = ref([])
const importDialog = ref(null)

const weekdays = [
  { v: 1, l: 'T2' },
  { v: 2, l: 'T3' },
  { v: 3, l: 'T4' },
  { v: 4, l: 'T5' },
  { v: 5, l: 'T6' },
  { v: 6, l: 'T7' },
  { v: 7, l: 'CN' },
]

function p2pTermLabel(pt) {
  const year = pt.academic_term?.academic_year ?? pt.academic_year ?? ''
  const status = pt.status ?? ''
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

onMounted(async () => {
  const [r, c, at, pt] = await Promise.all([
    listPolicyRoutes({ per_page: 100 }),
    listCampuses({ per_page: 100 }),
    listAcademicTerms({ per_page: 100 }),
    listP2pPolicyTerms({ per_page: 50 }),
  ])
  routes.value = r.items ?? []
  campuses.value = c.items ?? []
  academicTerms.value = at.items ?? []
  p2pTerms.value = pt.items ?? []
  if (p2pTerms.value[0] && !filters.p2p_policy_term_id) {
    filters.p2p_policy_term_id = p2pTerms.value[0].id
  }
  await reload()
})

watch(apiParams, reload, { deep: true })
</script>
