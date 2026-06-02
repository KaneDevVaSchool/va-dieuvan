<template>
    <section
        class="w-full min-w-0 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-700/70 dark:bg-slate-950/30"
        :aria-label="t('trip_detail.passengers.title', { n: rows.length })"
    >
        <div
            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex flex-wrap items-center gap-2">
                <h2
                    class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                >
                    {{ t("trip_detail.passengers.title", { n: rows.length }) }}
                </h2>
                <span
                    v-if="canCheckIn && rows.length"
                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200"
                >
                    {{
                        t("trip_detail.passengers.checkin_header_badge", {
                            checked: checkedCount,
                            total: rows.length,
                        })
                    }}
                </span>
            </div>
        </div>

        <div
            v-if="canCheckIn && rows.length >= 2"
            class="mt-3 flex flex-wrap gap-1.5"
            role="group"
            :aria-label="t('trip_detail.passengers.filter_segment_aria')"
        >
            <button
                v-for="opt in filterOptions"
                :key="opt.key"
                type="button"
                class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-slate-900"
                :class="filterChipClass(opt.key)"
                :aria-pressed="statusFilter === opt.key"
                :aria-label="opt.ariaLabel"
                @click="statusFilter = opt.key"
            >
                {{ opt.label }}
                <span
                    v-if="opt.badge != null"
                    class="min-w-[1.25rem] rounded-md bg-white/80 px-1 text-center text-[10px] font-bold tabular-nums text-slate-800 dark:bg-slate-900/60 dark:text-slate-100"
                >
                    {{ opt.badge }}
                </span>
            </button>
        </div>

        <!-- DataTable toolbar -->
        <div
            v-if="rows.length || canEditList"
            class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
        >
            <div v-if="rows.length" class="relative w-full sm:w-64">
                <MagnifyingGlassIcon
                    class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    aria-hidden="true"
                />
                <input
                    v-model="passengerSearch"
                    type="text"
                    autocomplete="off"
                    class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-8 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500"
                    :placeholder="t('trip_detail.passengers.dt_search_ph')"
                    :aria-label="t('trip_detail.passengers.search_aria')"
                />
                <button
                    v-if="passengerSearch.trim()"
                    type="button"
                    class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                    :aria-label="t('trip_detail.passengers.search_clear_aria')"
                    @click="passengerSearch = ''"
                >
                    <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
            <div v-else class="min-h-0 flex-1" />
            <div class="flex flex-wrap items-center gap-2">
                <select
                    v-if="rows.length"
                    v-model.number="pageSize"
                    :aria-label="t('trip_detail.passengers.dt_page_size_aria')"
                    class="rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-sm text-slate-900 shadow-sm outline-none focus:ring-2 focus:ring-sky-500/30 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                >
                    <option :value="5">5</option>
                    <option :value="10">10</option>
                    <option :value="15">15</option>
                    <option :value="20">20</option>
                </select>
                <button
                    v-if="canEditList && selectedKeys.length > 0"
                    type="button"
                    class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-rose-700 disabled:opacity-50"
                    :disabled="listBusy"
                    @click="onBulkDelete"
                >
                    {{ t("trip_detail.passengers.dt_bulk_delete") }}
                </button>
                <button
                    v-if="canEditList"
                    type="button"
                    class="rounded-lg border border-sky-200/80 bg-sky-50/80 px-3 py-1.5 text-xs font-semibold text-sky-800 transition hover:bg-sky-100 disabled:opacity-50 dark:border-sky-800/50 dark:bg-sky-950/40 dark:text-sky-200 dark:hover:bg-sky-950/70"
                    :disabled="listBusy"
                    @click="openAddModal"
                >
                    {{ t("trip_detail.passengers.dt_add_row") }}
                </button>
                <button
                    v-if="rows.length"
                    type="button"
                    class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                    :aria-label="t('trip_detail.passengers.export_csv_aria')"
                    @click="exportCsv"
                >
                    <ArrowDownTrayIcon class="h-4 w-4 shrink-0" />
                    {{ t("trip_detail.passengers.export_csv") }}
                </button>
            </div>
        </div>

        <div
            class="mt-4 overflow-x-auto rounded-xl ring-1 ring-slate-200/60 dark:ring-slate-700/60"
            :class="
                passengerTableScroll
                    ? 'max-h-[min(28rem,72vh)] overflow-y-auto overscroll-contain'
                    : ''
            "
        >
            <p
                v-if="rows.length && !filteredRows.length"
                class="px-3 py-10 text-center text-sm text-slate-500 dark:text-slate-400"
            >
                {{ t("trip_detail.passengers.search_empty") }}
            </p>
            <table
                v-else-if="rows.length"
                class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-700"
            >
                <thead
                    class="sticky top-0 z-10 bg-slate-50/95 shadow-sm backdrop-blur-sm dark:bg-slate-800/95 dark:shadow-slate-900/80"
                >
                    <tr>
                        <th
                            v-if="canEditList"
                            scope="col"
                            class="w-10 px-3 py-2.5 text-center text-xs font-semibold text-slate-600 dark:text-slate-300"
                        >
                            <div class="flex justify-center">
                            <input
                                ref="headerSelectRef"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                :aria-label="t('trip_detail.passengers.dt_col_select')"
                                @change="onToggleAll"
                            />
                            </div>
                        </th>
                        <th
                            v-if="canCheckIn"
                            scope="col"
                            class="w-10 px-2 py-2.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300"
                        />
                        <th
                            scope="col"
                            class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300"
                        >
                            {{ t("trip_detail.passengers.col_name") }}
                        </th>
                        <th
                            scope="col"
                            class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300"
                        >
                            {{ t("trip_detail.passengers.col_role") }}
                        </th>
                        <th
                            scope="col"
                            class="min-w-[9rem] max-w-[220px] px-3 py-2.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300"
                        >
                            {{ t("trip_detail.passengers.col_contact") }}
                        </th>
                        <th
                            scope="col"
                            class="min-w-0 px-3 py-2.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300"
                        >
                            {{ t("trip_detail.passengers.col_notes") }}
                        </th>
                        <th
                            v-if="canEditList"
                            scope="col"
                            class="w-24 px-3 py-2.5 text-right text-xs font-semibold text-slate-600 dark:text-slate-300"
                        >
                            {{ t("trip_detail.passengers.dt_col_actions") }}
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-slate-100 bg-white dark:divide-slate-700 dark:bg-slate-950/40"
                >
                    <template
                        v-for="row in paginatedRows"
                        :key="row.passengerKey"
                    >
                        <tr
                            class="transition hover:bg-slate-50 dark:hover:bg-slate-800/40"
                            :class="[
                                rowExpanded === row.passengerKey
                                    ? 'bg-slate-50/80 dark:bg-slate-800/50'
                                    : '',
                                checkedLocal[row.passengerKey]
                                    ? 'bg-emerald-50/90 dark:bg-emerald-950/25'
                                    : '',
                                editingKey === row.passengerKey
                                    ? 'bg-sky-50/50 dark:bg-sky-950/20'
                                    : '',
                            ]"
                            @click.self="onRowBackgroundClick(row)"
                        >
                            <td
                                v-if="canEditList"
                                class="w-10 px-3 py-2.5 text-center align-middle"
                                @click.stop
                            >
                                <div class="flex justify-center">
                                <input
                                    v-if="isRowSelectable(row)"
                                    v-model="selectedKeys"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                                    :value="row.passengerKey"
                                    :disabled="listBusy"
                                />
                                </div>
                            </td>
                            <td
                                v-if="canCheckIn"
                                class="px-2 py-2.5 align-middle"
                                @click.stop
                            >
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                    :checked="!!checkedLocal[row.passengerKey]"
                                    :disabled="checkingKey === row.passengerKey"
                                    @change="onToggleCheck(row)"
                                />
                            </td>
                            <td
                                class="px-3 py-2.5 align-top"
                                @click="onRowContentClick(row)"
                            >
                                <template
                                    v-if="editingKey === row.passengerKey && editDraft"
                                >
                                    <template v-if="row.editMeta?.kind === 'cargo'">
                                        <input
                                            v-model="editDraft.name"
                                            type="text"
                                            class="w-full min-w-[8rem] rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                            :placeholder="
                                                t(
                                                    'trip_detail.passengers.ph_cargo_name',
                                                )
                                            "
                                        />
                                    </template>
                                    <template
                                        v-else-if="
                                            row.editMeta?.kind === 'business'
                                        "
                                    >
                                        <div class="text-sm text-slate-600 dark:text-slate-400">
                                            {{ row.name }}
                                        </div>
                                    </template>
                                    <template v-else>
                                        <input
                                            v-model="editDraft.person_in_charge"
                                            type="text"
                                            class="w-full min-w-[8rem] rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-500/25 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                            :placeholder="
                                                t('trip_detail.passengers.ph_name')
                                            "
                                        />
                                    </template>
                                </template>
                                <template v-else>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-200 text-[11px] font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200"
                                        >
                                            {{ initials(row.name) }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1">
                                                <div
                                                    class="font-medium text-slate-900 dark:text-slate-100"
                                                >
                                                    {{ row.name }}
                                                </div>
                                                <button
                                                    v-if="
                                                        row.pickupAddress?.trim() ||
                                                        row.notes?.trim()
                                                    "
                                                    type="button"
                                                    class="shrink-0 rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                                                    :aria-expanded="
                                                        rowExpanded ===
                                                        row.passengerKey
                                                    "
                                                    :aria-label="
                                                        t(
                                                            'trip_detail.passengers.checkin_expand_pickup',
                                                        )
                                                    "
                                                    @click.stop="
                                                        toggleExpand(
                                                            row.passengerKey,
                                                        )
                                                    "
                                                >
                                                    <ChevronDownIcon
                                                        class="h-4 w-4 transition-transform"
                                                        :class="
                                                            rowExpanded ===
                                                            row.passengerKey
                                                                ? 'rotate-180'
                                                                : ''
                                                        "
                                                        aria-hidden="true"
                                                    />
                                                </button>
                                            </div>
                                            <div
                                                v-if="
                                                    checkedLocal[row.passengerKey]
                                                "
                                                class="mt-0.5 text-[10px] text-emerald-700"
                                            >
                                                {{
                                                    checkTimeLabel(
                                                        row.passengerKey,
                                                    )
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </td>
                            <td
                                class="px-3 py-2.5 align-top"
                                @click="onRowContentClick(row)"
                            >
                                <template
                                    v-if="editingKey === row.passengerKey && editDraft"
                                >
                                    <template
                                        v-if="row.editMeta?.kind === 'business'"
                                    >
                                        <input
                                            v-model="editDraft.guests"
                                            type="number"
                                            min="1"
                                            step="1"
                                            class="w-20 rounded-md border border-slate-300 px-2 py-1 text-center text-sm tabular-nums shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                            :placeholder="
                                                t(
                                                    'trip_detail.passengers.ph_guests',
                                                )
                                            "
                                        />
                                    </template>
                                    <template v-else-if="row.editMeta?.kind === 'cargo'">
                                        <div class="flex flex-col gap-0.5">
                                            <span
                                                class="text-[10px] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400"
                                                >{{
                                                    t(
                                                        "trip_detail.passengers.col_qty",
                                                    )
                                                }}</span
                                            >
                                            <input
                                                v-model="editDraft.qty"
                                                type="number"
                                                min="1"
                                                step="1"
                                                class="w-20 rounded-md border border-slate-300 px-2 py-1 text-center text-sm tabular-nums shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                                :placeholder="
                                                    t(
                                                        'trip_detail.passengers.ph_qty',
                                                    )
                                                "
                                            />
                                        </div>
                                    </template>
                                    <template v-else>
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="rolePillClass(row.roleKind)"
                                        >
                                            {{ row.roleLabel }}
                                        </span>
                                    </template>
                                </template>
                                <template v-else>
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="rolePillClass(row.roleKind)"
                                    >
                                        {{ row.roleLabel }}
                                    </span>
                                    <span
                                        v-if="checkedLocal[row.passengerKey]"
                                        class="ml-1 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800 dark:bg-emerald-950/55 dark:text-emerald-200"
                                    >
                                        {{
                                            t(
                                                "trip_detail.passengers.checkin_on_vehicle",
                                            )
                                        }}
                                    </span>
                                    <span
                                        v-else-if="canCheckIn"
                                        class="ml-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        {{
                                            t(
                                                "trip_detail.passengers.checkin_waiting",
                                            )
                                        }}
                                    </span>
                                </template>
                            </td>
                            <td
                                class="max-w-[220px] min-w-[9rem] px-3 py-2.5 align-top"
                                @click="onRowContentClick(row)"
                            >
                                <template
                                    v-if="editingKey === row.passengerKey && editDraft"
                                >
                                    <template v-if="row.editMeta?.kind === 'cargo'">
                                        <input
                                            v-model="editDraft.pickup_contact"
                                            type="text"
                                            class="mb-1 w-full rounded-md border border-slate-300 px-2 py-1 text-xs shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                            :placeholder="
                                                t(
                                                    'trip_detail.passengers.ph_pickup_contact',
                                                )
                                            "
                                        />
                                        <input
                                            v-model="editDraft.delivery_contact"
                                            type="text"
                                            class="w-full rounded-md border border-slate-300 px-2 py-1 text-xs shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                            :placeholder="
                                                t(
                                                    'trip_detail.passengers.ph_delivery_contact',
                                                )
                                            "
                                        />
                                    </template>
                                    <template
                                        v-else-if="
                                            row.editMeta?.kind === 'named_tp'
                                        "
                                    >
                                        <input
                                            v-model="editDraft.phone"
                                            type="tel"
                                            class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                            :placeholder="
                                                t(
                                                    'trip_detail.passengers.ph_phone',
                                                )
                                            "
                                        />
                                    </template>
                                    <template
                                        v-else-if="
                                            row.editMeta?.kind === 'passenger'
                                        "
                                    >
                                        <input
                                            v-model="editDraft.pickup"
                                            type="text"
                                            class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                            :placeholder="
                                                t(
                                                    'trip_detail.passengers.checkin_expand_pickup',
                                                )
                                            "
                                        />
                                    </template>
                                    <template v-else>
                                        <span class="text-slate-500">—</span>
                                    </template>
                                </template>
                                <template v-else>
                                    <template v-if="telHref(row.contact)">
                                        <a
                                            :href="
                                                telHref(row.contact) ||
                                                undefined
                                            "
                                            class="text-sky-700 underline-offset-2 hover:underline dark:text-sky-400"
                                            @click.stop
                                        >
                                            {{ row.contact }}
                                        </a>
                                    </template>
                                    <span v-else class="text-slate-600">{{
                                        row.contact || "—"
                                    }}</span>
                                </template>
                            </td>
                            <td
                                class="min-w-0 px-3 py-2.5 align-top text-slate-600 dark:text-slate-300"
                                @click="onRowContentClick(row)"
                            >
                                <template
                                    v-if="editingKey === row.passengerKey && editDraft"
                                >
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input
                                            v-if="row.editMeta?.kind !== 'cargo'"
                                            v-model="editDraft.notes"
                                            type="text"
                                            class="min-w-[10rem] flex-1 rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                            :placeholder="
                                                t('trip_detail.passengers.ph_notes')
                                            "
                                        />
                                        <input
                                            v-else
                                            v-model="editDraft.item_notes"
                                            type="text"
                                            class="min-w-[10rem] flex-1 rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                            :placeholder="
                                                t('trip_detail.passengers.ph_notes')
                                            "
                                        />
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50"
                                            :disabled="listBusy"
                                            :aria-label="t('trip_detail.passengers.save')"
                                            @click.stop="saveEdit(row)"
                                        >
                                            <CheckIcon
                                                class="h-4 w-4"
                                                aria-hidden="true"
                                            />
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                            :aria-label="
                                                t(
                                                    'trip_detail.passengers.cancel_edit',
                                                )
                                            "
                                            :disabled="listBusy"
                                            @click.stop="cancelEdit"
                                        >
                                            <XMarkIcon
                                                class="h-4 w-4"
                                                aria-hidden="true"
                                            />
                                        </button>
                                    </div>
                                </template>
                                <template v-else>
                                    <div
                                        class="flex flex-wrap items-center gap-1.5"
                                    >
                                        <span
                                            v-if="row.flagWheelchair"
                                            role="img"
                                            :aria-label="
                                                t(
                                                    'trip_detail.passengers.flag_wheelchair',
                                                )
                                            "
                                        >
                                            <WheelchairGlyph
                                                class="h-5 w-5 text-rose-600"
                                                aria-hidden="true"
                                            />
                                        </span>
                                        <span
                                            v-if="row.flagAllergy"
                                            role="img"
                                            :aria-label="
                                                t(
                                                    'trip_detail.passengers.flag_allergy',
                                                )
                                            "
                                        >
                                            <ExclamationTriangleIcon
                                                class="h-5 w-5 text-amber-500"
                                                aria-hidden="true"
                                            />
                                        </span>
                                        <span>{{ row.notes || "—" }}</span>
                                    </div>
                                </template>
                            </td>
                            <td
                                v-if="canEditList"
                                class="px-3 py-2.5 text-right align-top"
                                @click.stop
                            >
                                <template v-if="row.editable && row.editMeta">
                                    <div
                                        v-if="editingKey !== row.passengerKey"
                                        class="flex justify-end"
                                    >
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-lg border border-slate-200/90 bg-white p-1.5 text-rose-600 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 disabled:opacity-40 dark:border-slate-600 dark:bg-slate-900 dark:text-rose-400 dark:hover:bg-rose-950/35"
                                            :disabled="listBusy"
                                            :aria-label="
                                                t('trip_detail.passengers.dt_delete')
                                            "
                                            @click="deleteRow(row)"
                                        >
                                            <TrashIcon
                                                class="h-4 w-4"
                                                aria-hidden="true"
                                            />
                                        </button>
                                    </div>
                                </template>
                            </td>
                        </tr>
                        <tr
                            v-if="rowExpanded === row.passengerKey"
                            class="bg-slate-50/60 dark:bg-slate-800/25"
                        >
                            <td
                                :colspan="tableColSpan"
                                class="px-4 py-3 text-xs text-slate-700 dark:text-slate-200"
                            >
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <div>
                                        <div class="font-semibold text-slate-500 dark:text-slate-400">
                                            {{
                                                t(
                                                    "trip_detail.passengers.checkin_expand_pickup",
                                                )
                                            }}
                                        </div>
                                        <div class="mt-0.5">
                                            {{
                                                row.pickupAddress?.trim() || "—"
                                            }}
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-500 dark:text-slate-400">
                                            {{
                                                t(
                                                    "trip_detail.passengers.checkin_private_note",
                                                )
                                            }}
                                        </div>
                                        <div class="mt-0.5 whitespace-pre-wrap">
                                            {{ row.notes?.trim() || "—" }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div
                v-else
                class="p-6 text-center text-sm text-slate-500 dark:text-slate-400"
            >
                {{ t("trip_detail.passengers.dt_empty_table") }}
            </div>

            <div
                v-if="rows.length && filteredRows.length > 0"
                class="flex flex-col gap-2 border-t border-slate-100 px-3 py-2.5 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:text-slate-400"
            >
                <div class="tabular-nums">
                    {{
                        t("trip_detail.passengers.dt_showing", {
                            from: showFrom,
                            to: showTo,
                            total: totalFiltered,
                        })
                    }}
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        :disabled="page <= 1"
                        @click="page--"
                    >
                        {{ t("trip_detail.passengers.dt_prev") }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        :disabled="page >= totalPages"
                        @click="page++"
                    >
                        {{ t("trip_detail.passengers.dt_next") }}
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="specialSummary"
            class="mt-4 rounded-xl border border-sky-200/80 bg-sky-50/80 px-4 py-3 text-sm text-sky-950 dark:border-sky-900/60 dark:bg-sky-950/35 dark:text-sky-50"
        >
            <div
                class="text-xs font-bold uppercase tracking-wide text-sky-900/90 dark:text-sky-200"
            >
                {{ t("trip_detail.passengers.special_summary_title") }}
            </div>
            <p class="mt-1 whitespace-pre-wrap">{{ specialSummary }}</p>
        </div>

        <Teleport to="body">
            <div
                v-if="addModalOpen"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[1px]"
                role="dialog"
                aria-modal="true"
                @click.self="closeAddModal"
            >
                <div
                    class="max-h-[min(90vh,44rem)] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900"
                >
                    <h3
                        class="text-sm font-semibold text-slate-900 dark:text-slate-100"
                    >
                        {{ t("trip_detail.passengers.add_modal_title") }}
                    </h3>
                    <p
                        v-if="listKind === 'passenger'"
                        class="mt-1 text-xs text-slate-500 dark:text-slate-400"
                    >
                        {{
                            t(
                                "trip_detail.passengers.one_row_one_passenger_hint",
                            )
                        }}
                    </p>

                    <div
                        v-if="listKind === 'passenger'"
                        class="mt-4 grid gap-3 sm:grid-cols-2"
                    >
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.col_name")
                                }}</span
                            >
                            <input
                                v-model="addForm.person_in_charge"
                                type="text"
                                class="inp"
                                :placeholder="t('trip_detail.passengers.ph_name')"
                            />
                        </label>
                        <div
                            class="grid grid-cols-1 gap-3 sm:col-span-2 sm:grid-cols-2"
                        >
                            <label class="block min-w-0">
                                <span
                                    class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                    >{{
                                        t("trip_detail.passengers.m_depart_at")
                                    }}</span
                                >
                                <input
                                    v-model="addForm.depart_at"
                                    type="datetime-local"
                                    class="inp min-w-0"
                                />
                            </label>
                            <label class="block min-w-0">
                                <span
                                    class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                    >{{
                                        t("trip_detail.passengers.m_return_at")
                                    }}</span
                                >
                                <input
                                    v-model="addForm.return_at"
                                    type="datetime-local"
                                    class="inp min-w-0"
                                />
                            </label>
                        </div>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_pickup")
                                }}</span
                            >
                            <input
                                v-model="addForm.pickup"
                                type="text"
                                class="inp"
                                :placeholder="
                                    t('trip_detail.passengers.ph_pickup')
                                "
                            />
                        </label>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_dropoff")
                                }}</span
                            >
                            <input
                                v-model="addForm.dropoff"
                                type="text"
                                class="inp"
                                :placeholder="
                                    t('trip_detail.passengers.ph_dropoff')
                                "
                            />
                        </label>
                        <label class="block">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_unit_price")
                                }}</span
                            >
                            <input
                                v-model="addForm.unit_price"
                                type="text"
                                class="inp"
                                :placeholder="
                                    t(
                                        'trip_detail.passengers.ph_unit_price',
                                    )
                                "
                            />
                        </label>
                        <label class="block">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_extra_fee")
                                }}</span
                            >
                            <input
                                v-model="addForm.extra_fee"
                                type="text"
                                class="inp"
                                :placeholder="
                                    t('trip_detail.passengers.ph_extra_fee')
                                "
                            />
                        </label>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.col_notes")
                                }}</span
                            >
                            <input
                                v-model="addForm.notes"
                                type="text"
                                class="inp"
                                :placeholder="t('trip_detail.passengers.ph_notes')"
                            />
                        </label>
                    </div>

                    <div
                        v-else-if="listKind === 'business'"
                        class="mt-4 grid gap-3 sm:grid-cols-2"
                    >
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.col_guests")
                                }}</span
                            >
                            <input
                                v-model="addForm.guests"
                                type="number"
                                min="1"
                                step="1"
                                class="inp max-w-[12rem]"
                                :placeholder="
                                    t('trip_detail.passengers.ph_guests')
                                "
                            />
                        </label>
                        <div
                            class="grid grid-cols-1 gap-3 sm:col-span-2 sm:grid-cols-2"
                        >
                            <label class="block min-w-0">
                                <span
                                    class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                    >{{
                                        t(
                                            "trip_detail.passengers.m_depart_at",
                                        )
                                    }}</span
                                >
                                <input
                                    v-model="addForm.depart_at"
                                    type="datetime-local"
                                    class="inp min-w-0"
                                />
                            </label>
                            <label class="block min-w-0">
                                <span
                                    class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                    >{{
                                        t(
                                            "trip_detail.passengers.m_return_at",
                                        )
                                    }}</span
                                >
                                <input
                                    v-model="addForm.return_at"
                                    type="datetime-local"
                                    class="inp min-w-0"
                                />
                            </label>
                        </div>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_pickup")
                                }}</span
                            >
                            <input
                                v-model="addForm.pickup"
                                type="text"
                                class="inp"
                                :placeholder="
                                    t('trip_detail.passengers.ph_pickup')
                                "
                            />
                        </label>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_waypoint")
                                }}</span
                            >
                            <input
                                v-model="addForm.waypoint"
                                type="text"
                                class="inp"
                                :placeholder="
                                    t('trip_detail.passengers.ph_waypoint')
                                "
                            />
                        </label>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_dropoff")
                                }}</span
                            >
                            <input
                                v-model="addForm.dropoff"
                                type="text"
                                class="inp"
                                :placeholder="
                                    t('trip_detail.passengers.ph_dropoff')
                                "
                            />
                        </label>
                        <label class="block">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_unit_price")
                                }}</span
                            >
                            <input
                                v-model="addForm.unit_price"
                                type="text"
                                class="inp"
                                :placeholder="
                                    t(
                                        'trip_detail.passengers.ph_unit_price',
                                    )
                                "
                            />
                        </label>
                        <label class="block">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_extra_fee")
                                }}</span
                            >
                            <input
                                v-model="addForm.extra_fee"
                                type="text"
                                class="inp"
                                :placeholder="
                                    t(
                                        'trip_detail.passengers.ph_extra_fee',
                                    )
                                "
                            />
                        </label>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.col_notes")
                                }}</span
                            >
                            <input
                                v-model="addForm.notes"
                                type="text"
                                class="inp"
                                :placeholder="t('trip_detail.passengers.ph_notes')"
                            />
                        </label>
                    </div>

                    <div v-else class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.col_name")
                                }}</span
                            >
                            <input
                                v-model="addForm.name"
                                type="text"
                                class="inp"
                                :placeholder="
                                    t('trip_detail.passengers.ph_cargo_name')
                                "
                            />
                        </label>
                        <label class="block">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.col_qty")
                                }}</span
                            >
                            <input
                                v-model="addForm.qty"
                                type="number"
                                min="1"
                                step="1"
                                class="inp"
                            />
                        </label>
                        <label class="block">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_cost")
                                }}</span
                            >
                            <input
                                v-model="addForm.cost"
                                type="text"
                                class="inp"
                            />
                        </label>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_dimensions")
                                }}</span
                            >
                            <input
                                v-model="addForm.dimensions"
                                type="text"
                                class="inp"
                            />
                        </label>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_weight")
                                }}</span
                            >
                            <input
                                v-model="addForm.weight"
                                type="text"
                                class="inp"
                            />
                        </label>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.col_notes")
                                }}</span
                            >
                            <input
                                v-model="addForm.item_notes"
                                type="text"
                                class="inp"
                            />
                        </label>
                        <label class="block">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_pickup_at")
                                }}</span
                            >
                            <input
                                v-model="addForm.pickup_at"
                                type="datetime-local"
                                class="inp"
                            />
                        </label>
                        <label class="block">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_delivery_at")
                                }}</span
                            >
                            <input
                                v-model="addForm.delivery_at"
                                type="datetime-local"
                                class="inp"
                            />
                        </label>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_pickup_place")
                                }}</span
                            >
                            <input
                                v-model="addForm.pickup_place"
                                type="text"
                                class="inp"
                            />
                        </label>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t(
                                        'trip_detail.passengers.m_delivery_place',
                                    )
                                }}</span
                            >
                            <input
                                v-model="addForm.delivery_place"
                                type="text"
                                class="inp"
                            />
                        </label>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.ph_pickup_contact")
                                }}</span
                            >
                            <input
                                v-model="addForm.pickup_contact"
                                type="text"
                                class="inp"
                            />
                        </label>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t(
                                        'trip_detail.passengers.ph_delivery_contact',
                                    )
                                }}</span
                            >
                            <input
                                v-model="addForm.delivery_contact"
                                type="text"
                                class="inp"
                            />
                        </label>
                        <label class="block sm:col-span-2">
                            <span
                                class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400"
                                >{{
                                    t("trip_detail.passengers.m_transport_note")
                                }}</span
                            >
                            <input
                                v-model="addForm.transport_note"
                                type="text"
                                class="inp"
                            />
                        </label>
                    </div>

                    <div class="mt-5 flex flex-wrap justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            :disabled="listBusy"
                            @click="closeAddModal"
                        >
                            {{ t("trip_detail.passengers.cancel_edit") }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg bg-sky-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-sky-700 disabled:opacity-50"
                            :disabled="listBusy"
                            @click="submitAddModal"
                        >
                            {{ t("trip_detail.passengers.save") }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </section>
</template>

<script setup lang="ts">
import { computed, ref, watch, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import {
    ArrowDownTrayIcon,
    CheckIcon,
    ChevronDownIcon,
    MagnifyingGlassIcon,
    TrashIcon,
    XMarkIcon,
} from "@heroicons/vue/24/outline";
import { ExclamationTriangleIcon } from "@heroicons/vue/24/solid";
import {
    emptyPassengerRow,
    emptyBusinessRow,
    emptyCargoRow,
    isPassengerRowFilled,
    isBusinessRowFilled,
    isCargoRowFilled,
} from "../../composables/dispatchWizardConstants";
import { showAppError } from "../../composables/appMessage";
import { togglePassengerCheckInApi } from "../../composables/usePassengerCheckIn";
import { confirmAction } from "../../composables/useConfirm";

const WheelchairGlyph = {
    name: "WheelchairGlyph",
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm7.94 14.13-1.39-3.47A2 2 0 0 0 16.67 16H13v-2.34c1.81.34 3.72-.37 4.92-2.02l1.14-1.59a1 1 0 0 0-1.62-1.16l-1.15 1.6c-.72 1-1.86 1.51-3.03 1.51h-.61a1 1 0 0 0-.98.8l-2.2 11a1 1 0 1 0 1.96.39l2.03-10.19H16a4 4 0 0 1 3.89 3.05l1.39 3.47a1 1 0 1 0 1.86-.73ZM7 12a5 5 0 1 0 5 5 5 5 0 0 0-5-5Zm0 8a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg>',
};

export type PassengerEditMeta = {
    kind: "passenger" | "business" | "cargo" | "named_tp";
    rowIndex: number;
};

export type PassengerRow = {
    passengerKey: string;
    name: string;
    roleKind: string;
    roleLabel: string;
    contact: string;
    notes: string;
    pickupAddress?: string;
    flagWheelchair?: boolean;
    flagAllergy?: boolean;
    editMeta?: PassengerEditMeta | null;
    editable?: boolean;
    editFields?: Record<string, string> | null;
};

const props = defineProps<{
    tripId: number;
    trip: Record<string, unknown> | null;
    rows: PassengerRow[];
    canCheckIn: boolean;
    canEditList: boolean;
    specialSummary?: string;
}>();

const emit = defineEmits<{
    "trip-updated": [trip: Record<string, unknown>];
    "passenger-list-save": [
        payload: {
            meta: PassengerEditMeta;
            draft: Record<string, string>;
            resolve: (ok: boolean) => void;
        },
    ];
    "passenger-list-delete": [
        payload: {
            keys: string[];
            resolve: (ok: boolean) => void;
        },
    ];
    "passenger-list-add-submit": [
        payload: {
            kind: "passenger" | "business" | "cargo";
            draft: Record<string, string>;
            resolve: (ok: boolean) => void;
        },
    ];
}>();

const { t } = useI18n();

type StatusFilterKey = "all" | "waiting" | "onboard";

const statusFilter = ref<StatusFilterKey>("all");
const passengerSearch = ref("");
const page = ref(1);
const pageSize = ref(10);
const selectedKeys = ref<string[]>([]);
const rowExpanded = ref<string | null>(null);
const checkingKey = ref<string | null>(null);
const checkedLocal = ref<Record<string, boolean>>({});
const checkedAtLocal = ref<Record<string, string>>({});
const editingKey = ref<string | null>(null);
const editDraft = ref<Record<string, string> | null>(null);
const listBusy = ref(false);
const headerSelectRef = ref<HTMLInputElement | null>(null);
const addModalOpen = ref(false);
const addForm = ref<Record<string, string>>({});

function syncFromTrip() {
    const m = props.trip?.passenger_check_ins as
        | Record<string, { checked_in_at?: string }>
        | undefined;
    const next: Record<string, boolean> = {};
    const times: Record<string, string> = {};
    if (m && typeof m === "object") {
        for (const row of props.rows) {
            const cell = m[row.passengerKey];
            if (cell && typeof cell === "object" && cell.checked_in_at) {
                next[row.passengerKey] = true;
                times[row.passengerKey] = String(cell.checked_in_at);
            }
        }
    }
    checkedLocal.value = next;
    checkedAtLocal.value = times;
}

watch(
    () => [props.trip?.passenger_check_ins, props.rows],
    () => syncFromTrip(),
    { deep: true, immediate: true },
);

watch(
    () => props.rows,
    () => {
        selectedKeys.value = selectedKeys.value.filter((k) =>
            props.rows.some((r) => r.passengerKey === k),
        );
        if (editingKey.value && !props.rows.some((r) => r.passengerKey === editingKey.value)) {
            editingKey.value = null;
            editDraft.value = null;
        }
    },
    { deep: true },
);

watch([passengerSearch, statusFilter], () => {
    rowExpanded.value = null;
    page.value = 1;
});

watch([() => props.rows.length, pageSize], () => {
    const maxPage = Math.max(
        1,
        Math.ceil(filteredRows.value.length / pageSize.value) || 1,
    );
    if (page.value > maxPage) page.value = maxPage;
});

const passengerTableScroll = computed(
    () => props.rows.length >= 10,
);

const listKind = computed((): "passenger" | "business" | "cargo" => {
    const dr = props.trip?.dispatch_request as
        | { trip_type?: string }
        | undefined;
    const tt = String(dr?.trip_type ?? "");
    if (tt === "cargo") return "cargo";
    if (tt === "business") return "business";
    return "passenger";
});

const waitingCount = computed(
    () => props.rows.filter((r) => !checkedLocal.value[r.passengerKey]).length,
);

const onboardCount = computed(
    () => props.rows.filter((r) => !!checkedLocal.value[r.passengerKey]).length,
);

const checkedCount = computed(
    () => props.rows.filter((r) => checkedLocal.value[r.passengerKey]).length,
);

const rowsFilteredByStatus = computed(() => {
    const all = props.rows ?? [];
    if (!props.canCheckIn || statusFilter.value === "all") return all;
    if (statusFilter.value === "waiting") {
        return all.filter((r) => !checkedLocal.value[r.passengerKey]);
    }
    return all.filter((r) => !!checkedLocal.value[r.passengerKey]);
});

const filteredRows = computed(() => {
    const base = rowsFilteredByStatus.value;
    const q = passengerSearch.value.trim().toLowerCase();
    if (!q) return base;
    return base.filter((row) => {
        const hay = [
            row.name,
            row.contact,
            row.notes,
            row.roleLabel,
            row.pickupAddress,
        ]
            .join(" ")
            .toLowerCase();
        return hay.includes(q);
    });
});

const totalFiltered = computed(() => filteredRows.value.length);
const totalPages = computed(() =>
    Math.max(1, Math.ceil(totalFiltered.value / pageSize.value) || 1),
);
const pageStart = computed(() => (page.value - 1) * pageSize.value);
const paginatedRows = computed(() => {
    const f = filteredRows.value;
    const start = pageStart.value;
    return f.slice(start, start + pageSize.value);
});
const showFrom = computed(() =>
    totalFiltered.value === 0 ? 0 : pageStart.value + 1,
);
const showTo = computed(() =>
    Math.min(pageStart.value + pageSize.value, totalFiltered.value),
);

const selectableKeysFiltered = computed(() =>
    filteredRows.value
        .filter((r) => isRowSelectable(r))
        .map((r) => r.passengerKey),
);

function isRowSelectable(row: PassengerRow) {
    return !!(props.canEditList && row.editable && row.editMeta);
}

function filterChipClass(key: StatusFilterKey): string {
    const on = statusFilter.value === key;
    return on
        ? "border-blue-500 bg-blue-50 text-blue-800 ring-1 ring-blue-200 dark:border-blue-600 dark:bg-blue-950/55 dark:text-blue-200 dark:ring-blue-900/70"
        : "border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800/90";
}

const filterOptions = computed(() => {
    if (!props.canCheckIn || !props.rows.length) return [];
    return [
        {
            key: "all" as const,
            label: t("trip_detail.passengers.filter_all"),
            badge: props.rows.length,
            ariaLabel: t("trip_detail.passengers.filter_all_aria"),
        },
        {
            key: "waiting" as const,
            label: t("trip_detail.passengers.filter_waiting"),
            badge: waitingCount.value,
            ariaLabel: t("trip_detail.passengers.filter_waiting_aria"),
        },
        {
            key: "onboard" as const,
            label: t("trip_detail.passengers.filter_onboard"),
            badge: onboardCount.value,
            ariaLabel: t("trip_detail.passengers.filter_onboard_aria"),
        },
    ];
});

const tableColSpan = computed(() => {
    let n = 4;
    if (props.canEditList) n += 2;
    if (props.canCheckIn) n += 1;
    return n;
});

function initials(name: string) {
    const n = String(name ?? "").trim();
    if (!n || n === "—") return "?";
    const parts = n.split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function rolePillClass(kind: string) {
    if (kind === "staff")
        return "bg-slate-200 text-slate-800 dark:bg-slate-700/70 dark:text-slate-100";
    if (kind === "student")
        return "bg-sky-50 text-sky-800 dark:bg-sky-950/55 dark:text-sky-200";
    if (kind === "cargo")
        return "bg-amber-50 text-amber-900 dark:bg-amber-950/45 dark:text-amber-100";
    return "bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200";
}

function telHref(contact: string) {
    const raw = String(contact ?? "").trim();
    if (!raw || raw === "—") return "";
    const digits = raw.replace(/[^\d+]/g, "");
    if (digits.length < 8) return "";
    return `tel:${digits}`;
}

function toggleExpand(key: string) {
    rowExpanded.value = rowExpanded.value === key ? null : key;
}

function onRowContentClick(row: PassengerRow) {
    if (listBusy.value) return;
    if (editingKey.value === row.passengerKey) return;
    if (row.editable && row.editMeta) {
        startEdit(row);
        return;
    }
    toggleExpand(row.passengerKey);
}

function onRowBackgroundClick(row: PassengerRow) {
    onRowContentClick(row);
}

function checkTimeLabel(key: string) {
    const iso = checkedAtLocal.value[key];
    if (!iso) return "";
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return "";
    return d.toLocaleTimeString(undefined, {
        hour: "2-digit",
        minute: "2-digit",
    });
}

function onToggleAll(ev: Event) {
    const el = ev.target as HTMLInputElement;
    const all = selectableKeysFiltered.value;
    if (el.checked) {
        selectedKeys.value = [...new Set([...selectedKeys.value, ...all])];
    } else {
        selectedKeys.value = selectedKeys.value.filter((k) => !all.includes(k));
    }
}

watch(
    [selectedKeys, selectableKeysFiltered],
    () => {
        nextTick(() => {
            const el = headerSelectRef.value;
            if (!el) return;
            const all = selectableKeysFiltered.value;
            const picked = selectedKeys.value.filter((k) =>
                all.includes(k),
            ).length;
            el.checked = picked === all.length && all.length > 0;
            el.indeterminate = picked > 0 && picked < all.length;
        });
    },
    { deep: true },
);

function startEdit(row: PassengerRow) {
    if (!row.editFields || !row.editMeta) return;
    rowExpanded.value = null;
    editingKey.value = row.passengerKey;
    editDraft.value = { ...row.editFields };
}

function cancelEdit() {
    editingKey.value = null;
    editDraft.value = null;
}

function saveEdit(row: PassengerRow) {
    if (!row.editMeta || !editDraft.value) return;
    listBusy.value = true;
    emit("passenger-list-save", {
        meta: row.editMeta,
        draft: { ...editDraft.value },
        resolve(ok) {
            listBusy.value = false;
            if (ok) {
                editingKey.value = null;
                editDraft.value = null;
            }
        },
    });
}

async function deleteRow(row: PassengerRow) {
    if (!row.editable || !row.editMeta) return;
    const okC = await confirmAction({
        title: t("trip_detail.passengers.dt_delete_confirm_title"),
        message: t("trip_detail.passengers.dt_delete_confirm_body"),
        confirmLabel: t("trip_detail.passengers.dt_delete"),
        cancelLabel: t("trip_detail.passengers.cancel_edit"),
    });
    if (!okC) return;
    listBusy.value = true;
    emit("passenger-list-delete", {
        keys: [row.passengerKey],
        resolve(ok) {
            listBusy.value = false;
            if (ok) {
                editingKey.value = null;
                editDraft.value = null;
                selectedKeys.value = selectedKeys.value.filter(
                    (k) => k !== row.passengerKey,
                );
            }
        },
    });
}

async function onBulkDelete() {
    if (!selectedKeys.value.length) return;
    const okC = await confirmAction({
        title: t("trip_detail.passengers.dt_bulk_confirm_title"),
        message: t("trip_detail.passengers.dt_bulk_confirm_body", {
            n: selectedKeys.value.length,
        }),
        confirmLabel: t("trip_detail.passengers.dt_bulk_delete"),
        cancelLabel: t("trip_detail.passengers.cancel_edit"),
    });
    if (!okC) return;
    const keys = [...selectedKeys.value];
    listBusy.value = true;
    emit("passenger-list-delete", {
        keys,
        resolve(ok) {
            listBusy.value = false;
            if (ok) {
                selectedKeys.value = [];
                page.value = 1;
            }
        },
    });
}

function openAddModal() {
    if (listKind.value === "cargo")
        addForm.value = { ...emptyCargoRow() };
    else if (listKind.value === "business")
        addForm.value = { ...emptyBusinessRow() };
    else addForm.value = { ...emptyPassengerRow() };
    addModalOpen.value = true;
}

function closeAddModal() {
    if (listBusy.value) return;
    addModalOpen.value = false;
}

function submitAddModal() {
    const draft = { ...addForm.value };
    let filled = false;
    if (listKind.value === "cargo") filled = isCargoRowFilled(draft as never);
    else if (listKind.value === "business")
        filled = isBusinessRowFilled(draft as never);
    else filled = isPassengerRowFilled(draft as never);
    if (!filled) {
        showAppError(t("trip_detail.passengers.validation_need_one"));
        return;
    }
    listBusy.value = true;
    emit("passenger-list-add-submit", {
        kind: listKind.value,
        draft,
        resolve(ok) {
            listBusy.value = false;
            if (ok) {
                addModalOpen.value = false;
                page.value = 1;
            }
        },
    });
}

function csvEscapeCell(v: string): string {
    const s = String(v ?? "").replace(/"/g, '""');
    if (/[,"\n\r]/.test(s)) return `"${s}"`;
    return s;
}

function exportCsv(): void {
    const list = props.rows ?? [];
    if (!list.length) return;
    const delim = ",";
    const head = [
        t("trip_detail.passengers.col_name"),
        t("trip_detail.passengers.col_role"),
        t("trip_detail.passengers.col_contact"),
        t("trip_detail.passengers.col_notes"),
        t("trip_detail.passengers.csv_pickup"),
        t("trip_detail.passengers.csv_checked"),
    ];
    const lines = [head.map(csvEscapeCell).join(delim)];
    for (const row of list) {
        const checkedFlag = checkedLocal.value[row.passengerKey]
            ? t("trip_detail.passengers.csv_yes")
            : t("trip_detail.passengers.csv_no");
        lines.push(
            [
                csvEscapeCell(row.name),
                csvEscapeCell(row.roleLabel),
                csvEscapeCell(row.contact),
                csvEscapeCell(row.notes),
                csvEscapeCell(row.pickupAddress ?? ""),
                csvEscapeCell(checkedFlag),
            ].join(delim),
        );
    }
    const blob = new Blob([`\uFEFF${lines.join("\n")}`], {
        type: "text/csv;charset=utf-8;",
    });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `passengers-trip-${props.tripId}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

async function onToggleCheck(row: PassengerRow) {
    if (!props.canCheckIn) return;
    const key = row.passengerKey;
    const prev = !!checkedLocal.value[key];
    const next = !prev;
    checkedLocal.value = { ...checkedLocal.value, [key]: next };
    if (next) {
        const iso = new Date().toISOString();
        checkedAtLocal.value = { ...checkedAtLocal.value, [key]: iso };
    } else {
        const { [key]: _rm, ...rest } = checkedAtLocal.value;
        checkedAtLocal.value = rest;
    }

    checkingKey.value = key;
    const res = await togglePassengerCheckInApi({
        tripId: props.tripId,
        passengerKey: key,
        nextChecked: next,
        errorMessage: t("trip_detail.passengers.checkin_error"),
    });
    checkingKey.value = null;

    if (!res.ok) {
        checkedLocal.value = { ...checkedLocal.value, [key]: prev };
        if (!prev) {
            const { [key]: _t, ...restT } = checkedAtLocal.value;
            checkedAtLocal.value = restT;
        }
        return;
    }
    if (res.data) emit("trip-updated", res.data);
}
</script>

<style scoped>
.inp {
    @apply w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-slate-900 shadow-sm outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-500/25 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100;
}
</style>
