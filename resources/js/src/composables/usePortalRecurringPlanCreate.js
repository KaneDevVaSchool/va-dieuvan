import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../store'
import { createPortalDispatchRequestTemplate } from '../api/requests'
import { uploadAttachment } from '../api/attachments'
import { searchUsersForDispatchForm } from '../api/operational'
import { formatApiError } from '../api/http'
import { newIdempotencyKey } from '../util/idempotency'
import {
  LEGACY_DRAFT_KEY,
  E1_WEEKDAY_KEYS,
  todayISODate,
} from './dispatchWizardConstants'
import {
  buildIsoWeekdaysFromE1,
  computeRecurringOccurrenceDates,
} from '../util/recurringOccurrences'

const DRAFT_VERSION = 'portal-recurring-plan-v1'

function createEmptyWeekdays() {
  return Object.fromEntries(E1_WEEKDAY_KEYS.map((k) => [k, false]))
}

export function createPortalRecurringPlanForm() {
  const today = todayISODate()
  return {
    recurrence_freq_mode: 'daily',
    recurrence_start_date: today,
    recurrence_depart_time: '07:00',
    recurrence_return_time: '17:00',
    recurrence_end_date: '',
    recurrence_end_mode: 'date',
    recurrence_repeat_count: '',
    e1_weekdays: createEmptyWeekdays(),
    pickup: '',
    dropoff: '',
    planned_guests: '',
    requester_name: '',
    requester_email: '',
    requester_phone: '',
    requester_unit: '',
    purpose: '',
  }
}

function normalizeTimeHhMm(raw) {
  const s = String(raw ?? '').trim()
  if (!s) return ''
  const m = s.match(/^(\d{1,2}):(\d{2})/)
  if (m) return `${m[1].padStart(2, '0')}:${m[2]}`
  return ''
}

function isPlausibleEmail(s) {
  const t = String(s ?? '').trim()
  if (!t) return false
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t)
}

function sanitizeVnPhoneDigits(raw) {
  if (raw == null) return ''
  let d = String(raw).replace(/\D/g, '')
  if (d.startsWith('84') && d.length >= 10) d = `0${d.slice(2)}`
  if (d.length > 11) d = d.slice(0, 11)
  return d
}

function draftStorageKey(uid) {
  return uid != null ? `${LEGACY_DRAFT_KEY}-u${uid}-extracurricular` : `${LEGACY_DRAFT_KEY}-extracurricular`
}

function activeDraftPointerKey(uid) {
  return `${draftActiveStorageKey(uid)}-extracurricular`
}

function isQuotaExceededError(e) {
  return e?.name === 'QuotaExceededError' || e?.code === 22
}

