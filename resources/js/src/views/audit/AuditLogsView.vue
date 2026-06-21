<template>
  <div class="space-y-6 pb-10 min-w-0">

    <div class="flex flex-col gap-3 border-b border-slate-200/80 pb-6 lg:flex-row lg:items-end lg:justify-between dark:border-slate-700">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">
          {{ t('audit_logs_page.page_main_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.hero_subtitle') }}</p>
      </div>
      <button
        type="button"
        class="inline-flex h-10 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
        :disabled="loading"
        data-testid="audit-logs-reload"
        @click="reload(true)"
      >
        <ArrowPathIcon class="h-4 w-4" :class="loading ? 'animate-spin' : ''" aria-hidden="true" />
        {{ t('audit_logs_page.reload') }}
      </button>
    </div>

    <AuditLogsSummaryBar
      :summary="summary"
      :loading="loading"
      :active-category="activeCategory"
      @quick-filter="onKpiQuickFilter"
    />

    <div
      class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
    >
      <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
        <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
          <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
            <DatagridToolbarSearch
              v-model="searchInput"
              input-id="audit-logs-search"
              :placeholder="t('audit_logs_page.search_in_page_ph')"
              :aria-label="t('audit_logs_page.search_aria')"
              stretch
              inline-actions
              hide-label
              input-height="h-10"
            />
          </div>

          <div class="flex shrink-0 items-center gap-2">
            <FilterVisibilityDropdown
              :open="showFilterPanelDd"
              :title="t('audit_logs_page.funnel_show_on_bar')"
              :hint="t('audit_logs_page.filter_panel_hint')"
              @close="closeFilterPanel"
            >
              <template #trigger>
                <DatagridToolbarActionButton
                  icon="filter"
                  :active="showFilterPanelDd"
                  test-id="audit-toolbar-filter"
                  @click="toggleFilterPanel"
                >
                  {{ t('audit_logs_page.toolbar_filter') }}
                </DatagridToolbarActionButton>
              </template>
              <li v-for="fd in filterControlDefs" :key="'audit-vis-' + fd.key" class="flex items-start gap-2">
                <input
                  :id="`audit-filter-vis-${fd.key}`"
                  v-model="visibleFilters[fd.key]"
                  type="checkbox"
                  class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                  :data-testid="`audit-filter-vis-${fd.key}`"
                />
                <label
                  :for="`audit-filter-vis-${fd.key}`"
                  class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                >
                  {{ fd.label }}
                </label>
              </li>
            </FilterVisibilityDropdown>
          </div>

          <div class="ml-auto flex shrink-0 items-center">
            <button
              type="button"
              class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 transition hover:bg-slate-50 hover:text-slate-800 dark:hover:bg-slate-800"
              :title="t('audit_logs_page.funnel_clear_all')"
              data-testid="audit-reset-filters"
              @click="resetFilters"
            >
              <FunnelIcon class="h-5 w-5" aria-hidden="true" />
              <XMarkIcon class="h-3 w-3 text-rose-500" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="hasFilterRow"
        class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
      >
        <div v-if="visibleFilters.category" class="xl:col-span-6">
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="cat in categoryOptions"
              :key="cat.key"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium transition"
              :class="activeCategory === cat.key
                ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900'
                : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'"
              :data-testid="`audit-category-${cat.key}`"
              @click="setCategory(cat.key)"
            >
              <span aria-hidden="true">{{ cat.icon }}</span>
              {{ cat.label }}
            </button>
          </div>
        </div>

        <DatagridFilterField v-if="visibleFilters.event">
          <select
            v-model="filterEvent"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('audit_logs_page.filter_vis_event')"
            data-testid="audit-filter-event"
            @change="onServerFilterChange"
          >
            <option value="">{{ t('audit_logs_page.event_label') }}</option>
            <option v-for="ev in eventFilterOptions" :key="ev.value" :value="ev.value">{{ ev.label }}</option>
          </select>
        </DatagridFilterField>

        <DatagridFilterField v-if="visibleFilters.subject">
          <select
            v-model="filterSubjectType"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('audit_logs_page.filter_subject_aria')"
            data-testid="audit-filter-subject"
            @change="onServerFilterChange"
          >
            <option v-for="opt in subjectTypeOptions" :key="opt.value === '' ? '_subj_any' : opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
        </DatagridFilterField>

        <div v-if="visibleFilters.date_range" class="min-w-0 w-full sm:col-span-2 xl:col-span-2">
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <FilterDatePicker
              v-model="filters.from"
              :placeholder="t('audit_logs_page.from_label')"
              :max-date="filters.to || null"
              input-id="audit-filter-from"
              @update:model-value="onDateFilterChange"
            />
            <FilterDatePicker
              v-model="filters.to"
              :placeholder="t('audit_logs_page.to_label')"
              :min-date="filters.from || null"
              input-id="audit-filter-to"
              @update:model-value="onDateFilterChange"
            />
          </div>
        </div>

        <DatagridFilterField v-if="visibleFilters.per_page">
          <select
            v-model.number="filters.per_page"
            :class="FILTER_CONTROL_CLASS"
            :aria-label="t('audit_logs_page.filter_vis_per_page')"
            data-testid="audit-filter-per-page"
            @change="onServerFilterChange"
          >
            <option :value="DEFAULT_AUDIT_PER_PAGE">{{ t('audit_logs_page.filter_vis_per_page') }}</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
            <option :value="200">200</option>
          </select>
        </DatagridFilterField>
      </div>

      <div v-if="loading" class="space-y-2 p-4 sm:p-5">
        <div v-for="i in 5" :key="i" class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
          <div class="h-9 w-9 shrink-0 animate-pulse rounded-full bg-slate-200 dark:bg-slate-700" />
          <div class="flex-1 space-y-2 pt-0.5">
            <div class="h-4 w-3/5 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
            <div class="h-3 w-2/5 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
          </div>
        </div>
      </div>

      <template v-else>
        <div
          v-if="!groupedItems.length"
          class="flex flex-col items-center justify-center border-t border-slate-100 py-16 text-center dark:border-slate-700"
        >
          <ClockIcon class="mb-3 h-12 w-12 text-slate-300 dark:text-slate-600" />
          <p class="text-base font-semibold text-slate-600 dark:text-slate-400">{{ t('audit_logs_page.empty_feed') }}</p>
          <p class="mt-1 text-sm text-slate-400 dark:text-slate-500">{{ t('audit_logs_page.empty_feed_hint') }}</p>
        </div>

        <div v-else class="space-y-5 border-t border-slate-100 p-4 sm:p-5 dark:border-slate-700">
          <div v-for="group in groupedItems" :key="group.date">
            <div class="mb-2 flex items-center gap-3">
              <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ group.label }}</span>
              <div class="h-px flex-1 bg-slate-100 dark:bg-slate-800" />
            </div>

            <div class="space-y-1.5">
              <button
                v-for="log in group.items"
                :key="log.id"
                type="button"
                class="group grid w-full cursor-pointer grid-cols-[auto_minmax(0,1fr)_auto] items-start gap-3 rounded-2xl border border-slate-200/60 bg-white px-4 py-3.5 text-left transition hover:border-slate-300 hover:shadow-sm dark:border-slate-700/60 dark:bg-slate-900 dark:hover:border-slate-600"
                :data-testid="`audit-log-row-${log.id}`"
                @click="goDetail(log)"
              >
                <div
                  class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                  :class="eventAvatarClass(log.event)"
                  :aria-hidden="true"
                >
                  <BoltIcon v-if="isSystemActor(log)" class="h-4 w-4" />
                  <span v-else>{{ actorInitials(log) }}</span>
                </div>

                <div class="min-w-0">
                  <p class="text-sm leading-snug text-slate-800 dark:text-slate-200">
                    <span class="font-semibold">{{ actorDisplayName(log) }}</span>
                    <span class="px-1 text-slate-300 dark:text-slate-600" aria-hidden="true">·</span>
                    <span class="text-slate-600 dark:text-slate-400">{{ actionVerb(log.event) }}</span>
                    <template v-if="subjectText(log)">
                      <span class="px-1 text-slate-300 dark:text-slate-600" aria-hidden="true">·</span>
                      <span class="font-medium text-slate-700 dark:text-slate-300">{{ subjectText(log) }}</span>
                    </template>
                  </p>
                  <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                    <span class="text-xs text-slate-400 dark:text-slate-500">{{ timeAgo(log.created_at) }}</span>
                    <span
                      v-if="log.metadata?.status && log.metadata.status >= 400"
                      class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium"
                      :class="log.metadata.status >= 500 ? 'bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400'"
                    >
                      <ExclamationCircleIcon class="h-3 w-3" aria-hidden="true" />
                      {{ t('audit_logs_page.meta_status', { status: log.metadata.status }) }}
                    </span>
                  </div>
                </div>

                <div class="shrink-0 text-right">
                  <span
                    class="inline-flex max-w-[10rem] items-center gap-1.5 truncate rounded-2xl px-3 py-1.5 text-[11px] font-semibold sm:max-w-none sm:gap-2 sm:px-4 sm:py-2 sm:text-sm"
                    :class="eventBadgeClass(log.event)"
                    :title="eventLabel(log.event)"
                  >
                    <component :is="eventIcon(log.event)" class="h-3.5 w-3.5 shrink-0 sm:h-4 sm:w-4" aria-hidden="true" />
                    <span class="truncate">{{ eventLabel(log.event) }}</span>
                  </span>
                  <ChevronRightIcon class="mx-auto mt-1 h-3.5 w-3.5 text-slate-300 transition group-hover:text-slate-400 dark:text-slate-600" aria-hidden="true" />
                </div>
              </button>
            </div>
          </div>
        </div>

        <div
          v-if="meta.last_page > 1"
          class="flex items-center justify-between border-t border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5"
        >
          <p class="text-xs text-slate-400 dark:text-slate-500">
            {{ t('audit_logs_page.total_activities', { n: (meta.total ?? 0).toLocaleString(locale === 'en' ? 'en-US' : 'vi-VN') }) }}
          </p>
          <div class="flex items-center gap-1">
            <button
              type="button"
              class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
              :disabled="(meta.current_page ?? 1) <= 1"
              data-testid="audit-pagination-prev"
              @click="goPage((meta.current_page ?? 1) - 1)"
            >
              <ChevronLeftIcon class="h-4 w-4" aria-hidden="true" />
            </button>
            <span class="min-w-[4.5rem] text-center text-xs font-medium text-slate-600 dark:text-slate-300">
              {{ t('audit_logs_page.page_of', { cur: meta.current_page ?? 1, last: meta.last_page ?? 1 }) }}
            </span>
            <button
              type="button"
              class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
              :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1)"
              data-testid="audit-pagination-next"
              @click="goPage((meta.current_page ?? 1) + 1)"
            >
              <ChevronRightIcon class="h-4 w-4" aria-hidden="true" />
            </button>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import {
  ArrowPathIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClockIcon,
  FunnelIcon,
  XMarkIcon,
  ExclamationCircleIcon,
  BoltIcon,
} from '@heroicons/vue/24/outline'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../../components/shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'
import FilterDatePicker from '../../components/shared/ui/FilterDatePicker.vue'
import AuditLogsSummaryBar from '../../components/audit/AuditLogsSummaryBar.vue'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import { useAuditLogPresentation, AUDIT_CATEGORY_EVENTS } from '../../composables/useAuditLogPresentation.js'
import { listAuditLogs } from '../../api/audit'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { formatApiError } from '../../api/http'
import { debounceTrailing } from '../../composables/useDebounce'

