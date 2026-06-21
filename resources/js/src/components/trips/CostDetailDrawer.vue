<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      leave-active-class="transition-opacity duration-150"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-[55] bg-slate-900/40 backdrop-blur-[2px]"
        @click="emit('close')"
      />
    </Transition>
    <Transition
      enter-active-class="transition-transform duration-200 ease-out"
      enter-from-class="translate-x-full"
      leave-active-class="transition-transform duration-150 ease-in"
      leave-to-class="translate-x-full"
    >
      <aside
        v-if="open"
        class="fixed inset-y-0 right-0 z-[56] flex w-full max-w-[480px] flex-col bg-white shadow-2xl dark:bg-slate-950"
        role="dialog"
        aria-modal="true"
        :aria-label="t('cost_center.drawer_aria')"
      >
        <!-- Header -->
        <header
          class="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-800"
        >
          <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
              {{ t('cost_center.drawer_title') }}
            </p>
            <p class="mt-0.5 font-mono text-sm font-bold text-slate-900 dark:text-white">
              {{ costCode }}
            </p>
          </div>
          <button
            type="button"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800"
            :aria-label="t('cost_center.drawer_close')"
            @click="emit('close')"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </header>

        <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
          <!-- Amount + status -->
          <div
            class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-slate-50 to-white p-4 dark:border-slate-800 dark:from-slate-900 dark:to-slate-950"
          >
            <div class="flex items-center justify-between gap-2">
              <span
                class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide"
                :class="statusClass(row?.status)"
              >
                {{ statusLabel(row?.status) }}
              </span>
              <span
                class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300"
              >
                {{ groupLabel }}
              </span>
            </div>
            <p class="mt-3 text-2xl font-bold tabular-nums text-slate-900 dark:text-white">
              {{ fmtMoney(row?.amount) }}
            </p>
            <p v-if="row?.description" class="mt-1.5 whitespace-pre-wrap text-sm text-slate-600 dark:text-slate-300">
              {{ row.description }}
            </p>
          </div>

          <!-- Meta grid -->
          <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3">
            <div>
              <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                {{ t('cost_center.col_creator') }}
              </dt>
              <dd class="mt-0.5 truncate text-sm text-slate-800 dark:text-slate-200">
                {{ row?.creator?.name || t('cost_center.empty') }}
              </dd>
            </div>
            <div>
              <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                {{ t('cost_center.col_created') }}
              </dt>
              <dd class="mt-0.5 text-sm tabular-nums text-slate-800 dark:text-slate-200">
                {{ fmtDateTime(row?.created_at) }}
              </dd>
            </div>
            <div v-if="row?.confirmer?.name">
              <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                {{ t('cost_center.col_approver') }}
              </dt>
              <dd class="mt-0.5 truncate text-sm text-slate-800 dark:text-slate-200">
                {{ row.confirmer.name }}
              </dd>
            </div>
            <div v-if="row?.confirmed_at">
              <dt class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                {{ t('cost_center.col_decided_at') }}
              </dt>
              <dd class="mt-0.5 text-sm tabular-nums text-slate-800 dark:text-slate-200">
                {{ fmtDateTime(row.confirmed_at) }}
              </dd>
            </div>
          </dl>

          <p
            v-if="row?.rejection_reason?.trim()"
            class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-100"
          >
            <span class="font-semibold">{{ t('cost_center.reject_reason') }}:</span>
            {{ row.rejection_reason.trim() }}
          </p>

          <!-- Approval history -->
          <section class="mt-6">
            <h3 class="text-[11px] font-bold uppercase tracking-wide text-slate-500">
              {{ t('cost_center.drawer_history') }}
            </h3>
            <ol class="mt-3 space-y-3">
              <li
                v-for="(ev, i) in historyEvents"
                :key="ev.key"
                class="relative flex gap-3 pb-3 last:pb-0"
              >
                <span
                  v-if="i < historyEvents.length - 1"
                  class="absolute left-[7px] top-5 h-full w-px bg-slate-200 dark:bg-slate-700"
                  aria-hidden="true"
                />
                <span
                  class="relative z-[1] mt-0.5 h-3.5 w-3.5 shrink-0 rounded-full border-2 border-white dark:border-slate-950"
                  :class="ev.dotClass"
                />
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ ev.title }}</p>
                  <p v-if="ev.subtitle" class="text-xs text-slate-500">{{ ev.subtitle }}</p>
                  <p v-if="ev.at" class="text-[11px] tabular-nums text-slate-400">{{ fmtDateTime(ev.at) }}</p>
                </div>
              </li>
            </ol>
          </section>

          <!-- Receipts -->
          <section class="mt-6">
            <h3 class="text-[11px] font-bold uppercase tracking-wide text-slate-500">
              {{ t('cost_center.drawer_receipts') }}
            </h3>
            <div v-if="detailLoading" class="mt-3 text-xs text-slate-400">{{ t('cost_center.loading') }}</div>
            <div v-else-if="!receipts.length" class="mt-3 text-sm italic text-slate-400">
              {{ t('cost_center.receipts_empty') }}
            </div>
            <div v-else class="mt-3 grid grid-cols-3 gap-2">
              <a
                v-for="r in receipts"
                :key="r.key"
                :href="r.url"
                target="_blank"
                rel="noopener noreferrer"
                class="group flex aspect-square items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900"
              >
                <img v-if="r.isImage" :src="r.url" :alt="r.name" loading="lazy" class="h-full w-full object-cover" />
                <DocumentTextIcon v-else class="h-8 w-8 text-slate-400" aria-hidden="true" />
              </a>
            </div>
          </section>
        </div>

        <!-- Action footer -->
        <footer
          v-if="canReconcile && isPending"
          class="flex items-center gap-2 border-t border-slate-200 px-5 py-3 dark:border-slate-800"
        >
          <button
            type="button"
            class="flex-1 rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50"
            :disabled="busy"
            @click="emit('approve', row)"
          >
            {{ t('cost_center.action_approve') }}
          </button>
          <button
            type="button"
            class="flex-1 rounded-lg border border-rose-300 bg-white px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50 disabled:opacity-50 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300"
            :disabled="busy"
            @click="emit('reject', row)"
          >
            {{ t('cost_center.action_reject') }}
          </button>
        </footer>
        <footer
          v-else-if="canReconcile"
          class="flex justify-end border-t border-slate-200 px-5 py-3 dark:border-slate-800"
        >
          <button
            type="button"
            class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-rose-300"
            :disabled="busy"
            @click="emit('delete', row)"
          >
            {{ t('cost_center.action_delete') }}
          </button>
        </footer>
      </aside>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { XMarkIcon, DocumentTextIcon } from '@heroicons/vue/24/outline'
