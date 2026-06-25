<template>
  <div id="request-docs-panel" class="space-y-5 text-[90%] leading-snug">
    <!-- Step strip -->
    <ol
      class="flex items-start gap-0"
      :aria-label="t('request_detail.docs_progress_aria')"
    >
      <li
        v-for="(step, index) in flowSteps"
        :key="step.key"
        class="flex min-w-0 flex-1 flex-col items-center"
      >
        <div class="flex w-full items-center">
          <div
            v-if="index > 0"
            class="h-0.5 flex-1 transition-colors"
            :class="connectorClassBefore(index)"
            aria-hidden="true"
          />
          <span
            class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition-all"
            :class="stepNodeClass(step.state)"
          >
            <CheckIcon v-if="step.state === 'done'" class="h-4 w-4" aria-hidden="true" />
            <span v-else-if="step.state === 'current'" class="h-2 w-2 rounded-full bg-current" aria-hidden="true" />
            <span v-else class="text-[10px] tabular-nums text-slate-400">{{ index + 1 }}</span>
          </span>
          <div
            v-if="index < flowSteps.length - 1"
            class="h-0.5 flex-1 transition-colors"
            :class="connectorClassAfter(index)"
            aria-hidden="true"
          />
        </div>
        <p
          class="mt-2 max-w-[7rem] text-center text-[11px] font-medium leading-tight sm:max-w-none sm:text-xs"
          :class="step.state === 'upcoming' ? 'text-slate-400' : 'text-slate-800'"
        >
          {{ step.label }}
        </p>
      </li>
    </ol>

    <!-- Hero: phiếu đã ký / scan chính -->
    <section
      id="request-docs-signed"
      class="rounded-2xl bg-slate-50/80 p-1 ring-1 ring-slate-200/60"
      :aria-labelledby="heroHeadingId"
    >
      <div class="rounded-xl bg-white px-4 py-4 sm:px-5 sm:py-5">
        <header class="mb-4 flex flex-wrap items-start justify-between gap-2">
          <div>
            <h2 :id="heroHeadingId" class="text-sm font-medium tracking-tight text-slate-900 sm:text-base">
              {{ t('request_detail.docs_signed_paper_heading') }}
            </h2>
            <p class="mt-0.5 text-xs leading-relaxed text-slate-500 sm:text-sm">
              {{ t('request_detail.docs_signed_paper_lead') }}
            </p>
          </div>
          <span
            v-if="heroComplete"
            class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2.5 py-1 text-[11px] font-medium text-teal-800 ring-1 ring-teal-200/80"
          >
            <CheckIcon class="h-3.5 w-3.5" aria-hidden="true" />
            {{ t('request_detail.docs_step_3_label') }}
          </span>
          <span
            v-else-if="req?.status !== 'approved'"
            class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600"
          >
            {{ t('request_detail.lbl_status_short') }}: {{ req?.status }}
          </span>
        </header>

        <RequestSignedDocumentStatus
          :current="signedDocumentCurrent"
          :can-manage="canManageSignedDocument"
          :ocr-busy="signedOcrBusy"
          :verify-busy="signedVerifyBusy"
          @rerun-ocr="$emit('signed-rerun-ocr')"
          @verify="(d) => $emit('signed-verify', d)"
        />

        <div v-if="!heroHasFiles" class="space-y-3">
          <div
            v-if="canHeroUpload && heroUploadFn"
            class="overflow-hidden rounded-xl border border-dashed border-slate-200 bg-gradient-to-b from-slate-50/80 to-white transition hover:border-teal-300/80"
          >
            <div class="flex flex-col items-center px-4 py-8 text-center sm:py-10">
              <CloudArrowUpIcon class="h-10 w-10 text-slate-300 sm:h-11 sm:w-11" aria-hidden="true" />
              <p class="mt-3 text-sm font-medium text-slate-900">
                {{ t('request_detail.docs_hero_empty_title') }}
              </p>
              <p class="mt-1 max-w-md text-xs text-slate-500 sm:text-sm">
                {{ t('request_detail.docs_hero_empty_sub') }}
              </p>
            </div>
            <div class="border-t border-slate-100 bg-white/80 px-3 py-3 sm:px-4">
              <FileUpload
                :key="`hero-${requestId}-0`"
                :label="t('request_detail.attach_signed_paper_label')"
                :hint="t('request_detail.docs_hero_empty_sub')"
                drag-drop
                :upload-fn="heroUploadFn"
                @uploaded="onHeroUploaded"
              />
            </div>
          </div>
          <p
            v-else
            class="rounded-xl border border-dashed border-amber-200/70 bg-amber-50/40 px-4 py-6 text-center text-sm text-amber-950/90"
          >
            {{ t('request_detail.docs_signed_paper_empty') }}
          </p>
          <p v-if="signedUploadErr" class="text-xs font-medium text-rose-600">{{ signedUploadErr }}</p>
        </div>

        <ul v-else class="space-y-3">
          <li
            v-for="a in heroFiles"
            :key="a.id"
            class="overflow-hidden rounded-xl ring-1 ring-slate-200/80 transition"
            :class="highlightAttachmentId === a.id ? 'ring-2 ring-teal-400/70 bg-teal-50/20' : 'bg-white'"
          >
            <div class="flex flex-col gap-3 p-3 sm:flex-row sm:items-start sm:p-4">
              <div
                class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-100 sm:h-16 sm:w-16"
              >
                <img
                  v-if="isImageMime(a.mime_type) && a.url"
                  :src="a.url"
                  alt=""
                  class="h-full w-full object-cover"
                />
                <DocumentTextIcon v-else class="h-7 w-7 text-slate-400" aria-hidden="true" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-900" :title="fileTitle(a)">
                  {{ fileTitle(a) }}
                </p>
                <p class="mt-0.5 text-xs text-slate-500">
                  {{ fmtFileSize(a.size_bytes) }}
                  <span v-if="a.kind" class="text-slate-400"> · {{ attachmentKindLabel(a.kind) }}</span>
                </p>
                <p class="mt-1 text-xs tabular-nums text-slate-500">
                  {{ t('request_detail.docs_hero_uploaded_by') }}
                  <span class="font-normal text-slate-700">{{ uploaderLabel(a) }}</span>
                  <span v-if="a.created_at" class="text-slate-400"> · {{ formatDateTime(a.created_at) }}</span>
                </p>

                <div class="mt-2 flex flex-wrap items-center gap-2">
                  <span
                    v-if="ocrBusy === a.id"
                    class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-medium text-violet-800"
                  >
                    <span
                      class="h-3 w-3 animate-spin rounded-full border-2 border-violet-300 border-t-violet-700"
                      aria-hidden="true"
                    />
                    {{ t('request_detail.docs_ocr_running') }}
                  </span>
                  <span
                    v-else-if="a.ocr_processed_at"
                    class="inline-flex items-center gap-1 rounded-full bg-teal-50 px-2 py-0.5 text-[11px] font-medium text-teal-800 ring-1 ring-teal-200/60"
                  >
                    <SparklesIcon class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ t('request_detail.docs_ocr_done_badge') }}
                  </span>
                  <button
                    v-else-if="canRunOcrOn(a)"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[11px] font-medium text-violet-800 hover:bg-violet-50"
                    @click="$emit('ocr', a.id)"
                  >
                    <SparklesIcon class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ t('request_detail.ocr_run') }}
                  </button>
                </div>
              </div>
              <div class="flex shrink-0 flex-wrap gap-1 sm:flex-col sm:items-end">
                <button
                  v-if="isAttachmentPreviewable(a)"
                  type="button"
                  class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100"
                  data-testid="attachment-preview-btn"
                  @click="$emit('preview', a)"
                >
                  <EyeIcon class="h-4 w-4" aria-hidden="true" />
                  {{ t('request_detail.preview_action') }}
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-teal-800 hover:bg-teal-50"
                  @click="$emit('download', a)"
                >
                  <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />
                  {{ t('request_detail.download_action') }}
                </button>
                <button
                  v-if="canDeleteAttachment && a.kind === 'paper_scan'"
                  type="button"
                  class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 disabled:opacity-50"
                  :disabled="deletingId === a.id"
                  @click="$emit('delete', a)"
                >
                  <TrashIcon class="h-4 w-4" aria-hidden="true" />
                  {{ t('request_detail.delete_action') }}
                </button>
              </div>
            </div>

            <div v-if="a.ocr_text" class="border-t border-slate-100 bg-slate-50/50 px-3 py-2 sm:px-4">
              <button
                type="button"
                class="flex w-full items-center justify-between gap-2 text-left text-[11px] font-medium text-slate-600"
                @click="toggleOcrText(a.id)"
              >
                {{ t('request_detail.docs_ocr_show_text') }}
                <ChevronDownIcon
                  class="h-4 w-4 shrink-0 transition"
                  :class="ocrTextOpen[a.id] ? 'rotate-180' : ''"
                  aria-hidden="true"
                />
              </button>
              <div v-show="ocrTextOpen[a.id]" class="mt-2">
                <span
                  v-if="isOcrStub(a)"
                  class="mb-1 inline-block rounded bg-violet-50 px-1.5 py-px text-[9px] font-semibold text-violet-800"
                >
                  {{ t('request_detail.ocr_stub_badge') }}
                </span>
                <pre
                  class="max-h-36 overflow-auto whitespace-pre-wrap rounded-lg bg-white p-2 font-mono text-[10px] leading-snug text-slate-800 ring-1 ring-slate-200/80"
                >{{ a.ocr_text }}</pre>
              </div>
            </div>
          </li>
        </ul>

        <div
          v-if="heroHasFiles && canHeroUpload && heroUploadFn"
          class="mt-4 border-t border-slate-100 pt-4"
        >
          <FileUpload
            :key="`hero-add-${requestId}-${heroFiles.length}`"
            :label="t('request_detail.attach_paper_scan_label')"
            drag-drop
            compact
            :upload-fn="heroUploadFn"
            @uploaded="onHeroUploaded"
          />
        </div>

        <div v-if="ocrErr" class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-800">{{ ocrErr }}</div>

        <dl
          v-if="req?.paper_status === 'received' && (req?.paper_reference || req?.paper_received_at)"
          class="mt-4 flex flex-wrap gap-x-4 gap-y-1 rounded-lg bg-slate-50 px-3 py-2 text-xs tabular-nums text-slate-700"
        >
          <div v-if="req.paper_reference">
            <dt class="inline font-medium text-slate-500">{{ t('request_detail.paper_ref_label_short') }}</dt>
            <dd class="inline">{{ req.paper_reference }}</dd>
          </div>
          <div v-if="req.paper_received_at">
            <dt class="inline font-medium text-slate-500">{{ t('request_detail.paper_received_at_short') }}</dt>
            <dd class="inline">{{ formatDateTime(req.paper_received_at) }}</dd>
          </div>
        </dl>
      </div>
    </section>

    <!-- Tài liệu đính kèm (phụ) -->
    <details
      id="request-docs-general"
      class="group rounded-xl bg-white ring-1 ring-slate-200/70 open:ring-slate-300/80"
      :open="generalAttachments.length > 0"
    >
      <summary
        class="flex cursor-pointer list-none items-center gap-2 px-4 py-3 text-sm font-medium text-slate-800 [&::-webkit-details-marker]:hidden"
      >
        <PaperClipIcon class="h-4 w-4 shrink-0 text-slate-500" aria-hidden="true" />
        <span class="min-w-0 flex-1">{{ t('request_detail.docs_general_section') }}</span>
        <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold tabular-nums text-slate-600">
          {{ generalAttachments.length }}
        </span>
        <ChevronDownIcon
          class="h-4 w-4 shrink-0 text-slate-400 transition group-open:rotate-180"
          aria-hidden="true"
        />
      </summary>
      <div class="border-t border-slate-100 px-4 pb-4 pt-2">
        <ul v-if="generalAttachments.length" class="divide-y divide-slate-100">
          <li
            v-for="a in generalAttachments"
            :key="a.id"
            class="flex min-w-0 items-center gap-2 py-2"
            :class="highlightAttachmentId === a.id ? 'rounded-lg bg-teal-50/90 px-2 -mx-2' : ''"
          >
            <DocumentIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            <div class="min-w-0 flex-1">
              <p class="truncate text-xs font-medium text-slate-900" :title="fileTitle(a)">{{ fileTitle(a) }}</p>
              <p class="truncate text-[10px] text-slate-500">
                {{ fmtFileSize(a.size_bytes) }}
                <span v-if="a.kind" class="text-slate-400"> · {{ attachmentKindLabel(a.kind) }}</span>
              </p>
            </div>
            <DocsFileActions
              :attachment="a"
              :can-preview="isAttachmentPreviewable(a)"
              :can-delete="canDeleteAttachment"
              :deleting="deletingId === a.id"
              @preview="$emit('preview', a)"
              @download="$emit('download', a)"
              @delete="$emit('delete', a)"
            />
          </li>
        </ul>
        <p v-else class="py-4 text-center text-xs text-slate-500">{{ t('request_detail.docs_empty_attachments') }}</p>
        <div v-if="attachErr" class="mt-2 rounded-md bg-rose-50 px-2 py-1 text-[11px] text-rose-800">{{ attachErr }}</div>
        <div v-if="canUploadGeneral && uploadGeneralFn" class="mt-3">
          <FileUpload
            :key="`doc-${requestId}-${generalAttachments.length}`"
            :label="t('request_detail.add_attachment_label')"
            drag-drop
            compact
            :upload-fn="uploadGeneralFn"
            @uploaded="(a) => $emit('uploaded-general', a)"
          />
        </div>
      </div>
    </details>

    <!-- Timeline -->
    <section v-if="timelineEvents.length || ocrBusy" class="px-1">
      <h3 class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
        {{ t('request_detail.docs_timeline_heading') }}
      </h3>
      <ol class="relative mt-3 space-y-0 pl-4">
        <li
          v-for="(ev, idx) in timelineEvents"
          :key="`${ev.key}-${idx}`"
          class="relative pb-4 last:pb-0"
        >
          <span
            class="absolute -left-4 top-1.5 h-2 w-2 rounded-full ring-2 ring-white"
            :class="ev.dotClass"
            aria-hidden="true"
          />
          <span
            v-if="idx < timelineEvents.length - 1"
            class="absolute -left-[13px] top-3 h-[calc(100%-4px)] w-px bg-slate-200"
            aria-hidden="true"
          />
          <p class="text-xs font-normal text-slate-800">{{ ev.label }}</p>
          <p v-if="ev.sub" class="text-xs text-slate-500">{{ ev.sub }}</p>
          <p v-if="ev.time" class="mt-0.5 text-xs tabular-nums text-slate-500">
            {{ formatDateTime(ev.time) }}
          </p>
        </li>
      </ol>
    </section>

    <div v-if="$slots['paper-forms']" class="rounded-xl bg-slate-50/60 p-3 ring-1 ring-slate-200/60">
      <slot name="paper-forms" />
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowDownTrayIcon,
  ChevronDownIcon,
  CloudArrowUpIcon,
  DocumentIcon,
  DocumentTextIcon,
  EyeIcon,
  PaperClipIcon,
  SparklesIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import { CheckIcon } from '@heroicons/vue/24/solid'
