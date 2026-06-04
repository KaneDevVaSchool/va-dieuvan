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

      <!-- Target program -->
      <div class="rounded-xl border border-va-200 bg-va-50/60 px-3 py-2">
        <div class="text-[11px] font-medium uppercase tracking-wide text-va-700/80">Chương trình đích</div>
        <div class="mt-1 flex items-center gap-2">
          <select v-model="targetProgramId" class="h-8 max-w-[12rem] rounded-md border border-va-200 bg-white px-2 text-sm font-semibold text-va-800 focus:border-va-500 focus:outline-none focus:ring-2 focus:ring-va-500/20">
            <option :value="null">Không chỉ định</option>
            <option v-for="p in programs" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <PencilSquareIcon class="h-4 w-4 text-va-500" />
        </div>
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
      <!-- Upload card -->
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-start justify-between">
          <div>
            <h2 class="text-base font-semibold text-slate-900">Chọn tệp để tải lên</h2>
            <p class="mt-0.5 text-sm text-slate-500">Hỗ trợ định dạng .xlsx, .xls, .csv — Tối đa 10MB</p>
          </div>
          <span class="rounded-full bg-va-50 px-2.5 py-1 text-xs font-medium text-va-700">Bước 1 / 3</span>
        </div>

        <div
          class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-10 text-center transition"
          :class="dragging ? 'border-va-500 bg-va-50/60' : 'border-slate-200 bg-slate-50/40'"
          @dragover.prevent="dragging = true"
          @dragleave.prevent="dragging = false"
          @drop.prevent="onDrop"
        >
          <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-va-100 text-va-600">
            <CloudArrowUpIcon class="h-7 w-7" />
          </div>
          <div class="text-sm font-semibold text-slate-700">Kéo &amp; thả tệp vào đây</div>
          <div class="mt-0.5 text-xs text-slate-400">hoặc nhấn để chọn từ máy tính của bạn</div>

          <div v-if="file" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-white px-3 py-1.5 text-sm text-slate-700 shadow-sm ring-1 ring-slate-200">
            <DocumentIcon class="h-4 w-4 text-emerald-500" /> {{ file.name }}
            <button class="text-slate-400 hover:text-rose-500" @click="file = null"><XMarkIcon class="h-4 w-4" /></button>
          </div>

          <Button class="mt-4" variant="secondary" @click="fileInput?.click()">
            <FolderOpenIcon class="h-4 w-4" /> Chọn tệp
          </Button>
          <input ref="fileInput" type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="onFile" />

          <div class="mt-5 flex flex-wrap items-center justify-center gap-3 text-xs text-slate-400">
            <span class="inline-flex items-center gap-1"><TableCellsIcon class="h-4 w-4 text-emerald-500" /> .xlsx / .xls</span>
            <span class="inline-flex items-center gap-1"><DocumentTextIcon class="h-4 w-4 text-sky-500" /> .csv</span>
            <span class="inline-flex items-center gap-1"><ScaleIcon class="h-4 w-4" /> Tối đa 10MB</span>
          </div>
        </div>
      </div>

      <!-- Config card -->
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="text-base font-semibold text-slate-900">Cấu hình Import</h2>
        <p class="mt-0.5 text-sm text-slate-500">Thiết lập các tùy chọn trước khi tiến hành mapping dữ liệu</p>

        <div class="mt-4 grid gap-6 sm:grid-cols-2">
          <div>
            <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Chế độ Import</div>
            <div class="space-y-2">
              <label
                v-for="m in importModes"
                :key="m.value"
                class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition"
                :class="config.mode === m.value ? 'border-va-500 bg-va-50/50 ring-1 ring-va-500/20' : 'border-slate-200 hover:border-slate-300'"
              >
                <input type="radio" :value="m.value" v-model="config.mode" class="mt-0.5 h-4 w-4 accent-va-800" />
                <span>
                  <span class="block text-sm font-medium text-slate-800">{{ m.label }}</span>
                  <span class="block text-xs text-slate-400">{{ m.desc }}</span>
                </span>
              </label>
            </div>
          </div>

          <div>
            <div class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tùy chọn bổ sung</div>
            <div class="space-y-3">
              <label class="flex items-start justify-between gap-3">
                <span>
                  <span class="block text-sm font-medium text-slate-800">Bỏ qua hàng tiêu đề</span>
                  <span class="block text-xs text-slate-400">Dòng đầu tiên của tệp là tiêu đề cột</span>
                </span>
                <Toggle v-model="config.has_header" />
              </label>
              <label class="flex items-start justify-between gap-3">
                <span>
                  <span class="block text-sm font-medium text-slate-800">Bỏ qua dòng lỗi</span>
                  <span class="block text-xs text-slate-400">Tiếp tục import khi gặp dòng dữ liệu không hợp lệ</span>
                </span>
                <Toggle v-model="config.skip_errors" />
              </label>
            </div>
          </div>
        </div>

        <div class="mt-5 flex justify-end">
          <Button :loading="busy" :disabled="!file" @click="doUpload">
            Tải lên &amp; tiếp tục <ArrowRightIcon class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </template>

    <!-- Phase 2a: Mapping -->
    <div v-else-if="step === 1" class="space-y-3 rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="text-base font-semibold text-slate-900">Mapping &amp; Preview</h2>
      <p class="text-sm text-slate-500">Ghép cột tệp với trường dữ liệu. Cột "Họ tên" là bắt buộc.</p>
      <div class="grid gap-3 sm:grid-cols-2">
        <div v-for="field in mappableFields" :key="field.key">
          <Select v-model="mapping[field.key]" :label="field.label + (field.required ? ' *' : '')">
            <option value="">— Không ghép —</option>
            <option v-for="col in batch.header_row" :key="col" :value="col">{{ col }}</option>
          </Select>
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
        <table class="w-full min-w-[40rem] text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-3 py-2 font-medium">Dòng</th>
              <th class="px-3 py-2 font-medium">Họ tên</th>
              <th class="px-3 py-2 font-medium">Mã</th>
              <th class="px-3 py-2 font-medium">Trạng thái</th>
              <th class="px-3 py-2 font-medium">Ghi chú</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="r in rows" :key="r.id">
              <td class="px-3 py-2 text-slate-500">{{ r.row_number }}</td>
              <td class="px-3 py-2 text-slate-900">{{ r.data.full_name }}</td>
              <td class="px-3 py-2 text-slate-600">{{ r.data.code }}</td>
              <td class="px-3 py-2"><span :class="rowStatusClass(r.validation_status)">{{ r.validation_status }}</span></td>
              <td class="px-3 py-2 text-xs text-slate-500">{{ (r.validation_errors || []).map((e) => e.message).join('; ') }}</td>
            </tr>
          </tbody>
        </table>
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
        <a v-if="batch.error_rows > 0" :href="errorReportUrl" class="ml-2 underline">Tải báo cáo lỗi</a>
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
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  ArrowLeftIcon, ArrowRightIcon, CheckIcon, CloudArrowUpIcon, DocumentIcon, XMarkIcon,
  FolderOpenIcon, TableCellsIcon, DocumentTextIcon, ScaleIcon, PencilSquareIcon,
} from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import Select from '../../components/ui/Select.vue'
import Toggle from '../../components/ui/Toggle.vue'
import {
  uploadImport,
  saveImportMapping,
  listImportRows,
  applyImportFixes,
  executeImport,
  importErrorReportUrl,
  listPrograms,
} from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

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
const result = ref(null)

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

const errorReportUrl = computed(() => importErrorReportUrl(batch.value.id))

function onFile(e) {
  file.value = e.target.files?.[0] ?? null
}
function onDrop(e) {
  dragging.value = false
  const f = e.dataTransfer?.files?.[0]
  if (f) file.value = f
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
    await loadRows()
    step.value = 2
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busy.value = false
  }
}

async function loadRows() {
  rows.value = (await listImportRows(batch.value.id, { per_page: 100 }))?.items ?? []
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

function goBack() {
  router.push({ name: 'tpStudents' })
}

onMounted(loadPrograms)
</script>
