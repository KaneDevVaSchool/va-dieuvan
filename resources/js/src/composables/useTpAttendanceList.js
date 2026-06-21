import { computed, reactive, ref, watch } from 'vue'

export const ATTENDANCE_FILTER_VIS_IDS = ['boarded_time', 'notes']

export function rowNotesSearchText(row) {
  return `${row.reason_code || ''} ${row.absence_reason || ''} ${row.driver_notes || ''}`.trim().toLowerCase()
}

export const ATTENDANCE_COL_STORAGE_KEY = 'va-tp-attendance-cols-v2'

export const ATTENDANCE_PER_PAGE_OPTIONS = [5, 10, 15, 20]

export const ATTENDANCE_COL_DEFAULTS = {
  class_name: true,
  boarded_time: true,
  status: true,
  notes: true,
  pickup_point: false,
  parent_phone: false,
  code: false,
  attendance_toggle: true,
}

const SORT_KEYS = {
  student: (r) => (r.full_name || '').toLowerCase(),
  code: (r) => (r.code || '').toLowerCase(),
  class_name: (r) => (r.class_name || '').toLowerCase(),
  pickup_point: (r) => (r.pickup_point || '').toLowerCase(),
  boarded_time: (r) => r.boarded_at || '',
  status: (r) => r.display_status || '',
  notes: (r) => `${r.reason_code || ''} ${r.absence_reason || ''} ${r.driver_notes || ''}`.trim().toLowerCase(),
  parent_phone: (r) => r.parent_phone || '',
}

function loadColumnPrefs() {
  try {
    const raw = localStorage.getItem(ATTENDANCE_COL_STORAGE_KEY)
    if (!raw) return { ...ATTENDANCE_COL_DEFAULTS }
    return { ...ATTENDANCE_COL_DEFAULTS, ...JSON.parse(raw) }
  } catch {
    return { ...ATTENDANCE_COL_DEFAULTS }
  }
}

export function useTpAttendanceList(getItems) {
  const filters = reactive({
    q: '',
    noteQ: '',
    boardedFrom: '',
    boardedTo: '',
    displayStatus: '',
  })

  const sort = reactive({ key: 'student', dir: 'asc' })
  const page = ref(1)
  const perPage = ref(10)

  const columnVisible = ref(loadColumnPrefs())
  watch(
    columnVisible,
    (v) => {
      try {
        localStorage.setItem(ATTENDANCE_COL_STORAGE_KEY, JSON.stringify(v))
      } catch {
        /* ignore */
      }
    },
    { deep: true },
  )

  const selectedIds = ref(new Set())

  function colOn(id) {
    if (id === 'student' || id === 'select') return true
    return columnVisible.value[id] !== false
  }

  function setColumn(id, checked) {
    columnVisible.value = { ...columnVisible.value, [id]: checked }
  }

  function toggleSelectAll(visibleRows) {
    const ids = visibleRows.map((r) => r.student_id)
    const allOn = ids.length > 0 && ids.every((id) => selectedIds.value.has(id))
    const next = new Set(selectedIds.value)
    if (allOn) {
      ids.forEach((id) => next.delete(id))
    } else {
      ids.forEach((id) => next.add(id))
    }
    selectedIds.value = next
  }

  function toggleSelectRow(studentId) {
    const next = new Set(selectedIds.value)
    if (next.has(studentId)) next.delete(studentId)
    else next.add(studentId)
    selectedIds.value = next
  }

  function clearSelection() {
    selectedIds.value = new Set()
  }

  const filteredItems = computed(() => {
    let rows = getItems() || []
    const q = filters.q.trim().toLowerCase()
    if (q) {
      rows = rows.filter(
        (r) =>
          r.full_name?.toLowerCase().includes(q) ||
          r.code?.toLowerCase().includes(q) ||
          r.parent_phone?.includes(q) ||
          r.class_name?.toLowerCase().includes(q),
      )
    }
    const noteQ = filters.noteQ.trim().toLowerCase()
    if (noteQ) {
      rows = rows.filter((r) => rowNotesSearchText(r).includes(noteQ))
    }
    if (filters.boardedFrom || filters.boardedTo) {
      const fromHm = filters.boardedFrom || null
      const toHm = filters.boardedTo || null
      rows = rows.filter((r) => matchesBoardedTimeRange(r, fromHm, toHm))
    }
    if (filters.displayStatus) {
      rows = rows.filter((r) => r.display_status === filters.displayStatus)
    }

    const keyFn = SORT_KEYS[sort.key] || SORT_KEYS.student
    const dir = sort.dir === 'desc' ? -1 : 1
    rows = [...rows].sort((a, b) => {
      const av = keyFn(a)
      const bv = keyFn(b)
      if (av < bv) return -1 * dir
      if (av > bv) return 1 * dir
      return 0
    })

    return rows
  })

  const totalFiltered = computed(() => filteredItems.value.length)

  const totalPages = computed(() =>
    Math.max(1, Math.ceil(totalFiltered.value / perPage.value)),
  )

  const pagedItems = computed(() => {
    const start = (page.value - 1) * perPage.value
    return filteredItems.value.slice(start, start + perPage.value)
  })

  watch(
    () => [filters.q, filters.noteQ, filters.boardedFrom, filters.boardedTo, filters.displayStatus, perPage.value, sort.key, sort.dir],
    () => {
      page.value = 1
    },
  )

  watch(totalPages, (tp) => {
    if (page.value > tp) page.value = tp
  })

  const activeFilterCount = computed(() => {
    let n = 0
    if (filters.q.trim()) n++
    if (filters.noteQ.trim()) n++
    if (filters.boardedFrom || filters.boardedTo) n++
    if (filters.displayStatus) n++
    return n
  })

  function resetFilters() {
    filters.q = ''
    filters.noteQ = ''
    filters.boardedFrom = ''
    filters.boardedTo = ''
    filters.displayStatus = ''
  }

  function toggleSort(key) {
    if (sort.key === key) {
      sort.dir = sort.dir === 'asc' ? 'desc' : 'asc'
    } else {
      sort.key = key
      sort.dir = 'asc'
    }
  }

  return {
    filters,
    sort,
    page,
    perPage,
    columnVisible,
    colOn,
    setColumn,
    selectedIds,
    toggleSelectAll,
    toggleSelectRow,
    clearSelection,
    filteredItems,
    pagedItems,
    totalFiltered,
    totalPages,
    activeFilterCount,
    resetFilters,
    toggleSort,
  }
}

