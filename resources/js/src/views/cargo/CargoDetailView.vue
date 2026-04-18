<template>
  <div class="space-y-5 pb-10">
    <div v-if="loadError" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-200">
      {{ loadError }}
    </div>

    <template v-else-if="shipment">
      <!-- Header -->
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
          <RouterLink
            to="/cargo"
            class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:text-teal-900 dark:text-teal-400 dark:hover:text-teal-200"
          >
            <ChevronLeftIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
            {{ t('cargo_detail.back_list') }}
          </RouterLink>
          <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-xl md:text-2xl">
            {{ t('cargo_detail.hero_title', { code: displayCode }) }}
          </h1>
          <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
            <span class="font-medium text-slate-800 dark:text-slate-200">{{ routeSummary }}</span>
            <span v-if="departHint" class="text-slate-500"> · {{ departHint }}</span>
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <RouterLink
            v-if="requestId"
            :to="'/requests/' + requestId"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
          >
            <DocumentTextIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" aria-hidden="true" />
            {{ t('cargo_detail.btn_request') }}
          </RouterLink>
          <RouterLink
            v-if="shipment.trip_id"
            :to="'/trips/' + shipment.trip_id"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
          >
            <TruckIcon class="h-5 w-5 text-slate-500 dark:text-slate-400" aria-hidden="true" />
            {{ t('cargo_detail.btn_trip') }}
          </RouterLink>
          <a
            v-if="mapsHref"
            :href="mapsHref"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-teal-600/20 transition hover:bg-teal-700"
          >
            <MapPinIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
            {{ t('cargo_detail.btn_maps') }}
          </a>
        </div>
      </div>

      <!-- Hero status -->
      <div
        class="overflow-hidden rounded-2xl bg-gradient-to-br from-sky-600 via-teal-600 to-teal-700 px-4 py-5 text-white shadow-lg shadow-teal-900/20 sm:px-6 sm:py-6"
      >
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div class="flex items-start gap-3">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15 backdrop-blur">
              <TruckIcon class="h-7 w-7" aria-hidden="true" />
            </div>
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-teal-100/90">
                {{ t('cargo_detail.status_label') }}
              </p>
              <p class="mt-0.5 text-xl font-bold sm:text-2xl">{{ labelCargoStatus(shipment.status) }}</p>
              <p v-if="slaLine" class="mt-1 text-sm text-teal-100/95">{{ slaLine }}</p>
            </div>
          </div>
          <div class="grid grid-cols-3 gap-3 sm:gap-6 lg:min-w-[320px]">
            <div class="rounded-xl bg-white/10 px-3 py-2 text-center backdrop-blur sm:px-4">
              <p class="text-[10px] font-semibold uppercase tracking-wide text-teal-100/80">{{ t('cargo_detail.hero_qty') }}</p>
              <p class="mt-0.5 text-lg font-bold tabular-nums sm:text-xl">{{ shipment.quantity ?? '—' }}</p>
            </div>
            <div class="rounded-xl bg-white/10 px-3 py-2 text-center backdrop-blur sm:px-4">
              <p class="text-[10px] font-semibold uppercase tracking-wide text-teal-100/80">{{ t('cargo_detail.hero_weight') }}</p>
              <p class="mt-0.5 text-lg font-bold tabular-nums sm:text-xl">{{ weightLabel }}</p>
            </div>
            <div class="rounded-xl bg-white/10 px-3 py-2 text-center backdrop-blur sm:px-4">
              <p class="text-[10px] font-semibold uppercase tracking-wide text-teal-100/80">{{ t('cargo_detail.hero_progress') }}</p>
              <p class="mt-0.5 text-lg font-bold tabular-nums sm:text-xl">{{ progressPct }}%</p>
            </div>
          </div>
        </div>
        <div class="mt-4 h-2 overflow-hidden rounded-full bg-black/20">
          <div class="h-full rounded-full bg-white/90 transition-all" :style="{ width: progressPct + '%' }" />
        </div>
        <p v-if="slaBreached" class="mt-3 flex items-center gap-2 rounded-lg bg-amber-400/20 px-3 py-2 text-sm font-medium text-amber-50">
          <ExclamationTriangleIcon class="h-5 w-5 shrink-0 text-amber-200" aria-hidden="true" />
          {{ t('cargo_detail.sla_breached') }}
        </p>
      </div>

      <div class="grid gap-5 lg:grid-cols-5">
        <!-- Main column -->
        <div class="min-w-0 space-y-5 lg:col-span-3">
          <!-- Route timeline -->
          <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
            <div class="border-b border-slate-200/90 px-4 py-3 dark:border-slate-700 sm:px-5">
              <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                <MapPinIcon class="h-5 w-5 text-emerald-600 dark:text-emerald-400" aria-hidden="true" />
                {{ t('cargo_detail.card_route') }}
              </h2>
            </div>
            <div class="px-4 py-5 sm:px-5">
              <div class="relative space-y-6 pl-2">
                <div class="absolute left-[15px] top-3 bottom-3 w-0.5 bg-slate-200 dark:bg-slate-600" aria-hidden="true" />
                <div class="relative flex gap-4">
                  <div
                    class="relative z-[1] flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-white bg-emerald-500 text-white shadow dark:border-slate-900"
                  >
                    <CheckCircleIcon v-if="pickupDone" class="h-5 w-5" aria-hidden="true" />
                    <span v-else class="h-2.5 w-2.5 rounded-full bg-white/90" />
                  </div>
                  <div class="min-w-0 flex-1 pt-0.5">
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">{{
                        t('cargo_detail.route_pickup')
                      }}</span>
                      <span
                        v-if="pickupDone"
                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-100"
                      >
                        {{ t('cargo_detail.route_done') }}
                      </span>
                      <span v-else class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        {{ t('cargo_detail.route_pending') }}
                      </span>
                    </div>
                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ shipment.pickup_address || '—' }}</p>
                    <p v-if="shipment.sender_name" class="text-xs text-slate-600 dark:text-slate-400">{{ shipment.sender_name }}</p>
                    <p v-if="shipment.picked_up_at" class="mt-1 text-xs tabular-nums text-slate-500">{{ fmt(shipment.picked_up_at) }}</p>
                  </div>
                </div>
                <div class="relative flex gap-4">
                  <div
                    class="relative z-[1] flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-white bg-rose-500 text-white shadow dark:border-slate-900"
                  >
                    <TruckIcon v-if="inMotion" class="h-4 w-4" aria-hidden="true" />
                    <CheckCircleIcon v-else-if="deliveredDone" class="h-5 w-5" aria-hidden="true" />
                    <span v-else class="h-2.5 w-2.5 rounded-full bg-white/90" />
                  </div>
                  <div class="min-w-0 flex-1 pt-0.5">
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="text-xs font-bold uppercase tracking-wide text-rose-700 dark:text-rose-400">{{
                        t('cargo_detail.route_delivery')
                      }}</span>
                      <span
                        v-if="deliveredDone"
                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-100"
                      >
                        {{ t('cargo_detail.route_delivered') }}
                      </span>
                      <span
                        v-else-if="inMotion"
                        class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-900 dark:bg-sky-950/50 dark:text-sky-100"
                      >
                        {{ t('cargo_detail.route_moving') }}
                      </span>
                      <span v-else class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        {{ t('cargo_detail.route_awaiting') }}
                      </span>
                    </div>
                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{{ shipment.delivery_address || '—' }}</p>
                    <p v-if="shipment.receiver_name" class="text-xs text-slate-600 dark:text-slate-400">{{ shipment.receiver_name }}</p>
                    <p v-if="shipment.delivered_at" class="mt-1 text-xs tabular-nums text-slate-500">{{ fmt(shipment.delivered_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Map placeholder -->
          <div class="overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 dark:border-slate-600 dark:bg-slate-800/50">
            <div class="flex min-h-[180px] flex-col items-center justify-center gap-2 px-4 py-10 text-center sm:min-h-[220px]">
              <MapIcon class="h-10 w-10 text-slate-400 dark:text-slate-500" aria-hidden="true" />
              <p class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ t('cargo_detail.map_placeholder_title') }}</p>
              <p class="max-w-md text-xs text-slate-500 dark:text-slate-400">{{ t('cargo_detail.map_placeholder_hint') }}</p>
            </div>
          </div>

          <!-- Audit timeline -->
          <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
            <div class="border-b border-slate-200/90 px-4 py-3 dark:border-slate-700 sm:px-5">
              <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                <ClockIcon class="h-5 w-5 text-violet-600 dark:text-violet-400" aria-hidden="true" />
                {{ t('cargo_detail.card_timeline') }}
              </h2>
            </div>
            <div class="px-4 py-4 sm:px-5">
              <div v-if="timelineLoading" class="text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_page.timeline_loading') }}</div>
              <ul v-else-if="timelineItems.length" class="relative ml-1 space-y-4 border-l-2 border-slate-200 pl-5 dark:border-slate-600">
                <li v-for="(ev, idx) in timelineItems" :key="idx + ev.at" class="relative">
                  <span
                    class="absolute -left-[calc(1.25rem+5px)] top-1.5 h-2.5 w-2.5 rounded-full border-2 border-white bg-teal-600 dark:border-slate-900"
                  />
                  <div class="flex flex-wrap justify-between gap-2 text-xs">
                    <span class="font-medium text-slate-800 dark:text-slate-200">{{ timelineTitle(ev) }}</span>
                    <span class="tabular-nums text-slate-500 dark:text-slate-400">{{ fmt(ev.at) }}</span>
                  </div>
                  <p v-if="timelineSubtitle(ev)" class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">{{ timelineSubtitle(ev) }}</p>
                  <p v-if="ev.actor?.name" class="mt-0.5 text-[11px] text-slate-500">{{ ev.actor.name }}</p>
                </li>
              </ul>
              <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_page.timeline_empty') }}</p>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <aside class="min-w-0 space-y-5 lg:col-span-2">
          <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
            <div class="border-b border-slate-200/90 px-4 py-3 dark:border-slate-700">
              <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                <UserIcon class="h-5 w-5 text-teal-600 dark:text-teal-400" aria-hidden="true" />
                {{ t('cargo_detail.card_fleet') }}
              </h2>
            </div>
            <div class="space-y-3 px-4 py-4">
              <template v-if="tripLite?.driver">
                <div class="flex gap-3">
                  <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-bold text-teal-800 dark:bg-teal-950 dark:text-teal-200"
                  >
                    {{ driverInitials(tripLite.driver.full_name) }}
                  </div>
                  <div class="min-w-0">
                    <p class="font-semibold text-slate-900 dark:text-slate-100">{{ tripLite.driver.full_name }}</p>
                    <p v-if="tripLite.driver.phone" class="text-xs tabular-nums text-slate-600 dark:text-slate-400">{{ tripLite.driver.phone }}</p>
                  </div>
                </div>
              </template>
              <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_detail.fleet_unassigned') }}</p>
              <ul class="space-y-2 border-t border-slate-100 pt-3 text-sm dark:border-slate-800">
                <li v-if="tripLite?.vehicle" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('cargo_detail.fleet_plate') }}</span>
                  <span class="font-mono font-medium text-slate-900 dark:text-slate-100">{{ tripLite.vehicle.license_plate }}</span>
                </li>
                <li v-if="tripLite?.transport_provider" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('cargo_detail.fleet_provider') }}</span>
                  <span class="text-right font-medium text-slate-900 dark:text-slate-100">{{ tripLite.transport_provider.name }}</span>
                </li>
                <li v-if="tripLite?.dispatcher" class="flex justify-between gap-2">
                  <span class="text-slate-500 dark:text-slate-400">{{ t('cargo_detail.fleet_dispatcher') }}</span>
                  <span class="text-right text-slate-800 dark:text-slate-200">{{ tripLite.dispatcher.name }}</span>
                </li>
              </ul>
            </div>
          </div>

          <!-- Costs -->
          <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
            <div class="border-b border-slate-200/90 px-4 py-3 dark:border-slate-700">
              <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                <CurrencyDollarIcon class="h-5 w-5 text-teal-600 dark:text-teal-400" aria-hidden="true" />
                {{ t('cargo_detail.card_costs') }}
              </h2>
            </div>
            <div class="px-4 py-4">
              <p v-if="costsForbidden" class="text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_detail.costs_no_permission') }}</p>
              <p v-else-if="costsLoading" class="text-sm text-slate-500">{{ t('cargo_detail.costs_loading') }}</p>
              <template v-else-if="costItems.length">
                <ul class="space-y-2">
                  <li v-for="c in costItems" :key="c.id" class="flex justify-between gap-3 text-sm">
                    <span class="min-w-0 text-slate-600 dark:text-slate-400">
                      {{ c.description?.trim() || c.type || t('cargo_detail.cost_line') }}
                      <span v-if="c.status" class="ml-1 text-[11px] text-slate-400">({{ c.status }})</span>
                    </span>
                    <span class="shrink-0 tabular-nums font-medium text-slate-900 dark:text-slate-100">{{ formatMoney(c.amount, c.currency) }}</span>
                  </li>
                </ul>
                <div class="mt-4 border-t border-slate-200 pt-3 dark:border-slate-700">
                  <div class="flex justify-between text-sm font-semibold text-slate-900 dark:text-slate-100">
                    <span>{{ t('cargo_detail.costs_total') }}</span>
                    <span class="tabular-nums text-teal-700 dark:text-teal-400">{{ formatMoney(costsTotal) }}</span>
                  </div>
                </div>
              </template>
              <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_detail.costs_empty') }}</p>
            </div>
          </div>

          <!-- Documents -->
          <div class="overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900/40">
            <div class="border-b border-slate-200/90 px-4 py-3 dark:border-slate-700">
              <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-slate-100">
                <FolderIcon class="h-5 w-5 text-violet-600 dark:text-violet-400" aria-hidden="true" />
                {{ t('cargo_detail.card_docs') }}
              </h2>
            </div>
            <div class="px-4 py-4">
              <ul v-if="(shipment.attachments ?? []).length" class="space-y-2">
                <li
                  v-for="att in shipment.attachments"
                  :key="att.id"
                  class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2.5 dark:border-slate-700 dark:bg-slate-800/40"
                >
                  <DocumentIcon class="h-8 w-8 shrink-0 text-slate-500" aria-hidden="true" />
                  <div class="min-w-0 flex-1">
                    <a
                      v-if="att.url"
                      :href="att.url"
                      target="_blank"
                      rel="noopener"
                      class="block truncate text-sm font-medium text-teal-700 underline hover:text-teal-900 dark:text-teal-400"
                    >
                      {{ att.original_name || t('cargo_page.open_pod') }}
                    </a>
                    <span v-else class="block truncate text-sm text-slate-700 dark:text-slate-300">{{ att.original_name }}</span>
                    <span class="text-[11px] text-slate-500">{{ formatBytes(att.size_bytes) }}</span>
                  </div>
                  <a
                    v-if="att.url"
                    :href="att.url"
                    target="_blank"
                    rel="noopener"
                    class="shrink-0 rounded-lg p-2 text-slate-500 hover:bg-white hover:text-teal-700 dark:hover:bg-slate-700 dark:hover:text-teal-300"
                    :aria-label="t('cargo_detail.doc_download')"
                  >
                    <ArrowDownTrayIcon class="h-5 w-5" aria-hidden="true" />
                  </a>
                </li>
              </ul>
              <p v-else class="text-sm text-slate-500 dark:text-slate-400">{{ t('cargo_page.no_pod') }}</p>
              <FileUpload
                v-if="canUploadPod"
                :key="'pod-' + shipment.id"
                class="mt-4"
                :label="t('cargo_page.upload_pod')"
                :hint="t('cargo_page.upload_pod_hint')"
                :upload-fn="(file, onProgress) => uploadCargoPod(shipment.id, file, onProgress)"
                @uploaded="reloadShipment"
              />
            </div>
          </div>

          <!-- Roadmap / suggestions -->
          <div class="rounded-2xl border border-amber-200/80 bg-amber-50/50 p-4 dark:border-amber-900/40 dark:bg-amber-950/20">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-amber-900 dark:text-amber-100">
              <LightBulbIcon class="h-5 w-5" aria-hidden="true" />
              {{ t('cargo_detail.roadmap_title') }}
            </h3>
            <ul class="mt-3 list-inside list-disc space-y-1.5 text-xs leading-relaxed text-amber-950/90 dark:text-amber-100/90">
              <li>{{ t('cargo_detail.roadmap_1') }}</li>
              <li>{{ t('cargo_detail.roadmap_2') }}</li>
              <li>{{ t('cargo_detail.roadmap_3') }}</li>
            </ul>
          </div>
        </aside>
      </div>
    </template>

    <div v-else-if="loading" class="py-16 text-center text-sm text-slate-500 dark:text-slate-400">
      {{ t('cargo_page.loading') }}
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowDownTrayIcon,
  CheckCircleIcon,
  ChevronLeftIcon,
  ClockIcon,
  CurrencyDollarIcon,
  DocumentIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  FolderIcon,
  LightBulbIcon,
  MapIcon,
  MapPinIcon,
  TruckIcon,
  UserIcon,
} from '@heroicons/vue/24/outline'
import FileUpload from '../../components/ui/FileUpload.vue'
import { uploadCargoPod } from '../../api/attachments'
import { listCostsForTrip } from '../../api/costs'
import { getCargoShipment, getCargoShipmentTimeline } from '../../api/cargo'
import { labelCargoStatus } from '../../util/labels'
import { useAuthStore } from '../../store'
import { i18n } from '../../i18n'

