<template>
  <div class="space-y-3">
    <div class="flex items-center gap-2">
      <Input v-model="month" type="month" label="Tháng" />
    </div>
    <div v-if="loading" class="py-10 text-center text-sm text-slate-500">Đang tải…</div>
    <div v-else class="grid grid-cols-2 gap-2 sm:grid-cols-4 md:grid-cols-7">
      <button
        v-for="d in days"
        :key="d.id"
        :class="['rounded-lg border p-2 text-left text-xs', dayClass(d)]"
        @click="openDay(d)"
      >
        <div class="font-semibold">{{ shortDate(d.scheduled_date) }}</div>
        <div class="text-[11px]">{{ dayTypeLabel(d.day_type) }}</div>
        <div class="text-[11px] text-slate-500">SS: {{ d.expected_count }}</div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import Input from '../../../components/ui/Input.vue'
import { listProgramDays } from '../../../api/transportProgram'
import { showAppErrorFromApi } from '../../../composables/appMessage'

const props = defineProps({ program: { type: Object, required: true } })
const router = useRouter()
const loading = ref(false)
const days = ref([])
const month = ref(new Date().toISOString().slice(0, 7))

async function load() {
  loading.value = true
  try {
    const res = await listProgramDays(props.program.id, { month: month.value })
    days.value = res?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

watch(month, load)

function shortDate(d) {
  return d?.slice(5)
}
function dayTypeLabel(t) {
  return { operating: 'Vận hành', cancelled: 'Đã hủy', makeup: 'Học bù' }[t] || t
}
function dayClass(d) {
  if (d.day_type === 'cancelled') return 'border-rose-200 bg-rose-50 text-rose-700'
  if (d.day_type === 'makeup') return 'border-amber-200 bg-amber-50 text-amber-700'
  return 'border-emerald-200 bg-emerald-50 text-emerald-700'
}
function openDay(d) {
  router.push({ name: 'tpDayAttendance', params: { dayId: d.id } })
}

onMounted(load)
</script>
