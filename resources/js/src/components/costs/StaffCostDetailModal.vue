<template>
  <Modal
    :open="open"
    wide
    :title="modalTitle"
    :description="modalSubtitle"
    @close="emit('close')"
  >
    <div v-if="loading" class="flex items-center justify-center gap-2 py-16 text-sm text-slate-500" data-testid="cost-detail-loading">
      <span
        class="inline-block size-5 animate-spin rounded-full border-2 border-slate-200 border-t-teal-600"
        aria-hidden="true"
      />
      {{ t('costs_page.detail_loading') }}
    </div>

    <p v-else-if="loadError" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-100">
      {{ loadError }}
    </p>

    <div v-else-if="cost" class="space-y-6">
      <div class="flex flex-wrap items-center gap-2">
        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusBadgeClass(cost.status)">
          {{ costStatusLabel(cost.status) }}
        </span>
        <span v-if="cost.trip_id" class="font-mono text-sm font-semibold text-teal-700 dark:text-teal-400">
          {{ tripCode }}
        </span>
        <span
          v-else
          class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-950/50 dark:text-amber-200"
        >
          {{ t('costs_page.badge_standalone') }}
        </span>
      </div>

      <dl class="grid gap-4 sm:grid-cols-2">
        <div>
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('costs_page.detail_type') }}</dt>
          <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ typeLabel(cost.type) }}</dd>
        </div>
        <div>
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('costs_page.col_payment') }}</dt>
          <dd class="mt-1 text-lg font-bold tabular-nums text-slate-900 dark:text-white">{{ formatVnd(cost.amount) }}</dd>
        </div>
        <div>
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('costs_page.col_time') }}</dt>
          <dd class="mt-1 text-sm text-slate-800 dark:text-slate-200">{{ formatDateTime(cost.created_at) }}</dd>
        </div>
        <div v-if="cost.confirmed_at">
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('costs_page.detail_confirmed_at') }}</dt>
          <dd class="mt-1 text-sm text-slate-800 dark:text-slate-200">{{ formatDateTime(cost.confirmed_at) }}</dd>
        </div>
        <div class="sm:col-span-2">
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('costs_page.col_submitter') }}</dt>
          <dd class="mt-1 text-sm text-slate-800 dark:text-slate-200">{{ cost.creator?.name || '—' }}</dd>
        </div>
        <div v-if="cost.confirmer?.name" class="sm:col-span-2">
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('costs_page.col_owner') }}</dt>
          <dd class="mt-1 text-sm text-slate-800 dark:text-slate-200">{{ cost.confirmer.name }}</dd>
        </div>
        <div v-if="cost.trip_id" class="sm:col-span-2">
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('costs_page.col_trip') }}</dt>
          <dd class="mt-1">
            <RouterLink
              class="font-mono text-sm font-semibold text-teal-700 underline decoration-teal-700/30 underline-offset-2 hover:text-teal-900 dark:text-teal-400"
              :to="`/trips/${cost.trip_id}`"
              data-testid="cost-detail-trip-link"
            >
              {{ tripCode }}
            </RouterLink>
            <p v-if="tripRouteLine" class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ tripRouteLine }}</p>
          </dd>
        </div>
        <div v-if="cost.description?.trim()" class="sm:col-span-2">
          <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('costs_page.col_description') }}</dt>
          <dd class="mt-1 whitespace-pre-wrap rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-800 dark:bg-slate-800/60 dark:text-slate-200">
            {{ cost.description.trim() }}
          </dd>
        </div>
        <div v-if="cost.rejection_reason?.trim()" class="sm:col-span-2">
          <dt class="text-xs font-semibold uppercase tracking-wide text-rose-600 dark:text-rose-400">{{ t('costs_page.col_notes') }}</dt>
          <dd class="mt-1 whitespace-pre-wrap rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-100">
            {{ cost.rejection_reason.trim() }}
          </dd>
        </div>
      </dl>

      <section aria-labelledby="cost-detail-evidence-heading">
        <h3 id="cost-detail-evidence-heading" class="text-sm font-bold text-slate-900 dark:text-slate-100">
          {{ t('costs_page.detail_evidence_heading') }}
        </h3>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('costs_page.detail_evidence_hint') }}</p>

        <div v-if="!evidenceRows.length" class="mt-4 rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
          {{ t('costs_page.detail_evidence_empty') }}
        </div>

        <div v-else class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
          <div
            v-for="row in evidenceRows"
            :key="row.key"
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40"
          >
            <template v-if="row.isImage">
              <button
                type="button"
                class="relative flex aspect-square w-full overflow-hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500"
                :data-testid="`cost-detail-evidence-${row.key}`"
                @click="openPreview(row)"
              >
                <img
                  :src="rowImageSrc(row)"
                  :alt="row.name"
                  loading="lazy"
                  decoding="async"
                  class="h-full w-full object-cover"
                  @error="onEvidenceImageError(row)"
                />
              </button>
            </template>
            <a
              v-else
              :href="resolvedFileUrl(row)"
              target="_blank"
              rel="noopener noreferrer"
              class="flex min-h-[7rem] flex-col items-center justify-center gap-2 px-3 py-4 text-center text-sm font-semibold text-teal-800 hover:bg-slate-50 dark:text-teal-300 dark:hover:bg-slate-800/80"
              :data-testid="`cost-detail-evidence-${row.key}`"
            >
              <DocumentTextIcon class="h-10 w-10 text-slate-400" aria-hidden="true" />
              <span class="line-clamp-2 break-all text-xs">{{ row.name }}</span>
              <span class="text-[11px] font-medium text-slate-500">{{ t('costs_page.detail_open_file') }}</span>
            </a>
          </div>
        </div>
      </section>
    </div>

    <Teleport to="body">
      <div
        v-if="previewUrl"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="t('costs_page.detail_preview_aria')"
        @click="previewUrl = null"
      >
        <img
          :src="previewUrl"
          class="max-h-[min(90dvh,900px)] max-w-full rounded-lg object-contain shadow-2xl"
          alt=""
          @click.stop
        />
      </div>
    </Teleport>
  </Modal>