import FileUpload from '../ui/FileUpload.vue'
import DocsFileActions from './DocsFileActions.vue'
import RequestSignedDocumentStatus from './RequestSignedDocumentStatus.vue'
import { useDispatchRequestDocs, isAttachmentPreviewable } from '../../composables/useDispatchRequestDocs'

const props = defineProps({
  req: { type: Object, default: null },
  requestId: { type: [Number, String], required: true },
  docsProgressSteps: { type: Array, default: () => [] },
  docsChecklist: { type: Array, default: () => [] },
  highlightAttachmentId: { type: [Number, null], default: null },
  generalAttachments: { type: Array, default: () => [] },
  signedPaperAttachments: { type: Array, default: () => [] },
  signedDocumentCurrent: { type: Object, default: null },
  canManageSignedDocument: { type: Boolean, default: false },
  signedOcrBusy: { type: Boolean, default: false },
  signedVerifyBusy: { type: Boolean, default: false },
  paperScans: { type: Array, default: () => [] },
  attachErr: { type: String, default: '' },
  ocrErr: { type: String, default: '' },
  signedUploadErr: { type: String, default: '' },
  ocrBusy: { type: [Number, null], default: null },
  deletingId: { type: [Number, null], default: null },
  canUploadGeneral: { type: Boolean, default: false },
  canUploadSigned: { type: Boolean, default: false },
  canUploadPaperScan: { type: Boolean, default: false },
  canDeleteAttachment: { type: Boolean, default: false },
  canRunOcr: { type: Boolean, default: false },
  uploadGeneralFn: { type: Function, default: null },
  uploadSignedFn: { type: Function, default: null },
  uploadPaperScanFn: { type: Function, default: null },
  signedUploadKey: { type: String, default: 'signed' },
  formatDateTime: { type: Function, required: true },
})

