<template>
  <div class="w-full min-w-0 space-y-4 pb-28">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div class="min-w-0 space-y-0.5">
        <button
          type="button"
          class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600"
          @click="goBack"
        >
          <ArrowLeftIcon class="h-3.5 w-3.5" />
          Quay lại
        </button>
        <nav class="text-xs text-slate-500" aria-label="breadcrumb">
          <span>Chương trình Đưa đón</span>
          <span v-if="data?.day?.program_name"> › {{ data.day.program_name }}</span>
          <span> › {{ t('tp_attendance_page.title') }}</span>
        </nav>
        <div class="flex flex-wrap items-center gap-2 pt-0.5">
          <h1 class="text-xl font-bold tracking-tight text-slate-900">{{ t('tp_attendance_page.title') }}</h1>
          <span class="rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-semibold text-sky-800">{{ shiftLabel }}</span>
          <span
            v-if="data?.attendance_status === 'confirmed'"
            class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800"
          >
            <CheckCircleIcon class="h-3.5 w-3.5" />
            {{ t('tp_attendance_page.session_confirmed') }}
          </span>
          <span
            v-else-if="data?.attendance_status === 'draft'"
            class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800"
          >
            <ClockIcon class="h-3.5 w-3.5" />
            {{ t('tp_attendance_page.session_draft') }}
          </span>
        </div>
        <p v-if="data?.day" class="flex flex-wrap items-center gap-1.5 text-sm text-slate-500">
          <CalendarDaysIcon class="h-4 w-4 shrink-0" />
          {{ formatLongDate(data.day.scheduled_date) }}
          <span v-if="shiftDepartureDisplay" class="before:mr-1 before:content-['·']">{{ shiftDepartureDisplay }}</span>
          <span v-if="data.day.driver_name" class="before:mr-1 before:content-['·']">
            <TruckIcon class="mb-0.5 mr-0.5 inline h-3.5 w-3.5" />{{ data.day.driver_name }}
          </span>
        </p>
      </div>
    </div>

    <div
      v-if="data"
      class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-sm"
    >
      <div class="flex items-center gap-1">
        <button
          type="button"
          :disabled="!hasPrevDay"
          class="rounded-lg border border-slate-200 p-1.5 text-slate-500 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
          aria-label="Ngày trước"
          @click="shiftDay(-1)"
        >
          <ChevronLeftIcon class="h-4 w-4" />
        </button>
        <span class="min-w-[96px] text-center text-sm font-semibold text-slate-800">
          {{ formatShortDate(data.day.scheduled_date) }}
        </span>
        <button
          type="button"
          :disabled="!hasNextDay"
          class="rounded-lg border border-slate-200 p-1.5 text-slate-500 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
          aria-label="Ngày sau"
          @click="shiftDay(1)"
        >
          <ChevronRightIcon class="h-4 w-4" />
        </button>
      </div>
      <div class="flex items-center rounded-lg border border-slate-200 p-0.5 text-sm" role="group" aria-label="Chọn ca">
        <button
          type="button"
          class="rounded-md px-4 py-1.5 font-medium transition disabled:cursor-not-allowed disabled:opacity-40"
          :class="isMorningShift ? 'bg-va-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
          :disabled="!hasMorningShift"
          @click="trySwitchShift('morning')"
        >Sáng</button>
        <button
          type="button"
          class="rounded-md px-4 py-1.5 font-medium transition disabled:cursor-not-allowed disabled:opacity-40"
          :class="!isMorningShift ? 'bg-va-800 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
          :disabled="!hasAfternoonShift"
          @click="trySwitchShift('afternoon')"
        >Chiều</button>
      </div>
    </div>

    <template v-if="loading">
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div v-for="i in 4" :key="i" class="h-24 animate-pulse rounded-2xl bg-slate-100" />
      </div>
      <div class="h-12 animate-pulse rounded-2xl bg-slate-100" />
    </template>

    <template v-else-if="data">
      <AttendanceStatsBar :summary="data.summary" />

      <div
        v-if="data.attendance_status === 'confirmed'"
        class="flex items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3"
      >
        <div class="flex items-center gap-2 text-sm font-medium text-emerald-800">
          <LockClosedIcon class="h-4 w-4 shrink-0" />
          Điểm danh đã xác nhận. Dữ liệu ở chế độ chỉ đọc.
        </div>
        <Button v-if="canReopen" variant="secondary" class="shrink-0 text-xs" :loading="saving" @click="onReopen">
          Mở lại chỉnh sửa
        </Button>
      </div>

      <div class="sticky top-0 z-20 -mx-1 px-1 pt-1">
        <AppFilterBar>
          <div class="flex w-full flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
            <div class="relative min-w-[200px] flex-1 basis-[200px]">
              <MagnifyingGlassIcon
                class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
              />
              <input
                v-model="filters.q"
                type="search"
                :placeholder="t('tp_attendance_page.search_placeholder')"
                class="h-9 w-full rounded-lg border-0 bg-white/90 pl-8 pr-3 text-sm ring-1 ring-slate-200/80 focus:ring-2 focus:ring-teal-500/30"
                :aria-label="t('tp_attendance_page.search_placeholder')"
              />
            </div>

            <button
              type="button"
              class="inline-flex shrink-0 items-center justify-center rounded-lg border border-white/80 bg-white/90 p-2 text-slate-600 shadow-sm hover:bg-white disabled:opacity-40"
              :disabled="isConfirmed || saving"
              :title="t('tp_attendance_page.mark_all_present')"
              :aria-label="t('tp_attendance_page.mark_all_present_aria')"
              @click="onMarkAllPresent"
            >
              <CheckIcon class="h-5 w-5" :class="markingAllPresent ? 'animate-pulse' : ''" />
            </button>

            <details ref="columnPickerRef" class="group relative shrink-0">
              <summary
                class="flex cursor-pointer list-none items-center gap-1 rounded-lg border border-white/80 bg-white/90 px-2 py-1.5 text-slate-700 shadow-sm transition hover:bg-white [&::-webkit-details-marker]:hidden"
                :aria-label="t('tp_attendance_page.table_columns')"
              >
                <ViewColumnsIcon class="h-5 w-5 shrink-0 text-slate-600" />
                <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" />
              </summary>
              <div
                class="absolute left-0 top-[calc(100%+6px)] z-50 min-w-[220px] rounded-xl border border-slate-200/90 bg-white p-3 text-sm shadow-lg ring-1 ring-slate-900/5"
                @click.stop
              >
                <ul class="max-h-[min(50vh,280px)] space-y-2 overflow-y-auto">
                  <li v-for="opt in columnToggleOptions" :key="opt.id" class="flex items-center gap-2">
                    <input
                      :id="`att-col-${opt.id}`"
                      type="checkbox"
                      class="rounded border-slate-300 text-teal-600"
                      :checked="columnVisible[opt.id] !== false"
                      @change="setColumn(opt.id, $event.target.checked)"
                    />
                    <label :for="`att-col-${opt.id}`" class="cursor-pointer text-xs">{{ t(opt.labelKey) }}</label>
                  </li>
                </ul>
              </div>
            </details>

            <AppFilterFunnelMenu ref="filterMenuRef" :badge-count="activeFilterCount">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                {{ t('tp_attendance_page.filter_menu_title') }}
              </p>
              <ul class="mt-2 space-y-2 text-sm text-slate-700">
                <li v-if="filters.q.trim()" class="flex justify-between gap-2">
                  <span class="text-slate-500">Tìm kiếm</span>
                  <span class="max-w-[11rem] truncate font-medium">{{ filters.q }}</span>
                </li>
                <li v-if="filters.className" class="flex justify-between gap-2">
                  <span class="text-slate-500">{{ t('tp_attendance_page.filter_vis_class') }}</span>
                  <span class="font-medium">{{ filters.className }}</span>
                </li>
                <li v-if="filters.status" class="flex justify-between gap-2">
                  <span class="text-slate-500">{{ t('tp_attendance_page.filter_vis_status') }}</span>
                  <span class="font-medium">{{ statusFilterLabel(filters.status) }}</span>
                </li>
                <li v-if="filters.pickup" class="flex justify-between gap-2">
                  <span class="text-slate-500">{{ t('tp_attendance_page.filter_vis_pickup') }}</span>
                  <span class="font-medium">{{ filters.pickup }}</span>
                </li>
                <li v-if="filters.grade" class="flex justify-between gap-2">
                  <span class="text-slate-500">{{ t('tp_attendance_page.filter_vis_grade') }}</span>
                  <span class="font-medium">{{ filters.grade }}</span>
                </li>
                <li v-if="filters.boardedFrom || filters.boardedTo" class="flex justify-between gap-2">
                  <span class="text-slate-500">{{ t('tp_attendance_page.filter_vis_boarded_time') }}</span>
                  <span class="font-medium">{{ filters.boardedFrom || '…' }} – {{ filters.boardedTo || '…' }}</span>
                </li>
                <li v-if="filters.marked" class="flex justify-between gap-2">
                  <span class="text-slate-500">{{ t('tp_attendance_page.notify_scope_not_marked') }}</span>
                  <span class="font-medium">{{ t('tp_attendance_page.filter_on') }}</span>
                </li>
              </ul>
              <div class="mt-4 border-t border-slate-100 pt-3">
                <p class="text-xs font-semibold text-slate-700">{{ t('tp_attendance_page.filter_show_controls_title') }}</p>
                <p class="mt-0.5 text-[11px] text-slate-500">{{ t('tp_attendance_page.filter_show_controls_hint') }}</p>
                <ul class="mt-2 space-y-2">
                  <li v-for="opt in filterBarVisibilityOptions" :key="opt.id" class="flex items-center gap-2">
                    <input
                      :id="`attendance-filter-vis-${opt.id}`"
                      v-model="filterBarVisible[opt.id]"
                      type="checkbox"
                      class="rounded border-slate-300 text-teal-600"
                    />
                    <label :for="`attendance-filter-vis-${opt.id}`" class="cursor-pointer text-sm">{{ t(opt.labelKey) }}</label>
                  </li>
                </ul>
              </div>
              <button
                type="button"
                class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="resetFilters(); closeFilterMenu()"
              >
                {{ t('tp_attendance_page.filter_clear_all') }}
              </button>
            </AppFilterFunnelMenu>

            <div class="hidden h-6 w-px bg-slate-200/90 sm:block" aria-hidden="true" />

            <button
              type="button"
              class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1.5 text-slate-500 hover:bg-white/70 hover:text-slate-800"
              :disabled="activeFilterCount === 0"
              @click="resetFilters()"
            >
              <FunnelIcon class="h-4 w-4" />
              <XMarkIcon class="h-3.5 w-3.5" />
            </button>

            <details ref="exportMenuRef" class="relative ml-auto shrink-0">
              <summary
                class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-white/80 bg-white/90 px-2.5 py-1.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-white disabled:opacity-50 [&::-webkit-details-marker]:hidden"
                :class="exportingExcel ? 'pointer-events-none opacity-60' : ''"
                :aria-label="t('tp_attendance_page.export_excel_aria')"
              >
                <ArrowDownTrayIcon class="h-5 w-5 text-slate-600" />
                <span class="hidden sm:inline">{{ exportingExcel ? t('tp_attendance_page.export_excel_busy') : t('tp_attendance_page.export_excel') }}</span>
              </summary>
              <div class="absolute right-0 top-[calc(100%+6px)] z-50 min-w-[200px] rounded-xl border border-slate-200 bg-white py-1 shadow-lg">
                <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="runExport('all')">
                  {{ t('tp_attendance_page.export_scope_all') }}
                </button>
                <button type="button" class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50" @click="runExport('page')">
                  {{ t('tp_attendance_page.export_scope_page') }}
                </button>
                <button
                  type="button"
                  class="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50 disabled:text-slate-400"
                  :disabled="selectedCount === 0"
                  @click="runExport('selected')"
                >
                  {{ t('tp_attendance_page.export_scope_selected') }}
                </button>
              </div>
            </details>
          </div>

          <div
            v-if="hasVisibleBarFilters"
            class="flex w-full flex-wrap items-center gap-x-2 gap-y-2 border-t border-slate-200/80 pt-2"
          >
            <select
              v-if="filterBarVisible.class"
              v-model="filters.className"
              class="h-9 rounded-lg border-0 bg-white/90 pl-2 pr-6 text-sm ring-1 ring-slate-200/80"
              :class="filters.className ? 'text-slate-900' : 'text-slate-500'"
            >
              <option value="">{{ t('tp_attendance_page.filter_vis_class') }}</option>
              <option v-for="c in classOptions" :key="c" :value="c">{{ c }}</option>
            </select>
            <select
              v-if="filterBarVisible.status"
              v-model="filters.status"
              class="h-9 rounded-lg border-0 bg-white/90 pl-2 pr-6 text-sm ring-1 ring-slate-200/80"
              :class="filters.status ? 'text-slate-900' : 'text-slate-500'"
            >
              <option value="">{{ t('tp_attendance_page.filter_vis_status') }}</option>
              <option value="present">{{ t('tp_attendance_page.status_present') }}</option>
              <option value="excused">{{ t('tp_attendance_page.status_excused') }}</option>
              <option value="unexcused">{{ t('tp_attendance_page.status_unexcused') }}</option>
            </select>
            <select
              v-if="filterBarVisible.pickup && pickupOptions.length"
              v-model="filters.pickup"
              class="h-9 rounded-lg border-0 bg-white/90 pl-2 pr-6 text-sm ring-1 ring-slate-200/80"
              :class="filters.pickup ? 'text-slate-900' : 'text-slate-500'"
            >
              <option value="">{{ t('tp_attendance_page.filter_vis_pickup') }}</option>
              <option v-for="p in pickupOptions" :key="p" :value="p">{{ p }}</option>
            </select>
            <select
              v-if="filterBarVisible.grade && gradeOptions.length"
              v-model="filters.grade"
              class="h-9 rounded-lg border-0 bg-white/90 pl-2 pr-6 text-sm ring-1 ring-slate-200/80"
              :class="filters.grade ? 'text-slate-900' : 'text-slate-500'"
            >
              <option value="">{{ t('tp_attendance_page.filter_vis_grade') }}</option>
              <option v-for="g in gradeOptions" :key="g" :value="g">{{ g }}</option>
            </select>
            <template v-if="filterBarVisible.boarded_time">
              <input
                v-model="filters.boardedFrom"
                type="time"
                class="h-9 rounded-lg border-0 bg-white/90 px-2 text-sm ring-1 ring-slate-200/80"
                :aria-label="t('tp_attendance_page.filter_vis_boarded_time')"
              />
              <span class="text-xs text-slate-400">–</span>
              <input
                v-model="filters.boardedTo"
                type="time"
                class="h-9 rounded-lg border-0 bg-white/90 px-2 text-sm ring-1 ring-slate-200/80"
              />
            </template>
            <select
              v-if="filterBarVisible.sort"
              v-model="sortPreset"
              class="h-9 rounded-lg border-0 bg-white/90 pl-2 pr-6 text-sm ring-1 ring-slate-200/80 text-slate-900"
            >
              <option v-for="o in sortPresets" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
            <select
              v-if="filterBarVisible.per_page"
              v-model.number="perPage"
              class="h-9 rounded-lg border-0 bg-white/90 pl-2 pr-6 text-sm ring-1 ring-slate-200/80 text-slate-900"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
            </select>
          </div>
        </AppFilterBar>
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
      >
        <template #bulk-actions>
          <Button variant="secondary" class="!py-1 !text-xs" :disabled="isConfirmed" @click="openNotifyPanel">
            <BellIcon class="h-4 w-4" />
            {{ t('tp_attendance_page.bulk_notify') }}
          </Button>
          <Button
            variant="secondary"
            class="!py-1 !text-xs"
            :disabled="isConfirmed || !selectedCount"
            :loading="bulkMarking"
            @click="onBulkMarkPresent"
          >
            {{ t('tp_attendance_page.bulk_mark_present') }}
          </Button>
        </template>
      </AttendanceDataTable>

      <div v-if="totalFiltered > perPage" class="flex items-center justify-between text-sm text-slate-600">
        <span>{{ t('tp_attendance_page.pagination', { page, total: totalPages }) }}</span>
        <div class="flex gap-1">
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-2 py-1 disabled:opacity-40"
            :disabled="page <= 1"
            @click="page--"
          >
            <ChevronLeftIcon class="h-4 w-4" />
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-2 py-1 disabled:opacity-40"
            :disabled="page >= totalPages"
            @click="page++"
          >
            <ChevronRightIcon class="h-4 w-4" />
          </button>
        </div>
      </div>
    </template>

    <div
      v-if="data && !loading"
      class="sticky bottom-0 z-30 pt-2 supports-[padding:max(0px)]:pb-[max(0.75rem,env(safe-area-inset-bottom))]"
    >
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white/95 shadow-2xl shadow-slate-900/15 backdrop-blur-md">
        <div
          v-if="data.missing_reason_count > 0"
          class="flex items-center gap-2 border-b border-amber-100 bg-amber-50 px-4 py-2.5 text-sm"
        >
          <ExclamationTriangleIcon class="h-4 w-4 shrink-0 text-amber-600" />
          <span class="font-medium text-amber-900">
            {{ data.missing_reason_count }} học sinh chưa có lý do vắng (chọn danh mục hoặc nhập ghi chú)
          </span>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-3">
          <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-600">
            <span>
              <span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-emerald-500" />
              Có mặt: <strong class="text-slate-800">{{ data.summary.present }}</strong>
            </span>
            <span>
              <span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-amber-400" />
              Có phép: <strong class="text-slate-800">{{ data.summary.excused }}</strong>
            </span>
            <span>
              <span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-rose-500" />
              Không phép: <strong class="text-slate-800">{{ data.summary.unexcused }}</strong>
            </span>
          </div>
          <div v-if="!isConfirmed" class="flex items-center gap-2">
            <Button variant="secondary" :disabled="saving" :loading="savingDraft" @click="onSaveDraft">Lưu nháp</Button>
            <Button :disabled="saving || data.missing_reason_count > 0" :loading="confirming" @click="onConfirm">
              <LockClosedIcon class="h-4 w-4" />
              Xác nhận điểm danh
            </Button>
          </div>
          <div v-else class="text-sm font-medium text-emerald-700">
            <CheckCircleIcon class="mr-1 inline h-4 w-4" />
            Đã xác nhận lúc {{ formatTime(data.attendance_confirmed_at) }}
          </div>
        </div>
      </div>
    </div>

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
  </div>
