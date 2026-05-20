import { computed, reactive, ref, watch } from 'vue'

const VIS_STORAGE_KEY = 'p2p-policy-student-filter-vis'

const DEFAULT_VISIBILITY = {
  p2p_policy_term_id: true,
  academic_term_id: true,
  policy_route_id: true,
  campus_id: true,
  class_name: true,
  is_active: true,
  policy_type: true,
  weekday_iso: false,
}

export function useP2pPolicyStudentFilters() {
  const filters = reactive({
    q: '',
    academic_year: '',
    academic_term_id: '',
    p2p_policy_term_id: '',
    policy_route_id: '',
    campus_id: '',
    class_name: '',
    is_active: '',
    policy_type: '',
    weekday_iso: '',
    per_page: 100,
  })

  const visibility = reactive(loadVisibility())

  function loadVisibility() {
    try {
      const raw = localStorage.getItem(VIS_STORAGE_KEY)
      if (raw) {
        return { ...DEFAULT_VISIBILITY, ...JSON.parse(raw) }
      }
    } catch {
      /* ignore */
    }
    return { ...DEFAULT_VISIBILITY }
  }

  watch(
    visibility,
    () => {
      try {
        localStorage.setItem(VIS_STORAGE_KEY, JSON.stringify({ ...visibility }))
      } catch {
        /* ignore */
      }
    },
    { deep: true },
  )

  const apiParams = computed(() => {
    const p = {}
    if (filters.q.trim()) p.q = filters.q.trim()
    if (filters.academic_year) p.academic_year = filters.academic_year
    if (filters.academic_term_id) p.academic_term_id = filters.academic_term_id
    if (filters.p2p_policy_term_id) p.p2p_policy_term_id = filters.p2p_policy_term_id
    if (filters.policy_route_id) p.policy_route_id = filters.policy_route_id
    if (filters.campus_id) p.campus_id = filters.campus_id
    if (filters.class_name) p.class_name = filters.class_name
    if (filters.is_active !== '') p.is_active = filters.is_active
    if (filters.policy_type) p.policy_type = filters.policy_type
    if (filters.weekday_iso) p.weekday_iso = filters.weekday_iso
    p.per_page = filters.per_page
    return p
  })

  const activeFilterCount = computed(() => {
    let n = 0
    if (filters.academic_year) n++
    if (filters.academic_term_id) n++
    if (filters.p2p_policy_term_id) n++
    if (filters.policy_route_id) n++
    if (filters.campus_id) n++
    if (filters.class_name) n++
    if (filters.is_active !== '') n++
    if (filters.policy_type) n++
    if (filters.weekday_iso) n++
    return n
  })

  function clearFilters() {
    filters.q = ''
    filters.academic_year = ''
    filters.academic_term_id = ''
    filters.p2p_policy_term_id = ''
    filters.policy_route_id = ''
    filters.campus_id = ''
    filters.class_name = ''
    filters.is_active = ''
    filters.policy_type = ''
    filters.weekday_iso = ''
  }

  const page = ref(1)

  return {
    filters,
    visibility,
    apiParams,
    activeFilterCount,
    clearFilters,
    page,
    filterDefs: [
      { id: 'p2p_policy_term_id', labelKey: 'p2p_policy_page.filter_p2p_term' },
      { id: 'academic_term_id', labelKey: 'p2p_policy_page.filter_term' },
      { id: 'policy_route_id', labelKey: 'p2p_policy_page.filter_assigned_group' },
      { id: 'campus_id', labelKey: 'p2p_policy_page.filter_campus' },
      { id: 'class_name', labelKey: 'p2p_policy_page.filter_class' },
      { id: 'is_active', labelKey: 'p2p_policy_page.filter_active' },
      { id: 'policy_type', labelKey: 'p2p_policy_page.filter_policy_type' },
      { id: 'weekday_iso', labelKey: 'p2p_policy_page.filter_weekday' },
    ],
  }
}
