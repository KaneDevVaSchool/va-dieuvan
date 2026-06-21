<template>
  <div class="flex flex-col gap-3">
    <!-- Section 1 · Xe được phân công -->
    <InternalMultiPickSection
      v-if="!hideInternalVehicleSection"
      class="min-w-0"
      v-model="selected.internalVehicles"
      :options="internalVehicleOptions"
      :is-loading="isLoading"
      :disabled="disabled"
      :icon="TruckIcon"
      :title="t('trip_detail.coordination.section_vehicles_title')"
      :pick-button-label="t('trip_detail.coordination.add_vehicle_btn')"
      :search-placeholder="t('trip_detail.coordination.resource_section_internal_ph')"
      :empty-hint="t('trip_detail.coordination.multi_pick_empty_vehicles')"
      data-testid="dispatch-internal-vehicle-strip"
    >
      <template v-if="selected.internalVehicles.length" #footer>
        <div class="flex items-center justify-between">
          <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
            {{ t('trip_detail.coordination.total_capacity_label') }}
          </span>
          <span class="text-[12px] font-bold tabular-nums text-slate-800 dark:text-slate-100">
            {{ t('trip_detail.coordination.total_capacity_seats', { n: internalSeatCapacity }) }}
          </span>
        </div>
      </template>
    </InternalMultiPickSection>

    <!-- Section 2 · Tài xế được phân công -->
    <InternalMultiPickSection
      v-if="!hideInternalDriverSection"
      class="min-w-0"
      v-model="selected.internalDrivers"
      :options="internalDriverOptions"
      :is-loading="isLoading"
      :disabled="disabled"
      :icon="UserIcon"
      :title="t('trip_detail.coordination.section_drivers_title')"
      :pick-button-label="t('trip_detail.coordination.add_driver_btn')"
      :search-placeholder="t('trip_detail.coordination.resource_section_driver_ph')"
      :empty-hint="t('trip_detail.coordination.multi_pick_empty_drivers')"
      :option-label-class-fn="driverOptionLabelClass"
      data-testid="dispatch-internal-driver-strip"
    >
      <template #toolbar>
        <button
          type="button"
          class="w-full rounded-xl bg-white px-2.5 py-1.5 text-left text-[11px] font-semibold text-[#8B1A1A] shadow-sm hover:bg-slate-50 disabled:opacity-45 dark:bg-slate-800/70 dark:text-[#e57373]"
          :disabled="!tripDate || disabled"
          data-testid="dispatch-open-workload-panel"
          @click="showWorkloadPanel = true"
        >
          {{ t('trip_detail.coordination.workload_open_panel') }}
        </button>
      </template>
      <template #option-extra="{ option }">
        <DriverWorkloadBadge
          v-if="driverWorkloadEntry(option.id)"
          :workload="driverWorkloadEntry(option.id)"
          :color-fn="loadColor"
        />
      </template>
    </InternalMultiPickSection>

    <!-- Section 3 · Nguồn lực bổ sung -->
    <div
      v-if="supplementBlockVisible"
      class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white dark:border-slate-800 dark:bg-slate-900/50"
    >
      <div class="border-b border-slate-200/70 bg-slate-50 px-3 py-2.5 dark:border-slate-800 dark:bg-slate-900/40">
        <div class="flex items-center gap-2">
          <div class="text-[12px] font-semibold text-slate-800 dark:text-slate-100">
            {{ t('trip_detail.coordination.supplement_block_title') }}
          </div>
          <span
            v-if="supplementTotalCount"
            class="shrink-0 rounded-full bg-slate-200/90 px-2 py-0.5 text-[10px] font-bold tabular-nums text-slate-700 dark:bg-slate-700 dark:text-slate-200"
          >
            {{ supplementTotalCount }}
          </span>
        </div>
        <p class="mt-0.5 text-[11px] leading-snug text-slate-500 dark:text-slate-400">
          {{ t('trip_detail.coordination.supplement_block_subtitle') }}
        </p>
        <div class="mt-2 flex flex-wrap gap-2" role="radiogroup" :aria-label="t('trip_detail.coordination.supplement_block_title')">
          <button
            v-if="!hideTaxiSection"
            type="button"
            role="radio"
            :aria-checked="activeSupplementKind === 'taxi'"
            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[12px] font-semibold transition disabled:opacity-45"
            :class="
              activeSupplementKind === 'taxi'
                ? 'bg-[#8B1A1A] text-white shadow-sm'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'
            "
            :disabled="disabled"
            data-testid="dispatch-supplement-radio-taxi"
            @click="setSupplementKind('taxi')"
          >
            <MapPinIcon class="size-3.5" aria-hidden="true" />
            {{ t('trip_detail.coordination.supplement_radio_taxi') }}
            <span
              v-if="selected.taxis.length"
              class="rounded-full bg-black/10 px-1.5 text-[10px] tabular-nums dark:bg-white/15"
            >{{ selected.taxis.length }}</span>
          </button>
          <button
            v-if="!hideVendorSection"
            type="button"
            role="radio"
            :aria-checked="activeSupplementKind === 'vendor'"
            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[12px] font-semibold transition disabled:opacity-45"
            :class="
              activeSupplementKind === 'vendor'
                ? 'bg-[#8B1A1A] text-white shadow-sm'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700'
            "
            :disabled="disabled"
            data-testid="dispatch-supplement-radio-vendor"
            @click="setSupplementKind('vendor')"
          >
            <BuildingOfficeIcon class="size-3.5" aria-hidden="true" />
            {{ t('trip_detail.coordination.supplement_radio_vendor') }}
            <span
              v-if="selected.vendors.length"
              class="rounded-full bg-black/10 px-1.5 text-[10px] tabular-nums dark:bg-white/15"
            >{{ selected.vendors.length }}</span>
          </button>
        </div>
      </div>

      <div v-if="activeSupplementKind" class="px-1 pb-1">
        <SupplementTransportSection
          v-if="activeSupplementKind === 'taxi' && !hideTaxiSection"
          v-model="selected.taxis"
          embedded
          kind="taxi"
          :options="taxiOptions"
          :is-loading="isLoading"
          :default-seat="4"
          :disabled="disabled"
          :title="t('trip_detail.coordination.resource_section_taxi_title')"
          :subtitle="t('trip_detail.coordination.resource_section_taxi_sub')"
          :name-placeholder="t('trip_detail.coordination.resource_section_taxi_ph')"
          :name-field-label="t('trip_detail.coordination.supplement_field_provider')"
          :icon="MapPinIcon"
        />
        <SupplementTransportSection
          v-if="activeSupplementKind === 'vendor' && !hideVendorSection"
          v-model="selected.vendors"
          embedded
          kind="vendor"
          :options="vendorOptions"
          :is-loading="isLoading"
          :default-seat="7"
          :can-quick-create="canQuickCreateVendor"
          :disabled="disabled"
          :title="t('trip_detail.coordination.resource_section_vendor_title')"
          :subtitle="t('trip_detail.coordination.resource_section_vendor_sub')"
          :name-placeholder="t('trip_detail.coordination.resource_section_vendor_ph')"
          :name-field-label="t('trip_detail.coordination.supplement_field_ncc_name')"
          :icon="BuildingOfficeIcon"
          @create-vendor="$emit('create-vendor')"
        />
      </div>
    </div>

    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="-translate-y-1 opacity-0"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="-translate-y-1 opacity-0"
    >
      <div
        v-if="showValidation && panelErrorMessage"
        class="mt-3 rounded-xl bg-rose-50 px-3 py-2 text-[12px] font-medium leading-snug text-rose-800 shadow-sm dark:bg-rose-950/45 dark:text-rose-200"
        role="alert"
      >
        {{ panelErrorMessage }}
      </div>
    </Transition>

    <Teleport to="body">
      <div
        v-if="showWorkloadPanel && !hideInternalDriverSection && !disabled"
        class="fixed inset-0 z-[310] flex justify-end bg-black/30"
        @click.self="showWorkloadPanel = false"
      >
        <div class="h-full w-[min(380px,100vw)] translate-x-0 bg-white transition-transform duration-200 ease-out dark:bg-slate-950">
          <DriverWorkloadPanel
            v-if="tripDate"
            :drivers="internalDriverOptions"
            :trip-date="tripDate"
            :selected-driver-id="selectedInternalDriverId"
            @close="showWorkloadPanel = false"
            @assign="onAssignFromWorkloadPanel"
          />
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, ref, toRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  BuildingOfficeIcon,
  MapPinIcon,
  TruckIcon,
  UserIcon,
} from '@heroicons/vue/24/outline'
import InternalMultiPickSection from './InternalMultiPickSection.vue'
import SupplementTransportSection from './SupplementTransportSection.vue'
import DriverWorkloadBadge from './DriverWorkloadBadge.vue'
import DriverWorkloadPanel from './DriverWorkloadPanel.vue'
import { useResourceSelector } from '../../composables/useResourceSelector'
import { useDriverWorkload } from '../../composables/useDriverWorkload'

