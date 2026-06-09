import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useDispatchRequestDocs } from './useDispatchRequestDocs'
import { useRequestCostEstimate } from './useRequestCostEstimate'
import { useRequestWorkflowSteps } from './useRequestWorkflowSteps'
import { usePortalTimelineSteps } from './usePortalTimelineSteps'
import { deleteAttachment, runAttachmentOcr, uploadAttachment } from '../api/attachments'
import { rerunSignedDocumentOcr, verifySignedDocument } from '../api/signedDocuments'
import { getDispatchFormSettings } from '../api/dispatchSettings'
import {
  decideDispatchRequest,
  deptDecideDispatchRequest,
  exportDispatchRequestPdf,
  fillPriceDispatchRequest,
  getDispatchRequest,
  markPaperReceived,
  revertPaperReceived,
  cloneDispatchRequest,
  patchPassengerCount,
  getDispatchRequestAuditLogs,
} from '../api/requests'
import { formatApiError } from '../api/http'
import { saveAs } from 'file-saver'
import { labelTripType } from '../util/labels'
import { dispatchRequestDisplayPassengerCount } from '../util/dispatchRequestPassengers'
import { downloadBinaryAttachmentFromApi } from '../util/downloadPdfAttachment'
import { toDatetimeLocalValue } from '../util/datetime'
import { createActionIdempotencyKey } from '../util/idempotency'
import { useAuthStore } from '../store'
import { confirmAction } from './useConfirm'
import { showAppSuccess, showAppError } from './appMessage'

const DETAIL_TABS = ['form', 'students', 'docs']
const STAFF_DETAIL_TABS = ['form', 'route', 'docs', 'students', 'activity']

const FOCUS_TARGETS = {
  'fill-price': 'request-focus-fill-price',
  'dept-decision': 'request-focus-dept-decision',
  docs: 'request-docs-panel',
  'docs-upload': 'request-docs-upload-row',
  'passenger-adjust': 'request-focus-passenger',
}

/**
 * State & actions for staff/dept dispatch request detail.
 */
