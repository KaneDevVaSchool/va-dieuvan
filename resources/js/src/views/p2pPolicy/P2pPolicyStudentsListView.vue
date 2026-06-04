<template>
  <div class="space-y-4 md:space-y-5">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
          {{ t('p2p_policy_page.students_title') }}
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ t('p2p_policy_page.students_subtitle') }}</p>
      </div>
      <button
        type="button"
        class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700"
        @click="openAddModal"
      >
        <PlusIcon class="h-5 w-5" aria-hidden="true" />
        Thêm học sinh
      </button>
    </div>

    <!-- Filter Bar -->
    <div class="relative z-40">
      <AppFilterBar>
        <div ref="filterBarRef" class="relative flex flex-wrap items-center gap-x-1 gap-y-2 sm:gap-x-2">
          <!-- Funnel summary -->
          <details class="group relative">
            <summary class="flex cursor-pointer list-none items-center gap-1.5 rounded-xl border border-white/90 bg-white/95 px-2.5 py-2 text-slate-700 shadow-sm ring-1 ring-slate-200/50 transition hover:border-teal-200/70 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/95 dark:text-slate-200 dark:ring-slate-700/60 dark:hover:border-teal-800/40 dark:hover:bg-slate-800 [&::-webkit-details-marker]:hidden">
              <span class="relative inline-flex">
                <FunnelIcon class="h-5 w-5 text-slate-600 dark:text-slate-400" />
                <span v-if="activeFilterCount > 0" class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-teal-500 px-1 text-[10px] font-bold leading-none text-white">{{ activeFilterCount }}</span>
              </span>
              <ChevronDownIcon class="h-4 w-4 shrink-0 text-slate-400" />
            </summary>
            <div class="absolute left-0 top-[calc(100%+8px)] z-[100] min-w-[220px] overflow-hidden rounded-2xl border border-violet-200/50 bg-white shadow-xl shadow-violet-500/10 ring-1 ring-slate-900/5 dark:border-violet-800/40 dark:bg-slate-900">
              <p class="border-b border-violet-100/80 bg-gradient-to-r from-violet-50/60 to-transparent px-3 py-2 text-xs font-semibold uppercase tracking-wide text-violet-700 dark:border-violet-900/40 dark:from-violet-950/50 dark:text-violet-300">Bộ lọc đang áp dụng</p>
              <div class="p-3">
                <ul class="space-y-1.5 text-sm">
                  <li v-if="filterStatus"><span class="text-slate-500">Trạng thái:</span> <span class="font-medium">{{ labelStudentPolicyStatus(filterStatus) }}</span></li>
                  <li v-if="filterSlot"><span class="text-slate-500">Ca:</span> <span class="font-medium">{{ labelTimeSlot(filterSlot) }}</span></li>
                  <li v-if="filterSemester"><span class="text-slate-500">HK:</span> <span class="font-medium">HK{{ filterSemester }}</span></li>
                  <li v-if="!activeFilterCount" class="text-slate-400 text-xs">Chưa chọn điều kiện lọc.</li>
                </ul>
                <button type="button" class="mt-3 w-full rounded-xl border border-slate-200 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800" @click="resetFilters">Xóa tất cả</button>
              </div>
            </div>
          </details>

          <div class="hidden h-6 w-px bg-slate-200/90 sm:block dark:bg-slate-700" />

          <div class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-2 sm:gap-x-3">
            <!-- Status -->
            <AppFilterDropdown :summary-text="filterStatus ? labelStudentPolicyStatus(filterStatus) : 'Trạng thái'" panel-class="min-w-[180px] py-1">
              <ul class="space-y-0.5 px-1">
                <li v-for="opt in studentStatusOptions" :key="opt.value">
                  <button type="button" :class="['flex w-full rounded-lg px-3 py-2 text-left text-sm transition', filterStatus === opt.value ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800']" @click="filterStatus = opt.value; $event.currentTarget.closest('details').open = false">{{ opt.label }}</button>
                </li>
              </ul>
            </AppFilterDropdown>

            <!-- Slot -->
            <AppFilterDropdown :summary-text="filterSlot ? labelTimeSlot(filterSlot) : 'Tất cả ca'" panel-class="min-w-[160px] py-1">
              <ul class="space-y-0.5 px-1">
                <li v-for="opt in slotOptions" :key="opt.value">
                  <button type="button" :class="['flex w-full rounded-lg px-3 py-2 text-left text-sm transition', filterSlot === opt.value ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800']" @click="filterSlot = opt.value; $event.currentTarget.closest('details').open = false">{{ opt.label }}</button>
                </li>
              </ul>
            </AppFilterDropdown>

            <!-- Semester -->
            <AppFilterDropdown :summary-text="filterSemester ? 'HK ' + filterSemester : 'Học kỳ'" panel-class="min-w-[140px] py-1">
              <ul class="space-y-0.5 px-1">
                <li v-for="opt in semesterOptions" :key="opt.value">
                  <button type="button" :class="['flex w-full rounded-lg px-3 py-2 text-left text-sm transition', filterSemester === opt.value ? 'bg-teal-50 font-medium text-teal-900 dark:bg-teal-950/50 dark:text-teal-100' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800']" @click="filterSemester = opt.value; $event.currentTarget.closest('details').open = false">{{ opt.label }}</button>
                </li>
              </ul>
            </AppFilterDropdown>
          </div>

          <div class="ml-auto flex shrink-0 items-center gap-2 pl-2">
            <button type="button" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800" :disabled="loading" @click="load">
              <ArrowPathIcon class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
              <span class="hidden sm:inline">Làm mới</span>
            </button>
          </div>
        </div>
      </AppFilterBar>
    </div>

    <!-- Table -->
    <div class="rounded-xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div v-if="loading" class="flex items-center justify-center py-16 text-sm text-slate-500">
        <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> {{ t('p2p_policy_page.loading') }}
      </div>
      <div v-else-if="!items.length" class="flex flex-col items-center justify-center py-16 text-center">
        <UserGroupIcon class="mb-3 h-12 w-12 text-slate-300 dark:text-slate-600" />
        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ t('p2p_policy_page.empty') }}</p>
        <p class="mt-1 text-xs text-slate-400">Thêm học sinh policy hoặc kiểm tra bộ lọc.</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-slate-700">
              <th class="whitespace-nowrap py-3 pl-4 pr-3">Học sinh</th>
              <th class="whitespace-nowrap py-3 pr-3">Lớp / Cơ sở</th>
              <th class="whitespace-nowrap py-3 pr-3">Tuyến</th>
              <th class="whitespace-nowrap py-3 pr-3">Ca</th>
              <th class="whitespace-nowrap py-3 pr-3">Điểm đón → Trả</th>
              <th class="whitespace-nowrap py-3 pr-3">Hiệu lực</th>
              <th class="whitespace-nowrap py-3 pr-3">Trạng thái</th>
              <th class="whitespace-nowrap py-3 pr-4 text-right">Hành động</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-for="row in items" :key="row.id" class="transition hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
              <td class="whitespace-nowrap py-3 pl-4 pr-3 font-medium text-slate-900 dark:text-slate-100">
                {{ row.student_name ?? row.student_id }}
              </td>
              <td class="whitespace-nowrap py-3 pr-3 text-slate-600 dark:text-slate-400">{{ row.class_name ?? '—' }}</td>
              <td class="whitespace-nowrap py-3 pr-3 text-slate-700 dark:text-slate-300">{{ row.route_name ?? '—' }}</td>
              <td class="whitespace-nowrap py-3 pr-3">
                <span :class="['inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium', row.time_slot === 'morning' ? 'bg-amber-100 text-amber-900 dark:bg-amber-950/40 dark:text-amber-100' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-200']">
                  {{ labelTimeSlot(row.time_slot) }}
                </span>
              </td>
              <td class="whitespace-nowrap py-3 pr-3 text-slate-600 dark:text-slate-400">
                {{ row.pickup_point ?? '—' }} → {{ row.dropoff_point ?? '—' }}
              </td>
              <td class="whitespace-nowrap py-3 pr-3 tabular-nums text-xs text-slate-500 dark:text-slate-400">
                {{ row.effective_from ?? '?' }} → {{ row.effective_to ?? '?' }}
              </td>
              <td class="whitespace-nowrap py-3 pr-3">
                <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', studentPolicyStatusPillClass(row.status)]">
                  {{ labelStudentPolicyStatus(row.status) }}
                </span>
              </td>
              <td class="whitespace-nowrap py-3 pr-4 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" @click="openEdit(row)">Sửa</button>
                  <button v-if="row.status === 'active'" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-amber-700 transition hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-950/30" @click="confirmSuspend(row)">Ngưng</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <Modal
      :open="showFormModal"
      :title="editTarget ? 'Sửa chính sách học sinh' : 'Thêm học sinh policy'"
      description="Mỗi ca (sáng/chiều) là một bản ghi riêng — xem spec E1."
      wide
      @close="closeFormModal"
    >
      <form class="space-y-4" @submit.prevent="submitForm">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Học sinh <span class="text-rose-500">*</span></label>
            <input v-model="studentSearch" type="search" placeholder="Tìm học sinh…" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" @input="searchStudentsDebounced" />
            <ul v-if="studentResults.length && !form.student_id" class="mt-1 max-h-40 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-700">
              <li v-for="s in studentResults" :key="s.id">
                <button type="button" class="flex w-full px-3 py-2 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-800" @click="pickStudent(s)">{{ s.full_name }} <span class="text-slate-400">({{ s.grade ?? s.student_code }})</span></button>
              </li>
            </ul>
            <p v-if="form.student_id" class="mt-1 text-xs text-teal-700 dark:text-teal-300">Đã chọn ID {{ form.student_id }} — <button type="button" class="underline" @click="clearStudent">Đổi</button></p>
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Tuyến <span class="text-rose-500">*</span></label>
            <select v-model="form.route_id" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" required>
              <option value="">Chọn tuyến…</option>
              <option v-for="r in routeOptions" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Ca <span class="text-rose-500">*</span></label>
            <select v-model="form.time_slot" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" required>
              <option value="">Chọn ca…</option>
              <option value="morning">Sáng</option>
              <option value="afternoon">Chiều</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Học kỳ <span class="text-rose-500">*</span></label>
            <select v-model="form.semester" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" required>
              <option value="">Chọn HK…</option>
              <option value="1">HK 1</option>
              <option value="2">HK 2</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Năm học <span class="text-rose-500">*</span></label>
            <input v-model="form.school_year" type="text" placeholder="2025-2026" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" required />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Điểm đón</label>
            <input v-model="form.pickup_point_id" type="text" placeholder="ID điểm đón" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Điểm trả</label>
            <input v-model="form.dropoff_point_id" type="text" placeholder="ID điểm trả" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Hiệu lực từ <span class="text-rose-500">*</span></label>
            <input v-model="form.effective_from" type="date" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" required />
          </div>
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Hiệu lực đến <span class="text-rose-500">*</span></label>
            <input v-model="form.effective_to" type="date" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800" required />
          </div>
        </div>
        <div v-if="formError" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-300">{{ formError }}</div>
        <div class="flex justify-end gap-2 pt-1">
          <button type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800" @click="closeFormModal">Đóng</button>
          <button type="submit" :disabled="actionLoading" class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-700 disabled:opacity-50">
            <ArrowPathIcon v-if="actionLoading" class="h-4 w-4 animate-spin" />
            {{ editTarget ? 'Lưu thay đổi' : 'Thêm học sinh' }}
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ArrowPathIcon, ChevronDownIcon, FunnelIcon, PlusIcon, UserGroupIcon } from '@heroicons/vue/24/outline'
import AppFilterBar from '../../components/filters/AppFilterBar.vue'
import AppFilterDropdown from '../../components/filters/AppFilterDropdown.vue'
import Modal from '../../components/ui/Modal.vue'
import { useDetailsAutoCloseWithin } from '../../composables/useDetailsAutoClose.js'
import {
  labelStudentPolicyStatus,
  labelTimeSlot,
  studentPolicyStatusPillClass,
} from '../../constants/policyTripStatus.js'
import { createStudentPolicy, listPolicyRoutes, listStudentPolicies, searchPolicyStudents, updateStudentPolicy } from '../../api/p2p.js'

