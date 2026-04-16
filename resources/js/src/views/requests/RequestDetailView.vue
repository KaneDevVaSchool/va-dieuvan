<template>
  <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
  <div v-else-if="req" class="space-y-4">
    <Card :title="`Yêu cầu #${req.id}`">
      <div class="mb-3">
        <RouterLink class="text-sm text-slate-600 underline hover:text-slate-900" to="/requests">← Danh sách yêu cầu</RouterLink>
      </div>
      <div class="grid gap-2 text-sm md:grid-cols-2">
        <div><span class="text-slate-500">Trạng thái:</span> {{ labelRequestStatus(req.status) }}</div>
        <div><span class="text-slate-500">Loại:</span> {{ labelTripType(req.trip_type) }}</div>
        <div><span class="text-slate-500">Kênh:</span> {{ labelSourceChannel(req.source_channel) }}</div>
        <div><span class="text-slate-500">Gấp:</span> {{ req.is_urgent ? 'Có' : 'Không' }}</div>
        <div class="md:col-span-2"><span class="text-slate-500">Tuyến:</span> {{ req.origin }} → {{ req.destination }}</div>
        <div><span class="text-slate-500">Xuất phát:</span> {{ fmt(req.depart_at) }}</div>
        <div>
          <span class="text-slate-500">Phiếu giấy:</span>
          <span
            class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
            :class="paperBadgeClass(req.paper_status)"
          >
            {{ labelPaperStatus(req.paper_status) }}
          </span>
          <span v-if="req.paper_reference" class="ml-2 text-slate-700">{{ req.paper_reference }}</span>
        </div>
        <div v-if="req.paper_received_at">
          <span class="text-slate-500">Nhận phiếu lúc:</span> {{ fmt(req.paper_received_at) }}
        </div>
      </div>
      <div v-if="req.trip" class="mt-4">
        <RouterLink class="text-sm font-medium text-slate-900 underline" :to="`/trips/${req.trip.id}`">
          Mở chuyến #{{ req.trip.id }}
        </RouterLink>
      </div>
    </Card>

    <Card v-if="bm02PdfAttachment || bm02ExcelAttachment" title="BM.02 đính kèm (Điểm — Điểm)">
      <p class="mb-3 text-xs text-slate-500">
        File đã lưu trên máy chủ — xem trước PDF hoặc tải Excel/PDF qua API (có xác thực).
      </p>
      <div class="flex flex-wrap gap-2">
        <Button
          v-if="bm02PdfAttachment"
          variant="secondary"
          type="button"
          class="text-xs"
          :loading="bm02DownloadBusy === `pdf-${bm02PdfAttachment.id}`"
          @click="downloadBm02File(bm02PdfAttachment, 'pdf')"
        >
          Tải PDF
        </Button>
        <Button
          v-if="bm02ExcelAttachment"
          variant="secondary"
          type="button"
          class="text-xs"
          :loading="bm02DownloadBusy === `xlsx-${bm02ExcelAttachment.id}`"
          @click="downloadBm02File(bm02ExcelAttachment, 'xlsx')"
        >
          Tải Excel
        </Button>
      </div>
      <div v-if="bm02PdfPreviewUrl" class="mt-4 space-y-1.5">
        <div class="text-xs font-medium text-slate-700">Xem trước PDF</div>
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-slate-100 shadow-sm">
          <iframe
            :src="bm02PdfPreviewUrl"
            class="block h-[min(65vh,520px)] w-full min-h-[240px] border-0 bg-white"
            title="BM.02 PDF"
          />
        </div>
        <a
          :href="bm02PdfPreviewUrl"
          target="_blank"
          rel="noopener noreferrer"
          class="text-xs font-medium text-teal-700 hover:text-teal-800"
        >
          Mở tab mới
        </a>
      </div>
      <p v-if="bm02DownloadErr" class="mt-2 text-xs text-rose-600">{{ bm02DownloadErr }}</p>
      <p v-if="bm02PdfAttachment && !bm02PdfPreviewUrl && bm02PreviewErr" class="mt-2 text-xs text-rose-600">
        {{ bm02PreviewErr }}
      </p>
    </Card>

    <Card v-if="req.status === 'pending'" title="Thao tác">
      <div class="flex flex-col gap-3 md:flex-row">
        <Button :loading="acting" @click="decide('approve')">Duyệt</Button>
        <Button variant="danger" :loading="acting" @click="decide('reject')">Từ chối</Button>
      </div>
      <div v-if="msg" class="mt-2 text-sm text-slate-600">{{ msg }}</div>
    </Card>

    <Card v-if="paperScans.length" title="Scan phiếu đã tải">
      <p class="mb-3 text-xs text-slate-500">
        OCR (stub): chạy trên máy chủ, sẵn sàng thay engine thật. Chỉ áp dụng cho file loại
        <code class="rounded bg-slate-100 px-1">paper_scan</code>.
      </p>
      <ul class="space-y-4 text-sm">
        <li
          v-for="a in paperScans"
          :key="a.id"
          class="rounded-lg border border-slate-100 bg-slate-50/50 p-3"
        >
          <div class="flex flex-wrap items-center gap-2">
            <a v-if="a.url" :href="a.url" target="_blank" rel="noopener" class="font-medium text-slate-900 underline">
              {{ a.original_name || 'Mở file' }}
            </a>
            <span v-if="a.mime_type" class="text-xs text-slate-400">{{ a.mime_type }}</span>
            <Button
              variant="secondary"
              type="button"
              class="text-xs"
              :loading="ocrBusy === a.id"
              @click="runOcr(a.id)"
            >
              Chạy OCR (demo)
            </Button>
          </div>
          <p v-if="a.ocr_processed_at" class="mt-1 text-xs text-slate-500">
            OCR lúc: {{ fmt(a.ocr_processed_at) }}
          </p>
          <pre
            v-if="a.ocr_text"
            class="mt-2 max-h-40 overflow-auto whitespace-pre-wrap rounded border bg-white p-2 text-xs text-slate-800"
          >{{ a.ocr_text }}</pre>
        </li>
      </ul>
    </Card>

    <div v-if="ocrErr" class="rounded-md bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ ocrErr }}</div>

    <FileUpload
      :key="`paper-${route.params.id}`"
      label="Đính kèm phiếu giấy / scan"
      hint="Ảnh hoặc PDF (tối đa 10MB). Cần quyền attachment.upload. Sau khi có scan, đánh dấu đã nhận phiếu bên dưới."
      :upload-fn="uploadPaperScan"
      @uploaded="load"
    />

    <Card v-if="req.paper_status === 'pending'" title="Đánh dấu đã nhận phiếu giấy">
      <p class="text-xs text-slate-500">Cần quyền request.paper.manage. Cập nhật trạng thái sau khi đã có bản scan / phiếu thật.</p>
      <form class="mt-3 grid gap-3 md:grid-cols-2" @submit.prevent="doMarkPaper">
        <Input v-model="paperForm.paper_reference" label="Số tham chiếu / mã phiếu" placeholder="Tùy chọn" />
        <Input v-model="paperForm.paper_received_at" label="Thời điểm nhận" type="datetime-local" />
        <div class="md:col-span-2 flex items-center gap-2">
          <Button :loading="paperActing" type="submit">Đánh dấu đã nhận</Button>
          <span v-if="paperMsg" class="text-sm text-slate-600">{{ paperMsg }}</span>
        </div>
      </form>
    </Card>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import FileUpload from '../../components/ui/FileUpload.vue'
