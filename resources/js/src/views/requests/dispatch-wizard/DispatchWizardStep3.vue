<template>
    <div class="dw-step3-root">
        <div class="dw-step3-hero">
            <h2 class="text-lg font-semibold text-slate-900">
                {{ t('dispatch_wizard.s3.title') }}
            </h2>
            <div class="dw-step3-guide mt-3" role="note">
                <p class="dw-step3-guide__title">{{ t('dispatch_wizard.s3.guide_title') }}</p>
                <div class="dw-step3-guide__body">
                    <p v-if="!isCargo">
                        {{ t('dispatch_wizard.s3.guide_pass') }}
                    </p>
                    <p v-else>
                        {{ t('dispatch_wizard.s3.guide_cargo') }}
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
                        {{ t('dispatch_wizard.s3.e1_title') }}
                    </h3>
                    <p class="dw-sec-intro__meta">
                        {{ t('dispatch_wizard.s3.e1_meta') }}
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
                                        {{ t('dispatch_wizard.s3.col_no') }}
                                    </th>
                                    <th
                                        class="min-w-[13rem] px-1 py-2 text-center"
                                        colspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.trip_out') }}
                                    </th>
                                    <th
                                        class="min-w-[13rem] px-1 py-2 text-center"
                                        colspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.trip_back') }}
                                    </th>
                                    <th
                                        class="min-w-[5rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.guests') }}
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.owner') }}
                                    </th>
                                    <th
                                        class="min-w-[7rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.unit_price') }}
                                    </th>
                                    <th
                                        class="min-w-[7rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.extra_fee') }}
                                    </th>
                                    <th
                                        class="min-w-[6rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.row_total') }}
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.notes') }}
                                    </th>
                                    <th class="w-8"></th>
                                </tr>
                                <tr
                                    class="border-b border-slate-200 bg-slate-50/90 text-[10px] normal-case text-slate-600"
                                >
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        {{ t('dispatch_wizard.s3.time') }}
                                        <span
                                            class="dw-th-req"
                                            :title="t('dispatch_wizard.s3.priority_col')"
                                            >*</span
                                        >
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        {{ t('dispatch_wizard.s3.place') }}
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        {{ t('dispatch_wizard.s3.time') }}
                                        <span
                                            class="dw-th-req"
                                            :title="t('dispatch_wizard.s3.priority_col')"
                                            >*</span
                                        >
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        {{ t('dispatch_wizard.s3.place') }}
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
                                            :title="t('dispatch_wizard.s3.depart_pickup_title')"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.pickup"
                                            type="text"
                                            :placeholder="t('dispatch_wizard.s3.pickup_ph')"
                                            class="dw-cell dw-cell--table"
                                            :title="t('dispatch_wizard.s3.pickup_title')"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.return_at"
                                            type="datetime-local"
                                            class="dw-cell dw-cell--table dw-date-input"
                                            :title="t('dispatch_wizard.s3.return_title')"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.dropoff"
                                            type="text"
                                            :placeholder="t('dispatch_wizard.s3.dropoff_ph')"
                                            class="dw-cell dw-cell--table"
                                            :title="t('dispatch_wizard.s3.dropoff_title')"
                                        />
                                    </td>
                                    <td class="min-w-[5rem] p-0.5">
                                        <input
                                            v-model="row.guests"
                                            type="number"
                                            min="1"
                                            :placeholder="t('dispatch_wizard.s3.guests_ph')"
                                            class="dw-cell dw-cell--table min-w-[4.5rem]"
                                            :title="t('dispatch_wizard.s3.guests_title')"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.person_in_charge"
                                            type="text"
                                            :placeholder="t('dispatch_wizard.s3.pic_ph')"
                                            class="dw-cell dw-cell--table"
                                        />
                                    </td>
                                    <td class="min-w-[7.5rem] p-0.5">
                                        <input
                                            :value="row.unit_price"
                                            type="text"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            :placeholder="t('dispatch_wizard.s3.vnd_ph')"
                                            class="dw-cell dw-cell--table dw-cell--vnd"
                                            :title="t('dispatch_wizard.s3.unit_price_title')"
                                            @input="vndRow(row, 'unit_price', $event)"
                                        />
                                    </td>
                                    <td class="min-w-[7.5rem] p-0.5">
                                        <input
                                            :value="row.extra_fee"
                                            type="text"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            :placeholder="t('dispatch_wizard.s3.vnd_ph_small')"
                                            class="dw-cell dw-cell--table dw-cell--vnd"
                                            :title="t('dispatch_wizard.s3.extra_fee_title')"
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
                                            :placeholder="t('dispatch_wizard.s3.row_notes_ph')"
                                            class="dw-cell dw-cell--table"
                                        />
                                    </td>
                                    <td class="px-0.5">
                                        <button
                                            v-if="passengerRows.length > 1"
                                            type="button"
                                            class="rounded p-1 text-rose-600 hover:bg-rose-50"
                                            :title="t('dispatch_wizard.s3.delete_row')"
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
                                        {{ t('dispatch_wizard.s3.sum_est') }}
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
                        {{ t('dispatch_wizard.s3.add_row') }}
                    </button>
                    <label class="dw-table-toolbar__extra">
                        <input
                            v-model="form.multi_day"
                            type="checkbox"
                            class="dw-table-toolbar__extra-check"
                        />
                        <span :title="t('dispatch_wizard.s3.multi_day_title')"
                            >{{ t('dispatch_wizard.s3.multi_day') }}</span
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
                            {{ t('dispatch_wizard.s3.e11_notes_title') }}
                        </h3>
                        <p
                            v-if="isP2PExtracurricular"
                            class="dw-e-panel__lede mt-1 max-w-none text-xs font-normal normal-case text-slate-600"
                        >
                            {{ t('dispatch_wizard.s3.e11_extralead', { amount: formatCurrency(5000000) }) }}
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
                            >{{ t('dispatch_wizard.s3.e11_flag') }}</span
                        >
                    </label>
                </div>

                <div class="dw-e11-fields">
                    <div class="dw-e11-field">
                        <span class="dw-e11-field__label"
                            >{{ t('dispatch_wizard.s3.from_date') }}
                            <span class="font-normal text-slate-400"
                                >{{ t('dispatch_wizard.s3.optional') }}</span
                            ></span
                        >
                        <input
                            v-model="form.e1_from_date"
                            type="date"
                            lang="vi"
                            class="dw-input dw-input--e11 dw-date-input"
                            :title="t('dispatch_wizard.s3.e1_period_from_title')"
                            @click="openDatePickerFromInput($event)"
                        />
                        <span class="dw-e11-field__hint"
                            >{{ t('dispatch_wizard.s3.date_hint') }}</span
                        >
                    </div>
                    <div class="dw-e11-field">
                        <span class="dw-e11-field__label"
                            >{{ t('dispatch_wizard.s3.to_date') }}
                            <span class="font-normal text-slate-400"
                                >{{ t('dispatch_wizard.s3.optional') }}</span
                            ></span
                        >
                        <input
                            v-model="form.e1_to_date"
                            type="date"
                            lang="vi"
                            class="dw-input dw-input--e11 dw-date-input"
                            :title="t('dispatch_wizard.s3.e1_period_to_title')"
                            @click="openDatePickerFromInput($event)"
                        />
                        <span class="dw-e11-field__hint"
                            >{{ t('dispatch_wizard.s3.to_after_from', { from: t('dispatch_wizard.s3.from_date') }) }}</span
                        >
                    </div>
                    <div class="dw-e11-field">
                        <span class="dw-e11-field__label"
                            >{{ t('dispatch_wizard.s3.days_total') }}</span
                        >
                        <input
                            v-model="form.e1_days_total"
                            type="text"
                            inputmode="numeric"
                            class="dw-input dw-input--e11"
                            :placeholder="t('dispatch_wizard.s3.days_ph')"
                            :title="t('dispatch_wizard.s3.days_title')"
                        />
                        <span class="dw-e11-field__hint"
                            >{{ t('dispatch_wizard.s3.days_hint') }}</span
                        >
                    </div>
                    <div class="dw-e11-field">
                        <span class="dw-e11-field__label"
                            >{{ t('dispatch_wizard.s3.extra_cost') }}</span
                        >
                        <input
                            :value="form.e1_extra_cost"
                            type="text"
                            inputmode="numeric"
                            autocomplete="off"
                            class="dw-input dw-input--e11 dw-cell--vnd"
                            :placeholder="t('dispatch_wizard.s3.extra_cost_ph')"
                            :title="t('dispatch_wizard.s3.extra_cost_title')"
                            @input="vndForm('e1_extra_cost', $event)"
                        />
                        <span class="dw-e11-field__hint"
                            >{{ t('dispatch_wizard.s3.vat_hint') }}</span
                        >
                    </div>
                </div>

                <div class="dw-e11-weekwrap">
                    <p
                        id="dw-e11-weekdays-label"
                        class="dw-e11-weekwrap__title"
                    >
                        {{ t('dispatch_wizard.s3.weekdays_title') }}
                        <span class="font-normal text-slate-500"
                            >{{ t('dispatch_wizard.s3.weekdays_sub') }}</span
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
                        {{ t('dispatch_wizard.s3.e2_title') }}
                    </h3>
                    <p class="dw-sec-intro__meta">
                        {{ t('dispatch_wizard.s3.e2_meta') }}
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
                                        {{ t('dispatch_wizard.s3.col_no') }}
                                    </th>
                                    <th
                                        class="min-w-[13rem] px-1 py-2 text-center"
                                        colspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.trip_out') }}
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.waypoint_col') }}
                                    </th>
                                    <th
                                        class="min-w-[13rem] px-1 py-2 text-center"
                                        colspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.trip_back') }}
                                    </th>
                                    <th
                                        class="min-w-[5rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.guests') }}
                                    </th>
                                    <th
                                        class="min-w-[7rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.unit_price') }}
                                    </th>
                                    <th
                                        class="min-w-[7rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.extra_fee') }}
                                    </th>
                                    <th
                                        class="min-w-[6rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.row_total') }}
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-2"
                                        rowspan="2"
                                    >
                                        {{ t('dispatch_wizard.s3.notes') }}
                                    </th>
                                    <th class="w-8"></th>
                                </tr>
                                <tr
                                    class="border-b border-slate-200 bg-slate-50/90 text-[10px] normal-case text-slate-600"
                                >
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        {{ t('dispatch_wizard.s3.time') }}
                                        <span
                                            class="dw-th-req"
                                            :title="t('dispatch_wizard.s3.priority_col')"
                                            >*</span
                                        >
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        {{ t('dispatch_wizard.s3.place') }}
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        {{ t('dispatch_wizard.s3.time') }}
                                        <span
                                            class="dw-th-req"
                                            :title="t('dispatch_wizard.s3.priority_col')"
                                            >*</span
                                        >
                                    </th>
                                    <th
                                        class="min-w-[10rem] whitespace-normal px-1 py-1"
                                    >
                                        {{ t('dispatch_wizard.s3.place') }}
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
                                            :title="t('dispatch_wizard.s3.biz_depart_title')"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.pickup"
                                            type="text"
                                            :placeholder="t('dispatch_wizard.s3.biz_pickup_ph')"
                                            class="dw-cell dw-cell--table"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.waypoint"
                                            type="text"
                                            :placeholder="t('dispatch_wizard.s3.waypoint_ph')"
                                            class="dw-cell dw-cell--table"
                                            :title="t('dispatch_wizard.s3.waypoint_title')"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.return_at"
                                            type="datetime-local"
                                            class="dw-cell dw-cell--table dw-date-input"
                                            :title="t('dispatch_wizard.s3.biz_return_title')"
                                        />
                                    </td>
                                    <td class="min-w-[10rem] p-0.5">
                                        <input
                                            v-model="row.dropoff"
                                            type="text"
                                            :placeholder="t('dispatch_wizard.s3.biz_drop_ph')"
                                            class="dw-cell dw-cell--table"
                                        />
                                    </td>
                                    <td class="min-w-[5rem] p-0.5">
                                        <input
                                            v-model="row.guests"
                                            type="number"
                                            min="1"
                                            :placeholder="t('dispatch_wizard.s3.guests_ph')"
                                            class="dw-cell dw-cell--table min-w-[4.5rem]"
                                            :title="t('dispatch_wizard.s3.biz_guests_title')"
                                        />
                                    </td>
                                    <td class="min-w-[7.5rem] p-0.5">
                                        <input
                                            :value="row.unit_price"
                                            type="text"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            :placeholder="t('dispatch_wizard.s3.vnd_ph')"
                                            class="dw-cell dw-cell--table dw-cell--vnd"
                                            :title="t('dispatch_wizard.s3.biz_unit_price_title')"
                                            @input="vndRow(row, 'unit_price', $event)"
                                        />
                                    </td>
                                    <td class="min-w-[7.5rem] p-0.5">
                                        <input
                                            :value="row.extra_fee"
                                            type="text"
                                            inputmode="numeric"
                                            autocomplete="off"
                                            :placeholder="t('dispatch_wizard.s3.vnd_ph_small')"
                                            class="dw-cell dw-cell--table dw-cell--vnd"
                                            :title="t('dispatch_wizard.s3.biz_extra_fee_title')"
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
                                            :placeholder="t('dispatch_wizard.s3.row_notes_biz_ph')"
                                            class="dw-cell dw-cell--table"
                                        />
                                    </td>
                                    <td class="px-0.5">
                                        <button
                                            v-if="businessRows.length > 1"
                                            type="button"
                                            class="rounded p-1 text-rose-600 hover:bg-rose-50"
                                            :title="t('dispatch_wizard.s3.delete_row')"
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
                                        {{ t('dispatch_wizard.s3.sum_e2') }}
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
                        {{ t('dispatch_wizard.s3.add_row') }}
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
                            {{ t('dispatch_wizard.s3.e21_title') }}
                        </h3>
                        <p
                            v-if="isP2PExtracurricular"
                            class="dw-e-panel__lede mt-1 max-w-none text-xs font-normal normal-case text-slate-600"
                        >
                            {{ t('dispatch_wizard.s3.e21_lead_extra') }}
                        </p>
                        <p
                            v-else
                            class="dw-e-panel__lede mt-1 max-w-none text-xs font-normal normal-case text-slate-600"
                        >
                            {{ t('dispatch_wizard.s3.e21_lead_default') }}
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
                                >{{ t('dispatch_wizard.s3.door_pickup') }}<span
                                    v-if="isP2PExtracurricular"
                                    class="mt-0.5 block text-[11px] font-normal normal-case text-slate-500"
                                    >{{ t('dispatch_wizard.s3.door_pickup_extra', { amount: formatCurrency(200000) }) }}</span
                                ></span
                            >
                        </label>
                        <div class="dw-e21-row__cost">
                            <span class="dw-e21-row__cost-label"
                                >{{ t('dispatch_wizard.s3.door_cost_lbl') }}</span
                            >
                            <input
                                :value="form.e2_door_cost"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                :placeholder="t('dispatch_wizard.s3.vnd_ph_small')"
                                class="dw-e21-row__input dw-cell--vnd"
                                :title="t('dispatch_wizard.s3.door_cost_title')"
                                @input="vndForm('e2_door_cost', $event)"
                            />
                            <span class="dw-e21-row__unit">{{ t('dispatch_wizard.s3.vnd') }}</span>
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
                                >{{ t('dispatch_wizard.s3.driver_self') }}<span
                                    v-if="isP2PExtracurricular"
                                    class="mt-0.5 block text-[11px] font-normal normal-case text-slate-500"
                                    >{{ t('dispatch_wizard.s3.driver_self_extra', { amount: formatCurrency(500000) }) }}</span
                                ></span
                            >
                        </label>
                        <div class="dw-e21-row__cost">
                            <span class="dw-e21-row__cost-label"
                                >{{ t('dispatch_wizard.s3.door_cost_lbl') }}</span
                            >
                            <input
                                :value="form.e2_driver_self_cost"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                :placeholder="t('dispatch_wizard.s3.vnd_ph')"
                                class="dw-e21-row__input dw-cell--vnd"
                                :title="t('dispatch_wizard.s3.driver_cost_title')"
                                @input="vndForm('e2_driver_self_cost', $event)"
                            />
                            <span class="dw-e21-row__unit">{{ t('dispatch_wizard.s3.vnd') }}</span>
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
                                >{{ t('dispatch_wizard.s3.after21') }}<span
                                    v-if="isP2PExtracurricular"
                                    class="mt-0.5 block text-[11px] font-normal normal-case text-slate-500"
                                    >{{ t('dispatch_wizard.s3.after21_extra', { amount: formatCurrency(1000000) }) }}</span
                                ></span
                            >
                        </label>
                        <div class="dw-e21-row__cost">
                            <span class="dw-e21-row__cost-label"
                                >{{ t('dispatch_wizard.s3.door_cost_lbl') }}</span
                            >
                            <input
                                :value="form.e2_after_21h_cost"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                :placeholder="t('dispatch_wizard.s3.vnd_ph_large')"
                                class="dw-e21-row__input dw-cell--vnd"
                                :title="t('dispatch_wizard.s3.after21_title')"
                                @input="vndForm('e2_after_21h_cost', $event)"
                            />
                            <span class="dw-e21-row__unit">{{ t('dispatch_wizard.s3.vnd') }}</span>
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
                        {{ t('dispatch_wizard.s3.cargo_title') }}
                    </h3>
                    <p class="dw-sec-intro__meta">
                        {{ t('dispatch_wizard.s3.cargo_meta') }}
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
                                    {{ t('dispatch_wizard.s3.col_no') }}
                                </th>
                                <th
                                    class="min-w-[9rem] whitespace-normal px-1 py-2"
                                >
                                    {{ t('dispatch_wizard.s3.cargo_name') }}
                                </th>
                                <th
                                    class="min-w-[3.5rem] whitespace-normal px-1 py-2"
                                >
                                    {{ t('dispatch_wizard.s3.qty') }}
                                </th>
                                <th
                                    class="min-w-[8rem] whitespace-normal px-1 py-2"
                                >
                                    {{ t('dispatch_wizard.s3.dim') }}
                                </th>
                                <th
                                    class="min-w-[7rem] whitespace-normal px-1 py-2"
                                >
                                    {{ t('dispatch_wizard.s3.weight') }}
                                </th>
                                <th
                                    class="min-w-[9rem] whitespace-normal px-1 py-2"
                                >
                                    {{ t('dispatch_wizard.s3.item_notes') }}
                                </th>
                                <th
                                    class="min-w-[20rem] border-l border-slate-200 px-1 py-2"
                                    colspan="3"
                                >
                                    {{ t('dispatch_wizard.s3.pickup_group') }}
                                </th>
                                <th
                                    class="min-w-[20rem] border-l border-slate-200 px-1 py-2"
                                    colspan="3"
                                >
                                    {{ t('dispatch_wizard.s3.delivery_group') }}
                                </th>
                                <th
                                    class="min-w-[10rem] whitespace-normal px-1 py-2"
                                >
                                    {{ t('dispatch_wizard.s3.transport_note') }}
                                </th>
                                <th
                                    class="min-w-[7rem] whitespace-normal px-1 py-2"
                                >
                                    {{ t('dispatch_wizard.s3.cost') }}
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
                                    {{ t('dispatch_wizard.s3.time') }}
                                    <span class="dw-th-req" :title="t('dispatch_wizard.s3.priority_col')"
                                        >*</span
                                    >
                                </th>
                                <th
                                    class="min-w-[10rem] whitespace-normal px-1 py-1"
                                >
                                    {{ t('dispatch_wizard.s3.place') }}
                                </th>
                                <th
                                    class="min-w-[9rem] whitespace-normal px-1 py-1"
                                >
                                    {{ t('dispatch_wizard.s3.shipper_col') }}
                                </th>
                                <th
                                    class="min-w-[10rem] border-l border-slate-200 whitespace-normal px-1 py-1"
                                >
                                    {{ t('dispatch_wizard.s3.time') }}
                                    <span class="dw-th-req" :title="t('dispatch_wizard.s3.priority_col')"
                                        >*</span
                                    >
                                </th>
                                <th
                                    class="min-w-[10rem] whitespace-normal px-1 py-1"
                                >
                                    {{ t('dispatch_wizard.s3.place') }}
                                </th>
                                <th
                                    class="min-w-[9rem] whitespace-normal px-1 py-1"
                                >
                                    {{ t('dispatch_wizard.s3.receiver_col') }}
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
                                        :placeholder="t('dispatch_wizard.s3.cargo_name_ph')"
                                        class="dw-cell dw-cell--table"
                                        :title="t('dispatch_wizard.s3.cargo_name_title')"
                                    />
                                </td>
                                <td class="min-w-[3.5rem] p-0.5">
                                    <input
                                        v-model="row.qty"
                                        type="text"
                                        :placeholder="t('dispatch_wizard.s3.qty_ph')"
                                        class="dw-cell dw-cell--table min-w-[3.25rem]"
                                    />
                                </td>
                                <td class="min-w-[8rem] p-0.5">
                                    <input
                                        v-model="row.dimensions"
                                        type="text"
                                        :placeholder="t('dispatch_wizard.s3.dim_ph')"
                                        class="dw-cell dw-cell--table"
                                    />
                                </td>
                                <td class="min-w-[7rem] p-0.5">
                                    <input
                                        v-model="row.weight"
                                        type="text"
                                        :placeholder="t('dispatch_wizard.s3.weight_ph')"
                                        class="dw-cell dw-cell--table"
                                    />
                                </td>
                                <td class="min-w-[9rem] p-0.5">
                                    <input
                                        v-model="row.item_notes"
                                        type="text"
                                        :placeholder="t('dispatch_wizard.s3.item_notes_ph')"
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
                                        :title="t('dispatch_wizard.s3.pickup_at_title')"
                                    />
                                </td>
                                <td class="min-w-[10rem] p-0.5">
                                    <input
                                        v-model="row.pickup_place"
                                        type="text"
                                        :placeholder="t('dispatch_wizard.s3.pickup_place_ph')"
                                        class="dw-cell dw-cell--table"
                                    />
                                </td>
                                <td class="min-w-[9rem] p-0.5">
                                    <input
                                        v-model="row.pickup_contact"
                                        type="text"
                                        :placeholder="t('dispatch_wizard.s3.pickup_contact_ph')"
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
                                        :title="t('dispatch_wizard.s3.delivery_at_title')"
                                    />
                                </td>
                                <td class="min-w-[10rem] p-0.5">
                                    <input
                                        v-model="row.delivery_place"
                                        type="text"
                                        :placeholder="t('dispatch_wizard.s3.delivery_place_ph')"
                                        class="dw-cell dw-cell--table"
                                    />
                                </td>
                                <td class="min-w-[9rem] p-0.5">
                                    <input
                                        v-model="row.delivery_contact"
                                        type="text"
                                        :placeholder="t('dispatch_wizard.s3.delivery_contact_ph')"
                                        class="dw-cell dw-cell--table"
                                    />
                                </td>
                                <td class="min-w-[10rem] p-0.5">
                                    <input
                                        v-model="row.transport_note"
                                        type="text"
                                        :placeholder="t('dispatch_wizard.s3.trans_ph')"
                                        class="dw-cell dw-cell--table"
                                    />
                                </td>
                                <td class="min-w-[7.5rem] p-0.5">
                                    <input
                                        :value="row.cost"
                                        type="text"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        :placeholder="t('dispatch_wizard.s3.vnd_ph')"
                                        class="dw-cell dw-cell--table dw-cell--vnd"
                                        :title="t('dispatch_wizard.s3.cost_title')"
                                        @input="vndRow(row, 'cost', $event)"
                                    />
                                </td>
                                <td class="px-0.5">
                                    <button
                                        v-if="cargoRows.length > 1"
                                        type="button"
                                        class="rounded p-1 text-rose-600 hover:bg-rose-50"
                                        :title="t('dispatch_wizard.s3.delete_row')"
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
                                    {{ t('dispatch_wizard.s3.cargo_sum') }}
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
                            {{ t('dispatch_wizard.s3.add_cargo_row') }}
                        </button>
                    </div>
                </div>
                </div>
            </article>

            <article
                class="dw-step3-section space-y-3 bg-slate-50/40 p-4 sm:p-5"
            >
                <div class="text-xs font-semibold uppercase text-slate-600">
                    {{ t('dispatch_wizard.s3.cargo_panel') }}
                </div>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-600"
                        >{{ t('dispatch_wizard.s3.cargo_notes_lbl') }}
                        <span class="font-normal text-slate-400"
                            >{{ t('dispatch_wizard.s3.optional') }}</span
                        ></span
                    >
                    <textarea
                        v-model="form.cargo_extra_notes"
                        rows="2"
                        class="dw-input min-h-[3.5rem] resize-y"
                        :placeholder="t('dispatch_wizard.s3.cargo_notes_ph')"
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
                                >{{ t('dispatch_wizard.s3.need_porters') }}</span
                            >
                        </label>
                        <div
                            class="flex min-w-0 flex-wrap items-center gap-2 sm:max-w-[28rem] sm:justify-end"
                        >
                            <span
                                class="shrink-0 text-xs font-medium text-slate-600"
                                >{{ t('dispatch_wizard.s3.qty_lbl') }}</span
                            >
                            <input
                                v-model="form.porter_qty"
                                type="text"
                                :placeholder="t('dispatch_wizard.s3.porter_qty_ph')"
                                class="dw-cell dw-cell--e21 min-w-[6rem] max-w-[10rem]"
                            />
                            <span
                                class="shrink-0 text-xs font-medium text-slate-600"
                                >{{ t('dispatch_wizard.s3.cost_vnd') }}</span
                            >
                            <input
                                :value="form.porter_cost"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                :placeholder="t('dispatch_wizard.s3.vnd_ph')"
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
                                >{{ t('dispatch_wizard.s3.interprov') }}</span
                            >
                        </label>
                        <div
                            class="flex min-w-0 shrink-0 items-center gap-2 sm:w-[min(100%,20rem)] sm:justify-end"
                        >
                            <span
                                class="shrink-0 text-xs font-medium text-slate-600"
                                >{{ t('dispatch_wizard.s3.interprov_cost') }}</span
                            >
                            <input
                                :value="form.interprovincial_cost"
                                type="text"
                                inputmode="numeric"
                                autocomplete="off"
                                :placeholder="t('dispatch_wizard.s3.vnd_ph_small')"
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
import { useI18n } from "vue-i18n";
import { PlusIcon, TrashIcon } from "@heroicons/vue/24/outline";
import { formatVndWhileTyping } from "../../../util/money";
import { DISPATCH_WIZARD_KEY } from "./injectionKeys";

const { t } = useI18n();
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
