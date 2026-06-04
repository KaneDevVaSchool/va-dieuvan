<template>
  <div class="space-y-3">
    <p class="text-sm text-slate-500">Chọn một ngày trong tab "Lịch" để điểm danh, hoặc chọn nhanh ngày gần nhất bên dưới.</p>
    <div v-if="loading" class="py-10 text-center text-sm text-slate-500">Đang tải…</div>
    <div v-else class="grid gap-2 sm:grid-cols-3 md:grid-cols-5">
      <button
        v-for="d in upcoming"
        :key="d.id"
        class="rounded-lg border border-slate-200 bg-white p-3 text-left text-sm hover:border-va-800"
        @click="openDay(d)"
      >
        <div class="font-semibold text-slate-900">{{ d.scheduled_date }}</div>
        <div class="text-xs text-slate-500">Sĩ số: {{ d.expected_count }}</div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { listProgramDays } from '../../../api/transportProgram'
import { showAppErrorFromApi } from '../../../composables/appMessage'

const props = defineProps({ program: { type: Object, required: true } })
const router = useRouter()
const loading = ref(false)
const upcoming = ref([])

async function load() {
  loading.value = true
  try {
    const month = new Date().toISOString().slice(0, 7)
    const res = await listProgramDays(props.program.id, { month })
    upcoming.value = (res?.items ?? []).filter((d) => d.day_type === 'operating').slice(0, 15)
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

function openDay(d) {
  router.push({ name: 'tpDayAttendance', params: { dayId: d.id } })
}

onMounted(load)
</script>
