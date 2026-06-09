<template>
  <div class="min-h-full w-full bg-driver-bg pb-[calc(6rem+env(safe-area-inset-bottom))] text-driver-ink">
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

    <div class="mx-auto max-w-lg px-4 pt-4 sm:max-w-2xl">
      <p class="text-sm leading-snug text-driver-muted">
        {{ t('driver_costs.create_intro') }}
      </p>

      <div
        class="mt-4 grid grid-cols-2 gap-1.5 rounded-xl bg-driver-surface/80 p-1 ring-1 ring-white/[0.06]"
        role="tablist"
        :aria-label="t('driver_costs.link_mode_aria')"
      >
        <button
          type="button"
          role="tab"
          :aria-selected="linkMode === 'trip'"
          class="min-h-[44px] rounded-lg px-2 text-sm font-bold transition"
          :class="linkMode === 'trip' ? 'bg-driver-accent text-driver-bg' : 'text-driver-muted'"
          @click="setLinkMode('trip')"
        >
          {{ t('driver_costs.link_mode_trip') }}
        </button>
        <button
          type="button"
          role="tab"
          :aria-selected="linkMode === 'none'"
          class="min-h-[44px] rounded-lg px-2 text-sm font-bold transition"
          :class="linkMode === 'none' ? 'bg-driver-accent text-driver-bg' : 'text-driver-muted'"
          @click="setLinkMode('none')"
        >
          {{ t('driver_costs.link_mode_none') }}
        </button>
      </div>

      <div class="mt-5 space-y-4 rounded-[1.25rem] bg-driver-card p-4 ring-1 ring-white/[0.06] sm:p-5">
        <template v-if="linkMode === 'trip'">
          <div>
            <p class="text-sm font-semibold text-driver-muted">{{ t('driver_costs.step_trip_title') }}</p>
            <p class="mt-0.5 text-xs leading-snug text-driver-muted/80">{{ t('driver_costs.step_trip_hint') }}</p>
            <DriverCostTripPicker
              v-model="form.trip_id"
              class="mt-2"
              :trips="tripOptions"
              :loading="tripsLoading"
            />
          </div>
        </template>

        <div>
          <label class="text-sm font-semibold text-driver-muted" for="dcc-type">{{ t('driver_trip_detail.cost_type') }}</label>
          <div class="relative mt-1.5">
            <select
              id="dcc-type"
              v-model="form.type"
              class="flex min-h-[48px] w-full appearance-none rounded-xl border border-white/10 bg-driver-surface px-3 py-2.5 pr-10 text-base text-driver-ink focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
            >
              <option v-for="ct in costTypes" :key="ct.value" :value="ct.value">{{ ct.label }}</option>
            </select>
            <ChevronDownIcon class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-driver-muted/70" aria-hidden="true" />
          </div>
        </div>

        <div>
          <label class="text-sm font-semibold text-driver-muted" for="dcc-amt">{{ t('driver_trip_detail.cost_amount') }}</label>
          <div class="relative mt-1.5">
            <input
              id="dcc-amt"
              :value="amountDisplay"
              type="text"
              inputmode="numeric"
              class="flex min-h-[48px] w-full rounded-xl border border-white/10 bg-driver-surface py-2.5 pl-3 pr-11 text-base font-semibold tabular-nums text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
              :placeholder="t('driver_cost_req.ph_amount')"
              @input="onAmountInput"
            />
            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-driver-muted">₫</span>
          </div>
        </div>

        <div>
          <label class="text-sm font-semibold text-driver-muted" for="dcc-desc">{{ t('driver_trip_detail.cost_desc') }}</label>
          <input
            id="dcc-desc"
            v-model="form.description"
            type="text"
            class="mt-1.5 flex min-h-[48px] w-full rounded-xl border border-white/10 bg-driver-surface px-3 py-2.5 text-base text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
            :placeholder="t('driver_cost_req.ph_desc')"
          />
        </div>

        <p v-if="errorMsg" class="text-sm text-rose-300">{{ errorMsg }}</p>

        <button
          type="button"
          class="flex min-h-[52px] w-full items-center justify-center rounded-xl bg-driver-accent py-3 text-base font-bold text-driver-bg transition hover:brightness-110 disabled:opacity-45 active:scale-[0.99]"
          :disabled="saving || !canSubmit"
          @click="submit"
        >
          {{ saving ? t('driver_costs.create_saving') : t('driver_costs.create_submit') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeftIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'
import { listDriverTrips } from '../../api/driver'
import { submitStandaloneTripCost } from '../../api/costs'
import DriverCostTripPicker from '../../components/driver/costs/DriverCostTripPicker.vue'
import { isTripEligibleForDriverLinkedCost } from '../../constants/tripStatus'
import { toLocalDateKey } from '../../util/dates'
import { formatVndWhileTyping, parseMoneyVnd } from '../../util/money'

const { t } = useI18n()
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
/** @type {import('vue').Ref<object[]>} */
const tripOptions = ref([])

const costTypes = computed(() => [
  { value: 'fuel', label: t('driver_trip_detail.cost_type_fuel') },
  { value: 'toll', label: t('driver_trip_detail.cost_type_toll') },
  { value: 'parking', label: t('driver_trip_detail.cost_type_parking') },
  { value: 'other', label: t('driver_trip_detail.cost_type_other') },
])

const amountDisplay = computed(() => formatVndWhileTyping(form.value.amount))

const canSubmit = computed(() => {
  const num = parseMoneyVnd(form.value.amount)
  if (!Number.isFinite(num) || num <= 0) return false
  if (linkMode.value === 'trip' && !String(form.value.trip_id || '').trim()) return false
  return true
})

function setLinkMode(mode) {
  linkMode.value = mode
  if (mode === 'none') form.value.trip_id = ''
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
  const num = parseMoneyVnd(form.value.amount)
  if (!Number.isFinite(num) || num <= 0) {
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
