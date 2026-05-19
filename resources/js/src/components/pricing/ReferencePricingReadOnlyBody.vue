<template>
  <div class="pricing-readonly text-slate-900">
    <!-- Bảng dữ liệu dùng chung PricingReferenceView; đây là bản tra cứu không chỉnh sửa. -->
    <div v-if="loading" class="flex items-center gap-3 py-10 text-sm text-slate-600">
      <span
        class="inline-block size-5 shrink-0 animate-spin rounded-full border-2 border-teal-200 border-t-teal-600"
        aria-hidden="true"
      />
      {{ t('request_detail.reference_pricing_loading') }}
    </div>
    <div v-else-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-3 text-sm text-rose-800">
      {{ error }}
    </div>
    <div v-else class="space-y-6 pb-1">
      <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/5">
        <div class="border-b border-teal-800/25 bg-gradient-to-r from-teal-800 to-teal-700 px-3 py-3 text-white md:px-4">
          <h2 class="text-sm font-semibold md:text-base">1 · Xe vận tải hành khách</h2>
          <p class="mt-0.5 text-[11px] font-normal text-white/85 md:text-xs">Đơn vị: VNĐ · Giá theo gói / tuyến</p>
        </div>
        <div class="-mx-px overflow-x-auto">
          <table class="w-full min-w-[880px] border-collapse text-left text-[11px] md:text-sm">
            <thead>
              <tr class="bg-slate-50 text-slate-700">
                <th
                  class="sticky left-0 z-[2] min-w-[180px] border-b border-slate-200 bg-slate-50 px-2 py-2 font-semibold shadow-[4px_0_12px_-4px_rgba(15,23,42,0.12)] md:px-3"
                >
                  Gói / tuyến
                </th>
                <th class="border-b border-slate-200 px-1.5 py-2 text-center font-semibold md:px-2">Xe 7 chỗ</th>
                <th class="border-b border-slate-200 px-1.5 py-2 text-center font-semibold md:px-2">Xe 15 chỗ</th>
                <th class="border-b border-slate-200 px-1.5 py-2 text-center font-semibold md:px-2">Xe 28 chỗ</th>
                <th class="border-b border-slate-200 px-1.5 py-2 text-center font-semibold md:px-2">Xe 33 chỗ</th>
                <th class="border-b border-slate-200 px-1.5 py-2 text-center font-semibold md:px-2">
                  Xe 45 chỗ <span class="mt-1 block font-normal text-[10px] text-slate-500">(KH đi HM)</span>
                </th>
                <th class="border-b border-slate-200 px-1.5 py-2 text-center font-semibold md:px-2">Limousine 9</th>
                <th class="border-b border-slate-200 px-1.5 py-2 text-center font-semibold md:px-2">Limousine 11</th>
                <th
                  class="border-b border-slate-200 bg-emerald-50/95 px-1.5 py-2 text-center font-semibold text-emerald-900 md:px-2"
                >
                  Chi phí tự túc TX
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in passengerFares"
                :key="row.id"
                :class="idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/70'"
                class="border-b border-slate-100"
              >
                <td
                  class="sticky left-0 z-[1] border-slate-100 bg-inherit px-2 py-2 font-medium md:px-3"
                  :style="{ boxShadow: '4px 0 12px -4px rgba(15,23,42,0.08)' }"
                >
                  {{ row.package_label }}
                </td>
                <td class="px-1.5 py-2 text-center tabular-nums md:px-2">{{ fmtMoney(row.seat_7) }}</td>
                <td class="px-1.5 py-2 text-center tabular-nums md:px-2">{{ fmtMoney(row.seat_15) }}</td>
                <td class="px-1.5 py-2 text-center tabular-nums md:px-2">{{ fmtMoney(row.seat_28) }}</td>
                <td class="px-1.5 py-2 text-center tabular-nums md:px-2">{{ fmtMoney(row.seat_33) }}</td>
                <td class="px-1.5 py-2 text-center tabular-nums md:px-2">{{ fmtMoney(row.seat_45) }}</td>
                <td class="px-1.5 py-2 text-center tabular-nums md:px-2">{{ fmtMoney(row.limo_9) }}</td>
                <td class="px-1.5 py-2 text-center tabular-nums md:px-2">{{ fmtMoney(row.limo_11) }}</td>
                <td class="bg-emerald-50/40 px-1.5 py-2 text-center text-sm font-semibold tabular-nums text-emerald-900 md:px-2">
                  {{ fmtMoney(row.driver_self_support) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div v-if="passengerNoteBlocks.length" class="grid gap-2 sm:gap-3 md:grid-cols-2">
        <div
          v-for="block in passengerNoteBlocks"
          :key="block.key"
          class="rounded-xl border border-sky-200/70 bg-gradient-to-br from-sky-50 to-white p-3 shadow-sm sm:p-4"
        >
          <div class="mb-1.5 flex items-center gap-2 text-xs font-semibold text-sky-900 sm:text-sm">
            <span class="size-1.5 shrink-0 rounded-full bg-sky-500" aria-hidden="true" />
            {{ block.title }}
          </div>
          <p class="text-xs leading-relaxed text-slate-700 whitespace-pre-wrap sm:text-sm">{{ block.body }}</p>
        </div>
      </div>

      <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/5">
        <div class="border-b border-amber-200/70 bg-gradient-to-r from-amber-700 to-amber-600 px-3 py-3 text-white md:px-4">
          <h2 class="text-sm font-semibold md:text-base">Chi phí huỷ xe (hành khách)</h2>
          <p class="mt-0.5 text-[11px] text-amber-100/95">Tham chiếu nội bộ</p>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-[600px] w-full border-collapse text-left text-[11px] md:text-sm">
            <thead>
              <tr class="bg-amber-50 text-amber-950">
                <th class="border-b border-amber-200 px-2 py-2 font-semibold md:px-3">Trường hợp</th>
                <th class="border-b border-amber-200 px-2 py-2 font-semibold md:px-3">Điều kiện</th>
                <th class="border-b border-amber-200 px-2 py-2 font-semibold md:px-3">Chi phí</th>
                <th class="border-b border-amber-200 px-2 py-2 font-semibold md:px-3">Ghi chú</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(r, i) in cancellationRows"
                :key="i"
                class="border-b border-slate-100"
                :class="cancellationRowClass(i)"
              >
                <td class="px-2 py-2 font-medium md:px-3">{{ r.case }}</td>
                <td class="px-2 py-2 text-slate-700 md:px-3">{{ r.when }}</td>
                <td class="px-2 py-2 font-semibold md:px-3" :class="r.feeClass">{{ r.fee }}</td>
                <td class="px-2 py-2 text-slate-600 md:px-3">{{ r.note }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm ring-1 ring-slate-900/5">
        <div class="border-b border-indigo-900/20 bg-gradient-to-r from-indigo-800 to-indigo-700 px-3 py-3 text-white md:px-4">
          <h2 class="text-sm font-semibold md:text-base">2 · Xe vận chuyển hàng hóa</h2>
          <p class="mt-0.5 text-[11px] text-white/85 md:text-xs">Đơn vị: VNĐ · Một chiều</p>
        </div>
        <div class="-mx-px overflow-x-auto">
          <table class="w-full min-w-[1000px] border-collapse text-left text-[11px] md:text-sm">
            <thead>
              <tr class="bg-slate-50 text-slate-700">
                <th
                  class="sticky left-0 z-[2] min-w-[200px] border-b border-slate-200 bg-slate-50 px-2 py-2 font-semibold shadow-[4px_0_12px_-4px_rgba(15,23,42,0.12)] md:px-3"
                >
                  Lộ trình
                </th>
                <th class="border-b border-slate-200 px-1.5 py-2 text-center font-semibold md:px-2">1 kiện</th>
                <th class="border-b border-slate-200 px-1.5 py-2 text-center font-semibold md:px-2">2–5 kiện</th>
                <th class="border-b border-slate-200 px-1.5 py-2 text-center font-semibold md:px-2">Van 500kg</th>
                <th class="border-b border-slate-200 px-1.5 py-2 text-center font-semibold md:px-2">Van 1000kg</th>
                <th class="border-b border-slate-200 px-1.5 py-2 text-center font-semibold md:px-2">Tải 2000kg</th>
                <th class="border-b border-slate-200 px-1.5 py-2 text-center font-semibold md:px-2">Bốc xếp/điểm</th>
                <th
                  class="border-b border-slate-200 bg-violet-50/95 px-1.5 py-2 text-center font-semibold text-violet-950 md:px-2"
                >
                  Phí chờ/giờ
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in cargoFares"
                :key="row.id"
                :class="idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/70'"
                class="border-b border-slate-100"
              >
                <td
                  class="sticky left-0 z-[1] border-slate-100 bg-inherit px-2 py-2 md:px-3"
                  :style="{ boxShadow: '4px 0 12px -4px rgba(15,23,42,0.08)' }"
                >
                  <div class="font-medium">{{ row.route_label }}</div>
                  <div v-if="row.distance_km != null" class="mt-0.5 text-[10px] text-slate-500 md:text-[11px]">
                    ≈ {{ formatDist(row.distance_km) }} km
                  </div>
                </td>
                <td class="px-1.5 py-2 text-center tabular-nums md:px-2">{{ fmtMoney(row.one_crate_50_40_50) }}</td>
                <td class="px-1.5 py-2 text-center tabular-nums md:px-2">{{ fmtMoney(row.crates_2_to_5_50_40_50) }}</td>
                <td class="px-1.5 py-2 text-center tabular-nums md:px-2">{{ fmtMoney(row.van_500kg) }}</td>
                <td class="px-1.5 py-2 text-center tabular-nums md:px-2">{{ fmtMoney(row.van_1000kg) }}</td>
                <td class="px-1.5 py-2 text-center tabular-nums md:px-2">{{ fmtMoney(row.van_2000kg) }}</td>
                <td class="px-1.5 py-2 text-center tabular-nums md:px-2">{{ fmtMoney(row.loading_assist_per_point) }}</td>
                <td class="bg-violet-50/35 px-1.5 py-2 text-center font-semibold tabular-nums text-violet-950 md:px-2">
                  {{ fmtMoney(row.waiting_fee_per_hour) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div v-if="cargoNoteBlocks.length" class="rounded-xl border border-indigo-200/75 bg-gradient-to-br from-indigo-50 to-white p-3 shadow-sm sm:p-4">
        <div class="mb-2 flex items-center gap-2 text-xs font-semibold text-indigo-950 sm:text-sm">
          <span class="size-1.5 shrink-0 rounded-full bg-indigo-500" aria-hidden="true" />
          Ghi chú xe hàng hóa
        </div>
        <ul class="list-inside list-disc space-y-1.5 text-xs leading-relaxed text-slate-700 sm:text-sm">
          <li
            v-for="(block, i) in cargoNoteBlocks"
            :key="block.id ?? i"
            class="whitespace-pre-wrap pl-0.5 marker:text-indigo-400"
          >
            {{ block.body }}
          </li>
        </ul>
      </div>

      <section v-if="otherNotes.length">
        <h3 class="mb-2 text-xs font-semibold text-slate-800 sm:text-sm">Thông tin bổ sung</h3>
        <details
          v-for="n in otherNotes"
          :key="n.id"
          class="mb-2 rounded-lg border border-slate-200 bg-white last:mb-0 open:bg-slate-50/60"
        >
          <summary
            class="cursor-pointer list-none px-2 py-2 text-[11px] font-medium marker:content-none sm:px-3 sm:text-sm [&::-webkit-details-marker]:hidden"
          >
            <span class="inline-flex items-center gap-1.5">
              <span class="text-slate-400">▸</span>
              {{ labelPricingNoteCategory(n.category) }}
              <span v-if="n.title" class="font-normal text-slate-600">— {{ n.title }}</span>
            </span>
          </summary>
          <p class="border-t border-slate-100 px-2 py-2 text-[11px] text-slate-700 whitespace-pre-wrap sm:px-3 sm:text-sm">
            {{ n.body }}
          </p>
        </details>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { formatVnd, labelPricingNoteCategory } from '../../util/labels'

const props = defineProps({
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
  passengerFares: { type: Array, default: () => [] },
  cargoFares: { type: Array, default: () => [] },
  notes: { type: Array, default: () => [] },
})

const { t } = useI18n()

const cancellationRows = [
  {
    case: 'Huỷ xe trước ngày thực hiện dịch vụ',
    when: 'Có thông báo trước (tối thiểu 12 giờ làm việc)',
    fee: 'Không tính phí',
    feeClass: 'text-emerald-800',
    note: 'Báo huỷ hợp lệ',
  },
  {
    case: 'Huỷ xe trước ngày thực hiện dịch vụ',
    when: 'Thông báo muộn (dưới 12 giờ làm việc)',
    fee: '50% giá trị chuyến xe',
    feeClass: 'text-amber-800',
    note: 'Đã chốt lịch nhưng chưa điều xe',
  },
  {
    case: 'Huỷ khi Bên A đã điều xe đến điểm đón',
    when: 'Xe đã di chuyển hoặc có mặt tại điểm đón',
    fee: '70% giá trị chuyến xe',
    feeClass: 'text-rose-800',
    note: 'Tính phí theo thực tế điều động',
  },
  {
    case: 'Huỷ do sự kiện bất khả kháng',
    when: 'Có chứng minh bằng văn bản (hoặc thông tin xác nhận)',
    fee: 'Hai bên cùng rà soát, không tính phí',
    feeClass: 'text-slate-800',
    note: 'Ví dụ: thiên tai, sự cố bất ngờ, …',
  },
]

function cancellationRowClass(index) {
  const tones = ['bg-emerald-50/45', 'bg-amber-50/40', 'bg-rose-50/40', 'bg-slate-50/80']
  return `${tones[index] ?? ''} hover:bg-slate-50/70`
}

function fmtMoney(v) {
  if (v == null || v === '') return '—'
  return formatVnd(v)
}

function formatDist(km) {
  const n = Number(km)
  if (!Number.isFinite(n)) return '—'
  if (Number.isInteger(n)) return String(n)
  return n.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 1 })
}

const passengerNoteBlocks = computed(() => {
  const notes = props.notes ?? []
  return ['passenger_general', 'passenger_driver']
    .map((cat) => {
      const n = notes.find((x) => x.category === cat)
      if (!n) return null
      return {
        key: cat,
        id: n.id,
        title: n.title || (cat === 'passenger_general' ? 'Ghi chú chung' : 'Tài xế'),
        body: n.body,
      }
    })
    .filter(Boolean)
})

const cargoNoteBlocks = computed(() => (props.notes ?? []).filter((n) => n.category === 'cargo_general'))

const otherNotes = computed(() =>
  (props.notes ?? []).filter(
    (n) => !['passenger_general', 'passenger_driver', 'passenger_cancel', 'cargo_general'].includes(n.category),
  ),
)
</script>