const emit = defineEmits([
  'preview',
  'download',
  'delete',
  'ocr',
  'uploaded-general',
  'uploaded-signed',
  'uploaded-paper-scan',
  'signed-rerun-ocr',
  'signed-verify',
])

const { t } = useI18n()

const heroHeadingId = 'request-docs-hero-heading'
const ocrTextOpen = reactive({})

const reqRef = computed(() => props.req)
const { fmtFileSize, attachmentKindLabel, isOcrStub } = useDispatchRequestDocs(reqRef)

const heroFiles = computed(() => {
  const signed = props.signedPaperAttachments ?? []
  const scans = props.paperScans ?? []
  if (signed.length) return [...signed, ...scans.filter((s) => !signed.some((x) => x.id === s.id))]
  return scans
})

const heroHasFiles = computed(() => heroFiles.value.length > 0)

const heroUploadFn = computed(() => {
  if (props.canUploadSigned && props.uploadSignedFn) return props.uploadSignedFn
  if (props.canUploadPaperScan && props.uploadPaperScanFn) return props.uploadPaperScanFn
  return null
})

const canHeroUpload = computed(
  () => (props.canUploadSigned && props.uploadSignedFn) || (props.canUploadPaperScan && props.uploadPaperScanFn),
)

const heroComplete = computed(() => {
  const signedStep = props.docsProgressSteps.find((s) => s.key === 'signed')
  const paperStep = props.docsProgressSteps.find((s) => s.key === 'paper_ocr')
  const signedDone = signedStep?.state === 'done' || heroHasFiles.value
  const paperDone = paperStep?.state === 'done' || props.req?.paper_status === 'received'
  return signedDone && paperDone
})

