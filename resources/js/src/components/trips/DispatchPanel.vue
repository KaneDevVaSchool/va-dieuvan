<template>
    <section
        class="flex flex-col rounded-2xl bg-white shadow-sm shadow-slate-900/5 print:hidden dark:bg-slate-950/40 dark:shadow-black/25"
        :aria-label="t('trip_detail.coordination.title')"
    >
        <div
            class="space-y-3 p-3"
            data-testid="dispatch-panel-scroll"
        >
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

            <!-- Summary bar: cần · đã phân bổ · thiếu/dư (scan 3 giây) -->
            <div
                v-if="!coordinationActionsLocked"
                class="grid grid-cols-2 gap-px overflow-hidden rounded-2xl shadow-sm sm:grid-cols-4"
                :class="
                    seatsMet
                        ? 'bg-emerald-200/60 dark:bg-emerald-900/40'
                        : 'bg-amber-200/60 dark:bg-amber-900/40'
                "
                role="status"
                data-testid="dispatch-summary-bar"
            >
                <div
                    class="px-3 py-2.5"
                    :class="seatsMet ? 'bg-emerald-50 dark:bg-emerald-950/40' : 'bg-[#FAEEDA] dark:bg-amber-950/30'"
                >
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ t('trip_detail.coordination.summary_passengers') }}
                    </div>
                    <div class="mt-0.5 text-[15px] font-bold tabular-nums text-slate-900 dark:text-slate-100">
                        {{ t('trip_detail.coordination.summary_people_n', { n: passengerCount }) }}
                    </div>
                </div>
                <div
                    class="px-3 py-2.5"
                    :class="seatsMet ? 'bg-emerald-50 dark:bg-emerald-950/40' : 'bg-[#FAEEDA] dark:bg-amber-950/30'"
                >
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ t('trip_detail.coordination.summary_seats_needed') }}
                    </div>
                    <div class="mt-0.5 text-[15px] font-bold tabular-nums text-slate-900 dark:text-slate-100">
                        {{ neededSeats }}
                    </div>
                </div>
                <div
                    class="px-3 py-2.5"
                    :class="seatsMet ? 'bg-emerald-50 dark:bg-emerald-950/40' : 'bg-[#FAEEDA] dark:bg-amber-950/30'"
                >
                    <div class="text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ t('trip_detail.coordination.summary_allocated') }}
                    </div>
                    <div class="mt-0.5 text-[15px] font-bold tabular-nums text-slate-900 dark:text-slate-100">
                        {{ allocatedSeats }}
                    </div>
                </div>
                <div
                    class="px-3 py-2.5"
                    :class="seatsMet ? 'bg-emerald-50 dark:bg-emerald-950/40' : 'bg-[#FAEEDA] dark:bg-amber-950/30'"
                >
                    <div
                        class="text-[10px] font-semibold uppercase tracking-wide"
                        :class="seatsMet ? 'text-emerald-700 dark:text-emerald-400' : 'text-[#854F0B] dark:text-[#F2C07D]'"
                    >
                        {{ seatsMet ? t('trip_detail.coordination.summary_surplus') : t('trip_detail.coordination.summary_short') }}
                    </div>
                    <div
                        class="mt-0.5 flex items-center gap-1 text-[15px] font-bold tabular-nums"
                        :class="seatsMet ? 'text-emerald-700 dark:text-emerald-300' : 'text-[#854F0B] dark:text-[#F2C07D]'"
                    >
                        <ExclamationTriangleIcon
                            v-if="!seatsMet"
                            class="size-3.5 shrink-0"
                            aria-hidden="true"
                        />
                        {{ seatsMet ? surplusSeats : shortfallSeats }}
                    </div>
                </div>
            </div>

            <div
                v-if="scheduleAssignTabs && scheduleAssignTabs.length > 1"
                class="space-y-2"
            >
                <p
                    v-if="assignProgressLabel"
                    class="text-[12px] font-medium text-slate-600 dark:text-slate-400"
                >
                    {{ assignProgressLabel }}
                </p>
                <div
                    class="flex gap-1.5 overflow-x-auto pb-0.5 scrollbar-hidden"
                    role="tablist"
                    :aria-label="t('trip_detail.schedules.assign_tabs_aria')"
                >
                    <button
                        v-for="tab in scheduleAssignTabs"
                        :key="tab.key"
                        type="button"
                        role="tab"
                        class="shrink-0 rounded-full px-3 py-1.5 text-[12px] font-semibold transition"
                        :class="
                            activeScheduleKey === tab.key
                                ? 'bg-[#8B1A1A] text-white shadow-sm'
                                : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200'
                        "
                        :aria-selected="activeScheduleKey === tab.key"
                        @click="$emit('update:activeScheduleKey', tab.key)"
                    >
                        {{ tab.label }}
                        <span
                            v-if="tab.assigned"
                            class="ml-1 inline-block size-1.5 rounded-full bg-emerald-400"
                            aria-hidden="true"
                        />
                    </button>
                </div>
            </div>

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
                    :hide-internal-vehicle-section="coordinationActionsLocked"
                    :hide-internal-driver-section="coordinationActionsLocked"
                    :disabled="coordinationActionsLocked"
                    @update:resources="$emit('update:resources', $event)"
                    @update:capacity="onCapacityUpdate"
                    @create-vendor="$emit('create-vendor')"
                />
            </div>

            <ConflictBanner
                v-if="vehicleConflictBanner"
                class="block"
                :conflict="vehicleConflictBanner"
                :disabled="coordinationActionsLocked"
                @pick-again="$emit('conflict-pick-again')"
                @keep-anyway="$emit('conflict-keep')"
            />

            <!-- Chỉ đọc sau khi đã gán — tránh trùng với picker phía trên -->
            <CollapsiblePanelSection
                v-if="coordinationActionsLocked && (hasCurrentAssignmentList || showAssignmentSupplementsPanel)"
                :title="
                    t('trip_detail.coordination.current_assignment_section')
                "
                :summary-collapsed="assignmentCollapsedSummary"
                :persist-key="collapseStorageKey('assignment')"
            >
                <div class="space-y-3">
                    <CurrentAssignmentList
                        v-if="hasCurrentAssignmentList"
                        :vehicles="assignmentVehicles"
                        :drivers="assignmentDrivers"
                        :allow-change="false"
                    />
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
                </div>
            </CollapsiblePanelSection>

            <!-- Section 4 · Kiểm tra năng lực — nơi DUY NHẤT hiện cảnh báo -->
            <div
                v-if="!coordinationActionsLocked"
                class="rounded-2xl border border-slate-200/80 bg-white px-3 py-2.5 shadow-sm dark:border-slate-800 dark:bg-slate-900/50"
                role="status"
                data-testid="dispatch-validation-card"
            >
                <div class="mb-1.5 text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400">
                    {{ t('trip_detail.coordination.validation_section_title') }}
                </div>
                <ul class="space-y-1">
                    <li class="flex items-center gap-2 text-[13px]">
                        <component
                            :is="capacity.hasVehicle ? CheckCircleIcon : ExclamationTriangleIcon"
                            class="size-4 shrink-0"
                            :class="capacity.hasVehicle ? 'text-emerald-500' : 'text-[#EF9F27]'"
                            aria-hidden="true"
                        />
                        <span :class="capacity.hasVehicle ? 'text-slate-700 dark:text-slate-200' : 'text-[#854F0B] dark:text-[#F2C07D]'">
                            {{ capacity.hasVehicle ? t('trip_detail.coordination.validation_has_vehicle') : t('trip_detail.coordination.validation_no_vehicle') }}
                        </span>
                    </li>
                    <li class="flex items-center gap-2 text-[13px]">
                        <component
                            :is="capacity.hasDriver ? CheckCircleIcon : ExclamationTriangleIcon"
                            class="size-4 shrink-0"
                            :class="capacity.hasDriver ? 'text-emerald-500' : 'text-[#EF9F27]'"
                            aria-hidden="true"
                        />
                        <span :class="capacity.hasDriver ? 'text-slate-700 dark:text-slate-200' : 'text-[#854F0B] dark:text-[#F2C07D]'">
                            {{ capacity.hasDriver ? t('trip_detail.coordination.validation_has_driver') : t('trip_detail.coordination.validation_no_driver') }}
                        </span>
                    </li>
                    <li class="flex items-center gap-2 text-[13px]">
                        <component
                            :is="seatsMet ? CheckCircleIcon : ExclamationTriangleIcon"
                            class="size-4 shrink-0"
                            :class="seatsMet ? 'text-emerald-500' : 'text-[#EF9F27]'"
                            aria-hidden="true"
                        />
                        <span :class="seatsMet ? 'text-slate-700 dark:text-slate-200' : 'text-[#854F0B] dark:text-[#F2C07D]'">
                            {{ seatsMet ? t('trip_detail.coordination.validation_seats_ok') : t('trip_detail.coordination.validation_seats_short', { n: shortfallSeats }) }}
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Toast thành công sau khi gán -->
            <div
                v-if="assignMsg && assignFeedbackKind === 'success'"
                class="rounded-xl bg-emerald-50 px-3 py-2 text-[13px] font-medium leading-snug text-emerald-950 shadow-sm dark:bg-emerald-950/35 dark:text-emerald-50"
                role="status"
            >
                {{ assignMsg }}
            </div>
            <div
                v-else-if="assignMsg && assignFeedbackKind === 'error'"
                class="rounded-xl bg-rose-50 px-3 py-2 text-[13px] font-medium leading-snug text-rose-950 shadow-sm dark:bg-rose-950/40 dark:text-rose-50"
                role="alert"
            >
                {{ assignMsg }}
            </div>

            <!-- Section 5 · Ghi chú (phẳng, không collapse) -->
            <div>
                <label
                    class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-500 dark:text-slate-400"
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
            </div>

            <p
                v-if="!canAssign && !canUpdateStatus"
                class="text-xs font-normal text-slate-500"
            >
                {{ t("trip_detail.coordination.no_permission_assign") }}
            </p>

        </div>

        <div
            v-if="showAssignFooter"
            class="shrink-0 border-t border-slate-200/90 bg-white/95 px-3.5 py-3 shadow-[0_-8px_24px_-6px_rgba(15,23,42,0.12)] backdrop-blur-md supports-[backdrop-filter]:bg-white/90 dark:border-slate-700/80 dark:bg-slate-950/95 dark:shadow-[0_-8px_24px_-6px_rgba(0,0,0,0.4)]"
            style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))"
            data-testid="dispatch-assign-footer"
        >
            <div class="mb-2 flex items-center justify-between gap-3">
                <div class="flex items-baseline gap-1.5">
                    <span class="text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        {{ t('trip_detail.coordination.summary_allocated') }}
                    </span>
                    <span class="text-[14px] font-bold tabular-nums text-slate-900 dark:text-slate-100">
                        {{ allocatedSeats }}/{{ neededSeats }}
                    </span>
                </div>
                <span
                    v-if="!seatsMet"
                    class="rounded-full bg-[#FAEEDA] px-2.5 py-0.5 text-[11px] font-semibold tabular-nums text-[#854F0B] dark:bg-amber-950/45 dark:text-[#F2C07D]"
                >
                    {{ t('trip_detail.coordination.summary_short') }} {{ shortfallSeats }}
                </span>
            </div>
            <button
                type="button"
                class="w-full rounded-xl bg-[#8B1A1A] px-3 py-2.5 text-[12px] font-semibold text-white shadow-md shadow-[#8B1A1A]/25 outline-none hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-45 dark:shadow-[#8B1A1A]/30"
                :disabled="assigning || !assignReady"
                data-testid="dispatch-confirm-assign"
                @click="onConfirmAssignClick"
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
        </div>

        <!-- Modal xác nhận khi còn thiếu chỗ -->
        <Teleport to="body">
            <div
                v-if="showShortConfirm"
                class="fixed inset-0 z-[330] flex items-end justify-center bg-black/45 p-0 sm:items-center sm:p-4"
                role="dialog"
                aria-modal="true"
                :aria-label="t('trip_detail.coordination.confirm_short_title')"
                @click.self="showShortConfirm = false"
            >
                <div class="w-full max-w-sm overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl dark:bg-slate-900">
                    <div class="flex items-start gap-2.5 px-4 pt-4">
                        <ExclamationTriangleIcon class="mt-0.5 size-5 shrink-0 text-[#EF9F27]" aria-hidden="true" />
                        <div>
                            <h2 class="text-[14px] font-semibold text-slate-900 dark:text-slate-100">
                                {{ t('trip_detail.coordination.confirm_short_title') }}
                            </h2>
                            <p class="mt-1 text-[13px] leading-relaxed text-slate-600 dark:text-slate-300">
                                {{ t('trip_detail.coordination.confirm_short_body', { n: shortfallSeats }) }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2 px-4 pb-4">
                        <button
                            type="button"
                            class="flex-1 rounded-xl bg-slate-100 px-3 py-2.5 text-[12px] font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                            @click="showShortConfirm = false"
                        >
                            {{ t('trip_detail.coordination.confirm_cancel') }}
                        </button>
                        <button
                            type="button"
                            class="flex-1 rounded-xl bg-[#8B1A1A] px-3 py-2.5 text-[12px] font-semibold text-white shadow-md shadow-[#8B1A1A]/25 hover:brightness-105"
                            data-testid="dispatch-confirm-short-continue"
                            @click="onShortConfirmContinue"
                        >
                            {{ t('trip_detail.coordination.confirm_continue') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </section>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import {
    CheckCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
} from "@heroicons/vue/24/outline";
import CollapsiblePanelSection from "../dispatch/CollapsiblePanelSection.vue";
import ResourcePanel from "../dispatch/ResourcePanel.vue";
import ConflictBanner, { type VehicleConflict } from "./ConflictBanner.vue";
import CurrentAssignmentList, {
    type AssignmentListDriver,
    type AssignmentListVehicle,
} from "./CurrentAssignmentList.vue";
import type { SupplementItem } from "../../types/dispatch";

const props = withDefaults(
    defineProps<{
    canAssign: boolean;
    canUpdateStatus: boolean;
    /** Chuyến đã qua bước gán trên timeline — khóa chỉnh sửa panel điều phối */
    coordinationActionsLocked?: boolean;
    canQuickCreateProvider: boolean;
    assignmentVehicles: AssignmentListVehicle[];
    assignmentDrivers: AssignmentListDriver[];
    vehicleConflictBanner: VehicleConflict | null;
    tripId: number | null;
    scheduleDateKeyForList: string;
    neededSeats: number;
    /** Số hành khách của chuyến — hiển thị ở summary bar */
    passengerCount: number;
    suitableVehiclesCount: number;
    busyVehicleIds: number[];
    busyDriverIds: number[];
    tripSnapshot: object | null;
    coordinationNotes: string;
    assignMsg: string;
    assignFeedbackKind: string;
    capacityBannerText: string;
    showAssignFooter: boolean;
    assignReady: boolean;
    assigning: boolean;
    supplementAssignments?:
        | { taxis: SupplementItem[]; vendors: SupplementItem[] }
        | null;
    scheduleAssignTabs?: { key: string; label: string; assigned: boolean }[];
    activeScheduleKey?: string;
    assignProgressLabel?: string;
}>(),
    {
        coordinationActionsLocked: false,
        assignmentVehicles: () => [],
        assignmentDrivers: () => [],
        supplementAssignments: null,
        scheduleAssignTabs: () => [],
        activeScheduleKey: "",
        assignProgressLabel: "",
    },
);

const emit = defineEmits<{
    "vehicle-card-change": [];
    "driver-card-change": [];
    "conflict-pick-again": [];
    "conflict-keep": [];
    "update:resources": [payload: unknown];
    "create-vendor": [];
    "update:coordinationNotes": [value: string];
    assign: [];
    "update:activeScheduleKey": [key: string];
}>();

const { t } = useI18n();

const resourcePanelRef = ref<InstanceType<typeof ResourcePanel> | null>(null);
defineExpose({ resourcePanel: resourcePanelRef });

function collapseStorageKey(segment: string): string | null {
    const id = props.tripId;
    if (id == null || id === "") return null;
    return `va-trip-${String(id)}-dispatch-${segment}`;
}

type CapacityState = {
    allocatedSeats: number;
    hasVehicle: boolean;
    hasDriver: boolean;
};

const capacity = ref<CapacityState>({
    allocatedSeats: 0,
    hasVehicle: false,
    hasDriver: false,
});

function onCapacityUpdate(payload: CapacityState) {
    capacity.value = {
        allocatedSeats: Number(payload?.allocatedSeats) || 0,
        hasVehicle: Boolean(payload?.hasVehicle),
        hasDriver: Boolean(payload?.hasDriver),
    };
}

const allocatedSeats = computed(() => capacity.value.allocatedSeats);
const seatsMet = computed(() => allocatedSeats.value >= props.neededSeats);
const shortfallSeats = computed(() =>
    Math.max(0, props.neededSeats - allocatedSeats.value),
);
const surplusSeats = computed(() =>
    Math.max(0, allocatedSeats.value - props.neededSeats),
);

const showShortConfirm = ref(false);

function onConfirmAssignClick() {
    if (props.assigning || !props.assignReady) return;
    if (shortfallSeats.value > 0) {
        showShortConfirm.value = true;
        return;
    }
    emit("assign");
}

function onShortConfirmContinue() {
    showShortConfirm.value = false;
    emit("assign");
}

function formatSupplementLine(it: SupplementItem): string {
    const lab = String(it.label ?? "").trim();
    const n = Number(it.supplementSeats);
    let base = lab;
    if (Number.isFinite(n) && n > 0) {
        base = `${lab} — ${t("trip_detail.coordination.seats_n", {
            n: Math.floor(n),
        })}`;
    }
    const plate = String(it.externalVehicleRef ?? "").trim();
    if (plate) {
        base = base ? `${base} · ${plate}` : plate;
    }
    const priceRaw = Number(it.servicePrice);
    if (Number.isFinite(priceRaw) && priceRaw >= 0) {
        const priceSeg = t("trip_detail.coordination.supplement_taxi_price_summary", {
            n: priceRaw,
        });
        base = base ? `${base} · ${priceSeg}` : priceSeg;
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

const hasCurrentAssignmentList = computed(
    () =>
        (props.assignmentVehicles?.length ?? 0) > 0 ||
        (props.assignmentDrivers?.length ?? 0) > 0,
);

/** Khi đã chọn tài xế nội bộ — bổ sung taxi/NCC gộp vào khối phân công. */
const showAssignmentSupplementsPanel = computed(() => {
    if ((props.assignmentDrivers?.length ?? 0) === 0) return false;
    return supplementAssignmentsFlattened.value.length > 0;
});

const assignmentCollapsedSummary = computed(() => {
    const bits: string[] = [];
    const vn = props.assignmentVehicles?.length ?? 0;
    const dn = props.assignmentDrivers?.length ?? 0;
    if (vn) {
        const first = props.assignmentVehicles[0]?.license_plate?.trim();
        const plateFallback = t("trip_detail.empty.plate");
        bits.push(
            vn > 1
                ? t("trip_detail.coordination.assignment_list_vehicles_short", {
                      n: vn,
                      plate: first || plateFallback,
                  })
                : first || plateFallback,
        );
    }
    if (dn) {
        const first = props.assignmentDrivers[0]?.full_name?.trim();
        const nameFallback = t("trip_detail.empty.driver");
        bits.push(
            dn > 1
                ? t("trip_detail.coordination.assignment_list_drivers_short", {
                      n: dn,
                      name: first || nameFallback,
                  })
                : first || nameFallback,
        );
    }
    if (
        showAssignmentSupplementsPanel.value &&
        supplementAssignmentsFlattened.value.length > 0
    )
        bits.push(`+${supplementAssignmentsFlattened.value.length}`);
    return bits.length
        ? bits.join(" · ")
        : t("trip_detail.coordination.assignment_collapsed_empty");
});

function onCoordNotesInput(e: Event) {
    emit("update:coordinationNotes", (e.target as HTMLTextAreaElement).value);
}
</script>
