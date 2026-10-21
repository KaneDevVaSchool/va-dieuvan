<template>
  <div class="min-h-full w-full bg-driver-bg pb-4 text-driver-ink">
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
          <h1 class="truncate text-lg font-bold">{{ doc?.code || t('expense.page_title') }}</h1>
          <p v-if="doc" class="truncate text-xs text-driver-muted">{{ doc.type_label }}</p>
        </div>
        <span class="w-[48px] shrink-0" aria-hidden="true" />
      </div>
    </header>

    <div class="mx-auto max-w-lg space-y-4 px-4 pt-4 sm:max-w-2xl">
      <p v-if="loadError" class="rounded-xl bg-amber-500/15 px-3 py-2 text-sm text-amber-100">{{ loadError }}</p>
      <p v-if="!doc && !loadError" class="text-sm text-driver-muted">{{ t('expense.loading') }}</p>

      <template v-if="doc">
        <section class="rounded-[1.25rem] bg-driver-card p-4 ring-1 ring-white/[0.06]">
          <div class="flex items-center justify-between gap-2">
            <span class="rounded-full px-3 py-1 text-sm font-bold" :class="EXPENSE_STATUS_PILL_DRIVER[doc.status]">{{ t(`expense.status_${doc.status}`) }}</span>
            <span class="text-xl font-bold tabular-nums">{{ formatVndCurrency(doc.total_amount) }}</span>
          </div>
          <p class="mt-1 text-right text-xs text-driver-muted">{{ doc.amount_in_words }}</p>
          <p class="mt-2 text-sm text-driver-muted">{{ t(`expense.status_${doc.status}_hint`) }}</p>
          <p v-if="doc.status === 'rejected' && doc.rejected_reason" class="mt-2 rounded-lg bg-amber-500/15 px-3 py-2 text-sm text-amber-100">
            {{ t('expense.rejected_reason') }}: {{ doc.rejected_reason }}
          </p>

          <dl class="mt-3 space-y-1.5 text-sm">
            <div v-if="doc.purpose" class="flex gap-2"><dt class="w-28 shrink-0 text-driver-muted">{{ t('expense.purpose') }}</dt><dd>{{ doc.purpose }}</dd></div>
            <div v-if="doc.basis" class="flex gap-2"><dt class="w-28 shrink-0 text-driver-muted">{{ t('expense.basis') }}</dt><dd>{{ doc.basis }}</dd></div>
            <div class="flex gap-2"><dt class="w-28 shrink-0 text-driver-muted">{{ t('expense.requester_name') }}</dt><dd>{{ doc.requester_name }} · {{ doc.department_label }}</dd></div>
            <div v-if="doc.vehicle" class="flex gap-2"><dt class="w-28 shrink-0 text-driver-muted">{{ t('expense.vehicle_main') }}</dt><dd>{{ doc.vehicle.license_plate }}</dd></div>
            <div v-if="doc.payment_method" class="flex gap-2"><dt class="w-28 shrink-0 text-driver-muted">{{ t('expense.payment_method') }}</dt><dd>{{ t(`expense.method_${doc.payment_method}`) }} {{ doc.bank_account_no ? `· ${doc.bank_account_no} ${doc.bank_name || ''}` : '' }}</dd></div>
          </dl>

          <div v-if="doc.parent || doc.children?.length" class="mt-3 flex flex-wrap gap-2">
            <RouterLink v-if="doc.parent" :to="{ name: 'driverExpenseDetail', params: { id: doc.parent.id } }" class="rounded-lg bg-driver-surface px-2.5 py-1.5 text-xs font-semibold text-driver-accent ring-1 ring-white/10">
              ← {{ doc.parent.code }}
            </RouterLink>
            <RouterLink v-for="c in doc.children" :key="c.id" :to="{ name: 'driverExpenseDetail', params: { id: c.id } }" class="rounded-lg bg-driver-surface px-2.5 py-1.5 text-xs font-semibold text-driver-accent ring-1 ring-white/10">
              {{ c.code }} · {{ c.type_label }} →
            </RouterLink>
          </div>
        </section>

        <!-- Hành động -->
        <section class="grid grid-cols-2 gap-2">
          <RouterLink
            v-if="doc.can?.edit"
            :to="{ name: 'driverExpenseEdit', params: { id: doc.id } }"
            class="flex min-h-[52px] items-center justify-center gap-1.5 rounded-xl bg-driver-card text-sm font-bold text-driver-accent ring-1 ring-white/10"
          >
            <PencilSquareIcon class="h-5 w-5" /> {{ t('expense.edit') }}
          </RouterLink>
          <button
            v-if="doc.can?.submit"
            type="button"
            class="flex min-h-[52px] items-center justify-center gap-1.5 rounded-xl bg-driver-accent text-sm font-bold text-driver-bg disabled:opacity-45"
            :disabled="busy"
            data-testid="expense-submit"
            @click="submit"
          >
            <PaperAirplaneIcon class="h-5 w-5" /> {{ t('expense.submit') }}
          </button>
          <button type="button" class="flex min-h-[52px] items-center justify-center gap-1.5 rounded-xl bg-driver-card text-sm font-bold ring-1 ring-white/10" :disabled="busy" @click="download('pdf')">
            <ArrowDownTrayIcon class="h-5 w-5" /> PDF
          </button>
          <button type="button" class="flex min-h-[52px] items-center justify-center gap-1.5 rounded-xl bg-driver-card text-sm font-bold ring-1 ring-white/10" :disabled="busy" @click="download('xlsx')">
            <ArrowDownTrayIcon class="h-5 w-5" /> Excel
          </button>
          <label
            v-if="doc.can?.upload_scan"
            class="col-span-2 flex min-h-[52px] cursor-pointer items-center justify-center gap-1.5 rounded-xl bg-driver-card text-sm font-bold text-driver-accent ring-1 ring-white/10"
            :class="busy ? 'opacity-50' : ''"
          >
            <CameraIcon class="h-5 w-5" /> {{ t('expense.upload_scan') }}
            <input type="file" accept="image/*,application/pdf" class="sr-only" :disabled="busy" @change="uploadScan" />
          </label>
          <RouterLink
            v-for="next in followUps"
            :key="next.key"
            :to="{ name: 'driverExpenseCreate', query: next.query }"
            class="col-span-2 flex min-h-[52px] items-center justify-center gap-1.5 rounded-xl bg-driver-card text-sm font-bold text-driver-accent ring-1 ring-white/10"
          >
            <DocumentPlusIcon class="h-5 w-5" /> {{ t(next.label) }}
          </RouterLink>
          <button v-if="doc.can?.cancel" type="button" class="col-span-2 min-h-[44px] text-sm font-semibold text-driver-muted" :disabled="busy" @click="cancel">
            {{ t('expense.cancel_doc') }}
          </button>
        </section>

        <!-- Bản scan -->
        <section v-if="scans.length" class="rounded-[1.25rem] bg-driver-card p-4 ring-1 ring-white/[0.06]">
          <h2 class="text-sm font-bold uppercase tracking-wide text-driver-muted">{{ t('expense.scans') }}</h2>
          <div class="mt-2 flex flex-wrap gap-2">
            <AuthAttachmentThumb v-for="a in scans" :key="a.id" :attachment="a" dark />
          </div>
        </section>

        <!-- Dòng -->
        <section class="rounded-[1.25rem] bg-driver-card p-3 ring-1 ring-white/[0.06]">
          <h2 class="px-1 pb-2 text-sm font-bold uppercase tracking-wide text-driver-muted">{{ t('expense.sec_lines') }}</h2>
          <ul class="space-y-2">
            <li v-for="(l, i) in doc.lines" :key="l.id" class="rounded-xl bg-driver-surface p-3 ring-1 ring-white/[0.06]">
              <div class="flex items-start justify-between gap-2">
                <p class="min-w-0 text-sm font-semibold">{{ i + 1 }}. {{ l.category || t(`expense.cost_type_${l.cost_type}`, l.cost_type || '') }}</p>
                <p class="shrink-0 text-sm font-bold tabular-nums">{{ formatVndCurrency(l.amount) }}</p>
              </div>
              <p v-if="l.description" class="mt-0.5 text-xs text-driver-muted">{{ l.description }}</p>
              <p class="mt-1 text-xs text-driver-muted">
                {{ [l.license_plate, l.purchased_on ? formatDate(l.purchased_on) : null, l.quantity !== 1 ? `${l.quantity} ${l.unit || ''} × ${formatVndCurrency(l.unit_price)}` : null].filter(Boolean).join(' · ') }}
              </p>
              <p v-if="!l.has_invoice && doc.uses_trip_costs" class="mt-1 text-xs text-amber-200">{{ t('expense.no_invoice_badge') }} · {{ l.seller_name }}</p>
            </li>
          </ul>
        </section>
      </template>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  CameraIcon,
  DocumentPlusIcon,
  PaperAirplaneIcon,
  PencilSquareIcon,
} from '@heroicons/vue/24/outline'
import { cancelMyExpense, downloadExpenseFile, getMyExpense, submitExpense, uploadExpenseScan } from '../../api/expenses'
import { EXPENSE_STATUS_PILL_DRIVER } from '../../constants/checklistExpense'
import { confirmAction } from '../../composables/useConfirm'
import { showAppError, showAppErrorFromApi, showAppSuccess } from '../../composables/appMessage'
import { formatVndCurrency } from '../../util/money'
import AuthAttachmentThumb from '../../components/shared/AuthAttachmentThumb.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const doc = ref(null)
const loadError = ref('')
const busy = ref(false)

