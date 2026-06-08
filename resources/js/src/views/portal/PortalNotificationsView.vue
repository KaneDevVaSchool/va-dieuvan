<template>
  <div>
    <section class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:py-10">
      <!-- Page header -->
      <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
          {{ t('portal.notifications_page_title_v2') }}
        </h1>
        <p class="mt-2 text-sm text-slate-600">{{ t('portal.notifications_page_lead_v2') }}</p>
      </div>

      <!-- Toolbar: tabs + search + mark all -->
      <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <div
          class="inline-flex shrink-0 rounded-xl border border-slate-200 bg-slate-100/80 p-1"
          role="tablist"
        >
          <button
            type="button"
            role="tab"
            :aria-selected="activeTab === 'all'"
            class="rounded-lg px-4 py-2 text-sm font-semibold transition"
            :class="
              activeTab === 'all'
                ? 'bg-white text-va-800 shadow-sm'
                : 'text-slate-600 hover:text-slate-900'
            "
            @click="setTab('all')"
          >
            {{ t('portal.notifications_tab_all') }}
          </button>
          <button
            type="button"
            role="tab"
            :aria-selected="activeTab === 'unread'"
            class="relative rounded-lg px-4 py-2 text-sm font-semibold transition"
            :class="
              activeTab === 'unread'
                ? 'bg-white text-va-800 shadow-sm'
                : 'text-slate-600 hover:text-slate-900'
            "
            @click="setTab('unread')"
          >
            {{ t('portal.notifications_tab_unread') }}
            <span
              v-if="unreadTotal > 0"
              class="ml-1.5 inline-flex min-h-[18px] min-w-[18px] items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold leading-none text-white"
            >
              {{ unreadTotal > 99 ? '99+' : unreadTotal }}
            </span>
          </button>
        </div>

        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2 sm:justify-end">
          <label class="relative min-w-0 flex-1 sm:max-w-xs">
            <span class="sr-only">{{ t('portal.notifications_search_placeholder') }}</span>
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
              aria-hidden="true"
            />
            <input
              v-model="searchInput"
              type="search"
              autocomplete="off"
              :placeholder="t('portal.notifications_search_placeholder')"
              class="h-10 w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-va-300 focus:outline-none focus:ring-2 focus:ring-va-700/20"
            />
          </label>
          <button
            type="button"
            class="inline-flex min-h-[40px] shrink-0 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-va-200 hover:bg-va-50 disabled:opacity-50"
            :disabled="markingAll || loading || unreadTotal === 0"
            @click="markAll"
          >
            <CheckIcon class="h-4 w-4 text-va-800" aria-hidden="true" />
            <span class="hidden sm:inline">{{ t('portal.notifications_mark_all') }}</span>
          </button>
        </div>
      </div>

      <p v-if="error" class="mt-6 text-sm text-rose-600">{{ error }}</p>

      <div v-else-if="loading" class="mt-8 space-y-3">
        <div v-for="i in 5" :key="i" class="animate-pulse rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
          <div class="flex gap-3">
            <div class="h-10 w-10 shrink-0 rounded-full bg-slate-200" />
            <div class="min-w-0 flex-1 space-y-2">
              <div class="h-4 w-2/3 rounded bg-slate-200" />
              <div class="h-3 w-full rounded bg-slate-100" />
            </div>
          </div>
        </div>
      </div>

      <p v-else-if="!filteredItems.length" class="mt-10 text-center text-sm text-slate-500">
        {{ t('portal.notifications_empty') }}
      </p>

      <div v-else class="mt-8 space-y-8">
        <template v-for="group in groupedSections" :key="group.key">
          <section v-if="group.items.length">
            <h2 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">
              {{ group.label }}
            </h2>
            <ul class="space-y-2">
              <li
                v-for="n in group.items"
                :key="n.id"
                class="rounded-2xl border bg-white shadow-sm transition"
                :class="
                  n.read
                    ? 'border-slate-100'
                    : 'border-va-100/80 bg-gradient-to-r from-va-50/40 to-white'
                "
              >
                <component
                  :is="rowLinkComponent(n)"
                  v-bind="rowLinkBind(n)"
                  class="flex gap-3 px-4 py-4 sm:px-5"
                  @click="onRowClick(n)"
                >
                  <span
                    class="mt-2 h-2 w-2 shrink-0 rounded-full"
                    :class="n.read ? 'bg-transparent' : 'bg-va-700'"
                    aria-hidden="true"
                  />
                  <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                    :class="visualFor(n).iconBg"
                    aria-hidden="true"
                  >
                    <component :is="visualFor(n).icon" class="h-5 w-5" :class="visualFor(n).iconColor" />
                  </span>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                      <p class="text-sm font-semibold text-slate-900">
                        {{ n.data?.title ?? n.type }}
                      </p>
                      <time class="shrink-0 text-xs text-slate-400" :datetime="n.created_at">
                        {{ formatRelativeTime(n.created_at) }}
                      </time>
                    </div>
                    <p v-if="n.data?.body" class="mt-1 text-sm leading-snug text-slate-600">
                      {{ n.data.body }}
                    </p>
                    <span
                      v-if="visualFor(n).badgeLabel"
                      class="mt-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                      :class="visualFor(n).badgeClass"
                    >
                      <component
                        :is="visualFor(n).badgeIcon"
                        v-if="visualFor(n).badgeIcon"
                        class="h-3 w-3"
                        aria-hidden="true"
                      />
                      {{ visualFor(n).badgeLabel }}
                    </span>
                  </div>
                </component>
              </li>
            </ul>
          </section>
        </template>
      </div>

      <button
        v-if="pagination && pagination.current_page < pagination.last_page"
        type="button"
        class="mt-8 flex w-full min-h-[48px] items-center justify-center rounded-2xl border border-slate-200 bg-white text-sm font-semibold text-slate-800 shadow-sm transition hover:border-va-200 hover:bg-va-50 disabled:opacity-50"
        :disabled="loadingMore"
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
  CheckIcon,
  ClockIcon,
  MagnifyingGlassIcon,
  PaperAirplaneIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline'
