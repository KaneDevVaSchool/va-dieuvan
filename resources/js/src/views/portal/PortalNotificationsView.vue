<template>
  <div>
    <section class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:py-10">
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
          {{ t('portal.notifications_page_title_v2') }}
        </h1>
        <p class="mt-2 text-sm text-slate-600">{{ t('portal.notifications_page_lead_v2') }}</p>
      </div>

      <PortalNotificationsSummaryBar
        class="mt-6"
        :loading="loading"
        :total="summaryTotal"
        :unread="unreadTotal"
        :active-tab="activeTab"
        @quick-filter="setTab"
      />

      <PortalNotificationsToolbar
        class="mt-5"
        v-model:search-input="searchInput"
        v-model:active-tab="activeTab"
        :unread-total="unreadTotal"
        :marking-all="markingAll"
        :mark-all-disabled="loading || unreadTotal === 0"
        @mark-all="markAll"
      />

      <p v-if="error" class="mt-6 text-sm text-rose-600">{{ error }}</p>

      <div v-else-if="loading" class="mt-6 space-y-3">
        <div
          v-for="i in 5"
          :key="i"
          class="flex animate-pulse overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm"
        >
          <div class="w-1 shrink-0 bg-slate-100" />
          <div class="flex flex-1 gap-3 px-3 py-3.5 sm:px-4 sm:py-4">
            <div class="h-10 w-10 shrink-0 rounded-lg bg-slate-200" />
            <div class="min-w-0 flex-1 space-y-2">
              <div class="h-4 w-2/3 rounded bg-slate-200" />
              <div class="h-3 w-1/2 rounded bg-slate-100" />
              <div class="h-9 w-full rounded-lg bg-slate-50" />
            </div>
          </div>
        </div>
      </div>

      <p v-else-if="!filteredItems.length" class="mt-10 text-center text-sm text-slate-500">
        {{ t('portal.notifications_empty') }}
      </p>

      <div v-else class="mt-6 space-y-6">
        <template v-for="group in groupedSections" :key="group.key">
          <section v-if="group.items.length">
            <h2 class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">
              {{ group.label }}
            </h2>
            <ul class="space-y-2.5">
              <PortalNotificationCard
                v-for="n in group.items"
                :key="n.id"
                :notification="n"
                :visual="visualFor(n)"
                :relative-time="formatRelativeTime(n.created_at)"
                :link-component="rowLinkComponent(n)"
                :link-bind="rowLinkBind(n)"
                @click="onRowClick"
              />
            </ul>
          </section>
        </template>
      </div>

      <button
        v-if="pagination && pagination.current_page < pagination.last_page"
        type="button"
        class="mt-8 flex w-full min-h-[48px] items-center justify-center rounded-2xl border border-slate-200 bg-white text-sm font-semibold text-slate-800 shadow-sm transition hover:border-va-200 hover:bg-va-50 disabled:opacity-50"
        :disabled="loadingMore"
        data-testid="portal-notifications-load-more"
        @click="loadMore"
      >
        {{ loadingMore ? t('portal.loading_more') : t('portal.notifications_load_more') }}
      </button>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowPathIcon,
  ArrowUturnLeftIcon,
  BellIcon,
  CheckCircleIcon,
  ClockIcon,
  PaperAirplaneIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'
import {
  fetchPortalNotifications,
  markAllPortalNotificationsRead,
  markPortalNotificationRead,
} from '../../api/notifications'
import { formatApiError } from '../../api/http'
import PortalNotificationsSummaryBar from '../../components/portal/PortalNotificationsSummaryBar.vue'
import PortalNotificationsToolbar from '../../components/portal/PortalNotificationsToolbar.vue'
import PortalNotificationCard from '../../components/portal/PortalNotificationCard.vue'
import { refCodeFromPortalNotification, routeFromPortalNotification } from '../../util/portalNotificationRoute'

const { t, locale } = useI18n()

const loading = ref(true)
const loadingMore = ref(false)
const error = ref('')
const items = ref([])
const pagination = ref(null)
const unreadTotal = ref(0)
const markingAll = ref(false)
const activeTab = ref('all')
const searchInput = ref('')
const debouncedSearch = ref('')

