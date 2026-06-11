<template>
  <div class="mx-auto min-h-full w-full max-w-[430px] overflow-x-hidden bg-driver-bg pb-28 text-driver-ink sm:max-w-2xl">
    <!-- Header -->
    <header
      class="sticky top-0 z-[25] flex items-center gap-3 border-b border-white/5 bg-driver-bg/90 px-4 py-3 backdrop-blur-md [-webkit-backdrop-filter:blur(12px)]"
      style="padding-top: max(0.75rem, env(safe-area-inset-top))"
    >
      <button
        type="button"
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-driver-accent/12 text-driver-accent transition active:scale-95"
        :aria-label="t('trip_history_page.back')"
        data-testid="driver-tp-attendance-back"
        @click="goBack"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" class="h-5 w-5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
      </button>

      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
          <h1 class="truncate text-xl font-bold leading-tight tracking-tight">
            {{ day?.program_name || 'Điểm danh' }}
          </h1>
          <!-- Hiển thị badge ca khi có multi-slot (tuyến con) -->
          <span
            v-if="shift === 'morning'"
            class="shrink-0 rounded-md bg-amber-500/20 px-2 py-0.5 text-xs font-bold text-amber-300"
          >Sáng</span>
          <span
            v-else-if="shift === 'afternoon'"
            class="shrink-0 rounded-md bg-violet-500/20 px-2 py-0.5 text-xs font-bold text-violet-300"
          >Chiều</span>
        </div>
        <p v-if="day" class="mt-0.5 truncate text-sm text-driver-muted">
          {{ formattedDate }}
          <span v-if="day.program_departure_time" class="mx-1 text-driver-accent/50">·</span>
          <span v-if="day.program_departure_time" class="text-driver-accent">{{ day.program_departure_time }}</span>
        </p>
      </div>

      <!-- Offline badge -->
      <span
        v-if="!online"
        class="shrink-0 rounded-full bg-amber-500/20 px-2.5 py-1 text-xs font-semibold text-amber-300"
      >
        Ngoại tuyến{{ pendingCount ? ` · ${pendingCount}` : '' }}
      </span>
    </header>

    <div class="px-4 pt-4">
      <!-- Loading skeleton -->
      <div v-if="loading" class="space-y-3 py-6">
        <div class="h-5 w-3/5 animate-pulse rounded-lg bg-driver-accent/10" />
        <div class="h-4 w-2/5 animate-pulse rounded-lg bg-driver-accent/8" />
        <div class="mt-4 h-24 animate-pulse rounded-2xl bg-driver-surface" />
      </div>

      <template v-else-if="day">
        <!-- Info card: route + vehicle -->
        <div
          v-if="day.program_origin || day.program_destination || day.effective_vehicle"
          class="mb-4 rounded-2xl border border-white/8 bg-white/[0.03] px-4 py-3.5"
        >
          <!-- Route row -->
          <div v-if="day.program_origin || day.program_destination" class="flex items-start gap-2.5">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 h-4 w-4 shrink-0 text-driver-accent/60" aria-hidden="true">
              <path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 0 0 .912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 0 0 .19-.153 15.588 15.588 0 0 0 2.046-2.082c1.101-1.364 2.291-3.458 2.291-6.097a5 5 0 0 0-10 0c0 2.639 1.19 4.733 2.291 6.097a15.588 15.588 0 0 0 2.046 2.082 8.916 8.916 0 0 0 .19.153l.012.01ZM8 8.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" clip-rule="evenodd" />
            </svg>
            <div class="min-w-0 flex-1 text-sm">
              <span v-if="day.program_origin" class="font-medium text-driver-ink">{{ day.program_origin }}</span>
              <span v-if="day.program_origin && day.program_destination" class="mx-1 text-driver-muted">→</span>
              <span v-if="day.program_destination" class="font-medium text-driver-ink">{{ day.program_destination }}</span>
            </div>
          </div>

          <!-- Vehicle row -->
          <div
            v-if="day.effective_vehicle"
            class="mt-2.5 flex items-center gap-2.5"
            :class="{ 'border-t border-white/6 pt-2.5': day.program_origin || day.program_destination }"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-4 w-4 shrink-0 text-driver-accent/60" aria-hidden="true">
              <path d="M6.5 8.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0ZM12.5 8.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
              <path fill-rule="evenodd" d="M1.246 4.421A.75.75 0 0 1 2 4h12a.75.75 0 0 1 .75.75v.573c0 .296-.181.562-.457.671L13.5 6.5v.5h.75a.75.75 0 0 1 0 1.5H13.5v1.75a.75.75 0 0 1-.75.75H11.5a.75.75 0 0 1-.75-.75V10h-5v.25a.75.75 0 0 1-.75.75H3.25a.75.75 0 0 1-.75-.75V8.5H1.75a.75.75 0 0 1 0-1.5H2.5V6.5L1.707 5.994A.75.75 0 0 1 1.246 4.421ZM3.5 6.5v.5h9V6.5L11 5.5H5L3.5 6.5Z" clip-rule="evenodd" />
            </svg>
            <span class="text-sm font-medium text-driver-ink">{{ day.effective_vehicle.license_plate }}</span>
          </div>
        </div>

        <!-- Execution summary bar -->
        <div v-if="execution" class="mb-4 flex items-center justify-between rounded-2xl border border-white/8 bg-white/[0.03] px-4 py-3">
          <span class="rounded-lg px-3 py-1 text-sm font-semibold" :class="execStatusClass(execution.status)">
            {{ execStatusLabel(execution.status) }}
          </span>
          <div class="flex items-center gap-3 text-sm">
            <span class="flex items-center gap-1">
              <b class="text-lg font-extrabold tabular-nums text-driver-accent">{{ execution.total_boarded }}</b>
              <span class="text-driver-muted">/{{ execution.total_expected }}</span>
            </span>
            <span v-if="execution.total_absent" class="rounded-lg bg-rose-500/15 px-2.5 py-0.5 text-sm font-semibold text-rose-300">
              {{ execution.total_absent }} vắng
            </span>
          </div>
        </div>

        <!-- Bước 1: tài xế xác nhận sẽ chạy chuyến (kiểm tra theo ca nếu có) -->
        <div v-if="!execution && !slotConfirmedAt" class="space-y-3">
          <p class="text-base text-driver-ink/70">Xác nhận bạn sẽ chạy chuyến này. Sau khi xác nhận mới có thể bắt đầu chuyến.</p>
          <button
            type="button"
            class="w-full rounded-2xl bg-driver-accent py-4 text-center text-lg font-bold text-driver-bg transition active:scale-[0.99] disabled:opacity-50"
            :disabled="busy"
            @click="confirm"
          >
            Xác nhận chuyến
          </button>
        </div>

        <!-- Bước 2: đã xác nhận → bắt đầu chuyến -->
        <div v-else-if="!execution" class="space-y-3">
          <div class="flex items-center justify-between rounded-2xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3">
            <div class="flex items-center gap-2 text-base font-medium text-emerald-200">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-4 w-4 shrink-0" aria-hidden="true">
                <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
              </svg>
              Đã xác nhận chuyến
            </div>
            <button type="button" class="text-sm text-emerald-200/60 underline disabled:opacity-50" :disabled="busy" @click="unconfirm">Bỏ</button>
          </div>
          <button
            v-if="!blockingInProgressShift"
            type="button"
            class="w-full rounded-2xl bg-driver-accent py-4 text-center text-lg font-bold text-driver-bg transition active:scale-[0.99] disabled:opacity-50"
            :disabled="busy"
            data-testid="driver-tp-start-trip"
            @click="start"
          >
            Bắt đầu chuyến
          </button>
          <div
            v-else
            class="space-y-3 rounded-2xl border border-amber-400/30 bg-amber-500/10 px-4 py-4"
            data-testid="driver-tp-blocking-shift-banner"
          >
            <p class="text-base text-amber-100/90">
              {{ t('driver_tp_attendance.blocking_shift_message', { shift: blockingShiftLabel }) }}
            </p>
            <button
              type="button"
              class="w-full rounded-2xl bg-amber-500/20 py-3.5 text-center text-base font-bold text-amber-100 ring-1 ring-amber-400/30 active:scale-[0.99]"
              data-testid="driver-tp-open-blocking-shift"
              @click="openBlockingShift"
            >
              {{ t('driver_tp_attendance.blocking_shift_open', { shift: blockingShiftLabel }) }}
            </button>
          </div>
        </div>

        <!-- Đang chạy: danh sách học sinh -->
        <div v-if="execution && execution.status === 'in_progress'" class="space-y-2">
          <div
            v-for="s in execution.student_logs"
            :key="s.student_id"
            class="rounded-2xl border border-white/8 bg-white/[0.03] px-3 py-3 sm:px-4"
            :class="{ 'ring-1 ring-driver-accent/25': actingStudentId === s.student_id }"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0 flex-1">
                <div class="truncate text-[15px] font-semibold leading-snug sm:text-base">{{ s.full_name }}</div>
                <div class="mt-0.5 text-xs text-driver-muted sm:text-sm">{{ s.code }}</div>
              </div>
              <span
                class="shrink-0 rounded-lg px-2.5 py-1 text-xs font-semibold sm:px-3 sm:text-sm"
                :class="logClass(s.final_status)"
              >
                {{ logLabel(s.final_status) }}
              </span>
            </div>

            <p
              v-if="s.driver_notes && expandedNoteStudentId !== s.student_id"
              class="mt-2 line-clamp-2 text-xs text-amber-200/90"
            >
              {{ s.driver_notes }}
            </p>

            <div class="mt-2.5 flex gap-1.5 sm:gap-2">
              <button
                v-if="canBoardStudent(s.final_status)"
                type="button"
                class="flex min-h-11 flex-1 items-center justify-center rounded-xl bg-driver-accent/15 text-sm font-semibold text-driver-accent active:scale-[0.98] disabled:opacity-50 sm:text-base"
                :disabled="isStudentBusy(s.student_id)"
                :data-testid="`driver-tp-board-${s.student_id}`"
                @click="act('board', s)"
              >
                Lên xe
              </button>
              <button
                v-if="s.final_status === 'boarded'"
                type="button"
                class="flex min-h-11 flex-1 items-center justify-center rounded-xl bg-sky-500/15 text-sm font-semibold text-sky-300 active:scale-[0.98] disabled:opacity-50 sm:text-base"
                :disabled="isStudentBusy(s.student_id)"
                :data-testid="`driver-tp-alight-${s.student_id}`"
                @click="act('alight', s)"
              >
                Xuống xe
              </button>
              <button
                v-if="s.final_status !== 'absent'"
                type="button"
                class="flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-rose-500/15 px-3 text-sm font-semibold text-rose-300 active:scale-[0.98] disabled:opacity-50 sm:px-4 sm:text-base"
                :disabled="isStudentBusy(s.student_id)"
                :data-testid="`driver-tp-absent-${s.student_id}`"
                @click="act('absent', s)"
              >
                Vắng
              </button>
              <button
                v-else
                type="button"
                class="flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-white/10 px-3 text-sm font-semibold text-driver-ink/60 active:scale-[0.98] disabled:opacity-50 sm:px-4 sm:text-base"
                :disabled="isStudentBusy(s.student_id)"
                :data-testid="`driver-tp-undo-absent-${s.student_id}`"
                @click="act('undo-absent', s)"
              >
                Hủy vắng
              </button>
            </div>

            <button
              type="button"
              class="mt-2 flex w-full min-h-9 items-center justify-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold transition active:scale-[0.99]"
              :class="
                expandedNoteStudentId === s.student_id || s.driver_notes
                  ? 'bg-amber-500/12 text-amber-200 ring-1 ring-amber-400/20'
                  : 'text-driver-muted ring-1 ring-white/8'
              "
              :aria-label="t('driver_tp_attendance.note_btn_aria', { name: s.full_name })"
              :aria-expanded="expandedNoteStudentId === s.student_id"
              data-testid="driver-tp-student-note-toggle"
              @click="toggleNotePanel(s)"
            >
              {{ s.driver_notes ? t('driver_tp_attendance.note_btn_edit') : t('driver_tp_attendance.note_btn') }}
            </button>

            <div
              v-if="expandedNoteStudentId === s.student_id"
              class="mt-2 space-y-2 border-t border-white/6 pt-2"
              :data-testid="`driver-tp-note-panel-${s.student_id}`"
            >
              <textarea
                v-model="noteDraft"
                rows="2"
                maxlength="500"
                class="w-full resize-none rounded-xl border border-white/10 bg-driver-surface px-3 py-2 text-sm text-driver-ink placeholder:text-driver-muted/70 focus:border-driver-accent/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/25 sm:text-base"
                :placeholder="t('driver_tp_attendance.note_placeholder')"
                data-testid="driver-tp-note-input"
              />
              <p class="text-right text-[11px] tabular-nums text-driver-muted">{{ noteDraft.length }}/500</p>
              <div class="flex gap-2">
                <button
                  type="button"
                  class="flex min-h-10 flex-1 items-center justify-center rounded-xl bg-white/10 text-sm font-semibold text-driver-ink active:scale-[0.99]"
                  data-testid="driver-tp-note-cancel"
                  @click="closeNotePanel"
                >
                  {{ t('driver_tp_attendance.note_cancel') }}
                </button>
                <button
                  type="button"
                  class="flex min-h-10 flex-1 items-center justify-center rounded-xl bg-driver-accent text-sm font-bold text-driver-bg disabled:opacity-50 active:scale-[0.99]"
                  :disabled="noteSaving"
                  data-testid="driver-tp-note-save"
                  @click="saveNote(s)"
                >
                  {{ t('driver_tp_attendance.note_save') }}
                </button>
              </div>
            </div>
          </div>

          <button
            type="button"
            class="mb-[max(0.5rem,env(safe-area-inset-bottom))] mt-3 w-full rounded-2xl bg-sky-500 py-3.5 text-center text-base font-bold text-white transition active:scale-[0.99] disabled:opacity-50 sm:py-4 sm:text-lg"
            data-testid="driver-tp-complete-trip"
            :disabled="busy"
            @click="complete"
          >
            Hoàn thành chuyến
          </button>
        </div>

        <!-- Hoàn thành: tóm tắt -->
        <div v-else-if="execution && execution.status === 'completed'" class="space-y-3">
          <div class="rounded-2xl border border-emerald-400/25 bg-emerald-500/10 px-4 py-5">
            <div class="mb-1 flex items-center gap-2 text-base font-bold text-emerald-300">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-5 w-5 shrink-0" aria-hidden="true">
                <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
              </svg>
              Chuyến đã hoàn thành
            </div>
            <p v-if="execution.completed_at" class="mt-0.5 text-sm text-emerald-200/70">
              {{ formatTime(execution.completed_at) }}
            </p>
          </div>

          <!-- Student breakdown for completed trip -->
          <div v-if="execution.student_logs?.length" class="rounded-2xl border border-white/8 bg-white/[0.03]">
            <div class="border-b border-white/6 px-4 py-3">
              <p class="text-sm font-semibold text-driver-muted uppercase tracking-wide">Danh sách học sinh</p>
            </div>
            <div class="divide-y divide-white/5">
              <div
                v-for="s in execution.student_logs"
                :key="s.student_id"
                class="flex items-center justify-between gap-3 px-4 py-3"
              >
                <div class="min-w-0">
                  <div class="truncate text-base font-medium">{{ s.full_name }}</div>
                  <div class="mt-0.5 text-sm text-driver-muted">{{ s.code }}</div>
                  <p v-if="s.driver_notes" class="mt-1 line-clamp-2 text-xs text-amber-200/80">
                    {{ s.driver_notes }}
                  </p>
                </div>
                <span class="shrink-0 rounded-lg px-3 py-1 text-sm font-semibold" :class="logClass(s.final_status)">
                  {{ logLabel(s.final_status) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import {
  driverGetDay,
  driverConfirmDay,
  driverUnconfirmDay,
  driverStartTrip,
  driverCompleteTrip,
  driverBoard,
  driverAlight,
  driverAbsent,
  driverUndoAbsent,
  driverUpdateStudentNotes,
} from '../../api/transportProgram'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const loading = ref(false)
const busy = ref(false)
const day = ref(null)
const execution = ref(null)
const online = ref(typeof navigator !== 'undefined' ? navigator.onLine : true)
const pendingCount = ref(0)
const expandedNoteStudentId = ref(null)
const noteDraft = ref('')
const noteSaving = ref(false)
const actingStudentId = ref(null)

// Ca của tuyến con mà tài xế đang xem (morning | afternoon | null cho single-slot).
const shift = computed(() => {
  const s = route.query.shift
  return s === 'morning' || s === 'afternoon' ? s : null
})

// Trạng thái xác nhận riêng của ca này (tuyến con).
const slotConfirmedAt = computed(() => {
  if (!day.value) return null
  if (shift.value === 'morning') return day.value.morning_confirmed_at ?? day.value.confirmed_at
  if (shift.value === 'afternoon') return day.value.afternoon_confirmed_at ?? day.value.confirmed_at
  return day.value.confirmed_at
})

const blockingInProgressShift = computed(() => {
  const s = day.value?.blocking_in_progress_shift
  return s === 'morning' || s === 'afternoon' ? s : null
})

const blockingShiftLabel = computed(() => {
  if (blockingInProgressShift.value === 'morning') return t('driver_home.shift_morning')
  if (blockingInProgressShift.value === 'afternoon') return t('driver_home.shift_afternoon')
  return ''
})

function openBlockingShift() {
  if (!blockingInProgressShift.value) return
  router.push({
    name: 'driverTpAttendance',
    params: { dayId: route.params.dayId },
    query: { shift: blockingInProgressShift.value, from: route.query.from ?? 'list' },
  })
}

function setOnline() { online.value = navigator.onLine }

const formattedDate = computed(() => {
  if (!day.value?.scheduled_date) return ''
  return new Date(day.value.scheduled_date + 'T00:00:00').toLocaleDateString('vi-VN', {
    weekday: 'long',
    day: 'numeric',
    month: 'numeric',
    year: 'numeric',
  })
})

async function load() {
  loading.value = true
  try {
    day.value = await driverGetDay(route.params.dayId, shift.value)
    execution.value = day.value?.execution ?? null
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    loading.value = false
  }
}

async function confirm() {
  busy.value = true
  try {
    const result = await driverConfirmDay(route.params.dayId, shift.value)
    day.value = result
    showAppSuccess('Đã xác nhận chuyến.')
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busy.value = false
  }
}

async function unconfirm() {
  busy.value = true
  try {
    const result = await driverUnconfirmDay(route.params.dayId, shift.value)
    day.value = result
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    busy.value = false
  }
}

async function start() {
  busy.value = true
  try {
    execution.value = await driverStartTrip(route.params.dayId, deviceId(), shift.value)
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

function isStudentBusy(studentId) {
  return actingStudentId.value === studentId
}

async function act(type, s) {
  if (actingStudentId.value != null) return
  if (type === 'board' && !canBoardStudent(s.final_status)) return
  if (type === 'alight' && s.final_status !== 'boarded') return

  const log = execution.value?.student_logs?.find((l) => l.student_id === s.student_id)
  const prevStatus = log?.final_status

  actingStudentId.value = s.student_id
  const ts = new Date().toISOString()
  applyLocal(type, s)
  try {
    const execId = execution.value.id
    if (type === 'board') await driverBoard(execId, s.student_id, ts)
    else if (type === 'alight') await driverAlight(execId, s.student_id, ts)
    else if (type === 'absent') await driverAbsent(execId, s.student_id, { absence_type: 'no_notice', client_timestamp: ts })
    else if (type === 'undo-absent') await driverUndoAbsent(execId, s.student_id)
    if (online.value) await refreshTotals()
  } catch (err) {
    if (online.value) {
      if (log && prevStatus != null) log.final_status = prevStatus
      showAppErrorFromApi(err)
    } else {
      pendingCount.value++
    }
  } finally {
    actingStudentId.value = null
  }
}

function applyLocal(type, s) {
  const log = execution.value.student_logs.find((l) => l.student_id === s.student_id)
  if (!log) return
  const map = { board: 'boarded', alight: 'alighted', absent: 'absent', 'undo-absent': 'pending' }
  log.final_status = map[type] ?? log.final_status
}

/** API dùng `alighted`; UI cũ dùng `completed` — chuẩn hoá để không hiện nút Lên xe nhầm. */
function isStudentAlighted(status) {
  return status === 'alighted' || status === 'completed'
}

function canBoardStudent(status) {
  return status === 'pending' || status === 'absent'
}

async function refreshTotals() {
  try {
    const fresh = await driverGetDay(route.params.dayId, shift.value)
    const ex = fresh?.execution
    if (!ex || !execution.value) return
    execution.value.total_boarded = ex.total_boarded
    execution.value.total_absent = ex.total_absent
    execution.value.total_expected = ex.total_expected
  } catch { /* ignore */ }
}

function goBack() {
  if (typeof window !== 'undefined' && window.history.length > 1) {
    router.back()
  } else {
    router.push({ name: 'driverTpDays' })
  }
}

function toggleNotePanel(s) {
  if (expandedNoteStudentId.value === s.student_id) {
    closeNotePanel()
    return
  }
  expandedNoteStudentId.value = s.student_id
  noteDraft.value = s.driver_notes || ''
}

function closeNotePanel() {
  expandedNoteStudentId.value = null
  noteDraft.value = ''
}

async function saveNote(s) {
  if (!execution.value?.id || noteSaving.value) return
  noteSaving.value = true
  try {
    const payload = await driverUpdateStudentNotes(
      execution.value.id,
      s.student_id,
      noteDraft.value.trim() || null,
    )
    const log = execution.value.student_logs.find((l) => l.student_id === s.student_id)
    if (log) {
      log.driver_notes = payload.driver_notes ?? (noteDraft.value.trim() || null)
    }
    closeNotePanel()
  } catch (err) {
    showAppErrorFromApi(err)
  } finally {
    noteSaving.value = false
  }
}

function deviceId() {
  let id = localStorage.getItem('tp_device_id')
  if (!id) {
    id = 'dev-' + Math.random().toString(36).slice(2, 10)
    localStorage.setItem('tp_device_id', id)
  }
  return id
}

function formatTime(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
}

function execStatusLabel(s) { return { in_progress: 'Đang chạy', completed: 'Hoàn thành' }[s] || s }
function execStatusClass(s) {
  return {
    in_progress: 'bg-sky-500/20 text-sky-200',
    completed: 'bg-emerald-500/20 text-emerald-200',
  }[s] || 'bg-white/10 text-driver-muted'
}
function logLabel(s) {
  if (isStudentAlighted(s)) return 'Đã xuống'
  return { pending: 'Chờ', boarded: 'Đã lên', absent: 'Vắng' }[s] || s
}
function logClass(s) {
  if (isStudentAlighted(s)) return 'bg-sky-500/20 text-sky-300'
  return {
    pending: 'bg-white/10 text-driver-ink/60',
    boarded: 'bg-driver-accent/20 text-driver-accent',
    absent: 'bg-rose-500/20 text-rose-300',
  }[s] || 'bg-white/10'
}

onMounted(() => {
  load()
  window.addEventListener('online', setOnline)
  window.addEventListener('offline', setOnline)
})
watch(
  () => [route.params.dayId, route.query.shift],
  () => {
    void load()
  },
)
onUnmounted(() => {
  window.removeEventListener('online', setOnline)
  window.removeEventListener('offline', setOnline)
})
</script>