export function useRequestDetailPage() {
  const route = useRoute()
  const router = useRouter()
  const auth = useAuthStore()
  const { t, locale } = useI18n()

  const isStaffContext = computed(() => route.name === 'requestDetail')
  const isDeptContext = computed(() => route.name === 'deptRequestDetail')

  const backTo = computed(() =>
    isDeptContext.value ? { name: 'deptDashboard' } : { name: 'requests' },
  )
  const backAriaLabel = computed(() =>
    isDeptContext.value ? t('dept.aria_back_pending') : t('request_detail.aria_back_list'),
  )
  const cloneBannerContext = computed(() => (isDeptContext.value ? 'dept' : 'staff'))

  const deptDecisionIdem = createActionIdempotencyKey()
  const d2dDecisionIdem = createActionIdempotencyKey()

  const req = ref(null)
  const loading = ref(true)
  const formSettings = ref(null)

  const paperForm = ref({ paper_reference: '', paper_received_at: '' })
  const paperActing = ref(false)
  const paperRevertActing = ref(false)
  const paperMsg = ref('')
  const ocrBusy = ref(null)
  const ocrErr = ref('')
  const signedOcrBusy = ref(false)
  const signedVerifyBusy = ref(false)
  const attachErr = ref('')
  const deletingId = ref(null)
  const pdfBusy = ref(false)
  const pdfErr = ref('')

  const deptActing = ref(false)
  const deptMsg = ref('')
  const deptRejectOpen = ref(false)
  const deptRejectReason = ref('')

  const d2dActing = ref(false)
  const d2dMsg = ref('')
  const d2dRejectOpen = ref(false)
  const d2dRejectReason = ref('')

  const fillPriceActing = ref(false)
  const fillPriceMsg = ref('')

  const copyRejectionFeedback = ref(false)
  let copyRejectionTimer = null
  const signedUploadErr = ref('')
  const passengerDraft = ref(1)
  const passengerSaving = ref(false)
  const passengerPatchErr = ref('')
  const resetCloneBusy = ref(false)

  const auditItems = ref([])
  const auditLoading = ref(false)
  const auditError = ref('')

  const activeTab = ref('form')
  const previewOpen = ref(false)
  const previewAttachment = ref(null)
  const docsHighlightAttachmentId = ref(null)
  const focusHighlight = ref(null)

  const reqForDocs = computed(() => req.value)
  const {
    signedPaperAttachments,
    paperScans,
    generalAttachments,
    docsTabNeedsFocus,
    docsChecklist,
    docsProgressSteps,
  } = useDispatchRequestDocs(reqForDocs)

  const signedDocumentCurrent = computed(() => req.value?.signed_document?.current ?? null)
  const { costEstimate } = useRequestCostEstimate(reqForDocs)
  const timelineSteps = usePortalTimelineSteps(reqForDocs, t)

  const requestRefCode = computed(() => {
    const r = req.value
    if (!r?.id) return ''
    const d = r.created_at ? new Date(r.created_at) : new Date()
    const y = d.getFullYear()
    const m = String(d.getMonth() + 1).padStart(2, '0')
    return `REQ-${y}${m}-${String(r.id).padStart(3, '0')}`
  })

  const canUploadAttachment = computed(() => auth.hasPermission('attachment.upload'))
  const canDeleteAttachment = computed(() => auth.hasPermission('attachment.upload'))
  const canManagePaper = computed(() => auth.hasPermission('request.paper.manage'))
  const pdfExportDisabled = computed(() => req.value?.status !== 'approved')

  const isCurrentUserRequester = computed(
    () =>
      auth.user?.id != null &&
      req.value?.requester_id != null &&
      Number(auth.user.id) === Number(req.value.requester_id),
  )

  const showFillPriceSection = computed(
    () =>
      isStaffContext.value &&
      req.value?.status === 'pending' &&
      req.value?.trip_type !== 'door_to_door' &&
      auth.hasPermission('request.fill_price'),
  )

  const showD2dDecisionSection = computed(
    () =>
      isStaffContext.value &&
      req.value?.status === 'pending' &&
      req.value?.trip_type === 'door_to_door' &&
      auth.hasPermission('request.approve'),
  )

  const showDeptDecisionSection = computed(
    () =>
      isDeptContext.value &&
      req.value?.status === 'price_filled' &&
      auth.hasPermission('request.approve_dept'),
  )

  const showSignedPaperSection = computed(
    () => req.value?.status === 'approved' && isCurrentUserRequester.value,
  )

  const showStudentCountTab = computed(() => req.value?.dispatch_request_template_id != null)
  const showPassengerAdjustSection = computed(() => false)
  const passengerDispatcherOverride = computed(() => false)
  const passengerDepartLocked = computed(() => true)

  const showResetCloneBtn = computed(() => {
    if (!isCurrentUserRequester.value || !auth.hasPermission('request.create')) return false
    return ['approved', 'rejected'].includes(String(req.value?.status || ''))
  })

  const showRecurringBadge = computed(() => !!req.value?.dispatch_request_template_id)
  const showUrgentBadge = computed(() => !!req.value?.is_urgent)

  const rejectionBannerTitle = computed(() => {
    const r = req.value
    if (!r || r.status !== 'rejected') return ''
    if (r.trip_type !== 'door_to_door') {
      return t('request_detail.dept_rejected_banner_title')
    }
    return t('request_detail.rejected_banner_title_generic')
  })

  const docsNeedsPaperScan = computed(
    () =>
      req.value?.status === 'approved' &&
      paperScans.value.length === 0 &&
      canUploadAttachment.value,
  )

  const workflowCtx = computed(() => ({
    showFillPriceSection: showFillPriceSection.value,
    showDeptDecisionSection: showDeptDecisionSection.value,
    showPassengerAdjustSection: showPassengerAdjustSection.value,
    docsTabNeedsFocus: docsTabNeedsFocus.value,
    docsNeedsPaperScan: docsNeedsPaperScan.value,
  }))

  const { formTabActionCount, studentsTabActionCount, docsTabActionCount, todoItems: workflowTodoItems } =
    useRequestWorkflowSteps(reqForDocs, workflowCtx)

  const approvalTabNeedsFocus = computed(() => {
    const r = req.value
    if (!r) return false
    return !!(
      r.dispatch_package_cost_alert ||
      r.dispatch_package_budget_alert ||
      showResetCloneBtn.value ||
      showDeptDecisionSection.value ||
      showD2dDecisionSection.value ||
      showFillPriceSection.value ||
      showSignedPaperSection.value
    )
  })

  const journeyDepartLine = computed(() => {
    const r = req.value
    if (!r?.depart_at) return ''
    return `${fmtDateVi(r.depart_at)} · ${fmtTimeWindow(r.depart_at, r.arrive_by)}`
  })

  const overviewScheduleLine = computed(() => {
    const r = req.value
    if (!r) return ''
    const window = `${fmtDateVi(r.depart_at)} · ${fmtTimeWindow(r.depart_at, r.arrive_by)}`
    const dist =
      costEstimate.value?.distanceLabel != null
        ? t('request_detail.distance_badge_approx', { label: costEstimate.value.distanceLabel })
        : ''
    return dist ? `${window} · ${dist}` : window
  })

  const sectionNavItems = computed(() => {
    const items = [
      {
        id: 'form',
        label: t('request_detail.tab_form'),
        badge: formTabActionCount.value > 0 ? formTabActionCount.value : null,
        dot: formTabActionCount.value === 0 && approvalTabNeedsFocus.value,
      },
    ]
    if (showStudentCountTab.value) {
      items.push({
        id: 'students',
        label: t('request_detail.tab_students'),
        badge: studentsTabActionCount.value > 0 ? studentsTabActionCount.value : null,
      })
    }
    items.push({
      id: 'docs',
      label: t('request_detail.tab_docs'),
      badge: docsTabActionCount.value > 0 ? docsTabActionCount.value : null,
      dot: docsTabActionCount.value === 0 && docsTabNeedsFocus.value,
      dotTone: 'amber',
    })
    return items
  })

  const requesterAsideSubtitle = computed(() => {
    const u = req.value?.wizard_snapshot?.form?.requester_unit
    if (u?.trim()) return u.trim()
    const code = req.value?.requester?.employee_code
    if (code) return t('request_detail.requester_employee_line', { code })
    return ''
  })

  const requesterAsideFields = computed(() => {
    const r = req.value
    if (!r) return []
    const unitInHero = !!r.wizard_snapshot?.form?.requester_unit?.trim()
    const codeInHero = !unitInHero && !!r.requester?.employee_code?.trim()
    const rows = []
    const email = r.requester?.email?.trim()
    if (email) rows.push({ key: 'email', label: t('request_detail.lbl_email'), value: email })
    const phone = r.requester?.phone?.trim()
    if (phone) rows.push({ key: 'phone', label: t('request_detail.lbl_phone'), value: phone })
    const code = r.requester?.employee_code?.trim()
    if (code && !codeInHero) rows.push({ key: 'code', label: t('request_detail.lbl_employee_code'), value: code })
    const unit = r.wizard_snapshot?.form?.requester_unit?.trim()
    if (unit && !unitInHero) rows.push({ key: 'unit', label: t('request_detail.lbl_requester_unit'), value: unit })
    return rows
  })

  const requesterInitials = computed(() => {
    const name = req.value?.requester?.name?.trim() || ''
    if (!name) return '?'
    const parts = name.split(/\s+/).filter(Boolean)
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  })

  const passengerOrCargoLine = computed(() => {
    const r = req.value
    if (!r) return '—'
    if (r.trip_type === 'cargo' && r.wizard_snapshot?.cargoRows?.length) {
      const weights = r.wizard_snapshot.cargoRows.map((x) => x.weight).filter((w) => String(w).trim())
      if (weights.length) {
        const joined = weights.join(', ')
        const hint = /tấn|kg|ton/i.test(joined) ? '' : t('request_detail.cargo_weight_declared_hint')
        return `${joined}${hint}`
      }
    }
    const n = dispatchRequestDisplayPassengerCount(r)
    if (n > 0) {
      return t('request_detail.passengers_count_line', { n })
    }
    return '—'
  })

  function requestDetailLocaleTag() {
    return locale.value === 'en' ? 'en-GB' : 'vi-VN'
  }

  function fmt(v) {
    if (!v) return '—'
    const d = new Date(v)
    if (Number.isNaN(d.getTime())) return '—'
    const loc = requestDetailLocaleTag()
    const hour12 = locale.value === 'en'
    return d.toLocaleString(loc, {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12,
    })
  }

  function fmtDateVi(iso) {
    if (!iso) return '—'
    try {
      return new Date(iso).toLocaleDateString(requestDetailLocaleTag(), {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
      })
    } catch {
      return '—'
    }
  }

  function fmtTimeWindow(depart, arrive) {
    if (!depart) return '—'
    const loc = requestDetailLocaleTag()
    const hour12 = locale.value === 'en'
    const opt = { hour: '2-digit', minute: '2-digit', hour12 }
    const a = new Date(depart).toLocaleTimeString(loc, opt)
    if (!arrive) return a
    const b = new Date(arrive).toLocaleTimeString(loc, opt)
    return `${a} - ${b}`
  }

  function fmtStepDetail(v) {
    return fmt(v)
  }

  function formatVndCurrency(n) {
    const loc = requestDetailLocaleTag()
    const num = new Intl.NumberFormat(loc).format(Number(n))
    return `${num} ${t('dept.currency_suffix')}`
  }

  function allowedDetailTabs() {
    if (isStaffContext.value) {
      return showStudentCountTab.value
        ? STAFF_DETAIL_TABS
        : STAFF_DETAIL_TABS.filter((tab) => tab !== 'students')
    }
    return DETAIL_TABS
  }

  function tabFromRouteQuery() {
    const q = route.query.tab
    if (typeof q !== 'string') return null
    if (q === 'route' && !isStaffContext.value) return 'form'
    if (q === 'students' && !showStudentCountTab.value) return null
    return allowedDetailTabs().includes(q) ? q : null
  }

  function setActiveTab(tab) {
    if (tab === 'students' && !showStudentCountTab.value) return
    activeTab.value = tab
    const nextQuery = { ...route.query, tab }
    if (route.query.tab !== tab) {
      router.replace({ query: nextQuery })
    }
  }

  function onWorkflowNavigate({ tab, focus }) {
    if (tab) setActiveTab(tab)
    if (focus) {
      router.replace({ query: { ...route.query, tab: tab || activeTab.value, focus } })
      scrollToFocus(focus)
    }
  }

  async function scrollToFocus(focus) {
    focusHighlight.value = focus
    await nextTick()
    const id = FOCUS_TARGETS[focus]
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    window.setTimeout(() => {
      if (focusHighlight.value === focus) focusHighlight.value = null
    }, 3000)
  }

  watch(
    () => route.query.tab,
    (tab) => {
      if (typeof tab === 'string' && allowedDetailTabs().includes(tab) && activeTab.value !== tab) {
        if (tab === 'students' && !showStudentCountTab.value) return
        activeTab.value = tab
      }
    },
  )

  watch(docsTabNeedsFocus, (need, was) => {
    if (need && was !== true && req.value && tabFromRouteQuery() !== 'docs') {
      setActiveTab('docs')
    }
  })

  watch(
    () => route.query.focus,
    (focus) => {
      if (typeof focus === 'string' && FOCUS_TARGETS[focus]) {
        const tabQ = tabFromRouteQuery()
        if (tabQ) activeTab.value = tabQ
        scrollToFocus(focus)
      }
    },
    { immediate: true },
  )

  function openAttachmentPreview(a) {
    previewAttachment.value = a
    previewOpen.value = true
  }

  function closeAttachmentPreview() {
    previewOpen.value = false
    previewAttachment.value = null
  }

  async function loadAuditLogs() {
    auditLoading.value = true
    auditError.value = ''
    try {
      auditItems.value = await getDispatchRequestAuditLogs(route.params.id)
    } catch (e) {
      auditError.value = formatApiError(e, t('request_detail.audit_timeline_load_fail'))
    } finally {
      auditLoading.value = false
    }
  }

  async function load() {
    loading.value = true
    try {
      const [dr, fs] = await Promise.all([
        getDispatchRequest(route.params.id),
        getDispatchFormSettings().catch(() => null),
      ])
      req.value = dr
      formSettings.value = fs
      if (isStaffContext.value) loadAuditLogs()
      paperForm.value.paper_reference = req.value?.paper_reference ?? ''
      paperForm.value.paper_received_at = req.value?.paper_received_at
        ? toDatetimeLocalValue(new Date(req.value.paper_received_at))
        : ''
      const actual = dr.student_count_actual ?? dr.passenger_count
      passengerDraft.value = Math.max(1, Math.min(999, Math.round(Number(actual) || 1)))
      passengerPatchErr.value = ''
      const tabQ = tabFromRouteQuery()
      if (tabQ) activeTab.value = tabQ
      if (route.query.tab === 'route' && !isStaffContext.value) {
        router.replace({ query: { ...route.query, tab: 'form' } })
      }
    } finally {
      loading.value = false
    }
  }

  async function onSaveRowPrices(payload) {
    if (!req.value?.id || fillPriceActing.value) return
    fillPriceActing.value = true
    fillPriceMsg.value = ''
    try {
      await fillPriceDispatchRequest(Number(route.params.id), payload)
      showAppSuccess(t('request_detail.fill_price_success'))
      await load()
    } catch (e) {
      fillPriceMsg.value = formatApiError(e, t('request_detail.passenger_save_fail'))
      showAppError(fillPriceMsg.value)
    } finally {
      fillPriceActing.value = false
    }
  }

  async function onResetCloneRequest() {
    if (!req.value?.id || resetCloneBusy.value) return
    resetCloneBusy.value = true
    try {
      const dr = await cloneDispatchRequest(req.value.id)
      await router.push({ name: 'dispatchRequestNew', query: { replace: String(dr.id) } })
    } catch (e) {
      showAppError(formatApiError(e, t('request_detail.reset_clone_fail')))
    } finally {
      resetCloneBusy.value = false
    }
  }

  async function savePassengerDraft() {
    if (!req.value?.id || passengerSaving.value || passengerDepartLocked.value) return
    passengerSaving.value = true
    passengerPatchErr.value = ''
    try {
      const n = Math.round(Number(passengerDraft.value))
      await patchPassengerCount(req.value.id, n)
      showAppSuccess(t('request_detail.passenger_saved'))
      await load()
    } catch (e) {
      passengerPatchErr.value = formatApiError(e, t('request_detail.passenger_save_fail'))
    } finally {
      passengerSaving.value = false
    }
  }

  async function runOcr(attachmentId) {
    if (ocrBusy.value != null) return
    ocrErr.value = ''
    ocrBusy.value = attachmentId
    try {
      const updated = await runAttachmentOcr(attachmentId)
      if (updated?.ocr_status === 'queued') {
        showAppSuccess(t('request_detail.ocr_queued_toast'), '')
        await load()
        return
      }
      const list = req.value?.attachments
      if (Array.isArray(list)) {
        const idx = list.findIndex((x) => x.id === attachmentId)
        if (idx >= 0) list[idx] = { ...list[idx], ...updated }
        else list.unshift(updated)
      } else {
        await load()
      }
      showAppSuccess(t('request_detail.ocr_success_toast'), t('request_detail.toast_attachment_removed_title'))
    } catch (e) {
      ocrErr.value = formatApiError(e, t('request_detail.ocr_failed_fallback'))
      showAppError(ocrErr.value)
    } finally {
      ocrBusy.value = null
    }
  }

  function uploadPaperScan(file, onProgress) {
    return uploadAttachment({
      attachable_type: 'dispatch_request',
      attachable_id: Number(route.params.id),
      kind: 'paper_scan',
      file,
      onProgress,
    })
  }

  function uploadRequestDocument(file, onProgress) {
    return uploadAttachment({
      attachable_type: 'dispatch_request',
      attachable_id: Number(route.params.id),
      kind: 'request_attachment',
      file,
      onProgress,
    })
  }

  function highlightUploadedAttachment(payload) {
    const id = payload?.id ?? payload?.attachment?.id
    if (!id) return
    docsHighlightAttachmentId.value = id
    window.setTimeout(() => {
      docsHighlightAttachmentId.value = null
    }, 2500)
  }

  async function onDocUploaded(payload) {
    attachErr.value = ''
    await load()
    highlightUploadedAttachment(payload)
  }

  async function onPaperScanUploaded(payload) {
    await load()
    highlightUploadedAttachment(payload)
  }

  async function downloadFile(a) {
    attachErr.value = ''
    try {
      await downloadBinaryAttachmentFromApi(a.id, a.original_name || 'download')
    } catch (e) {
      attachErr.value = e?.response?.data?.message ?? t('request_detail.download_failed_fallback')
    }
  }

  async function removeAttachment(a) {
    if (!canDeleteAttachment.value) return
    const ok = await confirmAction({
      title: t('request_detail.delete_attachment_confirm_title'),
      message: t('request_detail.delete_attachment_confirm_message', {
        name: a.original_name || t('request_detail.delete_attachment_this_file'),
      }),
      confirmLabel: t('request_detail.delete_attachment_confirm_btn'),
      danger: true,
    })
    if (!ok) return
    attachErr.value = ''
    deletingId.value = a.id
    try {
      await deleteAttachment(a.id)
      await load()
      showAppSuccess(t('request_detail.toast_attachment_removed_msg'), t('request_detail.toast_attachment_removed_title'))
    } catch (e) {
      attachErr.value = e?.response?.data?.message ?? t('request_detail.attachment_delete_failed_fallback')
    } finally {
      deletingId.value = null
    }
  }

  async function doMarkPaper() {
    paperMsg.value = ''
    paperActing.value = true
    const wasReceived = req.value?.paper_status === 'received'
    try {
      const payload = {}
      if (wasReceived) {
        const ref = paperForm.value.paper_reference?.trim() ?? ''
        payload.paper_reference = ref === '' ? null : ref
      } else if (paperForm.value.paper_reference?.trim()) {
        payload.paper_reference = paperForm.value.paper_reference.trim()
      }
      if (paperForm.value.paper_received_at) {
        payload.paper_received_at = new Date(paperForm.value.paper_received_at).toISOString()
      }
      await markPaperReceived(route.params.id, payload)
      showAppSuccess(
        wasReceived ? t('request_detail.paper_saved_toast_msg') : t('request_detail.paper_marked_toast_msg'),
        t('request_detail.toast_attachment_removed_title'),
      )
      await load()
    } catch (e) {
      paperMsg.value = e?.response?.data?.message ?? t('request_detail.paper_error_generic')
    } finally {
      paperActing.value = false
    }
  }

  async function doRevertPaper() {
    const ok = await confirmAction({
      title: t('request_detail.paper_revert_confirm_title'),
      message: t('request_detail.paper_revert_confirm_message'),
      confirmLabel: t('request_detail.paper_revert_confirm_btn'),
      danger: true,
    })
    if (!ok) return
    paperMsg.value = ''
    paperRevertActing.value = true
    try {
      await revertPaperReceived(route.params.id)
      showAppSuccess(t('request_detail.paper_reverted_toast_msg'), t('request_detail.toast_attachment_removed_title'))
      await load()
    } catch (e) {
      paperMsg.value = e?.response?.data?.message ?? t('request_detail.paper_error_generic')
    } finally {
      paperRevertActing.value = false
    }
  }

  async function downloadRequestPdf() {
    if (pdfExportDisabled.value || pdfBusy.value) return
    pdfBusy.value = true
    try {
      const blob = await exportDispatchRequestPdf(Number(route.params.id))
      saveAs(blob, `de-nghi-dieu-van-${route.params.id}.pdf`)
    } catch (e) {
      pdfErr.value = e?.response?.data?.message ?? t('request_detail.pdf_export_failed_fallback')
      window.alert(pdfErr.value)
    } finally {
      pdfBusy.value = false
    }
  }

  function openDeptReject() {
    deptRejectOpen.value = true
    deptRejectReason.value = ''
    deptMsg.value = ''
  }

  function closeDeptReject() {
    deptRejectOpen.value = false
    deptRejectReason.value = ''
    deptMsg.value = ''
  }

  async function submitDeptReject() {
    const reason = deptRejectReason.value.trim()
    if (!reason) {
      deptMsg.value = t('request_detail.dept_reject_reason_placeholder')
      return
    }
    deptMsg.value = ''
    deptActing.value = true
    try {
      await deptDecideDispatchRequest(
        Number(route.params.id),
        {
          decision: 'reject',
          rejection_reason: reason,
        },
        { idempotencyKey: deptDecisionIdem.get() },
      )
      deptDecisionIdem.reset()
      closeDeptReject()
      await load()
      showAppSuccess(t('requests_page.reject_success_body'), t('requests_page.reject_success_title'))
    } catch (e) {
      deptMsg.value = e?.response?.data?.message ?? formatApiError(e, 'Error')
    } finally {
      deptActing.value = false
    }
  }

  async function onDeptApproveClick() {
    const ok = await confirmAction({
      title: t('request_detail.dept_approve_confirm_title'),
      message: t('request_detail.dept_approve_confirm_message'),
      confirmLabel: t('request_detail.dept_approve'),
    })
    if (!ok) return
    deptMsg.value = ''
    deptActing.value = true
    try {
      const res = await deptDecideDispatchRequest(
        Number(route.params.id),
        { decision: 'approve' },
        { idempotencyKey: deptDecisionIdem.get() },
      )
      deptDecisionIdem.reset()
      const code = requestRefCode.value || `REQ-${route.params.id}`
      const tripId = res?.trip?.id
      if (auth.isDeptHeadOnly()) {
        if (tripId) {
          await router.push({ name: 'deptDashboard' })
          showAppSuccess(t('requests_page.approve_success_body_trip', { code }), t('requests_page.approve_success_title'))
        } else {
          showAppSuccess(t('requests_page.approve_success_body', { code }), t('requests_page.approve_success_title'), {
            navigateTo: '/dept',
            primaryLabel: t('requests_page.approve_success_go_trips'),
          })
        }
      } else if (tripId) {
        await router.push(`/trips/${tripId}`)
        showAppSuccess(t('requests_page.approve_success_body_trip', { code }), t('requests_page.approve_success_title'))
      } else {
        showAppSuccess(t('requests_page.approve_success_body', { code }), t('requests_page.approve_success_title'), {
          navigateTo: '/trips',
          primaryLabel: t('requests_page.approve_success_go_trips'),
        })
      }
    } catch (e) {
      deptMsg.value = e?.response?.data?.message ?? formatApiError(e, 'Error')
    } finally {
      deptActing.value = false
    }
  }

  function openD2dReject() {
    d2dRejectOpen.value = true
    d2dRejectReason.value = ''
    d2dMsg.value = ''
  }

  function closeD2dReject() {
    d2dRejectOpen.value = false
    d2dRejectReason.value = ''
    d2dMsg.value = ''
  }

  async function submitD2dReject() {
    const reason = d2dRejectReason.value.trim()
    if (!reason) {
      d2dMsg.value = t('request_detail.dept_reject_reason_placeholder')
      return
    }
    d2dMsg.value = ''
    d2dActing.value = true
    try {
      await decideDispatchRequest(
        Number(route.params.id),
        { decision: 'reject', rejection_reason: reason },
        { idempotencyKey: d2dDecisionIdem.get() },
      )
      d2dDecisionIdem.reset()
      closeD2dReject()
      await load()
      showAppSuccess(t('requests_page.reject_success_body'), t('requests_page.reject_success_title'))
    } catch (e) {
      d2dMsg.value = formatApiError(e, 'Error')
    } finally {
      d2dActing.value = false
    }
  }

  async function onD2dApproveClick() {
    const ok = await confirmAction({
      title: t('request_detail.d2d_approve_confirm_title'),
      message: t('request_detail.d2d_approve_confirm_message'),
      confirmLabel: t('request_detail.d2d_approve_confirm_btn'),
    })
    if (!ok) return
    d2dMsg.value = ''
    d2dActing.value = true
    try {
      const res = await decideDispatchRequest(
        Number(route.params.id),
        { decision: 'approve' },
        { idempotencyKey: d2dDecisionIdem.get() },
      )
      d2dDecisionIdem.reset()
      const code = requestRefCode.value || `REQ-${route.params.id}`
      const tripId = res?.trip?.id
      if (tripId) {
        await router.push(`/trips/${tripId}`)
        showAppSuccess(t('requests_page.approve_success_body_trip', { code }), t('requests_page.approve_success_title'))
      } else {
        await load()
        showAppSuccess(t('requests_page.approve_success_body', { code }), t('requests_page.approve_success_title'), {
          navigateTo: '/trips',
          primaryLabel: t('requests_page.approve_success_go_trips'),
        })
      }
    } catch (e) {
      d2dMsg.value = formatApiError(e, 'Error')
    } finally {
      d2dActing.value = false
    }
  }

  function uploadSignedPaper(file, onProgress) {
    signedUploadErr.value = ''
    return uploadAttachment({
      attachable_type: 'dispatch_request',
      attachable_id: Number(route.params.id),
      kind: 'signed_paper',
      file,
      onProgress,
    })
  }

  function onSignedUploaded() {
    signedUploadErr.value = ''
    load()
  }

  async function onSignedRerunOcr() {
    const id = signedDocumentCurrent.value?.id
    if (!id || signedOcrBusy.value) return
    signedOcrBusy.value = true
    ocrErr.value = ''
    try {
      await rerunSignedDocumentOcr(Number(id))
      showAppSuccess(t('request_detail.ocr_queued_toast'), '')
      await load()
    } catch (e) {
      ocrErr.value = formatApiError(e, t('request_detail.ocr_failed_fallback'))
      showAppError(ocrErr.value)
    } finally {
      signedOcrBusy.value = false
    }
  }

  async function onSignedVerify(decision) {
    const id = signedDocumentCurrent.value?.id
    if (!id || signedVerifyBusy.value) return
    signedVerifyBusy.value = true
    try {
      await verifySignedDocument(Number(id), decision)
      showAppSuccess(t('request_detail.signed_doc_verify_approve'), '')
      await load()
    } catch (e) {
      showAppError(formatApiError(e, t('request_detail.ocr_failed_fallback')))
    } finally {
      signedVerifyBusy.value = false
    }
  }

  async function copyRejectionReason() {
    const text = String(req.value?.rejection_reason ?? '').trim()
    if (!text) return
    try {
      await navigator.clipboard.writeText(text)
      copyRejectionFeedback.value = true
      if (copyRejectionTimer) clearTimeout(copyRejectionTimer)
      copyRejectionTimer = setTimeout(() => {
        copyRejectionFeedback.value = false
      }, 2000)
    } catch {
      showAppError(t('request_detail.copy_rejection_failed'))
    }
  }

  onMounted(load)
  watch(() => route.params.id, load)
  onBeforeUnmount(() => {
    if (copyRejectionTimer) clearTimeout(copyRejectionTimer)
  })

  return {
    route,
    t,
    labelTripType,
    req,
    loading,
    backTo,
    backAriaLabel,
    cloneBannerContext,
    requestRefCode,
    showRecurringBadge,
    showUrgentBadge,
    pdfExportDisabled,
    pdfBusy,
    downloadRequestPdf,
    sectionNavItems,
    activeTab,
    setActiveTab,
    rejectionBannerTitle,
    copyRejectionFeedback,
    copyRejectionReason,
    workflowTodoItems,
    onWorkflowNavigate,
    timelineSteps,
    journeyDepartLine,
    overviewScheduleLine,
    requesterInitials,
    requesterAsideSubtitle,
    requesterAsideFields,
    passengerOrCargoLine,
    showD2dDecisionSection,
    showDeptDecisionSection,
    showFillPriceSection,
    focusHighlight,
    costEstimate,
    formatVndCurrency,
    d2dActing,
    d2dMsg,
    d2dRejectOpen,
    d2dRejectReason,
    openD2dReject,
    closeD2dReject,
    submitD2dReject,
    onD2dApproveClick,
    deptActing,
    deptMsg,
    deptRejectOpen,
    deptRejectReason,
    openDeptReject,
    closeDeptReject,
    submitDeptReject,
    onDeptApproveClick,
    fillPriceActing,
    fillPriceMsg,
    onSaveRowPrices,
    showStudentCountTab,
    showResetCloneBtn,
    resetCloneBusy,
    onResetCloneRequest,
    signedPaperAttachments,
    showSignedPaperSection,
    signedUploadErr,
    uploadSignedPaper,
    onSignedUploaded,
    downloadFile,
    approvalTabNeedsFocus,
    passengerDraft,
    passengerSaving,
    passengerPatchErr,
    passengerDepartLocked,
    passengerDispatcherOverride,
    savePassengerDraft,
    fmt,
    fmtStepDetail,
    docsProgressSteps,
    docsChecklist,
    docsHighlightAttachmentId,
    generalAttachments,
    paperScans,
    attachErr,
    ocrErr,
    ocrBusy,
    deletingId,
    canUploadAttachment,
    canDeleteAttachment,
    canManagePaper,
    signedDocumentCurrent,
    signedOcrBusy,
    signedVerifyBusy,
    uploadRequestDocument,
    uploadPaperScan,
    openAttachmentPreview,
    removeAttachment,
    runOcr,
    onDocUploaded,
    onPaperScanUploaded,
    onSignedRerunOcr,
    onSignedVerify,
    paperForm,
    paperActing,
    paperRevertActing,
    paperMsg,
    doMarkPaper,
    doRevertPaper,
    previewOpen,
    previewAttachment,
    closeAttachmentPreview,
    auditItems,
    auditLoading,
    auditError,
  }
}