export function attendanceStatusLabel(s, t) {
  if (s.display_status === 'present') return t('tp_attendance_page.status_present')
  if (s.display_status === 'excused') return t('tp_attendance_page.status_excused')
  return t('tp_attendance_page.status_unexcused')
}

/** @returns {string|null} HH:mm theo giờ máy người dùng */
export function boardedTimeHm(iso) {
  if (!iso) return null
  try {
    const d = new Date(iso)
    if (Number.isNaN(d.getTime())) return null
    const h = String(d.getHours()).padStart(2, '0')
    const m = String(d.getMinutes()).padStart(2, '0')
    return `${h}:${m}`
  } catch {
    return null
  }
}

function matchesBoardedTimeRange(row, fromHm, toHm) {
  const hm = boardedTimeHm(row.boarded_at)
  if (!hm) return false
  if (fromHm && hm < fromHm) return false
  if (toHm && hm > toHm) return false
  return true
}

/** @returns {string} dd/MM/yyyy */
export function formatViDate(iso) {
  if (!iso) return ''
  const part = String(iso).slice(0, 10)
  const [y, m, d] = part.split('-')
  if (!y || !m || !d) return ''
  return `${d}/${m}/${y}`
}

/** @returns {string} HH:mm */
export function formatViTime(isoOrDatetime) {
  if (!isoOrDatetime) return ''
  const raw = String(isoOrDatetime)
  if (/^\d{1,2}:\d{2}/.test(raw) && raw.length <= 8) {
    return raw.slice(0, 5)
  }
  try {
    const d = new Date(isoOrDatetime)
    if (Number.isNaN(d.getTime())) return ''
    const h = String(d.getHours()).padStart(2, '0')
    const m = String(d.getMinutes()).padStart(2, '0')
    return `${h}:${m}`
  } catch {
    return ''
  }
}

/** @returns {string} dd/MM/yyyy HH:mm */
export function formatViDateTime(isoOrDatetime) {
  if (!isoOrDatetime) return ''
  const d = formatViDate(String(isoOrDatetime).slice(0, 10))
  const t = formatViTime(isoOrDatetime)
  if (d && t) return `${d} ${t}`
  return d || t
}

export function formatBoardedTime(iso) {
  return formatViTime(iso)
}
