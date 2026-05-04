<template>
  <div class="mx-auto max-w-6xl space-y-4 pb-6 sm:space-y-6 sm:pb-8">
    <Card :title="t('audit_logs_page.page_main_title')">
      <div class="relative z-40 mb-4">
        <AppFilterBar>
          <div class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
            <details ref="funnelDetailsRef" class="group relative">
              <summary
                class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
              >
                <span class="relative inline-flex">
                  <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" aria-hidden="true" />
                  <span
                    v-if="activeFilterCount > 0"
                    class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-500 px-1 text-[10px] font-bold leading-none text-white"
                  >
                    {{ activeFilterCount }}
                  </span>
                </span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
              </summary>
              <div
                class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[260px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900 dark:shadow-black/30 dark:ring-slate-950/50"
              >
                <p
                  class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300"
                >
                  {{ t('audit_logs_page.funnel_applied') }}
                </p>
                <div class="p-3 pt-2">
                  <ul class="mt-2 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                    <li class="flex justify-between gap-2">
                      <span class="text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.filter_vis_event') }}</span>
                      <span class="max-w-[10rem] truncate text-right font-medium">{{ eventSummaryLabel }}</span>
                    </li>
                    <li v-if="filters.actor_id" class="flex justify-between gap-2">
                      <span class="text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.filter_vis_actor') }}</span>
                      <span class="font-medium">{{ filters.actor_id }}</span>
                    </li>
                    <li v-if="filters.from || filters.to" class="flex justify-between gap-2">
                      <span class="shrink-0 text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.filter_vis_dates') }}</span>
                      <span class="max-w-[12rem] truncate text-right font-medium">{{ dateRangeSummary }}</span>
                    </li>
                    <li v-if="filters.per_page !== 50" class="flex justify-between gap-2">
                      <span class="text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.filter_vis_per_page') }}</span>
                      <span class="font-medium">{{ filters.per_page }}</span>
                    </li>
                    <li v-if="inPageSearchInput.trim()" class="flex justify-between gap-2">
                      <span class="text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.filter_vis_search') }}</span>
                      <span class="max-w-[10rem] truncate text-right font-medium">{{ inPageSearchInput }}</span>
                    </li>
                  </ul>
                  <div class="mt-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">
                      {{ t('audit_logs_page.funnel_show_on_bar') }}
                    </p>
                    <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
                      <li v-for="fd in filterControlDefsLabeled" :key="fd.id" class="flex items-start gap-2">
                        <input
                          :id="`audit-logs-filter-vis-${fd.id}`"
                          v-model="filterControlVisible[fd.id]"
                          type="checkbox"
                          class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30 dark:border-slate-600 dark:bg-slate-900 dark:focus:ring-offset-slate-900"
                        />
                        <label
                          :for="`audit-logs-filter-vis-${fd.id}`"
                          class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                        >
                          {{ fd.label }}
                        </label>
                      </li>
                    </ul>
                  </div>
                  <button
                    type="button"
                    class="mt-3 w-full rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                    @click="resetFilters"
                  >
                    {{ t('audit_logs_page.funnel_clear_all') }}
                  </button>
                </div>
              </div>
            </details>

            <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
              <AppFilterDropdown
                v-if="filterControlVisible.event"
                root-class="shrink-0"
                :label="t('audit_logs_page.event_label')"
                :summary-text="eventSummaryLabel"
                summary-text-class="max-w-[10rem]"
                panel-class="max-h-[min(60vh,320px)] min-w-[220px] overflow-hidden py-1"
              >
                <ul class="max-h-[min(50vh,280px)] space-y-0.5 overflow-y-auto px-1 py-1">
                  <li>
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="
                        !filters.event
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                      "
                      @click="setEventFilter($event, '')"
                    >
                      {{ t('audit_logs_page.event_all') }}
                    </button>
                  </li>
                  <li v-for="e in auditEventPresets" :key="e.value">
                    <button
                      type="button"
                      class="flex w-full rounded-lg px-3 py-2 text-left text-sm transition"
                      :class="
                        filters.event === e.value
                          ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                          : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
                      "
                      @click="setEventFilter($event, e.value)"
                    >
                      {{ e.label }}
                    </button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <AppFilterDropdown
                v-if="filterControlVisible.dates"
                root-class="shrink-0"
                :label="t('audit_logs_page.date_range')"
                :summary-text="dateRangeChip"
                summary-text-class="max-w-[11rem]"
                panel-class="w-[min(100vw-1.5rem,320px)] p-3"
              >
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                  <label class="block min-w-0 flex-1 text-xs font-medium text-slate-600 dark:text-slate-400">
                    <span class="mb-1 block">{{ t('audit_logs_page.from_label') }}</span>
                    <input
                      v-model="filters.from"
                      type="date"
                      class="h-9 w-full rounded-md border border-slate-200 bg-white px-2 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                    />
                  </label>
                  <span class="hidden text-slate-400 sm:inline" aria-hidden="true">—</span>
                  <label class="block min-w-0 flex-1 text-xs font-medium text-slate-600 dark:text-slate-400">
                    <span class="mb-1 block">{{ t('audit_logs_page.to_label') }}</span>
                    <input
                      v-model="filters.to"
                      type="date"
                      class="h-9 w-full rounded-md border border-slate-200 bg-white px-2 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                    />
                  </label>
                </div>
              </AppFilterDropdown>

              <input
                v-if="filterControlVisible.actor"
                v-model="actorDraft"
                type="text"
                inputmode="numeric"
                :aria-label="t('audit_logs_page.actor_label')"
                :placeholder="t('audit_logs_page.actor_ph')"
                class="audit-input h-9 w-[7.5rem] shrink-0 rounded-md sm:w-28"
              />

              <input
                v-if="filterControlVisible.search"
                v-model="inPageSearchInput"
                type="search"
                :aria-label="t('audit_logs_page.search_in_page')"
                :placeholder="t('audit_logs_page.search_in_page_ph')"
                class="audit-input h-9 w-[10rem] shrink-0 sm:w-44"
              />

              <label v-if="filterControlVisible.per_page" class="inline-flex shrink-0 items-center gap-1.5">
                <span class="sr-only">{{ t('audit_logs_page.filter_vis_per_page') }}</span>
                <select
                  v-model.number="filters.per_page"
                  class="h-9 rounded-md border-0 bg-white/90 px-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600"
                  :aria-label="t('audit_logs_page.filter_vis_per_page')"
                  :disabled="loading"
                  @change="onPerPageChange"
                >
                  <option :value="25">25</option>
                  <option :value="50">50</option>
                  <option :value="100">100</option>
                  <option :value="200">200</option>
                </select>
                <span class="hidden whitespace-nowrap text-xs text-slate-500 sm:inline dark:text-slate-400" aria-hidden="true">
                  {{ t('audit_logs_page.per_page_unit') }}
                </span>
              </label>
            </div>

            <div
              class="ml-auto flex shrink-0 items-center gap-1 border-l border-violet-200/70 pl-2 sm:gap-2 sm:pl-3 dark:border-violet-900/40"
            >
              <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 transition hover:bg-white/70 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-slate-200"
                aria-label="Xóa bộ lọc"
                @click="resetFilters"
              >
                <span class="relative inline-flex">
                  <FunnelIcon class="h-5 w-5" aria-hidden="true" />
                  <XMarkIcon
                    class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full bg-white text-rose-500 ring-1 ring-rose-100 dark:bg-slate-900 dark:ring-rose-900/40"
                  />
                </span>
              </button>
            </div>
          </div>
        </AppFilterBar>
      </div>

      <div v-if="loading" class="flex items-center gap-3 py-10 text-sm text-slate-500 dark:text-slate-400">
        <span
          class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-teal-600 dark:border-slate-600 dark:border-t-teal-400"
          aria-hidden="true"
        />
        {{ t('audit_logs_page.loading') }}
      </div>

      <template v-else>
        <div v-if="!displayItems.length" class="rounded-xl border border-dashed border-slate-200 py-12 text-center dark:border-slate-700">
          <p class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ t('audit_logs_page.empty') }}</p>
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-slate-200/90 shadow-sm dark:border-slate-700">
          <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[48rem] border-collapse text-left text-sm">
              <thead>
                <tr class="border-b border-slate-200 bg-slate-50/95 text-slate-600 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-300">
                  <th class="whitespace-nowrap py-2.5 pl-4 pr-3 text-xs font-semibold uppercase tracking-wide">#</th>
                  <th class="whitespace-nowrap py-2.5 pr-3 text-xs font-semibold uppercase tracking-wide">{{ t('audit_logs_page.event_label') }}</th>
                  <th class="whitespace-nowrap py-2.5 pr-3 text-xs font-semibold uppercase tracking-wide">{{ t('audit_logs_page.col_time') }}</th>
                  <th class="min-w-[8rem] whitespace-nowrap py-2.5 pr-3 text-xs font-semibold uppercase tracking-wide">{{ t('audit_logs_page.actor') }}</th>
                  <th class="min-w-[10rem] whitespace-nowrap py-2.5 pr-3 text-xs font-semibold uppercase tracking-wide">{{ t('audit_logs_page.subject') }}</th>
                  <th class="min-w-[12rem] py-2.5 pl-2 pr-4 text-xs font-semibold uppercase tracking-wide">{{ t('audit_logs_page.details') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(l, li) in displayItems"
                  :key="l.id"
                  class="border-b border-slate-100 transition-colors hover:bg-slate-50/80 dark:border-slate-800 dark:hover:bg-slate-800/40"
                  :class="li % 2 === 1 ? 'bg-white dark:bg-transparent' : 'bg-slate-50/30 dark:bg-slate-900/40'"
                >
                  <td class="whitespace-nowrap py-2.5 pl-4 pr-3 align-top text-xs tabular-nums text-slate-500 dark:text-slate-400">
                    {{ l.id }}
                  </td>
                  <td class="py-2.5 pr-3 align-top">
                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ l.event }}</span>
                  </td>
                  <td class="whitespace-nowrap py-2.5 pr-3 align-top text-xs text-slate-500 dark:text-slate-400">
                    {{ formatDate(l.created_at) }}
                  </td>
                  <td class="py-2.5 pr-3 align-top text-sm text-slate-700 dark:text-slate-300">
                    {{ l.actor?.name ?? l.actor_id ?? '—' }}
                  </td>
                  <td class="max-w-[14rem] py-2.5 pr-3 align-top text-xs text-slate-600 dark:text-slate-400">
                    <span class="break-all">{{ l.auditable_type ?? '—' }}#{{ l.auditable_id ?? '' }}</span>
                  </td>
                  <td class="py-2.5 pl-2 pr-4 align-top text-xs leading-snug text-slate-600 dark:text-slate-400">
                    {{ previewMeta(l.metadata) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
            <div v-for="l in displayItems" :key="'m' + l.id" class="p-4">
              <div class="flex flex-wrap items-baseline justify-between gap-2">
                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ l.event }}</span>
                <span class="text-xs text-slate-500 dark:text-slate-400">{{ formatDate(l.created_at) }}</span>
              </div>
              <p class="mt-1 text-xs tabular-nums text-slate-500">#{{ l.id }}</p>
              <dl class="mt-3 space-y-2 text-xs">
                <div class="flex gap-2">
                  <dt class="shrink-0 text-slate-500">{{ t('audit_logs_page.actor') }}</dt>
                  <dd class="min-w-0 font-medium text-slate-800 dark:text-slate-200">{{ l.actor?.name ?? l.actor_id ?? '—' }}</dd>
                </div>
                <div class="flex gap-2">
                  <dt class="shrink-0 text-slate-500">{{ t('audit_logs_page.subject') }}</dt>
                  <dd class="min-w-0 break-all text-slate-600 dark:text-slate-400">{{ l.auditable_type ?? '—' }}#{{ l.auditable_id ?? '' }}</dd>
                </div>
                <div class="flex gap-2">
                  <dt class="shrink-0 text-slate-500">{{ t('audit_logs_page.details') }}</dt>
                  <dd class="min-w-0 text-slate-600 dark:text-slate-400">{{ previewMeta(l.metadata) }}</dd>
                </div>
              </dl>
            </div>
          </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 text-sm dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
          <div class="text-xs text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.total', { n: meta.total ?? 0 }) }}</div>
          <div class="flex flex-wrap items-center gap-2">
            <Button
              variant="secondary"
              :disabled="loading || (meta.current_page ?? 1) <= 1"
              @click="goPage((meta.current_page ?? 1) - 1)"
            >
              {{ t('audit_logs_page.prev') }}
            </Button>
            <span class="text-xs text-slate-500 dark:text-slate-400">
              {{ t('audit_logs_page.page_of', { cur: meta.current_page ?? 1, last: meta.last_page ?? 1 }) }}
            </span>
            <Button
              variant="secondary"
              :disabled="loading || (meta.current_page ?? 1) >= (meta.last_page ?? 1)"
              @click="goPage((meta.current_page ?? 1) + 1)"
            >
              {{ t('audit_logs_page.next') }}
            </Button>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronDownIcon, FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import Card from '../../components/ui/Card.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import Button from '../../components/ui/Button.vue'
