<template>
  <div class="mx-auto max-w-7xl px-4 pb-16 pt-6 sm:px-6 lg:pb-24">
    <PortalSuccessCard
      v-if="welcomeOpen"
      class="mb-6"
      :title="t('portal.welcome_banner_title')"
      :badge="t('portal.status_pending')"
      :request-id="Number(route.params.id)"
      :hint="t('portal.welcome_banner_hint')"
    >
      <template #actions>
        <button
          type="button"
          class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow hover:bg-slate-50"
          @click="welcomeOpen = false"
        >
          {{ t('portal.welcome_banner_dismiss') }}
        </button>
      </template>
    </PortalSuccessCard>

    <div v-if="loading" class="py-14 text-center text-sm text-slate-500">{{ t('portal.detail_loading') }}</div>

    <div v-else-if="detailError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
      {{ detailError }}
      <RouterLink :to="{ name: 'portalHome' }" class="mt-3 block font-semibold text-indigo-700 underline">{{ t('portal.back_home') }}</RouterLink>
    </div>

    <template v-else-if="req">
      <div class="flex flex-col gap-4 border-b border-slate-200/80 pb-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <RouterLink
            :to="{ name: 'portalHome' }"
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50/70 hover:text-indigo-700"
            :aria-label="t('portal.back_list')"
          >
            <ArrowLeftIcon class="h-5 w-5" aria-hidden="true" />
          </RouterLink>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <h1 class="font-mono text-lg font-bold text-slate-900 sm:text-xl">#{{ req.id }}</h1>
              <StatusBadge :status="req.status" />
              <span
                v-if="req.is_urgent"
                class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700"
              >
                {{ t('portal.badge_urgent') }}
              </span>
            </div>
            <p class="mt-2 text-sm leading-relaxed text-slate-700">
              {{ routeSummary }}
            </p>
            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
              <span v-if="departFmt">{{ departFmt }}</span>
              <span v-if="tripTypeLabel">{{ tripTypeLabel }}</span>
              <span v-if="pollingRefreshing" class="font-medium text-teal-700">{{ t('portal.auto_refresh_indicator') }}</span>
            </div>
          </div>
        </div>

        <div class="flex w-full shrink-0 flex-col gap-2 sm:max-w-sm lg:w-[14rem]">
          <button
            type="button"
            class="inline-flex min-h-[48px] w-full flex-col rounded-2xl border px-4 py-2.5 text-left text-sm font-semibold shadow-md transition-all"
            :class="
              pdfExportDisabled ? 'cursor-not-allowed border-slate-100 bg-slate-50 text-slate-400 opacity-95' : 'border-teal-200 bg-teal-50 text-teal-900 hover:bg-teal-100'
            "
            :disabled="pdfBusy || pdfExportDisabled"
            :title="pdfExportDisabled ? t('portal.pdf_locked_tooltip') : t('portal.pdf_btn_label')"
            @click="downloadPdf"
          >
            <span class="flex items-center gap-2">
              <span
                v-if="pdfBusy"
                class="h-5 w-5 shrink-0 animate-spin rounded-full border-2 border-teal-600/35 border-t-teal-800"
              />
              <DocumentArrowDownIcon v-else-if="!pdfExportDisabled" class="h-5 w-5 shrink-0 text-teal-800" aria-hidden="true" />
              <DocumentArrowDownIcon v-else class="h-5 w-5 shrink-0 text-slate-300" aria-hidden="true" />
              <span>{{ pdfPrimaryLabel }}</span>
            </span>
          </button>
          <p v-if="pdfExportDisabled" class="max-w-xl text-xs leading-snug text-slate-500">
            {{ t('portal.pdf_locked_tooltip') }}
          </p>
        </div>
      </div>

      <PortalStatusTimeline class="mt-8" :title="t('portal.timeline_heading')" :steps="timelineSteps" />

      <section
        v-if="req.status === 'approved'"
        class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
      >
        <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/80 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
          <div class="flex min-w-0 items-start gap-3">
            <PdfFileIcon class="mt-0.5 shrink-0" size-class="h-10 w-8" />
            <div class="min-w-0">
              <h2 class="text-sm font-bold text-slate-900 sm:text-base">{{ t('portal.pdf_preview_title') }}</h2>
              <p class="mt-1 text-xs leading-relaxed text-slate-600 sm:text-sm">{{ t('portal.pdf_preview_lead') }}</p>
            </div>
          </div>
          <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
            <button
              type="button"
              class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="!pdfBlobUrl"
              @click="openPdfInNewTab"
            >
              <ArrowTopRightOnSquareIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ t('portal.pdf_preview_open_tab') }}
            </button>
            <button
              type="button"
              class="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-semibold text-teal-900 shadow-sm transition hover:bg-teal-100 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="pdfBusy || !pdfBlobUrl"
              @click="downloadPdf"
            >
              <DocumentArrowDownIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ t('portal.pdf_btn_label') }}
            </button>
          </div>
        </div>

        <div class="relative bg-slate-100 p-3 sm:p-4">
          <div
            v-if="pdfPreviewLoading"
            class="flex min-h-[min(75vh,640px)] flex-col items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white py-16 text-sm text-slate-600"
          >
            <span
              class="h-9 w-9 animate-spin rounded-full border-2 border-teal-500/30 border-t-teal-700"
              aria-hidden="true"
            />
            {{ t('portal.pdf_export_loading') }}
          </div>
          <div
            v-else-if="pdfPreviewError"
            class="flex min-h-[min(40vh,320px)] flex-col items-center justify-center gap-3 rounded-xl border border-rose-200 bg-rose-50/80 px-4 py-10 text-center"
          >
            <p class="max-w-md text-sm text-rose-800">{{ pdfPreviewError }}</p>
            <button
              type="button"
              class="rounded-xl border border-rose-200 bg-white px-4 py-2 text-xs font-semibold text-rose-900 shadow-sm hover:bg-rose-50"
              @click="retryPdfPreview"
            >
              {{ t('portal.pdf_preview_retry') }}
            </button>
          </div>
          <div
            v-else-if="pdfBlobUrl"
            class="overflow-hidden rounded-xl border border-slate-300/90 bg-white shadow-inner ring-1 ring-slate-900/5"
          >
            <iframe
              :src="pdfIframeSrc"
              class="block h-[min(75vh,900px)] w-full border-0 bg-white"
              :title="t('portal.pdf_preview_iframe_title')"
            />
          </div>
        </div>
      </section>

      <div
        v-if="req.status === 'rejected' && req.rejection_reason"
        class="mt-8 rounded-2xl border-2 border-rose-300 bg-rose-50 px-5 py-4 shadow-sm"
      >
        <div class="flex items-start gap-3">
          <XCircleIcon class="h-8 w-8 shrink-0 text-rose-600" aria-hidden="true" />
          <div class="min-w-0 flex-1">
            <p class="font-semibold text-rose-950">{{ t('portal.rejected_title') }}</p>
            <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-rose-900/95">{{ req.rejection_reason }}</p>
            <button
              type="button"
              class="mt-4 inline-flex min-h-[40px] items-center gap-2 rounded-xl border border-rose-200 bg-white px-4 text-xs font-semibold text-rose-900 shadow-sm hover:bg-rose-50"
              @click="copyRejectionReason"
            >
              <ClipboardDocumentIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
              {{ copyRejectionFeedback ? t('portal.copied') : t('portal.copy_rejection') }}
            </button>
          </div>
        </div>
      </div>

      <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-8">
          <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm text-sm">
            <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ t('portal.detail_facts') }}</h2>
            <dl class="mt-4 grid gap-3 sm:grid-cols-2">
              <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ t('portal.origin') }}</dt>
                <dd class="mt-0.5 font-medium text-slate-900">{{ req.origin || '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ t('portal.destination') }}</dt>
                <dd class="mt-0.5 font-medium text-slate-900">{{ req.destination || '—' }}</dd>
              </div>
              <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ t('portal.notes') }}</dt>
                <dd class="mt-0.5 whitespace-pre-wrap text-slate-800">{{ req.notes || '—' }}</dd>
              </div>
              <div v-if="servicePriceFmt">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ t('portal.service_price_label') }}</dt>
                <dd class="mt-0.5 font-medium text-slate-900">{{ servicePriceFmt }}</dd>
              </div>
            </dl>
          </section>
        </div>

        <aside class="min-w-0 space-y-8 lg:border-l lg:border-slate-200/75 lg:pl-8 xl:space-y-8">
          <div class="sticky top-[5.75rem] space-y-6">
            <PortalSignedDocUpload
              v-if="showSignedSection"
              :attachments="signedPaperAttachments"
              :upload-component-key="`portal-signed-${req.id}-${uploadKeySeed}`"
              :upload-fn="uploadSignedPaper"
              :error="signedUploadErr"
              @download="downloadAttachment"
              @uploaded="onSignedUploaded"
            />
            <template v-else>
              <PortalStatusHint :req="req" />
              <p class="text-xs leading-relaxed text-slate-500">{{ t('portal.signed_gate_hint') }}</p>
            </template>

            <div class="flex flex-wrap gap-3 text-xs font-semibold text-slate-500">
              {{ t('portal.detail_help_footer') }}
            </div>
          </div>
        </aside>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowLeftIcon, ArrowTopRightOnSquareIcon, ClipboardDocumentIcon, DocumentArrowDownIcon, XCircleIcon } from '@heroicons/vue/24/outline'
