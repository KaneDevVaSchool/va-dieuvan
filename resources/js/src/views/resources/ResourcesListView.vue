<template>
  <div
    class="resources-shell rounded-xl border border-slate-200 bg-white text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
  >
    <!-- Header -->
    <div class="border-b border-slate-200 px-4 py-4 sm:px-5 dark:border-slate-700">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">{{ t('resources.page_title') }}</h1>
          <p class="mt-0.5 text-xs text-slate-600 dark:text-slate-400">{{ t('resources.page_subtitle') }}</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <div class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-0.5 dark:border-slate-600 dark:bg-slate-800">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              type="button"
              :class="[
                'rounded-md px-3 py-1.5 text-xs font-medium transition',
                activeTab === tab.id
                  ? 'bg-teal-600 text-white shadow-sm'
                  : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
              ]"
              @click="setTab(tab.id)"
            >
              {{ t(tab.labelKey) }}
            </button>
          </div>
          <div class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
            <input
              v-model="search"
              type="search"
              class="w-full min-w-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 sm:min-w-[200px] dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500"
              :placeholder="searchPlaceholder"
            />
            <button
              type="button"
              class="inline-flex shrink-0 items-center justify-center gap-1.5 rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-teal-500"
            >
              <span class="text-lg leading-none">+</span>
              {{ addButtonLabel }}
            </button>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="mt-4 grid gap-3 sm:grid-cols-3">
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
          {{ t('resources.filter_status') }}
          <select
            v-model="filters.status"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          >
            <option value="">{{ t('resources.filter_all') }}</option>
            <option value="active">{{ t('resources.status_active') }}</option>
            <option value="maintenance">{{ t('resources.status_maintenance') }}</option>
            <option value="inactive">{{ t('resources.status_inactive') }}</option>
          </select>
        </label>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
          {{ t('resources.filter_type') }}
          <select
            v-model="filters.type"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          >
            <option value="">{{ t('resources.filter_all') }}</option>
            <option value="van">{{ t('resources.type_van') }}</option>
            <option value="truck">{{ t('resources.type_truck') }}</option>
            <option value="bus">{{ t('resources.type_bus') }}</option>
          </select>
        </label>
        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
          {{ t('resources.filter_compliance') }}
          <select
            v-model="filters.compliance"
            class="mt-1 w-full rounded-lg border border-slate-200 bg-white py-2 pl-3 pr-8 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
          >
            <option value="">{{ t('resources.filter_all') }}</option>
            <option value="ok">{{ t('resources.compliance_ok') }}</option>
            <option value="soon">{{ t('resources.compliance_soon') }}</option>
            <option value="exp">{{ t('resources.compliance_exp') }}</option>
          </select>
        </label>
      </div>
    </div>

    <div class="relative flex min-h-[420px]">
      <!-- Table -->
      <div class="min-w-0 flex-1 overflow-x-auto p-3 sm:p-4">
        <!-- Vehicles -->
        <table v-if="activeTab === 'vehicles'" class="w-full min-w-[720px] border-separate border-spacing-0 text-left text-sm">
          <thead>
            <tr class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('resources.col_vehicle') }}</th>
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('resources.col_type_capacity') }}</th>
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('resources.col_status') }}</th>
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('resources.col_compliance') }}</th>
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('resources.col_driver') }}</th>
              <th class="border-b border-slate-200 px-2 py-2 text-right dark:border-slate-700">{{ t('resources.col_actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="v in filteredVehicles"
              :key="v.code"
              class="cursor-pointer transition hover:bg-slate-50 dark:hover:bg-slate-800/60"
              :class="selectedVehicle?.code === v.code ? 'bg-teal-50 dark:bg-slate-800/80' : ''"
              @click="selectVehicle(v)"
            >
              <td class="border-b border-slate-100 px-2 py-3 dark:border-slate-800">
                <div class="flex items-center gap-2">
                  <TruckIcon class="h-5 w-5 shrink-0 text-teal-600 dark:text-teal-400" aria-hidden="true" />
                  <div>
                    <div class="font-medium text-slate-900 dark:text-white">{{ v.code }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ v.model }}</div>
                  </div>
                </div>
              </td>
              <td class="border-b border-slate-100 px-2 py-3 text-slate-700 dark:border-slate-800 dark:text-slate-300">{{ v.typeLabel }}</td>
              <td class="border-b border-slate-100 px-2 py-3 dark:border-slate-800">
                <span :class="statusBadgeClass(v.status)">{{ labelVehicleStatus(v.status) }}</span>
              </td>
              <td class="border-b border-slate-100 px-2 py-3 dark:border-slate-800">
                <div class="flex flex-wrap gap-1">
                  <span :class="compliancePillClass(v.insurance)">{{ t('resources.tag_ins') }} {{ insuranceHint(v.insurance) }}</span>
                  <span :class="compliancePillClass(v.registration)">{{ t('resources.tag_reg') }} {{ insuranceHint(v.registration) }}</span>
                </div>
              </td>
              <td class="border-b border-slate-100 px-2 py-3 dark:border-slate-800">
                <span v-if="v.driver" class="text-slate-800 dark:text-slate-200">{{ v.driver }}</span>
                <span v-else class="italic text-slate-500">{{ t('resources.unassigned') }}</span>
              </td>
              <td class="border-b border-slate-100 px-2 py-3 text-right text-slate-400 dark:border-slate-800">
                <ChevronRightIcon class="ml-auto h-5 w-5" aria-hidden="true" />
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Drivers -->
        <table v-else-if="activeTab === 'drivers'" class="w-full min-w-[560px] border-separate border-spacing-0 text-left text-sm">
          <thead>
            <tr class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('resources.col_driver_name') }}</th>
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('resources.col_license') }}</th>
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('resources.col_phone') }}</th>
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('resources.col_status') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in filteredDrivers" :key="d.id" class="border-b border-slate-100 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/50">
              <td class="px-2 py-3 font-medium text-slate-900 dark:text-white">{{ d.name }}</td>
              <td class="px-2 py-3 text-slate-700 dark:text-slate-300">{{ d.license }}</td>
              <td class="px-2 py-3 text-slate-600 dark:text-slate-400">{{ d.phone }}</td>
              <td class="px-2 py-3">
                <span :class="statusBadgeClass(d.status)">{{ labelVehicleStatus(d.status) }}</span>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Suppliers -->
        <table v-else class="w-full min-w-[560px] border-separate border-spacing-0 text-left text-sm">
          <thead>
            <tr class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('resources.col_supplier') }}</th>
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('resources.col_contact') }}</th>
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('resources.col_service') }}</th>
              <th class="border-b border-slate-200 px-2 py-2 dark:border-slate-700">{{ t('resources.col_status') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in filteredSuppliers" :key="s.id" class="border-b border-slate-100 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/50">
              <td class="px-2 py-3 font-medium text-slate-900 dark:text-white">{{ s.name }}</td>
              <td class="px-2 py-3 text-slate-700 dark:text-slate-300">{{ s.contact }}</td>
              <td class="px-2 py-3 text-slate-600 dark:text-slate-400">{{ s.service }}</td>
              <td class="px-2 py-3">
                <span :class="statusBadgeClass(s.status)">{{ labelVehicleStatus(s.status) }}</span>
              </td>
            </tr>
          </tbody>
        </table>

        <p v-if="activeTab === 'vehicles' && !filteredVehicles.length" class="py-8 text-center text-sm text-slate-500">
          {{ t('resources.empty') }}
        </p>
        <p v-if="activeTab === 'drivers' && !filteredDrivers.length" class="py-8 text-center text-sm text-slate-500">
          {{ t('resources.empty') }}
        </p>
        <p v-if="activeTab === 'suppliers' && !filteredSuppliers.length" class="py-8 text-center text-sm text-slate-500">
          {{ t('resources.empty') }}
        </p>
      </div>

      <!-- Detail panel (vehicles) -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="translate-x-4 opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-4 opacity-0"
      >
        <aside
          v-if="activeTab === 'vehicles' && selectedVehicle"
          class="fixed inset-0 z-50 flex justify-end bg-black/50 p-3 backdrop-blur-sm lg:static lg:z-auto lg:inset-auto lg:flex lg:w-[380px] lg:shrink-0 lg:bg-transparent lg:p-0 lg:backdrop-blur-0"
          @click.self="closePanel"
        >
          <div
            class="flex h-full w-full max-w-md flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-600 dark:bg-slate-900 lg:max-w-none lg:rounded-none lg:border-l lg:border-y-0 lg:border-r-0"
            @click.stop
          >
            <div class="border-b border-slate-200 p-4 dark:border-slate-700">
              <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-3">
                  <TruckIcon class="h-10 w-10 text-teal-600 dark:text-teal-400" aria-hidden="true" />
                  <div>
                    <div class="text-lg font-semibold text-slate-900 dark:text-white">{{ selectedVehicle.code }}</div>
                    <div class="text-sm text-slate-600 dark:text-slate-400">{{ selectedVehicle.model }}</div>
                  </div>
                </div>
                <button
                  type="button"
                  class="rounded-lg p-1 text-slate-500 hover:bg-slate-100 hover:text-slate-900 lg:hidden dark:hover:bg-slate-800 dark:hover:text-white"
                  :aria-label="t('resources.close_panel')"
                  @click="closePanel"
                >
                  <span class="text-xl leading-none">×</span>
                </button>
              </div>
              <div class="mt-3 flex flex-wrap gap-2">
                <span :class="['rounded-full border px-2.5 py-0.5 text-xs font-medium', statusOutlineClass(selectedVehicle.status)]">
                  {{ labelVehicleStatus(selectedVehicle.status) }}
                </span>
                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs text-slate-700 dark:bg-slate-700 dark:text-slate-300">{{ selectedVehicle.capacityLabel }}</span>
              </div>
              <div class="mt-4 grid grid-cols-2 gap-2">
                <button
                  type="button"
                  class="rounded-lg bg-teal-600 py-2.5 text-sm font-medium text-white hover:bg-teal-500"
                >
                  {{ t('resources.action_edit') }}
                </button>
                <button
                  type="button"
                  class="flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                >
                  <span class="text-rose-600 dark:text-rose-400">⏻</span>
                  {{ t('resources.action_deactivate') }}
                </button>
              </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-4">
              <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('resources.section_compliance') }}</div>
              <div class="mt-2 space-y-2">
                <div
                  class="rounded-lg border p-3"
                  :class="
                    selectedVehicle.insurance.state === 'soon'
                      ? 'border-amber-300 bg-amber-50 dark:border-amber-500/70 dark:bg-amber-950/20'
                      : 'border-emerald-200 bg-emerald-50 dark:border-emerald-600/50 dark:bg-emerald-950/10'
                  "
                >
                  <div class="text-sm font-medium text-slate-900 dark:text-white">{{ t('resources.doc_insurance') }}</div>
                  <div class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ complianceDocLine(selectedVehicle.insurance) }}</div>
                  <button type="button" class="mt-2 text-xs font-medium text-teal-700 hover:text-teal-800 dark:text-teal-400 dark:hover:text-teal-300">
                    {{ t('resources.update_document') }}
                  </button>
                </div>
                <div
                  class="rounded-lg border p-3"
                  :class="
                    selectedVehicle.registration.state === 'exp'
                      ? 'border-rose-300 bg-rose-50 dark:border-rose-500/70 dark:bg-rose-950/20'
                      : 'border-emerald-200 bg-emerald-50 dark:border-emerald-600/50 dark:bg-emerald-950/10'
                  "
                >
                  <div class="text-sm font-medium text-slate-900 dark:text-white">{{ t('resources.doc_registration') }}</div>
                  <div class="mt-1 text-xs text-slate-600 dark:text-slate-400">{{ complianceDocLine(selectedVehicle.registration) }}</div>
                </div>
              </div>

              <div class="mt-6 text-[11px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ t('resources.section_assignments') }}</div>
              <ul class="relative mt-3 space-y-4 border-l border-slate-200 pl-4 dark:border-slate-700">
                <li v-for="(a, i) in selectedVehicle.assignments" :key="i" class="relative">
                  <span class="absolute -left-[21px] top-1 h-2.5 w-2.5 rounded-full border-2 border-white bg-teal-600 dark:border-slate-900" />
                  <div class="text-xs text-slate-500">{{ a.when }}</div>
                  <div class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ a.title }}</div>
                  <div class="mt-1 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400">
                    <span>{{ a.driver }}</span>
                    <span class="text-emerald-700 dark:text-emerald-400">{{ t('resources.assignment_done') }}</span>
                  </div>
                </li>
              </ul>
            </div>

            <div class="border-t border-slate-200 p-3 dark:border-slate-700">
              <button
                type="button"
                class="w-full rounded-lg border border-slate-200 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800"
              >
                {{ t('resources.view_history') }}
              </button>
            </div>
          </div>
        </aside>
      </Transition>
    </div>

    <p class="border-t border-slate-200 px-4 py-3 text-center text-[11px] text-slate-500 dark:border-slate-700">{{ t('resources.demo_note') }}</p>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronRightIcon, TruckIcon } from '@heroicons/vue/24/outline'

