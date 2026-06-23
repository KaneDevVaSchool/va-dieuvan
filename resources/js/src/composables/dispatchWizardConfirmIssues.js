import { extractApiValidationMessages } from '../api/http'
import { dispatchScheduleRowErrors } from './dispatchScheduleRowErrors'
import {
  isPassengerRowFilled,
  isPassengerRouteFilled,
  isBusinessRowFilled,
  isCargoRowFilled,
  showCoordinatorPanelForTripType,
} from './dispatchWizardConstants'

/** @typedef {'general' | 'purpose' | 'attach' | 'schedule'} ConfirmSection */

/** @typedef {{ section: ConfirmSection, message: string }} ConfirmIssue */

const SECTION_ORDER = /** @type {ConfirmSection[]} */ (['general', 'purpose', 'attach', 'schedule'])

/**
 * @param {ConfirmIssue[]} issues
 * @param {Record<ConfirmSection, string>} sectionTitles
 */
export function groupConfirmIssues(issues, sectionTitles) {
  const map = new Map()
  for (const sec of SECTION_ORDER) {
    map.set(sec, [])
  }
  for (const item of issues) {
    const list = map.get(item.section) || []
    list.push(item.message)
    map.set(item.section, list)
  }
  return SECTION_ORDER.filter((sec) => (map.get(sec)?.length ?? 0) > 0).map((section) => ({
    section,
    title: sectionTitles[section] || section,
    messages: map.get(section) || [],
  }))
}

function pushConfirmRowIssues(issues, rows, variant, sectionKey, t, options = {}) {
  const wantsRecurring = !!options.wantsRecurringTemplate
  rows.forEach((row, idx) => {
    const filled =
      variant === 'cargo'
        ? isCargoRowFilled(row)
        : variant === 'business'
          ? isBusinessRowFilled(row)
          : wantsRecurring
            ? isPassengerRouteFilled(row)
            : isPassengerRowFilled(row)
    if (!filled) return
    const err = dispatchScheduleRowErrors(
      row,
      variant,
      wantsRecurring && variant === 'passenger' ? { timesFromRecurringTemplate: true } : undefined,
    )
    const n = idx + 1
    const sectionLabel = t(sectionKey)
    if (err.time_required) {
      issues.push({
        section: 'schedule',
        message: t('dispatch_wizard.confirm.issue_row_time_required', { section: sectionLabel, n }),
      })
    }
    if (err.outbound_place) {
      issues.push({
        section: 'schedule',
        message: t('dispatch_wizard.confirm.issue_row_outbound_place', { section: sectionLabel, n }),
      })
    }
    if (err.pickup_place) {
      issues.push({
        section: 'schedule',
        message: t('dispatch_wizard.confirm.issue_row_pickup_place', { section: sectionLabel, n }),
      })
    }
    if (err.return_time_required) {
      issues.push({
        section: 'schedule',
        message: t('dispatch_wizard.confirm.issue_row_return_time', { section: sectionLabel, n }),
      })
    }
    if (err.return_place) {
      issues.push({
        section: 'schedule',
        message: t('dispatch_wizard.confirm.issue_row_return_place', { section: sectionLabel, n }),
      })
    }
    if (err.return_time) {
      issues.push({
        section: 'schedule',
        message: t('dispatch_wizard.confirm.issue_row_return_order', { section: sectionLabel, n }),
      })
    }
    if (err.passengers) {
      issues.push({
        section: 'schedule',
        message: t('dispatch_wizard.confirm.issue_row_guests', { section: sectionLabel, n }),
      })
    }
  })
}

/**
 * @param {object} ctx
 * @param {import('vue').Ref|object} ctx.form
 * @param {Function} ctx.t
 * @param {boolean} ctx.isCargo
 * @param {boolean} ctx.isPortal
 * @param {Array} ctx.passengerRows
 * @param {Array} ctx.businessRows
 * @param {Array} ctx.cargoRows
 * @param {boolean} ctx.coordinatorEmailFormatInvalid
 * @param {boolean} ctx.step2DateOrderInvalid
 * @param {boolean} ctx.detailStepSchedulesValid
 * @param {string} ctx.computedDepartAt
 * @param {Function} ctx.isPlausibleEmail
 */
