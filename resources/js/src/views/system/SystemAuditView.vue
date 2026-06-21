<template>
  <div class="min-w-0 space-y-6 pb-10">

    <div>
      <router-link
        :to="{ name: 'auditLogs' }"
        class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-teal-700 dark:text-slate-400 dark:hover:text-teal-400"
        data-testid="audit-detail-back"
      >
        <ArrowLeftIcon class="h-4 w-4" aria-hidden="true" />
        {{ t('audit_logs_page.detail_back') }}
      </router-link>
    </div>

    <div v-if="loading" class="space-y-4">
      <div class="h-10 w-64 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-700" />
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div v-for="i in 4" :key="i" class="h-20 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-700" />
      </div>
      <div class="h-48 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-700" />
    </div>

    <div
      v-else-if="!item"
      class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 py-16 text-center dark:border-slate-700"
    >
      <ExclamationTriangleIcon class="mb-4 h-12 w-12 text-slate-300 dark:text-slate-600" />
      <p class="text-base font-semibold text-slate-700 dark:text-slate-300">{{ t('audit_logs_page.detail_not_found_title') }}</p>
      <p class="mt-1 text-sm text-slate-500">{{ t('audit_logs_page.detail_not_found_body', { id }) }}</p>
    </div>

    <template v-else>
      <div class="flex flex-wrap items-start gap-3">
        <span
          class="inline-flex items-center gap-2 rounded-2xl px-4 py-2 text-sm font-semibold"
          :class="eventBadgeClass(item.event)"
        >
          <component :is="eventIcon(item.event)" class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ eventLabel(item.event) }}
        </span>
        <div>
          <p class="text-lg font-bold text-slate-900 dark:text-slate-50">
            {{ t('audit_logs_page.detail_title', { id: item.id }) }}
          </p>
          <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ formatDate(item.created_at) }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.detail_actor') }}</p>
          <div v-if="item.actor" class="mt-1.5 flex items-center gap-2">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200">
              {{ actorInitialsFromName(item.actor.name) }}
            </span>
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-200">{{ item.actor.name }}</p>
              <p class="truncate text-[11px] text-slate-400">{{ emptyDisplay(item.actor.email) }}</p>
            </div>
          </div>
          <p v-else class="mt-1.5 text-sm font-medium text-slate-700 dark:text-slate-300">
            {{ t('audit_logs_page.feed_actor_system') }}
            <span v-if="item.actor_id" class="text-slate-400"> (#{{ item.actor_id }})</span>
          </p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.detail_ip') }}</p>
          <p class="mt-1.5 font-mono text-sm font-semibold text-slate-700 dark:text-slate-200">{{ emptyDisplay(item.ip_address) }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.detail_module') }}</p>
          <p class="mt-1.5 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ emptyDisplay(item.module) }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.detail_result') }}</p>
          <span
            v-if="item.result"
            class="mt-1.5 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
            :class="item.result === 'success'
              ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
              : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'"
          >
            {{ resultLabel(item.result) }}
          </span>
          <p v-else class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">{{ emptyDisplay(null) }}</p>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200/80 bg-white px-5 py-4 dark:border-slate-700 dark:bg-slate-900">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.detail_subject') }}</p>
        <div class="flex flex-wrap items-center gap-6">
          <div>
            <p class="text-[11px] text-slate-400">{{ t('audit_logs_page.detail_subject_model') }}</p>
            <p class="mt-0.5 font-mono text-sm text-slate-700 dark:text-slate-300">
              {{ item.auditable_type ? subjectTypeLabel(item.auditable_type.split('\\').pop()) : emptyDisplay(null) }}
            </p>
            <p v-if="item.auditable_type" class="mt-0.5 font-mono text-[11px] text-slate-400">{{ item.auditable_type }}</p>
          </div>
          <div>
            <p class="text-[11px] text-slate-400">{{ t('audit_logs_page.detail_subject_id') }}</p>
            <p class="mt-0.5 font-mono text-sm font-semibold text-slate-700 dark:text-slate-300">
              {{ item.auditable_id != null ? `#${item.auditable_id}` : emptyDisplay(null) }}
            </p>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200/80 bg-white px-5 py-4 dark:border-slate-700 dark:bg-slate-900">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.detail_metadata') }}</p>
        <div v-if="metadataEntries.length" class="flex flex-wrap gap-x-6 gap-y-3">
          <div v-for="entry in metadataEntries" :key="entry.key">
            <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ metadataKeyLabel(entry.key) }}</p>
            <div class="mt-0.5 flex items-center gap-1.5">
              <span
                v-if="entry.key === 'method'"
                class="rounded px-1.5 py-0.5 text-[11px] font-bold uppercase tracking-wide"
                :class="httpMethodClass(String(entry.val))"
              >{{ entry.val }}</span>
              <span
                v-else-if="entry.key === 'status'"
                class="rounded px-1.5 py-0.5 text-[11px] font-semibold tabular-nums"
                :class="httpStatusClass(Number(entry.val))"
              >{{ entry.val }}</span>
              <span
                v-else-if="entry.key === 'duration_ms'"
                class="text-sm font-semibold text-slate-700 dark:text-slate-200"
              >{{ entry.val }}<span class="ml-0.5 text-xs font-normal text-slate-400">{{ t('audit_logs_page.detail_ms') }}</span></span>
              <span v-else class="font-mono text-sm text-slate-700 dark:text-slate-200">{{ formatMetaValue(entry.val) }}</span>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.detail_metadata_empty') }}</p>
      </div>

      <div class="rounded-2xl border border-slate-200/80 bg-white px-5 py-4 dark:border-slate-700 dark:bg-slate-900">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.detail_device') }}</p>
        <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
          <div>
            <span class="text-[11px] font-medium text-slate-400">{{ t('audit_logs_page.detail_browser') }}</span>
            <p class="mt-0.5 text-slate-700 dark:text-slate-300">{{ emptyDisplay(item.browser) }}</p>
          </div>
          <div>
            <span class="text-[11px] font-medium text-slate-400">{{ t('audit_logs_page.detail_os') }}</span>
            <p class="mt-0.5 text-slate-700 dark:text-slate-300">{{ emptyDisplay(item.os) }}</p>
          </div>
          <div>
            <span class="text-[11px] font-medium text-slate-400">{{ t('audit_logs_page.detail_device_type') }}</span>
            <p class="mt-0.5 text-slate-700 dark:text-slate-300">{{ emptyDisplay(item.device) }}</p>
          </div>
          <div class="min-w-0 w-full sm:flex-1">
            <span class="text-[11px] font-medium text-slate-400">{{ t('audit_logs_page.detail_user_agent') }}</span>
            <p class="mt-0.5 break-all font-mono text-xs text-slate-500 dark:text-slate-400">{{ emptyDisplay(item.user_agent) }}</p>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200/80 bg-white px-5 py-4 dark:border-slate-700 dark:bg-slate-900">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.detail_diff') }}</p>

        <p v-if="!diff.length" class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">
          {{ t('audit_logs_page.detail_diff_empty') }}
        </p>

        <div v-else class="space-y-2">
          <div
            v-for="row in diff"
            :key="row.key"
            class="overflow-hidden rounded-xl border"
            :class="diffBorderClass(row.type)"
          >
            <div
              class="flex items-center gap-2 border-b px-3.5 py-2"
              :class="diffHeaderClass(row.type)"
            >
              <span class="font-mono text-xs font-semibold text-slate-700 dark:text-slate-200">{{ row.key }}</span>
              <span
                class="ml-auto inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                :class="diffTypeBadgeClass(row.type)"
              >
                <component :is="diffTypeIcon(row.type)" class="h-3 w-3" aria-hidden="true" />
                {{ diffTypeLabel(row.type) }}
              </span>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-slate-800">
              <div v-if="row.type !== 'added' && row.before != null" class="flex gap-2 px-3.5 py-2.5">
                <span class="mt-0.5 shrink-0 text-[10px] font-bold uppercase text-rose-500">{{ t('audit_logs_page.detail_before') }}</span>
                <pre class="min-w-0 flex-1 whitespace-pre-wrap break-all font-mono text-xs leading-relaxed text-rose-700 dark:text-rose-300">{{ fmt(row.before) }}</pre>
              </div>
              <div v-if="row.type !== 'removed' && row.after != null" class="flex gap-2 px-3.5 py-2.5">
                <span class="mt-0.5 shrink-0 text-[10px] font-bold uppercase text-emerald-600">{{ t('audit_logs_page.detail_after') }}</span>
                <pre class="min-w-0 flex-1 whitespace-pre-wrap break-all font-mono text-xs leading-relaxed text-emerald-700 dark:text-emerald-300">{{ fmt(row.after) }}</pre>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowLeftIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  MinusIcon,
  ArrowsRightLeftIcon,
} from '@heroicons/vue/24/outline'
import { getAuditLogDetail } from '../../api/system'
import { useAuditLogPresentation } from '../../composables/useAuditLogPresentation.js'

