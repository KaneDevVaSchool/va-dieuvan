<template>
  <div class="min-h-full w-full max-w-[430px] bg-driver-bg pb-28 text-driver-ink sm:max-w-none">
    <header
      class="sticky top-0 z-[25] flex items-center gap-2 border-b border-white/5 bg-driver-bg/90 px-3 py-3 backdrop-blur-md"
      style="padding-top: max(0.75rem, env(safe-area-inset-top))"
    >
      <RouterLink to="/driver/policy-trips" class="flex h-10 w-10 items-center justify-center rounded-full text-[#7fdcc8] transition hover:bg-white/5 active:scale-95">
        <span class="sr-only">{{ t('driver_policy.back') }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
      </RouterLink>
      <div class="min-w-0 flex-1">
        <h1 class="truncate text-lg font-bold tracking-tight">{{ trip?.route_name || t('driver_policy.title') }}</h1>
        <p v-if="trip" class="truncate text-xs text-driver-ink/50">{{ labelTimeSlot(trip.time_slot) }} · {{ trip.planned_departure }}</p>
      </div>
    </header>

    <div class="px-3 pt-3">
      <div v-if="loading" class="py-16 text-center text-sm text-driver-ink/60">{{ t('driver_policy.loading') }}</div>

      <template v-else-if="trip">
        <!-- Tóm tắt -->
        <div class="mb-3 flex items-center justify-between rounded-2xl border border-white/8 bg-white/[0.03] px-4 py-3 text-sm">
          <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(trip.status)">{{ labelPolicyTripStatus(trip.status) }}</span>
          <span><b class="text-[#7fdcc8]">{{ trip.boarded_count }}</b>/{{ trip.expected_count }} · <span class="text-rose-300">{{ trip.absent_count }} {{ t('driver_policy.absent') }}</span></span>
        </div>

        <p v-if="error" class="mb-3 rounded-xl border border-rose-400/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-200">{{ error }}</p>

        <!-- Bắt đầu chuyến -->
        <button
          v-if="trip.status === 'assigned'"
          type="button"
          class="w-full rounded-2xl bg-[#7fdcc8] py-3.5 text-center text-base font-semibold text-driver-bg transition active:scale-[0.99] disabled:opacity-50"
          :disabled="busy"
          @click="start"
        >
          {{ t('driver_policy.start_trip') }}
        </button>

        <!-- Danh sách điểm danh -->
        <div v-if="trip.status === 'in_progress' || trip.status === 'completed'" class="space-y-2">
          <div v-for="s in students" :key="s.id" class="rounded-2xl border border-white/8 bg-white/[0.03] p-3">
            <div class="flex items-center justify-between gap-2">
              <div class="min-w-0">
                <div class="truncate font-medium">{{ s.student_name }}</div>
                <div class="text-xs text-driver-ink/50">{{ s.class_name || s.student_code }}</div>
              </div>
              <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium" :class="studentBadgeClass(s)">{{ studentLabel(s) }}</span>
            </div>

            <!-- Hành động khi đang chạy -->
            <div v-if="trip.status === 'in_progress'" class="mt-3">
              <div v-if="!s.absence_reason && !s.boarded_at" class="flex gap-2">
                <button type="button" class="flex-1 rounded-xl bg-[#7fdcc8] py-2 text-sm font-semibold text-driver-bg active:scale-95 disabled:opacity-50" :disabled="busy" @click="board(s)">
                  {{ t('driver_policy.board') }}
                </button>
                <button type="button" class="flex-1 rounded-xl border border-white/10 py-2 text-sm font-medium text-driver-ink/80 active:scale-95" @click="absentFor = absentFor === s.id ? null : s.id">
                  {{ t('driver_policy.mark_absent') }}
                </button>
              </div>
              <div v-else-if="s.boarded_at && !s.alighted_at" class="flex">
                <button type="button" class="flex-1 rounded-xl border border-[#7fdcc8]/40 py-2 text-sm font-semibold text-[#7fdcc8] active:scale-95 disabled:opacity-50" :disabled="busy" @click="alight(s)">
                  {{ t('driver_policy.alight') }}
                </button>
              </div>

              <!-- Chọn lý do vắng -->
              <div v-if="absentFor === s.id" class="mt-2 flex gap-2">
                <button type="button" class="flex-1 rounded-xl bg-rose-500/20 py-2 text-xs font-medium text-rose-200 active:scale-95" :disabled="busy" @click="absent(s, 'absent_no_notice')">
                  {{ labelAbsenceReason('absent_no_notice') }}
                </button>
                <button type="button" class="flex-1 rounded-xl bg-amber-500/20 py-2 text-xs font-medium text-amber-200 active:scale-95" :disabled="busy" @click="absent(s, 'late_cancellation')">
                  {{ labelAbsenceReason('late_cancellation') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Hoàn thành (sticky footer) -->
    <div
      v-if="trip?.status === 'in_progress'"
      class="fixed inset-x-0 bottom-0 z-30 mx-auto max-w-[430px] border-t border-white/8 bg-driver-bg/95 p-3 backdrop-blur sm:max-w-none"
      style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))"
    >
      <button type="button" class="w-full rounded-2xl bg-[#7fdcc8] py-3.5 text-base font-semibold text-driver-bg transition active:scale-[0.99] disabled:opacity-50" :disabled="busy" @click="complete()">
        {{ t('driver_policy.complete_trip') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  getDriverPolicyTripStudents,
  startDriverPolicyTrip,
  completeDriverPolicyTrip,
  boardPolicyTripStudent,
  alightPolicyTripStudent,
  absentPolicyTripStudent,
} from '../../api/driverPolicy'
import { formatApiError } from '../../api/http'
import { confirmAction } from '../../composables/useConfirm'
import {
  labelPolicyTripStatus,
  labelTimeSlot,
  labelAbsenceReason,
} from '../../constants/policyTripStatus'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const id = route.params.id
const loading = ref(false)
const busy = ref(false)
const error = ref('')
const trip = ref(null)
const students = ref([])
const absentFor = ref(null)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await getDriverPolicyTripStudents(id)
    trip.value = data.trip
    students.value = data.items
  } catch (err) {
    error.value = formatApiError(err)
  } finally {
    loading.value = false
  }
}

