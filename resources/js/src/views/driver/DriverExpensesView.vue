<template>
  <div class="min-h-full w-full bg-driver-bg pb-[calc(6rem+env(safe-area-inset-bottom))] text-driver-ink">
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
        <h1 class="min-w-0 flex-1 truncate text-center text-lg font-bold sm:text-xl">{{ t('expense.page_title') }}</h1>
        <span class="w-[48px] shrink-0" aria-hidden="true" />
      </div>
    </header>

    <div class="mx-auto max-w-lg space-y-4 px-4 pt-4 sm:max-w-2xl">
      <p class="text-sm leading-snug text-driver-muted">{{ t('expense.page_intro') }}</p>

      <section class="rounded-[1.25rem] bg-driver-card p-4 ring-1 ring-white/[0.06]">
        <h2 class="text-base font-bold">{{ t('expense.create_title') }}</h2>
        <div class="mt-3 grid grid-cols-2 gap-2">
          <RouterLink
            v-for="opt in EXPENSE_DOC_TYPES"
            :key="opt.key"
            :to="{ name: 'driverExpenseCreate', query: { type: opt.docType, kind: opt.kind || undefined } }"
            class="flex min-h-[64px] flex-col justify-center rounded-xl bg-driver-surface px-3 py-2 ring-1 ring-white/10 active:scale-[0.99]"
            :class="opt.key === 'no_invoice_statement' ? 'col-span-2' : ''"
            :data-testid="`expense-create-${opt.key}`"
          >
            <span class="text-sm font-bold">{{ t(`expense.type_${opt.key}`) }}</span>
            <span class="text-xs text-driver-muted">{{ t(`expense.type_${opt.key}_hint`) }}</span>
          </RouterLink>
        </div>
      </section>

      <div class="-mx-1 flex gap-1.5 overflow-x-auto px-1 pb-1 scrollbar-hidden" role="tablist">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          type="button"
          role="tab"
          :aria-selected="activeTab === tab.value"
          class="min-h-[40px] shrink-0 rounded-full px-3.5 text-sm font-semibold ring-1 transition"
          :class="activeTab === tab.value ? 'bg-driver-accent text-driver-bg ring-driver-accent' : 'bg-driver-card text-driver-muted ring-white/10'"
          @click="switchTab(tab.value)"
        >
          {{ tab.label }}
        </button>
      </div>

      <p v-if="loading && !items.length" class="text-sm text-driver-muted">{{ t('expense.loading') }}</p>
      <p v-else-if="!items.length" class="rounded-xl bg-driver-card p-4 text-sm text-driver-muted ring-1 ring-white/[0.06]">{{ t('expense.empty') }}</p>

      <ul class="space-y-2">
        <li v-for="doc in items" :key="doc.id">
          <RouterLink
            :to="{ name: 'driverExpenseDetail', params: { id: doc.id } }"
            class="block rounded-xl bg-driver-card p-3 ring-1 ring-white/[0.06] active:scale-[0.99]"
          >
            <div class="flex items-center justify-between gap-2">
              <span class="truncate text-sm font-bold">{{ doc.code }}</span>
              <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold" :class="EXPENSE_STATUS_PILL_DRIVER[doc.status]">
                {{ t(`expense.status_${doc.status}`) }}
              </span>
            </div>
            <p class="mt-1 truncate text-sm">{{ doc.type_label }} · {{ doc.purpose || '—' }}</p>
            <div class="mt-1 flex items-center justify-between text-xs text-driver-muted">
              <span>{{ doc.vehicle?.license_plate || '' }} {{ doc.proposed_on ? `· ${formatDate(doc.proposed_on)}` : '' }}</span>
              <span class="text-sm font-bold tabular-nums text-driver-ink">{{ formatVndCurrency(doc.total_amount) }}</span>
            </div>
          </RouterLink>
        </li>
      </ul>

      <button
        v-if="meta.current_page < meta.last_page"
        type="button"
        class="min-h-[44px] w-full rounded-xl bg-driver-card text-sm font-semibold text-driver-accent ring-1 ring-white/10"
        :disabled="loading"
        @click="load(meta.current_page + 1)"
      >
        {{ t('expense.load_more') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import { listMyExpenses } from '../../api/expenses'
import { EXPENSE_DOC_TYPES, EXPENSE_STATUS_PILL_DRIVER } from '../../constants/checklistExpense'
import { formatVndCurrency } from '../../util/money'

const { t } = useI18n()
const router = useRouter()

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1 })
const loading = ref(false)
const activeTab = ref('open')

const tabs = computed(() => [
  { value: 'open', label: t('expense.tab_open'), status: ['draft', 'submitted', 'signed', 'rejected'] },
  { value: 'paid', label: t('expense.status_paid'), status: ['paid'] },
  { value: 'all', label: t('expense.tab_all'), status: [] },
])

function goBack() {
  if (window.history.length > 1) router.back()
  else router.push({ name: 'driverCosts' })
}

function formatDate(ymd) {
  return ymd ? new Date(`${ymd}T00:00:00`).toLocaleDateString('vi-VN') : ''
}

function switchTab(v) {
  activeTab.value = v
  void load(1)
}

async function load(page = 1) {
  loading.value = true
  try {
    const status = tabs.value.find((x) => x.value === activeTab.value)?.status ?? []
    const res = await listMyExpenses({ page, per_page: 20, status: status.length ? status : undefined })
    items.value = page === 1 ? res.items : [...items.value, ...res.items]
    meta.value = res.meta
  } catch {
    if (page === 1) items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => load(1))
</script>
