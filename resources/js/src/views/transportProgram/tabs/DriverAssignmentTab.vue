<template>
  <div class="space-y-5">
    <!-- ── Program-level drivers: luôn hiển thị + chọn inline có tìm kiếm ──────── -->
    <div class="grid gap-4 lg:grid-cols-2">
      <!-- Tài xế chạy chuyến -->
      <div class="rounded-2xl border border-va-800/20 bg-white p-4 shadow-sm">
        <div class="mb-2.5 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-va-800">
          <IdentificationIcon class="h-4 w-4" /> Tài xế chạy chuyến
        </div>
        <DriverCombobox
          :model-value="defaultSel"
          :drivers="driverOptions"
          :exclude-id="backupDriverId"
          exclude-label="đang là sơ cua"
          :loading="loadingDrivers"
          :disabled="programBusy"
          accent="brand"
          placeholder="Chọn tài xế chạy chuyến"
          @update:model-value="(v) => onProgramDriverChange('default', v)"
        />
      </div>

      <!-- Tài xế sơ cua -->
      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-2.5 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-amber-600">
          <LifebuoyIcon class="h-4 w-4" /> Tài xế sơ cua
        </div>
        <DriverCombobox
          :model-value="backupSel"
          :drivers="driverOptions"
          :exclude-id="defaultDriverId"
          exclude-label="đang chạy chuyến"
          :loading="loadingDrivers"
          :disabled="programBusy"
          accent="amber"
          placeholder="Chọn tài xế sơ cua"
          @update:model-value="(v) => onProgramDriverChange('backup', v)"
        />
      </div>
    </div>

    <!-- Vehicle info bar -->
    <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
      <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-teal-50 text-teal-600"><TruckIcon class="h-5 w-5" /></span>
      <div class="min-w-0">
        <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Xe mặc định</div>
        <div class="truncate text-sm font-semibold text-slate-800">{{ vehicleLabel }}</div>
      </div>
    </div>

    <!-- ── Per-trip assignment ─────────────────────────────────────────────── -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h3 class="text-base font-semibold text-slate-900">Phân công theo chuyến</h3>
          <p class="text-sm text-slate-500">Đổi tài xế cho từng ngày — ưu tiên hơn tài xế mặc định.</p>
        </div>
        <div class="flex items-center gap-2">
          <button class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50" @click="shiftMonth(-1)">
            <ChevronLeftIcon class="h-4 w-4" />
          </button>
          <input
            v-model="month"
            type="month"
            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-va-800/20 focus:border-va-800/40 focus:ring"
          />
          <button class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50" @click="shiftMonth(1)">
            <ChevronRightIcon class="h-4 w-4" />
          </button>
        </div>
      </div>

      <!-- Legend -->
      <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-slate-100 bg-slate-50/60 px-4 py-2 text-xs text-slate-500">
        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-300"></span> Mặc định</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-400"></span> Sơ cua</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-va-800"></span> Tùy chỉnh</span>
        <span class="ml-auto inline-flex items-center gap-1.5"><LockClosedIcon class="h-3.5 w-3.5" /> Đã chạy — không đổi được</span>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-16 text-sm text-slate-500">
        <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> Đang tải…
      </div>
      <div v-else-if="!rows.length" class="py-16 text-center text-sm text-slate-500">
        Không có ngày vận hành trong tháng này.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full min-w-[44rem] text-left text-base">
          <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3 font-medium">Ngày</th>
              <th class="px-4 py-3 font-medium">Tài xế chạy chuyến</th>
              <th class="px-4 py-3 font-medium">Nguồn</th>
              <th class="px-4 py-3 text-right font-medium">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="d in rows" :key="d.id" class="align-middle hover:bg-slate-50/60">
              <td class="px-4 py-3">
                <div class="font-medium text-slate-900">{{ formatDate(d.scheduled_date) }}</div>
                <div class="text-xs text-slate-400">{{ weekday(d.scheduled_date) }}</div>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2.5">
                  <DriverAvatar v-if="d.effective_driver" :driver="d.effective_driver" small />
                  <span v-else class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-300"><UserPlusIcon class="h-4 w-4" /></span>
                  <span class="min-w-0">
                    <span class="block truncate text-sm font-medium text-slate-800">{{ d.effective_driver?.full_name || 'Chưa có tài xế' }}</span>
                    <span v-if="d.effective_driver?.phone" class="block truncate text-xs text-slate-400">{{ d.effective_driver.phone }}</span>
                  </span>
                </div>
              </td>
              <td class="px-4 py-3">
                <span :class="sourceBadgeClass(sourceOf(d))">{{ sourceLabel(sourceOf(d)) }}</span>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1.5">
                  <template v-if="d.has_execution">
                    <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-400">
                      <LockClosedIcon class="h-3.5 w-3.5" /> Đã chạy
                    </span>
                  </template>
                  <template v-else>
                    <button
                      v-if="backupDriver && String(d.effective_driver?.id) !== String(backupDriverId)"
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 disabled:opacity-50"
                      :disabled="busyDayId === d.id"
                      title="Dùng tài xế sơ cua cho chuyến này"
                      @click="useBackup(d)"
                    >
                      <LifebuoyIcon class="h-3.5 w-3.5" /> Sơ cua
                    </button>
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-50"
                      :disabled="busyDayId === d.id"
                      @click="openDayPicker(d)"
                    >
                      <ArrowsRightLeftIcon class="h-3.5 w-3.5" /> Đổi
                    </button>
                    <button
                      v-if="d.driver_id"
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-medium text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 disabled:opacity-50"
                      :disabled="busyDayId === d.id"
                      title="Trả về tài xế mặc định"
                      @click="resetDay(d)"
                    >
                      <ArrowUturnLeftIcon class="h-3.5 w-3.5" />
                    </button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <DriverPickerModal
      :open="picker.open"
      title="Đổi tài xế cho chuyến"
      :description="pickerDescription"
      :drivers="driverOptions"
      :selected-id="pickerSelectedId"
      :allow-clear="pickerAllowClear"
      clear-label="Trả về tài xế mặc định"
      @select="onPickerSelect"
      @close="picker.open = false"
    />
  </div>
