<template>
    <div
        class="-mx-3 rounded-b-2xl bg-gradient-to-br from-slate-900 via-slate-900 to-slate-800 px-4 pb-5 text-white shadow-lg sm:-mx-4 sm:px-5"
        :style="{ paddingTop: 'max(1rem, env(safe-area-inset-top))' }"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <p class="text-sm text-white/70">
                    {{ t("driver_home.hello") }}
                </p>
                <h1
                    class="mt-0.5 truncate text-2xl font-bold tracking-tight sm:text-3xl"
                >
                    {{ user?.name || "—" }}
                </h1>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <NotificationBell />
                <img
                    v-if="avatarUrl"
                    :src="avatarUrl"
                    alt=""
                    class="h-12 w-12 rounded-full border-2 border-white/20 object-cover"
                />
                <div
                    v-else
                    class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white/20 bg-white/10 text-lg font-bold"
                >
                    {{ initials }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useI18n } from "vue-i18n";
import NotificationBell from "../notifications/NotificationBell.vue";

const props = defineProps({
    user: { type: Object, default: null },
    avatarUrl: { type: String, default: null },
    initials: { type: String, default: "?" },
});

const { t } = useI18n();
</script>