import { saveAs } from 'file-saver'
import {
  downloadPortalAttachmentBlob,
  exportPortalDispatchRequestPdf,
  getPortalDispatchRequest,
  uploadPortalSignedPaper,
} from '../../api/requests'
import { formatApiError } from '../../api/http'
import { usePortalTimelineSteps } from '../../composables/usePortalTimelineSteps.js'
import { usePortalDetailPoll } from '../../composables/usePortalDetailPoll.js'
import StatusBadge from '../../components/ui/StatusBadge.vue'
import PortalSuccessCard from '../../components/portal/PortalSuccessCard.vue'
import PortalStatusTimeline from '../../components/portal/PortalStatusTimeline.vue'
import PortalSignedDocUpload from '../../components/portal/PortalSignedDocUpload.vue'
import PortalStatusHint from '../../components/portal/PortalStatusHint.vue'
import PdfFileIcon from '../../components/icons/PdfFileIcon.vue'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()

const loading = ref(true)
const detailError = ref('')
const req = ref(null)

const pdfBusy = ref(false)
const pdfBlobUrl = ref('')
const pdfPreviewLoading = ref(false)
const pdfPreviewError = ref('')
const signedUploadErr = ref('')
const uploadKeySeed = ref(0)
const welcomeOpen = ref(false)
const pollingRefreshing = ref(false)
const copyRejectionFeedback = ref(false)

