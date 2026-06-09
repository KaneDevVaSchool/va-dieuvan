import { saveAs } from 'file-saver'
import { attendanceStatusLabel, formatBoardedTime } from './useTpAttendanceList'

function filenameStamp() {
  const d = new Date()
  const p = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}_${p(d.getHours())}${p(d.getMinutes())}`
}

const COL_DEFS = [
  { id: 'code', headerKey: 'col_code', get: (r) => r.code || '' },
  { id: 'student', headerKey: 'col_student', get: (r) => r.full_name || '' },
  { id: 'class_name', headerKey: 'col_class', get: (r) => r.class_name || '' },
  { id: 'pickup_point', headerKey: 'col_pickup', get: (r) => r.pickup_point || '' },
  { id: 'parent_phone', headerKey: 'col_parent_phone', get: (r) => r.parent_phone || '' },
  { id: 'boarded_time', headerKey: 'col_boarded_time', get: (r) => formatBoardedTime(r.boarded_at) },
  { id: 'status', headerKey: 'col_status', get: (r, t) => attendanceStatusLabel(r, t) },
  {
    id: 'notes',
    headerKey: 'col_notes',
    get: (r, t, reasons) => {
      const reason = reasons.find((x) => x.code === r.reason_code)
      const parts = [reason?.label_vi, r.absence_reason].filter(Boolean)
      return parts.join(' — ') || '—'
    },
  },
]

/**
 * @param {object} opts
 * @param {import('vue-i18n').ComposerTranslation} opts.t
 * @param {array} opts.rows
 * @param {object} opts.columnVisible
 * @param {object} opts.meta - program, date, shift, attendance_status
 * @param {array} opts.reasons
 */
export async function exportAttendanceExcel({ t, rows, columnVisible, meta, reasons = [] }) {
  const ExcelJS = (await import('exceljs')).default
  const wb = new ExcelJS.Workbook()
  wb.creator = 'VA Dispatch'
  const ws = wb.addWorksheet(t('tp_attendance_page.export_sheet'), {
    views: [{ state: 'frozen', ySplit: 4 }],
  })

  ws.mergeCells('A1:H1')
  const title = ws.getCell('A1')
  title.value = t('tp_attendance_page.export_title')
  title.font = { bold: true, size: 14, color: { argb: 'FF0F766E' } }

  ws.mergeCells('A2:H2')
  const sub = ws.getCell('A2')
  sub.value = [
    meta.programName,
    meta.date,
    meta.shiftLabel,
    meta.driverName ? `· ${meta.driverName}` : '',
    `· ${t('tp_attendance_page.export_session')}: ${meta.sessionLabel}`,
  ]
    .filter(Boolean)
    .join(' ')
  sub.font = { size: 11, color: { argb: 'FF475569' } }

  ws.addRow([])

  const visibleCols = COL_DEFS.filter((c) => {
    if (c.id === 'student') return true
    return columnVisible[c.id] !== false
  })

  const headerRow = ws.addRow(visibleCols.map((c) => t(`tp_attendance_page.${c.headerKey}`)))
  headerRow.eachCell((cell) => {
    cell.fill = {
      type: 'pattern',
      pattern: 'solid',
      fgColor: { argb: 'FF0F766E' },
    }
    cell.font = { bold: true, color: { argb: 'FFFFFFFF' } }
    cell.alignment = { vertical: 'middle', horizontal: 'center' }
    cell.border = {
      bottom: { style: 'thin', color: { argb: 'FF0D9488' } },
    }
  })
  headerRow.height = 22

  for (const r of rows) {
    const values = visibleCols.map((c) => c.get(r, t, reasons))
    const row = ws.addRow(values)
    if (r.display_status === 'unexcused') {
      row.eachCell((cell) => {
        cell.fill = {
          type: 'pattern',
          pattern: 'solid',
          fgColor: { argb: 'FFFFF1F2' },
        }
      })
    } else if (r.display_status === 'excused') {
      row.eachCell((cell) => {
        cell.fill = {
          type: 'pattern',
          pattern: 'solid',
          fgColor: { argb: 'FFFFFBEB' },
        }
      })
    }
  }

  ws.autoFilter = {
    from: { row: 4, column: 1 },
    to: { row: 3 + rows.length, column: visibleCols.length },
  }

  visibleCols.forEach((col, i) => {
    const colIdx = i + 1
    let max = String(t(`tp_attendance_page.${col.headerKey}`)).length
    ws.eachRow((row, rowNumber) => {
      if (rowNumber < 4) return
      const v = row.getCell(colIdx).value
      const len = v == null ? 0 : String(v).length
      if (len > max) max = len
    })
    ws.getColumn(colIdx).width = Math.min(48, Math.max(10, max + 2))
  })

  const buffer = await wb.xlsx.writeBuffer()
  saveAs(
    new Blob([buffer], {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    }),
    `Attendance_Report_${filenameStamp()}.xlsx`,
  )
}
