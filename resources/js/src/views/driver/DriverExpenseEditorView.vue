<template>
  <div class="min-h-full w-full bg-driver-bg pb-[calc(7rem+env(safe-area-inset-bottom))] text-driver-ink">
    <header
      class="sticky top-0 z-40 border-b border-white/[0.06] bg-driver-bg/90 backdrop-blur-md"
      :style="{ paddingTop: 'max(0.5rem, env(safe-area-inset-top))' }"
    >
      <div class="mx-auto flex max-w-lg items-center gap-2 px-3 pb-3 pt-2 sm:max-w-2xl">
        <button
          type="button"
          class="flex min-h-[48px] min-w-[48px] shrink-0 items-center justify-center rounded-2xl bg-driver-card ring-1 ring-white/[0.08] active:scale-[0.97]"
          :aria-label="t('expense.back')"
          @click="goBack"
        >
          <ArrowLeftIcon class="h-6 w-6 text-driver-accent" />
        </button>
        <div class="min-w-0 flex-1 text-center">
          <h1 class="truncate text-lg font-bold">{{ t(`expense.type_${typeKey}`) }}</h1>
          <p class="truncate text-xs text-driver-muted">{{ docId ? docCode : t('expense.new_doc') }}</p>
        </div>
        <span class="w-[48px] shrink-0" aria-hidden="true" />
      </div>
    </header>

    <div class="mx-auto max-w-lg space-y-4 px-4 pt-4 sm:max-w-2xl">
      <p v-if="loadError" class="rounded-xl bg-amber-500/15 px-3 py-2 text-sm text-amber-100">{{ loadError }}</p>
      <p v-if="rejectedReason" class="rounded-xl bg-amber-500/15 px-3 py-2 text-sm text-amber-100 ring-1 ring-amber-400/30">
        {{ t('expense.rejected_reason') }}: {{ rejectedReason }}
      </p>

      <!-- Căn cứ -->
      <section v-if="!docId && parentOptions.length" class="rounded-[1.25rem] bg-driver-card p-4 ring-1 ring-white/[0.06]">
        <label class="text-sm font-semibold text-driver-muted" for="ex-parent">{{ t('expense.parent') }}</label>
        <select id="ex-parent" v-model="form.parent_id" class="mt-1.5 min-h-[48px] w-full rounded-xl border border-white/10 bg-driver-surface px-3 text-base text-driver-ink" @change="onParentChange">
          <option value="">{{ t('expense.parent_none') }}</option>
          <option v-for="p in parentOptions" :key="p.id" :value="p.id">{{ p.code }} · {{ p.type_label }} · {{ formatVndCurrency(p.total_amount) }}</option>
        </select>
        <p class="mt-1 text-xs text-driver-muted/80">{{ t('expense.parent_hint') }}</p>
      </section>

      <!-- Người đề nghị -->
      <section class="space-y-2 rounded-[1.25rem] bg-driver-card p-4 ring-1 ring-white/[0.06]">
        <h2 class="text-sm font-bold uppercase tracking-wide text-driver-muted">{{ t('expense.sec_requester') }}</h2>
        <input v-model="form.requester_name" type="text" maxlength="255" :placeholder="t('expense.requester_name')" :aria-label="t('expense.requester_name')" :class="inputCls" />
        <div class="grid grid-cols-2 gap-2">
          <input v-model="form.requester_phone" type="tel" maxlength="32" :placeholder="t('expense.requester_phone')" :aria-label="t('expense.requester_phone')" :class="inputCls" />
          <input v-model="form.requester_email" type="email" maxlength="255" :placeholder="t('expense.requester_email')" :aria-label="t('expense.requester_email')" :class="inputCls" />
        </div>
        <input v-model="form.department_label" type="text" maxlength="255" :placeholder="t('expense.department')" :aria-label="t('expense.department')" :class="inputCls" />
      </section>

      <!-- Nội dung -->
      <section class="space-y-2 rounded-[1.25rem] bg-driver-card p-4 ring-1 ring-white/[0.06]">
        <h2 class="text-sm font-bold uppercase tracking-wide text-driver-muted">{{ t('expense.sec_content') }}</h2>
        <label class="block text-xs text-driver-muted">
          {{ t('expense.vehicle_main') }}
          <select v-model="form.vehicle_id" :class="inputCls">
            <option value="">—</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.license_plate }}{{ v.mine ? ' ★' : '' }}</option>
          </select>
        </label>
        <label class="block text-xs text-driver-muted">
          {{ docType === 'payment_request' ? t('expense.content') : t('expense.purpose') }}
          <textarea v-model="form.purpose" rows="2" maxlength="2000" :class="inputCls" />
        </label>
        <label class="block text-xs text-driver-muted">
          {{ t('expense.basis') }}
          <input v-model="form.basis" type="text" maxlength="2000" :class="inputCls" />
        </label>

        <template v-if="docType === 'proposal'">
          <div class="grid grid-cols-2 gap-2">
            <label class="text-xs text-driver-muted">{{ t('expense.proposed_on') }}<input v-model="form.proposed_on" type="date" :class="inputCls" /></label>
            <label class="text-xs text-driver-muted">{{ t('expense.needed_by') }}<input v-model="form.needed_by" type="date" :class="inputCls" /></label>
          </div>
          <label class="flex items-center gap-2 text-sm"><input v-model="form.is_urgent" type="checkbox" class="h-5 w-5 accent-[#7fdcc8]" />{{ t('expense.urgent') }}</label>
          <input v-if="form.is_urgent" v-model="form.urgent_reason" type="text" maxlength="255" :placeholder="t('expense.urgent_reason')" :class="inputCls" />
          <label class="block text-xs text-driver-muted">{{ t('expense.allocation_target') }}<input v-model="form.allocation_target" type="text" maxlength="255" :class="inputCls" /></label>
          <label class="block text-xs text-driver-muted">{{ t('expense.handover_person') }}<input v-model="form.handover_person" type="text" maxlength="255" :class="inputCls" /></label>
        </template>

        <template v-if="docType === 'payment_request'">
          <div class="grid grid-cols-2 gap-2">
            <label class="text-xs text-driver-muted">{{ t('expense.proposed_on') }}<input v-model="form.proposed_on" type="date" :class="inputCls" /></label>
            <label class="text-xs text-driver-muted">{{ t('expense.payment_due') }}<input v-model="form.payment_due_label" type="text" maxlength="255" :placeholder="t('expense.payment_due_ph')" :class="inputCls" /></label>
          </div>
          <div class="grid grid-cols-2 gap-2" role="radiogroup" :aria-label="t('expense.payment_method')">
            <button
              v-for="m in ['cash', 'transfer']"
              :key="m"
              type="button"
              class="min-h-[48px] rounded-xl text-sm font-bold ring-1"
              :class="form.payment_method === m ? 'bg-driver-accent text-driver-bg ring-driver-accent' : 'bg-driver-surface text-driver-muted ring-white/10'"
              @click="form.payment_method = m"
            >
              {{ t(`expense.method_${m}`) }}
            </button>
          </div>
          <template v-if="form.payment_method === 'transfer'">
            <input v-model="form.bank_account_no" type="text" inputmode="numeric" maxlength="64" :placeholder="t('expense.bank_account_no')" :class="inputCls" />
            <div class="grid grid-cols-2 gap-2">
              <input v-model="form.bank_name" type="text" maxlength="255" :placeholder="t('expense.bank_name')" :class="inputCls" />
              <input v-model="form.bank_account_holder" type="text" maxlength="255" :placeholder="t('expense.bank_holder')" :class="inputCls" />
            </div>
          </template>
        </template>

        <template v-if="docType === 'no_invoice_statement'">
          <label class="block text-xs text-driver-muted">{{ t('expense.purchasing_person') }}<input v-model="form.purchasing_person" type="text" maxlength="255" :class="inputCls" /></label>
          <label class="block text-xs text-driver-muted">{{ t('expense.proposed_on') }}<input v-model="form.proposed_on" type="date" :class="inputCls" /></label>
        </template>
      </section>

      <!-- Dòng chi phí -->
      <section class="rounded-[1.25rem] bg-driver-card p-3 ring-1 ring-white/[0.06]">
        <h2 class="px-1 pb-2 text-sm font-bold uppercase tracking-wide text-driver-muted">{{ t('expense.sec_lines') }}</h2>
        <p v-if="usesTripCosts" class="px-1 pb-2 text-xs text-driver-muted/80">{{ t('expense.lines_ledger_hint') }}</p>
        <ul class="space-y-2">
          <ExpenseLineEditor
            v-for="(line, i) in lines"
            :key="line._key"
            :line="line"
            :index="i"
            :vehicles="vehicles"
            :doc-type="docType"
            :uses-trip-costs="usesTripCosts"
            :error="lineErrors[i] || ''"
            @remove="lines.splice(i, 1)"
          />
        </ul>
        <div class="mt-2 grid gap-2" :class="usesTripCosts ? 'grid-cols-2' : 'grid-cols-1'">
          <button type="button" class="flex min-h-[48px] items-center justify-center gap-1.5 rounded-xl bg-driver-surface text-sm font-bold text-driver-accent ring-1 ring-white/10" @click="addLine()">
            <PlusIcon class="h-5 w-5" /> {{ t('expense.add_line') }}
          </button>
          <button
            v-if="usesTripCosts"
            type="button"
            class="flex min-h-[48px] items-center justify-center gap-1.5 rounded-xl bg-driver-surface text-sm font-bold text-driver-accent ring-1 ring-white/10"
            @click="pickerOpen = true"
          >
            <ListBulletIcon class="h-5 w-5" /> {{ t('expense.pick_costs') }}
          </button>
        </div>
        <div class="mt-3 rounded-xl bg-driver-bg/50 px-3 py-2.5">
          <p class="flex items-center justify-between text-base font-bold">
            <span>{{ t('expense.total') }}</span><span class="tabular-nums">{{ formatVndCurrency(total) }}</span>
          </p>
          <p class="mt-0.5 text-xs text-driver-muted">{{ vndAmountInWords(total) }}</p>
        </div>
      </section>

      <!-- Người ký -->
      <section v-if="form.signatories.length" class="space-y-2 rounded-[1.25rem] bg-driver-card p-4 ring-1 ring-white/[0.06]">
        <h2 class="text-sm font-bold uppercase tracking-wide text-driver-muted">{{ t('expense.sec_signatories') }}</h2>
        <label v-for="(s, i) in form.signatories" :key="i" class="block text-xs text-driver-muted">
          {{ s.role }}
          <input v-model="s.name" type="text" maxlength="120" :class="inputCls" />
        </label>
      </section>

      <p v-if="saveError" class="text-sm text-rose-300">{{ saveError }}</p>
      <button
        type="button"
        class="flex min-h-[52px] w-full items-center justify-center rounded-xl bg-driver-accent py-3 text-base font-bold text-driver-bg disabled:opacity-45 active:scale-[0.99]"
        :disabled="saving"
        data-testid="expense-save"
        @click="save"
      >
        {{ saving ? t('expense.saving') : t('expense.save_draft') }}
      </button>
    </div>

    <ExpenseCostPicker
      :open="pickerOpen"
      :doc-type="docType"
      :vehicle-id="form.vehicle_id || null"
      :document-id="docId"
      :exclude-ids="lines.map((l) => l.trip_cost_id).filter(Boolean)"
      @close="pickerOpen = false"
      @pick="addPickedCosts"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeftIcon, ListBulletIcon, PlusIcon } from '@heroicons/vue/24/outline'
