<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
      <label class="block">
        <span class="mb-1 block text-sm font-medium text-slate-600">Từ ngày</span>
        <input v-model="from" type="date" class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-base outline-none ring-va-800/20 focus:ring" />
      </label>
      <label class="block">
        <span class="mb-1 block text-sm font-medium text-slate-600">Đến ngày</span>
        <input v-model="to" type="date" class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-base outline-none ring-va-800/20 focus:ring" />
      </label>
      <Button :loading="loading" @click="load"><ChartBarIcon class="h-4 w-4" /> Xem báo cáo vắng</Button>
    </div>

    <template v-if="report">
      <!-- Summary -->
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-xs uppercase tracking-wide text-slate-400">Số ngày</div>
          <div class="mt-1 text-2xl font-bold text-slate-900">{{ report.columns.length }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-xs uppercase tracking-wide text-slate-400">Học sinh</div>
          <div class="mt-1 text-2xl font-bold text-slate-900">{{ report.rows.length }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="text-xs uppercase tracking-wide text-slate-400">Tổng lượt vắng</div>
          <div class="mt-1 text-2xl font-bold text-rose-600">{{ totalAbsences }}</div>
        </div>
      </div>

      <div v-if="!report.rows.length" class="rounded-2xl border border-slate-200 bg-white py-16 text-center text-sm text-slate-500">
        Không có dữ liệu trong khoảng thời gian này.
      </div>
      <div v-else class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-[40rem] text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="sticky left-0 z-10 bg-slate-50 px-3 py-2.5 font-medium">Học sinh</th>
              <th v-for="d in report.columns" :key="d" class="px-2 py-2.5 text-center font-medium">{{ d.slice(5) }}</th>
              <th class="px-3 py-2.5 text-center font-medium">Tổng</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="row in report.rows" :key="row.student_id" class="hover:bg-slate-50/60">
              <td class="sticky left-0 z-10 bg-white px-3 py-2 font-medium text-slate-900">
                {{ row.full_name }}
                <span class="ml-1 font-mono text-[11px] text-slate-400">{{ row.code }}</span>
              </td>
              <td v-for="d in report.columns" :key="d" class="px-2 py-2 text-center">
                <span
                  v-if="row.cells[d]"
                  class="inline-grid h-6 w-6 place-items-center rounded-md bg-rose-50 text-xs font-bold text-rose-600"
                  :title="row.cells[d]"
                >✕</span>
                <span v-else class="text-slate-200">·</span>
              </td>
              <td class="px-3 py-2 text-center font-bold" :class="rowTotal(row) ? 'text-rose-600' : 'text-slate-300'">
                {{ rowTotal(row) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
    <div v-else class="rounded-2xl border border-dashed border-slate-200 bg-white py-16 text-center text-sm text-slate-400">
      Chọn khoảng ngày và nhấn "Xem báo cáo vắng" để xem bảng điểm danh.
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { ChartBarIcon } from '@heroicons/vue/24/outline'
import Button from '../../../components/ui/Button.vue'
import { getAbsenceReport } from '../../../api/transportProgram'
import { showAppErrorFromApi } from '../../../composables/appMessage'

const props = defineProps({ program: { type: Object, required: true } })
const loading = ref(false)
const report = ref(null)
const today = new Date()
const from = ref(new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0, 10))
const to = ref(today.toISOString().slice(0, 10))

function rowTotal(row) {
  return Object.values(row.cells || {}).filter(Boolean).length
}

const totalAbsences = computed(() => {
  if (!report.value) return 0
  return report.value.rows.reduce((sum, r) => sum + rowTotal(r), 0)
})

async function load() {
  loading.value = true
  try {
    const res = await getAbsenceReport(props.program.id, { from: from.value, to: to.value })
    report.value = { columns: res?.columns ?? [], rows: res?.rows ?? [] }
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}
</script>
