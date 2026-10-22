<template>
  <Teleport to="body">
    <!-- Anchor the inbox to the bell that opened it. -->
    <div
      v-if="notifStore.panelOpen"
      class="pointer-events-none fixed inset-0 z-[200] print:hidden"
    >
      <div
        class="pointer-events-auto absolute inset-0"
        @click="closeNotificationPanel"
      />
      <span
        v-if="pointerStyle"
        class="pointer-events-none fixed z-20 h-3.5 w-3.5 rotate-45 border-b border-l border-slate-200/90 bg-white dark:border-slate-700/90 dark:bg-slate-900"
        :style="pointerStyle"
        data-testid="notification-panel-pointer"
        aria-hidden="true"
      />
      <div
        id="notification-center-panel"
        ref="panelRef"
        class="pointer-events-auto fixed z-10 flex w-full flex-col overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-[0_16px_48px_-12px_rgba(15,23,42,0.28),0_4px_12px_rgba(15,23,42,0.06)] dark:border-slate-700/90 dark:bg-slate-900 dark:shadow-black/40"
        :style="panelStyle"
        role="dialog"
        :aria-label="t('notify.title')"
      >
        <div class="flex shrink-0 items-center gap-2.5 border-b border-slate-100 px-3.5 py-3 dark:border-slate-800">
          <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-va-50 text-va-800 ring-1 ring-inset ring-va-100 dark:bg-va-950/50 dark:text-va-300 dark:ring-va-900/50" aria-hidden="true">
            <BellIcon class="h-[18px] w-[18px]" />
          </span>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
              <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                {{ t('notify.title') }}
              </h2>
              <span
                v-if="notifStore.lastUnread > 0"
                class="rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-semibold text-sky-700 dark:bg-sky-950/50 dark:text-sky-300"
              >
                {{ t('notify.unread_line', { n: notifStore.lastUnread > 99 ? '99+' : notifStore.lastUnread }) }}
              </span>
            </div>
          </div>
          <button
            type="button"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-100 active:bg-slate-200 dark:text-slate-400 dark:hover:bg-slate-800"
            :aria-label="t('notify.close')"
            @click="closeNotificationPanel"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <div
          v-if="showAudienceTabs"
          class="shrink-0 border-b border-slate-100 px-2.5 py-2 dark:border-slate-800"
          role="tablist"
          :aria-label="t('notify.title')"
        >
          <div class="flex gap-0.5 overflow-x-auto rounded-xl bg-slate-100/80 p-1 [scrollbar-width:none] dark:bg-slate-800/60 [&::-webkit-scrollbar]:hidden">
          <button
            v-for="tab in audienceTabs"
            :key="tab.key"
            type="button"
            role="tab"
            :aria-selected="notifStore.activeTab === tab.key"
            class="flex-1 shrink-0 whitespace-nowrap rounded-lg px-2 py-1.5 text-[11px] font-semibold transition"
            :class="
              notifStore.activeTab === tab.key
                ? 'bg-va-800 text-white shadow-sm dark:bg-va-700'
                : 'text-slate-700 hover:bg-white/70 hover:text-va-800 dark:text-slate-300 dark:hover:bg-slate-700/60'
            "
            @click="notifStore.setTab(tab.key)"
          >
            {{ t(tab.labelKey) }}
          </button>
          </div>
        </div>

        <div
          v-if="notifStore.loading"
          class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-2 py-2"
          aria-busy="true"
          :aria-label="t('notify.skeleton_loading')"
        >
          <div
            v-for="i in 3"
            :key="i"
            class="mb-3 rounded-2xl border border-slate-100 bg-slate-50/90 px-3.5 py-3 dark:border-slate-800 dark:bg-slate-800/40"
          >
            <div class="flex items-start gap-3">
              <div class="mt-0.5 h-8 w-8 shrink-0 animate-pulse rounded-full bg-slate-200 dark:bg-slate-700" />
              <div class="min-w-0 flex-1 space-y-2">
                <div class="h-2.5 w-16 animate-pulse rounded-full bg-slate-200 dark:bg-slate-700" />
                <div class="h-3.5 w-4/5 animate-pulse rounded-full bg-slate-200 dark:bg-slate-700" />
                <div class="h-3 w-3/5 animate-pulse rounded-full bg-slate-200 dark:bg-slate-700" />
              </div>
            </div>
          </div>
        </div>
        <template v-else>
          <div
            v-if="!notifStore.items.length"
            class="flex flex-1 flex-col items-center justify-center gap-1.5 px-4 py-10 text-center"
          >
            <BellIcon class="h-7 w-7 text-slate-300 dark:text-slate-600" aria-hidden="true" />
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300">
              {{ t('notify.empty') }}
            </p>
            <p class="text-xs text-slate-500 dark:text-slate-500">
              {{ t('notify.empty_hint') }}
            </p>
          </div>
          <ul
            v-else
            class="min-h-0 flex-1 overflow-y-auto overscroll-y-contain px-2 py-2"
          >
            <template v-for="section in groupedSections" :key="section.key">
              <li
                class="list-none px-1 pb-2 pt-3 text-[11px] font-bold uppercase tracking-wide text-slate-400 first:pt-0 dark:text-slate-500"
                role="presentation"
              >
                {{ section.label }}
              </li>
              <NotificationItem
                v-for="n in section.items"
                :key="n.id"
                :notification="n"
                :kind="classifyKind(n)"
                :lines="linesFor(n)"
                :type-label="tripTypeLabelFor(n)"
                :relative-time="formatRelativeTime(n.created_at)"
                :has-nav-link="!!resolveNavLink(n)"
                @open="onOpenNotif"
              />
            </template>
          </ul>
        </template>
        <div class="shrink-0 border-t border-slate-100 bg-slate-50/70 px-3.5 py-3 dark:border-slate-800 dark:bg-slate-950/30">
          <div class="flex items-center justify-between gap-2 text-xs">
            <RouterLink
              :to="hubPath"
              class="font-semibold text-sky-600 hover:underline dark:text-sky-400"
              @click="notifStore.closePanel()"
            >
              {{ t('notify.view_all') }}
            </RouterLink>
            <button
              v-if="!notifStore.loading && notifStore.items.length"
              type="button"
              class="font-semibold text-slate-600 hover:underline dark:text-slate-400"
              @click="onReadAll"
            >
              {{ t('notify.mark_all') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { BellIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import NotificationItem from './NotificationItem.vue'
import { buildStaffPrefixedPath } from '../../config/dispatchWebBase'
import { useNotificationStore } from '../../store/notificationCenter'
import { useAuthStore } from '../../store'

const { t, locale } = useI18n()
const auth = useAuthStore()
const notifStore = useNotificationStore()
const router = useRouter()
const route = useRoute()
const isDriverApp = computed(() => !!route.meta?.driverApp)
const hubPath = computed(() =>
  isDriverApp.value ? '/driver' : buildStaffPrefixedPath('/notifications'),
)
const panelRef = ref(null)
const panelStyle = ref({})
const pointerStyle = ref(null)
let panelObserver = null

function positionPanel() {
  if (!notifStore.panelOpen || !panelRef.value) return
  const anchor = notifStore.panelAnchor?.isConnected
    ? notifStore.panelAnchor
    : document.querySelector('[data-testid="notification-bell"]')
  const rect = anchor?.getBoundingClientRect()
  const sidebarRect = anchor?.closest('aside')?.getBoundingClientRect()
  const margin = 8
  const gap = 8
  const rightSpace = sidebarRect ? window.innerWidth - sidebarRect.right - gap - margin : 0
  const besideSidebar = sidebarRect && rightSpace >= 220
  const width = Math.min(384, besideSidebar ? rightSpace : window.innerWidth - margin * 2)
  const below = Math.max(0, window.innerHeight - (rect?.bottom ?? 0) - gap - margin)
  const above = Math.max(0, (rect?.top ?? 0) - gap - margin)
  const openBelow = below >= 280 || below >= above
  const maxHeight = Math.min(448, besideSidebar ? window.innerHeight - margin * 2 : openBelow ? below : above)
  const height = Math.min(panelRef.value.scrollHeight, maxHeight)
  const left = besideSidebar
    ? sidebarRect.right + gap
    : Math.max(margin, Math.min(rect?.left ?? margin, window.innerWidth - width - margin))
  const top = besideSidebar
    ? Math.min(rect.top, window.innerHeight - height - margin) - 24
    : openBelow ? (rect?.bottom ?? 0) + gap : (rect?.top ?? 0) - gap - height
  const panelTop = Math.max(margin, top)
  // Keep the pointer aimed at the bell while avoiding the rounded corners.
  const pointerY = rect ? Math.max(panelTop + 24, Math.min(rect.top + rect.height / 2, panelTop + height - 24)) : 0
  pointerStyle.value = besideSidebar ? { left: `${left - 7}px`, top: `${pointerY - 7}px` } : null
  panelStyle.value = {
    width: `${width}px`,
    maxHeight: `${maxHeight}px`,
    left: `${left}px`,
    top: `${panelTop}px`,
  }
}

function closeNotificationPanel() {
  notifStore.closePanel()
  notifStore.panelAnchor?.focus({ preventScroll: true })
}

function onPanelKeydown(event) {
  if (event.key === 'Escape') {
    event.preventDefault()
    closeNotificationPanel()
  }
}

function stopPanelListeners() {
  panelObserver?.disconnect()
  panelObserver = null
  window.removeEventListener('resize', positionPanel)
  window.removeEventListener('scroll', positionPanel, true)
  document.removeEventListener('keydown', onPanelKeydown)
}

watch(() => notifStore.panelOpen, async (open) => {
  stopPanelListeners()
  if (!open) return
  await nextTick()
  if (!notifStore.panelOpen || !panelRef.value) return
  positionPanel()
  panelObserver = new ResizeObserver(positionPanel)
  panelObserver.observe(panelRef.value)
  window.addEventListener('resize', positionPanel)
  window.addEventListener('scroll', positionPanel, true)
  document.addEventListener('keydown', onPanelKeydown)
  panelRef.value.querySelector('button')?.focus({ preventScroll: true })
})

const audienceTabs = [
  { key: 'all', labelKey: 'notify.tab_all' },
  { key: 'dispatcher', labelKey: 'notify.tab_dispatcher' },
  { key: 'driver', labelKey: 'notify.tab_driver' },
  { key: 'department_head', labelKey: 'notify.tab_dept_head' },
  { key: 'admin', labelKey: 'notify.tab_admin' },
]

const showAudienceTabs = computed(() => {
  const u = auth.user
  if (!u || isDriverApp.value) return false
  if (u.is_superadmin) return true
  return (auth.roleNames ?? []).some((name) => name === 'admin')
})

function startOfLocalDay(d) {
  const x = new Date(d)
  x.setHours(0, 0, 0, 0)
  return x.getTime()
}

const groupedSections = computed(() => {
  const list = notifStore.items || []
  const now = new Date()
  const todayStart = startOfLocalDay(now)
  const y = new Date(now)
  y.setDate(y.getDate() - 1)
  const yesterdayStart = startOfLocalDay(y)
  const today = []
  const yesterday = []
  const earlier = []
  for (const n of list) {
    const ts = new Date(n.created_at).getTime()
    if (Number.isNaN(ts)) {
      continue
    }
    if (ts >= todayStart) {
      today.push(n)
    } else if (ts >= yesterdayStart) {
      yesterday.push(n)
    } else {
      earlier.push(n)
    }
  }
  const out = []
  if (today.length) {
    out.push({ key: 'today', label: t('notify.group_today'), items: today })
  }
  if (yesterday.length) {
    out.push({ key: 'yesterday', label: t('notify.group_yesterday'), items: yesterday })
  }
  if (earlier.length) {
    out.push({ key: 'earlier', label: t('notify.group_earlier'), items: earlier })
  }
  return out
})

function formatRelativeTime(iso) {
  if (!iso) {
    return ''
  }
  const d = new Date(iso)
  const sec = Math.floor((Date.now() - d.getTime()) / 1000)
  if (Number.isNaN(sec) || sec < 0) {
    return ''
  }
  if (sec < 60) {
    return t('notify.rel_just_now')
  }
  if (sec < 3600) {
    return t('notify.rel_minutes_ago', { n: Math.floor(sec / 60) })
  }
  if (sec < 86400) {
    return t('notify.rel_hours_ago', { n: Math.floor(sec / 3600) })
  }
  if (sec < 604800) {
    return t('notify.rel_days_ago', { n: Math.floor(sec / 86400) })
  }
  const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
  return d.toLocaleDateString(loc, { day: 'numeric', month: 'short' })
}

function rawParts(n) {
  const d = n.data && typeof n.data === 'object' ? n.data : {}
  const title = d.title != null ? String(d.title).trim() : ''
  const body = d.body != null ? String(d.body).trim() : ''
  const message = d.message != null ? String(d.message).trim() : ''
  const combined = [message, body, title].filter(Boolean).join(' ').trim()
  return { d, title, body, message, combined, lower: combined.toLowerCase() }
}

function isNoiseOrDebug(combined, lower) {
  if (!combined) {
    return false
  }
  return (
    /dev mode|test notification|mock data|internal server|stack trace/.test(lower)
    || /api\s*error|request failed due to server|error:\s*5\d\d/.test(lower)
    || /fetched successfully|notification fetched|loaded successfully/.test(lower)
  )
}

function looksTechnical(text) {
  return /https?:\/\/|\bapi\b|\bjson\b|status\s*:\s*\d{3}|exception|undefined/.test(
    String(text).toLowerCase(),
  )
}

function classifyKind(n) {
  const { d, combined, lower } = rawParts(n)
  const type = String(n.type || '')
  if (isNoiseOrDebug(combined, lower)) {
    return 'noise'
  }
  if (d.event === 'trip.assigned' || type === 'TripAssignedNotification') {
    return 'trip_assigned'
  }
  if (
    d.event === 'trip.assigned_to_requester'
    || type === 'TripAssignedToRequesterNotification'
  ) {
    return 'assigned_to_requester'
  }
  if (d.event === 'tp.driver.morning_reminder' || type === 'TpDriverMorningReminderNotification') {
    return 'tp_morning'
  }
  if (d.event === 'tp.driver.assignment_changed' || type === 'TpDriverAssignmentNotification') {
    return 'tp_assignment'
  }
  if (d.event === 'dispatch_request.created' || type === 'NewDispatchRequestNotification') {
    return 'new_trip'
  }
  if (
    /new dispatch request|dispatch request|điều xe mới|yêu cầu điều xe/.test(lower)
  ) {
    return 'new_trip'
  }
  if (
    /trip status updated|status updated|trạng thái chuyến|cập nhật trạng thái/.test(lower)
    || (/chuyến/.test(lower) && /cập nhật/.test(lower))
  ) {
    return 'status'
  }
  if (
    /request failed|server error|could not|không thể|lỗi máy chủ|thử lại|timeout|network error/.test(
      lower,
    )
    || /\b5\d\d\b/.test(combined)
  ) {
    return 'error'
  }
  return 'generic'
}

function clipText(s, max) {
  const str = String(s).replace(/\s+/g, ' ').trim()
  if (str.length <= max) {
    return str
  }
  return `${str.slice(0, max - 1)}…`
}

function tripTypeLabelFor(n) {
  if (classifyKind(n) !== 'trip_assigned') {
    return ''
  }
  const d = n.data && typeof n.data === 'object' ? n.data : {}
  const tt = String(d.trip_type || 'unspecified')
  const keys = {
    door_to_door: 'notify.trip_type_door_to_door',
    point_to_point: 'notify.trip_type_point_to_point',
    business: 'notify.trip_type_business',
    cargo: 'notify.trip_type_cargo',
    unspecified: 'notify.trip_type_unspecified',
  }
  return t(keys[tt] || 'notify.trip_type_unspecified')
}

function linesFor(n) {
  const kind = classifyKind(n)
  if (kind === 'noise') {
    return { primary: t('notify.fallback'), sub: '' }
  }
  if (kind === 'trip_assigned') {
    const { d } = rawParts(n)
    const o = d.origin != null ? String(d.origin).trim() : ''
    const dest = d.destination != null ? String(d.destination).trim() : ''
    const routeLine = o && dest ? `${o} → ${dest}` : (o || dest)
    const sub = routeLine || (d.body != null ? String(d.body).trim() : '') || t('notify.trip_assigned_sub')

    return { primary: t('notify.trip_assigned'), sub }
  }
  if (kind === 'tp_morning') {
    const { d } = rawParts(n)
    const sub = d.body != null ? String(d.body).trim() : t('notify.tp_morning_sub')
    return { primary: t('notify.tp_morning_title'), sub }
  }
  if (kind === 'tp_assignment') {
    const { d } = rawParts(n)
    const primary = d.title != null ? String(d.title).trim() : t('notify.fallback')
    const sub = d.body != null ? String(d.body).trim() : ''
    return { primary: clipText(primary, 100), sub: clipText(sub, 120) }
  }
  if (kind === 'assigned_to_requester') {
    const { d } = rawParts(n)
    const primary = d.title != null ? String(d.title).trim() : t('notify.fallback')
    const sub = d.body != null ? String(d.body).trim() : ''
    return { primary: clipText(primary, 100), sub: clipText(sub, 140) }
  }
  if (kind === 'new_trip') {
    return { primary: t('notify.trip_new'), sub: t('notify.trip_new_sub') }
  }
  if (kind === 'status') {
    return { primary: t('notify.trip_status'), sub: t('notify.trip_status_sub') }
  }
  if (kind === 'error') {
    return { primary: t('notify.trip_error'), sub: t('notify.trip_error_sub') }
  }
  const { message, body, title, combined } = rawParts(n)
  const navSub = resolveNavLink(n) ? t('notify.trip_new_sub') : ''
  if (title && !looksTechnical(title)) {
    const sub = body && body !== title ? clipText(body, 120) : navSub
    return { primary: clipText(title, 100), sub }
  }
  const text = message || body || title
  if (text && !looksTechnical(text)) {
    return { primary: clipText(text, 100), sub: navSub }
  }
  return {
    primary: t('notify.fallback'),
    sub: resolveNavLink(n) ? t('notify.trip_new_sub') : '',
  }
}

function resolveNavLink(n) {
  const d = n.data && typeof n.data === 'object' ? n.data : {}
  if (d.url) {
    return String(d.url)
  }
  if (d.action_url) {
    return String(d.action_url)
  }
  const programDayId = d.program_day_id
  if (programDayId != null && programDayId !== '' && isDriverApp.value) {
    return `/driver/tp-days/${String(programDayId)}`
  }
  const tripId = d.trip_id ?? d.tripId
  if (tripId != null && tripId !== '') {
    const id = String(tripId)
    return isDriverApp.value ? `/driver/trips/${id}` : buildStaffPrefixedPath(`/trips/${id}`)
  }
  const drId = d.dispatch_request_id
  if (drId != null && drId !== '') {
    const id = String(drId)
    return isDriverApp.value ? '/driver/trips' : buildStaffPrefixedPath(`/requests/${id}`)
  }
  return null
}

async function onOpenNotif(n) {
  const link = resolveNavLink(n)
  await notifStore.onReadOne(n.id)
  if (link) {
    notifStore.closePanel()
    if (link.startsWith('/')) {
      try {
        await router.push(link)
      } catch {
        window.location.assign(link)
      }
    } else {
      window.location.assign(link)
    }
  }
}

async function onReadAll() {
  await notifStore.onReadAll()
}

function onBellClick() {
  if (notifStore.panelOpen) {
    notifStore.closePanel()
  } else {
    notifStore.openPanel()
  }
  void notifStore.requestBrowserNotificationPermission()
  void notifStore.refreshBadges()
}

onMounted(() => {
  if (auth.isLoggedIn) {
    notifStore.startPolling()
  }
})

onUnmounted(() => {
  stopPanelListeners()
  notifStore.stopPolling()
})

watch(
  () => auth.isLoggedIn,
  (v) => {
    if (v) {
      notifStore.startPolling()
    } else {
      notifStore.stopPolling()
    }
  },
)

</script>