const route = useRoute()
const { t, te, locale } = useI18n()
const auth = useAuthStore()

const loading = ref(true)
const loadError = ref('')
const shipment = ref(null)
const timelineItems = ref([])
const timelineLoading = ref(true)
const costItems = ref([])
const costsLoading = ref(false)
const costsForbidden = ref(false)

const displayCode = computed(() => shipment.value?.tracking_code || '#' + shipment.value?.id)
const requestId = computed(() => shipment.value?.dispatch_request_id ?? shipment.value?.dispatch_request?.id ?? null)
const tripLite = computed(() => shipment.value?.trip ?? null)

const routeSummary = computed(() => {
  const s = shipment.value
  if (!s) return ''
  const a = s.pickup_address || s.dispatch_request?.origin || '—'
  const b = s.delivery_address || s.dispatch_request?.destination || '—'
  return `${a} → ${b}`
})

const departHint = computed(() => {
  const d = tripLite.value?.depart_at || shipment.value?.dispatch_request?.depart_at
  if (!d) return ''
  return t('cargo_detail.depart_prefix') + ' ' + fmt(d)
})

const mapsHref = computed(() => {
  const s = shipment.value
  if (!s) return ''
  const o = s.pickup_address || s.dispatch_request?.origin
  const dest = s.delivery_address || s.dispatch_request?.destination
  if (!o || !dest) return ''
  const u = new URL('https://www.google.com/maps/dir/')
  u.searchParams.set('api', '1')
  u.searchParams.set('origin', o)
  u.searchParams.set('destination', dest)
  return u.toString()
})