let searchDebounce = null

watch(searchInput, (v) => {
  clearTimeout(searchDebounce)
  searchDebounce = window.setTimeout(() => {
    debouncedSearch.value = String(v ?? '').trim()
  }, 350)
})

watch(activeTab, (tab, prev) => {
  if (prev !== undefined && tab !== prev) {
    loadFirst()
  }
})

const summaryTotal = computed(() => pagination.value?.total ?? items.value.length)

const filteredItems = computed(() => {
  let list = items.value
  const q = debouncedSearch.value
  if (q) {
    const lower = q.toLowerCase()
    list = list.filter((n) => {
      const id = String(n.data?.dispatch_request_id ?? '')
      const ref = refCodeFromPortalNotification(n)
      const { origin, destination } = routeFromPortalNotification(n)
      const hay = [
        id,
        ref,
        n.data?.title ?? '',
        n.data?.body ?? '',
        origin,
        destination,
      ]
        .join(' ')
        .toLowerCase()
      return id.includes(q) || ref.toLowerCase().includes(lower) || hay.includes(lower)
    })
  }
  return list
})

function startOfDay(d) {
  const x = new Date(d)
  x.setHours(0, 0, 0, 0)
  return x
}

const groupedSections = computed(() => {
  const today = startOfDay(new Date())
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  const buckets = { today: [], yesterday: [], older: [] }

  for (const n of filteredItems.value) {
    const created = n.created_at ? new Date(n.created_at) : null
    if (!created || Number.isNaN(created.getTime())) {
      buckets.older.push(n)
      continue
    }
    const day = startOfDay(created)
    if (day.getTime() === today.getTime()) buckets.today.push(n)
    else if (day.getTime() === yesterday.getTime()) buckets.yesterday.push(n)
    else buckets.older.push(n)
  }

  return [
    { key: 'today', label: t('portal.notifications_group_today'), items: buckets.today },
    { key: 'yesterday', label: t('portal.notifications_group_yesterday'), items: buckets.yesterday },
    { key: 'older', label: t('portal.notifications_group_older'), items: buckets.older },
  ]
})

function visualFor(n) {
  const type = String(n.type ?? '').toLowerCase()
  const status = String(n.data?.status ?? n.data?.request_status ?? '').toLowerCase()

  if (type.includes('reject') || type.includes('return') || status === 'rejected') {
    return {
      icon: ArrowUturnLeftIcon,
      iconBg: 'bg-amber-100 ring-amber-200/80',
      iconColor: 'text-amber-700',
      badgeLabel: t('portal.notifications_badge_returned'),
      badgeClass: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200/60',
      badgeIcon: ArrowUturnLeftIcon,
    }
  }
  if (type.includes('complet') || status === 'completed') {
    return {
      icon: TruckIcon,
      iconBg: 'bg-emerald-100 ring-emerald-200/80',
      iconColor: 'text-emerald-700',
      badgeLabel: t('portal.notifications_badge_dispatched_done'),
      badgeClass: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200/60',
      badgeIcon: CheckCircleIcon,
    }
  }
  if (type.includes('dispatch') || status === 'approved') {
    return {
      icon: TruckIcon,
      iconBg: 'bg-sky-100 ring-sky-200/80',
      iconColor: 'text-sky-700',
      badgeLabel: t('portal.notifications_badge_dispatched'),
      badgeClass: 'bg-sky-50 text-sky-800 ring-1 ring-sky-200/60',
      badgeIcon: ArrowPathIcon,
    }
  }
  if (type.includes('approv')) {
    return {
      icon: CheckCircleIcon,
      iconBg: 'bg-emerald-100 ring-emerald-200/80',
      iconColor: 'text-emerald-700',
      badgeLabel: t('portal.notifications_badge_approved'),
      badgeClass: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200/60',
      badgeIcon: CheckCircleIcon,
    }
  }
  if (type.includes('submit') || type.includes('creat') || type.includes('new')) {
    return {
      icon: PaperAirplaneIcon,
      iconBg: 'bg-slate-100 ring-slate-200/80',
      iconColor: 'text-slate-600',
      badgeLabel: t('portal.notifications_badge_pending'),
      badgeClass: 'bg-slate-100 text-slate-700 ring-1 ring-slate-200/60',
      badgeIcon: ClockIcon,
    }
  }
  return {
    icon: BellIcon,
    iconBg: 'bg-slate-100 ring-slate-200/80',
    iconColor: 'text-slate-500',
    badgeLabel: '',
    badgeClass: '',
    badgeIcon: null,
  }
}

