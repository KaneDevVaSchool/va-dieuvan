<template>
  <Modal
    :open="open"
    :title="t('p2p_policy_page.cancel_title')"
    :description="tripLabel"
    @close="emit('close')"
  >
    <div class="space-y-4">
      <label class="block">
        <span class="mb-1 block text-xs font-medium text-slate-600">
          {{ t('p2p_policy_page.cancel_reason') }}<span class="text-rose-600"> *</span>
        </span>
        <textarea
          v-model="reason"
          rows="3"
          class="w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm outline-none ring-slate-200 focus:ring"
          :placeholder="t('p2p_policy_page.cancel_reason_ph')"
        ></textarea>
      </label>

      <p v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>

      <div class="flex justify-end gap-2 pt-1">
        <Button variant="secondary" :disabled="loading" @click="emit('close')">{{ t('common.cancel') }}</Button>
        <Button variant="danger" :loading="loading" :disabled="!reason.trim()" @click="submit">
          {{ t('p2p_policy_page.cancel_action') }}
        </Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Modal from '../ui/Modal.vue'
import Button from '../ui/Button.vue'
import { cancelPolicyTrip } from '../../api/p2p'
import { formatApiError } from '../../api/http'
import { labelTimeSlot } from '../../constants/policyTripStatus'

const props = defineProps({
  open: { type: Boolean, default: false },
  trip: { type: Object, default: null },
})
const emit = defineEmits(['close', 'cancelled'])
const { t } = useI18n()

const reason = ref('')
const loading = ref(false)
const error = ref('')

const tripLabel = computed(() =>
  props.trip ? `${props.trip.route_name ?? ''} · ${labelTimeSlot(props.trip.time_slot)} · ${props.trip.trip_date}` : '',
)

watch(
  () => props.open,
  (v) => {
    if (v) {
      reason.value = ''
      error.value = ''
    }
  },
)

async function submit() {
  loading.value = true
  error.value = ''
  try {
    const row = await cancelPolicyTrip(props.trip.id, reason.value.trim())
    emit('cancelled', row)
    emit('close')
  } catch (err) {
    error.value = formatApiError(err)
  } finally {
    loading.value = false
  }
}
</script>
