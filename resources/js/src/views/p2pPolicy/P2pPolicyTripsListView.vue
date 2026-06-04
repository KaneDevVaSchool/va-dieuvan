<template>
  <div class="space-y-4 md:space-y-5">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
          {{ t('p2p_policy_page.trips_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.trips_subtitle') }}</p>
      </div>
    </div>

    <!-- KPI Strip -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
      <div
        v-for="box in kpiBoxes"
        :key="box.key"
        class="rounded-2xl border border-slate-200/90 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-slate-900/50 sm:p-4"
      >
        <div class="flex min-w-0 items-center gap-3">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="box.iconWrap">
            <component :is="box.icon" class="h-5 w-5" :class="box.iconClass" aria-hidden="true" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="text-xl font-bold tabular-nums text-slate-900 dark:text-white sm:text-2xl">
              {{ loading ? '…' : box.value }}
            </div>
            <div class="mt-0.5 text-xs font-medium leading-snug text-slate-600 dark:text-slate-400">
              {{ box.label }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter Bar -->
    <div class="relative z-40">
      <AppFilterBar>
        <div ref="filterBarRef" class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
          <!-- Funnel -->
          <details class="group relative">
            <summary
              class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden"
            >
              <span class="relative inline-flex">
                <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" aria-hidden="true" />
                <span
                  v-if="activeFilterCount > 0"
                  class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-500 px-1 text-[10px] font-bold leading-none text-white"
                >{{ activeFilterCount }}</span>
              </span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            </summary>
            <div class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[220px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900 dark:shadow-black/30 dark:ring-slate-950/50">
              <p class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300">
                Bộ lọc đang áp dụng
              </p>
              <div class="p-3">
                <ul class="space-y-1.5 text-sm text-slate-700 dark:text-slate-300">
                  <li><span class="text-slate-500">Ngày:</span> <span class="font-medium">{{ filterDate }}</span></li>
                  <li v-if="filterSlot"><span class="text-slate-500">Ca:</span> <span class="font-medium">{{ labelTimeSlot(filterSlot) }}</span></li>
                  <li v-if="filterStatus"><span class="text-slate-500">Trạng thái:</span> <span class="font-medium">{{ labelPolicyTripStatus(filterStatus) }}</span></li>
                </ul>
                <button
                  type="button"
                  class="mt-3 w-full rounded-xl border border-slate-200 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                  @click="resetFilters"
                >Xóa tất cả bộ lọc</button>
              </div>
            </div>
          </details>

          <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" aria-hidden="true" />

          <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
            <!-- Date filter -->
            <AppFilterDropdown :summary-text="filterDate" panel-class="min-w-[200px] p-3">
              <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Ngày</label>
              <input
                v-model="filterDate"
                type="date"
                class="w-full rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm dark:border-slate-700 dark:bg-slate-800"
              />
            </AppFilterDropdown>

            <!-- Slot filter -->
            <AppFilterDropdown :summary-text="filterSlot ? labelTimeSlot(filterSlot) : 'Tất cả ca'" panel-class="min-w-[160px] py-1">
              <ul class="space-y-0.5 px-1">
                <li v-for="opt in slotOptions" :key="opt.value">
                  <button
                    type="button"
                    :class="[
                      'flex w-full rounded-lg px-3 py-2 text-left text-sm transition',
                      filterSlot === opt.value
                        ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                        : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800',
                    ]"
                    @click="filterSlot = opt.value; $event.currentTarget.closest('details').open = false"
                  >{{ opt.label }}</button>
                </li>
              </ul>
            </AppFilterDropdown>

            <!-- Status filter -->
            <AppFilterDropdown :summary-text="filterStatus ? labelPolicyTripStatus(filterStatus) : 'Trạng thái'" panel-class="min-w-[180px] py-1">
              <ul class="space-y-0.5 px-1">
                <li v-for="opt in statusOptions" :key="opt.value">
                  <button
                    type="button"
                    :class="[
                      'flex w-full rounded-lg px-3 py-2 text-left text-sm transition',
                      filterStatus === opt.value
                        ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100'
                        : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800',
                    ]"
                    @click="filterStatus = opt.value; $event.currentTarget.closest('details').open = false"
                  >{{ opt.label }}</button>
                </li>
              </ul>
            </AppFilterDropdown>
          </div>

          <!-- Reload CTA -->
          <div class="ml-auto flex shrink-0 items-center gap-2 pl-2">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
              :disabled="loading"
              @click="loadTrips"
            >
              <ArrowPathIcon class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
              <span class="hidden sm:inline">Làm mới</span>
            </button>
          </div>
        </div>
      </AppFilterBar>
    </div>

    <!-- Table -->
    <div class="rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div v-if="loading" class="flex items-center justify-center py-16 text-sm text-slate-500 dark:text-slate-400">
        <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" />
        {{ t('p2p_policy_page.loading') }}
      </div>
      <div v-else-if="!items.length" class="flex flex-col items-center justify-center py-16 text-center">
        <AcademicCapIcon class="mb-3 h-12 w-12 text-slate-300 dark:text-slate-600" />
        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ t('p2p_policy_page.empty') }}</p>
        <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ t('p2p_policy_page.developing') }}</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:text-slate-400">
              <th class="whitespace-nowrap py-3 pl-4 pr-3">Ca</th>
              <th class="whitespace-nowrap py-3 pr-3">Tuyến</th>
              <th class="whitespace-nowrap py-3 pr-3">Giờ dự kiến</th>
              <th class="whitespace-nowrap py-3 pr-3">Tài xế</th>
              <th class="whitespace-nowrap py-3 pr-3">Xe</th>
              <th class="whitespace-nowrap py-3 pr-3 text-right">HS</th>
              <th class="whitespace-nowrap py-3 pr-3 text-right">Đã lên</th>
              <th class="whitespace-nowrap py-3 pr-3 text-right">Vắng</th>
              <th class="whitespace-nowrap py-3 pr-3">Trạng thái</th>
              <th class="whitespace-nowrap py-3 pr-4 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr
              v-for="row in items"
              :key="row.id"
              class="transition hover:bg-slate-50/60 dark:hover:bg-slate-800/40"
            >
              <td class="whitespace-nowrap py-3 pl-4 pr-3">
                <span :class="['inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium', row.time_slot === 'morning' ? 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200']">
                  {{ labelTimeSlot(row.time_slot) }}
                </span>
              </td>
              <td class="whitespace-nowrap py-3 pr-3 font-medium text-slate-900 dark:text-slate-100">
                {{ row.route_name ?? '—' }}
              </td>
              <td class="whitespace-nowrap py-3 pr-3 tabular-nums text-slate-700 dark:text-slate-300">
                {{ row.planned_departure ?? '—' }}
              </td>
              <td class="whitespace-nowrap py-3 pr-3">
                <span v-if="row.driver_name" class="text-slate-800 dark:text-slate-200">{{ row.driver_name }}</span>
                <span v-else class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">Chưa gán</span>
              </td>
              <td class="whitespace-nowrap py-3 pr-3 text-slate-600 dark:text-slate-400">
                {{ row.vehicle_plate ?? '—' }}
              </td>
              <td class="whitespace-nowrap py-3 pr-3 text-right tabular-nums text-slate-700 dark:text-slate-300">
                {{ row.expected_count ?? 0 }}
              </td>
              <td class="whitespace-nowrap py-3 pr-3 text-right tabular-nums font-medium text-emerald-700 dark:text-emerald-400">
                {{ row.boarded_count ?? 0 }}
              </td>
              <td class="whitespace-nowrap py-3 pr-3 text-right tabular-nums font-medium text-rose-600 dark:text-rose-400">
                {{ row.absent_count ?? 0 }}
              </td>
              <td class="whitespace-nowrap py-3 pr-3">
                <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', policyTripStatusPillClass(row.status)]">
                  {{ labelPolicyTripStatus(row.status) }}
                </span>
              </td>
              <td class="whitespace-nowrap py-3 pr-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    type="button"
                    class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                    @click="openStudentDetail(row)"
                  >Học sinh</button>
                  <button
                    v-if="row.status === 'scheduled' || row.status === 'assigned'"
                    type="button"
                    class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-indigo-700 transition hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950/30"
                    @click="openAssignDriver(row)"
                  >Gán tài xế</button>
                  <button
                    v-if="row.status !== 'cancelled' && row.status !== 'completed'"
                    type="button"
                    class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-rose-700 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-950/30"
                    @click="openCancel(row)"
                  >Hủy</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- SSE status note -->
    <p class="text-center text-xs text-slate-400 dark:text-slate-500">
      Cột "Đã lên" / "Vắng" / "Trạng thái" sẽ cập nhật realtime qua SSE khi backend sẵn sàng
      (<code class="rounded bg-slate-100 px-1 py-0.5 dark:bg-slate-800">GET /api/policy-trips/live-updates</code>).
    </p>

    <!-- Modal: Danh sách học sinh trong chuyến (§8.2) -->
    <Modal
      :open="!!selectedTrip"
      :title="`Học sinh chuyến — ${selectedTrip ? labelTimeSlot(selectedTrip.time_slot) + ' · ' + (selectedTrip.route_name ?? '') : ''}`"
      description="Trạng thái điểm danh lên/xuống xe và vắng"
      wide
      @close="selectedTrip = null"
    >
      <div v-if="studentsLoading" class="flex items-center gap-2 text-sm text-slate-500 py-6">
        <ArrowPathIcon class="h-4 w-4 animate-spin" /> Đang tải…
      </div>
      <div v-else-if="!tripStudents.length" class="py-6 text-center text-sm text-slate-500">
        Chưa có học sinh trong chuyến này.
      </div>
      <table v-else class="min-w-full text-sm">
        <thead>
          <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700">
            <th class="py-2 pr-3">Họ tên</th>
            <th class="py-2 pr-3">Trạng thái</th>
            <th class="py-2 pr-3">Lên xe</th>
            <th class="py-2">Xuống xe</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
          <tr v-for="s in tripStudents" :key="s.id">
            <td class="py-2.5 pr-3 font-medium text-slate-900 dark:text-slate-100">{{ s.student_name ?? s.student_id }}</td>
            <td class="py-2.5 pr-3">
              <span v-if="s.absence_reason" :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', absenceReasonPillClass(s.absence_reason)]">
                {{ labelAbsenceReason(s.absence_reason) }}
              </span>
              <span v-else-if="s.alighted_at" class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100">Đã xuống xe</span>
              <span v-else-if="s.boarded_at" class="inline-flex items-center rounded-full bg-teal-100 px-2 py-0.5 text-xs font-medium text-teal-900 dark:bg-teal-950/40 dark:text-teal-100">Đã lên xe</span>
              <span v-else class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-400">Dự kiến</span>
            </td>
            <td class="py-2.5 pr-3 tabular-nums text-xs text-slate-500 dark:text-slate-400">{{ s.boarded_at ? fmtTime(s.boarded_at) : '—' }}</td>
            <td class="py-2.5 tabular-nums text-xs text-slate-500 dark:text-slate-400">{{ s.alighted_at ? fmtTime(s.alighted_at) : '—' }}</td>
          </tr>
        </tbody>
      </table>
    </Modal>

    <!-- Modal: Hủy chuyến -->
    <Modal
      :open="!!cancelTarget"
      title="Hủy chuyến"
      description="Nhập lý do hủy chuyến policy này."
      @close="cancelTarget = null; cancelReason = ''"
    >
      <div class="space-y-3">
        <div>
          <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Lý do hủy <span class="text-rose-500">*</span></label>
          <textarea
            v-model="cancelReason"
            rows="3"
            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800"
            placeholder="Nhập lý do hủy chuyến…"
          />
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800" @click="cancelTarget = null; cancelReason = ''">Đóng</button>
          <button
            type="button"
            :disabled="!cancelReason.trim() || actionLoading"
            class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:opacity-50"
            @click="confirmCancel"
          >
            <ArrowPathIcon v-if="actionLoading" class="h-4 w-4 animate-spin" />
            Xác nhận hủy
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  AcademicCapIcon,
  ArrowPathIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  ChevronDownIcon,
  ClockIcon,
  FunnelIcon,
  TruckIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import Modal from '../../components/ui/Modal.vue'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import {
  absenceReasonPillClass,
  labelAbsenceReason,
  labelPolicyTripStatus,
  labelTimeSlot,
  policyTripStatusPillClass,
} from '../../constants/policyTripStatus.js'
import { cancelPolicyTrip, listPolicyTrips, listPolicyTripStudents } from '../../api/p2p.js'

