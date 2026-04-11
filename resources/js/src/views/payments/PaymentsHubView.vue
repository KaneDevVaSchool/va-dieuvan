<template>
  <div class="space-y-4">
    <div class="flex gap-2 overflow-x-auto border-b pb-2 text-sm">
      <button
        v-for="t in tabs"
        :key="t.id"
        class="whitespace-nowrap rounded-full px-3 py-1"
        :class="tab === t.id ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'"
        @click="tab = t.id"
      >
        {{ t.label }}
      </button>
    </div>

    <Card v-if="tab === 'periods'" title="Kỳ đối soát">
      <div class="mb-3 flex flex-col gap-2 md:flex-row md:items-end">
        <Input v-model="periodCreate.start_date" label="start_date" type="date" />
        <Input v-model="periodCreate.end_date" label="end_date" type="date" />
        <Button :loading="creating" @click="createPeriod">Tạo kỳ</Button>
      </div>
      <div v-if="loadingP" class="text-sm text-slate-500">Đang tải…</div>
      <div v-else class="space-y-2">
        <div v-for="p in periods" :key="p.id" class="rounded-lg border p-3 text-sm">
          <div class="font-semibold">#{{ p.id }} {{ p.status }} ({{ p.start_date }} → {{ p.end_date }})</div>
          <div class="mt-2 flex flex-wrap gap-2">
            <Button variant="secondary" @click="lockPeriod(p.id)">Khóa kỳ</Button>
            <Input v-model.number="genTripIds[p.id]" class="w-40" label="Trip IDs (1,2)" />
            <Button @click="genPay(p.id)">Tạo payments</Button>
          </div>
        </div>
      </div>
    </Card>

    <Card v-if="tab === 'payments'" title="Payments">
      <div v-if="loadingPay" class="text-sm text-slate-500">Đang tải…</div>
      <div v-else class="space-y-2">
        <div v-for="p in payments" :key="p.id" class="rounded-lg border p-3 text-sm">
          <div class="flex flex-wrap justify-between gap-2">
            <span class="font-semibold">#{{ p.id }} trip {{ p.trip_id }} • {{ p.status }}</span>
            <span>{{ p.amount }} {{ p.currency }}</span>
          </div>
          <form class="mt-2 flex flex-wrap items-end gap-2" @submit.prevent="exec(p.id)">
            <Select v-model="execForm[p.id].method" label="method">
              <option value="bank">bank</option>
              <option value="cash">cash</option>
            </Select>
            <Select v-model="execForm[p.id].status" label="status">
              <option value="paid">paid</option>
              <option value="failed">failed</option>
            </Select>
            <Button :loading="execLoading[p.id]" type="submit">Thực hiện</Button>
          </form>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
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

const tabs = [
  { id: 'periods', label: 'Kỳ đối soát' },
  { id: 'payments', label: 'Payments' },
]
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
