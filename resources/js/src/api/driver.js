import { http } from './http'

/** Khớp max trong `DriverTripHistoryRequest` (Laravel). */
export const DRIVER_TRIPS_LIST_MAX_PER_PAGE = 100

/** Tối đa số trang kéo thêm để khỏi backlog vô hạn nếu meta lệch. */
export const DRIVER_TRIPS_FETCH_MAX_PAGES = 50

/** Fetch song song các trang 2…lastPage (dùng chung incremental + full bundle). */
const PAGE_FETCH_CONCURRENCY = 10

/** @returns {Promise<object>} Tổng hợp bối cảnh tài xế (xe, số liệu) */
export async function getDriverSummary() {
  const { data } = await http.get('/driver/summary')
  return data.data
}

/**
 * Một trang lịch sử chuyến tài xế (GET /api/driver/trips).
 * @param {Record<string, unknown>} params date_from, date_to, status, page, per_page
 */
export async function listDriverTrips(params = {}) {
  const { data } = await http.get('/driver/trips', { params })
  return data.data
}

/** Học sinh trên tuyến gắn chuyến D2D (khi có RouteRun). */
export async function listDriverTripPolicyStudents(tripId) {
  const { data } = await http.get(`/driver/trips/${tripId}/policy-students`)
  return data.data?.students ?? []
}

/**
 * Các trang 2…lastPage (per_page cố định), gom song song theo batch.
 * @param {Record<string, unknown>} restParams date_from, date_to, status, …
 * @param {number} perPage
 * @param {number} lastPage từ meta trang 1 (đã clamp)
 * @returns {Promise<unknown[]>}
 */
export async function listDriverTripPagesAfterFirst(restParams, perPage, lastPage) {
  const cap = Math.min(Math.max(1, lastPage), DRIVER_TRIPS_FETCH_MAX_PAGES)
  if (cap <= 1) return []

  const out = []
  for (let start = 2; start <= cap; start += PAGE_FETCH_CONCURRENCY) {
    const end = Math.min(start + PAGE_FETCH_CONCURRENCY - 1, cap)
    const promises = []
    for (let p = start; p <= end; p += 1) {
      promises.push(listDriverTrips({ ...restParams, per_page: perPage, page: p }))
    }
    const pages = await Promise.all(promises)
    for (const res of pages) {
      out.push(...(res?.items ?? []))
    }
  }
  return out
}

/**
 * Paginate phía server cho đến hết trong khoảng ngày (per_page mặc định 100).
 * Trả về `stats` từ trang 1 (trùng nhau mọi trang) để gộp KPI dashboard.
 * @param {Record<string, unknown>} params
 */
export async function listDriverTripsAll(params = {}) {
  const { page: _drop, per_page: perPageRequested, ...rest } = params
  const perPage = Math.min(
    DRIVER_TRIPS_LIST_MAX_PER_PAGE,
    Math.max(1, Number(perPageRequested) || DRIVER_TRIPS_LIST_MAX_PER_PAGE),
  )
  const first = await listDriverTrips({ ...rest, per_page: perPage, page: 1 })
  const batch1 = first?.items ?? []
  /** @type {Record<string, unknown> | null} */
  let statsFromFirst = null
  if (first?.stats != null && typeof first.stats === 'object') {
    statsFromFirst = first.stats
  }
  const lastMeta = first?.meta ?? null
  const lastPage = Math.min(Number(first?.meta?.last_page ?? 1), DRIVER_TRIPS_FETCH_MAX_PAGES)

  if (lastPage <= 1) {
    return { items: batch1, meta: lastMeta, stats: statsFromFirst }
  }

  const restItems = await listDriverTripPagesAfterFirst(rest, perPage, lastPage)
  return { items: [...batch1, ...restItems], meta: lastMeta, stats: statsFromFirst }
}
