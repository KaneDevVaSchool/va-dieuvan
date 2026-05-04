export interface ResourceItem {
  id: string | number
  label: string
  sublabel?: string
  /** undefined/true = có thể chọn; false = bận / không chọn */
  available?: boolean
  /** Taxi nhập tay (không có bản ghi transport_provider) */
  isCustom?: boolean
}

export interface SelectedResources {
  internalVehicles: ResourceItem[]
  /** Tối đa 1 tài xế — UI dùng ResourceSection multiple=false */
  internalDrivers: ResourceItem[]
  taxis: ResourceItem[]
  vendors: ResourceItem[]
}

export type ResourceDispatchValidationCode =
  | null
  | 'empty'
  | 'mix'
  | 'need_driver'
  | 'need_vehicle'
  | 'need_provider'

export interface ResourceDispatchPayload {
  readyForSubmit: boolean
  validationCode: ResourceDispatchValidationCode
  mode: 'internal' | 'external'
  vehicle_id: number | null
  driver_id: number | null
  transport_provider_id: number | null
  external_vehicle_ref: string | null
  external_driver_ref: string | null
  internal_vehicle_ids: Array<string | number>
  taxi_ids: Array<string | number>
  vendor_ids: Array<string | number>
  primaryVehicleId: number | null
}
