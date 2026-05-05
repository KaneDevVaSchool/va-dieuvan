<template>
  <div class="flex flex-col">
    <div
      v-if="!hideInternalVehicleSection || !hideInternalDriverSection"
      class="mb-1 flex items-center justify-between gap-2"
    >
      <span class="text-[11px] font-medium uppercase tracking-wider text-slate-600 dark:text-slate-400">{{
        t('trip_detail.coordination.assign_pair_title')
      }}</span>
      <span class="text-[11px] font-normal text-slate-500 dark:text-slate-400">{{
        t('trip_detail.coordination.resource_fit_count', { n: availableCount })
      }}</span>
    </div>

    <ResourceSection
      v-if="!hideInternalVehicleSection"
      v-model="selected.internalVehicles"
      :options="internalVehicleOptions"
      :is-loading="isLoading"
      :title="t('trip_detail.coordination.resource_section_internal_title')"
      :subtitle="t('trip_detail.coordination.resource_section_internal_sub')"
      :placeholder="t('trip_detail.coordination.resource_section_internal_ph')"
      :icon="TruckIcon"
    />

    <div
      v-if="!hideInternalDriverSection"
      id="dispatch-internal-driver-section"
      class="rounded-[12px]"
    >
      <button
        type="button"
        class="mb-2 w-full rounded-[12px] border-[0.5px] border-slate-200/90 bg-white px-2.5 py-1.5 text-left text-[11px] font-medium text-[#8B1A1A] hover:bg-rose-50/80 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900 dark:hover:bg-rose-950/30"
        :disabled="!tripDate"
        @click="showWorkloadPanel = true"
      >
        {{ t('trip_detail.coordination.workload_open_panel') }}
      </button>
      <ResourceSection
        v-model="selected.internalDrivers"
        :options="internalDriverOptions"
        :is-loading="isLoading"
        :multiple="false"
        :title="t('trip_detail.coordination.resource_section_driver_title')"
        :subtitle="t('trip_detail.coordination.resource_section_driver_sub')"
        :placeholder="t('trip_detail.coordination.resource_section_driver_ph')"
        :icon="UserIcon"
        :option-label-class-fn="driverOptionLabelClass"
      >
        <template #option-extra="{ option }">
          <DriverWorkloadBadge
            v-if="driverWorkloadEntry(option.id)"
            :workload="driverWorkloadEntry(option.id)"
            :color-fn="loadColor"
          />
        </template>
      </ResourceSection>
    </div>

    <div
      v-if="!hideTaxiSection || !hideVendorSection"
      class="mt-2 border-t border-[0.5px] border-slate-200/80 px-1.5 pb-2 pt-3.5 dark:border-slate-700/60"
    >
      <div class="mb-3 flex items-center justify-between gap-2">
        <span class="text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">{{
          t('trip_detail.coordination.supplement_section_title')
        }}</span>
        <span
          class="shrink-0 rounded-full border-[0.5px] border-slate-300/80 bg-slate-100/90 px-2 py-0.5 text-[11px] font-medium tabular-nums text-slate-600 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300"
        >
          {{ t('trip_detail.coordination.supplement_options_count', { n: supplementOptionCount }) }}
        </span>
      </div>

      <ResourceSection
        v-if="!hideTaxiSection"
        v-model="selected.taxis"
        v-model:supplement-seats="taxiSeatSupplementStr"
        :options="taxiOptions"
        :is-loading="isLoading"
        allow-custom-entry
        show-supplement-seats
        indent-search
        :title="t('trip_detail.coordination.resource_section_taxi_title')"
        :subtitle="t('trip_detail.coordination.resource_section_taxi_sub')"
        :placeholder="t('trip_detail.coordination.resource_section_taxi_ph')"
        :icon="BuildingStorefrontIcon"
      />

      <ResourceSection
        v-if="!hideVendorSection"
        v-model="selected.vendors"
        v-model:supplement-seats="nccSeatSupplementStr"
        :options="vendorOptions"
        :is-loading="isLoading"
        show-supplement-seats
        indent-search
        :title="t('trip_detail.coordination.resource_section_vendor_title')"
        :subtitle="t('trip_detail.coordination.resource_section_vendor_sub')"
        :placeholder="t('trip_detail.coordination.resource_section_vendor_ph')"
        :icon="BuildingOffice2Icon"
      />

      <div v-if="canQuickCreateVendor && !hideVendorSection" class="mt-2">
        <button
          type="button"
          class="w-full rounded-[12px] border-[0.5px] border-dashed border-[#8B1A1A]/70 px-3 py-2 text-[12px] font-medium text-[#8B1A1A] hover:bg-rose-50/80 dark:hover:bg-rose-950/40"
          @click="$emit('create-vendor')"
        >
          {{ t('trip_detail.coordination.provider_quick_add') }}
        </button>
      </div>

      <div
        v-if="selected.taxis.length > 0 || selected.vendors.length > 0"
        class="mt-2"
      >
        <button
          type="button"
          class="flex w-full items-center justify-center gap-1 rounded-[12px] border-[0.5px] border-slate-300/90 bg-white px-3 py-2 text-[12px] font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800/80"
          :aria-expanded="showExternalRefs"
          @click="showExternalRefs = !showExternalRefs"
        >
          {{ t('trip_detail.coordination.external_ref_expand_btn') }}
          <span class="tabular-nums text-slate-400" aria-hidden="true">{{ showExternalRefs ? '▾' : '▸' }}</span>
        </button>
        <div
          v-show="showExternalRefs"
          class="mt-2 space-y-2 rounded-[12px] border-[0.5px] border-slate-200/80 bg-slate-50/60 p-2.5 dark:border-slate-700 dark:bg-slate-900/40"
        >
          <div>
            <label class="mb-1 block text-[11px] font-medium text-slate-600 dark:text-slate-400">{{
              t('trip_detail.coordination.external_vehicle')
            }}</label>
            <input
              v-model="externalVehicleRef"
              type="text"
              class="w-full rounded-[12px] border-[0.5px] border-slate-200/90 bg-white px-2.5 py-1.5 text-[13px] font-normal text-slate-900 outline-none ring-0 focus:border-[#8B1A1A]/40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
              :placeholder="t('trip_detail.coordination.external_vehicle_ph')"
            />
          </div>
          <div>
            <label class="mb-1 block text-[11px] font-medium text-slate-600 dark:text-slate-400">{{
              t('trip_detail.coordination.external_driver')
            }}</label>
            <input
              v-model="externalDriverRef"
              type="text"
              class="w-full rounded-[12px] border-[0.5px] border-slate-200/90 bg-white px-2.5 py-1.5 text-[13px] font-normal text-slate-900 outline-none ring-0 focus:border-[#8B1A1A]/40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
              :placeholder="t('trip_detail.coordination.external_driver_ph')"
            />
          </div>
        </div>
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
        class="mt-2 rounded-[12px] border-[0.5px] border-rose-200/80 bg-rose-50 px-2.5 py-1.5 text-[12px] font-medium text-rose-800 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-200"
        role="alert"
      >
        {{ panelErrorMessage }}
      </div>
    </Transition>

    <Teleport to="body">
      <div
        v-if="showWorkloadPanel && !hideInternalDriverSection"
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
import { BuildingOffice2Icon, BuildingStorefrontIcon, TruckIcon, UserIcon } from '@heroicons/vue/24/outline'
import ResourceSection from './ResourceSection.vue'
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
})