const weightLabel = computed(() => {
  const g = shipment.value?.weight_grams
  if (g == null || g === '') return '—'
  const kg = Number(g) / 1000
  if (!Number.isFinite(kg)) return '—'
  return kg < 1 ? `${g} g` : `${kg.toLocaleString(locale.value === 'en' ? 'en-US' : 'vi-VN', { maximumFractionDigits: 2 })} kg`
})

const progressPct = computed(() => {
  const st = shipment.value?.status
  const map = { pending: 12, picked_up: 40, in_transit: 72, delivered: 100, failed: 0, cancelled: 0 }
  return map[st] ?? 15
})

const pickupDone = computed(() => ['picked_up', 'in_transit', 'delivered'].includes(shipment.value?.status))
const deliveredDone = computed(() => shipment.value?.status === 'delivered')
const inMotion = computed(() => shipment.value?.status === 'in_transit')

const slaLine = computed(() => {
  if (!shipment.value?.sla_due_at) return ''
  return t('cargo_detail.sla_due_line', { at: fmt(shipment.value.sla_due_at) })
})

const slaBreached = computed(() => {
  if (!shipment.value?.sla_due_at || shipment.value?.status === 'delivered') return false
  return new Date(shipment.value.sla_due_at).getTime() < Date.now()
})

