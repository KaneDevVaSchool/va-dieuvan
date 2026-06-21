<template>
  <div class="space-y-5 pb-8">
    <div class="flex flex-col gap-4 border-b border-slate-200/80 pb-5">
      <div class="flex flex-wrap items-start gap-3">
        <button
          type="button"
          class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
          :aria-label="t('tp_attendance_page.back_to_program')"
          data-testid="tp-attendance-back"
          @click="goBack"
        >
          <ArrowLeftIcon class="h-5 w-5" aria-hidden="true" />
        </button>
        <div class="min-w-0 flex-1">
          <nav class="flex min-w-0 flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-slate-500" aria-label="breadcrumb">
            <button type="button" class="font-medium text-slate-600 hover:text-va-800" @click="goBack">
              {{ t('tp_attendance_page.breadcrumb_program') }}
            </button>
            <ChevronRightIcon class="h-3.5 w-3.5 shrink-0 text-slate-300" aria-hidden="true" />
            <span class="max-w-[min(100%,20rem)] truncate font-medium text-slate-700">
              {{ data?.day?.program_name || t('tp_attendance_page.header_program_fallback') }}
            </span>
            <ChevronRightIcon class="h-3.5 w-3.5 shrink-0 text-slate-300" aria-hidden="true" />
            <span class="font-semibold text-slate-900">{{ t('tp_attendance_page.title') }}</span>
          </nav>
          <div class="mt-2 flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
              {{ headerTitle }}
            </h1>
            <span class="rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-900 ring-1 ring-sky-200/80">
              {{ shiftLabel }}
            </span>
            <span
              v-if="data?.attendance_status === 'confirmed'"
              class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-900 ring-1 ring-emerald-200/80"
            >
              <CheckCircleIcon class="h-3.5 w-3.5" aria-hidden="true" />
              {{ t('tp_attendance_page.session_confirmed') }}
            </span>
            <span
              v-else-if="data?.attendance_status === 'draft'"
              class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-900 ring-1 ring-amber-200/80"
            >
              <ClockIcon class="h-3.5 w-3.5" aria-hidden="true" />
              {{ t('tp_attendance_page.session_draft') }}
            </span>
          </div>
          <p class="mt-1 text-sm text-slate-500">{{ t('tp_attendance_page.hero_subtitle') }}</p>
          <p v-if="headerMetaLine" class="mt-2 text-sm font-medium text-slate-700">{{ headerMetaLine }}</p>
        </div>
        <div
          v-if="data"
          class="flex w-full flex-wrap items-center justify-end gap-3 sm:w-auto sm:flex-col sm:items-end"
        >
          <div class="flex items-center gap-1 rounded-lg border border-slate-200 bg-white p-0.5">
            <button
              type="button"
              :disabled="!hasPrevDay"
              class="rounded-md p-1.5 text-slate-500 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
              :aria-label="t('tp_attendance_page.day_prev_aria')"
              data-testid="tp-attendance-day-prev"
              @click="shiftDay(-1)"
            >
              <ChevronLeftIcon class="h-4 w-4" />
            </button>
            <span class="min-w-[6.5rem] text-center text-sm font-semibold tabular-nums text-slate-800">
              {{ formatShortDate(data.day.scheduled_date) }}
            </span>
            <button
              type="button"
              :disabled="!hasNextDay"
              class="rounded-md p-1.5 text-slate-500 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
              :aria-label="t('tp_attendance_page.day_next_aria')"
              data-testid="tp-attendance-day-next"
              @click="shiftDay(1)"
            >
              <ChevronRightIcon class="h-4 w-4" />
            </button>
          </div>
          <div class="flex items-center rounded-lg border border-slate-200 bg-white p-0.5 text-sm" role="group" :aria-label="t('tp_attendance_page.shift_toggle_aria')">
            <button
              type="button"
              class="rounded-md px-3 py-1.5 font-medium transition disabled:cursor-not-allowed disabled:opacity-40"
              :class="isMorningShift ? 'bg-va-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
              :disabled="!hasMorningShift"
              data-testid="tp-attendance-shift-morning"
              @click="trySwitchShift('morning')"
            >{{ t('tp_attendance_page.shift_morning') }}</button>
            <button
              type="button"
              class="rounded-md px-3 py-1.5 font-medium transition disabled:cursor-not-allowed disabled:opacity-40"
              :class="!isMorningShift ? 'bg-va-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
              :disabled="!hasAfternoonShift"
              data-testid="tp-attendance-shift-afternoon"
              @click="trySwitchShift('afternoon')"
            >{{ t('tp_attendance_page.shift_afternoon') }}</button>
          </div>
        </div>
      </div>
    </div>

    <template v-if="loading">
      <div class="h-36 animate-pulse rounded-xl bg-slate-100" />
      <div class="h-14 animate-pulse rounded-xl bg-slate-100" />
      <div class="h-64 animate-pulse rounded-xl bg-slate-100" />
    </template>

    <template v-else-if="data">
      <AttendanceSummaryBar
        :summary="data.summary"
        :active-status="filters.displayStatus"
        @quick-filter="onKpiQuickFilter"
      />

      <div
        v-if="data.attendance_status === 'confirmed'"
        class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
      >
        <div class="flex items-center gap-2 text-sm font-medium text-emerald-800">
          <LockClosedIcon class="h-4 w-4 shrink-0" />
          {{ t('tp_attendance_page.confirmed_readonly_banner') }}
        </div>
        <Button v-if="canReopen" variant="secondary" class="shrink-0 text-xs" :loading="saving" data-testid="tp-attendance-reopen" @click="onReopen">
          {{ t('tp_attendance_page.reopen_edit') }}
        </Button>
      </div>

      <div
        ref="datagridRef"
        class="overflow-visible rounded-xl border border-slate-200/80 bg-white shadow-sm"
      >
        <div class="border-b border-slate-100 px-4 py-3 sm:px-5">
          <div class="mb-2 flex flex-wrap items-center gap-2">
            <h2 class="text-sm font-semibold text-slate-800">
              {{ t('tp_attendance_page.list_section_title') }}
              <span class="ml-1 text-xs font-normal text-slate-400">({{ totalFiltered }})</span>
            </h2>
          </div>
          <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
            <div class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto">
              <DatagridToolbarSearch
                v-model="filters.q"
                input-id="tp-attendance-search"
                :placeholder="t('tp_attendance_page.search_placeholder')"
                :aria-label="t('tp_attendance_page.search_placeholder')"
                stretch
                inline-actions
                hide-label
                input-height="h-10"
                data-testid="tp-attendance-toolbar-search"
              />
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
              <FilterVisibilityDropdown
                :open="showFilterPanelDd"
                :title="t('tp_attendance_page.filter_show_controls_title')"
                :hint="t('tp_attendance_page.filter_show_controls_hint')"
                @close="closeFilterPanel"
              >
                <template #trigger>
                  <DatagridToolbarActionButton
                    icon="filter"
                    :active="showFilterPanelDd"
                    test-id="tp-attendance-toolbar-filter"
                    @click="toggleFilterPanel"
                  >
                    {{ t('tp_attendance_page.toolbar_filter') }}
                  </DatagridToolbarActionButton>
                </template>
                <li v-for="fd in filterControlDefs" :key="'att-vis-' + fd.key" class="flex items-start gap-2">
                  <input
                    :id="'att-filter-vis-' + fd.key"
                    v-model="visibleFilters[fd.key]"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30"
                    :data-testid="`tp-attendance-filter-vis-${fd.key}`"
                  />
                  <label :for="'att-filter-vis-' + fd.key" class="cursor-pointer text-sm text-slate-700">{{ fd.label }}</label>
                </li>
              </FilterVisibilityDropdown>

              <FilterVisibilityDropdown
                :open="showColPanelDd"
                :title="t('tp_attendance_page.column_visibility_title')"
                @close="closeColPanel"
              >
                <template #trigger>
                  <DatagridToolbarActionButton
                    icon="columns"
                    :active="showColPanelDd"
                    test-id="tp-attendance-toolbar-columns"
                    @click="toggleColPanel"
                  >
                    {{ t('tp_attendance_page.toolbar_columns') }}
                  </DatagridToolbarActionButton>
                </template>
                <li v-for="opt in columnToggleOptions" :key="'att-col-' + opt.id" class="flex items-start gap-2">
                  <input
                    :id="`att-col-${opt.id}`"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30"
                    :checked="columnVisible[opt.id] !== false"
                    :data-testid="`tp-attendance-col-vis-${opt.id}`"
                    @change="setColumn(opt.id, $event.target.checked)"
                  />
                  <label :for="`att-col-${opt.id}`" class="cursor-pointer text-sm text-slate-700">{{ t(opt.labelKey) }}</label>
                </li>
              </FilterVisibilityDropdown>

              <button
                type="button"
                class="inline-flex h-10 items-center gap-1 rounded-lg px-2 text-sm text-slate-500 hover:bg-slate-50"
                :title="t('tp_attendance_page.filter_clear_all')"
                :disabled="activeFilterCount === 0"
                data-testid="tp-attendance-reset-filters"
                @click="resetFilters()"
              >
                <FunnelIcon class="h-5 w-5" aria-hidden="true" />
                <XMarkIcon class="h-3 w-3 text-rose-500" aria-hidden="true" />
              </button>

              <Button
                variant="secondary"
                class="!h-10 shrink-0 gap-1.5 !px-3 !text-sm"
                :disabled="isConfirmed || saving"
                data-testid="tp-attendance-mark-all-present"
                @click="onMarkAllPresent"
              >
                <CheckIcon class="h-4 w-4" :class="markingAllPresent ? 'animate-pulse' : ''" />
                {{ t('tp_attendance_page.mark_all_present') }}
              </Button>
            </div>

            <div class="ml-auto flex shrink-0 items-center gap-2">
              <details ref="exportMenuRef" class="group relative">
                <summary class="list-none [&::-webkit-details-marker]:hidden">
                  <DatagridToolbarActionButton
                    icon="export"
                    :disabled="exportingExcel"
                    test-id="tp-attendance-toolbar-export"
                    @click.prevent
                  >
                    {{ exportingExcel ? t('tp_attendance_page.export_excel_busy') : t('tp_attendance_page.export_excel') }}
                  </DatagridToolbarActionButton>
                </summary>
                <div
                  class="absolute right-0 top-[calc(100%+8px)] z-[110] min-w-[200px] rounded-xl border border-slate-200 bg-white py-1 shadow-lg"
                  @click.stop
                >
                  <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" data-testid="tp-attendance-export-all" @click="runExport('all')">
                    {{ t('tp_attendance_page.export_scope_all') }}
                  </button>
                  <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" data-testid="tp-attendance-export-page" @click="runExport('page')">
                    {{ t('tp_attendance_page.export_scope_page') }}
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50 disabled:text-slate-400"
                    :disabled="selectedCount === 0"
                    data-testid="tp-attendance-export-selected"
                    @click="runExport('selected')"
                  >
                    {{ t('tp_attendance_page.export_scope_selected') }}
                  </button>
                </div>
              </details>
            </div>
          </div>
        </div>

        <div
          v-if="hasFilterRow"
          class="grid grid-cols-1 gap-3 border-t border-slate-100 px-5 py-4 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6"
        >
          <DatagridFilterField v-if="visibleFilters.notes" class="sm:col-span-2">
            <input
              v-model="filters.noteQ"
              type="search"
              :placeholder="t('tp_attendance_page.filter_notes_placeholder')"
              :class="FILTER_CONTROL_CLASS"
              :aria-label="t('tp_attendance_page.filter_notes_placeholder')"
              data-testid="tp-attendance-filter-notes"
            />
          </DatagridFilterField>
          <DatagridFilterField v-if="visibleFilters.boarded_time" class="sm:col-span-2 xl:col-span-2">
            <div class="flex w-full flex-wrap items-center gap-2">
              <input
                v-model="filters.boardedFrom"
                type="time"
                :class="FILTER_CONTROL_CLASS"
                :aria-label="t('tp_attendance_page.filter_boarded_from')"
                data-testid="tp-attendance-filter-boarded-from"
              />
              <span class="text-slate-300" aria-hidden="true">→</span>
              <input
                v-model="filters.boardedTo"
                type="time"
                :class="FILTER_CONTROL_CLASS"
                :aria-label="t('tp_attendance_page.filter_boarded_to')"
                data-testid="tp-attendance-filter-boarded-to"
              />
            </div>
          </DatagridFilterField>
        </div>

        <AttendanceDataTable
          :rows="pagedItems"
          :reasons="reasons"
          :selected-ids="selectedIds"
          :selected-count="selectedCount"
          :sort-key="sort.key"
          :sort-dir="sort.dir"
          :col-on="colOn"
          :pending-rows="pendingRows"
          :read-only="isConfirmed"
          :saving="saving"
          :empty-text="activeFilterCount ? t('tp_attendance_page.empty_filter') : t('tp_attendance_page.empty_day')"
          @sort="toggleSort"
          @toggle-select="toggleSelectRow"
          @toggle-select-all="toggleSelectAll"
          @clear-selection="clearSelection"
          @toggle-present="togglePresent"
          @reason-change="onReasonChange"
          @note-blur="onAbsenceNoteBlur"
          @open-student="openStudentDetail"
        >
          <template #bulk-actions>
            <Button variant="secondary" class="!py-1 !text-xs" :disabled="isConfirmed" data-testid="tp-attendance-bulk-notify" @click="openNotifyPanel">
              <BellIcon class="h-4 w-4" />
              {{ t('tp_attendance_page.bulk_notify') }}
            </Button>
            <Button
              variant="secondary"
              class="!py-1 !text-xs"
              :disabled="isConfirmed || !selectedCount"
              :loading="bulkMarking"
              data-testid="tp-attendance-bulk-present"
              @click="onBulkMarkPresent"
            >
              {{ t('tp_attendance_page.bulk_mark_present') }}
            </Button>
          </template>
        </AttendanceDataTable>

        <div
          v-if="totalFiltered > 0"
          class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-2.5 text-sm text-slate-600 sm:px-5"
          data-testid="tp-attendance-pagination"
        >
          <span class="text-xs sm:text-sm">
            {{ t('tp_attendance_page.pagination_showing', { from: pageRangeFrom, to: pageRangeTo, total: totalFiltered }) }}
          </span>
          <div class="flex flex-wrap items-center gap-2">
            <label class="flex items-center gap-1.5 text-xs text-slate-600">
              {{ t('tp_attendance_page.filter_vis_per_page') }}
              <select
                v-model.number="perPage"
                class="input h-10 rounded-lg border border-slate-200 bg-white py-1 pl-2 pr-7 text-sm text-slate-900"
                data-testid="tp-attendance-per-page"
              >
                <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
              </select>
            </label>
            <div class="flex items-center gap-0.5">
              <button
                type="button"
                class="rounded-lg border border-slate-200 p-1.5 disabled:opacity-40"
                :disabled="page <= 1"
                :aria-label="t('tp_attendance_page.pagination_prev')"
                data-testid="tp-attendance-page-prev"
                @click="page--"
              >
                <ChevronLeftIcon class="h-4 w-4" />
              </button>
              <span class="min-w-[4rem] text-center text-xs tabular-nums">
                {{ t('tp_attendance_page.pagination', { page, total: totalPages }) }}
              </span>
              <button
                type="button"
                class="rounded-lg border border-slate-200 p-1.5 disabled:opacity-40"
                :disabled="page >= totalPages"
                :aria-label="t('tp_attendance_page.pagination_next')"
                data-testid="tp-attendance-page-next"
                @click="page++"
              >
                <ChevronRightIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200/90 bg-white shadow-sm" data-testid="tp-attendance-footer">
        <div
          v-if="data.missing_reason_count > 0"
          class="flex items-center gap-2 border-b border-amber-100 bg-amber-50 px-4 py-2.5 text-sm"
        >
          <ExclamationTriangleIcon class="h-4 w-4 shrink-0 text-amber-600" />
          <span class="font-medium text-amber-900">
            {{ t('tp_attendance_page.missing_reason_banner', { n: data.missing_reason_count }) }}
          </span>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-3 sm:px-5">
          <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-600">
            <span>
              <span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-emerald-500" />
              {{ t('tp_attendance_page.footer_present') }}: <strong class="text-slate-800">{{ data.summary.present }}</strong>
            </span>
            <span>
              <span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-amber-400" />
              {{ t('tp_attendance_page.footer_excused') }}: <strong class="text-slate-800">{{ data.summary.excused }}</strong>
            </span>
            <span>
              <span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-rose-500" />
              {{ t('tp_attendance_page.footer_unexcused') }}: <strong class="text-slate-800">{{ data.summary.unexcused }}</strong>
            </span>
          </div>
          <div v-if="!isConfirmed" class="flex items-center gap-2">
            <Button variant="secondary" data-testid="tp-attendance-save-draft" :disabled="saving" :loading="savingDraft" @click="onSaveDraft">
              {{ t('tp_attendance_page.save_draft') }}
            </Button>
            <Button data-testid="tp-attendance-confirm" :disabled="saving || data.missing_reason_count > 0" :loading="confirming" @click="onConfirm">
              <LockClosedIcon class="h-4 w-4" />
              {{ t('tp_attendance_page.confirm_attendance') }}
            </Button>
          </div>
          <div v-else class="text-sm font-medium text-emerald-700">
            <CheckCircleIcon class="mr-1 inline h-4 w-4" />
            {{ t('tp_attendance_page.confirmed_at', { datetime: formatDateTime(data.attendance_confirmed_at) }) }}
          </div>
        </div>
      </div>
    </template>

    <AttendanceParentNotifyPanel
      :open="notifyOpen"
      :preview="notifyPreview"
      :preview-loading="notifyPreviewLoading"
      :logs="notifyLogs"
      :sending="notifying"
      :selected-ids="selectedIdList"
      :filtered-ids="filteredIdList"
      @close="notifyOpen = false"
      @scope-change="loadNotifyPreview"
      @refresh-logs="loadNotifyLogs"
      @send="onSendNotify"
      @retry="onRetryNotify"
    />

    <TpStudentDetailModal
      :open="studentDetailOpen"
      :student-id="studentDetailId"
      :attendance-row="studentDetailRow"
      :reasons="reasons"
      @close="closeStudentDetail"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowLeftIcon,
  BellIcon,
  CheckCircleIcon,
  CheckIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  FunnelIcon,
  LockClosedIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

