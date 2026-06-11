<template>
    <section
        class="rounded-2xl bg-slate-100/55 shadow-sm shadow-slate-900/5 dark:bg-slate-900/40 dark:shadow-black/20"
        :class="clipOverflow ? 'overflow-hidden' : 'overflow-visible'"
        :aria-label="title"
    >
        <div
            class="flex flex-wrap items-start gap-1 sm:flex-nowrap sm:items-center"
        >
            <button
                type="button"
                class="flex min-w-0 flex-1 items-center gap-2 rounded-xl px-2.5 py-2 text-left text-slate-700 transition-colors hover:bg-white/65 dark:text-slate-200 dark:hover:bg-slate-800/60"
                :aria-expanded="expanded"
                :aria-controls="contentId"
                @click="toggle"
            >
                <ChevronDownIcon
                    class="size-4 shrink-0 text-slate-400 transition-transform duration-200 motion-reduce:transition-none dark:text-slate-500"
                    :class="expanded ? '-rotate-180' : ''"
                    aria-hidden="true"
                />
                <div class="min-w-0 flex-1">
                    <div
                        class="text-[10px] font-semibold uppercase leading-tight tracking-[0.1em] text-slate-500 dark:text-slate-400"
                    >
                        {{ title }}
                    </div>
                    <div
                        v-if="effectiveSummary"
                        class="mt-0.5 line-clamp-2 text-[11px] font-normal leading-snug text-slate-600 dark:text-slate-400"
                    >
                        {{ effectiveSummary }}
                    </div>
                </div>
            </button>
            <div
                v-if="$slots['header-extra'] || $slots['header-end']"
                class="flex shrink-0 items-center gap-1 pr-2 pb-1 pt-1 sm:py-2"
            >
                <slot name="header-extra" />
                <slot name="header-end" />
            </div>
        </div>
        <Transition
            enter-active-class="transition duration-200 ease-out motion-reduce:transition-none"
            enter-from-class="opacity-0 -translate-y-0.5"
            leave-active-class="transition duration-150 ease-in motion-reduce:transition-none"
            leave-to-class="opacity-0 -translate-y-0.5"
        >
            <div
                v-show="expanded"
                :id="contentId"
                class="px-2.5 pb-2.5 pt-2"
                role="region"
            >
                <slot />
            </div>
        </Transition>
    </section>
</template>

<script setup lang="ts">
import { computed, ref, useId, watch } from "vue";
import { ChevronDownIcon } from "@heroicons/vue/24/outline";

const props = withDefaults(
    defineProps<{
        title: string;
        summaryCollapsed?: string;
        summaryExpanded?: string;
        defaultExpanded?: boolean;
        persistKey?: string | null;
        /** false: tránh cắt dropdown/modal con (panel nguồn lực) */
        clipOverflow?: boolean;
    }>(),
    {
        summaryCollapsed: "",
        summaryExpanded: "",
        defaultExpanded: true,
        persistKey: null,
        clipOverflow: true,
    },
);

function readStored(): boolean | undefined {
    if (
        props.persistKey == null ||
        typeof sessionStorage === "undefined"
    )
        return undefined;
    try {
        const raw = sessionStorage.getItem(props.persistKey);
        if (raw === "1") return true;
        if (raw === "0") return false;
    } catch {
        /* noop */
    }
    return undefined;
}

const expanded = ref(readStored() ?? props.defaultExpanded);

watch(
    () => props.persistKey,
    () => {
        const r = readStored();
        expanded.value = r ?? props.defaultExpanded;
    },
);

watch(expanded, (e) => {
    if (
        props.persistKey == null ||
        typeof sessionStorage === "undefined"
    )
        return;
    try {
        sessionStorage.setItem(props.persistKey, e ? "1" : "0");
    } catch {
        /* noop */
    }
});



function toggle() {
    expanded.value = !expanded.value;
}

const contentId = `${useId()}-collapsible`;

const effectiveSummary = computed(() => {
    if (expanded.value) return "";
    return props.summaryCollapsed || "";
});

defineExpose({
    setExpanded(value: boolean) {
        expanded.value = value;
    },
    toggle,
    expanded,
});
</script>
