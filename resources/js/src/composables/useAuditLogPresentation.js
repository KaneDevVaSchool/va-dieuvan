import { useI18n } from 'vue-i18n'

/** Shared labels / copy for audit log list + detail (SPA). */
export function useAuditLogPresentation() {
  const { t, te } = useI18n()

  function eventLabel(event) {
    if (!event) return t('audit_logs_page.event_unknown')
    const key = `audit_logs_page.events.${event}`
    return te(key) ? t(key) : event
  }

  function actionVerb(event) {
    if (!event) return t('audit_logs_page.verb_unknown')
    const key = `audit_logs_page.verbs.${event}`
    return te(key) ? t(key) : event
  }

  function subjectTypeLabel(typeName) {
    if (!typeName) return ''
    const key = `audit_logs_page.subjects.${typeName}`
    return te(key) ? t(key) : typeName
  }

  function subjectText(log) {
    if (!log?.auditable_type && !log?.auditable_id) return null
    const typeName = log.auditable_type?.split('\\').pop()
    const label = subjectTypeLabel(typeName)
    return log.auditable_id ? `${label} #${log.auditable_id}` : label
  }

  function actorDisplayName(log) {
    if (log?.actor?.name) return log.actor.name
    return t('audit_logs_page.feed_actor_system')
  }

  function isSystemActor(log) {
    return !log?.actor?.name
  }

  function emptyDisplay(value) {
    if (value == null || value === '') return t('audit_logs_page.detail_empty')
    return value
  }

  function resultLabel(result) {
    if (!result) return null
    const key = `audit_logs_page.results.${result}`
    return te(key) ? t(key) : result
  }

  const EVENT_AVATAR = {
    'request.approve': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
    'request.reject': 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300',
    'request.create': 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
    'request.paper_received': 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300',
    'attachment.upload': 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
    'attachment.ocr_stub': 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300',
    'cargo.sla_breached': 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
    'api.request': 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
  }

  const EVENT_TAG = {
    'request.approve': 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400',
    'request.reject': 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400',
    'request.create': 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400',
    'request.paper_received': 'bg-teal-50 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400',
    'attachment.upload': 'bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400',
    'attachment.ocr_stub': 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400',
    'cargo.sla_breached': 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400',
  }

  function eventAvatarClass(event) {
    return EVENT_AVATAR[event] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
  }

  function eventTagClass(event) {
    return EVENT_TAG[event] ?? 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
  }

  function actorInitials(log) {
    const name = log?.actor?.name
    if (!name) return null
    return name
      .split(' ')
      .filter(Boolean)
      .slice(-2)
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2)
  }

  return {
    eventLabel,
    actionVerb,
    subjectTypeLabel,
    subjectText,
    actorDisplayName,
    isSystemActor,
    emptyDisplay,
    resultLabel,
    eventAvatarClass,
    eventTagClass,
    actorInitials,
  }
}

export const AUDIT_CATEGORY_EVENTS = {
  request: ['request.create', 'request.paper_received', 'request.reject', 'request.approve'],
  file: ['attachment.upload', 'attachment.ocr_stub'],
  alert: ['cargo.sla_breached'],
  system: ['api.request'],
}