import Button from '../../components/ui/Button.vue'
import DatagridToolbarSearch from '../../components/shared/ui/DatagridToolbarSearch.vue'
import DatagridToolbarActionButton from '../../components/shared/ui/DatagridToolbarActionButton.vue'
import DatagridFilterField from '../../components/shared/ui/DatagridFilterField.vue'
import FilterVisibilityDropdown from '../../components/shared/ui/FilterVisibilityDropdown.vue'
import AttendanceSummaryBar from './attendance/AttendanceSummaryBar.vue'
import AttendanceDataTable from './attendance/AttendanceDataTable.vue'
import AttendanceParentNotifyPanel from './attendance/AttendanceParentNotifyPanel.vue'
import TpStudentDetailModal from '../../components/transportProgram/TpStudentDetailModal.vue'

import {
  confirmDayAttendance,
  getDayAttendance,
  listAbsenceReasons,
  listDayParentsNotifyLogs,
  listProgramDays,
  markDayAbsence,
  markDayPresent,
  notifyDayParents,
  previewDayParentsNotify,
  reopenDayAttendance,
  saveAttendanceDraft,
} from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { useVisibleFilterControls } from '../../composables/useVisibleFilterControls.js'
import {
  ATTENDANCE_PER_PAGE_OPTIONS,
  formatViDate,
  formatViDateTime,
  formatViTime,
  useTpAttendanceList,
} from '../../composables/useTpAttendanceList'
import { exportAttendanceExcel } from '../../composables/useTpAttendanceExcelExport'
import { slotsForProgram } from '../../composables/tpProgramSlots'
import { confirmAction } from '../../composables/useConfirm'
import { useAuthStore } from '../../store'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import { useTpDayLiveUpdates } from '../../composables/useTpDayLiveUpdates.js'

