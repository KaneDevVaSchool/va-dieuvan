import { computed, unref } from 'vue'
import { TARGET_OPTIONS } from './dispatchWizardConstants'
import { labelTripType } from '../util/labels'

export function nz(v) {
  const s = v == null ? '' : String(v).trim()
  return s === '' ? '' : s
}

export function fmtDateVi(v) {
  if (!v) return '—'
  const s = String(v).trim()
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
    const [y, m, d] = s.slice(0, 10).split('-')
    return `${d}/${m}/${y}`
  }
  try {
    const dt = new Date(s)
    if (!Number.isNaN(dt.getTime())) {
      const dd = String(dt.getDate()).padStart(2, '0')
      const mm = String(dt.getMonth() + 1).padStart(2, '0')
      const yy = dt.getFullYear()
      return `${dd}/${mm}/${yy}`
    }
  } catch {
    /* ignore */
  }
  return s || '—'
}

/**
 * Read-only BM.03 presentation fields from a dispatch request.
 * @param {import('vue').Ref|import('vue').ComputedRef} reqRef
 */
export function useBm03PresentFromRequest(reqRef) {
  const form = computed(() => unref(reqRef)?.wizard_snapshot?.form ?? {})

  const tripSubtitle = computed(() => {
    const tripType = unref(reqRef)?.trip_type
    if (tripType === 'cargo') return '(Điều chuyển Hàng hóa)'
    if (tripType === 'business') return '(Công tác)'
    if (tripType === 'point_to_point') return '(Vận chuyển Điểm — Điểm)'
    return '(Đưa đón tận nơi)'
  })

  const tripTypeLabel = computed(() => labelTripType(unref(reqRef)?.trip_type))

  const aName = computed(
    () => nz(form.value.requester_name) || nz(unref(reqRef)?.requester?.name) || '—',
  )
  const aEmail = computed(
    () => nz(form.value.requester_email) || nz(unref(reqRef)?.requester?.email) || '—',
  )
  const aPhone = computed(
    () => nz(form.value.requester_phone) || nz(unref(reqRef)?.requester?.phone) || '—',
  )
  const aUnit = computed(() => nz(form.value.requester_unit) || '—')

  const purposeDisplay = computed(() => nz(form.value.purpose) || '—')
  const basisDisplay = computed(() => {
    const ref = nz(form.value.basis_ref)
    if (ref) return ref
    const bf = nz(form.value.basisFileName)
    return bf ? `Đính kèm tệp «${bf}»` : '—'
  })

  const targetsSet = computed(() => {
    const raw = Array.isArray(form.value.targets) ? form.value.targets : []
    return new Set(raw.map((x) => String(x).trim()).filter(Boolean))
  })

  return {
    TARGET_OPTIONS,
    form,
    tripSubtitle,
    tripTypeLabel,
    aName,
    aEmail,
    aPhone,
    aUnit,
    purposeDisplay,
    basisDisplay,
    proposedDateRaw: computed(() => form.value.proposed_date),
    dateNeededRaw: computed(() => form.value.date_needed),
    isUrgent: computed(() => !!form.value.is_urgent),
    urgentReason: computed(() => nz(form.value.urgent_reason) || '—'),
    targetsSet,
    coordinatorName: computed(() => nz(form.value.coordinator_name) || '—'),
    coordinatorEmail: computed(() => nz(form.value.coordinator_email) || '—'),
    coordinatorPhone: computed(() => nz(form.value.coordinator_phone) || '—'),
  }
}
