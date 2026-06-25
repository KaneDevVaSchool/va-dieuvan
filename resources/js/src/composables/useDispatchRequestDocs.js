import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

/** @param {object|null|undefined} attachment */
export function attachmentMime(attachment) {
  return String(attachment?.mime_type || attachment?.mime || '').toLowerCase()
}

/** @param {object|null|undefined} attachment */
export function isAttachmentPreviewable(attachment) {
  if (!attachment) return false
  const mime = attachmentMime(attachment)
  if (mime.startsWith('image/') || mime.includes('pdf')) return true
  const name = String(attachment.original_name || '')
  return /\.(jpe?g|png|gif|webp|bmp|heic|pdf)$/i.test(name)
}

export function isPreviewableMime(mime) {
  const m = String(mime || '').toLowerCase()
  return m.startsWith('image/') || m.includes('pdf')
}

/**
 * @param {import('vue').Ref<object|null>|import('vue').ComputedRef<object|null>} reqRef
 */
export function useDispatchRequestDocs(reqRef) {
  const { t } = useI18n()

  const signedPaperAttachments = computed(() => {
    const list = reqRef.value?.attachments ?? []
    return list.filter((a) => a.kind === 'signed_paper')
  })

  const paperScans = computed(() => {
    const list = reqRef.value?.attachments ?? []
    return list.filter((a) => a.kind === 'paper_scan')
  })

  const generalAttachments = computed(() => {
    const list = reqRef.value?.attachments ?? []
    return list.filter((a) => a.kind !== 'paper_scan' && a.kind !== 'signed_paper')
  })

  const docsTabNeedsFocus = computed(() => {
    const r = reqRef.value
    if (!r || r.status !== 'approved') return false
    const current = r.signed_document?.current
    if (current) return false
    return signedPaperAttachments.value.length === 0
  })

  const docsChecklist = computed(() => {
    const r = reqRef.value
    const hasGeneral = generalAttachments.value.length > 0
    const hasSigned = signedPaperAttachments.value.length > 0
    const hasScan = paperScans.value.length > 0
    const approved = r?.status === 'approved'

    return [
      {
        key: 'attachments',
        label: t('request_detail.docs_check_attachments'),
        done: hasGeneral,
        scrollTarget: 'request-docs-general',
      },
      {
        key: 'signed',
        label: t('request_detail.docs_check_signed'),
        done: hasSigned,
        disabled: !approved,
        scrollTarget: 'request-docs-signed',
      },
      {
        key: 'scan',
        label: t('request_detail.docs_check_scan'),
        done: hasScan || r?.paper_status === 'received',
        disabled: !approved,
        scrollTarget: 'request-docs-paper',
      },
    ]
  })

  const docsProgressSteps = computed(() => {
    const r = reqRef.value
    const hasGeneral = generalAttachments.value.length > 0
    const hasSigned = signedPaperAttachments.value.length > 0
    const hasScan = paperScans.value.length > 0
    const paperReceived = r?.paper_status === 'received'
    const approved = r?.status === 'approved'
    const current = r?.signed_document?.current
    const verification = current?.verification_status
    const ocrDone = current?.ocr_status === 'completed'

    const signedState = !approved
      ? 'upcoming'
      : hasSigned || current
        ? verification === 'auto_pass' || verification === 'verified'
          ? 'done'
          : 'current'
        : 'current'

    const scanState = !approved
      ? 'upcoming'
      : hasScan
        ? paperReceived
          ? 'done'
          : 'current'
        : ocrDone && (verification === 'auto_pass' || verification === 'verified' || verification === 'manual_review')
          ? 'current'
          : hasSigned
            ? 'current'
            : approved
              ? 'current'
              : 'upcoming'

    return [
      {
        key: 'attachments',
        label: t('request_detail.docs_step_attachments'),
        state: hasGeneral ? 'done' : approved ? 'current' : 'upcoming',
      },
      {
        key: 'signed',
        label: t('request_detail.docs_step_signed'),
        state: signedState,
      },
      {
        key: 'paper_ocr',
        label: t('request_detail.docs_step_paper'),
        state: scanState,
      },
    ]
  })

  function fmtFileSize(bytes) {
    const n = Number(bytes)
    if (!Number.isFinite(n) || n < 0) return '—'
    if (n < 1024) return `${n} B`
    if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
    return `${(n / (1024 * 1024)).toFixed(1)} MB`
  }

  function attachmentKindLabel(kind) {
    const k = kind || 'file'
    const key = `request_detail.attachment_kind_${k}`
    const translated = t(key)
    return translated !== key ? translated : k
  }

  function isOcrStub(attachment) {
    const engine = attachment?.ocr_meta?.engine
    return engine === 'stub' || engine === undefined
  }

  return {
    signedPaperAttachments,
    paperScans,
    generalAttachments,
    docsTabNeedsFocus,
    docsChecklist,
    docsProgressSteps,
    fmtFileSize,
    attachmentKindLabel,
    isOcrStub,
    isPreviewableMime,
  }
}
