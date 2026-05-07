<template>
    <div
        class="px-0 pb-4"
        :style="{ paddingTop: 'max(1rem, env(safe-area-inset-top))' }"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-sm text-slate-400 dark:text-slate-500">
                    {{ t("driver_home.hello") }}
                </p>
                <h1 class="mt-0.5 truncate text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">
                    {{ user?.name || "—" }}
                </h1>
                <p class="mt-1 text-sm font-semibold" style="color: #9A0036">
                    {{ todayLabel }}
                </p>
            </div>

            <div class="flex items-center gap-2 shrink-0 mt-1">
                <NotificationBell />
                <img
                    v-if="avatarUrl"
                    :src="avatarUrl"
                    alt=""
                    class="h-11 w-11 rounded-full border-2 border-slate-200 object-cover dark:border-slate-700"
                />
                <div
                    v-else
                    class="flex h-11 w-11 items-center justify-center rounded-full border-2 border-slate-200 bg-slate-100 text-base font-bold text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                >
                    {{ initials }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import NotificationBell from "../notifications/NotificationBell.vue";

const props = defineProps({
    user: { type: Object, default: null },
    avatarUrl: { type: String, default: null },
    initials: { type: String, default: "?" },
});

const { locale } = useI18n();
const { t } = useI18n();

const todayLabel = computed(() => {
    const d = new Date();
    return d.toLocaleDateString(locale.value === "vi" ? "vi-VN" : "en-US", {
        weekday: "long",
        day: "numeric",
        month: "numeric",
        year: "numeric",
    });
});
</script>
