<template>
  <div class="flex flex-col">
    <div class="mb-1 flex items-center justify-between gap-2">
      <span class="text-[13px] font-medium text-slate-900 dark:text-slate-100">{{
        t('trip_detail.coordination.assign_pair_title')
      }}</span>
      <span class="text-[11px] text-slate-500 dark:text-slate-400">{{
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
      :empty-hint="t('trip_detail.coordination.resource_section_internal_empty')"
      :icon="TruckIcon"
    />

    <ResourceSection
      v-if="!hideInternalDriverSection"
      v-model="selected.internalDrivers"
      :options="internalDriverOptions"
      :is-loading="isLoading"
      :multiple="false"
      :title="t('trip_detail.coordination.resource_section_driver_title')"
      :subtitle="t('trip_detail.coordination.resource_section_driver_sub')"
      :placeholder="t('trip_detail.coordination.resource_section_driver_ph')"
      :empty-hint="t('trip_detail.coordination.resource_section_driver_empty')"
      :icon="UserIcon"
    />

    <ResourceSection
      v-model="selected.taxis"
      :options="taxiOptions"
      :is-loading="isLoading"
      allow-custom-entry
      :title="t('trip_detail.coordination.resource_section_taxi_title')"
      :subtitle="t('trip_detail.coordination.resource_section_taxi_sub')"
      :placeholder="t('trip_detail.coordination.resource_section_taxi_ph')"
      :empty-hint="t('trip_detail.coordination.resource_section_taxi_empty')"
      :icon="BuildingStorefrontIcon"
    />

    <ResourceSection
      v-model="selected.vendors"
      :options="vendorOptions"
      :is-loading="isLoading"
      :title="t('trip_detail.coordination.resource_section_vendor_title')"
      :subtitle="t('trip_detail.coordination.resource_section_vendor_sub')"
      :placeholder="t('trip_detail.coordination.resource_section_vendor_ph')"
      :empty-hint="t('trip_detail.coordination.resource_section_vendor_empty')"
      :icon="BuildingOffice2Icon"
    />

    <div v-if="canQuickCreateVendor" class="mt-1.5">
      <button
        type="button"
        class="w-full rounded-md border border-dashed border-[#8B1A1A] px-3 py-1.5 text-[12px] text-[#8B1A1A] hover:bg-rose-50 dark:hover:bg-rose-950/40"
        @click="$emit('create-vendor')"
      >
        {{ t('trip_detail.coordination.provider_quick_add') }}
      </button>
    </div>

    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="-translate-y-1 opacity-0"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="-translate-y-1 opacity-0"
    >
      <div
        v-if="selected.taxis.length > 0 || selected.vendors.length > 0"
        class="mt-2 flex flex-col gap-2 rounded-lg border border-amber-200/80 bg-amber-50/70 p-2.5 dark:border-amber-800/50 dark:bg-amber-950/30"
      >
        <div>
          <label class="mb-1 block text-[11px] font-medium text-slate-600 dark:text-slate-400">{{
            t('trip_detail.coordination.external_vehicle')
          }}</label>
          <input
            v-model="externalVehicleRef"
            type="text"
            class="w-full rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-[13px] text-slate-900 outline-none ring-sky-200 focus:ring dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
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
            class="w-full rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-[13px] text-slate-900 outline-none ring-sky-200 focus:ring dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
            :placeholder="t('trip_detail.coordination.external_driver_ph')"
          />
        </div>
      </div>
    </Transition>

    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="-translate-y-1 opacity-0"
      leave-active-class="transition duration-150 ease-in"
      leave-to-class="-translate-y-1 opacity-0"
    >
      <div
        v-if="showValidation && panelErrorMessage"
        class="mt-2 rounded-md border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-[12px] font-medium text-rose-800 dark:border-rose-800 dark:bg-rose-950/50 dark:text-rose-200"
        role="alert"
      >
        {{ panelErrorMessage }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, ref, toRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { BuildingOffice2Icon, BuildingStorefrontIcon, TruckIcon, UserIcon } from '@heroicons/vue/24/outline'
import ResourceSection from './ResourceSection.vue'
import { useResourceSelector } from '../../composables/useResourceSelector'

const props = defineProps({
  tripId: { type: [String, Number], required: true },
  availableCount: { type: Number, default: 0 },
  busyVehicleIds: { type: Array, default: () => [] },
  busyDriverIds: { type: Array, default: () => [] },
  tripSnapshot: { type: Object, default: null },
  canQuickCreateVendor: { type: Boolean, default: false },
  /** Ẩn picker xe nội bộ (hiển thị VehicleCard thay thế) */
  hideInternalVehicleSection: { type: Boolean, default: false },
  /** Ẩn picker tài xế nội bộ (hiển thị DriverCard thay thế) */
  hideInternalDriverSection: { type: Boolean, default: false },
})

const emit = defineEmits(['create-vendor', 'update:resources'])

const { t } = useI18n()

const tripIdRef = toRef(props, 'tripId')

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

const showValidation = ref(false)
const externalVehicleRef = ref('')
const externalDriverRef = ref('')

const panelErrorMessage = computed(() => {
  const p = buildDispatchPayload(externalVehicleRef.value, externalDriverRef.value)
  const c = p.validationCode
  if (!c) return ''
  const map = {
    empty: 'trip_detail.coordination.resource_validation_empty',
    mix: 'trip_detail.coordination.resource_validation_mix',
    need_driver: 'trip_detail.coordination.resource_validation_need_driver',
    need_vehicle: 'trip_detail.coordination.resource_validation_need_vehicle',
    need_provider: 'trip_detail.coordination.validation_provider',
  }
  const key = map[c]
  return key ? t(key) : ''
})

const lastPayloadJson = ref('')

function emitResources() {
  const p = buildDispatchPayload(externalVehicleRef.value, externalDriverRef.value)
  const j = JSON.stringify(p)
  if (j === lastPayloadJson.value) return
  lastPayloadJson.value = j
  emit('update:resources', p)
}

watch(
  [selected, externalVehicleRef, externalDriverRef],
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
    await fetchOptions()
    applyHydration({
      vehicleId: snap.vehicleId,
      driverId: snap.driverId,
      transportProviderId: snap.transportProviderId,
    })
    externalVehicleRef.value = snap.externalVehicleRef ?? ''
    externalDriverRef.value = snap.externalDriverRef ?? ''
    lastPayloadJson.value = ''
    emitResources()
  },
  { immediate: true },
)

function validate() {
  showValidation.value = true
  const p = buildDispatchPayload(externalVehicleRef.value, externalDriverRef.value)
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
