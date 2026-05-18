<template>
  <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ t('portal.quick_actions_title') }}</h2>
    <div class="mt-4 grid gap-3">
      <RouterLink
        v-for="item in items"
        :key="item.type"
        :to="{ name: 'portalCreate', query: { type: item.type } }"
        class="flex min-h-[52px] items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-left transition hover:border-va-300 hover:bg-va-50/80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800"
      >
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-slate-200/80">
          <component :is="item.icon" class="h-6 w-6 text-va-800" aria-hidden="true" />
        </span>
        <span class="min-w-0 flex-1">
          <span class="block text-sm font-semibold text-slate-900">{{ item.label }}</span>
          <span class="mt-0.5 block text-xs text-slate-500">{{ item.hint }}</span>
        </span>
        <ChevronRightIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
      </RouterLink>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { BriefcaseIcon, ChevronRightIcon, CubeIcon, MapPinIcon, UserGroupIcon } from '@heroicons/vue/24/outline'

const { t } = useI18n()

const items = computed(() => [
  {
    type: 'business',
    label: t('portal.quick_business_label'),
    hint: t('portal.quick_business_hint'),
    icon: BriefcaseIcon,
  },
  {
    type: 'cargo',
    label: t('portal.quick_cargo_label'),
    hint: t('portal.quick_cargo_hint'),
    icon: CubeIcon,
  },
  {
    type: 'door_to_door',
    label: t('portal.quick_d2d_label'),
    hint: t('portal.quick_d2d_hint'),
    icon: MapPinIcon,
  },
  {
    type: 'point_to_point',
    label: t('portal.quick_p2p_label'),
    hint: t('portal.quick_p2p_hint'),
    icon: UserGroupIcon,
  },
])
</script>