import { listAuditLogs } from '../../api/audit'
import { AUDIT_EVENT_PRESETS } from '../../config/systemSeedOptions'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { formatApiError } from '../../api/http'
import { debounceTrailing } from '../../composables/useDebounce'

const AUDIT_FILTER_VIS_KEY = 'va.audit_logs.filter_control_visibility_v1'
const FILTER_CONTROL_IDS = ['event', 'dates', 'actor', 'search', 'per_page']

function defaultFilterControlVisibility() {
  return FILTER_CONTROL_IDS.reduce((acc, id) => {
    acc[id] = true
    return acc
  }, {})
}

const { t, locale } = useI18n()

const loading = ref(false)
const listReady = ref(false)
const items = ref([])
const meta = ref({})
const auditEventPresets = AUDIT_EVENT_PRESETS
const funnelDetailsRef = ref(null)
const filterControlVisible = reactive(defaultFilterControlVisibility())
const actorDraft = ref('')
const inPageSearchInput = ref('')
const inPageQ = ref('')

const filterControlDefs = Object.freeze([
  { id: 'event', labelKey: 'audit_logs_page.filter_vis_event' },
  { id: 'dates', labelKey: 'audit_logs_page.filter_vis_dates' },
  { id: 'actor', labelKey: 'audit_logs_page.filter_vis_actor' },
  { id: 'search', labelKey: 'audit_logs_page.filter_vis_search' },
  { id: 'per_page', labelKey: 'audit_logs_page.filter_vis_per_page' },
])

