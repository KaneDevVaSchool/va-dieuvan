<template>
  <div class="min-h-full w-full max-w-[430px] bg-driver-bg pb-28 text-driver-ink sm:max-w-none">
    <header
      class="sticky top-0 z-[25] flex items-center gap-2 border-b border-white/5 bg-driver-bg/90 px-3 py-3 backdrop-blur-md"
      style="padding-top: max(0.75rem, env(safe-area-inset-top))"
    >
      <div class="min-w-0 flex-1">
        <h1 class="truncate text-lg font-bold tracking-tight">Chuyến đưa đón hôm nay</h1>
        <p class="truncate text-xs text-driver-ink/50">Chương trình P2P</p>
      </div>
      <span v-if="!online" class="rounded-full bg-amber-500/20 px-2 py-0.5 text-xs text-amber-300">Ngoại tuyến</span>
    </header>

    <div class="px-3 pt-3">
      <div v-if="loading" class="py-16 text-center text-sm text-driver-ink/60">Đang tải…</div>
      <div v-else-if="!days.length" class="py-16 text-center text-sm text-driver-ink/60">Không có chuyến nào được giao.</div>
      <div v-else class="space-y-2">
        <button
          v-for="d in days"
          :key="d.day_id"
          type="button"
          class="block w-full rounded-2xl border border-white/8 bg-white/[0.03] p-4 text-left transition active:scale-[0.99]"
          @click="open(d)"
        >
          <div class="flex items-center justify-between">
            <div class="font-semibold">{{ d.program_name }}</div>
            <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(d.execution_status)">{{ statusLabel(d.execution_status) }}</span>
          </div>
          <div class="mt-1 text-xs text-driver-ink/50">{{ d.scheduled_date }} · {{ d.departure_time }} · {{ d.expected_count }} HS</div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { driverListDays } from '../../api/transportProgram'
import { showAppErrorFromApi } from '../../composables/appMessage'

const router = useRouter()
const loading = ref(false)
const days = ref([])
const online = ref(typeof navigator !== 'undefined' ? navigator.onLine : true)

function setOnline() { online.value = navigator.onLine }

async function load() {
  loading.value = true
  try {
    const today = new Date().toISOString().slice(0, 10)
    days.value = (await driverListDays({ date_from: today, date_to: today }))?.items ?? []
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

function open(d) {
  router.push({ name: 'driverTpAttendance', params: { dayId: d.day_id } })
}

function statusLabel(s) {
  return { in_progress: 'Đang chạy', completed: 'Hoàn thành', cancelled: 'Đã hủy' }[s] || 'Chưa bắt đầu'
}
function statusClass(s) {
  return { in_progress: 'bg-[#7fdcc8]/20 text-[#7fdcc8]', completed: 'bg-sky-500/20 text-sky-300', cancelled: 'bg-rose-500/20 text-rose-300' }[s] || 'bg-white/10 text-driver-ink/60'
}

onMounted(() => {
  load()
  window.addEventListener('online', setOnline)
  window.addEventListener('offline', setOnline)
})
onUnmounted(() => {
  window.removeEventListener('online', setOnline)
  window.removeEventListener('offline', setOnline)
})
</script>