const { t } = useI18n()

const filterBarRef = ref(null)
useDetailsAutoCloseWithin(filterBarRef)

const loading = ref(false)
const items = ref([])
const filterDate = ref(new Date().toISOString().slice(0, 10))
const filterSlot = ref('')
const filterStatus = ref('')

const slotOptions = [
  { value: '', label: 'Tất cả ca' },
  { value: 'morning', label: 'Sáng' },
  { value: 'afternoon', label: 'Chiều' },
]

const statusOptions = [
  { value: '', label: 'Tất cả trạng thái' },
  { value: 'scheduled', label: 'Chờ gán tài xế' },
  { value: 'assigned', label: 'Đã phân công' },
  { value: 'in_progress', label: 'Đang chạy' },
  { value: 'completed', label: 'Hoàn thành' },
  { value: 'cancelled', label: 'Đã huỷ' },
]

const activeFilterCount = computed(() => (filterSlot.value ? 1 : 0) + (filterStatus.value ? 1 : 0))

function resetFilters() {
  filterSlot.value = ''
  filterStatus.value = ''
  filterDate.value = new Date().toISOString().slice(0, 10)
}

// KPI
const kpiBoxes = computed(() => [
  {
    key: 'scheduled',
    label: 'Chờ gán tài xế',
    value: items.value.filter((r) => r.status === 'scheduled').length,
    icon: ClockIcon,
    iconWrap: 'bg-slate-100 dark:bg-slate-800',
    iconClass: 'text-slate-500',
  },
  {
    key: 'assigned',
    label: 'Đã phân công',
    value: items.value.filter((r) => r.status === 'assigned').length,
    icon: TruckIcon,
    iconWrap: 'bg-indigo-100 dark:bg-indigo-950/40',
    iconClass: 'text-indigo-600 dark:text-indigo-300',
  },
  {
    key: 'in_progress',
    label: 'Đang chạy',
    value: items.value.filter((r) => r.status === 'in_progress').length,
    icon: CalendarDaysIcon,
    iconWrap: 'bg-teal-100 dark:bg-teal-950/40',
    iconClass: 'text-teal-600 dark:text-teal-300',
  },
  {
    key: 'completed',
    label: 'Hoàn thành',
    value: items.value.filter((r) => r.status === 'completed').length,
    icon: CheckCircleIcon,
    iconWrap: 'bg-emerald-100 dark:bg-emerald-950/40',
    iconClass: 'text-emerald-600 dark:text-emerald-300',
  },
])

