<template>
  <div class="min-h-full w-full overflow-x-hidden bg-driver-bg pb-[calc(7rem+env(safe-area-inset-bottom))] text-driver-ink">
    <header
      class="sticky top-0 z-40 border-b border-white/[0.06] bg-driver-bg/90 backdrop-blur-md"
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
          {{ t('driver_costs.create_title') }}
        </h1>
        <span class="w-[48px] shrink-0" aria-hidden="true" />
      </div>
    </header>

    <div class="mx-auto max-w-lg px-4 pb-8 pt-4 sm:max-w-2xl">
      <!-- Hero -->
      <div class="relative overflow-hidden rounded-[1.35rem] bg-driver-card px-4 py-4 ring-1 ring-driver-accent/20">
        <div class="pointer-events-none absolute -right-6 -top-8 h-28 w-28 rounded-full bg-driver-accent/[0.07] blur-2xl" aria-hidden="true" />
        <div class="relative flex gap-3">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-driver-accent/15 ring-1 ring-driver-accent/30">
            <BanknotesIcon class="h-6 w-6 text-driver-accent" aria-hidden="true" />
          </div>
          <div class="min-w-0">
            <p class="text-base font-bold text-driver-ink">{{ t('driver_costs.create_hero_title') }}</p>
            <p class="mt-1 text-sm leading-relaxed text-driver-muted">{{ t('driver_costs.create_hero_body') }}</p>
          </div>
        </div>
      </div>

      <!-- Link mode -->
      <div
        class="mt-5 grid grid-cols-2 gap-2 rounded-2xl bg-driver-surface/80 p-1 ring-1 ring-white/[0.06]"
        role="tablist"
        :aria-label="t('driver_costs.link_mode_aria')"
      >
        <button
          type="button"
          role="tab"
          :aria-selected="linkMode === 'trip'"
          class="min-h-[48px] rounded-xl px-3 text-sm font-bold transition sm:text-base"
          :class="linkMode === 'trip' ? 'bg-driver-accent text-driver-bg shadow-sm' : 'text-driver-muted hover:text-driver-ink'"
          @click="setLinkMode('trip')"
        >
          {{ t('driver_costs.link_mode_trip') }}
        </button>
        <button
          type="button"
          role="tab"
          :aria-selected="linkMode === 'none'"
          class="min-h-[48px] rounded-xl px-3 text-sm font-bold transition sm:text-base"
          :class="linkMode === 'none' ? 'bg-driver-accent text-driver-bg shadow-sm' : 'text-driver-muted hover:text-driver-ink'"
          @click="setLinkMode('none')"
        >
          {{ t('driver_costs.link_mode_none') }}
        </button>
      </div>

      <div class="mt-6 space-y-6">
        <!-- Step 1: Trip -->
        <section v-if="linkMode === 'trip'" class="rounded-[1.35rem] bg-driver-card p-4 ring-1 ring-white/[0.06] sm:p-5">
          <div class="flex items-start gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-driver-accent/15 text-sm font-bold text-driver-accent">1</span>
            <div class="min-w-0 flex-1">
              <h2 class="text-base font-bold text-driver-ink sm:text-lg">{{ t('driver_costs.step_trip_title') }}</h2>
              <p class="mt-1 text-sm text-driver-muted">{{ t('driver_costs.step_trip_hint') }}</p>
            </div>
          </div>

          <div v-if="selectedTrip" class="mt-4 flex items-start gap-3 rounded-2xl bg-driver-surface px-4 py-3 ring-2 ring-driver-accent/35">
            <MapPinIcon class="mt-0.5 h-5 w-5 shrink-0 text-driver-accent" aria-hidden="true" />
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-bold text-driver-ink">#{{ selectedTrip.id }}</span>
                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="tripStatusPillClass(selectedTrip.status)">
                  {{ tripStatusLabel(selectedTrip.status) }}
                </span>
              </div>
              <p class="mt-1 line-clamp-2 text-sm text-driver-muted">{{ tripLabel(selectedTrip) }}</p>
              <p v-if="selectedTripDepart" class="mt-1 text-xs text-driver-muted/80">{{ selectedTripDepart }}</p>
            </div>
            <button
              type="button"
              class="shrink-0 rounded-xl px-2 py-1 text-xs font-semibold text-driver-accent underline-offset-2 hover:underline"
              @click="form.trip_id = ''"
            >
              {{ t('driver_costs.trip_change') }}
            </button>
          </div>

          <template v-else>
            <label class="sr-only" for="dcc-trip-search">{{ t('driver_costs.trip_search') }}</label>
            <input
              id="dcc-trip-search"
              v-model="tripSearch"
              type="search"
              autocomplete="off"
              class="mt-4 flex min-h-[48px] w-full rounded-2xl border border-white/10 bg-driver-surface px-4 py-3 text-base text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
              :placeholder="t('driver_costs.trip_search_ph')"
            />

            <p v-if="tripsLoading" class="mt-3 text-sm text-driver-muted">{{ t('driver_costs.trips_loading') }}</p>
            <p v-else-if="!filteredTrips.length" class="mt-3 rounded-xl bg-driver-surface/60 px-3 py-4 text-center text-sm text-driver-muted">
              {{ t('driver_costs.trips_empty') }}
            </p>

            <div v-else class="mt-3 max-h-[min(42vh,320px)] space-y-4 overflow-y-auto overscroll-contain pr-0.5">
              <div v-if="completedTrips.length">
                <p class="px-1 text-xs font-semibold uppercase tracking-wide text-driver-muted">{{ t('driver_costs.trip_group_completed') }}</p>
                <ul class="mt-2 space-y-2">
                  <li v-for="tr in completedTrips" :key="tr.id">
                    <button
                      type="button"
                      class="flex min-h-[56px] w-full items-center gap-3 rounded-2xl border border-white/[0.06] bg-driver-surface px-3 py-2.5 text-left transition active:scale-[0.99] hover:ring-1 hover:ring-driver-accent/25"
                      @click="pickTrip(tr)"
                    >
                      <span class="text-sm font-bold tabular-nums text-driver-ink">#{{ tr.id }}</span>
                      <span class="min-w-0 flex-1">
                        <span class="line-clamp-1 text-sm font-medium text-driver-ink">{{ tripLabel(tr) }}</span>
                        <span class="mt-0.5 block text-xs text-driver-muted">{{ tripDepartShort(tr) }}</span>
                      </span>
                      <ChevronRightIcon class="h-5 w-5 shrink-0 text-driver-muted/50" aria-hidden="true" />
                    </button>
                  </li>
                </ul>
              </div>
              <div v-if="activeTrips.length">
                <p class="px-1 text-xs font-semibold uppercase tracking-wide text-driver-muted">{{ t('driver_costs.trip_group_active') }}</p>
                <ul class="mt-2 space-y-2">
                  <li v-for="tr in activeTrips" :key="tr.id">
                    <button
                      type="button"
                      class="flex min-h-[56px] w-full items-center gap-3 rounded-2xl border border-white/[0.06] bg-driver-surface px-3 py-2.5 text-left transition active:scale-[0.99] hover:ring-1 hover:ring-driver-accent/25"
                      @click="pickTrip(tr)"
                    >
                      <span class="text-sm font-bold tabular-nums text-driver-ink">#{{ tr.id }}</span>
                      <span class="min-w-0 flex-1">
                        <span class="line-clamp-1 text-sm font-medium text-driver-ink">{{ tripLabel(tr) }}</span>
                        <span class="mt-0.5 block text-xs text-driver-muted">{{ tripDepartShort(tr) }}</span>
                      </span>
                      <ChevronRightIcon class="h-5 w-5 shrink-0 text-driver-muted/50" aria-hidden="true" />
                    </button>
                  </li>
                </ul>
              </div>
            </div>
          </template>
        </section>

        <section v-else class="rounded-[1.35rem] bg-driver-card px-4 py-4 ring-1 ring-white/[0.06] sm:px-5">
          <p class="text-sm leading-relaxed text-driver-muted">{{ t('driver_costs.standalone_hint') }}</p>
        </section>

        <!-- Step 2: Type & amount -->
        <section class="rounded-[1.35rem] bg-driver-card p-4 ring-1 ring-white/[0.06] sm:p-5">
          <div class="flex items-start gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-driver-accent/15 text-sm font-bold text-driver-accent">
              {{ linkMode === 'trip' ? '2' : '1' }}
            </span>
            <div>
              <h2 class="text-base font-bold text-driver-ink sm:text-lg">{{ t('driver_costs.step_amount_title') }}</h2>
              <p class="mt-1 text-sm text-driver-muted">{{ t('driver_costs.step_amount_hint') }}</p>
            </div>
          </div>

          <p class="mt-4 text-sm font-semibold text-driver-muted">{{ t('driver_trip_detail.cost_type') }}</p>
          <div class="mt-2 flex flex-wrap gap-2">
            <button
              v-for="ct in costTypes"
              :key="ct.value"
              type="button"
              class="min-h-[44px] rounded-2xl px-4 text-sm font-bold ring-1 transition active:scale-[0.98]"
              :class="
                form.type === ct.value
                  ? 'bg-driver-accent text-driver-bg ring-driver-accent/50'
                  : 'bg-driver-surface text-driver-ink ring-white/10 hover:ring-driver-accent/25'
              "
              @click="form.type = ct.value"
            >
              {{ ct.label }}
            </button>
          </div>

          <label class="mt-4 block text-sm font-semibold text-driver-muted" for="dcc-amt">{{ t('driver_trip_detail.cost_amount') }}</label>
          <div class="relative mt-2">
            <input
              id="dcc-amt"
              :value="amountDisplay"
              type="text"
              inputmode="numeric"
              class="flex min-h-[52px] w-full rounded-2xl border border-white/10 bg-driver-surface py-3 pl-4 pr-14 text-lg font-semibold tabular-nums text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
              :placeholder="t('driver_cost_req.ph_amount')"
              @input="onAmountInput"
            />
            <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-driver-muted">₫</span>
          </div>
          <p v-if="amountPreview" class="mt-1.5 text-sm text-driver-muted">{{ amountPreview }}</p>
        </section>

        <!-- Step 3: Description -->
        <section class="rounded-[1.35rem] bg-driver-card p-4 ring-1 ring-white/[0.06] sm:p-5">
          <div class="flex items-start gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-driver-accent/15 text-sm font-bold text-driver-accent">
              {{ linkMode === 'trip' ? '3' : '2' }}
            </span>
            <div>
              <h2 class="text-base font-bold text-driver-ink sm:text-lg">{{ t('driver_costs.step_desc_title') }}</h2>
              <p class="mt-1 text-sm text-driver-muted">{{ t('driver_costs.step_desc_hint') }}</p>
            </div>
          </div>

          <label class="sr-only" for="dcc-desc">{{ t('driver_trip_detail.cost_desc') }}</label>
          <input
            id="dcc-desc"
            v-model="form.description"
            type="text"
            class="mt-4 flex min-h-[52px] w-full rounded-2xl border border-white/10 bg-driver-surface px-4 py-3 text-base text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
            :placeholder="t('driver_cost_req.ph_desc')"
          />
        </section>

        <p v-if="errorMsg" class="rounded-2xl border border-rose-500/30 bg-rose-950/40 px-4 py-3 text-sm text-rose-200">
          {{ errorMsg }}
        </p>

        <button
          type="button"
          class="flex min-h-[56px] w-full items-center justify-center rounded-2xl bg-driver-accent py-3.5 text-base font-bold text-driver-bg shadow-[0_12px_36px_-14px_rgba(127,220,200,0.55)] transition hover:brightness-110 disabled:opacity-45 active:scale-[0.99]"
          :disabled="saving || !canSubmit"
          @click="submit"
        >
          {{ saving ? t('driver_costs.create_saving') : t('driver_costs.create_submit') }}
        </button>
        <p class="text-center text-xs leading-relaxed text-driver-muted/80">{{ t('driver_costs.create_receipt_hint') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeftIcon,
  BanknotesIcon,
  ChevronRightIcon,
  MapPinIcon,
} from '@heroicons/vue/24/outline'
import { listDriverTrips } from '../../api/driver'
import { submitStandaloneTripCost } from '../../api/costs'
import { isTripEligibleForDriverLinkedCost } from '../../constants/tripStatus'
import { tripTimelineRouteLine } from '../../composables/useDriverTripDisplay'
import { toLocalDateKey } from '../../util/dates'
import { formatVndCurrency, formatVndWhileTyping } from '../../util/money'

const { t, locale } = useI18n()
const router = useRouter()
const route = useRoute()

const linkMode = ref('trip')

const form = ref({
  trip_id: '',
  type: 'toll',
  amount: '',
  description: '',
})

const saving = ref(false)
const errorMsg = ref('')
const tripsLoading = ref(false)
const tripSearch = ref('')
/** @type {import('vue').Ref<object[]>} */
const tripOptions = ref([])

const costTypes = computed(() => [
  { value: 'fuel', label: t('driver_trip_detail.cost_type_fuel') },
  { value: 'toll', label: t('driver_trip_detail.cost_type_toll') },
  { value: 'parking', label: t('driver_trip_detail.cost_type_parking') },
  { value: 'other', label: t('driver_trip_detail.cost_type_other') },
])

const amountDisplay = computed(() => formatVndWhileTyping(form.value.amount))

const amountPreview = computed(() => {
  const a = String(form.value.amount || '').replace(/\D/g, '')
  if (!a) return ''
  const num = parseInt(a, 10)
  if (!Number.isFinite(num) || num <= 0) return ''
  return formatVndCurrency(num)
})

const selectedTrip = computed(() => {
  const id = String(form.value.trip_id || '').trim()
  if (!id) return null
  return tripOptions.value.find((tr) => String(tr.id) === id) ?? null
})

const selectedTripDepart = computed(() => {
  const tr = selectedTrip.value
  if (!tr) return ''
  return tripDepartShort(tr)
})

const filteredTrips = computed(() => {
  const q = tripSearch.value.trim().toLowerCase()
  let list = tripOptions.value
  if (q) {
    list = list.filter((tr) => {
      const id = String(tr.id)
      const label = tripLabel(tr).toLowerCase()
      const dep = tripDepartShort(tr).toLowerCase()
      return id.includes(q) || label.includes(q) || dep.includes(q)
    })
  }
  return list
})

const completedTrips = computed(() =>
  filteredTrips.value.filter((tr) => String(tr?.status ?? '').toLowerCase() === 'completed'),
)

const activeTrips = computed(() =>
  filteredTrips.value.filter((tr) => String(tr?.status ?? '').toLowerCase() !== 'completed'),
)

const canSubmit = computed(() => {
  const a = String(form.value.amount || '').replace(/\D/g, '')
  const num = a === '' ? NaN : parseInt(a, 10)
  if (!Number.isFinite(num) || num <= 0) return false
  if (linkMode.value === 'trip' && !String(form.value.trip_id || '').trim()) return false
  return true
})

function tripLabel(tr) {
  return tripTimelineRouteLine(tr, t)
}

function tripDepartShort(tr) {
  const iso = tr?.depart_at || tr?.dispatch_request?.depart_at
  if (!iso) return '—'
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return '—'
  const loc = locale.value === 'vi' ? 'vi-VN' : 'en-US'
  return d.toLocaleString(loc, {
    weekday: 'short',
    day: 'numeric',
    month: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  })
}

function tripStatusLabel(status) {
  const s = String(status ?? '').toLowerCase()
  const map = {
    completed: t('driver_costs.trip_st_completed'),
    in_progress: t('driver_costs.trip_st_in_progress'),
    assigned: t('driver_costs.trip_st_assigned'),
    driver_confirmed: t('driver_costs.trip_st_driver_confirmed'),
    approved: t('driver_costs.trip_st_approved'),
    pending: t('driver_costs.trip_st_pending'),
  }
  return map[s] ?? status ?? '—'
}

function tripStatusPillClass(status) {
  const s = String(status ?? '').toLowerCase()
  if (s === 'completed') return 'bg-emerald-500/15 text-emerald-200 ring-1 ring-emerald-400/25'
  if (s === 'in_progress') return 'bg-teal-500/15 text-teal-100 ring-1 ring-teal-400/25'
  return 'bg-slate-500/15 text-slate-200 ring-1 ring-slate-400/20'
}

function setLinkMode(mode) {
  linkMode.value = mode
  if (mode === 'none') form.value.trip_id = ''
  errorMsg.value = ''
}

function pickTrip(tr) {
  form.value.trip_id = String(tr.id)
  tripSearch.value = ''
  errorMsg.value = ''
}

function onAmountInput(ev) {
  form.value.amount = String(ev.target?.value ?? '').replace(/\D/g, '')
}

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push({ name: 'driverCosts' })
}

