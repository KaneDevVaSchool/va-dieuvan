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

const VN_WORDS = ['không', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín']

function readTriple(n, full) {
  const hundred = Math.floor(n / 100)
  const ten = Math.floor((n % 100) / 10)
  const unit = n % 10
  const parts = []

  if (hundred > 0) {
    parts.push(hundred === 1 ? 'một trăm' : `${VN_WORDS[hundred]} trăm`)
  } else if (full && n > 0) {
    parts.push('không trăm')
  }

  if (ten > 1) {
    parts.push(`${VN_WORDS[ten]} mươi`)
    if (unit === 1) parts.push('mốt')
    else if (unit === 5) parts.push('lăm')
    else if (unit > 0) parts.push(VN_WORDS[unit])
  } else if (ten === 1) {
    parts.push('mười')
    if (unit === 5) parts.push('lăm')
    else if (unit > 0) parts.push(VN_WORDS[unit])
  } else if (unit > 0) {
    if (hundred > 0 || full) parts.push('lẻ')
    parts.push(VN_WORDS[unit])
  }

  return parts.join(' ').replace(/\s+/g, ' ').trim()
}

/** Đọc số tiền VNĐ thành chữ (vd. 150000090 → «một trăm năm mươi triệu… đồng»). */
export function vndAmountInWords(amount) {
  let n = Math.round(normalizeMoneyAmount(amount))
  if (n === 0) return 'Không đồng'
  if (n < 0) return ''

  const scales = [
    { v: 1_000_000_000, label: 'tỷ' },
    { v: 1_000_000, label: 'triệu' },
    { v: 1_000, label: 'nghìn' },
  ]

  const chunks = []
  for (const { v, label } of scales) {
    const block = Math.floor(n / v)
    if (block > 0) {
      chunks.push(`${readTriple(block, n >= v)} ${label}`)
      n %= v
    }
  }
  if (n > 0) {
    const tail = readTriple(n, false)
    chunks.push(chunks.length > 0 && n < 1000 ? `lẻ ${tail}` : tail)
  }

  const text = chunks.join(' ').replace(/\s+/g, ' ').trim()
  return `${text.charAt(0).toUpperCase()}${text.slice(1)} đồng`
}
