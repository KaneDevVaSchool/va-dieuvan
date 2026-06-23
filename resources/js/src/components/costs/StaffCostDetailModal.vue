<template>
  <Modal
    :open="open"
    extra-wide
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

    <div v-else-if="cost" class="space-y-5">
      <!-- Hero: mã + số tiền -->
      <div
        class="overflow-hidden rounded-2xl border border-slate-200/90 bg-gradient-to-br from-slate-50 via-white to-teal-50/40 dark:border-slate-700 dark:from-slate-900/80 dark:via-slate-900/60 dark:to-teal-950/20"
        data-testid="cost-detail-hero"
      >
        <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-start sm:justify-between sm:p-5">
          <div class="min-w-0 space-y-2">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('costs_page.detail_cost_code') }}
            </p>
            <p class="font-mono text-xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">
              {{ costNoteCode }}
            </p>
            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusBadgeClass(cost.status)">
                {{ costStatusLabel(cost.status) }}
              </span>
              <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">{{ typeLabel(cost.type) }}</span>
              <span
                v-if="!cost.trip_id"
                class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-950/50 dark:text-amber-200"
              >
                {{ t('costs_page.badge_standalone') }}
              </span>
              <RouterLink
                v-else
                class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 font-mono text-xs font-bold text-teal-800 ring-1 ring-teal-200/70 hover:bg-teal-100 dark:bg-teal-950/40 dark:text-teal-200 dark:ring-teal-800/50"
                :to="`/trips/${cost.trip_id}`"
                data-testid="cost-detail-trip-link-hero"
                @click.stop
              >
                {{ tripCode }}
              </RouterLink>
            </div>
          </div>
          <div class="shrink-0 text-left sm:text-right">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('costs_page.col_payment') }}
            </p>
            <p class="mt-0.5 text-2xl font-bold tabular-nums text-slate-900 dark:text-white sm:text-3xl">
              {{ formatVnd(cost.amount) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Chuyến & xe -->
      <section
        class="rounded-2xl border border-slate-200/90 bg-white dark:border-slate-700 dark:bg-slate-900/30"
        aria-labelledby="cost-detail-trip-vehicle-heading"
      >
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
          <h3 id="cost-detail-trip-vehicle-heading" class="text-sm font-bold text-slate-900 dark:text-slate-100">
            {{ t('costs_page.detail_section_trip_vehicle') }}
          </h3>
        </div>
        <div class="px-4 py-4 sm:px-5">
          <p
            v-if="!cost.trip_id"
            class="text-sm italic leading-relaxed text-slate-500 dark:text-slate-400"
          >
            {{ t('costs_page.detail_standalone_vehicle_hint') }}
          </p>
          <template v-else>
            <dl class="grid gap-4 sm:grid-cols-2">
              <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('costs_page.col_trip') }}
                </dt>
                <dd class="mt-1.5">
                  <RouterLink
                    class="font-mono text-base font-bold text-teal-700 underline decoration-teal-700/30 underline-offset-2 hover:text-teal-900 dark:text-teal-400"
                    :to="`/trips/${cost.trip_id}`"
                    data-testid="cost-detail-trip-link"
                  >
                    {{ tripCode }}
                  </RouterLink>
                  <p v-if="tripRouteLine" class="mt-1.5 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                    {{ tripRouteLine }}
                  </p>
                  <p v-else class="mt-1.5 text-sm italic text-slate-400 dark:text-slate-500">
                    {{ t('costs_page.empty_route') }}
                  </p>
                </dd>
              </div>
              <div v-if="tripTypeSlug">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('costs_page.detail_trip_type') }}
                </dt>
                <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">
                  {{ labelTripType(tripTypeSlug) }}
                </dd>
              </div>
              <div v-if="tripDepartDisplay">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('costs_page.detail_trip_depart') }}
                </dt>
                <dd class="mt-1 text-sm tabular-nums text-slate-800 dark:text-slate-200">
                  {{ tripDepartDisplay }}
                </dd>
              </div>
              <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('costs_page.col_fleet_source') }}
                </dt>
                <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-200">
                  {{ fleetModeLabel(tripFleetMode) }}
                </dd>
              </div>
              <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('costs_page.detail_vehicle') }}
                </dt>
                <dd
                  class="mt-1 text-sm"
                  :class="vehicleDisplay ? 'font-semibold text-slate-900 dark:text-slate-100' : 'italic text-slate-400 dark:text-slate-500'"
                >
                  {{ vehicleDisplay || t('costs_page.empty_not_available') }}
                </dd>
              </div>
              <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('costs_page.detail_driver') }}
                </dt>
                <dd
                  class="mt-1 text-sm"
                  :class="driverDisplay ? 'font-medium text-slate-800 dark:text-slate-200' : 'italic text-slate-400 dark:text-slate-500'"
                >
                  {{ driverDisplay || t('costs_page.empty_not_available') }}
                </dd>
              </div>
              <div v-if="providerName" class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('costs_page.col_provider') }}
                </dt>
                <dd class="mt-1 text-sm font-medium text-slate-800 dark:text-slate-200">
                  {{ providerName }}
                </dd>
              </div>
            </dl>
          </template>
        </div>
      </section>

      <!-- Chi phí từng chặng (dự toán phiếu) -->
      <section
        v-if="estimateLineRows.length"
        class="rounded-2xl border border-slate-200/90 bg-white dark:border-slate-700 dark:bg-slate-900/30"
        aria-labelledby="cost-detail-leg-costs-heading"
        data-testid="cost-detail-leg-costs"
      >
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
          <h3 id="cost-detail-leg-costs-heading" class="text-sm font-bold text-slate-900 dark:text-slate-100">
            {{ t('costs_page.detail_section_leg_costs') }}
          </h3>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            {{ t('costs_page.detail_leg_costs_hint') }}
          </p>
        </div>
        <div class="overflow-x-auto px-4 py-4 sm:px-5">
          <table class="w-full min-w-[640px] border-collapse text-sm">
            <thead>
              <tr class="border-b border-slate-200 text-left dark:border-slate-700">
                <th scope="col" class="pb-2 pr-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('costs_page.detail_leg_col_leg') }}
                </th>
                <th scope="col" class="pb-2 pr-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('costs_page.col_description') }}
                </th>
                <th scope="col" class="pb-2 pr-3 text-right text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('costs_page.col_unit_price') }}
                </th>
                <th scope="col" class="pb-2 pr-3 text-right text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('costs_page.col_extra_fee') }}
                </th>
                <th scope="col" class="pb-2 text-right text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('costs_page.col_payment') }}
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr
                v-for="row in estimateLineRows"
                :key="row.key"
                class="align-top"
                :data-testid="`cost-detail-leg-row-${row.key}`"
              >
                <td class="py-3 pr-3 whitespace-nowrap">
                  <span
                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                    :class="row.leg_seq ? 'bg-amber-50 text-amber-900 ring-1 ring-amber-200/80 dark:bg-amber-950/40 dark:text-amber-100 dark:ring-amber-800/50' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'"
                  >
                    {{ row.leg_label }}
                  </span>
                </td>
                <td class="py-3 pr-3 min-w-[12rem]">
                  <p class="font-medium text-slate-900 dark:text-slate-100">{{ row.description }}</p>
                  <p v-if="row.leg_route && row.leg_route !== row.description" class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    {{ row.leg_route }}
                  </p>
                </td>
                <td class="py-3 pr-3 text-right tabular-nums text-slate-800 dark:text-slate-200">
                  {{ row.unit_price_display }}
                </td>
                <td class="py-3 pr-3 text-right tabular-nums text-slate-800 dark:text-slate-200">
                  {{ row.extra_fee_display }}
                </td>
                <td class="py-3 text-right font-semibold tabular-nums text-slate-900 dark:text-slate-100">
                  {{ formatVnd(row.amount) }}
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="border-t border-slate-200 dark:border-slate-700">
                <td colspan="4" class="pt-3 pr-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                  {{ t('costs_page.detail_leg_total_label') }}
                </td>
                <td class="pt-3 text-right text-base font-bold tabular-nums text-teal-800 dark:text-teal-300">
                  {{ formatVnd(estimateLinesTotal) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </section>

      <!-- Thông tin ghi chú -->
      <section
        class="rounded-2xl border border-slate-200/90 bg-white dark:border-slate-700 dark:bg-slate-900/30"
        aria-labelledby="cost-detail-note-heading"
      >
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
          <h3 id="cost-detail-note-heading" class="text-sm font-bold text-slate-900 dark:text-slate-100">
            {{ t('costs_page.detail_section_note') }}
          </h3>
        </div>
        <dl class="grid gap-4 px-4 py-4 sm:grid-cols-2 sm:px-5">
          <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('costs_page.detail_type') }}
            </dt>
            <dd class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ typeLabel(cost.type) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('costs_page.col_time') }}
            </dt>
            <dd
              class="mt-1 text-sm tabular-nums"
              :class="displayTextOrNull(cost.created_at) ? 'text-slate-800 dark:text-slate-200' : 'italic text-slate-400 dark:text-slate-500'"
            >
              {{ formatDateTimeDisplay(cost.created_at) }}
            </dd>
          </div>
          <div v-if="cost.confirmed_at">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('costs_page.detail_confirmed_at') }}
            </dt>
            <dd class="mt-1 text-sm tabular-nums text-slate-800 dark:text-slate-200">
              {{ formatDateTimeDisplay(cost.confirmed_at) }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('costs_page.col_submitter') }}
            </dt>
            <dd
              class="mt-1 text-sm"
              :class="displayTextOrNull(cost.creator?.name) ? 'text-slate-800 dark:text-slate-200' : 'italic text-slate-400 dark:text-slate-500'"
            >
              {{ displayTextOrNull(cost.creator?.name) || t('costs_page.empty_not_available') }}
            </dd>
          </div>
          <div v-if="cost.confirmer?.name">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('costs_page.col_owner') }}
            </dt>
            <dd class="mt-1 text-sm text-slate-800 dark:text-slate-200">{{ cost.confirmer.name }}</dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              {{ t('costs_page.col_description') }}
            </dt>
            <dd
              v-if="displayTextOrNull(cost.description)"
              class="mt-1.5 whitespace-pre-wrap rounded-xl border border-slate-200/80 bg-slate-50/90 px-3.5 py-2.5 text-sm leading-relaxed text-slate-800 dark:border-slate-600 dark:bg-slate-800/60 dark:text-slate-200"
            >
              {{ String(cost.description).trim() }}
            </dd>
            <dd v-else class="mt-1 text-sm italic text-slate-400 dark:text-slate-500">
              {{ t('costs_page.empty_description') }}
            </dd>
          </div>
          <div v-if="cost.rejection_reason?.trim()" class="sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wide text-rose-600 dark:text-rose-400">
              {{ t('costs_page.col_notes') }}
            </dt>
            <dd class="mt-1.5 whitespace-pre-wrap rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-sm text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-100">
              {{ cost.rejection_reason.trim() }}
            </dd>
          </div>
        </dl>
      </section>

      <!-- Minh chứng -->
      <section
        class="rounded-2xl border border-slate-200/90 bg-white dark:border-slate-700 dark:bg-slate-900/30"
        aria-labelledby="cost-detail-evidence-heading"
      >
        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700 sm:px-5">
          <h3 id="cost-detail-evidence-heading" class="text-sm font-bold text-slate-900 dark:text-slate-100">
            {{ t('costs_page.detail_evidence_heading') }}
          </h3>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ t('costs_page.detail_evidence_hint') }}</p>
        </div>

        <div v-if="!evidenceRows.length" class="px-4 py-10 text-center text-sm italic text-slate-500 dark:px-5 dark:text-slate-400">
          {{ t('costs_page.detail_evidence_empty') }}
        </div>

        <div v-else class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 sm:p-5">
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
import { formatCostNoteCode, formatTripCode, formatVnd, labelTripType } from '../../util/labels'
import { displayTextOrNull, isEmptyDisplay } from '../../util/displayValue'
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

