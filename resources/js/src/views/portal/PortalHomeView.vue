<template>
  <div>
    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-10">
      <!-- Heading + primary CTA -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ t('portal.dashboard_heading') }}</h1>
          <p class="mt-2 max-w-2xl text-sm text-slate-600">{{ t('portal.dashboard_lead') }}</p>
        </div>
        <RouterLink
          :to="{ name: 'portalCreate' }"
          class="inline-flex min-h-[48px] shrink-0 items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-6 text-sm font-bold text-white shadow-md transition hover:bg-indigo-700"
        >
          <PlusCircleIcon class="h-5 w-5" aria-hidden="true" />
          {{ t('portal.cta_primary') }}
        </RouterLink>
      </div>

      <PortalKpiCards class="mt-8" :loading="summaryLoading" :summary="summary" />

      <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem] xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-6">
          <div class="flex flex-wrap items-end justify-between gap-3">
            <h2 class="text-lg font-bold text-slate-900">{{ t('portal.recent_requests_heading') }}</h2>
            <RouterLink
              :to="{ name: 'portalRequestList' }"
              class="text-sm font-semibold text-indigo-600 underline-offset-2 hover:underline"
            >
              {{ t('portal.view_all_requests') }}
            </RouterLink>
          </div>

          <PortalRequestSkeleton v-if="loading && !silentListRefresh" class="mt-2" :aria-label="t('portal.loading_requests')" />

          <div v-else-if="fetchError" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ fetchError }}
          </div>

          <PortalEmptyState
            v-else-if="!items.length"
            class="mt-2"
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

          <PortalRequestsTable v-else :requests="items" />
        </div>

        <aside class="space-y-6 lg:sticky lg:top-[5.5rem] lg:self-start">
          <PortalQuickActions />
          <PortalNotificationsPanel :refresh-tick="pollTick" />
        </aside>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { PlusCircleIcon } from '@heroicons/vue/24/outline'
import { getPortalRequestsSummary, listPortalRequests } from '../../api/requests'
import { formatApiError } from '../../api/http'
import PortalEmptyState from '../../components/portal/PortalEmptyState.vue'
import PortalRequestSkeleton from '../../components/portal/PortalRequestSkeleton.vue'
import PortalKpiCards from '../../components/portal/PortalKpiCards.vue'
import PortalQuickActions from '../../components/portal/PortalQuickActions.vue'
import PortalNotificationsPanel from '../../components/portal/PortalNotificationsPanel.vue'
import PortalRequestsTable from '../../components/portal/PortalRequestsTable.vue'

const RECENT_LIMIT = 8

const { t } = useI18n()

const loading = ref(true)
const silentListRefresh = ref(false)
const fetchError = ref('')
const items = ref([])

const summary = ref(null)
const summaryLoading = ref(true)

const pollTick = ref(0)
let pollTimer = null

async function loadSummary(silent = false) {
  if (!silent) summaryLoading.value = true
  try {
    summary.value = await getPortalRequestsSummary()
  } catch {
    if (!silent) summary.value = { processing: 0, pending: 0, completed_this_month: 0, rejected: 0 }
  } finally {
    if (!silent) summaryLoading.value = false
  }
}

async function loadRecent(silent = false) {
  silentListRefresh.value = silent
  if (!silent) {
    loading.value = true
    fetchError.value = ''
  }
  try {
    const data = await listPortalRequests({ per_page: RECENT_LIMIT, page: 1 })
    items.value = data.items ?? []
  } catch (e) {
    if (!silent) {
      fetchError.value = formatApiError(e, t('portal.load_requests_fail'))
      items.value = []
    }
  } finally {
    silentListRefresh.value = false
    if (!silent) loading.value = false
  }
}

async function refreshAllQuiet() {
  pollTick.value += 1
  await Promise.all([loadSummary(true), loadRecent(true)])
}

onMounted(async () => {
  await Promise.all([loadSummary(false), loadRecent(false)])
  pollTimer = window.setInterval(refreshAllQuiet, 60_000)
})

onBeforeUnmount(() => {
  if (pollTimer) window.clearInterval(pollTimer)
})
</script>
