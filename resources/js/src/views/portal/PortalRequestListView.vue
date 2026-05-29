<template>
  <div>
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-10">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <RouterLink
            :to="{ name: portalRoutes.home }"
            class="text-xs font-semibold text-indigo-600 underline-offset-2 hover:underline"
          >
            ←
            {{
              isExtracurricularModule
                ? t('portal.extracurricular_module.back_hub')
                : t('portal.back_dashboard')
            }}
          </RouterLink>
          <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">
            {{
              isExtracurricularModule
                ? t('portal.extracurricular_module.list_heading')
                : t('portal.list_page_heading')
            }}
          </h1>
          <p class="mt-2 text-sm text-slate-600">
            {{
              isExtracurricularModule
                ? t('portal.extracurricular_module.list_lead')
                : t('portal.list_page_lead')
            }}
          </p>
        </div>
        <RouterLink
          :to="{ name: portalRoutes.create }"
          class="inline-flex min-h-[48px] shrink-0 items-center justify-center rounded-2xl bg-indigo-600 px-6 text-sm font-bold text-white shadow-md hover:bg-indigo-700"
        >
          {{ t('portal.cta_primary') }}
        </RouterLink>
      </div>

      <!-- Filter bar -->
      <div class="relative z-40 mt-8">
        <AppFilterBar>
          <div class="flex flex-col gap-2">
            <!-- Search — full width on all breakpoints -->
            <div class="relative w-full min-w-0">
              <label class="sr-only" for="portal-list-q">{{ t('portal.search_placeholder') }}</label>
              <MagnifyingGlassIcon
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                aria-hidden="true"
              />
              <input
                id="portal-list-q"
                v-model="searchInput"
                type="search"
                autocomplete="off"
                role="combobox"
                :aria-expanded="showSearchSuggest"
                aria-controls="portal-list-q-suggest"
                aria-autocomplete="list"
                :aria-label="t('portal.search_placeholder')"
                :placeholder="t('portal.search_placeholder')"
                :title="t('portal.search_placeholder')"
                class="portal-list-q h-10 w-full rounded-lg border-0 bg-white/90 py-2 pl-9 pr-3 text-sm ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
                @input="onSearchInput"
                @focus="onSearchFocus"
                @blur="onSearchBlur"
                @keydown.down.prevent="moveSearchSuggest(1)"
                @keydown.up.prevent="moveSearchSuggest(-1)"
                @keydown.enter.prevent="commitSearchSuggest"
                @keydown.escape="closeSearchSuggest"
              />
              <div
                v-if="searchSuggestLoading"
                class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 animate-spin rounded-full border-2 border-slate-200 border-t-teal-600"
                aria-hidden="true"
              />
              <ul
                v-if="showSearchSuggest"
                id="portal-list-q-suggest"
                class="absolute z-[110] mt-1 max-h-56 w-full overflow-auto rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg ring-1 ring-slate-900/5"
                role="listbox"
                :aria-label="t('portal.search_suggest_aria')"
              >
                <li v-if="searchSuggestLoading" class="px-3 py-2.5 text-slate-500">
                  {{ t('portal.search_suggest_loading') }}
                </li>
                <template v-else-if="searchSuggestions.length">
                  <li v-for="(req, idx) in searchSuggestions" :key="req.id" role="presentation">
                    <button
                      type="button"
                      role="option"
                      :aria-selected="idx === searchSuggestFocus"
                      :class="[
                        'flex w-full flex-col gap-0.5 px-3 py-2.5 text-left transition',
                        idx === searchSuggestFocus ? 'bg-teal-50' : 'hover:bg-slate-50',
                      ]"
                      @mousedown.prevent="pickSearchSuggestion(req)"
                    >
                      <span class="font-mono text-sm font-semibold text-slate-900">#{{ req.id }}</span>
                      <span class="truncate text-xs text-slate-500">{{ suggestRouteLine(req) }}</span>
                    </button>
                  </li>
                </template>
                <li v-else class="px-3 py-2.5 text-slate-500">{{ t('portal.search_suggest_empty') }}</li>
              </ul>
            </div>

            <div class="flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">

            <!-- Funnel / phễu -->
            <details ref="funnelRef" class="group relative">
              <summary
                class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md [&::-webkit-details-marker]:hidden"
                :aria-label="t('portal.filter_toolbar_label')"
              >
                <span class="relative inline-flex">
                  <FunnelIcon class="h-5 w-5 text-slate-600" aria-hidden="true" />
                  <span
                    v-if="activeFilterCount > 0"
                    class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-500 px-1 text-[10px] font-bold leading-none text-white"
                  >
                    {{ activeFilterCount > 9 ? '9+' : activeFilterCount }}
                  </span>
                </span>
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
              </summary>
              <div
                class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[260px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5"
              >
                <!-- Applied filters header -->
                <p class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700">
                  {{ t('portal.filter_applied_title') }}
                </p>
                <div class="p-3 pt-2">
                  <ul class="mt-1 space-y-2 text-sm text-slate-700">
                    <li v-if="!activeFilterLines.length" class="text-slate-400">
                      {{ t('portal.filter_no_active') }}
                    </li>
                    <li
                      v-for="(row, i) in activeFilterLines"
                      :key="i"
                      class="flex items-center gap-1.5"
                    >
                      <span class="text-slate-500">{{ row.label }}:</span>
                      <span class="font-medium text-slate-800">{{ row.value }}</span>
                    </li>
                  </ul>

                  <!-- Visibility toggles -->
                  <div class="mt-3 border-t border-slate-100 pt-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700">
                      {{ t('portal.filter_show_controls_title') }}
                    </p>
                    <p class="mt-0.5 text-[10px] leading-snug text-slate-500">
                      {{ t('portal.filter_show_controls_hint') }}
                    </p>
                    <ul class="mt-2 max-h-[min(40vh,220px)] space-y-2 overflow-y-auto pr-0.5">
                      <li v-for="fd in visibilityOptions" :key="'vis-' + fd.id" class="flex items-start gap-2">
                        <input
                          :id="'portal-filter-vis-' + fd.id"
                          v-model="filterDropdownVisible[fd.id]"
                          type="checkbox"
                          class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30"
                        />
                        <label
                          :for="'portal-filter-vis-' + fd.id"
                          class="cursor-pointer text-sm leading-snug text-slate-700"
                        >
                          {{ fd.label }}
                        </label>
                      </li>
                    </ul>
                  </div>

                  <!-- Clear all -->
                  <button
                    type="button"
                    class="mt-3 w-full rounded-xl border border-slate-200 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    @click="resetFilters"
                  >
                    {{ t('portal.filter_clear_all') }}
                  </button>
                </div>
              </div>
            </details>

            <!-- Filter chips -->
            <div class="flex min-w-0 flex-1 items-center gap-x-2 gap-y-2 overflow-x-auto pb-0.5 [-ms-overflow-style:none] [scrollbar-width:none] sm:flex-wrap sm:overflow-visible sm:pb-0 [&::-webkit-scrollbar]:hidden sm:gap-x-3">

              <!-- Status chip -->
              <AppFilterDropdown
                v-if="filterDropdownVisible.status !== false"
                :label="t('portal.filter_label_status')"
                :summary-text="currentStatusLabel"
                panel-class="min-w-[200px] py-1"
                class="shrink-0 snap-start"
              >
                <ul class="max-h-[min(60vh,300px)] overflow-y-auto px-1 py-1">
                  <li v-for="opt in filterOptions" :key="opt.key">
                    <button
                      type="button"
                      :class="[
                        'flex w-full rounded-lg px-3 py-2 text-left text-sm transition',
                        filterStatus === opt.key
                          ? 'bg-teal-50 font-medium text-teal-900'
                          : 'text-slate-700 hover:bg-slate-50',
                      ]"
                      @click="onFilter(opt.key)"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <!-- Sort chip -->
              <AppFilterDropdown
                v-if="filterDropdownVisible.sort !== false"
                :label="t('portal.filter_label_sort')"
                :summary-text="currentSortLabel"
                panel-class="min-w-[240px] py-1"
                class="shrink-0 snap-start"
              >
                <ul class="max-h-[min(60vh,300px)] overflow-y-auto px-1 py-1">
                  <li v-for="opt in sortOptions" :key="opt.value">
                    <button
                      type="button"
                      :class="[
                        'flex w-full rounded-lg px-3 py-2 text-left text-sm transition',
                        sort === opt.value
                          ? 'bg-teal-50 font-medium text-teal-900'
                          : 'text-slate-700 hover:bg-slate-50',
                      ]"
                      @click="onSort(opt.value)"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <!-- Trip type chip -->
              <AppFilterDropdown
                v-if="filterDropdownVisible.trip_type !== false"
                :label="t('portal.filter_label_trip_type')"
                :summary-text="currentTripTypeLabel"
                panel-class="min-w-[220px] py-1"
                class="shrink-0 snap-start"
              >
                <ul class="max-h-[min(60vh,300px)] overflow-y-auto px-1 py-1">
                  <li v-for="opt in tripTypeOptions" :key="opt.key">
                    <button
                      type="button"
                      :class="[
                        'flex w-full rounded-lg px-3 py-2 text-left text-sm transition',
                        filterTripType === opt.key
                          ? 'bg-teal-50 font-medium text-teal-900'
                          : 'text-slate-700 hover:bg-slate-50',
                      ]"
                      @click="onTripType(opt.key)"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <!-- Urgent chip -->
              <AppFilterDropdown
                v-if="filterDropdownVisible.urgent !== false"
                :label="t('portal.filter_label_urgent')"
                :summary-text="currentUrgentLabel"
                panel-class="min-w-[180px] py-1"
                class="shrink-0 snap-start"
              >
                <ul class="max-h-[min(60vh,300px)] overflow-y-auto px-1 py-1">
                  <li v-for="opt in urgentOptions" :key="opt.key">
                    <button
                      type="button"
                      :class="[
                        'flex w-full rounded-lg px-3 py-2 text-left text-sm transition',
                        filterUrgent === opt.key
                          ? 'bg-teal-50 font-medium text-teal-900'
                          : 'text-slate-700 hover:bg-slate-50',
                      ]"
                      @click="onUrgent(opt.key)"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <!-- Extracurricular chip -->
              <AppFilterDropdown
                v-if="!isExtracurricularModule && filterDropdownVisible.extracurricular !== false"
                :label="t('portal.filter_label_extracurricular')"
                :summary-text="currentExtracurricularLabel"
                panel-class="min-w-[240px] py-1"
                class="shrink-0 snap-start"
              >
                <ul class="max-h-[min(60vh,300px)] overflow-y-auto px-1 py-1">
                  <li v-for="opt in extracurricularOptions" :key="opt.key">
                    <button
                      type="button"
                      :class="[
                        'flex w-full rounded-lg px-3 py-2 text-left text-sm transition',
                        filterExtracurricular === opt.key
                          ? 'bg-teal-50 font-medium text-teal-900'
                          : 'text-slate-700 hover:bg-slate-50',
                      ]"
                      @click="onExtracurricular(opt.key)"
                    >
                      {{ opt.label }}
                    </button>
                  </li>
                </ul>
              </AppFilterDropdown>

              <!-- Date range chip -->
              <AppFilterDropdown
                v-if="filterDropdownVisible.date_range !== false"
                :label="t('portal.filter_label_date_range')"
                :summary-text="currentDateRangeLabel"
                panel-class="min-w-[260px] p-3"
                class="shrink-0 snap-start"
              >
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                  <label class="flex flex-1 flex-col gap-1 text-xs text-slate-600">
                    <span>{{ t('portal.filter_date_from') }}</span>
                    <input
                      v-model="dateFrom"
                      type="date"
                      class="h-9 rounded-lg border-0 bg-white/90 px-2 text-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
                    />
                  </label>
                  <span class="hidden text-slate-400 sm:inline" aria-hidden="true">—</span>
                  <label class="flex flex-1 flex-col gap-1 text-xs text-slate-600">
                    <span>{{ t('portal.filter_date_to') }}</span>
                    <input
                      v-model="dateTo"
                      type="date"
                      class="h-9 rounded-lg border-0 bg-white/90 px-2 text-sm ring-1 ring-slate-200/80 focus:outline-none focus:ring-2 focus:ring-teal-500/30"
                    />
                  </label>
                </div>
              </AppFilterDropdown>
            </div>

            <!-- Action area: clear button -->
            <div
              v-if="activeFilterCount > 0"
              class="ml-auto flex shrink-0 items-center pl-2"
            >
              <button
                type="button"
                :title="t('portal.filter_clear_all')"
                :aria-label="t('portal.filter_clear_all')"
                class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-500"
                @click="resetFilters"
              >
                <XMarkIcon class="h-4 w-4" aria-hidden="true" />
              </button>
            </div>
            </div>
          </div>
        </AppFilterBar>
      </div>

      <PortalRequestSkeleton
        v-if="loading && !silentListRefresh"
        class="mt-8"
        :aria-label="t('portal.loading_requests')"
      />

      <div v-else-if="fetchError" class="mt-8 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        {{ fetchError }}
      </div>

      <template v-else>
        <PortalEmptyState
          v-if="!items.length"
          class="mt-10"
          :title="t('portal.empty_title')"
          :description="t('portal.empty_desc')"
        >
          <template #action>
            <RouterLink
              :to="{ name: portalRoutes.create }"
              class="inline-flex min-h-[48px] items-center justify-center rounded-2xl bg-indigo-600 px-8 text-sm font-semibold text-white shadow-md hover:bg-indigo-700"
            >
              {{ t('portal.cta_primary') }}
            </RouterLink>
          </template>
        </PortalEmptyState>

        <div v-else class="mt-8 space-y-4">
          <div
            v-if="isExtracurricularMode && showPlanCreatedHint"
            class="flex gap-3 rounded-xl border border-teal-200 bg-teal-50/90 px-4 py-3 text-sm text-teal-950"
            role="status"
          >
            <p class="min-w-0 flex-1 leading-relaxed">{{ t('portal.recurring_plan.created_list_hint') }}</p>
            <button
              type="button"
              class="shrink-0 text-xs font-semibold text-teal-800 underline hover:text-teal-950"
              @click="dismissPlanCreatedHint"
            >
              {{ t('portal.welcome_banner_dismiss') }}
            </button>
          </div>
          <div
            v-if="isExtracurricularMode"
            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-violet-200/70 bg-gradient-to-r from-slate-50 via-violet-50/40 to-indigo-50/30 px-3 py-2.5 shadow-sm ring-1 ring-violet-100/50"
          >
            <div class="flex flex-wrap gap-1 rounded-xl bg-white/80 p-1 shadow-inner ring-1 ring-slate-200/60">
              <button
                v-for="mode in listViewModes"
                :key="mode.id"
                type="button"
                class="rounded-lg px-3 py-2 text-xs font-semibold transition"
                :class="
                  extracurricularListView === mode.id
                    ? 'bg-violet-600 text-white shadow-sm shadow-violet-500/25'
                    : 'text-slate-600 hover:bg-violet-50 hover:text-violet-900'
                "
                @click="extracurricularListView = mode.id"
              >
                {{ mode.label }}
              </button>
            </div>
            <label
              v-if="extracurricularListView === 'schedule'"
              class="flex items-center gap-2 text-sm text-slate-700"
            >
              <span class="font-medium">{{ t('portal.recurring_plan.schedule_group_label') }}</span>
              <select
                v-model="scheduleGroupBy"
                class="h-9 rounded-lg border border-violet-200/80 bg-white px-2.5 text-sm font-medium text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-violet-500/30"
              >
                <option value="day">{{ t('portal.recurring_plan.group_by_day') }}</option>
                <option value="plan">{{ t('portal.recurring_plan.group_by_plan') }}</option>
                <option value="route">{{ t('portal.recurring_plan.group_by_route') }}</option>
                <option value="status">{{ t('portal.recurring_plan.group_by_status') }}</option>
              </select>
            </label>
          </div>
          <div class="flex flex-wrap items-center justify-end gap-2">
            <label class="flex items-center gap-2">
              <span class="text-sm text-slate-600">{{ t('portal.filter_per_page') }}</span>
              <select
                v-model.number="perPage"
                class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-sm font-medium text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500/30"
                :aria-label="t('portal.filter_per_page')"
                @change="onPerPageChange"
              >
                <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
              </select>
            </label>
          </div>
          <ExtracurricularRequestsCalendar
            v-if="isExtracurricularMode && extracurricularListView === 'calendar'"
            :requests="items"
            :detail-route-name="portalRoutes.detail"
          />
          <ExtracurricularScheduleTable
            v-else-if="isExtracurricularMode && extracurricularListView === 'schedule'"
            :requests="items"
            :group-by="scheduleGroupBy"
            :detail-route-name="portalRoutes.detail"
            @refresh="reloadSilent"
            @plan-label-saved="onPlanLabelSaved"
          />
          <ExtracurricularRequestsDataTable
            v-else-if="isExtracurricularMode"
            ref="extracurricularTableRef"
            :requests="items"
            variant="portal"
            :detail-route-name="portalRoutes.detail"
            @refresh="reloadFromStart"
            @clone="onCloneFromList"
          />
          <PortalRequestsTable v-else :requests="items" />
          <nav
            v-if="pagination && (pagination.last_page ?? 1) > 1"
            class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between"
            :aria-label="t('portal.filter_per_page')"
          >
            <p class="text-sm text-slate-500">
              {{
                t('portal.pagination_summary', {
                  from: pageFrom,
                  to: pageTo,
                  total: pagination.total ?? 0,
                })
              }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
              <button
                type="button"
                class="min-h-[40px] rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 disabled:opacity-50"
                :disabled="(pagination.current_page ?? 1) <= 1 || loading"
                @click="goPage((pagination.current_page ?? 1) - 1)"
              >
                {{ t('portal.page_prev') }}
              </button>
              <div class="flex items-center gap-1">
                <button
                  v-for="p in pageNumbers"
                  :key="p"
                  type="button"
                  class="min-w-[2.25rem] rounded-lg px-2 py-1.5 text-sm"
                  :class="
                    p === pagination.current_page
                      ? 'bg-indigo-600 font-semibold text-white'
                      : 'text-slate-600 hover:bg-slate-100'
                  "
                  @click="goPage(p)"
                >
                  {{ p }}
                </button>
              </div>
              <button
                type="button"
                class="min-h-[40px] rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 disabled:opacity-50"
                :disabled="(pagination.current_page ?? 1) >= (pagination.last_page ?? 1) || loading"
                @click="goPage((pagination.current_page ?? 1) + 1)"
              >
                {{ t('portal.page_next') }}
              </button>
            </div>
          </nav>
          <p
            v-else-if="pagination && (pagination.total ?? 0) > 0"
            class="text-center text-sm text-slate-500"
          >
            {{
              t('portal.pagination_summary', {
                from: pageFrom,
                to: pageTo,
                total: pagination.total ?? 0,
              })
            }}
          </p>
        </div>
      </template>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ChevronDownIcon, FunnelIcon, MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { cloneDispatchRequest, listPortalRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import PortalEmptyState from '../../components/portal/PortalEmptyState.vue'
import PortalRequestSkeleton from '../../components/portal/PortalRequestSkeleton.vue'
import PortalRequestsTable from '../../components/portal/PortalRequestsTable.vue'
import ExtracurricularRequestsDataTable from '../../components/requests/ExtracurricularRequestsDataTable.vue'
import ExtracurricularRequestsCalendar from '../../components/portal/extracurricular/ExtracurricularRequestsCalendar.vue'
import ExtracurricularScheduleTable from '../../components/portal/extracurricular/ExtracurricularScheduleTable.vue'
import { usePortalExtracurricularModule } from '../../composables/usePortalExtracurricularModule'

const PER_PAGE_OPTIONS = [5, 10, 15, 20]
const PER_PAGE_KEY = 'portal-list-per-page'
const SUGGEST_PER_PAGE = 8
const VIS_KEY = 'portal-filter-vis'

function readStoredPerPage() {
  try {
    const n = parseInt(localStorage.getItem(PER_PAGE_KEY) ?? '10', 10)
    return PER_PAGE_OPTIONS.includes(n) ? n : 10
  } catch {
    return 10
  }
}

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { isExtracurricularModule, routes: portalRoutes } = usePortalExtracurricularModule()

const isExtracurricularMode = computed(
  () => isExtracurricularModule.value || filterExtracurricular.value === 'extracurricular',
)

const extracurricularTableRef = ref(null)
const extracurricularListView = ref('schedule')
const scheduleGroupBy = ref('plan')

const listViewModes = computed(() => [
  { id: 'calendar', label: t('portal.recurring_plan.list_view_calendar') },
  { id: 'schedule', label: t('portal.recurring_plan.list_view_schedule') },
  { id: 'detail', label: t('portal.recurring_plan.list_view_detail') },
])

// ── List state ───────────────────────────────────────────────
const loading = ref(true)
const silentListRefresh = ref(false)
const fetchError = ref('')
const items = ref([])
const pagination = ref(null)
const perPage = ref(readStoredPerPage())
const perPageOptions = PER_PAGE_OPTIONS

// ── Filter state ─────────────────────────────────────────────
const filterStatus = ref('all')
const filterTripType = ref('all')
const filterUrgent = ref('all')
const filterExtracurricular = ref('all')
const sort = ref('depart_desc')
const searchInput = ref('')
const debouncedQ = ref('')
const searchDropdownOpen = ref(false)
const searchSuggestions = ref([])
const searchSuggestLoading = ref(false)
const searchSuggestFocus = ref(-1)
const dateFrom = ref('')
const dateTo = ref('')

const showSearchSuggest = computed(
  () => searchDropdownOpen.value && String(searchInput.value ?? '').trim().length >= 1,
)

// ── Filter chip visibility (persisted) ───────────────────────
const filterDropdownVisible = reactive(
  (() => {
    try {
      return JSON.parse(localStorage.getItem(VIS_KEY) ?? '{}') ?? {}
    } catch {
      return {}
    }
  })(),
)

watch(
  filterDropdownVisible,
  (v) => {
    try {
      localStorage.setItem(VIS_KEY, JSON.stringify(v))
    } catch {
      // localStorage unavailable
    }
  },
  { deep: true },
)

const visibilityOptions = computed(() => [
  { id: 'status', label: t('portal.filter_label_status') },
  { id: 'sort', label: t('portal.filter_label_sort') },
  { id: 'trip_type', label: t('portal.filter_label_trip_type') },
  { id: 'urgent', label: t('portal.filter_label_urgent') },
  { id: 'extracurricular', label: t('portal.filter_label_extracurricular') },
  { id: 'date_range', label: t('portal.filter_label_date_range') },
])

// ── Filter / sort option lists ────────────────────────────────
const filterOptions = computed(() => [
  { key: 'all', label: t('portal.filter_all') },
  { key: 'pending', label: t('portal.filter_pending') },
  { key: 'approved', label: t('portal.filter_approved') },
  { key: 'rejected', label: t('portal.filter_rejected') },
  { key: 'returned', label: t('portal.filter_returned') },
])

const sortOptions = computed(() => [
  { value: 'depart_desc', label: t('portal.sort_depart_desc') },
  { value: 'depart_asc', label: t('portal.sort_depart_asc') },
  { value: 'created_desc', label: t('portal.sort_created_desc') },
  { value: 'created_asc', label: t('portal.sort_created_asc') },
])

const tripTypeOptions = computed(() => [
  { key: 'all', label: t('portal.filter_trip_type_all') },
  { key: 'business', label: t('portal.filter_trip_type_business') },
  { key: 'cargo', label: t('portal.filter_trip_type_cargo') },
  { key: 'door_to_door', label: t('portal.filter_trip_type_door_to_door') },
  { key: 'point_to_point', label: t('portal.filter_trip_type_point_to_point') },
])

const urgentOptions = computed(() => [
  { key: 'all', label: t('portal.filter_urgent_all') },
  { key: 'urgent', label: t('portal.filter_urgent_yes') },
  { key: 'normal', label: t('portal.filter_urgent_no') },
])

const extracurricularOptions = computed(() => [
  { key: 'all', label: t('portal.filter_extracurricular_all') },
  { key: 'extracurricular', label: t('portal.filter_extracurricular_only') },
])

const currentStatusLabel = computed(
  () => filterOptions.value.find((o) => o.key === filterStatus.value)?.label ?? filterStatus.value,
)

const currentSortLabel = computed(
  () => sortOptions.value.find((o) => o.value === sort.value)?.label ?? sort.value,
)

const currentTripTypeLabel = computed(
  () => tripTypeOptions.value.find((o) => o.key === filterTripType.value)?.label ?? filterTripType.value,
)

const currentUrgentLabel = computed(
  () => urgentOptions.value.find((o) => o.key === filterUrgent.value)?.label ?? filterUrgent.value,
)

const currentExtracurricularLabel = computed(
  () =>
    extracurricularOptions.value.find((o) => o.key === filterExtracurricular.value)?.label
    ?? filterExtracurricular.value,
)

const currentDateRangeLabel = computed(() => {
  if (dateFrom.value && dateTo.value) return `${dateFrom.value} — ${dateTo.value}`
  if (dateFrom.value) return `${t('portal.filter_date_from')}: ${dateFrom.value}`
  if (dateTo.value) return `${t('portal.filter_date_to')}: ${dateTo.value}`
  return t('portal.filter_all')
})

// ── Active filter count + summary lines ──────────────────────
const activeFilterCount = computed(() => {
  let n = 0
  if (filterStatus.value !== 'all') n++
  if (filterTripType.value !== 'all') n++
  if (filterUrgent.value !== 'all') n++
  if (filterExtracurricular.value !== 'all') n++
  if (sort.value !== 'depart_desc') n++
  if (debouncedQ.value) n++
  if (dateFrom.value || dateTo.value) n++
  return n
})

const activeFilterLines = computed(() => {
  const lines = []
  if (filterStatus.value !== 'all') {
    lines.push({ label: t('portal.filter_label_status'), value: currentStatusLabel.value })
  }
  if (filterTripType.value !== 'all') {
    lines.push({ label: t('portal.filter_label_trip_type'), value: currentTripTypeLabel.value })
  }
  if (filterUrgent.value !== 'all') {
    lines.push({ label: t('portal.filter_label_urgent'), value: currentUrgentLabel.value })
  }
  if (filterExtracurricular.value !== 'all') {
    lines.push({
      label: t('portal.filter_label_extracurricular'),
      value: currentExtracurricularLabel.value,
    })
  }
  if (sort.value !== 'depart_desc') {
    lines.push({ label: t('portal.filter_label_sort'), value: currentSortLabel.value })
  }
  if (dateFrom.value || dateTo.value) {
    lines.push({ label: t('portal.filter_label_date_range'), value: currentDateRangeLabel.value })
  }
  if (debouncedQ.value) {
    lines.push({ label: t('portal.search_placeholder'), value: debouncedQ.value })
  }
  return lines
})

const pageFrom = computed(() => {
  const cur = pagination.value?.current_page ?? 1
  const per = pagination.value?.per_page ?? perPage.value
  const total = pagination.value?.total ?? 0
  if (total === 0) return 0
  return (cur - 1) * per + 1
})

const pageTo = computed(() => {
  const cur = pagination.value?.current_page ?? 1
  const per = pagination.value?.per_page ?? perPage.value
  const total = pagination.value?.total ?? 0
  return Math.min(cur * per, total)
})

const pageNumbers = computed(() => {
  const last = pagination.value?.last_page ?? 1
  const cur = pagination.value?.current_page ?? 1
  const window = 3
  const start = Math.max(1, cur - 1)
  const end = Math.min(last, start + window - 1)
  const list = []
  for (let p = start; p <= end; p++) list.push(p)
  return list
})

// ── Search autocomplete ──────────────────────────────────────
let suggestTimer = null

function suggestRouteLine(req) {
  const o = (req.origin || '').trim()
  const d = (req.destination || '').trim()
  if (o || d) return `${o || '…'} → ${d || '…'}`.trim()
  return t('portal.card_no_route')
}

async function fetchSearchSuggestions(term) {
  const q = String(term ?? '').trim()
  if (!q) {
    searchSuggestions.value = []
    return
  }
  searchSuggestLoading.value = true
  try {
    const data = await listPortalRequests({
      per_page: SUGGEST_PER_PAGE,
      page: 1,
      sort: 'depart_desc',
      q,
    })
    searchSuggestions.value = data.items ?? []
    searchSuggestFocus.value = searchSuggestions.value.length ? 0 : -1
  } catch {
    searchSuggestions.value = []
    searchSuggestFocus.value = -1
  } finally {
    searchSuggestLoading.value = false
  }
}

function scheduleSearchSuggestions() {
  clearTimeout(suggestTimer)
  const term = String(searchInput.value ?? '').trim()
  if (!term) {
    searchSuggestions.value = []
    searchSuggestFocus.value = -1
    return
  }
  suggestTimer = window.setTimeout(() => fetchSearchSuggestions(term), 280)
}

function onSearchInput() {
  searchDropdownOpen.value = true
  scheduleSearchSuggestions()
}

function onSearchFocus() {
  searchDropdownOpen.value = true
  if (String(searchInput.value ?? '').trim()) {
    scheduleSearchSuggestions()
  }
}

function onSearchBlur() {
  window.setTimeout(() => {
    searchDropdownOpen.value = false
    searchSuggestFocus.value = -1
  }, 150)
}

function closeSearchSuggest() {
  searchDropdownOpen.value = false
  searchSuggestFocus.value = -1
}

function moveSearchSuggest(delta) {
  if (!searchSuggestions.value.length) return
  const n = searchSuggestions.value.length
  if (searchSuggestFocus.value < 0) {
    searchSuggestFocus.value = delta > 0 ? 0 : n - 1
    return
  }
  searchSuggestFocus.value = (searchSuggestFocus.value + delta + n) % n
}

function flushSearch(term = searchInput.value) {
  const q = String(term ?? '').trim()
  clearTimeout(debounceTimer)
  searchInput.value = q
  closeSearchSuggest()
  searchSuggestions.value = []
  if (q !== debouncedQ.value) {
    debouncedQ.value = q
  } else {
    reloadFromStart()
  }
}

function pickSearchSuggestion(req) {
  flushSearch(String(req.id))
}

function commitSearchSuggest() {
  if (showSearchSuggest.value && searchSuggestFocus.value >= 0) {
    const req = searchSuggestions.value[searchSuggestFocus.value]
    if (req) {
      pickSearchSuggestion(req)
      return
    }
  }
  flushSearch()
}

// ── Debounced search ─────────────────────────────────────────
let debounceTimer = null

watch(searchInput, (v) => {
  clearTimeout(debounceTimer)
  debounceTimer = window.setTimeout(() => {
    debouncedQ.value = String(v ?? '').trim()
  }, 350)
})

watch(debouncedQ, () => {
  reloadFromStart()
})

let dateDebounceTimer = null
watch([dateFrom, dateTo], () => {
  clearTimeout(dateDebounceTimer)
  dateDebounceTimer = window.setTimeout(() => {
    reloadFromStart()
  }, 400)
})

// ── Actions ──────────────────────────────────────────────────
function onFilter(key) {
  filterStatus.value = key
  reloadFromStart()
}

function onTripType(key) {
  filterTripType.value = key
  reloadFromStart()
}

function onUrgent(key) {
  filterUrgent.value = key
  reloadFromStart()
}

function onExtracurricular(key) {
  filterExtracurricular.value = key
  reloadFromStart()
}

function onSort(value) {
  sort.value = value
  reloadFromStart()
}

function resetFilters() {
  filterStatus.value = 'all'
  filterTripType.value = 'all'
  filterUrgent.value = 'all'
  filterExtracurricular.value = 'all'
  sort.value = 'depart_desc'
  clearTimeout(debounceTimer)
  searchInput.value = ''
  debouncedQ.value = ''
  closeSearchSuggest()
  searchSuggestions.value = []
  dateFrom.value = ''
  dateTo.value = ''
  reloadFromStart()
}

// ── API ──────────────────────────────────────────────────────
function listParams(page) {
  return {
    per_page: perPage.value,
    page,
    sort: sort.value,
    filter: filterStatus.value === 'all' ? undefined : filterStatus.value,
    q: debouncedQ.value || undefined,
    trip_type: filterTripType.value !== 'all' ? filterTripType.value : undefined,
    is_urgent:
      filterUrgent.value !== 'all' ? (filterUrgent.value === 'urgent' ? 1 : 0) : undefined,
    extracurricular_only:
      isExtracurricularModule.value || filterExtracurricular.value === 'extracurricular'
        ? true
        : undefined,
    date_from: dateFrom.value || undefined,
    date_to: dateTo.value || undefined,
  }
}

async function loadPage(page = 1, silent = false) {
  if (silent) {
    silentListRefresh.value = true
  } else {
    loading.value = true
    fetchError.value = ''
  }
  try {
    const data = await listPortalRequests(listParams(page))
    items.value = data.items ?? []
    pagination.value = data.meta ?? null
  } catch (e) {
    if (!silent) {
      fetchError.value = formatApiError(e, t('portal.load_requests_fail'))
      items.value = []
      pagination.value = null
    }
  } finally {
    silentListRefresh.value = false
    if (!silent) loading.value = false
  }
}

function reloadFromStart() {
  loadPage(1)
}

function reloadSilent() {
  loadPage(1, true)
}

function templateIdFromRequest(req) {
  const tid = req?.dispatch_request_template_id ?? req?.dispatch_request_template?.id
  if (tid == null || tid === '') return null
  const n = Number(tid)
  return Number.isFinite(n) && n > 0 ? n : null
}

function onPlanLabelSaved({ templateId, label }) {
  const tid = Number(templateId)
  const trimmed = String(label || '').trim()
  if (!Number.isFinite(tid) || tid <= 0 || !trimmed) return
  for (const req of items.value) {
    if (templateIdFromRequest(req) !== tid) continue
    req.recurring_plan_label = trimmed
    if (!req.dispatch_request_template) {
      req.dispatch_request_template = { id: tid }
    }
    if (!req.dispatch_request_template.dispatch_package) {
      req.dispatch_request_template.dispatch_package = { label: trimmed }
    } else {
      req.dispatch_request_template.dispatch_package.label = trimmed
    }
  }
}

function goPage(page) {
  if (loading.value) return
  const last = pagination.value?.last_page ?? 1
  const p = Math.max(1, Math.min(page, last))
  loadPage(p)
}

async function onCloneFromList(req) {
  if (!req?.id) return
  extracurricularTableRef.value?.setCloneBusy?.(req.id, true)
  try {
    const dr = await cloneDispatchRequest(req.id)
    await router.push({ name: portalRoutes.value.create, query: { replace: String(dr.id) } })
  } catch (e) {
    fetchError.value = formatApiError(e, t('request_detail.reset_clone_fail'))
  } finally {
    extracurricularTableRef.value?.setCloneBusy?.(req.id, false)
  }
}

function onPerPageChange() {
  try {
    localStorage.setItem(PER_PAGE_KEY, String(perPage.value))
  } catch {
    // ignore
  }
  reloadFromStart()
}

const showPlanCreatedHint = ref(false)

function dismissPlanCreatedHint() {
  showPlanCreatedHint.value = false
  const q = { ...route.query }
  delete q.plan_created
  router.replace({ query: q })
}

watch(
  () => route.query.plan_created,
  (v) => {
    showPlanCreatedHint.value = String(v) === '1'
  },
  { immediate: true },
)

watch(extracurricularListView, () => {
  if (isExtracurricularMode.value) {
    reloadFromStart()
  }
})

onMounted(() => {
  if (isExtracurricularModule.value) {
    filterExtracurricular.value = 'extracurricular'
  }
  loadPage(1)
})

onBeforeUnmount(() => {
  clearTimeout(debounceTimer)
  clearTimeout(suggestTimer)
  clearTimeout(dateDebounceTimer)
})
</script>
