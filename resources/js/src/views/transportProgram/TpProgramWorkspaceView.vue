<template>
  <div class="space-y-6 pb-10">
    <!-- Loading -->
    <div v-if="!program" class="flex items-center justify-center py-20 text-base text-slate-500">
      <ArrowPathIcon class="mr-2 h-6 w-6 animate-spin" /> {{ t('tp_program_detail.loading_program') }}
    </div>

    <template v-else>
      <!-- Page header (no card border) -->
      <div class="flex flex-col gap-4 border-b border-slate-200/80 pb-6 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
          <button
            class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-va-800 transition hover:text-va-900"
            data-testid="tp-program-back-list"
            @click="goList"
          >
            <ArrowLeftIcon class="h-4 w-4" /> {{ t('tp_program_detail.back_to_list') }}
          </button>
          <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">{{ program.name }}</h1>
            <span :class="statusBadge(program.status)">
              <span class="h-1.5 w-1.5 rounded-full bg-current opacity-80"></span>
              {{ statusLabel(program.status) }}
            </span>
          </div>
          <div
            class="mt-2.5 flex flex-wrap items-center gap-2"
            role="list"
            :aria-label="t('tp_program_detail.meta_aria')"
            data-testid="tp-program-meta"
          >
            <span
              role="listitem"
              class="inline-flex max-w-full items-center gap-1.5 rounded-lg border border-slate-200/80 bg-slate-50/90 px-2.5 py-1 text-xs text-slate-600"
            >
              <TagIcon class="h-3.5 w-3.5 shrink-0 text-slate-400" aria-hidden="true" />
              <span class="font-mono text-[11px] font-semibold tabular-nums text-slate-800">{{ program.code }}</span>
            </span>

            <span
              role="listitem"
              class="inline-flex max-w-full min-w-0 items-center gap-1.5 rounded-lg border border-slate-200/80 bg-slate-50/90 px-2.5 py-1 text-xs text-slate-600"
            >
              <CalendarDaysIcon class="h-3.5 w-3.5 shrink-0 text-slate-400" aria-hidden="true" />
              <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                {{ t('tp_program_detail.meta_label_period') }}
              </span>
              <span
                class="min-w-0 truncate tabular-nums font-medium"
                :class="hasDateRange ? 'text-slate-800' : 'italic text-slate-400'"
              >
                {{ dateRangeText }}
              </span>
            </span>

            <span
              role="listitem"
              class="inline-flex max-w-full min-w-0 items-center gap-1.5 rounded-lg border border-slate-200/80 bg-slate-50/90 px-2.5 py-1 text-xs text-slate-600"
            >
              <UserCircleIcon class="h-3.5 w-3.5 shrink-0 text-slate-400" aria-hidden="true" />
              <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                {{ t('tp_program_detail.meta_label_responsible') }}
              </span>
              <span
                class="min-w-0 max-w-[12rem] truncate font-medium sm:max-w-[16rem]"
                :class="responsibleName ? 'text-slate-800' : 'italic text-slate-400'"
                :title="responsibleName || undefined"
              >
                {{ responsibleName || t('tp_program_detail.empty_responsible') }}
              </span>
            </span>

            <span
              role="listitem"
              class="inline-flex max-w-full min-w-0 items-center gap-1.5 rounded-lg border border-slate-200/80 bg-slate-50/90 px-2.5 py-1 text-xs text-slate-600"
            >
              <MapPinIcon class="h-3.5 w-3.5 shrink-0 text-slate-400" aria-hidden="true" />
              <template v-if="hasRoute">
                <span class="min-w-0 max-w-[9rem] truncate font-medium text-slate-800 sm:max-w-[11rem]" :title="program.origin_name">
                  {{ program.origin_name }}
                </span>
                <ArrowLongRightIcon class="h-3.5 w-3.5 shrink-0 text-slate-400" aria-hidden="true" />
                <span
                  class="min-w-0 max-w-[9rem] truncate font-medium text-slate-800 sm:max-w-[11rem]"
                  :title="routeDestinationName"
                >
                  {{ routeDestinationName }}
                </span>
              </template>
              <span v-else class="italic text-slate-400">{{ t('tp_program_detail.empty_route') }}</span>
            </span>
          </div>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
          <button
            v-if="program.status === 'draft' || program.status === 'paused'"
            class="inline-flex items-center gap-1.5 rounded-xl border border-va-800 bg-va-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-va-900"
            data-testid="tp-program-lifecycle-activate"
            @click="lifecycle('activate')"
          >
            <BoltIcon class="h-4 w-4" /> Kích hoạt
          </button>
          <button
            v-if="program.status === 'active'"
            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50"
            data-testid="tp-program-lifecycle-pause"
            @click="lifecycle('pause')"
          >
            <PauseIcon class="h-4 w-4" /> Tạm dừng
          </button>
          <button
            v-if="program.status !== 'cancelled'"
            class="inline-flex items-center gap-1.5 rounded-xl border border-rose-300 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm transition hover:border-rose-400 hover:bg-rose-50"
            data-testid="tp-program-lifecycle-cancel"
            @click="lifecycle('cancel')"
          >
            <XMarkIcon class="h-4 w-4" /> Hủy
          </button>
        </div>
      </div>

      <TpProgramDetailSummaryBar
        :program="program"
        :operating-day-count="operatingDayCount"
        :days-loading="daysLoading"
      />

      <!-- Tab bar -->
      <div class="flex gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          :class="[
            'inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-4 py-2.5 text-sm font-semibold transition',
            active === tab.key ? 'bg-[color:var(--va-brand)] text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700',
          ]"
          :data-testid="`tp-program-tab-${tab.key}`"
          @click="active = tab.key"
        >
          <component :is="tab.icon" class="h-4 w-4" />
          {{ tab.label }}
        </button>
      </div>

      <component
        :is="activeComponent"
        :program="program"
        :days="days"
        :days-loading="daysLoading"
        @refresh="load"
      />
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowLeftIcon,
  ArrowLongRightIcon,
  ArrowPathIcon,
  BoltIcon,
  PauseIcon,
  XMarkIcon,
  ChartBarIcon,
  CalendarIcon,
  CalendarDaysIcon,
  MapPinIcon,
  TagIcon,
  UserCircleIcon,
  UsersIcon,
  ClipboardDocumentCheckIcon,
  IdentificationIcon,
} from '@heroicons/vue/24/outline'
import { getProgram, activateProgram, pauseProgram, cancelProgram, listProgramDays } from '../../api/transportProgram'
import { formatViDate } from '../../composables/useTpAttendanceList'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import TpProgramDetailSummaryBar from '../../components/transportProgram/TpProgramDetailSummaryBar.vue'
import OverviewTab from './tabs/OverviewTab.vue'
import ScheduleTab from './tabs/ScheduleTab.vue'
import StudentsTab from './tabs/StudentsTab.vue'
import AttendanceTab from './tabs/AttendanceTab.vue'
import DriverAssignmentTab from './tabs/DriverAssignmentTab.vue'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const program = ref(null)
const days = ref([])
const daysLoading = ref(false)
const active = ref('overview')

