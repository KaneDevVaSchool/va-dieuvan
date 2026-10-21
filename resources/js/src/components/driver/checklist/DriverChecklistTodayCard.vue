<template>
  <section
    v-if="visible"
    class="rounded-xl bg-driver-card p-3.5 ring-1 ring-white/[0.06]"
    data-testid="driver-checklist-today-card"
  >
    <div class="flex items-center justify-between gap-2">
      <h2 class="flex items-center gap-1.5 text-sm font-bold">
        <ClipboardDocumentCheckIcon class="h-4.5 w-4.5 text-driver-accent" aria-hidden="true" />
        {{ t('checklist.today_title') }}
      </h2>
      <RouterLink :to="{ name: 'driverChecklists' }" class="text-xs font-semibold text-driver-accent">
        {{ t('checklist.see_all') }}
      </RouterLink>
    </div>

    <div
      v-for="f in failedToday"
      :key="f.submission_id"
      class="mt-2.5 flex items-start gap-2 rounded-lg bg-rose-500/15 px-3 py-2 text-xs text-rose-100 ring-1 ring-rose-400/30"
      role="alert"
    >
      <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 shrink-0 text-rose-300" aria-hidden="true" />
      <span>{{ t('checklist.failed_banner', { plate: f.license_plate || '—', count: f.critical_ng_count }) }}</span>
    </div>

    <p v-if="loading && !periods.length" class="mt-2.5 text-xs text-driver-muted">{{ t('checklist.loading') }}</p>

    <ul class="mt-2.5 space-y-1.5">
      <li v-for="p in periods" :key="p.template.id">
        <RouterLink
          :to="p.draft_id ? { name: 'driverChecklistForm', params: { id: p.draft_id } } : { name: 'driverChecklists', query: { start: p.template.id } }"
          class="flex min-h-[48px] items-center gap-2.5 rounded-lg bg-driver-surface px-3 py-2 ring-1 ring-white/[0.06] transition active:scale-[0.99]"
        >
          <span class="min-w-0 flex-1">
            <span class="block truncate text-xs sm:text-sm font-semibold">{{ t(`checklist.freq_${p.template.frequency}`) }}</span>
            <span class="block truncate text-[11px] text-driver-muted">{{ p.period_label }}</span>
          </span>
          <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold" :class="statePill(p.state)">
            {{ t(`checklist.state_${p.state}`) }}
          </span>
          <ChevronRightIcon class="h-4 w-4 shrink-0 text-driver-muted" aria-hidden="true" />
        </RouterLink>
      </li>
    </ul>
  </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronRightIcon, ClipboardDocumentCheckIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { getChecklistOverview } from '../../../api/checklists'
import { useAuthStore } from '../../../store'

const { t } = useI18n()
const auth = useAuthStore()

const loading = ref(false)
const periods = ref([])
const failedToday = ref([])
const hasProfile = ref(true)

const visible = computed(
  () => auth.hasPermission('checklist.submit') && auth.isNavFeatureVisible('module.driver_checklist') && hasProfile.value,
)

function statePill(state) {
  return {
    pass: 'bg-emerald-500/20 text-emerald-200',
    fail: 'bg-rose-500/20 text-rose-200',
    draft: 'bg-amber-500/20 text-amber-200',
    todo: 'bg-white/10 text-driver-ink',
  }[state] || 'bg-white/10 text-driver-muted'
}

async function load() {
  if (!auth.hasPermission('checklist.submit')) return
  loading.value = true
  try {
    const data = await getChecklistOverview()
    periods.value = data?.periods ?? []
    failedToday.value = data?.failed_today ?? []
    hasProfile.value = data?.has_driver_profile !== false
  } catch {
    periods.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
defineExpose({ reload: load })
</script>
