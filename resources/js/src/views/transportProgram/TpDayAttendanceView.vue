<template>
  <div class="mx-auto max-w-3xl space-y-4">
    <div>
      <button class="text-xs text-slate-400 hover:text-slate-600" @click="goBack">← Quay lại</button>
      <h1 class="text-lg font-bold tracking-tight text-slate-900 sm:text-xl">Điểm danh ngày</h1>
      <p v-if="data" class="text-sm text-slate-500">
        Sĩ số dự kiến: {{ data.effective_count }} / {{ data.items.length }} học sinh
      </p>
    </div>

    <div v-if="loading" class="py-10 text-center text-sm text-slate-500">Đang tải…</div>
    <div v-else-if="data" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div
        v-for="s in data.items"
        :key="s.student_id"
        class="flex items-center gap-3 border-b border-slate-100 px-3 py-2.5 last:border-0"
      >
        <div class="flex-1">
          <div class="text-sm font-medium text-slate-900">{{ s.full_name }}</div>
          <div class="text-xs text-slate-500">{{ s.code }} · {{ s.class_name || '—' }}</div>
        </div>
        <span
          v-if="s.status === 'absent'"
          class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700"
        >
          Vắng ({{ absenceLabel(s.absence_type) }})
        </span>
        <Button v-if="s.status === 'attending'" variant="secondary" @click="markAbsent(s)">Đánh vắng</Button>
        <Button v-else variant="secondary" @click="unmark(s)">Hủy vắng</Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from '../../components/ui/Button.vue'
import { getDayAttendance, markDayAbsence, unmarkDayAbsence } from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const data = ref(null)

async function load() {
  loading.value = true
  try {
    data.value = await getDayAttendance(route.params.dayId)
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

async function markAbsent(s) {
  try {
    data.value = await markDayAbsence(route.params.dayId, {
      student_ids: [s.student_id],
      absence_type: 'parent_notified',
      absence_reason: null,
    })
    showAppSuccess('Đã đánh vắng.')
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

async function unmark(s) {
  try {
    data.value = await unmarkDayAbsence(route.params.dayId, s.student_id)
    showAppSuccess('Đã hủy vắng.')
  } catch (err) {
    showAppErrorFromApi(err)
  }
}

function absenceLabel(t) {
  return { parent_notified: 'Báo trước', no_notice: 'Không báo', late_cancel: 'Hủy muộn', absent: 'Vắng' }[t] || t
}

function goBack() {
  router.back()
}

onMounted(load)
</script>