function tripPickerDateRange() {
  const now = new Date()
  const past = new Date(now)
  past.setDate(past.getDate() - 30)
  const horizon = new Date(now)
  horizon.setDate(horizon.getDate() + 21)
  return { date_from: toLocalDateKey(past), date_to: toLocalDateKey(horizon) }
}

async function loadTrips() {
  tripsLoading.value = true
  try {
    const res = await listDriverTrips({
      ...tripPickerDateRange(),
      per_page: 100,
      page: 1,
    })
    const items = res?.items ?? []
    tripOptions.value = items
      .filter((tr) => isTripEligibleForDriverLinkedCost(tr?.status))
      .sort((a, b) => {
        const ac = String(a?.status ?? '').toLowerCase() === 'completed' ? 0 : 1
        const bc = String(b?.status ?? '').toLowerCase() === 'completed' ? 0 : 1
        if (ac !== bc) return ac - bc
        const ta = new Date(a?.depart_at || 0).getTime()
        const tb = new Date(b?.depart_at || 0).getTime()
        return tb - ta
      })
  } catch {
    tripOptions.value = []
  } finally {
    tripsLoading.value = false
  }
}

function applyTripFromQuery() {
  const raw = route.query.trip_id
  const id = typeof raw === 'string' ? raw.trim() : ''
  if (!id) return
  linkMode.value = 'trip'
  form.value.trip_id = id
}

