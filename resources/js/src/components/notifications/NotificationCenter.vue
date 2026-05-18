<template>
  <Teleport to="body">
    <!-- Panel: bottom-sheet mobile · floating card sm+; justify theo trục sidebar -->
    <div
      v-if="notifStore.panelOpen"
      class="fixed inset-0 z-[200] flex flex-col justify-end sm:flex-row sm:items-start sm:p-3 md:p-4 print:hidden"
      :class="panelJustifyClass"
    >
      <div
        class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px] transition-opacity active:bg-slate-900/55"
        @click="notifStore.closePanel()"
      />
      <div
        class="relative z-10 flex max-h-[min(88dvh,42rem)] w-full flex-col overflow-hidden rounded-t-[1.25rem] border border-slate-200/90 bg-white shadow-[0_-12px_48px_-12px_rgba(15,23,42,0.22)] dark:border-slate-700/90 dark:bg-slate-900 dark:shadow-[0_-12px_48px_-12px_rgba(0,0,0,0.45)] sm:max-h-[min(32rem,85vh)] sm:max-w-md sm:rounded-2xl sm:shadow-2xl"
        :style="{
          paddingTop: 'max(0.75rem, env(safe-area-inset-top))',
          paddingBottom: 'max(0.5rem, env(safe-area-inset-bottom))',
        }"
        role="dialog"
        aria-modal="true"
        :aria-label="t('notify.title')"
      >
        <!-- Grab bar (mobile affordance) -->
        <div
          class="mx-auto mb-1 h-1 w-10 shrink-0 rounded-full bg-slate-300/80 dark:bg-slate-600 sm:hidden"
          aria-hidden="true"
        />

        <div class="flex items-center gap-3 border-b border-slate-100 px-4 pb-3 pt-1 dark:border-slate-800 sm:pt-0">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-950/80 dark:text-sky-300"
          >
            <BellIcon class="h-5 w-5" aria-hidden="true" />
          </div>
          <div class="min-w-0 flex-1">
            <h2 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">
              {{ t('notify.title') }}
            </h2>
            <p v-if="notifStore.lastUnread > 0" class="text-xs font-medium text-sky-600 dark:text-sky-400">
              {{ t('notify.unread_line', { n: notifStore.lastUnread > 99 ? '99+' : notifStore.lastUnread }) }}
            </p>
          </div>
          <button
            type="button"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition active:scale-95 active:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:active:bg-slate-700"
            :title="t('notify.close')"
            :aria-label="t('notify.close')"
            @click="notifStore.closePanel()"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <div
          v-if="isDriverApp"
          class="border-b border-slate-100 px-4 py-4 dark:border-slate-800"
        >
          <!-- All set: permission granted + push subscribed -->
          <div
            v-if="notificationPermission === 'granted' && notifStore.pushState === 'subscribed'"
            class="flex items-center gap-3 rounded-2xl bg-emerald-50 px-4 py-3 dark:bg-emerald-950/30"
          >
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 shrink-0 text-emerald-600 dark:text-emerald-400" aria-hidden="true">
              <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
            </svg>
            <p class="text-base font-semibold text-emerald-800 dark:text-emerald-200">
              {{ t('notify.driver_status_on') }}
            </p>
          </div>

          <!-- Notifications blocked by user -->
          <div
            v-else-if="notificationPermission === 'denied'"
            class="rounded-2xl bg-amber-50 px-4 py-3 dark:bg-amber-950/30"
          >
            <p class="text-base font-semibold text-amber-900 dark:text-amber-100">
              {{ t('notify.driver_denied_title') }}
            </p>
            <p class="mt-1 text-sm leading-relaxed text-amber-700 dark:text-amber-300">
              {{ t('notify.driver_denied_body') }}
            </p>
          </div>

          <!-- Setup flow: chưa granted hoặc dev -->
          <div v-else class="flex flex-col gap-2.5">
            <!-- Step 1: Request browser permission (or combined flow if prod) -->
            <button
              v-if="notificationPermission !== 'granted'"
              type="button"
              :disabled="pushLoading"
              class="flex min-h-[52px] w-full items-center justify-center gap-2 rounded-2xl bg-sky-600 text-base font-bold text-white shadow-sm active:bg-sky-700 disabled:opacity-60 dark:bg-sky-600 dark:active:bg-sky-500"
              @click="onEnableAll"
            >
              <span
                v-if="pushLoading"
                class="h-5 w-5 animate-spin rounded-full border-2 border-white border-t-transparent"
                aria-hidden="true"
              />
              <span v-else>{{ t('notify.driver_enable_btn') }}</span>
            </button>

            <!-- Permission granted, non-prod: show done state -->
            <div
              v-if="notificationPermission === 'granted' && !isProd"
              class="flex items-center gap-3 rounded-2xl bg-emerald-50 px-4 py-3 dark:bg-emerald-950/30"
            >
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 shrink-0 text-emerald-600 dark:text-emerald-400" aria-hidden="true">
                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
              </svg>
              <p class="text-base font-semibold text-emerald-800 dark:text-emerald-200">
                {{ t('notify.driver_step_done') }}
              </p>
            </div>
          </div>
        </div>

        <div
          v-if="!isDriverApp"
          class="flex flex-col gap-2 border-b border-slate-100 bg-slate-50/95 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/40"
        >
          <label
            class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-200/80 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 shadow-sm dark:border-slate-600 dark:bg-slate-900/60 dark:text-slate-200"
          >
            <span>{{ t('notify.sound') }}</span>
            <input
              v-model="notifStore.soundEnabled"
              type="checkbox"
              class="h-5 w-5 rounded-md border-slate-300 text-sky-600 focus:ring-sky-500/40 dark:border-slate-500 dark:bg-slate-800"
            />
          </label>
          <div class="flex flex-col gap-1.5 sm:flex-row sm:flex-wrap sm:items-center sm:gap-2">
            <button
              type="button"
              class="rounded-lg px-2 py-1.5 text-left text-sm font-semibold text-sky-600 active:bg-sky-50 dark:text-sky-400 dark:active:bg-sky-950/50"
              @click="enableDesktopNotify"
            >
              {{ t('notify.desktop_perm') }}
            </button>
            <span class="hidden text-slate-300 dark:text-slate-600 sm:inline" aria-hidden="true">·</span>
            <button
              v-if="isProd"
              type="button"
              class="rounded-lg px-2 py-1.5 text-left text-sm font-semibold text-sky-600 active:bg-sky-50 dark:text-sky-400 dark:active:bg-sky-950/50"
              @click="notifStore.registerWebPush"
            >
              {{ t('notify.push_reg') }}
            </button>
          </div>
        </div>

        <div
          v-if="notifStore.loading"
          class="min-h-0 flex-1 overflow-y-auto px-3 py-3"
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
            class="flex flex-1 flex-col items-center justify-center gap-3 px-6 py-14 text-center"
          >
            <div
              class="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500"
            >
              <BellIcon class="h-8 w-8" aria-hidden="true" />
            </div>
            <p class="text-lg font-semibold text-slate-700 dark:text-slate-200">
              {{ t('notify.empty') }}
            </p>
            <p class="text-sm text-slate-500 dark:text-slate-400">
              {{ t('notify.empty_hint') }}
            </p>
          </div>
          <ul
            v-else
            class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 py-3"
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
        <div
          class="shrink-0 border-t border-slate-100 bg-white/95 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/95"
        >
          <div class="flex items-center justify-between gap-2">
            <RouterLink
              :to="hubPath"
              class="rounded-lg px-2 py-1.5 text-sm font-semibold text-sky-600 transition hover:bg-sky-50 active:bg-sky-100 dark:text-sky-400 dark:hover:bg-sky-950/40"
              @click="notifStore.closePanel()"
            >
              {{ t('notify.view_all') }}
            </RouterLink>
            <button
              v-if="!notifStore.loading && notifStore.items.length"
              type="button"
              class="rounded-lg px-2 py-1.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 active:bg-slate-200 dark:text-slate-300 dark:hover:bg-slate-800"
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
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { useNotificationStore } from '../../store/notificationCenter'
import { useAuthStore } from '../../store'
import { useSidebarLayout } from '../../composables/useSidebarLayout'

