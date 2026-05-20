/** Một nháp / user để tránh chồng nhiều bản khi đổi tài khoản hoặc lặp khóa cũ. */
export const LEGACY_DRAFT_KEY = 'dispatch-request-wizard-draft-v1'

/** Danh sách meta nhiều bản nháp (JSON `{ items: DraftMeta[] }`). */
export const draftListStorageKey = (userId) => `dispatch-request-wizard-draft-list-v2-u${userId}`

/** Payload đầy đủ một bản nháp. */
export const draftItemStorageKey = (userId, draftId) =>
  `dispatch-request-wizard-draft-item-v2-u${userId}__${draftId}`

/** Id bản nháp đang mở (chuỗi). */
export const draftActiveStorageKey = (userId) => `dispatch-request-wizard-draft-active-v2-u${userId}`

/** Giới hạn số bản nháp lưu trên trình duyệt / user. */
export const MAX_SAVED_DRAFTS = 25

export const E1_WEEKDAY_KEYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']

/** Danh sách đối tượng phân bổ (31 mục) — giữ thứ tự khớp backend khi có xuất biểu mẫu. */
export const TARGET_OPTIONS = [
  'TiH Tân Bình',
  'MN Phú Định',
  'P. Kinh Doanh',
  'Vườn Trường',
  'THCS Tân Bình',
  'TiH-THCS Phú Định',
  'P. Công Nghệ',
  'Ban TA TiHo',
  'MN Bình Thới',
  'MN Vĩnh Hội',
  'BP.CUHC',
  'Ban TA THCS',
  'TiH Bình Thới',
  'MN Thông Tây Hội',
  'P. Kế Toán',
  'Ban TA THPT',
  'THCS Bình Thới',
  'TiH-THCS Thông Tây Hội',
  'P. Mua Hàng',
  'VA - Cần Thơ',
  'THPT VMA',
  'MN Hạnh Thông',
  'P. Đầu Tư',
  'VA - Vũng Tàu',
  'MN Hòa Bình',
  'P.CSVC',
  'Khóa Hè',
  'Viễn Đông',
  'P.HCNS',
  'Tham vấn học đường',
  'Ban Pháp chế (P.CSVC)',
]

export function todayISODate() {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

export function emptyPassengerRow() {
  return {
    depart_at: '',
    pickup: '',
    return_at: '',
    dropoff: '',
    guests: '1',
    unit_price: '',
    extra_fee: '',
    person_in_charge: '',
    notes: '',
  }
}

export function emptyBusinessRow() {
  return {
    depart_at: '',
    pickup: '',
    waypoint: '',
    return_at: '',
    dropoff: '',
    guests: '1',
    unit_price: '',
    extra_fee: '',
    notes: '',
  }
}

export function emptyCargoRow() {
  return {
    name: '',
    qty: '1',
    dimensions: '',
    weight: '',
    item_notes: '',
    pickup_at: '',
    pickup_place: '',
    pickup_contact: '',
    delivery_at: '',
    delivery_place: '',
    delivery_contact: '',
    transport_note: '',
    cost: '',
  }
}

export function createInitialForm() {
  const today = todayISODate()
  return {
    trip_type: 'point_to_point',
    source_channel: 'portal',
    requester_name: '',
    requester_email: '',
    requester_phone: '',
    requester_unit: '',
    purpose: '',
    point_purpose_kind: 'point_to_point',
    proposed_date: today,
    date_needed: today,
    is_urgent: false,
    urgent_reason: '',
    targets: [],
    coordinator_name: '',
    coordinator_email: '',
    coordinator_phone: '',
    multi_day: false,
    recurring_enabled: false,
    recurrence_start_date: '',
    recurrence_depart_time: '07:00',
    recurrence_return_time: '17:00',
    recurrence_end_date: '',
    recurrence_end_mode: 'date',
    recurrence_repeat_count: '',
    cargo_extra_notes: '',
    need_porters: false,
    porter_qty: '',
    porter_cost: '',
    interprovincial: false,
    interprovincial_cost: '',
    e1_use_3plus_days: false,
    e1_from_date: '',
    e1_to_date: '',
    e1_days_total: '',
    e1_extra_cost: '',
    e1_weekdays: {
      mon: false,
      tue: false,
      wed: false,
      thu: false,
      fri: false,
      sat: false,
      sun: false,
    },
    e2_door_pickup: false,
    e2_door_cost: '',
    e2_driver_self: false,
    e2_driver_self_cost: '',
    e2_after_21h: false,
    e2_after_21h_cost: '',
    /** Ghi chú tự do — map vào cột `notes` DB; không gồm nội dung BM.03 tự sinh */
    free_notes: '',
  }
}

/** Điểm đón + trả (CLB định kỳ bước Chi tiết). */
export function isPassengerRouteFilled(r) {
  return !!(r.pickup?.trim() && r.dropoff?.trim())
}

export function isPassengerRowFilled(r) {
  if (r.pickup?.trim() || r.dropoff?.trim() || r.depart_at || r.return_at) return true
  if (r.person_in_charge?.trim() || r.notes?.trim()) return true
  if (r.unit_price && String(r.unit_price).trim() !== '') return true
  if (r.extra_fee && String(r.extra_fee).trim() !== '') return true
  const g = String(r.guests ?? '').trim()
  return !!(g && g !== '1')
}

export function isBusinessRowFilled(r) {
  if (r.pickup?.trim() || r.dropoff?.trim() || r.waypoint?.trim() || r.depart_at || r.return_at) return true
  if (r.unit_price && String(r.unit_price).trim() !== '') return true
  if (r.extra_fee && String(r.extra_fee).trim() !== '') return true
  if (r.notes?.trim()) return true
  const g = String(r.guests ?? '').trim()
  return !!(g && g !== '1')
}

export function isCargoRowFilled(r) {
  if (r.name?.trim()) return true
  return !!(
    r.pickup_place?.trim() ||
    r.delivery_place?.trim() ||
    r.pickup_at ||
    r.delivery_at ||
    r.pickup_contact?.trim() ||
    r.delivery_contact?.trim() ||
    (r.cost && String(r.cost).trim() !== '') ||
    (r.qty && String(r.qty).trim() !== '' && String(r.qty).trim() !== '1') ||
    r.dimensions?.trim() ||
    r.weight?.trim() ||
    r.item_notes?.trim() ||
    r.transport_note?.trim()
  )
}