export function usePortalRecurringPlanCreate() {
  const { t, locale } = useI18n()
  const router = useRouter()
  const auth = useAuthStore()

  const step = ref(0)
  const maxReachedStep = ref(0)
  const form = ref(createPortalRecurringPlanForm())
  const loading = ref(false)
  const error = ref('')
  const created = ref(null)
  const basisFile = ref(null)
  const basisFileError = ref('')
  const requesterEmailTouched = ref(false)
  const draftSaveError = ref('')
  const draftSaveFlash = ref(false)
  let draftSaveFlashTimer = null

  const requesterSearchQ = ref('')
  const requesterSearchResults = ref([])
  const requesterSearchLoading = ref(false)
  const requesterSearchError = ref('')
  const requesterDropdownOpen = ref(false)
  let requesterSearchTimer = null

  const e1WeekdayOptions = computed(() =>
    E1_WEEKDAY_KEYS.map((k) => ({ k, label: t(`dispatch_wizard.weekday.${k}`) })),
  )

  const occurrencePreview = computed(() => {
    const f = form.value
    const freq = f.recurrence_freq_mode === 'weekly' ? 'weekly' : 'daily'
    const byweekday = freq === 'weekly' ? buildIsoWeekdaysFromE1(f.e1_weekdays) : undefined
    return computeRecurringOccurrenceDates({
      freq,
      interval: 1,
      byweekday,
      startDate: f.recurrence_start_date,
      recurrenceEndDate: f.recurrence_end_date,
      repeatCount: f.recurrence_repeat_count,
      endMode: f.recurrence_end_mode || 'date',
    })
  })

  const firstOccurrenceDate = computed(() => occurrencePreview.value.dates[0] || '')

  function recurringScheduleComplete() {
    const f = form.value
    if (!String(f.recurrence_start_date || '').trim()) return false
    if (!normalizeTimeHhMm(f.recurrence_depart_time)) return false
    if (!normalizeTimeHhMm(f.recurrence_return_time)) return false
    const mode = f.recurrence_end_mode || 'date'
    if (mode === 'date' && !String(f.recurrence_end_date || '').trim()) return false
    if (mode === 'weeks') {
      const n = Number(f.recurrence_repeat_count)
      if (!Number.isFinite(n) || n < 1) return false
    }
    if (f.recurrence_freq_mode === 'weekly') {
      if (!buildIsoWeekdaysFromE1(f.e1_weekdays).length) return false
    }
    return occurrencePreview.value.count >= 1
  }

  const requesterEmailFormatInvalid = computed(
    () => !!form.value.requester_email?.trim() && !isPlausibleEmail(form.value.requester_email),
  )

  const step0Complete = computed(() => {
    const f = form.value
    return (
      recurringScheduleComplete() &&
      !!f.pickup?.trim() &&
      !!f.dropoff?.trim() &&
      Number(f.planned_guests) >= 1 &&
      !!f.requester_name?.trim() &&
      !!f.requester_email?.trim() &&
      !requesterEmailFormatInvalid.value &&
      !!f.purpose?.trim()
    )
  })

  const stepBlockers = computed(() => {
    if (loading.value) return []
    const blockers = []
    const f = form.value
    if (step.value === 0) {
      if (!String(f.recurrence_start_date || '').trim()) {
        blockers.push(t('portal.extracurricular_create.blocker_start_date'))
      }
      if (!normalizeTimeHhMm(f.recurrence_depart_time)) {
        blockers.push(t('portal.extracurricular_create.blocker_depart_time'))
      }
      if (!normalizeTimeHhMm(f.recurrence_return_time)) {
        blockers.push(t('portal.extracurricular_create.blocker_return_time'))
      }
      const endMode = f.recurrence_end_mode || 'date'
      if (endMode === 'date' && !String(f.recurrence_end_date || '').trim()) {
        blockers.push(t('portal.extracurricular_create.blocker_end_date'))
      }
      if (endMode === 'weeks') {
        const n = Number(f.recurrence_repeat_count)
        if (!Number.isFinite(n) || n < 1) {
          blockers.push(t('portal.extracurricular_create.blocker_repeat_weeks'))
        }
      }
      if (f.recurrence_freq_mode === 'weekly' && !buildIsoWeekdaysFromE1(f.e1_weekdays).length) {
        blockers.push(t('portal.extracurricular_create.blocker_weekdays'))
      }
      if (occurrencePreview.value.count < 1) {
        blockers.push(t('portal.recurring_plan.blocker_no_occurrences'))
      }
      if (!f.pickup?.trim() || !f.dropoff?.trim()) {
        blockers.push(t('portal.extracurricular_create.blocker_route'))
      }
      if (!(Number(f.planned_guests) >= 1)) {
        blockers.push(t('portal.recurring_plan.blocker_guests'))
      }
      if (!f.requester_name?.trim()) {
        blockers.push(t('portal.extracurricular_create.blocker_requester_name'))
      }
      if (!f.requester_email?.trim() || requesterEmailFormatInvalid.value) {
        blockers.push(t('portal.extracurricular_create.blocker_requester_email'))
      }
      if (!f.purpose?.trim()) {
        blockers.push(t('portal.extracurricular_create.blocker_purpose'))
      }
    }
    return blockers
  })

  const canGoNext = computed(() => (step.value === 0 ? step0Complete.value : true))

  const headerPrimaryDisabled = computed(() => {
    if (loading.value) return true
    if (step.value === 0) return !canGoNext.value
    return !step0Complete.value
  })

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

  function enterStep(i) {
    const target = Math.max(0, Math.min(1, Number(i)))
    if (target > maxReachedStep.value) maxReachedStep.value = target
    step.value = target
  }

  function nextStep() {
    if (!canGoNext.value) return
    enterStep(step.value + 1)
  }

  function prevStep() {
    if (step.value > 0) step.value--
  }

  function goStepFromStepper(idx) {
    enterStep(idx === 0 ? 0 : 1)
  }

  function scheduleRequesterSearch() {
    requesterSearchError.value = ''
    clearTimeout(requesterSearchTimer)
    requesterSearchTimer = setTimeout(runRequesterSearch, 350)
  }

  async function runRequesterSearch() {
    const q = requesterSearchQ.value.trim()
    if (q.length < 2) {
      requesterSearchResults.value = []
      requesterDropdownOpen.value = false
      return
    }
    requesterSearchLoading.value = true
    requesterDropdownOpen.value = true
    try {
      requesterSearchResults.value = await searchUsersForDispatchForm(q)
    } catch (e) {
      requesterSearchError.value = formatApiError(e, t('dispatch_wizard.search_staff_fail'))
    } finally {
      requesterSearchLoading.value = false
    }
  }

  function pickRequester(u) {
    form.value.requester_name = u.name || ''
    form.value.requester_email = u.email || ''
    form.value.requester_phone = sanitizeVnPhoneDigits(u.phone || '')
    requesterSearchQ.value = ''
    requesterDropdownOpen.value = false
  }

  function onRequesterPhoneInput(e) {
    form.value.requester_phone = sanitizeVnPhoneDigits(e?.target?.value)
  }

  function onRequesterEmailBlur() {
    requesterEmailTouched.value = true
  }

  function toIsoMaybe(v) {
    if (!v) return null
    try {
      return new Date(v).toISOString()
    } catch {
      return v
    }
  }

  function buildDepartAtIso() {
    const day = firstOccurrenceDate.value || form.value.recurrence_start_date?.trim()
    const dep = normalizeTimeHhMm(form.value.recurrence_depart_time)
    if (!day || !dep) return ''
    return toIsoMaybe(`${day}T${dep}`)
  }

  function buildApiPayload() {
    const f = form.value
    const freq = f.recurrence_freq_mode === 'weekly' ? 'weekly' : 'daily'
    const endMode = f.recurrence_end_mode || 'date'
    const guests = Math.round(Number(f.planned_guests) || 0)

    const wizard_snapshot = {
      form: {
        trip_type: 'point_to_point',
        point_purpose_kind: 'extracurricular',
        source_channel: 'portal',
        recurrence_freq_mode: f.recurrence_freq_mode,
        recurrence_start_date: f.recurrence_start_date,
        recurrence_depart_time: f.recurrence_depart_time,
        recurrence_return_time: f.recurrence_return_time,
        recurrence_end_date: f.recurrence_end_date,
        recurrence_end_mode: f.recurrence_end_mode,
        recurrence_repeat_count: f.recurrence_repeat_count,
        e1_weekdays: { ...f.e1_weekdays },
        requester_name: f.requester_name,
        requester_email: f.requester_email,
        requester_phone: f.requester_phone,
        requester_unit: f.requester_unit,
        purpose: f.purpose,
      },
      passengerRows: [
        {
          pickup: f.pickup,
          dropoff: f.dropoff,
          guests: String(guests),
          depart_at: buildDepartAtIso(),
          return_at: `${firstOccurrenceDate.value || f.recurrence_start_date}T${normalizeTimeHhMm(f.recurrence_return_time)}`,
        },
      ],
    }

    return {
      trip_type: 'point_to_point',
      source_channel: 'portal',
      origin: f.pickup.trim(),
      destination: f.dropoff.trim(),
      depart_at: buildDepartAtIso(),
      arrive_by: null,
      passenger_count: guests > 0 ? guests : null,
      start_date: f.recurrence_start_date?.trim() || undefined,
      return_time: f.recurrence_return_time?.trim() || undefined,
      recurrence_rule:
        freq === 'daily'
          ? { freq: 'daily', interval: 1 }
          : {
              freq: 'weekly',
              interval: 1,
              byweekday: buildIsoWeekdaysFromE1(f.e1_weekdays),
            },
      recurrence_end_date:
        endMode === 'date' ? f.recurrence_end_date?.trim() || undefined : undefined,
      repeat_count:
        endMode === 'weeks'
          ? Math.max(1, Math.round(Number(f.recurrence_repeat_count) || 0))
          : undefined,
      wizard_snapshot,
    }
  }

  let submitInFlight = false

  async function submitPlan() {
    if (created.value?.id || submitInFlight || loading.value) return
    if (!step0Complete.value) return
    submitInFlight = true
    loading.value = true
    error.value = ''
    try {
      const idempotencyKey = newIdempotencyKey()
      const payload = buildApiPayload()
      const pack = await createPortalDispatchRequestTemplate(payload, { idempotencyKey })
      created.value = pack?.dispatch_request ?? null
      if (basisFile.value && created.value?.id) {
        try {
          await uploadAttachment({
            attachable_type: 'dispatch_request',
            attachable_id: created.value.id,
            kind: 'proposal_basis',
            file: basisFile.value,
          })
        } catch (attachErr) {
          error.value = formatApiError(attachErr, t('dispatch_wizard.validate.attach_fail'))
        }
      }
      clearDraft()
      if (created.value?.id) {
        await router.push({
          name: 'portalExtracurricularDetail',
          params: { id: created.value.id },
        })
      }
    } catch (e) {
      error.value = formatApiError(e, t('dispatch_wizard.validate.create_fail'))
    } finally {
      loading.value = false
      submitInFlight = false
    }
  }

  function primaryAction() {
    if (loading.value || submitInFlight) return
    if (step.value === 0) nextStep()
    else submitPlan()
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
      step: step.value,
      maxReachedStep: maxReachedStep.value,
      savedAt: Date.now(),
    }
  }

  function applyDraftPayload(data) {
    if (!data || typeof data !== 'object') return
    if (data.version === DRAFT_VERSION && data.form) {
      form.value = { ...createPortalRecurringPlanForm(), ...data.form, e1_weekdays: { ...createEmptyWeekdays(), ...(data.form.e1_weekdays || {}) } }
      step.value = data.step ?? 0
      maxReachedStep.value = data.maxReachedStep ?? step.value
      return
    }
    const legacyForm = data.form
    if (legacyForm && typeof legacyForm === 'object') {
      const next = createPortalRecurringPlanForm()
      next.recurrence_start_date = legacyForm.recurrence_start_date || next.recurrence_start_date
      next.recurrence_depart_time = legacyForm.recurrence_depart_time || next.recurrence_depart_time
      next.recurrence_return_time = legacyForm.recurrence_return_time || next.recurrence_return_time
      next.recurrence_end_date = legacyForm.recurrence_end_date || ''
      next.recurrence_end_mode = legacyForm.recurrence_end_mode || 'date'
      next.recurrence_repeat_count = legacyForm.recurrence_repeat_count || ''
      if (legacyForm.e1_weekdays) next.e1_weekdays = { ...createEmptyWeekdays(), ...legacyForm.e1_weekdays }
      next.requester_name = legacyForm.requester_name || ''
      next.requester_email = legacyForm.requester_email || ''
      next.requester_phone = sanitizeVnPhoneDigits(legacyForm.requester_phone || '')
      next.requester_unit = legacyForm.requester_unit || ''
      next.purpose = legacyForm.purpose || ''
      const row = data.passengerRows?.[0]
      if (row) {
        next.pickup = row.pickup || ''
        next.dropoff = row.dropoff || ''
        next.planned_guests = row.guests || ''
      }
      form.value = next
    }
  }

  function saveDraft() {
    draftSaveError.value = ''
    try {
      const uid = auth.user?.id ?? null
      const json = JSON.stringify(serializeDraft())
      localStorage.setItem(draftStorageKey(uid), json)
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
      if (raw) {
        applyDraftPayload(JSON.parse(raw))
        return
      }
      if (uid != null) {
        const legacyRaw = localStorage.getItem(`${LEGACY_DRAFT_KEY}-u${uid}-extracurricular`)
        if (legacyRaw) {
          applyDraftPayload(JSON.parse(legacyRaw))
        }
      }
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

  function setBasisFile(file) {
    basisFileError.value = ''
    if (!file) {
      basisFile.value = null
      return
    }
    const max = 10 * 1024 * 1024
    if (file.size > max) {
      basisFileError.value = t('portal.recurring_plan.blocker_file_too_large')
      return
    }
    basisFile.value = file
  }

  function clearBasisFile() {
    basisFile.value = null
    basisFileError.value = ''
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

  const stepperSteps = computed(() => [
    { key: 'info', label: t('portal.recurring_plan.step_info') },
    { key: 'confirm', label: t('dispatch_wizard.steps.confirm') },
  ])

  onMounted(() => {
    loadDraftFromStorage()
    if (auth.user?.email && !form.value.requester_email) {
      form.value.requester_email = auth.user.email
    }
    if (auth.user?.name && !form.value.requester_name) {
      form.value.requester_name = auth.user.name
    }
  })

  onUnmounted(() => {
    clearTimeout(draftSaveFlashTimer)
    clearTimeout(requesterSearchTimer)
  })

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
    step,
    maxReachedStep,
    form,
    loading,
    error,
    created,
    basisFile,
    basisFileError,
    requesterSearchQ,
    requesterSearchResults,
    requesterSearchLoading,
    requesterSearchError,
    requesterDropdownOpen,
    requesterEmailTouched,
    step2RequesterEmailInvalid: computed(
      () => requesterEmailTouched.value && requesterEmailFormatInvalid.value,
    ),
    draftSaveError,
    draftSaveFlash,
    e1WeekdayOptions,
    occurrencePreview,
    stepBlockers,
    canGoNext,
    headerPrimaryDisabled,
    stepperSteps,
    toggleWeekday,
    setWeekdayPreset,
    nextStep,
    prevStep,
    goStepFromStepper,
    primaryAction,
    submitPlan,
    saveDraft,
    scheduleRequesterSearch,
    pickRequester,
    onRequesterPhoneInput,
    onRequesterEmailBlur,
    setBasisFile,
    clearBasisFile,
    formatIsoDateDisplay,
    normalizeTimeHhMm,
  }
}