import { runAttachmentOcr, uploadAttachment } from '../../api/attachments'
import { downloadBinaryAttachmentFromApi, fetchPdfBlobForPreview } from '../../util/downloadPdfAttachment'
import { decideDispatchRequest, getDispatchRequest, markPaperReceived } from '../../api/requests'
import { newIdempotencyKey } from '../../util/idempotency'
import { labelPaperStatus, labelRequestStatus, labelSourceChannel, labelTripType } from '../../util/labels'

const route = useRoute()
const req = ref(null)
const loading = ref(true)
const acting = ref(false)
const msg = ref('')

const paperForm = ref({ paper_reference: '', paper_received_at: '' })
const paperActing = ref(false)
const paperMsg = ref('')
const ocrBusy = ref(null)
const ocrErr = ref('')

const paperScans = computed(() => {
  const list = req.value?.attachments ?? []
  return list.filter((a) => a.kind === 'paper_scan')
})

const bm02PdfAttachment = computed(() => {
  const list = req.value?.attachments ?? []
  return list.find((a) => a.kind === 'bm02_pdf') ?? null
})

const bm02ExcelAttachment = computed(() => {
  const list = req.value?.attachments ?? []
  return list.find((a) => a.kind === 'bm02_excel') ?? null
})

const bm02PdfPreviewUrl = ref(null)
const bm02PreviewErr = ref('')
const bm02DownloadBusy = ref(null)
const bm02DownloadErr = ref('')
let bm02PdfBlobUrlToRevoke = null

