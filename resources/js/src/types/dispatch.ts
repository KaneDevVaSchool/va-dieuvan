export interface ResourceItem {
  id: string | number
  label: string
  sublabel?: string
  /** undefined/true = có thể chọn; false = bận / không chọn */
  available?: boolean
  /** Taxi nhập tay (không có bản ghi transport_provider) */
  isCustom?: boolean
  /** Gắn từ API vehicle — đồng bộ xe khi chọn tài xế */
  defaultDriverId?: number | null
  seatCount?: number | null
  /** Taxi / NCC — số chỗ khai báo cho đúng dòng bổ sung (cộng vào sức chở). */
  supplementSeats?: number | null
  /** Tham chiếu xe/lái ngoài (chủ yếu NCC), gộp khi submit. */
  externalVehicleRef?: string | null
  externalDriverRef?: string | null
}

export interface SelectedResources {
  internalVehicles: ResourceItem[]
  /** Tối đa 1 tài xế — UI dùng ResourceSection multiple=false */
  internalDrivers: ResourceItem[]
  taxis: ResourceItem[]
  vendors: ResourceItem[]
}

export type ResourceDispatchValidationCode = null | 'empty' | 'need_provider'

/** Snapshot đầy đủ của một item bổ sung phương tiện — được lưu xuống DB. */
export interface SupplementItem {
  id: string
  label: string
  supplementSeats?: number | null
  isCustom?: boolean | null
  externalVehicleRef?: string | null
  externalDriverRef?: string | null
}

/** Cấu trúc JSON lưu trong trips.supplement_transports */
export interface SupplementTransports {
  taxis: SupplementItem[]
  vendors: SupplementItem[]
}

export interface ResourceDispatchPayload {
  readyForSubmit: boolean
  validationCode: ResourceDispatchValidationCode
  mode: 'internal' | 'external' | 'combined'
  vehicle_id: number | null
  driver_id: number | null
  transport_provider_id: number | null
  external_vehicle_ref: string | null
  external_driver_ref: string | null
  internal_vehicle_ids: Array<string | number>
  taxi_ids: Array<string | number>
  vendor_ids: Array<string | number>
  primaryVehicleId: number | null
  /** Số chỗ bổ sung khai báo cho phần taxi (cộng vào sức chở ước tính). */
  taxiSeatSupplement: number
  /** Số chỗ bổ sung khai báo cho phần NCC. */
  nccSeatSupplement: number
  /** Danh sách đầy đủ để lưu DB và hydrate lại sau refresh. */
  supplementTransports: SupplementTransports
}