const scans = computed(() => (doc.value?.attachments ?? []).filter((a) => a.kind === 'signed_scan'))

/** Chứng từ tiếp theo có thể lập từ hồ sơ này. */
const followUps = computed(() => {
  const d = doc.value
  if (!d || d.status === 'cancelled') return []
  if (d.doc_type === 'proposal') {
    return [
      { key: 'pay', label: 'expense.follow_payment', query: { type: 'payment_request', kind: 'payment', parent: d.id } },
      { key: 'adv', label: 'expense.follow_advance', query: { type: 'payment_request', kind: 'advance', parent: d.id } },
    ]
  }
  if (d.doc_type === 'payment_request' && d.payment_kind === 'advance') {
    return [{ key: 'reimb', label: 'expense.follow_reimbursement', query: { type: 'payment_request', kind: 'reimbursement', parent: d.id } }]
  }
  return []
})

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push({ name: 'driverExpenses' })
}

function formatDate(ymd) {
  return ymd ? new Date(`${ymd}T00:00:00`).toLocaleDateString('vi-VN') : ''
}

async function load() {
  loadError.value = ''
  try {
    doc.value = await getMyExpense(route.params.id)
  } catch (err) {
    loadError.value = err?.response?.data?.message || t('expense.load_error')
  }
}

