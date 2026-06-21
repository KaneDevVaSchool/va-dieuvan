<template>
    <section
        class="w-full min-w-0 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-5 dark:border-slate-700/70 dark:bg-slate-950/30"
        :aria-label="t('trip_detail.passengers.title', { n: displayPassengerTotal })"
    >
        <!-- Header -->
        <div class="flex flex-wrap items-center gap-2">
            <h2
                class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"
            >
                {{ t("trip_detail.passengers.title", { n: displayPassengerTotal }) }}
            </h2>
            <span
                v-if="opsMode && displayPassengerTotal > 0"
                class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200"
            >
                {{
                    t("trip_detail.passengers.checkin_header_badge", {
                        checked: boardedCount,
                        total: displayPassengerTotal,
                    })
                }}
            </span>
        </div>

        <!-- Tiến độ lên xe -->
        <template v-if="opsMode && rows.length">
            <div class="mt-3">
                <div class="mb-1 flex items-center justify-between text-[11px] font-medium text-slate-500 dark:text-slate-400">
                    <span>{{ t("trip_detail.passengers.progress_title") }}</span>
                    <span class="tabular-nums">
                        {{
                            t("trip_detail.passengers.progress_summary", {
                                done: boardedCount,
                                total: displayPassengerTotal,
                            })
                        }}
                    </span>
                </div>
                <div
                    class="h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                    role="progressbar"
                    :aria-valuenow="boardedPct"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <div
                        class="h-full rounded-full bg-emerald-500 transition-[width] duration-500 dark:bg-emerald-500"
                        :style="{ width: boardedPct + '%' }"
                    />
                </div>
            </div>
        </template>

        <!-- Toolbar datagrid -->
        <div
            v-if="rows.length || canEditList"
            class="mt-3 overflow-visible"
        >
            <div class="border-b border-slate-100 pb-3 dark:border-slate-700">
                <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
                    <div
                        v-if="rows.length"
                        class="min-w-0 w-full basis-full lg:min-w-[10rem] lg:flex-1 lg:basis-auto"
                    >
                        <DatagridToolbarSearch
                            v-model="passengerSearch"
                            input-id="trip-passengers-search"
                            :placeholder="t('trip_detail.passengers.dt_search_ph')"
                            :aria-label="t('trip_detail.passengers.search_aria')"
                            stretch
                            inline-actions
                            hide-label
                            input-height="h-10"
                        />
                    </div>
                    <div v-else class="min-h-0 w-full flex-1 lg:w-auto" />

                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        <FilterVisibilityDropdown
                            v-if="opsMode && rows.length"
                            :open="showFilterPanelDd"
                            :title="t('trip_detail.passengers.filter_show_controls_title')"
                            :hint="t('trip_detail.passengers.filter_show_controls_hint')"
                            @close="closeFilterPanel"
                        >
                            <template #trigger>
                                <DatagridToolbarActionButton
                                    icon="filter"
                                    :active="showFilterPanelDd"
                                    test-id="trip-passengers-toolbar-filter"
                                    @click="openFilterPanel()"
                                >
                                    {{ t('trip_detail.passengers.toolbar_filter') }}
                                </DatagridToolbarActionButton>
                            </template>
                            <li
                                v-for="fd in filterControlDefs"
                                :key="'trip-pax-vis-' + fd.key"
                                class="flex items-start gap-2"
                            >
                                <input
                                    :id="`trip-pax-filter-vis-${fd.key}`"
                                    v-model="visibleFilters[fd.key]"
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-va-800 focus:ring-va-700/30 dark:border-slate-600"
                                    :data-testid="`trip-pax-filter-vis-${fd.key}`"
                                />
                                <label
                                    :for="`trip-pax-filter-vis-${fd.key}`"
                                    class="cursor-pointer text-sm leading-snug text-slate-700 dark:text-slate-300"
                                >
                                    {{ fd.label }}
                                </label>
                            </li>
                        </FilterVisibilityDropdown>

                        <button
                            v-if="canEditList"
                            type="button"
                            class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-lg border border-sky-200/80 bg-sky-50/80 px-3 text-sm font-semibold text-sky-800 shadow-sm transition hover:bg-sky-100 disabled:opacity-50 dark:border-sky-800/50 dark:bg-sky-950/40 dark:text-sky-200 dark:hover:bg-sky-950/70"
                            :disabled="listBusy"
                            data-testid="trip-passengers-add-row"
                            @click="openAddModal"
                        >
                            <UserPlusIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                            {{ t('trip_detail.passengers.dt_add_row') }}
                        </button>

                        <DatagridToolbarActionButton
                            v-if="rows.length"
                            icon="export"
                            test-id="trip-passengers-toolbar-export"
                            @click="exportCsv"
                        >
                            {{ t('trip_detail.passengers.export_csv') }}
                        </DatagridToolbarActionButton>
                    </div>

                    <div
                        v-if="opsMode && rows.length"
                        class="ml-auto flex h-10 shrink-0 items-center rounded-lg border border-slate-200 bg-slate-50 p-0.5 dark:border-slate-600 dark:bg-slate-900"
                        role="group"
                        :aria-label="t('trip_detail.passengers.view.aria')"
                    >
                        <button
                            v-for="vm in viewModes"
                            :key="vm.key"
                            type="button"
                            class="rounded-md px-2.5 py-1.5 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-va-700/30"
                            :class="
                                viewMode === vm.key
                                    ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-slate-100'
                                    : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'
                            "
                            :aria-pressed="viewMode === vm.key"
                            :data-testid="`trip-passengers-view-${vm.key}`"
                            @click="viewMode = vm.key"
                        >
                            {{ vm.label }}
                        </button>
                    </div>
                </div>

                <div
                    v-if="hasFilterRow && rows.length"
                    class="mt-3 grid grid-cols-1 gap-3 border-t border-slate-100 pt-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 dark:border-slate-700"
                >
                    <DatagridFilterField v-if="visibleFilters.status && opsMode">
                        <select
                            v-model="statusFilterSelect"
                            :class="FILTER_CONTROL_CLASS"
                            :aria-label="t('trip_detail.passengers.col_status')"
                            data-testid="trip-passengers-filter-status"
                        >
                            <option value="">
                                {{ t('trip_detail.passengers.col_status') }}
                            </option>
                            <option
                                v-for="s in PASSENGER_STATUSES"
                                :key="s.key"
                                :value="s.key"
                            >
                                {{ t(`trip_detail.passengers.status.${s.i18n}`) }}
                            </option>
                        </select>
                    </DatagridFilterField>

                    <DatagridFilterField
                        v-if="visibleFilters.page_size && viewMode === 'table'"
                    >
                        <select
                            v-model.number="pageSize"
                            :class="FILTER_CONTROL_CLASS"
                            :aria-label="t('trip_detail.passengers.dt_page_size_aria')"
                            data-testid="trip-passengers-filter-page-size"
                        >
                            <option :value="5">5</option>
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="20">20</option>
                        </select>
                    </DatagridFilterField>
                </div>
            </div>
        </div>

        <!-- BULK ACTION BAR -->
        <div
            v-if="opsMode && selectedKeys.length"
            class="mt-3 flex flex-wrap items-center gap-2 rounded-xl border border-blue-200 bg-blue-50/80 px-3 py-2 dark:border-blue-900/60 dark:bg-blue-950/35"
        >
            <span class="text-xs font-semibold text-blue-900 dark:text-blue-200">
                {{ t("trip_detail.passengers.bulk_selected", { n: selectedKeys.length }) }}
            </span>
            <div class="ml-auto flex flex-wrap items-center gap-1.5">
                <button
                    v-for="ba in bulkStatusActions"
                    :key="ba.status"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                    :disabled="bulkBusy"
                    @click="applyBulkStatus(ba.status)"
                >
                    <span class="h-1.5 w-1.5 rounded-full" :class="ba.dot" />
                    {{ ba.label }}
                </button>
                <button
                    type="button"
                    class="rounded-lg px-2 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100 dark:text-blue-300 dark:hover:bg-blue-900/50"
                    @click="clearSelection"
                >
                    {{ t("trip_detail.passengers.bulk_clear") }}
                </button>
            </div>
        </div>

        <!-- 5. DATA GRID -->
        <div
            class="mt-4 overflow-x-auto rounded-xl ring-1 ring-slate-200/60 dark:ring-slate-700/60"
            :class="tableScroll ? 'max-h-[min(32rem,74vh)] overflow-y-auto overscroll-contain' : ''"
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
                    class="sticky top-0 z-10 bg-slate-50/95 shadow-sm backdrop-blur-sm dark:bg-slate-800/95"
                >
                    <tr>
                        <th
                            v-if="opsMode"
                            scope="col"
                            class="w-10 px-2 py-2.5 text-left"
                        >
                            <input
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                :checked="allVisibleSelected"
                                :indeterminate.prop="someVisibleSelected && !allVisibleSelected"
                                :aria-label="t('trip_detail.passengers.select_all_aria')"
                                @change="toggleSelectAll"
                            />
                        </th>
                        <th scope="col" class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300">
                            {{ t("trip_detail.passengers.col_name") }}
                        </th>
                        <th v-if="hasLegs" scope="col" class="min-w-[9rem] px-3 py-2.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300">
                            {{ t("trip_detail.passengers.col_leg") }}
                        </th>
                        <th scope="col" class="min-w-[9rem] max-w-[220px] px-3 py-2.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300">
                            {{ t("trip_detail.passengers.col_contact") }}
                        </th>
                        <th v-if="opsMode" scope="col" class="min-w-[8rem] px-3 py-2.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300">
                            {{ t("trip_detail.passengers.col_pickup") }}
                        </th>
                        <th v-if="opsMode" scope="col" class="min-w-[8.5rem] px-3 py-2.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300">
                            {{ t("trip_detail.passengers.col_status") }}
                        </th>
                        <th v-if="opsMode" scope="col" class="w-24 px-3 py-2.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300">
                            {{ t("trip_detail.passengers.col_board_time") }}
                        </th>
                        <th scope="col" class="min-w-0 px-3 py-2.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300">
                            {{ t("trip_detail.passengers.col_notes") }}
                        </th>
                        <th v-if="showActionsCol" scope="col" class="w-px px-3 py-2.5 text-right text-xs font-semibold text-slate-600 dark:text-slate-300">
                            {{ t("trip_detail.passengers.dt_col_actions") }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-700 dark:bg-slate-950/40">
                    <template v-for="item in renderItems" :key="item.key">
                        <!-- Group header (grouping views) -->
                        <tr v-if="item.type === 'group'" class="bg-slate-50/80 dark:bg-slate-800/40">
                            <th
                                :colspan="tableColSpan"
                                scope="colgroup"
                                class="px-3 py-2 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <span v-if="item.dot" class="h-2 w-2 rounded-full" :class="item.dot" />
                                    {{ item.label }}
                                    <span class="rounded bg-slate-200 px-1.5 text-[10px] tabular-nums text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                                        {{ item.count }}
                                    </span>
                                </span>
                            </th>
                        </tr>

                        <!-- Passenger row -->
                        <tr
                            v-else
                            class="cursor-pointer transition hover:bg-slate-50 dark:hover:bg-slate-800/40"
                            :class="rowClass(item.row)"
                            @click="onRowContentClick(item.row)"
                        >
                            <td v-if="opsMode" class="px-2 py-2.5 align-middle" @click.stop>
                                <input
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                    :checked="!!selected[item.row.passengerKey]"
                                    :aria-label="t('trip_detail.passengers.select_row_aria')"
                                    @change="toggleSelect(item.row.passengerKey)"
                                />
                            </td>

                            <!-- Name / avatar (with inline edit fallback for editable list rows) -->
                            <td class="px-3 py-2.5 align-top">
                                <template v-if="editingKey === item.row.passengerKey && editDraft">
                                    <input
                                        v-if="item.row.editMeta?.kind === 'cargo'"
                                        v-model="editDraft.name"
                                        type="text"
                                        class="w-full min-w-[8rem] rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                        :placeholder="t('trip_detail.passengers.ph_cargo_name')"
                                        @click.stop
                                    />
                                    <div v-else-if="item.row.editMeta?.kind === 'business'" class="text-sm text-slate-600 dark:text-slate-400">
                                        {{ item.row.name }}
                                    </div>
                                    <input
                                        v-else
                                        v-model="editDraft.person_in_charge"
                                        type="text"
                                        class="w-full min-w-[8rem] rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                        :placeholder="t('trip_detail.passengers.ph_name')"
                                        @click.stop
                                    />
                                </template>
                                <div v-else class="flex items-center gap-2">
                                    <div
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-[11px] font-bold"
                                        :class="avatarClass(item.row)"
                                    >
                                        {{ initials(item.row.name) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate font-medium text-slate-900 dark:text-slate-100">
                                            {{ item.row.name }}
                                        </div>
                                        <div class="truncate text-[11px] text-slate-400">{{ item.row.roleLabel }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Chặng -->
                            <td v-if="hasLegs" class="min-w-[9rem] px-3 py-2.5 align-top">
                                <select
                                    v-if="editingKey === item.row.passengerKey && editDraft && item.row.editMeta?.kind === 'named_tp'"
                                    v-model="editDraft.leg_key"
                                    class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                    :aria-label="t('trip_detail.passengers.col_leg')"
                                    @click.stop
                                >
                                    <option value="">{{ t("trip_detail.passengers.leg_unassigned") }}</option>
                                    <option v-for="opt in legOptions" :key="opt.key" :value="opt.key">
                                        {{ opt.label }}
                                    </option>
                                </select>
                                <span
                                    v-else-if="item.row.legLabel"
                                    class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                >
                                    {{ item.row.legLabel }}
                                </span>
                                <span v-else class="text-[11px] italic text-slate-400 dark:text-slate-500">
                                    {{ t("trip_detail.passengers.leg_unassigned") }}
                                </span>
                            </td>

                            <!-- Contact -->
                            <td class="max-w-[220px] min-w-[9rem] px-3 py-2.5 align-top">
                                <template v-if="editingKey === item.row.passengerKey && editDraft">
                                    <input
                                        v-if="item.row.editMeta?.kind === 'cargo'"
                                        v-model="editDraft.pickup_contact"
                                        type="text"
                                        class="w-full rounded-md border border-slate-300 px-2 py-1 text-xs shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                        :placeholder="t('trip_detail.passengers.ph_pickup_contact')"
                                        @click.stop
                                    />
                                    <input
                                        v-else-if="item.row.editMeta?.kind === 'named_tp'"
                                        v-model="editDraft.phone"
                                        type="tel"
                                        class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                        :placeholder="t('trip_detail.passengers.ph_phone')"
                                        @click.stop
                                    />
                                    <input
                                        v-else-if="item.row.editMeta?.kind === 'passenger'"
                                        v-model="editDraft.pickup"
                                        type="text"
                                        class="w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                        :placeholder="t('trip_detail.passengers.checkin_expand_pickup')"
                                        @click.stop
                                    />
                                    <EmptyValue v-else empty-key="trip_detail.empty.cell" />
                                </template>
                                <template v-else>
                                    <a
                                        v-if="telHref(item.row.contact)"
                                        :href="telHref(item.row.contact) || undefined"
                                        class="inline-flex items-center gap-1 text-sky-700 underline-offset-2 hover:underline dark:text-sky-400"
                                        @click.stop
                                    >
                                        <PhoneIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                        {{ item.row.contact }}
                                    </a>
                                    <EmptyValue v-else :value="item.row.contact" empty-key="trip_detail.empty.contact" />
                                </template>
                            </td>

                            <!-- Pickup -->
                            <td v-if="opsMode" class="min-w-[8rem] px-3 py-2.5 align-top text-slate-600 dark:text-slate-300">
                                <EmptyValue :value="item.row.pickupAddress" empty-key="trip_detail.empty.address" />
                            </td>

                            <!-- Status -->
                            <td v-if="opsMode" class="px-3 py-2.5 align-top" @click.stop>
                                <div class="relative inline-block">
                                    <select
                                        :value="statusOf(item.row.passengerKey)"
                                        :disabled="busyKey === item.row.passengerKey"
                                        class="appearance-none rounded-full border-0 py-1 pl-2.5 pr-7 text-[11px] font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :class="passengerStatusMeta(statusOf(item.row.passengerKey)).badge"
                                        :aria-label="t('trip_detail.passengers.action_set_status')"
                                        @change="onStatusSelect(item.row, $event)"
                                    >
                                        <option v-for="s in PASSENGER_STATUSES" :key="s.key" :value="s.key">
                                            {{ t(`trip_detail.passengers.status.${s.i18n}`) }}
                                        </option>
                                    </select>
                                    <ChevronDownIcon class="pointer-events-none absolute right-1.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 opacity-60" aria-hidden="true" />
                                </div>
                            </td>

                            <!-- Board time -->
                            <td v-if="opsMode" class="w-24 px-3 py-2.5 align-top text-[11px] tabular-nums text-slate-500 dark:text-slate-400">
                                <span v-if="boardTimeLabel(item.row.passengerKey)">{{ boardTimeLabel(item.row.passengerKey) }}</span>
                                <span v-else class="text-slate-300 dark:text-slate-600">—</span>
                            </td>

                            <!-- Notes -->
                            <td class="min-w-0 px-3 py-2.5 align-top text-slate-600 dark:text-slate-300">
                                <template v-if="editingKey === item.row.passengerKey && editDraft">
                                    <div class="flex flex-wrap items-center gap-2" @click.stop>
                                        <input
                                            v-if="item.row.editMeta?.kind !== 'cargo'"
                                            v-model="editDraft.notes"
                                            type="text"
                                            class="min-w-[10rem] flex-1 rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                            :placeholder="t('trip_detail.passengers.ph_notes')"
                                        />
                                        <input
                                            v-else
                                            v-model="editDraft.item_notes"
                                            type="text"
                                            class="min-w-[10rem] flex-1 rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                                            :placeholder="t('trip_detail.passengers.ph_notes')"
                                        />
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-emerald-600 text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50"
                                            :disabled="listBusy"
                                            :aria-label="t('trip_detail.passengers.save')"
                                            @click="saveEdit(item.row)"
                                        >
                                            <CheckIcon class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                            :aria-label="t('trip_detail.passengers.cancel_edit')"
                                            :disabled="listBusy"
                                            @click="cancelEdit"
                                        >
                                            <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                    </div>
                                </template>
                                <div v-else class="flex flex-wrap items-center gap-1.5">
                                    <span v-if="item.row.flagWheelchair" role="img" :aria-label="t('trip_detail.passengers.flag_wheelchair')">
                                        <WheelchairGlyph class="h-5 w-5 text-rose-600" aria-hidden="true" />
                                    </span>
                                    <span v-if="item.row.flagAllergy" role="img" :aria-label="t('trip_detail.passengers.flag_allergy')">
                                        <ExclamationTriangleIcon class="h-5 w-5 text-amber-500" aria-hidden="true" />
                                    </span>
                                    <EmptyValue :value="item.row.notes" empty-key="trip_detail.empty.notes" />
                                </div>
                            </td>

                            <!-- Actions -->
                            <td v-if="showActionsCol" class="px-3 py-2.5 text-right align-top whitespace-nowrap" @click.stop>
                                <div v-if="editingKey !== item.row.passengerKey" class="flex items-center justify-end gap-1">
                                    <template v-if="opsMode">
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm transition hover:bg-emerald-100 disabled:opacity-40 dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-300"
                                            :disabled="busyKey === item.row.passengerKey || statusOf(item.row.passengerKey) === 'onboard'"
                                            :aria-label="t('trip_detail.passengers.action_onboard')"
                                            :title="t('trip_detail.passengers.action_onboard')"
                                            @click="applyStatus(item.row, 'onboard')"
                                        >
                                            <ArrowRightOnRectangleIcon class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-teal-200 bg-teal-50 text-teal-700 shadow-sm transition hover:bg-teal-100 disabled:opacity-40 dark:border-teal-800/50 dark:bg-teal-950/40 dark:text-teal-300"
                                            :disabled="busyKey === item.row.passengerKey || statusOf(item.row.passengerKey) === 'dropped_off'"
                                            :aria-label="t('trip_detail.passengers.action_dropoff')"
                                            :title="t('trip_detail.passengers.action_dropoff')"
                                            @click="applyStatus(item.row, 'dropped_off')"
                                        >
                                            <ArrowLeftOnRectangleIcon class="h-4 w-4" aria-hidden="true" />
                                        </button>
                                        <a
                                            v-if="telHref(item.row.contact)"
                                            :href="telHref(item.row.contact) || undefined"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                                            :aria-label="t('trip_detail.passengers.tel_call')"
                                            :title="t('trip_detail.passengers.tel_call')"
                                        >
                                            <PhoneIcon class="h-4 w-4" aria-hidden="true" />
                                        </a>
                                    </template>
                                    <button
                                        v-if="item.row.editable && item.row.editMeta"
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 disabled:opacity-40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                                        :disabled="listBusy"
                                        :aria-label="t('trip_detail.passengers.dt_edit')"
                                        :title="t('trip_detail.passengers.dt_edit')"
                                        @click="startEdit(item.row)"
                                    >
                                        <PencilSquareIcon class="h-4 w-4" aria-hidden="true" />
                                    </button>
                                    <button
                                        v-if="item.row.editable && item.row.editMeta"
                                        type="button"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200/90 bg-white text-rose-600 shadow-sm transition hover:border-rose-200 hover:bg-rose-50 disabled:opacity-40 dark:border-slate-600 dark:bg-slate-900 dark:text-rose-400"
                                        :disabled="listBusy"
                                        :aria-label="t('trip_detail.passengers.dt_delete')"
                                        :title="t('trip_detail.passengers.dt_delete')"
                                        @click="deleteRow(item.row)"
                                    >
                                        <TrashIcon class="h-4 w-4" aria-hidden="true" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div v-else class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">
                {{ t("trip_detail.passengers.dt_empty_table") }}
            </div>

            <!-- Pagination (table view only) -->
            <div
                v-if="rows.length && filteredRows.length > 0 && viewMode === 'table'"
                class="flex flex-col gap-2 border-t border-slate-100 px-3 py-2.5 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800 dark:text-slate-400"
            >
                <div class="tabular-nums">
                    {{ t("trip_detail.passengers.dt_showing", { from: showFrom, to: showTo, total: totalFiltered }) }}
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                        :disabled="page <= 1"
                        @click="page--"
                    >
                        {{ t("trip_detail.passengers.dt_prev") }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
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
            <div class="text-xs font-bold uppercase tracking-wide text-sky-900/90 dark:text-sky-200">
                {{ t("trip_detail.passengers.special_summary_title") }}
            </div>
            <p class="mt-1 whitespace-pre-wrap">{{ specialSummary }}</p>
        </div>

        <!-- DETAIL DRAWER -->
        <Teleport to="body">
            <div v-if="drawerRow" class="fixed inset-0 z-[110]" role="dialog" aria-modal="true">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[1px]" @click="closeDrawer" />
                <aside
                    class="absolute right-0 top-0 flex h-full w-full max-w-full flex-col bg-white shadow-2xl sm:w-[420px] dark:bg-slate-900"
                >
                    <header class="flex items-start justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-700">
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold" :class="avatarClass(drawerRow)">
                                {{ initials(drawerRow.name) }}
                            </div>
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-slate-900 dark:text-slate-100">{{ drawerRow.name }}</div>
                                <span class="mt-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="passengerStatusMeta(statusOf(drawerRow.passengerKey)).badge">
                                    {{ t(`trip_detail.passengers.status.${passengerStatusMeta(statusOf(drawerRow.passengerKey)).i18n}`) }}
                                </span>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                            :aria-label="t('trip_detail.passengers.drawer.close')"
                            @click="closeDrawer"
                        >
                            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                    </header>

                    <div class="flex-1 space-y-5 overflow-y-auto p-4">
                        <!-- Quick status actions -->
                        <div v-if="opsMode" class="flex flex-wrap gap-1.5">
                            <button
                                v-for="s in PASSENGER_STATUSES"
                                :key="s.key"
                                type="button"
                                class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition disabled:opacity-50"
                                :class="
                                    statusOf(drawerRow.passengerKey) === s.key
                                        ? s.chipActive
                                        : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200'
                                "
                                :disabled="busyKey === drawerRow.passengerKey"
                                @click="applyStatus(drawerRow, s.key)"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="s.dot" />
                                {{ t(`trip_detail.passengers.status.${s.i18n}`) }}
                            </button>
                        </div>

                        <section>
                            <h4 class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ t("trip_detail.passengers.drawer.section_contact") }}</h4>
                            <dl class="space-y-2 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-500 dark:text-slate-400">{{ t("trip_detail.passengers.col_contact") }}</dt>
                                    <dd class="min-w-0 text-right">
                                        <a v-if="telHref(drawerRow.contact)" :href="telHref(drawerRow.contact) || undefined" class="inline-flex items-center gap-1 text-sky-700 hover:underline dark:text-sky-400">
                                            <PhoneIcon class="h-3.5 w-3.5" aria-hidden="true" />{{ drawerRow.contact }}
                                        </a>
                                        <EmptyValue v-else :value="drawerRow.contact" empty-key="trip_detail.empty.contact" />
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section>
                            <h4 class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ t("trip_detail.passengers.drawer.section_travel") }}</h4>
                            <dl class="space-y-2 text-sm">
                                <div v-if="hasLegs" class="flex items-start justify-between gap-3">
                                    <dt class="shrink-0 text-slate-500 dark:text-slate-400">{{ t("trip_detail.passengers.col_leg") }}</dt>
                                    <dd class="min-w-0 text-right text-slate-700 dark:text-slate-200">
                                        <span v-if="drawerRow.legLabel">{{ drawerRow.legLabel }}</span>
                                        <span v-else class="italic text-slate-400 dark:text-slate-500">{{ t("trip_detail.passengers.leg_unassigned") }}</span>
                                    </dd>
                                </div>
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="shrink-0 text-slate-500 dark:text-slate-400">{{ t("trip_detail.passengers.col_pickup") }}</dt>
                                    <dd class="min-w-0 text-right text-slate-700 dark:text-slate-200">
                                        <EmptyValue :value="drawerRow.pickupAddress" empty-key="trip_detail.empty.address" />
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-500 dark:text-slate-400">{{ t("trip_detail.passengers.drawer.field_board_time") }}</dt>
                                    <dd class="text-right tabular-nums text-slate-700 dark:text-slate-200">
                                        {{ boardTimeLabel(drawerRow.passengerKey) || "—" }}
                                    </dd>
                                </div>
                                <div v-if="dropoffTimeLabel(drawerRow.passengerKey)" class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-500 dark:text-slate-400">{{ t("trip_detail.passengers.drawer.field_dropoff_time") }}</dt>
                                    <dd class="text-right tabular-nums text-slate-700 dark:text-slate-200">
                                        {{ dropoffTimeLabel(drawerRow.passengerKey) }}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section v-if="drawerRow.notes || drawerRow.flagWheelchair || drawerRow.flagAllergy">
                            <h4 class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ t("trip_detail.passengers.drawer.section_other") }}</h4>
                            <div class="flex flex-wrap gap-1.5">
                                <span v-if="drawerRow.flagWheelchair" class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-800 dark:bg-rose-950/50 dark:text-rose-200">
                                    {{ t("trip_detail.passengers.flag_wheelchair") }}
                                </span>
                                <span v-if="drawerRow.flagAllergy" class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-900 dark:bg-amber-950/50 dark:text-amber-200">
                                    {{ t("trip_detail.passengers.flag_allergy") }}
                                </span>
                            </div>
                            <p v-if="drawerRow.notes" class="mt-2 whitespace-pre-wrap text-sm text-slate-700 dark:text-slate-200">{{ drawerRow.notes }}</p>
                        </section>
                    </div>

                    <footer v-if="drawerRow.editable && drawerRow.editMeta" class="border-t border-slate-200 p-3 dark:border-slate-700">
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
                            @click="editFromDrawer(drawerRow)"
                        >
                            <PencilSquareIcon class="h-4 w-4" aria-hidden="true" />
                            {{ t("trip_detail.passengers.dt_edit") }}
                        </button>
                    </footer>
                </aside>
            </div>
        </Teleport>

        <!-- ADD MODAL -->
        <Teleport to="body">
            <div
                v-if="addModalOpen"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[1px]"
                role="dialog"
                aria-modal="true"
                @click.self="closeAddModal"
            >
                <div class="w-full max-w-lg rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                        {{ t("trip_detail.passengers.add_modal_title") }}
                    </h3>
                    <p v-if="listKind === 'passenger'" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t("trip_detail.passengers.one_row_one_passenger_hint") }}
                    </p>

                    <div v-if="listKind === 'passenger'" class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.col_name") }}</span>
                            <input v-model="addForm.person_in_charge" type="text" class="inp" :placeholder="t('trip_detail.passengers.ph_name')" />
                        </label>
                        <div class="grid grid-cols-1 gap-3 sm:col-span-2 sm:grid-cols-2">
                            <label class="block min-w-0">
                                <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_depart_at") }}</span>
                                <input v-model="addForm.depart_at" type="datetime-local" class="inp min-w-0" />
                            </label>
                            <label class="block min-w-0">
                                <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_return_at") }}</span>
                                <input v-model="addForm.return_at" type="datetime-local" class="inp min-w-0" />
                            </label>
                        </div>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_pickup") }}</span>
                            <input v-model="addForm.pickup" type="text" class="inp" :placeholder="t('trip_detail.passengers.ph_pickup')" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_dropoff") }}</span>
                            <input v-model="addForm.dropoff" type="text" class="inp" :placeholder="t('trip_detail.passengers.ph_dropoff')" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_unit_price") }}</span>
                            <input v-model="addForm.unit_price" type="text" class="inp" :placeholder="t('trip_detail.passengers.ph_unit_price')" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_extra_fee") }}</span>
                            <input v-model="addForm.extra_fee" type="text" class="inp" :placeholder="t('trip_detail.passengers.ph_extra_fee')" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.col_notes") }}</span>
                            <input v-model="addForm.notes" type="text" class="inp" :placeholder="t('trip_detail.passengers.ph_notes')" />
                        </label>
                    </div>

                    <div v-else-if="listKind === 'business'" class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="grid grid-cols-1 gap-3 sm:col-span-2 sm:grid-cols-2">
                            <label class="block min-w-0">
                                <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_depart_at") }}</span>
                                <input v-model="addForm.depart_at" type="datetime-local" class="inp min-w-0" />
                            </label>
                            <label class="block min-w-0">
                                <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_return_at") }}</span>
                                <input v-model="addForm.return_at" type="datetime-local" class="inp min-w-0" />
                            </label>
                        </div>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_pickup") }}</span>
                            <input v-model="addForm.pickup" type="text" class="inp" :placeholder="t('trip_detail.passengers.ph_pickup')" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_waypoint") }}</span>
                            <input v-model="addForm.waypoint" type="text" class="inp" :placeholder="t('trip_detail.passengers.ph_waypoint')" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_dropoff") }}</span>
                            <input v-model="addForm.dropoff" type="text" class="inp" :placeholder="t('trip_detail.passengers.ph_dropoff')" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_unit_price") }}</span>
                            <input v-model="addForm.unit_price" type="text" class="inp" :placeholder="t('trip_detail.passengers.ph_unit_price')" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_extra_fee") }}</span>
                            <input v-model="addForm.extra_fee" type="text" class="inp" :placeholder="t('trip_detail.passengers.ph_extra_fee')" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.col_notes") }}</span>
                            <input v-model="addForm.notes" type="text" class="inp" :placeholder="t('trip_detail.passengers.ph_notes')" />
                        </label>
                    </div>

                    <div v-else class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.col_name") }}</span>
                            <input v-model="addForm.name" type="text" class="inp" :placeholder="t('trip_detail.passengers.ph_cargo_name')" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.col_qty") }}</span>
                            <input v-model="addForm.qty" type="number" min="1" step="1" class="inp" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_cost") }}</span>
                            <input v-model="addForm.cost" type="text" class="inp" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_dimensions") }}</span>
                            <input v-model="addForm.dimensions" type="text" class="inp" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_weight") }}</span>
                            <input v-model="addForm.weight" type="text" class="inp" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.col_notes") }}</span>
                            <input v-model="addForm.item_notes" type="text" class="inp" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_pickup_at") }}</span>
                            <input v-model="addForm.pickup_at" type="datetime-local" class="inp" />
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_delivery_at") }}</span>
                            <input v-model="addForm.delivery_at" type="datetime-local" class="inp" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_pickup_place") }}</span>
                            <input v-model="addForm.pickup_place" type="text" class="inp" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_delivery_place") }}</span>
                            <input v-model="addForm.delivery_place" type="text" class="inp" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.ph_pickup_contact") }}</span>
                            <input v-model="addForm.pickup_contact" type="text" class="inp" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.ph_delivery_contact") }}</span>
                            <input v-model="addForm.delivery_contact" type="text" class="inp" />
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ t("trip_detail.passengers.m_transport_note") }}</span>
                            <input v-model="addForm.transport_note" type="text" class="inp" />
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
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import {
    ArrowLeftOnRectangleIcon,
    ArrowRightOnRectangleIcon,
    CheckIcon,
    ChevronDownIcon,
    PencilSquareIcon,
    PhoneIcon,
    TrashIcon,
    UserPlusIcon,
    XMarkIcon,
} from "@heroicons/vue/24/outline";
import { ExclamationTriangleIcon } from "@heroicons/vue/24/solid";
import DatagridToolbarSearch from "../shared/ui/DatagridToolbarSearch.vue";
import DatagridToolbarActionButton from "../shared/ui/DatagridToolbarActionButton.vue";
import DatagridFilterField from "../shared/ui/DatagridFilterField.vue";
import FilterVisibilityDropdown from "../shared/ui/FilterVisibilityDropdown.vue";
import { useVisibleFilterControls } from "../../composables/useVisibleFilterControls.js";
import {
    emptyPassengerRow,
    emptyBusinessRow,
    emptyCargoRow,
    isPassengerRowFilled,
    isBusinessRowFilled,
    isCargoRowFilled,
} from "../../composables/dispatchWizardConstants";
import { showAppError } from "../../composables/appMessage";
import {
    PASSENGER_STATUSES,
    passengerStatusMeta,
    readPassengerStatus,
    setPassengerStatusApi,
    bulkSetPassengerStatusApi,
    type PassengerStatusKey,
} from "../../composables/usePassengerStatus";
import { confirmAction } from "../../composables/useConfirm";
import EmptyValue from "../ui/EmptyValue.vue";
import { isEmptyDisplay } from "../../util/displayValue";

