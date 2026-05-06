<template>
    <section
        class="overflow-hidden rounded-[12px] border-[0.5px] bg-white print:hidden dark:border-slate-700/80 dark:bg-slate-950/30"
        :aria-label="t('trip_detail.coordination.title')"
    >
        <div class="space-y-3 p-3">
            <!-- Header: chip label + badges + frozen status -->
            <div class="flex items-center justify-between gap-2">
                <span
                    class="shrink-0 rounded-full border border-slate-200/80 bg-white px-2.5 py-1 text-[10px] font-semibold uppercase tracking-widest text-slate-500 dark:border-slate-700/60 dark:bg-slate-900/40 dark:text-slate-400"
                >
                    {{ t("trip_detail.coordination.title") }}
                </span>
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                    <span
                        v-if="capacityBannerText"
                        class="shrink-0 rounded-[100px] border-[0.5px] border-[#EF9F27]/70 bg-[#FAEEDA] px-2 py-0.5 text-[11px] font-medium text-[#854F0B] dark:border-[#EF9F27]/45 dark:bg-amber-950/30 dark:text-[#F2C07D]"
                    >
                        ⚠ {{ t("trip_detail.coordination.capacity_short_badge") }}
                    </span>
                    <span
                        v-if="coordinationActionsLocked"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-amber-200/70 bg-amber-50 px-2.5 py-1 text-amber-700 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-300"
                        role="img"
                        :aria-label="
                            t('trip_detail.coordination.actions_locked_after_assign')
                        "
                    >
                        <LockClosedIcon class="size-3 shrink-0" aria-hidden="true" />
                    </span>
                </div>
            </div>

            <div
                v-if="coordinationActionsLocked"
                class="flex items-center gap-2 rounded-xl border border-slate-200/60 bg-slate-50 px-3 py-2.5 dark:border-slate-700/60 dark:bg-slate-900/40"
                role="status"
            >
                <InformationCircleIcon
                    class="size-4 shrink-0 text-slate-400 dark:text-slate-500"
                    aria-hidden="true"
                />
                <p class="text-[12px] font-normal text-slate-500 dark:text-slate-400">
                    {{ t("trip_detail.coordination.actions_locked_after_assign") }}
                </p>
            </div>

            <!-- Departure datetime row (grouped card) -->
            <div
                v-if="canRescheduleTrip"
                class="rounded-[12px] border-[0.5px] bg-slate-50/40 p-2.5 dark:border-slate-700/50 dark:bg-slate-900/25"
            >
                <div
                    class="flex flex-nowrap items-center gap-2 overflow-x-auto"
                >
                    <span class="shrink-0 text-[11px] font-medium text-slate-600 dark:text-slate-400">
                        {{ t("trip_detail.reschedule.depart_label") }}
                    </span>
                    <input
                        :value="rescheduleDepartLocal"
                        type="datetime-local"
                        class="min-w-0 flex-1 shrink rounded-[12px] border-[0.5px] bg-white px-2 py-1.5 text-[13px] font-normal outline-none ring-0 focus:border-[#8B1A1A]/40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 sm:min-w-[10rem]"
                        :disabled="coordinationActionsLocked"
                        @input="onRescheduleDateInput"
                    />
                    <button
                        type="button"
                        class="shrink-0 rounded-[12px] border-[0.5px] border-slate-300/90 bg-white px-2.5 py-1.5 text-[12px] font-normal text-slate-800 hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-800/80"
                        :disabled="rescheduling || coordinationActionsLocked"
                        @click="$emit('reschedule')"
                    >
                        <span
                            v-if="rescheduling"
                            class="mr-1 inline-block h-2.5 w-2.5 animate-spin rounded-full border border-slate-400/50 border-t-slate-700 dark:border-t-slate-200"
                        />
                        {{ t("trip_detail.reschedule.save") }}
                    </button>
                </div>
                <div
                    v-if="rescheduleMsg"
                    class="mt-1.5 rounded-[12px] border-[0.5px] px-2 py-1.5 text-[12px] font-normal"
                    :class="
                        rescheduleFeedbackIsError
                            ? 'border-rose-200/80 bg-rose-50 text-rose-900'
                            : 'border-emerald-200/80 bg-emerald-50 text-emerald-900'
                    "
                    role="status"
                >
                    {{ rescheduleMsg }}
                </div>
            </div>

            <!-- Capacity shortfall -->
            <div
                v-if="capacityBannerText"
                class="flex gap-2 rounded-[12px] border-[0.5px] border-[#EF9F27]/60 bg-[#FAEEDA] px-2.5 py-2 text-[13px] font-normal text-[#854F0B] dark:border-[#EF9F27]/40 dark:bg-amber-950/25 dark:text-[#F2C07D]"
                role="status"
            >
                <ClockIcon
                    class="mt-0.5 h-4 w-4 shrink-0 text-[#EF9F27]"
                    aria-hidden="true"
                />
                <span class="min-w-0 leading-snug">{{ capacityBannerText }}</span>
            </div>

            <!-- Schedule summary + overlapping trips -->
            <div>
                <div
                    class="flex items-center gap-3 rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 dark:border-slate-700/60 dark:bg-slate-950/35"
                >
                    <CalendarDaysIcon
                        class="size-4 shrink-0 text-slate-400 dark:text-slate-500"
                        aria-hidden="true"
                    />
                    <div class="flex min-w-0 flex-col gap-0.5">
                        <strong
                            class="text-[13px] font-medium leading-snug text-slate-800 dark:text-slate-100"
                        >
                            {{
                                scheduleInfoLines.length
                                    ? scheduleInfoLines[0]
                                    : t(
                                          "trip_detail.coordination.toolbar_funnel_empty",
                                      )
                            }}
                        </strong>
                        <span
                            v-if="secondaryScheduleHint.length"
                            class="text-[11px] leading-snug text-slate-400 dark:text-slate-500"
                            >{{ secondaryScheduleHint }}</span
                        >
                    </div>
                </div>
                <div
                    v-if="canAssign && overlappingOtherTrips.length"
                    class="mt-2 rounded-xl border border-red-200/50 bg-red-50/40 px-3 py-2 dark:border-red-900/50 dark:bg-red-950/25"
                >
                    <div
                        class="mb-1.5 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-red-600 dark:text-red-400"
                    >
                        <ExclamationTriangleIcon
                            class="size-3.5 shrink-0"
                            aria-hidden="true"
                        />
                        {{
                            t("trip_detail.coordination.overlap_section_title")
                        }}
                    </div>
                    <ul class="space-y-1.5 overflow-y-auto pr-0.5 text-[12px]">
                        <li
                            v-for="row in overlappingOtherTrips"
                            :key="row.id"
                            class="flex flex-wrap items-center gap-x-2 gap-y-1 text-slate-600 dark:text-slate-400"
                        >
                            <RouterLink
                                :to="tripDetailPathFor(row.id)"
                                class="font-medium text-sky-700 underline-offset-2 hover:underline dark:text-sky-400"
                            >
                                #{{ row.id }}
                            </RouterLink>
                            <span
                                class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] tabular-nums text-slate-500 dark:bg-slate-800/70 dark:text-slate-400"
                                >{{ fmtTime(row.depart_at) }}</span
                            >
                            <span class="text-slate-600 dark:text-slate-400">{{
                                row.label
                            }}</span>
                            <span
                                v-if="row.driverName || row.vehiclePlate"
                                class="text-slate-500 dark:text-slate-500"
                            >
                                <template v-if="row.driverName">{{
                                    row.driverName
                                }}</template>
                                <template v-if="row.driverName && row.vehiclePlate"
                                    >&nbsp;·&nbsp;</template
                                >
                                <template v-if="row.vehiclePlate">{{
                                    row.vehiclePlate
                                }}</template>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="border-t border-[0.5px] border-slate-200/70 dark:border-slate-700/50"
                aria-hidden="true"
            />

            <!-- Current assignment + resources -->
            <div>
                <div
                    v-if="
                        showInternalVehicleCard ||
                        showInternalDriverCard ||
                        vehicleConflictBanner
                    "
                    class="mb-2"
                >
                    <div
                        class="text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400"
                    >
                        {{
                            t(
                                "trip_detail.coordination.current_assignment_section",
                            )
                        }}
                    </div>
                    <div
                        class="mt-2 space-y-3 md:grid md:grid-cols-2 md:gap-3 md:space-y-0"
                    >
                        <VehicleCard
                            v-if="
                                showInternalVehicleCard && selectedVehicleForCard
                            "
                            :vehicle="selectedVehicleForCard"
                            :busy="vehicleCardBusy"
                            :allow-change="!coordinationActionsLocked"
                            @change="$emit('vehicle-card-change')"
                        />
                        <DriverCard
                            v-if="
                                showInternalDriverCard && selectedDriverForCard
                            "
                            :driver="selectedDriverForCard"
                            :busy="driverCardBusy"
                            :allow-change="!coordinationActionsLocked"
                            @change="$emit('driver-card-change')"
                        />
                    </div>
                    <div
                        v-if="showAssignmentSupplementsPanel"
                        class="mt-3 rounded-xl border border-emerald-200/60 bg-emerald-50/25 px-3 py-2.5 dark:border-emerald-900/45 dark:bg-emerald-950/20"
                        role="status"
                        :aria-label="
                            t(
                                'trip_detail.coordination.supplement_section_title',
                            )
                        "
                    >
                        <div
                            class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-emerald-800 dark:text-emerald-400"
                        >
                            {{
                                t(
                                    "trip_detail.coordination.supplement_section_title",
                                )
                            }}
                        </div>
                        <ul class="flex flex-wrap gap-1.5">
                            <li
                                v-for="item in supplementAssignmentsFlattened"
                                :key="`${item.prefix}-${item.id ?? item.label}-${item.idx}`"
                                class="inline-flex max-w-full items-center rounded-full border border-emerald-300/40 bg-emerald-50 px-2 py-0.5 text-[12px] font-normal text-emerald-800 dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-300"
                            >
                                <span class="truncate"
                                    ><span class="font-medium">{{
                                        item.prefix
                                    }}</span
                                    >&nbsp;{{ item.line }}</span
                                >
                            </li>
                        </ul>
                    </div>
                    <ConflictBanner
                        class="mt-3"
                        :conflict="vehicleConflictBanner"
                        :disabled="coordinationActionsLocked"
                        @pick-again="$emit('conflict-pick-again')"
                        @keep-anyway="$emit('conflict-keep')"
                    />
                </div>

                <div
                    class="rounded-xl border border-slate-200/70 bg-transparent p-0 dark:border-slate-700/60"
                >
                    <ResourcePanel
                        v-if="tripId"
                        ref="resourcePanelRef"
                        :trip-id="tripId"
                        :trip-date="scheduleDateKeyForList"
                        :needed-seats="neededSeats"
                        :available-count="suitableVehiclesCount"
                        :busy-vehicle-ids="busyVehicleIds"
                        :busy-driver-ids="busyDriverIds"
                        :trip-snapshot="tripSnapshot"
                        :can-quick-create-vendor="canQuickCreateProvider"
                        :hide-internal-vehicle-section="showInternalVehicleCard"
                        :hide-internal-driver-section="showInternalDriverCard"
                        :disabled="coordinationActionsLocked"
                        @update:resources="$emit('update:resources', $event)"
                        @create-vendor="$emit('create-vendor')"
                    />
                </div>
            </div>

            <div
                v-if="assignMsg"
                class="rounded-[12px] border-[0.5px] px-3 py-2 text-sm font-medium"
                :class="
                    assignFeedbackKind === 'success'
                        ? 'border-emerald-200/80 bg-emerald-50 text-emerald-950'
                        : assignFeedbackKind === 'error'
                          ? 'border-rose-200/80 bg-rose-50 text-rose-950'
                          : 'border-slate-200/80 bg-slate-50 text-slate-800'
                "
                role="alert"
            >
                {{ assignMsg }}
            </div>

            <div
                class="mt-1 border-t border-slate-100 pt-3 dark:border-slate-700/60"
            >
                <label
                    class="mb-1.5 block text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500"
                    for="dispatch-internal-notes-field"
                >
                    {{ t("trip_detail.coordination.internal_notes") }}
                </label>
                <textarea
                    id="dispatch-internal-notes-field"
                    :value="coordinationNotes"
                    rows="3"
                    class="w-full resize-none rounded-xl border border-slate-200/70 bg-slate-50/60 px-3 py-2.5 text-[13px] font-normal text-slate-700 outline-none ring-0 placeholder:text-slate-400 focus:border-[#8B1A1A]/40 disabled:cursor-not-allowed disabled:opacity-55 dark:border-slate-700/60 dark:bg-slate-900/40 dark:text-slate-100 dark:placeholder:text-slate-500"
                    :placeholder="
                        t('trip_detail.coordination.internal_notes_ph')
                    "
                    :disabled="coordinationActionsLocked"
                    @input="onCoordNotesInput"
                />
            </div>

            <p
                v-if="!canAssign && !canUpdateStatus"
                class="text-xs font-normal text-slate-500"
            >
                {{ t("trip_detail.coordination.no_permission_assign") }}
            </p>

            <!-- Sticky assignment actions -->
            <div
                v-if="showAssignFooter"
                class="sticky bottom-0 z-10 -mx-3 -mb-3 mt-1 border-t border-[0.5px] border-slate-200/80 bg-white/95 px-3 py-3 backdrop-blur-sm dark:border-slate-700/70 dark:bg-slate-950/95"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="rounded-[12px] border-[0.5px] border-slate-300/90 bg-transparent px-3 py-2 text-[12px] font-normal text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800/80"
                        :disabled="assigning"
                        @click="emit('cancel')"
                    >
                        {{ t("trip_detail.coordination.footer_cancel") }}
                    </button>
                    <button
                        type="button"
                        class="min-w-0 flex-1 rounded-[12px] px-3 py-2 text-[12px] font-normal text-white disabled:cursor-not-allowed disabled:opacity-50 sm:min-w-[12rem] sm:flex-initial"
                        style="background-color: #8b1a1a"
                        :disabled="assigning || !assignReady"
                        @click="emit('assign')"
                    >
                        <span
                            v-if="assigning"
                            class="mr-2 inline-block h-3 w-3 animate-spin rounded-full border border-white/40 border-t-white"
                        />
                        {{
                            t("trip_detail.coordination.footer_confirm_assign")
                        }}
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import {
    CalendarDaysIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    LockClosedIcon,
} from "@heroicons/vue/24/outline";
import ResourcePanel from "../dispatch/ResourcePanel.vue";
import ConflictBanner, { type VehicleConflict } from "./ConflictBanner.vue";
import DriverCard from "./DriverCard.vue";
import VehicleCard from "./VehicleCard.vue";
import type { SupplementItem } from "../../types/dispatch";
import { buildStaffPrefixedPath as staffPath } from "../../config/dispatchWebBase";

