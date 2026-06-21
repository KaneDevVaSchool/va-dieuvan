import { useI18n } from 'vue-i18n'
import {
  GlobeAltIcon,
  DocumentPlusIcon,
  InboxArrowDownIcon,
  XCircleIcon,
  CheckCircleIcon,
  ArrowUpTrayIcon,
  EyeIcon,
  BoltIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

/** Shared labels / copy for audit log list + detail (SPA). */
export function useAuditLogPresentation() {
  const { t, tm } = useI18n()

  /** Event codes contain dots; vue-i18n path keys must not use te/t with interpolated dots. */
  function lookupAuditMessage(group, code) {
    if (!code) return null
    const bucket = tm(`audit_logs_page.${group}`)
    if (bucket && typeof bucket === 'object' && code in bucket) {
      const val = bucket[code]
      return typeof val === 'string' ? val : null
    }
    return null
  }

  function eventLabel(event) {
    if (!event) return t('audit_logs_page.event_unknown')
    return lookupAuditMessage('events', event) ?? event
  }

  function actionVerb(event) {
    if (!event) return t('audit_logs_page.verb_unknown')
    return lookupAuditMessage('verbs', event) ?? event
  }

  function subjectTypeLabel(typeName) {
    if (!typeName) return ''
    return lookupAuditMessage('subjects', typeName) ?? typeName
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
    return lookupAuditMessage('results', result) ?? result
  }

  const EVENT_CONFIG = {
    'api.request': {
      icon: GlobeAltIcon,
      avatar: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
      badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
      tag: 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
    },
    'request.approve': {
      icon: CheckCircleIcon,
      avatar: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
      badge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
      tag: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400',
    },
    'request.reject': {
      icon: XCircleIcon,
      avatar: 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300',
      badge: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
      tag: 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400',
    },
    'request.create': {
      icon: DocumentPlusIcon,
      avatar: 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
      badge: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
      tag: 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400',
    },
    'request.paper_received': {
      icon: InboxArrowDownIcon,
      avatar: 'bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300',
      badge: 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300',
      tag: 'bg-teal-50 text-teal-600 dark:bg-teal-950/40 dark:text-teal-400',
    },
    'attachment.upload': {
      icon: ArrowUpTrayIcon,
      avatar: 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300',
      badge: 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
      tag: 'bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-400',
    },
    'attachment.ocr_stub': {
      icon: EyeIcon,
      avatar: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300',
      badge: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
      tag: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400',
    },
    'cargo.sla_breached': {
      icon: ExclamationTriangleIcon,
      avatar: 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
      badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
      tag: 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400',
    },
  }

  const DEFAULT_EVENT_STYLE = {
    icon: BoltIcon,
    avatar: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
    badge: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    tag: 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
  }

  function eventStyle(event) {
    return EVENT_CONFIG[event] ?? DEFAULT_EVENT_STYLE
  }

  function eventIcon(event) {
    return eventStyle(event).icon
  }

  function eventAvatarClass(event) {
    return eventStyle(event).avatar
  }

  function eventBadgeClass(event) {
    return eventStyle(event).badge
  }

  function eventTagClass(event) {
    return eventStyle(event).tag
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
    eventIcon,
    eventAvatarClass,
    eventBadgeClass,
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
