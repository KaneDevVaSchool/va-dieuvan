<template>
  <div class="space-y-3">
    <div class="flex items-center gap-2">
      <Input v-model="month" type="month" label="Tháng" />
    </div>
    <div v-if="loading" class="py-10 text-center text-sm text-slate-500">Đang tải…</div>
    <div v-else-if="report" class="space-y-3">
      <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <div class="text-xs uppercase tracking-wide text-slate-400">Số chuyến</div>
          <div class="mt-1 text-2xl font-bold text-slate-900">{{ report.trip_count }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <div class="text-xs uppercase tracking-wide text-slate-400">Chi phí dự kiến</div>
          <div class="mt-1 text-2xl font-bold text-slate-900">{{ money(report.planned_cost) }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
          <div class="text-xs uppercase tracking-wide text-slate-400">Chi phí thực tế</div>
          <div class="mt-1 text-2xl font-bold text-slate-900">{{ money(report.actual_cost) }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import Input from '../../../components/ui/Input.vue'
import { getCostReport } from '../../../api/transportProgram'
import { showAppErrorFromApi } from '../../../composables/appMessage'

const props = defineProps({ program: { type: Object, required: true } })
const loading = ref(false)
const report = ref(null)
const month = ref(new Date().toISOString().slice(0, 7))

async function load() {
  loading.value = true
  try {
    report.value = await getCostReport(props.program.id, { month: month.value })
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

watch(month, load)

function money(v) {
  return new Intl.NumberFormat('vi-VN').format(Number(v || 0)) + ' đ'
}

onMounted(load)
</script>
