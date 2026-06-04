<template>
  <div class="space-y-3">
    <div class="flex items-center justify-between">
      <h2 class="text-sm font-semibold text-slate-700">Học sinh đã đăng ký ({{ items.length }})</h2>
      <Button @click="goEnroll"><PlusIcon class="h-4 w-4" /> Đăng ký học sinh</Button>
    </div>
    <div v-if="loading" class="py-10 text-center text-sm text-slate-500">Đang tải…</div>
    <div v-else-if="!items.length" class="rounded-xl border border-slate-200 bg-white py-10 text-center text-sm text-slate-500">
      Chưa có học sinh nào.
    </div>
    <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
      <table class="w-full min-w-[40rem] text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-3 py-2 font-medium">Mã</th>
            <th class="px-3 py-2 font-medium">Họ tên</th>
            <th class="px-3 py-2 font-medium">Lớp</th>
            <th class="px-3 py-2 text-right font-medium">Thao tác</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="s in items" :key="s.student_id" class="hover:bg-slate-50/60">
            <td class="px-3 py-2 font-mono text-xs text-slate-500">{{ s.code }}</td>
            <td class="px-3 py-2 font-medium text-slate-900">{{ s.full_name }}</td>
            <td class="px-3 py-2 text-slate-600">{{ s.class_name }}</td>
            <td class="px-3 py-2 text-right">
              <button class="text-xs font-medium text-rose-600 hover:underline" @click="unenroll(s)">Hủy đăng ký</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { PlusIcon } from '@heroicons/vue/24/outline'
import Button from '../../../components/ui/Button.vue'
import { listEnrollments, unenrollStudent } from '../../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../../composables/appMessage'
import { confirmAction } from '../../../composables/useConfirm'

const props = defineProps({ program: { type: Object, required: true } })
const router = useRouter()
const loading = ref(false)
const items = ref([])

async function load() {
  loading.value = true
  try {
    items.value = (await listEnrollments(props.program.id))?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

function goEnroll() {
  router.push({ name: 'tpEnrollStudents', params: { id: props.program.id } })
}

async function unenroll(s) {
  const ok = await confirmAction({ title: 'Hủy đăng ký', message: `Hủy đăng ký ${s.full_name}?`, danger: true, confirmLabel: 'Hủy đăng ký' })
  if (!ok) return
  try {
    await unenrollStudent(props.program.id, s.student_id, 'Hủy bởi quản trị')
    showAppSuccess('Đã hủy đăng ký.')
    load()
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

onMounted(load)
</script>
