<template>
  <div class="space-y-5">
    <!-- ── Program-level drivers ───────────────────────────────────────────────── -->
    <div v-if="hasBothShifts" class="grid gap-4 lg:grid-cols-2">
      <div
        v-for="card in programShiftCards"
        :key="card.shift"
        class="rounded-2xl border bg-white p-4 shadow-sm"
        :class="card.shift === 'morning' ? 'border-amber-200/80' : 'border-violet-200/80'"
      >
        <div
          class="mb-3 text-xs font-semibold uppercase tracking-wide"
          :class="card.shift === 'morning' ? 'text-amber-700' : 'text-violet-700'"
        >
          {{ card.label }}{{ card.time ? ` · ${card.time}` : '' }}
        </div>
        <div class="space-y-3">
          <div>
            <div class="mb-1.5 text-[11px] font-medium text-slate-500">Tài xế chạy chuyến</div>
            <DriverCombobox
              :model-value="card.mainSel"
              :drivers="driverOptions"
              :exclude-id="card.backupSel"
              exclude-label="đang là sơ cua"
              :loading="loadingDrivers"
              :disabled="programBusy"
              accent="brand"
              placeholder="Chọn tài xế chạy chuyến"
              @update:model-value="(v) => onProgramShiftDriverChange(card.shift, 'default', v)"
            />
          </div>
          <div>
            <div class="mb-1.5 text-[11px] font-medium text-slate-500">Tài xế sơ cua</div>
            <DriverCombobox
              :model-value="card.backupSel"
              :drivers="driverOptions"
              :exclude-id="card.mainSel"
              exclude-label="đang chạy chuyến"
              :loading="loadingDrivers"
              :disabled="programBusy"
              accent="amber"
              placeholder="Chọn tài xế sơ cua"
              @update:model-value="(v) => onProgramShiftDriverChange(card.shift, 'backup', v)"
            />
          </div>
        </div>
      </div>
    </div>
    <div v-else class="grid gap-4 lg:grid-cols-2">
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

      <!-- Shift tabs (chỉ hiện khi chương trình có cả sáng lẫn chiều) -->
      <div v-if="hasBothShifts" class="flex items-center gap-0 border-b border-slate-100 px-4">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 border-b-2 px-4 py-3 text-sm font-semibold transition"
          :class="activeShift === 'morning'
            ? 'border-amber-400 text-amber-700'
            : 'border-transparent text-slate-500 hover:text-slate-800'"
          @click="activeShift = 'morning'"
        >
          <!-- Sun icon -->
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0" aria-hidden="true">
            <path d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2ZM10 15a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 15ZM10 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM15.657 5.404a.75.75 0 1 0-1.06-1.06l-1.061 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM6.464 14.596a.75.75 0 1 0-1.06-1.06l-1.06 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM18 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 18 10ZM5 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 5 10ZM14.596 15.657a.75.75 0 0 0 1.06-1.06l-1.06-1.061a.75.75 0 1 0-1.06 1.06l1.06 1.06ZM5.404 6.464a.75.75 0 0 0 1.06-1.06l-1.06-1.06a.75.75 0 1 0-1.06 1.06l1.06 1.06Z" />
          </svg>
          Sáng
          <span v-if="morningTime" class="ml-0.5 text-xs font-normal text-slate-400">({{ morningTime }})</span>
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-1.5 border-b-2 px-4 py-3 text-sm font-semibold transition"
          :class="activeShift === 'afternoon'
            ? 'border-violet-400 text-violet-700'
            : 'border-transparent text-slate-500 hover:text-slate-800'"
          @click="activeShift = 'afternoon'"
        >
          <!-- Moon icon -->
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0" aria-hidden="true">
            <path fill-rule="evenodd" d="M7.455 2.004a.75.75 0 0 1 .26.77 7 7 0 0 0 9.958 7.967.75.75 0 0 1 1.067.853A8.5 8.5 0 1 1 6.647 1.921a.75.75 0 0 1 .808.083Z" clip-rule="evenodd" />
          </svg>
          Chiều
          <span v-if="afternoonTime" class="ml-0.5 text-xs font-normal text-slate-400">({{ afternoonTime }})</span>
        </button>
      </div>

      <!-- Legend -->
      <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-slate-100 bg-slate-50/60 px-4 py-2 text-xs text-slate-500">
        <span class="inline-flex items-center gap-1.5">
          <!-- Single shift badge (no tabs) -->
          <template v-if="!hasBothShifts">
            <span v-if="hasMorning" class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3" aria-hidden="true"><path d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2ZM10 15a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 15ZM10 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM15.657 5.404a.75.75 0 1 0-1.06-1.06l-1.061 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM6.464 14.596a.75.75 0 1 0-1.06-1.06l-1.06 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM18 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 18 10ZM5 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 5 10ZM14.596 15.657a.75.75 0 0 0 1.06-1.06l-1.06-1.061a.75.75 0 1 0-1.06 1.06l1.06 1.06ZM5.404 6.464a.75.75 0 0 0 1.06-1.06l-1.06-1.06a.75.75 0 1 0-1.06 1.06l1.06 1.06Z" /></svg>
              Sáng{{ morningTime ? ` · ${morningTime}` : '' }}
            </span>
            <span v-else-if="hasAfternoon" class="inline-flex items-center gap-1 rounded-md bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3" aria-hidden="true"><path fill-rule="evenodd" d="M7.455 2.004a.75.75 0 0 1 .26.77 7 7 0 0 0 9.958 7.967.75.75 0 0 1 1.067.853A8.5 8.5 0 1 1 6.647 1.921a.75.75 0 0 1 .808.083Z" clip-rule="evenodd" /></svg>
              Chiều{{ afternoonTime ? ` · ${afternoonTime}` : '' }}
            </span>
          </template>
        </span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-300"></span> Mặc định</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-400"></span> Tùy chỉnh ngày</span>
        <span class="ml-auto inline-flex items-center gap-1.5"><LockClosedIcon class="h-3.5 w-3.5" /> Đã chạy — không đổi được</span>
      </div>

      <div v-if="loading" class="flex items-center justify-center py-16 text-sm text-slate-500">
        <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> Đang tải…
      </div>
      <div v-else-if="!rows.length" class="py-16 text-center text-sm text-slate-500">
        Không có ngày vận hành trong tháng này.
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full min-w-[56rem] text-left text-base">
          <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-4 py-3 font-medium">Ngày</th>
              <th class="px-4 py-3 font-medium">
                <span class="inline-flex items-center gap-1">
                  <IdentificationIcon class="h-3.5 w-3.5 text-va-800/70" />
                  Tài xế chạy chuyến
                </span>
              </th>
              <th class="px-4 py-3 font-medium">
                <span class="inline-flex items-center gap-1">
                  <LifebuoyIcon class="h-3.5 w-3.5 text-amber-500/80" />
                  Tài xế sơ cua
                </span>
              </th>
              <th class="px-4 py-3 text-right font-medium">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="d in rows" :key="d.id" class="align-middle hover:bg-slate-50/60">
              <!-- Ngày -->
              <td class="px-4 py-3">
                <div class="font-medium text-slate-900">{{ formatDate(d.scheduled_date) }}</div>
                <div class="text-xs text-slate-400">{{ weekday(d.scheduled_date) }}</div>
              </td>

              <!-- Tài xế chạy chuyến -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-2.5">
                  <DriverAvatar v-if="dayShiftView(d).effective_driver" :driver="dayShiftView(d).effective_driver" small />
                  <span v-else class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-300"><UserPlusIcon class="h-4 w-4" /></span>
                  <span class="min-w-0">
                    <span class="flex items-center gap-1.5">
                      <span class="block truncate text-sm font-medium text-slate-800">{{ dayShiftView(d).effective_driver?.full_name || 'Chưa có tài xế' }}</span>
                      <span
                        v-if="dayShiftView(d).driver_override_id"
                        class="shrink-0 rounded-full bg-va-800/10 px-1.5 py-0.5 text-[10px] font-semibold text-va-800"
                      >Ngày</span>
                    </span>
                    <span v-if="dayShiftView(d).effective_driver?.phone" class="block truncate text-xs text-slate-400">{{ dayShiftView(d).effective_driver.phone }}</span>
                  </span>
                </div>
              </td>

              <!-- Tài xế sơ cua -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-2.5">
                  <DriverAvatar v-if="dayShiftView(d).effective_backup_driver" :driver="dayShiftView(d).effective_backup_driver" small muted />
                  <span v-else class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-amber-50 text-amber-300"><LifebuoyIcon class="h-4 w-4" /></span>
                  <span class="min-w-0">
                    <span class="flex items-center gap-1.5">
                      <span class="block truncate text-sm font-medium" :class="dayShiftView(d).effective_backup_driver ? 'text-slate-700' : 'text-slate-400 italic'">
                        {{ dayShiftView(d).effective_backup_driver?.full_name || 'Chưa có sơ cua' }}
                      </span>
                      <span
                        v-if="dayShiftView(d).backup_override_id"
                        class="shrink-0 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700"
                      >Ngày</span>
                    </span>
                    <span v-if="dayShiftView(d).effective_backup_driver?.phone" class="block truncate text-xs text-slate-400">{{ dayShiftView(d).effective_backup_driver.phone }}</span>
                  </span>
                </div>
              </td>

              <!-- Thao tác -->
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1.5">
                  <template v-if="d.has_execution">
                    <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-medium text-slate-400">
                      <LockClosedIcon class="h-3.5 w-3.5" /> Đã chạy
                    </span>
                  </template>
                  <template v-else>
                    <!-- Đổi tài xế chạy chuyến -->
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-50"
                      :disabled="busyDayId === d.id"
                      title="Đổi tài xế chạy chuyến"
                      @click="openMainPicker(d)"
                    >
                      <IdentificationIcon class="h-3.5 w-3.5 text-va-800/70" /> Đổi TX
                    </button>
                    <!-- Reset tài xế chạy chuyến -->
                    <button
                      v-if="dayShiftView(d).driver_override_id"
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-medium text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 disabled:opacity-50"
                      :disabled="busyDayId === d.id"
                      title="Trả về tài xế mặc định"
                      @click="resetDay(d)"
                    >
                      <ArrowUturnLeftIcon class="h-3.5 w-3.5" />
                    </button>

                    <span class="h-4 w-px bg-slate-200" />

                    <!-- Đổi tài xế sơ cua -->
                    <button
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 disabled:opacity-50"
                      :disabled="busyDayId === d.id"
                      title="Đổi tài xế sơ cua cho chuyến này"
                      @click="openBackupPicker(d)"
                    >
                      <LifebuoyIcon class="h-3.5 w-3.5" /> Đổi SC
                    </button>
                    <!-- Reset sơ cua về mặc định -->
                    <button
                      v-if="dayShiftView(d).backup_override_id"
                      type="button"
                      class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-medium text-amber-400 transition hover:bg-amber-50 hover:text-amber-600 disabled:opacity-50"
                      :disabled="busyDayId === d.id"
                      title="Trả về tài xế sơ cua mặc định"
                      @click="resetBackupDay(d)"
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

    <!-- Modal đổi tài xế chạy chuyến -->
    <DriverPickerModal
      :open="mainPicker.open"
      title="Đổi tài xế chạy chuyến"
      :description="mainPickerDescription"
      :drivers="driverOptions"
      :selected-id="mainPickerSelectedId"
      :exclude-ids="mainPickerExcludeIds"
      :allow-clear="mainPickerAllowClear"
      clear-label="Trả về tài xế mặc định"
      @select="onMainPickerSelect"
      @close="mainPicker.open = false"
    />

    <!-- Modal đổi tài xế sơ cua -->
    <DriverPickerModal
      :open="backupPicker.open"
      title="Đổi tài xế sơ cua"
      :description="backupPickerDescription"
      :drivers="driverOptions"
      :selected-id="backupPickerSelectedId"
      :exclude-ids="backupPickerExcludeIds"
      :allow-clear="backupPickerAllowClear"
      clear-label="Trả về sơ cua mặc định"
      @select="onBackupPickerSelect"
      @close="backupPicker.open = false"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import {
  IdentificationIcon,
  TruckIcon,
  ArrowPathIcon,
  UserPlusIcon,
  LifebuoyIcon,
  LockClosedIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ArrowUturnLeftIcon,
} from '@heroicons/vue/24/outline'
import { h } from 'vue'
import DriverPickerModal from '../components/DriverPickerModal.vue'
import DriverCombobox from '../components/DriverCombobox.vue'
import {
  listProgramDays,
  assignDayDriver,
  clearDayDriver,
  assignDayBackupDriver,
  clearDayBackupDriver,
  updateProgram,
} from '../../../api/transportProgram'
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
const activeShift = ref('morning')