const props = defineProps({
  tripId: { type: [String, Number], required: true },
  /** YYYY-MM-DD — ngày chuyến (khung tuần & “hôm nay” trong workload) */
  tripDate: { type: String, default: '' },
  availableCount: { type: Number, default: 0 },
  busyVehicleIds: { type: Array, default: () => [] },
  busyDriverIds: { type: Array, default: () => [] },
  tripSnapshot: { type: Object, default: null },
  canQuickCreateVendor: { type: Boolean, default: false },
  /** Ẩn picker xe nội bộ (hiển thị VehicleCard thay thế) */
  hideInternalVehicleSection: { type: Boolean, default: false },
  /** Ẩn picker tài xế nội bộ (hiển thị DriverCard thay thế) */
  hideInternalDriverSection: { type: Boolean, default: false },
  /** Số chỗ tối thiểu chuyến — chọn xe mặc định theo tài xế + gợi ý taxi/NCC */
  neededSeats: { type: Number, default: 1 },
  /** Ẩn section taxi (dùng khi tab Nội bộ đang active) */
  hideTaxiSection: { type: Boolean, default: false },
  /** Ẩn section NCC + nút tạo nhanh (dùng khi tab không phải NCC) */
  hideVendorSection: { type: Boolean, default: false },
  /** Khóa toàn bộ thao tác (vd. sau khi đã gán trên timeline) */
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['create-vendor', 'update:resources', 'update:capacity'])

const { t } = useI18n()

const tripIdRef = toRef(props, 'tripId')
const tripDateRef = toRef(props, 'tripDate')

const showWorkloadPanel = ref(false)

watch(
  () => props.disabled,
  (d) => {
    if (d) showWorkloadPanel.value = false
  },
)

const hydratingFromSnapshot = ref(false)

const { fetchWorkload, loadColor, workloadMap } = useDriverWorkload(tripDateRef)

const busyVehicleSet = computed(() => new Set((props.busyVehicleIds || []).map(Number)))
const busyDriverSet = computed(() => new Set((props.busyDriverIds || []).map(Number)))

const busyVehicleIdsRef = ref(new Set())
const busyDriverIdsRef = ref(new Set())

watch(
  busyVehicleSet,
  (s) => {
    busyVehicleIdsRef.value = s
  },
  { immediate: true },
)

watch(
  busyDriverSet,
  (s) => {
    busyDriverIdsRef.value = s
  },
  { immediate: true },
)

const {
  internalVehicleOptions,
  internalDriverOptions,
  taxiOptions,
  vendorOptions,
  selected,
  isLoading,
  buildDispatchPayload,
  fetchOptions,
  applyHydration,
} = useResourceSelector(tripIdRef, busyVehicleIdsRef, busyDriverIdsRef)

function minSeatsNeeded() {
  const n = Number(props.neededSeats)
  return Number.isFinite(n) && n > 0 ? n : 1
}

function sumListedSupplementSeats(list) {
  return list.reduce((acc, it) => {
    const n = Number(it.supplementSeats)
    if (!Number.isFinite(n) || n < 1) return acc
    return acc + Math.floor(n)
  }, 0)
}

const supplementTotalSeats = computed(
  () =>
    sumListedSupplementSeats(selected.value.taxis) +
    sumListedSupplementSeats(selected.value.vendors),
)

const supplementBlockVisible = computed(
  () =>
    (!props.hideTaxiSection || !props.hideVendorSection) &&
    !props.hideInternalDriverSection,
)

const resourcesPanelVisible = computed(
  () =>
    !props.hideInternalVehicleSection ||
    !props.hideInternalDriverSection ||
    supplementBlockVisible.value,
)

const supplementTotalCount = computed(
  () => selected.value.taxis.length + selected.value.vendors.length,
)

/** Loại nguồn lực ngoài đang mở form (radio): null = chưa chọn, form ẩn. */
const activeSupplementKind = ref(null)

function defaultSupplementKind() {
  if (!props.hideTaxiSection && selected.value.taxis.length) return 'taxi'
  if (!props.hideVendorSection && selected.value.vendors.length) return 'vendor'
  return null
}

function setSupplementKind(kind) {
  if (props.disabled) return
  activeSupplementKind.value = activeSupplementKind.value === kind ? null : kind
}

// Mở sẵn form đúng loại khi hydrate có sẵn taxi/NCC; ẩn loại đã bị ẩn theo tab.
watch(
  () => [
    selected.value.taxis.length,
    selected.value.vendors.length,
    props.hideTaxiSection,
    props.hideVendorSection,
  ],
  () => {
    if (activeSupplementKind.value === 'taxi' && props.hideTaxiSection) {
      activeSupplementKind.value = null
    }
    if (activeSupplementKind.value === 'vendor' && props.hideVendorSection) {
      activeSupplementKind.value = null
    }
    if (activeSupplementKind.value === null) {
      activeSupplementKind.value = defaultSupplementKind()
    }
  },
  { immediate: true },
)

const internalSeatCapacity = computed(() =>
  selected.value.internalVehicles.reduce((acc, v) => {
    const n = Number(v?.seatCount)
    return acc + (Number.isFinite(n) && n > 0 ? Math.floor(n) : 0)
  }, 0),
)

/** Tổng chỗ đã phân bổ = sức chứa xe nội bộ + ghế taxi/NCC bổ sung. */
const allocatedSeats = computed(
  () => internalSeatCapacity.value + supplementTotalSeats.value,
)

const showValidation = ref(false)

const panelErrorMessage = computed(() => {
  const p = buildDispatchPayload()
  const c = p.validationCode
  if (!c) return ''
  const map = {
    empty: 'trip_detail.coordination.resource_validation_empty',
    need_provider: 'trip_detail.coordination.validation_provider',
  }
  const key = map[c]
  return key ? t(key) : ''
})

const lastPayloadJson = ref('')

function emitCapacity() {
  emit('update:capacity', {
    allocatedSeats: allocatedSeats.value,
    hasVehicle:
      selected.value.internalVehicles.length > 0 || supplementTotalCount.value > 0,
    hasDriver: selected.value.internalDrivers.length > 0,
  })
}

function emitResources() {
  emitCapacity()
  const p = buildDispatchPayload()
  const j = JSON.stringify(p)
  if (j === lastPayloadJson.value) return
  lastPayloadJson.value = j
  emit('update:resources', p)
}

function driverOptionLabelClass(opt) {
  const w = driverWorkloadEntry(opt.id)
  return w?.load_level === 'high' ? 'text-rose-700/90 dark:text-rose-300' : ''
}

function driverWorkloadEntry(id) {
  const n = Number(id)
  if (!Number.isFinite(n)) return null
  return workloadMap.value[String(n)] ?? null
}

const selectedInternalDriverId = computed(() => {
  const first = selected.value.internalDrivers[0]
  if (!first) return null
  const n = Number(first.id)
  return Number.isFinite(n) ? n : null
})

function onAssignFromWorkloadPanel(driverId) {
  const id = Number(driverId)
  const driver = internalDriverOptions.value.find((d) => Number(d.id) === id)
  if (!driver) return
  if (!selected.value.internalDrivers.some((x) => Number(x.id) === id)) {
    selected.value.internalDrivers = [...selected.value.internalDrivers, driver]
  }
  syncInternalVehicleToDriver()
  showWorkloadPanel.value = false
  lastPayloadJson.value = ''
  emitResources()
  void nextTick(() => {
    document.getElementById('dispatch-internal-driver-section')?.scrollIntoView({
      behavior: 'smooth',
      block: 'nearest',
    })
  })
}

function findBestVehicleForDriver(driverId) {
  const need = minSeatsNeeded()
  const opts = internalVehicleOptions.value.filter(
    (v) => v.defaultDriverId === driverId && v.available !== false,
  )
  if (!opts.length) return null
  const fitting = opts.filter((v) => (v.seatCount ?? 0) >= need)
  const pool = fitting.length ? fitting : opts
  if (fitting.length) {
    return pool.slice().sort((a, b) => (a.seatCount ?? 0) - (b.seatCount ?? 0))[0]
  }
  return pool.slice().sort((a, b) => (b.seatCount ?? 0) - (a.seatCount ?? 0))[0]
}

function syncInternalVehicleToDriver() {
  if (props.hideInternalDriverSection || props.hideInternalVehicleSection) return
  const d = selected.value.internalDrivers[0]
  if (!d || !Number.isFinite(Number(d.id))) return
  const veh = findBestVehicleForDriver(Number(d.id))
  if (!veh) return
  const list = selected.value.internalVehicles
  if (list.some((x) => Number(x.id) === Number(veh.id))) return
  if (list.length === 0) {
    selected.value.internalVehicles = [veh]
    return
  }
  selected.value.internalVehicles = [...list, veh]
}

watch(
  () => selected,
  () => emitResources(),
  { deep: true },
)

watch(
  () => {
    const s = props.tripSnapshot
    if (!s?.tripId) return null
    return `${s.tripId}:${s.lockVersion ?? 0}:${s.scheduleKey ?? ''}`
  },
  async (key, prevKey) => {
    if (key == null) return
    if (key === prevKey) return
    const snap = props.tripSnapshot
    if (!snap?.tripId) return
    hydratingFromSnapshot.value = true
    try {
      await fetchOptions(true)
      applyHydration({
        vehicleId: snap.vehicleId,
        driverId: snap.driverId,
        transportProviderId: snap.transportProviderId,
        externalVehicleRef: snap.externalVehicleRef,
        externalDriverRef: snap.externalDriverRef,
        supplementTransports: snap.supplementTransports ?? null,
      })
      lastPayloadJson.value = ''
      emitResources()
    } finally {
      await nextTick()
      hydratingFromSnapshot.value = false
    }
  },
  { immediate: true },
)

watch(
  () => [props.tripId, props.tripDate, props.hideInternalDriverSection],
  () => {
    if (props.hideInternalDriverSection) return
    if (props.tripId == null || props.tripId === '') return
    if (!props.tripDate) return
    void fetchWorkload()
  },
  { immediate: true },
)

function validate() {
  showValidation.value = true
  const p = buildDispatchPayload()
  return p.readyForSubmit === true
}

async function refreshOptions() {
  await fetchOptions(true)
}

function pickProvider(providerId, kind) {
  const id = Number(providerId)
  if (!Number.isFinite(id)) return
  const seats = Math.max(1, minSeatsNeeded())
  if (kind === 'taxi') {
    const item = taxiOptions.value.find((x) => Number(x.id) === id)
    if (item && !selected.value.taxis.some((x) => Number(x.id) === id)) {
      selected.value.taxis = [...selected.value.taxis, { ...item, supplementSeats: Math.max(4, seats) }]
    }
  } else {
    const item = vendorOptions.value.find((x) => Number(x.id) === id)
    if (item && !selected.value.vendors.some((x) => Number(x.id) === id)) {
      selected.value.vendors = [...selected.value.vendors, { ...item, supplementSeats: Math.max(7, seats) }]
    }
  }
}

function clearInternalVehicle() {
  selected.value.internalVehicles = []
  lastPayloadJson.value = ''
  emitResources()
}

function clearInternalDriver() {
  selected.value.internalDrivers = []
  lastPayloadJson.value = ''
  emitResources()
}

defineExpose({ validate, refreshOptions, pickProvider, clearInternalVehicle, clearInternalDriver })
</script>
