<template>
  <section class="rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-sm sm:rounded-2xl sm:p-5">
    <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500 sm:text-sm">{{ t('portal.quick_actions_title') }}</h2>
    <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-2 sm:gap-2.5 lg:grid-cols-1 lg:gap-3">
      <RouterLink
        :to="{ name: 'portalRequestList', query: { filter: 'pending' } }"
        class="group col-span-2 flex min-h-[46px] items-center gap-2.5 rounded-xl border border-amber-200/80 bg-amber-50/70 p-2.5 text-left transition hover:border-amber-300 hover:bg-amber-100/70 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600 sm:min-h-[52px] sm:gap-3 sm:rounded-2xl sm:p-3.5 lg:col-span-1"
        data-testid="portal-quick-pending"
      >
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100/90 text-amber-700 sm:h-10 sm:w-10 sm:rounded-xl">
          <ClockIcon class="h-4.5 w-4.5 sm:h-5 sm:w-5" aria-hidden="true" />
        </span>
        <span class="min-w-0 flex-1">
          <span class="flex flex-wrap items-center gap-1.5 sm:gap-2">
            <span class="text-xs font-bold text-slate-900 sm:text-sm">{{ t('portal.quick_pending_requests_label') }}</span>
            <span
              v-if="pendingCount > 0"
              class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-amber-600 px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-white"
            >
              {{ pendingCount > 99 ? '99+' : pendingCount }}
            </span>
          </span>
          <span class="block text-[11px] text-slate-500 leading-snug break-words sm:text-xs">{{ t('portal.quick_pending_requests_hint') }}</span>
        </span>
        <ChevronRightIcon class="h-4 w-4 shrink-0 text-slate-400 transition group-hover:text-amber-600 sm:h-5 sm:w-5" aria-hidden="true" />
      </RouterLink>

      <RouterLink
        :to="extracurricularQuick.to"
        class="group flex min-h-[44px] items-center gap-2 rounded-xl border border-slate-100 bg-slate-50/60 p-2 text-left transition hover:border-va-200 hover:bg-va-50/60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800 sm:min-h-[50px] sm:gap-3 sm:rounded-2xl sm:p-3"
      >
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-va-50 text-va-800 sm:h-10 sm:w-10 sm:rounded-xl">
          <component :is="extracurricularQuick.icon" class="h-4.5 w-4.5 sm:h-5 sm:w-5" aria-hidden="true" />
        </span>
        <span class="min-w-0 flex-1">
          <span class="block text-xs font-semibold leading-snug text-slate-900 break-words sm:text-sm">{{ extracurricularQuick.label }}</span>
          <span class="block text-[10px] text-slate-500 leading-snug break-words sm:text-xs">{{ extracurricularQuick.hint }}</span>
        </span>
        <ChevronRightIcon class="hidden h-4 w-4 shrink-0 text-slate-400 transition group-hover:text-va-800 sm:block" aria-hidden="true" />
      </RouterLink>

      <RouterLink
        v-for="item in items"
        :key="item.type"
        :to="{ name: 'portalCreate', query: { type: item.type } }"
        class="group flex min-h-[44px] items-center gap-2 rounded-xl border border-slate-100 bg-slate-50/60 p-2 text-left transition hover:border-slate-200 hover:bg-slate-100/80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800 sm:min-h-[50px] sm:gap-3 sm:rounded-2xl sm:p-3"
      >
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm ring-1 ring-slate-900/5 text-va-800 sm:h-10 sm:w-10 sm:rounded-xl">
          <component :is="item.icon" class="h-4.5 w-4.5 sm:h-5 sm:w-5" aria-hidden="true" />
        </span>
        <span class="min-w-0 flex-1">
          <span class="block text-xs font-semibold leading-snug text-slate-900 break-words sm:text-sm">{{ item.label }}</span>
          <span class="block text-[10px] text-slate-500 leading-snug break-words sm:text-xs">{{ item.hint }}</span>
        </span>
        <ChevronRightIcon class="hidden h-4 w-4 shrink-0 text-slate-400 transition group-hover:text-va-700 sm:block" aria-hidden="true" />
      </RouterLink>
    </div>
    <RouterLink
      :to="{ name: 'portalRequestList' }"
      class="mt-3 flex min-h-[38px] items-center justify-center rounded-xl border border-slate-200/90 bg-slate-50/80 text-xs font-semibold text-va-800 shadow-sm transition hover:border-va-300 hover:bg-va-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-va-800 sm:mt-4 sm:min-h-[44px] sm:text-sm"
    >
      {{ t('portal.view_all_requests') }}
    </RouterLink>
  </section>
</template>

<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { AcademicCapIcon, BriefcaseIcon, ChevronRightIcon, ClockIcon, CubeIcon, MapPinIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  pendingCount: { type: Number, default: 0 },
})

const { t } = useI18n()

const pendingCount = computed(() => {
  const n = props.pendingCount
  return typeof n === 'number' && Number.isFinite(n) ? Math.max(0, n) : 0
})

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
])

const extracurricularQuick = computed(() => ({
  to: { name: 'portalExtracurricularHome' },
  label: t('portal.quick_extracurricular_label'),
  hint: t('portal.quick_extracurricular_hint'),
  icon: AcademicCapIcon,
}))
</script>
