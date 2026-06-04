<template>
  <div class="min-h-full w-full max-w-[430px] bg-driver-bg pb-28 text-driver-ink sm:max-w-none">
    <header
      class="sticky top-0 z-[25] flex items-center gap-2 border-b border-white/5 bg-driver-bg/90 px-3 py-3 backdrop-blur-md"
      style="padding-top: max(0.75rem, env(safe-area-inset-top))"
    >
      <RouterLink to="/driver/tp-days" class="flex h-10 w-10 items-center justify-center rounded-full text-[#7fdcc8] transition hover:bg-white/5 active:scale-95">
        <span class="sr-only">Quay lại</span>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
      </RouterLink>
      <div class="min-w-0 flex-1">
        <h1 class="truncate text-lg font-bold tracking-tight">{{ day?.program?.name || day?.program_id || 'Điểm danh' }}</h1>
        <p v-if="day" class="truncate text-xs text-driver-ink/50">{{ day.scheduled_date }}</p>
      </div>
      <span v-if="!online" class="rounded-full bg-amber-500/20 px-2 py-0.5 text-xs text-amber-300">Ngoại tuyến · {{ pendingCount }}</span>
    </header>

    <div class="px-3 pt-3">
      <div v-if="loading" class="py-16 text-center text-sm text-driver-ink/60">Đang tải…</div>

      <template v-else-if="day">
        <div v-if="execution" class="mb-3 flex items-center justify-between rounded-2xl border border-white/8 bg-white/[0.03] px-4 py-3 text-sm">
          <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="execStatusClass(execution.status)">{{ execStatusLabel(execution.status) }}</span>
          <span><b class="text-[#7fdcc8]">{{ execution.total_boarded }}</b>/{{ execution.total_expected }} · <span class="text-rose-300">{{ execution.total_absent }} vắng</span></span>
        </div>

        <button
          v-if="!execution"
          type="button"
          class="w-full rounded-2xl bg-[#7fdcc8] py-3.5 text-center text-base font-semibold text-driver-bg transition active:scale-[0.99] disabled:opacity-50"
          :disabled="busy"
          @click="start"
        >
          Bắt đầu chuyến
        </button>

        <div v-if="execution && execution.status === 'in_progress'" class="space-y-2">
          <div v-for="s in execution.student_logs" :key="s.student_id" class="rounded-2xl border border-white/8 bg-white/[0.03] p-3">
            <div class="flex items-center justify-between gap-2">
              <div class="min-w-0">
                <div class="truncate font-medium">{{ s.full_name }}</div>
                <div class="text-xs text-driver-ink/50">{{ s.code }}</div>
              </div>
              <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium" :class="logClass(s.final_status)">{{ logLabel(s.final_status) }}</span>
            </div>
            <div class="mt-2 flex gap-2">
              <button
                v-if="s.final_status !== 'boarded' && s.final_status !== 'completed' && s.final_status !== 'absent'"
                class="flex-1 rounded-xl bg-[#7fdcc8]/15 py-2 text-sm font-medium text-[#7fdcc8] active:scale-95"
                @click="act('board', s)"
              >
                Lên xe
              </button>
              <button
                v-if="s.final_status === 'boarded'"
                class="flex-1 rounded-xl bg-sky-500/15 py-2 text-sm font-medium text-sky-300 active:scale-95"
                @click="act('alight', s)"
              >
                Xuống xe
              </button>
              <button
                v-if="s.final_status !== 'absent'"
                class="rounded-xl bg-rose-500/15 px-3 py-2 text-sm font-medium text-rose-300 active:scale-95"
                @click="act('absent', s)"
              >
                Vắng
              </button>
              <button
                v-else
                class="rounded-xl bg-white/10 px-3 py-2 text-sm font-medium text-driver-ink/70 active:scale-95"
                @click="act('undo-absent', s)"
              >
                Hủy vắng
              </button>
            </div>
          </div>

          <button
            type="button"
            class="mt-4 w-full rounded-2xl bg-sky-500 py-3.5 text-center text-base font-semibold text-white transition active:scale-[0.99] disabled:opacity-50"
            :disabled="busy"
            @click="complete"
          >
            Hoàn thành chuyến
          </button>
        </div>

        <div v-else-if="execution && execution.status === 'completed'" class="rounded-2xl border border-sky-400/30 bg-sky-500/10 px-4 py-6 text-center text-sm text-sky-200">
          Chuyến đã hoàn thành.
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  driverGetDay,
  driverStartTrip,
  driverCompleteTrip,
  driverBoard,
  driverAlight,
  driverAbsent,
  driverUndoAbsent,
} from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const route = useRoute()
const loading = ref(false)
const busy = ref(false)
const day = ref(null)
const execution = ref(null)
const online = ref(typeof navigator !== 'undefined' ? navigator.onLine : true)
const pendingCount = ref(0)

