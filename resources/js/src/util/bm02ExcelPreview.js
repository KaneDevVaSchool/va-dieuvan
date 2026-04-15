import * as XLSX from 'xlsx'

const BOX_EMPTY = '\u2610'
const BOX_CHECKED = '\u2611'

function applyCheckboxMarksToWorksheet(ws, checkboxCells) {
  if (!checkboxCells || typeof checkboxCells !== 'object') return
  for (const [rawAddr, checked] of Object.entries(checkboxCells)) {
    const addr = String(rawAddr).replace(/\$/g, '').toUpperCase()
    if (!addr) continue
    const mark = checked ? BOX_CHECKED : BOX_EMPTY
    ws[addr] = { t: 's', v: mark, w: mark }
  }
}

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
  const inner = XLSX.utils.sheet_to_html(ws, {
    id: 'bm02-excel-preview-table',
    editable: false,
    header: '',
    footer: '',
  })

  return (
    '<div class="bm02-excel-sheet-root">' +
    '<style type="text/css">' +
    '.bm02-excel-sheet-root .bm02-excel-preview-table{border-collapse:collapse;table-layout:fixed;width:100%;min-width:max(100%,720px);background:#fff;}' +
    '.bm02-excel-sheet-root .bm02-excel-preview-table td,.bm02-excel-sheet-root .bm02-excel-preview-table th{' +
    'border:1px solid #bfbfbf!important;vertical-align:middle;padding:2px 4px;font-size:11px;line-height:1.25;' +
    '}' +
    '.bm02-excel-sheet-root .bm02-excel-preview-table thead th{background:#e8f0e8;}' +
    '</style>' +
    inner +
    '</div>'
  )
}