const { t } = useI18n()

const tabs = [
  { id: 'vehicles', labelKey: 'resources.tab_vehicles' },
  { id: 'drivers', labelKey: 'resources.tab_drivers' },
  { id: 'suppliers', labelKey: 'resources.tab_suppliers' },
]

const activeTab = ref('vehicles')
const search = ref('')
const selectedVehicle = ref(null)

const filters = ref({
  status: '',
  type: '',
  compliance: '',
})

/** @type {import('vue').Ref<Array<Record<string, unknown>>>} */
const vehicles = ref([
  {
    code: 'VAN-402',
    model: 'Ford Transit 2022',
    typeKey: 'van',
    typeLabel: 'Passenger Van, 12 Pax',
    capacityLabel: '12 chỗ',
    status: 'active',
    driver: 'Marvin Tucker',
    insurance: { state: 'ok', days: null, until: '2026-08-01' },
    registration: { state: 'ok', days: null, until: '2026-12-15' },
    assignments: [
      { when: '12/04/2026 08:30', title: 'Cargo Run - Downtown', driver: 'Marvin Tucker' },
      { when: '10/04/2026 14:00', title: 'Sân bay → Trường', driver: 'Marvin Tucker' },
    ],
  },
  {
    code: 'TRK-891',
    model: 'Isuzu QKR 2021',
    typeKey: 'truck',
    typeLabel: 'Light truck, 3.5 tấn',
    capacityLabel: '3.5 tấn',
    status: 'maintenance',
    driver: null,
    insurance: { state: 'soon', days: 12, until: '2026-04-25' },
    registration: { state: 'ok', days: null, until: '2027-01-10' },
    assignments: [{ when: '08/04/2026 09:00', title: 'Giao hàng nội thành', driver: 'Lee Nguyen' }],
  },
  {
    code: 'BUS-120',
    model: 'Thaco TB120 2020',
    typeKey: 'bus',
    typeLabel: 'Coach, 45 Pax',
    capacityLabel: '45 chỗ',
    status: 'inactive',
    driver: null,
    insurance: { state: 'exp', days: null, until: '2025-11-01' },
    registration: { state: 'soon', days: 5, until: '2026-04-18' },
    assignments: [],
  },
])

