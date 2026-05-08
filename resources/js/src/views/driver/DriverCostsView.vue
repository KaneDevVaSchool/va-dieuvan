<template>
  <div class="relative min-h-full w-full overflow-x-hidden bg-driver-bg text-driver-ink">
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

    <DriverCostStatusTabs
      v-model="statusTab"
      :tabs="tabItems"
      :aria-label="t('driver_costs.filter_aria')"
    />

    <div
      class="mx-auto w-full max-w-lg px-4 pb-[calc(6rem+env(safe-area-inset-bottom))] pt-4 sm:max-w-2xl sm:px-5"
    >
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
          <h1 class="text-2xl font-bold tracking-tight text-driver-ink sm:text-[1.65rem]">
            {{ t('driver_costs.title') }}
          </h1>
        </div>
        <RouterLink
          to="/driver/schedule"
          class="flex min-h-[44px] shrink-0 items-center gap-2 rounded-2xl bg-driver-accent/15 px-4 text-sm font-bold text-driver-accent ring-1 ring-driver-accent/35 transition hover:bg-driver-accent/25 active:scale-[0.98]"
        >
          <PlusCircleIcon class="h-5 w-5" aria-hidden="true" />
          {{ t('driver_costs.cta_add') }}
        </RouterLink>
      </div>

      <p
        v-if="errorMsg"
        class="mt-4 rounded-2xl border border-amber-500/35 bg-amber-950/35 px-4 py-3 text-sm text-amber-100 ring-1 ring-amber-500/20"
      >
        {{ t('driver_home.load_error') }}
      </p>

      <!-- Skeleton -->
      <div v-if="loading && !items.length" class="mt-6 space-y-4">
        <div v-for="s in 4" :key="s" class="animate-pulse overflow-hidden rounded-[1.35rem] bg-driver-card ring-1 ring-white/[0.06]">
          <div class="h-36 bg-driver-surface/90" />
          <div class="h-14 border-t border-white/[0.06] bg-driver-surface/60" />
        </div>
      </div>

      <!-- Empty -->
      <div
        v-else-if="!loading && !items.length"
        class="mt-16 rounded-[1.35rem] bg-driver-card px-6 py-14 text-center ring-1 ring-white/[0.06]"
      >
        <ClipboardDocumentListIcon class="mx-auto h-14 w-14 text-driver-muted/45" aria-hidden="true" />
        <p class="mt-5 text-lg font-semibold text-driver-ink">{{ t('driver_costs.empty_title') }}</p>
        <p class="mt-2 text-base text-driver-muted">{{ t('driver_costs.empty_hint') }}</p>
        <RouterLink
          to="/driver/schedule"
          class="mt-8 inline-flex min-h-[52px] items-center justify-center rounded-2xl bg-driver-accent px-8 text-base font-bold text-driver-bg transition hover:brightness-110 active:scale-[0.99]"
        >
          {{ t('driver_costs.cta_add') }}
        </RouterLink>
      </div>

      <ul v-else class="mt-6 space-y-5">
        <li v-for="c in items" :key="c.id">
          <DriverCostListCard
            :cost="c"
            :status-label="statusLabel"
            :type-label="typeLabel"
            :trip-cta="t('driver_costs.open_trip')"
          />
        </li>
      </ul>

      <div ref="sentinelRef" class="h-4 w-full shrink-0 scroll-mt-4" aria-hidden="true" />

      <div v-if="loadingMore" class="flex justify-center py-8">
        <span class="inline-block h-9 w-9 animate-spin rounded-full border-2 border-driver-accent border-t-transparent" />
      </div>

      <p v-if="items.length && noMore" class="pb-8 pt-2 text-center text-sm text-driver-muted/70">
        {{ t('driver_costs.end_of_list') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { ClipboardDocumentListIcon, PlusCircleIcon } from '@heroicons/vue/24/outline'
import DriverCostStatusTabs from '../../components/driver/costs/DriverCostStatusTabs.vue'
import DriverCostListCard from '../../components/driver/costs/DriverCostListCard.vue'
import { useDriverCostsList } from '../../composables/useDriverCostsList'
import { usePullToRefresh } from '../../composables/usePullToRefresh'

const { t, te } = useI18n()
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
  return map[st] ?? st ?? '—'
}

function typeLabel(type) {
  const raw = String(type ?? '').trim()
  if (!raw) return '—'
  const slug = raw.toLowerCase().replace(/[^a-z0-9_]/g, '_')
  const i18nKey = `trip_detail.costs.type_${slug}`
  if (te(i18nKey)) return t(i18nKey)
  return raw
}

onMounted(() => {
  void load({ append: false })
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
