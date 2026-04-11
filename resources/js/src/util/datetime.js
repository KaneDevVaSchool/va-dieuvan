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
