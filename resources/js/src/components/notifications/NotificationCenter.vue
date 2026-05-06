<template>
  <Teleport to="body">
    <!-- Chuông: floating, mobile + desktop -->
    <div
      v-if="auth.isLoggedIn && !isDriverApp"
      class="pointer-events-none fixed right-0 top-0 z-[100] p-2 pl-6 sm:p-3 print:hidden md:hidden"
      :style="{ paddingTop: 'max(0.5rem, env(safe-area-inset-top))' }"
    >
      <div class="pointer-events-auto flex flex-col items-end gap-2">
        <button
          type="button"
          class="relative flex h-11 w-11 items-center justify-center rounded-full border border-amber-200/80 bg-amber-50/95 text-amber-800 shadow-md backdrop-blur transition hover:bg-amber-100 dark:border-amber-800/60 dark:bg-amber-950/90 dark:text-amber-100"
          :title="t('notify.bell_open')"
          @click="onBellClick"
        >
          <BellIcon class="h-5 w-5" aria-hidden="true" />
          <span
            v-if="notifStore.lastUnread > 0"
            class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-none text-white"
          >
            {{ notifStore.lastUnread > 99 ? '99+' : notifStore.lastUnread }}
          </span>
        </button>
      </div>
    </div>

    <!-- Panel: bottom-sheet mobile · floating card sm+ -->
    <div
      v-if="notifStore.panelOpen"
      class="fixed inset-0 z-[200] flex flex-col justify-end sm:flex-row sm:items-start sm:justify-end sm:p-3 md:p-4 print:hidden md:hidden"
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
          class="flex flex-1 flex-col items-center justify-center gap-3 py-12"
        >
          <span
            class="h-8 w-8 animate-spin rounded-full border-2 border-sky-500 border-t-transparent dark:border-sky-400"
            aria-hidden="true"
          />
          <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
            …
          </p>
        </div>
        <template v-else>
          <div
            v-if="!notifStore.items.length"
            class="flex flex-1 flex-col items-center justify-center gap-3 px-6 py-14 text-center"
          >
            <div
              class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500"
            >
              <BellIcon class="h-7 w-7" aria-hidden="true" />
            </div>
            <p class="max-w-[240px] text-sm font-medium leading-relaxed text-slate-500 dark:text-slate-400">
              {{ t('notify.empty') }}
            </p>
          </div>
          <ul
            v-else
            class="min-h-0 flex-1 space-y-2 overflow-y-auto overscroll-contain px-3 py-3"
          >
            <li
              v-for="n in notifStore.items"
              :key="n.id"
              class="rounded-2xl border px-3.5 py-3 text-sm shadow-sm transition active:scale-[0.99]"
              :class="n.read
                ? 'border-slate-100 bg-slate-50/90 dark:border-slate-800 dark:bg-slate-800/40'
                : 'border-sky-200/90 bg-gradient-to-br from-sky-50 to-white dark:border-sky-900/60 dark:from-sky-950/30 dark:to-slate-900/80'"
            >
              <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ n.type }}
                <span class="font-normal text-slate-400 dark:text-slate-500">·</span>
                {{ formatTime(n.created_at) }}
              </p>
              <p class="mt-1.5 text-[15px] leading-snug text-slate-800 dark:text-slate-100">
                {{ lineText(n) }}
              </p>
              <div class="mt-2.5 flex flex-wrap gap-2">
                <button
                  v-if="actionLink(n)"
                  type="button"
                  class="rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-bold text-white active:bg-sky-700 dark:bg-sky-500 dark:active:bg-sky-600"
                  @click="onOpenNotif(n)"
                >
                  {{ t('notify.open') }}
                </button>
                <button
                  v-else
                  type="button"
                  class="rounded-lg px-3 py-1.5 text-xs font-bold text-sky-600 active:bg-sky-50 dark:text-sky-400 dark:active:bg-sky-950/40"
                  @click="onOpenNotif(n)"
                >
                  {{ t('notify.mark_read') }}
                </button>
              </div>
            </li>
          </ul>
        </template>
        <div
          v-if="!notifStore.loading && notifStore.items.length"
          class="shrink-0 border-t border-slate-100 bg-white/95 px-4 pt-3 dark:border-slate-800 dark:bg-slate-900/95"
        >
          <button
            type="button"
            class="w-full rounded-2xl bg-slate-900 py-3.5 text-base font-bold text-white shadow-md active:scale-[0.98] active:bg-slate-800 dark:bg-white dark:text-slate-900 dark:active:bg-slate-200"
            @click="onReadAll"
          >
            {{ t('notify.mark_all') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { BellIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useNotificationStore } from '../../store/notificationCenter'
import { useAuthStore } from '../../store'

const { t } = useI18n()
const auth = useAuthStore()
const notifStore = useNotificationStore()
const router = useRouter()
const route = useRoute()
const isDriverApp = computed(() => !!route.meta?.driverApp)
const isProd = import.meta.env.PROD

function formatTime(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  return d.toLocaleString('vi-VN', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit' })
}

function lineText(n) {
  const d = n.data
  if (d && typeof d === 'object') {
    if (d.message) return String(d.message)
    if (d.body) return String(d.body)
    if (d.title) return String(d.title)
  }
  return t('notify.fallback')
}

function actionLink(n) {
  const d = n.data
  if (d && typeof d === 'object' && d.url) return d.url
  if (d && typeof d === 'object' && d.action_url) return d.action_url
  return null
}

async function onOpenNotif(n) {
  const link = actionLink(n)
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
</script>
