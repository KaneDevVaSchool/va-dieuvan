<template>
  <div class="space-y-4 md:space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl md:text-2xl">Học sinh</h1>
        <p class="mt-1 text-sm text-slate-500">Danh mục học sinh dùng cho chương trình đưa đón.</p>
      </div>
      <div class="flex gap-2">
        <Button variant="secondary" @click="goImport"><ArrowUpTrayIcon class="h-4 w-4" /> Nhập Excel</Button>
        <Button @click="openCreate"><PlusIcon class="h-4 w-4" /> Thêm học sinh</Button>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3 rounded-xl border border-slate-200 bg-white p-3 sm:grid-cols-4">
      <Input v-model="filters.search" label="Tìm kiếm" placeholder="Tên, mã, SĐT" />
      <Select v-model="filters.status" label="Trạng thái">
        <option value="">Tất cả</option>
        <option value="active">Đang học</option>
        <option value="inactive">Ngừng</option>
        <option value="transferred">Chuyển trường</option>
        <option value="graduated">Tốt nghiệp</option>
      </Select>
      <Input v-model="filters.grade" label="Khối" />
      <Input v-model="filters.class_name" label="Lớp" />
    </div>

    <div v-if="loading" class="flex items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> Đang tải…
    </div>
    <div v-else-if="!items.length" class="rounded-xl border border-slate-200 bg-white py-16 text-center text-sm text-slate-500">
      Không có học sinh.
    </div>
    <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
      <table class="w-full min-w-[48rem] text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-3 py-2.5 font-medium">Mã</th>
            <th class="px-3 py-2.5 font-medium">Họ tên</th>
            <th class="px-3 py-2.5 font-medium">Lớp</th>
            <th class="px-3 py-2.5 font-medium">Phụ huynh</th>
            <th class="px-3 py-2.5 font-medium">Trạng thái</th>
            <th class="px-3 py-2.5 text-right font-medium">Thao tác</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="s in items" :key="s.id" class="hover:bg-slate-50/60">
            <td class="px-3 py-2.5 font-mono text-xs text-slate-500">{{ s.code }}</td>
            <td class="px-3 py-2.5 font-medium text-slate-900">{{ s.full_name }}</td>
            <td class="px-3 py-2.5 text-slate-600">{{ s.grade }} {{ s.class_name }}</td>
            <td class="px-3 py-2.5 text-slate-600">{{ s.parent_name }} <span class="text-slate-400">{{ s.parent_phone }}</span></td>
            <td class="px-3 py-2.5"><span :class="statusClass(s.status)">{{ statusLabel(s.status) }}</span></td>
            <td class="px-3 py-2.5 text-right">
              <button class="text-xs font-medium text-va-800 hover:underline" @click="openEdit(s)">Sửa</button>
              <button class="ml-3 text-xs font-medium text-rose-600 hover:underline" @click="remove(s)">Xóa</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <TpStudentFormModal v-if="showForm" :student="editing" @close="showForm = false" @saved="onSaved" />
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { PlusIcon, ArrowPathIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import TpStudentFormModal from '../../components/transportProgram/TpStudentFormModal.vue'
import { listStudents, deleteStudent } from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { confirmAction } from '../../composables/useConfirm'

const router = useRouter()
const loading = ref(false)
const items = ref([])
const showForm = ref(false)
const editing = ref(null)
const filters = reactive({ search: '', status: '', grade: '', class_name: '' })
let timer = null

async function load() {
  loading.value = true
  try {
    const res = await listStudents({
      search: filters.search || undefined,
      status: filters.status || undefined,
      grade: filters.grade || undefined,
      class_name: filters.class_name || undefined,
    })
    items.value = res?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

watch(() => [filters.status, filters.grade, filters.class_name], load)
watch(() => filters.search, () => {
  clearTimeout(timer)
  timer = setTimeout(load, 300)
})

function openCreate() {
  editing.value = null
  showForm.value = true
}
function openEdit(s) {
  editing.value = { ...s }
  showForm.value = true
}
function onSaved() {
  showForm.value = false
  load()
}
function goImport() {
  router.push({ name: 'tpImportWizard' })
}

async function remove(s) {
  const ok = await confirmAction({ title: 'Xóa học sinh', message: `Xóa ${s.full_name}?`, danger: true, confirmLabel: 'Xóa' })
  if (!ok) return
  try {
    await deleteStudent(s.id)
    showAppSuccess('Đã xóa.')
    load()
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

function statusLabel(s) {
  return { active: 'Đang học', inactive: 'Ngừng', transferred: 'Chuyển', graduated: 'Tốt nghiệp' }[s] || s
}
function statusClass(s) {
  const base = 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium '
  return base + ({ active: 'bg-emerald-100 text-emerald-700', inactive: 'bg-slate-100 text-slate-600', transferred: 'bg-amber-100 text-amber-700', graduated: 'bg-sky-100 text-sky-700' }[s] || 'bg-slate-100 text-slate-600')
}

onMounted(load)
</script>
