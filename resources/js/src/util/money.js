/** Hậu tố hiển thị tiền tệ (fill-price, phiếu). */
export const VND_CURRENCY_SUFFIX = 'VNĐ'

/** Chuẩn hóa số nguyên VNĐ từ API (decimal), number, hoặc chuỗi form (1.500.000). */
export function normalizeMoneyAmount(v) {
  if (v == null || v === '') return 0
  if (typeof v === 'number' && Number.isFinite(v)) return Math.round(v)

  const s = String(v).trim().replace(/\s/g, '')
  const western = s.replace(/,/g, '')
  if (/^\d+\.\d+$/.test(western)) {
    const n = Number(western)
    return Number.isFinite(n) ? Math.round(n) : 0
  }

  const digits = s.replace(/\./g, '').replace(/,/g, '').replace(/\D/g, '')
  if (!digits) return 0
  const n = Number(digits)
  return Number.isFinite(n) ? n : 0
}

/** Parse số tiền từ chuỗi form (VN: có thể có dấu . phân cách nghìn). */
export function parseMoneyVnd(v) {
  return normalizeMoneyAmount(v)
}

/** Hiển thị số tiền VNĐ (không parse). */
export function formatVndCurrency(n, suffix = 'đ') {
  const num = new Intl.NumberFormat('vi-VN').format(Number(n))
  return suffix ? `${num} ${suffix}` : num
}

/** Chỉ giữ chữ số rồi format nhóm nghìn kiểu vi-VN (vd. 1000000 → 1.000.000). */
export function formatVndWhileTyping(raw) {
  const digits = String(raw ?? '').replace(/\D/g, '')
  if (!digits) return ''
  return Number(digits).toLocaleString('vi-VN')
}

/** Giá trị đã lưu (snapshot / API) → chuỗi nhập tay đã nhóm nghìn. */
export function formatMoneyDraftDisplay(v) {
  const n = normalizeMoneyAmount(v)
  if (!n) return ''
  return formatVndWhileTyping(String(n))
}
