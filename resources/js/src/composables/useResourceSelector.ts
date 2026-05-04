import { type Ref, ref, unref, watch } from 'vue'
import { listDrivers, listTransportProviders, listVehicles } from '../api/operational'
import type { ResourceDispatchPayload, ResourceItem, ResourceDispatchValidationCode, SelectedResources } from '../types/dispatch'

function toResourceVehicle(v: Record<string, unknown>, busy: Set<number>): ResourceItem {
  const id = v.id as number
  const plate = String(v.license_plate ?? '')
  const type = v.type != null ? String(v.type) : '—'
  const seats = v.seat_count != null ? Number(v.seat_count) : 0
  const subParts: string[] = []
  const def = v.default_driver as { full_name?: string } | undefined
  if (def?.full_name) subParts.push(String(def.full_name))
  return {
    id,
    label: `${plate} · ${type}${seats ? ` (${seats})` : ''}`,
    sublabel: subParts.length ? subParts.join(' · ') : undefined,
    available: !busy.has(id),
  }
}

function toResourceDriver(d: Record<string, unknown>, busy: Set<number>): ResourceItem {
  const id = d.id as number
  const name = String(d.full_name ?? '')
  const phone = d.phone != null ? String(d.phone) : ''
  return {
    id,
    label: name,
    sublabel: phone || undefined,
    available: !busy.has(id),
  }
}

function toResourceProvider(p: Record<string, unknown>): ResourceItem {
  const id = p.id as number
  const name = String(p.name ?? '')
  const type = p.type != null ? String(p.type) : ''
  return {
    id,
    label: name,
    sublabel: type || undefined,
    available: true,
  }
}

