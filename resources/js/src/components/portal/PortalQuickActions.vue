<template>
  <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ t('portal.quick_actions_title') }}</h2>
    <div class="mt-4 grid gap-3">
      <RouterLink
        :to="{ name: 'portalRequestList', query: { filter: 'pending' } }"
        class="group flex min-h-[52px] items-center gap-3 rounded-2xl border border-amber-200/80 bg-amber-50/50 px-4 py-3 text-left shadow-sm transition hover:border-amber-300 hover:bg-amber-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600"
      >
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-amber-200/80">
          <ClockIcon class="h-6 w-6 text-amber-600" aria-hidden="true" />
        </span>
        <span class="min-w-0 flex-1">
          <span class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-semibold text-slate-900">{{ t('portal.quick_pending_requests_label') }}</span>
            <span
              v-if="pendingCount > 0"
              class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-amber-600 px-1.5 py-0.5 text-[10px] font-bold tabular-nums text-white"
            >
              {{ pendingCount > 99 ? '99+' : pendingCount }}
            </span>
          </span>
          <span class="mt-0.5 block text-xs text-slate-500">{{ t('portal.quick_pending_requests_hint') }}</span>
        </span>
        <ChevronRightIcon class="h-5 w-5 shrink-0 text-slate-400 transition group-hover:text-amber-600" aria-hidden="true" />
      </RouterLink>

      <RouterLink
        :to="extracurricularQuick.to"
        class="group flex min-h-[52px] items-center gap-3 rounded-2xl border border-violet-200/80 bg-violet-50/60 px-4 py-3 text-left shadow-sm transition hover:border-violet-300 hover:bg-violet-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600"
      >
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-violet-200/80">
          <component :is="extracurricularQuick.icon" class="h-6 w-6 text-violet-700" aria-hidden="true" />
        </span>
        <span class="min-w-0 flex-1">
          <span class="block text-sm font-semibold text-slate-900">{{ extracurricularQuick.label }}</span>
          <span class="mt-0.5 block text-xs text-slate-500">{{ extracurricularQuick.hint }}</span>
        </span>
        <ChevronRightIcon class="h-5 w-5 shrink-0 text-slate-400 transition group-hover:text-violet-600" aria-hidden="true" />
      </RouterLink>
      <RouterLink
        v-for="item in items"
        :key="item.type"
        :to="{ name: 'portalCreate', query: { type: item.type } }"
        class="group flex min-h-[52px] items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 text-left shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50/50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
      >
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white shadow-sm ring-1 ring-slate-200/80">
          <component :is="item.icon" class="h-6 w-6 text-indigo-600" aria-hidden="true" />
        </span>
        <span class="min-w-0 flex-1">
          <span class="block text-sm font-semibold text-slate-900">{{ item.label }}</span>
          <span class="mt-0.5 block text-xs text-slate-500">{{ item.hint }}</span>
        </span>
        <ChevronRightIcon class="h-5 w-5 shrink-0 text-slate-400 transition group-hover:text-indigo-500" aria-hidden="true" />
      </RouterLink>
    </div>
    <RouterLink
      :to="{ name: 'portalRequestList' }"
      class="mt-4 flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200 bg-slate-50/80 text-sm font-semibold text-indigo-600 transition hover:border-indigo-200 hover:bg-indigo-50/50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
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
