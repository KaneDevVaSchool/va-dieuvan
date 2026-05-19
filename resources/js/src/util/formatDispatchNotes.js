function escapeRe(s) {
  return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}

/** Bản ghi cũ: toàn bộ BM.03 được lưu trong `notes`. */
export function isLegacyBm03NotesBlock(s) {
  return !!(s && typeof s === 'string' && /===\s*ĐỀ NGHỊ ĐIỀU VẬN/i.test(s))
}

/**
 * Chuẩn hoá ghi chú BM.03 để hiển thị: giữ xuống dòng; nếu ít ký tự xuống dòng, chèn ngắt trước các mục quen thuộc.
 * @param {string|null|undefined} raw
 */
export function formatDispatchRequestNotesForDisplay(raw) {
  let s = String(raw ?? '').replace(/\r\n/g, '\n').trim()
  if (!s) return ''

  s = s.replace(/(Không cần lưu phiếu)\s*(===)/i, '$1\n\n$2')
  s = s.replace(/([^\n])\s*(===\s)/g, '$1\n\n$2')
  s = s.replace(/^\s*(===\s)/, '$1')

  const newlineCount = (s.match(/\n/g) || []).length
  if (newlineCount >= 4) return s.replace(/\n{3,}/g, '\n\n').trim()

  const sections = [
    'Nội dung đề xuất cho chương trình / sự kiện ngoại khóa',
    'Nội dung đề xuất cho nhân sự đi công tác',
    'e.1.1 Ghi chú khác đề xuất',
    'Đối tượng / điều phối',
    'Nội dung đề nghị vận chuyển',
    'Mục đích sử dụng',
    'Người đề nghị',
    'Thời gian',
    'Nội dung chi tiết',
    'Ghi chú khác (công tác)',
    '--- Hệ thống:',
  ]

  for (const title of sections) {
    const re = new RegExp(`([^\\n])(${escapeRe(title)})`, 'g')
    s = s.replace(re, '$1\n\n$2')
  }

  return s.replace(/\n{3,}/g, '\n\n').trim()
}

/** Tiêu đề mục do BM.03/MH.QT.04 sinh ra (đồng bộ với buildBm03BodyFromSnapshot). */
const BM03_KNOWN_SECTION_HEADERS = new Set([
  'Người đề nghị',
  'Mục đích sử dụng',
  'Thời gian',
  'Đối tượng / điều phối',
  'Nội dung đề nghị vận chuyển',
  'Nội dung đề xuất cho chương trình / sự kiện ngoại khóa',
  'Nội dung đề xuất cho nhân sự đi công tác',
  'e.1.1 Ghi chú khác đề xuất',
  'Nội dung chi tiết',
  'Ghi chú khác (công tác)',
])

/**
 * Tách nội dung đã format thành các mục để hiển thị dạng thẻ (BM.03 + ghi chú thêm).
 * @param {string|null|undefined} formattedText — nên dùng output sau formatDispatchRequestNotesForDisplay
 * @returns {{ banner: string | null, sections: Array<{ title: string, content: string }> }}
 */
export function splitDispatchRequestNotesVisualSections(formattedText) {
  let text = String(formattedText ?? '').replace(/\r\n/g, '\n').trim()
  if (!text) {
    return { banner: null, sections: [] }
  }

  let appendix = ''
  const appendixRe = /\n\n—{2,}\n\nGhi chú thêm:\n\n/
  const appendixIdx = text.search(appendixRe)
  if (appendixIdx >= 0) {
    appendix = text.slice(appendixIdx).replace(appendixRe, '').trim()
    text = text.slice(0, appendixIdx).trim()
  }

  const mainPart = text
  const lines = mainPart.split('\n')
  let banner = null
  /** @type {Array<{ title: string, content: string }>} */
  const sections = []
  let title = 'Chi tiết đề nghị'
  /** @type {string[]} */
  let buf = []

  const flush = () => {
    const content = buf.join('\n').trim()
    buf = []
    if (!content) return
    sections.push({ title, content })
    title = 'Chi tiết đề nghị'
  }

  for (const raw of lines) {
    const t = raw.trim()
    if (/^=+$/.test(t)) continue

    const eqLine = t.match(/^=+\s*(.+?)\s*=+\s*$/)
    if (eqLine && eqLine[1]) {
      flush()
      banner = eqLine[1].trim()
      continue
    }

    if (BM03_KNOWN_SECTION_HEADERS.has(t)) {
      flush()
      title = t
      continue
    }

    if (/^---\s*Hệ thống/i.test(t)) {
      flush()
      title = 'Hệ thống'
      buf.push(raw)
      continue
    }

    buf.push(raw)
  }
  flush()

  if (sections.length === 0 && mainPart.trim()) {
    sections.push({ title: 'Chi tiết đề nghị', content: mainPart.trim() })
  }

  if (appendix) {
    sections.push({ title: 'Ghi chú thêm', content: appendix })
  }

  return { banner, sections }
}