</template>

<script setup>
import { computed, h, onMounted, ref, watch } from 'vue'
import {
  IdentificationIcon,
  TruckIcon,
  ArrowPathIcon,
  UserPlusIcon,
  LifebuoyIcon,
  LockClosedIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ArrowsRightLeftIcon,
  ArrowUturnLeftIcon,
} from '@heroicons/vue/24/outline'
import DriverPickerModal from '../components/DriverPickerModal.vue'
import DriverCombobox from '../components/DriverCombobox.vue'
import { listProgramDays, assignDayDriver, clearDayDriver, updateProgram } from '../../../api/transportProgram'
import { listDrivers } from '../../../api/operational'
import { showAppErrorFromApi, showAppSuccess } from '../../../composables/appMessage'

const props = defineProps({ program: { type: Object, required: true } })
const emit = defineEmits(['refresh'])

const loading = ref(false)
const loadingDrivers = ref(false)
const days = ref([])
const drivers = ref([])
const month = ref(new Date().toISOString().slice(0, 7))
const busyDayId = ref(null)
const programBusy = ref(false)

const picker = ref({ open: false, day: null })

const WD = ['CN', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy']

const defaultDriver = computed(() => props.program.default_driver || null)
const backupDriver = computed(() => props.program.backup_driver || null)
const defaultDriverId = computed(() => props.program.default_driver_id ?? null)
const backupDriverId = computed(() => props.program.backup_driver_id ?? null)

// Lựa chọn inline (đồng bộ từ server, tránh nhấp nháy khi đang lưu)
const defaultSel = ref(defaultDriverId.value)
const backupSel = ref(backupDriverId.value)
watch(defaultDriverId, (v) => { defaultSel.value = v })
watch(backupDriverId, (v) => { backupSel.value = v })

// Danh sách cho combobox: gộp thêm tài xế đang gán nếu không có trong danh sách tải về
// (vd. tài xế đã nghỉ việc) để vẫn hiển thị đúng người đang phụ trách.
const driverOptions = computed(() => {
  const list = [...drivers.value]
  const ids = new Set(list.map((d) => String(d.id)))
  for (const d of [defaultDriver.value, backupDriver.value]) {
    if (d && !ids.has(String(d.id))) {
      list.push(d)
      ids.add(String(d.id))
    }
  }
  return list
})

const vehicleLabel = computed(() => {
  const v = props.program.default_vehicle
  if (v) return [v.license_plate, v.type, v.seat_count ? `${v.seat_count} chỗ` : null].filter(Boolean).join(' · ')
  const s = props.program.settings?.vehicle
  if (s) return [s.plate_number, s.type, s.max_capacity ? `${s.max_capacity} chỗ` : null].filter(Boolean).join(' · ') || '— chưa gán —'
  return '— chưa gán —'
})

const rows = computed(() => days.value.filter((d) => d.day_type !== 'cancelled'))

// ── Picker config (chỉ dùng cho đổi tài xế theo từng chuyến) ─────────────────────
const pickerDescription = computed(() => {
  const day = picker.value.day
  return day ? `${formatDate(day.scheduled_date)} · ${weekday(day.scheduled_date)}` : ''
})
const pickerSelectedId = computed(() => picker.value.day?.effective_driver?.id ?? null)
const pickerAllowClear = computed(() => !!picker.value.day?.driver_id)

function openDayPicker(day) {
  picker.value = { open: true, day }
}

async function onPickerSelect(driverId) {
  await saveDayDriver(picker.value.day, driverId)
  picker.value.open = false
}

// ── Program: tài xế chạy chuyến / sơ cua (chọn inline) ──────────────────────────
function onProgramDriverChange(mode, val) {
  const id = val != null && val !== '' ? Number(val) : null
  // Cập nhật lạc quan để không nhấp nháy giá trị trong lúc lưu
  if (mode === 'default') defaultSel.value = id
  else backupSel.value = id
  saveProgramDriver(mode, id)
}

async function saveProgramDriver(mode, driverId) {
  const field = mode === 'default' ? 'default_driver_id' : 'backup_driver_id'
  programBusy.value = true
  try {
    await updateProgram(props.program.id, { [field]: driverId })
    showAppSuccess(mode === 'default' ? 'Đã cập nhật tài xế chạy chuyến.' : 'Đã cập nhật tài xế sơ cua.')
    emit('refresh')
    await loadDays()
  } catch (err) {
    showAppErrorFromApi(err)
    // Hoàn nguyên lựa chọn về trạng thái server khi lưu lỗi
    defaultSel.value = defaultDriverId.value
    backupSel.value = backupDriverId.value
  } finally {
    programBusy.value = false
  }
}

// ── Per-day overrides ───────────────────────────────────────────────────────────
async function saveDayDriver(day, driverId) {
  if (!day) return
  busyDayId.value = day.id
  try {
    const updated = driverId
      ? await assignDayDriver(day.id, { driver_id: driverId })
      : await clearDayDriver(day.id)
    patchRow(updated)
    showAppSuccess(driverId ? 'Đã đổi tài xế cho chuyến.' : 'Đã trả về tài xế mặc định.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busyDayId.value = null
  }
}

function useBackup(day) {
  if (!backupDriverId.value) return
  saveDayDriver(day, backupDriverId.value)
}
function resetDay(day) {
  saveDayDriver(day, null)
}

function patchRow(updated) {
  if (!updated?.id) return
  const i = days.value.findIndex((d) => d.id === updated.id)
  if (i >= 0) days.value.splice(i, 1, { ...days.value[i], ...updated })
}

// ── Source classification ───────────────────────────────────────────────────────
function sourceOf(d) {
  if (!d.driver_id) return 'default'
  if (backupDriverId.value && String(d.driver_id) === String(backupDriverId.value)) return 'backup'
  return 'custom'
}
function sourceLabel(s) {
  return { default: 'Mặc định', backup: 'Sơ cua', custom: 'Tùy chỉnh' }[s]
}
function sourceBadgeClass(s) {
  const base = 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold '
  return base + ({
    default: 'bg-slate-100 text-slate-500',
    backup: 'bg-amber-50 text-amber-700',
    custom: 'bg-va-800/10 text-va-800',
  }[s])
}

// ── Helpers ─────────────────────────────────────────────────────────────────────
function weekday(dateStr) {
  return WD[new Date(dateStr + 'T00:00:00').getDay()]
}
function formatDate(dateStr) {
  const d = new Date(dateStr + 'T00:00:00')
  return `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`
}
function shiftMonth(delta) {
  const [y, m] = month.value.split('-').map(Number)
  const dt = new Date(y, m - 1 + delta, 1)
  month.value = `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}`
}

const DriverAvatar = (p) => {
  const d = p.driver || {}
  const url = d.avatar_url || d.user?.avatar_url
  const size = p.small ? 'h-8 w-8' : 'h-11 w-11'
  if (url) return h('img', { src: url, alt: d.full_name, class: `${size} shrink-0 rounded-full object-cover` })
  const initials = (d.full_name || '?').trim().split(/\s+/).slice(-2).map((w) => w[0]).join('').toUpperCase()
  const tone = p.muted ? 'bg-amber-100 text-amber-700' : 'bg-va-800/10 text-va-800'
  return h('span', { class: `${size} grid shrink-0 place-items-center rounded-full text-xs font-semibold ${tone}` }, initials)
}
DriverAvatar.props = ['driver', 'small', 'muted']

// ── Data ────────────────────────────────────────────────────────────────────────
async function loadDays() {
  loading.value = true
  try {
    days.value = (await listProgramDays(props.program.id, { month: month.value }))?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

async function loadDrivers() {
  loadingDrivers.value = true
  try {
    drivers.value = (await listDrivers({ per_page: 200 }))?.items ?? []
  } catch {
    drivers.value = []
  } finally {
    loadingDrivers.value = false
  }
}

watch(month, loadDays)
onMounted(() => {
  loadDays()
  loadDrivers()
})
</script>
