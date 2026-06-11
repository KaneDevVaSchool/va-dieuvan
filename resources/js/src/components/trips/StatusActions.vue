<template>
    <section
        :class="
            embedded
                ? ''
                : 'overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-3 dark:border-slate-700/80 dark:bg-slate-900/45'
        "
        :aria-label="t('trip_detail.status_block.title')"
    >
        <!-- Header: title + status badge -->
        <div
            class="flex flex-wrap items-center gap-2"
            :class="hideSectionTitle ? 'justify-end' : 'justify-between'"
        >
            <h2
                v-if="!hideSectionTitle"
                :class="
                    embedded
                        ? 'text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400'
                        : 'text-sm font-semibold text-slate-700 dark:text-slate-200'
                "
            >
                {{ t("trip_detail.status_block.title") }}
            </h2>
            <!-- Status badge with dot indicator -->
            <div
                class="flex items-center gap-1.5 rounded-full px-2.5 py-0.5"
                :class="badgeClass"
                aria-live="polite"
            >
                <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70" aria-hidden="true" />
                <span class="text-xs font-medium">{{ labelTripStatus(tripStatus) }}</span>
            </div>
        </div>

        <template v-if="workflow.isTerminal">
            <div
                v-if="workflow.hintI18nKey"
                class="mt-2.5 flex items-start gap-2 rounded-lg bg-slate-100/80 px-3 py-2.5 dark:bg-slate-800/50"
            >
                <CheckCircleIcon
                    v-if="tripStatus === 'completed'"
                    class="mt-0.5 size-4 shrink-0 text-emerald-500 dark:text-emerald-400"
                    aria-hidden="true"
                />
                <NoSymbolIcon
                    v-else
                    class="mt-0.5 size-4 shrink-0 text-slate-400 dark:text-slate-500"
                    aria-hidden="true"
                />
                <p class="text-xs leading-relaxed text-slate-600 dark:text-slate-400">
                    {{ t(workflow.hintI18nKey) }}
                </p>
            </div>
        </template>

        <template v-else>
            <!-- Action control bar: primary/secondary left, destructive right -->
            <div
                class="mt-3 flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-2 dark:border-slate-700 dark:bg-slate-800/50"
            >
                <div class="flex items-center gap-2">
                    <!-- Assign vehicle (primary) -->
                    <button
                        v-if="workflow.primaryAction?.kind === 'assign'"
                        type="button"
                        :disabled="primaryDisabled || assigning"
                        :aria-busy="assigning"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-150 hover:bg-blue-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500/30 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="$emit('assign')"
                    >
                        <template v-if="assigning">
                            <span class="h-3 w-3 animate-spin rounded-full border border-current border-t-transparent" aria-hidden="true" />
                            {{ t("common.processing") }}
                        </template>
                        <template v-else>
                            <TruckIcon class="size-3.5 shrink-0" aria-hidden="true" />
                            {{ t("trip_detail.status_workflow.assign_vehicle") }}
                        </template>
                    </button>

                    <!-- Start trip (primary) -->
                    <button
                        v-else-if="
                            workflow.primaryAction?.kind === 'advance' &&
                            workflow.primaryAction.to === 'in_progress'
                        "
                        type="button"
                        :disabled="statusBusy"
                        :aria-busy="statusing"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-150 hover:bg-blue-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500/30 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="$emit('advance', 'in_progress')"
                    >
                        <template v-if="statusing">
                            <span class="h-3 w-3 animate-spin rounded-full border border-current border-t-transparent" aria-hidden="true" />
                            {{ t("common.processing") }}
                        </template>
                        <template v-else>
                            <PlayIcon class="size-3.5 shrink-0" aria-hidden="true" />
                            {{ t("trip_detail.status_workflow.start_trip") }}
                        </template>
                    </button>

                    <!-- Complete trip (primary) -->
                    <button
                        v-else-if="
                            workflow.primaryAction?.kind === 'advance' &&
                            workflow.primaryAction.to === 'completed'
                        "
                        type="button"
                        :disabled="statusBusy"
                        :aria-busy="statusing"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-150 hover:bg-blue-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500/30 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="$emit('advance', 'completed')"
                    >
                        <template v-if="statusing">
                            <span class="h-3 w-3 animate-spin rounded-full border border-current border-t-transparent" aria-hidden="true" />
                            {{ t("common.processing") }}
                        </template>
                        <template v-else>
                            <CheckIcon class="size-3.5 shrink-0" aria-hidden="true" />
                            {{ t("trip_detail.status_workflow.complete_trip") }}
                        </template>
                    </button>

                    <!-- Resume trip (secondary) -->
                    <button
                        v-if="workflow.secondaryResumeTrip"
                        type="button"
                        :disabled="statusBusy"
                        :aria-busy="statusing"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-transparent px-3 py-2 text-xs font-semibold text-slate-700 transition-all duration-150 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-300/30 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                        @click="$emit('advance', 'in_progress')"
                    >
                        <template v-if="statusing">
                            <span class="h-3 w-3 animate-spin rounded-full border border-current border-t-transparent" aria-hidden="true" />
                            {{ t("common.processing") }}
                        </template>
                        <template v-else>
                            <ArrowPathIcon class="size-3.5 shrink-0" aria-hidden="true" />
                            {{ t("trip_detail.status_workflow.resume_trip") }}
                        </template>
                    </button>
                </div>

                <!-- Destructive: cancel (right) -->
                <button
                    v-if="workflow.showDestructiveCancel"
                    type="button"
                    :disabled="cancelBusy || statusBusy"
                    :aria-busy="rejecting"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-rose-600 transition-all duration-150 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-400/30 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 dark:text-rose-400 dark:hover:bg-rose-900/30"
                    @click="$emit('cancel')"
                >
                    <template v-if="rejecting">
                        <span class="h-3 w-3 animate-spin rounded-full border border-current border-t-transparent" aria-hidden="true" />
                        {{ t("common.processing") }}
                    </template>
                    <template v-else>
                        <XMarkIcon class="size-3.5 shrink-0" aria-hidden="true" />
                        {{ t("trip_detail.status_workflow.cancel_outlined") }}
                    </template>
                </button>
            </div>

            <!-- Hint guidance block -->
            <div
                v-if="workflow.hintI18nKey"
                class="mt-2 flex items-start gap-2 rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
            >
                <span aria-hidden="true">ℹ️</span>
                <span>{{ t(workflow.hintI18nKey) }}</span>
            </div>

            <!-- Note textarea -->
            <label class="mt-3 block" for="trip-note">
                <span class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400">
                    {{ t('trip_detail.status_update.note') }}
                </span>
                <textarea
                    id="trip-note"
                    :value="modelValue"
                    rows="2"
                    class="block w-full resize-none rounded-xl border border-slate-200 bg-transparent px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:border-slate-700 dark:text-slate-100 dark:placeholder:text-slate-500"
                    :placeholder="t('trip_detail.status_update.note_ph')"
                    @input="onNoteInputFromEvent"
                />
                <p class="mt-1 text-[11px] text-slate-400">
                    {{ t('trip_detail.status_update.note_hint') }}
                </p>
            </label>
        </template>

        <span
            v-if="
                !canAssign &&
                !canUpdateStatus &&
                !workflow.isTerminal &&
                !workflow.hintI18nKey
            "
            class="mt-2 block text-xs text-slate-500"
        >
            {{ t("trip_detail.coordination.no_permission_assign") }}
        </span>
    </section>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import {
    ArrowPathIcon,
    CheckCircleIcon,
    CheckIcon,
    NoSymbolIcon,
    PlayIcon,
    TruckIcon,
    XMarkIcon,
} from "@heroicons/vue/24/outline";
import { labelTripStatus } from "../../util/labels";
import {
    resolveTripStatusWorkflow,
    tripStatusBadgeClass,
} from "../../composables/useTripStatusWorkflow";