const filterControlDefsLabeled = computed(() =>
  filterControlDefs.map((d) => ({ id: d.id, label: t(d.labelKey) })),
)

const filters = reactive({
  event: 'api.request',
  actor_id: '',
  from: '',
  to: '',
  page: 1,
  per_page: 50,
})

const eventSummaryLabel = computed(() => {
  if (!filters.event) return t('audit_logs_page.event_all')
  const row = auditEventPresets.find((e) => e.value === filters.event)
  return row?.label ?? filters.event
})

const dateRangeSummary = computed(() => {
  if (!filters.from && !filters.to) return t('audit_logs_page.date_any')
  const a = filters.from || '…'
  const b = filters.to || '…'
  return `${a} — ${b}`
})

const dateRangeChip = computed(() => {
  if (!filters.from && !filters.to) return t('audit_logs_page.date_any')
  return dateRangeSummary.value
})

const activeFilterCount = computed(() => {
  let n = 0
  if (filters.actor_id) n++
  if (filters.from || filters.to) n++
  if (filters.per_page !== 50) n++
  if (inPageSearchInput.value.trim()) n++
  return n
})

const bumpInPageDebounced = debounceTrailing(() => {
  inPageQ.value = inPageSearchInput.value
}, 300)

watch(inPageSearchInput, () => bumpInPageDebounced())