const costNoteCode = computed(() => formatCostNoteCode(cost.value?.id ?? props.costId))

const modalTitle = computed(() => {
  if (!props.open) return t('costs_page.detail_modal_title')
  return t('costs_page.detail_modal_title_code', { code: costNoteCode.value })
})

const modalSubtitle = computed(() => {
  if (cost.value?.trip_id) {
    return t('costs_page.detail_modal_subtitle_trip', {
      code: costNoteCode.value,
      trip: tripCode.value,
    })
  }
  return t('costs_page.detail_modal_subtitle')
})

const tripCode = computed(() => formatTripCode(cost.value?.trip_id ?? cost.value?.trip?.id))

const trip = computed(() => cost.value?.trip ?? null)

const dispatchRequest = computed(() => trip.value?.dispatch_request ?? trip.value?.dispatchRequest ?? null)

const tripTypeSlug = computed(() => dispatchRequest.value?.trip_type ?? null)

const tripRouteLine = computed(() => {
  const dr = dispatchRequest.value
  if (!dr) return ''
  const o = displayTextOrNull(dr.origin)
  const d = displayTextOrNull(dr.destination)
  if (!o && !d) return ''
  const from = o || t('costs_page.empty_not_available')
  const to = d || t('costs_page.empty_not_available')
  return `${from} → ${to}`
})

