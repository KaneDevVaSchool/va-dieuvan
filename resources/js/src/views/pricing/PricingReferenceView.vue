<template>
  <div class="space-y-8 pb-10">
    <Card>
      <div class="mb-6 border-b border-slate-100 pb-5">
        <h1 class="text-lg font-semibold tracking-tight text-slate-900 md:text-xl">Bảng giá tham chiếu</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-600">
          Tra cứu nhanh đơn giá xe hành khách và vận chuyển hàng hóa nội bộ. Giá và điều khoản có thể được cập nhật theo quyết định Phòng Điều vận; vui lòng xác nhận với điều vận khi lên kế hoạch chuyến.
        </p>
      </div>

      <div v-if="loading" class="flex items-center gap-2 py-12 text-sm text-slate-500">
        <span
          class="inline-block size-4 animate-spin rounded-full border-2 border-va-200 border-t-va-700"
          aria-hidden="true"
        />
        Đang tải bảng giá…
      </div>
      <div v-else-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        {{ error }}
      </div>
      <div v-else class="space-y-10">
        <!-- 1. Hành khách -->
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm ring-1 ring-slate-900/5">
          <div
            class="flex flex-col gap-1 border-b border-va-800/20 bg-gradient-to-r from-va-800 to-va-700 px-4 py-4 text-white md:flex-row md:items-center md:justify-between md:px-5"
          >
            <div class="flex items-center gap-3">
              <span
                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white/15 text-sm font-bold tabular-nums"
                >1</span
              >
              <div>
                <h2 class="text-base font-semibold">Xe vận tải hành khách</h2>
                <p class="mt-0.5 text-xs font-normal text-white/85">Đơn vị: VNĐ · Giá theo gói / tuyến</p>
              </div>
            </div>
          </div>
          <div class="-mx-px overflow-x-auto">
            <table class="min-w-[880px] w-full border-collapse text-left text-xs md:text-sm">
              <thead>
                <tr class="bg-slate-50 text-slate-700">
                  <th
                    class="sticky left-0 z-[2] min-w-[200px] border-b border-slate-200 bg-slate-50 px-3 py-3 font-semibold shadow-[4px_0_12px_-4px_rgba(15,23,42,0.12)] md:px-4"
                  >
                    Gói / tuyến
                  </th>
                  <th class="border-b border-slate-200 px-2 py-3 text-center font-semibold md:px-3">Xe 7 chỗ</th>
                  <th class="border-b border-slate-200 px-2 py-3 text-center font-semibold md:px-3">Xe 15 chỗ</th>
                  <th class="border-b border-slate-200 px-2 py-3 text-center font-semibold md:px-3">Xe 28 chỗ</th>
                  <th class="border-b border-slate-200 px-2 py-3 text-center font-semibold md:px-3">Xe 33 chỗ</th>
                  <th class="border-b border-slate-200 px-2 py-3 text-center font-semibold md:px-3">
                    <span class="block">Xe 45 chỗ</span>
                    <span class="mt-1 block text-[10px] font-normal leading-snug text-slate-500 md:text-[11px]"
                      >Không đi tuyến vườn trường Hóc Môn</span
                    >
                  </th>
                  <th class="border-b border-slate-200 px-2 py-3 text-center font-semibold md:px-3">Limousine 9 chỗ</th>
                  <th class="border-b border-slate-200 px-2 py-3 text-center font-semibold md:px-3">Limousine 11 chỗ</th>
                  <th
                    class="border-b border-slate-200 bg-emerald-50/90 px-2 py-3 text-center font-semibold text-emerald-900 md:px-3"
                  >
                    Chi phí tự túc TX
                  </th>
                  <th
                    v-if="canEdit"
                    class="border-b border-slate-200 bg-slate-100 px-2 py-3 text-center text-xs font-semibold text-slate-700 md:px-3"
                  >
                    Thao tác
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, idx) in data.passenger_fares"
                  :key="row.id"
                  :class="idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/70'"
                  class="border-b border-slate-100 transition-colors hover:bg-va-50/40"
                >
                  <td
                    class="sticky left-0 z-[1] border-slate-100 bg-inherit px-3 py-2.5 font-medium text-slate-900 shadow-[4px_0_12px_-4px_rgba(15,23,42,0.08)] md:px-4"
                  >
                    {{ row.package_label }}
                  </td>
                  <td class="px-2 py-2.5 text-center tabular-nums text-slate-800 md:px-3">{{ cellMoney(row.seat_7) }}</td>
                  <td class="px-2 py-2.5 text-center tabular-nums text-slate-800 md:px-3">{{ cellMoney(row.seat_15) }}</td>
                  <td class="px-2 py-2.5 text-center tabular-nums text-slate-800 md:px-3">{{ cellMoney(row.seat_28) }}</td>
                  <td class="px-2 py-2.5 text-center tabular-nums text-slate-800 md:px-3">{{ cellMoney(row.seat_33) }}</td>
                  <td class="px-2 py-2.5 text-center tabular-nums text-slate-800 md:px-3">{{ cellMoney(row.seat_45) }}</td>
                  <td class="px-2 py-2.5 text-center tabular-nums text-slate-800 md:px-3">{{ cellMoney(row.limo_9) }}</td>
                  <td class="px-2 py-2.5 text-center tabular-nums text-slate-800 md:px-3">{{ cellMoney(row.limo_11) }}</td>
                  <td
                    class="bg-emerald-50/50 px-2 py-2.5 text-center text-sm font-semibold tabular-nums text-emerald-900 md:px-3"
                  >
                    {{ cellMoney(row.driver_self_support) }}
                  </td>
                  <td v-if="canEdit" class="border-l border-slate-200 bg-slate-50/90 px-2 py-2 text-center md:px-2">
                    <div class="flex flex-col items-stretch gap-1.5">
                      <button
                        type="button"
                        class="rounded border border-slate-200 bg-white px-2 py-1 text-[11px] font-medium text-slate-800 shadow-sm hover:bg-slate-50"
                        @click="openPassengerEdit(row)"
                      >
                        Sửa
                      </button>
                      <button
                        type="button"
                        class="rounded border border-slate-200 bg-white px-2 py-1 text-[11px] font-medium text-slate-600 shadow-sm hover:bg-slate-50"
                        @click="openHistory('passenger_fare_rate', row.id, row.package_label)"
                      >
                        Lịch sử
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Ghi chú xe khách (API) -->
        <div v-if="passengerNoteBlocks.length" class="grid gap-3 md:grid-cols-2">
          <div
            v-for="block in passengerNoteBlocks"
            :key="block.key"
            class="rounded-xl border border-sky-200/80 bg-gradient-to-br from-sky-50 to-white p-4 shadow-sm"
          >
            <div class="mb-2 flex flex-wrap items-start justify-between gap-2">
              <div class="flex items-center gap-2 text-sm font-semibold text-sky-900">
                <span class="size-2 shrink-0 rounded-full bg-sky-500" aria-hidden="true" />
                {{ block.title }}
              </div>
              <div v-if="canEdit && block.id" class="flex gap-1">
                <button
                  type="button"
                  class="rounded border border-sky-300/80 bg-white px-2 py-0.5 text-[11px] font-medium text-sky-900 hover:bg-sky-50"
                  @click="openNoteEdit(block)"
                >
                  Sửa
                </button>
                <button
                  type="button"
                  class="rounded border border-sky-300/80 bg-white px-2 py-0.5 text-[11px] font-medium text-sky-800 hover:bg-sky-50"
                  @click="openHistory('pricing_note', block.id, block.title)"
                >
                  Lịch sử
                </button>
              </div>
            </div>
            <p class="text-sm leading-relaxed text-slate-700 whitespace-pre-wrap">{{ block.body }}</p>
          </div>
        </div>

        <!-- Huỷ xe — bảng cố định -->
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm ring-1 ring-slate-900/5">
          <div
            class="border-b border-amber-200/80 bg-gradient-to-r from-amber-700 to-amber-600 px-4 py-4 text-white md:px-5"
          >
            <h2 class="text-base font-semibold">Trường hợp chi phí huỷ xe (hành khách)</h2>
            <p class="mt-1 text-xs font-normal text-amber-100/95">
              Áp dụng khi thông báo huỷ so với ngày thực hiện dịch vụ và trạng thái điều xe.
            </p>
          </div>
          <div class="-mx-px overflow-x-auto">
            <table class="min-w-[720px] w-full border-collapse text-left text-xs md:text-sm">
              <thead>
                <tr class="bg-amber-50/90 text-amber-950">
                  <th class="border-b border-amber-200/80 px-3 py-3 font-semibold md:px-4">Trường hợp huỷ</th>
                  <th class="border-b border-amber-200/80 px-3 py-3 font-semibold md:px-4">Thời điểm / điều kiện</th>
                  <th class="border-b border-amber-200/80 px-3 py-3 font-semibold md:px-4">Chi phí phát sinh</th>
                  <th class="border-b border-amber-200/80 px-3 py-3 font-semibold md:px-4">Ghi chú</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(r, i) in cancellationRows"
                  :key="i"
                  class="border-b border-slate-100 transition-colors"
                  :class="cancellationRowClass(i)"
                >
                  <td class="px-3 py-3 font-medium text-slate-900 md:px-4">{{ r.case }}</td>
                  <td class="px-3 py-3 text-slate-700 md:px-4">{{ r.when }}</td>
                  <td class="px-3 py-3 font-semibold tabular-nums md:px-4" :class="r.feeClass">{{ r.fee }}</td>
                  <td class="px-3 py-3 text-slate-600 md:px-4">{{ r.note }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- 2. Hàng hóa -->
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm ring-1 ring-slate-900/5">
          <div
            class="flex flex-col gap-1 border-b border-indigo-900/20 bg-gradient-to-r from-indigo-800 to-indigo-700 px-4 py-4 text-white md:flex-row md:items-center md:justify-between md:px-5"
          >
            <div class="flex items-center gap-3">
              <span
                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white/15 text-sm font-bold tabular-nums"
                >2</span
              >
              <div>
                <h2 class="text-base font-semibold">Xe vận chuyển hàng hóa</h2>
                <p class="mt-0.5 text-xs font-normal text-white/85">Đơn vị: VNĐ · Đơn giá một chiều</p>
              </div>
            </div>
          </div>
          <div class="-mx-px overflow-x-auto">
            <table class="min-w-[1100px] w-full border-collapse text-left text-xs md:text-sm">
              <thead>
                <tr class="bg-slate-50 text-slate-700">
                  <th
                    class="sticky left-0 z-[2] min-w-[220px] border-b border-slate-200 bg-slate-50 px-3 py-3 font-semibold shadow-[4px_0_12px_-4px_rgba(15,23,42,0.12)] md:px-4"
                  >
                    Lộ trình
                  </th>
                  <th class="border-b border-slate-200 px-2 py-3 text-center font-semibold md:px-2.5">
                    <span class="block">1 kiện</span>
                    <span class="mt-1 block text-[10px] font-normal leading-snug text-slate-500 md:text-[11px]"
                      >50×40×50 (cm)</span
                    >
                  </th>
                  <th class="border-b border-slate-200 px-2 py-3 text-center font-semibold md:px-2.5">
                    <span class="block">2–5 kiện</span>
                    <span class="mt-1 block text-[10px] font-normal leading-snug text-slate-500 md:text-[11px]"
                      >50×40×50 (cm)</span
                    >
                  </th>
                  <th class="border-b border-slate-200 px-2 py-3 text-center font-semibold md:px-2.5">
                    <span class="block">Xe Van 500kg</span>
                    <span class="mt-1 block text-[10px] font-normal leading-snug text-slate-500 md:text-[11px]"
                      >Thùng 160×120×110 cm</span
                    >
                  </th>
                  <th class="border-b border-slate-200 px-2 py-3 text-center font-semibold md:px-2.5">
                    <span class="block">Xe Van 1000kg</span>
                    <span class="mt-1 block text-[10px] font-normal leading-snug text-slate-500 md:text-[11px]"
                      >Thùng 230×150×140 cm</span
                    >
                  </th>
                  <th class="border-b border-slate-200 px-2 py-3 text-center font-semibold md:px-2.5">
                    <span class="block">Xe tải 2000kg</span>
                    <span class="mt-1 block text-[10px] font-normal leading-snug text-slate-500 md:text-[11px]"
                      >Thùng 350×170×170 cm</span
                    >
                  </th>
                  <th class="border-b border-slate-200 px-2 py-3 text-center font-semibold md:px-2.5">
                    <span class="block">Hỗ trợ bốc xếp</span>
                    <span class="mt-1 block text-[10px] font-normal leading-snug text-slate-500 md:text-[11px]"
                      >1 người / 1 điểm lấy hàng</span
                    >
                  </th>
                  <th class="border-b border-slate-200 bg-violet-50/90 px-2 py-3 text-center font-semibold text-violet-950 md:px-2.5">
                    <span class="block">Phí chờ xe</span>
                    <span class="mt-1 block text-[10px] font-normal leading-snug text-violet-800/90 md:text-[11px]"
                      >Hàng số lượng lớn · Đơn giá/giờ</span
                    >
                  </th>
                  <th
                    v-if="canEdit"
                    class="border-b border-slate-200 bg-slate-100 px-2 py-3 text-center text-xs font-semibold text-slate-700 md:px-2.5"
                  >
                    Thao tác
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, idx) in data.cargo_fares"
                  :key="row.id"
                  :class="idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/70'"
                  class="border-b border-slate-100 transition-colors hover:bg-indigo-50/35"
                >
                  <td
                    class="sticky left-0 z-[1] border-slate-100 bg-inherit px-3 py-2.5 shadow-[4px_0_12px_-4px_rgba(15,23,42,0.08)] md:px-4"
                  >
                    <div class="font-medium text-slate-900">{{ row.route_label }}</div>
                    <div v-if="row.distance_km != null" class="mt-0.5 text-[11px] text-slate-500">
                      ≈ {{ formatDistance(row.distance_km) }} km
                    </div>
                  </td>
                  <td class="px-2 py-2.5 text-center tabular-nums text-slate-800 md:px-2.5">
                    {{ formatVnd(row.one_crate_50_40_50) }}
                  </td>
                  <td class="px-2 py-2.5 text-center tabular-nums text-slate-800 md:px-2.5">
                    {{ formatVnd(row.crates_2_to_5_50_40_50) }}
                  </td>
                  <td class="px-2 py-2.5 text-center tabular-nums text-slate-800 md:px-2.5">
                    {{ formatVnd(row.van_500kg) }}
                  </td>
                  <td class="px-2 py-2.5 text-center tabular-nums text-slate-800 md:px-2.5">
                    {{ formatVnd(row.van_1000kg) }}
                  </td>
                  <td class="px-2 py-2.5 text-center tabular-nums text-slate-800 md:px-2.5">
                    {{ formatVnd(row.van_2000kg) }}
                  </td>
                  <td class="px-2 py-2.5 text-center tabular-nums text-slate-800 md:px-2.5">
                    {{ formatVnd(row.loading_assist_per_point) }}
                  </td>
                  <td
                    class="bg-violet-50/40 px-2 py-2.5 text-center text-sm font-semibold tabular-nums text-violet-950 md:px-2.5"
                  >
                    {{ formatVnd(row.waiting_fee_per_hour) }}
                  </td>
                  <td v-if="canEdit" class="border-l border-slate-200 bg-slate-50/90 px-2 py-2 text-center md:px-2">
                    <div class="flex flex-col items-stretch gap-1.5">
                      <button
                        type="button"
                        class="rounded border border-slate-200 bg-white px-2 py-1 text-[11px] font-medium text-slate-800 shadow-sm hover:bg-slate-50"
                        @click="openCargoEdit(row)"
                      >
                        Sửa
                      </button>
                      <button
                        type="button"
                        class="rounded border border-slate-200 bg-white px-2 py-1 text-[11px] font-medium text-slate-600 shadow-sm hover:bg-slate-50"
                        @click="openHistory('cargo_fare_rate', row.id, row.route_label)"
                      >
                        Lịch sử
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Ghi chú hàng hóa -->
        <div v-if="cargoNoteBlocks.length" class="rounded-xl border border-indigo-200/80 bg-gradient-to-br from-indigo-50 to-white p-4 shadow-sm">
          <div class="mb-2 flex flex-wrap items-start justify-between gap-2">
            <div class="flex items-center gap-2 text-sm font-semibold text-indigo-950">
              <span class="size-2 shrink-0 rounded-full bg-indigo-500" aria-hidden="true" />
              Ghi chú xe hàng hóa
            </div>
            <div v-if="canEdit && cargoNoteBlocks[0]?.id" class="flex gap-1">
              <button
                type="button"
                class="rounded border border-indigo-300/80 bg-white px-2 py-0.5 text-[11px] font-medium text-indigo-950 hover:bg-indigo-50"
                @click="openNoteEdit(cargoNoteBlocks[0])"
              >
                Sửa
              </button>
              <button
                type="button"
                class="rounded border border-indigo-300/80 bg-white px-2 py-0.5 text-[11px] font-medium text-indigo-900 hover:bg-indigo-50"
                @click="openHistory('pricing_note', cargoNoteBlocks[0].id, 'Ghi chú hàng hóa')"
              >
                Lịch sử
              </button>
            </div>
          </div>
          <ul class="list-inside list-disc space-y-2 text-sm leading-relaxed text-slate-700">
            <li v-for="(block, i) in cargoNoteBlocks" :key="block.id ?? i" class="whitespace-pre-wrap pl-0.5 marker:text-indigo-400">
              {{ block.body }}
            </li>
          </ul>
        </div>

        <!-- Ghi chú khác từ hệ thống (nếu có category lạ) -->
        <section v-if="otherNotes.length">
          <h3 class="mb-3 text-sm font-semibold text-slate-800">Thông tin bổ sung</h3>
          <div class="space-y-3">
            <details
              v-for="n in otherNotes"
              :key="n.id"
              class="rounded-lg border border-slate-200 bg-white open:bg-slate-50/50"
            >
              <summary
                class="cursor-pointer list-none px-3 py-2 text-sm font-medium text-slate-900 marker:content-none [&::-webkit-details-marker]:hidden"
              >
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

    <Modal :open="passengerEditOpen" wide title="Sửa giá — xe hành khách" @close="passengerEditOpen = false">
      <form class="space-y-3" @submit.prevent="savePassengerEdit">
        <label class="block text-xs font-medium text-slate-700">
          Gói / tuyến
          <input
            v-model="passengerForm.package_label"
            type="text"
            class="mt-1 w-full rounded border border-slate-200 px-2 py-1.5 text-sm"
            required
          />
        </label>
        <label class="block text-xs font-medium text-slate-700">
          Thứ tự hiển thị
          <input
            v-model.number="passengerForm.sort_order"
            type="number"
            min="0"
            class="mt-1 w-full rounded border border-slate-200 px-2 py-1.5 text-sm"
          />
        </label>
        <div class="grid grid-cols-2 gap-2 md:grid-cols-3">
          <label v-for="f in passengerMoneyFields" :key="f.key" class="block text-[11px] font-medium text-slate-600">
            {{ f.label }}
            <input
              v-model="passengerForm[f.key]"
              type="number"
              min="0"
              step="1000"
              class="mt-0.5 w-full rounded border border-slate-200 px-2 py-1 text-sm tabular-nums"
            />
          </label>
        </div>
        <p class="text-[11px] text-slate-500">Để trống cột giá = không áp dụng (—). Mỗi lần lưu hệ thống ghi nhận bản snapshot trước đó vào lịch sử.</p>
        <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
          <button type="button" class="rounded border border-slate-200 px-3 py-1.5 text-sm" @click="passengerEditOpen = false">
            Huỷ
          </button>
          <button
            type="submit"
            class="rounded bg-va-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-va-900 disabled:opacity-50"
            :disabled="savePassengerLoading"
          >
            {{ savePassengerLoading ? 'Đang lưu…' : 'Lưu' }}
          </button>
        </div>
      </form>
    </Modal>

    <Modal :open="cargoEditOpen" wide title="Sửa giá — hàng hóa" @close="cargoEditOpen = false">
      <form class="space-y-3" @submit.prevent="saveCargoEdit">
        <label class="block text-xs font-medium text-slate-700">
          Lộ trình
          <input
            v-model="cargoForm.route_label"
            type="text"
            class="mt-1 w-full rounded border border-slate-200 px-2 py-1.5 text-sm"
            required
          />
        </label>
        <label class="block text-xs font-medium text-slate-700">
          Khoảng cách (km, tuỳ chọn)
          <input
            v-model="cargoForm.distance_km"
            type="text"
            class="mt-1 w-full rounded border border-slate-200 px-2 py-1.5 text-sm"
            placeholder="Để trống nếu không cố định"
          />
        </label>
        <label class="block text-xs font-medium text-slate-700">
          Thứ tự hiển thị
          <input
            v-model.number="cargoForm.sort_order"
            type="number"
            min="0"
            class="mt-1 w-full rounded border border-slate-200 px-2 py-1.5 text-sm"
          />
        </label>
        <div class="grid grid-cols-2 gap-2 md:grid-cols-3">
          <label v-for="f in cargoMoneyFields" :key="f.key" class="block text-[11px] font-medium text-slate-600">
            {{ f.label }}
            <input
              v-model="cargoForm[f.key]"
              type="number"
              min="0"
              step="1000"
              class="mt-0.5 w-full rounded border border-slate-200 px-2 py-1 text-sm tabular-nums"
            />
          </label>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
          <button type="button" class="rounded border border-slate-200 px-3 py-1.5 text-sm" @click="cargoEditOpen = false">
            Huỷ
          </button>
          <button
            type="submit"
            class="rounded bg-indigo-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-900 disabled:opacity-50"
            :disabled="saveCargoLoading"
          >
            {{ saveCargoLoading ? 'Đang lưu…' : 'Lưu' }}
          </button>
        </div>
      </form>
    </Modal>

    <Modal :open="noteEditOpen" title="Sửa ghi chú" @close="noteEditOpen = false">
      <form class="space-y-3" @submit.prevent="saveNoteEdit">
        <label class="block text-xs font-medium text-slate-700">
          Tiêu đề
          <input v-model="noteForm.title" type="text" class="mt-1 w-full rounded border border-slate-200 px-2 py-1.5 text-sm" />
        </label>
        <label class="block text-xs font-medium text-slate-700">
          Nội dung
          <textarea
            v-model="noteForm.body"
            rows="8"
            class="mt-1 w-full rounded border border-slate-200 px-2 py-1.5 text-sm"
            required
          />
        </label>
        <label class="block text-xs font-medium text-slate-700">
          Thứ tự
          <input
            v-model.number="noteForm.sort_order"
            type="number"
            min="0"
            class="mt-1 w-full rounded border border-slate-200 px-2 py-1.5 text-sm"
          />
        </label>
        <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
          <button type="button" class="rounded border border-slate-200 px-3 py-1.5 text-sm" @click="noteEditOpen = false">
            Huỷ
          </button>
          <button
            type="submit"
            class="rounded bg-slate-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-900 disabled:opacity-50"
            :disabled="saveNoteLoading"
          >
            {{ saveNoteLoading ? 'Đang lưu…' : 'Lưu' }}
          </button>
        </div>
      </form>
    </Modal>

    <Modal :open="historyOpen" wide :title="historyTitle" @close="historyOpen = false">
      <div v-if="historyLoading" class="py-8 text-center text-sm text-slate-500">Đang tải lịch sử…</div>
      <div v-else class="space-y-3">
        <p v-if="!historyItems.length" class="text-sm text-slate-600">Chưa có phiên chỉnh sửa nào được lưu (snapshot trước mỗi lần cập nhật).</p>
        <div
          v-for="rev in historyItems"
          :key="rev.id"
          class="rounded-lg border border-slate-200 bg-slate-50/50 p-3 text-sm shadow-sm"
        >
          <div class="flex flex-wrap items-baseline justify-between gap-2 text-xs text-slate-600">
            <span class="font-medium text-slate-800">{{ formatHistoryDate(rev.created_at) }}</span>
            <span>{{ rev.user?.name ?? '—' }} · {{ rev.user?.email ?? '' }}</span>
          </div>
          <pre
            class="mt-2 max-h-56 overflow-auto rounded border border-slate-200 bg-white p-2 text-[11px] leading-relaxed text-slate-800"
            >{{ formatHistorySnapshot(rev.snapshot) }}</pre
          >
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import Card from '../../components/ui/Card.vue'
import Modal from '../../components/ui/Modal.vue'
import {
  getReferencePricing,
  getReferencePricingRevisions,
  updatePassengerFare,
  updateCargoFare,
  updatePricingNote,
} from '../../api/pricing'
import { formatVnd, labelPricingNoteCategory } from '../../util/labels'
import { useAuthStore } from '../../store'
import { showAppError, showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'

const auth = useAuthStore()
const canEdit = computed(() => auth.hasPermission('reference_pricing.manage'))

const loading = ref(true)
const error = ref('')
const data = ref({
  passenger_fares: [],
  cargo_fares: [],
  notes: [],
})

const passengerMoneyFields = [
  { key: 'seat_7', label: 'Xe 7 chỗ' },
  { key: 'seat_15', label: 'Xe 15 chỗ' },
  { key: 'seat_28', label: 'Xe 28 chỗ' },
  { key: 'seat_33', label: 'Xe 33 chỗ' },
  { key: 'seat_45', label: 'Xe 45 chỗ' },
  { key: 'limo_9', label: 'Limousine 9' },
  { key: 'limo_11', label: 'Limousine 11' },
  { key: 'driver_self_support', label: 'TX tự túc' },
]

const cargoMoneyFields = [
  { key: 'one_crate_50_40_50', label: '1 kiện' },
  { key: 'crates_2_to_5_50_40_50', label: '2–5 kiện' },
  { key: 'van_500kg', label: 'Van 500kg' },
  { key: 'van_1000kg', label: 'Van 1000kg' },
  { key: 'van_2000kg', label: 'Tải 2000kg' },
  { key: 'loading_assist_per_point', label: 'Bốc xếp/điểm' },
  { key: 'waiting_fee_per_hour', label: 'Phí chờ/giờ' },
]

const passengerEditOpen = ref(false)
const savePassengerLoading = ref(false)
const passengerForm = ref(emptyPassengerForm())

function emptyPassengerForm() {
  const o = { id: null, package_label: '', sort_order: 0 }
  for (const f of passengerMoneyFields) {
    o[f.key] = ''
  }
  return o
}

const cargoEditOpen = ref(false)
const saveCargoLoading = ref(false)
const cargoForm = ref(emptyCargoForm())

function emptyCargoForm() {
  const o = {
    id: null,
    route_label: '',
    distance_km: '',
    sort_order: 0,
  }
  for (const f of cargoMoneyFields) {
    o[f.key] = ''
  }
  return o
}

const noteEditOpen = ref(false)
const saveNoteLoading = ref(false)
const noteForm = ref({ id: null, title: '', body: '', sort_order: 0 })

const historyOpen = ref(false)
const historyLoading = ref(false)
const historyTitle = ref('Lịch sử thay đổi')
const historyItems = ref([])

/** Bảng huỷ xe — nội dung chuẩn theo quy định nội bộ (đồng bộ với ReferencePricingSeeder). */
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
    note: 'Ví dụ: thiên tai, sự cố bất ngờ, quyết định hành chính,…',
  },
]

