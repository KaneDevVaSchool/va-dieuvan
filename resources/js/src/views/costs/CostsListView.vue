<template>
  <div class="costs-page space-y-6 pb-12 text-slate-900">
    <!-- Header — light only -->
    <header class="rounded-2xl border border-slate-200/90 bg-white px-5 py-6 shadow-sm ring-1 ring-slate-900/[0.04] sm:px-8">
      <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Điều vận · Vận hành</p>
      <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">Quản lý chi phí</h1>
      <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-600">
        Theo dõi chi phí gắn chuyến (xăng, cầu đường, bãi xe…), trạng thái đối soát và chứng từ. Giao diện sáng, dễ in / đối chiếu.
      </p>
    </header>

    <!-- KPI — tóm tắt trang hiện tại + tổng từ API -->
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
      <div
        class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm ring-1 ring-slate-900/[0.03]"
      >
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Tổng bản ghi (lọc)</p>
        <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-900">{{ meta.total ?? 0 }}</p>
      </div>
      <div
        class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm ring-1 ring-slate-900/[0.03]"
      >
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Chờ xử lý (trang này)</p>
        <p class="mt-1 text-2xl font-semibold tabular-nums text-amber-800">{{ countOnPage('submitted') }}</p>
      </div>
      <div
        class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm ring-1 ring-slate-900/[0.03]"
      >
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Đã xác nhận (trang này)</p>
        <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-800">{{ countOnPage('confirmed') }}</p>
      </div>
      <div
        class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm ring-1 ring-slate-900/[0.03]"
      >
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Từ chối (trang này)</p>
        <p class="mt-1 text-2xl font-semibold tabular-nums text-rose-800">{{ countOnPage('rejected') }}</p>
      </div>
    </div>

    <!-- Toolbar lọc -->
    <div
      class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-slate-50/80 p-4 shadow-sm ring-1 ring-slate-900/[0.04] sm:flex-row sm:flex-wrap sm:items-end"
    >
      <div class="min-w-[12rem] flex-1">
        <label class="mb-1 block text-xs font-medium text-slate-600">Tìm trong trang hiện tại</label>
        <input
          v-model="searchQ"
          type="search"
          placeholder="Nội dung, người đề xuất, loại…"
          class="costs-input w-full"
        />
      </div>
      <div class="w-full min-w-[8rem] sm:w-40">
        <label class="mb-1 block text-xs font-medium text-slate-600">Trạng thái</label>
        <select v-model="filters.status" class="costs-input w-full">
          <option value="">Tất cả</option>
          <option value="draft">Nháp</option>
          <option value="submitted">Đã gửi</option>
          <option value="confirmed">Đã xác nhận</option>
          <option value="rejected">Từ chối</option>
        </select>
      </div>
      <div class="w-full min-w-[6rem] sm:w-32">
        <label class="mb-1 block text-xs font-medium text-slate-600">Trip ID</label>
        <input v-model.number="filters.trip_id" type="number" placeholder="—" class="costs-input w-full" />
      </div>
      <div class="w-full min-w-[9rem] sm:w-40">
        <label class="mb-1 block text-xs font-medium text-slate-600">Từ ngày</label>
        <input v-model="filters.from" type="date" class="costs-input w-full" />
      </div>
      <div class="w-full min-w-[9rem] sm:w-40">
        <label class="mb-1 block text-xs font-medium text-slate-600">Đến ngày</label>
        <input v-model="filters.to" type="date" class="costs-input w-full" />
      </div>
      <div class="flex w-full gap-2 sm:ml-auto sm:w-auto">
        <button type="button" class="costs-btn-primary flex-1 sm:flex-none" :disabled="loading" @click="reload">
          <span v-if="loading" class="inline-block size-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
          Áp dụng lọc
        </button>
      </div>
    </div>

    <!-- Bảng dạng bảng tính — nền sáng, viền nét đứt -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/[0.05]">
      <div class="border-b border-slate-200 bg-slate-100 px-4 py-3 sm:px-5">
        <h2 class="text-sm font-semibold text-slate-800">Danh sách chi phí</h2>
        <p class="mt-0.5 text-xs text-slate-500">Ô có nền xám nhạt là nhãn phân loại / trạng thái (dễ quét).</p>
      </div>
      <div class="costs-table-wrap overflow-x-auto">
        <table class="costs-sheet min-w-[1100px] w-full border-collapse text-left text-xs sm:text-sm">
          <thead>
            <tr class="bg-slate-100 text-[10px] font-semibold uppercase tracking-wide text-slate-600 sm:text-[11px]">
              <th class="costs-th w-10 text-center">STT</th>
              <th class="costs-th min-w-[7rem]">Đơn vị</th>
              <th class="costs-th min-w-[7rem]">Phân loại</th>
              <th class="costs-th min-w-[8rem]">Người đề xuất</th>
              <th class="costs-th min-w-[14rem]">Nội dung</th>
              <th class="costs-th min-w-[8rem]">Nhà cung cấp</th>
              <th class="costs-th costs-th--money min-w-[7rem] text-right" colspan="1">Tạm ứng</th>
              <th class="costs-th costs-th--money min-w-[8rem] text-right">Thanh toán</th>
              <th class="costs-th min-w-[6.5rem] whitespace-nowrap">Thời gian</th>
              <th class="costs-th min-w-[8rem]">Người phụ trách</th>
              <th class="costs-th min-w-[6rem]">Chứng từ</th>
              <th class="costs-th min-w-[7rem]">Pháp nhân TT</th>
              <th class="costs-th min-w-[6rem]">Ghi chú</th>
              <th class="costs-th min-w-[5rem]">Trip</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(c, idx) in displayedItems"
              :key="c.id"
              :class="idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/60'"
              class="transition-colors hover:bg-sky-50/50"
            >
              <td class="costs-td text-center text-slate-500">{{ rowIndex(idx) }}</td>
              <td class="costs-td">
                <span class="costs-pill">{{ UNIT_LABEL }}</span>
              </td>
              <td class="costs-td">
                <span class="costs-pill costs-pill--type">{{ typeLabel(c.type) }}</span>
              </td>
              <td class="costs-td text-slate-800">{{ c.creator?.name || '—' }}</td>
              <td class="costs-td max-w-[20rem] text-slate-800">
                <span class="line-clamp-2" :title="c.description || ''">{{ c.description || '—' }}</span>
              </td>
              <td class="costs-td text-slate-700">{{ c.trip?.transport_provider?.name ?? '—' }}</td>
              <td class="costs-td costs-td--money text-right text-slate-400">—</td>
              <td class="costs-td costs-td--money text-right font-medium tabular-nums text-slate-900">
                {{ formatVnd(c.amount) }}
              </td>
              <td class="costs-td whitespace-nowrap text-slate-700">{{ formatDateDMY(c.created_at) }}</td>
              <td class="costs-td">
                <span v-if="c.confirmer?.name" class="costs-pill">{{ c.confirmer.name }}</span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="costs-td">
                <a
                  v-if="c.receipt_url"
                  :href="c.receipt_url"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-va-800 underline decoration-va-800/30 underline-offset-2 hover:text-va-900"
                  >Xem</a
                >
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="costs-td text-slate-500">{{ LEGAL_ENTITY_PLACEHOLDER }}</td>
              <td class="costs-td max-w-[12rem] text-slate-600">
                <span v-if="c.rejection_reason" class="line-clamp-2 text-rose-700" :title="c.rejection_reason">{{
                  c.rejection_reason
                }}</span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="costs-td">
                <RouterLink
                  v-if="c.trip_id"
                  class="font-medium text-va-800 underline decoration-va-800/30 underline-offset-2 hover:text-va-900"
                  :to="`/trips/${c.trip_id}`"
                  >#{{ c.trip_id }}</RouterLink
                >
                <span v-else>—</span>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!loading && !displayedItems.length" class="px-4 py-12 text-center text-sm text-slate-500">
          Không có dòng nào phù hợp (thử đổi lọc hoặc từ khóa).
        </div>
        <div v-if="loading" class="flex items-center justify-center gap-2 px-4 py-12 text-sm text-slate-500">
          <span
            class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-va-700"
            aria-hidden="true"
          />
          Đang tải…
        </div>
      </div>

      <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/90 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
        <span class="text-sm text-slate-600">
          Trang <strong class="font-semibold text-slate-900">{{ meta.current_page ?? 1 }}</strong> /
          {{ meta.last_page ?? 1 }}
          <span class="text-slate-400"> · </span>
          {{ meta.total ?? 0 }} bản ghi
        </span>
        <div class="flex gap-2">
          <button
            type="button"
            class="costs-btn-ghost"
            :disabled="loading || (meta.current_page ?? 1) <= 1"
            @click="page(-1)"
          >
            Trước
          </button>
          <button
            type="button"
            class="costs-btn-ghost"
            :disabled="loading || (meta.current_page ?? 1) >= (meta.last_page ?? 1)"
            @click="page(1)"
          >
            Sau
          </button>
        </div>
      </div>
    </div>

    <!-- Nhập nhanh — card sáng, không sidebar tối -->
    <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/[0.04]">
      <summary
        class="cursor-pointer list-none px-5 py-4 text-sm font-semibold text-slate-900 marker:content-none [&::-webkit-details-marker]:hidden"
      >
        <span class="flex items-center justify-between gap-2">
          Nhập chi phí nhanh (cần Trip ID)
          <span class="text-xs font-normal text-slate-500 group-open:hidden">Mở rộng</span>
          <span class="hidden text-xs font-normal text-slate-500 group-open:inline">Thu gọn</span>
        </span>
      </summary>
      <div class="border-t border-slate-100 px-5 pb-5 pt-2">
        <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submitCost">
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Trip ID</label>
            <input v-model.number="costForm.trip_id" type="number" required class="costs-input w-full" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Loại</label>
            <select v-model="costForm.type" class="costs-input w-full">
              <option value="fuel">Xăng / dầu</option>
              <option value="toll">Phí cầu đường</option>
              <option value="parking">Bãi xe</option>
              <option value="other">Khác</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Số tiền ({{ costForm.currency }})</label>
            <input v-model.number="costForm.amount" type="number" min="0" step="1000" required class="costs-input w-full" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Mô tả</label>
            <input v-model="costForm.description" type="text" class="costs-input w-full" />
          </div>
          <div class="md:col-span-2 flex flex-wrap items-center gap-3">
            <button type="submit" class="costs-btn-primary" :disabled="submitting">
              <span v-if="submitting" class="inline-block size-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />
              Gửi chi phí
            </button>
            <span v-if="costMsg" class="text-sm text-slate-600">{{ costMsg }}</span>
          </div>
        </form>
      </div>
    </details>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { listTripCosts, submitTripCost } from '../../api/costs'