async function loadTrips() {
  loading.value = true
  try {
    const params = { date: filterDate.value }
    if (filterSlot.value) params.time_slot = filterSlot.value
    if (filterStatus.value) params.status = filterStatus.value
    const res = await listPolicyTrips(params)
    items.value = res?.items ?? res ?? []
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(loadTrips)
watch([filterDate, filterSlot, filterStatus], loadTrips)

// Student detail modal (§8.2)
const selectedTrip = ref(null)
const studentsLoading = ref(false)
const tripStudents = ref([])

async function openStudentDetail(row) {
  selectedTrip.value = row
  studentsLoading.value = true
  tripStudents.value = []
  try {
    const res = await listPolicyTripStudents(row.id)
    tripStudents.value = res?.items ?? res ?? []
  } catch {
    tripStudents.value = []
  } finally {
    studentsLoading.value = false
  }
}

// Assign driver (placeholder — opens future modal)
function openAssignDriver(row) {
  // TODO: open assign-driver modal when backend ready
  alert(`Gán tài xế cho chuyến ${row.id} — chức năng sẽ bổ sung sau khi backend hoàn tất.`)
}

// Cancel trip modal
const cancelTarget = ref(null)
const cancelReason = ref('')
const actionLoading = ref(false)

function openCancel(row) {
  cancelTarget.value = row
  cancelReason.value = ''
}

async function confirmCancel() {
  if (!cancelTarget.value || !cancelReason.value.trim()) return
  actionLoading.value = true
  try {
    await cancelPolicyTrip(cancelTarget.value.id, cancelReason.value.trim())
    cancelTarget.value = null
    cancelReason.value = ''
    await loadTrips()
  } catch {
    // error handled by http interceptor
  } finally {
    actionLoading.value = false
  }
}

function fmtTime(ts) {
  if (!ts) return '—'
  try {
    return new Date(ts).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
  } catch {
    return ts
  }
}
</script>
