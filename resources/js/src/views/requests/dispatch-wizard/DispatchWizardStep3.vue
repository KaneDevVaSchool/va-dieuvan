<template>
    <div class="dw-step3-root">
        <div class="dw-step3-hero">
            <h2 class="text-lg font-semibold text-slate-900">
                3. Chi tiết chuyến (Nội dung đề nghị chuyến đi)
            </h2>
            <div class="dw-step3-guide mt-3" role="note">
                <p class="dw-step3-guide__title">Hướng dẫn điền</p>
                <div class="dw-step3-guide__body">
                    <p v-if="!isCargo">
                        <span class="dw-req font-semibold">*</span>
                        trên cột là trường cần ưu tiên. Để qua bước này: điền
                        <strong class="text-slate-800"
                            >ít nhất một ô thời gian</strong
                        >
                        (đi hoặc về) trong toàn bộ bảng và có
                        <strong class="text-slate-800">ít nhất một dòng</strong>
                        có lịch trình / địa điểm. Số khách, đơn giá, phí… có thể
                        bổ sung sau nếu chưa rõ.
                    </p>
                    <p v-else>
                        <span class="dw-req font-semibold">*</span>
                        <strong class="text-slate-800">Tên hàng hóa</strong>
                        bắt buộc cho ít nhất một dòng; cần
                        <strong class="text-slate-800"
                            >ít nhất một thời gian</strong
                        >
                        (tập kết hoặc giao). Các cột khác giúp bộ phận điều vận
                        ước lượng xe — điền càng đầy càng tốt.
                    </p>
                </div>
            </div>
        </div>

        <!-- Passenger: E / e.1 / e.2 -->
        <div v-if="!isCargo" class="dw-step3-stack">
            <!-- e.1 (ẩn với loại Đi công tác — chỉ dùng bảng e.2) -->
            <section
                v-if="!isBusinessTrip"
                class="dw-step3-section"
                aria-labelledby="dw-e1-heading"
            >
                <header class="dw-sec-intro">
                    <h3 id="dw-e1-heading" class="dw-sec-intro__title">
                        Bảng chi tiết hành khách / chương trình (e.1)
                    </h3>
                    <p class="dw-sec-intro__meta">
                        Mỗi dòng là một lượt hoặc một nhóm. Chọn ngày giờ bằng
                        lịch trình duyệt; địa điểm ghi rõ địa chỉ hoặc tên điểm
                        để tài xế chủ động.
                    </p>
                </header>
                <div class="dw-table-wrap -mx-1 sm:mx-0">
                    <div class="dw-table-detail">
                        <table
                            class="min-w-[1280px] w-full border-collapse text-left text-[11px] sm:text-sm"
                        >
                            <thead>
                                <tr
                                    class="border-b border-slate-200 bg-slate-50 text-[10px] font-semibold uppercase text-slate-600 sm:text-xs"
                                >
                                    <th
                                        class="min-w-[2.5rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        STT
                                    </th>
                                    <th
                                        class="min-w-[13rem] px-1 py-2 text-center"
                                        colspan="2"
                                    >
                                        Chuyến đi
                                    </th>
                                    <th
                                        class="min-w-[13rem] px-1 py-2 text-center"
                                        colspan="2"
                                    >
                                        Chuyến về
                                    </th>
                                    <th
                                        class="min-w-[5rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        Số khách
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        Người phụ trách
                                    </th>
                                    <th
                                        class="min-w-[7rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        Đơn giá
                                    </th>
                                    <th
                                        class="min-w-[7rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        Phí phát sinh
                                    </th>
                                    <th
                                        class="min-w-[6rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        Tổng dòng
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        Ghi chú
                                    </th>
                                    <th class="w-8"></th>
                                </tr>
                                <tr
                                    class="border-b border-slate-200 bg-slate-50/90 text-[10px] normal-case text-slate-600"
                                >
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        Thời gian
                                        <span
                                            class="dw-th-req"
                                            title="Ưu tiên điền"
                                            >*</span
                                        >
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        Địa điểm
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        Thời gian
                                        <span
                                            class="dw-th-req"
                                            title="Ưu tiên điền"
                                            >*</span
                                        >
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        Địa điểm
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, idx) in passengerRows"
                                    :key="'e1-' + idx"
                                    class="border-b border-slate-100 align-top"
                                >
                                    <td class="px-1 py-1 text-slate-500">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.depart_at"
                                            type="datetime-local"
                                            class="dw-cell dw-cell--table dw-date-input"
                                            title="Giờ xuất phát chuyến đi"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.pickup"
                                            type="text"
                                            placeholder="VD: cổng trường, địa chỉ đón"
                                            class="dw-cell dw-cell--table"
                                            title="Điểm đón chuyến đi"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.return_at"
                                            type="datetime-local"
                                            class="dw-cell dw-cell--table dw-date-input"
                                            title="Giờ về / kết thúc chuyến về"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.dropoff"
                                            type="text"
                                            placeholder="VD: điểm trả, địa chỉ"
                                            class="dw-cell dw-cell--table"
                                            title="Điểm trả chuyến về"
                                        />
                                    </td>
                                    <td class="min-w-[5rem] p-0.5">
                                        <input
                                            v-model="row.guests"
                                            type="number"
                                            min="1"
                                            placeholder="Số người"
                                            class="dw-cell dw-cell--table min-w-[4.5rem]"
                                            title="Số hành khách (tối thiểu 1)"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.person_in_charge"
                                            type="text"
                                            placeholder="Họ và tên người phụ trách"
                                            class="dw-cell dw-cell--table"
                                        />
                                    </td>
                                    <td class="min-w-[7.5rem] p-0.5">
                                        <input
                                            :value="row.unit_price"
                                            type="text"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            placeholder="VD: 1.500.000"
                                            class="dw-cell dw-cell--table dw-cell--vnd"
                                            title="Đơn giá ước tính — gõ số, tự thêm dấu ."
                                            @input="vndRow(row, 'unit_price', $event)"
                                        />
                                    </td>
                                    <td class="min-w-[7.5rem] p-0.5">
                                        <input
                                            :value="row.extra_fee"
                                            type="text"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            placeholder="VD: 200.000"
                                            class="dw-cell dw-cell--table dw-cell--vnd"
                                            title="Phí phát sinh — gõ số, tự thêm dấu ."
                                            @input="vndRow(row, 'extra_fee', $event)"
                                        />
                                    </td>
                                    <td
                                        class="px-1 py-1 text-xs font-medium text-va-800"
                                    >
                                        {{ formatCurrency(rowLineTotal(row)) }}
                                    </td>
                                    <td class="min-w-[11rem] p-0.5">
                                        <input
                                            v-model="row.notes"
                                            type="text"
                                            placeholder="Ghi chú cho dòng (tuỳ chọn)"
                                            class="dw-cell dw-cell--table"
                                        />
                                    </td>
                                    <td class="px-0.5">
                                        <button
                                            v-if="passengerRows.length > 1"
                                            type="button"
                                            class="rounded p-1 text-rose-600 hover:bg-rose-50"
                                            title="Xóa dòng"
                                            @click="removePassengerRow(idx)"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-50">
                                    <td
                                        colspan="9"
                                        class="px-3 py-2 text-right text-xs font-medium text-slate-700"
                                    >
                                        Tổng (ước tính)
                                    </td>
                                    <td
                                        class="px-2 py-2 text-sm font-semibold text-va-800"
                                    >
                                        {{ formatCurrency(passengerE1Total) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="dw-table-toolbar">
                    <button
                        type="button"
                        class="dw-btn-row-add"
                        @click="addPassengerRow"
                    >
                        <PlusIcon class="h-4 w-4" />
                        Thêm dòng
                    </button>
                    <label class="dw-table-toolbar__extra">
                        <input
                            v-model="form.multi_day"
                            type="checkbox"
                            class="dw-table-toolbar__extra-check"
                        />
                        <span title="Đánh dấu khi lịch trình kéo dài nhiều ngày"
                            >Dùng cho 3+ ngày (ghi chú trong tóm tắt)</span
                        >
                    </label>
                </div>
            </section>

            <!-- e.1.1 (ẩn P2P thường & ẩn Đi công tác) -->
            <section
                v-if="showPassengerTripExtras && !isBusinessTrip"
                class="dw-step3-section dw-e-panel dw-e-panel--e11"
                aria-labelledby="dw-e11-heading"
            >
                <header class="dw-e-panel__head">
                    <div>
                        <h3 id="dw-e11-heading" class="dw-e-panel__title">
                            Ghi chú khác đề xuất (nếu có)
                        </h3>
                        <p
                            v-if="isP2PExtracurricular"
                            class="dw-e-panel__lede mt-1 max-w-none text-xs font-normal normal-case text-slate-600"
                        >
                            Thời gian sử dụng xe từ 03 ngày trở lên — gợi ý chi
                            phí biểu mẫu
                            <strong class="font-medium">5.000.000&nbsp;₫</strong
                            >. Vui lòng liên hệ NV Điều vận để điền / xác nhận
                            chi phí.
                        </p>
                    </div>
                </header>

                <div class="dw-e11-flag">
                    <label class="dw-e11-flag__row">
                        <input
                            v-model="form.e1_use_3plus_days"
                            type="checkbox"
                            class="dw-e11-flag__check"
                        />
                        <span class="dw-e11-flag__label"
                            >Thời gian sử dụng xe từ 03 ngày trở lên</span
                        >
                    </label>
                </div>

                <div class="dw-e11-fields">
                    <div class="dw-e11-field">
                        <span class="dw-e11-field__label"
                            >Từ ngày
                            <span class="font-normal text-slate-400"
                                >(tuỳ chọn)</span
                            ></span
                        >
                        <input
                            v-model="form.e1_from_date"
                            type="date"
                            lang="vi"
                            class="dw-input dw-input--e11 dw-date-input"
                            title="Ngày bắt đầu dùng xe nhiều ngày"
                            @click="openDatePickerFromInput($event)"
                        />
                        <span class="dw-e11-field__hint"
                            >Chọn trên lịch — định dạng dd/mm/yyyy</span
                        >
                    </div>
                    <div class="dw-e11-field">
                        <span class="dw-e11-field__label"
                            >Đến ngày
                            <span class="font-normal text-slate-400"
                                >(tuỳ chọn)</span
                            ></span
                        >
                        <input
                            v-model="form.e1_to_date"
                            type="date"
                            lang="vi"
                            class="dw-input dw-input--e11 dw-date-input"
                            title="Ngày kết thúc"
                            @click="openDatePickerFromInput($event)"
                        />
                        <span class="dw-e11-field__hint"
                            >Phải sau hoặc trùng «Từ ngày» nếu điền cả hai</span
                        >
                    </div>
                    <div class="dw-e11-field">
                        <span class="dw-e11-field__label"
                            >Tổng số ngày phát sinh</span
                        >
                        <input
                            v-model="form.e1_days_total"
                            type="text"
                            inputmode="numeric"
                            class="dw-input dw-input--e11"
                            placeholder="VD: 3"
                            title="Số ngày dự kiến dùng xe"
                        />
                        <span class="dw-e11-field__hint"
                            >Có thể để trống — NV Điều vận sẽ xác nhận</span
                        >
                    </div>
                    <div class="dw-e11-field">
                        <span class="dw-e11-field__label"
                            >Chi phí phát sinh</span
                        >
                        <input
                            :value="form.e1_extra_cost"
                            type="text"
                            inputmode="numeric"
                            autocomplete="off"
                            class="dw-input dw-input--e11 dw-cell--vnd"
                            placeholder="VD: 5.000.000"
                            title="Liên hệ NV Điều vận nếu chưa biết mức"
                            @input="vndForm('e1_extra_cost', $event)"
                        />
                        <span class="dw-e11-field__hint"
                            >Ước tính, gồm VAT nếu có</span
                        >
                    </div>
                </div>

                <div class="dw-e11-weekwrap">
                    <p
                        id="dw-e11-weekdays-label"
                        class="dw-e11-weekwrap__title"
                    >
                        Bao gồm các thứ trong tuần
                        <span class="font-normal text-slate-500"
                            >(tuỳ chọn — bấm để chọn/bỏ)</span
                        >
                    </p>
                    <div
                        class="dw-weekday-strip"
                        role="group"
                        aria-labelledby="dw-e11-weekdays-label"
                    >
                        <button
                            v-for="wd in e1WeekdayOptions"
                            :key="wd.k"
                            type="button"
                            class="dw-weekday-chip"
                            :class="{
                                'dw-weekday-chip--on': form.e1_weekdays[wd.k],
                            }"
                            role="checkbox"
                            :aria-checked="!!form.e1_weekdays[wd.k]"
                            @click="toggleE1Weekday(wd.k)"
                        >
                            {{ wd.label }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- e.2 -->
            <section
                v-if="!isPointToPointTrip"
                class="dw-step3-section"
                aria-labelledby="dw-e2-heading"
            >
                <header class="dw-sec-intro">
                    <h3 id="dw-e2-heading" class="dw-sec-intro__title">
                        Nội dung đề xuất cho nhân sự đi công tác
                    </h3>
                    <p class="dw-sec-intro__meta">
                        Ghi đủ thời gian — địa điểm giúp lập lộ trình. Cột «Điểm
                        dừng» dùng khi có điểm trung chuyển; để trống nếu đi
                        thẳng.
                    </p>
                </header>
                <div class="dw-table-wrap -mx-1 sm:mx-0">
                    <div class="dw-table-detail">
                        <table
                            class="min-w-[1360px] w-full border-collapse text-left text-[11px] sm:text-sm"
                        >
                            <thead>
                                <tr
                                    class="border-b border-slate-200 bg-slate-50 text-[10px] font-semibold uppercase text-slate-600 sm:text-xs"
                                >
                                    <th
                                        class="min-w-[2.5rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        STT
                                    </th>
                                    <th
                                        class="min-w-[13rem] px-1 py-2 text-center"
                                        colspan="2"
                                    >
                                        Chuyến đi
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        Điểm dừng giữa lịch trình
                                    </th>
                                    <th
                                        class="min-w-[13rem] px-1 py-2 text-center"
                                        colspan="2"
                                    >
                                        Chuyến về
                                    </th>
                                    <th
                                        class="min-w-[5rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        Số khách
                                    </th>
                                    <th
                                        class="min-w-[7rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        Đơn giá
                                    </th>
                                    <th
                                        class="min-w-[7rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        Phí phát sinh
                                    </th>
                                    <th
                                        class="min-w-[6rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        Tổng dòng
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        Ghi chú
                                    </th>
                                    <th class="w-8"></th>
                                </tr>
                                <tr
                                    class="border-b border-slate-200 bg-slate-50/90 text-[10px] normal-case text-slate-600"
                                >
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        Thời gian
                                        <span
                                            class="dw-th-req"
                                            title="Ưu tiên điền"
                                            >*</span
                                        >
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        Địa điểm
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        Thời gian
                                        <span
                                            class="dw-th-req"
                                            title="Ưu tiên điền"
                                            >*</span
                                        >
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        Địa điểm
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, idx) in businessRows"
                                    :key="'e2-' + idx"
                                    class="border-b border-slate-100 align-top"
                                >
                                    <td class="px-1 py-1 text-slate-500">
                                        {{ idx + 1 }}
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.depart_at"
                                            type="datetime-local"
                                            class="dw-cell dw-cell--table dw-date-input"
                                            title="Giờ xuất phát công tác"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.pickup"
                                            type="text"
                                            placeholder="VD: VP, sân bay, địa chỉ đón"
                                            class="dw-cell dw-cell--table"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.waypoint"
                                            type="text"
                                            placeholder="Điểm dừng (tuỳ chọn)"
                                            class="dw-cell dw-cell--table"
                                            title="Trung chuyển giữa chặng"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.return_at"
                                            type="datetime-local"
                                            class="dw-cell dw-cell--table dw-date-input"
                                            title="Giờ về"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.dropoff"
                                            type="text"
                                            placeholder="VD: khách sạn, điểm trả"
                                            class="dw-cell dw-cell--table"
                                        />
                                    </td>
                                    <td class="min-w-[5rem] p-0.5">
                                        <input
                                            v-model="row.guests"
                                            type="number"
                                            min="1"
                                            placeholder="Số người"
                                            class="dw-cell dw-cell--table min-w-[4.5rem]"
                                            title="Số nhân sự / khách trên dòng"
                                        />
                                    </td>
                                    <td class="min-w-[7.5rem] p-0.5">
                                        <input
                                            :value="row.unit_price"
                                            type="text"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            placeholder="VD: 1.500.000"
                                            class="dw-cell dw-cell--table dw-cell--vnd"
                                            title="Đơn giá — gõ số, tự thêm dấu ."
                                            @input="vndRow(row, 'unit_price', $event)"
                                        />
                                    </td>
                                    <td class="min-w-[7.5rem] p-0.5">
                                        <input
                                            :value="row.extra_fee"
                                            type="text"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            placeholder="VD: 200.000"
                                            class="dw-cell dw-cell--table dw-cell--vnd"
                                            title="Phụ phí — gõ số, tự thêm dấu ."
                                            @input="vndRow(row, 'extra_fee', $event)"
                                        />
                                    </td>
                                    <td
                                        class="px-1 py-1 text-xs font-medium text-va-800"
                                    >
                                        {{ formatCurrency(rowLineTotal(row)) }}
                                    </td>
                                    <td class="min-w-[11rem] p-0.5">
                                        <input
                                            v-model="row.notes"
                                            type="text"
                                            placeholder="Ghi chú dòng (tuỳ chọn)"
                                            class="dw-cell dw-cell--table"
                                        />
                                    </td>
                                    <td class="px-0.5">
                                        <button
                                            v-if="businessRows.length > 1"
                                            type="button"
                                            class="rounded p-1 text-rose-600 hover:bg-rose-50"
                                            title="Xóa dòng"
                                            @click="removeBusinessRow(idx)"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-50">
                                    <td
                                        colspan="9"
                                        class="px-3 py-2 text-right text-xs font-medium text-slate-700"
                                    >
                                        Tổng e.2 (ước tính)
                                    </td>
                                    <td
                                        class="px-2 py-2 text-sm font-semibold text-va-800"
                                    >
                                        {{ formatCurrency(passengerE2Total) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="dw-table-toolbar">
                    <button
                        type="button"
                        class="dw-btn-row-add"
                        @click="addBusinessRow"
                    >
                        <PlusIcon class="h-4 w-4" />
                        Thêm dòng
                    </button>
                </div>
            </section>

            <!-- e.2.1 -->
            <section
                v-if="showPassengerTripExtras"
                class="dw-step3-section dw-e-panel dw-e-panel--e21"
                aria-labelledby="dw-e21-heading"
            >
                <header class="dw-e-panel__head">
                    <div>
                        <h3 id="dw-e21-heading" class="dw-e-panel__title">
                            Ghi chú khác đề xuất (nếu có)
                        </h3>
                        <p
                            v-if="isP2PExtracurricular"
                            class="dw-e-panel__lede mt-1 max-w-none text-xs font-normal normal-case text-slate-600"
                        >
                            Gợi ý theo biểu mẫu; vui lòng liên hệ NV Điều vận để
                            điền thông tin chi phí.
                        </p>
                        <p
                            v-else
                            class="dw-e-panel__lede mt-1 max-w-none text-xs font-normal normal-case text-slate-600"
                        >
                            Tick nếu áp dụng; nhập chi phí ước tính (VNĐ) hoặc
                            để trống để NV Điều vận bổ sung.
                        </p>
                    </div>
                </header>
                <div class="dw-e21-rows">
                    <div class="dw-e21-row">
                        <label class="dw-e21-row__opt">
                            <input
                                v-model="form.e2_door_pickup"
                                type="checkbox"
                                class="dw-e21-row__check"
                            />
                            <span class="dw-e21-row__label"
                                >Xe đưa đón tận nhà<span
                                    v-if="isP2PExtracurricular"
                                    class="mt-0.5 block text-[11px] font-normal normal-case text-slate-500"
                                    >(
                                    <span class="italic"
                                        >Ghi rõ địa chỉ đón trả ở bảng phía
                                        trên</span
                                    >
                                    — gợi ý
                                    <strong class="font-medium"
                                        >200.000&nbsp;đ/người</strong
                                    >)</span
                                ></span
                            >
                        </label>
                        <div class="dw-e21-row__cost">
                            <span class="dw-e21-row__cost-label"
                                >Chi phí phát sinh</span
                            >
                            <input
                                :value="form.e2_door_cost"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="VD: 200.000"
                                class="dw-e21-row__input dw-cell--vnd"
                                title="Chi phí đưa đón tận nhà (ước tính)"
                                @input="vndForm('e2_door_cost', $event)"
                            />
                            <span class="dw-e21-row__unit">VNĐ</span>
                        </div>
                    </div>
                    <div class="dw-e21-row">
                        <label class="dw-e21-row__opt">
                            <input
                                v-model="form.e2_driver_self"
                                type="checkbox"
                                class="dw-e21-row__check"
                            />
                            <span class="dw-e21-row__label"
                                >Tài xế tự túc (ăn uống, khách sạn…)<span
                                    v-if="isP2PExtracurricular"
                                    class="mt-0.5 block text-[11px] font-normal normal-case text-slate-500"
                                    >(gợi ý
                                    <strong class="font-medium"
                                        >500.000&nbsp;₫</strong
                                    >)</span
                                ></span
                            >
                        </label>
                        <div class="dw-e21-row__cost">
                            <span class="dw-e21-row__cost-label"
                                >Chi phí phát sinh</span
                            >
                            <input
                                :value="form.e2_driver_self_cost"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="VD: 500.000"
                                class="dw-e21-row__input dw-cell--vnd"
                                title="Chi phí tài xế tự túc (ước tính)"
                                @input="vndForm('e2_driver_self_cost', $event)"
                            />
                            <span class="dw-e21-row__unit">VNĐ</span>
                        </div>
                    </div>
                    <div class="dw-e21-row">
                        <label class="dw-e21-row__opt">
                            <input
                                v-model="form.e2_after_21h"
                                type="checkbox"
                                class="dw-e21-row__check"
                            />
                            <span class="dw-e21-row__label"
                                >Có nhu cầu sử dụng xe sau 21h trong ngày<span
                                    v-if="isP2PExtracurricular"
                                    class="mt-0.5 block text-[11px] font-normal normal-case text-slate-500"
                                    >(gợi ý
                                    <strong class="font-medium"
                                        >1.000.000&nbsp;₫</strong
                                    >)</span
                                ></span
                            >
                        </label>
                        <div class="dw-e21-row__cost">
                            <span class="dw-e21-row__cost-label"
                                >Chi phí phát sinh</span
                            >
                            <input
                                :value="form.e2_after_21h_cost"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="VD: 1.000.000"
                                class="dw-e21-row__input dw-cell--vnd"
                                title="Phụ phí sử dụng xe sau 21:00 (ước tính)"
                                @input="vndForm('e2_after_21h_cost', $event)"
                            />
                            <span class="dw-e21-row__unit">VNĐ</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Cargo table -->
        <div v-else class="dw-step3-stack">
            <article class="dw-step3-section">
                <header class="dw-sec-intro">
                    <h3 class="dw-sec-intro__title">
                        Nội dung đề nghị vận chuyển hàng hóa
                    </h3>
                    <p class="dw-sec-intro__meta">
                        Mỗi dòng một loại hàng hoặc một lô. Điền khối lượng /
                        kích thước giúp chọn loại xe phù hợp. Cột có
                        <span class="dw-th-req">*</span>
                        cần ưu tiên để hệ thống xử lý đúng SLA.
                    </p>
                </header>
                <div class="dw-table-wrap">
                <div class="dw-table-detail">
                    <table
                        class="min-w-[1420px] w-full border-collapse text-left text-xs sm:text-sm"
                    >
                        <thead>
                            <tr
                                class="border-b border-slate-200 bg-slate-50 text-[10px] uppercase leading-tight text-slate-600 sm:text-xs"
                            >
                                <th
                                    class="min-w-[2.5rem] whitespace-normal px-1 py-2"
                                >
                                    STT
                                </th>
                                <th
                                    class="min-w-[9rem] whitespace-normal px-1 py-2"
                                >
                                    Tên HH
                                </th>
                                <th
                                    class="min-w-[3.5rem] whitespace-normal px-1 py-2"
                                >
                                    SL
                                </th>
                                <th
                                    class="min-w-[8rem] whitespace-normal px-1 py-2"
                                >
                                    Kích thước (1 kiện)
                                </th>
                                <th
                                    class="min-w-[7rem] whitespace-normal px-1 py-2"
                                >
                                    KL (1 kiện)
                                </th>
                                <th
                                    class="min-w-[9rem] whitespace-normal px-1 py-2"
                                >
                                    Ghi chú HH
                                </th>
                                <th
                                    class="min-w-[20rem] border-l border-slate-200 px-1 py-2"
                                    colspan="3"
                                >
                                    Điểm tập kết
                                </th>
                                <th
                                    class="min-w-[20rem] border-l border-slate-200 px-1 py-2"
                                    colspan="3"
                                >
                                    Điểm giao
                                </th>
                                <th
                                    class="min-w-[10rem] whitespace-normal px-1 py-2"
                                >
                                    VC / ghi chú NV
                                </th>
                                <th
                                    class="min-w-[7rem] whitespace-normal px-1 py-2"
                                >
                                    Chi phí
                                </th>
                                <th class="w-8"></th>
                            </tr>
                            <tr
                                class="border-b border-slate-200 bg-slate-50/80 text-[10px] normal-case text-slate-600"
                            >
                                <th colspan="6"></th>
                                <th
                                    class="min-w-[10rem] border-l border-slate-200 whitespace-normal px-1 py-1"
                                >
                                    Thời gian
                                    <span class="dw-th-req" title="Ưu tiên điền"
                                        >*</span
                                    >
                                </th>
                                <th
                                    class="min-w-[10rem] whitespace-normal px-1 py-1"
                                >
                                    Địa điểm
                                </th>
                                <th
                                    class="min-w-[9rem] whitespace-normal px-1 py-1"
                                >
                                    Người giao
                                </th>
                                <th
                                    class="min-w-[10rem] border-l border-slate-200 whitespace-normal px-1 py-1"
                                >
                                    Thời gian
                                    <span class="dw-th-req" title="Ưu tiên điền"
                                        >*</span
                                    >
                                </th>
                                <th
                                    class="min-w-[10rem] whitespace-normal px-1 py-1"
                                >
                                    Địa điểm
                                </th>
                                <th
                                    class="min-w-[9rem] whitespace-normal px-1 py-1"
                                >
                                    Người nhận
                                </th>
                                <th colspan="2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, idx) in cargoRows"
                                :key="idx"
                                class="border-b border-slate-100 align-top"
                            >
                                <td class="px-1 py-1 text-slate-500">
                                    {{ idx + 1 }}
                                </td>
                                <td class="min-w-[9rem] p-0.5">
                                    <input
                                        v-model="row.name"
                                        type="text"
                                        placeholder="Tên hàng, quy cách đóng gói"
                                        class="dw-cell dw-cell--table"
                                        title="Tên hàng hóa — bắt buộc trên ít nhất một dòng"
                                    />
                                </td>
                                <td class="min-w-[3.5rem] p-0.5">
                                    <input
                                        v-model="row.qty"
                                        type="text"
                                        placeholder="Số lượng / kiện"
                                        class="dw-cell dw-cell--table min-w-[3.25rem]"
                                    />
                                </td>
                                <td class="min-w-[8rem] p-0.5">
                                    <input
                                        v-model="row.dimensions"
                                        type="text"
                                        placeholder="Dài × rộng × cao (cm)"
                                        class="dw-cell dw-cell--table"
                                    />
                                </td>
                                <td class="min-w-[7rem] p-0.5">
                                    <input
                                        v-model="row.weight"
                                        type="text"
                                        placeholder="kg / kiện"
                                        class="dw-cell dw-cell--table"
                                    />
                                </td>
                                <td class="min-w-[9rem] p-0.5">
                                    <input
                                        v-model="row.item_notes"
                                        type="text"
                                        placeholder="Mô tả thêm…"
                                        class="dw-cell dw-cell--table"
                                    />
                                </td>
                                <td
                                    class="min-w-[10rem] border-l border-slate-200 p-0.5"
                                >
                                    <input
                                        v-model="row.pickup_at"
                                        type="datetime-local"
                                        class="dw-cell dw-cell--table dw-date-input"
                                        title="Thời gian lấy / tập kết"
                                    />
                                </td>
                                <td class="min-w-[10rem] p-0.5">
                                    <input
                                        v-model="row.pickup_place"
                                        type="text"
                                        placeholder="Địa chỉ, tên kho, tầng…"
                                        class="dw-cell dw-cell--table"
                                    />
                                </td>
                                <td class="min-w-[9rem] p-0.5">
                                    <input
                                        v-model="row.pickup_contact"
                                        type="text"
                                        placeholder="Họ tên người giao"
                                        class="dw-cell dw-cell--table"
                                    />
                                </td>
                                <td
                                    class="min-w-[10rem] border-l border-slate-200 p-0.5"
                                >
                                    <input
                                        v-model="row.delivery_at"
                                        type="datetime-local"
                                        class="dw-cell dw-cell--table dw-date-input"
                                        title="Thời gian giao hàng"
                                    />
                                </td>
                                <td class="min-w-[10rem] p-0.5">
                                    <input
                                        v-model="row.delivery_place"
                                        type="text"
                                        placeholder="Địa chỉ nhận, bộ phận…"
                                        class="dw-cell dw-cell--table"
                                    />
                                </td>
                                <td class="min-w-[9rem] p-0.5">
                                    <input
                                        v-model="row.delivery_contact"
                                        type="text"
                                        placeholder="Họ tên người nhận"
                                        class="dw-cell dw-cell--table"
                                    />
                                </td>
                                <td class="min-w-[10rem] p-0.5">
                                    <input
                                        v-model="row.transport_note"
                                        type="text"
                                        placeholder="Loại xe, NCC, yêu cầu đặc biệt…"
                                        class="dw-cell dw-cell--table"
                                    />
                                </td>
                                <td class="min-w-[7.5rem] p-0.5">
                                    <input
                                        :value="row.cost"
                                        type="text"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        placeholder="VD: 1.500.000"
                                        class="dw-cell dw-cell--table dw-cell--vnd"
                                        title="Chi phí — gõ số, tự thêm dấu ."
                                        @input="vndRow(row, 'cost', $event)"
                                    />
                                </td>
                                <td class="px-0.5">
                                    <button
                                        v-if="cargoRows.length > 1"
                                        type="button"
                                        class="rounded p-1 text-rose-600 hover:bg-rose-50"
                                        @click="removeCargoRow(idx)"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50">
                                <td
                                    colspan="13"
                                    class="px-3 py-2 text-right font-medium text-slate-700"
                                >
                                    Tổng
                                </td>
                                <td class="px-2 py-2 font-semibold text-va-800">
                                    {{ formatCurrency(cargoTotal) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="dw-table-toolbar">
                        <button
                            type="button"
                            class="dw-btn-row-add"
                            @click="addCargoRow"
                        >
                            <PlusIcon class="h-4 w-4" />
                            Thêm dòng hàng
                        </button>
                    </div>
                </div>
                </div>
            </article>

            <article
                class="dw-step3-section space-y-3 bg-slate-50/40 p-4 sm:p-5"
            >
                <div class="text-xs font-semibold uppercase text-slate-600">
                    e.1.1 Ghi chú &amp; phát sinh
                </div>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-600"
                        >Ghi chú khác
                        <span class="font-normal text-slate-400"
                            >(tuỳ chọn)</span
                        ></span
                    >
                    <textarea
                        v-model="form.cargo_extra_notes"
                        rows="2"
                        class="dw-input min-h-[3.5rem] resize-y"
                        placeholder="Yêu cầu đặc biệt, giờ cấm tải, hàng dễ vỡ…"
                    />
                </label>
                <div
                    class="divide-y divide-slate-200/80 rounded-lg border border-slate-200/80 bg-white/60"
                >
                    <div
                        class="flex flex-col gap-2 px-3 py-3 sm:flex-row sm:items-center sm:gap-4 sm:py-2.5"
                    >
                        <label
                            class="flex min-w-0 flex-1 cursor-pointer items-start gap-2.5 sm:items-center"
                        >
                            <input
                                v-model="form.need_porters"
                                type="checkbox"
                                class="mt-0.5 shrink-0 rounded border-slate-300 text-va-800 sm:mt-0"
                            />
                            <span class="text-sm leading-snug text-slate-800"
                                >Yêu cầu bốc xếp / nhân công hỗ trợ</span
                            >
                        </label>
                        <div
                            class="flex min-w-0 flex-wrap items-center gap-2 sm:max-w-[28rem] sm:justify-end"
                        >
                            <span
                                class="shrink-0 text-xs font-medium text-slate-600"
                                >Số lượng</span
                            >
                            <input
                                v-model="form.porter_qty"
                                type="text"
                                placeholder="VD: 2 người"
                                class="dw-cell dw-cell--e21 min-w-[6rem] max-w-[10rem]"
                            />
                            <span
                                class="shrink-0 text-xs font-medium text-slate-600"
                                >Chi phí (VNĐ)</span
                            >
                            <input
                                :value="form.porter_cost"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="VD: 500.000"
                                class="dw-cell dw-cell--e21 dw-cell--vnd min-w-[10rem] flex-1 sm:max-w-[14rem]"
                                @input="vndForm('porter_cost', $event)"
                            />
                        </div>
                    </div>
                    <div
                        class="flex flex-col gap-2 px-3 py-3 sm:flex-row sm:items-center sm:gap-4 sm:py-2.5"
                    >
                        <label
                            class="flex min-w-0 flex-1 cursor-pointer items-start gap-2.5 sm:items-center"
                        >
                            <input
                                v-model="form.interprovincial"
                                type="checkbox"
                                class="mt-0.5 shrink-0 rounded border-slate-300 text-va-800 sm:mt-0"
                            />
                            <span class="text-sm leading-snug text-slate-800"
                                >Gửi chành xe đi tỉnh</span
                            >
                        </label>
                        <div
                            class="flex min-w-0 shrink-0 items-center gap-2 sm:w-[min(100%,20rem)] sm:justify-end"
                        >
                            <span
                                class="shrink-0 text-xs font-medium text-slate-600"
                                >Chi phí phát sinh (VNĐ)</span
                            >
                            <input
                                :value="form.interprovincial_cost"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                placeholder="VD: 300.000"
                                class="dw-cell dw-cell--e21 dw-cell--vnd min-w-[10rem] flex-1 sm:max-w-[14rem]"
                                @input="vndForm('interprovincial_cost', $event)"
                            />
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </div>
</template>
<script setup>
import { computed, inject, unref } from "vue";
import { PlusIcon, TrashIcon } from "@heroicons/vue/24/outline";
import { formatVndWhileTyping } from "../../../util/money";
import { DISPATCH_WIZARD_KEY } from "./injectionKeys";

const w = inject(DISPATCH_WIZARD_KEY);
if (!w)
    throw new Error(
        "DispatchWizardStep3: missing DISPATCH_WIZARD_KEY provider",
    );

const {
    passengerRows,
    businessRows,
    cargoRows,
    form,
    passengerE1Total,
    passengerE2Total,
    cargoTotal,
    e1WeekdayOptions,
    formatCurrency,
    rowLineTotal,
    addPassengerRow,
    removePassengerRow,
    addBusinessRow,
    removeBusinessRow,
    addCargoRow,
    removeCargoRow,
    openDatePickerFromInput,
    toggleE1Weekday,
} = w;

function vndRow(row, key, e) {
    row[key] = formatVndWhileTyping(e.target.value);
}
function vndForm(key, e) {
    form.value[key] = formatVndWhileTyping(e.target.value);
}

// inject trả về object thường: ref/computed lồng (w.x) không unwrap trong template → v-if / v-for sai.
const isCargo = computed(() => unref(w.isCargo));
const isPointToPointTrip = computed(() => unref(w.isPointToPointTrip));
const isBusinessTrip = computed(() => form.value.trip_type === "business");
/** P2P + mục đích tab 2: hoạt động ngoại khóa → hiện e.1.1 & e.2.1 */
const isP2PExtracurricular = computed(
    () =>
        isPointToPointTrip.value &&
        form.value.point_purpose_kind === "extracurricular",
);
const showPassengerTripExtras = computed(
    () => !isPointToPointTrip.value || isP2PExtracurricular.value,
);
</script>
