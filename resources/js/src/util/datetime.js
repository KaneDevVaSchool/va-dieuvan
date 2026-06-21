/**
 * Parse datetime-local (YYYY-MM-DDTHH:mm) as local wall time.
 * @param {string | null | undefined} isoLocal
 * @returns {Date | null}
 */
export function parseDatetimeLocalToDate(isoLocal) {
  const m = String(isoLocal ?? '').trim().match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/)
  if (!m) return null
  const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]), Number(m[4]), Number(m[5]), 0, 0)
  return Number.isNaN(d.getTime()) ? null : d
}

/**
 * Hiển thị ngày giờ từ giá trị input datetime-local: DD/MM/YYYY h:mm AM/PM.
 * @param {string | null | undefined} isoLocal
 */
export function formatDatetimeLocalAmPm(isoLocal) {
  const d = parseDatetimeLocalToDate(isoLocal)
  if (!d) return ''
  const day = String(d.getDate()).padStart(2, '0')
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const year = d.getFullYear()
  const h24 = d.getHours()
  const min = String(d.getMinutes()).padStart(2, '0')
  const isAm = h24 < 12
  const h12 = h24 % 12 || 12
  const suf = isAm ? 'AM' : 'PM'
  return `${day}/${month}/${year} ${h12}:${min} ${suf}`
}

/**
 * Ngày từ ISO / datetime API → hiển thị theo locale (không giờ UTC thô).
 * @param {string | null | undefined} iso
 * @param {'vi' | 'en'} [locale]
 */
export function formatIsoDate(iso, locale = 'vi') {
  if (iso == null || iso === '') return '—'
  const s = String(iso)
  const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/)
  let d
  if (m) {
    d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]))
  } else {
    d = new Date(iso)
  }
  if (Number.isNaN(d.getTime())) return '—'
  const loc = locale === 'en' ? 'en-US' : 'vi-VN'
  return d.toLocaleDateString(loc, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

/**
 * Ngày giờ ISO từ API.
 * @param {string | null | undefined} iso
 * @param {'vi' | 'en'} [locale]
 */
export function formatIsoDateTime(iso, locale = 'vi') {
  if (iso == null || iso === '') return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return '—'
  const loc = locale === 'en' ? 'en-US' : 'vi-VN'
  return d.toLocaleString(loc, {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: locale === 'en',
  })
}

/**
 * Hiển thị thống nhất trên danh sách (vd. 15:03 10/06/2026).
 * @param {string | null | undefined} iso
 * @param {'vi' | 'en'} [locale]
 */
export function formatListDateTime(iso, locale = 'vi') {
  if (iso == null || iso === '') return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  const datePart = `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`
  if (locale === 'en') {
    const h12 = d.getHours() % 12 || 12
    const ampm = d.getHours() < 12 ? 'AM' : 'PM'
    return `${h12}:${pad(d.getMinutes())} ${ampm} ${datePart}`
  }
  return `${pad(d.getHours())}:${pad(d.getMinutes())} ${datePart}`
}

/** @param {Date} d */
export function toDatetimeLocalValue(d) {
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

/** Giờ xuất phát tối thiểu cho datetime-local input (BR-001: ≥ 2h), làm tròn lên bước phút. */
export function minDepartDatetimeLocalValue(roundMinutes = 15) {
  const min = Date.now() + 2 * 60 * 60 * 1000
  const roundMs = roundMinutes * 60 * 1000
  const t = Math.ceil(min / roundMs) * roundMs
  return toDatetimeLocalValue(new Date(t))
}

/** Gợi ý mặc định: đáp ứng BR-001, làm tròn lên. */
export function suggestBr001CompliantLocal(roundMinutes = 15) {
  return minDepartDatetimeLocalValue(roundMinutes)
}

/**
 * @param {string} departAtLocal - giá trị datetime-local
 * @param {boolean} isUrgent
 */
export function br001Status(departAtLocal, isUrgent) {
  if (isUrgent) {
    return { kind: 'skipped', message: 'Lệnh gấp: không áp dụng quy tắc trước 2 giờ.' }
  }
  if (!departAtLocal || !String(departAtLocal).trim()) {
    return { kind: 'empty', message: 'Chọn giờ xuất phát để kiểm tra BR-001.' }
  }
  const t = new Date(departAtLocal).getTime()
  if (Number.isNaN(t)) {
    return { kind: 'invalid', message: 'Giờ xuất phát không hợp lệ.' }
  }
  const deadline = Date.now() + 2 * 60 * 60 * 1000
  if (t < deadline) {
    return {
      kind: 'viol',
      message: 'Chưa đủ 2 giờ trước giờ xuất phát. Bật “Lệnh gấp” hoặc đổi giờ.',
    }
  }
  const hoursToDepart = (t - Date.now()) / 3_600_000
  return {
    kind: 'ok',
    message: `Đạt BR-001: khoảng ${hoursToDepart.toFixed(1)} giờ nữa tới giờ xuất phát (tối thiểu phải cách hiện tại 2 giờ).`,
  }
}
