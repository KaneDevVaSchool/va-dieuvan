<template>
  <div class="space-y-4">
    <p
      v-if="!canVehicles && !canDrivers"
      class="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400"
    >
      {{ t('compliance.no_permission') }}
    </p>

    <section
      v-else
      class="rounded-xl border border-slate-200/90 bg-white p-4 dark:border-slate-700 dark:bg-slate-900/50 sm:p-5"
    >
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ t('compliance.library_title') }}</h2>
          <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ t('compliance.library_subtitle') }}</p>
        </div>
        <div class="inline-flex rounded-lg border border-slate-200 p-0.5 dark:border-slate-600" role="tablist">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            role="tab"
            :aria-selected="ownerType === tab.value"
            class="rounded-md px-3 py-1.5 text-sm font-medium"
            :class="
              ownerType === tab.value
                ? 'bg-teal-600 text-white'
                : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800'
            "
            :data-testid="`compliance-library-tab-${tab.value}`"
            @click="switchTab(tab.value)"
          >
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- Thống kê nhanh theo tình trạng hạn (bấm để lọc) -->
      <div class="mt-4 flex flex-wrap gap-2">
        <button
          v-for="chip in summaryChips"
          :key="chip.state"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1"
          :class="[chip.class, filters.state === chip.state ? 'ring-2 ring-offset-1 ring-slate-400 dark:ring-offset-slate-900' : 'ring-transparent']"
          @click="toggleState(chip.state)"
        >
          {{ chip.label }}
          <span class="tabular-nums">{{ chip.count }}</span>
        </button>
      </div>

      <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <input
          v-model="filters.q"
          type="search"
          :placeholder="ownerType === 'vehicle' ? t('compliance.search_ph_vehicle') : t('compliance.search_ph_driver')"
          class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          data-testid="compliance-library-search"
        />
        <select
          v-model="filters.doc_type"
          :aria-label="t('compliance.filter_doc_type')"
          class="w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
        >
          <option value="">{{ t('compliance.filter_doc_type') }}: {{ t('compliance.all') }}</option>
          <option v-for="dt in docTypes" :key="dt" :value="dt">{{ docTypeLabel(dt) }}</option>
        </select>
        <select
          v-model="filters.state"
          :aria-label="t('compliance.filter_state')"
          class="w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
        >
          <option value="">{{ t('compliance.filter_state') }}: {{ t('compliance.all') }}</option>
          <option v-for="s in STATES" :key="s" :value="s">{{ t(`compliance.state_${s}`) }}</option>
        </select>
        <select
          v-model="filters.status"
          :aria-label="t('compliance.filter_status')"
          class="w-full rounded-lg border border-slate-200 py-2 pl-3 pr-8 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
        >
          <option value="active">{{ t('compliance.status_active') }}</option>
          <option value="superseded">{{ t('compliance.status_superseded') }}</option>
          <option value="all">{{ t('compliance.status_all') }}</option>
        </select>
      </div>

      <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[860px] border-separate border-spacing-0 text-left text-sm">
          <thead>
            <tr class="bg-slate-50 text-[11px] font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-400">
              <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">
                {{ ownerType === 'vehicle' ? t('compliance.col_owner_vehicle') : t('compliance.col_owner_driver') }}
              </th>
              <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('compliance.col_doc') }}</th>
              <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('compliance.col_issued') }}</th>
              <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('compliance.col_expires') }}</th>
              <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('compliance.col_status') }}</th>
              <th class="border-b border-slate-200 px-3 py-2 dark:border-slate-700">{{ t('compliance.col_files') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            <tr v-for="doc in items" :key="doc.id" :class="doc.status === 'superseded' ? 'opacity-75' : ''">
              <td class="px-3 py-2 align-top">
                <RouterLink
                  v-if="doc.owner"
                  :to="{ name: ownerType === 'vehicle' ? 'vehicleDetail' : 'driverDetail', params: { id: doc.owner.id } }"
                  class="font-medium text-teal-700 hover:underline dark:text-teal-400"
                  :class="ownerType === 'vehicle' ? 'font-mono' : ''"
                  :title="t('compliance.open_owner')"
                >
                  {{ doc.owner.label }}
                </RouterLink>
                <div v-if="doc.owner?.sub" class="text-xs text-slate-500">{{ doc.owner.sub }}</div>
              </td>
              <td class="max-w-[240px] px-3 py-2 align-top">
                <div class="text-slate-900 dark:text-slate-100">{{ docTypeLabel(doc.doc_type) }}</div>
                <div v-if="doc.title" class="truncate text-xs text-slate-600 dark:text-slate-400">{{ doc.title }}</div>
                <div v-if="doc.document_no" class="truncate text-xs text-slate-500">{{ doc.document_no }}</div>
              </td>
              <td class="whitespace-nowrap px-3 py-2 align-top text-xs">{{ formatIsoDate(doc.issued_at, locale) }}</td>
              <td class="whitespace-nowrap px-3 py-2 align-top text-xs">{{ formatIsoDate(doc.expires_at, locale) }}</td>
              <td class="px-3 py-2 align-top">
                <span :class="pillClass(doc.expiry?.state)">{{ stateLabel(doc.expiry) }}</span>
              </td>
              <td class="w-[300px] px-3 py-2 align-top">
                <ComplianceFileActions :attachments="doc.attachments" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-if="loading" class="mt-3 text-sm text-slate-500">{{ t('resources.loading') }}</p>
      <p v-else-if="!items.length" class="mt-3 text-sm text-slate-500">{{ t('compliance.empty') }}</p>

      <div v-if="meta.last_page > 1" class="mt-4 flex items-center justify-between gap-2 text-xs text-slate-600 dark:text-slate-400">
        <span>{{ t('compliance.page_info', { page: meta.current_page, last: meta.last_page, total: meta.total }) }}</span>
        <div class="flex gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-3 py-1.5 hover:bg-slate-50 disabled:opacity-40 dark:border-slate-600 dark:hover:bg-slate-800"
            :disabled="loading || meta.current_page <= 1"
            @click="goPage(meta.current_page - 1)"
          >
            {{ t('compliance.prev') }}
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-3 py-1.5 hover:bg-slate-50 disabled:opacity-40 dark:border-slate-600 dark:hover:bg-slate-800"
            :disabled="loading || meta.current_page >= meta.last_page"
            @click="goPage(meta.current_page + 1)"
          >
            {{ t('compliance.next') }}
          </button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { listComplianceLibrary } from '../../api/operational'
import { showAppErrorFromApi } from '../../composables/appMessage'
import { useAuthStore } from '../../store'
import { formatIsoDate } from '../../util/datetime'
import ComplianceFileActions from '../../components/compliance/ComplianceFileActions.vue'

const VEHICLE_DOC_TYPES = [
  'registration',
  'insurance_certificate',
  'inspection_certificate',
  'transport_permit',
  'ownership_proof',
  'lease_contract',
  'maintenance_record',
  'other',
]
const DRIVER_DOC_TYPES = [
  'id_card',
  'license',
  'medical_certificate',
  'criminal_record',
  'training_certificate',
  'labor_contract',
  'social_insurance',
  'other',
]
const STATES = ['exp', 'soon', 'ok', 'none']

const { t, te, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const canVehicles = computed(
  () => auth.hasPermission('resource.vehicle.manage') || auth.hasPermission('trip.assign'),
)
const canDrivers = computed(() => auth.hasPermission('resource.driver.manage'))

const tabs = computed(() =>
  [
    canVehicles.value && { value: 'vehicle', label: t('compliance.tab_vehicles') },
    canDrivers.value && { value: 'driver', label: t('compliance.tab_drivers') },
  ].filter(Boolean),
)

const ownerType = ref(route.query.tab === 'driver' && canDrivers.value ? 'driver' : canVehicles.value ? 'vehicle' : 'driver')
const filters = ref(emptyFilters())
const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 25, total: 0 })
const summary = ref({ exp: 0, soon: 0, ok: 0, none: 0, active: 0, superseded: 0 })
const loading = ref(false)
let requestSeq = 0
let searchTimer = null

function emptyFilters() {
  return { q: '', doc_type: '', state: '', status: 'active' }
}

const docTypes = computed(() => (ownerType.value === 'vehicle' ? VEHICLE_DOC_TYPES : DRIVER_DOC_TYPES))

function docTypeLabel(type) {
  const k = `${ownerType.value === 'vehicle' ? 'vehicle' : 'driver'}_compliance_doc_type.${type}`
  return te(k) ? t(k) : type
}

const summaryChips = computed(() => [
  { state: 'exp', label: t('compliance.state_exp'), count: summary.value.exp, class: pillClass('exp') },
  { state: 'soon', label: t('compliance.state_soon'), count: summary.value.soon, class: pillClass('soon') },
  { state: 'ok', label: t('compliance.state_ok'), count: summary.value.ok, class: pillClass('ok') },
  { state: 'none', label: t('compliance.state_none'), count: summary.value.none, class: pillClass('none') },
])

function pillClass(state) {
  const base = 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold '
  if (state === 'exp') return base + 'bg-rose-100 text-rose-900 dark:bg-rose-950/60 dark:text-rose-300'
  if (state === 'soon') return base + 'bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300'
  if (state === 'ok') return base + 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300'
  return base + 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'
}

function stateLabel(exp) {
  const s = exp?.state
  if (s === 'superseded') return t('compliance.superseded')
  if (s === 'soon') return t('resources.exp_in_days', { n: exp.days })
  if (s === 'exp' || s === 'ok' || s === 'none') return t(`compliance.state_${s}`)
  return '—'
}

async function load(page = 1) {
  const seq = ++requestSeq
  loading.value = true
  try {
    const f = filters.value
    const res = await listComplianceLibrary({
      owner_type: ownerType.value,
      q: f.q.trim() || undefined,
      doc_type: f.doc_type || undefined,
      state: f.state || undefined,
      status: f.status,
      page,
      per_page: 25,
    })
    if (seq !== requestSeq) return
    items.value = res.items || []
    meta.value = res.meta || meta.value
    summary.value = res.summary || summary.value
  } catch (e) {
    if (seq === requestSeq) showAppErrorFromApi(e, t('resources.load_error'))
  } finally {
    if (seq === requestSeq) loading.value = false
  }
}

function goPage(p) {
  load(p)
}

function toggleState(state) {
  filters.value.state = filters.value.state === state ? '' : state
}

function switchTab(value) {
  if (ownerType.value === value) return
  ownerType.value = value
  filters.value = emptyFilters()
  router.replace({ query: { ...route.query, tab: value } })
}

watch(
  () => [ownerType.value, filters.value.doc_type, filters.value.state, filters.value.status],
  () => load(1),
)
watch(
  () => filters.value.q,
  () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => load(1), 350)
  },
)

onMounted(() => {
  if (canVehicles.value || canDrivers.value) load(1)
})
</script>
