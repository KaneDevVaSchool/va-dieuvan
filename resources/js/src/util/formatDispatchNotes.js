function escapeRe(s) {
  return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
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
