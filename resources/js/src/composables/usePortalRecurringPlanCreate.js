import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store'
import { createPortalDispatchRequestTemplate } from '../api/requests'
import { formatApiError } from '../api/http'
import { newIdempotencyKey } from '../util/idempotency'
import { LEGACY_DRAFT_KEY, E1_WEEKDAY_KEYS, todayISODate } from './dispatchWizardConstants'
import { buildIsoWeekdaysFromE1, computeRecurringOccurrenceDates } from '../util/recurringOccurrences'

const DRAFT_VERSION = 'portal-recurring-plan-v2'

function addDaysYmd(ymd, days) {
  const parts = String(ymd || '')
    .trim()
    .slice(0, 10)
    .split('-')
    .map(Number)
  if (parts.length < 3 || !parts[0]) return ymd
  const dt = new Date(parts[0], parts[1] - 1, parts[2])
  dt.setDate(dt.getDate() + days)
  const y = dt.getFullYear()
  const m = String(dt.getMonth() + 1).padStart(2, '0')
  const d = String(dt.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

function createEmptyWeekdays() {
  return Object.fromEntries(E1_WEEKDAY_KEYS.map((k) => [k, k !== 'sat' && k !== 'sun']))
}

export function createPortalRecurringPlanForm() {
  const today = todayISODate()
  return {
    recurrence_start_date: today,
    recurrence_end_date: addDaysYmd(today, 6),
    recurrence_depart_time: '07:00',
    recurrence_return_time: '17:00',
    e1_weekdays: createEmptyWeekdays(),
    pickup: '',
    dropoff: '',
    notes: '',
  }
}

function normalizeTimeHhMm(raw) {
  const s = String(raw ?? '').trim()
  if (!s) return ''
  const m = s.match(/^(\d{1,2}):(\d{2})/)
  if (m) return `${m[1].padStart(2, '0')}:${m[2]}`
  return ''
}

function draftStorageKey(uid) {
  return uid != null ? `${LEGACY_DRAFT_KEY}-u${uid}-extracurricular` : `${LEGACY_DRAFT_KEY}-extracurricular`
}

function isQuotaExceededError(e) {
  return e?.name === 'QuotaExceededError' || e?.code === 22
}

export function usePortalRecurringPlanCreate() {
  const { t, locale } = useI18n()
  const router = useRouter()
  const auth = useAuthStore()

  const form = ref(createPortalRecurringPlanForm())
  const loading = ref(false)
  const error = ref('')
  const created = ref(null)
  const draftSaveError = ref('')
  const draftSaveFlash = ref(false)
  let draftSaveFlashTimer = null

  const e1WeekdayOptions = computed(() =>
    E1_WEEKDAY_KEYS.map((k) => ({ k, label: t(`dispatch_wizard.weekday.${k}`) })),
  )

  const occurrencePreview = computed(() => {
    const f = form.value
    return computeRecurringOccurrenceDates({
      freq: 'weekly',
      interval: 1,
      byweekday: buildIsoWeekdaysFromE1(f.e1_weekdays),
      startDate: f.recurrence_start_date,
      recurrenceEndDate: f.recurrence_end_date,
      repeatCount: null,
      endMode: 'date',
    })
  })

  const firstOccurrenceDate = computed(() => occurrencePreview.value.dates[0] || '')

  function scheduleDateRangeValid(f) {
    const start = String(f.recurrence_start_date || '').trim()
    const end = String(f.recurrence_end_date || '').trim()
    if (!start || !end) return false
    return end >= start
  }

  function scheduleComplete() {
    const f = form.value
    if (!String(f.recurrence_start_date || '').trim()) return false
    if (!String(f.recurrence_end_date || '').trim()) return false
    if (!scheduleDateRangeValid(f)) return false
    if (!normalizeTimeHhMm(f.recurrence_depart_time)) return false
    if (!normalizeTimeHhMm(f.recurrence_return_time)) return false
    if (!buildIsoWeekdaysFromE1(f.e1_weekdays).length) return false
    return occurrencePreview.value.count >= 1
  }

  const formComplete = computed(() => {
    const f = form.value
    if (!scheduleComplete()) return false
    if (!f.pickup?.trim() || !f.dropoff?.trim()) return false
    return true
  })

  const previewHint = computed(() => {
    const f = form.value
    if (!String(f.recurrence_end_date || '').trim()) {
      return t('portal.extracurricular_create.blocker_end_date')
    }
    if (!scheduleDateRangeValid(f)) {
      return t('portal.recurring_plan.blocker_end_before_start')
    }
    if (!buildIsoWeekdaysFromE1(f.e1_weekdays).length) {
      return t('portal.extracurricular_create.blocker_weekdays')
    }
    if (occurrencePreview.value.count < 1) {
      return t('portal.recurring_plan.blocker_no_occurrences')
    }
    return ''
  })

  const stepBlockers = computed(() => {
    if (loading.value) return []
    const blockers = []
    const f = form.value
    if (!String(f.recurrence_start_date || '').trim()) {
      blockers.push(t('portal.extracurricular_create.blocker_start_date'))
    }
    if (!String(f.recurrence_end_date || '').trim()) {
      blockers.push(t('portal.extracurricular_create.blocker_end_date'))
    } else if (!scheduleDateRangeValid(f)) {
      blockers.push(t('portal.recurring_plan.blocker_end_before_start'))
    }
    if (!normalizeTimeHhMm(f.recurrence_depart_time)) {
      blockers.push(t('portal.extracurricular_create.blocker_depart_time'))
    }
    if (!normalizeTimeHhMm(f.recurrence_return_time)) {
      blockers.push(t('portal.extracurricular_create.blocker_return_time'))
    }
    if (!buildIsoWeekdaysFromE1(f.e1_weekdays).length) {
      blockers.push(t('portal.extracurricular_create.blocker_weekdays'))
    }
    if (occurrencePreview.value.count < 1) {
      blockers.push(t('portal.recurring_plan.blocker_no_occurrences'))
    }
    if (!f.pickup?.trim() || !f.dropoff?.trim()) {
      blockers.push(t('portal.extracurricular_create.blocker_route'))
    }
    return blockers
  })

  const headerPrimaryDisabled = computed(() => loading.value || !formComplete.value)

  function toggleWeekday(k) {
    const w = form.value.e1_weekdays
    if (w && typeof w[k] === 'boolean') w[k] = !w[k]
  }

  function setWeekdayPreset(preset) {
    const w = form.value.e1_weekdays
    if (!w) return
    if (preset === 'weekdays') {
      for (const k of E1_WEEKDAY_KEYS) w[k] = k !== 'sat' && k !== 'sun'
    } else if (preset === 'all') {
      for (const k of E1_WEEKDAY_KEYS) w[k] = true
    } else if (preset === 'clear') {
      for (const k of E1_WEEKDAY_KEYS) w[k] = false
    }
  }

  function toIsoMaybe(v) {
    if (!v) return null
    try {
      return new Date(v).toISOString()
    } catch {
      return v
    }
  }

  /** Chọn mốc khởi hành đầu tiên ≥ 2h (tránh 422); recurring CLB backend bỏ qua 2h nhưng vẫn cần depart_at hợp lệ. */
  function buildDepartAtIso() {
    const dep = normalizeTimeHhMm(form.value.recurrence_depart_time)
    if (!dep) return ''
    const minMs = Date.now() + 2 * 3600 * 1000
    const dates = occurrencePreview.value.dates || []
    for (const day of dates) {
      const iso = toIsoMaybe(`${day}T${dep}`)
      if (iso && new Date(iso).getTime() >= minMs) return iso
    }
    const fallbackDay = dates[0] || form.value.recurrence_start_date?.trim()
    if (!fallbackDay) return ''
    return toIsoMaybe(`${fallbackDay}T${dep}`)
  }

  function buildArriveByIso(departIso) {
    const ret = normalizeTimeHhMm(form.value.recurrence_return_time)
    if (!departIso || !ret) return null
    try {
      const dep = new Date(departIso)
      const [rh, rm] = ret.split(':').map(Number)
      const arr = new Date(dep)
      arr.setHours(rh, rm, 0, 0)
      if (arr.getTime() < dep.getTime()) {
        arr.setDate(arr.getDate() + 1)
      }
      return arr.toISOString()
    } catch {
      return null
    }
  }

  function buildApiPayload() {
    const f = form.value
    const u = auth.user || {}
    const wizard_snapshot = {
      form: {
        trip_type: 'point_to_point',
        point_purpose_kind: 'extracurricular',
        source_channel: 'portal',
        recurrence_start_date: f.recurrence_start_date,
        recurrence_end_date: f.recurrence_end_date,
        recurrence_depart_time: f.recurrence_depart_time,
        recurrence_return_time: f.recurrence_return_time,
        e1_weekdays: { ...f.e1_weekdays },
        requester_name: u.name || '',
        requester_email: u.email || '',
        requester_phone: u.phone || '',
        requester_unit: u.department?.name || '',
      },
      passengerRows: [
        {
          pickup: f.pickup,
          dropoff: f.dropoff,
          depart_at: buildDepartAtIso(),
          return_at: `${firstOccurrenceDate.value || f.recurrence_start_date}T${normalizeTimeHhMm(f.recurrence_return_time)}`,
        },
      ],
    }

    const departAt = buildDepartAtIso()
    const arriveBy = buildArriveByIso(departAt)
    const returnTime = normalizeTimeHhMm(f.recurrence_return_time)

    const payload = {
      trip_type: 'point_to_point',
      source_channel: 'portal',
      origin: f.pickup.trim(),
      destination: f.dropoff.trim(),
      depart_at: departAt,
      notes: f.notes?.trim() || null,
      start_date: f.recurrence_start_date?.trim() || undefined,
      return_time: returnTime || undefined,
      recurrence_rule: {
        freq: 'weekly',
        interval: 1,
        byweekday: buildIsoWeekdaysFromE1(f.e1_weekdays),
      },
      recurrence_end_date: f.recurrence_end_date?.trim() || undefined,
      wizard_snapshot,
    }
    if (arriveBy) {
      payload.arrive_by = arriveBy
    }

    return payload
  }

  let submitInFlight = false

  async function submitPlan() {
    if (created.value?.id || submitInFlight || loading.value) return
    if (!formComplete.value) return
    submitInFlight = true
    loading.value = true
    error.value = ''
    try {
      const payload = buildApiPayload()
      if (!payload.depart_at) {
        error.value = t('portal.recurring_plan.blocker_depart_at_invalid')
        return
      }
      const idempotencyKey = newIdempotencyKey()
      const pack = await createPortalDispatchRequestTemplate(payload, { idempotencyKey })
      created.value = pack?.dispatch_request ?? null
      clearDraft()
      await router.push({ name: 'portalExtracurricularList', query: { plan_created: '1' } })
    } catch (e) {
      error.value = formatApiError(e, t('dispatch_wizard.validate.create_fail'))
    } finally {
      loading.value = false
      submitInFlight = false
    }
  }

  function primaryAction() {
    if (!headerPrimaryDisabled.value) submitPlan()
  }

  function flashDraftSaved() {
    draftSaveFlash.value = true
    clearTimeout(draftSaveFlashTimer)
    draftSaveFlashTimer = setTimeout(() => {
      draftSaveFlash.value = false
    }, 2500)
  }

  function serializeDraft() {
    return {
      version: DRAFT_VERSION,
      form: { ...form.value, e1_weekdays: { ...form.value.e1_weekdays } },
      savedAt: Date.now(),
    }
  }

  function applyDraftPayload(data) {
    if (!data || typeof data !== 'object') return
    if (data.version === DRAFT_VERSION && data.form) {
      form.value = {
        ...createPortalRecurringPlanForm(),
        ...data.form,
        e1_weekdays: { ...createEmptyWeekdays(), ...(data.form.e1_weekdays || {}) },
      }
    }
  }

  function saveDraft() {
    draftSaveError.value = ''
    try {
      const uid = auth.user?.id ?? null
      localStorage.setItem(draftStorageKey(uid), JSON.stringify(serializeDraft()))
      flashDraftSaved()
    } catch (e) {
      if (isQuotaExceededError(e)) {
        draftSaveError.value = t('dispatch_wizard.draft.save_quota_error')
      }
    }
  }

  function loadDraftFromStorage() {
    try {
      const uid = auth.user?.id ?? null
      const raw = localStorage.getItem(draftStorageKey(uid))
      if (raw) applyDraftPayload(JSON.parse(raw))
    } catch {
      /* ignore */
    }
  }

  function clearDraft() {
    try {
      const uid = auth.user?.id ?? null
      localStorage.removeItem(draftStorageKey(uid))
    } catch {
      /* ignore */
    }
  }

  function formatIsoDateDisplay(iso) {
    if (!iso) return '—'
    try {
      const [y, m, d] = String(iso).split('-').map(Number)
      const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
      return new Intl.DateTimeFormat(loc, { day: '2-digit', month: '2-digit', year: 'numeric' }).format(
        new Date(y, (m || 1) - 1, d || 1),
      )
    } catch {
      return iso
    }
  }

  onMounted(() => {
    loadDraftFromStorage()
    ensureEndDateAfterStart()
  })

  onUnmounted(() => clearTimeout(draftSaveFlashTimer))

  function ensureEndDateAfterStart() {
    const f = form.value
    const start = String(f.recurrence_start_date || '').trim()
    if (!start) return
    const end = String(f.recurrence_end_date || '').trim()
    if (!end || end < start) {
      form.value.recurrence_end_date = addDaysYmd(start, 6)
    }
  }

  watch(
    () => form.value.recurrence_start_date,
    () => ensureEndDateAfterStart(),
  )

  let autosaveTimer = null
  watch(
    form,
    () => {
      clearTimeout(autosaveTimer)
      autosaveTimer = setTimeout(() => saveDraft(), 1200)
    },
    { deep: true },
  )

  return {
    form,
    loading,
    error,
    draftSaveError,
    draftSaveFlash,
    e1WeekdayOptions,
    occurrencePreview,
    previewHint,
    scheduleComplete,
    stepBlockers,
    headerPrimaryDisabled,
    toggleWeekday,
    setWeekdayPreset,
    primaryAction,
    submitPlan,
    saveDraft,
    formatIsoDateDisplay,
  }
}