const { t, locale } = useI18n()
const router = useRouter()

const {
  eventLabel,
  actionVerb,
  subjectText,
  actorDisplayName,
  isSystemActor,
  eventAvatarClass,
  eventBadgeClass,
  eventIcon,
  actorInitials,
} = useAuditLogPresentation()

const FILTER_CONTROL_CLASS = 'input h-10 w-full text-sm'

const AUDIT_FILTER_CONTROLS = [
  { key: 'category', label: t('audit_logs_page.filter_vis_category'), default: false },
  { key: 'date_range', label: t('audit_logs_page.filter_vis_dates'), default: false },
  { key: 'event', label: t('audit_logs_page.filter_vis_event'), default: false },
  { key: 'subject', label: t('audit_logs_page.filter_vis_subject'), default: false },
  { key: 'per_page', label: t('audit_logs_page.filter_vis_per_page'), default: false },
]

const categoryOptions = computed(() => [
  { key: 'all', label: t('audit_logs_page.category_all'), icon: '📋' },
  { key: 'request', label: t('audit_logs_page.category_request'), icon: '📝' },
  { key: 'file', label: t('audit_logs_page.category_file'), icon: '📎' },
  { key: 'alert', label: t('audit_logs_page.category_alert'), icon: '⚠️' },
  { key: 'system', label: t('audit_logs_page.category_system'), icon: '🔧' },
])

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
  filterControlDefs,
} = useVisibleFilterControls(
  AUDIT_FILTER_CONTROLS,
  'va-dieuvan.audit-logs.visible-filters.v1',
)