import { getTripCost } from '../../api/costs'
import { costGroupOf, COST_GROUPS, isPendingCost, type TripCostRow } from '../../composables/useTripFinancials'

const props = defineProps<{
  open: boolean
  row: TripCostRow | null
  canReconcile: boolean
  busy?: boolean
}>()

const emit = defineEmits<{
  close: []
  approve: [row: TripCostRow]
  reject: [row: TripCostRow]
  delete: [row: TripCostRow]
}>()

const { t, te, locale } = useI18n()

const detail = ref<Record<string, unknown> | null>(null)
const detailLoading = ref(false)

const isPending = computed(() => (props.row ? isPendingCost(props.row) : false))

const costCode = computed(() => {
  const id = props.row?.id
  return id != null ? `#CP-${String(id).padStart(5, '0')}` : '—'
})

const groupLabel = computed(() => {
  const g = costGroupOf(props.row?.type)
  const key = COST_GROUPS[g].labelKey
  return te(key) ? t(key) : g
})

function fmtMoney(v: unknown) {
  const n = Number(v)
  const c = props.row?.currency || 'VND'
  if (!Number.isFinite(n)) return '—'
  return `${new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'vi-VN').format(n)} ${c}`
}

function fmtDateTime(iso: unknown) {
  if (!iso) return '—'
  const d = new Date(iso as string)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleString(locale.value === 'en' ? 'en-US' : 'vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function statusLabel(status: unknown) {
  const raw = String(status ?? '').trim().toLowerCase()
  if (!raw) return '—'
  const key = `trip_detail.costs.status_${raw}`
  return te(key) ? t(key) : raw
}

function statusClass(status: unknown) {
  const s = String(status ?? '').toLowerCase()
  if (s === 'confirmed' || s === 'approved')
    return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
  if (s === 'rejected') return 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-200'
  if (s === 'paid') return 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-200'
  if (s === 'submitted' || s === 'pending')
    return 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-100'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
}

const historyEvents = computed(() => {
  const r = props.row
  if (!r) return []
  const events: { key: string; title: string; subtitle?: string; at?: string | null; dotClass: string }[] = []
  events.push({
    key: 'created',
    title: t('cost_center.hist_created'),
    subtitle: r.creator?.name || undefined,
    at: r.created_at,
    dotClass: 'bg-slate-400',
  })
  const s = String(r.status ?? '').toLowerCase()
  if (s === 'confirmed' || s === 'approved') {
    events.push({
      key: 'approved',
      title: t('cost_center.hist_approved'),
      subtitle: r.confirmer?.name || undefined,
      at: r.confirmed_at,
      dotClass: 'bg-emerald-500',
    })
  } else if (s === 'rejected') {
    events.push({
      key: 'rejected',
      title: t('cost_center.hist_rejected'),
      subtitle: r.confirmer?.name || undefined,
      at: r.confirmed_at,
      dotClass: 'bg-rose-500',
    })
  } else {
    events.push({
      key: 'pending',
      title: t('cost_center.hist_pending'),
      dotClass: 'bg-amber-400',
    })
  }
  return events
})

function looksLikeImage(name: string, url: string) {
  return /\.(jpe?g|png|gif|webp|bmp|heic|heif)(\?|$)/i.test(`${name} ${url}`.toLowerCase())
}

const receipts = computed(() => {
  const rows: { key: string; url: string; name: string; isImage: boolean }[] = []
  const seen = new Set<string>()
  const atts = (detail.value?.attachments as Array<Record<string, unknown>>) || []
  for (const a of atts) {
    const url = a?.url as string
    if (!url || seen.has(url)) continue
    seen.add(url)
    const name = (a?.original_name as string) || t('cost_center.receipt_file')
    rows.push({ key: `att-${a.id}`, url, name, isImage: looksLikeImage(name, url) })
  }
  const legacy = props.row?.receipt_url
  if (legacy && !seen.has(legacy)) {
    rows.push({
      key: 'legacy',
      url: legacy,
      name: t('cost_center.receipt_file'),
      isImage: looksLikeImage('', legacy),
    })
  }
  return rows
})

async function loadDetail() {
  const id = props.row?.id
  if (!props.open || id == null) {
    detail.value = null
    return
  }
  detailLoading.value = true
  try {
    detail.value = await getTripCost(id)
  } catch {
    detail.value = null
  } finally {
    detailLoading.value = false
  }
}

watch(
  () => [props.open, props.row?.id],
  () => {
    if (props.open) void loadDetail()
    else detail.value = null
  },
  { immediate: true },
)
</script>