const { t } = useI18n()
const filterBarRef = ref(null)
useDetailsAutoCloseWithin(filterBarRef)

const loading = ref(false)
const items = ref([])
const filterStatus = ref('')
const filterSlot = ref('')
const filterSemester = ref('')

const slotOptions = [
  { value: '', label: 'Tất cả ca' },
  { value: 'morning', label: 'Sáng' },
  { value: 'afternoon', label: 'Chiều' },
]
const studentStatusOptions = [
  { value: '', label: 'Tất cả trạng thái' },
  { value: 'active', label: 'Đang hoạt động' },
  { value: 'inactive', label: 'Ngưng' },
  { value: 'suspended', label: 'Tạm ngưng' },
]
const semesterOptions = [
  { value: '', label: 'Tất cả HK' },
  { value: '1', label: 'HK 1' },
  { value: '2', label: 'HK 2' },
]

const activeFilterCount = computed(() =>
  [filterStatus.value, filterSlot.value, filterSemester.value].filter(Boolean).length,
)

function resetFilters() {
  filterStatus.value = ''
  filterSlot.value = ''
  filterSemester.value = ''
}

async function load() {
  loading.value = true
  try {
    const params = {}
    if (filterStatus.value) params.status = filterStatus.value
    if (filterSlot.value) params.time_slot = filterSlot.value
    if (filterSemester.value) params.semester = filterSemester.value
    const res = await listStudentPolicies(params)
    items.value = res?.items ?? res ?? []
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

const routeOptions = ref([])
const studentSearch = ref('')
const studentResults = ref([])
let studentDebounce = null

function searchStudentsDebounced() {
  clearTimeout(studentDebounce)
  form.student_id = ''
  if (!studentSearch.value.trim()) {
    studentResults.value = []
    return
  }
  studentDebounce = setTimeout(async () => {
    try {
      const res = await searchPolicyStudents(studentSearch.value.trim())
      studentResults.value = res?.items ?? res ?? []
    } catch {
      studentResults.value = []
    }
  }, 300)
}

function pickStudent(s) {
  form.student_id = String(s.id)
  studentSearch.value = s.full_name
  studentResults.value = []
}

function clearStudent() {
  form.student_id = ''
  studentSearch.value = ''
}

onMounted(async () => {
  await Promise.all([
    load(),
    listPolicyRoutes().then((r) => { routeOptions.value = r?.items ?? r ?? [] }).catch(() => {}),
  ])
})
watch([filterStatus, filterSlot, filterSemester], load)

// Form modal
const showFormModal = ref(false)
const editTarget = ref(null)
const actionLoading = ref(false)
const formError = ref('')
const form = reactive({
  student_id: '',
  route_id: '',
  time_slot: '',
  semester: '',
  school_year: '',
  pickup_point_id: '',
  dropoff_point_id: '',
  effective_from: '',
  effective_to: '',
})

function openAddModal() {
  editTarget.value = null
  Object.keys(form).forEach((k) => (form[k] = ''))
  studentSearch.value = ''
  studentResults.value = []
  formError.value = ''
  showFormModal.value = true
}

function openEdit(row) {
  editTarget.value = row
  studentSearch.value = row.student_name ?? ''
  studentResults.value = []
  Object.assign(form, {
    student_id: row.student_id ?? '',
    route_id: row.route_id ?? '',
    time_slot: row.time_slot ?? '',
    semester: String(row.semester ?? ''),
    school_year: row.school_year ?? '',
    pickup_point_id: row.pickup_point_id ?? '',
    dropoff_point_id: row.dropoff_point_id ?? '',
    effective_from: row.effective_from ?? '',
    effective_to: row.effective_to ?? '',
  })
  formError.value = ''
  showFormModal.value = true
}

function closeFormModal() {
  showFormModal.value = false
  editTarget.value = null
}

async function submitForm() {
  formError.value = ''
  actionLoading.value = true
  try {
    const payload = {
      ...form,
      student_id: form.student_id ? Number(form.student_id) : form.student_id,
      route_id: form.route_id ? Number(form.route_id) : form.route_id,
      semester: form.semester ? Number(form.semester) : form.semester,
    }
    if (editTarget.value) {
      await updateStudentPolicy(editTarget.value.id, payload)
    } else {
      await createStudentPolicy(payload)
    }
    closeFormModal()
    await load()
  } catch (err) {
    formError.value = err?.response?.data?.message ?? 'Không thực hiện được. Thử lại sau.'
  } finally {
    actionLoading.value = false
  }
}

async function confirmSuspend(row) {
  if (!confirm(`Ngưng policy học sinh ${row.student_name ?? row.student_id}? Các chuyến tương lai sẽ được cập nhật tự động.`)) return
  actionLoading.value = true
  try {
    await updateStudentPolicy(row.id, { status: 'suspended' })
    await load()
  } catch {
    // handled by interceptor
  } finally {
    actionLoading.value = false
  }
}
</script>
