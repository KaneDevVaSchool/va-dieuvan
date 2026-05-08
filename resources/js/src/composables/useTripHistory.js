import { computed, ref } from 'vue'
import { http } from '../api/http'

/**
 * Driver trip history via GET /api/driver/trips
 * Params: date_from, date_to, status (optional), page, per_page (max 15)
 */
export function useTripHistory() {
  const trips = ref([])
  const stats = ref(null)
  const isLoading = ref(false)
  const error = ref('')
  const page = ref(1)
  const lastPage = ref(1)

  const hasMore = computed(() => page.value < lastPage.value)

  function buildParams(baseParams, pageNum) {
    const params = {
      ...baseParams,
      per_page: 15,
      page: pageNum,
    }
    if (params.status === 'all' || params.status == null || params.status === '') {
      delete params.status
    }
    return params
  }

  /** @param {Record<string, unknown>} baseParams @param {boolean} append */
  async function fetch(baseParams, append = false) {
    error.value = ''
    const nextPage = append ? page.value + 1 : 1
    if (!append) {
      trips.value = []
      page.value = 1
      lastPage.value = 1
    }
    isLoading.value = true
    try {
      const { data } = await http.get('/driver/trips', {
        params: buildParams(baseParams, nextPage),
      })
      const d = data?.data ?? {}
      const items = Array.isArray(d.items) ? d.items : []
      const meta = d.meta ?? {}

      if (!append) {
        trips.value = items
        if (d.stats != null && typeof d.stats === 'object') {
          const s = d.stats
          stats.value = {
            total: s.total ?? 0,
            completed: s.completed ?? s.completed_this_month ?? 0,
            cancelled: s.cancelled ?? s.cancelled_this_month ?? 0,
            total_km: s.total_km ?? null,
            completed_this_month: s.completed_this_month ?? s.completed ?? 0,
            cancelled_this_month: s.cancelled_this_month ?? s.cancelled ?? 0,
            completed_growth_pct: s.completed_growth_pct ?? null,
            overdue_count: s.overdue_count ?? 0,
          }
        } else {
          stats.value = {
            total: 0,
            completed: 0,
            cancelled: 0,
            total_km: null,
            completed_this_month: 0,
            cancelled_this_month: 0,
            completed_growth_pct: null,
            overdue_count: 0,
          }
        }
      } else {
        trips.value = [...trips.value, ...items]
      }
      page.value = Number(meta.current_page) || nextPage
      lastPage.value = Number(meta.last_page) || 1
    } catch {
      error.value = 'fetch_failed'
      if (!append) {
        trips.value = []
        stats.value = null
      }
    } finally {
      isLoading.value = false
    }
  }

  /** @param {Record<string, unknown>} baseParams */
  async function loadMore(baseParams) {
    if (!hasMore.value || isLoading.value) return
    await fetch(baseParams, true)
  }

  return {
    trips,
    stats,
    isLoading,
    error,
    hasMore,
    page,
    lastPage,
    fetch,
    loadMore,
  }
}
