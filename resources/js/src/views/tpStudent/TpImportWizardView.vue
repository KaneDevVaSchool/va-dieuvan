<template>
  <div class="mx-auto max-w-4xl space-y-5">
    <div>
      <button class="text-xs text-slate-400 hover:text-slate-600" @click="goBack">← Danh sách học sinh</button>
      <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">Nhập học sinh từ Excel</h1>
    </div>

    <ol class="flex flex-wrap gap-2 text-xs">
      <li
        v-for="(s, i) in steps"
        :key="s.key"
        :class="[
          'flex items-center gap-1.5 rounded-full px-3 py-1 font-medium',
          step === i ? 'bg-va-800 text-white' : i < step ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500',
        ]"
      >
        <span>{{ i + 1 }}.</span> {{ s.label }}
      </li>
    </ol>

    <!-- Step 1: Upload -->
    <div v-if="step === 0" class="space-y-3 rounded-xl border border-slate-200 bg-white p-5">
      <p class="text-sm text-slate-600">Chọn tệp .xlsx / .csv. Dòng đầu tiên là tiêu đề cột.</p>
      <input type="file" accept=".xlsx,.xls,.csv" @change="onFile" class="block text-sm" />
      <Button :loading="busy" :disabled="!file" @click="doUpload">Tải lên &amp; đọc</Button>
    </div>

    <!-- Step 2: Mapping -->
    <div v-else-if="step === 1" class="space-y-3 rounded-xl border border-slate-200 bg-white p-5">
      <p class="text-sm text-slate-600">Ghép cột tệp với trường dữ liệu. Cột "Họ tên" là bắt buộc.</p>
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

    <!-- Step 3: Preview/Validate -->
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

    <!-- Step 4: Auto-fix -->
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

    <!-- Step 5: Import -->
    <div v-else-if="step === 4" class="space-y-3 rounded-xl border border-slate-200 bg-white p-5">
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
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from '../../components/ui/Button.vue'
import Select from '../../components/ui/Select.vue'
import {
  uploadImport,
  saveImportMapping,
  listImportRows,
  applyImportFixes,
  executeImport,
  importErrorReportUrl,
} from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const router = useRouter()
const step = ref(0)
const busy = ref(false)
const file = ref(null)
const batch = ref({ header_row: [], valid_rows: 0, warning_rows: 0, error_rows: 0 })
const rows = ref([])
const result = ref(null)

const steps = [
  { key: 'upload', label: 'Tải lên' },
  { key: 'mapping', label: 'Ghép cột' },
  { key: 'preview', label: 'Kiểm tra' },
  { key: 'fix', label: 'Sửa tự động' },
  { key: 'import', label: 'Nhập' },
]

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

async function doUpload() {
  busy.value = true
  try {
    batch.value = await uploadImport(file.value)
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
</script>