import { createExpense, getExpenseDefaults, getMyExpense, updateExpense } from '../../api/expenses'
import { expenseTypeKey, expenseUsesTripCosts } from '../../constants/checklistExpense'
import { showAppSuccess } from '../../composables/appMessage'
import { formatVndCurrency, vndAmountInWords } from '../../util/money'
import { toLocalDateKey } from '../../util/dates'
import ExpenseLineEditor from '../../components/driver/expenses/ExpenseLineEditor.vue'
import ExpenseCostPicker from '../../components/driver/expenses/ExpenseCostPicker.vue'

const inputCls =
  'mt-1 min-h-[48px] w-full rounded-xl border border-white/10 bg-driver-surface px-3 py-2 text-base text-driver-ink placeholder:text-driver-muted/50 focus:outline-none focus:ring-2 focus:ring-driver-accent/45'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const docId = computed(() => (route.params.id ? Number(route.params.id) : null))
const docType = ref(String(route.query.type || 'proposal'))
const paymentKind = ref(route.query.kind ? String(route.query.kind) : docType.value === 'payment_request' ? 'payment' : null)
const docCode = ref('')
const lockVersion = ref(0)
const rejectedReason = ref('')

const defaults = ref({ vehicles: [], parents: [], signatories: {} })
const vehicles = computed(() => defaults.value.vehicles ?? [])
const loadError = ref('')
const saving = ref(false)
const saveError = ref('')
const lineErrors = ref({})
const pickerOpen = ref(false)

