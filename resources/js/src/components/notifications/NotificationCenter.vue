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

    <!-- Panel -->
    <div
      v-if="notifStore.panelOpen"
      class="fixed inset-0 z-[200] sm:flex sm:items-start sm:justify-end sm:pt-14 sm:pr-3 print:hidden md:hidden"
    >
      <div
        class="absolute inset-0 bg-slate-900/40"
        @click="notifStore.closePanel()"
      />
      <div
        class="absolute right-0 top-0 flex h-full w-full max-w-sm flex-col bg-white shadow-2xl dark:border-l dark:border-slate-800 dark:bg-slate-900 sm:mt-0 sm:h-[min(32rem,85vh)] sm:max-w-md sm:rounded-2xl sm:border sm:border-slate-200/80"
        :style="{ paddingTop: 'max(0.5rem, env(safe-area-inset-top))' }"
      >
        <div class="flex items-center justify-between border-b border-slate-200 px-3 py-2.5 dark:border-slate-700">
          <h2 class="text-base font-bold text-slate-900 dark:text-white">
            {{ t('notify.title') }}
          </h2>
          <button
            type="button"
            class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
            :title="t('notify.close')"
            @click="notifStore.closePanel()"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <div
          class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-3 py-2 text-xs dark:border-slate-800"
        >
          <label class="inline-flex cursor-pointer items-center gap-1.5 text-slate-600 dark:text-slate-300">
            <input
              v-model="notifStore.soundEnabled"
              type="checkbox"
              class="rounded border-slate-300"
            />
            {{ t('notify.sound') }}
          </label>
          <button
            type="button"
            class="font-medium text-sky-600 hover:underline dark:text-sky-400"
            @click="enableDesktopNotify"
          >
            {{ t('notify.desktop_perm') }}
          </button>
          <button
            v-if="isProd"
            type="button"
            class="font-medium text-sky-600 hover:underline dark:text-sky-400"
            @click="notifStore.registerWebPush"
          >
            {{ t('notify.push_reg') }}
          </button>
        </div>

        <div
          v-if="notifStore.loading"
          class="p-4 text-center text-sm text-slate-500"
        >
          …
        </div>
        <template v-else>
          <p
            v-if="!notifStore.items.length"
            class="p-4 text-center text-sm text-slate-500"
          >
            {{ t('notify.empty') }}
          </p>
          <ul
            v-else
            class="min-h-0 flex-1 space-y-1.5 overflow-y-auto overscroll-contain p-2"
          >
            <li
              v-for="n in notifStore.items"
              :key="n.id"
              class="rounded-xl border px-2.5 py-2 text-sm"
              :class="n.read ? 'border-slate-100 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-800/30' : 'border-sky-200/80 bg-sky-50/50 dark:border-sky-900/50 dark:bg-sky-950/20'"
            >
              <p class="text-[10px] font-medium uppercase text-slate-500 dark:text-slate-400">
                {{ n.type }} ·
                {{ formatTime(n.created_at) }}
              </p>
              <p class="mt-0.5 text-slate-800 dark:text-slate-200">
                {{ lineText(n) }}
              </p>
              <div class="mt-1 flex flex-wrap gap-1.5">
                <button
                  v-if="actionLink(n)"
                  type="button"
                  class="text-xs font-semibold text-sky-600 dark:text-sky-400"
                  @click="onOpenNotif(n)"
                >
                  {{ t('notify.open') }}
                </button>
                <button
                  v-else
                  type="button"
                  class="text-xs font-semibold text-sky-600 dark:text-sky-400"
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
          class="shrink-0 border-t border-slate-200 p-2 dark:border-slate-800"
        >
          <button
            type="button"
            class="w-full rounded-xl bg-slate-900 py-2 text-sm font-semibold text-white dark:bg-slate-100 dark:text-slate-900"
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