const props = defineProps<{
    tripStatus?: string | null;
    canAssign: boolean;
    canUpdateStatus: boolean;
    assignReady: boolean;
    assigning: boolean;
    rejecting: boolean;
    statusing: boolean;
    modelValue: string;
    embedded?: boolean;
    /** Parent card already shows section title */
    hideSectionTitle?: boolean;
}>();

const emit = defineEmits<{
    assign: [];
    advance: ["in_progress" | "completed"];
    cancel: [];
    "update:modelValue": [v: string];
}>();

function onNoteInputFromEvent(ev: Event) {
    emit("update:modelValue", (ev.target as HTMLTextAreaElement).value);
}

const { t } = useI18n();

const workflow = computed(() =>
    resolveTripStatusWorkflow({
        tripStatus: props.tripStatus,
        canAssign: props.canAssign,
        assignReady: props.assignReady,
        assigning: props.assigning,
        canUpdateStatus: props.canUpdateStatus,
    }),
);

const badgeClass = computed(() => tripStatusBadgeClass(props.tripStatus));

const cancelBusy = computed(() => props.rejecting);
const statusBusy = computed(() => props.statusing);

const primaryDisabled = computed(() => {
    const w = workflow.value;
    if (w.primaryAction?.kind !== "assign") return false;
    return !props.assignReady || props.assigning;
});
</script>
