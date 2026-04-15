import * as XLSX from 'xlsx'

/** Ký tự ô vuông trống / đã tick (giống checkbox trên mẫu in). */
const BOX_EMPTY = '\u2610'
const BOX_CHECKED = '\u2611'

/**
 * Gắn trạng thái tick vào bản sao sheet trước khi xuất HTML (file gốc dùng ảnh Drawing, trình duyệt không hiển thị).
 *
 * @param {object} ws — worksheet SheetJS
 * @param {Record<string, boolean>|null|undefined} checkboxCells — ví dụ { B21: true, B25: false }
 */
function applyCheckboxMarksToWorksheet(ws, checkboxCells) {
  if (!checkboxCells || typeof checkboxCells !== 'object') return
  for (const [rawAddr, checked] of Object.entries(checkboxCells)) {
    const addr = String(rawAddr).replace(/\$/g, '').toUpperCase()
    if (!addr) continue
    const mark = checked ? BOX_CHECKED : BOX_EMPTY
    const cell = { t: 's', v: mark, w: mark }
    ws[addr] = cell
  }
}

/**
 * Xem trước BM.02 dạng bảng Excel (sheet) từ base64 .xlsx.
 *
 * @param {string} excelBase64
 * @param {Record<string, boolean>|null|undefined} excelCheckboxCells
 * @returns {string} HTML fragment (một phần tử gốc chứa `<table>`)
 */
export function buildBm02ExcelSheetPreviewHtml(excelBase64, excelCheckboxCells) {
  if (!excelBase64 || typeof excelBase64 !== 'string') return ''
  const bin = atob(excelBase64)
  const bytes = new Uint8Array(bin.length)
  for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i)
  const wb = XLSX.read(bytes, { type: 'array', cellDates: true })
  const name = wb.SheetNames[0]
  if (!name) return ''
  const ws = wb.Sheets[name]
  if (!ws) return ''

  applyCheckboxMarksToWorksheet(ws, excelCheckboxCells)

  return XLSX.utils.sheet_to_html(ws, { id: 'bm02-excel-preview-table', editable: false })
}