let copyRejectionTimer = null

const timelineSteps = usePortalTimelineSteps(req, t)

const pdfPrimaryLabel = computed(() => {
  if (pdfBusy.value) return t('portal.pdf_export_loading')
  if (pdfExportDisabled.value) return t('portal.export_pdf')
  return t('portal.pdf_btn_label')
})

function revokePdfPreviewUrl() {
  if (pdfBlobUrl.value && pdfBlobUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(pdfBlobUrl.value)
  }
  pdfBlobUrl.value = ''
  pdfPreviewLoading.value = false
  pdfPreviewError.value = ''
}

watch(
  () => route.params.id,
  () => {
    if (route.name === 'portalRequestDetail') {
      welcomeOpen.value = false
      revokePdfPreviewUrl()
      load()
    }
  },
)

watch(
  () => req.value?.status,
  (s) => {
    if (s === 'approved') loadPdfPreview()
  },
)

async function load(opts = {}) {
  const silent = opts.silent === true
  if (!silent) {
    loading.value = true
    detailError.value = ''
  } else {
    pollingRefreshing.value = true
  }
  try {
    const id = Number(route.params.id)
    if (!Number.isFinite(id)) {
      if (!silent) {
        detailError.value = t('portal.detail_invalid_id')
        req.value = null
      }
      return
    }
    const data = await getPortalDispatchRequest(id)
    req.value = data
    if (!silent) detailError.value = ''
  } catch (e) {
    if (!silent) {
      detailError.value = formatApiError(e, t('portal.detail_load_fail'))
      req.value = null
    }
  } finally {
    if (!silent) loading.value = false
    else pollingRefreshing.value = false
  }
}