const ATTENDANCE_FILTER_CONTROLS = [
  { key: 'notes', label: 'Ghi chú', default: false },
  { key: 'boarded_time', label: 'Giờ lên xe', default: false },
]

const FILTER_CONTROL_CLASS =
  'input h-10 w-full text-sm rounded-lg border border-slate-200 bg-white px-3 text-slate-900 shadow-sm focus:border-va-700 focus:outline-none focus:ring-2 focus:ring-va-700/15'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const loading = ref(false)
const saving = ref(false)
const savingDraft = ref(false)
const confirming = ref(false)
const markingAllPresent = ref(false)
const bulkMarking = ref(false)
const exportingExcel = ref(false)
const notifying = ref(false)
const pendingRows = ref(new Set())

const studentDetailOpen = ref(false)
const studentDetailId = ref(null)
const studentDetailRow = ref(null)

function openStudentDetail(row) {
  studentDetailId.value = row?.student_id ?? null
  studentDetailRow.value = row ?? null
  studentDetailOpen.value = true
}

function closeStudentDetail() {
  studentDetailOpen.value = false
  studentDetailId.value = null
  studentDetailRow.value = null
}

const data = ref(null)
const reasons = ref([])
const siblingDays = ref([])

const {
  visibleFilters,
  hasFilterRow,
  showFilterPanelDd,
  openFilterPanel,
  closeFilterPanel,
  filterControlDefs,
} = useVisibleFilterControls(ATTENDANCE_FILTER_CONTROLS, 'va-tp-attendance-datagrid-filters.v1')

