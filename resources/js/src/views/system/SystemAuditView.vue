<template>
  <div class="mx-auto max-w-4xl space-y-5 pb-10">

    <!-- ── Back + header ─────────────────────────────────────────────────── -->
    <div>
      <router-link
        :to="{ name: 'auditLogs' }"
        class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-teal-700 dark:text-slate-400 dark:hover:text-teal-400"
      >
        <ArrowLeftIcon class="h-4 w-4" aria-hidden="true" />
        Quay lại nhật ký
      </router-link>
    </div>

    <!-- ── Loading skeletons ──────────────────────────────────────────────── -->
    <div v-if="loading" class="space-y-4">
      <div class="h-10 w-64 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-700" />
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div v-for="i in 4" :key="i" class="h-20 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-700" />
      </div>
      <div class="h-48 animate-pulse rounded-2xl bg-slate-200 dark:bg-slate-700" />
    </div>

    <!-- ── Error state ─────────────────────────────────────────────────────── -->
    <div
      v-else-if="!item"
      class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 py-16 text-center dark:border-slate-700"
    >
      <ExclamationTriangleIcon class="mb-4 h-12 w-12 text-slate-300 dark:text-slate-600" />
      <p class="text-base font-semibold text-slate-700 dark:text-slate-300">Không tìm thấy bản ghi</p>
      <p class="mt-1 text-sm text-slate-500">Bản ghi #{{ id }} không tồn tại hoặc bạn không có quyền xem.</p>
    </div>

    <template v-else>
      <!-- ── Event header ─────────────────────────────────────────────────── -->
      <div class="flex flex-wrap items-start gap-3">
        <span
          class="inline-flex items-center gap-2 rounded-2xl px-4 py-2 text-sm font-semibold"
          :class="eventBadgeClass(item.event)"
        >
          <component :is="eventIcon(item.event)" class="h-4 w-4 shrink-0" aria-hidden="true" />
          {{ item.event }}
        </span>
        <div>
          <p class="text-lg font-bold text-slate-900 dark:text-slate-50">Chi tiết nhật ký #{{ item.id }}</p>
          <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ formatDate(item.created_at) }}</p>
        </div>
      </div>

      <!-- ── Info cards ───────────────────────────────────────────────────── -->
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Người thực hiện</p>
          <div v-if="item.actor" class="mt-1.5 flex items-center gap-2">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200">
              {{ actorInitials(item.actor.name) }}
            </span>
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-200">{{ item.actor.name }}</p>
              <p v-if="item.actor.email" class="truncate text-[11px] text-slate-400">{{ item.actor.email }}</p>
            </div>
          </div>
          <p v-else class="mt-1.5 text-sm font-medium text-slate-700 dark:text-slate-300">{{ item.actor_id ?? '—' }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400">IP Address</p>
          <p class="mt-1.5 font-mono text-sm font-semibold text-slate-700 dark:text-slate-200">{{ item.ip_address ?? '—' }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Module</p>
          <p class="mt-1.5 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ item.module ?? '—' }}</p>
        </div>

        <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Kết quả</p>
          <span
            v-if="item.result"
            class="mt-1.5 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold"
            :class="item.result === 'success'
              ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
              : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'"
          >
            {{ item.result }}
          </span>
          <p v-else class="mt-1.5 text-sm text-slate-400">—</p>
        </div>
      </div>

      <!-- ── Subject info ─────────────────────────────────────────────────── -->
      <div
        v-if="item.auditable_type || item.auditable_id"
        class="rounded-2xl border border-slate-200/80 bg-white px-5 py-4 dark:border-slate-700 dark:bg-slate-900"
      >
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Đối tượng tác động</p>
        <div class="flex flex-wrap items-center gap-3">
          <div v-if="item.auditable_type">
            <p class="text-[11px] text-slate-400">Model</p>
            <p class="mt-0.5 font-mono text-sm text-slate-700 dark:text-slate-300">{{ item.auditable_type }}</p>
          </div>
          <div v-if="item.auditable_id">
            <p class="text-[11px] text-slate-400">ID</p>
            <p class="mt-0.5 font-mono text-sm font-semibold text-slate-700 dark:text-slate-300">#{{ item.auditable_id }}</p>
          </div>
        </div>
      </div>

      <!-- ── Metadata section ─────────────────────────────────────────────── -->
      <div
        v-if="item.metadata && Object.keys(item.metadata).length"
        class="rounded-2xl border border-slate-200/80 bg-white px-5 py-4 dark:border-slate-700 dark:bg-slate-900"
      >
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Metadata</p>
        <div class="flex flex-wrap gap-x-6 gap-y-3">
          <template v-for="(val, key) in item.metadata" :key="key">
            <div v-if="val != null">
              <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ key }}</p>
              <div class="mt-0.5 flex items-center gap-1.5">
                <span
                  v-if="key === 'method'"
                  class="rounded px-1.5 py-0.5 text-[11px] font-bold uppercase tracking-wide"
                  :class="httpMethodClass(String(val))"
                >{{ val }}</span>
                <span
                  v-else-if="key === 'status'"
                  class="rounded px-1.5 py-0.5 text-[11px] font-semibold tabular-nums"
                  :class="httpStatusClass(Number(val))"
                >{{ val }}</span>
                <span
                  v-else-if="key === 'duration_ms'"
                  class="text-sm font-semibold text-slate-700 dark:text-slate-200"
                >{{ val }}<span class="ml-0.5 text-xs font-normal text-slate-400">ms</span></span>
                <span v-else class="font-mono text-sm text-slate-700 dark:text-slate-200">{{ typeof val === 'object' ? JSON.stringify(val) : val }}</span>
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- ── Device / browser info ────────────────────────────────────────── -->
      <div
        v-if="item.browser || item.os || item.device || item.user_agent"
        class="rounded-2xl border border-slate-200/80 bg-white px-5 py-4 dark:border-slate-700 dark:bg-slate-900"
      >
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Thiết bị & trình duyệt</p>
        <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
          <div v-if="item.browser">
            <span class="text-[11px] font-medium text-slate-400">Browser</span>
            <p class="mt-0.5 text-slate-700 dark:text-slate-300">{{ item.browser }}</p>
          </div>
          <div v-if="item.os">
            <span class="text-[11px] font-medium text-slate-400">OS</span>
            <p class="mt-0.5 text-slate-700 dark:text-slate-300">{{ item.os }}</p>
          </div>
          <div v-if="item.device">
            <span class="text-[11px] font-medium text-slate-400">Device</span>
            <p class="mt-0.5 text-slate-700 dark:text-slate-300">{{ item.device }}</p>
          </div>
          <div v-if="item.user_agent" class="w-full">
            <span class="text-[11px] font-medium text-slate-400">User-Agent</span>
            <p class="mt-0.5 break-all font-mono text-xs text-slate-500 dark:text-slate-400">{{ item.user_agent }}</p>
          </div>
        </div>
      </div>

      <!-- ── Diff section ─────────────────────────────────────────────────── -->
      <div class="rounded-2xl border border-slate-200/80 bg-white px-5 py-4 dark:border-slate-700 dark:bg-slate-900">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Thay đổi dữ liệu (Diff)</p>

        <p v-if="!diff.length" class="py-6 text-center text-sm text-slate-400 dark:text-slate-500">
          Không có thay đổi before / after trên bản ghi này.
        </p>

        <div v-else class="space-y-2">
          <div
            v-for="row in diff"
            :key="row.key"
            class="overflow-hidden rounded-xl border"
            :class="diffBorderClass(row.type)"
          >
            <!-- Diff header -->
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

            <!-- Before / After values -->
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
              <div v-if="row.type !== 'added' && row.before != null" class="flex gap-2 px-3.5 py-2.5">
                <span class="mt-0.5 shrink-0 text-[10px] font-bold uppercase text-rose-500">Before</span>
                <pre class="min-w-0 flex-1 whitespace-pre-wrap break-all font-mono text-xs leading-relaxed text-rose-700 dark:text-rose-300">{{ fmt(row.before) }}</pre>
              </div>
              <div v-if="row.type !== 'removed' && row.after != null" class="flex gap-2 px-3.5 py-2.5">
                <span class="mt-0.5 shrink-0 text-[10px] font-bold uppercase text-emerald-600">After</span>
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
import {
  ArrowLeftIcon,
  ExclamationTriangleIcon,
  GlobeAltIcon,
  DocumentPlusIcon,
  InboxArrowDownIcon,
  XCircleIcon,
  CheckCircleIcon,
  ArrowUpTrayIcon,
  EyeIcon,
  BoltIcon,
  PlusIcon,
  MinusIcon,
  ArrowsRightLeftIcon,
} from '@heroicons/vue/24/outline'
import { getAuditLogDetail } from '../../api/system'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const { locale } = useI18n()

