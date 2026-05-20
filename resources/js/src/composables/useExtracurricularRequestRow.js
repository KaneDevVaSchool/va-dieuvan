/**
 * Shared rules for extracurricular list rows (portal + staff).
 * @param {import('vue').Ref|import('vue').ComputedRef} userRef
 * @param {{ hasPermission: (p: string) => boolean }} auth
 */
export function useExtracurricularRequestRow(auth, userRef) {
  function hoursUntilDepartIso(iso) {
    if (!iso) return null
    try {
      const d = new Date(iso)
      if (Number.isNaN(d.getTime())) return null
      return (d.getTime() - Date.now()) / 3600000
    } catch {
      return null
    }
  }

  function isRequester(req) {
    const u = userRef?.value ?? userRef
    if (!u?.id || req?.requester_id == null) return false
    return Number(u.id) === Number(req.requester_id)
  }

  function isRecurringInstance(req) {
    return req?.dispatch_request_template_id != null
  }

  function planStudentCount(req) {
    const n = req?.passenger_count
    return n != null && n !== '' ? Number(n) : null
  }

  function actualStudentCount(req) {
    const raw = req?.student_count_actual ?? req?.passenger_count
    return Math.max(1, Math.min(999, Math.round(Number(raw) || 1)))
  }

  function showPassengerAdjust(req) {
    if (!isRecurringInstance(req)) return false
    if (auth.hasPermission('trip.view_all')) {
      return ['pending', 'price_filled', 'approved'].includes(String(req?.status || ''))
    }
    return (
      isRequester(req) && ['pending', 'price_filled'].includes(String(req?.status || ''))
    )
  }

  function passengerDepartLocked(req) {
    if (auth.hasPermission('trip.view_all')) return false
    if (!isRequester(req) || !isRecurringInstance(req)) return true
    if (req?.locked_at) return true
    const st = req?.status
    if (st !== 'pending' && st !== 'price_filled') return true
    const h = hoursUntilDepartIso(req?.depart_at)
    return h == null || h < 24
  }

  function canEditStudentCount(req) {
    return showPassengerAdjust(req) && !passengerDepartLocked(req)
  }

  function canCloneReset(req) {
    if (!auth.hasPermission('request.create')) return false
    if (!['approved', 'rejected'].includes(String(req?.status || ''))) return false
    if (auth.hasPermission('trip.view_all')) return true
    return isRequester(req)
  }

  /** Suffix for i18n key `{portal|requests_page}.extracurricular_table.*` */
  function lockHintKey(req) {
    if (!isRecurringInstance(req)) return 'not_recurring'
    if (req?.locked_at && !auth.hasPermission('trip.view_all')) return 'locked'
    const st = req?.status
    if (st !== 'pending' && st !== 'price_filled' && !auth.hasPermission('trip.view_all')) {
      return 'status_locked'
    }
    const h = hoursUntilDepartIso(req?.depart_at)
    if (h != null && h < 24 && !auth.hasPermission('trip.view_all')) {
      return 'depart_soon'
    }
    return ''
  }

  return {
    hoursUntilDepartIso,
    isRecurringInstance,
    planStudentCount,
    actualStudentCount,
    showPassengerAdjust,
    passengerDepartLocked,
    canEditStudentCount,
    canCloneReset,
    lockHintKey,
  }
}