const WheelchairGlyph = {
    name: "WheelchairGlyph",
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm7.94 14.13-1.39-3.47A2 2 0 0 0 16.67 16H13v-2.34c1.81.34 3.72-.37 4.92-2.02l1.14-1.59a1 1 0 0 0-1.62-1.16l-1.15 1.6c-.72 1-1.86 1.51-3.03 1.51h-.61a1 1 0 0 0-.98.8l-2.2 11a1 1 0 1 0 1.96.39l2.03-10.19H16a4 4 0 0 1 3.89 3.05l1.39 3.47a1 1 0 1 0 1.86-.73ZM7 12a5 5 0 1 0 5 5 5 5 0 0 0-5-5Zm0 8a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg>',
};

export type PassengerEditMeta = {
    kind: "passenger" | "business" | "cargo" | "named_tp";
    rowIndex: number;
    businessRowIndex?: number;
};

export type PassengerRow = {
    passengerKey: string;
    name: string;
    roleKind: string;
    roleLabel: string;
    contact: string;
    notes: string;
    pickupAddress?: string;
    legKey?: string;
    legLabel?: string;
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
    /** Các chặng để gán hành khách (chỉ chuyến nhiều chặng). */
    legs?: { key: string; label: string }[];
    /** Số khách thống nhất (yêu cầu / lịch trình). */
    requestPassengerCount?: number;
    canCheckIn: boolean;
    canEditList: boolean;
    specialSummary?: string;
}>();

