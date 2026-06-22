/** @param {string|null|undefined} raw */
export function normalizeContactPhone(raw) {
  const digits = String(raw ?? '').replace(/\D/g, '')
  return digits || null
}

/**
 * @param {string|null|undefined} name
 * @param {string|null|undefined} phone
 */
export function formatCargoPartyLine(name, phone) {
  const n = String(name ?? '').trim()
  const p = String(phone ?? '').trim()
  if (n && p) return `${n} · ${p}`
  return n || p || ''
}

/**
 * @param {Record<string, unknown>|null|undefined} row
 */
export function cargoPartiesFromRow(row) {
  if (!row || typeof row !== 'object') {
    return {
      senderName: '',
      senderPhone: null,
      receiverName: '',
      receiverPhone: null,
      senderLine: '',
      receiverLine: '',
    }
  }
  const senderName = String(row.pickup_contact ?? '').trim()
  const receiverName = String(row.delivery_contact ?? '').trim()
  const senderPhone = normalizeContactPhone(row.pickup_contact_phone)
  const receiverPhone = normalizeContactPhone(row.delivery_contact_phone)
  return {
    senderName,
    senderPhone,
    receiverName,
    receiverPhone,
    senderLine: formatCargoPartyLine(senderName, row.pickup_contact_phone),
    receiverLine: formatCargoPartyLine(receiverName, row.delivery_contact_phone),
  }
}

/**
 * Gộp tên người gửi/nhận từ phiếu cargo_shipment với snapshot wizard khi DB thiếu.
 *
 * @param {Record<string, unknown>|null|undefined} shipment
 * @param {Record<string, unknown>|null|undefined} snapRow
 */
export function mergeCargoShipmentWithSnapshot(shipment, snapRow) {
  if (!shipment) return null
  const parties = cargoPartiesFromRow(snapRow)
  const senderFromDb = String(shipment.sender_name ?? '').trim()
  const receiverFromDb = String(shipment.receiver_name ?? '').trim()
  return {
    ...shipment,
    sender_name: senderFromDb || parties.senderName || null,
    receiver_name: receiverFromDb || parties.receiverName || null,
    sender_phone: parties.senderPhone,
    receiver_phone: parties.receiverPhone,
  }
}
