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
        <div
          v-if="!hasFareRows"
          class="rounded-xl border border-amber-200/90 bg-amber-50/80 px-4 py-3 text-sm text-amber-950"
          role="status"
          data-testid="pricing-reference-empty"
        >
          Chưa có dòng giá hành khách hoặc hàng hóa trên hệ thống. Nếu bạn vừa nhập dữ liệu, hãy tải lại trang (Ctrl+F5) để bỏ bản cache cũ; liên hệ quản trị nếu vẫn trống.
          <button
            type="button"
            class="ml-2 font-semibold text-amber-900 underline decoration-amber-600/40 underline-offset-2 hover:text-amber-950"
            data-testid="pricing-reference-reload"
            @click="reloadPricing"
          >
            Tải lại
          </button>
        </div>
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
                        @click="openHistory('passenger_fare_rate', row.id, row.package_label, row)"
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
                  @click="openHistory('pricing_note', block.id, block.title, block)"
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
                        @click="openHistory('cargo_fare_rate', row.id, row.route_label, row)"
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
                @click="openHistory('pricing_note', cargoNoteBlocks[0].id, 'Ghi chú hàng hóa', cargoNoteBlocks[0])"
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

    <Modal
      :open="passengerEditOpen"
      wide
      title="Sửa giá — xe hành khách"
      description="Các trường có dấu * là bắt buộc. Giá để trống nghĩa là không áp dụng (hiển thị — trên bảng)."
      @close="passengerEditOpen = false"
    >
      <form class="space-y-5" @submit.prevent="savePassengerEdit">
        <div class="grid gap-4 sm:grid-cols-2">
          <label :class="labelClass">
            <span>Gói / tuyến <span class="text-rose-600" aria-hidden="true">*</span></span>
            <input
              v-model="passengerForm.package_label"
              type="text"
              :class="fieldInputClass"
              placeholder="Ví dụ: TPHCM/4h/50km hoặc TPHCM - Cơ sở Vũng Tàu (1 ngày)"
              required
              autocomplete="off"
            />
          </label>
          <label :class="labelClass">
            <span>Thứ tự hiển thị</span>
            <input
              v-model.number="passengerForm.sort_order"
              type="number"
              min="0"
              :class="fieldInputClass"
              placeholder="Số nhỏ hiển thị trước (ví dụ: 10, 20)"
            />
          </label>
        </div>
        <div>
          <p class="mb-2 text-xs font-medium text-slate-600">Đơn giá theo loại xe (VNĐ)</p>
          <p class="mb-3 text-[11px] leading-relaxed text-slate-500">
            Nhập số nguyên (đồng). Để trống nếu không áp dụng cho gói này.
          </p>
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
            <label v-for="f in passengerMoneyFields" :key="f.key" :class="labelClass">
              <span>{{ f.label }}</span>
              <input
                v-model="passengerForm[f.key]"
                type="number"
                min="0"
                step="1000"
                :class="fieldInputClass"
                :placeholder="`Ví dụ: 1296000 hoặc để trống`"
                inputmode="numeric"
              />
            </label>
          </div>
        </div>
        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
            @click="passengerEditOpen = false"
          >
            Huỷ
          </button>
          <button
            type="submit"
            class="rounded-lg bg-va-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-va-900 disabled:opacity-50"
            :disabled="savePassengerLoading"
          >
            {{ savePassengerLoading ? 'Đang lưu…' : 'Lưu thay đổi' }}
          </button>
        </div>
      </form>
    </Modal>

    <Modal
      :open="cargoEditOpen"
      wide
      title="Sửa giá — hàng hóa"
      description="Các trường có dấu * là bắt buộc. Khoảng cách có thể để trống nếu lộ trình không gắn số km cố định."
      @close="cargoEditOpen = false"
    >
      <form class="space-y-5" @submit.prevent="saveCargoEdit">
        <div class="grid gap-4 sm:grid-cols-2">
          <label class="sm:col-span-2" :class="labelClass">
            <span>Lộ trình <span class="text-rose-600" aria-hidden="true">*</span></span>
            <input
              v-model="cargoForm.route_label"
              type="text"
              :class="fieldInputClass"
              placeholder="Ví dụ: VA Tân Bình - VA Bình Thới (3 km)"
              required
              autocomplete="off"
            />
          </label>
          <label :class="labelClass">
            <span>Khoảng cách (km)</span>
            <input
              v-model="cargoForm.distance_km"
              type="text"
              :class="fieldInputClass"
              placeholder="Ví dụ: 6,5 hoặc để trống"
              inputmode="decimal"
            />
          </label>
          <label :class="labelClass">
            <span>Thứ tự hiển thị</span>
            <input
              v-model.number="cargoForm.sort_order"
              type="number"
              min="0"
              :class="fieldInputClass"
              placeholder="Ví dụ: 10"
            />
          </label>
        </div>
        <div>
          <p class="mb-2 text-xs font-medium text-slate-600">Đơn giá một chiều (VNĐ)</p>
          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3">
            <label v-for="f in cargoMoneyFields" :key="f.key" :class="labelClass">
              <span>{{ f.label }}</span>
              <input
                v-model="cargoForm[f.key]"
                type="number"
                min="0"
                step="1000"
                :class="fieldInputClass"
                placeholder="Ví dụ: 30000"
                inputmode="numeric"
              />
            </label>
          </div>
        </div>
        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
            @click="cargoEditOpen = false"
          >
            Huỷ
          </button>
          <button
            type="submit"
            class="rounded-lg bg-indigo-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-900 disabled:opacity-50"
            :disabled="saveCargoLoading"
          >
            {{ saveCargoLoading ? 'Đang lưu…' : 'Lưu thay đổi' }}
          </button>
        </div>
      </form>
    </Modal>

    <Modal
      :open="noteEditOpen"
      title="Sửa ghi chú"
      description="Nội dung có dấu * là bắt buộc. Tiêu đề có thể để trống."
      @close="noteEditOpen = false"
    >
      <form class="space-y-4" @submit.prevent="saveNoteEdit">
        <label :class="labelClass">
          <span>Tiêu đề</span>
          <input
            v-model="noteForm.title"
            type="text"
            :class="fieldInputClass"
            placeholder="Tóm tắt ngắn (tuỳ chọn)"
            autocomplete="off"
          />
        </label>
        <label :class="labelClass">
          <span>Nội dung <span class="text-rose-600" aria-hidden="true">*</span></span>
          <textarea
            v-model="noteForm.body"
            rows="8"
            :class="fieldInputClass"
            placeholder="Nhập đầy đủ điều khoản hoặc ghi chú hiển thị cho người dùng…"
            required
          />
        </label>
        <label :class="labelClass">
          <span>Thứ tự hiển thị</span>
          <input
            v-model.number="noteForm.sort_order"
            type="number"
            min="0"
            :class="fieldInputClass"
            placeholder="Ví dụ: 10"
          />
        </label>
        <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
            @click="noteEditOpen = false"
          >
            Huỷ
          </button>
          <button
            type="submit"
            class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-900 disabled:opacity-50"
            :disabled="saveNoteLoading"
          >
            {{ saveNoteLoading ? 'Đang lưu…' : 'Lưu thay đổi' }}
          </button>
        </div>
      </form>
    </Modal>

    <Modal
      :open="historyOpen"
      wide
      :title="historyTitle"
      description="So sánh theo chiều ngang: mỗi cột là một mốc (phiên bản đã lưu + cột Hiện tại). Ô nền vàng = giá trị khác cột liền trước."
      @close="historyOpen = false"
    >
      <div v-if="historyLoading" class="flex flex-col items-center justify-center gap-2 py-12 text-sm text-slate-500">
        <span
          class="inline-block size-6 animate-spin rounded-full border-2 border-slate-200 border-t-va-700"
          aria-hidden="true"
        />
        Đang tải lịch sử…
      </div>
      <div v-else-if="!historyItems.length" class="rounded-lg border border-dashed border-slate-200 bg-slate-50/80 px-4 py-6 text-center text-sm text-slate-600">
        Chưa có phiên bản lưu trữ nào. Sau lần chỉnh sửa đầu tiên, bạn sẽ thấy lịch sử tại đây.
      </div>
      <div v-else class="space-y-3">
        <p class="border-l-4 border-amber-400 bg-amber-50/60 px-3 py-2 text-xs leading-relaxed text-slate-700">
          <strong>Đọc nhanh:</strong> mỗi cột là một “ảnh” dữ liệu tại thời điểm đó. Cột
          <strong>Hiện tại</strong> là giá trị đang dùng trên bảng giá. Dưới badge có
          <strong>tóm tắt từng trường đổi</strong> (dạng giá trị cũ → giá trị mới); bảng bên dưới là đầy đủ từng ô.
        </p>
        <div class="-mx-1 overflow-x-auto rounded-xl border border-slate-200 shadow-sm">
          <table class="w-full min-w-[800px] border-collapse text-left text-xs md:text-sm">
            <thead>
              <tr class="bg-gradient-to-b from-slate-100 to-slate-50">
                <th
                  class="sticky left-0 z-[2] min-w-[132px] border-b border-r border-slate-200 px-2 py-2 text-left font-semibold text-slate-800 shadow-[6px_0_12px_-6px_rgba(15,23,42,0.15)]"
                >
                  Trường dữ liệu
                </th>
                <th
                  v-for="(col, colIdx) in historyColumns"
                  :key="col.id"
                  class="min-w-[168px] max-w-[220px] border-b border-slate-200 px-2 py-2 text-center align-top"
                >
                  <div class="font-semibold text-slate-900">{{ col.label }}</div>
                  <div class="mt-0.5 text-[11px] font-normal leading-snug text-slate-500">{{ col.dateLabel }}</div>
                  <div v-if="col.kind === 'revision'" class="mt-1 text-[11px] text-slate-600">
                    <span class="block">Người lưu:</span>
                    <span class="font-medium text-slate-800">{{ col.editorName }}</span>
                    <span v-if="col.editorEmail" class="mt-0.5 block truncate text-[10px] text-slate-500">{{
                      col.editorEmail
                    }}</span>
                  </div>
                  <div
                    v-if="colIdx > 0 && columnChangeCounts[colIdx] != null"
                    class="mt-2 rounded-md bg-amber-100/90 px-1.5 py-1 text-[10px] font-semibold leading-tight text-amber-950"
                  >
                    {{ columnChangeCounts[colIdx] }} trường đổi so với cột trước
                  </div>
                  <ul
                    v-if="colIdx > 0 && columnChangeSummaries[colIdx]?.length"
                    class="mt-1.5 max-h-32 overflow-y-auto space-y-1 border-t border-amber-200/60 pt-1.5 text-left text-[10px] leading-snug text-slate-700"
                  >
                    <li v-for="(line, li) in columnChangeSummaries[colIdx]" :key="li" class="break-words">
                      {{ line }}
                    </li>
                  </ul>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in historyComparisonRows"
                :key="row.key"
                class="border-b border-slate-100 transition-colors hover:bg-slate-50/80"
              >
                <td
                  class="sticky left-0 z-[1] border-r border-slate-100 bg-white/95 px-2 py-2 font-medium text-slate-700 shadow-[6px_0_12px_-6px_rgba(15,23,42,0.08)]"
                >
                  {{ row.label }}
                </td>
                <td
                  v-for="(cell, colIdx) in row.cells"
                  :key="colIdx"
                  class="max-w-[240px] px-2 py-2 align-top text-slate-800"
                  :class="[
                    colIdx > 0 && row.changed[colIdx]
                      ? 'bg-amber-50 ring-1 ring-inset ring-amber-200/70'
                      : 'bg-white',
                    row.multiline ? 'whitespace-pre-wrap break-words leading-relaxed' : 'tabular-nums',
                  ]"
                >
                  {{ cell }}
                </td>
              </tr>
            </tbody>
          </table>
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

