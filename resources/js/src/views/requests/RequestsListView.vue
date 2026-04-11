<template>
  <div class="space-y-4">
    <Card>
      <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div class="grid w-full gap-3 md:grid-cols-2 lg:grid-cols-5">
          <Select v-model="filters.status" label="Trạng thái" placeholder="Tất cả">
            <option value="pending">{{ labelRequestStatus('pending') }}</option>
            <option value="approved">{{ labelRequestStatus('approved') }}</option>
            <option value="rejected">{{ labelRequestStatus('rejected') }}</option>
            <option value="cancelled">{{ labelRequestStatus('cancelled') }}</option>
          </Select>
          <Select v-model="filters.trip_type" label="Loại chuyến" placeholder="Tất cả">
            <option value="door_to_door">{{ labelTripType('door_to_door') }}</option>
            <option value="point_to_point">{{ labelTripType('point_to_point') }}</option>
            <option value="business">{{ labelTripType('business') }}</option>
            <option value="cargo">{{ labelTripType('cargo') }}</option>
          </Select>
          <Select v-model="filters.source_channel" label="Kênh" placeholder="Tất cả">
            <option value="portal">{{ labelSourceChannel('portal') }}</option>
            <option value="zalo">{{ labelSourceChannel('zalo') }}</option>
            <option value="paper">{{ labelSourceChannel('paper') }}</option>
          </Select>
          <Select v-model="filters.paper_status" label="Trạng thái phiếu giấy" placeholder="Tất cả">
            <option value="pending">{{ labelPaperStatus('pending') }}</option>
            <option value="received">{{ labelPaperStatus('received') }}</option>
            <option value="digitally_signed">{{ labelPaperStatus('digitally_signed') }}</option>
          </Select>
          <Input v-model="filters.per_page" label="Số dòng/trang" type="number" placeholder="20" />
        </div>

        <div class="flex gap-2">
          <Button variant="secondary" :loading="loading" @click="reload">Lọc</Button>
          <RouterLink class="rounded-md border px-3 py-2 text-sm hover:bg-slate-50" to="/dispatch-requests/new">
            Tạo yêu cầu
          </RouterLink>
        </div>
      </div>
    </Card>

    <Card title="Danh sách yêu cầu">
      <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
      <div v-else>
        <div v-if="!items.length" class="text-sm text-slate-500">Không có dữ liệu.</div>

        <div class="grid gap-3 md:grid-cols-2" v-else>
          <RouterLink
            v-for="r in items"
            :key="r.id"
            :to="`/requests/${r.id}`"
            class="block rounded-xl border bg-white p-4 transition hover:border-slate-400"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="truncate text-sm font-semibold">
                  #{{ r.id }} • {{ labelTripType(r.trip_type) }} • {{ labelRequestStatus(r.status) }}
                </div>
                <div class="mt-1 text-xs text-slate-500">
                  {{ formatDate(r.depart_at) }}
                  <span v-if="r.arrive_by"> → {{ formatDate(r.arrive_by) }}</span>
                </div>
              </div>
              <div class="shrink-0">
                <span
                  class="inline-flex items-center rounded-full px-2 py-1 text-[11px] font-medium"
                  :class="badgeClass(r.status)"
                >
                  {{ labelRequestStatus(r.status) }}
                </span>
              </div>
            </div>

            <div class="mt-3 grid gap-2 text-xs text-slate-600">
              <div class="flex gap-2">
                <span class="w-24 text-slate-500">Tuyến</span>
                <span class="min-w-0 truncate">{{ r.origin ?? '-' }} → {{ r.destination ?? '-' }}</span>
              </div>
              <div class="flex gap-2">
                <span class="w-24 text-slate-500">Kênh</span>
                <span>{{ labelSourceChannel(r.source_channel) }}
                  <span v-if="r.is_urgent" class="ml-2 text-rose-600">(gấp)</span></span>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <span class="w-24 shrink-0 text-slate-500">Phiếu giấy</span>
                <span
                  v-if="r.paper_status"
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                  :class="paperBadgeClass(r.paper_status)"
                >
                  {{ labelPaperStatus(r.paper_status) }}
                </span>
                <span v-else class="text-slate-400">-</span>
                <span v-if="r.paper_reference" class="truncate text-slate-600">• {{ r.paper_reference }}</span>
              </div>
            </div>
            <div class="mt-3 text-xs font-medium text-slate-900">Chi tiết →</div>
          </RouterLink>
        </div>

        <div class="mt-4 flex items-center justify-between text-sm">
          <div class="text-slate-500">Total: {{ meta.total ?? 0 }}</div>
          <div class="flex items-center gap-2">
            <Button variant="secondary" :disabled="(meta.current_page ?? 1) <= 1" @click="goPage((meta.current_page ?? 1) - 1)">
              Trước
            </Button>
            <div class="text-xs text-slate-500">
              Trang {{ meta.current_page ?? 1 }} / {{ meta.last_page ?? 1 }}
            </div>
            <Button
              variant="secondary"
              :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1)"
              @click="goPage((meta.current_page ?? 1) + 1)"
            >
              Sau
            </Button>
          </div>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { listRequests } from '../../api/requests'
import { labelPaperStatus, labelRequestStatus, labelSourceChannel, labelTripType } from '../../util/labels'

const route = useRoute()
const loading = ref(false)
const items = ref([])
const meta = ref({})

const filters = reactive({
  status: '',
  trip_type: '',
  source_channel: '',
  paper_status: '',
  per_page: 20,
  page: 1,
})

function formatDate(v) {
  if (!v) return '-'
  try {
    return new Date(v).toLocaleString('vi-VN')
  } catch {
    return String(v)
  }
}

function paperBadgeClass(status) {
  if (status === 'received' || status === 'digitally_signed') return 'bg-emerald-50 text-emerald-700'
  if (status === 'pending') return 'bg-amber-50 text-amber-700'
  return 'bg-slate-100 text-slate-600'
}

function badgeClass(status) {
  return status === 'approved'
    ? 'bg-emerald-50 text-emerald-700'
    : status === 'pending'
      ? 'bg-amber-50 text-amber-700'
      : status === 'rejected'
        ? 'bg-rose-50 text-rose-700'
        : 'bg-slate-100 text-slate-700'
}

async function reload() {
  loading.value = true
  try {
    const params = { ...filters }
    Object.keys(params).forEach((k) => (params[k] === '' ? delete params[k] : null))
    const res = await listRequests(params)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  filters.page = p
  reload()
}

function applyRouteQuery() {
  const q = route.query
  if (typeof q.status === 'string') filters.status = q.status
  if (typeof q.trip_type === 'string') filters.trip_type = q.trip_type
  if (typeof q.source_channel === 'string') filters.source_channel = q.source_channel
  if (typeof q.paper_status === 'string') filters.paper_status = q.paper_status
  filters.page = 1
}

onMounted(() => {
  applyRouteQuery()
  reload()
})

watch(
  () => route.query,
  () => {
    applyRouteQuery()
    reload()
  },
  { deep: true },
)
</script>

