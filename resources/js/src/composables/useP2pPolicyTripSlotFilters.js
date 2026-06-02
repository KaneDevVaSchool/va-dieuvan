import { computed, reactive } from 'vue'

export const P2P_TRIPS_PER_PAGE_OPTIONS = [10, 20, 50, 100]
export const P2P_TRIPS_DEFAULT_PER_PAGE = 10

export const P2P_TRIPS_COL_DEFS = [
  { id: 'leg', labelKey: 'p2p_policy_page.col_leg' },
  { id: 'depart', labelKey: 'p2p_policy_page.col_depart' },
  { id: 'driver', labelKey: 'p2p_policy_page.col_driver' },
  { id: 'vehicle', labelKey: 'p2p_policy_page.col_vehicle' },
  { id: 'passengers', labelKey: 'p2p_policy_page.col_passengers' },
  { id: 'reminder', labelKey: 'p2p_policy_page.col_reminder' },
]

export function useP2pPolicyTripSlotFilters() {
  const filters = reactive({
    q: '',
    p2p_policy_term_id: '',
    policy_route_id: '',
    run_date_from: '',
    run_date_to: '',
    leg: '',
    trip_status: '',
    has_trip: '',
    reminder_status: '',
    per_page: P2P_TRIPS_DEFAULT_PER_PAGE,
    page: 1,
  })

  const filterDefs = [
    { id: 'p2p_policy_term_id', labelKey: 'p2p_policy_page.filter_p2p_term' },
    { id: 'policy_route_id', labelKey: 'p2p_policy_page.filter_route' },
    { id: 'run_date_from', labelKey: 'p2p_policy_page.filter_run_date_from' },
    { id: 'run_date_to', labelKey: 'p2p_policy_page.filter_run_date_to' },
    { id: 'leg', labelKey: 'p2p_policy_page.filter_leg' },
    { id: 'trip_status', labelKey: 'p2p_policy_page.filter_trip_status' },
    { id: 'has_trip', labelKey: 'p2p_policy_page.filter_has_trip' },
    { id: 'reminder_status', labelKey: 'p2p_policy_page.filter_reminder_status' },
  ]

  const visibility = reactive({
    p2p_policy_term_id: true,
    policy_route_id: true,
    run_date_from: true,
    run_date_to: true,
    leg: true,
    trip_status: false,
    has_trip: false,
    reminder_status: false,
  })

  const colVisible = reactive({
    leg: true,
    depart: true,
    driver: true,
    vehicle: true,
    passengers: true,
    reminder: true,
  })

  const apiParams = computed(() => {
    const p = {
      per_page: filters.per_page,
      page: filters.page,
    }
    if (filters.q.trim()) p.q = filters.q.trim()
    if (filters.p2p_policy_term_id) p.p2p_policy_term_id = filters.p2p_policy_term_id
    if (filters.policy_route_id) p.policy_route_id = filters.policy_route_id
    if (filters.run_date_from) p.run_date_from = filters.run_date_from
    if (filters.run_date_to) p.run_date_to = filters.run_date_to
    if (filters.leg) p.leg = filters.leg
    if (filters.trip_status) p.trip_status = filters.trip_status
    if (filters.has_trip) p.has_trip = filters.has_trip
    if (filters.reminder_status) p.reminder_status = filters.reminder_status
    return p
  })

  const activeFilterCount = computed(() => {
    let n = 0
    if (filters.p2p_policy_term_id) n++
    if (filters.policy_route_id) n++
    if (filters.run_date_from) n++
    if (filters.run_date_to) n++
    if (filters.leg) n++
    if (filters.trip_status) n++
    if (filters.has_trip) n++
    if (filters.reminder_status) n++
    if (filters.per_page !== P2P_TRIPS_DEFAULT_PER_PAGE) n++
    if (filters.q.trim()) n++
    return n
  })

  function clearFilters() {
    filters.q = ''
    filters.p2p_policy_term_id = ''
    filters.policy_route_id = ''
    filters.run_date_from = ''
    filters.run_date_to = ''
    filters.leg = ''
    filters.trip_status = ''
    filters.has_trip = ''
    filters.reminder_status = ''
    filters.per_page = P2P_TRIPS_DEFAULT_PER_PAGE
    filters.page = 1
  }

  function resetPage() {
    filters.page = 1
  }

  return {
    filters,
    visibility,
    colVisible,
    apiParams,
    activeFilterCount,
    clearFilters,
    resetPage,
    filterDefs,
  }
}