function formatRelativeTime(raw) {
  if (!raw) return ''
  const d = new Date(raw)
  if (Number.isNaN(d.getTime())) return ''

  const now = new Date()
  const diffMs = now - d
  const diffMin = Math.floor(diffMs / 60_000)
  const diffHr = Math.floor(diffMs / 3_600_000)

  const loc = locale.value === 'vi' ? 'vi-VN' : 'en-US'
  const timeStr = d.toLocaleTimeString(loc, { hour: '2-digit', minute: '2-digit' })

  if (diffMin < 1) return t('portal.notifications_time_just_now')
  if (diffMin < 60) return t('portal.notifications_time_minutes_ago', { n: diffMin })
  if (diffHr < 24 && startOfDay(d).getTime() === startOfDay(now).getTime()) {
    return t('portal.notifications_time_hours_ago', { n: diffHr })
  }

  const yesterday = startOfDay(now)
  yesterday.setDate(yesterday.getDate() - 1)
  if (startOfDay(d).getTime() === yesterday.getTime()) {
    return t('portal.notifications_time_yesterday', { time: timeStr })
  }

  return d.toLocaleDateString(loc, { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function rowLinkComponent(n) {
  return n.data?.dispatch_request_id ? RouterLink : 'div'
}

function rowLinkBind(n) {
  const id = n.data?.dispatch_request_id
  if (!id) return {}
  return {
    to: { name: 'portalRequestDetail', params: { id: String(id) } },
    'data-testid': `portal-notification-link-${id}`,
  }
}

async function onRowClick(n) {
  if (!n.read) {
    try {
      await markPortalNotificationRead(n.id)
      items.value = items.value.map((x) => (x.id === n.id ? { ...x, read: true } : x))
      unreadTotal.value = Math.max(0, unreadTotal.value - 1)
    } catch {
      // navigation still proceeds for linked rows
    }
  }
}

function listParams(page) {
  return {
    per_page: 25,
    page,
    filter: activeTab.value === 'unread' ? 'unread' : undefined,
  }
}

async function loadFirst() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchPortalNotifications(listParams(1))
    items.value = data.items ?? []
    pagination.value = data.meta ?? null
    unreadTotal.value = data.meta?.unread_total ?? 0
  } catch (e) {
    error.value = formatApiError(e, t('portal.notifications_load_fail'))
    items.value = []
    pagination.value = null
  } finally {
    loading.value = false
  }
}

async function loadMore() {
  if (!pagination.value || loadingMore.value) return
  const next = (pagination.value.current_page ?? 1) + 1
  if (next > (pagination.value.last_page ?? 1)) return
  loadingMore.value = true
  try {
    const data = await fetchPortalNotifications(listParams(next))
    items.value = [...items.value, ...(data.items ?? [])]
    pagination.value = data.meta ?? pagination.value
    unreadTotal.value = data.meta?.unread_total ?? unreadTotal.value
  } catch (e) {
    error.value = formatApiError(e, t('portal.notifications_load_fail'))
  } finally {
    loadingMore.value = false
  }
}

function setTab(tab) {
  if (activeTab.value === tab) return
  activeTab.value = tab
}

async function markAll() {
  markingAll.value = true
  try {
    await markAllPortalNotificationsRead()
    items.value = items.value.map((x) => ({ ...x, read: true }))
    unreadTotal.value = 0
  } catch (e) {
    error.value = formatApiError(e, t('portal.notifications_mark_fail'))
  } finally {
    markingAll.value = false
  }
}

onBeforeUnmount(() => clearTimeout(searchDebounce))

loadFirst()
</script>
