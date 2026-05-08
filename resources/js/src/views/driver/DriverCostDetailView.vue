<template>
  <div class="min-h-full w-full overflow-x-hidden bg-driver-bg pb-[calc(7rem+env(safe-area-inset-bottom))] text-driver-ink">
    <!-- Top nav -->
    <header
      class="sticky top-0 z-40 border-b border-white/[0.06] bg-driver-bg/90 backdrop-blur-md supports-[backdrop-filter]:bg-driver-bg/75"
      :style="{ paddingTop: 'max(0.5rem, env(safe-area-inset-top))' }"
    >
      <div class="mx-auto flex max-w-lg items-center gap-2 px-3 pb-3 pt-2 sm:max-w-2xl">
        <button
          type="button"
          class="flex min-h-[48px] min-w-[48px] shrink-0 items-center justify-center rounded-2xl bg-driver-card ring-1 ring-white/[0.08] transition active:scale-[0.97]"
          :aria-label="t('driver_cost_req.back')"
          @click="goBack"
        >
          <ArrowLeftIcon class="h-6 w-6 text-driver-accent" />
        </button>
        <h1 class="min-w-0 flex-1 truncate text-center text-lg font-bold sm:text-xl">
          {{ t('driver_cost_req.title') }}
        </h1>
        <span class="w-[48px] shrink-0" aria-hidden="true" />
      </div>
    </header>

    <!-- Compact sticky summary (scroll) -->
    <Transition name="fade">
      <div
        v-if="cost && !heroVisible"
        class="fixed left-0 right-0 z-[38] border-b border-white/[0.06] bg-driver-surface/95 px-4 py-3 backdrop-blur-md"
        :style="{ top: 'calc(3.5rem + env(safe-area-inset-top))' }"
      >
        <div class="mx-auto flex max-w-lg items-center justify-between gap-3 sm:max-w-2xl">
          <p class="text-lg font-bold tabular-nums text-driver-ink">{{ formatVnd(cost.amount) }}</p>
          <span class="rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wide" :class="badgeCls(cost.status)">
            {{ badgeTitle(cost.status) }}
          </span>
        </div>
      </div>
    </Transition>

    <div class="mx-auto max-w-lg px-4 pb-8 pt-4 sm:max-w-2xl">
      <p
        v-if="loadError"
        class="mb-4 rounded-2xl border border-amber-500/35 bg-amber-950/40 px-4 py-3 text-sm text-amber-100 ring-1 ring-amber-500/25"
      >
        {{ loadError }}
      </p>

      <div v-if="loading" class="space-y-4">
        <div class="h-44 animate-pulse rounded-[1.35rem] bg-driver-card ring-1 ring-white/[0.06]" />
        <div class="h-36 animate-pulse rounded-[1.35rem] bg-driver-card ring-1 ring-white/[0.06]" />
        <div class="h-48 animate-pulse rounded-[1.35rem] bg-driver-card ring-1 ring-white/[0.06]" />
      </div>

      <template v-else-if="cost">
        <!-- Hero -->
        <div ref="heroRef" class="relative isolate mb-6 overflow-hidden rounded-[1.5rem] bg-driver-card px-5 pb-6 pt-6 shadow-[0_18px_50px_-24px_rgba(127,220,200,0.35)] ring-1 ring-driver-accent/20">
          <div class="pointer-events-none absolute -right-8 -top-12 h-40 w-40 rounded-full bg-driver-accent/[0.08] blur-3xl" aria-hidden="true" />
          <div class="relative flex flex-wrap items-start justify-between gap-3">
            <span class="rounded-full px-3.5 py-1.5 text-xs font-bold uppercase tracking-wide sm:text-[13px]" :class="badgeCls(cost.status)">
              {{ badgeTitle(cost.status) }}
            </span>
            <span class="text-sm tabular-nums text-driver-muted">#{{ cost.trip_id }}</span>
          </div>
          <div class="relative mt-5 flex items-start gap-4">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-[1.1rem] bg-driver-surface ring-1 ring-white/[0.06]" aria-hidden="true">
              <FuelIcon v-if="normType(cost.type) === 'fuel'" class="h-9 w-9 text-driver-accent" />
              <TollIcon v-else-if="normType(cost.type) === 'toll'" class="h-9 w-9 text-driver-accent" />
              <ParkingIcon v-else-if="normType(cost.type) === 'parking'" class="h-9 w-9 text-driver-accent" />
              <MoneyIcon v-else class="h-9 w-9 text-driver-accent" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold uppercase tracking-wide text-driver-muted">{{ typeLabelUi(cost.type) }}</p>
              <p class="mt-2 text-[clamp(1.65rem,5vw,2.35rem)] font-bold tabular-nums leading-none tracking-tight text-driver-ink">
                {{ formatVnd(cost.amount) }}
              </p>
              <p class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-base text-driver-muted">
                <span>{{ dateLabel }}</span>
                <span aria-hidden="true" class="text-driver-muted/35">·</span>
                <span>{{ timeLabel }}</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Quick trip -->
        <details open class="mb-4 overflow-hidden rounded-[1.35rem] bg-driver-card ring-1 ring-white/[0.06]">
          <summary
            class="flex cursor-pointer list-none items-center gap-3 px-4 py-4 text-lg font-bold text-driver-ink outline-none transition hover:bg-white/[0.03] [&::-webkit-details-marker]:hidden"
          >
            <MapIcon class="h-6 w-6 shrink-0 text-driver-accent" aria-hidden="true" />
            <span class="min-w-0 flex-1">{{ t('driver_cost_req.sec_trip') }}</span>
            <ChevronDownIcon class="details-chevron h-5 w-5 shrink-0 text-driver-muted transition-transform duration-200" />
          </summary>
          <div class="border-t border-white/[0.06] px-4 pb-5 pt-4">
            <RouterLink
              v-if="cost.trip_id"
              :to="`/driver/trips/${cost.trip_id}`"
              class="flex min-h-[52px] items-center gap-4 rounded-2xl bg-driver-surface px-4 py-3 ring-1 ring-white/[0.06] transition hover:bg-driver-elevated"
            >
              <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-driver-muted">{{ t('driver_cost_req.trip_label') }}</p>
                <p class="mt-1 text-lg font-bold text-driver-ink">#{{ cost.trip_id }}</p>
                <p class="mt-1 line-clamp-2 text-base text-driver-muted">{{ tripRouteLine }}</p>
              </div>
              <ChevronRightIcon class="h-6 w-6 shrink-0 text-driver-muted/45" />
            </RouterLink>
          </div>
        </details>

        <!-- Cost breakdown -->
        <details open class="mb-4 overflow-hidden rounded-[1.35rem] bg-driver-card ring-1 ring-white/[0.06]">
          <summary
            class="flex cursor-pointer list-none items-center gap-3 px-5 py-4 text-lg font-bold text-driver-ink outline-none transition hover:bg-white/[0.03] [&::-webkit-details-marker]:hidden"
          >
            <MoneyIcon class="h-6 w-6 shrink-0 text-driver-accent" aria-hidden="true" />
            <span class="flex-1">{{ t('driver_cost_req.sec_cost') }}</span>
            <ChevronDownIcon class="details-chevron h-5 w-5 shrink-0 text-driver-muted transition-transform duration-200" />
          </summary>
          <div class="border-t border-white/[0.06] px-5 pb-5 pt-4 text-base leading-relaxed">
            <dl class="grid gap-4 sm:grid-cols-2">
              <div>
                <dt class="text-sm font-medium text-driver-muted">{{ t('driver_cost_req.cost_type_label') }}</dt>
                <dd class="mt-1 text-lg font-semibold text-driver-ink">{{ typeLabelUi(cost.type) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-driver-muted">{{ t('driver_cost_req.amount_label') }}</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums text-driver-ink">{{ formatVnd(cost.amount) }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-driver-muted">{{ t('driver_cost_req.date_label') }}</dt>
                <dd class="mt-1 font-semibold text-driver-ink">{{ dateLabel }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-driver-muted">{{ t('driver_cost_req.time_label') }}</dt>
                <dd class="mt-1 font-semibold text-driver-ink">{{ timeLabel }}</dd>
              </div>
            </dl>
          </div>
        </details>

        <!-- Receipts -->
        <div class="mb-4 overflow-hidden rounded-[1.35rem] bg-driver-card p-4 ring-1 ring-white/[0.06]">
          <DriverReceiptGallery
            :trip-id="Number(cost.trip_id)"
            :cost-id="Number(cost.id)"
            :attachments="attachmentList"
            :legacy-receipt-url="legacyReceiptUrl"
            :readonly="galleryReadonly"
            :can-mutate="galleryMutable"
            :title="t('driver_cost_req.images_title')"
            :empty-text="t('driver_cost_req.no_images')"
            :hint-mutate="t('driver_cost_req.gallery_hint')"
            :add-photos="t('driver_cost_req.gallery_add')"
            :camera-capture="t('driver_cost_req.gallery_camera')"
            :preview-verb="t('driver_cost_req.gallery_preview')"
            :preview-alt="t('driver_cost_req.gallery_preview')"
            :remove-aria="t('driver_cost_req.gallery_remove')"
            :confirm-remove-title="t('driver_cost_req.gallery_remove_title')"
            :confirm-remove-msg="t('driver_cost_req.gallery_remove_msg')"
            :upload-fail="t('driver_cost_req.gallery_upload_fail')"
            :delete-fail-msg="t('driver_cost_req.gallery_delete_fail')"
            :retry-label="t('driver_cost_req.gallery_retry')"
            :uploading-label="t('driver_cost_req.gallery_uploading')"
            :delete-confirm-label="t('driver_cost_req.gallery_delete_confirm')"
            :delete-cancel-label="t('driver_trip_detail.cancel')"
            :move-up="t('driver_cost_req.gallery_move_up')"
            :move-down="t('driver_cost_req.gallery_move_down')"
            @updated="onGalleryUpdated"
          />
        </div>

        <!-- Notes -->
        <details v-if="cost.description" class="mb-4 overflow-hidden rounded-[1.35rem] bg-driver-card ring-1 ring-white/[0.06]">
          <summary
            class="flex cursor-pointer list-none items-center gap-3 px-5 py-4 text-lg font-bold text-driver-ink outline-none transition hover:bg-white/[0.03] [&::-webkit-details-marker]:hidden"
          >
            <DocumentTextIcon class="h-6 w-6 shrink-0 text-driver-accent" aria-hidden="true" />
            <span class="flex-1">{{ t('driver_cost_req.notes_title') }}</span>
            <ChevronDownIcon class="details-chevron h-5 w-5 shrink-0 text-driver-muted transition-transform duration-200" />
          </summary>
          <div class="border-t border-white/[0.06] px-5 pb-5 pt-4">
            <p class="rounded-2xl bg-driver-surface/90 px-4 py-4 text-base leading-relaxed text-driver-ink ring-1 ring-white/[0.05]">
              {{ cost.description }}
            </p>
          </div>
        </details>

        <!-- Timeline -->
        <details open class="mb-6 overflow-hidden rounded-[1.35rem] bg-driver-card ring-1 ring-white/[0.06]">
          <summary
            class="flex cursor-pointer list-none items-center gap-3 px-5 py-4 text-lg font-bold text-driver-ink outline-none transition hover:bg-white/[0.03] [&::-webkit-details-marker]:hidden"
          >
            <ClockIcon class="h-6 w-6 shrink-0 text-driver-accent" aria-hidden="true" />
            <span class="flex-1">{{ t('driver_cost_req.sec_timeline') }}</span>
            <ChevronDownIcon class="details-chevron h-5 w-5 shrink-0 text-driver-muted transition-transform duration-200" />
          </summary>
          <div class="border-t border-white/[0.06] px-5 pb-5 pt-4">
            <ol class="relative space-y-6 border-l-2 border-driver-accent/25 pl-6">
              <li v-for="row in timelineRows" :key="row.key" class="relative">
                <span class="absolute -left-[1.4rem] top-1 flex h-4 w-4 items-center justify-center rounded-full bg-driver-accent shadow-[0_0_0_4px_#020B0B]" aria-hidden="true" />
                <p class="text-base font-semibold text-driver-ink">{{ row.title }}</p>
                <p class="mt-1 text-sm tabular-nums text-driver-muted">{{ row.when }}</p>
                <p v-if="row.detail" class="mt-2 text-base leading-snug text-driver-muted">{{ row.detail }}</p>
              </li>
            </ol>
          </div>
        </details>
      </template>
    </div>

    <!-- Bottom actions -->
    <div
      v-if="cost && canAct"
      class="fixed bottom-[calc(var(--driver-bottom-nav-height,3.5rem)+env(safe-area-inset-bottom))] left-0 right-0 z-[36] border-t border-white/[0.07] bg-driver-bg/95 px-4 py-4 backdrop-blur-md sm:static sm:z-auto sm:border-0 sm:bg-transparent sm:px-0 sm:backdrop-blur-none"
    >
      <div class="mx-auto flex max-w-lg gap-3 sm:max-w-2xl">
        <button
          type="button"
          class="flex min-h-[52px] flex-1 items-center justify-center gap-2 rounded-2xl bg-rose-500/15 py-3 text-base font-bold text-rose-200 ring-1 ring-rose-400/35 transition hover:bg-rose-500/25 disabled:opacity-45"
          :disabled="actionBusy"
          @click="onCancel"
        >
          <TrashIcon class="h-6 w-6 shrink-0" />
          {{ t('driver_cost_req.btn_cancel') }}
        </button>
        <button
          type="button"
          class="flex min-h-[52px] flex-1 items-center justify-center gap-2 rounded-2xl bg-driver-accent py-3 text-base font-bold text-driver-bg shadow-[0_12px_36px_-14px_rgba(127,220,200,0.55)] transition hover:brightness-110 disabled:opacity-45 active:scale-[0.99]"
          :disabled="actionBusy"
          @click="openEdit"
        >
          <PencilSquareIcon class="h-6 w-6 shrink-0" />
          {{ t('driver_cost_req.btn_edit') }}
        </button>
      </div>
    </div>

    <!-- Edit bottom sheet -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="editOpen"
          class="fixed inset-0 z-[55] flex flex-col justify-end bg-black/55 sm:items-center sm:justify-center sm:p-6"
          @click.self="closeEditSheet"
        >
          <div
            class="max-h-[min(92dvh,900px)] w-full max-w-lg overflow-y-auto rounded-t-[1.75rem] bg-driver-card px-4 pb-[max(1.25rem,env(safe-area-inset-bottom))] pt-5 shadow-2xl ring-1 ring-white/[0.08] sm:rounded-[1.75rem]"
            @click.stop
          >
            <div class="mx-auto mb-4 h-1.5 w-12 shrink-0 rounded-full bg-white/15 sm:hidden" aria-hidden="true" />
            <h3 class="text-xl font-bold text-driver-ink">{{ t('driver_cost_req.edit_title') }}</h3>
            <p v-if="draftSavedHint" class="mt-2 text-sm text-driver-accent">{{ draftSavedHint }}</p>

            <p class="mt-5 text-sm font-semibold text-driver-muted">{{ t('driver_trip_detail.cost_type') }}</p>
            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
              <button
                v-for="opt in editTypes"
                :key="opt.value"
                type="button"
                class="min-h-[48px] rounded-2xl px-2 text-sm font-bold transition ring-1 sm:text-base"
                :class="
                  editForm.type === opt.value
                    ? 'bg-driver-accent text-driver-bg ring-driver-accent'
                    : 'bg-driver-surface text-driver-ink ring-white/[0.08] hover:bg-driver-elevated'
                "
                @click="editForm.type = opt.value"
              >
                {{ typeLabelUi(opt.value) }}
              </button>
            </div>

            <label class="mt-6 block text-sm font-semibold text-driver-muted" for="dcf-amt">{{ t('driver_trip_detail.cost_amount') }}</label>
            <input
              id="dcf-amt"
              v-model="editForm.amount"
              type="text"
              inputmode="numeric"
              autocomplete="transaction-amount"
              class="mt-2 min-h-[52px] w-full rounded-2xl border-0 bg-driver-surface px-4 text-lg font-semibold text-driver-ink ring-1 ring-white/[0.08] placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/80"
              :placeholder="t('driver_cost_req.ph_amount')"
              @blur="touch.amount = true"
            />
            <p v-if="amountErr && touch.amount" class="mt-2 text-sm font-medium text-rose-300">{{ amountErr }}</p>

            <label class="mt-5 block text-sm font-semibold text-driver-muted" for="dcf-desc">{{ t('driver_trip_detail.cost_desc') }}</label>
            <input
              id="dcf-desc"
              v-model="editForm.description"
              type="text"
              class="mt-2 min-h-[52px] w-full rounded-2xl border-0 bg-driver-surface px-4 text-base text-driver-ink ring-1 ring-white/[0.08] placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/80"
              :placeholder="t('driver_cost_req.ph_desc')"
            />

            <p v-if="editError" class="mt-4 rounded-xl bg-rose-500/15 px-4 py-3 text-sm font-medium text-rose-200 ring-1 ring-rose-400/25">
              {{ editError }}
            </p>

            <div class="mt-8 flex gap-3 pb-2">
              <button
                type="button"
                class="flex min-h-[52px] flex-1 items-center justify-center rounded-2xl bg-driver-surface py-3 text-base font-bold text-driver-ink ring-1 ring-white/10 transition hover:bg-driver-elevated"
                @click="closeEditSheet"
              >
                {{ t('driver_trip_detail.cancel') }}
              </button>
              <button
                type="button"
                class="flex min-h-[52px] flex-1 items-center justify-center rounded-2xl bg-driver-accent py-3 text-base font-bold text-driver-bg transition hover:brightness-110 disabled:opacity-45"
                :disabled="editBusy || !!amountErr"
                @click="confirmSaveEdit"
              >
                {{ editBusy ? '…' : t('driver_trip_detail.confirm') }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import {
  ArrowLeftIcon,
  BanknotesIcon as MoneyIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  ClockIcon,
  DocumentTextIcon,
  FireIcon as FuelIcon,
  MapIcon,
  MapPinIcon as ParkingIcon,
  PencilSquareIcon,
  RectangleStackIcon as TollIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import DriverReceiptGallery from '../../components/driver/costs/DriverReceiptGallery.vue'
import { confirmAction } from '../../composables/useConfirm'
import { showAppSuccess } from '../../composables/appMessage'
import { deleteTripCost, getTripCost, updateTripCost } from '../../api/costs'
import { formatVnd } from '../../util/labels'

const { t, te } = useI18n()
const route = useRoute()
const router = useRouter()

const loading = ref(true)
const loadError = ref('')
const cost = ref(null)
const actionBusy = ref(false)
const editOpen = ref(false)
const editBusy = ref(false)
const editError = ref('')
const editForm = ref({ type: 'toll', amount: '', description: '' })
const touch = ref({ amount: false })
const heroRef = ref(null)
const heroVisible = ref(true)
const draftSavedHint = ref('')

const editTypes = [
  { value: 'fuel' },
  { value: 'toll' },
  { value: 'parking' },
  { value: 'other' },
]

let heroIo = null
let draftDebounceTimer = null
let draftHintTimer = null

const id = computed(() => {
  const n = Number(route.params.id)
  return Number.isFinite(n) && n > 0 ? n : null
})

const canAct = computed(() => cost.value && ['draft', 'submitted'].includes(cost.value.status))

const galleryMutable = computed(() => ['draft', 'submitted'].includes(cost.value?.status ?? ''))

const galleryReadonly = computed(() => !galleryMutable.value)

const dr = computed(() => cost.value?.trip?.dispatch_request)

const attachmentList = computed(() => {
  const raw = cost.value?.attachments
  if (!Array.isArray(raw)) return []
  return raw.map((a) => ({ id: a.id, url: a.url })).filter((x) => x.url)
})

const legacyReceiptUrl = computed(() => {
  const u = cost.value?.receipt_url
  return typeof u === 'string' && /^https?:/i.test(u) ? u : ''
})

function normType(t) {
  return String(t ?? '')
    .trim()
    .toLowerCase()
}

function badgeCls(s) {
  if (s === 'confirmed') return 'bg-emerald-500/18 text-emerald-200 ring-1 ring-emerald-400/25'
  if (s === 'rejected') return 'bg-rose-500/18 text-rose-200 ring-1 ring-rose-400/28'
  if (s === 'submitted') return 'bg-amber-500/18 text-amber-100 ring-1 ring-amber-400/28'
  return 'bg-white/[0.08] text-driver-muted ring-1 ring-white/12'
}

function badgeTitle(s) {
  if (s === 'confirmed') return t('driver_cost_req.badge_done')
  if (s === 'rejected') return t('driver_cost_req.badge_rejected')
  if (s === 'submitted') return t('driver_cost_req.badge_pending')
  return t('driver_cost_req.badge_draft')
}

function typeLabelUi(type) {
  const k = `driver_trip_detail.cost_type_${String(type || 'other')}`
  if (te(k)) return t(k)
  return type
}

const dateLabel = computed(() => {
  if (!cost.value?.created_at) return '—'
  const d = new Date(cost.value.created_at)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
})

const timeLabel = computed(() => {
  if (!cost.value?.created_at) return '—'
  const d = new Date(cost.value.created_at)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
})

const tripRouteLine = computed(() => {
  const o = (dr.value?.origin || '').trim()
  const dest = (dr.value?.destination || '').trim()
  if (o && dest) return `${o} → ${dest}`
  return o || dest || '—'
})

function fmtWhen(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return '—'
  return d.toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const timelineRows = computed(() => {
  const c = cost.value
  if (!c) return []
  const rows = []
  rows.push({
    key: 'created',
    title: t('driver_cost_req.tl_created'),
    when: fmtWhen(c.created_at),
    detail: '',
  })
  if (c.status !== 'draft') {
    rows.push({
      key: 'queue',
      title: t('driver_cost_req.tl_pending'),
      when: fmtWhen(c.created_at),
      detail: t('driver_cost_req.tl_pending_hint'),
    })
  }
  if (c.status === 'confirmed') {
    rows.push({
      key: 'ok',
      title: t('driver_cost_req.tl_approved'),
      when: fmtWhen(c.confirmed_at || c.updated_at),
      detail: c.confirmer?.name ? t('driver_cost_req.tl_by', { name: c.confirmer.name }) : '',
    })
  }
  if (c.status === 'rejected') {
    rows.push({
      key: 'no',
      title: t('driver_cost_req.tl_rejected'),
      when: fmtWhen(c.confirmed_at || c.updated_at),
      detail: c.rejection_reason || t('driver_cost_req.tl_no_reason'),
    })
  }
  return rows
})

const amountErr = computed(() => {
  const raw = String(editForm.value.amount ?? '').replace(/\D/g, '')
  const num = raw === '' ? NaN : parseInt(raw, 10)
  if (!Number.isFinite(num) || num < 0) return t('driver_trip_detail.cost_err_amount')
  return ''
})

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push({ name: 'driverCosts' })
}

async function load() {
  if (id.value == null) {
    loadError.value = t('driver_trip_detail.err_bad_id')
    loading.value = false
    return
  }
  loading.value = true
  loadError.value = ''
  try {
    cost.value = await getTripCost(id.value)
  } catch {
    loadError.value = t('driver_home.load_error')
    cost.value = null
  } finally {
    loading.value = false
  }
}

function draftStorageKey() {
  return id.value != null ? `driver-cost-draft:${id.value}` : null
}

function persistDraft() {
  const key = draftStorageKey()
  if (!key || !editOpen.value) return
  try {
    localStorage.setItem(key, JSON.stringify(editForm.value))
    draftSavedHint.value = t('driver_cost_req.draft_saved')
    window.clearTimeout(draftHintTimer)
    draftHintTimer = window.setTimeout(() => {
      draftSavedHint.value = ''
    }, 2200)
  } catch {
    /* ignore */
  }
}

watch(
  editForm,
  () => {
    if (!editOpen.value) return
    window.clearTimeout(draftDebounceTimer)
    draftDebounceTimer = window.setTimeout(persistDraft, 650)
  },
  { deep: true },
)

function openEdit() {
  const c = cost.value
  if (!c) return
  editError.value = ''
  touch.value.amount = false
  editForm.value = {
    type: c.type || 'other',
    amount: String(Math.round(Number(c.amount) || 0)),
    description: c.description || '',
  }
  const key = draftStorageKey()
  if (key) {
    try {
      const raw = localStorage.getItem(key)
      if (raw) {
        const parsed = JSON.parse(raw)
        if (parsed && typeof parsed === 'object') {
          editForm.value = {
            type: parsed.type || editForm.value.type,
            amount: parsed.amount != null ? String(parsed.amount) : editForm.value.amount,
            description: parsed.description ?? editForm.value.description,
          }
        }
      }
    } catch {
      /* ignore */
    }
  }
  editOpen.value = true
  nextTick(() => {
    const el = document.getElementById('dcf-amt')
    el?.focus?.()
  })
}

function closeEditSheet() {
  editOpen.value = false
}

async function confirmSaveEdit() {
  touch.value.amount = true
  if (amountErr.value || id.value == null || editBusy.value) return
  const ok = await confirmAction({
    title: t('driver_cost_req.save_confirm_title'),
    message: t('driver_cost_req.save_confirm_msg'),
    confirmLabel: t('driver_trip_detail.confirm'),
    cancelLabel: t('driver_trip_detail.cancel'),
  })
  if (!ok) return
  await saveEdit()
}

async function saveEdit() {
  if (id.value == null || editBusy.value) return
  const a = String(editForm.value.amount || '').replace(/\D/g, '')
  const num = a === '' ? NaN : parseInt(a, 10)
  if (!Number.isFinite(num) || num < 0) {
    editError.value = t('driver_trip_detail.cost_err_amount')
    return
  }
  editBusy.value = true
  editError.value = ''
  try {
    await updateTripCost(id.value, {
      type: editForm.value.type,
      amount: num,
      description: editForm.value.description?.trim() || null,
      currency: 'VND',
    })
    try {
      const key = draftStorageKey()
      if (key) localStorage.removeItem(key)
    } catch {
      /* ignore */
    }
    editOpen.value = false
    showAppSuccess(t('driver_cost_req.save_ok'), t('driver_cost_req.save_ok_title'))
    await load()
  } catch {
    editError.value = t('driver_trip_detail.cost_err_submit')
  } finally {
    editBusy.value = false
  }
}

async function onCancel() {
  if (id.value == null || !canAct.value) return
  const ok = await confirmAction({
    title: t('driver_cost_req.cancel_confirm_title'),
    message: t('driver_cost_req.cancel_confirm'),
    danger: true,
    confirmLabel: t('driver_cost_req.btn_cancel'),
    cancelLabel: t('driver_trip_detail.cancel'),
  })
  if (!ok) return
  actionBusy.value = true
  try {
    await deleteTripCost(id.value)
    goBack()
  } catch {
    loadError.value = t('driver_cost_req.cancel_err')
  } finally {
    actionBusy.value = false
  }
}

async function onGalleryUpdated() {
  await load()
}

onMounted(() => {
  void load()
  heroIo = new IntersectionObserver(
    ([e]) => {
      heroVisible.value = e?.isIntersecting ?? true
    },
    { threshold: 0.35 },
  )
  if (heroRef.value) heroIo.observe(heroRef.value)
})

watch(heroRef, (el, prev) => {
  if (!heroIo) return
  if (prev) heroIo.unobserve(prev)
  if (el) heroIo.observe(el)
})

watch(
  () => route.params.id,
  () => {
    void load()
  },
)

onUnmounted(() => {
  if (heroIo) heroIo.disconnect()
  window.clearTimeout(draftDebounceTimer)
  window.clearTimeout(draftHintTimer)
})
</script>

<style scoped>
details[open] > summary .details-chevron {
  transform: rotate(180deg);
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.18s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
