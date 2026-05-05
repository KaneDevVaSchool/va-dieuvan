<template>
    <section
        class="overflow-hidden rounded-[12px] border-[0.5px] border-slate-200/90 bg-white print:hidden dark:border-slate-700/80 dark:bg-slate-950/30"
        :aria-label="t('trip_detail.coordination.title')"
    >
        <div class="space-y-3 p-3">
            <!-- Header: title + shortfall badge -->
            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="shrink-0 text-sm font-medium text-slate-700 dark:text-slate-200"
                >
                    {{ t("trip_detail.coordination.title") }}
                </span>
                <div class="flex-1" />
                <span
                    v-if="capacityBannerText"
                    class="shrink-0 rounded-full border-[0.5px] border-[#EF9F27]/50 bg-[#EF9F27]/12 px-2 py-0.5 text-[11px] font-medium text-[#B9720D] dark:border-[#EF9F27]/40 dark:bg-[#EF9F27]/14 dark:text-[#F2C07D]"
                >
                    ⚠ {{ t("trip_detail.coordination.capacity_short_badge") }}
                </span>
            </div>

            <!-- Schedule & alerts (surfaced, not collapsed) -->
            <div
                class="rounded-[12px] border-[0.5px] border-slate-200/80 bg-slate-50/50 px-2.5 py-2 dark:border-slate-700/60 dark:bg-slate-900/30"
            >
                <div
                    class="text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400"
                >
                    {{ t("trip_detail.coordination.schedule_alerts_heading") }}
                </div>
                <ul
                    class="mt-1.5 space-y-1 text-xs font-normal text-slate-700 dark:text-slate-300"
                >
                    <li
                        v-for="(line, idx) in scheduleInfoLines"
                        :key="'sched-' + idx"
                        class="leading-snug"
                    >
                        {{ line }}
                    </li>
                    <li
                        v-if="!scheduleInfoLines.length"
                        class="text-slate-400 dark:text-slate-500"
                    >
                        {{
                            t("trip_detail.coordination.toolbar_funnel_empty")
                        }}
                    </li>
                </ul>
            </div>

            <!-- Capacity shortfall -->
            <div
                v-if="capacityBannerText"
                class="flex gap-2 rounded-[12px] border-[0.5px] border-[#EF9F27]/45 bg-[#EF9F27]/14 px-2.5 py-2 text-xs font-normal text-[#8A4A0A] dark:border-[#EF9F27]/35 dark:bg-[#EF9F27]/12 dark:text-[#F2C98A]"
                role="status"
            >
                <ClockIcon
                    class="mt-0.5 h-4 w-4 shrink-0 text-[#EF9F27]"
                    aria-hidden="true"
                />
                <span class="min-w-0 leading-snug">{{ capacityBannerText }}</span>
            </div>

            <!-- Departure datetime row (grouped card) -->
            <div
                v-if="canRescheduleTrip"
                class="rounded-[12px] border-[0.5px] border-slate-200/80 bg-slate-50/40 p-2.5 dark:border-slate-700/50 dark:bg-slate-900/25"
            >
                <div
                    class="flex flex-nowrap items-center gap-2 overflow-x-auto"
                >
                    <span
                        class="shrink-0 text-xs font-medium text-slate-600 dark:text-slate-300"
                    >
                        {{ t("trip_detail.reschedule.depart_label") }}
                    </span>
                    <input
                        :value="rescheduleDepartLocal"
                        type="datetime-local"
                        class="min-w-0 flex-1 shrink rounded-[12px] border-[0.5px] border-slate-200/90 bg-white px-2 py-1.5 text-xs font-normal outline-none ring-0 focus:border-[#8B1A1A]/40 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 sm:min-w-[10rem]"
                        @input="onRescheduleDateInput"
                    />
                    <button
                        type="button"
                        class="shrink-0 rounded-[12px] border-[0.5px] border-slate-300/90 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-800 hover:bg-slate-50 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-800/80"
                        :disabled="rescheduling"
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
                    class="mt-1.5 rounded-[12px] border-[0.5px] px-2 py-1.5 text-xs font-medium"
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
                            @change="$emit('vehicle-card-change')"
                        />
                        <DriverCard
                            v-if="
                                showInternalDriverCard && selectedDriverForCard
                            "
                            :driver="selectedDriverForCard"
                            :busy="driverCardBusy"
                            @change="$emit('driver-card-change')"
                        />
                    </div>
                    <ConflictBanner
                        class="mt-3"
                        :conflict="vehicleConflictBanner"
                        @pick-again="$emit('conflict-pick-again')"
                        @keep-anyway="$emit('conflict-keep')"
                    />
                </div>

                <div
                    class="rounded-[12px] border-[0.5px] border-slate-200/80 bg-slate-50/30 p-3 dark:border-slate-700/60 dark:bg-slate-900/35"
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
                        @update:resources="$emit('update:resources', $event)"
                        @create-vendor="$emit('create-vendor')"
                    />
                </div>
            </div>

            <div
                v-if="canAssign && overlappingOtherTrips.length"
                class="rounded-[12px] border-[0.5px] border-slate-200/80 bg-white p-2.5 dark:border-slate-700/60 dark:bg-slate-900/40"
            >
                <div
                    class="text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400"
                >
                    {{ t("trip_detail.coordination.overlap_section_title") }}
                </div>
                <ul
                    class="mt-1.5 max-h-36 space-y-1 overflow-y-auto text-[11px] font-normal"
                >
                    <li
                        v-for="row in overlappingOtherTrips"
                        :key="row.id"
                        class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5"
                    >
                        <RouterLink
                            :to="tripDetailPathFor(row.id)"
                            class="font-medium text-sky-700 underline-offset-2 hover:underline"
                        >
                            #{{ row.id }}
                        </RouterLink>
                        <span class="tabular-nums text-slate-600">{{
                            fmtTime(row.depart_at)
                        }}</span>
                        <span class="min-w-0 text-slate-700">{{
                            row.label
                        }}</span>
                        <span
                            v-if="row.driverName || row.vehiclePlate"
                            class="text-slate-500"
                        >
                            <template v-if="row.driverName">{{
                                row.driverName
                            }}</template>
                            <template v-if="row.driverName && row.vehiclePlate">
                                ·
                            </template>
                            <template v-if="row.vehiclePlate">{{
                                row.vehiclePlate
                            }}</template>
                        </span>
                    </li>
                </ul>
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
                class="border-t border-[0.5px] border-slate-100 pt-2.5 dark:border-slate-700/60"
            >
                <label
                    class="mb-1.5 block text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400"
                >
                    {{ t("trip_detail.coordination.internal_notes") }}
                </label>
                <textarea
                    :value="coordinationNotes"
                    rows="3"
                    class="w-full rounded-[12px] border-[0.5px] border-slate-200/90 bg-white px-2.5 py-2 text-sm font-normal outline-none ring-0 focus:border-[#8B1A1A]/35 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    :placeholder="
                        t('trip_detail.coordination.internal_notes_ph')
                    "
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
                        class="rounded-[12px] border-[0.5px] border-slate-300/90 bg-transparent px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800/80"
                        :disabled="assigning"
                        @click="emit('cancel')"
                    >
                        {{ t("trip_detail.coordination.footer_cancel") }}
                    </button>
                    <button
                        type="button"
                        class="min-w-0 flex-1 rounded-[12px] px-3 py-2 text-xs font-medium text-white disabled:cursor-not-allowed disabled:opacity-50 sm:min-w-[12rem] sm:flex-initial"
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
import { ref } from "vue";
import { RouterLink, useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import { ClockIcon } from "@heroicons/vue/24/outline";
import ResourcePanel from "../dispatch/ResourcePanel.vue";
import ConflictBanner, { type VehicleConflict } from "./ConflictBanner.vue";
import DriverCard from "./DriverCard.vue";
import VehicleCard from "./VehicleCard.vue";
import { buildStaffPrefixedPath as staffPath } from "../../config/dispatchWebBase";

const props = defineProps<{
    canAssign: boolean;
    canUpdateStatus: boolean;
    canRescheduleTrip: boolean;
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
}>();

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