const drivers = ref([
  { id: 1, name: 'Marvin Tucker', license: 'B2 — 12/2028', phone: '0901 234 567', status: 'active' },
  { id: 2, name: 'Lee Nguyen', license: 'C — 06/2027', phone: '0912 888 999', status: 'active' },
  { id: 3, name: 'Anh Minh', license: 'B2 — 03/2026', phone: '0987 000 111', status: 'inactive' },
])

const suppliers = ref([
  { id: 1, name: 'VA Fuel Co.', contact: 'fuel@example.com', service: 'Nhiên liệu', status: 'active' },
  { id: 2, name: 'City Garage', contact: '+84 28 3xxx', service: 'Bảo dưỡng', status: 'active' },
  { id: 3, name: 'Parts Express', contact: 'parts@example.com', service: 'Phụ tùng', status: 'maintenance' },
])

const searchPlaceholder = computed(() => {
  if (activeTab.value === 'vehicles') return t('resources.search_vehicles')
  if (activeTab.value === 'drivers') return t('resources.search_drivers')
  return t('resources.search_suppliers')
})

const addButtonLabel = computed(() => {
  if (activeTab.value === 'vehicles') return t('resources.add_vehicle')
  if (activeTab.value === 'drivers') return t('resources.add_driver')
  return t('resources.add_supplier')
})

