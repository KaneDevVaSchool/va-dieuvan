<template>
  <div class="space-y-4">
    <div v-if="program" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <button class="text-xs text-slate-400 hover:text-slate-600" @click="goList">← Danh sách chương trình</button>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">{{ program.name }}</h1>
        <p class="text-xs text-slate-500">{{ program.code }} · {{ program.start_date }} → {{ program.end_date }}</p>
      </div>
      <div class="flex items-center gap-2">
        <span :class="statusClass(program.status)">{{ statusLabel(program.status) }}</span>
        <Button v-if="program.status === 'draft' || program.status === 'paused'" variant="secondary" @click="lifecycle('activate')">Kích hoạt</Button>
        <Button v-if="program.status === 'active'" variant="secondary" @click="lifecycle('pause')">Tạm dừng</Button>
        <Button v-if="program.status !== 'cancelled'" variant="danger" @click="lifecycle('cancel')">Hủy</Button>
      </div>
    </div>

    <div class="flex gap-1 overflow-x-auto border-b border-slate-200">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        :class="[
          'whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium',
          active === tab.key ? 'border-va-800 text-va-900' : 'border-transparent text-slate-500 hover:text-slate-700',
        ]"
        @click="active = tab.key"
      >
        {{ tab.label }}
      </button>
    </div>

    <component :is="activeComponent" v-if="program" :program="program" @refresh="load" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from '../../components/ui/Button.vue'
import { getProgram, activateProgram, pauseProgram, cancelProgram } from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'
import OverviewTab from './tabs/OverviewTab.vue'
import ScheduleTab from './tabs/ScheduleTab.vue'
import StudentsTab from './tabs/StudentsTab.vue'
import AttendanceTab from './tabs/AttendanceTab.vue'
import DriverAssignmentTab from './tabs/DriverAssignmentTab.vue'
import CostTab from './tabs/CostTab.vue'
import ReportsTab from './tabs/ReportsTab.vue'
import AuditTab from './tabs/AuditTab.vue'

const route = useRoute()
const router = useRouter()
const program = ref(null)
const active = ref('overview')

const tabs = [
  { key: 'overview', label: 'Tổng quan', comp: OverviewTab },
  { key: 'schedule', label: 'Lịch', comp: ScheduleTab },
  { key: 'students', label: 'Học sinh', comp: StudentsTab },
  { key: 'attendance', label: 'Điểm danh', comp: AttendanceTab },
  { key: 'driver', label: 'Tài xế', comp: DriverAssignmentTab },
  { key: 'cost', label: 'Chi phí', comp: CostTab },
  { key: 'reports', label: 'Báo cáo', comp: ReportsTab },
  { key: 'audit', label: 'Nhật ký', comp: AuditTab },
]

const activeComponent = computed(() => tabs.find((t) => t.key === active.value)?.comp)

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
function statusClass(s) {
  const base = 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium '
  return base + ({ draft: 'bg-slate-100 text-slate-600', active: 'bg-emerald-100 text-emerald-700', paused: 'bg-amber-100 text-amber-700', completed: 'bg-sky-100 text-sky-700', cancelled: 'bg-rose-100 text-rose-700' }[s] || 'bg-slate-100 text-slate-600')
}

onMounted(load)
</script>
