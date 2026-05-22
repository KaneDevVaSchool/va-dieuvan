<template>
  <div id="request-docs-panel" class="space-y-3">
    <ul
      v-if="docsChecklist.length"
      class="flex flex-wrap gap-2 rounded-lg border border-slate-200 bg-white px-2 py-2"
      :aria-label="t('request_detail.docs_checklist_aria')"
    >
      <li v-for="item in docsChecklist" :key="item.key">
        <button
          type="button"
          class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold transition"
          :class="
            item.done
              ? 'bg-teal-50 text-teal-900 ring-1 ring-teal-200/80'
              : item.disabled
                ? 'cursor-not-allowed bg-slate-50 text-slate-400 ring-1 ring-slate-100'
                : 'bg-amber-50 text-amber-950 ring-1 ring-amber-200/80 hover:bg-amber-100/80'
          "
          :disabled="item.disabled"
          @click="scrollToSection(item.scrollTarget)"
        >
          <CheckIcon v-if="item.done" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
          <span v-else class="h-3.5 w-3.5 shrink-0 rounded-full border border-current opacity-60" aria-hidden="true" />
          {{ item.label }}
        </button>
      </li>
    </ul>

    <nav
      class="flex gap-1 rounded-lg border border-slate-200 bg-slate-50/80 p-1"
      :aria-label="t('request_detail.docs_progress_aria')"
    >
      <div
        v-for="step in docsProgressSteps"
        :key="step.key"
        class="flex min-w-0 flex-1 flex-col items-center gap-0.5 px-1 py-1 text-center"
      >
        <span
          class="flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-bold"
          :class="
            step.state === 'done'
              ? 'bg-teal-600 text-white'
              : step.state === 'current'
                ? 'bg-amber-100 text-amber-950 ring-2 ring-amber-400/60'
                : 'bg-white text-slate-400 ring-1 ring-slate-200'
          "
        >
          <CheckIcon v-if="step.state === 'done'" class="h-3.5 w-3.5" />
          <span v-else>{{ step.state === 'current' ? '!' : '·' }}</span>
        </span>
        <span class="w-full truncate text-[9px] font-semibold leading-tight text-slate-700 sm:text-[10px]">
          {{ step.label }}
        </span>
      </div>
    </nav>

    <div
      v-if="showDocsUploadRow"
      id="request-docs-upload-row"
      class="grid gap-2 sm:grid-cols-2"
    >
      <FileUpload
        v-if="canUploadPaperScan && uploadPaperScanFn"
        :key="`paper-${requestId}-${paperScans.length}`"
        :label="t('request_detail.attach_paper_scan_label')"
        drag-drop
        compact
        :upload-fn="uploadPaperScanFn"
        @uploaded="(a) => $emit('uploaded-paper-scan', a)"
      />
      <FileUpload
        v-if="canUploadGeneral && uploadGeneralFn"
        :key="`doc-${requestId}-${generalAttachments.length}`"
        :label="t('request_detail.add_attachment_label')"
        drag-drop
        compact
        :upload-fn="uploadGeneralFn"
        @uploaded="$emit('uploaded-general')"
      />
    </div>

    <!-- Đính kèm -->
    <section id="request-docs-general" class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
      <header
        class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/80 px-3 py-2"
        :title="t('request_detail.docs_section_lead')"
      >
        <PaperClipIcon class="h-4 w-4 shrink-0 text-teal-700" aria-hidden="true" />
        <h2 class="min-w-0 flex-1 truncate text-xs font-bold uppercase tracking-wide text-slate-800">
          {{ t('request_detail.docs_section_heading') }}
        </h2>
        <span class="shrink-0 rounded bg-white px-1.5 py-px text-[10px] font-bold tabular-nums text-slate-600 ring-1 ring-slate-200">
          {{ generalAttachments.length }}
        </span>
      </header>
      <div class="px-3 py-2">
        <ul v-if="generalAttachments.length" class="divide-y divide-slate-100 rounded-md border border-slate-100">
          <li
            v-for="a in generalAttachments"
            :key="a.id"
            class="flex min-w-0 items-center gap-2 bg-white px-2 py-1.5 transition-colors"
            :class="highlightAttachmentId === a.id ? 'bg-teal-50/90 ring-1 ring-inset ring-teal-300/60' : ''"
          >
            <DocumentIcon class="h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            <div class="min-w-0 flex-1 overflow-hidden">
              <p class="truncate text-xs font-medium text-slate-900" :title="fileTitle(a)">{{ fileTitle(a) }}</p>
              <p class="truncate text-[10px] text-slate-500">
                {{ fmtFileSize(a.size_bytes) }}
                <span v-if="a.kind" class="text-slate-400"> · {{ attachmentKindLabel(a.kind) }}</span>
              </p>
            </div>
            <DocsFileActions
              :attachment="a"
              :can-preview="isPreviewableMime(a.mime_type)"
              :can-delete="canDeleteAttachment"
              :deleting="deletingId === a.id"
              @preview="$emit('preview', a)"
              @download="$emit('download', a)"
              @delete="$emit('delete', a)"
            />
          </li>
        </ul>
        <p v-else class="py-3 text-center text-[11px] text-slate-500">{{ t('request_detail.docs_empty_attachments') }}</p>
        <div v-if="attachErr" class="mt-2 rounded-md bg-rose-50 px-2 py-1 text-[11px] text-rose-800">{{ attachErr }}</div>
        <div
          v-if="canUploadGeneral && uploadGeneralFn && !showDocsUploadRow"
          class="mt-2 border-t border-slate-100 pt-2"
        >
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
    </section>

    <!-- Phiếu đã ký -->
    <section
      v-if="showSignedSection"
      id="request-docs-signed"
      class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"
    >
      <header
        class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/80 px-3 py-2"
        :title="t('request_detail.docs_signed_paper_lead')"
      >
        <ClipboardDocumentIcon class="h-4 w-4 shrink-0 text-slate-600" aria-hidden="true" />
        <h2 class="min-w-0 flex-1 truncate text-xs font-bold uppercase tracking-wide text-slate-800">
          {{ t('request_detail.docs_signed_paper_heading') }}
        </h2>
      </header>
      <div class="px-3 py-2">
        <ul v-if="signedPaperAttachments.length" class="divide-y divide-slate-100 rounded-md border border-teal-100/80">
          <li v-for="a in signedPaperAttachments" :key="a.id" class="flex min-w-0 items-center gap-2 bg-teal-50/25 px-2 py-1.5">
            <div class="min-w-0 flex-1 overflow-hidden">
              <p class="truncate text-xs font-medium text-slate-900">{{ fileTitle(a) }}</p>
              <p class="truncate text-[10px] text-slate-600">{{ fmtFileSize(a.size_bytes) }}</p>
            </div>
            <DocsFileActions
              :attachment="a"
              :can-preview="isPreviewableMime(a.mime_type)"
              :can-delete="false"
              :deleting="false"
              download-emphasis
              @preview="$emit('preview', a)"
              @download="$emit('download', a)"
            />
          </li>
        </ul>
        <p v-else class="rounded-md border border-dashed border-amber-200/70 bg-amber-50/30 py-2.5 text-center text-[11px] text-amber-950/90">
          {{ t('request_detail.docs_signed_paper_empty') }}
        </p>
        <div v-if="canUploadSigned && uploadSignedFn" class="mt-2 border-t border-teal-100/80 pt-2">
          <FileUpload
            :key="signedUploadKey"
            :label="t('request_detail.attach_signed_paper_label')"
            drag-drop
            compact
            :upload-fn="uploadSignedFn"
            @uploaded="$emit('uploaded-signed')"
          />
          <p v-if="signedUploadErr" class="mt-1 text-[11px] text-rose-700">{{ signedUploadErr }}</p>
        </div>
      </div>
    </section>

    <!-- Phiếu giấy & OCR -->
    <section
      v-if="showPaperSection"
      id="request-docs-paper"
      class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"
    >
      <header
        class="flex min-w-0 items-center gap-2 border-b border-slate-100 bg-slate-50/80 px-3 py-2"
        :title="t('request_detail.paper_ocr_lead')"
      >
        <DocumentTextIcon class="h-4 w-4 shrink-0 text-violet-700" aria-hidden="true" />
        <h2 class="min-w-0 flex-1 truncate text-xs font-bold uppercase tracking-wide text-slate-800">
          {{ t('request_detail.paper_ocr_heading') }}
        </h2>
        <span
          v-if="req?.paper_status === 'received'"
          class="shrink-0 rounded-full bg-teal-100 px-1.5 py-px text-[9px] font-bold uppercase text-teal-900"
        >
          {{ t('request_detail.paper_received_badge') }}
        </span>
        <span
          v-else-if="req?.paper_status === 'pending'"
          class="shrink-0 rounded-full bg-amber-100 px-1.5 py-px text-[9px] font-bold uppercase text-amber-950"
        >
          {{ t('request_detail.paper_pending_badge') }}
        </span>
      </header>
      <div class="space-y-2 px-3 py-2">
        <dl
          v-if="req?.paper_status === 'received' && (req?.paper_reference || req?.paper_received_at)"
          class="flex flex-wrap gap-x-4 gap-y-1 rounded-md bg-slate-50 px-2 py-1.5 text-[11px]"
        >
          <div v-if="req.paper_reference" class="min-w-0">
            <dt class="inline font-semibold text-slate-500">{{ t('request_detail.paper_ref_label_short') }}</dt>
            <dd class="inline text-slate-900">{{ req.paper_reference }}</dd>
          </div>
          <div v-if="req.paper_received_at" class="min-w-0">
            <dt class="inline font-semibold text-slate-500">{{ t('request_detail.paper_received_at_short') }}</dt>
            <dd class="inline text-slate-900">{{ formatDateTime(req.paper_received_at) }}</dd>
          </div>
        </dl>

        <ul v-if="paperScans.length" class="divide-y divide-slate-100 rounded-md border border-slate-100">
          <li v-for="a in paperScans" :key="a.id" class="bg-white">
            <div class="flex min-w-0 items-center gap-2 px-2 py-1.5">
              <DocumentTextIcon class="h-4 w-4 shrink-0 text-violet-600/80" aria-hidden="true" />
              <div class="min-w-0 flex-1 overflow-hidden">
                <p class="truncate text-xs font-medium text-slate-900" :title="fileTitle(a)">{{ fileTitle(a) }}</p>
                <p class="truncate text-[10px] text-slate-500">
                  {{ fmtFileSize(a.size_bytes) }}
                  <template v-if="a.mime_type"><span class="text-slate-400"> · </span>{{ a.mime_type }}</template>
                  <template v-if="a.ocr_processed_at">
                    <span class="text-slate-400"> · </span>
                    <span class="text-teal-700">{{ t('request_detail.ocr_result_prefix') }} {{ formatDateTime(a.ocr_processed_at) }}</span>
                  </template>
                </p>
              </div>
              <div class="flex shrink-0 flex-nowrap items-center gap-0.5" role="group" :aria-label="t('request_detail.docs_actions_aria')">
                <button
                  v-if="isPreviewableMime(a.mime_type)"
                  type="button"
                  class="flex h-7 w-7 items-center justify-center rounded-md text-slate-600 hover:bg-slate-100"
                  :title="t('request_detail.preview_action')"
                  @click="$emit('preview', a)"
                >
                  <EyeIcon class="h-3.5 w-3.5" aria-hidden="true" />
                </button>
                <button
                  type="button"
                  class="flex h-7 w-7 items-center justify-center rounded-md text-teal-700 hover:bg-teal-50"
                  :title="t('request_detail.download_action')"
                  @click="$emit('download', a)"
                >
                  <ArrowDownTrayIcon class="h-3.5 w-3.5" aria-hidden="true" />
                </button>
                <button
                  v-if="canRunOcr"
                  type="button"
                  class="relative flex h-7 w-7 items-center justify-center rounded-md text-violet-800 hover:bg-violet-50 disabled:opacity-50"
                  :disabled="ocrBusy !== null && ocrBusy !== a.id"
                  :title="a.ocr_processed_at ? t('request_detail.ocr_rerun') : t('request_detail.ocr_run')"
                  @click="$emit('ocr', a.id)"
                >
                  <span
                    v-if="ocrBusy === a.id"
                    class="absolute inset-0 m-auto h-3.5 w-3.5 animate-spin rounded-full border-2 border-violet-300 border-t-violet-700"
                    aria-hidden="true"
                  />
                  <SparklesIcon v-else class="h-3.5 w-3.5" aria-hidden="true" />
                </button>
                <button
                  v-if="canDeleteAttachment"
                  type="button"
                  class="flex h-7 w-7 items-center justify-center rounded-md text-rose-600 hover:bg-rose-50 disabled:opacity-50"
                  :disabled="deletingId === a.id"
                  :title="t('request_detail.delete_scan_action')"
                  @click="$emit('delete', a)"
                >
                  <TrashIcon class="h-3.5 w-3.5" aria-hidden="true" />
                </button>
              </div>
            </div>
            <div v-if="a.ocr_text" class="border-t border-slate-50 px-2 pb-2 pt-0">
              <div class="flex items-center justify-between gap-2">
                <p class="text-[9px] font-bold uppercase tracking-wide text-slate-400">
                  {{ t('request_detail.ocr_text_label') }}
                </p>
                <span
                  v-if="isOcrStub(a)"
                  class="rounded bg-violet-50 px-1.5 py-px text-[9px] font-semibold text-violet-800"
                >
                  {{ t('request_detail.ocr_stub_badge') }}
                </span>
              </div>
              <pre
                class="mt-1 max-h-32 overflow-auto whitespace-pre-wrap rounded border border-slate-100 bg-slate-50/90 p-2 font-mono text-[10px] leading-snug text-slate-800"
              >{{ a.ocr_text }}</pre>
            </div>
          </li>
        </ul>
        <p v-else-if="canUploadPaperScan" class="py-2 text-center text-[11px] text-slate-500">
          {{ t('request_detail.docs_empty_paper_scan') }}
        </p>

        <div v-if="ocrErr" class="rounded-md bg-rose-50 px-2 py-1 text-[11px] text-rose-800">{{ ocrErr }}</div>

        <div
          v-if="canUploadPaperScan && uploadPaperScanFn && !showDocsUploadRow"
          class="rounded-md border border-dashed border-violet-200/50 p-2"
        >
          <FileUpload
            :key="`paper-${requestId}-${paperScans.length}`"
            :label="t('request_detail.attach_paper_scan_label')"
            drag-drop
            compact
            :upload-fn="uploadPaperScanFn"
            @uploaded="(a) => $emit('uploaded-paper-scan', a)"
          />
        </div>

        <slot name="paper-forms" />
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ArrowDownTrayIcon,
  ClipboardDocumentIcon,
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
import { useDispatchRequestDocs } from '../../composables/useDispatchRequestDocs'