const typeKey = computed(() => expenseTypeKey({ doc_type: docType.value, payment_kind: paymentKind.value }))
const usesTripCosts = computed(() => expenseUsesTripCosts(docType.value, paymentKind.value))

const form = reactive({
  parent_id: route.query.parent ? Number(route.query.parent) : '',
  vehicle_id: '',
  requester_name: '',
  requester_email: '',
  requester_phone: '',
  department_label: '',
  purpose: '',
  basis: '',
  proposed_on: toLocalDateKey(new Date()),
  needed_by: '',
  is_urgent: false,
  urgent_reason: '',
  allocation_target: '',
  handover_person: '',
  purchasing_person: '',
  payment_due_label: '',
  payment_method: '',
  bank_account_no: '',
  bank_name: '',
  bank_account_holder: '',
  signatories: [],
})
const lines = ref([])
let keySeq = 0

const parentOptions = computed(() =>
  (defaults.value.parents ?? []).filter((p) => {
    if (docType.value === 'payment_request' && paymentKind.value === 'reimbursement') return p.doc_type === 'proposal' || p.payment_kind === 'advance'
    if (docType.value === 'payment_request') return p.doc_type === 'proposal'
    return false
  }),
)

const total = computed(() =>
  lines.value.reduce((sum, l) => {
    if (l.trip_cost_id && !l.owns_trip_cost) return sum + (Number(l.amount) || 0)
    return sum + Math.round((Number(l.quantity) || 1) * (Number(l.unit_price) || 0))
  }, 0),
)

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push({ name: 'driverExpenses' })
}

