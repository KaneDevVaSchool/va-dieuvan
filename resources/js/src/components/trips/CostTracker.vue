<template>
    <section
        class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm print:break-inside-avoid dark:border-slate-700/80 dark:bg-slate-950/40"
        :aria-label="t('trip_detail.costs_block.title')"
        data-testid="trip-cost-tracker"
    >
        <!-- Header -->
        <div
            class="border-b border-slate-100 bg-slate-50/80 px-4 py-3 dark:border-slate-700/80 dark:bg-slate-900/50 sm:px-5 sm:py-4"
        >
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="min-w-0">
                    <h2
                        class="text-sm font-semibold text-slate-800 dark:text-slate-100"
                    >
                        {{ t("trip_detail.costs_block.title") }}
                    </h2>
                    <p
                        class="mt-0.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400"
                    >
                        {{ t("trip_detail.costs_block.subtitle") }}
                    </p>
                </div>
                <div
                    class="flex shrink-0 flex-wrap items-center justify-end gap-2"
                >
                    <RouterLink
                        v-if="showCostsLink"
                        to="/costs"
                        class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-200 transition hover:bg-blue-50 dark:text-blue-400 dark:ring-blue-900/70 dark:hover:bg-blue-950/50"
                        data-testid="trip-costs-view-all"
                    >
                        {{ t("trip_detail.costs.view_all") }}
                    </RouterLink>
                    <button
                        v-if="canSubmit"
                        type="button"
                        class="rounded-lg px-2.5 py-1.5 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                        :class="
                            showQuickAdd
                                ? 'bg-slate-200 text-slate-800 dark:bg-slate-700 dark:text-slate-100'
                                : 'bg-blue-600 text-white hover:bg-blue-700'
                        "
                        data-testid="trip-costs-toggle-add"
                        @click="toggleQuickAdd"
                    >
                        {{
                            showQuickAdd
                                ? t("trip_detail.costs.add_btn_cancel")
                                : t("trip_detail.costs.add_btn")
                        }}
                    </button>
                </div>
            </div>

            <!-- Summary row -->
            <dl
                class="mt-3 flex flex-wrap gap-2"
                :aria-label="t('trip_detail.costs.summary_aria')"
            >
                <div
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200/90 bg-white px-2.5 py-1 dark:border-slate-600 dark:bg-slate-900/80"
                >
                    <dt class="text-[11px] font-medium text-slate-500 dark:text-slate-400">
                        {{ t("trip_detail.costs.summary_count_label") }}
                    </dt>
                    <dd
                        class="text-xs font-bold tabular-nums text-slate-800 dark:text-slate-100"
                    >
                        {{ (costs ?? []).length }}
                    </dd>
                </div>
                <div
                    class="inline-flex min-w-0 items-center gap-1.5 rounded-lg border border-emerald-200/90 bg-emerald-50/80 px-2.5 py-1 dark:border-emerald-900/50 dark:bg-emerald-950/30"
                >
                    <dt class="text-[11px] font-medium text-emerald-800/80 dark:text-emerald-300/90">
                        {{ t("trip_detail.costs.summary_approved_label") }}
                    </dt>
                    <dd
                        class="truncate text-xs font-bold tabular-nums text-emerald-900 dark:text-emerald-100"
                    >
                        {{ approvedTotalFmt }}
                    </dd>
                </div>
                <div
                    v-if="pendingSum > 0"
                    class="inline-flex min-w-0 items-center gap-1.5 rounded-lg border border-amber-200/90 bg-amber-50/80 px-2.5 py-1 dark:border-amber-900/50 dark:bg-amber-950/30"
                >
                    <dt class="text-[11px] font-medium text-amber-800/80 dark:text-amber-300/90">
                        {{ t("trip_detail.costs.summary_pending_label") }}
                    </dt>
                    <dd
                        class="truncate text-xs font-bold tabular-nums text-amber-900 dark:text-amber-100"
                    >
                        {{ pendingTotalFmt }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="px-4 py-3 sm:px-5 sm:py-4">
            <!-- Quick-add form -->
            <Transition
                enter-active-class="transition-all duration-150 ease-out"
                enter-from-class="opacity-0 -translate-y-1"
                leave-active-class="transition-all duration-100 ease-in"
                leave-to-class="opacity-0 -translate-y-1"
            >
                <div
                    v-if="showQuickAdd && canSubmit"
                    class="mb-4 rounded-xl border border-blue-200/80 bg-blue-50/40 p-3 dark:border-blue-900/50 dark:bg-blue-950/20"
                    data-testid="trip-costs-quick-form"
                    @dragover.prevent
                    @drop.prevent="onDropPending"
                >
                    <p
                        class="text-xs font-semibold text-slate-700 dark:text-slate-200"
                    >
                        {{ t("trip_detail.costs.quick_title") }}
                    </p>

                    <div
                        class="mt-2 flex flex-wrap gap-1.5"
                        role="radiogroup"
                        :aria-label="t('trip_detail.costs.quick_type')"
                    >
                        <button
                            v-for="opt in COST_TRACKER_TYPES"
                            :key="opt.value"
                            type="button"
                            role="radio"
                            :aria-checked="selectedType === opt.value"
                            class="rounded-full px-2.5 py-1 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                            :class="chipClass(opt.value)"
                            :data-testid="`trip-cost-type-${opt.value}`"
                            @click="selectedType = opt.value"
                        >
                            {{ t(opt.labelKey) }}
                        </button>
                    </div>

                    <div
                        class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-[minmax(7rem,8rem)_1fr_auto]"
                    >
                        <label class="block min-w-0">
                            <span
                                class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                            >
                                {{ t("trip_detail.costs.quick_amount") }}
                            </span>
                            <input
                                id="trip-quick-cost-amt"
                                ref="amountInputRef"
                                v-model="amount"
                                type="number"
                                min="0"
                                step="1"
                                :placeholder="
                                    t('trip_detail.costs.quick_amount_ph')
                                "
                                autocomplete="transaction-amount"
                                class="h-9 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-sm tabular-nums text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                data-testid="trip-cost-amount-input"
                            />
                        </label>
                        <label class="block min-w-0 sm:col-span-1">
                            <span
                                class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                            >
                                {{ t("trip_detail.costs.quick_desc") }}
                            </span>
                            <input
                                id="trip-quick-cost-desc"
                                v-model="description"
                                type="text"
                                :placeholder="
                                    t('trip_detail.costs.quick_desc_ph')
                                "
                                class="h-9 w-full rounded-lg border border-slate-200 bg-white px-2.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                data-testid="trip-cost-desc-input"
                            />
                        </label>
                        <div
                            class="flex items-end gap-2 sm:flex-col sm:items-stretch sm:justify-end"
                        >
                            <input
                                ref="pendingFileRef"
                                type="file"
                                accept="image/*,application/pdf"
                                class="hidden"
                                data-testid="trip-cost-receipt-file"
                                @change="onPendingFile"
                            />
                            <button
                                type="button"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-600 dark:bg-slate-900"
                                :aria-label="
                                    t('trip_detail.costs.attach_receipt_aria')
                                "
                                data-testid="trip-cost-attach-receipt"
                                @click="pendingFileRef?.click()"
                            >
                                <PaperClipIcon class="h-4 w-4" />
                            </button>
                            <Button
                                type="button"
                                variant="primary"
                                class="!h-9 !shrink-0 !px-4 !py-0 !text-xs"
                                :loading="submitting"
                                data-testid="trip-cost-submit"
                                @click="submit"
                            >
                                {{ t("trip_detail.costs.quick_submit") }}
                            </Button>
                        </div>
                    </div>

                    <div
                        v-if="pendingFile"
                        class="mt-2 flex items-center gap-2 border-t border-blue-200/60 pt-2 dark:border-blue-900/40"
                    >
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded border border-slate-200 bg-slate-100 text-[9px] dark:border-slate-600 dark:bg-slate-800"
                        >
                            <img
                                v-if="pendingPreviewUrl"
                                :src="pendingPreviewUrl"
                                alt=""
                                class="h-full w-full object-cover"
                            />
                            <span
                                v-else
                                class="font-semibold text-slate-600 dark:text-slate-400"
                                >PDF</span
                            >
                        </div>
                        <span
                            class="min-w-0 flex-1 truncate text-xs text-slate-600 dark:text-slate-300"
                            >{{ pendingFile.name }}</span
                        >
                        <button
                            type="button"
                            class="rounded px-1.5 py-0.5 text-xs text-slate-400 hover:bg-slate-200/80 hover:text-rose-600 dark:hover:bg-slate-800"
                            data-testid="trip-cost-clear-receipt"
                            @click="clearPendingFile"
                        >
                            ✕
                        </button>
                    </div>

                    <p
                        v-if="formMsg"
                        class="mt-2 text-xs text-slate-600 dark:text-slate-400"
                        role="status"
                    >
                        {{ formMsg }}
                    </p>
                    <p
                        v-else
                        class="mt-2 text-[11px] text-slate-500 dark:text-slate-400"
                    >
                        {{ t("trip_detail.costs.receipt_drop_hint") }}
                    </p>
                </div>
            </Transition>

            <!-- Cost list -->
            <div
                class="divide-y divide-slate-100 rounded-xl border border-slate-200/80 dark:divide-slate-700/80 dark:border-slate-700/80"
            >
                <CostItem
                    v-for="c in costs ?? []"
                    :key="c.id"
                    :cost="c"
                    :can-submit="canSubmit"
                    :uploading-receipt="receiptUploadingId === c.id"
                    :cost-type-label="costTypeLabel(c.type)"
                    @pick-receipt="onReceiptPick"
                />
                <div
                    v-if="!(costs ?? []).length"
                    class="px-4 py-8 text-center"
                >
                    <p class="text-sm font-medium text-slate-600 dark:text-slate-300">
                        {{ t("trip_detail.costs.empty_title") }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {{ t("trip_detail.costs.empty_hint") }}
                    </p>
                    <button
                        v-if="canSubmit && !showQuickAdd"
                        type="button"
                        class="mt-3 inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700"
                        data-testid="trip-costs-empty-cta"
                        @click="openQuickAdd"
                    >
                        {{ t("trip_detail.costs.empty_cta") }}
                    </button>
                </div>
            </div>

            <!-- Breakdown -->
            <div
                v-if="breakdown.grand > 0"
                class="mt-4 rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 dark:border-slate-700/80 dark:bg-slate-900/40"
            >
                <div
                    class="text-xs font-semibold text-slate-600 dark:text-slate-400"
                >
                    {{ t("trip_detail.costs.breakdown_title") }}
                </div>
                <div class="mt-2 space-y-2">
                    <div
                        v-for="row in breakdownRows"
                        :key="row.key"
                        class="flex items-center gap-2 text-xs"
                    >
                        <span
                            class="w-24 shrink-0 font-medium text-slate-700 dark:text-slate-300"
                            >{{ row.label }}</span
                        >
                        <div
                            class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800"
                        >
                            <div
                                class="h-full rounded-full bg-blue-500"
                                :style="{ width: row.pct }"
                            />
                        </div>
                        <span
                            class="w-24 shrink-0 text-right tabular-nums text-slate-700 dark:text-slate-200"
                            >{{ row.amountFmt }}</span
                        >
                    </div>
                    <div
                        class="flex items-center justify-between border-t border-slate-200 pt-2 text-xs font-semibold dark:border-slate-700"
                    >
                        <span>{{ t("trip_detail.costs.breakdown_total") }}</span>
                        <span class="tabular-nums">{{ grandFmt }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from "vue";
import { RouterLink } from "vue-router";
import { useI18n } from "vue-i18n";
import { PaperClipIcon } from "@heroicons/vue/24/outline";
import Button from "../ui/Button.vue";
import CostItem from "./CostItem.vue";
import { submitTripCost, uploadTripCostReceipt } from "../../api/costs";
import { newIdempotencyKey } from "../../util/idempotency";
import {
    COST_TRACKER_TYPES,
    useCostTypeBreakdown,
    type CostRow,
} from "../../composables/useCostTracker";

const props = defineProps<{
    tripId: number;
    costs?: CostRow[] | null;
    canSubmit: boolean;
    showCostsLink?: boolean;
    /** @deprecated Layout uses standalone card; kept for compatibility */
    embedded?: boolean;
}>();

const emit = defineEmits<{
    updated: [];
}>();

const { t, te, locale } = useI18n();

const showQuickAdd = ref(false);
const selectedType = ref<string>("fuel");
const amount = ref("");
const description = ref("");
const submitting = ref(false);
const formMsg = ref("");
const pendingFile = ref<File | null>(null);
const pendingPreviewUrl = ref<string | null>(null);
const pendingFileRef = ref<HTMLInputElement | null>(null);
const amountInputRef = ref<HTMLInputElement | null>(null);
const receiptUploadingId = ref<number | null>(null);

function sumCostAmounts(list: CostRow[]) {
    return list.reduce((s, c) => s + (Number(c.amount) || 0), 0);
}

const costsConfirmedOnly = computed(() =>
    (props.costs ?? []).filter(
        (c) => String(c.status ?? "").toLowerCase() === "confirmed",
    ),
);

const costsPendingLike = computed(() =>
    (props.costs ?? []).filter((c) => {
        const s = String(c.status ?? "").toLowerCase();
        return s === "submitted" || s === "draft" || s === "pending";
    }),
);

const breakdownSource = computed(() => costsConfirmedOnly.value);
const breakdown = useCostTypeBreakdown(breakdownSource);

const currency = computed(
    () => props.costs?.[0]?.currency || "VND",
);

const locTag = computed(() =>
    locale.value === "en" ? "en-US" : "vi-VN",
);

function fmtMoney(n: number) {
    return `${new Intl.NumberFormat(locTag.value).format(n)} ${currency.value}`;
}

const approvedSum = computed(() => sumCostAmounts(costsConfirmedOnly.value));
const pendingSum = computed(() => sumCostAmounts(costsPendingLike.value));

const approvedTotalFmt = computed(() =>
    (props.costs ?? []).length
        ? fmtMoney(approvedSum.value)
        : t("trip_detail.costs.summary_none"),
);

const pendingTotalFmt = computed(() => fmtMoney(pendingSum.value));

watch(pendingFile, (f) => {
    if (pendingPreviewUrl.value) {
        URL.revokeObjectURL(pendingPreviewUrl.value);
        pendingPreviewUrl.value = null;
    }
    if (f && f.type.startsWith("image/"))
        pendingPreviewUrl.value = URL.createObjectURL(f);
});

function toggleQuickAdd() {
    showQuickAdd.value = !showQuickAdd.value;
    if (showQuickAdd.value) {
        nextTick(() => amountInputRef.value?.focus());
    }
}

function openQuickAdd() {
    showQuickAdd.value = true;
    nextTick(() => amountInputRef.value?.focus());
}

function chipClass(value: string) {
    const on = selectedType.value === value;
    return on
        ? "bg-blue-600 text-white ring-2 ring-blue-300 dark:ring-blue-400/80"
        : "bg-white text-slate-800 ring-1 ring-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-100 dark:ring-slate-600 dark:hover:bg-slate-700";
}

function costTypeLabel(type: string | null | undefined) {
    const raw = String(type ?? "").trim();
    if (!raw) return t("trip_detail.empty.cost_type");
    const slug = raw.toLowerCase().replace(/[^a-z0-9_]/g, "_");
    const key = `trip_detail.costs.type_${slug}`;
    if (te(key)) return t(key);
    return raw;
}

const grandFmt = computed(() => fmtMoney(breakdown.value.grand));

const breakdownRows = computed(() => {
    const { totals, grand } = breakdown.value;
    const fmtN = (n: number) => new Intl.NumberFormat(locTag.value).format(n);
    const rows: {
        key: string;
        label: string;
        pct: string;
        amountFmt: string;
    }[] = [];
    const add = (
        key: "fuel" | "toll" | "parking" | "other",
        labelKey: string,
    ) => {
        const n = totals[key];
        if (n <= 0) return;
        const pct = `${Math.max(4, Math.round((n / grand) * 100))}%`;
        rows.push({ key, label: t(labelKey), pct, amountFmt: `${fmtN(n)} đ` });
    };
    add("fuel", "trip_detail.costs.type_fuel");
    add("toll", "trip_detail.costs.type_toll");
    add("parking", "trip_detail.costs.type_parking");
    add("other", "trip_detail.costs.type_other");
    return rows;
});

function onDropPending(e: DragEvent) {
    const f = e.dataTransfer?.files?.[0];
    if (f) pendingFile.value = f;
}

function onPendingFile(e: Event) {
    const input = e.target as HTMLInputElement;
    const f = input.files?.[0];
    pendingFile.value = f ?? null;
    input.value = "";
}

function clearPendingFile() {
    pendingFile.value = null;
}

async function submit() {
    if (!props.canSubmit) return;
    formMsg.value = "";
    const type = String(selectedType.value ?? "")
        .trim()
        .toLowerCase();
    const n = Number(amount.value);
    if (!type || !Number.isFinite(n) || n <= 0) {
        formMsg.value = t("trip_detail.costs.quick_invalid");
        return;
    }
    submitting.value = true;
    try {
        const created = await submitTripCost(
            props.tripId,
            {
                type,
                amount: n,
                currency: "VND",
                description: description.value?.trim() || undefined,
            },
            { idempotencyKey: newIdempotencyKey() },
        );
        formMsg.value = t("trip_detail.messages.ok");
        amount.value = "";
        description.value = "";
        const file = pendingFile.value;
        clearPendingFile();
        emit("updated");
        const costId =
            created &&
            typeof created === "object" &&
            created !== null &&
            "id" in created
                ? Number((created as { id?: number }).id)
                : NaN;
        if (file && Number.isFinite(costId)) {
            receiptUploadingId.value = costId;
            try {
                await uploadTripCostReceipt(props.tripId, costId, file, {
                    idempotencyKey: newIdempotencyKey(),
                });
            } finally {
                receiptUploadingId.value = null;
            }
            emit("updated");
        }
        showQuickAdd.value = false;
        formMsg.value = "";
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } } };
        formMsg.value =
            err?.response?.data?.message ?? t("trip_detail.messages.error");
    } finally {
        submitting.value = false;
    }
}

function onReceiptPick(costId: number, e: Event) {
    onReceiptFile(costId, e);
}

async function onReceiptFile(costId: number, e: Event) {
    const input = e.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = "";
    if (!file || !props.canSubmit) return;
    receiptUploadingId.value = costId;
    try {
        await uploadTripCostReceipt(props.tripId, costId, file, {
            idempotencyKey: newIdempotencyKey(),
        });
        emit("updated");
    } finally {
        receiptUploadingId.value = null;
    }
}
</script>
