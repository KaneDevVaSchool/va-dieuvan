<template>
  <Modal
    :open="open"
    wide
    :title="t('p2p_policy_page.detail_title')"
    :description="tripLabel"
    @close="emit('close')"
  >
    <div class="space-y-4">
      <div v-if="trip" class="grid grid-cols-2 gap-3 rounded-lg bg-slate-50 px-3 py-3 text-sm sm:grid-cols-4">
        <div>
          <div class="text-xs text-slate-500">{{ t('p2p_policy_page.col_status') }}</div>
          <PolicyPill :text="labelPolicyTripStatus(trip.status)" :pill-class="policyTripStatusPillClass(trip.status)" />
        </div>
        <div>
          <div class="text-xs text-slate-500">{{ t('p2p_policy_page.col_driver') }}</div>
          <div class="font-medium text-slate-800">{{ trip.driver_name || t('p2p_policy_page.unassigned') }}</div>
        </div>
        <div>
          <div class="text-xs text-slate-500">{{ t('p2p_policy_page.col_expected') }}</div>
          <div class="font-medium text-slate-800">{{ trip.expected_count }}</div>
        </div>
        <div>
          <div class="text-xs text-slate-500">{{ t('p2p_policy_page.col_boarded') }} / {{ t('p2p_policy_page.col_absent') }}</div>
          <div class="font-medium text-slate-800">{{ trip.boarded_count }} / {{ trip.absent_count }}</div>
        </div>
      </div>

      <div v-if="loading" class="py-6 text-center text-sm text-slate-500">{{ t('p2p_policy_page.loading') }}</div>

      <div v-else-if="students.length === 0" class="py-6 text-center text-sm text-slate-500">
        {{ t('p2p_policy_page.detail_empty') }}
      </div>

      <div v-else class="overflow-hidden rounded-lg border border-slate-200">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
            <tr>
              <th class="px-3 py-2 font-medium">{{ t('p2p_policy_page.col_student') }}</th>
              <th class="px-3 py-2 font-medium">{{ t('p2p_policy_page.col_student_status') }}</th>
              <th class="px-3 py-2 font-medium">{{ t('p2p_policy_page.col_times') }}</th>
              <th class="px-3 py-2 text-right font-medium">{{ t('p2p_policy_page.col_actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="s in students" :key="s.id">
              <td class="px-3 py-2">
                <div class="font-medium text-slate-800">{{ s.student_name }}</div>
                <div class="text-xs text-slate-500">{{ s.class_name || s.student_code }}</div>
              </td>
              <td class="px-3 py-2">
                <PolicyPill v-if="s.absence_reason" :text="labelAbsenceReason(s.absence_reason)" :pill-class="absenceReasonPillClass(s.absence_reason)" />
                <PolicyPill v-else :text="t('p2p_policy_page.student_status_' + s.status)" :pill-class="studentStatusPill(s.status)" />
                <div v-if="s.reported_by_name" class="mt-0.5 text-[11px] text-slate-400">
                  {{ t('p2p_policy_page.reported_by', { name: s.reported_by_name }) }}
                </div>
              </td>
              <td class="px-3 py-2 text-xs text-slate-500">
                <div v-if="s.boarded_at">{{ t('p2p_policy_page.boarded_at') }}: {{ fmtTime(s.boarded_at) }}</div>
                <div v-if="s.alighted_at">{{ t('p2p_policy_page.alighted_at') }}: {{ fmtTime(s.alighted_at) }}</div>
                <span v-if="!s.boarded_at && !s.alighted_at">—</span>
              </td>
              <td class="px-3 py-2 text-right">
                <button
                  v-if="canMarkAbsent(s)"
                  type="button"
                  class="rounded-md border border-slate-200 px-2 py-1 text-xs text-slate-600 transition hover:bg-slate-50 disabled:opacity-50"
                  :disabled="markingId === s.id"
                  @click="markAbsent(s)"
                >
                  {{ t('p2p_policy_page.mark_absent_reported') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>

      <div class="flex justify-end pt-1">
        <Button variant="secondary" @click="emit('close')">{{ t('common.close') }}</Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Modal from '../ui/Modal.vue'
import Button from '../ui/Button.vue'
import PolicyPill from './PolicyPill.vue'
import { getPolicyTripStudents, markPolicyTripStudentAbsent } from '../../api/p2p'
import { formatApiError } from '../../api/http'
import {
  labelAbsenceReason,
  absenceReasonPillClass,
  labelPolicyTripStatus,
  policyTripStatusPillClass,
  labelTimeSlot,
} from '../../constants/policyTripStatus'

const props = defineProps({
  open: { type: Boolean, default: false },
  tripId: { type: [Number, String], default: null },
})
const emit = defineEmits(['close', 'changed'])
const { t } = useI18n()

const trip = ref(null)
const students = ref([])
const loading = ref(false)
const error = ref('')
const markingId = ref(null)

const tripLabel = computed(() =>
  trip.value ? `${trip.value.route_name ?? ''} · ${labelTimeSlot(trip.value.time_slot)} · ${trip.value.trip_date}` : '',
)

watch(
  () => props.open,
  (v) => {
    if (v && props.tripId) load()
  },
)

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await getPolicyTripStudents(props.tripId)
    trip.value = data.trip
    students.value = data.items
  } catch (err) {
    error.value = formatApiError(err)
  } finally {
    loading.value = false
  }
}

/** Điều vận chỉ đánh vắng (có phép) HS chưa xử lý ở chuyến chưa khởi hành. */
function canMarkAbsent(s) {
  return !s.absence_reason && !s.boarded_at && ['scheduled', 'assigned'].includes(trip.value?.status)
}

async function markAbsent(s) {
  markingId.value = s.id
  error.value = ''
  try {
    await markPolicyTripStudentAbsent(s.id, 'absent_reported')
    await load()
    emit('changed')
  } catch (err) {
    error.value = formatApiError(err)
  } finally {
    markingId.value = null
  }
}

function studentStatusPill(status) {
  const map = {
    expected: 'bg-slate-100 text-slate-700',
    boarded: 'bg-teal-100 text-teal-900',
    alighted: 'bg-emerald-100 text-emerald-900',
  }
  return map[status] ?? 'bg-slate-100 text-slate-700'
}

function fmtTime(iso) {
  try {
    return new Date(iso).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
  } catch {
    return iso
  }
}
</script>
