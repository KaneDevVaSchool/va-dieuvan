<template>
  <div class="flex flex-col">
    <CollapsiblePanelSection
      v-if="resourcesPanelVisible"
      :title="t('trip_detail.coordination.section_resources_title')"
      :summary-collapsed="unifiedCollapsedSummary"
      :default-expanded="true"
      :persist-key="null"
    >
      <template #header-end>
        <div
          class="flex shrink-0 flex-wrap items-center justify-end gap-1.5 pb-1 pr-2 pt-1 sm:py-2"
        >
          <span
            v-if="showFitCountBadge"
            class="tabular-nums text-[11px] font-medium text-slate-500 dark:text-slate-400"
          >{{
            t('trip_detail.coordination.resource_fit_count', { n: availableCount })
          }}</span>
          <span
            v-if="supplementBlockVisible"
            class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-semibold tabular-nums shadow-sm"
            :class="
              capacityGapRemaining > 0
                ? 'bg-[#FAEEDA] text-[#854F0B] shadow-amber-900/10 dark:bg-amber-950/45 dark:text-[#F2C07D]'
                : 'bg-emerald-50 text-emerald-800 shadow-emerald-900/10 dark:bg-emerald-950/55 dark:text-emerald-100'
            "
          >
            <template v-if="capacityGapRemaining > 0">
              {{
                t('trip_detail.coordination.supplement_capacity_badge_short', {
                  n: capacityGapRemaining,
                })
              }}
            </template>
            <template v-else>
              {{ t('trip_detail.coordination.supplement_capacity_badge_ok') }}
            </template>
          </span>
        </div>
      </template>

      <div class="space-y-2.5 pt-0.5">
        <div
          v-if="!hideInternalVehicleSection"
          class="overflow-hidden rounded-2xl bg-slate-50/90 shadow-sm shadow-slate-900/5 dark:bg-slate-900/35 dark:shadow-black/25"
        >
          <ResourceSection
            v-model="selected.internalVehicles"
            :options="internalVehicleOptions"
            :is-loading="isLoading"
            :disabled="disabled"
            :title="t('trip_detail.coordination.resource_section_internal_title')"
            :subtitle="t('trip_detail.coordination.resource_section_internal_sub')"
            :placeholder="t('trip_detail.coordination.resource_section_internal_ph')"
            :icon="TruckIcon"
          />
        </div>

        <div
          v-if="!hideInternalDriverSection"
          id="dispatch-internal-driver-section"
          class="overflow-hidden rounded-2xl bg-slate-50/90 shadow-sm shadow-slate-900/5 dark:bg-slate-900/35 dark:shadow-black/25"
        >
          <ResourceSection
            v-model="selected.internalDrivers"
            :options="internalDriverOptions"
            :is-loading="isLoading"
            :disabled="disabled"
            :multiple="false"
            :title="t('trip_detail.coordination.resource_section_driver_title')"
            :subtitle="t('trip_detail.coordination.resource_section_driver_sub')"
            :placeholder="t('trip_detail.coordination.resource_section_driver_ph')"
            :icon="UserIcon"
            :option-label-class-fn="driverOptionLabelClass"
          >
            <template #body-before-search>
              <button
                type="button"
                class="w-full rounded-xl bg-white px-2.5 py-1.5 text-left text-[11px] font-semibold leading-snug text-[#8B1A1A] shadow-sm shadow-slate-900/5 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-45 dark:bg-slate-800/70 dark:text-[#e57373] dark:hover:bg-slate-800 dark:shadow-black/25"
                :disabled="!tripDate || disabled"
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
          </ResourceSection>
        </div>

        <template v-if="supplementBlockVisible">
          <div
            v-if="!hideTaxiSection"
            class="overflow-hidden rounded-2xl bg-slate-50/85 shadow-sm shadow-slate-900/5 dark:bg-slate-900/38 dark:shadow-black/25"
          >
            <SupplementTransportSection
              v-model="selected.taxis"
              kind="taxi"
              :options="taxiOptions"
              :is-loading="isLoading"
              :default-seat="4"
              indent-body
              :disabled="disabled"
              :title="t('trip_detail.coordination.resource_section_taxi_title')"
              :subtitle="t('trip_detail.coordination.resource_section_taxi_sub')"
              :name-placeholder="t('trip_detail.coordination.resource_section_taxi_ph')"
              :name-field-label="t('trip_detail.coordination.supplement_field_provider')"
              :icon="MapPinIcon"
            />
          </div>

          <div
            v-if="!hideVendorSection"
            class="overflow-hidden rounded-2xl bg-slate-50/85 shadow-sm shadow-slate-900/5 dark:bg-slate-900/38 dark:shadow-black/25"
          >
            <SupplementTransportSection
              v-model="selected.vendors"
              kind="vendor"
              :options="vendorOptions"
              :is-loading="isLoading"
              :default-seat="7"
              indent-body
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
        </template>
      </div>
    </CollapsiblePanelSection>

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
        class="fixed inset-0 z-[200] flex justify-end bg-black/30"
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
import CollapsiblePanelSection from './CollapsiblePanelSection.vue'
import ResourceSection from './ResourceSection.vue'
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

const emit = defineEmits(['create-vendor', 'update:resources'])

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

const showFitCountBadge = computed(
  () =>
    !props.hideInternalVehicleSection || !props.hideInternalDriverSection,
)

const unifiedCollapsedSummary = computed(() => {
  const parts = []
  if (!props.hideInternalVehicleSection || !props.hideInternalDriverSection) {
    const vn = selected.value.internalVehicles.length
    const dn = selected.value.internalDrivers.length
    if (!vn && !dn) {
      parts.push(t('trip_detail.coordination.pick_internal_hint'))
    } else {
      parts.push(t('trip_detail.coordination.assign_pair_counts', { vn, dn }))
    }
  }
  if (supplementBlockVisible.value) {
    parts.push(
      t('trip_detail.coordination.supplement_summary_compact', {
        taxi: selected.value.taxis.length,
        ncc: selected.value.vendors.length,
        total: supplementTotalSeats.value,
      }),
    )
  }
  return parts.filter(Boolean).join(' · ')
})

const internalSeatCapacity = computed(() => {
  const v = selected.value.internalVehicles[0]
  const n = Number(v?.seatCount)
  return Number.isFinite(n) && n > 0 ? n : 0
})

const capacityGapRemaining = computed(() => {
  const need = minSeatsNeeded()
  const covered = internalSeatCapacity.value + supplementTotalSeats.value
  return Math.max(0, need - covered)
})

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

function emitResources() {
  const p = buildDispatchPayload()
  const j = JSON.stringify(p)
  if (j === lastPayloadJson.value) return
  lastPayloadJson.value = j
  emit('update:resources', p)
}

function driverWorkloadEntry(id) {
  const n = Number(id)
  if (!Number.isFinite(n)) return null
  return workloadMap.value[String(n)] ?? null
}

function driverOptionLabelClass(opt) {
  const w = driverWorkloadEntry(opt.id)
  return w?.load_level === 'high' ? 'text-rose-700/90 dark:text-rose-300' : ''
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
  selected.value.internalDrivers = [driver]
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
  const cur = selected.value.internalVehicles[0]
  if (cur && Number(cur.id) === Number(veh.id)) return
  selected.value.internalVehicles = [veh]
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
    return `${s.tripId}:${s.lockVersion ?? 0}`
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
