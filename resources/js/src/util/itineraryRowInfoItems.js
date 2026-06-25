import { formatItineraryRowDt, itineraryRowEndpoints, nz } from './requestItineraryRowDisplay'

/**
 * @param {'cargo'|'business'|'passenger'} tripType
 * @returns {Array<{ key: string, label: string, primary: string, secondary: string, iconKey: string, tone: string }>}
 */
export function itineraryRowInfoItems(row, { tripType, t }) {
  const items = []
  const { from, to } = itineraryRowEndpoints(row)

  if (tripType === 'cargo') {
    const pickupAt = formatItineraryRowDt(row.pickup_at)
    const deliveryAt = formatItineraryRowDt(row.delivery_at)
    if (pickupAt || from) {
      items.push({
        key: 'pickup',
        label: t('request_detail.ops_lbl_pickup_time'),
        primary: pickupAt || '—',
        secondary: from || '',
        iconKey: 'clock',
        tone: 'emerald',
      })
    }
    if (deliveryAt || to) {
      items.push({
        key: 'delivery',
        label: t('request_detail.ops_lbl_delivery_time'),
        primary: deliveryAt || '—',
        secondary: to || '',
        iconKey: 'arrow-right',
        tone: 'rose',
      })
    }
    const qty = nz(row.qty)
    const weight = nz(row.weight)
    if (qty || weight) {
      items.push({
        key: 'load',
        label: t('request_detail.ops_lbl_qty'),
        primary: qty || '—',
        secondary: weight ? `${t('request_detail.ops_lbl_weight')}: ${weight}` : '',
        iconKey: 'scale',
        tone: 'amber',
      })
    }
    return items
  }

  const outTime = formatItineraryRowDt(row.depart_at)
  const backTime = formatItineraryRowDt(row.return_at)
  if (outTime || from) {
    items.push({
      key: 'out',
      label: t('request_detail.ops_lbl_depart_time'),
      primary: outTime || '—',
      secondary: from || '',
      iconKey: 'clock',
      tone: 'emerald',
    })
  }
  if (backTime || to) {
    items.push({
      key: 'back',
      label: t('request_detail.ops_lbl_return_time'),
      primary: backTime || '—',
      secondary: to || '',
      iconKey: 'arrow-uturn',
      tone: 'rose',
    })
  }
  const waypoint = nz(row.waypoint)
  if (waypoint && tripType === 'business') {
    items.push({
      key: 'waypoint',
      label: t('request_detail.ops_lbl_waypoint'),
      primary: waypoint,
      secondary: '',
      iconKey: 'map-pin',
      tone: 'amber',
    })
  }
  const guests = nz(row.guests)
  if (guests) {
    items.push({
      key: 'guests',
      label: t('request_detail.ops_lbl_guests'),
      primary: t('request_detail.ops_row_heading_guests', { n: guests }),
      secondary: '',
      iconKey: 'user-group',
      tone: 'sky',
    })
  }
  const pic = nz(row.person_in_charge)
  const picPhone = nz(row.person_in_charge_phone)
  if ((pic || picPhone) && tripType === 'passenger') {
    items.push({
      key: 'pic',
      label: t('request_detail.ops_lbl_person_in_charge'),
      primary: pic || picPhone,
      secondary: pic && picPhone ? picPhone : '',
      iconKey: 'user',
      tone: 'violet',
    })
  }
  return items
}
