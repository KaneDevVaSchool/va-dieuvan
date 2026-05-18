<template>
  <li
    class="mb-2 list-none rounded-2xl border px-3.5 py-3 text-sm shadow-sm transition active:scale-[0.99]"
    :class="notification.read
      ? 'cursor-pointer border-slate-100 bg-slate-50/90 dark:border-slate-800 dark:bg-slate-800/40'
      : 'cursor-pointer border-sky-200/90 bg-gradient-to-br from-sky-50 to-white dark:border-sky-900/60 dark:from-sky-950/30 dark:to-slate-900/80'"
    role="button"
    tabindex="0"
    @click="emit('open', notification)"
    @keydown.enter.prevent="emit('open', notification)"
    @keydown.space.prevent="emit('open', notification)"
  >
    <div class="flex items-start gap-3">
      <!-- Typed icon -->
      <div
        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
        :class="iconConfig.bg"
        aria-hidden="true"
      >
        <component :is="iconConfig.icon" class="h-4 w-4" :class="iconConfig.color" />
      </div>

      <div class="min-w-0 flex-1">
        <p class="text-[11px] font-medium tracking-wide text-slate-500 dark:text-slate-400">
          {{ relativeTime }}
        </p>
        <span
          v-if="typeLabel"
          class="mt-1.5 inline-flex max-w-full rounded-lg px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide"
          :class="iconConfig.badge"
        >
          {{ typeLabel }}
        </span>
        <p class="mt-1.5 text-[15px] font-semibold leading-snug text-slate-800 dark:text-slate-100">
          {{ lines.primary }}
        </p>
        <p
          v-if="lines.sub"
          class="mt-1 text-[13px] leading-snug text-slate-600 dark:text-slate-300"
        >
          {{ lines.sub }}
        </p>
        <div class="mt-2.5 flex flex-wrap items-center gap-2">
          <button
            v-if="hasNavLink"
            type="button"
            class="rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-bold text-white active:bg-sky-700 dark:bg-sky-500 dark:active:bg-sky-600"
            @click.stop="emit('open', notification)"
          >
            {{ t('notify.open') }}
          </button>
          <button
            v-else
            type="button"
            class="rounded-lg px-3 py-1.5 text-xs font-bold text-sky-600 active:bg-sky-50 dark:text-sky-400 dark:active:bg-sky-950/40"
            @click.stop="emit('open', notification)"
          >
            {{ t('notify.mark_read') }}
          </button>
          <span
            v-if="!notification.read"
            class="h-2 w-2 shrink-0 rounded-full bg-sky-500 dark:bg-sky-400"
            aria-hidden="true"
          />
        </div>
      </div>
    </div>
  </li>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  TruckIcon,
  PlusCircleIcon,
  ArrowPathIcon,
  ExclamationCircleIcon,
  BellIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  notification: { type: Object, required: true },
  kind: { type: String, default: 'generic' },
  lines: { type: Object, required: true },
  typeLabel: { type: String, default: '' },
  relativeTime: { type: String, default: '' },
  hasNavLink: { type: Boolean, default: false },
})

const emit = defineEmits(['open'])

const { t } = useI18n()

const KIND_CONFIG = {
  trip_assigned: {
    icon: TruckIcon,
    bg: 'bg-sky-100 dark:bg-sky-950/60',
    color: 'text-sky-600 dark:text-sky-400',
    badge: 'bg-sky-100 text-sky-800 dark:bg-sky-900/50 dark:text-sky-200',
  },
  new_trip: {
    icon: PlusCircleIcon,
    bg: 'bg-emerald-100 dark:bg-emerald-950/60',
    color: 'text-emerald-600 dark:text-emerald-400',
    badge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200',
  },
  status: {
    icon: ArrowPathIcon,
    bg: 'bg-amber-100 dark:bg-amber-950/60',
    color: 'text-amber-600 dark:text-amber-400',
    badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200',
  },
  error: {
    icon: ExclamationCircleIcon,
    bg: 'bg-rose-100 dark:bg-rose-950/60',
    color: 'text-rose-600 dark:text-rose-400',
    badge: 'bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-200',
  },
  generic: {
    icon: BellIcon,
    bg: 'bg-slate-100 dark:bg-slate-700/60',
    color: 'text-slate-500 dark:text-slate-400',
    badge: 'bg-slate-100 text-slate-700 dark:bg-slate-700/50 dark:text-slate-300',
  },
  noise: {
    icon: BellIcon,
    bg: 'bg-slate-100 dark:bg-slate-700/60',
    color: 'text-slate-400 dark:text-slate-500',
    badge: 'bg-slate-100 text-slate-600 dark:bg-slate-700/50 dark:text-slate-400',
  },
}

const iconConfig = computed(() => KIND_CONFIG[props.kind] ?? KIND_CONFIG.generic)
</script>
