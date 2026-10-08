<template>
  <div class="space-y-4">
    <section class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5">
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('expense.staff_title') }}</h2>
          <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('expense.staff_subtitle') }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <RouterLink :to="{ name: 'vehicleCostLedger' }" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">
            <TruckIcon class="h-4 w-4" /> {{ t('expense.ledger_title') }}
          </RouterLink>
          <RouterLink v-if="auth.hasPermission('expense.manage')" :to="{ name: 'expenseSettings' }" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800">
            <Cog6ToothIcon class="h-4 w-4" /> {{ t('expense.settings_title') }}
          </RouterLink>
        </div>
      </div>

      <div class="mt-4 flex flex-wrap gap-2">
        <button
          v-for="s in statusChips"
          :key="s.value"
          type="button"
          class="rounded-full px-3 py-1 text-xs font-semibold ring-1"
          :class="filters.status === s.value ? 'bg-teal-600 text-white ring-teal-600' : 'bg-white text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-600'"
          @click="filters.status = s.value; load(1)"
        >
          {{ s.label }}
        </button>
      </div>

      <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <input v-model="filters.q" type="search" :placeholder="t('expense.search_ph')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 lg:col-span-2" @keyup.enter="load(1)" />
        <select v-model="filters.type" :aria-label="t('expense.filter_type')" class="rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" @change="load(1)">
          <option value="">{{ t('expense.all_types') }}</option>
          <option v-for="opt in EXPENSE_DOC_TYPES" :key="opt.key" :value="opt.key">{{ t(`expense.type_${opt.key}`) }}</option>
        </select>
        <input v-model="filters.date_from" type="date" :aria-label="t('expense.filter_from')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" @change="load(1)" />
        <input v-model="filters.date_to" type="date" :aria-label="t('expense.filter_to')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" @change="load(1)" />
      </div>

      <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[860px] border-separate border-spacing-0 text-left text-sm">
          <thead>
            <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400">
              <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('expense.col_code') }}</th>
              <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('expense.col_type') }}</th>
              <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('expense.col_requester') }}</th>
              <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('expense.col_content') }}</th>
              <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('expense.col_date') }}</th>
              <th class="border-b border-slate-200 px-3 py-2 text-right dark:border-slate-700">{{ t('expense.col_amount') }}</th>
              <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('expense.col_status') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-for="d in items" :key="d.id" class="cursor-pointer hover:bg-slate-50/60 dark:hover:bg-slate-800/40" @click="router.push({ name: 'expenseDetail', params: { id: d.id } })">
              <td class="px-3 py-2 font-mono text-xs text-teal-700 dark:text-teal-400">{{ d.code }}</td>
              <td class="px-3 py-2 text-xs">{{ d.type_label }}</td>
              <td class="px-3 py-2">{{ d.requester_name }}</td>
              <td class="max-w-[280px] px-3 py-2">
                <div class="truncate">{{ d.purpose || '—' }}</div>
                <div class="text-xs text-slate-500">{{ d.vehicle?.license_plate }} {{ d.parent ? `· ← ${d.parent.code}` : '' }}</div>
              </td>
              <td class="whitespace-nowrap px-3 py-2 text-xs">{{ formatDate(d.proposed_on) }}</td>
              <td class="whitespace-nowrap px-3 py-2 text-right font-semibold tabular-nums">{{ formatVndCurrency(d.total_amount) }}</td>
              <td class="px-3 py-2"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1" :class="EXPENSE_STATUS_PILL[d.status]">{{ t(`expense.status_${d.status}`) }}</span></td>
            </tr>
          </tbody>
        </table>
        <p v-if="loading" class="mt-3 text-sm text-slate-500">{{ t('expense.loading') }}</p>
        <p v-else-if="!items.length" class="mt-3 text-sm text-slate-500">{{ t('expense.empty') }}</p>
      </div>
      <div v-if="meta.last_page > 1" class="mt-4 flex items-center justify-between gap-2 text-xs text-slate-600 dark:text-slate-400">
        <span>{{ t('expense.page_info', { page: meta.current_page, last: meta.last_page, total: meta.total }) }}</span>
        <div class="flex gap-2">
          <button type="button" class="rounded-md border border-slate-200 px-3 py-1 disabled:opacity-40 dark:border-slate-600" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">‹</button>
          <button type="button" class="rounded-md border border-slate-200 px-3 py-1 disabled:opacity-40 dark:border-slate-600" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">›</button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { Cog6ToothIcon, TruckIcon } from '@heroicons/vue/24/outline'
import { listExpenses } from '../../api/expenses'
import { EXPENSE_DOC_TYPES, EXPENSE_STATUS_PILL } from '../../constants/checklistExpense'
import { showAppErrorFromApi } from '../../composables/appMessage'
import { useAuthStore } from '../../store'
import { formatVndCurrency } from '../../util/money'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(false)
const filters = reactive({ q: '', status: 'pending', type: '', date_from: '', date_to: '' })

const statusChips = computed(() => [
  { value: 'pending', label: t('expense.chip_pending') },
  { value: 'rejected', label: t('expense.status_rejected') },
  { value: 'paid', label: t('expense.status_paid') },
  { value: 'draft', label: t('expense.status_draft') },
  { value: '', label: t('expense.tab_all') },
])

function formatDate(ymd) {
  return ymd ? new Date(`${ymd}T00:00:00`).toLocaleDateString('vi-VN') : ''
}

async function load(page = 1) {
  loading.value = true
  try {
    const opt = EXPENSE_DOC_TYPES.find((x) => x.key === filters.type)
    const params = {
      page,
      per_page: 30,
      q: filters.q || undefined,
      date_from: filters.date_from || undefined,
      date_to: filters.date_to || undefined,
      doc_type: opt?.docType,
      payment_kind: opt?.kind || undefined,
      status: filters.status === 'pending' ? ['submitted', 'signed'] : filters.status ? [filters.status] : undefined,
    }
    const res = await listExpenses(params)
    items.value = res.items
    meta.value = res.meta
  } catch (err) {
    showAppErrorFromApi(err, t('expense.load_error'))
  } finally {
    loading.value = false
  }
}

onMounted(() => load(1))
</script>
