<template>
  <div>
    <section class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:py-10">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <RouterLink
            :to="{ name: 'portalHome' }"
            class="text-xs font-semibold text-va-800 underline-offset-2 hover:underline"
          >
            ← {{ t('portal.back_dashboard') }}
          </RouterLink>
          <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-900">{{ t('portal.notifications_page_title') }}</h1>
          <p class="mt-2 text-sm text-slate-600">{{ t('portal.notifications_page_lead') }}</p>
        </div>
        <button
          type="button"
          class="min-h-[44px] rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50 disabled:opacity-50"
          :disabled="markingAll || loading"
          @click="markAll"
        >
          {{ t('portal.notifications_mark_all') }}
        </button>
      </div>

      <p v-if="error" class="mt-6 text-sm text-rose-600">{{ error }}</p>

      <div v-else-if="loading" class="mt-8 space-y-3">
        <div v-for="i in 6" :key="i" class="animate-pulse rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
          <div class="h-4 w-2/3 rounded bg-slate-200" />
          <div class="mt-3 h-3 w-full rounded bg-slate-100" />
        </div>
      </div>

      <p v-else-if="!items.length" class="mt-10 text-center text-sm text-slate-500">{{ t('portal.notifications_empty') }}</p>

      <ul v-else class="mt-8 divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <li v-for="n in items" :key="n.id" class="px-4 py-4 sm:px-5">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold text-slate-900">{{ n.data?.title ?? n.type }}</p>
              <p class="mt-1 text-sm text-slate-600">{{ n.data?.body ?? '' }}</p>
              <p class="mt-2 text-xs text-slate-400">{{ fmtTime(n.created_at) }}</p>
              <RouterLink
                v-if="n.data?.dispatch_request_id"
                class="mt-3 inline-block text-xs font-semibold text-va-800 underline underline-offset-2"
                :to="{ name: 'portalRequestDetail', params: { id: String(n.data.dispatch_request_id) } }"
              >
                {{ t('portal.notifications_open_request', { id: n.data.dispatch_request_id }) }}
              </RouterLink>
            </div>
            <div class="flex shrink-0 flex-col items-end gap-2">
              <span
                v-if="!n.read"
                class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-900"
              >
                {{ t('portal.notifications_badge_new') }}
              </span>
              <button
                v-if="!n.read"
                type="button"
                class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-50 disabled:opacity-50"
                :disabled="markingId === n.id"
                @click="markOne(n.id)"
              >
                {{ t('portal.notifications_mark_read') }}
              </button>
            </div>
          </div>
        </li>
      </ul>

      <button
        v-if="pagination && pagination.current_page < pagination.last_page"
        type="button"
        class="mt-6 flex w-full min-h-[48px] items-center justify-center rounded-2xl border border-slate-200 bg-white text-sm font-semibold text-slate-800 shadow hover:bg-slate-50 disabled:opacity-50"
        :disabled="loadingMore"
        @click="loadMore"
      >
        {{ loadingMore ? t('portal.loading_more') : t('portal.load_more') }}
      </button>
    </section>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
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
const markingAll = ref(false)
const markingId = ref(null)

function fmtTime(raw) {
  if (!raw) return ''
  try {
    const d = new Date(raw)
    if (Number.isNaN(d.getTime())) return ''
    const opts = { dateStyle: 'medium', timeStyle: 'short' }
    return new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-US', opts).format(d)
  } catch {
    return ''
  }
}

async function loadFirst() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchPortalNotifications({ per_page: 25, page: 1 })
    items.value = data.items ?? []
    pagination.value = data.meta ?? null
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
    const data = await fetchPortalNotifications({ per_page: 25, page: next })
    items.value = [...items.value, ...(data.items ?? [])]
    pagination.value = data.meta ?? pagination.value
  } catch (e) {
    error.value = formatApiError(e, t('portal.notifications_load_fail'))
  } finally {
    loadingMore.value = false
  }
}

async function markOne(id) {
  markingId.value = id
  try {
    await markPortalNotificationRead(id)
    items.value = items.value.map((x) => (x.id === id ? { ...x, read: true } : x))
  } catch (e) {
    error.value = formatApiError(e, t('portal.notifications_mark_fail'))
  } finally {
    markingId.value = null
  }
}

async function markAll() {
  markingAll.value = true
  try {
    await markAllPortalNotificationsRead()
    items.value = items.value.map((x) => ({ ...x, read: true }))
  } catch (e) {
    error.value = formatApiError(e, t('portal.notifications_mark_fail'))
  } finally {
    markingAll.value = false
  }
}

onMounted(() => loadFirst())
</script>
