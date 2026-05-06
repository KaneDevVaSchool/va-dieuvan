<template>
    <section
        class="mb-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700/70 dark:bg-slate-950/30"
        :aria-label="t('trip_detail.passengers.named_section_aria')"
    >
        <div
            class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
        >
            <h3
                class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400"
            >
                {{ t("trip_detail.passengers.named_section_title") }}
            </h3>
            <button
                type="button"
                class="inline-flex shrink-0 items-center justify-center rounded-xl bg-[#8B1A1A] px-3 py-2 text-xs font-semibold text-white shadow-sm hover:brightness-105 disabled:cursor-not-allowed disabled:opacity-45 dark:shadow-[#8B1A1A]/25"
                :disabled="disabled || saving"
                @click="emit('save')"
            >
                <span
                    v-if="saving"
                    class="mr-2 inline-block h-3 w-3 animate-spin rounded-full border border-white/40 border-t-white"
                    aria-hidden="true"
                />
                {{ t("trip_detail.passengers.named_save") }}
            </button>
        </div>

        <label
            class="mb-3 flex flex-col gap-1 sm:max-w-[12rem]"
            :for="uid + '-count'"
        >
            <span
                class="text-[11px] font-semibold text-slate-600 dark:text-slate-400"
            >
                {{ t("trip_detail.passengers.named_count_label") }}
            </span>
            <input
                :id="uid + '-count'"
                v-model.number="passengerCount"
                type="number"
                min="1"
                max="50"
                step="1"
                inputmode="numeric"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-inner outline-none focus:border-sky-400/60 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                :disabled="disabled || saving"
            />
        </label>

        <div class="space-y-3">
            <div
                v-for="(row, i) in passengers"
                :key="i"
                class="rounded-xl border border-slate-100 bg-slate-50/80 p-3 dark:border-slate-700/60 dark:bg-slate-900/40"
            >
                <p class="mb-2 text-[11px] font-semibold text-slate-600 dark:text-slate-400">
                    {{
                        t("trip_detail.passengers.named_row_label", {
                            n: i + 1,
                        })
                    }}
                </p>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label class="flex flex-col gap-0.5">
                        <span class="text-[10px] font-medium text-slate-500">{{
                            t("trip_detail.passengers.named_field_name")
                        }}</span>
                        <input
                            v-model="row.name"
                            type="text"
                            maxlength="255"
                            class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-950"
                            :disabled="disabled || saving"
                        />
                    </label>
                    <label class="flex flex-col gap-0.5">
                        <span class="text-[10px] font-medium text-slate-500">{{
                            t("trip_detail.passengers.named_field_phone")
                        }}</span>
                        <input
                            v-model="row.phone"
                            type="text"
                            maxlength="20"
                            class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-950"
                            :disabled="disabled || saving"
                        />
                    </label>
                    <label class="flex flex-col gap-0.5 sm:col-span-2">
                        <span class="text-[10px] font-medium text-slate-500">{{
                            t("trip_detail.passengers.named_field_note")
                        }}</span>
                        <textarea
                            v-model="row.note"
                            rows="2"
                            maxlength="2000"
                            class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-sm dark:border-slate-600 dark:bg-slate-950"
                            :disabled="disabled || saving"
                        />
                    </label>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { useId } from "vue";
import { useI18n } from "vue-i18n";

const passengerCount = defineModel("passengerCount", {
    type: Number,
    required: true,
});

const passengers = defineModel("passengers", {
    type: Array,
    required: true,
});

defineProps({
    saving: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["save"]);

const { t } = useI18n();
const uid = useId();
</script>
