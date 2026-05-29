<template>
  <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between gap-2">
      <h2 class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">
        {{ t('portal.notifications_panel_title') }}
        <span
          v-if="unreadTotal > 0"
          class="inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-cyan-600 px-1.5 py-0.5 text-[10px] font-bold tabular-nums normal-case tracking-normal text-white"
        >
          {{ unreadTotal > 99 ? '99+' : unreadTotal }}
        </span>
      </h2>
      <RouterLink
        :to="{ name: 'portalNotifications' }"
        class="text-xs font-semibold text-indigo-600 underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
      >
        {{ t('portal.notifications_view_all') }}
      </RouterLink>
    </div>

    <p v-if="error" class="mt-3 text-xs text-rose-600">{{ error }}</p>

    <div v-else-if="loading && !silentRefreshing" class="mt-4 space-y-3">
      <div v-for="i in 3" :key="i" class="animate-pulse space-y-2 rounded-xl bg-slate-50 px-3 py-3">
        <div class="h-3 w-3/4 rounded bg-slate-200" />
        <div class="h-3 w-full rounded bg-slate-100" />
      </div>
    </div>

    <p v-else-if="!items.length" class="mt-4 text-sm text-slate-500">{{ t('portal.notifications_empty') }}</p>

    <ul v-else class="mt-4 divide-y divide-slate-100">
      <li v-for="n in items" :key="n.id" class="group/item py-3 first:pt-0">
        <div class="flex items-start gap-2">
          <component
            :is="linkWrapper(n)"
            v-bind="linkBind(n)"
            class="min-w-0 flex-1 rounded-lg outline-none ring-indigo-500/25 focus-visible:ring-2 focus-visible:ring-indigo-500"
          >
            <div class="flex items-start gap-2">
              <span
                v-if="!n.read"
                class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-cyan-500 shadow-sm shadow-cyan-500/40"
                aria-hidden="true"
              />
              <span v-else class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-transparent" aria-hidden="true" />
              <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-900">{{ n.data?.title ?? n.type }}</p>
                <p class="mt-0.5 line-clamp-2 text-xs leading-snug text-slate-600">{{ n.data?.body ?? '' }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ fmtTime(n.created_at) }}</p>
              </div>
            </div>
          </component>
          <button
            v-if="!n.read"
            type="button"
            class="shrink-0 rounded-lg px-2 py-1 text-[10px] font-semibold text-indigo-600 opacity-0 transition hover:bg-indigo-50 group-hover/item:opacity-100 focus-visible:opacity-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 max-sm:opacity-100"
            :disabled="markingId === n.id"
            @click.stop="markRead(n)"
          >
            {{ t('portal.notifications_mark_read') }}
          </button>
        </div>
      </li>
    </ul>
  </section>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { fetchPortalNotifications, markPortalNotificationRead } from '../../api/notifications'
import { formatApiError } from '../../api/http'

const props = defineProps({
  /** Parent có thể trigger refresh (polling). */
  refreshTick: { type: Number, default: 0 },
})

const { t, locale } = useI18n()

const loading = ref(true)
const silentRefreshing = ref(false)
const error = ref('')
const items = ref([])
const unreadTotal = ref(0)
const markingId = ref(null)

const linkWrapper = (n) => {
  const id = n.data?.dispatch_request_id
  return id ? RouterLink : 'div'
}

const linkBind = (n) => {
  const id = n.data?.dispatch_request_id
  if (!id) return {}
  return { to: { name: 'portalRequestDetail', params: { id: String(id) } } }
}

function fmtTime(raw) {
  if (!raw) return ''
  try {
    const d = new Date(raw)
    if (Number.isNaN(d.getTime())) return ''
    const opts = { dateStyle: 'short', timeStyle: 'short' }
    return new Intl.DateTimeFormat(locale.value === 'vi' ? 'vi-VN' : 'en-US', opts).format(d)
  } catch {
    return ''
  }
}

async function markRead(n) {
  if (!n?.id || n.read) return
  markingId.value = n.id
  try {
    await markPortalNotificationRead(n.id)
    n.read = true
    unreadTotal.value = Math.max(0, unreadTotal.value - 1)
  } catch (e) {
    error.value = formatApiError(e, t('portal.notifications_mark_fail'))
  } finally {
    markingId.value = null
  }
}

async function load(opts = { silent: false }) {
  const silent = !!opts.silent
  silentRefreshing.value = silent
  if (!silent) loading.value = true
  if (!silent) error.value = ''
  try {
    const data = await fetchPortalNotifications({ per_page: 5, page: 1 })
    items.value = data.items ?? []
    unreadTotal.value = data.meta?.unread_total ?? 0
  } catch (e) {
    error.value = formatApiError(e, t('portal.notifications_load_fail'))
    items.value = []
    unreadTotal.value = 0
  } finally {
    silentRefreshing.value = false
    if (!silent) loading.value = false
  }
}

onMounted(() => load({ silent: false }))

watch(
  () => props.refreshTick,
  (tick, prev) => {
    if (tick !== prev && tick > 0) load({ silent: true })
  },
)

defineExpose({
  reload: load,
})
</script>