function patchStudent(row) {
  const idx = students.value.findIndex((x) => x.id === row.id)
  if (idx !== -1) students.value[idx] = row
}

async function start() {
  await run(async () => {
    trip.value = await startDriverPolicyTrip(id)
  })
}

async function board(s) {
  await run(async () => patchStudent(await boardPolicyTripStudent(s.id)), true)
}
async function alight(s) {
  await run(async () => patchStudent(await alightPolicyTripStudent(s.id)), true)
}
async function absent(s, reason) {
  absentFor.value = null
  await run(async () => patchStudent(await absentPolicyTripStudent(s.id, reason)), true)
}

async function complete(confirm = false) {
  busy.value = true
  error.value = ''
  try {
    trip.value = await completeDriverPolicyTrip(id, confirm)
    router.push('/driver/policy-trips')
  } catch (err) {
    const data = err?.response?.data?.data
    if (data?.code === 'boarded_not_alighted') {
      const names = (data.students || []).map((x) => x.student_name).filter(Boolean).join(', ')
      const ok = await confirmAction({
        title: t('driver_policy.confirm_complete_title'),
        message: t('driver_policy.confirm_boarded_not_alighted', { names }),
        confirmLabel: t('driver_policy.complete_trip'),
      })
      if (ok) {
        busy.value = false
        return complete(true)
      }
    } else {
      error.value = formatApiError(err)
    }
  } finally {
    busy.value = false
  }
}

/** Sau mỗi action có thể đổi count → reload nhẹ. */
async function run(fn, refresh = false) {
  busy.value = true
  error.value = ''
  try {
    await fn()
    if (refresh) await load()
  } catch (err) {
    error.value = formatApiError(err)
  } finally {
    busy.value = false
  }
}

function studentLabel(s) {
  if (s.absence_reason) return labelAbsenceReason(s.absence_reason)
  if (s.alighted_at) return t('driver_policy.status_alighted')
  if (s.boarded_at) return t('driver_policy.status_boarded')
  return t('driver_policy.status_expected')
}
function studentBadgeClass(s) {
  if (s.absence_reason) return 'bg-rose-500/15 text-rose-200'
  if (s.alighted_at) return 'bg-emerald-500/15 text-emerald-200'
  if (s.boarded_at) return 'bg-[#7fdcc8]/15 text-[#7fdcc8]'
  return 'bg-white/10 text-driver-ink/60'
}
function statusClass(status) {
  return status === 'in_progress' ? 'bg-[#7fdcc8]/15 text-[#7fdcc8]' : 'bg-white/10 text-driver-ink/70'
}

onMounted(load)
</script>