function cancellationRowClass(index) {
  const tones = ['bg-emerald-50/50', 'bg-amber-50/40', 'bg-rose-50/45', 'bg-slate-50/80']
  return `${tones[index] ?? ''} hover:brightness-[0.99]`
}

const passengerNoteBlocks = computed(() => {
  const notes = data.value.notes ?? []
  return ['passenger_general', 'passenger_driver']
    .map((cat) => {
      const n = notes.find((x) => x.category === cat)
      if (!n) return null
      return {
        key: cat,
        id: n.id,
        title: n.title || (cat === 'passenger_general' ? 'Ghi chú chung' : 'Tài xế'),
        body: n.body,
        sort_order: n.sort_order,
      }
    })
    .filter(Boolean)
})

const cargoNoteBlocks = computed(() => (data.value.notes ?? []).filter((n) => n.category === 'cargo_general'))

/** Ẩn passenger_cancel khỏi UI vì đã hiển thị bảng huỷ xe cố định phía trên. */
const otherNotes = computed(() =>
  (data.value.notes ?? []).filter(
    (n) => !['passenger_general', 'passenger_driver', 'passenger_cancel', 'cargo_general'].includes(n.category),
  ),
)

function cellMoney(v) {
  if (v == null || v === '') return '—'
  return formatVnd(v)
}