const emit = defineEmits(['create-vendor', 'update:resources'])

const { t } = useI18n()

const tripIdRef = toRef(props, 'tripId')
const tripDateRef = toRef(props, 'tripDate')

const showWorkloadPanel = ref(false)
const showExternalRefs = ref(false)

const hydratingFromSnapshot = ref(false)
const autoExternalSeatsHint = ref('')

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

const supplementOptionCount = computed(
  () => taxiOptions.value.length + vendorOptions.value.length,
)

const showValidation = ref(false)
const externalVehicleRef = ref('')
const externalDriverRef = ref('')
const taxiSeatSupplementStr = ref('')
const nccSeatSupplementStr = ref('')

function parsePositiveIntStr(raw) {
  const n = Number(String(raw ?? '').trim())
  if (!Number.isFinite(n) || n <= 0) return 0
  return Math.floor(n)
}

function seatExtrasForPayload() {
  return {
    taxiSeatSupplement: selected.value.taxis.length > 0 ? parsePositiveIntStr(taxiSeatSupplementStr.value) : 0,
    nccSeatSupplement: selected.value.vendors.length > 0 ? parsePositiveIntStr(nccSeatSupplementStr.value) : 0,
  }
}

const panelErrorMessage = computed(() => {
  const p = buildDispatchPayload(externalVehicleRef.value, externalDriverRef.value, seatExtrasForPayload())
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
  const p = buildDispatchPayload(externalVehicleRef.value, externalDriverRef.value, seatExtrasForPayload())
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

function minSeatsNeeded() {
  const n = Number(props.neededSeats)
  return Number.isFinite(n) && n > 0 ? n : 1
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
  () => selected.value.internalDrivers.map((x) => String(x.id)).join(','),
  () => {
    if (hydratingFromSnapshot.value) return
    if (props.hideInternalDriverSection || props.hideInternalVehicleSection) return
    const d = selected.value.internalDrivers[0]
    if (!d || !Number.isFinite(Number(d.id))) return
    syncInternalVehicleToDriver()
  },
)

watch(
  () => selected.value.taxis.length,
  (n) => {
    if (!n) taxiSeatSupplementStr.value = ''
  },
)

watch(
  () => selected.value.vendors.length,
  (n) => {
    if (!n) nccSeatSupplementStr.value = ''
  },
)

watch(
  () => selected.value.taxis.length + selected.value.vendors.length,
  (sum) => {
    if (sum === 0) showExternalRefs.value = false
  },
)

watch(
  () => [selected.value.taxis.length, selected.value.vendors.length],
  ([taxiN, vendorN], prev) => {
    const prevT = prev?.[0] ?? 0
    const prevV = prev?.[1] ?? 0
    const now = taxiN + vendorN > 0
    const was = prevT + prevV > 0
    const hint = String(
      t('trip_detail.coordination.external_vehicle_seats_auto', { n: minSeatsNeeded() }) || '',
    ).trim()
    if (now && !was && !String(externalVehicleRef.value || '').trim() && hint) {
      externalVehicleRef.value = hint
      autoExternalSeatsHint.value = hint
      return
    }
    if (!now && was && autoExternalSeatsHint.value) {
      const cur = String(externalVehicleRef.value || '').trim()
      const auto = String(autoExternalSeatsHint.value || '').trim()
      if (cur === auto) externalVehicleRef.value = ''
      autoExternalSeatsHint.value = ''
    }
  },
)

watch(
  [selected, externalVehicleRef, externalDriverRef, taxiSeatSupplementStr, nccSeatSupplementStr],
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
      await fetchOptions()
      applyHydration({
        vehicleId: snap.vehicleId,
        driverId: snap.driverId,
        transportProviderId: snap.transportProviderId,
      })
      externalVehicleRef.value = snap.externalVehicleRef ?? ''
      externalDriverRef.value = snap.externalDriverRef ?? ''
      taxiSeatSupplementStr.value = ''
      nccSeatSupplementStr.value = ''
      showExternalRefs.value = false
      autoExternalSeatsHint.value = ''
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
  const p = buildDispatchPayload(externalVehicleRef.value, externalDriverRef.value, seatExtrasForPayload())
  return p.readyForSubmit === true
}

async function refreshOptions() {
  await fetchOptions()
}

function pickProvider(providerId, kind) {
  const id = Number(providerId)
  if (!Number.isFinite(id)) return
  if (kind === 'taxi') {
    const item = taxiOptions.value.find((x) => Number(x.id) === id)
    if (item && !selected.value.taxis.some((x) => Number(x.id) === id)) {
      selected.value.taxis = [...selected.value.taxis, item]
    }
  } else {
    const item = vendorOptions.value.find((x) => Number(x.id) === id)
    if (item && !selected.value.vendors.some((x) => Number(x.id) === id)) {
      selected.value.vendors = [...selected.value.vendors, item]
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