const route = useRoute()
const { t, locale, te } = useI18n()

const {
  eventLabel,
  eventIcon,
  eventBadgeClass,
  subjectTypeLabel,
  emptyDisplay,
  resultLabel,
} = useAuditLogPresentation()

const id = computed(() => route.params.id)
const loading = ref(true)
const item = ref(null)
const diff = ref([])

const metadataEntries = computed(() => {
  const meta = item.value?.metadata
  if (!meta || typeof meta !== 'object') return []
  return Object.entries(meta)
    .filter(([, val]) => val != null)
    .map(([key, val]) => ({ key, val }))
})

function metadataKeyLabel(key) {
  const i18nKey = `audit_logs_page.metadata_keys.${key}`
  return te(i18nKey) ? t(i18nKey) : key
}

function formatMetaValue(val) {
  if (val == null || val === '') return emptyDisplay(null)
  return typeof val === 'object' ? JSON.stringify(val) : String(val)
}

function httpMethodClass(method) {
  const m = (method ?? '').toUpperCase()
  if (m === 'GET') return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
  if (m === 'POST') return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
  if (m === 'PUT') return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
  if (m === 'PATCH') return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'
  if (m === 'DELETE') return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
}

function httpStatusClass(n) {
  if (n >= 200 && n < 300) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
  if (n >= 300 && n < 400) return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
  if (n >= 400 && n < 500) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
  if (n >= 500) return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
  return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
}