const mainPicker = ref({ open: false, day: null })
const backupPicker = ref({ open: false, day: null })

const WD = ['CN', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy']

const defaultDriver = computed(() => props.program.default_driver || null)
const backupDriver = computed(() => props.program.backup_driver || null)
const defaultDriverId = computed(() => props.program.default_driver_id ?? null)
const backupDriverId = computed(() => props.program.backup_driver_id ?? null)

const defaultSel = ref(defaultDriverId.value)
const backupSel = ref(backupDriverId.value)
watch(defaultDriverId, (v) => { defaultSel.value = v })
watch(backupDriverId, (v) => { backupSel.value = v })

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

// ── Shift helpers ────────────────────────────────────────────────────────────
const morningTime = computed(() => {
  const s = props.program.settings
  if (s?.morning?.enabled && s.morning?.departure) return s.morning.departure.slice(0, 5)
  return props.program.departure_time ? String(props.program.departure_time).slice(0, 5) : null
})

const afternoonTime = computed(() => {
  const s = props.program.settings
  if (s?.afternoon?.enabled && s.afternoon?.departure) return s.afternoon.departure.slice(0, 5)
  return props.program.return_time ? String(props.program.return_time).slice(0, 5) : null
})

const hasMorning = computed(() => {
  const s = props.program.settings
  if (s?.morning?.enabled) return true
  if (!s?.morning?.enabled && !s?.afternoon?.enabled) return !!props.program.departure_time
  return false
})

const hasAfternoon = computed(() => {
  const s = props.program.settings
  if (s?.afternoon?.enabled) return true
  if (!s?.morning?.enabled && !s?.afternoon?.enabled) return !!props.program.return_time
  return false
})

const hasBothShifts = computed(() => hasMorning.value && hasAfternoon.value)

const morningDefaultSel = ref(null)
const morningBackupSel = ref(null)
const afternoonDefaultSel = ref(null)
const afternoonBackupSel = ref(null)

function syncProgramShiftSelections() {
  const s = props.program.settings || {}
  const m = s.morning || {}
  const a = s.afternoon || {}
  morningDefaultSel.value = m.default_driver_id ?? props.program.default_driver_id ?? null
  morningBackupSel.value = m.backup_driver_id ?? props.program.backup_driver_id ?? null
  afternoonDefaultSel.value = a.default_driver_id ?? props.program.default_driver_id ?? null
  afternoonBackupSel.value = a.backup_driver_id ?? props.program.backup_driver_id ?? null
}

watch(() => props.program, syncProgramShiftSelections, { deep: true, immediate: true })

const programShiftCards = computed(() => [
  {
    shift: 'morning',
    label: 'Ca sáng',
    time: morningTime.value,
    mainSel: morningDefaultSel.value,
    backupSel: morningBackupSel.value,
  },
  {
    shift: 'afternoon',
    label: 'Ca chiều',
    time: afternoonTime.value,
    mainSel: afternoonDefaultSel.value,
    backupSel: afternoonBackupSel.value,
  },
])

function dayShiftView(day) {
  if (!day) {
    return {
      effective_driver: null,
      effective_backup_driver: null,
      driver_override_id: null,
      backup_override_id: null,
    }
  }
  if (hasBothShifts.value && day.shift_assignments) {
    const block = day.shift_assignments[activeShift.value] || {}
    return {
      effective_driver: block.effective_driver ?? null,
      effective_backup_driver: block.effective_backup_driver ?? null,
      driver_override_id: block.driver_id ?? null,
      backup_override_id: block.backup_driver_id ?? null,
    }
  }
  return {
    effective_driver: day.effective_driver ?? null,
    effective_backup_driver: day.effective_backup_driver ?? null,
    driver_override_id: day.driver_id ?? null,
    backup_override_id: day.backup_driver_id ?? null,
  }
}

function shiftQueryParam() {
  return hasBothShifts.value ? { shift: activeShift.value } : {}
}

function shiftLabelForPicker() {
  if (!hasBothShifts.value) return ''
  return activeShift.value === 'afternoon' ? ' · Ca chiều' : ' · Ca sáng'
}

// ── Rows ─────────────────────────────────────────────────────────────────────
const rows = computed(() => days.value.filter((d) => d.day_type !== 'cancelled'))

// ── Main picker ──────────────────────────────────────────────────────────────
const mainPickerDescription = computed(() => {
  const day = mainPicker.value.day
  return day ? `${formatDate(day.scheduled_date)} · ${weekday(day.scheduled_date)}${shiftLabelForPicker()}` : ''
})
const mainPickerSelectedId = computed(() => dayShiftView(mainPicker.value.day).effective_driver?.id ?? null)
const mainPickerAllowClear = computed(() => !!dayShiftView(mainPicker.value.day).driver_override_id)
const mainPickerExcludeIds = computed(() => {
  const view = dayShiftView(mainPicker.value.day)
  const ids = []
  if (view.effective_backup_driver?.id) ids.push(view.effective_backup_driver.id)
  return ids
})

function openMainPicker(day) {
  mainPicker.value = { open: true, day }
}

async function onMainPickerSelect(driverId) {
  await saveDayDriver(mainPicker.value.day, driverId)
  mainPicker.value.open = false
}

// ── Backup picker ────────────────────────────────────────────────────────────
const backupPickerDescription = computed(() => {
  const day = backupPicker.value.day
  return day ? `${formatDate(day.scheduled_date)} · ${weekday(day.scheduled_date)}${shiftLabelForPicker()}` : ''
})
const backupPickerSelectedId = computed(() => dayShiftView(backupPicker.value.day).effective_backup_driver?.id ?? null)
const backupPickerAllowClear = computed(() => !!dayShiftView(backupPicker.value.day).backup_override_id)
const backupPickerExcludeIds = computed(() => {
  const view = dayShiftView(backupPicker.value.day)
  const ids = []
  if (view.effective_driver?.id) ids.push(view.effective_driver.id)
  return ids
})

function openBackupPicker(day) {
  backupPicker.value = { open: true, day }
}

async function onBackupPickerSelect(driverId) {
  await saveBackupDayDriver(backupPicker.value.day, driverId)
  backupPicker.value.open = false
}

// ── Program: tài xế chạy chuyến / sơ cua (chọn inline) ──────────────────────────
function onProgramDriverChange(mode, val) {
  const id = val != null && val !== '' ? Number(val) : null
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
    defaultSel.value = defaultDriverId.value
    backupSel.value = backupDriverId.value
  } finally {
    programBusy.value = false
  }
}