function newLine(patch = {}) {
  keySeq += 1
  return reactive({
    _key: `l${keySeq}`,
    id: null,
    trip_cost_id: null,
    owns_trip_cost: false,
    vehicle_id: form.vehicle_id || '',
    cost_type: 'other',
    category: '',
    description: '',
    unit: '',
    quantity: 1,
    unit_price: 0,
    amount: 0,
    purchased_on: toLocalDateKey(new Date()),
    seller_name: '',
    seller_address: '',
    has_invoice: docType.value !== 'no_invoice_statement',
    note: '',
    ...patch,
  })
}

function addLine() {
  lines.value.push(newLine())
}

function addPickedCosts(costs) {
  for (const c of costs) {
    if (lines.value.some((line) => Number(line.trip_cost_id) === Number(c.id))) continue
    lines.value.push(
      newLine({
        trip_cost_id: c.id,
        vehicle_id: c.vehicle_id || '',
        cost_type: c.type,
        category: t(`expense.cost_type_${c.type}`, c.type),
        description: c.description || '',
        unit: c.unit || '',
        quantity: c.quantity || 1,
        unit_price: c.unit_price || c.amount,
        amount: c.amount,
        purchased_on: c.reported_on,
        has_invoice: docType.value === 'no_invoice_statement' ? false : c.has_invoice,
        seller_name: c.seller_name || '',
        seller_address: c.seller_address || '',
      }),
    )
  }
}

function onParentChange() {
  const p = (defaults.value.parents ?? []).find((x) => String(x.id) === String(form.parent_id))
  if (!p) return
  if (!form.purpose) form.purpose = p.purpose || ''
  if (!form.vehicle_id && p.vehicle_id) form.vehicle_id = p.vehicle_id
  form.basis = p.code
}

