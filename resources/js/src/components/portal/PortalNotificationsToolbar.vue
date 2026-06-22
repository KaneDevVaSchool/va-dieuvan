<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { CheckIcon } from '@heroicons/vue/24/outline'
import DatagridToolbarSearch from '../shared/ui/DatagridToolbarSearch.vue'
import DatagridSegmentedControl from '../shared/ui/DatagridSegmentedControl.vue'

const props = defineProps({
  searchInput: { type: String, default: '' },
  activeTab: { type: String, default: 'all' },
  unreadTotal: { type: Number, default: 0 },
  markingAll: { type: Boolean, default: false },
  markAllDisabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:searchInput', 'update:activeTab', 'mark-all'])

const { t } = useI18n()

const tabOptions = computed(() => [
  {
    value: 'all',
    label: t('portal.notifications_tab_all'),
  },
  {
    value: 'unread',
    label:
      props.unreadTotal > 0
        ? `${t('portal.notifications_tab_unread')} (${props.unreadTotal > 99 ? '99+' : props.unreadTotal})`
        : t('portal.notifications_tab_unread'),
  },
])
</script>

<template>
  <div
    class="rounded-card border border-slate-200/80 bg-white shadow-sm"
    data-testid="portal-notifications-toolbar"
  >
    <div class="border-b border-slate-100 px-4 py-3 sm:px-5">
      <div class="flex w-full min-w-0 flex-wrap items-center gap-2 lg:flex-nowrap">
        <div class="min-w-0 w-full basis-full lg:flex-1 lg:basis-auto">
          <DatagridToolbarSearch
            :model-value="searchInput"
            input-id="portal-notifications-q"
            :placeholder="t('portal.notifications_search_placeholder')"
            :aria-label="t('portal.notifications_search_placeholder')"
            hide-label
            stretch
            inline-actions
            input-height="h-10"
            @update:model-value="emit('update:searchInput', $event)"
          />
        </div>

        <div class="flex shrink-0 items-center gap-2">
          <button
            type="button"
            class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-va-700/30 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="markingAll || markAllDisabled"
            data-testid="portal-notifications-mark-all"
            @click="emit('mark-all')"
          >
            <CheckIcon class="h-[15px] w-[15px] shrink-0 opacity-80" aria-hidden="true" />
            <span class="hidden sm:inline">{{ t('portal.notifications_mark_all') }}</span>
            <span class="sm:hidden">{{ t('portal.notifications_mark_read') }}</span>
          </button>
        </div>

        <div class="ml-auto flex w-full shrink-0 basis-full lg:w-auto lg:basis-auto">
          <DatagridSegmentedControl
            :model-value="activeTab"
            :options="tabOptions"
            :aria-label="t('portal.notifications_kpi.filter_aria')"
            @update:model-value="emit('update:activeTab', $event)"
          />
        </div>
      </div>
    </div>
  </div>
</template>