export function buildConfirmReviewIssues(ctx) {
  /** @type {ConfirmIssue[]} */
  const issues = []
  const f = ctx.form?.value ?? ctx.form
  const t = ctx.t

  const add = (/** @type {ConfirmSection} */ section, message) => {
    if (message) issues.push({ section, message })
  }

  if (!f.trip_type) add('general', t('dispatch_wizard.validate.pick_type'))
  if (!f.requester_name?.trim()) add('general', t('dispatch_wizard.confirm.issue_requester_name'))
  if (!f.requester_email?.trim()) add('general', t('dispatch_wizard.confirm.issue_requester_email'))
  else if (!ctx.isPlausibleEmail(f.requester_email)) add('general', t('dispatch_wizard.confirm.issue_requester_email'))
  if (
    ctx.coordinatorEmailFormatInvalid &&
    showCoordinatorPanelForTripType(f.trip_type)
  ) {
    add('general', t('dispatch_wizard.validate.coord_email'))
  }
  if (!f.purpose?.trim()) add('purpose', t('dispatch_wizard.confirm.issue_purpose'))
  if (!ctx.wantsRecurringTemplate) {
    if (!f.proposed_date || !f.date_needed) add('general', t('dispatch_wizard.confirm.issue_dates'))
    if (ctx.step2DateOrderInvalid) add('general', t('dispatch_wizard.validate.date_order'))
  }
  if (f.is_urgent && !f.urgent_reason?.trim()) add('general', t('dispatch_wizard.validate.urgent_reason'))
  if (ctx.portalNeedsDeptHead && !String(f.dept_head_user_id ?? '').trim()) {
    add('general', t('request_detail.assign_dept_head_required'))
  }

  if (ctx.isCargo) {
    if (!ctx.cargoRows.some((r) => r.name?.trim())) add('schedule', t('dispatch_wizard.validate.cargo_row'))
    pushConfirmRowIssues(issues, ctx.cargoRows, 'cargo', 'dispatch_wizard.confirm.sec_cargo', t)
  } else if (f.trip_type === 'point_to_point') {
    const rowOpts = { wantsRecurringTemplate: ctx.wantsRecurringTemplate }
    if (ctx.wantsRecurringTemplate) {
      if (!ctx.passengerRows.some(isPassengerRouteFilled)) {
        add('schedule', t('dispatch_wizard.validate.p2p_route_required'))
      }
      if (!Object.values(f.e1_weekdays || {}).some(Boolean)) {
        add('schedule', t('dispatch_wizard.validate.recurring_weekday'))
      }
    } else if (!ctx.passengerRows.some(isPassengerRowFilled)) {
      add('schedule', t('dispatch_wizard.validate.p2p_row'))
    }
    pushConfirmRowIssues(issues, ctx.passengerRows, 'passenger', 'dispatch_wizard.confirm.sec_e1', t, rowOpts)
  } else if (f.trip_type === 'business') {
    if (!ctx.businessRows.some(isBusinessRowFilled)) add('schedule', t('dispatch_wizard.validate.detail_row'))
    pushConfirmRowIssues(issues, ctx.businessRows, 'business', 'dispatch_wizard.confirm.sec_e2', t)
  } else if (f.trip_type === 'door_to_door') {
    if (
      !ctx.passengerRows.some(isPassengerRowFilled) &&
      !ctx.businessRows.some(isBusinessRowFilled)
    ) {
      add('schedule', t('dispatch_wizard.validate.detail_row'))
    }
    pushConfirmRowIssues(issues, ctx.passengerRows, 'passenger', 'dispatch_wizard.confirm.sec_e1', t)
    pushConfirmRowIssues(issues, ctx.businessRows, 'business', 'dispatch_wizard.confirm.sec_e2', t)
  }

  if (!ctx.detailStepSchedulesValid) add('schedule', t('dispatch_wizard.confirm.issue_schedule_invalid'))
  if (!ctx.computedDepartAt?.trim()) add('general', t('dispatch_wizard.validate.depart_time'))

  return issues
}

const API_FIELD_SECTION = /** @type {Record<string, ConfirmSection>} */ ({
  trip_type: 'general',
  source_channel: 'general',
  is_urgent: 'general',
  urgent_reason: 'general',
  depart_at: 'schedule',
  arrive_by: 'schedule',
  origin: 'schedule',
  destination: 'schedule',
  passenger_count: 'schedule',
  notes: 'purpose',
  wizard_snapshot: 'schedule',
})

/**
 * @param {unknown} err
 * @param {Function} t
 * @returns {ConfirmIssue[]}
 */
export function mapApiValidationToConfirmIssues(err, t) {
  const raw = extractApiValidationMessages(err)
  if (!raw.length) return []

  return raw.map(({ field, message }) => {
    const base = String(field || '').split('.')[0]
    const section = API_FIELD_SECTION[base] || 'general'
    const key = `dispatch_wizard.confirm.api_${base}`
    const mapped = t(key)
    const text =
      mapped !== key
        ? mapped
        : t('dispatch_wizard.confirm.api_field_fallback', {
            field: base || 'dữ liệu',
            detail: message,
          })
    return { section, message: text }
  })
}

/**
 * Điều hướng về bước cần sửa đầu tiên (khi gửi từ bước xác nhận).
 * @returns {boolean} true nếu đã chuyển bước
 */
export function navigateToFirstInvalidWizardStep(ctx) {
  const f = ctx.form?.value ?? ctx.form
  if (!f.trip_type) {
    ctx.setStep(0)
    return true
  }
  if (
    !f.requester_name?.trim() ||
    !f.requester_email?.trim() ||
    !ctx.isPlausibleEmail(f.requester_email) ||
    !f.purpose?.trim() ||
    !f.proposed_date ||
    !f.date_needed ||
    (showCoordinatorPanelForTripType(f.trip_type) && ctx.coordinatorEmailFormatInvalid) ||
    ctx.step2DateOrderInvalid ||
    (f.is_urgent && !f.urgent_reason?.trim())
  ) {
    ctx.setStep(1)
    return true
  }
  if (ctx.isCargo) {
    if (!ctx.cargoRows.some((r) => r.name?.trim())) {
      ctx.setStep(2)
      return true
    }
  } else if (f.trip_type === 'point_to_point') {
    if (!ctx.passengerRows.some(isPassengerRowFilled)) {
      ctx.setStep(2)
      return true
    }
  } else if (f.trip_type === 'business') {
    if (!ctx.businessRows.some(isBusinessRowFilled)) {
      ctx.setStep(2)
      return true
    }
  } else if (
    !ctx.passengerRows.some(isPassengerRowFilled) &&
    !ctx.businessRows.some(isBusinessRowFilled)
  ) {
    ctx.setStep(2)
    return true
  }
  if (!ctx.computedDepartAt?.trim() || !ctx.schedulesPass()) {
    ctx.setStep(2)
    return true
  }
  return false
}
