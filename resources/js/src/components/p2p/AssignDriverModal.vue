<template>
  <Modal
    :open="open"
    :title="t('p2p_policy_page.assign_title')"
    :description="tripLabel"
    @close="emit('close')"
  >
    <div class="space-y-4">
      <Select v-model="driverId" :label="t('p2p_policy_page.assign_driver')" :placeholder="t('p2p_policy_page.assign_pick_driver')" required>
        <option v-for="d in drivers" :key="d.id" :value="String(d.id)">
          {{ d.full_name }}<template v-if="d.phone"> · {{ d.phone }}</template>
        </option>
      </Select>

      <Select v-model="vehicleId" :label="t('p2p_policy_page.assign_vehicle')" :placeholder="t('p2p_policy_page.assign_pick_vehicle')" required>
        <option v-for="v in vehicles" :key="v.id" :value="String(v.id)">
          {{ v.license_plate }}<template v-if="v.seat_count"> · {{ v.seat_count }} {{ t('p2p_policy_page.seats') }}</template>
        </option>
      </Select>

      <p v-if="capacityWarning" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
        {{ t('p2p_policy_page.assign_capacity_warning', { seats: selectedSeats, expected: trip?.expected_count ?? 0 }) }}
      </p>

      <p v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
        {{ error }}
      </p>

      <div class="flex justify-end gap-2 pt-1">
        <Button variant="secondary" :disabled="loading" @click="emit('close')">{{ t('common.cancel') }}</Button>
        <Button :loading="loading" :disabled="!driverId || !vehicleId" @click="submit">{{ t('p2p_policy_page.assign_action') }}</Button>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Modal from '../ui/Modal.vue'
import Button from '../ui/Button.vue'
import Select from '../ui/Select.vue'
import { assignDriverToPolicyTrip, listDrivers, listVehicles } from '../../api/p2p'
import { formatApiError } from '../../api/http'
import { labelTimeSlot } from '../../constants/policyTripStatus'

const props = defineProps({
  open: { type: Boolean, default: false },
  trip: { type: Object, default: null },
})
const emit = defineEmits(['close', 'assigned'])
const { t } = useI18n()

const drivers = ref([])
const vehicles = ref([])
const driverId = ref('')
const vehicleId = ref('')
const loading = ref(false)
const error = ref('')

const tripLabel = computed(() => {
  if (!props.trip) return ''
  return `${props.trip.route_name ?? ''} · ${labelTimeSlot(props.trip.time_slot)} · ${props.trip.trip_date}`
})

const selectedSeats = computed(() => {
  const v = vehicles.value.find((x) => String(x.id) === vehicleId.value)
  return v?.seat_count ?? 0
})
const capacityWarning = computed(
  () => vehicleId.value && selectedSeats.value > 0 && selectedSeats.value < (props.trip?.expected_count ?? 0),
)

watch(
  () => props.open,
  async (v) => {
    if (!v) return
    error.value = ''
    driverId.value = props.trip?.driver_id ? String(props.trip.driver_id) : ''
    vehicleId.value = props.trip?.vehicle_id ? String(props.trip.vehicle_id) : ''
    await loadLookups()
  },
)

async function loadLookups() {
  try {
    const [d, v] = await Promise.all([
      listDrivers({ per_page: 200 }),
      listVehicles({ per_page: 200 }),
    ])
    drivers.value = d?.items ?? []
    vehicles.value = v?.items ?? []
  } catch (err) {
    error.value = formatApiError(err)
  }
}

async function submit() {
  loading.value = true
  error.value = ''
  try {
    const row = await assignDriverToPolicyTrip(props.trip.id, {
      driver_id: Number(driverId.value),
      vehicle_id: Number(vehicleId.value),
    })
    emit('assigned', row)
    emit('close')
  } catch (err) {
    error.value = formatApiError(err)
  } finally {
    loading.value = false
  }
}
</script>
