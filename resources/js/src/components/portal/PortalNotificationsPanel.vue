<template>
  <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between gap-2">
      <h2 class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ t('portal.notifications_panel_title') }}</h2>
      <RouterLink
        :to="{ name: 'portalNotifications' }"
        class="text-xs font-semibold text-va-800 underline-offset-2 hover:underline"
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
      <li v-for="n in items" :key="n.id" class="py-3 first:pt-0">
        <component
          :is="linkWrapper(n)"
          v-bind="linkBind(n)"
          class="group block rounded-lg outline-none ring-va-800/25 focus-visible:ring-2"
        >
          <div class="flex items-start gap-2">
            <span
              v-if="!n.read"
              class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-sky-500"
              aria-hidden="true"
            />
            <span v-else class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-transparent" aria-hidden="true" />
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold text-slate-900">{{ n.data?.title ?? n.type }}</p>
              <p class="mt-0.5 line-clamp-2 text-xs leading-snug text-slate-600">{{ n.data?.body ?? '' }}</p>
              <p class="mt-1 text-[11px] text-slate-400">{{ fmtTime(n.created_at) }}</p>
            </div>
          </div>
        </component>
      </li>
    </ul>
  </section>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { fetchPortalNotifications } from '../../api/notifications'
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

async function load(opts = { silent: false }) {
  const silent = !!opts.silent
  silentRefreshing.value = silent
  if (!silent) loading.value = true
  error.value = ''
  try {
    const data = await fetchPortalNotifications({ per_page: 5, page: 1 })
    items.value = data.items ?? []
  } catch (e) {
    error.value = formatApiError(e, t('portal.notifications_load_fail'))
    items.value = []
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
