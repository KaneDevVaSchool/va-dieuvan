<template>
  <div class="mx-auto max-w-6xl space-y-5 pb-10">

    <!-- ── Page header ────────────────────────────────────────────────────── -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-xl font-bold text-slate-900 dark:text-slate-50">{{ t('audit_logs_page.page_main_title') }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          Ghi nhận mọi thao tác, truy cập và thay đổi dữ liệu trong hệ thống.
        </p>
      </div>
      <button
        type="button"
        class="inline-flex items-center gap-2 self-start rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 sm:self-auto"
        :disabled="loading"
        @click="reload(true)"
      >
        <ArrowPathIcon class="h-4 w-4" :class="loading ? 'animate-spin' : ''" aria-hidden="true" />
        Làm mới
      </button>
    </div>

    <!-- ── Stats strip ─────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Tổng bản ghi</p>
        <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900 dark:text-slate-50">
          <span v-if="loading && !meta.total" class="inline-block h-7 w-16 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
          <template v-else>{{ (meta.total ?? 0).toLocaleString('vi-VN') }}</template>
        </p>
      </div>
      <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Loại sự kiện</p>
        <p class="mt-1.5 truncate text-sm font-semibold text-slate-700 dark:text-slate-200">{{ eventSummaryLabel }}</p>
      </div>
      <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Khoảng ngày</p>
        <p class="mt-1.5 truncate text-sm font-semibold text-slate-700 dark:text-slate-200">{{ dateRangeChip }}</p>
      </div>
      <div class="rounded-2xl border border-slate-200/80 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Trang</p>
        <p class="mt-1.5 text-sm font-semibold tabular-nums text-slate-700 dark:text-slate-200">
          {{ meta.current_page ?? 1 }} / {{ meta.last_page ?? 1 }}
        </p>
      </div>
    </div>

    <!-- ── Filter panel ────────────────────────────────────────────────────── -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">

      <!-- Event type pills -->
      <div class="mb-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Loại sự kiện</p>
        <div class="flex flex-wrap gap-1.5">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition"
            :class="
              !filters.event
                ? 'bg-teal-600 text-white shadow-sm'
                : 'border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
            "
            @click="filters.event = ''"
          >
            Tất cả
          </button>
          <button
            v-for="e in auditEventPresets"
            :key="e.value"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-medium transition"
            :class="
              filters.event === e.value
                ? 'text-white shadow-sm ' + eventActivePillClass(e.value)
                : 'border border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700'
            "
            @click="filters.event = e.value"
          >
            <component :is="eventIcon(e.value)" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
            {{ e.value }}
          </button>
        </div>
      </div>

      <!-- Date, actor, search, per page -->
      <div class="flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
        <label class="flex flex-col gap-1">
          <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Từ ngày</span>
          <input
            v-model="filters.from"
            type="date"
            class="h-9 rounded-xl border border-slate-200 bg-white px-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
          />
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Đến ngày</span>
          <input
            v-model="filters.to"
            type="date"
            class="h-9 rounded-xl border border-slate-200 bg-white px-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
          />
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Mã người thực hiện</span>
          <input
            v-model="actorDraft"
            type="text"
            inputmode="numeric"
            placeholder="VD: 12"
            class="h-9 w-28 rounded-xl border border-slate-200 bg-white px-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
          />
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Tìm trong trang</span>
          <div class="relative">
            <MagnifyingGlassIcon class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
            <input
              v-model="inPageSearchInput"
              type="search"
              placeholder="Sự kiện, người dùng…"
              class="h-9 w-44 rounded-xl border border-slate-200 bg-white pl-8 pr-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
            />
          </div>
        </label>
        <label class="flex flex-col gap-1">
          <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Dòng / trang</span>
          <select
            v-model.number="filters.per_page"
            class="h-9 rounded-xl border border-slate-200 bg-white px-2.5 text-sm text-slate-900 shadow-sm focus:border-teal-400 focus:outline-none focus:ring-2 focus:ring-teal-500/25 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
            :disabled="loading"
            @change="onPerPageChange"
          >
            <option :value="25">25</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
            <option :value="200">200</option>
          </select>
        </label>
        <button
          type="button"
          class="ml-auto inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 px-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-800"
          @click="resetFilters"
        >
          <XMarkIcon class="h-4 w-4" aria-hidden="true" />
          Xóa bộ lọc
        </button>
      </div>
    </div>

    <!-- ── Loading skeletons ──────────────────────────────────────────────── -->
    <div v-if="loading" class="overflow-hidden rounded-2xl border border-slate-200/80 dark:border-slate-700">
      <div class="border-b border-slate-200/80 bg-slate-50/80 px-4 py-3 dark:border-slate-700 dark:bg-slate-800/60">
        <div class="h-4 w-32 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
      </div>
      <div class="divide-y divide-slate-100 dark:divide-slate-800">
        <div v-for="i in 8" :key="i" class="flex items-center gap-4 px-4 py-3.5">
          <div class="h-6 w-28 animate-pulse rounded-full bg-slate-200 dark:bg-slate-700" />
          <div class="h-4 w-24 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
          <div class="h-4 w-20 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
          <div class="ml-auto h-4 w-40 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
        </div>
      </div>
    </div>

    <template v-else>
      <!-- ── Empty state ──────────────────────────────────────────────────── -->
      <div
        v-if="!displayItems.length"
        class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 py-16 text-center dark:border-slate-700 dark:bg-slate-900/30"
      >
        <DocumentMagnifyingGlassIcon class="mb-4 h-14 w-14 text-slate-300 dark:text-slate-600" />
        <p class="text-base font-semibold text-slate-700 dark:text-slate-300">Không có dữ liệu</p>
        <p class="mt-1 max-w-xs text-sm text-slate-500 dark:text-slate-400">{{ t('audit_logs_page.empty') }}</p>
      </div>

      <!-- ── Table ────────────────────────────────────────────────────────── -->
      <div v-else class="overflow-hidden rounded-2xl border border-slate-200/80 shadow-sm dark:border-slate-700">

        <!-- Desktop table -->
        <div class="hidden overflow-x-auto md:block">
          <table class="w-full min-w-[52rem] border-collapse text-left text-sm">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50/90 dark:border-slate-700 dark:bg-slate-800/80">
                <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Sự kiện</th>
                <th class="whitespace-nowrap px-3 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Thời gian</th>
                <th class="whitespace-nowrap px-3 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Người thực hiện</th>
                <th class="whitespace-nowrap px-3 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Đối tượng</th>
                <th class="min-w-[14rem] px-3 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Chi tiết</th>
                <th class="w-8 px-3 py-3" />
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr
                v-for="l in displayItems"
                :key="l.id"
                class="group cursor-pointer transition-colors hover:bg-teal-50/50 dark:hover:bg-teal-950/20"
                @click="goDetail(l)"
              >
                <!-- Event badge -->
                <td class="px-4 py-3.5 align-middle">
                  <span
                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                    :class="eventBadgeClass(l.event)"
                  >
                    <component :is="eventIcon(l.event)" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                    {{ l.event }}
                  </span>
                </td>

                <!-- Time + ID -->
                <td class="whitespace-nowrap px-3 py-3.5 align-middle">
                  <div class="text-xs text-slate-700 dark:text-slate-300">{{ formatDate(l.created_at) }}</div>
                  <div class="mt-0.5 text-[11px] tabular-nums text-slate-400 dark:text-slate-500">#{{ l.id }}</div>
                </td>

                <!-- Actor -->
                <td class="px-3 py-3.5 align-middle">
                  <div v-if="l.actor" class="flex items-center gap-2">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                      {{ actorInitials(l.actor.name) }}
                    </span>
                    <div class="min-w-0">
                      <p class="max-w-[7rem] truncate text-xs font-medium text-slate-800 dark:text-slate-200">{{ l.actor.name }}</p>
                      <p v-if="l.actor.employee_code" class="text-[11px] text-slate-400">{{ l.actor.employee_code }}</p>
                    </div>
                  </div>
                  <span v-else class="text-xs text-slate-400">{{ l.actor_id ?? '—' }}</span>
                </td>

                <!-- Subject -->
                <td class="max-w-[10rem] px-3 py-3.5 align-middle">
                  <p v-if="l.auditable_type" class="truncate text-xs font-medium text-slate-700 dark:text-slate-300">
                    {{ auditableLabel(l.auditable_type) }}
                  </p>
                  <p v-if="l.auditable_id" class="mt-0.5 text-[11px] tabular-nums text-slate-400">#{{ l.auditable_id }}</p>
                  <span v-if="!l.auditable_type && !l.auditable_id" class="text-xs text-slate-400">—</span>
                </td>

                <!-- Details (metadata) -->
                <td class="px-3 py-3.5 align-middle">
                  <div v-if="l.metadata" class="flex flex-wrap items-center gap-1.5">
                    <span
                      v-if="l.metadata.method"
                      class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                      :class="httpMethodClass(l.metadata.method)"
                    >{{ l.metadata.method }}</span>
                    <span class="max-w-[12rem] truncate text-xs text-slate-500 dark:text-slate-400">{{ l.metadata.path }}</span>
                    <span
                      v-if="l.metadata.status"
                      class="rounded px-1.5 py-0.5 text-[10px] font-semibold tabular-nums"
                      :class="httpStatusClass(l.metadata.status)"
                    >{{ l.metadata.status }}</span>
                    <span v-if="l.metadata.duration_ms != null" class="text-[11px] text-slate-400">{{ l.metadata.duration_ms }}ms</span>
                  </div>
                  <span v-else class="text-xs text-slate-400">—</span>
                </td>

                <!-- Chevron -->
                <td class="px-3 py-3.5 align-middle">
                  <ChevronRightIcon class="h-4 w-4 text-slate-300 transition group-hover:text-slate-500 dark:text-slate-600 dark:group-hover:text-slate-400" aria-hidden="true" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile cards -->
        <div class="divide-y divide-slate-100 dark:divide-slate-800 md:hidden">
          <div
            v-for="l in displayItems"
            :key="'m' + l.id"
            class="cursor-pointer p-4 transition-colors hover:bg-teal-50/40 dark:hover:bg-teal-950/20"
            @click="goDetail(l)"
          >
            <div class="flex items-start justify-between gap-2">
              <span
                class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold"
                :class="eventBadgeClass(l.event)"
              >
                <component :is="eventIcon(l.event)" class="h-3 w-3 shrink-0" aria-hidden="true" />
                {{ l.event }}
              </span>
              <ChevronRightIcon class="h-4 w-4 shrink-0 text-slate-300 dark:text-slate-600" aria-hidden="true" />
            </div>
            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ formatDate(l.created_at) }} · #{{ l.id }}</p>
            <dl class="mt-2 space-y-1.5 text-xs">
              <div class="flex gap-2">
                <dt class="shrink-0 text-slate-500">Người TH</dt>
                <dd class="min-w-0 font-medium text-slate-800 dark:text-slate-200">{{ l.actor?.name ?? l.actor_id ?? '—' }}</dd>
              </div>
              <div v-if="l.auditable_type" class="flex gap-2">
                <dt class="shrink-0 text-slate-500">Đối tượng</dt>
                <dd class="min-w-0 text-slate-600 dark:text-slate-400">{{ auditableLabel(l.auditable_type) }}#{{ l.auditable_id ?? '' }}</dd>
              </div>
              <div v-if="l.metadata" class="flex flex-wrap items-center gap-1">
                <span
                  v-if="l.metadata.method"
                  class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase"
                  :class="httpMethodClass(l.metadata.method)"
                >{{ l.metadata.method }}</span>
                <span class="max-w-[12rem] truncate text-slate-500 dark:text-slate-400">{{ l.metadata.path }}</span>
                <span
                  v-if="l.metadata.status"
                  class="rounded px-1 py-0.5 text-[10px] font-semibold tabular-nums"
                  :class="httpStatusClass(l.metadata.status)"
                >{{ l.metadata.status }}</span>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- ── Pagination ────────────────────────────────────────────────────── -->
      <div class="flex flex-col items-start justify-between gap-3 sm:flex-row sm:items-center">
        <p class="text-xs text-slate-500 dark:text-slate-400">
          Tổng <span class="font-semibold text-slate-700 dark:text-slate-200">{{ (meta.total ?? 0).toLocaleString('vi-VN') }}</span> bản ghi
        </p>
        <div class="flex items-center gap-1.5">
          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
            :disabled="loading || (meta.current_page ?? 1) <= 1"
            aria-label="Trang đầu"
            @click="goPage(1)"
          >
            <ChevronDoubleLeftIcon class="h-3.5 w-3.5" aria-hidden="true" />
          </button>
          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
            :disabled="loading || (meta.current_page ?? 1) <= 1"
            aria-label="Trang trước"
            @click="goPage((meta.current_page ?? 1) - 1)"
          >
            <ChevronLeftIcon class="h-3.5 w-3.5" aria-hidden="true" />
          </button>
          <span class="min-w-[5.5rem] text-center text-xs font-medium text-slate-700 dark:text-slate-300">
            Trang {{ meta.current_page ?? 1 }} / {{ meta.last_page ?? 1 }}
          </span>
          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
            :disabled="loading || (meta.current_page ?? 1) >= (meta.last_page ?? 1)"
            aria-label="Trang sau"
            @click="goPage((meta.current_page ?? 1) + 1)"
          >
            <ChevronRightIcon class="h-3.5 w-3.5" aria-hidden="true" />
          </button>
          <button
            type="button"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800"
            :disabled="loading || (meta.current_page ?? 1) >= (meta.last_page ?? 1)"
            aria-label="Trang cuối"
            @click="goPage(meta.last_page ?? 1)"
          >
            <ChevronDoubleRightIcon class="h-3.5 w-3.5" aria-hidden="true" />
          </button>
        </div>
      </div>
    </template>

  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import {
  ArrowPathIcon,
  ChevronRightIcon,
  ChevronLeftIcon,
  ChevronDoubleLeftIcon,
  ChevronDoubleRightIcon,
  DocumentMagnifyingGlassIcon,
  GlobeAltIcon,
  DocumentPlusIcon,
  InboxArrowDownIcon,
  XCircleIcon,
  CheckCircleIcon,
  ArrowUpTrayIcon,
  EyeIcon,
  ExclamationTriangleIcon,
  BoltIcon,
  MagnifyingGlassIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { listAuditLogs } from '../../api/audit'
import { AUDIT_EVENT_PRESETS } from '../../config/systemSeedOptions'
import { showAppError, showAppSuccess } from '../../composables/appMessage'
import { formatApiError } from '../../api/http'
import { debounceTrailing } from '../../composables/useDebounce'

const { t, locale } = useI18n()
const router = useRouter()

// ─── State ────────────────────────────────────────────────────────────────────

const loading   = ref(false)
const listReady = ref(false)
const items     = ref([])
const meta      = ref({})

const auditEventPresets = AUDIT_EVENT_PRESETS

const actorDraft       = ref('')
const inPageSearchInput = ref('')
const inPageQ          = ref('')

const filters = reactive({
  event:    'api.request',
  actor_id: '',
  from:     '',
  to:       '',
  page:     1,
  per_page: 50,
})

// ─── Event badge / icon config ────────────────────────────────────────────────

const EVENT_CONFIG = {
  'api.request':            { icon: GlobeAltIcon,           badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',           active: 'bg-blue-600' },
  'request.create':         { icon: DocumentPlusIcon,        badge: 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',       active: 'bg-green-600' },
  'request.paper_received': { icon: InboxArrowDownIcon,      badge: 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300',           active: 'bg-teal-600' },
  'request.reject':         { icon: XCircleIcon,             badge: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',           active: 'bg-rose-600' },
  'request.approve':        { icon: CheckCircleIcon,         badge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300', active: 'bg-emerald-600' },
  'attachment.upload':      { icon: ArrowUpTrayIcon,         badge: 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',   active: 'bg-purple-600' },
  'attachment.ocr_stub':    { icon: EyeIcon,                 badge: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',   active: 'bg-indigo-600' },
  'cargo.sla_breached':     { icon: ExclamationTriangleIcon, badge: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',       active: 'bg-amber-600' },
}

function eventIcon(event) {
  return EVENT_CONFIG[event]?.icon ?? BoltIcon
}

function eventBadgeClass(event) {
  return EVENT_CONFIG[event]?.badge ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
}

function eventActivePillClass(event) {
  return EVENT_CONFIG[event]?.active ?? 'bg-teal-600'
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

function httpStatusClass(status) {
  const n = Number(status)
  if (n >= 200 && n < 300) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
  if (n >= 300 && n < 400) return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
  if (n >= 400 && n < 500) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
  if (n >= 500)             return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
  return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
}

// ─── Computed ─────────────────────────────────────────────────────────────────

const eventSummaryLabel = computed(() => {
  if (!filters.event) return t('audit_logs_page.event_all')
  const row = auditEventPresets.find((e) => e.value === filters.event)
  return row?.label ?? filters.event
})

const dateRangeChip = computed(() => {
  if (!filters.from && !filters.to) return t('audit_logs_page.date_any')
  const a = filters.from || '…'
  const b = filters.to || '…'
  return `${a} — ${b}`
})

const bumpInPageDebounced = debounceTrailing(() => {
  inPageQ.value = inPageSearchInput.value
}, 300)

watch(inPageSearchInput, () => bumpInPageDebounced())

function metaMatchesQuery(l, q) {
  const blob = [
    String(l.event ?? ''),
    String(l.actor?.name ?? ''),
    String(l.actor_id ?? ''),
    String(l.auditable_type ?? ''),
    String(l.auditable_id ?? ''),
    JSON.stringify(l.metadata ?? ''),
  ].join(' ').toLowerCase()
  return blob.includes(q)
}

const displayItems = computed(() => {
  const q = inPageQ.value.trim().toLowerCase()
  if (!q) return items.value
  return items.value.filter((l) => metaMatchesQuery(l, q))
})

// ─── UI helpers ───────────────────────────────────────────────────────────────

function actorInitials(name) {
  if (!name) return '?'
  return name.split(' ').slice(-2).map((n) => n[0] ?? '').join('').toUpperCase().slice(0, 2)
}

function auditableLabel(type) {
  if (!type) return '—'
  return type.split('\\').pop()
}

function formatDate(v) {
  if (!v) return '-'
  try {
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return new Date(v).toLocaleString(loc)
  } catch {
    return String(v)
  }
}

// ─── Actions ──────────────────────────────────────────────────────────────────

const scheduleReload = debounceTrailing(() => {
  if (!listReady.value) return
  filters.page = 1
  reload(false)
}, 400)

const bumpActorDebounced = debounceTrailing(() => {
  const next = actorDraft.value.trim()
  if (next === filters.actor_id) return
  filters.actor_id = next
  scheduleReload()
}, 400)

watch(actorDraft, () => bumpActorDebounced())
watch(() => [filters.event, filters.from, filters.to], () => scheduleReload(), { deep: true })

function onPerPageChange() {
  filters.page = 1
  reload(false)
}

function resetFilters() {
  filters.event    = 'api.request'
  filters.actor_id = ''
  filters.from     = ''
  filters.to       = ''
  filters.per_page = 50
  filters.page     = 1
  actorDraft.value       = ''
  inPageSearchInput.value = ''
  inPageQ.value          = ''
  if (listReady.value) reload(false)
}

function goDetail(log) {
  router.push({ name: 'systemAuditDetail', params: { id: log.id } })
}

async function reload(notify = false) {
  loading.value = true
  try {
    const params = { ...filters }
    Object.keys(params).forEach((k) => { if (params[k] === '') delete params[k] })
    const res = await listAuditLogs(params)
    items.value = res.items ?? []
    meta.value  = res.meta ?? {}
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

onMounted(async () => {
  actorDraft.value = filters.actor_id ? String(filters.actor_id) : ''
  await reload(false)
  listReady.value = true
})
</script>