function setTab(id) {
  activeTab.value = id
  selectedVehicle.value = null
}

function closePanel() {
  selectedVehicle.value = null
}

function selectVehicle(v) {
  selectedVehicle.value = v
}

function labelVehicleStatus(s) {
  const map = {
    active: t('resources.status_active'),
    maintenance: t('resources.status_maintenance'),
    inactive: t('resources.status_inactive'),
  }
  return map[s] ?? s
}

function statusBadgeClass(s) {
  if (s === 'active') {
    return 'inline-flex rounded-md bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-800 dark:bg-sky-950/80 dark:text-sky-300'
  }
  if (s === 'maintenance') {
    return 'inline-flex rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-900 dark:bg-amber-950/80 dark:text-amber-300'
  }
  if (s === 'inactive') {
    return 'inline-flex rounded-md bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-900 dark:bg-rose-950/80 dark:text-rose-300'
  }
  return 'inline-flex rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-700 dark:bg-slate-700 dark:text-slate-300'
}

function statusOutlineClass(s) {
  if (s === 'active') return 'border-sky-400 text-sky-800 dark:border-sky-500/50 dark:text-sky-300'
  if (s === 'maintenance') return 'border-amber-400 text-amber-900 dark:border-amber-500/60 dark:text-amber-300'
  if (s === 'inactive') return 'border-rose-400 text-rose-900 dark:border-rose-500/50 dark:text-rose-300'
  return 'border-slate-300 text-slate-700 dark:border-slate-600 dark:text-slate-300'
}