function formatDistance(km) {
  const n = Number(km)
  if (Number.isInteger(n)) return String(n)
  return n.toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 1 })
}

function numOrNull(v) {
  if (v === '' || v === null || v === undefined) return null
  const n = Number(v)
  return Number.isFinite(n) ? n : null
}

function formatHistoryDate(iso) {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString('vi-VN', { dateStyle: 'short', timeStyle: 'short' })
  } catch {
    return String(iso)
  }
}

function formatHistorySnapshot(snap) {
  if (snap == null) return '—'
  try {
    return JSON.stringify(snap, null, 2)
  } catch {
    return String(snap)
  }
}

function openPassengerEdit(row) {
  const f = emptyPassengerForm()
  f.id = row.id
  f.package_label = row.package_label ?? ''
  f.sort_order = row.sort_order ?? 0
  for (const x of passengerMoneyFields) {
    const v = row[x.key]
    f[x.key] = v != null && v !== '' ? String(v) : ''
  }
  passengerForm.value = f
  passengerEditOpen.value = true
}

async function savePassengerEdit() {
  const f = passengerForm.value
  if (!f.id) return
  savePassengerLoading.value = true
  try {
    const payload = {
      package_label: f.package_label,
      sort_order: Number(f.sort_order) || 0,
    }
    for (const x of passengerMoneyFields) {
      payload[x.key] = numOrNull(f[x.key])
    }
    const updated = await updatePassengerFare(f.id, payload)
    const list = data.value.passenger_fares ?? []
    const idx = list.findIndex((r) => r.id === f.id)
    if (idx >= 0) list[idx] = updated
    passengerEditOpen.value = false
    showAppSuccess('Đã cập nhật và lưu lịch sử phiên bản trước đó.', 'Đã lưu')
  } catch (e) {
    showAppErrorFromApi(e)
  } finally {
    savePassengerLoading.value = false
  }
}

