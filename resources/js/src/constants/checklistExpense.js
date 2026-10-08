/** Checklist tài xế & hồ sơ chi phí xe — hằng số dùng chung FE. */

export const CHECKLIST_RESULTS = ['ok', 'ng', 'na']

/** Loại chi phí dùng trong hồ sơ (khớp `trip_costs.type`). */
export const EXPENSE_COST_TYPES = ['fuel', 'toll', 'parking', 'meal', 'repair', 'maintenance', 'insurance', 'inspection', 'supplies', 'other']

export const EXPENSE_DOC_TYPES = [
  { docType: 'proposal', kind: null, key: 'proposal' },
  { docType: 'payment_request', kind: 'payment', key: 'payment' },
  { docType: 'payment_request', kind: 'advance', key: 'advance' },
  { docType: 'payment_request', kind: 'reimbursement', key: 'reimbursement' },
  { docType: 'no_invoice_statement', kind: null, key: 'no_invoice_statement' },
]

export const EXPENSE_STATUSES = ['draft', 'submitted', 'signed', 'paid', 'rejected', 'cancelled']

/** Khóa i18n loại hồ sơ theo doc_type + payment_kind. */
export function expenseTypeKey(doc) {
  if (!doc) return ''
  if (doc.doc_type === 'payment_request') return doc.payment_kind || 'payment'
  return doc.doc_type
}

/** Hồ sơ ghi nhận chi phí đã phát sinh → dòng gắn sổ chi phí xe (TT, HU, BK). */
export function expenseUsesTripCosts(docType, kind) {
  if (docType === 'no_invoice_statement') return true
  return docType === 'payment_request' && kind !== 'advance'
}

/** Pill màu trạng thái hồ sơ (theme sáng staff). */
export const EXPENSE_STATUS_PILL = {
  draft: 'bg-slate-100 text-slate-700 ring-slate-300 dark:bg-slate-800 dark:text-slate-300',
  submitted: 'bg-sky-50 text-sky-800 ring-sky-300 dark:bg-sky-950/50 dark:text-sky-200',
  signed: 'bg-indigo-50 text-indigo-800 ring-indigo-300 dark:bg-indigo-950/50 dark:text-indigo-200',
  paid: 'bg-emerald-50 text-emerald-800 ring-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-200',
  rejected: 'bg-amber-50 text-amber-800 ring-amber-300 dark:bg-amber-950/50 dark:text-amber-200',
  cancelled: 'bg-slate-100 text-slate-500 ring-slate-200 line-through dark:bg-slate-800 dark:text-slate-500',
}

/** Pill màu trạng thái hồ sơ (theme tối app tài xế). */
export const EXPENSE_STATUS_PILL_DRIVER = {
  draft: 'bg-white/10 text-driver-muted',
  submitted: 'bg-sky-500/15 text-sky-200',
  signed: 'bg-indigo-500/20 text-indigo-200',
  paid: 'bg-emerald-500/20 text-emerald-200',
  rejected: 'bg-amber-500/20 text-amber-200',
  cancelled: 'bg-white/5 text-driver-muted/60 line-through',
}

/** Trạng thái ô ma trận / thẻ checklist. */
export const CHECKLIST_STATE_PILL = {
  pass: 'bg-emerald-50 text-emerald-800 ring-emerald-300 dark:bg-emerald-950/50 dark:text-emerald-200',
  fail: 'bg-rose-50 text-rose-800 ring-rose-300 dark:bg-rose-950/50 dark:text-rose-200',
  draft: 'bg-amber-50 text-amber-800 ring-amber-300 dark:bg-amber-950/50 dark:text-amber-200',
  todo: 'bg-slate-50 text-slate-500 ring-slate-200 dark:bg-slate-800/60 dark:text-slate-400',
}
