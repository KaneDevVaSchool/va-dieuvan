<template>
  <div class="space-y-5 pb-8">
    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="flex flex-wrap items-start gap-4 border-b border-slate-200 bg-slate-50/80 px-4 py-4 sm:px-5">
        <div class="min-w-0 flex-1 text-center sm:text-left">
          <h2 class="text-base font-bold uppercase tracking-tight text-slate-900 sm:text-lg">Đề Nghị Điều Vận</h2>
          <p class="mt-1 text-xs font-medium text-slate-600 sm:text-sm">{{ tripSubtitle }}</p>
        </div>
        <div class="mx-auto grid w-full max-w-[13rem] shrink-0 gap-px overflow-hidden rounded border border-slate-300 text-[11px] sm:mx-0">
          <div class="grid grid-cols-2 bg-white">
            <span class="border-b border-r border-slate-300 px-2 py-1 font-semibold text-slate-600">Ký hiệu</span>
            <span class="border-b border-slate-300 px-2 py-1 text-right text-slate-900">BM.03/MH.QT.04</span>
          </div>
          <div class="grid grid-cols-2 bg-white">
            <span class="border-b border-r border-slate-300 px-2 py-1 font-semibold text-slate-600">Ngày BH</span>
            <span class="border-b border-slate-300 px-2 py-1 text-right text-slate-900">29/08/2025</span>
          </div>
          <div class="grid grid-cols-2 bg-white">
            <span class="border-r border-slate-300 px-2 py-1 font-semibold text-slate-600">Lần BH</span>
            <span class="px-2 py-1 text-right text-slate-900">01</span>
          </div>
          <div class="grid grid-cols-2 bg-white">
            <span class="border-t border-r border-slate-300 px-2 py-1 font-semibold text-slate-600">Mã yêu cầu</span>
            <span class="border-t border-slate-300 px-2 py-1 text-right font-medium text-slate-900">#{{ req?.id ?? '—' }}</span>
          </div>
        </div>
      </div>

      <div class="divide-y divide-slate-100 px-4 py-4 sm:px-5">
        <!-- A -->
        <div>
          <div class="flex items-center gap-2 rounded-t-lg bg-slate-50 px-3 py-2.5 sm:px-4">
            <span class="inline-flex items-center justify-center rounded bg-slate-800 px-2 py-0.5 text-xs font-bold text-white">A</span>
            <span class="text-xs font-bold uppercase tracking-wide text-slate-700">Người đề nghị</span>
          </div>
          <div class="-mt-px space-y-0 rounded-b-lg border border-t-0 border-slate-200 px-3 py-4 sm:px-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <BmRoField label="a.1 Họ và tên" :model-value="aName" />
              <BmRoField label="a.2 Email VA của nhân viên" :model-value="aEmail" />
              <BmRoField label="a.3 Số điện thoại" :model-value="aPhone" />
              <BmRoField label="a.4 Đơn vị" :model-value="aUnit" />
            </div>
          </div>
        </div>

        <!-- B -->
        <div class="pt-5">
          <div class="flex items-center gap-2 rounded-t-lg bg-slate-50 px-3 py-2.5 sm:px-4">
            <span class="inline-flex items-center justify-center rounded bg-slate-800 px-2 py-0.5 text-xs font-bold text-white">B</span>
            <span class="text-xs font-bold uppercase tracking-wide text-slate-700">Mục đích sử dụng</span>
          </div>
          <div class="-mt-px space-y-4 rounded-b-lg border border-t-0 border-slate-200 px-3 py-4 sm:px-4">
            <BmRoField label="b.1 Mục đích sử dụng" :model-value="purposeDisplay" multiline />
            <BmRoField
              label="b.2 Căn cứ đề xuất"
              :model-value="basisDisplay"
              hint="— Tờ trình số …/ ngày &amp; nội dung"
              multiline
            />
          </div>
        </div>

        <!-- C -->
        <div class="pt-5">
          <div class="flex items-center gap-2 rounded-t-lg bg-slate-50 px-3 py-2.5 sm:px-4">
            <span class="inline-flex items-center justify-center rounded bg-slate-800 px-2 py-0.5 text-xs font-bold text-white">C</span>
            <span class="text-xs font-bold uppercase tracking-wide text-slate-700">Thời gian</span>
          </div>
          <div class="-mt-px space-y-4 rounded-b-lg border border-t-0 border-slate-200 px-3 py-4 sm:px-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <BmRoField label="c.1 Ngày đề xuất" :model-value="fmtDateVi(proposedDateRaw)" />
              <BmRoField label="c.2 Ngày cần sử dụng xe" :model-value="fmtDateVi(dateNeededRaw)" />
            </div>
            <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-700">
              Lưu ý: Tối thiểu 03 ngày làm việc trước ngày cấp xe; từ 2.000 kg trở lên cần báo sớm ít nhất 05 ngày làm việc.
            </p>
            <div class="grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2">
              <div class="flex flex-wrap items-start gap-2">
                <input type="checkbox" class="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600" :checked="isUrgent" disabled />
                <div class="min-w-0 flex-1 space-y-1">
                  <span class="text-xs font-semibold uppercase text-slate-700">Gấp</span>
                  <input
                    type="text"
                    readonly
                    tabindex="-1"
                    class="w-full rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-900"
                    :value="urgentReason || '—'"
                    aria-label="Lý do gấp"
                  />
                </div>
              </div>
              <div class="flex flex-wrap items-center justify-end gap-2">
                <span class="text-xs font-semibold text-slate-700">Loại yêu cầu:</span>
                <span class="inline-flex rounded border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-800">{{ tripTypeLabel }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- D -->
        <div class="pt-5">
          <div class="flex items-center gap-2 rounded-t-lg bg-slate-50 px-3 py-2.5 sm:px-4">
            <span class="inline-flex items-center justify-center rounded bg-slate-800 px-2 py-0.5 text-xs font-bold text-white">D</span>
            <span class="text-xs font-bold uppercase tracking-wide text-slate-700">Khu vực / Đối tượng được phân bổ</span>
          </div>
          <div class="-mt-px rounded-b-lg border border-t-0 border-slate-200 px-3 py-4 sm:px-4">
            <p class="mb-3 text-[11px] font-bold uppercase tracking-wide text-slate-700">d.1 Đối tượng sử dụng</p>
            <div class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2 lg:grid-cols-4">
              <label
                v-for="opt in TARGET_OPTIONS"
                :key="opt"
                class="flex cursor-default items-start gap-2 text-xs text-slate-800"
              >
                <input type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-teal-600" :checked="targetsSet.has(opt)" disabled />
                <span class="leading-snug">{{ opt }}</span>
              </label>
            </div>
            <div class="mt-4 grid gap-4 border-t border-slate-200 pt-4 sm:grid-cols-3">
              <BmRoField label="d.2 Nhân sự phụ trách — Họ tên" :model-value="nz(form.coordinator_name)" />
              <BmRoField label="Email" :model-value="nz(form.coordinator_email)" />
              <BmRoField label="SĐT" :model-value="nz(form.coordinator_phone)" />
            </div>
          </div>
        </div>

        <!-- E -->
        <div class="pt-5">
          <div class="flex flex-wrap items-end gap-2 rounded-t-lg bg-slate-50 px-3 py-2.5 sm:px-4">
            <span class="inline-flex items-center justify-center rounded bg-slate-800 px-2 py-0.5 text-xs font-bold text-white">E</span>
            <span class="text-xs font-bold uppercase tracking-wide text-slate-700">Nội dung đề nghị vận chuyển</span>
            <span class="text-[11px] font-semibold text-slate-500">{{ sectionESub }}</span>
          </div>
          <div class="-mt-px space-y-0 rounded-b-lg border border-t-0 border-slate-200 px-3 py-4 sm:px-4">
            <div v-if="isCargo" class="overflow-x-auto rounded-lg border border-slate-300">
              <table class="w-full min-w-[1100px] border-collapse text-left text-[11px]">
                <thead>
                  <tr class="border-b border-slate-900 bg-white">
                    <th rowspan="2" class="border border-slate-300 px-1 py-1 text-center align-middle">STT</th>
                    <th colspan="5" class="border border-slate-300 px-1 py-1 text-center">Thông tin hàng hóa</th>
                    <th colspan="3" class="border border-slate-300 px-1 py-1 text-center">Điểm tập kết</th>
                    <th colspan="3" class="border border-slate-300 px-1 py-1 text-center">Điểm giao</th>
                    <th rowspan="2" class="border border-slate-300 px-1 py-1 text-center align-middle">
                      Loại hình<br />
                      <span class="font-normal opacity-75">(NV điều phối)</span>
                    </th>
                    <th rowspan="2" class="border border-slate-300 px-1 py-1 text-center align-middle">
                      Chi phí<br />
                      <span class="font-normal opacity-75">(Gồm VAT)</span>
                    </th>
                  </tr>
                  <tr class="bg-white">
                    <th class="border border-slate-300 px-1 py-1">Tên hàng hóa</th>
                    <th class="border border-slate-300 px-1 py-1 text-center">SL</th>
                    <th class="border border-slate-300 px-1 py-1">Kích thước</th>
                    <th class="border border-slate-300 px-1 py-1">Khối lượng</th>
                    <th class="border border-slate-300 px-1 py-1">Ghi chú</th>
                    <th class="border border-slate-300 px-1 py-1">Thời gian</th>
                    <th class="border border-slate-300 px-1 py-1">Địa điểm</th>
                    <th class="border border-slate-300 px-1 py-1">Người giao</th>
                    <th class="border border-slate-300 px-1 py-1">Thời gian</th>
                    <th class="border border-slate-300 px-1 py-1">Địa điểm</th>
                    <th class="border border-slate-300 px-1 py-1">Người nhận</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in cargoDisplayRows" :key="'c-' + idx" class="odd:bg-white even:bg-slate-50/70">
                    <td class="border border-slate-300 px-1 py-0.5 text-center align-top">{{ idx + 1 }}</td>
                    <td
                      v-for="ci in cargoColIndexes"
                      :key="'cx' + ci"
                      class="border border-slate-300 px-0.5 py-0 align-top"
                    >
                      <BmRoTd :model-value="cargoCell(row, ci)" :align-right="ci === 12" />
                    </td>
                  </tr>
                  <tr class="bg-slate-100 font-semibold">
                    <td colspan="13" class="border border-slate-300 px-2 py-1 text-right">Tổng:</td>
                    <td class="border border-slate-300 px-2 py-1 text-right tabular-nums">{{ grandTotalFmt }}</td>
                  </tr>
                </tbody>
              </table>
              <div class="border-t border-slate-300 bg-white p-3 text-xs">
                <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
                  <div class="space-y-2">
                    <p class="font-semibold text-slate-800">e.1.1 Các ghi chú khác</p>
                    <label class="flex flex-wrap items-center gap-2">
                      <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600" :checked="form.need_porters" disabled />
                      <span>Yêu cầu bốc xếp / nhân công hỗ trợ</span>
                    </label>
                    <div class="ml-7 grid gap-2 sm:grid-cols-2">
                      <BmRoField compact label="Số lượng" :model-value="form.need_porters ? nz(form.porter_qty) : ''" />
                      <BmRoField compact label="Chi phí phát sinh" :model-value="form.need_porters ? fmtMoney(porterMoney) : ''" />
                    </div>
                    <label class="flex flex-wrap items-center gap-2 pt-2">
                      <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-teal-600" :checked="form.interprovincial" disabled />
                      <span>Gửi chành xe đi tỉnh</span>
                    </label>
                    <div class="ml-7">
                      <BmRoField compact label="Chi phí phát sinh" :model-value="form.interprovincial ? fmtMoney(interprovincialMoney) : ''" />
                    </div>
                    <BmRoField v-if="nz(form.cargo_extra_notes)" compact label="Ghi chú thêm" :model-value="form.cargo_extra_notes" multiline />
                  </div>
                  <p class="text-[11px] text-slate-500 sm:self-start sm:max-w-[12rem]">(Liên hệ NV Điều vận để điền thông tin chi phí)</p>
                </div>
              </div>
            </div>

            <div v-else-if="!isBusiness" class="overflow-x-auto rounded-lg border border-slate-300">
              <table class="w-full min-w-[920px] border-collapse text-left text-[11px]">
                <thead>
                  <tr class="border-b border-slate-900 bg-white">
                    <th class="border border-slate-300 px-1 py-1 text-center">STT</th>
                    <th class="border border-slate-300 px-1 py-1">Diễn giải</th>
                    <th class="border border-slate-300 px-1 py-1 text-center">SL</th>
                    <th class="border border-slate-300 px-1 py-1 text-center">TG đi</th>
                    <th class="border border-slate-300 px-1 py-1">Điểm đón</th>
                    <th class="border border-slate-300 px-1 py-1 text-center">TG về</th>
                    <th class="border border-slate-300 px-1 py-1">Điểm trả</th>
                    <th class="border border-slate-300 px-1 py-1">Phụ trách</th>
                    <th class="border border-slate-300 px-1 py-1 text-right">Đơn giá</th>
                    <th class="border border-slate-300 px-1 py-1 text-right">Phụ thu</th>
                    <th class="border border-slate-300 px-1 py-1">Ghi chú</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in passengerSnapshotTableRows" :key="'p-' + idx" class="odd:bg-white even:bg-slate-50/70">
                    <td class="border border-slate-300 px-1 py-0.5 text-center align-top">{{ idx + 1 }}</td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="passDesc(row)" /></td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="passQty(row)" class="text-center" /></td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="fmtShortDt(row.depart_at)" /></td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="passPickup(row)" /></td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="fmtShortDt(row.return_at)" /></td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="passDropoff(row)" /></td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="row.person_in_charge" /></td>
                    <td class="border border-slate-300 px-1 py-0.5 align-top">
                      <input
                        v-if="showFillPriceSection && idx < passengerRowCount"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        class="w-full min-w-[5.5rem] rounded border border-teal-200/70 bg-white px-1 py-0.5 text-right text-[11px] tabular-nums outline-none ring-teal-500/20 focus:border-teal-500 focus:ring-1"
                        :value="passengerPriceDraftUnit[idx]"
                        @input="onPassengerUnitInput(idx, $event.target.value)"
                      />
                      <BmRoTd v-else :model-value="fmtMoneyRow(row.unit_price)" align-right />
                    </td>
                    <td class="border border-slate-300 px-1 py-0.5 align-top">
                      <input
                        v-if="showFillPriceSection && idx < passengerRowCount"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        class="w-full min-w-[5.5rem] rounded border border-teal-200/70 bg-white px-1 py-0.5 text-right text-[11px] tabular-nums outline-none ring-teal-500/20 focus:border-teal-500 focus:ring-1"
                        :value="passengerPriceDraftExtra[idx]"
                        @input="onPassengerExtraInput(idx, $event.target.value)"
                      />
                      <BmRoTd v-else :model-value="fmtMoneyRow(row.extra_fee)" align-right />
                    </td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="row.notes" /></td>
                  </tr>
                  <tr class="bg-slate-100 font-semibold">
                    <td colspan="10" class="border border-slate-300 px-2 py-1 text-right">Tổng ước tính:</td>
                    <td class="border border-slate-300 px-2 py-1 text-right tabular-nums">{{ grandTotalFmt }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-else class="overflow-x-auto rounded-lg border border-slate-300">
              <table class="w-full min-w-[940px] border-collapse text-left text-[11px]">
                <thead>
                  <tr class="border-b border-slate-900 bg-white">
                    <th class="border border-slate-300 px-1 py-1 text-center">STT</th>
                    <th class="border border-slate-300 px-1 py-1">Diễn giải</th>
                    <th class="border border-slate-300 px-1 py-1 text-center">SL</th>
                    <th class="border border-slate-300 px-1 py-1">Điểm dừng / cung đường</th>
                    <th class="border border-slate-300 px-1 py-1">Ghi chú</th>
                    <th class="border border-slate-300 px-1 py-1 text-center">TG đi</th>
                    <th class="border border-slate-300 px-1 py-1">Điểm đi</th>
                    <th class="border border-slate-300 px-1 py-1 text-center">TG về</th>
                    <th class="border border-slate-300 px-1 py-1">Điểm đến</th>
                    <th class="border border-slate-300 px-1 py-1 text-right">Đơn giá</th>
                    <th class="border border-slate-300 px-1 py-1 text-right">Phụ thu</th>
                    <th class="border border-slate-300 px-1 py-1">Khác</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in businessSnapshotTableRows" :key="'b-' + idx" class="odd:bg-white even:bg-slate-50/70">
                    <td class="border border-slate-300 px-1 py-0.5 text-center align-top">{{ idx + 1 }}</td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="nz(row.description) || 'Công tác'" /></td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="row.guests" class="text-center" /></td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="row.waypoint" /></td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="row.notes" /></td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="row.depart_at" /></td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="row.pickup" /></td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="row.return_at" /></td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="row.dropoff" /></td>
                    <td class="border border-slate-300 px-1 py-0.5 align-top">
                      <input
                        v-if="showFillPriceSection && idx < businessRowCount"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        class="w-full min-w-[5.5rem] rounded border border-teal-200/70 bg-white px-1 py-0.5 text-right text-[11px] tabular-nums outline-none ring-teal-500/20 focus:border-teal-500 focus:ring-1"
                        :value="businessPriceDraftUnit[idx]"
                        @input="onBusinessUnitInput(idx, $event.target.value)"
                      />
                      <BmRoTd v-else :model-value="fmtMoneyRow(row.unit_price)" align-right />
                    </td>
                    <td class="border border-slate-300 px-1 py-0.5 align-top">
                      <input
                        v-if="showFillPriceSection && idx < businessRowCount"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        class="w-full min-w-[5.5rem] rounded border border-teal-200/70 bg-white px-1 py-0.5 text-right text-[11px] tabular-nums outline-none ring-teal-500/20 focus:border-teal-500 focus:ring-1"
                        :value="businessPriceDraftExtra[idx]"
                        @input="onBusinessExtraInput(idx, $event.target.value)"
                      />
                      <BmRoTd v-else :model-value="fmtMoneyRow(row.extra_fee)" align-right />
                    </td>
                    <td class="border border-slate-300 px-0.5 py-0 align-top"><BmRoTd :model-value="row.other || '—'" class="text-center" /></td>
                  </tr>
                  <tr class="bg-slate-100 font-semibold">
                    <td colspan="11" class="border border-slate-300 px-2 py-1 text-right">Tổng (ước tính):</td>
                    <td class="border border-slate-300 px-2 py-1 text-right tabular-nums">{{ grandTotalFmt }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div
              v-if="showFillPriceSection && !isCargo"
              class="border-t border-teal-200/70 bg-gradient-to-r from-teal-50/90 via-teal-50/40 to-slate-50/80 px-3 py-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.6)] sm:px-4"
            >
              <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                <div class="flex flex-wrap items-center gap-2 rounded-xl bg-white/80 p-2 ring-1 ring-teal-600/10 sm:gap-2.5">
                  <Button
                    type="button"
                    class="shadow-sm sm:min-h-[2.375rem] !bg-teal-600 hover:!bg-teal-700 focus-visible:!ring-teal-500"
                    :loading="fillPriceActing"
                    @click="emitSaveRowPrices"
                  >
                    Lưu giá &amp; chuyển Trưởng đơn vị duyệt
                  </Button>
                  <span class="hidden h-6 w-px shrink-0 bg-teal-200/80 sm:block" aria-hidden="true" />
                  <Button
                    type="button"
                    variant="secondary"
                    :disabled="fillPriceActing"
                    class="!border-teal-300/70 !bg-white !text-teal-900 shadow-sm ring-1 ring-teal-500/10 hover:!border-teal-400 hover:!bg-teal-50/90 disabled:opacity-50 sm:min-h-[2.375rem]"
                    @click="emit('open-reference-pricing')"
                  >
                    {{ t('request_detail.reference_pricing_link') }}
                  </Button>
                </div>
                <p v-if="fillPriceMsg" class="flex-1 rounded-lg border border-amber-200/80 bg-amber-50/90 px-3 py-2 text-xs leading-relaxed text-amber-950 sm:flex-none sm:max-w-md sm:text-right">
                  {{ fillPriceMsg }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- F -->
        <div class="pt-5">
          <div class="flex items-center gap-2 rounded-t-lg bg-slate-50 px-3 py-2.5 sm:px-4">
            <span class="inline-flex items-center justify-center rounded bg-slate-800 px-2 py-0.5 text-xs font-bold text-white">F</span>
            <span class="text-xs font-bold uppercase tracking-wide text-slate-700">Phần xác nhận của các bên liên quan</span>
          </div>
          <div class="-mt-px overflow-hidden rounded-b-lg border border-t-0 border-slate-200 px-3 py-4 sm:px-4">
            <table class="w-full border-collapse text-center text-xs">
              <tbody>
                <tr>
                  <th class="border border-slate-300 bg-white px-2 py-2 font-bold">Trưởng phòng Mua hàng</th>
                  <th class="border border-slate-300 bg-white px-2 py-2 font-bold">Người đề xuất</th>
                  <th class="border border-slate-300 bg-white px-2 py-2 font-bold">Trưởng đơn vị đề xuất</th>
                </tr>
                <tr class="min-h-[4rem]">
                  <td class="border border-slate-300 bg-white px-2 py-4 align-bottom text-[11px]">
                    Phạm Thanh Hùng<br />
                    <span class="text-slate-600">Giám đốc … (tùy lĩnh vực)</span>
                  </td>
                  <td class="border border-slate-300 bg-white px-2 py-6 align-bottom" />
                  <td class="border border-slate-300 bg-white px-2 py-6 align-bottom" />
                </tr>
                <tr>
                  <th class="border border-slate-300 bg-white px-2 py-2 font-bold">Giám đốc Vận hành</th>
                  <th class="border border-slate-300 bg-white px-2 py-2 font-bold">&nbsp;</th>
                  <th class="border border-slate-300 bg-white px-2 py-2 font-bold">Tổng Giám đốc</th>
                </tr>
                <tr>
                  <td class="border border-slate-300 bg-white px-2 py-4 align-bottom text-[11px]">Bùi Quang Minh</td>
                  <td class="border border-slate-300 bg-white px-2 py-6 align-bottom" />
                  <td class="border border-slate-300 bg-white px-2 py-6 align-bottom" />
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <CostLimitAlert v-if="req?.dispatch_package_cost_alert" :alert="req.dispatch_package_cost_alert" />

    <section
      v-if="showResetCloneBtn || showPassengerAdjustSection"
      class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
    >
      <ResetCloneSection v-if="showResetCloneBtn" :busy="resetCloneBusy" @clone="$emit('resetClone')" />
      <StudentCountField
        v-if="showPassengerAdjustSection"
        v-model:passenger-count="passengerDraftModel"
        :locked="passengerDepartLocked"
        :depart-at-formatted="departAtFormatted"
        :saving="passengerSaving"
        :error="passengerPatchErr"
        :class="showResetCloneBtn ? 'mt-4 border-t border-slate-100 pt-4' : ''"
        @save="$emit('savePassenger')"
      />
    </section>

    <DeptApprovalSection
      v-if="showDeptDecisionSection"
      :service-price-display="servicePriceDisplay"
      :acting="deptActing"
      :inline-message="deptMsg"
      :reject-modal-open="deptRejectOpen"
      @approve="$emit('deptApprove')"
      @reject="$emit('deptReject')"
    />

    <SignedPaperUpload
      v-if="showSignedPaperSection"
      :attachments="signedPaperAttachments"
      :upload-component-key="signedUploadComponentKey"
      :upload-fn="uploadSignedFn"
      :error="signedUploadErr"
      @download="$emit('downloadSigned', $event)"
      @uploaded="$emit('signedUploaded')"
    />

    <div
      v-if="!approvalTabNeedsFocus"
      class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-500"
    >
      Không có hành động cần xử lý
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Button from '../ui/Button.vue'
import DeptApprovalSection from './DeptApprovalSection.vue'
import SignedPaperUpload from './SignedPaperUpload.vue'
import CostLimitAlert from './CostLimitAlert.vue'
import ResetCloneSection from './ResetCloneSection.vue'
import StudentCountField from '../recurring/StudentCountField.vue'
import BmRoField from './RequestBm03RoField.vue'
import BmRoTd from './RequestBm03RoTd.vue'
import { TARGET_OPTIONS, isPassengerRowFilled, isBusinessRowFilled } from '../../composables/dispatchWizardConstants'
import { parseMoneyVnd, formatVndWhileTyping } from '../../util/money'
import { labelTripType } from '../../util/labels'

const { t } = useI18n()

const props = defineProps({
  req: { type: Object, default: null },
  fillPriceActing: { type: Boolean, default: false },
  fillPriceMsg: { type: String, default: '' },
  deptActing: { type: Boolean, default: false },
  deptMsg: { type: String, default: '' },
  deptRejectOpen: { type: Boolean, default: false },
  signedPaperAttachments: { type: Array, default: () => [] },
  signedUploadComponentKey: { type: String, default: 'signed' },
  uploadSignedFn: {
    type: Function,
    default() {
      return async () => undefined
    },
  },
  signedUploadErr: { type: String, default: '' },
  passengerSaving: { type: Boolean, default: false },
  passengerPatchErr: { type: String, default: '' },
  passengerDepartLocked: { type: Boolean, default: true },
  departAtFormatted: { type: String, default: '' },
  showFillPriceSection: { type: Boolean, default: false },
  showDeptDecisionSection: { type: Boolean, default: false },
  showSignedPaperSection: { type: Boolean, default: false },
  showResetCloneBtn: { type: Boolean, default: false },
  showPassengerAdjustSection: { type: Boolean, default: false },
  approvalTabNeedsFocus: { type: Boolean, default: false },
  resetCloneBusy: { type: Boolean, default: false },
  servicePriceDisplay: { type: String, default: null },
})

const passengerDraftModel = defineModel('passengerDraft', {
  type: Number,
  default: 1,
})

const emit = defineEmits([
  'save-row-prices',
  'open-reference-pricing',
  'deptApprove',
  'deptReject',
  'downloadSigned',
  'signedUploaded',
  'savePassenger',
  'resetClone',
])

/** Indices 0..12 matching cargo PDF columns → cost last */
const cargoColIndexes = Array.from({ length: 13 }, (_, i) => i)

const form = computed(() => props.req?.wizard_snapshot?.form ?? {})
const snap = computed(() => props.req?.wizard_snapshot ?? {})

const isCargo = computed(() => props.req?.trip_type === 'cargo')
const isBusiness = computed(() => props.req?.trip_type === 'business')
const isP2p = computed(() => props.req?.trip_type === 'point_to_point')

const tripSubtitle = computed(() => {
  if (isCargo.value) return '(Điều chuyển Hàng hóa)'
  if (isBusiness.value) return '(Công tác)'
  if (isP2p.value) return '(Vận chuyển Điểm — Điểm)'
  return '(Đưa đón tận nơi)'
})

const tripTypeLabel = computed(() => labelTripType(props.req?.trip_type))

const sectionESub = computed(() => {
  if (isCargo.value) return ''
  if (isBusiness.value) return '· E.2 Bảng nội dung công tác'
  return isP2p.value ? '· E.1 Bảng hành trình (Điểm — Điểm)' : '· E.1 Bảng nội dung hành khách'
})

function nz(v) {
  const s = v == null ? '' : String(v).trim()
  return s === '' ? '' : s
}

const aName = computed(() => nz(form.value.requester_name) || nz(props.req?.requester?.name))
const aEmail = computed(() => nz(form.value.requester_email) || nz(props.req?.requester?.email))
const aPhone = computed(() => nz(form.value.requester_phone) || nz(props.req?.requester?.phone))
const aUnit = computed(() => nz(form.value.requester_unit))

const purposeDisplay = computed(() => nz(form.value.purpose))

const basisDisplay = computed(() => {
  const ref = nz(form.value.basis_ref)
  if (ref) return ref
  const bf = nz(form.value.basisFileName)
  return bf ? `Đính kèm tệp «${bf}»` : ''
})

const proposedDateRaw = computed(() => form.value.proposed_date)
const dateNeededRaw = computed(() => form.value.date_needed)
const isUrgent = computed(() => !!form.value.is_urgent)
const urgentReason = computed(() => nz(form.value.urgent_reason))

const targetsSet = computed(() => {
  const raw = Array.isArray(form.value.targets) ? form.value.targets : []
  return new Set(raw.map((x) => String(x).trim()).filter(Boolean))
})

function fmtDateVi(v) {
  if (!v) return ''
  const s = String(v).trim()
  if (/^\d{4}-\d{2}-\d{2}/.test(s)) {
    const [y, m, d] = s.slice(0, 10).split('-')
    return `${d}/${m}/${y}`
  }
  try {
    const dt = new Date(s)
    if (!Number.isNaN(dt.getTime())) return dt.toLocaleDateString('vi-VN')
  } catch {
    /* ignore */
  }
  return s || '—'
}

function fmtShortDt(v) {
  if (!v) return ''
  const s = String(v).trim()
  try {
    const d = new Date(s)
    if (!Number.isNaN(d.getTime())) {
      return d.toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })
    }
  } catch {
    /* ignore */
  }
  return s
}

function padRows(rows, min, factory) {
  const out = [...rows]
  while (out.length < min) out.push(factory())
  return out
}

const EMPTY_CARGO = () => ({
  name: '',
  qty: '',
  dimensions: '',
  weight: '',
  item_notes: '',
  pickup_at: '',
  pickup_place: '',
  pickup_contact: '',
  delivery_at: '',
  delivery_place: '',
  delivery_contact: '',
  transport_note: '',
  cost: '',
})

const EMPTY_PASS = () => ({
  depart_at: '',
  return_at: '',
  description: '',
  guests: '',
  pickup_place: '',
  dropoff_place: '',
  pickup: '',
  dropoff: '',
  person_in_charge: '',
  unit_price: '',
  extra_fee: '',
  notes: '',
})

const EMPTY_BIZ = () => ({
  description: '',
  guests: '',
  waypoint: '',
  notes: '',
  depart_at: '',
  pickup: '',
  return_at: '',
  dropoff: '',
  other: '',
  unit_price: '',
  extra_fee: '',
})

const cargoFilled = computed(() =>
  (Array.isArray(snap.value.cargoRows) ? snap.value.cargoRows : []).filter((r) => nz(r?.name)),
)
const cargoDisplayRows = computed(() =>
  padRows(cargoFilled.value.map((r) => ({ ...EMPTY_CARGO(), ...r })), 10, EMPTY_CARGO),
)

const passengerRowCount = computed(() =>
  Array.isArray(snap.value.passengerRows) ? snap.value.passengerRows.length : 0,
)
const passengerSnapshotTableRows = computed(() => {
  const raw = Array.isArray(snap.value.passengerRows) ? snap.value.passengerRows : []
  const merged = raw.map((r) => ({ ...EMPTY_PASS(), ...r }))
  return padRows(merged, 5, EMPTY_PASS)
})

const businessRowCount = computed(() =>
  Array.isArray(snap.value.businessRows) ? snap.value.businessRows.length : 0,
)
const businessSnapshotTableRows = computed(() => {
  const raw = Array.isArray(snap.value.businessRows) ? snap.value.businessRows : []
  const merged = raw.map((r) => ({ ...EMPTY_BIZ(), ...r }))
  return padRows(merged, 5, EMPTY_BIZ)
})

/** Column order: name,qty,dim,weight,notes, puT,puPl,puC, delT,delPl,delC, transport, cost */
function cargoCell(row, ci) {
  const vals = [
    row.name,
    row.qty,
    row.dimensions,
    row.weight,
    row.item_notes,
    row.pickup_at,
    row.pickup_place,
    row.pickup_contact,
    row.delivery_at,
    row.delivery_place,
    row.delivery_contact,
    row.transport_note,
    formatCostCell(row.name, row.cost),
  ]
  return vals[ci] ?? ''
}

const passengerFilled = computed(() =>
  (Array.isArray(snap.value.passengerRows) ? snap.value.passengerRows : []).filter(isPassengerRowFilled),
)

const businessFilled = computed(() =>
  (Array.isArray(snap.value.businessRows) ? snap.value.businessRows : []).filter(isBusinessRowFilled),
)

const passengerPriceDraftUnit = ref([])
const passengerPriceDraftExtra = ref([])
const businessPriceDraftUnit = ref([])
const businessPriceDraftExtra = ref([])

function fmtDraftStored(v) {
  if (v == null || v === '') return ''
  if (typeof v === 'number' && Number.isFinite(v)) {
    return formatVndWhileTyping(String(Math.round(v)))
  }
  return formatVndWhileTyping(String(v))
}

function syncInlinePriceDrafts() {
  const pr = props.req?.wizard_snapshot?.passengerRows
  const pa = Array.isArray(pr) ? pr : []
  passengerPriceDraftUnit.value = pa.map((r) => fmtDraftStored(r?.unit_price))
  passengerPriceDraftExtra.value = pa.map((r) => fmtDraftStored(r?.extra_fee))

  const br = props.req?.wizard_snapshot?.businessRows
  const ba = Array.isArray(br) ? br : []
  businessPriceDraftUnit.value = ba.map((r) => fmtDraftStored(r?.unit_price))
  businessPriceDraftExtra.value = ba.map((r) => fmtDraftStored(r?.extra_fee))
}

watch(
  () => [props.req?.id, props.req?.wizard_snapshot?.passengerRows, props.req?.wizard_snapshot?.businessRows],
  syncInlinePriceDrafts,
  { deep: true, immediate: true },
)

function parseMoney(v) {
  return parseMoneyVnd(v)
}

function formatCostCell(name, cost) {
  const n = parseMoney(cost)
  if (!nz(name) || n === 0) return ''
  return `${new Intl.NumberFormat('vi-VN').format(n)} đ`
}

function fmtMoney(n) {
  if (n == null || Number(n) === 0) return ''
  return `${new Intl.NumberFormat('vi-VN').format(Number(n))} đ`
}

function fmtMoneyRow(v) {
  const n = parseMoney(v)
  return n > 0 ? fmtMoney(n) : ''
}

function passDesc(r) {
  return nz(r.description) || nz(r.name)
}

function passQty(r) {
  return nz(r.guests)
}

function passPickup(r) {
  return nz(r.pickup_place) || nz(r.pickup)
}

function passDropoff(r) {
  return nz(r.dropoff_place) || nz(r.dropoff)
}

function onPassengerUnitInput(idx, raw) {
  const next = formatVndWhileTyping(raw)
  const cp = [...passengerPriceDraftUnit.value]
  if (idx < 0 || idx >= cp.length) return
  cp[idx] = next
  passengerPriceDraftUnit.value = cp
}

function onPassengerExtraInput(idx, raw) {
  const next = formatVndWhileTyping(raw)
  const cp = [...passengerPriceDraftExtra.value]
  if (idx < 0 || idx >= cp.length) return
  cp[idx] = next
  passengerPriceDraftExtra.value = cp
}

function onBusinessUnitInput(idx, raw) {
  const next = formatVndWhileTyping(raw)
  const cp = [...businessPriceDraftUnit.value]
  if (idx < 0 || idx >= cp.length) return
  cp[idx] = next
  businessPriceDraftUnit.value = cp
}

function onBusinessExtraInput(idx, raw) {
  const next = formatVndWhileTyping(raw)
  const cp = [...businessPriceDraftExtra.value]
  if (idx < 0 || idx >= cp.length) return
  cp[idx] = next
  businessPriceDraftExtra.value = cp
}

const grandTotalSum = computed(() => {
  if (isCargo.value) {
    return cargoFilled.value.reduce((s, r) => s + parseMoney(r.cost), 0)
  }
  if (props.showFillPriceSection && !isCargo.value && isBusiness.value) {
    let s = 0
    const n = businessRowCount.value
    for (let i = 0; i < n; i++) {
      s += parseMoneyVnd(businessPriceDraftUnit.value[i] ?? '')
      s += parseMoneyVnd(businessPriceDraftExtra.value[i] ?? '')
    }
    return s
  }
  if (props.showFillPriceSection && !isCargo.value && !isBusiness.value) {
    let s = 0
    const n = passengerRowCount.value
    for (let i = 0; i < n; i++) {
      s += parseMoneyVnd(passengerPriceDraftUnit.value[i] ?? '')
      s += parseMoneyVnd(passengerPriceDraftExtra.value[i] ?? '')
    }
    return s
  }
  if (isBusiness.value) {
    return businessFilled.value.reduce((acc, r) => acc + parseMoney(r.unit_price) + parseMoney(r.extra_fee), 0)
  }
  return passengerFilled.value.reduce((acc, r) => acc + parseMoney(r.unit_price) + parseMoney(r.extra_fee), 0)
})

const grandTotalFmt = computed(() => `${new Intl.NumberFormat('vi-VN').format(grandTotalSum.value)} đ`)

function buildRowsPayload() {
  if (isBusiness.value) {
    const n = businessRowCount.value
    const out = []
    for (let i = 0; i < n; i++) {
      out.push({
        unit_price: parseMoneyVnd(businessPriceDraftUnit.value[i] ?? ''),
        extra_fee: parseMoneyVnd(businessPriceDraftExtra.value[i] ?? ''),
      })
    }
    return out
  }
  const n = passengerRowCount.value
  const out = []
  for (let i = 0; i < n; i++) {
    out.push({
      unit_price: parseMoneyVnd(passengerPriceDraftUnit.value[i] ?? ''),
      extra_fee: parseMoneyVnd(passengerPriceDraftExtra.value[i] ?? ''),
    })
  }
  return out
}

function emitSaveRowPrices() {
  emit('save-row-prices', { rows: buildRowsPayload(), service_price: grandTotalSum.value })
}

const porterMoney = computed(() => parseMoney(form.value.porter_cost))
const interprovincialMoney = computed(() => parseMoney(form.value.interprovincial_cost))
</script>
