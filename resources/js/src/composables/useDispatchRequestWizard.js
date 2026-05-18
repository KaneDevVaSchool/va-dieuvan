import { computed, onMounted, onUnmounted, ref, watch, watchEffect } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  AcademicCapIcon,
  BriefcaseIcon,
  BuildingOffice2Icon,
  CubeIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '../store'
import { uploadAttachment } from '../api/attachments'
import {
  createDispatchRequest,
  createDispatchRequestTemplate,
  createPortalDispatchRequest,
  getDispatchRequest,
  patchDispatchRequestWizard,
} from '../api/requests'
import { getDispatchFormSettings } from '../api/dispatchSettings'
import { searchUsersForDispatchForm } from '../api/operational'
import { formatApiError } from '../api/http'
import { parseMoneyVnd } from '../util/money'
import { newIdempotencyKey } from '../util/idempotency'
import { formatDatetimeLocalAmPm } from '../util/datetime'
import {
  LEGACY_DRAFT_KEY,
  TARGET_OPTIONS,
  E1_WEEKDAY_KEYS,
  createInitialForm,
  emptyPassengerRow,
  emptyBusinessRow,
  emptyCargoRow,
  isPassengerRowFilled,
  isBusinessRowFilled,
  isCargoRowFilled,
  draftListStorageKey,
  draftItemStorageKey,
  draftActiveStorageKey,
  MAX_SAVED_DRAFTS,
  todayISODate,
} from './dispatchWizardConstants'
import { dispatchScheduleRowErrors } from './dispatchScheduleRowErrors'
import { buildStaffPrefixedPath as staffPath } from '../config/dispatchWebBase'

/**
 * @param {{ isPortal?: boolean }} [options]
 */