import {
  fetchPortalNotifications,
  markAllPortalNotificationsRead,
  markPortalNotificationRead,
} from '../../api/notifications'
import { formatApiError } from '../../api/http'

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
  }, 300)
})

const filteredItems = computed(() => {
  let list = items.value
  const q = debouncedSearch.value
  if (q) {
    list = list.filter((n) => {
      const id = String(n.data?.dispatch_request_id ?? '')
      return id.includes(q) || (n.data?.title ?? '').toLowerCase().includes(q.toLowerCase())
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
      iconBg: 'bg-amber-100',
      iconColor: 'text-amber-700',
      badgeLabel: t('portal.notifications_badge_returned'),
      badgeClass: 'bg-amber-50 text-amber-800 ring-1 ring-amber-200/60',
      badgeIcon: ArrowUturnLeftIcon,
    }
  }
  if (type.includes('complet') || status === 'completed') {
    return {
      icon: TruckIcon,
      iconBg: 'bg-emerald-100',
      iconColor: 'text-emerald-700',
      badgeLabel: t('portal.notifications_badge_dispatched_done'),
      badgeClass: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200/60',
      badgeIcon: CheckCircleIcon,
    }
  }
  if (type.includes('dispatch') || status === 'approved') {
    return {
      icon: TruckIcon,
      iconBg: 'bg-sky-100',
      iconColor: 'text-sky-700',
      badgeLabel: t('portal.notifications_badge_dispatched'),
      badgeClass: 'bg-sky-50 text-sky-800 ring-1 ring-sky-200/60',
      badgeIcon: ArrowPathIcon,
    }
  }
  if (type.includes('approv')) {
    return {
      icon: CheckCircleIcon,
      iconBg: 'bg-emerald-100',
      iconColor: 'text-emerald-700',
      badgeLabel: t('portal.notifications_badge_approved'),
      badgeClass: 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200/60',
      badgeIcon: CheckCircleIcon,
    }
  }
  if (type.includes('submit') || type.includes('creat') || type.includes('new')) {
    return {
      icon: PaperAirplaneIcon,
      iconBg: 'bg-slate-100',
      iconColor: 'text-slate-600',
      badgeLabel: t('portal.notifications_badge_pending'),
      badgeClass: 'bg-slate-100 text-slate-700 ring-1 ring-slate-200/60',
      badgeIcon: ClockIcon,
    }
  }
  return {
    icon: BellIcon,
    iconBg: 'bg-slate-100',
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
  return { to: { name: 'portalRequestDetail', params: { id: String(id) } } }
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
  loadFirst()
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