function openCargoEdit(row) {
  const f = emptyCargoForm()
  f.id = row.id
  f.route_label = row.route_label ?? ''
  f.distance_km = row.distance_km != null && row.distance_km !== '' ? String(row.distance_km) : ''
  f.sort_order = row.sort_order ?? 0
  for (const x of cargoMoneyFields) {
    const v = row[x.key]
    f[x.key] = v != null && v !== '' ? String(v) : ''
  }
  cargoForm.value = f
  cargoEditOpen.value = true
}

async function saveCargoEdit() {
  const f = cargoForm.value
  if (!f.id) return
  saveCargoLoading.value = true
  try {
    const payload = {
      route_label: f.route_label,
      sort_order: Number(f.sort_order) || 0,
      distance_km:
        f.distance_km === '' || f.distance_km == null ? null : Number(String(f.distance_km).replace(',', '.')),
    }
    if (payload.distance_km != null && !Number.isFinite(payload.distance_km)) {
      showAppError('Nhập khoảng cách (km) hợp lệ hoặc để trống.')
      return
    }
    for (const x of cargoMoneyFields) {
      payload[x.key] = numOrNull(f[x.key])
    }
    const updated = await updateCargoFare(f.id, payload)
    const list = data.value.cargo_fares ?? []
    const idx = list.findIndex((r) => r.id === f.id)
    if (idx >= 0) list[idx] = updated
    cargoEditOpen.value = false
    showAppSuccess('Đã cập nhật và lưu lịch sử phiên bản trước đó.', 'Đã lưu')
  } catch (e) {
    showAppErrorFromApi(e)
  } finally {
    saveCargoLoading.value = false
  }
}