const emit = defineEmits<{
    "trip-updated": [trip: Record<string, unknown>];
    "lock-conflict": [];
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

const FILTER_CONTROL_CLASS =
    "input h-10 w-full rounded-lg border border-slate-200 bg-white text-sm text-slate-900 shadow-sm outline-none focus:border-va-700 focus:ring-2 focus:ring-va-700/15 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100";

const PASSENGER_FILTER_CONTROLS = [
    { key: "status", label: t("trip_detail.passengers.col_status"), default: false },
    {
        key: "page_size",
        label: t("trip_detail.passengers.filter_page_size"),
        default: false,
    },
];

const {
    visibleFilters,
    hasFilterRow,
    showFilterPanelDd,
    openFilterPanel,
    closeFilterPanel,
    filterControlDefs,
} = useVisibleFilterControls(PASSENGER_FILTER_CONTROLS, "trip-detail-passengers-filter.v1");

/** Trung tâm điều hành (KPI/trạng thái/bulk/drawer) chỉ bật khi cho phép check-in. */
const opsMode = computed(() => props.canCheckIn);

/** Có danh sách chặng để gán/hiển thị (chuyến nhiều chặng). */
const legOptions = computed(() => props.legs ?? []);
const hasLegs = computed(() => legOptions.value.length > 0);
const NO_LEG_KEY = "__no_leg__";

const requestPassengerCount = computed(() => props.requestPassengerCount ?? 0);

const displayPassengerTotal = computed(() => {
    const req = requestPassengerCount.value;
    if (req > 0) return req;
    return props.rows.length;
});

type StatusFilterKey = "all" | PassengerStatusKey;

const statusFilter = ref<StatusFilterKey>("all");

const statusFilterSelect = computed({
    get: () => (statusFilter.value === "all" ? "" : statusFilter.value),
    set: (v: string) => {
        statusFilter.value = v === "" ? "all" : (v as PassengerStatusKey);
    },
});

const passengerSearch = ref("");
const page = ref(1);
const pageSize = ref(10);
const viewMode = ref<"table" | "status" | "pickup" | "leg">("table");
const drawerKey = ref<string | null>(null);

const busyKey = ref<string | null>(null);
const bulkBusy = ref(false);
const statusLocal = ref<Record<string, PassengerStatusKey>>({});
const timesLocal = ref<
    Record<string, { checked_in_at?: string; dropped_off_at?: string }>
>({});
const selected = ref<Record<string, boolean>>({});

const editingKey = ref<string | null>(null);
const editDraft = ref<Record<string, string> | null>(null);
const listBusy = ref(false);
const addModalOpen = ref(false);
const addForm = ref<Record<string, string>>({});

function statusOf(key: string): PassengerStatusKey {
    return statusLocal.value[key] ?? "pending";
}

function syncFromTrip() {
    const m = props.trip?.passenger_check_ins as
        | Record<string, { status?: string; checked_in_at?: string; dropped_off_at?: string }>
        | undefined;
    const st: Record<string, PassengerStatusKey> = {};
    const tm: Record<string, { checked_in_at?: string; dropped_off_at?: string }> = {};
    if (m && typeof m === "object") {
        for (const row of props.rows) {
            const cell = m[row.passengerKey];
            if (cell && typeof cell === "object") {
                st[row.passengerKey] = readPassengerStatus(cell);
                tm[row.passengerKey] = {
                    checked_in_at: cell.checked_in_at,
                    dropped_off_at: cell.dropped_off_at,
                };
            }
        }
    }
    statusLocal.value = st;
    timesLocal.value = tm;
}

watch(
    () => [props.trip?.passenger_check_ins, props.rows],
    () => syncFromTrip(),
    { deep: true, immediate: true },
);

watch(
    () => props.rows,
    () => {
        if (editingKey.value && !props.rows.some((r) => r.passengerKey === editingKey.value)) {
            editingKey.value = null;
            editDraft.value = null;
        }
        // Bỏ chọn các key không còn tồn tại.
        const valid = new Set(props.rows.map((r) => r.passengerKey));
        const next: Record<string, boolean> = {};
        for (const k of Object.keys(selected.value)) {
            if (valid.has(k) && selected.value[k]) next[k] = true;
        }
        selected.value = next;
        if (drawerKey.value && !valid.has(drawerKey.value)) drawerKey.value = null;
    },
    { deep: true },
);

watch([passengerSearch, statusFilter, viewMode], () => {
    page.value = 1;
});

watch([() => props.rows.length, pageSize], () => {
    const maxPage = Math.max(1, Math.ceil(filteredRows.value.length / pageSize.value) || 1);
    if (page.value > maxPage) page.value = maxPage;
});

const tableScroll = computed(() => props.rows.length >= 10 || viewMode.value !== "table");

const listKind = computed((): "passenger" | "business" | "cargo" => {
    const dr = props.trip?.dispatch_request as { trip_type?: string } | undefined;
    const tt = String(dr?.trip_type ?? "");
    if (tt === "cargo") return "cargo";
    if (tt === "business") return "business";
    return "passenger";
});

const showActionsCol = computed(() => opsMode.value || props.canEditList);

/* ── Counts / KPI / progress ─────────────────────────────────────────── */

const statusCounts = computed(() => {
    const c: Record<PassengerStatusKey, number> = {
        pending: 0,
        confirmed: 0,
        onboard: 0,
        dropped_off: 0,
        absent: 0,
        cancelled: 0,
    };
    for (const r of props.rows) c[statusOf(r.passengerKey)]++;
    return c;
});

/** Đã lên xe = onboard + đã xuống (đã từng lên). */
const boardedCount = computed(
    () => statusCounts.value.onboard + statusCounts.value.dropped_off,
);
const boardedPct = computed(() => {
    const total = props.rows.length;
    if (!total) return 0;
    return Math.round((boardedCount.value / total) * 100);
});

type ViewModeKey = "table" | "status" | "pickup" | "leg";
const viewModes = computed(() => {
    const modes: { key: ViewModeKey; label: string }[] = [
        { key: "table", label: t("trip_detail.passengers.view.table") },
        { key: "status", label: t("trip_detail.passengers.view.status") },
        { key: "pickup", label: t("trip_detail.passengers.view.pickup") },
    ];
    if (hasLegs.value) {
        modes.push({ key: "leg", label: t("trip_detail.passengers.view.leg") });
    }
    return modes;
});

const bulkStatusActions = computed(() =>
    (["onboard", "dropped_off", "absent"] as PassengerStatusKey[]).map((status) => {
        const meta = passengerStatusMeta(status);
        return {
            status,
            dot: meta.dot,
            label: t(`trip_detail.passengers.status.${meta.i18n}`),
        };
    }),
);

/* ── Filtering / pagination ──────────────────────────────────────────── */

const rowsFilteredByStatus = computed(() => {
    const all = props.rows ?? [];
    if (!opsMode.value || statusFilter.value === "all") return all;
    return all.filter((r) => statusOf(r.passengerKey) === statusFilter.value);
});

const filteredRows = computed(() => {
    const base = rowsFilteredByStatus.value;
    const q = passengerSearch.value.trim().toLowerCase();
    if (!q) return base;
    return base.filter((row) =>
        [row.name, row.contact, row.notes, row.pickupAddress, row.legLabel]
            .join(" ")
            .toLowerCase()
            .includes(q),
    );
});

const totalFiltered = computed(() => filteredRows.value.length);
const totalPages = computed(() =>
    Math.max(1, Math.ceil(totalFiltered.value / pageSize.value) || 1),
);
const pageStart = computed(() => (page.value - 1) * pageSize.value);
const paginatedRows = computed(() =>
    filteredRows.value.slice(pageStart.value, pageStart.value + pageSize.value),
);
const showFrom = computed(() => (totalFiltered.value === 0 ? 0 : pageStart.value + 1));
const showTo = computed(() =>
    Math.min(pageStart.value + pageSize.value, totalFiltered.value),
);

type RenderItem =
    | { type: "group"; key: string; label: string; count: number; dot?: string }
    | { type: "row"; key: string; row: PassengerRow };

const renderItems = computed((): RenderItem[] => {
    // Table view: phân trang phẳng.
    if (viewMode.value === "table") {
        return paginatedRows.value.map((row) => ({
            type: "row",
            key: row.passengerKey,
            row,
        }));
    }

    // Grouping views: gom nhóm, không phân trang.
    const groups = new Map<string, { label: string; dot?: string; rows: PassengerRow[] }>();
    const ensure = (gk: string, label: string, dot?: string) => {
        if (!groups.has(gk)) groups.set(gk, { label, dot, rows: [] });
        return groups.get(gk)!;
    };

    if (viewMode.value === "status") {
        for (const s of PASSENGER_STATUSES) {
            ensure(s.key, t(`trip_detail.passengers.status.${s.i18n}`), s.dot);
        }
        for (const row of filteredRows.value) {
            const s = statusOf(row.passengerKey);
            ensure(s, t(`trip_detail.passengers.status.${s}`)).rows.push(row);
        }
    } else if (viewMode.value === "leg") {
        for (const opt of legOptions.value) ensure(opt.key, opt.label);
        for (const row of filteredRows.value) {
            const key = (row.legKey ?? "").trim();
            if (key) {
                ensure(key, row.legLabel || key).rows.push(row);
            } else {
                ensure(NO_LEG_KEY, t("trip_detail.passengers.leg_unassigned")).rows.push(row);
            }
        }
    } else {
        for (const row of filteredRows.value) {
            const raw = (row.pickupAddress ?? "").trim();
            const label = raw && !isEmptyDisplay(raw) ? raw : t("trip_detail.passengers.group_no_pickup");
            ensure(label.toLowerCase(), label).rows.push(row);
        }
    }

    const items: RenderItem[] = [];
    for (const [gk, g] of groups) {
        if (!g.rows.length) continue;
        items.push({ type: "group", key: `g_${gk}`, label: g.label, count: g.rows.length, dot: g.dot });
        for (const row of g.rows) items.push({ type: "row", key: row.passengerKey, row });
    }
    return items;
});

const tableColSpan = computed(() => {
    let n = 3; // name, contact, notes
    if (hasLegs.value) n += 1; // chặng
    if (opsMode.value) n += 4; // select, pickup, status, board-time
    if (showActionsCol.value) n += 1;
    return n;
});

/* ── Selection / bulk ────────────────────────────────────────────────── */

const selectedKeys = computed(() => Object.keys(selected.value).filter((k) => selected.value[k]));
const allVisibleSelected = computed(() => {
    const visible = filteredRows.value;
    return visible.length > 0 && visible.every((r) => selected.value[r.passengerKey]);
});
const someVisibleSelected = computed(() =>
    filteredRows.value.some((r) => selected.value[r.passengerKey]),
);

function toggleSelect(key: string) {
    selected.value = { ...selected.value, [key]: !selected.value[key] };
}
function toggleSelectAll() {
    if (allVisibleSelected.value) {
        const next = { ...selected.value };
        for (const r of filteredRows.value) delete next[r.passengerKey];
        selected.value = next;
    } else {
        const next = { ...selected.value };
        for (const r of filteredRows.value) next[r.passengerKey] = true;
        selected.value = next;
    }
}
function clearSelection() {
    selected.value = {};
}

/* ── Drawer ──────────────────────────────────────────────────────────── */

const drawerRow = computed(
    () => props.rows.find((r) => r.passengerKey === drawerKey.value) ?? null,
);
function openDrawer(row: PassengerRow) {
    drawerKey.value = row.passengerKey;
}
function closeDrawer() {
    drawerKey.value = null;
}
function editFromDrawer(row: PassengerRow) {
    closeDrawer();
    startEdit(row);
}

/* ── Row visuals ─────────────────────────────────────────────────────── */

function rowClass(row: PassengerRow): string {
    const classes: string[] = [];
    if (editingKey.value === row.passengerKey) classes.push("bg-sky-50/50 dark:bg-sky-950/20");
    else if (selected.value[row.passengerKey]) classes.push("bg-blue-50/60 dark:bg-blue-950/25");
    else {
        const s = statusOf(row.passengerKey);
        if (s === "onboard" || s === "dropped_off") classes.push("bg-emerald-50/50 dark:bg-emerald-950/15");
        else if (s === "absent") classes.push("bg-amber-50/40 dark:bg-amber-950/15");
        else if (s === "cancelled") classes.push("opacity-60");
    }
    return classes.join(" ");
}

function avatarClass(row: PassengerRow): string {
    const s = statusOf(row.passengerKey);
    const meta = passengerStatusMeta(s);
    if (s === "pending") return "bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200";
    return meta.badge;
}

function initials(name: string) {
    const n = String(name ?? "").trim();
    if (!n || isEmptyDisplay(n)) return "?";
    const parts = n.split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function telHref(contact: string) {
    const raw = String(contact ?? "").trim();
    if (isEmptyDisplay(raw)) return "";
    const digits = raw.replace(/[^\d+]/g, "");
    if (digits.length < 8) return "";
    return `tel:${digits}`;
}

function fmtTime(iso?: string) {
    if (!iso) return "";
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return "";
    return d.toLocaleTimeString(undefined, { hour: "2-digit", minute: "2-digit" });
}
function boardTimeLabel(key: string) {
    return fmtTime(timesLocal.value[key]?.checked_in_at);
}
function dropoffTimeLabel(key: string) {
    return fmtTime(timesLocal.value[key]?.dropped_off_at);
}

/* ── Row click ───────────────────────────────────────────────────────── */

function onRowContentClick(row: PassengerRow) {
    if (listBusy.value || editingKey.value === row.passengerKey) return;
    if (opsMode.value) {
        openDrawer(row);
        return;
    }
    if (row.editable && row.editMeta) startEdit(row);
}

/* ── Status mutations ────────────────────────────────────────────────── */

function onStatusSelect(row: PassengerRow, ev: Event) {
    const next = (ev.target as HTMLSelectElement).value as PassengerStatusKey;
    applyStatus(row, next);
}

async function applyStatus(row: PassengerRow, status: PassengerStatusKey) {
    if (!opsMode.value) return;
    const key = row.passengerKey;
    const prevStatus = statusOf(key);
    const prevTimes = timesLocal.value[key];
    if (prevStatus === status) return;

    // Optimistic.
    statusLocal.value = { ...statusLocal.value, [key]: status };
    const nowIso = new Date().toISOString();
    if (status === "onboard") {
        timesLocal.value = {
            ...timesLocal.value,
            [key]: { ...prevTimes, checked_in_at: prevTimes?.checked_in_at ?? nowIso },
        };
    } else if (status === "dropped_off") {
        timesLocal.value = {
            ...timesLocal.value,
            [key]: {
                checked_in_at: prevTimes?.checked_in_at ?? nowIso,
                dropped_off_at: nowIso,
            },
        };
    }

    busyKey.value = key;
    const res = await setPassengerStatusApi({
        tripId: props.tripId,
        passengerKey: key,
        status,
        lockVersion: Number(props.trip?.lock_version ?? 0),
        tripSnapshot: props.trip ?? null,
        errorMessage: t("trip_detail.passengers.status_update_error"),
    });
    busyKey.value = null;

    if (!res.ok) {
        statusLocal.value = { ...statusLocal.value, [key]: prevStatus };
        timesLocal.value = { ...timesLocal.value, [key]: prevTimes ?? {} };
        if (res.lockConflict) emit("lock-conflict");
        return;
    }
    if (res.data) emit("trip-updated", res.data);
}

async function applyBulkStatus(status: PassengerStatusKey) {
    const keys = selectedKeys.value;
    if (!keys.length) return;
    bulkBusy.value = true;
    const res = await bulkSetPassengerStatusApi({
        tripId: props.tripId,
        keys,
        status,
        lockVersion: Number(props.trip?.lock_version ?? 0),
        tripSnapshot: props.trip ?? null,
        errorMessage: t("trip_detail.passengers.status_update_error"),
    });
    bulkBusy.value = false;

    if (!res.ok) {
        if (res.lockConflict) emit("lock-conflict");
        return;
    }
    clearSelection();
    if (res.data) emit("trip-updated", res.data);
}

/* ── List edit / add / delete (legacy, unchanged behavior) ───────────── */

function startEdit(row: PassengerRow) {
    if (!row.editFields || !row.editMeta) return;
    closeDrawer();
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
            }
        },
    });
}