const id      = computed(() => route.params.id)
const loading = ref(true)
const item    = ref(null)
const diff    = ref([])

// ─── Event config (same mapping as list view) ─────────────────────────────────

const EVENT_CONFIG = {
  'api.request':            { icon: GlobeAltIcon,      badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300' },
  'request.create':         { icon: DocumentPlusIcon,  badge: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' },
  'request.paper_received': { icon: InboxArrowDownIcon, badge: 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300' },
  'request.reject':         { icon: XCircleIcon,       badge: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300' },
  'request.approve':        { icon: CheckCircleIcon,   badge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' },
  'attachment.upload':      { icon: ArrowUpTrayIcon,   badge: 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300' },
  'attachment.ocr_stub':    { icon: EyeIcon,           badge: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300' },
  'cargo.sla_breached':     { icon: ExclamationTriangleIcon, badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' },
}

function eventIcon(event) {
  return EVENT_CONFIG[event]?.icon ?? BoltIcon
}

function eventBadgeClass(event) {
  return EVENT_CONFIG[event]?.badge ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
}

// ─── HTTP helpers ─────────────────────────────────────────────────────────────

function httpMethodClass(method) {
  const m = (method ?? '').toUpperCase()
  if (m === 'GET')    return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
  if (m === 'POST')   return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
  if (m === 'PUT')    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
  if (m === 'PATCH')  return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300'
  if (m === 'DELETE') return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
}

function httpStatusClass(n) {
  if (n >= 200 && n < 300) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
  if (n >= 300 && n < 400) return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
  if (n >= 400 && n < 500) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
  if (n >= 500)             return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
  return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
}

// ─── Diff helpers ─────────────────────────────────────────────────────────────

function diffBorderClass(type) {
  if (type === 'added')   return 'border-emerald-200 dark:border-emerald-900/50'
  if (type === 'removed') return 'border-rose-200 dark:border-rose-900/50'
  return 'border-amber-200 dark:border-amber-900/50'
}

function diffHeaderClass(type) {
  if (type === 'added')   return 'border-emerald-100 bg-emerald-50/60 dark:border-emerald-900/40 dark:bg-emerald-950/30'
  if (type === 'removed') return 'border-rose-100 bg-rose-50/60 dark:border-rose-900/40 dark:bg-rose-950/30'
  return 'border-amber-100 bg-amber-50/60 dark:border-amber-900/40 dark:bg-amber-950/30'
}

function diffTypeBadgeClass(type) {
  if (type === 'added')   return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
  if (type === 'removed') return 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'
  return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
}

function diffTypeIcon(type) {
  if (type === 'added')   return PlusIcon
  if (type === 'removed') return MinusIcon
  return ArrowsRightLeftIcon
}

function diffTypeLabel(type) {
  if (type === 'added')   return 'Thêm mới'
  if (type === 'removed') return 'Xóa'
  return 'Thay đổi'
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function actorInitials(name) {
  if (!name) return '?'
  return name.split(' ').slice(-2).map((n) => n[0] ?? '').join('').toUpperCase().slice(0, 2)
}

function formatDate(v) {
  if (!v) return '—'
  try {
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return new Date(v).toLocaleString(loc)
  } catch {
    return String(v)
  }
}

function fmt(v) {
  return typeof v === 'object' && v !== null ? JSON.stringify(v, null, 2) : String(v)
}

// ─── Load ─────────────────────────────────────────────────────────────────────

async function load() {
  loading.value = true
  item.value    = null
  diff.value    = []
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