function toggleFilterPanel() {
  openFilterPanel()
}

const eventFilterOptions = computed(() =>
  Object.keys(AUDIT_CATEGORY_EVENTS).flatMap((cat) => AUDIT_CATEGORY_EVENTS[cat]).map((value) => ({
    value,
    label: eventLabel(value),
  })),
)

const AUDIT_SUBJECT_TYPES = [
  { value: 'App\\Models\\DispatchRequest', labelKey: 'audit_logs_page.subjects.DispatchRequest' },
  { value: 'App\\Models\\Trip', labelKey: 'audit_logs_page.subjects.Trip' },
  { value: 'App\\Models\\Attachment', labelKey: 'audit_logs_page.subjects.Attachment' },
  { value: 'App\\Models\\User', labelKey: 'audit_logs_page.subjects.User' },
  { value: 'App\\Models\\Vehicle', labelKey: 'audit_logs_page.subjects.Vehicle' },
  { value: 'App\\Models\\Driver', labelKey: 'audit_logs_page.subjects.Driver' },
  { value: 'App\\Models\\CargoShipment', labelKey: 'audit_logs_page.subjects.CargoShipment' },
]

const subjectTypeOptions = computed(() => [
  { value: '', label: t('audit_logs_page.filter_subject_placeholder') },
  ...AUDIT_SUBJECT_TYPES.map((o) => ({ value: o.value, label: t(o.labelKey) })),
])

