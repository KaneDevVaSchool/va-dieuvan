<template>
  <div>
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-10">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <RouterLink
            :to="{ name: 'portalHome' }"
            class="text-xs font-semibold text-indigo-600 underline-offset-2 hover:underline"
          >
            ← {{ t('portal.back_dashboard') }}
          </RouterLink>
          <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">{{ t('portal.list_page_heading') }}</h1>
          <p class="mt-2 text-sm text-slate-600">{{ t('portal.list_page_lead') }}</p>
        </div>
        <RouterLink
          :to="{ name: 'portalCreate' }"
          class="inline-flex min-h-[48px] shrink-0 items-center justify-center rounded-2xl bg-indigo-600 px-6 text-sm font-bold text-white shadow-md hover:bg-indigo-700"
        >
          {{ t('portal.cta_primary') }}
        </RouterLink>
      </div>

      <div class="mt-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex min-w-0 flex-1 flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
          <label class="sr-only" for="portal-list-q">{{ t('portal.search_placeholder') }}</label>
          <input
            id="portal-list-q"
            v-model="searchInput"
            type="search"
            autocomplete="off"
            class="min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-4 text-sm shadow-sm outline-none ring-indigo-500/15 focus:border-indigo-400 focus:ring-2 sm:max-w-md"
            :placeholder="t('portal.search_placeholder')"
          />
          <div class="flex shrink-0 items-center gap-2">
            <label class="text-xs font-semibold text-slate-600" for="portal-sort">{{ t('portal.sort_label') }}</label>
            <select
              id="portal-sort"
              v-model="sort"
              class="min-h-[44px] rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium shadow-sm outline-none focus:border-indigo-400 focus:ring-2"
              @change="reloadFromStart"
            >
              <option value="depart_desc">{{ t('portal.sort_depart_desc') }}</option>
              <option value="depart_asc">{{ t('portal.sort_depart_asc') }}</option>
              <option value="created_desc">{{ t('portal.sort_created_desc') }}</option>
              <option value="created_asc">{{ t('portal.sort_created_asc') }}</option>
            </select>
          </div>
        </div>
      </div>

      <div class="mt-5 flex flex-wrap gap-2" role="toolbar" :aria-label="t('portal.filter_toolbar_label')">
        <button
          v-for="opt in filterOptions"
          :key="opt.key"
          type="button"
          class="min-h-[40px] rounded-full border px-4 text-xs font-semibold transition sm:text-sm"
          :class="
            filterStatus === opt.key
              ? 'border-indigo-600 bg-indigo-600 text-white shadow-md hover:bg-indigo-700'
              : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50'
          "
          :aria-pressed="filterStatus === opt.key"
          @click="onFilter(opt.key)"
        >
          {{ opt.label }}
        </button>
      </div>

      <PortalRequestSkeleton v-if="loading && !loadingMore" class="mt-8" :aria-label="t('portal.loading_requests')" />

      <div v-else-if="fetchError" class="mt-8 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        {{ fetchError }}
      </div>

      <template v-else>
        <PortalEmptyState
          v-if="!items.length"
          class="mt-10"
          :title="t('portal.empty_title')"
          :description="t('portal.empty_desc')"
        >
          <template #action>
            <RouterLink
              :to="{ name: 'portalCreate' }"
              class="inline-flex min-h-[48px] items-center justify-center rounded-2xl bg-indigo-600 px-8 text-sm font-semibold text-white shadow-md hover:bg-indigo-700"
            >
              {{ t('portal.cta_primary') }}
            </RouterLink>
          </template>
        </PortalEmptyState>

        <div v-else class="mt-8 space-y-4">
          <PortalRequestsTable :requests="items" />
          <button
            v-if="pagination && pagination.current_page < pagination.last_page"
            type="button"
            class="relative z-30 flex w-full min-h-[52px] items-center justify-center rounded-2xl border border-slate-200 bg-white text-sm font-semibold text-slate-800 shadow transition hover:border-indigo-200 hover:bg-indigo-50/60 disabled:opacity-50"
            :disabled="loadingMore"
            @click="loadMore"
          >
            {{ loadingMore ? t('portal.loading_more') : t('portal.load_more') }}
          </button>
        </div>
      </template>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { listPortalRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import PortalEmptyState from '../../components/portal/PortalEmptyState.vue'
import PortalRequestSkeleton from '../../components/portal/PortalRequestSkeleton.vue'
import PortalRequestsTable from '../../components/portal/PortalRequestsTable.vue'

const PER_PAGE = 15

const { t } = useI18n()

const loading = ref(true)
const loadingMore = ref(false)
const fetchError = ref('')
const items = ref([])
const pagination = ref(null)

const filterStatus = ref('all')
const sort = ref('depart_desc')
const searchInput = ref('')
const debouncedQ = ref('')

let debounceTimer = null

const filterOptions = computed(() => [
  { key: 'all', label: t('portal.filter_all') },
  { key: 'pending', label: t('portal.filter_pending') },
  { key: 'approved', label: t('portal.filter_approved') },
  { key: 'rejected', label: t('portal.filter_rejected') },
  { key: 'returned', label: t('portal.filter_returned') },
])

watch(searchInput, (v) => {
  clearTimeout(debounceTimer)
  debounceTimer = window.setTimeout(() => {
    debouncedQ.value = String(v ?? '').trim()
  }, 350)
})

watch(debouncedQ, () => {
  reloadFromStart()
})

function onFilter(key) {
  filterStatus.value = key
  reloadFromStart()
}

function listParams(page) {
  const params = {
    per_page: PER_PAGE,
    page,
    sort: sort.value,
    filter: filterStatus.value === 'all' ? undefined : filterStatus.value,
    q: debouncedQ.value || undefined,
  }
  return params
}

async function loadFirst() {
  loading.value = true
  fetchError.value = ''
  try {
    const data = await listPortalRequests(listParams(1))
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
    const data = await listPortalRequests(listParams(nextPage))
    items.value = [...items.value, ...(data.items ?? [])]
    pagination.value = data.meta ?? pagination.value
  } catch (e) {
    fetchError.value = formatApiError(e, t('portal.load_requests_fail'))
  } finally {
    loadingMore.value = false
  }
}

function reloadFromStart() {
  loadFirst()
}

onMounted(() => {
  loadFirst()
})

onBeforeUnmount(() => clearTimeout(debounceTimer))
</script>
