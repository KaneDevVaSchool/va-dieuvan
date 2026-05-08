import { http } from './http'

/** Khớp max trong `DriverTripHistoryRequest` (Laravel). */
export const DRIVER_TRIPS_LIST_MAX_PER_PAGE = 100

/** @returns {Promise<object>} Tổng hợp bối cảnh tài xế (xe, số liệu, bảo trì) */
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
  let page = 1
  const allItems = []
  let lastMeta = null
  /** @type {Record<string, unknown> | null} */
  let statsFromFirst = null
  const maxPages = 50

  while (page <= maxPages) {
    const res = await listDriverTrips({ ...rest, per_page: perPage, page })
    const batch = res?.items ?? []
    if (page === 1 && res?.stats != null && typeof res.stats === 'object') {
      statsFromFirst = res.stats
    }
    lastMeta = res?.meta ?? lastMeta
    allItems.push(...batch)
    const lastPage = Number(res?.meta?.last_page ?? 1)
    if (page >= lastPage || batch.length === 0) break
    page += 1
  }

  return { items: allItems, meta: lastMeta, stats: statsFromFirst }
}
