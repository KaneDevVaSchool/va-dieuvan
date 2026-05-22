/** Parse số tiền từ chuỗi form (VN: có thể có dấu . phân cách nghìn). */
export function parseMoneyVnd(v) {
  const s = String(v ?? '')
    .replace(/\./g, '')
    .replace(/\s/g, '')
    .replace(/,/g, '')
  const n = Number(s)
  return Number.isFinite(n) ? n : 0
}

/** Chỉ giữ chữ số rồi format nhóm nghìn kiểu vi-VN (vd. 1000000 → 1.000.000). */
/** Hiển thị số tiền VNĐ (không parse). */
export function formatVndCurrency(n, suffix = 'đ') {
  const num = new Intl.NumberFormat('vi-VN').format(Number(n))
  return suffix ? `${num} ${suffix}` : num
}

export function formatVndWhileTyping(raw) {
  const digits = String(raw ?? '').replace(/\D/g, '')
  if (!digits) return ''
  return Number(digits).toLocaleString('vi-VN')
}