async function setupBm02PdfPreview() {
  bm02PreviewErr.value = ''
  if (bm02PdfBlobUrlToRevoke) {
    try {
      URL.revokeObjectURL(bm02PdfBlobUrlToRevoke)
    } catch {
      /* ignore */
    }
    bm02PdfBlobUrlToRevoke = null
  }
  bm02PdfPreviewUrl.value = null
  const pdf = bm02PdfAttachment.value
  if (!pdf?.id) return
  try {
    const r = await fetchPdfBlobForPreview(pdf.id)
    if (r.ok) {
      bm02PdfPreviewUrl.value = URL.createObjectURL(r.blob)
      bm02PdfBlobUrlToRevoke = bm02PdfPreviewUrl.value
    } else {
      bm02PreviewErr.value = 'Không hiển thị được PDF (định dạng không hợp lệ).'
    }
  } catch {
    bm02PreviewErr.value = 'Không tải được PDF để xem trước.'
  }
}

async function downloadBm02File(att, kind) {
  if (!att?.id) return
  bm02DownloadErr.value = ''
  bm02DownloadBusy.value = `${kind}-${att.id}`
  try {
    await downloadBinaryAttachmentFromApi(att.id, att.original_name || (kind === 'pdf' ? 'BM02.pdf' : 'BM02.xlsx'))
  } catch {
    bm02DownloadErr.value = 'Không tải được tệp đính kèm.'
  } finally {
    bm02DownloadBusy.value = null
  }
}

function paperBadgeClass(status) {
  if (status === 'received' || status === 'digitally_signed') return 'bg-emerald-50 text-emerald-800'
  if (status === 'pending') return 'bg-amber-50 text-amber-800'
  return 'bg-slate-100 text-slate-700'
}

function fmt(v) {
  return v ? new Date(v).toLocaleString('vi-VN') : '-'
}

async function load() {
  loading.value = true
  bm02DownloadErr.value = ''
  try {
    req.value = await getDispatchRequest(route.params.id)
    if (req.value?.paper_status === 'pending') {
      paperForm.value.paper_reference = req.value.paper_reference ?? ''
    }
    await setupBm02PdfPreview()
  } finally {
    loading.value = false
  }
}

async function runOcr(attachmentId) {
  ocrErr.value = ''
  ocrBusy.value = attachmentId
  try {
    await runAttachmentOcr(attachmentId)
    await load()
  } catch (e) {
    ocrErr.value = e?.response?.data?.message ?? 'OCR thất bại.'
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

async function doMarkPaper() {
  paperMsg.value = ''
  paperActing.value = true
  try {
    const payload = {}
    if (paperForm.value.paper_reference?.trim()) {
      payload.paper_reference = paperForm.value.paper_reference.trim()
    }
    if (paperForm.value.paper_received_at) {
      payload.paper_received_at = new Date(paperForm.value.paper_received_at).toISOString()
    }
    await markPaperReceived(route.params.id, payload)
    paperMsg.value = 'Đã đánh dấu đã nhận phiếu'
    await load()
  } catch (e) {
    paperMsg.value = e?.response?.data?.message ?? 'Lỗi'
  } finally {
    paperActing.value = false
  }
}

async function decide(d) {
  msg.value = ''
  acting.value = true
  try {
    await decideDispatchRequest(
      route.params.id,
      { decision: d, reason: d === 'reject' ? 'reject' : null },
      { idempotencyKey: newIdempotencyKey() },
    )
    msg.value = 'Đã xử lý'
    await load()
  } catch (e) {
    msg.value = e?.response?.data?.message ?? 'Lỗi'
  } finally {
    acting.value = false
  }
}

onMounted(load)
watch(() => route.params.id, load)

onBeforeUnmount(() => {
  if (bm02PdfBlobUrlToRevoke) {
    try {
      URL.revokeObjectURL(bm02PdfBlobUrlToRevoke)
    } catch {
      /* ignore */
    }
    bm02PdfBlobUrlToRevoke = null
  }
})
</script>
