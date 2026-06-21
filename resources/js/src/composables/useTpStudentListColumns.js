import { ref, watch } from 'vue'

export const TP_STUDENT_COL_STORAGE_KEY = 'va-tp-student-list-cols-v2'

/** Cột luôn hiển thị: checkbox, STT, Học sinh, Hành động */
export const TP_STUDENT_COL_DEFAULTS = {
  code: false,
  gender: false,
  date_of_birth: false,
  grade: false,
  class_name: true,
  parent_contact: true,
  father: false,
  mother: false,
  address: false,
  pickup_point: true,
  transport_status: true,
  note: false,
}

export const TP_STUDENT_COLUMN_LABELS = {
  code: 'Mã HS',
  gender: 'Giới tính',
  date_of_birth: 'Ngày sinh',
  grade: 'Khối',
  class_name: 'Lớp',
  parent_contact: 'Liên hệ chính',
  father: 'Cha',
  mother: 'Mẹ',
  address: 'Địa chỉ',
  pickup_point: 'Điểm đón',
  transport_status: 'Trạng thái ĐĐ',
  note: 'Ghi chú',
}

function loadColumnPrefs() {
  try {
    const raw = localStorage.getItem(TP_STUDENT_COL_STORAGE_KEY)
    if (!raw) return { ...TP_STUDENT_COL_DEFAULTS }
    return { ...TP_STUDENT_COL_DEFAULTS, ...JSON.parse(raw) }
  } catch {
    return { ...TP_STUDENT_COL_DEFAULTS }
  }
}

export function useTpStudentListColumns() {
  const columnVisible = ref(loadColumnPrefs())

  watch(
    columnVisible,
    (v) => {
      try {
        localStorage.setItem(TP_STUDENT_COL_STORAGE_KEY, JSON.stringify(v))
      } catch {
        /* ignore */
      }
    },
    { deep: true },
  )

  function colOn(id) {
    return columnVisible.value[id] !== false
  }

  function setColumn(id, checked) {
    columnVisible.value = { ...columnVisible.value, [id]: checked }
  }

  const columnToggleOptions = Object.keys(TP_STUDENT_COL_DEFAULTS).map((id) => ({
    id,
    label: TP_STUDENT_COLUMN_LABELS[id] ?? id,
  }))

  return { columnVisible, colOn, setColumn, columnToggleOptions }
}
