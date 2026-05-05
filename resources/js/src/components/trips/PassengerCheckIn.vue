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
                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-900"
                >
                    {{
                        t("trip_detail.passengers.checkin_header_badge", {
                            checked: checkedCount,
                            total: rows.length,
                        })
                    }}
                </span>
            </div>
            <div v-if="canEditList" class="flex flex-wrap items-center gap-2">
                <template v-if="!editMode">
                    <button
                        type="button"
                        class="rounded-lg border border-sky-200/80 bg-sky-50/80 px-3 py-1.5 text-sm font-semibold text-sky-800 transition hover:bg-sky-100 dark:border-sky-800/50 dark:bg-sky-950/40 dark:text-sky-200 dark:hover:bg-sky-950/70"
                        @click="$emit('start-edit')"
                    >
                        {{ t("trip_detail.passengers.edit_inline") }}
                    </button>
                </template>
            </div>
        </div>

        <p
            v-if="editMessage"
            class="mt-2 text-sm text-rose-600 dark:text-rose-400"
        >
            {{ editMessage }}
        </p>

        <div
            v-if="!editMode && rows.length >= 2"
            class="mt-3 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
        >
            <div
                v-if="canCheckIn && rows.length"
                class="flex flex-wrap gap-1.5"
                role="group"
                :aria-label="
                    t('trip_detail.passengers.filter_segment_aria')
                "
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
            <div
                class="flex min-w-[12rem] flex-1 flex-wrap items-center gap-2 sm:max-w-xl sm:justify-end"
            >
                <div class="relative min-h-9 w-full min-w-0 sm:max-w-md sm:flex-1">
                    <MagnifyingGlassIcon
                        class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        aria-hidden="true"
                    />
                    <input
                        v-model="passengerSearch"
                        type="search"
                        :aria-label="t('trip_detail.passengers.search_aria')"
                        autocomplete="off"
                        class="h-9 w-full rounded-lg border border-slate-200 bg-white pl-9 pr-9 text-sm text-slate-900 outline-none ring-blue-500/30 placeholder:text-slate-400 focus:border-blue-400 focus:ring-2 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500"
                        :placeholder="
                            t('trip_detail.passengers.search_ph')
                        "
                    />
                    <button
                        v-if="passengerSearch.trim()"
                        type="button"
                        class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                        :aria-label="
                            t('trip_detail.passengers.search_clear_aria')
                        "
                        @click="passengerSearch = ''"
                    >
                        <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        v-if="
                            passengerSearch.trim() &&
                            displayRows.length < rowsFilteredByStatus.length
                        "
                        class="text-xs tabular-nums text-slate-500 dark:text-slate-400"
                    >
                        {{
                            t("trip_detail.passengers.search_match", {
                                shown: displayRows.length,
                                total: rowsFilteredByStatus.length,
                            })
                        }}
                    </span>
                    <button
                        type="button"
                        class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                        :aria-label="
                            t('trip_detail.passengers.export_csv_aria')
                        "
                        @click="exportCsv"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4 shrink-0" />
                        {{
                            t("trip_detail.passengers.export_csv")
                        }}
                    </button>
                </div>
            </div>
        </div>

        <div
            class="mt-4 rounded-xl border border-slate-100 dark:border-slate-700/80"
            :class="
                passengerTableScroll && !editMode
                    ? 'max-h-[min(28rem,72vh)] overflow-auto overscroll-contain'
                    : 'overflow-x-auto'
            "
        >
            <slot name="editor" />
            <template v-if="!editMode">
                <div
                    v-if="rows.length && !displayRows.length"
                    class="bg-white px-3 py-10 text-center text-sm text-slate-500 dark:bg-slate-950/40 dark:text-slate-400"
                >
                    {{ t("trip_detail.passengers.search_empty") }}
                </div>
                <table
                    v-else-if="rows.length"
                    class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-700"
                >
                    <thead class="sticky top-0 z-10 bg-slate-50/95 shadow-sm backdrop-blur-sm dark:bg-slate-800/95 dark:shadow-slate-900/80">
                        <tr>
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-700 dark:bg-slate-950/40">
                        <template
                            v-for="row in displayRows"
                            :key="row.passengerKey"
                        >
                            <tr
                                class="cursor-pointer border-b border-slate-100 transition dark:border-slate-700/60"
                                :class="[
                                    rowExpanded === row.passengerKey
                                        ? 'bg-slate-50/80 dark:bg-slate-800/50'
                                        : '',
                                    checkedLocal[row.passengerKey]
                                        ? 'bg-emerald-50/90 dark:bg-emerald-950/25'
                                        : '',
                                    'hover:bg-slate-50/70 dark:hover:bg-slate-800/30',
                                ]"
                                @click.self="toggleExpand(row.passengerKey)"
                            >
                                <td
                                    v-if="canCheckIn"
                                    class="px-2 py-2.5 align-middle"
                                    @click.stop
                                >
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                        :checked="
                                            !!checkedLocal[row.passengerKey]
                                        "
                                        :disabled="
                                            checkingKey === row.passengerKey
                                        "
                                        @change="onToggleCheck(row)"
                                    />
                                </td>
                                <td
                                    class="px-3 py-2.5 align-top"
                                    @click="toggleExpand(row.passengerKey)"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-700"
                                        >
                                            {{ initials(row.name) }}
                                        </div>
                                        <div>
                                            <div
                                                class="font-medium text-slate-900 dark:text-slate-100"
                                            >
                                                {{ row.name }}
                                            </div>
                                            <div
                                                v-if="
                                                    checkedLocal[
                                                        row.passengerKey
                                                    ]
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
                                </td>
                                <td
                                    class="px-3 py-2.5 align-top"
                                    @click="toggleExpand(row.passengerKey)"
                                >
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="rolePillClass(row.roleKind)"
                                    >
                                        {{ row.roleLabel }}
                                    </span>
                                    <span
                                        v-if="checkedLocal[row.passengerKey]"
                                        class="ml-1 inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800"
                                    >
                                        {{
                                            t(
                                                "trip_detail.passengers.checkin_on_vehicle",
                                            )
                                        }}
                                    </span>
                                    <span
                                        v-else-if="canCheckIn"
                                        class="ml-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600"
                                    >
                                        {{
                                            t(
                                                "trip_detail.passengers.checkin_waiting",
                                            )
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="max-w-[220px] min-w-[9rem] px-3 py-2.5 align-top"
                                    @click.stop
                                >
                                    <template v-if="telHref(row.contact)">
                                        <a
                                            :href="
                                                telHref(row.contact) ||
                                                undefined
                                            "
                                            class="text-sky-700 underline-offset-2 hover:underline dark:text-sky-400"
                                        >
                                            {{ row.contact }}
                                        </a>
                                    </template>
                                    <span v-else class="text-slate-600">{{
                                        row.contact || "—"
                                    }}</span>
                                </td>
                                <td
                                    class="min-w-0 px-3 py-2.5 align-top text-slate-600 dark:text-slate-300"
                                    @click="toggleExpand(row.passengerKey)"
                                >
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
                                </td>
                            </tr>
                            <tr
                                v-if="rowExpanded === row.passengerKey"
                                class="bg-slate-50/60"
                            >
                                <td
                                    :colspan="canCheckIn ? 5 : 4"
                                    class="px-4 py-3 text-xs text-slate-700 dark:text-slate-200"
                                >
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        <div>
                                            <div
                                                class="font-semibold text-slate-500"
                                            >
                                                {{
                                                    t(
                                                        "trip_detail.passengers.checkin_expand_pickup",
                                                    )
                                                }}
                                            </div>
                                            <div class="mt-0.5">
                                                {{
                                                    row.pickupAddress?.trim() ||
                                                    "—"
                                                }}
                                            </div>
                                        </div>
                                        <div>
                                            <div
                                                class="font-semibold text-slate-500"
                                            >
                                                {{
                                                    t(
                                                        "trip_detail.passengers.checkin_private_note",
                                                    )
                                                }}
                                            </div>
                                            <div
                                                class="mt-0.5 whitespace-pre-wrap"
                                            >
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
                    v-if="!rows.length"
                    class="px-3 py-6 text-center text-sm text-slate-500 dark:text-slate-400"
                >
                    {{ t("trip_detail.passengers.empty") }}
                </div>
            </template>
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
    </section>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import {
    ArrowDownTrayIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
} from "@heroicons/vue/24/outline";
import { ExclamationTriangleIcon } from "@heroicons/vue/24/solid";
import { togglePassengerCheckInApi } from "../../composables/usePassengerCheckIn";

const WheelchairGlyph = {
    name: "WheelchairGlyph",
    template:
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 6a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm7.94 14.13-1.39-3.47A2 2 0 0 0 16.67 16H13v-2.34c1.81.34 3.72-.37 4.92-2.02l1.14-1.59a1 1 0 0 0-1.62-1.16l-1.15 1.6c-.72 1-1.86 1.51-3.03 1.51h-.61a1 1 0 0 0-.98.8l-2.2 11a1 1 0 1 0 1.96.39l2.03-10.19H16a4 4 0 0 1 3.89 3.05l1.39 3.47a1 1 0 1 0 1.86-.73ZM7 12a5 5 0 1 0 5 5 5 5 0 0 0-5-5Zm0 8a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg>',
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
};

const props = defineProps<{
    tripId: number;
    trip: Record<string, unknown> | null;
    rows: PassengerRow[];
    canCheckIn: boolean;
    canEditList: boolean;
    editMode?: boolean;
    editMessage?: string;
    specialSummary?: string;
}>();

const emit = defineEmits<{
    "start-edit": [];
    "trip-updated": [trip: Record<string, unknown>];
}>();

const { t } = useI18n();

type StatusFilterKey = "all" | "waiting" | "onboard";

const statusFilter = ref<StatusFilterKey>("all");
const passengerSearch = ref("");
const rowExpanded = ref<string | null>(null);
const checkingKey = ref<string | null>(null);
const checkedLocal = ref<Record<string, boolean>>({});
const checkedAtLocal = ref<Record<string, string>>({});

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
    () => props.editMode,
    (on) => {
        if (on) {
            passengerSearch.value = "";
            statusFilter.value = "all";
        }
    },
);

watch([passengerSearch, statusFilter], () => {
    rowExpanded.value = null;
});

const passengerTableScroll = computed(
    () => props.rows.length >= 10 && !props.editMode,
);

const waitingCount = computed(
    () =>
        props.rows.filter((r) => !checkedLocal.value[r.passengerKey]).length,
);

const onboardCount = computed(
    () =>
        props.rows.filter((r) => !!checkedLocal.value[r.passengerKey]).length,
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

const displayRows = computed(() => {
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

function initials(name: string) {
    const n = String(name ?? "").trim();
    if (!n || n === "—") return "?";
    const parts = n.split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function rolePillClass(kind: string) {
    if (kind === "staff")
        return "bg-slate-100 text-slate-800 dark:bg-slate-700/70 dark:text-slate-100";
    if (kind === "student")
        return "bg-sky-50 text-sky-800 dark:bg-sky-950/55 dark:text-sky-200";
    if (kind === "cargo")
        return "bg-amber-50 text-amber-900 dark:bg-amber-950/45 dark:text-amber-100";
    return "bg-slate-50 text-slate-700 dark:bg-slate-800 dark:text-slate-200";
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
