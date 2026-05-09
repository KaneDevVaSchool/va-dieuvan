<template>
    <section
        class="overflow-hidden rounded-2xl bg-white shadow-sm shadow-slate-900/5 print:hidden dark:bg-slate-950/40 dark:shadow-black/25"
        :aria-label="t('trip_detail.coordination.title')"
    >
        <div class="space-y-3.5 p-3.5">
            <!-- Header: chip label + badges + frozen status -->
            <div class="flex items-center justify-between gap-2">
                <span
                    class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-500 dark:bg-slate-800/90 dark:text-slate-400"
                >
                    {{ t("trip_detail.coordination.title") }}
                </span>
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                    <span
                        v-if="capacityBannerText"
                        class="inline-flex shrink-0 items-center gap-1 rounded-full bg-[#FAEEDA] px-2.5 py-0.5 text-[11px] font-medium text-[#854F0B] shadow-sm shadow-amber-900/5 dark:bg-amber-950/45 dark:text-[#F2C07D]"
                    >
                        <ExclamationTriangleIcon class="size-3 shrink-0" aria-hidden="true" />
                        {{ t("trip_detail.coordination.capacity_short_badge") }}
                    </span>
                    <span
                        v-if="coordinationActionsLocked"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-amber-100/90 px-2.5 py-1 text-amber-800 shadow-sm shadow-amber-900/5 dark:bg-amber-950/50 dark:text-amber-200"
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
                class="flex items-start gap-2.5 rounded-2xl bg-slate-100/80 px-3.5 py-2.5 shadow-inner shadow-slate-900/5 dark:bg-slate-800/50 dark:shadow-black/20"
                role="status"
            >
                <InformationCircleIcon
                    class="mt-0.5 size-4 shrink-0 text-slate-400 dark:text-slate-500"
                    aria-hidden="true"
                />
                <p class="text-[12px] leading-relaxed text-slate-600 dark:text-slate-400">
                    {{ t("trip_detail.coordination.actions_locked_after_assign") }}
                </p>
            </div>

            <!-- Departure datetime -->
            <CollapsiblePanelSection
                v-if="canRescheduleTrip"
                :title="t('trip_detail.coordination.section_reschedule_title')"
                :summary-collapsed="rescheduleCollapsedSummary"
                :default-expanded="!coordinationActionsLocked"
                :persist-key="collapseStorageKey('reschedule')"
            >
                <div class="rounded-xl bg-slate-100/50 p-3 shadow-inner shadow-slate-900/5 dark:bg-slate-900/25 dark:shadow-black/15">
                    <div
                        class="flex flex-nowrap items-center gap-2 overflow-x-auto"
                    >
                        <span class="shrink-0 text-[11px] font-semibold tracking-wide text-slate-500 dark:text-slate-400">
                            {{ t("trip_detail.reschedule.depart_label") }}
                        </span>
                        <input
                            :value="rescheduleDepartLocal"
                            type="datetime-local"
                            class="min-w-0 flex-1 shrink rounded-xl bg-white px-2.5 py-1.5 text-[13px] font-normal text-slate-800 shadow-inner shadow-slate-900/5 outline-none focus:bg-white focus:shadow-md focus:shadow-slate-900/10 disabled:opacity-55 dark:bg-slate-800/90 dark:text-slate-100 dark:shadow-black/25 dark:focus:shadow-lg sm:min-w-[10rem]"
                            :disabled="coordinationActionsLocked"
                            @input="onRescheduleDateInput"
                        />
                        <button
                            type="button"
                            class="shrink-0 rounded-xl bg-white px-2.5 py-1.5 text-[12px] font-medium text-slate-800 shadow-sm shadow-slate-900/10 hover:bg-slate-50 disabled:opacity-50 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700/80 dark:shadow-black/25"
                            :disabled="rescheduling || coordinationActionsLocked"
                            @click="$emit('reschedule')"
                        >
                            <span
                                v-if="rescheduling"
                                class="mr-1 inline-block h-2.5 w-2.5 animate-spin rounded-full border-2 border-slate-300 border-t-[#8B1A1A] dark:border-slate-600 dark:border-t-amber-200"
                            />
                            {{ t("trip_detail.reschedule.save") }}
                        </button>
                    </div>
                    <div
                        v-if="rescheduleMsg"
                        class="mt-2 rounded-xl px-2.5 py-1.5 text-[12px] font-normal leading-snug shadow-sm"
                        :class="
                            rescheduleFeedbackIsError
                                ? 'bg-rose-50 text-rose-900 dark:bg-rose-950/40 dark:text-rose-100'
                                : 'bg-emerald-50 text-emerald-900 dark:bg-emerald-950/35 dark:text-emerald-100'
                        "
                        role="status"
                    >
                        {{ rescheduleMsg }}
                    </div>
                </div>
            </CollapsiblePanelSection>

            <!-- Capacity shortfall -->
            <div
                v-if="capacityBannerText"
                class="flex gap-2.5 rounded-2xl bg-[#FAEEDA]/95 px-3 py-2.5 text-[13px] font-normal leading-relaxed text-[#854F0B] shadow-sm shadow-amber-900/10 dark:bg-amber-950/30 dark:text-[#F2C07D]"
                role="status"
            >
                <ExclamationTriangleIcon
                    class="mt-0.5 h-4 w-4 shrink-0 text-[#EF9F27]"
                    aria-hidden="true"
                />
                <span class="min-w-0 leading-snug">{{ capacityBannerText }}</span>
            </div>

            <CollapsiblePanelSection
                :title="t('trip_detail.coordination.schedule_alerts_heading')"
                :summary-collapsed="scheduleSectionSummaryCollapsed"
                :persist-key="collapseStorageKey('schedule')"
            >
                <template #header-end>
                    <button
                        type="button"
                        class="rounded-xl p-2 text-slate-500 transition-colors hover:bg-white/75 hover:text-slate-800 dark:hover:bg-slate-800/70 dark:hover:text-slate-100"
                        :aria-label="
                            t('trip_detail.coordination.open_schedule_popup_aria')
                        "
                        @click.stop="scheduleDetailPopupOpen = true"
                    >
                        <ArrowsPointingOutIcon class="size-4 shrink-0" aria-hidden="true" />
                    </button>
                </template>
                <div>
                    <div
                        class="flex items-center gap-3 rounded-2xl bg-gradient-to-br from-slate-50 via-white to-slate-50/90 px-3.5 py-3 shadow-sm shadow-slate-900/5 dark:from-slate-900/50 dark:via-slate-900/35 dark:to-slate-950/60 dark:shadow-black/25"
                    >
                        <CalendarDaysIcon
                            class="size-4 shrink-0 text-slate-400 dark:text-slate-500"
                            aria-hidden="true"
                        />
                        <strong
                            class="text-[13px] font-semibold leading-snug text-slate-800 dark:text-slate-50"
                        >
                            {{
                                scheduleInfoLines.length
                                    ? scheduleInfoLines[0]
                                    : t(
                                          'trip_detail.coordination.toolbar_funnel_empty',
                                      )
                            }}
                        </strong>
                    </div>
                    <div
                        v-if="canAssign && overlappingOtherTrips.length"
                        class="mt-2 rounded-2xl bg-red-50/90 px-3.5 py-2.5 shadow-sm shadow-red-900/10 dark:bg-red-950/35 dark:shadow-black/25"
                    >
                        <div
                            class="mb-1.5 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-red-600 dark:text-red-400"
                        >
                            <ExclamationTriangleIcon
                                class="size-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            {{
                                t('trip_detail.coordination.overlap_section_title')
                            }}
                        </div>
                        <ul class="schedule-overlap-ul max-h-36 space-y-1.5 overflow-y-auto overscroll-contain pr-0.5 text-[12px]">
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
                                    class="rounded-md bg-white/70 px-1.5 py-0.5 text-[11px] tabular-nums text-slate-600 shadow-inner shadow-slate-900/5 dark:bg-slate-800/70 dark:text-slate-400"
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
            </CollapsiblePanelSection>

            <!-- Current assignment (summary cards) -->
            <CollapsiblePanelSection
                v-if="
                    showInternalVehicleCard ||
                    showInternalDriverCard ||
                    vehicleConflictBanner
                "
                :title="
                    t('trip_detail.coordination.current_assignment_section')
                "
                :summary-collapsed="assignmentCollapsedSummary"
                :persist-key="collapseStorageKey('assignment')"
            >
                <div class="space-y-3">
                    <div
                        class="space-y-3 md:grid md:grid-cols-2 md:gap-3 md:space-y-0"
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
                        class="rounded-2xl bg-emerald-50/85 px-3.5 py-3 shadow-sm shadow-emerald-900/10 dark:bg-emerald-950/35 dark:shadow-black/20"
                        role="status"
                        :aria-label="
                            t(
                                'trip_detail.coordination.supplement_section_title',
                            )
                        "
                    >
                        <div
                            class="mb-2 text-[10px] font-semibold uppercase tracking-[0.1em] text-emerald-800 dark:text-emerald-400"
                        >
                            {{
                                t(
                                    'trip_detail.coordination.supplement_section_title',
                                )
                            }}
                        </div>
                        <ul class="flex flex-wrap gap-1.5">
                            <li
                                v-for="item in supplementAssignmentsFlattened"
                                :key="`${item.prefix}-${item.id ?? item.label}-${item.idx}`"
                                class="inline-flex max-w-full items-center rounded-full bg-emerald-100/80 px-2.5 py-0.5 text-[11px] font-medium text-emerald-900 shadow-sm shadow-emerald-900/10 dark:bg-emerald-950/55 dark:text-emerald-100"
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
                        class="block"
                        :conflict="vehicleConflictBanner"
                        :disabled="coordinationActionsLocked"
                        @pick-again="$emit('conflict-pick-again')"
                        @keep-anyway="$emit('conflict-keep')"
                    />
                </div>
            </CollapsiblePanelSection>

            <div v-if="tripId" class="-mx-0.5">
                <ResourcePanel
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
                    :hide-internal-driver-section="coordinationActionsLocked"
                    :disabled="coordinationActionsLocked"
                    @update:resources="$emit('update:resources', $event)"
                    @create-vendor="$emit('create-vendor')"
                />
            </div>

            <div
                v-if="assignMsg"
                class="rounded-xl px-3 py-2 text-[13px] font-medium leading-snug shadow-sm"
                :class="
                    assignFeedbackKind === 'success'
                        ? 'bg-emerald-50 text-emerald-950 dark:bg-emerald-950/35 dark:text-emerald-50'
                        : assignFeedbackKind === 'error'
                          ? 'bg-rose-50 text-rose-950 dark:bg-rose-950/40 dark:text-rose-50'
                          : 'bg-slate-100 text-slate-800 dark:bg-slate-800/70 dark:text-slate-100'
                "
                role="alert"
            >
                {{ assignMsg }}
            </div>

            <CollapsiblePanelSection
                :title="t('trip_detail.coordination.internal_notes')"
                :summary-collapsed="notesCollapsedSummary"
                :persist-key="collapseStorageKey('notes')"
                :default-expanded="Boolean(coordinationNotes?.trim()?.length)"
            >
                <label
                    class="sr-only"
                    for="dispatch-internal-notes-field"
                >
                    {{ t("trip_detail.coordination.internal_notes") }}
                </label>
                <textarea
                    id="dispatch-internal-notes-field"
                    :value="coordinationNotes"
                    rows="3"
                    class="w-full resize-none rounded-2xl bg-slate-100/90 px-3.5 py-2.5 text-[13px] font-normal leading-relaxed text-slate-700 shadow-inner shadow-slate-900/5 outline-none placeholder:text-slate-400 focus:bg-white focus:shadow-md focus:shadow-slate-900/10 disabled:cursor-not-allowed disabled:opacity-55 dark:bg-slate-800/80 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:bg-slate-900"
                    :placeholder="
                        t('trip_detail.coordination.internal_notes_ph')
                    "
                    :disabled="coordinationActionsLocked"
                    @input="onCoordNotesInput"
                />
            </CollapsiblePanelSection>

            <p
                v-if="!canAssign && !canUpdateStatus"
                class="text-xs font-normal text-slate-500"
            >
                {{ t("trip_detail.coordination.no_permission_assign") }}
            </p>

            <!-- Sticky assignment actions -->
            <div
                v-if="showAssignFooter"
                class="sticky bottom-0 z-10 -mx-3.5 -mb-3.5 mt-2 bg-white/92 px-3.5 py-3 shadow-[0_-12px_32px_-8px_rgba(15,23,42,0.1)] backdrop-blur-md supports-[backdrop-filter]:bg-white/80 dark:bg-slate-950/92 dark:shadow-[0_-12px_32px_-8px_rgba(0,0,0,0.45)]"
            >
                <div class="flex flex-col gap-2">
                    <button
                        type="button"
                        class="w-full rounded-xl bg-[#8B1A1A] px-3 py-2 text-[12px] font-semibold text-white shadow-md shadow-[#8B1A1A]/25 outline-none hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-45 dark:shadow-[#8B1A1A]/30"
                        :disabled="assigning || !assignReady"
                        @click="emit('assign')"
                    >
                        <span
                            v-if="assigning"
                            class="mr-2 inline-block h-3 w-3 animate-spin rounded-full border border-white/40 border-t-white"
                        />
                        <span v-if="assigning" class="sr-only">{{ t("trip_detail.coordination.footer_loading_aria") }}</span>
                        {{
                            t("trip_detail.coordination.footer_confirm_assign")
                        }}
                    </button>
                    <button
                        type="button"
                        class="w-full rounded-xl bg-slate-100 px-3 py-2 text-[12px] font-medium text-slate-700 shadow-sm hover:bg-slate-200/80 disabled:opacity-55 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        :disabled="assigning"
                        @click="emit('cancel')"
                    >
                        {{ t("trip_detail.coordination.footer_cancel") }}
                    </button>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div
                v-if="scheduleDetailPopupOpen"
                class="fixed inset-0 z-[240] flex items-start justify-center overflow-y-auto bg-black/45 p-4 pb-10 pt-10 dark:bg-black/55"
                role="dialog"
                aria-modal="true"
                :aria-label="
                    t('trip_detail.coordination.popup_schedule_title')
                "
                @click.self="scheduleDetailPopupOpen = false"
            >
                <div
                    class="w-full max-w-md rounded-2xl bg-white p-4 shadow-2xl dark:bg-slate-900"
                    @click.stop
                >
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2
                            class="text-[13px] font-semibold text-slate-800 dark:text-slate-100"
                        >
                            {{
                                t(
                                    "trip_detail.coordination.popup_schedule_title",
                                )
                            }}
                        </h2>
                        <button
                            type="button"
                            class="rounded-lg bg-slate-100 px-2.5 py-1 text-[12px] font-medium text-slate-700 shadow-sm dark:bg-slate-800 dark:text-slate-200"
                            @click="scheduleDetailPopupOpen = false"
                        >
                            {{
                                t(
                                    "trip_detail.coordination.popup_schedule_close",
                                )
                            }}
                        </button>
                    </div>
                    <div
                        class="flex items-center gap-3 rounded-2xl bg-gradient-to-br from-slate-50 via-white to-slate-50/90 px-3.5 py-3 shadow-sm dark:from-slate-900/50 dark:via-slate-900/35 dark:to-slate-950/60"
                    >
                        <CalendarDaysIcon
                            class="size-4 shrink-0 text-slate-400 dark:text-slate-500"
                            aria-hidden="true"
                        />
                        <strong
                            class="text-[13px] font-semibold leading-snug text-slate-800 dark:text-slate-50"
                        >
                            {{
                                scheduleInfoLines.length
                                    ? scheduleInfoLines[0]
                                    : t(
                                          "trip_detail.coordination.toolbar_funnel_empty",
                                      )
                            }}
                        </strong>
                    </div>
                    <div
                        v-if="canAssign && overlappingOtherTrips.length"
                        class="mt-3 rounded-2xl bg-red-50/90 px-3.5 py-2.5 shadow-sm dark:bg-red-950/35"
                    >
                        <div
                            class="mb-1.5 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-red-600 dark:text-red-400"
                        >
                            <ExclamationTriangleIcon
                                class="size-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            {{
                                t(
                                    "trip_detail.coordination.overlap_section_title",
                                )
                            }}
                        </div>
                        <ul class="max-h-[40vh] space-y-1.5 overflow-y-auto overscroll-contain text-[12px]">
                            <li
                                v-for="row in overlappingOtherTrips"
                                :key="'pop-' + row.id"
                                class="flex flex-wrap items-center gap-x-2 gap-y-1 text-slate-600 dark:text-slate-400"
                            >
                                <RouterLink
                                    :to="tripDetailPathFor(row.id)"
                                    class="font-medium text-sky-700 underline-offset-2 hover:underline dark:text-sky-400"
                                    @click="scheduleDetailPopupOpen = false"
                                >
                                    #{{ row.id }}
                                </RouterLink>
                                <span
                                    class="rounded-md bg-white/70 px-1.5 py-0.5 text-[11px] tabular-nums text-slate-600 shadow-inner dark:bg-slate-800/70 dark:text-slate-400"
                                    >{{ fmtTime(row.depart_at) }}</span
                                >
                                <span class="text-slate-600 dark:text-slate-400">{{
                                    row.label
                                }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </Teleport>
    </section>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import {
    ArrowsPointingOutIcon,
    CalendarDaysIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    LockClosedIcon,
} from "@heroicons/vue/24/outline";
import CollapsiblePanelSection from "../dispatch/CollapsiblePanelSection.vue";
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

const scheduleDetailPopupOpen = ref(false);

function collapseStorageKey(segment: string): string | null {
    const id = props.tripId;
    if (id == null || id === "") return null;
    return `va-trip-${String(id)}-dispatch-${segment}`;
}

const rescheduleCollapsedSummary = computed(() => {
    const raw = String(props.rescheduleDepartLocal ?? "").trim();
    if (!raw) return t("trip_detail.reschedule.depart_label");
    return raw.replace("T", " ");
});

const scheduleSectionSummaryCollapsed = computed(() => {
    const first = props.scheduleInfoLines?.[0]?.trim() ?? "";
    const base =
        first ||
        t("trip_detail.coordination.toolbar_funnel_empty");
    if (props.canAssign && props.overlappingOtherTrips.length > 0) {
        return `${base} · ${t("trip_detail.coordination.section_overlap_count", {
            n: props.overlappingOtherTrips.length,
        })}`;
    }
    return base;
});

const notesCollapsedSummary = computed(() => {
    const n = props.coordinationNotes?.trim()?.length ?? 0;
    if (!n)
        return t("trip_detail.coordination.notes_collapsed_empty");
    return t("trip_detail.coordination.notes_chars_summary", { n });
});

const secondaryScheduleHint = computed(() => {
    const lines = props.scheduleInfoLines ?? [];
    if (lines.length <= 1) return "";
    return lines.slice(1).join(" · ");
});

function formatSupplementLine(it: SupplementItem): string {
    const lab = String(it.label ?? "").trim();
    const n = Number(it.supplementSeats);
    let base = lab;
    if (Number.isFinite(n) && n > 0) {
        base = `${lab} — ${t("trip_detail.coordination.seats_n", {
            n: Math.floor(n),
        })}`;
    }
    const cn = String(it.contactNotes ?? "").trim();
    if (cn) {
        const short = cn.length > 80 ? `${cn.slice(0, 77)}…` : cn;
        return base ? `${base} · ${short}` : short;
    }
    return base;
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

const assignmentCollapsedSummary = computed(() => {
    const bits: string[] = [];
    const v = props.selectedVehicleForCard as {
        license_plate?: string | null;
    } | null;
    const d = props.selectedDriverForCard as {
        full_name?: string | null;
    } | null;
    if (props.showInternalVehicleCard && v) {
        bits.push(
            v.license_plate?.trim()?.length ? String(v.license_plate).trim() : "—",
        );
    }
    if (props.showInternalDriverCard && d?.full_name?.trim()?.length)
        bits.push(String(d.full_name).trim());
    if (
        showAssignmentSupplementsPanel.value &&
        supplementAssignmentsFlattened.value.length > 0
    )
        bits.push(`+${supplementAssignmentsFlattened.value.length}`);
    return bits.length
        ? bits.join(" · ")
        : t("trip_detail.coordination.assignment_collapsed_empty");
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
