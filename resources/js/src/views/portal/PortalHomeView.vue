<template>
  <div>
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-10">
      <div class="xl:grid xl:grid-cols-[minmax(0,20rem)_1fr] xl:gap-10">
        <aside class="mb-8 space-y-6 xl:sticky xl:top-[5.5rem] xl:mb-0 xl:self-start">
          <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">{{ t('portal.home_sidebar_title') }}</h2>
            <p v-if="auth.user?.name" class="mt-3 text-sm font-medium text-slate-800">{{ auth.user.name }}</p>
            <p v-if="auth.user?.email" class="mt-1 break-all text-xs text-slate-500">{{ auth.user.email }}</p>
            <div class="mt-4">
              <RouterLink
                :to="{ name: 'portalCreate' }"
                class="flex min-h-[48px] w-full items-center justify-center rounded-2xl bg-va-800 px-4 text-center text-sm font-semibold text-white shadow-md hover:bg-va-900"
              >
                {{ t('portal.cta_primary') }}
              </RouterLink>
            </div>
          </div>

          <div
            v-if="!loading && items.length && pagination"
            class="grid grid-cols-2 gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"
          >
            <div>
              <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ t('portal.stat_pending') }}</p>
              <p class="mt-1 font-mono text-xl font-bold text-amber-800">{{ pendingLoadedCount }}</p>
            </div>
            <div>
              <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ t('portal.stat_approved') }}</p>
              <p class="mt-1 font-mono text-xl font-bold text-emerald-800">{{ approvedLoadedCount }}</p>
            </div>
            <p class="col-span-2 text-[10px] leading-snug text-slate-400">{{ t('portal.stat_loaded_hint') }}</p>
          </div>

          <div
            v-if="!loading && pagination && (pagination.total ?? 0) >= 0"
            class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"
          >
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ t('portal.stat_total') }}</p>
            <p class="mt-1 font-mono text-2xl font-bold text-slate-900">{{ pagination.total ?? 0 }}</p>
          </div>
        </aside>

        <div class="min-w-0">
          <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ t('portal.home_heading') }}</h1>
              <p class="mt-2 text-sm text-slate-600">{{ t('portal.home_lead') }}</p>
            </div>
          </div>

          <PortalRequestSkeleton v-if="loading" class="mt-8" :aria-label="t('portal.loading_requests')" />

          <div v-else-if="fetchError" class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ fetchError }}
          </div>

          <template v-else>
            <div v-if="items.length" class="mt-6 flex flex-wrap gap-2" role="toolbar" :aria-label="t('portal.filter_toolbar_label')">
              <button
                v-for="opt in filterOptions"
                :key="opt.key"
                type="button"
                class="min-h-[40px] rounded-full border px-4 text-xs font-semibold transition sm:text-sm"
                :class="
                  filterStatus === opt.key
                    ? 'border-va-800 bg-va-800 text-white shadow-md hover:bg-va-900'
                    : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50'
                "
                :aria-pressed="filterStatus === opt.key"
                @click="filterStatus = opt.key"
              >
                {{ opt.label }}
              </button>
            </div>

            <PortalEmptyState
              v-if="!items.length"
              class="mt-10"
              :title="t('portal.empty_title')"
              :description="t('portal.empty_desc')"
            >
              <template #action>
                <RouterLink
                  :to="{ name: 'portalCreate' }"
                  class="inline-flex min-h-[48px] items-center justify-center rounded-2xl bg-va-800 px-8 text-sm font-semibold text-white shadow-md hover:bg-va-900"
                >
                  {{ t('portal.cta_primary') }}
                </RouterLink>
              </template>
            </PortalEmptyState>

            <template v-else-if="!filteredItems.length">
              <div class="mt-10 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center shadow-sm">
                <p class="text-sm font-medium text-slate-800">{{ t('portal.filter_empty') }}</p>
                <button
                  type="button"
                  class="mt-4 inline-flex min-h-[44px] items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                  @click="filterStatus = 'all'"
                >
                  {{ t('portal.filter_clear') }}
                </button>
              </div>
            </template>

            <div v-else class="mt-8 space-y-4">
              <PortalRequestCard
                v-for="r in filteredItems"
                :key="r.id"
                :req="r"
                :to="{ name: 'portalRequestDetail', params: { id: String(r.id) } }"
              />
              <button
                v-if="pagination && pagination.current_page < pagination.last_page"
                type="button"
                class="relative z-30 flex w-full min-h-[52px] items-center justify-center rounded-2xl border border-slate-200 bg-white text-sm font-semibold text-slate-800 shadow hover:bg-slate-50 disabled:opacity-50"
                :disabled="loadingMore"
                @click="loadMore"
              >
                {{ loadingMore ? t('portal.loading_more') : t('portal.load_more') }}
              </button>
            </div>
          </template>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '../../store'
import { listPortalRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import PortalRequestCard from '../../components/portal/PortalRequestCard.vue'
import PortalEmptyState from '../../components/portal/PortalEmptyState.vue'
import PortalRequestSkeleton from '../../components/portal/PortalRequestSkeleton.vue'

const { t } = useI18n()
const auth = useAuthStore()

const loading = ref(true)
const loadingMore = ref(false)
const fetchError = ref('')
const items = ref([])
const pagination = ref(null)

const filterStatus = ref('all')

const filterOptions = computed(() => [
  { key: 'all', label: t('portal.filter_all') },
  { key: 'pending', label: t('portal.filter_pending') },
  { key: 'approved', label: t('portal.filter_approved') },
  { key: 'rejected', label: t('portal.filter_rejected') },
])

const filteredItems = computed(() => {
  if (filterStatus.value === 'all') return items.value
  return items.value.filter((req) => {
    const st = req.status
    if (filterStatus.value === 'pending') return st === 'pending' || st === 'price_filled'
    if (filterStatus.value === 'approved') return st === 'approved'
    if (filterStatus.value === 'rejected') return st === 'rejected'
    return true
  })
})

const pendingLoadedCount = computed(() =>
  items.value.filter((r) => r.status === 'pending' || r.status === 'price_filled').length,
)

const approvedLoadedCount = computed(() => items.value.filter((r) => r.status === 'approved').length)

async function loadFirst() {
  loading.value = true
  fetchError.value = ''
  try {
    const data = await listPortalRequests({ per_page: 10, page: 1 })
    items.value = data.items ?? []
    pagination.value = data.meta ?? null
  } catch (e) {
    fetchError.value = formatApiError(e, t('portal.load_requests_fail'))
    items.value = []
    pagination.value = null
  } finally {
    loading.value = false
  }
}

async function loadMore() {
  if (!pagination.value || loadingMore.value) return
  const nextPage = (pagination.value.current_page ?? 1) + 1
  if (nextPage > (pagination.value.last_page ?? 1)) return

  loadingMore.value = true
  try {
    const data = await listPortalRequests({ per_page: 10, page: nextPage })
    const merged = [...items.value, ...(data.items ?? [])]
    items.value = merged
    pagination.value = data.meta ?? pagination.value
  } catch (e) {
    fetchError.value = formatApiError(e, t('portal.load_requests_fail'))
  } finally {
    loadingMore.value = false
  }
}

onMounted(() => {
  loadFirst()
})
</script>
