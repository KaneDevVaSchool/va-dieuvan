/** Parse số tiền từ chuỗi form (VN: có thể có dấu . phân cách nghìn). */
export function parseMoneyVnd(v) {
  const s = String(v ?? '')
    .replace(/\./g, '')
    .replace(/\s/g, '')
    .replace(/,/g, '')
  const n = Number(s)
  return Number.isFinite(n) ? n : 0
}