const datagridRef = ref(null)
const exportMenuRef = ref(null)
const showColPanelDd = ref(false)
useDetailsAutoCloseWithin(datagridRef)

function toggleFilterPanel() {
  showColPanelDd.value = false
  openFilterPanel()
}

function toggleColPanel() {
  closeFilterPanel()
  showColPanelDd.value = !showColPanelDd.value
}

function closeColPanel() {
  showColPanelDd.value = false
}

function onKpiQuickFilter(payload) {
  filters.displayStatus = payload?.display_status ?? ''
}

const {
  filters,
  sort,
  page,
  perPage,
  columnVisible,
  colOn,
  setColumn,
  selectedIds,
  toggleSelectAll,
  toggleSelectRow,
  clearSelection,
  filteredItems,
  pagedItems,
  totalFiltered,
  totalPages,
  activeFilterCount,
  resetFilters,
  toggleSort,
} = useTpAttendanceList(() => data.value?.items || [])

const perPageOptions = ATTENDANCE_PER_PAGE_OPTIONS

const pageRangeFrom = computed(() =>
  totalFiltered.value === 0 ? 0 : (page.value - 1) * perPage.value + 1,
)
const pageRangeTo = computed(() =>
  Math.min(page.value * perPage.value, totalFiltered.value),
)