function previewMeta(m) {
  if (!m) return '-'
  const parts = []
  if (m.method) parts.push(`${m.method} ${m.path ?? ''}`.trim())
  if (m.status) parts.push(t('audit_logs_page.meta_status', { status: m.status }))
  if (m.duration_ms != null) parts.push(`${m.duration_ms} ms`)
  return parts.join(' · ') || JSON.stringify(m)
}

function metaMatchesQuery(l, q) {
  const blob = [
    String(l.event ?? ''),
    String(l.actor?.name ?? ''),
    String(l.actor_id ?? ''),
    String(l.auditable_type ?? ''),
    String(l.auditable_id ?? ''),
    previewMeta(l.metadata),
  ]
    .join(' ')
    .toLowerCase()
  return blob.includes(q)
}

const displayItems = computed(() => {
  const q = inPageQ.value.trim().toLowerCase()
  if (!q) return items.value
  return items.value.filter((l) => metaMatchesQuery(l, q))
})

const scheduleReload = debounceTrailing(() => {
  if (!listReady.value) return
  filters.page = 1
  reload(false)
}, 400)

const bumpActorDebounced = debounceTrailing(() => {
  const raw = actorDraft.value.trim()
  const next = raw === '' ? '' : raw
  if (next === filters.actor_id) return
  filters.actor_id = next
  scheduleReload()
}, 400)

