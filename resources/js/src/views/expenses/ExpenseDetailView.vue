<template>
  <div class="space-y-3 sm:space-y-4">
    <RouterLink :to="{ name: 'expenses' }" class="inline-flex items-center gap-1 text-sm font-medium text-teal-700 hover:underline dark:text-teal-400">
      <ArrowLeftIcon class="h-4 w-4" /> {{ t('expense.back_to_list') }}
    </RouterLink>
    <p v-if="loadError" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">{{ loadError }}</p>

    <template v-if="doc">
      <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ doc.code }} · {{ doc.type_label }}</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ doc.requester_name }} · {{ doc.department_label }} · {{ doc.requester_phone }}</p>
            <div class="mt-2 flex flex-wrap items-center gap-2">
              <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1" :class="EXPENSE_STATUS_PILL[doc.status]">{{ t(`expense.status_${doc.status}`) }}</span>
              <RouterLink v-if="doc.parent" :to="{ name: 'expenseDetail', params: { id: doc.parent.id } }" class="text-xs text-teal-700 hover:underline dark:text-teal-400">← {{ doc.parent.code }}</RouterLink>
              <RouterLink v-for="c in doc.children" :key="c.id" :to="{ name: 'expenseDetail', params: { id: c.id } }" class="text-xs text-teal-700 hover:underline dark:text-teal-400">{{ c.code }} ({{ c.type_label }}) →</RouterLink>
            </div>
          </div>
          <div class="text-right">
            <p class="text-2xl font-bold tabular-nums text-slate-900 dark:text-white">{{ formatVndCurrency(doc.total_amount) }}</p>
            <p class="text-xs text-slate-500">{{ doc.amount_in_words }}</p>
          </div>
        </div>

        <p v-if="doc.rejected_reason && doc.status === 'rejected'" class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
          {{ t('expense.rejected_reason') }}: {{ doc.rejected_reason }}
        </p>

        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
          <div class="sm:col-span-2"><dt class="text-xs text-slate-500">{{ t('expense.purpose') }}</dt><dd>{{ doc.purpose || '—' }}</dd></div>
          <div><dt class="text-xs text-slate-500">{{ t('expense.basis') }}</dt><dd>{{ doc.basis || '—' }}</dd></div>
          <div><dt class="text-xs text-slate-500">{{ t('expense.proposed_on') }}</dt><dd>{{ formatDate(doc.proposed_on) }}</dd></div>
          <div v-if="doc.needed_by"><dt class="text-xs text-slate-500">{{ t('expense.needed_by') }}</dt><dd>{{ formatDate(doc.needed_by) }} <span v-if="doc.is_urgent" class="font-semibold text-rose-600">· {{ t('expense.urgent') }}</span></dd></div>
          <div v-if="doc.payment_method"><dt class="text-xs text-slate-500">{{ t('expense.payment_method') }}</dt><dd>{{ t(`expense.method_${doc.payment_method}`) }} {{ doc.bank_account_no ? `· ${doc.bank_account_no} — ${doc.bank_name || ''} ${doc.bank_account_holder || ''}` : '' }}</dd></div>
          <div><dt class="text-xs text-slate-500">{{ t('expense.submitted_label') }}</dt><dd>{{ dateTime(doc.submitted_at) || '—' }}</dd></div>
          <div v-if="doc.paid_at"><dt class="text-xs text-slate-500">{{ t('expense.paid_label') }}</dt><dd>{{ dateTime(doc.paid_at) }}</dd></div>
        </dl>

        <div class="mt-4 flex flex-wrap gap-2">
          <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-600 dark:text-slate-200" :disabled="busy" @click="download('pdf')"><ArrowDownTrayIcon class="h-4 w-4" /> PDF</button>
          <button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-600 dark:text-slate-200" :disabled="busy" @click="download('xlsx')"><ArrowDownTrayIcon class="h-4 w-4" /> Excel</button>
          <label v-if="doc.can?.upload_scan" class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-600 dark:text-slate-200">
            <ArrowUpTrayIcon class="h-4 w-4" /> {{ t('expense.upload_scan') }}
            <input type="file" accept="image/*,application/pdf" class="sr-only" :disabled="busy" @change="uploadScan" />
          </label>
          <span class="flex-1" />
          <button v-if="doc.can?.reject" type="button" class="rounded-lg border border-amber-300 px-3 py-1.5 text-sm font-semibold text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300" :disabled="busy" @click="rejectOpen = !rejectOpen">
            {{ t('expense.reject') }}
          </button>
          <button v-if="doc.can?.mark_paid" type="button" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50" :disabled="busy" @click="markPaid">
            {{ t('expense.mark_paid') }}
          </button>
          <button v-if="doc.can?.cancel" type="button" class="rounded-lg px-3 py-1.5 text-sm text-slate-500 hover:text-rose-600" :disabled="busy" @click="cancel">
            {{ t('expense.cancel_doc') }}
          </button>
        </div>
        <div v-if="rejectOpen" class="mt-3 flex flex-wrap gap-2">
          <input v-model="rejectReason" type="text" maxlength="255" :placeholder="t('expense.reject_reason_ph')" class="min-w-[260px] flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
          <button type="button" class="rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white disabled:opacity-50" :disabled="busy || !rejectReason.trim()" @click="reject">{{ t('expense.reject_confirm') }}</button>
        </div>
      </section>

      <section v-if="scans.length" class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50">
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('expense.scans') }}</h3>
        <div class="mt-2 flex flex-wrap gap-2"><AuthAttachmentThumb v-for="a in scans" :key="a.id" :attachment="a" size="lg" /></div>
      </section>

      <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ t('expense.sec_lines') }}</h3>
        <div class="mt-3 overflow-x-auto">
          <table class="w-full min-w-[860px] border-separate border-spacing-0 text-left text-sm">
            <thead>
              <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">#</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('expense.line_category') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('expense.line_vehicle') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('expense.line_date') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 text-right dark:border-slate-700">{{ t('expense.line_qty') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 text-right dark:border-slate-700">{{ t('expense.line_unit_price') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 text-right dark:border-slate-700">{{ t('expense.line_amount') }}</th>
                <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('expense.col_invoice') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="(l, i) in doc.lines" :key="l.id">
                <td class="px-3 py-2 align-top text-xs text-slate-500">{{ i + 1 }}</td>
                <td class="px-3 py-2 align-top">
                  <div class="font-medium">{{ l.category || t(`expense.cost_type_${l.cost_type}`, l.cost_type || '') }}</div>
                  <div class="text-xs text-slate-500">{{ l.description }}</div>
                  <div v-if="l.trip_cost_id" class="text-[11px] text-slate-400">#{{ l.trip_cost_id }} · {{ l.trip_cost_status }}</div>
                </td>
                <td class="px-3 py-2 align-top font-mono text-xs">{{ l.license_plate }}</td>
                <td class="whitespace-nowrap px-3 py-2 align-top text-xs">{{ formatDate(l.purchased_on) }}</td>
                <td class="px-3 py-2 text-right align-top tabular-nums">{{ l.quantity }} {{ l.unit }}</td>
                <td class="px-3 py-2 text-right align-top tabular-nums">{{ formatVndCurrency(l.unit_price, '') }}</td>
                <td class="px-3 py-2 text-right align-top font-semibold tabular-nums">{{ formatVndCurrency(l.amount, '') }}</td>
                <td class="px-3 py-2 align-top text-xs">
                  <template v-if="doc.uses_trip_costs">
                    <span v-if="l.has_invoice">{{ t('expense.yes') }}</span>
                    <span v-else class="text-amber-700 dark:text-amber-300">{{ t('expense.no_invoice_badge') }}<br />{{ l.seller_name }} {{ l.seller_address ? `· ${l.seller_address}` : '' }}</span>
                  </template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          <h4 class="text-xs font-semibold uppercase text-slate-500">{{ t('expense.sec_signatories') }}</h4>
          <p class="text-sm">{{ (doc.signatories || []).map((s) => `${s.role}: ${s.name || '—'}`).join(' · ') }}</p>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { ArrowDownTrayIcon, ArrowLeftIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/outline'
import { cancelExpense, downloadExpenseFile, getExpense, markExpensePaid, rejectExpense, uploadExpenseScan } from '../../api/expenses'
import { EXPENSE_STATUS_PILL } from '../../constants/checklistExpense'
import { confirmAction } from '../../composables/useConfirm'
import { showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { formatVndCurrency } from '../../util/money'
import AuthAttachmentThumb from '../../components/shared/AuthAttachmentThumb.vue'

const { t } = useI18n()
const route = useRoute()

const doc = ref(null)
const loadError = ref('')
const busy = ref(false)
const rejectOpen = ref(false)
const rejectReason = ref('')

const scans = computed(() => (doc.value?.attachments ?? []).filter((a) => a.kind === 'signed_scan'))

function formatDate(ymd) {
  return ymd ? new Date(`${ymd}T00:00:00`).toLocaleDateString('vi-VN') : ''
}
function dateTime(iso) {
  return iso ? new Date(iso).toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''
}

async function load() {
  loadError.value = ''
  try {
    doc.value = await getExpense(route.params.id)
  } catch (err) {
    loadError.value = err?.response?.data?.message || t('expense.load_error')
  }
}

async function act(fn, msg) {
  busy.value = true
  try {
    doc.value = await fn()
    if (msg) showAppSuccess(msg)
  } catch (err) {
    showAppErrorFromApi(err, t('expense.save_error'))
  } finally {
    busy.value = false
  }
}

async function markPaid() {
  const ok = await confirmAction({ title: t('expense.mark_paid'), message: t('expense.mark_paid_confirm', { code: doc.value.code }) })
  if (ok) await act(() => markExpensePaid(doc.value.id), t('expense.mark_paid_done'))
}

async function reject() {
  await act(() => rejectExpense(doc.value.id, rejectReason.value.trim()), t('expense.reject_done'))
  rejectOpen.value = false
  rejectReason.value = ''
}

async function cancel() {
  const ok = await confirmAction({ title: t('expense.cancel_doc'), message: t('expense.cancel_confirm'), danger: true })
  if (ok) await act(() => cancelExpense(doc.value.id))
}

async function uploadScan(ev) {
  const file = ev.target?.files?.[0]
  ev.target.value = ''
  if (file) await act(() => uploadExpenseScan(doc.value.id, file, 'staff'), t('expense.scan_uploaded'))
}

async function download(format) {
  busy.value = true
  try {
    await downloadExpenseFile(doc.value, format, 'staff')
  } catch (err) {
    showAppErrorFromApi(err, t('expense.download_error'))
  } finally {
    busy.value = false
  }
}

onMounted(load)
watch(
  () => route.params.id,
  (id, prev) => {
    if (id && id !== prev) void load()
  },
)
</script>
