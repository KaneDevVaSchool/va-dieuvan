import { saveAs } from 'file-saver'

const BRAND_HEADER = 'FF115E59'
const BRAND_HEADER_BORDER = 'FF0D9488'
const URGENT_ROW = 'FFFFF1F2'
const PENDING_ROW = 'FFFFFBEB'

function filenameStamp() {
  const d = new Date()
  const p = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}_${p(d.getHours())}${p(d.getMinutes())}`
}

function formatGeneratedAt(date = new Date()) {
  const p = (n) => String(n).padStart(2, '0')
  return `${p(date.getDate())}/${p(date.getMonth() + 1)}/${date.getFullYear()} ${p(date.getHours())}:${p(date.getMinutes())}`
}

/**
 * @param {object} opts
 * @param {import('vue-i18n').ComposerTranslation} opts.t
 * @param {string[]} opts.headers
 * @param {array} opts.rows
 * @param {(row: object) => unknown[]} opts.mapRow
 * @param {(row: object) => { urgent?: boolean; pending?: boolean }} [opts.rowStyle]
 * @param {string[]} opts.filterLines
 * @param {number} opts.totalCount
 */
function columnLetter(colCount) {
  let n = colCount
  let s = ''
  while (n > 0) {
    const r = (n - 1) % 26
    s = String.fromCharCode(65 + r) + s
    n = Math.floor((n - 1) / 26)
  }
  return s || 'A'
}

export async function exportRequestsListExcel({
  t,
  headers,
  rows,
  mapRow,
  rowStyle,
  filterLines,
  totalCount,
}) {
  const ExcelJS = (await import('exceljs')).default
  const colCount = headers.length
  const lastColLetter = columnLetter(colCount)

  const wb = new ExcelJS.Workbook()
  wb.creator = 'VA Dieu Van'
  const ws = wb.addWorksheet(t('requests_page.export_sheet_name'), {
    views: [{ state: 'frozen', ySplit: 6 }],
  })

  ws.mergeCells(`A1:${lastColLetter}1`)
  const title = ws.getCell('A1')
  title.value = t('requests_page.export_title')
  title.font = { bold: true, size: 14, color: { argb: BRAND_HEADER } }
  title.alignment = { vertical: 'middle' }

  ws.mergeCells(`A2:${lastColLetter}2`)
  const meta = ws.getCell('A2')
  meta.value = t('requests_page.export_meta_line', {
    at: formatGeneratedAt(),
    count: totalCount,
  })
  meta.font = { size: 11, color: { argb: 'FF475569' } }
  meta.alignment = { wrapText: true, vertical: 'top' }

  ws.mergeCells(`A3:${lastColLetter}3`)
  const filterCell = ws.getCell('A3')
  const filterBody =
    filterLines.length > 0
      ? filterLines.map((line) => `• ${line}`).join('\n')
      : t('requests_page.export_filters_none')
  filterCell.value = `${t('requests_page.export_filters_heading')}\n${filterBody}`
  filterCell.font = { size: 10, color: { argb: 'FF334155' } }
  filterCell.alignment = { wrapText: true, vertical: 'top' }

  ws.addRow([])
  ws.addRow([])

  const headerRowIndex = 6
  const headerRow = ws.getRow(headerRowIndex)
  headers.forEach((h, i) => {
    headerRow.getCell(i + 1).value = h
  })
  headerRow.height = 24
  headerRow.eachCell((cell) => {
    cell.fill = {
      type: 'pattern',
      pattern: 'solid',
      fgColor: { argb: BRAND_HEADER },
    }
    cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 10 }
    cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true }
    cell.border = {
      bottom: { style: 'thin', color: { argb: BRAND_HEADER_BORDER } },
      top: { style: 'thin', color: { argb: BRAND_HEADER_BORDER } },
      left: { style: 'thin', color: { argb: 'FF0F766E' } },
      right: { style: 'thin', color: { argb: 'FF0F766E' } },
    }
  })

  let dataRowIndex = headerRowIndex
  for (const r of rows) {
    dataRowIndex += 1
    const values = mapRow(r)
    const row = ws.getRow(dataRowIndex)
    values.forEach((v, i) => {
      row.getCell(i + 1).value = v
    })
    const style = rowStyle?.(r) ?? {}
    const fillArgb = style.urgent ? URGENT_ROW : style.pending ? PENDING_ROW : null
    row.eachCell((cell) => {
      cell.alignment = { vertical: 'top', wrapText: true }
      cell.border = {
        bottom: { style: 'hair', color: { argb: 'FFE2E8F0' } },
        left: { style: 'hair', color: { argb: 'FFE2E8F0' } },
        right: { style: 'hair', color: { argb: 'FFE2E8F0' } },
      }
      if (fillArgb) {
        cell.fill = {
          type: 'pattern',
          pattern: 'solid',
          fgColor: { argb: fillArgb },
        }
      }
    })
  }

  if (rows.length > 0) {
    ws.autoFilter = {
      from: { row: headerRowIndex, column: 1 },
      to: { row: dataRowIndex, column: headers.length },
    }
  }

  headers.forEach((h, i) => {
    const colIdx = i + 1
    let max = String(h).length
    ws.eachRow((row, rowNumber) => {
      if (rowNumber < headerRowIndex) return
      const v = row.getCell(colIdx).value
      const len = v == null ? 0 : String(v).length
      if (len > max) max = Math.min(len, 80)
    })
    ws.getColumn(colIdx).width = Math.min(52, Math.max(11, max + 2))
  })

  ws.getRow(1).height = 28
  ws.getRow(3).height = Math.min(120, 18 + filterLines.length * 14)

  const buffer = await wb.xlsx.writeBuffer()
  saveAs(
    new Blob([buffer], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }),
    `${t('requests_page.export_filename_prefix')}_${filenameStamp()}.xlsx`,
  )
}