const loading = ref(false)
const listReady = ref(false)
const items = ref([])
const meta = ref({})
const summary = ref({ total: 0, request: 0, file: 0, alert: 0, system: 0 })
const activeCategory = ref('all')
const searchInput = ref('')
const searchQ = ref('')

const DEFAULT_AUDIT_PER_PAGE = 50
const filters = reactive({ from: '', to: '', page: 1, per_page: DEFAULT_AUDIT_PER_PAGE })
const filterEvent = ref('')
const filterSubjectType = ref('')

const bumpSearch = debounceTrailing(() => { searchQ.value = searchInput.value }, 350)
watch(searchInput, () => bumpSearch())

function itemMatchesSearch(log, q) {
  if (!q) return true
  const blob = [
    actorDisplayName(log),
    log.actor?.employee_code ?? '',
    log.actor_id ?? '',
    eventLabel(log.event),
    actionVerb(log.event),
    subjectText(log) ?? '',
    log.auditable_type ?? '',
    String(log.auditable_id ?? ''),
  ].join(' ').toLowerCase()
  return blob.includes(q)
}

const filteredItems = computed(() => {
  const q = searchQ.value.trim().toLowerCase()
  if (!q) return items.value
  return items.value.filter((l) => itemMatchesSearch(l, q))
})