const selectedCount = computed(() => selectedIds.value.size)
const selectedIdList = computed(() => [...selectedIds.value])
const filteredIdList = computed(() => filteredItems.value.map((r) => r.student_id))

const columnToggleOptions = [
  { id: 'class_name', labelKey: 'tp_attendance_page.col_class' },
  { id: 'boarded_time', labelKey: 'tp_attendance_page.col_boarded_time' },
  { id: 'status', labelKey: 'tp_attendance_page.col_status' },
  { id: 'notes', labelKey: 'tp_attendance_page.col_notes' },
  { id: 'pickup_point', labelKey: 'tp_attendance_page.col_pickup' },
  { id: 'parent_phone', labelKey: 'tp_attendance_page.col_parent_phone' },
  { id: 'code', labelKey: 'tp_attendance_page.col_code' },
  { id: 'attendance_toggle', labelKey: 'tp_attendance_page.col_toggle' },
]

const isConfirmed = computed(() => data.value?.attendance_status === 'confirmed')
const canReopen = computed(() => auth.user?.is_superadmin || auth.hasPermission('data.override_confirmed'))

const headerTitle = computed(() => data.value?.day?.program_name || t('tp_attendance_page.title'))

const headerMetaLine = computed(() => {
  const day = data.value?.day
  if (!day) return ''
  const parts = []
  const datePart = formatLongDate(day.scheduled_date)
  if (datePart) parts.push(datePart)
  parts.push(
    `${t('tp_attendance_page.header_departure_label')}: ${shiftDepartureDisplay.value || t('tp_attendance_page.header_departure_empty')}`,
  )
  parts.push(
    `${t('tp_attendance_page.header_driver_label')}: ${day.driver_name || t('tp_attendance_page.header_driver_empty')}`,
  )
  return parts.join(' · ')
})