async function onProgramShiftDriverChange(shift, mode, val) {
  const id = val != null && val !== '' ? Number(val) : null
  if (shift === 'morning') {
    if (mode === 'default') morningDefaultSel.value = id
    else morningBackupSel.value = id
  } else {
    if (mode === 'default') afternoonDefaultSel.value = id
    else afternoonBackupSel.value = id
  }
  await saveProgramShiftDriver(shift, mode, id)
}

async function saveProgramShiftDriver(shift, mode, driverId) {
  const settings = JSON.parse(JSON.stringify(props.program.settings || {}))
  if (!settings[shift] || typeof settings[shift] !== 'object') {
    settings[shift] = { enabled: true }
  }
  const key = mode === 'default' ? 'default_driver_id' : 'backup_driver_id'
  settings[shift][key] = driverId
  programBusy.value = true
  try {
    await updateProgram(props.program.id, { settings })
    const label = shift === 'morning' ? 'sáng' : 'chiều'
    showAppSuccess(
      mode === 'default'
        ? `Đã cập nhật tài xế chạy chuyến ca ${label}.`
        : `Đã cập nhật tài xế sơ cua ca ${label}.`,
    )
    emit('refresh')
    await loadDays()
  } catch (err) {
    showAppErrorFromApi(err)
    syncProgramShiftSelections()
  } finally {
    programBusy.value = false
  }
}

