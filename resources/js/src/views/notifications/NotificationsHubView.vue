<template>
  <div class="space-y-4">
    <Card :title="t('notifications_hub.card_inbox_title')">
      <div
        v-if="showAudienceTabs"
        class="mb-3 flex flex-wrap gap-1 border-b border-slate-100 pb-3 dark:border-slate-800"
        role="tablist"
      >
        <button
          v-for="tab in audienceTabs"
          :key="tab.key"
          type="button"
          role="tab"
          :aria-selected="notifStore.activeTab === tab.key"
          class="rounded-lg px-3 py-1.5 text-sm font-semibold transition"
          :class="
            notifStore.activeTab === tab.key
              ? 'bg-sky-100 text-sky-800 dark:bg-sky-950/60 dark:text-sky-200'
              : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800'
          "
          @click="setActiveTab(tab.key)"
        >
          {{ t(tab.labelKey) }}
        </button>
      </div>
      <div class="mb-3 flex flex-wrap items-center gap-2">
        <Button variant="secondary" type="button" :loading="loading" @click="load">{{
          t('notifications_hub.refresh')
        }}</Button>
        <Button variant="secondary" type="button" :loading="markingAll" @click="markAll">{{
          t('notifications_hub.mark_all_read')
        }}</Button>
      </div>
      <p v-if="error" class="text-sm text-rose-600">{{ error }}</p>
      <div v-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('notifications_hub.empty') }}</div>
      <ul class="divide-y divide-slate-100">
        <li v-for="n in items" :key="n.id" class="py-3">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
              <div class="text-sm font-medium text-slate-900">{{ n.data?.title ?? n.type }}</div>
              <div class="mt-0.5 text-sm text-slate-600">{{ n.data?.body ?? '' }}</div>
              <div class="mt-1 text-xs text-slate-400">{{ fmt(n.created_at) }}</div>
              <RouterLink
                v-if="n.data?.dispatch_request_id"
                class="mt-2 inline-block text-xs font-medium text-slate-900 underline"
                to="/requests"
              >
                {{ t('notifications_hub.open_request', { id: n.data.dispatch_request_id }) }}
              </RouterLink>
            </div>
            <div class="shrink-0">
              <span
                v-if="!n.read"
                class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-900"
              >
                {{ t('notifications_hub.badge_new') }}
              </span>
              <Button
                v-if="!n.read"
                variant="secondary"
                type="button"
                class="mt-2 text-xs"
                :loading="markingId === n.id"
                @click="markOne(n.id)"
              >
                {{ t('notifications_hub.mark_read') }}
              </Button>
            </div>
          </div>
        </li>
      </ul>
    </Card>

    <Card :title="t('notifications_hub.card_suggestions_title')">
      <ul class="grid gap-2 text-sm md:grid-cols-2">
        <li>
          <RouterLink class="text-slate-900 underline hover:text-slate-600" :to="{ path: '/requests', query: { status: 'pending' } }">
            {{ t('notifications_hub.link_pending_requests') }}
          </RouterLink>
        </li>
        <li>
          <RouterLink class="text-slate-900 underline hover:text-slate-600" to="/trips">{{ t('notifications_hub.link_trips') }}</RouterLink>
        </li>
        <li>
          <RouterLink class="text-slate-900 underline hover:text-slate-600" to="/cargo">{{ t('notifications_hub.link_cargo') }}</RouterLink>
        </li>
        <li>
          <RouterLink class="text-slate-900 underline hover:text-slate-600" to="/costs">{{ t('notifications_hub.link_costs') }}</RouterLink>
        </li>
      </ul>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import { fetchNotificationInbox, markAllNotificationsRead, markNotificationRead } from '../../api/notifications'
import { useAuthStore } from '../../store'
import { useNotificationStore } from '../../store/notificationCenter'

const { t, locale } = useI18n()
const auth = useAuthStore()
const notifStore = useNotificationStore()

const audienceTabs = [
  { key: 'all', labelKey: 'notify.tab_all' },
  { key: 'dispatcher', labelKey: 'notify.tab_dispatcher' },
  { key: 'driver', labelKey: 'notify.tab_driver' },
  { key: 'department_head', labelKey: 'notify.tab_dept_head' },
  { key: 'admin', labelKey: 'notify.tab_admin' },
]

const showAudienceTabs = computed(() => {
  const u = auth.user
  if (!u) return false
  if (u.is_superadmin) return true
  return (auth.roleNames ?? []).some((name) => name === 'admin')
})

function setActiveTab(key) {
  notifStore.setTab(key, { reload: false })
  void load()
}

const loading = ref(false)
const markingAll = ref(false)
const markingId = ref(null)
const error = ref('')
const items = ref([])

function fmt(iso) {
  if (!iso) return ''
  const loc = locale.value === 'en' ? 'en-GB' : 'vi-VN'
  return new Date(iso).toLocaleString(loc)
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const tab = notifStore.activeTab
    const audience = tab && tab !== 'all' ? tab : undefined
    const data = await fetchNotificationInbox({ per_page: 50, audience })
    items.value = data.items ?? []
  } catch (e) {
    error.value = e?.response?.data?.message ?? String(e?.message ?? 'Error')
  } finally {
    loading.value = false
  }
}

async function markAll() {
  markingAll.value = true
  error.value = ''
  try {
    await markAllNotificationsRead()
    await load()
  } catch (e) {
    error.value = e?.response?.data?.message ?? String(e?.message ?? 'Error')
  } finally {
    markingAll.value = false
  }
}

async function markOne(id) {
  markingId.value = id
  error.value = ''
  try {
    await markNotificationRead(id)
    await load()
  } catch (e) {
    error.value = e?.response?.data?.message ?? String(e?.message ?? 'Error')
  } finally {
    markingId.value = null
  }
}

onMounted(load)
</script>
