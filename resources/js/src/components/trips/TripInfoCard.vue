<script setup lang="ts">
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import {
    CalendarDaysIcon,
    MapIcon,
} from "@heroicons/vue/24/outline";
import { isEmptyDisplay } from "../../util/displayValue";

type Step = { state: string; label: string };

type SlaBanner = { kind: "overdue" | "ok"; text: string } | null;

type ScheduleCard = {
    depart_at?: string | null;
    arrive_by?: string | null;
    pickup?: string;
    dropoff?: string;
    labelSeq?: number;
} | null;

const props = defineProps<{
    trip: Record<string, any>;
    passengerCount: number;
    scheduleDateLong: string;
    scheduleDepartTime: string;
    scheduleArriveTime: string;
    scheduleArriveDateShort: string;
    scheduleTimeRange: string;
    scheduleDuration: string;
    scheduleMismatchNotes: string[];
    estimatedDistanceLabel: string;
    estimatedCostLabel: string;
    scheduleCard: ScheduleCard;
    scheduleLegCount: number;
    requesterInitials: string;
    requesterName: string;
    requesterSubtitle: string;
    tripTypeLabel: string;
    slaBanner: SlaBanner;
    stepPickup: Step;
    stepDropoff: Step;
    originLabel: string;
    destinationLabel: string;
}>();

const { t, locale } = useI18n();

const localeTag = computed(() =>
    locale.value === "en" ? "en-US" : "vi-VN",
);

function fmtTime(v: string | null | undefined) {
    if (!v) return "";
    return new Date(v).toLocaleTimeString(localeTag.value, {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    });
}

function fmtDateLongFromIso(v: string | null | undefined) {
    if (!v) return props.scheduleDateLong;
    return new Date(v).toLocaleDateString(localeTag.value, {
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    });
}

const scheduleLegBadge = computed(() => {
    const n = props.scheduleCard?.labelSeq;
    if (n == null || n < 1) return "";
    return t("trip_detail.schedules.tab_short", { n });
});

const hasEmbeddedSchedule = computed(
    () => props.scheduleLegCount === 1 && !!props.scheduleCard,
);

const hasMultipleSchedules = computed(() => props.scheduleLegCount > 1);

const embeddedDepartAt = computed(
    () => props.scheduleCard?.depart_at ?? props.trip.depart_at ?? null,
);
const embeddedReturnAt = computed(
    () => props.scheduleCard?.arrive_by ?? props.trip.arrive_by ?? null,
);

const itineraryDateLabel = computed(() =>
    fmtDateLongFromIso(embeddedDepartAt.value),
);

const pickupPlace = computed(() => {
    const p = props.scheduleCard?.pickup?.trim();
    if (p) return p;
    const o = props.originLabel?.trim();
    return isEmptyDisplay(o) ? "" : String(o).trim();
});

const dropoffPlace = computed(() => {
    const d = props.scheduleCard?.dropoff?.trim();
    if (d) return d;
    const dest = props.destinationLabel?.trim();
    return isEmptyDisplay(dest) ? "" : dest;
});

const embeddedReturnDateShort = computed(() => {
    const a = embeddedDepartAt.value;
    const b = embeddedReturnAt.value;
    if (!a || !b) return "";
    const da = new Date(a);
    const db = new Date(b);
    if (da.toDateString() === db.toDateString()) return "";
    return db.toLocaleDateString(localeTag.value, {
        weekday: "short",
        day: "numeric",
        month: "short",
    });
});

const showFinanceBlock = computed(
    () =>
        !!props.estimatedDistanceLabel?.trim() ||
        !!props.estimatedCostLabel?.trim(),
);

