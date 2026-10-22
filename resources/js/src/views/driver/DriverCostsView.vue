<template>
  <div class="relative min-h-full w-full overflow-x-clip bg-driver-bg text-driver-ink">
    <!-- Pull distance indicator -->
    <div
      class="pointer-events-none fixed left-1/2 top-[calc(env(safe-area-inset-top)+4px)] z-40 -translate-x-1/2 transition-opacity duration-150"
      :class="ptrRefreshing || ptrPulling ? 'opacity-100' : 'opacity-0'"
      aria-hidden="true"
    >
      <div
        class="flex items-center gap-2 rounded-full bg-driver-card px-4 py-2 text-sm font-semibold text-driver-accent shadow-lg ring-1 ring-driver-accent/25"
      >
        <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-driver-accent border-t-transparent" />
        {{ ptrRefreshing ? t('driver_costs.ptr_refreshing') : t('driver_costs.ptr_pull') }}
      </div>
    </div>

    <DriverPageHeader :title="t('driver_costs.title')">
      <template #actions>
        <RouterLink
          :to="{ name: 'driverCostCreate' }"
          class="inline-flex min-h-[36px] shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg bg-driver-accent px-3 py-1.5 text-xs font-semibold text-driver-bg transition hover:brightness-110 active:scale-[0.98] sm:text-sm"
        >
          <PlusIcon class="h-4 w-4 shrink-0" :stroke-width="2.5" aria-hidden="true" />
          {{ t('driver_costs.cta_add') }}
        </RouterLink>
      </template>
      <DriverCostStatusTabs
        v-model="statusTab"
        :tabs="tabItems"
        :aria-label="t('driver_costs.filter_aria')"
      />
    </DriverPageHeader>

    <div
      class="driver-stagger mx-auto w-full max-w-lg px-3.5 pb-4 pt-3 sm:max-w-2xl sm:px-5"
    >
      <RouterLink
        v-if="canExpense"
        :to="{ name: 'driverExpenses' }"
        class="flex min-h-[48px] items-center gap-2.5 rounded-xl bg-driver-card px-3.5 py-2.5 ring-1 ring-white/[0.08] active:scale-[0.99]"
        data-testid="driver-costs-expense-link"
      >
        <DocumentDuplicateIcon class="h-5 w-5 shrink-0 text-driver-accent" aria-hidden="true" />
        <span class="min-w-0 flex-1">
          <span class="block text-sm font-bold">{{ t('expense.costs_link_title') }}</span>
          <span class="block text-[11px] text-driver-muted">{{ t('expense.costs_link_hint') }}</span>
        </span>
        <ChevronRightIcon class="h-4.5 w-4.5 shrink-0 text-driver-muted" aria-hidden="true" />
      </RouterLink>

      <p
        v-if="errorMsg"
        class="mt-3 rounded-xl border border-amber-500/35 bg-amber-950/35 px-3.5 py-2.5 text-xs text-amber-100 ring-1 ring-amber-500/20"
      >
        {{ t('driver_home.load_error') }}
      </p>

      <!-- Skeleton -->
      <div v-if="loading && !items.length" class="mt-4 space-y-3">
        <div v-for="s in 4" :key="s" class="animate-pulse overflow-hidden rounded-xl bg-driver-card ring-1 ring-white/[0.06]">
          <div class="h-28 bg-driver-surface/90" />
          <div class="h-10 border-t border-white/[0.06] bg-driver-surface/60" />
        </div>
      </div>

      <!-- Empty -->
      <div
        v-else-if="!loading && !items.length"
        class="mt-4 rounded-xl bg-driver-card px-4 py-6 text-center ring-1 ring-white/[0.06]"
      >
        <ClipboardDocumentListIcon class="mx-auto h-8 w-8 text-driver-muted/45" aria-hidden="true" />
        <p class="mt-3 text-sm font-semibold text-driver-ink">{{ t('driver_costs.empty_title') }}</p>
        <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-driver-muted">{{ t('driver_costs.empty_hint') }}</p>
      </div>

      <ul v-else class="mt-3 space-y-3">
        <li v-for="c in items" :key="c.id">
          <DriverCostListCard
            :cost="c"
            :status-label="statusLabel"
            :type-label="typeLabel"
            :trip-cta="t('driver_costs.open_trip')"
            :standalone-badge="t('driver_costs.badge_standalone')"
          />
        </li>
      </ul>

      <div ref="sentinelRef" class="h-4 w-full shrink-0 scroll-mt-4" aria-hidden="true" />

      <div v-if="loadingMore" class="flex justify-center py-8">
        <span class="inline-block h-9 w-9 animate-spin rounded-full border-2 border-driver-accent border-t-transparent" />
      </div>

      <p v-if="items.length && noMore" class="pt-2 text-center text-sm text-driver-muted/70">
        {{ t('driver_costs.end_of_list') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { ChevronRightIcon, ClipboardDocumentListIcon, DocumentDuplicateIcon, PlusIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '../../store'
import DriverPageHeader from '../../components/driver/DriverPageHeader.vue'
import DriverCostStatusTabs from '../../components/driver/costs/DriverCostStatusTabs.vue'
import DriverCostListCard from '../../components/driver/costs/DriverCostListCard.vue'
import { useDriverCostsList } from '../../composables/useDriverCostsList'
import { useDriverVisiblePoll } from '../../composables/useDriverVisiblePoll'
import { usePullToRefresh } from '../../composables/usePullToRefresh'

const { t, te } = useI18n()
const auth = useAuthStore()
const canExpense = computed(
  () => auth.hasPermission('expense.submit') && auth.isNavFeatureVisible('module.driver_expense'),
)
const route = useRoute()
const router = useRouter()

const statusTab = computed({
  get: () => (typeof route.query.status === 'string' ? route.query.status : ''),
  set: (v) => {
    router.replace({ path: '/driver/costs', query: v ? { status: v } : {} })
  },
})

const tabItems = computed(() => [
  { value: '', label: t('driver_costs.filter_all') },
  { value: 'submitted', label: t('driver_costs.filter_submitted') },
  { value: 'confirmed', label: t('driver_costs.filter_confirmed') },
  { value: 'rejected', label: t('driver_costs.filter_rejected') },
])

const statusRef = computed(() => statusTab.value)

const { items, loading, loadingMore, errorMsg, meta, load, refresh, loadMore } = useDriverCostsList(statusRef)

const { start: startCostsVisiblePoll } = useDriverVisiblePoll(() => refresh())

const { pulling: ptrPulling, refreshing: ptrRefreshing } = usePullToRefresh(() => refresh())

const sentinelRef = ref(null)
/** @type {import('vue').Ref<IntersectionObserver | null>} */
let io = null

const noMore = computed(() => {
  const cur = meta.value?.current_page ?? 1
  const last = meta.value?.last_page ?? 1
  return cur >= last && items.value.length > 0
})

function statusLabel(st) {
  const map = {
    submitted: t('driver_costs.st_submitted'),
    confirmed: t('driver_costs.st_confirmed'),
    rejected: t('driver_costs.st_rejected'),
    draft: t('driver_costs.st_draft'),
  }
  return map[st] ?? t('driver_costs.st_submitted')
}

function typeLabel(type) {
  const raw = String(type ?? '').trim()
  if (!raw) return '—'
  const slug = raw.toLowerCase().replace(/[^a-z0-9_]/g, '_')
  const driverKey = `driver_trip_detail.cost_type_${slug}`
  if (te(driverKey)) return t(driverKey)
  const expenseKey = `expense.cost_type_${slug}`
  if (te(expenseKey)) return t(expenseKey)
  return t('driver_trip_detail.cost_type_other')
}

onMounted(() => {
  void load({ append: false }).finally(() => startCostsVisiblePoll())
  io = new IntersectionObserver(
    (entries) => {
      if (entries.some((e) => e.isIntersecting)) void loadMore()
    },
    { rootMargin: '120px' },
  )
  if (sentinelRef.value) io.observe(sentinelRef.value)
})

watch(sentinelRef, (el, prev) => {
  if (io && prev) io.unobserve(prev)
  if (io && el) io.observe(el)
})

onUnmounted(() => {
  if (io) io.disconnect()
})
</script>
