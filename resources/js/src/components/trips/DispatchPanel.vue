<template>
    <section
        class="flex min-h-0 flex-col overflow-hidden rounded-2xl bg-white shadow-sm shadow-slate-900/5 print:hidden dark:bg-slate-950/40 dark:shadow-black/25"
        :aria-label="t('trip_detail.coordination.title')"
    >
        <div
            class="min-h-0 flex-1 space-y-3 overflow-y-auto overscroll-y-contain scrollbar-hidden p-3"
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

        </div>

        <div
            v-if="showAssignFooter"
            class="shrink-0 border-t border-slate-200/90 bg-white/95 px-3.5 py-3 shadow-[0_-8px_24px_-6px_rgba(15,23,42,0.12)] backdrop-blur-md supports-[backdrop-filter]:bg-white/90 dark:border-slate-700/80 dark:bg-slate-950/95 dark:shadow-[0_-8px_24px_-6px_rgba(0,0,0,0.4)]"
            style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))"
            data-testid="dispatch-assign-footer"
        >
            <button
                type="button"
                class="w-full rounded-xl bg-[#8B1A1A] px-3 py-2.5 text-[12px] font-semibold text-white shadow-md shadow-[#8B1A1A]/25 outline-none hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-45 dark:shadow-[#8B1A1A]/30"
                :disabled="assigning || !assignReady"
                data-testid="dispatch-confirm-assign"
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
        </div>

    </section>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useI18n } from "vue-i18n";
import {
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

const notesCollapsedSummary = computed(() => {
    const n = props.coordinationNotes?.trim()?.length ?? 0;
    if (!n)
        return t("trip_detail.coordination.notes_collapsed_empty");
    return t("trip_detail.coordination.notes_chars_summary", { n });
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