const { t, locale } = useI18n()
const auth = useAuthStore()
const notifStore = useNotificationStore()
const router = useRouter()
const route = useRoute()
const isDriverApp = computed(() => !!route.meta?.driverApp)
const isProd = import.meta.env.PROD
const hubPath = computed(() =>
  isDriverApp.value ? '/driver' : buildStaffPrefixedPath('/notifications'),
)
const { isVertical } = useSidebarLayout()
const panelJustifyClass = computed(() =>
  isVertical.value ? 'md:justify-start' : 'sm:justify-end',
)

const notificationPermission = ref('default')
const pushLoading = ref(false)
const attemptedDriverPushThisOpen = ref(false)

function syncNotifPerm() {
  notificationPermission.value = typeof Notification !== 'undefined'
    ? Notification.permission
    : 'denied'
}

function showPushResult(result) {
  if (result?.ok) {
    showAppSuccess(t('notify.push_ok'))
  } else if (result?.reason === 'denied') {
    showAppError(t('notify.push_err_denied'))
  } else if (result?.reason === 'network') {
    showAppError(t('notify.push_err_network'))
  } else if (result && !result.ok && result.reason !== 'unsupported' && result.reason !== 'no_vapid') {
    showAppError(t('notify.push_err_api'))
  }
}

async function onEnableAll() {
  pushLoading.value = true
  try {
    await notifStore.requestBrowserNotificationPermission()
    syncNotifPerm()
    if (typeof Notification !== 'undefined' && Notification.permission === 'granted' && isProd) {
      const result = await notifStore.registerWebPush()
      showPushResult(result)
    }
  } finally {
    pushLoading.value = false
    syncNotifPerm()
  }
}

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
  const text = message || body || title
  if (text && !looksTechnical(text)) {
    const sub = resolveNavLink(n) ? t('notify.trip_new_sub') : ''
    return { primary: clipText(text, 100), sub }
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

function enableDesktopNotify() {
  void notifStore.requestBrowserNotificationPermission()
}

onMounted(() => {
  syncNotifPerm()
  if (auth.isLoggedIn) {
    notifStore.startPolling()
    void notifStore.refreshBadges()
  }
})

onUnmounted(() => {
  notifStore.stopPolling()
})

watch(
  () => auth.isLoggedIn,
  (v) => {
    if (v) {
      notifStore.startPolling()
      void notifStore.refreshBadges()
    } else {
      notifStore.stopPolling()
    }
  },
)

watch(
  () => [notifStore.panelOpen, route.path],
  () => {
    if (notifStore.panelOpen && isDriverApp.value) {
      syncNotifPerm()
    }
  },
)

watch(
  () => notifStore.panelOpen,
  async (open) => {
    if (!open) {
      attemptedDriverPushThisOpen.value = false
      return
    }
    await nextTick()
    syncNotifPerm()
    if (!isDriverApp.value || !isProd) return
    if (typeof Notification === 'undefined' || Notification.permission !== 'granted') return
    if (notifStore.pushState === 'subscribed') return
    if (attemptedDriverPushThisOpen.value) return
    attemptedDriverPushThisOpen.value = true
    try {
      // Chỉ đăng ký nền: không mở AppMessageModal (tránh chặn UX khi user mở panel để đọc thông báo;
      // lỗi mạng/timeouts push hay bị nhầm là “bấm thông báo bị lỗi”).
      await notifStore.registerWebPush()
    } finally {
      syncNotifPerm()
    }
  },
)
</script>
