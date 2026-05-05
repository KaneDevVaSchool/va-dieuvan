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
            :class="embedded ? 'justify-between' : 'justify-between'"
        >
            <h2
                :class="
                    embedded
                        ? 'text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400'
                        : 'text-sm font-semibold text-slate-700 dark:text-slate-200'
                "
            >
                {{ t("trip_detail.status_block.title") }}
            </h2>
            <span
                class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="badgeClass"
            >
                {{ labelTripStatus(tripStatus) }}
            </span>
        </div>

        <template v-if="workflow.isTerminal">
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ workflow.hintI18nKey ? t(workflow.hintI18nKey) : "" }}
            </p>
        </template>

        <template v-else>
            <!-- Primary + secondary action buttons -->
            <div class="mt-2.5 flex flex-wrap items-center gap-2">
                <button
                    v-if="workflow.primaryAction?.kind === 'assign'"
                    type="button"
                    :disabled="primaryDisabled || assigning"
                    class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="$emit('assign')"
                >
                    <span
                        v-if="assigning"
                        class="mr-2 inline-block h-3 w-3 animate-spin rounded-full border border-white/50 border-t-white"
                        aria-hidden="true"
                    />
                    {{ t("trip_detail.status_workflow.assign_vehicle") }}
                </button>

                <button
                    v-else-if="
                        workflow.primaryAction?.kind === 'advance' &&
                        workflow.primaryAction.to === 'in_progress'
                    "
                    type="button"
                    :disabled="statusBusy"
                    class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="$emit('advance', 'in_progress')"
                >
                    <span
                        v-if="statusing"
                        class="mr-2 inline-block h-3 w-3 animate-spin rounded-full border border-white/50 border-t-white"
                        aria-hidden="true"
                    />
                    {{ t("trip_detail.status_workflow.start_trip") }}
                </button>

                <button
                    v-else-if="
                        workflow.primaryAction?.kind === 'advance' &&
                        workflow.primaryAction.to === 'completed'
                    "
                    type="button"
                    :disabled="statusBusy"
                    class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="$emit('advance', 'completed')"
                >
                    <span
                        v-if="statusing"
                        class="mr-2 inline-block h-3 w-3 animate-spin rounded-full border border-white/50 border-t-white"
                        aria-hidden="true"
                    />
                    {{ t("trip_detail.status_workflow.complete_trip") }}
                </button>

                <button
                    v-if="workflow.secondaryResumeTrip"
                    type="button"
                    :disabled="statusBusy"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-800 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                    @click="$emit('advance', 'in_progress')"
                >
                    <span
                        v-if="statusing"
                        class="mr-2 inline-block h-3 w-3 animate-spin rounded-full border border-slate-400 border-t-slate-800"
                        aria-hidden="true"
                    />
                    {{ t("trip_detail.status_workflow.resume_trip") }}
                </button>

                <button
                    v-if="workflow.showDestructiveCancel"
                    type="button"
                    :disabled="cancelBusy || statusBusy"
                    class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-50 dark:border-rose-900/70 dark:bg-rose-950/30 dark:text-rose-300 dark:hover:bg-rose-950/50"
                    @click="$emit('cancel')"
                >
                    <span
                        v-if="rejecting"
                        class="mr-2 inline-block h-3 w-3 animate-spin rounded-full border border-rose-300 border-t-rose-600"
                        aria-hidden="true"
                    />
                    {{ t("trip_detail.status_workflow.cancel_outlined") }}
                </button>
            </div>

            <!-- Hint -->
            <p
                v-if="workflow.hintI18nKey"
                class="mt-1.5 text-xs text-slate-500 dark:text-slate-400"
            >
                {{ t(workflow.hintI18nKey) }}
            </p>

            <!-- Note textarea (below buttons for clear reading order) -->
            <label class="mt-2.5 block">
                <span
                    class="mb-1 block text-xs font-semibold text-slate-500 dark:text-slate-400"
                >
                    {{ t("trip_detail.status_update.note") }}
                </span>
                <textarea
                    :value="modelValue"
                    rows="2"
                    class="block w-full resize-none rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500"
                    :placeholder="t('trip_detail.status_update.note_ph')"
                    @input="onNoteInputFromEvent"
                />
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