function compliancePillClass(doc) {
  if (doc.state === 'ok') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-emerald-100 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300'
  }
  if (doc.state === 'soon') {
    return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300'
  }
  return 'inline-flex rounded px-1.5 py-0.5 text-[10px] font-semibold bg-rose-100 text-rose-900 dark:bg-rose-950/60 dark:text-rose-300'
}

function insuranceHint(doc) {
  if (doc.state === 'ok') return ''
  if (doc.state === 'soon') return `${doc.days}d`
  return t('resources.exp_short')
}

function complianceDocLine(doc) {
  if (doc.state === 'ok') return t('resources.valid_until', { date: doc.until })
  if (doc.state === 'soon') return t('resources.exp_in_days', { n: doc.days })
  return t('resources.expired_on', { date: doc.until })
}

function matchesCompliance(v) {
  const c = filters.value.compliance
  if (!c) return true
  const states = [v.insurance.state, v.registration.state]
  if (c === 'ok') return states.every((s) => s === 'ok')
  if (c === 'soon') return states.some((s) => s === 'soon')
  if (c === 'exp') return states.some((s) => s === 'exp')
  return true
}

const filteredVehicles = computed(() => {
  const q = search.value.trim().toLowerCase()
  return vehicles.value.filter((v) => {
    if (filters.value.status && v.status !== filters.value.status) return false
    if (filters.value.type && v.typeKey !== filters.value.type) return false
    if (!matchesCompliance(v)) return false
    if (!q) return true
    const hay = `${v.code} ${v.model} ${v.driver ?? ''}`.toLowerCase()
    return hay.includes(q)
  })
})

const filteredDrivers = computed(() => {
  const q = search.value.trim().toLowerCase()
  return drivers.value.filter((d) => {
    if (filters.value.status && d.status !== filters.value.status) return false
    if (!q) return true
    return `${d.name} ${d.license} ${d.phone}`.toLowerCase().includes(q)
  })
})

const filteredSuppliers = computed(() => {
  const q = search.value.trim().toLowerCase()
  return suppliers.value.filter((s) => {
    if (filters.value.status && s.status !== filters.value.status) return false
    if (!q) return true
    return `${s.name} ${s.contact} ${s.service}`.toLowerCase().includes(q)
  })
})

watch(filteredVehicles, (list) => {
  if (!selectedVehicle.value) return
  const still = list.find((x) => x.code === selectedVehicle.value.code)
  if (!still) selectedVehicle.value = null
})

watch(activeTab, () => {
  search.value = ''
  filters.value = { status: '', type: '', compliance: '' }
})
</script>