const props = withDefaults(
    defineProps<{
    canAssign: boolean;
    canUpdateStatus: boolean;
    canRescheduleTrip: boolean;
    /** Chuyến đã qua bước gán trên timeline — khóa chỉnh sửa panel điều phối */
    coordinationActionsLocked?: boolean;
    canQuickCreateProvider: boolean;
    rescheduleDepartLocal: string;
    rescheduling: boolean;
    rescheduleMsg: string;
    rescheduleFeedbackIsError: boolean;
    showInternalVehicleCard: boolean;
    selectedVehicleForCard: object | null;
    vehicleCardBusy: boolean;
    showInternalDriverCard: boolean;
    selectedDriverForCard: object | null;
    driverCardBusy: boolean;
    vehicleConflictBanner: VehicleConflict | null;
    tripId: number | null;
    scheduleDateKeyForList: string;
    neededSeats: number;
    suitableVehiclesCount: number;
    busyVehicleIds: number[];
    busyDriverIds: number[];
    tripSnapshot: object | null;
    overlappingOtherTrips: {
        id: number;
        depart_at: string;
        label: string;
        driverName: string;
        vehiclePlate: string;
    }[];
    coordinationNotes: string;
    assignMsg: string;
    assignFeedbackKind: string;
    scheduleInfoLines: string[];
    capacityBannerText: string;
    showAssignFooter: boolean;
    assignReady: boolean;
    assigning: boolean;
    supplementAssignments?:
        | { taxis: SupplementItem[]; vendors: SupplementItem[] }
        | null;
}>(),
    {
        coordinationActionsLocked: false,
        supplementAssignments: null,
    },
);