usePortalDetailPoll(load, req)

onMounted(async () => {
  await load()
  if (route.query.created === '1') {
    welcomeOpen.value = true
    const q = { ...route.query }
    delete q.created
    router.replace({ query: q })
  }
})

onBeforeUnmount(() => {
  if (copyRejectionTimer) clearTimeout(copyRejectionTimer)
  revokePdfPreviewUrl()
})

const routeSummary = computed(() => {
  const r = req.value
  if (!r) return ''
  const o = (r.origin || '').trim()
  const d = (r.destination || '').trim()
  if (!o && !d) return t('portal.detail_no_locations')
  return `${o || '…'} → ${d || '…'}`
})

const departFmt = computed(() => {
  const r = req.value
  if (!r?.depart_at) return ''
  try {
    return new Date(r.depart_at).toLocaleString('vi-VN', {
      weekday: 'short',
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return ''
  }
})

const tripTypeLabel = computed(() => {
  const tt = req.value?.trip_type
  if (!tt) return ''
  return t(`dispatch_wizard.trip_short.${tt}`)
})

const pdfExportDisabled = computed(() => req.value?.status !== 'approved')

const signedPaperAttachments = computed(() =>
  (req.value?.attachments ?? []).filter((a) => a.kind === 'signed_paper'),
)

const showSignedSection = computed(() => req.value?.status === 'approved')

const pdfIframeSrc = computed(() => {
  const url = pdfBlobUrl.value
  if (!url) return ''
  return `${url}#view=FitH`
})

const servicePriceFmt = computed(() => {
  const p = req.value?.service_price
  if (p == null || p === '') return null
  return `${new Intl.NumberFormat('vi-VN').format(Number(p))} VNĐ`
})

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
    window.alert(t('portal.copy_failed'))
  }
}

async function loadPdfPreview() {
  if (!req.value?.id || req.value.status !== 'approved') return
  if (pdfBlobUrl.value || pdfPreviewLoading.value) return
  pdfPreviewLoading.value = true
  pdfPreviewError.value = ''
  try {
    const blob = await exportPortalDispatchRequestPdf(Number(req.value.id))
    if (!blob || blob.size === 0) {
      throw new Error(t('portal.pdf_preview_failed'))
    }
    pdfBlobUrl.value = URL.createObjectURL(blob)
  } catch (e) {
    pdfPreviewError.value = formatApiError(e, t('portal.pdf_preview_failed'))
  } finally {
    pdfPreviewLoading.value = false
  }
}

async function retryPdfPreview() {
  revokePdfPreviewUrl()
  await loadPdfPreview()
}

function openPdfInNewTab() {
  if (!pdfBlobUrl.value) return
  window.open(pdfIframeSrc.value || pdfBlobUrl.value, '_blank', 'noopener,noreferrer')
}

async function downloadPdf() {
  if (!req.value?.id || pdfExportDisabled.value || pdfBusy.value) return
  pdfBusy.value = true
  try {
    const blob = await exportPortalDispatchRequestPdf(Number(req.value.id))
    saveAs(blob, `de-nghi-dieu-van-${req.value.id}.pdf`)
  } catch (e) {
    window.alert(formatApiError(e, t('portal.pdf_download_fail')))
  } finally {
    pdfBusy.value = false
  }
}

function uploadSignedPaper(file, onProgress) {
  return uploadPortalSignedPaper(Number(route.params.id), file, onProgress)
}

async function downloadAttachment(att) {
  signedUploadErr.value = ''
  try {
    const blob = await downloadPortalAttachmentBlob(Number(route.params.id), att.id)
    saveAs(blob, att.original_name || `attachment-${att.id}`)
  } catch (e) {
    signedUploadErr.value = formatApiError(e, t('portal.signed_download_fail'))
  }
}

async function onSignedUploaded() {
  signedUploadErr.value = ''
  uploadKeySeed.value += 1
  await load()
}
</script>