const metaPills = computed(() => {
    const pills: { key: string; text: string; className: string }[] = [];
    const dr = props.trip.dispatch_request;
    if (props.trip.dispatcher?.name) {
        pills.push({
            key: "dispatcher",
            text: t("trip_detail.meta.dispatcher", {
                name: props.trip.dispatcher.name,
            }),
            className: "bg-slate-100 text-slate-800",
        });
    }
    if (dr?.source_channel) {
        pills.push({
            key: "source",
            text: t("trip_detail.meta.source", { ch: dr.source_channel }),
            className: "bg-indigo-50 text-indigo-900",
        });
    }
    if (dr?.paper_status && dr.paper_status !== "pending") {
        pills.push({
            key: "paper",
            text: t("trip_detail.meta.paper", { st: dr.paper_status }),
            className: "bg-amber-50 text-amber-900",
        });
    }
    if (props.trip.payment_status === "paid") {
        pills.push({
            key: "paid",
            text: t("trip_detail.meta.paid"),
            className: "bg-emerald-100 text-emerald-900",
        });
    }
    return pills;
});
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-3.5 text-[13px] shadow-sm sm:p-4"
        :aria-label="t('trip_detail.overview.title')"
    >
        <div class="flex min-w-0 items-center gap-3">
                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-sm font-bold text-slate-700 ring-2 ring-white dark:bg-slate-800 dark:text-slate-200"
                >
                    <img
                        v-if="trip.dispatch_request?.requester?.avatar_url"
                        :src="trip.dispatch_request.requester.avatar_url"
                        alt=""
                        class="h-full w-full object-cover"
                    />
                    <span v-else>{{ requesterInitials }}</span>
                </div>
                <div class="min-w-0">
                    <div
                        v-if="requesterName"
                        class="truncate font-semibold text-slate-900"
                    >
                        {{ requesterName }}
                    </div>
                    <div
                        v-if="requesterSubtitle"
                        class="truncate text-[12px] text-slate-600"
                    >
                        {{ requesterSubtitle }}
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                        <span
                            class="inline-flex items-center rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-medium text-violet-800 ring-1 ring-violet-100"
                        >
                            {{ tripTypeLabel }}
                        </span>
                        <span
                            v-if="trip.dispatch_request?.is_urgent"
                            class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700 ring-1 ring-rose-100"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-rose-500"
                                aria-hidden="true"
                            />
                            {{ t("trip_detail.high_priority") }}
                        </span>
                        <span
                            v-if="passengerCount > 0"
                            class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold tabular-nums text-slate-800"
                        >
                            {{
                                t("trip_detail.schedules.total_guests_value", {
                                    n: passengerCount,
                                })
                            }}
                        </span>
                    </div>
                </div>
        </div>

        <div
            v-if="slaBanner"
            class="mt-3 rounded-xl border px-3 py-2 text-[12px] font-medium"
            :class="
                slaBanner.kind === 'overdue'
                    ? 'border-rose-200 bg-rose-50 text-rose-900'
                    : 'border-sky-200 bg-sky-50 text-sky-950'
            "
        >
            {{ slaBanner.text }}
        </div>

        <div
            v-if="metaPills.length"
            class="mt-2.5 flex flex-wrap gap-1.5 print:hidden"
        >
            <span
                v-for="pill in metaPills"
                :key="pill.key"
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                :class="pill.className"
            >
                {{ pill.text }}
            </span>
        </div>

        <!-- Một lịch: tuyến + khung giờ đầy đủ (từ wizard) -->
        <div
            v-if="hasEmbeddedSchedule"
            class="mt-4 overflow-hidden rounded-xl border border-slate-200/80 bg-slate-50/50"
        >
            <div
                class="flex flex-wrap items-center gap-2 border-b border-slate-200/60 bg-white px-3 py-2"
            >
                <CalendarDaysIcon
                    class="h-3.5 w-3.5 shrink-0 text-slate-400"
                    aria-hidden="true"
                />
                <span class="min-w-0 flex-1 font-semibold leading-snug text-slate-900">
                    {{ itineraryDateLabel }}
                </span>
                <span
                    v-if="scheduleLegBadge"
                    class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600"
                >
                    {{ scheduleLegBadge }}
                </span>
            </div>

            <div class="space-y-0 px-3 py-2.5">
                <div
                    v-if="pickupPlace || fmtTime(embeddedDepartAt)"
                    class="grid gap-1 py-2 sm:grid-cols-[4.5rem_1fr]"
                >
                    <span
                        class="text-[10px] font-bold uppercase tracking-wide text-emerald-700"
                    >
                        {{ t("dispatch_wizard.confirm.lbl_out") }}
                    </span>
                    <div class="min-w-0">
                        <p
                            v-if="fmtTime(embeddedDepartAt)"
                            class="tabular-nums text-[15px] font-semibold text-slate-900"
                        >
                            {{ fmtTime(embeddedDepartAt) }}
                        </p>
                        <p
                            v-if="pickupPlace"
                            class="mt-0.5 text-[12px] font-medium text-slate-700"
                        >
                            {{ pickupPlace }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="dropoffPlace || fmtTime(embeddedReturnAt)"
                    class="grid gap-1 border-t border-slate-200/70 py-2 sm:grid-cols-[4.5rem_1fr]"
                >
                    <span
                        class="text-[10px] font-bold uppercase tracking-wide text-indigo-700"
                    >
                        {{ t("dispatch_wizard.confirm.lbl_back") }}
                    </span>
                    <div class="min-w-0">
                        <p
                            v-if="fmtTime(embeddedReturnAt)"
                            class="tabular-nums text-[15px] font-semibold text-slate-900"
                        >
                            {{ fmtTime(embeddedReturnAt) }}
                            <span
                                v-if="embeddedReturnDateShort"
                                class="ml-1.5 text-[11px] font-medium text-slate-500"
                            >
                                {{ embeddedReturnDateShort }}
                            </span>
                        </p>
                        <p
                            v-if="dropoffPlace"
                            class="mt-0.5 text-[12px] font-medium text-slate-700"
                        >
                            {{ dropoffPlace }}
                        </p>
                    </div>
                </div>
            </div>

            <ul
                v-if="scheduleMismatchNotes.length"
                class="border-t border-slate-200/60 bg-amber-50/40 px-3 py-2 text-[11px] text-amber-900"
            >
                <li v-for="(ln, i) in scheduleMismatchNotes" :key="i">
                    {{ ln }}
                </li>
            </ul>
        </div>

        <p
            v-else-if="hasMultipleSchedules"
            class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-[12px] text-slate-600"
        >
            {{
                t("trip_detail.overview.multi_schedule_hint", {
                    n: scheduleLegCount,
                })
            }}
        </p>

        <!-- Không có lịch wizard: giờ cấp chuyến + tuyến -->
        <div
            v-else
            class="mt-4 space-y-3 rounded-xl border border-slate-200/80 bg-slate-50/50 p-3"
        >
            <div
                v-if="scheduleDateLong"
                class="font-semibold text-slate-900"
            >
                {{ scheduleDateLong }}
            </div>
            <div
                v-if="scheduleDepartTime || scheduleArriveTime"
                class="flex flex-wrap gap-x-4 gap-y-1"
                role="group"
                :aria-label="scheduleTimeRange || undefined"
            >
                <div v-if="scheduleDepartTime">
                    <span
                        class="text-[10px] font-semibold uppercase text-slate-500"
                    >
                        {{ t("trip_detail.overview.time_depart") }}
                    </span>
                    <span
                        class="ml-2 tabular-nums font-semibold text-slate-900"
                    >
                        {{ scheduleDepartTime }}
                    </span>
                </div>
                <div
                    v-if="
                        scheduleArriveTime
                    "
                >
                    <span
                        class="text-[10px] font-semibold uppercase text-slate-500"
                    >
                        {{ t("trip_detail.overview.time_arrive") }}
                    </span>
                    <span
                        class="ml-2 tabular-nums font-semibold text-slate-900"
                    >
                        {{ scheduleArriveTime }}
                    </span>
                </div>
                <div v-if="scheduleDuration">
                    <span
                        class="text-[10px] font-semibold uppercase text-slate-500"
                    >
                        {{ t("trip_detail.overview.duration_label") }}
                    </span>
                    <span class="ml-2 font-medium text-slate-700">
                        {{ scheduleDuration }}
                    </span>
                </div>
            </div>
            <div
                v-if="pickupPlace || dropoffPlace"
                class="flex items-start gap-2 text-[12px]"
            >
                <MapIcon
                    class="mt-0.5 size-4 shrink-0 text-slate-400"
                    aria-hidden="true"
                />
                <div class="min-w-0">
                    <p v-if="pickupPlace" class="font-medium text-slate-800">
                        {{ t("trip_detail.trip_status.pickup") }}:
                        {{ pickupPlace }}
                        <span
                            v-if="stepPickup.label"
                            class="font-normal text-slate-500"
                        >
                            ({{ stepPickup.label }})
                        </span>
                    </p>
                    <p
                        v-if="dropoffPlace"
                        class="mt-1 font-medium text-slate-800"
                    >
                        {{ t("trip_detail.trip_status.dropoff") }}:
                        {{ dropoffPlace }}
                        <span
                            v-if="stepDropoff.label"
                            class="font-normal text-slate-500"
                        >
                            ({{ stepDropoff.label }})
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="showFinanceBlock"
            class="mt-3 flex flex-wrap items-baseline gap-x-4 gap-y-1 rounded-lg border border-slate-100 bg-white px-3 py-2"
        >
            <span
                class="w-full text-[10px] font-bold uppercase tracking-wide text-slate-500"
            >
                {{ t("trip_detail.overview.finance_section") }}
            </span>
            <p
                v-if="estimatedDistanceLabel"
                class="text-[12px] text-slate-700"
            >
                <span class="text-slate-500"
                    >{{ t("trip_detail.overview.est_distance") }}:</span
                >
                <span class="ml-1 font-semibold tabular-nums text-slate-900">{{
                    estimatedDistanceLabel
                }}</span>
            </p>
            <p v-if="estimatedCostLabel" class="text-[12px] text-slate-700">
                <span class="text-slate-500"
                    >{{ t("trip_detail.overview.est_cost") }}:</span
                >
                <span class="ml-1 font-semibold tabular-nums text-slate-900">{{
                    estimatedCostLabel
                }}</span>
            </p>
        </div>
    </section>
</template>
