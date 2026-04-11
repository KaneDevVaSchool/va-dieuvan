<template>
  <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
  <div v-else-if="trip" class="space-y-4">
    <Card :title="`Chuyến #${trip.id}`">
      <div class="grid gap-2 text-sm md:grid-cols-2">
        <div><span class="text-slate-500">Trạng thái:</span> {{ labelTripStatus(trip.status) }}</div>
        <div><span class="text-slate-500">Lock version:</span> {{ trip.lock_version }}</div>
        <div><span class="text-slate-500">Xuất phát:</span> {{ fmt(trip.depart_at) }}</div>
        <div><span class="text-slate-500">Đến:</span> {{ fmt(trip.arrive_by) }}</div>
        <div><span class="text-slate-500">Xe:</span> {{ trip.vehicle?.license_plate ?? '-' }}</div>
        <div><span class="text-slate-500">Tài xế:</span> {{ trip.driver?.full_name ?? '-' }}</div>
      </div>
    </Card>

    <Card v-if="canAssign" title="Gán tài nguyên (dispatcher)">
      <p v-if="resourceHint" class="mb-2 text-xs text-amber-800">{{ resourceHint }}</p>
      <form class="grid gap-3 md:grid-cols-2" @submit.prevent="doAssign">
        <Input v-model.number="assign.lock_version" label="lock_version" type="number" />
        <div class="md:col-span-2 grid gap-3 sm:grid-cols-2">
          <Select v-model="vehicleChoice" label="Xe" placeholder="Chọn biển số">
            <option value="">— Không đổi / bỏ chọn —</option>
            <option v-for="v in vehicles" :key="v.id" :value="String(v.id)">
              {{ v.license_plate }} · {{ v.type ?? 'xe' }} {{ v.seat_count ? `(${v.seat_count} chỗ)` : '' }}
            </option>
          </Select>
          <Select v-model="driverChoice" label="Tài xế" placeholder="Chọn tài xế">
            <option value="">— Không đổi / bỏ chọn —</option>
            <option v-for="d in drivers" :key="d.id" :value="String(d.id)">
              {{ d.full_name }} {{ d.phone ? `· ${d.phone}` : '' }}
            </option>
          </Select>
        </div>
        <Input v-model.number="assign.transport_provider_id" label="transport_provider_id (tùy chọn)" type="number" />
        <div class="md:col-span-2">
          <Button :loading="assigning" type="submit">Gán</Button>
          <span v-if="assignMsg" class="ml-3 text-sm text-slate-600">{{ assignMsg }}</span>
        </div>
      </form>
    </Card>

    <Card v-else title="Gán tài nguyên">
      <p class="text-sm text-slate-600">Bạn không có quyền <code class="rounded bg-slate-100 px-1">trip.assign</code>.</p>
    </Card>

    <Card title="Cập nhật trạng thái">
      <form class="flex flex-col gap-3 md:flex-row md:items-end" @submit.prevent="doStatus">
        <Select v-model="statusForm.status" label="Trạng thái" placeholder="Chọn">
          <option value="driver_confirmed">{{ labelTripStatus('driver_confirmed') }}</option>
          <option value="in_progress">{{ labelTripStatus('in_progress') }}</option>
          <option value="completed">{{ labelTripStatus('completed') }}</option>
          <option value="cancelled">{{ labelTripStatus('cancelled') }}</option>
        </Select>
        <Input v-model="statusForm.message" class="md:flex-1" label="Ghi chú" />
        <Button :loading="statusing" type="submit">Cập nhật</Button>
      </form>
    </Card>

    <Card title="Chi phí (50 mới nhất)">
      <div class="space-y-2 text-sm">
        <div v-for="c in trip.costs ?? []" :key="c.id" class="flex justify-between border-b py-2">
          <span>{{ c.type }} • {{ c.status }}</span>
          <span class="font-medium">{{ c.amount }} {{ c.currency }}</span>
        </div>
        <div v-if="!(trip.costs ?? []).length" class="text-slate-500">Chưa có.</div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { assignTrip, getTrip, updateTripStatus } from '../../api/trips'
import { listVehicles, listDrivers } from '../../api/operational'
import { newIdempotencyKey } from '../../util/idempotency'
import { labelTripStatus } from '../../util/labels'
import { useAuthStore } from '../../store'

const auth = useAuthStore()
const route = useRoute()
const trip = ref(null)
const loading = ref(true)
const assigning = ref(false)
const assignMsg = ref('')
const statusing = ref(false)
const vehicles = ref([])
const drivers = ref([])
const resourceHint = ref('')
const vehicleChoice = ref('')
const driverChoice = ref('')

const canAssign = computed(() => auth.hasPermission('trip.assign'))

const assign = ref({ lock_version: 0, vehicle_id: null, driver_id: null, transport_provider_id: null })

function fmt(v) {
  return v ? new Date(v).toLocaleString('vi-VN') : '-'
}

async function loadResources() {
  resourceHint.value = ''
  if (!canAssign.value) return
  try {
    const [vr, dr] = await Promise.allSettled([
      listVehicles({ status: 'ready', per_page: 150 }),
      listDrivers({ employment_status: 'active', availability_status: 'available', per_page: 150 }),
    ])
    if (vr.status === 'fulfilled') {
      vehicles.value = vr.value.items ?? []
    } else {
      resourceHint.value = 'Không tải được danh sách xe (kiểm tra quyền resource.vehicle.manage hoặc trip.assign).'
    }
    if (dr.status === 'fulfilled') {
      drivers.value = dr.value.items ?? []
    } else {
      resourceHint.value =
        resourceHint.value ||
        'Không tải được danh sách tài xế (kiểm tra quyển resource.driver.manage hoặc trip.assign).'
    }
  } catch {
    resourceHint.value = 'Lỗi tải danh sách xe/tài xế.'
  }
}

async function load() {
  loading.value = true
  try {
    trip.value = await getTrip(route.params.id)
    assign.value.lock_version = trip.value.lock_version ?? 0
    vehicleChoice.value = trip.value.vehicle_id ? String(trip.value.vehicle_id) : ''
    driverChoice.value = trip.value.driver_id ? String(trip.value.driver_id) : ''
    await loadResources()
  } finally {
    loading.value = false
  }
}

async function doAssign() {
  assignMsg.value = ''
  assigning.value = true
  try {
    const payload = { lock_version: assign.value.lock_version }
    if (vehicleChoice.value) payload.vehicle_id = Number(vehicleChoice.value)
    if (driverChoice.value) payload.driver_id = Number(driverChoice.value)
    if (assign.value.transport_provider_id != null && assign.value.transport_provider_id !== '') {
      payload.transport_provider_id = Number(assign.value.transport_provider_id)
    }
    await assignTrip(route.params.id, payload, { idempotencyKey: newIdempotencyKey() })
    assignMsg.value = 'OK'
    await load()
  } catch (e) {
    assignMsg.value = e?.response?.data?.message ?? 'Lỗi'
  } finally {
    assigning.value = false
  }
}

async function doStatus() {
  statusing.value = true
  try {
    await updateTripStatus(route.params.id, statusForm.value)
    await load()
  } finally {
    statusing.value = false
  }
}

const statusForm = ref({ status: 'in_progress', message: '' })

onMounted(load)
watch(() => route.params.id, load)
</script>