import { newIdempotencyKey } from '../../util/idempotency'
import { formatVnd } from '../../util/labels'

const UNIT_LABEL = 'Chi phí vận hành'
const LEGAL_ENTITY_PLACEHOLDER = '—'

const TYPE_LABELS = {
  fuel: 'Xăng / dầu',
  toll: 'Phí cầu đường',
  parking: 'Bãi xe',
  other: 'Khác',
}

const loading = ref(false)
const items = ref([])
const meta = ref({})
const searchQ = ref('')
const filters = reactive({ status: '', trip_id: '', from: '', to: '', page: 1, per_page: 25 })

const costForm = ref({ trip_id: '', type: 'fuel', amount: '', description: '', currency: 'VND' })
const submitting = ref(false)
const costMsg = ref('')

const displayedItems = computed(() => {
  const q = searchQ.value.trim().toLowerCase()
  if (!q) return items.value
  return items.value.filter((c) => {
    const d = String(c.description ?? '').toLowerCase()
    const creator = String(c.creator?.name ?? '').toLowerCase()
    const ty = String(c.type ?? '').toLowerCase()
    const trip = String(c.trip_id ?? '')
    return d.includes(q) || creator.includes(q) || ty.includes(q) || trip.includes(q)
  })
})

function rowIndex(idx) {
  const page = meta.value.current_page ?? 1
  const per = meta.value.per_page ?? 25
  return (page - 1) * per + idx + 1
}

