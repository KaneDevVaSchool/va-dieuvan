<template>
  <div class="space-y-4">
    <div
      class="flex gap-2 overflow-x-auto overscroll-x-contain border-b pb-2 [-ms-overflow-style:none] [scrollbar-width:none] sm:flex-wrap [&::-webkit-scrollbar]:hidden"
    >
      <button
        v-for="item in tabDefs"
        :key="item.id"
        type="button"
        class="shrink-0 whitespace-nowrap rounded-full px-3 py-1.5 text-sm transition active:scale-[0.98]"
        :class="tab === item.id ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'"
        @click="tab = item.id"
      >
        {{ item.label }}
      </button>
    </div>

    <Card v-if="tab === 'periods'" :title="t('payments_hub.card_periods_title')">
      <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap md:items-end">
        <Input v-model="periodCreate.start_date" :label="t('payments_hub.label_start_date')" type="date" />
        <Input v-model="periodCreate.end_date" :label="t('payments_hub.label_end_date')" type="date" />
        <Button :loading="creating" class="w-full shrink-0 sm:w-auto" @click="createPeriod">{{
          t('payments_hub.btn_create_period')
        }}</Button>
      </div>
      <div v-if="loadingP" class="text-sm text-slate-500">{{ t('payments_hub.loading') }}</div>
      <div v-else class="space-y-2">
        <div v-for="p in periods" :key="p.id" class="rounded-lg border p-3 text-sm">
          <div class="font-semibold">
            {{
              t('payments_hub.period_line', {
                id: p.id,
                status: p.status,
                start: p.start_date,
                end: p.end_date,
              })
            }}
          </div>
          <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-end">
            <Button variant="secondary" class="w-full sm:w-auto" @click="lockPeriod(p.id)">{{
              t('payments_hub.btn_lock_period')
            }}</Button>
            <Input v-model.number="genTripIds[p.id]" class="w-full min-w-0 sm:w-40" :label="t('payments_hub.label_trip_ids')" />
            <Button class="w-full sm:w-auto" @click="genPay(p.id)">{{ t('payments_hub.btn_gen_payments') }}</Button>
          </div>
        </div>
      </div>
    </Card>

    <Card v-if="tab === 'payments'" :title="t('payments_hub.card_payments_title')">
      <div v-if="loadingPay" class="text-sm text-slate-500">{{ t('payments_hub.loading') }}</div>
      <div v-else class="space-y-2">
        <div v-for="p in payments" :key="p.id" class="rounded-lg border p-3 text-sm">
          <div class="flex flex-wrap justify-between gap-2">
            <span class="font-semibold">{{
              t('payments_hub.payment_line', { id: p.id, tripId: p.trip_id, status: p.status })
            }}</span>
            <span>{{ p.amount }} {{ p.currency }}</span>
          </div>
          <form class="mt-2 flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-end" @submit.prevent="exec(p.id)">
            <Select v-model="execForm[p.id].method" :label="t('payments_hub.label_method')">
              <option value="bank">{{ t('payments_hub.method_bank') }}</option>
              <option value="cash">{{ t('payments_hub.method_cash') }}</option>
            </Select>
            <Select v-model="execForm[p.id].status" :label="t('payments_hub.label_status')">
              <option value="paid">{{ t('payments_hub.status_paid') }}</option>
              <option value="failed">{{ t('payments_hub.status_failed') }}</option>
            </Select>
            <Button :loading="execLoading[p.id]" class="w-full sm:w-auto" type="submit">{{ t('payments_hub.btn_execute') }}</Button>
          </form>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import Card from '../../components/ui/Card.vue'
import Button from '../../components/ui/Button.vue'
import Input from '../../components/ui/Input.vue'
import Select from '../../components/ui/Select.vue'
import {
  createReconciliationPeriod,
  executePayment,
  generatePayments,
  listPayments,
  listReconciliationPeriods,
  lockReconciliationPeriod,
} from '../../api/payments'
import { newIdempotencyKey } from '../../util/idempotency'

const { t } = useI18n()

const tabDefs = computed(() => [
  { id: 'periods', label: t('payments_hub.tab_periods') },
  { id: 'payments', label: t('payments_hub.tab_payments') },
])
const tab = ref('periods')

const loadingP = ref(false)
const periods = ref([])
const periodCreate = reactive({ start_date: '', end_date: '' })
const creating = ref(false)
const genTripIds = reactive({})

const loadingPay = ref(false)
const payments = ref([])
const execForm = reactive({})
const execLoading = reactive({})

async function loadPeriods() {
  loadingP.value = true
  try {
    const res = await listReconciliationPeriods({ per_page: 50 })
    periods.value = res.items ?? []
  } finally {
    loadingP.value = false
  }
}

async function loadPayments() {
  loadingPay.value = true
  try {
    const res = await listPayments({ per_page: 50 })
    payments.value = res.items ?? []
    for (const p of payments.value) {
      if (!execForm[p.id]) {
        execForm[p.id] = { method: 'bank', status: 'paid', reference: '' }
      }
    }
  } finally {
    loadingPay.value = false
  }
}

async function createPeriod() {
  creating.value = true
  try {
    await createReconciliationPeriod({ ...periodCreate })
    await loadPeriods()
  } finally {
    creating.value = false
  }
}

async function lockPeriod(id) {
  await lockReconciliationPeriod(id)
  await loadPeriods()
}

async function genPay(periodId) {
  const raw = String(genTripIds[periodId] ?? '')
  const trip_ids = raw
    .split(/[,\s]+/)
    .map((x) => parseInt(x, 10))
    .filter((n) => !Number.isNaN(n))
  await generatePayments(periodId, { trip_ids })
  await loadPayments()
}

async function exec(id) {
  execLoading[id] = true
  try {
    await executePayment(id, execForm[id], { idempotencyKey: newIdempotencyKey() })
    await loadPayments()
  } finally {
    execLoading[id] = false
  }
}

watch(tab, (v) => {
  if (v === 'periods') loadPeriods()
  if (v === 'payments') loadPayments()
})

onMounted(loadPeriods)
</script>