const flowSteps = computed(() => {
  const signedStep = props.docsProgressSteps.find((s) => s.key === 'signed') ?? { state: 'upcoming' }
  const paperStep = props.docsProgressSteps.find((s) => s.key === 'paper_ocr') ?? { state: 'upcoming' }

  const uploadState = signedStep.state
  let ocrState = paperStep.state
  if (uploadState !== 'done' && uploadState !== 'current') {
    ocrState = 'upcoming'
  } else if (paperStep.state === 'current') {
    ocrState = 'current'
  }

  let completeState = 'upcoming'
  if (heroComplete.value) completeState = 'done'
  else if (paperStep.state === 'done' || props.req?.paper_status === 'received') completeState = 'current'
  else if (uploadState === 'done' && ocrState === 'done') completeState = 'done'

  return [
    { key: 'upload', label: t('request_detail.docs_step_1_label'), state: uploadState },
    { key: 'ocr', label: t('request_detail.docs_step_2_label'), state: ocrState },
    { key: 'complete', label: t('request_detail.docs_step_3_label'), state: completeState },
  ]
})

const timelineEvents = computed(() => {
  const events = []

  props.generalAttachments.forEach((a) => {
    events.push({
      key: `gen-${a.id}`,
      label: a.original_name || fileTitle(a),
      sub: t('request_detail.attachment_kind_request_attachment'),
      time: a.created_at,
      dotClass: 'bg-slate-400',
    })
  })

  props.signedPaperAttachments.forEach((a) => {
    events.push({
      key: `signed-${a.id}`,
      label: t('request_detail.docs_timeline_signed_upload'),
      sub: a.original_name || fileTitle(a),
      time: a.created_at,
      dotClass: 'bg-teal-500',
    })
  })

  props.paperScans.forEach((a) => {
    events.push({
      key: `scan-${a.id}`,
      label: t('request_detail.docs_timeline_scan_upload'),
      sub: a.original_name || fileTitle(a),
      time: a.created_at,
      dotClass: 'bg-teal-500',
    })
    if (a.ocr_processed_at) {
      events.push({
        key: `ocr-${a.id}`,
        label: t('request_detail.docs_ocr_done_badge'),
        sub: null,
        time: a.ocr_processed_at,
        dotClass: 'bg-violet-500',
      })
    }
  })

  if (props.req?.paper_status === 'received' && props.req?.paper_received_at) {
    events.push({
      key: 'paper-received',
      label: t('request_detail.paper_received_badge'),
      sub: props.req.paper_reference || null,
      time: props.req.paper_received_at,
      dotClass: 'bg-teal-600',
    })
  }

  const list = events.filter((e) => e.time).sort((a, b) => new Date(a.time) - new Date(b.time))

  if (props.ocrBusy) {
    list.push({
      key: 'ocr-busy',
      label: t('request_detail.docs_ocr_running'),
      sub: null,
      time: null,
      dotClass: 'bg-violet-400 animate-pulse',
    })
  }

  return list
})

