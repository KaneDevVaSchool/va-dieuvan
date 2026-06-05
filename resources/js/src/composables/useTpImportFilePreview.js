/**
 * Xem trước nội dung file import (sheet đầu) trước khi tải lên server.
 * @returns {Promise<{ headers: string[], rows: string[][], totalRows: number, note: string | null }>}
 */
export async function parseImportFilePreview(file, maxPreviewRows = 8) {
  if (!file) {
    return { headers: [], rows: [], totalRows: 0, note: null }
  }

  const name = (file.name || '').toLowerCase()
  if (name.endsWith('.csv')) {
    return parseCsvPreview(file, maxPreviewRows)
  }
  if (name.endsWith('.xlsx')) {
    return parseXlsxPreview(file, maxPreviewRows)
  }
  if (name.endsWith('.xls')) {
    return {
      headers: [],
      rows: [],
      totalRows: 0,
      note: 'File .xls: không xem trước trên trình duyệt — bạn vẫn có thể tải lên bình thường.',
    }
  }

  return {
    headers: [],
    rows: [],
    totalRows: 0,
    note: 'Định dạng không hỗ trợ xem trước. Chỉ chấp nhận .xlsx, .xls, .csv.',
  }
}

async function parseCsvPreview(file, maxPreviewRows) {
  const text = await file.text()
  const lines = text.replace(/^\uFEFF/, '').split(/\r?\n/).filter((l) => l.trim() !== '')
  if (lines.length === 0) {
    return { headers: [], rows: [], totalRows: 0, note: 'File CSV trống.' }
  }

  const parsed = lines.map(parseCsvLine)
  const headers = (parsed[0] || []).map((c) => String(c ?? '').trim())
  const dataRows = parsed.slice(1)
  const preview = dataRows.slice(0, maxPreviewRows).map((r) => padRow(r, headers.length))

  return {
    headers,
    rows: preview,
    totalRows: dataRows.length,
    note: null,
  }
}

function parseCsvLine(line) {
  const out = []
  let cur = ''
  let inQuotes = false
  for (let i = 0; i < line.length; i++) {
    const ch = line[i]
    if (ch === '"') {
      if (inQuotes && line[i + 1] === '"') {
        cur += '"'
        i++
      } else {
        inQuotes = !inQuotes
      }
    } else if ((ch === ',' && !inQuotes) || ch === ';') {
      out.push(cur)
      cur = ''
      if (ch === ';') break
    } else {
      cur += ch
    }
  }
  out.push(cur)
  return out
}

function padRow(row, len) {
  const cells = [...row]
  while (cells.length < len) cells.push('')
  return cells.slice(0, len).map((c) => (c == null ? '' : String(c)))
}

async function parseXlsxPreview(file, maxPreviewRows) {
  const readXlsxFile = (await import('read-excel-file')).default
  const matrix = await readXlsxFile(file, { sheet: 1 })
  if (!matrix.length) {
    return { headers: [], rows: [], totalRows: 0, note: 'Sheet đầu tiên trống.' }
  }

  const headers = (matrix[0] || []).map((c) => (c == null ? '' : String(c).trim()))
  const dataRows = matrix.slice(1).filter((r) => r.some((c) => c != null && String(c).trim() !== ''))
  const preview = dataRows.slice(0, maxPreviewRows).map((r) => padRow(r, headers.length))

  return {
    headers,
    rows: preview,
    totalRows: dataRows.length,
    note: null,
  }
}