const labelClass = 'block text-xs font-medium text-slate-700'
const fieldInputClass =
  'mt-1.5 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 transition focus:border-va-600 focus:outline-none focus:ring-2 focus:ring-va-600/15'

const loading = ref(true)
const error = ref('')
const data = ref({
  passenger_fares: [],
  cargo_fares: [],
  notes: [],
})

const hasFareRows = computed(
  () => (data.value.passenger_fares?.length ?? 0) > 0 || (data.value.cargo_fares?.length ?? 0) > 0,
)

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
const historyEntityType = ref('passenger_fare_rate')
/** id bản ghi đang xem lịch sử (để gắn cột Hiện tại từ dữ liệu trang). */
const historyContextId = ref(null)
/** Bản ghi truyền từ nút Lịch sử (ưu tiên hơn tra cứu trong `data`). */
const historyCurrentRecord = ref(null)

function resolveHistoryCurrentRecord() {
  if (historyCurrentRecord.value) return historyCurrentRecord.value
  const id = historyContextId.value
  const t = historyEntityType.value
  if (!id) return null
  if (t === 'passenger_fare_rate') return data.value.passenger_fares?.find((r) => r.id === id) ?? null
  if (t === 'cargo_fare_rate') return data.value.cargo_fares?.find((r) => r.id === id) ?? null
  if (t === 'pricing_note') return data.value.notes?.find((n) => n.id === id) ?? null
  return null
}

