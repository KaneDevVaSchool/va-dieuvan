<template>
  <div class="space-y-3">
    <div class="flex flex-wrap items-end gap-2">
      <Input v-model="from" type="date" label="Từ ngày" />
      <Input v-model="to" type="date" label="Đến ngày" />
      <Button :loading="loading" @click="load">Xem báo cáo vắng</Button>
    </div>
    <div v-if="report" class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
      <table class="w-full min-w-[40rem] text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-3 py-2 font-medium">Học sinh</th>
            <th v-for="d in report.dates" :key="d" class="px-2 py-2 text-center font-medium">{{ d.slice(5) }}</th>
            <th class="px-3 py-2 text-center font-medium">Tổng</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="row in report.rows" :key="row.student_id" class="hover:bg-slate-50/60">
            <td class="px-3 py-2 font-medium text-slate-900">{{ row.full_name }}</td>
            <td v-for="d in report.dates" :key="d" class="px-2 py-2 text-center">
              <span v-if="row.absences[d]" class="text-rose-600">✕</span>
              <span v-else class="text-slate-300">·</span>
            </td>
            <td class="px-3 py-2 text-center font-semibold text-slate-700">{{ row.total }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Input from '../../../components/ui/Input.vue'
import Button from '../../../components/ui/Button.vue'
import { getAbsenceReport } from '../../../api/transportProgram'
import { showAppErrorFromApi } from '../../../composables/appMessage'

const props = defineProps({ program: { type: Object, required: true } })
const loading = ref(false)
const report = ref(null)
const today = new Date()
const from = ref(new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0, 10))
const to = ref(today.toISOString().slice(0, 10))

async function load() {
  loading.value = true
  try {
    report.value = await getAbsenceReport(props.program.id, { from: from.value, to: to.value })
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}
</script>