const props = defineProps({
  req: { type: Object, default: null },
  requestId: { type: [Number, String], required: true },
  docsProgressSteps: { type: Array, default: () => [] },
  docsChecklist: { type: Array, default: () => [] },
  highlightAttachmentId: { type: [Number, null], default: null },
  generalAttachments: { type: Array, default: () => [] },
  signedPaperAttachments: { type: Array, default: () => [] },
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

defineEmits([
  'preview',
  'download',
  'delete',
  'ocr',
  'uploaded-general',
  'uploaded-signed',
  'uploaded-paper-scan',
])

const { t } = useI18n()

const reqRef = computed(() => props.req)
const { fmtFileSize, attachmentKindLabel, isOcrStub, isPreviewableMime } = useDispatchRequestDocs(reqRef)

const showSignedSection = computed(
  () => props.signedPaperAttachments.length > 0 || props.req?.status === 'approved' || props.canUploadSigned,
)

const showPaperSection = computed(
  () =>
    props.paperScans.length > 0 ||
    props.canUploadPaperScan ||
    props.req?.paper_status === 'pending' ||
    props.req?.paper_status === 'received',
)

/** Hai ô tải (phiếu/scan + tài liệu) xếp ngang khi cùng hiển thị. */
const showDocsUploadRow = computed(() => {
  const paper = props.canUploadPaperScan && props.uploadPaperScanFn
  const general = props.canUploadGeneral && props.uploadGeneralFn
  return paper && general
})

function fileTitle(a) {
  return a.original_name || t('request_detail.file_fallback_name', { id: a.id })
}

function scrollToSection(id) {
  if (!id) return
  const el = document.getElementById(id)
  el?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}
</script>
