import { CubeIcon, TruckIcon, UserGroupIcon } from '@heroicons/vue/24/outline'

/** Loại hiển thị icon: xe tải / van / xe khách */
export const VEHICLE_ICON_COMPONENTS = {
  truck: TruckIcon,
  van: CubeIcon,
  bus: UserGroupIcon,
}

/**
 * @param {{ type?: string | null, seat_count?: number | null, payload_kg?: number | null }} v
 * @returns {'truck'|'van'|'bus'}
 */
export function vehicleIconKind(v) {
  if (v?.payload_kg) return 'truck'
  const n = v?.seat_count
  if (n != null && n >= 28) return 'bus'
  const s = (v?.type || '').toLowerCase()
  if (/tải|truck|van 5|van 10|kg|payload|isuzu|qkr|hino/.test(s)) return 'truck'
  if (/28|45|bus|coach|thaco|khách lớn|xe bus/.test(s)) return 'bus'
  if (/van|transit|minibus|7 chỗ|15 chỗ/.test(s)) return 'van'
  return 'van'
}
