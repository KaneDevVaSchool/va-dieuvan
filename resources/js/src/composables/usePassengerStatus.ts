import { bulkSetPassengerStatus, setPassengerStatus } from '../api/trips'
import {
  isOptimisticLockConflict,
  isTripLockConflict,
  withTripLockVersion,
} from '../util/tripLock'
import { showAppError } from './appMessage'

/** Trạng thái vận hành của hành khách — đồng bộ với App\Support\PassengerStatus (PHP). */
export type PassengerStatusKey =
  | 'pending'
  | 'confirmed'
  | 'onboard'
  | 'dropped_off'
  | 'absent'
  | 'cancelled'

export type PassengerStatusMeta = {
  key: PassengerStatusKey
  /** hậu tố i18n: trip_detail.passengers.status.<key> */
  i18n: string
  /** Badge pill cho cột Trạng thái. */
  badge: string
  /** Chấm màu nhỏ (dùng trong KPI / nhóm). */
  dot: string
  /** Chip bộ lọc khi đang chọn. */
  chipActive: string
  /** Viền nhấn cho KPI card. */
  kpiAccent: string
}

/** Thứ tự hiển thị theo vòng đời chuyến: chờ → xác nhận → lên → xuống → vắng → huỷ. */
export const PASSENGER_STATUSES: PassengerStatusMeta[] = [
  {
    key: 'pending',
    i18n: 'pending',
    badge:
      'bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700',
    dot: 'bg-slate-400',
    chipActive:
      'border-slate-400 bg-slate-100 text-slate-800 ring-1 ring-slate-200 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-100',
    kpiAccent: 'text-slate-600 dark:text-slate-300',
  },
  {
    key: 'confirmed',
    i18n: 'confirmed',
    badge:
      'bg-blue-100 text-blue-800 ring-1 ring-blue-200 dark:bg-blue-950/55 dark:text-blue-200 dark:ring-blue-900/70',
    dot: 'bg-blue-500',
    chipActive:
      'border-blue-500 bg-blue-50 text-blue-800 ring-1 ring-blue-200 dark:border-blue-600 dark:bg-blue-950/55 dark:text-blue-200',
    kpiAccent: 'text-blue-600 dark:text-blue-300',
  },
  {
    key: 'onboard',
    i18n: 'onboard',
    badge:
      'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950/55 dark:text-emerald-200 dark:ring-emerald-900/70',
    dot: 'bg-emerald-500',
    chipActive:
      'border-emerald-500 bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200 dark:border-emerald-600 dark:bg-emerald-950/55 dark:text-emerald-200',
    kpiAccent: 'text-emerald-600 dark:text-emerald-300',
  },
  {
    key: 'dropped_off',
    i18n: 'dropped_off',
    badge:
      'bg-teal-100 text-teal-800 ring-1 ring-teal-200 dark:bg-teal-950/55 dark:text-teal-200 dark:ring-teal-900/70',
    dot: 'bg-teal-500',
    chipActive:
      'border-teal-500 bg-teal-50 text-teal-800 ring-1 ring-teal-200 dark:border-teal-600 dark:bg-teal-950/55 dark:text-teal-200',
    kpiAccent: 'text-teal-600 dark:text-teal-300',
  },
  {
    key: 'absent',
    i18n: 'absent',
    badge:
      'bg-amber-100 text-amber-800 ring-1 ring-amber-200 dark:bg-amber-950/55 dark:text-amber-200 dark:ring-amber-900/70',
    dot: 'bg-amber-500',
    chipActive:
      'border-amber-500 bg-amber-50 text-amber-900 ring-1 ring-amber-200 dark:border-amber-600 dark:bg-amber-950/55 dark:text-amber-200',
    kpiAccent: 'text-amber-600 dark:text-amber-300',
  },
  {
    key: 'cancelled',
    i18n: 'cancelled',
    badge:
      'bg-rose-100 text-rose-800 ring-1 ring-rose-200 dark:bg-rose-950/55 dark:text-rose-200 dark:ring-rose-900/70',
    dot: 'bg-rose-500',
    chipActive:
      'border-rose-500 bg-rose-50 text-rose-800 ring-1 ring-rose-200 dark:border-rose-600 dark:bg-rose-950/55 dark:text-rose-200',
    kpiAccent: 'text-rose-600 dark:text-rose-300',
  },
]

const STATUS_BY_KEY = new Map<PassengerStatusKey, PassengerStatusMeta>(
  PASSENGER_STATUSES.map((s) => [s.key, s]),
)

export function passengerStatusMeta(key: string): PassengerStatusMeta {
  return STATUS_BY_KEY.get(key as PassengerStatusKey) ?? PASSENGER_STATUSES[0]
}

type CheckInEntry = {
  status?: string
  checked_in_at?: string
  dropped_off_at?: string
} | null

/** Đọc trạng thái chuẩn hoá từ một entry passenger_check_ins (tương thích entry cũ). */
export function readPassengerStatus(entry: CheckInEntry): PassengerStatusKey {
  if (!entry || typeof entry !== 'object') return 'pending'
  const s = entry.status
  if (typeof s === 'string' && STATUS_BY_KEY.has(s as PassengerStatusKey)) {
    return s as PassengerStatusKey
  }
  // Entry cũ: chỉ có checked_in_at.
  if (entry.checked_in_at) return 'onboard'
  return 'pending'
}

type ApiResult = { ok: boolean; lockConflict?: boolean; data?: Record<string, unknown> }

function handleStatusError(e: unknown, errorMessage: string): ApiResult {
  if (isOptimisticLockConflict(e)) return { ok: false, lockConflict: true }
  if (isTripLockConflict(e)) {
    const resp = (e as { response?: { data?: { message?: string } } })?.response?.data?.message
    showAppError(typeof resp === 'string' ? resp : errorMessage)
    return { ok: false }
  }
  showAppError(errorMessage)
  return { ok: false }
}

/** Đặt trạng thái cho một hành khách (kèm xử lý optimistic lock). */
export async function setPassengerStatusApi(options: {
  tripId: number
  passengerKey: string
  status: PassengerStatusKey
  lockVersion: number
  tripSnapshot?: Record<string, unknown> | null
  errorMessage: string
}): Promise<ApiResult> {
  const { tripId, passengerKey, status, lockVersion, tripSnapshot, errorMessage } = options
  const tripForLock = tripSnapshot ?? { lock_version: lockVersion }
  try {
    const payload = withTripLockVersion(
      { status, at: new Date().toISOString() },
      tripForLock,
    )
    const data = await setPassengerStatus(tripId, passengerKey, payload)
    return { ok: true, data: data as Record<string, unknown> }
  } catch (e) {
    return handleStatusError(e, errorMessage)
  }
}

/** Đặt cùng một trạng thái cho nhiều hành khách. */
export async function bulkSetPassengerStatusApi(options: {
  tripId: number
  keys: string[]
  status: PassengerStatusKey
  lockVersion: number
  tripSnapshot?: Record<string, unknown> | null
  errorMessage: string
}): Promise<ApiResult> {
  const { tripId, keys, status, lockVersion, tripSnapshot, errorMessage } = options
  const tripForLock = tripSnapshot ?? { lock_version: lockVersion }
  try {
    const payload = withTripLockVersion(
      { keys, status, at: new Date().toISOString() },
      tripForLock,
    )
    const data = await bulkSetPassengerStatus(tripId, payload)
    return { ok: true, data: data as Record<string, unknown> }
  } catch (e) {
    return handleStatusError(e, errorMessage)
  }
}