const emit = defineEmits<{
    reschedule: [];
    "vehicle-card-change": [];
    "driver-card-change": [];
    "conflict-pick-again": [];
    "conflict-keep": [];
    "update:resources": [payload: unknown];
    "create-vendor": [];
    "update:rescheduleDepartLocal": [value: string];
    "update:coordinationNotes": [value: string];
    assign: [];
    cancel: [];
}>();

const { t, locale } = useI18n();
const route = useRoute();

const resourcePanelRef = ref<InstanceType<typeof ResourcePanel> | null>(null);
defineExpose({ resourcePanel: resourcePanelRef });

const secondaryScheduleHint = computed(() => {
    const lines = props.scheduleInfoLines ?? [];
    if (lines.length <= 1) return "";
    return lines.slice(1).join(" · ");
});

function formatSupplementLine(it: SupplementItem): string {
    const lab = String(it.label ?? "").trim();
    const n = Number(it.supplementSeats);
    if (Number.isFinite(n) && n > 0) {
        return `${lab} — ${t("trip_detail.coordination.seats_n", {
            n: Math.floor(n),
        })}`;
    }
    return lab;
}

type SupplementFlatten = {
    idx: number;
    prefix: string;
    line: string;
    id: string | number | null | undefined;
    label?: string | null;
};

