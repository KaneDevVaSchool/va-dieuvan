<template>
  <div class="space-y-4 md:space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl md:text-2xl">
          Chương trình đưa đón
        </h1>
        <p class="mt-1 text-sm text-slate-500">Quản lý chương trình đưa đón học sinh (P2P redesign)</p>
      </div>
      <Button @click="goCreate">
        <PlusIcon class="h-4 w-4" /> Tạo chương trình
      </Button>
    </div>

    <div class="grid grid-cols-2 gap-3 rounded-xl border border-slate-200 bg-white p-3 sm:grid-cols-4">
      <Select v-model="filters.status" label="Trạng thái">
        <option value="">Tất cả</option>
        <option value="draft">Nháp</option>
        <option value="active">Đang chạy</option>
        <option value="paused">Tạm dừng</option>
        <option value="completed">Hoàn thành</option>
        <option value="cancelled">Đã hủy</option>
      </Select>
      <Input v-model="filters.search" label="Tìm kiếm" placeholder="Tên hoặc mã" />
    </div>

    <div v-if="loading" class="flex items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-sm text-slate-500">
      <ArrowPathIcon class="mr-2 h-5 w-5 animate-spin" /> Đang tải…
    </div>
    <div v-else-if="!items.length" class="flex flex-col items-center justify-center rounded-xl border border-slate-200 bg-white py-16 text-center">
      <AcademicCapIcon class="mb-3 h-12 w-12 text-slate-300" />
      <p class="text-sm font-medium text-slate-600">Chưa có chương trình nào</p>
      <Button class="mt-3" @click="goCreate">Tạo chương trình đầu tiên</Button>
    </div>

    <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
      <table class="w-full min-w-[52rem] text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-3 py-2.5 font-medium">Mã</th>
            <th class="px-3 py-2.5 font-medium">Tên chương trình</th>
            <th class="px-3 py-2.5 font-medium">Thời gian</th>
            <th class="px-3 py-2.5 font-medium">Số ngày</th>
            <th class="px-3 py-2.5 font-medium">Học sinh</th>
            <th class="px-3 py-2.5 font-medium">Trạng thái</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr
            v-for="p in items"
            :key="p.id"
            class="cursor-pointer hover:bg-slate-50/60"
            @click="goWorkspace(p.id)"
          >
            <td class="px-3 py-2.5 font-mono text-xs text-slate-500">{{ p.code }}</td>
            <td class="px-3 py-2.5 font-medium text-slate-900">{{ p.name }}</td>
            <td class="px-3 py-2.5 text-slate-600">{{ p.start_date }} → {{ p.end_date }}</td>
            <td class="px-3 py-2.5 text-slate-600">{{ p.day_count }}</td>
            <td class="px-3 py-2.5 text-slate-600">{{ p.enrolled_count }}</td>
            <td class="px-3 py-2.5">
              <span :class="statusClass(p.status)">{{ statusLabel(p.status) }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { PlusIcon, ArrowPathIcon, AcademicCapIcon } from '@heroicons/vue/24/outline'
import Button from '../../components/ui/Button.vue'
import Select from '../../components/ui/Select.vue'
import Input from '../../components/ui/Input.vue'
import { listPrograms } from '../../api/transportProgram'
import { showAppErrorFromApi } from '../../composables/appMessage'

const router = useRouter()
const loading = ref(false)
const items = ref([])
const filters = reactive({ status: '', search: '' })
let searchTimer = null

async function load() {
  loading.value = true
  try {
    const res = await listPrograms({
      status: filters.status || undefined,
      search: filters.search || undefined,
    })
    items.value = res?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

watch(() => filters.status, load)
watch(() => filters.search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(load, 300)
})

function goCreate() {
  router.push({ name: 'tpProgramCreate' })
}
function goWorkspace(id) {
  router.push({ name: 'tpProgramWorkspace', params: { id } })
}

function statusLabel(s) {
  return {
    draft: 'Nháp',
    active: 'Đang chạy',
    paused: 'Tạm dừng',
    completed: 'Hoàn thành',
    cancelled: 'Đã hủy',
  }[s] || s
}
function statusClass(s) {
  const base = 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium '
  return base + ({
    draft: 'bg-slate-100 text-slate-600',
    active: 'bg-emerald-100 text-emerald-700',
    paused: 'bg-amber-100 text-amber-700',
    completed: 'bg-sky-100 text-sky-700',
    cancelled: 'bg-rose-100 text-rose-700',
  }[s] || 'bg-slate-100 text-slate-600')
}

onMounted(load)
</script>