const canUploadPod = computed(() => auth.hasPermission('cargo.manage'))

function fmt(v) {
  return v ? new Date(v).toLocaleString(locale.value === 'en' ? 'en-GB' : 'vi-VN') : ''
}

function driverInitials(name) {
  if (!name) return '?'
  const p = String(name).trim().split(/\s+/)
  if (p.length === 1) return p[0].slice(0, 2).toUpperCase()
  return (p[0][0] + p[p.length - 1][0]).toUpperCase()
}

function formatBytes(n) {
  if (n == null || n <= 0) return '—'
  if (n < 1024) return `${n} B`
  if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`
  return `${(n / (1024 * 1024)).toFixed(1)} MB`
}

function formatMoney(amount, currency) {
  const a = Number(amount)
  if (!Number.isFinite(a)) return '—'
  const cur = (currency || 'VND').toUpperCase()
  try {
    return new Intl.NumberFormat(locale.value === 'en' ? 'en-US' : 'vi-VN', {
      style: 'currency',
      currency: cur === 'VND' ? 'VND' : cur,
      maximumFractionDigits: cur === 'VND' ? 0 : 2,
    }).format(a)
  } catch {
    return `${a} ${cur}`
  }
}

const costsTotal = computed(() => costItems.value.reduce((s, c) => s + (Number(c.amount) || 0), 0))

function timelineTitle(ev) {
  if (ev.kind === 'milestone') {
    const key = `labels.cargo_timeline.${ev.code}`
    return te(key) ? t(key) : ev.code
  }
  const key = `labels.cargo_audit.${ev.code}`
  return te(key) ? t(key) : ev.code
}

function timelineSubtitle(ev) {
  if (ev.kind === 'milestone' && ev.detail) return ev.detail
  if (ev.kind === 'audit' && ev.code === 'cargo.status_change' && ev.after?.status) {
    const st = ev.after.status
    const key = `labels.cargo_status.${st}`
    return te(key) ? t(key) : st
  }
  return ''
}

function setDocumentTitle() {
  if (typeof document === 'undefined') return
  const code = displayCode.value
  const appTitle = i18n.global.t('app.title')
  if (code) {
    document.title = `${t('cargo_detail.document_title', { code })} · ${appTitle}`
  }
}

async function loadCosts(tripId) {
  costsLoading.value = true
  costsForbidden.value = false
  costItems.value = []
  try {
    const data = await listCostsForTrip(tripId, { per_page: 100, page: 1 })
    costItems.value = data.items ?? []
  } catch (e) {
    const status = e?.response?.status
    if (status === 403) {
      costsForbidden.value = true
    }
  } finally {
    costsLoading.value = false
  }
}

async function reloadShipment() {
  const id = route.params.id
  const data = await getCargoShipment(id)
  shipment.value = data
}

async function load() {
  loading.value = true
  loadError.value = ''
  shipment.value = null
  timelineItems.value = []
  try {
    await reloadShipment()
    const id = route.params.id
    timelineLoading.value = true
    getCargoShipmentTimeline(id)
      .then((d) => {
        timelineItems.value = d.items ?? []
      })
      .catch(() => {
        timelineItems.value = []
      })
      .finally(() => {
        timelineLoading.value = false
      })

    const tid = shipment.value?.trip_id
    if (tid) {
      loadCosts(tid)
    }
  } catch (e) {
    loadError.value = e?.response?.data?.message || t('cargo_detail.load_error')
  } finally {
    loading.value = false
  }
}

watch(
  () => [shipment.value?.tracking_code, shipment.value?.id],
  () => setDocumentTitle(),
  { flush: 'post' },
)

onMounted(load)

watch(
  () => route.params.id,
  () => load(),
)
</script>