function applyDoc(doc) {
  docType.value = doc.doc_type
  paymentKind.value = doc.payment_kind
  docCode.value = doc.code
  lockVersion.value = doc.lock_version
  rejectedReason.value = doc.status === 'rejected' ? doc.rejected_reason : ''
  for (const k of Object.keys(form)) {
    if (k === 'signatories') form.signatories = (doc.signatories ?? []).map((s) => ({ ...s }))
    else if (k in doc) form[k] = doc[k] ?? (typeof form[k] === 'boolean' ? false : '')
  }
  lines.value = (doc.lines ?? []).map((l) => newLine({ ...l, vehicle_id: l.vehicle_id || '' }))
}

async function load() {
  loadError.value = ''
  try {
    defaults.value = await getExpenseDefaults()
    if (docId.value) {
      const doc = await getMyExpense(docId.value)
      if (!doc.can?.edit) {
        await router.replace({ name: 'driverExpenseDetail', params: { id: doc.id } })
        return
      }
      applyDoc(doc)
      return
    }
    const d = defaults.value
    form.requester_name = d.requester?.name || ''
    form.requester_email = d.requester?.email || ''
    form.requester_phone = d.requester?.phone || ''
    form.department_label = d.department_label || ''
    form.purchasing_person = d.requester?.name || ''
    form.signatories = (d.signatories?.[docType.value] ?? []).map((s) => ({ ...s }))
    const mine = (d.vehicles ?? []).filter((v) => v.mine)
    if (mine.length === 1) form.vehicle_id = mine[0].id
    if (form.parent_id) onParentChange()
    if (!usesTripCosts.value || docType.value === 'no_invoice_statement') addLine()
  } catch (err) {
    loadError.value = err?.response?.data?.message || t('expense.load_error')
  }
}

function payload() {
  const body = { ...form }
  if (docType.value === 'no_invoice_statement') body.parent_id = null
  for (const k of Object.keys(body)) {
    if (body[k] === '') body[k] = null
  }
  body.vehicle_id = form.vehicle_id ? Number(form.vehicle_id) : null
  body.lines = lines.value.map((l) => ({
    id: l.id || null,
    trip_cost_id: l.trip_cost_id || null,
    vehicle_id: l.vehicle_id ? Number(l.vehicle_id) : null,
    cost_type: l.cost_type || 'other',
    category: l.category || null,
    description: l.description || null,
    unit: l.unit || null,
    quantity: Number(l.quantity) || 1,
    unit_price: Number(l.unit_price) || 0,
    amount: Number(l.amount) || 0,
    purchased_on: l.purchased_on || null,
    seller_name: l.seller_name || null,
    seller_address: l.seller_address || null,
    has_invoice: !!l.has_invoice,
    note: l.note || null,
  }))
  if (docId.value) {
    delete body.parent_id
    body.lock_version = lockVersion.value
  } else {
    body.doc_type = docType.value
    body.payment_kind = docType.value === 'payment_request' ? paymentKind.value : null
    body.parent_id = form.parent_id ? Number(form.parent_id) : null
  }
  return body
}

function mapLineErrors(errors) {
  const out = {}
  for (const [key, msgs] of Object.entries(errors || {})) {
    const m = key.match(/^lines\.(\d+)\./)
    if (m) out[Number(m[1])] = [out[Number(m[1])], ...(Array.isArray(msgs) ? msgs : [msgs])].filter(Boolean).join(' ')
  }
  return out
}

async function save() {
  if (saving.value) return
  saving.value = true
  saveError.value = ''
  lineErrors.value = {}
  try {
    const doc = docId.value ? await updateExpense(docId.value, payload()) : await createExpense(payload())
    showAppSuccess(t('expense.saved_msg', { code: doc.code }), t('expense.saved_title'))
    await router.replace({ name: 'driverExpenseDetail', params: { id: doc.id } })
  } catch (err) {
    const res = err?.response
    if (res?.status === 422 && res.data?.errors) {
      lineErrors.value = mapLineErrors(res.data.errors)
      saveError.value = Object.values(res.data.errors).flat()[0] || t('expense.save_error')
    } else {
      saveError.value = res?.data?.message || t('expense.save_error')
    }
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>
