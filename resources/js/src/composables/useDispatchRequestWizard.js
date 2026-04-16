import { computed, onBeforeUnmount, onMounted, ref, watch, watchEffect } from 'vue'
import { useRouter } from 'vue-router'
import {
  AcademicCapIcon,
  BriefcaseIcon,
  BuildingOffice2Icon,
  CubeIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '../store'
import { uploadAttachment } from '../api/attachments'
import saveAs from 'file-saver'
import { createDispatchRequest, getDispatchRequest, previewBm02DispatchForm } from '../api/requests'
import { searchUsersForDispatchForm } from '../api/operational'
import { formatApiError } from '../api/http'
import { newIdempotencyKey } from '../util/idempotency'
import { downloadBinaryAttachmentFromApi, fetchPdfBlobForPreview } from '../util/downloadPdfAttachment'
import { toDatetimeLocalValue } from '../util/datetime'
import {
  LEGACY_DRAFT_KEY,
  WIZARD_STEPS,
  TARGET_OPTIONS,
  E1_WEEKDAY_OPTIONS,
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
  TRIP_TYPE_LABEL_VI,
} from './dispatchWizardConstants'

export function useDispatchRequestWizard() {
  const router = useRouter()
  const auth = useAuthStore()

  const steps = WIZARD_STEPS
  const targetOptions = TARGET_OPTIONS
  const e1WeekdayOptions = E1_WEEKDAY_OPTIONS

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
    const tripLabel = TRIP_TYPE_LABEL_VI[trip] || trip || '—'
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
    localStorage.setItem(draftListStorageKey(uid), JSON.stringify({ items }))
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
    if (Array.isArray(data.cargoRows) && data.cargoRows.length) cargoRows.value = data.cargoRows
    if (typeof data.step === 'number') step.value = data.step
    if (typeof data.maxReachedStep === 'number') {
      maxReachedStep.value = Math.max(data.maxReachedStep, step.value)
    }
    draftSavedAt.value = data.savedAt ?? Date.now()
    trimPassengerRowsInPlace()
    trimBusinessRowsInPlace()
    trimCargoRowsInPlace()
    hasDraftSnapshot.value = true
  }

  const step = ref(0)
  const maxReachedStep = ref(0)
  const loading = ref(false)
  const error = ref('')

  const bm02Loading = ref(false)
  const bm02PreviewError = ref('')
  const bm02PdfUrl = ref(null)
  const bm02PdfBase64 = ref('')
  const bm02ExcelBase64 = ref('')
  const bm02FilenamePdf = ref('BM02-denghi-dieuvan-preview.pdf')
  const bm02FilenameXlsx = ref('BM02-denghi-dieuvan-preview.xlsx')
  /** Sau khi gửi yêu cầu: file BM.02 lấy từ attachments (DB), không dùng base64 preview. */
  const bm02PdfAttachmentId = ref(null)
  const bm02ExcelAttachmentId = ref(null)
  const created = ref(null)
  const draftSavedAt = ref(null)
  const hasDraftSnapshot = ref(false)
  const clearDraftModalOpen = ref(false)
  /** Id bản nháp đang mở (chuỗi); null = phiên làm việc mới, lưu sẽ tạo bản mới. */
  const activeDraftId = ref(null)
  /** Meta cho danh sách (mới nhất trước). */
  const savedDraftsList = ref([])
  const draftsModalOpen = ref(false)

  const form = ref(createInitialForm())

  const basisFile = ref(null)
  const basisFileInput = ref(null)
  const basisDragOver = ref(false)
  const basisFileError = ref('')

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

  const tripTypeOptions = [
    {
      value: 'door_to_door',
      label: 'Đưa đón (Door-to-door)',
      hint: '',
      icon: AcademicCapIcon,
      iconClass: 'text-violet-600',
      selectedClass: 'border-violet-500 bg-violet-50 shadow-sm ring-1 ring-violet-200',
    },
    {
      value: 'point_to_point',
      label: 'Điểm — Điểm',
      hint: '',
      icon: BuildingOffice2Icon,
      iconClass: 'text-sky-600',
      selectedClass: 'border-sky-500 bg-sky-50 shadow-sm ring-1 ring-sky-200',
    },
    {
      value: 'business',
      label: 'Công tác',
      hint: '',
      icon: BriefcaseIcon,
      iconClass: 'text-emerald-600',
      selectedClass: 'border-emerald-500 bg-emerald-50 shadow-sm ring-1 ring-emerald-200',
    },
    {
      value: 'cargo',
      label: 'Hàng hóa',
      hint: '',
      icon: CubeIcon,
      iconClass: 'text-orange-600',
      selectedClass: 'border-orange-500 bg-orange-50 shadow-sm ring-1 ring-orange-200',
      badge: 'SLA 3h',
    },
  ]

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
    if (u.employee_code) return `Mã NV: ${u.employee_code}`
    return ''
  }

  function formatFileSize(n) {
    if (n == null || Number.isNaN(n)) return ''
    if (n < 1024) return `${n} B`
    if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
    return `${(n / (1024 * 1024)).toFixed(1)} MB`
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
      requesterSearchError.value = formatApiError(e, 'Không tìm kiếm được nhân sự.')
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
      coordinatorSearchError.value = formatApiError(e, 'Không tìm kiếm được nhân sự.')
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
  }

  function onBasisFileChange(e) {
    basisFileError.value = ''
    const f = e?.target?.files?.[0]
    if (!f) return
    if (f.size > 10 * 1024 * 1024) {
      basisFile.value = null
      basisFileError.value = 'Tệp vượt quá 10MB.'
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
      basisFileError.value = 'Tệp vượt quá 10MB.'
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
    if (created.value) return 'Đã gửi'
    if (activeDraftId.value && savedDraftsList.value.length) {
      const m = savedDraftsList.value.find((x) => x.id === activeDraftId.value)
      if (m) {
        try {
          return `Bản nháp • ${m.tripLabel} • ${new Date(m.savedAt).toLocaleString('vi-VN')}`
        } catch {
          return 'Bản nháp'
        }
      }
    }
    if (draftSavedAt.value) {
      try {
        return `Bản nháp • Lưu ${new Date(draftSavedAt.value).toLocaleString('vi-VN')}`
      } catch {
        return 'Bản nháp'
      }
    }
    return 'Phiên mới (chưa lưu)'
  })

  function minDatetimeLocalFromValues(values) {
    const vals = values.filter(Boolean)
    if (!vals.length) return ''
    let min = null
    for (const v of vals) {
      const t = new Date(v).getTime()
      if (!Number.isNaN(t) && (min === null || t < min)) min = t
    }
    if (min === null) return ''
    return toDatetimeLocalValue(new Date(min))
  }

  const computedDepartAt = computed(() => {
    const vals = []
    if (isCargo.value) {
      for (const r of cargoRows.value) {
        if (r.pickup_at) vals.push(r.pickup_at)
      }
    } else {
      for (const r of passengerRows.value) {
        if (r.depart_at) vals.push(r.depart_at)
      }
      if (!isPointToPointTrip.value) {
        for (const r of businessRows.value) {
          if (r.depart_at) vals.push(r.depart_at)
        }
      }
    }
    return minDatetimeLocalFromValues(vals)
  })

  function parseMoney(v) {
    const n = Number(String(v).replace(/\s/g, ''))
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
      passengerRows.value.reduce((s, r) => s + parseMoney(r.guests), 0) +
      businessRows.value.reduce((s, r) => s + parseMoney(r.guests), 0),
  )

  const cargoTotal = computed(() => cargoRows.value.reduce((s, r) => s + parseMoney(r.cost), 0))

  const extraCosts = computed(() => {
    let x = 0
    if (form.value.need_porters) x += parseMoney(form.value.porter_cost)
    if (form.value.interprovincial) x += parseMoney(form.value.interprovincial_cost)
    return x
  })

  function formatCurrency(n) {
    if (n == null || Number.isNaN(Number(n))) return '—'
    try {
      return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(n))
    } catch {
      return `${n} ₫`
    }
  }

  const step2DateOrderInvalid = computed(() =>
    isDateNeededBeforeProposed(form.value.proposed_date, form.value.date_needed),
  )

  const step2RequesterEmailInvalid = computed(
    () => !!form.value.requester_email?.trim() && !isPlausibleEmail(form.value.requester_email),
  )

  const step2CoordinatorEmailInvalid = computed(
    () => !!form.value.coordinator_email?.trim() && !isPlausibleEmail(form.value.coordinator_email),
  )

  const canGoNext = computed(() => {
    if (step.value === 0) return !!form.value.trip_type
    if (step.value === 1) {
      return (
        !!form.value.requester_name?.trim() &&
        !!form.value.requester_email?.trim() &&
        isPlausibleEmail(form.value.requester_email) &&
        !step2CoordinatorEmailInvalid.value &&
        !!form.value.purpose?.trim() &&
        !!form.value.proposed_date &&
        !!form.value.date_needed &&
        !step2DateOrderInvalid.value &&
        (!form.value.is_urgent || !!form.value.urgent_reason?.trim())
      )
    }
    if (step.value === 2) {
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
    if (!passengerRows.value.length) passengerRows.value.push(emptyPassengerRow())
  }

  function addBusinessRow() {
    businessRows.value.push(emptyBusinessRow())
  }

  function removeBusinessRow(i) {
    businessRows.value.splice(i, 1)
    if (!businessRows.value.length) businessRows.value.push(emptyBusinessRow())
  }

  function addCargoRow() {
    cargoRows.value.push(emptyCargoRow())
  }

  function removeCargoRow(i) {
    cargoRows.value.splice(i, 1)
    if (!cargoRows.value.length) cargoRows.value.push(emptyCargoRow())
  }

  const canSubmitApi = computed(() => !!computedDepartAt.value?.trim())

  const headerPrimaryLabel = computed(() => {
    if (loading.value) return 'Đang gửi…'
    if (step.value < 3) return 'Tiếp tới xác nhận'
    return 'Gửi yêu cầu'
  })

  const headerPrimaryDisabled = computed(() => {
    if (loading.value) return true
    if (step.value < 3) return !canGoNext.value
    return !canSubmitApi.value
  })

  function buildNotesBody() {
    const f = form.value
    const lines = []
    lines.push('=== ĐỀ NGHỊ ĐIỀU VẬN (BM.03/MH.QT.04 — bản điện tử) ===')
    lines.push('')
    lines.push('Người đề nghị')
    lines.push(`- Họ tên: ${f.requester_name || '—'}`)
    lines.push(`- Email: ${f.requester_email || '—'}`)
    lines.push(`- Điện thoại: ${f.requester_phone || '—'}`)
    lines.push(`- Đơn vị: ${f.requester_unit || '—'}`)
    lines.push('')
    lines.push('Mục đích sử dụng')
    lines.push(`- Mục đích: ${f.purpose || '—'}`)
    if (f.trip_type === 'point_to_point') {
      const pk =
        f.point_purpose_kind === 'extracurricular' ? 'Hoạt động ngoại khóa' : 'Điểm — Điểm'
      lines.push(`- Phân loại mục đích: ${pk}`)
    }
    if (basisFile.value) {
      lines.push(`- Căn cứ đề xuất: đính kèm tệp «${basisFile.value.name}»`)
    } else {
      lines.push('- Căn cứ đề xuất: (chưa đính kèm tệp)')
    }
    lines.push('')
    lines.push('Thời gian')
    lines.push(`- Ngày đề xuất: ${f.proposed_date || '—'}`)
    lines.push(`- Ngày cần sử dụng xe: ${f.date_needed || '—'}`)
    if (f.is_urgent) lines.push(`- GẤP — Lý do: ${f.urgent_reason || '—'}`)
    lines.push('')
    lines.push('Đối tượng / điều phối')
    lines.push(`- Đối tượng: ${f.targets?.length ? f.targets.join(', ') : '—'}`)
    lines.push(
      `- Điều phối: ${f.coordinator_name || '—'} | ${f.coordinator_email || '—'} | ${f.coordinator_phone || '—'}`,
    )
    lines.push('')

    if (isCargo.value) {
      lines.push('Nội dung đề nghị vận chuyển')
      lines.push('Nội dung chi tiết')
      cargoRows.value.forEach((r, i) => {
        if (!r.name?.trim()) return
        lines.push(
          `${i + 1}. ${r.name} | SL ${r.qty || '—'} | ${r.dimensions || '—'} | ${r.weight || '—'} | ${r.item_notes || ''}`,
        )
        lines.push(
          `   Lấy: ${r.pickup_at || '—'} @ ${r.pickup_place || '—'} — ${r.pickup_contact || '—'}`,
        )
        lines.push(
          `   Giao: ${r.delivery_at || '—'} @ ${r.delivery_place || '—'} — ${r.delivery_contact || '—'}`,
        )
        lines.push(`   Vận chuyển: ${r.transport_note || '—'} | Chi phí: ${r.cost || '0'}`)
      })
      lines.push(`Tổng hàng: ${formatCurrency(cargoTotal.value)}`)
      if (f.cargo_extra_notes?.trim()) lines.push(`Ghi chú khác: ${f.cargo_extra_notes}`)
      if (f.need_porters) {
        lines.push(`- Bốc xếp: SL ${f.porter_qty || '—'} — phát sinh ${f.porter_cost || '0'} VNĐ`)
      }
      if (f.interprovincial) {
        lines.push(`- Chành xe tỉnh — phát sinh ${f.interprovincial_cost || '0'} VNĐ`)
      }
      lines.push(`Tổng cộng (ước tính): ${formatCurrency(cargoTotal.value + extraCosts.value)}`)
    } else {
      lines.push('Nội dung đề nghị vận chuyển')
      lines.push('Nội dung đề xuất cho chương trình / sự kiện ngoại khóa')
      if (f.multi_day) lines.push('(Dùng nhiều ngày — chi tiết bổ sung khi điều phối.)')
      passengerRows.value.forEach((r, i) => {
        if (!isPassengerRowFilled(r)) return
        lines.push(
          `${i + 1}. Đi: ${r.depart_at || '—'} ${r.pickup || '—'} | Về: ${r.return_at || '—'} ${r.dropoff || '—'} | ${r.guests || '0'} khách | NV: ${r.person_in_charge || '—'} | ĐG ${r.unit_price || '0'} + PS ${r.extra_fee || '0'} | ${r.notes || ''}`,
        )
      })
      lines.push(`Tổng (ước tính): ${formatCurrency(passengerE1Total.value)}`)
      if (f.trip_type !== 'point_to_point') {
        const wd = f.e1_weekdays || {}
        const wdLabels = []
        if (wd.mon) wdLabels.push('T2')
        if (wd.tue) wdLabels.push('T3')
        if (wd.wed) wdLabels.push('T4')
        if (wd.thu) wdLabels.push('T5')
        if (wd.fri) wdLabels.push('T6')
        if (wd.sat) wdLabels.push('T7')
        if (wd.sun) wdLabels.push('CN')
        lines.push('e.1.1 Ghi chú khác đề xuất')
        if (f.e1_use_3plus_days) {
          lines.push(
            `- Xe từ 3 ngày trở lên: ${f.e1_from_date || '—'} → ${f.e1_to_date || '—'} | Tổng ngày: ${f.e1_days_total || '—'} | Phát sinh: ${f.e1_extra_cost || '0'}`,
          )
        }
        if (wdLabels.length) lines.push(`- Các thứ trong tuần: ${wdLabels.join(', ')}`)
        lines.push('Nội dung đề xuất cho nhân sự đi công tác')
        businessRows.value.forEach((r, i) => {
          if (!isBusinessRowFilled(r)) return
          lines.push(
            `${i + 1}. Đi: ${r.depart_at || '—'} ${r.pickup || '—'} | Dừng: ${r.waypoint || '—'} | Về: ${r.return_at || '—'} ${r.dropoff || '—'} | ${r.guests || '0'} khách | ĐG+PS: ${formatCurrency(rowLineTotal(r))} | ${r.notes || ''}`,
          )
        })
        lines.push(`Tổng e.2 (ước tính): ${formatCurrency(passengerE2Total.value)}`)
        lines.push('Ghi chú khác (công tác)')
        if (f.e2_door_pickup) lines.push(`- Đưa đón tận nhà: ${f.e2_door_cost || '0'}`)
        if (f.e2_driver_self) lines.push(`- Tài xế tự túc: ${f.e2_driver_self_cost || '0'}`)
        if (f.e2_after_21h) lines.push(`- Xe sau 21h: ${f.e2_after_21h_cost || '0'}`)
        lines.push(`Tổng (ước tính): ${formatCurrency(passengerTotal.value)}`)
      }
    }

    lines.push('')
    lines.push('--- Hệ thống: các trường trên được gửi kèm để bộ phận Điều vận xử lý.')
    return lines.join('\n')
  }

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

  let submitInFlight = false

  function primaryAction() {
    if (step.value < 3) nextStep()
    else doSubmit()
  }

  function validateBeforeApi() {
    if (!form.value.trip_type) {
      step.value = 0
      return 'Chọn loại dịch vụ.'
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
      return 'Điền đủ thông tin bước 2 (A–C, mục đích).'
    }
    if (step2CoordinatorEmailInvalid.value) {
      step.value = 1
      return 'Email nhân sự điều phối không hợp lệ.'
    }
    if (isDateNeededBeforeProposed(form.value.proposed_date, form.value.date_needed)) {
      step.value = 1
      return 'Ngày cần sử dụng xe không được sớm hơn ngày đề xuất.'
    }
    if (form.value.is_urgent && !form.value.urgent_reason?.trim()) {
      step.value = 1
      return 'Ghi lý do khi chọn Gấp.'
    }
    if (isCargo.value) {
      if (!cargoRows.value.some((r) => r.name?.trim())) {
        step.value = 2
        return 'Thêm ít nhất một dòng hàng hóa (tên hàng).'
      }
    } else if (form.value.trip_type === 'point_to_point') {
      if (!passengerRows.value.some(isPassengerRowFilled)) {
        step.value = 2
        return 'Thêm ít nhất một dòng chi tiết (e.1) hoặc nhập thời gian chuyến.'
      }
    } else if (
      !passengerRows.value.some(isPassengerRowFilled) &&
      !businessRows.value.some(isBusinessRowFilled)
    ) {
      step.value = 2
      return 'Thêm ít nhất một dòng chi tiết (e.1 hoặc e.2) hoặc nhập thời gian chuyến.'
    }
    if (!computedDepartAt.value?.trim()) {
      step.value = 2
      return 'Nhập thời gian chuyến đi (ít nhất một ô thời gian trong bảng chi tiết).'
    }
    return ''
  }

  async function doSubmit() {
    if (submitInFlight || loading.value) return
    const v = validateBeforeApi()
    if (v) {
      error.value = v
      return
    }
    error.value = ''
    created.value = null
    submitInFlight = true
    loading.value = true
    const idempotencyKey = newIdempotencyKey()
    try {
      const { origin, destination } = computeApiOriginDestination()
      const notes = buildNotesBody()
      const payload = {
        trip_type: form.value.trip_type,
        source_channel: form.value.source_channel,
        origin: origin || undefined,
        destination: destination || undefined,
        depart_at: toIsoMaybe(computedDepartAt.value),
        arrive_by: null,
        passenger_count: isCargo.value
          ? null
          : passengerGuestTotal.value > 0
            ? Math.round(passengerGuestTotal.value)
            : null,
        notes,
        is_urgent: !!form.value.is_urgent,
      }
      if (form.value.trip_type === 'point_to_point') {
        payload.wizard_snapshot = buildWizardSnapshot()
      }
      Object.keys(payload).forEach((k) => (payload[k] === '' ? delete payload[k] : null))
      created.value = await createDispatchRequest(payload, { idempotencyKey })
      if (created.value?.id && created.value.trip_type === 'point_to_point') {
        await hydrateBm02FromSavedDispatchRequest(created.value.id)
      }
      if (basisFile.value && created.value?.id) {
        try {
          await uploadAttachment({
            attachable_type: 'dispatch_request',
            attachable_id: created.value.id,
            kind: 'proposal_basis',
            file: basisFile.value,
          })
        } catch (attachErr) {
          error.value = formatApiError(
            attachErr,
            'Đã tạo yêu cầu nhưng không tải được file căn cứ. Bạn có thể thử lại từ chi tiết yêu cầu (nếu được phép).',
          )
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
      error.value = formatApiError(e, 'Tạo yêu cầu thất bại.')
    } finally {
      loading.value = false
      submitInFlight = false
    }
  }

  function revokeBm02PdfUrl() {
    if (bm02PdfUrl.value) {
      try {
        URL.revokeObjectURL(bm02PdfUrl.value)
      } catch {
        /* ignore */
      }
      bm02PdfUrl.value = null
    }
  }

  function base64ToBlob(base64, mime) {
    const bin = atob(base64)
    const bytes = new Uint8Array(bin.length)
    for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i)
    return new Blob([bytes], { type: mime })
  }

  function buildWizardSnapshot() {
    return {
      form: { ...form.value, basisFileName: basisFile.value?.name ?? '' },
      passengerRows: passengerRows.value.map((r) => ({ ...r })),
      businessRows: businessRows.value.map((r) => ({ ...r })),
      cargoRows: cargoRows.value.map((r) => ({ ...r })),
    }
  }

  async function hydrateBm02FromSavedDispatchRequest(dispatchRequestId) {
    if (!dispatchRequestId) return
    bm02PreviewError.value = ''
    try {
      const dr = await getDispatchRequest(dispatchRequestId)
      const atts = dr.attachments ?? []
      const pdfAtt = atts.find((a) => a.kind === 'bm02_pdf')
      const xlsxAtt = atts.find((a) => a.kind === 'bm02_excel')
      bm02PdfAttachmentId.value = pdfAtt?.id ?? null
      bm02ExcelAttachmentId.value = xlsxAtt?.id ?? null
      if (pdfAtt?.original_name) bm02FilenamePdf.value = pdfAtt.original_name
      if (xlsxAtt?.original_name) bm02FilenameXlsx.value = xlsxAtt.original_name
      bm02PdfBase64.value = ''
      bm02ExcelBase64.value = ''
      const prevPdfUrl = bm02PdfUrl.value
      bm02PdfUrl.value = null
      if (prevPdfUrl) {
        try {
          URL.revokeObjectURL(prevPdfUrl)
        } catch {
          /* ignore */
        }
      }
      if (pdfAtt?.id) {
        const r = await fetchPdfBlobForPreview(pdfAtt.id)
        if (r.ok) {
          bm02PdfUrl.value = URL.createObjectURL(r.blob)
        } else {
          bm02PreviewError.value = 'Không hiển thị được PDF BM.02 từ đính kèm đã lưu.'
        }
      }
    } catch (e) {
      bm02PreviewError.value = formatApiError(e, 'Không đọc được BM.02 đã lưu trên máy chủ.')
    }
  }

  async function loadBm02Preview() {
    if (form.value.trip_type !== 'point_to_point') return
    bm02PdfAttachmentId.value = null
    bm02ExcelAttachmentId.value = null
    bm02PreviewError.value = ''
    bm02PdfBase64.value = ''
    bm02ExcelBase64.value = ''
    bm02Loading.value = true
    const prevPdfUrl = bm02PdfUrl.value
    bm02PdfUrl.value = null
    try {
      const data = await previewBm02DispatchForm(buildWizardSnapshot())
      bm02PdfBase64.value = data.pdf_base64 ?? ''
      bm02ExcelBase64.value = data.excel_base64 ?? ''
      if (data.filename_pdf) bm02FilenamePdf.value = data.filename_pdf
      if (data.filename_xlsx) bm02FilenameXlsx.value = data.filename_xlsx
      if (data.pdf_base64) {
        bm02PdfUrl.value = URL.createObjectURL(base64ToBlob(data.pdf_base64, 'application/pdf'))
      }
      if (prevPdfUrl) {
        try {
          URL.revokeObjectURL(prevPdfUrl)
        } catch {
          /* ignore */
        }
      }
    } catch (e) {
      bm02PdfUrl.value = prevPdfUrl
      bm02PreviewError.value = formatApiError(e, 'Không tạo được bản xem trước BM.02.')
    } finally {
      bm02Loading.value = false
    }
  }

  const canDownloadBm02Pdf = computed(
    () => !!(bm02PdfAttachmentId.value || bm02PdfBase64.value),
  )
  const canDownloadBm02Excel = computed(
    () => !!(bm02ExcelAttachmentId.value || bm02ExcelBase64.value),
  )

  async function downloadBm02Pdf() {
    if (bm02PdfAttachmentId.value) {
      try {
        await downloadBinaryAttachmentFromApi(bm02PdfAttachmentId.value, bm02FilenamePdf.value)
      } catch (e) {
        bm02PreviewError.value = formatApiError(e, 'Không tải được PDF BM.02.')
      }
      return
    }
    if (!bm02PdfBase64.value) return
    saveAs(base64ToBlob(bm02PdfBase64.value, 'application/pdf'), bm02FilenamePdf.value)
  }

  async function downloadBm02Excel() {
    if (bm02ExcelAttachmentId.value) {
      try {
        await downloadBinaryAttachmentFromApi(bm02ExcelAttachmentId.value, bm02FilenameXlsx.value)
      } catch (e) {
        bm02PreviewError.value = formatApiError(e, 'Không tải được Excel BM.02.')
      }
      return
    }
    if (!bm02ExcelBase64.value) return
    saveAs(
      base64ToBlob(
        bm02ExcelBase64.value,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      ),
      bm02FilenameXlsx.value,
    )
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
    } catch {
      /* ignore */
    }
  }

  function saveDraft() {
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
    } catch {
      /* ignore */
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
      revokeBm02PdfUrl()
      bm02PdfBase64.value = ''
      bm02ExcelBase64.value = ''
      bm02PdfAttachmentId.value = null
      bm02ExcelAttachmentId.value = null
      bm02PreviewError.value = ''
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
    revokeBm02PdfUrl()
    bm02PdfBase64.value = ''
    bm02ExcelBase64.value = ''
    bm02PdfAttachmentId.value = null
    bm02ExcelAttachmentId.value = null
    bm02PreviewError.value = ''
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
    try {
      localStorage.removeItem(currentDraftStorageKey())
      localStorage.removeItem(LEGACY_DRAFT_KEY)
    } catch {
      /* ignore */
    }
    refreshDraftsList()
  }

  watchEffect((onCleanup) => {
    if (typeof document === 'undefined') return
    if (!clearDraftModalOpen.value) {
      document.body.style.overflow = ''
      return
    }
    document.body.style.overflow = 'hidden'
    if (typeof window === 'undefined') return
    const onKey = (e) => {
      if (e.key === 'Escape') closeClearDraftModal()
    }
    window.addEventListener('keydown', onKey)
    onCleanup(() => {
      document.body.style.overflow = ''
      window.removeEventListener('keydown', onKey)
    })
  })

  watchEffect((onCleanup) => {
    if (typeof document === 'undefined') return
    if (!draftsModalOpen.value) {
      document.body.style.overflow = ''
      return
    }
    document.body.style.overflow = 'hidden'
    if (typeof window === 'undefined') return
    const onKey = (e) => {
      if (e.key === 'Escape') closeDraftsModal()
    }
    window.addEventListener('keydown', onKey)
    onCleanup(() => {
      document.body.style.overflow = ''
      window.removeEventListener('keydown', onKey)
    })
  })

  function onCancel() {
    if (created.value) {
      router.push('/requests')
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
    migrateLegacyDraft()
    loadDraftFromStorage()
    if (auth.user) {
      if (!form.value.requester_name?.trim() && auth.user.name) form.value.requester_name = auth.user.name
      if (!form.value.requester_email?.trim() && auth.user.email) form.value.requester_email = auth.user.email
    }
    if (form.value.requester_name?.trim()) requesterSearchQ.value = form.value.requester_name
    if (form.value.coordinator_name?.trim()) coordinatorSearchQ.value = form.value.coordinator_name
    form.value.requester_phone = sanitizeVnPhoneDigits(form.value.requester_phone)
    form.value.coordinator_phone = sanitizeVnPhoneDigits(form.value.coordinator_phone)
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

  onBeforeUnmount(() => {
    revokeBm02PdfUrl()
  })

  watch(
    step,
    (s) => {
      if (s > maxReachedStep.value) maxReachedStep.value = s
      if (s === 3 && form.value.trip_type === 'point_to_point') loadBm02Preview()
    },
    { immediate: true },
  )

  watch(
    () => form.value.proposed_date,
    (newVal, oldVal) => {
      if (!newVal) return
      // Chỉ đồng bộ khi "Ngày cần xe" vẫn trùng ngày đề xuất cũ — tránh ghi đè khi người dùng / nháp đã tách hai ngày.
      if (oldVal != null && form.value.date_needed !== oldVal) return
      form.value.date_needed = newVal
    },
  )

  return {
    steps,
    step,
    maxReachedStep,
    loading,
    error,
    bm02Loading,
    bm02PreviewError,
    bm02PdfUrl,
    bm02PdfBase64,
    bm02ExcelBase64,
    bm02FilenamePdf,
    bm02FilenameXlsx,
    canDownloadBm02Pdf,
    canDownloadBm02Excel,
    created,
    draftSavedAt,
    hasDraftSnapshot,
    clearDraftModalOpen,
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
    step2CoordinatorEmailInvalid,
    passengerRows,
    businessRows,
    cargoRows,
    tripTypeOptions,
    isCargo,
    isPointToPointTrip,
    e1WeekdayOptions,
    openDatePickerFromInput,
    toggleE1Weekday,
    onRequesterPhoneInput,
    onCoordinatorPhoneInput,
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
    headerPrimaryLabel,
    headerPrimaryDisabled,
    primaryAction,
    loadBm02Preview,
    downloadBm02Pdf,
    downloadBm02Excel,
    saveDraft,
    openClearDraftModal,
    closeClearDraftModal,
    confirmClearDraft,
    loadDraftById,
    deleteDraftById,
    startNewDraftSession,
    openDraftsModal,
    closeDraftsModal,
    onCancel,
  }
}