</template>

<script setup>
import { computed, onActivated, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  BellIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  CheckIcon,
  ChevronDownIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  FunnelIcon,
  LockClosedIcon,
  MagnifyingGlassIcon,
  TruckIcon,
  ViewColumnsIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

import Button from '../../components/ui/Button.vue'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterFunnelMenu from '../../components/filters/AppFilterFunnelMenu.vue'
import AttendanceStatsBar from './attendance/AttendanceStatsBar.vue'
import AttendanceDataTable from './attendance/AttendanceDataTable.vue'
import AttendanceParentNotifyPanel from './attendance/AttendanceParentNotifyPanel.vue'

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
import { useFilterBarVisibility } from '../../composables/useFilterBarVisibility'
import {
  ATTENDANCE_FILTER_VIS_IDS,
  useTpAttendanceList,
} from '../../composables/useTpAttendanceList'
import { exportAttendanceExcel } from '../../composables/useTpAttendanceExcelExport'
import { slotsForProgram } from '../../composables/tpProgramSlots'
import { confirmAction } from '../../composables/useConfirm'
import { useAuthStore } from '../../store'
import { useDetailsAutoClose } from '../../composables/useDetailsAutoClose.js'

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

const data = ref(null)
const reasons = ref([])
const siblingDays = ref([])

const filterBarDefaults = Object.fromEntries(ATTENDANCE_FILTER_VIS_IDS.map((id) => [id, false]))
const { visible: filterBarVisible, resetVisibility: resetFilterBarVisibility, hasVisibleOnBar: hasVisibleBarFilters } =
  useFilterBarVisibility(ATTENDANCE_FILTER_VIS_IDS, filterBarDefaults)

const filterMenuRef = ref(null)
const columnPickerRef = ref(null)
const exportMenuRef = ref(null)
useDetailsAutoClose(columnPickerRef)
useDetailsAutoClose(exportMenuRef)

function onAttendanceFilterBarEnter() {
  resetFilterBarVisibility()
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

const filterBarVisibilityOptions = [
  { id: 'class', labelKey: 'tp_attendance_page.filter_vis_class' },
  { id: 'status', labelKey: 'tp_attendance_page.filter_vis_status' },
  { id: 'pickup', labelKey: 'tp_attendance_page.filter_vis_pickup' },
  { id: 'grade', labelKey: 'tp_attendance_page.filter_vis_grade' },
  { id: 'boarded_time', labelKey: 'tp_attendance_page.filter_vis_boarded_time' },
  { id: 'sort', labelKey: 'tp_attendance_page.filter_vis_sort' },
  { id: 'per_page', labelKey: 'tp_attendance_page.filter_vis_per_page' },
]

const sortPresets = computed(() => [
  { value: 'student_asc', label: `${t('tp_attendance_page.col_student')} A→Z` },
  { value: 'student_desc', label: `${t('tp_attendance_page.col_student')} Z→A` },
  { value: 'status_asc', label: t('tp_attendance_page.col_status') },
  { value: 'boarded_time_desc', label: t('tp_attendance_page.col_boarded_time') },
])

const sortPreset = computed({
  get: () => `${sort.key}_${sort.dir}`,
  set: (v) => {
    const s = String(v)
    const i = s.lastIndexOf('_')
    sort.key = i > 0 ? s.slice(0, i) : 'student'
    sort.dir = i > 0 ? s.slice(i + 1) : 'asc'
  },
})

const isConfirmed = computed(() => data.value?.attendance_status === 'confirmed')
const canReopen = computed(() => auth.user?.is_superadmin || auth.hasPermission('data.override_confirmed'))

const classOptions = computed(() => {
  const s = new Set((data.value?.items || []).map((i) => i.class_name).filter(Boolean))
  return [...s].sort()
})
const pickupOptions = computed(() => {
  const s = new Set((data.value?.items || []).map((i) => i.pickup_point).filter(Boolean))
  return [...s].sort()
})
const gradeOptions = computed(() => {
  const s = new Set((data.value?.items || []).map((i) => i.grade).filter(Boolean))
  return [...s].sort()
})

function statusFilterLabel(v) {
  if (v === 'present') return t('tp_attendance_page.status_present')
  if (v === 'excused') return t('tp_attendance_page.status_excused')
  return t('tp_attendance_page.status_unexcused')
}

function closeFilterMenu() {
  filterMenuRef.value?.close?.()
}

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

const shiftLabel = computed(() => (isMorningShift.value ? 'Chuyến Sáng' : 'Chuyến Chiều'))

function formatClockTime(value) {
  if (!value) return ''
  return String(value).slice(0, 5)
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
  if (!iso) return '—'
  const [y, m, d] = iso.split('-')
  return `${d}/${m}/${y}`
}
function formatLongDate(iso) {
  if (!iso) return ''
  const dt = new Date(`${iso}T12:00:00`)
  const wd = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'][dt.getDay()]
  return `${wd}, ${formatShortDate(iso)}`
}
function formatTime(isoOrDatetime) {
  if (!isoOrDatetime) return ''
  try {
    return new Date(isoOrDatetime).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
  } catch {
    return ''
  }
}

onMounted(() => {
  onAttendanceFilterBarEnter()
  load()
})
onActivated(onAttendanceFilterBarEnter)
</script>