function diffBorderClass(type) {
  if (type === 'added') return 'border-emerald-200 dark:border-emerald-900/50'
  if (type === 'removed') return 'border-rose-200 dark:border-rose-900/50'
  return 'border-amber-200 dark:border-amber-900/50'
}

function diffHeaderClass(type) {
  if (type === 'added') return 'border-emerald-100 bg-emerald-50/60 dark:border-emerald-900/40 dark:bg-emerald-950/30'
  if (type === 'removed') return 'border-rose-100 bg-rose-50/60 dark:border-rose-900/40 dark:bg-rose-950/30'
  return 'border-amber-100 bg-amber-50/60 dark:border-amber-900/40 dark:bg-amber-950/30'
}

function diffTypeBadgeClass(type) {
  if (type === 'added') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
  if (type === 'removed') return 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'
  return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
}

function diffTypeIcon(type) {
  if (type === 'added') return PlusIcon
  if (type === 'removed') return MinusIcon
  return ArrowsRightLeftIcon
}

function diffTypeLabel(type) {
  if (type === 'added') return t('audit_logs_page.diff_added')
  if (type === 'removed') return t('audit_logs_page.diff_removed')
  return t('audit_logs_page.diff_changed')
}

function actorInitialsFromName(name) {
  if (!name) return '?'
  return name.split(' ').slice(-2).map((n) => n[0] ?? '').join('').toUpperCase().slice(0, 2)
}

function formatDate(v) {
  if (!v) return emptyDisplay(null)
  try {
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return new Date(v).toLocaleString(loc)
  } catch {
    return String(v)
  }
}

function fmt(v) {
  if (v == null) return emptyDisplay(null)
  return typeof v === 'object' ? JSON.stringify(v, null, 2) : String(v)
}

async function load() {
  loading.value = true
  item.value = null
  diff.value = []
  try {
    const data = await getAuditLogDetail(id.value)
    item.value = data.item
    diff.value = data.diff || []
  } catch {
    item.value = null
    diff.value = []
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(id, load)
</script>
