import { computed, reactive, watch } from 'vue'

const VIS_STORAGE_KEY = 'p2p-policy-routes-filter-vis'

export const P2P_ROUTES_PER_PAGE_OPTIONS = [5, 10, 15, 20]
export const P2P_ROUTES_DEFAULT_PER_PAGE = 10

const defaultVisibility = () => ({
  p2p_policy_term_id: true,
  is_active: true,
  per_page: true,
})

function loadVisibility() {
  try {
    const raw = localStorage.getItem(VIS_STORAGE_KEY)
    if (!raw) return defaultVisibility()
    return { ...defaultVisibility(), ...JSON.parse(raw) }
  } catch {
    return defaultVisibility()
  }
}

export function useP2pPolicyRoutesFilters() {
  const filters = reactive({
    p2p_policy_term_id: '',
    is_active: '',
    per_page: P2P_ROUTES_DEFAULT_PER_PAGE,
    page: 1,
  })

  const visibility = reactive(loadVisibility())

  watch(
    visibility,
    (v) => {
      try {
        localStorage.setItem(VIS_STORAGE_KEY, JSON.stringify(v))
      } catch {
        /* ignore */
      }
    },
    { deep: true },
  )

  const filterControlDefs = [
    { id: 'p2p_policy_term_id', labelKey: 'p2p_policy_page.filter_p2p_term' },
    { id: 'is_active', labelKey: 'p2p_policy_page.filter_active' },
    { id: 'per_page', labelKey: 'filter_bar.per_page' },
  ]

  const activeFilterCount = computed(() => {
    let n = 0
    if (filters.p2p_policy_term_id) n++
    if (filters.is_active !== '') n++
    if (filters.per_page !== P2P_ROUTES_DEFAULT_PER_PAGE) n++
    return n
  })

  const apiParams = computed(() => {
    const p = {
      per_page: filters.per_page,
      page: filters.page,
    }
    if (filters.p2p_policy_term_id) p.p2p_policy_term_id = filters.p2p_policy_term_id
    if (filters.is_active !== '') p.is_active = filters.is_active === 'true' || filters.is_active === true
    return p
  })

  function clearFilters() {
    filters.p2p_policy_term_id = ''
    filters.is_active = ''
    filters.per_page = P2P_ROUTES_DEFAULT_PER_PAGE
    filters.page = 1
  }

  function resetPage() {
    filters.page = 1
  }

  return {
    filters,
    visibility,
    filterControlDefs,
    activeFilterCount,
    apiParams,
    clearFilters,
    resetPage,
  }
}