const tripDepartDisplay = computed(() => {
  const iso = trip.value?.depart_at
  if (isEmptyDisplay(iso)) return ''
  return formatDateTimeDisplay(iso)
})

const providerName = computed(() => {
  const tp = trip.value?.transport_provider ?? trip.value?.transportProvider
  return displayTextOrNull(tp?.name) || ''
})

const vehicleDisplay = computed(() => {
  const v = trip.value?.vehicle
  const plate = displayTextOrNull(v?.license_plate)
  if (plate) {
    const typePart = displayTextOrNull(v?.type)
    return typePart ? `${plate} · ${typePart}` : plate
  }
  const ext = displayTextOrNull(trip.value?.external_vehicle_ref)
  if (ext) return ext
  return ''
})

const driverDisplay = computed(() => {
  const d = trip.value?.driver
  const name = displayTextOrNull(d?.full_name)
  if (name) {
    const phone = displayTextOrNull(d?.phone)
    return phone ? `${name} · ${phone}` : name
  }
  return displayTextOrNull(trip.value?.external_driver_ref) || ''
})

const tripFleetMode = computed(() => {
  const tr = trip.value
  if (!tr) return 'unspecified'
  const providerId = tr.transport_provider_id ?? tr.transportProvider?.id ?? null
  const vehicleId = tr.vehicle_id ?? tr.vehicle?.id ?? null
  const providerType = tr.transport_provider?.type ?? tr.transportProvider?.type ?? null
  if (providerId) {
    if (providerType === 'taxi') return 'taxi'
    return 'vendor_hire'
  }
  if (vehicleId) return 'internal'
  return 'unspecified'
})