function openAddModal() {
    if (listKind.value === "cargo") addForm.value = { ...emptyCargoRow() };
    else if (listKind.value === "business") addForm.value = { ...emptyBusinessRow() };
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
    else if (listKind.value === "business") filled = isBusinessRowFilled(draft as never);
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

/* ── CSV export ──────────────────────────────────────────────────────── */

function csvEscapeCell(v: string): string {
    const s = String(v ?? "").replace(/"/g, '""');
    if (/[,"\n\r]/.test(s)) return `"${s}"`;
    return s;
}

function exportCsv(): void {
    const list = props.rows ?? [];
    if (!list.length) return;
    const head = [
        t("trip_detail.passengers.col_name"),
        ...(hasLegs.value ? [t("trip_detail.passengers.col_leg")] : []),
        t("trip_detail.passengers.col_contact"),
        t("trip_detail.passengers.col_notes"),
        t("trip_detail.passengers.csv_pickup"),
        t("trip_detail.passengers.col_status"),
        t("trip_detail.passengers.col_board_time"),
    ];
    const lines = [head.map(csvEscapeCell).join(",")];
    for (const row of list) {
        const s = statusOf(row.passengerKey);
        lines.push(
            [
                csvEscapeCell(row.name),
                ...(hasLegs.value ? [csvEscapeCell(row.legLabel ?? "")] : []),
                csvEscapeCell(row.contact),
                csvEscapeCell(row.notes),
                csvEscapeCell(row.pickupAddress ?? ""),
                csvEscapeCell(t(`trip_detail.passengers.status.${passengerStatusMeta(s).i18n}`)),
                csvEscapeCell(boardTimeLabel(row.passengerKey)),
            ].join(","),
        );
    }
    const blob = new Blob([`﻿${lines.join("\n")}`], {
        type: "text/csv;charset=utf-8;",
    });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `passengers-trip-${props.tripId}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<style scoped>
.inp {
    @apply w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm text-slate-900 shadow-sm outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-500/25 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100;
}
</style>
