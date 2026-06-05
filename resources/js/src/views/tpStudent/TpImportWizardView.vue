<template>
  <div class="mx-auto max-w-5xl space-y-5">
    <!-- Header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <button class="mb-1 inline-flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600" @click="goBack">
          <ArrowLeftIcon class="h-3.5 w-3.5" /> Danh sách học sinh
        </button>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 md:text-2xl">Import Danh sách Học sinh</h1>
        <p class="mt-1 text-sm text-slate-500">Tải lên tệp Excel hoặc CSV để nhập hàng loạt dữ liệu học sinh vào hệ thống</p>
      </div>
    </div>

    <!-- Stepper -->
    <div class="flex items-center rounded-xl border border-slate-200 bg-white px-4 py-3">
      <template v-for="(p, i) in phases" :key="p.key">
        <div class="flex items-center gap-2">
          <span
            class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
            :class="i < phase ? 'bg-emerald-500 text-white' : i === phase ? 'bg-va-800 text-white' : 'bg-slate-100 text-slate-400'"
          >
            <CheckIcon v-if="i < phase" class="h-4 w-4" />
            <span v-else>{{ i + 1 }}</span>
          </span>
          <div class="hidden sm:block">
            <div class="text-[11px] font-medium" :class="i === phase ? 'text-va-800' : 'text-slate-500'">Bước {{ i + 1 }}</div>
            <div class="text-xs font-semibold" :class="i === phase ? 'text-slate-900' : 'text-slate-400'">{{ p.label }}</div>
          </div>
        </div>
        <div v-if="i < phases.length - 1" class="mx-3 h-px flex-1" :class="i < phase ? 'bg-emerald-300' : 'bg-slate-200'"></div>
      </template>
    </div>

    <!-- Phase 1: Upload + config -->
    <template v-if="step === 0">
      <div class="grid gap-4 lg:grid-cols-5">
        <!-- Upload + preview -->
        <div class="space-y-4 lg:col-span-3">
          <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
              <div>
                <h2 class="text-sm font-semibold text-slate-900">Tệp import</h2>
                <p class="text-xs text-slate-500">.xlsx, .xls, .csv — tối đa 10MB</p>
              </div>
              <button
                type="button"
                :disabled="sampleDownloading"
                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:border-va-300 hover:text-va-700 disabled:opacity-60"
                @click="doDownloadSample"
              >
                <ArrowDownTrayIcon class="h-3.5 w-3.5" />
                {{ sampleDownloading ? 'Đang tải…' : 'File mẫu' }}
              </button>
            </div>

            <div
              class="flex flex-col items-center rounded-lg border-2 border-dashed px-4 py-6 text-center transition"
              :class="dragging ? 'border-va-500 bg-va-50/50' : 'border-slate-200 bg-slate-50/30'"
              @dragover.prevent="dragging = true"
              @dragleave.prevent="dragging = false"
              @drop.prevent="onDrop"
            >
              <CloudArrowUpIcon class="h-8 w-8 text-va-500" />
              <p class="mt-2 text-sm font-medium text-slate-700">Kéo thả hoặc chọn tệp</p>
              <div v-if="file" class="mt-2 inline-flex max-w-full items-center gap-2 truncate rounded-md bg-white px-2 py-1 text-xs text-slate-700 ring-1 ring-slate-200">
                <DocumentIcon class="h-3.5 w-3.5 shrink-0 text-emerald-500" />
                <span class="truncate">{{ file.name }}</span>
                <button type="button" class="shrink-0 text-slate-400 hover:text-rose-500" @click="clearFile"><XMarkIcon class="h-3.5 w-3.5" /></button>
              </div>
              <Button class="mt-3" variant="secondary" @click="fileInput?.click()">
                <FolderOpenIcon class="h-4 w-4" /> Chọn tệp
              </Button>
              <input ref="fileInput" type="file" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" class="hidden" @change="onFile" />
            </div>

            <!-- Client preview -->
            <div v-if="file" class="mt-4">
              <div class="mb-2 flex items-center justify-between">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Xem trước (sheet đầu)</h3>
                <span v-if="previewLoading" class="text-xs text-slate-400">Đang đọc…</span>
                <span v-else-if="filePreview.totalRows" class="text-xs text-slate-500">{{ filePreview.totalRows }} dòng dữ liệu</span>
              </div>
              <p v-if="filePreview.note" class="mb-2 text-xs text-amber-700">{{ filePreview.note }}</p>
              <div v-if="filePreview.headers.length" class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="w-full min-w-[32rem] text-left text-xs">
                  <thead class="bg-slate-800 text-white">
                    <tr>
                      <th v-for="(h, hi) in filePreview.headers" :key="hi" class="whitespace-nowrap px-2 py-1.5 font-medium">{{ h || `Cột ${hi + 1}` }}</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 bg-white">
                    <tr v-for="(row, ri) in filePreview.rows" :key="ri" class="hover:bg-slate-50/80">
                      <td v-for="(cell, ci) in row" :key="ci" class="max-w-[10rem] truncate px-2 py-1.5 text-slate-700" :title="cell">{{ cell }}</td>
                    </tr>
                  </tbody>
                </table>
                <p v-if="filePreview.totalRows > filePreview.rows.length" class="border-t border-slate-100 bg-slate-50 px-2 py-1.5 text-[11px] text-slate-500">
                  Hiển thị {{ filePreview.rows.length }}/{{ filePreview.totalRows }} dòng — phần còn lại sẽ xử lý sau khi tải lên.
                </p>
              </div>
            </div>
          </div>

          <details class="rounded-lg border border-sky-100 bg-sky-50/60 px-3 py-2 text-xs text-slate-600">
            <summary class="cursor-pointer font-semibold text-sky-900">Hướng dẫn nhập file</summary>
            <ol class="mt-2 list-decimal space-y-1 pl-4 leading-relaxed">
              <li>Dùng sheet <strong>Danh sách học sinh</strong> (dòng 1 = tiêu đề cột).</li>
              <li><strong>Họ tên</strong> bắt buộc; <strong>Mã học sinh</strong> nên có để cập nhật.</li>
              <li>Kiểm tra bảng xem trước trước khi bấm tải lên.</li>
            </ol>
          </details>
        </div>

        <!-- Config sidebar -->
        <div class="space-y-4 lg:col-span-2">
          <div class="rounded-xl border border-va-200 bg-va-50/40 p-4">
            <div class="text-[11px] font-semibold uppercase tracking-wide text-va-700/80">Chương trình đích</div>
            <select v-model="targetProgramId" class="mt-1.5 h-9 w-full rounded-md border border-va-200 bg-white px-2 text-sm text-va-900 focus:border-va-500 focus:outline-none focus:ring-2 focus:ring-va-500/20">
              <option :value="null">Không chỉ định</option>
              <option v-for="p in programs" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>

          <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h2 class="text-sm font-semibold text-slate-900">Cấu hình</h2>
            <div class="mt-3 space-y-2">
              <label
                v-for="m in importModes"
                :key="m.value"
                class="flex cursor-pointer items-start gap-2 rounded-lg border p-2.5 text-xs transition"
                :class="config.mode === m.value ? 'border-va-500 bg-va-50/50' : 'border-slate-200'"
              >
                <input type="radio" :value="m.value" v-model="config.mode" class="mt-0.5 accent-va-800" />
                <span>
                  <span class="font-medium text-slate-800">{{ m.label }}</span>
                  <span class="block text-slate-400">{{ m.desc }}</span>
                </span>
              </label>
            </div>
            <div class="mt-3 space-y-2 border-t border-slate-100 pt-3">
              <label class="flex items-center justify-between gap-2 text-xs">
                <span class="text-slate-700">Dòng đầu là tiêu đề</span>
                <Toggle v-model="config.has_header" />
              </label>
              <label class="flex items-center justify-between gap-2 text-xs">
                <span class="text-slate-700">Bỏ qua dòng lỗi khi nhập</span>
                <Toggle v-model="config.skip_errors" />
              </label>
            </div>
            <Button class="mt-4 w-full" :loading="busy" :disabled="!file" @click="doUpload">
              Tải lên &amp; tiếp tục <ArrowRightIcon class="h-4 w-4" />
            </Button>
          </div>
        </div>
      </div>
    </template>

    <!-- Phase 2a: Mapping -->
    <div v-else-if="step === 1" class="space-y-4 rounded-xl border border-slate-200 bg-white p-4">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
          <h2 class="text-base font-semibold text-slate-900">Ghép cột &amp; xem trước</h2>
          <p class="text-xs text-slate-500">{{ batch.original_filename }} — {{ batch.total_rows }} dòng</p>
        </div>
      </div>
      <div class="grid gap-4 xl:grid-cols-2">
        <div class="grid gap-2 sm:grid-cols-2">
          <div v-for="field in mappableFields" :key="field.key">
            <Select v-model="mapping[field.key]" :label="field.label + (field.required ? ' *' : '')">
              <option value="">— Không ghép —</option>
              <option v-for="col in batch.header_row" :key="col" :value="col">{{ col }}</option>
            </Select>
          </div>
        </div>
        <div class="overflow-x-auto rounded-lg border border-slate-200">
          <div class="border-b border-slate-100 bg-slate-50 px-2 py-1.5 text-[11px] font-semibold uppercase text-slate-500">Dữ liệu gốc (5 dòng đầu)</div>
          <table class="w-full min-w-[24rem] text-left text-xs">
            <thead class="bg-slate-800 text-white">
              <tr>
                <th v-for="col in batch.header_row" :key="col" class="whitespace-nowrap px-2 py-1 font-medium">{{ col }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="r in rawPreviewRows" :key="r.id">
                <td v-for="col in batch.header_row" :key="col" class="max-w-[8rem] truncate px-2 py-1 text-slate-700" :title="cellRaw(r, col)">{{ cellRaw(r, col) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="flex justify-between">
        <Button variant="secondary" @click="step = 0">Quay lại</Button>
        <Button :loading="busy" @click="doMapping">Kiểm tra dữ liệu</Button>
      </div>
    </div>

    <!-- Phase 2b: Preview/Validate -->
    <div v-else-if="step === 2" class="space-y-3">
      <div class="grid grid-cols-3 gap-3">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-center">
          <div class="text-2xl font-bold text-emerald-700">{{ batch.valid_rows }}</div>
          <div class="text-xs text-emerald-600">Hợp lệ</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-center">
          <div class="text-2xl font-bold text-amber-700">{{ batch.warning_rows }}</div>
          <div class="text-xs text-amber-600">Cảnh báo</div>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-center">
          <div class="text-2xl font-bold text-rose-700">{{ batch.error_rows }}</div>
          <div class="text-xs text-rose-600">Lỗi</div>
        </div>
      </div>
      <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <p class="border-b border-slate-100 px-3 py-2 text-xs text-slate-500">
          Nhấn biểu tượng bút để sửa dòng; ✓ lưu, ✕ hủy.
        </p>
        <table class="w-full min-w-[48rem] text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-3 py-2 font-medium">Dòng</th>
              <th v-for="col in inlineEditColumns" :key="col.key" class="px-3 py-2 font-medium">{{ col.label }}</th>
              <th class="px-3 py-2 font-medium">Trạng thái</th>
              <th class="px-3 py-2 font-medium">Ghi chú</th>
              <th class="w-24 px-3 py-2 font-medium text-center">Sửa</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-if="rowsLoading">
              <td :colspan="inlineEditColumns.length + 4" class="px-3 py-6 text-center text-sm text-slate-400">Đang tải dữ liệu…</td>
            </tr>
            <tr v-else-if="!rows.length">
              <td :colspan="inlineEditColumns.length + 4" class="px-3 py-6 text-center text-sm text-slate-400">Không có dòng trên trang này.</td>
            </tr>
            <template v-else>
            <tr
              v-for="r in rows"
              :key="r.id"
              class="transition-colors"
              :class="editingRowId === r.id ? 'bg-va-50/40' : 'hover:bg-slate-50/50'"
            >
              <td class="px-3 py-2 text-slate-500">{{ r.row_number }}</td>
              <td v-for="col in inlineEditColumns" :key="col.key" class="px-3 py-2">
                <input
                  v-if="editingRowId === r.id && editDraft"
                  v-model="editDraft[col.key]"
                  type="text"
                  class="w-full min-w-[5rem] rounded-md border border-slate-300 px-2 py-1 text-sm focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
                  :placeholder="col.label"
                />
                <span v-else class="text-slate-900">{{ displayCell(r, col.key) }}</span>
              </td>
              <td class="px-3 py-2"><span :class="rowStatusClass(r.validation_status)">{{ statusLabel(r.validation_status) }}</span></td>
              <td class="px-3 py-2 text-xs text-slate-500">{{ (r.validation_errors || []).map((e) => e.message).join('; ') }}</td>
              <td class="px-3 py-2">
                <div class="flex items-center justify-center gap-1">
                  <template v-if="editingRowId === r.id">
                    <button
                      type="button"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50"
                      :disabled="rowSaving"
                      aria-label="Lưu chỉnh sửa"
                      @click="saveRowEdit(r)"
                    >
                      <CheckIcon class="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-slate-600 hover:bg-slate-50 disabled:opacity-50"
                      :disabled="rowSaving"
                      aria-label="Hủy chỉnh sửa"
                      @click="cancelRowEdit"
                    >
                      <XMarkIcon class="h-4 w-4" />
                    </button>
                  </template>
                  <button
                    v-else
                    type="button"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 text-slate-500 hover:border-va-300 hover:text-va-700 disabled:opacity-40"
                    :disabled="editingRowId != null || rowSaving"
                    aria-label="Chỉnh sửa dòng"
                    @click="startRowEdit(r)"
                  >
                    <PencilSquareIcon class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
            </template>
          </tbody>
        </table>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-3 py-2.5">
          <label class="flex items-center gap-2 text-xs text-slate-600">
            <span class="whitespace-nowrap">Số dòng/trang</span>
            <select
              v-model.number="rowsPerPage"
              class="h-8 rounded-md border border-slate-200 bg-white px-2 text-sm text-slate-800 focus:border-va-500 focus:outline-none focus:ring-1 focus:ring-va-500/30"
              :disabled="rowsLoading || editingRowId != null"
              aria-label="Số dòng mỗi trang"
              @change="onRowsPerPageChange"
            >
              <option v-for="n in rowPageSizeOptions" :key="n" :value="n">{{ n }}</option>
            </select>
          </label>
          <span class="text-xs text-slate-500">
            <template v-if="rowMeta.total">
              Trang {{ rowMeta.current_page }} / {{ rowMeta.last_page }}
              <span class="text-slate-400">·</span>
              {{ rowMeta.total }} dòng
            </template>
            <template v-else>Chưa có dòng dữ liệu</template>
          </span>
          <div class="flex items-center gap-2">
            <Button
              variant="secondary"
              :disabled="rowsLoading || rowMeta.current_page <= 1 || editingRowId != null"
              @click="changeRowsPage(rowMeta.current_page - 1)"
            >
              Trước
            </Button>
            <Button
              variant="secondary"
              :disabled="rowsLoading || rowMeta.current_page >= rowMeta.last_page || editingRowId != null"
              @click="changeRowsPage(rowMeta.current_page + 1)"
            >
              Sau
            </Button>
          </div>
        </div>
      </div>
      <div class="flex justify-between">
        <Button variant="secondary" @click="step = 1">Quay lại</Button>
        <Button @click="step = 3">Tự động sửa</Button>
      </div>
    </div>

    <!-- Phase 2c: Auto-fix -->
    <div v-else-if="step === 3" class="space-y-3 rounded-xl border border-slate-200 bg-white p-5">
      <p class="text-sm text-slate-600">Chọn các quy tắc sửa tự động trước khi nhập.</p>
      <label v-for="rule in fixRules" :key="rule.key" class="flex items-center gap-2 text-sm">
        <input type="checkbox" v-model="rules[rule.key]" class="h-4 w-4 accent-va-800" />
        {{ rule.label }}
      </label>
      <div class="flex justify-between">
        <Button variant="secondary" @click="step = 2">Quay lại</Button>
        <div class="flex gap-2">
          <Button variant="secondary" :loading="busy" @click="doFixes">Áp dụng &amp; kiểm tra lại</Button>
          <Button @click="step = 4">Tiếp tục nhập</Button>
        </div>
      </div>
    </div>

    <!-- Phase 3: Import & result -->
    <div v-else-if="step === 4" class="space-y-3 rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="text-base font-semibold text-slate-900">Xác nhận &amp; Kết quả</h2>
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" v-model="options.include_warnings" class="h-4 w-4 accent-va-800" /> Nhập cả dòng cảnh báo
      </label>
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" v-model="options.update_existing" class="h-4 w-4 accent-va-800" /> Cập nhật học sinh đã tồn tại (theo mã)
      </label>
      <div v-if="result" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
        Đã nhập {{ result.imported }}, bỏ qua {{ result.skipped }}, lỗi {{ result.failed }}.
        <a v-if="batch.error_rows > 0" href="#" class="ml-2 underline" @click.prevent="doDownloadErrorReport">Tải báo cáo lỗi</a>
      </div>
      <div class="flex justify-between">
        <Button variant="secondary" @click="step = 3">Quay lại</Button>
        <Button v-if="!result" :loading="busy" @click="doExecute">Bắt đầu nhập</Button>
        <Button v-else @click="goBack">Hoàn tất</Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowDownTrayIcon, ArrowLeftIcon, ArrowRightIcon, CheckIcon, CloudArrowUpIcon,
  DocumentIcon, XMarkIcon, FolderOpenIcon, PencilSquareIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import Select from '../../components/ui/Select.vue'
import Toggle from '../../components/ui/Toggle.vue'
import {
  uploadImport,
  saveImportMapping,
  listImportRows,
  updateImportRow,
  applyImportFixes,
  executeImport,
  downloadImportErrorReport,
  downloadImportSample,
  listPrograms,
} from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { parseImportFilePreview } from '../../composables/useTpImportFilePreview'

const router = useRouter()
const step = ref(0)
const busy = ref(false)
const file = ref(null)
const fileInput = ref(null)
const dragging = ref(false)
const programs = ref([])
const targetProgramId = ref(null)
const batch = ref({ header_row: [], valid_rows: 0, warning_rows: 0, error_rows: 0 })
const rows = ref([])
const rawPreviewRows = ref([])
const result = ref(null)
const previewLoading = ref(false)
const filePreview = reactive({ headers: [], rows: [], totalRows: 0, note: null })
const editingRowId = ref(null)
const editDraft = ref(null)
const rowSaving = ref(false)
const rowsPage = ref(1)
const rowsPerPage = ref(10)
const rowPageSizeOptions = [5, 10, 15, 20]
const rowMeta = ref({ current_page: 1, last_page: 1, total: 0 })
const rowsLoading = ref(false)

const inlineEditColumns = [
  { key: 'full_name', label: 'Họ tên' },
  { key: 'code', label: 'Mã' },
  { key: 'grade', label: 'Khối' },
  { key: 'class_name', label: 'Lớp' },
  { key: 'parent_phone', label: 'SĐT PH' },
]

const phases = [
  { key: 'upload', label: 'Tải lên tệp' },
  { key: 'mapping', label: 'Mapping & Preview' },
  { key: 'result', label: 'Xác nhận & Kết quả' },
]
const phase = computed(() => (step.value === 0 ? 0 : step.value === 4 ? 2 : 1))

const importModes = [
  { value: 'create', label: 'Thêm mới', desc: 'Chỉ thêm học sinh mới, bỏ qua bản ghi đã tồn tại' },
  { value: 'update', label: 'Cập nhật', desc: 'Cập nhật học sinh đã tồn tại theo mã' },
  { value: 'upsert', label: 'Thêm & Cập nhật', desc: 'Thêm mới và cập nhật học sinh trùng mã' },
]
const config = reactive({ mode: 'create', has_header: true, skip_errors: false })

const mappableFields = [
  { key: 'full_name', label: 'Họ tên', required: true },
  { key: 'code', label: 'Mã học sinh' },
  { key: 'grade', label: 'Khối' },
  { key: 'class_name', label: 'Lớp' },
  { key: 'parent_name', label: 'Phụ huynh' },
  { key: 'parent_phone', label: 'SĐT phụ huynh' },
  { key: 'address', label: 'Địa chỉ' },
]
const mapping = reactive({})

const fixRules = [
  { key: 'trim_whitespace', label: 'Cắt khoảng trắng thừa' },
  { key: 'normalize_phone', label: 'Chuẩn hóa số điện thoại (+84)' },
  { key: 'capitalize_name', label: 'Viết hoa tên đúng chuẩn' },
  { key: 'uppercase_code', label: 'Viết hoa mã học sinh' },
]
const rules = reactive({ trim_whitespace: true, normalize_phone: true, capitalize_name: false, uppercase_code: false })

const options = reactive({ include_warnings: true, update_existing: false })

const sampleDownloading = ref(false)

watch(file, async (f) => {
  filePreview.headers = []
  filePreview.rows = []
  filePreview.totalRows = 0
  filePreview.note = null
  if (!f) return
  previewLoading.value = true
  try {
    const p = await parseImportFilePreview(f)
    filePreview.headers = p.headers
    filePreview.rows = p.rows
    filePreview.totalRows = p.totalRows
    filePreview.note = p.note
  } catch {
    filePreview.note = 'Không đọc được file để xem trước — vẫn có thể thử tải lên.'
  } finally {
    previewLoading.value = false
  }
})

async function doDownloadSample() {
  sampleDownloading.value = true
  try {
    await downloadImportSample()
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    sampleDownloading.value = false
  }
}

function onFile(e) {
  file.value = e.target.files?.[0] ?? null
}
function clearFile() {
  file.value = null
  if (fileInput.value) fileInput.value.value = ''
}
function onDrop(e) {
  dragging.value = false
  const f = e.dataTransfer?.files?.[0]
  if (f) file.value = f
}

function cellRaw(row, col) {
  const d = row.data || {}
  const v = d[col]
  return v == null ? '' : String(v)
}

async function doDownloadErrorReport() {
  try {
    await downloadImportErrorReport(batch.value.id)
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

async function loadPrograms() {
  try {
    const res = await listPrograms({ status: 'active' })
    programs.value = res?.items ?? res ?? []
  } catch {
    programs.value = []
  }
}

async function doUpload() {
  busy.value = true
  try {
    options.update_existing = config.mode !== 'create'
    options.include_warnings = config.skip_errors ? true : options.include_warnings
    batch.value = await uploadImport(file.value, targetProgramId.value)
    autoGuessMapping()
    await loadRawPreview()
    step.value = 1
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busy.value = false
  }
}

function autoGuessMapping() {
  const guess = { full_name: ['họ tên', 'hoten', 'name', 'tên'], code: ['mã', 'code', 'mshs'], grade: ['khối', 'grade'], class_name: ['lớp', 'class'], parent_name: ['phụ huynh', 'parent'], parent_phone: ['sđt', 'phone', 'điện thoại'], address: ['địa chỉ', 'address'] }
  for (const f of mappableFields) {
    const found = (batch.value.header_row || []).find((c) => (guess[f.key] || []).some((k) => String(c).toLowerCase().includes(k)))
    if (found) mapping[f.key] = found
  }
}

async function doMapping() {
  if (!mapping.full_name) {
    showAppErrorFromApi({ response: { data: { message: 'Vui lòng ghép cột Họ tên.' } } })
    return
  }
  busy.value = true
  try {
    const cleaned = {}
    for (const [k, v] of Object.entries(mapping)) if (v) cleaned[k] = v
    const res = await saveImportMapping(batch.value.id, cleaned)
    batch.value = { ...batch.value, ...res }
    rowsPage.value = 1
    cancelRowEdit()
    await loadRows(1)
    step.value = 2
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busy.value = false
  }
}

async function loadRows(page = rowsPage.value) {
  if (!batch.value?.id) return
  rowsLoading.value = true
  try {
    const res = await listImportRows(batch.value.id, { per_page: rowsPerPage.value, page })
    rows.value = res?.items ?? []
    const meta = res?.meta ?? {}
    rowMeta.value = {
      current_page: meta.current_page ?? page,
      last_page: meta.last_page ?? 1,
      total: meta.total ?? rows.value.length,
    }
    rowsPage.value = rowMeta.value.current_page
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    rowsLoading.value = false
  }
}

function onRowsPerPageChange() {
  cancelRowEdit()
  rowsPage.value = 1
  loadRows(1)
}

function changeRowsPage(page) {
  if (page < 1 || page > rowMeta.value.last_page) return
  cancelRowEdit()
  rowsPage.value = page
  loadRows(page)
}

async function loadRawPreview() {
  rawPreviewRows.value = (await listImportRows(batch.value.id, { per_page: 5 }))?.items ?? []
}

async function doFixes() {
  busy.value = true
  try {
    const res = await applyImportFixes(batch.value.id, rules)
    showAppSuccess(`Đã sửa ${res?.fixed ?? 0} dòng.`)
    batch.value = { ...batch.value, ...res }
    await loadRows()
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busy.value = false
  }
}

async function doExecute() {
  busy.value = true
  try {
    result.value = await executeImport(batch.value.id, options)
    showAppSuccess(`Nhập thành công ${result.value.imported} học sinh.`)
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busy.value = false
  }
}

function rowStatusClass(s) {
  const base = 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium '
  return base + ({ valid: 'bg-emerald-100 text-emerald-700', warning: 'bg-amber-100 text-amber-700', error: 'bg-rose-100 text-rose-700' }[s] || 'bg-slate-100 text-slate-600')
}

function statusLabel(s) {
  return ({ valid: 'Hợp lệ', warning: 'Cảnh báo', error: 'Lỗi', pending: 'Chờ' }[s] || s)
}

function displayCell(row, key) {
  const v = row.data?.[key]
  if (v == null || v === '') return '—'
  return String(v)
}

function startRowEdit(row) {
  editingRowId.value = row.id
  const d = row.data || {}
  editDraft.value = {
    full_name: d.full_name ?? '',
    code: d.code ?? '',
    grade: d.grade ?? '',
    class_name: d.class_name ?? '',
    parent_name: d.parent_name ?? '',
    parent_phone: d.parent_phone ?? '',
    address: d.address ?? '',
  }
}

function cancelRowEdit() {
  editingRowId.value = null
  editDraft.value = null
}

async function saveRowEdit(row) {
  if (!editDraft.value) return
  rowSaving.value = true
  try {
    const res = await updateImportRow(batch.value.id, row.id, { ...editDraft.value })
    const idx = rows.value.findIndex((x) => x.id === row.id)
    if (idx >= 0 && res?.row) {
      rows.value[idx] = { ...rows.value[idx], ...res.row }
    }
    batch.value = {
      ...batch.value,
      valid_rows: res.valid_rows,
      warning_rows: res.warning_rows,
      error_rows: res.error_rows,
    }
    cancelRowEdit()
    await loadRows(rowsPage.value)
    showAppSuccess('Đã cập nhật dòng.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    rowSaving.value = false
  }
}

function goBack() {
  router.push({ name: 'tpStudents' })
}

onMounted(loadPrograms)
</script>
