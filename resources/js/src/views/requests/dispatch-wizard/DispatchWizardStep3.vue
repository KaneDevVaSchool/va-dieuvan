<template>
  <div class="space-y-6 sm:space-y-8">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">3. Chi tiết chuyến (Nội dung đề nghị chuyến đi)</h2>
          </div>

          <!-- Passenger: E / e.1 / e.2 -->
          <div v-if="!w.isCargo" class="space-y-8">

            <!-- e.1 -->
            <section class="dw-e-block dw-e-block--e1 space-y-4" aria-labelledby="dw-e1-heading">
          
              <div class="dw-table-wrap -mx-1 rounded-xl border border-slate-200 shadow-sm ring-1 ring-slate-900/[0.04] sm:mx-0">
                <table class="min-w-[1280px] w-full border-collapse text-left text-[11px] sm:text-sm">
                  <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[10px] font-semibold uppercase text-slate-600 sm:text-xs">
                      <th class="min-w-[2.5rem] whitespace-normal px-1 py-2" rowspan="2">STT</th>
                      <th class="min-w-[13rem] px-1 py-2 text-center" colspan="2">Chuyến đi</th>
                      <th class="min-w-[13rem] px-1 py-2 text-center" colspan="2">Chuyến về</th>
                      <th class="min-w-[5rem] whitespace-normal px-1 py-2" rowspan="2">Số khách</th>
                      <th class="min-w-[10rem] whitespace-normal px-1 py-2" rowspan="2">Người phụ trách</th>
                      <th class="min-w-[7rem] whitespace-normal px-1 py-2" rowspan="2">Đơn giá</th>
                      <th class="min-w-[7rem] whitespace-normal px-1 py-2" rowspan="2">Phí phát sinh</th>
                      <th class="min-w-[6rem] whitespace-normal px-1 py-2" rowspan="2">Tổng dòng</th>
                      <th class="min-w-[10rem] whitespace-normal px-1 py-2" rowspan="2">Ghi chú</th>
                      <th class="w-8"></th>
                    </tr>
                    <tr class="border-b border-slate-200 bg-slate-50/90 text-[10px] normal-case text-slate-600">
                      <th class="min-w-[10rem] whitespace-normal px-1 py-1">Thời gian</th>
                      <th class="min-w-[10rem] whitespace-normal px-1 py-1">Địa điểm</th>
                      <th class="min-w-[10rem] whitespace-normal px-1 py-1">Thời gian</th>
                      <th class="min-w-[10rem] whitespace-normal px-1 py-1">Địa điểm</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, idx) in w.passengerRows" :key="'e1-' + idx" class="border-b border-slate-100 align-top">
                      <td class="px-1 py-1 text-slate-500">{{ idx + 1 }}</td>
                      <td class="min-w-[10rem] p-0.5"><input v-model="row.depart_at" type="datetime-local" class="dw-cell dw-cell--table" /></td>
                      <td class="min-w-[10rem] p-0.5"><input v-model="row.pickup" type="text" placeholder="Điểm đón" class="dw-cell dw-cell--table" /></td>
                      <td class="min-w-[10rem] p-0.5"><input v-model="row.return_at" type="datetime-local" class="dw-cell dw-cell--table" /></td>
                      <td class="min-w-[10rem] p-0.5"><input v-model="row.dropoff" type="text" placeholder="Điểm trả" class="dw-cell dw-cell--table" /></td>
                      <td class="min-w-[5rem] p-0.5"><input v-model="row.guests" type="number" min="1" placeholder="—" class="dw-cell dw-cell--table min-w-[4.5rem]" /></td>
                      <td class="min-w-[10rem] p-0.5"><input v-model="row.person_in_charge" type="text" placeholder="Họ tên + SĐT" class="dw-cell dw-cell--table" /></td>
                      <td class="min-w-[7.5rem] p-0.5"><input v-model="row.unit_price" type="number" min="0" step="1000" placeholder="0" class="dw-cell dw-cell--table" /></td>
                      <td class="min-w-[7.5rem] p-0.5"><input v-model="row.extra_fee" type="number" min="0" step="1000" placeholder="0" class="dw-cell dw-cell--table" /></td>
                      <td class="px-1 py-1 text-xs font-medium text-va-800">{{ w.formatCurrency(w.rowLineTotal(row)) }}</td>
                      <td class="min-w-[11rem] p-0.5"><input v-model="row.notes" type="text" placeholder="Ghi chú dòng…" class="dw-cell dw-cell--table" /></td>
                      <td class="px-0.5">
                        <button
                          v-if="w.passengerRows.length > 1"
                          type="button"
                          class="rounded p-1 text-rose-600 hover:bg-rose-50"
                          title="Xóa dòng"
                          @click="w.removePassengerRow(idx)"
                        >
                          <TrashIcon class="h-4 w-4" />
                        </button>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr class="bg-slate-50">
                      <td colspan="9" class="px-3 py-2 text-right text-xs font-medium text-slate-700">Tổng e.1 (ước tính)</td>
                      <td class="px-2 py-2 text-sm font-semibold text-va-800">{{ w.formatCurrency(w.passengerE1Total) }}</td>
                      <td colspan="2"></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
              <div class="dw-table-toolbar">
                <button type="button" class="dw-btn-row-add" @click="w.addPassengerRow">
                  <PlusIcon class="h-4 w-4" />
                  Thêm dòng
                </button>
                <label class="dw-table-toolbar__extra">
                  <input v-model="w.form.multi_day" type="checkbox" class="dw-table-toolbar__extra-check" />
                  <span>Dùng cho 3+ ngày (ghi chú trong tóm tắt)</span>
                </label>
              </div>
            </section>

            <!-- e.1.1 (ẩn với loại Điểm — Điểm) -->
            <section
              v-if="!w.isPointToPointTrip"
              class="dw-e-panel dw-e-panel--e11"
              aria-labelledby="dw-e11-heading"
            >
              <header class="dw-e-panel__head">
                <div>
                  <h3 id="dw-e11-heading" class="dw-e-panel__title">
                    Ghi chú khác đề xuất (nếu có)
                  </h3>
                </div>
              </header>

              <div class="dw-e11-flag">
                <label class="dw-e11-flag__row">
                  <input v-model="w.form.e1_use_3plus_days" type="checkbox" class="dw-e11-flag__check" />
                  <span class="dw-e11-flag__label">Thời gian sử dụng xe từ 03 ngày trở lên</span>
                </label>
              </div>

              <div class="dw-e11-fields">
                <div class="dw-e11-field">
                  <span class="dw-e11-field__label">Từ ngày</span>
                  <input
                    v-model="w.form.e1_from_date"
                    type="date"
                    lang="vi"
                    class="dw-input dw-input--e11"
                    @click="w.openDatePickerFromInput($event)"
                  />
                  <span class="dw-e11-field__hint">dd/mm/yyyy</span>
                </div>
                <div class="dw-e11-field">
                  <span class="dw-e11-field__label">Đến ngày</span>
                  <input
                    v-model="w.form.e1_to_date"
                    type="date"
                    lang="vi"
                    class="dw-input dw-input--e11"
                    @click="w.openDatePickerFromInput($event)"
                  />
                  <span class="dw-e11-field__hint">dd/mm/yyyy</span>
                </div>
                <div class="dw-e11-field">
                  <span class="dw-e11-field__label">Tổng số ngày phát sinh</span>
                  <input v-model="w.form.e1_days_total" type="text" class="dw-input dw-input--e11" placeholder="—" />
                  <span class="dw-e11-field__hint">Ghi số ngày hoặc để trống</span>
                </div>
                <div class="dw-e11-field">
                  <span class="dw-e11-field__label">Chi phí phát sinh</span>
                  <input
                    v-model="w.form.e1_extra_cost"
                    type="number"
                    min="0"
                    step="1000"
                    class="dw-input dw-input--e11"
                    placeholder="0"
                  />
                  <span class="dw-e11-field__hint">VNĐ (ước tính, gồm VAT nếu có)</span>
                </div>
              </div>

              <div class="dw-e11-weekwrap">
                <p id="dw-e11-weekdays-label" class="dw-e11-weekwrap__title">Bao gồm các thứ trong tuần từ</p>
                <div class="dw-weekday-strip" role="group" aria-labelledby="dw-e11-weekdays-label">
                  <button
                    v-for="wd in w.e1WeekdayOptions"
                    :key="wd.k"
                    type="button"
                    class="dw-weekday-chip"
                    :class="{ 'dw-weekday-chip--on': w.form.e1_weekdays[wd.k] }"
                    role="checkbox"
                    :aria-checked="!!w.form.e1_weekdays[wd.k]"
                    @click="w.toggleE1Weekday(wd.k)"
                  >
                    {{ wd.label }}
                  </button>
                </div>
              </div>
            </section>

            <!-- e.2 -->
            <section
              v-if="!w.isPointToPointTrip"
              class="dw-e-block dw-e-block--e2 space-y-4"
              aria-labelledby="dw-e2-heading"
            >
              <header class="dw-e-block__intro">
                <div>
                  <h3 id="dw-e2-heading" class="dw-e-block__title">
                    Nội dung đề xuất cho nhân sự đi công tác
                  </h3>
                </div>
              </header>
              <div class="dw-table-wrap -mx-1 rounded-xl border border-slate-200 shadow-sm ring-1 ring-slate-900/[0.04] sm:mx-0">
                <table class="min-w-[1360px] w-full border-collapse text-left text-[11px] sm:text-sm">
                  <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[10px] font-semibold uppercase text-slate-600 sm:text-xs">
                      <th class="min-w-[2.5rem] whitespace-normal px-1 py-2" rowspan="2">STT</th>
                      <th class="min-w-[13rem] px-1 py-2 text-center" colspan="2">Chuyến đi</th>
                      <th class="min-w-[10rem] whitespace-normal px-1 py-2" rowspan="2">Điểm dừng giữa lịch trình</th>
                      <th class="min-w-[13rem] px-1 py-2 text-center" colspan="2">Chuyến về</th>
                      <th class="min-w-[5rem] whitespace-normal px-1 py-2" rowspan="2">Số khách</th>
                      <th class="min-w-[7rem] whitespace-normal px-1 py-2" rowspan="2">Đơn giá</th>
                      <th class="min-w-[7rem] whitespace-normal px-1 py-2" rowspan="2">Phí phát sinh</th>
                      <th class="min-w-[6rem] whitespace-normal px-1 py-2" rowspan="2">Tổng dòng</th>
                      <th class="min-w-[10rem] whitespace-normal px-1 py-2" rowspan="2">Ghi chú</th>
                      <th class="w-8"></th>
                    </tr>
                    <tr class="border-b border-slate-200 bg-slate-50/90 text-[10px] normal-case text-slate-600">
                      <th class="min-w-[10rem] whitespace-normal px-1 py-1">Thời gian</th>
                      <th class="min-w-[10rem] whitespace-normal px-1 py-1">Địa điểm</th>
                      <th class="min-w-[10rem] whitespace-normal px-1 py-1">Thời gian</th>
                      <th class="min-w-[10rem] whitespace-normal px-1 py-1">Địa điểm</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, idx) in w.businessRows" :key="'e2-' + idx" class="border-b border-slate-100 align-top">
                      <td class="px-1 py-1 text-slate-500">{{ idx + 1 }}</td>
                      <td class="min-w-[10rem] p-0.5"><input v-model="row.depart_at" type="datetime-local" class="dw-cell dw-cell--table" /></td>
                      <td class="min-w-[10rem] p-0.5"><input v-model="row.pickup" type="text" placeholder="Điểm đón" class="dw-cell dw-cell--table" /></td>
                      <td class="min-w-[10rem] p-0.5"><input v-model="row.waypoint" type="text" placeholder="Điểm dừng" class="dw-cell dw-cell--table" /></td>
                      <td class="min-w-[10rem] p-0.5"><input v-model="row.return_at" type="datetime-local" class="dw-cell dw-cell--table" /></td>
                      <td class="min-w-[10rem] p-0.5"><input v-model="row.dropoff" type="text" placeholder="Điểm trả" class="dw-cell dw-cell--table" /></td>
                      <td class="min-w-[5rem] p-0.5"><input v-model="row.guests" type="number" min="1" placeholder="—" class="dw-cell dw-cell--table min-w-[4.5rem]" /></td>
                      <td class="min-w-[7.5rem] p-0.5"><input v-model="row.unit_price" type="number" min="0" step="1000" placeholder="0" class="dw-cell dw-cell--table" /></td>
                      <td class="min-w-[7.5rem] p-0.5"><input v-model="row.extra_fee" type="number" min="0" step="1000" placeholder="0" class="dw-cell dw-cell--table" /></td>
                      <td class="px-1 py-1 text-xs font-medium text-va-800">{{ w.formatCurrency(w.rowLineTotal(row)) }}</td>
                      <td class="min-w-[11rem] p-0.5"><input v-model="row.notes" type="text" placeholder="Ghi chú dòng…" class="dw-cell dw-cell--table" /></td>
                      <td class="px-0.5">
                        <button
                          v-if="w.businessRows.length > 1"
                          type="button"
                          class="rounded p-1 text-rose-600 hover:bg-rose-50"
                          title="Xóa dòng"
                          @click="w.removeBusinessRow(idx)"
                        >
                          <TrashIcon class="h-4 w-4" />
                        </button>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr class="bg-slate-50">
                      <td colspan="9" class="px-3 py-2 text-right text-xs font-medium text-slate-700">Tổng e.2 (ước tính)</td>
                      <td class="px-2 py-2 text-sm font-semibold text-va-800">{{ w.formatCurrency(w.passengerE2Total) }}</td>
                      <td colspan="2"></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
              <div class="dw-table-toolbar">
                <button
                  type="button"
                  class="dw-btn-row-add"
                  @click="w.addBusinessRow"
                >
                  <PlusIcon class="h-4 w-4" />
                  Thêm dòng
                </button>
              </div>
            </section>

            <!-- e.2.1 -->
            <section
              v-if="!w.isPointToPointTrip"
              class="dw-e-panel dw-e-panel--e21"
              aria-labelledby="dw-e21-heading"
            >
              <header class="dw-e-panel__head">
                <div>
                  <h3 id="dw-e21-heading" class="dw-e-panel__title">
                    Ghi chú khác đề xuất (nếu có)
                  </h3>
                </div>
              </header>
              <div class="dw-e21-rows">
                <div class="dw-e21-row">
                  <label class="dw-e21-row__opt">
                    <input v-model="w.form.e2_door_pickup" type="checkbox" class="dw-e21-row__check" />
                    <span class="dw-e21-row__label">Xe đưa đón tận nhà</span>
                  </label>
                  <div class="dw-e21-row__cost">
                    <span class="dw-e21-row__cost-label">Chi phí phát sinh</span>
                    <input
                      v-model="w.form.e2_door_cost"
                      type="number"
                      min="0"
                      step="1000"
                      placeholder="0"
                      class="dw-e21-row__input"
                    />
                    <span class="dw-e21-row__unit">VNĐ</span>
                  </div>
                </div>
                <div class="dw-e21-row">
                  <label class="dw-e21-row__opt">
                    <input v-model="w.form.e2_driver_self" type="checkbox" class="dw-e21-row__check" />
                    <span class="dw-e21-row__label">Tài xế tự túc (ăn uống, khách sạn…)</span>
                  </label>
                  <div class="dw-e21-row__cost">
                    <span class="dw-e21-row__cost-label">Chi phí phát sinh</span>
                    <input
                      v-model="w.form.e2_driver_self_cost"
                      type="number"
                      min="0"
                      step="1000"
                      placeholder="0"
                      class="dw-e21-row__input"
                    />
                    <span class="dw-e21-row__unit">VNĐ</span>
                  </div>
                </div>
                <div class="dw-e21-row">
                  <label class="dw-e21-row__opt">
                    <input v-model="w.form.e2_after_21h" type="checkbox" class="dw-e21-row__check" />
                    <span class="dw-e21-row__label">Có nhu cầu sử dụng xe sau 21h trong ngày</span>
                  </label>
                  <div class="dw-e21-row__cost">
                    <span class="dw-e21-row__cost-label">Chi phí phát sinh</span>
                    <input
                      v-model="w.form.e2_after_21h_cost"
                      type="number"
                      min="0"
                      step="1000"
                      placeholder="0"
                      class="dw-e21-row__input"
                    />
                    <span class="dw-e21-row__unit">VNĐ</span>
                  </div>
                </div>
              </div>
            </section>
          </div>

          <!-- Cargo table -->
          <div v-else class="space-y-4">
            <div class="rounded-xl border border-slate-200 bg-gradient-to-b from-slate-50/90 to-white px-4 py-3 sm:px-5">
              <h3 class="text-sm font-bold uppercase tracking-wide text-va-800">Nội dung đề nghị vận chuyển</h3>
              <p class="mt-1 text-xs text-slate-600">Nội dung chi tiết</p>
            </div>
            <div class="dw-table-wrap rounded-xl border border-slate-200">
            <table class="min-w-[1420px] w-full border-collapse text-left text-xs sm:text-sm">
              <thead>
                <tr class="border-b border-slate-200 bg-slate-50 text-[10px] uppercase leading-tight text-slate-600 sm:text-xs">
                  <th class="min-w-[2.5rem] whitespace-normal px-1 py-2">STT</th>
                  <th class="min-w-[9rem] whitespace-normal px-1 py-2">Tên HH</th>
                  <th class="min-w-[3.5rem] whitespace-normal px-1 py-2">SL</th>
                  <th class="min-w-[8rem] whitespace-normal px-1 py-2">Kích thước (1 kiện)</th>
                  <th class="min-w-[7rem] whitespace-normal px-1 py-2">KL (1 kiện)</th>
                  <th class="min-w-[9rem] whitespace-normal px-1 py-2">Ghi chú HH</th>
                  <th class="min-w-[20rem] border-l border-slate-200 px-1 py-2" colspan="3">Điểm tập kết</th>
                  <th class="min-w-[20rem] border-l border-slate-200 px-1 py-2" colspan="3">Điểm giao</th>
                  <th class="min-w-[10rem] whitespace-normal px-1 py-2">VC / ghi chú NV</th>
                  <th class="min-w-[7rem] whitespace-normal px-1 py-2">Chi phí</th>
                  <th class="w-8"></th>
                </tr>
                <tr class="border-b border-slate-200 bg-slate-50/80 text-[10px] normal-case text-slate-600">
                  <th colspan="6"></th>
                  <th class="min-w-[10rem] border-l border-slate-200 whitespace-normal px-1 py-1">Thời gian</th>
                  <th class="min-w-[10rem] whitespace-normal px-1 py-1">Địa điểm</th>
                  <th class="min-w-[9rem] whitespace-normal px-1 py-1">Người giao</th>
                  <th class="min-w-[10rem] border-l border-slate-200 whitespace-normal px-1 py-1">Thời gian</th>
                  <th class="min-w-[10rem] whitespace-normal px-1 py-1">Địa điểm</th>
                  <th class="min-w-[9rem] whitespace-normal px-1 py-1">Người nhận</th>
                  <th colspan="2"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in w.cargoRows" :key="idx" class="border-b border-slate-100 align-top">
                  <td class="px-1 py-1 text-slate-500">{{ idx + 1 }}</td>
                  <td class="min-w-[9rem] p-0.5"><input v-model="row.name" type="text" placeholder="Tên hàng hóa" class="dw-cell dw-cell--table" /></td>
                  <td class="min-w-[3.5rem] p-0.5"><input v-model="row.qty" type="text" placeholder="SL" class="dw-cell dw-cell--table min-w-[3.25rem]" /></td>
                  <td class="min-w-[8rem] p-0.5"><input v-model="row.dimensions" type="text" placeholder="Dài × rộng × cao (cm)" class="dw-cell dw-cell--table" /></td>
                  <td class="min-w-[7rem] p-0.5"><input v-model="row.weight" type="text" placeholder="kg / kiện" class="dw-cell dw-cell--table" /></td>
                  <td class="min-w-[9rem] p-0.5"><input v-model="row.item_notes" type="text" placeholder="Mô tả thêm…" class="dw-cell dw-cell--table" /></td>
                  <td class="min-w-[10rem] border-l border-slate-200 p-0.5"><input v-model="row.pickup_at" type="datetime-local" class="dw-cell dw-cell--table" /></td>
                  <td class="min-w-[10rem] p-0.5"><input v-model="row.pickup_place" type="text" placeholder="Địa chỉ tập kết" class="dw-cell dw-cell--table" /></td>
                  <td class="min-w-[9rem] p-0.5"><input v-model="row.pickup_contact" type="text" placeholder="Họ tên + SĐT" class="dw-cell dw-cell--table" /></td>
                  <td class="min-w-[10rem] border-l border-slate-200 p-0.5"><input v-model="row.delivery_at" type="datetime-local" class="dw-cell dw-cell--table" /></td>
                  <td class="min-w-[10rem] p-0.5"><input v-model="row.delivery_place" type="text" placeholder="Địa chỉ giao" class="dw-cell dw-cell--table" /></td>
                  <td class="min-w-[9rem] p-0.5"><input v-model="row.delivery_contact" type="text" placeholder="Họ tên + SĐT" class="dw-cell dw-cell--table" /></td>
                  <td class="min-w-[10rem] p-0.5"><input v-model="row.transport_note" type="text" placeholder="Xe VA / NCC…" class="dw-cell dw-cell--table" /></td>
                  <td class="min-w-[7.5rem] p-0.5"><input v-model="row.cost" type="number" min="0" step="1000" placeholder="0" class="dw-cell dw-cell--table" /></td>
                  <td class="px-0.5">
                    <button
                      v-if="w.cargoRows.length > 1"
                      type="button"
                      class="rounded p-1 text-rose-600 hover:bg-rose-50"
                      @click="w.removeCargoRow(idx)"
                    >
                      <TrashIcon class="h-4 w-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="bg-slate-50">
                  <td colspan="13" class="px-3 py-2 text-right font-medium text-slate-700">Tổng</td>
                  <td class="px-2 py-2 font-semibold text-va-800">{{ w.formatCurrency(w.cargoTotal) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
            <div class="border-t border-slate-200 bg-white p-3">
              <button
                type="button"
                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-va-800 shadow-sm hover:bg-slate-50"
                @click="w.addCargoRow"
              >
                <PlusIcon class="h-4 w-4" />
                Thêm dòng hàng
              </button>
            </div>
            </div>

            <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
            <div class="text-xs font-semibold uppercase text-slate-600">e.1.1 Ghi chú &amp; phát sinh</div>
            <label class="block">
              <span class="mb-1 block text-xs font-medium text-slate-600">Ghi chú khác (nếu có)</span>
              <textarea v-model="w.form.cargo_extra_notes" rows="2" class="dw-input min-h-[3.5rem] resize-y" />
            </label>
            <div class="divide-y divide-slate-200/80 rounded-lg border border-slate-200/80 bg-white/60">
              <div class="flex flex-col gap-2 px-3 py-3 sm:flex-row sm:items-center sm:gap-4 sm:py-2.5">
                <label class="flex min-w-0 flex-1 cursor-pointer items-start gap-2.5 sm:items-center">
                  <input v-model="w.form.need_porters" type="checkbox" class="mt-0.5 shrink-0 rounded border-slate-300 text-va-800 sm:mt-0" />
                  <span class="text-sm leading-snug text-slate-800">Yêu cầu bốc xếp / nhân công hỗ trợ</span>
                </label>
                <div class="flex min-w-0 flex-wrap items-center gap-2 sm:max-w-[28rem] sm:justify-end">
                  <span class="shrink-0 text-xs font-medium text-slate-600">Số lượng</span>
                  <input v-model="w.form.porter_qty" type="text" placeholder="VD: 2 người" class="dw-cell dw-cell--e21 min-w-[6rem] max-w-[10rem]" />
                  <span class="shrink-0 text-xs font-medium text-slate-600">Chi phí (VNĐ)</span>
                  <input
                    v-model="w.form.porter_cost"
                    type="number"
                    min="0"
                    step="1000"
                    placeholder="0 — ước tính"
                    class="dw-cell dw-cell--e21 min-w-[10rem] flex-1 sm:max-w-[14rem]"
                  />
                </div>
              </div>
              <div class="flex flex-col gap-2 px-3 py-3 sm:flex-row sm:items-center sm:gap-4 sm:py-2.5">
                <label class="flex min-w-0 flex-1 cursor-pointer items-start gap-2.5 sm:items-center">
                  <input v-model="w.form.interprovincial" type="checkbox" class="mt-0.5 shrink-0 rounded border-slate-300 text-va-800 sm:mt-0" />
                  <span class="text-sm leading-snug text-slate-800">Gửi chành xe đi tỉnh</span>
                </label>
                <div class="flex min-w-0 shrink-0 items-center gap-2 sm:w-[min(100%,20rem)] sm:justify-end">
                  <span class="shrink-0 text-xs font-medium text-slate-600">Chi phí phát sinh (VNĐ)</span>
                  <input
                    v-model="w.form.interprovincial_cost"
                    type="number"
                    min="0"
                    step="1000"
                    placeholder="0 — ước tính"
                    class="dw-cell dw-cell--e21 min-w-[10rem] flex-1 sm:max-w-[14rem]"
                  />
                </div>
              </div>
            </div>
          </div>
          </div>
  </div>
</template>
<script setup>
import { inject } from 'vue'
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { DISPATCH_WIZARD_KEY } from './injectionKeys'

const w = inject(DISPATCH_WIZARD_KEY)
if (!w) throw new Error('DispatchWizardStep3: missing DISPATCH_WIZARD_KEY provider')
</script>