watch(actorDraft, () => bumpActorDebounced())

watch(
  () => [filters.event, filters.from, filters.to],
  () => scheduleReload(),
  { deep: true },
)

function loadFilterControlVisibility() {
  try {
    const raw = localStorage.getItem(AUDIT_FILTER_VIS_KEY)
    if (!raw) return
    const o = JSON.parse(raw)
    const base = defaultFilterControlVisibility()
    FILTER_CONTROL_IDS.forEach((id) => {
      if (typeof o[id] === 'boolean') base[id] = o[id]
    })
    Object.assign(filterControlVisible, base)
  } catch {
    /* ignore */
  }
}

watch(
  filterControlVisible,
  () => {
    try {
      localStorage.setItem(AUDIT_FILTER_VIS_KEY, JSON.stringify({ ...filterControlVisible }))
    } catch {
      /* ignore */
    }
  },
  { deep: true },
)

function setEventFilter(ev, value) {
  filters.event = value
  const el = ev.currentTarget
  if (!el?.closest) return
  const d = el.closest('details')
  if (d) d.open = false
}

function onPerPageChange() {
  filters.page = 1
  reload(false)
}

function resetFilters() {
  filters.event = 'api.request'
  filters.actor_id = ''
  filters.from = ''
  filters.to = ''
  filters.per_page = 50
  filters.page = 1
  actorDraft.value = ''
  inPageSearchInput.value = ''
  inPageQ.value = ''
  const el = funnelDetailsRef.value
  if (el) el.open = false
  if (listReady.value) reload(false)
}

function formatDate(v) {
  if (!v) return '-'
  try {
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return new Date(v).toLocaleString(loc)
  } catch {
    return String(v)
  }
}

async function reload(notify = false) {
  loading.value = true
  try {
    const params = { ...filters }
    Object.keys(params).forEach((k) => (params[k] === '' ? delete params[k] : null))
    const res = await listAuditLogs(params)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
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
  loadFilterControlVisibility()
  actorDraft.value = filters.actor_id ? String(filters.actor_id) : ''
  await reload(false)
  listReady.value = true
})
</script>

<style scoped>
.audit-input {
  @apply rounded-md border-0 bg-white/90 px-2.5 text-sm text-slate-900 shadow-sm ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 dark:bg-slate-950 dark:text-slate-100 dark:ring-slate-600 dark:placeholder:text-slate-500;
}
</style>