const groupedItems = computed(() => {
  const groups = []
  const seen = new Map()
  for (const log of filteredItems.value) {
    const key = dateGroupKey(log.created_at)
    const label = dateGroupLabel(log.created_at)
    if (!seen.has(key)) {
      seen.set(key, groups.length)
      groups.push({ date: key, label, items: [] })
    }
    groups[seen.get(key)].items.push(log)
  }
  return groups
})

function dateGroupKey(v) {
  if (!v) return 'unknown'
  try { return new Date(v).toISOString().slice(0, 10) } catch { return 'unknown' }
}

function dateGroupLabel(v) {
  if (!v) return t('audit_logs_page.date_unknown')
  try {
    const d = new Date(v)
    const now = new Date()
    const diff = Math.floor((now - d) / 86400000)
    if (diff === 0) return t('audit_logs_page.date_today')
    if (diff === 1) return t('audit_logs_page.date_yesterday')
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return d.toLocaleDateString(loc, { weekday: 'long', day: 'numeric', month: 'long' })
  } catch { return String(v) }
}

function timeAgo(v) {
  if (!v) return t('audit_logs_page.detail_empty')
  try {
    const diffMs = Date.now() - new Date(v)
    const diffMins = Math.floor(diffMs / 60000)
    const diffH = Math.floor(diffMins / 60)
    const diffD = Math.floor(diffH / 24)
    if (diffMins < 1) return t('audit_logs_page.time_just_now')
    if (diffMins < 60) return t('audit_logs_page.time_minutes_ago', { n: diffMins })
    if (diffH < 24) return t('audit_logs_page.time_hours_ago', { n: diffH })
    if (diffD <= 1) return t('audit_logs_page.date_yesterday')
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return new Date(v).toLocaleTimeString(loc, { hour: '2-digit', minute: '2-digit' })
  } catch { return t('audit_logs_page.detail_empty') }
}

const scheduleReload = debounceTrailing(() => {
  if (!listReady.value) return
  filters.page = 1
  reload(false)
}, 350)

function onDateFilterChange() {
  scheduleReload()
}

watch(() => [filters.from, filters.to], () => scheduleReload())

function onServerFilterChange() {
  filters.page = 1
  if (listReady.value) reload(false)
}

function setCategory(key) {
  activeCategory.value = key
  filters.page = 1
  if (listReady.value) reload(false)
}

function onKpiQuickFilter({ category }) {
  setCategory(category ?? 'all')
}

function resetFilters() {
  activeCategory.value = 'all'
  searchInput.value = ''
  searchQ.value = ''
  filters.from = ''
  filters.to = ''
  filterEvent.value = ''
  filterSubjectType.value = ''
  filters.per_page = DEFAULT_AUDIT_PER_PAGE
  filters.page = 1
  if (listReady.value) reload(false)
}

function goDetail(log) {
  router.push({ name: 'systemAuditDetail', params: { id: log.id } })
}

async function reload(notify = false) {
  loading.value = true
  try {
    const params = { page: filters.page, per_page: filters.per_page }
    if (filters.from) params.from = filters.from
    if (filters.to) params.to = filters.to
    if (filterEvent.value) {
      params.event = filterEvent.value
    } else if (activeCategory.value !== 'all') {
      params.events = AUDIT_CATEGORY_EVENTS[activeCategory.value] ?? []
    }
    if (filterSubjectType.value) params.auditable_type = filterSubjectType.value

    const res = await listAuditLogs(params)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
    summary.value = res.summary ?? summary.value
    if (notify) showAppSuccess(t('audit_logs_page.reload_success'), t('audit_logs_page.reload_success_title'))
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  filters.page = p
  reload(false)
}

onMounted(async () => {
  await reload(false)
  listReady.value = true
})
</script>
