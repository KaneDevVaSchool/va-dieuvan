/** @param {string|undefined|null} status */
export function requestStatusBadgeClass(status) {
  if (status === 'approved') return 'bg-teal-50 text-teal-800 ring-1 ring-inset ring-teal-600/15'
  if (status === 'pending') return 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-500/10'
  if (status === 'price_filled') return 'bg-amber-50 text-amber-900 ring-1 ring-inset ring-amber-600/20'
  if (status === 'rejected') return 'bg-rose-50 text-rose-800 ring-1 ring-inset ring-rose-600/15'
  if (status === 'draft') return 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-400/20'
  if (status === 'cancelled') return 'bg-slate-100 text-slate-500 ring-1 ring-inset ring-slate-400/15'
  if (status === 'soft_deleted') return 'bg-rose-50 text-rose-800 ring-1 ring-inset ring-rose-600/15'
  return 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-500/10'
}