function countOnPage(status) {
  return items.value.filter((c) => c.status === status).length
}

function typeLabel(t) {
  return TYPE_LABELS[t] ?? t ?? '—'
}

function formatDateDMY(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleDateString('vi-VN', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    })
  } catch {
    return '—'
  }
}

async function reload() {
  loading.value = true
  try {
    const p = { ...filters }
    Object.keys(p).forEach((k) => (p[k] === '' ? delete p[k] : null))
    const res = await listTripCosts(p)
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

async function submitCost() {
  costMsg.value = ''
  submitting.value = true
  try {
    await submitTripCost(
      costForm.value.trip_id,
      {
        type: costForm.value.type,
        amount: costForm.value.amount,
        description: costForm.value.description || null,
      },
      { idempotencyKey: newIdempotencyKey() },
    )
    costMsg.value = 'Đã gửi.'
    await reload()
  } catch (e) {
    costMsg.value = e?.response?.data?.message ?? 'Lỗi gửi chi phí.'
  } finally {
    submitting.value = false
  }
}

onMounted(reload)
</script>

<style scoped>
.costs-page {
  --cost-border: rgb(226 232 240);
  --cost-grid: rgb(241 245 249);
}

.costs-input {
  @apply rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-va-800 focus:outline-none focus:ring-2 focus:ring-va-800/20;
}

.costs-btn-primary {
  @apply inline-flex items-center justify-center gap-2 rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-va-900 disabled:cursor-not-allowed disabled:opacity-50;
}

.costs-btn-ghost {
  @apply rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50;
}

.costs-table-wrap {
  @apply max-w-full;
}

.costs-sheet .costs-th,
.costs-sheet .costs-td {
  border: 1px dashed var(--cost-border);
  padding: 0.5rem 0.6rem;
  vertical-align: top;
}

@media (min-width: 640px) {
  .costs-sheet .costs-th,
  .costs-sheet .costs-td {
    padding: 0.55rem 0.75rem;
  }
}

.costs-th {
  background: linear-gradient(to bottom, rgb(241 245 249), rgb(226 232 240 / 0.85));
}

.costs-td--money {
  font-variant-numeric: tabular-nums;
}

.costs-pill {
  @apply inline-flex max-w-full items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-medium text-slate-700 sm:text-xs;
}

.costs-pill--type {
  @apply bg-slate-200/90 text-slate-800;
}
</style>