const programForSlots = computed(() => {
  const day = data.value?.day
  if (!day) return null
  return { settings: day.settings, departure_time: day.departure_time, return_time: day.return_time }
})
const programSlots = computed(() => slotsForProgram(programForSlots.value))
const hasMorningShift = computed(() => programSlots.value.some((s) => s.shift === 'morning'))
const hasAfternoonShift = computed(() => programSlots.value.some((s) => s.shift === 'afternoon'))

const activeShift = computed(() => {
  const q = route.query.shift
  const shifts = programSlots.value.map((s) => s.shift)
  if (q === 'morning' || q === 'afternoon') {
    if (shifts.length && !shifts.includes(q)) return shifts[0] || 'morning'
    return q
  }
  return shifts[0] || 'morning'
})
const isMorningShift = computed(() => activeShift.value === 'morning')
const usesPerShiftAttendance = computed(() => {
  if (data.value && typeof data.value.multi_slot === 'boolean') return data.value.multi_slot
  return programSlots.value.length > 1
})

function shiftQueryOpts() {
  const shift = activeShift.value
  if (usesPerShiftAttendance.value) return { shift }
  if (!programForSlots.value && (route.query.shift === 'morning' || route.query.shift === 'afternoon')) {
    return { shift: route.query.shift }
  }
  return {}
}

const shiftLabel = computed(() =>
  isMorningShift.value ? t('tp_attendance_page.shift_morning') : t('tp_attendance_page.shift_afternoon'),
)

function formatClockTime(value) {
  return formatViTime(value)
}
const shiftDepartureDisplay = computed(() => {
  const slot = programSlots.value.find((s) => s.shift === activeShift.value)
  return slot?.departure ? formatClockTime(slot.departure) : ''
})

const sortedSiblings = computed(() => [...siblingDays.value].sort((a, b) => a.scheduled_date.localeCompare(b.scheduled_date)))
const hasPrevDay = computed(() => sortedSiblings.value.findIndex((d) => d.id === Number(route.params.dayId)) > 0)
const hasNextDay = computed(() => {
  const idx = sortedSiblings.value.findIndex((d) => d.id === Number(route.params.dayId))
  return idx >= 0 && idx < sortedSiblings.value.length - 1
})

async function refreshAttendanceQuiet() {
  if (!route.params.dayId || saving.value) return
  try {
    const att = await getDayAttendance(route.params.dayId, shiftQueryOpts())
    data.value = att
  } catch {
    /* nền — không làm gián đoạn thao tác điều vận */
  }
}