function openNoteEdit(block) {
  noteForm.value = {
    id: block.id,
    title: block.title ?? '',
    body: block.body ?? '',
    sort_order: block.sort_order ?? 0,
  }
  noteEditOpen.value = true
}

async function saveNoteEdit() {
  const f = noteForm.value
  if (!f.id) return
  saveNoteLoading.value = true
  try {
    const updated = await updatePricingNote(f.id, {
      title: f.title || null,
      body: f.body,
      sort_order: Number(f.sort_order) || 0,
    })
    const notes = data.value.notes ?? []
    const idx = notes.findIndex((n) => n.id === f.id)
    if (idx >= 0) notes[idx] = updated
    noteEditOpen.value = false
    showAppSuccess('Đã cập nhật ghi chú và lưu lịch sử.', 'Đã lưu')
  } catch (e) {
    showAppErrorFromApi(e)
  } finally {
    saveNoteLoading.value = false
  }
}

async function openHistory(type, id, label) {
  historyTitle.value = label ? `Lịch sử — ${label}` : 'Lịch sử thay đổi'
  historyItems.value = []
  historyOpen.value = true
  historyLoading.value = true
  try {
    historyItems.value = await getReferencePricingRevisions(type, id)
  } catch (e) {
    showAppErrorFromApi(e)
    historyOpen.value = false
  } finally {
    historyLoading.value = false
  }
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