export function useDispatchRequestWizard(options = {}) {
  const { isPortal = false } = options
  const router = useRouter()
  const route = useRoute()
  const auth = useAuthStore()
  const { t, locale } = useI18n()

  const steps = computed(() => [
    { id: 'type', title: t('dispatch_wizard.steps.type') },
    { id: 'info', title: t('dispatch_wizard.steps.info') },
    { id: 'detail', title: t('dispatch_wizard.steps.detail') },
    { id: 'confirm', title: t('dispatch_wizard.steps.confirm') },
  ])
  const targetOptions = TARGET_OPTIONS
  const e1WeekdayOptions = computed(() =>
    E1_WEEKDAY_KEYS.map((k) => ({ k, label: t(`dispatch_wizard.weekday.${k}`) })),
  )

  function draftKeyForUser(userId) {
    return userId != null ? `${LEGACY_DRAFT_KEY}-u${userId}` : LEGACY_DRAFT_KEY
  }

  function currentDraftStorageKey() {
    return draftKeyForUser(auth.user?.id)
  }

  function uidOrNull() {
    return auth.user?.id ?? null
  }

  function buildDraftMeta(draftId, data) {
    const f = data.form || {}
    const trip = f.trip_type || ''
    const tripLabel = t(`dispatch_wizard.trip_short.${trip}`) || trip || '—'
    const rawLine = (f.purpose || '').trim().split(/\r?\n/)[0] || '—'
    const purposeLine = rawLine.length > 72 ? `${rawLine.slice(0, 69)}…` : rawLine
    return {
      id: draftId,
      savedAt: data.savedAt ?? Date.now(),
      tripLabel,
      purposeLine,
    }
  }

  function readDraftList(uid) {
    try {
      const raw = localStorage.getItem(draftListStorageKey(uid))
      if (!raw) return { items: [] }
      const parsed = JSON.parse(raw)
      if (parsed && Array.isArray(parsed.items)) return { items: parsed.items }
    } catch {
      /* ignore */
    }
    return { items: [] }
  }

  function writeDraftList(uid, items) {
    try {
      localStorage.setItem(draftListStorageKey(uid), JSON.stringify({ items }))
    } catch (e) {
      if (isQuotaExceededError(e)) throw e
    }
  }

  function refreshDraftsList() {
    const uid = uidOrNull()
    if (uid == null) {
      savedDraftsList.value = []
      return
    }
    const { items } = readDraftList(uid)
    savedDraftsList.value = [...items].sort((a, b) => b.savedAt - a.savedAt)
  }

  /** Chuyển khóa nháp đơn (v1) sang danh sách nhiều bản (v2). */
  function migrateV1SingleDraftToMulti(uid) {
    const listKey = draftListStorageKey(uid)
    if (localStorage.getItem(listKey)) {
      const v1Key = draftKeyForUser(uid)
      if (localStorage.getItem(v1Key)) localStorage.removeItem(v1Key)
      return
    }
    const raw = localStorage.getItem(draftKeyForUser(uid))
    if (!raw) return
    try {
      const data = JSON.parse(raw)
      const id = `d-${Date.now()}`
      localStorage.setItem(draftItemStorageKey(uid, id), raw)
      const meta = buildDraftMeta(id, data)
      writeDraftList(uid, [meta])
      localStorage.setItem(draftActiveStorageKey(uid), id)
      localStorage.removeItem(draftKeyForUser(uid))
    } catch {
      /* ignore */
    }
  }

  function applyDraftPayload(data) {
    if (data.form) form.value = { ...createInitialForm(), ...data.form }
    if (Array.isArray(data.passengerRows) && data.passengerRows.length) {
      passengerRows.value = data.passengerRows.map((r) => ({ ...emptyPassengerRow(), ...r }))
    }
    if (Array.isArray(data.businessRows) && data.businessRows.length) {
      businessRows.value = data.businessRows.map((r) => ({ ...emptyBusinessRow(), ...r }))
    }
    if (Array.isArray(data.cargoRows) && data.cargoRows.length) {
      cargoRows.value = data.cargoRows.map((r) => ({ ...emptyCargoRow(), ...r }))
    }
    if (typeof data.step === 'number') step.value = data.step
    if (typeof data.maxReachedStep === 'number') {
      maxReachedStep.value = Math.max(data.maxReachedStep, step.value)
    }
    draftSavedAt.value = data.savedAt ?? Date.now()
    trimPassengerRowsInPlace()
    trimBusinessRowsInPlace()
    trimCargoRowsInPlace()
    hasDraftSnapshot.value = true
    hydrateRequestedDateTimeFromState()
    queueMicrotask(() => {
      syncUrgentFromSchedule()
      if (!urgentAutoActive.value) {
        urgentManualDesired.value = !!form.value.is_urgent
      }
    })
    requesterEmailTouched.value = false
    coordinatorEmailTouched.value = false
  }

  const step = ref(0)
  const maxReachedStep = ref(0)
  const loading = ref(false)
  const error = ref('')

  const created = ref(null)
  /** Khi đặt lại từ phiếu đã duyệt: PATCH wizard thay vì POST mới. */
  const replaceDraftRequestId = ref(null)
  const draftSavedAt = ref(null)
  const lastAutoSavedAt = ref(null)
  const hasDraftSnapshot = ref(false)
  const clearDraftModalOpen = ref(false)
  const submitResultModalOpen = ref(false)
  const submitResultOk = ref(false)
  const submitResultDetail = ref('')

  function closeSubmitResultModal() {
    submitResultModalOpen.value = false
  }
  /** Id bản nháp đang mở (chuỗi); null = phiên làm việc mới, lưu sẽ tạo bản mới. */
  const activeDraftId = ref(null)
  /** Meta cho danh sách (mới nhất trước). */
  const savedDraftsList = ref([])
  const draftsModalOpen = ref(false)
  const draftSaveError = ref('')
  const draftSaveFlash = ref(false)
  let draftSaveFlashTimer = null

  const form = ref(createInitialForm())
  const requestedDateTime = ref(
    (() => {
      const d = form.value.date_needed || form.value.proposed_date || todayISODate()
      return d ? `${d}T00:00` : ''
    })(),
  )

  /** Ngưỡng gấp (giờ): từ API `dispatch-form-settings`. */
  const urgentThresholds = ref({
    passenger: 72,
    cargo: 24,
  })
  const dispatchFormSettingsLoading = ref(false)
  const dispatchFormSettingsError = ref('')
  /** Theo lịch: trong ngưỡng → ép Gấp, khoá tắt. */
  const urgentAutoActive = ref(false)
  /** Ý định thủ công khi không bị auto. */
  const urgentManualDesired = ref(false)

  const basisFile = ref(null)
  const basisFileInput = ref(null)
  const basisDragOver = ref(false)
  const basisFileError = ref('')

  /** Chỉ hiện lỗi định dạng sau blur (ô email coordinator). Logic chặn bước vẫn dùng `coordinatorEmailFormatInvalid`. */
  const coordinatorEmailTouched = ref(false)
  /** Chỉ hiện lỗi định dạng sau blur (ô email người đề nghị). Logic chặn bước vẫn dùng `requesterEmailFormatInvalid`. */
  const requesterEmailTouched = ref(false)

  let requesterSearchTimer = null
  const requesterSearchQ = ref('')
  const requesterSearchResults = ref([])
  const requesterSearchLoading = ref(false)
  const requesterDropdownOpen = ref(false)
  const requesterSearchError = ref('')
  let requesterBlurTimer = null

  let coordinatorSearchTimer = null
  const coordinatorSearchQ = ref('')
  const coordinatorSearchResults = ref([])
  const coordinatorSearchLoading = ref(false)
  const coordinatorDropdownOpen = ref(false)
  const coordinatorSearchError = ref('')
  let coordinatorBlurTimer = null

  const passengerRows = ref([emptyPassengerRow()])
  const businessRows = ref([emptyBusinessRow()])
  const cargoRows = ref([emptyCargoRow()])

  /** Bước 3: form thẻ — không cho Next khi có lỗi inline hoặc danh sách rỗng (đồng bộ từ DispatchWizardStep3). */
  const detailStepSchedulesValid = ref(true)

  const tripTypeOptions = computed(() => [
    {
      value: 'door_to_door',
      label: t('dispatch_wizard.trip_type.door_to_door.label'),
      hint: t('dispatch_wizard.trip_type.door_to_door.hint'),
      icon: AcademicCapIcon,
      iconClass: 'text-violet-600',
      selectedClass: 'border-violet-500 bg-violet-50 shadow-sm ring-1 ring-violet-200',
    },
    {
      value: 'point_to_point',
      label: t('dispatch_wizard.trip_type.point_to_point.label'),
      hint: t('dispatch_wizard.trip_type.point_to_point.hint'),
      icon: BuildingOffice2Icon,
      iconClass: 'text-sky-600',
      selectedClass: 'border-sky-500 bg-sky-50 shadow-sm ring-1 ring-sky-200',
    },
    {
      value: 'business',
      label: t('dispatch_wizard.trip_type.business.label'),
      hint: t('dispatch_wizard.trip_type.business.hint'),
      icon: BriefcaseIcon,
      iconClass: 'text-emerald-600',
      selectedClass: 'border-emerald-500 bg-emerald-50 shadow-sm ring-1 ring-emerald-200',
    },
    {
      value: 'cargo',
      label: t('dispatch_wizard.trip_type.cargo.label'),
      hint: t('dispatch_wizard.trip_type.cargo.hint'),
      icon: CubeIcon,
      iconClass: 'text-orange-600',
      selectedClass: 'border-orange-500 bg-orange-50 shadow-sm ring-1 ring-orange-200',
      badge: t('dispatch_wizard.trip_type.cargo.badge'),
    },
  ])

  const isCargo = computed(() => form.value.trip_type === 'cargo')
  const isPointToPointTrip = computed(() => form.value.trip_type === 'point_to_point')

  function trimPassengerRowsInPlace() {
    const kept = passengerRows.value.filter(isPassengerRowFilled)
    passengerRows.value = kept.length ? kept : [emptyPassengerRow()]
  }

  function trimBusinessRowsInPlace() {
    const kept = businessRows.value.filter(isBusinessRowFilled)
    businessRows.value = kept.length ? kept : [emptyBusinessRow()]
  }

  function trimCargoRowsInPlace() {
    const kept = cargoRows.value.filter(isCargoRowFilled)
    cargoRows.value = kept.length ? kept : [emptyCargoRow()]
  }

  /** Chỉ giữ chữ số, tối đa 11 ký tự; hỗ trợ +84 → 0… */
  function sanitizeVnPhoneDigits(raw) {
    if (raw == null) return ''
    let d = String(raw).replace(/\D/g, '')
    if (d.startsWith('84') && d.length >= 10) d = `0${d.slice(2)}`
    if (d.length > 11) d = d.slice(0, 11)
    return d
  }

  /** Chuỗi yyyy-mm-dd (input type=date) → timestamp nửa đêm local. */
  function isoLocalDateMs(iso) {
    const m = String(iso ?? '').match(/^(\d{4})-(\d{2})-(\d{2})$/)
    if (!m) return null
    const t = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3])).getTime()
    return Number.isNaN(t) ? null : t
  }

  function isDateNeededBeforeProposed(proposedDate, dateNeeded) {
    const a = isoLocalDateMs(proposedDate)
    const b = isoLocalDateMs(dateNeeded)
    if (a == null || b == null) return false
    return b < a
  }

  function isPlausibleEmail(s) {
    const t = String(s ?? '').trim()
    if (!t) return false
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(t)
  }

  function formatOrgUnitFromUser(u) {
    const parts = [u.unit_name, u.department_name].filter(Boolean)
    if (parts.length) return parts.join(' — ')
    if (u.employee_code) return t('dispatch_wizard.emp_code', { code: u.employee_code })
    return ''
  }

  function formatFileSize(n) {
    if (n == null || Number.isNaN(n)) return ''
    if (n < 1024) return `${n} B`
    if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
    return `${(n / (1024 * 1024)).toFixed(1)} MB`
  }

  function formatDraftTime(ts) {
    try {
      const loc = locale.value === 'vi' ? 'vi-VN' : 'en-US'
      return new Date(ts).toLocaleString(loc)
    } catch {
      return '—'
    }
  }

  function openDatePickerFromInput(evt) {
    const inp = evt?.currentTarget
    if (!inp || inp.type !== 'date') return
    if (typeof inp.showPicker === 'function') {
      try {
        inp.showPicker()
        return
      } catch {
        /* fallback */
      }
    }
    try {
      inp.focus({ preventScroll: true })
    } catch {
      inp.focus()
    }
  }

  function toggleE1Weekday(k) {
    const w = form.value.e1_weekdays
    if (!w || typeof w[k] !== 'boolean') return
    w[k] = !w[k]
  }

  function onRequesterPhoneInput(e) {
    form.value.requester_phone = sanitizeVnPhoneDigits(e?.target?.value)
  }

  function onCoordinatorPhoneInput(e) {
    form.value.coordinator_phone = sanitizeVnPhoneDigits(e?.target?.value)
  }

  function onRequesterEmailBlur() {
    requesterEmailTouched.value = true
  }

  function onCoordinatorEmailBlur() {
    coordinatorEmailTouched.value = true
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
      requesterSearchError.value = ''
      requesterDropdownOpen.value = false
      return
    }
    requesterSearchLoading.value = true
    requesterDropdownOpen.value = true
    try {
      requesterSearchResults.value = await searchUsersForDispatchForm(q)
      requesterSearchError.value = ''
      requesterDropdownOpen.value = true
    } catch (e) {
      requesterSearchResults.value = []
      requesterSearchError.value = formatApiError(e, t('dispatch_wizard.search_staff_fail'))
      requesterDropdownOpen.value = true
    } finally {
      requesterSearchLoading.value = false
    }
  }

  function onRequesterSearchFocus() {
    clearTimeout(requesterBlurTimer)
    if (requesterSearchQ.value.trim().length >= 2) requesterDropdownOpen.value = true
  }

  function onRequesterSearchBlur() {
    requesterBlurTimer = setTimeout(() => {
      requesterDropdownOpen.value = false
    }, 200)
  }

  function pickRequester(u) {
    requesterSearchError.value = ''
    form.value.requester_name = u.name || ''
    form.value.requester_email = u.email || ''
    form.value.requester_phone = sanitizeVnPhoneDigits(u.phone || '')
    form.value.requester_unit = formatOrgUnitFromUser(u)
    requesterSearchQ.value = u.name || ''
    requesterSearchResults.value = []
    requesterDropdownOpen.value = false
    requesterEmailTouched.value = false
  }

  function scheduleCoordinatorSearch() {
    coordinatorSearchError.value = ''
    clearTimeout(coordinatorSearchTimer)
    coordinatorSearchTimer = setTimeout(runCoordinatorSearch, 350)
  }

  async function runCoordinatorSearch() {
    const q = coordinatorSearchQ.value.trim()
    if (q.length < 2) {
      coordinatorSearchResults.value = []
      coordinatorSearchError.value = ''
      coordinatorDropdownOpen.value = false
      return
    }
    coordinatorSearchLoading.value = true
    coordinatorDropdownOpen.value = true
    try {
      coordinatorSearchResults.value = await searchUsersForDispatchForm(q)
      coordinatorSearchError.value = ''
      coordinatorDropdownOpen.value = true
    } catch (e) {
      coordinatorSearchResults.value = []
      coordinatorSearchError.value = formatApiError(e, t('dispatch_wizard.search_staff_fail'))
      coordinatorDropdownOpen.value = true
    } finally {
      coordinatorSearchLoading.value = false
    }
  }

  function onCoordinatorSearchFocus() {
    clearTimeout(coordinatorBlurTimer)
    if (coordinatorSearchQ.value.trim().length >= 2) coordinatorDropdownOpen.value = true
  }

  function onCoordinatorSearchBlur() {
    coordinatorBlurTimer = setTimeout(() => {
      coordinatorDropdownOpen.value = false
    }, 200)
  }

  function pickCoordinator(u) {
    coordinatorSearchError.value = ''
    form.value.coordinator_name = u.name || ''
    form.value.coordinator_email = u.email || ''
    form.value.coordinator_phone = sanitizeVnPhoneDigits(u.phone || '')
    coordinatorSearchQ.value = u.name || ''
    coordinatorSearchResults.value = []
    coordinatorDropdownOpen.value = false
    coordinatorEmailTouched.value = false
  }

  const ALLOWED_BASIS_MIME = new Set([
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/pjpeg',
  ])
  const ALLOWED_BASIS_EXT = new Set(['.pdf', '.jpg', '.jpeg', '.png', '.webp'])

  function isAllowedBasisFile(f) {
    if (!f) return false
    const type = String(f.type || '')
      .trim()
      .toLowerCase()
    if (type && ALLOWED_BASIS_MIME.has(type)) return true
    const name = String(f.name || '')
    const dot = name.lastIndexOf('.')
    if (dot < 0) return false
    return ALLOWED_BASIS_EXT.has(name.slice(dot).toLowerCase())
  }

  function onBasisFileChange(e) {
    basisFileError.value = ''
    const f = e?.target?.files?.[0]
    if (!f) return
    if (f.size > 10 * 1024 * 1024) {
      basisFile.value = null
      basisFileError.value = t('dispatch_wizard.file_too_large')
      if (basisFileInput.value) basisFileInput.value.value = ''
      return
    }
    if (!isAllowedBasisFile(f)) {
      basisFile.value = null
      basisFileError.value = t('dispatch_wizard.file_type_not_allowed')
      if (basisFileInput.value) basisFileInput.value.value = ''
      return
    }
    basisFile.value = f
  }

  function onBasisDrop(e) {
    basisDragOver.value = false
    basisFileError.value = ''
    const f = e?.dataTransfer?.files?.[0]
    if (!f) return
    if (f.size > 10 * 1024 * 1024) {
      basisFileError.value = t('dispatch_wizard.file_too_large')
      return
    }
    if (!isAllowedBasisFile(f)) {
      basisFileError.value = t('dispatch_wizard.file_type_not_allowed')
      return
    }
    basisFile.value = f
  }

  function clearBasisFile() {
    basisFile.value = null
    basisFileError.value = ''
    if (basisFileInput.value) basisFileInput.value.value = ''
  }

  const draftLabel = computed(() => {
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    if (created.value) return t('dispatch_wizard.draft.submitted')
    if (activeDraftId.value && savedDraftsList.value.length) {
      const m = savedDraftsList.value.find((x) => x.id === activeDraftId.value)
      if (m) {
        try {
          return t('dispatch_wizard.draft.label_with_trip', {
            trip: m.tripLabel,
            time: new Date(m.savedAt).toLocaleString(loc),
          })
        } catch {
          return t('dispatch_wizard.draft.draft')
        }
      }
    }
    if (draftSavedAt.value) {
      try {
        return t('dispatch_wizard.draft.label_saved', {
          time: new Date(draftSavedAt.value).toLocaleString(loc),
        })
      } catch {
        return t('dispatch_wizard.draft.draft')
      }
    }
    return t('dispatch_wizard.draft.new_session')
  })

  const formattedRequestedDateTime = computed(() => formatDatetimeLocalAmPm(requestedDateTime.value))

  const computedDepartAt = computed(() => requestedDateTime.value?.trim() || '')

  function syncRowDepartTimesFromRequested(isoLocal) {
    const v = isoLocal != null ? String(isoLocal).trim() : ''
    for (const r of passengerRows.value) {
      r.depart_at = v
    }
    for (const r of businessRows.value) {
      r.depart_at = v
    }
    for (const r of cargoRows.value) {
      r.pickup_at = v
    }
  }

  function applyRequestedDateTimeToFormAndRows() {
    const raw = requestedDateTime.value?.trim() ?? ''
    form.value.date_needed = raw.length >= 10 ? raw.slice(0, 10) : ''
    syncRowDepartTimesFromRequested(raw)
  }

  function hydrateRequestedDateTimeFromState() {
    for (const r of passengerRows.value) {
      if (r.depart_at?.trim()) {
        requestedDateTime.value = r.depart_at.trim()
        return
      }
    }
    for (const r of businessRows.value) {
      if (r.depart_at?.trim()) {
        requestedDateTime.value = r.depart_at.trim()
        return
      }
    }
    for (const r of cargoRows.value) {
      if (r.pickup_at?.trim()) {
        requestedDateTime.value = r.pickup_at.trim()
        return
      }
    }
    const d = form.value.date_needed || form.value.proposed_date || todayISODate()
    requestedDateTime.value = d ? `${d}T00:00` : ''
  }

  // Đồng bộ form.date_needed và giờ trên các dòng khi đổi requestedDateTime;
  // chỉ theo dõi độ dài mảng dòng để không ghi đè chỉnh sửa chi tiết từng ô.
  watch(
    () => ({
      dt: requestedDateTime.value,
      pl: passengerRows.value.length,
      bl: businessRows.value.length,
      cl: cargoRows.value.length,
    }),
    () => {
      applyRequestedDateTimeToFormAndRows()
    },
    { flush: 'post', immediate: true },
  )

  function thresholdHoursForTripType(tripType) {
    return tripType === 'cargo' ? urgentThresholds.value.cargo : urgentThresholds.value.passenger
  }

  /** Auto Gấp theo ngưỡng dispatch-settings: ưu tiên `depart_at`; chưa có thì dùng 00:00 ngày `date_needed` (cùng quy tắc khoảng cách như backend). */
  function syncUrgentFromSchedule() {
    const threshold = thresholdHoursForTripType(form.value.trip_type)
    const departRaw = computedDepartAt.value?.trim()
    if (departRaw) {
      const departMs = new Date(departRaw).getTime()
      if (!Number.isFinite(departMs)) return
      const hoursUntilDepart = (departMs - Date.now()) / (3600 * 1000)
      if (hoursUntilDepart >= 0 && hoursUntilDepart <= threshold) {
        urgentAutoActive.value = true
        form.value.is_urgent = true
      } else {
        urgentAutoActive.value = false
        form.value.is_urgent = urgentManualDesired.value
      }
      return
    }

    const dayStartMs = isoLocalDateMs(form.value.date_needed)
    if (dayStartMs == null) {
      if (urgentAutoActive.value) {
        urgentAutoActive.value = false
        form.value.is_urgent = urgentManualDesired.value
      }
      return
    }
    const hoursUntilDayStart = (dayStartMs - Date.now()) / (3600 * 1000)
    if (hoursUntilDayStart >= 0 && hoursUntilDayStart <= threshold) {
      urgentAutoActive.value = true
      form.value.is_urgent = true
    } else {
      urgentAutoActive.value = false
      form.value.is_urgent = urgentManualDesired.value
    }
  }

  function toggleUrgentManual() {
    if (urgentAutoActive.value) return
    urgentManualDesired.value = !urgentManualDesired.value
    form.value.is_urgent = urgentManualDesired.value
  }

  const appliedUrgentThresholdHours = computed(() =>
    thresholdHoursForTripType(form.value.trip_type),
  )

  watch(
    () => ({
      depart: computedDepartAt.value,
      date_needed: form.value.date_needed,
      trip_type: form.value.trip_type,
      pTh: urgentThresholds.value.passenger,
      cTh: urgentThresholds.value.cargo,
    }),
    syncUrgentFromSchedule,
    { flush: 'post', immediate: true },
  )

  function parseMoney(v) {
    return parseMoneyVnd(v)
  }

  function parseGuests(v) {
    const n = Number(String(v ?? '').trim().replace(/\s/g, ''))
    return Number.isFinite(n) ? n : 0
  }

  function rowLineTotal(r) {
    return parseMoney(r.unit_price) + parseMoney(r.extra_fee)
  }

  const passengerE1Total = computed(() =>
    passengerRows.value.reduce((s, r) => s + rowLineTotal(r), 0),
  )

  const passengerE2Total = computed(() =>
    businessRows.value.reduce((s, r) => s + rowLineTotal(r), 0),
  )

  const passengerTotal = computed(() => passengerE1Total.value + passengerE2Total.value)

  const passengerGuestTotal = computed(
    () =>
      passengerRows.value.reduce((s, r) => s + parseGuests(r.guests), 0) +
      businessRows.value.reduce((s, r) => s + parseGuests(r.guests), 0),
  )

  const cargoTotal = computed(() =>
    cargoRows.value.reduce(
      (s, r) => s + (r.name?.trim() ? parseMoney(r.cost) : 0),
      0,
    ),
  )

  const extraCosts = computed(() => {
    let x = 0
    if (form.value.need_porters) x += parseMoney(form.value.porter_cost)
    if (form.value.interprovincial) x += parseMoney(form.value.interprovincial_cost)
    return x
  })

  function formatCurrency(n) {
    if (n == null || Number.isNaN(Number(n))) return '—'
    try {
      const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
      return new Intl.NumberFormat(loc, { style: 'currency', currency: 'VND' }).format(Number(n))
    } catch {
      return `${n} ₫`
    }
  }

  const step2DateOrderInvalid = computed(() =>
    isDateNeededBeforeProposed(form.value.proposed_date, form.value.date_needed),
  )

  const requesterEmailFormatInvalid = computed(
    () => !!form.value.requester_email?.trim() && !isPlausibleEmail(form.value.requester_email),
  )

  const coordinatorEmailFormatInvalid = computed(
    () => !!form.value.coordinator_email?.trim() && !isPlausibleEmail(form.value.coordinator_email),
  )

  /** Viền/ gợi ý chỉ sau blur. Logic chặn bước dùng *FormatInvalid tương ứng. */
  const step2RequesterEmailInvalid = computed(
    () => requesterEmailTouched.value && requesterEmailFormatInvalid.value,
  )

  const step2CoordinatorEmailInvalid = computed(
    () => coordinatorEmailTouched.value && coordinatorEmailFormatInvalid.value,
  )

  const canGoNext = computed(() => {
    if (step.value === 0) return !!form.value.trip_type
    if (step.value === 1) {
      return (
        !!form.value.requester_name?.trim() &&
        !!form.value.requester_email?.trim() &&
        !requesterEmailFormatInvalid.value &&
        !coordinatorEmailFormatInvalid.value &&
        !!form.value.purpose?.trim() &&
        !!form.value.proposed_date &&
        !!form.value.date_needed &&
        !step2DateOrderInvalid.value &&
        (!form.value.is_urgent || !!form.value.urgent_reason?.trim())
      )
    }
    if (step.value === 2) {
      if (!detailStepSchedulesValid.value) return false
      if (!computedDepartAt.value?.trim()) return false
      if (isCargo.value) {
        return cargoRows.value.some((r) => r.name?.trim())
      }
      if (isPointToPointTrip.value) {
        return passengerRows.value.some(isPassengerRowFilled)
      }
      return (
        passengerRows.value.some(isPassengerRowFilled) || businessRows.value.some(isBusinessRowFilled)
      )
    }
    return true
  })

  function goStep(i) {
    if (i <= maxReachedStep.value) step.value = i
  }

  function nextStep() {
    if (!canGoNext.value) return
    if (step.value < 3) step.value++
  }

  function addPassengerRow() {
    passengerRows.value.push(emptyPassengerRow())
  }

  function removePassengerRow(i) {
    passengerRows.value.splice(i, 1)
  }

  function addBusinessRow() {
    businessRows.value.push(emptyBusinessRow())
  }

  function removeBusinessRow(i) {
    businessRows.value.splice(i, 1)
  }

  function addCargoRow() {
    cargoRows.value.push(emptyCargoRow())
  }

  function removeCargoRow(i) {
    cargoRows.value.splice(i, 1)
  }

  const canSubmitApi = computed(() => !!computedDepartAt.value?.trim())

  /** Bước 3 đã ẩn khi vào confirm; cần tính lại để nút Submit & validate API không báo sót chuyến về. */
  function schedulesPassDispatchErrorsSnapshot() {
    const checkAll = (rows, variant, isFilled) =>
      rows.every((r) => !isFilled(r) || Object.keys(dispatchScheduleRowErrors(r, variant)).length === 0)

    const tt = form.value.trip_type
    if (tt === 'cargo') return checkAll(cargoRows.value, 'cargo', isCargoRowFilled)
    if (tt === 'point_to_point') return checkAll(passengerRows.value, 'passenger', isPassengerRowFilled)
    if (tt === 'business') return checkAll(businessRows.value, 'business', isBusinessRowFilled)
    return (
      checkAll(passengerRows.value, 'passenger', isPassengerRowFilled) &&
      checkAll(businessRows.value, 'business', isBusinessRowFilled)
    )
  }

  const schedulesPassForSubmit = computed(() => schedulesPassDispatchErrorsSnapshot())

  function pushConfirmRowIssues(msgs, rows, variant, sectionKey) {
    rows.forEach((row, idx) => {
      const filled =
        variant === 'cargo'
          ? isCargoRowFilled(row)
          : variant === 'business'
            ? isBusinessRowFilled(row)
            : isPassengerRowFilled(row)
      if (!filled) return
      const err = dispatchScheduleRowErrors(row, variant)
      const n = idx + 1
      if (err.time_required) {
        msgs.push(t('dispatch_wizard.confirm.issue_row_time_required', { section: t(sectionKey), n }))
      }
      if (err.return_time_required) {
        msgs.push(t('dispatch_wizard.confirm.issue_row_return_time', { section: t(sectionKey), n }))
      }
      if (err.return_place) {
        msgs.push(t('dispatch_wizard.confirm.issue_row_return_place', { section: t(sectionKey), n }))
      }
      if (err.return_time) {
        msgs.push(t('dispatch_wizard.confirm.issue_row_return_order', { section: t(sectionKey), n }))
      }
      if (err.passengers) {
        msgs.push(t('dispatch_wizard.confirm.issue_row_guests', { section: t(sectionKey), n }))
      }
    })
  }

  /** Gợi ý kiểm tra trước khi gửi — không thay cho validateBeforeApi khi submit. */
  const confirmReviewIssues = computed(() => {
    const msgs = []
    const f = form.value
    if (!f.trip_type) msgs.push(t('dispatch_wizard.validate.pick_type'))
    if (!f.requester_name?.trim()) msgs.push(t('dispatch_wizard.confirm.issue_requester_name'))
    if (!f.requester_email?.trim()) msgs.push(t('dispatch_wizard.confirm.issue_requester_email'))
    else if (!isPlausibleEmail(f.requester_email)) msgs.push(t('dispatch_wizard.confirm.issue_requester_email'))
    if (coordinatorEmailFormatInvalid.value) msgs.push(t('dispatch_wizard.validate.coord_email'))
    if (!f.purpose?.trim()) msgs.push(t('dispatch_wizard.confirm.issue_purpose'))
    if (!f.proposed_date || !f.date_needed) msgs.push(t('dispatch_wizard.confirm.issue_dates'))
    if (step2DateOrderInvalid.value) msgs.push(t('dispatch_wizard.validate.date_order'))
    if (f.is_urgent && !f.urgent_reason?.trim()) msgs.push(t('dispatch_wizard.validate.urgent_reason'))

    if (isCargo.value) {
      if (!cargoRows.value.some((r) => r.name?.trim())) msgs.push(t('dispatch_wizard.validate.cargo_row'))
      pushConfirmRowIssues(msgs, cargoRows.value, 'cargo', 'dispatch_wizard.confirm.sec_cargo')
    } else if (f.trip_type === 'point_to_point') {
      if (!passengerRows.value.some(isPassengerRowFilled)) msgs.push(t('dispatch_wizard.validate.p2p_row'))
      pushConfirmRowIssues(msgs, passengerRows.value, 'passenger', 'dispatch_wizard.confirm.sec_e1')
    } else if (f.trip_type === 'business') {
      if (!businessRows.value.some(isBusinessRowFilled)) msgs.push(t('dispatch_wizard.validate.detail_row'))
      pushConfirmRowIssues(msgs, businessRows.value, 'business', 'dispatch_wizard.confirm.sec_e2')
    } else if (f.trip_type === 'door_to_door') {
      if (
        !passengerRows.value.some(isPassengerRowFilled) &&
        !businessRows.value.some(isBusinessRowFilled)
      ) {
        msgs.push(t('dispatch_wizard.validate.detail_row'))
      }
      pushConfirmRowIssues(msgs, passengerRows.value, 'passenger', 'dispatch_wizard.confirm.sec_e1')
      pushConfirmRowIssues(msgs, businessRows.value, 'business', 'dispatch_wizard.confirm.sec_e2')
    }

    if (!detailStepSchedulesValid.value) msgs.push(t('dispatch_wizard.confirm.issue_schedule_invalid'))
    if (!computedDepartAt.value?.trim()) msgs.push(t('dispatch_wizard.validate.depart_time'))

    return msgs
  })

  const headerPrimaryLabel = computed(() => {
    if (loading.value) return t('dispatch_wizard.header.sending')
    if (step.value < 3) return t('dispatch_wizard.header.to_confirm')
    return t('dispatch_wizard.header.submit')
  })

  const headerPrimaryDisabled = computed(() => {
    if (loading.value) return true
    if (step.value < 3) return !canGoNext.value
    return !canSubmitApi.value || !schedulesPassForSubmit.value
  })

  function computeApiOriginDestination() {
    if (isCargo.value) {
      const r = cargoRows.value.find((x) => x.name?.trim())
      return {
        origin: r?.pickup_place?.trim() || '',
        destination: r?.delivery_place?.trim() || '',
      }
    }
    const r =
      passengerRows.value.find((x) => x.pickup?.trim() || x.dropoff?.trim()) ||
      (!isPointToPointTrip.value
        ? businessRows.value.find((x) => x.pickup?.trim() || x.dropoff?.trim())
        : undefined)
    return {
      origin: r?.pickup?.trim() || '',
      destination: r?.dropoff?.trim() || '',
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

  function buildIsoWeekdaysFromE1(e1) {
    const map = { mon: 1, tue: 2, wed: 3, thu: 4, fri: 5, sat: 6, sun: 7 }
    const out = []
    if (!e1 || typeof e1 !== 'object') return out
    for (const [k, iso] of Object.entries(map)) {
      if (e1[k]) out.push(iso)
    }
    return out
  }

  async function hydrateFromPendingReplace(id) {
    replaceDraftRequestId.value = null
    loading.value = true
    error.value = ''
    try {
      const dr = await getDispatchRequest(id)
      if (dr.status !== 'pending') {
        error.value = t('dispatch_wizard.replace.invalid_status')
        return
      }
      replaceDraftRequestId.value = id
      const snap = dr.wizard_snapshot
      if (snap && typeof snap === 'object') {
        applyDraftPayload({
          form: { ...createInitialForm(), ...(snap.form || {}) },
          passengerRows: snap.passengerRows,
          businessRows: snap.businessRows,
          cargoRows: snap.cargoRows,
          step: 0,
          maxReachedStep: 0,
        })
      }
      form.value.recurring_enabled = false
      created.value = null
    } catch (e) {
      error.value = formatApiError(e, t('dispatch_wizard.replace.load_fail'))
    } finally {
      loading.value = false
    }
  }

  let submitInFlight = false

  function primaryAction() {
    if (loading.value || submitInFlight) return
    if (step.value < 3) nextStep()
    else doSubmit()
  }

  function validateBeforeApi() {
    if (!form.value.trip_type) {
      step.value = 0
      return t('dispatch_wizard.validate.pick_type')
    }
    if (
      !form.value.requester_name?.trim() ||
      !form.value.requester_email?.trim() ||
      !isPlausibleEmail(form.value.requester_email) ||
      !form.value.purpose?.trim() ||
      !form.value.proposed_date ||
      !form.value.date_needed
    ) {
      step.value = 1
      return t('dispatch_wizard.validate.step2')
    }
    if (coordinatorEmailFormatInvalid.value) {
      step.value = 1
      return t('dispatch_wizard.validate.coord_email')
    }
    if (isDateNeededBeforeProposed(form.value.proposed_date, form.value.date_needed)) {
      step.value = 1
      return t('dispatch_wizard.validate.date_order')
    }
    if (form.value.is_urgent && !form.value.urgent_reason?.trim()) {
      step.value = 1
      return t('dispatch_wizard.validate.urgent_reason')
    }
    if (isCargo.value) {
      if (!cargoRows.value.some((r) => r.name?.trim())) {
        step.value = 2
        return t('dispatch_wizard.validate.cargo_row')
      }
    } else if (form.value.trip_type === 'point_to_point') {
      if (!passengerRows.value.some(isPassengerRowFilled)) {
        step.value = 2
        return t('dispatch_wizard.validate.p2p_row')
      }
    } else if (
      !passengerRows.value.some(isPassengerRowFilled) &&
      !businessRows.value.some(isBusinessRowFilled)
    ) {
      step.value = 2
      return t('dispatch_wizard.validate.detail_row')
    }
    if (!computedDepartAt.value?.trim()) {
      step.value = 2
      return t('dispatch_wizard.validate.depart_time')
    }
    if (!schedulesPassDispatchErrorsSnapshot()) {
      step.value = 2
      return t('dispatch_wizard.confirm.issue_schedule_invalid')
    }
    if (
      isPortal &&
      form.value.recurring_enabled &&
      form.value.trip_type === 'point_to_point' &&
      form.value.point_purpose_kind === 'extracurricular'
    ) {
      step.value = 1
      return t('portal.create.recurring_not_supported')
    }
    const wantsRecurring =
      form.value.recurring_enabled &&
      form.value.trip_type === 'point_to_point' &&
      form.value.point_purpose_kind === 'extracurricular' &&
      !replaceDraftRequestId.value
    if (wantsRecurring) {
      const hasWd = Object.values(form.value.e1_weekdays || {}).some(Boolean)
      if (!hasWd) {
        step.value = 2
        return t('dispatch_wizard.validate.recurring_weekday')
      }
    }
    return ''
  }

  let autosaveTimer = null

  onUnmounted(() => {
    clearTimeout(draftSaveFlashTimer)
    clearTimeout(autosaveTimer)
  })

  async function doSubmit() {
    if (created.value?.id || submitInFlight || loading.value) return
    submitInFlight = true
    loading.value = true
    try {
      const v = validateBeforeApi()
      if (v) {
        error.value = v
        submitResultOk.value = false
        submitResultDetail.value = v
        submitResultModalOpen.value = true
        return
      }
      error.value = ''
      created.value = null
      const idempotencyKey = newIdempotencyKey()
      try {
      const { origin, destination } = computeApiOriginDestination()
      const freeNotes = form.value.free_notes?.trim() || ''
      const formSnap = { ...form.value }
      if (basisFile.value?.name) {
        formSnap.basisFileName = basisFile.value.name
      }
      const wizard_snapshot = {
        form: formSnap,
        passengerRows: passengerRows.value.map((r) => ({ ...r })),
        businessRows: businessRows.value.map((r) => ({ ...r })),
        cargoRows: cargoRows.value.map((r) => ({ ...r })),
      }
      const payload = {
        trip_type: form.value.trip_type,
        source_channel: isPortal ? 'portal' : form.value.source_channel,
        origin: origin || undefined,
        destination: destination || undefined,
        depart_at: toIsoMaybe(computedDepartAt.value),
        arrive_by: null,
        passenger_count: isCargo.value
          ? null
          : passengerGuestTotal.value > 0
            ? Math.round(passengerGuestTotal.value)
            : null,
        notes: freeNotes || undefined,
        is_urgent: !!form.value.is_urgent,
        urgent_reason: form.value.is_urgent ? (form.value.urgent_reason?.trim() || undefined) : undefined,
        wizard_snapshot,
      }
      Object.keys(payload).forEach((k) => (payload[k] === '' ? delete payload[k] : null))
      const wantsRecurring =
        !isPortal &&
        form.value.recurring_enabled &&
        form.value.trip_type === 'point_to_point' &&
        form.value.point_purpose_kind === 'extracurricular' &&
        !replaceDraftRequestId.value

      let createdResult = null
      if (wantsRecurring) {
        const tmplPayload = {
          ...payload,
          recurrence_rule: {
            freq: 'weekly',
            interval: 1,
            byweekday: buildIsoWeekdaysFromE1(form.value.e1_weekdays),
          },
          recurrence_end_date: form.value.recurrence_end_date?.trim() || undefined,
        }
        const pack = await createDispatchRequestTemplate(tmplPayload, { idempotencyKey })
        createdResult = pack?.dispatch_request ?? null
      } else if (replaceDraftRequestId.value && !isPortal) {
        const rid = replaceDraftRequestId.value
        createdResult = await patchDispatchRequestWizard(rid, payload, { idempotencyKey })
        replaceDraftRequestId.value = null
        try {
          const q = { ...route.query }
          delete q.replace
          delete q.clone
          await router.replace({ path: route.path, query: q })
        } catch {
          /* ignore */
        }
      } else {
        createdResult = isPortal
          ? await createPortalDispatchRequest(payload, { idempotencyKey })
          : await createDispatchRequest(payload, { idempotencyKey })
      }
      created.value = createdResult
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
      try {
        localStorage.removeItem(currentDraftStorageKey())
        localStorage.removeItem(LEGACY_DRAFT_KEY)
        const uid = uidOrNull()
        const submittedDraftId = activeDraftId.value
        if (uid != null && submittedDraftId) {
          localStorage.removeItem(draftItemStorageKey(uid, submittedDraftId))
          let { items } = readDraftList(uid)
          items = items.filter((x) => x.id !== submittedDraftId)
          writeDraftList(uid, items)
          localStorage.removeItem(draftActiveStorageKey(uid))
        }
        activeDraftId.value = null
      } catch {
        /* ignore */
      }
      draftSavedAt.value = null
      refreshDraftsList()
      {
        const u = uidOrNull()
        hasDraftSnapshot.value = u != null && readDraftList(u).items.length > 0
      }
      } catch (e) {
        error.value = formatApiError(e, t('dispatch_wizard.validate.create_fail'))
      }
      if (created.value?.id) {
        submitResultOk.value = true
        submitResultDetail.value = (error.value && String(error.value).trim()) || ''
        submitResultModalOpen.value = true
      } else if (error.value) {
        submitResultOk.value = false
        submitResultDetail.value = error.value
        submitResultModalOpen.value = true
      }
    } finally {
      loading.value = false
      submitInFlight = false
    }
  }

  function migrateLegacyDraft() {
    if (typeof localStorage === 'undefined') return
    const uid = auth.user?.id
    if (uid == null) return
    const userKey = draftKeyForUser(uid)
    if (localStorage.getItem(userKey)) return
    const legacy = localStorage.getItem(LEGACY_DRAFT_KEY)
    if (!legacy) return
    localStorage.setItem(userKey, legacy)
    localStorage.removeItem(LEGACY_DRAFT_KEY)
  }

  function upsertDraftAndTrim(uid, meta, dataJson) {
    let { items } = readDraftList(uid)
    const idx = items.findIndex((x) => x.id === meta.id)
    if (idx >= 0) items[idx] = meta
    else items.push(meta)
    items.sort((a, b) => b.savedAt - a.savedAt)
    while (items.length > MAX_SAVED_DRAFTS) {
      const removed = items.pop()
      if (removed) {
        try {
          localStorage.removeItem(draftItemStorageKey(uid, removed.id))
        } catch {
          /* ignore */
        }
        if (activeDraftId.value === removed.id) {
          activeDraftId.value = null
          try {
            localStorage.removeItem(draftActiveStorageKey(uid))
          } catch {
            /* ignore */
          }
        }
      }
    }
    writeDraftList(uid, items)
    try {
      localStorage.setItem(draftItemStorageKey(uid, meta.id), dataJson)
    } catch (e) {
      if (isQuotaExceededError(e)) throw e
    }
  }

  function isQuotaExceededError(err) {
    if (!err) return false
    if (err.name === 'QuotaExceededError') return true
    if (err.code === 22 || err.code === 1014) return true
    return false
  }

  function flashDraftSaved() {
    draftSaveFlash.value = true
    clearTimeout(draftSaveFlashTimer)
    draftSaveFlashTimer = window.setTimeout(() => {
      draftSaveFlash.value = false
      draftSaveFlashTimer = null
    }, 2000)
  }

  function saveDraft() {
    draftSaveError.value = ''
    try {
      trimPassengerRowsInPlace()
      trimBusinessRowsInPlace()
      trimCargoRowsInPlace()
      const savedAt = Date.now()
      const data = {
        form: form.value,
        passengerRows: passengerRows.value,
        businessRows: businessRows.value,
        cargoRows: cargoRows.value,
        step: step.value,
        maxReachedStep: maxReachedStep.value,
        savedAt,
      }
      const uid = uidOrNull()
      if (uid == null) {
        localStorage.setItem(currentDraftStorageKey(), JSON.stringify(data))
        draftSavedAt.value = savedAt
        hasDraftSnapshot.value = true
        flashDraftSaved()
        return
      }
      let id = activeDraftId.value
      if (!id) {
        try {
          id = localStorage.getItem(draftActiveStorageKey(uid)) || null
        } catch {
          id = null
        }
      }
      if (!id) id = `d-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`
      activeDraftId.value = id
      const json = JSON.stringify(data)
      const meta = buildDraftMeta(id, data)
      try {
        localStorage.setItem(draftActiveStorageKey(uid), id)
      } catch {
        /* ignore */
      }
      upsertDraftAndTrim(uid, meta, json)
      draftSavedAt.value = savedAt
      hasDraftSnapshot.value = true
      refreshDraftsList()
      flashDraftSaved()
    } catch (e) {
      if (isQuotaExceededError(e)) {
        draftSaveError.value = t('dispatch_wizard.draft.save_quota_error')
      }
    }
  }

  function loadDraftFromStorage() {
    try {
      const uid = uidOrNull()
      if (uid == null) {
        const raw = localStorage.getItem(currentDraftStorageKey())
        if (!raw) {
          hasDraftSnapshot.value = false
          refreshDraftsList()
          return
        }
        const data = JSON.parse(raw)
        applyDraftPayload(data)
        refreshDraftsList()
        return
      }
      migrateV1SingleDraftToMulti(uid)
      const activeStored = localStorage.getItem(draftActiveStorageKey(uid))
      if (activeStored) {
        const raw = localStorage.getItem(draftItemStorageKey(uid, activeStored))
        if (raw) {
          const data = JSON.parse(raw)
          applyDraftPayload(data)
          activeDraftId.value = activeStored
          refreshDraftsList()
          return
        }
      }
      const { items } = readDraftList(uid)
      if (items.length) {
        const sorted = [...items].sort((a, b) => b.savedAt - a.savedAt)
        const raw = localStorage.getItem(draftItemStorageKey(uid, sorted[0].id))
        if (raw) {
          const data = JSON.parse(raw)
          applyDraftPayload(data)
          activeDraftId.value = sorted[0].id
          try {
            localStorage.setItem(draftActiveStorageKey(uid), sorted[0].id)
          } catch {
            /* ignore */
          }
        }
      } else {
        hasDraftSnapshot.value = false
      }
      refreshDraftsList()
    } catch {
      hasDraftSnapshot.value = false
      refreshDraftsList()
    }
  }

  function loadDraftById(draftId) {
    const uid = uidOrNull()
    if (uid == null) return
    try {
      const raw = localStorage.getItem(draftItemStorageKey(uid, draftId))
      if (!raw) return
      const data = JSON.parse(raw)
      applyDraftPayload(data)
      activeDraftId.value = draftId
      localStorage.setItem(draftActiveStorageKey(uid), draftId)
      if (form.value.requester_name?.trim()) requesterSearchQ.value = form.value.requester_name
      if (form.value.coordinator_name?.trim()) coordinatorSearchQ.value = form.value.coordinator_name
      form.value.requester_phone = sanitizeVnPhoneDigits(form.value.requester_phone)
      form.value.coordinator_phone = sanitizeVnPhoneDigits(form.value.coordinator_phone)
      error.value = ''
      created.value = null
      refreshDraftsList()
      draftsModalOpen.value = false
    } catch {
      /* ignore */
    }
  }

  function deleteDraftById(draftId) {
    const uid = uidOrNull()
    if (uid == null) return
    try {
      const wasActive = activeDraftId.value === draftId
      localStorage.removeItem(draftItemStorageKey(uid, draftId))
      let { items } = readDraftList(uid)
      items = items.filter((x) => x.id !== draftId)
      writeDraftList(uid, items)
      if (wasActive) {
        try {
          localStorage.removeItem(draftActiveStorageKey(uid))
        } catch {
          /* ignore */
        }
        resetWizardForm()
        hasDraftSnapshot.value = items.length > 0
      }
      refreshDraftsList()
    } catch {
      /* ignore */
    }
  }

  function startNewDraftSession() {
    const uid = uidOrNull()
    if (uid != null) {
      try {
        localStorage.removeItem(draftActiveStorageKey(uid))
      } catch {
        /* ignore */
      }
    }
    resetWizardForm()
    if (uid != null) {
      hasDraftSnapshot.value = readDraftList(uid).items.length > 0
    }
    refreshDraftsList()
    draftsModalOpen.value = false
  }

  function openDraftsModal() {
    refreshDraftsList()
    draftsModalOpen.value = true
  }

  function closeDraftsModal() {
    draftsModalOpen.value = false
  }

  function navigateToSubmittedRequestDetail() {
    const id = created.value?.id
    if (!id) return
    if (isPortal) {
      router.push({
        name: 'portalRequestDetail',
        params: { id: String(id) },
        query: { created: '1' },
      })
    } else {
      router.push(staffPath(`/requests/${id}`))
    }
    closeSubmitResultModal()
  }

  function closeSubmitModalAndStartNewDraft() {
    closeSubmitResultModal()
    startNewDraftSession()
  }

  function resetWizardForm() {
    form.value = createInitialForm()
    passengerRows.value = [emptyPassengerRow()]
    businessRows.value = [emptyBusinessRow()]
    cargoRows.value = [emptyCargoRow()]
    step.value = 0
    maxReachedStep.value = 0
    error.value = ''
    created.value = null
    draftSavedAt.value = null
    hasDraftSnapshot.value = false
    activeDraftId.value = null
    basisFile.value = null
    basisFileError.value = ''
    requesterSearchQ.value = ''
    requesterSearchError.value = ''
    coordinatorSearchQ.value = ''
    coordinatorSearchError.value = ''
    requesterEmailTouched.value = false
    coordinatorEmailTouched.value = false
    draftSaveError.value = ''
    draftSaveFlash.value = false
    clearTimeout(draftSaveFlashTimer)
    draftSaveFlashTimer = null
    urgentAutoActive.value = false
    urgentManualDesired.value = false
    hydrateRequestedDateTimeFromState()
    if (isPortal) {
      form.value.source_channel = 'portal'
    }
  }

  function openClearDraftModal() {
    clearDraftModalOpen.value = true
  }

  function closeClearDraftModal() {
    clearDraftModalOpen.value = false
  }

  function confirmClearDraft() {
    closeClearDraftModal()
    const uid = uidOrNull()
    const id = activeDraftId.value
    if (uid != null && id) {
      deleteDraftById(id)
    } else {
      try {
        localStorage.removeItem(currentDraftStorageKey())
        localStorage.removeItem(LEGACY_DRAFT_KEY)
      } catch {
        /* ignore */
      }
      resetWizardForm()
    }
    refreshDraftsList()
  }

  watchEffect((onCleanup) => {
    if (typeof document === 'undefined') return
    const overlayOpen =
      submitResultModalOpen.value || draftsModalOpen.value || clearDraftModalOpen.value
    document.body.style.overflow = overlayOpen ? 'hidden' : ''

    let onKey
    if (typeof window !== 'undefined' && overlayOpen) {
      onKey = (e) => {
        if (e.key !== 'Escape') return
        if (submitResultModalOpen.value) closeSubmitResultModal()
        else if (draftsModalOpen.value) closeDraftsModal()
        else closeClearDraftModal()
      }
      window.addEventListener('keydown', onKey)
    }

    onCleanup(() => {
      document.body.style.overflow = ''
      if (onKey) window.removeEventListener('keydown', onKey)
    })
  })

  function onCancel() {
    if (created.value) {
      if (isPortal) router.push({ name: 'portalHome' })
      else router.push(staffPath('/requests'))
      return
    }
    router.back()
  }

  onMounted(async () => {
    try {
      if (!auth.user) await auth.fetchMe()
    } catch {
      /* router guard / 401 */
    }
    dispatchFormSettingsError.value = ''
    if (isPortal) {
      dispatchFormSettingsLoading.value = false
      syncUrgentFromSchedule()
    } else {
      dispatchFormSettingsLoading.value = true
      try {
        const s = await getDispatchFormSettings()
        urgentThresholds.value = {
          passenger: Number(s.passenger_urgent_threshold_hours) || 72,
          cargo: Number(s.cargo_urgent_threshold_hours) || 24,
        }
        syncUrgentFromSchedule()
      } catch (e) {
        dispatchFormSettingsError.value = formatApiError(
          e,
          t('dispatch_wizard.errors.dispatch_settings_loading'),
        )
      } finally {
        dispatchFormSettingsLoading.value = false
      }
    }
    migrateLegacyDraft()
    const rawReplace = route.query.replace ?? route.query.clone
    if (!isPortal && rawReplace != null && String(rawReplace).trim() !== '') {
      const num = Number(rawReplace)
      if (Number.isFinite(num) && num >= 1) {
        await hydrateFromPendingReplace(num)
      }
    } else {
      loadDraftFromStorage()
    }
    if (auth.user) {
      if (!form.value.requester_name?.trim() && auth.user.name) form.value.requester_name = auth.user.name
      if (!form.value.requester_email?.trim() && auth.user.email) form.value.requester_email = auth.user.email
    }
    if (form.value.requester_name?.trim()) requesterSearchQ.value = form.value.requester_name
    if (form.value.coordinator_name?.trim()) coordinatorSearchQ.value = form.value.coordinator_name
    form.value.requester_phone = sanitizeVnPhoneDigits(form.value.requester_phone)
    form.value.coordinator_phone = sanitizeVnPhoneDigits(form.value.coordinator_phone)
    if (isPortal) {
      form.value.source_channel = 'portal'
    }
    if (!hasDraftSnapshot.value && typeof localStorage !== 'undefined') {
      const u = uidOrNull()
      if (u != null) {
        hasDraftSnapshot.value = readDraftList(u).items.length > 0
      }
      if (!hasDraftSnapshot.value) {
        hasDraftSnapshot.value = !!localStorage.getItem(currentDraftStorageKey())
      }
    }
  })

  watch(
    () => route.query.replace ?? route.query.clone,
    async (raw) => {
      if (isPortal) return
      if (raw == null || String(raw).trim() === '') return
      const num = Number(raw)
      if (!Number.isFinite(num) || num < 1) return
      await hydrateFromPendingReplace(num)
    },
  )

  watch(
    step,
    (s) => {
      if (s > maxReachedStep.value) maxReachedStep.value = s
    },
    { immediate: true },
  )

  watch(
    () => form.value.proposed_date,
    (newVal, oldVal) => {
      if (!newVal) return
      // Chỉ đồng bộ khi ngày cần xe (theo bước 2) vẫn trùng ngày đề xuất cũ — tránh ghi đè khi nháp đã tách hai ngày.
      if (oldVal != null && form.value.date_needed !== oldVal) return
      const cur = requestedDateTime.value?.trim() || ''
      const tPart = cur.includes('T') ? cur.slice(11) : '00:00'
      requestedDateTime.value = `${newVal}T${tPart || '00:00'}`
    },
  )

  // Autosave: debounce 3s sau mỗi thay đổi form; không lưu khi đã submit (created) hoặc đang ở step 0
  watch(
    form,
    () => {
      if (created.value) return
      if (step.value === 0 && !form.value.trip_type) return
      clearTimeout(autosaveTimer)
      autosaveTimer = setTimeout(() => {
        if (created.value) return
        saveDraft()
        lastAutoSavedAt.value = Date.now()
      }, 3000)
    },
    { deep: true },
  )

  return {
    isPortal,
    steps,
    step,
    maxReachedStep,
    loading,
    error,
    created,
    replaceDraftRequestId,
    draftSavedAt,
    lastAutoSavedAt,
    hasDraftSnapshot,
    clearDraftModalOpen,
    submitResultModalOpen,
    submitResultOk,
    submitResultDetail,
    closeSubmitResultModal,
    activeDraftId,
    savedDraftsList,
    draftsModalOpen,
    targetOptions,
    form,
    basisFile,
    basisFileInput,
    basisDragOver,
    basisFileError,
    requesterSearchQ,
    requesterSearchResults,
    requesterSearchLoading,
    requesterDropdownOpen,
    requesterSearchError,
    coordinatorSearchQ,
    coordinatorSearchResults,
    coordinatorSearchLoading,
    coordinatorDropdownOpen,
    coordinatorSearchError,
    step2DateOrderInvalid,
    step2RequesterEmailInvalid,
    draftSaveError,
    draftSaveFlash,
    step2CoordinatorEmailInvalid,
    passengerRows,
    businessRows,
    cargoRows,
    detailStepSchedulesValid,
    tripTypeOptions,
    isCargo,
    isPointToPointTrip,
    e1WeekdayOptions,
    openDatePickerFromInput,
    toggleE1Weekday,
    onRequesterPhoneInput,
    onCoordinatorPhoneInput,
    onRequesterEmailBlur,
    onCoordinatorEmailBlur,
    scheduleRequesterSearch,
    onRequesterSearchFocus,
    onRequesterSearchBlur,
    pickRequester,
    scheduleCoordinatorSearch,
    onCoordinatorSearchFocus,
    onCoordinatorSearchBlur,
    pickCoordinator,
    onBasisFileChange,
    onBasisDrop,
    clearBasisFile,
    draftLabel,
    dispatchFormSettingsLoading,
    dispatchFormSettingsError,
    urgentAutoActive,
    appliedUrgentThresholdHours,
    toggleUrgentManual,
    requestedDateTime,
    formattedRequestedDateTime,
    computedDepartAt,
    rowLineTotal,
    passengerE1Total,
    passengerE2Total,
    passengerTotal,
    passengerGuestTotal,
    cargoTotal,
    extraCosts,
    formatCurrency,
    formatFileSize,
    formatDraftTime,
    canGoNext,
    goStep,
    nextStep,
    addPassengerRow,
    removePassengerRow,
    addBusinessRow,
    removeBusinessRow,
    addCargoRow,
    removeCargoRow,
    canSubmitApi,
    confirmReviewIssues,
    headerPrimaryLabel,
    headerPrimaryDisabled,
    primaryAction,
    saveDraft,
    openClearDraftModal,
    closeClearDraftModal,
    confirmClearDraft,
    loadDraftById,
    deleteDraftById,
    startNewDraftSession,
    openDraftsModal,
    closeDraftsModal,
    navigateToSubmittedRequestDetail,
    closeSubmitModalAndStartNewDraft,
    onCancel,
  }
}
