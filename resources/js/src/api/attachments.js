import { http } from './http'

/**
 * @param {object} opts
 * @param {'trip'|'cargo_shipment'|'trip_cost'|'dispatch_request'|'driver_compliance_document'} opts.attachable_type
 * @param {number} opts.attachable_id
 * @param {string} [opts.kind]
 * @param {File} opts.file
 * @param {(pct: number) => void} [opts.onProgress] 0..1
 */
export async function uploadAttachment({ attachable_type, attachable_id, kind, file, onProgress }) {
  const fd = new FormData()
  fd.append('attachable_type', attachable_type)
  fd.append('attachable_id', String(attachable_id))
  if (kind) fd.append('kind', kind)
  fd.append('file', file)

  const { data } = await http.post('/attachments', fd, {
    onUploadProgress: (e) => {
      if (!onProgress || !e.total) return
      onProgress(e.loaded / e.total)
    },
  })
  return data.data
}

/**
 * @param {number} cargoShipmentId
 * @param {File} file
 * @param {(pct: number) => void} [onProgress]
 */
export async function runAttachmentOcr(attachmentId) {
  const { data } = await http.post(`/attachments/${attachmentId}/ocr`)
  return data.data
}

export async function uploadCargoPod(cargoShipmentId, file, onProgress) {
  const fd = new FormData()
  fd.append('file', file)
  const { data } = await http.post(`/cargo-shipments/${cargoShipmentId}/pod`, fd, {
    onUploadProgress: (e) => {
      if (!onProgress || !e.total) return
      onProgress(e.loaded / e.total)
    },
  })
  return data.data
}