async function load() {
  loading.value = true
  data.value = null
  clearSelection()
  try {
    const [att, reasonList] = await Promise.all([
      getDayAttendance(route.params.dayId, shiftQueryOpts()),
      listAbsenceReasons().catch(() => ({ items: [] })),
    ])
    data.value = att
    reasons.value = reasonList.items || []
    if (att?.day?.program_id) {
      const month = att.day.scheduled_date?.slice(0, 7)
      const daysPayload = await listProgramDays(att.day.program_id, { month })
      siblingDays.value = daysPayload?.items || daysPayload || []
    }
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

const liveDayId = computed(() => route.params.dayId)
const liveShift = computed(() => {
  if (data.value && typeof data.value.multi_slot === 'boolean' && !data.value.multi_slot) {
    return null
  }
  if (programSlots.value.length <= 1 && data.value?.multi_slot !== true) {
    return null
  }
  return activeShift.value
})

const { start: startLiveUpdates, stop: stopLiveUpdates } = useTpDayLiveUpdates(liveDayId, liveShift, {
  onChange: refreshAttendanceQuiet,
})

watch(() => route.params.dayId, () => { if (route.params.dayId) load() })
watch(activeShift, (next, prev) => { if (prev && next !== prev && route.params.dayId) load() })
watch(
  () => [data.value?.day?.id, route.query.shift, programSlots.value.length],
  () => {
    if (!data.value?.day?.id) return
    const q = route.query.shift
    const shifts = programSlots.value.map((s) => s.shift)
    if ((q === 'morning' || q === 'afternoon') && shifts.includes(q)) return
    const fallback = shifts[0] || 'morning'
    if (q !== fallback) {
      router.replace({
        name: 'tpDayAttendance',
        params: { dayId: route.params.dayId },
        query: usesPerShiftAttendance.value ? { shift: fallback } : {},
      })
    }
  },
)

function categoryForReason(code) {
  const r = reasons.value.find((x) => x.code === code)
  return r?.default_category || 'excused'
}
function absenceTypeForCategory(category) {
  return category === 'excused' ? 'parent_notified' : 'no_notice'
}

async function togglePresent(s) {
  if (isConfirmed.value) return
  pendingRows.value = new Set([...pendingRows.value, s.student_id])
  saving.value = true
  try {
    if (s.status === 'attending') {
      data.value = await markDayAbsence(route.params.dayId, {
        student_ids: [s.student_id],
        absence_type: 'no_notice',
        category: 'unexcused',
        ...shiftQueryOpts(),
      })
    } else {
      data.value = await markDayPresent(route.params.dayId, { student_ids: [s.student_id], ...shiftQueryOpts() })
    }
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    const next = new Set(pendingRows.value)
    next.delete(s.student_id)
    pendingRows.value = next
    saving.value = false
  }
}

async function onReasonChange(s, code) {
  if (!code || isConfirmed.value) return
  const category = categoryForReason(code)
  pendingRows.value = new Set([...pendingRows.value, s.student_id])
  saving.value = true
  try {
    data.value = await markDayAbsence(route.params.dayId, {
      student_ids: [s.student_id],
      absence_type: absenceTypeForCategory(category),
      category,
      reason_code: code,
      absence_reason: s.absence_reason || undefined,
      ...shiftQueryOpts(),
    })
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    const next = new Set(pendingRows.value)
    next.delete(s.student_id)
    pendingRows.value = next
    saving.value = false
  }
}

async function onAbsenceNoteBlur(s, rawNote) {
  if (isConfirmed.value) return
  const note = (rawNote || '').trim()
  const prev = (s.absence_reason || '').trim()
  if (note === prev) return
  if (!note && !s.reason_code) return
  pendingRows.value = new Set([...pendingRows.value, s.student_id])
  saving.value = true
  try {
    const category = s.category || (s.reason_code ? categoryForReason(s.reason_code) : 'unexcused')
    const payload = {
      student_ids: [s.student_id],
      absence_type: s.absence_type || absenceTypeForCategory(category),
      category,
      absence_reason: note || undefined,
      ...shiftQueryOpts(),
    }
    if (s.reason_code) payload.reason_code = s.reason_code
    data.value = await markDayAbsence(route.params.dayId, payload)
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    const next = new Set(pendingRows.value)
    next.delete(s.student_id)
    pendingRows.value = next
    saving.value = false
  }
}

async function onBulkMarkPresent() {
  const ids = selectedIdList.value
  if (!ids.length) return
  bulkMarking.value = true
  saving.value = true
  try {
    data.value = await markDayPresent(route.params.dayId, { student_ids: ids, ...shiftQueryOpts() })
    clearSelection()
    showAppSuccess('Đã cập nhật.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    bulkMarking.value = false
    saving.value = false
  }
}

async function onMarkAllPresent() {
  const ok = await confirmAction({
    title: t('tp_attendance_page.mark_all_present'),
    message: 'Hành động này sẽ xóa toàn bộ vắng trong ngày và đặt tất cả học sinh thành Có mặt. Tiếp tục?',
    confirmLabel: t('tp_attendance_page.mark_all_present'),
  })
  if (!ok) return
  markingAllPresent.value = true
  saving.value = true
  try {
    data.value = await markDayPresent(route.params.dayId, { mark_all: true, ...shiftQueryOpts() })
    showAppSuccess('Đã đánh dấu tất cả học sinh có mặt.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    markingAllPresent.value = false
    saving.value = false
  }
}

async function onSaveDraft() {
  savingDraft.value = true
  saving.value = true
  try {
    data.value = await saveAttendanceDraft(route.params.dayId, shiftQueryOpts())
    showAppSuccess('Đã lưu nháp điểm danh.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    savingDraft.value = false
    saving.value = false
  }
}

async function onConfirm() {
  if (data.value?.missing_reason_count > 0) return
  const ok = await confirmAction({
    title: 'Xác nhận điểm danh',
    message: `Xác nhận điểm danh cho ngày ${formatShortDate(data.value?.day?.scheduled_date)}?`,
    confirmLabel: 'Xác nhận',
  })
  if (!ok) return
  confirming.value = true
  saving.value = true
  try {
    data.value = await confirmDayAttendance(route.params.dayId, data.value.attendance_lock_version, shiftQueryOpts())
    showAppSuccess('Điểm danh đã được xác nhận thành công.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    confirming.value = false
    saving.value = false
  }
}

async function onReopen() {
  const ok = await confirmAction({ title: 'Mở lại điểm danh', message: 'Mở lại điểm danh đã xác nhận?', confirmLabel: 'Mở lại' })
  if (!ok) return
  saving.value = true
  try {
    data.value = await reopenDayAttendance(route.params.dayId, shiftQueryOpts())
    showAppSuccess('Đã mở lại điểm danh.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    saving.value = false
  }
}

function exportMeta() {
  return {
    programName: data.value?.day?.program_name || '',
    date: formatShortDate(data.value?.day?.scheduled_date),
    shiftLabel: shiftLabel.value,
    driverName: data.value?.day?.driver_name || '',
    sessionLabel:
      data.value?.attendance_status === 'confirmed'
        ? t('tp_attendance_page.session_confirmed')
        : t('tp_attendance_page.session_draft'),
  }
}

function rowsForExport(mode) {
  if (mode === 'page') return pagedItems.value
  if (mode === 'selected') {
    const set = selectedIds.value
    return filteredItems.value.filter((r) => set.has(r.student_id))
  }
  return filteredItems.value
}

async function runExport(mode) {
  exportMenuRef.value?.removeAttribute?.('open')
  const rows = rowsForExport(mode)
  if (!rows.length) return
  exportingExcel.value = true
  try {
    await exportAttendanceExcel({
      t,
      rows,
      columnVisible: columnVisible.value,
      meta: exportMeta(),
      reasons: reasons.value,
    })
  } catch (err) {
    showAppErrorFromApi(err, t('tp_attendance_page.export_fail'))
  } finally {
    exportingExcel.value = false
  }
}

const notifyOpen = ref(false)
const notifyPreview = ref(null)
const notifyPreviewLoading = ref(false)
const notifyLogs = ref([])
let notifyScope = 'selected'

function notifyBody(scope, studentIds = [], retryBatchId = null) {
  const body = { scope, student_ids: studentIds, ...shiftQueryOpts() }
  if (retryBatchId) body.retry_batch_id = retryBatchId
  return body
}

async function loadNotifyLogs() {
  try {
    const res = await listDayParentsNotifyLogs(route.params.dayId)
    notifyLogs.value = res.items || []
  } catch {
    notifyLogs.value = []
  }
}

async function loadNotifyPreview(scope = notifyScope) {
  notifyScope = scope
  notifyPreviewLoading.value = true
  try {
    const ids = scope === 'filtered' ? filteredIdList.value : scope === 'selected' ? selectedIdList.value : []
    notifyPreview.value = await previewDayParentsNotify(route.params.dayId, notifyBody(scope, ids))
  } catch (err) {
    notifyPreview.value = null
    showAppErrorFromApi(err)
  } finally {
    notifyPreviewLoading.value = false
  }
}

function openNotifyPanel() {
  notifyOpen.value = true
  loadNotifyLogs()
  loadNotifyPreview(selectedCount.value ? 'selected' : 'all_absent')
}

async function onSendNotify({ scope, studentIds }) {
  notifying.value = true
  try {
    const res = await notifyDayParents(route.params.dayId, notifyBody(scope, studentIds))
    showAppSuccess(`Đã xếp hàng thông báo cho ${res.queued} phụ huynh.`)
    await loadNotifyLogs()
    notifyOpen.value = false
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    notifying.value = false
  }
}

async function onRetryNotify(batchId) {
  if (!batchId) return
  notifying.value = true
  try {
    await notifyDayParents(route.params.dayId, notifyBody('all_absent', [], batchId))
    showAppSuccess('Đã gửi lại.')
    await loadNotifyLogs()
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    notifying.value = false
  }
}

async function shiftDay(delta) {
  const idx = sortedSiblings.value.findIndex((d) => d.id === Number(route.params.dayId))
  const next = sortedSiblings.value[idx + delta]
  if (next) {
    const query = usesPerShiftAttendance.value ? { shift: activeShift.value } : {}
    await router.push({ name: 'tpDayAttendance', params: { dayId: next.id }, query })
  }
}

function trySwitchShift(which) {
  if (which === activeShift.value) return
  if (which === 'morning' && !hasMorningShift.value) return
  if (which === 'afternoon' && !hasAfternoonShift.value) return
  router.replace({ name: 'tpDayAttendance', params: { dayId: route.params.dayId }, query: { shift: which } })
}

function goBack() {
  if (data.value?.day?.program_id) {
    router.push({ name: 'tpProgramWorkspace', params: { id: data.value.day.program_id } })
  } else {
    router.back()
  }
}

function formatShortDate(iso) {
  if (!iso) return t('tp_attendance_page.empty_date')
  return formatViDate(iso)
}
function formatLongDate(iso) {
  if (!iso) return ''
  const dt = new Date(`${iso}T12:00:00`)
  const wd = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'][dt.getDay()]
  return `${wd}, ${formatShortDate(iso)}`
}
function formatDateTime(isoOrDatetime) {
  return formatViDateTime(isoOrDatetime) || t('tp_attendance_page.empty_date')
}

onMounted(async () => {
  await load()
  startLiveUpdates()
})
onUnmounted(() => stopLiveUpdates())
</script>
