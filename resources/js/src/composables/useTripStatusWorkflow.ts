export type AdvanceTripStatusPayload = 'in_progress' | 'completed'

/** Maps API-supported next step for dispatcher linear flow (excluding assign). */
export type PrimaryUiAction =
  | { kind: 'assign' }
  | { kind: 'advance'; to: AdvanceTripStatusPayload }

const TERMINAL = new Set(['completed', 'cancelled'])

/** Tailwind badge classes aligned with `StickyTripHeader`. */
export function tripStatusBadgeClass(status: string | null | undefined): string {
  const s = String(status ?? '')
  const map: Record<string, string> = {
    pending: 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-200',
    approved: 'bg-green-100 text-green-900 dark:bg-green-950/50 dark:text-green-200',
    assigned: 'bg-blue-100 text-blue-900 dark:bg-blue-950/40 dark:text-blue-200',
    driver_confirmed: 'bg-blue-100 text-blue-900 dark:bg-blue-950/40 dark:text-blue-200',
    in_progress: 'bg-blue-100 text-blue-900 dark:bg-blue-950/40 dark:text-blue-200',
    completed: 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-100',
    cancelled: 'bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-200',
    incident: 'bg-rose-100 text-rose-900 dark:bg-rose-950/40 dark:text-rose-200',
  }
  return map[s] ?? 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100'
}

export interface TripWorkflowInput {
  tripStatus: string | null | undefined
  /** When true, emit assign matches sticky header readiness. */
  canAssign: boolean
  assignReady: boolean
  assigning: boolean
  canUpdateStatus: boolean
}

export interface ResolvedTripWorkflow {
  primaryAction: PrimaryUiAction | null
  /** Incident only: dispatcher can reopen as in-progress. */
  secondaryResumeTrip: boolean
  showDestructiveCancel: boolean
  isTerminal: boolean
  hintI18nKey: string | null
  primaryDisabledReason: string | null // i18n key for aria only
}

/** Client-side dispatcher workflow guard (backend does not validate graph). */
export function resolveTripStatusWorkflow(ws: TripWorkflowInput): ResolvedTripWorkflow {
  const st = String(ws.tripStatus ?? '').trim().toLowerCase()
  const terminal = TERMINAL.has(st)

  if (terminal) {
    return {
      primaryAction: null,
      secondaryResumeTrip: false,
      showDestructiveCancel: false,
      isTerminal: true,
      hintI18nKey: 'trip_detail.status_workflow.terminal_hint',
      primaryDisabledReason: null,
    }
  }

  let primaryAction: PrimaryUiAction | null = null

  if (st === 'approved' && ws.canAssign) {
    primaryAction = { kind: 'assign' }
  } else if ((st === 'assigned' || st === 'driver_confirmed') && ws.canUpdateStatus) {
    primaryAction = { kind: 'advance', to: 'in_progress' }
  } else if (st === 'in_progress' && ws.canUpdateStatus) {
    primaryAction = { kind: 'advance', to: 'completed' }
  } else if (st === 'incident' && ws.canUpdateStatus) {
    primaryAction = { kind: 'advance', to: 'completed' }
  }

  let hintI18nKey: string | null = null

  if (st === 'pending') {
    hintI18nKey = 'trip_detail.status_workflow.pending_hint'
    primaryAction = null
  } else if (st === 'approved' && !ws.canAssign && !ws.canUpdateStatus) {
    hintI18nKey = 'trip_detail.coordination.no_permission_assign'
  } else if (st === 'approved' && !ws.canAssign && ws.canUpdateStatus) {
    hintI18nKey = 'trip_detail.status_workflow.approved_need_assign_perm'
  } else if (primaryAction?.kind === 'assign' && !ws.assignReady && !ws.assigning) {
    hintI18nKey = 'trip_detail.coordination.assign_disabled_hint'
  } else if ((st === 'assigned' || st === 'driver_confirmed') && !ws.canUpdateStatus) {
    hintI18nKey = 'trip_detail.coordination.no_permission_status'
  } else if (!primaryAction && st !== 'pending' && ws.canUpdateStatus && !['completed', 'cancelled'].includes(st)) {
    hintI18nKey = 'trip_detail.status_workflow.no_transition'
  } else if (!primaryAction && st !== 'pending') {
    if (!ws.canUpdateStatus && st !== 'approved') {
      hintI18nKey = 'trip_detail.coordination.no_permission_status'
    }
  }

  let primaryDisabledReason: string | null = null
  if (primaryAction?.kind === 'assign') {
    if (!ws.assignReady || ws.assigning) primaryDisabledReason = 'trip_detail.coordination.assign_disabled_hint'
  }

  const secondaryResumeTrip = st === 'incident' && ws.canUpdateStatus

  const showDestructiveCancel = ws.canUpdateStatus && !TERMINAL.has(st)

  return {
    primaryAction,
    secondaryResumeTrip,
    showDestructiveCancel,
    isTerminal: false,
    hintI18nKey: hintI18nKey,
    primaryDisabledReason,
  }
}