</template>

<script setup>
import { computed, onUnmounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { DocumentTextIcon } from '@heroicons/vue/24/outline'
import Modal from '../ui/Modal.vue'
import { getTripCost } from '../../api/costs'
import { showAppErrorFromApi } from '../../composables/appMessage'
import { formatTripCode, formatVnd } from '../../util/labels'
import { fetchAttachmentBlob, resolveAttachmentAbsoluteUrl } from '../../util/downloadPdfAttachment'

const props = defineProps({
  open: { type: Boolean, default: false },
  /** @type {number | null} */
  costId: { type: Number, default: null },
  typeLabelFn: { type: Function, default: null },
})

const emit = defineEmits(['close'])

const { t, te, locale } = useI18n()

const loading = ref(false)
const loadError = ref('')
/** @type {import('vue').Ref<Record<string, unknown> | null>} */
const cost = ref(null)
const previewUrl = ref(null)
/** @type {import('vue').Ref<Map<number, string>>} */
const blobUrlByAttachmentId = ref(new Map())
const hydratingAttachmentIds = ref(new Set())

function revokeAllBlobUrls() {
  for (const u of blobUrlByAttachmentId.value.values()) {
    URL.revokeObjectURL(u)
  }
  blobUrlByAttachmentId.value = new Map()
}

async function hydrateAttachmentBlob(attachmentId) {
  const id = Number(attachmentId)
  if (!Number.isFinite(id) || id < 1) return
  if (blobUrlByAttachmentId.value.has(id) || hydratingAttachmentIds.value.has(id)) return
  hydratingAttachmentIds.value.add(id)
  try {
    const blob = await fetchAttachmentBlob(id)
    const objectUrl = URL.createObjectURL(blob)
    const next = new Map(blobUrlByAttachmentId.value)
    next.set(id, objectUrl)
    blobUrlByAttachmentId.value = next
  } catch {
    /* fallback: resolveAttachmentAbsoluteUrl */
  } finally {
    hydratingAttachmentIds.value.delete(id)
  }
}

function syncAttachmentBlobCache() {
  const ids = new Set(
    (cost.value?.attachments || []).map((a) => a?.id).filter((x) => x != null && Number(x) > 0),
  )
  const next = new Map()
  for (const [id, url] of blobUrlByAttachmentId.value) {
    if (ids.has(id)) next.set(id, url)
    else URL.revokeObjectURL(url)
  }
  blobUrlByAttachmentId.value = next
  for (const id of ids) {
    void hydrateAttachmentBlob(id)
  }
}

function looksLikeImage(mime, name, url) {
  const m = String(mime || '').toLowerCase()
  if (m.startsWith('image/')) return true
  const probe = `${name || ''} ${url || ''}`.toLowerCase()
  return /\.(jpe?g|png|gif|webp|bmp|heic|heif)(\?|$)/i.test(probe)
}

function rowImageSrc(row) {
  if (!row) return ''
  const id = row.attachmentId
  if (id != null) {
    const cached = blobUrlByAttachmentId.value.get(id)
    if (cached) return cached
    void hydrateAttachmentBlob(id)
  }
  return resolveAttachmentAbsoluteUrl(row.url)
}

function resolvedFileUrl(row) {
  return resolveAttachmentAbsoluteUrl(row?.url || '')
}

function onEvidenceImageError(row) {
  if (row?.attachmentId) void hydrateAttachmentBlob(row.attachmentId)
}

const modalTitle = computed(() => {
  if (!props.costId) return t('costs_page.detail_modal_title')
  return t('costs_page.detail_modal_title_id', { id: props.costId })
})

const modalSubtitle = computed(() => t('costs_page.detail_modal_subtitle'))

const tripCode = computed(() => formatTripCode(cost.value?.trip_id ?? cost.value?.trip?.id))

const tripRouteLine = computed(() => {
  const dr = cost.value?.trip?.dispatch_request ?? cost.value?.trip?.dispatchRequest
  if (!dr) return ''
  const o = (dr.origin ?? '').trim()
  const d = (dr.destination ?? '').trim()
  if (!o && !d) return ''
  return `${o || '—'} → ${d || '—'}`
})

const evidenceRows = computed(() => {
  const rows = []
  const seen = new Set()
  for (const a of cost.value?.attachments || []) {
    const url = a?.url
    if (!url || seen.has(url)) continue
    seen.add(url)
    const mime = String(a?.mime_type || '')
    const name = a?.original_name || t('costs_page.detail_file_fallback')
    rows.push({
      key: `att-${a.id}`,
      url,
      name,
      attachmentId: a.id ?? null,
      isImage: looksLikeImage(mime, name, url),
    })
  }
  const leg = cost.value?.receipt_url
  if (leg && !seen.has(leg)) {
    const lower = String(leg).toLowerCase()
    const isPdf = lower.includes('.pdf')
    rows.push({
      key: 'legacy-receipt',
      url: leg,
      name: t('costs_page.detail_legacy_receipt'),
      attachmentId: null,
      isImage: !isPdf && looksLikeImage('', '', leg),
    })
  }
  return rows
})

function typeLabel(slug) {
  if (props.typeLabelFn) return props.typeLabelFn(slug)
  if (!slug) return '—'
  const key = `trip_detail.costs.type_${slug}`
  if (te(key)) return t(key)
  return slug
}

function costStatusLabel(status) {
  if (!status) return '—'
  const key = `trip_detail.costs.status_${status}`
  if (te(key)) return t(key)
  const dash = `dashboard_analytics.cost_status_${status}`
  if (te(dash)) return t(dash)
  return String(status)
}

function statusBadgeClass(status) {
  const s = String(status || '')
  if (s === 'confirmed') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200'
  if (s === 'rejected') return 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-200'
  if (s === 'submitted' || s === 'pending') return 'bg-amber-100 text-amber-900 dark:bg-amber-950/50 dark:text-amber-100'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
}

function formatDateTime(iso) {
  if (!iso) return '—'
  try {
    const loc = locale.value === 'en' ? 'en-US' : 'vi-VN'
    return new Date(iso).toLocaleString(loc, {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    })
  } catch {
    return '—'
  }
}

async function openPreview(row) {
  if (row?.attachmentId) await hydrateAttachmentBlob(row.attachmentId)
  previewUrl.value = rowImageSrc(row)
}

async function loadDetail() {
  if (!props.open || !props.costId) {
    cost.value = null
    loadError.value = ''
    return
  }
  loading.value = true
  loadError.value = ''
  try {
    cost.value = await getTripCost(props.costId)
    syncAttachmentBlobCache()
  } catch (e) {
    cost.value = null
    loadError.value = t('costs_page.detail_load_err')
    showAppErrorFromApi(e)
  } finally {
    loading.value = false
  }
}

watch(
  () => [props.open, props.costId],
  () => {
    previewUrl.value = null
    if (props.open) loadDetail()
    else {
      cost.value = null
      loadError.value = ''
      revokeAllBlobUrls()
    }
  },
  { immediate: true },
)

onUnmounted(() => {
  revokeAllBlobUrls()
})
</script>