export function useResourceSelector(
  tripId: Ref<string | number | null | undefined>,
  busyVehicleIds: Ref<Set<number>>,
  busyDriverIds: Ref<Set<number>>,
) {
  const internalVehicleOptions = ref<ResourceItem[]>([])
  const internalDriverOptions = ref<ResourceItem[]>([])
  const taxiOptions = ref<ResourceItem[]>([])
  const vendorOptions = ref<ResourceItem[]>([])
  const isLoading = ref(false)

  const selected = ref<SelectedResources>({
    internalVehicles: [],
    internalDrivers: [],
    taxis: [],
    vendors: [],
  })

  async function fetchOptions() {
    const tid = unref(tripId)
    if (tid == null || tid === '') {
      internalVehicleOptions.value = []
      internalDriverOptions.value = []
      taxiOptions.value = []
      vendorOptions.value = []
      return
    }
    isLoading.value = true
    try {
      const bv = busyVehicleIds.value
      const bd = busyDriverIds.value
      const [vr, dr, pr] = await Promise.all([
        listVehicles({ status: 'ready', per_page: 150 }),
        listDrivers({ employment_status: 'active', availability_status: 'available', per_page: 150 }),
        listTransportProviders({ is_active: true, per_page: 200 }),
      ])
      const vItems = (vr?.items ?? []) as Record<string, unknown>[]
      const dItems = (dr?.items ?? []) as Record<string, unknown>[]
      const pItems = (pr?.items ?? []) as Record<string, unknown>[]
      internalVehicleOptions.value = vItems.map((v) => toResourceVehicle(v, bv))
      internalDriverOptions.value = dItems.map((d) => toResourceDriver(d, bd))
      const taxis: ResourceItem[] = []
      const vendors: ResourceItem[] = []
      for (const p of pItems) {
        const t = String(p.type ?? '').toLowerCase()
        const r = toResourceProvider(p)
        if (t === 'taxi') taxis.push(r)
        else vendors.push(r)
      }
      taxiOptions.value = taxis
      vendorOptions.value = vendors
    } finally {
      isLoading.value = false
    }
  }

  watch(
    tripId,
    () => {
      void fetchOptions()
    },
    { immediate: true },
  )

  watch([busyVehicleIds, busyDriverIds], () => {
    const bv = busyVehicleIds.value
    const bd = busyDriverIds.value
    internalVehicleOptions.value = internalVehicleOptions.value.map((item) => ({
      ...item,
      available: !bv.has(Number(item.id)),
    }))
    internalDriverOptions.value = internalDriverOptions.value.map((item) => ({
      ...item,
      available: !bd.has(Number(item.id)),
    }))
  })

  function validationCode(): ResourceDispatchValidationCode {
    const intV = selected.value.internalVehicles
    const intD = selected.value.internalDrivers
    const txs = selected.value.taxis
    const vds = selected.value.vendors

    const hasInternalPiece = intV.length > 0 || intD.length > 0
    const hasExternalPiece = txs.length > 0 || vds.length > 0

    if (!hasInternalPiece && !hasExternalPiece) return 'empty'
    if (hasInternalPiece && hasExternalPiece) return 'mix'
    if (hasExternalPiece) {
      if (vds.length === 0 && txs.length === 0) return 'need_provider'
      return null
    }
    if (intV.length > 0 && intD.length === 0) return 'need_driver'
    if (intD.length > 0 && intV.length === 0) return 'need_vehicle'
    if (intV.length > 0 && intD.length > 0) return null
    return 'empty'
  }

  function buildDispatchPayload(
    externalVehicleRef: string,
    externalDriverRef: string,
  ): ResourceDispatchPayload {
    const code = validationCode()
    const intV = selected.value.internalVehicles
    const intD = selected.value.internalDrivers
    const txs = selected.value.taxis
    const vds = selected.value.vendors

    const base: ResourceDispatchPayload = {
      readyForSubmit: false,
      validationCode: code,
      mode: 'internal',
      vehicle_id: null,
      driver_id: null,
      transport_provider_id: null,
      external_vehicle_ref: externalVehicleRef?.trim() || null,
      external_driver_ref: externalDriverRef?.trim() || null,
      internal_vehicle_ids: intV.map((x) => x.id),
      taxi_ids: txs.map((x) => x.id),
      vendor_ids: vds.map((x) => x.id),
      primaryVehicleId: null,
    }

    if (code !== null) return base

    if (txs.length > 0 || vds.length > 0) {
      const providerRaw = vds[0]?.id ?? txs[0]?.id
      const providerId = providerRaw != null ? Number(providerRaw) : null
      return {
        ...base,
        readyForSubmit: providerId != null,
        validationCode: providerId == null ? 'need_provider' : null,
        mode: 'external',
        vehicle_id: null,
        driver_id: null,
        transport_provider_id: providerId,
        primaryVehicleId: null,
      }
    }

    const vehicleId = intV.length ? Number(intV[0].id) : null
    const driverId = intD.length ? Number(intD[0].id) : null
    return {
      ...base,
      readyForSubmit: vehicleId != null && driverId != null,
      mode: 'internal',
      vehicle_id: vehicleId,
      driver_id: driverId,
      transport_provider_id: null,
      external_vehicle_ref: null,
      external_driver_ref: null,
      primaryVehicleId: vehicleId,
    }
  }

  function applyHydration(snapshot: {
    vehicleId?: number | null
    driverId?: number | null
    transportProviderId?: number | null
    externalVehicleRef?: string | null
    externalDriverRef?: string | null
  }) {
    selected.value = {
      internalVehicles: [],
      internalDrivers: [],
      taxis: [],
      vendors: [],
    }
    const vid = snapshot.vehicleId
    if (vid != null) {
      const found = internalVehicleOptions.value.find((x) => Number(x.id) === Number(vid))
      if (found) selected.value.internalVehicles = [found]
      else {
        selected.value.internalVehicles = [
          { id: vid, label: `#${vid}`, sublabel: undefined, available: true },
        ]
      }
    }
    const did = snapshot.driverId
    if (did != null) {
      const found = internalDriverOptions.value.find((x) => Number(x.id) === Number(did))
      if (found) selected.value.internalDrivers = [found]
      else {
        selected.value.internalDrivers = [{ id: did, label: `#${did}`, available: true }]
      }
    }
    const pid = snapshot.transportProviderId
    if (pid != null) {
      const taxi = taxiOptions.value.find((x) => Number(x.id) === Number(pid))
      const vendor = vendorOptions.value.find((x) => Number(x.id) === Number(pid))
      if (taxi) selected.value.taxis = [taxi]
      else if (vendor) selected.value.vendors = [vendor]
      else {
        selected.value.vendors = [{ id: pid, label: `#${pid}`, available: true }]
      }
    }
  }

  return {
    internalVehicleOptions,
    internalDriverOptions,
    taxiOptions,
    vendorOptions,
    selected,
    isLoading,
    buildDispatchPayload,
    fetchOptions,
    applyHydration,
  }
}
