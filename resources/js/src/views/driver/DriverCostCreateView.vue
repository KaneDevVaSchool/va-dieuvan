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

    <div class="mx-auto max-w-lg px-4 pb-8 pt-5 sm:max-w-2xl">
      <p class="text-base leading-relaxed text-driver-muted">
        {{ t('driver_costs.create_intro') }}
      </p>

      <div class="mt-6 space-y-5 rounded-[1.35rem] bg-driver-card p-5 ring-1 ring-white/[0.06]">
        <div>
          <label class="text-sm font-semibold text-driver-muted" for="dcc-trip">{{ t('driver_costs.trip_optional') }}</label>
          <div class="relative mt-2">
            <select
              id="dcc-trip"
              v-model="form.trip_id"
              class="flex min-h-[52px] w-full appearance-none rounded-2xl border border-white/10 bg-driver-surface px-4 py-3 pr-11 text-base text-driver-ink focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
            >
              <option value="">{{ t('driver_costs.trip_none') }}</option>
              <option v-for="tr in tripOptions" :key="tr.id" :value="String(tr.id)">
                #{{ tr.id }} — {{ tripLabel(tr) }}
              </option>
            </select>
            <ChevronDownIcon class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-driver-muted/70" aria-hidden="true" />
          </div>
          <p v-if="tripsLoading" class="mt-2 text-sm text-driver-muted">{{ t('driver_costs.trips_loading') }}</p>
        </div>

        <div>
          <p class="text-sm font-semibold text-driver-muted">{{ t('driver_trip_detail.cost_type') }}</p>
          <div class="relative mt-2">
            <select
              v-model="form.type"
              class="flex min-h-[52px] w-full appearance-none rounded-2xl border border-white/10 bg-driver-surface px-4 py-3 pr-11 text-base text-driver-ink focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
            >
              <option v-for="ct in costTypes" :key="ct.value" :value="ct.value">{{ ct.label }}</option>
            </select>
            <ChevronDownIcon class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-driver-muted/70" aria-hidden="true" />
          </div>
        </div>

        <div>
          <label class="text-sm font-semibold text-driver-muted" for="dcc-amt">{{ t('driver_trip_detail.cost_amount') }}</label>
          <input
            id="dcc-amt"
            v-model="form.amount"
            type="text"
            inputmode="numeric"
            class="mt-2 flex min-h-[52px] w-full rounded-2xl border border-white/10 bg-driver-surface px-4 py-3 text-base text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
            :placeholder="t('driver_cost_req.ph_amount')"
          />
        </div>

        <div>
          <label class="text-sm font-semibold text-driver-muted" for="dcc-desc">{{ t('driver_trip_detail.cost_desc') }}</label>
          <input
            id="dcc-desc"
            v-model="form.description"
            type="text"
            class="mt-2 flex min-h-[52px] w-full rounded-2xl border border-white/10 bg-driver-surface px-4 py-3 text-base text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/45"
            :placeholder="t('driver_cost_req.ph_desc')"
          />
        </div>

        <p v-if="errorMsg" class="text-sm text-rose-300">{{ errorMsg }}</p>

        <button
          type="button"
          class="flex min-h-[52px] w-full items-center justify-center rounded-2xl bg-driver-accent py-3 text-base font-bold text-driver-bg shadow-[0_12px_36px_-14px_rgba(127,220,200,0.55)] transition hover:brightness-110 disabled:opacity-45 active:scale-[0.99]"
          :disabled="saving"
          @click="submit"
        >
          {{ saving ? t('driver_costs.create_saving') : t('driver_costs.create_submit') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { ArrowLeftIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'
import { listDriverTrips } from '../../api/driver'
import { submitStandaloneTripCost } from '../../api/costs'
import { isTripCostEditableStatus } from '../../constants/tripStatus'
import { toLocalDateKey } from '../../util/dates'

const { t } = useI18n()
const router = useRouter()

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

function tripLabel(tr) {
  const dr = tr?.dispatch_request
  const o = (dr?.origin || '').trim()
  const d = (dr?.destination || '').trim()
  if (o && d) return `${o} → ${d}`
  return o || d || tr?.status || '—'
}

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push({ name: 'driverCosts' })
}

/** Khớp cửa sổ dashboard tài xế (~51 ngày, dưới giới hạn API 60). */
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
      per_page: 50,
      page: 1,
    })
    const items = res?.items ?? []
    tripOptions.value = items.filter((tr) => isTripCostEditableStatus(tr?.status))
  } catch {
    tripOptions.value = []
  } finally {
    tripsLoading.value = false
  }
}

async function submit() {
  if (saving.value) return
  const a = String(form.value.amount || '').replace(/\D/g, '')
  const num = a === '' ? NaN : parseInt(a, 10)
  if (!Number.isFinite(num) || num < 0) {
    errorMsg.value = t('driver_trip_detail.cost_err_amount')
    return
  }
  saving.value = true
  errorMsg.value = ''
  try {
    const tripRaw = String(form.value.trip_id || '').trim()
    const payload = {
      type: form.value.type,
      amount: num,
      description: form.value.description?.trim() || null,
      currency: 'VND',
    }
    if (tripRaw) payload.trip_id = Number(tripRaw)
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

onMounted(() => {
  void loadTrips()
})
</script>