const estimateLineRows = computed(() => {
  const lines = cost.value?.estimate_lines
  if (!Array.isArray(lines) || !lines.length) return []
  return lines.map((line, index) => {
    const legSeq = line?.leg_seq != null && Number(line.leg_seq) > 0 ? Number(line.leg_seq) : null
    const unit = Number(line?.unit_price ?? 0)
    const extra = Number(line?.extra_fee ?? 0)
    const amount = Number(line?.amount ?? unit + extra)
    const description = String(line?.description ?? '').trim() || t('costs_page.empty_not_available')
    const legRoute = String(line?.leg_route ?? '').trim()
    return {
      key: String(line?.line_key ?? `line-${index}`),
      leg_seq: legSeq,
      leg_label: legSeq
        ? t('trip_detail.passengers.leg_seq', { n: legSeq })
        : t('costs_page.detail_leg_trip_wide'),
      description,
      leg_route: legRoute,
      amount,
      unit_price_display: unit > 0 ? formatVnd(unit) : t('costs_page.empty_not_available'),
      extra_fee_display: extra > 0 ? formatVnd(extra) : t('costs_page.empty_not_available'),
    }
  })
})

const estimateLinesTotal = computed(() =>
  estimateLineRows.value.reduce((sum, row) => sum + (Number(row.amount) || 0), 0),
)

function fleetModeLabel(mode) {
  const map = {
    internal: 'dashboard_analytics.fleet_internal',
    vendor_hire: 'dashboard_analytics.fleet_vendor_hire',
    taxi: 'dashboard_analytics.fleet_taxi',
    unspecified: 'dashboard_analytics.fleet_unspecified',
  }
  const key = map[mode]
  if (key && te(key)) return t(key)
  return mode ? String(mode) : t('costs_page.empty_not_available')
}

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
  if (!slug) return t('costs_page.empty_not_available')
  const key = `trip_detail.costs.type_${slug}`
  if (te(key)) return t(key)
  return slug
}

function costStatusLabel(status) {
  if (!status) return t('costs_page.empty_not_available')
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

function formatDateTimeDisplay(iso) {
  if (isEmptyDisplay(iso)) return t('costs_page.empty_date')
  const formatted = formatDateTime(iso)
  return isEmptyDisplay(formatted) ? t('costs_page.empty_date') : formatted
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