function snapshotFromCurrentRecord(entityType, row) {
  if (!row) return {}
  if (entityType === 'passenger_fare_rate') {
    const o = {
      package_label: row.package_label,
      package_code: row.package_code,
      sort_order: row.sort_order,
    }
    for (const f of passengerMoneyFields) o[f.key] = row[f.key]
    return o
  }
  if (entityType === 'cargo_fare_rate') {
    const o = {
      route_label: row.route_label,
      route_code: row.route_code,
      distance_km: row.distance_km,
      sort_order: row.sort_order,
    }
    for (const f of cargoMoneyFields) o[f.key] = row[f.key]
    return o
  }
  if (entityType === 'pricing_note') {
    return {
      category: row.category,
      title: row.title,
      body: row.body,
      sort_order: row.sort_order,
    }
  }
  return {}
}

function getHistoryFieldDefs(entityType) {
  if (entityType === 'passenger_fare_rate') {
    return [
      { key: 'package_label', label: 'Gói / tuyến' },
      { key: 'package_code', label: 'Mã gói (hệ thống)' },
      { key: 'sort_order', label: 'Thứ tự hiển thị' },
      ...passengerMoneyFields.map((f) => ({ key: f.key, label: `${f.label} (VNĐ)` })),
    ]
  }
  if (entityType === 'cargo_fare_rate') {
    return [
      { key: 'route_label', label: 'Lộ trình' },
      { key: 'route_code', label: 'Mã tuyến (hệ thống)' },
      { key: 'distance_km', label: 'Khoảng cách' },
      { key: 'sort_order', label: 'Thứ tự hiển thị' },
      ...cargoMoneyFields.map((f) => ({ key: f.key, label: `${f.label} (VNĐ)` })),
    ]
  }
  if (entityType === 'pricing_note') {
    return [
      { key: 'category', label: 'Loại ghi chú' },
      { key: 'title', label: 'Tiêu đề' },
      { key: 'sort_order', label: 'Thứ tự hiển thị' },
      { key: 'body', label: 'Nội dung', multiline: true },
    ]
  }
  return []
}

