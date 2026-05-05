import { type Ref, ref, unref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { listDrivers, listTransportProviders, listVehicles } from '../api/operational'
import type { ResourceDispatchPayload, ResourceItem, ResourceDispatchValidationCode, SelectedResources } from '../types/dispatch'

function isCustomResource(it: ResourceItem): boolean {
  return it.isCustom === true || String(it.id).startsWith('custom:')
}

function toResourceVehicle(v: Record<string, unknown>, busy: Set<number>): ResourceItem {
  const id = v.id as number
  const plate = String(v.license_plate ?? '')
  const type = v.type != null ? String(v.type) : '—'
  const seatsRaw = v.seat_count != null ? Number(v.seat_count) : 0
  const seats = Number.isFinite(seatsRaw) && seatsRaw > 0 ? seatsRaw : 0
  const subParts: string[] = []
  const def = v.default_driver as { id?: number; full_name?: string } | undefined
  if (def?.full_name) subParts.push(String(def.full_name))
  const defId = def?.id != null && Number.isFinite(Number(def.id)) ? Number(def.id) : null
  return {
    id,
    label: `${plate} · ${type}${seats ? ` (${seats})` : ''}`,
    sublabel: subParts.length ? subParts.join(' · ') : undefined,
    available: !busy.has(id),
    defaultDriverId: defId,
    seatCount: seats > 0 ? seats : null,
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
  const { t } = useI18n()
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

  const MAX_EXTERNAL_VEHICLE_REF = 255

  function truncateExternalRef(s: string): string {
    const t = s.trim()
    if (t.length <= MAX_EXTERNAL_VEHICLE_REF) return t
    return `${t.slice(0, MAX_EXTERNAL_VEHICLE_REF - 1)}…`
  }

  function validationCode(): ResourceDispatchValidationCode {
    const intV = selected.value.internalVehicles
    const intD = selected.value.internalDrivers
    const txs = selected.value.taxis
    const vds = selected.value.vendors

    const hasV = intV.length > 0
    const hasD = intD.length > 0
    const hasT = txs.length > 0
    const hasP = vds.length > 0
    if (!hasV && !hasD && !hasT && !hasP) return 'empty'

    const numericTaxis = txs.filter((t) => !isCustomResource(t))
    const numericVendors = vds.filter((v) => !isCustomResource(v))
    const customTaxiOk = txs
      .filter((t) => isCustomResource(t))
      .some((x) => String(x.label ?? '').trim().length > 0)
    const customVendorOk = vds
      .filter((v) => isCustomResource(v))
      .some((x) => String(x.label ?? '').trim().length > 0)

    if (hasT || hasP) {
      const hasNumericId = numericVendors.length > 0 || numericTaxis.length > 0
      if (!hasNumericId && !customTaxiOk && !customVendorOk) return 'need_provider'
    }

    return null
  }

  function sumSupplementSeats(items: ResourceItem[]): number {
    return items.reduce((acc, it) => {
      const n = Number(it.supplementSeats)
      if (!Number.isFinite(n) || n <= 0) return acc
      return acc + Math.floor(n)
    }, 0)
  }

  function aggregateExternalRefs(items: ResourceItem[]): { vehicle: string; driver: string } {
    const vp: string[] = []
    const dp: string[] = []
    for (const it of items) {
      const ev = String(it.externalVehicleRef ?? '').trim()
      const ed = String(it.externalDriverRef ?? '').trim()
      if (ev) vp.push(ev)
      if (ed) dp.push(ed)
    }
    return { vehicle: vp.join(' · '), driver: dp.join(' · ') }
  }

  function buildDispatchPayload(): ResourceDispatchPayload {
    const code = validationCode()
    const intV = selected.value.internalVehicles
    const intD = selected.value.internalDrivers
    const txs = selected.value.taxis
    const vds = selected.value.vendors

    const hasV = intV.length > 0
    const hasD = intD.length > 0

    const numericTaxis = txs.filter((t) => !isCustomResource(t))
    const numericVendors = vds.filter((v) => !isCustomResource(v))

    const taxiSeatSupplement = sumSupplementSeats(txs)
    const nccSeatSupplement = sumSupplementSeats(vds)

    function lineForItem(it: ResourceItem): string {
      const lab = String(it.label ?? '').trim()
      if (!lab) return ''
      const n = Number(it.supplementSeats)
      const seats = Number.isFinite(n) && n > 0 ? Math.floor(n) : 0
      if (seats > 0) return `${lab} — ${t('trip_detail.coordination.seats_n', { n: seats })}`
      return lab
    }

    function supplementDescribeBlock(kind: 'taxi' | 'vendor', items: ResourceItem[]): string {
      const parts = items.map(lineForItem).filter(Boolean)
      if (!parts.length) return ''
      const head =
        kind === 'taxi'
          ? t('trip_detail.coordination.resource_section_taxi_title')
          : t('trip_detail.coordination.resource_section_vendor_title')
      return `${head}: ${parts.join(' · ')}`
    }

    const taxiBlock = supplementDescribeBlock('taxi', txs)
    const nccBlock = supplementDescribeBlock('vendor', vds)
    const prefixParts = [taxiBlock, nccBlock].filter(Boolean)

    const taxiExt = aggregateExternalRefs(txs)
    const vendorExt = aggregateExternalRefs(vds)
    const userEvInput = [taxiExt.vehicle, vendorExt.vehicle].filter(Boolean).join(' · ')
    const userEdInput = [taxiExt.driver, vendorExt.driver].filter(Boolean).join(' · ')

    const base: ResourceDispatchPayload = {
      readyForSubmit: false,
      validationCode: code,
      mode: 'internal',
      vehicle_id: null,
      driver_id: null,
      transport_provider_id: null,
      external_vehicle_ref: null,
      external_driver_ref: userEdInput ? userEdInput : null,
      internal_vehicle_ids: intV.map((x) => x.id),
      taxi_ids: txs.map((x) => x.id),
      vendor_ids: vds.map((x) => x.id),
      primaryVehicleId: null,
      taxiSeatSupplement,
      nccSeatSupplement,
    }

    if (code !== null) {
      return {
        ...base,
        external_vehicle_ref: userEvInput ? userEvInput : null,
        external_driver_ref: userEdInput ? userEdInput : null,
      }
    }

    const vehicleId = hasV ? Number(intV[0].id) : null
    const driverId = hasD ? Number(intD[0].id) : null
    const safeVehicleId = Number.isFinite(vehicleId) ? vehicleId : null
    const safeDriverId = Number.isFinite(driverId) ? driverId : null

    let providerId: number | null = null
    if (numericVendors.length > 0) {
      const id = Number(numericVendors[0].id)
      providerId = Number.isFinite(id) ? id : null
    } else if (numericTaxis.length > 0) {
      const id = Number(numericTaxis[0].id)
      providerId = Number.isFinite(id) ? id : null
    }

    const prefix = prefixParts.join(' · ')
    const userEv = userEvInput?.trim() || ''
    let evRef = ''
    if (prefix && userEv) evRef = `${prefix} · ${userEv}`
    else if (prefix) evRef = prefix
    else evRef = userEv
    evRef = evRef ? truncateExternalRef(evRef) : ''

    const hasCustomTaxi = txs.some(
      (x) => isCustomResource(x) && String(x.label ?? '').trim().length > 0,
    )
    const hasCustomVendor = vds.some(
      (x) => isCustomResource(x) && String(x.label ?? '').trim().length > 0,
    )
    const hasExternalBody =
      numericTaxis.length > 0 ||
      numericVendors.length > 0 ||
      hasCustomTaxi ||
      hasCustomVendor

    const hasInternal = hasV || hasD
    const mode =
      hasInternal && hasExternalBody ? 'combined' : hasInternal ? 'internal' : 'external'

    const ready = hasV || hasD || hasExternalBody

    const extVehicleFinal = hasExternalBody ? evRef || null : null
    const extDriverFinal = hasExternalBody ? (userEdInput?.trim() || null) : null

    return {
      ...base,
      readyForSubmit: ready,
      validationCode: null,
      mode,
      vehicle_id: safeVehicleId,
      driver_id: safeDriverId,
      transport_provider_id: providerId,
      external_vehicle_ref: extVehicleFinal,
      external_driver_ref: extDriverFinal,
      primaryVehicleId: safeVehicleId,
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

    const extV = String(snapshot.externalVehicleRef ?? '').trim()
    const extD = String(snapshot.externalDriverRef ?? '').trim()
    if (extV || extD) {
      if (selected.value.vendors.length > 0) {
        const v0 = selected.value.vendors[0]
        selected.value.vendors[0] = {
          ...v0,
          ...(extV ? { externalVehicleRef: extV } : {}),
          ...(extD ? { externalDriverRef: extD } : {}),
        }
      } else if (selected.value.taxis.length > 0) {
        const t0 = selected.value.taxis[0]
        selected.value.taxis[0] = {
          ...t0,
          ...(extV ? { externalVehicleRef: extV } : {}),
          ...(extD ? { externalDriverRef: extD } : {}),
        }
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
