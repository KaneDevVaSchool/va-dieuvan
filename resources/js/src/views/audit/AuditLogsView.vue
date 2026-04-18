<template>
  <div class="space-y-4 sm:space-y-6">
    <Card :title="t('audit_logs_page.filter_card_title')">
      <p class="mb-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
        {{ t('audit_logs_page.filter_intro') }}
      </p>
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <Select
          v-model="filters.event"
          :label="t('audit_logs_page.event_label')"
          :hint="t('audit_logs_page.event_hint')"
          placeholder=""
        >
          <option value="">{{ t('audit_logs_page.event_all') }}</option>
          <option v-for="e in auditEventPresets" :key="e.value" :value="e.value">{{ e.label }}</option>
        </Select>
        <Input
          v-model="filters.actor_id"
          :label="t('audit_logs_page.actor_label')"
          type="number"
          :placeholder="t('audit_logs_page.actor_ph')"
          :hint="t('audit_logs_page.actor_hint')"
        />
        <Input
          v-model="filters.from"
          :label="t('audit_logs_page.from_label')"
          type="date"
          placeholder=""
          :hint="t('audit_logs_page.from_hint')"
        />
        <Input
          v-model="filters.to"
          :label="t('audit_logs_page.to_label')"
          type="date"
          placeholder=""
          :hint="t('audit_logs_page.to_hint')"
        />
      </div>
      <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">
          {{ t('audit_logs_page.hint_footer') }}
        </p>
        <Button variant="secondary" class="w-full shrink-0 sm:w-auto" :loading="loading" @click="reload(true)">
          {{ t('audit_logs_page.apply') }}
        </Button>
      </div>
    </Card>

    <Card :title="t('audit_logs_page.list_title')">
      <div v-if="loading" class="text-sm text-slate-500">{{ t('audit_logs_page.loading') }}</div>
      <div v-else>
        <div v-if="!items.length" class="text-sm text-slate-500">
          {{ t('audit_logs_page.empty') }}
        </div>

        <div v-else class="space-y-2">
          <div
            v-for="l in items"
            :key="l.id"
            class="rounded-lg border border-slate-200/90 bg-white p-3 dark:border-slate-700 dark:bg-slate-900/80"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                #{{ l.id }} · {{ l.event }}
              </div>
              <div class="text-xs text-slate-500">{{ formatDate(l.created_at) }}</div>
            </div>
            <div class="mt-2 grid gap-2 text-xs text-slate-600 dark:text-slate-400 md:grid-cols-2">
              <div>
                <span class="text-slate-500">{{ t('audit_logs_page.actor') }}</span>
                <span class="ml-1">{{ l.actor?.name ?? l.actor_id ?? '—' }}</span>
              </div>
              <div class="truncate">
                <span class="text-slate-500">{{ t('audit_logs_page.subject') }}</span>
                <span class="ml-1">{{ l.auditable_type ?? '—' }}#{{ l.auditable_id ?? '' }}</span>
              </div>
              <div class="md:col-span-2">
                <span class="text-slate-500">{{ t('audit_logs_page.details') }}</span>
                <span class="ml-1">{{ previewMeta(l.metadata) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 text-sm dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
          <div class="text-slate-500">{{ t('audit_logs_page.total', { n: meta.total ?? 0 }) }}</div>
          <div class="flex flex-wrap items-center gap-2">
            <Button variant="secondary" :disabled="(meta.current_page ?? 1) <= 1" @click="goPage((meta.current_page ?? 1) - 1)">
              {{ t('audit_logs_page.prev') }}
            </Button>
            <div class="text-xs text-slate-500">
              {{ t('audit_logs_page.page_of', { cur: meta.current_page ?? 1, last: meta.last_page ?? 1 }) }}
            </div>
            <Button
              variant="secondary"
              :disabled="(meta.current_page ?? 1) >= (meta.last_page ?? 1)"
              @click="goPage((meta.current_page ?? 1) + 1)"
            >
              {{ t('audit_logs_page.next') }}
            </Button>
          </div>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import { listAuditLogs } from '../../api/audit'
import { AUDIT_EVENT_PRESETS } from '../../config/systemSeedOptions'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { formatApiError } from '../../api/http'

const { t, locale } = useI18n()

const loading = ref(false)
const items = ref([])
const meta = ref({})
const auditEventPresets = AUDIT_EVENT_PRESETS

const filters = reactive({
  event: 'api.request',
  actor_id: '',
  from: '',
  to: '',
  page: 1,
  per_page: 50,
})

function formatDate(v) {
  if (!v) return '-'
  try {
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return new Date(v).toLocaleString(loc)
  } catch {
    return String(v)
  }
}

function previewMeta(m) {
  if (!m) return '-'
  const parts = []
  if (m.method) parts.push(`${m.method} ${m.path ?? ''}`.trim())
  if (m.status) parts.push(t('audit_logs_page.meta_status', { status: m.status }))
  if (m.duration_ms != null) parts.push(`${m.duration_ms} ms`)
  return parts.join(' · ') || JSON.stringify(m)
}

async function reload(notify = false) {
  loading.value = true
  try {
    const params = { ...filters }
    Object.keys(params).forEach((k) => (params[k] === '' ? delete params[k] : null))
    const res = await listAuditLogs(params)
    items.value = res.items ?? []
    meta.value = res.meta ?? {}
    if (notify) showAppSuccess(t('audit_logs_page.reload_success'), t('audit_logs_page.reload_success_title'))
  } catch (e) {
    showAppError(formatApiError(e))
  } finally {
    loading.value = false
  }
}

function goPage(p) {
  filters.page = p
  reload(false)
}

onMounted(() => reload(false))
</script>
