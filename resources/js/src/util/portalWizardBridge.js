import {
  LEGACY_DRAFT_KEY,
  createInitialForm,
  draftActiveStorageKey,
  draftItemStorageKey,
  draftListStorageKey,
  todayISODate,
} from '../composables/dispatchWizardConstants'

/**
 * Giá trị mặc định portal /portal/new — cùng nguồn với DispatchRequestCreateView (createInitialForm).
 * @returns {{ tripType: string, infoModel: Record<string, unknown> }}
 */
export function buildPortalCreateDefaultsFromWizardForm() {
  const f = createInitialForm()
  return mapWizardFormToPortalState(f, { passengerRows: [], businessRows: [], cargoRows: [] })
}

/**
 * Map form + dòng BM.03 trong nháp wizard → model portal (bước thông tin).
 * @param {object} form
 * @param {{ passengerRows?: object[], businessRows?: object[], cargoRows?: object[] }} rows
 */
export function mapWizardFormToPortalState(form, rows) {
  const tripType = form.trip_type || 'point_to_point'
  const passengerRows = Array.isArray(rows.passengerRows) ? rows.passengerRows : []
  const businessRows = Array.isArray(rows.businessRows) ? rows.businessRows : []
  const cargoRows = Array.isArray(rows.cargoRows) ? rows.cargoRows : []

  const pr0 = passengerRows[0] || {}
  const br0 = businessRows[0] || {}
  const cr0 = cargoRows[0] || {}

  let origin = ''
  let destination = ''
  if (tripType === 'cargo') {
    origin = String(cr0.pickup_place ?? '').trim()
    destination = String(cr0.delivery_place ?? '').trim()
  } else if (tripType === 'business') {
    origin = String(br0.pickup ?? '').trim()
    destination = String(br0.dropoff ?? '').trim()
  } else {
    origin = String(pr0.pickup ?? '').trim()
    destination = String(pr0.dropoff ?? '').trim()
  }

  let departAtLocal = ''
  if (tripType === 'cargo' && String(cr0.pickup_at ?? '').trim()) {
    departAtLocal = normalizeDatetimeLocalValue(String(cr0.pickup_at).trim())
  } else if (tripType === 'business' && String(br0.depart_at ?? '').trim()) {
    departAtLocal = normalizeDatetimeLocalValue(String(br0.depart_at).trim())
  } else if (String(pr0.depart_at ?? '').trim()) {
    departAtLocal = normalizeDatetimeLocalValue(String(pr0.depart_at).trim())
  } else {
    const d = form.date_needed || form.proposed_date || todayISODate()
    departAtLocal = d ? `${d}T00:00` : ''
  }

  let arriveByLocal = ''
  const retP = String(pr0.return_at ?? '').trim()
  if (retP) arriveByLocal = normalizeDatetimeLocalValue(retP)
  else if (tripType === 'business' && String(br0.return_at ?? '').trim()) {
    arriveByLocal = normalizeDatetimeLocalValue(String(br0.return_at).trim())
  } else if (tripType === 'cargo' && String(cr0.delivery_at ?? '').trim()) {
    arriveByLocal = normalizeDatetimeLocalValue(String(cr0.delivery_at).trim())
  }

  const purpose = String(form.purpose ?? '').trim()
  const notesParts = [form.free_notes].map((x) => String(x ?? '').trim()).filter(Boolean)
  const notes = notesParts.join('\n\n')

  let passengerCount = null
  if (tripType !== 'cargo') {
    const g = parseInt(String(pr0.guests ?? ''), 10)
    if (Number.isFinite(g) && g >= 1) passengerCount = g
  }

  const infoModel = {
    origin,
    destination,
    departAtLocal,
    arriveByLocal,
    passengerCount,
    purpose,
    notes,
    isUrgent: !!form.is_urgent,
    urgentReason: String(form.urgent_reason ?? '').trim(),
  }

  return { tripType, infoModel }
}

/**
 * Chuẩn hóa chuỗi datetime-local (YYYY-MM-DDTHH:mm).
 * @param {string} raw
 */
function normalizeDatetimeLocalValue(raw) {
  const t = String(raw).trim()
  if (!t) return ''
  const m = t.match(/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2})/)
  return m ? m[1] : t
}

function tryParseJson(raw) {
  try {
    return JSON.parse(raw)
  } catch {
    return null
  }
}

function readDraftListMeta(uid) {
  try {
    const raw = localStorage.getItem(draftListStorageKey(uid))
    if (!raw) return []
    const parsed = tryParseJson(raw)
    return Array.isArray(parsed?.items) ? parsed.items : []
  } catch {
    return []
  }
}

/**
 * Đọc payload nháp wizard (read-only, không migrate) — cùng khóa với useDispatchRequestWizard.
 * @param {number|string|null|undefined} userId
 * @returns {{ form?: object, passengerRows?: object[], businessRows?: object[], cargoRows?: object[] } | null}
 */
export function loadDispatchWizardDraftFromStorage(userId) {
  if (typeof localStorage === 'undefined') return null
  try {
    if (userId != null) {
      const activeId = localStorage.getItem(draftActiveStorageKey(userId))
      if (activeId) {
        const raw = localStorage.getItem(draftItemStorageKey(userId, activeId))
        const data = tryParseJson(raw)
        if (data?.form) return data
      }
      const items = readDraftListMeta(userId)
      if (items.length) {
        const sorted = [...items].sort((a, b) => b.savedAt - a.savedAt)
        const raw = localStorage.getItem(draftItemStorageKey(userId, sorted[0].id))
        const data = tryParseJson(raw)
        if (data?.form) return data
      }
      const legacyKey = `${LEGACY_DRAFT_KEY}-u${userId}`
      const rawLegacy = localStorage.getItem(legacyKey)
      const legacy = tryParseJson(rawLegacy)
      if (legacy?.form) return legacy
    }
    const rawV1 = localStorage.getItem(LEGACY_DRAFT_KEY)
    const v1 = tryParseJson(rawV1)
    if (v1?.form) return v1
  } catch {
    /* ignore */
  }
  return null
}