function setOnline() { online.value = navigator.onLine }

async function load() {
  loading.value = true
  try {
    day.value = await driverGetDay(route.params.dayId)
    execution.value = day.value?.execution ?? null
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

async function start() {
  busy.value = true
  try {
    execution.value = await driverStartTrip(route.params.dayId, deviceId())
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busy.value = false
  }
}

async function complete() {
  busy.value = true
  try {
    execution.value = await driverCompleteTrip(execution.value.id, true)
    showAppSuccess('Đã hoàn thành chuyến.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busy.value = false
  }
}

async function act(type, s) {
  const ts = new Date().toISOString()
  // Optimistic local update — khi offline, request tự được hàng đợi (outbox).
  applyLocal(type, s)
  try {
    const execId = execution.value.id
    if (type === 'board') await driverBoard(execId, s.student_id, ts)
    else if (type === 'alight') await driverAlight(execId, s.student_id, ts)
    else if (type === 'absent') await driverAbsent(execId, s.student_id, { absence_type: 'no_notice', client_timestamp: ts })
    else if (type === 'undo-absent') await driverUndoAbsent(execId, s.student_id)
    // Đồng bộ lại tổng số khi online thành công
    if (online.value) await refreshTotals()
  } catch (err) {
    if (online.value) {
      showAppErrorFromApi(err)
      await load()
    } else {
      pendingCount.value++
    }
  }
}

function applyLocal(type, s) {
  const log = execution.value.student_logs.find((l) => l.student_id === s.student_id)
  if (!log) return
  const map = { board: 'boarded', alight: 'completed', absent: 'absent', 'undo-absent': 'pending' }
  log.final_status = map[type] ?? log.final_status
}

async function refreshTotals() {
  try {
    const fresh = await driverGetDay(route.params.dayId)
    if (fresh?.execution) execution.value = fresh.execution
  } catch { /* ignore */ }
}

function deviceId() {
  let id = localStorage.getItem('tp_device_id')
  if (!id) {
    id = 'dev-' + Math.random().toString(36).slice(2, 10)
    localStorage.setItem('tp_device_id', id)
  }
  return id
}

function execStatusLabel(s) { return { in_progress: 'Đang chạy', completed: 'Hoàn thành' }[s] || s }
function execStatusClass(s) { return { in_progress: 'bg-[#7fdcc8]/20 text-[#7fdcc8]', completed: 'bg-sky-500/20 text-sky-300' }[s] || 'bg-white/10' }
function logLabel(s) { return { pending: 'Chờ', boarded: 'Đã lên', completed: 'Đã xuống', absent: 'Vắng' }[s] || s }
function logClass(s) { return { pending: 'bg-white/10 text-driver-ink/60', boarded: 'bg-[#7fdcc8]/20 text-[#7fdcc8]', completed: 'bg-sky-500/20 text-sky-300', absent: 'bg-rose-500/20 text-rose-300' }[s] || 'bg-white/10' }

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
