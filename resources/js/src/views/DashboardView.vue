<template>
  <div class="space-y-4">
    <div v-if="loadError" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
      {{ loadError }}
    </div>
    <div class="grid gap-4 md:grid-cols-3">
      <Card title="Trips theo trạng thái">
        <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
        <div v-else class="space-y-1 text-sm">
          <div v-for="(v, k) in summary?.trips_by_status ?? {}" :key="k" class="flex items-center justify-between gap-2">
            <span class="text-slate-600">{{ labelTripStatus(k) }}</span>
            <span class="font-semibold tabular-nums">{{ v }}</span>
          </div>
          <div v-if="!Object.keys(summary?.trips_by_status ?? {}).length" class="text-slate-500">Chưa có dữ liệu.</div>
        </div>
      </Card>

      <Card title="Chi phí confirmed theo loại">
        <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
        <div v-else class="space-y-1 text-sm">
          <div v-for="(v, k) in summary?.confirmed_costs_by_type ?? {}" :key="k" class="flex items-center justify-between">
            <span class="text-slate-600">{{ k }}</span>
            <span class="font-semibold">{{ formatMoney(v) }}</span>
          </div>
          <div v-if="!Object.keys(summary?.confirmed_costs_by_type ?? {}).length" class="text-slate-500">Chưa có dữ liệu.</div>
        </div>
      </Card>

      <Card title="Cargo SLA breaches">
        <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
        <div v-else class="flex items-end justify-between">
          <div class="text-sm text-slate-600">Số shipment trễ SLA</div>
          <div class="text-2xl font-bold">{{ summary?.cargo_sla_breaches ?? 0 }}</div>
        </div>
      </Card>
    </div>

    <Card title="Chi phí confirmed theo NCC / nội bộ (tháng hiện tại)">
      <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
      <template v-else>
        <p class="mb-2 text-xs text-slate-500">
          Cùng nguồn dữ liệu với báo cáo tổng hợp. Xem đầy đủ và xuất CSV tại
          <RouterLink class="font-medium text-slate-900 underline" to="/reports">Báo cáo</RouterLink>.
        </p>
        <div v-if="!topProviders.length" class="text-sm text-slate-500">Chưa có dữ liệu.</div>
        <ul v-else class="space-y-1 text-sm">
          <li v-for="(row, i) in topProviders" :key="i" class="flex justify-between gap-2">
            <span class="text-slate-600">{{ row.provider }}</span>
            <span class="shrink-0 font-medium tabular-nums">{{ formatMoney(row.total_amount) }}</span>
          </li>
        </ul>
      </template>
    </Card>

    <Card title="Thao tác nhanh">
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <RouterLink class="rounded-lg border bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50" to="/dispatcher">
          <div class="font-semibold">Bảng điều vận</div>
          <div class="mt-1 text-xs text-slate-500">Hàng đợi chuyến & lịch tài xế</div>
        </RouterLink>
        <RouterLink class="rounded-lg border bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50" to="/trips">
          <div class="font-semibold">Danh sách chuyến</div>
          <div class="mt-1 text-xs text-slate-500">Lọc, mở chi tiết từng chuyến</div>
        </RouterLink>
        <RouterLink class="rounded-lg border bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50" to="/dispatch-requests/new">
          <div class="font-semibold">Tạo yêu cầu</div>
          <div class="mt-1 text-xs text-slate-500">BR-001, preset, idempotency</div>
        </RouterLink>
        <RouterLink class="rounded-lg border bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50" to="/requests">
          <div class="font-semibold">Danh sách yêu cầu</div>
          <div class="mt-1 text-xs text-slate-500">Lọc trạng thái, kênh, phiếu giấy</div>
        </RouterLink>
        <RouterLink class="rounded-lg border bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50" to="/pricing">
          <div class="font-semibold">Bảng giá tham chiếu</div>
          <div class="mt-1 text-xs text-slate-500">Xe khách &amp; hàng hóa</div>
        </RouterLink>
        <RouterLink class="rounded-lg border bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50" to="/help">
          <div class="font-semibold">Hướng dẫn</div>
          <div class="mt-1 text-xs text-slate-500">BR-001, BR-002, phiếu giấy</div>
        </RouterLink>
        <RouterLink class="rounded-lg border bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50" to="/roadmap">
          <div class="font-semibold">Đề xuất &amp; lộ trình</div>
          <div class="mt-1 text-xs text-slate-500">Tính năng đề xuất, UX</div>
        </RouterLink>
        <RouterLink class="rounded-lg border bg-white p-3 text-sm transition-colors hover:border-slate-300 hover:bg-slate-50" to="/audit-logs">
          <div class="font-semibold">Activity log</div>
          <div class="mt-1 text-xs text-slate-500">Truy vết thao tác</div>
        </RouterLink>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import Card from '../components/ui/Card.vue'
import { getSummary } from '../api/reports'
import { labelTripStatus } from '../util/labels'

const loading = ref(false)
const loadError = ref('')
const summary = ref(null)

const topProviders = computed(() => {
  const raw = summary.value?.confirmed_costs_by_provider
  if (!Array.isArray(raw)) return []
  return raw.slice(0, 8)
})

function formatMoney(v) {
  const n = Number(v ?? 0)
  return new Intl.NumberFormat('vi-VN').format(n) + ' VND'
}

onMounted(async () => {
  loading.value = true
  loadError.value = ''
  try {
    summary.value = await getSummary()
  } catch {
    loadError.value = 'Không tải được tổng quan. Thử lại sau.'
  } finally {
    loading.value = false
  }
})
</script>

