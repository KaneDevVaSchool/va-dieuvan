<template>
  <div class="space-y-3">
    <div class="rounded-xl border border-slate-200 bg-white p-4">
      <h2 class="text-sm font-semibold text-slate-700">Mặc định chương trình</h2>
      <p class="mt-1 text-sm text-slate-600">
        Tài xế: <span class="font-medium">{{ program.default_driver?.full_name || '— chưa gán —' }}</span>
      </p>
      <p class="text-sm text-slate-600">
        Xe: <span class="font-medium">{{ program.default_vehicle?.label || '— chưa gán —' }}</span>
      </p>
      <p class="mt-2 text-xs text-slate-400">Gán riêng theo ngày trong màn điểm danh từng ngày (override).</p>
    </div>

    <div v-if="loading" class="py-8 text-center text-sm text-slate-500">Đang tải…</div>
    <div v-else class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
      <table class="w-full min-w-[40rem] text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-3 py-2 font-medium">Ngày</th>
            <th class="px-3 py-2 font-medium">Tài xế hiệu lực</th>
            <th class="px-3 py-2 font-medium">Override?</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="d in days" :key="d.id" class="hover:bg-slate-50/60">
            <td class="px-3 py-2 text-slate-900">{{ d.scheduled_date }}</td>
            <td class="px-3 py-2 text-slate-600">{{ d.effective_driver?.full_name || '—' }}</td>
            <td class="px-3 py-2">
              <span v-if="d.driver_id" class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">Override</span>
              <span v-else class="text-xs text-slate-400">Mặc định</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { listProgramDays } from '../../../api/transportProgram'
import { showAppErrorFromApi } from '../../../composables/appMessage'

const props = defineProps({ program: { type: Object, required: true } })
const loading = ref(false)
const days = ref([])

async function load() {
  loading.value = true
  try {
    const month = new Date().toISOString().slice(0, 7)
    days.value = (await listProgramDays(props.program.id, { month }))?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>
