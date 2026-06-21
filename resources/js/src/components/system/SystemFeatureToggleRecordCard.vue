<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  ChevronDownIcon,
  LinkIcon,
  ShieldCheckIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  row: { type: Object, required: true },
  expanded: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
  navLinks: { type: Array, default: () => [] },
  relatedPerms: { type: Array, default: () => [] },
  navLinkLabel: { type: Function, required: true },
  relatedPermLabel: { type: Function, required: true },
  relatedPermHint: { type: Function, required: true },
})

const emit = defineEmits(['patch', 'toggle-expand', 'delete'])

const { t } = useI18n()

const statusTone = computed(() => {
  if (props.row.maintenance_mode) return 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200'
  if (!props.row.is_enabled) return 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
  return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
})

const statusLabel = computed(() => {
  if (props.row.maintenance_mode) return t('system_pages.feature_toggles.status_maintenance')
  if (!props.row.is_enabled) return t('system_pages.feature_toggles.status_off')
  return t('system_pages.feature_toggles.status_on')
})

const hasExpand = computed(() => props.navLinks.length > 0 || props.relatedPerms.length > 0)

function toggleSwitch(field) {
  emit('patch', { [field]: !props.row[field] })
}
</script>

<template>
  <article
    class="rounded-2xl bg-slate-50/90 dark:bg-slate-800/35"
    :class="!row.is_enabled ? 'opacity-90' : ''"
    :data-testid="`feature-toggle-card-${row.id}`"
  >
    <div class="flex flex-col gap-3 px-3 py-3.5 sm:px-4 lg:flex-row lg:items-center lg:gap-4">
      <div class="min-w-0 flex-1 lg:max-w-[28%]">
        <div class="flex flex-wrap items-center gap-2">
          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ row.name }}</h3>
          <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="statusTone">{{ statusLabel }}</span>
          <span
            v-if="row.upgrade_notice"
            class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold text-violet-700 dark:bg-violet-950/50 dark:text-violet-200"
          >
            {{ t('system_pages.feature_toggles.badge_upgrade') }}
          </span>
        </div>
        <p class="mt-1 break-all font-mono text-[11px] text-slate-500 dark:text-slate-400">{{ row.key }}</p>
        <p v-if="row.module" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
          {{ t('system_pages.feature_toggles.module_label') }}: <span class="font-medium text-slate-700 dark:text-slate-300">{{ row.module }}</span>
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-4 lg:gap-6">
        <div class="flex flex-col gap-1">
          <span class="text-[10px] font-semibold uppercase tracking-wide text-teal-700 dark:text-teal-400">{{ t('system_pages.feature_toggles.col_enabled') }}</span>
          <button
            type="button"
            role="switch"
            :aria-checked="row.is_enabled"
            :disabled="saving"
            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 disabled:opacity-50 dark:focus:ring-offset-slate-900"
            :class="row.is_enabled ? 'bg-teal-500' : 'bg-slate-200 dark:bg-slate-700'"
            data-testid="feature-toggle-enabled"
            @click="toggleSwitch('is_enabled')"
          >
            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="row.is_enabled ? 'translate-x-5' : 'translate-x-0'" />
          </button>
        </div>
        <div class="flex flex-col gap-1">
          <span class="text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">{{ t('system_pages.feature_toggles.col_maintenance') }}</span>
          <button
            type="button"
            role="switch"
            :aria-checked="row.maintenance_mode"
            :disabled="saving"
            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-50"
            :class="row.maintenance_mode ? 'bg-amber-500' : 'bg-slate-200 dark:bg-slate-700'"
            data-testid="feature-toggle-maintenance"
            @click="toggleSwitch('maintenance_mode')"
          >
            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="row.maintenance_mode ? 'translate-x-5' : 'translate-x-0'" />
          </button>
        </div>
        <div class="flex flex-col gap-1">
          <span class="text-[10px] font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-400">{{ t('system_pages.feature_toggles.col_upgrade') }}</span>
          <button
            type="button"
            role="switch"
            :aria-checked="row.upgrade_notice"
            :disabled="saving"
            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 disabled:opacity-50"
            :class="row.upgrade_notice ? 'bg-violet-500' : 'bg-slate-200 dark:bg-slate-700'"
            data-testid="feature-toggle-upgrade"
            @click="toggleSwitch('upgrade_notice')"
          >
            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200" :class="row.upgrade_notice ? 'translate-x-5' : 'translate-x-0'" />
          </button>
        </div>
      </div>

      <div class="flex shrink-0 items-center gap-1 self-start lg:self-center">
        <button
          v-if="hasExpand"
          type="button"
          class="inline-flex h-10 items-center gap-1 rounded-lg px-2.5 text-xs font-medium text-teal-700 hover:bg-teal-50 dark:text-teal-400 dark:hover:bg-teal-950/40"
          data-testid="feature-toggle-expand"
          @click="emit('toggle-expand')"
        >
          <LinkIcon class="h-4 w-4" aria-hidden="true" />
          {{ t('system_pages.feature_toggles.nav_impact') }}
          <ChevronDownIcon class="h-3.5 w-3.5 transition-transform" :class="{ '-rotate-180': expanded }" aria-hidden="true" />
        </button>
        <button
          type="button"
          class="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600 disabled:opacity-40 dark:hover:bg-rose-950/40"
          :title="t('system_pages.common.delete')"
          :disabled="saving"
          data-testid="feature-toggle-delete"
          @click="emit('delete')"
        >
          <TrashIcon class="h-4 w-4" aria-hidden="true" />
        </button>
      </div>
    </div>

    <div v-if="expanded && hasExpand" class="border-t border-slate-200/60 px-4 py-3 dark:border-slate-700/60">
      <div class="space-y-3">
        <div v-if="navLinks.length" class="flex items-start gap-2">
          <LinkIcon class="mt-0.5 h-4 w-4 shrink-0 text-teal-500" aria-hidden="true" />
          <div class="min-w-0 flex-1">
            <p class="mb-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('system_pages.feature_toggles.nav_affected') }}</p>
            <div class="flex flex-wrap gap-1.5">
              <span
                v-for="link in navLinks"
                :key="link.to"
                class="rounded-lg bg-white/80 px-2.5 py-1 text-xs font-medium text-slate-700 dark:bg-slate-900/80 dark:text-slate-300"
              >
                {{ navLinkLabel(link) }}
              </span>
            </div>
          </div>
        </div>
        <div v-if="relatedPerms.length" class="flex items-start gap-2">
          <ShieldCheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-violet-500" aria-hidden="true" />
          <div class="min-w-0 flex-1">
            <p class="mb-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ t('system_pages.feature_toggles.perms_related') }}</p>
            <ul class="space-y-1.5">
              <li
                v-for="perm in relatedPerms"
                :key="perm"
                class="rounded-lg bg-white/80 px-2.5 py-1.5 text-xs dark:bg-slate-900/80"
              >
                <span class="font-semibold text-slate-800 dark:text-slate-100">{{ relatedPermLabel(perm) }}</span>
                <span class="ml-1.5 font-mono text-[10px] text-slate-400">{{ perm }}</span>
                <p v-if="relatedPermHint(perm)" class="mt-0.5 text-[11px] leading-snug text-slate-500 dark:text-slate-400">{{ relatedPermHint(perm) }}</p>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </article>
</template>