const supplementAssignmentsFlattened = computed<SupplementFlatten[]>(() => {
    const s = props.supplementAssignments;
    const out: SupplementFlatten[] = [];
    if (!s) return out;
    let idx = 0;
    const taxiLbl = t("trip_detail.coordination.resource_section_taxi_title");
    const vendorLbl = t(
        "trip_detail.coordination.resource_section_vendor_title",
    );
    for (const it of s.taxis ?? []) {
        idx++;
        out.push({
            idx,
            prefix: taxiLbl,
            line: formatSupplementLine(it),
            id: it.id,
            label: it.label,
        });
    }
    for (const it of s.vendors ?? []) {
        idx++;
        out.push({
            idx,
            prefix: vendorLbl,
            line: formatSupplementLine(it),
            id: it.id,
            label: it.label,
        });
    }
    return out;
});

/** Khi đã chọn & hiển thị tài xế nội bộ — bổ sung phương tiện gộp vào khối phân công. */
const showAssignmentSupplementsPanel = computed(() => {
    if (!props.showInternalDriverCard) return false;
    return supplementAssignmentsFlattened.value.length > 0;
});

function tripDetailPathFor(id: number) {
    return route.path.startsWith("/driver")
        ? `/driver/trips/${id}`
        : staffPath(`/trips/${id}`);
}

function fmtTime(v: string) {
    const l = locale.value === "en" ? "en-US" : "vi-VN";
    return v
        ? new Date(v).toLocaleTimeString(l, {
              hour: "2-digit",
              minute: "2-digit",
          })
        : "-";
}

function onRescheduleDateInput(e: Event) {
    emit("update:rescheduleDepartLocal", (e.target as HTMLInputElement).value);
}

function onCoordNotesInput(e: Event) {
    emit("update:coordinationNotes", (e.target as HTMLTextAreaElement).value);
}
</script>