function fmtAuditMoney(v) {
  if (v == null || v === '') return '— (không áp dụng)'
  return formatVnd(v)
}

function displayFieldValue(entityType, fieldKey, snapshot) {
  if (!snapshot) return '—'
  if (entityType === 'passenger_fare_rate') {
    if (fieldKey === 'package_label') return snapshot.package_label ?? '—'
    if (fieldKey === 'package_code') return snapshot.package_code ?? '—'
    if (fieldKey === 'sort_order') return snapshot.sort_order != null ? String(snapshot.sort_order) : '—'
    if (passengerMoneyFields.some((f) => f.key === fieldKey)) return fmtAuditMoney(snapshot[fieldKey])
  }
  if (entityType === 'cargo_fare_rate') {
    if (fieldKey === 'route_label') return snapshot.route_label ?? '—'
    if (fieldKey === 'route_code') return snapshot.route_code ?? '—'
    if (fieldKey === 'distance_km') {
      const dk = snapshot.distance_km
      if (dk == null || dk === '') return '— (không gắn km cố định)'
      return `${formatDistance(Number(dk))} km`
    }
    if (fieldKey === 'sort_order') return snapshot.sort_order != null ? String(snapshot.sort_order) : '—'
    if (cargoMoneyFields.some((f) => f.key === fieldKey)) return fmtAuditMoney(snapshot[fieldKey])
  }
  if (entityType === 'pricing_note') {
    if (fieldKey === 'category') return labelPricingNoteCategory(snapshot.category) ?? '—'
    if (fieldKey === 'title') return snapshot.title?.trim() ? snapshot.title : '—'
    if (fieldKey === 'sort_order') return snapshot.sort_order != null ? String(snapshot.sort_order) : '—'
    if (fieldKey === 'body') return snapshot.body ?? '—'
  }
  return '—'
}

function normalizeFieldValueForCompare(entityType, fieldKey, snapshot) {
  if (!snapshot) return '__empty__'
  const v = snapshot[fieldKey]
  if (entityType === 'passenger_fare_rate' && passengerMoneyFields.some((f) => f.key === fieldKey)) {
    if (v == null || v === '') return ''
    const n = Number(v)
    return Number.isFinite(n) ? String(n) : String(v)
  }
  if (entityType === 'cargo_fare_rate' && cargoMoneyFields.some((f) => f.key === fieldKey)) {
    if (v == null || v === '') return ''
    const n = Number(v)
    return Number.isFinite(n) ? String(n) : String(v)
  }
  if (fieldKey === 'distance_km') {
    if (v == null || v === '') return ''
    const n = Number(v)
    return Number.isFinite(n) ? String(n) : String(v)
  }
  if (fieldKey === 'sort_order') {
    if (v == null || v === '') return ''
    return String(Number(v))
  }
  if (fieldKey === 'body') return String(v ?? '').trim()
  if (fieldKey === 'title') return String(v ?? '').trim()
  if (fieldKey === 'category') return String(v ?? '').trim()
  return String(v ?? '').trim()
}

