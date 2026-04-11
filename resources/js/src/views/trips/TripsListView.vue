<template>
  <div class="space-y-4">
    <Card>
      <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div class="grid w-full gap-3 md:grid-cols-3">
          <Select v-model="filters.status" label="Trạng thái" placeholder="Tất cả">
            <option value="pending">{{ labelTripStatus('pending') }}</option>
            <option value="approved">{{ labelTripStatus('approved') }}</option>
            <option value="assigned">{{ labelTripStatus('assigned') }}</option>
            <option value="driver_confirmed">{{ labelTripStatus('driver_confirmed') }}</option>
            <option value="in_progress">{{ labelTripStatus('in_progress') }}</option>
            <option value="completed">{{ labelTripStatus('completed') }}</option>
            <option value="cancelled">{{ labelTripStatus('cancelled') }}</option>
            <option value="incident">{{ labelTripStatus('incident') }}</option>
          </Select>
          <Input v-model="filters.from" label="Từ ngày" type="date" />
          <Input v-model="filters.to" label="Đến ngày" type="date" />
        </div>
        <Button variant="secondary" :loading="loading" @click="reload">Lọc</Button>
      </div>
    </Card>

    <Card title="Chuyến đi">
      <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
      <div v-else class="space-y-2">
        <RouterLink
          v-for="t in items"
          :key="t.id"
          :to="`/trips/${t.id}`"
          class="block rounded-xl border bg-white p-4 hover:border-slate-400"
        >
          <div class="flex items-start justify-between gap-2">
            <div>
              <div class="text-sm font-semibold">Chuyến #{{ t.id }} • {{ labelTripStatus(t.status) }}</div>
              <div class="mt-1 text-xs text-slate-500">
                {{ fmt(t.depart_at) }}
                <span v-if="t.vehicle"> • Xe {{ t.vehicle.license_plate }}</span>
              </div>
            </div>
            <span class="text-xs text-slate-400">→</span>
          </div>
        </RouterLink>
        <div v-if="!items.length" class="text-sm text-slate-500">Không có chuyến.</div>
      </div>

      <div class="mt-4 flex items-center justify-between text-sm">
        <span class="text-slate-500">Total {{ meta.total ?? 0 }}</span>
        <div class="flex gap-2">
          <Button variant="secondary" :disabled="(meta.current_page ?? 1) <= 1" @click="page(-1)">Trước</Button>
          <Button variant="secondary" :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1)" @click="page(1)">
            Sau
          </Button>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { listTrips } from '../../api/trips'
import { labelTripStatus } from '../../util/labels'

const loading = ref(false)
const items = ref([])
const meta = ref({})
const filters = reactive({ status: '', from: '', to: '', page: 1, per_page: 20 })

function fmt(v) {
  return v ? new Date(v).toLocaleString('vi-VN') : '-'
}

async function reload() {
  loading.value = true
  try {
    const p = { ...filters }
    Object.keys(p).forEach((k) => (p[k] === '' ? delete p[k] : null))
    const res = await listTrips(p)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
  } finally {
    loading.value = false
  }
}

function page(d) {
  filters.page = (meta.value.current_page ?? 1) + d
  reload()
}

onMounted(reload)
</script>
