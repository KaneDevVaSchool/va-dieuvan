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
  const allItems = []
  let lastMeta = null
  /** @type {Record<string, unknown> | null} */
  let statsFromFirst = null
  const maxPages = 50
  const first = await listDriverTrips({ ...rest, per_page: perPage, page: 1 })
  const batch1 = first?.items ?? []
  if (first?.stats != null && typeof first.stats === 'object') {
    statsFromFirst = first.stats
  }
  lastMeta = first?.meta ?? lastMeta
  allItems.push(...batch1)
  let lastPage = Math.min(Number(first?.meta?.last_page ?? 1), maxPages)

  if (lastPage <= 1) {
    return { items: allItems, meta: lastMeta, stats: statsFromFirst }
  }

  /** Fetch các trang còn lại song song (từng batch) để giảm waterfall trên PWA. */
  const CONCURRENCY = 4
  for (let start = 2; start <= lastPage; start += CONCURRENCY) {
    const end = Math.min(start + CONCURRENCY - 1, lastPage)
    const promises = []
    for (let p = start; p <= end; p += 1) {
      promises.push(listDriverTrips({ ...rest, per_page: perPage, page: p }))
    }
    const pages = await Promise.all(promises)
    for (const res of pages) {
      allItems.push(...(res?.items ?? []))
      lastMeta = res?.meta ?? lastMeta
    }
  }

  return { items: allItems, meta: lastMeta, stats: statsFromFirst }
}
