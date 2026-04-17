<template>
  <div class="min-h-screen bg-[#F8F9FA] print:bg-white">
    <div v-if="loading" class="px-4 py-12 text-center text-sm text-slate-500">Đang tải…</div>

    <template v-else-if="req">
      <div class="mx-auto max-w-6xl space-y-6 px-4 pb-10 print:pt-0">
        <div
          class="flex flex-col gap-4 border-b border-slate-200/80 pb-6 sm:flex-row sm:items-center sm:justify-between print:border-0 print:pb-3"
        >
          <div class="flex min-w-0 flex-1 items-start gap-3">
            <RouterLink
              to="/requests"
              class="no-print mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
              aria-label="Quay lại danh sách"
            >
              <ArrowLeftIcon class="h-5 w-5" />
            </RouterLink>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">
                  Chi tiết yêu cầu {{ requestRefCode }}
                </h1>
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                  :class="statusBadgeClass(req.status)"
                >
                  {{ labelRequestStatus(req.status) }}
                </span>
              </div>
              <p v-if="req.trip" class="mt-1 text-sm text-teal-700">
                <RouterLink
                  :to="`/trips/${req.trip.id}`"
                  class="font-medium underline decoration-teal-600/30 underline-offset-2 hover:decoration-teal-700"
                >
                  Mở chuyến #{{ req.trip.id }}
                </RouterLink>
              </p>
            </div>
          </div>
          <div class="no-print flex flex-wrap items-center gap-2 sm:justify-end">
            <RouterLink
              to="/notifications"
              class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50"
              title="Thông báo"
            >
              <BellIcon class="h-5 w-5" />
              <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white" aria-hidden="true" />
            </RouterLink>
            <button
              type="button"
              class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50"
              @click="doPrint"
            >
              <PrinterIcon class="h-5 w-5 text-slate-500" />
              In
            </button>
            <button
              type="button"
              class="inline-flex h-10 cursor-not-allowed items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-400 shadow-sm"
              disabled
              title="Chỉnh sửa yêu cầu sẽ được bổ sung sau"
            >
              <PencilSquareIcon class="h-5 w-5" />
              Chỉnh sửa
            </button>
          </div>
        </div>

        <!-- Stepper -->
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm print:border print:shadow-none">
          <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">Tiến trình yêu cầu</h2>
          <div class="mt-6 overflow-x-auto pb-2">
            <div class="flex min-w-[640px] items-start">
              <template v-for="(step, idx) in stepperSteps" :key="step.key">
                <div class="flex min-w-0 flex-1 flex-col items-center text-center">
                  <div
                    class="flex h-10 w-10 items-center justify-center rounded-full border-2 text-sm font-semibold transition-colors"
                    :class="stepCircleClass(step.state)"
                  >
                    <CheckIcon v-if="step.state === 'done'" class="h-5 w-5" />
                    <HandThumbUpIcon
                      v-else-if="step.key === 'approved' && (step.state === 'upcoming' || step.state === 'current')"
                      class="h-5 w-5"
                      :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                    />
                    <TruckIcon
                      v-else-if="step.key === 'running' && step.state !== 'done'"
                      class="h-5 w-5"
                      :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                    />
                    <FlagIcon
                      v-else-if="step.key === 'done' && step.state !== 'done'"
                      class="h-5 w-5"
                      :class="step.state === 'current' ? 'text-teal-600' : 'text-slate-400'"
                    />
                    <span v-else-if="step.state === 'current'" class="h-2.5 w-2.5 rounded-full bg-teal-600" />
                    <span v-else-if="step.state === 'rejected'" class="text-xs font-bold">!</span>
                    <span v-else class="text-slate-300">·</span>
                  </div>
                  <p class="mt-2 text-xs font-semibold text-slate-800">{{ step.label }}</p>
                  <p v-if="step.sub" class="mt-0.5 text-[11px] text-slate-500">{{ step.sub }}</p>
                  <p
                    v-if="step.key === 'pending' && step.state === 'current' && req.status === 'pending'"
                    class="mt-0.5 text-[11px] font-medium text-teal-600"
                  >
                    Đang xử lý
                  </p>
                  <p
                    v-if="step.key === 'pending' && req.status === 'rejected'"
                    class="mt-0.5 text-[11px] font-medium text-rose-600"
                  >
                    Đã từ chối
                  </p>
                </div>
                <div
                  v-if="idx < stepperSteps.length - 1"
                  class="mx-1 mt-5 h-0.5 w-6 shrink-0 sm:w-10"
                  :class="step.state === 'done' ? 'bg-teal-500' : 'bg-slate-200'"
                  aria-hidden="true"
                />
              </template>
            </div>
          </div>
        </section>

        <!-- Pending actions -->
        <section
          v-if="req.status === 'pending' && canApprove"
          class="no-print rounded-2xl border border-amber-200/80 bg-amber-50/50 p-5 shadow-sm"
        >
          <h2 class="text-sm font-semibold text-slate-900">Thao tác duyệt</h2>
          <div class="mt-3 flex flex-col gap-3 sm:flex-row">
            <Button :loading="acting" class="bg-teal-600 hover:bg-teal-700" @click="decide('approve')">Duyệt</Button>
            <Button variant="danger" :loading="acting" @click="decide('reject')">Từ chối</Button>
          </div>
          <p v-if="msg" class="mt-2 text-sm text-slate-600">{{ msg }}</p>
        </section>

        <div class="grid gap-6 lg:grid-cols-3">
          <!-- Trip info -->
          <section class="space-y-6 lg:col-span-2">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm print:border print:shadow-none">
              <div class="flex flex-wrap items-start justify-between gap-2 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2 text-teal-600">
                  <InformationCircleIcon class="h-6 w-6 shrink-0" />
                  <h2 class="text-base font-semibold text-slate-900">Thông tin chuyển</h2>
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                  Loại: {{ labelTripType(req.trip_type) }}
                </span>
              </div>

              <div class="mt-5 grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="min-w-0">
                  <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Người yêu cầu</p>
                  <div class="mt-2 flex items-center gap-3">
                    <img
                      v-if="req.requester?.avatar_url"
                      :src="req.requester.avatar_url"
                      alt=""
                      class="h-11 w-11 rounded-full object-cover ring-2 ring-slate-100"
                    />
                    <div
                      v-else
                      class="flex h-11 w-11 items-center justify-center rounded-full bg-teal-100 text-sm font-bold text-teal-800 ring-2 ring-teal-50"
                    >
                      {{ requesterInitials }}
                    </div>
                    <div class="min-w-0">
                      <p class="font-semibold text-slate-900">{{ req.requester?.name ?? '—' }}</p>
                      <p class="text-sm text-slate-500">{{ requesterSubtitle }}</p>
                    </div>
                  </div>
                </div>

                <div class="min-w-0">
                  <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Thời gian yêu cầu</p>
                  <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-800">
                    <span class="inline-flex items-center gap-1.5">
                      <CalendarDaysIcon class="h-4 w-4 text-slate-400" />
                      {{ fmtDateVi(req.depart_at) }}
                    </span>
                    <span class="text-slate-300">|</span>
                    <span class="inline-flex items-center gap-1.5">
                      <ClockIcon class="h-4 w-4 text-slate-400" />
                      {{ fmtTimeWindow(req.depart_at, req.arrive_by) }}
                    </span>
                  </div>
                </div>

                <div class="min-w-0 md:col-span-2">
                  <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Lộ trình</p>
                  <div class="mt-3 flex gap-3">
                    <div class="flex flex-col items-center pt-1">
                      <span class="h-3 w-3 rounded-full border-2 border-teal-500 bg-white" />
                      <span class="mt-1 w-px flex-1 min-h-[2.5rem] bg-teal-300" />
                      <span class="h-3 w-3 rounded-full border-2 border-teal-500 bg-white" />
                    </div>
                    <div class="min-w-0 flex-1 space-y-4">
                      <div>
                        <p class="font-semibold text-slate-900">{{ req.origin || '—' }}</p>
                        <p class="text-sm text-slate-500">{{ routeSubFrom }}</p>
                      </div>
                      <div>
                        <p class="font-semibold text-slate-900">{{ req.destination || '—' }}</p>
                        <p class="text-sm text-slate-500">{{ routeSubTo }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="min-w-0" :class="{ 'md:col-span-2': !hasUserNotes }">
                  <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Số hành khách / Khối lượng</p>
                  <div class="mt-2 flex items-center gap-2 text-sm text-slate-800">
                    <CubeIcon class="h-5 w-5 shrink-0 text-teal-600" />
                    <span>{{ passengerOrCargoLine }}</span>
                  </div>
                </div>

                <div v-if="hasUserNotes" class="min-w-0">
                  <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Ghi chú</p>
                  <div
                    class="mt-2 max-h-[min(28rem,55vh)] overflow-y-auto whitespace-pre-wrap break-words rounded-lg bg-slate-50 px-3 py-3 text-sm leading-relaxed text-slate-700 [overflow-wrap:anywhere] print:max-h-none md:max-h-[min(36rem,65vh)]"
                  >
                    {{ userNotesFormatted }}
                  </div>
                </div>

                <div v-if="bm03Display" class="min-w-0 md:col-span-2">
                  <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Nội dung đơn điện tử (BM.03)</p>
                  <p class="mt-1 text-xs text-slate-500">Tự động từ biểu mẫu tạo yêu cầu; không dùng cột ghi chú.</p>
                  <div
                    class="mt-2 max-h-[min(32rem,70vh)] overflow-y-auto whitespace-pre-wrap break-words rounded-lg bg-slate-50 px-3 py-3 text-sm leading-relaxed text-slate-700 [overflow-wrap:anywhere] print:max-h-none"
                  >
                    {{ bm03Display }}
                  </div>
                </div>

                <!-- Attached documents -->
                <div class="min-w-0 md:col-span-2">
                  <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Tài liệu đính kèm</p>
                  <p class="mt-1 text-xs text-slate-500">Tải thêm chứng từ, ảnh hoặc file liên quan (tối đa 10MB). Kéo thả hoặc chọn tệp.</p>

                  <ul v-if="generalAttachments.length" class="mt-3 divide-y divide-slate-100 rounded-lg border border-slate-100 bg-slate-50/50">
                    <li
                      v-for="a in generalAttachments"
                      :key="a.id"
                      class="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5 text-sm"
                    >
                      <span class="min-w-0 font-medium text-slate-800">{{ a.original_name || `File #${a.id}` }}</span>
                      <div class="flex items-center gap-2">
                        <button
                          type="button"
                          class="text-xs font-medium text-teal-700 underline underline-offset-2 hover:text-teal-800"
                          @click="downloadFile(a)"
                        >
                          Tải xuống
                        </button>
                        <button
                          v-if="canDeleteAttachment"
                          type="button"
                          class="text-xs font-medium text-rose-600 underline underline-offset-2 hover:text-rose-700"
                          :disabled="deletingId === a.id"
                          @click="removeAttachment(a)"
                        >
                          {{ deletingId === a.id ? '…' : 'Xóa' }}
                        </button>
                      </div>
                    </li>
                  </ul>

                  <div v-if="attachErr" class="mt-2 text-sm text-rose-600">{{ attachErr }}</div>

                  <div class="no-print mt-3">
                    <FileUpload
                      v-if="canUploadAttachment"
                      :key="`doc-${route.params.id}-${generalAttachments.length}`"
                      label="Thêm tài liệu"
                      hint=""
                      drag-drop
                      :upload-fn="uploadRequestDocument"
                      @uploaded="onDocUploaded"
                    />
                    <p v-else class="text-xs text-slate-500">Bạn không có quyền tải file đính kèm.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Paper scan / OCR (administrative) -->
            <div
              v-if="paperScans.length || canUploadAttachment || req.paper_status === 'pending'"
              class="no-print rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm"
            >
              <h2 class="text-sm font-semibold text-slate-900">Phiếu giấy &amp; OCR</h2>
              <p v-if="paperScans.length" class="mt-1 text-xs text-slate-500">
                OCR (stub): chỉ áp dụng cho file loại <code class="rounded bg-slate-100 px-1">paper_scan</code>.
              </p>
              <ul v-if="paperScans.length" class="mt-3 space-y-3 text-sm">
                <li
                  v-for="a in paperScans"
                  :key="a.id"
                  class="rounded-lg border border-slate-100 bg-slate-50/50 p-3"
                >
                  <div class="flex flex-wrap items-center gap-2">
                    <button
                      type="button"
                      class="font-medium text-teal-700 underline"
                      @click="downloadFile(a)"
                    >
                      {{ a.original_name || 'Tải file' }}
                    </button>
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
                  <p v-if="a.ocr_processed_at" class="mt-1 text-xs text-slate-500">OCR lúc: {{ fmt(a.ocr_processed_at) }}</p>
                  <pre
                    v-if="a.ocr_text"
                    class="mt-2 max-h-40 overflow-auto whitespace-pre-wrap rounded border bg-white p-2 text-xs text-slate-800"
                  >{{ a.ocr_text }}</pre>
                </li>
              </ul>

              <div v-if="ocrErr" class="mt-2 text-sm text-rose-600">{{ ocrErr }}</div>

              <div v-if="canUploadAttachment" class="mt-4">
                <FileUpload
                  :key="`paper-${route.params.id}`"
                  label="Đính kèm phiếu giấy / scan"
                  hint="Ảnh hoặc PDF (tối đa 10MB). Loại tệp: paper_scan."
                  :upload-fn="uploadPaperScan"
                  @uploaded="load"
                />
              </div>

              <div v-if="req.paper_status === 'pending' && canManagePaper" class="mt-6 border-t border-slate-100 pt-4">
                <h3 class="text-sm font-medium text-slate-900">Đánh dấu đã nhận phiếu giấy</h3>
                <p class="mt-1 text-xs text-slate-500">Cần quyền request.paper.manage.</p>
                <form class="mt-3 grid gap-3 md:grid-cols-2" @submit.prevent="doMarkPaper">
                  <Input v-model="paperForm.paper_reference" label="Số tham chiếu / mã phiếu" placeholder="Tùy chọn" />
                  <Input v-model="paperForm.paper_received_at" label="Thời điểm nhận" type="datetime-local" />
                  <div class="md:col-span-2 flex items-center gap-2">
                    <Button :loading="paperActing" type="submit">Đánh dấu đã nhận</Button>
                    <span v-if="paperMsg" class="text-sm text-slate-600">{{ paperMsg }}</span>
                  </div>
                </form>
              </div>
            </div>
          </section>

          <!-- Cost estimate -->
          <section class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm print:border print:shadow-none">
              <div class="flex items-center gap-2 text-teal-600">
                <CalculatorIcon class="h-6 w-6 shrink-0" />
                <h2 class="text-base font-semibold text-slate-900">Ước tính chi phí</h2>
              </div>

              <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between gap-3 border-b border-slate-50 pb-3">
                  <dt class="text-slate-500">Quãng đường ước tính</dt>
                  <dd class="text-right font-medium text-slate-900">{{ costEstimate?.distanceLabel ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-slate-50 pb-3">
                  <dt class="text-slate-500">Loại xe đề xuất</dt>
                  <dd class="text-right font-medium text-slate-900">{{ costEstimate?.vehicleHint ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-slate-50 pb-3">
                  <dt class="text-slate-500">Đơn giá tham chiếu</dt>
                  <dd class="text-right font-medium text-slate-900">{{ costEstimate?.refUnitLabel ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-slate-50 pb-3">
                  <dt class="text-slate-500">Phí cầu đường (dự kiến)</dt>
                  <dd class="text-right font-medium text-slate-900">{{ costEstimate?.tollLabel ?? '—' }}</dd>
                </div>
              </dl>

              <div class="mt-5 rounded-xl bg-teal-50/80 px-4 py-4 ring-1 ring-teal-600/10">
                <p class="text-xs font-medium text-teal-800/90">Tổng chi phí ước tính</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-teal-600">
                  {{ costEstimate ? formatVndCurrency(costEstimate.total) : '—' }}
                </p>
                <p class="mt-2 text-[11px] leading-snug text-teal-700/90">
                  * Chi phí thực tế có thể thay đổi dựa trên lộ trình thực tế.
                </p>
              </div>
            </div>
          </section>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  ArrowLeftIcon,
  BellIcon,
  CalculatorIcon,
  CalendarDaysIcon,
  ClockIcon,
  CubeIcon,
  FlagIcon,
  HandThumbUpIcon,
  InformationCircleIcon,
  PencilSquareIcon,
  PrinterIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'
import { CheckIcon } from '@heroicons/vue/24/solid'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import FileUpload from '../../components/ui/FileUpload.vue'
import { deleteAttachment, runAttachmentOcr, uploadAttachment } from '../../api/attachments'
import { decideDispatchRequest, getDispatchRequest, markPaperReceived } from '../../api/requests'
import { newIdempotencyKey } from '../../util/idempotency'
import { labelRequestStatus, labelTripType } from '../../util/labels'
import { formatDispatchRequestNotesForDisplay, isLegacyBm03NotesBlock } from '../../util/formatDispatchNotes'
import { parseMoneyVnd } from '../../util/money'
import { downloadBinaryAttachmentFromApi } from '../../util/downloadPdfAttachment'
import { useAuthStore } from '../../store'

const route = useRoute()
const auth = useAuthStore()

const req = ref(null)
const loading = ref(true)
const acting = ref(false)
const msg = ref('')

const paperForm = ref({ paper_reference: '', paper_received_at: '' })
const paperActing = ref(false)
const paperMsg = ref('')
const ocrBusy = ref(null)
const ocrErr = ref('')

const attachErr = ref('')
const deletingId = ref(null)

const hasUserNotes = computed(() => {
  const n = req.value?.notes?.trim()
  if (!n) return false
  return !isLegacyBm03NotesBlock(n)
})

const userNotesFormatted = computed(() => {
  const n = req.value?.notes?.trim()
  if (!n || isLegacyBm03NotesBlock(n)) return ''
  return formatDispatchRequestNotesForDisplay(n)
})

const bm03Display = computed(() => {
  const r = req.value
  const fromSnap = r?.wizard_snapshot?.bm03_body?.trim()
  if (fromSnap) return formatDispatchRequestNotesForDisplay(fromSnap)
  const n = r?.notes?.trim()
  if (n && isLegacyBm03NotesBlock(n)) return formatDispatchRequestNotesForDisplay(n)
  return ''
})

const requestRefCode = computed(() => {
  const r = req.value
  if (!r?.id) return ''
  const d = r.created_at ? new Date(r.created_at) : new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  return `REQ-${y}${m}-${String(r.id).padStart(3, '0')}`
})

const canApprove = computed(
  () => auth.hasPermission('request.approve') || auth.hasPermission('trip.view_all'),
)

const canUploadAttachment = computed(() => auth.hasPermission('attachment.upload'))
const canDeleteAttachment = computed(() => auth.hasPermission('attachment.upload'))
const canManagePaper = computed(() => auth.hasPermission('request.paper.manage'))

const paperScans = computed(() => {
  const list = req.value?.attachments ?? []
  return list.filter((a) => a.kind === 'paper_scan')
})

const generalAttachments = computed(() => {
  const list = req.value?.attachments ?? []
  return list.filter((a) => a.kind !== 'paper_scan')
})

const requesterSubtitle = computed(() => {
  const u = req.value?.wizard_snapshot?.form?.requester_unit
  if (u?.trim()) return u.trim()
  const code = req.value?.requester?.employee_code
  if (code) return `Mã NV: ${code}`
  return req.value?.requester?.email ?? '—'
})

const requesterInitials = computed(() => {
  const name = req.value?.requester?.name?.trim() || ''
  if (!name) return '?'
  const parts = name.split(/\s+/).filter(Boolean)
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const routeSubFrom = computed(() => {
  const snap = req.value?.wizard_snapshot
  const row = snap?.cargoRows?.[0]
  if (row?.pickup_place?.trim()) return row.pickup_place.trim()
  if (row?.pickup_contact?.trim()) return row.pickup_contact.trim()
  return ''
})

const routeSubTo = computed(() => {
  const snap = req.value?.wizard_snapshot
  const row = snap?.cargoRows?.[0]
  if (row?.delivery_place?.trim()) return row.delivery_place.trim()
  if (row?.delivery_contact?.trim()) return row.delivery_contact.trim()
  return ''
})

const passengerOrCargoLine = computed(() => {
  const r = req.value
  if (!r) return '—'
  if (r.trip_type === 'cargo' && r.wizard_snapshot?.cargoRows?.length) {
    const weights = r.wizard_snapshot.cargoRows.map((x) => x.weight).filter((w) => String(w).trim())
    if (weights.length) {
      const joined = weights.join(', ')
      return `${joined}${/tấn|kg|ton/i.test(joined) ? '' : ' (khối lượng theo khai báo)'}`
    }
  }
  if (r.passenger_count != null && r.passenger_count > 0) {
    return `${r.passenger_count} hành khách`
  }
  return '—'
})

const costEstimate = computed(() => {
  const r = req.value
  const snap = r?.wizard_snapshot
  if (!snap?.form) return null

  const f = snap.form
  const cargoExtra = parseMoneyVnd
  let extras = 0
  if (f.need_porters) extras += cargoExtra(f.porter_cost)
  if (f.interprovincial) extras += cargoExtra(f.interprovincial_cost)
  if (f.e1_use_3plus_days) extras += cargoExtra(f.e1_extra_cost)
  if (f.e2_door_pickup) extras += cargoExtra(f.e2_door_cost)
  if (f.e2_driver_self) extras += cargoExtra(f.e2_driver_self_cost)
  if (f.e2_after_21h) extras += cargoExtra(f.e2_after_21h_cost)

  const rowTotal = (row) => cargoExtra(row.unit_price) + cargoExtra(row.extra_fee)
  const totalPass = (snap.passengerRows ?? []).reduce((s, row) => s + rowTotal(row), 0)
  const totalBus = (snap.businessRows ?? []).reduce((s, row) => s + rowTotal(row), 0)
  const cargoCosts = (snap.cargoRows ?? []).reduce((s, row) => s + cargoExtra(row.cost), 0)

  const total = extras + totalPass + totalBus + cargoCosts

  const passU = snap.passengerRows?.[0]?.unit_price
  const busU = snap.businessRows?.[0]?.unit_price
  const refRaw = passU || busU
  const refUnitLabel =
    refRaw != null && String(refRaw).trim() !== ''
      ? `${new Intl.NumberFormat('vi-VN').format(cargoExtra(refRaw))} VNĐ`
      : null

  const toll = f.interprovincial ? cargoExtra(f.interprovincial_cost) : 0
  const tollLabel = f.interprovincial && toll > 0 ? `${new Intl.NumberFormat('vi-VN').format(toll)} VNĐ` : null

  const wRaw = (snap.cargoRows ?? []).map((c) => c.weight).find((x) => String(x ?? '').trim())
  let vehicleHint = null
  if (wRaw) {
    const n = parseFloat(String(wRaw).replace(',', '.'))
    if (Number.isFinite(n)) vehicleHint = `Tải ~${n} tấn (tham khảo)`
    else vehicleHint = String(wRaw)
  }

  return {
    distanceLabel: null,
    vehicleHint,
    refUnitLabel,
    tollLabel,
    total,
  }
})

const stepperSteps = computed(() => {
  const r = req.value
  if (!r) return []
  const trip = r.trip
  const tripSt = trip?.status
  const st = r.status

  const steps = [
    { key: 'created', label: 'Tạo', sub: fmtShort(r.created_at), state: 'done' },
    { key: 'pending', label: 'Chờ duyệt', sub: '', state: 'upcoming' },
    { key: 'approved', label: 'Đã duyệt', sub: '', state: 'upcoming' },
    { key: 'running', label: 'Đang chạy', sub: '', state: 'upcoming' },
    { key: 'done', label: 'Hoàn tất', sub: '', state: 'upcoming' },
  ]

  let active = 1
  if (st === 'pending') active = 1
  else if (st === 'rejected') active = 1
  else if (st === 'approved') {
    active = 2
    if (trip && ['assigned', 'driver_confirmed', 'in_progress'].includes(tripSt)) active = 3
    if (tripSt === 'completed') active = 4
  }

  steps[0].state = 'done'
  for (let i = 1; i < steps.length; i++) {
    if (i < active) steps[i].state = 'done'
    else if (i === active) steps[i].state = st === 'rejected' && i === 1 ? 'rejected' : 'current'
    else steps[i].state = 'upcoming'
  }
  if (st === 'rejected') {
    for (let i = 2; i < steps.length; i++) steps[i].state = 'upcoming'
  }

  return steps
})

function stepCircleClass(state) {
  if (state === 'done') return 'border-teal-500 bg-teal-500 text-white'
  if (state === 'current') return 'border-teal-500 bg-white text-teal-600'
  if (state === 'rejected') return 'border-rose-400 bg-white text-rose-500'
  return 'border-slate-200 bg-white text-slate-300'
}

function statusBadgeClass(status) {
  if (status === 'approved') return 'bg-teal-50 text-teal-800 ring-1 ring-inset ring-teal-600/15'
  if (status === 'pending') return 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-500/10'
  if (status === 'rejected') return 'bg-rose-50 text-rose-800 ring-1 ring-inset ring-rose-600/15'
  return 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-500/10'
}

function fmt(v) {
  return v ? new Date(v).toLocaleString('vi-VN') : '-'
}

function fmtShort(v) {
  if (!v) return ''
  const d = new Date(v)
  return d.toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
}

function fmtDateVi(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleDateString('vi-VN')
}

function fmtTimeWindow(depart, arrive) {
  if (!depart) return '—'
  const opt = { hour: '2-digit', minute: '2-digit' }
  const a = new Date(depart).toLocaleTimeString('vi-VN', opt)
  if (!arrive) return a
  const b = new Date(arrive).toLocaleTimeString('vi-VN', opt)
  return `${a} - ${b}`
}

function formatVndCurrency(n) {
  return `${new Intl.NumberFormat('vi-VN').format(Number(n))} VNĐ`
}

function doPrint() {
  window.print()
}

async function load() {
  loading.value = true
  try {
    req.value = await getDispatchRequest(route.params.id)
    if (req.value?.paper_status === 'pending') {
      paperForm.value.paper_reference = req.value.paper_reference ?? ''
    }
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

function uploadRequestDocument(file, onProgress) {
  return uploadAttachment({
    attachable_type: 'dispatch_request',
    attachable_id: Number(route.params.id),
    kind: 'request_attachment',
    file,
    onProgress,
  })
}

function onDocUploaded() {
  attachErr.value = ''
  load()
}

async function downloadFile(a) {
  attachErr.value = ''
  try {
    await downloadBinaryAttachmentFromApi(a.id, a.original_name || 'download')
  } catch (e) {
    attachErr.value = e?.response?.data?.message ?? 'Không tải được file.'
  }
}

async function removeAttachment(a) {
  if (!canDeleteAttachment.value) return
  attachErr.value = ''
  deletingId.value = a.id
  try {
    await deleteAttachment(a.id)
    await load()
  } catch (e) {
    attachErr.value = e?.response?.data?.message ?? 'Không xóa được file.'
  } finally {
    deletingId.value = null
  }
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
</script>

<style scoped>
@media print {
  .no-print {
    display: none !important;
  }
}
</style>
