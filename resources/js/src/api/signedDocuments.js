import http from './http'

/**
 * @param {number} dispatchRequestId
 * @param {File} file
 * @param {(pct: number) => void} [onProgress]
 */
export async function uploadStaffSignedDocument(dispatchRequestId, file, onProgress) {
  const fd = new FormData()
  fd.append('file', file)
  const { data } = await http.post(`/dispatch-requests/${dispatchRequestId}/signed-documents`, fd, {
    onUploadProgress: (e) => {
      if (!onProgress || !e.total) return
      onProgress(e.loaded / e.total)
    },
  })
  return data.data
}

/**
 * @param {number} versionId
 */
export async function rerunSignedDocumentOcr(versionId) {
  const { data } = await http.post(`/signed-document-versions/${versionId}/ocr`)
  return data.data
}

/**
 * @param {number} versionId
 * @param {'approve'|'reject'} decision
 * @param {string} [note]
 */
export async function verifySignedDocument(versionId, decision, note) {
  const { data } = await http.post(`/signed-document-versions/${versionId}/verify`, { decision, note })
  return data.data
}

/**
 * @param {number} dispatchRequestId
 */
export async function getStaffSignedDocuments(dispatchRequestId) {
  const { data } = await http.get(`/dispatch-requests/${dispatchRequestId}/signed-documents`, {
    params: { include_history: 1 },
  })
  return data.data
}
