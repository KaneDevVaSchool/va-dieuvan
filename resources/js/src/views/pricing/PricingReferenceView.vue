<template>
  <div class="space-y-4">
    <Card title="Bảng giá tham chiếu">
      <p class="mb-4 text-sm text-slate-600">
        Dữ liệu từ hệ thống (seed / cập nhật DB). Cuộn ngang trên điện thoại để xem đủ cột.
      </p>
      <div v-if="loading" class="text-sm text-slate-500">Đang tải…</div>
      <div v-else-if="error" class="text-sm text-rose-600">{{ error }}</div>
      <div v-else class="space-y-6">
        <section>
          <h2 class="mb-2 text-sm font-semibold text-slate-900">1. Xe hành khách (VNĐ)</h2>
          <div class="-mx-4 overflow-x-auto px-4 md:mx-0 md:px-0">
            <table class="min-w-[720px] w-full border-collapse text-left text-xs md:text-sm">
              <thead>
                <tr class="border-b bg-slate-50 text-slate-600">
                  <th class="sticky left-0 z-[1] bg-slate-50 px-2 py-2 font-medium">Gói / tuyến</th>
                  <th class="px-2 py-2 font-medium">7 chỗ</th>
                  <th class="px-2 py-2 font-medium">15</th>
                  <th class="px-2 py-2 font-medium">28</th>
                  <th class="px-2 py-2 font-medium">33</th>
                  <th class="px-2 py-2 font-medium">45</th>
                  <th class="px-2 py-2 font-medium">Lim 9</th>
                  <th class="px-2 py-2 font-medium">Lim 11</th>
                  <th class="px-2 py-2 font-medium">TX tự túc</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in data.passenger_fares" :key="row.id" class="border-b border-slate-100">
                  <td class="sticky left-0 bg-white px-2 py-2 font-medium text-slate-800">{{ row.package_label }}</td>
                  <td class="px-2 py-2 text-slate-700">{{ cellMoney(row.seat_7) }}</td>
                  <td class="px-2 py-2 text-slate-700">{{ cellMoney(row.seat_15) }}</td>
                  <td class="px-2 py-2 text-slate-700">{{ cellMoney(row.seat_28) }}</td>
                  <td class="px-2 py-2 text-slate-700">{{ cellMoney(row.seat_33) }}</td>
                  <td class="px-2 py-2 text-slate-700">{{ cellMoney(row.seat_45) }}</td>
                  <td class="px-2 py-2 text-slate-700">{{ cellMoney(row.limo_9) }}</td>
                  <td class="px-2 py-2 text-slate-700">{{ cellMoney(row.limo_11) }}</td>
                  <td class="px-2 py-2 text-slate-700">{{ cellMoney(row.driver_self_support) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section>
          <h2 class="mb-2 text-sm font-semibold text-slate-900">2. Xe hàng hóa — một chiều (VNĐ)</h2>
          <div class="-mx-4 overflow-x-auto px-4 md:mx-0 md:px-0">
            <table class="min-w-[900px] w-full border-collapse text-left text-xs md:text-sm">
              <thead>
                <tr class="border-b bg-slate-50 text-slate-600">
                  <th class="sticky left-0 z-[1] bg-slate-50 px-2 py-2 font-medium">Lộ trình</th>
                  <th class="px-2 py-2 font-medium">1 kiện</th>
                  <th class="px-2 py-2 font-medium">2–5 kiện</th>
                  <th class="px-2 py-2 font-medium">Van 500kg</th>
                  <th class="px-2 py-2 font-medium">Van 1000kg</th>
                  <th class="px-2 py-2 font-medium">Tải 2000kg</th>
                  <th class="px-2 py-2 font-medium">Bốc xếp/điểm</th>
                  <th class="px-2 py-2 font-medium">Chờ/giờ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in data.cargo_fares" :key="row.id" class="border-b border-slate-100">
                  <td class="sticky left-0 bg-white px-2 py-2">
                    <div class="font-medium text-slate-800">{{ row.route_label }}</div>
                    <div v-if="row.distance_km != null" class="text-[11px] text-slate-500">~{{ row.distance_km }} km</div>
                  </td>
                  <td class="px-2 py-2">{{ formatVnd(row.one_crate_50_40_50) }}</td>
                  <td class="px-2 py-2">{{ formatVnd(row.crates_2_to_5_50_40_50) }}</td>
                  <td class="px-2 py-2">{{ formatVnd(row.van_500kg) }}</td>
                  <td class="px-2 py-2">{{ formatVnd(row.van_1000kg) }}</td>
                  <td class="px-2 py-2">{{ formatVnd(row.van_2000kg) }}</td>
                  <td class="px-2 py-2">{{ formatVnd(row.loading_assist_per_point) }}</td>
                  <td class="px-2 py-2">{{ formatVnd(row.waiting_fee_per_hour) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section>
          <h2 class="mb-2 text-sm font-semibold text-slate-900">Ghi chú &amp; điều khoản</h2>
          <div class="space-y-3">
            <details
              v-for="n in data.notes"
              :key="n.id"
              class="rounded-lg border border-slate-200 bg-white open:bg-slate-50/50"
            >
              <summary class="cursor-pointer list-none px-3 py-2 text-sm font-medium text-slate-900 marker:content-none [&::-webkit-details-marker]:hidden">
                <span class="inline-flex items-center gap-2">
                  <span class="text-slate-400">▸</span>
                  {{ labelPricingNoteCategory(n.category) }}
                  <span v-if="n.title" class="font-normal text-slate-600">— {{ n.title }}</span>
                </span>
              </summary>
              <p class="border-t border-slate-100 px-3 py-2 text-sm text-slate-700 whitespace-pre-wrap">{{ n.body }}</p>
            </details>
          </div>
        </section>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import Card from '../../components/ui/Card.vue'
import { getReferencePricing } from '../../api/pricing'
import { formatVnd, labelPricingNoteCategory } from '../../util/labels'

const loading = ref(true)
const error = ref('')
const data = ref({
  passenger_fares: [],
  cargo_fares: [],
  notes: [],
})

function cellMoney(v) {
  if (v == null || v === '') return '—'
  return formatVnd(v)
}

onMounted(async () => {
  loading.value = true
  error.value = ''
  try {
    data.value = await getReferencePricing()
  } catch (e) {
    error.value = e?.response?.data?.message ?? 'Không tải được bảng giá.'
  } finally {
    loading.value = false
  }
})
</script>