const operatingDayCount = computed(() => days.value.filter((d) => d.day_type === 'operating').length)

const hasDateRange = computed(() => Boolean(program.value?.start_date && program.value?.end_date))

const dateRangeText = computed(() => {
  const p = program.value
  if (!hasDateRange.value) return t('tp_program_detail.empty_date_range')
  const from = formatViDate(p.start_date)
  const to = formatViDate(p.end_date)
  return `${from} – ${to}`
})

const responsibleName = computed(() => program.value?.responsible_user_name?.trim() || '')

const hasRoute = computed(() => Boolean(program.value?.origin_name?.trim()))

const routeDestinationName = computed(() => {
  const p = program.value
  if (!p?.origin_name) return ''
  return p.destination_name?.trim() || t('tp_programs_page.card_destination_default')
})

const tabs = [
  { key: 'overview', label: 'Tổng quan', comp: OverviewTab, icon: ChartBarIcon },
  { key: 'schedule', label: 'Lịch', comp: ScheduleTab, icon: CalendarIcon },
  { key: 'students', label: 'Học sinh', comp: StudentsTab, icon: UsersIcon },
  { key: 'attendance', label: 'Điểm danh', comp: AttendanceTab, icon: ClipboardDocumentCheckIcon },
  { key: 'driver', label: 'Tài xế', comp: DriverAssignmentTab, icon: IdentificationIcon },
]

const activeComponent = computed(() => tabs.find((t) => t.key === active.value)?.comp)

async function loadDays() {
  if (!program.value?.id) return
  daysLoading.value = true
  try {
    days.value = (await listProgramDays(program.value.id))?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    daysLoading.value = false
  }
}

async function load() {
  try {
    program.value = await getProgram(route.params.id)
    await loadDays()
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

async function lifecycle(action) {
  try {
    if (action === 'activate') program.value = await activateProgram(route.params.id)
    else if (action === 'pause') program.value = await pauseProgram(route.params.id)
    else if (action === 'cancel') {
      const ok = await confirmAction({ title: 'Hủy chương trình', message: 'Bạn chắc chắn muốn hủy chương trình này?', danger: true, confirmLabel: 'Hủy chương trình' })
      if (!ok) return
      program.value = await cancelProgram(route.params.id, 'Hủy bởi quản trị')
    }
    showAppSuccess('Đã cập nhật trạng thái.')
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

function goList() {
  router.push({ name: 'tpPrograms' })
}

function statusLabel(s) {
  return { draft: 'Nháp', active: 'Đang chạy', paused: 'Tạm dừng', completed: 'Hoàn thành', cancelled: 'Đã hủy' }[s] || s
}
function statusBadge(s) {
  const base = 'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold '
  return base + ({
    draft: 'border-slate-200 bg-slate-50 text-slate-600',
    active: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    paused: 'border-amber-200 bg-amber-50 text-amber-900',
    completed: 'border-sky-200 bg-sky-50 text-sky-800',
    cancelled: 'border-rose-200 bg-rose-50 text-rose-800',
  }[s] || 'border-slate-200 bg-slate-50 text-slate-600')
}

onMounted(load)
</script>