const historyColumns = computed(() => {
  const revs = [...(historyItems.value ?? [])].sort((a, b) => a.id - b.id)
  const current = resolveHistoryCurrentRecord()
  const cols = revs.map((rev, i) => ({
    id: `rev-${rev.id}`,
    kind: 'revision',
    snapshot: rev.snapshot || {},
    label: `Phiên bản ${i + 1}`,
    dateLabel: formatHistoryDate(rev.created_at),
    editorName: rev.user?.name ?? '—',
    editorEmail: rev.user?.email ?? '',
  }))
  if (current) {
    cols.push({
      id: 'current',
      kind: 'current',
      snapshot: snapshotFromCurrentRecord(historyEntityType.value, current),
      label: 'Hiện tại',
      dateLabel: 'Đang áp dụng trên bảng',
      editorName: '—',
      editorEmail: '',
    })
  }
  return cols
})

const historyComparisonRows = computed(() => {
  const cols = historyColumns.value
  const entityType = historyEntityType.value
  const defs = getHistoryFieldDefs(entityType)
  if (!cols.length || !defs.length) return []
  return defs.map((def) => {
    const cells = cols.map((col) => displayFieldValue(entityType, def.key, col.snapshot))
    const changed = cols.map((_, idx) => {
      if (idx === 0) return false
      const a = normalizeFieldValueForCompare(entityType, def.key, cols[idx - 1].snapshot)
      const b = normalizeFieldValueForCompare(entityType, def.key, cols[idx].snapshot)
      return a !== b
    })
    return { key: def.key, label: def.label, multiline: def.multiline, cells, changed }
  })
})

const columnChangeCounts = computed(() => {
  const rows = historyComparisonRows.value
  const n = historyColumns.value.length
  if (!n || !rows.length) return []
  const counts = Array.from({ length: n }, () => null)
  for (let c = 1; c < n; c++) {
    counts[c] = rows.filter((r) => r.changed[c]).length
  }
  return counts
})

/** Rút gọn giá trị để một dòng “cũ → mới” trong header (không cần đủ như ô bảng). */
function summarizeForChangeLine(text, multiline) {
  if (text == null || text === '') return '—'
  const t = String(text).replace(/\s+/g, ' ').trim()
  const max = multiline ? 56 : 44
  if (t.length <= max) return t
  return `${t.slice(0, max)}…`
}

/** Mỗi cột (index > 0): danh sách dòng “Tên trường: giá trị trước → giá trị sau”. */
const columnChangeSummaries = computed(() => {
  const cols = historyColumns.value
  const entityType = historyEntityType.value
  const defs = getHistoryFieldDefs(entityType)
  const n = cols.length
  const out = Array.from({ length: n }, () => [])
  for (let c = 1; c < n; c++) {
    const lines = []
    for (const def of defs) {
      const a = normalizeFieldValueForCompare(entityType, def.key, cols[c - 1].snapshot)
      const b = normalizeFieldValueForCompare(entityType, def.key, cols[c].snapshot)
      if (a === b) continue
      const prevDisp = displayFieldValue(entityType, def.key, cols[c - 1].snapshot)
      const currDisp = displayFieldValue(entityType, def.key, cols[c].snapshot)
      const left = summarizeForChangeLine(prevDisp, def.multiline)
      const right = summarizeForChangeLine(currDisp, def.multiline)
      lines.push(`${def.label}: ${left} → ${right}`)
    }
    out[c] = lines
  }
  return out
})

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
        category: n.category,
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

async function openHistory(type, id, label, currentRecord = null) {
  historyEntityType.value = type
  historyContextId.value = id
  historyCurrentRecord.value = currentRecord
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

async function reloadPricing() {
  loading.value = true
  error.value = ''
  try {
    data.value = await getReferencePricing()
  } catch (e) {
    error.value = e?.response?.data?.message ?? 'Không tải được bảng giá.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  reloadPricing()
})
</script>