async function submit() {
  const ok = await confirmAction({ title: t('expense.submit'), message: t('expense.submit_confirm') })
  if (!ok) return
  busy.value = true
  try {
    doc.value = await submitExpense(doc.value.id, doc.value.lock_version)
    showAppSuccess(t('expense.submitted_msg'), t('expense.submitted_title'))
  } catch (err) {
    const errors = err?.response?.data?.errors
    if (errors) showAppError(Object.values(errors).flat().join('\n'), t('expense.submit_invalid'))
    else showAppErrorFromApi(err, t('expense.save_error'))
  } finally {
    busy.value = false
  }
}

async function download(format) {
  busy.value = true
  try {
    await downloadExpenseFile(doc.value, format, 'driver')
  } catch {
    showAppError(t('expense.download_error'))
  } finally {
    busy.value = false
  }
}

async function uploadScan(ev) {
  const file = ev.target?.files?.[0]
  ev.target.value = ''
  if (!file) return
  busy.value = true
  try {
    doc.value = await uploadExpenseScan(doc.value.id, file, 'driver')
    showAppSuccess(t('expense.scan_uploaded'))
  } catch (err) {
    showAppErrorFromApi(err, t('expense.save_error'))
  } finally {
    busy.value = false
  }
}

async function cancel() {
  const ok = await confirmAction({ title: t('expense.cancel_doc'), message: t('expense.cancel_confirm'), danger: true })
  if (!ok) return
  busy.value = true
  try {
    doc.value = await cancelMyExpense(doc.value.id)
  } catch (err) {
    showAppErrorFromApi(err, t('expense.save_error'))
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