// ── Per-day main driver ──────────────────────────────────────────────────────
async function saveDayDriver(day, driverId) {
  if (!day) return
  busyDayId.value = day.id
  try {
    const q = shiftQueryParam()
    const updated = driverId
      ? await assignDayDriver(day.id, { driver_id: driverId, ...q })
      : await clearDayDriver(day.id, q)
    patchRow(updated)
    showAppSuccess(driverId ? 'Đã đổi tài xế chạy chuyến.' : 'Đã trả về tài xế mặc định.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busyDayId.value = null
  }
}

function resetDay(day) {
  saveDayDriver(day, null)
}

// ── Per-day backup driver ────────────────────────────────────────────────────
async function saveBackupDayDriver(day, driverId) {
  if (!day) return
  busyDayId.value = day.id
  try {
    const q = shiftQueryParam()
    const updated = driverId
      ? await assignDayBackupDriver(day.id, { backup_driver_id: driverId, ...q })
      : await clearDayBackupDriver(day.id, q)
    patchRow(updated)
    showAppSuccess(driverId ? 'Đã đổi tài xế sơ cua cho chuyến.' : 'Đã trả về sơ cua mặc định.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busyDayId.value = null
  }
}

function resetBackupDay(day) {
  saveBackupDayDriver(day, null)
}

function patchRow(updated) {
  if (!updated?.id) return
  const i = days.value.findIndex((d) => d.id === updated.id)
  if (i >= 0) days.value.splice(i, 1, { ...days.value[i], ...updated })
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
  if (hasAfternoon.value && !hasMorning.value) activeShift.value = 'afternoon'
})
</script>