function stepNodeClass(state) {
  if (state === 'done') return 'bg-teal-600 text-white shadow-sm shadow-teal-600/20'
  if (state === 'current') return 'bg-amber-50 text-amber-900 ring-2 ring-amber-400/70'
  return 'bg-white text-slate-400 ring-1 ring-slate-200'
}

function connectorClassBefore(index) {
  const prev = flowSteps.value[index - 1]
  return prev?.state === 'done' ? 'bg-teal-400' : 'bg-slate-200'
}

function connectorClassAfter(index) {
  const cur = flowSteps.value[index]
  return cur?.state === 'done' ? 'bg-teal-400' : 'bg-slate-200'
}

function fileTitle(a) {
  return a.original_name || t('request_detail.file_fallback_name', { id: a.id })
}

function uploaderLabel(a) {
  return a.uploader?.name || a.uploader_name || a.uploaded_by_name || '—'
}

function isImageMime(mime) {
  return String(mime || '').toLowerCase().startsWith('image/')
}

function canRunOcrOn(a) {
  return props.canRunOcr && a.kind === 'paper_scan' && !a.ocr_processed_at
}

function toggleOcrText(id) {
  ocrTextOpen[id] = !ocrTextOpen[id]
}

function onHeroUploaded(payload) {
  if (props.canUploadSigned && props.uploadSignedFn) {
    emit('uploaded-signed', payload)
  } else {
    emit('uploaded-paper-scan', payload)
  }
}
</script>
