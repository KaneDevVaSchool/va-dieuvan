<template>
    <!-- Skeleton -->
    <div v-if="loading" class="space-y-3">
        <div
            class="animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-800 h-52"
        />
    </div>

    <!-- Hero trip card -->
    <div
        v-else-if="trip"
        class="overflow-hidden rounded-2xl bg-slate-900 text-white shadow-lg"
    >
        <!-- Header row: status + time -->
        <div class="flex items-center justify-between gap-2 px-4 pt-4">
            <span
                class="inline-flex rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wide"
                :class="badgeClass"
            >
                {{ statusLabel }}
            </span>
            <span
                class="shrink-0 text-base tabular-nums font-semibold text-white/80"
            >
                {{ timeRange }}
            </span>
        </div>

        <!-- Trip details -->
        <div class="mt-3 space-y-2 px-4">
            <!-- Pickup -->
            <div class="flex items-start gap-3">
                <span
                    class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-500/20"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        class="h-4 w-4 text-emerald-400"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.757.433c.12.065.227.115.315.142.162.04.343.04.506 0a1.16 1.16 0 00.315-.142c.088-.027.195-.077.315-.142z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[11px] font-medium uppercase text-white/50">
                        Điểm đón
                    </p>
                    <p class="truncate text-base font-semibold leading-snug">
                        {{ origin }}
                    </p>
                </div>
            </div>

            <!-- Dropoff -->
            <div class="flex items-start gap-3">
                <span
                    class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-sky-500/20"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        class="h-4 w-4 text-sky-400"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 1a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0110 1zM5.05 3.05a.75.75 0 011.06 0l1.062 1.06A.75.75 0 116.11 5.173L5.05 4.11a.75.75 0 010-1.06zm9.9 0a.75.75 0 010 1.06l-1.06 1.062a.75.75 0 01-1.062-1.061l1.061-1.061a.75.75 0 011.06 0zM10 8a2 2 0 100 4 2 2 0 000-4zm-7.75 2a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5h-1.5zm14.5 0a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5h-1.5zM5.05 16.95a.75.75 0 001.06-1.06l-1.06-1.062a.75.75 0 00-1.061 1.06l1.06 1.062zm9.9-1.06a.75.75 0 00-1.061-1.061l-1.062 1.06a.75.75 0 001.06 1.062l1.062-1.061zM10 16a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0110 16z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[11px] font-medium uppercase text-white/50">
                        Điểm đến
                    </p>
                    <p class="truncate text-base font-semibold leading-snug">
                        {{ destination }}
                    </p>
                </div>
            </div>

            <!-- Requester + passengers -->
            <div class="flex items-center gap-2 pt-1">
                <img
                    v-if="requesterAvatar"
                    :src="requesterAvatar"
                    alt=""
                    class="h-8 w-8 rounded-full object-cover border border-white/20"
                />
                <div
                    v-else
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-sm font-bold"
                >
                    {{ requesterInitials }}
                </div>
                <p class="text-base font-semibold">{{ requesterName }}</p>
                <span
                    v-if="passengerCount"
                    class="ml-auto shrink-0 text-sm text-white/60"
                >
                    {{ passengerCount }} khách
                </span>
            </div>
        </div>

        <!-- CTA -->
        <div class="mt-4 px-4 pb-4">
            <RouterLink
                :to="`/driver/trips/${trip.id}`"
                class="flex w-full items-center justify-center gap-2 rounded-2xl px-4 text-lg font-bold transition active:scale-[0.98]"
                :class="ctaClass"
                style="min-height: 56px"
            >
                {{ ctaLabel }}
            </RouterLink>
        </div>
    </div>

    <!-- Empty state -->
    <div
        v-else
        class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/80 py-12 text-center dark:border-slate-700 dark:bg-slate-900/40"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.2"
            stroke="currentColor"
            class="h-16 w-16 text-slate-300 dark:text-slate-600"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"
            />
        </svg>
        <p
            class="mt-3 text-base font-semibold text-slate-500 dark:text-slate-400"
        >
            {{ t("driver_home.no_current_trip") }}
        </p>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import { RouterLink } from "vue-router";

const props = defineProps({
    trip: { type: Object, default: null },
    loading: { type: Boolean, default: false },
});

const { t, te } = useI18n();

function formatHm(iso) {
    if (!iso) return "";
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return "";
    return d.toLocaleTimeString("vi-VN", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    });
}

const timeRange = computed(() => {
    if (!props.trip) return "";
    const start = formatHm(props.trip.depart_at);
    const end = formatHm(
        props.trip.arrive_by || props.trip.dispatch_request?.arrive_by,
    );
    if (start && end) return `${start} – ${end}`;
    return start || "—";
});

const origin = computed(() => {
    const dr = props.trip?.dispatch_request;
    return dr?.origin?.trim() || "—";
});

const destination = computed(() => {
    const dr = props.trip?.dispatch_request;
    return dr?.destination?.trim() || "—";
});

const requesterName = computed(() => {
    const dr = props.trip?.dispatch_request;
    return dr?.requester?.name?.trim() || dr?.origin || "—";
});

const requesterAvatar = computed(
    () => props.trip?.dispatch_request?.requester?.avatar_url || null,
);

const requesterInitials = computed(() => {
    const n = requesterName.value;
    if (n === "—") return "?";
    const parts = n.split(/\s+/);
    if (parts.length >= 2)
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    return n.slice(0, 2).toUpperCase();
});

const passengerCount = computed(() => {
    const list = props.trip?.dispatch_request?.named_passengers;
    if (Array.isArray(list)) return list.length;
    return props.trip?.dispatch_request?.passenger_count || null;
});

const status = computed(() =>
    String(props.trip?.status ?? "").trim().toLowerCase(),
);

const statusLabel = computed(() => {
    const st = status.value;
    const key = `labels.trip_status.${st}`;
    if (st && te(key)) return t(key);
    return t("driver_home.trip_pending");
});

const badgeClass = computed(() => {
    if (status.value === "in_progress")
        return "bg-amber-400/20 text-amber-300";
    return "bg-slate-700 text-slate-300";
});

const ctaLabel = computed(() => {
    if (status.value === "in_progress") return t("driver_home.btn_continue");
    if (status.value === "completed") return t("driver_home.btn_view");
    return t("driver_home.btn_start");
});

const ctaClass = computed(() => {
    if (status.value === "in_progress")
        return "bg-amber-500 text-white hover:bg-amber-400";
    return "bg-emerald-500 text-white hover:bg-emerald-400";
});
</script>
