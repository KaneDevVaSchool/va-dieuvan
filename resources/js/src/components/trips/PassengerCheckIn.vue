<template>
    <section
        class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm"
    >
        <div
            class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex flex-wrap items-center gap-2">
                <h2
                    class="text-xs font-bold uppercase tracking-wide text-slate-500"
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

        <div class="mt-4 overflow-x-auto rounded-xl border border-slate-100">
            <slot name="editor" />
            <template v-if="!editMode">
                <table
                    v-if="rows.length"
                    class="min-w-full divide-y divide-slate-100 text-sm"
                >
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th
                                v-if="canCheckIn"
                                class="w-10 px-2 py-2.5 text-left text-xs font-semibold text-slate-600"
                            />
                            <th
                                class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600"
                            >
                                {{ t("trip_detail.passengers.col_name") }}
                            </th>
                            <th
                                class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600"
                            >
                                {{ t("trip_detail.passengers.col_role") }}
                            </th>
                            <th
                                class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600"
                            >
                                {{ t("trip_detail.passengers.col_contact") }}
                            </th>
                            <th
                                class="px-3 py-2.5 text-left text-xs font-semibold text-slate-600"
                            >
                                {{ t("trip_detail.passengers.col_notes") }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template v-for="row in rows" :key="row.passengerKey">
                            <tr
                                class="cursor-pointer transition"
                                :class="[
                                    rowExpanded === row.passengerKey
                                        ? 'bg-slate-50/80'
                                        : '',
                                    checkedLocal[row.passengerKey]
                                        ? 'bg-emerald-50/90'
                                        : '',
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
                                                class="font-medium text-slate-900"
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
                                    class="max-w-[160px] px-3 py-2.5 align-top"
                                    @click.stop
                                >
                                    <template v-if="telHref(row.contact)">
                                        <a
                                            :href="
                                                telHref(row.contact) ||
                                                undefined
                                            "
                                            class="text-sky-700 underline-offset-2 hover:underline"
                                        >
                                            {{ row.contact }}
                                        </a>
                                    </template>
                                    <span v-else class="text-slate-600">{{
                                        row.contact || "—"
                                    }}</span>
                                </td>
                                <td
                                    class="px-3 py-2.5 align-top text-slate-600"
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
                                    class="px-4 py-3 text-xs text-slate-700"
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
                    class="px-3 py-6 text-center text-sm text-slate-500"
                >
                    {{ t("trip_detail.passengers.empty") }}
                </div>
            </template>
        </div>

        <div
            v-if="specialSummary"
            class="mt-4 rounded-xl border border-sky-100 bg-sky-50/80 px-4 py-3 text-sm text-sky-950"
        >
            <div
                class="text-xs font-bold uppercase tracking-wide text-sky-800/80"
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

const checkedCount = computed(
    () => props.rows.filter((r) => checkedLocal.value[r.passengerKey]).length,
);

function initials(name: string) {
    const n = String(name ?? "").trim();
    if (!n || n === "—") return "?";
    const parts = n.split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function rolePillClass(kind: string) {
    if (kind === "staff") return "bg-slate-100 text-slate-800";
    if (kind === "student") return "bg-sky-50 text-sky-800";
    if (kind === "cargo") return "bg-amber-50 text-amber-900";
    return "bg-slate-50 text-slate-700";
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