async function submit() {
  if (saving.value || !canSubmit.value) return
  const a = String(form.value.amount || '').replace(/\D/g, '')
  const num = a === '' ? NaN : parseInt(a, 10)
  if (!Number.isFinite(num) || num < 0) {
    errorMsg.value = t('driver_trip_detail.cost_err_amount')
    return
  }
  saving.value = true
  errorMsg.value = ''
  try {
    const payload = {
      type: form.value.type,
      amount: num,
      description: form.value.description?.trim() || null,
      currency: 'VND',
    }
    if (linkMode.value === 'trip') {
      const tripRaw = String(form.value.trip_id || '').trim()
      if (tripRaw) payload.trip_id = Number(tripRaw)
    }
    const created = await submitStandaloneTripCost(payload, {
      idempotencyKey: `driver-standalone-cost-${Date.now()}`,
    })
    const id = created?.id
    if (id) {
      await router.replace({ name: 'driverCostDetail', params: { id: String(id) } })
    } else {
      await router.replace({ name: 'driverCosts' })
    }
  } catch {
    errorMsg.value = t('driver_trip_detail.cost_err_submit')
  } finally {
    saving.value = false
  }
}

watch(
  () => route.query.trip_id,
  () => applyTripFromQuery(),
)

onMounted(() => {
  void loadTrips().finally(() => applyTripFromQuery())
})
</script>
