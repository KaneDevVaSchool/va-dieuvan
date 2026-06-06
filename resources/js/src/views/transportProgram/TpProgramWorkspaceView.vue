<template>
  <div class="space-y-5 pb-10">
    <!-- Loading -->
    <div v-if="!program" class="flex items-center justify-center rounded-2xl border border-slate-200 bg-white py-20 text-base text-slate-500">
      <ArrowPathIcon class="mr-2 h-6 w-6 animate-spin" /> Đang tải chương trình…
    </div>

    <template v-else>
      <!-- Header banner -->
      <div class="overflow-hidden rounded-2xl border border-slate-200 border-t-[3px] border-t-[color:var(--va-brand)] bg-white shadow-sm">
        <div class="relative border-b border-slate-200 bg-white px-5 py-5 sm:px-6">
          <button
            class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-va-800 transition hover:text-va-900"
            @click="goList"
          >
            <ArrowLeftIcon class="h-4 w-4" /> Danh sách chương trình
          </button>
          <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ program.name }}</h1>
                <span :class="statusBadge(program.status)">
                  <span class="h-1.5 w-1.5 rounded-full bg-current opacity-80"></span>
                  {{ statusLabel(program.status) }}
                </span>
              </div>
              <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                <span class="font-mono">{{ program.code }}</span>
                <span v-if="program.start_date">· {{ program.start_date }} → {{ program.end_date }}</span>
                <span v-if="program.responsible_user_name">· Phụ trách: {{ program.responsible_user_name }}</span>
              </p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
              <button
                v-if="program.status === 'draft' || program.status === 'paused'"
                class="inline-flex items-center gap-1.5 rounded-xl border border-va-800 bg-va-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-va-900"
                @click="lifecycle('activate')"
              >
                <BoltIcon class="h-4 w-4" /> Kích hoạt
              </button>
              <button
                v-if="program.status === 'active'"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-400 hover:bg-slate-50"
                @click="lifecycle('pause')"
              >
                <PauseIcon class="h-4 w-4" /> Tạm dừng
              </button>
              <button
                v-if="program.status !== 'cancelled'"
                class="inline-flex items-center gap-1.5 rounded-xl border border-rose-300 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm transition hover:border-rose-400 hover:bg-rose-50"
                @click="lifecycle('cancel')"
              >
                <XMarkIcon class="h-4 w-4" /> Hủy
              </button>
            </div>
          </div>
        </div>

        <!-- Quick facts -->
        <div class="grid grid-cols-2 divide-x divide-slate-100 border-t border-slate-100 sm:grid-cols-4">
          <div class="px-5 py-3.5">
            <div class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-slate-400">
              <UserGroupIcon class="h-4 w-4" /> Học sinh
            </div>
            <div class="mt-0.5 text-xl font-bold text-slate-900">{{ program.enrolled_count ?? 0 }}</div>
          </div>
          <div class="px-5 py-3.5">
            <div class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-slate-400">
              <CalendarDaysIcon class="h-4 w-4" /> Ngày vận hành
            </div>
            <div class="mt-0.5 text-xl font-bold text-slate-900">{{ program.day_count ?? 0 }}</div>
          </div>
          <div class="px-5 py-3.5">
            <div class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-slate-400">
              <ClockIcon class="h-4 w-4" /> Khung giờ
            </div>
            <div class="mt-0.5 truncate text-xl font-bold text-slate-900">{{ timeRange }}</div>
          </div>
          <div class="px-5 py-3.5">
            <div class="flex items-center gap-1.5 text-xs font-medium uppercase tracking-wide text-slate-400">
              <MapPinIcon class="h-4 w-4" /> Tuyến
            </div>
            <div class="mt-0.5 truncate text-sm font-semibold text-slate-700">{{ program.origin_name || '—' }} → {{ program.destination_name || 'Trường' }}</div>
          </div>
        </div>
      </div>

      <!-- Tab bar -->
      <div class="flex gap-1 overflow-x-auto rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          :class="[
            'inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg px-4 py-2.5 text-sm font-semibold transition',
            active === tab.key ? 'bg-[color:var(--va-brand)] text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700',
          ]"
          @click="active = tab.key"
        >
          <component :is="tab.icon" class="h-4 w-4" />
          {{ tab.label }}
        </button>
      </div>

      <component :is="activeComponent" :program="program" @refresh="load" />
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeftIcon,
  ArrowPathIcon,
  BoltIcon,
  PauseIcon,
  XMarkIcon,
  UserGroupIcon,
  CalendarDaysIcon,
  ClockIcon,
  MapPinIcon,
  ChartBarIcon,
  CalendarIcon,
  UsersIcon,
  ClipboardDocumentCheckIcon,
  IdentificationIcon,
} from '@heroicons/vue/24/outline'
import { getProgram, activateProgram, pauseProgram, cancelProgram } from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import OverviewTab from './tabs/OverviewTab.vue'
import ScheduleTab from './tabs/ScheduleTab.vue'
import StudentsTab from './tabs/StudentsTab.vue'
import AttendanceTab from './tabs/AttendanceTab.vue'
import DriverAssignmentTab from './tabs/DriverAssignmentTab.vue'

const route = useRoute()
const router = useRouter()
const program = ref(null)
const active = ref('overview')

const tabs = [
  { key: 'overview', label: 'Tổng quan', comp: OverviewTab, icon: ChartBarIcon },
  { key: 'schedule', label: 'Lịch', comp: ScheduleTab, icon: CalendarIcon },
  { key: 'students', label: 'Học sinh', comp: StudentsTab, icon: UsersIcon },
  { key: 'attendance', label: 'Điểm danh', comp: AttendanceTab, icon: ClipboardDocumentCheckIcon },
  { key: 'driver', label: 'Tài xế', comp: DriverAssignmentTab, icon: IdentificationIcon },
]

const activeComponent = computed(() => tabs.find((t) => t.key === active.value)?.comp)

const timeRange = computed(() => {
  const fmt = (t) => (t ? String(t).slice(0, 5) : null)
  const s = program.value?.settings || {}
  const morning = s.morning || {}
  const afternoon = s.afternoon || {}
  const hasMorning = morning.enabled != null ? !!morning.enabled : !!program.value?.departure_time
  const hasAfternoon = afternoon.enabled != null ? !!afternoon.enabled : !!program.value?.return_time
  const parts = []
  if (hasMorning) {
    parts.push(`Sáng ${fmt(morning.departure || program.value?.departure_time) || '—'}`)
  }
  if (hasAfternoon) {
    parts.push(`Chiều ${fmt(afternoon.departure || program.value?.return_time) || '—'}`)
  }
  if (parts.length) return parts.join(' · ')
  return fmt(program.value?.departure_time) || '—'
})

async function load() {
  try {
    program.value = await getProgram(route.params.id)
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
